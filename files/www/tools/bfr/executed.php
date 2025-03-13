<?php
$start = "/data/adb/box/scripts/box.service start && /data/adb/box/scripts/box.iptables enable";
$stop = "/data/adb/box/scripts/box.iptables disable && /data/adb/box/scripts/box.service stop";
$restart = "/data/adb/box/scripts/box.service restart";
$clashlogs = "/data/adb/box/run/runs.log";
$p = $_SERVER['HTTP_HOST'];
$x = explode(':', $p);
$host = $x[0];

// Shell execution functions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action == 'start') {
        shell_exec($start);
    } elseif ($action == 'stop') {
        shell_exec($stop);
    } elseif ($action == 'restart') {
        shell_exec($restart);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.iconify.design/2/2.1.0/iconify.min.js"></script>
    <title>Box For Root</title>
    <style>
        :root {
            --primary-color: #2196F3;
            --success-color: #4CAF50;
            --bg-light: #f5f7fa;
            --text-light: #2c3e50;
            --card-light: #ffffff;
            --border-light: #edf2f7;
            --header-light: #f8fafc;
            
            --bg-dark: #1a1b1e;
            --text-dark: #e4e7eb;
            --card-dark: #25262b;
            --border-dark: #2c2e33;
            --header-dark: #1f2023;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding: 0px;
            display: flex;
            flex-direction: column;
            background-color: transparent;
            color: var(--text-light);
            transition: all 0.3s ease;
            line-height: 0;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 0px 0px;
        }

        .container {
            padding: 10px;
            width: 100%;
            margin-bottom: 0px;
            margin-top: -35px;
            height: calc(100vh - 50px);
            display: flex;
            flex-direction: column;
            border-radius: 12px;
        }

        .logs-card {
            background-color: var(--card-light);
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .logs-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-light);
            font-weight: 500;
            font-size: 0.8rem;
            color: var(--text-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--header-light);
        }

        .logs-container {
            flex: 1;
            overflow-y: auto;
            padding: 5px 10px;
            padding: 10px;
            width: 100%;
            margin-bottom: 0px;
            margin-top: 0px;
            height: calc(100vh - 50px);
            display: flex;
            flex-direction: column;
            border-radius: 12px;
        }

        .log-entry {
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 5px;
            background-color: var(--bg-light);
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 10px;
            line-height: 1.4;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .logs-container::-webkit-scrollbar {
            width: 6px;
        }

        .logs-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .logs-container::-webkit-scrollbar-thumb {
            background-color: var(--border-light);
            border-radius: 3px;
        }

        .button-container {
            display: flex;
            width: 100%;
            margin-bottom: 20px;
            background: #fff;
            padding-top: 60px;  /* Menambah ruang di atas */
            padding-bottom: 20px;
            border-radius: 12px;
            justify-content: center;
            align-items: center;
            z-index: 0;
        }

        .button-container form {
            display: flex;
            width: 90%;
            margin: 0;
        }

        .button-container button {
            flex: 1; /* Ensures the buttons take equal space */
            padding: 8px;
            color: white;
            border: none;
            font-size: 0.9rem;
            font-weight: 520;
            cursor: pointer;
            transition: background-color 0.3s;
            box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.2);
        }

        .button-container button:first-child {
            background-color: #4CAF50;  /* Green for start */
            border-top-left-radius: 6px;
            border-bottom-left-radius: 6px;
        }

        .button-container button:nth-child(2) {
            background-color: #FF9800;
        }

        .button-container button:last-child {
            background-color: #F44336;
            border-top-right-radius: 6px;
            border-bottom-right-radius: 6px;
        }

        .button-container button:hover {
            opacity: 0.8; /* Slightly change opacity on hover */
        }

        /* Dashboard Button - Outline Style */
        .dashboard-button {
            display: block;
            width: 90%;
            padding: 6px;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            background-color: transparent;
            font-size: 0.9rem;
            cursor: pointer;
            border-radius: 6px;
            text-align: center;
            margin: 0px auto;
            top: 50px;
            font-weight: 600;
            transition: background-color 0.3s, color 0.3s;
            z-index: 1;
            position: relative;
            box-shadow: 2px 2px 2px rgba(0, 0, 255, 0.2);
        }

        .dashboard-button:hover {
            background-color: rgba(33, 150, 243, 0.3);
            color: white;
        }
        a {
        text-decoration: none;
        }
        
        .loading-container {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 500;
            padding: 10px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
        }

        .loading-container .iconify {
            margin-right: 10px;
    }
    
    @media (prefers-color-scheme: dark) {
    body {
        background-color: transparent;
        color: var(--text-dark);
    }
    
    .button-container {
        background: #2a2a2a;
    }
    
    .dashboard-button {
        box-shadow: 2px 2px 2px rgba(0, 0, 255, 0.6);
    }
    
    .button-container button {
        box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.6);
    }

    .logs-card {
        background-color: var(--card-dark);
    }

    .logs-header {
            border-color: var(--border-dark);
            color: var(--text-dark);
            background-color: var(--header-dark);
    }

    .log-entry {
            background-color: rgba(0, 0, 0, 0.2);
    }

    .logs-container::-webkit-scrollbar-thumb {
        background-color: var(--border-dark);
    }
}

    </style>
</head>
<body>
    <div class="container">
        <a href="http://<?php echo $host; ?>:9090/ui/#/overview" target="_blank">
            <button class="dashboard-button">Dashboard</button>
        </a>
        <div class="button-container">
            <form method="POST">
                <button type="submit" name="action" value="start">Start</button>
                <button type="submit" name="action" value="restart">Restart</button>
                <button type="submit" name="action" value="stop">Stop</button>
            </form>
        </div>

        <!-- Logs Card -->
        <div class="logs-card">
            <div class="logs-header">
                <span>Log Entries</span>
            </div>
            <div class="logs-container">
                <?php
                $file = fopen("$clashlogs", "r");
                while (!feof($file)) {
                    $log = str_replace('"', '', fgets($file));
                    echo '<div class="log-entry">' . htmlspecialchars($log) . '</div>';
                }
                fclose($file);
                ?>
            </div>
        </div>
    </div>
  <!-- Loading Spinner -->
  <div id="loading-container" class="loading-container" style="display:none;">
    <span class="iconify" data-icon="svg-spinners:bars-rotate-fade" style="font-size: 20px;"></span>
    <span>Loading...</span>
  </div>
 <script>
    document.querySelector('form').addEventListener('submit', function() {
        document.getElementById('loading-container').style.display = 'flex'; // Show spinner
    });
 </script>
</body>
</html>