# Arsitektur

BOX UI Extended adalah WebUI berbasis PHP7 yang berjalan di perangkat Android rooted melalui Magisk atau KernelSU. Server PHP7 embedded melayani halaman web yang dirender di sisi server dengan arsitektur iframe-based SPA.

## Topologi

```text
Browser (phone/tablet/PC)
  |
  | HTTP
  v
PHP7 embedded server (127.0.0.1:80 default)
  |
  +-- Auth layer: session-based PHP auth
  +-- index.php: SPA shell + sidebar navigasi
  +-- iframe: load semua konten halaman via iframe
  +-- files/www: halaman PHP untuk setiap fitur
  +-- files/www/auth: login, logout, change password
  +-- files/www/tools: tools fitur (network, box, monitor)
  +-- files/www/webui: monitor dashboard, assets CSS/JS
  +-- files/www/tiny: Tiny File Manager
  +-- files/www/zashboard: Clash dashboard SPA
  +-- files/www/libernet: Libernet Plus SPA
```

## Stack

| Layer | Teknologi |
|-------|-----------|
| Web Server | PHP7 embedded (CLI server) |
| Backend | PHP 7.4+ dengan ekstensi (70+ .so extensions) |
| Frontend | HTML + CSS + JavaScript (vanilla) |
| Icons | Font Awesome 6, Iconify, Material Icons |
| Styling | Pure CSS dengan multiple themes (Luci, Argon) |
| File Manager | Tiny File Manager (PHP) |
| Terminal | ttyd (Web terminal) |
| Charts | Chart.js |
| Proxy Dashboard | Zashboard (Vue SPA) |
| Tunnel Manager | Libernet Plus (Vue SPA) |

## Alur Request

1. Browser request ke `http://127.0.0.1:80`.
2. `index.php` memeriksa theme JSON, lalu include file theme.
3. Theme file (extended.php) render SPA shell: sidebar + iframe container.
4. Semua konten fitur dimuat via `loadContent()` JS yang inject iframe.
5. Setiap halaman PHP menangani auth, business logic, dan rendering sendiri.
6. Operasi shell dijalankan via `shell_exec()`, `exec()`, atau `passthru()`.
7. Tidak ada routing terpusat - setiap file PHP adalah endpoint sendiri.

## Struktur Direktori (Setelah Refactor)

```
/data/adb/php7/
  files/
    bin/                PHP binary + 70+ extensions (.so)
    config/             php.config, php.ini
    tmp/                Temporary files
    www/                Document root - semua halaman web
      index.php         SPA entry point (refactored — loads bootstrap + layout)
      router.php        Front controller (new)
      extended.php      Theme Luci (legacy fallback)
      
      includes/         BACKEND LOGIC (refactored — pisah dari view)
        bootstrap.php   Autoloader, constants, auth guard helpers
        helpers.php     Pure helper functions
        auth/           
          AuthService.php   Auth logic centralized
        commands/
          CommandRunner.php ALL shell execution terpusat
        module/
          ModuleRegistry.php Module metadata + sidebar generation
        features/
          network/      Network service (NetworkService.php)
          monitor/      System monitoring service
          system/       System operations service
          box/          BOX service
      
      pages/            VIEW/TEMPLATE LAYER (refactored — pisah dari logic)
        layouts/
          default.php   Main SPA shell layout
        partials/
          sidebar.php   Sidebar from ModuleRegistry
        auth/
          login.php     Login template
        network/
          tools.php     Network tools view
          monitor.php   Network monitor view
        monitor/
        system/
        box/
      
      api/              JSON API endpoints
        clash_status.php   Clash status (for sidebar indicator)
      
      auth/             Auth pages (refactored — use AuthService)
        login.php
        logout.php
        change_password.php
        config.php
        config.json
        credentials.php
      
      kaiadmin/         Kai Admin template assets
      select_theme/     Theme selector
      tiny/             Tiny File Manager (legacy — deferred)
      tools/            Feature tools (legacy + renamed files)
        bfr/            BOX settings
        hotspot/        Hotspot manager
        interface/      Interface management
        logs/           Debug logs
        ocgen/          Config generator
        speedtest/      Speed test
        tutorial/       Tutorial page
        interface_manager.php   # Renamed from opsi_interface.php
        box_manager.php         # Renamed from opsi_box.php
        airplane_pilot.php     # Renamed from modpes.php
        bandwidth_monitor.php   # Renamed from vnstat.php
        networktools_handler.php # POST handler (extracted from networktools.php)
        ... (original files tetap ada untuk backward compat)
      webui/            WebUI theme assets (CSS, fonts, JS)
      zashboard/        Clash dashboard SPA
  scripts/              Shell scripts (php_run, ttyd_run, etc.)
```

## Alur Boot (service.sh)

1. Tunggu boot animation selesai (`init.svc.bootanim`).
2. Deteksi busybox: Magisk (`/data/adb/magisk/busybox`) atau KernelSU (`/data/adb/ksu/bin/busybox`).
3. Bersihkan PID files lama.
4. Start PHP7 server via `php_run -s`.
5. Start ttyd terminal via `ttyd_run -s`.
6. Start inotifyd monitor untuk auto-reload module.

## Dependency Direction

Allowed:
- Halaman PHP manapun bisa include file lain (`include`, `require`).
- Tools bisa mengakses shell commands via `shell_exec()`, `exec()`.
- Halaman bisa memanggil script di `/data/adb/php7/scripts/`.

Forbidden:
- Tidak ada arsitektur MVC yang ketat.
- Tidak ada autoloading PSR - semua manual include.
- Tidak ada separation of concerns yang formal.

## Catatan Arsitektur

Proyek ini adalah **legacy PHP monolith** dengan arsitektur SPA sederhana:

- **Entry point**: `index.php` -> `extended.php` (atau theme lain)
- **Routing**: Tidak ada router - setiap URL path adalah file PHP langsung
- **State management**: PHP sessions untuk auth, JavaScript DOM manipulation untuk UI
- **Shell execution**: Langsung dari halaman PHP via `shell_exec()` / `exec()`
- **Keamanan**: Auth session-based, LOGIN_ENABLED config, setiap halaman cek session sendiri
