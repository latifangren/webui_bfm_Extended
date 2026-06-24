<?php
// Fungsi untuk mendapatkan penggunaan CPU
function getCpuUsage() {
    $stats1 = file('/proc/stat');
    $cpuLine1 = $stats1[0]; 
    $values1 = array_map('intval', preg_split('/\s+/', trim($cpuLine1)));
    list($cpu, $user1, $nice1, $system1, $idle1) = array_slice($values1, 0, 5);

    usleep(500000); // Waktu pengukuran 0.5 detik

    $stats2 = file('/proc/stat');
    $cpuLine2 = $stats2[0];
    $values2 = array_map('intval', preg_split('/\s+/', trim($cpuLine2)));
    list($cpu, $user2, $nice2, $system2, $idle2) = array_slice($values2, 0, 5);

    $total1 = $user1 + $nice1 + $system1 + $idle1;
    $total2 = $user2 + $nice2 + $system2 + $idle2;
    
    if ($total2 === $total1) {
        return 0;
    }
    
    $idleDiff = $idle2 - $idle1;
    $totalDiff = $total2 - $total1;
    return ($totalDiff - $idleDiff) / $totalDiff * 100;
}

// Fungsi untuk mendapatkan temperatur CPU
function get_cpu_temperature() {
    $thermal_base = '/sys/class/thermal/';
    $max_temp = null;
    $cpu_temp = null;

    foreach (glob($thermal_base . 'thermal_zone*') as $zone) {
        $type_file = $zone . '/type';
        $temp_file = $zone . '/temp';
        if (file_exists($type_file) && file_exists($temp_file)) {
            $type = strtolower(trim(file_get_contents($type_file)));
            $temp = intval(trim(file_get_contents($temp_file)));
            if ($temp > 1000) $temp = $temp / 1000; // konversi ke °C jika perlu
            if (strpos($type, 'cpu') !== false || strpos($type, 'soc') !== false || strpos($type, 'core') !== false) {
                $cpu_temp = $temp;
                break;
            }
            if ($max_temp === null || $temp > $max_temp) {
                $max_temp = $temp;
            }
        }
    }
    if ($cpu_temp !== null) return round($cpu_temp, 1);
    if ($max_temp !== null) return round($max_temp, 1);
    return 'N/A';
}

// Fungsi untuk mendapatkan frekuensi CPU
function cpuFrequencies() {
    $num_cores = intval(trim(shell_exec('grep -c "^processor" /proc/cpuinfo')));
    $frequencies = [];
    
    for ($i = 0; $i < $num_cores; $i++) {
        $governor_file = "/sys/devices/system/cpu/cpu$i/cpufreq/scaling_governor";
        $min_freq_file = "/sys/devices/system/cpu/cpu$i/cpufreq/scaling_min_freq";
        $max_freq_file = "/sys/devices/system/cpu/cpu$i/cpufreq/scaling_max_freq";
        $cur_freq_file = "/sys/devices/system/cpu/cpu$i/cpufreq/scaling_cur_freq";
        $online_file = "/sys/devices/system/cpu/cpu$i/online";
        
        $core_info = [
            'core' => $i,
            'governor' => 'unknown',
            'min_freq' => 0,
            'max_freq' => 0,
            'current_freq' => 0,
            'online' => true
        ];
        
        if (file_exists($governor_file)) {
            $core_info['governor'] = trim(file_get_contents($governor_file));
        }
        
        if (file_exists($min_freq_file)) {
            $core_info['min_freq'] = intval(trim(file_get_contents($min_freq_file))) / 1000;
        }
        
        if (file_exists($max_freq_file)) {
            $core_info['max_freq'] = intval(trim(file_get_contents($max_freq_file))) / 1000;
        }
        
        if (file_exists($cur_freq_file)) {
            $core_info['current_freq'] = intval(trim(file_get_contents($cur_freq_file))) / 1000;
        }
        
        // Periksa status online berdasarkan file online dan frekuensi saat ini
        if ($i == 0) {
            // Core 0 selalu online
            $core_info['online'] = true;
        } else if (file_exists($online_file)) {
            $online_status = intval(trim(file_get_contents($online_file)));
            $core_info['online'] = ($online_status == 1 || $core_info['current_freq'] > 0);
        } else {
            // Jika file online tidak ada, periksa berdasarkan frekuensi
            $core_info['online'] = ($core_info['current_freq'] > 0);
        }
        
        $frequencies[] = $core_info;
    }
    
    return $frequencies;
}

// Fungsi untuk mendapatkan informasi CPU
function cpu() {
    $cpu_info = [];
    $model = trim(shell_exec("cat /proc/cpuinfo | grep 'model name' | head -1 | cut -d ':' -f2"));
    if (empty($model)) {
        $model = trim(shell_exec("cat /proc/cpuinfo | grep 'Processor' | head -1 | cut -d ':' -f2"));
    }
    $cpu_info['model'] = $model;
    $cpu_info['cores'] = intval(trim(shell_exec('grep -c "^processor" /proc/cpuinfo')));
    $cpu_info['architecture'] = trim(shell_exec("uname -m"));
    
    return $cpu_info;
}

// Handler untuk request AJAX
if (isset($_GET['action']) && $_GET['action'] === 'get_data') {
    header('Content-Type: application/json');
    $cpu_frequencies = cpuFrequencies();
    $avg_freq = 0;
    $online_cores = 0;
    foreach ($cpu_frequencies as $core) {
        if ($core['online']) {
            $avg_freq += $core['current_freq'];
            $online_cores++;
        }
    }
    $cpu_avg_freq = $online_cores > 0 ? round($avg_freq / $online_cores, 1) : 0;
    $load = function_exists('sys_getloadavg') ? sys_getloadavg() : [0,0,0];
    echo json_encode([
        'cpu_usage' => round(getCpuUsage(), 1),
        'cpu_temperature' => get_cpu_temperature(),
        'cpu_frequencies' => $cpu_frequencies,
        'cpu_avg_freq' => $cpu_avg_freq,
        'cpu_info' => cpu(),
        'cpu_load' => $load
    ]);
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor CPU Realtime</title>
    <style>
        @font-face {
            font-family: 'Roboto';
            font-style: normal;
            font-weight: 400;
            src: url('../fonts/Roboto-Regular.woff2') format('woff2'),
                 url('../fonts/Roboto-Regular.woff') format('woff');
        }

        @font-face {
            font-family: 'Roboto';
            font-style: normal;
            font-weight: 500;
            src: url('../fonts/Roboto-Medium.woff2') format('woff2'),
                 url('../fonts/Roboto-Medium.woff') format('woff');
        }

        @font-face {
            font-family: 'Roboto';
            font-style: normal;
            font-weight: 700;
            src: url('../fonts/Roboto-Bold.woff2') format('woff2'),
                 url('../fonts/Roboto-Bold.woff') format('woff');
        }
    </style>
    <script src="../js/iconify.min.js"></script>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #000000;
            margin: 0;
            padding: 12px;
            color: #F1F1F1;
            font-size: 14px;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 15px;
        }

        .dashboard-header h1 {
            margin: 0;
            font-size: 20px;
            color: #FECA0A;
        }

        .container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 12px;
            max-width: 1200px;
            margin: 0 auto;
            background: #111111;
            border-radius: 10px;
        }

        .chart-card {
            background: #181818;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            border: 1px solid #222;
        }

        .section-title {
            font-size: 15px;
            font-weight: 600;
            color: #fff;
            padding-bottom: 8px;
            border-bottom: 1px solid #333;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .core-card {
            background: #222222;
            border-radius: 6px;
            margin-bottom: 8px;
            border: 1px solid #333333;
        }

        .core-card .card-body {
            padding: 10px;
            color: #F1F1F1;
        }

        .core-card h5 {
            color: #FECA0A;
            font-weight: 600;
            margin: 0 0 6px 0;
            font-size: 0.95em;
        }

        .frequency-bar {
            height: 6px;
            background-color: #333333;
            border-radius: 3px;
            margin: 6px 0;
            overflow: hidden;
        }

        .frequency-bar .progress {
            height: 100%;
            background: linear-gradient(90deg, #FECA0A, #FEDC6A);
            transition: width 0.3s ease;
        }

        .frequency-label {
            display: flex;
            justify-content: space-between;
            color: #fff;
            font-size: 0.85em;
        }

        .status-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            color: #fff;
        }

        .status-online {
            background-color: rgba(254, 202, 10, 0.2);
            color: #fff;
        }

        .status-offline {
            background-color: rgba(255, 107, 107, 0.1);
            color: #fff;
        }

        .governor-badge {
            background-color: #333;
            color: #fff;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
        }

        .cluster-header {
            color: #fff;
            font-size: 0.9em;
            font-weight: 600;
            margin: 12px 0 8px 0;
            padding: 6px;
            background: #181818;
            border-radius: 4px;
        }

        .cores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 8px;
        }

        /* Footer Styles */
        .footer {
            margin-top: 20px;
            padding: 15px 0;
            background: linear-gradient(45deg, #000000, #222222);
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .footer-left, .footer-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #F1F1F1;
        }

        .footer-icon {
            color: #FECA0A;
            font-size: 18px;
            animation: pulse 1.5s infinite;
        }

        .footer-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            color: #000000;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: #FECA0A;
        }

        .telegram-button {
            background-color: #FECA0A;
        }

        .dashboard-button {
            background-color: #181818;
            color: #F1F1F1;
        }

        .footer-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(254, 202, 10, 0.3);
        }

        .purple-text {
            color: #FECA0A;
        }

        .cyan-text {
            color: #F1F1F1;
        }

        .grey-text {
            color: #999999;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        @media (max-width: 768px) {
            body {
                padding: 8px;
            }
            
            .container {
                grid-template-columns: 1fr;
            }
            
            .cores-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 480px) {
            .cores-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .footer-content {
                flex-direction: column;
                text-align: center;
            }

            .footer-left, .footer-right {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <h1>Monitor CPU Realtime</h1>
    </div>

    <div class="container">
        <div class="chart-card">
            <div class="section-title">
                <i class="iconify" data-icon="mdi:cpu-64-bit"></i> Informasi CPU
            </div>
            <div id="cpuInfo"></div>
            <div style="margin-bottom:10px;">
                <label for="historyLength">History: </label>
                <select id="historyLength">
                    <option value="30">30 detik</option>
                    <option value="60" selected>60 detik</option>
                    <option value="120">120 detik</option>
                </select>
            </div>
            <canvas id="cpuChart" height="80"></canvas>
            <div id="cpuLoad" style="margin-top:10px;"></div>
            <div class="cluster-header">Performance Cores (BIG)</div>
            <div id="bigCores" class="cores-grid"></div>
            <div class="cluster-header">Efficiency Cores (LITTLE)</div>
            <div id="littleCores" class="cores-grid"></div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-left">
                    <span class="footer-brand">
                        <span class="purple-text">© 2025</span>
                        <span class="cyan-text">CPU Monitor</span>
                        <i class="iconify footer-icon pulse" data-icon="mdi:heart"></i> 
                        <span class="grey-text">by</span>
                    </span>
                    <a href="https://t.me/latifan_id" class="footer-button telegram-button" target="_blank">
                        <i class="iconify" data-icon="mdi:telegram"></i>
                        @latifan_id
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function formatFrequency(freq) {
            if (freq >= 1000) {
                return (freq / 1000).toFixed(2) + ' GHz';
            }
            return freq.toFixed(0) + ' MHz';
        }

        // Chart.js setup
        let cpuUsageHistory = [];
        let cpuTempHistory = [];
        let cpuFreqHistory = [];
        let chartLabels = [];
        let maxHistory = 60;
        const historySelect = document.getElementById('historyLength');
        historySelect.addEventListener('change', function() {
            maxHistory = parseInt(this.value);
            // Potong data jika lebih panjang dari maxHistory
            cpuUsageHistory = cpuUsageHistory.slice(-maxHistory);
            cpuTempHistory = cpuTempHistory.slice(-maxHistory);
            cpuFreqHistory = cpuFreqHistory.slice(-maxHistory);
            chartLabels = chartLabels.slice(-maxHistory);
            cpuChart.update();
        });

        const ctx = document.getElementById('cpuChart').getContext('2d');
        const cpuChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'CPU Usage (%)',
                        data: cpuUsageHistory,
                        borderColor: '#FECA0A',
                        backgroundColor: 'rgba(254,202,10,0.1)',
                        yAxisID: 'y',
                        tension: 0.2,
                    },
                    {
                        label: 'Suhu CPU (°C)',
                        data: cpuTempHistory,
                        borderColor: '#FF6B6B',
                        backgroundColor: 'rgba(255,107,107,0.1)',
                        yAxisID: 'y1',
                        tension: 0.2,
                    },
                    {
                        label: 'Frekuensi Rata-rata (MHz)',
                        data: cpuFreqHistory,
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59,130,246,0.1)',
                        yAxisID: 'y2',
                        tension: 0.2,
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true }
                },
                scales: {
                    y: {
                        type: 'linear',
                        position: 'left',
                        title: { display: true, text: 'CPU Usage (%)' },
                        min: 0, max: 100
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        title: { display: true, text: 'Suhu (°C)' },
                        min: 0, max: 100,
                        grid: { drawOnChartArea: false }
                    },
                    y2: {
                        type: 'linear',
                        position: 'right',
                        title: { display: true, text: 'Frekuensi (MHz)' },
                        min: 0,
                        grid: { drawOnChartArea: false },
                        offset: true
                    }
                }
            }
        });

        function updateData() {
            fetch('CpuMonitor.php?action=get_data')
                .then(response => response.json())
                .then(data => {
                    const cpuInfo = document.getElementById('cpuInfo');
                    cpuInfo.innerHTML = `
                        <div class="core-card">
                            <div class="card-body">
                                <h5>${data.cpu_info.model}</h5>
                                <div class="frequency-label">
                                    <span>${data.cpu_info.architecture} | ${data.cpu_info.cores} Cores</span>
                                    <span>${data.cpu_usage}% | ${data.cpu_temperature}°C | ${formatFrequency(data.cpu_avg_freq)}</span>
                                </div>
                            </div>
                        </div>
                    `;

                    // Tampilkan load average
                    const cpuLoad = document.getElementById('cpuLoad');
                    cpuLoad.innerHTML = `<b>Load Average:</b> ${data.cpu_load[0].toFixed(2)}, ${data.cpu_load[1].toFixed(2)}, ${data.cpu_load[2].toFixed(2)}`;

                    // Update grafik history
                    const now = new Date();
                    const label = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0') + ':' + now.getSeconds().toString().padStart(2, '0');
                    cpuUsageHistory.push(data.cpu_usage);
                    cpuTempHistory.push(parseFloat(data.cpu_temperature));
                    cpuFreqHistory.push(data.cpu_avg_freq);
                    chartLabels.push(label);
                    if (cpuUsageHistory.length > maxHistory) {
                        cpuUsageHistory.shift();
                        cpuTempHistory.shift();
                        cpuFreqHistory.shift();
                        chartLabels.shift();
                    }
                    cpuChart.data.labels = chartLabels;
                    cpuChart.data.datasets[0].data = cpuUsageHistory;
                    cpuChart.data.datasets[1].data = cpuTempHistory;
                    cpuChart.data.datasets[2].data = cpuFreqHistory;
                    cpuChart.update();

                    // Core info
                    const bigCores = document.getElementById('bigCores');
                    const littleCores = document.getElementById('littleCores');
                    bigCores.innerHTML = '';
                    littleCores.innerHTML = '';
                    data.cpu_frequencies.forEach((core, index) => {
                        const coreEl = document.createElement('div');
                        coreEl.innerHTML = `
                            <div class="core-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5>Core ${core.core}</h5>
                                        <span class="status-badge ${core.online ? 'status-online' : 'status-offline'}">
                                            ${core.online ? 'Online' : 'Offline'}
                                        </span>
                                    </div>
                                    <div class="frequency-label">
                                        <span>${formatFrequency(core.current_freq)}</span>
                                        <span class="governor-badge">${core.governor}</span>
                                    </div>
                                    <div class="frequency-bar">
                                        <div class="progress" style="width: ${(core.current_freq / core.max_freq * 100)}%"></div>
                                    </div>
                                    <div class="frequency-label">
                                        <small>${formatFrequency(core.min_freq)}</small>
                                        <small>${formatFrequency(core.max_freq)}</small>
                                    </div>
                                </div>
                            </div>
                        `;
                        if (index < 4) {
                            bigCores.appendChild(coreEl);
                        } else {
                            littleCores.appendChild(coreEl);
                        }
                    });
                });
        }

        setInterval(updateData, 1000);
        updateData();
    </script>
</body>
</html> 