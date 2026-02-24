<?php
/**
 * API: get_all_repairs.php  (v3 - uses v_repair_full view)
 * Supports GET filters: machine, dept, status, priority, document_no, date
 */
require_once '../config/config.php';
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // รับ filter จาก GET (repair_form.js ส่งแบบ GET) หรือ POST body
    $filters = array_merge($_GET, $_POST);
    // รองรับ JSON body เดิมด้วย
    $json = file_get_contents('php://input');
    if ($json) {
        $decoded = json_decode($json, true);
        if (is_array($decoded)) $filters = array_merge($filters, $decoded);
    }

    $sql    = "SELECT * FROM v_repair_full WHERE 1=1";
    $params = [];

    // machine code หรือชื่อเครื่อง
    if (!empty($filters['machine'])) {
        $sql .= " AND (machine_code LIKE :machine OR machine_name LIKE :machine2)";
        $params[':machine']  = '%' . $filters['machine'] . '%';
        $params[':machine2'] = '%' . $filters['machine'] . '%';
    }

    // หน่วยงาน
    if (!empty($filters['dept'])) {
        $sql .= " AND department_name LIKE :dept";
        $params[':dept'] = '%' . $filters['dept'] . '%';
    }

    // สถานะ
    if (!empty($filters['status'])) {
        $sql .= " AND status = :status";
        $params[':status'] = (int)$filters['status'];
    }

    // ความเร่งด่วน
    if (!empty($filters['priority'])) {
        $sql .= " AND priority = :priority";
        $params[':priority'] = $filters['priority'];
    }

    // เลขที่เอกสาร
    if (!empty($filters['document_no'])) {
        $sql .= " AND document_no LIKE :doc_no";
        $params[':doc_no'] = '%' . $filters['document_no'] . '%';
    }

    // วันที่แจ้ง
    if (!empty($filters['repair_date'])) {
        $sql .= " AND DATE(start_job) = :repair_date";
        $params[':repair_date'] = $filters['repair_date'];
    }

    // สาขา id
    if (!empty($filters['branch_id'])) {
        $sql .= " AND branch_id = :branch_id";
        $params[':branch_id'] = (int)$filters['branch_id'];
    }

    $sql .= " ORDER BY start_job DESC LIMIT 500";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_response(true, 'ดึงข้อมูลสำเร็จ', $data);

} catch (PDOException $e) {
    http_response_code(500);
    json_response(false, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
}
?>
