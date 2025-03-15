<?php
session_start();

// Load credentials
$credentials = include 'credentials.php';
$stored_username = $credentials['username'];
$stored_hashed_password = $credentials['hashed_password'];

$config_file = '/data/adb/php7/files/www/auth/config.json';

if (!file_exists($config_file)) {
    die('Error: Configuration file not found.');
}

$config = json_decode(file_get_contents($config_file), true);

// Define the LOGIN_ENABLED constant based on the JSON file
define('LOGIN_ENABLED', $config['LOGIN_ENABLED']);

// Check if login is disabled
$login_disabled = !LOGIN_ENABLED;

// If login is disabled, set a session flag or a message variable
if ($login_disabled) {
    $_SESSION['login_disabled'] = true;
    // Langsung alihkan ke halaman utama jika login dinonaktifkan
    header("Location: /");
    exit;
}

// Check if the user is already logged in and redirect accordingly
if (isset($_SESSION['user_id'])) {
    $redirect_to = isset($_SESSION['redirect_to']) ? $_SESSION['redirect_to'] : '/';
    unset($_SESSION['redirect_to']);
    header("Location: $redirect_to");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate credentials
    if ($username === $stored_username && password_verify($password, $stored_hashed_password)) {
        $_SESSION['user_id'] = session_id();
        $_SESSION['username'] = $username;
        $redirect_to = isset($_SESSION['redirect_to']) ? $_SESSION['redirect_to'] : '/';
        unset($_SESSION['redirect_to']);
        header("Location: $redirect_to");
        exit;
    } else {
        $error = 'Username atau password tidak valid.';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Login</title>
    <link rel="icon" href="../webui/assets/luci.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons&display=swap" rel="stylesheet">
    <!-- Font Awesome untuk logo social media -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --neon-yellow: #FECA0A;
            --dark-bg: #000000;
            --light-text: #F1F1F1;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
            background-color: var(--dark-bg);
            color: var(--light-text);
            background-image: url('./assets/background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            overflow-x: hidden;
            font-family: Arial, sans-serif;
            position: relative;
        }
        
        /* Overlay untuk background */
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6); /* Overlay gelap */
            pointer-events: none;
            z-index: 0;
        }
        
        .header-dashboard {
            width: 100%;
            padding: 15px 20px;
            background-color: rgba(0, 0, 0, 0.8);
            border-bottom: 2px solid var(--neon-yellow);
            box-shadow: 0 0 20px rgba(254, 202, 10, 0.3);
            text-align: center;
            position: relative;
            margin-bottom: 20px;
            z-index: 2; /* Pastikan di atas overlay */
        }
        
        .header-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 28px;
            font-weight: bold;
            color: var(--neon-yellow);
            text-shadow: 
                0 0 10px rgba(254, 202, 10, 0.7),
                0 0 20px rgba(254, 202, 10, 0.4);
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        
        .header-dashboard::before,
        .header-dashboard::after {
            content: "";
            position: absolute;
            height: 25px;
            width: 25px;
            border-color: var(--neon-yellow);
        }
        
        .header-dashboard::before {
            left: 5px;
            top: 5px;
            border-left: 2px solid;
            border-top: 2px solid;
        }
        
        .header-dashboard::after {
            right: 5px;
            top: 5px;
            border-right: 2px solid;
            border-top: 2px solid;
        }
        
        main {
            flex: 1 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
            padding: 20px;
        }
        
        /* Menghapus grid yang mungkin mengganggu tampilan background */
        main::before {
            display: none;
        }
        
        .login-container {
            width: 90%;
            max-width: 400px;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 
                0 0 15px rgba(254, 202, 10, 0.5),
                0 0 30px rgba(254, 202, 10, 0.2),
                inset 0 0 10px rgba(254, 202, 10, 0.1);
            background-color: rgba(0, 0, 0, 0.8); /* Transparan agar background terlihat sedikit */
            border: 1px solid var(--neon-yellow);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(5px); /* Efek blur di belakang container */
            animation: container-pulse 3s ease-in-out infinite;
        }
        
        @keyframes container-pulse {
            0%, 100% { box-shadow: 0 0 15px rgba(254, 202, 10, 0.5), 0 0 30px rgba(254, 202, 10, 0.2); }
            50% { box-shadow: 0 0 20px rgba(254, 202, 10, 0.6), 0 0 40px rgba(254, 202, 10, 0.3); }
        }
        
        .login-title {
            font-family: 'Orbitron', sans-serif;
            text-align: center;
            margin-bottom: 30px;
            color: var(--neon-yellow);
            text-shadow: 
                0 0 10px rgba(254, 202, 10, 0.7),
                0 0 20px rgba(254, 202, 10, 0.4);
            letter-spacing: 2px;
            position: relative;
            z-index: 1;
            font-size: 24px;
            font-weight: bold;
        }
        
        .login-title::before, .login-title::after {
            content: "//";
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: var(--neon-yellow);
            opacity: 0.6;
        }
        
        .login-title::before {
            left: 10px;
        }
        
        .login-title::after {
            right: 10px;
        }
        
        .btn-login {
            width: 100%;
            margin-top: 30px;
            background-color: var(--neon-yellow);
            color: var(--dark-bg);
            border-radius: 30px;
            font-family: 'Orbitron', sans-serif;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            padding: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-login:hover {
            background-color: var(--dark-bg);
            color: var(--neon-yellow);
            box-shadow: 
                0 0 15px rgba(254, 202, 10, 0.8),
                0 0 30px rgba(254, 202, 10, 0.4);
            border: 2px solid var(--neon-yellow);
        }
        
        .btn-login::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to bottom right,
                rgba(255, 255, 255, 0.3),
                rgba(255, 255, 255, 0),
                rgba(255, 255, 255, 0),
                rgba(255, 255, 255, 0.3)
            );
            transform: rotate(45deg);
            z-index: -1;
            animation: shine 2s forwards infinite;
            opacity: 0;
        }
        
        .btn-login:hover::before {
            opacity: 1;
        }
        
        @keyframes shine {
            0% { left: -50%; opacity: 0; }
            10% { opacity: 1; }
            100% { left: 150%; opacity: 0; }
        }
        
        .error-message {
            color: var(--neon-yellow);
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            border-left: 3px solid var(--neon-yellow);
            background-color: rgba(254, 202, 10, 0.1);
            text-shadow: 0 0 8px rgba(254, 202, 10, 0.7);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .input-field {
            margin-bottom: 25px;
            position: relative;
        }
        
        .input-field i {
            position: absolute;
            left: 0;
            top: 15px;
            color: var(--neon-yellow);
            font-size: 24px;
            text-shadow: 0 0 5px rgba(254, 202, 10, 0.5);
        }
        
        .input-field input {
            width: 100%;
            padding: 10px 10px 10px 35px;
            background: transparent;
            border: none;
            border-bottom: 2px solid var(--neon-yellow);
            color: var(--light-text);
            font-family: 'Orbitron', sans-serif;
            letter-spacing: 1px;
            outline: none;
            box-shadow: 0 1px 0 0 rgba(254, 202, 10, 0.3);
            transition: all 0.3s ease;
        }
        
        .input-field.password-field input {
            padding-right: 40px;
        }
        
        .input-field input:focus {
            border-bottom: 2px solid var(--neon-yellow);
            box-shadow: 
                0 1px 0 0 var(--neon-yellow),
                0 4px 10px rgba(254, 202, 10, 0.1);
        }
        
        .input-field label {
            position: absolute;
            left: 35px;
            top: 15px;
            color: var(--light-text);
            font-weight: 300;
            pointer-events: none;
            transition: 0.2s ease all;
        }
        
        .input-field input:focus ~ label,
        .input-field input:valid ~ label {
            top: -10px;
            font-size: 12px;
            color: var(--neon-yellow);
            text-shadow: 0 0 5px rgba(254, 202, 10, 0.5);
        }
        
        .toggle-password {
            position: absolute;
            right: 5px;
            top: 12px;
            color: var(--neon-yellow);
            cursor: pointer;
            font-size: 22px;
            transition: all 0.3s ease;
            text-shadow: 0 0 5px rgba(254, 202, 10, 0.5);
            z-index: 10;
        }
        
        .toggle-password:hover {
            color: var(--light-text);
            text-shadow: 0 0 8px var(--neon-yellow);
        }
        
        .login-container::before {
            content: "";
            position: absolute;
            width: 30px;
            height: 30px;
            border-top: 2px solid var(--neon-yellow);
            border-left: 2px solid var(--neon-yellow);
            top: 5px;
            left: 5px;
        }
        
        .login-container::after {
            content: "";
            position: absolute;
            width: 30px;
            height: 30px;
            border-bottom: 2px solid var(--neon-yellow);
            border-right: 2px solid var(--neon-yellow);
            bottom: 5px;
            right: 5px;
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
            -webkit-font-feature-settings: 'liga';
            -webkit-font-smoothing: antialiased;
        }
        
        .icon-right {
            margin-left: 8px;
        }
        
        /* Social Media Buttons */
        .social-links {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 15px;
        }
        
        .social-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: transparent;
            border: 2px solid var(--neon-yellow);
            color: var(--neon-yellow);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-decoration: none;
        }
        
        .social-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--neon-yellow);
            transform: translateY(100%);
            transition: all 0.3s ease;
            z-index: -1;
        }
        
        .social-btn:hover {
            color: var(--dark-bg);
            text-shadow: none;
            box-shadow: 0 0 15px rgba(254, 202, 10, 0.6);
        }
        
        .social-btn:hover::before {
            transform: translateY(0);
        }
        
        .social-btn i {
            font-size: 20px;
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease;
        }
        
        .social-btn:hover i {
            transform: scale(1.2);
        }
        
        .social-links-title {
            text-align: center;
            font-family: 'Orbitron', sans-serif;
            color: var(--light-text);
            margin-bottom: 15px;
            font-size: 14px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        
        /* Divider between login form and social links */
        .divider {
            height: 1px;
            background: linear-gradient(
                to right,
                transparent,
                var(--neon-yellow),
                transparent
            );
            margin: 30px 0 20px;
            position: relative;
        }
        
        .divider::after {
            content: "//";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(0, 0, 0, 0.8);
            padding: 0 10px;
            color: var(--neon-yellow);
            font-family: 'Orbitron', sans-serif;
        }
        
        /* Credit Banner */
        .credit-banner {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: rgba(0, 0, 0, 0.8);
            padding: 10px 15px;
            border-radius: 20px;
            border: 1px solid var(--neon-yellow);
            display: flex;
            align-items: center;
            box-shadow: 0 0 15px rgba(254, 202, 10, 0.4);
            z-index: 100; /* Pastikan di atas overlay */
            transform: translateZ(0);
            animation: banner-pulse 3s infinite;
        }
        
        @keyframes banner-pulse {
            0%, 100% { box-shadow: 0 0 15px rgba(254, 202, 10, 0.4); }
            50% { box-shadow: 0 0 20px rgba(254, 202, 10, 0.7); }
        }
        
        .credit-banner::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 20px;
            background: linear-gradient(45deg, 
                rgba(254, 202, 10, 0.2) 0%, 
                rgba(254, 202, 10, 0) 25%, 
                rgba(254, 202, 10, 0) 75%, 
                rgba(254, 202, 10, 0.2) 100%);
            z-index: -1;
            animation: shine 3s linear infinite;
        }
        
        @keyframes shine {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        .credit-logo {
            margin-right: 10px;
            font-size: 20px;
            color: var(--neon-yellow);
            animation: rotate 4s linear infinite;
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .credit-text {
            font-family: 'Orbitron', sans-serif;
            font-size: 14px;
            font-weight: bold;
            display: flex;
            flex-direction: column;
        }
        
        .credit-by {
            font-size: 10px;
            color: var(--light-text);
            opacity: 0.8;
        }
        
        .credit-name {
            color: var(--neon-yellow);
            text-shadow: 0 0 5px rgba(254, 202, 10, 0.5);
        }
        
        @media (max-width: 480px) {
            .credit-banner {
                bottom: 10px;
                right: 10px;
                padding: 8px 12px;
            }
            
            .credit-logo {
                font-size: 16px;
            }
            
            .credit-text {
                font-size: 12px;
            }
            
            .credit-by {
                font-size: 8px;
            }
        }
    </style>
</head>
<body>
    <header class="header-dashboard">
        <h1 class="header-title">BFM WEBUI EXTENDED</h1>
    </header>
    
    <main>
        <div class="login-container">
            <h4 class="login-title">SYSTEM ACCESS</h4>
            
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <p><?php echo $error; ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="input-field">
                    <i class="material-icons">person</i>
                    <input id="username" name="username" type="text" required>
                    <label for="username">Username</label>
                </div>
                
                <div class="input-field password-field">
                    <i class="material-icons">lock</i>
                    <input id="password" name="password" type="password" required>
                    <label for="password">Password</label>
                    <i class="material-icons toggle-password" id="togglePassword">visibility</i>
                </div>
                
                <button class="btn-login" type="submit">
                    AUTHENTICATE <i class="material-icons icon-right">send</i>
                </button>
            </form>
            
            <div class="divider"></div>
            
            <div class="social-links-title">CONNECT WITH US</div>
            <div class="social-links">
                <a href="https://github.com/latifangren" target="_blank" class="social-btn">
                    <i class="fab fa-github"></i>
                </a>
                <a href="https://www.facebook.com/latifan.latifan.latifan.latif" target="_blank" class="social-btn">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://www.youtube.com/@Bangoor_72" target="_blank" class="social-btn">
                    <i class="fab fa-youtube"></i>
                </a>
                <a href="https://t.me/latifan_id" target="_blank" class="social-btn">
                    <i class="fab fa-telegram-plane"></i>
                </a>
            </div>
        </div>
    </main>

    <!-- Credit Banner -->
    <div class="credit-banner">
        <i class="fas fa-code credit-logo"></i>
        <div class="credit-text">
            <span class="credit-by">DESIGNED BY</span>
            <span class="credit-name">LATIFAN_ID</span>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');
            
            inputs.forEach(input => {
                if (input.value.trim() !== '') {
                    input.nextElementSibling.classList.add('active');
                }
                
                input.addEventListener('input', function() {
                    if (this.value.trim() !== '') {
                        this.nextElementSibling.classList.add('active');
                    } else {
                        this.nextElementSibling.classList.remove('active');
                    }
                });
            });
            
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            togglePassword.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    this.textContent = 'visibility_off';
                } else {
                    passwordInput.type = 'password';
                    this.textContent = 'visibility';
                }
                
                this.style.textShadow = '0 0 15px #FECA0A';
                setTimeout(() => {
                    this.style.textShadow = '0 0 5px rgba(254, 202, 10, 0.5)';
                }, 300);
            });
        });
    </script>
</body>
</html>
    