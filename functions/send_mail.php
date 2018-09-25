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
* function to generate an email
* bool send_mail ( string $to , string $subject , string $message )
*/

function send_mail($to, $subject, $message) {
    require_once 'PHPMailer/src/PHPmailer.php';
	require_once 'PHPMailer/src/SMTP.php';
	require_once 'PHPMailer/src/Exception.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    if (file_exists('mailconfig.inc.php')) {
        require_once 'mailconfig.inc.php';
    }
    else {
        return FALSE;
    }
    //setup smtp
    if ($cfg['mail']['mailer'] == 'smtp') {
        $mail->isSMTP();
        $mail->Host = $cfg['mail']['Host'];
        $mail->SMTPAuth = $cfg['mail']['SMTPAuth'];
        $mail->Username = $cfg['mail']['Username'];
        $mail->Password = $cfg['mail']['Password'];
        $mail->SMTPSecure = $cfg['mail']['SMTPSecure'];
        $mail->Port =$cfg['mail']['Port']; 
    }
    else {
        $mail->isMail();
    }
    //set from and to
    $mail->setFrom($cfg['mail']['from_email'], $cfg['mail']['from_name']);
    $mail->addAddress($to);
    //set message
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $message;
    $mail->AltBody = $mail->html2text($message);
    //send mail
    return $mail->send();
}
?>