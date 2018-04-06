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
        <label>Symbol</label>
        <select id="symbol" name="symbol" class="form-control select2-hidden-accessible" required="1" data-placeholder="Enter Trade Symbol" rel="select2" name="dept" tabindex="-1" aria-hidden="true" aria-required="true">
            @foreach( $symbols as $col )
            <option values='{{ strtoupper($col->col) }}'>{{ strtoupper($col->col) }}</option>                        
            @endforeach
        </select>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-12">
                <div id="container" style="height: 600px; min-width: 310px"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')

<!--<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>-->
<script src="//code.highcharts.com/stock/highstock.js"></script>
<script src="//code.highcharts.com/stock/modules/drag-panes.js"></script>
<script src="//code.highcharts.com/stock/modules/exporting.js"></script>
<script src="//code.highcharts.com/stock/indicators/indicators.js"></script>
<script src="//code.highcharts.com/stock/indicators/pivot-points.js"></script>
<script src="//code.highcharts.com/stock/indicators/macd.js"></script>


<script>
var fnGenerateChart = function () {
    $.getJSON("{{ url(config('laraadmin.adminRoute') . '/get-chart') }}/" + $("#symbol option:selected").val(), function (data) {
        // split the data set into ohlc and volume
        var ohlc = [],
                volume = [],
                dataLength = data.totalMACDPoints,
                /* set the allowed units for data grouping  */
                groupingUnits = [['week',[1]], ['month',[1, 2, 3, 4, 6]]],
                i = 0;

        $.each(data.recentData, function(key, item) {
            ohlc.push([
                //data.recentData[key]["timestamp"], /* the date  */
                (data.recentData[key]["timestamp"] + (3.5 * 3600)),
                data.recentData[key]["open"],     /* open  */
                data.recentData[key]["high"],     /* high  */
                data.recentData[key]["low"],      /* low   */
                data.recentData[key]["close"]     /* close */
            ]);
        });
        /*
        raw = line = hist = [];
        for (i; i < data.totalMACDPoints; i += 1) {
            raw.push(data.macdData[0][i]);
            line.push(data.macdData[1][i]);
            hist.push(data.macdData[2][i]);
        }        
        if (data.totalMACDPoints > 0) {
            volume[0] = {
                name: "MACD Raw",
                data: raw
            };
            volume[1] = {
                name: "MACD Line",
                data: line
            };
            volume[3] = {
                name: "MACD Histrogram",
                data: hist
            };
        }
        */

        // create the chart
        Highcharts.stockChart('container', {
            rangeSelector: {
                buttons: [{
                    type: 'minute',
                    count: 1,
                    text: '1m'
                }, {
                    type: 'minute',
                    count: 5,
                    text: '5m'
                }, {
                    type: 'minute',
                    count: 10,
                    text: '10m'
                }, {
                    type: 'minute',
                    count: 15,
                    text: '15m'
                }, {
                    type: 'hour',
                    count: 1,
                    text: '1h'
                }, {
                    type: 'hour',
                    count: 2,
                    text: '2h'
                }, {
                    type: 'hour',
                    count: 4,
                    text: '4h'
                },{
                    type: 'hour',
                    count: 12,
                    text: '12h'
                },{
                    type: 'day',
                    count: 1,
                    text: '1d'
                }],
                selected: 4
            },            
            title: {
                text: data.symbol
            },
            subtitle: {
                text: 'With MACD and Pivot Points technical indicators'
            },
            yAxis: [{
                    labels: {
                        align: 'right',
                        x: -3
                    },
                    title: {
                        text: 'OHLC'
                    },
                    height: '50%',
                    lineWidth: 2,
                    resize: {
                        enabled: true
                    }
                }, {
                    top: '75%',
                    height: '25%',
                    labels: {
                        align: 'right',
                        x: -3
                    },
                    offset: 0,
                    title: {
                        text: 'MACD'
                    }
                }],
            tooltip: {
                split: true
            },
            navigator: {
                enabled: false
            },
            series: [{
                type: 'ohlc',
                id: 'candlestickSymbol',
                name: data.symbol,
                data: ohlc,
                zIndex: 1
            }, {
                type: 'pivotpoints',
                linkedTo: 'candlestickSymbol',
                zIndex: 0,
                lineWidth: 1,
                dataLabels: {
                    overflow: 'none',
                    crop: false,
                    y: 4,
                    style: {
                        fontSize: 9
                    }
                }
            }, {
                type: 'macd',
                yAxis: 1,
                linkedTo: 'candlestickSymbol',
                color:'blue',
                gapSize:3,
                macdLine: {
                    styles: {
                        lineColor:'green',
                        lineWidth:1,
                    }
                },
                signalLine:{
                    styles: {
                        lineColor:'red',
                        lineWidth:1,
                    }
                },
                params: {
                    shortPeriod: 12,
                    longPeriod: 26,
                    signalPeriod: 9,
                    period: 26
                }
            }]
        });

    });
}
$(function () {
    $('#symbol').on('change', function (e) {
        fnGenerateChart();
    });
    fnGenerateChart();
    setTimeout("fnGenerateChart", 3000);
});
</script>
@endpush