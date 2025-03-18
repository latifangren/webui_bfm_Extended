# BOX UI Extended

## 🌟 Ringkasan
BOX UI adalah antarmuka berbasis web untuk mengelola perangkat Android tanpa perlu menyentuh perangkat secara langsung. Aplikasi ini mendukung modul Magisk dan KernelSU dengan berbagai fitur komprehensif untuk mengontrol dan memantau perangkat Android.

## 🎯 Tujuan
- Menyediakan antarmuka web yang mudah digunakan untuk mengelola perangkat Android
- Mengintegrasikan berbagai tools manajemen sistem dalam satu dashboard
- Memungkinkan akses remote ke fungsi-fungsi penting perangkat Android
- Menyederhanakan pengelolaan konfigurasi jaringan dan sistem

## 👥 Target Pengguna
- Pengguna Android yang menggunakan Magisk atau KernelSU
- Administrator sistem Android
- Pengguna yang membutuhkan akses remote ke perangkat Android
- Pengguna yang memerlukan manajemen jaringan dan sistem yang komprehensif

## 💻 Persyaratan Sistem
### Minimum Requirements:
- Perangkat Android dengan Magisk atau KernelSU terinstal
- Termux
- Akses root
- Koneksi internet untuk fitur-fitur tertentu

## 🔐 Informasi Login
- **Username**: admin
- **Password**: 12345

## 🚀 Fitur Utama

### Manajemen Sistem
- **Dashboard Clash**: Antarmuka komprehensif untuk manajemen konfigurasi Clash
- **System Info**: Informasi detail tentang perangkat Anda
- **Tiny FM**: File manager untuk memodifikasi file dan konfigurasi
- **TTyd**: Terminal manager (membutuhkan Termux dan Termux:Boot)
- **BOX Settings**: Pengaturan BFR untuk menjalankan core Clash, SingBox, XRay, V2Fly
- **SMS Inbox**: Baca SMS Android melalui web UI
- **Config Generator**: Generate konfigurasi Clash dan import Vmess, Vless, Trojan, Shadowsocks
- **BOX Logs**: Lihat log aktivitas BOX
- **Reboot Options**: Reboot perangkat atau reboot ke TWRP

### Fitur Jaringan
- **Hotspot Manager**: Kelola koneksi hotspot
- **Network Tools**: Tools untuk analisis jaringan
- **CPU Monitor**: Pantau kinerja CPU
- **Speed Test**: Uji kecepatan (lokal dan online)
- **Clash Integration**: Integrasi penuh dengan Dashboard Clash

### 📡 Ping Monitor
- **Status Real-time**: Pantau status koneksi secara real-time
- **Grafik Interaktif**: Visualisasi status ping dengan grafik dinamis
- **Mode Pesawat**: Monitor status mode pesawat
- **Log Aktivitas**: Pantau log aktivitas ping secara detail
- **Statistik**: 
  - Total ping
  - Ping sukses
  - Ping gagal
  - Riwayat status koneksi

### 📱 APN Monitor
- **Manajemen APN**: Kontrol dan monitor status APN
- **Auto Switch**: Fitur pergantian APN otomatis
- **Status Real-time**: Pantau status APN aktif
- **Log Monitor**: Pantau aktivitas perubahan APN
- **Statistik APN**:
  - Jumlah pergantian APN
  - Status koneksi
  - Riwayat perubahan

## 📱 Cara Mengakses
Setelah instalasi, akses BOX UI melalui:
- [http://127.0.0.1:80](http://127.0.0.1:80)
- [http://127.0.0.1](http://127.0.0.1)

### Akses Fitur Monitor
- Ping Monitor: `http://127.0.0.1/tools/pingmonitor.php`
- Tampilan Modern dengan Dark Mode
- Refresh otomatis setiap 5 detik
- Grafik status ping real-time
- Panel kontrol untuk start/stop monitoring

## 📥 Panduan Instalasi
1. Download repositori ini sebagai file zip
2. Pastikan file download bukan hanya folder, jika ya ekstrak terlebih dahulu
3. Pilih semua file dalam folder webui_bfm
4. Zip ulang dan flash modul
5. Pastikan saat mengunduh modul tidak hanya berupa folder

### Persyaratan Tambahan
- Termux
- Magisk atau KernelSU

## 🔄 Sistem Update
- Update otomatis melalui Magisk Manager
- Changelog terstruktur untuk setiap versi
- Backup konfigurasi sebelum update

#### 🛠️ Perbaikan & Peningkatan
- Penyederhanaan tombol dark mode untuk tampilan yang lebih bersih
- Mengoptimalkan tampilan di perangkat mobile dengan mengatasi masalah tumpang tindih teks "BOX UI"
- Penggunaan tema hitam sebagai default untuk mengurangi beban baterai pada perangkat AMOLED
- Perbaikan tampilan pada mode gelap di berbagai halaman
- Menghapus menu "Akun" yang tidak digunakan dari opsi box

## 👨‍💻 Tim Pengembang

### Modder Extended Version
- **Developer:** [Latifan_id](https://github.com/latifangren)

### Developer & Kontributor
- **WEB UI BFM:** [geeks121/webui_bfm](https://github.com/geeks121/webui_bfm)
- **ARGON UI:** taamarin, Gondes & Zay's
- **PHP7 Server:** [nosignals/magisk-php7-webserver](https://github.com/nosignals/magisk-php7-webserver)
- **BOX Magisk:** [taamarin/box_for_magisk](https://github.com/taamarin/box_for_magisk)
- **Generator:** [mitralola716/ocgen](https://github.com/mitralola716/ocgen)

## 📞 Dukungan dan Bantuan
- Dokumentasi lengkap tersedia di interface
- Sistem pelaporan bug melalui GitHub Issues
- Update berkala untuk peningkatan dan perbaikan
- Dukungan komunitas aktif

## 📄 Lisensi
BOX UI dilisensikan di bawah MIT LICENSE terbaru.

## 💝 Ucapan Terima Kasih
Terima kasih kepada seluruh pengguna dan kontributor yang telah membantu mengembangkan BOX UI hingga saat ini!
