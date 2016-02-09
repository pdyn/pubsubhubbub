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
 * Test pubsubhubbbub client.
 *
 * @group pdyn
 * @group pdyn_pubsubhubbub
 * @group pdyn_pubsubhubbub_client
 * @codeCoverageIgnore
 */
class ClientTest extends \PHPUnit_Framework_TestCase {
	/**
	 * Test send_request method.
	 */
	public function test_send_request() {
		$httpclient = new \pdyn\httpclient\tests\MockHttpClient();
		$clientstorage = new StorageMock();
		$client = new ClientMock($clientstorage);

		// Test successfuly 202 and 204 status codes.
		$httpclient->set_response(new \pdyn\httpclient\HttpClientResponse(202, 'text/plain', ''));
		$result = $client->send_request('subscribe', 'http://localhost/', 'testtopic', 'http://example.com/pubsub/123', $httpclient);
		$this->assertTrue($result);

		$httpclient->set_response(new \pdyn\httpclient\HttpClientResponse(204, 'text/plain', ''));
		$result = $client->send_request('subscribe', 'http://localhost/', 'testtopic', 'http://example.com/pubsub/123', $httpclient);
		$this->assertTrue($result);

		// Test failure conditions.
		$httpclient->set_response(new \pdyn\httpclient\HttpClientResponse(400, 'text/plain', ''));
		$result = $client->send_request('subscribe', 'http://localhost/', 'testtopic', 'http://example.com/pubsub/123', $httpclient);
		$this->assertInternalType('array', $result);
		$this->assertArrayHasKey('status_code', $result);
		$this->assertEquals(400, $result['status_code']);
		$this->assertArrayHasKey('msg', $result);
		$expectedrequestdata = [
			'hub.mode' => 'subscribe',
			'hub.callback' => 'http://example.com/pubsub/123',
			'hub.topic' => 'testtopic',
			'hub.verify' => 'async',
			'hub.lease_seconds' => 604800
		];
		$actualrequestdata = $httpclient->get_request_data();
		$this->assertArrayHasKey('hub.verify_token', $actualrequestdata);
		unset($actualrequestdata['hub.verify_token']);
		$this->assertEquals($expectedrequestdata, $actualrequestdata);
	}

	/**
	 * Test verify_request method.
	 */
	public function test_verify_request() {
		$clientstorage = new StorageMock();
		$client = new ClientMock($clientstorage);

		$params = [
			'hub_mode' => 'subscribe',
			'hub_challenge' => 'CHALLENGE',
			'hub_verify_token' => 'VERIFY',
			'hub_topic' => 'topic',
		];
		$localcallback = 'http://localhost';

		// Test quiet mode.
		$verified = $client->verify_request($params, $localcallback, true);
		$this->assertTrue($verified);

		// Test non-quiet mode.
		$verified = $client->verify_request($params, $localcallback);
		$this->assertNotEmpty($client->last_response);
		$this->assertEquals(200, $client->last_response['code']);
		$this->assertEquals('CHALLENGE', $client->last_response['msg']);
	}
}
