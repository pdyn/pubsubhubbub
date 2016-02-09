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
 * A mock StorageInterface implementation that stores all data in the class instead of the database.
 */
class MockStorage implements \pdyn\pubsubhubbub\server\StorageInterface {
	/** @var array Stored requests. */
	public $requests = [];

	/** @var array Stored subscribers. */
	public $subscribers = [];

	/**
	 * Create a request.
	 *
	 * @param \pdyn\pubsubhubbub\server\Request $request The populated Request object.
	 * @return \pdyn\pubsubhubbub\server\Request The request object with populated 'id' field.
	 */
	public function create_request(\pdyn\pubsubhubbub\server\Request $request) {
		static $id = 1;
		$newreq = clone $request;
		$newreq->id = $id;
		$this->requests[$id] = $newreq;
		$id++;
		return $newreq;
	}

	/**
	 * Get an existing request based on request parameters.
	 *
	 * @param string $mode The request mode.
	 * @param string $topic The requested topic.
	 * @param string $callback The request callback.
	 * @return array|bool The full request array, or false if not found.
	 */
	public function get_request(\pdyn\pubsubhubbub\server\Request $request) {
		foreach ($this->requests as $savedreq) {
			$params = ['callback', 'topic', 'mode'];
			$equal = true;
			foreach ($params as $param) {
				if ($savedreq->$param !== $request->$param) {
					$equal = false;
					break;
				}
			}
			if ($equal === true) {
				return clone $savedreq;
			}
		}
	}

	/**
	 * Get a request by unique identifier.
	 *
	 * @param int $id The unique identifier of the request.
	 * @return array|bool The full created request array, or false if not found.
	 */
	public function get_request_by_id($id) {
		foreach ($this->requests as $i => $savedreq) {
			if ($savedreq->id == $id) {
				return clone $savedreq;
			}
		}
		return false;
	}

	/**
	 * Update a request.
	 *
	 * @param int $id The unique identifier of the request.
	 * @param \pdyn\pubsubhubbub\server\Request $request The updated Request object.
	 * @return bool Success/Failure.
	 */
	public function update_request($id, \pdyn\pubsubhubbub\server\Request $request) {
		foreach ($this->requests as $i => $savedreq) {
			if ($savedreq->id == $id) {
				$this->requests[$i] = clone $request;
				return true;
			}
		}
		return false;
	}

	/**
	 * Delete a request.
	 *
	 * @param int $id The unique identifier of the request to delete.
	 * @return bool Success/Failure.
	 */
	public function delete_request($id) {
		foreach ($this->requests as $i => $savedreq) {
			if ($savedreq->id == $id) {
				unset($this->requests[$i]);
				return true;
			}
		}
		return false;
	}

	/**
	 * Create a new subscriber.
	 *
	 * @param \pdyn\pubsubhubbub\server\Request $request A Request object.
	 * @return array The full subscriber array. This MUST include an 'id' key, containing a unique identifier,
	 */
	public function create_subscriber(\pdyn\pubsubhubbub\server\Request $request) {
		static $id = 1;
		$this->subscribers[$id] = [
			'id' => $id,
			'topic' => $request->topic,
			'callback' => $request->callback,
			'time_expires' => $request->timerequested + $request->leaseseconds,
		];
		$id++;
	}

	/**
	 * Get a subscriber based on topic and callback.
	 *
	 * @param string $topic The subscribed topic.
	 * @param string $callback The subscriber's callback URL.
	 * @return array|bool The full subscriber array, or false if not found.
	 */
	public function get_subscriber($topic, $callback) {
		foreach ($this->subscribers as $subscriber) {
			if ($subscriber['topic'] === $topic && $subscriber['callback'] === $callback) {
				return $subscriber;
			}
		}
		return false;
	}

	/**
	 * Update subscriber information.
	 *
	 * @param int $id The unique identifier for the subscriber.
	 * @param array $updated An array of updated information.
	 * @return bool Success/Failure.
	 */
	public function update_subscriber($id, $updated) {
		if (isset($this->subscribers[$id])) {
			$this->subscribers[$id] = array_merge($this->subscribers[$id], $updated);
		} else {
			return false;
		}
	}

	/**
	 * Delete a subscriber.
	 *
	 * @param int $id The unique identifier of the subscriber to delete.
	 * @return bool Success/Failure.
	 */
	public function delete_subscriber($id) {
		if (isset($this->subscribers[$id])) {
			unset($this->subscribers[$id]);
			return true;
		}
		return false;
	}
}
