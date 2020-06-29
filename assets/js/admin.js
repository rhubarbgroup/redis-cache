( function ( $ ) {
    window.rediscache = {
        metrics: {
            computed: null,
            names: {
                h: 'hits',
                m: 'misses',
                r: 'ratio',
                b: 'bytes',
                t: 'time',
                c: 'calls',
            },
        },
    };

    // executed on page load
    $( function () {
        if ( $( '#widget-redis-stats' ).length ) {
            rediscache.metrics.computed = compute_metrics(
                rediscache_metrics,
                rediscache.metrics.names
            );

            setup_charts();
            render_chart( 'time' );
        }

        $( '#widget-redis-stats ul a' ).on(
            'click',
            function ( event ) {
                event.preventDefault();

                $('#widget-redis-stats .active').removeClass('active');
                $(this).blur().addClass('active');

                render_chart(
                    $(event.target).data('chart')
                );
            }
        );

        $( '.notice.is-dismissible[data-dismissible]' ).on(
            'click.roc-dismiss-notice',
            '.notice-dismiss',
            function ( event ) {
                event.preventDefault();

                $.post( ajaxurl, {
                    notice: $( this ).parent().attr( 'data-dismissible' ),
                    action: 'roc_dismiss_notice',
                } );
            }
        );
    } );

    var compute_metrics = function ( raw_metrics, metric_names ) {
        var metrics = {};

        // parse raw metrics in blocks of minutes
        for ( var entry in raw_metrics ) {
            var values = {};
            var timestamp = raw_metrics[ entry ];
            var minute = ( timestamp - timestamp % 60 ) * 1000;

            entry.split( ';' ).forEach(
                function ( value ) {
                    var metric = value.split( '=' );

                    if ( metric_names[ metric[0] ] ) {
                          values[ metric_names[ metric[0] ] ] = Number( metric[1] );
                    }
                }
            );

            if ( ! metrics[ minute ] ) {
                metrics[ minute ] = [];
            }

            metrics[ minute ].push( values );
        }

        // calculate median value for each block
        for ( var entry in metrics ) {
            if ( metrics[ entry ].length === 1 ) {
                metrics[ entry ] = metrics[ entry ].shift();
                continue;
            }

            var medians = {};

            for ( var key in metric_names ) {
                var name = metric_names[ key ];

                medians[ name ] = compute_median(
                    metrics[ entry ].map(
                        function ( metric ) {
                            return metric[ name ];
                        }
                    )
                )
            }

            metrics[ entry ] = medians;
        }

        var computed = [];

        for ( var timestamp in metrics ) {
            var entry = metrics[ timestamp ];

            entry.date = Number( timestamp );
            entry.time = entry.time * 1000;

            computed.push( entry );
        }

        computed.sort(
            function( a, b ) {
                return a.date - b.date;
            }
        );

        return computed;
    };

    var compute_median = function ( numbers ) {
        var median = 0;
        var numsLen = numbers.length;

        numbers.sort();

        if ( numsLen % 2 === 0 ) {
            median = ( numbers[ numsLen / 2 - 1 ] + numbers[ numsLen / 2 ] ) / 2;
        } else {
            median = numbers[ ( numsLen - 1 ) / 2 ];
        }

        return median;
    };

    var render_chart = function ( id ) {
        if ( window.rediscache_chart ) {
            window.rediscache_chart.updateOptions( rediscache_charts[id] );
            return;
        }

        var chart = new ApexCharts(
            document.querySelector( '#redis-stats-chart' ),
            rediscache_charts[id]
        );

        chart.render();
        window.rediscache_chart = chart;
    };

    var setup_charts = function () {

        var time = rediscache.metrics.computed.map(
            function ( entry ) {
                return [entry.date, entry.time];
            }
        );

        var timeMedian = compute_median(
            time.map(
                function ( entry ) {
                    return entry[1];
                }
            )
        );

        var bytes = rediscache.metrics.computed.map(
            function ( entry ) {
                return [entry.date, entry.bytes];
            }
        )

        var bytesMedian = compute_median(
            bytes.map(
                function ( entry ) {
                    return entry[1];
                }
            )
        );

        var ratio = rediscache.metrics.computed.map(
            function ( entry ) {
                return [entry.date, entry.ratio];
            }
        );

        var ratioMedian = compute_median(
            ratio.map(
                function ( entry ) {
                    return entry[1];
                }
            )
        );

        var calls = rediscache.metrics.computed.map(
            function ( entry ) {
                return [entry.date, entry.calls];
            }
        );

        var callsMedian = compute_median(
            calls.map(
                function ( entry ) {
                    return entry[1];
                }
            )
        );

        rediscache_charts.time.series = [{
            name: 'Time',
            type: 'area',
            data: time,
        }, {
            name: 'Pro',
            type: 'line',
            data: time.map(
                function ( entry ) {
                    return [entry[0], entry[1] * 0.5];
                }
            ),
        } ];

        rediscache_charts.time.annotations.texts[0].text = Math.round( timeMedian ) + ' ms';

        rediscache_charts.bytes.series = [{
            name: 'Bytes',
            type: 'area',
            data: bytes,
        }, {
            name: 'Pro',
            type: 'line',
            data: bytes.map(
                function ( entry ) {
                    return [entry[0], entry[1] * 0.3];
                }
            ),
        } ];

        rediscache_charts.bytes.annotations.texts[0].text = Math.round( bytesMedian / 1024 ) + ' KB';

        rediscache_charts.ratio.series = [{
            name: 'Ratio',
            type: 'area',
            data: ratio,
        }];

        rediscache_charts.ratio.annotations.texts[0].text = Math.round( ratioMedian ) + '%';

        rediscache_charts.calls.series = [{
            name: 'Calls',
            type: 'area',
            data: calls,
        }, {
            name: 'Pro',
            type: 'line',
            data: calls.map(
                function ( entry ) {
                    return [entry[0], Math.round( entry[1] / 50 ) + 5];
                }
            ),
        } ];

        rediscache_charts.calls.annotations.texts[0].text = Math.round( callsMedian );
    };
} ( jQuery ) );

var rediscache_charts = {

    time: {
        stroke: {
            width: [2, 2],
            curve: 'smooth',
            dashArray: [0, 8]
        },
        colors: [
            '#0096dd',
            '#72777c',
        ],
        annotations: {
            texts: [{ x: '15%', y: '30%', fontSize: '20px', fontWeight: 600, fontFamily: 'inherit', foreColor: '#72777c' }],
        },
        chart: {
            type: 'line',
            height: '100%',
            toolbar: { show: false },
            zoom: { enabled: false },
            animations: { enabled: false }
        },
        dataLabels: {
            enabled: false,
        },
        legend: {
            show: false,
        },
        fill: {
            opacity: [0.25, 1],
        },
        yaxis: {
            type: 'numeric',
            tickAmount: 4,
            min: 0,
            labels: {
                style: { colors: '#72777c', fontSize: '13px', fontFamily: 'inherit' },
                formatter: function ( value ) {
                    return Math.round( value ) + ' ms';
                },
            },
        },
        xaxis: {
            type: 'datetime',
            labels: {
                format: 'HH:mm',
                datetimeUTC: false,
                style: { colors: '#72777c', fontSize: '13px', fontFamily: 'inherit' },
            },
            tooltip: { enabled: false },
        },
        tooltip: {
            fixed: {
                enabled: true,
                position: 'bottomLeft',
                offsetY: 30,
                offsetX: 0,
            },
            custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                return '<div class="apexcharts-tooltip-title">'
                    + new Date( w.globals.seriesX[seriesIndex][dataPointIndex] ).toTimeString().slice( 0, 5 )
                    + '</div>'
                    + '<div class="apexcharts-tooltip-series-group" style="display: flex;">'
                    + '  <span class="apexcharts-tooltip-marker" style="background-color: rgb(0, 143, 251);"></span>'
                    + '  <div class="apexcharts-tooltip-text">'
                    + '    <div class="apexcharts-tooltip-y-group">'
                    + '      <span class="apexcharts-tooltip-text-label">' + w.globals.seriesNames[0] + ': </span>'
                    + '      <span class="apexcharts-tooltip-text-value">' + Math.round( series[0][dataPointIndex] * 100 ) / 100 + ' ms</span>'
                    + '    </div>'
                    + '  </div>'
                    + '</div>'
                    + '<div class="apexcharts-tooltip-series-group" style="display: flex;">'
                    + '  <span class="apexcharts-tooltip-marker" style="background-color: #72777c;"></span>'
                    + '  <div class="apexcharts-tooltip-text">'
                    + '    <div class="apexcharts-tooltip-y-group">'
                    + '      <span class="apexcharts-tooltip-text-label">Redis Cache Pro</span>'
                    + '    </div>'
                    + '  </div>'
                    + '</div>';
            },
        },
    },

    bytes: {
        stroke: {
            width: [2, 2],
            curve: 'smooth',
            dashArray: [0, 8]
        },
        colors: [
            '#0096dd',
            '#72777c',
        ],
        annotations: {
            texts: [{ x: '15%', y: '30%', fontSize: '20px', fontWeight: 600, fontFamily: 'inherit', foreColor: '#72777c' }],
        },
        chart: {
            type: 'line',
            toolbar: { show: false },
            zoom: { enabled: false },
            animations: { enabled: false }
        },
        dataLabels: {
            enabled: false,
        },
        legend: {
            show: false,
        },
        fill: {
            opacity: [0.25, 1],
        },
        yaxis: {
            type: 'numeric',
            tickAmount: 4,
            min: 0,
            labels: {
                style: { colors: '#72777c', fontSize: '13px', fontFamily: 'inherit' },
                formatter: function ( value ) {
                    return Math.round( value / 1024 ) + ' KB';
                },
            },
        },
        xaxis: {
            type: 'datetime',
            labels: {
                format: 'HH:mm',
                datetimeUTC: false,
                style: { colors: '#72777c', fontSize: '13px', fontFamily: 'inherit' },
            },
            tooltip: { enabled: false },
        },
        tooltip: {
            fixed: {
                enabled: true,
                position: 'bottomLeft',
                offsetY: 30,
                offsetX: 0,
            },
            custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                return '<div class="apexcharts-tooltip-title">'
                    + new Date( w.globals.seriesX[seriesIndex][dataPointIndex] ).toTimeString().slice( 0, 5 )
                    + '</div>'
                    + '<div class="apexcharts-tooltip-series-group" style="display: flex;">'
                    + '  <span class="apexcharts-tooltip-marker" style="background-color: #0096dd;"></span>'
                    + '  <div class="apexcharts-tooltip-text">'
                    + '    <div class="apexcharts-tooltip-y-group">'
                    + '      <span class="apexcharts-tooltip-text-label">' + w.globals.seriesNames[0] + ': </span>'
                    + '      <span class="apexcharts-tooltip-text-value">' + Math.round( series[0][dataPointIndex] / 1024 ) + ' KB</span>'
                    + '    </div>'
                    + '  </div>'
                    + '</div>'
                    + '<div class="apexcharts-tooltip-series-group" style="display: flex;">'
                    + '  <span class="apexcharts-tooltip-marker" style="background-color: #72777c;"></span>'
                    + '  <div class="apexcharts-tooltip-text">'
                    + '    <div class="apexcharts-tooltip-y-group">'
                    + '      <span class="apexcharts-tooltip-text-label">Redis Cache Pro</span>'
                    + '    </div>'
                    + '  </div>'
                    + '</div>';
            },
        },
    },

    ratio: {
        stroke: {
            width: [2, 2],
            curve: 'smooth',
            dashArray: [0, 8]
        },
        colors: [
            '#0096dd',
            '#72777c',
        ],
        annotations: {
            texts: [{ x: '15%', y: '30%', fontSize: '20px', fontWeight: 600, fontFamily: 'inherit', foreColor: '#72777c' }],
        },
        chart: {
            type: 'line',
            toolbar: { show: false },
            zoom: { enabled: false },
            animations: { enabled: false }
        },
        dataLabels: {
            enabled: false,
        },
        legend: {
            show: false,
        },
        fill: {
            opacity: [0.25, 1],
        },
        yaxis: {
            type: 'numeric',
            tickAmount: 4,
            min: 0,
            max: 100,
            labels: {
                style: { colors: '#72777c', fontSize: '13px', fontFamily: 'inherit' },
                formatter: function ( value ) {
                    return Math.round( value ) + '%';
                },
            },
        },
        xaxis: {
            type: 'datetime',
            labels: {
                format: 'HH:mm',
                datetimeUTC: false,
                style: { colors: '#72777c', fontSize: '13px', fontFamily: 'inherit' },
            },
            tooltip: { enabled: false },
        },
        tooltip: {
            fixed: {
                enabled: true,
                position: 'bottomLeft',
                offsetY: 30,
                offsetX: 0,
            },
            custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                return '<div class="apexcharts-tooltip-title">'
                    + new Date( w.globals.seriesX[seriesIndex][dataPointIndex] ).toTimeString().slice( 0, 5 )
                    + '</div>'
                    + '<div class="apexcharts-tooltip-series-group" style="display: flex;">'
                    + '  <span class="apexcharts-tooltip-marker" style="background-color: rgb(0, 143, 251);"></span>'
                    + '  <div class="apexcharts-tooltip-text">'
                    + '    <div class="apexcharts-tooltip-y-group">'
                    + '      <span class="apexcharts-tooltip-text-label">' + w.globals.seriesNames[0] + ': </span>'
                    + '      <span class="apexcharts-tooltip-text-value">' + Math.round( series[0][dataPointIndex] * 100 ) / 100 + '%</span>'
                    + '    </div>'
                    + '  </div>'
                    + '</div>';
            },
        },
    },

    calls: {
        stroke: {
            width: [2, 2],
            curve: 'smooth',
            dashArray: [0, 8]
        },
        colors: [
            '#0096dd',
            '#72777c',
        ],
        annotations: {
            texts: [{ x: '15%', y: '30%', fontSize: '20px', fontWeight: 600, fontFamily: 'inherit', foreColor: '#72777c' }],
        },
        chart: {
            type: 'line',
            toolbar: { show: false },
            zoom: { enabled: false },
            animations: { enabled: false }
        },
        dataLabels: {
            enabled: false,
        },
        legend: {
            show: false,
        },
        fill: {
            opacity: [0.25, 1],
        },
        yaxis: {
            type: 'numeric',
            tickAmount: 4,
            min: 0,
            labels: {
                style: { colors: '#72777c', fontSize: '13px', fontFamily: 'inherit' },
                formatter: function ( value ) {
                    return Math.round( value );
                },
            },
        },
        xaxis: {
            type: 'datetime',
            labels: {
                format: 'HH:mm',
                datetimeUTC: false,
                style: { colors: '#72777c', fontSize: '13px', fontFamily: 'inherit' },
            },
            tooltip: { enabled: false },
        },
        tooltip: {
            fixed: {
                enabled: true,
                position: 'bottomLeft',
                offsetY: 30,
                offsetX: 0,
            },
            custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                return '<div class="apexcharts-tooltip-title">'
                    + new Date( w.globals.seriesX[seriesIndex][dataPointIndex] ).toTimeString().slice( 0, 5 )
                    + '</div>'
                    + '<div class="apexcharts-tooltip-series-group" style="display: flex;">'
                    + '  <span class="apexcharts-tooltip-marker" style="background-color: #0096dd;"></span>'
                    + '  <div class="apexcharts-tooltip-text">'
                    + '    <div class="apexcharts-tooltip-y-group">'
                    + '      <span class="apexcharts-tooltip-text-label">' + w.globals.seriesNames[0] + ': </span>'
                    + '      <span class="apexcharts-tooltip-text-value">' + Math.round( series[0][dataPointIndex] ) + '</span>'
                    + '    </div>'
                    + '  </div>'
                    + '</div>'
                    + '<div class="apexcharts-tooltip-series-group" style="display: flex;">'
                    + '  <span class="apexcharts-tooltip-marker" style="background-color: #72777c;"></span>'
                    + '  <div class="apexcharts-tooltip-text">'
                    + '    <div class="apexcharts-tooltip-y-group">'
                    + '      <span class="apexcharts-tooltip-text-label">Redis Cache Pro</span>'
                    + '    </div>'
                    + '  </div>'
                    + '</div>';
            },
        },
    },

};
