<?php
session_start();

// Fungsi untuk mendapatkan daftar perangkat yang terkoneksi
function getConnectedDevices() {
    $devices = array();
    
    // Menggunakan perintah ip neigh untuk mendapatkan informasi perangkat terkoneksi
    $output = shell_exec('ip -4 neigh');
    
    if ($output) {
        $lines = explode("\n", $output);
        
        foreach ($lines as $line) {
            if (!empty($line)) {
                $parts = preg_split('/\s+/', $line);
                // Memastikan hanya alamat IPv4 yang valid yang ditambahkan
                if (count($parts) >= 5 && filter_var($parts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $devices[] = array(
                        'ip' => $parts[0],
                        'mac' => $parts[4],
                        'signal' => '-', // Tidak ada informasi sinyal dari ip neigh
                        'connected_time' => '-', // Tidak ada informasi waktu dari ip neigh
                    );
                }
            }
        }
    }
    
    return $devices;
}

// Fungsi untuk mengecek status blokir
function isBlocked($ip) {
    if (file_exists('blocked_users.txt')) {
        $blocked_ips = file('blocked_users.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return in_array($ip, $blocked_ips);
    }
    return false;
}

// Fungsi untuk mengecek status limit
function isLimited($ip) {
    if (file_exists('limited_users.txt')) {
        $limited_ips = file('limited_users.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($limited_ips as $line) {
            $parts = explode(':', $line);
            if ($parts[0] === $ip) {
                return true;
            }
        }
    }
    return false;
}

// Fungsi untuk mendapatkan nilai limit bandwidth
function getLimitValue($ip) {
    if (file_exists('limited_users.txt')) {
        $limited_ips = file('limited_users.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($limited_ips as $line) {
            $parts = explode(':', $line);
            if ($parts[0] === $ip) {
                return $parts[1];
            }
        }
    }
    return null;
}

// Fungsi untuk memblokir perangkat
function blockDevice($ip) {
    // Simpan IP yang diblokir ke file
    file_put_contents('blocked_users.txt', "$ip\n", FILE_APPEND);
    
    // Blokir menggunakan iptables
    shell_exec("iptables -A INPUT -s " . $ip . " -j DROP");
    shell_exec("iptables -A FORWARD -s " . $ip . " -j DROP");
    shell_exec("iptables -A OUTPUT -d " . $ip . " -j DROP");
    
    // Putuskan koneksi WiFi
    shell_exec("nmcli dev wifi disconnect " . $ip);
    
    return true;
}

// Fungsi untuk membatalkan blokir perangkat
function unblockDevice($ip) {
    // Hapus IP dari file blocked_users.txt
    $blocked_ips = file('blocked_users.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $blocked_ips = array_diff($blocked_ips, array($ip));
    file_put_contents('blocked_users.txt', implode("\n", $blocked_ips) . "\n");
    
    // Hapus aturan iptables
    shell_exec("iptables -D INPUT -s " . $ip . " -j DROP");
    shell_exec("iptables -D FORWARD -s " . $ip . " -j DROP");
    shell_exec("iptables -D OUTPUT -d " . $ip . " -j DROP");
    
    return true;
}

// Fungsi untuk membatasi bandwidth
function limitBandwidth($ip, $download) {
    // Menggunakan tc untuk membatasi bandwidth
    $download = intval($download);
    
    // Simpan IP dan limit ke file
    $limited_ips = file_exists('limited_users.txt') ? 
        file('limited_users.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : 
        array();
    
    // Hapus entri lama jika ada
    $limited_ips = array_filter($limited_ips, function($line) use ($ip) {
        return strpos($line, $ip . ':') !== 0;
    });
    
    // Tambahkan entri baru
    $limited_ips[] = "$ip:$download";
    file_put_contents('limited_users.txt', implode("\n", $limited_ips) . "\n");
    
    // Hapus aturan lama jika ada
    shell_exec("tc qdisc del dev wlan0 root");
    
    // Buat aturan baru
    shell_exec("tc qdisc add dev wlan0 root handle 1: htb default 12");
    shell_exec("tc class add dev wlan0 parent 1: classid 1:1 htb rate {$download}kbps");
    shell_exec("tc filter add dev wlan0 protocol ip parent 1:0 prio 1 u32 match ip dst {$ip} flowid 1:1");
    
    return true;
}

// Fungsi untuk membatalkan limit bandwidth
function unlimitBandwidth($ip) {
    // Hapus IP dari file limited_users.txt
    if (file_exists('limited_users.txt')) {
        $limited_ips = file('limited_users.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $limited_ips = array_filter($limited_ips, function($line) use ($ip) {
            return strpos($line, $ip . ':') !== 0;
        });
        file_put_contents('limited_users.txt', implode("\n", $limited_ips) . "\n");
    }
    
    // Hapus aturan tc untuk IP tertentu
    shell_exec("tc filter del dev wlan0 protocol ip parent 1:0 prio 1 u32 match ip dst {$ip}");
    
    return true;
}

$devices = getConnectedDevices();
$search = isset($_GET['search']) ? $_GET['search'] : '';
$message = '';

// Filter devices berdasarkan pencarian
if (!empty($search)) {
    $devices = array_filter($devices, function($device) use ($search) {
        return stripos($device['ip'], $search) !== false || 
               stripos($device['mac'], $search) !== false;
    });
}

// Handle aksi blokir
if (isset($_POST['block']) && isset($_POST['ip'])) {
    $ip = $_POST['ip'];
    if (blockDevice($ip)) {
        $message = "Perangkat dengan IP $ip berhasil diblokir dan diputuskan dari hotspot";
    } else {
        $message = "Gagal memblokir perangkat";
    }
}

// Handle aksi unblock
if (isset($_POST['unblock']) && isset($_POST['ip'])) {
    $ip = $_POST['ip'];
    if (unblockDevice($ip)) {
        $message = "Perangkat dengan IP $ip berhasil dibuka blokirnya";
    } else {
        $message = "Gagal membuka blokir perangkat";
    }
}

// Handle aksi limiter
if (isset($_POST['limit']) && isset($_POST['ip'])) {
    $ip = $_POST['ip'];
    $download = isset($_POST['download']) ? $_POST['download'] : 1024; // Default 1Mbps
    
    if (limitBandwidth($ip, $download)) {
        $message = "Bandwidth untuk IP $ip berhasil dibatasi (Download: {$download}Kbps)";
    } else {
        $message = "Gagal membatasi bandwidth";
    }
}

// Handle aksi unlimit
if (isset($_POST['unlimit']) && isset($_POST['ip'])) {
    $ip = $_POST['ip'];
    if (unlimitBandwidth($ip)) {
        $message = "Bandwidth untuk IP $ip berhasil dibuka limitnya";
    } else {
        $message = "Gagal membuka limit bandwidth";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Hotspot</title>
    <style>
        @font-face {
            font-family: 'Material Icons';
            font-style: normal;
            font-weight: 400;
            src: url('../webui/fonts/MaterialIcons-Regular.woff2') format('woff2'),
                 url('../webui/fonts/MaterialIcons-Regular.woff') format('woff');
        }

        .material-icons {
            font-family: 'Material Icons';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            font-feature-settings: 'liga';
        }

        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #FFFFFF;
            margin: 0;
            padding: 20px;
            color: #333333;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #FFFFFF;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 80px;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #FECA0A;
            margin-bottom: 30px;
            font-weight: 700;
            font-size: 28px;
            position: relative;
            padding-bottom: 15px;
            text-shadow: 0 2px 10px rgba(254, 202, 10, 0.3);
            letter-spacing: 1px;
        }

        h1:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, rgba(254, 202, 10, 0.1), #FECA0A, rgba(254, 202, 10, 0.1));
            border-radius: 2px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            font-size: 14px;
            background-color: #FFFFFF;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 16px;
            text-align: left;
        }

        th {
            background-color: rgba(254, 202, 10, 0.1);
            color: #333333;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
        }

        td {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        tr:hover {
            background-color: rgba(254, 202, 10, 0.05);
        }

        .button {
            display: inline-block;
            padding: 8px 16px;
            margin: 4px;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .refresh-button {
            background-color: #FECA0A;
            color: #000000;
        }

        .refresh-button:hover {
            background-color: #e5b609;
        }

        .block-button {
            background-color: #e74c3c;
            color: white;
        }

        .block-button:hover {
            background-color: #c0392b;
        }

        .unblock-button {
            background-color: #27ae60;
            color: white;
        }

        .unblock-button:hover {
            background-color: #219a52;
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .manage-button {
            background-color: #FECA0A;
            color: #000000;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 5px;
        }

        .manage-button:hover {
            background-color: #e5b609;
        }

        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
        }

        .success {
            background-color: rgba(39, 174, 96, 0.1);
            color: #27ae60;
            border: 1px solid rgba(39, 174, 96, 0.3);
        }

        .error {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .limit-button {
            background-color: #FECA0A;
            color: #000000;
            padding: 8px 16px;
            font-size: 12px;
        }

        .limit-button:hover {
            background-color: #e5b609;
        }

        .unlimit-button {
            background-color: #1abc9c;
            color: white;
            padding: 8px 16px;
            font-size: 12px;
        }

        .unlimit-button:hover {
            background-color: #16a085;
        }

        .search-box {
            width: 100%;
            padding: 15px;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #FFFFFF;
            color: #333333;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .search-box:focus {
            border-color: #FECA0A;
            outline: none;
            box-shadow: 0 2px 15px rgba(254, 202, 10, 0.2);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin: 0 4px;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-blocked {
            background-color: rgba(231, 76, 60, 0.8);
            color: white;
            border: 1px solid rgba(254, 202, 10, 0.3);
        }

        .status-limited {
            background-color: rgba(254, 202, 10, 0.8);
            color: #000000;
            border: 1px solid rgba(254, 202, 10, 0.3);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 1000;
        }

        .modal-content {
            background-color: #FFFFFF;
            margin: 10% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            position: relative;
            animation: modalSlideIn 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 28px;
            color: #FECA0A;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close:hover {
            color: #e5b609;
            transform: rotate(90deg);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333333;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #FFFFFF;
            color: #333333;
        }

        .form-group input:focus {
            border-color: #FECA0A;
            outline: none;
            box-shadow: 0 2px 15px rgba(254, 202, 10, 0.2);
        }

        /* Footer Styles */
        .footer {
            background-color: #FFFFFF;
            padding: 20px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
            left: 0;
            z-index: 1000;
            box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.1);
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-content {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .button-group {
                flex-direction: column;
                gap: 10px;
            }

            .refresh-button, .theme-toggle {
                width: 100%;
            }

            table {
                font-size: 13px;
            }

            th, td {
                padding: 12px 8px;
            }

            .button {
                font-size: 12px;
                padding: 6px 12px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }

            h1 {
                font-size: 20px;
            }

            th, td {
                padding: 8px 6px;
                font-size: 12px;
            }

            td .button {
                margin: 2px 0;
                display: block;
                width: auto;
            }

            form[style*="display: inline"] {
                display: block !important;
                margin: 2px 0;
            }
        }

        /* Footer Brand Styles */
        .footer-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .copyright {
            color: #FECA0A;
            font-weight: 600;
            text-shadow: 0 0 10px rgba(254, 202, 10, 0.3);
        }

        .brand-name {
            color: #FECA0A;
            font-weight: 600;
            font-size: 1.1em;
        }

        .heart-icon {
            color: #FECA0A;
            animation: heartBeat 1.5s ease infinite;
            font-size: 18px !important;
        }

        .by-text {
            color: #F1F1F1;
            font-weight: 500;
        }

        .telegram-button {
            background-color: #1a1a1a;
            padding: 8px 16px;
            border-radius: 20px;
            color: #FECA0A;
            font-weight: 500;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(254, 202, 10, 0.3);
        }

        .telegram-button:hover {
            background-color: rgba(254, 202, 10, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(254, 202, 10, 0.2);
        }

        @keyframes heartBeat {
            0% { transform: scale(1); }
            14% { transform: scale(1.3); }
            28% { transform: scale(1); }
            42% { transform: scale(1.3); }
            70% { transform: scale(1); }
        }

        .theme-toggle {
            background-color: #FECA0A;
            color: #000000;
            padding: 12px 24px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .theme-toggle:hover {
            background-color: #e5b609;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
        }

        .theme-toggle i {
            font-size: 20px;
            transition: transform 0.5s ease;
        }

        body.dark-mode {
            background-color: #000000;
            color: #F1F1F1;
        }

        body.dark-mode .container {
            background-color: #1a1a1a;
            border: 1px solid rgba(254, 202, 10, 0.2);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        body.dark-mode table {
            background-color: #1a1a1a;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        body.dark-mode th {
            background-color: rgba(254, 202, 10, 0.1);
            color: #FECA0A;
        }

        body.dark-mode td {
            color: #F1F1F1;
            border-bottom: 1px solid rgba(254, 202, 10, 0.1);
        }

        body.dark-mode tr:hover {
            background-color: rgba(254, 202, 10, 0.05);
        }

        body.dark-mode .search-box {
            background-color: #1a1a1a;
            border-color: rgba(254, 202, 10, 0.2);
            color: #F1F1F1;
        }

        body.dark-mode .modal-content {
            background-color: #1a1a1a;
            color: #F1F1F1;
            border: 1px solid rgba(254, 202, 10, 0.3);
        }

        body.dark-mode .form-group input {
            background-color: #1a1a1a;
            border-color: rgba(254, 202, 10, 0.2);
            color: #F1F1F1;
        }

        body.dark-mode .form-group label {
            color: #FECA0A;
        }

        body.dark-mode .theme-toggle {
            background-color: #1a1a1a;
            color: #FECA0A;
            border: 1px solid rgba(254, 202, 10, 0.3);
        }

        body.dark-mode .theme-toggle:hover {
            background-color: rgba(254, 202, 10, 0.1);
        }

        body.dark-mode h2 {
            color: #FECA0A;
        }

        body.dark-mode .close {
            color: #FECA0A;
        }

        body.dark-mode .copyright,
        body.dark-mode .brand-name,
        body.dark-mode .heart-icon {
            color: #FECA0A;
        }

        body.dark-mode .by-text {
            color: #F1F1F1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Perangkat Terkoneksi ke Hotspot</h1>
        
        <div class="button-group">
            <button class="button refresh-button" onclick="window.location.reload()">Refresh List</button>
            <button class="button theme-toggle" onclick="toggleTheme()">
                <i class="material-icons" id="theme-icon">dark_mode</i>
                <span id="theme-text">Mode Gelap</span>
            </button>
        </div>

        <form method="GET" action="">
            <input type="text" name="search" class="search-box" placeholder="Cari berdasarkan IP atau MAC..." value="<?php echo htmlspecialchars($search); ?>">
        </form>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'berhasil') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>IP Address</th>
                    <th>MAC Address</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (empty($devices)) {
                    echo '<tr><td colspan="5" style="text-align: center;"><i class="material-icons" style="vertical-align: middle; margin-right: 5px;">info</i>Tidak ada perangkat yang terkoneksi</td></tr>';
                } else {
                    $counter = 1;
                    foreach ($devices as $device) {
                        $status_badges = '';
                        if (isBlocked($device['ip'])) {
                            $status_badges .= '<span class="status-badge status-blocked"><i class="material-icons tiny" style="font-size: 12px; vertical-align: middle; margin-right: 3px;">block</i>Diblokir</span>';
                        }
                        if (isLimited($device['ip'])) {
                            $limit_value = getLimitValue($device['ip']);
                            $status_badges .= '<span class="status-badge status-limited"><i class="material-icons tiny" style="font-size: 12px; vertical-align: middle; margin-right: 3px;">speed</i>Dibatasi ' . $limit_value . ' Kbps</span>';
                        }
                        
                        if (empty($status_badges)) {
                            $status_badges = '<span class="status-badge" style="background-color: rgba(39, 174, 96, 0.8); color: white;"><i class="material-icons tiny" style="font-size: 12px; vertical-align: middle; margin-right: 3px;">check_circle</i>Aktif</span>';
                        }
                        
                        echo "<tr>
                                <td>{$counter}</td>
                                <td>" . htmlspecialchars($device['ip']) . "</td>
                                <td>" . htmlspecialchars($device['mac']) . "</td>
                                <td>{$status_badges}</td>
                                <td>
                                    <button class='button limit-button' onclick='openLimitModal(\"" . htmlspecialchars($device['ip']) . "\")'>
                                        <i class='material-icons tiny' style='vertical-align: middle; margin-right: 3px;'>speed</i>Limit
                                    </button>
                                    <form method='POST' style='display: inline;'>
                                        <input type='hidden' name='ip' value='" . htmlspecialchars($device['ip']) . "'>
                                        <button type='submit' name='unlimit' class='button unlimit-button' onclick='return confirm(\"Apakah Anda yakin ingin membuka limit bandwidth untuk perangkat ini?\")'>
                                            <i class='material-icons tiny' style='vertical-align: middle; margin-right: 3px;'>settings_ethernet</i>Buka Limit
                                        </button>
                                    </form>
                                    <form method='POST' style='display: inline;'>
                                        <input type='hidden' name='ip' value='" . htmlspecialchars($device['ip']) . "'>
                                        <button type='submit' name='block' class='button block-button' onclick='return confirm(\"Apakah Anda yakin ingin memblokir perangkat ini?\")'>
                                            <i class='material-icons tiny' style='vertical-align: middle; margin-right: 3px;'>block</i>Blokir
                                        </button>
                                    </form>
                                    <form method='POST' style='display: inline;'>
                                        <input type='hidden' name='ip' value='" . htmlspecialchars($device['ip']) . "'>
                                        <button type='submit' name='unblock' class='button unblock-button' onclick='return confirm(\"Apakah Anda yakin ingin membuka blokir perangkat ini?\")'>
                                            <i class='material-icons tiny' style='vertical-align: middle; margin-right: 3px;'>check_circle</i>Buka Blokir
                                        </button>
                                    </form>
                                </td>
                              </tr>";
                        $counter++;
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Modal Limiter -->
    <div id="limitModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeLimitModal()">&times;</span>
            <h2><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">speed</i>Batasi Bandwidth</h2>
            <form method="POST" action="">
                <input type="hidden" name="ip" id="limitIp">
                <div class="form-group">
                    <label for="download">Download (Kbps):</label>
                    <input type="number" id="download" name="download" value="1024" min="1" required>
                </div>
                <button type="submit" name="limit" class="button manage-button">
                    <i class="material-icons" style="vertical-align: middle; margin-right: 5px;">save</i>Terapkan Limit
                </button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-brand">
                    <span class="copyright">© 2025</span>
                    <span class="brand-name">Hotspot Manager</span>
                    <i class="material-icons tiny heart-icon">favorite</i>
                    <span class="by-text">by</span>
                    <a href="https://t.me/latifan_id" class="telegram-link" target="_blank">
                        <span class="telegram-button">
                            <i class="material-icons tiny" style="vertical-align: middle; margin-right: 3px;">send</i>
                            <span>@latifan_id</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto refresh setiap 30 detik
        setTimeout(function() {
            location.reload();
        }, 30000);

        // Fungsi untuk membuka modal limiter
        function openLimitModal(ip) {
            document.getElementById('limitModal').style.display = 'block';
            document.getElementById('limitIp').value = ip;
        }

        // Fungsi untuk menutup modal limiter
        function closeLimitModal() {
            document.getElementById('limitModal').style.display = 'none';
        }

        // Tutup modal jika user klik di luar modal
        window.onclick = function(event) {
            var modal = document.getElementById('limitModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Fungsi untuk toggle dark mode
        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('theme-icon');
            const themeText = document.getElementById('theme-text');
            
            body.classList.toggle('dark-mode');
            
            if (body.classList.contains('dark-mode')) {
                themeIcon.textContent = 'light_mode';
                themeText.textContent = 'Mode Terang';
                localStorage.setItem('theme', 'dark');
            } else {
                themeIcon.textContent = 'dark_mode';
                themeText.textContent = 'Mode Gelap';
                localStorage.setItem('theme', 'light');
            }
        }

        // Aktifkan dark mode hanya jika sebelumnya disimpan dalam localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            const themeIcon = document.getElementById('theme-icon');
            const themeText = document.getElementById('theme-text');
            
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
                
                if (themeIcon && themeText) {
                    themeIcon.textContent = 'light_mode';
                    themeText.textContent = 'Mode Terang';
                }
            } else {
                document.body.classList.remove('dark-mode');
                
                if (themeIcon && themeText) {
                    themeIcon.textContent = 'dark_mode';
                    themeText.textContent = 'Mode Gelap';
                }
            }
        });

        // Tambahkan efek ripple ke semua tombol
        const buttons = document.querySelectorAll('.button');
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                const x = e.clientX - e.target.offsetLeft;
                const y = e.clientY - e.target.offsetTop;
                
                const ripple = document.createElement('span');
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    </script>

    <!-- Add Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</body>
</html>  