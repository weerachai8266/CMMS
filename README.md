# ระบบแจ้งซ่อมเครื่องจักร — MT Maintenance System

ระบบจัดการการแจ้งซ่อมเครื่องจักรสำหรับแผนก MT พัฒนาด้วย PHP 8, MySQL, Bootstrap 4 และ jQuery

---

## 📐 สูตรคำนวณ KPI (KPI Formulas)

> ข้อมูลทั้งหมดมาจาก `api/kpi_data.php` และคำนวณเพิ่มเติมใน `assets/js/kpi.js`
>
> ตาราง: `mt_repair` (ข้อมูลการแจ้งซ่อม), `mt_machine_history` (ประวัติงานช่าง)
>
> **หมายเหตุ:** รายการที่มี `status = 50` (ยกเลิก) จะถูกตัดออกจากการคำนวณทั้งหมด

---

### 1. Success Rate (อัตราซ่อมสำเร็จ)

$$\text{Success Rate} = \frac{\text{จำนวนรายการซ่อมเสร็จ (status=40)}}{\text{จำนวนรายการทั้งหมด}} \times 100$$

```
completedRepairs / totalRepairs × 100
```

- **สถานะที่นับ:** status = 40 (ซ่อมเสร็จสิ้น)
- **แหล่งข้อมูล:** `mt_repair`

---

### 2. MTTR — Mean Time To Repair (เวลาเฉลี่ยในการซ่อม)

$$\text{MTTR} = \frac{\sum(\text{end\_job} - \text{approved\_at})}{\text{จำนวนรายการ}} \quad \text{(หน่วย: ชั่วโมง)}$$

```sql
AVG(TIMESTAMPDIFF(HOUR, approved_at, end_job))
```

- **แหล่งข้อมูล:** `mt_repair`
- **หมายเหตุ:** นับตั้งแต่วันที่ **อนุมัติ** (`approved_at`) จนถึงเสร็จงาน (`end_job`) เพื่อสะท้อนเวลาซ่อมจริง (ไม่รวมเวลารอ)

---

### 3. Response Time (เวลาตอบสนอง / เวลาอนุมัติ)

$$\text{Response Time} = \frac{\sum(\text{approved\_at} - \text{start\_job})}{\text{จำนวนรายการ}} \quad \text{(หน่วย: นาที)}$$

```sql
AVG(TIMESTAMPDIFF(MINUTE, start_job, approved_at))
```

- **แหล่งข้อมูล:** `mt_repair`
- **ความหมาย:** เวลาเฉลี่ยตั้งแต่ยื่นแจ้งซ่อมจนได้รับการอนุมัติ

---

### 4. MTBF — Mean Time Between Failure (เวลาเฉลี่ยระหว่างความเสีย)

**ระดับเครื่องจักรรายเครื่อง:**

$$\text{MTBF}_{\text{เครื่อง}} = \frac{\text{last\_failure} - \text{first\_failure}}{\text{failure\_count} - 1} \quad \text{(หน่วย: ชั่วโมง)}$$

```php
$period_hours = (strtotime($last_failure) - strtotime($first_failure)) / 3600;
$mtbf_hours   = $period_hours / ($failure_count - 1);
$mtbf_days    = $mtbf_hours / 24;
```

**ระดับระบบรวม:**

$$\text{MTBF}_{\text{ระบบ}} = \frac{\text{ช่วงเวลาทั้งหมด (ชั่วโมง)}}{\text{จำนวน failures ทั้งหมด}}$$

```php
$overall_mtbf_hours = $total_period_hours / $total_failures;
$overall_mtbf_days  = $overall_mtbf_hours / 24;
```

- **แหล่งข้อมูล:** `mt_repair` — เครื่องที่มี failure > 1 ครั้ง
- **ยิ่งสูงยิ่งดี** — หมายถึงเครื่องเสียห่างกันมากขึ้น

---

### 5. OEE — Overall Equipment Effectiveness (ประสิทธิผลโดยรวม)

$$\text{OEE} = \text{Availability} \times \text{Quality} \times 100$$

โดยที่:

$$\text{Availability} = \frac{\text{Work Hours}}{\text{Work Hours} + \text{Downtime Hours}}$$

$$\text{Quality} = \frac{\text{Success Rate}}{100}$$

```javascript
const totalTime    = totalWorkHours + totalDowntimeHours;
const availability = totalTime > 0 ? (totalTime - totalDowntimeHours) / totalTime : 0;
const quality      = successRate / 100;
const oee          = (availability * quality * 100).toFixed(1);
```

- **แหล่งข้อมูล:** `mt_machine_history` (work_hours, downtime_hours) + Success Rate
- **หมายเหตุ:** Performance factor ไม่ได้คำนวณแยก (ถือเป็น 1.0) เพราะไม่มีข้อมูล capacity

---

### 6. First Time Fix Rate (อัตราซ่อมสำเร็จครั้งแรก)

$$\text{FTFR} = \frac{\text{จำนวนรายการซ่อมเสร็จ (status=40)}}{\text{จำนวนรายการทั้งหมด}} \times 100$$

```sql
(COUNT(CASE WHEN status = 40 THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0))
```

- **แหล่งข้อมูล:** `mt_repair`
- **หมายเหตุ:** ในระบบนี้ FTFR = Success Rate (ยังไม่ติดตามการซ่อมซ้ำ)

---

### 7. Pareto (การวิเคราะห์สาเหตุการเสีย)

$$\text{Percentage}_{\text{สาเหตุ}} = \frac{\text{จำนวนครั้งที่เสียด้วยสาเหตุนั้น}}{\text{จำนวนรายการทั้งหมด}} \times 100$$

```sql
COUNT(*) * 100.0 / (SELECT COUNT(*) FROM mt_repair WHERE ...) as percentage
```

- แสดง Top 20 สาเหตุ เรียงจากมากไปน้อย
- **แหล่งข้อมูล:** `mt_repair.issue`

---

### 8. Completion Rate รายเดือน (Monthly Completion Rate)

$$\text{Completion Rate}_{\text{เดือน}} = \frac{\text{จำนวนซ่อมเสร็จในเดือน}}{\text{จำนวนรายการทั้งหมดในเดือน}} \times 100$$

```sql
SUM(CASE WHEN status = 40 THEN 1 ELSE 0 END) / COUNT(*) * 100 as completion_rate
```

- **แหล่งข้อมูล:** `mt_repair` — group by เดือน, ย้อนหลัง 12 เดือน

---

### 9. สถิติต้นทุน (Cost Statistics)

| Metric | สูตร | แหล่งข้อมูล |
|--------|------|-------------|
| ค่าใช้จ่ายรวม | `SUM(total_cost)` | `mt_machine_history` |
| ค่าใช้จ่ายเฉลี่ย/งาน | `AVG(total_cost)` | `mt_machine_history` |
| ชั่วโมงทำงานรวม | `SUM(work_hours)` | `mt_machine_history` |
| ชั่วโมงหยุดเครื่องรวม | `SUM(downtime_hours)` | `mt_machine_history` |

---

### 10. Period Comparison (เปรียบเทียบช่วงเวลา)

ระบบเปรียบเทียบอัตโนมัติกับช่วงก่อนหน้าที่มีความยาวเท่ากัน:

```php
$period_days    = diff(date_from, date_to) + 1;
$prev_date_from = date_from - $period_days days;
$prev_date_to   = date_to   - $period_days days;
```

Metrics ที่เปรียบเทียบ: `total_repairs`, `success_rate`, `avg_repair_hours` (MTTR), `avg_approval_minutes` (Response Time), `first_time_fix_rate`

Trend badge แสดงด้วย:
- 🟢 ดีขึ้น (เพิ่มสำหรับ rate, ลดสำหรับ time)
- 🔴 แย่ลง
- ⚪ ไม่เปลี่ยนแปลง

---

### 11. Work Hours / Downtime Hours (จาก machine_history)

```sql
-- Work Hours แยกตาม status
AVG(work_hours)    -- เฉลี่ยชั่วโมงทำงานต่อ status
SUM(work_hours)    -- รวมชั่วโมงทำงานทุก status
MIN/MAX(work_hours)

-- Downtime Hours แยกตาม status
AVG(downtime_hours)
SUM(downtime_hours)
```

- **แหล่งข้อมูล:** `mt_machine_history` — group by status

---

## 🔢 รหัสสถานะ (Status Codes)

| Code | ความหมาย | ใช้ใน KPI |
|------|----------|-----------|
| 10 | รออนุมัติ (Pending Approval) | นับใน total |
| 11 | ไม่อนุมัติ (Rejected) | นับใน total |
| 20 | รอดำเนินการ (In Progress) | นับใน total |
| 30 | รออะไหล่ (Waiting Parts) | นับใน total |
| 40 | ซ่อมเสร็จสิ้น (Completed) | **ตัวเศษ** ของ Success Rate / FTFR |
| 50 | ยกเลิก (Cancelled) | **ตัดออก** จากทุกการคำนวณ |

---

## 🗂️ โครงสร้างตารางที่เกี่ยวข้อง

### `mt_repair`
| Field | ใช้ใน |
|-------|-------|
| `status` | ทุก KPI |
| `start_job` | MTBF, Response Time (เริ่มต้น), Daily Trend |
| `approved_at` | MTTR (เริ่มต้น), Response Time (สิ้นสุด) |
| `end_job` | MTTR (สิ้นสุด) |
| `branch` | KPI แยกสาขา |
| `department` | KPI แยกแผนก |
| `machine_number` | Top Frequent Machines, MTBF |
| `issue` | Pareto Chart |

### `mt_machine_history`
| Field | ใช้ใน |
|-------|-------|
| `work_hours` | OEE (Availability), Total Work Hours |
| `downtime_hours` | OEE (Availability), Total Downtime |
| `total_cost` | Cost Statistics |
| `handled_by` | Top Technician |
| `machine_code`, `machine_name` | Top Expensive Machines |

---

## 📊 กราฟบน KPI Dashboard

| กราฟ | ประเภท | ข้อมูล |
|------|--------|--------|
| สัดส่วนสถานะ | Doughnut | status_stats |
| แนวโน้มรายวัน | Line | daily_trend (30 วัน) |
| ตามแผนก | Bar | department_stats |
| Monthly Performance | Bar | monthly_performance (12 เดือน) |
| Pareto Chart | Bar + Line | failure_causes (Top 20) |
| สถานะ (%) | Doughnut | status_stats |

---

## ⚙️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.x, PDO MySQL |
| Database | MySQL (host: 192.168.0.44) |
| Frontend | Bootstrap 4.5, jQuery 3.5.1 |
| Charts | Chart.js 3.9.1 |
| Export | jsPDF 2.5.1, html2canvas, SheetJS |
| Font | Google Fonts — Sarabun |

---

## 🌐 URL หลัก

| หน้า | URL |
|------|-----|
| หน้าหลัก | `/mt/` |
| Monitor | `/mt/pages/monitor.php` |
| KPI Dashboard | `/mt/pages/kpi.php` |
| เครื่องจักร | `/mt/pages/machines.php` |
| Test Suite | `/mt/tests/run.php` |
| Stress Test | `/mt/tests/stress.php` |
