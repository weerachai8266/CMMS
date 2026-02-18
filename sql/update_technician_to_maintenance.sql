-- อัพเดท role จาก 'technician' เป็น 'maintenance' ในตาราง mt_users
-- รันคำสั่งนี้เพื่ออัพเดท users ที่มีอยู่แล้วในระบบ

UPDATE mt_users 
SET role = 'maintenance' 
WHERE role = 'technician';

-- ตรวจสอบผลลัพธ์
SELECT id, username, full_name, role 
FROM mt_users 
WHERE role = 'maintenance';

-- หมายเหตุ:
-- คำสั่งนี้จะเปลี่ยน role ของ user ทั้งหมดที่เป็น 'technician' ให้เป็น 'maintenance'
-- เช่น user ที่ชื่อ 'technician' จะกลายเป็น role = 'maintenance'
