<?php
session_start();

// ถ้า login แล้วให้ redirect ไปหน้า machines
if (isset($_SESSION['technician_logged_in']) && $_SESSION['technician_logged_in'] === true) {
    header('Location: ../pages/machines.php');
    exit;
}

// ตรวจสอบการ login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // ตรวจสอบ username และ password
    // TODO: ในอนาคตควรเก็บใน database และเข้ารหัส password
    $valid_users = [
        'admin' => 'admin123',
        'technician' => 'tech123',
        'maintenance' => 'mt123'
    ];
    
    if (isset($valid_users[$username]) && $valid_users[$username] === $password) {
        $_SESSION['technician_logged_in'] = true;
        $_SESSION['technician_username'] = $username;
        $_SESSION['login_time'] = time();
        header('Location: ../pages/machines.php');
        exit;
    } else {
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - เจ้าหน้าที่ซ่อมบำรุง</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Sarabun', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
            animation: fadeInUp 0.6s ease;
        }
        
        .login-header {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        
        .login-header i {
            font-size: 3.5rem;
            margin-bottom: 15px;
            animation: pulse 2s infinite;
        }
        
        .login-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .login-header p {
            font-size: 1rem;
            opacity: 0.95;
            margin: 0;
        }
        
        .login-body {
            padding: 40px 35px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #ffc107;
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 193, 7, 0.4);
        }
        
        .btn-back {
            background: #6c757d;
            border: none;
            border-radius: 10px;
            padding: 10px;
            font-size: 1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-back:hover {
            background: #5a6268;
            color: white;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            animation: shake 0.5s;
        }
        
        .login-info {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        
        .login-info strong {
            display: block;
            margin-bottom: 10px;
            color: #007bff;
        }
        
        .login-info code {
            background: white;
            padding: 2px 6px;
            border-radius: 4px;
            color: #e83e8c;
            font-size: 0.85rem;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        @media (max-width: 576px) {
            .login-header {
                padding: 30px 20px;
            }
            
            .login-header h2 {
                font-size: 1.5rem;
            }
            
            .login-body {
                padding: 30px 25px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-user-cog"></i>
            <h2>เจ้าหน้าที่ซ่อมบำรุง</h2>
            <p>กรุณาเข้าสู่ระบบ</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> ชื่อผู้ใช้
                    </label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                        </div>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="กรอกชื่อผู้ใช้" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> รหัสผ่าน
                    </label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                        </div>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="กรอกรหัสผ่าน" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                </button>
                
                <a href="../index.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> กลับหน้าแรก
                </a>
            </form>
            
            <!-- <div class="login-info">
                <strong><i class="fas fa-info-circle"></i> ข้อมูลสำหรับทดสอบ:</strong>
                <div>Username: <code>admin</code> / Password: <code>admin123</code></div>
                <div>Username: <code>technician</code> / Password: <code>tech123</code></div>
                <div>Username: <code>maintenance</code> / Password: <code>mt123</code></div>
            </div> -->
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
