<?php
// payment_history.php
session_start();
include 'db.php';

// Only admin access
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$table = 'payments'; // ensure this matches your DB table name

// ---------- AJAX handlers ----------
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])){
    header('Content-Type: application/json; charset=utf-8');

    function json_err($msg){ echo json_encode(['success'=>false,'error'=>$msg]); exit; }
    function json_ok($data=[]){ echo json_encode(array_merge(['success'=>true], $data)); exit; }

    $action = $_POST['action'];

    // EDIT
    if($action === 'edit'){
        $id = intval($_POST['id'] ?? 0);
        if(!$id) json_err('Invalid ID');

        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0.00;
        $status = in_array($_POST['status'] ?? '', ['paid','pending']) ? $_POST['status'] : 'pending';
        $method = in_array($_POST['payment_method'] ?? '', ['upi','card','netbanking']) ? $_POST['payment_method'] : null;
        $upi_id = trim($_POST['upi_id'] ?? '');
        $card_last4 = trim($_POST['card_last4'] ?? '');
        $netbank_user = trim($_POST['netbank_user'] ?? '');

        $stmt = $conn->prepare("UPDATE {$table} SET amount=?, status=?, payment_method=?, upi_id=?, card_last4=?, netbank_user=? WHERE id=?");
        if(!$stmt) json_err("Prepare failed: ".$conn->error);
        $stmt->bind_param("dsssssi", $amount, $status, $method, $upi_id, $card_last4, $netbank_user, $id);
        if($stmt->execute()){
            $stmt->close();
            json_ok(['msg'=>'Payment updated']);
        } else {
            $err = $stmt->error;
            $stmt->close();
            json_err("Execute failed: ".$err);
        }
    }

    // DELETE single
    if($action === 'delete'){
        $id = intval($_POST['id'] ?? 0);
        if(!$id) json_err('Invalid ID for deletion');

        $stmt = $conn->prepare("DELETE FROM {$table} WHERE id=?");
        if(!$stmt) json_err("Prepare failed: ".$conn->error);
        $stmt->bind_param("i", $id);
        if($stmt->execute()){
            $stmt->close();
            json_ok(['msg'=>'Deleted']);
        } else {
            $err = $stmt->error;
            $stmt->close();
            json_err("Delete failed: ".$err);
        }
    }

    // BULK DELETE
    if($action === 'bulk_delete'){
        $ids = $_POST['ids'] ?? [];
        if(!is_array($ids) || count($ids) === 0) json_err('No IDs provided');

        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function($v){ return $v>0; });
        if(count($ids) === 0) json_err('No valid IDs');

        $in = implode(',', $ids); // sanitized ints
        $sql = "DELETE FROM {$table} WHERE id IN ($in)";
        if($conn->query($sql)){
            json_ok(['msg'=>'Bulk deleted','count'=>count($ids)]);
        } else {
            json_err('Bulk delete failed: '.$conn->error);
        }
    }

    // WRITE COMPUTED TOTALS BACK TO DB
    if($action === 'write_totals'){
        $res = $conn->query("SELECT id, course_ids, amount FROM {$table} WHERE amount<=0");
        if(!$res) json_err("Select failed: ".$conn->error);

        $updated = 0;
        while($row = $res->fetch_assoc()){
            $computed = compute_total_from_course_ids($conn, $row['course_ids']);
            if($computed > 0){
                $stmt = $conn->prepare("UPDATE {$table} SET amount=? WHERE id=?");
                if($stmt){
                    $stmt->bind_param("di", $computed, $row['id']);
                    if($stmt->execute()) $updated++;
                    $stmt->close();
                }
            }
        }
        $res->free();
        json_ok(['msg'=>"Updated $updated payments"]);
    }

    json_err('Unknown action');
}

// ---------- helper to compute total from course_ids ----------
function compute_total_from_course_ids($conn, $course_ids_string){
    $total = 0.00;
    $course_ids_string = trim($course_ids_string);
    if($course_ids_string === '') return $total;
    $parts = array_filter(array_map('intval', explode(',', $course_ids_string)));
    if(empty($parts)) return $total;
    $in = implode(',', $parts);
    $sql = "SELECT COALESCE(SUM(price),0) AS total FROM courses WHERE id IN ($in)";
    $res = $conn->query($sql);
    if($res){
        $row = $res->fetch_assoc();
        $total = floatval($row['total'] ?? 0.00);
        $res->free();
    }
    return $total;
}

// ---------- Fetch payments for display ----------
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

$query = "
SELECT p.id, p.user_id, u.username, p.amount, p.payment_method, p.upi_id, 
       p.card_last4, p.netbank_user, p.course_ids, p.status, p.created_at
FROM payments p
JOIN users u ON p.user_id = u.id
WHERE 1=1
";

$params = [];
$types = '';
if($search){
    $query .= " AND u.username LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}
if($filter && in_array($filter,['paid','pending'])){
    $query .= " AND p.status = ?";
    $params[] = $filter;
    $types .= 's';
}
$query .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
if(!$stmt){
    die("SQL Error: ".$conn->error);
}
if(!empty($params)){
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>SkillPilot — Payment History</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
:root{--trans:0.32s;--radius:12px;--shadow:0 8px 24px rgba(2,6,23,0.45);--bg-dark:linear-gradient(180deg,#071428,#102a43);--card-dark:rgba(255,255,255,0.04);--muted:#9ad6ff;--text:#e6f7ff;--accent:linear-gradient(90deg,#00c6ff,#7b2ff7);}
body{margin:0;font-family:'Poppins',sans-serif;background:var(--bg-dark);color:var(--text);transition:background var(--trans),color var(--trans);}
.container{max-width:1100px;margin:32px auto;padding:0 16px;}
.navbar{padding:18px 16px;background:transparent;display:flex;justify-content:space-between;align-items:center;}
.brand{font-weight:800;font-size:20px;}
.controls{display:flex;gap:10px;align-items:center;margin-bottom:20px;}
.searchbox input, .searchbox select{padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,0.06);background:rgba(255,255,255,0.02);color:var(--text);min-width:160px;}
.btn{padding:10px 14px;border-radius:10px;background:var(--accent);border:none;color:#fff;cursor:pointer;text-decoration:none;}
.btn:hover{text-decoration:none;}
.bulk-actions{display:flex;gap:10px;align-items:center;margin-bottom:12px;}
.paycard{position:relative;background:var(--card-dark);padding:20px 28px;border-radius:12px;box-shadow:var(--shadow);margin-bottom:18px;overflow:hidden;}
.paycard:hover{transform:translateY(-4px);transition:transform .18s ease;}
.paycard-content{padding-left:64px;}
.select-box{position:absolute;left:18px;top:18px;width:18px;height:18px;z-index:5;accent-color:#00c6ff;}
.actions{position:absolute;right:14px;top:12px;display:flex;gap:8px;}
.actions button{background:transparent;border:none;color:var(--muted);font-size:16px;cursor:pointer;}
.actions button:hover{color:#fff;transform:scale(1.05);}
.header-line-1{font-size:20px;font-weight:700;margin:0 0 6px 0;}
.header-line-2{font-size:14px;color:var(--muted);margin:0 0 12px 0;}
.pay-details p{margin:6px 0;color:var(--muted);font-size:15px;}
.status-paid{color:#4caf50;font-weight:700;}
.status-pending{color:#ffb74d;font-weight:700;}
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;z-index:1200;}
.modal .inner{background:#fff;color:#07203a;padding:20px;border-radius:12px;max-width:520px;width:94%;}
.modal h3{margin-top:0;}
.modal label{display:block;margin-top:8px;font-weight:600;}
.modal input, .modal select{width:100%;padding:10px;margin-top:6px;border-radius:8px;border:1px solid #ddd;}
.top-actions{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}
.empty-msg{padding:28px;background:rgba(255,255,255,0.02);border-radius:10px;text-align:center;color:var(--muted);}
@media(max-width:700px){ .paycard-content{padding-left:44px;} .select-box{left:12px;} .actions{right:8px;} }
</style>
</head>
<body>

<header class="navbar">
  <div class="brand">SkillPilot — Admin</div>
  <div style="display:flex;gap:12px;align-items:center">
    <div style="color:var(--muted)"><strong>👤</strong> <?= htmlspecialchars($username) ?> (Admin)</div>
    <a href="admin_dashboard.php" class="btn">Dashboard</a>
    <a href="logout.php" class="btn" style="background:#f43f5e">Logout</a>
  </div>
</header>

<main class="container">
  <h2 style="margin-bottom:10px">💳 Payment History</h2>

  <div class="controls">
    <form method="GET" style="display:flex;gap:10px;align-items:center" class="searchbox">
      <input type="text" name="search" placeholder="Search by username..." value="<?= htmlspecialchars($search) ?>">
      <select name="filter">
        <option value="">-- Filter Status --</option>
        <option value="paid" <?= $filter==='paid'?'selected':'' ?>>Paid</option>
        <option value="pending" <?= $filter==='pending'?'selected':'' ?>>Pending</option>
      </select>
      <button type="submit" class="btn">🔍 Search</button>
    </form>

    <div style="margin-left:auto" class="bulk-actions">
      <button id="selectAllBtn" class="btn" style="background:#475569;padding:8px 10px">Select All</button>
      <button id="clearAllBtn" class="btn" style="background:#94a3b8;padding:8px 10px">Clear</button>
      <button id="bulkDeleteBtn" class="btn" style="background:#ef4444;display:none">🧹 Delete Selected</button>
      <button id="writeTotalsBtn" class="btn" style="background:#10b981;padding:8px 10px">💾 Write Totals to DB</button>
    </div>
  </div>

  <?php
  $count = 0;
  while($row = $result->fetch_assoc()):
    $count++;
    $display_amount = (float)$row['amount'];
    if($display_amount <= 0){
        $display_amount = compute_total_from_course_ids($conn, $row['course_ids']);
    }

    $course_names = [];
    if(!empty($row['course_ids'])){
        $parts = array_filter(array_map('intval', explode(',', $row['course_ids'])));
        if(!empty($parts)){
            $in = implode(',', $parts);
            $resC = $conn->query("SELECT course_name FROM courses WHERE id IN ($in)");
            if($resC){
                while($rC = $resC->fetch_assoc()) $course_names[] = $rC['course_name'];
                $resC->free();
            }
        }
    }
  ?>
  <div class="paycard" data-id="<?= (int)$row['id'] ?>">
    <input type="checkbox" class="select-box card-checkbox" data-id="<?= (int)$row['id'] ?>" />
    <div class="actions">
      <button class="edit-icon" title="Edit">✏️</button>
      <button class="delete-icon" title="Delete">🗑️</button>
    </div>

    <div class="paycard-content">
      <div class="header-line-1">Payment #<?= (int)$row['id'] ?></div>
      <div class="header-line-2">User: <?= htmlspecialchars($row['username']) ?></div>

      <div class="pay-details">
        <p><strong>Amount:</strong> ₹<?= number_format($display_amount,2) ?></p>
        <p><strong>Status:</strong>
          <?php if($row['status'] === 'paid'): ?>
            <span class="status-paid">Paid ✅</span>
          <?php else: ?>
            <span class="status-pending">Pending ⏳</span>
          <?php endif; ?>
        </p>

        <p><strong>Payment Method:</strong> <?= htmlspecialchars(ucfirst($row['payment_method'])) ?></p>

        <?php if($row['payment_method'] == 'upi'): ?>
            <p><strong>UPI ID:</strong> <?= htmlspecialchars($row['upi_id']) ?></p>
        <?php elseif($row['payment_method'] == 'card'): ?>
            <p><strong>Card Last 4 Digits:</strong> <?= htmlspecialchars($row['card_last4']) ?></p>
        <?php elseif($row['payment_method'] == 'netbanking'): ?>
            <p><strong>Bank User:</strong> <?= htmlspecialchars($row['netbank_user']) ?></p>
        <?php endif; ?>

        <?php if(!empty($course_names)): ?>
            <p><strong>Courses:</strong> <?= htmlspecialchars(implode(', ', $course_names)) ?></p>
        <?php endif; ?>

        <p><strong>Date:</strong> <?= htmlspecialchars($row['created_at']) ?></p>
      </div>
    </div>
  </div>
  <?php endwhile;
  if($count===0): ?>
    <div class="empty-msg">No payments found.</div>
  <?php endif; ?>
</main>

<!-- Edit Modal -->
<div class="modal" id="editModal">
  <div class="inner">
    <h3>Edit Payment</h3>
    <input type="hidden" id="modal_id">
    <label>Amount (₹)</label>
    <input type="number" step="0.01" id="modal_amount">

    <label>Status</label>
    <select id="modal_status">
      <option value="paid">Paid</option>
      <option value="pending">Pending</option>
    </select>

    <label>Payment Method</label>
    <select id="modal_method">
      <option value="upi">UPI</option>
      <option value="card">Card</option>
      <option value="netbanking">Netbanking</option>
    </select>

    <div id="modal_method_fields" style="margin-top:8px;">
      <label>UPI ID</label>
      <input type="text" id="modal_upi">

      <label>Card Last 4</label>
      <input type="text" id="modal_card">

      <label>Bank User</label>
      <input type="text" id="modal_bank">
    </div>

    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:12px;">
      <button id="modal_cancel" class="btn" style="background:#94a3b8">Cancel</button>
      <button id="modal_save" class="btn">Save</button>
    </div>
  </div>
</div>

<script>
$(function(){
  function updateBulkUI(){
    const sel = $('.card-checkbox:checked').length;
    if(sel > 0) $('#bulkDeleteBtn').show(); else $('#bulkDeleteBtn').hide();
  }

  $('#selectAllBtn').on('click', function(){
    $('.card-checkbox').prop('checked', true);
    updateBulkUI();
  });
  $('#clearAllBtn').on('click', function(){
    $('.card-checkbox').prop('checked', false);
    updateBulkUI();
  });
  $(document).on('change', '.card-checkbox', updateBulkUI);

  // Bulk delete
  $('#bulkDeleteBtn').on('click', function(){
    const ids = $('.card-checkbox:checked').map(function(){ return $(this).data('id'); }).get();
    if(ids.length===0) return;
    Swal.fire({
      title:'Are you sure?',
      text:'This will delete selected payments',
      icon:'warning',
      showCancelButton:true,
      confirmButtonText:'Yes, delete'
    }).then((res)=>{
      if(res.isConfirmed){
        $.post('<?= $_SERVER['PHP_SELF'] ?>', {action:'bulk_delete', ids:ids}, function(r){
          if(r.success) location.reload();
          else Swal.fire('Error', r.error, 'error');
        },'json');
      }
    });
  });

  // Write totals
  $('#writeTotalsBtn').on('click', function(){
    $.post('<?= $_SERVER['PHP_SELF'] ?>',{action:'write_totals'}, function(r){
      if(r.success) Swal.fire('Done', r.msg,'success').then(()=>location.reload());
      else Swal.fire('Error',r.error,'error');
    },'json');
  });

  // Edit modal
  $('.edit-icon').on('click', function(){
    const card = $(this).closest('.paycard');
    $('#modal_id').val(card.data('id'));
    const amt = card.find('.pay-details p strong:contains("Amount")').parent().text().replace(/[^0-9.]/g,'');
    $('#modal_amount').val(amt);

    const st = card.find('.pay-details .status-paid,.status-pending').text().includes('Paid') ? 'paid':'pending';
    $('#modal_status').val(st);

    const pm = card.find('.pay-details p strong:contains("Payment Method")').parent().text().split(':')[1].trim().toLowerCase();
    $('#modal_method').val(pm);
    $('#modal_upi').val(card.find('p:contains("UPI ID")').text().split(':')[1]?.trim() || '');
    $('#modal_card').val(card.find('p:contains("Card Last 4")').text().split(':')[1]?.trim() || '');
    $('#modal_bank').val(card.find('p:contains("Bank User")').text().split(':')[1]?.trim() || '');
    $('#editModal').fadeIn();
  });

  $('#modal_cancel').on('click', function(){ $('#editModal').fadeOut(); });

  $('#modal_save').on('click', function(){
    const data = {
      action:'edit',
      id:$('#modal_id').val(),
      amount:$('#modal_amount').val(),
      status:$('#modal_status').val(),
      payment_method:$('#modal_method').val(),
      upi_id:$('#modal_upi').val(),
      card_last4:$('#modal_card').val(),
      netbank_user:$('#modal_bank').val()
    };
    $.post('<?= $_SERVER['PHP_SELF'] ?>', data, function(r){
      if(r.success){ Swal.fire('Saved',r.msg,'success').then(()=>location.reload()); }
      else Swal.fire('Error',r.error,'error');
    },'json');
  });

  // Delete single
  $('.delete-icon').on('click', function(){
    const id = $(this).closest('.paycard').data('id');
    Swal.fire({
      title:'Are you sure?',
      text:'This will delete the payment',
      icon:'warning',
      showCancelButton:true,
      confirmButtonText:'Yes, delete'
    }).then((res)=>{
      if(res.isConfirmed){
        $.post('<?= $_SERVER['PHP_SELF'] ?>',{action:'delete',id:id}, function(r){
          if(r.success) location.reload();
          else Swal.fire('Error',r.error,'error');
        },'json');
      }
    });
  });

});
</script>

</body>
</html>
