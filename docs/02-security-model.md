# Model Keamanan

BOX UI Extended berjalan pada perangkat Android rooted. Model keamanan berfokus pada melindungi akses panel dari jaringan lokal tanpa menghalangi pengguna yang sah.

## Core Rules

- Auth berbasis session PHP untuk semua halaman (kecuali login/setup).
- LOGIN_ENABLED config di `auth/config.php`.
- Default credentials: admin/12345.
- Setiap halaman PHP melakukan session check sendiri.
- Operasi shell via `shell_exec()` dari input pengguna - **tanpa sanitasi ketat**.

## Authentication

| Komponen | Detail |
|----------|--------|
| Session | PHP native sessions, cookie_lifetime 1 tahun |
| Login | `auth/login.php` - form username + password |
| Logout | `auth/logout.php` - destroy session |
| Change Password | `auth/change_password.php` |
| Config | `auth/config.json` + `auth/config.php` |
| Default User | admin |
| Default Pass | 12345 |

### File Konfigurasi Auth

`auth/config.json`:
```json
{
  "username": "admin",
  "password": "$2y$10$...",
  "login_enabled": true
}
```

## Route Classes

| Class | Contoh | Requirements |
|-------|--------|-------------|
| Public | `/auth/login.php` | No session needed |
| Authenticated | Semua halaman tools | Valid session + LOGIN_ENABLED check |
| Admin | `change_password.php`, `manage_login.php` | Valid session |

## Session Security

- Session ID regenerated on login.
- Cookies: konfigurasi PHP standar (tidak ada HttpOnly/Strict by default).
- Session file disimpan di tmp filesystem.
- Session lifetime: 1 tahun (cookie_lifetime).

## CSRF

- **Tidak ada CSRF protection** di sebagian besar form.
- Beberapa form menggunakan POST, beberapa GET untuk state-changing operations.
- Ini adalah **gap keamanan yang diketahui**.

## Shell Execution

Semua operasi yang membutuhkan akses root/system menggunakan PHP functions:

| Function | Usage |
|----------|-------|
| `shell_exec()` | Eksekusi shell command, return output string |
| `exec()` | Eksekusi shell command, return last line |
| `passthru()` | Eksekusi shell command, output langsung |

**Risiko**: Banyak halaman menggunakan input user langsung di shell commands tanpa sanitasi. Contoh:
- Input form fields langsung di concatenate ke command string.
- Tidak ada allowlist operation IDs.
- Tidak ada parameter validation.

## Audit Log

- **Tidak ada audit trail terpusat**.
- Beberapa tools log aktivitas ke file sendiri (misal `blocked_users.txt`).
- Debug logs di `tools/logs/`.

## Guardrail Philosophy

- **Tidak ada danger re-auth** - sekali login, semua operasi bisa dijalankan.
- **Tidak ada operation allowlist** - shell commands bebas dari input user.
- **Tidak ada output bounding** - shell output tidak dibatasi ukuran.
- Keamanan bergantung pada **security by obscurity** (panel hanya accessible dari local network).
- Default bind `127.0.0.1:80` (localhost only).

## Remote Access

- PHP7 server bind ke `127.0.0.1:80` secara default.
- Ekstensi port forwarding (misal via ADB) memungkinkan akses remote.
- ttyd terminal bind ke `127.0.0.1:3001`.
- Tidak ada warning di UI saat bind ke 0.0.0.0.

## Gap Keamanan Diketahui

| Issue | Severity | Status |
|-------|----------|--------|
| No CSRF protection | HIGH | Unresolved |
| Raw shell_exec dari input user | CRITICAL | Unresolved |
| No operation allowlist | HIGH | Unresolved |
| No audit trail | MEDIUM | Unresolved |
| Default credentials known | HIGH | Unresolved |
| No session HttpOnly/Strict | MEDIUM | Unresolved |
| GET endpoints mutate state | MEDIUM | Unresolved |
| No rate limiting | LOW | Unresolved |
