<?php
/**
 * System Logs
 * Refactored from tools/logs.php
 */
use BoxUI\Features\System\SystemService;

// Default log path
$log_path = '/cache/magisk.log';
$log_content = '';
$log_name = basename($log_path);

if (isset($_GET['path'])) {
    $requested = $_GET['path'];
    // Basic path traversal protection
    if (strpos($requested, '..') === false && strpos($requested, '/') === 0) {
        $log_path = $requested;
        $log_name = basename($log_path);
    }
}

if (isset($_GET['search']) && $_GET['search'] !== '') {
    $log_content = SystemService::searchLog($log_path, $_GET['search']);
    $log_name .= " (search: " . boxui_e($_GET['search']) . ")";
} else {
    $log_content = SystemService::readLog($log_path, 300);
}

if (isset($_POST['clear']) && $_POST['clear'] === '1') {
    SystemService::clearLog($log_path);
    echo '<meta http-equiv="refresh" content="0">';
    exit;
}

$log_files = SystemService::getLogFiles();
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">📋</span>
            <h1>System Logs</h1>
        </div>
    </div>

    <!-- Log File Selector -->
    <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
        <?php foreach ($log_files as $lf): ?>
        <a href="?path=<?= urlencode($lf['path']) ?>"
           style="padding:5px 12px;border-radius:6px;font-size:11px;background:<?= $log_path === $lf['path'] ? 'var(--accent,#FECA0A)' : 'var(--bg-secondary,#1a1a1a)' ?>;color:<?= $log_path === $lf['path'] ? '#000' : '#aaa' ?>;text-decoration:none;border:1px solid var(--border,#333);">
            <?= boxui_e($lf['name']) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Controls -->
    <div style="display:flex;gap:10px;margin-bottom:12px;flex-wrap:wrap;align-items:center;">
        <span style="font-size:13px;color:#888;"><?= boxui_e($log_name) ?></span>
        <form method="GET" style="display:flex;gap:6px;">
            <input type="hidden" name="path" value="<?= boxui_e($log_path) ?>">
            <input type="text" name="search" placeholder="Search..." style="padding:6px 10px;border-radius:6px;border:1px solid var(--border,#333);background:var(--bg-primary,#0d0d0d);color:#fff;font-size:12px;width:150px;">
            <button type="submit" style="padding:6px 12px;border:none;border-radius:6px;background:var(--accent,#FECA0A);color:#000;font-size:12px;cursor:pointer;">Cari</button>
        </form>
        <a href="?path=<?= urlencode($log_path) ?>" style="padding:6px 12px;border-radius:6px;background:var(--bg-secondary,#1a1a1a);color:#aaa;text-decoration:none;border:1px solid var(--border,#333);font-size:12px;">↻ Refresh</a>
        <form method="POST" style="display:inline;" onsubmit="return confirm('Clear this log?')">
            <input type="hidden" name="clear" value="1">
            <button type="submit" style="padding:6px 12px;border:none;border-radius:6px;background:#d32f2f;color:#fff;font-size:12px;cursor:pointer;">Clear</button>
        </form>
    </div>

    <!-- Log Content -->
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:0;border:1px solid var(--border,#333);overflow:hidden;">
        <pre style="margin:0;padding:15px;font-size:11px;overflow-x:auto;color:#ccc;line-height:1.5;max-height:70vh;white-space:pre-wrap;word-break:break-all;">
<?= boxui_e($log_content) ?: 'No log content.' ?>
        </pre>
    </div>
</div>
