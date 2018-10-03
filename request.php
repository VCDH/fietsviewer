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
accesscheck('request');
require 'dbconnect.inc.php';
require 'config.inc.php';


/*
* some config
*/
$daysofweek = array (
    2 => 'ma',
    3 => 'di',
    4 => 'wo',
    5 => 'do',
    6 => 'vr',
    7 => 'za',
    1 => 'zo'
);
$aggregateoptions = array (
    'h14' => 'Kwartier',
    'h12' => 'Halfuur',
    'h' => 'Uur',
    'd' => 'Dag',
    'w' => 'Week',
    'm' => 'Maand',
    'q' => 'Kwartaal',
    'y' => 'Jaar'
);
$priorityoptions = array (
    3 => 'Laag',
    2 => 'Normaal',
    1 => 'Hoog'
);

/*
* check if any markers are selected and if they exist
* returns FALSE if there are no markers or an array with the details of the markers otherwise
*/
function checkSelectedMarkers($json_markers) {
    require 'dbconnect.inc.php';
    $requestedMarkers = json_decode($json_markers, TRUE);
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
    global $aggregateoptions;
    global $priorityoptions;
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
    $type = NULL;
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
    //check type
    if (empty($type)) {
        $errors[] = 'type';
    }
    //check name field
    if (empty($_POST['name'])) {
        $errors[] = 'name';
    }
    //check date fields
    $n = 1;
    if ($type == 'diff') {
        $n = 2;
    }
    for ($i = 1; $i <= $n; $i++) {
        //check if date fields are a date
        if (strtotime($_POST['date-start' . $i]) === FALSE) {
            $errors[] = 'date-start' . $i;
        }
        if (strtotime($_POST['date-end' . $i]) === FALSE) {
            $errors[] = 'date-end' . $i;
        }
        if ((strtotime($_POST['date-start' . $i]) - strtotime($_POST['date-end' . $i])) > 0) {
            $errors[] = 'date-end-before-start' . $i;
        }
        if (strtotime($_POST['time-start' . $i]) === FALSE) {
            $errors[] = 'time-start' . $i;
        }
        if (strtotime($_POST['time-end' . $i]) === FALSE) {
            $errors[] = 'time-end' . $i;
        }
        if (is_array($_POST['daysofweek' . $i])) {
            foreach ($_POST['daysofweek' . $i] as $val) {
                if (!is_numeric($val) || ($val < 1) || ($val > 7)) {
                    $errors[] = 'daysofweek' . $i;
                    break;
                }
            }
        }
        else {
            $errors[] = 'daysofweek' . $i;
        }
    }
    //check aggregation period
    if (!array_key_exists($_POST['aggregate'], $aggregateoptions)) {
        $errors[] = 'aggregate';
    }
    //check data availability
    if (!is_numeric($_POST['availability']) || ($_POST['availability'] < 0) || ($_POST['availability'] > 100)) {
        $errors[] = 'availability';
    }
    //check priority
    if (!array_key_exists($_POST['priority'], $priorityoptions)) {
        $errors[] = 'priority';
    }
    //check email
    if (!in_array($_POST['email'], array('true', 'false'))) {
        $errors[] = 'email';
    }
    //return value
    if (!empty($errors)) {
        return $errors;
    }
    else {
        return TRUE;
    }
    //return errors
    return $errors;
}

/*
* add the request to the queue
* returns FALSE on failure or TRUE on success
*/
function addRequestToQueue() {
    //prepare request details
    $req_details = array();
    $req_details['markers'] = $_POST['markers'];
    $req_details['aggregate'] = $_POST['aggregate'];
    $req_details['availability'] = $_POST['availability'];
    $req_details['period'] = array();
    for ($i = 1; $i <= 2; $i++) {
        $req_details['period'][$i] = array();
        $req_details['period'][$i]['date-start'] = $_POST['date-start' . $i];
        $req_details['period'][$i]['date-end'] = $_POST['date-end' . $i];
        $req_details['period'][$i]['time-start'] = $_POST['time-start' . $i];
        $req_details['period'][$i]['time-end'] = $_POST['time-end' . $i];
        $req_details['period'][$i]['daysofweek'] = $_POST['daysofweek' . $i];
    }
    $req_details = json_encode($req_details);

    //add to database
    require 'dbconnect.inc.php';
    $qry = "INSERT INTO `request_queue` SET
    `user_id` = '" . mysqli_real_escape_string($db['link'], getuserdata('id')) . "',
    `name` = '" . mysqli_real_escape_string($db['link'], $_POST['name']) . "',
    `worker` = '" . mysqli_real_escape_string($db['link'], $_POST['type']) . "',
    `request_details` = '" . mysqli_real_escape_string($db['link'], $req_details) . "',
    `priority` = '" . mysqli_real_escape_string($db['link'], $_POST['priority']) . "',
    `send_email` = '" . mysqli_real_escape_string($db['link'], (($_POST['email'] == 'true') ? '1' : '0')) . "',
    `processed` = 0,
    `date_create` = NOW(),
    `date_lastchange` = NOW()";
    return mysqli_query($db['link'], $qry);
}

/*
* prepares an array with form field data, from either submit or page requests
*/
function getValuesForForm() {
    //default values
    $data = array();
    $data['name'] = '';
    $data['markers'] = '';
    $data['selectedmarkers'] = array();
    $data['type'] = '';
    for ($i = 1; $i <= 2; $i++) {
        $data['period'][$i]['date-start'] = date('Y-m-d', time() - 24*60*60);
        $data['period'][$i]['date-end'] = date('Y-m-d', time() - 24*60*60);
        $data['period'][$i]['time-start'] = '06:00';
        $data['period'][$i]['time-end'] = '22:00';
        $data['period'][$i]['daysofweek'] = array();
    }
    $data['aggregate'] = 'd';
    $data['availability'] = 0;
    $data['priority'] = 2;
    $data['email'] = 'true';
    //form is submitted, so we should get the post data
    if ($_POST['from'] == 'form') {
        $data['name'] = htmlspecialchars($_POST['name']);
        $data['type'] = htmlspecialchars($_POST['type']);
        $data['markers'] = $_POST['markers'];
        //TODO $data['selectedmarkers'] = array();
        for ($i = 1; $i <= 2; $i++) {
            $data['period'][$i]['date-start'] = htmlspecialchars($_POST['date-start' . $i]);
            $data['period'][$i]['date-end'] = htmlspecialchars($_POST['date-end' . $i]);
            $data['period'][$i]['time-start'] = htmlspecialchars($_POST['time-start' . $i]);
            $data['period'][$i]['time-end'] = htmlspecialchars($_POST['time-end' . $i]);
            $data['period'][$i]['daysofweek'] = array();
            if (is_array($_POST['daysofweek' . $i])) {
                foreach ($_POST['daysofweek' . $i] as $val) {
                    if (is_numeric($val) && ($val >= 1) && ($val <= 7)) {
                        $data['period'][$i]['daysofweek'][] = $val;
                    }
                }
            }
        }
        $data['aggregate'] = $_POST['aggregate'];
        $data['availability'] = htmlspecialchars($_POST['availability']);
        $data['priority'] = htmlspecialchars($_POST['priority']);
        $data['email'] = htmlspecialchars($_POST['email']);
    }
    //request to reuse previous request
    elseif (is_numeric($_GET['id'])) {
        //select from db
        require 'dbconnect.inc.php';
        $qry = "SELECT `name`, `worker`, `request_details`, `priority`, `send_email` FROM `request_queue` WHERE
        `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'
        AND `user_id` = '" . mysqli_real_escape_string($db['link'], getuserdata('id')) . "'
        LIMIT 1";
        $res = mysqli_query($db['link'], $qry);
        if (mysqli_num_rows($res)) {
            $assoc = mysqli_fetch_assoc($res);
            $data = json_decode($assoc['request_details'], TRUE);
            if (!is_array($data)) {
                $data = array();
            }
            for ($i = 1; $i <= 2; $i++) {
                if (!is_array($data['period'][$i]['daysofweek'])) {
                    $data['period'][$i]['daysofweek'] = array();
                }
            }
            $data['type'] = $assoc['worker'];
            $data['priority'] = $assoc['priority'];
            $data['email'] = ($assoc['send_email'] == 1) ? 'true' : 'false';
            $data['name'] = htmlspecialchars($assoc['name'] . ' - kopie');
        }
    }
    //request from map view
    else {
        $data['markers'] = $_POST['markers'];
    }
    return $data;
}

//process requests
$value = getValuesForForm();
$selectedmarkers = checkSelectedMarkers($value['markers']);
$requestcompleted = validRequestCompleted();
if ($requestcompleted === TRUE) {
    $requestcompleted = addRequestToQueue();
    if ($requestcompleted === TRUE) {
        //request hypervisor
        if ($cfg['hypervisor']['user_activated'] == TRUE) {
            include_once 'hypervisor.php';
        }
        //redirect to result page
        header('Location: results.php');
    }
    else {
        $errors[] = 'mysql';
    }
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
        <?php 
        if (in_array('mysql', $requestcompleted)) {
            echo '<p class="error">Kan aanvraag niet opslaan. Probeer het later nogmaals of meld het probleem als het zich blijft voordoen.</p>';
        }
        elseif (is_array($requestcompleted) && !empty($requestcompleted)) {
            echo '<p class="info">Kan aanvraag niet indienen. E&eacute;n of meerdere velden zijn niet correct ingevuld.</p>';
        }
        ?>
        <form method="POST">
        <input type="hidden" name="from" value="form">
        <p>Via deze pagina kunnen analyses gemaakt worden op de data in fietsv&#7433;ewer. Aangevraagde analyses worden toegevoegd aan een wachtrij en kunnen zodra ze klaar zijn geraadpleegd worden via de pagina <a href="results.php">mijn analyses</a>. Via <a href="results.php">mijn analyses</a> kunnen de instellingen van eerder gedraaide analyses ook hergebruikt worden. Het is aanbevolen om deze pagina van boven naar beneden door te lopen, omdat sommige instellingen afhankelijk zijn van eerdere selectie.</p>

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
        <input type="hidden" name="markers" value="<?php echo htmlspecialchars($value['markers']); ?>">

        <h2>type analyse</h2>
        <?php if (in_array('type', $requestcompleted)) {
            echo '<p class="warning">Selecteer een type analyse.</p>';
        } ?>
        <p>Kies hieronder het type analyse dat je wil maken. Mis je een type analyse, controleer dan <a href="index.php">op de kaart</a> of de juiste kaartlagen zijn ingeschakeld.</p>
        <dl>
            <dt><input type="radio" name="type" id="form-type-1" value="diff"<?php if ($value['type'] == 'diff') echo ' checked'; ?>> <label for="form-type-1">Verschil</label></dt>
            <dd>Bereken het verschil tussen twee perioden</dd>
            <dt><input type="radio" name="type" id="form-type-2" value="trend"<?php if ($value['type'] == 'trend') echo ' checked'; ?>> <label for="form-type-2">Trend</label></dt>
            <dd>Bereken het verloop in de tijd over de som van de geselecteerde meetpunten</dd>
            <dt><input type="radio" name="type" id="form-type-3" value="plot"<?php if ($value['type'] == 'plot') echo ' checked'; ?>> <label for="form-type-3">Plot</label></dt>
            <dd>Maak een plot van de meetwaarden over de tijd</dd>
        </dl>

        <h2>Periode</h2>
        <?php 
        for ($i = 1; $i <= 2; $i++) { 
        $legend = (($i == 1) ? 'Onderzoeksperiode' : 'Vergelijken met basisperiode');
        ?>
        <fieldset class="left" id="form-period-select-<?php echo $i; ?>">
            <legend><?php echo $legend; ?></legend>
            <fieldset>
            <legend>Datum</legend>
            <?php
            if (in_array('date-start' . $i, $requestcompleted)) {
                echo '<p class="warning">Selecteer een geldige begindatum.</p>';
            }
            if (in_array('date-end' . $i, $requestcompleted)) {
                echo '<p class="warning">Selecteer een geldige einddatum.</p>';
            }
            if (in_array('date-end-before-start' . $i, $requestcompleted)) {
                echo '<p class="warning">Einddatum mag niet voor begindatum liggen.</p>';
            }
            ?>
            <table>
                <tr>
                    <td><label for="form-date-start<?php echo $i; ?>">van:</label></td>
                    <td><input type="date" name="date-start<?php echo $i; ?>" id="form-date-start<?php echo $i; ?>" value="<?php echo $value['period'][$i]['date-start']; ?>" autocomplete="off" required></td>
                </tr>
                <tr>
                    <td><label for="form-date-end<?php echo $i; ?>">t/m:</label></td
                    ><td><input type="date" name="date-end<?php echo $i; ?>" id="form-date-end<?php echo $i; ?>" value="<?php echo $value['period'][$i]['date-end']; ?>" autocomplete="off" required></td>
                </tr>
            </table>
            </fieldset>
            
            <fieldset>
            <legend>Tijd dagelijks</legend>
            <?php
            if (in_array('time-start' . $i, $requestcompleted)) {
                echo '<p class="warning">Selecteer een geldige begintijd.</p>';
            }
            if (in_array('time-end' . $i, $requestcompleted)) {
                echo '<p class="warning">Selecteer een geldige eindtijd.</p>';
            }
            ?>
            <table>
                <tr>
                    <td><label for="form-time-start<?php echo $i; ?>">van:</label></td>
                    <td><input type="time" name="time-start<?php echo $i; ?>" id="form-time-start<?php echo $i; ?>" value="<?php echo $value['period'][$i]['time-start']; ?>" autocomplete="off" required></td>
                </tr>
                <tr>
                    <td><label for="form-time-end<?php echo $i; ?>">tot:</label></td>
                    <td><input type="time" name="time-end<?php echo $i; ?>" id="form-time-end<?php echo $i; ?>" value="<?php echo $value['period'][$i]['time-end']; ?>" autocomplete="off" required></td>
                </tr>
            </table>
            </fieldset>
            
            <fieldset id="form-daysofweek<?php echo $i; ?>">
            <legend>Dagen van de week</legend>
            <?php
            if (in_array('daysofweek' . $i, $requestcompleted)) {
                echo '<p class="warning">Geen geldige weekdagen geselecteerd.</p>';
            }
            foreach ($daysofweek as $num => $val) {
                echo '<input type="checkbox" value="' . $num .'" name="daysofweek' . $i .'[]" id="form-daysofweek' .$i .'-' . $num .'"';
                if (in_array($num, $value['period'][$i]['daysofweek'])) echo ' checked';
                echo '>';
                echo ' <label for="form-daysofweek' . $i . '-' . $num .'">' . $val .'</label>';
            }
            ?>
            <br>
            <a id="form-daysofweek<?php echo $i; ?>-selectworkdays">werkdagen</a> - 
            <a id="form-daysofweek<?php echo $i; ?>-selecttuethu">di+do</a> - 
            <a id="form-daysofweek<?php echo $i; ?>-selectweekend">weekend</a> - 
            <a id="form-daysofweek<?php echo $i; ?>-selectall">weekdagen</a> - 
            <a id="form-daysofweek<?php echo $i; ?>-selectnone">geen</a>
            </fieldset>
        </fieldset>
        <?php } ?>
        <div class="clear"></div>

        <h2>Aggregatie</h2>
        <p>Selecteer het aggregatieniveau waarop de resultaten gepresenteerd moeten worden.</p>
        <?php if (in_array('aggregate', $requestcompleted)) {
            echo '<p class="warning">Selecteer een geldige aggregatieperiode.</p>';
        } ?>
        <p><label for="form-aggregate">Aggregeer data per:</label>
            <select name="aggregate" id="form-aggregate" required>
                <?php
                foreach ($aggregateoptions as $val => $str) {
                    echo '<option value="' . $val . '"';
                    if ($value['aggregate'] == $val) echo ' selected';
                    echo '>' . $str . '</option>';
                }
                ?>
            </select>
        </p>

        <h2>Databeschikbaarheid</h2>
        <p>Indien gewenst kunnen alleen meetlocaties met een minimale op te geven databeschikbaarheid worden meegenomen.</p>
        <?php if (in_array('availability', $requestcompleted)) {
            echo '<p class="warning">Beschikbaarheid moet een getal zijn van 0 tot 100.</p>';
        } ?>
        <p><label for="form-availability">Minimale databeschikbaarheid per meetlocatie:</label> <input type="number" name="availability" id="form-availability" value="<?php echo $value['availability']; ?>" min="0" max="100" step="1" autocomplete="off" required>%</p>

        <h2>prioriteit</h2>
        <p>Kies hieronder met welke prioriteit de aanvraag moet worden uitgevoerd. Aanvragen met een hoge prioriteit worden eerder in de wachtrij geplaatst. Aanvragen met een normale prioriteit die niet binnen 24 uur in behandeling kunnen worden genomen krijgen op dat moment hoge prioriteit. Voor aanvragen met een lage prioriteit geldt hetzelfde, maar dan na 72 uur.</p>
        <?php if (in_array('name', $requestcompleted)) {
            echo '<p class="warning">Selecteer een prioriteit voor deze aanvraag.</p>';
        } ?>
        <p><label for="form-priority">Prioriteit:</label>
        <select name="priority" id="form-priority" required>
            <?php
            foreach ($priorityoptions as $val => $str) {
                echo '<option value="' . $val . '"';
                if ($value['priority'] == $val) echo ' selected';
                echo '>' . $str . '</option>';
            }
            ?>    
        </select></p>
        <?php if (in_array('email', $requestcompleted)) {
            echo '<p class="warning">Geef aan of je een e-mail wilt ontvangen zodra de aanvraag klaar is.</p>';
        } ?>
        <p>E-mail bij gereed: <input type="radio" name="email" id="form-email-true" value="true"<?php echo (($value['email'] == 'true') ? ' checked' : ''); ?>> <label for="form-email-true">Ja</label> <input type="radio" name="email" id="form-email-false" value="false"<?php echo (($value['email'] == 'false') ? ' checked' : ''); ?>> <label for="form-email-false">Nee</label><br>
        <span id="form-email-to-container">E-mail wordt gestuurd naar: <?php echo htmlspecialchars(getuserdata('email')); ?></span></p>

        <h2>Naam</h2>
        <p>Geef je aanvraag een naam, zodat je deze later makkelijk terug kunt vinden.</p>
        <?php if (in_array('name', $requestcompleted)) {
            echo '<p class="warning">Geef je aanvraag een naam.</p>';
        } ?>
        <p><label for="form-name">Naam aanvraag:</label> <input type="text" maxlength="255" name="name" id="form-name" value="<?php echo $value['name']; ?>" required></p>

        <p><input type="submit" value="Aanvraag indienen"></p>

        </form>

        <?php
    }
    ?>

</body>
</html>