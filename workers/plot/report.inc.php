<?php
/*
 	fietsviewer - grafische weergave van fietsdata
    Copyright (C) 2018-2019 Gemeente Den Haag, Netherlands
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
    type: 'bar',
    options: {
        responsive: false,
        scales: {
            yAxes: [{
                ticks: {
                    suggestedMin: 0,
                },
                scaleLabel: {
                    display: true,
                    labelString: 'fietsers'
                },
                position: "left",
                id: "axis-count"
            },
            {
                ticks: {
                    suggestedMin: 0,
                },
                scaleLabel: {
                    display: true,
                    labelString: 'procent'
                },
                position: "left",
                id: "axis-percent"
            },
            {
                ticks: {
                    suggestedMin: 0,
                },
                scaleLabel: {
                    display: true,
                    labelString: 'seconden'
                },
                position: "right",
                id: "axis-seconds"
            },
            {
                ticks: {
                    suggestedMin: 0,
                },
                scaleLabel: {
                    display: true,
                    labelString: 'minuten'
                },
                position: "right",
                id: "axis-minutes"
            }]
        },
        legend: {
            position: 'bottom'
        },
        tooltips: {
            callbacks: {
                label: function(tooltipItem, data) {
                    var label = data.datasets[tooltipItem.datasetIndex].label || '';
                    if (label) {
                        label += ': ';
                    }
                    label += tooltipItem.yLabel;
                    label += ' ' + chart.scales[data.datasets[tooltipItem.datasetIndex].yAxisID].options.scaleLabel.labelString;
                    return label;
                }
            }
        }
    }
});
//add data points
chart.data = JSON.parse('<?php echo $data['result']; ?>');
//disable line area fill
for (var i = 0; i < chart.data.datasets.length; i++) {
    chart.data.datasets[i].fill = false;
    chart.data.datasets[i].borderColor = randomColor();
    chart.data.datasets[i].backgroundColor = chart.data.datasets[i].borderColor;
}
chart.update();

function randomColor() {
    var r = Math.floor(Math.random() * 255);
    var g = Math.floor(Math.random() * 255);
    var b = Math.floor(Math.random() * 255);
    return 'rgb(' + r + ',' + g + ',' + b + ')';
}
</script>

<p><b>Toelichting bij de grafiek</b></p>
<ul>
    <li>gemiddeld aantal fietsers per uur in periode op tijdstip</li>
</ul>