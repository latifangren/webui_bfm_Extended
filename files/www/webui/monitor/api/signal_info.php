<?php
// Fungsi untuk mendapatkan signal info
function getSignalInfo() {
  $signalInfo = shell_exec('dumpsys telephony.registry');
  $signalData = [];

  if (!empty($signalInfo)) {
    // Parse LTE signal with all parameters
    if (preg_match('/CellSignalStrengthLte:(.+?)(?=CellSignalStrength|$)/s', $signalInfo, $lteMatch)) {
      $lteData = $lteMatch[1];

      preg_match('/rssi=([-\d]+)/', $lteData, $rssi);
      preg_match('/rsrp=([-\d]+)/', $lteData, $rsrp);
      preg_match('/rsrq=([-\d]+)/', $lteData, $rsrq);
      preg_match('/rssnr=([-\d]+)/', $lteData, $rssnr); // SINR
      preg_match('/level=(\d)/', $lteData, $level);

      $signalData[] = [
        'type' => 'LTE',
        'rssi' => $rssi[1] ?? 'N/A',
        'rsrp' => $rsrp[1] ?? 'N/A',
        'rsrq' => $rsrq[1] ?? 'N/A',
        'sinr' => isset($rssnr[1]) ? round($rssnr[1] / 10) : 'N/A', // Displayed as SINR
        'level' => $level[1] ?? 0,
      ];
    }
  }

  return [
    'signal_data' => $signalData,
  ];
}

// Mengembalikan data dalam format JSON
echo json_encode(getSignalInfo());
?>