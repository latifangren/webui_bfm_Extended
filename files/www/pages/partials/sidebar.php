<?php
/**
 * Sidebar partial — auto-generated from ModuleRegistry.
 */
use BoxUI\Module\ModuleRegistry;

$groups = ModuleRegistry::sidebar();
?>
<div id="sidebar" class="sidebar flex flex-col h-full bg-[#1a1a1a] text-foreground font-sans">
  <!-- BOX Header -->
  <div class="sidebar-header p-6 border-b-2 border-border bg-black/40">
    <div class="logo-container border-2 border-primary p-4 bg-[#1a1a1a] flex flex-col items-center justify-center text-center shadow-[4px_4px_0px_0px_rgba(249,244,218,1)]">
      <div class="logo-text font-sans tracking-wide">
        <span class="logo-box block font-head text-primary font-bold text-lg mb-1">[ BOX UI ]</span>
        <span class="logo-sub block text-xs tracking-widest text-[#aeaeae] uppercase">Extended</span>
        <div class="logo-version text-[9px] text-[#0CA95B] border border-[#0CA95B] px-2 py-0.5 mt-3 font-mono inline-block bg-[#0CA95B]/10">v2.1.1</div>
      </div>
    </div>
  </div>

  <!-- Clash Status Bar -->
  <div class="clash-status border-b-2 border-border p-4 flex items-center justify-between bg-black/20 text-xs">
    <div class="flex items-center gap-2">
      <div class="status-indicator h-2.5 w-2.5 rounded-full border border-border" id="statusIndicator"></div>
      <span id="clashStatusText" class="font-bold tracking-wider uppercase font-sans">Checking...</span>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav flex-1 overflow-y-auto">
  <?php foreach ($groups as $group): ?>
    <div class="nav-group border-b-2 border-border">
      <button class="dropdown-btn w-full text-left py-3 px-6 flex items-center justify-between font-head font-bold uppercase tracking-wider text-xs hover:bg-[#fcba28] hover:text-black transition-colors focus:outline-none" onclick="toggleDropdown(this)">
        <span class="tracking-widest"><?= boxui_e($group['name']) ?></span>
        <span class="caret text-[9px] transition-transform duration-300">▶</span>
      </button>
      <div class="dropdown-container transition-all duration-300 bg-black/20 overflow-hidden" style="max-height: 0px; opacity: 0;">
      <?php foreach ($group['modules'] as $module): ?>
        <a href="<?= boxui_e($module['route']) ?>"
           class="dropdown-item flex items-center gap-3 py-2.5 px-8 text-xs font-semibold hover:text-primary transition-colors font-sans hover:bg-[#fcba28]/5 border-b border-[#f9f4da]/10 last:border-b-0"
           onclick="event.preventDefault(); loadContent('<?= boxui_e($module['route']) ?>')">
          <i class="<?= boxui_e($module['icon']) ?> text-xs text-primary"></i>
          <span class="tracking-wide"><?= boxui_e($module['name']) ?></span>
        </a>
      <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
  </nav>

  <!-- Bottom Links -->
  <div class="sidebar-footer border-t-2 border-border p-4 gap-2 flex flex-col bg-black/40">
    <a href="#" onclick="event.preventDefault(); loadContent('/about.php')" class="flex items-center justify-center gap-3 px-4 py-2 text-xs font-bold uppercase border-2 border-border bg-[#1a1a1a] hover:bg-[#fcba28] hover:text-black transition-colors shadow-[2px_2px_0px_0px_rgba(249, 244, 218, 1)] active:translate-x-[1px] active:translate-y-[1px] active:shadow-[1px_1px_0px_0px_rgba(249, 244, 218, 1)]">
      <i class="fas fa-info-circle text-primary"></i> About
    </a>
    <a href="#" onclick="event.preventDefault(); loadContent('/article.html')" class="flex items-center justify-center gap-3 px-4 py-2 text-xs font-bold uppercase border-2 border-border bg-[#1a1a1a] hover:bg-[#fcba28] hover:text-black transition-colors shadow-[2px_2px_0px_0px_rgba(249, 244, 218, 1)] active:translate-x-[1px] active:translate-y-[1px] active:shadow-[1px_1px_0px_0px_rgba(249, 244, 218, 1)]">
      <i class="fas fa-book text-primary"></i> Docs
    </a>
    <a href="/auth/logout.php" class="flex items-center justify-center gap-3 px-4 py-2 text-xs font-bold uppercase border-2 border-border bg-red-950/20 text-red-400 hover:bg-red-500 hover:text-white transition-colors">
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
    if (caret) caret.style.transform = 'rotate(95deg)'; // slight offset for character
  }
}

// Auto-open first dropdown
document.addEventListener('DOMContentLoaded', function() {
  const firstDropdown = document.querySelector('.dropdown-container');
  if (firstDropdown) {
    firstDropdown.style.maxHeight = firstDropdown.scrollHeight + 'px';
    firstDropdown.style.opacity = '1';
    const firstCaret = document.querySelector('.dropdown-btn .caret');
    if (firstCaret) firstCaret.style.transform = 'rotate(95deg)';
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
        indicator.className = 'status-indicator active h-2.5 w-2.5 rounded-full border border-border';
        text.textContent = 'Clash Running';
      } else {
        indicator.className = 'status-indicator inactive h-2.5 w-2.5 rounded-full border border-border';
        text.textContent = 'Clash Stopped';
      }
    })
    .catch(() => {
      document.getElementById('statusIndicator').className = 'status-indicator inactive h-2.5 w-2.5 rounded-full border border-border';
      document.getElementById('clashStatusText').textContent = 'Clash Offline';
    });
}
setInterval(checkClashStatus, 10000);
checkClashStatus();
</script>