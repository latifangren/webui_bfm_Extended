<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * AdBlock Test — redirect to adblock.turtlecute.org
 */
use BoxUI\Features\Network\NetworkService;

$url = NetworkService::adBlockTestUrl();
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">🛡️</span>
            <h1>AdBlock Test</h1>
        </div>
    </div>

    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
        <p style="margin-bottom:15px;">Test apakah AdBlock berfungsi dengan benar:</p>
        <a href="<?= $url ?>" target="_blank" rel="noopener"
           style="display:inline-block;padding:12px 24px;background:var(--accent,#FECA0A);color:#000;border-radius:8px;text-decoration:none;font-weight:600;">
            Buka AdBlock Test →
        </a>
    </div>
</div>
