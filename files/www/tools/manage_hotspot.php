<?php
session_start();
// Handle command line argument untuk reapply
if (php_sapi_name() === 'cli' && isset($argv[1]) && $argv[1] === 'reapply') {
    writeLog("Menjalankan reapply rules dari CLI");
    reapplyRules();
    exit(0);
}
// Pastikan file-file yang diperlukan ada
if (!file_exists('limited_users.txt')) {
    file_put_contents('limited_users.txt', '');
}
if (!file_exists('blocked_users.txt')) {
    file_put_contents('blocked_users.txt', '');
}
if (!file_exists('limiter.log')) {
    file_put_contents('limiter.log', '');
}

// Tambahkan di awal file setelah session_start()
date_default_timezone_set('Asia/Jakarta');

// Tambahkan di awal file setelah session_start()
function isRulesApplied() {
    return file_exists(__DIR__ . '/hotspot_rules_applied');
}

function markRulesAsApplied() {
    $timestamp = date('d-m-Y H:i:s');
    file_put_contents(__DIR__ . '/hotspot_rules_applied', $timestamp);
}

// Fungsi untuk mendapatkan daftar perangkat yang terkoneksi
function getConnectedDevices() {
    $devices = array();
    
    // Menggunakan perintah ip neigh untuk mendapatkan informasi perangkat terkoneksi
    $output = shell_exec('ip -4 neigh');
    
    if ($output) {
        $lines = explode("\n", $output);
        
        foreach ($lines as $line) {
            if (!empty($line)) {
                $parts = preg_split('/\s+/', $line);
                // Memastikan hanya alamat IPv4 yang valid yang ditambahkan
                if (count($parts) >= 5 && filter_var($parts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $devices[] = array(
                        'ip' => $parts[0],
                        'mac' => $parts[4],
                        'signal' => '-', // Tidak ada informasi sinyal dari ip neigh
                        'connected_time' => '-', // Tidak ada informasi waktu dari ip neigh
                    );
                }
            }
        }
    }
    
    return $devices;
}

// Fungsi untuk mengecek status blokir
function isBlocked($ip) {
    if (file_exists('blocked_users.txt')) {
        $blocked_ips = file('blocked_users.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return in_array($ip, $blocked_ips);
    }
    return false;
}

// Fungsi untuk mengecek status limit
function isLimited($ip) {
    if (file_exists('limited_users.txt')) {
        $limited_ips = file('limited_users.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($limited_ips as $line) {
            $parts = explode(':', $line);
            if ($parts[0] === $ip) {
                return true;
            }
        }
    }
    return false;
}

// Modifikasi fungsi getLimitValue untuk mengembalikan nilai dalam Mbps
function getLimitValue($ip) {
    if (file_exists('limited_users.txt')) {
        $limited_ips = file('limited_users.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($limited_ips as $line) {
            $parts = explode(':', $line);
            if ($parts[0] === $ip) {
                // Nilai sudah dalam Mbps di file
                return $parts[1];
            }
        }
    }
    return null;
}

// Fungsi untuk memblokir perangkat
function blockDevice($ip) {
    // Simpan IP yang diblokir ke file
    file_put_contents('blocked_users.txt', "$ip\n", FILE_APPEND);
    
    // Blokir menggunakan iptables
    shell_exec("iptables -A INPUT -s " . $ip . " -j DROP");
    shell_exec("iptables -A FORWARD -s " . $ip . " -j DROP");
    shell_exec("iptables -A OUTPUT -d " . $ip . " -j DROP");
    
    // Putuskan koneksi WiFi
    shell_exec("nmcli dev wifi disconnect " . $ip);
    
    return true;
}

// Fungsi untuk membatalkan blokir perangkat
function unblockDevice($ip) {
    // Hapus IP dari file blocked_users.txt
    $blocked_ips = file('blocked_users.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $blocked_ips = array_diff($blocked_ips, array($ip));
    file_put_contents('blocked_users.txt', implode("\n", $blocked_ips) . "\n");
    
    // Hapus aturan iptables
    shell_exec("iptables -D INPUT -s " . $ip . " -j DROP");
    shell_exec("iptables -D FORWARD -s " . $ip . " -j DROP");
    shell_exec("iptables -D OUTPUT -d " . $ip . " -j DROP");
    
    return true;
}

// Modifikasi fungsi reapplyRules untuk menggunakan interface yang dipilih
function reapplyRules() {
    $wlan_interface = getSelectedInterface(); // Mengambil interface yang dipilih
    writeLog("Memulai proses menerapkan kembali aturan yang tersimpan pada interface: $wlan_interface");
    
    // Cek apakah aturan sudah diterapkan
    if (isRulesApplied()) {
        writeLog("Aturan sudah diterapkan sebelumnya, melewati proses penerapan ulang");
        return true;
    }

    writeLog("Memulai proses menerapkan kembali aturan yang tersimpan");
    
    // Deteksi command tc
    $tc_cmd = trim(shell_exec("which tc"));
    if (empty($tc_cmd)) {
        $tc_cmd = trim(shell_exec("which busybox tc"));
        if (empty($tc_cmd)) {
            writeLog("Error: tc command tidak ditemukan");
            return false;
        }
    }
    
    // Gunakan interface yang dipilih
    writeLog("Menggunakan interface: $wlan_interface");
    
    // Reset qdisc yang ada
    shell_exec("$tc_cmd qdisc del dev $wlan_interface root 2>/dev/null");
    
    // Buat qdisc root baru
    shell_exec("$tc_cmd qdisc add dev $wlan_interface root handle 1: htb default 1");
    
    // Buat class untuk default traffic
    shell_exec("$tc_cmd class add dev $wlan_interface parent 1: classid 1:1 htb rate 1000mbit");
    
    // Terapkan kembali aturan limit
    if (file_exists('limited_users.txt')) {
        $limited_ips = file('limited_users.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($limited_ips as $line) {
            $parts = explode(':', $line);
            if (count($parts) == 2) {
                $ip = $parts[0];
                $download = intval($parts[1]);
                $download_kbps = $download * 1024; // Konversi Mbps ke kbps
                
                // Generate classid dari last octet IP
                $ip_parts = explode('.', $ip);
                $classid = end($ip_parts);
                
                writeLog("Menerapkan kembali limit untuk IP: $ip dengan bandwidth: $download Mbps");
                
                // Buat class untuk IP dengan nilai dalam kbps
                $class_cmd = "$tc_cmd class add dev $wlan_interface parent 1: classid 1:$classid htb rate {$download_kbps}kbit ceil {$download_kbps}kbit";
                shell_exec($class_cmd);
                
                // Tambahkan filter
                $filter_cmd = "$tc_cmd filter add dev $wlan_interface protocol ip parent 1: prio 1 u32 match ip dst $ip flowid 1:$classid";
                shell_exec($filter_cmd);
                
                // Tambahkan filter upload
                $filter_upload_cmd = "$tc_cmd filter add dev $wlan_interface protocol ip parent 1: prio 1 u32 match ip src $ip flowid 1:$classid";
                shell_exec($filter_upload_cmd);
            }
        }
    }
    
    // Terapkan kembali aturan blokir
    if (file_exists('blocked_users.txt')) {
        $blocked_ips = file('blocked_users.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($blocked_ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                writeLog("Menerapkan kembali blokir untuk IP: $ip");
                shell_exec("iptables -A INPUT -s $ip -j DROP");
                shell_exec("iptables -A FORWARD -s $ip -j DROP");
                shell_exec("iptables -A OUTPUT -d $ip -j DROP");
            }
        }
    }
    
    // Tandai bahwa aturan sudah diterapkan
    markRulesAsApplied();
    writeLog("Selesai menerapkan kembali aturan yang tersimpan");
    return true;
}

// Panggil reapplyRules hanya jika belum diterapkan
if (!isRulesApplied()) {
    reapplyRules();
}

// Tambahkan fungsi logging
function writeLog($message) {
    $timestamp = date('d-m-Y H:i:s');
    file_put_contents('limiter.log', "$timestamp - $message\n", FILE_APPEND);
}

// Modifikasi fungsi limitBandwidth untuk menggunakan interface yang dipilih
function limitBandwidth($ip, $download) {
    $wlan_interface = getSelectedInterface(); // Mengambil interface yang dipilih
    writeLog("Memulai proses limit bandwidth untuk IP: $ip dengan $download Mbps pada interface: $wlan_interface");
    
    // Validasi input
    if (!filter_var($ip, FILTER_VALIDATE_IP) || !is_numeric($download) || $download <= 0) {
        writeLog("Error: Invalid input parameters");
        return false;
    }
    
    // Konversi Mbps ke kbps untuk tc
    $download_kbps = intval($download * 1024);
    
    // Deteksi command tc
    $tc_cmd = trim(shell_exec("which tc"));
    if (empty($tc_cmd)) {
        $tc_cmd = trim(shell_exec("which busybox tc"));
        if (empty($tc_cmd)) {
            writeLog("Error: tc command tidak ditemukan");
            return false;
        }
    }
    writeLog("Menggunakan tc command: $tc_cmd");
    
    // Deteksi interface
    $interfaces = shell_exec("ip link show");
    writeLog("Available interfaces: " . trim($interfaces));
    writeLog("Menggunakan interface yang dipilih: $wlan_interface");
    
    // Generate classid yang lebih pendek (menggunakan last octet dari IP)
    $ip_parts = explode('.', $ip);
    $classid = end($ip_parts);
    writeLog("Menggunakan classid: $classid");
    
    // Reset qdisc untuk interface
    $reset_cmd = "$tc_cmd qdisc del dev $wlan_interface root 2>/dev/null";
    writeLog("Menjalankan reset command: $reset_cmd");
    shell_exec($reset_cmd);
    
    // Buat qdisc root baru
    $root_cmd = "$tc_cmd qdisc add dev $wlan_interface root handle 1: htb default 1";
    writeLog("Membuat qdisc root: $root_cmd");
    $result = shell_exec("$root_cmd 2>&1");
    if (!empty($result)) {
        writeLog("Error saat membuat qdisc root: $result");
        return false;
    }
    
    // Buat class untuk default traffic
    $class_cmd = "$tc_cmd class add dev $wlan_interface parent 1: classid 1:1 htb rate 1000mbit";
    writeLog("Membuat default class: $class_cmd");
    shell_exec($class_cmd);
    
    // Buat class untuk IP dengan nilai dalam kbps
    $class_cmd = "$tc_cmd class add dev $wlan_interface parent 1: classid 1:$classid htb rate {$download_kbps}kbit ceil {$download_kbps}kbit";
    writeLog("Membuat limit class: $class_cmd");
    $result = shell_exec("$class_cmd 2>&1");
    if (strpos($result, 'Error') !== false) {
        writeLog("Error saat membuat limit class: $result");
        return false;
    }
    
    // Tambahkan filter untuk mengarahkan traffic ke class yang dibatasi
    $filter_cmd = "$tc_cmd filter add dev $wlan_interface protocol ip parent 1: prio 1 u32 " .
                 "match ip dst $ip flowid 1:$classid";
    writeLog("Menambahkan filter: $filter_cmd");
    $result = shell_exec("$filter_cmd 2>&1");
    if (strpos($result, 'Error') !== false) {
        writeLog("Error saat menambahkan filter: $result");
        return false;
    }
    
    // Tambahkan filter untuk upload (source IP)
    $filter_upload_cmd = "$tc_cmd filter add dev $wlan_interface protocol ip parent 1: prio 1 u32 " .
                        "match ip src $ip flowid 1:$classid";
    writeLog("Menambahkan filter upload: $filter_upload_cmd");
    $result = shell_exec("$filter_upload_cmd 2>&1");
    if (strpos($result, 'Error') !== false) {
        writeLog("Error saat menambahkan filter upload: $result");
        // Tidak return false karena ini opsional
    }
    
    // Simpan nilai dalam Mbps ke file
    try {
        $limited_ips = file_exists('limited_users.txt') ? 
            file('limited_users.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : 
            array();
        
        $limited_ips = array_filter($limited_ips, function($line) use ($ip) {
            return strpos($line, $ip . ':') !== 0;
        });
        
        // Simpan nilai asli dalam Mbps
        $limited_ips[] = "$ip:$download";
        file_put_contents('limited_users.txt', implode("\n", $limited_ips) . "\n");
        writeLog("Berhasil menyimpan ke limited_users.txt");
    } catch (Exception $e) {
        writeLog("Error saat menyimpan ke file: " . $e->getMessage());
    }
    
    writeLog("Proses limit bandwidth selesai untuk IP: $ip");
    return true;
}

// Fungsi untuk membatalkan limit bandwidth
function unlimitBandwidth($ip) {
    writeLog("Memulai proses membuka limit untuk IP: $ip");
    
    // Deteksi command tc
    $tc_cmd = trim(shell_exec("which tc"));
    if (empty($tc_cmd)) {
        $tc_cmd = trim(shell_exec("which busybox tc"));
        if (empty($tc_cmd)) {
            writeLog("Error: tc command tidak ditemukan");
            return false;
        }
    }
    
    // Gunakan wlan1 karena ini yang aktif
    $wlan_interface = "wlan1";
    writeLog("Menggunakan interface: $wlan_interface");
    
    // Generate classid dari last octet IP
    $ip_parts = explode('.', $ip);
    $classid = end($ip_parts);
    writeLog("Menggunakan classid: $classid");
    
    // Hapus filter untuk IP tertentu
    $filter_cmd = "$tc_cmd filter del dev $wlan_interface protocol ip parent 1: prio 1 u32 match ip dst $ip 2>/dev/null";
    writeLog("Menghapus filter download: $filter_cmd");
    shell_exec($filter_cmd);
    
    // Hapus filter upload
    $filter_upload_cmd = "$tc_cmd filter del dev $wlan_interface protocol ip parent 1: prio 1 u32 match ip src $ip 2>/dev/null";
    writeLog("Menghapus filter upload: $filter_upload_cmd");
    shell_exec($filter_upload_cmd);
    
    // Hapus class untuk IP tertentu
    $class_cmd = "$tc_cmd class del dev $wlan_interface classid 1:$classid 2>/dev/null";
    writeLog("Menghapus class: $class_cmd");
    shell_exec($class_cmd);
    
    // Hapus IP dari file limited_users.txt
    try {
        if (file_exists('limited_users.txt')) {
            $limited_ips = file('limited_users.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $limited_ips = array_filter($limited_ips, function($line) use ($ip) {
                return strpos($line, $ip . ':') !== 0;
            });
            file_put_contents('limited_users.txt', implode("\n", $limited_ips) . "\n");
            writeLog("Berhasil menghapus IP dari limited_users.txt");
        }
    } catch (Exception $e) {
        writeLog("Error saat menghapus dari file: " . $e->getMessage());
        return false;
    }
    
    writeLog("Proses membuka limit selesai untuk IP: $ip");
    return true;
}

// Perbaikan fungsi clearLog yang lebih menyeluruh
function clearLog() {
    // Tulis ke error_log server untuk debugging
    error_log("Mencoba membersihkan log limiter.log");
    
    // Mencoba hapus dengan cara langsung
    try {
        // Pastikan path file absolut
        $log_file = __DIR__ . '/limiter.log';
        
        // Cek file ada dan dapat ditulis
        if (!file_exists($log_file)) {
            error_log("File log tidak ada, membuat file baru");
            $timestamp = date('d-m-Y H:i:s');
            file_put_contents($log_file, "$timestamp - File log baru dibuat\n");
            return true;
        }
        
        if (!is_writable($log_file)) {
            error_log("File log tidak dapat ditulis: $log_file");
            chmod($log_file, 0666); // Coba ubah permission
            if (!is_writable($log_file)) {
                error_log("Gagal mengubah permission file log");
                return false;
            }
        }
        
        // Hapus konten file
        if (file_put_contents($log_file, '') === false) {
            error_log("Gagal menulis ke file log");
            return false;
        }
        
        // Tulis pesan inisialisasi
        $timestamp = date('d-m-Y H:i:s');
        file_put_contents($log_file, "$timestamp - Log telah dibersihkan\n", FILE_APPEND);
        error_log("Berhasil membersihkan log");
        return true;
    } catch (Exception $e) {
        error_log("Exception saat menghapus log: " . $e->getMessage());
        return false;
    }
}

// Tangani aksi hapus log dengan prioritas tinggi di awal file
if (isset($_POST['clear_log']) || isset($_GET['clear_log'])) {
    // Kirim header untuk mencegah caching
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    
    $log_cleared = clearLog();
    if ($log_cleared) {
        $message = "Log berhasil dibersihkan " . date('H:i:s');
        
        // Redirect untuk menghindari refresh form
        if (!isset($_GET['ajax'])) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?log_cleared=1&t=" . time());
            exit();
        }
    } else {
        $message = "Gagal membersihkan log. Periksa permission file. " . date('H:i:s');
    }
}

if (isset($_GET['log_cleared'])) {
    $message = "Log berhasil dibersihkan " . date('H:i:s');
}

$devices = getConnectedDevices();
$search = isset($_GET['search']) ? $_GET['search'] : '';
$message = '';

// Filter devices berdasarkan pencarian
if (!empty($search)) {
    $devices = array_filter($devices, function($device) use ($search) {
        return stripos($device['ip'], $search) !== false || 
               stripos($device['mac'], $search) !== false;
    });
}

// Handle aksi blokir
if (isset($_POST['block']) && isset($_POST['ip'])) {
    $ip = $_POST['ip'];
    if (blockDevice($ip)) {
        $message = "Perangkat dengan IP $ip berhasil diblokir dan diputuskan dari hotspot";
    } else {
        $message = "Gagal memblokir perangkat";
    }
}

// Handle aksi unblock
if (isset($_POST['unblock']) && isset($_POST['ip'])) {
    $ip = $_POST['ip'];
    if (unblockDevice($ip)) {
        $message = "Perangkat dengan IP $ip berhasil dibuka blokirnya";
    } else {
        $message = "Gagal membuka blokir perangkat";
    }
}

// Handle aksi limiter
if (isset($_POST['limit']) && isset($_POST['ip'])) {
    $ip = $_POST['ip'];
    $download = isset($_POST['download']) ? $_POST['download'] : 1024;
    
    writeLog("Menerima request limit untuk IP: $ip dengan bandwidth: $download Mbps");
    
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $message = "Error: IP Address tidak valid";
        writeLog($message);
    } elseif (!is_numeric($download) || $download <= 0) {
        $message = "Error: Nilai bandwidth harus berupa angka positif";
        writeLog($message);
    } else {
        if (limitBandwidth($ip, $download)) {
            $message = "Bandwidth untuk IP $ip berhasil dibatasi (Download: {$download}Mbps)";
            writeLog("Sukses: " . $message);
        } else {
            $message = "Error: Gagal menerapkan limit bandwidth. Cek limiter.log untuk detail";
            writeLog("Gagal menerapkan limit bandwidth");
        }
    }
}

// Handle aksi unlimit
if (isset($_POST['unlimit']) && isset($_POST['ip'])) {
    $ip = $_POST['ip'];
    writeLog("Menerima request untuk membuka limit IP: $ip");
    
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $message = "Error: IP Address tidak valid";
        writeLog($message);
    } else {
        if (unlimitBandwidth($ip)) {
            $message = "Bandwidth untuk IP $ip berhasil dibuka limitnya";
            writeLog("Sukses: " . $message);
        } else {
            $message = "Error: Gagal membuka limit bandwidth. Cek limiter.log untuk detail";
            writeLog("Gagal membuka limit bandwidth");
        }
    }
}

// Tambahkan fungsi untuk mendapatkan interface yang aktif
function getActiveInterfaces() {
    $interfaces = [];
    $output = shell_exec("ip link show");
    if (strpos($output, 'wlan0') !== false) {
        $interfaces[] = 'wlan0';
    }
    if (strpos($output, 'wlan1') !== false) {
        $interfaces[] = 'wlan1';
    }
    return $interfaces;
}

// Tambahkan fungsi untuk menyimpan interface yang dipilih
function saveSelectedInterface($interface) {
    file_put_contents(__DIR__ . '/selected_interface', $interface);
}

// Fungsi untuk mendapatkan interface yang dipilih
function getSelectedInterface() {
    if (file_exists(__DIR__ . '/selected_interface')) {
        return trim(file_get_contents(__DIR__ . '/selected_interface'));
    }
    // Default ke wlan1 jika belum ada yang dipilih
    return 'wlan1';
}

// Tambahkan di bagian atas file untuk handle POST interface
if (isset($_POST['select_interface'])) {
    $selected_interface = $_POST['interface'];
    saveSelectedInterface($selected_interface);
    // Reset rules applied status agar aturan diterapkan ulang
    if (file_exists(__DIR__ . '/hotspot_rules_applied')) {
        unlink(__DIR__ . '/hotspot_rules_applied');
    }
    $message = "Interface $selected_interface berhasil dipilih";
}

// Update fungsi limitBandwidth dan reapplyRules untuk menggunakan interface yang dipilih
function getWlanInterface() {
    return getSelectedInterface();
}

// Fungsi untuk mendapatkan status interface wlan (aktif atau tidak)
function getInterfaceStatus($interface) {
    $output = shell_exec("ip link show $interface 2>/dev/null");
    if (empty($output)) {
        return false; // Interface tidak tersedia
    }
    
    // Cek apakah interface dalam status UP
    return (strpos($output, 'state UP') !== false);
}

// Fungsi untuk mendapatkan informasi detail interface
function getInterfaceInfo($interface) {
    $info = [
        'name' => $interface,
        'status' => getInterfaceStatus($interface) ? 'aktif' : 'nonaktif',
        'ip' => '',
        'mac' => '',
        'clients' => 0
    ];
    
    // Dapatkan alamat IP
    $ip_output = shell_exec("ip addr show $interface 2>/dev/null | grep 'inet ' | awk '{print \$2}' | cut -d/ -f1");
    if (!empty($ip_output)) {
        $info['ip'] = trim($ip_output);
    }
    
    // Dapatkan alamat MAC
    $mac_output = shell_exec("ip link show $interface 2>/dev/null | grep 'link/ether' | awk '{print \$2}'");
    if (!empty($mac_output)) {
        $info['mac'] = trim($mac_output);
    }
    
    // Hitung jumlah klien yang terhubung ke interface ini
    $client_output = shell_exec("ip neigh show dev $interface 2>/dev/null | grep -v FAILED | wc -l");
    if (!empty($client_output)) {
        $info['clients'] = (int)trim($client_output);
    }
    
    return $info;
}

// Tambahkan fungsi penghapusan log yang sangat radikal dan langsung
function forceDeleteLog() {
    // Definisikan path file log dengan absolut
    $log_file = __DIR__ . '/limiter.log';
    $relative_log_file = 'limiter.log';
    
    $success = false;
    $debug_info = [];
    
    // Debug info
    $debug_info[] = "Timestamp: " . date('Y-m-d H:i:s');
    $debug_info[] = "PHP version: " . phpversion();
    $debug_info[] = "OS: " . PHP_OS;
    $debug_info[] = "Current dir: " . __DIR__;
    $debug_info[] = "Absolute path: $log_file";
    $debug_info[] = "Relative path: $relative_log_file";
    
    // Metode 1: Mencoba file_put_contents
    try {
        if (file_exists($log_file)) {
            $debug_info[] = "File absolute exists";
            if (is_writable($log_file)) {
                $debug_info[] = "File absolute is writable";
                $result = file_put_contents($log_file, '');
                if ($result !== false) {
                    $success = true;
                    $debug_info[] = "Method 1 succeeded (absolute path)";
                } else {
                    $debug_info[] = "Method 1 failed: " . error_get_last()['message'];
                }
            } else {
                $debug_info[] = "File absolute not writable";
            }
        } else {
            $debug_info[] = "File absolute not found";
        }
    } catch (Exception $e) {
        $debug_info[] = "Exception in Method 1: " . $e->getMessage();
    }
    
    // Metode 2: Mencoba path relatif
    if (!$success) {
        try {
            if (file_exists($relative_log_file)) {
                $debug_info[] = "File relative exists";
                if (is_writable($relative_log_file)) {
                    $debug_info[] = "File relative is writable";
                    $result = file_put_contents($relative_log_file, '');
                    if ($result !== false) {
                        $success = true;
                        $debug_info[] = "Method 2 succeeded (relative path)";
                    } else {
                        $debug_info[] = "Method 2 failed: " . error_get_last()['message'];
                    }
                } else {
                    $debug_info[] = "File relative not writable";
                }
            } else {
                $debug_info[] = "File relative not found";
            }
        } catch (Exception $e) {
            $debug_info[] = "Exception in Method 2: " . $e->getMessage();
        }
    }

    // Metode 3: Menggunakan fopen/fclose/ftruncate
    if (!$success) {
        try {
            if (file_exists($log_file) || file_exists($relative_log_file)) {
                $path = file_exists($log_file) ? $log_file : $relative_log_file;
                $debug_info[] = "Trying Method 3 with path: $path";
                
                $fp = fopen($path, 'w');
                if ($fp) {
                    ftruncate($fp, 0);
                    fclose($fp);
                    $success = true;
                    $debug_info[] = "Method 3 succeeded";
                } else {
                    $debug_info[] = "Method 3 failed: could not open file";
                }
            }
        } catch (Exception $e) {
            $debug_info[] = "Exception in Method 3: " . $e->getMessage();
        }
    }
    
    // Metode 4: Menggunakan shell command jika PHP berjalan di Linux
    if (!$success && PHP_OS !== 'WINNT') {
        try {
            $debug_info[] = "Trying Method 4 (shell command)";
            $path = file_exists($log_file) ? $log_file : $relative_log_file;
            $debug_info[] = "Shell path: $path";
            
            $output = shell_exec("echo '' > " . escapeshellarg($path) . " 2>&1");
            $debug_info[] = "Shell output: " . ($output ?: "no output");
            
            if (file_exists($path) && filesize($path) == 0) {
                $success = true;
                $debug_info[] = "Method 4 succeeded (verified filesize)";
            } else {
                $debug_info[] = "Method 4 failed or couldn't verify";
                $debug_info[] = "File exists: " . (file_exists($path) ? "yes" : "no");
                $debug_info[] = "File size: " . (file_exists($path) ? filesize($path) : "N/A");
            }
        } catch (Exception $e) {
            $debug_info[] = "Exception in Method 4: " . $e->getMessage();
        }
    }
    
    // Jika sukses, tulis header baru
    if ($success) {
        try {
            $timestamp = date('d-m-Y H:i:s');
            $path = file_exists($log_file) ? $log_file : $relative_log_file;
            file_put_contents($path, "$timestamp - Log telah dibersihkan\n", FILE_APPEND);
            $debug_info[] = "Successfully wrote header to cleaned log";
        } catch (Exception $e) {
            $debug_info[] = "Exception writing header: " . $e->getMessage();
        }
    }
    
    // Log debug info untuk troubleshooting
    error_log("LOG DELETE DEBUG: " . json_encode($debug_info));
    
    return [
        'success' => $success,
        'debug' => $debug_info
    ];
}

// Handle aksi hapus log di awal file
if (isset($_POST['clear_log'])) {
    // Hapus log dengan berbagai metode
    $result = forceDeleteLog();
    
    if ($result['success']) {
        $message = "Log berhasil dihapus pada " . date('H:i:s');
    } else {
        $message = "Gagal menghapus log. Lihat server error log untuk informasi.";
    }
    
    // Force reload halaman tanpa POST data
    header("Location: " . $_SERVER['PHP_SELF'] . "?msg=" . urlencode($message) . "&t=" . time());
    exit;
}

if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg']);
}

?>

<!-- Tampilkan informasi interface sebelum interface selector -->
<div class="interface-info">
    <h2><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">router</i>Status Interface</h2>
    <div class="interface-cards">
        <?php
        $interfaces = ['wlan0', 'wlan1'];
        $selected_interface = getSelectedInterface();
        
        foreach ($interfaces as $interface) {
            $info = getInterfaceInfo($interface);
            $status_class = $info['status'] === 'aktif' ? 'active' : 'inactive';
            $selected = ($interface === $selected_interface) ? true : false;
            
            echo "
            <div class='interface-card $status_class'>
                <div class='interface-header'>
                    <span class='interface-name'>$interface</span>
                    <span class='interface-status {$info['status']}'>{$info['status']}</span>
                </div>
                <div class='interface-details'>
                    <p><strong>IP:</strong> " . ($info['ip'] ? $info['ip'] : 'Tidak tersedia') . "</p>
                    <p><strong>MAC:</strong> " . ($info['mac'] ? $info['mac'] : 'Tidak tersedia') . "</p>
                    <p><strong>Klien Terhubung:</strong> {$info['clients']}</p>
                </div>
                " . ($selected ? "<div class='interface-selected'><span class='selected-badge'><i class='material-icons' style='font-size: 14px; vertical-align: middle; margin-right: 3px;'>check_circle</i>Dipilih Sebagai Interface Hotspot</span></div>" : "") . "
            </div>
            ";
        }
        ?>
    </div>
</div>

<!-- Pindahkan interface selector ke bawah interface info -->
<div class="interface-selector">
    <h2><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">wifi</i>Pilih Interface</h2>
    
    <!-- Tambahkan keterangan -->
    <div class="interface-info-text">
        <p><i class="material-icons" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">info</i>Pilih sesuai interface WiFi yang aktif untuk mengelola hotspot</p>
    </div>
    
    <form method="POST" class="interface-form">
        <div class="interface-options">
            <?php
            $active_interfaces = getActiveInterfaces();
            $selected_interface = getSelectedInterface();
            foreach ($active_interfaces as $interface) {
                $checked = ($interface === $selected_interface) ? 'checked' : '';
                echo "
                <label class='interface-option'>
                    <input type='radio' name='interface' value='$interface' $checked>
                    <span class='interface-name'>$interface</span>
                    " . ($interface === $selected_interface ? 
                        "<span class='status-badge active-interface'>Aktif</span>" : 
                        "<span class='status-badge inactive-interface'>Tidak Aktif</span>") . "
                </label>";
            }
            ?>
        </div>
        <button type="submit" name="select_interface" class="button interface-button">
            <i class="material-icons" style="vertical-align: middle; margin-right: 5px;">save</i>Pilih Interface
        </button>
    </form>
</div>

<!-- Tambahkan CSS -->
<style>
    .interface-selector {
        background: rgba(0, 0, 0, 0.02);
        border-radius: 10px;
        padding: 15px;
        margin: 0 auto 20px auto;
        border: 1px solid rgba(0, 0, 0, 0.1);
        max-width: 500px;
        text-align: center;
    }

    .interface-form {
        margin-top: 10px;
    }

    .interface-options {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 15px;
    }

    .interface-option {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease;
        background: white;
    }

    .interface-option:hover {
        background: rgba(254, 202, 10, 0.1);
    }

    .interface-name {
        font-weight: bold;
    }

    .active-interface {
        background-color: rgba(39, 174, 96, 0.8) !important;
    }

    .inactive-interface {
        background-color: rgba(0, 0, 0, 0.3) !important;
    }

    .interface-button {
        background-color: #FECA0A !important;
        margin: 0 auto;
        display: block;
        min-width: 150px;
    }

    /* Dark mode support */
    body.dark-mode .interface-selector {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(254, 202, 10, 0.2);
    }

    body.dark-mode .interface-option {
        border-color: rgba(254, 202, 10, 0.2);
        background: #1a1a1a;
    }

    body.dark-mode .interface-option:hover {
        background: rgba(254, 202, 10, 0.2);
    }
    
    /* Responsive styling untuk tampilan compact di mobile */
    @media (max-width: 768px) {
        .interface-selector {
            padding: 12px 10px;
            max-width: 100%;
        }
        
        .interface-options {
            gap: 10px;
            margin-bottom: 12px;
        }
        
        .interface-option {
            padding: 6px 10px;
            gap: 6px;
        }
        
        .interface-button {
            min-width: 120px;
            padding: 6px 10px;
        }
    }
    
    @media (max-width: 480px) {
        .interface-selector {
            padding: 10px 8px;
        }
        
        .interface-options {
            gap: 6px;
            margin-bottom: 10px;
        }
        
        .interface-option {
            padding: 5px 8px;
            gap: 4px;
        }
        
        .interface-name {
            font-size: 0.9em;
        }
        
        .interface-button {
            min-width: 100px;
            padding: 5px 8px;
            font-size: 0.9em;
        }
    }

    /* Styling untuk keterangan interface */
    .interface-info-text {
        margin: 8px 0 15px 0;
        font-size: 0.9em;
        color: #666;
        background: rgba(0, 0, 0, 0.05);
        border-radius: 5px;
        padding: 8px 12px;
        text-align: center;
    }
    
    .interface-info-text p {
        margin: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    body.dark-mode .interface-info-text {
        color: #ccc;
        background: rgba(255, 255, 255, 0.1);
    }
    
    @media (max-width: 768px) {
        .interface-info-text {
            margin: 6px 0 12px 0;
            font-size: 0.85em;
            padding: 6px 10px;
        }
        
        .interface-info-text .material-icons {
            font-size: 14px !important;
        }
    }
    
    @media (max-width: 480px) {
        .interface-info-text {
            margin: 5px 0 10px 0;
            font-size: 0.8em;
            padding: 5px 8px;
        }
        
        .interface-info-text .material-icons {
            font-size: 12px !important;
        }
    }
</style>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Hotspot</title>
    <style>
        @font-face {
            font-family: 'Material Icons';
            font-style: normal;
            font-weight: 400;
            src: url('../webui/fonts/MaterialIcons-Regular.woff2') format('woff2'),
                 url('../webui/fonts/MaterialIcons-Regular.woff') format('woff');
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
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #FFFFFF;
            margin: 0;
            padding: 20px;
            color: #333333;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #FFFFFF;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 80px;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #FECA0A;
            margin-bottom: 30px;
            font-weight: 700;
            font-size: 28px;
            position: relative;
            padding-bottom: 15px;
            text-shadow: 0 2px 10px rgba(254, 202, 10, 0.3);
            letter-spacing: 1px;
        }

        h1:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, rgba(254, 202, 10, 0.1), #FECA0A, rgba(254, 202, 10, 0.1));
            border-radius: 2px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            font-size: 14px;
            background-color: #FFFFFF;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 16px;
            text-align: left;
        }

        th {
            background-color: rgba(254, 202, 10, 0.1);
            color: #333333;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
        }

        td {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        tr:hover {
            background-color: rgba(254, 202, 10, 0.05);
        }

        .button {
            display: inline-block;
            padding: 8px 16px;
            margin: 4px;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .refresh-button {
            background-color: #FECA0A;
            color: #000000;
        }

        .refresh-button:hover {
            background-color: #e5b609;
        }

        .block-button {
            background-color: #e74c3c;
            color: white;
        }

        .block-button:hover {
            background-color: #c0392b;
        }

        .unblock-button {
            background-color: #27ae60;
            color: white;
        }

        .unblock-button:hover {
            background-color: #219a52;
        }

        .manage-button {
            background-color: #FECA0A;
            color: #000000;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 5px;
        }

        .manage-button:hover {
            background-color: #e5b609;
        }

        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
        }

        .success {
            background-color: rgba(39, 174, 96, 0.1);
            color: #27ae60;
            border: 1px solid rgba(39, 174, 96, 0.3);
        }

        .error {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .limit-button {
            background-color: #FECA0A;
            color: #000000;
            padding: 8px 16px;
            font-size: 12px;
        }

        .limit-button:hover {
            background-color: #e5b609;
        }

        .unlimit-button {
            background-color: #1abc9c;
            color: white;
            padding: 8px 16px;
            font-size: 12px;
        }

        .unlimit-button:hover {
            background-color: #16a085;
        }

        .search-box {
            width: 100%;
            padding: 15px;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #FFFFFF;
            color: #333333;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .search-box:focus {
            border-color: #FECA0A;
            outline: none;
            box-shadow: 0 2px 15px rgba(254, 202, 10, 0.2);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin: 0 4px;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-blocked {
            background-color: rgba(231, 76, 60, 0.8);
            color: white;
            border: 1px solid rgba(254, 202, 10, 0.3);
        }

        .status-limited {
            background-color: rgba(254, 202, 10, 0.8);
            color: #000000;
            border: 1px solid rgba(254, 202, 10, 0.3);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 1000;
        }

        .modal-content {
            background-color: #FFFFFF;
            margin: 10% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            position: relative;
            animation: modalSlideIn 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 28px;
            color: #FECA0A;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close:hover {
            color: #e5b609;
            transform: rotate(90deg);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333333;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #FFFFFF;
            color: #333333;
        }

        .form-group input:focus {
            border-color: #FECA0A;
            outline: none;
            box-shadow: 0 2px 15px rgba(254, 202, 10, 0.2);
        }

        /* Footer Styles */
        .footer {
            background-color: #FFFFFF;
            padding: 20px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
            left: 0;
            z-index: 1000;
            box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.1);
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-content {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .button-group {
                flex-direction: column;
                gap: 10px;
            }

            .refresh-button, .theme-toggle {
                width: 100%;
            }

            table {
                font-size: 13px;
            }

            th, td {
                padding: 12px 8px;
            }

            .button {
                font-size: 12px;
                padding: 6px 12px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }

            h1 {
                font-size: 20px;
            }

            th, td {
                padding: 8px 6px;
                font-size: 12px;
            }

            td .button {
                margin: 2px 0;
                display: block;
                width: auto;
            }

            form[style*="display: inline"] {
                display: block !important;
                margin: 2px 0;
            }
        }

        /* Footer Brand Styles */
        .footer-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .copyright {
            color: #FECA0A;
            font-weight: 600;
            text-shadow: 0 0 10px rgba(254, 202, 10, 0.3);
        }

        .brand-name {
            color: #FECA0A;
            font-weight: 600;
            font-size: 1.1em;
        }

        .heart-icon {
            color: #FECA0A;
            animation: heartBeat 1.5s ease infinite;
            font-size: 18px !important;
        }

        .by-text {
            color: #F1F1F1;
            font-weight: 500;
        }

        .telegram-button {
            background-color: #1a1a1a;
            padding: 8px 16px;
            border-radius: 20px;
            color: #FECA0A;
            font-weight: 500;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(254, 202, 10, 0.3);
        }

        .telegram-button:hover {
            background-color: rgba(254, 202, 10, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(254, 202, 10, 0.2);
        }

        @keyframes heartBeat {
            0% { transform: scale(1); }
            14% { transform: scale(1.3); }
            28% { transform: scale(1); }
            42% { transform: scale(1.3); }
            70% { transform: scale(1); }
        }

        .theme-toggle {
            background-color: #FECA0A;
            color: #000000;
            padding: 12px 24px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .theme-toggle:hover {
            background-color: #e5b609;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
        }

        .theme-toggle i {
            font-size: 20px;
            transition: transform 0.5s ease;
        }

        body.dark-mode {
            background-color: #000000;
            color: #F1F1F1;
        }

        body.dark-mode .container {
            background-color: #1a1a1a;
            border: 1px solid rgba(254, 202, 10, 0.2);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        body.dark-mode table {
            background-color: #1a1a1a;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        body.dark-mode th {
            background-color: rgba(254, 202, 10, 0.1);
            color: #FECA0A;
        }

        body.dark-mode td {
            color: #F1F1F1;
            border-bottom: 1px solid rgba(254, 202, 10, 0.1);
        }

        body.dark-mode tr:hover {
            background-color: rgba(254, 202, 10, 0.05);
        }

        body.dark-mode .search-box {
            background-color: #1a1a1a;
            border-color: rgba(254, 202, 10, 0.2);
            color: #F1F1F1;
        }

        body.dark-mode .modal-content {
            background-color: #1a1a1a;
            color: #F1F1F1;
            border: 1px solid rgba(254, 202, 10, 0.3);
        }

        body.dark-mode .form-group input {
            background-color: #1a1a1a;
            border-color: rgba(254, 202, 10, 0.2);
            color: #F1F1F1;
        }

        body.dark-mode .form-group label {
            color: #FECA0A;
        }

        body.dark-mode .theme-toggle {
            background-color: #1a1a1a;
            color: #FECA0A;
            border: 1px solid rgba(254, 202, 10, 0.3);
        }

        body.dark-mode .theme-toggle:hover {
            background-color: rgba(254, 202, 10, 0.1);
        }

        body.dark-mode h2 {
            color: #FECA0A;
        }

        body.dark-mode .close {
            color: #FECA0A;
        }

        body.dark-mode .copyright,
        body.dark-mode .brand-name,
        body.dark-mode .heart-icon {
            color: #FECA0A;
        }

        body.dark-mode .by-text {
            color: #F1F1F1;
        }

        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .log-actions {
            display: flex;
            gap: 10px;
        }

        .clear-button {
            background-color: #e74c3c !important;
            color: white !important;
            border: none !important;
        }

        .clear-button:hover {
            background-color: #c0392b !important;
        }

        body.dark-mode .clear-button {
            background-color: #c0392b !important;
        }

        body.dark-mode .clear-button:hover {
            background-color: #e74c3c !important;
        }

        @media (max-width: 768px) {
            .log-header {
                flex-direction: column;
                gap: 15px;
            }

            .log-actions {
                width: 100%;
            }

            .log-actions button,
            .log-actions form {
                flex: 1;
            }

            .refresh-button,
            .clear-button {
                width: 100%;
                margin: 5px 0;
            }
        }

        .log-viewer {
            margin-top: 30px;
            background: rgba(0, 0, 0, 0.02);
            border-radius: 10px;
            padding: 20px;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .log-viewer h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.2em;
            display: flex;
            align-items: center;
        }

        .log-container {
            max-height: 400px;
            overflow-y: auto;
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            font-family: monospace;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .log-entry {
            padding: 8px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .log-entry:last-child {
            border-bottom: none;
        }

        .error-log {
            color: #e74c3c;
            background: rgba(231, 76, 60, 0.05);
        }

        .success-log {
            color: #27ae60;
            background: rgba(39, 174, 96, 0.05);
        }

        /* Dark mode support */
        body.dark-mode .log-viewer {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(254, 202, 10, 0.2);
        }

        body.dark-mode .log-viewer h2 {
            color: #FECA0A;
        }

        body.dark-mode .log-container {
            background: #1a1a1a;
            border-color: rgba(254, 202, 10, 0.2);
            color: #F1F1F1;
        }

        body.dark-mode .log-entry {
            border-color: rgba(255, 255, 255, 0.1);
        }

        body.dark-mode .error-log {
            color: #ff6b6b;
            background: rgba(255, 107, 107, 0.1);
        }

        body.dark-mode .success-log {
            color: #51cf66;
            background: rgba(81, 207, 102, 0.1);
        }

        /* Scrollbar styling */
        .log-container::-webkit-scrollbar {
            width: 8px;
        }

        .log-container::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 4px;
        }

        .log-container::-webkit-scrollbar-thumb {
            background: rgba(254, 202, 10, 0.5);
            border-radius: 4px;
        }

        .log-container::-webkit-scrollbar-thumb:hover {
            background: rgba(254, 202, 10, 0.7);
        }

        /* Styling khusus untuk unit bandwidth */
        .bandwidth-unit {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            text-transform: none !important;
        }

        .interface-info {
            margin-bottom: 30px;
        }

        .interface-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .interface-card {
            flex: 1;
            min-width: 250px;
            background-color: #FFFFFF;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: all 0.3s ease;
        }

        .interface-card.active {
            border: 2px solid #27ae60;
        }

        .interface-card.inactive {
            border: 2px solid #e74c3c;
        }

        .interface-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .interface-name {
            font-size: 1.2em;
            font-weight: 600;
        }

        .interface-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .interface-status.aktif {
            background-color: rgba(39, 174, 96, 0.8);
            color: white;
            border: 1px solid rgba(39, 174, 96, 0.3);
        }

        .interface-status.nonaktif {
            background-color: rgba(231, 76, 60, 0.8);
            color: white;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .interface-details p {
            margin: 8px 0;
        }

        .interface-selected {
            margin-top: 15px;
        }

        .selected-badge {
            background-color: #FECA0A;
            color: #000000;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        body.dark-mode .interface-card {
            background-color: #1a1a1a;
            color: #F1F1F1;
            border: 1px solid rgba(254, 202, 10, 0.2);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        body.dark-mode .interface-card.active {
            border-color: #51cf66;
        }

        body.dark-mode .interface-card.inactive {
            border-color: #ff6b6b;
        }

        body.dark-mode .interface-status.aktif {
            background-color: rgba(81, 207, 102, 0.8);
            color: white;
            border: 1px solid rgba(81, 207, 102, 0.3);
        }

        body.dark-mode .interface-status.nonaktif {
            background-color: rgba(255, 107, 107, 0.8);
            color: white;
            border: 1px solid rgba(255, 107, 107, 0.3);
        }

        body.dark-mode .selected-badge {
            background-color: #1a1a1a;
            color: #FECA0A;
            border: 1px solid rgba(254, 202, 10, 0.3);
        }

        .device-actions {
            background-color: rgba(0, 0, 0, 0.02);
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        body.dark-mode .device-actions {
            background-color: rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(254, 202, 10, 0.1);
        }
        
        .action-row td {
            padding: 10px;
        }
        
        .action-row:hover {
            background-color: transparent !important;
        }
        
        @media (max-width: 768px) {
            .device-actions {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 8px;
            }
            
            .device-actions .button {
                flex: 1 1 calc(50% - 8px);
                margin: 4px;
            }
        }
        
        @media (max-width: 480px) {
            .device-actions .button {
                flex: 1 1 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Perangkat Terkoneksi ke Hotspot</h1>
        
        <div class="button-group">
            <button class="button refresh-button" onclick="window.location.reload()">Refresh List</button>
            <button class="button theme-toggle" onclick="toggleTheme()">
                <i class="material-icons" id="theme-icon">dark_mode</i>
                <span id="theme-text">Mode Gelap</span>
            </button>
        </div>

        <form method="GET" action="">
            <input type="text" name="search" class="search-box" placeholder="Cari berdasarkan IP atau MAC..." value="<?php echo htmlspecialchars($search); ?>">
        </form>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'berhasil') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>IP Address</th>
                    <th>MAC Address</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (empty($devices)) {
                    echo '<tr><td colspan="4" style="text-align: center;"><i class="material-icons" style="vertical-align: middle; margin-right: 5px;">info</i>Tidak ada perangkat yang terkoneksi</td></tr>';
                } else {
                    $counter = 1;
                    foreach ($devices as $device) {
                        $status_badges = '';
                        if (isBlocked($device['ip'])) {
                            $status_badges .= '<span class="status-badge status-blocked"><i class="material-icons tiny" style="font-size: 12px; vertical-align: middle; margin-right: 3px;">block</i>Diblokir</span>';
                        }
                        if (isLimited($device['ip'])) {
                            $limit_value = getLimitValue($device['ip']);
                            $status_badges .= '<span class="status-badge" style="background-color: rgba(241, 196, 15, 0.8); color: white;">
                                <i class="material-icons tiny" style="font-size: 12px; vertical-align: middle; margin-right: 3px;">speed</i>
                                Limited: ' . $limit_value . ' <span class="bandwidth-unit">Mbps</span>
                            </span>';
                        }
                        
                        if (empty($status_badges)) {
                            $status_badges = '<span class="status-badge" style="background-color: rgba(39, 174, 96, 0.8); color: white;"><i class="material-icons tiny" style="font-size: 12px; vertical-align: middle; margin-right: 3px;">check_circle</i>Aktif</span>';
                        }
                        
                        echo "<tr>
                                <td>{$counter}</td>
                                <td>" . htmlspecialchars($device['ip']) . "</td>
                                <td>" . htmlspecialchars($device['mac']) . "</td>
                                <td>{$status_badges}</td>
                              </tr>";
                        
                        // Tambahkan baris baru untuk aksi di bawah informasi perangkat
                        echo "<tr class='action-row'>
                                <td colspan='4' class='device-actions'>
                                    <button class='button limit-button' onclick='openLimitModal(\"" . htmlspecialchars($device['ip']) . "\")'>
                                        <i class='material-icons tiny' style='vertical-align: middle; margin-right: 3px;'>speed</i>Limit
                                    </button>
                                    <form method='POST' style='display: inline;'>
                                        <input type='hidden' name='ip' value='" . htmlspecialchars($device['ip']) . "'>
                                        <button type='submit' name='unlimit' class='button unlimit-button' onclick='return confirm(\"Apakah Anda yakin ingin membuka limit bandwidth untuk perangkat ini?\")'>
                                            <i class='material-icons tiny' style='vertical-align: middle; margin-right: 3px;'>settings_ethernet</i>Buka Limit
                                        </button>
                                    </form>
                                    <form method='POST' style='display: inline;'>
                                        <input type='hidden' name='ip' value='" . htmlspecialchars($device['ip']) . "'>
                                        <button type='submit' name='block' class='button block-button' onclick='return confirm(\"Apakah Anda yakin ingin memblokir perangkat ini?\")'>
                                            <i class='material-icons tiny' style='vertical-align: middle; margin-right: 3px;'>block</i>Blokir
                                        </button>
                                    </form>
                                    <form method='POST' style='display: inline;'>
                                        <input type='hidden' name='ip' value='" . htmlspecialchars($device['ip']) . "'>
                                        <button type='submit' name='unblock' class='button unblock-button' onclick='return confirm(\"Apakah Anda yakin ingin membuka blokir perangkat ini?\")'>
                                            <i class='material-icons tiny' style='vertical-align: middle; margin-right: 3px;'>check_circle</i>Buka Blokir
                                        </button>
                                    </form>
                                </td>
                              </tr>";
                        $counter++;
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Modal Limiter -->
    <div id="limitModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeLimitModal()">&times;</span>
            <h2><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">speed</i>Batasi Bandwidth</h2>
            <form method="POST" action="">
                <input type="hidden" name="ip" id="limitIp">
                <div class="form-group">
                    <label for="download">Download (<span class="bandwidth-unit">Mbps</span>):</label>
                    <input type="number" id="download" name="download" value="1" min="0.1" step="0.1" required>
                </div>
                <button type="submit" name="limit" class="button manage-button">
                    <i class="material-icons" style="vertical-align: middle; margin-right: 5px;">save</i>Terapkan Limit
                </button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-brand">
                    <span class="copyright">© 2025</span>
                    <span class="brand-name">Hotspot Manager</span>
                    <i class="material-icons tiny heart-icon">favorite</i>
                    <span class="by-text">by</span>
                    <a href="https://t.me/latifan_id" class="telegram-link" target="_blank">
                        <span class="telegram-button">
                            <i class="material-icons tiny" style="vertical-align: middle; margin-right: 3px;">send</i>
                            <span>@latifan_id</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bagian log viewer dengan form submit langsung -->
    <div class="log-viewer">
        <h2><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">description</i>Log Limiter</h2>
        <?php if (!empty($message)): ?>
        <div class="message-container <?php echo (strpos($message, 'berhasil') !== false) ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        <div class="log-container">
            <?php
            // Tampilkan log dengan auto-refresh timestamp untuk mencegah caching
            $log_file = 'limiter.log';
            if (file_exists($log_file)) {
                $logs = @file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($logs === false) {
                    echo "<div class='log-entry error-log'>Error: Tidak dapat membaca file log</div>";
                } else {
                    $logs = array_reverse($logs);
                    $logs = array_slice($logs, 0, 50); // Ambil 50 log terakhir
                    if (empty($logs)) {
                        echo "<div class='log-entry'>Log kosong</div>";
                    } else {
                        foreach ($logs as $log) {
                            // Tentukan class CSS berdasarkan isi log
                            $logClass = '';
                            if (stripos($log, 'error') !== false) {
                                $logClass = 'error-log';
                            } elseif (stripos($log, 'sukses') !== false || stripos($log, 'berhasil') !== false) {
                                $logClass = 'success-log';
                            }
                            echo "<div class='log-entry $logClass'>" . htmlspecialchars($log) . "</div>";
                        }
                    }
                }
            } else {
                echo "<div class='log-entry'>File log tidak ditemukan</div>";
            }
            ?>
        </div>
        <div class="log-actions">
            <button class="button refresh-button" onclick="refreshLog()">
                <i class="material-icons" style="vertical-align: middle; margin-right: 5px;">refresh</i>Refresh Log
            </button>
            <!-- Gunakan form submit langsung untuk hapus log -->
            <form method="POST" id="clearLogForm" onsubmit="return confirmClearLog()">
                <button type="submit" name="clear_log" value="1" class="button clear-button">
                    <i class="material-icons" style="vertical-align: middle; margin-right: 5px;">delete</i>Hapus Log
                </button>
            </form>
        </div>
    </div>

    <script>
        // Auto refresh setiap 30 detik
        setTimeout(function() {
            location.reload();
        }, 30000);

        // Fungsi untuk membuka modal limiter
        function openLimitModal(ip) {
            document.getElementById('limitModal').style.display = 'block';
            document.getElementById('limitIp').value = ip;
        }

        // Fungsi untuk menutup modal limiter
        function closeLimitModal() {
            document.getElementById('limitModal').style.display = 'none';
        }

        // Tutup modal jika user klik di luar modal
        window.onclick = function(event) {
            var modal = document.getElementById('limitModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Fungsi untuk toggle dark mode
        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('theme-icon');
            const themeText = document.getElementById('theme-text');
            
            body.classList.toggle('dark-mode');
            
            if (body.classList.contains('dark-mode')) {
                themeIcon.textContent = 'light_mode';
                themeText.textContent = 'Mode Terang';
                localStorage.setItem('theme', 'dark');
            } else {
                themeIcon.textContent = 'dark_mode';
                themeText.textContent = 'Mode Gelap';
                localStorage.setItem('theme', 'light');
            }
        }

        // Aktifkan dark mode hanya jika sebelumnya disimpan dalam localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            const themeIcon = document.getElementById('theme-icon');
            const themeText = document.getElementById('theme-text');
            
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
                
                if (themeIcon && themeText) {
                    themeIcon.textContent = 'light_mode';
                    themeText.textContent = 'Mode Terang';
                }
            } else {
                document.body.classList.remove('dark-mode');
                
                if (themeIcon && themeText) {
                    themeIcon.textContent = 'dark_mode';
                    themeText.textContent = 'Mode Gelap';
                }
            }
        });

        // Tambahkan efek ripple ke semua tombol
        const buttons = document.querySelectorAll('.button');
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                const x = e.clientX - e.target.offsetLeft;
                const y = e.clientY - e.target.offsetTop;
                
                const ripple = document.createElement('span');
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Fungsi hapus log yang menggabungkan AJAX dan fallback
        function clearLog() {
            if (confirm("Apakah Anda yakin ingin menghapus semua log?")) {
                // Tampilkan pesan loading
                document.querySelector('.log-container').innerHTML = 
                    '<div class="log-entry">Menghapus log...</div>';
                
                // Coba gunakan AJAX terlebih dahulu
                fetch(window.location.href + '?clear_log=true&ajax=1&t=' + new Date().getTime(), {
                    method: 'GET',
                    headers: {
                        'Cache-Control': 'no-cache, no-store, must-revalidate',
                        'Pragma': 'no-cache',
                        'Expires': '0'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    // Berhasil, paksa reload halaman
                    window.location.reload(true); // true = force reload from server
                })
                .catch(error => {
                    console.error('Error saat menghapus log dengan AJAX:', error);
                    
                    // Fallback: Buat form dan submit
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = window.location.href;
                    
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'clear_log';
                    input.value = 'true';
                    
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                });
            }
        }
        
        // Perbaikan fungsi refreshLog
        function refreshLog() {
            // ... existing code ...
        }

        // Konfirmasi sebelum menghapus log
        function confirmClearLog() {
            return confirm("Apakah Anda yakin ingin menghapus semua log?");
        }
        
        // Fungsi refresh log
        function refreshLog() {
            window.location.reload(true); // Force reload dari server
        }
    </script>

    <!-- Add Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</body>
</html>  