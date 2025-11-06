<?php
// session_start();
// if (!isset($_SESSION['db'])) {
//     header("Location: index.php");
//     exit;
// }
?>
<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>
<style>
body { font-family: Arial; margin: 40px; }
nav a { margin: 0 15px; text-decoration: none; font-weight: bold; }
</style>
</head>
<body>
<h2>SQL Console Dashboard</h2>
<nav>
    <a href="view.php">View</a>
    <a href="update.php">Update</a>
    <a href="delete.php">Delete</a>
    <a href="logout.php" style="float:right;">Logout</a>
</nav>
</body>
</html>
