<?php
/**
 * API สำหรับจัดการประวัติเครื่องจักร (mt_machine_history)
 * รองรับ: GET, POST, PUT, DELETE
 */

require_once '../config/config.php';
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($conn);
            break;
        case 'POST':
            handlePost($conn);
            break;
        case 'PUT':
            handlePut($conn);
            break;
        case 'DELETE':
            handleDelete($conn);
            break;
        default:
            http_response_code(405);
            json_response(false, 'Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(500);
    json_response(false, 'Server error: ' . $e->getMessage());
}

// ==================== GET ====================
function handleGet($conn) {
    // GET by ID
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT * FROM mt_machine_history WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            json_response(true, 'ดึงข้อมูลสำเร็จ', $data);
        } else {
            http_response_code(404);
            json_response(false, 'ไม่พบข้อมูล');
        }
        return;
    }
    
    // GET by machine_code
    if (isset($_GET['machine_code'])) {
        $machine_code = $_GET['machine_code'];
        $sql = "SELECT * FROM mt_machine_history WHERE machine_code = :machine_code ORDER BY work_date DESC, id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':machine_code', $machine_code);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        json_response(true, 'ดึงข้อมูลสำเร็จ', $data);
        return;
    }
    
    // GET with filters
    $sql = "SELECT * FROM mt_machine_history WHERE 1=1";
    $params = [];
    
    if (!empty($_GET['status'])) {
        $sql .= " AND status = :status";
        $params[':status'] = $_GET['status'];
    }
    
    if (!empty($_GET['date_from'])) {
        $sql .= " AND work_date >= :date_from";
        $params[':date_from'] = $_GET['date_from'];
    }
    
    if (!empty($_GET['date_to'])) {
        $sql .= " AND work_date <= :date_to";
        $params[':date_to'] = $_GET['date_to'];
    }
    
    if (!empty($_GET['branch'])) {
        $sql .= " AND branch = :branch";
        $params[':branch'] = $_GET['branch'];
    }
    
    $sql .= " ORDER BY work_date DESC, id DESC";
    
    // Limit
    if (isset($_GET['limit'])) {
        $sql .= " LIMIT " . intval($_GET['limit']);
    }
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    json_response(true, 'ดึงข้อมูลสำเร็จ', $data);
}

// ==================== POST (Create) ====================
function handlePost($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['machine_code'])) {
        http_response_code(400);
        json_response(false, 'กรุณาระบุรหัสเครื่องจักร');
        return;
    }
    
    // Auto-generate document_no ถ้าไม่ได้ส่งมา
    $document_no = $input['document_no'] ?? null;
    if (empty($document_no)) {
        // กำหนด prefix ตามประเภทงาน
        $work_type = strtoupper($input['work_type'] ?? 'PM');
        $prefix_map = [
            'PM' => 'PM',      // Preventive Maintenance
            'CAL' => 'CAL',    // Calibration
            'OVH' => 'OVH',    // Overhaul
            'INS' => 'INS'     // Inspection
        ];
        $prefix = isset($prefix_map[$work_type]) ? $prefix_map[$work_type] : 'HIS';
        
        // สร้างเลขที่เอกสาร รูปแบบ: PM001/68, CAL001/68, REP001/68
        $thai_year = date('Y') + 543; // แปลงเป็น พ.ศ.
        $year_2digit = substr($thai_year, -2); // เอาแค่ 2 หลักท้าย
        
        // หาเลขที่ล่าสุดของประเภทและปีนี้
        $sql_count = "SELECT COUNT(*) as count FROM mt_machine_history 
                      WHERE YEAR(work_date) = YEAR(NOW())
                      AND document_no LIKE :prefix";
        $stmt_count = $conn->prepare($sql_count);
        $prefix_pattern = $prefix . '%';
        $stmt_count->bindParam(':prefix', $prefix_pattern);
        $stmt_count->execute();
        $result = $stmt_count->fetch(PDO::FETCH_ASSOC);
        
        // เลขที่รันถัดไป (เริ่มจาก 001)
        $running_number = str_pad($result['count'] + 1, 3, '0', STR_PAD_LEFT);
        $document_no = $prefix . $running_number . '/' . $year_2digit;
    }
    
    $sql = "INSERT INTO mt_machine_history (
        machine_id, machine_code, machine_name, document_no,
        work_date, start_date, completed_date,
        issue_description, solution_description, parts_used,
        work_hours, downtime_hours, labor_cost, parts_cost, other_cost, total_cost,
        reported_by, handled_by, approved_by,
        status, priority, note, attachments,
        branch, department, created_by
    ) VALUES (
        :machine_id, :machine_code, :machine_name, :document_no,
        :work_date, :start_date, :completed_date,
        :issue_description, :solution_description, :parts_used,
        :work_hours, :downtime_hours, :labor_cost, :parts_cost, :other_cost, :total_cost,
        :reported_by, :handled_by, :approved_by,
        :status, :priority, :note, :attachments,
        :branch, :department, :created_by
    )";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    $stmt->bindValue(':machine_id', $input['machine_id'] ?? null);
    $stmt->bindValue(':machine_code', $input['machine_code']);
    $stmt->bindValue(':machine_name', $input['machine_name'] ?? null);
    $stmt->bindValue(':document_no', $document_no); // ใช้ตัวแปรที่ generate แล้ว
    $stmt->bindValue(':work_date', $input['work_date'] ?? null);
    $stmt->bindValue(':start_date', $input['start_date'] ?? null);
    $stmt->bindValue(':completed_date', $input['completed_date'] ?? null);
    $stmt->bindValue(':issue_description', $input['issue_description'] ?? null);
    $stmt->bindValue(':solution_description', $input['solution_description'] ?? null);
    $stmt->bindValue(':parts_used', $input['parts_used'] ?? null);
    $stmt->bindValue(':work_hours', $input['work_hours'] ?? 0);
    $stmt->bindValue(':downtime_hours', $input['downtime_hours'] ?? 0);
    $stmt->bindValue(':labor_cost', $input['labor_cost'] ?? 0);
    $stmt->bindValue(':parts_cost', $input['parts_cost'] ?? 0);
    $stmt->bindValue(':other_cost', $input['other_cost'] ?? 0);
    $stmt->bindValue(':total_cost', $input['total_cost'] ?? 0);
    $stmt->bindValue(':reported_by', $input['reported_by'] ?? null);
    $stmt->bindValue(':handled_by', $input['handled_by'] ?? null);
    $stmt->bindValue(':approved_by', $input['approved_by'] ?? null);
    $stmt->bindValue(':status', $input['status'] ?? 'completed');
    $stmt->bindValue(':priority', $input['priority'] ?? null);
    $stmt->bindValue(':note', $input['note'] ?? null);
    $stmt->bindValue(':attachments', $input['attachments'] ?? null);
    $stmt->bindValue(':branch', $input['branch'] ?? null);
    $stmt->bindValue(':department', $input['department'] ?? null);
    $stmt->bindValue(':created_by', $input['created_by'] ?? null);
    
    $stmt->execute();
    
    $newId = $conn->lastInsertId();
    json_response(true, 'บันทึกข้อมูลสำเร็จ', [
        'id' => $newId,
        'document_no' => $document_no
    ]);
}

// ==================== PUT (Update) ====================
function handlePut($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['id'])) {
        http_response_code(400);
        json_response(false, 'กรุณาระบุ ID');
        return;
    }
    
    $sql = "UPDATE mt_machine_history SET
        machine_id = :machine_id,
        machine_code = :machine_code,
        machine_name = :machine_name,
        document_no = :document_no,
        work_date = :work_date,
        start_date = :start_date,
        completed_date = :completed_date,
        issue_description = :issue_description,
        solution_description = :solution_description,
        parts_used = :parts_used,
        work_hours = :work_hours,
        downtime_hours = :downtime_hours,
        labor_cost = :labor_cost,
        parts_cost = :parts_cost,
        other_cost = :other_cost,
        total_cost = :total_cost,
        reported_by = :reported_by,
        handled_by = :handled_by,
        approved_by = :approved_by,
        status = :status,
        priority = :priority,
        note = :note,
        attachments = :attachments,
        branch = :branch,
        department = :department
        WHERE id = :id";
    
    $stmt = $conn->prepare($sql);
    
    $stmt->bindValue(':id', $input['id']);
    $stmt->bindValue(':machine_id', $input['machine_id'] ?? null);
    $stmt->bindValue(':machine_code', $input['machine_code']);
    $stmt->bindValue(':machine_name', $input['machine_name'] ?? null);
    $stmt->bindValue(':document_no', $input['document_no'] ?? null);
    $stmt->bindValue(':work_date', $input['work_date'] ?? null);
    $stmt->bindValue(':start_date', $input['start_date'] ?? null);
    $stmt->bindValue(':completed_date', $input['completed_date'] ?? null);
    $stmt->bindValue(':issue_description', $input['issue_description'] ?? null);
    $stmt->bindValue(':solution_description', $input['solution_description'] ?? null);
    $stmt->bindValue(':parts_used', $input['parts_used'] ?? null);
    $stmt->bindValue(':work_hours', $input['work_hours'] ?? 0);
    $stmt->bindValue(':downtime_hours', $input['downtime_hours'] ?? 0);
    $stmt->bindValue(':labor_cost', $input['labor_cost'] ?? 0);
    $stmt->bindValue(':parts_cost', $input['parts_cost'] ?? 0);
    $stmt->bindValue(':other_cost', $input['other_cost'] ?? 0);
    $stmt->bindValue(':total_cost', $input['total_cost'] ?? 0);
    $stmt->bindValue(':reported_by', $input['reported_by'] ?? null);
    $stmt->bindValue(':handled_by', $input['handled_by'] ?? null);
    $stmt->bindValue(':approved_by', $input['approved_by'] ?? null);
    $stmt->bindValue(':status', $input['status'] ?? 'completed');
    $stmt->bindValue(':priority', $input['priority'] ?? null);
    $stmt->bindValue(':note', $input['note'] ?? null);
    $stmt->bindValue(':attachments', $input['attachments'] ?? null);
    $stmt->bindValue(':branch', $input['branch'] ?? null);
    $stmt->bindValue(':department', $input['department'] ?? null);
    
    $stmt->execute();
    
    json_response(true, 'อัปเดตข้อมูลสำเร็จ');
}

// ==================== DELETE ====================
function handleDelete($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['id'])) {
        http_response_code(400);
        json_response(false, 'กรุณาระบุ ID');
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM mt_machine_history WHERE id = :id");
    $stmt->bindValue(':id', $input['id']);
    $stmt->execute();
    
    json_response(true, 'ลบข้อมูลสำเร็จ');
}
?>
