<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$p = $_SERVER['HTTP_HOST'];
$x = explode(':', $p);
$host = $x[0];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Options</title>
    <link rel="stylesheet" href="/webui/css/devforge.css">
    <style>
        body {
            background-color: transparent !important;
            color: #f9f4da !important;
            margin: 0;
            padding: 10px;
            box-sizing: border-box;
            font-family: 'Space Grotesk', sans-serif;
        }
        .container {
            width: 100%;
            margin-top: 10px;
            box-sizing: border-box;
        }
        .tab-content {
            display: none;
            width: 100%;
            box-sizing: border-box;
        }
        .tab-content.active {
            display: block;
        }
        .active-tab {
            background-color: #fcba28 !important;
            color: #000000 !important;
            box-shadow: none !important;
            transform: translate(2px, 2px) !important;
        }
        iframe {
            height: 75vh;
            width: 100%;
            border: none;
            background: #1a1a1a;
            overflow-y: auto;
        }
    </style>
</head>
<body>

<div class="flex flex-wrap gap-3 border-b-2 border-border pb-4 mb-6">
    <button onclick="showTab('Root path')" class="tab-button border-2 border-border bg-[#1a1a1a] text-foreground text-xs font-bold uppercase py-2 px-4 hover:bg-[#fcba28] hover:text-black shadow-[2px_2px_0px_0px_rgba(249,244,218,1)] transition-all cursor-pointer">Root</button>
    <button onclick="showTab('Storage path')" class="tab-button border-2 border-border bg-[#1a1a1a] text-foreground text-xs font-bold uppercase py-2 px-4 hover:bg-[#fcba28] hover:text-black shadow-[2px_2px_0px_0px_rgba(249,244,218,1)] transition-all cursor-pointer">Storage</button>
    <button onclick="showTab('ADB path')" class="tab-button border-2 border-border bg-[#1a1a1a] text-foreground text-xs font-bold uppercase py-2 px-4 hover:bg-[#fcba28] hover:text-black shadow-[2px_2px_0px_0px_rgba(249,244,218,1)] transition-all cursor-pointer">ADB</button>
    <button onclick="showTab('WebUI path')" class="tab-button border-2 border-border bg-[#1a1a1a] text-foreground text-xs font-bold uppercase py-2 px-4 hover:bg-[#fcba28] hover:text-black shadow-[2px_2px_0px_0px_rgba(249,244,218,1)] transition-all cursor-pointer">WebUI</button>
    <button onclick="showTab('Clash path')" class="tab-button border-2 border-border bg-[#1a1a1a] text-foreground text-xs font-bold uppercase py-2 px-4 hover:bg-[#fcba28] hover:text-black shadow-[2px_2px_0px_0px_rgba(249,244,218,1)] transition-all cursor-pointer">Clash</button>
</div>

<div class="container block">
    <div id="Root path" class="tab-content active">
        <iframe id="rootFrame" loading="lazy"></iframe>
    </div>
    <div id="Storage path" class="tab-content">
        <iframe id="storageFrame" loading="lazy"></iframe>
    </div>
    <div id="ADB path" class="tab-content">
        <iframe id="adbFrame" loading="lazy"></iframe>
    </div>
    <div id="WebUI path" class="tab-content">
        <iframe id="webuiFrame" loading="lazy"></iframe>
    </div>
    <div id="Clash path" class="tab-content">
        <iframe id="clashFrame" loading="lazy"></iframe>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tab content and remove 'active' class from all iframe containers
    var tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(function(tab) {
        tab.classList.remove('active');
    });

    // Remove 'active-tab' class from all buttons
    var tabLinks = document.querySelectorAll('.tab-button');
    tabLinks.forEach(function(link) {
        link.classList.remove('active-tab');
    });

    // Show the clicked tab
    var activeTab = document.getElementById(tabName);
    if (activeTab) {
        activeTab.classList.add('active');
    }

    // Add 'active-tab' class to the clicked tab button
    var activeLink = document.querySelector('button[onclick="showTab(\'' + tabName + '\')"]');
    if (activeLink) {
        activeLink.classList.add('active-tab');
    }

    // Load iframe src dynamically when tab is active
    if (tabName === 'Root path' && !document.getElementById('rootFrame').src) {
        document.getElementById('rootFrame').src = 'index.php';
    }
    if (tabName === 'Storage path' && !document.getElementById('storageFrame').src) {
        document.getElementById('storageFrame').src = 'http://<?php echo $host; ?>/tiny/index.php?p=sdcard';
    }
    if (tabName === 'ADB path' && !document.getElementById('adbFrame').src) {
        document.getElementById('adbFrame').src = 'http://<?php echo $host; ?>/tiny/index.php?p=data/adb';
    }
    if (tabName === 'WebUI path' && !document.getElementById('webuiFrame').src) {
        document.getElementById('webuiFrame').src = 'http://<?php echo $host; ?>/tiny/index.php?p=data/adb/php7/files/www';
    }
    if (tabName === 'Clash path' && !document.getElementById('clashFrame').src) {
        document.getElementById('clashFrame').src = 'http://<?php echo $host; ?>/tiny/index.php?p=data/adb/box/clash';
    }
}

// Set default active tab
document.addEventListener("DOMContentLoaded", function() {
    showTab('Root path');
});
</script>

</body>
</html>