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
 * Represents a pubsubhubbub client request.
 */
class Request {
	/** @var int The database ID of the request. */
	public $id = null;

	/** @var string The request mode. Either "subscribe" or "unsubscribe" */
	public $mode = '';

	/** @var string The topic we're requesting a subscription to. Usually the feed URL. */
	public $topic = '';

	/** @var string The remote URL to send the request to. Usually the hub URL. */
	public $remoteurl = '';

	/** @var string The local callback URL where new posts should be sent. */
	public $localurl = '';

	/** @var string The verification token. */
	public $token = '';

	/** @var int The UNIX timestamp when the request was made. */
	public $timerequested = 0;

	/** @var int How long we're requesting the subscription for. Hub may or may not respect this. */
	public $leaseseconds = 604800;
}
