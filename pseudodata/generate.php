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

/*
* script to generate pseudo data for testing purposes
*/

$mst = array(
    array('FVGDH01_K101-242', 52.06336620425858, 4.312791501913742, 124, 'trafficlight-induction', 50),
    array('FVGDH01_K101-252', 52.06297802402803, 4.312483755908603, 214, 'trafficlight-induction', 50),
    array('FVGDH01_K101-262', 52.06394549647492, 4.313620760380436, 40 , 'trafficlight-induction', 50),
    array('FVGDH01_K101-282', 52.06306064461313, 4.313843528501806, 140, 'trafficlight-induction', 50),
    array('FVGDH01_K101-822', 52.06361983198937, 4.31201965106083 , 32 , 'trafficlight-induction', 50),
    array('FVGDH01_K101-842', 52.06376152556393, 4.311823728403931, 109, 'trafficlight-induction', 50),
    array('FVGDH01_K101-862', 52.0639464625495 , 4.312108858709857, 213, 'trafficlight-induction', 50),
    array('FVGDH01_K101-882', 52.06361776906253, 4.312982904937972, 124, 'trafficlight-induction', 50),
    array('FVGDH01_K295-242', 52.06269883465167, 4.313808190186452, 138, 'trafficlight-induction', 50),
    array('FVGDH01_K295-282', 52.06269042175768, 4.314350112829912, 319, 'trafficlight-induction', 50),
    array('FVGDH01_K295-282', 52.06269042175768, 4.314350112829912, 319, 'trafficlight-induction', 50)
);

$date_start = '2018-05-01 00:00:00';
$date_end = '2018-05-31 23:59:59';
$minute_step = 15;
$file = 'flow_pseudo.csv';

$handle = fopen($file, 'wb');
fwrite($handle, 'location-id;lat;lon;heading;method;quality;period-from;period-to;time-from;time-to;per;bicycle' . PHP_EOL);

$date_now = strtotime($date_start);
$date_end = strtotime($date_end);
while ($date_now < $date_end) {
    $time_end = $date_now + $minute_step*60 - 1;
    //add entry
    foreach($mst as $ms) {
        $val = rand(0, 40);
        $line = $ms[0] . ';' . $ms[1] . ';' . $ms[2] . ';' . $ms[3] . ';' . $ms[4] . ';' . $ms[5] . ';' . date('Y-m-d', $date_now) . ';' . date('Y-m-d', $time_end) . ';' . date('H:i:s', $date_now) . ';' . date('H:i:s', $time_end) . ';' . 0 . ';' . $val . PHP_EOL;
        fwrite($handle, $line);
    }
    //update time
    $date_now += $minute_step*60;
}

fclose($handle);
?>
