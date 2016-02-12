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

/**
 * A pubsubhubbub client StorageInterface implementation using the database.
 */
class StorageDb implements StorageInterface {

	/**
	 * Constructor.
	 *
	 * @param \pdyn\database\DbDriverInterface $DB An active database connection.
	 */
	public function __construct(\pdyn\database\DbDriverInterface &$DB) {
		$this->DB =& $DB;
	}

	/**
	 * Translate a request database record into a Request object.
	 *
	 * @param array $dbrec The database record array.
	 * @return Request The populated Request object.
	 */
	protected function request_from_dbrec(array $dbrec) {
		$request = new Request;
		$request->id = $dbrec['id'];
		$request->mode = $dbrec['action'];
		$request->topic = $dbrec['rmt_data'];
		$request->remoteurl = $dbrec['remote_endpoint'];
		$request->localurl = $dbrec['local_endpoint'];
		$request->token = $dbrec['verify_token'];
		return $request;
	}

	/**
	 * Translate a Request object to a database record.
	 *
	 * @param Request $request A populated Request object.
	 * @return array A database record array, ready to be inserted.
	 */
	protected function request_to_dbrec(Request $request) {
		return [
			'connection_type' => 'pubsubhubbub',
			'action' => $request->mode,
			'rmt_data' => $request->topic,
			'remote_endpoint' => $request->remoteurl,
			'local_endpoint' => $request->localurl,
			'verify_token' => $request->token
		];
	}

	/**
	 * Get a saved request.
	 *
	 * @param string $mode The request mode.
	 * @param string $topic The request topic.
	 * @param string $remoteurl The remote URL.
	 * @param string $localurl The local callback URL.
	 * @param string $token The verification token.
	 * @return array|bool The request, if found, or false.
	 */
	public function get_request($mode = null, $topic = null, $remoteurl = null, $localurl = null, $token = null) {
		$request = new Request;
		$request->mode = $mode;
		$request->topic = $topic;
		$request->remoteurl = $remoteurl;
		$request->localurl = $localurl;
		$request->token = $token;

		$params = $this->request_to_dbrec($request);
		if ($params['verify_token'] === '' || $params['verify_token'] === null) {
			unset($params['verify_token']);
		}
		if ($params['remote_endpoint'] === '' || $params['remote_endpoint'] === null) {
			unset($params['remote_endpoint']);
		}
		$dbrec = $this->DB->get_record('connections_sockets_requests_out', $params);
		if (empty($dbrec)) {
			return false;
		} else {
			return $this->request_from_dbrec($dbrec);
		}
	}

	/**
	 * Create a new request.
	 *
	 * @param string $mode The request mode.
	 * @param string $topic The request topic.
	 * @param string $remoteurl The remote URL.
	 * @param string $localurl The local callback URL.
	 * @param string $token The verification token.
	 * @return array The complete created request.
	 */
	public function create_request($mode = null, $topic = null, $remoteurl = null, $localurl = null, $token = null) {
		$request = new Request;
		$request->mode = $mode;
		$request->topic = $topic;
		$request->remoteurl = $remoteurl;
		$request->localurl = $localurl;
		$request->token = $token;
		$request->time_requested = time();

		$request->id = $this->DB->insert_record('connections_sockets_requests_out', $this->request_to_dbrec($request));
		return $request;
	}

	/**
	 * Delete a request.
	 *
	 * @param int $id The request ID.
	 * @return bool Success/Failure.
	 */
	public function delete_request($id) {
		$this->DB->delete_records('connections_sockets_requests_out', ['id' => $id]);
		return true;
	}
}
