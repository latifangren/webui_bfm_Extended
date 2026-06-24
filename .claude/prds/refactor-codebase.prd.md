# Refactor Codebase — BOX UI Extended

## Problem

Codebase BOX UI Extended saat ini adalah PHP monolith tanpa separation of concerns — shell execution (`shell_exec()`), query parameter handling, HTML rendering, inline CSS, dan JavaScript semuanya campur dalam satu file PHP. Tidak ada routing terpusat, tidak ada autoloading, tidak ada layer service/commands yang terpisah. Maintainer susah cari logic tertentu, risk regresi tinggi, dan mustahil di-test. Network tools (15+ file) adalah area paling kacau dengan logic terduplikasi dan shell execution dari input user tanpa sanitasi di banyak tempat.

## Evidence

- **Codebase audit**: 60+ file PHP dengan pola `shell_exec()` + echo HTML dalam file yang sama (`tools/networktools.php`, `tools/network_monitor.php`, `tools/powermanager.php`, dll).
- **Tidak ada routing terpusat**: setiap URL path = file PHP langsung di document root. Tidak ada satu entry point yang handle routing.
- **Duplikasi logic**: pattern `shell_exec()` untuk command yang sama (misal `getprop`, `ifconfig`) diulang di banyak file.
- **Dokumentasi** `docs/02-security-model.md` mencatat 9 gap keamanan — semua rooted dari arsitektur monolith.
- **Observasi maintainer**: network tools disebut "paling kacau" — indikator langsung dari orang yang ngurus codebase tiap hari.

## Users

- **Primary**: LATIFAN (maintainer tunggal) — ingin nambah fitur, fix bug, dan maintain codebase tanpa takut sesuatu break di tempat lain.
- **Not for**: end-user device yang cuma pakai panel — refactor tidak boleh ubah UX.

## Hypothesis

Kita percaya **refactor ke struktur layered dengan pemisahan backend logic, frontend rendering, dan shell execution** akan **membuat codebase gampang di-maintain, aman untuk diedit, dan bisa di-test** untuk **maintainer**. Kita akan tau kita benar ketika **network tools yang sekarang kacau punya struktur jelas (service layer terpisah dari template) dan maintainer bisa nambah fitur baru tanpa takut regresi**.

## Success Metrics

| Metric | Target | How measured |
|--------|--------|-------------|
| Separation | 0 file PHP yang campur shell_exec + HTML | Code review — tiap file cuma punya 1 tanggung jawab |
| Shell execution | Terpusat di 1 layer (`includes/commands/`) | `grep -r "shell_exec\|exec(" files/www/ --include="*.php"` cuma di commands layer |
| Duplikasi network tools | 0 duplikasi command strings | Code review |
| Testable | Service layer bisa di-test dengan mock shell | Minimal ada test structure |

## Scope

**MVP** — Refactor struktur folder dan pisahin logic untuk network tools (area paling kacau). Struktur baru mirip AegisDroid tapi pake PHP:

```
files/www/
  index.php                 # SPA shell (stay, mungkin di-slim)
  router.php                # Simple front controller (baru)
  pages/                    # Template/view layer (pisah dari logic)
    partials/               # HTMX-style partials
  includes/                 # Backend logic
    bootstrap.php           # Autoload, config load
    auth/                   # Auth logic
    module/                 # Module registry
    commands/               # Semua shell execution terpusat
    features/               # Service logic per fitur
```

**Deliverables:**
1. **Struktur folder baru** — `includes/` buat backend, pisah dari `pages/` buat view.
2. **Commands layer** — semua `shell_exec()` / `exec()` cuma lewat `includes/commands/`. Typed operations, bukan raw string.
3. **Feature packages** — tiap fitur punya folder sendiri di `includes/features/<nama>/` dengan `service.php` (logic) dan `module.php` (metadata).
4. **Module registry** — tiap fitur daftar sebagai module dengan metadata (ID, nama, kategori, risk level).
5. **Template pisah** — file view di `pages/` atau `pages/partials/`, pure HTML dengan PHP minim.
6. **Auth system di-refactor** — jadi service layer terpisah, bukan inline session check di tiap file.
7. **Hapus** Libernet, Zashboard dari distribusi.
8. **Router.php** — front controller sederhana ganti akses langsung ke file.

**Network tools prioritas refactor (yang paling kacau duluan):**
1. Network Monitor (ping) — `tools/network_monitor.php`
2. Network Tools (WiFi) — `tools/networktools.php`
3. Speed Test — `tools/speedtest/speedtest.php`
4. Bandwidth Monitor (vnStat) — `tools/vnstat.php`
5. Interface Manager — `tools/opsi_interface.php`
6. NetLimiter — `tools/net_limiter_control.php`
7. Hotspot — `tools/hotspot/hotspot.php`
8. Airplane Pilot — `tools/modpes.php`
9. APN auto-switch — `data/adb/service.d/autoswitchapn.sh`

**Out of scope**
- Tiny File Manager (`tiny/`) — terlalu besar, tetap sebagai third-party atau di-port belakangan.
- Monitor pages (CPU/RAM/Battery/Storage) — port setelah network tools stabil.
- Config Generator (OcGen) — port belakangan.
- SMS Viewer, ADB Tools, Sidompul — port belakangan.
- UI redesign — struktur berubah, tapi tampilan tetap sama.
- Migrasi ke framework PHP (Laravel dll) — tetap pure PHP kaya sekarang.

## Delivery Milestones

| # | Milestone | Outcome | Status | Plan |
|---|-----------|---------|--------|------|
| 1 | Struktur folder baru | `includes/` (bootstrap, auth, module, commands, features), `pages/` (views, partials), `router.php` | pending | — |
| 2 | Bootstrap + Autoload | Satu entry point, PSR-4 style autoloading, config centralized | pending | — |
| 3 | Commands layer | Semua shell execution di `includes/commands/`, typed operations, params validation | pending | — |
| 4 | Module registry | Tiap fitur daftar dengan metadata, centralized registry | pending | — |
| 5 | Auth service | Auth logic pindah ke `includes/auth/`, session + CSRF + config centralized | pending | — |
| 6 | 3 network tools pertama | Network Monitor, Network Tools, Speed Test — refactor ke service + view pattern | pending | — |
| 7 | Sisa network tools | vnStat, Interface, NetLimiter, Hotspot, Airplane, APN auto-switch | pending | — |
| 8 | Router + cleanup | Router.php aktif, hapus Libernet/Zashboard, verify semua rute lama jalan | pending | — |

## Open Questions

- [ ] Apakah `router.php` perlu nanganin semua request, atau cukup untuk tools baru? (Backward compat untuk file lama?)
- [ ] Apakah session handling tetap pake PHP native sessions atau pindah ke cookie-based custom?
- [ ] Apakah module registry dipake buat generate sidebar otomatis?

## Risks

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| Refactor terlalu lama dan tidak selesai | Medium | HIGH | Prioritaskan MVP — struktur folder + 3 network tools dulu |
| Backward compatibility rusak | Medium | HIGH | Router maintain old paths, test tiap halaman jalan |
| Hilangnya fitur saat refactor | Medium | MEDIUM | Feature parity checklist tiap milestone |
| PHP version limitations (7.4) | Low | MEDIUM | Stay within PHP 7.4 compatible syntax |

---
*Status: DRAFT — requirements only. Implementation planning pending via /plan.*
