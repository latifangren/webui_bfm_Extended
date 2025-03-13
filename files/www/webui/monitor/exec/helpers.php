<?php
// Ambil data memori terbaru
$total_memory_kb = shell_exec('grep MemTotal /proc/meminfo | awk \'{print $2}\'');
$total_memory_gb = intval(trim($total_memory_kb)) / 1024 / 1024;
$total_memory_gb_rounded = round($total_memory_gb, 1);
$total_memory_mb_rounded = round($total_memory_gb * 1024, 1);

$free_memory_kb = shell_exec('grep MemFree /proc/meminfo | awk \'{print $2}\'');
$free_memory_gb = intval(trim($free_memory_kb)) / 1024 / 1024;
$free_memory_gb_rounded = round($free_memory_gb, 1);
$free_memory_mb_rounded = round($free_memory_gb * 1024, 1);

$buffers_memory_kb = shell_exec('grep Buffers /proc/meminfo | awk \'{print $2}\'');
$buffers_memory_gb = intval(trim($buffers_memory_kb)) / 1024 / 1024;
$buffers_memory_gb_rounded = round($buffers_memory_gb, 1);
$buffers_memory_mb_rounded = round($buffers_memory_gb * 1024, 1);

$cached_memory_kb = shell_exec('grep ^Cached /proc/meminfo | awk \'{print $2}\'');
$cached_memory_gb = intval(trim($cached_memory_kb)) / 1024 / 1024;
$cached_memory_gb_rounded = round($cached_memory_gb, 1);
$cached_memory_mb_rounded = round($cached_memory_gb * 1024, 1);

$used_memory_gb = $total_memory_gb_rounded - $free_memory_gb_rounded - $buffers_memory_gb_rounded - $cached_memory_gb_rounded;
$used_memory_mb = $total_memory_mb_rounded - $free_memory_mb_rounded - $buffers_memory_mb_rounded - $cached_memory_mb_rounded;
$used_memory_percent = round(($used_memory_mb / $total_memory_mb_rounded) * 100);

$available_memory_gb = $free_memory_gb_rounded + $buffers_memory_gb_rounded + $cached_memory_gb_rounded;
$available_memory_mb = $free_memory_mb_rounded + $buffers_memory_mb_rounded + $cached_memory_mb_rounded;

$total_free_kb = shell_exec('grep SwapFree /proc/meminfo | awk \'{print $2}\'');
$total_free_gb = intval(trim($total_free_kb)) / 1024 / 1024;
$total_free_gb_rounded = round($total_free_gb, 1);
$total_free_mb_rounded = round($total_free_gb * 1024, 1);

$total_swap_kb = shell_exec('grep SwapTotal /proc/meminfo | awk \'{print $2}\'');
$total_swap_gb = intval(trim($total_swap_kb)) / 1024 / 1024;
$total_swap_gb_rounded = round($total_swap_gb, 1);
$total_swap_mb_rounded = round($total_swap_gb * 1024, 1);

$used_swap_mb = $total_swap_mb_rounded - $total_free_mb_rounded;
$used_swap_percent = round(($used_swap_mb / $total_swap_mb_rounded) * 100);

$total_swapcache_kb = shell_exec('grep SwapCache /proc/meminfo | awk \'{print $2}\'');
$total_swapcache_mb = round($total_swapcache_kb / 1024, 1);

$total_dirty_kb = shell_exec('grep Dirty /proc/meminfo | awk \'{print $2}\'');
$total_dirty_mb = round($total_dirty_kb / 1024, 2);

$gpuFreq = shell_exec('cat /sys/class/kgsl/kgsl-3d0/devfreq/cur_freq');
$gpuLoad = shell_exec('cat /sys/kernel/gpu/gpu_busy');
$gpuFreq = trim($gpuFreq);
$gpuLoad = trim($gpuLoad);
$gpuFreqMHz = $gpuFreq / 1000000;

$mpstatOutput = shell_exec('mpstat -P ALL 1 1');
$lines = explode("\n", $mpstatOutput);
foreach ($lines as $line) {
    if (preg_match('/\s*all\s+([\d\.]+)\s+[\d\.]+\s+[\d\.]+\s+[\d\.]+\s+[\d\.]+\s+[\d\.]+\s+[\d\.]+\s+[\d\.]+\s+([\d\.]+)/', $line, $matches)) {
        $idle = $matches[2];
        $activeCpu = 100 - $idle;
        $active = round($activeCpu, 1);
        break;
    }
}
// Mengembalikan data dalam format JSON
echo json_encode([
    'total_memory' => ($total_memory_gb_rounded >= 1 ? $total_memory_gb_rounded . ' GB' : $total_memory_mb_rounded . ' MB'),
    'free_memory' => ($available_memory_gb >= 1 ? $available_memory_gb . ' GB' : $available_memory_mb . ' MB'),
    'used_memory_percent' => $used_memory_percent,
    'swap_free' => ($total_free_gb_rounded >= 1 ? $total_free_gb_rounded . ' GB' : $total_free_mb_rounded . ' MB'),
    'total_swap' => ($total_swap_gb_rounded >= 1 ? $total_swap_gb_rounded . ' GB' : $total_swap_mb_rounded . ' MB'),
    'used_swap_percent' => $used_swap_percent,
    'total_swapcache' => ($total_swapcache_mb >= 1 ? $total_swapcache_mb . ' MB' : $total_swapcache_kb . ' KB'),
    'total_dirty' => ($total_dirty_mb >= 1 ? $total_dirty_mb . ' MB' : $total_dirty_kb . ' KB'),
    'gpuFreq' => $gpuFreqMHz . ' MHz',
    'gpuLoad' => $gpuLoad,
    'active' => $active
]);
?>