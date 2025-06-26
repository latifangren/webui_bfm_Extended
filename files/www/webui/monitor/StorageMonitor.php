<?php
// Fungsi bantu untuk eksekusi command
function getSystemData($command) {
    $output = shell_exec($command);
    return trim($output) ?: '0'; // Return '0' jika kosong
}

// Fungsi untuk mendapatkan informasi penyimpanan
function getStorageInfo() {
    $info = [];
    
    // 1. Informasi partisi /data
    $storageData = getSystemData('df /data 2>/dev/null | tail -n1');
    if ($storageData && $storageData !== '0') {
        $storageParts = preg_split('/\s+/', trim($storageData));
        if (count($storageParts) >= 6) {
            $info['data'] = [
                'filesystem' => $storageParts[0],
                'total' => round($storageParts[1]/1024/1024, 1) . ' GB',
                'used' => round($storageParts[2]/1024/1024, 1) . ' GB',
                'free' => round($storageParts[3]/1024/1024, 1) . ' GB',
                'percent' => $storageParts[4],
                'mount' => $storageParts[5]
            ];
        }
    }
    
    // 2. Informasi partisi root (/)
    $rootData = getSystemData('df / 2>/dev/null | tail -n1');
    if ($rootData && $rootData !== '0') {
        $rootParts = preg_split('/\s+/', trim($rootData));
        if (count($rootParts) >= 6) {
            $info['root'] = [
                'filesystem' => $rootParts[0],
                'total' => round($rootParts[1]/1024/1024, 1) . ' GB',
                'used' => round($rootParts[2]/1024/1024, 1) . ' GB',
                'free' => round($rootParts[3]/1024/1024, 1) . ' GB',
                'percent' => $rootParts[4],
                'mount' => $rootParts[5]
            ];
        }
    }
    
    // 3. Informasi partisi tambahan
    $mounts = ['/storage', '/sdcard', '/mnt', '/system'];
    foreach ($mounts as $mount) {
        $mountData = getSystemData("df {$mount} 2>/dev/null | tail -n1");
        if ($mountData && $mountData !== '0') {
            $mountParts = preg_split('/\s+/', trim($mountData));
            if (count($mountParts) >= 6) {
                $info['mounts'][$mount] = [
                    'filesystem' => $mountParts[0],
                    'total' => round($mountParts[1]/1024/1024, 1) . ' GB',
                    'used' => round($mountParts[2]/1024/1024, 1) . ' GB',
                    'free' => round($mountParts[3]/1024/1024, 1) . ' GB',
                    'percent' => $mountParts[4],
                    'mount' => $mountParts[5]
                ];
            }
        }
    }
    
    // 4. Informasi cache yang lebih sederhana dan pasti berfungsi
    $cacheTypes = [
        'dalvik' => '/data/dalvik-cache',
        'app' => '/data/data/*/cache',
        'tmp' => '/data/local/tmp',
        'system' => '/cache'
    ];
    
    $cacheDetails = [];
    $totalCache = 0;
    
    foreach ($cacheTypes as $type => $path) {
        $size = (int)getSystemData("du -sk ".escapeshellarg($path)." 2>/dev/null | awk '{print $1}'");
        $cacheDetails[$type] = formatBytes($size * 1024); // Convert KB to bytes
        $totalCache += $size * 1024;
    }
    
    $info['cache'] = [
        'total' => formatBytes($totalCache),
        'details' => $cacheDetails
    ];
    
    return $info;
}

// Fungsi format bytes yang lebih baik
function formatBytes($bytes) {
    $bytes = (float)$bytes;
    if ($bytes <= 0) return '0 B';
    
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    
    return round($bytes/pow(1024, $i), 2).' '.$units[$i];
}

// Fungsi untuk membersihkan cache
function clearCache() {
    $commands = [
        'rm -rf /data/dalvik-cache/*',
        'rm -rf /data/data/*/cache/*',
        'rm -rf /data/local/tmp/*',
        'rm -rf /cache/*'
    ];
    
    $results = [];
    foreach ($commands as $cmd) {
        $results[$cmd] = shell_exec($cmd." 2>&1");
    }
    return $results;
}

// Handler AJAX
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] === 'get_data') {
        echo json_encode(getStorageInfo());
    } elseif ($_GET['action'] === 'clear_cache') {
        echo json_encode(clearCache());
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storage Monitor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
  --memory-color: #FFCA0A;
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
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--background);
            color: var(--text-primary);
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        .card {
            background: var(--card-bg);
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .card-title {
            font-size: 18px;
            font-weight: 500;
            margin: 0;
            display: flex;
            align-items: center;
        }
        .card-title i {
            margin-right: 8px;
            color: var(--primary);
        }
        .btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
        }
        .btn-danger {
            background: var(--danger);
        }
        .progress-container {
            height: 6px;
            background: rgba(0,0,0,0.05);
            border-radius: 3px;
            margin: 8px 0;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            background: var(--success);
        }
        .progress-warning {
            background: var(--warning) !important;
        }
        .progress-danger {
            background: var(--danger) !important;
        }
        .partition-info {
            margin-bottom: 12px;
        }
        .partition-name {
            font-weight: 500;
        }
        .partition-size {
            color: var(--text-secondary);
            font-size: 14px;
        }
        .cache-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 12px;
        }
        .cache-item {
            background: rgba(0,122,255,0.05);
            padding: 10px;
            border-radius: 8px;
        }
        .cache-label {
            font-size: 13px;
            color: var(--text-secondary);
        }
        .cache-value {
            font-weight: 500;
            margin-top: 4px;
        }
        .total-cache {
            background: rgba(255,59,48,0.1);
            color: var(--danger);
            padding: 10px;
            border-radius: 8px;
            font-weight: 500;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
        }
        .total-cache i {
            margin-right: 8px;
        }
        .last-update {
            text-align: right;
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }
        .mount-item {
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .mount-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Storage Monitor</h1>
        <div class="last-update" id="lastUpdate">Loading...</div>
        
        <!-- Cache Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-trash"></i> Cache Storage</h2>
                <button class="btn btn-danger" id="clearCacheBtn">Clear Cache</button>
            </div>
            <div class="total-cache">
                <i class="fas fa-database"></i>
                <span id="totalCache">Calculating...</span>
            </div>
            <div class="cache-grid" id="cacheDetails"></div>
        </div>
        
        <!-- Root Partition -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-hard-drive"></i> Root Partition</h2>
            </div>
            <div id="rootInfo"></div>
        </div>
        
        <!-- Data Partition -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-database"></i> Data Partition</h2>
            </div>
            <div id="dataInfo"></div>
        </div>
        
        <!-- Additional Partitions -->
        <div class="card" id="mountsCard" style="display:none;">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-folder-tree"></i> Additional Partitions</h2>
            </div>
            <div id="mountsInfo"></div>
        </div>
    </div>

    <script>
        function updateDisplay(data) {
            // Update cache info
            if (data.cache) {
                document.getElementById('totalCache').textContent = data.cache.total || '0 B';
                
                let cacheHtml = '';
                const details = data.cache.details || {};
                
                cacheHtml += `<div class="cache-item">
                    <div class="cache-label">Dalvik Cache</div>
                    <div class="cache-value">${details.dalvik || '0 B'}</div>
                </div>`;
                cacheHtml += `<div class="cache-item">
                    <div class="cache-label">App Cache</div>
                    <div class="cache-value">${details.app || '0 B'}</div>
                </div>`;
                cacheHtml += `<div class="cache-item">
                    <div class="cache-label">Temp Files</div>
                    <div class="cache-value">${details.tmp || '0 B'}</div>
                </div>`;
                cacheHtml += `<div class="cache-item">
                    <div class="cache-label">System Cache</div>
                    <div class="cache-value">${details.system || '0 B'}</div>
                </div>`;
                
                document.getElementById('cacheDetails').innerHTML = cacheHtml;
            }
            
            // Update root partition
            if (data.root) {
                const root = data.root;
                const rootHtml = `
                    <div class="partition-info">
                        <div class="partition-name">Storage Usage</div>
                        <div class="partition-size">${root.used} / ${root.total} (${root.percent})</div>
                        <div class="progress-container">
                            <div class="progress-bar ${getProgressClass(root.percent)}" style="width: ${root.percent.replace('%','')}%"></div>
                        </div>
                        <div class="partition-size">${root.free} free</div>
                    </div>
                `;
                document.getElementById('rootInfo').innerHTML = rootHtml;
            }
            
            // Update data partition
            if (data.data) {
                const dataPart = data.data;
                const dataHtml = `
                    <div class="partition-info">
                        <div class="partition-name">Storage Usage</div>
                        <div class="partition-size">${dataPart.used} / ${dataPart.total} (${dataPart.percent})</div>
                        <div class="progress-container">
                            <div class="progress-bar ${getProgressClass(dataPart.percent)}" style="width: ${dataPart.percent.replace('%','')}%"></div>
                        </div>
                        <div class="partition-size">${dataPart.free} free</div>
                    </div>
                `;
                document.getElementById('dataInfo').innerHTML = dataHtml;
            }
            
            // Update additional mounts
            if (data.mounts && Object.keys(data.mounts).length > 0) {
                document.getElementById('mountsCard').style.display = 'block';
                let mountsHtml = '';
                
                for (const [mount, info] of Object.entries(data.mounts)) {
                    mountsHtml += `
                        <div class="mount-item">
                            <div class="partition-info">
                                <div class="partition-name">${info.filesystem || mount}</div>
                                <div class="partition-size">${info.used} / ${info.total} (${info.percent})</div>
                                <div class="progress-container">
                                    <div class="progress-bar ${getProgressClass(info.percent)}" style="width: ${info.percent.replace('%','')}%"></div>
                                </div>
                                <div class="partition-size">${info.free} free</div>
                            </div>
                        </div>
                    `;
                }
                
                document.getElementById('mountsInfo').innerHTML = mountsHtml;
            } else {
                document.getElementById('mountsCard').style.display = 'none';
            }
            
            // Update timestamp
            document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
        }
        
        function getProgressClass(percent) {
            const pct = parseInt(percent.replace('%',''));
            if (pct > 80) return 'progress-danger';
            if (pct > 60) return 'progress-warning';
            return 'progress-bar';
        }
        
        function fetchData() {
            fetch('?action=get_data')
                .then(response => response.json())
                .then(data => {
                    updateDisplay(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('lastUpdate').textContent = 'Error loading data';
                });
        }
        
        // Clear cache button
        document.getElementById('clearCacheBtn').addEventListener('click', function() {
            if (confirm('Are you sure you want to clear all cache?')) {
                fetch('?action=clear_cache')
                    .then(response => response.json())
                    .then(result => {
                        alert('Cache cleared successfully!');
                        fetchData();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error clearing cache');
                    });
            }
        });
        
        // Initial load
        fetchData();
        // Refresh every 3 seconds
        setInterval(fetchData, 3000);
    </script>
<body>

    <footer style="text-align: center; margin-top: 20px; color: var(--text-secondary); font-size: 13px;">
        <a href="https://t.me/On_Progressss" target="_blank" style="color: var(--primary); text-decoration: none;">
            Telegram @Sogek1ng
        </a>
    </footer>
</body>
</html>