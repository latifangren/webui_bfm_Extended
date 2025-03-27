<?php
// Fungsi untuk mendapatkan status AdGuard Home
function getAdGuardStatus() {
    return file_exists(PID_FILE);
}

// Fungsi untuk mendapatkan status iptables
function getIptablesStatus() {
    $config = executeCommand("grep '^enable_iptables=' " . SCRIPT_DIR . "/config.sh | sed 's/^enable_iptables=//'");
    return trim($config) === 'true';
}

// Fungsi untuk menjalankan perintah
function executeCommand($command) {
    return shell_exec('su -c "' . $command . '"');
}

// Tambahkan konstanta untuk path
define('AGH_DIR', '/data/adb/agh');
define('SCRIPT_DIR', AGH_DIR . '/scripts');
define('PID_FILE', AGH_DIR . '/bin/agh_pid');
define('MOD_PATH', '/data/adb/modules/AdGuardHome');

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'start':
                executeCommand(SCRIPT_DIR . "/service.sh start");
                if (getIptablesStatus()) {
                    executeCommand(SCRIPT_DIR . "/iptables.sh enable");
                }
                break;
            case 'stop':
                if (getIptablesStatus()) {
                    executeCommand(SCRIPT_DIR . "/iptables.sh disable");
                }
                executeCommand(SCRIPT_DIR . "/service.sh stop");
                break;
            case 'restart':
                if (getIptablesStatus()) {
                    executeCommand(SCRIPT_DIR . "/iptables.sh disable");
                }
                executeCommand(SCRIPT_DIR . "/service.sh stop");
                sleep(1);
                executeCommand(SCRIPT_DIR . "/service.sh start");
                if (getIptablesStatus()) {
                    executeCommand(SCRIPT_DIR . "/iptables.sh enable");
                }
                break;
        }
    }
    // Redirect untuk menghindari duplikasi POST saat refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$isRunning = getAdGuardStatus();
$iptablesEnabled = getIptablesStatus();
$statusText = $isRunning ? "Aktif" : "Tidak Aktif";
$statusColor = $isRunning ? "success" : "danger";
$iptablesText = $iptablesEnabled ? "Diaktifkan" : "Dinonaktifkan";
$iptablesColor = $iptablesEnabled ? "success" : "warning";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard AdGuard Home</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary-color: #FECA0A;
            --secondary-color: #FECA0A;
            --light-color: #333333;
            --success-color: #38B000;
            --danger-color: #D90429;
            --warning-color: #FECA0A;
            --background-color: #121212;
            --card-bg: #1E1E1E;
            --text-color: #E0E0E0;
            --card-header: #252525;
        }
        
        body {
            padding-top: 20px;
            background: var(--background-color);
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            min-height: 100vh;
        }
        
        .container {
            max-width: 100%;
            padding: 0 20px;
        }
        
        .card {
            margin-bottom: 20px;
            border-radius: 16px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            border: none;
            overflow: hidden;
            transition: all 0.3s ease;
            background-color: var(--card-bg);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(254, 202, 10, 0.2);
        }
        
        .card-header {
            padding: 1.2rem 1.5rem;
            border-bottom: none;
            background-color: var(--card-header);
            color: var(--text-color);
        }
        
        .card-body {
            padding: 1.5rem;
            color: var(--text-color);
        }
        
        .status-indicator {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
            box-shadow: 0 0 0 rgba(254, 202, 10, 0.4);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(254, 202, 10, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(254, 202, 10, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(254, 202, 10, 0);
            }
        }
        
        .status-card {
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            background: #252525;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            border-left: 3px solid var(--primary-color);
        }
        
        .status-card:hover {
            transform: translateX(5px);
            background: #2A2A2A;
        }
        
        .btn-control {
            margin-right: 10px;
            margin-bottom: 10px;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 500;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            transition: all 0.3s;
        }
        
        .btn-control:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(45deg, #38B000, #5CC91A);
        }
        
        .btn-danger {
            background: linear-gradient(45deg, #D90429, #EF233C);
        }
        
        .btn-warning {
            background: linear-gradient(45deg, #FECA0A, #FFD500);
            color: #000000;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), #FFD700);
            color: #000000;
        }
        
        .header-gradient {
            background: linear-gradient(135deg, #252525, #1A1A1A);
            border-bottom: 2px solid var(--primary-color);
        }
        
        .stats-card {
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            height: 100%;
            background: #252525;
            transition: all 0.3s ease;
            border-top: 3px solid var(--primary-color);
        }
        
        .stats-card:hover {
            transform: scale(1.05);
            background: #2A2A2A;
        }
        
        .stats-card h4 {
            font-weight: 600;
            color: var(--primary-color);
            margin-top: 5px;
        }
        
        .stats-card h6 {
            color: #CCC;
            font-weight: 500;
        }
        
        .admin-link {
            padding: 15px;
            border-radius: 12px;
            background: #252525;
            transition: all 0.3s ease;
            border: 1px solid rgba(254, 202, 10, 0.2);
        }
        
        .admin-link:hover {
            background: #2A2A2A;
            transform: translateX(5px);
            border-color: rgba(254, 202, 10, 0.4);
        }
        
        .admin-button {
            display: inline-block;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            border-radius: 50px;
            color: #000;
            background: linear-gradient(45deg, var(--primary-color), #FFD700);
            border: none;
            box-shadow: 0 4px 15px rgba(254, 202, 10, 0.3);
            transition: all 0.3s ease;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
            z-index: 1;
            width: 80%;
            max-width: 300px;
        }
        
        .admin-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(254, 202, 10, 0.4);
            color: #000;
        }
        
        .admin-button:active {
            transform: translateY(-2px);
        }
        
        .admin-button::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #FFD700, var(--primary-color));
            opacity: 0;
            z-index: -1;
            transition: opacity 0.3s ease;
        }
        
        .admin-button:hover::after {
            opacity: 1;
        }
        
        .admin-button i {
            margin-right: 8px;
            font-size: 18px;
        }
        
        .bg-success {
            background-color: var(--success-color) !important;
        }
        
        .bg-danger {
            background-color: var(--danger-color) !important;
        }
        
        .bg-warning {
            background-color: var(--warning-color) !important;
        }
        
        .text-success {
            color: var(--success-color) !important;
        }
        
        .text-danger {
            color: var(--danger-color) !important;
        }
        
        .text-warning {
            color: var(--warning-color) !important;
        }
        
        .progress-bar {
            background-color: var(--primary-color);
        }
        
        .progress {
            background-color: #333333;
        }
        
        /* Tambahan style untuk posisi tengah dan logo */
        .center-content {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            text-align: center;
        }
        
        .logo-container {
            margin-bottom: 20px;
            text-align: center;
            color: var(--primary-color);
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }
        
        .logo-container img {
            max-width: 120px;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
            transition: all 0.3s ease;
            border-radius: 12px;
        }
        
        .logo-container img:hover {
            transform: scale(1.05);
        }
        
        .control-buttons {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        
        .alert-warning {
            background-color: rgba(254, 202, 10, 0.1);
            color: var(--warning-color);
            border-color: rgba(254, 202, 10, 0.2);
        }
        
        .text-muted {
            color: #AAAAAA !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid px-4">
        <div class="logo-container mt-3">
            <h2 class="mt-3">AdGuard Home Manager</h2>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header header-gradient text-white">
                        <h3 class="card-title mb-0 text-center"><i class="fas fa-shield-alt mr-2"></i> Dashboard AdGuard Home</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($isRunning): ?>
                        <div class="admin-link mb-4 center-content">
                            <h5><i class="fas fa-cog me-2"></i> Panel Admin AdGuard Home</h5>
                            <a href="http://192.168.43.1:3000" target="_blank" class="admin-button">
                                <i class="fas fa-external-link-alt"></i> Buka Panel Admin
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-4 center-content">
                            <h4 class="mb-3">Status:</h4>
                            <div class="status-card bg-light w-100">
                                <div class="status-indicator bg-<?php echo $statusColor; ?>"></div>
                                <h5 class="mb-0">AdGuard Home saat ini <span class="text-<?php echo $statusColor; ?> fw-bold"><?php echo $statusText; ?></span></h5>
                            </div>
                            <div class="status-card bg-light w-100">
                                <div class="status-indicator bg-<?php echo $iptablesColor; ?>"></div>
                                <h5 class="mb-0">Iptables <span class="text-<?php echo $iptablesColor; ?> fw-bold"><?php echo $iptablesText; ?></span></h5>
                            </div>
                        </div>
                        
                        <h4 class="mb-3 text-center">Kontrol AdGuard Home:</h4>
                        <div class="control-buttons">
                            <form method="post">
                                <input type="hidden" name="action" value="start">
                                <button type="submit" class="btn btn-success btn-control">
                                    <i class="fas fa-play me-2"></i> Hidupkan
                                </button>
                            </form>
                            
                            <form method="post">
                                <input type="hidden" name="action" value="stop">
                                <button type="submit" class="btn btn-danger btn-control">
                                    <i class="fas fa-stop me-2"></i> Matikan
                                </button>
                            </form>
                            
                            <form method="post">
                                <input type="hidden" name="action" value="restart">
                                <button type="submit" class="btn btn-warning btn-control">
                                    <i class="fas fa-sync-alt me-2"></i> Restart
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body center-content">
                        <?php if ($isRunning): ?>
                        <div class="w-100">
                            <?php
                            $stats = executeCommand("curl -s http://192.168.43.1:3000/control/stats");
                            $stats = json_decode($stats, true);
                            if ($stats): 
                            ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="stats-card bg-light">
                                        <h6>Total Kueri DNS</h6>
                                        <h4><?php echo isset($stats['num_dns_queries']) ? number_format($stats['num_dns_queries']) : 'N/A'; ?></h4>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="stats-card bg-light">
                                        <h6>Diblokir</h6>
                                        <h4><?php echo isset($stats['num_blocked_filtering']) ? number_format($stats['num_blocked_filtering']) : 'N/A'; ?></h4>
                                    </div>
                                </div>
                            </div>
                            <?php if(isset($stats['num_dns_queries']) && isset($stats['num_blocked_filtering']) && $stats['num_dns_queries'] > 0): ?>
                            <div class="progress mt-2 mb-2" style="height: 10px; border-radius: 5px;">
                                <div class="progress-bar" role="progressbar" 
                                    style="width: <?php echo ($stats['num_blocked_filtering'] / $stats['num_dns_queries'] * 100); ?>%;" 
                                    aria-valuenow="<?php echo ($stats['num_blocked_filtering'] / $stats['num_dns_queries'] * 100); ?>" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                </div>
                            </div>
                            <div class="text-center">
                                <small class="text-muted">
                                    <?php echo round(($stats['num_blocked_filtering'] / $stats['num_dns_queries'] * 100), 1); ?>% kueri diblokir
                                </small>
                            </div>
                            <?php endif; ?>
                            <?php else: ?>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i> Harap hidupkan AdGuard Home terlebih dahulu untuk melihat informasi.
                        </div>
                        <div class="text-center mt-4">
                            <img src="../assets/img/inactive.png" alt="AdGuard Home Tidak Aktif" class="img-fluid" style="max-width: 200px; opacity: 0.7;">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <?php if (file_exists('../assets/js/fontawesome.all.min.js')): ?>
    <script src="../assets/js/fontawesome.all.min.js"></script>
    <?php else: ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js" integrity="sha512-yFjZbTYRCJodnuyGlsKamNE/LlEaEAxSUDe5+u61mV8zzqJVFOH7TnULE97kD8peyTZJ+W2uIzYggOp5cLB2vg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <?php endif; ?>
</body>
</html>
