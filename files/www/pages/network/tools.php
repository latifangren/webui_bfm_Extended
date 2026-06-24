<?php
/**
 * Network Tools — Airplane Mode page
 * 
 * Refactored from tools/networktools.php.
 * Logic extracted to NetworkService, this is pure view.
 */
use BoxUI\Features\Network\NetworkService;

$airplane_enabled = NetworkService::isAirplaneMode();
$radios = NetworkService::getRadios();
$wifi_status = NetworkService::wifiStatus();
$interfaces = NetworkService::getInterfaces();
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">📡</span>
            <h1>Network Tools</h1>
        </div>
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:15px;">
        <!-- Airplane Mode -->
        <div style="flex:1;min-width:280px;background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
            <h3 style="margin-top:0;font-size:16px;color:var(--accent,#FECA0A);">
                <i class="fas fa-plane"></i> Mode Pesawat
            </h3>
            <p style="font-size:13px;color:#888;">Status: 
                <strong style="color:<?= $airplane_enabled ? '#ff4444' : '#4CAF50' ?>">
                    <?= $airplane_enabled ? 'AKTIF' : 'Nonaktif' ?>
                </strong>
            </p>
            <form method="POST" action="/tools/network/networktools_handler.php" style="margin-top:15px;">
                <input type="hidden" name="action" value="<?= $airplane_enabled ? 'disable_airplane_mode' : 'enable_airplane_mode' ?>">
                <input type="hidden" name="enabled_radios" value='<?= boxui_e(json_encode($radios)) ?>'>

                <?php if (!$airplane_enabled): ?>
                <div style="margin-bottom:12px;">
                    <label style="display:block;font-size:12px;color:#aaa;margin-bottom:6px;">Radio yang tetap aktif:</label>
                    <label style="display:flex;align-items:center;gap:8px;margin-bottom:4px;font-size:13px;">
                        <input type="checkbox" name="cell" value="cell" <?= in_array('cell', $radios) ? 'checked' : '' ?>>
                        <span>Cellular</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;margin-bottom:4px;font-size:13px;">
                        <input type="checkbox" name="bluetooth" value="bluetooth" <?= in_array('bluetooth', $radios) ? 'checked' : '' ?>>
                        <span>Bluetooth</span>
                    </label>
                </div>
                <?php endif; ?>

                <button type="submit" style="width:100%;padding:10px;background:var(--accent,#FECA0A);color:#000;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px;">
                    <i class="fas fa-<?= $airplane_enabled ? 'toggle-off' : 'toggle-on' ?>"></i>
                    <?= $airplane_enabled ? 'Nonaktifkan' : 'Aktifkan' ?> Mode Pesawat
                </button>
            </form>
        </div>

        <!-- WiFi Status -->
        <div style="flex:1;min-width:280px;background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
            <h3 style="margin-top:0;font-size:16px;color:var(--accent,#FECA0A);">
                <i class="fas fa-wifi"></i> WiFi Status
            </h3>
            <?php if (isset($wifi_status['ssid'])): ?>
                <p style="font-size:13px;color:#888;">SSID: <strong style="color:#F1F1F1;"><?= boxui_e($wifi_status['ssid']) ?></strong></p>
                <p style="font-size:13px;color:#888;">Signal: <strong style="color:#F1F1F1;"><?= boxui_e($wifi_status['signal'] ?? 'N/A') ?></strong></p>
                <p style="font-size:13px;color:#888;">Frequency: <strong style="color:#F1F1F1;"><?= boxui_e($wifi_status['frequency'] ?? 'N/A') ?></strong></p>
            <?php else: ?>
                <p style="font-size:13px;color:#888;">Tidak terhubung ke WiFi</p>
            <?php endif; ?>
        </div>

        <!-- Network Interfaces -->
        <div style="flex:1;min-width:280px;background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
            <h3 style="margin-top:0;font-size:16px;color:var(--accent,#FECA0A);">
                <i class="fas fa-network-wired"></i> Interfaces
            </h3>
            <ul style="list-style:none;padding:0;margin:0;">
            <?php foreach ($interfaces as $iface): ?>
                <li style="padding:6px 0;border-bottom:1px solid #222;font-size:13px;display:flex;justify-content:space-between;">
                    <span><?= boxui_e($iface['name']) ?></span>
                    <span style="color:<?= $iface['state'] === 'up' ? '#4CAF50' : '#888' ?>">
                        <?= $iface['state'] === 'up' ? 'UP' : 'DOWN' ?>
                        <?php if (!empty($iface['ips'])): ?>
                            (<?= boxui_e($iface['ips'][0]) ?>)
                        <?php endif; ?>
                    </span>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
