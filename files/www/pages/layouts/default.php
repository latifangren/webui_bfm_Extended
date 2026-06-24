<?php
/**
 * Default layout — SPA shell with sidebar.
 * Mirrors the structure of extended.php but uses ModuleRegistry for sidebar.
 */
use BoxUI\Module\ModuleRegistry;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#5e72e4">
  <title><?= boxui_e($title ?? 'BOX UI Extended') ?></title>
  <link rel="icon" href="/webui/assets/luci.ico" type="image/x-icon">
  <script src="/webui/js/iconify.min.js"></script>
  <link rel="stylesheet" href="/webui/css/styles.css">
  <style>
    @font-face {
      font-family: 'Material Icons';
      font-style: normal; font-weight: 400;
      src: url('/webui/fonts/MaterialIcons-Regular.woff2') format('woff2'),
           url('/webui/fonts/MaterialIcons-Regular.woff') format('woff');
    }
    @font-face {
      font-family: 'Orbitron'; font-style: normal; font-weight: 400;
      src: url('/webui/fonts/Orbitron-Regular.woff2') format('woff2');
    }
    @font-face {
      font-family: 'Orbitron'; font-style: normal; font-weight: 500;
      src: url('/webui/fonts/Orbitron-Medium.woff2') format('woff2');
    }
    @font-face {
      font-family: 'Rajdhani'; font-style: normal; font-weight: 500;
      src: url('/webui/fonts/Rajdhani-Medium.woff2') format('woff2');
    }
    @font-face {
      font-family: 'Rajdhani'; font-style: normal; font-weight: 600;
      src: url('/webui/fonts/Rajdhani-SemiBold.woff2') format('woff2');
    }
    @font-face {
      font-family: 'Rajdhani'; font-style: normal; font-weight: 700;
      src: url('/webui/fonts/Rajdhani-Bold.woff2') format('woff2');
    }
    @font-face {
      font-family: 'Poppins'; font-style: normal; font-weight: 400;
      src: url('/webui/fonts/Poppins-Regular.woff2') format('woff2');
    }
    @font-face {
      font-family: 'Poppins'; font-style: normal; font-weight: 500;
      src: url('/webui/fonts/Poppins-Medium.woff2') format('woff2');
    }
    @font-face {
      font-family: 'Poppins'; font-style: normal; font-weight: 600;
      src: url('/webui/fonts/Poppins-SemiBold.woff2') format('woff2');
    }
    @font-face {
      font-family: 'SPACE ARMOR'; font-style: normal; font-weight: 400;
      src: url('/webui/fonts/SPACE ARMOR.otf') format('opentype');
    }
  </style>
  <?= $extraHead ?? '' ?>
</head>
<body>
  <div id="app">
    <!-- Toggle Button -->
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
    <!-- Refresh Button -->
    <button class="refresh-btn" onclick="refreshContent()" style="position:fixed;top:10px;right:10px;z-index:1001;background:var(--accent,#FECA0A);border:none;border-radius:50%;width:40px;height:40px;font-size:20px;cursor:pointer;color:#000;">
      ↻
    </button>

    <!-- Sidebar -->
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <!-- Main Content -->
    <div id="main">
      <div id="content">
        <?= $content ?? '' ?>
      </div>
    </div>
  </div>

  <!-- Overlay -->
  <div id="overlay" class="overlay" onclick="toggleSidebar()"></div>

  <!-- Loading Spinner -->
  <div id="loading" class="loading-spinner" style="display:none;">
    <svg width="50" height="50" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      <style>.spinner_9y7u{animation:spinner_fUkk 2.4s linear infinite;animation-delay:-2.4s;fill:var(--accent,#FECA0A)}.spinner_9y7u:nth-child(2n){animation-delay:-2.14s}.spinner_9y7u:nth-child(3n){animation-delay:-1.88s}.spinner_9y7u:nth-child(4n){animation-delay:-1.62s}.spinner_9y7u:nth-child(5n){animation-delay:-1.36s}.spinner_9y7u:nth-child(6n){animation-delay:-1.1s}.spinner_9y7u:nth-child(7n){animation-delay:-.84s}.spinner_9y7u:nth-child(8n){animation-delay:-.58s}.spinner_9y7u:nth-child(9n){animation-delay:-.32s}.spinner_9y7u:nth-child(10n){animation-delay:-.06s}@keyframes spinner_fUkk{0%,60%,100%{opacity:0}10%{opacity:1}}</style>
      <rect class="spinner_9y7u" x="1" y="1" rx="1" width="3" height="3"/>
      <rect class="spinner_9y7u" x="4.5" y="1" rx="1" width="3" height="3" transform="rotate(30 6 2.5)"/>
      <rect class="spinner_9y7u" x="8" y="1" rx="1" width="3" height="3" transform="rotate(60 9.5 2.5)"/>
      <rect class="spinner_9y7u" x="11.5" y="1" rx="1" width="3" height="3"/>
      <rect class="spinner_9y7u" x="15" y="1" rx="1" width="3" height="3" transform="rotate(120 16.5 2.5)"/>
      <rect class="spinner_9y7u" x="1" y="4.5" rx="1" width="3" height="3" transform="rotate(-60 2.5 6)"/>
      <rect class="spinner_9y7u" x="4.5" y="4.5" rx="1" width="3" height="3" transform="rotate(-30 6 6)"/>
      <rect class="spinner_9y7u" x="8" y="4.5" rx="1" width="3" height="3"/>
      <rect class="spinner_9y7u" x="11.5" y="4.5" rx="1" width="3" height="3"/>
      <rect class="spinner_9y7u" x="15" y="4.5" rx="1" width="3" height="3"/>
      <rect class="spinner_9y7u" x="1" y="8" rx="1" width="3" height="3" transform="rotate(-120 2.5 9.5)"/>
      <rect class="spinner_9y7u" x="4.5" y="8" rx="1" width="3" height="3"/>
      <rect class="spinner_9y7u" x="8" y="8" rx="1" width="3" height="3"/>
      <rect class="spinner_9y7u" x="11.5" y="8" rx="1" width="3" height="3"/>
      <rect class="spinner_9y7u" x="15" y="8" rx="1" width="3" height="3"/>
      <rect class="spinner_9y7u" x="1" y="11.5" rx="1" width="3" height="3"/>
      <rect class="spinner_9y7u" x="4.5" y="11.5" rx="1" width="3" height="3"/>
      <rect class="spinner_9y7u" x="8" y="11.5" rx="1" width="3" height="3"/>
      <rect class="spinner_9y7u" x="11.5" y="11.5" rx="1" width="3" height="3"/>
      <rect class="spinner_9y7u" x="15" y="11.5" rx="1" width="3" height="3"/>
    </svg>
  </div>

  <script>
    const host = '<?= boxui_host() ?>';

    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('active');
      document.getElementById('overlay').classList.toggle('active');
    }

    function loadContent(url) {
      const loading = document.getElementById('loading');
      const content = document.getElementById('content');
      loading.style.display = 'flex';
      content.innerHTML = '';

      // Check if it's an SPA (need iframe)
      if (url.endsWith('.html') || url.includes('zashboard') || url.includes('libernet')) {
        const iframe = document.createElement('iframe');
        iframe.src = url;
        iframe.style.width = '100%';
        iframe.style.minHeight = '110vh';
        iframe.style.border = 'none';
        iframe.onload = function() {
          loading.style.display = 'none';
          // Adjust iframe height
          try {
            const h = iframe.contentWindow.document.body.scrollHeight;
            iframe.style.height = Math.max(h, 1100) + 'px';
          } catch(e) {}
        };
        content.appendChild(iframe);
      } else {
        // Load via fetch
        fetch(url)
          .then(r => r.text())
          .then(html => {
            content.innerHTML = html;
            loading.style.display = 'none';
          })
          .catch(() => {
            content.innerHTML = '<p style="color:red;padding:20px;">Gagal memuat konten.</p>';
            loading.style.display = 'none';
          });
      }

      if (window.innerWidth <= 768) {
        toggleSidebar();
      }
    }

    function refreshContent() {
      const url = document.querySelector('#sidebar a.active')?.getAttribute('href') || '/webui/monitor/Overview.php';
      loadContent(url);
    }

    document.addEventListener('DOMContentLoaded', function() {
      // Load default content
      loadContent('/webui/monitor/Overview.php');
    });
  </script>
</body>
</html>
