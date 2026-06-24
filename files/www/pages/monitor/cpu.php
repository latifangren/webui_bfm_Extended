<?php
/**
 * CPU Monitor
 * Refactored from webui/monitor/CpuMonitor.php
 */
use BoxUI\Features\Monitor\SystemMonitorService;

$usage = SystemMonitorService::getCpuUsage();
$info = SystemMonitorService::getCpuInfo();
$processes = SystemMonitorService::getProcesses('%cpu', 15);

// Handle kill action
if (isset($_GET['kill'])) {
    $pid = (int)$_GET['kill'];
    $result = SystemMonitorService::killProcess($pid);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'output' => $result]);
    exit;
}
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">🔧</span>
            <h1>CPU Monitor</h1>
        </div>
    </div>

    <!-- CPU Info -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:15px;">
        <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:16px;border:1px solid var(--border,#333);text-align:center;">
            <div style="font-size:32px;font-weight:700;color:<?= $usage > 80 ? '#ff4444' : ($usage > 50 ? '#ff9800' : '#4CAF50') ?>;"><?= $usage ?>%</div>
            <div style="font-size:12px;color:#888;margin-top:4px;">Usage</div>
        </div>
        <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:16px;border:1px solid var(--border,#333);text-align:center;">
            <div style="font-size:22px;font-weight:600;color:#ddd;"><?= $info['cores'] ?? '?' ?></div>
            <div style="font-size:12px;color:#888;margin-top:4px;">Cores</div>
        </div>
        <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:16px;border:1px solid var(--border,#333);text-align:center;">
            <div style="font-size:16px;font-weight:600;color:#ddd;"><?= boxui_e($info['gov'] ?? 'N/A') ?></div>
            <div style="font-size:12px;color:#888;margin-top:4px;">Governor</div>
        </div>
        <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:16px;border:1px solid var(--border,#333);text-align:center;">
            <div style="font-size:14px;font-weight:600;color:#ddd;"><?= boxui_e($info['model'] ?? 'N/A') ?></div>
            <div style="font-size:12px;color:#888;margin-top:4px;">Model</div>
        </div>
    </div>

    <!-- Per-core Frequencies -->
    <?php if (!empty($info['freqs'])): ?>
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;margin-bottom:15px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:15px;color:var(--accent,#FECA0A);margin-bottom:12px;">
            <i class="fas fa-tachometer-alt"></i> Per-Core Frequency
        </h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:8px;">
            <?php foreach ($info['freqs'] as $i => $f): ?>
            <div style="background:var(--bg-primary,#0d0d0d);padding:10px;border-radius:8px;text-align:center;">
                <div style="font-size:11px;color:#888;">Core <?= $i ?></div>
                <div style="font-size:15px;font-weight:600;color:#ddd;"><?= boxui_e($f) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Processes -->
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:15px;color:var(--accent,#FECA0A);margin-bottom:12px;">
            <i class="fas fa-list"></i> Top Processes (CPU)
        </h3>
        <?php if (empty($processes)): ?>
        <p style="color:#888;font-size:13px;">No process data.</p>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead>
                <tr style="background:var(--bg-primary,#0d0d0d);">
                    <th style="padding:6px;text-align:left;color:#888;">PID</th>
                    <th style="padding:6px;text-align:left;color:#888;">User</th>
                    <th style="padding:6px;text-align:right;color:#888;">CPU%</th>
                    <th style="padding:6px;text-align:right;color:#888;">MEM%</th>
                    <th style="padding:6px;text-align:left;color:#888;">Command</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($processes as $p): ?>
                <tr style="border-bottom:1px solid #222;">
                    <td style="padding:6px;"><?= $p['pid'] ?></td>
                    <td style="padding:6px;color:#888;"><?= boxui_e($p['user']) ?></td>
                    <td style="padding:6px;text-align:right;color:#fff;"><?= $p['cpu'] ?>%</td>
                    <td style="padding:6px;text-align:right;color:#fff;"><?= $p['mem'] ?>%</td>
                    <td style="padding:6px;font-size:11px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= boxui_e($p['command']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
