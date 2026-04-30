<!DOCTYPE html>
<html dir="rtl" lang="he">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>דשבורד הקלטות שיחות - <?php echo Config::get('site.main.title'); ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Chart.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Styles -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Heebo', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .card-shadow {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .card-shadow:hover {
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .animate-pulse-slow {
            animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <nav class="gradient-bg text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4 space-x-reverse">
                    <i class="fas fa-phone-volume text-3xl"></i>
                    <h1 class="text-2xl font-bold"><?php echo Config::get('site.main.head'); ?></h1>
                </div>
                <div class="flex items-center space-x-4 space-x-reverse">
                    <a href="index.php" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition">
                        <i class="fas fa-search ml-2"></i>
                        חיפוש מתקדם
                    </a>
                    <button onclick="refreshData()" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition">
                        <i class="fas fa-sync-alt ml-2"></i>
                        רענן נתונים
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Dashboard -->
    <div class="container mx-auto px-4 py-8">
        <!-- Date Range Selector -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center space-x-4 space-x-reverse">
                    <span class="font-semibold">טווח תאריכים:</span>
                    <select id="dateRange" class="border rounded-lg px-4 py-2" onchange="changeDateRange(this.value)">
                        <option value="today" <?php echo $dateRange == 'today' ? 'selected' : ''; ?>>היום</option>
                        <option value="week" <?php echo $dateRange == 'week' ? 'selected' : ''; ?>>השבוע</option>
                        <option value="month" <?php echo $dateRange == 'month' ? 'selected' : ''; ?>>החודש</option>
                        <option value="3months" <?php echo $dateRange == '3months' ? 'selected' : ''; ?>>3 חודשים אחרונים</option>
                        <option value="year" <?php echo $dateRange == 'year' ? 'selected' : ''; ?>>שנה</option>
                    </select>
                </div>
                <div class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
                    <i class="fas fa-info-circle"></i>
                    <span>עדכון אחרון: <span id="lastUpdate">עכשיו</span></span>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Calls -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <i class="fas fa-phone text-blue-600 text-xl"></i>
                    </div>
                    <span class="text-<?php echo $callsChange >= 0 ? 'green' : 'red'; ?>-500 text-sm font-semibold">
                        <i class="fas fa-arrow-<?php echo $callsChange >= 0 ? 'up' : 'down'; ?>"></i> 
                        <?php echo abs($callsChange); ?>%
                    </span>
                </div>
                <h3 class="text-gray-600 text-sm mb-1">סה"כ שיחות</h3>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['total_calls']); ?></p>
                <p class="text-xs text-gray-500 mt-2">מתוכם <?php echo number_format($stats['answered_calls']); ?> נענו</p>
            </div>

            <!-- Average Duration -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-green-100 p-3 rounded-lg">
                        <i class="fas fa-clock text-green-600 text-xl"></i>
                    </div>
                    <span class="text-<?php echo $durationChange >= 0 ? 'green' : 'red'; ?>-500 text-sm font-semibold">
                        <i class="fas fa-arrow-<?php echo $durationChange >= 0 ? 'up' : 'down'; ?>"></i> 
                        <?php echo abs($durationChange); ?>%
                    </span>
                </div>
                <h3 class="text-gray-600 text-sm mb-1">משך שיחה ממוצע</h3>
                <p class="text-3xl font-bold text-gray-800">
                    <?php 
                    $avgDur = round($stats['avg_duration']);
                    echo sprintf('%d:%02d', floor($avgDur/60), $avgDur%60); 
                    ?>
                </p>
                <p class="text-xs text-gray-500 mt-2">דקות:שניות</p>
            </div>

            <!-- Answer Rate -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-purple-100 p-3 rounded-lg">
                        <i class="fas fa-percentage text-purple-600 text-xl"></i>
                    </div>
                    <span class="text-<?php echo $answerRateChange >= 0 ? 'green' : 'red'; ?>-500 text-sm font-semibold">
                        <i class="fas fa-arrow-<?php echo $answerRateChange >= 0 ? 'up' : 'down'; ?>"></i> 
                        <?php echo abs($answerRateChange); ?>%
                    </span>
                </div>
                <h3 class="text-gray-600 text-sm mb-1">אחוז מענה</h3>
                <p class="text-3xl font-bold text-gray-800"><?php echo $answerRate; ?>%</p>
                <p class="text-xs text-gray-500 mt-2">יעד: 85%</p>
            </div>

            <!-- Active Calls -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-red-100 p-3 rounded-lg">
                        <i class="fas fa-broadcast-tower text-red-600 text-xl animate-pulse-slow"></i>
                    </div>
                    <span class="text-yellow-500 text-sm font-semibold">
                        <i class="fas fa-circle text-xs"></i> פעיל
                    </span>
                </div>
                <h3 class="text-gray-600 text-sm mb-1">שיחות פעילות</h3>
                <p class="text-3xl font-bold text-gray-800" id="activeCalls"><?php echo $activeCalls; ?></p>
                <p class="text-xs text-gray-500 mt-2">מקסימום היום: <?php echo $maxConcurrent; ?></p>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Calls by Hour Chart -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <h3 class="text-lg font-semibold mb-4">התפלגות שיחות לפי שעה</h3>
                <div style="position:relative;height:260px"><canvas id="callsByHourChart"></canvas></div>
            </div>

            <!-- Call Status Distribution -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <h3 class="text-lg font-semibold mb-4">התפלגות סטטוס שיחות</h3>
                <div style="position:relative;height:260px"><canvas id="callStatusChart"></canvas></div>
            </div>
        </div>

        <!-- Heatmap and Recent Calls -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Weekly Heatmap -->
            <div class="lg:col-span-2 bg-white rounded-xl card-shadow p-6">
                <h3 class="text-lg font-semibold mb-4">מפת חום - עומס שיחות</h3>
                <div class="overflow-x-auto">
                    <div class="grid grid-cols-8 gap-1 min-w-[600px]">
                        <!-- Header -->
                        <div></div>
                        <div class="text-center text-xs font-semibold p-2">ראשון</div>
                        <div class="text-center text-xs font-semibold p-2">שני</div>
                        <div class="text-center text-xs font-semibold p-2">שלישי</div>
                        <div class="text-center text-xs font-semibold p-2">רביעי</div>
                        <div class="text-center text-xs font-semibold p-2">חמישי</div>
                        <div class="text-center text-xs font-semibold p-2">שישי</div>
                        <div class="text-center text-xs font-semibold p-2">שבת</div>
                        
                        <?php
                        // יצירת מפת חום
                        $heatmapArray = [];
                        foreach ($heatmapData as $cell) {
                            $heatmapArray[$cell['hour']][$cell['day_of_week']] = $cell['call_count'];
                        }
                        
                        for ($hour = 0; $hour < 24; $hour += 2) {
                            echo '<div class="text-xs font-semibold p-2 text-left">' . sprintf('%02d:00', $hour) . '</div>';
                            
                            for ($day = 0; $day < 7; $day++) {
                                $value = isset($heatmapArray[$hour][$day]) ? $heatmapArray[$hour][$day] : 0;
                                $intensity = $maxHeatmapValue > 0 ? $value / $maxHeatmapValue : 0;
                                
                                $bgColor = 'bg-gray-100';
                                if ($intensity > 0.8) $bgColor = 'bg-red-500';
                                elseif ($intensity > 0.6) $bgColor = 'bg-orange-400';
                                elseif ($intensity > 0.4) $bgColor = 'bg-yellow-300';
                                elseif ($intensity > 0.2) $bgColor = 'bg-green-300';
                                
                                echo '<div class="heatmap-cell ' . $bgColor . ' rounded cursor-pointer p-3 relative group">';
                                echo '<div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 bg-black/50 rounded text-white text-xs font-semibold transition-opacity">';
                                echo $value;
                                echo '</div></div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Recent Calls -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">שיחות אחרונות</h3>
                    <a href="index.php" class="text-blue-600 hover:text-blue-800 text-sm">
                        הצג הכל <i class="fas fa-arrow-left mr-1"></i>
                    </a>
                </div>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php foreach ($recentCalls as $call): ?>
                    <div class="border rounded-lg p-3 hover:bg-gray-50 transition">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex items-center">
                                <div class="bg-<?php echo $call['disposition'] == 'ANSWERED' ? 'green' : 'red'; ?>-100 p-2 rounded-full ml-3">
                                    <i class="fas fa-phone<?php echo $call['disposition'] != 'ANSWERED' ? '-slash' : '-alt'; ?> text-<?php echo $call['disposition'] == 'ANSWERED' ? 'green' : 'red'; ?>-600 text-xs"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-sm"><?php echo htmlspecialchars($call['src']); ?></p>
                                    <p class="text-xs text-gray-500">
                                        <?php echo $call['direction'] == 'incoming' ? 'נכנסת' : 'יוצאת'; ?> 
                                        → <?php echo htmlspecialchars($call['dst']); ?>
                                    </p>
                                </div>
                            </div>
                            <span class="text-xs text-gray-500">
                                <?php 
                                $timeDiff = time() - strtotime($call['calldate']);
                                if ($timeDiff < 60) echo 'עכשיו';
                                elseif ($timeDiff < 3600) echo 'לפני ' . floor($timeDiff/60) . ' דק\'';
                                else echo 'לפני ' . floor($timeDiff/3600) . ' שעות';
                                ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs bg-<?php 
                                switch($call['disposition']) {
                                    case 'ANSWERED': echo 'green'; break;
                                    case 'NO ANSWER': echo 'red'; break;
                                    case 'BUSY': echo 'yellow'; break;
                                    default: echo 'gray';
                                }
                            ?>-100 text-<?php 
                                switch($call['disposition']) {
                                    case 'ANSWERED': echo 'green'; break;
                                    case 'NO ANSWER': echo 'red'; break;
                                    case 'BUSY': echo 'yellow'; break;
                                    default: echo 'gray';
                                }
                            ?>-800 px-2 py-1 rounded">
                                <?php 
                                switch($call['disposition']) {
                                    case 'ANSWERED': echo 'נענה'; break;
                                    case 'NO ANSWER': echo 'לא נענה'; break;
                                    case 'BUSY': echo 'תפוס'; break;
                                    case 'FAILED': echo 'נכשל'; break;
                                    default: echo $call['disposition'];
                                }
                                ?>
                            </span>
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <span class="text-xs text-gray-600">
                                    <?php echo sprintf('%d:%02d', floor($call['billsec']/60), $call['billsec']%60); ?>
                                </span>
                                <?php if ($call['recordingfile'] && $call['disposition'] == 'ANSWERED'): ?>
                                <button onclick="playRecording('<?php echo base64_encode($call['recordingfile']); ?>', '<?php echo htmlspecialchars($call['src']); ?>', '<?php echo htmlspecialchars($call['dst']); ?>')" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-play-circle text-lg"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Average Wait Time -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <h3 class="text-lg font-semibold mb-4">זמן המתנה ממוצע</h3>
                <div style="position:relative;height:180px">
                    <canvas id="waitTimeGauge"></canvas>
                </div>
                <div class="text-center mt-4">
                    <p class="text-3xl font-bold text-gray-800"><?php echo sprintf('%02d:%02d', floor($avgWaitTime/60), $avgWaitTime%60); ?></p>
                    <p class="text-sm text-gray-600">דקות:שניות</p>
                </div>
            </div>

            <!-- Service Level -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <h3 class="text-lg font-semibold mb-4">רמת שירות (SLA)</h3>
                <div style="position:relative;height:180px">
                    <canvas id="slaGauge"></canvas>
                </div>
                <div class="text-center mt-4">
                    <p class="text-3xl font-bold text-gray-800"><?php echo $slaPercentage; ?>%</p>
                    <p class="text-sm text-gray-600">מענה תוך 20 שניות</p>
                </div>
            </div>

            <!-- Call Duration Distribution -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <h3 class="text-lg font-semibold mb-4">התפלגות משך שיחות</h3>
                <div style="position:relative;height:180px"><canvas id="durationChart"></canvas></div>
            </div>
        </div>
    </div>

    <script>
        // Chart.js Configuration
        Chart.defaults.font.family = "'Heebo', sans-serif";
        
        // PHP Data to JavaScript
        const hourlyData = <?php echo json_encode($hourlyData); ?>;
        const stats = <?php echo json_encode($stats); ?>;
        const durationDist = <?php echo json_encode($durationDist); ?>;
        
        // Prepare hourly chart data
        const hours = Array.from({length: 24}, (_, i) => i);
        const incomingData = new Array(24).fill(0);
        const outgoingData = new Array(24).fill(0);
        
        hourlyData.forEach(item => {
            incomingData[item.hour] = parseInt(item.incoming_calls);
            outgoingData[item.hour] = parseInt(item.outgoing_calls);
        });
        
        // Calls by Hour Chart
        const callsByHourCtx = document.getElementById('callsByHourChart').getContext('2d');
        const callsByHourChart = new Chart(callsByHourCtx, {
            type: 'line',
            data: {
                labels: hours.map(h => `${h}:00`),
                datasets: [{
                    label: 'שיחות נכנסות',
                    data: incomingData,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'שיחות יוצאות',
                    data: outgoingData,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: true,
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [5, 5]
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Call Status Chart
        const callStatusCtx = document.getElementById('callStatusChart').getContext('2d');
        const callStatusChart = new Chart(callStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['נענה', 'לא נענה', 'תפוס', 'נכשל'],
                datasets: [{
                    data: [
                        parseInt(stats.answered_calls),
                        parseInt(stats.missed_calls),
                        parseInt(stats.busy_calls),
                        parseInt(stats.failed_calls)
                    ],
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(156, 163, 175, 0.8)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: true,
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                }
            }
        });

        // Wait Time Gauge
        const waitTimeCtx = document.getElementById('waitTimeGauge').getContext('2d');
        const waitTimeGauge = new Chart(waitTimeCtx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [<?php echo $avgWaitTime; ?>, <?php echo max(0, 60 - $avgWaitTime); ?>],
                    backgroundColor: [
                        <?php echo $avgWaitTime <= 20 ? "'rgba(34, 197, 94, 0.8)'" : ($avgWaitTime <= 40 ? "'rgba(251, 191, 36, 0.8)'" : "'rgba(239, 68, 68, 0.8)'"); ?>,
                        'rgba(229, 231, 235, 0.3)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                circumference: 180,
                rotation: 270,
                cutout: '75%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
                    }
                }
            }
        });

        // SLA Gauge
        const slaCtx = document.getElementById('slaGauge').getContext('2d');
        const slaGauge = new Chart(slaCtx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [<?php echo $slaPercentage; ?>, <?php echo 100 - $slaPercentage; ?>],
                    backgroundColor: [
                        <?php echo $slaPercentage >= 85 ? "'rgba(34, 197, 94, 0.8)'" : ($slaPercentage >= 70 ? "'rgba(251, 191, 36, 0.8)'" : "'rgba(239, 68, 68, 0.8)'"); ?>,
                        'rgba(229, 231, 235, 0.3)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                circumference: 180,
                rotation: 270,
                cutout: '75%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
                    }
                }
            }
        });

        // Duration Distribution Chart
        const durationLabels = [];
        const durationData = [];
        const durationRangeOrder = ['0-1', '1-2', '2-3', '3-5', '5-10', '10+'];
        
        durationRangeOrder.forEach(range => {
            const found = durationDist.find(item => item.duration_range === range);
            durationLabels.push(range);
            durationData.push(found ? parseInt(found.count) : 0);
        });
        
        const durationCtx = document.getElementById('durationChart').getContext('2d');
        const durationChart = new Chart(durationCtx, {
            type: 'bar',
            data: {
                labels: durationLabels,
                datasets: [{
                    label: 'מספר שיחות',
                    data: durationData,
                    backgroundColor: 'rgba(139, 92, 246, 0.8)',
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [5, 5]
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('he-IL');
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'דקות'
                        }
                    }
                }
            }
        });

        // Functions
        function changeDateRange(range) {
            window.location.href = 'dashboard.php?range=' + range;
        }

        function refreshData() {
            location.reload();
        }

        function playRecording(uniqueid, src, dst) {
            var existing = document.getElementById('dashboard-player-box');
            if (existing) existing.remove();
            var box = document.createElement('div');
            box.id = 'dashboard-player-box';
            box.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:12px 20px;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.4);z-index:9999;display:flex;align-items:center;gap:12px;min-width:340px;direction:rtl';
            box.innerHTML = '<div style="font-size:.85em;min-width:100px">' + src + ' → ' + dst + '</div>' +
                '<audio controls autoplay style="flex:1;height:32px;direction:ltr"><source src="dl.php?f=' + fileEncoded + '"></audio>' +
                '<button onclick="document.getElementById(\'dashboard-player-box\').remove()" style="background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:6px;padding:4px 10px;cursor:pointer;font-size:1.1em">✕</button>';
            document.body.appendChild(box);
        }

        // Auto refresh every 30 seconds
        setInterval(function() {
            fetch('api/dashboard-live.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('activeCalls').textContent = data.activeCalls;
                    document.getElementById('lastUpdate').textContent = 'עכשיו';
                });
        }, 30000);

        // Update last refresh time
        function updateLastRefresh() {
            const lastUpdateEl = document.getElementById('lastUpdate');
            let seconds = 0;
            
            setInterval(() => {
                seconds += 30;
                if (seconds < 60) {
                    lastUpdateEl.textContent = 'לפני ' + seconds + ' שניות';
                } else if (seconds < 3600) {
                    const minutes = Math.floor(seconds / 60);
                    lastUpdateEl.textContent = 'לפני ' + minutes + ' דקות';
                } else {
                    const hours = Math.floor(seconds / 3600);
                    lastUpdateEl.textContent = 'לפני ' + hours + ' שעות';
                }
            }, 30000);
        }
        
        updateLastRefresh();
    </script>
</body>
</html>