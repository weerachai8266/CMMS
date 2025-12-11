<?php
session_start();

// ลบข้อมูล session
unset($_SESSION['technician_logged_in']);
unset($_SESSION['technician_username']);
unset($_SESSION['login_time']);

// ทำลาย session
session_destroy();

// Redirect กลับไปหน้า login
header('Location: login.php');
exit;
?>
