<!DOCTYPE html>
<!--
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
-->
<html lang="nl-nl">
<head>
	<title>fietsv&#7433;ewer - over fietsv&#7433;ewer</title>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
	
	<?php include('menu.inc.php'); ?>
	
    <h1>over fietsv&#7433;ewer (readme.txt)</h1>
    <pre><?php
    echo htmlspecialchars(file_get_contents('readme.txt'));
    ?></pre>

</body>
</html>