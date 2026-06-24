<?php
namespace BoxUI\Features\System;

use BoxUI\Commands\CommandRunner;

/**
 * BOX UI Extended — System Service
 * 
 * Power management, log reading, and general system operations.
 */
class SystemService
{
    // ── Power Management ─────────────────────────────────

    /**
     * Reboot the device.
     */
    public static function reboot(string $mode = ''): string
    {
        $cmd = 'reboot';
        if (in_array($mode, ['recovery', 'bootloader', 'fastboot'])) {
            $cmd .= " {$mode}";
        } elseif ($mode === 'poweroff') {
            $cmd = 'reboot -p';
        }
        return CommandRunner::su($cmd);
    }

    /**
     * Reboot to recovery.
     */
    public static function rebootRecovery(): string
    {
        return CommandRunner::su('reboot recovery');
    }

    /**
     * Reboot to bootloader.
     */
    public static function rebootBootloader(): string
    {
        return CommandRunner::su('reboot bootloader');
    }

    /**
     * Reboot to fastboot.
     */
    public static function rebootFastboot(): string
    {
        return CommandRunner::su('reboot fastboot');
    }

    /**
     * Soft reboot (framework restart).
     */
    public static function softReboot(): string
    {
        return CommandRunner::su('pkill -TERM system_server');
    }

    /**
     * Shutdown device.
     */
    public static function shutdown(): string
    {
        return CommandRunner::su('reboot -p');
    }

    // ── Logs ──────────────────────────────────────────────

    /**
     * Read a log file, returns last N lines.
     */
    public static function readLog(string $path, int $lines = 200): string
    {
        if (!file_exists($path)) return "File not found: {$path}";
        $raw = CommandRunner::sh("tail -{$lines} \"{$path}\" 2>/dev/null") ?: '';
        return $raw;
    }

    /**
     * Search log file for a pattern.
     */
    public static function searchLog(string $path, string $pattern): string
    {
        if (!file_exists($path)) return "File not found: {$path}";
        return CommandRunner::sh("grep -i '{$pattern}' \"{$path}\" 2>/dev/null") ?: 'No matches';
    }

    /**
     * Clear a log file.
     */
    public static function clearLog(string $path): void
    {
        if (file_exists($path)) {
            CommandRunner::su("> \"{$path}\"");
        }
    }

    /**
     * Get list of available log files in /cache/.
     */
    public static function getLogFiles(): array
    {
        $files = [];
        $dirs = ['/cache/', '/data/adb/box/', '/data/adb/'];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) continue;
            $raw = CommandRunner::sh("ls -lh {$dir} 2>/dev/null | grep -v '^total' | head -30");
            foreach (explode("\n", $raw) as $line) {
                if (preg_match('/^([drwx-]{10})\s+\d+\s+\S+\s+\S+\s+(\d+\.?\d*[KMG]?)\s+\S+\s+\S+\s+\S+\s+(.+)$/', $line, $m)) {
                    $files[] = [
                        'perms' => $m[1],
                        'size' => $m[2],
                        'name' => trim($m[3]),
                        'path' => $dir . trim($m[3]),
                    ];
                }
            }
        }
        return $files;
    }

    // ── Uptime ────────────────────────────────────────────

    public static function uptime(): string
    {
        return trim(CommandRunner::su('cat /proc/uptime 2>/dev/null'));
    }

    public static function uptimeFormatted(): string
    {
        $uptime = self::uptime();
        $seconds = (int)explode(' ', $uptime)[0] ?? 0;
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return "{$days}d {$hours}h {$minutes}m";
    }

    // ── Info ──────────────────────────────────────────────

    public static function kernelVersion(): string
    {
        return trim(CommandRunner::sh('uname -r 2>/dev/null'));
    }

    public static function androidVersion(): string
    {
        return trim(CommandRunner::su('getprop ro.build.version.release 2>/dev/null'));
    }

    public static function deviceModel(): string
    {
        return trim(CommandRunner::su('getprop ro.product.model 2>/dev/null'));
    }

    public static function magiskVersion(): string
    {
        return trim(CommandRunner::su('magisk -c 2>/dev/null'));
    }
}
