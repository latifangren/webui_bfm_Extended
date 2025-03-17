<?php
set_time_limit(300);
ini_set('memory_limit', '256M');

// URL OpenSpeedTest dengan parameter untuk menonaktifkan pengalihan
$openSpeedTestUrl = "https://openspeedtest.com/Get-widget.php?AutoStart=1&HideResult=true";
// URL untuk folder local
$localSpeedTestUrl = "./local/index.php";
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpeedTest Extended</title>
    <style>
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 400;
            src: url('../../webui/fonts/Poppins-Regular.woff2') format('woff2');
        }

        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 500;
            src: url('../../webui/fonts/Poppins-Medium.woff2') format('woff2');
        }

        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 600;
            src: url('../../webui/fonts/Poppins-SemiBold.woff2') format('woff2');
        }

        :root {
            --primary-color: #FECA0A;
            --secondary-color: #FECA0A;
            --accent-color: #FECA0A;
            --background-color: #E0E0E0;
            --card-bg: #FFFFFF;
            --text-color: #333333;
            --border-radius: 12px;
            --button-radius: 50px;
            --shadow: 0 4px 20px rgba(0,0,0,0.08);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --gradient: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        body.dark-mode {
            --primary-color: #FECA0A;
            --secondary-color: #FECA0A;
            --accent-color: #FECA0A;
            --background-color: #000000;
            --card-bg: #111111;
            --text-color: #F1F1F1;
            --shadow: 0 4px 20px rgba(254, 202, 10, 0.2);
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            transition: var(--transition);
            background-image: radial-gradient(circle at 10% 20%, rgba(254, 202, 10, 0.05) 0%, rgba(254, 202, 10, 0.05) 90%);
        }

        .header {
            background: var(--card-bg);
            padding: 0.8rem 1.2rem;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            height: 40px;
            border-bottom: 2px solid rgba(254, 202, 10, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .logo h1 {
            margin: 0;
            font-size: 1.4rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-weight: 600;
        }
        
        .credit {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            margin-left: 12px;
            gap: 4px;
        }
        
        .credit-text {
            color: var(--text-color);
            opacity: 0.8;
        }
        
        .credit-link {
            text-decoration: none;
            color: var(--accent-color);
            position: relative;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .credit-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 0;
            background: var(--gradient);
            transition: width 0.3s ease;
        }
        
        .credit-link:hover {
            color: var(--primary-color);
        }
        
        .credit-link:hover::after {
            width: 100%;
        }

        .tabs {
            background: var(--card-bg);
            padding: 0.7rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 0.5rem;
            box-shadow: 0 1px 8px rgba(0,0,0,0.03);
        }

        .tab {
            padding: 0.6rem 1.2rem;
            border: none;
            background: none;
            font-size: 0.95rem;
            color: var(--text-color);
            cursor: pointer;
            border-radius: var(--button-radius);
            transition: var(--transition);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .tab:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gradient);
            opacity: 0;
            z-index: -1;
            transition: opacity 0.3s ease;
            border-radius: var(--button-radius);
        }

        .tab.active:before {
            opacity: 1;
        }

        .tab.active {
            color: #000000;
            box-shadow: 0 4px 15px rgba(254, 202, 10, 0.3);
            transform: translateY(-2px);
        }

        .tab:not(.active):hover {
            background: rgba(254, 202, 10, 0.1);
            transform: translateY(-1px);
        }

        .controls {
            display: flex;
            gap: 0.8rem;
            align-items: center;
        }

        button {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: var(--button-radius);
            background: var(--gradient);
            color: #000000;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            font-size: 0.9rem;
            font-weight: 500;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        button:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: var(--button-radius);
            z-index: -1;
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.5s ease;
        }

        button:hover:after {
            transform: scaleX(1);
            transform-origin: left;
        }

        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(254, 202, 10, 0.25);
        }

        button.alternate {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        button.alternate:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gradient);
            opacity: 0;
            z-index: -1;
            transition: opacity 0.3s ease;
            border-radius: var(--button-radius);
        }

        button.alternate:hover:before {
            opacity: 1;
        }

        button.alternate:hover {
            color: #000000;
            border-color: transparent;
        }

        .speedtest-container {
            padding: 0.8rem;
            height: calc(100vh - 130px);
        }

        .tab-content {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            display: none;
            transition: var(--transition);
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .tab-content.active {
            display: block;
        }

        /* Icon styles */
        .icon {
            font-size: 1.1rem;
        }

        .icon-fullscreen::before {
            content: "⤢";
        }

        .icon-reload::before {
            content: "↻";
        }

        /* Toggle Switch untuk Dark Mode */
        .theme-switch {
            display: flex;
            align-items: center;
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to right, #ccc, #ddd);
            transition: .4s;
            border-radius: 50px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        input:checked + .slider {
            background: var(--gradient);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .theme-icon {
            margin-left: 8px;
            font-size: 1.1rem;
            transition: var(--transition);
        }
        
        .theme-icon::before {
            content: "☀️";
        }
        
        input:checked ~ .theme-icon::before {
            content: "🌙";
        }

        /* Glow effect untuk dark mode */
        body.dark-mode .tab.active,
        body.dark-mode button:hover {
            box-shadow: 0 0 15px rgba(254, 202, 10, 0.5);
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .header {
                padding: 0.5rem 0.8rem;
                height: 35px;
            }
            
            .tabs {
                flex-wrap: wrap;
                padding: 0.5rem;
            }
            
            .tab {
                padding: 0.5rem 0.8rem;
                font-size: 0.85rem;
            }
            
            .controls {
                gap: 0.5rem;
            }

            .text-label {
                display: none;
            }

            button {
                padding: 0.5rem 0.8rem;
                font-size: 0.85rem;
            }
            
            .speedtest-container {
                padding: 0.5rem;
                height: calc(100vh - 120px);
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">
            <div class="logo-icon">🚀</div>
            <h1>SpeedTest Extended</h1>
            <div class="credit">
                <span class="credit-text">mod by</span>
                <a href="https://t.me/latifan_id" class="credit-link" target="_blank">
                    latifan_id
                </a>
            </div>
        </div>
    </div>
    
    <div class="tabs">
        <button class="tab active" data-tab="online">SpeedTest Online</button>
        <button class="tab" data-tab="local">SpeedTest Local</button>
        
        <div class="controls">
            <div class="theme-switch">
                <label class="switch">
                    <input type="checkbox" id="dark-mode-toggle">
                    <span class="slider"></span>
                </label>
                <span class="theme-icon"></span>
            </div>
            <button id="fullscreen-btn"><span class="icon icon-fullscreen"></span> <span class="text-label">Layar Penuh</span></button>
            <button id="reload-btn" class="alternate"><span class="icon icon-reload"></span> <span class="text-label">Muat Ulang</span></button>
        </div>
    </div>
    
    <div class="speedtest-container">
        <iframe id="online-frame" class="tab-content active" src="<?php echo $openSpeedTestUrl; ?>" allow="fullscreen" sandbox="allow-scripts allow-same-origin allow-forms"></iframe>
        <iframe id="local-frame" class="tab-content" src="<?php echo $localSpeedTestUrl; ?>" allow="fullscreen" sandbox="allow-scripts allow-same-origin allow-forms"></iframe>
    </div>
    
    <script>
        // Tab switching
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');
                
                // Nonaktifkan semua tab dan konten
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                // Aktifkan tab yang dipilih dan kontennya
                tab.classList.add('active');
                document.getElementById(`${tabId}-frame`).classList.add('active');
            });
        });
        
        // Tombol untuk mode layar penuh
        document.getElementById('fullscreen-btn').addEventListener('click', function() {
            const activeFrame = document.querySelector('.tab-content.active');
            if (activeFrame.requestFullscreen) {
                activeFrame.requestFullscreen();
            } else if (activeFrame.webkitRequestFullscreen) { /* Safari */
                activeFrame.webkitRequestFullscreen();
            } else if (activeFrame.msRequestFullscreen) { /* IE11 */
                activeFrame.msRequestFullscreen();
            }
        });
        
        // Tombol muat ulang
        document.getElementById('reload-btn').addEventListener('click', function() {
            const activeFrame = document.querySelector('.tab-content.active');
            activeFrame.src = activeFrame.src;
        });
        
        // Deteksi apakah perangkat adalah mobile
        if(/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            // Jika mobile, otomatis aktifkan layar penuh saat halaman pertama kali dimuat
            window.addEventListener('load', function() {
                setTimeout(function() {
                    const activeFrame = document.querySelector('.tab-content.active');
                    if (activeFrame.requestFullscreen) {
                        activeFrame.requestFullscreen();
                    } else if (activeFrame.webkitRequestFullscreen) {
                        activeFrame.webkitRequestFullscreen();
                    }
                }, 1000);
            });
        }

        // Tangkap dan cegah pengalihan ke halaman hasil untuk semua iframe
        document.querySelectorAll('iframe').forEach(iframe => {
            iframe.addEventListener('load', function() {
                try {
                    // Tambahkan event listener untuk menangkap klik pada iframe
                    this.contentWindow.addEventListener('click', function(event) {
                        // Periksa apakah klik pada elemen yang mungkin mengarah ke halaman hasil
                        if (event.target.closest('a[href*="results"]') || 
                            event.target.closest('[data-result-link]')) {
                            event.preventDefault();
                            event.stopPropagation();
                            return false;
                        }
                    }, true);
                } catch(e) {
                    // Abaikan kesalahan same-origin jika terjadi
                    console.log("Tidak dapat menambahkan event listener ke iframe karena kebijakan same-origin");
                }
            });
        });

        // Fungsi Dark Mode
        const darkModeToggle = document.getElementById('dark-mode-toggle');
        
        // Periksa preferensi dark mode yang tersimpan
        if(localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
            darkModeToggle.checked = true;
        }
        
        // Menambahkan event listener untuk toggle dark mode
        darkModeToggle.addEventListener('change', function() {
            if(this.checked) {
                document.body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
            }
        });
    </script>
</body>
</html>