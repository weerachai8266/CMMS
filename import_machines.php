<?php
/**
 * Import Machine Data from CSV to Database
 * ไฟล์สำหรับ import ข้อมูลเครื่องจักรจาก CSV เข้าฐานข้อมูล
 */

require_once 'config/db.php';
require_once 'config/config.php';

/**
 * แปลงวันที่รูปแบบไทย (dd/mm/yyyy พ.ศ.) เป็น yyyy-mm-dd (ค.ศ.)
 * เช่น 12/12/2554 -> 2011-12-12
 */
function convertThaiDateToSQL($thaiDate) {
    if (empty($thaiDate)) {
        return null;
    }
    
    // รูปแบบ dd/mm/yyyy (พ.ศ.)
    $parts = explode('/', $thaiDate);
    if (count($parts) != 3) {
        return null;
    }
    
    $day = str_pad(trim($parts[0]), 2, '0', STR_PAD_LEFT);
    $month = str_pad(trim($parts[1]), 2, '0', STR_PAD_LEFT);
    $yearBE = intval(trim($parts[2])); // ปี พ.ศ.
    
    // แปลง พ.ศ. เป็น ค.ศ. (ลบ 543)
    $yearAD = $yearBE - 543;
    
    return sprintf('%04d-%02d-%02d', $yearAD, intval($month), intval($day));
}

// ตั้งค่า encoding
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html lang='th'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Import ข้อมูลเครื่องจักร</title>";
echo "<link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>";
echo "<link href='https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap' rel='stylesheet'>";
echo "<style>body { font-family: 'Sarabun', sans-serif; padding: 20px; } .success { color: green; } .error { color: red; }</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";
echo "<h1 class='mb-4'><i class='fas fa-upload'></i> Import ข้อมูลเครื่องจักร</h1>";

// ตรวจสอบว่ามีตาราง mt_machines หรือยัง
try {
    $check = $conn->query("SHOW TABLES LIKE 'mt_machines'");
    if ($check->rowCount() == 0) {
        echo "<div class='alert alert-danger'>";
        echo "<strong>ข้อผิดพลาด!</strong> ตาราง mt_machines ยังไม่ถูกสร้าง<br>";
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
$csvFile = 'sql/data.csv';

if (!file_exists($csvFile)) {
    echo "<div class='alert alert-danger'>ไม่พบไฟล์: $csvFile</div>";
    echo "</div></body></html>";
    exit;
}

echo "<div class='alert alert-info'>กำลังอ่านไฟล์: <strong>$csvFile</strong></div>";

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
while (($line = fgets($file)) !== FALSE) {
    $lineNumber++;
    
    // แปลง encoding ถ้าจำเป็น
    $line = mb_convert_encoding($line, 'UTF-8', 'UTF-8');
    
    // แยกข้อมูลด้วย comma
    $data = str_getcsv($line, ',');
    
    // ข้าม empty line
    if (empty($data[0]) && empty($data[1])) {
        continue;
    }
    
    // แยกข้อมูลตามคอลัมน์ CSV
    // Column: 0=Type, 1=Code, 2=Number, 3=Name, 4=Branch, 5=Brand, 6=Model, 7=HP, 8=Weight, 9=Qty, 10=RespDept, 11=WorkArea, 12=Manufacturer, 13=Supplier, 14=Price, 15=Phone, 16=PurchaseDate, 17=StartDate, 18=RegisterDate, 19=Status, 20=Unit, 21=Note
    $machine_type = isset($data[0]) ? strtoupper(trim($data[0])) : '';
    $machine_code = isset($data[1]) ? strtoupper(trim($data[1])) : '';
    $machine_number = isset($data[2]) ? strtoupper(trim($data[2])) : '';
    $machine_name = isset($data[3]) ? strtoupper(trim($data[3])) : '';
    $branch = isset($data[4]) ? strtoupper(trim($data[4])) : '';
    $brand = isset($data[5]) ? trim($data[5]) : '';
    $model = isset($data[6]) ? strtoupper(trim($data[6])) : '';
    $horsepower = isset($data[7]) ? trim($data[7]) : '';
    $weight = isset($data[8]) ? trim($data[8]) : '';
    $quantity = isset($data[9]) && is_numeric($data[9]) ? intval($data[9]) : 1;
    $responsible_dept = isset($data[10]) ? strtoupper(trim($data[10])) : '';
    $work_area = isset($data[11]) ? strtoupper(trim($data[11])) : '';
    $manufacturer = isset($data[12]) ? trim($data[12]) : '';
    $supplier = isset($data[13]) ? trim($data[13]) : '';
    $purchase_price = isset($data[14]) && is_numeric($data[14]) ? floatval($data[14]) : null;
    $contact_phone = isset($data[15]) ? trim($data[15]) : '';
    
    // แปลงวันที่รูปแบบไทย (dd/mm/yyyy พ.ศ.) เป็นรูปแบบสากล (yyyy-mm-dd ค.ศ.)
    $purchase_date = null;
    if (isset($data[16]) && !empty(trim($data[16]))) {
        $purchase_date = convertThaiDateToSQL(trim($data[16]));
    }
    $start_date = null;
    if (isset($data[17]) && !empty(trim($data[17]))) {
        $start_date = convertThaiDateToSQL(trim($data[17]));
    }
    $register_date = null;
    if (isset($data[18]) && !empty(trim($data[18]))) {
        $register_date = convertThaiDateToSQL(trim($data[18]));
    }
    
    $machine_status = isset($data[19]) ? strtolower(trim($data[19])) : 'active';
    $unit = isset($data[20]) ? strtoupper(trim($data[20])) : 'เครื่อง';
    $note = isset($data[21]) ? strtoupper(trim($data[21])) : '';
    
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
    
    if (empty($machine_name)) {
        echo "<tr class='table-warning'>";
        echo "<td>$lineNumber</td>";
        echo "<td><span class='badge badge-warning'>ข้าม</span></td>";
        echo "<td>รหัส: $machine_code - ไม่มีชื่อเครื่องจักร</td>";
        echo "</tr>";
        $skipCount++;
        continue;
    }
    
    try {
        // ตรวจสอบว่ามีรหัสนี้อยู่แล้วหรือไม่
        $checkSql = "SELECT id FROM mt_machines WHERE machine_code = :machine_code";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bindParam(':machine_code', $machine_code);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            // อัพเดทข้อมูลเดิม
            $updateSql = "UPDATE mt_machines SET 
                machine_type = :machine_type,
                machine_number = :machine_number,
                machine_name = :machine_name,
                branch = :branch,
                brand = :brand,
                model = :model,
                horsepower = :horsepower,
                weight = :weight,
                quantity = :quantity,
                responsible_dept = :responsible_dept,
                work_area = :work_area,
                manufacturer = :manufacturer,
                supplier = :supplier,
                purchase_price = :purchase_price,
                contact_phone = :contact_phone,
                purchase_date = :purchase_date,
                start_date = :start_date,
                register_date = :register_date,
                machine_status = :machine_status,
                unit = :unit,
                note = :note,
                updated_at = CURRENT_TIMESTAMP
                WHERE machine_code = :machine_code";
            
            $stmt = $conn->prepare($updateSql);
            $stmt->bindParam(':machine_type', $machine_type);
            $stmt->bindParam(':machine_code', $machine_code);
            $stmt->bindParam(':machine_number', $machine_number);
            $stmt->bindParam(':machine_name', $machine_name);
            $stmt->bindParam(':branch', $branch);
            $stmt->bindParam(':brand', $brand);
            $stmt->bindParam(':model', $model);
            $stmt->bindParam(':horsepower', $horsepower);
            $stmt->bindParam(':weight', $weight);
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':responsible_dept', $responsible_dept);
            $stmt->bindParam(':work_area', $work_area);
            $stmt->bindParam(':manufacturer', $manufacturer);
            $stmt->bindParam(':supplier', $supplier);
            $stmt->bindParam(':purchase_price', $purchase_price);
            $stmt->bindParam(':contact_phone', $contact_phone);
            $stmt->bindParam(':purchase_date', $purchase_date);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':register_date', $register_date);
            $stmt->bindParam(':machine_status', $machine_status);
            $stmt->bindParam(':unit', $unit);
            $stmt->bindParam(':note', $note);
            $stmt->execute();
            
            echo "<tr class='table-info'>";
            echo "<td>$lineNumber</td>";
            echo "<td><span class='badge badge-info'>อัพเดท</span></td>";
            echo "<td>$machine_code - $machine_name</td>";
            echo "</tr>";
            $successCount++;
            
        } else {
            // เพิ่มข้อมูลใหม่
            $insertSql = "INSERT INTO mt_machines 
                (machine_type, machine_code, machine_number, machine_name, branch, brand, model, horsepower, weight, quantity, responsible_dept, work_area, manufacturer, supplier, purchase_price, contact_phone, purchase_date, start_date, register_date, machine_status, unit, note) 
                VALUES 
                (:machine_type, :machine_code, :machine_number, :machine_name, :branch, :brand, :model, :horsepower, :weight, :quantity, :responsible_dept, :work_area, :manufacturer, :supplier, :purchase_price, :contact_phone, :purchase_date, :start_date, :register_date, :machine_status, :unit, :note)";
            
            $stmt = $conn->prepare($insertSql);
            $stmt->bindParam(':machine_type', $machine_type);
            $stmt->bindParam(':machine_code', $machine_code);
            $stmt->bindParam(':machine_number', $machine_number);
            $stmt->bindParam(':machine_name', $machine_name);
            $stmt->bindParam(':branch', $branch);
            $stmt->bindParam(':brand', $brand);
            $stmt->bindParam(':model', $model);
            $stmt->bindParam(':horsepower', $horsepower);
            $stmt->bindParam(':weight', $weight);
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':responsible_dept', $responsible_dept);
            $stmt->bindParam(':work_area', $work_area);
            $stmt->bindParam(':manufacturer', $manufacturer);
            $stmt->bindParam(':supplier', $supplier);
            $stmt->bindParam(':purchase_price', $purchase_price);
            $stmt->bindParam(':contact_phone', $contact_phone);
            $stmt->bindParam(':purchase_date', $purchase_date);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':register_date', $register_date);
            $stmt->bindParam(':machine_status', $machine_status);
            $stmt->bindParam(':unit', $unit);
            $stmt->bindParam(':note', $note);
            $stmt->execute();
            
            echo "<tr class='table-success'>";
            echo "<td>$lineNumber</td>";
            echo "<td><span class='badge badge-success'>สำเร็จ</span></td>";
            echo "<td>$machine_code - $machine_name</td>";
            echo "</tr>";
            $successCount++;
        }
        
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
echo "<div class='card-header bg-primary text-white'>";
echo "<h5 class='mb-0'>สรุปผลการ Import</h5>";
echo "</div>";
echo "<div class='card-body'>";
echo "<div class='row'>";
echo "<div class='col-md-3'>";
echo "<div class='alert alert-success'><strong>สำเร็จ:</strong> $successCount รายการ</div>";
echo "</div>";
echo "<div class='col-md-3'>";
echo "<div class='alert alert-warning'><strong>ข้าม:</strong> $skipCount รายการ</div>";
echo "</div>";
echo "<div class='col-md-3'>";
echo "<div class='alert alert-danger'><strong>ผิดพลาด:</strong> $errorCount รายการ</div>";
echo "</div>";
echo "<div class='col-md-3'>";
echo "<div class='alert alert-info'><strong>รวมทั้งหมด:</strong> $lineNumber บรรทัด</div>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='mt-4'>";
echo "<a href='pages/machines.php' class='btn btn-primary'><i class='fas fa-cogs'></i> ดูข้อมูลเครื่องจักร</a> ";
echo "<a href='index.php' class='btn btn-secondary'><i class='fas fa-home'></i> กลับหน้าแรก</a>";
echo "</div>";

echo "</div>"; // container
echo "</body>";
echo "</html>";
?>
