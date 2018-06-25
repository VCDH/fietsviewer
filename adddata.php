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
require_once 'functions/csv_functions.php';

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
* function to check the uploaded file for file consistency with the data format
* only header row and first row are checked
* returns (bool) FALSE if the file is wrong or (str) $format if the file is correct
*/
function check_data_format($file) {
    //open file
    $handle = fopen($file, 'rb');
    if ($handle == FALSE) {
        return FALSE;
    }
    //get header row
    $line = fgets($handle);
    if ($line == FALSE) {
        return FALSE;
    }
    //detect delimiter
    $delimiter = csv_delimiter_from_string($line);
    if ($delimiter == FALSE) {
        return FALSE;
    }
    //get column names
    $colnames = str_getcsv($line, $delimiter);
    //check dpf format
    $mandatory_keys = check_format_dpf_flow($colnames);
    if ($mandatory_keys == FALSE) {
        return FALSE;
    }
    else {
        $format = 'dpf-flow';
    }
    //check first row for integrity
    $row1 = fgetcsv($handle, null, $delimiter);
    foreach ($mandatory_keys as $col) {
        if (empty($row1[$col])) {
            return FALSE;
        }
    }
    return $format;
}

/*
* function to check "data platform fiets" format
* returns FALSE if it doesn't match or an array with for each column (in order) whether or not the column is mandatory (given in TRUE/FALSE)
*/
function check_format_dpf_flow($arr_colnames) {
    $mandatory_cols = array(
        array('locatie-id', 'location-id', 'id', 'nr'),
        array('lat'),
        array('lon'),
        array('richting', 'heading', 'direction'),
        array('methode', 'method'),
        array('periode-van', 'period-from'),
        array('periode-tot', 'period-to'),
        array('tijd-van', 'time-from'),
        array('tijd-tot', 'time-to'),
        array('fiets', 'bicycle')
    );
    //set $arr_colnames to lowercase
    $arr_colnames = array_map('strtolower', $arr_colnames);
    $mandatory_keys = array();
    //check for each mandatory col
    foreach ($mandatory_cols as $cols) {
        //assume false
        $assume = FALSE;
        //check for presence of field
        foreach ($cols as $col) {
            $key = array_search($col, $arr_colnames);
            if ($key !== FALSE) {
                $assume = TRUE;
                $mandatory_keys[] = $key;
                break;
            }
        }
        //if not present, break and return FALSE
        if ($assume == FALSE) {
            return FALSE;
        }
    }
    return $mandatory_keys;
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
                `prefix_id` = 1,
                `md5` = '" . mysqli_real_escape_string($db['link'], $res) . "',
                `filename` = '" . mysqli_real_escape_string($db['link'], $_FILES['file']['name']) . "',
                `datatype` = '" . mysqli_real_escape_string($db['link'], $format) . "',
                `processed` = 0,
                `date_create` = NOW(),
                `date_lastchange` = NOW()";
                if (mysqli_query($db['link'], $qry)) {
                    $upload_success = TRUE;
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
	<title>fietsv&#7433;ewer - gegevensset toevoegen</title>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="style.css">
	<script src="jquery/jquery-3.3.1.min.js"></script>
</head>
<body>
	
	<?php include('menu.inc.php'); ?>

    <h1>gegevensset toevoegen</h1>
    <p>Nieuwe gegevenssets kunnen handmatig of geautomatiseerd aan fietsv&#7433;ewer worden toegevoegd. Zie de helptekst voor meer informatie over ondersteunde bestandsindelingen en de API voor geautomatiseerd aanleveren. Gegevenssets kunnen handmatig worden toegevoegd via onderstaande uploadfunctie.</p>

    <h2>prefix</h2>
    <p></p>

    <h2>handmatige upload</h2>
    <p>Selecteer een bestand en klik op Upload. De bestandsindeling moet voldoen aan de specificatie zoals beschreven in de <a href="docs/interfacebeschrijving_import.html" class="ext" target="_blank">interfacebeschrijving</a>. De maximale bestandsgrootte is <?php echo ini_get('post_max_size'); ?>.</p>
    <?php
    if (!empty($upload_error)) {
        echo '<p class="error">' . $upload_error . '</p>';
    }
    if ($upload_success == TRUE) {
        echo '<p class="success">Bestand is aan de wachtrij toegevoegd. Zodra de gegevensset verwerkt is, wordt de data in fietsv&#7433;ewer zichtbaar.</p>';
    }
    ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="file">
        <br>
        <input type="submit" value="Upload">
    </form>

    <h2>geautomatiseerde upload</h2>
    <p>Via een API kunnen gegevenssets ook automatisch worden aangeboden. Voor meer informatie zie de <a href="docs/interfacebeschrijving_import.html" class="ext" target="_blank">interfacebeschrijving</a>.</p>
    <table>
        <tr><th>URL</th><td><?php echo htmlspecialchars(substr($_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'], '/'))) . '/api/add'; ?></td></tr>
        <tr><th>gebruikersnaam</th><td><?php echo htmlspecialchars(getuserdata('username')); ?></td></tr>
        <tr><th>wachtwoord</th><td>(bekend bij gebruiker)</td></tr>
        <tr><th>maximale POST grootte</th><td><?php echo ini_get('post_max_size'); ?></td></tr>
    </table>

    <h2>wachtrij</h2>
    <?php
    $qry = "SELECT `date_create`, `filename`, `datatype` 
    FROM `upload_queue`
    WHERE 
    `user_id` = '" . mysqli_real_escape_string($db['link'], getuserdata('id')) . "'
    AND
    `processed` = 0
    ORDER BY `date_create` ASC";
    $res = mysqli_query($db['link'], $qry);
    if (mysqli_num_rows($res)) {
        echo '<p>Onderstaande gegevenssets zijn ontvangen maar moeten nog verwerkt worden.</p>';
        echo '<table><thead>';
        echo '<tr><th>Toegevoegd</th><th>Bestand</th><th>Type</th></tr>';
        echo '</thead><tbody>';
        while ($row = mysqli_fetch_row($res)) {
            echo '<tr><td>';
            echo $row[0];
            echo '</td><td>';
            echo htmlspecialchars($row[1]);
            echo '</td><td>';
            echo htmlspecialchars($row[2]);
            echo '</td></tr>';
        }
        echo '</tbody></table>';
    }
    else {
        echo '<p>Er is geen gegevensset in de wachtrij.</p>';
    }
    ?>

    <h2>verwerkt</h2>
    <?php
    $qry = "SELECT `date_lastchange`, `filename`, `process_error`, `process_time`, `date_create` 
    FROM `upload_queue`
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
        echo '<p>Onderstaande gegevenssets zijn recent verwerkt. Alleen de 16 meest recente toevoegingen worden weergegeven.</p>';
        echo '<table><thead>';
        echo '<tr><th>Toegevoegd</th><th>Verwerkt</th><th>Bestand</th><th>Geslaagd</th><th>Verwerkingstijd</th><th>Foutmeldingen</th></tr>';
        echo '</thead><tbody>';
        while ($row = mysqli_fetch_row($res)) {
            echo '<tr><td>';
            echo $row[0];
            echo '</td><td>';
            echo $row[4];
            echo '</td><td>';
            echo htmlspecialchars($row[1]);
            echo '</td><td>';
            echo (($row[2] == 1) ? 'Nee' : 'Ja');
            echo '</td><td>';
            echo htmlspecialchars($row[3]);
            echo '</td><td>';
            echo htmlspecialchars($row[2]);
            echo '</td></tr>';
        }
        echo '</tbody></table>';
    }
    else {
        echo '<p>Er zijn nog geen gegevenssets verwerkt.</p>';
    }
    ?>
        
    
</body>
</html>