<?php
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
?>