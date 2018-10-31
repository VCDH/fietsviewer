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

//translate method to list

$selected_methods = array();

$filter = json_decode($_GET['filter'], TRUE);
//var_dump($filter);
if ($filter !== NULL) {
	//get methods from database
	$available_methods = array();
	$qry = "SELECT `name` FROM `method_flow`";
	$res = mysqli_query($db['link'], $qry);
	while ($data = mysqli_fetch_assoc($res)) {
		$available_methods[] = $data['name'];
	}
	$selected_methods = array_intersect($filter['mtd'], $available_methods);
	$selected_methods = array_map(function($a) { global $db; return '\'' . mysqli_real_escape_string($db['link'], $a) . '\''; }, $selected_methods);
	//get organisations from database
	$available_orgs = array();
	$qry = "SELECT `id` FROM `organisations`";
	$res = mysqli_query($db['link'], $qry);
	while ($data = mysqli_fetch_assoc($res)) {
		$available_orgs[] = $data['id'];
	}
	$available_orgs = array_intersect($filter['org'], $available_orgs);
	$available_orgs = array_map(function($a) { global $db; return '\'' . mysqli_real_escape_string($db['link'], $a) . '\''; }, $available_orgs);
	//get datasets from database
	$available_sets = array();
	$qry = "SELECT `id` FROM `datasets`";
	$res = mysqli_query($db['link'], $qry);
	while ($data = mysqli_fetch_assoc($res)) {
		$available_sets[] = $data['id'];
	}
	$available_sets = array_intersect($filter['set'], $available_sets);
	$available_sets = array_map(function($a) { global $db; return '\'' . mysqli_real_escape_string($db['link'], $a) . '\''; }, $available_sets);
}

$selected_methods = join(', ', $selected_methods);
$available_orgs = join(', ', $available_orgs);
$available_sets = join(', ', $available_sets);

if ($_GET['layer'] == 'flow') {
	//build query
	$qry = "SELECT `mst_flow`.`id` AS `id`, `location_id`, `lat`, `lon`, `heading` FROM `mst_flow`";
	if (!empty($available_orgs)) {
		$qry .= " LEFT JOIN `datasets`
		ON `datasets`.`id` = `mst_flow`.`dataset_id`";
	}
	$qry .= " WHERE " . bounds_to_sql($_GET['bounds']);
	if (!empty($available_orgs)) {
		$qry .= " AND `organisation_id` IN (" . $available_orgs . ")";
	}
	if (!empty($selected_methods)) {
		$qry .= " AND `method` IN (" . $selected_methods . ")";
	}
	if (!empty($available_sets)) {
		$qry .= " AND `dataset_id` IN (" . $available_sets . ")";
	}
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
elseif ($_GET['layer'] == 'waittime') {
	$qry = "SELECT `id`, `location_id`, `lat`, `lon` FROM `mst_waittime`
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

header('Content-Type: application/json');
echo json_encode($json);

?>