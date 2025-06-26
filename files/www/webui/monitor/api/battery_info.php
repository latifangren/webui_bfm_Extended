<?php
// Mengambil informasi baterai
$batteryInfo = array();

// Mendapatkan informasi baterai
$batteryPath = '/sys/class/power_supply/BAT0/';
if (file_exists($batteryPath)) {
  $batteryInfo['battery_level'] = intval(trim(file_get_contents($batteryPath . 'capacity')));
  $batteryInfo['battery_status'] = trim(file_get_contents($batteryPath . 'status'));
  $batteryInfo['battery_temp'] = intval(trim(file_get_contents($batteryPath . 'temp'))) / 10;
  $batteryInfo['battery_voltage'] = intval(trim(file_get_contents($batteryPath . 'voltage_now'))) / 1000000;
} else {
  $batteryInfo['battery_level'] = 0;// battery_info.php
  $batteryInfo['battery_status'] = batStatusCheck($battery_status);
  $batteryInfo['battery_temp'] = 0;
  $batteryInfo['battery_voltage'] = 0;
}

// Mengembalikan data dalam format JSON
header('Content-Type: application/json');
echo json_encode($batteryInfo);
?>