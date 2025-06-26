<?php
// Fungsi untuk mendapatkan informasi storage
function getStorageInfo() {
  $storageData = shell_exec('df /data 2>/dev/null | tail -n1');
  if ($storageData !== 'N/A' && !empty($storageData)) {
    $storageParts = preg_split('/\s+/', trim($storageData));
    if (count($storageParts) >= 6) {
      $storageTotal = round($storageParts[1]/1024/1024, 1);
      $storageUsed = round($storageParts[2]/1024/1024, 1);
      $storageFree = round($storageParts[3]/1024/1024, 1);
      $storagePercent = $storageTotal > 0 ? round(($storageUsed/$storageTotal)*100) : 0;
    } else {
      $storageTotal = $storageUsed = $storageFree = $storagePercent = 0;
    }
  } else {
    $storageTotal = $storageUsed = $storageFree = $storagePercent = 0;
  }

  return [
    'storage_total' => $storageTotal,
    'storage_used' => $storageUsed,
    'storage_free' => $storageFree,
    'storage_percent' => $storagePercent,
  ];
}

// Mengembalikan data dalam format JSON
echo json_encode(getStorageInfo());
?>