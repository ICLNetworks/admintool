<?php
session_start();
if (!isset($_SESSION['db'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SQL Console Dashboard</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin:0; padding:20px; background:#f5f5f5;
}
.container { max-width: 900px; margin:auto; }

/* Header */
h2 {
    text-align:center;
    margin-bottom:30px;
    color:#007bff;
}

/* Navigation Buttons */
.navbar {
    display:flex;
    flex-wrap:wrap;
    justify-content:center;
    gap:15px;
    margin-bottom:40px;
}
.navbar a {
    padding:12px 25px;
    background:#007bff;
    color:white;
    text-decoration:none;
    font-weight:500;
    border-radius:8px;
    transition: background 0.3s;
}
.navbar a:hover {
    background:#0056b3;
}

/* Active Tab */
.navbar a.active {
    background:#28a745; /* Green for active tab */
}

/* Logout Button */
.logout {
    background:#dc3545 !important;
}
.logout:hover {
    background:#a71d2a !important;
}

/* Responsive */
@media(max-width:600px){
    .navbar { flex-direction:column; align-items:center; }
    .navbar a { width:100%; text-align:center; }
}
</style>
</head>
<body>
<div class="container">
    <h2>SQL Console Dashboard</h2>
    <div class="navbar">
        <a href="view.php" class="active">View</a>
        <a href="update.php">Update</a>
        <a href="delete.php">Delete</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>
</div>
</body>
</html>
