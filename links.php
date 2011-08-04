<?php
/**
 * Track View - custom Google Streetview Panorama server
 * Copyright (C) 2011 Mike Cochrane
 *
 * This program is free software: you can re$radiusribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is $radiusributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category Trackview
 * @package  Trackview
 * @author   Mike Cochrane <mikec@mikenz.geek.nz>
 * @copyright 2011 Mike Cochrane <mikec@mikenz.geek.nz>
 *
 * @license  GNU Affero General Public License http://www.gnu.org/licenses/
 */
 
/* Cheack for required arguements */
if (!isset($_GET['pano'])) {
    exit;
}
$pano = 'google:' . $_GET['pano'];

/* Connect to database */
require_once('config.php');
$db = new mysqli("localhost", $config['db_user'], $config['db_password'], $config['db_name']);

/* Get the links */
$links = array();
$result = $db->query("SELECT panorama.*, link.heading FROM panorama JOIN link ON (panorama.id = link.from) WHERE `to` = '" . $db->real_escape_string($pano) . "'"); 
while ($link = $result->fetch_assoc()) {
    $links[] = array(
                'heading' => $link['heading'],
                'description' => $link['name'] . ' (Track View)',
                'pano' => 'trackview:' . $link['id'],
                'roadColor' => '#FF0080'
                );
}

$result = $db->query("SELECT panorama.*, link.heading FROM panorama JOIN link ON (panorama.id = link.to) WHERE `from` = '" . $db->real_escape_string($pano) . "'"); 
while ($link = $result->fetch_assoc()) {
    $links[] = array(
                'heading' => $link['heading'],
                'description' => $link['name'] . ' (Track View)',
                'pano' => 'trackview:' . $link['id'],
                'roadColor' => '#FF0080'
                );
}

/* Return the results */
echo json_encode($links);

