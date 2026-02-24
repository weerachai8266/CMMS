<?php
/**
 * API: save_repair.php  (v3 - ID-based, fully normalized)
 * บันทึกใบแจ้งซ่อมใหม่ - รับเฉพาะ ID ทุกฟิลด์
 */

require_once '../config/config.php';
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    json_response(false, 'Method not allowed');
}

// ---------- รับค่าจากฟอร์ม ----------
$branch_id        = (int)($_POST['branch_id']        ?? 0);
$division_id      = (int)($_POST['division_id']      ?? 0) ?: null;
$department_id    = (int)($_POST['department_id']    ?? 0) ?: null;
$machine_id       = (int)($_POST['machine_id']       ?? 0);
$issue_id         = (int)($_POST['issue_id']         ?? 0) ?: null;
$issue_detail     = sanitize_input($_POST['issue_detail']     ?? '');
$action_type_id   = (int)($_POST['action_type_id']   ?? 0) ?: null;
$action_detail    = sanitize_input($_POST['action_detail']    ?? '');
$priority         = in_array($_POST['priority'] ?? '', ['urgent','normal']) ? $_POST['priority'] : 'urgent';
$reported_by_id   = (int)($_POST['reported_by_id']   ?? 0) ?: null;
$reported_by_name = sanitize_input($_POST['reported_by_name'] ?? '');

// ---------- Validate required ----------
$errors = [];
if ($branch_id  <= 0) $errors[] = 'กรุณาเลือกสาขา';
if ($machine_id <= 0) $errors[] = 'กรุณาเลือกเครื่องจักร';
if ($issue_id === null && empty($issue_detail)) $errors[] = 'กรุณาระบุอาการเสีย';
if ($reported_by_id === null && empty($reported_by_name)) $errors[] = 'กรุณาระบุผู้แจ้ง';

if (!empty($errors)) {
    http_response_code(400);
    json_response(false, implode(', ', $errors));
}

// ---------- รูปภาพ ----------
$temp_image_file = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
    $max_size      = 5 * 1024 * 1024;

    if (!in_array($_FILES['image']['type'], $allowed_types)) {
        http_response_code(400);
        json_response(false, 'ไฟล์รูปต้องเป็น JPG, PNG, GIF หรือ WEBP');
    }
    if ($_FILES['image']['size'] > $max_size) {
        http_response_code(400);
        json_response(false, 'ขนาดไฟล์รูปต้องไม่เกิน 5 MB');
    }

    $temp_image_file = [
        'tmp_name'  => $_FILES['image']['tmp_name'],
        'extension' => strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION)),
    ];
}

try {
    // ดึง code จาก branch
    $stmt = $conn->prepare("SELECT code FROM mt_branches WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $branch_id]);
    $branch_row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$branch_row) {
        http_response_code(400);
        json_response(false, 'ไม่พบสาขาที่เลือก');
    }
    $branch_code = $branch_row['code'];

    // สร้างเลขที่เอกสาร: ACP001/68
    $thai_year  = (int)date('Y') + 543;
    $year_2     = substr((string)$thai_year, -2);

    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM mt_repair
        WHERE branch_id = :bid AND YEAR(start_job) = YEAR(NOW())
    ");
    $stmt->execute([':bid' => $branch_id]);
    $cnt        = (int)$stmt->fetchColumn();
    $running_no = str_pad($cnt + 1, 3, '0', STR_PAD_LEFT);
    $document_no = $branch_code . $running_no . '/' . $year_2;

    // INSERT
    $stmt = $conn->prepare("
        INSERT INTO mt_repair
            (document_no, branch_id, division_id, department_id,
             machine_id, issue_id, issue_detail,
             action_type_id, action_detail,
             priority, reported_by_id, reported_by_name,
             status, start_job)
        VALUES
            (:doc_no, :branch_id, :division_id, :department_id,
             :machine_id, :issue_id, :issue_detail,
             :action_type_id, :action_detail,
             :priority, :reported_by_id, :reported_by_name,
             :status, NOW())
    ");
    $stmt->execute([
        ':doc_no'           => $document_no,
        ':branch_id'        => $branch_id,
        ':division_id'      => $division_id,
        ':department_id'    => $department_id,
        ':machine_id'       => $machine_id,
        ':issue_id'         => $issue_id,
        ':issue_detail'     => $issue_detail ?: null,
        ':action_type_id'   => $action_type_id,
        ':action_detail'    => $action_detail ?: null,
        ':priority'         => $priority,
        ':reported_by_id'   => $reported_by_id,
        ':reported_by_name' => $reported_by_name ?: null,
        ':status'           => STATUS_PENDING_APPROVAL,
    ]);

    $last_id = (int)$conn->lastInsertId();

    // อัปโหลดรูป
    if ($temp_image_file !== null) {
        $month_folder = date('Y-m');
        $upload_dir   = '../uploads/' . $month_folder . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true);
        }
        $filename   = 'before_' . str_pad($last_id, 5, '0', STR_PAD_LEFT) . '.' . $temp_image_file['extension'];
        $saved_path = 'uploads/' . $month_folder . '/' . $filename;

        if (move_uploaded_file($temp_image_file['tmp_name'], $upload_dir . $filename)) {
            $u = $conn->prepare("UPDATE mt_repair SET image_before = :img WHERE id = :id");
            $u->execute([':img' => $saved_path, ':id' => $last_id]);
        }
    }

    json_response(true, 'บันทึกใบแจ้งซ่อมเรียบร้อย เลขที่: ' . $document_no, [
        'id'          => $last_id,
        'document_no' => $document_no,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    json_response(false, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
}
