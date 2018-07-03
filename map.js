/*
*	fietsviewer - grafische weergave van fietsdata
*   Copyright (C) 2018 Gemeente Den Haag, Netherlands
*   Developed by Jasper Vries
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
var markers = {};
var maplayers = {
	flow: {
		name: 'Intensiteit',
		unit: 'f/u',
		unit_full: 'fietsers/uur',
		active: true
	}
};
var icons = {
	flow: {
		colordefault: L.icon({
			iconUrl: 'img/icon_arrow.png',
			iconSize: [16,16],
			className: 'map-icon-flow'
		}),
		color1: L.icon({
			iconUrl: 'img/icon_arrow_blue-lighter.png',
			iconSize: [16,16],
			className: 'map-icon-flow',
		}),
		color2: L.icon({
			iconUrl: 'img/icon_arrow_blue.png',
			iconSize: [16,16],
			className: 'map-icon-flow',
		}),
		color3: L.icon({
			iconUrl: 'img/icon_arrow_blue-dark.png',
			iconSize: [16,16],
			className: 'map-icon-flow',
		}),
		color4: L.icon({
			iconUrl: 'img/icon_arrow_blue-darker.png',
			iconSize: [16,16],
			className: 'map-icon-flow',
		})
	}
};

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
		updateMapLayers();
	});
}

/*
* Get maps style on page load
*/
function getMapStyle() {
	//get map style
	if ((typeof onloadCookie !== 'undefined') && ((onloadCookie[2] == 'map-style-grayscale') || (onloadCookie[2] == 'map-style-lighter')  || (onloadCookie[2] == 'map-style-dark') || (onloadCookie[2] == 'map-style-oldskool'))) {
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
	if ((style_id == 'map-style-grayscale') || (style_id == 'map-style-lighter') || (style_id == 'map-style-dark') || (style_id == 'map-style-oldskool')) {
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
	$('img.leaflet-tile').removeClass('map-style-dark');
	$('img.leaflet-tile').removeClass('map-style-oldskool');
	if ((mapStyle == 'map-style-grayscale') || (mapStyle == 'map-style-lighter') ||  (mapStyle == 'map-style-dark') || (mapStyle == 'map-style-oldskool')) {
		$('img.leaflet-tile').addClass(mapStyle);
	}
}

/*
* Update map layers
*/
function updateMapLayers() {
	$.each(maplayers, function(layer, options) {
		if (options.active == true) {
			loadMarkers(layer);
		}
		else {
			unloadMarkers(layer);
		}
	});
}

/*
* Load/update markers for map layer
*/
function loadMarkers(layer) {
	var icon = L.icon({
		iconUrl: 'img/icon_arrow.png',
		iconSize: [16,16],
		className: 'map-icon-flow'
	});
	//check if layer has entry in makers object and add it if not
	if (!markers.hasOwnProperty(layer)) {
		markers[layer] = [];
	}
	//discard markers that are out of bounds
	else {
		for (var i = markers[layer].length - 1; i >= 0; i--) {
			if (!map.getBounds().contains(markers[layer][i].getLatLng())) {
				markers[layer][i].remove();
				markers[layer].splice(i, 1);
			}
		}
	}
	
	$.getJSON('maplayer.php', { layer: layer, bounds: map.getBounds().toBBoxString() })
	.done( function(json) {
		$.each(json, function(index, v) {
			//find if marker is already drawn
			var markerfound = false;
			for (var i = 0; i < markers[layer].length; i++) {
				if (markers[layer][i].options.x_id == v.id) {
					markerfound = true;
					break;
				}
			}
			//add new marker
			if (markerfound == false) {
				var marker = L.marker([v.lat, v.lon], {
					x_id: v.id,
					icon: icons[layer].colordefault,
					rotationAngle: v.heading,
					rotationOrigin: 'center',
					title: v.location_id
				}).addTo(map);
				marker.bindPopup('Laden...', { maxWidth: 500 });
				marker.on('click', function(e) {
					openMapPopup(e, layer, v.id)
				});
				markers[layer].push(marker);
			}
		});
		loadLayerData(layer);
	});
}

/*
* Load marker's popup content
*/
function openMapPopup(e, layer, id) {
	var popup = e.target.getPopup();
	$.getJSON('markerpopup.php', { layer: layer, id: id, date: $('#map-date').val(), time: $('#map-time').val() })
	.done( function(json) {
		popup.setContent(json.popup);
		popup.update();
	})
	.fail( function() {
		popup.setContent('Fout: kan gegevens niet laden');
		popup.update();
	});
}

/*
* Update all layer data without updating markers (call UpdateMapLayers() if you want both)
*/
function updateLayerData() {
	$.each(maplayers, function(layer, options) {
		if (options.active == true) {
			loadLayerData(layer);
		}
	});
}

/*
* Attach data values to the markers
*/
function loadLayerData(layer) {
	$.getJSON('layerdata.php', { layer: layer, bounds: map.getBounds().toBBoxString(), date: $('#map-date').val(), time: $('#map-time').val() })
	.done( function(json) {
		//loop markers
		if (markers.hasOwnProperty(layer)) {
			for (var i = 0; i < markers[layer].length; i++) {
				var id = markers[layer][i].options.x_id;
				if ((typeof json[id] !== 'undefined') && (json[id].color > 0)) {
					//data -> color
					markers[layer][i].setIcon(icons[layer]['color' + json[id].color]);
					//store current value
					markers[layer][i].options.x_value = json[id].val;
				}
				else {
					//no data -> grey
					markers[layer][i].setIcon(icons[layer].colordefault);
					//store current value
					markers[layer][i].options.x_value = null;
				}
			}
		}
	});
}

/*
* remove all markers for map layer
*/
function unloadMarkers(layer) {
	//check if layer has markers
	if (markers.hasOwnProperty(layer)) {
		for (var i = markers[layer].length - 1; i >= 0; i--) {
			markers[layer][i].remove();
			markers[layer].splice(i, 1);
		}
	}
}

/*
* Set the cookie to remember map center, zoom, style and active layers
*/
function setMapCookie() {
	var activeMapLayers = [];
	$.each(maplayers, function(layer, options) {
		if (options.active == true) {
			activeMapLayers.push(layer);
		}
	});
	Cookies.set('fietsviewer_map', [map.getCenter(), map.getZoom(), mapStyle, activeMapLayers], {expires: 1000});
}

/*
* draw layer GUI
*/
function drawLayerGUI() {
	$.each(maplayers, function(layer, options) {
		$('#map-layers fieldset').append('<input type="checkbox" id="map-layer-' + layer + '"><label for="map-layer-' + layer + '">' + options.name + '</label><br>');
		if (typeof onloadCookie !== 'undefined') {
			if (onloadCookie[3].indexOf(layer) >= 0) {
				maplayers[layer].active = true;
				$('#map-layer-' + layer).prop('checked', true);
			}
			else {
				maplayers[layer].active = false;
			}
		}
		else if (maplayers[layer].active == true) {
			$('#map-layer-' + layer).prop('checked', true);
		}
	});
	$('#map-layers input').change( function() {
		var layer = this.id.substr(10);
		var enableState = $(this).prop('checked');
		maplayers[layer].active = enableState;
		updateMapLayers();
		setMapCookie();
	});
	updateMapLayers();
}

/*
* Date control UI
*/
function dateControlUI(action) {
	//get date and time
	var date = $('#map-date').val();
	var time = $('#map-time').val();
	var datetime = new Date(date + ' ' + time);
	//apply calculation
	if (action == 'map-timecontrol-add-q') {
		datetime.setMinutes(datetime.getMinutes() + 15);
	}
	else if (action == 'map-timecontrol-sub-q') {
		datetime.setMinutes(datetime.getMinutes() - 15);
	}
	else if (action == 'map-timecontrol-add-h') {
		datetime.setHours(datetime.getHours() + 1);
	}
	else if (action == 'map-timecontrol-sub-h') {
		datetime.setHours(datetime.getHours() - 1);
	}
	else if (action == 'map-timecontrol-add-d') {
		datetime.setDate(datetime.getDate() + 1);
	}
	else if (action == 'map-timecontrol-sub-d') {
		datetime.setDate(datetime.getDate() - 1);
	}
	else if (action == 'map-timecontrol-add-w') {
		datetime.setDate(datetime.getDate() + 7);
	}
	else if (action == 'map-timecontrol-sub-w') {
		datetime.setDate(datetime.getDate() - 7);
	}
	//set date and time
	$('#map-date').val(datetime.getFullYear().toString().padStart(4, '0') + '-' + (datetime.getMonth() + 1).toString().padStart(2, '0') + '-' + datetime.getDate().toString().padStart(2, '0'));
	$('#map-time').val(datetime.getHours().toString().padStart(2, '0') + ':' + datetime.getMinutes().toString().padStart(2, '0'));	

	updateLayerData();
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
	drawLayerGUI();
	//date controle UI
	$('#map-timecontrol-container li').click( function () {
		dateControlUI($(this).attr('id'));
	});
	$('#map-date').change( function() {
		updateLayerData();
	});
	$('#map-time').change( function() {
		updateLayerData();
	});
});
