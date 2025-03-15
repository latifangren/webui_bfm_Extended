<?php
// Mengambil file JSON
$jsonFilePath = 'select_theme/theme.json';
$jsonData = file_get_contents($jsonFilePath);

// Mengecek apakah file JSON berhasil dibaca
if ($jsonData === false) {
    die('Error membaca file JSON');
}

// Decode JSON menjadi array
$data = json_decode($jsonData, true);

// Mengecek apakah ada kesalahan dalam decoding JSON
if ($data === null) {
    die('Error decoding JSON');
}

// Cek nilai path di dalam JSON
$path = isset($data['path']) ? $data['path'] : 'default';

// Tentukan file PHP yang akan di-include berdasarkan nilai path
if ($path === 'extended') {
    include('extended.php');
}
?>
