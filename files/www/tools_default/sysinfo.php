<?php
// Function: makeTitle
function makeTitle($title) {
    echo "<h2>$title</h2>";
}

// Function to avoid unwanted/wrong value from telephony.registry
function filterArray($array) {
    foreach ($array as $key => $values) {
        foreach ($values as $innerKey => $value) {
            if (abs($value) > 999) {
                unset($array[$key]);
                break;
            }
        }
    }
    return array_values($array);
}
// Function to extract LTE signal values from the input string
function extractActiveLteSignalValues($input) {
    $lteValues = [];
    if (preg_match_all('/CellSignalStrengthLte: rssi=([-\d]+) rsrp=([-\d]+) rsrq=([-\d]+) rssnr=([-\d]+) .*? level=([1-9]+)/', $input, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $rssi = (int)$match[1];
            $rsrp = (int)$match[2];
            $rsrq = (int)$match[3];
            $rssnr = (int)$match[4];
            
            // Hitung SINR menggunakan kedua metode
            $sinrFromRssnr = calculateSINR($rssnr);
            $sinrFromRsrpRsrq = calculateSINRFromRSRPRSRQ($rsrp, $rsrq, $rssi);
            
            // Gunakan nilai SINR yang lebih akurat
            $finalSinr = ($rssnr != 0) ? $sinrFromRssnr : $sinrFromRsrpRsrq;
            
            $lteValues[] = [
                'rssi' => $rssi,
                'rsrp' => $rsrp,
                'rsrq' => $rsrq,
                'rssnr' => $rssnr,
                'sinr' => $finalSinr
            ];
        }
    }
    return filterArray($lteValues);
}

// Function untuk menghitung SINR dari RSRP dan RSRQ
function calculateSINRFromRSRPRSRQ($rsrp, $rsrq, $rssi) {
    // Metode 1: Berdasarkan rumus standar SINR = RSRP - (RSRQ × RSRP / RSRQ)
    $sinr1 = 0;
    if ($rsrq != 0) {
        $sinr1 = $rsrp * (1 + (1 / $rsrq));
        $sinr1 = round(max(-10, min(25, $sinr1 + 110)), 1);
    }
    
    // Metode 2: Pendekatan berbasis korelasi empiris
    $rsrpFactor = ($rsrp + 140) / 60;
    $rsrqFactor = ($rsrq + 25) / 15;
    $sinr2 = ($rsrpFactor * 0.7 + $rsrqFactor * 0.3) * 30 - 10;
    $sinr2 = round(max(-10, min(25, $sinr2)), 1);
    
    // Metode 3: Berdasarkan RSSI
    $noiseFloor = ($rssi - 10 * log10(12 * 1.2));
    $sinr3 = $rsrp - $noiseFloor;
    $sinr3 = round(max(-10, min(25, $sinr3)), 1);
    
    // Kombinasikan hasil dengan pembobotan
    $combinedSINR = 0;
    if ($rsrq > -12) {
        $combinedSINR = ($sinr1 * 0.5 + $sinr2 * 0.3 + $sinr3 * 0.2);
    } elseif ($rsrp > -95) {
        $combinedSINR = ($sinr1 * 0.3 + $sinr2 * 0.5 + $sinr3 * 0.2);
    } else {
        $combinedSINR = ($sinr1 * 0.3 + $sinr2 * 0.3 + $sinr3 * 0.4);
    }
    
    // Koreksi final
    if ($rsrp > -80 && $combinedSINR < 10) {
        $combinedSINR = max($combinedSINR, 10);
    } elseif ($rsrp < -110 && $combinedSINR > 5) {
        $combinedSINR = min($combinedSINR, 5);
    }
    
    return round($combinedSINR, 1);
}

// Function untuk menghitung SINR dari RSSNR
function calculateSINR($rssnr) {
    if ($rssnr === null || !is_numeric($rssnr)) {
        return 0;
    }
    
    if ($rssnr < -30) {
        return -10;
    } elseif ($rssnr > 50) {
        return 25;
    }
    
    if ($rssnr >= 30) {
        return round($rssnr * 0.6);
    } elseif ($rssnr >= 20) {
        return round($rssnr * 0.65);
    } elseif ($rssnr >= 10) {
        return round($rssnr * 0.7);
    } elseif ($rssnr >= 0) {
        return round($rssnr * 0.8);
    } elseif ($rssnr >= -10) {
        return round($rssnr * 0.9);
    } elseif ($rssnr >= -20) {
        return round($rssnr * 0.7);
    } else {
        return round($rssnr * 0.5);
    }
}

// Function to determine the quality of the LTE signal
function assessLteSignalQuality($lteValues) {
    $qualityList = [];
    foreach ($lteValues as $lte) {
        $quality = [];
        
        // Assess RSRP
        if ($lte['rsrp'] >= -75) {
            $quality['rsrp'] = 'Excellent';
        } elseif ($lte['rsrp'] >= -95) {
            $quality['rsrp'] = 'Good';
        } elseif ($lte['rsrp'] >= -110) {
            $quality['rsrp'] = 'Fair';
        } else {
            $quality['rsrp'] = 'Bad';
        }

        // Assess RSRQ
        if ($lte['rsrq'] >= -8) {
            $quality['rsrq'] = 'Excellent';
        } elseif ($lte['rsrq'] >= -12) {
            $quality['rsrq'] = 'Good';
        } elseif ($lte['rsrq'] >= -16) {
            $quality['rsrq'] = 'Fair';
        } else {
            $quality['rsrq'] = 'Bad';
        }

        // Assess SINR
        if ($lte['sinr'] >= 15) {
            $quality['rssnr'] = 'Excellent';
        } elseif ($lte['sinr'] >= 10) {
            $quality['rssnr'] = 'Good';
        } elseif ($lte['sinr'] >= 5) {
            $quality['rssnr'] = 'Fair';
        } else {
            $quality['rssnr'] = 'Bad';
        }

        // Calculate overall quality
        $rsrpQuality = (($lte['rsrp'] + 140) / 60) * 100;
        $rsrqQuality = (($lte['rsrq'] + 25) / 15) * 100;
        $sinrQuality = (($lte['sinr'] + 10) / 25) * 100;
        
        // Weighted average with more emphasis on RSRP and SINR
        $overallQuality = ($rsrpQuality * 0.4 + $rsrqQuality * 0.2 + $sinrQuality * 0.4) / 100 * 5;
        $quality['overall'] = round(max(1, min(5, $overallQuality)), 2);

        $qualityList[] = $quality;
    }
    return $qualityList;
}

// Fungsi untuk menampilkan rating dengan emoji bulan seperti sistem bintang
function displayMoonRating($score) {
    // Emoji untuk penuh, setengah, dan kosong
    $fMoon = '🌕';   // Emoji bulan purnama (penuh)
    $gMoon = '🌖'; // Emoji bulan gibbous (lebih dari setengah)
    $hMoon = '🌗';    // Emoji bulan setengah
    $qMoon = '🌘'; // Emoji bulan sabit kecil (kurang dari setengah)
    $eMoon = '🌑';   // Emoji bulan baru (kosong)

    // Membatasi skor dalam rentang 1-5
    $score = max(1, min(5, $score));
    
    // Menentukan jumlah emoji penuh, setengah, dan kosong
    $fullMoons = floor($score); // Bulan purnama penuh
    $fraction = $score - $fullMoons; // Pecahan dari skor
    
    $gibbousMoons = 0;
    $halfMoons = 0;
    $quarterMoons = 0;
    
    // Menentukan emoji bulan setengah berdasarkan pecahan
    if ($fraction >= 0.9) {
        $fullMoons++;
    } elseif ($fraction >= 0.7) {
        $gibbousMoons = 1;
    } elseif ($fraction >= 0.4) {
        $halfMoons = 1;
    } elseif ($fraction >= 0.1) {
        $quarterMoons = 1;
    }
    
    $emptyMoons = 5 - $fullMoons - $gibbousMoons - $halfMoons - $quarterMoons; // Bulan kosong

    // Membuat string rating dengan urutan yang benar
    $rating = str_repeat($fMoon, $fullMoons) 
            . str_repeat($gMoon, $gibbousMoons) 
            . str_repeat($hMoon, $halfMoons) 
            . str_repeat($qMoon, $quarterMoons)
            . str_repeat($eMoon, $emptyMoons);
    
    return $rating;
}
function dataStatusCheck($state) {
    switch ($state) {
        case 0:
            return "Idle";
        case 1:
            return "Connecting";
        case 2:
            return "Connected";
        case 3:
            return "Disconnecting";
        case 4:
            return "Disconnected";
    }
}
function checkSignal(){
    $telephony = shell_exec("dumpsys telephony.registry | grep -E 'mSignalStrength='");
    // Extract LTE signal values for active SIM slots
    $lteValues = extractActiveLteSignalValues($telephony);
    // Assess LTE signal quality for active SIM slots
    $qualityList = assessLteSignalQuality($lteValues);
    
    $sim_operator = shell_exec('getprop gsm.sim.operator.alpha');
    $sims = explode(',', trim($sim_operator));
    
    $data_type = shell_exec('getprop gsm.network.type');
    $datyp = explode(',', trim($data_type));
    
    $idonow = shell_exec('dumpsys telephony.registry | grep -E "mDataConnectionState="');
    preg_match_all('/mDataConnectionState=(-?\d+)/', $idonow, $conmatches);
    $dataConnection = [];
    foreach ($conmatches[1] as $state) {
        if ((int)$state < 0) {
            $dataConnection[] = 0;
        } else {
            $dataConnection[] = (int)$state;
        }
    }
    
    $i = 0;
    foreach ($sims as $slot => $sim_op) {
        if (mb_strlen(trim($sim_op)) !== 0) {
echo "<div class='container'>";
echo '  <div class="row">';
// Card for Provider SIM
echo "<div class='card-00'>";
echo "    <i class='fas fa-sim-card'></i>";
echo "    <h3>Provider SIM " . ($slot + 1) . "</h3>";
echo "    <p>" . strtoupper($sim_op) . "</p>";
echo "</div>";

// Card for Network Type
echo "<div class='card-00'>";
echo "    <i class='fas fa-network-wired'></i>";
echo "    <h3>Network Type</h3>";
echo "    <p>" . $datyp[$slot] . " (" . dataStatusCheck($dataConnection[$slot]) . ")</p>";
echo "</div>";
echo '  </div>';
// Conditional Cards for LTE data
    echo '  <div class="row">';
if (strtoupper($datyp[$slot]) == 'LTE') {
    echo "<div class='card-00'>";
    echo "    <i class='fas fa-signal'></i>";
    echo "    <h3>LteRSRP</h3>";
    echo "    <p>" . $lteValues[$i]['rsrp'] . " dBm (" . $qualityList[$i]['rsrp'] . ")</p>";
    echo "</div>";

    echo "<div class='card-00'>";
    echo "    <i class='fas fa-signal'></i>";
    echo "    <h3>LteRSRQ</h3>";
    echo "    <p>" . $lteValues[$i]['rsrq'] . " dB (" . $qualityList[$i]['rsrq'] . ")</p>";
    echo "</div>";

    echo "<div class='card-00'>";
    echo "    <i class='fas fa-signal'></i>";
    echo "    <h3>LteSINR</h3>";
    echo "    <p>" . $lteValues[$i]['sinr'] . " dB (" . $qualityList[$i]['rssnr'] . ")</p>";
    echo "</div>";

    // Tambahkan informasi LTE Band
    $lteBands = getLteBand();
    if (!empty($lteBands)) {
        echo "<div class='card-00'>";
        echo "    <i class='fas fa-broadcast-tower'></i>";
        echo "    <h3>LTE Band</h3>";
        echo "    <p style='font-size: 0.9em;'>";
        foreach ($lteBands as $band) {
            echo "{$band['band']} ({$band['freq']})<br>";
            echo "<small style='color: #888;'>{$band['provider']}</small>";
        }
        echo "    </p>";
        echo "</div>";
    }

    echo "<div class='card-00'>";
    echo "    <i class='fas fa-star'></i>";
    echo "    <h3>Signal Quality</h3>";
    echo "    <p>" . displayMoonRating($qualityList[$i]['overall']) . "<br>(" . $qualityList[$i]['overall'] . ")</p>";
    echo "</div>";

    $i++;
} else {
    echo "<div class='card-00'>";
    echo "    <i class='fas fa-exclamation-triangle'></i>"; // Use an appropriate icon for "Not Available"
    echo "    <h3>Signal Quality</h3>";
    echo "    <p>Not Available</p>";
    echo "</div>";
    echo "</div>";
}

echo "</div>";

        }
    }
}

// Functions for System-info
function memory() {
    $total_memory_kb = shell_exec('cat /proc/meminfo | grep MemTotal | awk \'{print $2}\'');
    $total_memory_gb = intval(trim($total_memory_kb)) / 1024 / 1024; // Convert to GB
    $total_memory_gb_rounded = round($total_memory_gb, 1);
    $total_memory_mb_rounded = round($total_memory_gb * 1024, 1);

    $free_memory_kb = shell_exec('cat /proc/meminfo | grep MemFree | awk \'{print $2}\'');
    $free_memory_gb = intval(trim($free_memory_kb)) / 1024 / 1024; // Convert to GB
    $free_memory_gb_rounded = round($free_memory_gb, 1);
    $free_memory_mb_rounded = round($free_memory_gb * 1024, 1);

    $buffers_memory_kb = shell_exec('cat /proc/meminfo | grep Buffers | awk \'{print $2}\'');
    $buffers_memory_gb = intval(trim($buffers_memory_kb)) / 1024 / 1024; // Convert to GB
    $buffers_memory_gb_rounded = round($buffers_memory_gb, 1);
    $buffers_memory_mb_rounded = round($buffers_memory_gb * 1024, 1);

    $cached_memory_kb = shell_exec('cat /proc/meminfo | grep ^Cached | awk \'{print $2}\'');
    $cached_memory_gb = intval(trim($cached_memory_kb)) / 1024 / 1024; // Convert to GB
    $cached_memory_gb_rounded = round($cached_memory_gb, 1);
    $cached_memory_mb_rounded = round($cached_memory_gb * 1024, 1);
    
    $used_memory_gb = $total_memory_gb_rounded - $free_memory_gb_rounded - $buffers_memory_gb_rounded - $cached_memory_gb_rounded;
    $used_memory_mb = $total_memory_mb_rounded - $free_memory_mb_rounded - $buffers_memory_mb_rounded - $cached_memory_mb_rounded;
    $used_memory_percent = round(($used_memory_gb / $total_memory_gb_rounded) * 100);
    
    $available_memory_gb = $free_memory_gb_rounded + $buffers_memory_gb_rounded + $cached_memory_gb_rounded;
    $available_memory_mb = $free_memory_mb_rounded + $buffers_memory_mb_rounded + $cached_memory_mb_rounded;

echo "<div class='container'>";
echo '  <div class="row">';
// Card for RAM Usage
echo "<div class='card-00'>";
echo "    <i class='fas fa-memory'></i>"; // Icon for RAM
echo "    <h3>RAM Usage</h3>";
echo "    <p>";
if ($used_memory_gb >= 1) {
    echo "$used_memory_gb GB / ";
} else {
    echo "$used_memory_mb MB / ";
}
if ($total_memory_gb_rounded >= 1) {
    echo "$total_memory_gb_rounded GB ($used_memory_percent%) ";
} else {
    echo "$total_memory_mb_rounded MB ($used_memory_percent%) ";
}
if ($available_memory_gb >= 1) {
    echo "<br>Free: $available_memory_gb GB";
} else {
    echo "<br>Free: $available_memory_mb MB";
}
echo "    </p>";
echo "</div>";
echo "</div>";
echo "</div>";

    
    // Fetching temperature information from /sys/class/thermal/thermal_zone0/temp
    // $temperature = shell_exec('cat /sys/class/thermal/thermal_zone0/temp');
    // $temperature = round(intval(trim($temperature)) / 1000, 1); // Convert to Celsius
    // echo "<tr><td>Temperature</td><td>$temperature °C</td></tr>";
}
function getCpuUsage() {
    // Read the /proc/stat file
    $stats1 = file('/proc/stat');
    $cpuLine1 = $stats1[0]; // The first line contains CPU stats
    // Extract numeric values from the line
    $values1 = array_map('intval', preg_split('/\s+/', trim($cpuLine1)));
    list($cpu, $user1, $nice1, $system1, $idle1) = array_slice($values1, 0, 5);

    // Sleep for 0.5 second to measure CPU usage
    usleep(500000);

    // Read the /proc/stat file again
    $stats2 = file('/proc/stat');
    $cpuLine2 = $stats2[0]; // The first line contains CPU stats
    // Extract numeric values from the line
    $values2 = array_map('intval', preg_split('/\s+/', trim($cpuLine2)));
    list($cpu, $user2, $nice2, $system2, $idle2) = array_slice($values2, 0, 5);

    // Calculate the differences
    $total1 = $user1 + $nice1 + $system1 + $idle1;
    $total2 = $user2 + $nice2 + $system2 + $idle2;
    if ($total2 === $total1) {
        // Avoid division by zero
        return 0;
    }
    $idleDiff = $idle2 - $idle1;
    $totalDiff = $total2 - $total1;
    // Calculate CPU usage percentage
    $cpuUsage = ($totalDiff - $idleDiff) / $totalDiff * 100;

    return $cpuUsage;
}
function systemInfo() {
    $android_version = shell_exec('getprop ro.build.version.release');
    $android_version = trim($android_version);
    $os = "Android $android_version";
    $distro = ""; // Customize for your environment if needed
    $hostname = php_uname('n');
    $kernel_info = php_uname('r');
    $uptime = shell_exec('cat /proc/uptime');
    $uptime = explode(' ', $uptime);
    $uptime_seconds = intval(trim($uptime[0]));
    $uptime_minutes = intval($uptime_seconds / 60 % 60);
    $uptime_hours = intval($uptime_seconds / 60 / 60 % 24);
    $uptime_days = intval($uptime_seconds / 60 / 60 / 24);
    
    date_default_timezone_set('Asia/Jakarta');
    $current_date = date('Y-m-d H:i:s');

    $device_model = shell_exec('getprop ro.product.model');
    $device_model = trim($device_model);
    
    $cpu_used = round(getCpuUsage(), 2);
    
echo <<<HTML
<div class="container">
  <div class="row">
    <div class="card-01">
        <i class="fas fa-mobile-alt"></i>
        <h3>Device Model</h3>
        <p>{$device_model}</p>
    </div>
    
    <div class="card-01">
        <i class="fab fa-android"></i>
        <h3>OS</h3>
        <p>{$os} {$distro}</p>
    </div>
    <div class="card-01">
        <i class="fas fa-network-wired"></i>
        <h3>Hostname</h3>
        <p>{$hostname}</p>
    </div>
    </div>
    <div class="row">

    <div class="card-00">
        <i class="fas fa-clock"></i>
        <h3>Uptime</h3>
        <p>{$uptime_days} days, {$uptime_hours} hours, {$uptime_minutes} minutes</p>
    </div>
    <div class="card-00">
        <i class="fas fa-calendar-alt"></i>
        <h3>Current date</h3>
        <p>{$current_date}</p>
    </div>
    <div class="card-00">
        <i class="fas fa-microchip"></i>
        <h3>CPU Usage</h3>
        <p>{$cpu_used}% / 100%</p>
    </div>
    </div>
    <div class="row">
    <div class="card-000">
        <i class="fas fa-microchip"></i>
        <h3>Kernel</h3>
        <p>{$kernel_info}</p>
    </div>
</div>
HTML;


}

// Function: battery
function batStatusCheck($state) {
    switch ($state) {
        case 1:
            return "Unknown";
        case 2:
            return "Charging";
        case 3:
            return "Discharging";
        case 4:
            return "Not charging";
        case 5:
            return "Full";
    }
}
function battery() {
    $ac_powered = shell_exec('dumpsys battery | grep AC | cut -d \':\' -f2');
    $battery_level = shell_exec('dumpsys battery | grep level | cut -d \':\' -f2');
    $battery_status = shell_exec('dumpsys battery | grep status | cut -d \':\' -f2');
    $battery_current = shell_exec('cat /sys/class/power_supply/battery/current_now');
    if (strlen(trim($battery_current)) >= 5) {
        $battery_current = round(shell_exec('cat /sys/class/power_supply/battery/current_now') / 1000);
    }
    $battery_voltage = round(shell_exec('cat /sys/class/power_supply/battery/voltage_now') / 1000000, 2);
    $battery_temperature = shell_exec('dumpsys battery | grep temperature | cut -d \':\' -f2') / 10;
    // $ac_powered = trim($ac_powered);
    
echo '<div class="container">';
echo '  <div class="row">';
echo '    <div class="card-01">';
echo '        <i class="fas fa-plug"></i>';
echo '        <h3>AC Powered</h3>';
echo '        <p>' . strtoupper(htmlspecialchars($ac_powered)) . '</p>';
echo '    </div>';
echo '    <div class="card-01">';
echo '        <i class="fas fa-battery-full"></i>';
echo '        <h3>Status</h3>';
echo '        <p>' . htmlspecialchars(batStatusCheck($battery_status)) . '</p>';
echo '    </div>';
echo '    <div class="card-01">';
echo '        <i class="fas fa-battery-three-quarters"></i>';
echo '        <h3>Level</h3>';
echo '        <p>' . htmlspecialchars($battery_level) . '%</p>';
echo '    </div>';
echo '  </div>';
echo '  <div class="row">';
echo '    <div class="card-01">';
echo '        <i class="fas fa-bolt"></i>';
echo '        <h3>Current</h3>';
echo '        <p>' . htmlspecialchars($battery_current) . ' mA</p>';
echo '    </div>';
echo '    <div class="card-01">';
echo '        <i class="fas fa-bolt"></i>'; // Use an appropriate icon or custom icon
echo '        <h3>Voltage</h3>';
echo '        <p>' . htmlspecialchars($battery_voltage) . ' V</p>';
echo '    </div>';
echo '    <div class="card-01">';
echo '        <i class="fas fa-thermometer-half"></i>';
echo '        <h3>Temperature</h3>';
echo '        <p>' . htmlspecialchars($battery_temperature) . ' °C</p>';
echo '    </div>';
echo '  </div>';
echo '</div>';

}

// Function: load_average
function load_average() {
    $cpu_nb = shell_exec('cat /proc/cpuinfo | grep "^processor" | wc -l');
    $cpu_nb = intval(trim($cpu_nb));

    $loadavg = shell_exec('cat /proc/loadavg');
    $loadavg_arr = explode(' ', $loadavg);

    $load_1 = floatval($loadavg_arr[0]);
    $load_2 = floatval($loadavg_arr[1]);
    $load_3 = floatval($loadavg_arr[2]);

    $load_1_percent = round(($load_1 / $cpu_nb) * 100);
    $load_2_percent = round(($load_2 / $cpu_nb) * 100);
    $load_3_percent = round(($load_3 / $cpu_nb) * 100);

echo <<<HTML
<div class="container">
    <div class="row">
    <div class="card-00">
        <i class="fas fa-tachometer-alt"></i>
        <h3>Load Average (1 min)</h3>
        <p>{$load_1_percent}% ({$load_1})</p>
    </div>
    <div class="card-00">
        <i class="fas fa-tachometer-alt"></i>
        <h3>Load Average (5 min)</h3>
        <p>{$load_2_percent}% ({$load_2})</p>
    </div>
    <div class="card-00">
        <i class="fas fa-tachometer-alt"></i>
        <h3>Load Average (15 min)</h3>
        <p>{$load_3_percent}% ({$load_3})</p>
    </div>
    </div>
</div>
HTML;

}

// Function: network
function network() {
    // Database DNS publik yang populer
    $publicDnsProviders = [
        '8.8.8.8' => ['name' => 'Google DNS', 'icon' => 'fab fa-google'],
        '8.8.4.4' => ['name' => 'Google DNS', 'icon' => 'fab fa-google'],
        '1.1.1.1' => ['name' => 'Cloudflare DNS', 'icon' => 'fas fa-cloud'],
        '1.0.0.1' => ['name' => 'Cloudflare DNS', 'icon' => 'fas fa-cloud'],
        '9.9.9.9' => ['name' => 'Quad9 DNS', 'icon' => 'fas fa-shield-alt'],
        '149.112.112.112' => ['name' => 'Quad9 DNS', 'icon' => 'fas fa-shield-alt'],
        '208.67.222.222' => ['name' => 'OpenDNS', 'icon' => 'fas fa-lock'],
        '208.67.220.220' => ['name' => 'OpenDNS', 'icon' => 'fas fa-lock']
    ];
    
    // Database ISP DNS berdasarkan country/area code
    $ispDnsPatterns = [
        // Indonesia
        '/^180\.131\./' => ['name' => 'Telkom Indonesia DNS', 'icon' => 'fas fa-broadcast-tower'],
        '/^202\.134\./' => ['name' => 'Indosat Ooredoo DNS', 'icon' => 'fas fa-broadcast-tower'],
        '/^203\.142\./' => ['name' => 'XL Axiata DNS', 'icon' => 'fas fa-broadcast-tower'],
        '/^202\.152\./' => ['name' => 'Telkomsel DNS', 'icon' => 'fas fa-broadcast-tower'],
        '/^114\.4\./' => ['name' => 'Indosat DNS', 'icon' => 'fas fa-broadcast-tower'],
        '/^202\.155\./' => ['name' => 'Telkom DNS', 'icon' => 'fas fa-broadcast-tower'],
        '/^202\.51\./' => ['name' => 'CBN DNS', 'icon' => 'fas fa-broadcast-tower'],
        '/^202\.162\./' => ['name' => 'Biznet DNS', 'icon' => 'fas fa-broadcast-tower'],
        '/^103\.28\./' => ['name' => 'MyRepublic DNS', 'icon' => 'fas fa-broadcast-tower']
    ];

    // Get IP addresses
    $ip_addresses = shell_exec('ip address show | grep "inet " | grep -v "127.0.0.1" | awk \'{print $2}\' | cut -f1 -d"/"');
    $ip_addresses = array_unique(array_filter(explode("\n", trim($ip_addresses))));

    // Get Gateway
    $gateway = shell_exec('ip route | awk \'/default/ {print $3}\'');
    $gateway = trim($gateway);
    
    // Default DNS IP addresses
    $dumpDns = shell_exec('dumpsys connectivity | grep "DnsAddresses:" | sed -n \'s/.*DnsAddresses: \[ \([^,]*\),.*/\1/p\' | tr -d \'/\'');
    // Regular expression to match both IPv4 and IPv6 addresses    
    preg_match_all('/([0-9]{1,3}\.){3}[0-9]{1,3}|([a-fA-F0-9:]+:+)+[a-fA-F0-9]+/', $dumpDns, $dnsIP);
    $dnsIPs = isset($dnsIP[0]) ? array_unique($dnsIP[0]) : array();
    
    // Filter untuk hanya menampilkan DNS publik
    $publicDnsIPs = array_filter($dnsIPs, function($ip) {
        // Cek apakah IP adalah private
        $isPrivate = (
            preg_match('/^(10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.|192\.168\.)/', $ip) ||
            preg_match('/^fc00:|^fe80:|^fd/', $ip)
        );
        return !$isPrivate;
    });
    
    // Output IP addresses
    echo "<div class='container'>";
    echo '  <div class="row">';
    echo "  <div class='card-01'>";
    echo "    <i class='fas fa-network-wired'></i>"; // Icon for IP address and gateway
    echo "    <h3>IP Gateway</h3>";
    echo "    <p style='text-align: left;'>";
    if (empty($ip_addresses) && empty($gateway)) {
        echo "<span style='color: #ff6b6b;'><i class='fas fa-exclamation-circle'></i> Unavailable</span>";
    } else {
        if (!empty($ip_addresses)) {
            echo "<div style='margin-bottom: 5px;'><strong>IP Address:</strong></div>";
            foreach ($ip_addresses as $ip_address) {
                if (!empty(trim($ip_address))) {
                    echo "<div style='margin: 2px 0;'><i class='fas fa-network-wired' style='margin-right: 10px; font-size: 0.9em;'></i>{$ip_address}</div>";
                }
            }
        }
        if (!empty($gateway)) {
            echo "<div style='margin-top: 10px;'><strong>Gateway:</strong></div>";
            echo "<div style='margin: 2px 0;'><i class='fas fa-route' style='margin-right: 10px; font-size: 0.9em;'></i>{$gateway}</div>";
        }
    }
    echo "    </p>";
    echo "</div>";

    echo "<div class='card-01'>";
    echo "    <i class='fas fa-server'></i>"; // Icon for DNS Provider
    echo "    <h3>DNS Provider IP Address</h3>";
    echo "    <p style='text-align: left;'>";
    if (empty($publicDnsIPs)) {
        echo "<span style='color: #ff6b6b;'><i class='fas fa-exclamation-circle'></i> Unavailable</span>";
    } else {
        $displayedDNS = array(); // Track displayed DNS to avoid duplicates
        foreach ($publicDnsIPs as $dns_address) {
            if (empty(trim($dns_address)) || in_array($dns_address, $displayedDNS)) continue;
            
            $providerFound = false;
            
            // Cek apakah ada di database DNS publik
            if (isset($publicDnsProviders[$dns_address])) {
                $provider = $publicDnsProviders[$dns_address];
                echo "<div style='margin: 5px 0; display: flex; align-items: center;'>";
                echo "<i class='{$provider['icon']}' style='margin-right: 10px; font-size: 1.2em;'></i>";
                echo "<span><strong>{$provider['name']}</strong><br>";
                echo "<small style='color: #a8a8a8;'>{$dns_address}</small></span>";
                echo "</div>";
                $providerFound = true;
            } else {
                // Cek pola untuk ISP DNS
                foreach ($ispDnsPatterns as $pattern => $ispInfo) {
                    if (preg_match($pattern, $dns_address)) {
                        echo "<div style='margin: 5px 0; display: flex; align-items: center;'>";
                        echo "<i class='{$ispInfo['icon']}' style='margin-right: 10px; font-size: 1.2em;'></i>";
                        echo "<span><strong>{$ispInfo['name']}</strong><br>";
                        echo "<small style='color: #a8a8a8;'>{$dns_address}</small></span>";
                        echo "</div>";
                        $providerFound = true;
                        break;
                    }
                }
                
                // Jika tidak ditemukan di kedua database
                if (!$providerFound) {
                    echo "<div style='margin: 5px 0; display: flex; align-items: center;'>";
                    echo "<i class='fas fa-question-circle' style='margin-right: 10px; font-size: 1.2em; color: #ffd93d;'></i>";
                    echo "<span><strong>Unknown DNS</strong><br>";
                    echo "<small style='color: #a8a8a8;'>{$dns_address}</small></span>";
                    echo "</div>";
                }
            }
            $displayedDNS[] = $dns_address; // Tambahkan ke daftar DNS yang sudah ditampilkan
        }
    }
    echo "    </p>";
    echo "</div>";

    $checkClient = shell_exec('dumpsys wifi | grep "Client"');
    
    // Fungsi untuk mendapatkan perangkat terhubung
    function getConnectedDevices() {
        $output = shell_exec("cat /proc/net/arp | awk '{print $1}' | tail -n +2");
        $devices = explode("\n", trim($output));
        $activeDevices = 0;
        
        // Batasi maksimal ping untuk efisiensi
        $devices = array_slice($devices, 0, 10);
        
        foreach ($devices as $ip) {
            if (empty($ip)) continue;
            $pingResult = shell_exec("ping -c 1 -W 1 $ip 2>/dev/null");
            if (strpos($pingResult, "1 received") !== false) {
                $activeDevices++;
            }
        }

        return $activeDevices;
    }

    // Regex to extract the number of connected devices
    preg_match('/\.size\(\): (\d+)/', $checkClient, $matches);
    
    echo "<div class='card-01'>";
    echo "    <i class='fas fa-users'></i>"; // Icon for connected devices
    echo "    <h3>Device Connected</h3>";
    echo "    <p>";
    
    // Coba dapatkan jumlah perangkat dari dumpsys wifi
    if (isset($matches[1])) {
        $connectedClients = $matches[1];
        echo "$connectedClients Device's";
    } else {
        // Jika dumpsys wifi tidak berhasil, gunakan metode alternatif
        $connectedDevices = getConnectedDevices();
        if ($connectedDevices > 0) {
            echo "$connectedDevices Device's";
        } else {
            echo "No Device Connected";
        }
    }
    echo "    </p>";
    echo "</div>";
    echo '    </div>';
    echo "</div>";
}


// Function: cpu
function cpu() {
    $cpu_info = shell_exec('cat /proc/cpuinfo | grep -i "^model name" | awk -F": " \'{print $2}\' | head -1 | sed \'s/ \+/ /g\'');
    $cpu_freq = shell_exec('cat /proc/cpuinfo | grep -i "^cpu MHz" | awk -F": " \'{print $2}\' | head -1');
    $cpu_freq = intval(trim($cpu_freq));

    if (empty($cpu_freq)) {
        $cpu_freq = shell_exec('cat /sys/devices/system/cpu/cpu0/cpufreq/cpuinfo_max_freq');
        $cpu_freq = intval(trim($cpu_freq)) / 1000;
    }

    $cpu_cache = shell_exec('cat /proc/cpuinfo | grep -i "^cache size" | awk -F": " \'{print $2}\' | head -1');
    $cpu_bogomips = shell_exec('cat /proc/cpuinfo | grep -i "^bogomips" | awk -F": " \'{print $2}\' | head -1');
    
echo '<div class="container">';
echo '  <div class="row">';
echo '<div class="card-01">';
echo '<i class="fas fa-microchip"></i>'; // Add Font Awesome icon for CPU
echo '<h3>CPU Model</h3>';
echo '<p>' . htmlspecialchars($cpu_info) . '</p>';
echo '</div>';

echo '<div class="card-01">';
echo '<i class="fas fa-tachometer-alt"></i>'; // Add Font Awesome icon for Frequency
echo '<h3>CPU Frequency</h3>';
echo '<p>' . htmlspecialchars($cpu_freq) . ' MHz</p>';
echo '</div>';

echo '<div class="card-01">';
echo '<i class="fas fa-gear"></i>'; // Add Font Awesome icon for Bogomips
echo '<h3>CPU Bogomips</h3>';
echo '<p>' . htmlspecialchars($cpu_bogomips) . '</p>';
echo '</div>';
echo '</div>';
echo '</div>';

}

// Function: swap
function swap() {
    $swap_total_kb = shell_exec('cat /proc/meminfo | grep SwapTotal | awk \'{print $2}\'');
    $swap_total_gb = intval(trim($swap_total_kb)) / 1024 / 1024; // Convert to GB
    $swap_total_gb_rounded = round($swap_total_gb);

    $swap_free_kb = shell_exec('cat /proc/meminfo | grep SwapFree | awk \'{print $2}\'');
    $swap_free_gb = intval(trim($swap_free_kb)) / 1024 / 1024; // Convert to GB
    $swap_free_gb_rounded = round($swap_free_gb);

    if ($swap_total_gb_rounded > 0) {
        $swap_used_gb = $swap_total_gb_rounded - $swap_free_gb_rounded;
        $swap_used_percent = round(($swap_used_gb / $swap_total_gb_rounded) * 100);

echo "<div class='container'>";
echo '  <div class="row">';
// Card for Total Swap
echo "<div class='card-01'>";
echo "    <i class='fas fa-memory'></i>"; // Icon for memory or swap
echo "    <h3>Total Swap</h3>";
echo "    <p>";
if (isset($swap_total_gb_rounded)) {
    echo "$swap_total_gb_rounded GB";
} else {
    echo "Not Available";
}
echo "    </p>";
echo "</div>";

// Card for Used Swap
echo "<div class='card-01'>";
echo "    <i class='fas fa-tachometer-alt'></i>"; // Icon for usage or swap
echo "    <h3>Used Swap</h3>";
echo "    <p>";
if (isset($swap_used_gb)) {
    echo "$swap_used_gb GB ($swap_used_percent%)";
} else {
    echo "Not Available";
}
echo "    </p>";
echo "</div>";
echo "</div>";
echo "</div>";

    }
}

function disk_usage() {
    // Hanya cek penyimpanan internal
    $storage_path = '/storage/emulated/0';
    
    echo "<div class='container'>"; 
    echo '  <div class="row">';
    
    $output = shell_exec("df -h '$storage_path' 2>/dev/null | grep -v Filesystem");
    if (!empty($output)) {
        $parts = preg_split('/\s+/', trim($output));
        if (count($parts) >= 6) {
            $size = $parts[1];
            $used = $parts[2];
            $available = $parts[3];
            $used_percent = $parts[4];
            
            // Tampilkan informasi dalam card
            echo "<div class='card-01'>";
            echo "    <i class='fas fa-hdd'></i>";
            echo "    <div class='card-content'>";
            echo "        <h3>Internal Storage</h3>";
            echo "        <p>Total: $size<br>";
            echo "        Used: $used ($used_percent)<br>";
            echo "        Free: $available</p>";
            echo "    </div>";
            echo "</div>";
        }
    } else {
        echo "<div class='card-01'>";
        echo "    <i class='fas fa-hdd'></i>";
        echo "    <div class='card-content'>";
        echo "        <h3>Internal Storage</h3>";
        echo "        <p>Information Not Available</p>";
        echo "    </div>";
        echo "</div>";
    }
    
    echo "</div>";
    echo "</div>";
}

// Fungsi untuk mendapatkan band LTE
function getLteBand() {
    // Array hasil akhir yang akan dikembalikan
    $result = [];
    
    // Array untuk memetakan nomor band ke informasi frekuensi
    $bandMap = [
        '1' => ['band' => 'Band 1', 'freq' => '2100 MHz', 'provider' => 'FDD'],
        '2' => ['band' => 'Band 2', 'freq' => '1900 MHz', 'provider' => 'FDD'],
        '3' => ['band' => 'Band 3', 'freq' => '1800 MHz', 'provider' => 'FDD'],
        '4' => ['band' => 'Band 4', 'freq' => '1700/2100 MHz', 'provider' => 'FDD'],
        '5' => ['band' => 'Band 5', 'freq' => '850 MHz', 'provider' => 'FDD'],
        '7' => ['band' => 'Band 7', 'freq' => '2600 MHz', 'provider' => 'FDD'],
        '8' => ['band' => 'Band 8', 'freq' => '900 MHz', 'provider' => 'FDD'],
        '11' => ['band' => 'Band 11', 'freq' => '1500 MHz', 'provider' => 'FDD'],
        '20' => ['band' => 'Band 20', 'freq' => '800 MHz', 'provider' => 'FDD'],
        '28' => ['band' => 'Band 28', 'freq' => '700 MHz', 'provider' => 'FDD'],
        '38' => ['band' => 'Band 38', 'freq' => '2600 MHz', 'provider' => 'TDD'],
        '40' => ['band' => 'Band 40', 'freq' => '2300 MHz', 'provider' => 'TDD'],
        '41' => ['band' => 'Band 41', 'freq' => '2500 MHz', 'provider' => 'TDD']
    ];
    
    // METODE 1: Direct from getprop
    $possibleProps = [
        'gsm.baseband.channel',
        'gsm.network.type',
        'gsm.sim.operator.numeric',
        'ril.lte.caid',
        'ril.nw.band',
        'gsm.ril.ecc',
        'ril.ecclist',
        'ro.telephony.default_network'
    ];
    
    foreach ($possibleProps as $prop) {
        $propValue = trim(shell_exec("getprop $prop 2>/dev/null"));
        if (!empty($propValue)) {
            if (preg_match('/([1-9][0-9]?)/', $propValue, $matches)) {
                $bandNum = $matches[1];
                if (isset($bandMap[$bandNum])) {
                    $result[] = $bandMap[$bandNum];
                    return $result;
                }
            }
        }
    }
    
    // METODE 2: Dari dumpsys
    $output = shell_exec("dumpsys telephony.registry 2>/dev/null");
    if (preg_match('/earfcn\s*=\s*(\d+)/i', $output, $matches)) {
        $earfcn = (int)$matches[1];
        if ($earfcn >= 0 && $earfcn <= 599) {
            $result[] = $bandMap['1'];
            return $result;
        } elseif ($earfcn >= 1200 && $earfcn <= 1949) {
            $result[] = $bandMap['3'];
            return $result;
        } elseif ($earfcn >= 3450 && $earfcn <= 3799) {
            $result[] = $bandMap['8'];
            return $result;
        } elseif ($earfcn >= 38650 && $earfcn <= 39649) {
            $result[] = $bandMap['40'];
            return $result;
        }
    }
    
    // METODE 3: Deteksi berdasarkan provider
    $provider = trim(shell_exec("getprop gsm.sim.operator.alpha 2>/dev/null"));
    $networkType = trim(shell_exec("getprop gsm.network.type 2>/dev/null"));
    
    if (preg_match('/lte|4g/i', $networkType)) {
        if (preg_match('/telkomsel/i', $provider)) {
            return [$bandMap['3'], $bandMap['1']];
        } elseif (preg_match('/xl|axis/i', $provider)) {
            return [$bandMap['3'], $bandMap['1']];
        } elseif (preg_match('/indosat|ooredoo|im3/i', $provider)) {
            return [$bandMap['3'], $bandMap['1']];
        } elseif (preg_match('/tri|3/i', $provider)) {
            return [$bandMap['3'], $bandMap['1']];
        } elseif (preg_match('/smartfren/i', $provider)) {
            return [$bandMap['40']];
        }
    }
    
    // Default untuk Indonesia
    $mcc = trim(shell_exec("getprop gsm.sim.operator.numeric 2>/dev/null"));
    if (strpos($mcc, '510') === 0) {
        return [$bandMap['3'], $bandMap['1']];
    }
    
    $result[] = [
        'band' => 'Unknown',
        'freq' => 'Unknown',
        'provider' => $provider ?: 'Unknown'
    ];
    
    return $result;
}

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Info</title>
    <!--<link rel="stylesheet" href="style.css"-->
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
      * {
        box-sizing: border-box;
      }
  
      body {
        font-family: "Open Sans", sans-serif;
        background: #080808;
        color: white;
        text-align: center;
        margin: 0;
        padding: 0;
      }
      .logo {
        text-align: center;
        margin: 10px auto;
      }
      .logo img {
        width: 200px; /* Adjust width to make the logo smaller */
        height: auto;
      }
      #main {
        position: relative;
        list-style: none;
        background: #080808;
        font-weight: 400;
        font-size: 0;
        text-transform: uppercase;
        display: inline-block;
        padding: 0;
        margin: 1px auto;
        height: 55px; /* Adjust height to match tab height */
      }
  
      #main li {
        font-size: 0.8rem;
        display: inline-block;
        position: relative;
        padding: 15px 20px;
        cursor: pointer;
        z-index: 5;
        min-width: 120px;
        height: 100%; /* Make sure li items take full height */
        line-height: 32px; /* Vertically center text in the li items */
        margin: 0;
      }
  
      .drop {
        overflow: hidden;
        list-style: none;
        position: absolute;
        padding: 0;
        width: 100%;
        left: 0;
        top: 60px; /* Position below the nav bar */
      }
  
      .drop div {
        -webkit-transform: translate(0, -100%);
        -moz-transform: translate(0, -100%);
        -ms-transform: translate(0, -100%);
        transform: translate(0, -100%);
        -webkit-transition: all 0.5s 0.1s;
        -moz-transition: all 0.5s 0.1s;
        -ms-transition: all 0.5s 0.1s;
        transition: all 0.5s 0.1s;
        position: relative;
      }
  
      .drop li {
        display: block;
        padding: 0;
        width: 100%;
        background: #374954 !important;
      }
  
      #marker {
        height: 6px;
        background: #3E8760 !important;
        position: absolute;
        bottom: 0;
        width: 120px;
        z-index: 2;
        -webkit-transition: all 0.35s;
        -moz-transition: all 0.35s;
        -ms-transition: all 0.35s;
        transition: all 0.35s;
      }
  
      #main li:nth-child(1):hover ul div {
        -webkit-transform: translate(0, 0);
        -moz-transform: translate(0, 0);
        -ms-transform: translate(0, 0);
        transform: translate(0, 0);
      }
  
      #main li:nth-child(1):hover ~ #marker {
        -webkit-transform: translate(0px, 0);
        -moz-transform: translate(0px, 0);
        -ms-transform: translate(0px, 0);
        transform: translate(0px, 0);
      }
  
      #main li:nth-child(2):hover ul div {
        -webkit-transform: translate(0, 0);
        -moz-transform: translate(0, 0);
        -ms-transform: translate(0, 0);
        transform: translate(0, 0);
      }
  
      #main li:nth-child(2):hover ~ #marker {
        -webkit-transform: translate(120px, 0);
        -moz-transform: translate(120px, 0);
        -ms-transform: translate(120px, 0);
        transform: translate(120px, 0);
      }
  
      #main li:nth-child(3):hover ul div {
        -webkit-transform: translate(0, 0);
        -moz-transform: translate(0, 0);
        -ms-transform: translate(0, 0);
        transform: translate(0, 0);
      }
  
      #main li:nth-child(3):hover ~ #marker {
        -webkit-transform: translate(240px, 0);
        -moz-transform: translate(240px, 0);
        -ms-transform: translate(240px, 0);
        transform: translate(240px, 0);
      }
  
      #main li:nth-child(4):hover ul div {
        -webkit-transform: translate(0, 0);
        -moz-transform: translate(0, 0);
        -ms-transform: translate(0, 0);
        transform: translate(0, 0);
      }
  
      #main li:nth-child(4):hover ~ #marker {
        -webkit-transform: translate(360px, 0);
        -moz-transform: translate(360px, 0);
        -ms-transform: translate(360px, 0);
        transform: translate(360px, 0);
      }
  
      .tab-content {
        display: none;
        width: 100%;
        
      }
  
      .tab-content iframe {
        width: 100%;
        height: calc(100vh - 60px); /* Adjust based on header/footer height */
        border: none;
      }
      

      /* card */
      body{
  margin: 0;
  padding: 0;
  height: 100vh;
}

.container{
  margin: 20px;
}

.row{
  width: 100%;
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
}

.card-000{
  background: #0a0e0d;
  text-align: center;
  position: relative;
  padding: 20px;
  align-items: center;
  flex: 1;
  max-width: 500px;
  height: 150px;
  margin: 10px;
  border-radius: 5px;
  box-shadow: 0 0 5px rgba(94, 89, 89, 0.2);
}

.card-00{
  background: #0a0e0d;
  text-align: center;
  position: relative;
  padding: 20px;
  align-items: center;
  flex: 1;
  max-width: 300px;
  height: 150px;
  margin: 10px;
  border-radius: 5px;
  box-shadow: 0 0 5px rgba(94, 89, 89, 0.2);
}

.card-01{
  background: #03D29F;
  text-align: center;
  align-items: center;
  position: relative;
  padding: 20px;
  flex: 1;
  max-width: 300px;
  height: 150px;
  margin: 10px;
  border-radius: 5px;
  box-shadow: 0 0 5px rgba(94, 89, 89, 0.2);
}

.card-02{
  background: #0470ddd0;
  text-align: center;
  position: relative;
    padding: 20px;
  flex: 1;
  max-width: 460px;
  height: 200px;
  margin: 10px;
  border-radius: 5px;
  box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
}

.card-03{
  background: #FF7675;
  position: relative;
  padding: 20px;
  flex: 1;
  max-width: 940px;
  height: 300px;
  margin: 10px;
  border-radius: 5px;
  box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
}

@media (max-width:800px){

  .card-00{
    flex: 100%;
    max-width: 600px;
  }
  .card-01{
    flex: 100%;
    max-width: 600px;
  }

  .card-02{
    flex: 100%;
    max-width: 600px;
  }

  .card-03{
    flex: 100%;
    max-width: 600px;
  }
}

    </style>
  </head>

  <body>
    <div class="logo">
      <img src="../webui/assets/img/logo.png" alt="Logo">
  </div>
  
  <ul id="main">
      <li onclick="showTab('device')">System</li>
      <li onclick="showTab('battery')">Battery</li>
      <li onclick="showTab('network')">Networks</li>
      <!--<li onclick="toggleSubmenu()">Config
          <ul class="drop">
              <div id="config">
                  <li onclick="showTab('clash')">Clash</li>
                  <li onclick="showTab('sing-box')">Sing-Box</li>
              </div>
          </ul>
      </li>-->
      <li onclick="showTab('cpu')">CPU</li>
      <li onclick="showTab('disk')">DISK INFO</li>
      <div id="marker"></div>
  </ul>
  
  <div id="device" class="tab-content">
    <table>
        <?php systemInfo(); ?>
    </table>

  </div>
  <div id="battery" class="tab-content">
    <table>
        <?php battery(); ?>
    </table>
  </div>
  <div id="network" class="tab-content">
  <table>
                    <?php network(); checkSignal(); ?>
                </table>
  </div>
  <div id="cpu" class="tab-content">
  <?php
    cpu();
    load_average();
    ?>
  </div>
  <div id="disk" class="tab-content">
  <?php
    swap();
    memory();
    disk_usage();
    ?>
  </div>

    <script>
      function showTab(tabId) {
    // Hide all tab contents
    var tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(function(content) {
        content.style.display = 'none';
    });

    // Show the selected tab content
    var selectedTab = document.getElementById(tabId);
    selectedTab.style.display = 'block';
}

function toggleSubmenu() {
    var submenu = document.getElementById('config');
    if (submenu.style.display === 'block') {
        submenu.style.display = 'none';
    } else {
        submenu.style.display = 'block';
    }
}

// Initial tab setup: show the first tab
showTab('device');

    </script>

  </body>
</html>
