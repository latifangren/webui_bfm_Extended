<?php
/**
 * SMS Viewer
 * Refactored from tools/smsviewer.php
 */
use BoxUI\Features\Services\ServicesService;

$messages = ServicesService::getSmsMessages(50);
$stats = ServicesService::smsStats();
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">💬</span>
            <h1>SMS Viewer</h1>
        </div>
    </div>

    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;margin-bottom:15px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:15px;color:var(--accent,#FECA0A);margin-bottom:8px;">Status</h3>
        <p style="font-size:13px;color:#888;">Service: <strong style="color:#4CAF50;"><?= $stats['binary'] ?></strong></p>
    </div>

    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:15px;color:var(--accent,#FECA0A);margin-bottom:12px;">
            <i class="fas fa-envelope"></i> Recent Messages (<?= count($messages) ?>)
        </h3>
        <?php if (empty($messages)): ?>
        <p style="color:#888;font-size:13px;">No messages found. Install the sms binary in Termux.</p>
        <?php else: ?>
        <?php foreach ($messages as $msg): ?>
        <div style="padding:10px;margin-bottom:6px;background:var(--bg-primary,#0d0d0d);border-radius:8px;font-size:12px;border-left:3px solid var(--accent,#FECA0A);">
            <?php if (isset($msg['address'])): ?>
            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <strong style="color:#ddd;"><?= boxui_e($msg['address']) ?></strong>
                <span style="color:#888;"><?= isset($msg['date']) ? boxui_e($msg['date']) : '' ?></span>
            </div>
            <div style="color:#aaa;"><?= boxui_e($msg['body'] ?? '') ?></div>
            <?php else: ?>
            <div style="color:#ccc;"><?= boxui_e($msg['raw'] ?? '') ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
