/*
*	fietsviewer - grafische weergave van fietsdata
*   Copyright (C) 2018-2019 Gemeente Den Haag, Netherlands
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
var tileLayer = 'map-tile-osm';
var tileLayerOsm;
var tileLayerTf;
var onloadCookie;
var markers = {};
var maplayers = {
	flow: {
		name: 'Intensiteit',
		active: true
	},
	rln: {
		name: 'Rood Licht Negatie',
		active: false
	},
	waittime: {
		name: 'Wachttijd',
		active: false,
		subtypes: {
			avg_waittime: {
				name: 'Gemiddelde Wachttijd',
				active: true,
			},
			max_waittime: {
				name: 'Maximale Wachttijd',
				active: false,
			},
			timeloss: {
				name: 'Verliesminuten',
				active: false,
			},
			greenarrival: {
				name: 'Groenaankomst',
				active: false,
			}
		}
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
	},
	rln: {
		colordefault: L.icon({
			iconUrl: 'img/icon_bars_0.png',
			iconSize: [16,16],
			className: 'map-icon-flow'
		}),
		color1: L.icon({
			iconUrl: 'img/icon_bars_1.png',
			iconSize: [16,16],
			className: 'map-icon-flow',
		}),
		color2: L.icon({
			iconUrl: 'img/icon_bars_2.png',
			iconSize: [16,16],
			className: 'map-icon-flow',
		}),
		color3: L.icon({
			iconUrl: 'img/icon_bars_3.png',
			iconSize: [16,16],
			className: 'map-icon-flow',
		}),
		color4: L.icon({
			iconUrl: 'img/icon_bars_5.png',
			iconSize: [16,16],
			className: 'map-icon-flow',
		})
	},
	waittime: {
		colordefault: L.icon({
			iconUrl: 'img/icon_wait.png',
			iconSize: [16,16],
			className: 'map-icon-flow'
		}),
		color1: L.icon({
			iconUrl: 'img/icon_wait_green.png',
			iconSize: [16,16],
			className: 'map-icon-flow',
		}),
		color2: L.icon({
			iconUrl: 'img/icon_wait_yellow.png',
			iconSize: [16,16],
			className: 'map-icon-flow',
		}),
		color3: L.icon({
			iconUrl: 'img/icon_wait_orange.png',
			iconSize: [16,16],
			className: 'map-icon-flow',
		}),
		color4: L.icon({
			iconUrl: 'img/icon_wait_red.png',
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
	// define map tile layers
	tileLayerOsm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
	}).addTo(map);
	tileLayerTf = L.tileLayer('https://tile.thunderforest.com/cycle/{z}/{x}/{y}.png', {
		attribution: 'Maps &copy; <a href="http://www.thunderforest.com">Thunderforest</a>, Data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'
	}).addTo(map);
	//add tile layer
	setMapTileLayer(tileLayer);
	//modify some map controls
	map.zoomControl.setPosition('topleft');
	L.control.scale().addTo(map);
	//store map position and zoom in cookie
	map.on('load moveend', function() {
		setMapCookie();
		updateMapStyle();
		updateMapLayers();
	});
}

/*
* Get maps tileset on page load
*/
function getMapTileLayer() {
	//get map style
	if ((typeof onloadCookie !== 'undefined') && (onloadCookie[6] == 'map-tile-cycle')) {
		tileLayer = onloadCookie[6];
	}
	else {
		tileLayer = 'map-tile-osm';
	}
	//set correct radio button
	$('#' + tileLayer).prop('checked', true);
	//update map style
}

/*
* Set the map tileset
*/
function setMapTileLayer(tile_id) {
	if (tile_id == 'map-tile-cycle') {
		tileLayer = tile_id;
		map.removeLayer(tileLayerOsm);
		map.addLayer(tileLayerTf);
	}
	else {
		tileLayer = 'map-tile-osm';
		map.removeLayer(tileLayerTf);
		map.addLayer(tileLayerOsm);
	}
	updateMapStyle();
	setMapCookie();
}

/*
* Get maps style on page load
*/
function getMapStyle() {
	//get map style
	if ((typeof onloadCookie !== 'undefined') && ((onloadCookie[2] == 'map-style-grayscale') || (onloadCookie[2] == 'map-style-lighter')  || (onloadCookie[2] == 'map-style-dark') || (onloadCookie[2] == 'map-style-oldskool') || (onloadCookie[2] == 'map-style-cycle'))) {
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
	if ((style_id == 'map-style-grayscale') || (style_id == 'map-style-lighter') || (style_id == 'map-style-dark') || (style_id == 'map-style-oldskool') || (style_id == 'map-style-cycle')) {
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
	//map recolor
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
	//check if layer has entry in makers object and add it if not
	if (!markers.hasOwnProperty(layer)) {
		markers[layer] = [];
	}
	//draw new markers if they are not already drawn
	var visibleMarkerIds = [];
	$.getJSON('maplayer.php', { layer: layer, bounds: map.getBounds().toBBoxString(), filter: getCurrentlySelectedFilters() })
	.done( function(json) {
		$.each(json, function(index, v) {
			visibleMarkerIds.push(v.id);
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

		//remove markers that should not be drawn (both out of bound and as a result of filtering)
		for (var i = markers[layer].length - 1; i >= 0; i--) {
			if (visibleMarkerIds.indexOf(markers[layer][i].options.x_id) === -1) {
				markers[layer][i].remove();
				markers[layer].splice(i, 1);
				
			}
		}

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
		//remove chart element if any, because otherwise the chart won't load properly when opening a new popup without closing the old one first
		var chartelement = document.getElementById('availability-chart');
		if (chartelement) {
			chartelement.parentNode.removeChild(chartelement);
		}
		popup.setContent(json.popup).update();
		//load availability graph
		showAvailabilityGraphForPopup(layer, id);
	})
	.fail( function() {
		popup.setContent('Fout: kan gegevens niet laden').update();
	});
}

/*
* Show availability graph in marker's popup when it has opened
*/
function showAvailabilityGraphForPopup(layer, id) {
	var chart = new Chart(document.getElementById('availability-chart'), {
		type: 'line',
		options: {
			title: {
				display: true,
				text: 'Databeschikbaarheid'
			},
			responsive: false,
			scales: {
				yAxes: [{
					ticks: {
						suggestedMin: 0,
						suggestedMax: 100,
					},
					scaleLabel: {
						display: true,
						labelString: 'Databeschikbaarheid [%]'
					}
				}]
			},
			legend: {
				display: false
			}
		}
	});
	//add data points
	$.getJSON('markeravailability.php', { layer: layer, id: id })
	.done( function(json) {
		chart.data = json;
		chart.data.datasets[0].fill = false;
		chart.data.datasets[0].borderColor = '#155429';
		chart.update();
	})
	.fail( function() {
		chart.options.title.text = 'Fout: kan gegevens niet laden';
		chart.update();
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
	$.getJSON('mapdata.php', { layer: layer, subtype: getActiveLayerSubtype(layer), bounds: map.getBounds().toBBoxString(), date: $('#map-date').val(), time: $('#map-time').val(), filter: getCurrentlySelectedFilters() })
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
	Cookies.set('fietsviewer_map', [map.getCenter(), map.getZoom(), mapStyle, activeMapLayers, $('#map-date').val(), $('#map-time').val(), tileLayer], {expires: 1000});
}

/*
* draw layer GUI
*/
function drawLayerGUI() {
	$.each(maplayers, function(layer, options) {
		$('#map-layers').append('<input type="checkbox" id="map-layer-' + layer + '"><label for="map-layer-' + layer + '">' + options.name + '</label><br>');
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
		//add subtypes
		if (typeof options.subtypes !== 'undefined') {
			var ul_this = $('#map-layers').append('<ul id="map-layer-subtypecontainer-' + layer + '"></ul>').children("ul:last-child");
			$.each(options.subtypes, function(subtype, suboptions) {
				ul_this.append('<li><input type="radio" id="map-layer-subtype-' + layer + '-' + subtype + '" name="map-layer-subtype-' + layer + '"><label for="map-layer-subtype-' + layer + '-' + subtype + '" value="' + subtype + '">' + suboptions.name + '</label></li>');
				if (maplayers[layer].subtypes[subtype].active == true) {
					$('#map-layer-subtype-' + layer + subtype).prop('checked', true);
				}
			});
		}
	});
	$('#map-layers input[type=checkbox]').change( function() {
		var layer = this.id.substr(10);
		var enableState = $(this).prop('checked');
		maplayers[layer].active = enableState;
		updateMapLayers();
		setMapCookie();
	});
	$('#map-layers input[type=radio]').change( function() {
		var layersubtype = this.id.substr(18).split('-');
		var layer = layersubtype[0];
		$.each(maplayers[layer].subtypes, function(subtype, suboptions) {
			if (subtype == layersubtype[1]) {
				maplayers[layer].subtypes[subtype].active = true;
			}
			else {
				maplayers[layer].subtypes[subtype].active = false;
			}
		});
		updateMapLayers();
	});
	updateMapLayers();
	setMapCookie();
}

/*
* Get active layer subtype by layer
*/

function getActiveLayerSubtype(layer) {
	var returnval;
	if (typeof maplayers[layer].subtypes === 'undefined') {
		returnval = null;
	}
	$.each(maplayers[layer].subtypes, function(subtype, suboptions) {
		if (suboptions.active == true) {
			returnval = subtype;
		}
	});
	return returnval;
}

/*
* Add or update filter options in GUI
*/

function updateFilterOptions() {
	$.getJSON('mapfilters.php')
	.done( function(json) {
		//method
		$.each(json.mtd, function(i, val) {
			$('#filter-method').append('<input type="checkbox" name="' + val.name + '" id="filter-method-' + i + '"><label for="filter-method-' + i + '">' + val.desc + '</label><br>');
			$('#filter-method-' + i).prop('checked', true).change(function() { updateMapLayers(); });
		});
		//organisations
		$.each(json.org, function(i, val) {
			$('#filter-org').append('<input type="checkbox" id="filter-org-' + val.id + '"><label for="filter-org-' + val.id + '">' + val.name + '</label><br>');
			$('#filter-org-' + val.id).prop('checked', true).change(function() { updateMapLayers(); });
		});
		//datasets
		$.each(json.set, function(i, val) {
			$('#filter-set').append('<input type="checkbox" id="filter-set-' + val.id + '"><label for="filter-set-' + val.id + '" title="' + val.prefix + ' - ' + val.desc + '">' + val.name + '</label><br>');
			$('#filter-set-' + val.id).prop('checked', true).change(function() { updateMapLayers(); });
		});
	});
}

/*
* get currently selected filter options for passing to data requests
*/
function getCurrentlySelectedFilters() {
	var filters = {mtd: [], org: [], set: []};
	//get methods
	$.each($('#filter-method input'), function() {
		if ($(this).prop('checked')) {
			filters.mtd.push($(this).prop('name'));
		}
	});
	//get organisations
	$.each($('#filter-org input'), function() {
		if ($(this).prop('checked')) {
			filters.org.push($(this).prop('id').substr(11));
		}
	});
	//get datasets
	$.each($('#filter-set input'), function() {
		if ($(this).prop('checked')) {
			filters.set.push($(this).prop('id').substr(11));
		}
	});
	return JSON.stringify(filters);
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
	setMapCookie();
}

/*
* handle posting map bounds to data request page
*/
function openDataRequestPage() {
	//get map layers and IDs
	var activeMarkers= {};
	//loop active layers
	$.each(maplayers, function(layer, options) {
		if ((options.active == true) && markers.hasOwnProperty(layer)) {
			//check if layer has entry in activeMarkers object and add it if not
			if (!activeMarkers.hasOwnProperty(layer)) {
				activeMarkers[layer] = [];
			}
			//find visible markers
			for (var i = 0; i < markers[layer].length; i++) {
				var id = markers[layer][i].options.x_id;
				activeMarkers[layer].push(id);
			}
		}
	});
	//insert form and post it
	var form = document.createElement('form');
	form.method = 'post';
	form.action = 'request.php';
	var input = document.createElement('input');
	input.name = 'markers';
	input.value = JSON.stringify(activeMarkers);
	form.appendChild(input);
	form.style.display = "none";
	document.body.appendChild(form);
	form.submit();
}

/*
* document.ready
*/
$(function() {
	//load filter options
	updateFilterOptions();
	onloadCookie = Cookies.getJSON('fietsviewer_map');
	//get date from cookie
	if ((typeof onloadCookie !== 'undefined') && (typeof onloadCookie[4] !== 'undefined') && (typeof onloadCookie[5] !== 'undefined')) {
		$('#map-date').val(onloadCookie[4]);
		$('#map-time').val(onloadCookie[5]);	
	}
	//initialize map
	getMapTileLayer();
	initMap();
	getMapStyle();
	//handle to change map tileLayer
	$('#map-tile input').change( function() {
		setMapTileLayer(this.id);
	});
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
	//handle posting map bounds to data request page
	$('#menu-top-bar a[href="request.php"]').click( function (event) {
		event.preventDefault();
		openDataRequestPage();
	});
	//load accordion for side menu
	$('#map-options-container').accordion({
		heightStyle: 'content',
		active: 2
	});
});
