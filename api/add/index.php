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

//process api request
$json = array('FietsViewerRespons' => array('statusCode' => 500, 'statusText' => 'Internal Server Error'));

//check if credentials are provided
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    $json['FietsViewerRespons']['statusCode'] = 401;
    $json['FietsViewerRespons']['statusText'] = 'Unauthorized';
    $json['FietsViewerRespons']['statusDesc'] = 'Must provide username and password';
}
//check username and password
else {
    require('../../dbconnect.inc.php');
	require('../../config.inc.php');
	//hash password
	include_once('../../password_compat/lib/password.php');
	//get password by username
	$qry = "SELECT `id`, `password`, `accesslevel`, `organisation_id`, `default_dataset_id` FROM `users` WHERE
	`username` = '" . mysqli_real_escape_string($db['link'], $_SERVER['PHP_AUTH_USER']) . "'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res) == 1) {
		//user exists
		$data = mysqli_fetch_assoc($res);
		//check password
		if (password_verify($_SERVER['PHP_AUTH_PW'], $data['password'])) {
            //check access level
            require '../../accesslevels.inc.php';
            if ($data['accesslevel'] >= $cfg_accesslevel['adddata']) {
                //check if method is correct
                if  ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    $json['FietsViewerRespons']['statusCode'] = 405;
                    $json['FietsViewerRespons']['statusText'] = 'Method Not Allowed';
                    $json['FietsViewerRespons']['statusDesc'] = 'Only POST requests allowed';
                }
            }
            else {
                $json['FietsViewerRespons']['statusCode'] = 403;
                $json['FietsViewerRespons']['statusText'] = 'Forbidden';
                $json['FietsViewerRespons']['statusDesc'] = 'Feature not available to this user';
            }
        }
        else {
            $json['FietsViewerRespons']['statusCode'] = 401;
            $json['FietsViewerRespons']['statusText'] = 'Unauthorized';
            $json['FietsViewerRespons']['statusDesc'] = 'Username and/or password invalid';
        }
	}
	else {
        //invalid user
        $json['FietsViewerRespons']['statusCode'] = 401;
        $json['FietsViewerRespons']['statusText'] = 'Unauthorized';
        $json['FietsViewerRespons']['statusDesc'] = 'Username and/or password invalid';
    }
}

//TODO: implement 429 Too Many Requests for when user attempts to push two or more files at the same time

//if status 500, no error has occured to this point
if ($json['FietsViewerRespons']['statusCode'] == 500) {
    //get stream
    $fp = fopen('php://input', 'r');
    $rawData = stream_get_contents($fp);    
    //accept file into temporary file
    $tmp_file = tempnam('/tmp', 'fv_');
    file_put_contents($tmp_file, $rawData);
    //TODO check file length


    //check file format
    require_once '../../functions/csv_functions.php';
    require_once '../../functions/check_format.php';
    $format = check_data_format($tmp_file);
    if ($format === FALSE) {
        $json['FietsViewerRespons']['statusCode'] = 415;
        $json['FietsViewerRespons']['statusText'] = 'Unsupported Media Type';
        $json['FietsViewerRespons']['statusDesc'] = 'Data provided does not satisfy acceptable data format';
    }
    else {
        //store uploaded file
        //get md5 hash of uploaded file
        $md5 = md5_file($tmp_file);
        //decide file name
        chdir('../../');
        $target_file = $cfg['upload']['dir'];
        //add trailing slash if needed
        if (substr($target_file, -1) != '/') {
            $target_file .= '/';
        }
        $target_file .= $md5;
        //move uploaded file
        if (!rename($tmp_file, $target_file)) {
            $json['FietsViewerRespons']['statusDesc'] = 'Cannot store data to file';
            file_put_contents('checkdata.txt', $target_file);
        }
        else {
            //see if dataset ID exists
            $qry = "SELECT `id` FROM `datasets` WHERE
            `id` = '" . mysqli_real_escape_string($db['link'], $data['default_dataset_id']) . "'
            AND `organisation_id` = '" . mysqli_real_escape_string($db['link'], $data['organisation_id']) . "'";
            $res = mysqli_query($db['link'], $qry);
            if (mysqli_num_rows($res) !== 1) {
                $json['FietsViewerRespons']['statusCode'] = 400;
                $json['FietsViewerRespons']['statusText'] = 'Bad Request';
                $json['FietsViewerRespons']['statusDesc'] = 'No valid default dataset selected in user account settings';
            }
            else {
                //add to process queue
                $qry = "INSERT INTO `upload_queue` SET
                `user_id` = '" . mysqli_real_escape_string($db['link'], $data['id']) . "',
                `dataset_id` = '" . mysqli_real_escape_string($db['link'], $data['default_dataset_id']) . "', 
                `md5` = '" . mysqli_real_escape_string($db['link'], $md5) . "',
                `filename` = '" . mysqli_real_escape_string($db['link'], 'API '. time()) . "',
                `datatype` = '" . mysqli_real_escape_string($db['link'], $format) . "',
                `processed` = 0,
                `date_create` = NOW(),
                `date_lastchange` = NOW()";
                if (mysqli_query($db['link'], $qry)) {
                    $json['FietsViewerRespons']['statusCode'] = 202;
                    $json['FietsViewerRespons']['statusText'] = 'Accepted';
                    $json['FietsViewerRespons']['processId'] = mysqli_insert_id($db['link']);
                    $json['FietsViewerRespons']['md5'] = $md5;
                    //request hypervisor
                    if ($cfg['hypervisor']['user_activated'] == TRUE) {
                        include_once 'hypervisor.php';
                    }
                }
                else {
                    $json['FietsViewerRespons']['statusDesc'] = 'Cannot add request to database';
                }
            }
        }
    }
    //cleanup temp file if there is a reason to do so
    if (file_exists($tmp_file)) {
        unlink($tmp_file);
    }
}

//return headers and content body
if ($json['FietsViewerRespons']['statusCode'] == 401) {
    header('WWW-Authenticate: Basic realm="fietsviewer"');
}
header('HTTP/1.0 ' . $json['FietsViewerRespons']['statusCode'] . ' ' . $json['FietsViewerRespons']['statusText']);
header('Content-type: application/json');
echo json_encode($json, JSON_FORCE_OBJECT);
exit;

?>