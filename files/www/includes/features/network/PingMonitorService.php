<?php
namespace BoxUI\Features\Network;

use BoxUI\Commands\CommandRunner;

/**
 * BOX UI Extended — Ping Monitor Service
 *
 * Business logic for continuous ping monitoring.
 * NO HTML, NO shell_exec — delegates to CommandRunner.
 */
class PingMonitorService
{
    /**
     * Check if ping loop daemon is running.
     */
    public static function isRunning(): bool
    {
        return CommandRunner::ping_loop_status();
    }

    /**
     * Start ping loop daemon.
     */
    public static function start(): string
    {
        return CommandRunner::ping_loop_start();
    }

    /**
     * Stop ping loop daemon.
     */
    public static function stop(): string
    {
        return CommandRunner::ping_loop_stop();
    }

    /**
     * Get ping daemon log (last N lines).
     */
    public static function getLog(int $lines = 50): string
    {
        return CommandRunner::ping_loop_log($lines);
    }

    /**
     * Get monitored host from pingloop.sh config.
     */
    public static function getMonitoredHost(): string
    {
        return CommandRunner::ping_loop_host();
    }

    /**
     * Get ping log file path.
     */
    public static function getLogPath(): string
    {
        return CommandRunner::PING_LOOP_LOG;
    }

    /**
     * Get script file path.
     */
    public static function getScriptPath(): string
    {
        return CommandRunner::PING_LOOP_SCRIPT;
    }

    /**
     * Extract ping data from log for chart (success/fail per timestamp).
     * Returns array of {time: string, ok: bool} entries.
     */
    public static function getChartData(int $maxPoints = 50): array
    {
        $log = CommandRunner::ping_loop_log($maxPoints);
        $points = [];
        $lines = explode("\n", $log);

        foreach ($lines as $line) {
            $ts = '';
            $ok = false;

            if (strpos($line, 'Host dapat dijangkau') !== false) {
                $ok = true;
            } elseif (strpos($line, 'Host tidak dapat dijangkau') !== false) {
                $ok = false;
            } else {
                continue; // Skip non-ping lines
            }

            if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $line, $m)) {
                $ts = $m[1];
            }

            $points[] = ['time' => $ts, 'ok' => $ok];
        }

        // Reverse so newest is last
        $points = array_reverse($points);

        // Calculate statistics
        $total = count($points);
        $success = count(array_filter($points, fn($p) => $p['ok']));
        $fail = $total - $success;
        $uptime = $total > 0 ? round(($success / $total) * 100, 1) : 0;

        return [
            'points' => $points,
            'total' => $total,
            'success' => $success,
            'fail' => $fail,
            'uptime' => $uptime,
        ];
    }

    /**
     * Get API-style status JSON data for AJAX refresh.
     */
    public static function getStatusData(): array
    {
        $running = self::isRunning();
        $log = self::getLog();
        $host = self::getMonitoredHost();
        $chart = self::getChartData();

        return [
            'running' => $running,
            'host' => $host,
            'log' => $log,
            'chart' => $chart,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }
}
