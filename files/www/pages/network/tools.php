<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * Network Tools — Airplane Mode + Network Preference + WiFi.
 *
 * Pure view — logic in NetworkService.
 */
use BoxUI\Features\Network\NetworkService;

$airplane_enabled = NetworkService::isAirplaneMode();
$radios = NetworkService::getRadios();
$wifi_status = NetworkService::wifiStatus();
$interfaces = NetworkService::getInterfaces();
$net_pref = NetworkService::getNetworkPreference();
?>
<style>
:root {
    --bg-p: #0a0a0a; --bg-s: #111; --tx: #F1F1F1;
    --accent: #FECA0A; --border: #333;
    --green: #4CAF50; --red: #ff4444;
}
body {
    font-family: 'Rajdhani', sans-serif; margin: 0; padding: 20px;
    background: var(--bg-p); color: var(--tx);
}
.container { max-width: 900px; margin: 0 auto; }
h1 {
    font-family: 'Orbitron', monospace; font-size: 20px; color: var(--accent);
    margin: 0 0 16px 0;
}
.card {
    background: var(--bg-s); border: 1px solid var(--border);
    border-radius: 10px; padding: 16px; margin-bottom: 14px;
}
.card-title {
    font-size: 14px; font-weight: 600; color: var(--accent);
    margin: 0 0 12px 0;
    font-family: 'Orbitron', monospace;
    text-transform: uppercase;
}
.flex { display: flex; flex-wrap: wrap; gap: 15px; }
.col { flex: 1; min-width: 280px; }
.btn {
    padding: 8px 18px; border: none; border-radius: 6px;
    font-family: 'Orbitron', monospace; font-size: 11px;
    font-weight: 600; cursor: pointer; text-transform: uppercase;
    transition: opacity .15s;
}
.btn:hover { opacity: .85; }
.btn-primary { background: var(--accent); color: #000; }
.btn-small { padding: 5px 12px; font-size: 10px; }
select, input[type="text"] {
    padding: 8px 12px; background: #1a1a1a; border: 1px solid var(--border);
    border-radius: 6px; color: var(--tx); font-size: 13px; width: 100%;
    box-sizing: border-box; font-family: inherit;
}
label { display: block; font-size: 12px; color: #888; margin-bottom: 4px; }
.mb-8 { margin-bottom: 8px; }
.mt-8 { margin-top: 8px; }
.note { font-size: 12px; color: #666; margin-top: 4px; }
pre.output {
    background: #000; color: #0f0; padding: 10px; border-radius: 6px;
    font-size: 11px; overflow-x: auto; max-height: 200px;
    white-space: pre-wrap; word-break: break-all;
    border: 1px solid #222; font-family: 'Courier New', monospace;
}
pre.output:empty { display: none; }
.status-badge {
    display: inline-block; padding: 2px 10px; border-radius: 10px;
    font-size: 11px; font-weight: 600;
}
.badge-on { background: var(--green); color: #000; }
.badge-off { background: #444; color: #ccc; }

/* WiFi scan table */
table.wifi { width: 100%; border-collapse: collapse; font-size: 12px; }
table.wifi th {
    text-align: left; padding: 6px 8px; border-bottom: 1px solid var(--border);
    color: #888; font-weight: 600; font-family: 'Orbitron', monospace; font-size: 10px;
    text-transform: uppercase;
}
table.wifi td { padding: 6px 8px; border-bottom: 1px solid #1a1a1a; }
table.wifi tr:hover td { background: #1a1a1a; }
.signal-bar { display: inline-block; height: 10px; border-radius: 2px; }
</style>

<div class="container">
    <h1>Network Tools</h1>

    <div class="flex">
        <!-- Airplane Mode -->
        <div class="col">
            <div class="card">
                <div class="card-title">Mode Pesawat</div>
                <form hx-post="/tools/network/networktools_handler.php" hx-target="#content" style="margin-bottom:10px;">
                    <input type="hidden" name="action" value="airplane">
                    <input type="hidden" name="enable" value="<?= $airplane_enabled ? '0' : '1' ?>">
                    <button type="submit" class="btn btn-primary">
                        <?= $airplane_enabled ? 'Matikan' : 'Aktifkan' ?>
                    </button>
                    <span class="status-badge <?= $airplane_enabled ? 'badge-on' : 'badge-off' ?>" style="margin-left:8px;">
                        <?= $airplane_enabled ? 'ON' : 'OFF' ?>
                    </span>
                </form>

                <label>Radio selection saat airplane mode ON:</label>
                <form hx-post="/tools/network/networktools_handler.php" hx-target="#content">
                    <input type="hidden" name="action" value="radios">
                    <?php foreach (['wifi' => 'WiFi', 'bluetooth' => 'Bluetooth', 'mobile' => 'Mobile Data'] as $k => $lbl): ?>
                    <label style="display:flex;align-items:center;gap:8px;margin:6px 0;font-size:13px;cursor:pointer;">
                        <input type="checkbox" name="radios[]" value="<?= $k ?>" <?= in_array($k, $radios) ? 'checked' : '' ?>>
                        <?= $lbl ?>
                    </label>
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-primary btn-small mt-8">Simpan</button>
                </form>
            </div>

            <!-- WiFi Status + Scan -->
            <div class="card">
                <div class="card-title">WiFi</div>
                <div class="mb-8">
                    <label>Status</label>
                    <span class="status-badge <?= ($wifi_status['ssid'] ?? '') ? 'badge-on' : 'badge-off' ?>">
                        <?= $wifi_status['ssid'] ?? 'Tidak terhubung' ?>
                    </span>
                    <?php if ($wifi_status['ssid'] ?? ''): ?>
                    <div class="note">Signal: <?= $wifi_status['signal'] ?? '-' ?> | Freq: <?= $wifi_status['frequency'] ?? '-' ?></div>
                    <?php endif; ?>
                </div>
                <button class="btn btn-primary btn-small" 
                        hx-post="/tools/network/networktools_handler.php" 
                        hx-vals='{"action": "wifi_scan"}'
                        hx-target="#wifi-results"
                        hx-indicator="#wifi-indicator">
                    Scan WiFi
                </button>
                <div id="wifi-indicator" class="htmx-indicator" style="color:#888;font-size:12px;margin-top:8px;">Scanning...</div>
                <div id="wifi-results" style="margin-top:10px;"></div>
            </div>
        </div>

        <!-- Network Preference -->
        <div class="col">
            <div class="card">
                <div class="card-title">Preferensi Jaringan</div>
                <form hx-post="/tools/network/networktools_handler.php" hx-target="#content">
                    <input type="hidden" name="action" value="netpref">
                    <div class="mb-8">
                        <label>Mode jaringan saat ini:</label>
                        <div style="font-size:15px;font-weight:600;color:var(--accent);" id="current-mode">
                            <?= htmlspecialchars($net_pref['name']) ?> (<?= $net_pref['mode'] ?>)
                        </div>
                    </div>
                    <div class="mb-8">
                        <label>Set mode:</label>
                        <select name="mode">
                            <option value="0">2G Only (GSM/WCDMA)</option>
                            <option value="1">3G Only (WCDMA)</option>
                            <option value="3">2G/3G Auto</option>
                            <option value="5">4G Only</option>
                            <option value="7">5G Auto (NR/LTE)</option>
                            <option value="8">5G NSA</option>
                            <option value="9">5G Only (NR)</option>
                            <option value="10">5G SA</option>
                            <option value="11">4G/5G Auto</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-small">Terapkan</button>
                </form>
            </div>

            <!-- Connectivity Check -->
            <div class="card">
                <div class="card-title">Konektivitas</div>
                <button class="btn btn-primary btn-small" 
                        hx-post="/tools/network/networktools_handler.php" 
                        hx-vals='{"action": "connectivity"}'
                        hx-target="#conn-output"
                        hx-indicator="#conn-indicator">
                    Cek Koneksi
                </button>
                <div id="conn-indicator" class="htmx-indicator" style="color:#888;font-size:12px;margin-top:8px;">Checking...</div>
                <pre class="output" id="conn-output" style="margin-top:8px;"></pre>
            </div>

            <!-- DNS Lookup -->
            <div class="card">
                <div class="card-title">DNS Lookup</div>
                <form hx-post="/tools/network/networktools_handler.php" hx-target="#content">
                    <input type="hidden" name="action" value="dns">
                    <div class="mb-8">
                        <input type="text" name="host" placeholder="google.com" value="google.com">
                    </div>
                    <button type="submit" class="btn btn-primary btn-small">Lookup</button>
                </form>
                <?php if (isset($_GET['dns_result'])): ?>
                <pre class="output mt-8"><?= htmlspecialchars(urldecode($_GET['dns_result'])) ?></pre>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
