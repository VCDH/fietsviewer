/*
*	fietsviewer - grafische weergave van fietsdata
*   Copyright (C) 2018 Gemeente Den Haag, Netherlands
    Developed by Jasper Vries
*
*   This program is free software: you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation, either version 3 of the License, or
*   (at your option) any later version.
*
*   This program is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

/*
* check for browser support and display a warning if not all features are supported
*/

function checkBrowserSupport() {
    var test = document.createElement('input');
    try {
        test.type = 'date';
    }
    catch(err) {
        console.log('Cannot assign date for input.type');
    }
    if (test.type === 'text') {
        return false;
    }
    return true;
}

function displayBrowserWarning() {
    var div = document.createElement('div');
    div.id = 'browserincompatible';
    div.innerHTML = '<p>Helaas, uw browser is niet geschikt voor het gebruik van fietsv&#7433;ewer.</p>';
    document.body.appendChild(div);
}

if (checkBrowserSupport() === false) {
    displayBrowserWarning();
}