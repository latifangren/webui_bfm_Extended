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
            background-color: #000;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            height: 80vh;
            flex-direction: column;
        }
        .card-a {
            background-color: #2a2a33; 
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 80%;
            text-align: left;
        }
        h2 {
            color: #fff;
            font-size: 20px;
            margin-bottom: 30px;
            font-weight: 700;
        }
        p {
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
        }
        label {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            display: block;
            margin-bottom: 10px;
        }
        select {
            width: 50%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: silver;
            color: #333;
            font-size: 14px;
            outline: none;
        }
        select:focus {
            border: 1px solid #20305a;
        }
        .btn {
            display: block;
            width: 40%;
            padding: 12px;
            background-color: #20305a;
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
            background-color: #151f3a;
        }
        .success-message {
            color: green;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            width: 100%;
            display: block;
            position: relative;
            top: -40px;
        }

    </style>
</head>
<body>

    <?php if ($successMessage): ?>
        <div class="success-message"><?php echo $successMessage; ?></div>
    <?php endif; ?>

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
