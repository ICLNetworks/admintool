<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbname = trim($_POST['dbname']);
    $dbuser = trim($_POST['dbuser']);
    $dbpass = trim($_POST['dbpass']);

    // Add prefix automatically
    $prefixedDbName = "iclsoftw_" . $dbname;

    // Try DB connection
    $conn = @new mysqli("localhost", $dbuser, $dbpass, $prefixedDbName);

    if ($conn->connect_errno) {
        $error = "Connection failed: " . $conn->connect_error;
    } else {
        $_SESSION['db'] = [
            'name' => $prefixedDbName,
            'user' => $dbuser,
            'pass' => $dbpass
        ];
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SQL Console Login</title>
<style>
/* Reset */
* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

/* Body */
body {
    display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: #f5f5f5; padding: 20px;
}

/* Card */
.login-card {
    background: #fff;
    padding: 30px 25px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    width: 100%;
    max-width: 380px;
}

/* Heading */
.login-card h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #333;
    font-weight: 600;
}

/* Inputs */
.login-card input {
    width: 100%;
    padding: 12px 14px;
    margin: 10px 0;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s;
}
.login-card input:focus {
    border-color: #007bff;
    outline: none;
}

/* Button */
.login-card button {
    width: 100%;
    padding: 12px;
    background: #007bff;
    border: none;
    border-radius: 8px;
    color: white;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
}
.login-card button:hover {
    background: #0056b3;
}

/* Error message */
.error-msg {
    color: #d9534f;
    margin-top: 10px;
    text-align: center;
    font-size: 14px;
}

/* Mobile tweaks */
@media (max-width: 500px) {
    .login-card { padding: 25px 20px; }
    .login-card input, .login-card button { padding: 12px; font-size: 14px; }
}
</style>
</head>
<body>
<div class="login-card">
    <h2>SQL Console Login</h2>
    <form method="post">
        <input type="text" name="dbname" placeholder="Database Name" required>
        <input type="text" name="dbuser" placeholder="DB Username" required>
        <input type="password" name="dbpass" placeholder="DB Password" required>
        <button type="submit">Login</button>
    </form>
    <?php if (!empty($error)) echo "<div class='error-msg'>$error</div>"; ?>
</div>
</body>
</html>
