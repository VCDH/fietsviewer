<?php
//fietsviewer configuration file
$cfg['cookie']['name'] = 'fietsviewer';
$cfg['cookie']['expire'] = 30*24*60*60;

$cfg['upload']['dir'] = 'upload/';

$cfg['account']['pass_minlength'] = 8;
$cfg['account']['username_regex'] = '/[a-z0-9]+([@-_.]+[a-z0-9]+)*/i';
$cfg['account']['email_regex'] = '/.+@.+\.[a-z]{2}/i';
?>