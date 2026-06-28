<?php
/**
 * Airplane Pilot — View
 * Refactored from tools/modpes.php
 */
use BoxUI\Features\Network\NetworkService;

$airplane = NetworkService::isAirplaneMode();
$radios = NetworkService::getRadios();
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">✈️</span>
            <h1>Airplane Pilot</h1>
        </div>
    </div>

    <!-- Airplane Mode -->
    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;margin-bottom:15px;border:1px solid var(--border,#333);">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;">
            <div>
                <h3 style="margin:0;font-size:16px;">Mode Pesawat</h3>
                <p style="font-size:13px;color:#888;margin:5px 0 0;">
                    Status: <strong style="color:<?= $airplane ? '#ff4444' : '#4CAF50' ?>"><?= $airplane ? 'AKTIF' : 'Nonaktif' ?></strong>
                </p>
            </div>
            <form hx-post="/tools/network/networktools_handler.php" hx-target="#content">
                <input type="hidden" name="action" value="airplane">
                <input type="hidden" name="enable" value="<?= $airplane ? '0' : '1' ?>">
                <input type="hidden" name="redirect" value="/pages/network/airplane.php">
                <button type="submit"
                        style="padding:10px 24px;border:none;border-radius:8px;background:<?= $airplane ? '#4CAF50' : '#ff4444' ?>;color:#fff;font-weight:600;cursor:pointer;">
                    <?= $airplane ? 'Nonaktifkan' : 'Aktifkan' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Radio States -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
        <?php foreach ($radios as $radio => $state): ?>
        <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);text-align:center;">
            <div style="font-size:28px;margin-bottom:8px;">
                <?php
                $icons = ['wifi' => '📶', 'bluetooth' => '🔵', 'mobile_data' => '📱'];
                echo $icons[$radio] ?? '📡';
                ?>
            </div>
            <h4 style="margin:0 0 8px;font-size:14px;text-transform:capitalize;"><?= $radio ?></h4>
            <span style="font-size:12px;padding:4px 12px;border-radius:12px;background:<?= $state === 'on' ? 'rgba(76,175,80,0.2)' : 'rgba(102,102,102,0.2)' ?>;color:<?= $state === 'on' ? '#4CAF50' : '#888' ?>;">
                <?= $state === 'on' ? 'ON' : 'OFF' ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
