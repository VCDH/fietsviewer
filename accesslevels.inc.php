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
* Config of user access rights
* Accesslevel between 1 and 255, with 1 being lowest (only login rights) and 255 being highest (admin)
*/

$cfg_accesslevel['request'] = 1;            $cfg_accessdescription['request'] = 'Analyse maken';        
$cfg_accesslevel['results'] = 1;            $cfg_accessdescription['results'] = 'Mijn analyses';        
$cfg_accesslevel['about'] = 0;              $cfg_accessdescription['about'] = 'Over fietsv&#7433;ewer';          
$cfg_accesslevel['help'] = 1;               $cfg_accessdescription['help'] = 'Help weergeven';           
$cfg_accesslevel['adddata'] = 100;          $cfg_accessdescription['adddata'] = 'Data toevoegen';      
$cfg_accesslevel['admin'] = 50;             $cfg_accessdescription['admin'] = 'Beheer';        
$cfg_accesslevel['organisations'] = 250;    $cfg_accessdescription['organisations'] = 'Organisaties beheren';
$cfg_accesslevel['users'] = 100;            $cfg_accessdescription['users'] = 'Gebruikers beheren';        
$cfg_accesslevel['datasets'] = 50;          $cfg_accessdescription['datasets'] = 'Gegevenssets beheren';      

?>