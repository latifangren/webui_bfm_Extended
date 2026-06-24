# File Tree Codemap

## Root Files

```
/ (root module directory)
  |-- module.prop           # Magisk module metadata (id, version, author)
  |-- customize.sh          # Module installer script
  |-- service.sh            # Boot service script
  |-- uninstall.sh          # Module cleanup script
  |-- update.json           # OTA update configuration
  |-- CHANGELOG.md          # Version history
  |-- README.md             # Project overview
  |-- LICENSE               # MIT License
  |-- .gitignore            # Git ignore rules
```

## Service Scripts (`scripts/`)

```
scripts/
  |-- php_run              # PHP server lifecycle (start/stop/status)
  |-- ttyd_run             # ttyd terminal lifecycle
  |-- php_inotifyd         # File watcher daemon
  |-- tmux_run             # Tmux session manager
```

## Auto-Start Scripts (`data/adb/service.d/`)

```
data/adb/service.d/
  |-- autoswitchapn.sh     # Auto APN switch on connection loss
  |-- hotspot.sh           # Hotspot configuration
  |-- ip_static.sh         # Static IP assignment
  |-- pingloop.sh          # Ping monitoring loop
  |-- vnstat.sh            # vnStat traffic collector
```

## PHP Binary & Config (`files/bin/`)

```
files/bin/
  |-- php                  # PHP 7.4.33 CLI (32-bit embedded)
  |-- php.config           # PHP-FPM configuration
  |-- php.ini              # PHP settings
  |-- ttyd                 # Web terminal binary
  |-- msmtp                # SMTP mail client
  |-- preferences.sqlite   # SQLite preferences
  |-- *.so (70+)           # PHP extensions
```

Key extensions:
```
  |-- gd.so                # Image processing
  |-- mbstring.so          # Multibyte strings
  |-- mysqli.so            # MySQL
  |-- pdo_mysql.so         # PDO MySQL
  |-- pdo_pgsql.so         # PDO PostgreSQL
  |-- pgsql.so             # PostgreSQL
  |-- curl.so              # cURL
  |-- zip.so               # ZIP
  |-- yaml.so              # YAML parsing
  |-- ssh2.so              # SSH2
  |-- ldap.so              # LDAP
  |-- intl.so              # Internationalization
  |-- ev.so                # Event loop
  |-- eio.so               # Async I/O
  |-- inotify.so           # File system events
  |-- yar.so               # RPC
  |-- oauth.so             # OAuth
  |-- mailparse.so         # Email parsing
```

## Web Root (`files/www/`)

```
files/www/
  |-- index.php            # Entry point (refactored — loads bootstrap + layout)
  |-- router.php           # Front controller (NEW)
  |-- extended.php         # Theme Luci (legacy fallback)
  |-- about.php            # About + version info
  |-- article.html         # Documentation page
  |-- blackbox.php         # Blackbox tool
```

### Backend Logic (`files/www/includes/`) — REFACTORED

```
includes/
  |-- bootstrap.php        # Autoloader, constants, config loader
  |-- helpers.php          # Pure helper functions (boxui_e, boxui_format_bytes, etc.)
  |
  |-- auth/
  |   |-- AuthService.php  # Auth logic — login, logout, requireAuth, changePassword
  |
  |-- commands/
  |   |-- CommandRunner.php # ALL shell execution — su(), sh(), typed methods
  |
  |-- module/
  |   |-- ModuleRegistry.php # Module metadata + sidebar generation
  |
  |-- features/
  |   |-- network/
  |   |   |-- NetworkService.php  # Network business logic (WiFi, ping, hotspot, etc.)
  |   |-- monitor/                # System monitoring service (WIP)
  |   |-- system/                 # System operations service (WIP)
  |   |-- box/                    # BOX service (WIP)
```

### View Templates (`files/www/pages/`) — REFACTORED

```
pages/
  |-- layouts/
  |   |-- default.php      # Main SPA shell layout (sidebar + content container)
  |
  |-- partials/
  |   |-- sidebar.php      # Sidebar HTML generated from ModuleRegistry
  |
  |-- auth/
  |   |-- login.php        # Login page template (pure HTML, auth logic in controller)
  |
  |-- network/
  |   |-- tools.php        # Network tools view (Airplane mode, WiFi status)
  |   |-- monitor.php      # Ping monitor view
  |
  |-- monitor/             # System monitor views (WIP)
  |-- system/              # System views (WIP)
  |-- box/                 # BOX views (WIP)
```

### Auth System (`files/www/auth/`) — REFACTORED

```
auth/
  |-- config.php           # Auth config loader (legacy — includes/bootstrap.php handles this now)
  |-- config.json          # Stored credentials (LOGIN_ENABLED flag)
  |-- credentials.php      # Hashed credentials
  |-- login.php            # Login — refactored to use AuthService
  |-- logout.php           # Logout — refactored to use AuthService
  |-- change_password.php  # Password change — refactored to use AuthService
  |-- change_password_default.php  # Legacy fallback
  |-- manage_login.php     # Login settings (legacy)
  |-- manage_login_default.php     # Legacy fallback
  |-- assets/              # Auth page assets
  |   |-- background.jpg   # Login background
  |-- css/
  |   |-- materialize.min.css  # Materialize CSS
  |-- js/
  |   |-- materialize.min.js   # Materialize JS
```

### Tool Pages (`files/www/tools/`) — Nama file direfactor

```
tools/
  |-- dashboard.php                # Extended dashboard
  |-- logs.php                     # Magisk log viewer
  |-- opsi_box.php                 # BOX management (LEGACY name)
  |-- box_manager.php              # BOX management (RENAMED — wrapper to opsi_box.php)
  |-- powermanager.php             # Power management
  |-- networktools.php             # WiFi scan, connect, network diag (LEGACY name)
  |-- networktools_handler.php     # POST handler for network forms (NEW — extracted)
  |-- network_monitor.php          # Ping monitor with chart
  |-- vnstat.php                   # Bandwidth monitor (LEGACY name)
  |-- bandwidth_monitor.php        # Bandwidth monitor (RENAMED — wrapper to vnstat.php)
  |-- opsi_interface.php           # Interface manager (LEGACY name)
  |-- interface_manager.php        # Interface manager (RENAMED — wrapper to opsi_interface.php)
  |-- net_limiter_control.php      # NetLimiter (iptables)
  |-- adb_tools.php                # ADB WiFi controls
  |-- smsviewer.php                # SMS inbox viewer
  |-- modpes.php                   # Airplane/modem mode (LEGACY name)
  |-- airplane_pilot.php          # Airplane/modem mode (RENAMED — wrapper to modpes.php)
  |-- sidompul.php                 # Telco balance check
  |-- ad_block_test.php            # AdBlock test
  |-- dns_leak_test.php            # DNS leak test
  |-- blocked_users.txt            # NetLimiter blocked list
  |-- limited_users.txt            # NetLimiter limited list
```

### BOX For Magisk (`files/www/tools/bfr/`)

```
tools/bfr/
  |-- boxsettings.php     # BOX config editor
  |-- executed.php        # BOX lifecycle control
```

### Hotspot (`files/www/tools/hotspot/`)

```
tools/hotspot/
  |-- hotspot.php         # Hotspot control panel
  |-- process_hotspot.php # Hotspot backend processing
```

### Interface (`files/www/tools/interface/`)

```
tools/interface/
  |-- interface.php       # Interface configuration
  |-- ipset.php           # Static IP settings
  |-- script.php          # Interface automation script
```

### Debug Logs (`files/www/tools/logs/`)

```
tools/logs/
  |-- band_debug.log      # Band/network debug log
  |-- gps_debug.log       # GPS debug log
  |-- signal_debug.log    # Signal debug log
```

### Config Generator (`files/www/tools/ocgen/`)

```
tools/ocgen/
  |-- index.php            # Main config generator page
  |-- index.html           # Alternative entry
  |-- about.php            # About page
  |-- changelog.php        # Changelog
  |-- config.php           # Config
  |-- system.php           # System info
  |-- vmess.php            # VMess link parser
  |-- vless.php            # VLESS link parser
  |-- trojan.php           # Trojan link parser
  |-- ss.php               # Shadowsocks link parser
  |-- data/                # Assets
  |-- inc/                 # Includes (config, header, footer, navbar, JS)
  |-- js/                  # JavaScript files
  |-- lib/vendor/          # Third-party libs (axios, bootstrap, jquery, etc.)
```

### Speed Test (`files/www/tools/speedtest/`)

```
tools/speedtest/
  |-- speedtest.php        # Speed test page
  |-- local/               # Local network speed test
      |-- index.php        # Local speed test page
      |-- backend.php      # Local speed test backend
```

### Tutorial (`files/www/tools/tutorial/`)

```
tools/tutorial/
  |-- readme.md            # Tutorial markdown
  |-- readme.php           # Tutorial page
```

### Monitor Pages (`files/www/webui/monitor/`)

```
webui/monitor/
  |-- Overview.php                 # System dashboard
  |-- CpuMonitor.php               # CPU monitoring page
  |-- RamMonitor.php               # RAM monitoring page
  |-- BatteryMonitor.php           # Battery monitoring page
  |-- StorageMonitor.php           # Storage monitoring page
  |-- api/
  |   |-- battery_info.php         # Battery JSON API
  |   |-- helpers.php              # Shared helpers
  |   |-- network_info.php         # Network interface JSON API
  |   |-- network_interface_info.php # Interface detail JSON API
  |   |-- network_stats.json       # Cached network stats
  |   |-- signal_info.php          # Signal JSON API
  |   |-- storage_info.php         # Storage JSON API
  |   |-- system_info.php          # System JSON API
  |   |-- uptime.php               # Uptime JSON API
  |-- css/
  |   |-- styles.css               # Monitor page styles
  |   |-- sysinfo.css              # System info styles
  |   |-- background/
  |   |   |-- image.jpg            # Background image
  |   |-- LemonMilkProRegular.otf  # Font file
  |-- exec/
  |   |-- helpers.php              # Execution helpers
```

### Luci Theme Assets (`files/www/webui/`)

```
webui/
  |-- assets/
  |   |-- luci.ico                 # Favicon
  |   |-- img/
  |       |-- icon.png             # App icon
  |       |-- logo.png             # App logo
  |       |-- favicons/            # Various favicons
  |-- css/
  |   |-- styles.css               # Main stylesheet
  |   |-- font-awesome.min.css     # Font Awesome 4
  |   |-- fontawesome/             # Font Awesome 6
  |       |-- all.min.css
  |       |-- webfonts/
  |-- fonts/
  |   |-- Cyberpunk.otf / .ttf
  |   |-- LemonMilkProMedium.otf / Regular.otf
  |   |-- MaterialIcons-Regular.woff2
  |   |-- Orbitron-*.woff2 (Black, Bold, Medium)
  |   |-- Poppins-*.woff2 (Medium, Regular, SemiBold)
  |   |-- Rajdhani-*.woff2 (Bold, Medium, SemiBold)
  |   |-- Roboto-*.woff2 (Bold, Medium, Regular)
  |   |-- Rovelink.otf
  |   |-- SPACE ARMOR.otf
  |-- js/
  |   |-- font-awesome.min.js
  |   |-- iconify.min.js
  |   |-- fontawesome/all.min.js
  |   |-- iconify/iconify.min.js
  |   |-- chart/chart.min.js        # Chart.js
```

### Theme Selector (`files/www/select_theme/`)

```
select_theme/
  |-- theme.json           # Theme selection config
  |-- theme.php            # Theme selector logic
```

### Third-Party SPAs

```
kaiadmin/                  # Bootstrap admin template
  |-- assets/css/          # Bootstrap, KaiAdmin CSS
  |-- assets/fonts/        # Font Awesome, Simple Line Icons
  |-- assets/js/           # jQuery, Bootstrap, plugins

libernet/                  # Libernet Plus SPA
  |-- index.php            # Main SPA entry
  |-- api.php              # API endpoints
  |-- config.inc.php       # Config
  |-- css/                 # Styles
  |-- js/                  # Vue, axios, lodash
  |-- lib/vendor/          # jQuery, Bootstrap, SweetAlert2

tiny/                      # Tiny File Manager
  |-- index.php            # File manager main
  |-- opsi.php             # Options
  |-- translation.json     # Translations
  |-- assets/              # Bootstrap, Font Awesome, Ace editor

zashboard/                 # Clash Dashboard SPA
  |-- index.html           # Main entry
  |-- assets/              # CSS, JS, fonts
```

## Installer (`META-INF/`)

```
META-INF/com/google/android/
  |-- update-binary        # Magisk update binary
  |-- updater-script       # Installation commands
```
