<?php
/*
    openrouteserver - Open source NDW route configurator en server
    Copyright (C) 2014 Jasper Vries
	repurposed for:
 	fietsviewer - grafische weergave van fietsdata
    2019 Gemeente Den Haag, Netherlands

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

//function to write log file
if (!function_exists('write_log')) { function write_log ($string = '', $verbose = FALSE) {
	include 'config.inc.php';
	if (($verbose == TRUE) && ($cfg['logging']['verbose'] === FALSE)) {
		//do not log verbose output if it's disabled
		return;
	}
	if (!empty($string)) {
		if (file_exists($cfg['logging']['logfile'])) {
			$hdl = fopen($cfg['logging']['logfile'], 'r');
			$oldlog = fread($hdl, $cfg['logging']['maxlogfilesize']);
			fclose($hdl);
		}
		else $oldlog = '';
		$log = date('Y-m-d H:i:s') . "\t";
		$log .= $_SERVER['SCRIPT_NAME'] . "\t";
		$log .= "\t" . $string . PHP_EOL . $oldlog;
		$hdl = fopen($cfg['logging']['logfile'], 'w');
		fwrite($hdl, $log);
		fclose($hdl);
	}
}}
?>