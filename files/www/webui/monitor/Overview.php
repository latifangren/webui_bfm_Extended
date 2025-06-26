<?php
function getSystemData($command, $default = 'N/A') {
    $output = @shell_exec($command);
    return trim($output) !== '' ? trim($output) : $default;
}

// Uptime
$deviceName = getSystemData('getprop ro.product.manufacturer') . ' ' . getSystemData('getprop ro.product.model') . ' (' . getSystemData('getprop ro.product.device') . ')';
$uptime = getSystemData('cat /proc/uptime');
$uptime = floatval(explode(' ', $uptime)[0]);
$days = floor($uptime / 86400);
$hours = floor(($uptime % 86400) / 3600);
$minutes = floor(($uptime % 3600) / 60);
$uptimeFormatted = "$days days, $hours hours, $minutes minutes";

// CPU usage 
function getCpuUsage() {
    $stat1 = @shell_exec('cat /proc/stat | grep "^cpu "');
    if (!$stat1) {
        $top = @shell_exec('top -n 1 -b');
        if (preg_match('/CPU:\s+(\d+)%/', $top, $matches)) {
            return (int)$matches[1];
        }
        return 0;
    }
    usleep(500000);
    $stat2 = @shell_exec('cat /proc/stat | grep "^cpu "');
    if (!$stat2) return 0;
    $values1 = array_map('intval', preg_split('/\s+/', trim($stat1)));
    $values2 = array_map('intval', preg_split('/\s+/', trim($stat2)));
    $user1 = $values1[1] ?? 0;
    $nice1 = $values1[2] ?? 0;
    $system1 = $values1[3] ?? 0;
    $idle1 = $values1[4] ?? 0;
    $user2 = $values2[1] ?? 0;
    $nice2 = $values2[2] ?? 0;
    $system2 = $values2[3] ?? 0;
    $idle2 = $values2[4] ?? 0;
    $total1 = $user1 + $nice1 + $system1 + $idle1;
    $total2 = $user2 + $nice2 + $system2 + $idle2;
    $totalDiff = $total2 - $total1;
    $idleDiff = $idle2 - $idle1;
    if ($totalDiff <= 0) return 0;
    $cpuUsage = 100 * ($totalDiff - $idleDiff) / $totalDiff;
    return round(max(0, min(100, $cpuUsage)));
}

$cpuLoad = getCpuUsage();
$deviceModel = htmlspecialchars(getSystemData('getprop ro.product.model'));
$androidVersion = htmlspecialchars(getSystemData('getprop ro.build.version.release'));
$kernelVersion = htmlspecialchars(getSystemData('uname -r'));
$cpuCores = (int)getSystemData('nproc', 4);
$cpuModel = htmlspecialchars(getSystemData('cat /proc/cpuinfo | grep "model name" | head -1 | cut -d":" -f2 | xargs'));
if (empty($cpuModel)) {
    $cpuModel = htmlspecialchars(getSystemData('cat /proc/cpuinfo | grep Hardware | awk -F\': \' \'{print $2}\' | head -1 | xargs'));
}
$cpuFreqs = explode("\n", getSystemData('cat /sys/devices/system/cpu/cpu*/cpufreq/scaling_cur_freq'));
$cpuTemp = getSystemData('cat /sys/class/thermal/thermal_zone*/temp 2>/dev/null | head -1 | awk \'{print $1/1000}\'');
$cpuTemp = is_numeric($cpuTemp) ? round($cpuTemp, 1) : 'N/A';

// Memory info
$memTotal = round((int)getSystemData('grep MemTotal /proc/meminfo | awk \'{print $2}\'') / 1024);
$memAvailable = round((int)getSystemData('grep MemAvailable /proc/meminfo | awk \'{print $2}\'') / 1024);
$memUsed = $memTotal - $memAvailable;
$memPercent = round(($memUsed / $memTotal) * 100);
$swapTotal = round((int)getSystemData('grep SwapTotal /proc/meminfo | awk \'{print $2}\'') / 1024);
$swapFree = round((int)getSystemData('grep SwapFree /proc/meminfo | awk \'{print $2}\'') / 1024);

// GPU info
$gpuModel = getSystemData("cat /sys/kernel/gpu/gpu_model");
$gpuOpenGL = getSystemData("grep Hardware  /proc/cpuinfo");

function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// Storage info
$storageData = getSystemData('df /data 2>/dev/null | tail -n1');
if ($storageData !== 'N/A' && !empty($storageData)) {
    $storageParts = preg_split('/\s+/', trim($storageData));
    if (count($storageParts) >= 6) {
        $storageTotal = round($storageParts[1] / 1024 / 1024, 1);
        $storageUsed = round($storageParts[2] / 1024 / 1024, 1);
        $storageFree = round($storageParts[3] / 1024 / 1024, 1);
        $storagePercent = $storageTotal > 0 ? round(($storageUsed / $storageTotal) * 100) : 0;
    } else {
        $storageTotal = $storageUsed = $storageFree = $storagePercent = 0;
    }
} else {
    $storageTotal = $storageUsed = $storageFree = $storagePercent = 0;
}

// Battery info
function batStatusCheck($state) {
    switch ($state) {
        case 1:
            return "Unknown";
        case 2:
            return "Charging";
        case 3:
            return "Discharging";
        case 4:
            return "Not charging";
        case 5:
            return "Full";
    }
}
$ac_powered = shell_exec('dumpsys battery | grep AC | cut -d \':\' -f2');
$battery_level = shell_exec('dumpsys battery | grep level | cut -d \':\' -f2');
$battery_status = shell_exec('dumpsys battery | grep status | cut -d \':\' -f2');
$batteryLevel = getSystemData('dumpsys battery | grep level | awk \'{print $2}\'');
$batteryTemp = getSystemData('dumpsys battery | grep temperature | awk \'{print $2}\'');
$batteryTemp = is_numeric($batteryTemp) ? round($batteryTemp / 10, 1) . '°C' : 'N/A';
$battery_voltage_raw = getSystemData('cat /sys/class/power_supply/battery/voltage_now');
$battery_voltage = number_format($battery_voltage_raw / 1000000, 2) . ' V';

// Network Interface info
$networkInterfaces = [
    'mobile' => ['name' => 'Mobile', 'icon' => 'mdi:signal', 'interface' => 'rmnet_data1'],
    'Hotspot' => ['name' => 'Hotspot', 'icon' => 'basil:hotspot-outline', 'interface' => 'wlan0'],
    'eth' => ['name' => 'Ethernet', 'icon' => 'mdi:ethernet', 'interface' => 'eth0'],
    'usb' => ['name' => 'USB Tether', 'icon' => 'mdi:usb', 'interface' => 'rndis0']
];

foreach ($networkInterfaces as $key => $data) {
    $interfaceExists = trim(shell_exec("ip link show {$data['interface']} 2>/dev/null | wc -l")) > 0;

    if ($interfaceExists) {
        $rxBytes = (int)shell_exec("cat /sys/class/net/{$data['interface']}/statistics/rx_bytes 2>/dev/null");
        $txBytes = (int)shell_exec("cat /sys/class/net/{$data['interface']}/statistics/tx_bytes 2>/dev/null");

        $vnstatOutput = shell_exec("vnstat --json -i {$data['interface']}");
        if ($vnstatOutput === null) {
            $rxDaily = 0;
            $txDaily = 0;
        } else {
            $vnstatData = json_decode($vnstatOutput, true);
            if (isset($vnstatData['interfaces'][0]['traffic']['day'])) {
                $todayData = end($vnstatData['interfaces'][0]['traffic']['day']);
                $rxDaily = $todayData['rx'];
                $txDaily = $todayData['tx'];
            } else {
                $rxDaily = 0;
                $txDaily = 0;
            }
        }

        $ip = trim(shell_exec("ip addr show {$data['interface']} | grep 'inet ' | awk '{print \$2}' | head -n1"));
        $isConnected = $ip !== '';

        $networkInterfaces[$key]['exists'] = true;
        $networkInterfaces[$key]['rx'] = $rxBytes;
        $networkInterfaces[$key]['tx'] = $txBytes;
        $networkInterfaces[$key]['total'] = $rxBytes + $txBytes;
        $networkInterfaces[$key]['rx_daily'] = $rxDaily;
        $networkInterfaces[$key]['tx_daily'] = $txDaily;
        $networkInterfaces[$key]['total_daily'] = $rxDaily + $txDaily;
        $networkInterfaces[$key]['connected'] = $isConnected;
        $networkInterfaces[$key]['ip'] = $isConnected ? htmlspecialchars($ip) : 'No IP';
        $networkInterfaces[$key]['active'] = ($rxBytes + $txBytes) > 0;
    } else {
        $networkInterfaces[$key]['exists'] = false;
        $networkInterfaces[$key]['connected'] = false;
        $networkInterfaces[$key]['active'] = false;
        $networkInterfaces[$key]['ip'] = 'No IP';
        $networkInterfaces[$key]['rx_daily'] = 0;
        $networkInterfaces[$key]['tx_daily'] = 0;
        $networkInterfaces[$key]['total_daily'] = 0;
    }
}

// Enhanced Signal Info
$signalInfo = getSystemData('dumpsys telephony.registry');
$operator = trim(shell_exec('getprop gsm.sim.operator.alpha'));
$signalData = [];

if (!empty($signalInfo)) {
    if (preg_match('/CellSignalStrengthLte:(.+?)(?=CellSignalStrength|$)/s', $signalInfo, $lteMatch)) {
        $lteData = $lteMatch[1];
        preg_match('/rssi=([-\d]+)/', $lteData, $rssi);
        preg_match('/rsrp=([-\d]+)/', $lteData, $rsrp);
        preg_match('/rsrq=([-\d]+)/', $lteData, $rsrq);
        preg_match('/rssnr=([-\d]+)/', $lteData, $rssnr);
        preg_match('/level=(\d)/', $lteData, $level);
        $signalData[] = [
            'type' => 'LTE',
            'rssi' => $rssi[1] ?? 'N/A',
            'rsrp' => $rsrp[1] ?? 'N/A',
            'rsrq' => $rsrq[1] ?? 'N/A',
            'sinr' => $rssnr[1] ?? 'N/A',
            'level' => $level[1] ?? 0,
        ];
    }
}

$sim_operator = shell_exec('getprop gsm.sim.operator.alpha');
$sim_quality = shell_exec('dumpsys telephony.registry | grep -E "mSignalStrength="');
if (preg_match_all('/CellSignalStrengthLte: rssi=([-\d]+) rsrp=([-\d]+) rsrq=([-\d]+) rssnr=([-\d]+) .*? level=([1-9]+)/', $sim_quality, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $match) {
        $rssi = isset($match[1]) ? (int)$match[1] : 'N/A';
        $rsrp = isset($match[2]) ? (int)$match[2] : 'N/A';
        $rsrq = isset($match[3]) ? (int)$match[3] : 'N/A';
        $rssnr = isset($match[4]) ? (int)$match[4] : 'N/A';
    }
}

function getSignalLevel($level) {
    $levels = ['None', 'Poor', 'Moderate', 'Good', 'Great'];
    return $levels[$level] ?? 'Unknown';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="stylesheet" href="css/styles.css" />
  <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js" async></script>
</head>
<body class="istore-container" style="display: flex; flex-direction: column; min-height: auto; margin: 0;">
    <div class="istore-header">
      <iconify-icon icon="mdi:cellphone" class="header-icon"></iconify-icon>
      <h1>System Dashboard</h1>
      <div class="uptime-card">
        <div class="card-header">
          <iconify-icon icon="ph:clock-countdown-fill" class="header-right"></iconify-icon>
          <h3>Uptime</h3>
         </div>
       <span class="ph--clock-countdown-fill" style="font-size: 0.9rem"></span>
     </div>
   </div>
    <div class="system-overview">
      <div class="device-info">
        <span><?= htmlspecialchars($deviceModel) ?></span>
        <span>Android <?= htmlspecialchars($androidVersion) ?></span>
        <span>Kernel <?= htmlspecialchars($kernelVersion) ?></span>
      </div>
    </div>
    
    <div class="performance-section">
      <!-- CPU Card -->
      <div class="performance-card cpu-card">
        <div class="card-header" onclick="window.location.href='/webui/monitor/CpuMonitor.php'">
          <iconify-icon icon="mdi:cpu-64-bit"></iconify-icon>
          <h3>CPU</h3>
       </div>
        <div class="card-content">
          <div class="progress-container">
            <div class="progress-bar">
              <div class="progress-fill" id="cpu-progress" style="width: <?= $cpuLoad ?>%"></div>
            </div>
           <span class="progress-value" id="cpu-percent"><?= $cpuLoad ?>%</span>
          </div>
          <div class="performance-details">
            <div class="detail-item">
              <span>Model</span>
              <span><?= htmlspecialchars($cpuModel) ?></span>
            </div>
            <div class="detail-item">
              <span>Cores</span>
              <span><?= $cpuCores ?></span>
            </div>
            <div class="detail-item">
              <span>Frequency</span>
              <span><?= isset($cpuFreqs[0]) ? round($cpuFreqs[0]/1000) : 'N/A' ?> MHz</span>
            </div>
            <div class="detail-item">
              <span>Temperature</span>
              <span><?= $cpuTemp ?>°C</span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Memory Card -->
      <div class="performance-card memory-card">
        <div class="card-header" onclick="window.location.href='/webui/monitor/RamMonitor.php'">
          <iconify-icon icon="mdi:memory"></iconify-icon>
          <h3>Memory</h3>
       </div>
        <div class="card-content">
          <div class="progress-container">
            <div class="progress-bar">
              <div class="progress-fill" style="width: <?= $memPercent ?>%"></div>
            </div>
           <span class="progress-value"><?= $memPercent ?>%</span>
          </div>
          <div class="performance-details">
            <div class="detail-item">
              <span>Used</span>
              <span><?= $memUsed ?> MB</span>
            </div>
            <div class="detail-item">
              <span>Available</span>
              <span><?= $memAvailable ?> MB</span>
            </div>
            <div class="detail-item">
              <span>Total</span>
              <span><?= $memTotal ?> MB</span>
            </div>
            <?php if ($swapTotal > 0): ?>
            <div class="detail-item">
             <span>Swap</span>
             <span><?= $swapTotal - $swapFree ?> / <?= $swapTotal ?> MB</span>
           </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Battery Card -->
    <div class="status-section">
      <div class="status-card battery-card">
        <div class="card-header" onclick="window.location.href='/webui/monitor/BatteryMonitor.php'">
          <iconify-icon icon="mdi:battery"></iconify-icon>
           <h3>Battery</h3>
      </div>
        <div class="card-content">
          <div class="progress-container">
            <div class="progress-bar">
              <div class="level-fill" style="width: <?= $batteryLevel ?>%"></div>
             </div>
            <span class="progress-value"><?= $batteryLevel ?>%</span>
          </div>
          <div class="status-details">
            <div class="detail-item">
              <span>Status</span>
             <span><?= htmlspecialchars(batStatusCheck($battery_status)) ?></span>
            </div>
            <div class="detail-item">
              <span>Temperature</span>
              <span><?= $batteryTemp ?></span>
            </div>
            <div class="detail-item">
              <span>Voltage</span>
              <span><?= $battery_voltage ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Storage Card -->
      <div class="status-card storage-card">
        <div class="card-header" onclick="window.location.href='/webui/monitor/StorageMonitor.php'">
          <iconify-icon icon="mdi:harddisk"></iconify-icon>
          <h3>Storage</h3>
        </div>
        <div class="card-content">
          <div class="progress-container">
            <div class="progress-bar">
              <div class="progress-fill" style="width: <?= $storagePercent ?>%"></div>
            </div>
            <span class="progress-value"><?= $storagePercent ?>%</span>
          </div>
          <div class="status-details">
            <div class="detail-item">
              <span>Used</span>
              <span><?= $storageUsed ?> GB</span>
            </div>
            <div class="detail-item">
              <span>Free</span>
              <span><?= $storageFree ?> GB</span>
            </div>
            <div class="detail-item">
              <span>Total</span>
              <span><?= $storageTotal ?> GB</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Network & Signal Section -->
    <div class="network-section">
      <!-- Signal Card - Fixed -->
      <div class="signal-card">
        <div class="card-header" onclick="window.location.href='/tools/dashboard.php'">
         <iconify-icon icon="mdi:signal-cellular-outline"></iconify-icon>
          <h3>Signal Details</h3>
       </div>
        <div class="card-content">
          <?php if (!empty($signalData)): ?>
            <?php foreach ($signalData as $signal): ?>
              <div class="signal-type">
                <span class="network-type"><?= $signal['type'] ?></span>
                <div class="signal-strength">
                  <div class="signal-bars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <div class="bar <?= $i <= $signal['level'] ? 'active' : '' ?>"></div>
                    <?php endfor; ?>
                  </div>
                  <span class="signal-level">Level <?= $signal['level'] ?></span>
                </div>
                <div class="signal-metrics" onclick="window.location.href='/tools/pingmonitor.php'">
                  <div class="metric-row">
                    <span>RSSI:</span>
                    <span><?= $rssi ?> dBm</span>
                  </div>
                  <?php if ($signal['type'] === 'LTE'): ?>
                    <div class="metric-row">
                      <span>RSRP:</span>
                      <span><?= $rsrp ?> dBm</span>
                    </div>
                    <div class="metric-row">
                      <span>RSRQ:</span>
                      <span><?= $rsrq ?> dB</span>
                    </div>
                    <div class="metric-row">
                      <span>SINR:</span>
                      <span><?= $rssnr ?> dB</span>
                    </div>
                  <div class="operator-label">
                <span class="operator-label-text">Provider:</span>
                <span><?= htmlspecialchars($operator) ?></span>
                  <?php endif; ?>
                  <div class="metric-row">
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="signal-type">
              <span>No signal data available</span>
            </div>
          <?php endif; ?>
        </div>
      </div>
      
      
    <div class="toggle-button-container">
  <button class="toggle-button header-right" id="toggle-button">
    <iconify-icon icon="mdi:chevron-up" id="toggle-icon"></iconify-icon>
  </button>
</div>

      <!-- Network Usage Card -->
    <div class="show hidden" id="network-card">
    <div class="network-card">
      <div class="card-header" onclick="window.location.href='/tools/vnstat.php'">
        <iconify-icon icon="mdi:network"></iconify-icon>
        <h3>Network Usage</h3>
      </div>
      <div class="card-content">
        <?php foreach ($networkInterfaces as $type => $data): ?>
          <?php 
          $statusClass = '';
          $statusText = '';
          
          if (!$data['exists']) {
    $statusClass = 'status-notinstalled';
    $statusText = '<span class="text-danger">Offline</span>';
} else {
    $statusClass = $data['connected'] ? 'status-active' : 'status-inactive';
    $statusText = $data['connected'] ? '<span class="text-success">Active</span>' : '<span class="text-warning">Disconnected</span>';
}
          ?>
          <div class="network-interface">
            <div class="interface-header">
              <iconify-icon icon="<?= $data['icon'] ?>"></iconify-icon>
              <span><?= $data['name'] ?></span>
              <span class="interface-status <?= $statusClass ?>"><?= $statusText ?></span>
              <span class="ip-address"><?= $data['ip'] ?></span>
            </div>
            <?php if ($data['exists'] && $data['connected']): ?>
              <div class="usage-details">
                <div class="usage-bar">
                  <div class="download-bar" style="width: <?= min(100, ($data['rx'] / max(1, $data['rx'] + $data['tx'])) * 100) ?>%"></div>
                  <div class="upload-bar" style="width: <?= min(100, ($data['tx'] / max(1, $data['rx'] + $data['tx'])) * 100) ?>%"></div>
                </div>
                <div class="usage-stats">
                  <span class="download-stat">↓ <?= formatBytes($data['rx']) ?></span>
                  <span class="upload-stat">↑ <?= formatBytes($data['tx']) ?></span>
                  <span class="total-stat">Σ <?= formatBytes($data['total']) ?></span>
                </div>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
 </div>
 
<div style="text-align: center;">
  <div class="stamp-overview">
    Last Update: <span id="last-update"><?php echo date('Y-m-d H:i:s'); ?></span>
  </div>
</div>

<script>
// Fungsi untuk memperbarui informasi sistem
async function updateSystemInfo() {
  try {
    const cpuData = await fetch('api/system_info.php').then(response => response.json());
    document.getElementById('cpu-progress').style.width = `${cpuData.cpu_usage}%`;
    document.getElementById('cpu-percent').textContent = `${cpuData.cpu_usage}%`;

    document.querySelector('.memory-card .progress-fill').style.width = `${cpuData.mem_percent}%`;
    document.querySelector('.memory-card .progress-value').textContent = `${cpuData.mem_percent}%`;
    document.querySelector('.memory-card .detail-item:nth-child(1) span:nth-child(2)').textContent = `${cpuData.mem_used} MB`;
    document.querySelector('.memory-card .detail-item:nth-child(2) span:nth-child(2)').textContent = `${cpuData.mem_available} MB`;
    document.querySelector('.memory-card .detail-item:nth-child(3) span:nth-child(2)').textContent = `${cpuData.mem_total} MB`;

    if (cpuData.swap_total > 0) {
      document.querySelector('.memory-card .detail-item:nth-child(4) span:nth-child(2)').textContent = `${cpuData.swap_used} / ${cpuData.swap_total} MB`;
    }

    const uptimeData = await fetch('api/uptime.php').then(response => response.json());
    document.querySelector('.uptime-card span').textContent = uptimeData.uptime_formatted;

    const signalData = await fetch('api/signal_info.php').then(response => response.json());
    if (signalData.signal_data.length > 0) {
      document.querySelector('.signal-card .signal-type .network-type').textContent = signalData.signal_data[0].type;
      const levels = ['Level 0 (None)', 'Level 1 (Poor)', 'Level 2 (Moderate)', 'Level 3 (Good)', 'Level 4 (Great)'];
      document.querySelector('.signal-card .signal-strength .signal-level').textContent = levels[parseInt(signalData.signal_data[0].level)] || 'Unknown';
      document.querySelector('.signal-card .signal-metrics .metric-row:nth-child(1) span:nth-child(2)').textContent = `${signalData.signal_data[0].rssi} dBm`;
      document.querySelector('.signal-card .signal-metrics .metric-row:nth-child(2) span:nth-child(2)').textContent = `${signalData.signal_data[0].rsrp} dBm`;
      document.querySelector('.signal-card .signal-metrics .metric-row:nth-child(3) span:nth-child(2)').textContent = `${signalData.signal_data[0].rsrq} dB`;
      document.querySelector('.signal-card .signal-metrics .metric-row:nth-child(4) span:nth-child(2)').textContent = `${signalData.signal_data[0].sinr} dB`;

      const signalBars = document.querySelector('.signal-bars');
      signalBars.innerHTML = '';
      for (let i = 0; i < 5; i++) {
        const bar = document.createElement('div');
        bar.className = 'bar';
        if (i < signalData.signal_data[0].level) {
          bar.classList.add('active');
        }
        signalBars.appendChild(bar);
      }
    } else {
      document.querySelector('.signal-card .signal-type .network-type').textContent = 'No signal data available';
    }
  } catch (error) {
    console.error('Error:', error);
  }
}

// Tambahkan event listener untuk tombol naik turun
document.getElementById('toggle-button').addEventListener('click', function() {
  var networkCard = document.getElementById('network-card');
  var toggleIcon = document.getElementById('toggle-icon');
  
  networkCard.classList.toggle('hidden');
  
  if (networkCard.classList.contains('hidden')) {
    toggleIcon.setAttribute('icon', 'mdi:chevron-up');
  } else {
    toggleIcon.setAttribute('icon', 'mdi:chevron-down');
  }
});

// Fungsi untuk memperbarui waktu
function updateTimestamp() {
  const now = new Date();
  const timestamp = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
  document.getElementById('last-update').innerText = timestamp;
}

updateSystemInfo();
setInterval(updateTimestamp, 2000);
setInterval(updateSystemInfo, 5000);
  </script>
 </body>
</html>