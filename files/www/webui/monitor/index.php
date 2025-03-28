<?php
// Cache settings
session_start();
$cache_lifetime = 60; // 60 seconds cache for static data

// Function to get connected devices
function getConnectedDevices() {
    // Mendapatkan data dari tabel ARP
    $arpData = shell_exec("cat /proc/net/arp");
    $connectedDevices = [];
    
    // Proses data dari ARP
    $arpLines = explode("\n", trim($arpData));
    array_shift($arpLines); // Hapus header
    
    foreach ($arpLines as $line) {
        if (preg_match('/(\d+\.\d+\.\d+\.\d+)\s+\S+\s+\S+\s+([0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2})/i', $line, $matches)) {
            $ip = $matches[1];
            $mac = strtoupper($matches[2]);
            
            // Lakukan ping untuk memverifikasi perangkat aktif
            $pingResult = shell_exec("ping -c 1 -W 1 $ip 2>/dev/null");
            if (strpos($pingResult, "1 received") !== false) {
                $hostname = gethostbyaddr($ip);
                $device = [
                    'name' => $hostname !== $ip ? $hostname : 'Unknown Device',
                    'ip' => $ip,
                    'mac' => $mac,
                    'status' => 'Active'
                ];
                
                // Cek vendor dari MAC address (3 byte pertama)
                $macPrefix = substr($mac, 0, 8);
                $vendorCheck = shell_exec("grep -i '$macPrefix' /usr/share/nmap/nmap-mac-prefixes 2>/dev/null");
                if ($vendorCheck) {
                    $vendor = trim(explode("\t", $vendorCheck)[1] ?? '');
                    if ($vendor) {
                        $device['name'] .= " ($vendor)";
                    }
                }
                
                $connectedDevices[$ip] = $device;
            }
        }
    }
    
    // Tambahan: cek interface wifi untuk perangkat yang mungkin terlewat
    $wifiClients = shell_exec("dumpsys wifi | grep -A 1 'Client:'");
    if (preg_match_all('/Client:\s*(.*?)\n\s*MAC:\s*(.*?)\n/s', $wifiClients, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $clientName = trim($match[1]);
            $clientMac = strtoupper(trim($match[2]));
            
            // Cari IP berdasarkan MAC di data yang sudah ada
            $found = false;
            foreach ($connectedDevices as $device) {
                if ($device['mac'] === $clientMac) {
                    $found = true;
                    break;
                }
            }
            
            // Jika belum ada, coba temukan IP-nya
            if (!$found) {
                $ipSearch = shell_exec("cat /proc/net/arp | grep -i '$clientMac' | awk '{print $1}'");
                if ($ipSearch) {
                    $clientIp = trim($ipSearch);
                    $pingResult = shell_exec("ping -c 1 -W 1 $clientIp 2>/dev/null");
                    if (strpos($pingResult, "1 received") !== false) {
                        $connectedDevices[$clientIp] = [
                            'name' => $clientName ?: 'Unknown Device',
                            'ip' => $clientIp,
                            'mac' => $clientMac,
                            'status' => 'Active'
                        ];
                    }
                }
            }
        }
    }
    
    return array_values($connectedDevices);
}

// Check if this is an AJAX request for system info
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // CPU info
    $cpuFreq = shell_exec("cat /sys/devices/system/cpu/cpu0/cpufreq/scaling_cur_freq");
    $cpuFreq = intval($cpuFreq) / 1000;
    $cpuTemp = shell_exec("cat /sys/class/thermal/thermal_zone4/temp");
    $cpuTemp = round(intval($cpuTemp) / 1000, 1);
    $mpstatOutput = shell_exec('mpstat -P ALL 1 1');
    
    // Process CPU load
    $cpuLoad = 0;
    if (preg_match('/\s*all\s+[\d\.]+\s+[\d\.]+\s+[\d\.]+\s+[\d\.]+\s+[\d\.]+\s+[\d\.]+\s+[\d\.]+\s+[\d\.]+\s+([\d\.]+)/', $mpstatOutput, $matches)) {
        $cpuLoad = round(100 - floatval($matches[1]), 1);
    }

    // Get Memory info
    $total_memory_kb = shell_exec('grep MemTotal /proc/meminfo | awk \'{print $2}\'');
    $available_memory_kb = shell_exec('grep MemAvailable /proc/meminfo | awk \'{print $2}\'');
    $swap_total_kb = shell_exec('grep SwapTotal /proc/meminfo | awk \'{print $2}\'');
    $swap_free_kb = shell_exec('grep SwapFree /proc/meminfo | awk \'{print $2}\'');

    // Calculate memory usage
    $total_memory_gb = round(intval(trim($total_memory_kb)) / 1024 / 1024, 1);
    $available_memory_gb = round(intval(trim($available_memory_kb)) / 1024 / 1024, 1);
    $used_memory_gb = $total_memory_gb - $available_memory_gb;
    $memory_percent = round(($used_memory_gb / $total_memory_gb) * 100);

    // Calculate swap usage
    $swap_total_gb = round(intval(trim($swap_total_kb)) / 1024 / 1024, 1);
    $swap_free_gb = round(intval(trim($swap_free_kb)) / 1024 / 1024, 1);
    $swap_used_gb = $swap_total_gb - $swap_free_gb;
    $swap_percent = round(($swap_used_gb / ($swap_total_gb ?: 1)) * 100);

    // Get Internal Storage info
    function getStorageInfo() {
        $storage = [
            'total' => 'N/A',
            'used' => 'N/A',
            'available' => 'N/A',
            'percentage' => 0,
            'filesystem' => 'N/A',
            'system_partition' => 'N/A',
            'data_partition' => 'N/A'
        ];
        
        // Dapatkan info partisi sistem
        $system_info = shell_exec("df -h /system 2>/dev/null | grep -v Filesystem");
        if (preg_match('/(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\d+)%\s+(\S+)/', $system_info, $matches)) {
            $storage['system_partition'] = $matches[2];
        }
        
        // Dapatkan info partisi data
        $data_info = shell_exec("df -h /data 2>/dev/null | grep -v Filesystem");
        if (preg_match('/(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\d+)%\s+(\S+)/', $data_info, $matches)) {
            $storage['data_partition'] = $matches[2];
        }
        
        // Coba dapatkan info storage menggunakan df
        $df = shell_exec("df -h 2>/dev/null | grep -E '^/dev/'");
        $lines = explode("\n", trim($df));
        
        foreach ($lines as $line) {
            if (preg_match('/^\/dev\/\S+\s+(\S+)\s+(\S+)\s+(\S+)\s+(\d+)%\s+(\S+)/', $line, $matches)) {
                if (strpos($matches[5], 'data') !== false || 
                    ($storage['total'] === 'N/A' || convertToBytes($matches[1]) > convertToBytes($storage['total']))) {
                    $storage['total'] = $matches[1];
                    $storage['used'] = $matches[2];
                    $storage['available'] = $matches[3];
                    $storage['percentage'] = intval($matches[4]);
                    $storage['mount_point'] = $matches[5];
                }
            }
        }
        
        return $storage;
    }

    // Helper function untuk mengkonversi ukuran storage ke bytes
    function convertToBytes($size) {
        $size = trim($size);
        $unit = strtoupper(substr($size, -1));
        $value = floatval($size);
        
        switch ($unit) {
            case 'T': return $value * 1024 * 1024 * 1024 * 1024;
            case 'G': return $value * 1024 * 1024 * 1024;
            case 'M': return $value * 1024 * 1024;
            case 'K': return $value * 1024;
            default: return $value;
        }
    }

    // Helper function untuk memformat kecepatan IO
    function formatIOSpeed($speed_kb) {
        if ($speed_kb > 1024) {
            return round($speed_kb / 1024, 2) . ' MB/s';
        }
        return round($speed_kb, 2) . ' KB/s';
    }

    // Get storage info (cached)
    $storageInfo = getCachedData('storage_info', 'getStorageInfo');

    // Pastikan nilai persentase adalah angka
    $storageInfo['percentage'] = is_numeric($storageInfo['percentage']) ? intval($storageInfo['percentage']) : 0;
    
    header('Content-Type: application/json');
    echo json_encode([
        'cpuFreq' => round($cpuFreq),
        'cpuLoad' => $cpuLoad,
        'cpuTemp' => $cpuTemp,
        'memoryTotal' => $total_memory_gb . ' GB',
        'memoryUsed' => $used_memory_gb . ' GB',
        'memoryPercent' => $memory_percent,
        'swapTotal' => $swap_total_gb . ' GB',
        'swapUsed' => $swap_used_gb . ' GB',
        'swapPercent' => $swap_percent,
        'storageTotal' => $storageInfo['total'],
        'storageUsed' => $storageInfo['used'],
        'storageAvailable' => $storageInfo['available'],
        'storagePercent' => $storageInfo['percentage'],
        'systemPartition' => $storageInfo['system_partition'],
        'dataPartition' => $storageInfo['data_partition']
    ]);
    exit;
}

// Function to get cached data or fetch new data
function getCachedData($key, $callback) {
    if (isset($_SESSION[$key]) && isset($_SESSION[$key . '_time']) && 
        (time() - $_SESSION[$key . '_time'] < $GLOBALS['cache_lifetime'])) {
        return $_SESSION[$key];
    }
    $data = $callback();
    $_SESSION[$key] = $data;
    $_SESSION[$key . '_time'] = time();
    return $data;
}

// Get device info (cached)
$deviceInfo = getCachedData('device_info', function() {
    return [
        'manufacturer' => trim(shell_exec('getprop ro.product.manufacturer')),
        'model' => trim(shell_exec('getprop ro.product.model')),
        'device' => trim(shell_exec('getprop ro.product.device')),
        'os' => trim(shell_exec('getprop ro.build.version.release'))
    ];
});

// Format device name
$deviceName = "{$deviceInfo['manufacturer']} {$deviceInfo['model']} ({$deviceInfo['device']})";
$os = $deviceInfo['os'];

// Get uptime
$uptime = floatval(shell_exec('cat /proc/uptime'));
$days = floor($uptime / 86400);
$hours = floor(($uptime % 86400) / 3600);
$minutes = floor(($uptime % 3600) / 60);
$uptimeFormatted = "$days hari, $hours jam, $minutes menit";

// Get battery info (cached for 5 seconds)
$batteryInfo = getCachedData('battery_info', function() {
    $temperature = shell_exec('dumpsys battery');
    $matches = [];
    preg_match('/temperature:\s(\d+)/', $temperature, $tempMatches);
    preg_match('/level:\s(\d+)/', $temperature, $levelMatches);
    preg_match('/AC\s*powered:\s*(true|false)/', $temperature, $chargingMatches);
    
    $voltage_raw = shell_exec('cat /sys/class/power_supply/battery/voltage_now');
    
    return [
        'temp' => isset($tempMatches[1]) ? $tempMatches[1] : 0,
        'level' => isset($levelMatches[1]) ? $levelMatches[1] : 0,
        'charging' => isset($chargingMatches[1]) ? $chargingMatches[1] : 'false',
        'voltage' => $voltage_raw
    ];
});

$temperatureFormatted = number_format($batteryInfo['temp'] / 10, 1);
$level = $batteryInfo['level'];
$chargingStatus = strtolower($batteryInfo['charging']) === 'true' ? 'True' : 'False';
$battery_voltage = number_format($batteryInfo['voltage'] / 1000000, 2) . 'V';

// Get network interfaces (cached)
$networkInterfaces = getCachedData('network_interfaces', function() {
    $interfaces = [];
    $netDevices = explode("\n", trim(shell_exec("ls /sys/class/net")));
    
    foreach ($netDevices as $interface) {
        if (empty($interface)) continue;
        
        $ip = trim(shell_exec("ip addr show $interface | grep 'inet ' | awk '{print $2}'"));
        if (empty($ip)) continue;
        
        $stats = [
            'rx' => (int)shell_exec("cat /sys/class/net/$interface/statistics/rx_bytes"),
            'tx' => (int)shell_exec("cat /sys/class/net/$interface/statistics/tx_bytes")
        ];
        
        $interfaces[] = [
            'name' => $interface,
            'ip' => $ip,
            'received' => formatBytes($stats['rx']),
            'transmitted' => formatBytes($stats['tx'])
        ];
    }
    return $interfaces;
});

// Helper function to format bytes
function formatBytes($bytes) {
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 2) . ' GB';
    }
    return round($bytes / 1048576, 2) . ' MB';
}

// Get CPU info (cached)
$cpuInfo = getCachedData('cpu_info', function() {
    $cpu = shell_exec('cat /proc/cpuinfo');
    preg_match('/Hardware\s+:\s+([^\n]+)/', $cpu, $hardware);
    preg_match_all('/processor\s+/', $cpu, $processors);
    
    return [
        'hardware' => isset($hardware[1]) ? trim(preg_replace('/^(Qualcomm Technologies, Inc|MediaTek|Broadcom)\s*/', '', $hardware[1])) : 'Not found',
        'cores' => isset($processors[0]) ? count($processors[0]) : 'Not found'
    ];
});

$hardwareResult = $cpuInfo['hardware'];
$coreCount = $cpuInfo['cores'];

// Get SIM info (cached)
$simInfo = getCachedData('sim_info', function() {
    $operator = trim(shell_exec('getprop gsm.sim.operator.alpha'));
    $signal = shell_exec('dumpsys telephony.registry | grep -E "mSignalStrength="');
    $signalInfo = ['rssi' => 'N/A', 'rsrp' => 'N/A', 'rsrq' => 'N/A', 'rssnr' => 'N/A'];
    
    // Get LTE Band info
    $current_band = "Tidak terdeteksi";
    $network_type = trim(shell_exec('getprop gsm.network.type'));
    $radio_type = trim(shell_exec('getprop gsm.current.phone-type'));
    $bandwidth = 'N/A';

    // Cek apakah menggunakan jaringan LTE/4G
    if (strpos(strtoupper($network_type), 'LTE') !== false || strpos(strtoupper($network_type), '4G') !== false) {
        // Array untuk memetakan nomor band ke informasi frekuensi
        $bandMap = [
            '1' => ['band' => 'Band 1', 'freq' => '2100 MHz', 'provider' => 'FDD'],
            '3' => ['band' => 'Band 3', 'freq' => '1800 MHz', 'provider' => 'FDD'],
            '5' => ['band' => 'Band 5', 'freq' => '850 MHz', 'provider' => 'FDD'],
            '8' => ['band' => 'Band 8', 'freq' => '900 MHz', 'provider' => 'FDD'],
            '40' => ['band' => 'Band 40', 'freq' => '2300 MHz', 'provider' => 'TDD']
        ];
        
        // METODE 1: Direct from getprop
        $possibleProps = [
            'gsm.baseband.channel',
            'gsm.network.type',
            'gsm.sim.operator.numeric',
            'ril.lte.caid',
            'ril.nw.band'
        ];
        
        foreach ($possibleProps as $prop) {
            $propValue = trim(shell_exec("getprop $prop 2>/dev/null"));
            if (!empty($propValue)) {
                if (preg_match('/([1-9][0-9]?)/', $propValue, $matches)) {
                    $bandNum = $matches[1];
                    if (isset($bandMap[$bandNum])) {
                        $current_band = $bandMap[$bandNum]['band'] . " (" . $bandMap[$bandNum]['freq'] . " - " . $bandMap[$bandNum]['provider'] . ")";
                        break;
                    }
                }
            }
        }
        
        // METODE 2: Dari dumpsys jika masih tidak terdeteksi
        if ($current_band === "Tidak terdeteksi") {
            $output = shell_exec("dumpsys telephony.registry");
            if (preg_match('/earfcn\s*=\s*(\d+)/i', $output, $matches)) {
                $earfcn = (int)$matches[1];
                if ($earfcn >= 0 && $earfcn <= 599) {
                    $current_band = "Band 1 (2100 MHz - FDD)";
                } elseif ($earfcn >= 1200 && $earfcn <= 1949) {
                    $current_band = "Band 3 (1800 MHz - FDD)";
                } elseif ($earfcn >= 2400 && $earfcn <= 2649) {
                    $current_band = "Band 5 (850 MHz - FDD)";
                } elseif ($earfcn >= 3450 && $earfcn <= 3799) {
                    $current_band = "Band 8 (900 MHz - FDD)";
                } elseif ($earfcn >= 38650 && $earfcn <= 39649) {
                    $current_band = "Band 40 (2300 MHz - TDD)";
                }
            }
        }

        // Get bandwidth
        if (preg_match('/Bandwidth:\s*(\d+)/', $output, $bwMatch)) {
            $bandwidth = $bwMatch[1] . ' MHz';
        }
    }
    
    if (preg_match('/CellSignalStrengthLte: rssi=([-\d]+) rsrp=([-\d]+) rsrq=([-\d]+) rssnr=([-\d]+)/', $signal, $matches)) {
        $signalInfo = [
            'rssi' => $matches[1],
            'rsrp' => $matches[2],
            'rsrq' => $matches[3],
            'rssnr' => $matches[4]
        ];
    }
    
    return array_merge(
        ['operator' => $operator], 
        $signalInfo,
        ['band' => $current_band, 'bandwidth' => $bandwidth]
    );
});

$sim_operator = $simInfo['operator'];
$rssi = $simInfo['rssi'];
$rsrp = $simInfo['rsrp'];
$rsrq = $simInfo['rsrq'];
$rssnr = $simInfo['rssnr'];
$lteBand = $simInfo['band'];
$lteBandwidth = $simInfo['bandwidth'];

// Get connected devices (cached)
$connectedDevices = getCachedData('connected_devices', function() {
    return getConnectedDevices();
});

// Regular page load continues here
// Left Card
$temperature = shell_exec('dumpsys battery');
preg_match('/temperature:\s(\d+)/', $temperature, $matches);
preg_match('/level:\s(\d+)/', $temperature, $levelMatches);
preg_match('/AC\s*powered:\s*(true|false)/', $temperature, $chargingMatches);
if (isset($matches[1], $levelMatches[1], $chargingMatches[1])) {
    $temperatureValue = $matches[1];
    $temperatureFormatted = number_format($temperatureValue / 10, 1);
    $level = $levelMatches[1];
    $chargingStatus = strtolower($chargingMatches[1]) === 'true' ? 'True' : 'False';
}
$battery_voltage_raw = shell_exec('cat /sys/class/power_supply/battery/voltage_now');
$battery_voltage = number_format($battery_voltage_raw / 1000000, 2) . 'V';
// Network 
$networkInterfaces = [];
$interfaces = shell_exec("ls /sys/class/net");
foreach (explode("\n", $interfaces) as $interface) {
    if (empty($interface)) continue;
    $ip = shell_exec("ip addr show $interface | grep 'inet ' | awk '{print $2}'");
    $rx = shell_exec("cat /sys/class/net/$interface/statistics/rx_bytes");
    $tx = shell_exec("cat /sys/class/net/$interface/statistics/tx_bytes");
    $receivedMB = round($rx / 1024 / 1024, 2);
    $transmittedMB = round($tx / 1024 / 1024, 2);
    $receivedGB = round($rx / 1024 / 1024 / 1024, 2);
    $transmittedGB = round($tx / 1024 / 1024 / 1024, 2);
    if (!empty($ip)) {
        $networkInterfaces[] = [
            'name' => $interface,
            'ip' => $ip ?: '-',
            'received' => ($receivedGB >= 1 ? $receivedGB . ' GB' : $receivedMB . ' MB'),
            'transmitted' => ($transmittedGB >= 1 ? $transmittedGB . ' GB' : $transmittedMB . ' MB')
        ];
    }
}

// Cpu
$cpu = shell_exec('cat /proc/cpuinfo');
preg_match('/Hardware\s+:\s+([^\n]+)/', $cpu, $hardware);
preg_match_all('/processor\s+/', $cpu, $processors);
$hardwareResult = isset($hardware[1]) ? trim(preg_replace('/^(Qualcomm Technologies, Inc|MediaTek|Broadcom)\s*/', '', $hardware[1])) : 'Not found';  // Ambil model hardware tanpa vendor
$coreCount = isset($processors[0]) ? count($processors[0]) : 'Not found';  // Menghitung jumlah core berdasarkan entri "processor"

// SIM Card
$sim_operator = shell_exec('getprop gsm.sim.operator.alpha');
// Capture the output from the shell command
$sim_quality = shell_exec('dumpsys telephony.registry | grep -E "mSignalStrength="');
if (preg_match_all('/CellSignalStrengthLte: rssi=([-\d]+) rsrp=([-\d]+) rsrq=([-\d]+) rssnr=([-\d]+) .*? level=([1-9]+)/', $sim_quality, $matches, PREG_SET_ORDER)) {
    // Loop through each match and process the data
    foreach ($matches as $match) {
        // Assign the values to variables
        $rssi = isset($match[1]) ? (int)$match[1] : 'N/A';
        $rsrp = isset($match[2]) ? (int)$match[2] : 'N/A';
        $rsrq = isset($match[3]) ? (int)$match[3] : 'N/A';
        $rssnr = isset($match[4]) ? (int)$match[4] : 'N/A';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>System Dashboard</title>
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
  <link rel="stylesheet" href="css/styles.css">
</head>
<body>
  <div class="theme-switch-wrapper" id="darkModeToggle">
    <span class="iconify" data-icon="mdi:weather-sunny"></span>
  </div>

  <div class="dashboard-header">
    <h1>System Dashboard</h1>
    <p class="device-info"><?= $deviceName ?> - Android <?= $os ?></p>
  </div>

  <div class="container">
    <!-- System Overview -->
    <div class="chart-card">
      <div class="section-title">
        <span class="iconify" data-icon="mdi:desktop-mac-dashboard"></span>
        <span>System Overview</span>
      </div>
      <div class="status-info">
        <div class="status-item">
          <span class="iconify" data-icon="mdi:clock-outline"></span>
          <div class="status-details">
            <span class="status-label">Uptime</span>
            <span class="status-value"><?= $uptimeFormatted ?></span>
          </div>
        </div>
        <div class="status-item">
          <span class="iconify" data-icon="mdi:thermometer"></span>
          <div class="status-details">
            <span class="status-label">Battery Temperature</span>
            <span class="status-value"><?= $temperatureFormatted ?>°C</span>
          </div>
        </div>
        <div class="status-item">
          <span class="iconify" data-icon="mdi:battery-charging"></span>
          <div class="status-details">
            <span class="status-label">Battery</span>
            <span class="status-value"><?= $level ?>% (<?= $battery_voltage ?>)</span>
          </div>
        </div>
        <div class="status-item">
          <span class="iconify" data-icon="mdi:wifi"></span>
          <div class="status-details">
            <span class="status-label">Connected Devices</span>
            <span class="status-value"><?= count($connectedDevices) ?> device(s)</span>
            <?php if (!empty($connectedDevices)): ?>
              <div class="connected-devices-list">
                <?php foreach($connectedDevices as $device): ?>
                  <div class="device-item">
                    <?= $device['name'] ?><br>
                    <small>IP: <?= $device['ip'] ?><br>MAC: <?= $device['mac'] ?></small>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Memory Usage -->
    <div class="chart-card">
      <div class="section-title">
        <span class="iconify" data-icon="mdi:memory"></span>
        <span>Memory Usage</span>
      </div>
      <div class="chart" id="memoryChart" data-value="0%" data-label="Memory">
        <div class="chart-ring"></div>
      </div>
      <div class="details">
        <div class="progress-bar">
          <div class="bar-label">
            <span>RAM Usage</span>
            <span class="used-memory-percent"></span>
          </div>
          <div class="bar">
            <div class="bar-inner"></div>
          </div>
          <span class="total-memory"></span>
        </div>
        <div class="progress-bar">
          <div class="bar-label">
            <span>Swap Usage</span>
            <span class="used-swap-percent"></span>
          </div>
          <div class="bar">
            <div class="bar-inner"></div>
          </div>
          <span class="total-swap"></span>
        </div>
      </div>
    </div>

    <!-- CPU Status -->
    <div class="chart-card">
      <div class="section-title">
        <span class="iconify" data-icon="mdi:cpu-64-bit"></span>
        <span>CPU Status</span>
      </div>
      <div class="chart" id="cpuChart" data-value="0%" data-label="CPU">
        <div class="chart-ring"></div>
      </div>
      <div class="cpu-details">
        <div class="cpu-progress-bar">
          <div class="cpu-bar-label">
            <span>CPU Load</span>
            <span class="cpu-load">0%</span>
          </div>
          <div class="cpu-bar">
            <div class="cpu-bar-inner" style="width: 0%"></div>
          </div>
          <div class="cpu-value">
            <span class="cpu-freq"><?= $cpuFreq ?> MHz</span> | 
            <span class="cpu-temp"><?= $cpuTemp ?>°C</span>
          </div>
        </div>
        <div class="cpu-progress-bar">
          <div class="cpu-bar-label">
            <span>CPU Model</span>
          </div>
          <div class="cpu-value">
            <span class="cpu-model"><?= trim($hardwareResult) ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Storage Status -->
    <div class="chart-card">
      <div class="section-title">
        <span class="iconify" data-icon="mdi:harddisk"></span>
        <span>Penyimpanan Internal</span>
      </div>
      <div class="chart" id="storageChart" data-value="0%" data-label="Storage">
        <div class="chart-ring"></div>
      </div>
      <div class="storage-details">
        <div class="progress-bar">
          <div class="bar-label">
            <span>Penggunaan Storage</span>
            <span class="storage-percent">0%</span>
          </div>
          <div class="bar">
            <div class="bar-inner" style="width: 0%"></div>
          </div>
          <div class="storage-info">
            <span>Total: <span class="storage-total">0 GB</span></span> |
            <span>Tersedia: <span class="storage-available">0 GB</span></span>
          </div>
        </div>
        <div class="storage-io-stats">
          <div class="io-stat">
            <span class="iconify" data-icon="mdi:folder-outline"></span>
            <div class="io-details">
                <span class="io-label">Partisi Sistem</span>
                <span class="system-partition">N/A</span>
            </div>
          </div>
          <div class="io-stat">
            <span class="iconify" data-icon="mdi:folder-data-outline"></span>
            <div class="io-details">
                <span class="io-label">Partisi Data</span>
                <span class="data-partition">N/A</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Network Status & Mobile Network (Combined) -->
  <div class="container">
    <div class="network-mobile-grid">
      <!-- Network Interfaces -->
      <div class="chart-card network-section">
        <div class="section-title">
          <span class="iconify" data-icon="mdi:network"></span>
          <span>Network Interfaces</span>
        </div>
        <div class="compact-network-grid">
          <?php foreach ($networkInterfaces as $interface): ?>
          <div class="compact-network-item">
            <div class="network-item-header">
              <span class="iconify" data-icon="mdi:network"></span>
              <span class="network-item-name"><?= $interface['name'] ?></span>
              <span class="network-item-ip"><?= $interface['ip'] ?></span>
            </div>
            <div class="network-stats">
              <span>↓ <?= $interface['received'] ?></span>
              <span>↑ <?= $interface['transmitted'] ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Mobile Network -->
      <div class="chart-card mobile-section">
        <div class="section-title">
          <span class="iconify" data-icon="mdi:signal-4g"></span>
          <span>Mobile Network</span>
        </div>
        <div class="compact-mobile-info">
          <div class="mobile-header">
            <div class="operator-info">
              <span class="iconify" data-icon="mdi:sim"></span>
              <span><?= $sim_operator ?></span>
            </div>
            <div class="band-info">
              <span class="iconify" data-icon="mdi:signal-4g"></span>
              <span><?= $lteBand ?> (<?= $lteBandwidth ?>)</span>
            </div>
          </div>
          <div class="signal-grid">
            <div class="signal-item" title="RSSI">
              <small>RSSI</small>
              <span><?= $rssi ?> dBm</span>
            </div>
            <div class="signal-item" title="RSRP">
              <small>RSRP</small>
              <span><?= $rsrp ?> dBm</span>
            </div>
            <div class="signal-item" title="RSRQ">
              <small>RSRQ</small>
              <span><?= $rsrq ?> dB</span>
            </div>
            <div class="signal-item" title="RSSNR">
              <small>RSSNR</small>
              <span><?= $rssnr ?> dB</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  </div>

  <script>
let currentBarIndex = 0;
let bars = [];
// Cache DOM elements
const domElements = {
    totalMemory: document.querySelector('.total-memory'),
    usedMemoryPercent: document.querySelector('.used-memory-percent'),
    totalSwap: document.querySelector('.total-swap'),
    usedSwapPercent: document.querySelector('.used-swap-percent'),
    cpuFreq: document.querySelector('.cpu-freq'),
    cpuLoad: document.querySelector('.cpu-load'),
    cpuTemp: document.querySelector('.cpu-temp'),
    cpuModel: document.querySelector('.cpu-model'),
    cpuBarInner: document.querySelector('.cpu-bar-inner'),
    memoryChart: document.querySelector('#memoryChart'),
    cpuChart: document.querySelector('#cpuChart'),
    barElements: document.querySelectorAll('.bar-inner'),
    darkModeToggle: document.getElementById('darkModeToggle')
};

// Dark mode functions
function enableDarkMode() {
    document.documentElement.setAttribute('data-theme', 'dark');
    localStorage.setItem('theme', 'dark');
}

function disableDarkMode() {
    document.documentElement.setAttribute('data-theme', 'light');
    localStorage.setItem('theme', 'light');
}

// Check for saved dark mode preference
if (localStorage.getItem('theme') === 'dark') {
    enableDarkMode();
}

// Listen for dark mode toggle
domElements.darkModeToggle.addEventListener('click', () => {
    if (document.documentElement.getAttribute('data-theme') === 'dark') {
        disableDarkMode();
        domElements.darkModeToggle.querySelector('.iconify').setAttribute('data-icon', 'mdi:weather-sunny');
    } else {
        enableDarkMode();
        domElements.darkModeToggle.querySelector('.iconify').setAttribute('data-icon', 'mdi:weather-night');
    }
});

function updateChartDonut(element, value) {
    if (!element) return;
    
    // Pastikan value adalah angka dan dalam rentang 0-100
    value = Math.min(Math.max(parseFloat(value) || 0, 0), 100);
    
    // Update nilai persentase untuk tampilan
    element.setAttribute('data-value', `${Math.round(value)}%`);
    
    // Update chart ring dengan nilai persentase baru
    const ring = element.querySelector('.chart-ring');
    if (!ring) return;
    
    // Set CSS custom property untuk animasi
    ring.style.setProperty('--progress-value', value + '%');
    
    // Tambahkan kelas untuk memicu animasi ulang
    ring.classList.remove('animate');
    void ring.offsetWidth; // Trigger reflow
    ring.classList.add('animate');
}

function updateAllCharts(data) {
    // Update CPU Chart
    const cpuChart = document.querySelector('#cpuChart');
    if (cpuChart) {
        updateChartDonut(cpuChart, data.cpuLoad);
        const cpuLoad = document.querySelector('.cpu-load');
        if (cpuLoad) cpuLoad.textContent = `${data.cpuLoad}%`;
        const cpuBar = document.querySelector('.cpu-bar-inner');
        if (cpuBar) cpuBar.style.width = `${data.cpuLoad}%`;
    }
    
    // Update Memory Chart
    const memoryChart = document.querySelector('#memoryChart');
    if (memoryChart) {
        updateChartDonut(memoryChart, data.memoryPercent);
        const memoryPercent = document.querySelector('.used-memory-percent');
        if (memoryPercent) memoryPercent.textContent = `${data.memoryPercent}%`;
    }
    
    // Update Storage Chart
    const storageChart = document.querySelector('#storageChart');
    if (storageChart) {
        updateChartDonut(storageChart, data.storagePercent);
        const storagePercent = document.querySelector('.storage-percent');
        if (storagePercent) storagePercent.textContent = `${data.storagePercent}%`;
        const storageBar = document.querySelector('.storage-details .bar-inner');
        if (storageBar) storageBar.style.width = `${data.storagePercent}%`;
    }
}

function updateSystemStatus() {
    fetch('index.php', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Update semua chart dan nilai terkait
        updateAllCharts(data);
        
        // Update nilai-nilai lainnya
        if (domElements.cpuFreq) domElements.cpuFreq.textContent = `${data.cpuFreq} MHz`;
        if (domElements.cpuTemp) domElements.cpuTemp.textContent = `${data.cpuTemp}°C`;
        if (domElements.totalMemory) domElements.totalMemory.textContent = `(${data.memoryTotal})`;
        if (domElements.totalSwap) domElements.totalSwap.textContent = `(${data.swapTotal})`;
        
        // Update storage info
        const storageTotal = document.querySelector('.storage-total');
        const storageAvailable = document.querySelector('.storage-available');
        const systemPartition = document.querySelector('.system-partition');
        const dataPartition = document.querySelector('.data-partition');
        
        if (storageTotal) storageTotal.textContent = data.storageTotal;
        if (storageAvailable) storageAvailable.textContent = data.storageAvailable;
        if (systemPartition) systemPartition.textContent = data.systemPartition;
        if (dataPartition) dataPartition.textContent = data.dataPartition;
        
        // Update memory bars
        if (domElements.barElements.length >= 2) {
            domElements.barElements[0].style.width = `${data.memoryPercent}%`;
            domElements.barElements[1].style.width = `${data.swapPercent}%`;
        }
    })
    .catch(error => console.error('Error:', error));
}

// Update interval
const UPDATE_INTERVAL = 1500; // 1.5 seconds
setInterval(updateSystemStatus, UPDATE_INTERVAL);

// Initial call
updateSystemStatus();

function updateCpuTemp(temp) {
    const cpuTempElement = document.querySelector('.cpu-temp');
    if (!cpuTempElement) return;

    // Remove existing temperature state
    cpuTempElement.removeAttribute('data-temp');

    // Set new temperature state
    if (temp < 50) {
        cpuTempElement.setAttribute('data-temp', 'normal');
    } else if (temp < 70) {
        cpuTempElement.setAttribute('data-temp', 'warm');
    } else {
        cpuTempElement.setAttribute('data-temp', 'hot');
    }
}

// Update CPU temperature color on load
document.addEventListener('DOMContentLoaded', function() {
    const cpuTempElement = document.querySelector('.cpu-temp');
    if (cpuTempElement) {
        const temp = parseFloat(cpuTempElement.textContent);
        updateCpuTemp(temp);
    }
});

// Update CPU temperature color when value changes
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.type === 'characterData' || mutation.type === 'childList') {
            const temp = parseFloat(mutation.target.textContent);
            if (!isNaN(temp)) {
                updateCpuTemp(temp);
            }
        }
    });
});

const cpuTempElement = document.querySelector('.cpu-temp');
if (cpuTempElement) {
    observer.observe(cpuTempElement, {
        characterData: true,
        childList: true,
        subtree: true
    });
}

// Update iconify icon based on current theme on load
document.addEventListener('DOMContentLoaded', function() {
    if (document.documentElement.getAttribute('data-theme') === 'dark') {
        domElements.darkModeToggle.querySelector('.iconify').setAttribute('data-icon', 'mdi:weather-sunny');
    }
});

// Remove old dark mode toggle script that's now redundant
const toggleSwitch = document.querySelector('#checkbox');
// ... existing code ...
 </script>
</body>
</html>