<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

// Only admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    echo json_encode(['success'=>false,'error'=>'Unauthorized']);
    exit;
}

// Get POST data
$id = $_POST['id'] ?? '';
$amount = $_POST['amount'] ?? '';
$status = $_POST['status'] ?? '';
$payment_method = $_POST['payment_method'] ?? '';
$upi_id = $_POST['upi_id'] ?? '';
$card_last4 = $_POST['card_last4'] ?? '';
$netbank_user = $_POST['netbank_user'] ?? '';

if(!$id || !$status || !$payment_method){
    echo json_encode(['success'=>false,'error'=>'Missing required fields']);
    exit;
}

// Prepare SQL
$stmt = $conn->prepare("UPDATE payments SET amount=?, status=?, payment_method=?, upi_id=?, card_last4=?, netbank_user=? WHERE id=?");
$stmt->bind_param("dsssssi",$amount,$status,$payment_method,$upi_id,$card_last4,$netbank_user,$id);

if($stmt->execute()){
    echo json_encode(['success'=>true]);
}else{
    echo json_encode(['success'=>false,'error'=>$stmt->error]);
}
$stmt->close();
$conn->close();
