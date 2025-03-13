<?php
session_start();

require_once '/data/adb/php7/files/www/auth/auth_functions.php';
if (isset($_SESSION['login_disabled']) && $_SESSION['login_disabled'] === true) {
} else {
    checkUserLogin();
}

$credentials = include 'credentials.php';
$stored_username = $credentials['username'];
$stored_hashed_password = $credentials['hashed_password'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_username = $_POST['new_username'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // Validate current password using the same method as login
    {
        if ($new_password === $confirm_new_password) {
            // Hash new password
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update credentials.php file
            $credentials_content = "<?php\n";
            $credentials_content .= "if (basename(__FILE__) == basename(\$_SERVER['PHP_SELF'])) {\n";
            $credentials_content .= "    header('Location: /');\n";
            $credentials_content .= "    exit;\n";
            $credentials_content .= "}\n";
            $credentials_content .= "return [\n";
            $credentials_content .= "    'username' => '" . addslashes($new_username) . "',\n";
            $credentials_content .= "    'hashed_password' => '" . addslashes($new_hashed_password) . "',\n";
            $credentials_content .= "];\n";

            file_put_contents('credentials.php', $credentials_content);

            $success = 'Username and password have been updated successfully.';
        } else {
            $error = 'New passwords do not match.';
        }
    } 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Administration</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet"> <!-- Font Awesome -->
    <style>
body {
  font-family: Arial, sans-serif;
  background-color: transparent;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin: 0;
  transition: background-color 0.3s ease, color 0.3s ease;
}

/* Style umum untuk mode terang */
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
  color: #000;
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
  margin-top: 3px;
  margin-left: 10px;
  font-weight: bold;
}

.container {
  border-radius: 12px;
  padding: 10px;
  margin-bottom: 20px;
  margin-top: 10px;
  width: 90%;
  background-color: #ffffff;
  height: 100%;
  box-shadow: 2px 4px 6px rgba(0, 0, 0, 0.1);
}

h5 {
  text-align: center;
  font-size: 1.2em;
  color: #333;
  margin-bottom: 20px;
}

.input-field {
  margin-bottom: 20px;
  position: relative;
}

.input-field input {
  width: 100%;
  padding: 8px;
  font-size: 1em;
  border: 1px solid #ccc;
  border-radius: 5px;
  outline: none;
  box-sizing: border-box;
  background-color: #f9f9f9;
}

.input-field input:focus {
  border-color: #5e72e4;
}

.input-field label {
  position: absolute;
  top: -8px;
  left: 10px;
  font-size: 0.9em;
  color: #333;
  background-color: #fff;
  padding: 0 5px;
  transition: all 0.3s ease;
  border-radius: 2px;
}

.input-field input:focus + label,
.input-field input:not(:focus):valid + label {
  top: -18px;
  font-size: 0.8em;
  color: #5e72e4;
}

.error, .success {
  text-align: center;
  margin-bottom: 10px;
  font-size: 1em;
}

.error {
  color: red;
}

.success {
  color: green;
}

.save-button {
  text-align: center;
}

.save-button button {
  background-color: #5e72e4;
  color: #fff;
  border: none;
  padding: 12px 20px;
  font-size: 1em;
  border-radius: 5px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.save-button button:hover {
  background-color: #4e61d6;
}

/* Style for the toggle button (using Font Awesome asterisk) */
.toggle-password {
  position: absolute;
  right: 0px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  font-size: 0.6em;
  color: white;
  background-color: rgba(0, 0, 0, 0.3);
  width: 33px;
  height: 35px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 5px;
}

.toggle-password:hover {
  background-color: #808080;
  color: white;
}

/* Dark Mode Styles */
@media (prefers-color-scheme: dark) {
  body {
    background-color: transparent;
    color: transparent;
  }

  header h1 {
    color: #e0e0e0;
  }

  .new-container, .new-container p {
    background-color: #2a2a2a;
    color: #e0e0e0;
  }

  .container {
    background-color: #2a2a2a;
    color: #e0e0e0;
    box-shadow: 2px 4px 6px rgba(0, 0, 0, 0.5);
  }

  h5 {
    color: #e0e0e0;
  }

  .input-field input {
    background-color: #2a2a2a;
    color: #fff;
  }

  .input-field input:focus {
    border-color: #474f72;
  }

  .input-field label {
    color: #000;
    background-color: #e0e0e0;
  }

  .error {
    color: #ff6666;
  }

  .success {
    color: #66ff66;
  }

  .save-button button {
    background-color: #474f72;
    color: #fff;
  }

  .save-button button:hover {
    background-color: #4e61d6;
  }

  .toggle-password {
    background-color: rgba(0, 0, 0, 0.3);
  }

  .toggle-password:hover {
    background-color: rgba(0, 0, 0, 0.6);
  }
}

    </style>
</head>
<body>

<header>
    <div class="new-container">
        <p>Administration</p>
    </div>
    <div class="header-top">
        <h1>Change Password</h1>
    </div>
    <div class="header-bottom">
        <h1>Manage your credentials</h1>
    </div>
</header>

<!-- Change Password Form Section -->
<div class="container">
    <h5>Change Password</h5>

    <!-- Displaying success/error messages -->
    <?php if ($error) echo "<p class='error'>$error</p>"; ?>
    <?php if ($success) echo "<p class='success'>$success</p>"; ?>

    <form method="post" action="change_password.php">
        <div class="input-field">
            <input type="text" name="new_username" id="new_username" required>
            <label for="new_username">New Username</label>
        </div>
        <div class="input-field">
            <input type="password" name="new_password" id="new_password" required>
            <label for="new_password">New Password</label>
            <span class="toggle-password" onclick="togglePasswordVisibility()">
                <i class="fas fa-asterisk"></i>
            </span>
        </div>
        <div class="input-field">
            <input type="password" name="confirm_new_password" id="confirm_new_password" required>
            <label for="confirm_new_password">Confirm New Password</label>
            <span class="toggle-password" onclick="togglePasswordVisibility()">
                <i class="fas fa-asterisk"></i>
            </span>
        </div>
        <div class="save-button">
            <button type="submit" class="btn waves-effect waves-light">Save</button>
        </div>
    </form>
</div>

<!-- Custom JavaScript for toggling password visibility -->
<script>
    function togglePasswordVisibility() {
        var passwordFields = document.querySelectorAll("input[type=password]");
        passwordFields.forEach(field => {
            if (field.type === "password") {
                field.type = "text";
            } else {
                field.type = "password";
            }
        });
    }
</script>

</body>
</html>
