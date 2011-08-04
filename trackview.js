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
 * @author   Mike Cochrane <mikec@mikenz.geek.nz>
 * @copyright 2011 Mike Cochrane <mikec@mikenz.geek.nz>
 *
 * @license  GNU Affero General Public License http://www.gnu.org/licenses/
 */

var panorama;
var map;

function initialize() {
    /* Map Style */
    var simplifiedMap = [
                          {
                            featureType: "transit.station",
                            elementType: "all",
                            stylers: [
                              { visibility: "off" }
                            ]
                          },{
                            featureType: "road.highway",
                            elementType: "all",
                            stylers: [
                              { visibility: "simplified" },
                              { saturation: -99 }
                            ]
                          },{
                            featureType: "road",
                            elementType: "labels",
                            stylers: [
                              { visibility: "off" }
                            ]
                          },{
                            featureType: "administrative.land_parcel",
                            elementType: "labels",
                            stylers: [
                              { visibility: "off" }
                            ]
                          },{
                            featureType: "road.arterial",
                            elementType: "all",
                            stylers: [
                              { visibility: "simplified" },
                              { saturation: -99 }
                            ]
                          },{
                            featureType: "road.local",
                            elementType: "all",
                            stylers: [
                              { visibility: "simplified" },
                              { invert_lightness: true },
                              { saturation: -99 },
                              { lightness: 62 }
                            ]
                          }
                        ];


    /* Create Map */
    var mapOptions = {
        center: new google.maps.LatLng(-36.86392, 174.75748),
        zoom: 19,
        overviewMapControl: false,
        overviewMapControlOptions: {
          opened: true
        },
        mapTypeControlOptions: {
            mapTypeIds: ["trackview", google.maps.MapTypeId.SATELLITE, google.maps.MapTypeId.TERRAIN]
        },
        mapTypeId: "trackview"
    };
    map = new google.maps.Map(document.getElementById('map_canvas'), mapOptions);
    map.mapTypes.set('trackview', new google.maps.StyledMapType(simplifiedMap, {name: "Trackview"}));

    /* Add trackview provider */
    panorama = map.getStreetView();
    panorama.registerPanoProvider(getCustomPanorama);

    /* Watch for Streetview overlay being added */
    google.maps.event.addDomListener(map.overlayMapTypes, 'insert_at', insert_at);
    google.maps.event.addDomListener(map.overlayMapTypes, 'remove_at', remove_at);

    /* Watch for the panorama links changing */
    google.maps.event.addListener(panorama, 'links_changed', links_changed);

    /* HACK BECAUSE GOOGLE FORGOT TO BUILD THIS API */
    /* Override getPanoramaByLocation to allow pegman to return custom panoramas when dropped */
    google.maps.StreetViewService['prototype'].getPanoramaByLocationOrig = google.maps.StreetViewService['prototype'].getPanoramaByLocation;
    google.maps.StreetViewService['prototype'].getPanoramaByLocation = function(point, radius, callback) {
        var SVParent = this;
        $.ajax({
          url: "http://beta.trackview.org.nz/getPanoramaByLocation.php",
          dataType: 'json',
          data: {lat: point.lat(), lng: point.lng(), radius: radius},
          success: function(data) {
              // TODO: Get trackview and google results and return the closest */
              if (data) {
                  /* Trackview panorama */
                  data.location.latLng = new google.maps.LatLng(data.location.latLng[0], data.location.latLng[1]);
                  data.tiles.tileSize = new google.maps.Size(data.tiles.tileSize[0], data.tiles.tileSize[1]);
                  data.tiles.worldSize = new google.maps.Size(data.tiles.worldSize[0], data.tiles.worldSize[1]);
                  data.tiles.getTileUrl = getTrackviewPanoTileUrl;
                  callback(data, google.maps.StreetViewStatus.OK);
              } else {
                  /* Try for Google Street View panorama instead */
                  return SVParent.getPanoramaByLocationOrig(point, radius, callback);
              }
          }
        });
    };
}

/**
 * map.overlayMapTypes.insert_at Event Handler
 */
function insert_at(number, mapOverlay) {
    if (number == 0) {
        /* Streetview overlay was added, add trackview overlay too */
        map.overlayMapTypes.insertAt(1,
            new google.maps.ImageMapType({
                getTileUrl: function(coord, zoom) {
                    var X = coord.x % (1 << zoom);  // wrap
                    return "http://beta.trackview.org.nz/overlaytile.php?output=overlay&zoom=" + zoom + "&x=" + X + "&y=" + coord.y;
                },
                tileSize: new google.maps.Size(256, 256),
                isPng: true
            })
        );
    }
}

/**
 * map.overlayMapTypes.remove_at Event Handler
 */
function remove_at(number, mapOverlay) {
    /* Steetview overlay removed, remove trackview too */
    if (map.overlayMapTypes.length) {
        map.overlayMapTypes.pop();
    }
}

/**
 * Return the url for a panorama tile
 */
function getTrackviewPanoTileUrl(pano, zoom, tileX, tileY) {
    return 'http://beta.trackview.org.nz/tile.php?pano=' + pano + '&zoom=' + zoom + '&x=' + tileX + '&y=' +tileY + '&fmt=jpg';
}

function getCustomPanorama(pano, zoom, tileX, tileY) {
    // TODO: pull these from server - hmm async?
    if (pano.substring(0, 10) != 'trackview:') {
        return null;
    }
    return {
        location: {
            pano: pano,
            description: "Basque Park",
            latLng: new google.maps.LatLng(-36.86392, 174.75748)
        },
        links: [{
          'heading': 40,
          'description' : 'Macaulay Street (Google)',
          'roadColor' : '#FF0080',
          'pano' : 'PRkP18Twk8nx2Sdlm033Nw'
        }],
        copyright: 'Imagery (c) 2011 MikeNZ',
        tiles: {
            tileSize: new google.maps.Size(1024, 512),
            worldSize: new google.maps.Size(4096, 2048),
            centerHeading: 220,
            getTileUrl: getTrackviewPanoTileUrl
        }
    };
}

/**
 * panorama.links_changed Event Handler
 * Add links to trackview panoramas from Google Street view panoramas
 */
function links_changed() {
    if (panorama.getPano().substring(0, 10) == "trackview:") {
        /* Local panorama is linked already */
        return;
    }

    /* Get trackview links to Google Street View panoramas */
    $.ajax({
        url: "http://beta.trackview.org.nz/links.php",
        dataType: 'json',
        data: {pano: panorama.getPano()},
        success: function(data) {
            for (i = 0; i < data.length; i++) {
                panorama.getLinks().push(data[i]);
            }
        }
    });
}
