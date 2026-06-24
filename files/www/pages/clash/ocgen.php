<?php
/**
 * OcGen — Config Generator
 * 
 * OCGen is a standalone mini-app with 104 files (Bootstrap, JS, CSS).
 * Renders via iframe to avoid HTML structure conflicts with BOX UI layout.
 */
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">⚙️</span>
            <h1>Config Generator</h1>
        </div>
    </div>

    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:16px;margin-bottom:15px;border:1px solid var(--border,#333);">
        <p style="font-size:13px;color:#888;margin:0;">
            Open in new tab for full functionality:
            <a href="/tools/ocgen/index.php" target="_blank" style="color:var(--accent,#FECA0A);margin-left:8px;">
                Buka OcGen <i class="fas fa-external-link-alt"></i>
            </a>
        </p>
    </div>

    <div style="border-radius:12px;overflow:hidden;border:1px solid var(--border,#333);background:#fff;height:80vh;">
        <iframe src="/tools/ocgen/index.php" style="width:100%;height:100%;border:none;"
                title="OcGen Config Generator"></iframe>
    </div>
</div>
