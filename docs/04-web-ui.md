# Web UI

BOX UI Extended menggunakan arsitektur **iframe-based SPA** dengan sidebar navigasi dan konten dimuat di iframe.

## Layout Structure

```text
+------------------------------------------+
|  Decorative Header (BOX UI XTD v2.1.1)   |
+----------+-------------------------------+
| Sidebar  |                               |
|          |   iframe Container            |
| Clash    |   (loadContent via JS)        |
| Status   |                               |
| System   |                               |
| Services |                               |
| Box      |                               |
| Network  |                               |
| About    |                               |
| Docs     |                               |
| Update   |                               |
| Logout   |                               |
+----------+-------------------------------+
```

## Theme System

Proyek mendukung multiple themes yang dipilih via `select_theme/theme.json`:

```json
{
  "path": "extended"
}
```

### Theme Luci (Default - Extended)

File: `extended.php`

Palette warna:
- Primary: Hitam (`#000000`)
- Accent: Kuning (`#FECA0A`)
- Text: Putih (`#F1F1F1`)
- Icon: Biru (`#5e72e4`), Hijau (`#2ecc71`)

Font:
- **SpaceArmor** - Header/logo (otf)
- **Cyberpunk** - Decorative (ttf)
- **Rovelink** - Decorative (otf)
- **Orbitron** - Headings (Black, Bold, Medium)
- **Rajdhani** - Body text (Medium, SemiBold, Bold)
- **Poppins** - UI elements (Regular, Medium, SemiBold)
- **Roboto** - UI text (Regular, Medium, Bold)
- **Material Icons** - Icon font (woff2)

### Theme Argon (Alternate)

File: `argon.php` (jika ada di versi sebelumnya). Menggunakan template Kai Admin Bootstrap.

## CSS Structure

### Main Stylesheet: `webui/css/styles.css`

File CSS utama yang mencakup:
- Sidebar layout (fixed, toggle)
- Dropdown navigation
- Main panel + iframe container
- Loading spinner
- Overlay
- Responsive breakpoints

### Monitor Styles: `webui/monitor/css/`

| File | Untuk |
|------|-------|
| `styles.css` | Monitor pages styling |
| `sysinfo.css` | System info layout |

### Font Awesome: `css/font-awesome.min.css`

Ikon klasik untuk sidebar dan konten.

### Font Awesome 6: `css/fontawesome/all.min.css`

Ikon Font Awesome 6 via file lokal (no CDN).

## JavaScript Components

### `webui/js/`

| File | Source | Fungsi |
|------|--------|--------|
| `iconify.min.js` | Local | Iconify icon engine |
| `font-awesome.min.js` | Local | Font Awesome JS |
| `chart/chart.min.js` | Local | Chart.js untuk grafik |

### Third-Party JS

| File | Source | Fungsi |
|------|--------|--------|
| `iconify/iconify.min.js` | Local | Iconify fallback |
| `fontawesome/all.min.js` | Local | Font Awesome 6 JS |

## UI Components

### Sidebar

- Fixed left sidebar (256px width).
- Dropdown sections dengan caret rotation animation.
- Click item load konten via `loadContent()` JS function.
- Item aktif ditandai dengan underline.
- Sidebar bisa toggle open/close.
- Overlay mencegah interaksi saat sidebar open.
- Mobile responsive: sidebar jadi overlay penuh.

### Main Content Area

- Konten dimuat dalam iframe.
- Loading spinner ditampilkan saat iframe loading.
- `adjustIframeHeight()` JS menyesuaikan tinggi iframe.
- Max-height: 1000vh, min-height: 110vh.

### Dropdown Navigation

- Dropdown button dengan caret icon.
- Rotate 90 derajat saat open.
- Click outside untuk close.
- Satu dropdown open dalam satu waktu.

### Buttons

- **Toggle button**: Menu hamburger (fixed, kiri atas).
- **Refresh button**: Reload halaman (fixed, kanan atas).
- **Logout**: Di sidebar, refresh dulu lalu redirect.

### Loading Spinner

- SVG spinner animation (`svg-spinners:bars-rotate-fade`).
- Centered, muncul saat iframe loading.
- Hilang setelah iframe onload.

## Responsive Design

| Breakpoint | Behavior |
|------------|----------|
| Desktop (>768px) | Sidebar fixed, konten di sebelah kanan |
| Mobile (<=768px) | Sidebar jadi overlay, teks header lebih kecil, konten full-width |

## Required Pages

### Login (`/auth/login.php`)

- Form username + password.
- Session-based auth.
- Redirect ke halaman utama setelah login.

### Dashboard (`/webui/monitor/Overview.php`)

- System overview: CPU, RAM, storage, battery, uptime.
- Network info: interfaces, IP, signal.
- Service status.

### Clash Dashboard

- **Default**: `http://host:9090/ui/` - Clash built-in dashboard.
- **Zashboard**: `/zashboard/ui/` - Enhanced Clash dashboard SPA.

### File Manager (`/tiny/opsi.php`)

- Full file browser.
- Create, edit, rename, delete, upload.
- Multiple root directories (internal, ADB, WebUI).

### Power Manager (`/tools/powermanager.php`)

- Reboot, reboot recovery, reboot bootloader, shutdown.
- Module enable/disable.

### Network Tools

- WiFi scan and connect.
- Network interface management.
- Hotspot control.
- Static IP assignment.

## Anti-Patterns

- Tidak ada template engine - HTML dirender dengan echo/print PHP.
- CSS dan JS campur di file PHP yang sama.
- Inline styles di banyak tempat.
- Tidak ada separation of concerns.
- iframe approach menyebabkan masalah height/scroll.
- Tidak ada HTMX atau framework frontend modern.
