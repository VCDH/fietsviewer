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
* function to check if the user is logged in and to retreive login information
mixed getuserdata([string $req = null])
Parameters
$req: User information to retrieve: 'id', 'username', 'token', 'accesslevel'. Optional.
Return value:
Requested user information on success. When no user information requested, bool TRUE when user is logged in. Otherwise bool FALSE when the user is not logged in.
*/

function getuserdata($req = null) {
    require('dbconnect.inc.php');
	require('config.inc.php');
    //check if the user is logged in
    //retrieve cookie
    $cookievalue = unserialize($_COOKIE[$cfg['cookie']['name']]);
    if (!is_numeric($cookievalue[0])) {
        return FALSE;
    }
    
    //match user info with db
    $qry = "SELECT `users`.`id` AS `id`, `users`.`username` AS `username`, `users`.`accesslevel` AS `accesslevel`, `user_login_tokens`.`token` AS `token`, `users`.`organisation_id` AS `organisation_id` FROM `user_login_tokens`
    LEFT JOIN `users`
    ON `user_login_tokens`.`user_id` = `users`.`id`
    WHERE `user_login_tokens`.`user_id` = '" . mysqli_real_escape_string($db['link'], $cookievalue[0]) . "'
    AND `user_login_tokens`.`token` = '" . mysqli_real_escape_string($db['link'], $cookievalue[1]) . "'";
    $res = mysqli_query($db['link'], $qry);
    if ($data = mysqli_fetch_assoc($res)) {
        if (in_array($req, array('id', 'username', 'token', 'accesslevel', 'organisation_id'))) {
            return $data[$req];
        }
        else {
            return TRUE;
        }
    }
    return FALSE;
}

/*
* function to check if the user is logged in and issue a warning if not so
mixed logincheck( void )
Return value:
void if the user is logged in
HTTP 403 status code and link to login page if not
The function should be called before any HTML output (but degrades gracefully)
*/

function logincheck() {
    if (getuserdata() !== TRUE) {
        header('HTTP/1.0 401 Unauthorized');
        echo '<h1>401 Unauthorized</h1>';
        echo '<p><a href="login.php">login</a></p>';
        exit;
    }    
}

/*
* function to check if the user has a certain accesslevel
(bool) accesslevelcheck( (mixed) $req_accesslevel )
$req_accesslevel can be a named value from $accesslevel or a numeric value between 0 and 255
Return value:
TRUE if the user has sufficient accesslevel, FALSE otherwise
*/

function accesslevelcheck($req_accesslevel) {
    //get numeric value by named value
    if (is_string($req_accesslevel)) {
        require 'accesslevels.inc.php';
        if (array_key_exists($req_accesslevel, $cfg_accesslevel)) {
            $req_accesslevel = $cfg_accesslevel[$req_accesslevel];
        }
    }
    if (is_numeric($req_accesslevel) && ($req_accesslevel >= 0) && ($req_accesslevel <= 255) && (getuserdata('accesslevel') >= $req_accesslevel)) {
        return TRUE;
    }
    return FALSE; 
}

/*
* function to check if the user has a certain accesslevel
mixed accesscheck( (str) $req_accesslevel )
Return value:
void if the user has sufficient accesslevel, error message otherwise
*/

function accesscheck($req_accesslevel) {
    //find if given accesslevelname exists and check accesslevel
    if (accesslevelcheck($req_accesslevel) === TRUE) {
        return TRUE;
    }
    header('HTTP/1.0 401 Unauthorized');
    echo '<h1>401 Unauthorized</h1>';
    echo '<p>Te weinig rechten om deze functie gebruiken. Als je hier bent gekomen door een link aan te klikken, heb je een programmeerfout gevonden!</p>';
    echo '<p><a href="index.php">beginpagina</a></p>';
    exit;
}
?>