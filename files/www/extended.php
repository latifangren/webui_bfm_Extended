<?php
$p = $_SERVER['HTTP_HOST'];
$x = explode(':', $p);
$host = $x[0];// Get the host dynamically
session_start([
  'cookie_lifetime' => 31536000, // 1 year
]);

// Include the config file
include 'auth/config.php';

// Check if login is enabled and if the user is not logged in
if (LOGIN_ENABLED && !isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_REQUEST['actionButton'];
    switch ($action) {
        case "disable":
            $myfile = fopen("$moduledir/disable", "w") or die("Unable to open file!");
            break;
        case "enable":
            unlink("$moduledir/disable");
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<meta name="theme-color" content="#5e72e4">
  <title>Theme Luci</title>
 <link rel="icon" href="webui/assets/luci.ico" type="image/x-icon">
  <script src="webui/js/iconify.min.js"></script>
  <link rel="stylesheet" href="webui/css/styles.css">
  <style>
    @font-face {
      font-family: 'Material Icons';
      font-style: normal;
      font-weight: 400;
      src: url('webui/fonts/MaterialIcons-Regular.woff2') format('woff2'),
           url('webui/fonts/MaterialIcons-Regular.woff') format('woff');
    }

    @font-face {
      font-family: 'Orbitron';
      font-style: normal;
      font-weight: 400;
      src: url('webui/fonts/Orbitron-Regular.woff2') format('woff2'),
           url('webui/fonts/Orbitron-Regular.woff') format('woff');
    }

    @font-face {
      font-family: 'Orbitron';
      font-style: normal;
      font-weight: 500;
      src: url('webui/fonts/Orbitron-Medium.woff2') format('woff2'),
           url('webui/fonts/Orbitron-Medium.woff') format('woff');
    }

    @font-face {
      font-family: 'Orbitron';
      font-style: normal;
      font-weight: 700;
      src: url('webui/fonts/Orbitron-Bold.woff2') format('woff2'),
           url('webui/fonts/Orbitron-Bold.woff') format('woff');
    }

    @font-face {
      font-family: 'Orbitron';
      font-style: normal;
      font-weight: 900;
      src: url('webui/fonts/Orbitron-Black.woff2') format('woff2'),
           url('webui/fonts/Orbitron-Black.woff') format('woff');
    }

    @font-face {
      font-family: 'Rajdhani';
      font-style: normal;
      font-weight: 500;
      src: url('webui/fonts/Rajdhani-Medium.woff2') format('woff2'),
           url('webui/fonts/Rajdhani-Medium.woff') format('woff');
    }

    @font-face {
      font-family: 'Rajdhani';
      font-style: normal;
      font-weight: 600;
      src: url('webui/fonts/Rajdhani-SemiBold.woff2') format('woff2'),
           url('webui/fonts/Rajdhani-SemiBold.woff') format('woff');
    }

    @font-face {
      font-family: 'Rajdhani';
      font-style: normal;
      font-weight: 700;
      src: url('webui/fonts/Rajdhani-Bold.woff2') format('woff2'),
           url('webui/fonts/Rajdhani-Bold.woff') format('woff');
    }

    @font-face {
      font-family: 'Rovelink';
      font-style: normal;
      font-weight: normal;
      src: url('webui/fonts/Rovelink.otf') format('opentype');
    }

    @font-face {
      font-family: 'Cyberpunk';
      font-style: normal;
      font-weight: normal;
      src: url('webui/fonts/Cyberpunk.ttf') format('truetype');
    }

    @font-face {
      font-family: 'SpaceArmor';
      font-style: normal;
      font-weight: normal;
      src: url('webui/fonts/SPACE ARMOR.otf') format('opentype');
    }

    .material-icons {
      font-family: 'Material Icons';
      font-weight: normal;
      font-style: normal;
      font-size: 24px;
      line-height: 1;
      letter-spacing: normal;
      text-transform: none;
      display: inline-block;
      white-space: nowrap;
      word-wrap: normal;
      direction: ltr;
      -webkit-font-smoothing: antialiased;
    }
    
    /* Hapus semua efek glow */
    .decorative-img::before,
    .decorative-img-sidebar::before {
      content: 'BOX UI';
      white-space: pre;
      font-family: 'SpaceArmor', sans-serif;
      font-weight: normal;
      letter-spacing: 2px;
      font-size: 24px;
      color: #FECA0A;
      text-shadow: none; /* Hapus text-shadow */
    }
    
    /* Media query untuk tampilan mobile */
    @media screen and (max-width: 768px) {
      .decorative-img-sidebar::before {
        content: ''; /* Menghilangkan teks pada elemen kedua di mobile */
      }
      
      /* Menyesuaikan posisi untuk tampilan mobile */
      .decorative-img::before {
        font-size: 20px;
      }
      
      /* Menyembunyikan extended-text pada mobile jika masih tumpang tindih */
      .extended-text {
        font-size: 22px;
        letter-spacing: 2px;
      }
    }
    
    /* Extended text style */
    .extended-text {
      position: absolute;
      top: 15px;
      left: 45%;
      transform: translateX(-50%);
      font-family: 'SpaceArmor', sans-serif;
      font-size: 26px;
      font-weight: normal;
      color: #FECA0A;
      z-index: 1;
      text-transform: uppercase;
      letter-spacing: 3px;
      padding-bottom: 2px;
      display: inline-block;
    }

    /* Version text */
    .version-text {
      position: absolute;
      top: 20px;
      left: calc(45% + 65px);
      font-family: 'SpaceArmor', sans-serif;
      font-size: 14px;
      font-weight: normal;
      color: #FECA0A;
      z-index: 1;
      text-transform: uppercase;
      letter-spacing: 1px;
      display: inline-block;
      margin-left: 10px;
    }

    /* Media query untuk tampilan mobile */
    @media screen and (max-width: 768px) {
      .extended-text {
        font-size: 22px;
        top: 12px;
        left: 45%;
      }
      
      .version-text {
        font-size: 12px;
        top: 17px;
        left: calc(45% + 55px);
      }
      
      .decorative-img::before {
        font-size: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <!-- Extended Text in the middle -->
    <div class="extended-text">XTD</div>
    <!-- Version text -->
    <div class="version-text">v2.0</div>
    
    <!-- Sidebar -->
    <div id="mySidebar" class="sidebar">
      <!-- Decorative PNG Image at the Top -->
      <div class="decorative-img"></div>

      <ul>
        <li>
          <button class="dropdown-btn">
            <span class="iconify" data-icon="fa:dashboard" style="font-size: 18px; color: #FECA0A;" id="clash"></span> Clash Dashboard
            <span class="dropdown-icon iconify" data-icon="ci:caret-right-sm" style="font-size: 25px;"></span>
          </button>
          <div class="dropdown-container">
            <a onclick="loadContent('http://<?php echo $host; ?>:9090/ui/?hostname=<?php echo $host; ?>&port=9090'); closeDropdown(); addUnderline(this)">Default</a>
            <a onclick="loadContent('http://<?php echo $host; ?>/zashboard/ui#/setup?hostname=<?php echo $host; ?>&port=9090'); closeDropdown(); addUnderline(this)">Zashboard</a>
          </div>
        </li>
        <li>
          <button class="dropdown-btn">
            <span class="iconify" data-icon="material-symbols:dashboard-rounded" style="font-size: 19px;" id="status"></span>Status
            <span class="dropdown-icon iconify" data-icon="ci:caret-right-sm" style="font-size: 25px;"></span> <!-- Dropdown icon -->
          </button>
          <div class="dropdown-container">
            <a onclick="loadContent('/webui/monitor/index.php'); closeDropdown(); addUnderline(this)">Overview</a>
			<a onclick="loadContent('/webui/monitor/cpu_monitor_standalone.php'); closeDropdown(); addUnderline(this)">Cpu Monitor</a>
            <a onclick="loadContent('/tools/logs.php'); closeDropdown(); addUnderline(this)">Magisk Log</a>
          </div>
        </li>
        <li>
          <button class="dropdown-btn">
            <span class="iconify" data-icon="fa:gear" style="font-size: 17px;" id="gear"></span>System
            <span class="dropdown-icon iconify" data-icon="ci:caret-right-sm" style="font-size: 25px;"></span> <!-- Dropdown icon -->
          </button>
          <div class="dropdown-container">
            <a onclick="loadContent('/tiny/opsi.php'); closeDropdown(); addUnderline(this)"> File Manager</a>
            <a onclick="loadContent('/auth/change_password.php'); closeDropdown(); addUnderline(this)"> Administration</a>
            <a onclick="loadContent('/auth/manage_login.php'); closeDropdown(); addUnderline(this)"> Manage Login</a>
            <a onclick="loadContent('tools/reboot.php'); closeDropdown(); addUnderline(this)"> Reboot</a>
          </div>
        </li>
        <li>
          <button class="dropdown-btn">
            <span class="iconify" data-icon="ic:twotone-miscellaneous-services" style="font-size: 20px;" id="services"></span> Services
            <span class="dropdown-icon iconify" data-icon="ci:caret-right-sm" style="font-size: 25px;"></span> <!-- Dropdown icon -->
          </button>
          <div class="dropdown-container">
            <a onclick="loadContent('/tools/smsviewer.php'); closeDropdown(); addUnderline(this)"> SMS Viewer</a>
            <a onclick="loadContent('/tools/ocgen/index.php'); closeDropdown(); addUnderline(this)"> Config Generator</a>
            <a onclick="loadContent('/tools/modpes.php'); closeDropdown(); addUnderline(this)"> Airplane Pilot</a>
            <a onclick="loadContent('/tools/pingmonitor.php'); closeDropdown(); addUnderline(this)"> Ping Monitor</a>
            <a onclick="loadContent('/tools/speedtest/speedtest.php'); closeDropdown(); addUnderline(this)"> Speed Test</a>
            <a onclick="loadContent('http://<?php echo $p; ?>:3001'); closeDropdown(); addUnderline(this)"> Terminal</a>
          </div>
        </li>
        <li>
          <button class="dropdown-btn">
            <span class="iconify" data-icon="solar:box-bold-duotone" style="font-size: 19px;" id="box"></span>Box
            <span class="dropdown-icon iconify" data-icon="ci:caret-right-sm" style="font-size: 25px;"></span> <!-- Dropdown icon -->
          </button>
          <div class="dropdown-container">
            <a onclick="loadContent('/tools/opsi_box.php'); closeDropdown(); addUnderline(this)"> BFR</a>
          </div>
        </li>
        <li>
          <button class="dropdown-btn">
            <span class="iconify" data-icon="icon-park-solid:network-tree" style="font-size: 18px;" id="network"></span> Network
            <span class="dropdown-icon iconify" data-icon="ci:caret-right-sm" style="font-size: 25px;"></span> <!-- Dropdown icon -->
          </button>
          <div class="dropdown-container">
            <a onclick="loadContent('/tools/opsi_interface.php'); closeDropdown(); addUnderline(this)">Interface</a>
            <a onclick="loadContent('/tools/networktools.php'); closeDropdown(); addUnderline(this)">Network Tools</a>
            <a onclick="loadContent('/tools/hotspot/hotspot.php'); closeDropdown(); addUnderline(this)">Wireless</a>
            <a onclick="loadContent('/tools/vnstat.php'); closeDropdown(); addUnderline(this)">Bandwith</a>
            <a onclick="loadContent('/tools/manage_hotspot.php'); closeDropdown(); addUnderline(this)">Hotspot manager</a>
          </div>
        </li>
        <li>
          <a onclick="loadContent('/about.php'); closeDropdown(); addUnderline(this)">
            <span class="iconify" data-icon="mdi:timeline-text" style="font-size: 18px; margin-right: 5px; color: #5e72e4;"></span> About
          </a>
        </li>
        <li>
          <a onclick="loadContent('/article.html'); closeDropdown(); addUnderline(this)">
            <span class="iconify" data-icon="mdi:file-document-outline" style="font-size: 18px; margin-right: 5px; color: #5e72e4;"></span> Dokumentasi
          </a>
        </li>
        <li>
          <a href="https://github.com/latifangren/webui_bfm_argon_Extended" target="_blank" rel="noopener noreferrer">
            <span class="iconify" data-icon="mdi:update" style="font-size: 18px; margin-right: 5px; color: #2ecc71;"></span> Update WebUI
          </a>
        </li>
       <li><a href="javascript:void(0);" onclick="logoutAndRefresh()">
      <span class="iconify" data-icon="ri:logout-box-line" style="font-size: 18px; margin-right: 5px;"></span> Logout</a></li>
      </ul>
    </div>
    <!-- Container untuk loading spinner dan teks "Loading..." -->
<div id="loading-container" class="loading-container" style="display:none;">
  <span class="iconify" data-icon="svg-spinners:bars-rotate-fade" style="font-size: 20px;"></span>
  <span>Loading...</span>
</div>

    
    <!-- Main Content -->
    <div class="main-panel">
      <!-- The main content panel remains empty -->
    </div>
    
    <!-- Content loaded in iframe will appear here -->
    <div id="iframeContainer" class="iframe-container"></div>
  </div>

  <!-- Overlay to block interactions outside the sidebar -->
  <div id="overlay" class="overlay" onclick="closeNav()"></div>

  <!-- Decorative PNG image next to the toggle button -->
  <div class="decorative-img-sidebar"></div>

  <!-- Toggle button to open/close the sidebar -->
  <button class="toggle-btn" onclick="openNav()"><span class="iconify" data-icon="icon-park-solid:menu-fold-one" style="font-size: 27px;"></span></button>

  <!-- Refresh button -->
  <button class="refresh-btn" onclick="refreshPage()">Refresh</button>

<script>
  // Open the sidebar
  function openNav() {
    document.getElementById("mySidebar").classList.add("open");
    document.getElementById("overlay").classList.add("open");
    document.querySelector(".toggle-btn").style.display = "none"; // Hide the toggle button after sidebar opens
    document.documentElement.style.overflow = "hidden";
    document.body.style.overflow = "hidden";
  }

  // Close the sidebar
  function closeNav() {
    document.getElementById("mySidebar").classList.remove("open");
    document.getElementById("overlay").classList.remove("open");
    document.querySelector(".toggle-btn").style.display = "block"; // Show the toggle button when the sidebar is closed
    document.body.style.overflow = "auto";
    document.documentElement.style.overflow = "auto";
  }

// Load content dynamically
function loadContent(url) {
  // Tampilkan efek loading
  document.getElementById("loading-container").style.display = "flex";
  
  // Close all open dropdowns
  closeDropdown();

  const iframeContainer = document.getElementById('iframeContainer');
  iframeContainer.innerHTML = `<iframe src="${url}" allowfullscreen></iframe>`;
  closeNav(); // Close the sidebar automatically after loading content

  // Tunggu iframe untuk selesai dimuat
  const iframe = iframeContainer.querySelector("iframe");
  iframe.onload = function() {
    // Sembunyikan efek loading setelah konten dimuat
    document.getElementById("loading-container").style.display = "none";

    // Mengatur tinggi iframe berdasarkan konten
    adjustIframeHeight(iframe);
  };
}

function adjustIframeHeight(iframe) {
  const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
  const body = iframeDoc.body;
  
  // Mengambil tinggi konten dalam iframe
  const contentHeight = body.scrollHeight;

  // Menetapkan tinggi iframe agar sesuai dengan batas max-height dan min-height
  const maxHeight = 1000 * window.innerHeight / 100; // max-height dalam vh
  const minHeight = 110 * window.innerHeight / 100; // min-height dalam vh

  // Menyesuaikan tinggi iframe berdasarkan konten
  iframe.style.height = Math.min(Math.max(contentHeight, minHeight), maxHeight) + 'px';
}



  // Function to refresh the page
  function refreshPage() {
    location.reload();
  }

  // Close the dropdown container when an item is clicked
  function closeDropdown() {
    var dropdownContainers = document.getElementsByClassName("dropdown-container");
    for (var i = 0; i < dropdownContainers.length; i++) {
      dropdownContainers[i].classList.remove("open");
    }

    // Also reset the dropdown button's active state
    var dropdownButtons = document.getElementsByClassName("dropdown-btn");
    for (var i = 0; i < dropdownButtons.length; i++) {
      dropdownButtons[i].classList.remove("active");
      var dropdownIcon = dropdownButtons[i].querySelector(".dropdown-icon");
      if (dropdownIcon) {
        dropdownIcon.style.transform = "rotate(0deg)";
      }
    }
  }

  // Add underline to clicked item in dropdown and remove it from other items
  function addUnderline(element) {
    var allItems = document.querySelectorAll('.dropdown-container a');
    allItems.forEach(function(item) {
      item.classList.remove("clicked");
    });

    element.classList.add("clicked");
  }

  // Dropdown function for File Manager
  var dropdown = document.getElementsByClassName("dropdown-btn");
  for (var i = 0; i < dropdown.length; i++) {
    dropdown[i].addEventListener("click", function(event) {
      event.stopPropagation(); // Prevent closing on click inside dropdown
      var dropdownContent = this.nextElementSibling;
      var dropdownIcon = this.querySelector(".dropdown-icon");

      // Toggle the clicked dropdown
      if (dropdownContent.classList.contains("open")) {
        dropdownContent.classList.remove("open"); // Close the dropdown
        dropdownIcon.style.transform = "rotate(0deg)"; // Reset rotation
        this.classList.remove("active");
      } else {
        var allDropdowns = document.getElementsByClassName("dropdown-container");
        for (var j = 0; j < allDropdowns.length; j++) {
          if (allDropdowns[j] !== dropdownContent) {
            allDropdowns[j].classList.remove("open");
            var button = allDropdowns[j].previousElementSibling;
            if (button) {
              button.classList.remove("active");
              button.querySelector(".dropdown-icon").style.transform = "rotate(0deg)";
            }
          }
        }
        dropdownContent.classList.add("open"); // Open the dropdown
        this.classList.add("active");
        dropdownIcon.style.transform = "rotate(90deg)"; // Apply rotation
      }
    });
  }

  // Function to logout and refresh the page
  function logoutAndRefresh() {
    // Refresh the page before navigating to logout
    location.reload();
    // Redirect to the logout page after refresh
    window.location.href = 'auth/logout.php';
  }

  // Load "System Info" content automatically when the page loads
  window.onload = function() {
    loadContent('/webui/monitor/index.php'); // Automatically load System Info
  }

  // Close dropdown if clicked outside the sidebar or dropdown
  document.addEventListener("click", function(event) {
    var sidebar = document.getElementById("mySidebar");
    if (!sidebar.contains(event.target)) {
      closeDropdown();
    }
  });

  // Prevent scrolling when the sidebar is open
  document.getElementById("mySidebar").addEventListener("transitionend", function() {
    // Optionally, do something after the sidebar transition ends
    // This is where you can reset scroll if needed after the transition is finished
    if (!document.getElementById("mySidebar").classList.contains("open")) {
      document.body.style.overflow = "auto"; // Ensure scroll is enabled after sidebar close
    }
  });

</script>
</body>
</html>