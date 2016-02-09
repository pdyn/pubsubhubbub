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

namespace pdyn\pubsubhubbub\server\tests;

use \pdyn\base\Exception;

/**
 * A mock pubsubhubbub server allowing access to all protected properties/methods, assertion of HTTP responses, and handling
 * of a test request mode.
 */
class ServerMock extends \pdyn\pubsubhubbub\server\Server {
	use \pdyn\testing\AccessibleObjectTrait;

	/** @var array The last HTTP resonse sent. */
	public $last_response;

	/**
	 * Send an HTTP response.
	 *
	 * This mock implementation just records the intended response internally to be later asserted.
	 *
	 * @param int $httpcode An HTTP code to send.
	 * @param string $msg A message to send.
	 */
	protected function send_response($httpcode, $msg) {
		$this->last_response = ['code' => $httpcode, 'msg' => $msg];
	}

	/**
	 * Handle request function for a test mode.
	 *
	 * @param \pdyn\pubsubhubbub\server\Request $request A populated Request object.
	 * @return bool Success/Failure
	 */
	public function handle_request_testmode(\pdyn\pubsubhubbub\server\Request $request) {
		return $request;
	}
}
