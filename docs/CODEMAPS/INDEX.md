# BOX UI Extended Codemap Index

**Last Updated:** 2026-06-25
**Source:** PHP 7.4 | Embedded Server | JavaScript | Magisk Module

## Quick Navigation

| Codemap | Purpose |
|---------|---------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | System overview, data flow, component relationships |
| [MODULES.md](MODULES.md) | Feature modules, routes, dependency graph |
| [FILES.md](FILES.md) | Complete file tree, file purposes, key files |

## Repository at a Glance

```
webui_bfm_Extended/
|-- module.prop              # Magisk module metadata
|-- customize.sh             # Module installer
|-- service.sh               # Boot service
|-- uninstall.sh             # Cleanup
|-- update.json              # OTA config
|-- CHANGELOG.md             # Version history
|-- README.md                # Project overview
|-- LICENSE                  # MIT license
|-- data/adb/service.d/      # Auto-start scripts (5 scripts)
|-- files/
|   |-- bin/                 # PHP binary + 70+ extensions
|   |-- config/              # PHP config files
|   |-- tmp/                 # Temp storage
|   |-- www/                 # Web document root
|       |-- index.php        # SPA entry point (refactored — loads bootstrap + uses layout)
|       |-- router.php       # Front controller (new)
|       |-- extended.php     # Theme Luci (legacy fallback)
|       |-- includes/        # Backend logic (NEW — refactored)
|       |   |-- bootstrap.php    # Autoloader, config, auth guard helpers
|       |   |-- helpers.php      # Pure helper functions
|       |   |-- auth/            # Auth service (refactored from auth/*.php)
|       |   |-- commands/        # Shell execution layer (ALL shell_exec() here)
|       |   |-- module/          # Module registry for sidebar generation
|       |   |-- features/        # Business logic per feature domain
|       |       |-- network/     # Network service (NetworkService.php)
|       |       |-- monitor/     # System monitor service
|       |       |-- system/      # System service
|       |       |-- box/         # BOX service
|       |-- pages/           # View/template layer (NEW — pisah dari logic)
|       |   |-- layouts/        # Layout templates (default.php)
|       |   |-- partials/       # Sidebar, header partials
|       |   |-- auth/           # Login page view
|       |   |-- network/        # Network views (tools.php, monitor.php)
|       |   |-- monitor/        # Monitor views
|       |   |-- system/         # System views
|       |   |-- box/            # BOX views
|       |-- api/                  # JSON API endpoints
|       |   |-- clash_status.php  # Clash status API (new)
|       |-- auth/               # Auth pages (refactored to use AuthService)
|       |-- kaiadmin/           # Bootstrap template assets
|       |-- select_theme/       # Theme selector
|       |-- tiny/               # Tiny File Manager (legacy)
|       |-- tools/              # Feature tools (refactored + renamed files)
|       |   |-- interface_manager.php   # Renamed from opsi_interface.php
|       |   |-- box_manager.php         # Renamed from opsi_box.php
|       |   |-- airplane_pilot.php     # Renamed from modpes.php
|       |   |-- bandwidth_monitor.php   # Renamed from vnstat.php
|       |   |-- networktools_handler.php # POST handler (new — extracted from networktools.php)
|       |   |-- ...  (original files tetap ada untuk backward compat)
|       |-- webui/             # Theme assets (CSS, fonts, JS)
|       |-- zashboard/         # Clash dashboard SPA
|-- scripts/                 # Service scripts (4 files)
|-- META-INF/                # Magisk installer
```

## Key Numbers

- **PHP files:** 60+ (tools, auth, monitor)
- **PHP Extensions:** 70+ (.so files)
- **Feature modules:** 20+
- **Third-party SPAs:** 3 (Zashboard, Libernet, KaiAdmin)
- **CSS files:** 12+
- **JS files:** 15+
- **Service scripts:** 4
- **Auto-start scripts:** 5
- **External dependencies (CDN):** 0 (all migrated to local)

## Architecture Pattern

```
Browser
  |
  v
PHP7 embedded server (:80)
  |
  +-- index.php (SPA shell)
  |     +-- extended.php (theme)
  |           +-- sidebar + iframe container
  |
  +-- iframe -> setiap halaman PHP sebagai endpoint sendiri
  |     +-- auth/login.php
  |     +-- tools/opsi_box.php
  |     +-- webui/monitor/Overview.php
  |     +-- ...
  |
  +-- Shell execution via shell_exec()/exec()
  |     +-- Output langsung ke HTML
  |
  +-- Third-party SPAs via iframe
        +-- zashboard/ (Clash)
        +-- libernet/ (Tunnel)
```

## Design Principles

1. **File-as-endpoint** - Setiap file PHP adalah halaman dan routing sendiri.
2. **iframe SPA** - Sidebar + iframe untuk navigasi tanpa reload.
3. **PHP-first** - Semua logic server-side dengan PHP.
4. **Local-first** - Semua assets lokal (fonts, icons, JS libraries) - zero CDN dependency.
5. **Shell direct** - Operasi system via shell_exec() langsung dari PHP.
6. **Theme-based** - Multiple themes bisa dipilih via JSON config.
7. **Session auth** - Auth sederhana dengan PHP native sessions.
