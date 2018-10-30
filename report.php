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

    echo '<h1>fietsv&#7433;ewer - rapport</h1>';
    
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
        $data = mysqli_fetch_assoc($res);
        $request_details = json_decode($data['request_details'], TRUE);
        //report details
        echo '<table>';
        echo '<tr><td>Rapport titel</td><td>' . htmlspecialchars($data['report_name']) . '</td></tr>';
        echo '<tr><td>Aangevraagd door</td><td>' . htmlspecialchars($data['username']) . '</td></tr>';
        echo '<tr><td>Op datum</td><td>' . htmlspecialchars($data['date']) . '</td></tr>';
        echo '</table>';
        if (!empty($data['process_error'])) {
            echo '<p class="warning">De volgende fouten zijn aangetroffen bij het genereren van dit rapport: ' . htmlspecialchars($data['process_error']) . '.</p>';
        }
        else {
            //include worker
            $worker = 'workers/' . $data['worker'] . '/report.inc.php';
            if (file_exists($worker)) {
                include $worker;

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
                            if ($layer == 'flow') {
                                $qry ="SELECT `location_id`, `address`, `method_flow`.`description`, `quality`
                                FROM `mst_flow`
                                LEFT JOIN `method_flow`
                                ON `mst_flow`.`method` = `method_flow`.`name`
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