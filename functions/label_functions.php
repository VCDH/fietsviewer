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

function named_week_by_mysql_index($i) {
    switch($i) {
        case 0: return 'ma';
        case 1: return 'di';
        case 2: return 'wo';
        case 3: return 'do';
        case 4: return 'vr';
        case 5: return 'za';
        case 6: return 'zo';
        return FALSE;
    }
}

function named_dayofweek_by_mysql_index($i) {
    switch($i) {
        case 1: return 'zo';
        case 2: return 'ma';
        case 3: return 'di';
        case 4: return 'wo';
        case 5: return 'do';
        case 6: return 'vr';
        case 7: return 'za';
        return FALSE;
    }
}

function named_month_by_mysql_index($i) {
    switch($i) {
        case 1: return 'jan';
        case 2: return 'feb';
        case 3: return 'mrt';
        case 4: return 'apr';
        case 5: return 'mei';
        case 6: return 'jun';
        case 7: return 'jul';
        case 8: return 'aug';
        case 9: return 'sep';
        case 10: return 'okt';
        case 11: return 'nov';
        case 12: return 'dec';
        return FALSE;
    }
}
?>