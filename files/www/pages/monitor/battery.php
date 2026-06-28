<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * Battery Monitor
 * Refactored from webui/monitor/BatteryMonitor.php
 */
use BoxUI\Features\Monitor\SystemMonitorService;

$battery = SystemMonitorService::getBatteryInfo();

$pct = $battery['capacity'] ?? 0;
$status = $battery['status'] ?? 'Unknown';
$health = $battery['health'] ?? 'Unknown';
$tech = $battery['technology'] ?? 'Unknown';

$voltage = isset($battery['voltage_now']) ? round($battery['voltage_now'] / 1000, 0) : 0;
$current = isset($battery['current_now']) ? round($battery['current_now'] / 1000, 0) : 0;
$temp = isset($battery['temp']) ? round($battery['temp'] / 10, 1) : 'N/A';
$charge_full = isset($battery['charge_full']) ? round($battery['charge_full'] / 1000, 0) : 0;
$charge_now = isset($battery['charge_counter']) ? round($battery['charge_counter'] / 1000, 0) : 0;
$charge_design = isset($battery['charge_full_design']) ? round($battery['charge_full_design'] / 1000, 0) : 0;
$health_pct = $charge_design > 0 ? round(($charge_full / $charge_design) * 100) : 100;
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">🔋</span>
            <h1>Battery Monitor</h1>
        </div>
    </div>

    <!-- Quick Stats -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;margin-bottom:15px;">
        <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:16px;border:1px solid var(--border,#333);text-align:center;">
            <div style="font-size:36px;font-weight:700;color:<?= $pct > 20 ? '#4CAF50' : '#ff4444' ?>;"><?= $pct ?>%</div>
            <div style="font-size:12px;color:#888;margin-top:4px;">Capacity</div>
        </div>
        <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:16px;border:1px solid var(--border,#333);text-align:center;">
            <div style="font-size:18px;font-weight:600;color:#ddd;"><?= boxui_e($status) ?></div>
            <div style="font-size:12px;color:#888;margin-top:4px;">Status</div>
        </div>
        <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:16px;border:1px solid var(--border,#333);text-align:center;">
            <div style="font-size:18px;font-weight:600;color:#ddd;"><?= $temp ?>°C</div>
            <div style="font-size:12px;color:#888;margin-top:4px;">Temperature</div>
        </div>
        <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:16px;border:1px solid var(--border,#333);text-align:center;">
            <div style="font-size:18px;font-weight:600;color:#ddd;"><?= $health_pct ?>%</div>
            <div style="font-size:12px;color:#888;margin-top:4px;">Health</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <!-- Details -->
        <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
            <h3 style="margin-top:0;font-size:14px;color:var(--accent,#FECA0A);margin-bottom:12px;">Details</h3>
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <tr><td style="padding:5px 0;color:#888;">Health</td><td style="padding:5px 0;text-align:right;"><?= boxui_e($health) ?></td></tr>
                <tr><td style="padding:5px 0;color:#888;">Technology</td><td style="padding:5px 0;text-align:right;"><?= boxui_e($tech) ?></td></tr>
                <tr><td style="padding:5px 0;color:#888;">Voltage</td><td style="padding:5px 0;text-align:right;"><?= $voltage ?> mV</td></tr>
                <tr><td style="padding:5px 0;color:#888;">Current</td><td style="padding:5px 0;text-align:right;"><?= $current ?> mA</td></tr>
            </table>
        </div>

        <!-- Charge -->
        <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
            <h3 style="margin-top:0;font-size:14px;color:var(--accent,#FECA0A);margin-bottom:12px;">Charge</h3>
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <tr><td style="padding:5px 0;color:#888;">Full</td><td style="padding:5px 0;text-align:right;"><?= $charge_full ?> mAh</td></tr>
                <tr><td style="padding:5px 0;color:#888;">Current</td><td style="padding:5px 0;text-align:right;"><?= $charge_now ?> mAh</td></tr>
                <tr><td style="padding:5px 0;color:#888;">Design</td><td style="padding:5px 0;text-align:right;"><?= $charge_design ?> mAh</td></tr>
            </table>
        </div>
    </div>

    <!-- Thermals -->
    <?php if (!empty($battery['thermals'])): ?>
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;margin-top:12px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:14px;color:var(--accent,#FECA0A);margin-bottom:12px;">
            <i class="fas fa-thermometer-half"></i> Thermal Zones
        </h3>
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead>
                <tr style="background:var(--bg-primary,#0d0d0d);">
                    <th style="padding:6px;text-align:left;color:#888;">Zone</th>
                    <th style="padding:6px;text-align:right;color:#888;">Temp (°C)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($battery['thermals'] as $t): ?>
                <tr style="border-bottom:1px solid #222;">
                    <td style="padding:6px;"><?= boxui_e($t['type']) ?></td>
                    <td style="padding:6px;text-align:right;color:<?= $t['temp'] > 60 ? '#ff4444' : ($t['temp'] > 45 ? '#ff9800' : '#4CAF50') ?>;">
                        <?= $t['temp'] ?>°C
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
