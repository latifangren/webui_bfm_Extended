<?php
// Menyiapkan variabel atau konfigurasi server jika diperlukan
$pageTitle = "LibreSpeed - Speedtest Lokal";
$version = "1.0";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gradient-bg: transparent;
            --card-bg: rgba(255, 255, 255, 0.9);
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.12);
            --text-color: #333333;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: var(--gradient-bg);
            color: var(--text-color);
            text-align: center;
            min-height: 100vh;
        }
        
        .container {
            max-width: 95%;
            margin: 0 auto;
            padding: 10px;
        }
        
        h1 {
            color: var(--text-color);
            font-size: 1.8rem;
            margin-bottom: 2px;
            text-shadow: none;
        }
        
        .subtitle {
            margin-top: 0;
            font-size: 0.95rem;
            margin-bottom: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .colorful-text {
            background: linear-gradient(90deg, #ff9a9e, #fad0c4, #fad0c4, #a1c4fd, #c2e9fb);
            background-size: 300% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradient-shift 10s ease infinite;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .version-badge {
            background-color: var(--accent-color);
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(247, 37, 133, 0.3);
            animation: pulse-badge 2s infinite;
        }
        
        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        @keyframes pulse-badge {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .card {
            background-color: var(--card-bg);
            border-radius: 15px;
            padding: 15px;
            margin: 10px 0;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
        }
        
        .button {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin: 10px 0;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(247, 37, 133, 0.3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .button i {
            margin-right: 8px;
        }
        
        .button:hover {
            background-color: #e5177b;
            transform: translateY(-2px);
        }
        
        .button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .results {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .result-box {
            text-align: center;
            padding: 10px 5px;
            margin: 5px;
            flex: 1;
            min-width: 80px;
            border-radius: 10px;
            background-color: white;
            box-shadow: 0 3px 8px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
        }
        
        .result-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--gradient-bg);
        }
        
        .result-icon {
            font-size: 18px;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .result-value {
            font-size: 24px;
            font-weight: bold;
            margin: 5px 0;
            color: var(--primary-color);
            transition: all 0.5s ease;
        }
        
        .result-value.animated {
            animation: pulse 0.5s ease;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .result-label {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }
        
        .result-unit {
            font-size: 12px;
            color: #999;
            margin-top: 2px;
        }
        
        .progress-container {
            margin: 15px 0;
            position: relative;
        }
        
        .progress-bar {
            height: 6px;
            width: 100%;
            background-color: rgba(67, 97, 238, 0.1);
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-fill {
            height: 100%;
            width: 0%;
            background: var(--gradient-bg);
            border-radius: 8px;
            transition: width 0.5s ease;
            position: relative;
        }
        
        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            height: 100%;
            width: 5px;
            background-color: rgba(255, 255, 255, 0.8);
            animation: pulse-light 1.5s infinite;
        }
        
        @keyframes pulse-light {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }
        
        .status {
            font-size: 14px;
            margin: 10px 0;
            color: #555;
            font-weight: 500;
        }
        
        .progress-percentage {
            position: absolute;
            right: 0;
            top: -20px;
            font-size: 12px;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .test-options {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .option-group {
            display: flex;
            align-items: center;
            gap: 5px;
            background-color: white;
            padding: 8px 12px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            width: 42%;
        }
        
        .option-group label {
            font-weight: 600;
            color: #555;
            font-size: 12px;
            white-space: nowrap;
        }
        
        .select-size {
            padding: 5px 8px;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            background-color: white;
            font-size: 12px;
            width: 100%;
            transition: all 0.3s ease;
            cursor: pointer;
            color: #444;
        }
        
        .select-size:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .footer {
            color: rgba(255,255,255,0.7);
            font-size: 10px;
            margin-top: 15px;
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 8px;
                max-width: 100%;
            }
            
            .card {
                padding: 12px;
            }
            
            .option-group {
                width: 100%;
                flex-direction: column;
                align-items: flex-start;
                padding: 6px 10px;
            }
            
            .results {
                margin-top: 10px;
            }
            
            .result-box {
                min-width: 70px;
                padding: 8px 5px;
            }
        }
        
        /* Layout compact untuk hasil tes */
        .results-container {
            margin-top: 10px;
        }
        
        .compact-layout {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .test-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        /* Mendukung dark mode */
        @media (prefers-color-scheme: dark) {
            :root {
                --card-bg: rgba(30, 30, 30, 0.9);
                --text-color: #e0e0e0;
                --shadow: 0 5px 15px rgba(0, 0, 0, 0.25);
            }
            
            .result-box {
                background-color: rgba(40, 40, 40, 0.9);
            }
            
            .result-value {
                color: var(--accent-color);
            }
            
            .result-label {
                color: #aaa;
            }
            
            .option-group {
                background-color: rgba(40, 40, 40, 0.9);
            }
            
            .option-group label {
                color: #aaa;
            }
            
            .select-size {
                background-color: rgba(60, 60, 60, 0.9);
                color: #ddd;
                border-color: #555;
            }
            
            .status {
                color: #aaa;
            }
            
            .colorful-text {
                text-shadow: 0 1px 3px rgba(0,0,0,0.2);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="colorful-text">LibreSpeed</h1>
        <p class="subtitle">
            <span class="colorful-text">Tes Kecepatan Jaringan Lokal</span> 
            <span class="version-badge"><?php echo "v$version"; ?></span>
        </p>
        
        <div class="card">
            <div class="test-options">
                <div class="option-group">
                    <label><i class="fas fa-download"></i> Download:</label>
                    <select id="downloadSize" class="select-size">
                        <option value="50">50 MB</option>
                        <option value="100">100 MB</option>
                        <option value="200">200 MB</option>
                    </select>
                </div>
                <div class="option-group">
                    <label><i class="fas fa-upload"></i> Upload:</label>
                    <select id="uploadSize" class="select-size">
                        <option value="50">50 MB</option>
                        <option value="100">100 MB</option>
                        <option value="200">200 MB</option>
                    </select>
                </div>
            </div>
            
            <button id="startButton" class="button"><i class="fas fa-play"></i> Mulai Tes</button>
            
            <div class="status" id="status">Siap untuk memulai pengujian...</div>
            
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <div class="progress-percentage" id="progressPercentage">0%</div>
            </div>
            
            <div class="results">
                <div class="result-box">
                    <div class="result-icon"><i class="fas fa-tachometer-alt"></i></div>
                    <div class="result-label">Ping</div>
                    <div class="result-value" id="pingValue">-</div>
                    <div class="result-unit">ms</div>
                </div>
                
                <div class="result-box">
                    <div class="result-icon"><i class="fas fa-download"></i></div>
                    <div class="result-label">Download</div>
                    <div class="result-value" id="downloadValue">-</div>
                    <div class="result-unit">Mbps</div>
                </div>
                
                <div class="result-box">
                    <div class="result-icon"><i class="fas fa-upload"></i></div>
                    <div class="result-label">Upload</div>
                    <div class="result-value" id="uploadValue">-</div>
                    <div class="result-unit">Mbps</div>
                </div>
            </div>
        </div>
        
        <?php if (isset($_GET['debug'])): ?>
        <div class="card">
            <h3>Debug Info</h3>
            <p>IP: <?php echo $_SERVER['REMOTE_ADDR']; ?></p>
            <p>User Agent: <?php echo $_SERVER['HTTP_USER_AGENT']; ?></p>
        </div>
        <?php endif; ?>
        
        <div class="footer">
            &copy; <?php echo date('Y'); ?> LibreSpeed - Speedtest Lokal
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Deteksi dark mode dari parent frame jika berada dalam iframe
            try {
                if (window.parent && window.parent.document.body.classList.contains('dark-mode')) {
                    document.body.classList.add('dark-mode');
                }
            } catch (e) {
                // Abaikan error cross-origin
                console.log("Tidak dapat mendeteksi mode dari parent frame");
            }
            
            // Elemen UI
            const startButton = document.getElementById('startButton');
            const status = document.getElementById('status');
            const progressFill = document.getElementById('progressFill');
            const progressPercentage = document.getElementById('progressPercentage');
            const pingValue = document.getElementById('pingValue');
            const downloadValue = document.getElementById('downloadValue');
            const uploadValue = document.getElementById('uploadValue');
            
            // Tambahkan elemen UI baru
            const downloadSizeSelect = document.getElementById('downloadSize');
            const uploadSizeSelect = document.getElementById('uploadSize');
            
            // URL backend
            const BACKEND_URL = './backend.php';
            
            // Mulai pengujian
            startButton.addEventListener('click', async function() {
                startButton.disabled = true;
                startButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sedang Tes...';
                
                // Reset hasil
                pingValue.textContent = '-';
                downloadValue.textContent = '-';
                uploadValue.textContent = '-';
                progressFill.style.width = '0%';
                progressPercentage.textContent = '0%';
                
                // Hapus kelas animasi jika ada
                document.querySelectorAll('.result-value').forEach(el => {
                    el.classList.remove('animated');
                });
                
                try {
                    // Tes Ping
                    status.textContent = 'Mengukur ping...';
                    updateProgress(10);
                    
                    const ping = await testPing();
                    pingValue.textContent = ping.toFixed(1);
                    pingValue.classList.add('animated');
                    
                    // Tes Download
                    status.textContent = 'Mengukur kecepatan download...';
                    updateProgress(30);
                    
                    const download = await testDownload();
                    downloadValue.textContent = download.toFixed(2);
                    downloadValue.classList.add('animated');
                    
                    // Tes Upload
                    status.textContent = 'Mengukur kecepatan upload...';
                    updateProgress(70);
                    
                    const upload = await testUpload();
                    uploadValue.textContent = upload.toFixed(2);
                    uploadValue.classList.add('animated');
                    
                    // Selesai
                    status.textContent = 'Pengujian selesai!';
                    updateProgress(100);
                    startButton.innerHTML = '<i class="fas fa-redo"></i> Tes Lagi';
                } catch (error) {
                    console.error('Error during test:', error);
                    status.textContent = 'Terjadi kesalahan: ' + error.message;
                    startButton.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Coba Lagi';
                }
                
                startButton.disabled = false;
            });
            
            // Fungsi untuk mengukur ping
            async function testPing() {
                const iterations = 3;
                let totalPing = 0;
                
                for (let i = 0; i < iterations; i++) {
                    const start = performance.now();
                    
                    try {
                        const response = await fetch(BACKEND_URL + '?ping=1&r=' + Math.random());
                        if (!response.ok) throw new Error('Ping error');
                        await response.text();
                    } catch (error) {
                        throw new Error('Koneksi gagal');
                    }
                    
                    const end = performance.now();
                    totalPing += (end - start);
                    
                    updateProgress(10 + (i * 5));
                }
                
                return totalPing / iterations; // Ping rata-rata
            }
            
            // Fungsi untuk mengukur download
            async function testDownload() {
                let totalSpeed = 0;
                let validTests = 0;
                
                const selectedSize = parseInt(downloadSizeSelect.value);
                status.textContent = `Download: Mengukur file ${selectedSize}MB...`;
                
                try {
                    const start = performance.now();
                    const response = await fetch(BACKEND_URL + '?download=' + selectedSize + '&r=' + Math.random());
                    
                    if (!response.ok) throw new Error('Download error');
                    
                    const reader = response.body.getReader();
                    let receivedLength = 0;
                    let lastUpdateTime = start;
                    let lastReceivedLength = 0;
                    let speedSamples = [];
                    
                    while (true) {
                        const {done, value} = await reader.read();
                        
                        if (done) break;
                        
                        const now = performance.now();
                        receivedLength += value.length;
                        
                        // Ambil sampel kecepatan setiap 200ms
                        if (now - lastUpdateTime > 200) {
                            const chunkSize = receivedLength - lastReceivedLength;
                            const timeSpentSec = (now - lastUpdateTime) / 1000;
                            
                            // Konversi ke Mbps (Megabit per second)
                            // 1 byte = 8 bit, 1 MB = 1024 * 1024 byte
                            const currentSpeed = (chunkSize * 8) / (timeSpentSec * 1024 * 1024);
                            
                            if (currentSpeed > 0) {
                                speedSamples.push(currentSpeed);
                                // Update nilai download sementara untuk feedback real-time
                                downloadValue.textContent = currentSpeed.toFixed(2);
                            }
                            
                            lastUpdateTime = now;
                            lastReceivedLength = receivedLength;
                            
                            // Update status
                            const percentComplete = (receivedLength / (selectedSize * 1024 * 1024)) * 100;
                            updateProgress(30 + (percentComplete * 0.2)); // 30-50% progress
                            status.textContent = `Download: ${(receivedLength / (1024 * 1024)).toFixed(1)}MB dari ${selectedSize}MB...`;
                        }
                    }
                    
                    // Jika kita mendapatkan sampel, gunakan rata-rata dari sampel tengah
                    if (speedSamples.length > 0) {
                        // Urutkan sampel dan buang 10% terendah dan tertinggi
                        speedSamples.sort((a, b) => a - b);
                        const trimAmount = Math.floor(speedSamples.length * 0.1);
                        if (speedSamples.length > 10) {
                            speedSamples = speedSamples.slice(trimAmount, speedSamples.length - trimAmount);
                        }
                        
                        // Hitung rata-rata
                        const avgSpeed = speedSamples.reduce((sum, speed) => sum + speed, 0) / speedSamples.length;
                        totalSpeed = avgSpeed;
                        validTests = 1;
                    } else {
                        // Fallback ke metode lama jika tidak ada sampel
                        const end = performance.now();
                        const durationSec = (end - start) / 1000;
                        const speedMbps = (receivedLength * 8) / (durationSec * 1024 * 1024);
                        
                        totalSpeed = speedMbps;
                        validTests = 1;
                    }
                    
                    updateProgress(50);
                } catch (error) {
                    console.error('Error during download test:', error);
                }
                
                return validTests > 0 ? totalSpeed : 0;
            }
            
            // Fungsi untuk mengukur upload
            async function testUpload() {
                let totalSpeed = 0;
                let validTests = 0;
                
                const selectedSize = parseInt(uploadSizeSelect.value);
                status.textContent = `Upload: Mengukur file ${selectedSize}MB...`;
                
                try {
                    const blob = new Blob([new ArrayBuffer(selectedSize * 1024 * 1024)], 
                                         {type: 'application/octet-stream'});
                    const formData = new FormData();
                    formData.append('file', blob, 'speedtest.dat');
                    
                    // Persiapkan XHR untuk mendapatkan progres upload
                    return new Promise((resolve, reject) => {
                        const xhr = new XMLHttpRequest();
                        let speedSamples = [];
                        let lastLoaded = 0;
                        let lastTime = performance.now();
                        
                        xhr.upload.onprogress = function(event) {
                            if (event.lengthComputable) {
                                const now = performance.now();
                                const loaded = event.loaded;
                                const percentage = (loaded / event.total) * 100;
                                updateProgress(70 + (percentage * 0.15)); // 70-85% progress
                                
                                // Hitung kecepatan real-time
                                if (now - lastTime > 200) {
                                    const bytesUploaded = loaded - lastLoaded;
                                    const timeSpentSec = (now - lastTime) / 1000;
                                    
                                    // Konversi ke Mbps
                                    const currentSpeed = (bytesUploaded * 8) / (timeSpentSec * 1024 * 1024);
                                    
                                    if (currentSpeed > 0) {
                                        speedSamples.push(currentSpeed);
                                        // Update nilai upload sementara
                                        uploadValue.textContent = currentSpeed.toFixed(2);
                                    }
                                    
                                    lastLoaded = loaded;
                                    lastTime = now;
                                }
                                
                                status.textContent = `Upload: ${(loaded / (1024 * 1024)).toFixed(1)}MB dari ${selectedSize}MB...`;
                            }
                        };
                        
                        xhr.onload = function() {
                            if (xhr.status >= 200 && xhr.status < 300) {
                                // Kalkulasi kecepatan final
                                if (speedSamples.length > 0) {
                                    // Urutkan dan buang outlier
                                    speedSamples.sort((a, b) => a - b);
                                    const trimAmount = Math.floor(speedSamples.length * 0.1);
                                    if (speedSamples.length > 10) {
                                        speedSamples = speedSamples.slice(trimAmount, speedSamples.length - trimAmount);
                                    }
                                    
                                    // Hitung rata-rata
                                    const avgSpeed = speedSamples.reduce((sum, speed) => sum + speed, 0) / speedSamples.length;
                                    resolve(avgSpeed);
                                } else {
                                    // Fallback ke kalkulasi tradisional
                                    const fileSize = selectedSize * 8; // Megabits
                                    const timeTakenSec = (performance.now() - lastTime) / 1000;
                                    resolve(fileSize / timeTakenSec);
                                }
                                
                                updateProgress(85);
                            } else {
                                reject(new Error('Upload error: ' + xhr.statusText));
                            }
                        };
                        
                        xhr.onerror = function() {
                            reject(new Error('Network error during upload'));
                        };
                        
                        xhr.open('POST', BACKEND_URL + '?upload=1', true);
                        xhr.send(formData);
                    });
                } catch (error) {
                    console.error('Error during upload test:', error);
                    return 0;
                }
            }
            
            // Fungsi untuk memperbarui progress bar
            function updateProgress(percentage) {
                percentage = Math.min(100, Math.max(0, percentage));
                progressFill.style.width = percentage + '%';
                progressPercentage.textContent = Math.round(percentage) + '%';
            }
        });
    </script>
</body>
</html> 