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
    if (isset($_SERVER['HTTP_HX_REQUEST'])) {
        header('HX-Location: /pages/system/logs.php?path=' . urlencode($log_path));
        exit;
    }
    echo '<meta http-equiv="refresh" content="0">';
    exit;
}

$log_files = SystemService::getLogFiles();
?>
<div class="container font-sans">
    <div class="mb-8 border-b-2 border-border pb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center bg-primary border-2 border-border text-black font-bold">
                📋
            </div>
            <h1 class="font-head text-2xl uppercase tracking-wider text-primary">System Logs</h1>
        </div>
        <span class="text-xs font-mono bg-border/20 text-[#aeaeae] px-3 py-1 border border-border/20">LOG VIEWER</span>
    </div>

    <!-- Log File Selector -->
    <div class="flex gap-3 mb-6 flex-wrap">
        <?php foreach ($log_files as $lf): ?>
        <a href="#" hx-get="/pages/system/logs.php?path=<?= urlencode($lf['path']) ?>" hx-target="#content"
           class="border-2 border-border font-mono text-[10px] px-3.5 py-1.5 font-bold uppercase transition-all shadow-[2px_2px_0px_0px_rgba(249,244,218,1)] hover:bg-[#fcba28] hover:text-black <?= $log_path === $lf['path'] ? 'bg-[#fcba28] text-black shadow-[2px_2px_0px_0px_rgba(249,244,218,1)]' : 'bg-black text-[#f9f4da]' ?>">
            <?= boxui_e($lf['name']) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Controls -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 border-b border-[#f9f4da]/15 pb-4">
        <div class="flex items-center gap-3">
            <span class="text-xs font-mono text-[#fcba28] font-bold bg-[#fcba28]/10 px-2 py-0.5 border border-[#fcba28]/25"><?= boxui_e($log_name) ?></span>
        </div>
        
        <div class="flex flex-wrap items-center gap-3">
            <!-- Search Form -->
            <form hx-get="/pages/system/logs.php" hx-target="#content" class="flex gap-2">
                <input type="hidden" name="path" value="<?= boxui_e($log_path) ?>">
                <input type="text" name="search" placeholder="Search logs..." 
                       class="px-3 py-1.5 border-2 border-border bg-black text-[#f9f4da] font-mono text-xs focus:outline-none focus:border-primary w-40">
                <button type="submit" 
                        class="border-2 border-border bg-[#fcba28] text-black font-bold uppercase text-xs px-4 py-1.5 shadow-[2px_2px_0px_0px_rgba(249,244,218,1)] active:translate-y-[1px] active:shadow-[0px_0px_0px_0px] transition-all cursor-pointer">
                    Cari
                </button>
            </form>

            <!-- Action buttons -->
            <a href="#" hx-get="/pages/system/logs.php?path=<?= urlencode($log_path) ?>" hx-target="#content" 
               class="border-2 border-border bg-[#1a1a1a] text-[#f9f4da] font-bold uppercase text-xs px-4 py-1.5 shadow-[2px_2px_0px_0px_rgba(249,244,218,1)] active:translate-y-[1px] active:shadow-[0px_0px_0px_0px] transition-all text-center">
                Refresh
            </a>
            
            <form hx-post="/pages/system/logs.php" hx-target="#content" class="inline" onsubmit="return confirm('Clear this log file?')">
                <input type="hidden" name="path" value="<?= boxui_e($log_path) ?>">
                <input type="hidden" name="clear" value="1">
                <button type="submit" 
                        class="border-2 border-[#ff5c5c] bg-red-950/20 text-[#ff5c5c] font-bold uppercase text-xs px-4 py-1.5 hover:bg-[#ff5c5c] hover:text-white transition-colors cursor-pointer">
                    Clear
                </button>
            </form>
        </div>
    </div>

    <!-- Log Content Box -->
    <div class="border-2 border-border bg-black shadow-[4px_4px_0px_0px_rgba(249,244,218,1)]">
        <pre class="m-0 p-4 font-mono text-xs text-[#aeaeae] select-text h-[55vh] overflow-y-auto w-full whitespace-pre-wrap leading-relaxed"><?= boxui_e($log_content) ?: 'No log content recorded.' ?></pre>
    </div>
</div>