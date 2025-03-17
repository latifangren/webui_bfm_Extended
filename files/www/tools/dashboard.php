<?php
// Initialize connection status variables
$sim1_status = "Tidak tersedia";
$sim2_status = "Tidak tersedia";
$current_network = "Tidak terdeteksi";
$signal_strength = "Tidak tersedia";
$current_band = "Tidak terdeteksi";
$network_type = "Tidak tersedia";
$earfcn = "Tidak tersedia";
$bandwidth = "Tidak tersedia";
$cell_id = "Tidak tersedia";

// Array band yang valid di Indonesia
$valid_bands = [1, 3, 5, 8, 40];

// Get current cellular information using Android shell commands
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'refresh_data') {
    // Get SIM card status
    $sim1_status_raw = trim(shell_exec('getprop gsm.sim.state')) ?: "ABSENT";
    $sim1_status = "Tidak Tersedia";
    
    // Interpretasi status SIM 1
    if (strpos($sim1_status_raw, 'LOADED') !== false) {
        $sim1_status = "Terpasang & Aktif";
    } else if (strpos($sim1_status_raw, 'ABSENT') !== false) {
        $sim1_status = "Tidak Terpasang";
    }
    
    $sim2_status = trim(shell_exec('getprop gsm.sim.state.slot2')) ?: "Tidak tersedia";
    
    // Get current network operator
    $current_network = trim(shell_exec('getprop gsm.operator.alpha')) ?: "Tidak terdeteksi";
    $network_sim2 = trim(shell_exec('getprop gsm.operator.alpha.slot2')) ?: "Tidak terdeteksi";
    
    // Get signal strength menggunakan dumpsys tanpa root
    $signal_raw = shell_exec('dumpsys telephony.registry | grep -i signalstrength');
    if (empty($signal_raw)) {
        $signal_raw = shell_exec('dumpsys telephony.registry | grep -i signal');
    }
    
    // Metode 1: Mencoba mendapatkan nilai dBm dari SignalStrength
    if (preg_match('/(-\d+)(?:dBm|ASU)/', $signal_raw, $matches)) {
        $signal_strength = $matches[1] . " dBm";
    } 
    // Metode 2: Mencoba mendapatkan nilai dari mSignalStrength
    elseif (preg_match('/mSignalStrength=(-?\d+)/', $signal_raw, $matches)) {
        $signal_strength = $matches[1] . " dBm";
    }
    // Metode 3: Mencoba mendapatkan nilai dari ServiceState
    else {
        $service_state = shell_exec('dumpsys telephony.registry | grep -A 10 "mServiceState"');
        if (preg_match('/strength=(-?\d+)/', $service_state, $matches)) {
            $signal_strength = $matches[1] . " dBm";
        }
        // Metode 4: Mencoba mendapatkan nilai dari Signal Level
        elseif (preg_match('/level=(\d+)/', $service_state, $matches)) {
            $level = intval($matches[1]);
            // Konversi level ke perkiraan dBm
            switch($level) {
                case 4: $signal_strength = "-65 dBm (Sangat Baik)"; break;
                case 3: $signal_strength = "-75 dBm (Baik)"; break;
                case 2: $signal_strength = "-85 dBm (Sedang)"; break;
                case 1: $signal_strength = "-95 dBm (Lemah)"; break;
                case 0: $signal_strength = "-105 dBm (Sangat Lemah)"; break;
                default: $signal_strength = "Tidak tersedia";
            }
        } else {
            // Metode 5: Mencoba mendapatkan nilai dari Signal Quality
            $signal_quality = shell_exec('dumpsys telephony.registry | grep -i "SignalQuality"');
            if (preg_match('/quality=(\d+)/', $signal_quality, $matches)) {
                $quality = intval($matches[1]);
                $signal_strength = "Level " . $quality . " (" . ($quality >= 3 ? "Baik" : "Lemah") . ")";
            } else {
                $signal_strength = "Tidak tersedia";
            }
        }
    }
    
    // Tambahkan debug info untuk signal strength
    $debug_log .= "Signal Raw Data: " . $signal_raw . "\n";
    $debug_log .= "Service State Signal: " . $service_state . "\n";
    $debug_log .= "Signal Quality: " . $signal_quality . "\n";
    $debug_log .= "Final Signal Strength: " . $signal_strength . "\n";
    
    // Get current LTE band tanpa root
    $current_band = "Tidak terdeteksi";
    $network_type = trim(shell_exec('getprop gsm.network.type'));
    $radio_type = trim(shell_exec('getprop gsm.current.phone-type'));

    // Cek apakah menggunakan jaringan LTE/4G
    if (strpos(strtoupper($network_type), 'LTE') !== false || strpos(strtoupper($network_type), '4G') !== false) {
        // Array untuk memetakan nomor band ke informasi frekuensi
        $bandMap = [
            '1' => ['band' => 'Band 1', 'freq' => '2100 MHz', 'provider' => 'FDD'],
            '3' => ['band' => 'Band 3', 'freq' => '1800 MHz', 'provider' => 'FDD'],
            '5' => ['band' => 'Band 5', 'freq' => '850 MHz', 'provider' => 'FDD'],
            '8' => ['band' => 'Band 8', 'freq' => '900 MHz', 'provider' => 'FDD'],
            '40' => ['band' => 'Band 40', 'freq' => '2300 MHz', 'provider' => 'TDD']
        ];
        
        // METODE 1: Direct from getprop
        $possibleProps = [
            'gsm.baseband.channel',
            'gsm.network.type',
            'gsm.sim.operator.numeric',
            'ril.lte.caid',
            'ril.nw.band'
        ];
        
        foreach ($possibleProps as $prop) {
            $propValue = trim(shell_exec("getprop $prop 2>/dev/null"));
            if (!empty($propValue)) {
                if (preg_match('/([1-9][0-9]?)/', $propValue, $matches)) {
                    $bandNum = $matches[1];
                    if (isset($bandMap[$bandNum])) {
                        $current_band = $bandMap[$bandNum]['band'] . " (" . $bandMap[$bandNum]['freq'] . " - " . $bandMap[$bandNum]['provider'] . ")";
                        break;
                    }
                }
            }
        }
        
        // METODE 2: Dari dumpsys jika masih tidak terdeteksi
        if ($current_band === "Tidak terdeteksi") {
            $output = shell_exec("dumpsys telephony.registry");
            if (preg_match('/earfcn\s*=\s*(\d+)/i', $output, $matches)) {
                $earfcn = (int)$matches[1];
                if ($earfcn >= 0 && $earfcn <= 599) {
                    $current_band = "Band 1 (2100 MHz - FDD)";
                } elseif ($earfcn >= 1200 && $earfcn <= 1949) {
                    $current_band = "Band 3 (1800 MHz - FDD)";
                } elseif ($earfcn >= 2400 && $earfcn <= 2649) {
                    $current_band = "Band 5 (850 MHz - FDD)";
                } elseif ($earfcn >= 3450 && $earfcn <= 3799) {
                    $current_band = "Band 8 (900 MHz - FDD)";
                } elseif ($earfcn >= 38650 && $earfcn <= 39649) {
                    $current_band = "Band 40 (2300 MHz - TDD)";
                }
            }
        }
        
        // METODE 3: Deteksi berdasarkan operator jika masih tidak terdeteksi
        if ($current_band === "Tidak terdeteksi") {
            $operator = strtolower(trim(shell_exec("getprop gsm.sim.operator.alpha")));
            if (strpos($operator, 'telkomsel') !== false) {
                $current_band = "Band 3/1 (1800/2100 MHz - Telkomsel)";
            } elseif (strpos($operator, 'xl') !== false || strpos($operator, 'axis') !== false) {
                $current_band = "Band 3/1 (1800/2100 MHz - XL/AXIS)";
            } elseif (strpos($operator, 'indosat') !== false || strpos($operator, 'im3') !== false) {
                $current_band = "Band 3/1 (1800/2100 MHz - Indosat)";
            } elseif (strpos($operator, 'tri') !== false || strpos($operator, '3') !== false) {
                $current_band = "Band 3/1 (1800/2100 MHz - Tri)";
            } elseif (strpos($operator, 'smartfren') !== false) {
                $current_band = "Band 40 (2300 MHz - Smartfren)";
            }
        }
    } else {
        // Deteksi tipe jaringan non-LTE
        if (strpos(strtoupper($network_type), '3G') !== false || 
            strpos(strtoupper($network_type), 'UMTS') !== false || 
            strpos(strtoupper($network_type), 'HSPA') !== false) {
            $current_band = "Jaringan 3G";
        } elseif (strpos(strtoupper($network_type), '2G') !== false || 
                  strpos(strtoupper($network_type), 'GSM') !== false || 
                  strpos(strtoupper($network_type), 'EDGE') !== false) {
            $current_band = "Jaringan 2G";
        } else {
            $current_band = "Bukan Jaringan LTE (" . $network_type . ")";
        }
    }
    
    // Get EARFCN tanpa root
    $earfcn = "Tidak tersedia";
    
    // Metode 1: Dari dumpsys telephony.registry untuk mCellIdentityLte
    $earfcn_raw = shell_exec('dumpsys telephony.registry | grep -i -A 5 mCellIdentityLte');
    if (preg_match('/earfcn: (\d+)/', $earfcn_raw, $matches)) {
        $earfcn = $matches[1];
        // Tambahkan informasi band berdasarkan EARFCN
        if ($earfcn >= 0 && $earfcn <= 599) {
            $earfcn .= " (Band 1 - 2100 MHz)";
        } elseif ($earfcn >= 1200 && $earfcn <= 1949) {
            $earfcn .= " (Band 3 - 1800 MHz)";
        } elseif ($earfcn >= 2400 && $earfcn <= 2649) {
            $earfcn .= " (Band 5 - 850 MHz)";
        } elseif ($earfcn >= 3450 && $earfcn <= 3799) {
            $earfcn .= " (Band 8 - 900 MHz)";
        } elseif ($earfcn >= 38650 && $earfcn <= 39649) {
            $earfcn .= " (Band 40 - 2300 MHz)";
        }
    }
    // Metode 2: Mencoba dari ServiceState
    elseif (empty($earfcn) || $earfcn == "Tidak tersedia") {
        $service_state = shell_exec('dumpsys telephony.registry | grep -A 20 "mServiceState"');
        if (preg_match('/earfcn\s*=\s*(\d+)/i', $service_state, $matches)) {
            $earfcn = $matches[1];
            // Tambahkan informasi band
            if ($earfcn >= 0 && $earfcn <= 599) {
                $earfcn .= " (Band 1 - 2100 MHz)";
            } elseif ($earfcn >= 1200 && $earfcn <= 1949) {
                $earfcn .= " (Band 3 - 1800 MHz)";
            } elseif ($earfcn >= 2400 && $earfcn <= 2649) {
                $earfcn .= " (Band 5 - 850 MHz)";
            } elseif ($earfcn >= 3450 && $earfcn <= 3799) {
                $earfcn .= " (Band 8 - 900 MHz)";
            } elseif ($earfcn >= 38650 && $earfcn <= 39649) {
                $earfcn .= " (Band 40 - 2300 MHz)";
            }
        }
    }
    // Metode 3: Mencoba dari CellIdentity
    elseif (empty($earfcn) || $earfcn == "Tidak tersedia") {
        $cell_info = shell_exec('dumpsys telephony.registry | grep -A 10 "CellIdentity"');
        if (preg_match('/earfcn\s*=\s*(\d+)/i', $cell_info, $matches)) {
            $earfcn = $matches[1];
            // Tambahkan informasi band
            if ($earfcn >= 0 && $earfcn <= 599) {
                $earfcn .= " (Band 1 - 2100 MHz)";
            } elseif ($earfcn >= 1200 && $earfcn <= 1949) {
                $earfcn .= " (Band 3 - 1800 MHz)";
            } elseif ($earfcn >= 2400 && $earfcn <= 2649) {
                $earfcn .= " (Band 5 - 850 MHz)";
            } elseif ($earfcn >= 3450 && $earfcn <= 3799) {
                $earfcn .= " (Band 8 - 900 MHz)";
            } elseif ($earfcn >= 38650 && $earfcn <= 39649) {
                $earfcn .= " (Band 40 - 2300 MHz)";
            }
        }
    }
    
    // Tambahkan debug info untuk EARFCN
    $debug_log .= "\nEARFCN Debug Info:\n";
    $debug_log .= "EARFCN Raw Data: " . $earfcn_raw . "\n";
    $debug_log .= "Service State EARFCN: " . $service_state . "\n";
    $debug_log .= "Cell Info EARFCN: " . $cell_info . "\n";
    $debug_log .= "Final EARFCN Value: " . $earfcn . "\n";
    
    // Get cell ID tanpa root
    $cell_info = shell_exec('dumpsys telephony.registry | grep -A 20 "mCellIdentityLte"');
    if (empty($cell_info)) {
        $cell_info = shell_exec('dumpsys telephony.registry | grep -A 20 "CellIdentityLte"');
    }

    // Coba berbagai pola untuk Cell ID
    $cell_patterns = [
        '/ci:?\s*(\d+)/',
        '/mCi=(\d+)/',
        '/cellId:?\s*(\d+)/',
        '/CellIdentityLte:.*?ci = (\d+)/',
        '/CellIdentityLte:.*?mCi = (\d+)/',
        '/CellIdentity:.*?id = (\d+)/',
        '/global.*?cid = (\d+)/'
    ];

    $cell_id = "Tidak tersedia";
    foreach ($cell_patterns as $pattern) {
        if (preg_match($pattern, $cell_info, $matches)) {
            $cell_id = $matches[1];
            break;
        }
    }

    // Jika masih tidak tersedia, coba cari di service state
    if ($cell_id === "Tidak tersedia") {
        $service_state = shell_exec('dumpsys telephony.registry | grep -A 30 "mServiceState"');
        foreach ($cell_patterns as $pattern) {
            if (preg_match($pattern, $service_state, $matches)) {
                $cell_id = $matches[1];
                break;
            }
        }
    }

    // Jika masih tidak tersedia, coba dari telephony.db
    if ($cell_id === "Tidak tersedia") {
        $telephony_info = shell_exec('dumpsys telephony.db | grep -i "cellid\|cid"');
        foreach ($cell_patterns as $pattern) {
            if (preg_match($pattern, $telephony_info, $matches)) {
                $cell_id = $matches[1];
                break;
            }
        }
    }

    // Validasi Cell ID
    if ($cell_id !== "Tidak tersedia") {
        $cell_id = intval($cell_id);
        // Cell ID yang valid biasanya antara 0 dan 268435455 (2^28 - 1)
        if ($cell_id < 0 || $cell_id > 268435455) {
            $cell_id = "Tidak tersedia";
        }
    }

    // Debug info untuk band yang lebih lengkap
    $debug_log = "Debug Info Band:\n";
    $debug_log .= "Network Type: " . $network_type . "\n";
    $debug_log .= "Radio Type: " . $radio_type . "\n";
    $debug_log .= "Service State Raw: " . $service_state . "\n";
    $debug_log .= "Cell Identity Raw: " . $cell_info . "\n";
    $debug_log .= "Signal Info Raw: " . $signal_info . "\n";
    $debug_log .= "Telephony Info Raw: " . $telephony_info . "\n";
    $debug_log .= "Current Band Result: " . $current_band . "\n";
    $debug_log .= "Cell Info Raw: " . $cell_info . "\n";
    $debug_log .= "Service State Cell Info: " . $service_state . "\n";
    $debug_log .= "Telephony DB Cell Info: " . $telephony_info . "\n";
    $debug_log .= "Final Cell ID: " . $cell_id . "\n";
    
    // Simpan log debug
    $log_dir = __DIR__ . '/logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    file_put_contents($log_dir . '/band_debug.log', $debug_log, FILE_APPEND);
    
    echo "<p class='green-text'>Data sinyal berhasil diperbarui.</p>";
    if (isset($_POST['show_debug']) && $_POST['show_debug'] == 1) {
        echo "<pre class='grey-text'>" . htmlspecialchars($debug_log) . "</pre>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
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
    </style>
    <!-- Materialize CSS -->
    <link rel="stylesheet" href="../auth/css/materialize.min.css">
    <style>
        body {
            background-color: #000000;
            color: #F1F1F1;
            font-family: 'Roboto', sans-serif;
        }
        .nav-wrapper {
            background: #000000;
            padding: 0 20px;
            border-bottom: 2px solid #FECA0A;
        }
        .brand-logo {
            font-weight: 300;
            color: #FECA0A !important;
        }
        .page-footer {
            background: #000000;
            border-top: 2px solid #FECA0A;
            padding-top: 20px;
        }
        .container {
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .btn {
            background-color: #FECA0A !important;
            color: #000000 !important;
            border-radius: 30px;
            margin: 5px;
            text-transform: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
        .btn:hover {
            background-color: #F1B108 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
        }
        .btn i {
            color: #000000 !important;
        }
        .card {
            border-radius: 8px;
            margin-top: 15px;
            background-color: #1a1a1a !important;
            border: 1px solid rgba(254, 202, 10, 0.2);
        }
        .card .card-content {
            padding: 20px;
        }
        .card .card-title {
            font-weight: 500;
            color: #FECA0A !important;
        }
        .info-card {
            background-color: #1a1a1a;
            border-left: 4px solid;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .info-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        .info-card-primary {
            border-color: #FECA0A;
        }
        .info-card-success {
            border-color: #FECA0A;
        }
        .info-card-warning {
            border-color: #FECA0A;
        }
        .info-card-danger {
            border-color: #FECA0A;
        }
        .info-title {
            display: flex;
            align-items: center;
            font-size: 1.1em;
            color: #FECA0A;
        }
        .info-value {
            font-size: 1.4em;
            margin-top: 5px;
            font-weight: 300;
            color: #F1F1F1;
        }
        .info-icon {
            margin-right: 10px;
            color: #FECA0A;
        }
        .signal-indicator {
            display: flex;
            margin-top: 10px;
        }
        .signal-bar {
            width: 5px;
            margin-right: 2px;
            border-radius: 2px;
            background-color: #1e1e1e;
        }
        .signal-bar.active {
            background-color: #FECA0A;
        }
        .badge-container {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            margin: 5px;
            font-size: 12px;
            font-weight: 600;
            background-color: #1a1a1a !important;
            color: #F1F1F1 !important;
            border: 1px solid #FECA0A;
        }
        .tech-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            margin-left: 8px;
            vertical-align: middle;
            background-color: #FECA0A !important;
            color: #000000 !important;
        }
        .tech-2g, .tech-3g, .tech-4g, .tech-5g {
            background-color: #FECA0A !important;
            color: #000000 !important;
        }
        .header-card {
            background: #1a1a1a;
            color: #F1F1F1;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #FECA0A;
        }
        .header-card h5 {
            color: #FECA0A;
        }
        
        /* Override any color classes with !important */
        .purple.darken-2,
        .blue-grey.darken-1,
        .deep-purple,
        .teal.darken-1,
        .purple-text.text-lighten-3,
        .cyan-text.text-lighten-3,
        .grey-text.text-lighten-2,
        .pink-text,
        .deep-purple.darken-1,
        .blue.darken-2 {
            background-color: #1a1a1a !important;
            color: #F1F1F1 !important;
            border: 1px solid #FECA0A !important;
        }
        
        .btn-small.deep-purple.darken-1,
        .btn-small.blue.darken-2 {
            background-color: #FECA0A !important;
            color: #000000 !important;
        }
        
        [type="checkbox"].filled-in:checked + span:not(.lever):after {
            border: 2px solid #FECA0A;
            background-color: #FECA0A;
        }
        
        /* Footer specifically */
        .footer-copyright {
            background-color: #000000 !important;
            border-top: 1px solid #FECA0A;
        }
        
        .purple-text.text-lighten-3 {
            color: #FECA0A !important;
        }
        
        .cyan-text.text-lighten-3 {
            color: #F1F1F1 !important;
        }
        
        .pink-text.pulse {
            color: #FECA0A !important;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav>
        <div class="nav-wrapper">
            <a href="#" class="brand-logo">
                <i class="material-icons left">dashboard</i>Dashboard Sinyal
            </a>
            <ul id="nav-mobile" class="right hide-on-med-and-down">
                <li class="active"><a href="dashboard.php"><i class="material-icons left">dashboard</i>Dashboard</a></li>
                <li><a href="networktools.php"><i class="material-icons left">build</i>Tools</a></li>
                <li><a href="../auth/logout.php"><i class="material-icons left">exit_to_app</i>Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col s12">
                <div class="badge-container">
                    <span class="badge purple darken-2">Root Access: Active</span>
                    <span class="badge blue-grey darken-1">Network: <?php echo $network_type; ?></span>
                    <span class="badge deep-purple">Signal: <?php echo $signal_strength; ?></span>
                    <span class="badge teal darken-1">Operator: <?php echo $current_network; ?></span>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col s12">
                <div class="header-card">
                    <h5>Status Sinyal Perangkat</h5>
                    <p>Monitoring dan analisis kualitas sinyal seluler</p>
                    <form action="" method="post">
                        <button type="submit" name="action" value="refresh_data" class="btn waves-effect waves-light">
                            <i class="material-icons left">refresh</i>Perbarui Data
                        </button>
                        <label>
                            <input type="checkbox" name="show_debug" value="1" class="filled-in" />
                            <span class="white-text">Tampilkan Debug Info</span>
                        </label>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Kolom Kiri: Informasi SIM dan Jaringan -->
            <div class="col s12 m6">
                <h5><i class="material-icons left">sim_card</i>Informasi SIM Card</h5>
                
                <div class="info-card info-card-primary">
                    <div class="info-title">
                        <i class="material-icons info-icon">sim_card</i>Status SIM 1
                    </div>
                    <div class="info-value"><?php echo $sim1_status; ?></div>
                </div>
                
                <div class="info-card info-card-primary">
                    <div class="info-title">
                        <i class="material-icons info-icon">sim_card</i>Status SIM 2
                    </div>
                    <div class="info-value"><?php echo $sim2_status; ?></div>
                </div>
                
                <div class="info-card info-card-success">
                    <div class="info-title">
                        <i class="material-icons info-icon">business</i>Operator Jaringan
                    </div>
                    <div class="info-value"><?php echo $current_network; ?></div>
                </div>
                
                <div class="info-card info-card-warning">
                    <div class="info-title">
                        <i class="material-icons info-icon">network_cell</i>Tipe Jaringan
                    </div>
                    <div class="info-value">
                        <?php echo $network_type; ?>
                        <?php
                            // Display appropriate technology badge
                            if (strpos($network_type, 'LTE') !== false || strpos($network_type, '4G') !== false) {
                                echo '<span class="tech-badge tech-4g">4G</span>';
                            } elseif (strpos($network_type, '5G') !== false) {
                                echo '<span class="tech-badge tech-5g">5G</span>';
                            } elseif (strpos($network_type, 'UMTS') !== false || strpos($network_type, 'HSPA') !== false || strpos($network_type, '3G') !== false) {
                                echo '<span class="tech-badge tech-3g">3G</span>';
                            } elseif (strpos($network_type, 'GSM') !== false || strpos($network_type, 'GPRS') !== false || strpos($network_type, 'EDGE') !== false) {
                                echo '<span class="tech-badge tech-2g">2G</span>';
                            }
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Kolom Kanan: Informasi Sinyal dan Band -->
            <div class="col s12 m6">
                <h5><i class="material-icons left">signal_cellular_alt</i>Informasi Sinyal</h5>
                
                <div class="info-card info-card-danger">
                    <div class="info-title">
                        <i class="material-icons info-icon">signal_cellular_alt</i>Kekuatan Sinyal
                    </div>
                    <div class="info-value"><?php echo $signal_strength; ?></div>
                    <div class="signal-indicator">
                        <?php
                            // Extract numeric value from signal strength
                            $signal_value = 0;
                            if (preg_match('/(-\d+)/', $signal_strength, $matches)) {
                                $signal_value = abs(intval($matches[1]));
                            }
                            
                            // Signal bars (5 bars total)
                            for ($i = 1; $i <= 5; $i++) {
                                $active = false;
                                // Stronger signal = more bars
                                // Rough calculation: -50 dBm or stronger = 5 bars, -120 dBm or weaker = 0 bars
                                if ($signal_value <= 120 && $signal_value > 50) {
                                    // Convert to 0-5 scale (reverse because lower dBm means stronger signal)
                                    $bars = 5 - floor(($signal_value - 50) / 14);
                                    $active = $i <= $bars;
                                } elseif ($signal_value <= 50) {
                                    $active = true; // Full bars for very strong signal
                                }
                                
                                echo '<div class="signal-bar ' . ($active ? 'active' : '') . '" style="height: ' . (6 + $i * 3) . 'px;"></div>';
                            }
                        ?>
                    </div>
                </div>
                
                <div class="info-card info-card-success">
                    <div class="info-title">
                        <i class="material-icons info-icon">track_changes</i>Band LTE Saat Ini
                    </div>
                    <div class="info-value"><?php echo $current_band; ?></div>
                </div>
                
                <div class="info-card info-card-primary">
                    <div class="info-title">
                        <i class="material-icons info-icon">waves</i>EARFCN (Frequency)
                    </div>
                    <div class="info-value"><?php echo $earfcn; ?></div>
                </div>
                
                <div class="info-card info-card-warning">
                    <div class="info-title">
                        <i class="material-icons info-icon">settings_input_component</i>Cell ID
                    </div>
                    <div class="info-value"><?php echo $cell_id; ?></div>
                </div>
            </div>
        </div>
        <div class="footer-copyright">
            <div class="container">
                <div class="row">
                    <div class="col s12 m8">
                        <span class="left">
                            <span class="purple-text text-lighten-3">© 2025</span>
                            <span class="cyan-text text-lighten-3">Network Tools</span>
                            <i class="material-icons tiny pink-text pulse">favorite</i> 
                            <span class="grey-text text-lighten-2">by</span>
                            <a href="https://t.me/latifan_id" class="waves-effect waves-light btn-small deep-purple darken-1" target="_blank">
                                <i class="material-icons left tiny">telegram</i>
                                @latifan_id
                            </a>
                        </span>
                    </div>
                    <div class="col s12 m4">
                        <div class="right">
                            <a class="waves-effect waves-light btn-small blue darken-2" href="networktools.php">
                                <i class="material-icons left tiny">build</i>
                                Network Tools
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Materialize JavaScript -->
    <script src="../auth/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Refresh data secara otomatis setiap 30 detik
            setInterval(function() {
                document.querySelector('button[name="action"][value="refresh_data"]').click();
            }, 30000);
        });
    </script>
</body>
</html> 