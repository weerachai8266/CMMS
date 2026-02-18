<?php
require_once '../config/config.php';
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            listUsers($conn);
            break;
        case 'get':
            getUser($conn, $_GET['id'] ?? 0);
            break;
        case 'save':
            saveUser($conn);
            break;
        case 'delete':
            deleteUser($conn, $_POST['id'] ?? 0);
            break;
        case 'toggle_status':
            toggleStatus($conn, $_POST['id'] ?? 0);
            break;
        case 'reset_password':
            resetPassword($conn, $_POST['id'] ?? 0);
            break;
        case 'change_password':
            changePassword($conn);
            break;
        default:
            json_response(false, 'Invalid action');
    }
} catch (Exception $e) {
    http_response_code(500);
    json_response(false, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
}

function listUsers($conn) {
    $sql = "SELECT 
                id, username, full_name, email, phone, role,
                employee_id, department, branch, position,
                is_active, created_at, created_by, updated_at, updated_by, last_login,
                login_attempts, locked_until
            FROM mt_users 
            ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ซ่อนรหัสผ่าน
    foreach ($data as &$user) {
        unset($user['password']);
    }
    
    json_response(true, 'ดึงข้อมูลสำเร็จ', $data);
}

function getUser($conn, $id) {
    if (!$id) {
        json_response(false, 'Invalid parameters');
        return;
    }
    
    $stmt = $conn->prepare("
        SELECT 
            id, username, full_name, email, phone, role,
            employee_id, department, branch, position,
            is_active, created_at, created_by, updated_at, updated_by, last_login
        FROM mt_users 
        WHERE id = :id
    ");
    $stmt->execute([':id' => $id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        json_response(true, 'ดึงข้อมูลสำเร็จ', $data);
    } else {
        json_response(false, 'ไม่พบข้อมูล');
    }
}

function saveUser($conn) {
    $id = $_POST['user_id'] ?? $_POST['id'] ?? 0;
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'maintenance';
    $employee_id = trim($_POST['employee_id'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $branch = trim($_POST['branch'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $current_user = $_SESSION['technician_username'] ?? 'system';
    $current_role = $_SESSION['user_role'] ?? 'viewer';
    
    // ตรวจสอบสิทธิ์: เฉพาะ admin, manager และ staff เท่านั้น
    if (!in_array($current_role, ['admin', 'manager', 'staff'])) {
        json_response(false, 'คุณไม่มีสิทธิ์จัดการผู้ใช้');
        return;
    }
    
    // manager/staff ไม่สามารถแก้ไข user ที่เป็น admin ได้
    if (in_array($current_role, ['manager', 'staff']) && $id > 0) {
        $checkAdmin = $conn->prepare("SELECT role FROM mt_users WHERE id = :id");
        $checkAdmin->execute([':id' => $id]);
        $targetUser = $checkAdmin->fetch(PDO::FETCH_ASSOC);
        if ($targetUser && $targetUser['role'] === 'admin') {
            json_response(false, 'คุณไม่มีสิทธิ์แก้ไขผู้ดูแลระบบ');
            return;
        }
    }
    
    // manager/staff ไม่สามารถสร้าง user ที่เป็น admin ได้
    if (in_array($current_role, ['manager', 'staff']) && $role === 'admin') {
        json_response(false, 'คุณไม่มีสิทธิ์กำหนดบทบาทผู้ดูแลระบบ');
        return;
    }
    
    // Validate
    if (empty($username) || empty($full_name)) {
        json_response(false, 'กรุณากรอกชื่อผู้ใช้และชื่อ-นามสกุล');
        return;
    }
    
    if (!in_array($role, ['admin', 'manager', 'supervisor', 'leader', 'engineer', 'maintenance', 'technician', 'staff', 'viewer'])) {
        json_response(false, 'บทบาทไม่ถูกต้อง');
        return;
    }
    
    try {
        $conn->beginTransaction();
        
        if ($id > 0) {
            // Update - ตรวจสอบ username ซ้ำ (ยกเว้นตัวเอง)
            $checkStmt = $conn->prepare("SELECT id FROM mt_users WHERE username = :username AND id != :id");
            $checkStmt->execute([':username' => $username, ':id' => $id]);
            if ($checkStmt->fetch()) {
                $conn->rollBack();
                json_response(false, 'ชื่อผู้ใช้นี้มีอยู่แล้ว');
                return;
            }
            
            // Update user (ไม่รวม password ถ้าไม่ได้เปลี่ยน)
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE mt_users SET 
                    username = :username,
                    password = :password,
                    full_name = :full_name,
                    email = :email,
                    phone = :phone,
                    role = :role,
                    employee_id = :employee_id,
                    department = :department,
                    branch = :branch,
                    position = :position,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id";
                $params = [
                    ':id' => $id,
                    ':username' => $username,
                    ':password' => $hashed_password,
                    ':full_name' => $full_name,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':role' => $role,
                    ':employee_id' => $employee_id,
                    ':department' => $department,
                    ':branch' => $branch,
                    ':position' => $position,
                    ':updated_by' => $current_user
                ];
            } else {
                $sql = "UPDATE mt_users SET 
                    username = :username,
                    full_name = :full_name,
                    email = :email,
                    phone = :phone,
                    role = :role,
                    employee_id = :employee_id,
                    department = :department,
                    branch = :branch,
                    position = :position,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id";
                $params = [
                    ':id' => $id,
                    ':username' => $username,
                    ':full_name' => $full_name,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':role' => $role,
                    ':employee_id' => $employee_id,
                    ':department' => $department,
                    ':branch' => $branch,
                    ':position' => $position,
                    ':updated_by' => $current_user
                ];
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            $conn->commit();
            json_response(true, 'อัปเดตข้อมูลสำเร็จ');
            
        } else {
            // Insert - ต้องมีรหัสผ่าน
            if (empty($password)) {
                $conn->rollBack();
                json_response(false, 'กรุณากำหนดรหัสผ่าน');
                return;
            }
            
            // ตรวจสอบ username ซ้ำ
            $checkStmt = $conn->prepare("SELECT id FROM mt_users WHERE username = :username");
            $checkStmt->execute([':username' => $username]);
            if ($checkStmt->fetch()) {
                $conn->rollBack();
                json_response(false, 'ชื่อผู้ใช้นี้มีอยู่แล้ว');
                return;
            }
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO mt_users (
                username, password, full_name, email, phone, role,
                employee_id, department, branch, position,
                created_by, is_active
            ) VALUES (
                :username, :password, :full_name, :email, :phone, :role,
                :employee_id, :department, :branch, :position,
                :created_by, 1
            )";
            
            $params = [
                ':username' => $username,
                ':password' => $hashed_password,
                ':full_name' => $full_name,
                ':email' => $email,
                ':phone' => $phone,
                ':role' => $role,
                ':employee_id' => $employee_id,
                ':department' => $department,
                ':branch' => $branch,
                ':position' => $position,
                ':created_by' => $current_user
            ];
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            $conn->commit();
            json_response(true, 'เพิ่มผู้ใช้สำเร็จ', ['id' => $conn->lastInsertId()]);
        }
        
    } catch (PDOException $e) {
        $conn->rollBack();
        json_response(false, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
    }
}

function deleteUser($conn, $id) {
    if (!$id) {
        json_response(false, 'Invalid parameters');
        return;
    }
    
    $current_role = $_SESSION['user_role'] ?? 'viewer';
    
    // ตรวจสอบสิทธิ์: เฉพาะ admin, manager และ staff เท่านั้น
    if (!in_array($current_role, ['admin', 'manager', 'staff'])) {
        json_response(false, 'คุณไม่มีสิทธิ์ลบผู้ใช้');
        return;
    }
    
    // manager/staff ไม่สามารถลบ user ที่เป็น admin ได้
    if (in_array($current_role, ['manager', 'staff'])) {
        $checkAdmin = $conn->prepare("SELECT role FROM mt_users WHERE id = :id");
        $checkAdmin->execute([':id' => $id]);
        $targetUser = $checkAdmin->fetch(PDO::FETCH_ASSOC);
        if ($targetUser && $targetUser['role'] === 'admin') {
            json_response(false, 'คุณไม่มีสิทธิ์ลบผู้ดูแลระบบ');
            return;
        }
    }
    
    // ป้องกันไม่ให้ลบตัวเอง
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
        json_response(false, 'ไม่สามารถลบบัญชีของตัวเองได้');
        return;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM mt_users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        json_response(true, 'ลบผู้ใช้สำเร็จ');
    } catch (PDOException $e) {
        json_response(false, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
    }
}

function toggleStatus($conn, $id) {
    if (!$id) {
        json_response(false, 'Invalid parameters');
        return;
    }
    
    $current_role = $_SESSION['user_role'] ?? 'viewer';
    
    // ตรวจสอบสิทธิ์: เฉพาะ admin, manager และ staff เท่านั้น
    if (!in_array($current_role, ['admin', 'manager', 'staff'])) {
        json_response(false, 'คุณไม่มีสิทธิ์เปลี่ยนสถานะผู้ใช้');
        return;
    }
    
    // manager/staff ไม่สามารถเปลี่ยนสถานะ user ที่เป็น admin ได้
    if (in_array($current_role, ['manager', 'staff'])) {
        $checkAdmin = $conn->prepare("SELECT role FROM mt_users WHERE id = :id");
        $checkAdmin->execute([':id' => $id]);
        $targetUser = $checkAdmin->fetch(PDO::FETCH_ASSOC);
        if ($targetUser && $targetUser['role'] === 'admin') {
            json_response(false, 'คุณไม่มีสิทธิ์เปลี่ยนสถานะผู้ดูแลระบบ');
            return;
        }
    }
    
    // ป้องกันไม่ให้ปิดการใช้งานตัวเอง
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
        json_response(false, 'ไม่สามารถเปลี่ยนสถานะบัญชีของตัวเองได้');
        return;
    }
    
    $current_user = $_SESSION['technician_username'] ?? 'system';
    
    $sql = "UPDATE mt_users 
            SET is_active = 1 - is_active,
                updated_by = :updated_by,
                updated_at = NOW()
            WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id, ':updated_by' => $current_user]);
    
    json_response(true, 'เปลี่ยนสถานะสำเร็จ');
}

function resetPassword($conn, $id) {
    if (!$id) {
        json_response(false, 'Invalid parameters');
        return;
    }
    
    $current_role = $_SESSION['user_role'] ?? 'viewer';
    
    // ตรวจสอบสิทธิ์: เฉพาะ admin, manager และ staff เท่านั้น
    if (!in_array($current_role, ['admin', 'manager', 'staff'])) {
        json_response(false, 'คุณไม่มีสิทธิ์รีเซ็ตรหัสผ่าน');
        return;
    }
    
    // manager/staff ไม่สามารถรีเซ็ตรหัสผ่าน user ที่เป็น admin ได้
    if (in_array($current_role, ['manager', 'staff'])) {
        $checkAdmin = $conn->prepare("SELECT role FROM mt_users WHERE id = :id");
        $checkAdmin->execute([':id' => $id]);
        $targetUser = $checkAdmin->fetch(PDO::FETCH_ASSOC);
        if ($targetUser && $targetUser['role'] === 'admin') {
            json_response(false, 'คุณไม่มีสิทธิ์รีเซ็ตรหัสผ่านผู้ดูแลระบบ');
            return;
        }
    }
    
    $current_user = $_SESSION['technician_username'] ?? 'system';
    $new_password = 'password123'; // รหัสผ่านเริ่มต้น
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $sql = "UPDATE mt_users 
            SET password = :password,
                login_attempts = 0,
                locked_until = NULL,
                updated_by = :updated_by,
                updated_at = NOW()
            WHERE id = :id";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':password' => $hashed_password,
        ':updated_by' => $current_user,
        ':id' => $id
    ]);
    
    json_response(true, 'รีเซ็ตรหัสผ่านสำเร็จ รหัสผ่านใหม่: ' . $new_password);
}

function changePassword($conn) {
    $user_id = $_SESSION['user_id'] ?? 0;
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (!$user_id) {
        json_response(false, 'กรุณา login ก่อน');
        return;
    }
    
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        json_response(false, 'กรุณากรอกข้อมูลให้ครบถ้วน');
        return;
    }
    
    if ($new_password !== $confirm_password) {
        json_response(false, 'รหัสผ่านใหม่ไม่ตรงกัน');
        return;
    }
    
    if (strlen($new_password) < 6) {
        json_response(false, 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
        return;
    }
    
    try {
        // ตรวจสอบรหัสผ่านเดิม
        $stmt = $conn->prepare("SELECT password FROM mt_users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($old_password, $user['password'])) {
            json_response(false, 'รหัสผ่านเดิมไม่ถูกต้อง');
            return;
        }
        
        // เปลี่ยนรหัสผ่าน
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("
            UPDATE mt_users 
            SET password = :password,
                updated_at = NOW()
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':password' => $hashed_password,
            ':id' => $user_id
        ]);
        
        json_response(true, 'เปลี่ยนรหัสผ่านสำเร็จ');
        
    } catch (PDOException $e) {
        json_response(false, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
    }
}
?>
