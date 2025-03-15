<!DOCTYPE html>
<html lang="id">
<head>
    <title>Airplane BOX UI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        :root {
            --bg-color: #F1F1F1;
            --text-color: #000000;
            --card-bg: #ffffff;
            --hover-color: #e0e0e0;
            --accent-color: #FECA0A;
            --header-height: 60px;
        }
        
        body {
            visibility: hidden;
        }

        body[data-theme="dark"] {
            --bg-color: #000000;
            --text-color: #F1F1F1;
            --card-bg: #121212;
            --hover-color: #1a1a1a;
            --accent-color: #FECA0A;
            background-color: var(--bg-color);
            color: var(--text-color);
            padding-top: var(--header-height);
        }

        body[data-theme="light"] {
            background-color: var(--bg-color);
            color: var(--text-color);
            padding-top: var(--header-height);
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background-color: var(--card-bg);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 500;
            color: var(--accent-color);
        }

        .theme-toggle {
            background: none;
            border: none;
            cursor: pointer;
            padding: 10px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .theme-toggle:hover {
            background-color: var(--hover-color);
        }

        .theme-icon {
            font-size: 24px;
            color: var(--accent-color);
        }

        .container {
            padding: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .title {
            font-size: 2.8rem;
            text-align: center;
            margin: 20px 0 40px;
            font-weight: 500;
            color: var(--text-color);
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 12px;
            background-color: var(--hover-color);
        }

        .checkbox-wrapper:hover {
            transform: translateY(-2px);
        }

        [type="checkbox"] + span {
            color: var(--text-color);
            padding-left: 35px;
            font-size: 1.1rem;
        }

        .btn {
            margin: 15px;
            border-radius: 12px;
            text-transform: none;
            font-weight: 500;
            font-size: 1.1rem;
            height: 48px;
            line-height: 48px;
            padding: 0 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background-color: var(--accent-color);
            color: #000000;
        }

        .btn:hover {
            background-color: #e0b600;
        }

        .btn.green {
            background-color: var(--accent-color);
        }

        .btn.red {
            background-color: #ff5252;
        }

        .btn.blue {
            background-color: var(--accent-color);
        }

        .btn i {
            font-size: 20px;
        }

        .section-title {
            font-size: 1.8rem;
            margin: 30px 0 20px;
            font-weight: 500;
            color: var(--accent-color);
        }

        .success-message {
            color: var(--accent-color);
            text-align: center;
            margin: 15px 0;
            padding: 15px;
            border-radius: 12px;
            background-color: rgba(254, 202, 10, 0.1);
            font-size: 1.1rem;
        }

        @media (max-width: 600px) {
            .title {
                font-size: 2rem;
                margin: 15px 0 30px;
            }
            
            .btn {
                width: 100%;
                margin: 10px 0;
                height: 44px;
                line-height: 44px;
                font-size: 1rem;
            }

            .checkbox-group {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .section-title {
                font-size: 1.5rem;
                margin: 25px 0 15px;
            }

            .card {
                padding: 20px;
                margin: 20px 0;
            }

            .checkbox-wrapper {
                padding: 12px;
            }

            [type="checkbox"] + span {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body <?php echo isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'data-theme="dark"' : ''; ?>>
    <header class="header">
        <div class="logo">Airplane BOX UI</div>
        <button id="theme-toggle" class="theme-toggle">
            <i class="material-icons theme-icon" id="theme-icon">dark_mode</i>
        </button>
    </header>

    <div class="container">
        <?php
        // Initialize variables for pre-checking
        $checked_wifi = $checked_cell = $checked_bluetooth = $checked_nfc = $checked_wimax = false;
        $network_choice = 'hotspot'; // Default value
        $airplane_mode_enabled = false;

        // Detect current state of radio settings
        $current_radios = shell_exec("su -c 'settings get global airplane_mode_radios'");
        $current_radios = explode(',', trim($current_radios));
        
        //$checked_wifi = in_array('wifi', $current_radios);
        $checked_cell = in_array('cell', $current_radios);
        $checked_bluetooth = in_array('bluetooth', $current_radios);
        //$checked_nfc = in_array('nfc', $current_radios);
        //$checked_wimax = in_array('wimax', $current_radios);

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (isset($_POST['action']) && $_POST['action'] === 'disable_airplane_mode') {
                // Disable airplane mode
                shell_exec("su -c 'settings put global airplane_mode_on 0'");
                shell_exec("su -c 'am broadcast -a android.intent.action.AIRPLANE_MODE --ez state false'");

                // Ensure radios are still enabled based on previous settings
                $enabled_radios = isset($_POST['enabled_radios']) ? json_decode($_POST['enabled_radios'], true) : [];
                $radios_str = implode(',', $enabled_radios);
                shell_exec("su -c 'settings put global airplane_mode_radios \"$radios_str\"'");

                echo "<p class='green-text'>Airplane mode disabled.</p>";
                $airplane_mode_enabled = false;
            } elseif (isset($_POST['action']) && $_POST['action'] === 'enable_airplane_mode') {
                $enabled_radios = [];

                // Collect selected radios
               // if (isset($_POST['wifi'])) {
               //     $enabled_radios[] = 'wifi';
               //     $checked_wifi = true;
               // }
                if (isset($_POST['cell'])) {
                    $enabled_radios[] = 'cell';
                    $checked_cell = true;
                }
                if (isset($_POST['bluetooth'])) {
                    $enabled_radios[] = 'bluetooth';
                    $checked_bluetooth = true;
                }
               // if (isset($_POST['nfc'])) {
               //     $enabled_radios[] = 'nfc';
               //     $checked_nfc = true;
               // }
               // if (isset($_POST['wimax'])) {
                //    $enabled_radios[] = 'wimax';
                //    $checked_wimax = true;
                //}

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

                echo "<p class='green-text'>Airplane mode enabled with whitelisted radios. Network choice: $network_choice.</p>";

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

               // if (isset($_POST['nfc_control'])) {
               //     shell_exec("su -c 'nfc enable'");
               // } else {
                //    shell_exec("su -c 'nfc disable'");
                //}

                echo "<p class='green-text'>Radio settings updated.</p>";
            }
        }
        ?>

        <div class="card">
            <form action="" method="post">
                <h2 class="section-title">Select radios to keep on while airplane mode on :</h2>
                <div class="checkbox-group">
                    <div class="checkbox-wrapper">
                        <label>
                            <input type="checkbox" name="cell" <?php echo $checked_cell ? 'checked' : ''; ?> />
                            <span>Cell</span>
                        </label>
                    </div>
                    <div class="checkbox-wrapper">
                        <label>
                            <input type="checkbox" name="bluetooth" <?php echo $checked_bluetooth ? 'checked' : ''; ?> />
                            <span>Bluetooth</span>
                        </label>
                    </div>
                </div>

                <h2 class="section-title">Keep hotspot network enable? :</h2>
                <div class="checkbox-group">
                    <div class="checkbox-wrapper">
                        <label>
                            <input type="checkbox" name="network_choice" value="hotspot" <?php echo $network_choice === 'hotspot' ? 'checked' : ''; ?> />
                            <span>Hotspot Only</span>
                        </label>
                    </div>
                </div>

                <div class="center-align">
                    <button type="submit" name="action" value="enable_airplane_mode" class="btn green waves-effect waves-light">
                        <i class="material-icons">flight_takeoff</i>
                        Enable Airplane Mode
                    </button>
                    <button type="submit" name="action" value="disable_airplane_mode" class="btn red waves-effect waves-light">
                        <i class="material-icons">flight_land</i>
                        Disable Airplane Mode
                    </button>
                </div>
            </form>
        </div>

        <div class="card">
            <form action="" method="post">
                <h2 class="section-title">Update radios individually :</h2>
                <div class="checkbox-group">
                    <div class="checkbox-wrapper">
                        <label>
                            <input type="checkbox" name="bluetooth_control" <?php echo shell_exec("su -c 'svc bluetooth status'") === 'enabled' ? 'checked' : ''; ?> />
                            <span>Bluetooth</span>
                        </label>
                    </div>
                    <div class="checkbox-wrapper">
                        <label>
                            <input type="checkbox" name="wifi_control" <?php echo shell_exec("su -c 'svc wifi status'") === 'enabled' ? 'checked' : ''; ?> />
                            <span>WiFi</span>
                        </label>
                    </div>
                </div>
                <div class="center-align">
                    <button type="submit" name="action" value="update_radios" class="btn blue waves-effect waves-light">
                        <i class="material-icons">settings</i>
                        Update Radios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        // Menentukan tema berdasarkan preferensi pengguna
        const getCookie = (name) => {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        };

        // Set cookie untuk tema
        const setCookie = (name, value, days) => {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie = `${name}=${value};expires=${date.toUTCString()};path=/`;
        };

        // Fungsi untuk mengalihkan tema
        const toggleTheme = () => {
            const currentTheme = document.body.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.body.setAttribute('data-theme', newTheme);
            updateThemeIcon(newTheme);
            setCookie('theme', newTheme, 365);
        };

        // Perbarui ikon tema
        const updateThemeIcon = (theme) => {
            const themeIcon = document.getElementById('theme-icon');
            themeIcon.textContent = theme === 'dark' ? 'light_mode' : 'dark_mode';
        };

        // Set tema awal berdasarkan cookie atau preferensi sistem
        const savedTheme = getCookie('theme');
        const userPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        let currentTheme;
        if (savedTheme) {
            currentTheme = savedTheme;
        } else {
            currentTheme = userPrefersDark ? 'dark' : 'light';
        }
        
        document.body.setAttribute('data-theme', currentTheme);
        updateThemeIcon(currentTheme);

        // Setelah tema diterapkan, tampilkan konten
        document.body.style.visibility = 'visible';

        // Tambahkan event listener untuk tombol toggle tema
        document.getElementById('theme-toggle').addEventListener('click', toggleTheme);

        // Menangani perubahan preferensi tema sistem
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!getCookie('theme')) {
                const newTheme = e.matches ? 'dark' : 'light';
                document.body.setAttribute('data-theme', newTheme);
                updateThemeIcon(newTheme);
            }
        });
    </script>
</body>
</html>