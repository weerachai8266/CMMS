<?php
/**
 * Performance Tests: Response Time & Simulated Load
 * ทดสอบประสิทธิภาพและรับมือ concurrent users สูงสุด 100 คน
 */

function runPerformanceTests(TestRunner $t, array $cfg): void
{
    $base = $cfg['base_url'];
    $t->suite('⚡  Performance Tests');

    // ---- หน้า API ที่ถูกเรียกบ่อยที่สุด ----
    $endpoints = [
        ['GET',  'api/monitor_data.php',  [],   300, 'monitor_data (poll ทุก 10s)'],
        ['POST', 'api/get_all_repairs.php', [], 500, 'get_all_repairs'],
        ['GET',  'api/kpi_data.php',       [],   600, 'kpi_data'],
        ['GET',  'api/master_data.php',    [],   400, 'master_data'],
    ];

    foreach ($endpoints as [$method, $path, $data, $threshold, $label]) {
        // ยิง 5 ครั้งเพื่อเฉลี่ย (warm-up + stable)
        $times = [];
        for ($i = 0; $i < 5; $i++) {
            $r = $t->httpRequest($method, $base . $path, $data);
            if ($r['status'] === 200) {
                $times[] = $r['duration'];
            }
        }
        if (empty($times)) {
            $t->assert("TC-PERF: $label — HTTP 200", false, 'ไม่ได้รับ response');
            continue;
        }
        $avg = round(array_sum($times) / count($times), 2);
        $max = round(max($times), 2);
        $t->assert(
            "TC-PERF: $label — avg < {$threshold}ms (avg={$avg}ms)",
            $avg < $threshold,
            "avg={$avg}ms, max={$max}ms, threshold={$threshold}ms"
        );
        $t->assert(
            "TC-PERF: $label — max < " . ($threshold * 2) . "ms (max={$max}ms)",
            $max < ($threshold * 2),
            "max={$max}ms, threshold×2=" . ($threshold * 2) . "ms"
        );
    }
}

function runConcurrentLoadTest(TestRunner $t, array $cfg): void
{
    $base = $cfg['base_url'];
    $concurrentUsers = (int)($cfg['load_users'] ?? 50);
    $t->suite("👥  Concurrent Load Simulation ({$concurrentUsers} Users)");
    $url = $base . 'api/monitor_data.php';

    $mh      = curl_multi_init();
    $handles = [];
    $startAll = microtime(true);

    for ($i = 0; $i < $concurrentUsers; $i++) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_multi_add_handle($mh, $ch);
        $handles[] = $ch;
    }

    // Execute ทั้งหมดพร้อมกัน
    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh, 0.01);
    } while ($running > 0);

    $totalDuration = round((microtime(true) - $startAll) * 1000, 2);

    // เก็บผลลัพธ์
    $success = 0;
    $failed  = 0;
    $codes   = [];

    foreach ($handles as $ch) {
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $codes[] = $code;
        if ($code === 200) {
            $success++;
        } else {
            $failed++;
        }
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);

    $successRate = round(($success / $concurrentUsers) * 100, 1);
    $avgTime     = round($totalDuration / $concurrentUsers, 2);

    $t->assert(
        "TC-LOAD-01: {$concurrentUsers} concurrent — success rate ≥ 95% ({$successRate}%)",
        $successRate >= 95,
        "success={$success}, failed={$failed}"
    );
    $t->assert(
        "TC-LOAD-02: {$concurrentUsers} concurrent — รวมไม่เกิน 10s ({$totalDuration}ms)",
        $totalDuration < 10000,
        "total={$totalDuration}ms"
    );
    $t->assert(
        "TC-LOAD-03: avg time/request < 2s ({$avgTime}ms/req)",
        $avgTime < 2000,
        "avg={$avgTime}ms"
    );

    // ---- จำลอง 2× users (ทยอย batch) ----
    $batch2 = max($concurrentUsers * 2, 100);
    $t->suite("👥  Load Test: {$batch2} Users (Batch)");

    $batch   = $batch2;
    $batchMh = curl_multi_init();
    $bHandles = [];
    $bStart  = microtime(true);

    for ($i = 0; $i < $batch; $i++) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_multi_add_handle($batchMh, $ch);
        $bHandles[] = $ch;
    }

    $running = null;
    do {
        curl_multi_exec($batchMh, $running);
        curl_multi_select($batchMh, 0.01);
    } while ($running > 0);

    $bDuration = round((microtime(true) - $bStart) * 1000, 2);
    $bSuccess  = 0;

    foreach ($bHandles as $ch) {
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200) {
            $bSuccess++;
        }
        curl_multi_remove_handle($batchMh, $ch);
        curl_close($ch);
    }
    curl_multi_close($batchMh);

    $bRate = round(($bSuccess / $batch) * 100, 1);

    $t->assert(
        "TC-LOAD-04: {$batch} concurrent — success rate ≥ 90% ({$bRate}%)",
        $bRate >= 90,
        "success={$bSuccess}/{$batch}"
    );
    $t->assert(
        "TC-LOAD-05: {$batch} concurrent — รวมไม่เกิน 20s ({$bDuration}ms)",
        $bDuration < 20000,
        "total={$bDuration}ms"
    );
}

function runMemoryAndResourceTests(TestRunner $t, array $cfg): void
{
    $t->suite('🖥️  Server Resource Tests');

    // TC-RES-01: PHP memory limit เพียงพอ
    $memLimit = ini_get('memory_limit');
    $bytes = (int)$memLimit;
    if (str_ends_with(strtolower($memLimit), 'm')) {
        $bytes = (int)$memLimit * 1024 * 1024;
    } elseif (str_ends_with(strtolower($memLimit), 'g')) {
        $bytes = (int)$memLimit * 1024 * 1024 * 1024;
    }
    $t->assert("TC-RES-01: memory_limit ≥ 128MB ({$memLimit})", $bytes >= 128 * 1024 * 1024);

    // TC-RES-02: PHP version >= 8.0
    $phpVersion = PHP_VERSION;
    $t->assert("TC-RES-02: PHP version >= 8.0 (current: {$phpVersion})", version_compare($phpVersion, '8.0.0', '>='));

    // TC-RES-03: PDO MySQL extension โหลดอยู่
    $t->assert('TC-RES-03: PDO MySQL extension โหลดอยู่', extension_loaded('pdo_mysql'));

    // TC-RES-04: cURL extension โหลดอยู่ (สำหรับ API calls)
    $t->assert('TC-RES-04: cURL extension โหลดอยู่', extension_loaded('curl'));

    // TC-RES-05: curl_multi รองรับ concurrent requests
    $t->assert('TC-RES-05: curl_multi_init พร้อมใช้', function_exists('curl_multi_init'));

    // TC-RES-06: GD / Imagick สำหรับ image upload
    $t->assert('TC-RES-06: GD extension โหลดอยู่', extension_loaded('gd'));

    // TC-RES-07: uploads directory เขียนได้
    $uploadDir = __DIR__ . '/../../uploads/';
    $t->assert('TC-RES-07: uploads/ directory เขียนได้', is_writable($uploadDir), $uploadDir);

    // TC-RES-08: Disk space เหลืออย่างน้อย 1GB
    $freeBytes = disk_free_space('/');
    $freeMB    = round($freeBytes / (1024 * 1024));
    $t->assert("TC-RES-08: Disk free ≥ 1GB ({$freeMB}MB เหลือ)", $freeBytes >= 1 * 1024 * 1024 * 1024);

    // TC-RES-09: Memory ที่ใช้ตอนนี้ไม่เกิน 80%
    $currentUsage  = memory_get_usage(true);
    $limitCheck    = 128 * 1024 * 1024;
    $usagePct      = round($currentUsage / $limitCheck * 100, 1);
    $t->assert("TC-RES-09: Memory usage ต่ำกว่า 80% ({$usagePct}%)", $usagePct < 80);
}
