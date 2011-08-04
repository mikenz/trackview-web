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

ini_set('display_errors', 0);

// TODO: return cached file
// TODO: configure nginx so it returns cached file
require_once('config.php');

/* pChart library inclusions */
include("pChart2.1.1/class/pDraw.class.php");
include("pChart2.1.1/class/pImage.class.php");

/* Create map tile */
$tile = new pImage(GoogleMapUtility::TILE_SIZE, GoogleMapUtility::TILE_SIZE, null, true);

/* Google map tile to lat,lon */
$rect = GoogleMapUtility::getTileRect(
        $x = $_GET['x'],
        $y = $_GET['y'],
        $zoom = $_GET['zoom']
        );

/* Get points to display */
$db = new mysqli("localhost", $config['db_user'], $config['db_password'], $config['db_name']);
$result = $db->query('SELECT * FROM panorama');
while ($pano = $result->fetch_row()) {
    $lat = $pano[1];
    $lon = $pano[2];
    
    // TODO: eliminate links that don't pass through this tile
    if ($lon < $rect->x || $lon > $rect->x + $rect->width) {
        //continue;
    }
    if ($lat < $rect->y || $lat > $rect->y + $rect->height) {
        //continue;
    }

    $point = GoogleMapUtility::toZoomedPixelCoords($lat, $lon, $zoom);

    $point->x = $point->x - (GoogleMapUtility::TILE_SIZE * $x);
    $point->y = $point->y - (GoogleMapUtility::TILE_SIZE * $y);   

    $result = $db->query('SELECT * FROM link WHERE `from`="' . $pano[0] . '"');
    while ($link = $result->fetch_row()) {
        if (!is_null($link[3])) {
            /* Link to external panorama */
            $end = GoogleMapUtility::toZoomedPixelCoords($link[3], $link[4], $zoom);
            $end->x = $end->x - (GoogleMapUtility::TILE_SIZE * $x);
            $end->y = $end->y - (GoogleMapUtility::TILE_SIZE * $y);   
            
            // TODO: only draw lines the pass through the tile
            $tile->drawLine($point->x, $point->y, $end->x, $end->y, array('R' => 0xFF, 'G' => 0, 'B' => 0x80, 'Alpha' => 30, 'Weight' => 3));
        } else {
            /* Link to internal panorama */
            $internal = $db->query('SELECT lat, lng FROM panorama WHERE id = "' . $link[1] . '"')->fetch_row();
            //var_dump($internal);
            $end = GoogleMapUtility::toZoomedPixelCoords($internal[0], $internal[1], $zoom);
            $end->x = $end->x - (GoogleMapUtility::TILE_SIZE * $x);
            $end->y = $end->y - (GoogleMapUtility::TILE_SIZE * $y);   
        
            $tile->drawLine($point->x, $point->y, $end->x, $end->y, array('R' => 0xFF, 'G' => 0, 'B' => 0x80, 'Alpha' => 30, 'Weight' => 3));
        }
    }
}

/* Output tile */
$tile->stroke();
// TODO: cache tile
// TODO: cache headers
// TODO: 8bit png
exit;



function dickelinie($img,$start_x,$start_y,$end_x,$end_y,$color,$thickness) 
{
    $angle=(atan2(($start_y - $end_y),($end_x - $start_x))); 

    $dist_x=$thickness*(sin($angle));
    $dist_y=$thickness*(cos($angle));
    
    $p1x=ceil(($start_x + $dist_x));
    $p1y=ceil(($start_y + $dist_y));
    $p2x=ceil(($end_x + $dist_x));
    $p2y=ceil(($end_y + $dist_y));
    $p3x=ceil(($end_x - $dist_x));
    $p3y=ceil(($end_y - $dist_y));
    $p4x=ceil(($start_x - $dist_x));
    $p4y=ceil(($start_y - $dist_y));
    
    $array=array(0=>$p1x,$p1y,$p2x,$p2y,$p3x,$p3y,$p4x,$p4y);
    imagefilledpolygon ( $img, $array, (count($array)/2), $color );
}

class GoogleMapUtility {
    const TILE_SIZE = 256;

    public static function fromXYToLatLng($point,$zoom) {
        $scale = (1 << ($zoom)) * GoogleMapUtility::TILE_SIZE;
        
        return new Point(
            (int) ($normalised->x * $scale),
            (int)($normalised->y * $scale)
        );
    
        return new Point(
            $pixelCoords->x % GoogleMapUtility::TILE_SIZE, 
            $pixelCoords->y % GoogleMapUtility::TILE_SIZE
        );
    }
    
    public static function fromMercatorCoords($point) {
             $point->x *= 360; 
             $point->y = rad2deg(atan(sinh($point->y))*M_PI);
        return $point;
    }
    
    public static function getPixelOffsetInTile($lat,$lng,$zoom) {
        $pixelCoords = GoogleMapUtility::toZoomedPixelCoords($lat, $lng, $zoom);
        return new Point(
            $pixelCoords->x % GoogleMapUtility::TILE_SIZE, 
            $pixelCoords->y % GoogleMapUtility::TILE_SIZE
        );
    }

    public static function getTileRect($x,$y,$zoom) {
            $tilesAtThisZoom = 1 << $zoom;
        $lngWidth = 360.0 / $tilesAtThisZoom;
        $lng = -180 + ($x * $lngWidth);

        $latHeightMerc = 1.0 / $tilesAtThisZoom;
        $topLatMerc = $y * $latHeightMerc;
        $bottomLatMerc = $topLatMerc + $latHeightMerc;

        $bottomLat = (180 / M_PI) * ((2 * atan(exp(M_PI * 
            (1 - (2 * $bottomLatMerc))))) - (M_PI / 2));
        $topLat = (180 / M_PI) * ((2 * atan(exp(M_PI * 
            (1 - (2 * $topLatMerc))))) - (M_PI / 2));

        $latHeight = $topLat - $bottomLat;

        return new Boundary($lng, $bottomLat, $lngWidth, $latHeight);
    }

    public static function toMercatorCoords($lat, $lng) {
        if ($lng > 180) {
            $lng -= 360;
        }

        $lng /= 360;
        $lat = asinh(tan(deg2rad($lat)))/M_PI/2;
        return new Point($lng, $lat);
    }

    public static function toNormalisedMercatorCoords($point) {
        $point->x += 0.5;
        $point->y = abs($point->y-0.5);
        return $point;
    }

    public static function toTileXY($lat, $lng, $zoom) {
        $normalised = GoogleMapUtility::toNormalisedMercatorCoords(
            GoogleMapUtility::toMercatorCoords($lat, $lng)
        );
        $scale = 1 << ($zoom);
        return new Point((int)($normalised->x * $scale), (int)($normalised->y * $scale));
    }

    public static function toZoomedPixelCoords($lat, $lng, $zoom) {
        $normalised = GoogleMapUtility::toNormalisedMercatorCoords(
            GoogleMapUtility::toMercatorCoords($lat, $lng)
        );
        $scale = (1 << ($zoom)) * GoogleMapUtility::TILE_SIZE;
        return new Point(
            (int) ($normalised->x * $scale), 
            (int)($normalised->y * $scale)
        );
    }
}

class Point {
     public $x,$y;
     function __construct($x,$y) {
          $this->x = $x;
          $this->y = $y;
     }

     function __toString() {
          return "({$this->x},{$this->y})";
     }
}

class Boundary {
     public $x,$y,$width,$height;
     function __construct($x,$y,$width,$height) {
          $this->x = $x;
          $this->y = $y;
          $this->width = $width;
          $this->height = $height;
     }
     function __toString() {
          return "({$this->x},{$this->y},{$this->width},{$this->height})";
     }
}
