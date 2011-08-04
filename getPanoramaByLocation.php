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

/* Check for required arguements */
if (!isset($_GET['lat']) || !isset($_GET['lng']) || !isset($_GET['radius'])) {
    exit;
}

$lat = $_GET['lat'];
$lng = $_GET['lng'];
$radius = $_GET['radius'];

/* Calculate bounding box for radius */
$lon1 = $lng - $radius / abs(cos(deg2rad($lat)) * 110852); // 110852m per degree of lat
$lon2 = $lng + $radius / abs(cos(deg2rad($lat)) * 110852); // http://en.wikipedia.org/wiki/Latitude#Degree_length
$lat1 = $lat - ($radius / 110852);
$lat2 = $lat + ($radius / 110852);

/* Get panoramas */
require_once('config.php');
$db = new mysqli("localhost", $config['db_user'], $config['db_password'], $config['db_name']);
$result = $db->query("SELECT * FROM panorama WHERE
                                        lat BETWEEN $lat1 AND $lat2
                                        AND lng BETWEEN $lon1 AND $lon2");

$best = false;
$best_distance = $radius;

while ($pano = $result->fetch_row()) {
    $dist = dist($pano[1], $pano[2], $lat, $lng);
    if ($dist <= $best_distance) {
        $best = $pano;
        $best_distance = $dist;
    }
}

if ($best_distance < 20) {
    $searchResult = array(
                'location' => array (
                    'pano' => 'trackview:' . $best[0],
                    'description' =>  $best[4],
                    'latLng' => array($best[1],  $best[2])
                ),
                'links' => array(),
                'copyright' => $config['copyright'],
                'distance' => $best_distance,
                'tiles' => array(
                    'tileSize' => array(1024, 512),
                    'worldSize' => array(4096, 2048),
                    'centerHeading' => $best[3],
                )
            );

    $result = $db->query("SELECT * FROM link WHERE `from` = '" . $best[0] . "'");
    while ($link = $result->fetch_assoc()) {
        if (substr($link['to'], 0, 7) == 'google:') {
            $searchResult['links'][] = array(
                                          'heading' => ($link['heading'] >= 180) ? $link['heading'] - 180 : $link['heading'] + 180,
                                          'description' => $link['to_name'],
                                          'roadColor' => '#FF0080',
                                          'pano' => str_replace('google:', '', $link['to'])
                                       );
        } else {
            $internal = $db->query('SELECT lat, lng FROM panorama WHERE id = "' . $link['to'] . '"')->fetch_row();
            $searchResult['links'][] = array(
                                          'heading' => ($link['heading'] >= 180) ? $link['heading'] - 180 : $link['heading'] + 180,
                                          'description' => $link['to_name'],
                                          'roadColor' => '#FF0080',
                                          'pano' => "trackview:" . $link['to']
                                       );

        }
    }

    $result = $db->query("SELECT * FROM link WHERE `to` = '" . $best[0] . "'");
    while ($link = $result->fetch_assoc()) {
        $internal = $db->query('SELECT lat, lng FROM panorama WHERE id = "' . $link['to'] . '"')->fetch_row();
        $searchResult['links'][] = array(
                                      'heading' => $link['heading'],
                                      'description' => $link['from_name'],
                                      'roadColor' => '#FF0080',
                                      'pano' => "trackview:" . $link['from']
                                   );
    }
    echo (json_encode($searchResult));
}


function dist($lat1, $lon1, $lat2, $lon2) {
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    return $dist * 60 * 1.1515 * 1.609344 * 1000;
}
