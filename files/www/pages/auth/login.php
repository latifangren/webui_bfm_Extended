<?php
/**
 * Login page view.
 * Pure template — auth logic handled by AuthService.
 */
$error = $error ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Login - BOX UI Extended</title>
    <link rel="icon" href="../webui/assets/luci.ico" type="image/x-icon">
    <link rel="stylesheet" href="../webui/css/styles.css">
    <link rel="stylesheet" href="../auth/css/materialize.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Rajdhani', sans-serif;
            background: #000;
            color: #F1F1F1;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }
        .login-card {
            background: #111;
            border: 1px solid #333;
            border-radius: 16px;
            padding: 40px 30px;
            text-align: center;
        }
        .login-card h1 {
            font-family: 'Orbitron', monospace;
            font-size: 24px;
            color: #FECA0A;
            margin-bottom: 8px;
        }
        .login-card p {
            color: #888;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .input-field {
            margin-bottom: 20px;
            text-align: left;
        }
        .input-field label {
            display: block;
            font-size: 13px;
            color: #aaa;
            margin-bottom: 6px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .input-field input {
            width: 100%;
            padding: 12px 14px;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 10px;
            color: #F1F1F1;
            font-size: 15px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s;
        }
        .input-field input:focus {
            border-color: #FECA0A;
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: #FECA0A;
            color: #000;
            border: none;
            border-radius: 10px;
            font-family: 'Orbitron', monospace;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.15s;
        }
        .btn-login:hover { background: #ffd633; }
        .btn-login:active { transform: scale(0.97); }
        .error-msg {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff4444;
            color: #ff4444;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        .logo-login {
            font-family: 'Orbitron', monospace;
            font-size: 32px;
            color: #FECA0A;
            margin-bottom: 5px;
        }
        .logo-login small {
            font-size: 12px;
            color: #666;
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-login">
                BOX UI
                <small>EXTENDED v2.1.1</small>
            </div>
            <p>Masuk untuk mengelola perangkat</p>

            <?php if ($error): ?>
                <div class="error-msg"><?= boxui_e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="input-field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                <div class="input-field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-login">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
