<?php
namespace BoxUI\Features\Box;

use BoxUI\Commands\CommandRunner;

/**
 * BOX UI Extended — Box Service
 * 
 * BFR (Box For Root) service control and settings management.
 */
class BoxService
{
    // ── Service Control ──────────────────────────────────

    public static function start(): string
    {
        $cmd = "/data/adb/box/scripts/box.service start && /data/adb/box/scripts/box.iptables enable";
        return CommandRunner::su($cmd);
    }

    public static function stop(): string
    {
        $cmd = "/data/adb/box/scripts/box.iptables disable && /data/adb/box/scripts/box.service stop";
        return CommandRunner::su($cmd);
    }

    public static function restart(): string
    {
        $cmd = "/data/adb/box/scripts/box.service restart";
        return CommandRunner::su($cmd);
    }

    public static function status(): array
    {
        $raw = CommandRunner::su("/data/adb/box/scripts/box.service status 2>&1");
        $lines = explode("\n", trim($raw));
        return [
            'raw' => $raw,
            'is_running' => preg_match('/is running|active \(running\)/i', $raw),
            'lines' => $lines,
        ];
    }

    public static function getLogs(int $lines = 50): array
    {
        $logfile = '/data/adb/box/run/runs.log';
        if (!file_exists($logfile)) return ['No logs available'];
        $raw = CommandRunner::su("tail -{$lines} \"{$logfile}\" 2>/dev/null");
        return array_filter(explode("\n", $raw), function($l) {
            return trim($l) !== '';
        });
    }

    // ── Settings ─────────────────────────────────────────

    public static function settingsPath(): string
    {
        return '/data/adb/box/settings.ini';
    }

    public static function parseSettings(): array
    {
        $path = self::settingsPath();
        if (!file_exists($path)) return [];

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        $settings = [];
        foreach ($lines as $line) {
            if (trim($line) === '' || (isset($line[0]) && ($line[0] === ';' || $line[0] === '#'))) {
                $settings[] = $line;
            } elseif (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if (preg_match('/^"(.*)"$/', $value, $m)) {
                    $value = $m[1];
                } elseif (preg_match("/^'(.*)'$/", $value, $m)) {
                    $value = $m[1];
                }
                $settings[$key] = $value;
            } else {
                $settings[] = $line;
            }
        }
        return $settings;
    }

    public static function saveSettings(array $postData): string
    {
        $path = self::settingsPath();
        if (!file_exists($path)) return "Settings file not found";

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        $newLines = [];

        foreach ($lines as $line) {
            if (trim($line) === '' || (isset($line[0]) && ($line[0] === ';' || $line[0] === '#'))) {
                $newLines[] = $line;
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $type = $postData['type_' . $key] ?? '';

                if ($type === 'bool') {
                    $newVal = isset($postData[$key]) && $postData[$key] === 'on' ? 'true' : 'false';
                    $newLines[] = "{$key} = {$newVal}";
                } elseif ($type === 'dropdown') {
                    $newVal = $postData[$key] ?? $value;
                    $newLines[] = "{$key} = {$newVal}";
                } elseif ($type === 'form') {
                    $newVal = $postData[$key] ?? $value;
                    if (empty($newVal)) $newVal = '""';
                    $newLines[] = "{$key} = {$newVal}";
                } else {
                    $newVal = $postData[$key] ?? trim($value);
                    $newLines[] = "{$key} = {$newVal}";
                }
            } else {
                $newLines[] = $line;
            }
        }

        $content = implode("\n", $newLines);
        $encoded = base64_encode($content);
        CommandRunner::su("echo '{$encoded}' | base64 -d > '{$path}'");
        return "Settings saved";
    }

    // ── Config types ─────────────────────────────────────

    public static function boolKeys(): array
    {
        return ['port_detect','ipv6','cgroup_cpuset','cgroup_blkio','cgroup_memcg','run_crontab','update_geo','renew','update_subscription'];
    }

    public static function formKeys(): array
    {
        return ['tproxy_port','redir_port','memcg_limit','subscription_url_clash','name_clash_config','name_sing_config'];
    }

    public static function dropdownKeys(): array
    {
        return [
            'bin_name' => ['clash', 'sing-box', 'xray', 'v2fly'],
            'xclash_option' => ['mihomo', 'premium'],
            'network_mode' => ['redirect', 'tproxy', 'mixed', 'enhance', 'tun'],
            'proxy_mode' => ['blacklist', 'whitelist'],
        ];
    }

    public static function getHost(): string
    {
        $parts = explode(':', $_SERVER['HTTP_HOST'] ?? 'localhost');
        return $parts[0];
    }

    /**
     * Check if running on Android (termux).
     */
    public static function isAndroid(): bool
    {
        return file_exists('/data/data/com.termux') || is_dir('/data/adb');
    }
}
