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
 * A mock pubsubhubbub client implementation allowing assertion of HTTP responses and direct access to the send_request function.
 */
class ClientMock extends \pdyn\pubsubhubbub\client\Client {
	/** @var array The last HTTP resonse sent to the client. */
	public $last_response = [];

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
	public function send_request($mode, $url, $topic, $callback, \pdyn\httpclient\HttpClientInterface $httpclient) {
		return parent::send_request($mode, $url, $topic, $callback, $httpclient);
	}

	/**
	 * Send a response to the client.
	 *
	 * This mock implementation just records the intended response internally to be later asserted.
	 *
	 * @param int $httpcode An HTTP code to send.
	 * @param string $msg A message to send.
	 */
	public function send_response($httpcode, $msg) {
		$this->last_response = ['code' => $httpcode, 'msg' => $msg];
	}
}
