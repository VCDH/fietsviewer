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

if ($_GET['layer'] == 'flow') {
	$qry = "SELECT `mst_flow`.`id` AS `id`, `location_id`, `address`, `lat`, `lon`, `heading`, `description`, `flow_pos`, `flow_neg`, `t1`.`quality` AS `quality` 
	FROM `mst_flow`
	LEFT JOIN `method_flow`
	ON `mst_flow`.`method` = `method_flow`.`name`
	LEFT JOIN 
	(SELECT * FROM `data_flow` 
	WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'
	AND CAST('" . mysqli_real_escape_string($db['link'], $_GET['date'] . ' ' . $_GET['time']) . "' AS DATETIME) BETWEEN `datetime_from` AND `datetime_to`) 
	AS `t1`
    ON `mst_flow`.`id` = `t1`.`id`
	WHERE `mst_flow`.`id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'";

	$res = mysqli_query($db['link'], $qry);
	$json = array();
	if ($data = mysqli_fetch_assoc($res)) {
		$json['popup'] = '<table>
		<tr><th>ID:</th><th>' . htmlspecialchars($data['location_id']) . '</th></tr>
		<tr><td>Adres:</td><td>' . htmlspecialchars($data['address']) . '</td></tr>
		<tr><td>Co&ouml;rdinaten:</td><td>' . $data['lat'] . ',' . $data['lon'] . '</td></tr>
		<tr><td>Richting:</td><td>' . $data['heading'] . ' graden</td></tr>
		<tr><td>Methode:</td><td>' . htmlspecialchars($data['description']) . '</td></tr>
		<tr><td>Kwaliteit:</td><td>' . $data['quality'] . '%</td></tr>' .
		( ($data['flow_neg'] != null) ?
		'<tr><td>Intensiteit positief:</td><td>' . $data['flow_pos'] . (($data['flow_pos'] != null) ? ' per uur' : '') . '</td></tr>
		<tr><td>Intensiteit negatief:</td><td>' . $data['flow_neg'] . ' per uur</td></tr>'
		: ''
		) .
		'<tr><td>Intensiteit totaal:</td><td>' . (($data['flow_pos'] == null) ? 'geen data voor tijdstip' : ($data['flow_pos'] + $data['flow_neg']) . ' per uur') . '</td></tr>
		</table>';
	}
}

echo json_encode($json, JSON_FORCE_OBJECT);

?>