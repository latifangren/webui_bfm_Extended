# Module Codemap

## Feature Modules

### System Monitoring

| Modul | File Path | Deskripsi |
|-------|-----------|-----------|
| Dashboard Overview | `webui/monitor/Overview.php` | CPU, RAM, storage, battery, uptime overview |
| CPU Monitor | `webui/monitor/CpuMonitor.php` | Per-core frequency, usage, governor, Chart.js |
| RAM Monitor | `webui/monitor/RamMonitor.php` | Memory breakdown, top processes, swap |
| Battery Monitor | `webui/monitor/BatteryMonitor.php` | Health, capacity, temp, voltage, technology |
| Storage Monitor | `webui/monitor/StorageMonitor.php` | Partitions, usage, mount points |

### API Endpoints (JSON)

| Endpoint | File Path | Data |
|----------|-----------|------|
| `/monitor/api/battery_info.php` | `webui/monitor/api/battery_info.php` | Battery JSON data |
| `/monitor/api/network_info.php` | `webui/monitor/api/network_info.php` | Network interfaces |
| `/monitor/api/network_interface_info.php` | `webui/monitor/api/network_interface_info.php` | Interface details |
| `/monitor/api/signal_info.php` | `webui/monitor/api/signal_info.php` | Cellular signal |
| `/monitor/api/storage_info.php` | `webui/monitor/api/storage_info.php` | Storage partitions |
| `/monitor/api/system_info.php` | `webui/monitor/api/system_info.php` | System properties |
| `/monitor/api/uptime.php` | `webui/monitor/api/uptime.php` | System uptime |

### Network

| Modul | File Path | Deskripsi |
|-------|-----------|-----------|
| Network Tools | `tools/networktools.php` | WiFi scan, connect, forget, diagnostics |
| Network Monitor | `tools/network_monitor.php` | Ping monitor with real-time chart |
| Bandwidth Monitor | `tools/vnstat.php` | vnStat daily/monthly/top10 traffic |
| Interface Manager | `tools/opsi_interface.php` | Interface list + management |
| Interface Config | `tools/interface/interface.php` | Detailed interface configuration |
| IP Settings | `tools/interface/ipset.php` | Static IP assignment |
| Interface Script | `tools/interface/script.php` | Interface automation script |
| NetLimiter | `tools/net_limiter_control.php` | Block/limit client bandwidth |
| Wireless Hotspot | `tools/hotspot/hotspot.php` | Hotspot enable/disable/config |
| Hotspot Process | `tools/hotspot/process_hotspot.php` | Hotspot settings backend |
| Airplane Pilot | `tools/modpes.php` | Modem mode (airplane+hotspot+RNDIS) |
| Speed Test | `tools/speedtest/speedtest.php` | Network speed test |
| Speed Test Local | `tools/speedtest/local/index.php` | Local network speed test |
| DNS Leak Test | `tools/dns_leak_test.php` | DNS leak detection test |
| AdBlock Test | `tools/ad_block_test.php` | Ad blocking verification |

### System

| Modul | File Path | Deskripsi |
|-------|-----------|-----------|
| Power Manager | `tools/powermanager.php` | Reboot, recovery, bootloader, shutdown |
| File Manager | `tiny/opsi.php` | Full file browser (TinyFM) |
| Magisk Log | `tools/logs.php` | Runtime and audit logs |
| Module Control | `extended.php` (POST) | Module enable/disable |
| About | `about.php` | Version info, changelog |
| Documentation | `article.html` | Usage documentation |

### BOX Service

| Modul | File Path | Deskripsi |
|-------|-----------|-----------|
| BOX Options | `tools/opsi_box.php` | BOX management menu |
| BOX Settings | `tools/bfr/boxsettings.php` | BOX configuration editor |
| BOX Execution | `tools/bfr/executed.php` | BOX lifecycle (start/stop/restart) |
| Config Generator | `tools/ocgen/index.php` | VMess/VLESS/Trojan/SS -> Clash YAML |

### Services

| Modul | File Path | Deskripsi |
|-------|-----------|-----------|
| ADB Tools | `tools/adb_tools.php` | ADB WiFi, screenshot, app control |
| SMS Viewer | `tools/smsviewer.php` | Read SMS inbox via content provider |
| Sidompul | `tools/sidompul.php` | Indonesian telco balance check |
| Terminal | ttyd (`:3001`) | Web terminal emulator |

### Authentication

| Modul | File Path | Deskripsi |
|-------|-----------|-----------|
| Login | `auth/login.php` | Login form |
| Logout | `auth/logout.php` | Session destroy |
| Change Password | `auth/change_password.php` | Password change |
| Manage Login | `auth/manage_login.php` | Login settings |
| Config | `auth/config.php` | Auth config loader |
| Credentials | `auth/credentials.php` | Credential handling |

### Third-Party SPAs

| Modul | Path | Source | Description |
|-------|------|--------|-------------|
| Zashboard | `zashboard/` | Zephyruso | Clash dashboard SPA (Vue) |
| Libernet Plus | `libernet/` | lutfailham96 | Tunnel management SPA (Vue) |
| TinyFM | `tiny/` | prasathmani | File manager (PHP) |
| Kai Admin | `kaiadmin/` | Template | Bootstrap admin template |

## Infrastructure

### PHP Server (`files/bin/`)

| File | Description |
|------|-------------|
| `php` | PHP 7.4.33 CLI (32-bit, embedded) |
| `php.config` | PHP-FPM config |
| `php.ini` | PHP settings |
| `ttyd` | Web terminal binary |
| `msmtp` | SMTP mail client |
| `preferences.sqlite` | SQLite preferences DB |
| `*.so` (70+) | PHP extensions (gd, mbstring, mysqli, pdo, curl, etc.) |

### Service Scripts (`scripts/`)

| File | Function |
|------|----------|
| `php_run` | PHP server start/stop/status/restart |
| `ttyd_run` | ttyd start/stop/status/restart |
| `php_inotifyd` | inotify file watcher for auto-reload |
| `tmux_run` | Tmux session management |

### Boot Scripts (`data/adb/service.d/`)

| File | Function |
|------|----------|
| `autoswitchapn.sh` | Auto detect connection loss, switch APN |
| `hotspot.sh` | Hotspot configuration at boot |
| `ip_static.sh` | Assign static IP to interface |
| `pingloop.sh` | Continuous ping health check |
| `vnstat.sh` | vnStat traffic data collection |

## Module Dependency Graph

```
service.sh
  |-- php_run -> PHP server (:80)
  |     |-- files/www/index.php
  |           |-- extended.php (theme)
  |                 |-- auth/config.php
  |                 |-- auth/login.php
  |                 |-- webui/css/styles.css
  |                 |-- webui/js/iconify.min.js
  |                 |-- webui/monitor/Overview.php
  |                       |-- api/system_info.php
  |                       |-- api/battery_info.php
  |                       |-- api/network_info.php
  |                       |-- api/storage_info.php
  |                       |-- api/signal_info.php
  |                       |-- api/uptime.php
  |                 |-- tools/opsi_box.php
  |                       |-- shell_exec('box.service start')
  |                 |-- tools/powermanager.php
  |                       |-- shell_exec('reboot')
  |                 |-- tools/networktools.php
  |                       |-- exec('iw dev wlan0 scan')
  |                 |-- tools/vnstat.php
  |                       |-- exec('vnstat -d')
  |
  |-- ttyd_run -> ttyd terminal (:3001)
  |
  |-- inotifyd + php_inotifyd
        |-- Watches module dir for changes
```

## PHP Extensions (70+ .so files)

Selected key extensions:
| Extension | Purpose |
|-----------|---------|
| gd.so | Image processing |
| mbstring.so | Multi-byte string |
| mysqli.so | MySQL database |
| pdo_mysql.so | PDO MySQL |
| pdo_pgsql.so | PDO PostgreSQL |
| pdo_odbc.so | PDO ODBC |
| curl.so | cURL | 
| zip.so | ZIP archive |
| bcmath.so | Math operations |
| xml.so | XML parsing |
| json.so | JSON (built-in) |
| iconv.so | Character encoding |
| exif.so | Image metadata |
| ftp.so | FTP client |
| ldap.so | LDAP auth |
| ssh2.so | SSH2 client |
| intl.so | Internationalization |
| soap.so | SOAP client |
| yaml.so | YAML parsing |
| phar.so | PHP archives |
| calendar.so | Calendar functions |
| bz2.so | Bzip2 compression |
| gmp.so | GMP math |
| oauth.so | OAuth provider |
| ev.so / event.so | Event loops |
| eio.so | Async I/O |
| inotify.so | File system events |
| mailparse.so | Email parsing |
| stats.so | Statistical functions |
| xsl.so | XSL transforms |
| yar.so | RPC framework |

## Page Routes

| URL Path | File | Type |
|----------|------|------|
| `/` | `index.php` | SPA Shell |
| `/auth/login.php` | `auth/login.php` | Auth |
| `/about.php` | `about.php` | Info |
| `/article.html` | `article.html` | Docs |
| `/blackbox.php` | `blackbox.php` | Tool |
| `/monitor/Overview.php` | `webui/monitor/Overview.php` | Page |
| `/monitor/CpuMonitor.php` | `webui/monitor/CpuMonitor.php` | Page |
| `/monitor/RamMonitor.php` | `webui/monitor/RamMonitor.php` | Page |
| `/monitor/BatteryMonitor.php` | `webui/monitor/BatteryMonitor.php` | Page |
| `/monitor/StorageMonitor.php` | `webui/monitor/StorageMonitor.php` | Page |
| `/tools/logs.php` | `tools/logs.php` | Page |
| `/tools/opsi_box.php` | `tools/opsi_box.php` | Page |
| `/tools/powermanager.php` | `tools/powermanager.php` | Page |
| `/tools/networktools.php` | `tools/networktools.php` | Page |
| `/tools/network_monitor.php` | `tools/network_monitor.php` | Page |
| `/tools/vnstat.php` | `tools/vnstat.php` | Page |
| `/tools/opsi_interface.php` | `tools/opsi_interface.php` | Page |
| `/tools/net_limiter_control.php` | `tools/net_limiter_control.php` | Page |
| `/tools/hotspot/hotspot.php` | `tools/hotspot/hotspot.php` | Page |
| `/tools/modpes.php` | `tools/modpes.php` | Page |
| `/tools/speedtest/speedtest.php` | `tools/speedtest/speedtest.php` | Page |
| `/tools/adb_tools.php` | `tools/adb_tools.php` | Page |
| `/tools/smsviewer.php` | `tools/smsviewer.php` | Page |
| `/tools/sidompul.php` | `tools/sidompul.php` | Page |
| `/tools/ocgen/index.php` | `tools/ocgen/index.php` | SPA |
| `/tools/bfr/boxsettings.php` | `tools/bfr/boxsettings.php` | Page |
| `/tools/bfr/executed.php` | `tools/bfr/executed.php` | Page |
| `/tiny/opsi.php` | `tiny/opsi.php` | SPA |
| `/libernet/index.php` | `libernet/index.php` | SPA |
| `/zashboard/` | `zashboard/index.html` | SPA (iframe) |
| `:3001` | ttyd | Terminal |
