<?php
/**
 * ============================================================
 *  ระบบแจ้งซ่อมเครื่องจักร — Test Suite Runner
 *  เปิดใช้งาน: http://192.168.0.44/mt/tests/run.php
 * ============================================================
 */

// ป้องกันการเข้าถึงจากภายนอก (ใช้ได้เฉพาะใน local network)
$allowedCIDR = '192.168.';
$clientIP    = $_SERVER['REMOTE_ADDR'] ?? '';
if (!str_starts_with($clientIP, $allowedCIDR) && $clientIP !== '127.0.0.1') {
    http_response_code(403);
    die('Access denied: Test runner is only available on internal network.');
}

// ---- โหลด dependencies ----
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/TestRunner.php';
require_once __DIR__ . '/Unit/DatabaseAndValidationTest.php';
require_once __DIR__ . '/Integration/ApiWorkflowTest.php';
require_once __DIR__ . '/Performance/LoadTest.php';

// ---- Configuration ----
$loadUsers = max(1, min(500, (int)($_GET['load_users'] ?? 50)));
$cfg = [
    'base_url'   => 'http://192.168.0.44/mt/',
    'db_host'    => '192.168.0.44',
    'db_name'    => 'maintenance',
    'db_user'    => 'webapp_mt',
    'db_pass'    => 'user',
    'load_users' => $loadUsers,
];

// ---- เลือก Suite ที่จะรัน ----
$runSuites = [
    'db'          => isset($_GET['suite']) ? $_GET['suite'] === 'db'          : true,
    'validation'  => isset($_GET['suite']) ? $_GET['suite'] === 'validation'  : true,
    'api'         => isset($_GET['suite']) ? $_GET['suite'] === 'api'         : true,
    'workflow'    => isset($_GET['suite']) ? $_GET['suite'] === 'workflow'    : true,
    'negative'    => isset($_GET['suite']) ? $_GET['suite'] === 'negative'    : true,
    'performance' => isset($_GET['suite']) ? $_GET['suite'] === 'performance' : true,
    'resource'    => isset($_GET['suite']) ? $_GET['suite'] === 'resource'    : true,
    'load'        => isset($_GET['suite']) ? $_GET['suite'] === 'load'        : true,
];

// ---- Run Tests ----
$runner = new TestRunner();

if ($runSuites['db'])          runDatabaseTests($runner, $cfg);
if ($runSuites['validation'])  runValidationTests($runner);
if ($runSuites['api'])         runApiEndpointTests($runner, $cfg);
if ($runSuites['workflow'])    runRepairWorkflowTests($runner, $cfg);
if ($runSuites['negative'])    runApprovalNegativeTests($runner, $cfg);
if ($runSuites['performance']) runPerformanceTests($runner, $cfg);
if ($runSuites['resource'])    runMemoryAndResourceTests($runner, $cfg);
if ($runSuites['load'])        runConcurrentLoadTest($runner, $cfg);

$results = $runner->getResults();
$summary = $runner->getSummary();

// ---- Group by suite ----
$grouped = [];
foreach ($results as $r) {
    $grouped[$r['suite']][] = $r;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Suite — ระบบแจ้งซ่อมเครื่องจักร</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --pass:    #22c55e;
            --fail:    #ef4444;
            --skip:    #f59e0b;
            --bg:      #0f172a;
            --surface: #1e293b;
            --border:  #334155;
            --text:    #e2e8f0;
            --muted:   #94a3b8;
            --accent:  #6366f1;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Sarabun', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 24px;
        }
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }
        header h1 { font-size: 1.5rem; font-weight: 700; }
        header p  { color: var(--muted); font-size: 0.85rem; margin-top: 3px; }

        /* Summary Bar */
        .summary {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }
        .summary-card {
            flex: 1;
            min-width: 140px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px 20px;
            text-align: center;
        }
        .summary-card .num {
            font-size: 2.5rem;
            font-weight: 800;
            font-family: 'JetBrains Mono', monospace;
            line-height: 1;
        }
        .summary-card .lbl { color: var(--muted); font-size: 0.8rem; margin-top: 4px; }
        .summary-card.pass  { border-color: var(--pass); }
        .summary-card.pass  .num { color: var(--pass); }
        .summary-card.fail  { border-color: var(--fail); }
        .summary-card.fail  .num { color: var(--fail); }
        .summary-card.skip  { border-color: var(--skip); }
        .summary-card.skip  .num { color: var(--skip); }
        .summary-card.total .num { color: var(--accent); }
        .summary-card.time  .num { color: #38bdf8; font-size: 1.6rem; }

        /* Progress bar */
        .progress-wrap {
            background: var(--border);
            border-radius: 99px;
            height: 8px;
            margin-bottom: 28px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            border-radius: 99px;
            background: linear-gradient(90deg, var(--pass), #86efac);
            transition: width 0.8s ease;
        }

        /* Suite filter */
        .filter-bar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .filter-bar a {
            padding: 6px 16px;
            border-radius: 99px;
            border: 1px solid var(--border);
            color: var(--muted);
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.2s;
        }
        .filter-bar a:hover, .filter-bar a.active {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
        }

        /* Suite block */
        .suite {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .suite-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            cursor: pointer;
            user-select: none;
            border-bottom: 1px solid var(--border);
        }
        .suite-header h2 { font-size: 1rem; font-weight: 700; }
        .suite-badges { display: flex; gap: 8px; }
        .badge {
            padding: 2px 10px;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }
        .badge-pass  { background: rgba(34,197,94,.15);  color: var(--pass); }
        .badge-fail  { background: rgba(239,68,68,.15);  color: var(--fail); }
        .badge-skip  { background: rgba(245,158,11,.15); color: var(--skip); }
        .suite-body  { padding: 0 12px 12px; }

        /* Test rows */
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th {
            text-align: left;
            padding: 8px 12px;
            color: var(--muted);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            border-bottom: 1px solid var(--border);
        }
        td { padding: 9px 12px; border-bottom: 1px solid rgba(51,65,85,.5); font-size: 0.85rem; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,.02); }

        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }
        .chip-pass { background: rgba(34,197,94,.15);  color: var(--pass); }
        .chip-fail { background: rgba(239,68,68,.15);  color: var(--fail); }
        .chip-skip { background: rgba(245,158,11,.15); color: var(--skip); }

        .detail { color: var(--muted); font-size: 0.78rem; font-family: 'JetBrains Mono', monospace; }
        .time-ms { color: var(--muted); font-family: 'JetBrains Mono', monospace; font-size: 0.8rem; }

        /* Overall status banner */
        .result-banner {
            padding: 14px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 700;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .banner-pass { background: rgba(34,197,94,.1);  border: 1px solid var(--pass); color: var(--pass); }
        .banner-fail { background: rgba(239,68,68,.1);  border: 1px solid var(--fail); color: var(--fail); }

        /* Run buttons */
        .run-btns { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
        .run-btn {
            padding: 8px 20px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text);
            text-decoration: none;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .run-btn:hover       { background: var(--accent); border-color: var(--accent); }
        .run-btn.primary     { background: var(--accent); border-color: var(--accent); }
        .run-btn.danger      { border-color: var(--fail); color: var(--fail); }
        .run-btn.danger:hover{ background: var(--fail); color: white; }
        .run-btn.stress      { border-color: #f59e0b; color: #f59e0b; }
        .run-btn.stress:hover{ background: #f59e0b; color: #1c1917; }

        /* Load users inline form */
        .load-form { display:inline-flex; align-items:center; gap:6px; }
        .load-input {
            width: 72px; padding: 7px 10px;
            border-radius: 8px; border: 1px solid var(--fail);
            background: var(--surface); color: var(--fail);
            font-size: 0.85rem; font-family: 'JetBrains Mono', monospace;
            outline: none; text-align: center;
        }
        .load-input:focus { border-color: #f87171; }
        .load-submit {
            padding: 8px 16px; border-radius: 8px;
            border: 1px solid var(--fail); background: var(--surface);
            color: var(--fail); font-size: 0.85rem; cursor: pointer;
            transition: all 0.2s; white-space: nowrap;
        }
        .load-submit:hover { background: var(--fail); color: white; }
    </style>
</head>
<body>

<header>
    <div>
        <h1>🧪 Test Suite — ระบบแจ้งซ่อมเครื่องจักร</h1>
        <p>v<?= SYSTEM_VERSION ?> &nbsp;|&nbsp; PHP <?= PHP_VERSION ?> &nbsp;|&nbsp; Run: <?= date('d/m/Y H:i:s') ?> &nbsp;|&nbsp; By: <?= htmlspecialchars($clientIP) ?></p>
    </div>
</header>

<!-- Quick run buttons -->
<div class="run-btns">
    <form method="GET" action="run.php" class="load-form">
        <input type="number" class="load-input" name="load_users"
               min="1" max="500" value="<?= $loadUsers ?>"
               title="concurrent users สำหรับ Load Test">
        <button type="submit" class="run-btn primary" style="border:none;cursor:pointer">▶ Run All Tests</button>
    </form>
    <a href="run.php?suite=db" class="run-btn">🗄️ DB Only</a>
    <a href="run.php?suite=api" class="run-btn">🔌 API Only</a>
    <a href="run.php?suite=workflow" class="run-btn">🔄 Workflow Only</a>
    <a href="run.php?suite=performance" class="run-btn">⚡ Performance Only</a>
    <form method="GET" action="run.php" class="load-form">
        <input type="hidden" name="suite" value="load">
        <input type="number" class="load-input" name="load_users"
               min="1" max="500" value="<?= $loadUsers ?>"
               title="จำนวน concurrent users">
        <button type="submit" class="load-submit">👥 Load Test Only</button>
    </form>
    <a href="stress.php" class="run-btn stress">📊 Stress Test (หาจำนวน user สูงสุด)</a>
</div>

<!-- Summary Cards -->
<div class="summary">
    <div class="summary-card total">
        <div class="num"><?= $summary['total'] ?></div>
        <div class="lbl">Total Tests</div>
    </div>
    <div class="summary-card pass">
        <div class="num"><?= $summary['passed'] ?></div>
        <div class="lbl">Passed</div>
    </div>
    <div class="summary-card fail">
        <div class="num"><?= $summary['failed'] ?></div>
        <div class="lbl">Failed</div>
    </div>
    <div class="summary-card skip">
        <div class="num"><?= $summary['skipped'] ?></div>
        <div class="lbl">Skipped</div>
    </div>
    <div class="summary-card time">
        <div class="num"><?= number_format($summary['duration'] / 1000, 2) ?>s</div>
        <div class="lbl">Total Time</div>
    </div>
</div>

<!-- Progress bar -->
<?php $passRate = $summary['total'] > 0 ? ($summary['passed'] / $summary['total'] * 100) : 0; ?>
<div class="progress-wrap">
    <div class="progress-bar" style="width: <?= $passRate ?>%"></div>
</div>

<!-- Result banner -->
<?php if ($summary['failed'] === 0): ?>
    <div class="result-banner banner-pass">✅ ทุก Test ผ่าน — ระบบพร้อมใช้งาน (Pass rate: <?= number_format($passRate, 1) ?>%)</div>
<?php else: ?>
    <div class="result-banner banner-fail">❌ พบ <?= $summary['failed'] ?> Test ล้มเหลว — กรุณาตรวจสอบก่อน Deploy (Pass rate: <?= number_format($passRate, 1) ?>%)</div>
<?php endif; ?>

<!-- Test Results by Suite -->
<?php foreach ($grouped as $suiteName => $tests): ?>
    <?php
    $suitePass = count(array_filter($tests, fn($t) => $t['status'] === 'PASS'));
    $suiteFail = count(array_filter($tests, fn($t) => $t['status'] === 'FAIL'));
    $suiteSkip = count(array_filter($tests, fn($t) => $t['status'] === 'SKIP'));
    $suiteIcon = $suiteFail > 0 ? '❌' : '✅';
    ?>
    <div class="suite">
        <div class="suite-header" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'block' : 'none'">
            <h2><?= htmlspecialchars($suiteName) ?></h2>
            <div class="suite-badges">
                <?php if ($suitePass > 0): ?><span class="badge badge-pass">✓ <?= $suitePass ?></span><?php endif; ?>
                <?php if ($suiteFail > 0): ?><span class="badge badge-fail">✗ <?= $suiteFail ?></span><?php endif; ?>
                <?php if ($suiteSkip > 0): ?><span class="badge badge-skip">⊘ <?= $suiteSkip ?></span><?php endif; ?>
            </div>
        </div>
        <div class="suite-body">
            <table>
                <thead>
                    <tr>
                        <th style="width:80px">Status</th>
                        <th>Test Case</th>
                        <th>Detail</th>
                        <th style="width:80px;text-align:right">Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tests as $test): ?>
                        <?php
                        $chipClass = match($test['status']) {
                            'PASS' => 'chip-pass',
                            'FAIL' => 'chip-fail',
                            default => 'chip-skip',
                        };
                        $icon = match($test['status']) {
                            'PASS' => '✓',
                            'FAIL' => '✗',
                            default => '⊘',
                        };
                        ?>
                        <tr>
                            <td><span class="status-chip <?= $chipClass ?>"><?= $icon ?> <?= $test['status'] ?></span></td>
                            <td><?= htmlspecialchars($test['name']) ?></td>
                            <td class="detail"><?= htmlspecialchars($test['detail']) ?></td>
                            <td class="time-ms" style="text-align:right"><?= number_format($test['time'] * 1000, 0) ?>ms</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>

<script>
    // Auto-collapse passed suites, expand failed
    document.querySelectorAll('.suite').forEach(suite => {
        const hasFail = suite.querySelector('.badge-fail');
        const body    = suite.querySelector('.suite-body');
        if (!hasFail) {
            body.style.display = 'none';
        }
    });
</script>
</body>
</html>
