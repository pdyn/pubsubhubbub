<?php
/*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * @copyright 2010 onwards James McQuillan (http://pdyn.net)
 * @author James McQuillan <james@pdyn.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace pdyn\pubsubhubbub\server;

use \pdyn\base\Exception;

/**
 * A generic pubsubhubbub server.
 */
class Server {
	/** @var bool Whether to require approval of subscriptions */
	protected $require_approval = false;

	/** @var StorageInterface A StorageInterface implementation the Server can use to store requests and subscriptions. */
	protected $storage = null;

	/**
	 * Constructor.
	 *
	 * @param StorageInterface $storage A storage implementation.
	 */
	public function __construct(StorageInterface $storage, $require_approval = true) {
		$this->storage = $storage;
		$this->require_approval = $require_approval;
	}

	/**
	 * Send a response to the client.
	 *
	 * @param int $httpcode An HTTP code to send.
	 * @param string $msg A message to send.
	 */
	protected function send_response($httpcode, $msg) {
		\pdyn\httputils\Utils::die_with_status_code($httpcode, $msg);
	}

	/**
	 * Distribute content to a given endpoint.
	 *
	 * @param \pdyn\httpclient\HttpClientInterface $httpclient An HttpClientInterface object that will do the actual sending.
	 * @param string $endpoint A url to send the data.
	 * @param string $data The data to send.
	 */
	public function distribute_content(\pdyn\httpclient\HttpClientInterface $httpclient, $endpoint, $data) {
		$httpclient->post_async($endpoint, $data);
	}

	/**
	 * Handle an incoming request.
	 *
	 * This processes and verified incoming requests, then hands it off to process_request_{$mode} functions.
	 *
	 * @param array $params Array of incoming parameters.
	 * @param \pdyn\httpclient\HttpClientInterface $httpclient An HttpClientInterface object used for verifying the request.
	 * @return mixed The result of the process_request_{$mode} function.
	 */
	public function handle_request($params, \pdyn\httpclient\HttpClientInterface $httpclient) {
		try {
			$request = $this->process_request($params);
		} catch (\Exception $e) {
			$this->send_response($e->getCode(), $e->getMessage());
			return false;
		}

		$verified = $this->verify_request($httpclient, $request);
		if ($verified !== true) {
			$this->send_response(412, 'Verification failed.');
			return false;
		}

		$handler = 'handle_request_'.$request->mode;
		if (method_exists($this, $handler)) {
			return $this->$handler($request);
		} else {
			$this->send_response(Exception::ERR_NOT_IMPLEMENTED, 'Did not understand request mode');
			return false;
		}
	}

	/**
	 * Process and clean an incoming request.
	 *
	 * @param array $params Array of received parameters.
	 * @return Request A populated Request object.
	 */
	protected function process_request($params) {
		if (empty($params['hub_callback']) || empty($params['hub_mode']) || empty($params['hub_topic']) || empty($params['hub_verify'])) {
			throw new Exception(
				'Bad Subscription Request: One or more of hub_callback, hub_mode, hub_topic, or hub_verify was not present or empty',
				Exception::ERR_BAD_REQUEST
			);
		}

		$params['hub_mode'] = mb_strtolower($params['hub_mode']);
		$params['hub_verify'] = mb_strtolower($params['hub_verify']);
		if (!in_array($params['hub_verify'], ['sync', 'async'], true)) {
			throw new Exception('We currently only support sync and async verify methods', Exception::ERR_NOT_IMPLEMENTED);
		}
		if (\pdyn\datatype\Url::validate($params['hub_callback']) === false) {
			throw new Exception('Invalid Callback URL', Exception::ERR_BAD_REQUEST);
		}

		if (!is_string($params['hub_mode']) || !method_exists($this, 'handle_request_'.$params['hub_mode'])) {
			throw new Exception('hub_mode not accepted.', Exception::ERR_NOT_IMPLEMENTED);
		}

		if (!isset($params['hub_verify_token'])) {
			$params['hub_verify_token'] = '';
		}

		// Override Lease Seconds
		// We're going to disrespect subscriber requested lease seconds to show them who's boss.
		// (also because constant revalidation of subscriptions is a good thing and we don't intend on keeping permanent or
		// long-term subscriptions active if they are requested)
		$params['hub_lease_seconds'] = 604800;

		$request = new Request;
		$request->mode = $params['hub_mode'];
		$request->callback = $params['hub_callback'];
		$request->topic = $params['hub_topic'];
		$request->verifymode = $params['hub_verify'];
		$request->token = $params['hub_verify_token'];
		$request->timerequested = time();
		$request->leaseseconds = $params['hub_lease_seconds'];
		return $request;
	}

	/**
	 * Verify an incoming request.
	 *
	 * @param \pdyn\httpclient\HttpClientInterface $httpclient An HttpClientInterface instance.
	 * @param Request $request The request object.
	 * @param string $challenge Set the challenge string. If empty, a random one will be chosen.
	 * @return bool Whether the verification was successful.
	 */
	public function verify_request(\pdyn\httpclient\HttpClientInterface $httpclient, Request $request, $challenge = '') {
		if (empty($challenge)) {
			$challenge = md5(rand().rand().rand().microtime());
		}
		$params = [
			'hub.mode' => $request->mode,
			'hub.topic' => $request->topic,
			'hub.challenge' => $challenge,
			'hub.lease_seconds' => $request->leaseseconds,
		];

		if (!empty($request->token)) {
			$params['hub.verify_token'] = $request->token;
		}

		$response = $httpclient->get($request->callback, $params);
		return ($response->status_type() === 'success' && $response->body() === $challenge) ? true : false;
	}

	/**
	 * Handle a request with a mode of "subscribe"
	 *
	 * @param Request $request A populated Request object.
	 * @return bool Success/Failure
	 */
	protected function handle_request_subscribe(Request $request) {
		$existingsubscriber = $this->storage->get_subscriber($request->topic, $request->callback);
		if (empty($existingsubscriber)) {

			// Get, create, or updated request.
			$dbrequest = $this->storage->get_request($request);
			if (empty($dbrequest)) {
				$dbrequest = $this->storage->create_request($request);
			} else {
				if ($dbrequest->token !== $request->token) {
					$dbrequest->token = $request->token;
					$this->storage->update_request($dbrequest->id, $dbrequest);
				}
			}

			// If we require approval, indicate so to client, otherwise approve the request.
			if ($this->require_approval === true) {
				$msg = 'Thank you for your subscription request. We will verify this request with you once the publisher has approved your access.';
				$this->send_response(202, $msg);
			} else {
				if ($this->approve_connection($dbrequest->id) === true) {
					$this->send_response(204, 'Your subscription is active.');
				} else {
					$msg = 'There was a problem automatically approving your request. We will let you know when it has been approved manually.';
					$this->send_response(202, $msg);
				}
			}
			return true;
		} else {
			$newexpiry = ($request->timerequested + $request->leaseseconds);
			$this->storage->update_subscriber($existingsubscriber['id'], ['time_expires' => $newexpiry]);
			$this->send_response(204, 'Your subscription has been refreshed.');
		}
		return true;
	}

	/**
	 * Handle a request with a mode of "unsubscribe"
	 *
	 * @param Request $request A populated Request object.
	 * @return bool Success/Failure
	 */
	protected function handle_request_unsubscribe(Request $request) {
		$existingsubscriber = $this->storage->get_subscriber($request->topic, $request->callback);
		if (!empty($existingsubscriber)) {
			$this->storage->delete_subscriber($existingsubscriber['id']);
			$this->send_response(204, 'Sad to see you go.');
		} else {
			$this->send_response(204, 'You don\'t have an active subscription.');
		}
	}

	/**
	 * Approve a connection.
	 *
	 * @param int $requestid A request ID
	 * @return bool Success/Failure
	 */
	public function approve_connection($requestid) {
		$request = $this->storage->get_request_by_id($requestid);
		if (!empty($request) && $request->mode === 'subscribe') {
			$this->storage->delete_request($request->id);
			$this->storage->create_subscriber($request);
		}
		return true;
	}
}
