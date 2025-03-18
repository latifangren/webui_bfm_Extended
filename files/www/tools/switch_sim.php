<?php
// Fungsi untuk menjalankan perintah shell
function execCommand($command) {
    $output = null;
    $result_code = null;
    exec($command, $output, $result_code);
    return ['output' => $output, 'status' => $result_code];
}

// Fungsi untuk mendapatkan SIM yang aktif
function getCurrentSim() {
    $result = execCommand('settings get global multi_sim_data_call');
    if ($result['status'] === 0 && !empty($result['output'])) {
        return (int)$result['output'][0];
    }
    return 1; // default ke SIM 1 jika gagal membaca
}

// Proses switch SIM jika ada request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_sim = getCurrentSim();
    $new_sim = ($current_sim === 1) ? 2 : 1;
    
    $commands = [
        "settings put global multi_sim_data_call $new_sim",
        "svc data disable",
        "svc data enable"
    ];
    
    $success = true;
    foreach ($commands as $cmd) {
        $result = execCommand($cmd);
        if ($result['status'] !== 0) {
            $success = false;
            break;
        }
    }
    
    $message = $success ? "Berhasil switch ke SIM $new_sim" : "Gagal melakukan switch SIM";
}

$active_sim = getCurrentSim();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Switch SIM Data</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            max-width: 400px;
            margin: 0 auto;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .status {
            font-size: 18px;
            margin-bottom: 20px;
            text-align: center;
        }
        .button {
            background: #2196F3;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            width: 100%;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .button:hover {
            background: #1976D2;
        }
        .message {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }
        .success {
            background: #E8F5E9;
            color: #2E7D32;
        }
        .error {
            background: #FFEBEE;
            color: #C62828;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="status">
            SIM Data Aktif: SIM <?php echo $active_sim; ?>
        </div>
        
        <form method="POST">
            <button type="submit" class="button">
                Switch ke SIM <?php echo ($active_sim === 1) ? '2' : '1'; ?>
            </button>
        </form>

        <?php if (isset($message)): ?>
        <div class="message <?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html> 