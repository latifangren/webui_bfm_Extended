<?php
/**
 * NetLimiter (iptables) — View
 * Refactored from tools/net_limiter_control.php
 */
use BoxUI\Features\Network\NetworkService;

$devices = NetworkService::getConnectedDevices();
$leases = NetworkService::getDhcpLeases();
$blocked = NetworkService::getBlockedClients();
$limited = NetworkService::getLimitedClients();
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">🚫</span>
            <h1>NetLimiter</h1>
        </div>
    </div>

    <!-- Connected Devices -->
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;margin-bottom:15px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:15px;color:var(--accent,#FECA0A);margin-bottom:12px;">
            <i class="fas fa-network-wired"></i> Connected Devices
        </h3>
        <?php if (empty($devices)): ?>
        <p style="color:#888;font-size:13px;">No devices found in ARP table.</p>
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

    <!-- Blocked Clients -->
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;margin-bottom:15px;border:1px solid #d32f2f44;">
        <h3 style="margin-top:0;font-size:15px;color:#ef5350;margin-bottom:12px;">
            <i class="fas fa-ban"></i> Blocked Clients
        </h3>
        <?php if (empty($blocked)): ?>
        <p style="color:#888;font-size:13px;">No blocked clients.</p>
        <?php else: ?>
        <ul style="list-style:none;padding:0;">
            <?php foreach ($blocked as $b): ?>
            <li style="padding:6px 10px;background:rgba(211,47,47,0.1);border-radius:6px;margin-bottom:4px;font-size:13px;">
                <?= boxui_e($b) ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <!-- Limited Clients -->
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid #ff980044;">
        <h3 style="margin-top:0;font-size:15px;color:#ff9800;margin-bottom:12px;">
            <i class="fas fa-tachometer-alt"></i> Limited Clients
        </h3>
        <?php if (empty($limited)): ?>
        <p style="color:#888;font-size:13px;">No limited clients.</p>
        <?php else: ?>
        <ul style="list-style:none;padding:0;">
            <?php foreach ($limited as $l): ?>
            <li style="padding:6px 10px;background:rgba(255,152,0,0.1);border-radius:6px;margin-bottom:4px;font-size:13px;">
                <?= boxui_e($l) ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
