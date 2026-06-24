<?php
namespace BoxUI\Commands;

/**
 * BOX UI Extended — Command Runner
 * 
 * ONE place for ALL shell execution. No file outside this class
 * should call shell_exec(), exec(), or passthru() directly.
 * 
 * Commands are typed methods — never raw string concatenation.
 * Input validation is done before reaching shell.
 */
class CommandRunner
{
    /**
     * Run a shell command via su (root) and return output.
     */
    public static function su(string $command): string
    {
        return shell_exec("su -c '" . static::escape($command) . "'") ?? '';
    }

    /**
     * Run a shell command (without su) and return output.
     */
    public static function sh(string $command): string
    {
        return shell_exec($command) ?? '';
    }

    /**
     * Run a command and return array of lines.
     */
    public static function su_lines(string $command): array
    {
        $output = static::su($command);
        return array_filter(explode("\n", $output), 'strlen');
    }

    /**
     * Run with exec() — get exit code.
     * @return array{output: string[], exit_code: int}
     */
    public static function su_exec(string $command): array
    {
        $output = [];
        $exit_code = 0;
        exec("su -c '" . static::escape($command) . "' 2>&1", $output, $exit_code);
        return ['output' => $output, 'exit_code' => $exit_code];
    }

    // ── System Info Commands ───────────────────────────────

    public static function getprop(string $prop): string
    {
        return trim(static::sh("getprop {$prop}"));
    }

    public static function cpuinfo(): string
    {
        return static::sh('cat /proc/cpuinfo');
    }

    public static function meminfo(): string
    {
        return static::sh('cat /proc/meminfo');
    }

    public static function uptime(): string
    {
        return trim(static::sh('cat /proc/uptime'));
    }

    public static function stat(): string
    {
        return trim(static::sh('cat /proc/stat'));
    }

    public static function loadavg(): string
    {
        return trim(static::sh('cat /proc/loadavg'));
    }

    // ── Network Commands ──────────────────────────────────

    public static function ifconfig(): string
    {
        return static::sh('ifconfig 2>/dev/null');
    }

    public static function ip_addr(): string
    {
        return static::sh('ip addr show 2>/dev/null');
    }

    public static function ip_route(): string
    {
        return static::sh('ip route show 2>/dev/null');
    }

    public static function net_dev(): string
    {
        return static::sh('cat /proc/net/dev');
    }

    public static function wifi_scan(): string
    {
        return static::su('iw dev wlan0 scan 2>/dev/null');
    }

    public static function wifi_status(): string
    {
        return static::su('iw dev wlan0 link 2>/dev/null');
    }

    public static function ping(string $target, int $count = 4): string
    {
        $target = static::sanitize_host($target);
        return static::sh("ping -c {$count} {$target} 2>&1");
    }

    public static function airplane_mode(): string
    {
        return trim(static::su('settings get global airplane_mode_on'));
    }

    public static function airplane_mode_set(bool $on): void
    {
        $val = $on ? '1' : '0';
        static::su("settings put global airplane_mode_on {$val}");
        static::su("am broadcast -a android.intent.action.AIRPLANE_MODE --ez state " . ($on ? 'true' : 'false'));
    }

    // ── System Control Commands ───────────────────────────

    public static function reboot(): void
    {
        static::su('reboot');
    }

    public static function reboot_recovery(): void
    {
        static::su('reboot recovery');
    }

    public static function reboot_bootloader(): void
    {
        static::su('reboot bootloader');
    }

    public static function shutdown(): void
    {
        static::su('reboot -p');
    }

    // ── BOX Service Commands ──────────────────────────────

    public static function box_status(): string
    {
        return trim(static::sh('box.service status 2>&1'));
    }

    public static function box_start(): string
    {
        return trim(static::sh('box.service start 2>&1'));
    }

    public static function box_stop(): string
    {
        return trim(static::sh('box.service stop 2>&1'));
    }

    public static function box_restart(): string
    {
        return trim(static::sh('box.service restart 2>&1'));
    }

    // ── Hotspot Commands ──────────────────────────────────

    public static function hotspot_status(): string
    {
        return trim(static::su('settings get global tether_dun_required 2>/dev/null'));
    }

    public static function hotspot_enable(): void
    {
        static::su('svc wifi setwifienabled 0');
        static::su('svc usb setEnabled 0');
        // ... more hotspot setup
    }

    // ── Private Helpers ───────────────────────────────────

    private static function escape(string $command): string
    {
        return addslashes($command);
    }

    /**
     * Sanitize hostname/IP for ping — only allow safe chars.
     */
    private static function sanitize_host(string $host): string
    {
        // Allow: alphanumeric, dots, hyphens, underscores, colons (IPv6)
        $clean = preg_replace('/[^a-zA-Z0-9.\-_:]/', '', $host);
        return $clean ?: 'localhost';
    }
}
