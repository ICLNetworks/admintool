<?php
include 'db.php';

// Fetch all table names
$tables = [];
$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_array()) {
    $tables[] = $row[0];
}

$query = "";
$result = null;
$error = "";
$columns = [];
$selectedTable = $_POST['table'] ?? "";

if ($selectedTable) {
    $colres = $conn->query("SHOW COLUMNS FROM `$selectedTable`");
    while ($col = $colres->fetch_assoc()) $columns[] = $col['Field'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['runquery'])) {
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

    try {
        $result = $conn->query($query);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Data</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin:0; background:#f5f5f5; padding:20px;
}
.container { max-width: 1000px; margin: auto; }

/* Tabs */
.nav-tabs { display:flex; border-bottom:2px solid #ddd; margin-bottom:20px; flex-wrap:wrap; }
.nav-tabs button {
    padding:10px 20px; border:none; background:#eee; cursor:pointer;
    font-weight:500; border-top-left-radius:8px; border-top-right-radius:8px; margin-right:5px;
    transition: background 0.3s;
}
.nav-tabs button.active { background:#007bff; color:white; }

/* Tab content */
.tab-content {
    background:#fff; padding:25px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08);
}

/* Form Elements */
.tab-content label { display:block; margin-top:10px; font-weight:500; }
.tab-content input, .tab-content select, .tab-content textarea {
    width:100%; padding:10px 12px; margin-top:5px; border-radius:8px; border:1px solid #ccc; font-size:14px;
}
.tab-content select[multiple] { height:120px; }
.tab-content button {
    margin-top:15px; padding:12px 20px; background:#007bff; color:#fff; border:none; border-radius:8px; cursor:pointer;
    transition: background 0.3s; font-size:16px;
}
.tab-content button:hover { background:#0056b3; }

/* Table */
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { border:1px solid #999; padding:8px 12px; text-align:left; }
th { background:#007bff; color:white; }

/* Error */
.error { color:red; margin-top:15px; }

/* Responsive */
@media(max-width:600px){
    .nav-tabs { flex-direction:column; }
    .nav-tabs button { margin-bottom:5px; width:100%; }
}
</style>
<script>
function openTab(tabName){
    const tabs = document.querySelectorAll('.tab-content');
    const btns = document.querySelectorAll('.nav-tabs button');
    tabs.forEach(t => t.style.display='none');
    btns.forEach(b => b.classList.remove('active'));
    document.getElementById(tabName).style.display='block';
    document.getElementById(tabName+'Btn').classList.add('active');
}
window.onload = function(){ openTab('viewTab'); }
</script>
</head>
<body>
<div class="container">
    <div class="nav-tabs">
        <button id="viewTabBtn" onclick="openTab('viewTab')">View</button>
        <button id="updateTabBtn" onclick="openTab('updateTab')">Update</button>
        <button id="deleteTabBtn" onclick="openTab('deleteTab')">Delete</button>
    </div>

    <!-- View Tab -->
    <div id="viewTab" class="tab-content">
        <form method="post">
            <label>Table:</label>
            <select name="table" onchange="this.form.submit()">
                <option value="">--Select Table--</option>
                <?php foreach ($tables as $t): ?>
                <option value="<?= $t ?>" <?= $t==$selectedTable?'selected':'' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>

            <?php if ($selectedTable): ?>
                <label>Select Columns:</label>
                <select name="columns[]" multiple>
                    <?php foreach ($columns as $c): ?>
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

                <button name="runquery" type="submit">Run</button>
            <?php endif; ?>
        </form>

        <?php if ($query): ?>
            <p><strong>Executed Query:</strong> <?= htmlspecialchars($query) ?></p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="error">Error: <?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($result && $result instanceof mysqli_result && !$error): ?>
            <table>
                <tr>
                    <?php foreach ($result->fetch_fields() as $f): ?>
                        <th><?= htmlspecialchars($f->name) ?></th>
                    <?php endforeach; ?>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <?php foreach ($row as $val): ?>
                            <td><?= htmlspecialchars($val) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php elseif ($result === true): ?>
            <p>✅ Query executed successfully!</p>
        <?php endif; ?>
    </div>

    <!-- Update Tab -->
    <div id="updateTab" class="tab-content" style="display:none;">
        <p>Update functionality coming soon.</p>
    </div>

    <!-- Delete Tab -->
    <div id="deleteTab" class="tab-content" style="display:none;">
        <p>Delete functionality coming soon.</p>
    </div>
</div>
</body>
</html>
