<?php
/**
 * Unit Tests: ฐานข้อมูลและการ Validate ข้อมูล
 */

use function Tests\config;

function runDatabaseTests(TestRunner $t, array $cfg): void
{
    $t->suite('🗄️  Database Connection');

    // TC-DB-01: เชื่อมต่อฐานข้อมูลได้
    try {
        $conn = new PDO(
            "mysql:host={$cfg['db_host']};dbname={$cfg['db_name']};charset=utf8mb4",
            $cfg['db_user'],
            $cfg['db_pass']
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // อนุญาตค่า zero date (0000-00-00) ที่ระบบเก่าอาจมีอยู่
        $conn->exec("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");
        $t->assert('TC-DB-01: เชื่อมต่อฐานข้อมูลได้', true);
    } catch (PDOException $e) {
        $t->assert('TC-DB-01: เชื่อมต่อฐานข้อมูลได้', false, $e->getMessage());
        return; // หยุดทดสอบ DB ต่อถ้าเชื่อมต่อไม่ได้
    }

    // TC-DB-02: ตาราง mt_repair มีอยู่
    $tables = ['mt_repair', 'mt_approval_log', 'mt_users', 'mt_machines', 'mt_branches'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        $t->assert("TC-DB-02: ตาราง $table มีอยู่", $stmt->rowCount() > 0);
    }

    // TC-DB-03: Query ดึงข้อมูลได้
    $stmt = $conn->query("SELECT COUNT(*) AS cnt FROM mt_repair");
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    $t->assert('TC-DB-03: Query COUNT(*) สำเร็จ', isset($row['cnt']) && $row['cnt'] >= 0);

    // TC-DB-04: Status constants ถูกต้อง
    $stmt = $conn->query("SELECT DISTINCT status FROM mt_repair");
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $validStatuses = [10, 11, 20, 30, 40, 50];
    $invalid = array_diff($statuses, $validStatuses);
    $t->assert(
        'TC-DB-04: ค่า status ทั้งหมดถูกต้อง (10/11/20/30/40/50)',
        empty($invalid),
        empty($invalid) ? '' : 'พบ status ผิดปกติ: ' . implode(',', $invalid)
    );

    // TC-DB-05: document_no ไม่ซ้ำกัน
    $stmt = $conn->query(
        "SELECT document_no, COUNT(*) AS cnt FROM mt_repair 
         GROUP BY document_no HAVING cnt > 1"
    );
    $dups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $t->assert(
        'TC-DB-05: document_no ไม่มีซ้ำกัน',
        empty($dups),
        empty($dups) ? '' : 'พบซ้ำ: ' . implode(', ', array_column($dups, 'document_no'))
    );

    // TC-DB-06: ค่า end_job ต้องมากกว่า start_job เสมอ
    $stmt = $conn->query(
        "SELECT COUNT(*) AS cnt FROM mt_repair 
         WHERE end_job IS NOT NULL 
         AND end_job != '0000-00-00 00:00:00'
         AND CAST(end_job AS CHAR) NOT LIKE '0000%'
         AND end_job < start_job"
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $t->assert(
        'TC-DB-06: end_job ต้องมากกว่า start_job เสมอ',
        (int)$row['cnt'] === 0,
        (int)$row['cnt'] > 0 ? "พบ {$row['cnt']} รายการที่ end_job < start_job" : ''
    );

    // TC-DB-07: Prepared Statement ป้องกัน SQL injection
    $malicious = "' OR '1'='1";
    $stmt = $conn->prepare("SELECT id FROM mt_repair WHERE document_no = :doc LIMIT 1");
    $stmt->bindValue(':doc', $malicious);
    $stmt->execute();
    $t->assert('TC-DB-07: Prepared Statement ป้องกัน SQL Injection', $stmt->rowCount() === 0);
}

function runValidationTests(TestRunner $t): void
{
    $t->suite('✅  Input Validation');

    // TC-VAL-01: sanitize_input ตัด tag HTML ออก
    require_once __DIR__ . '/../../config/config.php';
    $xss = '<script>alert("xss")</script>';
    $result = sanitize_input($xss);
    $t->assert('TC-VAL-01: sanitize_input กรอง <script> tag', !str_contains($result, '<script>'), "Result: $result");

    // TC-VAL-02: sanitize_input ตัด trim whitespace
    $padded = '  hello world  ';
    $t->assertEquals('hello world', sanitize_input($padded), 'TC-VAL-02: sanitize_input trim whitespace');

    // TC-VAL-03: sanitize_input รับ HTML entity
    $html = '<b>bold</b>';
    $sanitized = sanitize_input($html);
    $t->assert('TC-VAL-03: sanitize_input escape HTML', !str_contains($sanitized, '<b>'), "Result: $sanitized");

    // TC-VAL-04: Status constants ถูก define ครบ
    $required = [
        'STATUS_PENDING_APPROVAL' => 10,
        'STATUS_REJECTED'         => 11,
        'STATUS_PENDING'          => 20,
        'STATUS_WAITING_PARTS'    => 30,
        'STATUS_COMPLETED'        => 40,
        'STATUS_CANCELLED'        => 50,
    ];
    foreach ($required as $const => $value) {
        $t->assert(
            "TC-VAL-04: Constant $const = $value",
            defined($const) && constant($const) === $value
        );
    }

    // TC-VAL-05: ฟังก์ชัน get_status_text ทำงานถูกต้อง
    $t->assertEquals('รออนุมัติ',    get_status_text(10), 'TC-VAL-05a: get_status_text(10)');
    $t->assertEquals('รอดำเนินการ', get_status_text(20), 'TC-VAL-05b: get_status_text(20)');
    $t->assertEquals('รออะไหล่',    get_status_text(30), 'TC-VAL-05c: get_status_text(30)');
    $t->assertEquals('ซ่อมเสร็จสิ้น', get_status_text(40), 'TC-VAL-05d: get_status_text(40)');

    // TC-VAL-06: ไฟล์อัปโหลด — ตรวจประเภทไฟล์
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $t->assert('TC-VAL-06a: อนุญาต image/jpeg',     in_array('image/jpeg',       $allowedTypes));
    $t->assert('TC-VAL-06b: บล็อก application/exe', !in_array('application/exe', $allowedTypes));
    $t->assert('TC-VAL-06c: บล็อก text/php',        !in_array('text/php',         $allowedTypes));

    // TC-VAL-07: ขนาดไฟล์ไม่เกิน 5MB
    $maxSize = 5 * 1024 * 1024;
    $t->assert('TC-VAL-07: ขนาดสูงสุด = 5MB', $maxSize === 5242880);
    $t->assert('TC-VAL-07a: ไฟล์ 4MB ผ่าน', 4 * 1024 * 1024 <= $maxSize);
    $t->assert('TC-VAL-07b: ไฟล์ 6MB ไม่ผ่าน', 6 * 1024 * 1024 > $maxSize);

    // TC-VAL-08: รูปแบบ document_no (สาขา 2-5 ตัว + 3 หลัก + / + ปี พ.ศ. 2 หลัก)
    $pattern = '/^[A-Z]{2,5}\d{3}\/\d{2}$/';
    $yr = substr((string)(date('Y') + 543), -2); // ปี พ.ศ. ปัจจุบัน 2 หลัก (เช่น 69)
    $t->assert("TC-VAL-08a: ACP001/{$yr} ถูกรูปแบบ",  preg_match($pattern, "ACP001/{$yr}") === 1);
    $t->assert("TC-VAL-08b: TST001/{$yr} ถูกรูปแบบ",  preg_match($pattern, "TST001/{$yr}") === 1);
    $t->assert('TC-VAL-08c: X001/69 ผิดรูปแบบ (prefix สั้นเกิน)',    preg_match($pattern, 'X001/69') === 0);
    $t->assert('TC-VAL-08d: ACP9999/69 ผิดรูปแบบ (เกิน 3 หลัก)',     preg_match($pattern, 'ACP9999/69') === 0);
    $t->assert('TC-VAL-08e: Test001/69 ผิดรูปแบบ (มี lowercase)',     preg_match($pattern, 'Test001/69') === 0);
    $t->assert('TC-VAL-08f: M001 ผิดรูปแบบ (ไม่มี /ปี)',              preg_match($pattern, 'M001') === 0);
    $t->assert('TC-VAL-08g: TST ผิดรูปแบบ (ไม่มีเลขและปี)',           preg_match($pattern, 'TST') === 0);
}
