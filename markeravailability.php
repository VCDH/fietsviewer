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
require 'functions/label_functions.php';

$qry = "SELECT SUM(UNIX_TIMESTAMP(`datetime_to`) - UNIX_TIMESTAMP(`datetime_from`) + 1), YEAR(`datetime_from`), MONTH(`datetime_from`)
FROM `";
switch ($_GET['layer']) {
    case 'rln' : $qry .= 'data_rln'; break;
    case 'waittime' : $qry .= 'data_waittime'; break;
    default: $qry .= 'data_flow';
}
$qry .= "` WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'
GROUP BY YEAR(`datetime_from`), MONTH(`datetime_from`)";

$res = mysqli_query($db['link'], $qry);
$chartjs = array();
$chartjs['labels'] = array();
$datasets[0] = array(
    'data' => array(),
    'label' => 'beschikbaarheid'
);

$month_next = 0;
$year_next = 0;
while ($row = mysqli_fetch_row($res)) {
    $year_this = $row[1];
    $month_this = $row[2];
    //add months to timeline that have no data at all
    if ($year_next != 0) {
        while (($year_next < $year_this) || ($month_next < $month_this)) {
            $chartjs['labels'][] = named_month_by_mysql_index($month_next) . ' ' . $year_next;
            $datasets[0]['data'][] = 0;
            $month_next +=1;
            if ($month_next == 13) {
                $month_next = 1;
                $year_next +=1;
            }
        }
    }
    //add this month to timeline
    $month_next = $row[2] + 1;
    $year_next = $row[1];
    if ($month_next == 13) {
        $month_next = 1;
        $year_next +=1;
    }
    $chartjs['labels'][] = named_month_by_mysql_index($month_this) . ' ' . $year_this;

    //calculate number of seconds in this month
    $seconds_total = strtotime($year_next . '-' . str_pad($month_next, 2, '0', STR_PAD_LEFT) . '-01') - strtotime($year_this . '-' . str_pad($month_this, 2, '0', STR_PAD_LEFT) . '-01');

    $datasets[0]['data'][] = round($row[0] / $seconds_total * 100, 1);
}
$chartjs['datasets'] = array_values($datasets);

header('Content-Type: application/json');
echo json_encode($chartjs);
?>