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

$division = sanitize_input($_POST['division'] ?? '');
$department = sanitize_input($_POST['department'] ?? '');
$branch = strtoupper(sanitize_input($_POST['branch'] ?? ''));
$machine_number = strtoupper(sanitize_input($_POST['machine_number'] ?? ''));
$issue = sanitize_input($_POST['issue'] ?? '');
$reported_by = sanitize_input($_POST['reported_by'] ?? '');

// โปรดดำเนินการ (radio button)
$action_type = sanitize_input($_POST['action_type'] ?? 'repair');
$action_other_text = ($action_type === 'other') ? sanitize_input($_POST['action_other_text'] ?? '') : '';

// ความเร่งด่วน
$priority = sanitize_input($_POST['priority'] ?? 'urgent');

$handled_by = ''; // จะกรอกตอนกดเสร็จสิ้น
$mt_report = ''; // ให้ MT กรอกทีหลัง
$status = STATUS_PENDING_APPROVAL; // Default status (10 = รออนุมัติ)
$image_before = ''; // รูปก่อนซ่อม
$image_after = ''; // รูปหลังซ่อม
$temp_image_file = null; // เก็บไฟล์ชั่วคราว

// Validate required fields
if (empty($division) || empty($department) || empty($branch) || empty($machine_number) || empty($issue) || empty($reported_by)) {
    http_response_code(400);
    json_response(false, 'กรุณากรอกข้อมูลให้ครบถ้วน');
}

// Handle file upload (รูปก่อนซ่อม) - บันทึกชั่วคราวก่อน
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    $file_type = $_FILES['image']['type'];
    $file_size = $_FILES['image']['size'];
    
    // Validate file type
    if (!in_array($file_type, $allowed_types)) {
        http_response_code(400);
        json_response(false, 'ไฟล์ต้องเป็น JPG, PNG หรือ GIF เท่านั้น');
    }
    
    // Validate file size
    if ($file_size > $max_size) {
        http_response_code(400);
        json_response(false, 'ขนาดไฟล์ต้องไม่เกิน 5MB');
    }
    
    // เก็บไฟล์ชั่วคราว
    $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $temp_image_file = [
        'tmp_name' => $_FILES['image']['tmp_name'],
        'extension' => $file_ext
    ];
}

try {
    // Lookup IDs จาก master tables ตามชื่อที่ส่งมา
    $division_id = null;
    $department_id = null;
    $department_group_id = null;
    $branch_id = null;

    $r = $conn->prepare("SELECT id FROM mt_divisions WHERE name = :name LIMIT 1");
    $r->execute([':name' => $division]);
    $row = $r->fetch(PDO::FETCH_ASSOC);
    if ($row) $division_id = (int)$row['id'];

    $r = $conn->prepare("SELECT id, group_id FROM mt_departments WHERE name = :name LIMIT 1");
    $r->execute([':name' => $department]);
    $row = $r->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $department_id = (int)$row['id'];
        $department_group_id = $row['group_id'] ? (int)$row['group_id'] : null;
    }

    $r = $conn->prepare("SELECT id FROM mt_branches WHERE name = :name LIMIT 1");
    $r->execute([':name' => $branch]);
    $row = $r->fetch(PDO::FETCH_ASSOC);
    if ($row) $branch_id = (int)$row['id'];

    // สร้างเลขที่เอกสาร รูปแบบ: ACP001/68 (สาขา + เลข 3 หลัก + / + ปี 2 หลัก)
    $thai_year = date('Y') + 543; // แปลงเป็น พ.ศ.
    $year_2digit = substr($thai_year, -2); // เอาแค่ 2 หลักท้าย (2568 -> 68)
    
    // หาเลขที่ล่าสุดของสาขาและปีนี้
    $sql_count = "SELECT COUNT(*) as count FROM mt_repair 
                  WHERE branch = :branch 
                  AND YEAR(start_job) = YEAR(NOW())";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bindParam(':branch', $branch);
    $stmt_count->execute();
    $result = $stmt_count->fetch(PDO::FETCH_ASSOC);
    
    // เลขที่รันถัดไป (เริ่มจาก 001)
    $running_number = str_pad($result['count'] + 1, 3, '0', STR_PAD_LEFT);
    $document_no = $branch . $running_number . '/' . $year_2digit;
    
    // Use prepared statement to prevent SQL injection
    $sql = "INSERT INTO mt_repair (division, division_id, department, department_id, department_group_id, branch, branch_id, document_no, machine_number, issue, 
            action_type, action_other_text, priority,
            image_before, image_after, reported_by, handled_by, mt_report, status) 
            VALUES (:division, :division_id, :department, :department_id, :department_group_id, :branch, :branch_id, :document_no, :machine_number, :issue, 
            :action_type, :action_other_text, :priority,
            :image_before, :image_after, :reported_by, :handled_by, :mt_report, :status)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':division', $division);
    $stmt->bindParam(':division_id', $division_id, PDO::PARAM_INT);
    $stmt->bindParam(':department', $department);
    $stmt->bindParam(':department_id', $department_id, PDO::PARAM_INT);
    $stmt->bindParam(':department_group_id', $department_group_id, PDO::PARAM_INT);
    $stmt->bindParam(':branch', $branch);
    $stmt->bindParam(':branch_id', $branch_id, PDO::PARAM_INT);
    $stmt->bindParam(':document_no', $document_no);
    $stmt->bindParam(':machine_number', $machine_number);
    $stmt->bindParam(':issue', $issue);
    $stmt->bindParam(':action_type', $action_type);
    $stmt->bindParam(':action_other_text', $action_other_text);
    $stmt->bindParam(':priority', $priority);
    $stmt->bindParam(':image_before', $image_before);
    $stmt->bindParam(':image_after', $image_after);
    $stmt->bindParam(':reported_by', $reported_by);
    $stmt->bindParam(':handled_by', $handled_by);
    $stmt->bindParam(':mt_report', $mt_report);
    $stmt->bindParam(':status', $status);
    
    $stmt->execute();
    
    // Get last insert ID
    $last_id = $conn->lastInsertId();
    
    // Upload รูปภาพด้วย ID ที่ได้
    if ($temp_image_file !== null) {
        $upload_base_dir = '../uploads/';
        $month_folder = date('Y-m');
        $upload_dir = $upload_base_dir . $month_folder . '/';
        
        // สร้างโฟลเดอร์เดือนถ้ายังไม่มี
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate filename: before_0001.jpg
        $new_filename = 'before_' . str_pad($last_id, 4, '0', STR_PAD_LEFT) . '.' . $temp_image_file['extension'];
        $upload_path = $upload_dir . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($temp_image_file['tmp_name'], $upload_path)) {
            $image_before = 'uploads/' . $month_folder . '/' . $new_filename;
            
            // Update image path in database
            $update_sql = "UPDATE mt_repair SET image_before = :image_before WHERE id = :id";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bindParam(':image_before', $image_before);
            $update_stmt->bindParam(':id', $last_id, PDO::PARAM_INT);
            $update_stmt->execute();
        }
    }
    
    json_response(true, 'บันทึกข้อมูลเรียบร้อย เลขที่: ' . $document_no, ['id' => $last_id, 'document_no' => $document_no]);
} catch (PDOException $e) {
    http_response_code(500);
    json_response(false, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
}
?>
