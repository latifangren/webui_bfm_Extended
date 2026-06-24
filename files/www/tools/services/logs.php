<?php
$clashlogs = "/cache/magisk.log";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Magisk Logs</title>
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

/* General styles for light mode (default) */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    padding: 24px;
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

header {
    padding: 0;
    text-align: center;
    position: relative;
    width: 100%;
}

.header-top {
    background-color: transparent;
    padding: 0px;
}

.header-bottom {
    background-color: transparent;
    padding: 25px;
}

header h1 {
    margin: 0;
    font-size: 0.8em;
    color: transparent;
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
    background-color: #ffffff;
    color: #000;
    text-align: center;
    z-index: 2;
}

.new-container p {
    text-align: left;
    font-size: 1em;
    color: #555;
    margin-top: 14px;
    margin-left: 10px;
    font-weight: bold;
}

.b-container {
    padding: 19px;
    width: 100%;
    margin-bottom: 0px;
    margin-top: 0px;
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

/* Dark mode styles */
@media (prefers-color-scheme: dark) {
    body {
        background-color: transparent;
        color: var(--text-dark);
    }

    .new-container {
        background-color: #2a2a2a;
        color: #e0e0e0;
    }
    .new-container p {
    background-color: #2a2a2a; /* Dark background for containers */
    color: #e0e0e0;
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
<header>
    <div class="new-container">
        <p>Magisk Logs</p>
    </div>
    <div class="header-top">
        <h1>p</h1>
    </div>
    <div class="header-bottom">
        <h1>p</h1>
    </div>
</header>
<body>
    <div class="b-container">
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

</body>
</html>