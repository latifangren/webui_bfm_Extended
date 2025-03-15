<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <title>Interface</title>
    <style>
        @font-face {
      font-family: 'LemonMilkProRegular';
      src: url('../webui/fonts/LemonMilkProRegular.otf') format('opentype');
    }
        body {
            font-family: 'LemonMilkProRegular';
            background-color: #000000;
            margin: 0;
            padding: 0;
            color: #F1F1F1;
        }
        header {
            padding: 0;
            text-align: center;
            position: relative;
            width: 100%;
            margin-bottom: 10px; /* Reduced the bottom margin */
        }
        .header-top {
            background-color: #000000;
            padding: 5px;
        }
        .header-bottom {
            background-color: #000000;
            padding: 5px;
        }
        header h1 {
            margin: 0;
            font-size: 0.8em;
            color: #FECA0A;
        }
        .new-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin-bottom: 0px;
            border-radius: 5px;
            width: 90%;
            height: 85%;
            padding: 10px;
            box-sizing: border-box;
            background-color: #1a1a1a;
            color: #F1F1F1;
            text-align: center;
            z-index: 2;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(254, 202, 10, 0.2);
        }
        .new-container p {
            text-align: left;
            font-size: 0.7em;
            color: #F1F1F1;
            margin-top: 5px;
            margin-left: 18px;
            font-weight: 200;
            float: left;
            margin-right: 2px; 
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .new-container p:hover {
            color: #FECA0A;
        }
        .container {
            width: 100%;
            padding: 10px; /* Removed padding */
            margin-top: -10px; /* Removed margin-top to make the content move upwards */
            box-sizing: border-box;
        }
        .tab-content {
            display: none;
            width: 100%;
            box-sizing: border-box;
            background-color: #000000;
        }
        .tab-content.active {
            display: block;
        }

        p.active-tab {
            color: #FECA0A;
            position: relative; /* Untuk mengatur garis bawah */
            padding-bottom: 17px; /* Jarak antara teks dan garis bawah */
        }

        p.active-tab::before {
            content: ''; /* Membuat pseudo-element */
            position: absolute;
            top: -20px;
            left: -15%;
            width: 130%;
            height: 150%;
            background-color: rgba(254, 202, 10, 0.1);
        }

        p.active-tab::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: -15%;
            width: 130%;
            height: 3px;
            background-color: #FECA0A;
        }
        iframe {
            width: 100%;
            height: 770px;
            border: none;
            background-color: #000000;
        }
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #000000;
                color: #F1F1F1;
            }

            .new-container, .new-container p {
                background-color: #1a1a1a;
                color: #F1F1F1;
            }

            .tab-content {
                background-color: #000000;
            }
            
            p.active-tab::before {
                background-color: rgba(254, 202, 10, 0.1);
            }

            p.active-tab{
                color: #FECA0A; 
            }
            p.active-tab::after {
                background-color: #FECA0A; 
            }
            iframe {
                background-color: #000000;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="new-container">
        <p onclick="showTab('INTERFACE')">Interface</p>
        <p onclick="showTab('IPSET')">IP Set</p>
    </div>
    <div class="header-top">
        <h1>o</h1>
    </div>
    <div class="header-bottom">
        <h1>o</h1>
    </div>
</header>

<div class="container">
    <div id="INTERFACE" class="tab-content active">
        <iframe id="interface" loading="lazy"></iframe>
    </div>
    <div id="IPSET" class="tab-content">
        <iframe id="ipset" loading="lazy"></iframe>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tab content and remove 'active' class from all iframe containers
    var tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(function(tab) {
        tab.classList.remove('active');
    });

    // Remove 'active-tab' class from all <p> elements
    var tabLinks = document.querySelectorAll('.new-container p');
    tabLinks.forEach(function(link) {
        link.classList.remove('active-tab');
    });

    // Show the clicked tab
    var activeTab = document.getElementById(tabName);
    if (activeTab) {
        activeTab.classList.add('active');
    }

    // Add 'active-tab' class to the clicked tab link
    var activeLink = document.querySelector('p[onclick="showTab(\'' + tabName + '\')"]');
    if (activeLink) {
        activeLink.classList.add('active-tab');
    }

    // Load iframe src dynamically when tab is active
    if (tabName === 'INTERFACE' && !document.getElementById('interface').src) {
        document.getElementById('interface').src = 'interface/interface.php';
    }
    if (tabName === 'IPSET' && !document.getElementById('ipset').src) {
        document.getElementById('ipset').src = 'interface/ipset.php';
    }
}

// Set default active tab
document.addEventListener("DOMContentLoaded", function() {
    showTab('INTERFACE');
});
</script>

</body>
</html>