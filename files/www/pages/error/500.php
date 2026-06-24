<?php
/**
 * 500 — Terjadi Kesalahan
 * 
 * Rendered by shutdown handler in bootstrap.php on fatal errors.
 * Variables: $errorMsg, $errorFile, $errorLine (optional).
 */
$errorMsg  = $errorMsg ?? '';
$errorFile = $errorFile ?? '';
$errorLine = $errorLine ?? 0;
$debugMode = defined('BOXUI_DEBUG') && BOXUI_DEBUG;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>500 - Terjadi Kesalahan</title>
  <link rel="icon" href="/webui/assets/luci.ico" type="image/x-icon">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Rajdhani', 'Segoe UI', sans-serif;
      background: #000;
      color: #F1F1F1;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      text-align: center;
      padding: 20px;
    }
    .error-box { max-width: 520px; }
    .error-code {
      font-family: 'Orbitron', monospace;
      font-size: 96px;
      font-weight: 700;
      color: #FECA0A;
      line-height: 1;
      margin-bottom: 8px;
    }
    .error-box h1 {
      font-size: 22px;
      font-weight: 600;
      margin-bottom: 12px;
      color: #fff;
    }
    .error-box p {
      color: #888;
      font-size: 15px;
      margin-bottom: 24px;
      line-height: 1.5;
    }
    .btn-home {
      display: inline-block;
      padding: 12px 28px;
      background: #FECA0A;
      color: #000;
      border: none;
      border-radius: 8px;
      font-family: 'Orbitron', monospace;
      font-size: 14px;
      font-weight: 600;
      text-decoration: none;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      transition: all 0.15s;
    }
    .btn-home:hover { background: #ffd633; }
    .debug-dump {
      margin-top: 20px;
      padding: 16px;
      background: #111;
      border: 1px solid #333;
      border-radius: 8px;
      text-align: left;
      font-family: monospace;
      font-size: 12px;
      color: #ff6b6b;
      word-break: break-all;
      line-height: 1.6;
    }
    .debug-dump strong { color: #FECA0A; }
  </style>
</head>
<body>
  <div class="error-box">
    <div class="error-code">500</div>
    <h1>Terjadi Kesalahan</h1>
    <p>Terjadi kesalahan internal saat memproses permintaan. Silakan coba lagi atau hubungi administrator.</p>
    <a href="/" class="btn-home">Beranda</a>

    <?php if ($debugMode && $errorMsg): ?>
    <div class="debug-dump">
      <strong>Message:</strong> <?= boxui_e($errorMsg) ?><br>
      <?php if ($errorFile): ?><strong>File:</strong> <?= boxui_e($errorFile) ?><br><?php endif; ?>
      <?php if ($errorLine): ?><strong>Line:</strong> <?= (int)$errorLine ?><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</body>
</html>
