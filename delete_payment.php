<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

// Only admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    echo json_encode(['success'=>false,'error'=>'Unauthorized']);
    exit;
}

// Single delete
if(isset($_POST['id'])){
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM payments WHERE id=?");
    $stmt->bind_param("i",$id);
    $success = $stmt->execute();
    $stmt->close();
    echo json_encode(['success'=>$success]);
    exit;
}

// Bulk delete
if(isset($_POST['ids'])){
    $ids = $_POST['ids'];
    if(!is_array($ids) || empty($ids)){
        echo json_encode(['success'=>false,'error'=>'No IDs selected']);
        exit;
    }
    $placeholders = implode(',', array_fill(0,count($ids),'?'));
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare("DELETE FROM payments WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    $success = $stmt->execute();
    $stmt->close();
    echo json_encode(['success'=>$success]);
    exit;
}

echo json_encode(['success'=>false,'error'=>'No action specified']);
