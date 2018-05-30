<?php
/*
 	fietsviewer - grafische weergave van fietsdata
    Copyright (C) 2018 Jasper Vries, Gemeente Den Haag
 
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

if ($_GET['layer'] == 'flow') {
	$qry = "SELECT `id`, `location_id`, `address`, `lat`, `lon`, `heading`, `description`, `quality` FROM `mst_flow`
	LEFT JOIN `method_flow` ON `mst_flow`.`method` = `method_flow`.`name`
	WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'";
	$res = mysqli_query($db['link'], $qry);
	$json = array();
	if ($data = mysqli_fetch_assoc($res)) {
		$json['popup'] = '<table>
		<tr><td>ID:</td><td>' . htmlspecialchars($data['location_id']) . '</td></tr>
		<tr><td>Adres:</td><td>' . htmlspecialchars($data['address']) . '</td></tr>
		<tr><td>Co&ouml;rdinaten:</td><td>' . $data['lat'] . ',' . $data['lon'] . '</td></tr>
		<tr><td>Richting:</td><td>' . $data['heading'] . ' graden</td></tr>
		<tr><td>Methode:</td><td>' . htmlspecialchars($data['description']) . '</td></tr>
		<tr><td>Kwaliteit:</td><td>' . $data['quality'] . '%</td></tr>
		</table>';
	}
}

echo json_encode($json, JSON_FORCE_OBJECT);

?>