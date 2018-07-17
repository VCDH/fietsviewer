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
logincheck();
require 'dbconnect.inc.php';
require 'config.inc.php';

/*
* check if any markers are selected and if they exist
* returns FALSE if there are no markers or an array with the details of the markers otherwise
*/
function checkSelectedMarkers() {
    require 'dbconnect.inc.php';
    $requestedMarkers = json_decode($_POST['markers'], TRUE);
    if ($requestedMarkers === NULL) {
        return FALSE;
    }
    $returnedMarkers = array();
    //loop layers
    foreach ($requestedMarkers as $layer => $markers) {
        //loop markers
        foreach ($markers as $marker) {
            if ($layer = 'flow') {
                $qry = "SELECT `mst_flow`.`id` as `id`, `location_id`, `address`, `description` FROM `mst_flow`
                LEFT JOIN `method_flow`
                ON `mst_flow`.`method` = `method_flow`.`name`
                WHERE `id` = '" . mysqli_real_escape_string($db['link'], $marker) . "'";
                $res = mysqli_query($db['link'], $qry);
                if (mysqli_num_rows($res)) {
                    $data = mysqli_fetch_assoc($res);
                    $data['layer'] = $layer;
                    $returnedMarkers[] = $data;
                }
            }
        }
    }
    if (!empty($returnedMarkers)) {
        return $returnedMarkers;
    }
    return FALSE;
}

/*
* process the request
* returns an array on failure or TRUE on success
*/
function validRequestCompleted() {
    $errors = array();
    //skip further processing if form is opened from map to make sure form is initially shown without errors
    if ($_POST['from'] != 'form') {
        return $errors;
    }
    //check if there are markers, may not be necessary due to checkSelectedMarkers() but we need $requestedMarkers anyways
    $requestedMarkers = json_decode($_POST['markers'], TRUE);
    if ($requestedMarkers === NULL) {
        $errors[] = 'markers';
    }
    //check analysis type
    $valid_types = array ('flow' => array ('diff', 'trend'));
    $available_types = array_intersect_key($valid_types, $requestedMarkers);
    if (empty($available_types)) {
        $errors[] = 'layer';
    }
    foreach ($available_types as $types) {
        if (in_array($_POST['type'], $types)) {
            $type = $_POST['type'];
            break;
        }
    }
    if (empty($type)) {
        $errors[] = 'type';
    }
    //TODO check date fields

    //TODO check aggregation period

    //TODO check data availability

    //

    //return value
    if (!empty($errors)) {
        return $errors;
    }
    else {
        //add to request queue
        $qry = "";
        $res = mysqli_query($db['link'], $qry);
        if ($res == TRUE) {
            return TRUE;
        }
        else {
            $errors[] = 'mysql';
        }
    }
    //return errors
    return $errors;
}


?>

<!DOCTYPE html>
<html lang="nl-nl">
<head>
	<title>fietsv&#7433;ewer - analyse maken</title>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="style.css">
	<script src="jquery/jquery-3.3.1.min.js"></script>
	<script src="request.js"></script>
</head>
<body>
	
    <?php include('menu.inc.php'); ?>
    
    
    <h1>analyse maken</h1>
    
    <?php
    //no valid markers
    $selectedmarkers = checkSelectedMarkers();
    $requestcompleted = validRequestCompleted();
    if (!$selectedmarkers) {
        ?>
        <p class="error">Geen meetlocaties geselecteerd.</p>
        <p>De meetlocaties die worden meegenomen in de analyse zijn gebaseerd op de meest recente kaartweergave: de meetlocaties die op dat moment in beeld waren, worden meegenomen in de analyse. <a href="index.php">Ga terug naar de kaart</a> en stel de kaartweergave goed in.</p>
        <?php
    }
    //request completed
    elseif ($requestcompleted === TRUE) {
        ?>
        <p class="success">Aanvraag is ingediend.</p>
        <p>De status van de aanvraag kan worden gevolgd via de pagina <a href="results.php">mijn analyses</a>.</p>
        <p>Ga naar de <a href="index.php">kaart</a> om iets anders te doen.</p>
        <?php
    }
    //request setup page
    else {
        ?>

        <form method="POST">
        <input type="hidden" name="from" value="form">
        <p>Via deze pagina kunnen analyses gemaakt worden op de data in fietsv&#7433;ewer. Aangevraagde analyses worden toegevoegd aan een wachtrij en kunnen zodra ze klaar zijn geraadpleegd worden via de pagina <a href="results.php">mijn analyses</a>. Via <a href="results.php">mijn analyses</a> kunnen de instellingen van eerder gedraaide analyses ook hergebruikt worden. Het is aanbevolen om deze pagina van boven naar beneden door te lopen, omdat sommige instellingen afhankelijk zijn van eerdere selectie.</p>
        <?php if (in_array('mysql', $requestcompleted)) {
            echo '<p class="warning">Kan aanvraag niet opslaan. Probeer het later nogmaals of meld het probleem als het zich blijft voordoen.</p>';
        } ?>

        <h2>meetlocatie selectie</h2>
        <?php if (in_array('markers', $requestcompleted)) {
            echo '<p class="warning">Geen markers geselecteerd.</p>';
        } ?>
        <p>De meetlocaties die worden meegenomen in de analyse zijn gebaseerd op de meest recente kaartweergave: de meetlocaties die op dat moment in beeld waren, worden meegenomen in de analyse. Bij twijfel, <a href="index.php">ga terug naar de kaart</a> en stel de kaartweergave goed in. De kaartlagen die zijn ingeschakeld bepalen ook welke analyses beschikbaar zijn.</p>
        <?php
        echo '<table>';
        echo '<tr><th></th><th>ID</th><th>Adres</th><th>Grootheid</th><th>Meetmethode</th></tr>';
       
        foreach ($selectedmarkers as $marker) {
            echo '<tr><td>';
            echo '<input type="checkbox" name="selectedmarkers[]" value="' . $marker['layer'] . '_' . $marker['id'] . '" checked disabled>';
            echo '</td><td>';
            echo htmlspecialchars($marker['location_id']);
            echo '</td><td>';
            echo htmlspecialchars($marker['address']);
            echo '</td><td>';
            echo htmlspecialchars($marker['layer']);
            echo '</td><td>';
            echo htmlspecialchars($marker['description']);
            echo '</td></tr>'; 
        }
        echo '</table>';
        ?>
        <input type="hidden" name="markers" value="<?php echo htmlspecialchars($_POST['markers']); ?>">

        <h2>type analyse</h2>
        <?php if (in_array('type', $requestcompleted)) {
            echo '<p class="warning">Selecteer een type analyse.</p>';
        } ?>
        <p>Kies hieronder het type analyse dat je wil maken. Dit bepaalt welke verdere instellingen nog gemaakt moeten worden. Mis je een type analyse, controleer dan <a href="index.php">op de kaart</a> of de juiste kaartlagen zijn ingeschakeld.</p>
        <dl>
            <dt><input type="radio" name="type" id="form-type-1" value="diff"> <label for="form-type-1">Verschil</label></dt>
            <dd>Bereken het verschil tussen twee perioden</dd>
            <dt><input type="radio" name="type" id="form-type-2" value="trend"> <label for="form-type-2">Trend</label></dt>
            <dd>Bereken het verloop in de tijd over de som van de geselecteerde meetpunten</dd>
        </dl>

        <h2>Periode</h2>
        <fieldset class="left">
            <legend>Onderzoeksperiode</legend>
            <fieldset>
            <legend>Datum</legend>
            <table>
            <tr><td><label for="form-date-start1">van:</label></td><td><input type="date" name="date-start1" id="form-date-start1" value="<?php echo date('Y-m-d', time() - 24*60*60); ?>" autocomplete="off" required></td></tr>
            <tr><td><label for="form-date-end1">t/m:</label></td><td><input type="date" name="date-end1" id="form-date-end1" value="<?php echo date('Y-m-d', time() - 24*60*60); ?>" autocomplete="off" required></td></tr>
            </table>
            </fieldset>
            
            <fieldset>
            <legend>Tijd dagelijks</legend>
            <table>
            <tr><td><label for="form-time-start1">van:</label></td><td><input type="time" name="time-start1" id="form-time-start1" value="06:00" autocomplete="off" required></td></tr>
            <tr><td><label for="form-time-end1">tot:</label></td><td><input type="time" name="time-end1" id="form-time-end1" value="21:59" autocomplete="off" required></td></tr>
            </table>
            </fieldset>
            
            <fieldset id="form-daysofweek1">
            <legend>Dagen van de week</legend>
            <input type="checkbox" value="2" name="daysofweek1[]" id="form-daysofweek1-2"> <label for="form-daysofweek1-2">ma</label>
            <input type="checkbox" value="3" name="daysofweek1[]" id="form-daysofweek1-3"> <label for="form-daysofweek1-3">di</label>
            <input type="checkbox" value="4" name="daysofweek1[]" id="form-daysofweek1-4"> <label for="form-daysofweek1-4">wo</label>
            <input type="checkbox" value="5" name="daysofweek1[]" id="form-daysofweek1-5"> <label for="form-daysofweek1-5">do</label>
            <input type="checkbox" value="6" name="daysofweek1[]" id="form-daysofweek1-6"> <label for="form-daysofweek1-6">vr</label>
            <input type="checkbox" value="7" name="daysofweek1[]" id="form-daysofweek1-7"> <label for="form-daysofweek1-7">za</label>
            <input type="checkbox" value="1" name="daysofweek1[]" id="form-daysofweek1-1"> <label for="form-daysofweek1-1">zo</label>
            <br><a id="form-daysofweek1-selectworkdays">werkdagen</a> - <a id="form-daysofweek1-selecttuethu">di+do</a> - <a id="form-daysofweek1-selectweekend">weekend</a> - <a id="form-daysofweek1-selectall">weekdagen</a> - <a id="form-daysofweek1-selectnone">geen</a>
            </fieldset>
        </fieldset>

        <fieldset class="left" id="form-period-select-2">
            <legend>Vergelijken met basisperiode</legend>
            <fieldset>
            <legend>Datum</legend>
            <table>
            <tr><td><label for="form-date-start2">van:</label></td><td><input type="date" name="date-start2" id="form-date-start2" value="<?php echo date('Y-m-d', time() - 24*60*60); ?>" autocomplete="off" required></td></tr>
            <tr><td><label for="form-date-end2">t/m:</label></td><td><input type="date" name="date-end2" id="form-date-end2" value="<?php echo date('Y-m-d', time() - 24*60*60); ?>" autocomplete="off" required></td></tr>
            </table>
            </fieldset>
            
            <fieldset>
            <legend>Tijd dagelijks</legend>
            <table>
            <tr><td><label for="form-time-start2">van:</label></td><td><input type="time" name="time-start2" id="form-time-start2" value="06:00" autocomplete="off" required></td></tr>
            <tr><td><label for="form-time-end2">tot:</label></td><td><input type="time" name="time-end2" id="form-time-end2" value="21:59" autocomplete="off" required></td></tr>
            </table>
            </fieldset>
            
            <fieldset id="form-daysofweek2">
            <legend>Dagen van de week</legend>
            <input type="checkbox" value="2" name="daysofweek2[]" id="form-daysofweek2-2"> <label for="form-daysofweek2-2">ma</label>
            <input type="checkbox" value="3" name="daysofweek2[]" id="form-daysofweek2-3"> <label for="form-daysofweek2-3">di</label>
            <input type="checkbox" value="4" name="daysofweek2[]" id="form-daysofweek2-4"> <label for="form-daysofweek2-4">wo</label>
            <input type="checkbox" value="5" name="daysofweek2[]" id="form-daysofweek2-5"> <label for="form-daysofweek2-5">do</label>
            <input type="checkbox" value="6" name="daysofweek2[]" id="form-daysofweek2-6"> <label for="form-daysofweek2-6">vr</label>
            <input type="checkbox" value="7" name="daysofweek2[]" id="form-daysofweek2-7"> <label for="form-daysofweek2-7">za</label>
            <input type="checkbox" value="1" name="daysofweek2[]" id="form-daysofweek2-1"> <label for="form-daysofweek2-1">zo</label>
            <br><a id="form-daysofweek2-selectworkdays">werkdagen</a> - <a id="form-daysofweek2-selecttuethu">di+do</a> - <a id="form-daysofweek2-selectweekend">weekend</a> - <a id="form-daysofweek2-selectall">weekdagen</a> - <a id="form-daysofweek2-selectnone">geen</a>
            </fieldset>
        </fieldset>
        <div class="clear"></div>

        <h2>Aggregatie</h2>
        <p>Selecteer het aggregatieniveau waarop de resultaten gepresenteerd moeten worden.</p>
        <p><label for="form-aggregate">Aggregeer data per:</label>
            <select name="aggregate" id="form-aggregate" required>
                <option value="h14">Kwartier</option>
                <option value="h12">Halfuur</option>
                <option value="h">Uur</option>
                <option value="d" selected>Dag</option>
                <option value="w">Week</option>
                <option value="m">Maand</option>
                <option value="q">Kwartaal</option>
                <option value="y">Jaar</option>
            </select>
        </p>

        <h2>Databeschikbaarheid</h2>
        <p>Indien gewenst kunnen alleen meetlocaties met een minimale op te geven databeschikbaarheid worden meegenomen.</p>
        <p><label for="form-availability">Minimale databeschikbaarheid per meetlocatie:</label> <input type="number" name="availability" id="form-availability" value="0" min="0" max="100" step="1" autocomplete="off" required>%</p>

        <h2>prioriteit</h2>
        <p>Kies hieronder met welke prioriteit de aanvraag moet worden uitgevoerd. Aanvragen met een uitgestelde prioriteit worden maximaal 24 uur in de wachtrij vastgehouden zolang er aanvragen met een normale prioriteit zijn.</p>
        <p><label for="form-priority">Prioriteit:</label>
        <select name="priority" id="form-priority" required>
            <option value="normal">Normaal</option>
            <option value="delayed">Uitgesteld</option>
        </select></p>
        <p>E-mail bij gereed: <input type="radio" name="email" id="form-email-true" value="true" checked> <label for="form-email-true">Ja</label> <input type="radio" name="email" id="form-email-false" value="false"> <label for="form-email-false">Nee</label><br>
        <span id="form-email-to-container"><label for="form-email-to">Stuur e-mail naar:</label> <input type="email" name="email-to" id="form-email-to" value=""></span></p>

        <p><input type="submit" value="Aanvraag indienen"></p>

        </form>

        <?php
    }
    ?>

</body>
</html>