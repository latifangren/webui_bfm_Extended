<?php
/**
 * Storage Monitor
 * Refactored from webui/monitor/StorageMonitor.php
 */
use BoxUI\Features\Monitor\SystemMonitorService;

$storage = SystemMonitorService::getStorageInfo();
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">💾</span>
            <h1>Storage Monitor</h1>
        </div>
    </div>

    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;margin-bottom:15px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:15px;color:var(--accent,#FECA0A);margin-bottom:12px;">
            <i class="fas fa-hdd"></i> Disk Usage
        </h3>
        <?php if (empty($storage['mounts'])): ?>
        <p style="color:#888;">No mount data.</p>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead>
                <tr style="background:var(--bg-primary,#0d0d0d);">
                    <th style="padding:8px;text-align:left;color:#888;">Filesystem</th>
                    <th style="padding:8px;text-align:right;color:#888;">Size</th>
                    <th style="padding:8px;text-align:right;color:#888;">Used</th>
                    <th style="padding:8px;text-align:right;color:#888;">Avail</th>
                    <th style="padding:8px;text-align:center;color:#888;">Use%</th>
                    <th style="padding:8px;text-align:left;color:#888;">Mounted</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($storage['mounts'] as $m): ?>
                <?php $pct = (int)$m['use_pct']; ?>
                <tr style="border-bottom:1px solid #222;">
                    <td style="padding:8px;font-family:monospace;font-size:11px;"><?= boxui_e($m['filesystem']) ?></td>
                    <td style="padding:8px;text-align:right;"><?= $m['size'] ?></td>
                    <td style="padding:8px;text-align:right;"><?= $m['used'] ?></td>
                    <td style="padding:8px;text-align:right;"><?= $m['avail'] ?></td>
                    <td style="padding:8px;text-align:center;">
                        <span style="padding:2px 8px;border-radius:4px;background:<?= $pct > 80 ? 'rgba(255,68,68,0.2)' : ($pct > 50 ? 'rgba(255,152,0,0.2)' : 'rgba(76,175,80,0.2)') ?>;color:<?= $pct > 80 ? '#ff4444' : ($pct > 50 ? '#ff9800' : '#4CAF50') ?>;">
                            <?= $m['use_pct'] ?>
                        </span>
                    </td>
                    <td style="padding:8px;"><?= boxui_e($m['mounted']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Mount Points -->
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:15px;color:var(--accent,#FECA0A);margin-bottom:12px;">
            <i class="fas fa-sitemap"></i> Mount Points
        </h3>
        <?php if (empty($storage['mount_info'])): ?>
        <p style="color:#888;">No mount info.</p>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead>
                <tr style="background:var(--bg-primary,#0d0d0d);">
                    <th style="padding:6px;text-align:left;color:#888;">Device</th>
                    <th style="padding:6px;text-align:left;color:#888;">Mount</th>
                    <th style="padding:6px;text-align:left;color:#888;">Type</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($storage['mount_info'] as $m): ?>
                <tr style="border-bottom:1px solid #222;">
                    <td style="padding:6px;font-family:monospace;font-size:11px;"><?= boxui_e($m['device']) ?></td>
                    <td style="padding:6px;"><?= boxui_e($m['mount']) ?></td>
                    <td style="padding:6px;"><?= boxui_e($m['fstype']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
