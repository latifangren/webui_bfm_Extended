# Architecture Codemap

## Refactored Layers

```
┌──────────────────────────────────────────────────────────┐
│  VIEW LAYER (pages/)                                     │
│  ─── HTML templates, pure PHP with minim logic           │
│  pages/network/tools.php, pages/network/monitor.php      │
│  pages/partials/sidebar.php, pages/layouts/default.php   │
├──────────────────────────────────────────────────────────┤
│  SERVICE LAYER (includes/features/)                       │
│  ─── Business logic, data transformation, no shell       │
│  includes/features/network/NetworkService.php            │
├──────────────────────────────────────────────────────────┤
│  COMMAND LAYER (includes/commands/)                       │
│  ─── ALL shell execution — ONE place                     │
│  includes/commands/CommandRunner.php                     │
├──────────────────────────────────────────────────────────┤
│  AUTH LAYER (includes/auth/)                              │
│  ─── Authentication, session management                 │
│  includes/auth/AuthService.php                           │
├──────────────────────────────────────────────────────────┤
│  MODULE LAYER (includes/module/)                          │
│  ─── Module metadata, registry, sidebar generation       │
│  includes/module/ModuleRegistry.php                      │
└──────────────────────────────────────────────────────────┘
```

**Constraint**: File di VIEW layer TIDAK BOLEH panggil shell_exec(). File di VIEW layer TIDAK BOLEH handle login logic. Semua shell execution lewat CommandRunner. Semua auth logic lewat AuthService.

## System Overview

```
[Android Device]
  |
  |-- Magisk/KernelSU Module
  |     |-- /data/adb/php7/
  |           |-- files/bin/php      (PHP7 CLI server)
  |           |-- files/www/          (Document root)
  |           |-- scripts/            (Lifecycle scripts)
  |
  |-- PHP7 embedded server (:80)
  |     |-- Serves files/www/
  |     |-- Auth via PHP sessions
  |     |-- No router - direct file access
  |
  |-- ttyd web terminal (:3001)
  |     |-- Terminal emulator in browser
  |     |-- Full shell access via websocket
  |
  |-- Busybox (Magisk/KernelSU)
        |-- Provides standard Unix tools
        |-- Required for service scripts
```

## Request Lifecycle (Setelah Refactor)

```text
1. Browser -> http://127.0.0.1:80/
     |
2. PHP built-in server handles request
     |
3. index.php loads:
     |  includes/bootstrap.php (autoloader + config + auth helpers)
     |  select_theme/theme.json (theme selection)
     |
4. Auth check via AuthService::requireAuth()
     |
5. pages/layouts/default.php renders SPA shell:
     |  - HTML head (meta, fonts, CSS)
     |  - Sidebar from ModuleRegistry (pages/partials/sidebar.php)
     |  - Content container
     |  - JS: loadContent() using fetch for pages, iframe for SPAs
     |
6. window.onload -> loadContent('/webui/monitor/Overview.php')
     |  fetch() the page, inject HTML into content container
     |
7. Pengguna klik menu sidebar:
     |  loadContent(url) -> fetch(url) -> HTML di-inject
     |
8. NEW: For refactored pages, separation is clear:
     |  a. Router maps URL
     |  b. includes/features/* handles business logic
     |  c. CommandRunner handles shell execution
     |  d. pages/* handles HTML template rendering

LEGACY pages (not yet refactored) still use old pattern:
     |  1. Check session manually
     |  2. Include config manually
     |  3. shell_exec() directly in the file
     |  4. Echo HTML directly
```

## Component Map

### Core Components

```
files/www/
  index.php              Entry point - theme loader
  extended.php           Theme Luci - main UI shell
  auth/
    config.php           Auth config loader
    config.json          Stored credentials
    login.php            Login form + handler
    logout.php           Session destroy
    change_password.php  Password change
    manage_login.php     Login settings management

  webui/
    monitor/
      Overview.php       System overview dashboard
      CpuMonitor.php     CPU usage monitor
      RamMonitor.php     Memory monitor
      BatteryMonitor.php Battery monitor
      StorageMonitor.php Storage monitor
      api/               JSON API endpoints (8 files)
      css/               Monitor stylesheets
      exec/              Execution helpers

  tools/
    dashboard.php        Extended dashboard
    opsi_box.php         BOX management
    powermanager.php     Power management
    logs.php             Log viewer
    networktools.php     Network diagnostics
    network_monitor.php  Ping monitor
    vnstat.php           Bandwidth monitor
    opsi_interface.php   Interface manager
    net_limiter_control.php  Bandwidth limiter
    adb_tools.php        ADB controls
    smsviewer.php        SMS viewer
    modpes.php           Airplane/modem mode
    sidompul.php         Telco balance
    ad_block_test.php    AdBlock test
    dns_leak_test.php    DNS leak test
```

### Service Layer

```
scripts/
  php_run               PHP server control (start/stop/status)
  ttyd_run              ttyd terminal control (start/stop/status)
  php_inotifyd          File watcher for auto-reload
  tmux_run              Tmux session manager
```

### Boot Scripts

```
data/adb/service.d/     Auto-started by service.sh
  autoswitchapn.sh      Monitor + auto-switch APN on connection loss
  hotspot.sh            Hotspot configuration service
  ip_static.sh          Assign static IP to interface
  pingloop.sh           Continuous ping monitoring
  vnstat.sh             vnStat traffic collection
```

## Shell Command Patterns

### System Info
```php
shell_exec('cat /proc/stat');
shell_exec('cat /proc/meminfo');
shell_exec('cat /sys/class/power_supply/battery/*');
```

### Network
```php
shell_exec('ifconfig');
shell_exec('ip addr show');
shell_exec('ping -c 4 ' . $target);
shell_exec('iw dev wlan0 scan');
```

### Package/App
```php
shell_exec('pm list packages');
shell_exec('dumpsys package ' . $package);
shell_exec('am force-stop ' . $package);
```

### System Control
```php
shell_exec('reboot');
shell_exec('svc wifi enable');
shell_exec('settings put global airplane_mode_on 1');
```

## Data Flow

```text
[User Action]
  |
  v
[JavaScript] loadContent(url)
  |
  v
[PHP File] Halaman spesifik
  |
  +-- Include config/auth check
  +-- Collect data via shell_exec() / file_get_contents()
  +-- Process/format data
  +-- Render HTML
  |     +-- Inline CSS
  |     +-- PHP loops for data display
  |     +-- JavaScript for interactivity
  |
  v
[iframe] HTML dirender di dalam iframe
  |
  v
[User] Melihat hasil
```

## Security Boundaries

```text
+--------------------------------------+
|  Browser (LAN)                       |
|  http://127.0.0.1:80                 |
+--------------------------------------+
        |
        | HTTP
        v
+--------------------------------------+
|  PHP Server                          |
|  - Session check                     |
|  - LOGIN_ENABLED gate                |
|  - No CSRF protection                |
|  - No operation allowlist            |
+--------------------------------------+
        |
        | shell_exec() / exec()
        v
+--------------------------------------+
|  Android Shell (root via su)         |
|  - Full system access                |
|  - No sandbox                        |
+--------------------------------------+
```
