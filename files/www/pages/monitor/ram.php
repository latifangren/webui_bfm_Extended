<?php
/**
 * RAM Monitor
 * Refactored from webui/monitor/RamMonitor.php
 */
use BoxUI\Features\Monitor\SystemMonitorService;

$ram = SystemMonitorService::getRamInfo();
$swap = SystemMonitorService::getSwapInfo();

$total_mb = isset($ram['MemTotal']) ? round($ram['MemTotal'] / 1024) : 0;
$free_mb = isset($ram['MemAvailable']) ? round($ram['MemAvailable'] / 1024) : 0;
$used_mb = $total_mb - $free_mb;
$pct = $total_mb > 0 ? round(($used_mb / $total_mb) * 100) : 0;
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">🧠</span>
            <h1>RAM Monitor</h1>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:15px;">
        <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:16px;border:1px solid var(--border,#333);text-align:center;">
            <div style="font-size:32px;font-weight:700;color:<?= $pct > 80 ? '#ff4444' : ($pct > 50 ? '#ff9800' : '#4CAF50') ?>;"><?= $pct ?>%</div>
            <div style="font-size:12px;color:#888;margin-top:4px;">Used</div>
        </div>
        <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:16px;border:1px solid var(--border,#333);text-align:center;">
            <div style="font-size:22px;font-weight:600;color:#ddd;"><?= $used_mb ?> MB</div>
            <div style="font-size:12px;color:#888;margin-top:4px;">Used</div>
        </div>
        <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:16px;border:1px solid var(--border,#333);text-align:center;">
            <div style="font-size:22px;font-weight:600;color:#ddd;"><?= $free_mb ?> MB</div>
            <div style="font-size:12px;color:#888;margin-top:4px;">Available</div>
        </div>
        <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:16px;border:1px solid var(--border,#333);text-align:center;">
            <div style="font-size:22px;font-weight:600;color:#ddd;"><?= $total_mb ?> MB</div>
            <div style="font-size:12px;color:#888;margin-top:4px;">Total</div>
        </div>
    </div>

    <!-- Memory Details -->
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;margin-bottom:15px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:15px;color:var(--accent,#FECA0A);margin-bottom:12px;">
            <i class="fas fa-chart-bar"></i> Memory Details
        </h3>
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <?php foreach (['MemTotal','MemFree','MemAvailable','Buffers','Cached','SwapTotal','SwapFree','Active','Inactive','Dirty','Writeback'] as $key): ?>
            <?php if (isset($ram[$key])): ?>
            <tr style="border-bottom:1px solid #222;">
                <td style="padding:6px 8px;color:#888;"><?= $key ?></td>
                <td style="padding:6px 8px;text-align:right;font-family:monospace;"><?= number_format($ram[$key]) ?> kB</td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Swap -->
    <?php if (!empty($swap)): ?>
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:15px;color:var(--accent,#FECA0A);margin-bottom:12px;">
            <i class="fas fa-swap"></i> Swap
        </h3>
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead>
                <tr style="background:var(--bg-primary,#0d0d0d);">
                    <th style="padding:6px;text-align:left;color:#888;">File</th>
                    <th style="padding:6px;text-align:right;color:#888;">Size</th>
                    <th style="padding:6px;text-align:right;color:#888;">Used</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($swap as $s): ?>
                <tr style="border-bottom:1px solid #222;">
                    <td style="padding:6px;"><?= boxui_e($s['file']) ?></td>
                    <td style="padding:6px;text-align:right;"><?= number_format($s['size']) ?></td>
                    <td style="padding:6px;text-align:right;"><?= number_format($s['used']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
