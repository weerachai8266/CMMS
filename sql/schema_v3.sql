-- =====================================================
-- CMMS V3 - Complete Database Schema (Normalized)
-- วันที่สร้าง: 2026-02-24
-- หลักการ: เก็บเฉพาะ ID ในตาราง mt_repair (ไม่เก็บชื่อซ้ำ)
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. MASTER DATA TABLES
-- =====================================================

-- สาขา
CREATE TABLE IF NOT EXISTS mt_branches (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code       VARCHAR(20)  NOT NULL UNIQUE COMMENT 'รหัสสาขา (ใช้สร้าง document_no)',
    name       VARCHAR(255) NOT NULL COMMENT 'ชื่อสาขา',
    is_active  TINYINT(1)   DEFAULT 1,
    sort_order INT          DEFAULT 0,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='สาขา';

-- ฝ่าย
CREATE TABLE IF NOT EXISTS mt_divisions (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL COMMENT 'ชื่อฝ่าย',
    is_active  TINYINT(1)   DEFAULT 1,
    sort_order INT          DEFAULT 0,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ฝ่าย';

-- หน่วยงาน/แผนก
CREATE TABLE IF NOT EXISTS mt_departments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    division_id INT UNSIGNED NOT NULL COMMENT 'อยู่ในฝ่ายไหน',
    name        VARCHAR(255) NOT NULL COMMENT 'ชื่อหน่วยงาน',
    is_active   TINYINT(1)   DEFAULT 1,
    sort_order  INT          DEFAULT 0,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_division (division_id),
    FOREIGN KEY (division_id) REFERENCES mt_divisions(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='หน่วยงาน/แผนก';

-- =====================================================
-- 2. USERS
-- =====================================================

CREATE TABLE IF NOT EXISTS mt_users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id   VARCHAR(50)  DEFAULT NULL UNIQUE COMMENT 'รหัสพนักงาน',
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    full_name     VARCHAR(150) NOT NULL,
    email         VARCHAR(150) DEFAULT NULL,
    phone         VARCHAR(20)  DEFAULT NULL,
    role          ENUM('admin','manager','approver','technician','reporter','viewer') NOT NULL DEFAULT 'reporter',
    department_id INT UNSIGNED DEFAULT NULL,
    branch_id     INT UNSIGNED DEFAULT NULL,
    is_active     TINYINT(1)   DEFAULT 1,
    last_login    DATETIME     DEFAULT NULL,
    login_attempts INT         DEFAULT 0,
    locked_until  DATETIME     DEFAULT NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by    INT UNSIGNED DEFAULT NULL,
    INDEX idx_role (role),
    INDEX idx_department (department_id),
    INDEX idx_branch (branch_id),
    FOREIGN KEY (department_id) REFERENCES mt_departments(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id)     REFERENCES mt_branches(id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ผู้ใช้งานระบบ';

-- =====================================================
-- 3. MACHINES (ทะเบียนเครื่องจักร)
-- =====================================================

CREATE TABLE IF NOT EXISTS mt_machine_types (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(100) NOT NULL UNIQUE COMMENT 'ประเภทเครื่องจักร',
    is_active TINYINT(1)   DEFAULT 1,
    sort_order INT         DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ประเภทเครื่องจักร';

CREATE TABLE IF NOT EXISTS mt_machines (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    machine_type_id INT UNSIGNED DEFAULT NULL,
    machine_code    VARCHAR(50)  NOT NULL UNIQUE COMMENT 'รหัสเครื่องจักร',
    machine_name    VARCHAR(255) NOT NULL COMMENT 'ชื่อเครื่องจักร',
    branch_id       INT UNSIGNED DEFAULT NULL COMMENT 'สาขาที่เครื่องอยู่',
    department_id   INT UNSIGNED DEFAULT NULL COMMENT 'หน่วยงานที่รับผิดชอบ',
    brand           VARCHAR(100) DEFAULT NULL,
    model           VARCHAR(100) DEFAULT NULL,
    serial_no       VARCHAR(100) DEFAULT NULL,
    manufacture_year YEAR        DEFAULT NULL,
    purchase_date   DATE         DEFAULT NULL,
    purchase_price  DECIMAL(12,2) DEFAULT NULL,
    start_date      DATE         DEFAULT NULL COMMENT 'วันที่เริ่มใช้งาน',
    warranty_expire DATE         DEFAULT NULL,
    location        VARCHAR(255) DEFAULT NULL COMMENT 'ตำแหน่งที่ตั้ง',
    qr_code         VARCHAR(255) DEFAULT NULL COMMENT 'QR Code URL',
    image           VARCHAR(500) DEFAULT NULL,
    status          ENUM('active','inactive','under_repair','scrapped') DEFAULT 'active',
    is_active       TINYINT(1)   DEFAULT 1,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by      INT UNSIGNED DEFAULT NULL,
    INDEX idx_machine_code (machine_code),
    INDEX idx_branch (branch_id),
    INDEX idx_department (department_id),
    INDEX idx_status (status),
    FOREIGN KEY (branch_id)       REFERENCES mt_branches(id)      ON DELETE SET NULL,
    FOREIGN KEY (department_id)   REFERENCES mt_departments(id)   ON DELETE SET NULL,
    FOREIGN KEY (machine_type_id) REFERENCES mt_machine_types(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ทะเบียนเครื่องจักร';

-- =====================================================
-- 4. REPAIR FORM MASTER DATA
-- =====================================================

-- ประเภทการดำเนินการ (ตรวจสอบ / แก้ไข / ซ่อม / อื่นๆ)
CREATE TABLE IF NOT EXISTS mt_action_types (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(100) NOT NULL UNIQUE,
    is_other  TINYINT(1)   DEFAULT 0 COMMENT '1 = ต้องกรอกรายละเอียดเพิ่ม',
    is_active TINYINT(1)   DEFAULT 1,
    sort_order INT         DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ประเภทการดำเนินการ';

-- หมวดอาการเสีย
CREATE TABLE IF NOT EXISTS mt_issue_categories (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(100) NOT NULL UNIQUE,
    is_active TINYINT(1)   DEFAULT 1,
    sort_order INT         DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='หมวดอาการเสีย';

-- อาการเสีย/ปัญหา
CREATE TABLE IF NOT EXISTS mt_issues (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED DEFAULT NULL,
    name        VARCHAR(255) NOT NULL COMMENT 'ชื่ออาการเสีย',
    is_active   TINYINT(1)   DEFAULT 1,
    sort_order  INT          DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES mt_issue_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='อาการเสีย';

-- =====================================================
-- 5. REPAIR ORDERS (ใบแจ้งซ่อม)
-- =====================================================

CREATE TABLE IF NOT EXISTS mt_repair (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_no       VARCHAR(30)  NOT NULL UNIQUE COMMENT 'เลขที่เอกสาร เช่น ACP001/68',

    -- ข้อมูลผู้แจ้ง (ID เท่านั้น)
    branch_id         INT UNSIGNED NOT NULL,
    division_id       INT UNSIGNED DEFAULT NULL,
    department_id     INT UNSIGNED DEFAULT NULL,

    -- เครื่องจักร (ID เท่านั้น)
    machine_id        INT UNSIGNED NOT NULL,

    -- อาการเสีย
    issue_id          INT UNSIGNED DEFAULT NULL COMMENT 'เลือกจากรายการ',
    issue_detail      TEXT         DEFAULT NULL COMMENT 'รายละเอียดเพิ่มเติม / กรณีไม่มีในรายการ',

    -- ประเภทการดำเนินการ
    action_type_id    INT UNSIGNED DEFAULT NULL,
    action_detail     VARCHAR(255) DEFAULT NULL COMMENT 'กรณีเลือก อื่นๆ',

    -- ความเร่งด่วน
    priority          ENUM('urgent','normal') NOT NULL DEFAULT 'urgent',

    -- รูปภาพ
    image_before      VARCHAR(500) DEFAULT NULL,
    image_after       VARCHAR(500) DEFAULT NULL,

    -- ผู้เกี่ยวข้อง (ID เท่านั้น)
    reported_by_id    INT UNSIGNED DEFAULT NULL,
    reported_by_name  VARCHAR(100) DEFAULT NULL COMMENT 'ชื่อผู้แจ้ง (กรณีไม่มีในระบบ)',
    handled_by_id     INT UNSIGNED DEFAULT NULL,
    approver_id       INT UNSIGNED DEFAULT NULL,

    -- สถานะ
    status            SMALLINT UNSIGNED NOT NULL DEFAULT 10
                      COMMENT '10=รออนุมัติ,11=ไม่อนุมัติ,20=รอดำเนินการ,30=รออะไหล่,40=เสร็จสิ้น,50=ยกเลิก',

    -- เวลา
    start_job         DATETIME     DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่แจ้ง',
    approved_at       DATETIME     DEFAULT NULL,
    completed_at      DATETIME     DEFAULT NULL,

    -- บันทึกเพิ่มเติม
    mt_report         TEXT         DEFAULT NULL COMMENT 'สรุปการซ่อม',
    reject_reason     TEXT         DEFAULT NULL,
    receiver_name     VARCHAR(100) DEFAULT NULL COMMENT 'ผู้รับงาน',
    job_status_note   VARCHAR(255) DEFAULT NULL,

    created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_document_no  (document_no),
    INDEX idx_branch       (branch_id),
    INDEX idx_department   (department_id),
    INDEX idx_machine      (machine_id),
    INDEX idx_status       (status),
    INDEX idx_start_job    (start_job),
    INDEX idx_reported_by  (reported_by_id),

    FOREIGN KEY (branch_id)      REFERENCES mt_branches(id)     ON DELETE RESTRICT,
    FOREIGN KEY (division_id)    REFERENCES mt_divisions(id)    ON DELETE SET NULL,
    FOREIGN KEY (department_id)  REFERENCES mt_departments(id)  ON DELETE SET NULL,
    FOREIGN KEY (machine_id)     REFERENCES mt_machines(id)     ON DELETE RESTRICT,
    FOREIGN KEY (issue_id)       REFERENCES mt_issues(id)       ON DELETE SET NULL,
    FOREIGN KEY (action_type_id) REFERENCES mt_action_types(id) ON DELETE SET NULL,
    FOREIGN KEY (reported_by_id) REFERENCES mt_users(id)        ON DELETE SET NULL,
    FOREIGN KEY (handled_by_id)  REFERENCES mt_users(id)        ON DELETE SET NULL,
    FOREIGN KEY (approver_id)    REFERENCES mt_users(id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ใบแจ้งซ่อม';

-- =====================================================
-- 6. APPROVAL LOG
-- =====================================================

CREATE TABLE IF NOT EXISTS mt_approval_log (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repair_id    INT UNSIGNED NOT NULL,
    approver_id  INT UNSIGNED DEFAULT NULL,
    approver_name VARCHAR(100) DEFAULT NULL,
    action       ENUM('approved','rejected','cancelled') NOT NULL,
    reason       TEXT         DEFAULT NULL,
    ip_address   VARCHAR(45)  DEFAULT NULL,
    user_agent   VARCHAR(500) DEFAULT NULL,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_repair (repair_id),
    FOREIGN KEY (repair_id)   REFERENCES mt_repair(id)  ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES mt_users(id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ประวัติการอนุมัติ';

-- =====================================================
-- 7. MACHINE HISTORY (ประวัติการซ่อมและ PM)
-- =====================================================

CREATE TABLE IF NOT EXISTS mt_machine_history (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repair_id       INT UNSIGNED DEFAULT NULL COMMENT 'อ้างอิงใบแจ้งซ่อม (ถ้ามี)',
    machine_id      INT UNSIGNED NOT NULL,
    history_type    ENUM('repair','pm','calibration','inspection','overhaul') NOT NULL DEFAULT 'repair',
    document_no     VARCHAR(50)  DEFAULT NULL,
    work_date       DATE         NOT NULL,
    completed_date  DATE         DEFAULT NULL,
    issue_desc      TEXT         DEFAULT NULL,
    solution_desc   TEXT         DEFAULT NULL,
    work_hours      DECIMAL(6,2) DEFAULT 0,
    downtime_hours  DECIMAL(6,2) DEFAULT 0,
    labor_cost      DECIMAL(12,2) DEFAULT 0,
    parts_cost      DECIMAL(12,2) DEFAULT 0,
    other_cost      DECIMAL(12,2) DEFAULT 0,
    total_cost      DECIMAL(12,2) GENERATED ALWAYS AS (labor_cost + parts_cost + other_cost) STORED,
    reported_by_id  INT UNSIGNED DEFAULT NULL,
    handled_by_id   INT UNSIGNED DEFAULT NULL,
    note            TEXT         DEFAULT NULL,
    status          ENUM('completed','cancelled') DEFAULT 'completed',
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by      INT UNSIGNED DEFAULT NULL,
    INDEX idx_machine  (machine_id),
    INDEX idx_repair   (repair_id),
    INDEX idx_work_date (work_date),
    FOREIGN KEY (machine_id)     REFERENCES mt_machines(id) ON DELETE RESTRICT,
    FOREIGN KEY (repair_id)      REFERENCES mt_repair(id)   ON DELETE SET NULL,
    FOREIGN KEY (reported_by_id) REFERENCES mt_users(id)    ON DELETE SET NULL,
    FOREIGN KEY (handled_by_id)  REFERENCES mt_users(id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ประวัติการซ่อมและบำรุงรักษาเครื่องจักร';

-- =====================================================
-- 8. SPARE PARTS (คลังอะไหล่)
-- =====================================================

-- ผู้จำหน่าย/ซัพพลายเออร์
CREATE TABLE IF NOT EXISTS mt_suppliers (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code         VARCHAR(20)  NOT NULL UNIQUE,
    name         VARCHAR(255) NOT NULL,
    contact_name VARCHAR(100) DEFAULT NULL,
    phone        VARCHAR(50)  DEFAULT NULL,
    email        VARCHAR(150) DEFAULT NULL,
    address      TEXT         DEFAULT NULL,
    tax_id       VARCHAR(20)  DEFAULT NULL,
    is_active    TINYINT(1)   DEFAULT 1,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ผู้จำหน่ายอะไหล่';

-- หมวดหมู่อะไหล่
CREATE TABLE IF NOT EXISTS mt_spare_part_categories (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(100) NOT NULL UNIQUE,
    is_active TINYINT(1)   DEFAULT 1,
    sort_order INT         DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='หมวดหมู่อะไหล่';

-- อะไหล่
CREATE TABLE IF NOT EXISTS mt_spare_parts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id  INT UNSIGNED DEFAULT NULL,
    supplier_id  INT UNSIGNED DEFAULT NULL,
    code         VARCHAR(50)  NOT NULL UNIQUE COMMENT 'รหัสอะไหล่',
    name         VARCHAR(255) NOT NULL COMMENT 'ชื่ออะไหล่',
    description  TEXT         DEFAULT NULL,
    unit         VARCHAR(20)  NOT NULL DEFAULT 'ชิ้น' COMMENT 'หน่วยนับ',
    unit_price   DECIMAL(12,2) DEFAULT 0 COMMENT 'ราคาต่อหน่วย',
    stock_qty    DECIMAL(10,2) DEFAULT 0 COMMENT 'จำนวนคงเหลือ',
    min_qty      DECIMAL(10,2) DEFAULT 0 COMMENT 'จำนวนต่ำสุด (alert)',
    max_qty      DECIMAL(10,2) DEFAULT 0 COMMENT 'จำนวนสูงสุด',
    location     VARCHAR(100) DEFAULT NULL COMMENT 'ตำแหน่งในคลัง',
    image        VARCHAR(500) DEFAULT NULL,
    is_active    TINYINT(1)   DEFAULT 1,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by   INT UNSIGNED DEFAULT NULL,
    INDEX idx_code     (code),
    INDEX idx_category (category_id),
    INDEX idx_supplier (supplier_id),
    FOREIGN KEY (category_id) REFERENCES mt_spare_part_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES mt_suppliers(id)             ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='คลังอะไหล่';

-- การเคลื่อนไหวอะไหล่ (รับ/เบิก/คืน/ปรับ)
CREATE TABLE IF NOT EXISTS mt_spare_part_transactions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    spare_part_id   INT UNSIGNED NOT NULL,
    transaction_type ENUM('in','out','return','adjust') NOT NULL COMMENT 'รับ/เบิก/คืน/ปรับ',
    qty             DECIMAL(10,2) NOT NULL COMMENT 'จำนวน (บวก=เข้า, ลบ=ออก)',
    unit_price      DECIMAL(12,2) DEFAULT 0,
    total_price     DECIMAL(12,2) GENERATED ALWAYS AS (qty * unit_price) STORED,
    repair_id       INT UNSIGNED DEFAULT NULL COMMENT 'อ้างอิงใบแจ้งซ่อม',
    po_item_id      INT UNSIGNED DEFAULT NULL COMMENT 'อ้างอิงใบสั่งซื้อ',
    note            VARCHAR(255) DEFAULT NULL,
    ref_doc         VARCHAR(100) DEFAULT NULL COMMENT 'เลขที่อ้างอิง',
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    created_by      INT UNSIGNED DEFAULT NULL,
    INDEX idx_spare_part (spare_part_id),
    INDEX idx_repair     (repair_id),
    INDEX idx_type       (transaction_type),
    FOREIGN KEY (spare_part_id) REFERENCES mt_spare_parts(id) ON DELETE RESTRICT,
    FOREIGN KEY (repair_id)     REFERENCES mt_repair(id)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='การเคลื่อนไหวอะไหล่';

-- อะไหล่ที่ใช้ต่อใบแจ้งซ่อม
CREATE TABLE IF NOT EXISTS mt_repair_parts (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repair_id     INT UNSIGNED NOT NULL,
    spare_part_id INT UNSIGNED NOT NULL,
    qty_used      DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price    DECIMAL(12,2) DEFAULT 0,
    total_price   DECIMAL(12,2) GENERATED ALWAYS AS (qty_used * unit_price) STORED,
    note          VARCHAR(255) DEFAULT NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    created_by    INT UNSIGNED DEFAULT NULL,
    INDEX idx_repair     (repair_id),
    INDEX idx_spare_part (spare_part_id),
    FOREIGN KEY (repair_id)     REFERENCES mt_repair(id)      ON DELETE CASCADE,
    FOREIGN KEY (spare_part_id) REFERENCES mt_spare_parts(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='อะไหล่ที่ใช้ต่อใบแจ้งซ่อม';

-- =====================================================
-- 9. PURCHASE ORDERS (ใบสั่งซื้อ)
-- =====================================================

CREATE TABLE IF NOT EXISTS mt_purchase_orders (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_no        VARCHAR(30)  NOT NULL UNIQUE COMMENT 'เลขที่ PO',
    supplier_id  INT UNSIGNED NOT NULL,
    status       ENUM('draft','sent','partial','received','cancelled') DEFAULT 'draft',
    order_date   DATE         NOT NULL,
    expect_date  DATE         DEFAULT NULL COMMENT 'วันที่คาดว่าจะได้รับ',
    received_date DATE        DEFAULT NULL,
    total_amount DECIMAL(14,2) DEFAULT 0,
    note         TEXT         DEFAULT NULL,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    created_by   INT UNSIGNED DEFAULT NULL,
    approved_by  INT UNSIGNED DEFAULT NULL,
    approved_at  DATETIME     DEFAULT NULL,
    INDEX idx_supplier (supplier_id),
    INDEX idx_status   (status),
    FOREIGN KEY (supplier_id) REFERENCES mt_suppliers(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ใบสั่งซื้ออะไหล่';

CREATE TABLE IF NOT EXISTS mt_purchase_order_items (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_id         INT UNSIGNED NOT NULL,
    spare_part_id INT UNSIGNED NOT NULL,
    qty_ordered   DECIMAL(10,2) NOT NULL,
    qty_received  DECIMAL(10,2) DEFAULT 0,
    unit_price    DECIMAL(12,2) DEFAULT 0,
    total_price   DECIMAL(12,2) GENERATED ALWAYS AS (qty_ordered * unit_price) STORED,
    note          VARCHAR(255) DEFAULT NULL,
    INDEX idx_po         (po_id),
    INDEX idx_spare_part (spare_part_id),
    FOREIGN KEY (po_id)         REFERENCES mt_purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (spare_part_id) REFERENCES mt_spare_parts(id)     ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='รายการในใบสั่งซื้อ';

-- =====================================================
-- 10. PM SCHEDULES (ซ่อมบำรุงเชิงป้องกัน)
-- =====================================================

-- แม่แบบ PM (template สำหรับเครื่องแต่ละประเภท)
CREATE TABLE IF NOT EXISTS mt_pm_templates (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    machine_type_id INT UNSIGNED DEFAULT NULL,
    name            VARCHAR(255) NOT NULL,
    description     TEXT         DEFAULT NULL,
    interval_type   ENUM('daily','weekly','monthly','quarterly','yearly','hours') NOT NULL DEFAULT 'monthly',
    interval_value  INT          NOT NULL DEFAULT 1 COMMENT 'ทุก N ครั้งของ interval_type',
    estimated_hours DECIMAL(5,2) DEFAULT NULL,
    is_active       TINYINT(1)   DEFAULT 1,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_type_id) REFERENCES mt_machine_types(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='แม่แบบ PM';

-- รายการงาน PM ในแต่ละแม่แบบ
CREATE TABLE IF NOT EXISTS mt_pm_template_tasks (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_id INT UNSIGNED NOT NULL,
    task_order  INT          DEFAULT 0,
    task_name   VARCHAR(255) NOT NULL,
    task_detail TEXT         DEFAULT NULL,
    is_required TINYINT(1)   DEFAULT 1,
    FOREIGN KEY (template_id) REFERENCES mt_pm_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='รายการงาน PM';

-- ตาราง PM ที่นัดไว้
CREATE TABLE IF NOT EXISTS mt_pm_schedules (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    machine_id   INT UNSIGNED NOT NULL,
    template_id  INT UNSIGNED DEFAULT NULL,
    scheduled_date DATE        NOT NULL COMMENT 'กำหนดทำ PM วันที่',
    due_date     DATE         DEFAULT NULL COMMENT 'ไม่ควรเกินวันที่',
    assigned_to  INT UNSIGNED DEFAULT NULL COMMENT 'ช่างที่รับผิดชอบ (user_id)',
    status       ENUM('pending','in_progress','completed','overdue','cancelled') DEFAULT 'pending',
    note         TEXT         DEFAULT NULL,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_machine  (machine_id),
    INDEX idx_schedule (scheduled_date),
    INDEX idx_status   (status),
    FOREIGN KEY (machine_id)  REFERENCES mt_machines(id)     ON DELETE RESTRICT,
    FOREIGN KEY (template_id) REFERENCES mt_pm_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES mt_users(id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางนัดหมาย PM';

-- บันทึกการทำ PM จริง
CREATE TABLE IF NOT EXISTS mt_pm_records (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    schedule_id  INT UNSIGNED DEFAULT NULL,
    machine_id   INT UNSIGNED NOT NULL,
    template_id  INT UNSIGNED DEFAULT NULL,
    work_date    DATE         NOT NULL,
    completed_at DATETIME     DEFAULT NULL,
    technician_id INT UNSIGNED DEFAULT NULL,
    work_hours   DECIMAL(5,2) DEFAULT 0,
    parts_cost   DECIMAL(12,2) DEFAULT 0,
    labor_cost   DECIMAL(12,2) DEFAULT 0,
    summary      TEXT         DEFAULT NULL,
    note         TEXT         DEFAULT NULL,
    status       ENUM('completed','partial','cancelled') DEFAULT 'completed',
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    created_by   INT UNSIGNED DEFAULT NULL,
    INDEX idx_machine  (machine_id),
    INDEX idx_schedule (schedule_id),
    FOREIGN KEY (machine_id)   REFERENCES mt_machines(id)      ON DELETE RESTRICT,
    FOREIGN KEY (schedule_id)  REFERENCES mt_pm_schedules(id)  ON DELETE SET NULL,
    FOREIGN KEY (technician_id) REFERENCES mt_users(id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='บันทึกการทำ PM จริง';

-- =====================================================
-- 11. DEFAULT DATA
-- =====================================================

-- สาขา
INSERT IGNORE INTO mt_branches (code, name, sort_order) VALUES
('ACP',    'ACP',    1),
('ASP',    'ASP',    2),
('DC',     'DC',     3),
('FUR',    'FUR',    4),
('CENTER', 'CENTER', 5);

-- ฝ่าย
INSERT IGNORE INTO mt_divisions (name, sort_order) VALUES
('ผลิต',    1),
('คุณภาพ',  2),
('สโตร์',   3),
('ขนส่ง',   4),
('MT',      5);

-- หน่วยงาน (division_id: 1=ผลิต, 2=คุณภาพ, 3=สโตร์, 4=ขนส่ง, 5=MT)
INSERT IGNORE INTO mt_departments (division_id, name, sort_order) VALUES
(1, 'ผลิต 1',   1),
(1, 'ผลิต 2',   2),
(1, 'ผลิต 3',   3),
(2, 'QC',       1),
(3, 'สโตร์',    1),
(4, 'ขนส่ง',    1),
(5, 'MT',       1);

-- ประเภทการดำเนินการ
INSERT IGNORE INTO mt_action_types (name, is_other, sort_order) VALUES
('ตรวจสอบ',   0, 1),
('แก้ไขปัญหา', 0, 2),
('ซ่อม',      0, 3),
('อื่นๆ',     1, 4);

-- หมวดอาการเสีย
INSERT IGNORE INTO mt_issue_categories (name, sort_order) VALUES
('ไฟฟ้า',     1),
('เครื่องกล', 2),
('ไฮดรอลิก',  3),
('นิวแมติก',  4),
('อื่นๆ',    5);

-- อาการเสีย
INSERT IGNORE INTO mt_issues (category_id, name, sort_order) VALUES
(1, 'ไม่มีไฟ / ไม่ทำงาน',      1),
(1, 'ฟิวส์ขาด',                2),
(1, 'มอเตอร์เสีย',             3),
(1, 'Circuit breaker ตัด',     4),
(2, 'เสียงผิดปกติ',             1),
(2, 'สายพานขาด/หลุด',          2),
(2, 'เพลาหัก/บิด',             3),
(2, 'ลูกปืนเสีย',              4),
(2, 'แตกร้าว/รั่ว',            5),
(3, 'น้ำมันรั่ว',              1),
(3, 'แรงดันตก',                2),
(4, 'ลมรั่ว',                  1),
(4, 'วาล์วเสีย',               2),
(5, 'ตรวจสอบตามรอบ',           1),
(5, 'อื่นๆ',                   2);

-- ประเภทเครื่องจักร
INSERT IGNORE INTO mt_machine_types (name, sort_order) VALUES
('Compressor',    1),
('Pump',          2),
('Motor',         3),
('Conveyor',      4),
('CNC Machine',   5),
('Forklift',      6),
('Generator',     7),
('HVAC',          8),
('เครื่องจักรทั่วไป', 9);

-- =====================================================
-- 12. VIEWS ที่ใช้บ่อย
-- =====================================================

-- View: ใบแจ้งซ่อมพร้อมชื่อ (JOIN ทุก FK)
CREATE OR REPLACE VIEW v_repair_full AS
SELECT
    r.id,
    r.document_no,
    r.status,
    r.priority,
    r.start_job,
    r.approved_at,
    r.completed_at,
    r.image_before,
    r.image_after,
    r.issue_detail,
    r.action_detail,
    r.mt_report,
    r.reject_reason,
    r.receiver_name,
    r.job_status_note,
    -- สาขา
    br.code     AS branch_code,
    br.name     AS branch_name,
    -- ฝ่าย / หน่วยงาน
    dv.name     AS division_name,
    dp.name     AS department_name,
    -- เครื่องจักร
    m.machine_code,
    m.machine_name,
    -- อาการเสีย
    ic.name     AS issue_category,
    iss.name    AS issue_name,
    -- ประเภทดำเนินการ
    at.name     AS action_type_name,
    at.is_other AS action_is_other,
    -- ผู้เกี่ยวข้อง
    ru.full_name  AS reported_by,
    r.reported_by_name,
    hu.full_name  AS handled_by,
    au.full_name  AS approved_by,
    r.created_at,
    r.updated_at
FROM mt_repair r
LEFT JOIN mt_branches     br  ON br.id  = r.branch_id
LEFT JOIN mt_divisions    dv  ON dv.id  = r.division_id
LEFT JOIN mt_departments  dp  ON dp.id  = r.department_id
LEFT JOIN mt_machines     m   ON m.id   = r.machine_id
LEFT JOIN mt_issue_categories ic ON ic.id = (SELECT category_id FROM mt_issues WHERE id = r.issue_id LIMIT 1)
LEFT JOIN mt_issues       iss ON iss.id = r.issue_id
LEFT JOIN mt_action_types at  ON at.id  = r.action_type_id
LEFT JOIN mt_users        ru  ON ru.id  = r.reported_by_id
LEFT JOIN mt_users        hu  ON hu.id  = r.handled_by_id
LEFT JOIN mt_users        au  ON au.id  = r.approver_id;

-- View: Stock ที่ต่ำกว่า minimum
CREATE OR REPLACE VIEW v_low_stock AS
SELECT
    sp.id, sp.code, sp.name, sp.unit,
    sp.stock_qty, sp.min_qty,
    (sp.min_qty - sp.stock_qty) AS shortage_qty,
    spc.name AS category_name,
    sup.name AS supplier_name,
    sup.phone AS supplier_phone
FROM mt_spare_parts sp
LEFT JOIN mt_spare_part_categories spc ON spc.id = sp.category_id
LEFT JOIN mt_suppliers sup ON sup.id = sp.supplier_id
WHERE sp.stock_qty <= sp.min_qty AND sp.is_active = 1
ORDER BY shortage_qty DESC;

-- View: PM ที่ใกล้ครบกำหนด (ภายใน 7 วัน) หรือเกินกำหนด
CREATE OR REPLACE VIEW v_pm_due AS
SELECT
    ps.id, ps.scheduled_date, ps.due_date, ps.status,
    m.machine_code, m.machine_name,
    br.name AS branch_name,
    dp.name AS department_name,
    u.full_name AS assigned_to_name,
    DATEDIFF(ps.scheduled_date, CURDATE()) AS days_until_due
FROM mt_pm_schedules ps
JOIN  mt_machines   m  ON m.id  = ps.machine_id
LEFT JOIN mt_branches   br ON br.id = m.branch_id
LEFT JOIN mt_departments dp ON dp.id = m.department_id
LEFT JOIN mt_users      u  ON u.id  = ps.assigned_to
WHERE ps.status IN ('pending', 'in_progress')
  AND ps.scheduled_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
ORDER BY ps.scheduled_date ASC;

-- =============================================
-- DEFAULT USERS (สำหรับ fresh install)
-- admin / admin1234 | mt / mt1234
-- =============================================
INSERT IGNORE INTO mt_users (username, password, full_name, role, is_active) VALUES
('admin', '$2y$10$6uE9cQI/SbhaJA9iY7UAU.yb9SxCD3n6lxSGrBCFdaNoTM.UFnalO', 'ผู้ดูแลระบบ', 'admin', 1),
('mt',    '$2y$10$vqIrU119rmUWFY2eUXGtQ.X3U0HmU02la8uiZeqIZcKS.G71fqIr6', 'ช่าง MT',     'technician', 1);

SET FOREIGN_KEY_CHECKS = 1;
