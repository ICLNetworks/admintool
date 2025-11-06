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

// AJAX: fetch columns for a selected table
if (isset($_POST['getColumns']) && $_POST['getColumns']) {
    $table = $_POST['getColumns'];
    $columns = [];
    $colres = $conn->query("SHOW COLUMNS FROM `$table`");
    while ($col = $colres->fetch_assoc()) $columns[] = $col['Field'];
    header('Content-Type: application/json');
    echo json_encode($columns);
    exit;
}

// AJAX: run query
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $table = $_POST['table'] ?? "";
    $selectCols = isset($_POST['columns']) && count($_POST['columns'])>0 ? implode(",", $_POST['columns']) : "*";
    $where = trim($_POST['where'] ?? '');
    $orderby = trim($_POST['orderby'] ?? '');
    $orderdir = $_POST['orderdir'] ?? "ASC";
    $limit = trim($_POST['limit'] ?? '');

    $query = "SELECT $selectCols FROM `$table`";
    if ($where) $query .= " WHERE $where";
    if ($orderby) $query .= " ORDER BY $orderby $orderdir";
    if ($limit) $query .= " LIMIT $limit";

    $result = $conn->query($query) ?: null;
    $data = ['error'=>$conn->error,'records'=>[]];
    if ($result && $result instanceof mysqli_result && !$data['error']) {
        while($row=$result->fetch_assoc()) $data['records'][]=$row;
    }

    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
// ✅ AJAX: UPDATE QUERY
if (isset($_POST['updateAjax'])) {
    $table = $_POST['table'];
    $setCols = $_POST['set_col'] ?? [];
    $setVals = $_POST['set_val'] ?? [];
    $whereCols = $_POST['where_col'] ?? [];
    $whereVals = $_POST['where_val'] ?? [];

    $setParts = [];
    foreach($setCols as $i=>$c){
        if($c !== "") $setParts[] = "`$c` = ?";
    }

    $whereParts = [];
    foreach($whereCols as $i=>$c){
        if($c !== "") $whereParts[] = "`$c` = ?";
    }

    if(empty($setParts) || empty($whereParts)){
        echo json_encode(['error'=>"Please provide SET and WHERE conditions"]);
        exit;
    }

    $sql = "UPDATE `$table` SET ".implode(", ",$setParts)." WHERE ".implode(" AND ",$whereParts);

    $stmt = $conn->prepare($sql);
    $vals = array_merge($setVals, $whereVals);
    $stmt->bind_param(str_repeat("s", count($vals)), ...$vals);
    $stmt->execute();

    echo json_encode(['error'=>$stmt->error,'affected'=>$stmt->affected_rows]);
    exit;
}
?>

<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SQL Dashboard</title>
<style>
* { box-sizing: border-box; }
body { font-family:'Segoe UI',sans-serif; margin:0;padding:20px; background:#f0f2f5; }
.container { max-width:1100px; margin:auto; }
.nav-tabs { display:flex; gap:5px; flex-wrap:wrap; margin-bottom:20px; }
.nav-tabs button { padding:10px 20px; border:none; background:#e2e6ea; border-radius:8px 8px 0 0; cursor:pointer; font-weight:500; transition:0.3s; }
.nav-tabs button.active { background:#007bff; color:#fff; }
.tab-content { display:none; background:#fff; padding:25px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); }
.tab-content form label { display:block; margin-top:10px; font-weight:500; }
input, select, textarea { width:100%; padding:10px; margin-top:5px; border-radius:8px; border:1px solid #ccc; }
select[multiple] { height:120px; }
button { margin-top:15px; padding:12px 20px; background:#007bff; color:#fff; border:none; border-radius:8px; cursor:pointer; }
button:hover { background:#0056b3; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { border:1px solid #999; padding:8px; text-align:left; }
th { background:#007bff; color:#fff; }
.error { color:red; margin-top:15px; }
@media(max-width:600px){
.nav-tabs { flex-direction:column; }
.nav-tabs button { width:100%; margin-bottom:5px; }
}
/* Modal */
#resultModal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; }
.modal-content { background:#fff; padding:20px; border-radius:10px; max-width:90%; max-height:90%; overflow:auto; }
.modal-header { display:flex; justify-content:space-between; align-items:center; }
.modal-header h3 { margin:0; }
.modal-header button { background:red; color:#fff; border:none; border-radius:5px; cursor:pointer; padding:5px 10px; }
.pagination { margin-top:10px; display:flex; gap:5px; flex-wrap:wrap; }
.pagination button { padding:5px 10px; border:none; border-radius:5px; cursor:pointer; background:#007bff; color:#fff; }
.pagination button.active { background:#0056b3; }
</style>
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
<form id="viewForm">
    <label>Table:</label>
    <select name="table" id="tableSelect">
        <option value="">--Select Table--</option>
        <?php foreach($tables as $t): ?>
        <option value="<?= $t ?>"><?= $t ?></option>
        <?php endforeach; ?>
    </select>

<div id="columnsContainer" style="display:none;">
    <label>Select Columns:</label>
    <select name="columns[]" id="columnsSelect" multiple></select>

    <label>Where:</label>
    <textarea name="where" rows="2"></textarea>
    <p id="whereError" style="color:red; margin-top:5px; display:none;"></p>

    <label>Order By:</label>
    <select name="orderby" id="orderbySelect">
        <option value="">-- None --</option>
    </select>
    <select name="orderdir">
        <option value="ASC">ASC</option>
        <option value="DESC">DESC</option>
    </select>

    <label>Limit:</label>
    <input type="number" name="limit">

    <button type="submit">Run</button>
</div>

<!-- ✅ UPDATE TAB (merged) -->
<div id="updateTab" class="tab-content">
<form id="updateForm">

<label>Table:</label>
<select name="table" id="updateTableSelect">
<option value="">--Select Table--</option>
<?php foreach($tables as $t): ?>
<option value="<?= $t ?>"><?= $t ?></option>
<?php endforeach; ?>
</select>

<div id="updateFieldsContainer" style="display:none;">

<h3>SET (Columns to Update)</h3>
<div id="setContainer"></div>
<button type="button" onclick="addSetRow()">+ Add Column to Update</button>

<h3>WHERE (Criteria)</h3>
<div id="whereContainer"></div>
<button type="button" onclick="addWhereRow()">+ Add Condition</button>

<button type="submit">Update</button>

<p id="updateMsg" style="margin-top:10px;"></p>

</div>
</div>

<div id="deleteTab" class="tab-content"><p>Delete functionality coming soon.</p></div>
</div>

<!-- Modal -->

<div id="resultModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Query Result</h3>
            <button onclick="document.getElementById('resultModal').style.display='none'">Close</button>
        </div>
        <div id="modalTable"></div>
        <div class="pagination" id="pagination"></div>
    </div>
</div>

<script>
let modalData = [];
let currentPage = 1;
const recordsPerPage = 50;

function openTab(tabName){
    document.querySelectorAll('.tab-content').forEach(t=>t.style.display='none');
    document.querySelectorAll('.nav-tabs button').forEach(b=>b.classList.remove('active'));
    document.getElementById(tabName).style.display='block';
    document.getElementById(tabName+'Btn').classList.add('active');
}

// Render modal page
function renderPage(){
    const start = (currentPage-1)*recordsPerPage;
    const end = start + recordsPerPage;
    const pageData = modalData.slice(start,end);
    if(pageData.length===0){ document.getElementById('modalTable').innerHTML='<p>No records</p>'; return; }

    let html = '<table><tr>';
    Object.keys(pageData[0]).forEach(h=>{ html+='<th>'+h+'</th>'; });
    html+='</tr>';
    pageData.forEach(row=>{
        html+='<tr>';
        Object.values(row).forEach(v=> html+='<td>'+v+'</td>');
        html+='</tr>';
    });
    html+='</table>';
    document.getElementById('modalTable').innerHTML=html;

    // pagination buttons
    const totalPages = Math.ceil(modalData.length/recordsPerPage);
    let pgHtml='';
    for(let i=1;i<=totalPages;i++){
        pgHtml+='<button class="'+(i===currentPage?'active':'')+'" onclick="currentPage='+i+';renderPage();">'+i+'</button>';
    }
    document.getElementById('pagination').innerHTML=pgHtml;
}

// Table change: fetch columns via AJAX
document.getElementById('tableSelect').addEventListener('change', function(){
    const table = this.value;
    if(!table){ document.getElementById('columnsContainer').style.display='none'; return; }

    fetch('', {
        method:'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body:'getColumns='+encodeURIComponent(table)
    })
    .then(res=>res.json())
    .then(cols=>{
        const sel = document.getElementById('columnsSelect');
        sel.innerHTML = '';
        cols.forEach(c=>{
            const opt = document.createElement('option');
            opt.value=c; opt.text=c;
            sel.add(opt);
        });
        const orderSel = document.getElementById('orderbySelect');
        orderSel.innerHTML = '<option value="">-- None --</option>';
        cols.forEach(c=>{
            const opt = document.createElement('option');
            opt.value=c; opt.text=c;
            orderSel.add(opt);
        });
        document.getElementById('columnsContainer').style.display='block';
    });
});

// Run query via AJAX
document.getElementById('viewForm').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('ajax',1);

    fetch('', { method:'POST', body:formData })
    .then(res=>res.json())
    .then(data=>{
        if(data.error){ alert(data.error); return; }
        modalData=data.records;
        currentPage=1;
        renderPage();
        document.getElementById('resultModal').style.display='flex';
    })
    .catch(err=>alert('Error: '+err));
});
let updateColumns=[];

document.getElementById('updateTableSelect').addEventListener('change', function(){
    fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'getColumns='+this.value})
    .then(r=>r.json()).then(cols=>{
        updateColumns=cols;
        document.getElementById('setContainer').innerHTML='';
        document.getElementById('whereContainer').innerHTML='';
        addSetRow(); addWhereRow();
        document.getElementById('updateFieldsContainer').style.display='block';
    });
});

function colDropdown(name){
    let html=`<select name="${name}"><option value="">--column--</option>`;
    updateColumns.forEach(c=> html+=`<option value="${c}">${c}</option>`);
    return html+"</select>";
}

function addSetRow(){
    setContainer.insertAdjacentHTML('beforeend',
    `<div style="display:flex;gap:10px;margin-top:5px;">${colDropdown('set_col[]')}<input name="set_val[]" placeholder="Value"></div>`);
}

function addWhereRow(){
    whereContainer.insertAdjacentHTML('beforeend',
    `<div style="display:flex;gap:10px;margin-top:5px;">${colDropdown('where_col[]')}<input name="where_val[]" placeholder="Value"></div>`);
}

document.getElementById('updateForm').addEventListener('submit', function(e){
    e.preventDefault();
    const fd=new FormData(this);
    fd.append('updateAjax',1);
    fetch('',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        updateMsg.style.color = d.error ? 'red' : 'green';
        updateMsg.innerText = d.error ? d.error : "✅ Updated "+d.affected+" row(s)";
    });
});

function openTab(n){document.querySelectorAll('.tab-content').forEach(t=>t.style.display='none');document.getElementById(n).style.display='block';document.querySelectorAll('.nav-tabs button').forEach(b=>b.classList.remove('active'));document.getElementById(n+'Btn').classList.add('active');}

window.onload = ()=> openTab('viewTab');
</script>

</body>
</html>
