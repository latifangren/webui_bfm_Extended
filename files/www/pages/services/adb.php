<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * ADB Tools
 * Refactored from tools/adb_tools.php
 */
use BoxUI\Features\Services\ServicesService;

$devices = ServicesService::adbDevices();
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">📱</span>
            <h1>ADB Tools</h1>
        </div>
    </div>

    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;margin-bottom:15px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:15px;color:var(--accent,#FECA0A);margin-bottom:12px;">
            <i class="fas fa-list"></i> Connected Devices
        </h3>
        <?php if (empty($devices)): ?>
        <p style="color:#888;font-size:13px;">No ADB devices found. Connect a device first.</p>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead><tr style="background:var(--bg-primary,#0d0d0d);"><th style="padding:8px;text-align:left;color:#888;">ID</th><th style="padding:8px;text-align:left;color:#888;">Status</th></tr></thead>
            <tbody>
                <?php foreach ($devices as $d): ?>
                <tr style="border-bottom:1px solid #222;">
                    <td style="padding:8px;"><?= boxui_e($d['id']) ?></td>
                    <td style="padding:8px;">
                        <span style="padding:2px 8px;border-radius:4px;background:<?= $d['status'] === 'device' ? 'rgba(76,175,80,0.2)' : 'rgba(255,152,0,0.2)' ?>;color:<?= $d['status'] === 'device' ? '#4CAF50' : '#ff9800' ?>;">
                            <?= $d['status'] ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:15px;color:var(--accent,#FECA0A);margin-bottom:12px;">
            <i class="fas fa-cog"></i> Actions
        </h3>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <button onclick="adbAction('enable_tcpip')" style="padding:8px 16px;border:none;border-radius:6px;background:#4CAF50;color:#fff;cursor:pointer;font-size:12px;">Enable TCP/IP</button>
            <button onclick="adbAction('disable_tcpip')" style="padding:8px 16px;border:none;border-radius:6px;background:#ff9800;color:#fff;cursor:pointer;font-size:12px;">Disable TCP/IP</button>
            <button onclick="adbAction('restart_server')" style="padding:8px 16px;border:none;border-radius:6px;background:#2196F3;color:#fff;cursor:pointer;font-size:12px;">Restart Server</button>
        </div>
        <div id="adb-result" style="margin-top:12px;padding:12px;background:var(--bg-primary,#0d0d0d);border-radius:8px;font-size:12px;font-family:monospace;color:#ccc;min-height:20px;display:none;"></div>
    </div>

    <script>
    function adbAction(action) {
        const resultDiv = document.getElementById('adb-result');
        resultDiv.style.display = 'block';
        resultDiv.textContent = 'Executing ' + action + '...';

        fetch('/tools/services/adb_tools_handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=' + action
        })
        .then(r => r.text())
        .then(d => { resultDiv.textContent = d; })
        .catch(e => { resultDiv.textContent = 'Error: ' + e; });
    }
    </script>
</div>
