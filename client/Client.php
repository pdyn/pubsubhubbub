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

namespace pdyn\pubsubhubbub\client;

use \pdyn\base\Exception;

/**
 * A generic pubsubhubbub client.
 */
class Client {
	/** @var StorageInterface A storage implementation. */
	protected $storage;

	/** @var A logging object. */
	protected $log;

	/**
	 * Constructor.
	 *
	 * @param StorageInterface $storage A storage implementation.
	 */
	public function __construct(StorageInterface $storage, $log = null) {
		$this->storage = $storage;
		if (!empty($log)) {
			$this->log = $log;
		}
	}

	/**
	 * Receive content from the hub.
	 *
	 * @return string The content.
	 */
	public function receive_content() {
		return file_get_contents('php://input');
	}

	/**
	 * Subscribe to a topic at a hub.
	 *
	 * @param string $url The URL to send the request to - the hub URL.
	 * @param string $topic The topic you are subscribing to.
	 * @param string $callback The local callback URL where posts will be sent.
	 * @return bool|array True if successful, array of 'status_code', and 'msg', specifying received http code and body text.
	 */
	public function subscribe($url, $topic, $callback, \pdyn\httpclient\HttpClientInterface $httpclient) {
		return $this->send_request('subscribe', $url, $topic, $callback, $httpclient);
	}

	/**
	 * Unsubscribe from a topic at a hub.
	 *
	 * @param string $url The URL to send the request to - the hub URL.
	 * @param string $topic The topic you are unsubscribing from.
	 * @param string $callback The local callback URL where posts were be sent.
	 * @return bool|array True if successful, array of 'status_code', and 'msg', specifying received http code and body text.
	 */
	public function unsubscribe($url, $topic, $callback, \pdyn\httpclient\HttpClientInterface $httpclient) {
		return $this->send_request('unsubscribe', $url, $topic, $callback, $httpclient);
	}

	/**
	 * This sends a request to the hub.
	 *
	 * @param string $mode The request mode. 'subscribe' or 'unsubscribe'
	 * @param string $url The URL to send the request to - the hub URL.
	 * @param string $topic The topic you are referring to.
	 * @param string $callback The local callback URL.
	 * @param \pdyn\httpclient\HttpClientInterface $httpclient An HttpClientInterface object that will handle the transfer.
	 * @return bool|array True if successful, array of 'status_code', and 'msg', specifying received http code and body text.
	 */
	protected function send_request($mode, $url, $topic, $callback, \pdyn\httpclient\HttpClientInterface $httpclient) {
		if (!\pdyn\datatype\Url::validate($callback)) {
			throw new Exception('Bad $callback received', Exception::ERR_BAD_REQUEST);
		}
		if (!\pdyn\datatype\Url::validate($url)) {
			throw new Exception('Bad $url received', Exception::ERR_BAD_REQUEST);
		}

		$request = $this->storage->get_request($mode, $topic, $url, $callback);

		$verifytoken = \pdyn\base\Utils::genRandomStr(64);
		if (empty($request)) {
			$request = $this->storage->create_request($mode, $topic, $url, $callback, $verifytoken);
		}

		$postdata = [
			'hub.mode' => $mode,
			'hub.callback' => $callback,
			'hub.topic' => $topic,
			'hub.verify' => 'async',
			'hub.verify_token' => $verifytoken,
			'hub.lease_seconds' => 604800,
		];

		$response = $httpclient->post($url, $postdata);
		if ($response->status_code() === 204 || $response->status_code() === 202) {
			return true;
		} else {
			$this->storage->delete_request($request->id);
			return ['status_code' => $response->status_code(), 'msg' => $response->body()];
		}
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
	 * This verifies a request we sent to the hub.
	 *
	 * Every time we send a request to the hub, the hub should ask for verification that the request actually came from the
	 * specified callback. This responds to the hub when the hub requests to verify a request.
	 *
	 * @param array $params Received parameters.
	 * @param string $localcallback The local URL that received the verification. This is used to look up the request.
	 * @return bool Verified/Not Verified.
	 */
	public function verify_request($params, $localcallback, $quiet = false) {
		if (empty($params['hub_mode']) || empty($params['hub_challenge']) || empty($params['hub_verify_token'])) {
			$msg = 'Request Incomplete: missing hub_mode, hub_challenge, or hub_verify_token';
			if (!empty($this->log)) {
				$this->log->debug($msg);
			}
			if ($quiet !== true) {
				$this->send_response(400, $msg);
			}
			return false;
		}
		$params['hub_mode'] = mb_strtolower($params['hub_mode']);
		if (!in_array($params['hub_mode'], ['subscribe', 'unsubscribe'], true)) {
			$msg = 'Bad Request: hub_mode must be either subscribe or unsubscribe';
			if (!empty($this->log)) {
				$this->log->debug($msg);
			}
			if ($quiet !== true) {
				$this->send_response(400, $msg);
			}
			return false;
		}

		// Attempt to get and verify request.
		$request = $this->storage->get_request($params['hub_mode'], $params['hub_topic'], null, $localcallback, $params['hub_verify_token']);
		$verified = (!empty($request)) ? true : false;

		if ($verified === true) {
			if (!empty($this->log)) {
				$this->log->debug('Found request, verified.');
			}
			// Request is verified, we don't need it any more.
			$this->storage->delete_request($request->id);

			// Send response and return.
			if ($quiet !== true) {
				$this->send_response(200, $params['hub_challenge']);
			}
			return true;
		} else {
			if (!empty($this->log)) {
				$this->log->debug('Could not find request, cannot verify.');
			}
			if ($quiet !== true) {
				usleep(rand(30000, 2000000));
				$this->send_response(404, 'Verification Failed 1');
			}
			return false;
		}
	}
}
