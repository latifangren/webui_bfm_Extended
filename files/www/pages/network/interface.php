<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * Interface Manager — View
 * Refactored from tools/opsi_interface.php
 */
use BoxUI\Features\Network\NetworkService;

$interfaces = NetworkService::getInterfaces();
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">🌐</span>
            <h1>Interface Manager</h1>
        </div>
    </div>

    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:15px;color:var(--accent,#FECA0A);margin-bottom:15px;">
            Network Interfaces
        </h3>

        <?php if (empty($interfaces)): ?>
        <p style="color:#888;">Tidak ada interface terdeteksi.</p>
        <?php else: ?>
        <?php foreach ($interfaces as $if): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:var(--bg-primary,#0d0d0d);border-radius:8px;margin-bottom:8px;border-left:3px solid <?= $if['state'] === 'UP' ? '#4CAF50' : '#666' ?>;">
            <div>
                <strong style="font-size:14px;color:#eee;"><?= boxui_e($if['name']) ?></strong>
                <span style="font-size:11px;color:#888;margin-left:8px;"><?= boxui_e($if['ip']) ?></span>
            </div>
            <div>
                <span style="font-size:11px;padding:2px 8px;border-radius:4px;background:<?= $if['state'] === 'UP' ? 'rgba(76,175,80,0.2)' : 'rgba(102,102,102,0.2)' ?>;color:<?= $if['state'] === 'UP' ? '#4CAF50' : '#888' ?>;">
                    <?= boxui_e($if['state']) ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
