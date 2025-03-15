<?php
// Initialize variables for pre-checking
$checked_wifi = $checked_cell = $checked_bluetooth = false;
$network_choice = 'hotspot'; // Default value
$airplane_mode_enabled = false;

// Detect current state of radio settings
$current_radios = shell_exec("su -c 'settings get global airplane_mode_radios'");
$current_radios = explode(',', trim($current_radios));

$checked_cell = in_array('cell', $current_radios);
$checked_bluetooth = in_array('bluetooth', $current_radios);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] === 'disable_airplane_mode') {
        // Disable airplane mode
        shell_exec("su -c 'settings put global airplane_mode_on 0'");
        shell_exec("su -c 'am broadcast -a android.intent.action.AIRPLANE_MODE --ez state false'");

        // Ensure radios are still enabled based on previous settings
        $enabled_radios = isset($_POST['enabled_radios']) ? json_decode($_POST['enabled_radios'], true) : [];
        $radios_str = implode(',', $enabled_radios);
        shell_exec("su -c 'settings put global airplane_mode_radios \"$radios_str\"'");

        echo "<p class='green-text'>Mode pesawat dinonaktifkan.</p>";
        $airplane_mode_enabled = false;
    } elseif (isset($_POST['action']) && $_POST['action'] === 'enable_airplane_mode') {
        $enabled_radios = [];

        // Collect selected radios
        if (isset($_POST['cell'])) {
            $enabled_radios[] = 'cell';
            $checked_cell = true;
        }
        if (isset($_POST['bluetooth'])) {
            $enabled_radios[] = 'bluetooth';
            $checked_bluetooth = true;
        }

        $radios_str = implode(',', $enabled_radios);

        // Collect choice for WiFi or Hotspot
        $network_choice = $_POST['network_choice'] ?? 'hotspot'; // Default to hotspot

        // Whitelist hardware radios to stay on
        shell_exec("su -c 'settings put global airplane_mode_radios \"$radios_str\"'");

        // Enable airplane mode
        shell_exec("su -c 'settings put global airplane_mode_on 1'");
        shell_exec("su -c 'am broadcast -a android.intent.action.AIRPLANE_MODE --ez state true'");

        // Handle network choice
        if ($network_choice === 'wifi') {
            // Enable WiFi only
            shell_exec("su -c 'svc wifi enable'");
            shell_exec("su -c 'svc wifi sethotspotenabled false'"); // Disable hotspot
        } elseif ($network_choice === 'hotspot') {
            // Enable hotspot only
            shell_exec("su -c 'svc wifi sethotspotenabled true'");
            shell_exec("su -c 'svc wifi disable'"); // set enable to Ensure WiFi is on
        }

        echo "<p class='green-text'>Mode pesawat diaktifkan dengan radio yang dipilih. Pilihan jaringan: $network_choice.</p>";

        // Automatically turn off airplane mode after 5 seconds
        echo "<script>
                setTimeout(function() {
                    var xhttp = new XMLHttpRequest();
                    xhttp.open('POST', '', true);
                    xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                    xhttp.send('action=disable_airplane_mode&enabled_radios=" . urlencode(json_encode($enabled_radios)) . "');
                }, 5000);
              </script>";
        
        $airplane_mode_enabled = true;
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_radios') {
        // Update individual radios based on the user's choice
        if (isset($_POST['bluetooth_control'])) {
            shell_exec("su -c 'svc bluetooth enable'");
        } else {
            shell_exec("su -c 'svc bluetooth disable'");
        }

        if (isset($_POST['wifi_control'])) {
            shell_exec("su -c 'svc wifi enable'");
        } else {
            shell_exec("su -c 'svc wifi disable'");
        }

        echo "<p class='green-text'>Pengaturan radio diperbarui.</p>";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'lock_band') {
        // Lock band menggunakan berbagai metode Qualcomm
        if (isset($_POST['band_selection']) && !empty($_POST['band_selection'])) {
            $band = $_POST['band_selection'];
            $command_type = $_POST['command_type'] ?? 'standard';
            $network_pref = $_POST['network_preference'] ?? '4g';
            $debug_output = "";
            
            // Set preferensi jaringan jika dipilih
            if (isset($_POST['set_network_pref']) && $network_pref) {
                $network_value = 0;
                switch ($network_pref) {
                    case '5g':
                        $network_value = 26; // NR/LTE/TDSCDMA/CDMA/EvDo/GSM/WCDMA
                        break;
                    case '5g_standalone':
                        $network_value = 27; // NR only
                        break;
                    case '4g':
                        $network_value = 9; // LTE/WCDMA/GSM auto
                        break;
                    case '4g_only':
                        $network_value = 11; // LTE only
                        break;
                    case '3g':
                        $network_value = 0; // WCDMA preferred
                        break;
                    case '3g_only':
                        $network_value = 2; // WCDMA only
                        break;
                    case '2g':
                        $network_value = 1; // GSM only
                        break;
                }
                shell_exec("su -c 'settings put global preferred_network_mode $network_value'");
                shell_exec("su -c 'settings put global preferred_network_mode1 $network_value'"); // Untuk slot SIM 1
                shell_exec("su -c 'settings put global preferred_network_mode2 $network_value'"); // Untuk slot SIM 2
                $debug_output .= "Set network preference: $network_pref (value: $network_value)\n";
            }
            
            // Terapkan force NSA/SA mode jika dipilih
            if (isset($_POST['force_5g_mode']) && !empty($_POST['force_5g_mode'])) {
                $mode_5g = $_POST['force_5g_mode'];
                switch ($mode_5g) {
                    case 'nsa':
                        shell_exec("su -c 'settings put global nr_nsa_allowed_networks 15'");
                        shell_exec("su -c 'settings put global nr_sa_allowed_networks 0'");
                        $debug_output .= "Force 5G NSA mode enabled\n";
                        break;
                    case 'sa':
                        shell_exec("su -c 'settings put global nr_nsa_allowed_networks 0'");
                        shell_exec("su -c 'settings put global nr_sa_allowed_networks 15'");
                        $debug_output .= "Force 5G SA mode enabled\n";
                        break;
                    case 'both':
                        shell_exec("su -c 'settings put global nr_nsa_allowed_networks 15'");
                        shell_exec("su -c 'settings put global nr_sa_allowed_networks 15'");
                        $debug_output .= "Both 5G NSA and SA modes enabled\n";
                        break;
                }
            }
            
            // Lock band berdasarkan tipe
            $is_nr_band = strpos($band, 'n') === 0;
            $band_number = $is_nr_band ? substr($band, 1) : $band;
            
            // Lock band berdasarkan provider jika dipilih
            if (isset($_POST['provider_preset']) && !empty($_POST['provider_preset'])) {
                $provider = $_POST['provider_preset'];
                $band_values = [];
                
                switch ($provider) {
                    case 'telkomsel':
                        $band_values = ['3', '8', '40']; // Contoh band Telkomsel
                        break;
                    case 'xl':
                        $band_values = ['1', '3', '8', '40']; // Contoh band XL
                        break;
                    case 'indosat':
                        $band_values = ['1', '3', '8']; // Contoh band Indosat
                        break;
                    case 'tri':
                        $band_values = ['1', '8', '40']; // Contoh band Tri
                        break;
                    case 'smartfren':
                        $band_values = ['5', '40', 'n40']; // Contoh band Smartfren
                        break;
                }
                
                if (!empty($band_values)) {
                    $band = implode(',', $band_values);
                    $command_type = 'multiple_bands';
                    $debug_output .= "Menggunakan preset provider: $provider, bands: $band\n";
                }
            }
            
            // Set EARFCN/ARFCN jika dipilih
            if (isset($_POST['set_earfcn']) && !empty($_POST['earfcn_value'])) {
                $earfcn = $_POST['earfcn_value'];
                
                if ($is_nr_band) {
                    $result_earfcn = shell_exec("su -c 'service call qcrilhook 35 i32 1 i32 $earfcn'");
                    $debug_output .= "Set NR-ARFCN to $earfcn: $result_earfcn\n";
                } else {
                    $result_earfcn = shell_exec("su -c 'service call phone 29 i32 1 i32 $earfcn'");
                    $debug_output .= "Set EARFCN to $earfcn: $result_earfcn\n";
                }
            }
            
            // Lock ke Cell ID tertentu jika dipilih
            if (isset($_POST['lock_cell_id']) && !empty($_POST['cell_id_value'])) {
                $cell_id = $_POST['cell_id_value'];
                $result_cell = shell_exec("su -c 'service call phone 28 i32 1 i32 $cell_id'");
                $debug_output .= "Lock to Cell ID $cell_id: $result_cell\n";
            }
            
            // Lock band dengan metode yang dipilih
            switch ($command_type) {
                case 'standard':
                    if ($is_nr_band) {
                        $result = shell_exec("su -c 'service call phone 27 i32 5 i32 $band_number'");
                    } else {
                        $result = shell_exec("su -c 'service call phone 27 i32 1 i32 $band_number'");
                    }
                    $debug_output .= "Coba perintah: service call phone 27 i32 " . ($is_nr_band ? "5" : "1") . " i32 $band_number\n";
                    break;
                
                case 'qcrilhook':
                    if ($is_nr_band) {
                        $result = shell_exec("su -c 'service call qcrilhook 33 i32 1 i32 $band_number'");
                    } else {
                        $result = shell_exec("su -c 'service call qcrilhook 31 i32 1 i32 $band_number'");
                    }
                    $debug_output .= "Coba perintah: service call qcrilhook " . ($is_nr_band ? "33" : "31") . " i32 1 i32 $band_number\n";
                    break;
                
                case 'qcrilnr':
                    $result = shell_exec("su -c 'service call qcrilnr 1 i32 1 i32 $band_number'");
                    $debug_output .= "Coba perintah: service call qcrilnr 1 i32 1 i32 $band_number\n";
                    break;
                
                case 'alternative':
                    $result = shell_exec("su -c 'service call phone 27 i32 3 i32 $band_number'");
                    $debug_output .= "Coba perintah: service call phone 27 i32 3 i32 $band_number\n";
                    break;
                
                case 'netmgr':
                    $result = shell_exec("su -c 'service call netmgr 3 i32 1 i32 $band_number'");
                    $debug_output .= "Coba perintah: service call netmgr 3 i32 1 i32 $band_number\n";
                    break;
                
                case 'atcommand':
                    if ($is_nr_band) {
                        $at_command = "AT+QNWPREFCFG=\"nr5g_band\",$band_number";
                    } else {
                        $at_command = "AT+QNWPREFCFG=\"lte_band\",$band_number";
                    }
                    $result = shell_exec("su -c 'echo \"$at_command\" > /dev/smd11'");
                    $debug_output .= "Coba perintah AT: $at_command via /dev/smd11\n";
                    
                    // Coba di port alternatif
                    shell_exec("su -c 'echo \"$at_command\" > /dev/ttyUSB0'");
                    $debug_output .= "Coba juga via /dev/ttyUSB0\n";
                    
                    // Coba juga untuk modem lain
                    shell_exec("su -c 'echo \"$at_command\" > /dev/ttyUSB2'");
                    $debug_output .= "Coba juga via /dev/ttyUSB2\n";
                    break;
                
                case 'multiple_bands':
                    // Untuk lock multiple bands (hanya bekerja di beberapa perangkat)
                    $bands = explode(',', $band);
                    
                    // Pisahkan 4G dan 5G bands
                    $lte_bands = [];
                    $nr_bands = [];
                    
                    foreach ($bands as $b) {
                        if (strpos($b, 'n') === 0) {
                            $nr_bands[] = substr($b, 1);
                        } else {
                            $lte_bands[] = $b;
                        }
                    }
                    
                    // Lock 4G bands jika ada
                    if (!empty($lte_bands)) {
                        // Gunakan bitmask untuk multiple bands
                        $band_mask = 0;
                        foreach ($lte_bands as $b) {
                            $band_mask |= (1 << ((int)$b - 1));
                        }
                        
                        $result = shell_exec("su -c 'service call phone 27 i32 2 i64 $band_mask'");
                        $debug_output .= "Lock multiple 4G bands (" . implode(",", $lte_bands) . ") with mask: $band_mask\n";
                        
                        // Coba juga dengan qcrilhook untuk beberapa perangkat
                        shell_exec("su -c 'service call qcrilhook 31 i32 2 i64 $band_mask'");
                        $debug_output .= "Juga coba dengan qcrilhook untuk 4G bands\n";
                    }
                    
                    // Lock 5G bands jika ada
                    if (!empty($nr_bands)) {
                        // Gunakan bitmask untuk multiple bands 5G
                        $nr_mask = 0;
                        foreach ($nr_bands as $b) {
                            $nr_mask |= (1 << ((int)$b - 1));
                        }
                        
                        $result2 = shell_exec("su -c 'service call qcrilhook 33 i32 2 i64 $nr_mask'");
                        $debug_output .= "Lock multiple 5G bands (" . implode(",", $nr_bands) . ") with mask: $nr_mask\n";
                        $result .= $result2;
                    }
                    break;
                
                case 'direct_inject':
                    // Metode direct inject dengan memperbarui file konfigurasi modem (teknik tingkat lanjut)
                    $target_path = "/data/vendor/modem_config/mcfg_sw.mbn";
                    
                    // Backup file asli jika belum ada
                    if (!file_exists("/data/vendor/modem_config/mcfg_sw.mbn.bak")) {
                        shell_exec("su -c 'cp $target_path $target_path.bak'");
                        $debug_output .= "Backup file modem konfigurasi asli\n";
                    }
                    
                    // Mencoba membuat perubahan ke file konfigurasi (ini adalah contoh, modifikasi sesuai kebutuhan)
                    $hex_band = dechex(1 << ((int)$band_number - 1));
                    $result = shell_exec("su -c 'echo \"$hex_band\" > /data/local/tmp/band_value.hex'");
                    $debug_output .= "Menggunakan metode direct inject (eksperimental)\n";
                    
                    $debug_output .= "PERINGATAN: Metode ini eksperimental dan bisa menyebabkan masalah modem\n";
                    break;
                
                case 'mediatek':
                    // Khusus untuk perangkat MediaTek
                    $result = shell_exec("su -c 'echo \"AT+CLTE=1,$band_number\" > /dev/radio/pttycmd1'");
                    $debug_output .= "Mencoba lock band dengan perintah MediaTek\n";
                    break;
            }
            
            // Simpan konfigurasi ke dalam file
            $config = [
                'band' => $band,
                'command_type' => $command_type,
                'network_pref' => $network_pref,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            file_put_contents('/data/local/tmp/band_config.json', json_encode($config));
            $debug_output .= "Konfigurasi disimpan ke /data/local/tmp/band_config.json\n";
            
            // Cek status band saat ini (hanya informatif)
            $current_band = shell_exec("su -c 'service call phone 27 i32 4 i32 0'");
            $debug_output .= "Cek status band: $current_band\n";
            
            // Informasi tambahan tentang sinyal
            $signal_info = shell_exec("su -c 'dumpsys telephony.registry | grep -E \"mSignalStrength|mDataNetworkType\"'");
            $debug_output .= "Informasi sinyal saat ini:\n$signal_info\n";
            
            // Restart radio sebagai upaya tambahan
            if (isset($_POST['restart_radio']) && $_POST['restart_radio'] == 1) {
                shell_exec("su -c 'svc data disable'");
                sleep(1);
                shell_exec("su -c 'svc data enable'");
                $debug_output .= "Radio direstart untuk menerapkan perubahan\n";
            }
            
            // Aktifkan monitoring jika dipilih
            if (isset($_POST['enable_monitoring']) && $_POST['enable_monitoring'] == 1) {
                // Buat file marker untuk monitoring
                shell_exec("su -c 'touch /data/local/tmp/band_monitor.flag'");
                
                // Tambahkan perintah untuk memulai monitoring di latar belakang
                shell_exec("su -c 'nohup sh -c \"while [ -f /data/local/tmp/band_monitor.flag ]; do dumpsys telephony.registry | grep -E \\\"mSignalStrength|mDataNetworkType\\\" >> /data/local/tmp/signal_monitor.log; sleep 5; done\" > /dev/null 2>&1 &'");
                
                $debug_output .= "Monitoring sinyal diaktifkan. Log disimpan di /data/local/tmp/signal_monitor.log\n";
            }
            
            echo "<p class='green-text'>Band $band dicoba dikunci dengan metode: $command_type.</p>";
            echo "<p class='blue-text'>Informasi Debug:<br><pre>" . htmlspecialchars($debug_output) . "</pre></p>";
            echo "<p class='orange-text'>Hasil eksekusi:<br><pre>" . htmlspecialchars($result ?? "Tidak ada output") . "</pre></p>";
        } else {
            echo "<p class='red-text'>Silakan pilih band terlebih dahulu!</p>";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'unlock_band') {
        // Unlock all bands with various methods
        $command_type = $_POST['command_type'] ?? 'standard';
        $debug_output = "";
        
        switch ($command_type) {
            case 'standard':
                $result = shell_exec("su -c 'service call phone 27 i32 0 i32 0'");
                $debug_output .= "Coba perintah: service call phone 27 i32 0 i32 0\n";
                break;
            case 'qcrilhook':
                // Unlock 4G bands
                $result = shell_exec("su -c 'service call qcrilhook 31 i32 0 i32 0'");
                $debug_output .= "Coba perintah 4G: service call qcrilhook 31 i32 0 i32 0\n";
                
                // Juga unlock 5G bands
                $result .= shell_exec("su -c 'service call qcrilhook 33 i32 0 i32 0'");
                $debug_output .= "Coba perintah 5G: service call qcrilhook 33 i32 0 i32 0\n";
                break;
            case 'qcrilnr':
                $result = shell_exec("su -c 'service call qcrilnr 1 i32 0 i32 0'");
                $debug_output .= "Coba perintah: service call qcrilnr 1 i32 0 i32 0\n";
                break;
            case 'alternative':
                $result = shell_exec("su -c 'service call phone 27 i32 0 i32 0'");
                $debug_output .= "Coba perintah: service call phone 27 i32 0 i32 0\n";
                break;
            case 'netmgr':
                $result = shell_exec("su -c 'service call netmgr 3 i32 0 i32 0'");
                $debug_output .= "Coba perintah: service call netmgr 3 i32 0 i32 0\n";
                break;
            case 'atcommand':
                // Reset LTE bands
                $at_command = "AT+QNWPREFCFG=\"lte_band\",0";
                $result = shell_exec("su -c 'echo \"$at_command\" > /dev/smd11'");
                $debug_output .= "Coba perintah AT LTE: $at_command\n";
                
                // Reset 5G bands
                $at_command = "AT+QNWPREFCFG=\"nr5g_band\",0";
                $result .= shell_exec("su -c 'echo \"$at_command\" > /dev/smd11'");
                $debug_output .= "Coba perintah AT 5G: $at_command\n";
                
                // Coba di port alternatif
                shell_exec("su -c 'echo \"AT+QNWPREFCFG=\\\"lte_band\\\",0\" > /dev/ttyUSB0'");
                shell_exec("su -c 'echo \"AT+QNWPREFCFG=\\\"nr5g_band\\\",0\" > /dev/ttyUSB0'");
                $debug_output .= "Coba juga via /dev/ttyUSB0\n";
                
                // Coba juga modem lain
                shell_exec("su -c 'echo \"AT+QNWPREFCFG=\\\"lte_band\\\",0\" > /dev/ttyUSB2'");
                shell_exec("su -c 'echo \"AT+QNWPREFCFG=\\\"nr5g_band\\\",0\" > /dev/ttyUSB2'");
                $debug_output .= "Coba juga via /dev/ttyUSB2\n";
                break;
            case 'multiple_bands':
                // For multiple bands, use bitmask 0 to unlock all
                $result = shell_exec("su -c 'service call phone 27 i32 2 i64 0'");
                $debug_output .= "Coba perintah multiple bands 4G: service call phone 27 i32 2 i64 0\n";
                
                // Unlock 5G bands with mask 0
                $result .= shell_exec("su -c 'service call qcrilhook 33 i32 2 i64 0'");
                $debug_output .= "Coba perintah multiple bands 5G: service call qcrilhook 33 i32 2 i64 0\n";
                break;
            
            case 'direct_inject':
                // Restore original file if we have a backup
                if (file_exists("/data/vendor/modem_config/mcfg_sw.mbn.bak")) {
                    shell_exec("su -c 'cp /data/vendor/modem_config/mcfg_sw.mbn.bak /data/vendor/modem_config/mcfg_sw.mbn'");
                    $debug_output .= "Mengembalikan file konfigurasi modem asli\n";
                }
                break;
            
            case 'mediatek':
                // Reset for MediaTek devices
                $result = shell_exec("su -c 'echo \"AT+CLTE=0,0\" > /dev/radio/pttycmd1'");
                $debug_output .= "Reset band lock untuk perangkat MediaTek\n";
                break;
        }
        
        // Reset pengaturan sistem
        shell_exec("su -c 'settings delete global preferred_network_mode'");
        shell_exec("su -c 'settings delete global preferred_network_mode1'");
        shell_exec("su -c 'settings delete global preferred_network_mode2'");
        $debug_output .= "Reset pengaturan sistem network mode\n";
        
        // Reset 5G mode settings
        shell_exec("su -c 'settings delete global nr_nsa_allowed_networks'");
        shell_exec("su -c 'settings delete global nr_sa_allowed_networks'");
        $debug_output .= "Reset pengaturan 5G mode (NSA/SA)\n";
        
        // Reset EARFCN/ARFCN
        shell_exec("su -c 'service call phone 29 i32 0 i32 0'");
        shell_exec("su -c 'service call qcrilhook 35 i32 0 i32 0'");
        $debug_output .= "Reset EARFCN/NR-ARFCN settings\n";
        
        // Reset Cell ID locking
        shell_exec("su -c 'service call phone 28 i32 0 i32 0'");
        $debug_output .= "Reset Cell ID locking\n";
        
        // Restart radio jika dicentang
        if (isset($_POST['restart_radio']) && $_POST['restart_radio'] == 1) {
            shell_exec("su -c 'svc data disable'");
            sleep(1);
            shell_exec("su -c 'svc data enable'");
            $debug_output .= "Radio direstart untuk menerapkan perubahan\n";
        }
        
        // Hapus file konfigurasi
        shell_exec("su -c 'rm -f /data/local/tmp/band_config.json'");
        $debug_output .= "File konfigurasi dihapus\n";
        
        // Hentikan monitoring jika sedang berjalan
        shell_exec("su -c 'rm -f /data/local/tmp/band_monitor.flag'");
        $debug_output .= "Monitoring sinyal dihentikan (jika aktif)\n";
        
        echo "<p class='green-text'>Band dicoba dibuka dengan metode: $command_type.</p>";
        echo "<p class='blue-text'>Informasi Debug:<br><pre>" . htmlspecialchars($debug_output) . "</pre></p>";
        echo "<p class='orange-text'>Hasil eksekusi:<br><pre>" . htmlspecialchars($result ?? "Tidak ada output") . "</pre></p>";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'get_network_info') {
        // Informasi jaringan saat ini
        $info = [];
        $info['Signal Strength'] = shell_exec("su -c 'dumpsys telephony.registry | grep mSignalStrength'");
        $info['Network Type'] = shell_exec("su -c 'dumpsys telephony.registry | grep mDataNetworkType'");
        $info['Operator'] = shell_exec("su -c 'dumpsys telephony.registry | grep mOperatorAlphaShort'");
        $info['Service State'] = shell_exec("su -c 'dumpsys telephony.registry | grep mServiceState'");
        $info['Cell Info'] = shell_exec("su -c 'dumpsys telephony.registry | grep mCellInfo'");
        
        // Dapatkan info lebih detail
        $info['Serving Cell'] = shell_exec("su -c 'dumpsys telephony.registry | grep -A 10 \"mCellIdentity\"'");
        $info['Band Info'] = shell_exec("su -c 'service call phone 27 i32 4 i32 0'");
        $info['EARFCN'] = shell_exec("su -c 'dumpsys telephony.registry | grep -i earfcn'");
        $info['Current Band Locks'] = shell_exec("su -c 'getprop | grep band'");
        
        // Info modem
        $info['Modem Info'] = shell_exec("su -c 'getprop | grep -E \"gsm.version.baseband|gsm.operator.alpha|gsm.network.type\"'");
        
        // Tambahkan informasi koneksi data
        $info['IP Address'] = shell_exec("su -c 'ip addr show | grep -E \"wlan|rmnet\"'");
        $info['DNS Settings'] = shell_exec("su -c 'getprop | grep dns'");
        
        echo "<div class='card blue-grey darken-1'><div class='card-content white-text'>";
        echo "<span class='card-title'>Informasi Jaringan Lengkap</span>";
        echo "<pre>";
        foreach ($info as $key => $value) {
            echo htmlspecialchars("=== $key ===\n$value\n\n");
        }
        echo "</pre></div></div>";
        
        // Cek log monitoring jika ada
        if (file_exists("/data/local/tmp/signal_monitor.log")) {
            $signal_log = shell_exec("su -c 'tail -n 20 /data/local/tmp/signal_monitor.log'");
            
            echo "<div class='card deep-purple darken-1'><div class='card-content white-text'>";
            echo "<span class='card-title'>Log Monitoring Sinyal (20 entri terakhir)</span>";
            echo "<pre>" . htmlspecialchars($signal_log) . "</pre>";
            echo "</div></div>";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'advanced_modem_control') {
        // Advanced modem control untuk perangkat yang sudah di-root
        $modem_command = $_POST['modem_command'] ?? '';
        $debug_output = "";
        
        switch ($modem_command) {
            case 'reset_modem':
                $result = shell_exec("su -c 'svc data disable && sleep 2 && svc data enable'");
                $debug_output .= "Modem direset dengan menon-aktifkan dan mengaktifkan data\n";
                break;
            
            case 'force_reconnect':
                $result = shell_exec("su -c 'settings put global airplane_mode_on 1'");
                shell_exec("su -c 'am broadcast -a android.intent.action.AIRPLANE_MODE --ez state true'");
                sleep(2);
                $result .= shell_exec("su -c 'settings put global airplane_mode_on 0'");
                shell_exec("su -c 'am broadcast -a android.intent.action.AIRPLANE_MODE --ez state false'");
                $debug_output .= "Force reconnect dengan menggunakan airplane mode\n";
                break;
            
            case 'clear_radio_logs':
                $result = shell_exec("su -c 'rm -f /data/log/radio/* /data/vendor/radio/logs/* /data/vendor/radio/*log*'");
                $debug_output .= "Log radio dibersihkan\n";
                break;
            
            case 'modem_diagnostics':
                $result = "== Modem Info ==\n";
                $result .= shell_exec("su -c 'getprop | grep radio'") . "\n";
                $result .= shell_exec("su -c 'getprop | grep gsm'") . "\n";
                $result .= shell_exec("su -c 'getprop | grep net'") . "\n";
                $result .= "== Signal Info ==\n";
                $result .= shell_exec("su -c 'dumpsys telephony.registry | grep -E \"mSignalStrength|mDataNetworkType|mOperator\"'");
                $debug_output .= "Mengumpulkan informasi diagnostik modem\n";
                break;
            
            case 'set_fast_dormancy':
                $fd_status = isset($_POST['fast_dormancy']) ? 1 : 0;
                $result = shell_exec("su -c 'settings put global cust_fast_dormancy $fd_status'");
                $debug_output .= "Fast dormancy " . ($fd_status ? "diaktifkan" : "dinonaktifkan") . "\n";
                break;
            
            case 'set_data_roaming':
                $roaming_status = isset($_POST['data_roaming']) ? 1 : 0;
                $result = shell_exec("su -c 'settings put global data_roaming $roaming_status'");
                $debug_output .= "Data roaming " . ($roaming_status ? "diaktifkan" : "dinonaktifkan") . "\n";
                break;
            
            case 'network_logging':
                $log_file = "/data/local/tmp/network_log_" . date("Ymd_His") . ".txt";
                $result = "Log dimulai, akan disimpan ke: $log_file\n\n";
                
                // Pengumpulan informasi jaringan
                $result .= "== Network Info ==\n";
                $result .= shell_exec("su -c 'ip addr'") . "\n";
                $result .= shell_exec("su -c 'ip route'") . "\n";
                $result .= "== DNS Info ==\n";
                $result .= shell_exec("su -c 'getprop | grep dns'") . "\n";
                
                // Simpan log
                shell_exec("su -c 'echo \"$result\" > $log_file'");
                $debug_output .= "Log jaringan disimpan ke $log_file\n";
                break;
        }
        
        echo "<p class='green-text'>Perintah modem dijalankan: $modem_command.</p>";
        echo "<p class='blue-text'>Informasi Debug:<br><pre>" . htmlspecialchars($debug_output) . "</pre></p>";
        echo "<p class='orange-text'>Hasil eksekusi:<br><pre>" . htmlspecialchars($result ?? "Tidak ada output") . "</pre></p>";
        
    } elseif (isset($_POST['action']) && $_POST['action'] === 'kernel_tweaks') {
        // Tweaks kernel untuk meningkatkan performa jaringan
        $tweak_type = $_POST['tweak_type'] ?? '';
        $debug_output = "";
        
        switch ($tweak_type) {
            case 'tcp_optimize':
                // Optimasi TCP untuk koneksi yang lebih baik
                shell_exec("su -c 'echo 1 > /proc/sys/net/ipv4/tcp_tw_reuse'");
                shell_exec("su -c 'echo 0 > /proc/sys/net/ipv4/tcp_timestamps'");
                shell_exec("su -c 'echo 1 > /proc/sys/net/ipv4/tcp_sack'");
                shell_exec("su -c 'echo 1 > /proc/sys/net/ipv4/tcp_window_scaling'");
                shell_exec("su -c 'echo 0 > /proc/sys/net/ipv4/tcp_slow_start_after_idle'");
                
                $result = "TCP dioptimasi untuk performa jaringan yang lebih baik";
                $debug_output .= "Mengatur parameter TCP di kernel\n";
                break;
            
            case 'network_buffer':
                // Meningkatkan buffer jaringan
                shell_exec("su -c 'echo 4194304 > /proc/sys/net/core/rmem_max'");
                shell_exec("su -c 'echo 4194304 > /proc/sys/net/core/wmem_max'");
                shell_exec("su -c 'echo 4194304 > /proc/sys/net/core/rmem_default'");
                shell_exec("su -c 'echo 4194304 > /proc/sys/net/core/wmem_default'");
                
                $result = "Buffer jaringan ditingkatkan untuk throughput yang lebih baik";
                $debug_output .= "Mengatur buffer jaringan di kernel\n";
                break;
            
            case 'dns_optimize':
                // Optimasi DNS
                shell_exec("su -c 'setprop net.dns1 8.8.8.8'");
                shell_exec("su -c 'setprop net.dns2 8.8.4.4'");
                shell_exec("su -c 'setprop net.eth0.dns1 8.8.8.8'");
                shell_exec("su -c 'setprop net.eth0.dns2 8.8.4.4'");
                shell_exec("su -c 'setprop net.wlan0.dns1 8.8.8.8'");
                shell_exec("su -c 'setprop net.wlan0.dns2 8.8.4.4'");
                
                $result = "DNS dioptimasi menggunakan Google DNS";
                $debug_output .= "Mengatur DNS settings\n";
                break;
            
            case 'custom_dns':
                // Custom DNS
                $primary_dns = $_POST['primary_dns'] ?? '';
                $secondary_dns = $_POST['secondary_dns'] ?? '';
                
                if (filter_var($primary_dns, FILTER_VALIDATE_IP)) {
                    shell_exec("su -c 'setprop net.dns1 $primary_dns'");
                    shell_exec("su -c 'setprop net.eth0.dns1 $primary_dns'");
                    shell_exec("su -c 'setprop net.wlan0.dns1 $primary_dns'");
                    shell_exec("su -c 'setprop net.rmnet0.dns1 $primary_dns'");
                    
                    $debug_output .= "Primary DNS diatur ke: $primary_dns\n";
                } else {
                    $debug_output .= "Primary DNS tidak valid, diabaikan\n";
                }
                
                if (filter_var($secondary_dns, FILTER_VALIDATE_IP)) {
                    shell_exec("su -c 'setprop net.dns2 $secondary_dns'");
                    shell_exec("su -c 'setprop net.eth0.dns2 $secondary_dns'");
                    shell_exec("su -c 'setprop net.wlan0.dns2 $secondary_dns'");
                    shell_exec("su -c 'setprop net.rmnet0.dns2 $secondary_dns'");
                    
                    $debug_output .= "Secondary DNS diatur ke: $secondary_dns\n";
                } else {
                    $debug_output .= "Secondary DNS tidak valid, diabaikan\n";
                }
                
                // Uji coba buat file resolv.conf
                $resolv_content = "";
                if (filter_var($primary_dns, FILTER_VALIDATE_IP)) {
                    $resolv_content .= "nameserver $primary_dns\n";
                }
                if (filter_var($secondary_dns, FILTER_VALIDATE_IP)) {
                    $resolv_content .= "nameserver $secondary_dns\n";
                }
                
                if (!empty($resolv_content)) {
                    shell_exec("su -c 'echo \"$resolv_content\" > /data/local/tmp/resolv.conf'");
                    shell_exec("su -c 'cp /data/local/tmp/resolv.conf /etc/resolv.conf'");
                    $debug_output .= "File resolv.conf dibuat dengan DNS custom\n";
                }
                
                // Restart DNS service jika ada
                shell_exec("su -c 'resetprop -n net.dns1 $primary_dns'");
                if (filter_var($secondary_dns, FILTER_VALIDATE_IP)) {
                    shell_exec("su -c 'resetprop -n net.dns2 $secondary_dns'");
                }
                
                $result = "Custom DNS berhasil diterapkan";
                break;
            
            case 'cloudflare_dns':
                // Cloudflare DNS (1.1.1.1, 1.0.0.1)
                shell_exec("su -c 'setprop net.dns1 1.1.1.1'");
                shell_exec("su -c 'setprop net.dns2 1.0.0.1'");
                shell_exec("su -c 'setprop net.eth0.dns1 1.1.1.1'");
                shell_exec("su -c 'setprop net.eth0.dns2 1.0.0.1'");
                shell_exec("su -c 'setprop net.wlan0.dns1 1.1.1.1'");
                shell_exec("su -c 'setprop net.wlan0.dns2 1.0.0.1'");
                shell_exec("su -c 'setprop net.rmnet0.dns1 1.1.1.1'");
                shell_exec("su -c 'setprop net.rmnet0.dns2 1.0.0.1'");
                
                $result = "DNS dioptimasi menggunakan Cloudflare DNS (1.1.1.1)";
                $debug_output .= "Mengatur Cloudflare DNS settings\n";
                break;
            
            case 'quad9_dns':
                // Quad9 DNS (9.9.9.9, 149.112.112.112)
                shell_exec("su -c 'setprop net.dns1 9.9.9.9'");
                shell_exec("su -c 'setprop net.dns2 149.112.112.112'");
                shell_exec("su -c 'setprop net.eth0.dns1 9.9.9.9'");
                shell_exec("su -c 'setprop net.eth0.dns2 149.112.112.112'");
                shell_exec("su -c 'setprop net.wlan0.dns1 9.9.9.9'");
                shell_exec("su -c 'setprop net.wlan0.dns2 149.112.112.112'");
                shell_exec("su -c 'setprop net.rmnet0.dns1 9.9.9.9'");
                shell_exec("su -c 'setprop net.rmnet0.dns2 149.112.112.112'");
                
                $result = "DNS dioptimasi menggunakan Quad9 DNS (9.9.9.9)";
                $debug_output .= "Mengatur Quad9 DNS settings\n";
                break;
            
            case 'reset_tweaks':
                // Reset semua tweaks ke default
                shell_exec("su -c 'echo 0 > /proc/sys/net/ipv4/tcp_tw_reuse'");
                shell_exec("su -c 'echo 1 > /proc/sys/net/ipv4/tcp_timestamps'");
                shell_exec("su -c 'echo 1 > /proc/sys/net/ipv4/tcp_sack'");
                shell_exec("su -c 'echo 1 > /proc/sys/net/ipv4/tcp_window_scaling'");
                shell_exec("su -c 'echo 1 > /proc/sys/net/ipv4/tcp_slow_start_after_idle'");
                
                // Reset DNS settings
                shell_exec("su -c 'setprop net.dns1 \"\"'");
                shell_exec("su -c 'setprop net.dns2 \"\"'");
                
                $result = "Parameter jaringan direset ke default";
                $debug_output .= "Mengatur ulang parameter kernel ke default\n";
                break;
        }
        
        echo "<p class='green-text'>Kernel tweaks dijalankan: $tweak_type.</p>";
        echo "<p class='blue-text'>Informasi Debug:<br><pre>" . htmlspecialchars($debug_output) . "</pre></p>";
        echo "<p class='orange-text'>Hasil:<br><pre>" . htmlspecialchars($result) . "</pre></p>";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'set_ip_address') {
        $ip = $_POST['ip'] ?? '192.168.43.1';
        $interface = $_POST['interface'] ?? 'wlan0';
        $netmask = $_POST['netmask'] ?? '24';

        $command = "su -c 'ip addr add {$ip}/{$netmask} dev {$interface}'";
        $result = shell_exec($command);
        
        if (empty($result)) {
            echo "<p class='green-text'>IP Address berhasil diatur: {$ip}/{$netmask} pada interface {$interface}</p>";
        } else {
            echo "<p class='red-text'>Error: $result</p>";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'get_current_ip') {
        $ip_info = shell_exec("su -c 'ip addr show'");
        echo "<pre class='white-text'>" . htmlspecialchars($ip_info) . "</pre>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Tools</title>
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Materialize CSS -->
    <link rel="stylesheet" href="../auth/css/materialize.min.css">
    <style>
        body {
            background-color: #000000;
            color: #F1F1F1;
            font-family: 'Roboto', sans-serif;
        }
        .nav-wrapper {
            background: #000000;
            padding: 0 20px;
            border-bottom: 2px solid #FECA0A;
        }
        .brand-logo {
            font-weight: 300;
            color: #FECA0A !important;
        }
        @media only screen and (max-width: 992px) {
            .brand-logo {
                font-size: 1.5rem !important;
                width: 80%;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .brand-logo i {
                font-size: 1.5rem !important;
            }
        }
        @media only screen and (max-width: 600px) {
            .brand-logo {
                font-size: 1.2rem !important;
                left: 50%;
                transform: translateX(-50%);
            }
            .brand-logo i {
                font-size: 1.2rem !important;
            }
            .sidenav-trigger {
                display: block !important;
            }
        }
        .sidenav {
            background-color: #121212;
        }
        .sidenav li > a {
            color: #F1F1F1;
        }
        .sidenav .user-view {
            padding: 32px 32px 0;
        }
        .sidenav .user-view .background {
            background-color: #000000;
            height: 100px;
        }
        .page-footer {
            background: #000000;
            border-top: 2px solid #FECA0A;
            padding-top: 20px;
        }
        .container {
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .btn {
            background-color: #FECA0A !important;
            color: #000000 !important;
            border-radius: 30px;
            margin: 5px;
            text-transform: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
        .btn:hover {
            background-color: #F1B108 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
        }
        .btn i {
            color: #000000 !important;
        }
        .card {
            background-color: #1a1a1a !important;
            border-radius: 8px;
            margin-top: 15px;
            border: 1px solid rgba(254, 202, 10, 0.2);
        }
        .card .card-content {
            padding: 20px;
        }
        .card .card-title {
            font-weight: 500;
            color: #FECA0A !important;
        }
        .tabs .tab a {
            color: rgba(254, 202, 10, 0.7);
        }
        .tabs .tab a:hover, .tabs .tab a.active {
            color: #FECA0A;
        }
        .tabs .indicator {
            background-color: #FECA0A;
        }
        .tabs {
            background-color: #000000;
            margin-bottom: 20px;
            border-bottom: 1px solid #FECA0A;
        }
        .tabs .tab a:focus, .tabs .tab a:focus.active {
            background-color: rgba(254, 202, 10, 0.1);
        }
        select.browser-default {
            background-color: #2d2d2d;
            color: #F1F1F1;
            border: 1px solid #FECA0A;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 15px;
        }
        
        .card.indigo.darken-4, 
        .card.blue-grey.darken-3, 
        .card.light-blue.darken-3,
        .card.amber.darken-2 {
            background-color: #1a1a1a !important;
        }
        
        .input-field input[type=text],
        .input-field input[type=number],
        .input-field input[type=password] {
            border-bottom: 1px solid #FECA0A !important;
            box-shadow: 0 1px 0 0 #FECA0A !important;
            color: #F1F1F1 !important;
        }
        
        .input-field input[type=text]:focus,
        .input-field input[type=number]:focus,
        .input-field input[type=password]:focus {
            border-bottom: 1px solid #F1B108 !important;
            box-shadow: 0 1px 0 0 #F1B108 !important;
        }
        
        .input-field label {
            color: #999 !important;
        }
        
        .input-field input[type=text]:focus + label,
        .input-field input[type=number]:focus + label,
        .input-field input[type=password]:focus + label {
            color: #FECA0A !important;
        }
        
        [type="checkbox"].filled-in:checked + span:not(.lever):after {
            border: 2px solid #FECA0A;
            background-color: #FECA0A;
        }
        
        .progress-bar {
            background-color: rgba(241, 241, 241, 0.1);
        }
        
        .progress-fill {
            background: linear-gradient(90deg, #FECA0A, #F1B108);
        }
        
        .green-text {
            color: #FECA0A !important;
        }
        
        .blue-text, .cyan-text {
            color: #F1F1F1 !important;
        }
        
        .orange-text {
            color: #F1B108 !important;
        }
        
        .btn-floating {
            background-color: #FECA0A !important;
        }
        
        .btn-floating i {
            color: #000000 !important;
        }
        
        .footer-copyright {
            background-color: rgba(0, 0, 0, 0.5) !important;
        }
        
        .purple-text.text-lighten-3 {
            color: #FECA0A !important;
        }
        
        /* Override any color classes with !important */
        .indigo.darken-4,
        .blue-grey.darken-3,
        .light-blue.darken-3,
        .deep-purple.darken-1,
        .blue.darken-2 {
            background-color: #1a1a1a !important;
            border: 1px solid #FECA0A !important;
        }
        .feature-card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .feature-card .card-action {
            margin-top: auto;
        }
        .feature-icon {
            font-size: 40px;
            margin-bottom: 15px;
            color: #aa00ff;
        }
        .tabs-content {
            padding: 15px;
            background-color: #1a1a1a;
            border-radius: 0 0 8px 8px;
        }
        pre {
            background-color: #1e1e1e; 
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .badge-container {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .badge {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 15px;
            margin: 5px;
            font-size: 12px;
            font-weight: 600;
            background-color: #1a1a1a !important;
            color: #F1F1F1 !important;
            border: 1px solid #FECA0A;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        
        /* Override standard Materialize color classes used in badges */
        .badge.purple.darken-2,
        .badge.blue-grey.darken-1,
        .badge.deep-purple,
        .badge.teal.darken-1 {
            background-color: #1a1a1a !important;
            color: #F1F1F1 !important;
            border: 1px solid #FECA0A !important;
        }
        
        /* Gold highlight for important text within badges */
        .badge-highlight {
            color: #FECA0A !important;
            font-weight: 700;
        }
        
        .divider {
            background-color: #444;
            margin: 30px 0;
        }
        .tabs .tab a {
            padding: 0 10px;
            font-size: 12px;
        }
        .tabs .tab .material-icons.tiny {
            font-size: 16px;
            margin-right: 3px;
            vertical-align: middle;
        }
        @media only screen and (max-width: 600px) {
            .tabs .tab a {
                padding: 0 5px;
                font-size: 11px;
            }
            .tabs .tab .material-icons.tiny {
                font-size: 14px;
                margin-right: 2px;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav>
        <div class="nav-wrapper">
            <a href="#" class="brand-logo">
                <i class="material-icons left">signal_cellular_alt</i>Network Tools
            </a>
            <a href="#" data-target="mobile-nav" class="sidenav-trigger"><i class="material-icons">menu</i></a>
            <ul id="nav-mobile" class="right hide-on-med-and-down">
                <li><a href="dashboard.php"><i class="material-icons left">dashboard</i>Dashboard</a></li>
                <li><a href="../auth/logout.php"><i class="material-icons left">exit_to_app</i>Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Menu Mobile -->
    <ul class="sidenav" id="mobile-nav">
        <li>
            <div class="user-view">
                <div class="background deep-purple">
                </div>
                <span class="white-text name">Network Tools</span>
                <span class="white-text email">Mobile Menu</span>
            </div>
        </li>
        <li><a href="dashboard.php"><i class="material-icons">dashboard</i>Dashboard</a></li>
        <li><div class="divider"></div></li>
        <li><a href="../auth/logout.php"><i class="material-icons">exit_to_app</i>Logout</a></li>
    </ul>

    <div class="container">
        <div class="row">
            <div class="col s12">
                <div class="badge-container">
                    <span class="badge"><span class="badge-highlight">Root Access:</span> Active</span>
                    <span class="badge"><span class="badge-highlight">Network:</span> <?php echo shell_exec("su -c 'getprop gsm.network.type'") ?: 'Unknown'; ?></span>
                    <span class="badge"><span class="badge-highlight">Signal:</span> <?php echo shell_exec("su -c \"dumpsys telephony.registry | grep mSignalStrength | head -n1 | awk '{print \\$2}'\"") ?: 'Unknown'; ?></span>
                    <span class="badge"><span class="badge-highlight">Operator:</span> <?php echo shell_exec("su -c 'getprop gsm.sim.operator.alpha'") ?: 'Unknown'; ?></span>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="row">
            <div class="col s12">
                <ul class="tabs">
                    <li class="tab col s2"><a class="active" href="#tab-lock-band"><i class="material-icons left tiny">network_cell</i>Lock Band</a></li>
                    <li class="tab col s2"><a href="#tab-modem-control"><i class="material-icons left tiny">router</i>Modem</a></li>
                    <li class="tab col s2"><a href="#tab-kernel-tweaks"><i class="material-icons left tiny">memory</i>Kernel</a></li>
                    <li class="tab col s3"><a href="#tab-radio-control"><i class="material-icons left tiny">airplanemode_active</i>Airplane Mode</a></li>
                    <li class="tab col s3"><a href="#tab-ip-address"><i class="material-icons left tiny">wifi</i>Set WLAN IP</a></li>
                </ul>
            </div>
        </div>

        <!-- Tab: Lock Band -->
        <div id="tab-lock-band" class="col s12 tabs-content">
            <div class="card indigo darken-4">
                <div class="card-content">
                    <span class="card-title">
                        <i class="material-icons left">network_cell</i>Lock Band Jaringan
                    </span>
                    <p class="grey-text text-lighten-1">Kunci perangkat ke band jaringan tertentu untuk optimasi koneksi.</p>
                    
                    <form action="" method="post">
                        <div class="row">
                            <div class="col s12 m6">
                                <div class="input-field">
                                    <select name="band_selection" class="browser-default">
                                        <option value="" disabled selected>Pilih Band</option>
                                        <optgroup label="4G Bands">
                                            <option value="1">Band 1 (2100 MHz)</option>
                                            <option value="2">Band 2 (1900 MHz)</option>
                                            <option value="3">Band 3 (1800 MHz)</option>
                                            <option value="4">Band 4 (1700/2100 MHz)</option>
                                            <option value="5">Band 5 (850 MHz)</option>
                                            <option value="7">Band 7 (2600 MHz)</option>
                                            <option value="8">Band 8 (900 MHz)</option>
                                            <option value="9">Band 9 (1800 MHz)</option>
                                            <option value="12">Band 12 (700 MHz)</option>
                                            <option value="13">Band 13 (700 MHz)</option>
                                            <option value="20">Band 20 (800 MHz)</option>
                                            <option value="28">Band 28 (700 MHz)</option>
                                            <option value="38">Band 38 (2600 MHz)</option>
                                            <option value="40">Band 40 (2300 MHz)</option>
                                            <option value="41">Band 41 (2500 MHz)</option>
                                        </optgroup>
                                        <optgroup label="5G Bands">
                                            <option value="n1">Band n1 (2100 MHz)</option>
                                            <option value="n3">Band n3 (1800 MHz)</option>
                                            <option value="n5">Band n5 (850 MHz)</option>
                                            <option value="n7">Band n7 (2600 MHz)</option>
                                            <option value="n8">Band n8 (900 MHz)</option>
                                            <option value="n28">Band n28 (700 MHz)</option>
                                            <option value="n40">Band n40 (2300 MHz)</option>
                                            <option value="n41">Band n41 (2500 MHz)</option>
                                            <option value="n77">Band n77 (3700 MHz)</option>
                                            <option value="n78">Band n78 (3500 MHz)</option>
                                            <option value="n79">Band n79 (4500 MHz)</option>
                                        </optgroup>
                                        <optgroup label="Multiple Bands (Example)">
                                            <option value="1,3,8">Bands 1+3+8 (Combo)</option>
                                            <option value="1,3,40,41">Bands 1+3+40+41 (Combo)</option>
                                            <option value="n77,n78">5G Bands n77+n78 (Combo)</option>
                                            <option value="3,n78">4G+5G Band 3+n78 (Combo)</option>
                                        </optgroup>
                                    </select>
                                </div>
                                
                                <div class="input-field">
                                    <select name="provider_preset" class="browser-default">
                                        <option value="" selected disabled>Preset Provider (Opsional)</option>
                                        <option value="telkomsel">Telkomsel</option>
                                        <option value="xl">XL Axiata</option>
                                        <option value="indosat">Indosat Ooredoo</option>
                                        <option value="tri">Tri Indonesia</option>
                                        <option value="smartfren">Smartfren</option>
                                    </select>
                                </div>
                                
                                <div class="input-field">
                                    <select name="command_type" class="browser-default">
                                        <option value="standard" selected>Metode Standard</option>
                                        <option value="qcrilhook">Metode QcrilHook (Perangkat Baru)</option>
                                        <option value="qcrilnr">Metode QcrilNR (5G)</option>
                                        <option value="alternative">Metode Alternatif</option>
                                        <option value="netmgr">Metode NetMgr</option>
                                        <option value="atcommand">Perintah AT (Quectel/Qualcomm)</option>
                                        <option value="multiple_bands">Lock Multiple Bands</option>
                                        <option value="direct_inject">Direct Inject (Eksperimental)</option>
                                        <option value="mediatek">Mediatek Devices</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col s12 m6">
                                <div class="input-field">
                                    <select name="network_preference" class="browser-default">
                                        <option value="" selected disabled>Pilih Preferensi Jaringan (Opsional)</option>
                                        <option value="5g">5G/4G/3G/2G (Auto)</option>
                                        <option value="5g_standalone">5G SA Only</option>
                                        <option value="4g">4G/3G/2G (Auto)</option>
                                        <option value="4g_only">4G Only</option>
                                        <option value="3g">3G Preferred</option>
                                        <option value="3g_only">3G Only</option>
                                        <option value="2g">2G Only</option>
                                    </select>
                                </div>
                                
                                <div class="input-field">
                                    <select name="force_5g_mode" class="browser-default">
                                        <option value="" selected disabled>Force 5G Mode (Opsional)</option>
                                        <option value="nsa">Force 5G NSA (Non-Standalone)</option>
                                        <option value="sa">Force 5G SA (Standalone)</option>
                                        <option value="both">Enable Both NSA & SA</option>
                                    </select>
                                </div>
                                
                                <div class="row">
                                    <div class="input-field col s12">
                                        <input id="earfcn_value" name="earfcn_value" type="text" class="white-text">
                                        <label for="earfcn_value">EARFCN/NR-ARFCN Value (Opsional)</label>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="input-field col s12">
                                        <input id="cell_id_value" name="cell_id_value" type="text" class="white-text">
                                        <label for="cell_id_value">Cell ID (Opsional)</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col s12 m6">
                                <p>
                                    <label>
                                        <input type="checkbox" name="set_network_pref" value="1" class="filled-in" />
                                        <span>Terapkan Preferensi Jaringan</span>
                                    </label>
                                </p>
                                
                                <p>
                                    <label>
                                        <input type="checkbox" name="set_earfcn" value="1" class="filled-in" />
                                        <span>Terapkan EARFCN/ARFCN</span>
                                    </label>
                                </p>
                            </div>
                            
                            <div class="col s12 m6">
                                <p>
                                    <label>
                                        <input type="checkbox" name="lock_cell_id" value="1" class="filled-in" />
                                        <span>Kunci ke Cell ID Spesifik</span>
                                    </label>
                                </p>
                                
                                <p>
                                    <label>
                                        <input type="checkbox" name="restart_radio" value="1" class="filled-in" />
                                        <span>Restart Radio Setelah Perubahan</span>
                                    </label>
                                </p>
                            </div>
                        </div>
                        
                        <div class="card-action center-align">
                            <button type="submit" name="action" value="lock_band" class="btn purple pulse">
                                <i class="material-icons left">lock</i>Kunci Band
                            </button>
                            <button type="submit" name="action" value="unlock_band" class="btn orange">
                                <i class="material-icons left">lock_open</i>Buka Kunci Band
                            </button>
                            <button type="submit" name="action" value="get_network_info" class="btn blue">
                                <i class="material-icons left">info</i>Info Jaringan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tab: Modem Control -->
        <div id="tab-modem-control" class="col s12 tabs-content">
            <div class="card deep-purple darken-3">
                <div class="card-content">
                    <span class="card-title">
                        <i class="material-icons left">router</i>Kontrol Modem Lanjutan
                    </span>
                    <p class="grey-text text-lighten-1">Akses ke fungsi tingkat rendah modem perangkat.</p>
                    
                    <form action="" method="post">
                        <div class="row">
                            <div class="col s12 m6">
                                <div class="input-field">
                                    <select name="modem_command" class="browser-default">
                                        <option value="" disabled selected>Pilih Perintah</option>
                                        <option value="reset_modem">Reset Modem</option>
                                        <option value="force_reconnect">Paksa Koneksi Ulang</option>
                                        <option value="clear_radio_logs">Bersihkan Log Radio</option>
                                        <option value="modem_diagnostics">Diagnosa Modem</option>
                                        <option value="network_logging">Log Info Jaringan</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col s12 m6">
                                <p>
                                    <label>
                                        <input type="checkbox" name="fast_dormancy" value="1" class="filled-in" />
                                        <span>Aktifkan Fast Dormancy</span>
                                    </label>
                                </p>
                                
                                <p>
                                    <label>
                                        <input type="checkbox" name="data_roaming" value="1" class="filled-in" />
                                        <span>Aktifkan Data Roaming</span>
                                    </label>
                                </p>
                            </div>
                        </div>
                        
                        <div class="card-action center-align">
                            <button type="submit" name="action" value="advanced_modem_control" class="btn deep-purple">
                                <i class="material-icons left">settings_applications</i>Jalankan Perintah
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tab: Kernel Tweaks -->
        <div id="tab-kernel-tweaks" class="col s12 tabs-content">
            <div class="card teal darken-3">
                <div class="card-content">
                    <span class="card-title">
                        <i class="material-icons left">memory</i>Tweaks Kernel
                    </span>
                    <p class="grey-text text-lighten-1">Optimalkan pengaturan kernel untuk performa jaringan yang lebih baik.</p>
                    
                    <form action="" method="post">
                        <div class="row">
                            <div class="col s12">
                                <div class="input-field">
                                    <select name="tweak_type" class="browser-default" id="tweak_type_select">
                                        <option value="" disabled selected>Pilih Tweak</option>
                                        <option value="tcp_optimize">Optimasi TCP</option>
                                        <option value="network_buffer">Tingkatkan Buffer Jaringan</option>
                                        <option value="dns_optimize">Optimasi DNS (Google)</option>
                                        <option value="cloudflare_dns">DNS Cloudflare (1.1.1.1)</option>
                                        <option value="quad9_dns">DNS Quad9 (9.9.9.9)</option>
                                        <option value="custom_dns">DNS Kustom</option>
                                        <option value="reset_tweaks">Reset Semua Tweaks</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div id="custom_dns_fields" style="display:none;">
                            <div class="row">
                                <div class="col s12 m6">
                                    <div class="input-field">
                                        <input id="primary_dns" name="primary_dns" type="text" class="white-text" placeholder="1.1.1.1">
                                        <label for="primary_dns">DNS Utama</label>
                                    </div>
                                </div>
                                <div class="col s12 m6">
                                    <div class="input-field">
                                        <input id="secondary_dns" name="secondary_dns" type="text" class="white-text" placeholder="8.8.8.8">
                                        <label for="secondary_dns">DNS Sekunder</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-action center-align">
                            <button type="submit" name="action" value="kernel_tweaks" class="btn teal">
                                <i class="material-icons left">build</i>Terapkan Tweak
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tab: Radio Control -->
        <div id="tab-radio-control" class="col s12 tabs-content">
            <div class="card blue-grey darken-3">
                <div class="card-content">
                    <span class="card-title">
                        <i class="material-icons left">airplanemode_active</i>Pengaturan Airplane Mode
                    </span>
                    <p class="grey-text text-lighten-1">Kontrol radio perangkat dan pengaturan mode pesawat.</p>

                    <form action="" method="post">
                        <h6 class="white-text">Pilih radio yang tetap aktif saat mode pesawat:</h6>
                        <div class="row">
                            <div class="col s12 m6">
                                <p>
                                    <label>
                                        <input type="checkbox" name="cell" class="filled-in" <?php echo $checked_cell ? 'checked' : ''; ?> />
                                        <span>Seluler</span>
                                    </label>
                                </p>
                                <p>
                                    <label>
                                        <input type="checkbox" name="bluetooth" class="filled-in" <?php echo $checked_bluetooth ? 'checked' : ''; ?> />
                                        <span>Bluetooth</span>
                                    </label>
                                </p>
                            </div>
                        </div>

                        <h6 class="white-text">Pengaturan Jaringan:</h6>
                        <div class="row">
                            <div class="col s12 m6">
                                <p>
                                    <label>
                                        <input type="checkbox" name="network_choice" value="hotspot" class="filled-in" <?php echo $network_choice === 'hotspot' ? 'checked' : ''; ?> />
                                        <span>Aktifkan Hotspot</span>
                                    </label>
                                </p>
                            </div>
                        </div>

                        <div class="card-action center-align">
                            <button type="submit" name="action" value="enable_airplane_mode" class="btn green waves-effect waves-light">
                                <i class="material-icons left">flight_takeoff</i>Aktifkan Mode Pesawat
                            </button>
                            <button type="submit" name="action" value="disable_airplane_mode" class="btn red waves-effect waves-light">
                                <i class="material-icons left">flight_land</i>Nonaktifkan Mode Pesawat
                            </button>
                        </div>
                    </form>

                    <div class="divider" style="margin: 30px 0;"></div>

                    <form action="" method="post">
                        <h6 class="white-text">Kontrol Radio Individual:</h6>
                        <div class="row">
                            <div class="col s12 m6">
                                <p>
                                    <label>
                                        <input type="checkbox" name="bluetooth_control" class="filled-in" <?php echo shell_exec("su -c 'svc bluetooth status'") === 'enabled' ? 'checked' : ''; ?> />
                                        <span>Bluetooth</span>
                                    </label>
                                </p>
                                <p>
                                    <label>
                                        <input type="checkbox" name="wifi_control" class="filled-in" <?php echo shell_exec("su -c 'svc wifi status'") === 'enabled' ? 'checked' : ''; ?> />
                                        <span>WiFi</span>
                                    </label>
                                </p>
                            </div>
                        </div>

                        <div class="card-action center-align">
                            <button type="submit" name="action" value="update_radios" class="btn blue waves-effect waves-light">
                                <i class="material-icons left">save</i>Perbarui Radio
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tab: IP Address -->
        <div id="tab-ip-address" class="col s12 tabs-content">
            <div class="card light-blue darken-3">
                <div class="card-content">
                    <span class="card-title">
                        <i class="material-icons left">wifi</i>Pengaturan WLAN IP
                    </span>
                    <p class="grey-text text-lighten-1">Atur IP Address untuk interface WLAN.</p>
                    
                    <form action="" method="post">
                        <div class="row">
                            <div class="col s12 m6">
                                <div class="input-field">
                                    <input id="interface" name="interface" type="text" class="white-text" value="wlan0">
                                    <label for="interface">Interface (contoh: wlan0)</label>
                                </div>
                            </div>
                            
                            <div class="col s12 m6">
                                <div class="input-field">
                                    <input id="ip" name="ip" type="text" class="white-text" value="192.168.43.1">
                                    <label for="ip">IP Address</label>
                                </div>
                            </div>
                            
                            <div class="col s12 m6">
                                <div class="input-field">
                                    <input id="netmask" name="netmask" type="text" class="white-text" value="24">
                                    <label for="netmask">Netmask (CIDR)</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-action center-align">
                            <button type="submit" name="action" value="set_ip_address" class="btn light-blue">
                                <i class="material-icons left">save</i>Set IP Address
                            </button>
                            <button type="button" id="show_current_ip" class="btn blue">
                                <i class="material-icons left">info</i>Tampilkan IP Saat Ini
                            </button>
                        </div>
                    </form>
                    
                    <div id="current_ip_info" class="white-text" style="margin-top: 20px;"></div>
                </div>
            </div>
        </div>

        <!-- Results Section (if any results to show) -->
        <?php if (isset($results_message)): ?>
        <div class="row">
            <div class="col s12">
                <div class="card amber darken-2">
                    <div class="card-content white-text">
                        <span class="card-title"><i class="material-icons left">info</i>Hasil Operasi</span>
                        <p><?php echo $results_message; ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
        <div class="footer-copyright">
            <div class="container">
                <div class="row">
                    <div class="col s12 m8">
                        <span class="left">
                            <span class="purple-text text-lighten-3">© 2025</span>
                            <span class="cyan-text text-lighten-3">Network Tools</span>
                            <i class="material-icons tiny pink-text pulse">favorite</i> 
                            <span class="grey-text text-lighten-2">by</span>
                            <a href="https://t.me/latifan_id" class="waves-effect waves-light btn-small deep-purple darken-1" target="_blank">
                                <i class="material-icons left tiny">telegram</i>
                                @latifan_id
                            </a>
                        </span>
                    </div>
                    <div class="col s12 m4">
                        <div class="right">
                            <a class="waves-effect waves-light btn-small blue darken-2" href="dashboard.php">
                                <i class="material-icons left tiny">signal_cellular_alt</i>
                                DASHBOARD SINYAL
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Materialize JavaScript -->
    <script src="../auth/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi sidenav untuk mobile
            var elems = document.querySelectorAll('.sidenav');
            var instances = M.Sidenav.init(elems);
            
            // Inisialisasi tabs
            var tabsElem = document.querySelector('.tabs');
            var tabsInstance = M.Tabs.init(tabsElem);
            
            // Inisialisasi select elements
            var selectElems = document.querySelectorAll('select');
            var selectInstances = M.FormSelect.init(selectElems);
            
            // Tampilkan field DNS kustom jika opsi tersebut dipilih
            var tweakTypeSelect = document.getElementById('tweak_type_select');
            var customDnsFields = document.getElementById('custom_dns_fields');
            
            if (tweakTypeSelect && customDnsFields) {
                tweakTypeSelect.addEventListener('change', function() {
                    if (this.value === 'custom_dns') {
                        customDnsFields.style.display = 'block';
                    } else {
                        customDnsFields.style.display = 'none';
                    }
                });
            }
            
            // Efek ripple untuk tombol
            var buttons = document.querySelectorAll('.btn');
            buttons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    let x = e.clientX - e.target.offsetLeft;
                    let y = e.clientY - e.target.offsetTop;
                    
                    let ripple = document.createElement('span');
                    ripple.style.left = `${x}px`;
                    ripple.style.top = `${y}px`;
                    
                    this.appendChild(ripple);
                    
                    setTimeout(function() {
                        ripple.remove();
                    }, 600);
                });
            });

            // Handler untuk tombol Show Current IP
            document.getElementById('show_current_ip')?.addEventListener('click', function() {
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_current_ip'
                })
                .then(response => response.text())
                .then(data => {
                    document.getElementById('current_ip_info').innerHTML = data;
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });
    </script>
</body>
</html>
