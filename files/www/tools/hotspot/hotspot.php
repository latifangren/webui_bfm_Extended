<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Hotspot Android</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #000000;
            color: #F1F1F1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        h1 {
            text-align: center;
            color: #F1F1F1;
            font-size: 20px;
        }
        .note {
            text-align: center;
            color: #aaa;
            font-size: 12px;
        }

    header {
      padding: 0;
      text-align: center;
      position: relative;
      width: 100%;
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
      color: #F1F1F1;
    }
    .new-container {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      margin-bottom: 100px;
      border-radius: 5px;
      width: 90%;
      height: 100%;
      padding: 10px;
      box-sizing: border-box;
      background-color: #1a1a1a;
      color: #F1F1F1;
      text-align: center;
      z-index: 2;
    }
    .new-container p {
      text-align: left;
      font-size: 1em;
      color: #FECA0A;
      margin-top: 5px;
      margin-left: 10px;
      font-weight: bold;
    }
    .container {
      border-radius: 5px;
      padding: 10px;
      margin-bottom: 20px;
      margin-top: 30px;
      width: 85%;
      height: 100%;
      background-color: #1a1a1a;
      color: #F1F1F1;
      box-shadow: 0 4px 20px rgba(254, 202, 10, 0.1);
    }
        label {
            margin-top: 5px;
            display: block;
            font-weight: bold;
            font-size: 12px;
            color: #FECA0A;
        }
        input[type="text"], input[type="password"] {
            width: 93%;
            padding: 12px;
            margin-top: 5px;
            border: 1px solid #FECA0A;
            border-radius: 10px;
            background-color: #222;
            color: #F1F1F1;
        }
        input[type="submit"] {
            background-color: #FECA0A;
            color: #000;
            border: none;
            padding: 12px;
            border-radius: 3px;
            cursor: pointer;
            width: auto;
            margin: 20px auto;
            display: block;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #e5b609;
        }
        .result {
            margin-top: 15px;
            padding: 8px;
            background-color: #222;
            border: 1px solid #FECA0A;
            border-radius: 3px;
            color: #FECA0A;
            display: none;
        }
        
        /* New loading indicator */
        .loading-indicator {
            position: fixed;
            top: 30%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(0, 0, 0, 0.8);
            color: #FECA0A;
            padding: 12px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(254,202,10,0.2);
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-container {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 56%; 
            transform: translateY(-50%);
            cursor: pointer;
            color: #FECA0A;
        }

/* Dark Mode Styles */
@media (prefers-color-scheme: dark) {
    body {}
    h1 {}
    .note {}
    .new-container, .new-container p {}
    .container {}
    label {}
    input[type="text"], input[type="password"] {}
    input[type="submit"] {}
    input[type="submit"]:hover {}
    .result {}
    .loading-indicator {}
    .toggle-password {}
}

    </style>
</head>
<body>
<header>
    <div class="new-container">
        <p>Wireless</p>
    </div>
    <div class="header-top">
        <h1>p</h1>
    </div>
    <div class="header-bottom">
        <h1>p</h1>
    </div>
</header>
    <div class="container">
        <h1>Konfigurasi Hotspot</h1>
        <p class="note">Isi data untuk mengatur hotspot Wi-Fi Anda</p>
        <form id="hotspotForm" action="process_hotspot.php" method="POST">
            <label for="ssid">SSID:</label>
            <input type="text" id="ssid" name="ssid" placeholder="1-15 karakter" required minlength="1" maxlength="15" pattern="^[^\s]*$" title="Tidak boleh mengandung spasi.">
            
            <label for="password">Password:</label>
            <div class="password-container">
                <input type="password" id="password" name="password" placeholder="8-15 karakter" required minlength="8" maxlength="15" pattern="^[^\s]*$" title="Tidak boleh mengandung spasi." autocomplete="off">
                <span id="togglePassword" class="toggle-password"><i class="fas fa-eye-slash"></i></span>
            </div>
            
            <input type="submit" value="Atur Hotspot">
        </form>
        <div class="result" id="resultMessage" aria-live="polite"></div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');
        const form = document.getElementById('hotspotForm');
        const resultMessage = document.getElementById('resultMessage');

        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
        });

        form.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission
            
            const password = passwordInput.value.trim();
            const ssid = document.getElementById('ssid').value.trim();

            // Validate input
            if (password.includes(' ') || ssid.includes(' ')) {
                resultMessage.textContent = "SSID dan Password tidak boleh mengandung spasi.";
                resultMessage.style.display = 'block';
                return;
            }

            // Show loading indicator
            const loadingIndicator = document.createElement('div');
            loadingIndicator.classList.add('loading-indicator');
            loadingIndicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            document.body.appendChild(loadingIndicator);

            // Simulate saving data (offline)
            localStorage.setItem('ssid', ssid);
            localStorage.setItem('password', password);

            // Provide feedback to the user
            resultMessage.textContent = "Hotspot berhasil diatur dengan SSID: " + ssid;
            resultMessage.style.display = 'block';

            // Optionally, you can still send the data to the server
            const formData = new FormData(form);
            fetch('process_hotspot.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                document.querySelector('.loading-indicator').remove(); // Hide loader
                resultMessage.textContent += "\n" + data; // Show server response
            })
            .catch(error => {
                document.querySelector('.loading-indicator').remove(); // Hide loader
                resultMessage.textContent += "\nTerjadi kesalahan: " + error.message;
            });

            // Clear the form
            form.reset();
        });

        // Autofill form with local storage data if available
        window.onload = function() {
            const savedSsid = localStorage.getItem('ssid');
            const savedPassword = localStorage.getItem('password');
            if (savedSsid) {
                document.getElementById('ssid').value = savedSsid;
            }
            if (savedPassword) {
                passwordInput.value = savedPassword;
            }
        };
    </script>
</body>
</html>
