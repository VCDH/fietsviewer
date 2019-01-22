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
* report module for the graph plot
*/

$qry = "SELECT `result` FROM `reports`
WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'
LIMIT 1";
$res = mysqli_query($db['link'], $qry);
$data = mysqli_fetch_assoc($res);

//canvas for graph
//javascript for graph
?>
<canvas id="line-chart" width="800" height="450"></canvas>
<script>
var chart = new Chart(document.getElementById("line-chart"), {
    type: 'line',
    options: {
        title: {
            display: true,
            text: 'Aantal fietsers per meetpunt'
        },
        responsive: false,
        scales: {
            yAxes: [{
                ticks: {
                    suggestedMin: 0,
                },
                scaleLabel: {
                    display: true,
                    labelString: 'Aantal fietsers'
                }
            }]
        },
        legend: {
            position: 'bottom'
        }
    }
});
//add data points
chart.data = JSON.parse('<?php echo $data['result']; ?>');
//disable line area fill
for (var i = 0; i < chart.data.datasets.length; i++) {
    chart.data.datasets[i].fill = false;
    chart.data.datasets[i].borderColor = randomColor();
}
chart.update();

function randomColor() {
    var r = Math.floor(Math.random() * 255);
    var g = Math.floor(Math.random() * 255);
    var b = Math.floor(Math.random() * 255);
    return 'rgb(' + r + ',' + g + ',' + b + ')';
}
</script>