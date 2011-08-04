<?php
/**
 * Track View - custom Google Streetview Panorama server
 * Copyright (C) 2011 Mike Cochrane
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
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
 
// TODO: configure nginx so it returns cached file
require_once('config.php');

$db = new mysqli("localhost", $config['db_user'], $config['db_password'], $config['db_name']);
list(, $panoid) = explode(":", $_GET['pano']);
$result = $db->query("SELECT filename FROM panorama WHERE id = " . intval($panoid));
$pano = $result->fetch_row();

$img = str_replace(".jpg", "", basename($pano[0]));
$zoom = $_GET['zoom'];
$fmt = $_GET['fmt'];
$x = $_GET['x'];
$y = $_GET['y'];

$cache = "cache/$img-$zoom-$x-$y.$fmt";
if (file_exists($cache)) {
    header("Cache-Control: private, max-age=10800, pre-check=10800");
    header("Pragma: private");
    header("Expires: " . date(DATE_RFC822,strtotime(" 2 day")));

    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) < filemtime($cache)) {
        header('Last-Modified: '. gmdate('D, d M Y H:i:s', filemtime($cache)) . ' GMT', true, 304);
        exit;
    }

    header('Content-type: image/jpeg');
    header('Content-length: ' . filesize($cache));
    readfile($cache);
    exit;
}


$width = 1024;
$height = 512;

$tile = imagecreatetruecolor($width, $height);
$img = imagecreatefromjpeg($img . ".jpg");
$img_width = imagesx($img) / pow(2, ($zoom));
$img_height = imagesy($img) / pow(2, ($zoom));

imagecopyresampled(
        $tile, 
        $img, 
        0, 
        0, 
        $x * $img_width, 
        $y * $img_height, 
        $width, 
        $height, 
        $img_width, 
        $img_height
        );

/* Cache tile */
imagejpeg($tile, $cache, 80);

/* Output to browser */
header("Cache-Control: private, max-age=10800, pre-check=10800");
header("Pragma: private");
header("Expires: " . date(DATE_RFC822,strtotime(" 2 day")));
header('Content-type: image/jpeg');
header('Content-length: ' . filesize($cache));
readfile($cache);

