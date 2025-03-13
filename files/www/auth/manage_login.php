<?php
session_start();

require_once '/data/adb/php7/files/www/auth/auth_functions.php';

// If login is disabled, set the current page but do not redirect to login
if (isset($_SESSION['login_disabled']) && $_SESSION['login_disabled'] === true) {
    // Login is disabled, handle accordingly
    // You can show a message or just let the user stay on the page
    //echo "<p>Login is currently disabled.</p>";
} else {
    // Proceed to check if the user is logged in
    checkUserLogin();
}

// Load the current configuration
$config = json_decode(file_get_contents('config.json'), true);

// Handle form submission to update the configuration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config['LOGIN_ENABLED'] = isset($_POST['login_enabled']);
    
    // Save the updated configuration back to the JSON file
    file_put_contents('config.json', json_encode($config, JSON_PRETTY_PRINT));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Manage Login</title>
    <!-- Include Materialize CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
    <style>
/* Default light mode styles */
body {
  font-family: Arial, sans-serif;
  background-color: transparent;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin: 0;
}

header {
  padding: 0;
  text-align: center;
  position: relative;
  width: 100%;
}

.header-top {
  background-color: transparent;
  padding: 5px;
}

.header-bottom {
  background-color: transparent;
  padding: 5px;
}

header h1 {
  margin: 0;
  font-size: 0.8em;
  color: #transparent;
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
  font-size: 1.1em;
  color: #555;
  margin-top: 0px;
  margin-left: 10px;
  font-weight: bold;
}

.container {
  border-radius: 12px;
  padding: 10px;
  margin-bottom: 20px;
  margin-top: 10px;
  width: 90%;
  height: 100%;
}

h3 {
  font-size: 16px;
  margin-bottom: 10px;
}

.switch {
  margin-top: 0;
  margin-bottom: 15px;
}

.switch label {
  font-size: 12px;
}

.btn {
  background-color: #5e72e4;
  color: white;
}

.btn:hover {
  background-color: #5e72e4;
}

.switch input[type="checkbox"]:checked + .lever {
  background-color: #ccc !important;
}

.switch input[type="checkbox"]:checked + .lever:before {
  background-color: white !important;
}

.switch label .lever:before {
  background-color: white !important;
}

/* Dark mode styles */
@media (prefers-color-scheme: dark) {
  body {
    background-color: transparent; /* Dark background */
    color: white; /* White text */
  }

  header h1 {
    color: #fff; /* White text */
  }

  .new-container, .new-container p {
    background-color: #2a2a2a; /* Dark background for containers */
    color: #e0e0e0;
  }

  .container {
    background-color: #2a2a2a; /* Dark container background */
    color: white; /* White text */
  }

  .btn {
    background-color: #474f72;
    color: white;
  }

  .btn:hover {
    background-color: #474f72;
  }

  /* Modify the switch styles for dark mode */
  .switch input[type="checkbox"]:checked + .lever {
    background-color: #888 !important;
  }

  .switch input[type="checkbox"]:checked + .lever:before {
    background-color: #fff !important;
  }
}

    </style>
</head>
<body>

<header>
    <div class="new-container">
        <p>Manage Login</p>
    </div>
    <div class="header-top">
        <h1>p</h1>
    </div>
    <div class="header-bottom">
        <h1>p</h1>
    </div>
</header>

<div class="container">
    <h3>Enable / Disable Login</h3>
    <form method="POST">
        <div class="switch">
            <label>
                Login Disabled
                <input type="checkbox" name="login_enabled" <?php echo $config['LOGIN_ENABLED'] ? 'checked' : ''; ?>>
                <span class="lever"></span>
                Login Enabled
            </label>
        </div>
        
        <button type="submit" class="btn">Save Changes</button>
    </form>
</div>

<!-- Include Materialize JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>
