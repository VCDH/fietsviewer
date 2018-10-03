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

require_once 'getuserdata.fct.php';
accesscheck('results');
require 'dbconnect.inc.php';
require 'config.inc.php';

/*
* cancel a request before it is processed
*/
if ($_GET['do'] == 'cancel') {
    //check if request may be cancelled and if so remove it
    $qry = "DELETE FROM `request_queue` WHERE
    `user_id` = '" . mysqli_real_escape_string($db['link'], getuserdata('id')) . "'
    AND `processed` = 0
    AND `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'";
    $delete_result = mysqli_query($db['link'], $qry);
}

?>

<!DOCTYPE html>
<html lang="nl-nl">
<head>
	<title>fietsv&#7433;ewer - mijn analyses</title>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="style.css">
	<script src="jquery/jquery-3.3.1.min.js"></script>
	<script src="results.js"></script>
</head>
<body>
	
    <?php include('menu.inc.php'); ?>
    
    
    <h1>mijn analyses</h1>

    <?php
    //queue
    echo '<h2>in wachtrij</h2>';
    //messages
    if ($delete_result === TRUE) {
        echo '<p class="success">Aanvraag is verwijderd.</p>';
    }
    elseif ($delete_result === FALSE) {
        echo '<p class="error">Aanvraag kan niet worden verwijderd. Mogelijk bestaat de aanvraag niet meer, of is deze al in behandeling genomen.</p>';
    }
    //list
    $qry = "SELECT `id`, `name`, `worker`, `priority`, `date_create` FROM `request_queue` WHERE
    `user_id` = '" . mysqli_real_escape_string($db['link'], getuserdata('id')) . "'
    AND `processed` = 0
    ORDER BY `priority`, `date_lastchange` DESC";
    $res = mysqli_query($db['link'], $qry);
    if (mysqli_num_rows($res)) {
        
        echo '<table>';
        echo '<tr><th>Naam</th><th>Type</th><th>Prioriteit</th><th>Datum aanvraag</th><th></th><th></th></td>';
        while ($data = mysqli_fetch_assoc($res)) {
            echo '<tr><td>';
            echo htmlspecialchars($data['name']);
            echo '</td><td>';
            echo htmlspecialchars($data['worker']);
            echo '</td><td>';
            switch ($data['priority']) {
                case 1: echo 'hoog'; break;
                case 3: echo 'laag'; break;
                default: echo 'normaal';
            }
            echo '</td><td>';
            echo date('d-m-Y H:i:s', strtotime($data['date_create']));
            echo '</td><td>';
            echo '<a href="?do=cancel&amp;id=' . $data['id'] . '" class="cancelbutton">Annuleren</a>';
            echo '</td><td>';
            echo '<a href="request.php?id=' . $data['id'] . '">Hergebruiken</a>';
            echo '</td></tr>';
        }
        echo '</table>';
    }
    else {
        echo '<p>Er zijn geen aanvragen in de wachtrij. Ga naar de <a href="index.php">kaart</a> om iets leuks te doen.</p>';
    }

    //being processed
    echo '<h2>in behandeling</h2>';
    $qry = "SELECT `id`, `name`, `worker`, `priority`, `date_create` FROM `request_queue` WHERE
    `user_id` = '" . mysqli_real_escape_string($db['link'], getuserdata('id')) . "'
    AND `processed` = 1
    AND `process_time` IS NULL
    ORDER BY `date_lastchange` DESC";
    $res = mysqli_query($db['link'], $qry);
    if (mysqli_num_rows($res)) {
        echo '<table>';
        echo '<tr><th>Naam</th><th>Type</th><th>Prioriteit</th><th>Datum aanvraag</th><th></th></td>';
        while ($data = mysqli_fetch_assoc($res)) {
            echo '<tr><td>';
            echo htmlspecialchars($data['name']);
            echo '</td><td>';
            echo htmlspecialchars($data['worker']);
            echo '</td><td>';
            switch ($data['priority']) {
                case 1: echo 'hoog'; break;
                case 3: echo 'laag'; break;
                default: echo 'normaal';
            }
            echo '</td><td>';
            echo date('d-m-Y H:i:s', strtotime($data['date_create']));
            echo '</td><td>';
            echo '<a href="request.php?id=' . $data['id'] . '">Hergebruiken</a>';
            echo '</td></tr>';
        }
        echo '</table>';
    }
    else {
        echo '<p>Er zijn op dit moment geen aanvragen in behandeling. Als je nog aanvragen in de wachtrij hebt, zijn deze op dit moment nog niet aan de beurt.</p>';
    }

    //finished
    echo '<h2>gereed</h2>';
    $qry = "SELECT `id`, `name`, `worker`, `priority`, `date_create` FROM `request_queue` WHERE
    `user_id` = '" . mysqli_real_escape_string($db['link'], getuserdata('id')) . "'
    AND `processed` = 1
    AND `process_time` IS NOT NULL
    ORDER BY `date_lastchange` DESC";
    $res = mysqli_query($db['link'], $qry);
    if (mysqli_num_rows($res)) {
        echo '<table>';
        echo '<tr><th>Naam</th><th>Type</th><th>Datum aanvraag</th><th></th><th></th></td>';
        while ($data = mysqli_fetch_assoc($res)) {
            echo '<tr><td>';
            echo htmlspecialchars($data['name']);
            echo '</td><td>';
            echo htmlspecialchars($data['worker']);
            echo '</td><td>';
            echo date('d-m-Y H:i:s', strtotime($data['date_create']));
            echo '</td><td>';
            echo '<a href="report.php?id=' . $data['id'] . '">Bekijk resultaat</a>';
            echo '</td><td>';
            echo '<a href="request.php?id=' . $data['id'] . '">Hergebruiken</a>';
            echo '</td></tr>';
        }
        echo '</table>';
    }
    else {
        echo '<p>Je hebt nog geen aanvraag die gereed is.</p>';
    }

    ?>


</body>
</html>