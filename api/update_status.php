<?php
require_once '../config/config.php';
require_once '../config/db.php';

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Validate input
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    json_response(false, 'Method not allowed');
}

$id             = filter_input(INPUT_POST, 'id',     FILTER_VALIDATE_INT);
$status         = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT);
$handled_by_id  = (int)($_POST['handled_by_id'] ?? 0) ?: null;
$mt_report      = sanitize_input($_POST['mt_report']      ?? '');
$receiver_name  = sanitize_input($_POST['receiver_name']   ?? '');
$job_status     = sanitize_input($_POST['job_status']      ?? 'complete');
$job_status_note = ($job_status === 'other') ? sanitize_input($_POST['job_status_note'] ?? '') : '';

if (!$id || $status === null || $status === false) {
    http_response_code(400);
    json_response(false, 'ข้อมูลไม่ครบถ้วน (id=' . $id . ', status=' . $status . ')');
}

if (!in_array($status, [STATUS_PENDING_APPROVAL, STATUS_PENDING, STATUS_WAITING_PARTS, STATUS_COMPLETED, STATUS_CANCELLED])) {
    http_response_code(400);
    json_response(false, 'สถานะไม่ถูกต้อง');
}

if ($status == STATUS_COMPLETED && !$handled_by_id) {
    http_response_code(400);
    json_response(false, 'กรุณาเลือกช่างผู้ดำเนินการ');
}

$end_job = ($status == STATUS_COMPLETED) ? date('Y-m-d H:i:s') : null;
$image_after = '';

// Handle file upload (รูปหลังซ่อม) - เฉพาะสถานะเสร็จสิ้น
if ($status == STATUS_COMPLETED && isset($_FILES['image_after']) && $_FILES['image_after']['error'] === UPLOAD_ERR_OK) {
    $upload_base_dir = '../uploads/';
    $month_folder = date('Y-m');
    $upload_dir = $upload_base_dir . $month_folder . '/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    $file_type = $_FILES['image_after']['type'];
    $file_size = $_FILES['image_after']['size'];
    
    if (!in_array($file_type, $allowed_types)) {
        http_response_code(400);
        json_response(false, 'ไฟล์ต้องเป็น JPG, PNG หรือ GIF เท่านั้น');
    }
    
    if ($file_size > $max_size) {
        http_response_code(400);
        json_response(false, 'ขนาดไฟล์ต้องไม่เกิน 5MB');
    }
    
    // Generate filename: after_0001.jpg
    $file_ext = pathinfo($_FILES['image_after']['name'], PATHINFO_EXTENSION);
    $new_filename = 'after_' . str_pad($id, 4, '0', STR_PAD_LEFT) . '.' . $file_ext;
    $upload_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($_FILES['image_after']['tmp_name'], $upload_path)) {
        $image_after = 'uploads/' . $month_folder . '/' . $new_filename;
    }
}

try {
    $completed_at = ($status == STATUS_COMPLETED) ? date('Y-m-d H:i:s') : null;

    $sql = "UPDATE mt_repair SET status = :status, updated_at = NOW()";

    if ($status == STATUS_COMPLETED) {
        $sql .= ", completed_at = :completed_at";
        $sql .= ", handled_by_id = :handled_by_id";
        $sql .= ", mt_report = :mt_report";
        $sql .= ", receiver_name = :receiver_name";
        $sql .= ", job_status_note = :job_status_note";
        if (!empty($image_after)) {
            $sql .= ", image_after = :image_after";
        }
    }

    $sql .= " WHERE id = :id";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':status', $status, PDO::PARAM_INT);
    $stmt->bindParam(':id',     $id,     PDO::PARAM_INT);

    if ($status == STATUS_COMPLETED) {
        $stmt->bindValue(':completed_at',   $completed_at);
        $stmt->bindValue(':handled_by_id',  $handled_by_id, PDO::PARAM_INT);
        $stmt->bindValue(':mt_report',      $mt_report);
        $stmt->bindValue(':receiver_name',  $receiver_name);
        $stmt->bindValue(':job_status_note', $job_status_note);
        if (!empty($image_after)) {
            $stmt->bindParam(':image_after', $image_after);
        }
    }

    $stmt->execute();

    // Get status name for response message
    $statusNames = [
        STATUS_PENDING_APPROVAL => 'รออนุมัติ',
        STATUS_PENDING => 'รอดำเนินการ',
        STATUS_WAITING_PARTS => 'รออะไหล่',
        STATUS_COMPLETED => 'ซ่อมเสร็จสิ้น'
    ];
    
    json_response(true, 'อัพเดทสถานะเป็น "' . $statusNames[$status] . '" เรียบร้อย');
} catch (PDOException $e) {
    http_response_code(500);
    json_response(false, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
}
?>
