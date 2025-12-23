<?php
require_once '../config/config.php';
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

// ตรวจสอบ POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    json_response(false, 'Method not allowed');
}

// รับข้อมูล
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$approver = sanitize_input($_POST['approver'] ?? '');
$reason = sanitize_input($_POST['reason'] ?? '');

// รับข้อมูลอุปกรณ์ที่ใช้ปฏิเสธ
$device_type = sanitize_input($_POST['device_type'] ?? null);
$browser = sanitize_input($_POST['browser'] ?? null);
$os = sanitize_input($_POST['os'] ?? null);
$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;

if (!$id || empty($approver) || empty($reason)) {
    http_response_code(400);
    json_response(false, 'ข้อมูลไม่ครบถ้วน กรุณาระบุเหตุผลในการปฏิเสธ');
}

try {
    // ตรวจสอบว่ามีรายการนี้และเป็นสถานะ 10 (รออนุมัติ) หรือไม่
    $checkSql = "SELECT id, document_no, machine_number, issue, reported_by FROM mt_repair WHERE id = :id AND status = :status";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bindValue(':id', $id, PDO::PARAM_INT);
    $checkStmt->bindValue(':status', STATUS_PENDING_APPROVAL, PDO::PARAM_INT);
    $checkStmt->execute();
    $repair = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$repair) {
        http_response_code(404);
        json_response(false, 'ไม่พบรายการที่รออนุมัติ');
    }
    
    // เริ่ม transaction
    $conn->beginTransaction();
    
    // อัปเดตสถานะเป็น 11 (ไม่อนุมัติ)
    $updateSql = "UPDATE mt_repair 
                  SET status = :new_status, 
                      approver = :approver, 
                      approved_at = NOW(),
                      reject_reason = :reason
                  WHERE id = :id";
    
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bindValue(':new_status', STATUS_REJECTED, PDO::PARAM_INT);
    $updateStmt->bindValue(':approver', $approver);
    $updateStmt->bindValue(':reason', $reason);
    $updateStmt->bindValue(':id', $id, PDO::PARAM_INT);
    $updateStmt->execute();
    
    // บันทึกประวัติการปฏิเสธพร้อมข้อมูลอุปกรณ์
    try {
        $logSql = "INSERT INTO mt_approval_log (repair_id, approver, action, reason, device_type, browser, os, ip_address) 
                   VALUES (:repair_id, :approver, 'rejected', :reason, :device_type, :browser, :os, :ip_address)";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bindValue(':repair_id', $id, PDO::PARAM_INT);
        $logStmt->bindValue(':approver', $approver);
        $logStmt->bindValue(':reason', $reason);
        $logStmt->bindValue(':device_type', $device_type);
        $logStmt->bindValue(':browser', $browser);
        $logStmt->bindValue(':os', $os);
        $logStmt->bindValue(':ip_address', $ip_address);
        $logStmt->execute();
    } catch (PDOException $e) {
        // ถ้าตารางยังไม่มีก็ข้าม ไม่ rollback
    }
    
    $conn->commit();
    
    json_response(true, 'ปฏิเสธใบแจ้งซ่อมเรียบร้อย', [
        'id' => $id,
        'document_no' => $repair['document_no'],
        'new_status' => STATUS_REJECTED,
        'approver' => $approver,
        'reason' => $reason
    ]);
    
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    json_response(false, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
}
?>
