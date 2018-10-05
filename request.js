/*
*	fietsviewer - grafische weergave van fietsdata
*   Copyright (C) 2018 Gemeente Den Haag, Netherlands
*   Developed by Jasper Vries
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
* function to process day of week selection presets
*/
function daysofweekSelectUI(id) {
	var parts = id.split('-');
	//unset all
	$('#form-' + parts[1] + ' input').prop('checked', false);
	//set various
	var days = [];
	//check which days to set
	switch(parts[2]) {
		case 'selectworkdays':
			days.push('2', '3', '4', '5', '6');
		break;
		case 'selecttuethu':
			days.push('3', '5');
		break;
		case 'selectweekend':
			days.push('1', '7');
		break;
		case 'selectall':
			days.push('1', '2', '3', '4', '5', '6', '7');
		break;
	}
	//set selected days
	$.each(days, function(i, day) {
		$('#form-' + parts[1] + ' input#form-' + parts[1] + '-' + day).prop('checked', true);
	})
}

/*
* function to show/hide email-to field
*/
function showHideEmailTo() {
	if ($('input[name=email]:checked').val() == 'true') {
		$('#form-email-to-container').show();
	}
	else {
		$('#form-email-to-container').hide();
	}
}

/*
* function to show/hide form-period-select-2
*/
function showHidePeriodSelect2() {
	if ($('input[name=type]:checked').attr('class') == 'form-worker-periods-2') {
		$('#form-period-select-2').show();
	}
	else {
		$('#form-period-select-2').hide();
	}
}

/*
* document.ready
*/
$(function() {
	$('#form-daysofweek1 a').click( function () {
		daysofweekSelectUI($(this).attr('id'));
	});
	$('#form-daysofweek2 a').click( function () {
		daysofweekSelectUI($(this).attr('id'));
	});
	//show/hide email-to field on load and click
	showHideEmailTo();
	$('input[name=email]').change(showHideEmailTo);
	//show/hide form-period-select-2 on load en type selection
	showHidePeriodSelect2();
	$('input[name=type]').change(showHidePeriodSelect2);

});
