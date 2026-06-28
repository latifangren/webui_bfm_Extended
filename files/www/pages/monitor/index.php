<?php
/**
 * Monitor Overview — combines CPU, RAM, Battery, Storage
 * Refactored from webui/monitor/Overview.php
 */
use BoxUI\Features\Monitor\SystemMonitorService;

$cpu_usage = SystemMonitorService::getCpuUsage();
$cpu_info = SystemMonitorService::getCpuInfo();
$ram = SystemMonitorService::getRamInfo();
$battery = SystemMonitorService::getBatteryInfo();
$storage = SystemMonitorService::getStorageInfo();

$total_ram = isset($ram['MemTotal']) ? round($ram['MemTotal'] / 1024) : 0;
$free_ram = isset($ram['MemAvailable']) ? round($ram['MemAvailable'] / 1024) : 0;
$used_ram = $total_ram - $free_ram;
$ram_pct = $total_ram > 0 ? round(($used_ram / $total_ram) * 100) : 0;

$bat_pct = $battery['capacity'] ?? 0;
$bat_status = $battery['status'] ?? 'Unknown';
$bat_temp = isset($battery['temp']) ? round($battery['temp'] / 10, 1) : 'N/A';

$cpu_color = $cpu_usage > 85 ? '#ff5c5c' : ($cpu_usage > 55 ? '#fcba28' : '#14b6e5');
$ram_color = $ram_pct > 85 ? '#ff5c5c' : ($ram_pct > 55 ? '#fcba28' : '#14b6e5');
$bat_color = $bat_pct > 80 ? '#14b6e5' : ($bat_pct > 25 ? '#fcba28' : '#ff5c5c');
?>

<div class="mb-8 border-b-2 border-border pb-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center bg-primary border-2 border-border text-black font-bold">
            📊
        </div>
        <h1 class="font-head text-2xl uppercase tracking-wider text-primary">Overview</h1>
    </div>
    <span class="text-xs font-mono bg-border/20 text-[#aeaeae] px-3 py-1 border border-border/20">SYSTEM STATE</span>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <!-- CPU Bento Card -->
    <div class="border-2 border-border bg-[#1a1a1a] p-6 hover:bg-black/20 transition-colors flex flex-col justify-between h-44" style="box-shadow: 4px 4px 0px 0px <?= $cpu_color ?>;">
        <div class="flex items-center justify-between border-b border-[#f9f4da]/10 pb-2 mb-2">
            <span class="text-xs font-bold uppercase text-[#aeaeae] flex items-center gap-2">
                <i class="fas fa-microchip text-primary"></i> CPU LOAD
            </span>
            <span class="h-2 w-2 rounded-full" style="background-color: <?= $cpu_color ?>;"></span>
        </div>
        <div class="font-head text-4xl font-normal leading-none" style="color: <?= $cpu_color ?>;">
            <?= $cpu_usage ?>%
        </div>
        <div class="text-[11px] text-[#aeaeae] font-medium tracking-wide">
            <?= $cpu_info['cores'] ?? '?' ?> Cores &middot; <?= $cpu_info['gov'] ?? 'N/A' ?>
        </div>
    </div>

    <!-- RAM Bento Card -->
    <div class="border-2 border-border bg-[#1a1a1a] p-6 hover:bg-black/20 transition-colors flex flex-col justify-between h-44" style="box-shadow: 4px 4px 0px 0px <?= $ram_color ?>;">
        <div class="flex items-center justify-between border-b border-[#f9f4da]/10 pb-2 mb-2">
            <span class="text-xs font-bold uppercase text-[#aeaeae] flex items-center gap-2">
                <i class="fas fa-memory text-primary"></i> RAM STATE
            </span>
            <span class="h-2 w-2 rounded-full" style="background-color: <?= $ram_color ?>;"></span>
        </div>
        <div class="font-head text-4xl font-normal leading-none" style="color: <?= $ram_color ?>;">
            <?= $ram_pct ?>%
        </div>
        <div class="text-[11px] text-[#aeaeae] font-medium tracking-wide">
            <?= $used_ram ?>MB / <?= $total_ram ?>MB
        </div>
    </div>

    <!-- Battery Bento Card -->
    <div class="border-2 border-border bg-[#1a1a1a] p-6 hover:bg-black/20 transition-colors flex flex-col justify-between h-44" style="box-shadow: 4px 4px 0px 0px <?= $bat_color ?>;">
        <div class="flex items-center justify-between border-b border-[#f9f4da]/10 pb-2 mb-2">
            <span class="text-xs font-bold uppercase text-[#aeaeae] flex items-center gap-2">
                <i class="fas fa-battery-three-quarters text-primary"></i> BATTERY
            </span>
            <span class="h-2 w-2 rounded-full" style="background-color: <?= $bat_color ?>;"></span>
        </div>
        <div class="font-head text-4xl font-normal leading-none" style="color: <?= $bat_color ?>;">
            <?= $bat_pct ?>%
        </div>
        <div class="text-[11px] text-[#aeaeae] font-medium tracking-wide">
            <?= boxui_e($bat_status) ?> &middot; <?= $bat_temp ?>°C
        </div>
    </div>

    <!-- Storage Bento Card -->
    <div class="border-2 border-border bg-[#1a1a1a] p-6 hover:bg-black/20 transition-colors flex flex-col justify-between h-44" style="box-shadow: 4px 4px 0px 0px #14b6e5;">
        <?php
        $data_mount = null;
        foreach ($storage['mounts'] as $m) {
            if ($m['mounted'] === '/data') { $data_mount = $m; break; }
        }
        if ($data_mount):
            $st_pct = (int)$data_mount['use_pct'];
            $st_color = $st_pct > 85 ? '#ff5c5c' : ($st_pct > 55 ? '#fcba28' : '#14b6e5');
        ?>
        <div class="flex items-center justify-between border-b border-[#f9f4da]/10 pb-2 mb-2">
            <span class="text-xs font-bold uppercase text-[#aeaeae] flex items-center gap-2">
                <i class="fas fa-hdd text-primary"></i> STORAGE (/data)
            </span>
            <span class="h-2 w-2 rounded-full" style="background-color: <?= $st_color ?>;"></span>
        </div>
        <div class="font-head text-4xl font-normal leading-none" style="color: <?= $st_color ?>;">
            <?= $st_pct ?>%
        </div>
        <div class="text-[11px] text-[#aeaeae] font-medium tracking-wide">
            <?= $data_mount['used'] ?> / <?= $data_mount['size'] ?>
        </div>
        <?php else: ?>
        <div class="flex items-center justify-between border-b border-[#f9f4da]/10 pb-2 mb-2">
            <span class="text-xs font-bold uppercase text-[#aeaeae] flex items-center gap-2">
                <i class="fas fa-hdd text-primary"></i> STORAGE
            </span>
            <span class="h-2 w-2 rounded-full bg-border"></span>
        </div>
        <div class="font-head text-4xl font-normal leading-none text-[#aeaeae]">
            N/A
        </div>
        <div class="text-[11px] text-[#aeaeae] font-medium tracking-wide">
            Mount /data not found
        </div>
        <?php endif; ?>
    </div>
</div>