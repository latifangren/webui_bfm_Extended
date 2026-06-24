<?php
namespace BoxUI\Features\Network;

use BoxUI\Commands\CommandRunner;

/**
 * BOX UI Extended — Network Service
 * 
 * Business logic for all network-related features.
 * NO HTML, NO shell_exec — delegates to CommandRunner.
 */
class NetworkService
{
    // ── WiFi ──────────────────────────────────────────────

    public static function scanWifi(): array
    {
        $raw = CommandRunner::wifi_scan();
        return self::parseWifiScan($raw);
    }

    public static function wifiStatus(): array
    {
        $raw = CommandRunner::wifi_status();
        $info = [];
        foreach (explode("\n", $raw) as $line) {
            if (preg_match('/SSID:\s*(.+)/', $line, $m)) $info['ssid'] = trim($m[1]);
            elseif (preg_match('/freq:\s*(.+)/', $line, $m)) $info['frequency'] = trim($m[1]);
            elseif (preg_match('/signal:\s*(.+)/', $line, $m)) $info['signal'] = trim($m[1]);
        }
        return $info;
    }

    public static function connectWifi(string $ssid, string $password): array
    {
        $output = [];
        $ssid_esc = addslashes($ssid);
        $pass_esc = addslashes($password);
        $output[] = CommandRunner::su("iw dev wlan0 connect -w '{$ssid_esc}' key 0:'{$pass_esc}' 2>&1");
        CommandRunner::su("svc wifi enable");
        $output[] = 'WiFi connecting...';
        return $output;
    }

    public static function wifiInfo(): string
    {
        return CommandRunner::su("dumpsys wifi 2>/dev/null | grep -E 'Wi-Fi is|SSID|mWifiInfo|linkSpeed|frequency|RSSI' | head -20");
    }

    // ── Ping Monitor ──────────────────────────────────────

    public static function ping(string $host, int $count = 4): array
    {
        $raw = CommandRunner::su("ping -c {$count} -W 2 {$host} 2>&1");

        $loss = '100';
        $avg = '-';
        $min = '-';
        $max = '-';
        $lines = explode("\n", $raw);

        foreach ($lines as $line) {
            if (preg_match('/(\d+)% packet loss/', $line, $m)) $loss = $m[1];
            if (preg_match('/round-trip.*= ([\d.]+)\/([\d.]+)\/([\d.]+)/', $line, $m)) {
                $min = $m[1]; $avg = $m[2]; $max = $m[3];
            }
        }

        return compact('raw', 'loss', 'avg', 'min', 'max', 'host');
    }

    public static function pingContinuous(string $host): string
    {
        return CommandRunner::su("ping -c 15 -W 2 {$host} 2>&1");
    }

    // ── DNS Leak Test ────────────────────────────────────

    public static function dnsLeakDetect(): array
    {
        $result = CommandRunner::sh('nslookup google.com 2>&1');
        $dns_servers = [];
        foreach (explode("\n", $result) as $line) {
            if (preg_match('/Server:\s*([0-9.]+)/', $line, $m)) $dns_servers[] = $m[1];
            elseif (preg_match('/Address\s*\d*:\s*([0-9.]+)/', $line, $m)) $dns_servers[] = $m[1];
        }
        return [
            'dns_servers' => array_values(array_unique($dns_servers)),
            'test_url' => 'https://www.dnscheck.tools/',
        ];
    }

    // ── AdBlock Test ──────────────────────────────────
    public static function adBlockTestUrl(): string { return 'https://adblock.turtlecute.org/'; }

    // ── Speed Test ────────────────────────────────────
    public static function speedTestWidgetUrl(): string { return "https://openspeedtest.com/Get-widget.php?AutoStart=1&HideResult=true"; }
    public static function hasSpeedtestBinary(): bool { return trim(CommandRunner::sh('which speedtest 2>/dev/null')) !== ''; }

    // ── NetLimiter (iptables) ─────────────────────────

    public static function getConnectedDevices(): array
    {
        $devices = [];
        $output = CommandRunner::sh('ip -4 neigh');
        if (!$output) return $devices;
        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 5 && filter_var($parts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $iface = '-';
                for ($i = 0; $i < count($parts); $i++) {
                    if ($parts[$i] === 'dev' && isset($parts[$i + 1])) { $iface = $parts[$i + 1]; break; }
                }
                $devices[] = ['ip' => $parts[0], 'mac' => $parts[count($parts) - 2] ?? '-', 'interface' => $iface];
            }
        }
        return $devices;
    }

    public static function getDhcpLeases(): array
    {
        $leases = [];
        foreach (['/data/misc/dhcp/dnsmasq.leases', '/data/misc/dhcp/dnsmasq_wlan0.leases', '/data/misc/dhcp/dnsmasq_wlan.leases'] as $f) {
            if (!file_exists($f)) continue;
            foreach (explode("\n", trim(CommandRunner::sh("cat {$f} 2>/dev/null"))) as $line) {
                $parts = explode(' ', trim($line));
                if (count($parts) >= 4) $leases[] = ['expires' => $parts[0], 'mac' => $parts[1], 'ip' => $parts[2], 'name' => $parts[3] ?? '-'];
            }
            break;
        }
        return $leases;
    }

    /**
     * Block a client via iptables.
     */
    public static function blockClient(string $ip, string $mac, string $interface = ''): void
    {
        CommandRunner::su("iptables -A FORWARD -s {$ip} -j DROP");
        CommandRunner::su("iptables -A FORWARD -m mac --mac-source {$mac} -j DROP");
        CommandRunner::su("iptables -A INPUT -s {$ip} -j DROP");
        self::saveToFile('blocked', $ip . '|' . $mac);
    }

    /**
     * Unblock a client.
     */
    public static function unblockClient(string $ip, string $mac): void
    {
        CommandRunner::su("iptables -D FORWARD -s {$ip} -j DROP");
        CommandRunner::su("iptables -D FORWARD -m mac --mac-source {$mac} -j DROP");
        CommandRunner::su("iptables -D INPUT -s {$ip} -j DROP");
        self::removeFromFile('blocked', $ip . '|' . $mac);
    }

    /**
     * Limit a client bandwidth.
     */
    public static function limitClient(string $ip, string $mac, string $rate = '1000/sec'): void
    {
        CommandRunner::su("iptables -A FORWARD -s {$ip} -m limit --limit {$rate} -j ACCEPT");
        CommandRunner::su("iptables -A FORWARD -s {$ip} -j DROP");
        self::saveToFile('limited', "{$ip}|{$mac}|{$rate}");
    }

    public static function unlimitClient(string $ip, string $mac): void
    {
        CommandRunner::su("iptables -D FORWARD -s {$ip} -m limit --limit 1000/sec -j ACCEPT");
        CommandRunner::su("iptables -D FORWARD -s {$ip} -j DROP");
        self::removeFromFile('limited', null, $ip);
    }

    public static function getBlockedClients(): array
    {
        return self::readFile('blocked');
    }

    public static function getLimitedClients(): array
    {
        return self::readFile('limited');
    }

    public static function clearAllRules(): void
    {
        CommandRunner::su('iptables -F FORWARD');
        CommandRunner::su('iptables -F INPUT');
    }

    // ── Network Preference ────────────────────────────────

    const NETWORK_MODE_NAMES = [
        0 => '2G Only (GSM/WCDMA)',
        1 => '3G Only (WCDMA)',
        2 => '3G Preferred (WCDMA pref)',
        3 => '2G/3G Auto',
        4 => '3G/4G Auto (LTE/WCDMA)',
        5 => '4G Preferred (LTE only)',
        6 => '4G Only (LTE/UMTS auto)',
        7 => '5G Auto (NR/LTE)',
        8 => '5G NSA (NR + LTE)',
        9 => '5G Only (NR only)',
        10 => '5G SA (Standalone)',
        11 => '4G/5G Auto',
    ];

    public static function getNetworkPreference(): array
    {
        $mode = CommandRunner::network_preference_get();
        $name = self::NETWORK_MODE_NAMES[$mode] ?? 'Unknown (' . $mode . ')';
        return ['mode' => $mode, 'name' => $name];
    }

    public static function setNetworkPreference(int $mode): void
    {
        CommandRunner::network_preference_set($mode);
    }

    // ── Connectivity Check ────────────────────────────────

    public static function checkConnectivity(): array
    {
        $raw = CommandRunner::connectivity_check();
        $reachable = strpos($raw, '1 received') !== false || strpos($raw, '2 received') !== false;
        $loss = '100%';
        if (preg_match('/(\d+)% packet loss/', $raw, $m)) {
            $loss = $m[0];
        }
        return [
            'reachable' => $reachable,
            'loss' => $loss,
            'raw' => $raw,
        ];
    }

    // ── DNS Lookup ────────────────────────────────────────

    public static function dnsLookup(string $host): array
    {
        $raw = CommandRunner::sh('nslookup ' . escapeshellarg($host) . ' 2>&1');
        $servers = [];
        $addresses = [];
        foreach (explode("\n", $raw) as $line) {
            if (preg_match('/Server:\s*([0-9.]+)/', $line, $m)) $servers[] = $m[1];
            elseif (preg_match('/Address\s*\d*:\s*([0-9.]+)/', $line, $m)) $addresses[] = $m[1];
            elseif (preg_match('/Name:\s*(.+)/', $line, $m)) $resolvedName = trim($m[1]);
        }
        return [
            'host' => $host,
            'servers' => array_values(array_unique($servers)),
            'addresses' => $addresses,
            'raw' => $raw,
        ];
    }

    // ── Hotspot ──────────────────────────────────────────

    public static function hotspotStatus(): array
    {
        $wifi_on = trim(CommandRunner::su('settings get global wifi_on 2>/dev/null'));
        $softap = trim(CommandRunner::su('settings get global softap_on 2>/dev/null'));
        return [
            'wifi_on' => $wifi_on === '1',
            'softap_on' => $softap === '1',
        ];
    }

    public static function hotspotEnable(string $ssid = '', string $password = ''): void
    {
        if ($ssid) CommandRunner::su("settings put global wifi_ap_ssid \"" . addslashes($ssid) . "\"");
        if ($password) CommandRunner::su("settings put global wifi_ap_passphrase \"" . addslashes($password) . "\"");
        CommandRunner::su("svc wifi disable");
        CommandRunner::su("settings put global wifi_saved_state 1");
        CommandRunner::su("settings put global softap_on 1");
    }

    public static function hotspotDisable(): void
    {
        CommandRunner::su("settings put global softap_on 0");
        CommandRunner::su("svc wifi enable");
    }

    public static function hotspotGetConfig(): array
    {
        $ssid = trim(CommandRunner::su('settings get global wifi_ap_ssid 2>/dev/null'));
        $password = trim(CommandRunner::su('settings get global wifi_ap_passphrase 2>/dev/null'));
        return [
            'ssid' => $ssid ?: 'AndroidAP',
            'password' => $password ?: '',
        ];
    }

    // ── Interface Manager ─────────────────────────────────

    public static function getInterfaceDetail(string $iface): array
    {
        $raw = CommandRunner::sh("ip addr show {$iface} 2>/dev/null");
        $stats_file = CommandRunner::sh("cat /proc/net/dev");
        $stats = [];
        foreach (explode("\n", $stats_file) as $line) {
            if (preg_match("/^{$iface}:\s*(\d+)\s+(\d+)\s+.*\s+(\d+)\s+(\d+)/", $line, $m)) {
                $stats = ['rx_bytes' => (int)$m[1], 'rx_packets' => (int)$m[2], 'tx_bytes' => (int)$m[3], 'tx_packets' => (int)$m[4]];
            }
        }
        return ['raw' => $raw, 'name' => $iface, 'stats' => $stats];
    }

    public static function getInterfaceIp(string $iface): string
    {
        $raw = CommandRunner::sh("ip -4 addr show {$iface} 2>/dev/null");
        if (preg_match('/inet\s+([0-9.]+)\/(\d+)/', $raw, $m)) return $m[1] . '/' . $m[2];
        return '-';
    }

    // ── Bandwidth Monitor (vnStat) ────────────────────────
    public static function vnstatAvailable(): bool { return trim(CommandRunner::sh('which vnstat 2>/dev/null')) !== ''; }
    public static function vnstatDaily(): string { return CommandRunner::sh('vnstat -d 2>/dev/null') ?: 'No data'; }
    public static function vnstatMonthly(): string { return CommandRunner::sh('vnstat -m 2>/dev/null') ?: 'No data'; }
    public static function vnstatTop(): string { return CommandRunner::sh('vnstat --top 2>/dev/null') ?: 'No data'; }
    public static function vnstatReset(): void
    {
        $db = '/data/data/com.termux/files/usr/var/lib/vnstat/vnstat.db';
        if (file_exists($db)) unlink($db);
    }
    public static function vnstatStart(): void
    {
        CommandRunner::sh('/data/data/com.termux/files/usr/bin/vnstatd -d');
    }

    // ── TCP/Network Optimization ──────────────────────────

    public static function optimizeTcp(): string
    {
        CommandRunner::su("echo 0 > /proc/sys/net/ipv4/tcp_timestamps");
        CommandRunner::su("echo 1 > /proc/sys/net/ipv4/tcp_sack");
        CommandRunner::su("echo 1 > /proc/sys/net/ipv4/tcp_window_scaling");
        CommandRunner::su("echo 0 > /proc/sys/net/ipv4/tcp_slow_start_after_idle");
        return "TCP optimized";
    }

    public static function optimizeNetworkBuffer(): string
    {
        CommandRunner::su("echo 4194304 > /proc/sys/net/core/rmem_max");
        CommandRunner::su("echo 4194304 > /proc/sys/net/core/wmem_max");
        CommandRunner::su("echo 4194304 > /proc/sys/net/core/rmem_default");
        CommandRunner::su("echo 4194304 > /proc/sys/net/core/wmem_default");
        return "Network buffers increased";
    }

    public static function optimizeDns(): string
    {
        foreach (['net.dns1 8.8.8.8', 'net.dns2 8.8.4.4', 'net.wlan0.dns1 8.8.8.8', 'net.wlan0.dns2 8.8.4.4'] as $prop) {
            CommandRunner::su("setprop {$prop}");
        }
        return "DNS optimized with Google DNS";
    }

    // ── Radio / Airplane Mode ──────────────────────────

    /**
     * Check if airplane mode is enabled.
     */
    public static function isAirplaneMode(): bool
    {
        return trim(CommandRunner::su('settings get global airplane_mode_on 2>/dev/null')) === '1';
    }

    /**
     * Enable or disable airplane mode.
     */
    public static function setAirplaneMode(bool $enabled): void
    {
        CommandRunner::su("settings put global airplane_mode_on " . ($enabled ? '1' : '0'));
        CommandRunner::su("am broadcast -a android.intent.action.AIRPLANE_MODE --ez state " . ($enabled ? 'true' : 'false'));
    }

    /**
     * Get radio state for wifi, bluetooth, mobile data.
     */
    public static function getRadios(): array
    {
        $wifi = trim(CommandRunner::su('settings get global wifi_on 2>/dev/null'));
        $bt = trim(CommandRunner::su('settings get global bluetooth_on 2>/dev/null'));
        $mobile = trim(CommandRunner::su('settings get global mobile_data 2>/dev/null'));
        return [
            'wifi' => $wifi === '1' ? 'on' : 'off',
            'bluetooth' => $bt === '1' ? 'on' : 'off',
            'mobile_data' => $mobile === '1' ? 'on' : 'off',
        ];
    }

    /**
     * Set individual radio states.
     */
    public static function setRadios(array $radios): void
    {
        foreach ($radios as $radio => $state) {
            switch ($radio) {
                case 'wifi':
                    $state === 'on' ? CommandRunner::su('svc wifi enable') : CommandRunner::su('svc wifi disable');
                    break;
                case 'bluetooth':
                    $state === 'on' ? CommandRunner::su('svc bluetooth enable') : CommandRunner::su('svc bluetooth disable');
                    break;
                case 'mobile_data':
                    $state === 'on' ? CommandRunner::su('svc data enable') : CommandRunner::su('svc data disable');
                    break;
            }
        }
    }

    /**
     * Get all network interfaces.
     */
    public static function getInterfaces(): array
    {
        $raw = CommandRunner::su('ip -br addr 2>/dev/null');
        $ifs = [];
        foreach (explode("\n", $raw) as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 2 && $parts[0] !== 'lo') {
                $ifs[] = [
                    'name' => $parts[0],
                    'state' => $parts[1],
                    'ip' => $parts[2] ?? '-',
                ];
            }
        }
        return $ifs;
    }

    // ── Private helpers ─────────────────────────────────

    private static function dataDir(): string
    {
        return __DIR__ . '/../../../tools';
    }

    private static function readFile(string $name): array
    {
        $path = self::dataDir() . "/{$name}_users.txt";
        return file_exists($path) ? file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    }

    private static function saveToFile(string $name, string $entry): void
    {
        $path = self::dataDir() . "/{$name}_users.txt";
        $entries = self::readFile($name);
        if (!in_array($entry, $entries)) {
            $entries[] = $entry;
            file_put_contents($path, implode("\n", $entries) . "\n");
        }
    }

    private static function removeFromFile(string $name, ?string $entry = null, ?string $ip = null): void
    {
        $path = self::dataDir() . "/{$name}_users.txt";
        $entries = self::readFile($name);
        $entries = array_filter($entries, function($e) use ($entry, $ip) {
            if ($ip) return strpos($e, $ip) === false;
            return $e !== $entry;
        });
        file_put_contents($path, implode("\n", $entries) . "\n");
    }

    private static function parseWifiScan(string $raw): array
    {
        $networks = [];
        foreach (explode("Cell ", $raw) as $block) {
            if (empty(trim($block))) continue;
            $net = [];
            if (preg_match('/ESSID:"(.+?)"/', $block, $m)) $net['ssid'] = $m[1];
            if (preg_match('/Signal level=(-\d+)/', $block, $m)) $net['signal'] = $m[1] . ' dBm';
            if (preg_match('/Encryption key:(on|off)/', $block, $m)) $net['security'] = $m[1] === 'on' ? 'Secured' : 'Open';
            if (!empty($net)) $networks[] = $net;
        }
        return $networks;
    }
}
