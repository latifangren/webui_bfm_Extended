<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * BOX Execution (BFR Service Control)
 * Refactored from tools/bfr/executed.php
 */
use BoxUI\Features\Box\BoxService;

$status = BoxService::status();
$logs = BoxService::getLogs(50);
?>
<div class="container font-sans">
    <div class="mb-8 border-b-2 border-border pb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center bg-primary border-2 border-border text-black font-bold">
                🚀
            </div>
            <h1 class="font-head text-2xl uppercase tracking-wider text-primary">BOX Execution</h1>
        </div>
        <span class="text-xs font-mono bg-border/20 text-[#aeaeae] px-3 py-1 border border-border/20">SERVICE EXEC</span>
    </div>

    <!-- Status & Operations -->
    <div class="border-2 border-border bg-[#1a1a1a] p-6 mb-6 shadow-[4px_4px_0px_0px_rgba(249,244,218,1)]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
            <div>
                <h3 class="font-head text-xs uppercase tracking-widest text-[#aeaeae] mb-2">Service Status</h3>
                <?php if ($status['is_running']): ?>
                <span class="inline-block px-3 py-1 text-xs font-bold text-[#0ca95b] border-2 border-[#0ca95b] bg-[#0ca95b]/10 tracking-widest uppercase">
                    RUNNING
                </span>
                <?php else: ?>
                <span class="inline-block px-3 py-1 text-xs font-bold text-[#ff5c5c] border-2 border-[#ff5c5c] bg-[#ff5c5c]/10 tracking-widest uppercase">
                    STOPPED
                </span>
                <?php endif; ?>
            </div>
            
            <div class="flex flex-wrap gap-4">
                <button hx-post="/tools/box/box_exec_handler.php" hx-vals='{"action": "start"}' hx-target="#content" 
                        class="border-2 border-border bg-[#0ca95b] hover:bg-[#0ca95b]/80 text-white font-bold font-sans uppercase text-xs px-5 py-2 shadow-[2px_2px_0px_0px_rgba(249,244,218,1)] hover:translate-y-[-1px] active:translate-y-[1px] active:shadow-[0px_0px_0px_0px] transition-all cursor-pointer">
                    Start
                </button>
                <button hx-post="/tools/box/box_exec_handler.php" hx-vals='{"action": "stop"}' hx-target="#content" 
                        class="border-2 border-border bg-[#ff5c5c] hover:bg-[#ff5c5c]/80 text-white font-bold font-sans uppercase text-xs px-5 py-2 shadow-[2px_2px_0px_0px_rgba(249,244,218,1)] hover:translate-y-[-1px] active:translate-y-[1px] active:shadow-[0px_0px_0px_0px] transition-all cursor-pointer">
                    Stop
                </button>
                <button hx-post="/tools/box/box_exec_handler.php" hx-vals='{"action": "restart"}' hx-target="#content" 
                        class="border-2 border-border bg-[#fcba28] hover:bg-[#fcba28]/80 text-black font-bold font-sans uppercase text-xs px-5 py-2 shadow-[2px_2px_0px_0px_rgba(249,244,218,1)] hover:translate-y-[-1px] active:translate-y-[1px] active:shadow-[0px_0px_0px_0px] transition-all cursor-pointer">
                    Restart
                </button>
            </div>
        </div>
    </div>

    <!-- Status Output -->
    <div class="border-2 border-border bg-[#1a1a1a] p-6 mb-6 shadow-[4px_4px_0px_0px_rgba(249,244,218,1)]">
        <h3 class="font-head text-xs text-primary mb-3 uppercase tracking-widest">Status Detail</h3>
        <pre class="m-0 p-4 bg-black border border-[#f9f4da]/15 font-mono text-[11px] text-[#aeaeae] max-h-48 overflow-y-auto w-full whitespace-pre-wrap leading-relaxed"><?= boxui_e($status['raw']) ?></pre>
    </div>

    <!-- Logs -->
    <div class="border-2 border-border bg-[#1a1a1a] p-6 shadow-[4px_4px_0px_0px_rgba(249,244,218,1)]">
        <h3 class="font-head text-xs text-primary mb-3 uppercase tracking-widest flex items-center gap-2">
            <i class="fas fa-clipboard-list"></i> Service Logs (<?= count($logs) ?>)
        </h3>
        <div class="max-h-72 overflow-y-auto bg-black border border-[#f9f4da]/15 p-4 rounded-none h-60">
            <?php if (empty($logs)): ?>
                <div class="text-xs text-[#aeaeae]/60 font-mono py-4 text-center">No log output recorded yet.</div>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <div class="py-1 font-mono text-[10px] text-[#aeaeae] border-b border-[#f9f4da]/5 last:border-b-0 hover:text-[#fcba28]">
                    <?= boxui_e($log) ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>