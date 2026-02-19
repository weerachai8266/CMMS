<?php
/**
 * Auto-sync Repair to Machine History
 * เมื่อใบแจ้งซ่อมเปลี่ยนสถานะเป็น "ซ่อมเสร็จสิ้น" (40)
 * จะ copy ข้อมูลไปยัง mt_machine_history อัตโนมัติ
 */

require_once '../config/config.php';
require_once '../config/db.php';

/**
 * Sync repair record to machine history
 * @param int $repairId - ID ของใบแจ้งซ่อม
 * @return bool
 */
function syncRepairToHistory($repairId, $conn) {
    try {
        // ดึงข้อมูลจาก mt_repair
        $sql = "SELECT * FROM mt_repair WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $repairId);
        $stmt->execute();
        $repair = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$repair) {
            return false;
        }

        // ❌ ห้าม sync รายการที่ถูกยกเลิก (status = 50)
        if ((int)$repair['status'] === STATUS_CANCELLED) {
            error_log("syncRepairToHistory: skipped repair id={$repairId} (status=50 cancelled)");
            return false;
        }
        
        // ตรวจสอบว่ามี record นี้ใน history แล้วหรือยัง
        $checkSql = "SELECT id FROM mt_machine_history WHERE document_no = :doc_no";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bindParam(':doc_no', $repair['document_no']);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            // ถ้ามีแล้ว ให้ UPDATE แทน
            $updateSql = "UPDATE mt_machine_history SET
                machine_code = :machine_code,
                work_date = :work_date,
                start_date = :start_date,
                completed_date = :completed_date,
                issue_description = :issue_description,
                solution_description = :solution_description,
                parts_used = :parts_used,
                work_hours = :work_hours,
                downtime_hours = :downtime_hours,
                total_cost = :total_cost,
                reported_by = :reported_by,
                handled_by = :handled_by,
                status = 'completed',
                branch = :branch,
                department = :department,
                note = :note,
                updated_at = CURRENT_TIMESTAMP
                WHERE document_no = :document_no";
            
            $stmt = $conn->prepare($updateSql);
            $stmt->bindValue(':parts_used', $repair['operation_detail']);
            $stmt->bindValue(':note', $repair['mtc_comment']);
        } else {
            // ถ้ายังไม่มี ให้ INSERT ใหม่
            $insertSql = "INSERT INTO mt_machine_history 
                (machine_code, machine_name, document_no,
                 work_date, start_date, completed_date,
                 issue_description, solution_description, parts_used,
                 work_hours, downtime_hours, total_cost,
                 reported_by, handled_by, status,
                 branch, department, note)
                VALUES 
                (:machine_code, :machine_name, :document_no,
                 :work_date, :start_date, :completed_date,
                 :issue_description, :solution_description, :parts_used,
                 :work_hours, :downtime_hours, :total_cost,
                 :reported_by, :handled_by, 'completed',
                 :branch, :department, :note)";
            
            $stmt = $conn->prepare($insertSql);
            $stmt->bindValue(':machine_name', $repair['machine_number']); // ใช้ machine_number แทน machine_name ชั่วคราว
            $stmt->bindValue(':parts_used', $repair['operation_detail']); // ใช้ operation_detail เป็นอะไหล่
            $stmt->bindValue(':note', $repair['mtc_comment']);
        }
        
        // Bind parameters ที่ใช้ร่วมกัน
        $stmt->bindValue(':machine_code', $repair['machine_number']);
        $stmt->bindValue(':document_no', $repair['document_no']);
        $stmt->bindValue(':work_date', $repair['start_job']);
        $stmt->bindValue(':start_date', $repair['start_date']);
        $stmt->bindValue(':completed_date', $repair['end_date']);
        $stmt->bindValue(':issue_description', $repair['issue']);
        $stmt->bindValue(':solution_description', $repair['mt_report']);
        $stmt->bindValue(':work_hours', $repair['work_hours'] ?? 0);
        $stmt->bindValue(':downtime_hours', $repair['downtime_hours'] ?? 0);
        $stmt->bindValue(':total_cost', $repair['total_cost'] ?? 0);
        $stmt->bindValue(':reported_by', $repair['reported_by']);
        $stmt->bindValue(':handled_by', $repair['handled_by']);
        $stmt->bindValue(':branch', $repair['branch']);
        $stmt->bindValue(':department', $repair['department']);
        
        $stmt->execute();
        return true;
        
    } catch (PDOException $e) {
        error_log("Error syncing repair to history: " . $e->getMessage());
        return false;
    }
}

// ถ้าถูกเรียกโดยตรง (สำหรับทดสอบ)
if (isset($_GET['repair_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    $repairId = intval($_GET['repair_id']);
    $result = syncRepairToHistory($repairId, $conn);
    json_response($result, $result ? 'Sync สำเร็จ' : 'Sync ไม่สำเร็จ');
}
?>
