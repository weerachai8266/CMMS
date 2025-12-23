# การเก็บข้อมูลอุปกรณ์สำหรับการอนุมัติ/ปฏิเสธ

## วัตถุประสงค์
เพื่อตรวจสอบความปลอดภัยและดูว่ามีการอนุมัติผิดปกติหรือไม่ เนื่องจากระบบยังไม่มีการ Login

## ข้อมูลที่เก็บ
1. **device_type** - ประเภทอุปกรณ์: desktop, mobile, tablet
2. **browser** - เบราว์เซอร์: Chrome, Firefox, Safari, Edge, etc.
3. **os** - ระบบปฏิบัติการ: Windows, macOS, iOS, Android, Linux
4. **ip_address** - IP Address ของผู้อนุมัติ/ปฏิเสธ

## ไฟล์ที่แก้ไข

### 1. ฐานข้อมูล
- `sql/alter_approval_log_add_device_info.sql` - เพิ่มคอลัมน์ใหม่ใน mt_approval_log

### 2. Backend API
- `api/approve_repair.php` - รับและบันทึกข้อมูลอุปกรณ์เมื่ออนุมัติ
- `api/reject_repair.php` - รับและบันทึกข้อมูลอุปกรณ์เมื่อปฏิเสธ

### 3. Frontend
- `pages/approval.php` - เพิ่ม JavaScript ตรวจจับอุปกรณ์และส่งข้อมูล

## วิธีใช้งาน

### ขั้นตอนที่ 1: รัน SQL
```bash
mysql -u user -p maintenance < sql/alter_approval_log_add_device_info.sql
```

หรือเข้า phpMyAdmin แล้วรันคำสั่ง SQL

### ขั้นตอนที่ 2: ทดสอบระบบ
1. เข้าหน้าอนุมัติใบแจ้งซ่อม
2. ลองอนุมัติ/ปฏิเสธใบแจ้งซ่อม
3. ตรวจสอบข้อมูลใน mt_approval_log

### ขั้นตอนที่ 3: ตรวจสอบข้อมูล
```sql
SELECT 
    id, 
    repair_id, 
    approver, 
    action, 
    device_type, 
    browser, 
    os, 
    ip_address, 
    approved_at 
FROM mt_approval_log 
ORDER BY approved_at DESC 
LIMIT 20;
```

## ประโยชน์

1. **ตรวจสอบการอนุมัติผิดปกติ**
   - ดูว่ามีการอนุมัติจาก IP แปลกๆ หรือไม่
   - ตรวจสอบว่ามีการใช้งานจากอุปกรณ์ที่ผิดปกติหรือไม่

2. **วิเคราะห์พฤติกรรม**
   - ดูว่าผู้อนุมัติใช้อุปกรณ์อะไรบ่อย
   - วิเคราะห์เวลาที่มีการอนุมัติ

3. **รายงาน**
   - สรุปการอนุมัติแยกตามประเภทอุปกรณ์
   - ดู IP ที่มีการอนุมัติบ่อย

## ตัวอย่าง Query ที่มีประโยชน์

### ดูการอนุมัติจากแต่ละอุปกรณ์
```sql
SELECT 
    device_type,
    COUNT(*) as count,
    COUNT(CASE WHEN action = 'approved' THEN 1 END) as approved,
    COUNT(CASE WHEN action = 'rejected' THEN 1 END) as rejected
FROM mt_approval_log
GROUP BY device_type;
```

### ดู Browser ที่ใช้มากที่สุด
```sql
SELECT 
    browser,
    COUNT(*) as count
FROM mt_approval_log
GROUP BY browser
ORDER BY count DESC;
```

### ดู IP ที่มีการอนุมัติบ่อย
```sql
SELECT 
    ip_address,
    approver,
    COUNT(*) as count
FROM mt_approval_log
GROUP BY ip_address, approver
HAVING count > 5
ORDER BY count DESC;
```

### ตรวจหาการอนุมัติผิดปกติ
```sql
-- ดูการอนุมัตินอกเวลาทำงาน (หลัง 18:00 หรือก่อน 08:00)
SELECT *
FROM mt_approval_log
WHERE HOUR(approved_at) >= 18 OR HOUR(approved_at) < 8
ORDER BY approved_at DESC;

-- ดูการอนุมัติจาก IP เดียวกันหลายชื่อ
SELECT 
    ip_address,
    GROUP_CONCAT(DISTINCT approver) as approvers,
    COUNT(*) as count
FROM mt_approval_log
GROUP BY ip_address
HAVING COUNT(DISTINCT approver) > 3;
```

## หมายเหตุ
- ระบบจะเก็บข้อมูลอัตโนมัติทุกครั้งที่มีการอนุมัติ/ปฏิเสธ
- ไม่มีผลกระทบต่อการใช้งานปัจจุบัน
- สามารถนำข้อมูลไปวิเคราะห์เพื่อเพิ่มความปลอดภัยในอนาคตได้
