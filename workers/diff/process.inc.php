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

/*
* worker process for the diff graph
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
    $dayofweek_1 = array();
    if (is_array($request_details['period']['1']['daysofweek'])) {
        foreach ($request_details['period']['1']['daysofweek'] as $day) {
            if (is_numeric($day) && ($day >= 1) && ($day <= 7)) {
                $dayofweek_1[] = $day;
            }
        }
    }
    $dayofweek_2 = array();
    if (is_array($request_details['period']['2']['daysofweek'])) {
        foreach ($request_details['period']['2']['daysofweek'] as $day) {
            if (is_numeric($day) && ($day >= 1) && ($day <= 7)) {
                $dayofweek_2[] = $day;
            }
        }
    }

    //build Plotly JSON Chart Schema
    $json = array('data' => array(), 'layout' => array());

    //build query
    $groupby = '';
    switch ($request_details['aggregate']) {
        //case 'h14' : $timestep = 15 * 60; break;
        //case 'h12' : $timestep = 30 * 60; break;
        case 'd' : $groupby = 'WEEKDAY(`datetime_from`)'; break;
        case 'w' : $groupby = 'WEEKOFYEAR(`datetime_from`)'; break;
        case 'm' : $groupby = 'MONTH(`datetime_from`)'; break;
        case 'q' : $groupby = 'QUARTER(`datetime_from`)'; break;
        case 'y' : $groupby = 'YEAR(`datetime_from`)'; break;
        default: $groupby = 'HOUR(`datetime_from`)'; //hour
    }
    
    //first 
    foreach ($layers as $layer => $ids) {
        if ($layer == 'flow') {
            $qry_ids = array_map(function($a) { global $db; return '\'' . mysqli_real_escape_string($db['link'], $a) . '\''; }, $ids);
            $qry_ids = join(',', $qry_ids);
                
            $qry = "SELECT '1', SUM(`flow_pos`), SUM(`flow_neg`), " . $groupby . " FROM `data_flow`
            WHERE DATE(`datetime_from`) BETWEEN '" . mysqli_real_escape_string($db['link'], $request_details['period']['1']['date-start']) . "' AND '" . mysqli_real_escape_string($db['link'], $request_details['period']['1']['date-end']) . "'
            AND TIME(`datetime_from`) BETWEEN '" . mysqli_real_escape_string($db['link'], date('H:i:s', strtotime($request_details['period']['1']['time-start']))) . "' AND '" . mysqli_real_escape_string($db['link'], date('H:i:s', strtotime($request_details['period']['1']['time-end']))) . "'
            AND DAYOFWEEK(`datetime_from`) IN (" . join(', ', $dayofweek_1) .")
            AND `id` IN (" .  $qry_ids . ")
            GROUP BY " . $groupby;
            $res = mysqli_query($db['link'], $qry);
            if (mysqli_error($db['link'])) {
                write_log($qry);
                write_log(mysqli_error($db['link']));
            }
            $flow_data = array('x' => array(), 'y' => array());
            while ($row = mysqli_fetch_row($res)) {
                //always a single bin
                $flow_data['x'][] = $row[3];
                $flow_data['y'][] = (int) $row[1] + $row[2];
            }
            //add to json format
            $json['data'][] = array(
                'x' => $flow_data['x'], 
                'y' => $flow_data['y'],
                'mode' => 'lines',
                'name' => 'fietsers onderzoeksperiode'
            );
        }
        elseif ($layer == 'waittime') {
            $qry_ids = array_map(function($a) { global $db; return '\'' . mysqli_real_escape_string($db['link'], $a) . '\''; }, $ids);
            $qry_ids = join(',', $qry_ids);
                
            $qry = "SELECT '1', AVG(`avg_waittime`), MAX(`max_waittime`), SUM(`timeloss`), AVG(`greenarrival`), " . $groupby . " FROM `data_waittime`
            WHERE DATE(`datetime_from`) BETWEEN '" . mysqli_real_escape_string($db['link'], $request_details['period']['1']['date-start']) . "' AND '" . mysqli_real_escape_string($db['link'], $request_details['period']['1']['date-end']) . "'
            AND TIME(`datetime_from`) BETWEEN '" . mysqli_real_escape_string($db['link'], date('H:i:s', strtotime($request_details['period']['1']['time-start']))) . "' AND '" . mysqli_real_escape_string($db['link'], date('H:i:s', strtotime($request_details['period']['1']['time-end']))) . "'
            AND DAYOFWEEK(`datetime_from`) IN (" . join(', ', $dayofweek_1) .")
            AND `id` IN (" .  $qry_ids . ")
            GROUP BY " . $groupby;
            $res = mysqli_query($db['link'], $qry);
            if (mysqli_error($db['link'])) {
                write_log($qry);
                write_log(mysqli_error($db['link']));
            }
            $waittime_data = array('x' => array(), 'avg_waittime' => array(), 'max_waittime' => array(), 'timeloss' => array(), 'greenarrival' => array());
            while ($row = mysqli_fetch_row($res)) {
                //always a single bin
                $waittime_data['x'][] = $row[5];
                $waittime_data['avg_waittime'][] = (int) $row[1];
                $waittime_data['max_waittime'][] = (int) $row[2];
                $waittime_data['timeloss'][] = (int) $row[3];
                $waittime_data['greenarrival'][] = (int) $row[4];
            }
            //add to json format
            $json['data'][] = array(
                'x' => $waittime_data['x'], 
                'y' => $waittime_data['avg_waittime'],
                'mode' => 'bar',
                'name' => 'gem wachttijd onderzoeksperiode'
            );
            $json['data'][] = array(
                'x' => $waittime_data['x'], 
                'y' => $waittime_data['max_waittime'],
                'mode' => 'bar',
                'name' => 'max wachttijd onderzoeksperiode'
            );
            $json['data'][] = array(
                'x' => $waittime_data['x'], 
                'y' => $waittime_data['timeloss'],
                'mode' => 'bar',
                'name' => 'verliesminuten onderzoeksperiode'
            );
            $json['data'][] = array(
                'x' => $waittime_data['x'], 
                'y' => $waittime_data['greenarrival'],
                'mode' => 'bar',
                'name' => 'groenaankomst onderzoeksperiode'
            );
        }
    }
    //second 
    foreach ($layers as $layer => $ids) {
        if ($layer == 'flow') {
            $qry_ids = array_map(function($a) { global $db; return '\'' . mysqli_real_escape_string($db['link'], $a) . '\''; }, $ids);
            $qry_ids = join(',', $qry_ids);
                
            $qry = "SELECT '1', SUM(`flow_pos`), SUM(`flow_neg`), " . $groupby . " FROM `data_flow`
            WHERE DATE(`datetime_from`) BETWEEN '" . mysqli_real_escape_string($db['link'], $request_details['period']['2']['date-start']) . "' AND '" . mysqli_real_escape_string($db['link'], $request_details['period']['2']['date-end']) . "'
            AND TIME(`datetime_from`) BETWEEN '" . mysqli_real_escape_string($db['link'], date('H:i:s', strtotime($request_details['period']['2']['time-start']))) . "' AND '" . mysqli_real_escape_string($db['link'], date('H:i:s', strtotime($request_details['period']['2']['time-end']))) . "'
            AND DAYOFWEEK(`datetime_from`) IN (" . join(', ', $dayofweek_2) .")
            AND `id` IN (" .  $qry_ids . ")
            GROUP BY " . $groupby;
            $res = mysqli_query($db['link'], $qry);
            if (mysqli_error($db['link'])) {
                write_log($qry);
                write_log(mysqli_error($db['link']));
            }
            $flow_data = array('x' => array(), 'y' => array());
            while ($row = mysqli_fetch_row($res)) {
                //always a single bin
                $flow_data['x'][] = $row[3];
                $flow_data['y'][] = (int) $row[1] + $row[2];
            }
            //add to json format
            $json['data'][] = array(
                'x' => $flow_data['x'], 
                'y' => $flow_data['y'],
                'type' => 'scatter',
                'mode' => 'lines',
                'name' => 'fietsers basisperiode'
            );
        }
        elseif ($layer == 'waittime') {
            $qry_ids = array_map(function($a) { global $db; return '\'' . mysqli_real_escape_string($db['link'], $a) . '\''; }, $ids);
            $qry_ids = join(',', $qry_ids);
                
            $qry = "SELECT '1', AVG(`avg_waittime`), MAX(`max_waittime`), SUM(`timeloss`), AVG(`greenarrival`), " . $groupby . " FROM `data_waittime`
            WHERE DATE(`datetime_from`) BETWEEN '" . mysqli_real_escape_string($db['link'], $request_details['period']['2']['date-start']) . "' AND '" . mysqli_real_escape_string($db['link'], $request_details['period']['2']['date-end']) . "'
            AND TIME(`datetime_from`) BETWEEN '" . mysqli_real_escape_string($db['link'], date('H:i:s', strtotime($request_details['period']['2']['time-start']))) . "' AND '" . mysqli_real_escape_string($db['link'], date('H:i:s', strtotime($request_details['period']['2']['time-end']))) . "'
            AND DAYOFWEEK(`datetime_from`) IN (" . join(', ', $dayofweek_2) .")
            AND `id` IN (" .  $qry_ids . ")
            GROUP BY " . $groupby;
            $res = mysqli_query($db['link'], $qry);if (mysqli_error($db['link'])) {
                write_log($qry);
                write_log(mysqli_error($db['link']));
            }
            $waittime_data = array('x' => array(), 'avg_waittime' => array(), 'max_waittime' => array(), 'timeloss' => array(), 'greenarrival' => array());
            while ($row = mysqli_fetch_row($res)) {
                //always a single bin
                $waittime_data['x'][] = $row[5];
                $waittime_data['avg_waittime'][] = (int) $row[1];
                $waittime_data['max_waittime'][] = (int) $row[2];
                $waittime_data['timeloss'][] = (int) $row[3];
                $waittime_data['greenarrival'][] = (int) $row[4];
            }
            //add to json format
            $json['data'][] = array(
                'x' => $waittime_data['x'], 
                'y' => $waittime_data['avg_waittime'],
                'type' => 'bar',
                'yaxis' => 'y2',
                'name' => 'gem wachttijd basisperiode'
            );
            $json['data'][] = array(
                'x' => $waittime_data['x'], 
                'y' => $waittime_data['max_waittime'],
                'type' => 'bar',
                'yaxis' => 'y2',
                'name' => 'max wachttijd basisperiode'
            );
            $json['data'][] = array(
                'x' => $waittime_data['x'], 
                'y' => $waittime_data['timeloss'],
                'type' => 'bar',
                'yaxis' => 'y2',
                'name' => 'verliesminuten basisperiode'
            );
            $json['data'][] = array(
                'x' => $waittime_data['x'], 
                'y' => $waittime_data['greenarrival'],
                'type' => 'bar',
                'yaxis' => 'y2',
                'name' => 'groenaankomst basisperiode'
            );
        }
    }

    //layout options
    $json['layout'] = array(
        'xaxis' => array(
            'type' => 'linear',
            'showgrid' => 'true',
            'autorange' => 'true'
        ),
        'yaxis' => array(
            'type' => 'linear',
            'showgrid' => 'true',
            'autorange' => 'true'
        ),
        'yaxis2' => array(
            'type' => 'linear',
            'showgrid' => 'true',
            'autorange' => 'true',
            'side' => 'right',
            'overlaying' => 'y'
        ),
        'barmode' => 'group'
    );

    return json_encode($json);
}
?>