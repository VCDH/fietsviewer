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

/*
* send new password
* should always return TRUE unless config is missing
*/
function reset_password($username) {
	require('dbconnect.inc.php');
	require('config.inc.php');
	require_once('functions/get_token.php');
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
	$qry = "SELECT `email`, `name` FROM `users` WHERE `username` = '" . mysqli_real_escape_string($db['link'], $username) . "' LIMIT 1";
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
		WHERE `username` = '" . mysqli_real_escape_string($db['link'], $username) . "'
		LIMIT 1";
		mysqli_query($db['link'], $sql);
		//prepare email
		$to = $data['email'];
		$subject = $cfg['mail']['subject']['lostpass'];
		$message = $cfg['mail']['message']['lostpass'];
		//$subject = str_replace(array('{{NAME}}', '{{PASSWORD}}'), array(htmlspecialchars($data[1]), $new_password), $subject);
		$message = str_replace(array('{{NAME}}', '{{PASSWORD}}'), array(htmlspecialchars($data['name']), $new_password), $message);
		//send email
		send_mail($to, $subject, $message);
	}
	return TRUE;
}
?>