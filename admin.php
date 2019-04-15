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
accesscheck('admin');
$accesslevel = getuserdata('accesslevel');
require 'dbconnect.inc.php';
require 'config.inc.php';

//preprocess
$messages = array();
//edit existing or store new organisation
if (($_GET['p'] == 'organisations') && ($_GET['a'] == 'edit') && accesslevelcheck('organisations')) {
    //check if id exists
    $qry = "SELECT `id`, `name` FROM `organisations` 
    WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'
    LIMIT 1";
    $res = mysqli_query($db['link'], $qry);
    if (mysqli_num_rows($res)) {
        $data = mysqli_fetch_assoc($res);
    }
    else {
        $data = array();
    }
    //process post
    if (!empty($_POST)) {
        $store_success = TRUE;
        //overload post
        $data['name'] = $_POST['name'];
        $data['abbr'] = $_POST['abbr'];
        //check fields
        if (empty($data['name'])) {
            //name is empty
            $store_success = FALSE;
            $messages[] = 'name_empty';
        }
        else {
            //check if name doesn't exist
            $qry = "SELECT `name` FROM `organisations` 
            WHERE `name` = '" . mysqli_real_escape_string($db['link'], $data['name']) . "'
            AND `id` != '" . mysqli_real_escape_string($db['link'], $data['id']) . "'
            LIMIT 1";
            $res = mysqli_query($db['link'], $qry);
            if (mysqli_num_rows($res)) {
                $store_success = FALSE;
                $messages[] = 'name_duplicate';
            }
        }
        if (empty($data['abbr'])) {
            //name is empty
            $store_success = FALSE;
            $messages[] = 'abbr_empty';
        }
        elseif (!preg_match('/[A-Z]{3,32}/', $data['abbr'])) {
            //name is empty
            $store_success = FALSE;
            $messages[] = 'abbr_format';
        }
        else {
            //check if name doesn't exist
            $qry = "SELECT `abbr` FROM `organisations` 
            WHERE `abbr` = '" . mysqli_real_escape_string($db['link'], $data['abbr']) . "'
            AND `id` != '" . mysqli_real_escape_string($db['link'], $data['id']) . "'
            LIMIT 1";
            $res = mysqli_query($db['link'], $qry);
            if (mysqli_num_rows($res)) {
                $store_success = FALSE;
                $messages[] = 'abbr_duplicate';
            }
        }
        //store in database
        if ($store_success === TRUE) {
            if (!is_numeric($data['id'])) {
                //insert new
                $qry = "INSERT INTO `organisations`
                SET
                `name` = '" . mysqli_real_escape_string($db['link'], $data['name']) . "', 
                `abbr` = '" . mysqli_real_escape_string($db['link'], $data['abbr']) . "'";
                $store_success = mysqli_query($db['link'], $qry);
            }
            else {
                //update existing
                $qry = "UPDATE `organisations`
                SET
                `name` = '" . mysqli_real_escape_string($db['link'], $data['name']) . "', 
                `abbr` = '" . mysqli_real_escape_string($db['link'], $data['abbr']) . "'
                WHERE
                `id` = '" . mysqli_real_escape_string($db['link'], $data['id']) . "'";
                $store_success = mysqli_query($db['link'], $qry);
            }
        }
    }
}
//edit existing or store new user
if (($_GET['p'] == 'users') && ($_GET['a'] == 'edit') && accesslevelcheck('users')) {
    //check if id exists
    $qry = "SELECT `id`, `username`, `name`, `email`, `phone`, `organisation_id`, `accesslevel` FROM `users` 
    WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'
    LIMIT 1";
    $res = mysqli_query($db['link'], $qry);
    if (mysqli_num_rows($res)) {
        $data = mysqli_fetch_assoc($res);
        $old_user_access_level = $data['accesslevel'];
    }
    else {
        $data = array();
    }
    $data['password'] = 'keep';
    //process post
    if (!empty($_POST)) {
        $store_success = TRUE;
        //overload post
        $data['username'] = $_POST['username'];
        $data['name'] = $_POST['name'];
        $data['email'] = $_POST['email'];
        $data['phone'] = $_POST['phone'];
        $data['accesslevel'] = $_POST['accesslevel'];
        $data['organisation_id'] = $_POST['organisation_id'];
        if ($_POST['password'] == 'email') {
            $data['password'] = 'email';
        }
        //check fields
        if (empty($data['username'])) {
            //name is empty
            $store_success = FALSE;
            $messages[] = 'username_empty';
        }
        else {
            //check if username doesn't exist
            $qry = "SELECT `username` FROM `users` 
            WHERE `username` = '" . mysqli_real_escape_string($db['link'], $data['name']) . "'
            AND `id` != '" . mysqli_real_escape_string($db['link'], $data['id']) . "'
            LIMIT 1";
            $res = mysqli_query($db['link'], $qry);
            if (mysqli_num_rows($res)) {
                $store_success = FALSE;
                $messages[] = 'username_duplicate';
            }
        }
        //check accesslevel
        if (!is_numeric($data['accesslevel']) || ($data['accesslevel'] < 1) || ($data['accesslevel'] > $accesslevel) || ($accesslevel < $old_user_access_level)) {
            //name is empty
            $store_success = FALSE;
            $messages[] = 'accesslevel';
        }
        //check organisation
        $qry = "SELECT `id` FROM `organisations` 
        WHERE `id` = '" . mysqli_real_escape_string($db['link'], $data['organisation_id']) . "'
        LIMIT 1";
        $res = mysqli_query($db['link'], $qry);
        if (!mysqli_num_rows($res)) {
            $store_success = FALSE;
            $messages[] = 'organisation_id';
        }
        //store in database
        if ($store_success === TRUE) {
            if (!is_numeric($data['id'])) {
                //insert new
                //TODO: initial password should be something better than the current time; it should not work for login, but it's better to do this properly
                $qry = "INSERT INTO `users`
                SET
                `username` = '" . mysqli_real_escape_string($db['link'], $data['username']) . "', 
                `name` = '" . mysqli_real_escape_string($db['link'], $data['name']) . "', 
                `email` = '" . mysqli_real_escape_string($db['link'], $data['email']) . "', 
                `password` = '" . mysqli_real_escape_string($db['link'], time()) . "', 
                `phone` = '" . mysqli_real_escape_string($db['link'], $data['phone']) . "', 
                `accesslevel` = '" . mysqli_real_escape_string($db['link'], $data['accesslevel']) . "', 
                `organisation_id` = '" . mysqli_real_escape_string($db['link'], $data['organisation_id']) . "'";
                $store_success = mysqli_query($db['link'], $qry);
            }
            else {
                //update existing
                $qry = "UPDATE `users`
                SET
                `username` = '" . mysqli_real_escape_string($db['link'], $data['username']) . "', 
                `name` = '" . mysqli_real_escape_string($db['link'], $data['name']) . "', 
                `email` = '" . mysqli_real_escape_string($db['link'], $data['email']) . "', 
                `phone` = '" . mysqli_real_escape_string($db['link'], $data['phone']) . "', 
                `accesslevel` = '" . mysqli_real_escape_string($db['link'], $data['accesslevel']) . "', 
                `organisation_id` = '" . mysqli_real_escape_string($db['link'], $data['organisation_id']) . "'
                WHERE
                `id` = '" . mysqli_real_escape_string($db['link'], $data['id']) . "'";
                $store_success = mysqli_query($db['link'], $qry);
            }
        }
        //new password
        if ($data['password'] == 'email') {
            //send new password
            require_once 'functions/reset_password.php';
            //new user
            if (!is_numeric($data['id'])) {
                reset_password($data['username'], TRUE);
            }
            //existing user
            else {
                reset_password($data['username']);
            }
        }
    }
}
//edit existing or store new dataset
if (($_GET['p'] == 'datasets') && ($_GET['a'] == 'edit') && accesslevelcheck('datasets')) {
    //check if id exists
    $qry = "SELECT `id`, `organisation_id`, `prefix`, `name`, `description` FROM `datasets` 
    WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'
    LIMIT 1";
    $res = mysqli_query($db['link'], $qry);
    if (mysqli_num_rows($res)) {
        $data = mysqli_fetch_assoc($res);
    }
    else {
        $data = array();
        $data['organisation_id'] = getuserdata('organisation_id');
    }
    //process post
    if (!empty($_POST)) {
        $store_success = TRUE;
        //overload post
        $data['name'] = $_POST['name'];
        $data['description'] = $_POST['description'];
        //check fields
        if (empty($data['name'])) {
            //name is empty
            $store_success = FALSE;
            $messages[] = 'name_empty';
        }
        //check organisation
        $qry = "SELECT `id`, `abbr` FROM `organisations` 
        WHERE `id` = '" . mysqli_real_escape_string($db['link'], $data['organisation_id']) . "'
        LIMIT 1";
        $res = mysqli_query($db['link'], $qry);
        if (!mysqli_num_rows($res)) {
            $store_success = FALSE;
            $messages[] = 'organisation_id';
        }
        else {
            $row = mysqli_fetch_row($res);
            $organisation_abbr = $row[1];
        }
        

        //store in database
        if ($store_success === TRUE) {
            if (!is_numeric($data['id'])) {
                //get last prefix
                $qry = "SELECT `prefix` FROM `datasets` 
                WHERE `prefix` LIKE '" . mysqli_real_escape_string($db['link'], $organisation_abbr) . "%' 
                ORDER BY `prefix` DESC
                LIMIT 1";
                $res = mysqli_query($db['link'], $qry);
                if (!mysqli_num_rows($res)) {
                    $new_prefix = $organisation_abbr . '01';
                }
                else {
                    //generate next prefix
                    $row = mysqli_fetch_row($res);
                    $last_prefix = $row[0];
                    $new_prefix = (int) substr($last_prefix, strlen($organisation_abbr));
                    $new_prefix += 1;
                    $new_prefix = $organisation_abbr . str_pad($new_prefix, 2, '0', STR_PAD_LEFT);
                }
                //insert new
                $qry = "INSERT INTO `datasets`
                SET
                `organisation_id` = '" . mysqli_real_escape_string($db['link'], $data['organisation_id']) . "', 
                `prefix` = '" . mysqli_real_escape_string($db['link'], $new_prefix) . "', 
                `name` = '" . mysqli_real_escape_string($db['link'], $data['name']) . "', 
                `description` = '" . mysqli_real_escape_string($db['link'], $data['description']) . "'";
                $store_success = mysqli_query($db['link'], $qry);
            }
            else {
                //update existing
                $qry = "UPDATE `datasets`
                SET
                `organisation_id` = '" . mysqli_real_escape_string($db['link'], $data['organisation_id']) . "', 
                `name` = '" . mysqli_real_escape_string($db['link'], $data['name']) . "', 
                `description` = '" . mysqli_real_escape_string($db['link'], $data['description']) . "'
                WHERE
                `id` = '" . mysqli_real_escape_string($db['link'], $data['id']) . "'";
                $store_success = mysqli_query($db['link'], $qry);
            }
        }
    }
}
//save default dataset
if (($_GET['p'] == 'api_dataset') && accesslevelcheck('datasets')) {
    $data = array();
    $data['dataset_id'] = getuserdata('default_dataset_id');
    //process post
    if (!empty($_POST)) {
        $store_success = TRUE;
        //overload post
        $data['dataset_id'] = $_POST['dataset_id'];
        //check dataset id
        if ($data['dataset_id'] !== 'NULL') {
            $qry = "SELECT `id` FROM `datasets` 
            WHERE `id` = '" . mysqli_real_escape_string($db['link'], $data['dataset_id']) . "' 
            AND `organisation_id` = '" . mysqli_real_escape_string($db['link'], getuserdata('organisation_id')) . "' 
            LIMIT 1";
            $res = mysqli_query($db['link'], $qry);
            if (!mysqli_num_rows($res)) {
                $store_success = FALSE;
                $messages[] = 'invalid';
            }
        }

        //store in database
        if ($store_success === TRUE) {
            $qry = "UPDATE `users` 
            SET `default_dataset_id` = " . ( ($data['dataset_id'] === 'NULL') ? 'NULL' : '\'' . mysqli_real_escape_string($db['link'], $data['dataset_id']) . '\'' ) . "
            WHERE `id` = '" . mysqli_real_escape_string($db['link'], getuserdata('id')) . "' 
            AND `organisation_id` = '" . mysqli_real_escape_string($db['link'], getuserdata('organisation_id')) . "'";
            $store_success = mysqli_query($db['link'], $qry);
        }
    }
}


?>
<!DOCTYPE html>
<html lang="nl-nl">
<head>
	<title>fietsv&#7433;ewer - admin</title>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="style.css">
	<script src="jquery/jquery-3.3.1.min.js"></script>
</head>
<body>
    <?php
    include('menu.inc.php');

    //edit organisation
    if (($_GET['p'] == 'organisations') && ($_GET['a'] == 'edit') && ($store_success !== TRUE)) {
        if (is_numeric($_GET['id'])) {
            echo '<h1>Organisatie bewerken</h1>';
        }
        else {
            echo '<h1>Organisatie toevoegen</h1>';
        }
        //messages
        if (in_array('name_duplicate', $messages)) {
            echo '<p class="warning">Er bestaat al een organisatie met deze naam.</p>';
        }
        if (in_array('name_empty', $messages)) {
            echo '<p class="warning">Organisatie-naam mag niet leeg zijn.</p>';
        }
        if (in_array('abbr_duplicate', $messages)) {
            echo '<p class="warning">Er bestaat al een organisatie met deze afkorting.</p>';
        }
        if (in_array('abbr_empty', $messages)) {
            echo '<p class="warning">Afkorting mag niet leeg zijn.</p>';
        }
        if (in_array('abbr_format', $messages)) {
            echo '<p class="warning">Afkorting mag enkel uit hoofdletters bestaan en moet minimaal 3 en maximaal 32 tekens lang zijn.</p>';
        }
        ?>
        <form method="post">
        <table class="invisible">
        <tr><td>Naam</td><td><input type="text" name="name" value="<?php echo htmlspecialchars($data['name']); ?>" required></td></tr>
        <tr><td>Afkorting</td><td><input type="text" name="abbr" value="<?php echo htmlspecialchars($data['abbr']); ?>" required></td></tr>
        <tr><td></td><td><input type="submit" value="Opslaan"> <a href="?p=organisations">Annuleren</a></td></tr>
        </table>
        </form>
        <p>De afkorting wordt gebruikt voor de identificatie van de datasets van deze organisatie. De afkorting wordt toegevoegd aan het begin van het ID van ieder meetpunt en daardoor is het wenselijk dat deze zo kort mogelijk is. De afkorting bestaat uit drie tot maximaal 32 hoofdletters.</p>
        <?php     
    }

    //organisations menu
    elseif ($_GET['p'] == 'organisations') {
        echo '<a href="?">&laquo; terug</a>';
        echo '<h1>Organisaties</h1>';
        //message
        if ($store_success === TRUE) {
            echo '<p class="success">Wijziging doorgevoerd.</p>';
        }
        //add link
        echo '<a href="?p=organisations&amp;a=edit">Organisatie toevoegen</a>';
        //list organisations in table
        $qry = "SELECT `id`, `name`, `abbr` FROM `organisations` ORDER BY `name` ASC";
        $res = mysqli_query($db['link'], $qry);
        if (mysqli_num_rows($res)) {
            echo '<table>';
            echo '<tr><th>Naam</th><th>Afkorting</th><th></th><th></th></tr>';
            while ($row = mysqli_fetch_row($res)) {
                echo '<tr><td>';
                echo htmlspecialchars($row[1]);
                echo '</td><td>';
                echo htmlspecialchars($row[2]);
                echo '</td><td>';
                echo '<a href="?p=organisations&amp;a=edit&amp;id=' . $row[0] . '">Bewerken</a>';
                echo '</td><td>';
                //TODO echo '<a href="?p=organisations&amp;a=delete&amp;id=' . $row[0] . '">Verwijderen</a>';
                echo '</td></tr>';
            }
            echo '</table>';
        }
        else {
            echo '<p class="info">Er zijn geen organisaties. Eigenlijk kan dit niet, dus kijk de installatie nog even goed na. Er zou in ieder geval een organisatie <i>system</i> moeten zijn.</p>';
        }
    }

    //edit users
    elseif (($_GET['p'] == 'users') && ($_GET['a'] == 'edit') && ($store_success !== TRUE)) {
        if (is_numeric($_GET['id'])) {
            echo '<h1>Gebruiker bewerken</h1>';
        }
        else {
            echo '<h1>Gebruiker toevoegen</h1>';
        }
        //messages
        if (in_array('name_duplicate', $messages)) {
            echo '<p class="warning">Er bestaat al een gebruiker met deze naam.</p>';
        }
        if (in_array('name_empty', $messages)) {
            echo '<p class="warning">Gebruikersnaam mag niet leeg zijn.</p>';
        }
        if (in_array('accesslevel', $messages)) {
            echo '<p class="warning">Toegangsniveau moet een getal van 1 t/m 255 zijn. 1 geeft de minste rechten, 255 de meeste.</p>';
        }
        if (in_array('organisation_id', $messages)) {
            echo '<p class="warning">Organisatie is ongeldig.</p>';
        }
        ?>
        <form method="post">
        <table class="invisible">
        <tr><td>Gebruikersnaam</td><td><input type="text" name="username" value="<?php echo htmlspecialchars($data['username']); ?>" required></td></tr>
        <tr><td>Naam</td><td><input type="text" name="name" value="<?php echo htmlspecialchars($data['name']); ?>"></td></tr>
        <tr><td>E-mail</td><td><input type="email" name="email" value="<?php echo htmlspecialchars($data['email']); ?>"></td></tr>
        <tr><td>Telefoonnummer</td><td><input type="tel" name="phone" value="<?php echo htmlspecialchars($data['phone']); ?>"></td></tr>
        <tr><td>Organisatie</td><td>
        <?php
        $user_organisation_id = getuserdata('organisation_id');
        $qry = "SELECT `id`, `name` FROM `organisations` ORDER BY `name`";
        $res = mysqli_query($db['link'], $qry);
        if (mysqli_num_rows($res)) {
            echo '<select name="organisation_id">';
            while ($row = mysqli_fetch_row($res)) {
                echo '<option value="' . $row[0] . '"';
                if (($data['organisation_id'] == $row[0]) || (empty($data['organisation_id']) && ($user_organisation_id == $row[0]))) {
                    echo ' selected';
                }
                echo '>';
                echo htmlspecialchars($row[1]);
                echo '</option>';
            }
            echo '</select>';
        }
        ?>
        </td></tr>
        <tr><td>Toegangsniveau</td><td><input type="number" name="accesslevel" value="<?php echo htmlspecialchars($data['accesslevel']); ?>" min="1" max="<?php echo $accesslevel; ?>" step="1" required> (1-<?php echo $accesslevel; ?>)</td></tr>
        <tr><td>Wachtwoord</td><td><input type="radio" name="password" value="keep" id="password-keep"<?php echo ($data['password'] == 'keep') ? ' checked' : ''; ?>> <label for="password-keep">Huidig wachtwoord behouden</label><br>
        <input type="radio" name="password" value="email" id="password-email"> <label for="password-email"<?php echo ($data['password'] == 'email') ? ' checked' : ''; ?>>Nieuw wachtwoord genereren en sturen per e-mail</label></td></tr>
        <tr><td></td><td><input type="submit" value="Opslaan"> <a href="?p=users">Annuleren</a></td></tr>
        </table>
        </form>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <h2>Toegangsniveau</h2>
        <p>Sorry, je werkt met een applicatie die nog niet af is. Hierdoor is het rechtensysteem nog niet direct gebruiksvriendelijk. Maar er is in ieder geval wel iets om te voorkomen dat je collega's hetzelfde kunnen als jij! Hoe het werkt is dat iedere gebruiker een toegangsniveau heeft. Op basis hiervan mag de gebruiker bepaalde functies wel of juist niet gebruiken. Hoe hoger het toegangsniveau, hoe meer de gebruiker mag. Raadpleeg onderstaande tabel om te zien wat een gebruiker mag als je een bepaald toegangsniveau toekent. Je kunt geen hoger toegangsniveau toekennen aan een gebruiker dan je zelf hebt, daardoor kun je ook geen gebruikers bewerken met een hoger toegangsniveau dan jezelf, ook als je wel het recht hebt om gebruikers te bewerken. Let op dat wanneer je je eigen toegangsniveau verlaagt, je dit nooit meer zelf kunt ophogen.</p>
        <?php
        require_once 'accesslevels.inc.php';
        asort($cfg_accesslevel);
        echo '<table>';
        echo '<tr><th>Functionaliteit</th><th>Minimaal vereist toegangsniveau</th></tr>';
        foreach ($cfg_accesslevel as $name => $lvl) {
            echo '<tr><td>' . $cfg_accessdescription[$name] . '</td><td>' . $lvl . '</td></tr>';
        }
        echo '</table>';
    }
    
    //users menu
    elseif ($_GET['p'] == 'users') {
        echo '<a href="?">&laquo; terug</a>';
        echo '<h1>Gebruikers</h1>';
        //message
        if ($store_success === TRUE) {
            echo '<p class="success">Wijziging doorgevoerd.</p>';
        }
        //add link
        echo '<a href="?p=users&amp;a=edit">Gebruiker toevoegen</a>';
        //list organisations in table
        $qry = "SELECT `users`.`id`, `username`, `users`.`name`, `organisations`.`name`, `accesslevel` FROM `users` 
        LEFT JOIN  `organisations`
        ON `organisations`.`id` = `users`.`organisation_id`
        ORDER BY `organisations`.`name`, `username` ASC";
        $res = mysqli_query($db['link'], $qry);
        if (mysqli_num_rows($res)) {
            echo '<table>';
            echo '<tr><th>Gebruikersnaam</th><th>Naam</th><th>Organisatie</th><th>Toegangsniveau</th><th></th><th></th></tr>';
            while ($row = mysqli_fetch_row($res)) {
                echo '<tr><td>';
                echo htmlspecialchars($row[1]);
                echo '</td><td>';
                echo htmlspecialchars($row[2]);
                echo '</td><td>';
                echo htmlspecialchars($row[3]);
                echo '</td><td>';
                echo htmlspecialchars($row[4]);
                echo '</td><td>';
                if ($accesslevel >= $row[4]) {
                    echo '<a href="?p=users&amp;a=edit&amp;id=' . $row[0] . '">Bewerken</a>';
                }
                echo '</td><td>';
                //if ($accesslevel >= $row[4]) {
                //TODO echo '<a href="?p=users&amp;a=delete&amp;id=' . $row[0] . '">Verwijderen</a>';
                //}
                echo '</td></tr>';
            }
            echo '</table>';
        }
        else {
            echo '<p class="info">Er zijn geen gebruikers.</p>';
        }
    }

    //edit datasets
    elseif (($_GET['p'] == 'datasets') && ($_GET['a'] == 'edit') && ($store_success !== TRUE)) {
        if (is_numeric($_GET['id'])) {
            echo '<h1>Gegevensset bewerken</h1>';
        }
        else {
            echo '<h1>Gegevensset toevoegen</h1>';
        }
        //messages
        if (in_array('name_empty', $messages)) {
            echo '<p class="warning">Naam mag niet leeg zijn.</p>';
        }
        if (in_array('organisation_id', $messages)) {
            echo '<p class="warning">Organisatie is ongeldig.</p>';
        }
        ?>
        <form method="post">
        <table class="invisible">
        <tr><td>Prefix</td><td><?php echo (is_numeric($_GET['id'])) ? htmlspecialchars($data['prefix']) : '(prefix wordt toegekend na opslaan)'; ?></td></tr>
        <tr><td>Naam dataset</td><td><input type="text" name="name" value="<?php echo htmlspecialchars($data['name']); ?>"></td></tr>
        <tr><td>Omschrijving</td><td><input type="text" name="description" value="<?php echo htmlspecialchars($data['description']); ?>"></td></tr>
        <tr><td></td><td><input type="submit" value="Opslaan"> <a href="?p=datasets">Annuleren</a></td></tr>
        </table>
        </form>
        <?php     
    }

    //datasets menu
    elseif ($_GET['p'] == 'datasets') {
        echo '<a href="?">&laquo; terug</a>';
        echo '<h1>Gegevenssets</h1>';
        echo '<p>Iedere gegevensset moet apart worden aangemeld. Hiermee is data terug te leiden naar een specifieke bron en kan hierop gefilterd worden.</p>';
        echo '<a href="?p=datasets&amp;a=edit">Gegevensset aanmelden</a>';
        //message
        if ($store_success === TRUE) {
            echo '<p class="success">Wijziging doorgevoerd.</p>';
        }
        //list organisations in table
        $qry = "SELECT `datasets`.`id`, `prefix`, `organisations`.`name`, `datasets`.`name`, `datasets`.`description` FROM `datasets` 
        LEFT JOIN  `organisations`
        ON `organisations`.`id` = `datasets`.`organisation_id`
        ORDER BY `organisations`.`name`, `prefix` ASC";
        $res = mysqli_query($db['link'], $qry);
        if (mysqli_num_rows($res)) {
            echo '<table>';
            echo '<tr><th>Organisatie</th><th>Prefix</th><th>Naam</th><th>Omschrijving</th><th>API</th><th></th><th></th><th></th></tr>';
            while ($row = mysqli_fetch_row($res)) {
                echo '<tr><td>';
                echo htmlspecialchars($row[2]);
                echo '</td><td>';
                echo htmlspecialchars($row[1]);
                echo '</td><td>';
                echo htmlspecialchars($row[3]);
                echo '</td><td>';
                echo htmlspecialchars($row[4]);
                echo '</td><td>';
                echo (($row[0] == getuserdata('default_dataset_id')) ? 'Ja' : '');
                echo '</td><td>';
                echo '<a href="?p=datasets&amp;a=edit&amp;id=' . $row[0] . '">Bewerken</a>';
                echo '</td><td>';
                //TODO echo '<a href="?p=datasets&amp;a=delete&amp;id=' . $row[0] . '">Verwijderen</a>';
                echo '</td><td>';
                echo '<a href="adddata.php?dataset_id=' . $row[0] . '">Data toevoegen aan gegevensset</a>';
                echo '</td></tr>';
            }
            echo '</table>';

            echo '<h2>API</h2>';
            echo '<p>Data kan geautomatiseerd worden aangeleverd via de API. Zie de <a href="docs/interfacebeschrijving_import.html" class="ext" target="_blank">interfacebeschrijving</a> voor meer informatie. Er moet een gegevensset gekozen worden waarin geautomatiseerd aangeleverde data wordt opgeslagen. Deze instelling is gekoppeld aan het gebruikersaccount. Wijzigingen zijn enkel van toepassing op nieuw aan te leveren data.</p>';
            echo '<p><b>Gegevensset voor API toegang:</b> ';
            //get selected dataset for API access
            $qry = "SELECT `id`, `name` FROM `datasets`
            WHERE `organisation_id` = '" . mysqli_real_escape_string($db['link'], getuserdata('organisation_id')) . "' 
            AND `id` = '" . mysqli_real_escape_string($db['link'], getuserdata('default_dataset_id')) . "'";
            $res = mysqli_query($db['link'], $qry);
            if (!mysqli_num_rows($res)) {
                echo '(niet geselecteerd)';
            }
            else {
                $data = mysqli_fetch_assoc($res);
                echo htmlspecialchars($data['name']);
            }
            echo ' <a href="admin.php?p=api_dataset">wijzigen</a></p>';
        }
        else {
            echo '<p class="info">Er zijn geen prefixen.</p>';
        }
    }

    //edit api dataset
    elseif (($_GET['p'] == 'api_dataset') && ($store_success !== TRUE)) {
        echo '<h1>API gegevensset instellen</h1>';
        //messages
        if (in_array('invalid', $messages)) {
            echo '<p class="warning">Geen geldige gegevensset geselecteerd.</p>';
        }
        ?>
        <form method="POST" enctype="multipart/form-data">
        
        <?php
        echo '<p><b>Gegevensset voor API:</b> <select name="dataset_id">';
        echo '<option value="NULL">(niet ingesteld)</option>';
        //get available datasets
        $qry = "SELECT `id`, `name` FROM `datasets`
        WHERE `organisation_id` = '" . mysqli_real_escape_string($db['link'], getuserdata('organisation_id')) . "'";
        $res = mysqli_query($db['link'], $qry);
        while ($row = mysqli_fetch_row($res)) {
            echo '<option value="' . $row[0] . '"';
            if ($data['dataset_id'] == $row[0]) {
                echo ' selected';
            }
            echo '>';
            echo htmlspecialchars($row[1]);
            echo '</option>';
        }
        echo '</select></p>';
        ?>
        <p><input type="submit" value="Opslaan"> <a href="?p=datasets">Annuleren</a></p>
        </form>
        <?php     
    }

    //edit api dataset
    elseif (($_GET['p'] == 'api_dataset') && ($store_success === TRUE)) {
        echo '<h1>API gegevensset instellen</h1>';
        echo '<p class="success">Wijziging doorgevoerd.</p>';
        echo '<a href="?p=datasets">&laquo; terug</a>';
    }

    //main admin menu
    else {
        echo '<h1>admin</h1>';
        echo '<ul>';
        if (accesslevelcheck('organisations')) {
            echo '<li><a href="?p=organisations">Organisaties beheren</a></li>';
        }
        if (accesslevelcheck('users')) {
            echo '<li><a href="?p=users">Gebruikers beheren</a></li>';
        }
        if (accesslevelcheck('datasets')) {
            echo '<li><a href="?p=datasets">Gegevenssets beheren</a></li>';
        }
        echo '</ul>';
    }
    ?>
    
</body>
</html>