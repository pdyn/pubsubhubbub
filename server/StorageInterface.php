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

/**
 * Storage Interface for pubsubhubbub server.
 */
interface StorageInterface {
	/**
	 * Create a request.
	 *
	 * @param Request $request The populated Request object.
	 * @return Request The request object with populated 'id' field.
	 */
	public function create_request(Request $request);

	/**
	 * Get an existing request based on request parameters.
	 *
	 * @param string $mode The request mode.
	 * @param string $topic The requested topic.
	 * @param string $callback The request callback.
	 * @return array|bool The full request array, or false if not found.
	 */
	public function get_request(Request $request);

	/**
	 * Get a request by unique identifier.
	 *
	 * @param int $id The unique identifier of the request.
	 * @return array|bool The full created request array, or false if not found.
	 */
	public function get_request_by_id($id);

	/**
	 * Update a request.
	 *
	 * @param int $id The unique identifier of the request.
	 * @param Request $request The updated Request object.
	 * @return bool Success/Failure.
	 */
	public function update_request($id, Request $request);

	/**
	 * Delete a request.
	 *
	 * @param int $id The unique identifier of the request to delete.
	 * @return bool Success/Failure.
	 */
	public function delete_request($id);

	/**
	 * Create a new subscriber.
	 *
	 * @param Request $request A Request object.
	 * @return array The full subscriber array. This MUST include an 'id' key, containing a unique identifier,
	 */
	public function create_subscriber(Request $request);

	/**
	 * Get a subscriber based on topic and callback.
	 *
	 * @param string $topic The subscribed topic.
	 * @param string $callback The subscriber's callback URL.
	 * @return array|bool The full subscriber array, or false if not found.
	 */
	public function get_subscriber($topic, $callback);

	/**
	 * Update subscriber information.
	 *
	 * @param int $id The unique identifier for the subscriber.
	 * @param array $updated An array of updated information.
	 * @return bool Success/Failure.
	 */
	public function update_subscriber($id, $updated);

	/**
	 * Delete a subscriber.
	 *
	 * @param int $id The unique identifier of the subscriber to delete.
	 * @return bool Success/Failure.
	 */
	public function delete_subscriber($id);
}
