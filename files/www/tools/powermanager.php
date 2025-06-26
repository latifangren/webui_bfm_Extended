<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Power Manager</title>
    <style>
        :root {
            --primary-color: #FFD600; /* Kuning terang */
            --danger-color: #f44336;
            --warning-color: #ff9800;
            --info-color: #FFD600;
            --text-color: #fff;
            --bg-color: #111111;
            --card-bg: #181818;
            --border-color: #FFD600;
            --radius: 16px;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --text-color: #fff;
                --bg-color: #111111;
                --card-bg: #181818;
                --border-color: #FFD600;
            }
        }
  @font-face {
    font-family: 'LemonMilkProRegular';
    src: url('../webui/fonts/LemonMilkProRegular.otf') format('opentype');
  }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: LemonMilkProRegular, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #111 60%, #FFD600 100%);
            color: var(--text-color);
            min-height: 100vh;
            padding: 30px 20px 0;
            transition: background 0.5s;
        }

        .container {
            max-width: 500px;
            margin: 0 auto;
        }

        .card {
            background: linear-gradient(120deg, #181818 80%, #FFD600 200%);
            border-radius: var(--radius);
            padding: 36px 32px 32px 32px;
            box-shadow: 0 8px 32px rgba(255, 214, 0, 0.12), 0 1.5px 8px rgba(0,0,0,0.25);
            margin-top: 32px;
            border: 2px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: -40px; left: -40px;
            width: 120px; height: 120px;
            background: radial-gradient(circle, #FFD60055 60%, transparent 100%);
            z-index: 0;
        }

        .title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 18px;
            text-align: center;
            color: var(--primary-color);
            letter-spacing: 1px;
            text-shadow: 0 2px 8px #FFD60033;
            position: relative;
            z-index: 1;
        }

        .description {
            font-size: 16px;
            line-height: 1.7;
            margin-bottom: 22px;
            text-align: center;
            color: var(--text-color);
            opacity: 0.92;
            z-index: 1;
            position: relative;
        }

        .divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--border-color) 50%, transparent);
            margin: 28px 0 22px 0;
            opacity: 0.7;
            border-radius: 2px;
        }

        .status {
            background: rgba(255, 214, 0, 0.08);
            padding: 18px;
            border-radius: var(--radius);
            margin: 22px 0;
            text-align: center;
            font-size: 15px;
            line-height: 1.7;
            color: #FFD600;
            border: 1.5px solid #FFD60044;
            font-weight: 500;
        }

        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            justify-content: center;
            margin-top: 24px;
            z-index: 1;
            position: relative;
        }

        .btn {
            padding: 14px 28px;
            border-radius: var(--radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s, box-shadow 0.3s;
            min-width: 170px;
            box-shadow: 0 2px 8px #FFD60022;
            outline: none;
        }

        .btn-primary {
            background: linear-gradient(90deg, #FFD600 80%, #fff700 100%);
            color: #181818;
        }

        .btn-primary:hover {
            background: #fff700;
            color: #111;
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 4px 16px #FFD60055;
        }

        .btn-danger {
            background: linear-gradient(90deg, #f44336 80%, #FFD600 100%);
            color: #fff;
        }

        .btn-danger:hover {
            background: #FFD600;
            color: #f44336;
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 4px 16px #FFD60055;
        }

        .btn-warning {
            background: linear-gradient(90deg, #ff9800 80%, #FFD600 100%);
            color: #fff;
        }

        .btn-warning:hover {
            background: #FFD600;
            color: #ff9800;
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 4px 16px #FFD60055;
        }

        .btn-info {
            background: linear-gradient(90deg, #FFD600 80%, #2196f3 100%);
            color: #181818;
        }

        .btn-info:hover {
            background: #2196f3;
            color: #FFD600;
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 4px 16px #FFD60055;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 20%;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
            justify-content: center;
            align-items: flex-start;
            background: rgba(0,0,0,0.7);
        }

        .modal-content {
            background: var(--card-bg);
            border-radius: var(--radius);
            width: 90%;
            max-width: 400px;
            padding: 28px 22px 22px 22px;
            text-align: center;
            box-shadow: 0 8px 32px #FFD60033;
            margin-top: 20px;
            border: 2px solid var(--border-color);
            position: relative;
        }

        .modal-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 13px;
            color: var(--primary-color);
            text-shadow: 0 2px 8px #FFD60033;
        }

        .modal-message {
            font-size: 16px;
            line-height: 1.7;
            margin-bottom: 25px;
            color: var(--text-color);
        }

        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .modal-actions .btn {
            min-width: 120px;
        }

        @media (max-width: 600px) {
            body {
                padding: 20px 8px 0;
            }
            .btn-group {
                flex-direction: column;
            }
            .btn {
                width: 100%;
            }
            .modal-actions {
                flex-direction: column;
            }
            .modal-actions button {
                width: 100%;
            }
            .modal {
                top: 10%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1 class="title">Power Management</h1>
            
            <p class="description">
                Manage your device's power state. Please ensure all important work is saved before performing any power actions.
            </p>
            
            <div class="status">
                Current system status: <strong>Running</strong><br>
                Last boot: <strong><?php echo date('Y-m-d H:i:s'); ?></strong>
            </div>
            
            <div class="divider"></div>
            
            <div class="btn-group">
                <button class="btn btn-primary" onclick="confirmAction('reboot')">
                    <span style="vertical-align:middle; margin-right:8px;">
                        <!-- Ikon restart (refresh) -->
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;"><path d="M10 3v2.5M10 14.5v2M16.07 7.93l-1.77 1.77M5.7 14.3l-1.77 1.77M17 10h-2.5M7.93 3.93L6.16 5.7M14.3 14.3l1.77 1.77M3 10h2.5" stroke="#181818" stroke-width="2" stroke-linecap="round"/></svg>
                    </span>
                    Restart System
                </button>
                <button class="btn btn-danger" onclick="confirmAction('turn_off')">
                    <span style="vertical-align:middle; margin-right:8px;">
                        <!-- Ikon power off -->
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;"><path d="M10 2v8" stroke="#fff" stroke-width="2" stroke-linecap="round"/><circle cx="10" cy="12" r="6" stroke="#fff" stroke-width="2"/></svg>
                    </span>
                    Shut Down
                </button>
                <button class="btn btn-warning" onclick="confirmAction('recovery')">
                    <span style="vertical-align:middle; margin-right:8px;">
                        <!-- Ikon recovery (tool/wrench) -->
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;"><path d="M17.7 13.29a6 6 0 01-7.99-7.99l2.12 2.12a2 2 0 002.83 2.83l2.12 2.12z" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 18l4-4" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>
                    </span>
                    Recovery Mode
                </button>
                <button class="btn btn-info" onclick="confirmAction('fastboot')">
                    <span style="vertical-align:middle; margin-right:8px;">
                        <!-- Ikon fastboot (petir/flash) -->
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;"><path d="M7 2l6 0-4 7h4l-6 9 2-8H7z" stroke="#181818" stroke-width="2" stroke-linejoin="round"/></svg>
                    </span>
                    Fastboot Mode
                </button>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">Confirm Power Action</h3>
            <p class="modal-message" id="modalMessage">Are you sure you want to perform this action?</p>
            <div class="modal-actions">
                <button class="btn" onclick="executeAction(false)" style="background-color: var(--border-color);">
                    Cancel
                </button>
                <button id="confirmBtn" class="btn btn-danger" onclick="executeAction(true)">
                    Confirm
                </button>
            </div>
        </div>
    </div>

    <script>
        let action = '';

        function confirmAction(selectedAction) {
            action = selectedAction;
            const modal = document.getElementById('confirmationModal');
            const confirmBtn = document.getElementById('confirmBtn');
            const modalMessage = document.getElementById('modalMessage');
            
            if (selectedAction === 'reboot') {
                confirmBtn.className = 'btn btn-primary';
                confirmBtn.textContent = 'Restart';
                modalMessage.textContent = 'Are you sure you want to restart the system? All services will be temporarily unavailable during restart.';
            } else if (selectedAction === 'turn_off') {
                confirmBtn.className = 'btn btn-danger';
                confirmBtn.textContent = 'Shut Down';
                modalMessage.textContent = 'Are you sure you want to shut down the system? You will need to manually power the device back on.';
            } else if (selectedAction === 'recovery') {
                confirmBtn.className = 'btn btn-warning';
                confirmBtn.textContent = 'Reboot to Recovery';
                modalMessage.textContent = 'Are you sure you want to reboot into recovery mode? The system will enter a special maintenance mode.';
            } else if (selectedAction === 'fastboot') {
                confirmBtn.className = 'btn btn-info';
                confirmBtn.textContent = 'Reboot to Fastboot';
                modalMessage.textContent = 'Are you sure you want to reboot into fastboot mode? This is used for low-level device operations.';
            }
            
            modal.style.display = 'flex';
        }

        function executeAction(isConfirmed) {
            const modal = document.getElementById('confirmationModal');
            modal.style.display = 'none';
            
            if (isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'action';
                input.value = action;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            if ($action === 'reboot') {
                shell_exec('su -c reboot');
                echo "<script>alert('System is restarting...');</script>";
            } elseif ($action === 'turn_off') {
                shell_exec('su -c reboot -p');
                echo "<script>alert('System is shutting down...');</script>";
            } elseif ($action === 'recovery') {
                shell_exec('su -c reboot recovery');
                echo "<script>alert('Rebooting into recovery mode...');</script>";
            } elseif ($action === 'fastboot') {
                // Try multiple fastboot commands as different devices may respond differently
                shell_exec('su -c reboot bootloader');
                shell_exec('su -c reboot fastboot');
                echo "<script>alert('Rebooting into fastboot mode...');</script>";
            }
        }
    }
    ?>

</body>
</html>