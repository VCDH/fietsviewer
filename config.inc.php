<?php
//fietsviewer configuration file
$cfg['cookie']['name'] = 'fietsviewer'; //name of the cookie set by fietsviewer
$cfg['cookie']['expire'] = 30*24*60*60; //maximum life of the cookie

$cfg['upload']['dir'] = 'upload/'; //configure the upload directory. It is created by install.php; rerun install.php when changing this value, or make sure to create the directory yourself

$cfg['account']['pass_minlength'] = 8; //set minimum password length
$cfg['account']['username_regex'] = '/[a-z0-9]+([@-_.]+[a-z0-9]+)*/i'; //regex that a username must pass; implies a username length check
$cfg['account']['email_regex'] = '/.+@.+\.[a-z]{2}/i'; //regex that an e-mail address must pass; by default just a basic check for @ and a tld

$cfg['hypervisor']['user_activated'] = TRUE; //set whether The Hypervisor is called after specific user actions. If you disable this, you must call The Hypervisor periodically via a cronjob to ensure that background tasks (processing data) are still executed. If you do run a cronjob, it's not required to change this setting.
?>