<?php
/**
 * Ping Monitor — Continuous ping monitoring.
 * 
 * Pure view — logic in PingMonitorService.
 * Essential features only: status, start/stop, log, chart.
 */
use BoxUI\Features\Network\PingMonitorService;

$running = PingMonitorService::isRunning();
$host = PingMonitorService::getMonitoredHost();
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
.container { max-width: 800px; margin: 0 auto; }
h1 {
    font-family: 'Orbitron', monospace; font-size: 20px; color: var(--accent);
    margin: 0 0 16px 0;
}
.card {
    background: var(--bg-s); border: 1px solid var(--border);
    border-radius: 10px; padding: 16px; margin-bottom: 12px;
}
.card-title {
    font-size: 15px; font-weight: 600; color: var(--accent);
    margin: 0 0 12px 0;
    font-family: 'Orbitron', monospace;
}
.controls { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
.btn {
    padding: 8px 18px; border: none; border-radius: 6px;
    font-family: 'Orbitron', monospace; font-size: 12px;
    font-weight: 600; cursor: pointer; text-transform: uppercase;
    transition: opacity .15s;
}
.btn:hover { opacity: .85; }
.btn:disabled { opacity: .4; cursor: not-allowed; }
.btn-start { background: var(--green); color: #000; }
.btn-stop { background: var(--red); color: #fff; }
.btn-restart { background: #FF9800; color: #000; }

.status-row { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 12px; }
.status-item { flex: 1; min-width: 120px; }
.status-label { font-size: 11px; color: #888; text-transform: uppercase; }
.status-value { font-size: 18px; font-weight: 700; font-family: 'Orbitron', monospace; }
.running { color: var(--green); }
.stopped { color: var(--red); }

pre.log {
    background: #000; color: #0f0; padding: 12px;
    border-radius: 6px; font-size: 12px; overflow-x: auto;
    min-height: 100px; max-height: 300px;
    white-space: pre-wrap; word-break: break-all;
    border: 1px solid #222;
    font-family: 'Courier New', monospace;
}
pre.log .dim { color: #555; }
pre.log .ok { color: #4CAF50; }
pre.log .fail { color: #ff4444; }

.chart-wrapper {
    position: relative; height: 120px;
    margin-bottom: 12px;
}
.stat-grid { display: flex; gap: 12px; flex-wrap: wrap; }
.stat-box {
    flex: 1; min-width: 80px; text-align: center;
    padding: 10px; background: #1a1a1a; border-radius: 8px;
}
.stat-box .num { font-size: 20px; font-weight: 700; font-family: 'Orbitron', monospace; }
.stat-box .lbl { font-size: 11px; color: #888; margin-top: 2px; }
#loading-msg {
    text-align: center; color: #888; padding: 40px 0; font-size: 14px;
}
</style>
<div class="container">
    <h1>Ping Monitor</h1>

    <!-- Controls -->
    <div class="card">
        <div class="card-title">Kontrol</div>
        <div class="controls">
            <form method="post" action="/tools/network/ping_monitor_handler.php" id="ping-form" style="display:flex;gap:8px;flex-wrap:wrap;">
                <input type="hidden" name="action" id="ping-action" value="">
                <button type="button" class="btn btn-start" id="btn-start" onclick="doAction('start')"<?= $running ? ' disabled' : '' ?>>Start</button>
                <button type="button" class="btn btn-stop" id="btn-stop" onclick="doAction('stop')"<?= !$running ? ' disabled' : '' ?>>Stop</button>
                <button type="button" class="btn btn-restart" id="btn-restart" onclick="doAction('restart')">Restart</button>
            </form>
        </div>

        <div class="status-row">
            <div class="status-item">
                <div class="status-label">Status</div>
                <div class="status-value <?= $running ? 'running' : 'stopped' ?>" id="stat-status">
                    <?= $running ? 'Running' : 'Stopped' ?>
                </div>
            </div>
            <div class="status-item">
                <div class="status-label">Host</div>
                <div class="status-value" id="stat-host"><?= htmlspecialchars($host) ?></div>
            </div>
            <div class="status-item">
                <div class="status-label">Last Update</div>
                <div class="status-value" style="font-size:13px;" id="stat-time">-</div>
            </div>
        </div>
    </div>

    <!-- Chart -->
    <div class="card">
        <div class="card-title">Grafik Ping</div>
        <div class="chart-wrapper">
            <canvas id="pingChart"></canvas>
        </div>
        <div class="stat-grid" id="stat-grid">
            <div class="stat-box"><div class="num" id="chart-total">0</div><div class="lbl">Total</div></div>
            <div class="stat-box"><div class="num" id="chart-ok" style="color:var(--green)">0</div><div class="lbl">Sukses</div></div>
            <div class="stat-box"><div class="num" id="chart-fail" style="color:var(--red)">0</div><div class="lbl">Gagal</div></div>
            <div class="stat-box"><div class="num" id="chart-uptime">0%</div><div class="lbl">Uptime</div></div>
        </div>
    </div>

    <!-- Log -->
    <div class="card">
        <div class="card-title">Log</div>
        <pre class="log" id="ping-log"><span class="dim">Menunggu data...</span></pre>
    </div>
</div>

<script src="/webui/js/chartjs/chart.min.js"></script>
<script>
var pingChart = null;
var refreshTimer = null;

function doAction(action) {
    var btn = document.getElementById('btn-' + (action === 'restart' ? 'restart' : action));
    btn.disabled = true;
    btn.textContent = '...';

    fetch('/tools/network/ping_monitor_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=' + action
    })
    .then(function(r) { return r.text(); })
    .then(function() {
        btn.disabled = false;
        btn.textContent = action.charAt(0).toUpperCase() + action.slice(1);
        refreshStatus();
    })
    .catch(function() {
        btn.disabled = false;
        btn.textContent = action.charAt(0).toUpperCase() + action.slice(1);
    });
}

function refreshStatus() {
    document.getElementById('loading-msg').style.display = 'block';
    fetch('/tools/network/ping_monitor_handler.php?action=get_status')
    .then(function(r) { return r.json(); })
    .then(function(data) {
        // Status
        var running = data.running;
        document.getElementById('stat-status').textContent = running ? 'Running' : 'Stopped';
        document.getElementById('stat-status').className = 'status-value ' + (running ? 'running' : 'stopped');
        document.getElementById('stat-host').textContent = data.host || '-';
        document.getElementById('stat-time').textContent = data.timestamp || '-';

        document.getElementById('btn-start').disabled = running;
        document.getElementById('btn-stop').disabled = !running;

        // Log
        var logEl = document.getElementById('ping-log');
        if (data.log) {
            logEl.textContent = data.log;
        }

        // Chart
        if (data.chart && data.chart.points) {
            updateChart(data.chart);
        }
    })
    .catch(function() {});
}

function updateChart(chartData) {
    var total = chartData.total || 0;
    var ok = chartData.success || 0;
    var fail = chartData.fail || 0;
    var uptime = chartData.uptime || 0;

    document.getElementById('chart-total').textContent = total;
    document.getElementById('chart-ok').textContent = ok;
    document.getElementById('chart-fail').textContent = fail;
    document.getElementById('chart-uptime').textContent = uptime + '%';

    if (total === 0) return;

    var labels = chartData.points.map(function(p) {
        return p.time ? p.time.substr(11, 5) : '';
    });
    var values = chartData.points.map(function(p) { return p.ok ? 1 : 0; });
    var colors = chartData.points.map(function(p) { return p.ok ? '#4CAF50' : '#ff4444'; });

    if (pingChart) { pingChart.destroy(); pingChart = null; }

    var ctx = document.getElementById('pingChart').getContext('2d');
    pingChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderColor: colors,
                borderWidth: 1,
                barPercentage: 0.6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 300 },
            scales: {
                y: { display: false, min: -0.1, max: 1.3 },
                x: {
                    ticks: { color: '#888', font: { size: 9 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 15 },
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ctx.parsed.y >= 1 ? 'OK' : 'FAIL';
                        }
                    }
                }
            }
        }
    });
}

// Auto refresh every 5 seconds
refreshTimer = setInterval(refreshStatus, 5000);

// Initial load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(refreshStatus, 500);
});
</script>
