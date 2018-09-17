<?php

use PHPMailer\PHPMailer\PHPMailer;
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

require_once('getuserdata.fct.php');
require_once('functions/get_token.php');

/*
* process logout
*/
function user_logout() {
	require('dbconnect.inc.php');
	require('config.inc.php');

	//invalidate token
	$qry = "DELETE FROM `user_login_tokens`
	WHERE `user_id` = '" . mysqli_real_escape_string($db['link'], getuserdata('id')) . "'
	`token` = '" . mysqli_real_escape_string($db['link'], getuserdata('token')) . "'";
	mysqli_query($db['link'], $qry);
	//unset cookie
	setcookie($cfg['cookie']['name'], '', time() - 3600, '/');
	return TRUE;
}

/*
* process login
*/
function user_login($username, $password) {
	require('dbconnect.inc.php');
	require('config.inc.php');
	//hash password
	include_once('password_compat/lib/password.php');
	//get password by username
	$qry = "SELECT `id`, `password` FROM `users` WHERE
	`username` = '" . mysqli_real_escape_string($db['link'], $username) . "'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res) == 1) {
		//user exists
		$data = mysqli_fetch_assoc($res);
		//check password
		if (password_verify($_POST['password'], $data['password'])) {
			//generate token
			$token = get_token(32);
			//add token to db
			$qry = "INSERT INTO `user_login_tokens` SET 
			`user_id` = '" . mysqli_real_escape_string($db['link'], $data['id']) . "',
			`token` = '" . mysqli_real_escape_string($db['link'], $token) . "',
			`date_create` = NOW(),
			`date_lastchange` = NOW(),
			`ip` = '" . mysqli_real_escape_string($db['link'], $_SERVER['REMOTE_ADDR']) . "'";
			if (!mysqli_query($db['link'], $qry)) {
				return FALSE;
			}

			//set cookie
			setcookie($cfg['cookie']['name'], serialize(array($data['id'], $token)), time() + $cfg['cookie']['expire'], '/');

			return TRUE;
		}
	}
	return FALSE;
}

/*
* send new password
* should always return TRUE
*/
function reset_password($username) {
	require('dbconnect.inc.php');
	require('config.inc.php');
	if (file_exists('mailconfig.inc.php')) {
        require_once 'mailconfig.inc.php';
        require_once 'functions/send_mail.php';
    }
    else {
        return FALSE;
    }
	//hash password
	include_once('password_compat/lib/password.php');
	//get email with user
	$qry = "SELECT `email`, `name` FROM `users` WHERE `username` = '" . mysqli_real_escape_string($db['link'], $_POST['username']) . "' LIMIT 1";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$data = mysqli_fetch_assoc($res);
		//generate new password
		$new_password = get_token(10);
		//set new password
		$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
		//query
		$sql = "UPDATE `".$db['prefix']."users`
		SET `password` = '" . mysqli_real_escape_string($db['link'], $new_password_hash) . "'
		WHERE `username` = '" . mysqli_real_escape_string($db['link'], $_POST['username']) . "'
		LIMIT 1";
		mysqli_query($db['link'], $sql);
		//prepare email
		$to = $data['email'];
		//TODO
		$subject = $cfg['mail']['subject']['lostpass'];
		$message = $cfg['mail']['message']['lostpass'];
		//$subject = str_replace(array('{{NAME}}', '{{PASSWORD}}'), array(htmlspecialchars($data[1]), $new_password), $subject);
		$message = str_replace(array('{{NAME}}', '{{PASSWORD}}'), array(htmlspecialchars($data['name']), $new_password), $message);
		//send email
		send_mail($to, $subject, $message);
	}
	return TRUE;
}

/*
* process login request
*/
$messages = array();
if (!empty($_POST)) {
	if ($_GET['do'] == 'lostpass') {
		//check if not an empty field
		if (empty($_POST['username'])) {
			$messages[] = 'empty';
			$lostpasssuccess = FALSE;
		}
		//send email
		$lostpasssuccess = reset_password($_POST['username']);
	}
	elseif (user_login($_POST['username'], $_POST['password']) === TRUE) {
		//redirect to index
		header('Location: index.php');
	}
	else {
		//show error
		$messages[] = 'login';
	}
}

/*
* process logout request
*/
if ($_GET['a'] == 'logout') {
	user_logout();
	header('Location: index.php');
}

?>


<!DOCTYPE html>
<html lang="nl-nl">
<head>
	<title>fietsv&#7433;ewer - aanmelden</title>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="style.css">
	<script src="jquery/jquery-3.3.1.min.js"></script>
</head>
<body>
	
	<?php 
	include('menu.inc.php'); 

	//lost password page
	if ($_GET['do'] == 'lostpass') {
		echo '<h1>wachtwoord vergeten</h1>';
		
		if (in_array('empty', $messages)) {
			echo '<p class="info">Vul een gebruikersnaam in.</p>';
		}
		elseif ($lostpasssuccess === FALSE) {
			echo '<p class="error">Kan wachtwoord niet aanvragen.</p>';
		}
		elseif ($lostpasssuccess === TRUE) {
			echo '<p class="success">Wanneer er een e-mailadres bij de opgegeven gebruikersnaam is geregisteerd, is een nieuw wachtwoord naar dit e-mailadres gezonden. Het kan enkele minuten duren voordat het nieuwe wachtwoord wordt ontvangen. Niets ontvangen? Kijk dan ook even in je spam-map!</p>';
		}
		
		?>
		<p>Wachtwoord vergeten? Vul hieronder je gebruikersnaam in om een nieuw wachtwoord per e-mail toegestuurd te krijgen. Hiervoor moet natuurlijk wel een e-mailadres in je account geregistreerd zijn. Lukt het niet? Neem dan contact op met een beheerder van fietsv&#7433;ewer binnen jouw organisatie. Deze kan het geregisteerde e-mailadres voor je wijzigen.</p>
		<form method="POST">
		<table class="invisible">
			<tr><td>Gebruikersnaam</td><td><input type="text" name="username"></td></tr>
			<tr><td></td><td><input type="submit" value="Nieuw wachtwoord aanvragen"> <a href="?">Annuleren</a></td></tr>
		</table>
		</form>
		<?php
	}

	//main login page
	else {
		echo '<h1>aanmelden</h1>';

		if (in_array('login', $messages)) {
			echo '<p class="error">Gebruikersnaam/wachtwoord onjuist</p>';
		}
		
		?>
		<form method="POST">
		<table class="invisible">
			<tr><td>Gebruikersnaam</td><td><input type="text" name="username"></td></tr>
			<tr><td>Wachtwoord</td><td><input type="password" name="password"></td></tr>
			<tr><td></td><td><input type="submit" value="Aanmelden"></td></tr>
		</table>
		</form>
		<p><a href="?do=lostpass">Wachtwoord vergeten</a></p>

		<?php
	}
	?>
	
</body>
</html>