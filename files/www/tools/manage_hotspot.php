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
    <title>Perangkat Terkoneksi ke Hotspot</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Default styles */
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding: 20px;
            color: #2c3e50;
            min-height: 100vh;
        }

        h1 {
            text-align: center;
            font-size: 2.4em;
            margin-bottom: 30px;
            position: relative;
            padding: 15px 0;
            background: linear-gradient(45deg, #FF6B6B, #4ECDC4, #45B7AF, #3498db);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-size: 300% 300%;
            animation: gradient-text 5s ease infinite;
            font-weight: 700;
            letter-spacing: 1px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        h1::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(45deg, #FF6B6B, #4ECDC4);
            border-radius: 2px;
        }

        @keyframes gradient-text {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        /* Dark mode adjustments */
        body.dark-mode h1 {
            background: linear-gradient(45deg, #FF8585, #5DDDD3, #4EC8BF, #3498db);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        body.dark-mode h1::after {
            background: linear-gradient(45deg, #FF8585, #5DDDD3);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            margin-bottom: 100px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 25px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #eef2f7;
        }

        th {
            background-color: #3498db;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background-color: #f8fafc;
            transform: scale(1.005);
            transition: all 0.2s ease;
        }

        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin: 30px 0;
        }

        .button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .refresh-button {
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .refresh-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        .block-button {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            padding: 8px 16px;
            font-size: 12px;
        }

        .unblock-button {
            background: linear-gradient(45deg, #7f8c8d, #95a5a6);
            color: white;
            padding: 8px 16px;
            font-size: 12px;
        }

        .limit-button {
            background: linear-gradient(45deg, #f1c40f, #f39c12);
            color: white;
            padding: 8px 16px;
            font-size: 12px;
        }

        .unlimit-button {
            background: linear-gradient(45deg, #1abc9c, #16a085);
            color: white;
            padding: 8px 16px;
            font-size: 12px;
        }

        .search-box {
            width: 100%;
            padding: 15px;
            border: 2px solid #eef2f7;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .search-box:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 2px 15px rgba(52, 152, 219, 0.2);
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
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
        }

        .status-limited {
            background: linear-gradient(45deg, #f1c40f, #f39c12);
            color: white;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
            position: relative;
            animation: modalSlideIn 0.3s ease;
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
            color: #95a5a6;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close:hover {
            color: #e74c3c;
            transform: rotate(90deg);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #eef2f7;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 2px 15px rgba(52, 152, 219, 0.1);
        }

        /* Footer Styles */
        .footer {
            background: linear-gradient(45deg, #2c3e50, #3498db);
            padding: 20px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
            left: 0;
            z-index: 1000;
            box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
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

        .telegram-button {
            background: rgba(255,255,255,0.1);
            padding: 8px 16px;
            border-radius: 20px;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .telegram-button:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        /* Dark mode styles */
        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, #2c3e50 0%, #1a1a1a 100%);
                color: #ecf0f1;
            }

            .container {
                background: rgba(44, 62, 80, 0.95);
            }

            table {
                background: #34495e;
            }

            th {
                background: #2980b9;
            }

            td {
                color: #ecf0f1;
                border-bottom: 1px solid #4a6278;
            }

            tr:hover {
                background: #2c3e50;
            }

            .search-box {
                background: #34495e;
                border-color: #4a6278;
                color: #ecf0f1;
            }

            .modal-content {
                background: #34495e;
                color: #ecf0f1;
            }

            .form-group input {
                background: #2c3e50;
                border-color: #4a6278;
                color: #ecf0f1;
            }
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .container {
                padding: 10px;
                margin: 0;
                margin-bottom: 80px;
            }

            h1 {
                font-size: 1.8em;
                margin-bottom: 20px;
                padding: 10px 0;
            }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
                font-size: 14px;
            }

            th, td {
                padding: 10px;
            }

            .button {
                padding: 8px 12px;
                font-size: 12px;
            }

            .button-group {
                flex-wrap: wrap;
                gap: 8px;
            }

            .search-box {
                padding: 10px;
                font-size: 14px;
                margin-bottom: 15px;
            }

            .status-badge {
                padding: 4px 8px;
                font-size: 10px;
            }

            .modal-content {
                margin: 20% auto;
                padding: 20px;
                width: 95%;
            }

            .footer {
                padding: 15px 0;
            }

            .footer-content {
                flex-direction: column;
                gap: 10px;
            }

            .footer-brand {
                flex-wrap: wrap;
                justify-content: center;
                text-align: center;
            }

            .telegram-button {
                padding: 6px 12px;
                font-size: 12px;
            }

            td {
                min-width: 100px;
            }

            td:nth-child(4) {
                min-width: 150px;
            }

            td:nth-child(5) {
                min-width: 200px;
            }

            .form-group input {
                padding: 8px;
                font-size: 14px;
            }

            .close {
                right: 15px;
                top: 10px;
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 1.5em;
            }

            .button {
                width: 100%;
                margin-bottom: 5px;
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
            color: #FF6B6B;
            font-weight: 600;
            text-shadow: 0 0 10px rgba(255, 107, 107, 0.5);
        }

        .brand-name {
            background: linear-gradient(45deg, #4ECDC4, #45B7AF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 600;
            font-size: 1.1em;
        }

        .heart-icon {
            color: #FF6B6B;
            animation: heartBeat 1.5s ease infinite;
            font-size: 18px !important;
        }

        .by-text {
            color: #A8E6CF;
            font-weight: 500;
        }

        .telegram-button {
            background: linear-gradient(45deg, #3498db, #2980b9);
            padding: 8px 16px;
            border-radius: 20px;
            color: white;
            font-weight: 500;
            box-shadow: 0 2px 10px rgba(52, 152, 219, 0.3);
        }

        .telegram-button:hover {
            background: linear-gradient(45deg, #2980b9, #2573a7);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
        }

        @keyframes heartBeat {
            0% { transform: scale(1); }
            14% { transform: scale(1.3); }
            28% { transform: scale(1); }
            42% { transform: scale(1.3); }
            70% { transform: scale(1); }
        }

        /* Dark mode adjustments */
        @media (prefers-color-scheme: dark) {
            .copyright {
                color: #FF8585;
            }
            
            .brand-name {
                background: linear-gradient(45deg, #5DDDD3, #4EC8BF);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            
            .heart-icon {
                color: #FF8585;
            }
            
            .by-text {
                color: #B8F6DF;
            }
        }

        .theme-toggle {
            background: linear-gradient(45deg, #34495e, #2c3e50);
            color: white;
            padding: 12px 24px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.3);
        }

        .theme-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(44, 62, 80, 0.4);
        }

        .theme-toggle i {
            font-size: 20px;
            transition: transform 0.5s ease;
        }

        body.dark-mode {
            background: linear-gradient(135deg, #2c3e50 0%, #1a1a1a 100%);
            color: #ecf0f1;
        }

        body.dark-mode .container {
            background: rgba(44, 62, 80, 0.95);
        }

        body.dark-mode table {
            background: #34495e;
        }

        body.dark-mode th {
            background: #2980b9;
        }

        body.dark-mode td {
            color: #ecf0f1;
            border-bottom: 1px solid #4a6278;
        }

        body.dark-mode tr:hover {
            background: #2c3e50;
        }

        body.dark-mode .search-box {
            background: #34495e;
            border-color: #4a6278;
            color: #ecf0f1;
        }

        body.dark-mode .modal-content {
            background: #34495e;
            color: #ecf0f1;
        }

        body.dark-mode .form-group input {
            background: #2c3e50;
            border-color: #4a6278;
            color: #ecf0f1;
        }

        body.dark-mode .theme-toggle {
            background: linear-gradient(45deg, #f1c40f, #f39c12);
        }

        body.dark-mode .copyright {
            color: #FF8585;
        }
        
        body.dark-mode .brand-name {
            background: linear-gradient(45deg, #5DDDD3, #4EC8BF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        body.dark-mode .heart-icon {
            color: #FF8585;
        }
        
        body.dark-mode .by-text {
            color: #B8F6DF;
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
                    echo '<tr><td colspan="5" style="text-align: center;">Tidak ada perangkat yang terkoneksi</td></tr>';
                } else {
                    $counter = 1;
                    foreach ($devices as $device) {
                        $status_badges = '';
                        if (isBlocked($device['ip'])) {
                            $status_badges .= '<span class="status-badge status-blocked">Diblokir</span>';
                        }
                        if (isLimited($device['ip'])) {
                            $limit_value = getLimitValue($device['ip']);
                            $status_badges .= '<span class="status-badge status-limited">Dibatasi ' . $limit_value . ' Kbps</span>';
                        }
                        
                        echo "<tr>
                                <td>{$counter}</td>
                                <td>" . htmlspecialchars($device['ip']) . "</td>
                                <td>" . htmlspecialchars($device['mac']) . "</td>
                                <td>{$status_badges}</td>
                                <td>
                                    <button class='button limit-button' onclick='openLimitModal(\"" . htmlspecialchars($device['ip']) . "\")'>Limit</button>
                                    <form method='POST' style='display: inline;'>
                                        <input type='hidden' name='ip' value='" . htmlspecialchars($device['ip']) . "'>
                                        <button type='submit' name='unlimit' class='button unlimit-button' onclick='return confirm(\"Apakah Anda yakin ingin membuka limit bandwidth untuk perangkat ini?\")'>Buka Limit</button>
                                    </form>
                                    <form method='POST' style='display: inline;'>
                                        <input type='hidden' name='ip' value='" . htmlspecialchars($device['ip']) . "'>
                                        <button type='submit' name='block' class='button block-button' onclick='return confirm(\"Apakah Anda yakin ingin memblokir perangkat ini?\")'>Blokir</button>
                                    </form>
                                    <form method='POST' style='display: inline;'>
                                        <input type='hidden' name='ip' value='" . htmlspecialchars($device['ip']) . "'>
                                        <button type='submit' name='unblock' class='button unblock-button' onclick='return confirm(\"Apakah Anda yakin ingin membuka blokir perangkat ini?\")'>Buka Blokir</button>
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
            <h2>Batasi Bandwidth</h2>
            <form method="POST" action="">
                <input type="hidden" name="ip" id="limitIp">
                <div class="form-group">
                    <label for="download">Download (Kbps):</label>
                    <input type="number" id="download" name="download" value="1024" min="1" required>
                </div>
                <button type="submit" name="limit" class="button manage-button">Terapkan Limit</button>
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
                            <i class="material-icons tiny">telegram</i>
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

        // Check saved theme preference
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            const themeIcon = document.getElementById('theme-icon');
            const themeText = document.getElementById('theme-text');
            
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
                themeIcon.textContent = 'light_mode';
                themeText.textContent = 'Mode Terang';
            }
        });
    </script>

    <!-- Add Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</body>
</html> 