<?php
namespace BoxUI\Features\Monitor;

use BoxUI\Commands\CommandRunner;

/**
 * BOX UI Extended — System Monitor Service
 * 
 * Business logic for CPU, RAM, Battery, Storage monitoring.
 * Reads from /proc, /sys, and shell commands.
 */
class SystemMonitorService
{
    // ── CPU ───────────────────────────────────────────────

    /**
     * Get CPU usage percentage (averaged over 0.5s).
     */
    public static function getCpuUsage(): array
    {
        $stats1 = file('/proc/stat');
        $user1 = self::readCpuField($stats1[0], 1);
        $nice1 = self::readCpuField($stats1[0], 2);
        $system1 = self::readCpuField($stats1[0], 3);
        $idle1 = self::readCpuField($stats1[0], 4);

        usleep(500000);

        $stats2 = file('/proc/stat');
        $user2 = self::readCpuField($stats2[0], 1);
        $nice2 = self::readCpuField($stats2[0], 2);
        $system2 = self::readCpuField($stats2[0], 3);
        $idle2 = self::readCpuField($stats2[0], 4);

        $total1 = $user1 + $nice1 + $system1 + $idle1;
        $total2 = $user2 + $nice2 + $system2 + $idle2;

        if ($total2 === $total1) return 0;

        $used = ($total2 - $total1) - ($idle2 - $idle1);
        return round(($used / ($total2 - $total1)) * 100, 1);
    }

    /**
     * Get detailed CPU info.
     */
    public static function getCpuInfo(): array
    {
        $cpuInfo = @file_get_contents('/proc/cpuinfo') ?: '';
        $cores = 0;
        $model = 'Unknown';
        foreach (explode("\n", $cpuInfo) as $line) {
            if (preg_match('/^processor\s+:\s+(\d+)/', $line, $m)) $cores = max($cores, (int)$m[1] + 1);
            if (preg_match('/^Hardware\s+:\s+(.+)/', $line, $m)) $model = trim($m[1]);
        }

        $freqs = [];
        for ($i = 0; $i < $cores; $i++) {
            $f = @file_get_contents("/sys/devices/system/cpu/cpu{$i}/cpufreq/scaling_cur_freq");
            $freqs[] = $f ? round((int)$f / 1000) . ' MHz' : 'N/A';
        }

        $governor = @file_get_contents("/sys/devices/system/cpu/cpu0/cpufreq/scaling_governor");
        $gov = $governor ? trim($governor) : 'N/A';

        // Per-core load
        $stat = @file('/proc/stat') ?: [];
        $perCore = [];
        foreach ($stat as $line) {
            if (preg_match('/^cpu(\d+)\s/', $line, $m)) {
                $vals = array_map('intval', preg_split('/\s+/', trim($line)));
                $total = array_sum(array_slice($vals, 1));
                $idle = $vals[4] ?? 0;
                $perCore[(int)$m[1]] = ['total' => $total, 'idle' => $idle];
            }
        }

        return compact('cores', 'model', 'freqs', 'gov', 'perCore');
    }

    /**
     * Get running processes list.
     */
    public static function getProcesses(string $sortBy = '%cpu', int $limit = 20): array
    {
        $raw = CommandRunner::sh("ps aux 2>/dev/null | head -" . ($limit + 1));
        $processes = [];
        $lines = explode("\n", trim($raw));
        if (count($lines) < 2) return $processes;

        // Skip header
        for ($i = 1; $i < count($lines); $i++) {
            $parts = preg_split('/\s+/', trim($lines[$i]));
            if (count($parts) >= 11) {
                $processes[] = [
                    'user' => $parts[0],
                    'pid' => $parts[1],
                    'cpu' => (float)$parts[2],
                    'mem' => (float)$parts[3],
                    'rss' => (int)$parts[5],
                    'command' => implode(' ', array_slice($parts, 10)),
                ];
            }
        }
        return $processes;
    }

    public static function killProcess(int $pid): string
    {
        return CommandRunner::su("kill -9 {$pid} 2>&1");
    }

    // ── RAM ───────────────────────────────────────────────

    public static function getRamInfo(): array
    {
        $meminfo = @file('/proc/meminfo') ?: [];
        $info = [];
        foreach ($meminfo as $line) {
            if (preg_match('/^(\w+):\s+(\d+)/', $line, $m)) {
                $info[$m[1]] = (int)$m[2];
            }
        }
        return $info;
    }

    public static function getSwapInfo(): array
    {
        $raw = CommandRunner::sh('swapon -s 2>/dev/null');
        $swaps = [];
        foreach (explode("\n", trim($raw)) as $line) {
            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 5 && $parts[0] !== 'Filename') {
                $swaps[] = [
                    'file' => $parts[0],
                    'size' => (int)$parts[2],
                    'used' => (int)$parts[3],
                ];
            }
        }
        return $swaps;
    }

    // ── Battery ──────────────────────────────────────────

    public static function getBatteryInfo(): array
    {
        $base = '/sys/class/power_supply/battery/';
        if (!is_dir($base)) return ['error' => 'Battery path not accessible'];

        $info = [];
        $info['capacity'] = (int)self::readSysFile($base . 'capacity');
        $info['status'] = self::readSysFile($base . 'status');
        $info['health'] = self::readSysFile($base . 'health');
        $info['technology'] = self::readSysFile($base . 'technology');
        $info['voltage_now'] = (int)self::readSysFile($base . 'voltage_now');
        $info['current_now'] = (int)self::readSysFile($base . 'current_now');
        $info['temp'] = (int)self::readSysFile($base . 'temp');
        $info['charge_full'] = (int)self::readSysFile($base . 'charge_full');
        $info['charge_full_design'] = (int)self::readSysFile($base . 'charge_full_design');
        $info['charge_counter'] = (int)self::readSysFile($base . 'charge_counter');

        // Thermal zones
        $thermals = [];
        for ($i = 0; $i < 20; $i++) {
            $t = self::readSysFile("/sys/class/thermal/thermal_zone{$i}/temp");
            $type = self::readSysFile("/sys/class/thermal/thermal_zone{$i}/type");
            if ($t !== '' && $type !== '') {
                $thermals[] = ['type' => $type, 'temp' => round((int)$t / 1000, 1)];
            }
        }
        $info['thermals'] = $thermals;

        return $info;
    }

    // ── Storage ──────────────────────────────────────────

    public static function getStorageInfo(): array
    {
        $raw = CommandRunner::sh("df -h 2>/dev/null");
        $mounts = [];
        foreach (explode("\n", trim($raw)) as $line) {
            if (preg_match('/^(\/\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)/', $line, $m)) {
                $mounts[] = [
                    'filesystem' => $m[1],
                    'size' => $m[2],
                    'used' => $m[3],
                    'avail' => $m[4],
                    'use_pct' => $m[5],
                    'mounted' => $m[6],
                ];
            }
        }

        // Mount info
        $mounts_raw = @file('/proc/mounts') ?: [];
        $mount_info = [];
        foreach ($mounts_raw as $line) {
            $parts = explode(' ', $line);
            if (count($parts) >= 4 && strpos($parts[0], '/') === 0) {
                $mount_info[] = [
                    'device' => $parts[0],
                    'mount' => $parts[1],
                    'fstype' => $parts[2],
                ];
            }
        }

        return compact('mounts', 'mount_info');
    }

    // ── Private ──────────────────────────────────────────

    private static function readCpuField(string $line, int $index): int
    {
        $vals = array_map('intval', preg_split('/\s+/', trim($line)));
        return $vals[$index] ?? 0;
    }

    private static function readSysFile(string $path): string
    {
        $content = @file_get_contents($path);
        return $content ? trim($content) : '';
    }
}
