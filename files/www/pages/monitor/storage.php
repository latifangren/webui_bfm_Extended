<?php
/**
 * Storage Monitor
 * Refactored from webui/monitor/StorageMonitor.php
 */
use BoxUI\Features\Monitor\SystemMonitorService;

$storage = SystemMonitorService::getStorageInfo();
?>

<div class="mb-8 border-b-2 border-border pb-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center bg-primary border-2 border-border text-black font-bold">
            💾
        </div>
        <h1 class="font-head text-2xl uppercase tracking-wider text-primary">Storage Monitor</h1>
    </div>
    <span class="text-xs font-mono bg-border/20 text-[#aeaeae] px-3 py-1 border border-border/20">FILE SYSTEM</span>
</div>

<!-- Disk Usage -->
<div class="border-2 border-border bg-[#1a1a1a] p-6 mb-8 shadow-[4px_4px_0px_0px_rgba(249,244,218,1)]">
    <h3 class="font-head text-sm text-primary mb-4 uppercase tracking-widest flex items-center gap-2">
        <i class="fas fa-hdd"></i> Disk Usage
    </h3>
    <?php if (empty($storage['mounts'])): ?>
    <p class="text-sm text-[#aeaeae] font-sans">No mount filesystems detected.</p>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse font-sans text-xs">
            <thead>
                <tr class="bg-black/60 border-b-2 border-border text-[#aeaeae] uppercase tracking-wider text-left">
                    <th class="p-3 font-bold">Filesystem</th>
                    <th class="p-3 text-right font-bold">Size</th>
                    <th class="p-3 text-right font-bold">Used</th>
                    <th class="p-3 text-right font-bold">Avail</th>
                    <th class="p-3 text-center font-bold">Use%</th>
                    <th class="p-3 font-bold">Mounted On</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($storage['mounts'] as $m): ?>
                <?php 
                $pct = (int)$m['use_pct'];
                $bar_color = $pct > 85 ? '#ff5c5c' : ($pct > 55 ? '#fcba28' : '#14b6e5');
                ?>
                <tr class="border-b border-[#f9f4da]/10 hover:bg-black/20 transition-colors">
                    <td class="p-3 font-mono text-[#d4cfa8] truncate max-w-[200px]" title="<?= boxui_e($m['filesystem']) ?>"><?= boxui_e($m['filesystem']) ?></td>
                    <td class="p-3 text-right font-mono text-[#f9f4da]"><?= $m['size'] ?></td>
                    <td class="p-3 text-right font-mono text-[#f9f4da]"><?= $m['used'] ?></td>
                    <td class="p-3 text-right font-mono text-[#f9f4da]"><?= $m['avail'] ?></td>
                    <td class="p-3 text-center">
                        <span class="px-2 py-0.5 border font-mono font-bold text-[11px]" style="border-color: <?= $bar_color ?>; color: <?= $bar_color ?>; background-color: <?= $bar_color ?>20;">
                            <?= $m['use_pct'] ?>
                        </span>
                    </td>
                    <td class="p-3 font-mono font-bold text-primary"><?= boxui_e($m['mounted']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Mount Points -->
<div class="border-2 border-border bg-[#1a1a1a] p-6 shadow-[4px_4px_0px_0px_rgba(249,244,218,1)]">
    <h3 class="font-head text-sm text-primary mb-4 uppercase tracking-widest flex items-center gap-2">
        <i class="fas fa-sitemap"></i> Active Mount Points
    </h3>
    <?php if (empty($storage['mount_info'])): ?>
    <p class="text-sm text-[#aeaeae] font-sans">No detailed mount points found.</p>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse font-sans text-xs">
            <thead>
                <tr class="bg-black/60 border-b-2 border-border text-[#aeaeae] uppercase tracking-wider text-left">
                    <th class="p-3 font-bold">Device Path</th>
                    <th class="p-3 font-bold">Mount Point</th>
                    <th class="p-3 font-bold">FS Type</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($storage['mount_info'] as $m): ?>
                <tr class="border-b border-[#f9f4da]/10 hover:bg-black/20 transition-colors">
                    <td class="p-3 font-mono text-[#d4cfa8] truncate max-w-sm" title="<?= boxui_e($m['device']) ?>"><?= boxui_e($m['device']) ?></td>
                    <td class="p-3 font-mono font-bold text-primary"><?= boxui_e($m['mount']) ?></td>
                    <td class="p-3 text-[#aeaeae]"><span class="px-2 py-0.5 bg-black/40 border border-[#f9f4da]/10 font-mono text-[10px]"><?= boxui_e($m['fstype']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>