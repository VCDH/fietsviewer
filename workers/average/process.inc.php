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
* worker process for the graph plot
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

    //build Plotly JSON Chart Schema
    $json = array('data' => array(), 'layout' => array());

    //build query
    $groupby = '';
    switch ($request_details['aggregate']) {
        //case 'h14' : $timestep = 15 * 60; break;
        //case 'h12' : $timestep = 30 * 60; break;
        case 'd' : $groupby = 'DAYOFWEEK(`datetime_from`)'; break;
        case 'w' : $groupby = 'WEEKOFYEAR(`datetime_from`)'; break;
        case 'm' : $groupby = 'MONTH(`datetime_from`)'; break;
        case 'q' : $groupby = 'QUARTER(`datetime_from`)'; break;
        case 'y' : $groupby = 'YEAR(`datetime_from`)'; break;
        default: $groupby = 'HOUR(`datetime_from`)'; //hour
    }
    
    foreach ($layers as $layer => $ids) {
        if ($layer == 'flow') {
            foreach ($ids as $id) {   
                $qry = "SELECT `id`, AVG(`flow_pos`), AVG(`flow_neg`), " . $groupby . " FROM `data_flow`
                WHERE DATE(`datetime_from`) BETWEEN '" . mysqli_real_escape_string($db['link'], $request_details['period']['1']['date-start']) . "' AND '" . mysqli_real_escape_string($db['link'], $request_details['period']['1']['date-end']) . "'
                AND TIME(`datetime_from`) BETWEEN '" . mysqli_real_escape_string($db['link'], date('H:i:s', strtotime($request_details['period']['1']['time-start']))) . "' AND '" . mysqli_real_escape_string($db['link'], date('H:i:s', strtotime($request_details['period']['1']['time-end']))) . "'
                AND DAYOFWEEK(`datetime_from`) IN (" . join(', ', $dayofweek) .")
                AND `id` = " . $id . "
                GROUP BY " . $groupby;
                $res = mysqli_query($db['link'], $qry);
                if (mysqli_error($db['link'])) {
                    write_log($qry);
                    write_log(mysqli_error($db['link']));
                }
                $flow_pos_data = array('x' => array(), 'y' => array());
                $flow_neg_data = array('x' => array(), 'y' => array());
                $draw_flow_neg = FALSE;
                while ($row = mysqli_fetch_row($res)) {
                    //decide bin or bins for time period
                    $bin = $row[3]; //always
                    $flow_pos_data['x'][] = $bin;
                    $flow_pos_data['y'][] = (int) $row[1];
                    if (!empty($row[2])) {
                        $flow_neg_data['x'][] = $bin;
                        $flow_neg_data['y'][] = (int) $row[2];
                        $draw_flow_neg = TRUE;
                    }
                }
                //get label for series
                $qry = "SELECT `location_id` FROM `mst_flow`
                WHERE `id` = " . $id;
                $res = mysqli_query($db['link'], $qry);
                $row = mysqli_fetch_row($res);
                //add to json format
                $json['data'][] = array(
                    'x' => $flow_pos_data['x'], 
                    'y' => $flow_pos_data['y'],
                    'type' => 'scatter',
                    'mode' => 'lines',
                    'name' => $row[0] . ' (fietsers heen)'
                );
                if ($draw_flow_neg == TRUE) {
                    $json['data'][] = array(
                        'x' => $flow_neg_data['x'], 
                        'y' => $flow_neg_data['y'],
                        'type' => 'scatter',
                        'mode' => 'lines',
                        'name' => $row[0] . ' (fietsers terug)'
                    );
                }
            }
        }
        elseif ($layer == 'waittime') {
            foreach ($ids as $id) {
                $qry = "SELECT `id`, AVG(`avg_waittime`), AVG(`max_waittime`), AVG(`timeloss`), AVG(`greenarrival`), " . $groupby . " FROM `data_waittime`
                WHERE DATE(`datetime_from`) BETWEEN '" . mysqli_real_escape_string($db['link'], $request_details['period']['1']['date-start']) . "' AND '" . mysqli_real_escape_string($db['link'], $request_details['period']['1']['date-end']) . "'
                AND TIME(`datetime_from`) BETWEEN '" . mysqli_real_escape_string($db['link'], date('H:i:s', strtotime($request_details['period']['1']['time-start']))) . "' AND '" . mysqli_real_escape_string($db['link'], date('H:i:s', strtotime($request_details['period']['1']['time-end']))) . "'
                AND DAYOFWEEK(`datetime_from`) IN (" . join(', ', $dayofweek) .")
                AND `id` = " . $id . "
                GROUP BY " . $groupby;
                $res = mysqli_query($db['link'], $qry);
                if (mysqli_error($db['link'])) {
                    write_log($qry);
                    write_log(mysqli_error($db['link']));
                }
                $waittime_data = array('x' => array(), 'avg_waittime' => array(), 'max_waittime' => array(), 'timeloss' => array(), 'greenarrival' => array());
                while ($row = mysqli_fetch_row($res)) {
                    //decide bin or bins for time period
                    $bin = $row[5]; //always
                    $waittime_data['x'][] = $bin;
                    $waittime_data['avg_waittime'][] = (int) $row[1];
                    $waittime_data['max_waittime'][] = (int) $row[2];
                    $waittime_data['timeloss'][] = (int) $row[3];
                    $waittime_data['greenarrival'][] = (int) $row[4];
                }
                //get label for series
                $qry = "SELECT `location_id` FROM `mst_waittime`
                WHERE `id` = " . $id;
                $res = mysqli_query($db['link'], $qry);
                $row = mysqli_fetch_row($res);
                //add to json format
                $json['data'][] = array(
                    'x' => $waittime_data['x'], 
                    'y' => $waittime_data['avg_waittime'],
                    'type' => 'bar',
                    'yaxis' => 'y2',
                    'name' => $row[0] . ' (gem wachttijd)'
                );
                $json['data'][] = array(
                    'x' => $waittime_data['x'], 
                    'y' => $waittime_data['max_waittime'],
                    'type' => 'bar',
                    'yaxis' => 'y2',
                    'name' => $row[0] . ' (max wachttijd)'
                );
                $json['data'][] = array(
                    'x' => $waittime_data['x'], 
                    'y' => $waittime_data['timeloss'],
                    'type' => 'bar',
                    'yaxis' => 'y2',
                    'name' => $row[0] . ' (verliesminuten)'
                );
                $json['data'][] = array(
                    'x' => $waittime_data['x'], 
                    'y' => $waittime_data['greenarrival'],
                    'type' => 'bar',
                    'yaxis' => 'y2',
                    'name' => $row[0] . ' (groenaankomst)'
                );
            }
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