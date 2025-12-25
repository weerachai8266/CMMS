<?php
require_once '../config/config.php';
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $filters = json_decode($input, true);
    
    // Build SQL query
    $sql = "SELECT * FROM mt_repair WHERE 1=1";
    $params = [];
    
    // Apply filters
    if (!empty($filters['repair_date'])) {
        $sql .= " AND DATE(start_job) = :repair_date";
        $params[':repair_date'] = $filters['repair_date'];
    }
    
    if (!empty($filters['document_no'])) {
        $sql .= " AND document_no LIKE :document_no";
        $params[':document_no'] = '%' . $filters['document_no'] . '%';
    }
    
    if (!empty($filters['status'])) {
        $sql .= " AND status = :status";
        $params[':status'] = $filters['status'];
    }
    
    if (!empty($filters['machine_number'])) {
        $sql .= " AND machine_number = :machine_number";
        $params[':machine_number'] = $filters['machine_number'];
    }
    
    if (!empty($filters['registry_signer'])) {
        if ($filters['registry_signer'] === 'empty') {
            $sql .= " AND (registry_signer IS NULL OR registry_signer = '')";
        } elseif ($filters['registry_signer'] === 'not_empty') {
            $sql .= " AND registry_signer IS NOT NULL AND registry_signer != ''";
        }
    }
    
    $sql .= " ORDER BY start_job DESC";
    
    $stmt = $conn->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    json_response(true, 'ดึงข้อมูลสำเร็จ', $data);
    
} catch (PDOException $e) {
    http_response_code(500);
    json_response(false, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
}
?>
