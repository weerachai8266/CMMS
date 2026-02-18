<?php
/**
 * ============================================================
 *  Stress / Capacity Test v2 — 3-Tier Classification
 *  เปิด: http://192.168.0.44/mt/tests/stress.php
 *  ระดับ:  SAFE     = avg ≤ 500ms  & success = 100%
 *           WARNING  = avg ≤ 2000ms & success ≥ 95%
 *           CRITICAL = avg > 2000ms  | success < 95%
 * ============================================================
 */
set_time_limit(300);

// เข้าได้เฉพาะ internal network
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
if (!str_starts_with($clientIP, '192.168.') && $clientIP !== '127.0.0.1') {
    http_response_code(403);
    die('Access denied');
}

require_once __DIR__ . '/../config/config.php';

// ---- Config ----
$BASE_URL   = 'http://192.168.0.44/mt/';
$TARGET_URL = $BASE_URL . 'api/monitor_data.php';
$STEPS      = [1, 5, 10, 20, 50, 100, 150, 200, 300, 500, 750, 1000];
$REPEAT     = 3;    // รอบต่อ step
$TIMEOUT    = 20;   // timeout/req (วินาที)

// ---- 3-Tier thresholds ----
define('SAFE_AVG',  500);    // ms
define('WARN_AVG',  2000);   // ms
define('SAFE_RATE', 100.0);  // %
define('WARN_RATE', 95.0);   // %

function getLevel(float $avgMs, float $successRate): string {
    if ($successRate < WARN_RATE || $avgMs > WARN_AVG) return 'critical';
    if ($successRate < SAFE_RATE || $avgMs > SAFE_AVG) return 'warning';
    return 'safe';
}

// ---- Ramp-up test ----
$stepResults    = [];
$consecutiveCrit = 0;

foreach ($STEPS as $users) {
    $allTimes   = [];
    $allSuccess = 0;
    $allFail    = 0;

    for ($round = 0; $round < $REPEAT; $round++) {
        $mh      = curl_multi_init();
        $handles = [];

        for ($i = 0; $i < $users; $i++) {
            $ch = curl_init($TARGET_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $TIMEOUT);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_multi_add_handle($mh, $ch);
            $handles[] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh, 0.005);
        } while ($running > 0);

        foreach ($handles as $ch) {
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $time = curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000; // ms
            if ($code === 200) {
                $allSuccess++;
                $allTimes[] = $time;
            } else {
                $allFail++;
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
        usleep(300000); // 300ms between rounds
    }

    $total       = $users * $REPEAT;
    $successRate = round($allSuccess / $total * 100, 1);
    $avgTime     = count($allTimes) > 0 ? round(array_sum($allTimes) / count($allTimes), 1) : 9999;
    $minTime     = count($allTimes) > 0 ? round(min($allTimes), 1) : 0;
    $maxTime     = count($allTimes) > 0 ? round(max($allTimes), 1) : 9999;
    $p50         = calcPercentile($allTimes, 50);
    $p95         = calcPercentile($allTimes, 95);
    $p99         = calcPercentile($allTimes, 99);

    $level = getLevel($avgTime, $successRate);
    $stepResults[] = compact('users','total','allSuccess','allFail','successRate','avgTime','minTime','maxTime','p50','p95','p99','level');

    // หยุดหลัง 2 consecutive critical
    if ($level === 'critical') {
        $consecutiveCrit++;
        if ($consecutiveCrit >= 2) break;
    } else {
        $consecutiveCrit = 0;
    }
}

// ---- Count per tier ----
$safeCount = 0; $warnCount = 0; $critCount = 0;
$maxSafeUsers = 0; $maxWarnUsers = 0;
foreach ($stepResults as $r) {
    if ($r['level'] === 'safe')     { $safeCount++; $maxSafeUsers = $r['users']; }
    elseif ($r['level'] === 'warning') { $warnCount++; $maxWarnUsers = $r['users']; }
    else                               { $critCount++; }
}

function calcPercentile(array $times, int $pct): float {
    if (empty($times)) return 0;
    sort($times);
    $idx = (int)ceil($pct / 100 * count($times)) - 1;
    return round($times[max(0, $idx)], 1);
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stress Test — ระบบแจ้งซ่อมเครื่องจักร</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>
:root {
    --safe:    #22c55e;
    --warn:    #f59e0b;
    --crit:    #ef4444;
    --bg:      #0f172a;
    --surface: #1e293b;
    --border:  #334155;
    --text:    #e2e8f0;
    --muted:   #94a3b8;
    --accent:  #6366f1;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Sarabun', sans-serif; background: var(--bg); color: var(--text); padding: 28px; }
h1 { font-size: 1.6rem; font-weight: 700; margin-bottom: 6px; }
.sub { color: var(--muted); font-size: 0.85rem; margin-bottom: 22px; }

/* Tier legend */
.tier-legend { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:24px; }
.tier-badge { display:flex; align-items:center; gap:8px; background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:10px 16px; font-size:0.82rem; }
.tier-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.dot-safe { background:var(--safe); }
.dot-warn { background:var(--warn); }
.dot-crit { background:var(--crit); }
.tier-badge b { color:var(--text); }
.tier-badge span { color:var(--muted); }

/* Tier cards */
.tier-cards { display:flex; gap:14px; flex-wrap:wrap; margin-bottom:28px; }
.tier-card { flex:1; min-width:200px; border-radius:14px; padding:20px 24px; }
.tc-safe { background:rgba(34,197,94,.10); border:1px solid var(--safe); }
.tc-warn { background:rgba(245,158,11,.10); border:1px solid var(--warn); }
.tc-crit { background:rgba(239,68,68,.10);  border:1px solid var(--crit); }
.tier-card .tc-title { font-size:0.75rem; text-transform:uppercase; letter-spacing:.1em; font-weight:700; margin-bottom:8px; }
.tc-safe .tc-title  { color:var(--safe); }
.tc-warn .tc-title  { color:var(--warn); }
.tc-crit .tc-title  { color:var(--crit); }
.tier-card .tc-num  { font-size:2.8rem; font-weight:800; font-family:'JetBrains Mono',monospace; line-height:1; }
.tc-safe .tc-num    { color:var(--safe); }
.tc-warn .tc-num    { color:var(--warn); }
.tc-crit .tc-num    { color:var(--crit); }
.tier-card .tc-sub  { color:var(--muted); font-size:0.8rem; margin-top:6px; }

/* Chart */
.chart-wrap { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:24px; margin-bottom:28px; }
.chart-title { font-weight:700; margin-bottom:18px; }
.bar-chart { display:flex; flex-direction:column; gap:10px; }
.bar-row { display:flex; align-items:center; gap:12px; }
.bar-label { width:90px; text-align:right; font-family:'JetBrains Mono',monospace; font-size:0.78rem; color:var(--muted); flex-shrink:0; }
.bar-track { flex:1; background:var(--border); border-radius:99px; height:26px; overflow:hidden; }
.bar-fill  { height:100%; border-radius:99px; display:flex; align-items:center; padding-left:10px; font-size:0.73rem; font-weight:700; white-space:nowrap; }
.bar-info  { font-family:'JetBrains Mono',monospace; font-size:0.73rem; color:var(--muted); width:260px; flex-shrink:0; }
.fill-safe { background:linear-gradient(90deg,#16a34a,#4ade80); color:#052e16; }
.fill-warn { background:linear-gradient(90deg,#d97706,#fbbf24); color:#1c1917; }
.fill-crit { background:linear-gradient(90deg,#b91c1c,#f87171); color:#fff; }

/* Table */
.table-wrap { background:var(--surface); border:1px solid var(--border); border-radius:12px; overflow:hidden; margin-bottom:28px; }
table { width:100%; border-collapse:collapse; }
th { background:rgba(99,102,241,.1); padding:9px 14px; text-align:left; font-size:0.72rem; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); border-bottom:1px solid var(--border); }
td { padding:9px 14px; border-bottom:1px solid rgba(51,65,85,.5); font-size:0.83rem; font-family:'JetBrains Mono',monospace; }
tr:last-child td { border-bottom:none; }
tr:hover td { background:rgba(255,255,255,.02); }
.chip { display:inline-block; padding:2px 10px; border-radius:99px; font-size:0.72rem; font-weight:700; }
.chip-safe { background:rgba(34,197,94,.15);  color:var(--safe); }
.chip-warn { background:rgba(245,158,11,.15); color:var(--warn); }
.chip-crit { background:rgba(239,68,68,.15);  color:var(--crit); }
.c-safe { color:var(--safe); }
.c-warn { color:var(--warn); }
.c-crit { color:var(--crit); }
.c-muted{ color:var(--muted); }

/* Recommend */
.recommend { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:20px 24px; }
.recommend h3 { font-weight:700; margin-bottom:14px; }
.recommend li { color:var(--muted); font-size:0.88rem; margin-bottom:8px; padding-left:4px; }
.recommend li strong { color:var(--text); }

.back-btn { display:inline-block; margin-bottom:20px; padding:8px 20px; border-radius:8px; background:var(--surface); border:1px solid var(--border); color:var(--text); text-decoration:none; font-size:0.85rem; }
.back-btn:hover { border-color:var(--accent); }

.cfg-bar { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:24px; }
.cfg-pill { background:var(--surface); border:1px solid var(--border); border-radius:8px; padding:5px 14px; font-size:0.78rem; font-family:'JetBrains Mono',monospace; color:var(--muted); }
.cfg-pill b { color:var(--text); }
</style>
</head>
<body>

<a href="run.php" class="back-btn">← กลับ Test Suite</a>

<h1>📊 Stress Test v2 — ความสามารถรองรับผู้ใช้งาน</h1>
<p class="sub">
    Target: <code><?= $TARGET_URL ?></code> &nbsp;|&nbsp;
    ทดสอบเมื่อ: <?= date('d/m/Y H:i:s') ?> &nbsp;|&nbsp;
    PHP <?= PHP_VERSION ?>
</p>

<!-- Config -->
<div class="cfg-bar">
    <div class="cfg-pill">Steps: <b><?= implode(' → ', $STEPS) ?></b></div>
    <div class="cfg-pill">Repeat/step: <b><?= $REPEAT ?>x</b></div>
    <div class="cfg-pill">Timeout: <b><?= $TIMEOUT ?>s/req</b></div>
    <div class="cfg-pill">Stop after: <b>2 consecutive CRITICAL</b></div>
</div>

<!-- Tier Legend -->
<div class="tier-legend">
    <div class="tier-badge"><div class="tier-dot dot-safe"></div><b>SAFE</b> <span>avg ≤ <?= SAFE_AVG ?>ms &amp; success = <?= SAFE_RATE ?>%</span></div>
    <div class="tier-badge"><div class="tier-dot dot-warn"></div><b>WARNING</b> <span>avg ≤ <?= WARN_AVG ?>ms &amp; success ≥ <?= WARN_RATE ?>%</span></div>
    <div class="tier-badge"><div class="tier-dot dot-crit"></div><b>CRITICAL</b> <span>avg > <?= WARN_AVG ?>ms <b>หรือ</b> success &lt; <?= WARN_RATE ?>%</span></div>
</div>

<!-- Tier Cards -->
<div class="tier-cards">
    <div class="tier-card tc-safe">
        <div class="tc-title">🟢 SAFE</div>
        <div class="tc-num"><?= $maxSafeUsers ?> users</div>
        <div class="tc-sub"><?= $safeCount ?> step<?= $safeCount!=1?'s':'' ?> ผ่านระดับนี้ — ใช้งานได้สบาย</div>
    </div>
    <div class="tier-card tc-warn">
        <div class="tc-title">🟡 WARNING</div>
        <div class="tc-num"><?= $maxWarnUsers ?: '—' ?><?= $maxWarnUsers ? ' users' : '' ?></div>
        <div class="tc-sub"><?= $warnCount ?> step<?= $warnCount!=1?'s':'' ?> — ช้าลงแต่ยังใช้ได้</div>
    </div>
    <div class="tier-card tc-crit">
        <div class="tc-title">🔴 CRITICAL</div>
        <div class="tc-num"><?= $critCount ?> step<?= $critCount!=1?'s':'' ?></div>
        <div class="tc-sub"><?= $critCount > 0 ? 'ระบบรับไม่ไหว — หยุดทดสอบอัตโนมัติ' : 'ไม่พบขั้นวิกฤต ✓' ?></div>
    </div>
</div>

<!-- Bar Chart -->
<?php
$maxAvg = max(array_column($stepResults, 'avgTime')) ?: 1;
$scale  = max($maxAvg, WARN_AVG * 1.2);
?>
<div class="chart-wrap">
    <div class="chart-title">📈 Avg Response Time per Concurrent Users</div>
    <div class="bar-chart">
        <?php foreach ($stepResults as $r):
            $pct       = min(100, round($r['avgTime'] / $scale * 100));
            $fillClass = match($r['level']) { 'safe' => 'fill-safe', 'warning' => 'fill-warn', default => 'fill-crit' };
        ?>
        <div class="bar-row">
            <div class="bar-label"><?= $r['users'] ?> users</div>
            <div class="bar-track">
                <div class="bar-fill <?= $fillClass ?>" style="width:<?= max($pct,3) ?>%">
                    <?= $r['avgTime'] ?>ms
                </div>
            </div>
            <div class="bar-info">
                p50 <?= $r['p50'] ?>ms &nbsp; p95 <?= $r['p95'] ?>ms &nbsp; p99 <?= $r['p99'] ?>ms &nbsp;
                <span class="<?= $r['successRate'] >= WARN_RATE ? 'c-safe' : 'c-crit' ?>"><?= $r['successRate'] ?>%✓</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Detailed Table -->
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Users</th><th>Reqs</th><th>✓</th><th>✗</th>
                <th>Rate%</th><th>Avg ms</th><th>Min ms</th><th>Max ms</th>
                <th>P50</th><th>P95</th><th>P99</th><th>Level</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($stepResults as $r):
            $rc = match($r['level']) { 'safe'=>'c-safe','warning'=>'c-warn',default=>'c-crit' };
            $chips = ['safe'=>'chip-safe','warning'=>'chip-warn','critical'=>'chip-crit'];
            $labels= ['safe'=>'✓ SAFE','warning'=>'⚠ WARN','critical'=>'✗ CRIT'];
            $avgC  = $r['avgTime'] <= SAFE_AVG ? 'c-safe' : ($r['avgTime'] <= WARN_AVG ? 'c-warn' : 'c-crit');
        ?>
        <tr>
            <td><b><?= $r['users'] ?></b></td>
            <td class="c-muted"><?= $r['total'] ?></td>
            <td class="c-safe"><?= $r['allSuccess'] ?></td>
            <td class="<?= $r['allFail']>0?'c-crit':'c-muted' ?>"><?= $r['allFail'] ?></td>
            <td class="<?= $rc ?>"><?= $r['successRate'] ?>%</td>
            <td class="<?= $avgC ?>"><?= $r['avgTime'] ?></td>
            <td class="c-muted"><?= $r['minTime'] ?></td>
            <td class="<?= $r['maxTime']>WARN_AVG?'c-crit':'' ?>"><?= $r['maxTime'] ?></td>
            <td><?= $r['p50'] ?></td>
            <td class="<?= $r['p95']>SAFE_AVG?'c-warn':'' ?>"><?= $r['p95'] ?></td>
            <td class="<?= $r['p99']>WARN_AVG?'c-crit':($r['p99']>SAFE_AVG?'c-warn':'') ?>"><?= $r['p99'] ?></td>
            <td><span class="chip <?= $chips[$r['level']] ?>"><?= $labels[$r['level']] ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Recommendations -->
<div class="recommend">
    <h3>💡 คำแนะนำ</h3>
    <ul>
        <?php if ($maxSafeUsers >= 100): ?>
        <li>✅ <strong>ระบบ SAFE ถึง <?= $maxSafeUsers ?> concurrent users</strong> — เพียงพอสำหรับองค์กร ≤100 คนแน่นอน</li>
        <?php elseif ($maxSafeUsers >= 50): ?>
        <li>🟡 <strong>SAFE ได้ถึง <?= $maxSafeUsers ?> users</strong> — ควรเพิ่ม PHP-FPM workers และเปิด OPcache เพื่อรองรับ 100 users</li>
        <?php else: ?>
        <li>🔴 <strong>SAFE เพียง <?= $maxSafeUsers ?> users</strong> — ควร optimize ก่อนขยาย เพิ่ม index MySQL และตรวจ slow query</li>
        <?php endif; ?>
        <?php if ($maxWarnUsers > $maxSafeUsers): ?>
        <li>⚠ ที่ <?= $maxWarnUsers ?> users ระบบยังรับได้ (WARNING) แต่ response time เริ่มสูง — ไม่แนะนำใช้งานต่อเนื่อง</li>
        <?php endif; ?>
        <li>📊 <strong>monitor_data.php</strong> ถูกเรียกทุก 10s — 20 จอ monitor = 2 req/s ต่อเนื่อง, 100 จอ = 10 req/s</li>
        <li>⚡ เพิ่มประสิทธิภาพ: <strong>OPcache</strong>, <strong>MySQL index</strong> บน <code>status</code> + <code>start_job</code>, <strong>PHP-FPM</strong> pm.max_children</li>
        <li>🔁 ทดสอบซ้ำในช่วง <strong>peak time</strong> (07:00–09:00น.) เพื่อดูผลใกล้เคียงจริงที่สุด</li>
        <li>📈 รัน Stress Test ทุกสัปดาห์เพื่อตรวจจับ <strong>performance regression</strong> หลัง deploy ใหม่</li>
    </ul>
</div>

</body>
</html>
