<?php
session_start();
if (!isset($_SESSION['db'])) {
    header("Location: index.php");
    exit;
}
$dbinfo = $_SESSION['db'];
$conn = new mysqli("localhost", $dbinfo['user'], $dbinfo['pass'], $dbinfo['name']);
if ($conn->connect_errno) {
    die("DB Connection Failed: " . $conn->connect_error);
}
?>
