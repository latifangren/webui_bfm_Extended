<?php
// Battery Monitor
// Save as battery_monitor.php in your web server directory

function getBatteryInfo() {
    $base_path = '/sys/class/power_supply/battery/';
    $info = [];
    
    if (!file_exists($base_path)) {
        return ['error' => "Battery path not accessible. Ensure device is rooted and permissions are set."];
    }

    // Basic info
    $info['capacity'] = (int)readBatteryFile($base_path.'capacity');
    $info['status'] = readBatteryFile($base_path.'status');
    $info['health'] = readBatteryFile($base_path.'health');
    $info['technology'] = readBatteryFile($base_path.'technology');
    
    // Voltage and current
    $info['voltage_now'] = (int)readBatteryFile($base_path.'voltage_now');
    $info['current_now'] = (int)readBatteryFile($base_path.'current_now');
    
    // Temperature
    $info['temp'] = (int)readBatteryFile($base_path.'temp');
    
    // Charge counters
    $info['charge_full'] = (int)readBatteryFile($base_path.'charge_full');
    $info['charge_full_design'] = (int)readBatteryFile($base_path.'charge_full_design');
    $info['charge_counter'] = (int)readBatteryFile($base_path.'charge_counter');
    
    // Time estimates
    $info['time_to_full'] = (int)readBatteryFile($base_path.'time_to_full_now');
    $info['time_to_empty'] = (int)readBatteryFile($base_path.'time_to_empty_now');
    
    // Additional info
    $info['cycle_count'] = readBatteryFile($base_path.'cycle_count');
    
    // Format values
    if ($info['voltage_now'] > 0) $info['voltage_now'] = $info['voltage_now'] / 1000000;
    if ($info['current_now'] > 0) $info['current_now'] = $info['current_now'] / 1000;
    if ($info['temp'] > 0) $info['temp'] = $info['temp'] / 10;
    if ($info['charge_full'] > 0) $info['charge_full'] = $info['charge_full'] / 1000;
    if ($info['charge_full_design'] > 0) $info['charge_full_design'] = $info['charge_full_design'] / 1000;
    if ($info['charge_counter'] > 0) $info['charge_counter'] = $info['charge_counter'] / 1000;
    
    // Calculate health percentage
    if ($info['charge_full_design'] > 0 && $info['charge_full'] > 0) {
        $info['health_percent'] = round(($info['charge_full'] / $info['charge_full_design']) * 100, 1);
    }
    
    return $info;
}

function readBatteryFile($path) {
    return file_exists($path) ? trim(file_get_contents($path)) : null;
}

$battery = getBatteryInfo();
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Battery Monitor - iStoreOS</title>
    <style>
    :root {
  /* Color Theme */
  --primary: #FFCA0A; /* Kuning */
  --primary-light: rgba(255, 202, 10, 0.1);
  --background: #000000; /* Hitam */
  --card-bg: rgba(0, 0, 0, 0.85); /* Card hitam transparan */
  --cards-bg: rgba(255, 255, 255, 0.05);
  --blur-value: 2px;
  --blur-values: 4px;
  --text-primary: #FFCA0A; /* Teks utama kuning */
  --text-secondary: #FFFDE4; /* Teks sekunder putih kekuningan */
  --border: #FFCA0A; /* Border kuning */
  --success: #34C759;
  --warning: #FFCA0A;
  --danger: #FF3B30;
  
  /* Specific Colors */
  --cpu-color: #FFCA0A;
  --memory-color: #5856D6;
  --battery-color: #FFCA0A;
  --storage-color: #AF52DE;
  --signal-color: #FFCA0A;
  --network-color: #5AC8FA;
  --uptime-color: #FFFF00;
  --mobile-color: #FFCA0A;
  --wifi-color: #FFCA0A;
  --usb-color: #34C759;
  --eth-color: #5856D6;
  
  /* Network Usage Colors */
  --download-color: #FFCA0A;
  --upload-color: #FF3B30;
  --total-color: #AF52DE;
}

@media (prefers-color-scheme: dark) {
  :root {
    --background: #000000;
    --card-bg: rgba(0, 0, 0, 0.85);
    --cards-bg: rgba(255, 255, 255, 0.05);
    --blur-value: 2px;
    --blur-values: 4px;
    --text-primary: #FFCA0A;
    --text-secondary: #FFFDE4;
    --border: #FFCA0A;
    --download-color: #FFCA0A;
    --upload-color: #FF3B30;
    --total-color: #1db54d;
  }
}
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--background);
            color: var(--text-primary);
            line-height: 1.5;
            padding: 10px;
        }

.battery-fill {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.2);
  }
  100% {
    transform: scale(1);
  }
}
        
        .container {
            max-width: 100%;
            margin: 0 auto;
            background: var(--card-bg);
            border: 3px solid var(--text-primary);
            border-radius: 12px;
            box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
    .header {
        color: var(--text-primary);
        padding: 18px 20px;
        text-align: center;
    }
        
        .header h1 {
            font-size: 20px;
            font-weight: 500;
        }
        
        .battery-summary {
            padding: 20px;
            text-align: center;
            border-bottom: 3px solid var(--text-primary);
        }
        
        .battery-percent {
            font-size: 48px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-primary);
        }
        
        .battery-status {
            font-size: 16px;
            color: var(--text-secondary);
            margin-bottom: 15px;
        }
        
        .battery-visual {
            width: 150px;
            height: 70px;
            margin: 0 auto 15px;
            border: 3px solid var(--text-primary);
            border-radius: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .battery-visual:after {
            content: '';
            position: absolute;
            right: -8px;
            top: 20px;
            width: 5px;
            height: 20px;
            background: var(--text-primary);
            border-radius: 0 3px 3px 0;
        }
        
        .battery-fill {
            height: 100%;
            transition: width 0.5s ease;
        }
        
        .battery-sections {
            padding: 15px;
        }
        
        .section {
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 12px;
            color: var(--primary);
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 8px;
            font-size: 18px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .info-item {
            background: var(--background);
            border-radius: 8px;
            padding: 12px;
        }
        
        .info-label {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 500;
        }
        
        .health-indicator {
            width: 100%;
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .health-bar {
            height: 100%;
            border-radius: 3px;
        }
        
        .time-estimate {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--background);
            border-radius: 8px;
            padding: 12px 15px;
            margin-top: 10px;
        }
        
        .time-label {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .time-value {
            font-weight: 600;
            font-size: 16px;
        }
        
        .alert {
            padding: 12px 15px;
            background: #fff8e6;
            border-left: 4px solid var(--warning);
            margin: 15px 0;
            border-radius: 8px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        
        .alert i {
            margin-right: 8px;
            color: var(--warning);
        }
        
        /* Status colors */
        .status-charging { color: var(--success); }
        .status-discharging { color: var(--warning); }
        .status-full { color: var(--primary); }
        
        /* Temperature colors */
        .temp-normal { color: var(--success); }
        .temp-warning { color: var(--warning); }
        .temp-danger { color: var(--danger); }
        
        /* Health colors */
        .health-good { background: var(--success); }
        .health-fair { background: var(--warning); }
        .health-poor { background: var(--danger); }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-battery-three-quarters"></i> Battery Information</h1>
        </div>
        
        <?php if (isset($battery['error'])): ?>
            <div style="padding: 20px; color: var(--danger);">
                <i class="fas fa-exclamation-triangle"></i> <?= $battery['error'] ?>
            </div>
        <?php else: ?>
            <div class="battery-summary">
                <div class="battery-percent"><?= $battery['capacity'] ?>%</div>
                <div class="battery-status">
                    <?php 
                        $status_class = 'status-' . strtolower($battery['status']);
                        $status_text = $battery['status'];
                        if ($status_text == 'Charging') $status_text = 'Charging';
                        elseif ($status_text == 'Discharging') $status_text = 'Discharging';
                        elseif ($status_text == 'Full') $status_text = 'Fully Charged';
                    ?>
                    <span class="<?= $status_class ?>"><?= $status_text ?></span>
                    <?php if ($battery['technology']): ?>
                        • <?= $battery['technology'] ?>
                    <?php endif; ?>
                </div>
                
                <div class="battery-visual">
                    <div class="battery-fill" style="
                        width: <?= $battery['capacity'] ?>%;
                        background: <?= 
                            $battery['capacity'] > 70 ? 'var(--success)' : 
                            ($battery['capacity'] > 30 ? 'var(--warning)' : 'var(--danger)')
                        ?>;
                    "></div>
                </div>
            </div>
            
            <div class="battery-sections">
                <?php if ($battery['time_to_full'] > 0 || $battery['time_to_empty'] > 0): ?>
                <div class="section">
                    <div class="section-title">
                        <i class="fas fa-clock"></i>
                        <span>Time Estimate</span>
                    </div>
                    <div class="time-estimate">
                        <div class="time-label">
                            <?= $battery['status'] == 'Charging' ? 'Time until full:' : 'Time remaining:' ?>
                        </div>
                        <div class="time-value">
                            <?php
                                $time = $battery['status'] == 'Charging' ? $battery['time_to_full'] : $battery['time_to_empty'];
                                $hours = floor($time / 3600);
                                $minutes = floor(($time % 3600) / 60);
                                echo $hours > 0 ? $hours.'h ' : '';
                                echo $minutes.'m';
                            ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="section">
                    <div class="section-title">
                        <i class="fas fa-heartbeat"></i>
                        <span>Battery Health</span>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Health Status</div>
                            <div class="info-value"><?= $battery['health'] ?: 'N/A' ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Maximum Capacity</div>
                            <div class="info-value"><?= $battery['charge_full'] ? $battery['charge_full'].' mAh' : 'N/A' ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Design Capacity</div>
                            <div class="info-value"><?= $battery['charge_full_design'] ? $battery['charge_full_design'].' mAh' : 'N/A' ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Current Charge</div>
                            <div class="info-value"><?= $battery['charge_counter'] ? $battery['charge_counter'].' mAh' : 'N/A' ?></div>
                        </div>
                    </div>
                    
                    <?php if (isset($battery['health_percent'])): ?>
                    <div style="margin-top: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <div class="info-label">Battery Health</div>
                            <div class="info-value"><?= $battery['health_percent'] ?>%</div>
                        </div>
                        <div class="health-indicator">
                            <div class="health-bar <?= 
                                $battery['health_percent'] > 80 ? 'health-good' : 
                                ($battery['health_percent'] > 60 ? 'health-fair' : 'health-poor')
                            ?>" style="width: <?= $battery['health_percent'] ?>%"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="section">
                    <div class="section-title">
                        <i class="fas fa-bolt"></i>
                        <span>Power Metrics</span>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Voltage</div>
                            <div class="info-value"><?= $battery['voltage_now'] ? number_format($battery['voltage_now'], 2).' V' : 'N/A' ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Current</div>
                            <div class="info-value"><?= $battery['current_now'] ? abs($battery['current_now']).' mA' : 'N/A' ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-title">
                        <i class="fas fa-thermometer-half"></i>
                        <span>Temperature</span>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Current Temp</div>
                            <div class="info-value <?= 
                                $battery['temp'] > 45 ? 'temp-danger' : 
                                ($battery['temp'] > 40 ? 'temp-warning' : 'temp-normal')
                            ?>">
                                <?= $battery['temp'] ? $battery['temp'].' °C' : 'N/A' ?>
                            </div>
                        </div>
                        <?php if ($battery['cycle_count']): ?>
                        <div class="info-item">
                            <div class="info-label">Cycle Count</div>
                            <div class="info-value"><?= $battery['cycle_count'] ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($battery['temp'] > 45): ?>
                <div class="alert">
                    <i class="fas fa-temperature-high"></i>
                    <div>High battery temperature detected (<?= $battery['temp'] ?>°C). Consider cooling your device.</div>
                </div>
                <?php elseif (isset($battery['health_percent']) && $battery['health_percent'] < 80): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>Battery health reduced to <?= $battery['health_percent'] ?>% of original capacity.</div>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
<body>

    <footer style="text-align: center; margin-top: 20px; color: #8E8E93; font-size: 13px;">
        <a href="https://t.me/On_Progressss" target="_blank" style="color: var(--primary); text-decoration: none;">
            Telegram @Sogek1ng
        </a>
    </footer>
</body>
</html>