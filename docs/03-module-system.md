# Modul System

BOX UI Extended memiliki arsitektur modul berbasis file PHP individual. Setiap file di `files/www/tools/` atau `files/www/webui/monitor/` adalah modul independen yang menangani satu fitur.

## Kategori Modul

| Kategori | Deskripsi | Contoh |
|----------|-----------|--------|
| **Monitor** | Pemantauan sistem real-time | CPU, RAM, Battery, Storage |
| **Jaringan** | Tools jaringan dan konektivitas | Network Tools, Hotspot, APN |
| **System** | Manajemen sistem | Power Manager, File Manager |
| **Services** | Layanan tambahan | ADB, SMS, Config Generator |
| **Box** | Manajemen proxy core | BFR Settings, Libernet |
| **Dashboard** | Clash dashboard SPA | Default, Zashboard |

## Daftar Modul Lengkap

### System Monitoring

| Modul | File | Deskripsi | Data Source |
|-------|------|-----------|-------------|
| Overview | `webui/monitor/Overview.php` | System overview dashboard | `/proc/`, shell commands |
| CPU Monitor | `webui/monitor/CpuMonitor.php` | Per-core CPU usage & frequency | `/proc/stat`, cpu sysfs |
| RAM Monitor | `webui/monitor/RamMonitor.php` | Memory usage, swap, processes | `/proc/meminfo`, `ps` |
| Battery Monitor | `webui/monitor/BatteryMonitor.php` | Battery health, charge, temp | `/sys/class/power_supply/` |
| Storage Monitor | `webui/monitor/StorageMonitor.php` | Disk usage, partitions | `df`, `mount` |

### API Endpoints (Monitor)

| Endpoint | File | Data |
|----------|------|------|
| `api/battery_info.php` | Informasi battery detail | SysFS reads |
| `api/network_info.php` | Network interfaces | `/proc/net/dev` |
| `api/network_interface_info.php` | Interface detail | `ip` commands |
| `api/signal_info.php` | Cellular signal | `dumpsys telephony` |
| `api/storage_info.php` | Storage partitions | `df`, `mount` |
| `api/system_info.php` | System info | `/proc/`, `getprop` |
| `api/uptime.php` | Uptime reading | `/proc/uptime` |
| `api/helpers.php` | Shared helper functions | - |

### Network Tools

| Modul | File | Deskripsi |
|-------|------|-----------|
| Network Monitor | `tools/network_monitor.php` | Ping monitor with charts |
| Network Tools | `tools/networktools.php` | WiFi scan, connect, network diagnostics |
| Bandwidth Monitor | `tools/vnstat.php` | vnStat traffic statistics |
| Interface Manager | `tools/opsi_interface.php` | Network interface management |
| Interface Config | `tools/interface/interface.php` | Interface configuration |
| IP Settings | `tools/interface/ipset.php` | Static IP assignment |
| Interface Script | `tools/interface/script.php` | Interface automation scripts |
| NetLimiter | `tools/net_limiter_control.php` | Client bandwidth limiter (iptables) |
| Wireless Hotspot | `tools/hotspot/hotspot.php` | Hotspot control panel |
| Hotspot Process | `tools/hotspot/process_hotspot.php` | Hotspot backend processing |
| Airplane Pilot | `tools/modpes.php` | Modem mode (airplane + hotspot) |
| Speed Test | `tools/speedtest/speedtest.php` | Internet speed test |
| Speed Test Local | `tools/speedtest/local/` | Local network speed test |
| DNS Leak Test | `tools/dns_leak_test.php` | DNS leak detection |
| AdBlock Test | `tools/ad_block_test.php` | Ad blocking verification |

### System Tools

| Modul | File | Deskripsi |
|-------|------|-----------|
| Power Manager | `tools/powermanager.php` | Reboot, shutdown, recovery |
| File Manager | `tiny/index.php` | Full file manager (TinyFM) |
| BOX Settings | `tools/bfr/boxsettings.php` | BOX For Magisk configuration |
| BOX Execution | `tools/bfr/executed.php` | BOX service lifecycle |
| BOX Options | `tools/opsi_box.php` | BOX management menu |
| Magisk Log | `tools/logs.php` | Module and system logs |
| Config Generator | `tools/ocgen/index.php` | Proxy config generator |
| Libernet Plus | `libernet/index.php` | Tunnel management SPA |

### Services

| Modul | File | Deskripsi |
|-------|------|-----------|
| ADB Tools | `tools/adb_tools.php` | ADB over WiFi controls |
| SMS Viewer | `tools/smsviewer.php` | SMS inbox via web UI |
| Sidompul | `tools/sidompul.php` | Indonesian telco balance check |
| Terminal | ttyd (`:3001`) | Web terminal via ttyd |

### Authentication

| Modul | File | Deskripsi |
|-------|------|-----------|
| Login | `auth/login.php` | Login form + authentication |
| Logout | `auth/logout.php` | Session destroy |
| Change Password | `auth/change_password.php` | Password change form |
| Manage Login | `auth/manage_login.php` | Login settings management |
| Config | `auth/config.php` | Auth configuration loader |

### Third-Party SPAs

| Modul | Path | Source |
|-------|------|--------|
| Zashboard | `zashboard/` | Clash dashboard (Vue SPA) |
| Libernet Plus | `libernet/` | Tunnel manager (Vue SPA) |
| File Manager | `tiny/` | Tiny File Manager (PHP) |
| Kai Admin | `kaiadmin/` | Bootstrap admin template |

## Service Scripts

| Script | Path | Fungsi |
|--------|------|--------|
| `php_run` | `scripts/php_run` | PHP7 server start/stop/status |
| `ttyd_run` | `scripts/ttyd_run` | ttyd terminal start/stop/status |
| `php_inotifyd` | `scripts/php_inotifyd` | Auto-reload on file changes |
| `tmux_run` | `scripts/tmux_run` | Tmux session manager |

## Auto-Start Scripts (service.d)

| Script | Fungsi |
|--------|--------|
| `autoswitchapn.sh` | APN auto-switch monitoring |
| `hotspot.sh` | Hotspot configuration |
| `ip_static.sh` | Static IP assignment |
| `pingloop.sh` | Ping monitoring loop |
| `vnstat.sh` | vnStat traffic monitor |
