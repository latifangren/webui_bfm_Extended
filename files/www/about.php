<?php
session_start();
include 'auth/config.php';

// Check if login is enabled and if the user is not logged in
if (LOGIN_ENABLED && !isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changelog</title>
    <style>
        @font-face {
            font-family: 'Material Icons';
            font-style: normal;
            font-weight: 400;
            src: url('webui/fonts/MaterialIcons-Regular.woff2') format('woff2'),
                 url('webui/fonts/MaterialIcons-Regular.woff') format('woff');
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fe;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        /* Tab styles */
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 1.1em;
            color: #8898aa;
            position: relative;
        }
        .tab.active {
            color: #FECA0A;
            font-weight: bold;
        }
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #FECA0A;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        #changelog {
            max-height: 600px;
            overflow-y: auto;
            padding-right: 10px;
        }
        #changelog::-webkit-scrollbar {
            width: 8px;
        }
        #changelog::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        #changelog::-webkit-scrollbar-thumb {
            background: #FECA0A;
            border-radius: 4px;
        }
        #changelog::-webkit-scrollbar-thumb:hover {
            background: #e0b600;
        }
        .version {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .version:last-child {
            border-bottom: none;
        }
        .version-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .version-number {
            font-size: 1.5em;
            font-weight: bold;
            color: #FECA0A;
        }
        .version-date {
            color: #8898aa;
            font-size: 0.9em;
        }
        .change-type {
            margin: 10px 0;
            font-weight: bold;
            color: #525f7f;
        }
        .change-list {
            list-style-type: none;
            padding-left: 20px;
            margin: 0;
        }
        .change-item {
            margin: 8px 0;
            position: relative;
        }
        .change-item::before {
            content: "•";
            color: #FECA0A;
            position: absolute;
            left: -15px;
        }
        .tag {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin-right: 5px;
        }
        .tag-new { background: #2dce89; color: white; }
        .tag-fix { background: #fb6340; color: white; }
        .tag-improvement { background: #11cdef; color: white; }

        /* Credits styles */
        .credits-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .credits-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11);
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #eaecef;
        }

        .credits-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
        }

        .credits-card h3 {
            color: #FECA0A;
            margin-bottom: 15px;
            font-size: 1.2em;
            border-bottom: 2px solid #eaecef;
            padding-bottom: 10px;
        }

        .credits-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .credits-item {
            display: flex;
            align-items: center;
            margin: 12px 0;
            padding: 8px;
            border-radius: 8px;
            transition: background-color 0.2s;
        }

        .credits-item:hover {
            background-color: #f8f9fe;
        }

        .credits-item strong {
            color: #525f7f;
            margin-right: 8px;
            min-width: 100px;
        }

        .credits-item a {
            color: #FECA0A;
            text-decoration: none;
            transition: color 0.2s;
        }

        .credits-item a:hover {
            color: #e0b600;
        }

        .credits-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            background: #FECA0A;
            color: #000000;
            margin-left: auto;
        }

        /* Tambahan style untuk dark mode */
        body.dark-mode {
            background-color: #000000;
            color: #F1F1F1;
        }

        body.dark-mode .container {
            background: #121212;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        body.dark-mode .tab {
            color: #8898aa;
        }
        
        body.dark-mode .tab.active {
            color: #FECA0A;
        }

        body.dark-mode .version {
            border-bottom-color: #363945;
        }

        body.dark-mode .credits-card {
            background: #121212;
            border-color: #363945;
        }

        body.dark-mode .credits-item:hover {
            background-color: #1a1a1a;
        }
        
        body.dark-mode .version-number {
            color: #FECA0A;
        }
        
        body.dark-mode .change-item::before {
            color: #FECA0A;
        }
        
        body.dark-mode #changelog::-webkit-scrollbar-thumb {
            background: #FECA0A;
        }
        
        body.dark-mode #changelog::-webkit-scrollbar-track {
            background: #1a1a1a;
        }

        /* Style untuk tombol dark mode */
        .dark-mode-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px;
            border-radius: 50%;
            border: none;
            background: #FECA0A;
            color: #000000;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .dark-mode-toggle:hover {
            background: #e0b600;
            transform: translateY(-2px);
        }

        .dark-mode-toggle .material-icons {
            font-size: 20px;
        }
    </style>
</head>
<body class="<?php echo isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'true' ? 'dark-mode' : ''; ?>">
    <!-- Tambahkan tombol dark mode di sini -->
    <button class="dark-mode-toggle" onclick="toggleDarkMode()">
        <i class="material-icons">dark_mode</i>
    </button>

    <div class="container">
        <div class="tabs">
            <button class="tab" onclick="openTab(event, 'changelog')">Changelog</button>
            <button class="tab" onclick="openTab(event, 'credits')">Credits</button>
        </div>

        <div id="changelog" class="tab-content active">
            <h1 style="text-align: center; color: #FECA0A; margin-bottom: 30px;">Changelog</h1>

            <div class="version">
                <div class="version-header">
                    <span class="version-number">Version 2.0.0 (Extended)</span>
                    <span class="version-date">18 Maret 2025</span>
                </div>
                <div class="change-type">🚀 Fitur Baru</div>
                <ul class="change-list">
                    <li class="change-item">
                        <span class="tag tag-new">NEW</span>
                        Implementasi sistem auto apn switch di menu ping monitor
                    </li>
                </ul>

                <div class="change-type">🛠️ Perbaikan & Peningkatan</div>
                <ul class="change-list">
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Peningkatan performa dashboard dengan optimasi database
                    </li>
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Redesign antarmuka pengguna untuk pengalaman yang lebih baik
                    </li>
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Optimasi penggunaan RAM dan CPU
                    </li>
                    <li class="change-item">
                        <span class="tag tag-fix">FIX</span>
                        Perbaikan bug pada sistem notifikasi
                    </li>
                    <li class="change-item">
                        <span class="tag tag-fix">FIX</span>
                        Perbaikan masalah kompatibilitas dengan Android 14
                    </li>
                </ul>
            </div>

            <div class="version">
                <div class="version-header">
                    <span class="version-number">Version 1.0.11 (Extended)</span>
                    <span class="version-date">17 Maret 2025</span>
                </div>
                <div class="change-type">🚀 Optimasi & Peningkatan</div>
                <ul class="change-list">
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Migrasi Font Awesome dari CDN ke file lokal untuk mengurangi ketergantungan internet
                    </li>
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Implementasi font Roboto lokal untuk konsistensi tampilan di semua halaman
                    </li>
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Migrasi Iconify dari CDN ke file lokal di modul
                    </li>
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Optimasi performa dengan mengurangi ketergantungan pada CDN eksternal
                    </li>
                </ul>
            </div>

            <div class="version">
                <div class="version-header">
                    <span class="version-number">Version 1.0.10 (Extended)</span>
                    <span class="version-date">16 Maret 2025</span>
                </div>
                <div class="change-type">🚀 Fitur Baru</div>
                <ul class="change-list">
                    <li class="change-item">
                        <span class="tag tag-new">NEW</span>
                        Implementasi tema warna baru dengan kombo hitam (#000000), kuning (#FECA0A), dan putih (#F1F1F1)
                    </li>
                    <li class="change-item">
                        <span class="tag tag-new">NEW</span>
                        Penyederhanaan tampilan antarmuka untuk pengalaman pengguna yang lebih baik
                    </li>
                </ul>

                <div class="change-type">🛠️ Perbaikan & Peningkatan</div>
                <ul class="change-list">
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Penyederhanaan tombol dark mode untuk tampilan yang lebih bersih
                    </li>
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Mengoptimalkan tampilan di perangkat mobile dengan mengatasi masalah tumpang tindih teks "BOX UI"
                    </li>
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Penggunaan tema hitam sebagai default untuk mengurangi beban baterai pada perangkat AMOLED
                    </li>
                    <li class="change-item">
                        <span class="tag tag-fix">FIX</span>
                        Perbaikan tampilan pada mode gelap di berbagai halaman
                    </li>
                    <li class="change-item">
                        <span class="tag tag-fix">FIX</span>
                        Menghapus menu "Akun" yang tidak digunakan dari opsi box
                    </li>
                </ul>
            </div>

            <div class="version">
                <div class="version-header">
                    <span class="version-number">Version 1.0.9 (Extended)</span>
                    <span class="version-date">15 Maret 2025</span>
                </div>
                <div class="change-type">🚀 Fitur Baru</div>
                <ul class="change-list">
                    <li class="change-item">
                        <span class="tag tag-new">NEW</span>
                        Penambahan fitur Speed Test pada tema default
                    </li>
                    <li class="change-item">
                        <span class="tag tag-new">NEW</span>
                        Implementasi Ping Monitor dengan tampilan yang lebih modern
                    </li>
                    <li class="change-item">
                        <span class="tag tag-new">NEW</span>
                        Fitur Dark Mode pada Ping Monitor
                    </li>
                    <li class="change-item">
                        <span class="tag tag-new">NEW</span>
                        Penambahan halaman Dokumentasi dengan panduan lengkap
                    </li>
                    <li class="change-item">
                        <span class="tag tag-new">NEW</span>
                        Tombol Update WebUI yang mengarah ke repository GitHub
                    </li>
                </ul>

                <div class="change-type">🛠️ Perbaikan & Peningkatan</div>
                <ul class="change-list">
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Integrasi modul Speed Test dari tema Argon ke tema default
                    </li>
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Tampilan chart statistik ping yang lebih informatif dan menarik
                    </li>
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Tata letak yang lebih compact dan responsif untuk berbagai perangkat
                    </li>
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Integrasi Ping Monitor ke menu tema Default
                    </li>
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Desain halaman dokumentasi yang lebih modern dengan daftar isi
                    </li>
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Perubahan ikon menu Dokumentasi untuk konsistensi visual
                    </li>
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Penambahan fitur copy ke clipboard pada blok kode dokumentasi
                    </li>
                    <li class="change-item">
                        <span class="tag tag-fix">FIX</span>
                        Perbaikan tampilan path yang terpotong pada layar mobile
                    </li>
                    <li class="change-item">
                        <span class="tag tag-fix">FIX</span>
                        Perbaikan fungsi tombol copy yang tidak berfungsi di beberapa perangkat
                    </li>
                </ul>
            </div>

            <div class="version">
                <div class="version-header">
                    <span class="version-number">Version 1.0.8 (Extended)</span>
                    <span class="version-date">15 Maret 2025</span>
                </div>
                <div class="change-type">🚀 Fitur Baru</div>
                <ul class="change-list">
                    <li class="change-item">
                        <span class="tag tag-new">NEW</span>
                        Penambahan sistem update otomatis melalui Magisk Manager
                    </li>
                    <li class="change-item">
                        <span class="tag tag-new">NEW</span>
                        Penambahan akses direktori ADB di File Manager
                    </li>
                    <li class="change-item">
                        <span class="tag tag-new">NEW</span>
                        Penambahan akses direktori WebUI di File Manager
                    </li>
                </ul>

                <div class="change-type">🛠️ Perbaikan & Peningkatan</div>
                <ul class="change-list">
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Penyederhanaan nama menu di File Manager
                    </li>
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Peningkatan navigasi File Manager dengan tab baru
                    </li>
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Integrasi dengan sistem pembaruan Magisk
                    </li>
                </ul>
            </div>

            <div class="version">
                <div class="version-header">
                    <span class="version-number">Version 1.0.7 (Extended)</span>
                    <span class="version-date">13 Maret 2025</span>
                </div>
                <div class="change-type">🚀 Fitur Baru</div>
                <ul class="change-list">
                    <li class="change-item">
                        <span class="tag tag-new">NEW</span>
                        Penambahan hotspot manager
                    </li>
                    <li class="change-item">
                        <span class="tag tag-new">NEW</span>
                        Penambahan networktools
                    </li>
                    <li class="change-item">
                        <span class="tag tag-new">NEW</span>
                        Penambahan Cpu monitor
                    </li>
                    <li class="change-item">
                        <span class="tag tag-new">NEW</span>
                        Integrasi dengan Clash Dashboard
                    </li>
                </ul>

                <div class="change-type">🛠️ Perbaikan & Peningkatan</div>
                <ul class="change-list">
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Optimasi performa sidebar
                    </li>
                    <li class="change-item">
                        <span class="tag tag-fix">FIX</span>
                        Perbaikan tampilan responsif pada perangkat mobile
                    </li>
                    <li class="change-item">
                        <span class="tag tag-improvement">IMPROVEMENT</span>
                        Peningkatan keamanan sistem
                    </li>
                </ul>
            </div>

            <div class="version">
                <div class="version-header">
                    <span class="version-number">Version 1.0.7 (Stable)</span>
                    <span class="version-date">1 januari 2025</span>
                </div>
                <div class="change-type">📋 Initial Release</div>
                <ul class="change-list">
                    <li class="change-item">
                        <span class="tag tag-new">NEW</span>
                        Rilis versi BOX UI
                    </li>
                    <li class="change-item">
                        <span class="tag tag-new">NEW</span>
                        Implementasi fitur dasar sistem
                    </li>
                </ul>
            </div>
        </div>

        <div id="credits" class="tab-content">
            <h1 style="text-align: center; color: #FECA0A; margin-bottom: 30px;">Credits</h1>
            <div class="credits-container">
                <div class="credits-card">
                    <h3>🌟 Modder Extended Version</h3>
                    <div class="credits-list">
                        <div class="credits-item">
                            <strong>Developer:</strong>
                            <a href="https://github.com/latifangren" target="_blank">Latifan_id</a>
                            <span class="credits-badge">Extended</span>
                        </div>
                    </div>
                </div>

                <div class="credits-card">
                    <h3>👨‍💻 Developer & Contributor</h3>
                    <div class="credits-list">
                        <div class="credits-item">
                            <strong>WEB UI BFM:</strong>
                            <a href="https://github.com/geeks121/webui_bfm" target="_blank">geeks121/webui_bfm</a>
                        </div>
                        <div class="credits-item">
                            <strong>ARGON UI:</strong>
                            <span>taamarin, Gondes & Zay's</span>
                        </div>
                        <div class="credits-item">
                            <strong>PHP7 Server:</strong>
                            <a href="https://github.com/nosignals/magisk-php7-webserver" target="_blank">nosignals/magisk-php7-webserver</a>
                        </div>
                        <div class="credits-item">
                            <strong>BOX Magisk:</strong>
                            <a href="https://github.com/taamarin/box_for_magisk" target="_blank">taamarin/box_for_magisk</a>
                        </div>
                        <div class="credits-item">
                            <strong>Generator:</strong>
                            <a href="https://github.com/mitralola716/ocgen" target="_blank">mitralola716/ocgen</a>
                        </div>
                    </div>
                </div>

                <div class="credits-card">
                    <h3>💝 Special Thanks</h3>
                    <div class="credits-list">
                        <div class="credits-item">
                            <p style="margin: 0; color: #525f7f;">Terima kasih kepada seluruh pengguna dan pendukung yang telah berkontribusi dalam pengembangan proyek ini.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee;">
        <h3 style="color: #FECA0A;">Hubungi Saya</h3>
        <div style="display: flex; justify-content: center; gap: 20px;">
            <a href="https://t.me/latifan_id" target="_blank" style="text-decoration: none;">
                <div style="display: flex; align-items: center; color: #0088cc;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.223-.535.223l.19-2.712 4.94-4.465c.215-.19-.047-.297-.332-.107L9.65 13.449l-2.665-.878c-.58-.183-.594-.577.12-.852l10.375-4c.485-.174.915.107.413 1.502z"/>
                    </svg>
                    <span style="margin-left: 8px;">Telegram</span>
                </div>
            </a>
            <a href="https://www.youtube.com/@Bangoor_72" target="_blank" style="text-decoration: none;">
                <div style="display: flex; align-items: center; color: #FF0000;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                    <span style="margin-left: 8px;">YouTube</span>
                </div>
            </a>
            <a href="https://www.facebook.com/latifan.latifan.latifan.latif" target="_blank" style="text-decoration: none;">
                <div style="display: flex; align-items: center; color: #1877f2;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    <span style="margin-left: 8px;">Facebook</span>
                </div>
            </a>
        </div>
    </div>

    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            
            tablinks = document.getElementsByClassName("tab");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }

        // Fungsi untuk dark mode yang diperbaiki
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            
            // Simpan preferensi ke cookie (bukan localStorage)
            const isDarkMode = document.body.classList.contains('dark-mode');
            document.cookie = `darkMode=${isDarkMode};path=/;max-age=${60*60*24*365}`;
            
            // Update icon
            const icon = document.querySelector('.dark-mode-toggle .material-icons');
            if (isDarkMode) {
                icon.textContent = 'light_mode';
            } else {
                icon.textContent = 'dark_mode';
            }
        }

        // Check preferensi dark mode saat halaman dimuat
        document.addEventListener('DOMContentLoaded', () => {
            // Fungsi untuk mendapatkan nilai cookie
            function getCookie(name) {
                const value = `; ${document.cookie}`;
                const parts = value.split(`; ${name}=`);
                if (parts.length === 2) return parts.pop().split(';').shift();
                return null;
            }
            
            // Cek preferensi dari cookie
            const isDarkMode = getCookie('darkMode') === 'true';
            const icon = document.querySelector('.dark-mode-toggle .material-icons');
            
            if (isDarkMode) {
                document.body.classList.add('dark-mode');
                icon.textContent = 'light_mode';
            } else {
                document.body.classList.remove('dark-mode');
                icon.textContent = 'dark_mode';
            }
            
            // Set tab default active (changelog)
            document.querySelector('.tab').click();
        });
    </script>
</body>
</html> 