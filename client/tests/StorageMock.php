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

namespace pdyn\pubsubhubbub\client\tests;

use \pdyn\base\Exception;

/**
 * A mock pubsubhubbub client StorageInterface implementation
 */
class StorageMock implements \pdyn\pubsubhubbub\client\StorageInterface {
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
		$request = new \pdyn\pubsubhubbub\client\Request;
		$request->id = 1;
		$request->mode = $mode;
		$request->topic = $topic;
		$request->remoteurl = $remoteurl;
		$request->localurl = $localurl;
		$request->token = $token;
		return $request;
	}

	/**
	 * Create a new request.
	 *
	 * @param string $mode The request mode.
	 * @param string $topic The request topic.
	 * @param string $remoteurl The remote URL.
	 * @param string $localurl The local callback URL.
	 * @param string $token The verification token.
	 * @return array The complete created request. This MUST include an 'id' key that conains a unique identifier to refer to this
	 *               request (this is used for $this->delete_request)
	 */
	public function create_request($mode = null, $topic = null, $remoteurl = null, $localurl = null, $token = null) {
		$request = new \pdyn\pubsubhubbub\client\Request;
		$request->id = 1;
		$request->mode = $mode;
		$request->topic = $topic;
		$request->remoteurl = $remoteurl;
		$request->localurl = $localurl;
		$request->token = $token;
		return $request;
	}

	/**
	 * Delete a request.
	 *
	 * @param int $id The request ID.
	 * @return bool Success/Failure.
	 */
	public function delete_request($id) {
		return true;
	}
}
