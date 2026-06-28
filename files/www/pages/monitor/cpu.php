<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * CPU Monitor
 * Refactored from webui/monitor/CpuMonitor.php
 */
use BoxUI\Features\Monitor\SystemMonitorService;

$usage = SystemMonitorService::getCpuUsage();
$info = SystemMonitorService::getCpuInfo();
$processes = SystemMonitorService::getProcesses('%cpu', 15);

// Handle kill action
if (isset($_GET['kill'])) {
    $pid = (int)$_GET['kill'];
    $result = SystemMonitorService::killProcess($pid);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'output' => $result]);
    exit;
}

$usage_color = $usage > 85 ? '#ff5c5c' : ($usage > 55 ? '#fcba28' : '#14b6e5');
?>

<div class="mb-8 border-b-2 border-border pb-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center bg-primary border-2 border-border text-black font-bold">
            🔧
        </div>
        <h1 class="font-head text-2xl uppercase tracking-wider text-primary">CPU Monitor</h1>
    </div>
    <span class="text-xs font-mono bg-border/20 text-[#aeaeae] px-3 py-1 border border-border/20">PROCESSOR</span>
</div>

<!-- CPU Info -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="border-2 border-border bg-[#1a1a1a] p-4 text-center hover:bg-black/20 transition-colors" style="box-shadow: 4px 4px 0px 0px <?= $usage_color ?>;">
        <div class="font-head text-3xl font-bold" style="color: <?= $usage_color ?>;"><?= $usage ?>%</div>
        <div class="text-xs text-[#aeaeae] mt-1 font-bold uppercase tracking-wider">Usage</div>
    </div>
    <div class="border-2 border-border bg-[#1a1a1a] p-4 text-center hover:bg-black/20 transition-colors shadow-[4px_4px_0px_0px_rgba(249,244,218,1)]">
        <div class="font-head text-2xl font-bold text-[#f9f4da]"><?= $info['cores'] ?? '?' ?></div>
        <div class="text-xs text-[#aeaeae] mt-1 font-bold uppercase tracking-wider">Cores</div>
    </div>
    <div class="border-2 border-border bg-[#1a1a1a] p-4 text-center hover:bg-black/20 transition-colors shadow-[4px_4px_0px_0px_rgba(249,244,218,1)] col-span-1">
        <div class="font-head text-sm font-bold text-[#f9f4da] tracking-wide truncate mt-2"><?= boxui_e($info['gov'] ?? 'N/A') ?></div>
        <div class="text-xs text-[#aeaeae] mt-1 font-bold uppercase tracking-wider">Governor</div>
    </div>
    <div class="border-2 border-border bg-[#1a1a1a] p-4 text-center hover:bg-black/20 transition-colors shadow-[4px_4px_0px_0px_rgba(249,244,218,1)] col-span-1">
        <div class="font-head text-xs font-bold text-[#f9f4da] tracking-tight truncate mt-2" title="<?= boxui_e($info['model'] ?? 'N/A') ?>"><?= boxui_e($info['model'] ?? 'N/A') ?></div>
        <div class="text-xs text-[#aeaeae] mt-1 font-bold uppercase tracking-wider">Hardware Model</div>
    </div>
</div>

<!-- Per-core Frequencies -->
<?php if (!empty($info['freqs'])): ?>
<div class="border-2 border-border bg-[#1a1a1a] p-6 mb-8 shadow-[4px_4px_0px_0px_rgba(249,244,218,1)]">
    <h3 class="font-head text-sm text-primary mb-4 uppercase tracking-widest flex items-center gap-2">
        <i class="fas fa-tachometer-alt"></i> Per-Core Frequency
    </h3>
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-4">
        <?php foreach ($info['freqs'] as $i => $f): ?>
        <div class="bg-black/40 border border-[#f9f4da]/15 p-3 text-center">
            <div class="text-[10px] text-[#aeaeae] font-bold uppercase tracking-wider mb-1">Core <?= $i ?></div>
            <div class="font-mono text-sm font-semibold text-[#f9f4da]"><?= boxui_e($f) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Processes -->
<div class="border-2 border-border bg-[#1a1a1a] p-6 shadow-[4px_4px_0px_0px_rgba(249,244,218,1)]">
    <h3 class="font-head text-sm text-primary mb-4 uppercase tracking-widest flex items-center gap-2">
        <i class="fas fa-list"></i> Top Processes (CPU)
    </h3>
    <?php if (empty($processes)): ?>
    <p class="text-sm text-[#aeaeae] font-sans">No process data available.</p>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-xs font-sans">
            <thead>
                <tr class="bg-black/60 border-b-2 border-border text-[#aeaeae] uppercase tracking-wider">
                    <th class="p-3 text-left font-bold">PID</th>
                    <th class="p-3 text-left font-bold">User</th>
                    <th class="p-3 text-right font-bold">CPU%</th>
                    <th class="p-3 text-right font-bold">MEM%</th>
                    <th class="p-3 text-left font-bold">Command</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($processes as $p): ?>
                <tr class="border-b border-[#f9f4da]/10 hover:bg-black/20 transition-colors">
                    <td class="p-3 font-mono font-bold text-primary"><?= $p['pid'] ?></td>
                    <td class="p-3 text-[#aeaeae]"><?= boxui_e($p['user']) ?></td>
                    <td class="p-3 text-right font-mono font-bold text-white"><?= $p['cpu'] ?>%</td>
                    <td class="p-3 text-right font-mono text-[#aeaeae]"><?= $p['mem'] ?>%</td>
                    <td class="p-3 font-mono text-[11px] max-w-sm truncate text-[#d4cfa8]" title="<?= boxui_e($p['command']) ?>"><?= boxui_e($p['command']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>