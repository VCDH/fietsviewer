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

require('dbconnect.inc.php');

$json = array(
    'mtd' => array(),
    'org' => array(),
    'set' => array()
);

//find methods
$qry = "SELECT `name`, `description` FROM `method_flow` ORDER BY `description`";
$res = mysqli_query($db['link'], $qry);
while ($data = mysqli_fetch_assoc($res)) {
    $json['mtd'][] = array (
        'name' => htmlspecialchars($data['name']),
        'desc' => htmlspecialchars($data['description'])
    );
}

//find organisations
$qry = "SELECT `id`, `name` FROM `organisations` ORDER BY `name`";
$res = mysqli_query($db['link'], $qry);
while ($data = mysqli_fetch_assoc($res)) {
    $json['org'][] = array (
        'id' => $data['id'],
        'name' => htmlspecialchars($data['name'])
    );
}

//find datasets
$qry = "SELECT `id`, `prefix`, `name`, `description` FROM `datasets` ORDER BY `prefix`";
$res = mysqli_query($db['link'], $qry);
while ($data = mysqli_fetch_assoc($res)) {
    $json['set'][] = array (
        'id' => $data['id'],
        'prefix' => htmlspecialchars($data['prefix']),
        'name' => htmlspecialchars($data['name']),
        'desc' => htmlspecialchars($data['description'])
    );
}

header('Content-Type: application/json');
echo json_encode($json, JSON_FORCE_OBJECT);

?>