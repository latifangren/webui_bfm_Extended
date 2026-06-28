<?php
// Fungsi untuk mendapatkan uptime
function getUptime() {
  $uptime = shell_exec('cat /proc/uptime');
  $uptime = floatval(explode(' ', $uptime)[0]);
  $days = floor($uptime / 86400);
  $hours = floor(($uptime % 86400) / 3600);
  $minutes = floor(($uptime % 3600) / 60);
  return "$days days, $hours hours, $minutes minutes";
}

// Mengembalikan data dalam format JSON
echo json_encode([
  'uptime_formatted' => getUptime(),
]);
?>