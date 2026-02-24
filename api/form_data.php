<?php
/**
 * API: form_data.php
 * ดึงข้อมูลสำหรับ Cascading Dropdowns ในฟอร์มใบแจ้งซ่อม
 * 
 * Actions:
 *   divisions              - ดึงฝ่ายทั้งหมด
 *   departments            - ดึงหน่วยงาน (กรอง: division_id)
 *   branches               - ดึงสาขาทั้งหมด
 *   machines               - ดึงเครื่องจักร (กรอง: branch_id, department_id)
 *   issues                 - ดึงอาการเสียพร้อมหมวด
 *   action_types           - ดึงประเภทการดำเนินการ
 *   reporters              - ดึงผู้ใช้ที่ใช้แจ้งซ่อม
 *   technicians            - ดึงช่างทั้งหมด
 */

require_once '../config/config.php';
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {

        // ฝ่ายทั้งหมด
        case 'divisions':
            $stmt = $conn->query("
                SELECT id, name
                FROM mt_divisions
                WHERE is_active = 1
                ORDER BY sort_order, name
            ");
            json_response(true, 'ok', $stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        // หน่วยงาน (cascade จาก division)
        case 'departments':
            $division_id = (int)($_GET['division_id'] ?? 0);
            if ($division_id > 0) {
                $stmt = $conn->prepare("
                    SELECT id, name
                    FROM mt_departments
                    WHERE division_id = :div AND is_active = 1
                    ORDER BY sort_order, name
                ");
                $stmt->execute([':div' => $division_id]);
            } else {
                $stmt = $conn->query("
                    SELECT id, name
                    FROM mt_departments
                    WHERE is_active = 1
                    ORDER BY sort_order, name
                ");
            }
            json_response(true, 'ok', $stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        // สาขาทั้งหมด
        case 'branches':
            $stmt = $conn->query("
                SELECT id, code, name
                FROM mt_branches
                WHERE is_active = 1
                ORDER BY sort_order, name
            ");
            json_response(true, 'ok', $stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        // เครื่องจักร (cascade จาก branch และ/หรือ department)
        case 'machines':
            $branch_id     = (int)($_GET['branch_id']     ?? 0);
            $department_id = (int)($_GET['department_id'] ?? 0);

            $where   = ['m.is_active = 1', "m.status != 'scrapped'"];
            $params  = [];

            if ($branch_id > 0) {
                $where[]    = 'm.branch_id = :branch_id';
                $params[':branch_id'] = $branch_id;
            }
            if ($department_id > 0) {
                $where[]    = 'm.department_id = :dept_id';
                $params[':dept_id'] = $department_id;
            }

            $sql = "
                SELECT m.id, m.machine_code, m.machine_name, m.status,
                       mt.name AS machine_type
                FROM mt_machines m
                LEFT JOIN mt_machine_types mt ON mt.id = m.machine_type_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY m.machine_code ASC
            ";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            json_response(true, 'ok', $stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        // ข้อมูลเครื่องจักรเดี่ยว (by id)
        case 'machine_detail':
            $machine_id = (int)($_GET['machine_id'] ?? 0);
            if ($machine_id <= 0) {
                json_response(false, 'machine_id required');
                break;
            }
            $stmt = $conn->prepare("
                SELECT m.id, m.machine_code, m.machine_name, m.brand, m.model, m.location, m.status,
                       br.name AS branch_name,
                       dp.name AS department_name,
                       mt.name AS machine_type
                FROM mt_machines m
                LEFT JOIN mt_branches     br ON br.id = m.branch_id
                LEFT JOIN mt_departments  dp ON dp.id = m.department_id
                LEFT JOIN mt_machine_types mt ON mt.id = m.machine_type_id
                WHERE m.id = :id LIMIT 1
            ");
            $stmt->execute([':id' => $machine_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                json_response(true, 'ok', $row);
            } else {
                json_response(false, 'ไม่พบเครื่องจักร');
            }
            break;

        // อาการเสียพร้อมหมวด
        case 'issues':
            $stmt = $conn->query("
                SELECT i.id, i.name, ic.name AS category_name, ic.id AS category_id
                FROM mt_issues i
                LEFT JOIN mt_issue_categories ic ON ic.id = i.category_id
                WHERE i.is_active = 1
                ORDER BY ic.sort_order, i.sort_order, i.name
            ");
            json_response(true, 'ok', $stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        // ประเภทการดำเนินการ
        case 'action_types':
            $stmt = $conn->query("
                SELECT id, name, is_other
                FROM mt_action_types
                WHERE is_active = 1
                ORDER BY sort_order, name
            ");
            json_response(true, 'ok', $stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        // ผู้แจ้ง (reporter)
        case 'reporters':
            $stmt = $conn->query("
                SELECT id, full_name, department_id
                FROM mt_users
                WHERE is_active = 1
                ORDER BY full_name
            ");
            json_response(true, 'ok', $stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        // ช่าง (technician)
        case 'technicians':
            $stmt = $conn->query("
                SELECT id, full_name, branch_id
                FROM mt_users
                WHERE is_active = 1
                  AND role IN ('admin','manager','technician')
                ORDER BY full_name
            ");
            json_response(true, 'ok', $stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        default:
            json_response(false, 'Invalid action. Use: divisions, departments, branches, machines, machine_detail, issues, action_types, reporters, technicians');
    }
} catch (PDOException $e) {
    http_response_code(500);
    json_response(false, 'Database error: ' . $e->getMessage());
}
