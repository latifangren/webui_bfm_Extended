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
    $cpu_temp = shell_exec('cat /sys/class/thermal/thermal_zone4/temp');
    if ($cpu_temp) {
        return round($cpu_temp / 1000, 1);
    }
    
    $cpu_temp = shell_exec('cat /sys/devices/virtual/thermal/thermal_zone0/temp');
    if ($cpu_temp) {
        return round($cpu_temp / 1000, 1);
    }
    
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
    echo json_encode([
        'cpu_usage' => round(getCpuUsage(), 1),
        'cpu_temperature' => get_cpu_temperature(),
        'cpu_frequencies' => cpuFrequencies(),
        'cpu_info' => cpu()
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
            background-color: #E0E0E0;
            margin: 0;
            padding: 12px;
            color: #333333;
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
        }

        .chart-card {
            background: #FFFFFF;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 15px;
            font-weight: 600;
            color: #FECA0A;
            padding-bottom: 8px;
            border-bottom: 1px solid #DDDDDD;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .core-card {
            background: #F8F8F8;
            border-radius: 6px;
            margin-bottom: 8px;
            border: 1px solid #DDDDDD;
        }

        .core-card .card-body {
            padding: 10px;
        }

        .core-card h5 {
            color: #FECA0A;
            font-weight: 600;
            margin: 0 0 6px 0;
            font-size: 0.95em;
        }

        .frequency-bar {
            height: 6px;
            background-color: #DDDDDD;
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
            color: #333333;
            font-size: 0.85em;
        }

        .status-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
        }

        .status-online {
            background-color: rgba(254, 202, 10, 0.2);
            color: #FECA0A;
        }

        .status-offline {
            background-color: rgba(255, 107, 107, 0.1);
            color: #FF6B6B;
        }

        .governor-badge {
            background-color: #EEEEEE;
            color: #333333;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
        }

        .cluster-header {
            color: #FECA0A;
            font-size: 0.9em;
            font-weight: 600;
            margin: 12px 0 8px 0;
            padding: 6px;
            background: rgba(254, 202, 10, 0.1);
            border-radius: 4px;
        }

        .cores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 8px;
        }

        /* Dark Mode */
        body.dark-mode {
            background-color: #000000;
            color: #F1F1F1;
        }

        .dark-mode .dashboard-header h1 {
            color: #FECA0A;
        }

        .dark-mode .chart-card {
            background-color: #111111;
            border: 1px solid #333333;
        }

        .dark-mode .section-title {
            color: #FECA0A;
            border-bottom-color: #333333;
        }

        .dark-mode .core-card {
            background: #1a1a1a;
            border-color: #333333;
        }

        .dark-mode .frequency-bar {
            background-color: #333333;
        }

        .dark-mode .frequency-label {
            color: #F1F1F1;
        }

        .dark-mode .governor-badge {
            background-color: #333333;
            color: #F1F1F1;
        }

        /* Theme Switch - Compact Version */
        .theme-switch-wrapper {
            position: absolute;
            top: 12px;
            right: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .theme-switch {
            display: inline-block;
            height: 24px;
            width: 44px;
            position: relative;
        }

        .theme-switch input {
            display: none;
        }

        .slider {
            background-color: #BBBBBB;
            bottom: 0;
            cursor: pointer;
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
            transition: .3s;
            border-radius: 24px;
        }

        .slider:before {
            background-color: #FFFFFF;
            bottom: 3px;
            content: "";
            height: 18px;
            left: 3px;
            position: absolute;
            transition: .3s;
            width: 18px;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #FECA0A;
        }

        input:checked + .slider:before {
            transform: translateX(20px);
        }

        .theme-switch-wrapper span {
            font-size: 0.85em;
            color: #333333;
        }

        .dark-mode .theme-switch-wrapper span {
            color: #F1F1F1;
        }

        /* Footer Styles */
        .footer {
            margin-top: 20px;
            padding: 15px 0;
            background: linear-gradient(45deg, #CCCCCC, #E5E5E5);
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
            color: #333333;
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
        }

        .telegram-button {
            background-color: #FECA0A;
        }

        .dashboard-button {
            background-color: #FFFFFF;
        }

        .footer-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(254, 202, 10, 0.3);
        }

        .purple-text {
            color: #FECA0A;
        }

        .cyan-text {
            color: #333333;
        }

        .grey-text {
            color: #666666;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        /* Dark Mode Footer Adjustments */
        .dark-mode .footer {
            background: linear-gradient(45deg, #000000, #222222);
        }

        .dark-mode .footer-button {
            background-color: #FECA0A;
            color: #000000;
        }

        .dark-mode .footer-brand {
            color: #F1F1F1;
        }

        .dark-mode .cyan-text {
            color: #F1F1F1;
        }

        .dark-mode .grey-text {
            color: #999999;
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
    <div class="theme-switch-wrapper">
        <label class="theme-switch" for="checkbox">
            <input type="checkbox" id="checkbox" />
            <div class="slider"></div>
        </label>
        <span>Dark Mode</span>
    </div>

    <div class="dashboard-header">
        <h1>Monitor CPU Realtime</h1>
    </div>

    <div class="container">
        <div class="chart-card">
            <div class="section-title">
                <i class="iconify" data-icon="mdi:cpu-64-bit"></i> Informasi CPU
            </div>
            <div id="cpuInfo"></div>
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

    <script>
        // Dark mode functions
        const darkModeToggle = document.getElementById('checkbox');
        
        function enableDarkMode() {
            document.body.classList.add('dark-mode');
            localStorage.setItem('darkMode', 'enabled');
        }

        function disableDarkMode() {
            document.body.classList.remove('dark-mode');
            localStorage.setItem('darkMode', null);
        }

        if (localStorage.getItem('darkMode') === 'enabled') {
            enableDarkMode();
            darkModeToggle.checked = true;
        }

        darkModeToggle.addEventListener('change', () => {
            if (darkModeToggle.checked) {
                enableDarkMode();
            } else {
                disableDarkMode();
            }
        });

        function formatFrequency(freq) {
            if (freq >= 1000) {
                return (freq / 1000).toFixed(2) + ' GHz';
            }
            return freq.toFixed(0) + ' MHz';
        }

        function updateData() {
            fetch('cpu_monitor_standalone.php?action=get_data')
                .then(response => response.json())
                .then(data => {
                    const cpuInfo = document.getElementById('cpuInfo');
                    cpuInfo.innerHTML = `
                        <div class="core-card">
                            <div class="card-body">
                                <h5>${data.cpu_info.model}</h5>
                                <div class="frequency-label">
                                    <span>${data.cpu_info.architecture} | ${data.cpu_info.cores} Cores</span>
                                    <span>${data.cpu_usage}% | ${data.cpu_temperature}°C</span>
                                </div>
                            </div>
                        </div>
                    `;

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