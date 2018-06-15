<?php
/*
 	fietsviewer - grafische weergave van fietsdata
    Copyright (C) 2018 Jasper Vries, Gemeente Den Haag
 
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
	include('password_compat/lib/password.php');
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
			$token_length = 32;
			$token_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
			$token = '';
			for ($i = 0; $i < $token_length; $i++) {
				$token .= substr($token_chars, mt_rand(0, strlen($token_chars) - 1), 1);
			}

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
process login request
*/
$errors = array();
if (!empty($_POST)) {
	if (user_login($_POST['username'], $_POST['password']) === TRUE) {
		//redirect to index
		header('Location: index.php');
	}
	else {
		//show error
		$errors[] = 'login';
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
	
	<?php include('menu.inc.php'); ?>

	<h1>aanmelden</h1>

	<?php
	if (in_array('login', $errors)) {
		echo '<p>Gebruikersnaam/wachtwoord onjuist</p>';
	}
	?>
	<form method="POST">
		Gebruikersnaam: <input type="text" name="username">
		<br>
		Wachtwoord: <input type="password" name="password">
		<br>
		<input type="submit" value="Aanmelden">
	</form>
</body>
</html>