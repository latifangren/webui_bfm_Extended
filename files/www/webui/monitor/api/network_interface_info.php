<?php
$networkInterfaces = [
    'mobile' => ['name' => 'Mobile', 'icon' => 'mdi:signal', 'interface' => 'rmnet_data1'],
    'hotspot' => ['name' => 'Hotspot', 'icon' => 'basil:hotspot-outline', 'interface' => 'wlan0'],
    'eth' => ['name' => 'Ethernet', 'icon' => 'mdi:ethernet', 'interface' => 'eth0'],
    'usb' => ['name' => 'USB Tether', 'icon' => 'mdi:usb', 'interface' => 'rndis0']
];

$statsFile = 'network_stats.json';

if (file_exists($statsFile)) {
    $storedStats = json_decode(file_get_contents($statsFile), true);
} else {
    $storedStats = [];
}

$interfaces = explode("\n", trim(shell_exec("ls /sys/class/net")));

$networkInterfaceInfo = [];

foreach ($networkInterfaces as $key => $data) {
    $interfaceExists = in_array($data['interface'], $interfaces);
    if ($interfaceExists) {
        $rxBytes = trim(shell_exec("cat /sys/class/net/{$data['interface']}/statistics/rx_bytes"));
        $txBytes = trim(shell_exec("cat /sys/class/net/{$data['interface']}/statistics/tx_bytes"));
        
        if (isset($storedStats[$key])) {
            $storedStats[$key]['rx'] = (int) $rxBytes;
            $storedStats[$key]['tx'] = (int) $txBytes;
        } else {
            $storedStats[$key]['rx'] = (int) $rxBytes;
            $storedStats[$key]['tx'] = (int) $txBytes;
        }

        $networkInterfaceInfo[$key] = [
            'name' => $data['name'],
            'rx' => $storedStats[$key]['rx'],
            'tx' => $storedStats[$key]['tx'],
            'total' => $storedStats[$key]['rx'] + $storedStats[$key]['tx']
        ];
    } else {
        $networkInterfaceInfo[$key] = [
            'name' => $data['name'],
            'rx' => 0,
            'tx' => 0,
            'total' => 0
        ];
    }
}

file_put_contents($statsFile, json_encode($storedStats));

echo json_encode($networkInterfaceInfo);