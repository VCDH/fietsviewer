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
accesscheck('adddata');
require 'dbconnect.inc.php';
require 'config.inc.php';
require_once 'functions/csv_functions.php';
require_once 'functions/check_format.php';

/*
* function to check uploaded file for errors
* returns (bool) TRUE if the file is ok, returns (string) if there is an error describing the error
*/
function check_uploaded_file ($file, $maxsize = 0, $allowed = array('csv')) {
    if (!empty($_FILES)) {
        //check if there is a file
        if ($_FILES[$file]['error'] === UPLOAD_ERR_NO_FILE) {
            //there is no file
            return 'no_file_selected';
        }
        else {
            //there is a file
            if ($_FILES[$file]['error'] === UPLOAD_ERR_OK) {
                //check custom filesize
                if (($maxsize == 0) || ($_FILES[$file]['size'] <= $maxsize)) {
                    //get filetype
                    $ext = strtolower(substr($_FILES[$file]['name'], strrpos($_FILES[$file]['name'], '.') + 1));
                    //check filetype
                    if (in_array($ext, $allowed)) {
                        //checks passed
                        return TRUE;
                    }
                    else {
                        return 'filetype_not_allowed';
                    }
                }
                else {
                    return 'file_too_large';
                }
            }
            //check ini filesize
            elseif ($_FILES[$file]['error'] === UPLOAD_ERR_INI_SIZE) {
                return 'file_too_large';
            }
            //check if file incomplete
            elseif ($_FILES[$file]['error'] === UPLOAD_ERR_PARTIAL) {
                return 'file_incomplete';
            }
            //some other error
            else {
                return 'unknown_error';
            }
        }
    } 
}

/*
* function to store uploaded file
* returns (bool) FALSE on failure or (string) md5_hash on success
*/
function store_uploaded_file($file) {
    global $cfg;
    //get md5 hash of uploaded file
    $md5 = md5_file($_FILES[$file]['tmp_name']);
    //move uploaded file
    $target_file = $cfg['upload']['dir'];
    //add trailing slash if needed
    if (substr($target_file, -1) != '/') {
        $target_file .= '/';
    }
    $target_file .= $md5;
    $res = move_uploaded_file($_FILES[$file]['tmp_name'], $target_file);
    if ($res == TRUE) {
        return $md5;
    }
    else {
        return FALSE;
    }
}

/*
* process upload
*/
if (!empty($_FILES)) {
    $res = check_uploaded_file('file');
    if ($res !== TRUE) {
        $upload_error = $res;
    }
    else {
        $format = check_data_format($_FILES['file']['tmp_name']);
        if ($format === FALSE) {
            $upload_error = 'data_format';
        }
        else {
            $res = store_uploaded_file('file');
            if ($res === FALSE) {
                $upload_error = 'move_file';
            }
            else {
                //add md5 to process queue
                $qry = "INSERT INTO `upload_queue` SET
                `user_id` = '" . mysqli_real_escape_string($db['link'], getuserdata('id')) . "',
                `dataset_id` = '" . mysqli_real_escape_string($db['link'], $_POST['dataset_id']) . "',
                `md5` = '" . mysqli_real_escape_string($db['link'], $res) . "',
                `filename` = '" . mysqli_real_escape_string($db['link'], $_FILES['file']['name']) . "',
                `datatype` = '" . mysqli_real_escape_string($db['link'], $format) . "',
                `processed` = 0,
                `date_create` = NOW(),
                `date_lastchange` = NOW()";
                if (mysqli_query($db['link'], $qry)) {
                    $upload_success = TRUE;
                    //request hypervisor
                    if ($cfg['hypervisor']['user_activated'] == TRUE) {
                        include_once 'hypervisor.php';
                    }
                }
                else {
                    $upload_error = 'database';
                }
            }
        }
    }
}

/*
* display page
*/
?>
<!DOCTYPE html>
<html lang="nl-nl">
<head>
	<title>fietsv&#7433;ewer - data aan gegevensset toevoegen</title>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="style.css">
	<script src="jquery/jquery-3.3.1.min.js"></script>
</head>
<body>
	
	<?php include('menu.inc.php'); ?>

    <h1>data aan gegevensset toevoegen</h1>
    
    <?php
    //get available datasets
    $qry = "SELECT `id`, `name` FROM `datasets`
    WHERE `organisation_id` = '" . mysqli_real_escape_string($db['link'], getuserdata('organisation_id')) . "'";
    $res = mysqli_query($db['link'], $qry);
    if (!mysqli_num_rows($res)) {
        echo '<h2>gegevensset ontbreekt</h2>';
        echo '<p>Geen gegevenssets gevonden. Een beheerder moet eerst een of meerdere <a href="admin.php?p=datasets">gegevenssets aanmaken</a>.</p>';
    }
    else {
    ?>
    
    <h2>handmatige upload</h2>

    <p>Data kunnen handmatig worden toegevoegd via onderstaande uploadfunctie. Selecteer aan welke gegevensset de nieuwe data moet worden toegevoegd. Een gegevensset is een collectie van &eacute;&eacute;n of meerdere datapunten die een bepaalde samenhang hebben (bijvoorbeeld door dezelfde organisatie met eenzelfde techniek ingewonnen). Selecteer daarna een bestand en klik op Upload. De bestandsindeling moet voldoen aan de specificatie zoals beschreven in de <a href="docs/interfacebeschrijving_import.html" class="ext" target="_blank">interfacebeschrijving</a>. De maximale bestandsgrootte is <?php echo ini_get('post_max_size'); ?>. Sluit de pagina niet voordat de upload voltooid is.</p>
    
    <form method="POST" enctype="multipart/form-data">
    
    <?php
        echo '<p><b>Gegevensset:</b> <select name="dataset_id">';
        while ($row = mysqli_fetch_row($res)) {
            echo '<option value="' . $row[0] . '"';
            if (($_POST['dataset_id'] == $row[0]) || (empty($_POST) && ($_GET['dataset_id'] == $row[0]))) {
                echo ' selected';
            }
            echo '>';
            echo htmlspecialchars($row[1]);
            echo '</option>';
        }
        echo '</select></p>';

    if (!empty($upload_error)) {
        echo '<p class="error">' . $upload_error . '</p>';
    }
    if ($upload_success == TRUE) {
        echo '<p class="success">Bestand is aan de wachtrij toegevoegd. Zodra het databestand verwerkt is, wordt de data in fietsv&#7433;ewer zichtbaar.</p>';
    }
    ?>
        <p><b>Bestand:</b> <input type="file" name="file"></p>
        <p><input type="submit" value="Upload"></p>
    </form>
    
    <?php
    }
    ?>
    
    <h2>wachtrij</h2>
    <?php
    $qry = "SELECT `date_create`, `filename`, `datatype`, `datasets`.`name`  
    FROM `upload_queue`
    LEFT JOIN `datasets`
    ON `upload_queue`.`dataset_id` = `datasets`.`id`
    WHERE 
    `user_id` = '" . mysqli_real_escape_string($db['link'], getuserdata('id')) . "'
    AND
    `processed` = 0
    ORDER BY `date_create` ASC";
    $res = mysqli_query($db['link'], $qry);
    if (mysqli_num_rows($res)) {
        echo '<p>Onderstaande databestanden zijn ontvangen maar moeten nog verwerkt worden.</p>';
        echo '<table><thead>';
        echo '<tr><th>Toegevoegd</th><th>Bestand</th><th>Gegevensset</th><th>Type</th></tr>';
        echo '</thead><tbody>';
        while ($row = mysqli_fetch_row($res)) {
            echo '<tr><td>';
            echo $row[0];
            echo '</td><td>';
            echo htmlspecialchars($row[1]);
            echo '</td><td>';
            echo htmlspecialchars($row[3]);
            echo '</td><td>';
            echo htmlspecialchars($row[2]);
            echo '</td></tr>';
        }
        echo '</tbody></table>';
    }
    else {
        echo '<p>Er is geen databestand in de wachtrij.</p>';
    }
    ?>
    <p><a href="?">vernieuwen</a></p>

    <h2>verwerkt</h2>
    <?php
    $qry = "SELECT `date_lastchange`, `filename`, `process_error`, `process_time`, `date_create`, `datatype`, `datasets`.`name` 
    FROM `upload_queue`
    LEFT JOIN `datasets`
    ON `upload_queue`.`dataset_id` = `datasets`.`id`
    WHERE 
    `user_id` = '" . mysqli_real_escape_string($db['link'], getuserdata('id')) . "'
    AND
    `processed` = 1
    AND 
    `process_time` IS NOT NULL
    ORDER BY `date_lastchange` DESC
    LIMIT 16";
    $res = mysqli_query($db['link'], $qry);
    if (mysqli_num_rows($res)) {
        echo '<p>Onderstaande databestanden zijn recent verwerkt. Alleen de 16 meest recente toevoegingen worden weergegeven.</p>';
        echo '<table><thead>';
        echo '<tr><th>Toegevoegd</th><th>Verwerkt</th><th>Bestand</th><th>Gegevensset</th><th>Type</th><th>Geslaagd</th><th>Verwerkingstijd</th><th>Foutmeldingen</th></tr>';
        echo '</thead><tbody>';
        while ($row = mysqli_fetch_row($res)) {
            echo '<tr><td>';
            echo $row[4];
            echo '</td><td>';
            echo $row[0];
            echo '</td><td>';
            echo htmlspecialchars($row[1]);
            echo '</td><td>';
            echo htmlspecialchars($row[6]);
            echo '</td><td>';
            echo htmlspecialchars($row[5]);
            echo '</td><td>';
            echo (($row[2] == 1) ? 'Nee' : 'Ja');
            echo '</td><td>';
            echo htmlspecialchars($row[3]);
            echo '</td><td>';
            if (strlen($row[2]) > 100) {
                echo htmlspecialchars(substr($row[2], 0, 100));
                echo '... (afgebroken)';
            }
            else {
                echo htmlspecialchars($row[2]);
            }
            echo '</td></tr>';
        }
        echo '</tbody></table>';
    }
    else {
        echo '<p>Er zijn nog geen databestanden verwerkt.</p>';
    }

    ?>

    <h2>geautomatiseerde upload</h2>
    <p>Via de API kunnen data ook automatisch worden aangeboden. Voor meer informatie zie de <a href="docs/interfacebeschrijving_import.html" class="ext" target="_blank">interfacebeschrijving</a>.</p>

    <?php
    //get default dataset for API access
    $qry = "SELECT `id`, `name` FROM `datasets`
    WHERE `organisation_id` = '" . mysqli_real_escape_string($db['link'], getuserdata('organisation_id')) . "' 
    AND `id` = '" . mysqli_real_escape_string($db['link'], getuserdata('default_dataset_id')) . "'";
    $res = mysqli_query($db['link'], $qry);
    if (!mysqli_num_rows($res)) {
        echo '<p>Er is geen gegevensset geselecteerd voor API toegang. <a href="admin.php?p=datasets">Wijs eerst een gegevensset toe</a>.</p>';
    }
    else {
        $data = mysqli_fetch_assoc($res);
        ?>
        <table>
            <tr><th>URL</th><td><?php echo htmlspecialchars(substr($_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'], '/'))) . '/api/add'; ?></td></tr>
            <tr><th>gebruikersnaam</th><td><?php echo htmlspecialchars(getuserdata('username')); ?></td></tr>
            <tr><th>wachtwoord</th><td>(bekend bij gebruiker)</td></tr>
            <tr><th>maximale POST grootte</th><td><?php echo ini_get('post_max_size'); ?></td></tr>
            <tr><th>gegevensset</th><td><?php echo htmlspecialchars($data['name']); ?> <a href="admin.php?p=datasets">wijzigen</a></td></tr>
        </table>
        <p><b>Gegevensset</b>: via de API aangeleverde data worden opgeslagen in de gegevensset <i><?php echo htmlspecialchars($data['name']); ?></i>.</p>
        <?php
    }
    ?>
    
</body>
</html>