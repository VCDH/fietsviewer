<?php
/*
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
*/

require('dbconnect.inc.php');
require('functions/bounds_to_sql.php');


if ($_GET['layer'] == 'flow') {
	$qry = "SELECT `id`, `location_id`, `lat`, `lon`, `heading` FROM `mst_flow`
	WHERE " . bounds_to_sql($_GET['bounds']);
	$res = mysqli_query($db['link'], $qry);
	$json = array();
	while ($data = mysqli_fetch_assoc($res)) {
		$json[] = array('id' => (int) $data['id'],
		'location_id' => $data['location_id'],
		'lat' => (float) $data['lat'],
		'lon' => (float) $data['lon'],
		'heading' => (int) $data['heading']);
	}
}
elseif ($_GET['layer'] == 'rln') {
	$qry = "SELECT `id`, `location_id`, `lat`, `lon` FROM `mst_rln`
	WHERE " . bounds_to_sql($_GET['bounds']);
	$res = mysqli_query($db['link'], $qry);
	$json = array();
	while ($data = mysqli_fetch_assoc($res)) {
		$json[] = array('id' => (int) $data['id'],
		'location_id' => $data['location_id'],
		'lat' => (float) $data['lat'],
		'lon' => (float) $data['lon'],
		'heading' => 0);
	}
}



echo json_encode($json);

?>