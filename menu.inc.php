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

require_once 'getuserdata.fct.php';
require 'accesslevels.inc.php';

//TODO move access level to separate config
$menu = array (
    array('href' => 'index.php', 'title' => 'kaart weergeven', 'access' => 0, 'block' => 1),
    array('href' => 'request.php', 'title' => 'analyse maken', 'access' => $cfg_accesslevel['request'], 'block' => 1, 'maponly' => TRUE),
    array('href' => 'results.php', 'title' => 'mijn analyses', 'access' => $cfg_accesslevel['results'], 'block' => 1),
    array('href' => 'about.php', 'title' => 'over fietsv&#7433;ewer', 'access' => $cfg_accesslevel['about'], 'block' => 2),
    //array('href' => 'help.php', 'title' => 'help', 'access' => $cfg_accesslevelaccesslevel['help'], 'block' => 2),
    array('href' => 'adddata.php', 'title' => 'data toevoegen', 'access' => $cfg_accesslevel['adddata'], 'block' => 2),
    array('href' => 'admin.php', 'title' => 'beheer', 'access' => $cfg_accesslevel['admin'], 'block' => 2),
    array('href' => 'account.php', 'title' => 'account', 'access' => 1, 'block' => 2),
    array('href' => 'login.php', 'title' => 'aanmelden', 'access' => -1, 'block' => 2),
    array('href' => 'login.php?a=logout', 'title' => 'afmelden', 'access' => 1, 'block' => 2),
);

//check login
$accesslevel = getuserdata('accesslevel');
$currentpage = basename($_SERVER["PHP_SELF"]);

//render menu
echo '<div id="menu-top-bar">';
echo '<div id="menu-top-bar-1">';
echo '<strong>fietsv&#7433;ewer</strong>';
foreach ($menu as $item) {
    if (($item['block'] == 1) 
    && ($currentpage != substr($item['href'], 0, strpos($item['href'], '.php') + 4)) 
    && ((($item['access'] >= 0) && ($accesslevel >= $item['access'])) || (($accesslevel == 0) && ($item['access'] < 0))) 
    && (($item['maponly'] != TRUE) || ($currentpage == 'index.php'))) {
        echo ' | <a href="' . $item['href'] . '" title="' . $item['title'] . '">' . $item['title'] . '</a>';
    }
}
echo '</div>';
echo '<div id="menu-top-bar-2">';
foreach ($menu as $item) {
    if (($item['block'] == 2) 
    && ($currentpage != substr($item['href'], 0, strpos($item['href'], '.php') + 4)) 
    && ((($item['access'] >= 0) && ($accesslevel >= $item['access'])) || (($accesslevel == 0) && ($item['access'] < 0))) 
    && (($item['maponly'] != TRUE) || ($currentpage == 'index.php'))) {
        echo '<a href="' . $item['href'] . '" title="' . $item['title'] . '">' . $item['title'] . '</a> | ';
    }
}
if ($accesslevel !== FALSE) {
    echo htmlspecialchars(getuserdata('username'));
}
else {
    echo 'gast';
}
echo '</div>';
echo '</div>';
?>