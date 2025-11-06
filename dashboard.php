<?php
session_start();
include 'db.php';

if (!isset($_SESSION['db'])) {
    header("Location: index.php");
    exit;
}

$tables = [];
$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_array()) $tables[] = $row[0];

$selectedTable = $_POST['table'] ?? "";
$columns = [];
$query = "";
$result = null;
$error = "";

// Get columns
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

    // AJAX response
    if (isset($_POST['ajax'])) {
        $data = ['error'=>$error,'records'=>[]];
        if ($result && $result instanceof mysqli_result && !$error) {
            while($row=$result->fetch_assoc()) $data['records'][]=$row;
        }
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
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

</div>

<!-- Modal for results -->

<div id="resultModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
    background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
    <div style="background:#fff; padding:20px; border-radius:12px; max-width:90%; max-height:80%; overflow:auto; position:relative;">
        <span style="position:absolute; top:10px; right:15px; cursor:pointer; font-weight:bold;" onclick="closeModal()">✖</span>
        <div id="modalContent"></div>
        <div style="margin-top:15px; text-align:center;">
            <button onclick="prevPage()" style="margin-right:10px;">Prev</button>
            <span id="pageInfo"></span>
            <button onclick="nextPage()" style="margin-left:10px;">Next</button>
        </div>
    </div>
</div>

<!-- Update & Delete Tabs -->

<div id="updateTab" class="tab-content"><p>Update functionality coming soon.</p></div>
<div id="deleteTab" class="tab-content"><p>Delete functionality coming soon.</p></div>

<script>
let modalData = [];
let currentPage = 1;
const pageSize = 50;

function closeModal() { document.getElementById('resultModal').style.display='none'; }
function renderPage() {
    const start = (currentPage-1)*pageSize;
    const end = start + pageSize;
    const pageData = modalData.slice(start,end);
    if(pageData.length===0) return;
    let html = '<table><tr>';
    Object.keys(pageData[0]).forEach(col => html += `<th>${col}</th>`);
    html += '</tr>';
    pageData.forEach(row=>{
        html += '<tr>';
        Object.values(row).forEach(val=> html+=`<td>${val}</td>`);
        html+='</tr>';
    });
    html += '</table>';
    document.getElementById('modalContent').innerHTML = html;
    document.getElementById('pageInfo').innerText = `Page ${currentPage} of ${Math.ceil(modalData.length/pageSize)}`;
}
function nextPage() { if(currentPage < Math.ceil(modalData.length/pageSize)) { currentPage++; renderPage(); } }
function prevPage() { if(currentPage > 1) { currentPage--; renderPage(); } }

document.querySelector('#viewTab form').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('ajax',1);

    fetch('', { method:'POST', body:formData })
    .then(res => res.json())
    .then(data=>{
        if(data.error) { alert(data.error); return; }
        modalData = data.records;
        currentPage = 1;
        renderPage();
        document.getElementById('resultModal').style.display='flex';
    })
    .catch(err=>alert('Error: '+err));
});
</script>

</div>
</body>
</html>
