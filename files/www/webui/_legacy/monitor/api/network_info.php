<?php
// Mengambil informasi jaringan
$networkInterfaces = array();

// Mendapatkan daftar antarmuka jaringan
$interfaces = array_keys($_SERVER);
foreach ($interfaces as $interface) {
  if (strpos($interface, 'HTTP_') !== 0 && strpos($interface, 'SERVER_') !== 0 && strpos($interface, 'REQUEST_') !== 0) {
    $data = array();
    $data['name'] = $interface;
    $data['ip'] = $_SERVER[$interface];
    $data['connected'] = true; // Asumsi terhubung
    $data['rx'] = 0; // Bytes diterima
    $data['tx'] = 0; // Bytes dikirim
    $data['total'] = 0; // Total bytes
    $networkInterfaces[] = $data;
  }
}

// Mendapatkan statistik jaringan
$rx = 0;
$tx = 0;
foreach (file('/proc/net/dev') as $line) {
  $data = explode(':', $line);
  if (count($data) > 1) {
    $stats = explode(' ', trim($data[1]));
    $rx += intval($stats[0]);
    $tx += intval($stats[8]);
  }
}
$networkInterfaces[0]['rx'] = $rx;
$networkInterfaces[0]['tx'] = $tx;
$networkInterfaces[0]['total'] = $rx + $tx;

// Mengembalikan data dalam format JSON
header('Content-Type: application/json');
echo json_encode(array('network_interfaces' => $networkInterfaces));
?>