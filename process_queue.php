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

require 'dbconnect.inc.php';
require 'config.inc.php';
require_once 'functions/csv_functions.php';

/*
* This script processes the file queue. It should be called periodically, e.g. via cron or some other means
* It is safeguarded against parallel execution, so it is fine to call it every minute
*/

$runningfile = substr($_SERVER['SCRIPT_NAME'], strrpos($_SERVER['SCRIPT_NAME'], '/'), strrpos($_SERVER['SCRIPT_NAME'], '.') - strlen($_SERVER['SCRIPT_NAME'])) . '.running';
$timeout = 1800; //seconds
$tmp_data_file = 'tmp_data.csv';
set_time_limit(0);

/*
* Script startup
* check if script is already running and terminate
* allowed to run if there is no running file or if there is no activity for the last $timeout minutes
*/
if (is_file($runningfile)) {
    $lastchange = file_get_contents($runningfile);
    if (!is_numeric($lastchange) || ((time() - $lastchange) > $timeout)) {
        exit;
    }
}
$lastrun = time();

function update_running_file() {
    global $runningfile;
    global $timeout;
    global $lastrun;
    //exit self if no activity for timeout period
    if ((time() - $lastrun) > $timeout) {
        unlink($runningfile);
        exit;
    }
    //otherwise update running file and lastrun time
    $lastrun = time();
    file_put_contents($runningfile, $lastrun);
}
update_running_file();

/*
* function to process uploaded CSV file
* returns an array with detailed status information on completion array( (bool) $success, (int) $num_lines_skipped, (array) $detailed_error_info)
*/
function process_uploaded_file($file, $format, $prefix, $dataset_id) {
    //open file
    $handle = fopen($file, 'rb');
    if ($handle == FALSE) {
        return 'file_open';
    }
    //get header row
    $line = fgets($handle);
    if ($line == FALSE) {
        return 'file_read';
    }
    //detect delimiter
    $delimiter = csv_delimiter_from_string($line);
    if ($delimiter == FALSE) {
        return 'delimiter';
    }
    //get column names
    $colnames = str_getcsv($line, $delimiter);
    //create a map with colum names to index
    $cols = array();
    if ($format == 'dpf-flow') {
        $cols['mst'] = array(
            'location_id' => array('locatie-id', 'location-id', 'id', 'nr'),
            'address' => array('adres', 'address'),
            'lat' => array('lat'),
            'lon' => array('lon'),
            'heading' => array('richting', 'heading', 'direction'),
            'method' => array('methode', 'method')
        );
        $cols['data'] = array (
            'quality' => array('kwaliteit', 'quality'),
            'period_from' => array('periode-van', 'period-from'),
            'period_to' => array('periode-tot', 'period-to'),
            'dayofweek' => array('weekdag', 'day-of-week'),
            'time_from' => array('tijd-van', 'time-from'),
            'time_to' => array('tijd-tot', 'time-to'),
            'per' => array('per'),
            'flow' => array('fiets', 'bicycle'),
            'flow_pos' => array('fiets-heen', 'bicycle-to'),
            'flow_neg' => array('fiets-terug', 'bicycle-from')
        );
    }
    elseif ($format == 'dpf-rln') {
        $cols['mst'] = array(
            'location_id' => array('locatie-id', 'location-id', 'id', 'nr'),
            'address' => array('adres', 'address'),
            'lat' => array('lat'),
            'lon' => array('lon'),
            'heading' => array('richting', 'heading', 'direction'),
            'method' => array('methode', 'method')
        );
        $cols['data'] = array (
            'quality' => array('kwaliteit', 'quality'),
            'period_from' => array('periode-van', 'period-from'),
            'period_to' => array('periode-tot', 'period-to'),
            'dayofweek' => array('weekdag', 'day-of-week'),
            'time_from' => array('tijd-van', 'time-from'),
            'time_to' => array('tijd-tot', 'time-to'),
            'per' => array('per'),
            'red-light-negation' => array('rood-licht-negatie', 'red-light-negation')
        );
    }
    elseif ($format == 'dpf-waittime') {
        $cols['mst'] = array(
            'location_id' => array('locatie-id', 'location-id', 'id', 'nr'),
            'address' => array('adres', 'address'),
            'lat' => array('lat'),
            'lon' => array('lon'),
            'heading' => array('richting', 'heading', 'direction'),
            'method' => array('methode', 'method')
        );
        $cols['data'] = array (
            'quality' => array('kwaliteit', 'quality'),
            'period_from' => array('periode-van', 'period-from'),
            'period_to' => array('periode-tot', 'period-to'),
            'dayofweek' => array('weekdag', 'day-of-week'),
            'time_from' => array('tijd-van', 'time-from'),
            'time_to' => array('tijd-tot', 'time-to'),
            'per' => array('per'),
            'wait-time' => array('wachttijd', 'wait-time')
        );
    }
    if (empty($cols)) {
        return 'unsupported_format';
    }
    else {
        //for each possible column, find the first available index
        foreach ($cols as $type => $indexes){
            foreach ($indexes as $index => $names) {
                $index_num = -1;
                foreach($names as $name) {
                    $res = array_search($name, $colnames);
                    if ($res !== FALSE) {
                        $index_num = $res;
                        break;
                    }
                }
                $cols[$type][$index] = $index_num;
            }
        }
    }

    //reset temp csv files
    global $tmp_data_file;
    unlink($tmp_data_file);
    $handle_data = fopen($tmp_data_file, 'wb');

    //init
    $i = 0;
    $errors = array();
    $location_ids = array();
    $location_details = array();

    //process each row
    while ($line = fgetcsv($handle, NULL, $delimiter)) {
        $i++;
        //mst
        if (($format == 'dpf-flow') || ($format == 'dpf-rln') || ($format == 'dpf-waittime')) {
            //location-id
            if (empty($line[$cols['mst']['location_id']])) {
                $errors[] = 'Invalid location-id on line ' . $i;
                continue;
            }
            //check if prefix is prefixed, otherwise prefix it
            if (substr($line[$cols['mst']['location_id']], 0, strlen($prefix)) != $prefix) {
                $line[$cols['mst']['location_id']] = $prefix . '_' . $line[$cols['mst']['location_id']];
            }
            //address, define empty string if optional field is not provided
            if ($cols['mst']['address'] != -1) {
                $line[$cols['mst']['address']] = '';
            }
            //Only Dutch latitudes and longitudes are allowed!
            //latitude check bounds
            if ((!empty($line[$cols['mst']['lat']])) && (!is_numeric ($line[$cols['mst']['lat']]) || ($line[$cols['mst']['lat']] < 50.7) || ($line[$cols['mst']['lat']] > 53.7))) {
                $errors[] = 'Invalid latitude on line ' . $i . '; must be between 50.7 and 53.7';
                continue;
            }
            //longitude check bounds
            if ((!empty($line[$cols['mst']['lon']])) && (!is_numeric ($line[$cols['mst']['lon']]) || ($line[$cols['mst']['lon']] < 3.3) || ($line[$cols['mst']['lon']] > 7.3))) {
                $errors[] = 'Invalid longitude on line ' . $i . '; must be between 3.3 and 7.3';
                continue;
            }
            //heading check bounds
            if ((!empty($line[$cols['mst']['heading']])) && (!is_numeric ($line[$cols['mst']['heading']]) || ($line[$cols['mst']['heading']] < 0) || ($line[$cols['mst']['heading']] > 360))) {
                $errors[] = 'Invalid heading on line ' . $i . '; must be between 0 and 360';
                continue;
            }
            else {
                //round heading and set 360 to 0
                $line[$cols['mst']['heading']] = round($line[$cols['mst']['heading']]);
                if ($line[$cols['mst']['heading']] == 360) {
                    $line[$cols['mst']['heading']] = 0;
                }
            }
            //check method
            $methods_allowed = array('visueel', 'visual', 'slang', 'pressure', 'radar', 'lus', 'induction', 'vri-lus', 'trafficlight-induction');
            $line[$cols['mst']['method']] = strtolower($line[$cols['mst']['method']]);
            if (!in_array($line[$cols['mst']['method']], $methods_allowed)) {
                $errors[] = 'Invalid method on line ' . $i;
                continue;
            }
            //TODO: check if lat, lon, heading and method are all given if any of them is given
            //translate method
            $line[$cols['mst']['method']] = str_replace(array('visueel', 'slang', 'lus', 'vri-lus'), array('visual', 'pressure', 'induction', 'trafficlight-induction'), $line[$cols['mst']['method']]);
        }
        //data
        if (($format == 'dpf-flow') || ($format == 'dpf-rln') || ($format == 'dpf-waittime')) {
            //quality
            if ((!empty($line[$cols['data']['quality']])) && (!is_numeric($line[$cols['data']['quality']]) || ($line[$cols['data']['quality']] < 0) || ($line[$cols['data']['quality']] > 100))) {
                $errors[] = 'Invalid quality on line ' . $i . '; must be between 0 and 100 or left blank';
                continue;
            }
            //period from/time from
            $date_from = date_create($line[$cols['data']['period_from']] . 'T' . $line[$cols['data']['time_from']], timezone_open('Europe/Amsterdam'));
            if ($date_from === FALSE) {
                $errors[] = 'Invalid date/time-from on line ' . $i;
                continue;
            }
            //period to/time to
            $date_to = date_create($line[$cols['data']['period_to']] . 'T' . $line[$cols['data']['time_to']], timezone_open('Europe/Amsterdam'));
            //if false, there may be a time set in period-to
            if ($date_to === FALSE) {
                $date_to = date_create(substr($line[$cols['data']['period_to']], 0, 10) . 'T' . $line[$cols['data']['time_to']], timezone_open('Europe/Amsterdam'));
                $date_to2 = date_create($line[$cols['data']['period_to']]);
                //if both false, there is no valid time
                if (($date_to === FALSE) || ($date_to2 === FALSE)) {
                    $errors[] = 'Invalid date/time-to on line ' . $i;
                    continue;
                }
                if ($date_to2 < $date_to) {
                    $date_to = $date_to2;
                }
            }
            //set date to UTC
            date_timezone_set($date_from, timezone_open('UTC'));
            date_timezone_set($date_to, timezone_open('UTC'));
            //TODO: dayofweek is not interpreted

            //per
            if (($line[$cols['data']['per']] != 1) || ($line[$cols['data']['per']] != 2)) {
                $line[$cols['data']['per']] = 0;
            }
            //calculate data period for data value normalization
            $data_period = 3600;
            if ($line[$cols['data']['per']] == 0) {
                $data_period = date_timestamp_get($date_to) - date_timestamp_get($date_from);
                
            }
            elseif ($line[$cols['data']['per']] == 2) {
                $data_period = 86400;
            }
        }
        if ($format == 'dpf-flow') {
            //bicycle
            if (!is_numeric($line[$cols['data']['flow']])) {
                $errors[] = 'Invalid flow on line ' . $i;
                continue;
            }
            //bicycle-to
            if (is_numeric($line[$cols['data']['flow_pos']])) {
                $flow_pos = $line[$cols['data']['flow_pos']]; 
            }
            else {
                $flow_pos = $line[$cols['data']['flow']];
            }
            //normalize flow-pos
            if (($line[$cols['data']['per']] == 0) || ($line[$cols['data']['per']] == 2)) {
                $flow_pos = $flow_pos * 3600 / $data_period;
            }
            //bicycle-from
            if (is_numeric($line[$cols['data']['flow_neg']])) {
                $flow_neg = $line[$cols['data']['flow_neg']];
                //normalize flow-neg
                if (($line[$cols['data']['per']] == 0) || ($line[$cols['data']['per']] == 2)) {
                    $flow_neg = $flow_neg * 3600 / $data_period;
                }
            }
            else {
                $flow_neg = null;
            }
        }
        elseif ($format == 'dpf-rln') {
            //red-light-negation
            if (!is_numeric($line[$cols['data']['red-light-negation']])) {
                $errors[] = 'Invalid data value on line ' . $i;
                continue;
            }
            //normalize red-light-negation
            if (($line[$cols['data']['per']] == 0) || ($line[$cols['data']['per']] == 2)) {
                $line[$cols['data']['red-light-negation']] = $line[$cols['data']['red-light-negation']] * 3600 / $data_period;
            }
        }
        elseif ($format == 'dpf-waittime') {
            //red-light-negation
            if (!is_numeric($line[$cols['data']['wait-time']])) {
                $errors[] = 'Invalid data value on line ' . $i;
                continue;
            }
            //normalize red-light-negation
            if (($line[$cols['data']['per']] == 0) || ($line[$cols['data']['per']] == 2)) {
                $line[$cols['data']['wait-time']] = $line[$cols['data']['wait-time']] * 3600 / $data_period;
            }
        }
        
        //overwrite mst details
        if (!empty($line[$cols['mst']['lat']])) {
            $location_details[$line[$cols['mst']['location_id']]] = array(
                'address' => $line[$cols['mst']['address']],
                'lat' => $line[$cols['mst']['lat']],
                'lon' => $line[$cols['mst']['lon']],
                'heading' => $line[$cols['mst']['heading']],
                'method' => $line[$cols['mst']['method']]
            );
        }
        //get location database ID
        if (!array_key_exists($line[$cols['mst']['location_id']], $location_ids)) {
            global $db;
            //if location ID, update/get database ID
            switch ($format) {
                case 'dpf-flow': $db_table_mst = 'mst_flow'; break;
                case 'dpf-rln': $db_table_mst = 'mst_rln'; break;
                case 'dpf-waittime': $db_table_mst = 'mst_waittime'; break;
                default: $db_table_mst = '';
            }
            if (array_key_exists($line[$cols['mst']['location_id']], $location_details)) {
                $qry = "INSERT INTO `" . $db_table_mst . "` 
                (`dataset_id`, `location_id`, `address`, `lat`, `lon`, `heading`, `method`) 
                VALUES (
                '" . mysqli_real_escape_string($db['link'], $dataset_id) . "',
                '" . mysqli_real_escape_string($db['link'], $line[$cols['mst']['location_id']]) . "',
                '" . mysqli_real_escape_string($db['link'], $location_details[$line[$cols['mst']['location_id']]]['address']) . "',
                '" . mysqli_real_escape_string($db['link'], $location_details[$line[$cols['mst']['location_id']]]['lat']) . "',
                '" . mysqli_real_escape_string($db['link'], $location_details[$line[$cols['mst']['location_id']]]['lon']) . "',
                '" . mysqli_real_escape_string($db['link'], $location_details[$line[$cols['mst']['location_id']]]['heading']) . "',
                '" . mysqli_real_escape_string($db['link'], $location_details[$line[$cols['mst']['location_id']]]['method']) . "'
                )
                ON DUPLICATE KEY UPDATE
                `address` = '" . mysqli_real_escape_string($db['link'], $location_details[$line[$cols['mst']['location_id']]]['address']) . "',
                `lat` = '" . mysqli_real_escape_string($db['link'], $location_details[$line[$cols['mst']['location_id']]]['lat']) . "',
                `lon`= '" . mysqli_real_escape_string($db['link'], $location_details[$line[$cols['mst']['location_id']]]['lon']) . "',
                `heading` = '" . mysqli_real_escape_string($db['link'], $location_details[$line[$cols['mst']['location_id']]]['heading']) . "',
                `method` = '" . mysqli_real_escape_string($db['link'], $location_details[$line[$cols['mst']['location_id']]]['method']) . "',
                `id` = LAST_INSERT_ID(`id`)";
                mysqli_query($db['link'], $qry);
                $insert_id = mysqli_insert_id($db['link']);
            }
            //otherwise get existing database ID
            else {
                $qry = "SELECT `id` FROM `" . $db_table_mst . "`
                WHERE `location_id` = '" . mysqli_real_escape_string($db['link'], $line[$cols['mst']['location_id']]) . "'";
                $res = mysqli_query($db['link'], $qry);
                if (mysqli_num_rows($res)) {
                    $insert_id = mysqli_fetch_row($res);
                    $insert_id = $insert_id[0];
                }
                else {
                    $insert_id = FALSE;
                }
            }
            if ($insert_id !== FALSE) {
                $location_ids[$line[$cols['mst']['location_id']]] = $insert_id;
            }
        }
        else {
            $insert_id = $location_ids[$line[$cols['mst']['location_id']]];
        }

        if ($insert_id !== FALSE) {
            //create csv for load data infile
            if ($format == 'dpf-flow') {
                $fields = array (
                    $insert_id,
                    '"' . date_format($date_from, 'Y-m-d H:i:s') . '"',
                    '"' . date_format($date_to, 'Y-m-d H:i:s') . '"',
                    $flow_pos,
                    $flow_neg,
                    $line[$cols['data']['quality']]
                );
            }
            elseif ($format == 'dpf-rln') {
                $fields = array (
                    $insert_id,
                    '"' . date_format($date_from, 'Y-m-d H:i:s') . '"',
                    '"' . date_format($date_to, 'Y-m-d H:i:s') . '"',
                    $line[$cols['data']['red-light-negation']],
                    $line[$cols['data']['quality']]
                );
            }
            elseif ($format == 'dpf-waittime') {
                $fields = array (
                    $insert_id,
                    '"' . date_format($date_from, 'Y-m-d H:i:s') . '"',
                    '"' . date_format($date_to, 'Y-m-d H:i:s') . '"',
                    $line[$cols['data']['wait-time']],
                    $line[$cols['data']['quality']]
                );
            }
            $fields = join(';', $fields) . PHP_EOL;
            fwrite($handle_data, $fields);
        }
        else {
            $errors[] = 'Invalid location-id on line ' . $i . '; no meta-information provided';
        }
    }
    //import tmp file to database
    fclose($handle_data);
    if ($format == 'dpf-flow') {
        $qry = "LOAD DATA LOCAL INFILE '" . $tmp_data_file . "'
        REPLACE
        INTO TABLE `data_flow`
        FIELDS 
            TERMINATED BY ';'
            OPTIONALLY ENCLOSED BY '\"'
        LINES
            TERMINATED BY '" . PHP_EOL . "'
        (`id`, `datetime_from`, `datetime_to`, `flow_pos`, @flow_neg, @quality)
        SET
        `flow_neg` = NULLIF(@flow_neg, ''),
        `quality` = NULLIF(@quality, '')";
    }
    elseif ($format == 'dpf-rln') {
        $qry = "LOAD DATA LOCAL INFILE '" . $tmp_data_file . "'
        REPLACE
        INTO TABLE `data_rln`
        FIELDS 
            TERMINATED BY ';'
            OPTIONALLY ENCLOSED BY '\"'
        LINES
            TERMINATED BY '" . PHP_EOL . "'
        (`id`, `datetime_from`, `datetime_to`, `red_light_negation`, @quality)
        SET
        `quality` = NULLIF(@quality, '')";
    }
    elseif ($format == 'dpf-waittime') {
        $qry = "LOAD DATA LOCAL INFILE '" . $tmp_data_file . "'
        REPLACE
        INTO TABLE `data_waittime`
        FIELDS 
            TERMINATED BY ';'
            OPTIONALLY ENCLOSED BY '\"'
        LINES
            TERMINATED BY '" . PHP_EOL . "'
        (`id`, `datetime_from`, `datetime_to`, `wait-time`, @quality)
        SET
        `quality` = NULLIF(@quality, '')";
    }
    mysqli_query($db['link'], $qry);
    $mysqli_error = mysqli_error($db['link']);
    if (!empty($mysqli_error)) {
        $errors[] = $mysqli_error;
    }
    //return status
    return($errors);
}

/*
* main loop
*/

//retrieve from database
$qry = "SELECT `upload_queue`.`id`, `upload_queue`.`md5`, `upload_queue`.`datatype`, `datasets`.`prefix`, `datasets`.`id`
FROM `upload_queue`
LEFT JOIN `datasets`
ON `upload_queue`.`dataset_id` =  `datasets`.`id`
WHERE
`upload_queue`.`processed` = 0
ORDER BY `upload_queue`.`date_create` ASC";
$res = mysqli_query($db['link'], $qry);
if (mysqli_num_rows($res)) {
    while ($row = mysqli_fetch_row($res)) {
        update_running_file();
        $process_time = time();
        //update processed time to indicate processing has started
        $qry2 = "UPDATE `upload_queue`
        SET `process_time` = 0,
        `date_lastchange` = NOW()
        WHERE `id` = ".$row[0];
        mysqli_query($db['link'], $qry2);

        //process and import uploaded file to importable format
        $file = $cfg['upload']['dir'];
        //add trailing slash if needed
        if (substr($file, -1) != '/') {
            $file .= '/';
        }
        $file .= $row[1];
        $output = process_uploaded_file($file, $row[2], $row[3], $row[4]);
        //update database with result
        If (!empty($output) && is_array($output)) {
            $output = join(PHP_EOL, $output);
        }
        $process_time = time() - $process_time;
        $qry2 = "UPDATE `upload_queue`
        SET `process_time` = " . $process_time . ",
        `processed` = 1,
        `date_lastchange` = NOW() " .
        ((!empty($output)) ? ", `process_error` = '" . $output . "' " : '') .
        "WHERE `id` = ".$row[0];
        mysqli_query($db['link'], $qry2);
        //unlink temporary file
        unlink($file);
    }
}

/*
* terminate script
*/
unlink($runningfile);
exit;
?>