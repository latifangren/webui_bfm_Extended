<?php
// Pastikan server sudah terinstall ADB dan device terhubung via USB

$ADB_PATH = '/data/data/com.termux/files/usr/bin/adb';

function get_adb_devices() {
    global $ADB_PATH;
    putenv('TMPDIR=/data/local/tmp');
    $output = shell_exec($ADB_PATH . ' devices 2>&1');
    return htmlspecialchars($output);
}

function enable_adb_wifi_device() {
    global $ADB_PATH;
    putenv('TMPDIR=/data/local/tmp');
    $out = shell_exec($ADB_PATH . ' shell su -c "setprop service.adb.tcp.port 5555" && ' . $ADB_PATH . ' shell su -c "setprop persist.sys.usb.config adb" && ' . $ADB_PATH . ' tcpip 5555 2>&1');
    return $out;
}

function disable_adb_wifi_device() {
    global $ADB_PATH;
    putenv('TMPDIR=/data/local/tmp');
    $out = shell_exec($ADB_PATH . ' shell su -c "setprop service.adb.tcp.port -1" && ' . $ADB_PATH . ' shell su -c "setprop persist.sys.usb.config adb" && ' . $ADB_PATH . ' usb 2>&1');
    return $out;
}

function check_adb_status() {
    global $ADB_PATH;
    putenv('TMPDIR=/data/local/tmp');
    $out = shell_exec($ADB_PATH . ' shell su -c "getprop sys.usb.state" 2>&1');
    return $out;
}

function kill_adb_server() {
    global $ADB_PATH;
    putenv('TMPDIR=/data/local/tmp');
    $out = shell_exec($ADB_PATH . ' kill-server 2>&1');
    return $out;
}

function start_adb_server() {
    global $ADB_PATH;
    putenv('TMPDIR=/data/local/tmp');
    $out = shell_exec($ADB_PATH . ' start-server 2>&1');
    return $out;
}

function get_android_prop($prop) {
    global $ADB_PATH;
    putenv('TMPDIR=/data/local/tmp');
    $out = shell_exec($ADB_PATH . ' shell su -c "getprop ' . escapeshellarg($prop) . '" 2>&1');
    return trim($out);
}

function set_android_prop($prop, $value) {
    global $ADB_PATH;
    putenv('TMPDIR=/data/local/tmp');
    $out = shell_exec($ADB_PATH . ' shell su -c "setprop ' . escapeshellarg($prop) . ' ' . escapeshellarg($value) . '" 2>&1');
    return $out;
}

function enable_self_adb_wifi() {
    global $ADB_PATH;
    putenv('TMPDIR=/data/local/tmp');
    $out = '';
    $cmds = [
        'su -c "setprop service.adb.tcp.port 5555"',
        'su -c "setprop persist.sys.usb.config adb"',
        'su -c "stop adbd"',
        'su -c "start adbd"',
    ];
    foreach ($cmds as $cmd) {
        $res = shell_exec($cmd . ' 2>&1');
        $out .= "$cmd\n$res\n";
    }
    sleep(2);
    $connect_cmd = $ADB_PATH . ' connect 127.0.0.1:5555';
    $connect_res = shell_exec($connect_cmd . ' 2>&1');
    $out .= "$connect_cmd\n$connect_res\n";
    return $out;
}

$props = [
    'persist.service.adb.enable',
    'persist.service.debuggable',
    'persist.sys.usb.config',
    'service.adb.tcp.port',
];

$prop_values = [];
foreach ($props as $prop) {
    $prop_values[$prop] = get_android_prop($prop);
}

$status = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enable_self_adb_wifi'])) {
        $status = enable_self_adb_wifi();
    } elseif (isset($_POST['enable_wifi'])) {
        $status = enable_adb_wifi_device();
    } elseif (isset($_POST['disable_wifi'])) {
        $status = disable_adb_wifi_device();
    } elseif (isset($_POST['check_status'])) {
        $status = check_adb_status();
    } elseif (isset($_POST['kill_adb'])) {
        $status = kill_adb_server();
    } elseif (isset($_POST['start_adb'])) {
        $status = start_adb_server();
    } elseif (isset($_POST['set_prop'])) {
        $prop = $_POST['prop_name'] ?? '';
        $val = $_POST['prop_value'] ?? '';
        if ($prop && $val !== '') {
            $status = set_android_prop($prop, $val);
            // Refresh value after set
            $prop_values[$prop] = get_android_prop($prop);
        }
    }
}

$status .= shell_exec('whoami');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>ADB Tools Android</title>
    <style>
        body { font-family: Arial, sans-serif; background: #181c24; color: #feca0a; }
        .container { max-width: 700px; margin: 40px auto; background: #23272f; padding: 24px; border-radius: 10px; box-shadow: 0 2px 8px #0005; }
        h2 { color: #feca0a; }
        label, input, button, select { font-size: 1rem; }
        input, button, select { padding: 6px 12px; border-radius: 4px; border: none; }
        input, select { margin-right: 8px; }
        button { background: #feca0a; color: #23272f; cursor: pointer; margin-right: 8px; margin-bottom: 8px; }
        button:hover { background: #ffd84a; }
        pre { background: #11141a; color: #fff; padding: 12px; border-radius: 6px; overflow-x: auto; }
        .row { margin-bottom: 18px; }
        .btn-group { margin-bottom: 18px; }
        table { width: 100%; background: #181c24; color: #feca0a; border-radius: 6px; margin-bottom: 18px; }
        th, td { padding: 8px 10px; text-align: left; }
        th { background: #23272f; }
        tr:nth-child(even) { background: #23272f; }
    </style>
</head>
<body>
<div class="container">
    <h2>ADB Tools Android (Root)</h2>
    <div class="btn-group">
        <form method="post" style="display:inline;">
            <button type="submit" name="enable_self_adb_wifi">Aktifkan ADB di HP Ini (localhost)</button>
        </form>
        <form method="post" style="display:inline;">
            <button type="submit" name="enable_wifi">Aktifkan ADB over WiFi</button>
        </form>
        <form method="post" style="display:inline;">
            <button type="submit" name="disable_wifi">Nonaktifkan ADB over WiFi (USB Mode)</button>
        </form>
        <form method="post" style="display:inline;">
            <button type="submit" name="check_status">Cek Status ADB</button>
        </form>
        <form method="post" style="display:inline;">
            <button type="submit" name="kill_adb">Kill ADB Server</button>
        </form>
        <form method="post" style="display:inline;">
            <button type="submit" name="start_adb">Start ADB Server</button>
        </form>
        <button onclick="window.location.reload();" style="background:#5e72e4;color:#fff;" type="button">Refresh</button>
        <button onclick="loadContent('http://<?php echo $p; ?>:3001');" style="background:#23272f;color:#feca0a;" type="button">Terminal</button>
    </div>
    <div class="row">
        <h3>Status Properti Sistem Android</h3>
        <form method="post" style="margin-bottom:12px;">
            <table>
                <tr><th>Property</th><th>Value</th><th>Ubah Nilai</th></tr>
                <?php foreach ($props as $prop): ?>
                <tr>
                    <td><?php echo htmlspecialchars($prop); ?></td>
                    <td><?php echo htmlspecialchars($prop_values[$prop]); ?></td>
                    <td>
                        <input type="hidden" name="prop_name" value="<?php echo htmlspecialchars($prop); ?>">
                        <input type="text" name="prop_value" value="<?php echo htmlspecialchars($prop_values[$prop]); ?>">
                        <button type="submit" name="set_prop">Set</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </form>
    </div>
    <div class="row">
        <h3>Status Device ADB:</h3>
        <pre><?php echo get_adb_devices(); ?></pre>
    </div>
    <?php if ($status): ?>
    <div class="row">
        <h3>Output Perintah:</h3>
        <pre><?php echo htmlspecialchars($status); ?></pre>
    </div>
    <?php endif; ?>
</div>
</body>
</html> 