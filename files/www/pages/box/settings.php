<?php
/**
 * BOX Settings (BFR Configuration)
 * Refactored from tools/bfr/boxsettings.php
 */
use BoxUI\Features\Box\BoxService;

$settings = BoxService::parseSettings();
$bool_keys = BoxService::boolKeys();
$form_keys = BoxService::formKeys();
$dropdown_keys = BoxService::dropdownKeys();
$ini_path = BoxService::settingsPath();

$save_msg = isset($_GET['msg']) ? html_entity_decode($_GET['msg']) : '';
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">⚙️</span>
            <h1>BOX Settings</h1>
        </div>
    </div>

    <?php if ($save_msg): ?>
    <div style="padding:12px 16px;margin-bottom:15px;border-radius:8px;background:rgba(76,175,80,0.15);color:#4CAF50;border:1px solid rgba(76,175,80,0.3);font-size:13px;">
        <i class="fas fa-check-circle"></i> <?= boxui_e($save_msg) ?>
    </div>
    <?php endif; ?>

    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
        <form method="POST" action="/tools/box_settings_handler.php">
            <input type="hidden" name="type_comment" value="comment">

            <!-- Toggle (bool) settings -->
            <?php foreach ($bool_keys as $key): ?>
            <?php $val = isset($settings[$key]) ? $settings[$key] : 'false'; ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #222;">
                <div>
                    <span style="font-size:13px;color:#ddd;"><?= boxui_e($key) ?></span>
                </div>
                <label style="position:relative;display:inline-block;width:44px;height:24px;">
                    <input type="hidden" name="type_<?= $key ?>" value="bool">
                    <input type="checkbox" name="<?= $key ?>" <?= $val === 'true' ? 'checked' : '' ?>
                           style="opacity:0;width:0;height:0;"
                           onchange="this.parentElement.querySelector('.slider').classList.toggle('active')">
                    <span class="slider" style="position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:<?= $val === 'true' ? '#4CAF50' : '#555' ?>;border-radius:24px;transition:0.3s;">
                        <span style="position:absolute;width:20px;height:20px;border-radius:50%;background:#fff;top:2px;<?= $val === 'true' ? 'right:2px;' : 'left:2px;' ?>transition:0.3s;"></span>
                    </span>
                </label>
            </div>
            <?php endforeach; ?>

            <!-- Dropdown settings -->
            <?php foreach ($dropdown_keys as $key => $options): ?>
            <?php $val = $settings[$key] ?? $options[0]; ?>
            <div style="padding:10px 0;border-bottom:1px solid #222;">
                <label style="display:block;font-size:12px;color:#888;margin-bottom:4px;"><?= boxui_e($key) ?></label>
                <input type="hidden" name="type_<?= $key ?>" value="dropdown">
                <select name="<?= $key ?>" style="width:100%;padding:8px;border-radius:6px;border:1px solid var(--border,#333);background:var(--bg-primary,#0d0d0d);color:#fff;font-size:13px;">
                    <?php foreach ($options as $opt): ?>
                    <option value="<?= boxui_e($opt) ?>" <?= $val === $opt ? 'selected' : '' ?>><?= boxui_e($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endforeach; ?>

            <!-- Text input settings -->
            <?php foreach ($form_keys as $key): ?>
            <?php $val = $settings[$key] ?? ''; ?>
            <div style="padding:10px 0;border-bottom:1px solid #222;">
                <label style="display:block;font-size:12px;color:#888;margin-bottom:4px;"><?= boxui_e($key) ?></label>
                <input type="hidden" name="type_<?= $key ?>" value="form">
                <input type="text" name="<?= $key ?>" value="<?= boxui_e($val) ?>"
                       style="width:100%;padding:8px;border-radius:6px;border:1px solid var(--border,#333);background:var(--bg-primary,#0d0d0d);color:#fff;font-size:13px;">
            </div>
            <?php endforeach; ?>

            <button type="submit" style="margin-top:15px;padding:10px 24px;border:none;border-radius:8px;background:var(--accent,#FECA0A);color:#000;font-size:14px;font-weight:600;cursor:pointer;width:100%;">
                <i class="fas fa-save"></i> Save Settings
            </button>
        </form>
    </div>

    <div style="margin-top:8px;font-size:11px;color:#555;text-align:center;">
        File: <?= $ini_path ?>
    </div>
</div>
