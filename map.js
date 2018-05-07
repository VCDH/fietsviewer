/*
*	fietsviewer - grafische weergave van fietsdata
*   Copyright (C) 2018 Jasper Vries, Gemeente Den Haag
*
*   This program is free software: you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation, either version 3 of the License, or
*   (at your option) any later version.
*
*   This program is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

/*
* Initialize global variables
*/
var map;
var mapStyle = 'map-style-default';
var onloadCookie;

/*
* Initialize the map on page load
*/
function initMap() {
	map = L.map('map');
	//set map position from cookie, if any
	if ((typeof onloadCookie !== 'undefined') && ($.isNumeric(onloadCookie[1]))) {
		//get and use center and zoom from cookie
		map.setView(onloadCookie[0], onloadCookie[1]);
		//get map style from cookie
		setMapStyle(onloadCookie[2]);
	}
	else {
		//set initial map view
		map.setView([52.071,4.239],12);
	}
	//add tile layer
	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
	}).addTo(map);
	//modify some map controls
	map.zoomControl.setPosition('bottomright');
	L.control.scale().addTo(map);
	//store map position and zoom in cookie
	map.on('load moveend', function() {
		setMapCookie();
		updateMapStyle();
	});
}

/*
* Get maps style on page load
*/
function getMapStyle() {
	//get map style
	if ((typeof onloadCookie !== 'undefined') && ((onloadCookie[2] == 'map-style-grayscale') || (onloadCookie[2] == 'map-style-lighter') || (onloadCookie[2] == 'map-style-oldskool'))) {
		mapStyle = onloadCookie[2];
	}
	else {
		mapStyle = 'map-style-default';
	}
	//set correct radio button
	$('#' + mapStyle).prop('checked', true);
	//update map style
	updateMapStyle();
}

/*
* Set the map style and store it in the cookie
*/
function setMapStyle(style_id) {
	if ((style_id == 'map-style-grayscale') || (style_id == 'map-style-lighter') || (style_id == 'map-style-oldskool')) {
		mapStyle = style_id;
	}
	else {
		mapStyle = 'map-style-default';
	}
	setMapCookie();
}

/*
* Apply or remove a CSS style when the user changes the map style or the map
*/
function updateMapStyle() {
	$('img.leaflet-tile').removeClass('map-style-grayscale');
	$('img.leaflet-tile').removeClass('map-style-lighter');
	$('img.leaflet-tile').removeClass('map-style-oldskool');
	if ((mapStyle == 'map-style-grayscale') || (mapStyle == 'map-style-lighter') || (mapStyle == 'map-style-oldskool')) {
		$('img.leaflet-tile').addClass(mapStyle);
	}
}

/*
* Set the cookie to remember map center, zoom and style
*/
function setMapCookie() {
	Cookies.set('fietsviewer_map', [map.getCenter(), map.getZoom(), mapStyle], {expires: 1000});
}

/*
* document.ready
*/
$(function() {
	onloadCookie = Cookies.getJSON('fietsviewer_map');
	initMap();
	getMapStyle();
	//handle to change map style
	$('#map-style input').change( function() {
		setMapStyle(this.id);
		updateMapStyle();
	});
});
