<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * Speed Test — iframe widget from openspeedtest.com
 */
use BoxUI\Features\Network\NetworkService;
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">⚡</span>
            <h1>Speed Test</h1>
        </div>
    </div>

    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
        <p style="margin-bottom:15px;font-size:13px;color:#aaa;">
            Speed test via OpenSpeedTest. Klik start untuk memulai.
        </p>
        <iframe src="<?= NetworkService::speedTestWidgetUrl() ?>"
                style="width:100%;height:500px;border:none;border-radius:8px;"
                allow="geolocation *"></iframe>
    </div>
</div>
