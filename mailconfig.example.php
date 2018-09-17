<?php

//config file for sending emails
//rename to mailconfig.inc.php

//mailer
//choose to use PHP's mail() function or smtp. Defaults to mail.
$cfg['mail']['mailer'] = 'mail';
//$cfg['mail']['mailer'] = 'smtp';

//what name and address mails are to be sent from
$cfg['mail']['from_name'] = 'fietsviewer';
$cfg['mail']['from_email'] = 'noreply@example.com';

//smtp settigs
//only needs to be set when using SMTP.
$cfg['mail']['Host'] = 'smtp1.example.com;smtp2.example.com';  // Specify main and backup SMTP servers
$cfg['mail']['SMTPAuth'] = true;                               // Enable SMTP authentication
$cfg['mail']['Username'] = 'user@example.com';                 // SMTP username
$cfg['mail']['Password'] = 'secret';                           // SMTP password
$cfg['mail']['SMTPSecure'] = 'tls';                            // Enable TLS encryption, `ssl` also accepted
$cfg['mail']['Port'] = 587;                                    // TCP port to connect to

$cfg['mail']['subject']['lostpass'] = 'fietsviewer wachtwoord';
$cfg['mail']['message']['lostpass'] = '<p>Beste {{NAME}},</p><p>Je nieuwe wachtwoord voor fietsviewer is:<br>{{PASSWORD}}</p><p>Met vriendelijke groeten,<br>fietsviewer</p>';

?>