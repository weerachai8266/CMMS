<?php
/**
 * Integration Tests: API Endpoints & Repair Workflow
 * ทดสอบ API ทุก endpoint และ workflow การแจ้งซ่อมตั้งแต่ต้นจนจบ
 */

function runApiEndpointTests(TestRunner $t, array $cfg): void
{
    $base = $cfg['base_url'];
    $t->suite('🔌  API Endpoints');

    // TC-API-01: GET monitor_data.php คืน JSON ถูกต้อง
    $r = $t->httpRequest('GET', $base . 'api/monitor_data.php');
    $t->assertHttpStatus(200, $r['status'], 'TC-API-01: GET monitor_data.php HTTP 200');
    $t->assert('TC-API-01b: Response เป็น JSON', $r['json'] !== null, $r['error']);
    $t->assert('TC-API-01c: มี field success', isset($r['json']['success']));
    $t->assert('TC-API-01d: มี field data', isset($r['json']['data']));
    $t->assert('TC-API-01e: มี field stats', isset($r['json']['stats']));

    // TC-API-02: GET monitor_data.php — stats fields ครบ
    if (isset($r['json']['stats'])) {
        $stats = $r['json']['stats'];
        foreach (['total', 'pending', 'completed', 'waiting'] as $key) {
            $t->assert("TC-API-02: stats.$key มีอยู่และเป็นตัวเลข", isset($stats[$key]) && is_numeric($stats[$key]));
        }
    }

    // TC-API-03: GET monitor_data.php — ความเร็ว < 500ms
    $t->assert('TC-API-03: monitor_data ตอบสนอง < 500ms', $r['duration'] < 500, "{$r['duration']}ms");

    // TC-API-04: GET get_all_repairs.php คืน JSON
    $r2 = $t->httpRequest('POST', $base . 'api/get_all_repairs.php');
    $t->assertHttpStatus(200, $r2['status'], 'TC-API-04: POST get_all_repairs.php HTTP 200');
    $t->assert('TC-API-04b: Response เป็น JSON', $r2['json'] !== null);

    // TC-API-05: GET kpi_data.php คืน JSON ถูกต้อง
    $r3 = $t->httpRequest('GET', $base . 'api/kpi_data.php');
    $t->assertHttpStatus(200, $r3['status'], 'TC-API-05: GET kpi_data.php HTTP 200');
    $t->assert('TC-API-05b: kpi_data เป็น JSON', $r3['json'] !== null);

    // TC-API-06: Method ผิด (GET แทน POST) ต้องได้ 405
    $r4 = $t->httpRequest('GET', $base . 'api/save_repair.php');
    $t->assertHttpStatus(405, $r4['status'], 'TC-API-06: GET save_repair.php คืน 405 Method Not Allowed');

    // TC-API-07: GET approve_repair.php ต้อง 405
    $r5 = $t->httpRequest('GET', $base . 'api/approve_repair.php');
    $t->assertHttpStatus(405, $r5['status'], 'TC-API-07: GET approve_repair.php คืน 405');

    // TC-API-08: POST save_repair.php ไม่มีข้อมูล ต้อง 400
    $r6 = $t->httpRequest('POST', $base . 'api/save_repair.php', []);
    $t->assertHttpStatus(400, $r6['status'], 'TC-API-08: POST save_repair.php ข้อมูลว่าง คืน 400');
    $t->assert('TC-API-08b: success = false', isset($r6['json']['success']) && $r6['json']['success'] === false);

    // TC-API-09: POST update_status.php — id ผิด ต้อง error
    $r7 = $t->httpRequest('POST', $base . 'api/update_status.php', ['id' => 'abc', 'status' => 20]);
    $t->assert('TC-API-09: update_status ด้วย id ผิดรูปแบบ ต้อง error', 
        isset($r7['json']['success']) && $r7['json']['success'] === false);

    // TC-API-10: POST monitor_update.php — status ผิดค่า ต้อง error
    $r8 = $t->httpRequest('POST', $base . 'api/monitor_update.php', ['id' => 1, 'status' => 99]);
    $t->assert('TC-API-10: monitor_update ด้วย status=99 ต้อง error',
        !isset($r8['json']['success']) || $r8['json']['success'] === false);

    // TC-API-11: Security — XSS ใน field ต้องถูก sanitize (sanitize_input)
    // ทดสอบ 2 กรณี:
    //   11a: ส่งข้อมูลไม่ครบ (reported_by ว่าง) → must 400, body ต้องไม่ echo script กลับ
    //   11b: ส่งข้อมูลครบพร้อม XSS → ถ้า save สำเร็จ ค่าที่เก็บต้องถูก escape + cleanup
    $xssPayload = '<script>alert(1)</script>';

    // 11a: incomplete form — guaranteed 400, check response body
    $r9a = $t->httpRequest('POST', $base . 'api/save_repair.php', [
        'division'       => $xssPayload,
        'department'     => 'Test',
        'branch'         => 'TST',
        'machine_number' => 'M001',
        'issue'          => 'Test issue',
        'reported_by'    => '', // จงใจเว้นว่าง → must 400
    ]);
    $t->assertHttpStatus(400, $r9a['status'], 'TC-API-11a: XSS + missing field → HTTP 400');
    $t->assert('TC-API-11a: Response body ไม่ echo <script> กลับ',
        !str_contains($r9a['body'] ?? '', '<script>'));

    // 11b: complete form with XSS — verify sanitization + cleanup
    $r9b = $t->httpRequest('POST', $base . 'api/save_repair.php', [
        'division'       => $xssPayload,
        'department'     => 'ฝ่ายทดสอบ',
        'branch'         => 'TST',
        'machine_number' => 'TEST-XSS',
        'issue'          => '[AUTOTEST-XSS] security test',
        'reported_by'    => 'AutoTester',
        'action_type'    => 'repair',
        'priority'       => 'urgent',
    ]);
    $t->assert('TC-API-11b: Response body ไม่ echo <script> กลับ',
        !str_contains($r9b['body'] ?? '', '<script>'));
    // ถ้า save สำเร็จ → ตรวจว่าค่าใน DB ถูก sanitize และ cleanup
    $xssId = $r9b['json']['data']['id'] ?? null;
    if ($xssId) {
        $xssRecord = null;
        $allR = $t->httpRequest('POST', $base . 'api/get_all_repairs.php', [], [], 'json');
        foreach ($allR['json']['data'] ?? [] as $row) {
            if ((int)$row['id'] === (int)$xssId) { $xssRecord = $row; break; }
        }
        $storedDivision = $xssRecord['division'] ?? '';
        $t->assert('TC-API-11b: division ที่เก็บใน DB ไม่มี raw <script> tag',
            !str_contains($storedDivision, '<script>'),
            "Stored: $storedDivision");
        // Cleanup
        $t->httpRequest('POST', $base . 'api/delete_repair.php', ['id' => (int)$xssId], [], 'json');
    }
}

function runRepairWorkflowTests(TestRunner $t, array $cfg): void
{
    $base = $cfg['base_url'];
    $t->suite('🔄  Repair Workflow (End-to-End)');

    // ---- Step 1: สร้างใบแจ้งซ่อมใหม่ ----
    $uniqueBranch = 'TST';
    $r = $t->httpRequest('POST', $base . 'api/save_repair.php', [
        'division'       => 'ฝ่ายทดสอบ',
        'department'     => 'แผนกทดสอบ',
        'branch'         => $uniqueBranch,
        'machine_number' => 'TEST-001',
        'issue'          => '[AUTOTEST] ทดสอบระบบ - ' . date('Y-m-d H:i:s'),
        'reported_by'    => 'AutoTester',
        'action_type'    => 'repair',
        'priority'       => 'urgent',
    ]);

    $t->assertHttpStatus(200, $r['status'], 'TC-WF-01: สร้างใบแจ้งซ่อมใหม่ HTTP 200');
    $t->assert('TC-WF-01b: success = true', isset($r['json']['success']) && $r['json']['success'] === true,
        $r['json']['message'] ?? '');

    $newId = $r['json']['data']['id'] ?? null;
    $docNo = $r['json']['data']['document_no'] ?? null;

    $t->assertNotEmpty($newId,  'TC-WF-01c: ได้รับ ID ของรายการใหม่');
    $t->assertNotEmpty($docNo, 'TC-WF-01d: ได้รับ document_no');

    if (!$newId) {
        $t->skip('TC-WF-02 ถึง TC-WF-08', 'ข้ามเพราะสร้างรายการไม่สำเร็จ');
        return;
    }

    // ---- Helper: ดึงข้อมูล record โดย ID ผ่าน get_all_repairs ----
    $getById = function(int $id) use ($t, $base): ?array {
        $r = $t->httpRequest('POST', $base . 'api/get_all_repairs.php', [], [], 'json');
        if (!isset($r['json']['data'])) return null;
        foreach ($r['json']['data'] as $row) {
            if ((int)$row['id'] === $id) return $row;
        }
        return null;
    };

    // ---- Step 2: ตรวจสอบข้อมูลที่สร้างไว้ ----
    $r2 = $t->httpRequest('POST', $base . 'api/get_all_repairs.php', [], [], 'json');
    $t->assertHttpStatus(200, $r2['status'], 'TC-WF-02: get_all_repairs HTTP 200');
    $record = $getById((int)$newId);
    $t->assert('TC-WF-02b: ข้อมูลถูกต้อง — machine_number = TEST-001',
        ($record['machine_number'] ?? '') === 'TEST-001');
    $t->assertEquals(10, (int)($record['status'] ?? -1),
        'TC-WF-02c: Status = 10 (รออนุมัติ)');

    // ---- Step 3: อนุมัติ (10 → 20) ----
    $r3 = $t->httpRequest('POST', $base . 'api/approve_repair.php', [
        'id'          => $newId,
        'approver'    => 'AutoTester-Approver',
        'device_type' => 'test',
        'browser'     => 'TestRunner',
        'os'          => 'Linux',
    ]);
    $t->assertHttpStatus(200, $r3['status'], 'TC-WF-03: อนุมัติรายการ HTTP 200');
    $t->assert('TC-WF-03b: success = true หลังอนุมัติ',
        isset($r3['json']['success']) && $r3['json']['success'] === true, $r3['json']['message'] ?? '');

    // ---- Step 4: ตรวจสถานะหลังอนุมัติ = 20 ----
    $record4 = $getById((int)$newId);
    $t->assertEquals(20, (int)($record4['status'] ?? -1),
        'TC-WF-04: Status = 20 หลังอนุมัติ (รอดำเนินการ)');

    // ---- Step 5: ตรวจสอบว่าปรากฏใน monitor_data ----
    $rMon = $t->httpRequest('GET', $base . 'api/monitor_data.php');
    $monIds = array_column($rMon['json']['data'] ?? [], 'id');
    $t->assert('TC-WF-05: รายการใหม่ปรากฏใน monitor_data', in_array((string)$newId, array_map('strval', $monIds)));

    // ---- Step 6: เปลี่ยนสถานะเป็นรออะไหล่ (20 → 30) ----
    $r5 = $t->httpRequest('POST', $base . 'api/update_status.php', [
        'id'         => $newId,
        'status'     => 30,
        'handled_by' => 'AutoTester-Tech',
    ]);
    $t->assert('TC-WF-06: เปลี่ยนสถานะเป็น 30 (รออะไหล่) สำเร็จ',
        isset($r5['json']['success']) && $r5['json']['success'] === true, $r5['json']['message'] ?? '');

    // ---- Step 7: เสร็จสิ้น (30 → 40) ----
    $r6 = $t->httpRequest('POST', $base . 'api/update_status.php', [
        'id'           => $newId,
        'status'       => 40,
        'handled_by'   => 'AutoTester-Tech',
        'receiver_name'=> 'AutoTester-Receiver',
        'job_status'   => 'complete',
    ]);
    $t->assert('TC-WF-07: เปลี่ยนสถานะเป็น 40 (ซ่อมเสร็จ) สำเร็จ',
        isset($r6['json']['success']) && $r6['json']['success'] === true, $r6['json']['message'] ?? '');

    // ---- Step 8: ตรวจสถานะสุดท้าย = 40 ----
    $record8 = $getById((int)$newId);
    $t->assertEquals(40, (int)($record8['status'] ?? -1),
        'TC-WF-08: Status = 40 หลังซ่อมเสร็จ');

    // ---- Step 9: Cleanup — ลบรายการทดสอบ ----
    $r8 = $t->httpRequest('POST', $base . 'api/delete_repair.php', ['id' => (int)$newId], [], 'json');
    $t->assert('TC-WF-09: ลบรายการทดสอบออก (Cleanup)',
        isset($r8['json']['success']) && $r8['json']['success'] === true, $r8['json']['message'] ?? '');
}

function runApprovalNegativeTests(TestRunner $t, array $cfg): void
{
    $base = $cfg['base_url'];
    $t->suite('🚫  Negative & Edge Case Tests');

    // TC-NEG-01: อนุมัติรายการที่ไม่มีอยู่
    $r = $t->httpRequest('POST', $base . 'api/approve_repair.php', [
        'id'       => 999999999,
        'approver' => 'Tester',
    ]);
    $t->assert('TC-NEG-01: อนุมัติ id ที่ไม่มีอยู่ ต้อง fail',
        isset($r['json']['success']) && $r['json']['success'] === false);

    // TC-NEG-02: บันทึกซ่อมโดยไม่มี reported_by
    $r2 = $t->httpRequest('POST', $base . 'api/save_repair.php', [
        'division'       => 'Test',
        'department'     => 'Test',
        'branch'         => 'TST',
        'machine_number' => 'M001',
        'issue'          => 'test',
        'reported_by'    => '', // ว่าง
    ]);
    $t->assert('TC-NEG-02: save_repair ไม่มี reported_by ต้อง fail',
        isset($r2['json']['success']) && $r2['json']['success'] === false);

    // TC-NEG-03: update_status เป็น 40 แต่ไม่มี handled_by
    $r3 = $t->httpRequest('POST', $base . 'api/update_status.php', [
        'id'         => 1,
        'status'     => 40,
        'handled_by' => '', // ว่าง
    ]);
    $t->assert('TC-NEG-03: update_status=40 ไม่มี handled_by ต้อง fail',
        isset($r3['json']['success']) && $r3['json']['success'] === false);

    // TC-NEG-04: status ที่ไม่อยู่ใน whitelist
    $r4 = $t->httpRequest('POST', $base . 'api/update_status.php', [
        'id'     => 1,
        'status' => 99,
    ]);
    $t->assert('TC-NEG-04: update_status=99 ต้องถูกปฏิเสธ',
        isset($r4['json']['success']) && $r4['json']['success'] === false);

    // TC-NEG-05: SQL Injection ใน id parameter
    $r5 = $t->httpRequest('POST', $base . 'api/approve_repair.php', [
        'id'       => "1 OR 1=1",
        'approver' => 'hacker',
    ]);
    // ต้อง fail ด้วย error ไม่ใช่อนุมัติทุกรายการ
    $t->assert('TC-NEG-05: SQL Injection ใน id ถูกป้องกัน',
        !isset($r5['json']['success']) || $r5['json']['success'] === false);

    // TC-NEG-06: approve รายการที่อนุมัติไปแล้ว (status != 10) ต้อง fail
    // สร้าง fixture เอง: บันทึก → อนุมัติครั้งแรก → อนุมัติซ้ำ → cleanup
    $fx = $t->httpRequest('POST', $base . 'api/save_repair.php', [
        'division'       => 'ฝ่ายทดสอบ',
        'department'     => 'แผนกทดสอบ',
        'branch'         => 'TST',
        'machine_number' => 'TEST-NEG06',
        'issue'          => '[AUTOTEST-NEG06] double approve test',
        'reported_by'    => 'AutoTester',
        'action_type'    => 'repair',
        'priority'       => 'urgent',
    ]);
    $fxId = $fx['json']['data']['id'] ?? null;

    if ($fxId) {
        // อนุมัติครั้งแรก (10 → 20)
        $t->httpRequest('POST', $base . 'api/approve_repair.php', [
            'id'       => $fxId,
            'approver' => 'AutoTester',
        ]);

        // อนุมัติซ้ำ — ต้อง fail เพราะ status เป็น 20 แล้ว
        $rDouble = $t->httpRequest('POST', $base . 'api/approve_repair.php', [
            'id'       => $fxId,
            'approver' => 'AutoTester',
        ]);
        $t->assert('TC-NEG-06: อนุมัติรายการที่ status=20 ซ้ำต้อง fail',
            isset($rDouble['json']['success']) && $rDouble['json']['success'] === false,
            $rDouble['json']['message'] ?? 'unexpected success');

        // Cleanup
        $t->httpRequest('POST', $base . 'api/delete_repair.php', ['id' => (int)$fxId], [], 'json');
    } else {
        $t->skip('TC-NEG-06: approve รายการที่อนุมัติไปแล้ว', 'สร้าง fixture ไม่สำเร็จ');
    }
}
