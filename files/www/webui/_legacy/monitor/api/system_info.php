<?php
// Fungsi untuk mendapatkan CPU usage
function getCpuUsage() {
    // Baca kode dari fungsi getCpuUsage yang sudah ada
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

// Fungsi untuk mendapatkan memori
function getMemoryInfo() {
    $memTotal = round((int)shell_exec('grep MemTotal /proc/meminfo | awk \'{print $2}\'') / 1024);
    $memAvailable = round((int)shell_exec('grep MemAvailable /proc/meminfo | awk \'{print $2}\'') / 1024);
    $memUsed = $memTotal - $memAvailable;
    $memPercent = round(($memUsed / $memTotal) * 100);

    $swapTotal = round((int)shell_exec('grep SwapTotal /proc/meminfo | awk \'{print $2}\'') / 1024);
    $swapFree = round((int)shell_exec('grep SwapFree /proc/meminfo | awk \'{print $2}\'') / 1024);
    $swapUsed = $swapTotal - $swapFree;

    return [
        'mem_percent' => $memPercent,
        'mem_used' => $memUsed,
        'mem_available' => $memAvailable,
        'mem_total' => $memTotal,
        'swap_total' => $swapTotal,
        'swap_used' => $swapUsed,
    ];
}

// Fungsi untuk mendapatkan baterai
function getBatteryInfo() {
    $batteryLevel = shell_exec('dumpsys battery | grep level | cut -d ":" -f2');
    $batteryStatus = shell_exec('dumpsys battery | grep status | cut -d ":" -f2');
    $batteryTemp = shell_exec('dumpsys battery | grep temperature | cut -d ":" -f2');
    $batteryVoltage = shell_exec('cat /sys/class/power_supply/battery/voltage_now');
    $batteryCurrent = shell_exec('cat /sys/class/power_supply/battery/current_now');

    // Validasi batteryLevel harus angka 0-100
    $batteryLevel = trim($batteryLevel);
    if (!is_numeric($batteryLevel) || $batteryLevel < 0 || $batteryLevel > 100) {
        $batteryLevel = null;
    }

    return [
        'battery_level' => $batteryLevel,
        'battery_status' => trim($batteryStatus),
        'battery_temp' => trim($batteryTemp),
        'battery_voltage' => round(trim($batteryVoltage) / 1000000, 2),
        'battery_current' => trim($batteryCurrent),
    ];
}

// Fungsi untuk mendapatkan uptime
function getUptime() {
    $uptime = shell_exec('cat /proc/uptime');
    $uptime = explode(' ', $uptime);
    $uptime = round($uptime[0]);

    $days = floor($uptime / 86400);
    $hours = floor(($uptime % 86400) / 3600);
    $minutes = floor(($uptime % 3600) / 60);

    return [
        'uptime_formatted' => "$days days, $hours hours, $minutes minutes",
    ];
}

// Fungsi untuk mendapatkan storage
function getStorageInfo() {
    $storageData = shell_exec('df /data 2>/dev/null | tail -n1');
    $storageParts = preg_split('/\s+/', trim($storageData));

    $storageTotal = round($storageParts[1] / 1024 / 1024, 1);
    $storageUsed = round($storageParts[2] / 1024 / 1024, 1);
    $storageFree = round($storageParts[3] / 1024 / 1024, 1);
    $storagePercent = round(($storageUsed / $storageTotal) * 100);

    return [
        'storage_total' => $storageTotal,
        'storage_used' => $storageUsed,
        'storage_free' => $storageFree,
        'storage_percent' => $storagePercent,
    ];
}

// Fungsi untuk mendapatkan signal
function getSignalInfo() {
    $signalData = shell_exec('dumpsys telephony.registry');
    $signalDataArray = [];

    if (preg_match('/CellSignalStrengthLte:(.+?)(?=CellSignalStrength|$)/s', $signalData, $lteMatch)) {
        $lteData = $lteMatch[1];

        preg_match('/rssi=([-\d]+)/', $lteData, $rssi);
        preg_match('/rsrp=([-\d]+)/', $lteData, $rsrp);
        preg_match('/rsrq=([-\d]+)/', $lteData, $rsrq);
        preg_match('/rssnr=([-\d]+)/', $lteData, $rssnr);
        preg_match('/level=(\d)/', $lteData, $level);

        $signalDataArray = [
            'type' => 'LTE',
            'rssi' => $rssi[1] ?? 'N/A',
            'rsrp' => $rsrp[1] ?? 'N/A',
            'rsrq' => $rsrq[1] ?? 'N/A',
            'sinr' => $rssnr[1] ?? 'N/A',
            'level' => $level[1] ?? 0,
        ];
    }

    return $signalDataArray;
}

// Mendapatkan data
$cpuLoad = getCpuUsage();
$memoryInfo = getMemoryInfo();
$batteryInfo = getBatteryInfo();
$uptimeInfo = getUptime();
$storageInfo = getStorageInfo();
$signalInfo = getSignalInfo();

// // Mengembalikan data dalam format JSON
echo json_encode([
    'cpu_usage' => $cpuLoad,
    'mem_percent' => $memoryInfo['mem_percent'],
    'mem_used' => $memoryInfo['mem_used'],
    'mem_available' => $memoryInfo['mem_available'],
    'mem_total' => $memoryInfo['mem_total'],
    'swap_total' => $memoryInfo['swap_total'],
    'swap_used' => $memoryInfo['swap_used'],
    'battery_level' => $batteryInfo['battery_level'],
    'battery_status' => $batteryInfo['battery_status'],
    'battery_temp' => $batteryInfo['battery_temp'],
    'battery_voltage' => $batteryInfo['battery_voltage'],
    'battery_current' => $batteryInfo['battery_current'],
    'uptime_formatted' => $uptimeInfo['uptime_formatted'],
    'storage_total' => $storageInfo['storage_total'],
    'storage_used' => $storageInfo['storage_used'],
    'storage_free' => $storageInfo['storage_free'],
    'storage_percent' => $storageInfo['storage_percent'],
    'signal_data' => $signalInfo['signal_data'],
]);