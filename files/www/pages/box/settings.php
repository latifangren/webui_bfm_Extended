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

<div class="mb-8 border-b-2 border-border pb-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center bg-primary border-2 border-border text-black font-bold">
            ⚙️
        </div>
        <h1 class="font-head text-2xl uppercase tracking-wider text-primary">BOX Settings</h1>
    </div>
    <span class="text-xs font-mono bg-border/20 text-[#aeaeae] px-3 py-1 border border-border/20">BFR CONFIG</span>
</div>

<?php if ($save_msg): ?>
<div class="border-2 border-[#0ca95b] bg-black/60 text-[#0ca95b] px-4 py-3 mb-6 font-sans text-xs font-bold uppercase tracking-wider flex items-center gap-2 shadow-[2px_2px_0px_0px_rgba(12,169,91,0.2)]">
    <i class="fas fa-check-circle"></i> <?= boxui_e($save_msg) ?>
</div>
<?php endif; ?>

<div class="border-2 border-border bg-[#1a1a1a] p-6 mb-4 shadow-[4px_4px_0px_0px_rgba(249,244,218,1)] max-w-2xl mx-auto">
    <form hx-post="/tools/box/box_settings_handler.php" hx-target="#content">
        <input type="hidden" name="type_comment" value="comment">

        <!-- Toggle (bool) settings -->
        <div class="mb-6 border-b border-[#f9f4da]/15 pb-4">
            <h3 class="font-head text-xs text-[#aeaeae] uppercase tracking-widest mb-4">Toggle Switches</h3>
            <?php foreach ($bool_keys as $key): ?>
            <?php $val = isset($settings[$key]) ? $settings[$key] : 'false'; ?>
            <div class="flex items-center justify-between py-3 border-b border-[#f9f4da]/10 last:border-0 hover:bg-black/10 px-2 transition-colors">
                <span class="text-xs font-mono text-[#f9f4da]"><?= boxui_e($key) ?></span>
                <label class="relative inline-block w-12 h-6 border-2 border-border cursor-pointer select-none">
                    <input type="hidden" name="type_<?= $key ?>" value="bool">
                    <input type="checkbox" name="<?= $key ?>" <?= $val === 'true' ? 'checked' : '' ?>
                           class="absolute opacity-0 w-0 h-0"
                           onchange="this.nextElementSibling.querySelector('.thumb').style.left = this.checked ? '24px' : '2px'; this.nextElementSibling.style.backgroundColor = this.checked ? '#0ca95b' : '#333'">
                    <span class="absolute inset-0 transition-colors" style="background-color: <?= $val === 'true' ? '#0ca95b' : '#333' ?>;">
                        <span class="thumb absolute w-4 h-4 bg-[#f9f4da] border border-black transition-all" style="top: 2px; <?= $val === 'true' ? 'left: 24px;' : 'left: 2px;' ?>"></span>
                    </span>
                </label>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Dropdown settings -->
        <div class="mb-6 border-b border-[#f9f4da]/15 pb-4">
            <h3 class="font-head text-xs text-[#aeaeae] uppercase tracking-widest mb-4">Configurations</h3>
            <?php foreach ($dropdown_keys as $key => $options): ?>
            <?php $val = $settings[$key] ?? $options[0]; ?>
            <div class="py-3">
                <label class="block text-xs font-mono text-[#aeaeae] mb-2"><?= boxui_e($key) ?></label>
                <input type="hidden" name="type_<?= $key ?>" value="dropdown">
                <select name="<?= $key ?>" class="w-full px-4 py-2 border-2 border-border bg-black text-[#f9f4da] font-mono text-sm focus:outline-none focus:border-primary transition-colors focus:ring-0">
                    <?php foreach ($options as $opt): ?>
                    <option class="bg-[#1a1a1a]" value="<?= boxui_e($opt) ?>" <?= $val === $opt ? 'selected' : '' ?>><?= boxui_e($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Text input settings -->
        <div class="mb-6">
            <h3 class="font-head text-xs text-[#aeaeae] uppercase tracking-widest mb-4">Values & Keys</h3>
            <?php foreach ($form_keys as $key): ?>
            <?php $val = $settings[$key] ?? ''; ?>
            <div class="py-3">
                <label class="block text-xs font-mono text-[#aeaeae] mb-2"><?= boxui_e($key) ?></label>
                <input type="hidden" name="type_<?= $key ?>" value="form">
                <input type="text" name="<?= $key ?>" value="<?= boxui_e($val) ?>"
                       class="w-full px-4 py-2 border-2 border-border bg-black text-[#f9f4da] font-mono text-xs focus:outline-none focus:border-primary transition-colors focus:ring-0">
            </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="w-full border-2 border-border bg-[#fcba28] text-black font-head font-bold uppercase tracking-wider py-3 mt-6 hover:bg-[#e0a41d] active:translate-x-[2px] active:translate-y-[2px] shadow-[4px_4px_0px_0px_rgba(249,244,218,1)] active:shadow-[0px_0px_0px_0px_rgba(0,0,0,0)] transition-all flex items-center justify-center gap-2">
            <i class="fas fa-save"></i> Save Settings
        </button>
    </form>
</div>

<div class="mt-4 font-mono text-[9px] text-[#aeaeae]/60 text-center">
    Config location: <?= boxui_e($ini_path) ?>
</div>