<?php
// Memastikan waktu eksekusi cukup untuk pengujian
set_time_limit(600);
ini_set('memory_limit', '512M');

// Konfigurasi dasar
$ENABLE_CORS = true;

// Header untuk hasil
header('Content-Type: text/plain; charset=utf-8');
if ($ENABLE_CORS) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST');
    header('Access-Control-Allow-Headers: Content-Encoding, Content-Type');
}

// Handler untuk ping
if (isset($_GET["ping"])) {
    echo "PONG";
    exit;
}

// Handler untuk download
if (isset($_GET["download"])) {
    $allowed_sizes = array(50, 100, 200);
    $size = isset($_GET["download"]) ? intval($_GET["download"]) : 50;
    
    if (!in_array($size, $allowed_sizes)) {
        $size = 50; // Default ke 50MB jika ukuran tidak valid
    }
    
    $data_size = $size * 1024 * 1024; // Konversi ke bytes
    
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=random.dat');
    header('Content-Transfer-Encoding: binary');
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    // Mengoptimalkan pengiriman data untuk file besar
    ob_end_clean();
    
    // Meningkatkan efisiensi dengan chunk yang lebih besar
    $chunk_size = 4 * 1024 * 1024; // 4MB chunk untuk kinerja yang lebih baik
    $bytes_sent = 0;
    $chunk = str_repeat("0123456789", $chunk_size / 10);
    
    // Menonaktifkan kompresi jika aktif
    if (function_exists('apache_setenv')) {
        apache_setenv('no-gzip', '1');
    }
    
    // Kirim data dalam chunk sampai mencapai ukuran yang diminta
    while ($bytes_sent < $data_size) {
        $bytes_to_send = min($chunk_size, $data_size - $bytes_sent);
        if ($bytes_to_send < $chunk_size) {
            echo substr($chunk, 0, $bytes_to_send);
        } else {
            echo $chunk;
        }
        
        $bytes_sent += $bytes_to_send;
        flush();
        
        // Berikan waktu istirahat singkat untuk sistem dengan memori terbatas
        if ($bytes_sent % (20 * 1024 * 1024) == 0) { // Setiap 20MB
            usleep(100000); // 100ms istirahat
        }
    }
    
    exit;
}

// Handler untuk upload
if (isset($_GET["upload"])) {
    // Menerima data dari POST request
    $content_length = isset($_SERVER['CONTENT_LENGTH']) ? intval($_SERVER['CONTENT_LENGTH']) : 0;
    
    if (isset($_FILES['file']) && !empty($_FILES['file']['name'])) {
        // Jika menggunakan multipart form data
        echo $_FILES['file']['size'];
    } else {
        // Jika menggunakan raw POST data
        $input = file_get_contents("php://input");
        echo strlen($input);
    }
    
    exit;
}

// Default response jika tidak ada aksi yang diminta
echo "OK";
?> 