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
* function to find CSV delimiter used in a string
* returns FALSE if there is no valid delimiter or otherwise a string with the delimiter
*/
function csv_delimiter_from_string($line) {
    //detect delimiter
    $delimiters = array(';', ',');
    $delimiter_count = array();
    foreach ($delimiters as $index => $delimiter) {
        $offset = 0;
        $count = 0;
        while ($offset !== FALSE) {
            $offset = strpos($line, $delimiter, $offset + 1);
            if ($offset !== FALSE) {
                $count++;
            }
            else {
                break;
            }
        }
        $delimiter_count[$index] = $count;
    }
    //sort delimiters and find most occurring one
    asort($delimiter_count);
    end($delimiter_count);
    if (current($delimiter_count) == 0) {
        return FALSE;
    }
    return $delimiters[key($delimiter_count)];
}

/*
* function to find CSV terminator used in a string
* returns FALSE if there is no line terminator at the end of the string or otherwise a string with the terminator
*/
function csv_terminator_from_string($line) {
    if (substr($line, -2) == "\r\n") {
        return "\r\n";
    }
    elseif (substr($line, -1) == "\n") {
        return "\n";
    }
    elseif (substr($line, -1) == "\r") {
        return "\r";
    }
    else {
        return FALSE;
    }
}