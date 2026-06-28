<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * Sidompul — Telco Balance Check
 * Refactored from tools/sidompul.php
 */
use BoxUI\Features\Services\ServicesService;

$endpoints = ServicesService::sidompulEndpoints();
?>
<div class="container">
    <div class="header">
        <div class="logo">
            <span class="logo-icon">💰</span>
            <h1>Sidompul</h1>
        </div>
    </div>

    <div style="background:var(--bg-secondary,#1a1a1a);border-radius:12px;padding:20px;border:1px solid var(--border,#333);">
        <p style="margin-bottom:15px;font-size:13px;color:#888;">Cek kuota/balance provider via Sidompul API.</p>

        <div style="margin-bottom:15px;">
            <label style="display:block;font-size:12px;color:#aaa;margin-bottom:5px;">Phone Number</label>
            <input type="text" id="msisdn" placeholder="08xxxxxxxxxx" value="08"
                   style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border,#333);background:var(--bg-primary,#0d0d0d);color:#fff;font-size:14px;">
        </div>
        <button onclick="checkBalance()" style="padding:10px 24px;border:none;border-radius:8px;background:var(--accent,#FECA0A);color:#000;font-size:14px;font-weight:600;cursor:pointer;">
            Cek Kuota
        </button>

        <div id="result" style="margin-top:15px;padding:15px;background:var(--bg-primary,#0d0d0d);border-radius:8px;font-size:13px;color:#ccc;display:none;"></div>
    </div>

    <script>
    const API_URL = '<?= $endpoints['url'] ?>';
    const API_KEY = '<?= $endpoints['api_key'] ?>';

    async function checkBalance() {
        const msisdn = document.getElementById('msisdn').value.trim();
        if (!msisdn) { alert('Masukkan nomor telepon'); return; }

        const resultDiv = document.getElementById('result');
        resultDiv.style.display = 'block';
        resultDiv.textContent = 'Mengecek...';

        try {
            const response = await fetch(API_URL + '?msisdn=' + encodeURIComponent(msisdn), {
                headers: { 'X-API-Key': API_KEY }
            });
            const data = await response.json();
            resultDiv.innerHTML = '<pre style="margin:0;font-size:12px;">' + JSON.stringify(data, null, 2) + '</pre>';
        } catch (e) {
            resultDiv.textContent = 'Error: ' + e.message;
        }
    }
    </script>
</div>
