<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * Bandwidth Monitor (vnStat) — View
 * Refactored from tools/vnstat.php
 */
use BoxUI\Features\Network\NetworkService;

$available = NetworkService::vnstatAvailable();
$daily = $available ? NetworkService::vnstatDaily() : '';
$monthly = $available ? NetworkService::vnstatMonthly() : '';
$top = $available ? NetworkService::vnstatTop() : '';
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">📊</span>
            <h1>Bandwidth Monitor</h1>
        </div>
    </div>

    <?php if (!$available): ?>
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);text-align:center;">
        <p style="color:#ff9800;font-size:15px;margin-bottom:15px;">vnStat tidak terinstall</p>
        <p style="font-size:13px;color:#888;margin-bottom:20px;">Install vnStat via Termux:<br><code style="background:#111;padding:4px 8px;border-radius:4px;">pkg install vnstat</code></p>
        <form hx-post="/tools/network/bandwidth_handler.php" hx-target="#content">
            <button type="submit" name="start_vnstat"
                    style="padding:10px 20px;border:none;border-radius:8px;background:var(--accent,#FECA0A);color:#000;font-weight:600;cursor:pointer;">
                Start vnStat Service
            </button>
        </form>
    </div>
    <?php else: ?>
    <div style="display:flex;gap:10px;margin-bottom:15px;flex-wrap:wrap;">
        <form hx-post="/tools/network/bandwidth_handler.php" hx-target="#content" style="display:inline;" onsubmit="return confirm('Reset all vnStat data?')">
            <button type="submit" name="reset_vnstat"
                    style="padding:8px 16px;border:none;border-radius:6px;background:#d32f2f;color:#fff;font-size:12px;cursor:pointer;">
                Reset Data
            </button>
        </form>
    </div>

    <!-- Daily -->
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;margin-bottom:15px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:15px;color:var(--accent,#FECA0A);margin-bottom:12px;">
            <i class="fas fa-calendar-day"></i> Daily Traffic
        </h3>
        <pre style="background:var(--bg-primary,#0d0d0d);padding:15px;border-radius:8px;font-size:12px;overflow-x:auto;color:#ccc;line-height:1.6;margin:0;white-space:pre-wrap;"><?= boxui_e($daily) ?></pre>
    </div>

    <!-- Monthly -->
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;margin-bottom:15px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:15px;color:var(--accent,#FECA0A);margin-bottom:12px;">
            <i class="fas fa-calendar-alt"></i> Monthly Traffic
        </h3>
        <pre style="background:var(--bg-primary,#0d0d0d);padding:15px;border-radius:8px;font-size:12px;overflow-x:auto;color:#ccc;line-height:1.6;margin:0;white-space:pre-wrap;"><?= boxui_e($monthly) ?></pre>
    </div>

    <!-- Top 10 -->
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
        <h3 style="margin-top:0;font-size:15px;color:var(--accent,#FECA0A);margin-bottom:12px;">
            <i class="fas fa-list"></i> Top 10 Days
        </h3>
        <pre style="background:var(--bg-primary,#0d0d0d);padding:15px;border-radius:8px;font-size:12px;overflow-x:auto;color:#ccc;line-height:1.6;margin:0;white-space:pre-wrap;"><?= boxui_e($top) ?></pre>
    </div>
    <?php endif; ?>
</div>
