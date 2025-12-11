<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once 'sync_repair_to_history.php'; // เพิ่ม auto-sync

header('Content-Type: application/json; charset=utf-8');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    json_response(false, 'Method not allowed');
}

// Get and validate input
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$status = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT);
$handled_by = sanitize_input($_POST['handled_by'] ?? '');

if (!$id || $status === null || $status === false) {
    http_response_code(400);
    json_response(false, 'ข้อมูลไม่ครบถ้วน');
}

// Validate status value
if (!in_array($status, [STATUS_PENDING, STATUS_COMPLETED, STATUS_WAITING_PARTS])) {
    http_response_code(400);
    json_response(false, 'สถานะไม่ถูกต้อง');
}

// ถ้าเป็นสถานะเสร็จสิ้น ต้องมีผู้ดำเนินการ
if ($status == STATUS_COMPLETED && empty($handled_by)) {
    http_response_code(400);
    json_response(false, 'กรุณาระบุชื่อผู้ดำเนินการ');
}

// Determine end_job based on status
$end_job = null;
if ($status == STATUS_COMPLETED) {
    // ถ้าสถานะเป็น "ซ่อมเสร็จแล้ว" ให้บันทึกเวลาที่เสร็จ
    $end_job = date('Y-m-d H:i:s');
} else {
    // ถ้าเป็นสถานะอื่น ให้เคลียร์เวลาเสร็จ
    $end_job = null;
}

try {
    // Update status
    $sql = "UPDATE mt_repair SET status = :status, end_job = :end_job";
    
    // อัพเดท handled_by ถ้าเป็นสถานะเสร็จสิ้น
    if ($status == STATUS_COMPLETED) {
        $sql .= ", handled_by = :handled_by";
    }
    
    $sql .= " WHERE id = :id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':status', $status, PDO::PARAM_INT);
    $stmt->bindParam(':end_job', $end_job);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    if ($status == STATUS_COMPLETED) {
        $stmt->bindParam(':handled_by', $handled_by);
    }
    
    $stmt->execute();
    
    // 🔥 Auto-sync to machine history when completed (status = 40)
    if ($status == STATUS_COMPLETED) {
        $syncResult = syncRepairToHistory($id, $conn);
        if (!$syncResult) {
            error_log("Warning: Failed to sync repair ID $id to machine history");
        }
    }
    
    // Get status name for response
    $statusNames = [
        STATUS_PENDING => 'รอดำเนินการ',
        STATUS_COMPLETED => 'ซ่อมเสร็จแล้ว',
        STATUS_WAITING_PARTS => 'รออะไหล่'
    ];
    
    json_response(true, 'อัพเดทสถานะเป็น "' . $statusNames[$status] . '" เรียบร้อย');
    
} catch (PDOException $e) {
    http_response_code(500);
    json_response(false, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
}
?>
