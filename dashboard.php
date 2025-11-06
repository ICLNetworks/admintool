<?php
session_start();
include 'db.php';

// Redirect if session not set
if (!isset($_SESSION['db'])) {
    header("Location: index.php");
    exit;
}

// Fetch table names
$tables = [];
$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_array()) $tables[] = $row[0];

// Initialize variables
$selectedTable = $_POST['table'] ?? "";
$columns = [];
$query = "";
$result = null;
$error = "";

// Get columns if table selected
if ($selectedTable) {
    $colres = $conn->query("SHOW COLUMNS FROM `$selectedTable`");
    while ($col = $colres->fetch_assoc()) $columns[] = $col['Field'];
}

// Handle View Query
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['runview'])) {
    $table = $_POST['table'];
    $selectCols = isset($_POST['columns']) ? implode(",", $_POST['columns']) : "*";
    $where = trim($_POST['where']);
    $orderby = trim($_POST['orderby']);
    $orderdir = $_POST['orderdir'] ?? "ASC";
    $limit = trim($_POST['limit']);

    $query = "SELECT $selectCols FROM `$table`";
    if ($where) $query .= " WHERE $where";
    if ($orderby) $query .= " ORDER BY $orderby $orderdir";
    if ($limit) $query .= " LIMIT $limit";

    $result = $conn->query($query) ?: null;
    if (!$result) $error = $conn->error;
}

// You can later add similar handling for Update and Delete here
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SQL Dashboard</title>
<style>
body {
    font-family: 'Segoe UI', sans-serif;
    margin:0; padding:20px; background:#f0f2f5;
}
.container { max-width: 1100px; margin:auto; }

/* Tabs */
.nav-tabs { display:flex; gap:5px; flex-wrap:wrap; margin-bottom:20px; }
.nav-tabs button {
padding:10px 20px; border:none; background:#e2e6ea; border-radius:8px 8px 0 0;
cursor:pointer; font-weight:500; transition:0.3s;
}
.nav-tabs button.active { background:#007bff; color:#fff; }

/* Tab content */
.tab-content {
display:none; background:#fff; padding:25px; border-radius:12px;
box-shadow:0 4px 12px rgba(0,0,0,0.08);
}
.tab-content form label { display:block; margin-top:10px; font-weight:500; }
.tab-content input, .tab-content select, .tab-content textarea {
width:100%; padding:10px; margin-top:5px; border-radius:8px; border:1px solid #ccc; font-size:14px;
}
.tab-content select[multiple] { height:120px; }
.tab-content button { margin-top:15px; padding:12px 20px; background:#007bff; color:#fff; border:none; border-radius:8px; cursor:pointer; transition:0.3s; }
.tab-content button:hover { background:#0056b3; }

/* Table */
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { border:1px solid #999; padding:8px; text-align:left; }
th { background:#007bff; color:#fff; }

/* Error */
.error { color:red; margin-top:15px; }

/* Responsive */
@media(max-width:600px){
.nav-tabs { flex-direction:column; }
.nav-tabs button { width:100%; margin-bottom:5px; }
} </style>

<script>
function openTab(tabName){
    document.querySelectorAll('.tab-content').forEach(t=>t.style.display='none');
    document.querySelectorAll('.nav-tabs button').forEach(b=>b.classList.remove('active'));
    document.getElementById(tabName).style.display='block';
    document.getElementById(tabName+'Btn').classList.add('active');
}
window.onload = ()=> openTab('viewTab');
</script>

</head>
<body>
<div class="container">
    <h2>SQL Console Dashboard</h2>
    <div class="nav-tabs">
        <button id="viewTabBtn" onclick="openTab('viewTab')">View</button>
        <button id="updateTabBtn" onclick="openTab('updateTab')">Update</button>
        <button id="deleteTabBtn" onclick="openTab('deleteTab')">Delete</button>
        <a href="logout.php" style="margin-left:auto; text-decoration:none; color:#007bff;">Logout</a>
    </div>

```
<!-- View Tab -->
<div id="viewTab" class="tab-content">
    <form method="post">
        <label>Table:</label>
        <select name="table" onchange="this.form.submit()">
            <option value="">--Select Table--</option>
            <?php foreach($tables as $t): ?>
            <option value="<?= $t ?>" <?= $t==$selectedTable?'selected':'' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>

        <?php if($selectedTable): ?>
            <label>Select Columns:</label>
            <select name="columns[]" multiple>
                <?php foreach($columns as $c): ?>
                <option value="<?= $c ?>" <?= (isset($_POST['columns']) && in_array($c,$_POST['columns']))?'selected':'' ?>><?= $c ?></option>
                <?php endforeach; ?>
            </select>

            <label>Where:</label>
            <textarea name="where" rows="2"><?= htmlspecialchars($_POST['where'] ?? '') ?></textarea>

            <label>Order By:</label>
            <input type="text" name="orderby" value="<?= htmlspecialchars($_POST['orderby'] ?? '') ?>">
            <select name="orderdir">
                <option value="ASC" <?= (($_POST['orderdir'] ?? '')=='ASC')?'selected':'' ?>>ASC</option>
                <option value="DESC" <?= (($_POST['orderdir'] ?? '')=='DESC')?'selected':'' ?>>DESC</option>
            </select>

            <label>Limit:</label>
            <input type="number" name="limit" value="<?= htmlspecialchars($_POST['limit'] ?? '') ?>">

            <button name="runview" type="submit">Run</button>
        <?php endif; ?>
    </form>

    <?php if($query): ?><p><strong>Executed Query:</strong> <?= htmlspecialchars($query) ?></p><?php endif; ?>
    <?php if($error): ?><p class="error">Error: <?= htmlspecialchars($error) ?></p><?php endif; ?>

    <?php if($result && $result instanceof mysqli_result && !$error): ?>
        <table>
            <tr><?php foreach($result->fetch_fields() as $f): ?><th><?= htmlspecialchars($f->name) ?></th><?php endforeach; ?></tr>
            <?php while($row=$result->fetch_assoc()): ?>
                <tr><?php foreach($row as $val): ?><td><?= htmlspecialchars($val) ?></td><?php endforeach; ?></tr>
            <?php endwhile; ?>
        </table>
    <?php elseif($result===true): ?><p>✅ Query executed successfully!</p><?php endif; ?>
</div>

<!-- Update Tab -->
<div id="updateTab" class="tab-content"><p>Update functionality coming soon.</p></div>

<!-- Delete Tab -->
<div id="deleteTab" class="tab-content"><p>Delete functionality coming soon.</p></div>
```

</div>
</body>
</html>
