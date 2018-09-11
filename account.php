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
* process password change
*/
if (($_GET['do'] == 'password') && (!empty($_POST))) {
    //check if both new passwords are equal
    if ($_POST['new_password1'] == $_POST['new_password2']) {
        //check new password length
		if (strlen($_POST['new_password1']) >= $cfg['account']['pass_minlength']) {
            //check old password
            include('password_compat/lib/password.php');
            //get password by username
            $qry = "SELECT `id`, `password` FROM `users` WHERE
            `id` = '" . getuserdata('id') . "'";
            $res = mysqli_query($db['link'], $qry);
            if (mysqli_num_rows($res) == 1) {
                //user exists
                $data = mysqli_fetch_assoc($res);
                //check password
                if (!password_verify($_POST['old_password'], $data['password'])) {
                    //password incorrect
                    $oud_wachtwoord_fout = TRUE;
                }
                else {
                    //store new password
                    $new_password = password_hash($_POST['new_password1'], PASSWORD_DEFAULT);
                    //query
                    $sql = "UPDATE `".$db['prefix']."users`
                    SET `password` = '" . mysqli_real_escape_string($db['link'], $new_password) . "'
                    WHERE `id` = '" . getuserdata('id') . "'";
                    $wachtwoord_gewijzigd = mysqli_query($db['link'], $sql);
                }
            }
		}
		else {
			//password length insufficient
			$wachtwoord_lengte = TRUE;
		}
    }
    else {
        //passwords don't match
        $nieuw_wachtwoord_fout = TRUE;
    }
}
/*
* process username change
*/
elseif (($_GET['do'] == 'username') && (!empty($_POST))) {
	$username_gewijzigd = TRUE;
	//check fields
	if (!preg_match($cfg['account']['username_regex'], $_POST['username'])) {
        $username_gewijzigd = FALSE;
    }
    //check if exists
    $qry = "SELECT `id` FROM `users` 
    WHERE `username` = '" . mysqli_real_escape_string($db['link'], $_POST['username']) . "'";
    $res = mysqli_query($db['link'], $qry);
    if (mysqli_num_rows($res)) {
        $username_gewijzigd = FALSE;
    }
	//save data
	if ($username_gewijzigd == TRUE) {
		//query om rij aan te passen
		$qry = "UPDATE `users`
		SET `username` = '" . mysqli_real_escape_string($db['link'], $_POST['username']) . "'
        WHERE `id` = '" . getuserdata('id') . "'";
		//voer query uit
		$username_gewijzigd = mysqli_query($db['link'], $qry);
	}
}
/*
* process username change
*/
elseif (($_GET['do'] == 'email') && (!empty($_POST))) {
	$email_gewijzigd = TRUE;
	//check fields
	if (!preg_match($cfg['account']['email_regex'], $_POST['email'])) {
        $email_gewijzigd = FALSE;
    }
	//save data
	if ($email_gewijzigd == TRUE) {
		//query om rij aan te passen
		$qry = "UPDATE `users`
		SET `email` = '" . mysqli_real_escape_string($db['link'], $_POST['email']) . "'
        WHERE `id` = '" . getuserdata('id') . "'";
		//voer query uit
		$email_gewijzigd = mysqli_query($db['link'], $qry);
	}
}
/*
* process user details change
*/
elseif (($_GET['do'] == 'userprofile') && (!empty($_POST))) {
	$fieldcheck = TRUE;
	//check fields
	if (empty($_POST['name'])) $fieldcheck = FALSE;
	//save data
	if ($fieldcheck == TRUE) {
		//query om rij aan te passen
		$qry = "UPDATE `users`
		SET `name` = '" . mysqli_real_escape_string($db['link'], $_POST['name']) . "',
		`phone` = '" . mysqli_real_escape_string($db['link'], $_POST['phone']) . "'
        WHERE `id` = '" . getuserdata('id') . "'";
		//voer query uit
		$userprofile_gewijzigd = mysqli_query($db['link'], $qry);
	}
}

/*
* display page
*/
?>
<!DOCTYPE html>
<html lang="nl-nl">
<head>
	<title>fietsv&#7433;ewer - account</title>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="style.css">
	<script src="jquery/jquery-3.3.1.min.js"></script>
</head>
<body>
	
    <?php include('menu.inc.php'); ?>

    <?php
    //change password
    if (($_GET['do'] == 'password') && ($wachtwoord_gewijzigd !== TRUE)) {
        echo '<h1>wachtwoord wijzigen</h1>';
        echo '<p>Geef je huidige wachtwoord en tweemaal het nieuwe wachtwoord op om je wachtwoord te wijzigen. Het nieuwe wachtwoord moet minstens ' . $cfg['account']['pass_minlength'] . ' tekens lang zijn.';
        //messages
        if ($nieuw_wachtwoord_fout === TRUE) {
			echo '<p class="error">De ingevulde nieuwe wachtwoorden zijn niet gelijk.</p>';
		}
		if ($oud_wachtwoord_fout === TRUE) {
			echo '<p class="error">De oude wachtwoord is niet juist.</p>';
		}
		if ($wachtwoord_lengte === TRUE) {
			echo '<p class="error">Het nieuwe wachtwoord is te kort.</p>';
		}
		?>
		
		<form method="post">
		<table class="invisible">
            <tr><td>Oud wachtwoord:</td><td><input type="password" name="old_password"></td></tr>
            <tr><td>Nieuw wachtwoord:</td><td><input type="password" name="new_password1"></td></tr>
            <tr><td>Herhaal wachtwoord:</td><td><input type="password" name="new_password2"></td></tr>
            <tr><td></td><td><input type="submit" value="Wijzig wachtwoord"> <a href="?">Annuleren</a></td></tr>
		</table>
		</form>
		<?php
    }

    //change username
    elseif (($_GET['do'] == 'username') && ($username_gewijzigd !== TRUE)) {
        echo '<h1>gebruikersnaam wijzigen</h1>';
        echo '<p>Hieronder kun je je gebruikersnaam wijzigen. Je kunt elke gebruikersnaam kiezen die nog niet in gebruik is en voldoet aan de expressie <i>' . htmlspecialchars($cfg['account']['username_regex']) . '</i></p>';
        //messages
        if ($username_gewijzigd === FALSE) {
            echo '<p class="error">Deze gebruikersnaam is niet toegestaan of bestaat al.</p>';
        }
        //get current username
        $sql = "SELECT
        `username`
        FROM `users`
        WHERE `id` = '" . getuserdata('id') . "'";
        $result = mysqli_query($db['link'], $sql);
        $data = mysqli_fetch_assoc($result);
        ?>
        
        <form method="post">
        <table class="invisible">
        <tr><td>Huidige gebruikersnaam:</td><td><?php echo $data['username']; ?></td></tr>
        <tr><td>Nieuwe gebruikersnaam:</td><td><input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username']); ?>"></td></tr>
        <tr><td></td><td><input type="submit" value="Opslaan"> <a href="?">Annuleren</a></td></tr>
        </table>
        </form>
        <?php
    }

    //change email
    elseif (($_GET['do'] == 'email') && ($email_gewijzigd !== TRUE)) {
        echo '<h1>e-mailadres wijzigen</h1>';
        echo '<p>Hieronder kun je je e-mailadres wijzigen. Je e-mailadres wordt gebruikt om weer toegang te kunnen krijgen tot je account wanneer je je wachtwoord bent vergeten. NB: er wordt niet gecontroleerd of het e-mailadres wat je invult klopt!</p>';
        //messages
        if ($email_gewijzigd === FALSE) {
            echo '<p class="error">Vul een e-mailadres in. Dit moet voldoen aan de expressie <i>' . htmlspecialchars($cfg['account']['email_regex']) . '</i></p>';
        }
        //get current address
        $sql = "SELECT
        `email`
        FROM `users`
        WHERE `id` = '" . getuserdata('id') . "'";
        $result = mysqli_query($db['link'], $sql);
        $data = mysqli_fetch_assoc($result);
        ?>
        
        <form method="post">
        <table class="invisible">
        <tr><td>Huidig e-mailadres:</td><td><?php echo $data['email']; ?></td></tr>
        <tr><td>Nieuw e-mailadres:</td><td><input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email']); ?>"></td></tr>
        <tr><td></td><td><input type="submit" value="Opslaan"> <a href="?">Annuleren</a></td></tr>
        </table>
        </form>
        <?php
    }

    //change personal details
    elseif (($_GET['do'] == 'userprofile') && ($userprofile_gewijzigd !== TRUE)) {
        echo '<h1>gebruikersprofiel wijzigen</h1>';
        echo '<p>Wijzig via deze pagina de gegevens in je gebruikersprofiel. Deze worden op dit moment nergens voor gebruikt, maar kunnen handig zijn voor de beheerder binnen jouw organisatie.</p>';
        //check if post data or get form database
		if (!empty($_POST)) {
			$data['name'] = htmlspecialchars($_POST['name']);
			$data['phone'] = htmlspecialchars($_POST['phone']);
		}
		else {
            //get data from database
			$sql = "SELECT
			`name`, `phone`
			FROM `users`
			WHERE `id` = '" . getuserdata('id') . "'";
			$result = mysqli_query($db['link'], $sql);
			if (mysqli_num_rows($result)) {
				$row = mysqli_fetch_row($result);
				$data['name'] = htmlspecialchars($row[0]);
				$data['phone'] = htmlspecialchars($row[1]);
			}
		}
        //messages
		if ($naam_fout === TRUE) {
			echo '<p class="error">Naam kan niet leeg zijn</p>';
		}
		?>
		
		<form method="post">
		<table>
		<tr><td>Naam:</td><td><input type="text" name="name" value="<?php echo $data['name']; ?>"></td></tr>
		<tr><td>Telefoonnummer:</td><td><input type="tel" name="phone" value="<?php echo $data['phone']; ?>"></td></tr>
		<tr><td></td><td><input type="submit" value="Opslaan"> <a href="?">Annuleren</a></td></tr>
		</table>
		</form>
		<?php
	}


    //query user info for main view
    else {
        echo '<h1>account</h1>';
        echo '<p>Via deze pagina kun je je wachtwoord en andere gegevens wijzigen.</p>';
        
        $qry = "SELECT `users`.`username` AS `username`, `users`.`email` AS `email`, `users`.`phone` AS `phone`, `users`.`name` AS `name`, `organisations`.`name` AS `organisation` FROM `users`
        LEFT JOIN `organisations`
        ON `users`.`organisation_id` = `organisations`.`id`
        WHERE `users`.`id` = '" . getuserdata('id') . "'";
        $res = mysqli_query($db['link'], $qry);
        if ($data = mysqli_fetch_assoc($res)) {
            ?>
                <table>
                    <tr><td>gebruikersnaam</td><td><?php echo htmlspecialchars($data['username']); ?></td><td><a href="?do=username">gebruikersnaam wijzigen</a></td></tr>
                    <tr><td>wachtwoord</td><td>********</td><td><a href="?do=password">wachtwoord wijzigen</a></td></tr>
                    <tr><td>e-mailadres</td><td><?php echo htmlspecialchars($data['email']); ?></td><td><a href="?do=email">e-mailadres wijzigen</a></td></tr>
                    <tr><td>naam</td><td><?php echo htmlspecialchars($data['name']); ?></td><td rowspan="2"><a href="?do=userprofile">gegevens wijzigen</a></td></tr>
                    <tr><td>telefoonnummer</td><td><?php echo htmlspecialchars($data['phone']); ?></td></tr>
                    <tr><td>organisatie</td><td><?php echo htmlspecialchars($data['organisation']); ?></td><td></td></tr>
                </table>
            <?php
        }
        else {
            echo '<p class="error">Er is een fout opgetreden bij het ophalen van gebruikersdata.</p>';
        }
    }
    ?>
    
</body>
</html>