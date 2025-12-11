<?php
require_once '../config/config.php';
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Query data with approved_at from mt_approval_log
    $sql = "SELECT 
                r.id, r.division, r.department, r.branch, r.document_no, 
                r.machine_number, r.issue, r.reported_by, r.handled_by, 
                r.mt_report, r.status, r.start_job, r.end_job,
                a.approved_at
            FROM mt_repair r
            LEFT JOIN mt_approval_log a ON r.id = a.repair_id AND a.action = 'approved'
            WHERE 
                r.status = 20
                OR r.status = 30
                OR (r.status = 40 AND DATE(r.end_job) = CURDATE())
            ORDER BY 
                CASE
                    WHEN r.status = 20 THEN 1
                    WHEN r.status = 30 THEN 2
                    WHEN r.status = 40 THEN 3
                END,
                r.start_job DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate statistics
    $stats = [
        'total' => 0,
        'pending' => 0,
        'completed' => 0,
        'waiting' => 0
    ];
    
    foreach ($results as $row) {
        $stats['total']++;
        switch (intval($row['status'])) {
            case STATUS_PENDING:
                $stats['pending']++;
                break;
            case STATUS_COMPLETED:
                $stats['completed']++;
                break;
            case STATUS_WAITING_PARTS:
                $stats['waiting']++;
                break;
        }
    }
    
    // Format output
    foreach ($results as &$row) {
        $row['start_job_raw'] = $row['start_job']; // Keep raw timestamp
        $row['end_job_raw'] = $row['end_job']; // Keep raw timestamp
        $row['approved_at_raw'] = $row['approved_at']; // Keep raw approved timestamp
        
        $row['start_job'] = date('d/m/Y H:i', strtotime($row['start_job']));
        if ($row['end_job'] && $row['end_job'] !== '0000-00-00 00:00:00') {
            $row['end_job'] = date('d/m/Y H:i', strtotime($row['end_job_raw']));
        }
        if ($row['approved_at']) {
            $row['approved_at_formatted'] = date('d/m/Y H:i', strtotime($row['approved_at']));
        } else {
            $row['approved_at_formatted'] = null;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $results,
        'stats' => $stats
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
