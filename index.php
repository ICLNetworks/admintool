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
<html>
<head>
<title>SQL Console Login</title>
<style>
body { font-family: Arial; margin: 60px; }
input { margin: 5px; padding: 8px; width: 250px; }
button { padding: 8px 16px; }
</style>
</head>
<body>
<h2>Login to SQL Console</h2>
<form method="post">
    <input type="text" name="dbname" placeholder="Database Name (without prefix)" required><br>
    <input type="text" name="dbuser" placeholder="DB Username" required><br>
    <input type="password" name="dbpass" placeholder="DB Password" required><br>
    <button type="submit">Login</button>
</form>
<?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
</body>
</html>
