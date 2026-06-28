<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * Hotspot — View
 * Refactored from tools/hotspot/hotspot.php
 */
use BoxUI\Features\Network\NetworkService;

$status = NetworkService::hotspotStatus();
$config = NetworkService::hotspotGetConfig();
$devices = NetworkService::getConnectedDevices();
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">📶</span>
            <h1>Hotspot</h1>
        </div>
    </div>

    <!-- Status -->
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;margin-bottom:15px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:15px;color:var(--accent,#FECA0A);margin-bottom:12px;">
            <i class="fas fa-info-circle"></i> Status
        </h3>
        <div style="display:flex;gap:20px;flex-wrap:wrap;">
            <div style="flex:1;min-width:120px;">
                <span style="font-size:12px;color:#888;">Hotspot</span>
                <div style="font-size:16px;font-weight:600;color:<?= $status['softap_on'] ? '#4CAF50' : '#888' ?>;">
                    <?= $status['softap_on'] ? 'AKTIF' : 'Nonaktif' ?>
                </div>
            </div>
            <div style="flex:1;min-width:120px;">
                <span style="font-size:12px;color:#888;">WiFi</span>
                <div style="font-size:16px;font-weight:600;color:<?= $status['wifi_on'] ? '#4CAF50' : '#888' ?>;">
                    <?= $status['wifi_on'] ? 'On' : 'Off' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Config -->
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;margin-bottom:15px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:15px;color:var(--accent,#FECA0A);margin-bottom:12px;">
            <i class="fas fa-cog"></i> Configuration
        </h3>
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <tr><td style="padding:6px 0;color:#888;width:100px;">SSID</td><td style="padding:6px 0;"><strong><?= boxui_e($config['ssid']) ?></strong></td></tr>
            <tr><td style="padding:6px 0;color:#888;">Password</td><td style="padding:6px 0;"><strong><?= boxui_e($config['password']) ?></strong></td></tr>
        </table>
    </div>

    <!-- Connected Devices -->
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:15px;color:var(--accent,#FECA0A);margin-bottom:12px;">
            <i class="fas fa-users"></i> Connected Devices
        </h3>
        <?php if (empty($devices)): ?>
        <p style="color:#888;font-size:13px;">No devices connected.</p>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead>
                <tr style="background:var(--bg-primary,#0d0d0d);">
                    <th style="padding:8px;text-align:left;color:#888;">IP</th>
                    <th style="padding:8px;text-align:left;color:#888;">MAC</th>
                    <th style="padding:8px;text-align:left;color:#888;">Interface</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($devices as $d): ?>
                <tr style="border-bottom:1px solid #222;">
                    <td style="padding:8px;"><?= boxui_e($d['ip']) ?></td>
                    <td style="padding:8px;font-family:monospace;"><?= boxui_e($d['mac']) ?></td>
                    <td style="padding:8px;"><?= boxui_e($d['interface']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
