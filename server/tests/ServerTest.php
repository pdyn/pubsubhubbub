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
use \pdyn\httpclient\tests\MockHttpClientResponse;

/**
 * Test pubsubhubbub server.
 *
 * @group pdyn
 * @group pdyn_pubsubhubbub
 * @group pdyn_pubsubhubbub_client
 * @codeCoverageIgnore
 */
class ServerTest extends \PHPUnit_Framework_TestCase {
	/**
	 * Test the process_request method.
	 */
	public function test_process_request() {
		$storagemock = new MockStorage();
		$server = new ServerMock($storagemock);

		$goodparams = [
			'hub_callback' => 'http://localhost/',
			'hub_mode' => 'subscribe',
			'hub_topic' => 'testtopic',
			'hub_verify' => 'async',
		];

		// Test ERR_BAD_REQUEST is thrown when params are empty.
		try {
			$params = [];
			$server->process_request($params);
		} catch (\Exception $e) {
			$this->assertEquals(Exception::ERR_BAD_REQUEST, $e->getCode());
		}

		// Test ERR_BAD_REQUEST is thrown when params do not include hub_callback.
		foreach (['hub_callback', 'hub_mode', 'hub_topic', 'hub_verify'] as $reqparam) {
			try {
				$params = $goodparams;
				unset($params[$reqparam]);
				$server->process_request($params);
				$this->assertTrue(false, 'Exception should have been thrown before reaching here. Param: '.$reqparam);
			} catch (\Exception $e) {
				$this->assertEquals(Exception::ERR_BAD_REQUEST, $e->getCode(), 'Incorrect exception code caught. Param: '.$reqparam);
			}
		}

		// Verify that 'hub_verify' must be 'sync' or 'async'.
		try {
			$params = $goodparams;
			$params['hub_verify'] = 'badval';
			$server->process_request($params);
			$this->assertTrue(false, 'Exception should have been thrown before reaching here.');
		} catch (\Exception $e) {
			$this->assertEquals(Exception::ERR_NOT_IMPLEMENTED, $e->getCode(), 'Incorrect exception code caught.');
		}

		// Verify that 'hub_callback' is validated as a URL.
		try {
			$params = $goodparams;
			$params['hub_callback'] = 'badval';
			$server->process_request($params);
			$this->assertTrue(false, 'Exception should have been thrown before reaching here.');
		} catch (\Exception $e) {
			$this->assertEquals(Exception::ERR_BAD_REQUEST, $e->getCode(), 'Incorrect exception code caught.');
		}

		// Verify that 'hub_mode' is implemented.
		try {
			$params = $goodparams;
			$params['hub_mode'] = 'badval';
			$server->process_request($params);
			$this->assertTrue(false, 'Exception should have been thrown before reaching here.');
		} catch (\Exception $e) {
			$this->assertEquals(Exception::ERR_NOT_IMPLEMENTED, $e->getCode(), 'Incorrect exception code caught.');
		}

		// Verify we get a good Request object returned when params are correct.
		$params = $goodparams;
		$request = $server->process_request($params);
		$request = (array)$request;
		$expectedrequest = [
			'id' => null,
			'mode' => 'subscribe',
			'callback' => 'http://localhost/',
			'topic' => 'testtopic',
			'verifymode' => 'async',
			'token' => '',
			'timerequested' => $request['timerequested'],
			'leaseseconds' => 604800
		];
		$this->assertEquals($expectedrequest, $request);

		// Verify we get a good Request object returned when params are correct and verify_token is set.
		$params = $goodparams;
		$params['hub_verify_token'] = '12345';
		$request = $server->process_request($params);
		$request = (array)$request;
		$expectedrequest = [
			'id' => null,
			'mode' => 'subscribe',
			'callback' => 'http://localhost/',
			'topic' => 'testtopic',
			'verifymode' => 'async',
			'token' => '12345',
			'timerequested' => $request['timerequested'],
			'leaseseconds' => 604800
		];
		$this->assertEquals($expectedrequest, $request);
	}

	/**
	 * Test the verify_request method.
	 */
	public function test_verify_request() {
		$storagemock = new MockStorage();
		$server = new ServerMock($storagemock);

		// Construct mock http client.
		$httpclientmock = new \pdyn\httpclient\tests\MockHttpClient();
		$httpclientmock->set_response(new MockHttpClientResponse(200, 'text/plain', 'CHALLENGE'));

		// Construct request.
		$request = new \pdyn\pubsubhubbub\server\Request;
		$request->callback = 'http://localhost/';
		$request->mode = 'subscribe';
		$request->topic = 'testtopic';
		$request->token = '12345';

		// Test successful verification w/ verification token.
		$verified = $server->verify_request($httpclientmock, $request, 'CHALLENGE');
		$this->assertTrue($verified);
		$this->assertEquals($request->callback, $httpclientmock->get_request_url());
		$expectedsentdata = [
			'hub.mode' => 'subscribe',
			'hub.topic' => 'testtopic',
			'hub.challenge' => 'CHALLENGE',
			'hub.verify_token' => '12345',
			'hub.lease_seconds' => 604800
		];
		$this->assertEquals($expectedsentdata, $httpclientmock->get_request_data());

		// Test successful verification w/ verification token.
		$requestnotoken = $request;
		$requestnotoken->token = '';
		$verified = $server->verify_request($httpclientmock, $requestnotoken, 'CHALLENGE');
		$this->assertTrue($verified);
		$this->assertEquals($request->callback, $httpclientmock->get_request_url());
		$expectedsentdata = [
			'hub.mode' => 'subscribe',
			'hub.topic' => 'testtopic',
			'hub.challenge' => 'CHALLENGE',
			'hub.lease_seconds' => 604800
		];
		$this->assertEquals($expectedsentdata, $httpclientmock->get_request_data());

		// Test successful verification based on other success status codes.
		$httpclientmock->set_response(new MockHttpClientResponse(202, 'text/plain', 'CHALLENGE'));
		$verified = $server->verify_request($httpclientmock, $request, 'CHALLENGE');
		$this->assertTrue($verified);
		$httpclientmock->set_response(new MockHttpClientResponse(204, 'text/plain', 'CHALLENGE'));
		$verified = $server->verify_request($httpclientmock, $request, 'CHALLENGE');
		$this->assertTrue($verified);

		// Test failed verification based on non-success status code.
		$httpclientmock->set_response(new MockHttpClientResponse(400, 'text/plain', 'CHALLENGE'));
		$verified = $server->verify_request($httpclientmock, $request, 'CHALLENGE');
		$this->assertFalse($verified);

		// Test failed verification based on non-success status code.
		$httpclientmock->set_response(new MockHttpClientResponse(500, 'text/plain', 'CHALLENGE'));
		$verified = $server->verify_request($httpclientmock, $request, 'CHALLENGE');
		$this->assertFalse($verified);

		// Test failed verification based on not echoing the challenge.
		$httpclientmock->set_response(new MockHttpClientResponse(200, 'text/plain', 'BAD RESPONSE'));
		$verified = $server->verify_request($httpclientmock, $request, 'CHALLENGE');
		$this->assertFalse($verified);
	}

	/**
	 * Test handle_request method.
	 */
	public function test_handle_request() {
		$storagemock = new MockStorage();
		$server = new ServerMock($storagemock);

		$httpclientmock = new \pdyn\httpclient\tests\MockHttpClient();

		$params = [
			'hub_mode' => 'testmode',
			'hub_callback' => 'http://localhost/',
			'hub_topic' => 'testtopic',
			'hub_verify' => 'async',
			'hub_verify_token' => '12345'
		];

		// Test successful handling.
		$responsefunc = function($requestdata) { return $requestdata['hub.challenge']; };
		$httpclientmock->set_response(new MockHttpClientResponse(200, 'text/plain', $responsefunc));
		$actualrequest = $server->handle_request($params, $httpclientmock);
		$actualrequest->timerequested = 1;
		$expectedrequest = new \pdyn\pubsubhubbub\server\Request;
		$expectedrequest->id = null;
		$expectedrequest->mode = 'testmode';
		$expectedrequest->callback = 'http://localhost/';
		$expectedrequest->topic = 'testtopic';
		$expectedrequest->verifymode = 'async';
		$expectedrequest->token = '12345';
		$expectedrequest->timerequested = 1;
		$expectedrequest->leaseseconds;
		$this->assertEquals($expectedrequest, $actualrequest);

		// Test failed verification.
		$httpclientmock->set_response(new MockHttpClientResponse(200, 'text/plain', 'badresponse'));
		$actualrequest = $server->handle_request($params, $httpclientmock);
		$this->assertEquals(412, $server->last_response['code']);

		// Test bad request mode.
		$params['hub_mode'] = 'badmode';
		$responsefunc = function($requestdata) { return $requestdata['hub.challenge']; };
		$httpclientmock->set_response(new MockHttpClientResponse(200, 'text/plain', $responsefunc));
		$actualrequest = $server->handle_request($params, $httpclientmock);
		$this->assertEquals(501, $server->last_response['code']);
	}

	/**
	 * Test the handle_request_subscribe method.
	 */
	public function test_handle_request_subscribe() {
		$storagemock = new MockStorage();
		$server = new ServerMock($storagemock);
		$time = time() - 10;

		// Test request.
		$request = new \pdyn\pubsubhubbub\server\Request;
		$request->mode = 'subscribe';
		$request->callback = 'http://localhost/';
		$request->topic = 'testtopic';
		$request->token = '12345';
		$request->timerequested = $time;

		// Test that when a new request is made, and we require verification, it is stored and the correct response is returned.
		$server->require_approval = true;
		$server->handle_request_subscribe($request);
		$this->assertEquals(1, count($server->storage->requests));
		$expectedrequest = $request;
		$expectedrequest->id = 1;
		$expectedrequest->verifymode = 'async';
		$expectedrequest->timerequested = $time;
		$expectedrequest->leaseseconds = 604800;
		$this->assertEquals($expectedrequest, $server->storage->requests[1]);
		$this->assertEquals(202, $server->last_response['code']);

		// Test that when a request is made with the same topic, callback, and mode, we update the verification token.
		$request->token = '56789';
		$server->require_approval = true;
		$server->handle_request_subscribe($request);
		$this->assertEquals(1, count($server->storage->requests)); // Make sure we didn't create a new request.
		$expectedrequest->token = '56789';
		$this->assertEquals($expectedrequest, $server->storage->requests[1]);
		$this->assertEquals(202, $server->last_response['code']);

		// Test auto-approving subscription requests.
		$server->require_approval = false;
		$server->handle_request_subscribe($request);
		$this->assertEmpty($server->storage->requests);
		$this->assertEquals(1, count($server->storage->subscribers));
		$expectedsubscriber = [
			'id' => 1,
			'topic' => $request->topic,
			'callback' => $request->callback,
			'time_expires' => $time + 604800
		];
		$this->assertEquals($expectedsubscriber, $server->storage->subscribers[1]);

		// Test updating existing subscriptions.
		$request->timerequested += 10;
		$server->handle_request_subscribe($request);
		$this->assertEmpty($server->storage->requests);
		$this->assertEquals(1, count($server->storage->subscribers));
		$expectedsubscriber = [
			'id' => 1,
			'topic' => $request->topic,
			'callback' => $request->callback,
			'time_expires' => $time + 10 + 604800
		];
		$this->assertEquals($expectedsubscriber, $server->storage->subscribers[1]);
	}

	/**
	 * Test the handle_request_unsubscribe method.
	 */
	public function test_handle_request_unsubscribe() {
		$storagemock = new MockStorage();
		$server = new ServerMock($storagemock);
		$time = time() - 10;

		// Test request.
		$request = new \pdyn\pubsubhubbub\server\Request;
		$request->mode = 'unsubscribe';
		$request->callback = 'http://localhost/';
		$request->topic = 'testtopic';
		$request->token = '12345';
		$request->timerequested = $time;

		$existingsubscriber = [
			'id' => 1,
			'topic' => $request->topic,
			'callback' => $request->callback,
			'time_expires' => $time + 604800
		];
		$server->storage->subscribers[1] = $existingsubscriber;

		$server->handle_request_unsubscribe($request);
		$this->assertEmpty($server->storage->requests);
		$this->assertEmpty($server->storage->subscribers);
	}
}
