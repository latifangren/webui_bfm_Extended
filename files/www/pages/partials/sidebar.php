<?php
/**
 * Sidebar partial — auto-generated from ModuleRegistry.
 */
use BoxUI\Module\ModuleRegistry;

$groups = ModuleRegistry::sidebar();
?>
<div id="sidebar" class="sidebar">
  <!-- BOX Header -->
  <div class="sidebar-header">
    <div class="logo-container">
      <div class="logo-decoration top"></div>
      <div class="logo-text">
        <span class="logo-box">[ BOX ]</span>
        <span class="logo-sub">UI EXTENDED</span>
        <div class="logo-version">v2.1.1</div>
      </div>
      <div class="logo-decoration bottom"></div>
    </div>
  </div>

  <!-- Clash Status Bar -->
  <div class="clash-status" id="clashStatus">
    <div class="status-indicator" id="statusIndicator"></div>
    <span id="clashStatusText">Checking...</span>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav">
  <?php foreach ($groups as $group): ?>
    <div class="nav-group">
      <button class="dropdown-btn" onclick="toggleDropdown(this)">
        <?= boxui_e($group['name']) ?>
        <span class="caret">▶</span>
      </button>
      <div class="dropdown-container">
      <?php foreach ($group['modules'] as $module): ?>
        <a href="<?= boxui_e($module['route']) ?>"
           class="dropdown-item"
           onclick="event.preventDefault(); loadContent('<?= boxui_e($module['route']) ?>')">
          <i class="<?= boxui_e($module['icon']) ?>"></i>
          <span><?= boxui_e($module['name']) ?></span>
        </a>
      <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
  </nav>

  <!-- Bottom Links -->
  <div class="sidebar-footer">
    <a href="#" onclick="event.preventDefault(); loadContent('/about.php')">
      <i class="fas fa-info-circle"></i> About
    </a>
    <a href="#" onclick="event.preventDefault(); loadContent('/article.html')">
      <i class="fas fa-book"></i> Docs
    </a>
    <a href="/auth/logout.php">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>
</div>

<script>
function toggleDropdown(btn) {
  const container = btn.nextElementSibling;
  const caret = btn.querySelector('.caret');
  const isOpen = container.style.maxHeight && container.style.maxHeight !== '0px';

  // Close all
  document.querySelectorAll('.dropdown-container').forEach(c => {
    c.style.maxHeight = '0px';
    c.style.opacity = '0';
  });
  document.querySelectorAll('.dropdown-btn .caret').forEach(c => {
    c.style.transform = 'rotate(0deg)';
  });

  if (!isOpen) {
    container.style.maxHeight = container.scrollHeight + 'px';
    container.style.opacity = '1';
    if (caret) caret.style.transform = 'rotate(90deg)';
  }
}

// Auto-open first dropdown
document.addEventListener('DOMContentLoaded', function() {
  const firstDropdown = document.querySelector('.dropdown-container');
  if (firstDropdown) {
    firstDropdown.style.maxHeight = firstDropdown.scrollHeight + 'px';
    firstDropdown.style.opacity = '1';
    const firstCaret = document.querySelector('.dropdown-btn .caret');
    if (firstCaret) firstCaret.style.transform = 'rotate(90deg)';
  }
});

// Clash status check
function checkClashStatus() {
  fetch('/api/clash_status.php')
    .then(r => r.text())
    .then(status => {
      const indicator = document.getElementById('statusIndicator');
      const text = document.getElementById('clashStatusText');
      if (status.includes('running') || status.includes('active')) {
        indicator.className = 'status-indicator active';
        text.textContent = 'Clash Running';
      } else {
        indicator.className = 'status-indicator inactive';
        text.textContent = 'Clash Stopped';
      }
    })
    .catch(() => {
      document.getElementById('statusIndicator').className = 'status-indicator inactive';
      document.getElementById('clashStatusText').textContent = 'Clash Offline';
    });
}
setInterval(checkClashStatus, 10000);
checkClashStatus();
</script>
