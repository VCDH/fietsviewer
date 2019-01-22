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

//convert time to correct format (local time)
$datetime = $_GET['date'] . ' ' . $_GET['time'];
$datetime = date('Y-m-d H:i:s', strtotime($datetime));

/*
* function to convert a number to a display format or text if there is nothing to display
*/
function echo_number($val, $sign) {
	if ($val == null) {
		$val = 'geen data voor tijdstip';
	}
	else {
		if (!is_int($val)) {
			$val = number_format($val, 2, ',', '');
		}
		$val = $val . ' ' . $sign;
	}
	return $val;
}

if ($_GET['layer'] == 'flow') {
	$qry = "SELECT `mst_flow`.`id` AS `id`, `location_id`, `address`, `lat`, `lon`, `heading`, `description`, `flow_pos`, `flow_neg`, `t1`.`quality` AS `quality` 
	FROM `mst_flow`
	LEFT JOIN `method_flow`
	ON `mst_flow`.`method` = `method_flow`.`name`
	LEFT JOIN 
	(SELECT * FROM `data_flow` 
	WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'
    AND `datetime_from` < '" . mysqli_real_escape_string($db['link'], $datetime) . "'
    AND `datetime_to` >= '" . mysqli_real_escape_string($db['link'], $datetime) . "' )
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
		<tr><td>Kwaliteit:</td><td>' . echo_number($data['quality'], '%') . '</td></tr>' .
		( ($data['flow_neg'] != null) ?
		'<tr><td>Intensiteit positief:</td><td>' . echo_number($data['flow_pos'], 'per uur') . '</td></tr>
		<tr><td>Intensiteit negatief:</td><td>' . echo_number($data['flow_neg'], 'per uur') . '</td></tr>'
		: ''
		) .
		'<tr><td>Intensiteit totaal:</td><td>' . echo_number($data['flow_pos'] + $data['flow_neg'], 'per uur') . '</td></tr>
		</table>';
	}
}
elseif ($_GET['layer'] == 'rln') {
	$qry = "SELECT `mst_rln`.`id` AS `id`, `location_id`, `address`, `lat`, `lon`, `heading`, `description`, `red_light_negation`, `t1`.`quality` AS `quality` 
	FROM `mst_rln`
	LEFT JOIN `method_flow`
	ON `mst_rln`.`method` = `method_flow`.`name`
	LEFT JOIN 
	(SELECT * FROM `data_rln` 
	WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'
    AND `datetime_from` < '" . mysqli_real_escape_string($db['link'], $datetime) . "'
    AND `datetime_to` >= '" . mysqli_real_escape_string($db['link'], $datetime) . "' )
	AS `t1`
    ON `mst_rln`.`id` = `t1`.`id`
	WHERE `mst_rln`.`id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'";

	$res = mysqli_query($db['link'], $qry);
	$json = array();
	if ($data = mysqli_fetch_assoc($res)) {
		$json['popup'] = '<table>
		<tr><th>ID:</th><th>' . htmlspecialchars($data['location_id']) . '</th></tr>
		<tr><td>Adres:</td><td>' . htmlspecialchars($data['address']) . '</td></tr>
		<tr><td>Co&ouml;rdinaten:</td><td>' . $data['lat'] . ',' . $data['lon'] . '</td></tr>
		<tr><td>Richting:</td><td>' . $data['heading'] . ' graden</td></tr>
		<tr><td>Methode:</td><td>' . htmlspecialchars($data['description']) . '</td></tr>
		<tr><td>Kwaliteit:</td><td>' . echo_number($data['quality'], '%') . '</td></tr>
		<tr><td>Rood Licht Negatie:</td><td>' . echo_number($data['red_light_negation'], 'per uur') . '</td></tr>
		</table>';
	}
}
elseif ($_GET['layer'] == 'waittime') {
	$qry = "SELECT `mst_waittime`.`id` AS `id`, `location_id`, `address`, `lat`, `lon`, `heading`, `description`, `avg_waittime`, `max_waittime`, `timeloss`, `greenarrival`, `t1`.`quality` AS `quality` 
	FROM `mst_waittime`
	LEFT JOIN `method_flow`
	ON `mst_waittime`.`method` = `method_flow`.`name`
	LEFT JOIN 
	(SELECT * FROM `data_waittime` 
	WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'
    AND `datetime_from` < '" . mysqli_real_escape_string($db['link'], $datetime) . "'
    AND `datetime_to` >= '" . mysqli_real_escape_string($db['link'], $datetime) . "' )
	AS `t1`
    ON `mst_waittime`.`id` = `t1`.`id`
	WHERE `mst_waittime`.`id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'";

	$res = mysqli_query($db['link'], $qry);
	$json = array();
	if ($data = mysqli_fetch_assoc($res)) {
		$json['popup'] = '<table>
		<tr><th>ID:</th><th>' . htmlspecialchars($data['location_id']) . '</th></tr>
		<tr><td>Adres:</td><td>' . htmlspecialchars($data['address']) . '</td></tr>
		<tr><td>Co&ouml;rdinaten:</td><td>' . $data['lat'] . ',' . $data['lon'] . '</td></tr>
		<tr><td>Richting:</td><td>' . $data['heading'] . ' graden</td></tr>
		<tr><td>Methode:</td><td>' . htmlspecialchars($data['description']) . '</td></tr>
		<tr><td>Kwaliteit:</td><td>' . echo_number($data['quality'], '%') . '</td></tr>
		<tr><td>Gemiddelde wachttijd:</td><td>' . echo_number($data['avg_waittime'], 'seconden') . '</td></tr>
		<tr><td>Maximale wachttijd:</td><td>' . echo_number($data['max_waittime'], 'seconden') . '</td></tr>
		<tr><td>Verliesminuten:</td><td>' . echo_number($data['timeloss'], 'minuten') . '</td></tr>
		<tr><td>Groenaankomst:</td><td>' . echo_number($data['greenarrival'], '%') . '</td></tr>
		</table>';
	}
}

$json['popup'] .= '<p><a href="https://www.google.nl/maps/?q=' . $data['lat'] . ',' . $data['lon'] . '&amp;layer=c&cbll=' . $data['lat'] . ',' . $data['lon'] . '&amp;cbp=11,' . $data['heading'] . ',0,0,5" target="_blank">Open locatie in Google Street View&trade;</a></p>';

//availability graph
$json['popup'] .= '<canvas id="availability-chart" width="360" height="240"></canvas>';

header('Content-Type: application/json');
echo json_encode($json, JSON_FORCE_OBJECT);

?>