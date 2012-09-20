(function($){
    $.fn.equalHeights = function() {
        var currentTallest = 0;
        $(this).each(function(){
            if ($(this).height() > currentTallest) {
                currentTallest = $(this).height();
            }
        });
        $(this).height(currentTallest);
        return this;
    };
    $(".half").equalHeights();

        chart = new Highcharts.Chart({
            chart: {
                renderTo: 'booster-chart',
                zoomType: 'xy'
            },
            title: {
                text: 'WP Booster Statistical Information'
            },
            xAxis: [{
                categories: categories
            }],
            yAxis: [{ // Primary yAxis
                allowDecimals: false,
                gridLineWidth: 0,
                title: {
                    text: 'Used Points',
                    style: {
                        color: '#ffcc55'
                    }
                },
                labels: {
                    formatter: function() {
                        return this.value +' points';
                    },
                    style: {
                        color: '#ffcc55'
                    }
                }
            }, {
                labels: {
                    formatter: function() {
                        return this.value +' MB';
                    },
                    style: {
                        color: '#89A54E'
                    }
                },
                title: {
                    text: 'Transfer',
                    style: {
                        color: '#89A54E'
                    }
                },
                opposite: true
            }, {
                gridLineWidth: 0,
                title: {
                    text: 'Requests',
                    style: {
                        color: '#4572A7'
                    }
                },
                labels: {
                    formatter: function() {
                        return this.value +' req';
                    },
                    style: {
                        color: '#4572A7'
                    }
                },
                opposite: true
            }],
            tooltip: {
                formatter: function() {
                    var unit = {
                        'Points': 'point',
                        'Requests': 'req',
                        'Transfer': 'MB'
                    }[this.series.name];
                    return ''+
                        this.x +': '+ this.y +' '+ unit;
                }
            },
            legend: {
                layout: 'vertical',
                align: 'left',
                x: 120,
                verticalAlign: 'top',
                y: 80,
                floating: true,
                backgroundColor: '#FFFFFF'
            },
            series: [{
                name: 'Points',
                color: '#ffcc55',
                type: 'column',
                data: used
            }, {
                name: 'Requests',
                color: '#4572A7',
                type: 'spline',
                yAxis: 2,
                data: requests
            }, {
                name: 'Transfer',
                color: '#89A54E',
                type: 'spline',
                yAxis: 1,
                data: transfers
            }]
        });
})(jQuery);

