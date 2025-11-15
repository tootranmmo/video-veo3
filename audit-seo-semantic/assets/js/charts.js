/**
 * SEO Analytics Charts with Chart.js
 */
(function($) {
    'use strict';

    var AuditCharts = {

        /**
         * Initialize charts
         */
        init: function() {
            this.initScoreChart();
            this.initBacklinkChart();
            this.initContentTrendChart();
            this.initIssuesChart();
        },

        /**
         * Score History Chart
         */
        initScoreChart: function() {
            var ctx = document.getElementById('scoreHistoryChart');
            if (!ctx) return;

            var data = JSON.parse(ctx.dataset.chartData || '{}');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels || [],
                    datasets: [{
                        label: 'Technical SEO Score',
                        data: data.technical || [],
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Content SEO Score',
                        data: data.content || [],
                        borderColor: '#4caf50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'SEO Score Over Time'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        },

        /**
         * Backlink Distribution Chart
         */
        initBacklinkChart: function() {
            var ctx = document.getElementById('backlinkChart');
            if (!ctx) return;

            var data = JSON.parse(ctx.dataset.chartData || '{}');

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['DoFollow', 'NoFollow', 'Toxic', 'Healthy'],
                    datasets: [{
                        data: [
                            data.dofollow || 0,
                            data.nofollow || 0,
                            data.toxic || 0,
                            data.healthy || 0
                        ],
                        backgroundColor: [
                            '#4caf50',
                            '#ff9800',
                            '#f44336',
                            '#2196f3'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Backlink Distribution'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        },

        /**
         * Content Metrics Trend Chart
         */
        initContentTrendChart: function() {
            var ctx = document.getElementById('contentTrendChart');
            if (!ctx) return;

            var data = JSON.parse(ctx.dataset.chartData || '{}');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels || [],
                    datasets: [{
                        label: 'Word Count',
                        data: data.word_count || [],
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        yAxisID: 'y'
                    }, {
                        label: 'Readability Score',
                        data: data.readability || [],
                        backgroundColor: 'rgba(255, 159, 64, 0.8)',
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Content Metrics Trend'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Word Count'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Readability Score'
                            },
                            grid: {
                                drawOnChartArea: false
                            },
                            max: 100
                        }
                    }
                }
            });
        },

        /**
         * Issues by Type Chart
         */
        initIssuesChart: function() {
            var ctx = document.getElementById('issuesChart');
            if (!ctx) return;

            var data = JSON.parse(ctx.dataset.chartData || '{}');

            new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: [
                        'Meta Tags',
                        'Headings',
                        'URLs',
                        'Images',
                        'Content',
                        'Links'
                    ],
                    datasets: [{
                        label: 'Current Score',
                        data: data.current || [0, 0, 0, 0, 0, 0],
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.2)'
                    }, {
                        label: 'Average Score',
                        data: [80, 75, 85, 70, 65, 75],
                        borderColor: '#4caf50',
                        backgroundColor: 'rgba(76, 175, 80, 0.2)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'SEO Areas Performance'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        },

        /**
         * Backlink Velocity Chart
         */
        createBacklinkVelocityChart: function(canvasId, data) {
            var ctx = document.getElementById(canvasId);
            if (!ctx) return;

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.dates || [],
                    datasets: [{
                        label: 'New Backlinks',
                        data: data.counts || [],
                        borderColor: '#9c27b0',
                        backgroundColor: 'rgba(156, 39, 176, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Backlink Acquisition Trend (Last 30 Days)'
                        },
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        },

        /**
         * Domain Authority Distribution
         */
        createDADistributionChart: function(canvasId, data) {
            var ctx = document.getElementById(canvasId);
            if (!ctx) return;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['0-20', '21-40', '41-60', '61-80', '81-100'],
                    datasets: [{
                        label: 'Number of Backlinks',
                        data: data.distribution || [0, 0, 0, 0, 0],
                        backgroundColor: [
                            '#f44336',
                            '#ff9800',
                            '#ffeb3b',
                            '#8bc34a',
                            '#4caf50'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Backlinks by Domain Authority'
                        },
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        },

        /**
         * Keyword Density Chart
         */
        createKeywordDensityChart: function(canvasId, keywords) {
            var ctx = document.getElementById(canvasId);
            if (!ctx) return;

            var labels = keywords.map(k => k.keyword);
            var data = keywords.map(k => k.density);

            new Chart(ctx, {
                type: 'horizontalBar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Density %',
                        data: data,
                        backgroundColor: 'rgba(54, 162, 235, 0.8)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Top Keywords Density'
                        },
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            max: 5,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        },

        /**
         * Comparison Chart (Multiple Posts)
         */
        createComparisonChart: function(canvasId, posts) {
            var ctx = document.getElementById(canvasId);
            if (!ctx) return;

            var labels = posts.map(p => p.title);
            var scores = posts.map(p => p.score);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'SEO Score',
                        data: scores,
                        backgroundColor: scores.map(score => {
                            if (score >= 80) return 'rgba(76, 175, 80, 0.8)';
                            if (score >= 60) return 'rgba(255, 152, 0, 0.8)';
                            return 'rgba(244, 67, 54, 0.8)';
                        })
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Posts SEO Score Comparison'
                        },
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        // Load Chart.js from CDN if not already loaded
        if (typeof Chart === 'undefined') {
            $('<script>')
                .attr('src', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js')
                .on('load', function() {
                    AuditCharts.init();
                })
                .appendTo('head');
        } else {
            AuditCharts.init();
        }
    });

    // Expose to global scope
    window.AuditCharts = AuditCharts;

})(jQuery);
