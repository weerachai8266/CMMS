<?php
require_once '../config/config.php';
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    json_response(false, 'Method not allowed');
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['id']) || empty($data['id'])) {
    http_response_code(400);
    json_response(false, 'ไม่พบ ID');
}

$id = intval($data['id']);

try {
    $sql = "DELETE FROM mt_repair WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        json_response(true, 'ลบข้อมูลเรียบร้อย');
    } else {
        json_response(false, 'ไม่พบข้อมูลที่ต้องการลบ');
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    json_response(false, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
}
?>
