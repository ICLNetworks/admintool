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
.container { max-width: 1000px; margin:auto; }

/* Header */
h2 {
    text-align:center;
    margin-bottom:30px;
    color:#007bff;
}

/* Navigation Tabs */
.navbar {
    display:flex;
    flex-wrap:wrap;
    justify-content:center;
    gap:15px;
    margin-bottom:20px;
}
.navbar button {
    padding:12px 25px;
    background:#007bff;
    color:white;
    font-weight:500;
    border:none;
    border-radius:8px;
    cursor:pointer;
    transition: background 0.3s;
}
.navbar button:hover { background:#0056b3; }
.navbar button.active { background:#28a745; } /* active tab color */

/* Content Panels */
.tabcontent {
    display:none;
    background:white;
    padding:20px;
    border-radius:8px;
    box-shadow:0 0 10px rgba(0,0,0,0.1);
}

/* Responsive */
@media(max-width:600px){
    .navbar { flex-direction:column; align-items:center; }
    .navbar button { width:100%; text-align:center; }
}
</style>
</head>
<body>
<div class="container">
    <h2>SQL Console Dashboard</h2>
    <div class="navbar">
        <button class="tablink active" onclick="openTab(event,'view')">View</button>
        <button class="tablink" onclick="openTab(event,'update')">Update</button>
        <button class="tablink" onclick="openTab(event,'delete')">Delete</button>
        <button onclick="window.location='logout.php'" style="background:#dc3545;">Logout</button>
    </div>

    <!-- View Tab -->
    <div id="view" class="tabcontent" style="display:block;">
        <?php include 'view.php'; ?>
    </div>

    <!-- Update Tab -->
    <div id="update" class="tabcontent">
        <?php include 'update.php'; ?>
    </div>

    <!-- Delete Tab -->
    <div id="delete" class="tabcontent">
        <?php include 'delete.php'; ?>
    </div>
</div>

<script>
function openTab(evt, tabName) {
    // Hide all tab contents
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    // Remove active class from all buttons
    tablinks = document.getElementsByClassName("tablink");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }

    // Show current tab and set active button
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.classList.add("active");
}
</script>
</body>
</html>
