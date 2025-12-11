# ไอเดียระบบอนุมัติและการแจ้งเตือน

## 1. ระบบอนุมัติ (Approval System)

### แนวทางที่ 1: อนุมัติแบบง่าย (Simple Approval)
**เหมาะสำหรับ:** องค์กรขนาดเล็ก-กลาง

**Flow:**
```
[แจ้งซ่อม] → สถานะ 10 (รออนุมัติ)
    ↓
[หัวหน้า MT อนุมัติ] → สถานะ 20 (รอดำเนินการ)
    ↓
[ช่างดำเนินการ] → สถานะ 30/40
```

**หน้าที่ต้องเพิ่ม:**
- หน้าอนุมัติสำหรับหัวหน้า MT (แสดงเฉพาะสถานะ 10)
- ปุ่ม "อนุมัติ" / "ปฏิเสธ" พร้อมเหตุผล
- บันทึกประวัติการอนุมัติ (ผู้อนุมัติ, วันที่, เวลา, หมายเหตุ)

**ตารางเพิ่มใน Database:**
```sql
CREATE TABLE mt_approval_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repair_id INT NOT NULL,
    approver VARCHAR(100) NOT NULL COMMENT 'ผู้อนุมัติ',
    action ENUM('approved', 'rejected') NOT NULL,
    reason TEXT COMMENT 'เหตุผล',
    approved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (repair_id) REFERENCES mt_repair(id)
);
```

---

### แนวทางที่ 2: อนุมัติหลายขั้น (Multi-level Approval)
**เหมาะสำหรับ:** องค์กรขนาดใหญ่ มีลำดับชั้นชัดเจน

**Flow:**
```
[แจ้งซ่อม] → สถานะ 10 (รออนุมัติหัวหน้าแผนก)
    ↓
[หัวหน้าแผนกอนุมัติ] → สถานะ 11 (รออนุมัติ MT)
    ↓
[หัวหน้า MT อนุมัติ] → สถานะ 20 (รอดำเนินการ)
    ↓
[ช่างดำเนินการ]
```

**สถานะเพิ่มเติม:**
- 10 = รออนุมัติหัวหน้าแผนก
- 11 = รออนุมัติหัวหน้า MT
- 12 = รออนุมัติผู้บริหาร (กรณีค่าใช้จ่ายสูง)
- 19 = ไม่อนุมัติ (Rejected)

---

### แนวทางที่ 3: อนุมัติตามเงื่อนไข (Conditional Approval)
**เหมาะสำหรับ:** ลดขั้นตอนงานเร่งด่วน

**เงื่อนไข:**
- งานเร่งด่วน (urgent) → อนุมัติอัตโนมัติ → สถานะ 20
- งานทั่วไป → ต้องอนุมัติ → สถานะ 10
- ค่าใช้จ่ายต่ำกว่า 5,000 บาท → อนุมัติอัตโนมัติ
- ค่าใช้จ่ายสูงกว่า 5,000 บาท → ต้องอนุมัติ

**Fields เพิ่มใน mt_repair:**
```sql
ALTER TABLE mt_repair ADD COLUMN is_urgent TINYINT(1) DEFAULT 0;
ALTER TABLE mt_repair ADD COLUMN estimated_cost DECIMAL(10,2);
ALTER TABLE mt_repair ADD COLUMN auto_approved TINYINT(1) DEFAULT 0;
```

---

## 2. ระบบแจ้งเตือน (Notification System)

### ตัวเลือก 1: อีเมล (Email Notification) ⭐ แนะนำ
**ข้อดี:**
- เข้าถึงง่าย ไม่ต้องติดตั้งแอพ
- มี log ประวัติการส่ง
- สามารถแนบไฟล์/รูปภาพได้

**เมื่อไหร่ที่ต้องส่ง:**
1. **เมื่อมีการแจ้งซ่อมใหม่** → ส่งถึง: หัวหน้า MT
2. **เมื่อได้รับการอนุมัติ** → ส่งถึง: ผู้แจ้ง, ช่าง MT
3. **เมื่อเปลี่ยนสถานะ** → ส่งถึง: ผู้แจ้ง
4. **เมื่องานเสร็จสิ้น** → ส่งถึง: ผู้แจ้ง, หัวหน้าแผนก
5. **เตือนงานค้าง** (เกิน 24 ชม.) → ส่งถึง: หัวหน้า MT

**การติดตั้ง (PHP):**
```php
// ใช้ PHPMailer หรือ Mail Server ของบริษัท
composer require phpmailer/phpmailer

// หรือใช้ SMTP ของ Gmail, Outlook
```

**ตัวอย่าง Template อีเมล:**
```
หัวข้อ: [MT Alert] มีใบแจ้งซ่อมรออนุมัติ #12345

เรียน คุณ [ชื่อผู้อนุมัติ]

มีใบแจ้งซ่อมรอการอนุมัติ:

- เลขที่: MT-2025-001
- เครื่องจักร: M-CNC-001
- ปัญหา: มอเตอร์ร้อนผิดปกติ
- ผู้แจ้ง: นาย ก. [แผนก Production]
- วันที่แจ้ง: 19/11/2025 14:30

กรุณาคลิกเพื่ออนุมัติ/ปฏิเสธ:
[ปุ่มอนุมัติ] [ปุ่มปฏิเสธ] [ดูรายละเอียด]

หรือเข้าระบบที่: http://your-domain/mt/pages/approval.php

ระบบ MT
```

---

### ตัวเลือก 2: LINE Notify ⭐ แนะนำมาก (สำหรับไทย)
**ข้อดี:**
- คนไทยใช้ LINE เกือบทุกคน
- ตั้งค่าง่าย ฟรี
- แจ้งเตือนแบบ Real-time
- รองรับรูปภาพ, สติกเกอร์

**วิธีติดตั้ง:**
1. สมัคร LINE Notify Token ที่ https://notify-bot.line.me/
2. สร้างกลุ่ม LINE สำหรับ MT Team
3. เพิ่ม LINE Notify เข้ากลุ่ม
4. ใช้ Token ส่งข้อความผ่าน API

**ตัวอย่าง Code (PHP):**
```php
function sendLineNotify($message, $token) {
    $url = 'https://notify-api.line.me/api/notify';
    $headers = [
        'Content-Type: application/x-www-form-urlencoded',
        'Authorization: Bearer ' . $token
    ];
    
    $data = ['message' => $message];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}

// ใช้งาน
$message = "
🔔 มีใบแจ้งซ่อมใหม่!
📋 เลขที่: MT-2025-001
🔧 เครื่องจักร: M-CNC-001
⚠️ ปัญหา: มอเตอร์ร้อนผิดปกติ
👤 ผู้แจ้ง: นาย ก.
📅 วันที่: 19/11/2025 14:30
";

sendLineNotify($message, 'YOUR_TOKEN');
```

**Token แยกตามหน้าที่:**
- Token 1: กลุ่มหัวหน้า MT (รับแจ้งเตือนรออนุมัติ)
- Token 2: กลุ่มช่าง MT (รับแจ้งเตือนงานใหม่)
- Token 3: กลุ่ม Admin (รับทุกการแจ้งเตือน)

---

### ตัวเลือก 3: Web Push Notification
**ข้อดี:**
- แจ้งเตือนผ่านเบราว์เซอร์
- ไม่ต้องเปิดแท็บ

**ข้อเสีย:**
- ต้อง allow notification ก่อน
- ไม่แจ้งเตือนเมื่อปิดเบราว์เซอร์

**เหมาะสำหรับ:**
- เจ้าหน้าที่ที่เปิดระบบทิ้งไว้ตลอดเวลา

---

### ตัวเลือก 4: SMS
**ข้อดี:**
- เข้าถึงได้แม้ไม่มีอินเทอร์เน็ต

**ข้อเสีย:**
- มีค่าใช้จ่าย (ประมาณ 0.25-0.50 บาท/ข้อความ)
- จำกัดจำนวนตัวอักษร

**เหมาะสำหรับ:**
- งานเร่งด่วนมาก
- ผู้บริหารระดับสูง

---

## 3. สรุปคำแนะนำ

### 📌 แนะนำให้ใช้ (Priority Order):

#### 🥇 อันดับ 1: LINE Notify + Email
- **LINE Notify** สำหรับ: แจ้งเตือนแบบ Real-time
- **Email** สำหรับ: บันทึกและ Formal communication

#### 🥈 อันดับ 2: ระบบอนุมัติแบบง่าย
- เริ่มจากแบบง่ายก่อน (1 ขั้น)
- ขยายเป็นหลายขั้นตอนภายหลังได้

---

## 4. Implementation Plan (แผนการพัฒนา)

### Phase 1: ระบบอนุมัติพื้นฐาน (1-2 วัน)
- [ ] สร้างหน้า `approval.php` สำหรับหัวหน้า MT
- [ ] สร้าง API `approve_repair.php`, `reject_repair.php`
- [ ] สร้างตาราง `mt_approval_log`
- [ ] เพิ่ม field `approver`, `approved_at`, `reject_reason` ใน mt_repair

### Phase 2: ระบบแจ้งเตือน LINE (1 วัน)
- [ ] สมัคร LINE Notify Token
- [ ] สร้าง function `sendLineNotify()`
- [ ] ใส่การแจ้งเตือนใน:
  - save.php (แจ้งซ่อมใหม่)
  - approve_repair.php (อนุมัติแล้ว)
  - update_status.php (เปลี่ยนสถานะ)

### Phase 3: ระบบแจ้งเตือนอีเมล (1-2 วัน)
- [ ] ติดตั้ง PHPMailer
- [ ] สร้าง Email Templates
- [ ] ตั้งค่า SMTP
- [ ] เพิ่ม field `email` ใน user table
- [ ] ส่งอีเมลแจ้งเตือน

### Phase 4: งานค้างและรายงาน (1 วัน)
- [ ] สร้างระบบเช็คงานค้าง (>24 ชม.)
- [ ] Cron job แจ้งเตือนงานค้าง
- [ ] Dashboard สำหรับติดตามงาน

---

## 5. ตัวอย่างโครงสร้างไฟล์

```
/var/www/html/mt/
├── pages/
│   ├── approval.php          (หน้าอนุมัติสำหรับหัวหน้า MT)
│   └── notifications.php     (ตั้งค่าการแจ้งเตือน)
│
├── api/
│   ├── approve_repair.php    (API อนุมัติ)
│   ├── reject_repair.php     (API ปฏิเสธ)
│   └── send_notification.php (ส่งแจ้งเตือน)
│
├── lib/
│   ├── LineNotify.php        (Class สำหรับ LINE)
│   └── EmailNotify.php       (Class สำหรับ Email)
│
└── config/
    └── notification.php      (กำหนด Token, Email)
```

---

## 6. ตัวอย่าง UI หน้าอนุมัติ

```
┌─────────────────────────────────────────────────────┐
│  📋 รายการรออนุมัติ (5)                              │
├─────────────────────────────────────────────────────┤
│                                                     │
│  🔴 #001 | M-CNC-001 | มอเตอร์ร้อน                 │
│     ผู้แจ้ง: นาย ก. | แผนก: Production             │
│     แจ้งเมื่อ: 19/11/2025 14:30 (30 นาทีที่แล้ว)   │
│     [✅ อนุมัติ]  [❌ ปฏิเสธ]  [📄 ดูเพิ่ม]        │
│                                                     │
│  🔴 #002 | M-PRESS-05 | แผ่นหลังร้าว                │
│     ผู้แจ้ง: นาง ข. | แผนก: Assembly               │
│     แจ้งเมื่อ: 19/11/2025 13:15 (2 ชม.ที่แล้ว)     │
│     [✅ อนุมัติ]  [❌ ปฏิเสธ]  [📄 ดูเพิ่ม]        │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

อยากให้ช่วยพัฒนาส่วนไหนก่อนครับ?
- หน้าอนุมัติ (approval.php)
- LINE Notify
- Email Notification
- หรือทั้งหมดเลย?
