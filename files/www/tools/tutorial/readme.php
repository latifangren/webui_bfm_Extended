<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panduan Auto Switch APN</title>
    <!-- Font Roboto Lokal -->
    <style>
        @font-face {
            font-family: 'Roboto';
            font-style: normal;
            font-weight: 400;
            src: url('../../../webui/fonts/Roboto-Regular.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Roboto';
            font-style: normal;
            font-weight: 500;
            src: url('../../../webui/fonts/Roboto-Medium.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Roboto';
            font-style: normal;
            font-weight: 700;
            src: url('../../../webui/fonts/Roboto-Bold.woff2') format('woff2');
        }
        body {
            font-family: 'Roboto', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        h1, h2, h3 {
            color: #2c3e50;
            margin-top: 30px;
        }
        h1 {
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        pre {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #ddd;
        }
        code {
            font-family: 'Courier New', monospace;
            background-color: #f5f5f5;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .alert {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        ul, ol {
            padding-left: 25px;
        }
        .container {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
    <!-- Font Awesome Lokal -->
    <link rel="stylesheet" href="../../../webui/css/font-awesome.min.css">
</head>
<body>
    <div class="container">
    <nav class="navbar">
        <div class="navbar-menu">
            <a href="/tools/pingmonitor.php" class="navbar-item">
                <span class="icon">📡</span>
                <span>Ping Monitor</span>
            </a>
        </div>
    </nav>

    <style>
    .navbar {
        background: #000000;
        padding: 1rem;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        border-bottom: 2px solid #FECA0A;
    }

    .navbar-menu {
        display: flex;
        align-items: center;
    }

    .navbar-item {
        color: #F1F1F1;
        text-decoration: none;
        padding: 0.5rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
    }

    .navbar-item:hover {
        background: rgba(254, 202, 10, 0.1);
        color: #FECA0A;
    }

    .icon {
        font-size: 1.2rem;
    }
    </style>

    <div class="alert">
        <strong>Perhatian!</strong> File pendukung silahkan download di GitHub:
        <a href="https://github.com/latifangren/webui_bfm_Extended/tree/main/data/adb/service.d" target="_blank">
            https://github.com/latifangren/webui_bfm_Extended/tree/main/data/adb/service.d
        </a>
        <br>Ambil file seperlunya saja.
    </div>
    
    <div class="info">
        <strong>File Wajib</strong> agar fitur ini berjalan:
        <ol>
            <li>autoswitchapn.sh (wajib ada)</li>
            <li>pingmonitor.sh (wajib ada)</li>
        </ol>
        Taruh di folder data/adb/service.d<br>
        Atur permission menjadi 755 (penting)
    </div>

    <h1>Script Auto Switch APN</h1>

    <p>Script ini dirancang untuk secara otomatis beralih antara dua APN (Access Point Name) ketika koneksi internet terdeteksi bermasalah. Script akan memantau koneksi ke host tertentu dan beralih ke APN cadangan jika koneksi terputus.</p>

    <h2>Cara Kerja</h2>

    <p>Script ini berfungsi dengan cara:</p>
    <ol>
        <li>Melakukan ping secara berkala ke host tertentu (default: quiz.vidio.com)</li>
        <li>Jika ping gagal mencapai batas yang ditentukan (default: 10 kali), script akan beralih ke APN pertama</li>
        <li>Setelah jeda waktu tertentu, script akan beralih kembali ke APN kedua</li>
        <li>Proses ini akan terus berulang untuk memastikan koneksi internet tetap berjalan</li>
    </ol>

    <h2>Keuntungan Menggunakan Script Ini</h2>

    <ul>
        <li>Menjaga koneksi internet tetap stabil saat APN utama mengalami masalah</li>
        <li>Beralih secara otomatis tanpa perlu intervensi manual</li>
        <li>Dapat disesuaikan dengan kebutuhan dan preferensi pengguna</li>
        <li>Sangat berguna untuk area dengan koneksi internet yang tidak stabil</li>
        <li>Ideal untuk aplikasi yang membutuhkan koneksi internet terus-menerus</li>
    </ul>

    <h2>Persyaratan</h2>

    <ul>
        <li>Perangkat Android yang sudah di-root (diuji pada Android 9.0+)</li>
        <li>Terminal emulator atau akses shell</li>
        <li>Izin superuser (su)</li>
        <li>Magisk atau sistem pengelolaan root serupa</li>
        <li>Setidaknya dua APN yang sudah dikonfigurasi di perangkat</li>
    </ul>

    <h2>Konfigurasi Script</h2>

    <p>Sebelum menggunakan script, Anda perlu mengkonfigurasi ID APN yang sesuai dengan perangkat Anda:</p>

    <h3>Menemukan ID APN</h3>

    <ol>
        <li>Buka terminal dan jalankan perintah berikut (ganti "nama_apn" dengan nama APN Anda):
            <pre>su -c content query --uri content://telephony/carriers | grep nama_apn</pre>
        </li>
        <li>Catat ID APN yang muncul (biasanya berupa angka seperti 2910 atau 3439)</li>
        <li>Contoh output:
            <pre>row_id=2910, name=Vidio, numeric=51001, mcc=510, mnc=01</pre>
            Pada contoh di atas, ID APN untuk "Vidio" adalah 2910.
        </li>
    </ol>

    <h3>Mengubah Konfigurasi</h3>

    <p>Edit file <code>autoswitchapn.sh</code> dan ubah parameter berikut sesuai kebutuhan:</p>

    <ul>
        <li><strong>HOST</strong>: Alamat yang akan di-ping untuk memeriksa koneksi
            <ul>
                <li>Default: quiz.vidio.com</li>
                <li>Disarankan: pilih host yang selalu online dan responsif</li>
            </ul>
        </li>
        <li><strong>PING_FAIL_LIMIT</strong>: Jumlah kegagalan ping sebelum beralih APN
            <ul>
                <li>Default: 10</li>
                <li>Nilai lebih rendah: lebih responsif tetapi dapat menyebabkan perpindahan yang terlalu sering</li>
                <li>Nilai lebih tinggi: kurang responsif tetapi lebih stabil</li>
            </ul>
        </li>
        <li><strong>WAIT_TIME</strong>: Waktu tunggu (detik) sebelum beralih kembali
            <ul>
                <li>Default: 5</li>
                <li>Sesuaikan berdasarkan kecepatan respons jaringan Anda</li>
            </ul>
        </li>
        <li><strong>ID_APN</strong>: ID untuk APN pertama
            <ul>
                <li>Ganti dengan ID APN utama Anda</li>
            </ul>
        </li>
        <li><strong>ID_APN2</strong>: ID untuk APN kedua
            <ul>
                <li>Ganti dengan ID APN cadangan Anda</li>
            </ul>
        </li>
    </ul>

    <h2>Cara Menggunakan</h2>

    <h3>Instalasi</h3>

    <ol>
        <li>Salin file <code>autoswitchapn.sh</code> ke direktori <code>/data/adb/service.d/</code> di perangkat Android Anda:
            <pre>su -c cp /path/to/autoswitchapn.sh /data/adb/service.d/</pre>
        </li>
        <li>Berikan izin eksekusi:
            <pre>su -c chmod +x /data/adb/service.d/autoswitchapn.sh</pre>
        </li>
        <li>Restart perangkat atau jalankan script secara manual:
            <pre>su -c /data/adb/service.d/autoswitchapn.sh</pre>
        </li>
    </ol>

    <p>Script akan berjalan secara otomatis saat perangkat dinyalakan dan akan terus berjalan di latar belakang.</p>

    <h3>Menghentikan Script</h3>

    <p>Jika Anda ingin menghentikan script, gunakan perintah:</p>
    <pre>su -c pkill -f autoswitchapn.sh</pre>

    <h2>Pemecahan Masalah</h2>

    <p>Jika script tidak berfungsi dengan baik:</p>

    <ol>
        <li>Pastikan ID APN yang dikonfigurasi sudah benar
            <ul>
                <li>Verifikasi ID dengan menjalankan: <code>su -c content query --uri content://telephony/carriers</code></li>
            </ul>
        </li>
        <li>Periksa apakah host yang ditentukan dapat di-ping
            <ul>
                <li>Coba ping manual: <code>ping -c 1 quiz.vidio.com</code></li>
            </ul>
        </li>
        <li>Periksa log untuk melihat apakah terjadi kesalahan saat menjalankan perintah
            <ul>
                <li>Jalankan script dengan output ke file log: <code>su -c /data/adb/service.d/autoswitchapn.sh > /sdcard/apn_log.txt 2>&1</code></li>
            </ul>
        </li>
        <li>Periksa izin script
            <ul>
                <li>Pastikan script memiliki izin eksekusi: <code>su -c ls -la /data/adb/service.d/autoswitchapn.sh</code></li>
            </ul>
        </li>
        <li>Jika script berhenti berjalan setelah beberapa waktu:
            <ul>
                <li>Kemungkinan script dihentikan oleh sistem penghemat baterai</li>
                <li>Tambahkan aplikasi terminal Anda ke daftar pengecualian penghemat baterai</li>
            </ul>
        </li>
    </ol>

    <h2>Pertanyaan Umum (FAQ)</h2>

    <h3>Apakah script ini menghabiskan banyak baterai?</h3>
    <p>Tidak, script hanya melakukan ping ringan dan operasi database APN yang minimal. Dampak terhadap baterai sangat kecil.</p>

    <h3>Apakah script ini bekerja pada semua perangkat Android?</h3>
    <p>Script ini bekerja pada sebagian besar perangkat Android yang sudah di-root, tetapi beberapa vendor mungkin memiliki implementasi APN yang berbeda.</p>

    <h3>Bagaimana cara mengetahui apakah script berjalan dengan baik?</h3>
    <p>Anda dapat melihat perubahan APN di pengaturan jaringan atau menjalankan script dengan output ke file log untuk memantau aktivitasnya.</p>

    <h3>Apakah saya bisa menggunakan lebih dari dua APN?</h3>
    <p>Script standar mendukung dua APN, tetapi Anda dapat memodifikasi script untuk mendukung lebih banyak APN jika diperlukan.</p>

    <h2>Catatan Penting</h2>

    <ul>
        <li>Script ini memerlukan akses root</li>
        <li>Mengganti APN secara otomatis dapat memengaruhi penggunaan data seluler</li>
        <li>Pastikan untuk menyesuaikan parameter sesuai dengan kebutuhan jaringan Anda</li>
        <li>Beberapa operator mungkin memiliki kebijakan yang membatasi perpindahan APN yang sering</li>
        <li>Perhatikan penggunaan data Anda, karena beralih antar APN dapat menyebabkan penagihan data dari operator yang berbeda</li>
    </ul>

    <h2>Kontribusi</h2>

    <p>Kontribusi untuk meningkatkan script ini sangat diterima. Silakan lakukan fork repositori, buat perubahan, dan kirimkan pull request.</p>

    <h2>Lisensi</h2>

    <p>Script ini didistribusikan di bawah lisensi open source dan bebas digunakan untuk keperluan pribadi maupun komersial.</p>
    </div>
</body>
</html> 