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

    //build result
    $result = array();

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
    
    $locations_with_negative_flow = array();
    foreach ($layers as $layer => $ids) {
        if ($layer == 'flow') {
            $locations_with_negative_flow[$layer] = array();
            foreach ($ids as $id) {
                
                $qry = "SELECT `id`, AVG(`flow_pos`), AVG(`flow_neg`), " . $groupby . " FROM `data_flow`
                WHERE DATE(`datetime_from`) BETWEEN '" . mysqli_real_escape_string($db['link'], $request_details['period']['1']['date-start']) . "' AND '" . mysqli_real_escape_string($db['link'], $request_details['period']['1']['date-end']) . "'
                AND TIME(`datetime_from`) BETWEEN '" . mysqli_real_escape_string($db['link'], date('H:i:s', strtotime($request_details['period']['1']['time-start']))) . "' AND '" . mysqli_real_escape_string($db['link'], date('H:i:s', strtotime($request_details['period']['1']['time-end']))) . "'
                AND DAYOFWEEK(`datetime_from`) IN (" . join(', ', $dayofweek) .")
                AND `id` = " . $id . "
                GROUP BY " . $groupby;
                $res = mysqli_query($db['link'], $qry);
                while ($row = mysqli_fetch_row($res)) {
                    //decide bin or bins for time period
                    $bin = $row[3]; //always
                    //keep track if flow_neg has to be shown in graph for this layer/id
                    if (($row[2] != null) && (!in_array($id, $locations_with_negative_flow[$layer]))) {
                        $locations_with_negative_flow[$layer][] = $id;
                    }
                    //add result to correct bin
                    $result[$bin][$layer][$id] = array(
                        'flow_pos' => (int) $row[1],
                        'flow_neg' => (int) $row[2]
                    );
                }
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
    foreach ($ids as $id) {
        //label (series)
        $qry = "SELECT `location_id` FROM `mst_flow`
        WHERE `id` = " . $id;
        $res = mysqli_query($db['link'], $qry);
        $row = mysqli_fetch_row($res);
        //datasets
        $datasets[$id.'pos'] = array(
            'data' => array(),
            'label' => $row[0].' (heen)'
        );
        if (in_array($id, $locations_with_negative_flow['flow'])) {
            $datasets[$id.'neg'] = array(
                'data' => array(),
                'label' => $row[0].' (terug)'
            );
        }
        foreach ($chartjs['labels'] as $bin) {
            $data_this_pos = $result[$bin]['flow'][$id]['flow_pos'];
            $datasets[$id.'pos']['data'][] = (empty($data_this_pos) ? null : $data_this_pos);
            if (in_array($id, $locations_with_negative_flow['flow'])) {
                $data_this_neg = $result[$bin]['flow'][$id]['flow_neg'];
                $datasets[$id.'neg']['data'][] = (empty($data_this_neg) ? null : $data_this_neg);
            }
        }
    }
    $chartjs['datasets'] = array_values($datasets);
    //convert timestamps to human readable
    for ($i = 0; $i < count($chartjs['labels']); $i++) {
        switch ($request_details['aggregate']) {
            //case 'h14' : $timestep = 15 * 60; break;
            //case 'h12' : $timestep = 30 * 60; break;
            case 'd' : $chartjs['labels'][$i] = named_week_by_mysql_index($chartjs['labels'][$i]); break;
            case 'm' : $chartjs['labels'][$i] = named_month_by_mysql_index($chartjs['labels'][$i]); break;
            case 'q' : $chartjs['labels'][$i] = 'Q' . $chartjs['labels'][$i]; break;
            case 'h' : $chartjs['labels'][$i] = $chartjs['labels'][$i] . ':00'; break;
            default: break;
        }
    }

    return json_encode($chartjs);
}
?>