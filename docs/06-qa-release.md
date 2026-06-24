# QA & Release

## Device QA Checklist

Gunakan pada device rooted test. Jangan pada primary device tanpa backup.

### Preflight
- Module ZIP siap: `webui_bfm_Extended-V2.1.1.zip`
- Module contains: `module.prop`, `customize.sh`, `service.sh`, `uninstall.sh`
- PHP binary dan 70+ extensions ada di `files/bin/`
- Konfigurasi auth: default credentials

### Install
- Install via Magisk Manager.
- Reboot device.
- Konfirmasi `/data/adb/php7/` created.
- Konfirmasi `files/bin/php` executable.

### Runtime
- PHP server berjalan: `ps | grep php`
- ttyd berjalan: `ps | grep ttyd`
- Web UI reachable di `http://127.0.0.1:80`
- Login dengan default credentials berhasil.
- Semua halaman tools render tanpa error PHP.

### Module Check
- Sidebar navigasi muncul dengan semua kategori.
- Dropdown bekerja (open/close).
- iframe load konten untuk setiap menu item.
- Loading spinner muncul saat konten dimuat.
- Logout berfungsi.

### Feature Testing (Spot Check)

| Feature | Test Case | Expected |
|---------|-----------|----------|
| Overview | Load dashboard | System info tampil |
| CPU Monitor | Load page | CPU cores + usage |
| RAM Monitor | Load page | Memory details |
| Battery | Load page | Battery status |
| File Manager | Browse, open file | File list, content |
| Power Manager | View options | Reboot buttons |
| Network Tools | Scan WiFi | Network list |
| Config Generator | Load page | Form appears |
| BOX Settings | Load page | Config form |
| SMS Viewer | Load page | SMS list |

### Danger Zone
- Power Manager: reboot/shutdown buttons visible.
- File Manager: delete confirmation prompt?
- ADB Tools: WiFi ADB enable/disable.

### Network Features
- Hotspot: toggle UI renders.
- Network Monitor: ping test runs.
- Speed Test: test executes.
- vnStat: traffic data displays.
- NetLimiter: block/limit controls visible.

### Browser Compatibility
- Chrome/Chromium: full functionality.
- Firefox: full functionality.
- Mobile browser: responsive layout.
- Multiple tabs: session preserved.

## Release Process

### Versioning
`v<MAJOR>.<MINOR>.<PATCH>-Extended`

Example: `v2.1.1-Extended`

### Pre-Release Checklist
1. Update `module.prop` version + versionCode.
2. Update `update.json` with new version URL.
3. Update `CHANGELOG.md` with changes.
4. Test install on clean device.
5. Test upgrade from previous version.
6. Spot-check semua modul fitur.
7. Build ZIP.
8. Verify ZIP structure.

### Artifact Names
```text
webui_bfm_argon_Extended-V<version>.zip
```

### Update JSON Format
```json
{
  "version": "v2.1.1 Extended",
  "versionCode": "20250626",
  "zipUrl": "https://github.com/latifangren/webui_bfm_Extended/releases/...",
  "changelog": "https://github.com/latifangren/.../CHANGELOG.md"
}
```

## Known Issues

| Issue | Workaround |
|-------|------------|
| PHP error di beberapa halaman | Refresh page |
| iframe height mismatch | Auto-adjusted via JS |
| File Manager scroll issue | Use desktop view |
| Session timeout tidak konsisten | Relogin |
