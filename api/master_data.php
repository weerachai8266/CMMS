<?php
require_once '../config/config.php';
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$type = $_POST['type'] ?? $_GET['type'] ?? ''; // company, branch, division, department

try {
    switch ($action) {
        case 'list':
            listItems($conn, $type);
            break;
        case 'get':
            getItem($conn, $type, $_GET['id'] ?? 0);
            break;
        case 'save':
            saveItem($conn, $type);
            break;
        case 'delete':
            deleteItem($conn, $type, $_POST['id'] ?? 0);
            break;
        case 'toggle_status':
            toggleStatus($conn, $type, $_POST['id'] ?? 0);
            break;
        default:
            json_response(false, 'Invalid action');
    }
} catch (Exception $e) {
    http_response_code(500);
    json_response(false, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
}

function getTableName($type) {
    $tables = [
        'company' => 'mt_companies',
        'branch' => 'mt_branches',
        'division' => 'mt_divisions',
        'department' => 'mt_departments',
        'issue' => 'mt_issues'
    ];
    return $tables[$type] ?? null;
}

function listItems($conn, $type) {
    $table = getTableName($type);
    if (!$table) {
        json_response(false, 'Invalid type');
        return;
    }
    
    if ($type === 'department') {
        $sql = "SELECT id, name, group_id, group_name, is_active, created_at, created_by, updated_at, updated_by FROM $table ORDER BY created_at DESC";
    } else {
        $sql = "SELECT id, name, is_active, created_at, created_by, updated_at, updated_by FROM $table ORDER BY created_at DESC";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    json_response(true, 'ดึงข้อมูลสำเร็จ', $data);
}

function getItem($conn, $type, $id) {
    $table = getTableName($type);
    if (!$table || !$id) {
        json_response(false, 'Invalid parameters');
        return;
    }
    
    $stmt = $conn->prepare("SELECT * FROM $table WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        json_response(true, 'ดึงข้อมูลสำเร็จ', $data);
    } else {
        json_response(false, 'ไม่พบข้อมูล');
    }
}

function saveItem($conn, $type) {
    $table = getTableName($type);
    if (!$table) {
        json_response(false, 'Invalid type');
        return;
    }
    
    $id = $_POST['id'] ?? 0;
    $name = trim($_POST['name'] ?? '');
    $current_user = $_SESSION['technician_username'] ?? 'system';
    
    if (empty($name)) {
        json_response(false, 'กรุณากรอกชื่อ');
        return;
    }
    
    try {
        $conn->beginTransaction();
        
        if ($type === 'department') {
            $group_id = intval($_POST['group_id'] ?? 0);
            $group_name = trim($_POST['group_name'] ?? '');
            if ($id > 0) {
                $sql = "UPDATE $table SET name = :name, group_id = :group_id, group_name = :group_name, updated_by = :updated_by, updated_at = NOW() WHERE id = :id";
                $params = [':id' => $id, ':name' => $name, ':group_id' => $group_id ?: null, ':group_name' => $group_name ?: null, ':updated_by' => $current_user];
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $conn->commit();
                json_response(true, 'อัปเดตข้อมูลสำเร็จ');
            } else {
                $sql = "INSERT INTO $table (name, group_id, group_name, created_by) VALUES (:name, :group_id, :group_name, :created_by)";
                $params = [':name' => $name, ':group_id' => $group_id ?: null, ':group_name' => $group_name ?: null, ':created_by' => $current_user];
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $conn->commit();
                json_response(true, 'เพิ่มข้อมูลสำเร็จ', ['id' => $conn->lastInsertId()]);
            }
        } else {
        if ($id > 0) {
            // Update
            $sql = "UPDATE $table SET name = :name, updated_by = :updated_by, updated_at = NOW() WHERE id = :id";
            $params = [':id' => $id, ':name' => $name, ':updated_by' => $current_user];
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            $conn->commit();
            json_response(true, 'อัปเดตข้อมูลสำเร็จ');
            
        } else {
            // Insert
            $sql = "INSERT INTO $table (name, created_by) VALUES (:name, :created_by)";
            $params = [':name' => $name, ':created_by' => $current_user];
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            $conn->commit();
            json_response(true, 'เพิ่มข้อมูลสำเร็จ', ['id' => $conn->lastInsertId()]);
        }
        }
        
    } catch (PDOException $e) {
        $conn->rollBack();
        json_response(false, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
    }
}

function deleteItem($conn, $type, $id) {
    $table = getTableName($type);
    if (!$table || !$id) {
        json_response(false, 'Invalid parameters');
        return;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM $table WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        json_response(true, 'ลบข้อมูลสำเร็จ');
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            json_response(false, 'ไม่สามารถลบได้ เนื่องจากมีข้อมูลที่เกี่ยวข้อง');
        } else {
            json_response(false, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}

function toggleStatus($conn, $type, $id) {
    $table = getTableName($type);
    if (!$table || !$id) {
        json_response(false, 'Invalid parameters');
        return;
    }
    
    $current_user = $_SESSION['technician_username'] ?? 'system';
    $sql = "UPDATE $table SET is_active = 1 - is_active, updated_by = :updated_by, updated_at = NOW() WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id, ':updated_by' => $current_user]);
    
    json_response(true, 'เปลี่ยนสถานะสำเร็จ');
}
?>
