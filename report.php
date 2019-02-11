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
require 'functions/label_functions.php';

//TODO: place this in separate config file with the same thing from request.php
$aggregateoptions = array (
    //'h14' => 'Kwartier',
    //'h12' => 'Halfuur',
    'h' => 'Uur',
    'd' => 'Dag',
    'w' => 'Week',
    'm' => 'Maand',
    'q' => 'Kwartaal',
    'y' => 'Jaar'
);

?>

<!DOCTYPE html>
<html lang="nl-nl">
<head>
	<title>fietsv&#7433;ewer - rapport</title>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="style.css">
	<script src="jquery/jquery-3.3.1.min.js"></script>
	<script src="Chart.js/Chart.min.js"></script>
</head>
<body>
	
    <?php include('menu.inc.php'); 

    
    
    //load report by id
    $qry = "SELECT `reports`.`id` AS `report_id`, `users`.`name` AS `username`, `reports`.`name` AS `report_name`, `reports`.`worker` AS `worker`, `reports`.`process_error` AS `process_error`, `reports`.`date_create` AS `date`, `request_queue`.`request_details` AS `request_details`
    FROM `reports`
    LEFT JOIN `users`
    ON `reports`.`user_id` = `users`.`id`
    LEFT JOIN `request_queue`
    ON `reports`.`id` = `request_queue`.`id`
    WHERE `reports`.`id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'
    LIMIT 1";
    $res = mysqli_query($db['link'], $qry);
    if (mysqli_num_rows($res)) {
        $data_report = mysqli_fetch_assoc($res);
        $request_details = json_decode($data_report['request_details'], TRUE);
        //report details
        echo '<h1>fietsv&#7433;ewer - ' . htmlspecialchars($data_report['report_name']) . '</h1>';
        if (!empty($data_report['process_error'])) {
            echo '<p class="warning">De volgende fouten zijn aangetroffen bij het genereren van dit rapport: ' . htmlspecialchars($data_report['process_error']) . '.</p>';
        }
        else {
            //include worker
            $worker = 'workers/' . $data_report['worker'] . '/report.inc.php';
            $worker_config = 'workers/' . $data_report['worker'] . '/worker.json';
            if (file_exists($worker)) {
                include $worker;
                //get worker config
                $worker_config = file_get_contents($worker_config);
                $worker_config = json_decode($worker_config, TRUE);

                //list measurement sites
                echo '<h2>meetlocaties</h2>';
                if (is_array($request_details['markers'])) {
                    //for each layer
                    echo '<table>';
                    echo '<tr><th>Locatie-id</th><th>Adres</th><th>Meetmethode</th><th>Kwaliteit*</th></tr>';
                    foreach ($request_details['markers'] as $layer => $ids) {
                        if (is_array($ids)) {
                            $ids = array_map(function($a) { global $db; return '\'' . mysqli_real_escape_string($db['link'], $a) . '\''; }, $ids);
                            $ids = join(',', $ids);
                            if (in_array($layer, array('flow', 'waittime'))) {
                                $qry ="SELECT `location_id`, `address`, `method_flow`.`description`, `quality`
                                FROM `mst_" . $layer . "`
                                LEFT JOIN `method_flow`
                                ON `mst_" . $layer . "`.`method` = `method_flow`.`name`
                                WHERE `id` IN (" .  $ids . ")";
                                $res = mysqli_query($db['link'], $qry);
                                while ($row = mysqli_fetch_row($res)) {
                                    echo '<tr><td>';
                                    echo htmlspecialchars($row[0]);
                                    echo '</td><td>';
                                    echo htmlspecialchars($row[1]);
                                    echo '</td><td>';
                                    echo htmlspecialchars($row[2]);
                                    echo '</td><td>';
                                    echo htmlspecialchars($row[3]);
                                    echo '</td></tr>';
                                }
                            }
                        }
                    }
                    echo '</table>';
                    echo '<p>*) Kwaliteit is de gemiddelde kwaliteit van een meetpunt over de gehele dataset.</p>';
                }

                //list request details
                echo '<h2>rapportinstellingen</h2>';
                echo '<table>';
                echo '<tr><td>Rapport titel</td><td>' . htmlspecialchars($data_report['report_name']) . '</td></tr>';
                echo '<tr><td>Rapport type</td><td>' . htmlspecialchars($worker_config['name']) . '</td></tr>';
                for ($i = 1; $i <= $worker_config['periods']; $i++) {
                    echo '<tr><td>';
                    if ($i == 1) {
                        echo 'Onderzoeksperiode:';
                    }
                    else {
                        echo 'Vergeleken met basisperiode:';
                    }
                    echo '</td><td>';
                    echo 'Datum: van ' . $request_details['period'][$i]['date-start'] . ' t/m ' . $request_details['period'][$i]['date-end'] . '<br>';
                    echo 'Tijd dagelijks: van ' . $request_details['period'][$i]['time-start'] . ' t/m ' . $request_details['period'][$i]['time-end'] . '<br>';
                    $daysofweek = array_map('named_dayofweek_by_mysql_index', $request_details['period'][$i]['daysofweek']);
                    echo 'Dagen van de week: '. join(', ', $daysofweek);
                    echo '</<td></tr>';
                }
                echo '<tr><td>Aggregatie</td><td>' . htmlspecialchars($aggregateoptions[$request_details['aggregate']]) . '</td></tr>';
                //echo '<tr><td>Beschikbaarheid</td><td>' . htmlspecialchars($request_details['availability']) . '</td></tr>';
                echo '<tr><td>Aangevraagd door</td><td>' . htmlspecialchars($data_report['username']) . '</td></tr>';
                echo '<tr><td>Op datum</td><td>' . htmlspecialchars($data_report['date']) . '</td></tr>';
                echo '</table>';

                //link to reuse report by id
                echo '<p><a href="request.php?id=' . $data_report['report_id']. '">rapportinstellingen hergebruiken</a></p>';

            }
            else {
                echo 'No worker report';
            }
        }
    }
    else {
        echo '<p class="error">Kan geen rapport vinden met opgegeven ID.</p>';
    }  
    ?>

</body>
</html>