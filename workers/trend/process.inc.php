<?php
/*
 	fietsviewer - grafische weergave van fietsdata
    Copyright (C) 2018-2019 Gemeente Den Haag, Netherlands
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

/*
* worker process for the trend graph
*/
function worker_process($request_details) {
    global $db;
    require(dirname(__FILE__).'/../../dbconnect.inc.php');
    require(dirname(__FILE__).'/../../functions/label_functions.php');

    $request_details = json_decode($request_details, TRUE);
    //check markers
    $layers = array();
    if (is_array($request_details['markers'])) {
        //for each layer
        foreach ($request_details['markers'] as $layer => $ids) {
            $layer_ids = array();
            if (is_array($ids)) {
                foreach ($ids as $id) {
                    if (is_numeric($id)) {
                        $layer_ids[] = $id;
                    }
                }
            }
            $layers[$layer] = $layer_ids;
        }
    }
    $dayofweek = array();
    if (is_array($request_details['period']['1']['daysofweek'])) {
        foreach ($request_details['period']['1']['daysofweek'] as $day) {
            if (is_numeric($day) && ($day >= 1) && ($day <= 7)) {
                $dayofweek[] = $day;
            }
        }
    }
    //build time bins
    $result = array();

    //build query
    $groupby = '';
    switch ($request_details['aggregate']) {
        //case 'h14' : $timestep = 15 * 60; break;
        //case 'h12' : $timestep = 30 * 60; break;
        case 'd' : $groupby = 'DATE(`datetime_from`)'; break;
        case 'w' : $groupby = 'YEARWEEK(`datetime_from`)'; break;
        case 'm' : $groupby = 'YEAR(`datetime_from`), MONTH(`datetime_from`)'; break;
        case 'q' : $groupby = 'YEAR(`datetime_from`), QUARTER(`datetime_from`)'; break;
        case 'y' : $groupby = 'YEAR(`datetime_from`)'; break;
        default: $groupby = 'DATE(`datetime_from`), HOUR(`datetime_from`)'; //hour
    }
    
    foreach ($layers as $layer => $ids) {
        if ($layer == 'flow') {
            $ids = array_map(function($a) { global $db; return '\'' . mysqli_real_escape_string($db['link'], $a) . '\''; }, $ids);
            $ids = join(',', $ids);
                
            $qry = "SELECT `id`, SUM(`flow_pos`), SUM(`flow_neg`), " . $groupby . " FROM `data_flow`
            WHERE DATE(`datetime_from`) BETWEEN '" . mysqli_real_escape_string($db['link'], $request_details['period']['1']['date-start']) . "' AND '" . mysqli_real_escape_string($db['link'], $request_details['period']['1']['date-end']) . "'
            AND TIME(`datetime_from`) BETWEEN '" . mysqli_real_escape_string($db['link'], date('H:i:s', strtotime($request_details['period']['1']['time-start']))) . "' AND '" . mysqli_real_escape_string($db['link'], date('H:i:s', strtotime($request_details['period']['1']['time-end']))) . "'
            AND DAYOFWEEK(`datetime_from`) IN (" . join(', ', $dayofweek) .")
            AND `id` IN (" .  $ids . ")
            GROUP BY " . $groupby;
            $res = mysqli_query($db['link'], $qry);
            while ($row = mysqli_fetch_row($res)) {
                //decide bin or bins for time period
                switch ($request_details['aggregate']) {
                    //case 'h14' : $timestep = 15 * 60; break;
                    //case 'h12' : $timestep = 30 * 60; break;
                    case 'h' : 
                    case 'm' : 
                    case 'q' : $bin = $row[3].str_pad($row[4], 2, '0', STR_PAD_LEFT); break;
                    default: $bin = $row[3]; //day, week, year
                }
                //add result to correct bin
                $result[$bin][$layer] = (int) $row[1] + $row[2];
            }
        }
        if ($layer == 'waittime') {
            $ids = array_map(function($a) { global $db; return '\'' . mysqli_real_escape_string($db['link'], $a) . '\''; }, $ids);
            $ids = join(',', $ids);
                
            $qry = "SELECT `id`, AVG(`avg_waittime`), MAX(`max_waittime`), SUM(`timeloss`), AVG(`greenarrival`), " . $groupby . " FROM `data_waittime`
            WHERE DATE(`datetime_from`) BETWEEN '" . mysqli_real_escape_string($db['link'], $request_details['period']['1']['date-start']) . "' AND '" . mysqli_real_escape_string($db['link'], $request_details['period']['1']['date-end']) . "'
            AND TIME(`datetime_from`) BETWEEN '" . mysqli_real_escape_string($db['link'], date('H:i:s', strtotime($request_details['period']['1']['time-start']))) . "' AND '" . mysqli_real_escape_string($db['link'], date('H:i:s', strtotime($request_details['period']['1']['time-end']))) . "'
            AND DAYOFWEEK(`datetime_from`) IN (" . join(', ', $dayofweek) .")
            AND `id` IN (" .  $ids . ")
            GROUP BY " . $groupby;
            $res = mysqli_query($db['link'], $qry);
            while ($row = mysqli_fetch_row($res)) {
                //decide bin or bins for time period
                switch ($request_details['aggregate']) {
                    //case 'h14' : $timestep = 15 * 60; break;
                    //case 'h12' : $timestep = 30 * 60; break;
                    case 'h' : 
                    case 'm' : 
                    case 'q' : $bin = $row[5].str_pad($row[6], 2, '0', STR_PAD_LEFT); break;
                    default: $bin = $row[5]; //day, week, year
                }
                //add result to correct bin
                $result[$bin][$layer] = array(
                    1 => (int) $row[1],
                    2 => (int) $row[2],
                    3 => (int) $row[3],
                    4 => (int) $row[4]
                );
            }
        }
    }
    ksort($result);
    //build chart.js data format
    $chartjs = array();
    //labels (timestamps)
    $chartjs['labels'] = array_keys($result);
    //datasets
    $datasets = array();
    //dataset
    $datasets[0] = array(
        'data' => array(),
        'type' => 'line',
        'label' => 'fietsers',
        'yAxisID' => 'axis-count'
    );
    $datasets[1] = array(
        'data' => array(),
        'type' => 'bar',
        'label' => 'gem wachttijd',
        'yAxisID' => 'axis-seconds'
    );
    $datasets[2] = array(
        'data' => array(),
        'type' => 'bar',
        'label' => 'max wachttijd',
        'yAxisID' => 'axis-seconds'
    );
    $datasets[3] = array(
        'data' => array(),
        'type' => 'bar',
        'label' => 'verliesminuten',
        'yAxisID' => 'axis-minutes'
    );
    $datasets[4] = array(
        'data' => array(),
        'type' => 'bar',
        'label' => 'groenaankomst',
        'yAxisID' => 'axis-percent'
    );
    foreach ($chartjs['labels'] as $bin) {
        //flow
        $data_this_pos = $result[$bin]['flow'];
        $datasets[0]['data'][] = (empty($data_this_pos) ? null : $data_this_pos);
        //waittime
        for ($i = 1; $i < 5; $i++) {
            $data_this_pos = $result[$bin]['waittime'][$i];
            $datasets[$i]['data'][] = (empty($data_this_pos) ? null : $data_this_pos);
        }
    }
    $chartjs['datasets'] = array_values($datasets);
    //convert timestamps to human readable
    for ($i = 0; $i < count($chartjs['labels']); $i++) {
        switch ($request_details['aggregate']) {
            //case 'h14' : $timestep = 15 * 60; break;
            //case 'h12' : $timestep = 30 * 60; break;
            case 'd' : $chartjs['labels'][$i] = substr($chartjs['labels'][$i], 8, 2) . '-' . substr($chartjs['labels'][$i], 5, 2) . '-' . substr($chartjs['labels'][$i], 0, 4); break;
            case 'm' : $chartjs['labels'][$i] = named_month_by_mysql_index(substr($chartjs['labels'][$i], 4)) . ' ' . substr($chartjs['labels'][$i], 0, 4); break;
            case 'q' : $chartjs['labels'][$i] = 'Q' . substr($chartjs['labels'][$i], 4) . ' ' . substr($chartjs['labels'][$i], 0, 4); break;
            case 'w' : $chartjs['labels'][$i] = 'w' . substr($chartjs['labels'][$i], 4) . ' ' . substr($chartjs['labels'][$i], 0, 4); break;
            case 'h' : $chartjs['labels'][$i] = substr($chartjs['labels'][$i], 0, 10) . ' ' . substr($chartjs['labels'][$i], 10)  . ':00'; break;
            default: break;
        }
    }

    return json_encode($chartjs);
}
?>