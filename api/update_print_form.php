<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once 'sync_repair_to_history.php';

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    json_response(false, 'Method not allowed');
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate input
if (!$data || !isset($data['id'])) {
    http_response_code(400);
    json_response(false, 'Invalid input data');
}

$id = intval($data['id']);
$section = intval($data['section'] ?? 0); // 2 = Section 2, 3 = Section 3, 0 = All

// Helper function to validate and sanitize date (already in YYYY-MM-DD format from input type="date")
function validateDate($dateStr) {
    if (empty($dateStr)) return null;
    // Validate format YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
        return $dateStr;
    }
    return null;
}

// Helper function to validate and sanitize time (already in HH:MM format from input type="time")
function validateTime($timeStr) {
    if (empty($timeStr)) return null;
    // Validate format HH:MM
    if (preg_match('/^\d{2}:\d{2}$/', $timeStr)) {
        return $timeStr . ':00'; // Add seconds
    }
    return null;
}

try {
    // Build SQL based on section
    $sql = "UPDATE mt_repair SET ";
    $fields = [];
    $params = [':id' => $id];
    
    // Section 2 fields
    if ($section == 2 || $section == 0) {
        $receive_date = validateDate(sanitize_input($data['receive_date'] ?? ''));
        $receive_time = validateTime(sanitize_input($data['receive_time'] ?? ''));
        $receiver_mt = strtoupper(sanitize_input($data['receiver_mt'] ?? ''));
        $mtc_comment = sanitize_input($data['mtc_comment'] ?? '');
        $mtc_signer = strtoupper(sanitize_input($data['mtc_signer'] ?? ''));
        $mtc_date = validateDate(sanitize_input($data['mtc_date'] ?? ''));
        
        $fields[] = "receive_date = :receive_date";
        $fields[] = "receive_time = :receive_time";
        $fields[] = "receiver_mt = :receiver_mt";
        $fields[] = "mtc_comment = :mtc_comment";
        $fields[] = "mtc_signer = :mtc_signer";
        $fields[] = "mtc_date = :mtc_date";
        
        $params[':receive_date'] = $receive_date;
        $params[':receive_time'] = $receive_time;
        $params[':receiver_mt'] = $receiver_mt;
        $params[':mtc_comment'] = $mtc_comment;
        $params[':mtc_signer'] = $mtc_signer;
        $params[':mtc_date'] = $mtc_date;
    }
    
    // Section 3 fields
    if ($section == 3 || $section == 0) {
        $mt_report = sanitize_input($data['mt_report'] ?? '');
        $operation_type = sanitize_input($data['operation_type'] ?? '');
        $operation_detail = sanitize_input($data['operation_detail'] ?? '');
        $worker_count = !empty($data['worker_count']) ? intval($data['worker_count']) : null;
        $work_hours = !empty($data['work_hours']) ? floatval($data['work_hours']) : null;
        $downtime_hours = !empty($data['downtime_hours']) ? floatval($data['downtime_hours']) : null;
        $start_date = validateDate(sanitize_input($data['start_date'] ?? ''));
        $start_time = validateTime(sanitize_input($data['start_time'] ?? ''));
        $end_date = validateDate(sanitize_input($data['end_date'] ?? ''));
        $end_time = validateTime(sanitize_input($data['end_time'] ?? ''));
        $total_cost = !empty($data['total_cost']) ? floatval(str_replace(',', '', $data['total_cost'])) : null;
        $registry_date = validateDate(sanitize_input($data['registry_date'] ?? ''));
        $registry_signer = strtoupper(sanitize_input($data['registry_signer'] ?? ''));
        $mtc_manager = strtoupper(sanitize_input($data['mtc_manager'] ?? ''));
        
        $fields[] = "mt_report = :mt_report";
        $fields[] = "operation_type = :operation_type";
        $fields[] = "operation_detail = :operation_detail";
        $fields[] = "worker_count = :worker_count";
        $fields[] = "work_hours = :work_hours";
        $fields[] = "downtime_hours = :downtime_hours";
        $fields[] = "start_date = :start_date";
        $fields[] = "start_time = :start_time";
        $fields[] = "end_date = :end_date";
        $fields[] = "end_time = :end_time";
        $fields[] = "total_cost = :total_cost";
        $fields[] = "registry_date = :registry_date";
        $fields[] = "registry_signer = :registry_signer";
        $fields[] = "mtc_manager = :mtc_manager";
        
        $params[':mt_report'] = $mt_report;
        $params[':operation_type'] = $operation_type;
        $params[':operation_detail'] = $operation_detail;
        $params[':worker_count'] = $worker_count;
        $params[':work_hours'] = $work_hours;
        $params[':downtime_hours'] = $downtime_hours;
        $params[':start_date'] = $start_date;
        $params[':start_time'] = $start_time;
        $params[':end_date'] = $end_date;
        $params[':end_time'] = $end_time;
        $params[':total_cost'] = $total_cost;
        $params[':registry_date'] = $registry_date;
        $params[':registry_signer'] = $registry_signer;
        $params[':mtc_manager'] = $mtc_manager;
    }
    
    if (empty($fields)) {
        http_response_code(400);
        json_response(false, 'ไม่มีข้อมูลที่ต้องบันทึก');
    }
    
    $sql .= implode(', ', $fields) . " WHERE id = :id";
    
    $stmt = $conn->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    
    // Check if any row was affected
    if ($stmt->rowCount() > 0) {
        // ตรวจสอบสถานะ และ sync ไปยัง history ถ้าเป็นงานเสร็จสิ้น
        $checkStatusSql = "SELECT status FROM mt_repair WHERE id = :id";
        $checkStmt = $conn->prepare($checkStatusSql);
        $checkStmt->bindParam(':id', $id);
        $checkStmt->execute();
        $repairData = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($repairData && $repairData['status'] == STATUS_COMPLETED) {
            syncRepairToHistory($id, $conn);
        }
        
        json_response(true, 'บันทึกข้อมูลเรียบร้อยแล้ว', ['id' => $id]);
    } else {
        json_response(true, 'ไม่มีการเปลี่ยนแปลงข้อมูล', ['id' => $id]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    json_response(false, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
}
?>
