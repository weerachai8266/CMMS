# ระบบแจ้งซ่อมเครื่องจักร (Maintenance Request System)

ระบบจัดการการแจ้งซ่อมเครื่องจักรสำหรับแผนก MT (Maintenance) พัฒนาด้วย PHP, MySQL และ Bootstrap

## 📋 คุณสมบัติ

- ✅ แจ้งซ่อมเครื่องจักร
- ✅ แสดงรายการแจ้งซ่อมทั้งหมด
- ✅ อัพเดทสถานะการซ่อม 3 แบบ:
  - 🟡 รอดำเนินการ (Pending)
  - 🔴 รออะไหล่ (Waiting for Parts)
  - 🟢 ซ่อมเสร็จแล้ว (Completed)
- ✅ หน้า Monitor แบบ Full Screen
- ✅ บันทึกเวลาเริ่มและเวลาสิ้นสุดการซ่อม
- ✅ รองรับการใช้งานบนมือถือ (Responsive Design)
- ✅ ป้องกัน SQL Injection ด้วย Prepared Statements
- ✅ แสดงข้อความแจ้งเตือนแบบ Real-time

## 🗂️ โครงสร้างโปรเจค

```
/var/www/html/mt/
│
├── 📁 config/                  # การตั้งค่า
│   ├── config.php             # การตั้งค่าทั่วไป
│   ├── db.php                 # การเชื่อมต่อฐานข้อมูล (ไม่ commit)
│   └── db.example.php         # ตัวอย่างการตั้งค่า DB
│
├── 📁 api/                     # API Endpoints
│   ├── save.php               # บันทึกข้อมูลการแจ้งซ่อม
│   ├── display.php            # แสดงรายการแจ้งซ่อม
│   ├── update_status.php      # อัพเดทสถานะการซ่อม
│   ├── monitor_data.php       # ดึงข้อมูลสำหรับ Monitor
│   └── monitor_update.php     # อัพเดทสถานะจาก Monitor
│
├── 📁 pages/                   # หน้าเว็บ
│   └── monitor.php            # หน้า Monitor Full Screen
│
├── 📁 assets/                  # ไฟล์ทรัพยากร
│   ├── css/
│   │   └── style.css          # CSS กำหนดรูปแบบ
│   └── js/
│       └── main.js            # JavaScript หลัก
│
├── 📁 docs/                    # เอกสาร
│   ├── README.md              # เอกสารนี้
│   ├── INSTALL.md             # คู่มือติดตั้ง
│   ├── STRUCTURE.md           # โครงสร้างไฟล์ละเอียด
│   ├── CHANGELOG.md           # บันทึกการเปลี่ยนแปลง
│   ├── ADMIN_GUIDE.md         # คู่มือผู้ดูแลระบบ
│   ├── MONITOR_GUIDE.md       # คู่มือการใช้งาน Monitor
│   └── INFO.txt               # ข้อมูลสรุป
│
├── 📄 index.php                # หน้าหลัก
├── 📄 test_connection.php      # ทดสอบการเชื่อมต่อ DB
├── 📄 .htaccess                # การตั้งค่า Apache
├── 📄 .gitignore               # Git ignore rules
└── 📄 sql.sql                  # โครงสร้างฐานข้อมูล

```

## 🗄️ โครงสร้างฐานข้อมูล

### ตาราง `mt_repair`

| Field          | Type          | Description                    |
|----------------|---------------|--------------------------------|
| id             | INT           | Primary Key (Auto Increment)   |
| department     | VARCHAR(100)  | ชื่อแผนก                       |
| machine_number | VARCHAR(50)   | หมายเลขเครื่องจักร             |
| issue          | VARCHAR(255)  | อาการเสีย                      |
| mt_report      | VARCHAR(255)  | รายงานจากแผนก MT               |
| stutus         | INT           | สถานะ (0/1/2)                 |
| start_job      | TIMESTAMP     | เวลาที่แจ้งซ่อม                 |
| end_job        | TIMESTAMP     | เวลาที่ซ่อมเสร็จ                |

**สถานะ:**
- `0` = รอดำเนินการ (Pending)
- `1` = ซ่อมเสร็จแล้ว (Completed)
- `2` = รออะไหล่ (Waiting for Parts)

## ⚙️ การติดตั้ง

### 1. ติดตั้งฐานข้อมูล

```sql
CREATE DATABASE automotive;
USE automotive;

CREATE TABLE `mt_repair` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `department` VARCHAR(100) NOT NULL,
    `machine_number` VARCHAR(50) NOT NULL,
    `issue` VARCHAR(255) NOT NULL,
    `mt_report` VARCHAR(255) NOT NULL,
    `stutus` INT NOT NULL COMMENT '0=รอดำเนินการ, 1=ซ่อมเสร็จแล้ว, 2=รออะไหล่',
    `start_job` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `end_job` TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;
```

### 2. ตั้งค่าการเชื่อมต่อฐานข้อมูล

คัดลอกไฟล์ตัวอย่าง:
```bash
cp config/db.example.php config/db.php
```

แก้ไขไฟล์ `config/db.php`:
```php
$servername = "localhost";      // IP หรือชื่อเซิร์ฟเวอร์ MySQL
$username   = "root";           // ชื่อผู้ใช้ MySQL
$password   = "your_password";  // รหัสผ่าน MySQL
$dbname     = "automotive";     // ชื่อฐานข้อมูล
```

### 3. เข้าใช้งาน

เปิดเว็บเบราว์เซอร์:
```
http://localhost/mt/
```

## 🔒 Security Features

- ✅ **Prepared Statements**: ป้องกัน SQL Injection
- ✅ **Input Validation**: ตรวจสอบข้อมูลนำเข้า
- ✅ **XSS Protection**: ใช้ `htmlspecialchars()` แสดงข้อมูล
- ✅ **HTTP Method Validation**: ตรวจสอบ POST/GET requests
- ✅ **PDO Error Mode**: จัดการ errors อย่างปลอดภัย

## 📱 การใช้งาน

### หน้าหลัก (index.php)
- กรอกฟอร์มแจ้งซ่อม
- ดูรายการแจ้งซ่อม
- เปลี่ยนสถานะ

### หน้า Monitor (pages/monitor.php)
- แสดงผลแบบ Full Screen
- เหมาะสำหรับติดจอแสดงผล
- Auto-refresh ทุก 10 วินาที
- เปลี่ยนสถานะได้โดยตรง

## 🚀 Features ที่จะเพิ่มในอนาคต

- [ ] ระบบ Login/Authentication
- [ ] Export ข้อมูลเป็น PDF/Excel
- [ ] แจ้งเตือนผ่าน Email/LINE
- [ ] Dashboard สรุปสถิติการซ่อม
- [ ] Upload รูปภาพประกอบการแจ้งซ่อม
- [ ] ระบบค้นหาและกรองข้อมูล

## 📚 เอกสารเพิ่มเติม

- [คู่มือติดตั้ง](INSTALL.md)
- [โครงสร้างโปรเจค](STRUCTURE.md)
- [คู่มือผู้ดูแลระบบ](ADMIN_GUIDE.md)
- [คู่มือการใช้งาน Monitor](MONITOR_GUIDE.md)
- [บันทึกการเปลี่ยนแปลง](CHANGELOG.md)

## 📄 License

MIT License - ใช้งานได้อย่างอิสระ

## 👨‍💻 Developer

MT Department - Automotive Division

---

**Version:** 2.0  
**Last Updated:** November 12, 2025
