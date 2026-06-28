<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * Power Manager
 * Refactored from tools/powermanager.php
 */
use BoxUI\Features\System\SystemService;

// Handle action via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $result = '';
    switch ($action) {
        case 'reboot': $result = SystemService::reboot(); break;
        case 'recovery': $result = SystemService::rebootRecovery(); break;
        case 'bootloader': $result = SystemService::rebootBootloader(); break;
        case 'fastboot': $result = SystemService::rebootFastboot(); break;
        case 'soft_reboot': $result = SystemService::softReboot(); break;
        case 'shutdown': $result = SystemService::shutdown(); break;
    }
    echo json_encode(['status' => 'executed', 'action' => $action]);
    exit;
}

$uptime = SystemService::uptimeFormatted();
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">⚡</span>
            <h1>Power Manager</h1>
        </div>
    </div>

    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;margin-bottom:15px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:15px;color:var(--accent,#FECA0A);margin-bottom:8px;">System Info</h3>
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <tr><td style="padding:5px 0;color:#888;width:120px;">Uptime</td><td style="padding:5px 0;"><?= $uptime ?></td></tr>
            <tr><td style="padding:5px 0;color:#888;">Kernel</td><td style="padding:5px 0;"><?= SystemService::kernelVersion() ?></td></tr>
            <tr><td style="padding:5px 0;color:#888;">Android</td><td style="padding:5px 0;"><?= SystemService::androidVersion() ?></td></tr>
            <tr><td style="padding:5px 0;color:#888;">Model</td><td style="padding:5px 0;"><?= SystemService::deviceModel() ?></td></tr>
        </table>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;">
        <button onclick="powerAction('reboot')" style="padding:16px;border:none;border-radius:12px;background:var(--bg-secondary,#1a1a1a);color:#fff;cursor:pointer;border:1px solid var(--border,#333);font-size:14px;transition:0.2s;" onmouseover="this.style.borderColor='#4CAF50'" onmouseout="this.style.borderColor='var(--border,#333)'">
            <div style="font-size:24px;margin-bottom:6px;">🔄</div>
            Reboot
        </button>
        <button onclick="powerAction('recovery')" style="padding:16px;border:none;border-radius:12px;background:var(--bg-secondary,#1a1a1a);color:#fff;cursor:pointer;border:1px solid var(--border,#333);font-size:14px;transition:0.2s;" onmouseover="this.style.borderColor='#ff9800'" onmouseout="this.style.borderColor='var(--border,#333)'">
            <div style="font-size:24px;margin-bottom:6px;">🔧</div>
            Recovery
        </button>
        <button onclick="powerAction('bootloader')" style="padding:16px;border:none;border-radius:12px;background:var(--bg-secondary,#1a1a1a);color:#fff;cursor:pointer;border:1px solid var(--border,#333);font-size:14px;transition:0.2s;" onmouseover="this.style.borderColor='#2196F3'" onmouseout="this.style.borderColor='var(--border,#333)'">
            <div style="font-size:24px;margin-bottom:6px;">🔵</div>
            Bootloader
        </button>
        <button onclick="powerAction('fastboot')" style="padding:16px;border:none;border-radius:12px;background:var(--bg-secondary,#1a1a1a);color:#fff;cursor:pointer;border:1px solid var(--border,#333);font-size:14px;transition:0.2s;" onmouseover="this.style.borderColor='#9C27B0'" onmouseout="this.style.borderColor='var(--border,#333)'">
            <div style="font-size:24px;margin-bottom:6px;">💜</div>
            Fastboot
        </button>
        <button onclick="powerAction('soft_reboot')" style="padding:16px;border:none;border-radius:12px;background:var(--bg-secondary,#1a1a1a);color:#fff;cursor:pointer;border:1px solid var(--border,#333);font-size:14px;transition:0.2s;" onmouseover="this.style.borderColor='#607D8B'" onmouseout="this.style.borderColor='var(--border,#333)'">
            <div style="font-size:24px;margin-bottom:6px;">♻️</div>
            Soft Reboot
        </button>
        <button onclick="powerAction('shutdown')" style="padding:16px;border:none;border-radius:12px;background:var(--bg-secondary,#1a1a1a);color:#fff;cursor:pointer;border:1px solid var(--border,#333);font-size:14px;transition:0.2s;" onmouseover="this.style.borderColor='#f44336'" onmouseout="this.style.borderColor='var(--border,#333)'">
            <div style="font-size:24px;margin-bottom:6px;">⛔</div>
            Shutdown
        </button>
    </div>

    <script>
    function powerAction(action) {
        const names = {reboot:'Reboot', recovery:'Recovery', bootloader:'Bootloader', fastboot:'Fastboot', soft_reboot:'Soft Reboot', shutdown:'Shutdown'};
        if (!confirm(`Konfirmasi ${names[action]}?`)) return;

        const btn = event.target.closest('button');
        btn.disabled = true;
        btn.style.opacity = '0.5';

        fetch('/pages/system/power.php', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: `action=${action}` })
            .then(r => r.json())
            .then(d => {
                alert(`${names[action]} executed!`);
            });
    }
    </script>
</div>
