file pendukung silahkan download di githus=b saya
https://github.com/latifangren/webui_bfm_Extended/tree/main/data/adb/service.d
ambil file seperlunya saja

perhatikan !!!
file file di folder data/adb/service.d agar tidak bentrok dengan script lain di luar file rekomendasi ini

file wajib agar fitur ini berjalan
1. autoswitchapn.sh (wajib ada)
2. pingmonitor.sh (wajib ada)
taruh di folder data/adb/service.d
atur permission menjadi 755 (penting)


# Script Auto Switch APN

Script ini dirancang untuk secara otomatis beralih antara dua APN (Access Point Name) ketika koneksi internet terdeteksi bermasalah. Script akan memantau koneksi ke host tertentu dan beralih ke APN cadangan jika koneksi terputus.

## Cara Kerja

Script ini berfungsi dengan cara:
1. Melakukan ping secara berkala ke host tertentu (default: quiz.vidio.com)
2. Jika ping gagal mencapai batas yang ditentukan (default: 10 kali), script akan beralih ke APN pertama
3. Setelah jeda waktu tertentu, script akan beralih kembali ke APN kedua
4. Proses ini akan terus berulang untuk memastikan koneksi internet tetap berjalan

## Keuntungan Menggunakan Script Ini

- Menjaga koneksi internet tetap stabil saat APN utama mengalami masalah
- Beralih secara otomatis tanpa perlu intervensi manual
- Dapat disesuaikan dengan kebutuhan dan preferensi pengguna
- Sangat berguna untuk area dengan koneksi internet yang tidak stabil
- Ideal untuk aplikasi yang membutuhkan koneksi internet terus-menerus

## Persyaratan

- Perangkat Android yang sudah di-root (diuji pada Android 9.0+)
- Terminal emulator atau akses shell
- Izin superuser (su)
- Magisk atau sistem pengelolaan root serupa
- Setidaknya dua APN yang sudah dikonfigurasi di perangkat

## Konfigurasi Script

Sebelum menggunakan script, Anda perlu mengkonfigurasi ID APN yang sesuai dengan perangkat Anda:

### Menemukan ID APN

1. Buka terminal dan jalankan perintah berikut (ganti "nama_apn" dengan nama APN Anda):
   ```
   su -c content query --uri content://telephony/carriers | grep nama_apn
   ```
2. Catat ID APN yang muncul (biasanya berupa angka seperti 2910 atau 3439)
3. Contoh output:
   ```
   row_id=2910, name=Vidio, numeric=51001, mcc=510, mnc=01
   ```
   Pada contoh di atas, ID APN untuk "Vidio" adalah 2910.

### Mengubah Konfigurasi

Edit file `autoswitchapn.sh` dan ubah parameter berikut sesuai kebutuhan:

- `HOST`: Alamat yang akan di-ping untuk memeriksa koneksi
  - Default: quiz.vidio.com
  - Disarankan: pilih host yang selalu online dan responsif
  
- `PING_FAIL_LIMIT`: Jumlah kegagalan ping sebelum beralih APN
  - Default: 10
  - Nilai lebih rendah: lebih responsif tetapi dapat menyebabkan perpindahan yang terlalu sering
  - Nilai lebih tinggi: kurang responsif tetapi lebih stabil
  
- `WAIT_TIME`: Waktu tunggu (detik) sebelum beralih kembali
  - Default: 5
  - Sesuaikan berdasarkan kecepatan respons jaringan Anda
  
- `ID_APN`: ID untuk APN pertama
  - Ganti dengan ID APN utama Anda
  
- `ID_APN2`: ID untuk APN kedua
  - Ganti dengan ID APN cadangan Anda

## Cara Menggunakan

### Instalasi

1. Salin file `autoswitchapn.sh` ke direktori `/data/adb/service.d/` di perangkat Android Anda:
   ```
   su -c cp /path/to/autoswitchapn.sh /data/adb/service.d/
   ```

2. Berikan izin eksekusi:
   ```
   su -c chmod +x /data/adb/service.d/autoswitchapn.sh
   ```

3. Restart perangkat atau jalankan script secara manual:
   ```
   su -c /data/adb/service.d/autoswitchapn.sh
   ```

Script akan berjalan secara otomatis saat perangkat dinyalakan dan akan terus berjalan di latar belakang.

### Menghentikan Script

Jika Anda ingin menghentikan script, gunakan perintah:
```
su -c pkill -f autoswitchapn.sh
```

## Pemecahan Masalah

Jika script tidak berfungsi dengan baik:

1. Pastikan ID APN yang dikonfigurasi sudah benar
   - Verifikasi ID dengan menjalankan: `su -c content query --uri content://telephony/carriers`
   
2. Periksa apakah host yang ditentukan dapat di-ping
   - Coba ping manual: `ping -c 1 quiz.vidio.com`
   
3. Periksa log untuk melihat apakah terjadi kesalahan saat menjalankan perintah
   - Jalankan script dengan output ke file log: `su -c /data/adb/service.d/autoswitchapn.sh > /sdcard/apn_log.txt 2>&1`
   
4. Periksa izin script
   - Pastikan script memiliki izin eksekusi: `su -c ls -la /data/adb/service.d/autoswitchapn.sh`
   
5. Jika script berhenti berjalan setelah beberapa waktu:
   - Kemungkinan script dihentikan oleh sistem penghemat baterai
   - Tambahkan aplikasi terminal Anda ke daftar pengecualian penghemat baterai

## Pertanyaan Umum (FAQ)

### Apakah script ini menghabiskan banyak baterai?
Tidak, script hanya melakukan ping ringan dan operasi database APN yang minimal. Dampak terhadap baterai sangat kecil.

### Apakah script ini bekerja pada semua perangkat Android?
Script ini bekerja pada sebagian besar perangkat Android yang sudah di-root, tetapi beberapa vendor mungkin memiliki implementasi APN yang berbeda.

### Bagaimana cara mengetahui apakah script berjalan dengan baik?
Anda dapat melihat perubahan APN di pengaturan jaringan atau menjalankan script dengan output ke file log untuk memantau aktivitasnya.

### Apakah saya bisa menggunakan lebih dari dua APN?
Script standar mendukung dua APN, tetapi Anda dapat memodifikasi script untuk mendukung lebih banyak APN jika diperlukan.

## Catatan Penting

- Script ini memerlukan akses root
- Mengganti APN secara otomatis dapat memengaruhi penggunaan data seluler
- Pastikan untuk menyesuaikan parameter sesuai dengan kebutuhan jaringan Anda 
- Beberapa operator mungkin memiliki kebijakan yang membatasi perpindahan APN yang sering
- Perhatikan penggunaan data Anda, karena beralih antar APN dapat menyebabkan penagihan data dari operator yang berbeda

## Kontribusi

Kontribusi untuk meningkatkan script ini sangat diterima. Silakan lakukan fork repositori, buat perubahan, dan kirimkan pull request.

## Lisensi

Script ini didistribusikan di bawah lisensi open source dan bebas digunakan untuk keperluan pribadi maupun komersial. 