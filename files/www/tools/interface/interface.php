<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interface Management</title>
    <script src="../../../webui/js/iconify/iconify.min.js"></script>
    <style>
body {
    font-family: Arial, sans-serif;
    background-color: transparent;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    margin: 0;
    min-height: 2vh;
}

.container {
    width: 320px;
    background-color: #1a1a1a;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
    padding: 20px;
    text-align: center;
    margin-bottom: 10px;
    border: 1px solid rgba(254, 202, 10, 0.2);
    border-radius: 8px;
    color: #F1F1F1;
}

.tab-container {
    width: 100px;
    height: 15px;
    background-color: #FECA0A;
    color: #000000;
    margin: 0 auto -3px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    position: relative;
    z-index: 1;
    box-shadow: 0 -5px 10px rgba(0, 0, 0, 0.3);
    font-size: 10px;
    border-radius: 3px 3px 0 0;
}

.icon-container {
    width: 100px;
    height: 50px;
    background-color: #1a1a1a;
    border-radius: 5px;
    margin: 0 auto 25px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    border: 1px solid #FECA0A;
}

.icon-container .iconify {
    font-size: 18px;
    color: #FECA0A;
}

.icon-container p {
    margin: 5px 0 0;
    font-size: 10px;
    color: #FECA0A;
    font-weight: bold;
}

.status {
    text-align: left;
    margin-bottom: 20px;
    font-size: 12px;
    color: #F1F1F1;
}

.status p {
    margin: 0px 0;
}

.status p strong {
    color: #FECA0A;
    font-weight: 600;
}

.buttons {
    display: flex;
    justify-content: space-between;
}

.buttons button {
    flex: 1;
    margin: 0 3px auto;
    padding: 6px 0px;
    font-size: 12px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    color: #000000;
    transition: all 0.3s ease;
    border: 1px solid transparent;
}

.buttons button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

.start {
    background-color: #FECA0A;
}

.stop {
    background-color: #FECA0A;
}

.edit, .restart {
    background-color: #FECA0A;
}

.edit-form {
    display: none;
    position: fixed;
    z-index: 2;
    left: 50%;
    top: 40%;
    transform: translate(-50%, -50%);
    width: 300px;
    height: 280px;
    background-color: #1a1a1a;
    padding: 10px;
    box-sizing: border-box;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
    border: 1px solid #FECA0A;
    color: #F1F1F1;
}

.edit-form h3 {
    margin: 20px 0;
    text-align: center;
    padding-left: 20px;
    font-size: 1em;
    font-weight: 550;
    color: #FECA0A;
}

.edit-form p {
    margin: 2px 0;
    text-align: left;
    padding-left: 20px;
    font-size: 0.8em;
    font-weight: 500;
    color: #FECA0A;
}

.edit-form textarea {
    width: 80%;
    margin: 0px 0;
    padding: 8px;
    border-radius: 3px;
    border: 1px solid #FECA0A;
    font-size: 10px;
    height: 30px;
    background-color: #000000;
    color: #F1F1F1;
}

.edit-form button {
    margin-top: 20px;
    margin-right: 10px;
    padding: 5px 10px;
    background-color: #FECA0A;
    color: #000000;
    border: none;
    border-radius: 2px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
    font-weight: 600;
}

.edit-form button:hover {
    background-color: #e5b609;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

/* Dark mode styles - masih tetap menggunakan tema hitam-kuning */
@media (prefers-color-scheme: dark) {
    body {
        background-color: transparent;
        color: #F1F1F1;
    }

    .container {
        background-color: #1a1a1a;
        color: #F1F1F1;
    }

    .tab-container {
        background-color: #FECA0A;
        color: #000000;
    }

    .icon-container {
        background-color: #1a1a1a;
        border: 1px solid #FECA0A;
    }
    
    .icon-container p {
        color: #FECA0A;
    }

    .icon-container .iconify {
        color: #FECA0A;
    }

    .status {
        color: #F1F1F1;
    }
    
    .status p strong {
        color: #FECA0A;
    }

    .buttons button {
        color: #000000;
    }

    .start {
        background-color: #FECA0A !important;
    }

    .stop {
        background-color: #FECA0A !important;
    }

    .edit, .restart {
        background-color: #FECA0A !important;
    }

    .edit-form {
        background-color: #1a1a1a;
        color: #F1F1F1;
        border: 1px solid #FECA0A;
    }
    
    .edit-form h3, .edit-form p {
        color: #FECA0A;
    }
    
    .edit-form textarea {
        background-color: #000000;
        color: #F1F1F1;
        border: 1px solid #FECA0A;
    }

    .edit-form button {
        background-color: #FECA0A;
        color: #000000;
    }

    .edit-form button:hover {
        background-color: #e5b609;
    }
}

    </style>
</head>
<body>
    <!-- WLAN Interface -->
    <div class="container" id="wlan-container">
        <div class="tab-container">
            <p><strong>Hotspot</strong></p>
        </div>
        <div class="icon-container">
            <span class="iconify" data-icon="basil:hotspot-outline"></span>
            <p>wlan+</p>
        </div>
        <div class="status" id="wlan-status">
            <p><strong>Status:</strong> <span id="wlan-status-text">Loading...</span></p>
            <p><strong>MAC:</strong> <span id="wlan-mac">Loading...</span></p>
            <p><strong>RX:</strong> <span id="wlan-rx">Loading...</span></p>
            <p><strong>TX:</strong> <span id="wlan-tx">Loading...</span></p>
            <p><strong>IPv4:</strong> <span id="wlan-ip">Loading...</span></p>
        </div>
        <div class="buttons">
            <button class="start" onclick="changeInterfaceStatus('wlan', 'enable')"><strong>START</strong></button>
            <button class="stop" onclick="changeInterfaceStatus('wlan', 'disable')"><strong>STOP</strong></button>
            <button class="edit" onclick="editInterfaceCommands('wlan')"><strong>EDIT</strong></button>
            <button class="restart" onclick="resetInterfaceCommands('wlan')"><strong>RESET</strong></button>
        </div>
        
        <!-- Edit Command Form -->
        <div id="wlan-edit-form" class="edit-form">
          <div style="text-align:center;">
            <h3>Edit Command For wlan+</h3>
            <p>Enable</p>
            <textarea id="wlan-enable-command" rows="3" placeholder="Contoh: service call tethering 4 null s16 random"></textarea><br>
            <p>Disable</p>
            <textarea id="wlan-disable-command" rows="3" placeholder="Contoh: su -c ifconfig wlan0 down"></textarea><br>
            <button onclick="saveInterfaceCommands('wlan')">Save</button>
            <button onclick="cancelEdit('wlan')">Cancel</button>
          </div>
        </div>
    </div>

    <!-- RNDIS Interface -->
    <div class="container" id="rndis-container">
        <div class="tab-container">
            <p><strong>USB Tethering</strong></p>
        </div>
        <div class="icon-container">
            <span class="iconify" data-icon="tdesign:usb-filled"></span>
            <p>rndis+</p>
        </div>
        <div class="status" id="rndis-status">
            <p><strong>Status:</strong> <span id="rndis-status-text">Loading...</span></p>
            <p><strong>MAC:</strong> <span id="rndis-mac">Loading...</span></p>
            <p><strong>RX:</strong> <span id="rndis-rx">Loading...</span></p>
            <p><strong>TX:</strong> <span id="rndis-tx">Loading...</span></p>
            <p><strong>IPv4:</strong> <span id="rndis-ip">Loading...</span></p>
        </div>
        <div class="buttons">
            <button class="start" onclick="changeInterfaceStatus('rndis', 'enable')"><strong>START</strong></button>
            <button class="stop" onclick="changeInterfaceStatus('rndis', 'disable')"><strong>STOP</strong></button>
            <button class="edit" onclick="editInterfaceCommands('rndis')"><strong>EDIT</strong></button>
            <button class="restart" onclick="resetInterfaceCommands('rndis')"><strong>RESET</strong></button>
        </div>
        
        <!-- Edit Command Form -->
        <div id="rndis-edit-form" class="edit-form">
           <div style="text-align:center;">
            <h3>Edit Command For rndis+</h3>
            <p>Enable</p>
            <textarea id="rndis-enable-command" rows="3" placeholder="Contoh: su -c svc usb setFunctions rndis"></textarea><br>
            <p>Disable</p>
            <textarea id="rndis-disable-command" rows="3" placeholder="Contoh: su -c svc usb setFunctions mtp"></textarea><br>
            <button onclick="saveInterfaceCommands('rndis')">Save</button>
            <button onclick="cancelEdit('rndis')">Cancel</button>
          </div>
        </div>
    </div>

    <!-- Ethernet Interface -->
    <div class="container" id="eth-container">
        <div class="tab-container">
            <p><strong>Ethernet</strong></p>
        </div>
        <div class="icon-container">
            <span class="iconify" data-icon="bi:ethernet"></span>
            <p>eth+</p>
        </div>
        <div class="status" id="eth-status">
            <p><strong>Status:</strong> <span id="eth-status-text">Loading...</span></p>
            <p><strong>MAC:</strong> <span id="eth-mac">Loading...</span></p>
            <p><strong>RX:</strong> <span id="eth-rx">Loading...</span></p>
            <p><strong>TX:</strong> <span id="eth-tx">Loading...</span></p>
            <p><strong>IPv4:</strong> <span id="eth-ip">Loading...</span></p>
        </div>
        <div class="buttons">
            <button class="start" onclick="changeInterfaceStatus('eth', 'enable')"><strong>START</strong></button>
            <button class="stop" onclick="changeInterfaceStatus('eth', 'disable')"><strong>STOP</strong></button>
            <button class="edit" onclick="editInterfaceCommands('eth')"><strong>EDIT</strong></button>
            <button class="restart" onclick="resetInterfaceCommands('eth')"><strong>RESET</strong></button>
        </div>
        
        <!-- Edit Command Form -->
        <div id="eth-edit-form" class="edit-form">
            <div style="text-align:center;">
            <h3>Edit Command For eth+</h3>
            <p>Enable</p>
            <textarea id="eth-enable-command" rows="3" placeholder="Contoh: su -c ifconfig eth0 up"></textarea><br>
            <p>Disable</p>
            <textarea id="eth-disable-command" rows="3" placeholder="Contoh: su -c ifconfig eth0 down"></textarea><br>
            <button onclick="saveInterfaceCommands('eth')">Save</button>
            <button onclick="cancelEdit('eth')">Cancel</button>
          </div>
        </div>
    </div>

    <script>
        function changeInterfaceStatus(interface, action) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'script.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    const response = JSON.parse(xhr.responseText);
                    updateStatus(interface, response);
                }
            };

            xhr.send(`interface=${interface}&action_type=${action}`);
        }

        function updateStatus(interface, data) {
            document.getElementById(`${interface}-status-text`).innerText = data.status;
            document.getElementById(`${interface}-mac`).innerText = data.mac;
            document.getElementById(`${interface}-rx`).innerText = data.rx;
            document.getElementById(`${interface}-tx`).innerText = data.tx;
            document.getElementById(`${interface}-ip`).innerText = data.ip;
        }
        
        function resetInterfaceCommands(interface) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'script.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
            alert("Commands reset successfully!");
        }
           };

            xhr.send(`interface=${interface}&action_type=reset`);
        }

        function editInterfaceCommands(interface) {
            document.getElementById(`${interface}-edit-form`).style.display = 'block';

            // Ambil perintah
            const enableCommand = localStorage.getItem(`${interface}-enable-command`) || "";
            const disableCommand = localStorage.getItem(`${interface}-disable-command`) || "";

            // Isi kolom dengan perintah yang sesuai
            document.getElementById(`${interface}-enable-command`).value = enableCommand;
            document.getElementById(`${interface}-disable-command`).value = disableCommand;
        }

        function saveInterfaceCommands(interface) {
            const enableCommand = document.getElementById(`${interface}-enable-command`).value;
            const disableCommand = document.getElementById(`${interface}-disable-command`).value;

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'script.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    alert("Commands saved successfully!");
                    cancelEdit(interface);
                }
            };

            xhr.send(`interface=${interface}&action_type=edit&enable_command=${encodeURIComponent(enableCommand)}&disable_command=${encodeURIComponent(disableCommand)}`);
        }

        function cancelEdit(interface) {
            document.getElementById(`${interface}-edit-form`).style.display = 'none';
        }

        setInterval(() => {
            const interfaces = ['wlan', 'rndis', 'eth'];
            interfaces.forEach(interface => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'script.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        const response = JSON.parse(xhr.responseText);
                        updateStatus(interface, response);
                    }
                };

                xhr.send(`interface=${interface}&action_type=status`);
            });
        }, 1500);
    </script>
</body>
</html>