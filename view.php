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
<html>
<head>
<title>View Data</title>
<style>
body { font-family: Arial; margin: 40px; }
label { display:block; margin-top:10px; }
textarea,input,select { margin-top:5px; width:300px; padding:5px; }
table { border-collapse: collapse; margin-top:20px; }
th,td { border:1px solid #999; padding:6px 10px; }
</style>
</head>
<body>
<h2>View Records</h2>
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
        <select name="columns[]" multiple size="5">
            <?php foreach ($columns as $c): ?>
            <option value="<?= $c ?>"><?= $c ?></option>
            <?php endforeach; ?>
        </select>

        <label>Where:</label>
        <textarea name="where" rows="2"></textarea>

        <label>Order By:</label>
        <input type="text" name="orderby">
        <select name="orderdir">
            <option value="ASC">ASC</option>
            <option value="DESC">DESC</option>
        </select>

        <label>Limit:</label>
        <input type="number" name="limit">

        <button name="runquery" type="submit">Run</button>
    <?php endif; ?>
</form>

<?php if ($query): ?>
<p><strong>Executed Query:</strong> <?= htmlspecialchars($query) ?></p>
<?php endif; ?>

<?php if ($error): ?>
<p style="color:red;">Error: <?= htmlspecialchars($error) ?></p>
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

</body>
</html>
