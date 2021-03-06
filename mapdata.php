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

//convert time to correct format (local time)
$datetime = $_GET['date'] . ' ' . $_GET['time'];
$datetime = date('Y-m-d H:i:s', strtotime($datetime));

if ($_GET['layer'] == 'flow') {
	$qry = "SELECT `mst_flow`.`id` AS `id`, `location_id`, `flow_pos`, `flow_neg`, `data_flow`.`quality` AS `quality` FROM `mst_flow`
    LEFT JOIN `data_flow`
    ON `mst_flow`.`id` = `data_flow`.`id`
    WHERE " . bounds_to_sql($_GET['bounds']) . "
    AND `datetime_from` < '" . mysqli_real_escape_string($db['link'], $datetime) . "'
    AND `datetime_to` >= '" . mysqli_real_escape_string($db['link'], $datetime) . "'";
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
elseif ($_GET['layer'] == 'rln') {
	$qry = "SELECT `mst_rln`.`id` AS `id`, `location_id`, `red_light_negation`, `data_rln`.`quality` AS `quality` FROM `mst_rln`
    LEFT JOIN `data_rln`
    ON `mst_rln`.`id` = `data_rln`.`id`
    WHERE " . bounds_to_sql($_GET['bounds']) . "
    AND `datetime_from` < '" . mysqli_real_escape_string($db['link'], $datetime) . "'
    AND `datetime_to` >= '" . mysqli_real_escape_string($db['link'], $datetime) . "'";
	$res = mysqli_query($db['link'], $qry);
	$json = array();
	while ($data = mysqli_fetch_assoc($res)) {
        //calculate color
        $color = 0;
        if (($data['red_light_negation']) > 0) {
            $color = 1;
        }
        if (($data['red_light_negation']) > 1) {
            $color = 2;
        }
        if (($data['red_light_negation']) > 2) {
            $color = 3;
        }
        if (($data['red_light_negation']) > 4) {
            $color = 4;
        }
        //add to output
        $json[(int) $data['id']] = array(
		'val' => $data['red_light_negation'],
		'quality' => $data['quality'],
		'color' => $color);
	}
}
elseif ($_GET['layer'] == 'waittime') {
    $color_order_inverse = FALSE;
    $select_column = 'avg_waittime';
    $color_margin_1 = 0;
    $color_margin_2 = 15;
    $color_margin_3 = 30;
    $color_margin_4 = 45;
    if ($_GET['subtype'] == 'max_waittime') {
        $select_column = 'max_waittime';
        $color_margin_1 = 0;
        $color_margin_2 = 30;
        $color_margin_3 = 60;
        $color_margin_4 = 90;
    }
    elseif ($_GET['subtype'] == 'timeloss') {
        $select_column = 'timeloss';
        $color_margin_1 = 0;
        $color_margin_2 = 30;
        $color_margin_3 = 60;
        $color_margin_4 = 90;
    }
    elseif ($_GET['subtype'] == 'greenarrival') {
        $select_column = 'greenarrival';
        $color_order_inverse = TRUE;
        $color_margin_1 = 60;
        $color_margin_2 = 40;
        $color_margin_3 = 20;
        $color_margin_4 = 0;
    }
    
    $qry = "SELECT `mst_waittime`.`id` AS `id`, `location_id`, `" . $select_column . "`, `data_waittime`.`quality` AS `quality` FROM `mst_waittime`
    LEFT JOIN `data_waittime`
    ON `mst_waittime`.`id` = `data_waittime`.`id`
    WHERE " . bounds_to_sql($_GET['bounds']) . "
    AND `datetime_from` < '" . mysqli_real_escape_string($db['link'], $datetime) . "'
    AND `datetime_to` >= '" . mysqli_real_escape_string($db['link'], $datetime) . "'";
	$res = mysqli_query($db['link'], $qry);
	$json = array();
	while ($data = mysqli_fetch_assoc($res)) {
        //calculate color
        $color = 0;
        //TODO: make this proper
        if ($color_order_inverse === TRUE) {
            if (($data[$select_column] > $color_margin_1)) {
                $color = 1;
            }
            elseif (($data[$select_column] > $color_margin_2) ) {
                $color = 2;
            }
            elseif (($data[$select_column] > $color_margin_3)) {
                $color = 3;
            }
            elseif (($data[$select_column] > $color_margin_4)) {
                $color = 4;
            }
        }
        else {
            if (($data[$select_column] > $color_margin_1)) {
                $color = 1;
            }
            if (($data[$select_column] > $color_margin_2) ) {
                $color = 2;
            }
            if (($data[$select_column] > $color_margin_3)) {
                $color = 3;
            }
            if (($data[$select_column] > $color_margin_4)) {
                $color = 4;
            }
        }
        //add to output
        $json[(int) $data['id']] = array(
		'val' => $data['waittime'],
		'quality' => $data['quality'],
		'color' => $color);
	}
}

header('Content-Type: application/json');
echo json_encode($json, JSON_FORCE_OBJECT);

?>