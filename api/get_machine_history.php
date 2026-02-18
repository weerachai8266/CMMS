<?php
require_once '../config/config.php';
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $machine_number = $_GET['machine_number'] ?? '';
    
    if (empty($machine_number)) {
        throw new Exception('Machine number parameter is required');
    }
    
    // Get repair history
    $sql = "SELECT 
        id,
        machine_number,
        department,
        branch,
        issue,
        start_job,
        end_job,
        status,
        handled_by,
        TIMESTAMPDIFF(HOUR, start_job, end_job) as work_hours
        FROM mt_repair
        WHERE machine_number = :machine_number
        ORDER BY start_job DESC
        LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':machine_number' => $machine_number]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total cost from machine_history table
    $sql_cost = "SELECT 
        SUM(total_cost) as total_cost,
        SUM(work_hours) as total_hours
        FROM mt_machine_history
        WHERE machine_code = :machine_number";
    
    $stmt_cost = $conn->prepare($sql_cost);
    $stmt_cost->execute([':machine_number' => $machine_number]);
    $stats = $stmt_cost->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total_cost' => $stats['total_cost'] ?? 0,
        'total_hours' => $stats['total_hours'] ?? 0
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
