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
  <meta name="theme-color" content="#1a1a1a">
  <title><?= boxui_e($title ?? 'BOX UI Extended') ?></title>
  <link rel="icon" href="/webui/assets/luci.ico" type="image/x-icon">
  <script src="/webui/js/iconify.min.js"></script>
  <script src="/webui/js/htmx.min.js"></script>
  <link rel="stylesheet" href="/webui/css/devforge.css">
  <style>
    :root {
      --background: #1a1a1a;
      --foreground: #f9f4da;
      --border: #f9f4da;
      --primary: #fcba28;
      --secondary: #14b6e5;
      --muted: #aeaeae;
    }
    
    body {
      font-family: 'Space Grotesk', sans-serif;
      background-color: #1a1a1a !important;
      color: #f9f4da !important;
    }
    
    h1, h2, h3, h4, h5, h6, .font-head {
      font-family: 'Archivo Black', sans-serif;
    }

    #app {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    #layout-wrapper {
      display: flex;
      flex: 1;
      position: relative;
    }

    /* Sidebar Transitions & Layout */
    #sidebar {
      width: 260px;
      background-color: #1a1a1a;
      border-right: 2px solid #f9f4da;
      display: flex;
      flex-direction: column;
      transition: transform 0.3s ease;
      z-index: 100;
    }

    #main {
      flex: 1;
      background-color: #1a1a1a;
      padding: 24px;
      min-height: calc(100vh - 64px);
    }

    /* Mobile state */
    @media (max-width: 768px) {
      #sidebar {
        position: fixed;
        top: 64px;
        bottom: 0;
        left: 0;
        transform: translateX(-100%);
      }
      #sidebar.active {
        transform: translateX(0);
      }
      #main {
        width: 100%;
      }
    }

    /* Overlay styles */
    .overlay {
      display: none;
      position: fixed;
      top: 64px;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.65);
      backdrop-filter: blur(4px);
      z-index: 90;
    }
    .overlay.active {
      display: block;
    }

    /* Status indicator widget */
    .status-indicator {
      background-color: #aeaeae;
    }
    .status-indicator.active {
      background-color: #0ca95b;
      box-shadow: 0 0 8px #0ca95b;
    }
    .status-indicator.inactive {
      background-color: #ff5c5c;
      box-shadow: 0 0 8px #ff5c5c;
    }

    /* Custom loading spinner wrapper */
    .loading-spinner {
      display: none;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 999;
      background: rgba(0, 0, 0, 0.85);
      padding: 30px;
      border: 2px solid #fcba28;
      box-shadow: 4px 4px 0px 0px rgba(249, 244, 218, 1);
    }
  </style>
  <?= $extraHead ?? '' ?>
</head>
<body class="min-h-screen bg-background text-foreground selection:bg-primary selection:text-black">
  <div id="app">
    <!-- Header banner -->
    <header class="w-full border-b-2 border-border bg-background px-6 text-[#f9f4da] z-50">
      <div class="flex h-16 items-center justify-between">
        <!-- Logo -->
        <a class="flex items-center gap-3 border-x-2 border-border px-6 h-full hover:bg-primary hover:text-black transition-colors" href="/">
          <div class="flex h-8 w-8 shrink-0 items-center justify-center bg-[#0CA95B] border border-border">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap fill-[#FCBA28] text-[#FCBA28]" aria-hidden="true">
              <path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path>
            </svg>
          </div>
          <span class="font-head text-base font-normal uppercase tracking-wider">
            DEVFORGE
          </span>
        </a>

        <!-- Controls/Navbar -->
        <div class="flex items-center gap-4 h-full">
          <!-- Toggle Button for sidebar -->
          <button class="md:hidden border-2 border-border bg-transparent px-4 py-1 text-xs uppercase font-bold hover:bg-[#fcba28] hover:text-black transition-colors" onclick="toggleSidebar()">
            Menu
          </button>
          
          <!-- Refresh Button -->
          <button class="border-2 border-border bg-[#fcba28] px-4 py-1 text-xs font-bold text-black uppercase transition-colors hover:bg-[#e0a41d] shadow-[2px_2px_0px_0px_rgba(249, 244, 218, 1)] active:translate-x-[2px] active:translate-y-[2px] active:shadow-[0px_0px_0px_0px_rgba(0,0,0,0)]" onclick="refreshContent()">
            Refresh
          </button>
        </div>
      </div>
    </header>

    <div id="layout-wrapper">
      <!-- Sidebar -->
      <?php include __DIR__ . '/../partials/sidebar.php'; ?>

      <!-- Main Content -->
      <div id="main">
        <div id="content">
          <?= $content ?? '' ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Overlay -->
  <div id="overlay" class="overlay" onclick="toggleSidebar()"></div>

  <!-- Loading Spinner -->
  <div id="loading" class="loading-spinner">
    <svg width="50" height="50" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      <style>.spinner_9y7u{animation:spinner_fUkk 2.4s linear infinite;animation-delay:-2.4s;fill:var(--primary,#fcba28)}.spinner_9y7u:nth-child(2n){animation-delay:-2.14s}.spinner_9y7u:nth-child(3n){animation-delay:-1.88s}.spinner_9y7u:nth-child(4n){animation-delay:-1.62s}.spinner_9y7u:nth-child(5n){animation-delay:-1.36s}.spinner_9y7u:nth-child(6n){animation-delay:-1.1s}.spinner_9y7u:nth-child(7n){animation-delay:-.84s}.spinner_9y7u:nth-child(8n){animation-delay:-.58s}.spinner_9y7u:nth-child(9n){animation-delay:-.32s}.spinner_9y7u:nth-child(10n){animation-delay:-.06s}@keyframes spinner_fUkk{0%,60%,100%{opacity:0}10%{opacity:1}}</style>
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
    <p id="loading-text" style="margin-top:12px;color:var(--primary,#fcba28);font-size:14px;font-weight:bold;">Memuat...</p>
  </div>

  <!-- Error fallback (hidden by default) -->
  <div id="load-error" class="hidden absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 p-10 text-center border-2 border-border bg-black shadow-[4px_4px_0px_0px_rgba(249, 244, 218, 1)]">
    <p style="font-size:1.2rem;color:var(--primary,#fcba28);font-family:'Archivo Black',sans-serif;">Gagal memuat halaman</p>
    <p style="font-size:0.9rem;color:#aeaeae;margin-top:8px;" id="load-error-detail"></p>
    <button onclick="location.reload()" style="margin-top:16px;padding:8px 20px;background:var(--primary,#fcba28);color:#000;border:2px solid var(--border);font-weight:bold;cursor:pointer;">Coba Lagi</button>
  </div>

  <script>
    const host = '<?= boxui_host() ?>';
    let loadingTimer = null;
    let minDisplayTimer = null;

    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('active');
      document.getElementById('overlay').classList.toggle('active');
    }

    function showLoading() {
      const loading = document.getElementById('loading');
      const err = document.getElementById('load-error');
      loading.style.display = 'flex';
      err.classList.add('hidden');
      document.getElementById('loading-text').textContent = 'Memuat...';

      // Loading timeout (10s)
      clearTimeout(loadingTimer);
      loadingTimer = setTimeout(function() {
        if (loading.style.display !== 'none') {
          document.getElementById('loading-text').textContent = 'Memuat... (terlambat)';
        }
      }, 10000);
    }

    function hideLoading() {
      clearTimeout(loadingTimer);
      // Minimum 300ms display to prevent flash
      clearTimeout(minDisplayTimer);
      minDisplayTimer = setTimeout(function() {
        document.getElementById('loading').style.display = 'none';
      }, 300);
    }

    function showError(msg) {
      clearTimeout(loadingTimer);
      document.getElementById('loading').style.display = 'none';
      document.getElementById('load-error').classList.remove('hidden');
      document.getElementById('load-error-detail').textContent = msg || '';
    }

    function loadContent(url) {
      const loading = document.getElementById('loading');
      const content = document.getElementById('content');
      const err = document.getElementById('load-error');
      showLoading();
      content.innerHTML = '';
      err.classList.add('hidden');

      // Update active links in sidebar
      document.querySelectorAll('#sidebar a.dropdown-item').forEach(link => {
        if (link.getAttribute('href') === url) {
          link.classList.add('text-primary');
          link.classList.add('bg-[#fcba28]/10');
        } else {
          link.classList.remove('text-primary');
          link.classList.remove('bg-[#fcba28]/10');
        }
      });

      // Same-origin pages: load via fetch
      fetch(url)
        .then(function(r) {
          if (!r.ok) throw new Error('HTTP ' + r.status + ': ' + r.statusText);
          return r.text();
        })
        .then(function(html) {
          content.innerHTML = html;
          if (window.htmx) {
            htmx.process(content);
          }
          hideLoading();
        })
        .catch(function(e) {
          content.innerHTML = '';
          showError(e.message || 'Gagal memuat konten.');
        });

      if (window.innerWidth <= 768) {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        if (sidebar.classList.contains('active')) {
          sidebar.classList.remove('active');
          overlay.classList.remove('active');
        }
      }
    }

    /**
     * postMessage listener for iframe child height reports.
     * Child frames send: parent.postMessage({iframeHeight: N}, '*')
     */
    window.addEventListener('message', function(e) {
      if (e.data && typeof e.data.iframeHeight === 'number') {
        var iframes = document.querySelectorAll('#content iframe');
        for (var i = 0; i < iframes.length; i++) {
          try {
            if (iframes[i].contentWindow === e.source) {
              iframes[i].style.height = Math.max(e.data.iframeHeight, 400) + 'px';
              break;
            }
          } catch(ex) {}
        }
      }
    });

    function refreshContent() {
      // Find active page or default
      let activeLink = document.querySelector('#sidebar a.text-primary');
      const url = activeLink ? activeLink.getAttribute('href') : '/pages/monitor/index.php';
      loadContent(url);
    }

    document.addEventListener('DOMContentLoaded', function() {
      // Load default content
      loadContent('/pages/monitor/index.php');
    });
  </script>
</body>
</html>