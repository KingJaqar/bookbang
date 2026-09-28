<?php
session_start();
require_once __DIR__ . '/db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
$session_id = session_id();
$count = 0;

if($user_id){
    $stmt = $conn->prepare("SELECT SUM(quantity) AS count FROM carts WHERE user_id=?");
    $stmt->bind_param("i",$user_id);
}else{
    $stmt = $conn->prepare("SELECT SUM(quantity) AS count FROM carts WHERE session_id=?");
    $stmt->bind_param("s",$session_id);
}
$stmt->execute();
$res = $stmt->get_result();
if($row = $res->fetch_assoc()){
    $count = $row['count'] ?? 0;
}
$stmt->close();
echo json_encode(['count'=>$count]);
