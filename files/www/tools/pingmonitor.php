<?php
// Tambahkan ini di bagian atas file setelah tag PHP
date_default_timezone_set('Asia/Jakarta');

// Script pingmonitor.php untuk memantau pingloop.sh
// Dibuat untuk lingkungan Android

// Konfigurasi
$logFile = "/data/local/tmp/pingloop.log";  // Lokasi file log
$scriptPath = "/data/adb/service.d/pingloop.sh";  // Lokasi script pingloop.sh yang benar
$refreshInterval = 5;  // Interval refresh dalam detik

// Fungsi untuk memeriksa apakah script pingloop.sh sedang berjalan
function isScriptRunning() {
    $output = [];
    // Coba beberapa pendekatan untuk mendeteksi script yang berjalan
    exec("ps -ef | grep pingloop.sh | grep -v grep", $output);
    if (!empty($output)) return true;
    
    // Pendekatan alternatif jika yang pertama gagal
    $output = [];
    exec("ps | grep pingloop.sh | grep -v grep", $output);
    if (!empty($output)) return true;
    
    // Pendekatan alternatif lain
    $output = [];
    exec("pgrep -f pingloop.sh", $output);
    
    return !empty($output);
}

// Fungsi untuk mendapatkan status mode pesawat
function getAirplaneMode() {
    $output = [];
    exec("settings get global airplane_mode_on", $output);
    return trim($output[0]) == "1" ? true : false;
}

// Fungsi untuk mendapatkan log dari pingloop.sh
function getLogContent($logFile, $lines = 20) {
    if (!file_exists($logFile)) {
        return "File log tidak ditemukan";
    }
    
    $output = [];
    exec("tail -n $lines $logFile", $output);
    return implode("\n", $output);
}

// Fungsi untuk memulai script jika belum berjalan
function startScript($scriptPath) {
    exec("/system/bin/sh $scriptPath > $GLOBALS[logFile] 2>&1 &");
    return "Script pingloop.sh dijalankan";
}

// Fungsi untuk menghentikan script
function stopScript() {
    exec("pkill -f pingloop.sh");
    return "Script pingloop.sh dihentikan";
}

// Fungsi untuk ekstrak data ping untuk grafik
function extractPingData($logContent) {
    $data = [];
    $times = [];
    $lines = explode("\n", $logContent);
    foreach ($lines as $line) {
        if (strpos($line, "Host dapat dijangkau") !== false) {
            // Ekstrak timestamp dan status
            if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $line, $matches)) {
                $timestamp = $matches[1];
                $times[] = $timestamp;
                $data[] = 1; // 1 untuk sukses
            }
        } elseif (strpos($line, "Host tidak dapat dijangkau") !== false) {
            // Ekstrak timestamp dan status
            if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $line, $matches)) {
                $timestamp = $matches[1];
                $times[] = $timestamp;
                $data[] = 0; // 0 untuk gagal
            }
        }
    }
    return ['times' => $times, 'data' => $data];
}

// Fungsi untuk mendapatkan host yang dipantau dari script pingloop.sh
function getMonitoredHost($scriptPath) {
    if (!file_exists($scriptPath)) {
        return "N/A";
    }
    
    $content = file_get_contents($scriptPath);
    if (preg_match('/HOST="([^"]+)"/', $content, $matches)) {
        return $matches[1];
    }
    
    return "N/A";
}

// Fungsi untuk mendapatkan direktori script
function getScriptDirectory($scriptPath) {
    return dirname($scriptPath);
}

// Tambahkan fungsi baru untuk memeriksa status APN Monitor
function isApnMonitorRunning() {
    $output = [];
    exec("ps -ef | grep autoswitchapn.sh | grep -v grep", $output);
    if (!empty($output)) return true;
    
    $output = [];
    exec("ps | grep autoswitchapn.sh | grep -v grep", $output);
    if (!empty($output)) return true;
    
    $output = [];
    exec("pgrep -f autoswitchapn.sh", $output);
    
    return !empty($output);
}

// Fungsi untuk mendapatkan current APN
function getCurrentApn() {
    $output = [];
    exec("su -c 'content query --uri content://telephony/carriers/preferapn'", $output);
    $apnInfo = implode("\n", $output);
    
    // Format output agar lebih mudah dibaca
    if (empty($apnInfo)) {
        return "Tidak dapat membaca informasi APN";
    }
    
    // Parse dan format APN info
    $formatted = "APN Info:\n";
    $formatted .= str_replace(" Row: ", "\n", $apnInfo);
    return $formatted;
}

// Fungsi untuk memulai APN Monitor
function startApnMonitor() {
    // Pastikan script ada dan executable
    $scriptPath = "/data/adb/service.d/autoswitchapn.sh";
    if (!file_exists($scriptPath)) {
        return "Error: Script autoswitchapn.sh tidak ditemukan";
    }
    
    // Jalankan script dengan su untuk mendapatkan akses root
    exec("su -c '/system/bin/sh $scriptPath > /data/local/tmp/apnmonitor.log 2>&1 &'");
    sleep(1); // Tunggu sebentar untuk memastikan script berjalan
    
    // Verifikasi apakah script berjalan
    if (isApnMonitorRunning()) {
        return "APN Monitor berhasil dijalankan";
    } else {
        return "Error: Gagal menjalankan APN Monitor";
    }
}

// Fungsi untuk menghentikan APN Monitor
function stopApnMonitor() {
    exec("su -c 'pkill -f autoswitchapn.sh'");
    sleep(1); // Tunggu sebentar untuk memastikan script berhenti
    
    if (!isApnMonitorRunning()) {
        return "APN Monitor berhasil dihentikan";
    } else {
        return "Error: Gagal menghentikan APN Monitor";
    }
}

// Fungsi untuk mendapatkan log APN Monitor
function getApnMonitorLog($lines = 20) {
    $logFile = "/data/local/tmp/apnmonitor.log";
    if (!file_exists($logFile)) {
        return "File log APN Monitor tidak ditemukan";
    }
    
    $output = [];
    exec("tail -n $lines $logFile", $output);
    return implode("\n", $output);
}

// Fungsi untuk mengekstrak data statistik APN
function extractApnStats($logContent) {
    $stats = [
        'switches' => 0,
        'success' => 0,
        'failed' => 0,
        'lastSwitch' => '',
        'events' => []
    ];
    
    $lines = explode("\n", $logContent);
    foreach ($lines as $line) {
        if (strpos($line, "Mengaktifkan mode APN") !== false) {
            $stats['switches']++;
            if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $line, $matches)) {
                $stats['lastSwitch'] = $matches[1];
                $stats['events'][] = ['time' => $matches[1], 'type' => 'switch'];
            }
        }
        if (strpos($line, "Host dapat dijangkau") !== false) {
            $stats['success']++;
            if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $line, $matches)) {
                $stats['events'][] = ['time' => $matches[1], 'type' => 'success'];
            }
        }
        if (strpos($line, "Host tidak dapat dijangkau") !== false) {
            $stats['failed']++;
            if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $line, $matches)) {
                $stats['events'][] = ['time' => $matches[1], 'type' => 'failed'];
            }
        }
    }
    
    return $stats;
}

// Handler untuk permintaan AJAX
if (isset($_GET['action'])) {
    switch($_GET['action']) {
        case 'get_status':
            $scriptRunning = isScriptRunning();
            $airplaneMode = getAirplaneMode();
            $logContent = getLogContent($logFile);
            $pingData = extractPingData($logContent);
            $monitoredHost = getMonitoredHost($scriptPath);
            $scriptDirectory = getScriptDirectory($scriptPath);
            $apnMonitorRunning = isApnMonitorRunning();
            $currentApn = getCurrentApn();
            $apnMonitorLog = getApnMonitorLog();
            $apnStats = extractApnStats($apnMonitorLog);
            
            header('Content-Type: application/json');
            echo json_encode([
                'scriptRunning' => $scriptRunning,
                'airplaneMode' => $airplaneMode,
                'logContent' => $logContent,
                'pingData' => $pingData,
                'monitoredHost' => $monitoredHost,
                'scriptDirectory' => $scriptDirectory,
                'scriptPath' => $scriptPath,
                'timestamp' => date('Y-m-d H:i:s'),
                'apnMonitorRunning' => $apnMonitorRunning,
                'currentApn' => $currentApn,
                'apnMonitorLog' => $apnMonitorLog,
                'apnStats' => $apnStats
            ]);
            exit;
    }
}

// Menangani permintaan aksi
$message = "";
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'start':
            $message = startScript($scriptPath);
            break;
        case 'stop':
            $message = stopScript();
            break;
        case 'restart':
            stopScript();
            sleep(1);
            $message = startScript($scriptPath);
            break;
        case 'start_apn':
            $message = startApnMonitor();
            // Tambahkan header untuk response AJAX
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['message' => $message]);
                exit;
            }
            break;
        case 'stop_apn':
            $message = stopApnMonitor();
            // Tambahkan header untuk response AJAX
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['message' => $message]);
                exit;
            }
            break;
    }
}

// Dapatkan status terkini
$scriptRunning = isScriptRunning();
$airplaneMode = getAirplaneMode();
$logContent = getLogContent($logFile);
$pingData = extractPingData($logContent);
$monitoredHost = getMonitoredHost($scriptPath);
$scriptDirectory = getScriptDirectory($scriptPath);

// HTML dan tampilan interface
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemantau Ping</title>
    <script src="\kaiadmin\assets\js\plugin\chart.js\chart.min.js"></script>
    <style>
        :root {
            /* Warna Utama */
            --primary-color: #FECA0A;     /* Kuning untuk highlight dan status aktif */
            --secondary-color: #F1F1F1;   /* Putih untuk teks umum */
            
            /* Warna Background */
            --bg-primary: #000000;        /* Hitam untuk background utama */
            --bg-secondary: #1a1a1a;      /* Abu gelap untuk card dan elemen sekunder */
            --bg-tertiary: #111111;       /* Abu lebih gelap untuk log container */
            
            /* Warna Status */
            --success-color: #4CAF50;     /* Hijau untuk status sukses */
            --warning-color: #FFA726;     /* Oranye untuk peringatan */
            --error-color: #F44336;       /* Merah untuk error/gagal */
            --info-color: #2196F3;        /* Biru untuk informasi */
            
            /* Warna Teks */
            --text-primary: #F1F1F1;      /* Putih untuk teks utama */
            --text-secondary: #aaaaaa;    /* Abu-abu untuk teks sekunder */
            --text-muted: #666666;        /* Abu-abu gelap untuk teks tersier */
            
            /* Warna Border & Shadow */
            --border-color: #333333;
            --shadow-color: rgba(0,0,0,0.2);
            
            /* Warna Chart */
            --chart-success: rgba(76, 175, 80, 0.5);
            --chart-error: rgba(244, 67, 54, 0.5);
            --chart-grid: rgba(255, 255, 255, 0.08);
            
            /* Warna Status */
            --status-success: #00E676;    /* Hijau terang untuk sukses */
            --status-error: #FF1744;      /* Merah terang untuk gagal */
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s, color 0.3s;
        }
        
        /* Container lebih compact */
        .container {
            max-width: 900px;
            margin: 10px auto;
            background: var(--bg-secondary);
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 2px 10px var(--shadow-color);
            transition: background-color 0.3s, box-shadow 0.3s;
        }
        
        /* Header lebih compact */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 15px;
            background: var(--bg-secondary);
            border-radius: 10px;
            box-shadow: 0 2px 8px var(--shadow-color);
            margin-bottom: 10px;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo-icon {
            font-size: 20px;
            margin-right: 5px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 18px;
            background: linear-gradient(135deg, #4285f4, #34a853);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-weight: 600;
        }
        
        .dark-mode .header h1 {
            background: linear-gradient(135deg, #4a83f8, #52c41a);
            -webkit-background-clip: text;
            background-clip: text;
        }
        
        .credit {
            display: flex;
            align-items: center;
            font-size: 0.75rem;
            margin-left: 8px;
            gap: 3px;
        }
        
        .credit-text {
            color: var(--text-primary);
            opacity: 0.8;
        }
        
        .credit-link {
            text-decoration: none;
            color: #4285f4;
            position: relative;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .credit-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 0;
            background: linear-gradient(135deg, #4285f4, #34a853);
            transition: width 0.3s ease;
        }
        
        .credit-link:hover {
            color: #34a853;
        }
        
        .credit-link:hover::after {
            width: 100%;
        }
        
        .dark-mode-toggle {
            font-size: 18px;
            cursor: pointer;
            background: none;
            border: none;
            color: var(--text-primary);
            transition: transform 0.3s, color 0.3s;
        }
        
        .dark-mode-toggle:hover {
            transform: rotate(30deg);
        }
        
        /* Redesain status panel untuk lebih compact */
        .status-panel {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 8px;
            margin-bottom: 10px;
        }
        
        .status-card {
            padding: 8px;
            border-radius: 8px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 1px 5px var(--shadow-color);
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 70px;
        }
        
        .card-icon {
            font-size: 16px;
            margin-bottom: 3px;
        }
        
        .status-title {
            font-size: 12px;
            margin-bottom: 4px;
            color: var(--text-secondary);
        }
        
        .status-value {
            font-size: 13px;
            font-weight: bold;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        
        /* Controls lebih compact */
        .controls {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-bottom: 10px;
        }
        
        .controls button {
            padding: 6px 8px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .button-icon {
            margin-right: 3px;
            font-size: 14px;
        }
        
        /* Chart section lebih compact */
        .chart-section {
            margin: 10px 0;
            background: var(--bg-secondary);
            border-radius: 8px;
            box-shadow: 0 2px 10px var(--shadow-color);
            overflow: hidden;
        }
        
        .chart-header {
            background: linear-gradient(135deg, var(--bg-primary), var(--bg-secondary));
            border-bottom: 2px solid var(--primary-color);
            padding: 8px 10px;
            color: var(--primary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .chart-header h2 {
            margin: 0;
            font-size: 16px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }
        
        .last-ping-status {
            font-weight: bold;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        
        .chart-wrapper {
            padding: 10px;
            height: 200px; /* Reduced height */
            position: relative;
        }
        
        /* Log section lebih compact */
        .log-section {
            margin: 10px 0;
            background: var(--bg-secondary);
            border-radius: 8px;
            box-shadow: 0 2px 10px var(--shadow-color);
            overflow: hidden;
        }
        
        .log-section h2 {
            background: linear-gradient(135deg, var(--bg-primary), var(--bg-secondary));
            border-bottom: 2px solid var(--primary-color);
            margin: 0;
            padding: 8px 10px;
            color: var(--primary-color);
            font-size: 16px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }
        
        .log-container {
            height: 200px; /* Reduced height */
            overflow-y: auto;
            padding: 8px;
            background-color: var(--bg-tertiary);
            font-family: 'Courier New', monospace;
            font-size: 11px;
            border: none;
            transition: background-color 0.3s;
        }
        
        /* Path yang lebih compact */
        .path-text {
            font-size: 11px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Timestamp lebih compact */
        .timestamp {
            text-align: center;
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 5px;
        }
        
        /* Miscellaneous settings */
        pre {
            margin: 0;
        }
        
        .message {
            margin: 10px 0;
            padding: 8px;
            border-radius: 6px;
            background-color: var(--bg-secondary);
            color: var(--primary-color);
            text-align: center;
            opacity: 1;
            transition: opacity 1s ease-out;
        }
        
        /* Status colors */
        .running {
            background-color: var(--bg-secondary);
            color: var(--success-color);
            border-color: var(--success-color);
        }
        .stopped {
            background-color: var(--bg-secondary);
            color: var(--error-color);
            border-color: var(--error-color);
        }
        .airplane-on {
            background-color: var(--bg-secondary);
            color: var(--warning-color);
            border-color: var(--warning-color);
        }
        .airplane-off {
            background-color: var(--bg-secondary);
            color: var(--info-color);
            border-color: var(--info-color);
        }
        .success-ping {
            background-color: var(--success-color);
            color: var(--bg-primary);
        }
        .failed-ping {
            background-color: var(--bg-secondary);
            color: var(--error-color);
            border: 1px solid var(--error-color);
        }
        
        /* Animation */
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(254, 202, 10, 0.4); }
            70% { box-shadow: 0 0 0 8px rgba(254, 202, 10, 0); }
            100% { box-shadow: 0 0 0 0 rgba(254, 202, 10, 0); }
        }
        
        /* Log Highlights */
        .log-line-success {
            color: var(--status-success) !important;  /* Hijau untuk ping sukses */
        }
        .log-line-error {
            color: var(--status-error) !important;   /* Merah untuk ping gagal */
        }
        
        /* Statistics Cards */
        .stats-value.success {
            color: var(--status-success) !important;  /* Hijau untuk statistik sukses */
        }
        .stats-value.failed {
            color: var(--status-error) !important;   /* Merah untuk statistik gagal */
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .status-panel {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }
            .controls {
                grid-template-columns: 1fr 1fr 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                margin: 5px;
                padding: 8px;
            }
            .status-panel {
                grid-template-columns: repeat(2, 1fr);
            }
            .path-text {
                font-size: 10px;
            }
        }
        
        .start-btn, .stop-btn, .restart-btn, .apn-btn {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--primary-color);
            border-radius: 20px;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .start-btn:hover, .restart-btn:hover {
            background-color: var(--success-color);
            color: var(--bg-primary);
            border-color: var(--success-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(254, 202, 10, 0.3);
        }
        
        .stop-btn:hover {
            background-color: var(--error-color);
            color: var(--bg-primary);
            border-color: var(--error-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(254, 202, 10, 0.3);
        }
        
        /* Header dan branding */
        .app-header {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px 10px;
            background-color: #000000;
            border-bottom: 2px solid #FECA0A;
            margin-bottom: 20px;
        }
        
        .app-title {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
            color: #FECA0A;
        }
        
        .app-emoji {
            margin-right: 10px;
            font-size: 28px;
        }
        
        /* Footer styling */
        .app-footer {
            text-align: center;
            padding: 15px;
            margin-top: 20px;
            border-top: 2px solid #FECA0A;
            background-color: #000000;
        }
        
        .footer-text {
            color: #F1F1F1;
            font-size: 14px;
        }
        
        .footer-brand {
            color: #FECA0A;
            font-weight: 600;
        }
        
        .footer-emoji {
            margin: 0 5px;
        }
        
        .footer-link {
            color: #FECA0A;
            text-decoration: none;
            border-bottom: 1px dotted #FECA0A;
            transition: all 0.3s ease;
        }
        
        .footer-link:hover {
            opacity: 0.8;
            border-bottom: 1px solid #FECA0A;
        }

        .apn-monitor-section {
            margin: 10px 0;
            background: var(--bg-secondary);
            border-radius: 8px;
            box-shadow: 0 2px 10px var(--shadow-color);
            overflow: hidden;
        }

        .section-header {
            background: linear-gradient(135deg, var(--bg-primary), var(--bg-secondary));
            border-bottom: 2px solid var(--primary-color);
            padding: 8px 10px;
            color: var(--primary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h2 {
            margin: 0;
            font-size: 16px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }

        .apn-controls {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 15px;
        }

        .apn-btn {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--primary-color);
            border-radius: 20px;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            width: 100%;
            transition: all 0.3s ease;
        }

        .apn-btn:hover {
            background-color: var(--success-color);
            color: var(--bg-primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(254, 202, 10, 0.3);
        }

        .apn-info {
            padding: 15px;
            border-top: 1px solid var(--border-color);
        }

        .current-apn {
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .apn-status {
            font-size: 14px;
            padding: 4px 10px;
            border-radius: 15px;
            background-color: var(--bg-secondary);
            border: 1px solid var(--primary-color);
        }

        .statistics-section {
            margin: 10px 0;
            background: var(--bg-secondary);
            border-radius: 8px;
            box-shadow: 0 2px 10px var(--shadow-color);
            overflow: hidden;
        }

        .stats-container {
            padding: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .stats-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }

        .stats-title {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .stats-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--text-primary);
        }

        .stats-value.success {
            color: var(--status-success) !important;
        }

        .stats-value.failed {
            color: var(--status-error) !important;
        }

        #apn-log-container {
            height: 200px;
            overflow-y: auto;
            background: var(--bg-tertiary);
            padding: 10px;
            border-radius: 4px;
        }

        #apn-log-content {
            font-family: monospace;
            font-size: 12px;
            line-height: 1.4;
            color: var(--text-primary);
        }

        /* Tambahkan style untuk tab */
        .tab-container {
            display: flex;
            margin-bottom: 20px;
        }

        .tab-buttons {
            display: flex;
            background: #1a1a1a;
            border-radius: 10px;
            padding: 5px;
            gap: 5px;
        }

        .tab-button {
            padding: 10px 20px;
            border: none;
            background: none;
            color: #F1F1F1;
            cursor: pointer;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .tab-button.active {
            background: #FECA0A;
            color: #000000;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="app-header">
            <span class="app-emoji">📡</span>
            <h1 class="app-title">Network Monitor</h1>
        </div>
        
        <div class="tab-buttons">
            <button class="tab-button active" data-tab="airplane">Auto AirplaneMode</button>
            <button class="tab-button" data-tab="apn">Auto APN Switch</button>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message" id="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <!-- Bungkus konten Auto AirplaneMode dalam tab -->
        <div class="tab-content active" id="airplane-tab">
            <!-- Status cards dalam grid layout -->
            <div class="status-panel">
                <div class="status-card <?php echo $scriptRunning ? 'running pulse' : 'stopped'; ?>" id="script-status">
                    <div class="card-icon"><?php echo $scriptRunning ? '⚙️' : '⛔'; ?></div>
                    <div class="status-title">Status Script</div>
                    <div class="status-value" id="script-status-value">
                        <?php echo $scriptRunning ? 'Berjalan' : 'Berhenti'; ?>
                    </div>
                </div>
                
                <div class="status-card <?php echo $airplaneMode ? 'airplane-on pulse' : 'airplane-off'; ?>" id="airplane-status">
                    <div class="card-icon"><?php echo $airplaneMode ? '✈️' : '📱'; ?></div>
                    <div class="status-title">Mode Pesawat</div>
                    <div class="status-value" id="airplane-status-value">
                        <?php echo $airplaneMode ? 'Aktif' : 'Tidak Aktif'; ?>
                    </div>
                </div>
                
                <div class="status-card" id="host-status">
                    <div class="card-icon">🌐</div>
                    <div class="status-title">Host</div>
                    <div class="status-value" id="host-status-value">
                        <?php echo $monitoredHost; ?>
                    </div>
                </div>
                
                <div class="status-card" id="script-directory">
                    <div class="card-icon">📁</div>
                    <div class="status-title">Direktori</div>
                    <div class="status-value path-text" id="script-directory-value">
                        <?php echo $scriptDirectory; ?>
                    </div>
                </div>
                
                <div class="status-card" id="script-path">
                    <div class="card-icon">📄</div>
                    <div class="status-title">Path Script</div>
                    <div class="status-value path-text" id="script-path-value">
                        <?php echo $scriptPath; ?>
                    </div>
                </div>
            </div>
            
            <div class="controls">
                <form method="post" id="start-form">
                    <input type="hidden" name="action" value="start">
                    <button type="submit" class="start-btn" <?php echo $scriptRunning ? 'disabled' : ''; ?>>
                        <span class="button-icon">▶️</span> Mulai
                    </button>
                </form>
                
                <form method="post" id="stop-form">
                    <input type="hidden" name="action" value="stop">
                    <button type="submit" class="stop-btn" <?php echo !$scriptRunning ? 'disabled' : ''; ?>>
                        <span class="button-icon">⏹️</span> Berhenti
                    </button>
                </form>
                
                <form method="post" id="restart-form">
                    <input type="hidden" name="action" value="restart">
                    <button type="submit" class="restart-btn">
                        <span class="button-icon">🔄</span> Mulai Ulang
                    </button>
                </form>
            </div>

            <div class="chart-section">
                <div class="chart-header">
                    <h2>Statistik Ping</h2>
                    <div class="last-ping-status" id="last-ping-status">
                        Ping Terakhir: Menunggu data...
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="pingChart"></canvas>
                </div>
            </div>

            <div class="log-section">
                <h2>Log Aktivitas</h2>
                <div class="log-container" id="log-container">
                    <pre id="log-content"><?php echo $logContent; ?></pre>
                </div>
            </div>
        </div>

        <!-- Bungkus konten Auto APN Switch dalam tab -->
        <div class="tab-content" id="apn-tab">
            <!-- Status cards untuk APN -->
            <div class="status-panel">
                <div class="status-card <?php echo isApnMonitorRunning() ? 'running pulse' : 'stopped'; ?>" id="apn-monitor-status-card">
                    <div class="card-icon"><?php echo isApnMonitorRunning() ? '⚙️' : '⛔'; ?></div>
                    <div class="status-title">Status Script</div>
                    <div class="status-value" id="apn-monitor-status-value">
                        <?php echo isApnMonitorRunning() ? 'Berjalan' : 'Berhenti'; ?>
                    </div>
                </div>
                
                <div class="status-card" id="apn-host-status">
                    <div class="card-icon">🌐</div>
                    <div class="status-title">Host</div>
                    <div class="status-value" id="apn-host-status-value">
                        <?php echo $monitoredHost; ?>
                    </div>
                </div>
                
                <div class="status-card" id="apn-script-directory">
                    <div class="card-icon">📁</div>
                    <div class="status-title">Direktori</div>
                    <div class="status-value path-text" id="apn-script-directory-value">
                        /data/adb/service.d
                    </div>
                </div>
                
                <div class="status-card" id="apn-script-path">
                    <div class="card-icon">📄</div>
                    <div class="status-title">Path Script</div>
                    <div class="status-value path-text" id="apn-script-path-value">
                        /data/adb/service.d/autoswitchapn.sh
                    </div>
                </div>
            </div>

            <div class="apn-monitor-section">
                <div class="section-header">
                    <h2>Auto APN Switch</h2>
                </div>
                <div class="apn-controls">
                    <form method="post" id="start-apn-form">
                        <input type="hidden" name="action" value="start_apn">
                        <button type="submit" class="apn-btn start-apn-btn" <?php echo isApnMonitorRunning() ? 'disabled' : ''; ?>>
                            <span class="button-icon">▶️</span> Mulai Auto APN Switch
                        </button>
                    </form>
                    
                    <form method="post" id="stop-apn-form">
                        <input type="hidden" name="action" value="stop_apn">
                        <button type="submit" class="apn-btn stop-apn-btn" <?php echo !isApnMonitorRunning() ? 'disabled' : ''; ?>>
                            <span class="button-icon">⏹️</span> Hentikan Auto APN Switch
                        </button>
                    </form>
                </div>
                <div class="apn-info">
                    <div class="current-apn" id="current-apn">
                        APN Saat Ini: Memuat...
                    </div>
                </div>
            </div>

            <div class="statistics-section">
                <div class="section-header">
                    <h2>Statistik Monitoring</h2>
                </div>
                <div class="stats-container">
                    <div class="stats-grid">
                        <div class="stats-card">
                            <div class="stats-title">Total Ping</div>
                            <div class="stats-value" id="total-ping">0</div>
                        </div>
                        <div class="stats-card">
                            <div class="stats-title">Ping Sukses</div>
                            <div class="stats-value success" id="success-ping">0</div>
                        </div>
                        <div class="stats-card">
                            <div class="stats-title">Ping Gagal</div>
                            <div class="stats-value failed" id="failed-ping">0</div>
                        </div>
                        <div class="stats-card">
                            <div class="stats-title">Pergantian APN</div>
                            <div class="stats-value" id="apn-switches">0</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="log-section">
                <h2>Log APN Monitor</h2>
                <div class="log-container" id="apn-log-container">
                    <pre id="apn-log-content">Memuat log...</pre>
                </div>
            </div>
        </div>

        <div class="timestamp" id="timestamp">
            Terakhir diperbarui: <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>

    <div class="app-footer">
        <p class="footer-text">
            <span class="footer-brand">Ping Monitor</span>
            <span class="footer-emoji">⚡</span>
            <span>mod by</span>
            <a href="https://t.me/latifan_id" class="footer-link">latifan_id</a>
        </p>
    </div>

    <script>
        // Konfigurasi chart yang benar-benar baru
        const pingData = <?php echo json_encode($pingData); ?>;
        
        // Buat data untuk chart
        const labels = pingData.times.map(time => time.substr(11, 5));
        const data = pingData.data;
        
        // Siapkan warna dan border dari data ping
        const backgroundColors = data.map(status => 
            status === 1 ? 'rgba(0, 230, 118, 0.5)' : 'rgba(255, 23, 68, 0.5)'  // Hijau untuk sukses, Merah untuk gagal
        );
        const borderColors = data.map(status => 
            status === 1 ? '#00E676' : '#FF1744'  // Hijau solid untuk sukses, Merah solid untuk gagal
        );
        
        // Buat chart baru dengan animasi dan styling yang lebih menarik
        const ctx = document.getElementById('pingChart').getContext('2d');
        const pingChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Status Ping',
                    data: data,
                    backgroundColor: backgroundColors,
                    borderColor: borderColors,
                    borderWidth: 3,
                    pointStyle: 'rectRounded',
                    pointRadius: 8,
                    pointHoverRadius: 12,
                    tension: 0.2,
                    fill: false,
                    stepped: 'before'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            size: 16
                        },
                        bodyFont: {
                            size: 14
                        },
                        padding: 15,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                return value === 1 ? '✅ Ping Berhasil' : '❌ Ping Gagal';
                            },
                            title: function(tooltipItems) {
                                return 'Waktu: ' + pingData.times[tooltipItems[0].dataIndex];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        min: -0.1,
                        max: 1.1,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            callback: function(value) {
                                if (value === 0) return '❌ Gagal';
                                if (value === 1) return '✅ Sukses';
                                return '';
                            },
                            font: {
                                size: 14
                            },
                            padding: 10
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            },
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                },
                elements: {
                    line: {
                        borderJoinStyle: 'round'
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
        
        // Fungsi untuk memperbarui grafik dengan style yang lebih menarik
        function updateChart(newData) {
            // Terbatas pada 20 data terakhir untuk tampilan yang lebih baik
            const maxDataPoints = 20;
            
            // Potong data jika lebih dari maxDataPoints
            let times = [...newData.times];
            let data = [...newData.data];
            if (times.length > maxDataPoints) {
                times = times.slice(-maxDataPoints);
                data = data.slice(-maxDataPoints);
            }
            
            // Persiapkan label dan warna
            const labels = times.map(time => time.substr(11, 5));
            const backgroundColors = data.map(status => 
                status === 1 ? 'rgba(0, 230, 118, 0.5)' : 'rgba(255, 23, 68, 0.5)'
            );
            const borderColors = data.map(status => 
                status === 1 ? '#00E676' : '#FF1744'
            );
            
            // Update chart data
            pingChart.data.labels = labels;
            pingChart.data.datasets[0].data = data;
            pingChart.data.datasets[0].backgroundColor = backgroundColors;
            pingChart.data.datasets[0].borderColor = borderColors;
            pingChart.update();
            
            // Update status ping terakhir dengan animasi
            if (data.length > 0) {
                const lastStatus = data[data.length - 1];
                const lastPingStatus = document.getElementById('last-ping-status');
                
                // Tambahkan animasi sederhana
                lastPingStatus.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    lastPingStatus.style.transform = 'scale(1)';
                }, 300);
                
                lastPingStatus.className = 'last-ping-status ' + (lastStatus === 1 ? 'success-ping' : 'failed-ping');
                lastPingStatus.innerHTML = 'Ping Terakhir: ' + (lastStatus === 1 ? '✅ Sukses' : '❌ Gagal');
            }
        }
        
        // Fungsi untuk memperbarui status
        function updateStatus() {
            fetch('?action=get_status')
                .then(response => response.json())
                .then(data => {
                    // Update status script
                    const scriptRunning = data.scriptRunning;
                    document.getElementById('script-status-value').textContent = scriptRunning ? 'Berjalan' : 'Berhenti';
                    const scriptStatus = document.getElementById('script-status');
                    scriptStatus.className = `status-card ${scriptRunning ? 'running pulse' : 'stopped'}`;
                    document.querySelector('.start-btn').disabled = scriptRunning;
                    document.querySelector('.stop-btn').disabled = !scriptRunning;
                    
                    // Update status mode pesawat
                    const airplaneMode = data.airplaneMode;
                    document.getElementById('airplane-status-value').textContent = airplaneMode ? 'Aktif' : 'Tidak Aktif';
                    const airplaneStatus = document.getElementById('airplane-status');
                    airplaneStatus.className = `status-card ${airplaneMode ? 'airplane-on pulse' : 'airplane-off'}`;
                    
                    // Update log content dengan syntax highlighting
                    const logContainer = document.getElementById('log-content');
                    let formattedLog = '';
                    const lines = data.logContent.split('\n');
                    for (const line of lines) {
                        if (line.includes("Host dapat dijangkau")) {
                            formattedLog += `<span class="log-line-success">${escapeHtml(line)}</span>\n`;
                        } else if (line.includes("Host tidak dapat dijangkau")) {
                            formattedLog += `<span class="log-line-error">${escapeHtml(line)}</span>\n`;
                        } else {
                            formattedLog += escapeHtml(line) + '\n';
                        }
                    }
                    logContainer.innerHTML = formattedLog;
                    
                    // Auto-scroll log ke bawah
                    const logContainerDiv = document.getElementById('log-container');
                    logContainerDiv.scrollTop = logContainerDiv.scrollHeight;
                    
                    // Update timestamp menggunakan waktu lokal
                    const timestamp = new Date(data.timestamp);
                    const options = {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false
                    };
                    document.getElementById('timestamp').textContent = 
                        'Terakhir diperbarui: ' + timestamp.toLocaleString('id-ID', options);
                    
                    // Update chart data jika ada data baru
                    if (data.pingData.times.length > 0) {
                        // Update grafik dengan data baru
                        updateChart(data.pingData);
                        
                        // Update direktori script
                        document.getElementById('script-directory-value').textContent = data.scriptDirectory;
                        document.getElementById('script-path-value').textContent = data.scriptPath;
                    }

                    // Update APN Monitor status
                    const apnMonitorRunning = data.apnMonitorRunning;
                    const apnStatusCard = document.getElementById('apn-monitor-status-card');
                    const apnStatusValue = document.getElementById('apn-monitor-status-value');
                    const apnStatusIcon = apnStatusCard.querySelector('.card-icon');
                    
                    // Update status card
                    apnStatusCard.className = `status-card ${apnMonitorRunning ? 'running pulse' : 'stopped'}`;
                    apnStatusValue.textContent = apnMonitorRunning ? 'Berjalan' : 'Berhenti';
                    apnStatusIcon.textContent = apnMonitorRunning ? '⚙️' : '⛔';
                    
                    // Update host dan path
                    document.getElementById('apn-host-status-value').textContent = data.monitoredHost;
                    document.getElementById('apn-script-directory-value').textContent = '/data/adb/service.d';
                    document.getElementById('apn-script-path-value').textContent = '/data/adb/service.d/autoswitchapn.sh';

                    // Update button states
                    const startApnBtn = document.querySelector('.start-apn-btn');
                    const stopApnBtn = document.querySelector('.stop-apn-btn');
                    if (startApnBtn && stopApnBtn) {
                        startApnBtn.disabled = apnMonitorRunning;
                        stopApnBtn.disabled = !apnMonitorRunning;
                    }

                    // Update current APN info
                    document.getElementById('current-apn').textContent = 
                        'APN Saat Ini:\n' + data.currentApn;

                    // Update statistik
                    const pingStats = {
                        total: data.pingData.data.length,
                        success: data.pingData.data.filter(x => x === 1).length,
                        failed: data.pingData.data.filter(x => x === 0).length
                    };
                    
                    document.getElementById('total-ping').textContent = pingStats.total;
                    document.getElementById('success-ping').textContent = pingStats.success;
                    document.getElementById('failed-ping').textContent = pingStats.failed;
                    document.getElementById('apn-switches').textContent = data.apnStats.switches;
                    
                    // Update log APN
                    const apnLogContent = document.getElementById('apn-log-content');
                    apnLogContent.innerHTML = formatLog(data.apnMonitorLog);
                    
                    // Auto-scroll log ke bawah
                    const apnLogContainer = document.getElementById('apn-log-container');
                    apnLogContainer.scrollTop = apnLogContainer.scrollHeight;
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        // Fungsi untuk escape HTML entities
        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
        
        // Update status setiap 5 detik
        setInterval(updateStatus, <?php echo $refreshInterval * 1000; ?>);
        
        // Jalankan saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            // Efek fade-out untuk pesan
            const message = document.getElementById('message');
            if (message) {
                setTimeout(() => {
                    message.style.opacity = 0;
                }, 5000);
            }
            
            // Tambahkan event listener untuk form submission
            document.getElementById('start-form').addEventListener('submit', function(e) {
                e.preventDefault();
                fetch('', {
                    method: 'POST',
                    body: new FormData(this)
                }).then(() => {
                    updateStatus();
                });
            });
            
            document.getElementById('stop-form').addEventListener('submit', function(e) {
                e.preventDefault();
                fetch('', {
                    method: 'POST',
                    body: new FormData(this)
                }).then(() => {
                    updateStatus();
                });
            });
            
            document.getElementById('restart-form').addEventListener('submit', function(e) {
                e.preventDefault();
                fetch('', {
                    method: 'POST',
                    body: new FormData(this)
                }).then(() => {
                    updateStatus();
                });
            });
            
            // Auto-scroll log ke bawah
            const logContainer = document.getElementById('log-container');
            logContainer.scrollTop = logContainer.scrollHeight;

            // Tambahkan event listener untuk form APN Monitor
            document.getElementById('start-apn-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const form = this;
                const formData = new FormData(form);
                
                // Disable buttons sementara request berjalan
                const buttons = document.querySelectorAll('.apn-btn');
                buttons.forEach(btn => btn.disabled = true);
                
                fetch('', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.message) {
                        // Tampilkan pesan
                        const message = document.createElement('div');
                        message.className = 'message';
                        message.textContent = data.message;
                        form.appendChild(message);
                        
                        // Hapus pesan setelah 3 detik
                        setTimeout(() => {
                            message.remove();
                        }, 3000);
                    }
                    // Update status
                    updateStatus();
                })
                .catch(error => {
                    console.error('Error:', error);
                })
                .finally(() => {
                    // Enable kembali buttons
                    buttons.forEach(btn => btn.disabled = false);
                });
            });
            
            document.getElementById('stop-apn-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const form = this;
                const formData = new FormData(form);
                
                // Disable buttons sementara request berjalan
                const buttons = document.querySelectorAll('.apn-btn');
                buttons.forEach(btn => btn.disabled = true);
                
                fetch('', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.message) {
                        // Tampilkan pesan
                        const message = document.createElement('div');
                        message.className = 'message';
                        message.textContent = data.message;
                        form.appendChild(message);
                        
                        // Hapus pesan setelah 3 detik
                        setTimeout(() => {
                            message.remove();
                        }, 3000);
                    }
                    // Update status
                    updateStatus();
                })
                .catch(error => {
                    console.error('Error:', error);
                })
                .finally(() => {
                    // Enable kembali buttons
                    buttons.forEach(btn => btn.disabled = false);
                });
            });

            // Tambahkan script untuk handling tab
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    // Remove active class from all buttons and contents
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to clicked button and corresponding content
                    button.classList.add('active');
                    const tabId = button.getAttribute('data-tab');
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                });
            });
        });
        
        // Dark Mode
        const darkModeToggle = document.getElementById('darkModeToggle');
        const body = document.body;
        
        // Cek apakah pengguna sudah pernah mengaktifkan dark mode
        if (localStorage.getItem('darkMode') === 'enabled') {
            body.classList.add('dark-mode');
            darkModeToggle.textContent = '☀️';
        } else {
            darkModeToggle.textContent = '🌙';
        }
        
        // Toggle dark mode
        darkModeToggle.addEventListener('click', function() {
            if (body.classList.contains('dark-mode')) {
                body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
                darkModeToggle.textContent = '🌙';
            } else {
                body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
                darkModeToggle.textContent = '☀️';
            }
            
            // Perbarui grafik untuk menyesuaikan dengan tema
            pingChart.options.scales.y.grid.color = getComputedStyle(document.documentElement)
                .getPropertyValue('--chart-grid');
            pingChart.options.scales.x.grid.color = getComputedStyle(document.documentElement)
                .getPropertyValue('--chart-grid');
            pingChart.update();
        });

        // Fungsi untuk format log dengan highlight
        function formatLog(logContent) {
            if (!logContent) return 'Tidak ada log...';
            
            return logContent.split('\n').map(line => {
                if (line.includes('Host dapat dijangkau')) {
                    return `<span style="color: var(--status-success)">${escapeHtml(line)}</span>`;
                } else if (line.includes('Host tidak dapat dijangkau')) {
                    return `<span style="color: var(--status-error)">${escapeHtml(line)}</span>`;
                } else if (line.includes('Mengaktifkan mode APN')) {
                    return `<span style="color: var(--apn-warning)">${escapeHtml(line)}</span>`;
                } else if (line.includes('Status APN')) {
                    return `<span style="color: var(--apn-primary)">${escapeHtml(line)}</span>`;
                }
                return escapeHtml(line);
            }).join('\n');
        }
    </script>
</body>
</html> 