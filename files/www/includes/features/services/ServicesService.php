<?php
namespace BoxUI\Features\Services;

use BoxUI\Commands\CommandRunner;

/**
 * BOX UI Extended — Services Service
 * 
 * ADB, SMS, Sidompul, and other utility services.
 */
class ServicesService
{
    // ── ADB Tools ─────────────────────────────────────────

    public static function adbPath(): string
    {
        $paths = [
            '/data/data/com.termux/files/usr/bin/adb',
            '/system/bin/adb',
            '/data/adb/adb',
        ];
        foreach ($paths as $p) {
            if (file_exists($p)) return $p;
        }
        return 'adb';
    }

    public static function adbDevices(): array
    {
        $adb = self::adbPath();
        $raw = CommandRunner::sh("{$adb} devices 2>&1");
        $devices = [];
        foreach (explode("\n", $raw) as $line) {
            if (preg_match('/^(\S+)\s+(\S+)/', $line, $m) && $m[1] !== 'List') {
                $devices[] = ['id' => $m[1], 'status' => $m[2]];
            }
        }
        return $devices;
    }

    public static function adbEnableTcpIp(): string
    {
        $adb = self::adbPath();
        return CommandRunner::sh("{$adb} shell su -c 'setprop service.adb.tcp.port 5555' && {$adb} shell su -c 'setprop persist.sys.usb.config adb' && {$adb} tcpip 5555 2>&1");
    }

    public static function adbDisableTcpIp(): string
    {
        $adb = self::adbPath();
        return CommandRunner::sh("{$adb} shell su -c 'setprop service.adb.tcp.port -1' && {$adb} shell su -c 'setprop persist.sys.usb.config adb' && {$adb} usb 2>&1");
    }

    public static function adbConnect(string $ip, string $port = '5555'): string
    {
        $adb = self::adbPath();
        return CommandRunner::sh("{$adb} connect {$ip}:{$port} 2>&1");
    }

    public static function adbRestartServer(): string
    {
        $adb = self::adbPath();
        CommandRunner::sh("{$adb} kill-server 2>&1");
        return CommandRunner::sh("{$adb} start-server 2>&1");
    }

    public static function adbGetProp(string $prop): string
    {
        $adb = self::adbPath();
        return CommandRunner::sh("{$adb} shell su -c 'getprop {$prop}' 2>&1");
    }

    public static function adbSetProp(string $prop, string $value): string
    {
        $adb = self::adbPath();
        $prop_esc = escapeshellarg($prop);
        $val_esc = escapeshellarg($value);
        return CommandRunner::sh("{$adb} shell su -c 'setprop {$prop_esc} {$val_esc}' 2>&1");
    }

    public static function adbShellCommand(string $cmd): string
    {
        $adb = self::adbPath();
        return CommandRunner::sh("{$adb} shell su -c {$cmd} 2>&1");
    }

    // ── SMS Viewer ────────────────────────────────────────

    public static function getSmsMessages(int $limit = 20): array
    {
        $cmd = '/data/data/com.termux/files/home/go/bin/sms';
        $output = [];
        exec("su -c \"{$cmd}\"", $output);
        $messages = [];

        foreach ($output as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            if (preg_match('/^\{.*\}$/', $line)) {
                $msg = json_decode($line, true);
                if ($msg) $messages[] = $msg;
            } else {
                // Plain text format
                $messages[] = ['raw' => $line];
            }
        }

        return array_slice($messages, 0, $limit);
    }

    /**
     * Get SMS inbox summary.
     */
    public static function smsStats(): array
    {
        return [
            'binary' => file_exists('/data/data/com.termux/files/home/go/bin/sms') ? 'Available' : 'Not installed',
        ];
    }

    // ── Sidompul (Telco Balance) ──────────────────────────

    public static function sidompulEndpoints(): array
    {
        return [
            'url' => 'https://apigw.kmsp-store.com/sidompul/v3/cek_kuota',
            'api_key' => '4352ff7d-f4e6-48c6-89dd-21c811621b1c',
            'description' => 'Telco balance check via Sidompul API',
        ];
    }

    // ── About ─────────────────────────────────────────────

    public static function moduleVersion(): string
    {
        $file = '/data/adb/modules/boxui_extended/module.prop';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if (preg_match('/version=(.+)/', $content, $m)) return trim($m[1]);
        }
        return 'Unknown';
    }

    public static function diskUsage(): string
    {
        return CommandRunner::sh('df -h /data 2>/dev/null | tail -1');
    }
}
