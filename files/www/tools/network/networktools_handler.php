<?php
/**
 * Network Tools — POST/GET handler
 * 
 * Handles airplane mode, network preference, WiFi scan,
 * connectivity check, DNS lookup.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

use BoxUI\Auth\AuthService;
use BoxUI\Features\Network\NetworkService;

AuthService::init();
AuthService::requireAuth();

$action = $_REQUEST['action'] ?? '';

switch ($action) {

    // ── Airplane Mode ───────────────────────────────────
    case 'airplane':
        $enable = ($_POST['enable'] ?? '0') === '1';
        NetworkService::setAirplaneMode($enable);
        header('Location: /pages/network/tools.php');
        exit;

    case 'radios':
        $selected = $_POST['radios'] ?? [];
        $map = ['wifi' => 'on', 'bluetooth' => 'on', 'mobile' => 'on'];
        foreach ($map as $k => &$v) {
            $v = in_array($k, $selected) ? 'on' : 'off';
        }
        NetworkService::setRadios($map);
        header('Location: /pages/network/tools.php');
        exit;

    // ── Network Preference ───────────────────────────────
    case 'netpref':
        $mode = (int) ($_POST['mode'] ?? 3);
        NetworkService::setNetworkPreference($mode);
        header('Location: /pages/network/tools.php');
        exit;

    // ── WiFi Scan ────────────────────────────────────────
    case 'wifi_scan':
        $networks = NetworkService::scanWifi();
        if (empty($networks)) {
            echo '<p style="color:#888;font-size:13px;margin:8px 0;">Tidak ada jaringan ditemukan.</p>';
            break;
        }
        echo '<table class="wifi">';
        echo '<tr><th>SSID</th><th>Signal</th><th>Channel</th><th>Encrypt</th></tr>';
        foreach ($networks as $n) {
            $ssid = htmlspecialchars($n['ssid'] ?? '-');
            $sig = (int) ($n['signal'] ?? -100);
            $ch = $n['channel'] ?? '-';
            $enc = $n['encryption'] ?? '-';
            $barWidth = max(10, min(100, ($sig + 100) * 2.5));
            $barColor = $sig > -60 ? '#4CAF50' : ($sig > -80 ? '#FECA0A' : '#ff4444');
            echo '<tr>';
            echo "<td><strong>{$ssid}</strong></td>";
            echo '<td><div class="signal-bar" style="width:' . $barWidth . 'px;background:' . $barColor . ';"></div> ' . $sig . 'dBm</td>';
            echo "<td>{$ch}</td>";
            echo "<td>{$enc}</td>";
            echo '</tr>';
        }
        echo '</table>';
        break;

    // ── Connectivity Check ───────────────────────────────
    case 'connectivity':
        $result = NetworkService::checkConnectivity();
        echo "Pinging 8.8.8.8..." . PHP_EOL;
        echo "Packet loss: " . $result['loss'];
        if ($result['reachable']) {
            echo " — Internet terhubung.";
        } else {
            echo " — TIDAK terhubung.";
        }
        break;

    // ── DNS Lookup ───────────────────────────────────────
    case 'dns':
        $host = $_POST['host'] ?? 'google.com';
        $host = preg_replace('/[^a-zA-Z0-9.-]/', '', $host);
        $result = NetworkService::dnsLookup($host);
        $out = "DNS Lookup: {$host}" . PHP_EOL;
        $out .= "DNS Servers: " . implode(', ', $result['servers']) . PHP_EOL;
        $out .= "Resolved IP: " . implode(', ', $result['addresses']);
        redirect_with_message($out);
        exit;

    default:
        http_response_code(400);
        echo "Unknown action: " . htmlspecialchars($action);
}

/**
 * Redirect with a message in query string.
 */
function redirect_with_message(string $msg): void {
    $url = '/pages/network/tools.php?dns_result=' . urlencode($msg);
    header('Location: ' . $url);
    exit;
}
