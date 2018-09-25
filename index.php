<!DOCTYPE html>
<!--
 	fietsviewer - grafische weergave van fietsdata
    Copyright (C) 2018 Gemeente Den Haag, Netherlands
    Developed by Jasper Vries
 
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
-->
<html lang="nl-nl">
<head>
	<title>fietsv&#7433;ewer</title>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="leaflet/leaflet.css">
	<link rel="stylesheet" type="text/css" href="style.css">
	<link rel="stylesheet" type="text/css" href="map.css">
	<script src="jquery/jquery-3.3.1.min.js"></script>
	<script src="js-cookie/js.cookie.min.js"></script>
	<script src="leaflet/leaflet.js"></script>
	<script src="Leaflet.RotatedMarker/leaflet.rotatedMarker.js"></script>
	<script src="map.js"></script>
</head>
<body>
	<div id="map"></div>
	<div id="map-options-container">
		<div id="map-tile">
			<fieldset>
			<legend>Kaartachtergrond</legend>
				<input type="radio" name="map-tile" id="map-tile-osm"><label for="map-tile-osm">OpenStreetMap</label><br>
				<input type="radio" name="map-tile" id="map-tile-cycle"><label for="map-tile-cycle">OpenCycleMap</label>
			</fieldset>
		</div>
		<div id="map-style">
			<fieldset>
			<legend>Kaartweergave</legend>
				<input type="radio" name="map-style" id="map-style-default"><label for="map-style-default">Standaard</label><br>
				<input type="radio" name="map-style" id="map-style-lighter"><label for="map-style-lighter">Lichter</label><br>
				<input type="radio" name="map-style" id="map-style-grayscale"><label for="map-style-grayscale">Grijswaarden</label><br>
				<input type="radio" name="map-style" id="map-style-dark"><label for="map-style-dark">Donker</label><br>
				<input type="radio" name="map-style" id="map-style-oldskool"><label for="map-style-oldskool">Vergeeld</label>
			</fieldset>
		</div>
		<div id="map-layers">
			<fieldset>
			<legend>Kaartlagen</legend>
			</fieldset>
		</div>
	</div>

	<div id="map-timecontrol-container">
		<ul>
			<li title="1 week eerder" id="map-timecontrol-sub-w">-1w</li>
			<li title="1 dag eerder" id="map-timecontrol-sub-d">-1d</li>
			<li title="1 uur eerder" id="map-timecontrol-sub-h">-1h</li>
			<li title="1 kwartier eerder" id="map-timecontrol-sub-q">-&frac14;h</li>
		</ul>
		<div id="map-timecontrol-input-container">
			<input type="date" id="map-date" value="<?php echo date('Y-m-d', time() - 24*60*60); ?>" autocomplete="off" required>
			<input type="time" id="map-time" value="<?php echo date('H:i'); ?>" autocomplete="off" required>
		</div>
		<ul>
			<li title="1 kwartier later" id="map-timecontrol-add-q">+&frac14;h</li>
			<li title="1 uur later" id="map-timecontrol-add-h">+1h</li>
			<li title="1 dag later" id="map-timecontrol-add-d">+1d</li>
			<li title="1 week later" id="map-timecontrol-add-w">+1w</li>
		</ul>
		<div class="clear"></div>
	</div>
	
	<?php include('menu.inc.php'); ?>
	
	<script src="browsersupport.js"></script>
</body>
</html>