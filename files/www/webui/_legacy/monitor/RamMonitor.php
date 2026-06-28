<?php
// Fungsi untuk mendapatkan informasi RAM
function getRamInfo() {
    $info = [];
    
    // 1. Informasi Dasar RAM
    $meminfo = @file_get_contents('/proc/meminfo');
    if ($meminfo) {
        $lines = explode("\n", $meminfo);
        $memdata = [];
        foreach ($lines as $line) {
            if (empty($line)) continue;
            $parts = explode(':', $line);
            $key = trim($parts[0]);
            $value = trim(preg_replace('/\s+/', ' ', $parts[1]));
            $value = preg_replace('/ kB$/', '', $value);
            $memdata[$key] = (int)$value;
        }
        
        // Convert kB to MB
        $info['total'] = round($memdata['MemTotal'] / 1024, 2);
        $info['free'] = round($memdata['MemFree'] / 1024, 2);
        $info['available'] = isset($memdata['MemAvailable']) ? round($memdata['MemAvailable'] / 1024, 2) : round(($memdata['MemFree'] + $memdata['Buffers'] + $memdata['Cached']) / 1024, 2);
        $info['buffers'] = round($memdata['Buffers'] / 1024, 2);
        $info['cached'] = round($memdata['Cached'] / 1024, 2);
        $info['swap_total'] = round($memdata['SwapTotal'] / 1024, 2);
        $info['swap_free'] = round($memdata['SwapFree'] / 1024, 2);
        $info['swap_used'] = $info['swap_total'] - $info['swap_free'];
        
        // Perhitungan penggunaan RAM fisik tanpa swap/zram
        $info['used'] = $info['total'] - $info['free'] - $info['buffers'] - $info['cached'];
        $info['used_with_buffers'] = $info['total'] - $info['available'];
        
        // Persentase penggunaan RAM fisik
        $info['usage_percent'] = $info['total'] > 0 ? round(($info['used'] / $info['total']) * 100, 1) : 0;
        $info['usage_with_buffers_percent'] = $info['total'] > 0 ? round(($info['used_with_buffers'] / $info['total']) * 100, 1) : 0;
        $info['swap_usage_percent'] = $info['swap_total'] > 0 ? round(($info['swap_used'] / $info['swap_total']) * 100, 1) : 0;
    }
    
    // 2. Informasi Proses (Top 5 memory-consuming processes)
    $processes = [];
    $ps_output = shell_exec('ps -eo pid,user,%mem,%cpu,comm --sort=-%mem | head -n 6');
    if ($ps_output) {
        $lines = explode("\n", trim($ps_output));
        array_shift($lines); // Remove header
        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 5) {
                $processes[] = [
                    'pid' => $parts[0],
                    'user' => $parts[1],
                    'mem_percent' => $parts[2],
                    'cpu_percent' => $parts[3],
                    'command' => $parts[4]
                ];
            }
        }
    }
    $info['top_processes'] = $processes;
    
    return $info;
}

// Handler untuk membersihkan cache
if (isset($_GET['action']) && $_GET['action'] === 'clear_cache') {
    header('Content-Type: application/json');
    // Perintah untuk membersihkan cache (memerlukan root)
    $output = shell_exec('sync; echo 3 > /proc/sys/vm/drop_caches; sysctl vm.drop_caches=3; swapoff -a && swapon -a 2>&1');
    echo json_encode(['success' => true, 'output' => $output]);
    exit;
}

// Handler untuk menghentikan proses tertentu
if (isset($_GET['action']) && $_GET['action'] === 'kill_process' && isset($_GET['pid'])) {
    header('Content-Type: application/json');
    $pid = (int)$_GET['pid'];
    // Hentikan proses (memerlukan izin root)
    $output = shell_exec("kill -9 {$pid} 2>&1");
    echo json_encode(['success' => true, 'output' => $output]);
    exit;
}

// Handler untuk menghentikan semua proses yang memakan banyak RAM
if (isset($_GET['action']) && $_GET['action'] === 'kill_top_processes') {
    header('Content-Type: application/json');
    $output = "";
    
    // Dapatkan 5 proses teratas yang memakan RAM
    $processes = getRamInfo()['top_processes'];
    foreach ($processes as $process) {
        if ($process['mem_percent'] > 5) { // Hanya proses yang menggunakan >5% RAM
            $output .= shell_exec("kill -9 {$process['pid']} 2>&1");
        }
    }
    
    echo json_encode(['success' => true, 'output' => $output]);
    exit;
}

// Handler AJAX
if (isset($_GET['action']) && $_GET['action'] === 'get_data') {
    header('Content-Type: application/json');
    echo json_encode(getRamInfo());
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iStoreOS Style RAM Monitor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
  /* Color Theme */
  --primary: #FFCA0A; /* Kuning */
  --primary-light: rgba(255, 202, 10, 0.1);
  --background: #000000; /* Hitam */
  --card-bg: rgba(0, 0, 0, 0.85); /* Card hitam transparan */
  --cards-bg: rgba(255, 255, 255, 0.05);
  --blur-value: 2px;
  --blur-values: 4px;
  --text-primary: #FFCA0A; /* Teks utama kuning */
  --text-secondary: #FFFDE4; /* Teks sekunder putih kekuningan */
  --border: #FFCA0A; /* Border kuning */
  --success: #34C759;
  --warning: #FFCA0A;
  --danger: #FF3B30;
  
  /* Specific Colors */
  --cpu-color: #FFCA0A;
  --memory-color: #FFCA0A;
  --battery-color: #FFCA0A;
  --storage-color: #AF52DE;
  --signal-color: #FFCA0A;
  --network-color: #5AC8FA;
  --uptime-color: #FFFF00;
  --mobile-color: #FFCA0A;
  --wifi-color: #FFCA0A;
  --usb-color: #34C759;
  --eth-color: #5856D6;
  
  /* Network Usage Colors */
  --download-color: #FFCA0A;
  --upload-color: #FF3B30;
  --total-color: #AF52DE;
}

@media (prefers-color-scheme: dark) {
  :root {
    --background: #000000;
    --card-bg: rgba(0, 0, 0, 0.85);
    --cards-bg: rgba(255, 255, 255, 0.05);
    --blur-value: 2px;
    --blur-values: 4px;
    --text-primary: #FFCA0A;
    --text-secondary: #FFFDE4;
    --border: #FFCA0A;
    --download-color: #FFCA0A;
    --upload-color: #FF3B30;
    --total-color: #1db54d;
  }
}
        
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--background);
            color: var(--text-primary);
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header Styles */
        .main-title {
            font-size: 24px;
            font-weight: 600;
            margin: 0 0 10px 0;
            text-align: center;
            color: var(--text-primary);
        }
        
        .last-update {
            font-size: 14px;
            color: var(--text-secondary);
            text-align: center;
            margin-bottom: 15px;
        }
        
        .istoreos-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            gap: 10px;
        }
        
        .istoreos-box {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 12px 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            flex: 1;
            border-left: 4px solid;
            display: flex;
            align-items: center;
        }
        
        .ram-usage-box {
            border-left-color: var(--primary);
        }
        
        .ram-cleaner-box {
            border-left-color: var(--secondary);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .ram-cleaner-box:hover {
            background-color: rgba(52, 199, 89, 0.1);
        }
        
        /* Circular Indicator */
        .circular-indicator {
            position: relative;
            width: 50px;
            height: 50px;
            margin-right: 12px;
        }
        
        .circular-bg {
            fill: none;
            stroke: rgba(0,0,0,0.05);
            stroke-width: 6;
        }
        
        .circular-progress {
            fill: none;
            stroke: var(--primary);
            stroke-width: 6;
            stroke-linecap: round;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
            transition: stroke-dashoffset 0.3s ease;
        }
        
        .circular-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1px;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .box-content {
            flex-grow: 1;
        }
        
        .box-title {
            font-size: 15px;
            color: var(--text-secondary);
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .box-value {
            font-size: 15px;
            font-weight: 500;
        }
        
        .ram-cleaner-box .box-value {
            color: var(--secondary);
        }
        
        .cleaner-icon {
            font-size: 18px;
            color: var(--secondary);
            margin-right: 8px;
        }
        
        .card {
            background-color: var(--card-bg);
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .card-header i {
            margin-right: 8px;
            color: var(--primary);
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 500;
            margin: 0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 500;
        }
        
        .usage-badge {
            display: inline-flex;
            align-items: center;
            background-color: rgba(0, 122, 255, 0.1);
            color: var(--primary);
            padding: 8px 12px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 500;
        }
        
        .usage-badge i {
            margin-right: 6px;
        }
        
        .progress-container {
            width: 100%;
            height: 8px;
            background-color: rgba(0,0,0,0.05);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }
        
        .progress-bar {
            height: 100%;
            background-color: var(--success);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .progress-bar-warning {
            background-color: var(--warning) !important;
        }
        
        .progress-bar-danger {
            background-color: var(--danger) !important;
        }
        
        .memory-breakdown {
            margin-top: 16px;
        }
        
        .memory-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .memory-item:last-child {
            border-bottom: none;
        }
        
        .memory-label {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .memory-value {
            font-size: 14px;
            font-weight: 500;
        }
        
        .process-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .process-item:last-child {
            border-bottom: none;
        }
        
        .process-info {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .process-name {
            font-weight: 500;
            margin-bottom: 2px;
        }
        
        .process-details {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .process-usage {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            margin-left: 10px;
        }
        
        .process-mem {
            color: var(--primary);
            font-weight: 500;
        }
        
        .process-cpu {
            color: var(--warning);
            font-size: 12px;
        }
        
        .swap-container {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        
        .swap-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }
        
        .swap-label {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .swap-value {
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Toast Notification */
        .toast {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--secondary);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .toast.show {
            opacity: 1;
        }
        
        /* Kill Button Styles */
        .kill-btn {
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 4px 8px;
            margin-left: 10px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }
        
        .kill-btn:hover {
            opacity: 0.9;
        }
        
        .kill-all-btn {
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            margin-top: 10px;
            cursor: pointer;
            font-size: 14px;
            width: 100%;
            transition: all 0.2s;
        }
        
        .kill-all-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Main Title -->
        <h1 class="main-title">RAM Monitor</h1>
        
        <!-- Last Update -->
        <div class="last-update" id="lastUpdate">Just now</div>
        
        <!-- iStoreOS Style Boxes -->
        <div class="istoreos-header">
            <div class="istoreos-box ram-usage-box">
                <div class="circular-indicator">
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circular-bg"
                            d="M18 2.0845
                            a 15.9155 15.9155 0 0 1 0 31.831
                            a 15.9155 15.9155 0 0 1 0 -31.831"
                        />
                        <path class="circular-progress"
                            id="circularProgress"
                            stroke-dasharray="100, 100"
                            d="M18 2.0845
                            a 15.9155 15.9155 0 0 1 0 31.831
                            a 15.9155 15.9155 0 0 1 0 -31.831"
                        />
                        <text class="circular-text" id="circularText">0%</text>
                    </svg>
                </div>
                <div class="box-content">
                    <div class="box-title">RAM Usage</div>
                    <div class="box-value" id="headerRamUsage">-/- MB</div>
                </div>
            </div>
            
            <div class="istoreos-box ram-cleaner-box" id="cleanRamBtn">
                <i class="fas fa-broom cleaner-icon"></i>
                <div class="box-content">
                    <div class="box-title"></div>
                    <div class="box-value">Clean Now</div>
                </div>
            </div>
        </div>
        
        <!-- RAM Usage Card -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-memory"></i>
                <h2 class="card-title">Memory Usage</h2>
            </div>
            <div class="usage-badge">
                <i class="fas fa-chart-pie"></i>
                <span id="ramUsage">-</span>
            </div>
            <div class="progress-container">
                <div class="progress-bar" id="ramProgressBar" style="width: 0%"></div>
            </div>
            
            <div class="memory-breakdown">
                <div class="memory-item">
                    <span class="memory-label">Total RAM</span>
                    <span class="memory-value" id="ramTotal">- MB</span>
                </div>
                <div class="memory-item">
                    <span class="memory-label">Used</span>
                    <span class="memory-value" id="ramUsed">- MB</span>
                </div>
                <div class="memory-item">
                    <span class="memory-label">Available</span>
                    <span class="memory-value" id="ramAvailable">- MB</span>
                </div>
                <div class="memory-item">
                    <span class="memory-label">Buffers</span>
                    <span class="memory-value" id="ramBuffers">- MB</span>
                </div>
                <div class="memory-item">
                    <span class="memory-label">Cached</span>
                    <span class="memory-value" id="ramCached">- MB</span>
                </div>
            </div>
            
            <div class="swap-container">
                <div class="swap-header">
                    <span class="swap-label">Swap Usage</span>
                    <span class="swap-value" id="swapUsage">-</span>
                </div>
                <div class="progress-container">
                    <div class="progress-bar" id="swapProgressBar" style="width: 0%"></div>
                </div>
                <div class="memory-item">
                    <span class="memory-label">Total Swap</span>
                    <span class="memory-value" id="swapTotal">- MB</span>
                </div>
                <div class="memory-item">
                    <span class="memory-label">Used Swap</span>
                    <span class="memory-value" id="swapUsed">- MB</span>
                </div>
            </div>
        </div>
        
        <!-- Processes Card -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i>
                <h2 class="card-title">Top Processes</h2>
            </div>
            <div id="processesContainer"></div>
            <button class="kill-all-btn" id="killTopProcessesBtn">Kill Top Memory Processes</button>
        </div>
    </div>

    <script>
        function formatMB(value) {
            return value.toFixed(1) + 'MB';
        }

        function cleanRam() {
            fetch('?action=clear_cache')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Cache cleaned successfully!');
                        setTimeout(updateData, 1000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function killProcess(pid) {
            if (confirm(`Are you sure you want to kill process ${pid}?`)) {
                fetch(`?action=kill_process&pid=${pid}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast(`Process ${pid} killed successfully!`);
                            updateData();
                        } else {
                            showToast(`Failed to kill process: ${data.output}`, true);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Error killing process', true);
                    });
            }
        }

        function killTopProcesses() {
            if (confirm('Are you sure you want to kill all top memory-consuming processes?')) {
                fetch('?action=kill_top_processes')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Top processes killed successfully!');
                            updateData();
                        } else {
                            showToast(`Failed to kill processes: ${data.output}`, true);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Error killing processes', true);
                    });
            }
        }

        function showToast(message, isError = false) {
            const toast = document.createElement('div');
            toast.className = 'toast show';
            toast.textContent = message;
            toast.style.backgroundColor = isError ? 'var(--danger)' : 'var(--secondary)';
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function updateData() {
            fetch('?action=get_data')
                .then(response => response.json())
                .then(data => {
                    console.log('RAM Data:', data);
                    
                    // RAM Usage
                    if (data.total) {
                        // Update circular indicator
                        const usagePercent = data.usage_percent;
                        const circumference = 2 * Math.PI * 15.9155;
                        const offset = circumference - (usagePercent / 100) * circumference;
                        document.getElementById('circularProgress').style.strokeDashoffset = offset;
                        document.getElementById('circularText').textContent = `${usagePercent}%`;
                        
                        // Update header RAM usage
                        document.getElementById('headerRamUsage').textContent = 
                            `${formatMB(data.used)}/${formatMB(data.total)}`;
                        
                        // Main RAM
                        document.getElementById('ramTotal').textContent = formatMB(data.total);
                        document.getElementById('ramUsed').textContent = formatMB(data.used);
                        document.getElementById('ramAvailable').textContent = formatMB(data.available);
                        document.getElementById('ramBuffers').textContent = formatMB(data.buffers);
                        document.getElementById('ramCached').textContent = formatMB(data.cached);
                        
                        document.getElementById('ramUsage').textContent = `${usagePercent}% used`;
                        
                        const ramProgress = document.getElementById('ramProgressBar');
                        ramProgress.style.width = `${usagePercent}%`;
                        
                        // Change color based on usage level
                        ramProgress.className = 'progress-bar';
                        if (usagePercent > 80) {
                            ramProgress.classList.add('progress-bar-danger');
                        } else if (usagePercent > 60) {
                            ramProgress.classList.add('progress-bar-warning');
                        } else {
                            ramProgress.classList.remove('progress-bar-warning', 'progress-bar-danger');
                        }
                        
                        // Swap
                        if (data.swap_total > 0) {
                            document.getElementById('swapTotal').textContent = formatMB(data.swap_total);
                            document.getElementById('swapUsed').textContent = formatMB(data.swap_used);
                            document.getElementById('swapUsage').textContent = `${data.swap_usage_percent}% used`;
                            
                            const swapProgress = document.getElementById('swapProgressBar');
                            swapProgress.style.width = `${data.swap_usage_percent}%`;
                            
                            // Change color based on swap usage level
                            swapProgress.className = 'progress-bar';
                            if (data.swap_usage_percent > 80) {
                                swapProgress.classList.add('progress-bar-danger');
                            } else if (data.swap_usage_percent > 60) {
                                swapProgress.classList.add('progress-bar-warning');
                            } else {
                                swapProgress.classList.remove('progress-bar-warning', 'progress-bar-danger');
                            }
                        } else {
                            document.getElementById('swapTotal').textContent = 'Not available';
                            document.getElementById('swapUsed').textContent = '-';
                            document.getElementById('swapUsage').textContent = 'No swap';
                            document.getElementById('swapProgressBar').style.width = '0%';
                        }
                    }
                    
                    // Processes
                    if (data.top_processes && data.top_processes.length > 0) {
                        let processesHtml = '';
                        data.top_processes.forEach(process => {
                            processesHtml += `
                                <div class="process-item">
                                    <div class="process-info">
                                        <div class="process-name">${process.command}</div>
                                        <div class="process-details">PID: ${process.pid} | User: ${process.user}</div>
                                    </div>
                                    <div class="process-usage">
                                        <div class="process-mem">${process.mem_percent}%</div>
                                        <div class="process-cpu">${process.cpu_percent}% CPU</div>
                                        <button class="kill-btn" onclick="killProcess(${process.pid})">Kill</button>
                                    </div>
                                </div>
                            `;
                        });
                        document.getElementById('processesContainer').innerHTML = processesHtml;
                    } else {
                        document.getElementById('processesContainer').innerHTML = '<div style="padding: 10px; color: var(--text-secondary);">No process data available</div>';
                    }
                    
                    // Update time
                    document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('lastUpdate').textContent = 'Update failed';
                });
        }

        // Initial update
        updateData();
        // Update every 2 seconds
        setInterval(updateData, 2000);
        
        // Add event listeners
        document.getElementById('cleanRamBtn').addEventListener('click', cleanRam);
        document.getElementById('killTopProcessesBtn').addEventListener('click', killTopProcesses);
    </script>
</body>
</html>