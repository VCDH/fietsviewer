<?php
/*
 	fietsviewer - grafische weergave van fietsdata
    Copyright (C) 2018 Jasper Vries, Gemeente Den Haag
 
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

require_once('getuserdata.fct.php');

$menu = array (
    array('href' => 'index.php', 'title' => 'kaart weergeven', 'access' => 'all', 'block' => 1),
    array('href' => 'analyze.php', 'title' => 'analyse maken', 'access' => 'all', 'block' => 1),
    array('href' => 'results.php', 'title' => 'mijn analyses', 'access' => 'login', 'block' => 1),
    array('href' => 'about.php', 'title' => 'over fietsv&#7433;ewer', 'access' => 'all', 'block' => 2),
    array('href' => 'help.php', 'title' => 'help', 'access' => 'all', 'block' => 2),
    array('href' => 'adddata.php', 'title' => 'gegevensset toevoegen', 'access' => 'login', 'block' => 2),
    array('href' => 'account.php', 'title' => 'account', 'access' => 'login', 'block' => 2),
    array('href' => 'login.php', 'title' => 'aanmelden', 'access' => 'logout', 'block' => 2),
    array('href' => 'login.php?a=logout', 'title' => 'afmelden', 'access' => 'login', 'block' => 2),
);

//check login
$login = getuserdata();
$currentpage = basename($_SERVER["PHP_SELF"]);

//render menu
echo '<div id="menu-top-bar">';
echo '<div id="menu-top-bar-1">';
echo '<strong>fietsv&#7433;ewer</strong>';
foreach ($menu as $item) {
    if (($item['block'] == 1) && ($currentpage != substr($item['href'], 0, strpos($item['href'], '.php') + 4)) && (($item['access'] == 'all') || (($login == TRUE) && ($item['access'] == 'login')) || (($login == FALSE) && ($item['access'] == 'logout')))) {
        echo ' | <a href="' . $item['href'] . '" title="' . $item['title'] . '">' . $item['title'] . '</a>';
    }
}
echo '</div>';
echo '<div id="menu-top-bar-2">';
foreach ($menu as $item) {
    if (($item['block'] == 2) && ($currentpage != substr($item['href'], 0, strpos($item['href'], '.php') + 4)) && (($item['access'] == 'all') || (($login == TRUE) && ($item['access'] == 'login')) || (($login == FALSE) && ($item['access'] == 'logout')))) {
        echo '<a href="' . $item['href'] . '" title="' . $item['title'] . '">' . $item['title'] . '</a> | ';
    }
}
if ($login == TRUE) {
    echo htmlspecialchars(getuserdata('username'));
}
echo '</div>';
echo '</div>';
?>