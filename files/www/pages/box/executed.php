<?php
/**
 * BOX Execution (BFR Service Control)
 * Refactored from tools/bfr/executed.php
 */
use BoxUI\Features\Box\BoxService;

$status = BoxService::status();
$logs = BoxService::getLogs(50);
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">🚀</span>
            <h1>BOX Execution</h1>
        </div>
    </div>

    <!-- Status -->
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;margin-bottom:15px;border:1px solid var(--border,#333);">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
            <div>
                <h3 style="margin:0 0 4px;font-size:16px;">Service Status</h3>
                <span style="font-size:14px;padding:4px 12px;border-radius:8px;background:<?= $status['is_running'] ? 'rgba(76,175,80,0.2)' : 'rgba(255,68,68,0.2)' ?>;color:<?= $status['is_running'] ? '#4CAF50' : '#ff4444' ?>;">
                    <?= $status['is_running'] ? 'RUNNING' : 'STOPPED' ?>
                </span>
            </div>
            <div style="display:flex;gap:8px;">
                <button hx-post="/tools/box/box_exec_handler.php" hx-vals='{"action": "start"}' hx-target="#content" class="svc-btn" style="background:#4CAF50;">Start</button>
                <button hx-post="/tools/box/box_exec_handler.php" hx-vals='{"action": "stop"}' hx-target="#content" class="svc-btn" style="background:#f44336;">Stop</button>
                <button hx-post="/tools/box/box_exec_handler.php" hx-vals='{"action": "restart"}' hx-target="#content" class="svc-btn" style="background:#ff9800;">Restart</button>
            </div>
        </div>
    </div>

    <!-- Status Output -->
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;margin-bottom:15px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:14px;color:var(--accent,#FECA0A);margin-bottom:8px;">Status Detail</h3>
        <pre style="margin:0;padding:12px;background:var(--bg-primary,#0d0d0d);border-radius:8px;font-size:11px;color:#aaa;max-height:200px;overflow-y:auto;white-space:pre-wrap;"><?= boxui_e($status['raw']) ?></pre>
    </div>

    <!-- Logs -->
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:14px;color:var(--accent,#FECA0A);margin-bottom:8px;">
            <i class="fas fa-clipboard-list"></i> Service Logs (<?= count($logs) ?>)
        </h3>
        <div style="max-height:300px;overflow-y:auto;background:var(--bg-primary,#0d0d0d);border-radius:8px;padding:10px;">
            <?php foreach ($logs as $log): ?>
            <div style="padding:3px 0;font-size:11px;color:#888;font-family:monospace;border-bottom:1px solid #222;">
                <?= boxui_e($log) ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <style>
    .svc-btn { padding:8px 20px; border:none; border-radius:8px; color:#fff; font-weight:600; cursor:pointer; font-size:13px; }
    .svc-btn:disabled { opacity:0.5; }
    </style>
</div>
