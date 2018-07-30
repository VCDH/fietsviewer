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
* command line tool to convert CSV in Dufec format to Data Platform Fiets format
*/

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

function show_usage() {
    echo 'Dufec format to Data Platform Fiets format converter' . PHP_EOL;
    echo 'part of fietsviewer project - grafische weergave van fietsdata' . PHP_EOL;
    echo 'Copyright (C) 2018 Gemeente Den Haag, Netherlands' . PHP_EOL;
    echo 'Developed by Jasper Vries' . PHP_EOL;
    echo PHP_EOL;
    echo PHP_EOL;
    echo 'Usage: php [-f] ' . basename(__FILE__) . ' <input_file> [<output_file>]' . PHP_EOL;
    echo PHP_EOL;
    echo '<input_file>      Filename of input file, must be a CSV file in Dufec format' . PHP_EOL;
    echo '<output_file>     Filename of output file, prefixed input filename will be used if omitted, file must not exist' . PHP_EOL;
    exit;
}

function str_to_heading($str) {
    switch(strtolower($str)) {
        case 'noordoost': return 45;
        case 'oost': return 90;
        case 'zuidoost': return 135;
        case 'zuid': return 180;
        case 'zuidwest': return 225;
        case 'west': return 270;
        case 'noordwest': return 315;
        default: return 0;
    }
}

function convert_method_name($str) {
    switch(strtolower($str)) {
        case 'telslangen': return 'pressure';
        default: return $str;
    }
}

//check if input file exists
if (!file_exists($argv[1])) {
    show_usage();
}
//check if output file doesn't exist
$outfile = (empty($argv[2]) ? 'converted_' . $argv[1] : $argv[2]);
if (file_exists($outfile)) {
    echo 'output file already exists!';
    exit;
}
//check input file extension
if (substr($argv[1], -3) != 'csv') {
    echo 'unsupported filetype!';
    exit;
}

//open output file and write headings
$outhandle = fopen($outfile, 'wb');
fwrite($outhandle, 'location-id;address;lat;lon;heading;method;quality;period-from;period-to;time-from;time-to;per;bicycle');

//read input file line by line
ini_set("auto_detect_line_endings", true); //accept \r line endings
$inhandle = fopen($argv[1], 'rb');
//read and skip first row and check for errors
$csv = fgetcsv($inhandle, 0, ';');
if (($csv === NULL) || ($csv === FALSE)) {
    echo 'input file is not valid semicolon-delimited CSV!';
    exit;
}

//these fields will be skipped if they are duplicate from the previous
$previousvalue = array('address' => '', 'lat' => 999, 'lon' => 999, 'heading' => 999, 'method' => '');

while ($csv = fgetcsv($inhandle, 0, ';')) {
    //prepare output
    $csvout = '';
    for ($h = 0; $h < 24; $h++) {
        $currentvalue = array(
            'address' => '"' . $csv[2] . ', ' . $csv[3] . '"', 
            'lat' => (float) str_replace(',', '.', $csv[4]), 
            'lon' => (float) str_replace(',', '.', $csv[5]), 
            'heading' => str_to_heading($csv[8]), 
            'method' => '"' . convert_method_name($csv[13]) . '"'
        );
        $csvout .= PHP_EOL . '"' . $csv[1] . '"'; //locaton-id
        $csvout .= ';' . (($previousvalue['address'] == $currentvalue['address']) ? '' : $currentvalue['address']); //address
        $csvout .= ';' . (($previousvalue['lat'] == $currentvalue['lat']) ? '' : $currentvalue['lat']); //lat
        $csvout .= ';' . (($previousvalue['lon'] == $currentvalue['lon']) ? '' : $currentvalue['lon']); //lon
        $csvout .= ';' . (($previousvalue['heading'] == $currentvalue['heading']) ? '' : $currentvalue['heading']); //heading
        $csvout .= ';' . (($previousvalue['method'] == $currentvalue['method']) ? '' : $currentvalue['method']); //method
        $csvout .= ';80'; //quality
        $csvout .= ';"' . date('Y-m-d', strtotime($csv[16])) . '"'; //period-from
        $csvout .= ';"' . date('Y-m-d', strtotime($csv[16])) . '"'; //period-to
        $csvout .= ';"' . str_pad($h, 2, '0', STR_PAD_LEFT) . ':00:00"'; //time-from
        $csvout .= ';"' . str_pad($h, 2, '0', STR_PAD_LEFT) . ':59:59"'; //time-to
        $csvout .= ';1'; //per
        $csvout .= ';' . $csv[17 + $h] . ''; //bicycle
        //set for next iteration to allow skipping of duplicate values in order to recude export filesize
        //$previousvalue = $currentvalue;
    }

    //write output to file
    fwrite($outhandle, $csvout);
}

//close open file handles
fclose($inhandle);
fclose($outhandle);
echo PHP_EOL . 'done!';

?>