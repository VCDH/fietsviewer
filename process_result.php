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

require 'dbconnect.inc.php';
require 'config.inc.php';
require_once 'functions/csv_functions.php';

/*
* This script processes the file queue. It should be called periodically, e.g. via cron or some other means
* It is safeguarded against parallel execution, so it is fine to call it every minute
*/

$runningfile = substr($_SERVER['SCRIPT_NAME'], strrpos($_SERVER['SCRIPT_NAME'], '/'), strrpos($_SERVER['SCRIPT_NAME'], '.') - strlen($_SERVER['SCRIPT_NAME'])) . '.running';
$timeout = 1800; //seconds
$tmp_data_file = 'tmp_data.csv';
set_time_limit(0);

/*
* Script startup
* check if script is already running and terminate
* allowed to run if there is no running file or if there is no activity for the last $timeout minutes
*/
if (is_file($runningfile)) {
    $lastchange = file_get_contents($runningfile);
    if (!is_numeric($lastchange) || ((time() - $lastchange) > $timeout)) {
        exit;
    }
}
$lastrun = time();

function update_running_file() {
    global $runningfile;
    global $timeout;
    global $lastrun;
    //exit self if no activity for timeout period
    if ((time() - $lastrun) > $timeout) {
        unlink($runningfile);
        exit;
    }
    //otherwise update running file and lastrun time
    $lastrun = time();
    file_put_contents($runningfile, $lastrun);
}
update_running_file();


/*
* main script
*/

//update priority of requests in queue
$priority_shift = array(
    array(
        'prio' => 3,
        'interval' => 72
    ),
    array(
        'prio' => 2,
        'interval' => 24
    )
);
foreach ($priority_shift as $ps_this) {
    $qry = "UPDATE `request_queue` 
    SET `priority` = 1,
    `date_lastchange` = NOW()
    WHERE `priority` = " . $ps_this['prio'] . "
    AND `date_lastchange` < DATE_SUB(NOW(), INTERVAL " . $ps_this['interval'] . " HOUR)
    AND `processed` = 0
    AND `process_time` IS NULL";
    mysqli_query($db['link'], $qry);
}
//select next job from queue
//selected one by one in order to respect priorities
$qry = "SELECT `id`, `user_id`, `name`, `worker`, `request_details`, `send_email` FROM `request_queue`
WHERE `processed` = 0
AND `process_time` IS NULL
ORDER BY `priority` ASC, `date_lastchange` DESC
LIMIT 1";
$res = mysqli_query($db['link'], $qry);
if (mysqli_num_rows($res)) {
    //process this request
    update_running_file();
    $data = mysqli_fetch_assoc($res);
    $process_time = time();
    //set processed status
    $qry = "UPDATE `request_queue`
    SET `processed` = 1
    WHERE `id` = " . $data['id'];
    mysqli_query($db['link'], $qry);
    //TODO: data availability check and update for request_details


    //include worker
    $worker = 'workers/' . $data['worker'] . '/process.inc.php';
    if (file_exists($worker)) {
        include $worker;
        //execute worker function
        $result = worker_process($data['request_details']);
    }
    else {
        $error = 'No valid worker';
    }
    //handle process errors
    if (substr($result, 0, 5) == 'ERROR') {
        $error = substr($result, 0);
        $result = '';
    }
    //store result in database
    $qry = "INSERT INTO `reports` SET
    `id` = " . $data['id'] . ",
    `user_id` = " . $data['user_id'] . ",
    `name` = '" . mysqli_real_escape_string($db['link'], $data['name']) . "',
    `worker` = '" . mysqli_real_escape_string($db['link'], $data['worker']) . "',
    `process_error` = " . (empty($error) ? '0' : '1') . ",
    `result` = '" . mysqli_real_escape_string($db['link'], $result) . "',
    `date_create` = NOW(),
    `date_lastchange` = NOW()";
    mysqli_query($db['link'], $qry);
    //update queue
    $process_time = time() - $process_time;
    $qry = "UPDATE `request_queue`
    SET `process_time` = " . $process_time;
    if (!empty($error)) {
        $qry .= ", `process_error` = '" . mysqli_real_escape_string($db['link'], $error) . "'";
    }
    $qry .= " WHERE `id` = " . $data['id'];
    mysqli_query($db['link'], $qry);
    //TODO send email
    if ($data['send_email'] == '1') {
        //get email address from user

        //prepare message

        //send message
        
    }
}

//script cannot run in loop because of functions that may need to be redefined, so it must me restarted for each job in the queue
//check if it needs to be restarted
$restart = FALSE;
$qry = "SELECT `id` FROM `request_queue`
WHERE `processed` = 0
AND `process_time` IS NULL
LIMIT 1";
$res = mysqli_query($db['link'], $qry);
if (mysqli_num_rows($res)) {
    $restart = TRUE;
}

/*
* terminate script
*/
unlink($runningfile);
//restart script
if ($restart == TRUE) {
    require_once 'functions/execInBackground.php';
    sleep(1);
    execInBackground('php process_result.php');
}
exit;
?>