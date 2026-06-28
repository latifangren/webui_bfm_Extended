<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * RAM Monitor
 * Refactored from webui/monitor/RamMonitor.php
 */
use BoxUI\Features\Monitor\SystemMonitorService;

$ram = SystemMonitorService::getRamInfo();
$swap = SystemMonitorService::getSwapInfo();

$total_mb = isset($ram['MemTotal']) ? round($ram['MemTotal'] / 1024) : 0;
$free_mb = isset($ram['MemAvailable']) ? round($ram['MemAvailable'] / 1024) : 0;
$used_mb = $total_mb - $free_mb;
$pct = $total_mb > 0 ? round(($used_mb / $total_mb) * 100) : 0;

$ram_color = $pct > 85 ? '#ff5c5c' : ($pct > 55 ? '#fcba28' : '#14b6e5');
?>

<div class="mb-8 border-b-2 border-border pb-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center bg-primary border-2 border-border text-black font-bold">
            🧠
        </div>
        <h1 class="font-head text-2xl uppercase tracking-wider text-primary">RAM Monitor</h1>
    </div>
    <span class="text-xs font-mono bg-border/20 text-[#aeaeae] px-3 py-1 border border-border/20">MEMORY</span>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="border-2 border-border bg-[#1a1a1a] p-4 text-center hover:bg-black/20 transition-colors" style="box-shadow: 4px 4px 0px 0px <?= $ram_color ?>;">
        <div class="font-head text-3xl font-bold" style="color: <?= $ram_color ?>;"><?= $pct ?>%</div>
        <div class="text-xs text-[#aeaeae] mt-1 font-bold uppercase tracking-wider">Used %</div>
    </div>
    <div class="border-2 border-border bg-[#1a1a1a] p-4 text-center hover:bg-black/20 transition-colors shadow-[4px_4px_0px_0px_rgba(249,244,218,1)]">
        <div class="font-head text-2xl font-bold text-[#f9f4da]"><?= $used_mb ?> MB</div>
        <div class="text-xs text-[#aeaeae] mt-1 font-bold uppercase tracking-wider">Used</div>
    </div>
    <div class="border-2 border-border bg-[#1a1a1a] p-4 text-center hover:bg-black/20 transition-colors shadow-[4px_4px_0px_0px_rgba(249,244,218,1)]">
        <div class="font-head text-2xl font-bold text-[#f9f4da]"><?= $free_mb ?> MB</div>
        <div class="text-xs text-[#aeaeae] mt-1 font-bold uppercase tracking-wider">Available</div>
    </div>
    <div class="border-2 border-border bg-[#1a1a1a] p-4 text-center hover:bg-black/20 transition-colors shadow-[4px_4px_0px_0px_rgba(249,244,218,1)]">
        <div class="font-head text-2xl font-bold text-[#f9f4da]"><?= $total_mb ?> MB</div>
        <div class="text-xs text-[#aeaeae] mt-1 font-bold uppercase tracking-wider">Total</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Memory Details -->
    <div class="border-2 border-border bg-[#1a1a1a] p-6 shadow-[4px_4px_0px_0px_rgba(249,244,218,1)]">
        <h3 class="font-head text-sm text-primary mb-4 uppercase tracking-widest flex items-center gap-2">
            <i class="fas fa-chart-bar"></i> Memory Details
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse font-sans text-xs">
                <tbody>
                    <?php foreach (['MemTotal','MemFree','MemAvailable','Buffers','Cached','SwapTotal','SwapFree','Active','Inactive','Dirty','Writeback'] as $key): ?>
                    <?php if (isset($ram[$key])): ?>
                    <tr class="border-b border-[#f9f4da]/10 hover:bg-black/20 transition-colors">
                        <td class="p-2.5 font-bold text-[#aeaeae]"><?= boxui_e($key) ?></td>
                        <td class="p-2.5 text-right font-mono font-bold text-[#f9f4da]"><?= number_format($ram[$key]) ?> kB</td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Swap details if any -->
    <div class="flex flex-col gap-6">
        <?php if (!empty($swap)): ?>
        <div class="border-2 border-border bg-[#1a1a1a] p-6 shadow-[4px_4px_0px_0px_rgba(249,244,218,1)]">
            <h3 class="font-head text-sm text-primary mb-4 uppercase tracking-widest flex items-center gap-2">
                <i class="fas fa-random"></i> Swap Configurations
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse font-sans text-xs">
                    <thead>
                        <tr class="bg-black/60 border-b-2 border-border text-[#aeaeae] uppercase tracking-wider text-left">
                            <th class="p-3 font-bold">File / Device</th>
                            <th class="p-3 text-right font-bold">Size</th>
                            <th class="p-3 text-right font-bold">Used</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($swap as $s): ?>
                        <tr class="border-b border-[#f9f4da]/10 hover:bg-black/20 transition-colors">
                            <td class="p-3 font-mono text-[#d4cfa8] truncate max-w-xs"><?= boxui_e($s['file']) ?></td>
                            <td class="p-3 text-right font-mono text-[#f9f4da]"><?= number_format($s['size']) ?> kB</td>
                            <td class="p-3 text-right font-mono font-bold text-[#ff5c5c]"><?= number_format($s['used']) ?> kB</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="border-2 border-border bg-[#1a1a1a] p-6 shadow-[4px_4px_0px_0px_rgba(249,244,218,1)] flex-1 flex flex-col items-center justify-center text-center">
            <i class="fas fa-info-circle text-4xl text-[#aeaeae] mb-3"></i>
            <h4 class="font-head text-sm uppercase tracking-wider text-[#aeaeae]">No Swap Active</h4>
            <p class="text-xs text-[#aeaeae]/60 mt-1 max-w-xs">Virtual swap memory is not configured or deactivated on this system.</p>
        </div>
        <?php endif; ?>
    </div>
</div>