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

======================================================================

This folder contains so-called "workers". Each worker is a folder that 
contains a set of instructions to process data and present a particular result.

The name of the folder is the name of the worker.

Each worker contains:

- worker.json
    specific configuration information for the worker, that is used by request.php, result.php and report.php.
- process.inc.php
    script that processes the data from the database and provides the result to be stored in the reports table;
    it should only perform read-only access on the database;
    it may create temporary tables for intermediate operation; it must garbage-collect its temporary tables;
    the script must define a function worker_process() that accepts the database-entry `request_details` as only parameter;
    `request_details` is provided as a json string;
    the function worker_process() must return a string value for storage in the database;
    the contents of return value may be worker-specific;
    if the worker process encounters an error, it must return a string error message prefixed with ERROR as result;
    in case of an error, no result will be stored, only the error message;
    the script may not modify variables in global scope;
    if the script defines other functions, it must do so in its own namespace, or as global functions with the name of the worker in the name of the function;
    data-availability is checked by the main result-processor and only qualifying markers are provided for the worker-process (`request_details` is modified accordingly).
- report.inc.php
    script that prepares display in report.php;
    report.php will provide the contents of the return value (provided by the worker_process() function) from the database.

Specific documentation for this should be provided at a later date.
