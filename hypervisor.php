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
* The Hypervisor is a script that can be called periodically form a cronjob or after certain user actions.
* It checks if certain background tasks are running and if not it will (re)start them.
* By default fietsviewer is configured to call The Hypervisor from user interaction.
* As such it is not required to set up a cronjob.
* Calling The Hypervisor from user interaction can be disabled in config.inc.php.
*/

/*
* function to execute command in background
* by Arno van den Brink, http://php.net/manual/en/function.exec.php#86329
*/
function execInBackground($cmd) {
    if (substr(php_uname(), 0, 7) == "Windows"){
        pclose(popen("start /B ". $cmd, "r")); 
    }
    else {
        exec($cmd . " > /dev/null &");  
    }
} 

//call process_queue
execInBackground('php process_queue.php');


?>