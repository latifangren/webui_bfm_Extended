<?php
/**
 * Change Password — refactored using AuthService.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

use BoxUI\Auth\AuthService;

AuthService::init();
AuthService::requireAuth();

$message = '';
$error = '';

$creds = require __DIR__ . '/credentials.php';
$current_username = $creds['username'] ?? 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $new_username = $_POST['new_username'] ?? $current_username;

    // Verify current password
    if (!password_verify($current, $creds['hashed_password'])) {
        $error = 'Password saat ini tidak sesuai.';
    } elseif (strlen($new) < 4) {
        $error = 'Password baru minimal 4 karakter.';
    } elseif ($new !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        AuthService::changePassword($new, $new_username);
        $current_username = $new_username;
        $message = 'Password berhasil diubah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password - BOX UI Extended</title>
    <link rel="icon" href="../webui/assets/luci.ico" type="image/x-icon">
    <link rel="stylesheet" href="../webui/css/styles.css">
    <style>
        body { font-family: 'Rajdhani', sans-serif; background: #0a0a0a; color: #F1F1F1; display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px; }
        .card { background:#111; border:1px solid #333; border-radius:16px; padding:30px; max-width:400px; width:100%; }
        .card h1 { font-family:'Orbitron',monospace; font-size:20px; color:#FECA0A; margin:0 0 20px; }
        .field { margin-bottom:15px; }
        .field label { display:block; font-size:13px; color:#aaa; margin-bottom:4px; }
        .field input { width:100%; padding:10px 12px; background:#1a1a1a; border:1px solid #333; border-radius:8px; color:#F1F1F1; box-sizing:border-box; }
        .btn { width:100%; padding:12px; background:#FECA0A; color:#000; border:none; border-radius:8px; font-weight:700; cursor:pointer; font-family:'Orbitron',monospace; }
        .msg { padding:8px 12px; border-radius:6px; margin-bottom:15px; font-size:13px; }
        .msg.success { background:rgba(76,175,80,0.1); border:1px solid #4CAF50; color:#4CAF50; }
        .msg.error { background:rgba(255,0,0,0.1); border:1px solid #ff4444; color:#ff4444; }
    </style>
</head>
<body>
    <div class="card">
        <h1>&#128273; Ganti Password</h1>
        <?php if ($message): ?><div class="msg success"><?= boxui_e($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="msg error"><?= boxui_e($error) ?></div><?php endif; ?>
        <form method="POST">
            <div class="field">
                <label for="new_username">Username</label>
                <input type="text" id="new_username" name="new_username" value="<?= boxui_e($current_username) ?>" required>
            </div>
            <div class="field">
                <label for="current_password">Password Saat Ini</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="field">
                <label for="new_password">Password Baru</label>
                <input type="password" id="new_password" name="new_password" required minlength="4">
            </div>
            <div class="field">
                <label for="confirm_password">Konfirmasi Password Baru</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="4">
            </div>
            <button type="submit" class="btn">Simpan</button>
        </form>
        <p style="text-align:center;margin-top:15px;"><a href="/" style="color:#888;font-size:13px;">← Kembali</a></p>
    </div>
</body>
</html>
