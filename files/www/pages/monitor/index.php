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
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">📊</span>
            <h1>Overview</h1>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
        <!-- CPU -->
        <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
            <div style="font-size:13px;color:#888;margin-bottom:8px;">
                <i class="fas fa-microchip"></i> CPU
            </div>
            <div style="font-size:36px;font-weight:700;color:<?= $cpu_usage > 80 ? '#ff4444' : ($cpu_usage > 50 ? '#ff9800' : '#4CAF50') ?>;">
                <?= $cpu_usage ?>%
            </div>
            <div style="font-size:11px;color:#666;margin-top:4px;">
                <?= $cpu_info['cores'] ?? '?' ?> Cores &middot; <?= $cpu_info['gov'] ?? 'N/A' ?>
            </div>
        </div>

        <!-- RAM -->
        <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
            <div style="font-size:13px;color:#888;margin-bottom:8px;">
                <i class="fas fa-memory"></i> RAM
            </div>
            <div style="font-size:36px;font-weight:700;color:<?= $ram_pct > 80 ? '#ff4444' : ($ram_pct > 50 ? '#ff9800' : '#4CAF50') ?>;">
                <?= $ram_pct ?>%
            </div>
            <div style="font-size:11px;color:#666;margin-top:4px;">
                <?= $used_ram ?>MB / <?= $total_ram ?>MB
            </div>
        </div>

        <!-- Battery -->
        <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
            <div style="font-size:13px;color:#888;margin-bottom:8px;">
                <i class="fas fa-battery-three-quarters"></i> Battery
            </div>
            <div style="font-size:36px;font-weight:700;color:<?= $bat_pct > 20 ? '#4CAF50' : '#ff4444' ?>;">
                <?= $bat_pct ?>%
            </div>
            <div style="font-size:11px;color:#666;margin-top:4px;">
                <?= boxui_e($bat_status) ?> &middot; <?= $bat_temp ?>°C
            </div>
        </div>

        <!-- Storage -->
        <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
            <div style="font-size:13px;color:#888;margin-bottom:8px;">
                <i class="fas fa-hdd"></i> Storage
            </div>
            <?php
            $data_mount = null;
            foreach ($storage['mounts'] as $m) {
                if ($m['mounted'] === '/data') { $data_mount = $m; break; }
            }
            if ($data_mount):
                $st_pct = (int)$data_mount['use_pct'];
            ?>
            <div style="font-size:36px;font-weight:700;color:<?= $st_pct > 80 ? '#ff4444' : ($st_pct > 50 ? '#ff9800' : '#4CAF50') ?>;">
                <?= $st_pct ?>%
            </div>
            <div style="font-size:11px;color:#666;margin-top:4px;">
                <?= $data_mount['used'] ?> / <?= $data_mount['size'] ?>
            </div>
            <?php else: ?>
            <div style="font-size:36px;font-weight:700;color:#888;">N/A</div>
            <?php endif; ?>
        </div>
    </div>
</div>
