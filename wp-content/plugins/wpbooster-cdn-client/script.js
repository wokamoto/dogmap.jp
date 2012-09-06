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

    new Highcharts.Chart({
        chart: {
            renderTo: 'booster-chart',
            type: 'line'
        },
        title: {
            text: 'Data Transfer'
        },
        xAxis: {
            categories: categories
        },
        yAxis: {
            title: {
                text: 'Bytes'
            }
        },
        series: [{
            name: 'Daily Transfers',
            data: transfers
        }]
     });
})(jQuery);

