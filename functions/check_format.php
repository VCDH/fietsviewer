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
* function to check the uploaded file for file consistency with the data format
* only header row and first row are checked
* returns (bool) FALSE if the file is wrong or (str) $format if the file is correct
*/
function check_data_format($file) {
    //open file
    $handle = fopen($file, 'rb');
    if ($handle == FALSE) {
        
        return FALSE;
    }
    //get header row
    $line = fgets($handle);
    if ($line == FALSE) {
        return FALSE;
    }
    //detect delimiter
    $delimiter = csv_delimiter_from_string($line);
    if ($delimiter == FALSE) {
        return FALSE;
    }
    //get column names
    $colnames = str_getcsv($line, $delimiter);
    //check data format
    $format = NULL;
    $format_check = check_format_dpf_flow($colnames);
    if ($format_check === TRUE) {
        $format = 'dpf-flow';
    }
    $format_check = check_format_dpf_rln($colnames);
    if ($format_check === TRUE) {
        $format = 'dpf-rln';
    }
    $format_check = check_format_dpf_waittime($colnames);
    if ($format_check === TRUE) {
        $format = 'dpf-waittime';
    }
    if ($format === NULL) {
        return FALSE;
    }
    //check if there is a data row
    $row1 = fgetcsv($handle, null, $delimiter);
    if ($row1 == FALSE) {
        return FALSE;
    }
    return $format;
}

/*
* function to check header row for "data platform fiets" format
* returns FALSE if it doesn't match or TRUE if mandatory columns are available
*/
function check_format_dpf_flow($arr_colnames) {
    $mandatory_cols = array(
        array('locatie-id', 'location-id', 'id', 'nr'),
        array('lat'),
        array('lon'),
        array('richting', 'heading', 'direction'),
        array('methode', 'method'),
        array('periode-van', 'period-from'),
        array('periode-tot', 'period-to'),
        array('tijd-van', 'time-from'),
        array('tijd-tot', 'time-to'),
        array('fiets', 'bicycle')
    );
    //set $arr_colnames to lowercase
    $arr_colnames = array_map('strtolower', $arr_colnames);
    //check for each mandatory col
    foreach ($mandatory_cols as $cols) {
        //assume false
        $assume = FALSE;
        //check for presence of field
        foreach ($cols as $col) {
            $key = array_search($col, $arr_colnames);
            if ($key !== FALSE) {
                $assume = TRUE;
                break;
            }
        }
        //if not present, break and return FALSE
        if ($assume == FALSE) {
            return FALSE;
        }
    }
    //all mandatory columns present
    return TRUE;
}
function check_format_dpf_rln($arr_colnames) {
    $mandatory_cols = array(
        array('locatie-id', 'location-id', 'id', 'nr'),
        array('lat'),
        array('lon'),
        array('richting', 'heading', 'direction'),
        array('methode', 'method'),
        array('periode-van', 'period-from'),
        array('periode-tot', 'period-to'),
        array('tijd-van', 'time-from'),
        array('tijd-tot', 'time-to'),
        array('rood-licht-negatie', 'red-light-negation')
    );
    //set $arr_colnames to lowercase
    $arr_colnames = array_map('strtolower', $arr_colnames);
    //check for each mandatory col
    foreach ($mandatory_cols as $cols) {
        //assume false
        $assume = FALSE;
        //check for presence of field
        foreach ($cols as $col) {
            $key = array_search($col, $arr_colnames);
            if ($key !== FALSE) {
                $assume = TRUE;
                break;
            }
        }
        //if not present, break and return FALSE
        if ($assume == FALSE) {
            return FALSE;
        }
    }
    //all mandatory columns present
    return TRUE;
}
function check_format_dpf_waittime($arr_colnames) {
    $mandatory_cols = array(
        array('locatie-id', 'location-id', 'id', 'nr'),
        array('lat'),
        array('lon'),
        array('richting', 'heading', 'direction'),
        array('methode', 'method'),
        array('periode-van', 'period-from'),
        array('periode-tot', 'period-to'),
        array('tijd-van', 'time-from'),
        array('tijd-tot', 'time-to'),
        array('wachttijd', 'wait-time')
    );
    //set $arr_colnames to lowercase
    $arr_colnames = array_map('strtolower', $arr_colnames);
    //check for each mandatory col
    foreach ($mandatory_cols as $cols) {
        //assume false
        $assume = FALSE;
        //check for presence of field
        foreach ($cols as $col) {
            $key = array_search($col, $arr_colnames);
            if ($key !== FALSE) {
                $assume = TRUE;
                break;
            }
        }
        //if not present, break and return FALSE
        if ($assume == FALSE) {
            return FALSE;
        }
    }
    //all mandatory columns present
    return TRUE;
}

?>