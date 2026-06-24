<?php
/**
 * Network Monitor — Ping view
 * 
 * Refactored from tools/network_monitor.php.
 * Pure view — logic in NetworkService.
 */
use BoxUI\Features\Network\NetworkService;

$ping_target = $_GET['target'] ?? '8.8.8.8';
$ping_count = (int) ($_GET['count'] ?? 4);
$ping_count = max(1, min(10, $ping_count));

$result = NetworkService::ping($ping_target, $ping_count);
?>
<style>
:root {
    --bg-primary: #0a0a0a;
    --bg-secondary: #111;
    --text-primary: #F1F1F1;
    --accent: #FECA0A;
    --border: #333;
    --success: #4CAF50;
    --danger: #ff4444;
}
body {
    font-family: 'Rajdhani', sans-serif;
    margin: 0;
    padding: 20px;
    background: var(--bg-primary);
    color: var(--text-primary);
}
.container {
    max-width: 600px;
    margin: 0 auto;
}
.card {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
}
.card h2 {
    margin: 0 0 12px 0;
    font-size: 18px;
    color: var(--accent);
    font-family: 'Orbitron', monospace;
}
.form-row {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}
.form-row input {
    flex: 1;
    padding: 10px 14px;
    background: #1a1a1a;
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 14px;
}
.form-row button {
    padding: 10px 20px;
    background: var(--accent);
    color: #000;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
    font-family: 'Orbitron', monospace;
    text-transform: uppercase;
    font-size: 12px;
}
.stat-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 15px;
}
.stat-item {
    text-align: center;
    padding: 10px;
    background: #1a1a1a;
    border-radius: 8px;
}
.stat-item .label {
    font-size: 11px;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.stat-item .value {
    font-size: 20px;
    font-weight: 700;
    color: var(--accent);
    margin-top: 4px;
}
.result-list {
    margin-top: 15px;
}
.result-item {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    border-bottom: 1px solid #222;
    font-size: 14px;
    color: #ccc;
}
</style>

<div class="container">
    <div class="card">
        <h2>📡 Network Monitor</h2>
        <form method="GET" class="form-row">
            <input type="text" name="target" value="<?= boxui_e($ping_target) ?>" placeholder="Target (IP atau hostname)">
            <input type="number" name="count" value="<?= $ping_count ?>" min="1" max="10" style="max-width:80px;">
            <button type="submit">Ping</button>
        </form>

        <?php if (!empty($result['stats'])): ?>
        <div class="stat-grid">
            <div class="stat-item">
                <div class="label">Min</div>
                <div class="value"><?= boxui_e(number_format($result['stats']['min'] ?? 0, 1)) ?>ms</div>
            </div>
            <div class="stat-item">
                <div class="label">Avg</div>
                <div class="value"><?= boxui_e(number_format($result['stats']['avg'] ?? 0, 1)) ?>ms</div>
            </div>
            <div class="stat-item">
                <div class="label">Max</div>
                <div class="value"><?= boxui_e(number_format($result['stats']['max'] ?? 0, 1)) ?>ms</div>
            </div>
            <div class="stat-item">
                <div class="label">Loss</div>
                <div class="value" style="color:<?= ($result['stats']['loss'] ?? 100) > 0 ? 'var(--danger)' : 'var(--success)' ?>">
                    <?= boxui_e($result['stats']['loss'] ?? 100) ?>%
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($result['results'])): ?>
        <div class="result-list">
            <h3 style="font-size:14px;color:#888;margin:0 0 8px 0;">Results (<?= count($result['results']) ?> replies)</h3>
            <?php foreach ($result['results'] as $i => $ms): ?>
            <div class="result-item">
                <span>Reply <?= $i + 1 ?></span>
                <span><?= number_format($ms, 1) ?> ms</span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($result['results']) && !empty($result['raw'])): ?>
        <p style="color:#ff4444;font-size:13px;">No replies from <?= boxui_e($ping_target) ?></p>
        <pre style="font-size:11px;color:#888;max-height:200px;overflow:auto;"><?= boxui_e($result['raw']) ?></pre>
        <?php endif; ?>
    </div>
</div>
