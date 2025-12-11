<?php
require_once '../config/config.php';
require_once '../config/db.php';

header('Content-Type: application/json');

// ตรวจสอบ HTTP method
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // ดึงรายการเครื่องจักรทั้งหมด
            if (isset($_GET['id'])) {
                // ดึงข้อมูลเครื่องจักรเฉพาะ ID
                $id = intval($_GET['id']);
                $sql = "SELECT * FROM mt_machines WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($data) {
                    echo json_encode(['success' => true, 'data' => $data]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูล']);
                }
            } else {
                // ดึงข้อมูลทั้งหมด
                $sql = "SELECT * FROM mt_machines ORDER BY machine_code ASC";
                $stmt = $conn->query($sql);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $data]);
            }
            break;

        case 'POST':
            // เพิ่มเครื่องจักรใหม่
            $machine_type = strtoupper(sanitize_input($_POST['machine_type']));
            $machine_code = strtoupper(sanitize_input($_POST['machine_code']));
            $machine_number = strtoupper(sanitize_input($_POST['machine_number'] ?? ''));
            $machine_name = strtoupper(sanitize_input($_POST['machine_name']));
            $branch = strtoupper(sanitize_input($_POST['branch'] ?? ''));
            $brand = strtoupper(sanitize_input($_POST['brand'] ?? ''));
            $model = strtoupper(sanitize_input($_POST['model'] ?? ''));
            $horsepower = strtoupper(sanitize_input($_POST['horsepower'] ?? ''));
            $weight = strtoupper(sanitize_input($_POST['weight'] ?? ''));
            $quantity = 1;
            // $quantity = intval($_POST['quantity']);
            $responsible_dept = strtoupper(sanitize_input($_POST['responsible_dept'] ?? ''));
            $work_area = strtoupper(sanitize_input($_POST['work_area'] ?? ''));
            $manufacturer = strtoupper(sanitize_input($_POST['manufacturer'] ?? ''));
            $supplier = strtoupper(sanitize_input($_POST['supplier'] ?? ''));
            $purchase_price = !empty($_POST['purchase_price']) ? floatval($_POST['purchase_price']) : null;
            $contact_phone = sanitize_input($_POST['contact_phone'] ?? '');
            $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
            $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
            $register_date = !empty($_POST['register_date']) ? $_POST['register_date'] : null;
            $machine_status = sanitize_input($_POST['machine_status'] ?? 'active');
            $unit = sanitize_input($_POST['unit']);
            $note = strtoupper(sanitize_input($_POST['note'] ?? ''));

            // ตรวจสอบรหัสเครื่องจักรซ้ำ
            $check_sql = "SELECT id FROM mt_machines WHERE machine_code = :machine_code";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bindParam(':machine_code', $machine_code);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'รหัสเครื่องจักรนี้มีอยู่ในระบบแล้ว']);
                exit;
            }

            $sql = "INSERT INTO mt_machines (machine_type, machine_code, machine_number, machine_name, branch, brand, model, horsepower, weight, 
                    quantity, unit, responsible_dept, work_area, manufacturer, supplier, purchase_price, contact_phone, 
                    purchase_date, start_date, register_date, machine_status, note) 
                    VALUES (:machine_type, :machine_code, :machine_number, :machine_name, :branch, :brand, :model, :horsepower, :weight, 
                    :quantity, :unit, :responsible_dept, :work_area, :manufacturer, :supplier, :purchase_price, :contact_phone, 
                    :purchase_date, :start_date, :register_date, :machine_status, :note)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':machine_type', $machine_type);
            $stmt->bindParam(':machine_code', $machine_code);
            $stmt->bindParam(':machine_number', $machine_number);
            $stmt->bindParam(':machine_name', $machine_name);
            $stmt->bindParam(':branch', $branch);
            $stmt->bindParam(':brand', $brand);
            $stmt->bindParam(':model', $model);
            $stmt->bindParam(':horsepower', $horsepower);
            $stmt->bindParam(':weight', $weight);
            $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $stmt->bindParam(':unit', $unit);
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
            $stmt->bindParam(':note', $note);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'เพิ่มข้อมูลเครื่องจักรสำเร็จ']);
            } else {
                echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล']);
            }
            break;

        case 'PUT':
            // แก้ไขข้อมูลเครื่องจักร
            parse_str(file_get_contents("php://input"), $_PUT);
            
            // Log for debugging
            error_log("PUT Request Data: " . print_r($_PUT, true));
            
            $id = intval($_PUT['machine_id']);
            $machine_type = strtoupper(sanitize_input($_PUT['machine_type']));
            $machine_code = strtoupper(sanitize_input($_PUT['machine_code']));
            $machine_number = strtoupper(sanitize_input($_PUT['machine_number'] ?? ''));
            $machine_name = strtoupper(sanitize_input($_PUT['machine_name']));
            $branch = strtoupper(sanitize_input($_PUT['branch'] ?? ''));
            $brand = strtoupper(sanitize_input($_PUT['brand'] ?? ''));
            $model = strtoupper(sanitize_input($_PUT['model'] ?? ''));
            $horsepower = strtoupper(sanitize_input($_PUT['horsepower'] ?? ''));
            $weight = strtoupper(sanitize_input($_PUT['weight'] ?? ''));
            $quantity = 1;
            // $quantity = intval($_PUT['quantity']);
            $responsible_dept = strtoupper(sanitize_input($_PUT['responsible_dept'] ?? ''));
            $work_area = strtoupper(sanitize_input($_PUT['work_area'] ?? ''));
            $manufacturer = strtoupper(sanitize_input($_PUT['manufacturer'] ?? ''));
            $supplier = strtoupper(sanitize_input($_PUT['supplier'] ?? ''));
            $purchase_price = !empty($_PUT['purchase_price']) ? floatval($_PUT['purchase_price']) : null;
            $contact_phone = sanitize_input($_PUT['contact_phone'] ?? '');
            $purchase_date = !empty($_PUT['purchase_date']) ? $_PUT['purchase_date'] : null;
            $start_date = !empty($_PUT['start_date']) ? $_PUT['start_date'] : null;
            $register_date = !empty($_PUT['register_date']) ? $_PUT['register_date'] : null;
            $machine_status = sanitize_input($_PUT['machine_status'] ?? 'active');
            $unit = sanitize_input($_PUT['unit']);
            $note = strtoupper(sanitize_input($_PUT['note'] ?? ''));

            // ตรวจสอบรหัสเครื่องจักรซ้ำ (ยกเว้นตัวเอง)
            $check_sql = "SELECT id FROM mt_machines WHERE machine_code = :machine_code AND id != :id";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bindParam(':machine_code', $machine_code);
            $check_stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'รหัสเครื่องจักรนี้มีอยู่ในระบบแล้ว']);
                exit;
            }

            $sql = "UPDATE mt_machines SET 
                    machine_type = :machine_type,
                    machine_code = :machine_code,
                    machine_number = :machine_number,
                    machine_name = :machine_name,
                    branch = :branch,
                    brand = :brand,
                    model = :model,
                    horsepower = :horsepower,
                    weight = :weight,
                    quantity = :quantity,
                    unit = :unit,
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
                    note = :note
                    WHERE id = :id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':machine_type', $machine_type);
            $stmt->bindParam(':machine_code', $machine_code);
            $stmt->bindParam(':machine_number', $machine_number);
            $stmt->bindParam(':machine_name', $machine_name);
            $stmt->bindParam(':branch', $branch);
            $stmt->bindParam(':brand', $brand);
            $stmt->bindParam(':model', $model);
            $stmt->bindParam(':horsepower', $horsepower);
            $stmt->bindParam(':weight', $weight);
            $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $stmt->bindParam(':unit', $unit);
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
            $stmt->bindParam(':note', $note);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                error_log("Machine updated successfully. ID: " . $id);
                echo json_encode(['success' => true, 'message' => 'แก้ไขข้อมูลสำเร็จ']);
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Update failed: " . print_r($errorInfo, true));
                echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการแก้ไขข้อมูล: ' . $errorInfo[2]]);
            }
            break;

        case 'DELETE':
            // ลบเครื่องจักร
            parse_str(file_get_contents("php://input"), $_DELETE);
            $id = intval($_DELETE['id']);

            // ตรวจสอบว่ามีการใช้งานในตาราง mt_repair หรือไม่
            $check_sql = "SELECT COUNT(*) as count FROM mt_repair WHERE machine_number IN (SELECT machine_code FROM mt_machines WHERE id = :id)";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $check_stmt->execute();
            $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบได้ เนื่องจากมีการใช้งานในระบบแจ้งซ่อม']);
                exit;
            }

            $sql = "DELETE FROM mt_machines WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'ลบข้อมูลสำเร็จ']);
            } else {
                echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
