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
	$qry = "SELECT `mst_flow`.`id` AS `id`, `location_id`, `flow_pos`, `flow_neg`, `data_flow`.`quality` AS `quality` FROM `mst_flow`
    LEFT JOIN `data_flow`
    ON `mst_flow`.`id` = `data_flow`.`id`
    WHERE " . bounds_to_sql($_GET['bounds']) . "
    AND CAST('" . mysqli_real_escape_string($db['link'], $_GET['date'] . ' ' . $_GET['time']) . "' AS DATETIME) BETWEEN `datetime_from` AND `datetime_to`";
	$res = mysqli_query($db['link'], $qry);
	$json = array();
	while ($data = mysqli_fetch_assoc($res)) {
        //calculate color
        $color = 0;
        if (($data['flow_pos'] + $data['flow_neg']) > 0) {
            $color = 1;
        }
        if (($data['flow_pos'] + $data['flow_neg']) > 50) {
            $color = 2;
        }
        if (($data['flow_pos'] + $data['flow_neg']) > 100) {
            $color = 3;
        }
        if (($data['flow_pos'] + $data['flow_neg']) > 150) {
            $color = 4;
        }
        //add to output
        $json[(int) $data['id']] = array(
		'val' => $data['flow_pos'] + $data['flow_neg'],
		'quality' => $data['quality'],
		'color' => $color);
	}
}

echo json_encode($json, JSON_FORCE_OBJECT);

?>