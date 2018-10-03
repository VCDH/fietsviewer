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

$config_file_name = 'dbconfig.inc.php';
$license_file_name = 'COPYING';
$database_server = 'localhost';
$database_user = '';
$database_password = '';
$database_database = 'fietsviewer';

//check if cli
function detect_cli() {
	if (php_sapi_name() === 'cli') {
        return TRUE;
    }
	if (defined('STDIN')) {
        return TRUE;
    }
	if (empty($_SERVER['REMOTE_ADDR']) && !isset($_SERVER['HTTP_USER_AGENT']) && count($_SERVER['argv']) > 0) {
        return TRUE;
    }
    return false;
}
if (detect_cli() !== TRUE) {
	echo 'Kan alleen uitvoeren vanaf opdrachtregel';
	exit;
}

function cli_input() {
	$handle = fopen ("php://stdin","r");
	$line = fgets($handle);
	fclose($handle);
	return trim($line);
}

function cli_exit() {
	echo PHP_EOL;
	echo 'Installatie afgebroken' . PHP_EOL;
	exit;
}

function write_empty_lines($num) {
	for ($i = 0; $i < $num; $i++) {
		echo PHP_EOL;
	}
}

/*
* main startup screen
*/
section_start:
write_empty_lines(10);
echo '###############################################################################' . PHP_EOL;
echo '###                                                                         ###' . PHP_EOL;
echo '###                     fietsviewer installatieprogramma                    ###' . PHP_EOL;
echo '###                                                                         ###' . PHP_EOL;
echo '###############################################################################' . PHP_EOL;
echo '###                                                                         ###' . PHP_EOL;
echo '### Welkom bij het installatieprogramma van fietsviewer.                    ###' . PHP_EOL;
echo '###                                                                         ###' . PHP_EOL;
echo '### Via dit installatieprogramma wordt een configuratiebestand aangemaakt   ###' . PHP_EOL;
echo '### en de noodzakelijke database en databasetabellen aangemaakt.            ###' . PHP_EOL;
echo '### Daarnaast wordt een beheeraccount voor de grafische interface gemaakt.  ###' . PHP_EOL;
echo '###                                                                         ###' . PHP_EOL;
echo '### Hierna is fietsviewer klaar voor gebruik en kan data worden ingelezen   ###' . PHP_EOL;
echo '### via de grafische interface of de push interface.                        ###' . PHP_EOL;
echo '###                                                                         ###' . PHP_EOL;
echo '###                                                                         ###' . PHP_EOL;
echo '### fietsviewer is vrij beschikbaar onder de voorwaarden van de             ###' . PHP_EOL;
echo '### GNU General Public License versie 3 of (naar keuze) elke hogere versie. ###' . PHP_EOL;
echo '###                                                                         ###' . PHP_EOL;
echo '### fietsviewer Copyright (C) 2018 Jasper Vries, Gemeente Den Haag          ###' . PHP_EOL;
echo '### This program comes with ABSOLUTELY NO WARRANTY. This is free software,  ###' . PHP_EOL;
echo '### and you are welcome to redistribute it under certain conditions. Type   ###' . PHP_EOL;
echo '### 1 for details.                                                          ###' . PHP_EOL;
echo '###                                                                         ###' . PHP_EOL;
echo '###############################################################################' . PHP_EOL;
echo ' 1. Bekijk licentietekst' . PHP_EOL;
echo ' 2. Akkoord met licentievoorwaarden en doorgaan' . PHP_EOL;
echo ' 3. Niet akkoord (afsluiten)' . PHP_EOL;
echo 'Geef keuze op, gevolgd door enter:';
$input = cli_input();

if ($input == '1') {
	goto section_license_text;
}
elseif ($input == '2') {
	goto section_check_config;
}
elseif ($input == '3') {
	cli_exit();
}
else {
	goto section_start;
}

/*
* license text
*/
section_license_text:
write_empty_lines(20);
	echo '===============================================================================' . PHP_EOL;
	echo 'Licentietekst (druk op een willekeurige toets om verder te gaan)' . PHP_EOL;
	echo '===============================================================================' . PHP_EOL;
	write_empty_lines(2);
if (file_exists($license_file_name)) {
	$linecount = 0;
	$handle = fopen($license_file_name, 'rb');
	while ($line = fgets($handle)) {
		echo trim($line) . PHP_EOL;
		$linecount++;
		if (($linecount % 20 == 0) && ($linecount > 0)) {
			cli_input();
			echo '---(page break)---' . PHP_EOL;
		}
	}
}
else {
	echo 'Kan licentietekst niet weergeven.' . PHP_EOL;
}

/*
* check if database config exists and ask to load that
*/
section_check_config:
if (file_exists($config_file_name)) {
	//parse file
	$handle = fopen($config_file_name, 'rb');
	while ($line = fgets($handle)) {
		//match line
		if (preg_match('/.*\$db\[\'(host|user|pass|database)\'\]\h*=\h*\'(.*)\';/U', $line, $matches) === 1) {
			if ($matches[1] == 'host') {
				$database_server = $matches[2];
			}
			elseif ($matches[1] == 'user') {
				$database_user = $matches[2];
			}
			elseif ($matches[1] == 'pass') {
				$database_password = $matches[2];
			}
			elseif ($matches[1] == 'database') {
				$database_database = $matches[2];
			}
		}
	}
	fclose($handle);
	
	write_empty_lines(20);
	echo '===============================================================================' . PHP_EOL;
	echo 'MySQL/MariaDB servergegevens aangetroffen' . PHP_EOL;
	echo '===============================================================================' . PHP_EOL;
	echo 'Server         : ' . $database_server . PHP_EOL;
	echo 'Gebruikersnaam : ' . $database_user . PHP_EOL;
	echo 'Wachtwoord     : ********' . PHP_EOL;
	echo 'Database       : ' . $database_database . PHP_EOL;	
	echo PHP_EOL;
	echo 'Deze gegevens gebruiken?' . PHP_EOL;
	echo ' 1. Ja' . PHP_EOL;
	echo ' 2. Nee (opnieuw invoeren)' . PHP_EOL;
	echo ' 3. Afsluiten' . PHP_EOL;
	section_db_found:
	echo 'Geef keuze op, gevolgd door enter:';
	$input = cli_input();

	if ($input == '1') {
		goto section_db_create;
	}
	elseif ($input == '2') {
		goto section_db;
	}
	elseif ($input == '3') {
		cli_exit();
	}
	else {
		goto section_db_found;
	}
}

/*
* section to enter database credentials
*/
section_db:

write_empty_lines(20);
echo '===============================================================================' . PHP_EOL;
echo 'MySQL/MariaDB servergegevens' . PHP_EOL;
echo '===============================================================================' . PHP_EOL;
section_db_server:
echo PHP_EOL;
echo 'Database server:' . PHP_EOL;
echo ' 1. localhost' . PHP_EOL;
echo ' 2. Aangepast serveradres opgeven' . PHP_EOL;
echo 'Geef keuze op, gevolgd door enter:';

$input = cli_input();

if ($input == '1') {
	$database_server = 'localhost';
}
elseif ($input == '2') {
	echo PHP_EOL;
	echo 'Aangepast serveradres' . PHP_EOL;
	echo 'Geef serveradres op:';
	$database_server = cli_input();
}
else {
	goto section_db_server;
}

echo PHP_EOL;
echo 'MySQL/MariaDB gebruikersnaam' . PHP_EOL;
echo 'Geef gebruikersnaam op:';
$database_user = cli_input();

echo PHP_EOL;
echo 'MySQL/MariaDB wachtwoord' . PHP_EOL;
echo 'Geef wachtwoord op:';
$database_password = cli_input();

section_db_database:
echo PHP_EOL;
echo 'Database' . PHP_EOL;
echo 'Geef op welke database door fietsviewer gebruikt wordt.' . PHP_EOL;
echo 'Als deze niet bestaat, zal verderop geprobeerd worden deze aan te maken.' . PHP_EOL;
echo ' 1. Gebruik standaard (`fietsviewer`)' . PHP_EOL;
echo ' 2. Aangepast serveradres opgeven' . PHP_EOL;
echo 'Geef keuze op, gevolgd door enter:';
$input = cli_input();

if ($input == '1') {
	$database_database = 'fietsviewer';
}
elseif ($input == '2') {
	echo PHP_EOL;
	echo 'Aangepast serveradres' . PHP_EOL;
	echo 'Geef serveradres op:';
	$database_database = cli_input();
}
else {
	goto section_db_database;
}

write_empty_lines(20);
echo '===============================================================================' . PHP_EOL;
echo 'MySQL/MariaDB servergegevens' . PHP_EOL;
echo '===============================================================================' . PHP_EOL;
section_db_confirm:
echo 'Server         : ' . $database_server . PHP_EOL;
echo 'Gebruikersnaam : ' . $database_user . PHP_EOL;
echo 'Wachtwoord     : ' . $database_password . PHP_EOL;
echo 'Database       : ' . $database_database . PHP_EOL;
echo PHP_EOL;
echo 'Zijn deze gegevens juist?' . PHP_EOL;
echo ' 1. Ja' . PHP_EOL;
echo ' 2. Nee (opnieuw invoeren)' . PHP_EOL;
echo ' 3. Afsluiten' . PHP_EOL;
echo 'Geef keuze op, gevolgd door enter:';
$input = cli_input();

if ($input == '2') {
	goto section_db;
}
elseif ($input == '3') {
	cli_exit();
}
elseif ($input != '1') {
	goto section_db_confirm;
}

/*
* check connection and create database
*/
section_db_create:
write_empty_lines(20);
echo '===============================================================================' . PHP_EOL;
echo 'Database aanmaken' . PHP_EOL;
echo '===============================================================================' . PHP_EOL;
//connect to database
$db['link'] = @mysqli_connect($database_server, $database_user, $database_password);
//check connection
if (!$db['link']) {
	section_db_connect_error:
	echo 'Kan niet verbinden met database.' . PHP_EOL;
	echo 'Oorzaak: ' . mysqli_connect_error() . PHP_EOL;
	echo ' 1. Databasegegevens opnieuw invoeren' . PHP_EOL;
	echo ' 2. Afsluiten' . PHP_EOL;
	echo 'Geef keuze op, gevolgd door enter:';
	$input = cli_input();

	if ($input == '1') {
		goto section_db;
	}
	elseif ($input == '2') {
		cli_exit();
	}
	else {
		goto section_db_connect_error;
	}
}

$qry = "SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA` WHERE `SCHEMA_NAME` = '" . mysqli_real_escape_string($db['link'], $database_database) . "'";
$res = mysqli_query($db['link'], $qry);
if (mysqli_num_rows($res) == 1) {
	section_db_exists:
	echo 'Database `' . mysqli_real_escape_string($db['link'], $database_database) . '` bestaat al.' . PHP_EOL;
	echo ' 1. Doorgaan' . PHP_EOL;
	echo ' 2. Andere database opgeven' . PHP_EOL;
	echo ' 3. Afsluiten' . PHP_EOL;
	echo 'Geef keuze op, gevolgd door enter:';
	$input = cli_input();

	if ($input == '2') {
		goto section_db_database;
	}
	elseif ($input == '3') {
		cli_exit();
	}
	elseif ($input != '1') {
		goto section_db_exists;
	}
}
else {
	//db doesn't exists
	$qry = "CREATE DATABASE `" . mysqli_real_escape_string($db['link'], $database_database) . "`
	COLLATE 'utf8_general_ci'";
	$res = @mysqli_query($db['link'], $qry);
	if ($res !== TRUE) {
		//failed to create
		section_db_create_failed:
		echo PHP_EOL;
		echo 'Kan database `' . mysqli_real_escape_string($db['link'], $database_database) . '` niet aanmaken.' . PHP_EOL;
		echo 'Oorzaak: ' . mysqli_error($db['link']) . PHP_EOL;
		echo PHP_EOL;
		echo 'Mogelijk heeft het gebruikersaccount onvoldoende rechten om een database aan te ' . PHP_EOL;
		echo 'maken. Als dat het geval is, maak dan eerst handmatig een database voor ' . PHP_EOL;
		echo 'fietsviewer aan en doorloop het installatieprogramma opnieuw.' . PHP_EOL;
		echo PHP_EOL;
		echo ' 1. Databasegegevens opnieuw invoeren' . PHP_EOL;
		echo ' 2. Afsluiten' . PHP_EOL;
		echo 'Geef keuze op, gevolgd door enter:';
		$input = cli_input();

		if ($input == '1') {
			goto section_db;
		}
		elseif ($input == '2') {
			cli_exit();
		}
		else {
			goto section_db_create_failed;
		}
	}
	else {
		echo PHP_EOL;
		echo 'Database `' . mysqli_real_escape_string($db['link'], $database_database) . '` is aangemaakt.' . PHP_EOL;
	}
}

/*
* create config file
*/
echo PHP_EOL;
echo '===============================================================================' . PHP_EOL;
echo 'Configuratiebestand aanmaken' . PHP_EOL;
echo '===============================================================================' . PHP_EOL;
$config = '<?php
//fietsviewer configuration file
$db[\'host\'] = \'' . $database_server . '\';
$db[\'user\'] = \'' . $database_user . '\';
$db[\'pass\'] = \'' . $database_password . '\';
$db[\'database\'] = \'' . $database_database . '\';
?>';
file_put_contents($config_file_name, $config);
echo 'Configuratiebestand is geinstalleerd naar ' . $config_file_name . PHP_EOL;

/*
* create tables
*/
section_create_tables:
echo PHP_EOL;
echo '===============================================================================' . PHP_EOL;
echo 'Tabellen aanmaken' . PHP_EOL;
echo '===============================================================================' . PHP_EOL;
mysqli_select_db($db['link'], $database_database);

$qry = array();

$qry[] = "CREATE TABLE `method_flow` (
`name` VARCHAR(32),
`description` TINYTEXT NULL,
PRIMARY KEY (`name`)
)
ENGINE = 'InnoDB'
COLLATE 'utf8_general_ci'";

$qry[] = "INSERT INTO `method_flow`
(`name`, `description`) 
VALUES
('visual', 'visuele telling'),
('pressure', 'telslang'),
('radar', 'radar'),
('induction', 'tellus'),
('trafficlight-induction', 'VRI-lus')";

$qry[] = "CREATE TABLE `mst_flow` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`location_id` VARCHAR(64) NOT NULL,
`address` TINYTEXT NULL,
`lat` FLOAT(16,13) SIGNED NOT NULL,
`lon` FLOAT(16,13) SIGNED NOT NULL,
`heading` INT(3) UNSIGNED NOT NULL,
`method` VARCHAR(32) NOT NULL,
`quality` INT(3) NOT NULL DEFAULT 50,
PRIMARY KEY (`location_id`),
UNIQUE KEY (`id`),
FOREIGN KEY (`method`) REFERENCES `method_flow` (`name`)
)
ENGINE = 'InnoDB'
COLLATE 'utf8_general_ci'";

$qry[] = "CREATE TABLE `data_flow` (
`id` INT UNSIGNED NOT NULL,
`datetime_from` DATETIME NOT NULL,
`datetime_to` DATETIME NOT NULL,
`flow_pos` FLOAT UNSIGNED NOT NULL,
`flow_neg` FLOAT UNSIGNED NULL,
`quality` INT(3) NULL,
PRIMARY KEY (`id`, `datetime_from`, `datetime_to`)
)
ENGINE = 'MyISAM'
COLLATE 'utf8_general_ci'";

$qry[] = "CREATE TABLE `mst_rln` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`location_id` VARCHAR(64) NOT NULL,
`address` TINYTEXT NULL,
`lat` FLOAT(16,13) SIGNED NOT NULL,
`lon` FLOAT(16,13) SIGNED NOT NULL,
`heading` INT(3) UNSIGNED NOT NULL,
`method` VARCHAR(32) NOT NULL,
`quality` INT(3) NOT NULL DEFAULT 50,
PRIMARY KEY (`location_id`),
UNIQUE KEY (`id`),
FOREIGN KEY (`method`) REFERENCES `method_flow` (`name`)
)
ENGINE = 'InnoDB'
COLLATE 'utf8_general_ci'";

$qry[] = "CREATE TABLE `data_rln` (
`id` INT UNSIGNED NOT NULL,
`datetime_from` DATETIME NOT NULL,
`datetime_to` DATETIME NOT NULL,
`red_light_negation` FLOAT UNSIGNED NOT NULL,
`quality` INT(3) NULL,
PRIMARY KEY (`id`, `datetime_from`, `datetime_to`)
)
ENGINE = 'MyISAM'
COLLATE 'utf8_general_ci'";

$qry[] = "CREATE TABLE `mst_waittime` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`location_id` VARCHAR(64) NOT NULL,
`address` TINYTEXT NULL,
`lat` FLOAT(16,13) SIGNED NOT NULL,
`lon` FLOAT(16,13) SIGNED NOT NULL,
`heading` INT(3) UNSIGNED NOT NULL,
`method` VARCHAR(32) NOT NULL,
`quality` INT(3) NOT NULL DEFAULT 50,
PRIMARY KEY (`location_id`),
UNIQUE KEY (`id`),
FOREIGN KEY (`method`) REFERENCES `method_flow` (`name`)
)
ENGINE = 'InnoDB'
COLLATE 'utf8_general_ci'";

$qry[] = "CREATE TABLE `data_waittime` (
`id` INT UNSIGNED NOT NULL,
`datetime_from` DATETIME NOT NULL,
`datetime_to` DATETIME NOT NULL,
`wait-time` FLOAT UNSIGNED NOT NULL,
`quality` INT(3) NULL,
PRIMARY KEY (`id`, `datetime_from`, `datetime_to`)
)
ENGINE = 'MyISAM'
COLLATE 'utf8_general_ci'";

$qry[] = "CREATE TABLE `organisations` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`name` TEXT NOT NULL,
PRIMARY KEY (`id`)
)
ENGINE = 'InnoDB'
COLLATE 'utf8_general_ci'";

$qry[] = "INSERT INTO `organisations`
(`id`, `name`, `abbr`) 
VALUES
(1, 'system', 'SYS')";

$qry[] = "CREATE TABLE `users` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`username` VARCHAR(64) NOT NULL,
`password` TINYTEXT NOT NULL,
`email` TINYTEXT NOT NULL,
`name` TINYTEXT,
`phone` TINYTEXT NULL,
`organisation_id` INT UNSIGNED NOT NULL,
`accesslevel` TINYINT UNSIGNED NOT NULL DEFAULT 0,
PRIMARY KEY (`id`),
UNIQUE KEY (`username`),
FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`)
)
ENGINE = 'InnoDB'
COLLATE 'utf8_general_ci'";

$qry[] = "CREATE TABLE `user_login_tokens` (
`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
`user_id` INT UNSIGNED NOT NULL,
`token` TINYTEXT NOT NULL,
`date_create` DATETIME NOT NULL,
`date_lastchange` DATETIME NOT NULL,
`ip` TINYTEXT NOT NULL,
`device` TINYTEXT,
PRIMARY KEY (`id`),
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
)
ENGINE = 'InnoDB'
COLLATE 'utf8_general_ci'";

$qry[] = "CREATE TABLE `organisation_prefixes` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`organisation_id` INT UNSIGNED NOT NULL,
`prefix` VARCHAR(8) NOT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY (`prefix`),
FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`)
)
ENGINE = 'InnoDB'
COLLATE 'utf8_general_ci'";

$qry[] = "INSERT INTO `organisation_prefixes` 
	(`id`, `organisation_id`, `prefix`)
	VALUES
	(1, 1, 'SYS01')";

$qry[] = "CREATE TABLE `upload_queue` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`user_id` INT UNSIGNED NOT NULL,
`prefix_id` INT UNSIGNED NOT NULL,
`filename` TINYTEXT NOT NULL,
`md5` VARCHAR(32) NOT NULL,
`datatype` VARCHAR(32) NOT NULL,
`processed` BOOLEAN NOT NULL,
`process_error` TEXT NULL DEFAULT NULL,
`process_time` INT DEFAULT NULL,
`date_create` DATETIME NOT NULL,
`date_lastchange` DATETIME NOT NULL,
PRIMARY KEY (`id`),
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
FOREIGN KEY (`prefix_id`) REFERENCES `organisation_prefixes` (`id`)
)
ENGINE = 'InnoDB'
COLLATE 'utf8_general_ci'";

$qry[] = "CREATE TABLE `request_queue` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`user_id` INT UNSIGNED NOT NULL,
`name` TINYTEXT NOT NULL,
`request_details` TEXT NOT NULL,
`priority` TINYINT NOT NULL,
`processed` BOOLEAN NOT NULL DEFAULT 0,
`process_error` TEXT NULL DEFAULT NULL,
`process_time` INT DEFAULT NULL,
`date_create` DATETIME NOT NULL,
`date_lastchange` DATETIME NOT NULL,
PRIMARY KEY (`id`),
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
)
ENGINE = 'InnoDB'
COLLATE 'utf8_general_ci'";

$qry[] = "CREATE TABLE `reports` (
`id` INT UNSIGNED NOT NULL,
`user_id` INT UNSIGNED NOT NULL,
`name` TINYTEXT NOT NULL,
`worker` VARCHAR(32) NOT NULL,
`process_error` BOOLEAN NOT NULL DEFAULT 0,
`result` MEDIUMBLOB NULL,
`date_create` DATETIME NOT NULL,
`date_lastchange` DATETIME NOT NULL,
PRIMARY KEY (`id`),
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
)
ENGINE = 'InnoDB'
COLLATE 'utf8_general_ci'";

$qry[] = "ALTER TABLE `organisations` 
ADD `abbr` VARCHAR(32) NOT NULL";

$qry[] = "ALTER TABLE `request_queue` 
ADD `send_email` BOOLEAN NOT NULL DEFAULT 0 AFTER `priority`";

$qry[] = "ALTER TABLE `request_queue` 
ADD `worker` VARCHAR(32) NOT NULL AFTER `name`";

$qry[] = "UPDATE `organisations` 
SET `abbr` = 'SYS'
WHERE `abbr` IS NULL";

$qry[] = "RENAME TABLE `organisation_prefixes` TO `datasets`";

$qry[] = "ALTER TABLE `datasets` 
ADD `name` TINYTEXT NULL,
ADD `description` TINYTEXT NULL";

$qry[] = "ALTER TABLE `upload_queue` 
DROP FOREIGN KEY `upload_queue_ibfk_2`, 
CHANGE `prefix_id` `dataset_id` INT UNSIGNED NOT NULL,
ADD FOREIGN KEY (`dataset_id`) REFERENCES `datasets` (`id`)";

$qry[] = "ALTER TABLE `mst_flow` 
ADD `dataset_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, 
ADD FOREIGN KEY ( `dataset_id` ) REFERENCES `datasets` (`id`)";
$qry[] = "ALTER TABLE `mst_rln` 
ADD `dataset_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, 
ADD FOREIGN KEY ( `dataset_id` ) REFERENCES `datasets` (`id`)";
$qry[] = "ALTER TABLE `mst_waittime` 
ADD `dataset_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, 
ADD FOREIGN KEY ( `dataset_id` ) REFERENCES `datasets` (`id`)";

foreach($qry as $qry_this) {
	$res = @mysqli_query($db['link'], $qry_this);
	//get table name
	preg_match('/(.*)\h+.+`(.+)`.+/U', $qry_this, $table_name);
	$qry_type = strtoupper($table_name[1]);
	$table_name = $table_name[2];
	//echo result
	if ($res !== TRUE) {
		switch ($qry_type) {
			case 'CREATE':
				echo '* Kan tabel `' . $table_name . '` niet aanmaken.' . PHP_EOL;
				break;
			case 'INSERT':
				echo '* Kan rijen op `' . $table_name . '` niet invoegen.' . PHP_EOL;
				break;
			default:
				echo '* ' . $qry_type .' op `' . $table_name . '` niet uitgevoerd.' . PHP_EOL;
		}
		echo '  Oorzaak: ' . mysqli_error($db['link']) . PHP_EOL;
	}
	else {
		switch ($qry_type) {
			case 'CREATE':
				echo '* Tabel `' . $table_name . '` aangemaakt.' . PHP_EOL;
				break;
			case 'INSERT':
				echo '* Rijen op `' . $table_name . '` ingevoegd.' . PHP_EOL;
				break;
			default:
				echo '* ' . $qry_type .' op `' . $table_name . '` uitgevoerd.' . PHP_EOL;
		}
	}
}

/*
* create upload dir
*/
section_upload_dir:
require('config.inc.php');
echo PHP_EOL;
echo '===============================================================================' . PHP_EOL;
echo 'Map voor bestandsuploads aanmaken' . PHP_EOL;
echo '===============================================================================' . PHP_EOL;
echo 'Uploadmap:' . PHP_EOL;
echo getcwd() . '/' . $cfg['upload']['dir'] . PHP_EOL;
echo PHP_EOL;
//check if dir exists
if (is_dir($cfg['upload']['dir'])) {
	echo 'Uploadmap bestaat al.' . PHP_EOL;
}
else {
	if (mkdir($cfg['upload']['dir'])) {
		echo 'Uploadmap aangemaakt.' . PHP_EOL;
	}
	else {
		echo 'Kan uploadmap niet aanmaken!.' . PHP_EOL;
		echo 'Maak handmatig de hier boven vermelde map aan!' . PHP_EOL;
		echo 'Druk op een toets om door te gaan.' . PHP_EOL;
		cli_input();
	}
}
echo PHP_EOL;


/*
* create admin account
*/
section_admin_account:
echo PHP_EOL;
echo '===============================================================================' . PHP_EOL;
echo 'Admin account aanmaken' . PHP_EOL;
echo '===============================================================================' . PHP_EOL;
//create password
include('password_compat/lib/password.php');
$password = password_hash('admin', PASSWORD_DEFAULT);
//insert into db
$qry = "INSERT INTO `users`
(`username`, `password`, `accesslevel`, `organisation_id`) 
VALUES
('admin', '".$password."', 999, 1)";
$res = @mysqli_query($db['link'], $qry);

if ($res == TRUE) {
	echo 'Er is een adminaccount aangemaakt met de volgende inloggegevens:' . PHP_EOL;
	echo 'Gebruikersnaam : admin' . PHP_EOL;
	echo 'Wachtwoord     : admin' . PHP_EOL;
	echo 'Voor een productieomgeving wordt aangeraden ' . PHP_EOL;
	echo 'om het wachtwoord direct te wijzigen!' . PHP_EOL;

}
else {
	echo 'Bestaand adminaccount ongewijzigd.';
}
echo PHP_EOL;




echo PHP_EOL;
echo 'Gereed. Einde installatieprogramma.' . PHP_EOL;
echo PHP_EOL;
?>