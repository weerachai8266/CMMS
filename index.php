<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>ระบบแจ้งซ่อม - Maintenance Request System</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Sarabun', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            overflow-x: hidden;
        }
        
        .main-container {
            max-width: 1800px;
            margin: 0 auto;
        }
        
        .header-section {
            text-align: center;
            color: white;
            margin-bottom: 60px;
            animation: fadeInDown 0.8s ease;
        }
        
        .logo-area {
            margin-bottom: 25px;
        }
        
        .logo-area i {
            font-size: 5.5rem;
            text-shadow: 3px 3px 8px rgba(0,0,0,0.3);
            animation: pulse 2s infinite;
        }
        
        .header-section h1 {
            font-size: 3.8rem;
            font-weight: 800;
            margin-bottom: 15px;
            text-shadow: 2px 2px 6px rgba(0,0,0,0.3);
            letter-spacing: 2px;
        }
        
        .header-section p {
            font-size: 1.4rem;
            opacity: 0.95;
            font-weight: 400;
            letter-spacing: 1px;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }
        
        .menu-card {
            background: white;
            border-radius: 25px;
            padding: 55px 40px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease;
        }
        
        .menu-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.9), transparent);
            animation: shimmer 3s infinite;
        }
        
        .menu-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 4px;
            background: currentColor;
            transition: width 0.4s ease;
        }
        
        .menu-card:hover::after {
            width: 80%;
        }
        
        .menu-card:hover {
            transform: translateY(-18px) scale(1.03);
            box-shadow: 0 30px 60px rgba(0,0,0,0.35);
            text-decoration: none;
            color: inherit;
        }
        
        .icon-wrapper {
            width: 110px;
            height: 110px;
            margin: 0 auto 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: all 0.5s ease;
        }
        
        .menu-card:hover .icon-wrapper {
            transform: rotate(360deg) scale(1.15);
        }
        
        .icon-wrapper i {
            font-size: 3.8rem;
            color: white;
            transition: all 0.3s ease;
        }
        
        .menu-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 18px;
            color: #2c3e50;
        }
        
        .menu-card p {
            color: #7f8c8d;
            font-size: 1.05rem;
            line-height: 1.7;
            margin: 0;
        }
        
        /* Card Themes */
        .menu-card.repair {
            border-top: 6px solid #28a745;
        }
        
        .menu-card.repair .icon-wrapper {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.4);
        }
        
        .menu-card.repair::after {
            color: #28a745;
        }
        
        .menu-card.machines {
            border-top: 6px solid #ffc107;
        }
        
        .menu-card.machines .icon-wrapper {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            box-shadow: 0 10px 25px rgba(255, 193, 7, 0.4);
        }
        
        .menu-card.machines::after {
            color: #ffc107;
        }
        
        .menu-card.monitor {
            border-top: 6px solid #007bff;
        }
        
        .menu-card.monitor .icon-wrapper {
            background: linear-gradient(135deg, #007bff 0%, #17a2b8 100%);
            box-shadow: 0 10px 25px rgba(0, 123, 255, 0.4);
        }
        
        .menu-card.monitor::after {
            color: #007bff;
        }
        
        .menu-card.approval {
            border-top: 6px solid #6f42c1;
        }
        
        .menu-card.approval .icon-wrapper {
            background: linear-gradient(135deg, #6f42c1 0%, #9b59b6 100%);
            box-shadow: 0 10px 25px rgba(111, 66, 193, 0.4);
        }
        
        .menu-card.approval::after {
            color: #6f42c1;
        }
        
        .menu-card.kpi {
            border-top: 6px solid #dc3545;
        }
        
        .menu-card.kpi .icon-wrapper {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
            box-shadow: 0 10px 25px rgba(220, 53, 69, 0.4);
        }
        
        .menu-card.kpi::after {
            color: #dc3545;
        }
        
        .menu-card.disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }
        
        .menu-card.disabled:hover {
            transform: translateY(-5px) scale(1);
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
        }
        
        .menu-card.disabled .icon-wrapper {
            animation: none;
        }
        
        .badge-coming {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 8px 18px;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
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
                transform: scale(1.08);
            }
        }
        
        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(100%);
            }
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .menu-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 30px;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 25px 15px;
            }
            
            .logo-area i {
                font-size: 4rem;
            }
            
            .header-section h1 {
                font-size: 2.5rem;
            }
            
            .header-section p {
                font-size: 1.2rem;
            }
            
            .menu-grid {
                grid-template-columns: 1fr;
                gap: 25px;
            }
            
            .menu-card {
                padding: 45px 30px;
            }
            
            .icon-wrapper {
                width: 90px;
                height: 90px;
            }
            
            .icon-wrapper i {
                font-size: 3rem;
            }
            
            .menu-card h3 {
                font-size: 1.7rem;
            }
        }
        
        @media (max-width: 480px) {
            .header-section h1 {
                font-size: 2rem;
            }
            
            .header-section p {
                font-size: 1rem;
            }
            
            .menu-card {
                padding: 35px 25px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header Section -->
        <div class="header-section">
            <div class="logo-area">
                <i class="fas fa-tools"></i>
            </div>
            <h1>ระบบแจ้งซ่อม</h1>
            <p>Maintenance Request System</p>
        </div>
        
        <!-- Menu Grid -->
        <div class="menu-grid">
            <!-- แจ้งซ่อม -->
            <a href="pages/repair_form.php" class="menu-card repair" style="animation-delay: 0.1s">
                <div class="icon-wrapper">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3>แจ้งซ่อม</h3>
                <p>ฟอร์มแจ้งซ่อมและดูรายการแจ้งซ่อม<br>รายงานปัญหาเครื่องจักรและอุปกรณ์</p>
            </a>            
            
            <!-- อนุมัติใบแจ้งซ่อม -->
            <a href="pages/approval.php" class="menu-card approval" style="animation-delay: 0.3s">
                <div class="icon-wrapper">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <h3>อนุมัติใบแจ้งซ่อม</h3>
                <p>สำหรับหัวหน้าแผนก<br>พิจารณาอนุมัติ/ปฏิเสธใบแจ้งซ่อม</p>
            </a>
            
            <!-- Monitor -->
            <a href="pages/monitor.php" class="menu-card monitor" style="animation-delay: 0.4s">
                <div class="icon-wrapper">
                    <i class="fas fa-tv"></i>
                </div>
                <h3>Monitor</h3>
                <p>หน้าจอแสดงสถานะการซ่อมแบบเต็มจอ<br>ติดตามงานแบบ Real-time Display</p>
            </a>
            
            <!-- จัดการเครื่องจักร -->
            <a href="auth/login.php" class="menu-card machines" style="animation-delay: 0.2s">
                <div class="icon-wrapper">
                    <i class="fas fa-cogs"></i>
                </div>
                <h3>สำหรับเจ้าหน้าที่ซ่อมบำรุง</h3>
                <p>ข้อมูลใบแจ้งซ่อมทั้งหมด<br>ทะเบียนเครื่องจักรและอุปกรณ์ทั้งหมด</p>
            </a>

            <!-- KPI Dashboard -->
            <a href="pages/kpi.php" class="menu-card kpi" style="animation-delay: 0.5s">
                <div class="icon-wrapper">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>KPI Dashboard</h3>
                <p>สรุปสถิติและรายงานผลการทำงาน<br>วิเคราะห์ประสิทธิภาพการซ่อมบำรุง</p>
            </a>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        // Add smooth scroll behavior
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Add keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                // Close any alerts or modals
            }
        });
    </script>
</body>
</html>
