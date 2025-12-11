<?php
/**
 * Import Machine History Data from CSV to Database
 * ไฟล์สำหรับ import ข้อมูลประวัติเครื่องจักรจาก CSV เข้าฐานข้อมูล
 */

require_once 'config/db.php';
require_once 'config/config.php';

/**
 * แปลงวันที่รูปแบบไทย (dd/mm/yyyy) เป็น yyyy-mm-dd
 * เช่น 06/04/2024 -> 2024-04-06
 */
function convertThaiDateToSQL($thaiDate) {
    if (empty($thaiDate)) {
        return null;
    }
    
    // ลบ whitespace
    $thaiDate = trim($thaiDate);
    
    // รูปแบบ dd/mm/yyyy หรือ dd/mm/yyyy hh:mm
    $parts = preg_split('/[\s]+/', $thaiDate);
    $datePart = $parts[0];
    $timePart = isset($parts[1]) ? $parts[1] : null;
    
    $dateComponents = explode('/', $datePart);
    if (count($dateComponents) != 3) {
        return null;
    }
    
    $day = str_pad(trim($dateComponents[0]), 2, '0', STR_PAD_LEFT);
    $month = str_pad(trim($dateComponents[1]), 2, '0', STR_PAD_LEFT);
    $year = intval(trim($dateComponents[2]));
    
    // ถ้าปีมากกว่า 2500 ถือว่าเป็น พ.ศ. แปลงเป็น ค.ศ.
    if ($year > 2500) {
        $year = $year - 543;
    }
    
    $result = sprintf('%04d-%02d-%02d', $year, intval($month), intval($day));
    
    // ถ้ามีเวลาด้วย
    if ($timePart) {
        $result .= ' ' . $timePart . ':00'; // เพิ่ม seconds
    }
    
    return $result;
}

// ตั้งค่า encoding
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html lang='th'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Import ข้อมูลประวัติเครื่องจักร</title>";
echo "<link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>";
echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'>";
echo "<link href='https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap' rel='stylesheet'>";
echo "<style>body { font-family: 'Sarabun', sans-serif; padding: 20px; } .success { color: green; } .error { color: red; }</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";
echo "<h1 class='mb-4'><i class='fas fa-upload'></i> Import ข้อมูลประวัติเครื่องจักร</h1>";

// ตรวจสอบว่ามีตาราง mt_machine_history หรือยัง
try {
    $check = $conn->query("SHOW TABLES LIKE 'mt_machine_history'");
    if ($check->rowCount() == 0) {
        echo "<div class='alert alert-danger'>";
        echo "<strong>ข้อผิดพลาด!</strong> ตาราง mt_machine_history ยังไม่ถูกสร้าง<br>";
        echo "กรุณาให้ DBA รันคำสั่ง SQL สร้างตารางก่อน";
        echo "</div>";
        echo "</div></body></html>";
        exit;
    }
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>ข้อผิดพลาด: " . $e->getMessage() . "</div>";
    echo "</div></body></html>";
    exit;
}

// เส้นทางไฟล์ CSV
$csvFile = 'sql/machine_history.csv';

if (!file_exists($csvFile)) {
    echo "<div class='alert alert-danger'>ไม่พบไฟล์: $csvFile</div>";
    echo "</div></body></html>";
    exit;
}

// ตรวจสอบ delimiter (Tab หรือ Comma)
// ถ้าใช้ Tab delimiter ให้ตั้งเป็น "\t", ถ้าใช้ Comma ให้ตั้งเป็น ","
$delimiter = ","; // เปลี่ยนเป็น "\t" ถ้าใช้ Tab delimiter

echo "<div class='alert alert-info'>กำลังอ่านไฟล์: <strong>$csvFile</strong> (Delimiter: " . ($delimiter == "\t" ? "Tab" : "Comma") . ")</div>";

// เปิดไฟล์ CSV
$file = fopen($csvFile, 'r');
if (!$file) {
    echo "<div class='alert alert-danger'>ไม่สามารถเปิดไฟล์ CSV ได้</div>";
    echo "</div></body></html>";
    exit;
}

// กำหนดให้ใช้ UTF-8
mb_internal_encoding('UTF-8');

// นับจำนวน
$successCount = 0;
$errorCount = 0;
$skipCount = 0;
$lineNumber = 0;

echo "<div class='card'>";
echo "<div class='card-body'>";
echo "<h5>ผลการ Import:</h5>";
echo "<table class='table table-sm table-bordered'>";
echo "<thead class='thead-dark'>";
echo "<tr><th style='width: 50px;'>แถว</th><th>สถานะ</th><th>รายละเอียด</th></tr>";
echo "</thead>";
echo "<tbody>";

// อ่านข้อมูลทีละบรรทัด
while (($data = fgetcsv($file, 0, $delimiter, '"', '"')) !== FALSE) {
    $lineNumber++;

    // ถ้าทั้งแถวว่าง ข้าม
    if ((count($data) == 1 && trim($data[0]) === '') ||
        (isset($data[0]) && $data[0] === null && isset($data[1]) && $data[1] === null)) {
        continue;
    }

    // ถ้าจำนวนคอลัมน์ไม่ถึงที่คาดไว้ (24) แสดง warning ให้เช็คไฟล์
    if (count($data) < 24) {
        echo "<tr class='table-warning'>";
        echo "<td>$lineNumber</td>";
        echo "<td><span class='badge badge-warning'>ข้าม</span></td>";
        echo "<td>จำนวนคอลัมน์ไม่ครบ (" . count($data) . " คอลัมน์) กรุณาตรวจสอบ CSV</td>";
        echo "</tr>";
        $skipCount++;
        continue;
    }
    
    // แปลง encoding ทีละช่อง (เผื่อไฟล์ไม่ใช่ UTF-8)
    foreach ($data as &$col) {
        if ($col !== null) {
            // ถ้าไฟล์เป็น TIS-620 ให้ใช้ 'TIS-620' แทน 'UTF-8' ด้านล่าง
            $col = mb_convert_encoding($col, 'UTF-8', 'UTF-8');
        }
    }
    unset($col);
    
    // แยกข้อมูลด้วย delimiter ที่กำหนด (รองรับ double quotes)
    // $data = str_getcsv($line, $delimiter);
    
    // ข้ามหัวตาราง (แถวแรก)
    if ($lineNumber == 1) {
        echo "<tr class='table-info'>";
        echo "<td>$lineNumber</td>";
        echo "<td><span class='badge badge-info'>หัวตาราง</span></td>";
        echo "<td>ข้ามหัวตาราง</td>";
        echo "</tr>";
        continue;
    }
    
    // ข้าม empty line
    if (empty($data[0]) && empty($data[1])) {
        continue;
    }
    
    // แยกข้อมูลตามคอลัมน์ CSV
    // Column: 0=machine_code, 1=machine_name, 2=document_no, 3=work_date, 4=start_date, 5=completed_date,
    // 6=issue_description, 7=solution_description, 8=parts_used, 9=work_hours, 10=downtime_hours,
    // 11=labor_cost, 12=parts_cost, 13=other_cost, 14=total_cost, 15=reported_by, 16=handled_by,
    // 17=approved_by, 18=status, 19=priority, 20=note, 21=attachments, 22=branch, 23=department
    
    $machine_code = isset($data[0]) ? strtoupper(trim($data[0])) : '';
    $machine_name = isset($data[1]) ? trim($data[1]) : '';
    $document_no = isset($data[2]) ? trim($data[2]) : '';
    $work_date = isset($data[3]) ? convertThaiDateToSQL(trim($data[3])) : null;
    $start_date = isset($data[4]) ? convertThaiDateToSQL(trim($data[4])) : null;
    $completed_date = isset($data[5]) ? convertThaiDateToSQL(trim($data[5])) : null;
    $issue_description = isset($data[6]) ? trim($data[6]) : '';
    $solution_description = isset($data[7]) ? trim($data[7]) : '';
    $parts_used = isset($data[8]) ? trim($data[8]) : '';
    $work_hours = isset($data[9]) && is_numeric($data[9]) ? floatval($data[9]) : 0;
    $downtime_hours = isset($data[10]) && is_numeric($data[10]) ? floatval($data[10]) : 0;
    $labor_cost = isset($data[11]) && is_numeric($data[11]) ? floatval($data[11]) : 0;
    $parts_cost = isset($data[12]) && is_numeric($data[12]) ? floatval($data[12]) : 0;
    $other_cost = isset($data[13]) && is_numeric($data[13]) ? floatval($data[13]) : 0;
    $total_cost = isset($data[14]) && is_numeric($data[14]) ? floatval($data[14]) : 0;
    $reported_by = isset($data[15]) ? trim($data[15]) : '';
    $handled_by = isset($data[16]) ? trim($data[16]) : '';
    // ข้าม approved_by (index 17), priority (index 19), attachments (index 21) - ไม่ใช้งาน
    $status = isset($data[18]) ? strtolower(trim($data[18])) : 'completed';
    $note = isset($data[20]) ? trim($data[20]) : '';
    $branch = isset($data[22]) ? trim($data[22]) : '';
    $department = isset($data[23]) ? trim($data[23]) : '';
    
    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($machine_code)) {
        echo "<tr class='table-warning'>";
        echo "<td>$lineNumber</td>";
        echo "<td><span class='badge badge-warning'>ข้าม</span></td>";
        echo "<td>ไม่มีรหัสเครื่องจักร</td>";
        echo "</tr>";
        $skipCount++;
        continue;
    }
    
    try {
        // ตรวจสอบว่ามี document_no ซ้ำหรือไม่
        // if (!empty($document_no)) {
        //     $checkSql = "SELECT id FROM mt_machine_history WHERE document_no = :document_no";
        //     $checkStmt = $conn->prepare($checkSql);
        //     $checkStmt->bindParam(':document_no', $document_no);
        //     $checkStmt->execute();
            
        //     if ($checkStmt->rowCount() > 0) {
        //         echo "<tr class='table-warning'>";
        //         echo "<td>$lineNumber</td>";
        //         echo "<td><span class='badge badge-warning'>ข้าม</span></td>";
        //         echo "<td>เอกสาร: $document_no - มีในระบบแล้ว</td>";
        //         echo "</tr>";
        //         $skipCount++;
        //         continue;
        //     }
        // }
        
        // TODO: Auto-generate document_no when importing
        // ระบบจะ skip รายการที่มี document_no เป็นเพียงคำนำหน้า (เช่น "PM", "CAL", "OVH", "INS")
        // เนื่องจากต้องนำเข้าข้อมูลย้อนหลัง 10 ปี โดยใช้เลขเอกสารเดิมจากระบบเก่า
        // เมื่อนำเข้าข้อมูลเก่าเสร็จแล้ว จึงจะเปิดใช้งาน auto-generate เหมือนกับ API
        // 
        // วิธีเปิดใช้งาน auto-generate:
        // 1. ตรวจสอบว่า document_no เป็นคำนำหน้าเท่านั้น (PM, CAL, OVH, INS)
        // 2. ถ้าใช่ ให้ดึง work_type จาก document_no และสร้างเลขเอกสารอัตโนมัติ
        // 3. ใช้ logic เดียวกับ api/machine_history.php (handlePost)
        // 
        // ตัวอย่างโค้ดที่ต้องเพิ่ม:
        // if (in_array($document_no, ['PM', 'CAL', 'OVH', 'INS'])) {
        //     $prefix_map = ['PM' => 'PM', 'CAL' => 'CAL', 'OVH' => 'OVH', 'INS' => 'INS'];
        //     $prefix = $prefix_map[$document_no];
        //     $year_2digit = date('y');
        //     $sql_count = "SELECT COUNT(*) FROM mt_machine_history 
        //                   WHERE YEAR(work_date) = YEAR(NOW()) AND document_no LIKE :prefix";
        //     $stmt_count = $conn->prepare($sql_count);
        //     $like_prefix = $prefix . '%';
        //     $stmt_count->bindParam(':prefix', $like_prefix);
        //     $stmt_count->execute();
        //     $count = $stmt_count->fetchColumn();
        //     $running_number = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        //     $document_no = $prefix . $running_number . '/' . $year_2digit;
        // }
        
        // Insert ข้อมูลใหม่
        $insertSql = "INSERT INTO mt_machine_history (
            machine_code, machine_name, document_no,
            work_date, start_date, completed_date,
            issue_description, solution_description, parts_used,
            work_hours, downtime_hours,
            labor_cost, parts_cost, other_cost, total_cost,
            reported_by, handled_by,
            status, note,
            branch, department
        ) VALUES (
            :machine_code, :machine_name, :document_no,
            :work_date, :start_date, :completed_date,
            :issue_description, :solution_description, :parts_used,
            :work_hours, :downtime_hours,
            :labor_cost, :parts_cost, :other_cost, :total_cost,
            :reported_by, :handled_by,
            :status, :note,
            :branch, :department
        )";
        
        $stmt = $conn->prepare($insertSql);
        $stmt->bindParam(':machine_code', $machine_code);
        $stmt->bindParam(':machine_name', $machine_name);
        $stmt->bindParam(':document_no', $document_no);
        $stmt->bindParam(':work_date', $work_date);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':completed_date', $completed_date);
        $stmt->bindParam(':issue_description', $issue_description);
        $stmt->bindParam(':solution_description', $solution_description);
        $stmt->bindParam(':parts_used', $parts_used);
        $stmt->bindParam(':work_hours', $work_hours);
        $stmt->bindParam(':downtime_hours', $downtime_hours);
        $stmt->bindParam(':labor_cost', $labor_cost);
        $stmt->bindParam(':parts_cost', $parts_cost);
        $stmt->bindParam(':other_cost', $other_cost);
        $stmt->bindParam(':total_cost', $total_cost);
        $stmt->bindParam(':reported_by', $reported_by);
        $stmt->bindParam(':handled_by', $handled_by);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':note', $note);
        $stmt->bindParam(':branch', $branch);
        $stmt->bindParam(':department', $department);
        
        $stmt->execute();
        
        echo "<tr class='table-success'>";
        echo "<td>$lineNumber</td>";
        echo "<td><span class='badge badge-success'>สำเร็จ</span></td>";
        echo "<td>$machine_code - $document_no</td>";
        echo "</tr>";
        $successCount++;
        
    } catch (PDOException $e) {
        echo "<tr class='table-danger'>";
        echo "<td>$lineNumber</td>";
        echo "<td><span class='badge badge-danger'>ผิดพลาด</span></td>";
        echo "<td>$machine_code - " . $e->getMessage() . "</td>";
        echo "</tr>";
        $errorCount++;
    }
}

fclose($file);

echo "</tbody>";
echo "</table>";
echo "</div>";
echo "</div>";

// สรุปผล
echo "<div class='card mt-3'>";
echo "<div class='card-body'>";
echo "<h5>สรุปผลการ Import</h5>";
echo "<ul class='list-group'>";
echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
echo "บันทึกสำเร็จ";
echo "<span class='badge badge-success badge-pill'>$successCount</span>";
echo "</li>";
echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
echo "ข้ามไป";
echo "<span class='badge badge-warning badge-pill'>$skipCount</span>";
echo "</li>";
echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
echo "ผิดพลาด";
echo "<span class='badge badge-danger badge-pill'>$errorCount</span>";
echo "</li>";
echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
echo "<strong>รวมทั้งหมด</strong>";
echo "<span class='badge badge-primary badge-pill'>" . ($successCount + $skipCount + $errorCount) . "</span>";
echo "</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "<div class='mt-3'>";
echo "<a href='pages/machines.php' class='btn btn-primary'><i class='fas fa-arrow-left'></i> กลับหน้าหลัก</a>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
