@extends("la.layouts.app")

@section("contentheader_title", "MACD Chart")
@section("contentheader_description", "MACD Chart")
@section("section", "ConfigureMACDBots")
@section("sub_section", "Chart")
@section("htmlheader_title", "MACD Chart")

@section("main-content")

@if (count($errors) > 0)
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="box box-success">
    <div class="box-header">        
        <div class="col-md-6">
            <label>Symbol</label>
            <select id="symbol" name="symbol" class="form-control select2-hidden-accessible" required="1" data-placeholder="Enter Trade Symbol" rel="select2" name="dept" tabindex="-1" aria-hidden="true" aria-required="true">
                @foreach( $symbols as $col )
                <option values='{{ strtoupper($col->col) }}'>{{ strtoupper($col->col) }}</option>                        
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label>Ticker points</label>
            <select id="tickerPoint" name="tickerPoint" class="form-control">
                <option value="200" selected="selected">200</option>
                <option value="400">400</option>
                <option value="600">600</option>
                <option value="800">800</option>
                <option value="999">999</option>
            </select>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-12">
                <canvas id="containerOHLC"></canvas>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <canvas id="container"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.bundle.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.13.0/moment.min.js"></script>
<script src="{{ asset('la-assets/js/utils.js') }}"></script>
<script>
var fnGenerateChart = function () {
            $.getJSON("{{ url('/get-chart') }}/" + $("#symbol option:selected").val() + "/" + $("#tickerPoint option:selected").val(), function (data) {
                // split the data set into ohlc and volume
                var openDS = [],
                    highDS = [],
                    lowDS = [],
                    closeDS = [],
                    dateTime = [],
                    macd = [];
                    signal = [];
                    hist = [];
                    macdAdvise = [];
                    macd1 = signal1 = hist1 = [];
                
                $.each(data.recentData, function (key, item) {
                    //dateTime.push(data.recentData[key]["keyTime"]);
                    dateTime.push(data.recentData[key]["closeTime"]);
                    openDS.push(parseFloat(data.recentData[key]["open"] * 100000).toFixed(6));
                    highDS.push(parseFloat(data.recentData[key]["high"] * 100000).toFixed(6));
                    lowDS.push(parseFloat(data.recentData[key]["low"] * 100000).toFixed(6));
                    closeDS.push(parseFloat(data.recentData[key]["close"] * 100000).toFixed(6));

                    macd.push(parseFloat(data.recentData[key]["macd"] * 100000).toFixed(6));
                    signal.push(parseFloat(data.recentData[key]["macds"] * 100000).toFixed(6));
                    hist.push(parseFloat(data.recentData[key]["macdh"] * 100000).toFixed(6));
                    macdAdvise.push(data.recentData[key]['advice']);
                });
                
                var minArray = [Math.min.apply(Math, macd), Math.min.apply(Math, signal), Math.min.apply(Math, hist)];
                var maxArray = [Math.max.apply(Math, macd), Math.max.apply(Math, signal), Math.max.apply(Math, hist)];

                var calMin = parseFloat(Math.min.apply(Math, minArray));
                var calMax = parseFloat(Math.max.apply(Math, maxArray));
                
                const colours = hist.map((value) => value < 0 ? window.chartColors.red : window.chartColors.green);
                
                var config = {
                    type: 'bar',
                    data: {
                        labels: dateTime,
                        fill: false,
                        datasets: [
                            {
                                type: 'line',
                                label: 'MACD',
                                backgroundColor: window.chartColors.blue,
                                borderColor: window.chartColors.blue,
                                borderWidth: 1,
                                data: macd,
                                fill: false,
                                lineTension: 0, 
                                cubicInterpolationMode: 'default', 
                                radius: 0
                            },
                            {
                                type: 'line',                                
                                label: 'MACDS',
                                backgroundColor: window.chartColors.red,
                                borderColor: window.chartColors.red,
                                borderWidth: 1,
                                data: signal,
                                fill: false,
                                lineTension: 0, 
                                cubicInterpolationMode: 'default', 
                                radius: 0
                            },
                            {
                                label: 'MACDH',
                                backgroundColor: colours,
                                borderColor: colours,
                                borderWidth: 1,
                                data: hist,
                                fill: true,
                                lineTension: 0, 
                                cubicInterpolationMode: 'default', 
                                radius: 0
                            }                   
                        ]
                    },
                    options: {
                        responsive: true,
                        title: {
                            display: true,
                            text: 'Price Chart'
                        },
                        tooltips: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                // Use the footer callback to display the sum of the items showing in the tooltip
                                afterBody: function(tooltipItems, data) {
                                    var adviseText = '';
                                    tooltipItems.forEach(function(tooltipItem) {
                                            adviseText = '- Advise: ' + macdAdvise[tooltipItem.index];
                                    });
                                    return adviseText;
                                }
                            }
                        },
                        hover: {
                            mode: 'nearest',
                            intersect: true
                        },
                        scales: {
                            xAxes: [{
                                    display: true,
                                    beginAtZero: true,
                                    scaleLabel: {
                                        display: true,
                                        labelString: 'Date/Time'
                                    },
                                    ticks: {
                                        callback: function(value, index, values) {
                                            if(index % 3 === 0) {
                                                var myDate = new Date(value);
                                                return myDate.getMonth() + 1 + "-" + myDate.getDate()+ "-" + myDate.getFullYear();
                                            }
                                            return "";
                                        }
                                    }
                                }],
                            yAxes: [{
                                    display: true,
                                    scaleLabel: {
                                        display: true,
                                        labelString: 'MACD'
                                    },
                                    ticks: {
                                        min: calMin,
                                        max: calMax,
                                        beginAtZero: true,
                                        userCallback: function (label, index, labels) {
                                            return parseFloat(label).toFixed(3);
                                        }
                                    }
                                }]
                        }
                    }
                };

                var ctx = document.getElementById('container').getContext('2d');
                window.myLine = new Chart(ctx, config);

                minArray = [Math.min.apply(Math, openDS), Math.min.apply(Math, highDS), Math.min.apply(Math, lowDS), Math.min.apply(Math, closeDS)];
                maxArray = [Math.max.apply(Math, openDS), Math.max.apply(Math, highDS), Math.max.apply(Math, lowDS), Math.max.apply(Math, closeDS)];

                calMin = parseFloat(Math.min.apply(Math, minArray));
                calMax = parseFloat(Math.max.apply(Math, maxArray));

                var configOHLC = {
                    type: 'line',
                    data: {
                        labels: dateTime,
                        datasets: [{
                                label: 'Open',
                                backgroundColor: window.chartColors.green,
                                borderColor: window.chartColors.green,
                                data: openDS,
                                fill: false,
                                lineTension: 0, 
                                cubicInterpolationMode: 'default', 
                                radius: 0,
                                borderWidth: 1,
                            },
                            {
                                label: 'Close',
                                backgroundColor: window.chartColors.red,
                                borderColor: window.chartColors.red,
                                data: closeDS,
                                fill: false,
                                lineTension: 0, 
                                cubicInterpolationMode: 'default', 
                                radius: 0,
                                borderWidth: 1,
                            },
                            {
                                label: 'High',
                                backgroundColor: window.chartColors.blue,
                                borderColor: window.chartColors.blue,
                                data: highDS,
                                fill: false,
                                lineTension: 0, 
                                cubicInterpolationMode: 'default', 
                                radius: 0,
                                borderWidth: 1,
                            },
                            {
                                label: 'Low',
                                backgroundColor: window.chartColors.purple,
                                borderColor: window.chartColors.purple,
                                data: lowDS,
                                fill: false,
                                lineTension: 0, 
                                cubicInterpolationMode: 'default', 
                                radius: 0,
                                borderWidth: 1,
                            }] 
                    },
                    options: {
                        responsive: true,
                        title: {
                            display: true,
                            text: 'OHLC'
                        },
                        tooltips: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                afterBody: function(tooltipItems, data) {
                                    var adviseText = '';
                                    tooltipItems.forEach(function(tooltipItem) {
                                            adviseText = '- Advise: ' + macdAdvise[tooltipItem.index];
                                    });
                                    return adviseText;
                                }
                            }
                        },
                        hover: {
                            mode: 'nearest',
                            intersect: true
                        },
                        scales: {
                            xAxes: [{
                                    display: true,
                                    scaleLabel: {
                                        display: true,
                                        labelString: 'Date/Time'
                                    },
                                    ticks: {
                                        callback: function(value, index, values) {
                                            if(index % 3 === 0) {
                                                var myDate = new Date(value);
                                                return myDate.getMonth() + 1 + "-" + myDate.getDate()+ "-" + myDate.getFullYear();
                                            }
                                            return "";
                                        }
                                    },
                                    showMaxMin: false
                                }],
                            yAxes: [{
                                    display: true,
                                    scaleLabel: {
                                        display: true,
                                        labelString: 'MACD'
                                    },
                                    ticks: {
                                        min: calMin,
                                        max: calMax,
                                        beginAtZero: true,
                                        userCallback: function (label, index, labels) {
                                            return parseFloat(label).toFixed(3);
                                        }
                                    }
                                }]
                        }
                    }
                };
                var ctxOHLC = document.getElementById('containerOHLC').getContext('2d');
                window.myLine = new Chart(ctxOHLC, configOHLC);
            });
        };

        $(function () {
            $('#symbol').on('change', function (e) {
                fnGenerateChart();
            });
            $('#tickerPoint').on('change', function (e) {
                fnGenerateChart();
            });
            fnGenerateChart();
            //setTimeout("fnGenerateChart", 3000);
        });    
{{--
var fnGenerateChart = function () {
    $.getJSON("{{ url('/get-chart') }}/" + $("#symbol option:selected").val(), function (data) {
        // split the data set into ohlc and volume
        var openDS = [],
            highDS = [],
            lowDS = [],
            closeDS = [],
            dateTime = [],
            macd = [];
            signal = [];
            hist = [];
            macd1 = signal1 = hist1 = [];

        $.each(data.recentData, function (key, item) {
            dateTime.push(data.recentData[key]["keyTime"]);
            openDS.push(parseFloat(data.recentData[key]["open"] * 100000).toFixed(6));
            highDS.push(parseFloat(data.recentData[key]["high"] * 100000).toFixed(6));
            lowDS.push(parseFloat(data.recentData[key]["low"] * 100000).toFixed(6));
            closeDS.push(parseFloat(data.recentData[key]["close"] * 100000).toFixed(6));

            macd.push(parseFloat(data.recentData[key]["macd"] * 100000).toFixed(6));
            signal.push(parseFloat(data.recentData[key]["macds"] * 100000).toFixed(6));
            hist.push(parseFloat(data.recentData[key]["macdh"] * 100000).toFixed(6));
        });

        var minArray = [Math.min.apply(Math, macd), Math.min.apply(Math, signal), Math.min.apply(Math, hist)];
        var maxArray = [Math.max.apply(Math, macd), Math.max.apply(Math, signal), Math.max.apply(Math, hist)];

        var calMin = parseFloat(Math.min.apply(Math, minArray));
        var calMax = parseFloat(Math.max.apply(Math, maxArray));

        const colours = hist.map((value) => value < 0 ? window.chartColors.red : window.chartColors.green);

        var config = {
            type: 'bar',
            data: {
                labels: dateTime,
                fill: false,
                datasets: [
                    {
                        type: 'line',
                        label: 'MACD',
                        backgroundColor: window.chartColors.blue,
                        borderColor: window.chartColors.blue,
                        borderWidth: 1,
                        data: macd,
                        fill: false,
                        lineTension: 0, 
                        cubicInterpolationMode: 'default', 
                        radius: 0
                    },
                    {
                        type: 'line',                                
                        label: 'MACDS',
                        backgroundColor: window.chartColors.red,
                        borderColor: window.chartColors.red,
                        borderWidth: 1,
                        data: signal,
                        fill: false,
                        lineTension: 0, 
                        cubicInterpolationMode: 'default', 
                        radius: 0
                    },
                    {
                        label: 'MACDH',
                        backgroundColor: colours,
                        borderColor: colours,
                        borderWidth: 1,
                        data: hist,
                        fill: true,
                        lineTension: 0, 
                        cubicInterpolationMode: 'default', 
                        radius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                title: {
                    display: true,
                    text: 'Price Chart'
                },
                tooltips: {
                    mode: 'index',
                    intersect: false
                },
                hover: {
                    mode: 'nearest',
                    intersect: true
                },
                scales: {
                    xAxes: [{
                            display: true,
                            beginAtZero: true,
                            scaleLabel: {
                                display: true,
                                labelString: 'Date/Time'
                            }
                        }],
                    yAxes: [{
                            display: true,
                            scaleLabel: {
                                display: true,
                                labelString: 'MACD'
                            },
                            ticks: {
                                min: calMin,
                                max: calMax,
                                beginAtZero: true,
                                userCallback: function (label, index, labels) {
                                    return parseFloat(label).toFixed(6);
                                }
                            }
                        }]
                }
            }
        };

        var ctx = document.getElementById('container').getContext('2d');
        window.myLine = new Chart(ctx, config);

        minArray = [Math.min.apply(Math, openDS), Math.min.apply(Math, highDS), Math.min.apply(Math, lowDS), Math.min.apply(Math, closeDS)];
        maxArray = [Math.max.apply(Math, openDS), Math.max.apply(Math, highDS), Math.max.apply(Math, lowDS), Math.max.apply(Math, closeDS)];

        calMin = parseFloat(Math.min.apply(Math, minArray));
        calMax = parseFloat(Math.max.apply(Math, maxArray));

        var configOHLC = {
            type: 'line',
            data: {
                labels: dateTime,
                datasets: [{
                        label: 'Open',
                        backgroundColor: window.chartColors.green,
                        borderColor: window.chartColors.green,
                        data: openDS,
                        fill: false,
                        lineTension: 0, 
                        cubicInterpolationMode: 'default', 
                        radius: 0,
                        borderWidth: 1,
                    },
                    {
                        label: 'Close',
                        backgroundColor: window.chartColors.red,
                        borderColor: window.chartColors.red,
                        data: closeDS,
                        fill: false,
                        lineTension: 0, 
                        cubicInterpolationMode: 'default', 
                        radius: 0,
                        borderWidth: 1,
                    },
                    {
                        label: 'High',
                        backgroundColor: window.chartColors.blue,
                        borderColor: window.chartColors.blue,
                        data: highDS,
                        fill: false,
                        lineTension: 0, 
                        cubicInterpolationMode: 'default', 
                        radius: 0,
                        borderWidth: 1,
                    },
                    {
                        label: 'Low',
                        backgroundColor: window.chartColors.purple,
                        borderColor: window.chartColors.purple,
                        data: lowDS,
                        fill: false,
                        lineTension: 0, 
                        cubicInterpolationMode: 'default', 
                        radius: 0,
                        borderWidth: 1,
                    }] 
            },
            options: {
                responsive: true,
                title: {
                    display: true,
                    text: 'OHLC'
                },
                tooltips: {
                    mode: 'index',
                    intersect: false
                },
                hover: {
                    mode: 'nearest',
                    intersect: true
                },
                scales: {
                    xAxes: [{
                            display: true,
                            scaleLabel: {
                                display: true,
                                labelString: 'Date/Time'
                            },
                            tickFormat: function (d) {
                                return new Date(d);
                            },
                            showMaxMin: false
                        }],
                    yAxes: [{
                            display: true,
                            scaleLabel: {
                                display: true,
                                labelString: 'MACD'
                            },
                            ticks: {
                                min: calMin,
                                max: calMax,
                                beginAtZero: true,
                                userCallback: function (label, index, labels) {
                                    return parseFloat(label).toFixed(6);
                                }
                            }
                        }]
                }
            }
        };
        var ctxOHLC = document.getElementById('containerOHLC').getContext('2d');
        window.myLine = new Chart(ctxOHLC, configOHLC);
    });
};

$(function () {
    $('#symbol').on('change', function (e) {
        fnGenerateChart();
    });
    fnGenerateChart();
    setTimeout("fnGenerateChart", 3000);
});
--}}
</script>
@endpush