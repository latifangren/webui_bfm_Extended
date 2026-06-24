# Instalasi & Deployment

BOX UI Extended berjalan sebagai Magisk/KernelSU module pada perangkat Android rooted.

## Requirements

- Perangkat Android dengan root (Magisk 24+ atau KernelSU).
- Minimal Android 8.0 (API level 26).
- Termux (untuk beberapa fitur tambahan).
- Koneksi internet (untuk update dan beberapa fitur).

## Default Runtime

```text
PHP Server:    127.0.0.1:80
ttyd Terminal: 127.0.0.1:3001
PHP Binary:    /data/adb/php7/files/bin/php
Web Root:      /data/adb/php7/files/www/
Config:        /data/adb/php7/files/config/
Scripts:       /data/adb/php7/scripts/
Tmp:           /data/adb/php7/files/tmp/
```

## Install via Magisk Manager

1. Download module ZIP dari [GitHub Releases](https://github.com/latifangren/webui_bfm_Extended/releases).
2. Buka Magisk Manager -> Modules -> Install from storage.
3. Pilih file ZIP.
4. Reboot device setelah install selesai.

## Install Manual

```sh
# Flash via custom recovery (TWRP)
adb push webui_bfm_Extended-V2.1.1.zip /sdcard/
# Install via Magisk
adb shell
su
magisk --install-module /sdcard/webui_bfm_Extended-V2.1.1.zip
reboot
```

## Post-Install

Setelah reboot, akses panel via browser:
- `http://127.0.0.1:80`
- `http://127.0.0.1` (default port 80)

### Login Default
- **Username**: admin
- **Password**: 12345

## Struktur Module

```
module.prop              # Metadata module Magisk
customize.sh             # Installer script
service.sh               # Boot service script
uninstall.sh             # Uninstall cleanup
update.json              # OTA update config
data/adb/service.d/      # Auto-start service scripts
  autoswitchapn.sh       # APN auto-switch
  hotspot.sh             # Hotspot config
  ip_static.sh           # Static IP
  pingloop.sh            # Ping loop
  vnstat.sh              # vnStat monitor
files/
  bin/                   # PHP binary + extensions
    php                  # PHP 7.4 interpreter (32-bit)
    php.config           # PHP configuration
    php.ini              # PHP ini settings
    ttyd                 # Web terminal binary
    *.so                 # 70+ PHP extensions
  config/                # PHP config files
  tmp/                   # Temp directory
  www/                   # Web document root
scripts/                 # Service scripts
META-INF/                # Magisk installer metadata
```

## Build dari Source

```sh
# Module sudah pre-built, tinggal zip
git clone https://github.com/latifangren/webui_bfm_Extended
# Edit files jika perlu
# ZIP module
zip -r webui_bfm_Extended-V2.1.1.zip . -x ".git/*"
```

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `PHP_PORT` | `80` | PHP server port |
| `TTYD_PORT` | `3001` | ttyd terminal port |
| `PHP_BIND` | `127.0.0.1` | PHP bind address |

## Service Management

```sh
# Melalui web UI
/tools/opsi_box.php     # BOX service control
/tools/powermanager.php # Power management

# Melalui shell (ADB/Termux)
/data/adb/php7/scripts/php_run -s      # Start PHP server
/data/adb/php7/scripts/php_run -k      # Stop PHP server
/data/adb/php7/scripts/ttyd_run -s     # Start ttyd
/data/adb/php7/scripts/ttyd_run -k     # Stop ttyd
```

## Update

Module mendukung OTA update via Magisk Manager:
1. Buka Magisk Manager.
2. Modules -> Universal BFR WEBUI -> Update.
3. Atau download ZIP baru dan install seperti fresh install.

## Uninstall

1. Buka Magisk Manager -> Modules.
2. Tap Remove pada Universal BFR WEBUI.
3. Reboot device.

Atau via shell:
```sh
su
magisk --remove-module UNIVERSALBFRWEBUI
reboot
```

## Catatan

- Module bekerja dengan PHP 7.4 32-bit embedded server.
- Beberapa fitur membutuhkan Termux (vnstat, speedtest local).
- Untuk akses remote, gunakan ADB forward: `adb forward tcp:8080 tcp:80`.
- ttyd terminal bind ke 127.0.0.1:3001, akses via `http://device-ip:3001`.
