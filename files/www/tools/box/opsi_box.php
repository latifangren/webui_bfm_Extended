<?php
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
    <title>Box Options</title>
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
    <button onclick="showTab('BFR')" class="tab-button border-2 border-border bg-[#1a1a1a] text-foreground text-xs font-bold uppercase py-2 px-4 hover:bg-[#fcba28] hover:text-black shadow-[2px_2px_0px_0px_rgba(249,244,218,1)] transition-all cursor-pointer">Box For Root</button>
    <button onclick="showTab('SERVICES')" class="tab-button border-2 border-border bg-[#1a1a1a] text-foreground text-xs font-bold uppercase py-2 px-4 hover:bg-[#fcba28] hover:text-black shadow-[2px_2px_0px_0px_rgba(249,244,218,1)] transition-all cursor-pointer">Services</button>
    <button onclick="showTab('YAML')" class="tab-button border-2 border-border bg-[#1a1a1a] text-foreground text-xs font-bold uppercase py-2 px-4 hover:bg-[#fcba28] hover:text-black shadow-[2px_2px_0px_0px_rgba(249,244,218,1)] transition-all cursor-pointer">Config.yaml</button>
    <button onclick="showTab('JSON')" class="tab-button border-2 border-border bg-[#1a1a1a] text-foreground text-xs font-bold uppercase py-2 px-4 hover:bg-[#fcba28] hover:text-black shadow-[2px_2px_0px_0px_rgba(249,244,218,1)] transition-all cursor-pointer">Config.json</button>
</div>

<div class="container block">
    <div id="BFR" class="tab-content active">
        <iframe id="bfr" loading="lazy"></iframe>
    </div>
    <div id="SERVICES" class="tab-content">
        <iframe id="services" loading="lazy"></iframe>
    </div>
    <div id="YAML" class="tab-content">
        <iframe id="yaml" loading="lazy"></iframe>
    </div>
    <div id="JSON" class="tab-content">
        <iframe id="json" loading="lazy"></iframe>
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
    if (tabName === 'BFR' && !document.getElementById('bfr').src) {
        document.getElementById('bfr').src = '/pages/box/executed.php';
    }
    if (tabName === 'SERVICES' && !document.getElementById('services').src) {
        document.getElementById('services').src = '/pages/box/settings.php';
    }
    if (tabName === 'YAML' && !document.getElementById('yaml').src) {
        document.getElementById('yaml').src = 'http://<?php echo $host; ?>/tiny/index.php?p=data%2Fadb%2Fbox%2Fclash&view=config.yaml';
    }
    if (tabName === 'JSON' && !document.getElementById('json').src) {
        document.getElementById('json').src = 'http://<?php echo $host; ?>/tiny/index.php?p=data%2Fadb%2Fbox%2Fsing-box&view=config.json';
    }
}

// Set default active tab
document.addEventListener("DOMContentLoaded", function() {
    showTab('BFR');
});
</script>

</body>
</html>