/**
 * Happy Turtle Security Dashboard Admin Scripts
 *
 * @package HappyTurtle_FSE
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initActivityChart();
        initTimeRangeSelector();
    });

    /**
     * Initialize activity chart
     */
    function initActivityChart() {
        var canvas = document.getElementById('htb-activity-chart');

        if (!canvas || typeof hourlyData === 'undefined' || typeof Chart === 'undefined') {
            return;
        }

        var ctx = canvas.getContext('2d');

        new Chart(ctx, {
            type: 'line',
            data: hourlyData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: {
                                size: 12,
                                family: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(30, 41, 59, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#e2e8f0',
                        borderColor: '#2D6A4F',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + ' requests';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 11
                            },
                            color: '#64748b'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 11
                            },
                            color: '#64748b',
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    /**
     * Initialize time range selector
     */
    function initTimeRangeSelector() {
        $('#htb-time-range').on('change', function() {
            var range = $(this).val();
            var url = window.location.href;

            // Update URL with new time range
            url = updateQueryString(url, 'range', range);

            // Reload page with new range
            window.location.href = url;
        });
    }

    /**
     * Update query string parameter
     */
    function updateQueryString(url, key, value) {
        var re = new RegExp('([?&])' + key + '=.*?(&|$)', 'i');
        var separator = url.indexOf('?') !== -1 ? '&' : '?';

        if (url.match(re)) {
            return url.replace(re, '$1' + key + '=' + value + '$2');
        } else {
            return url + separator + key + '=' + value;
        }
    }

})(jQuery);
