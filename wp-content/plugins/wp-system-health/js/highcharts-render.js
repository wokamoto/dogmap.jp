jQuery(document).ready(function($) {

    $.getJSON(wpsh_highcharts.cpu_usage, function(data) {
		
		var prefill = [];
		var tzd = (new Date()).getTimezoneOffset()*60000;
		for (i=0; i<=60; i++) {
			prefill.push([data[0] - (60-i)*2000 - tzd, (i==60 ? data[1] : 0), 0]);//data[1]*(i/60), data[2]]);
		}
		
    	window.chart = new Highcharts.Chart({
    	
		    chart: {
				borderColor: '#DFDFDF',
				borderWidth: 2,
		        renderTo: 'wpsh-highcharts',
		        type: 'area'/*'arearange',
		        zoomType: 'x'*/
		    },
			
			credits: {
				href: 'http://www.code-styling.de',
				text: 'WP System Health'
			},
		    
		    title: {
		        text: wpsh_highcharts.label_cpu_history
		    },
		
		    xAxis: {
		        type: 'datetime',
				labels: {
					formatter: function() {
						return Highcharts.dateFormat('%H:%M:%S', this.value);
					}
				}
		    },
		    
		    yAxis: {
		        title: {
		            text: wpsh_highcharts.label_cpu_percent
		        },
				minorTickInterval: 'auto',
				minorTickLength: 0,
				min: 0,
				//max: 100.0,
				tickInterval: 10.0
		    },
		
			plotOptions: {
				area: {
					marker: {
						enabled: false
					},
					lineWidth: 1
				},
				series: {
					shadow: false
				}
			},
			
		    tooltip: {
		        crosshairs: true,
				backgroundColor: '#FCFFC5',
				borderColor: '#ECEFB5',
				formatter: function() {
                        return '<b>'+ Highcharts.dateFormat('%d.%m.%Y', this.x) +'</b><br/>'+ Highcharts.dateFormat('%H:%M:%S', this.x) + ' | ' + this.point.y +' %';
                }
		    },
		    
		    legend: {
		        enabled: false
		    },
		
		    series: [{
		        name: wpsh_highcharts.label_cpu_history,
				data: prefill
		    }]
		
		},
		// Let the music play
		function(chart) {
				setInterval(function() {
					$.getJSON(wpsh_highcharts.cpu_usage, function(data) {
					data[0] -= (new Date()).getTimezoneOffset()*60000;
					chart.series[0].addPoint(
						data, 
						true, 
						true
					);
				});
			}, 3000);
		});
    });
    
});