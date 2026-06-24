# Roadmap

**Fase pengembangan BOX UI Extended.** Prioritas: stabilkan existing features, tambah fitur baru, kemudian refactor.

---

## Phase 0: Foundation (Complete)

Project layout, PHP7 webserver, basic web UI.

- [x] PHP7 embedded server (32-bit).
- [x] 70+ PHP extensions compiled.
- [x] Theme system (Luci + Argon).
- [x] Auth system (login/logout/change password).
- [x] Sidebar navigation with iframe SPA.
- [x] ttyd web terminal.
- [x] inotifyd auto-reload.

---

## Phase 1: Core Features (Complete)

Fitur dasar manajemen BOX dan system monitoring.

- [x] System Overview dashboard.
- [x] CPU Monitor dengan real-time chart.
- [x] RAM Monitor + proses list.
- [x] Battery Monitor detail.
- [x] Storage Monitor multi-partition.
- [x] BOX For Magisk settings.
- [x] BOX service lifecycle (start/stop/restart).
- [x] Magisk Log viewer.
- [x] File Manager (TinyFM).

---

## Phase 2: Network Tools (Complete)

Tools jaringan komprehensif.

- [x] Network Monitor (ping + charts).
- [x] Network Tools (WiFi scan/connect).
- [x] Bandwidth Monitor (vnStat).
- [x] Interface Manager.
- [x] NetLimiter (iptables).
- [x] Wireless Hotspot control.
- [x] Airplane Pilot (modem mode).
- [x] Speed Test (online + local).
- [x] DNS Leak Test.
- [x] AdBlock Test.

---

## Phase 3: Extended Features (Complete)

Fitur tambahan dan integrasi third-party.

- [x] Config Generator (VMess/VLESS/Trojan/SS).
- [x] Zashboard Clash dashboard.
- [x] Libernet Plus tunnel manager.
- [x] ADB Tools (WiFi ADB).
- [x] SMS Viewer.
- [x] Sidompul (telco balance).
- [x] APN auto-switch.
- [x] OTA update via Magisk Manager.
- [x] Local fonts (no CDN dependency).

---

## Phase 4: Polish & Optimization (Current)

Optimasi performa, UI polish, bug fixes.

- [x] Migrasi Font Awesome ke lokal.
- [x] Migrasi Iconify ke lokal.
- [x] Optimasi dashboard loading.
- [x] Dark mode improvements.
- [x] Mobile responsive fixes.
- [x] Theme simplifikasi (remove Akun menu).
- [x] AMOLED-friendly dark default.

### In Progress
- [ ] Perbaikan iframe height/resize.
- [ ] Optimasi PHP session handling.
- [ ] Error page yang lebih informatif.
- [ ] Loading state improvements.

---

## Phase 5: Security Hardening (Planned)

Perbaikan gap keamanan yang diketahui.

- [ ] Operation allowlist untuk shell commands.
- [ ] Input validation/sanitasi.
- [ ] CSRF protection.
- [ ] Session security (HttpOnly, SameSite).
- [ ] Audit logging.
- [ ] Rate limiting.
- [ ] Change default credentials on first login.

---

## Phase 6: Code Quality (Planned)

Refactor kode PHP legacy.

- [ ] Separation of concerns (MVC pattern).
- [ ] Centralized routing.
- [ ] Template engine.
- [ ] Error handling yang konsisten.
- [ ] Code linting (PHPCS).
- [ ] Unit testing.

---

## Phase 7: New Features (Backlog)

Fitur baru yang direncanakan.

- [ ] Multi-user support.
- [ ] Dark mode toggle per-user.
- [ ] Theme editor UI.
- [ ] Scheduled tasks (cron).
- [ ] Backup/restore config.
- [ ] Network speed chart history.
- [ ] WireGuard manager.
- [ ] Real-time log streaming (WebSocket).
- [ ] Push notification (reboot complete, etc).

---

## Version History

| Version | Date | Highlights |
|---------|------|------------|
| v2.1.1 | 2025-06-26 | DNS leak test, CPU/RAM/Battery/Storage realtime monitor, AdBlock test, Libernet Plus |
| v2.0.0 | 2025-03-18 | Auto APN switch, dashboard redesign, RAM/CPU optimasi, Android 14 support |
| v1.0.11 | 2025-03-17 | Migrasi Font Awesome + Iconify + Roboto ke lokal |
| v1.0.10 | 2025-03-16 | Theme warna baru (hitam/kuning/putih), AMOLED default, simplifikasi dark mode |
| v1.0.9 | 2025-03-15 | Speed Test, Ping Monitor modern, Dark Mode, Dokumentasi, OTA update |
| v1.0.8 | 2025-03-15 | Auto update di Magisk Manager, ADB dir access, WebUI dir access |
| v1.0.7 | 2025-03-13 | Hotspot manager, Network tools, CPU Monitor, Clash Dashboard |
