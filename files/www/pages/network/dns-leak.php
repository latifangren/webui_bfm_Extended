<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * DNS Leak Test — redirect to dnscheck.tools
 */
use BoxUI\Features\Network\NetworkService;

$dns = NetworkService::dnsLeakDetect();
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">🔍</span>
            <h1>DNS Leak Test</h1>
        </div>
    </div>

    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;margin-bottom:15px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:16px;">DNS Servers Terdeteksi</h3>
        <?php if (!empty($dns['dns_servers'])): ?>
        <ul style="list-style:none;padding:0;">
            <?php foreach ($dns['dns_servers'] as $s): ?>
            <li style="padding:6px 10px;background:var(--bg-primary,#111);border-radius:6px;margin-bottom:5px;font-family:monospace;font-size:13px;">
                <?= boxui_e($s) ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p style="color:#888;">Tidak bisa mendeteksi DNS servers.</p>
        <?php endif; ?>
    </div>

    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
        <p style="margin-bottom:15px;">Buka dnscheck.tools untuk test lengkap:</p>
        <a href="<?= $dns['test_url'] ?>" target="_blank" rel="noopener"
           style="display:inline-block;padding:12px 24px;background:var(--accent,#FECA0A);color:#000;border-radius:8px;text-decoration:none;font-weight:600;">
            Buka DNS Check Tools →
        </a>
    </div>
</div>
