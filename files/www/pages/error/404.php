<?php
/**
 * 404 — Halaman Tidak Ditemukan
 * 
 * Rendered by router.php when no route matches.
 * Can also be included directly via http_response_code(404).
 */
$requestUrl = $_SERVER['REQUEST_URI'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>404 - Halaman Tidak Ditemukan</title>
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
    .error-box { max-width: 480px; }
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
    .error-url {
      display: block;
      color: #555;
      font-size: 12px;
      font-family: monospace;
      margin-bottom: 24px;
      word-break: break-all;
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
    .btn-back {
      display: inline-block;
      margin-top: 12px;
      padding: 10px 20px;
      color: #888;
      font-size: 13px;
      text-decoration: none;
      border: 1px solid #333;
      border-radius: 8px;
      margin-left: 8px;
      transition: all 0.15s;
    }
    .btn-back:hover { color: #F1F1F1; border-color: #555; }
  </style>
</head>
<body>
  <div class="error-box">
    <div class="error-code">404</div>
    <h1>Halaman Tidak Ditemukan</h1>
    <p>Halaman yang Anda cari tidak tersedia atau telah dipindahkan.</p>
    <code class="error-url"><?= htmlspecialchars($requestUrl, ENT_QUOTES, 'UTF-8') ?></code>
    <div>
      <a href="/" class="btn-home">Beranda</a>
      <a href="javascript:history.back()" class="btn-back">Kembali</a>
    </div>
  </div>
</body>
</html>
