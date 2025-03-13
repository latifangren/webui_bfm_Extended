<?php
// Load the current theme configuration from theme.json
$config = json_decode(file_get_contents('theme.json'), true);
$successMessage = '';

// Check if the form was submitted and update the configuration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the selected design option from the form
    $selectedTheme = $_POST['design'] ?? 'default'; // Default to 'default' if nothing is selected
    
    // Update the configuration
    $config['path'] = $selectedTheme;
    
    // Save the updated configuration back to theme.json
    file_put_contents('theme.json', json_encode($config, JSON_PRETTY_PRINT));
    
    // Set the success message
    $successMessage = "Theme saved successfully! Selected theme: $selectedTheme";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Properties</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: transparent;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 0;
            height: 100%;
            flex-direction: column;
        }
        .card-a {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 80%;
            text-align: left;
            margin-top: 90px;
        }
        h2 {
            color: #343a40;
            font-size: 20px;
            margin-bottom: 30px;
            font-weight: 700;
        }
        p {
            color: #000;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
        }
        label {
            font-size: 14px;
            font-weight: 600;
            color: #555;
            display: block;
            margin-bottom: 10px;
        }
        select {
            width: 50%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f8f9fa;
            color: #333;
            font-size: 14px;
            outline: none;
        }
        select:focus {
            border: 1px solid #6379f4;
        }
        .btn {
            display: block;
            width: 40%;
            padding: 12px;
            background-color: #6379f4;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            margin-bottom: 5px;
            margin-left: auto;
            margin-right: auto;
        }
        .btn:hover {
            background-color: #5064c9;
        }
        .success-message {
            color: green;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            position: absolute;
            top: 60px; /* Adjust this to position it where you want */
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            z-index: 2; /* Make sure it appears above other content */
        }
        header {
            padding: 0;
            text-align: center;
            position: relative;
            width: 100%;
        }
        .new-container {
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            height: 50px;
            padding: 10px;
            box-sizing: border-box;
            background-color: #ffffff;
            color: #000;
            text-align: center;
            z-index: 1;
            border-radius: 5px;
        }
        .new-container p {
            text-align: left;
            font-size: 1.1em;
            color: #555;
            margin-top: 3px;
            margin-left: 10px;
            font-weight: bold;
        }

/* Dark Mode Styles */
@media (prefers-color-scheme: dark) {
    body {
        background-color: transparent;
        color: transparent;
    }
    .card-a {
        background-color: #2a2a2a;
        color: #e0e0e0;
        box-shadow: 4px 4px 6px rgba(0, 0, 0, 0.3);
    }
    h2 {
        color: #f1f1f1;
    }
    p {
        color: #f1f1f1;
    }
    label {
        color: #ddd;
    }
    select {
        background-color: #444;
        color: #ccc;
        border: 1px solid #666;
    }
    select:focus {
        border: 1px solid #474f72;
    }
    .btn {
        background-color: #474f72;
    }
    .btn:hover {
        background-color: #2e344e;
    }
    .success-message {
        color: green;
    }
    .new-container, .new-container p {
        background-color: #2a2a2a;
        color: #e0e0e0;
    }
}
    </style>
</head>
<body>
    <?php if ($successMessage): ?>
        <div class="success-message"><?php echo $successMessage; ?></div>
    <?php endif; ?>
    <header>
        <div class="new-container">
            <p>Theme Selection</p>
        </div>
    </header>
    <div class="card-a">
        <h2>System Properties</h2>
        <p>Here you can configure the theme as you wish</p>
        <form method="POST">
            <label for="design">Design</label>
            <select name="design" id="design">
                <option value="default" <?php echo ($config['path'] === 'default') ? 'selected' : ''; ?>>BOX UI</option>
                <option value="argon" <?php echo ($config['path'] === 'argon') ? 'selected' : ''; ?>>Argon</option>
            </select>
            <button type="submit" class="btn">SAVE</button>
        </form>
    </div>
</body>
</html>
