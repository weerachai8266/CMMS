<?php
require_once '../config/config.php';
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $status = $_GET['status'] ?? '';
    $date_from = $_GET['date_from'] ?? date('Y-m-01');
    $date_to = $_GET['date_to'] ?? date('Y-m-d');
    
    if (empty($status)) {
        throw new Exception('Status parameter is required');
    }
    
    $sql = "SELECT 
        id,
        machine_number,
        department,
        branch,
        issue,
        start_job,
        end_job,
        status,
        handled_by
        FROM mt_repair
        WHERE status = :status
        AND DATE(start_job) BETWEEN :date_from AND :date_to
        ORDER BY start_job DESC
        LIMIT 100";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':status' => $status,
        ':date_from' => $date_from,
        ':date_to' => $date_to
    ]);
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
