<?php
require_once '../config/config.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI Dashboard - Maintenance System</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Sarabun', sans-serif;
        }
        
        body {
            background: #f5f7fa;
            padding: 20px;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .dashboard-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
        }
        
        .dashboard-header p {
            font-size: 1.1rem;
            opacity: 0.95;
        }
        
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .filter-section label {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .kpi-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: currentColor;
        }
        
        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .kpi-card.primary { color: #007bff; }
        .kpi-card.success { color: #28a745; }
        .kpi-card.warning { color: #ffc107; }
        .kpi-card.danger { color: #dc3545; }
        .kpi-card.info { color: #17a2b8; }
        .kpi-card.purple { color: #6f42c1; }
        .kpi-card.orange { color: #ff9800; }
        
        .kpi-icon {
            font-size: 3rem;
            opacity: 0.2;
            position: absolute;
            right: 20px;
            top: 20px;
        }
        
        .kpi-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .kpi-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: currentColor;
            margin-bottom: 5px;
        }
        
        .kpi-detail {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        
        .chart-container h3 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .chart-wrapper {
            position: relative;
            height: 350px;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        
        .table-container h3 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            border-top: none;
            font-weight: 700;
            background: #f8f9fa;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .loading-spinner {
            text-align: center;
        }
        
        .loading-spinner i {
            font-size: 4rem;
            color: #667eea;
            animation: spin 1s linear infinite;
        }
        
        .loading-spinner p {
            font-size: 1.2rem;
            color: #6c757d;
            margin-top: 15px;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .btn-back {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-refresh {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-refresh:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
            color: white;
        }
        
        @media (max-width: 768px) {
            .dashboard-header h1 {
                font-size: 1.8rem;
            }
            
            .kpi-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-wrapper {
                height: 250px;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <i class="fas fa-circle-notch"></i>
            <p>กำลังโหลดข้อมูล...</p>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-chart-line"></i> KPI Dashboard</h1>
                    <p>สรุปสถิติและรายงานผลการทำงาน</p>
                </div>
                <div>
                    <a href="../index.php" class="btn btn-back">
                        <i class="fas fa-home"></i> กลับหน้าหลัก
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label for="dateFrom">จากวันที่</label>
                    <input type="date" class="form-control" id="dateFrom">
                </div>
                <div class="col-md-3">
                    <label for="dateTo">ถึงวันที่</label>
                    <input type="date" class="form-control" id="dateTo">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-refresh btn-block" onclick="loadKPIData()">
                        <i class="fas fa-sync-alt"></i> โหลดข้อมูล
                    </button>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-secondary btn-block" onclick="setDateRange('today')">วันนี้</button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-3">
                    <button class="btn btn-outline-secondary btn-block btn-sm" onclick="setDateRange('week')">สัปดาห์นี้</button>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-secondary btn-block btn-sm" onclick="setDateRange('month')">เดือนนี้</button>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-secondary btn-block btn-sm" onclick="setDateRange('lastMonth')">เดือนที่แล้ว</button>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-secondary btn-block btn-sm" onclick="setDateRange('year')">ปีนี้</button>
                </div>
            </div>
        </div>
        
        <!-- KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card primary">
                <i class="fas fa-clipboard-list kpi-icon"></i>
                <div class="kpi-label">ใบแจ้งซ่อมทั้งหมด</div>
                <div class="kpi-value" id="totalRepairs">0</div>
                <div class="kpi-detail">รายการทั้งหมด</div>
            </div>
            
            <div class="kpi-card warning">
                <i class="fas fa-clock kpi-icon"></i>
                <div class="kpi-label">รออนุมัติ</div>
                <div class="kpi-value" id="pendingRepairs">0</div>
                <div class="kpi-detail">รายการ</div>
            </div>
            
            <div class="kpi-card info">
                <i class="fas fa-tools kpi-icon"></i>
                <div class="kpi-label">กำลังซ่อม</div>
                <div class="kpi-value" id="inProgressRepairs">0</div>
                <div class="kpi-detail">รายการ</div>
            </div>
            
            <div class="kpi-card orange">
                <i class="fas fa-box-open kpi-icon"></i>
                <div class="kpi-label">รออะไหล่</div>
                <div class="kpi-value" id="waitingPartsRepairs">0</div>
                <div class="kpi-detail">รายการ</div>
            </div>
            
            <div class="kpi-card success">
                <i class="fas fa-check-circle kpi-icon"></i>
                <div class="kpi-label">เสร็จสิ้น</div>
                <div class="kpi-value" id="completedRepairs">0</div>
                <div class="kpi-detail">รายการ</div>
            </div>
            
            <!-- <div class="kpi-card purple">
                <i class="fas fa-hourglass-half kpi-icon"></i>
                <div class="kpi-label">เวลาเฉลี่ยในการซ่อม</div>
                <div class="kpi-value" id="avgRepairTime">0</div>
                <div class="kpi-detail">ชั่วโมง</div>
            </div> -->
            
            <div class="kpi-card danger">
                <i class="fas fa-dollar-sign kpi-icon"></i>
                <div class="kpi-label">ค่าใช้จ่ายรวม</div>
                <div class="kpi-value" id="totalCost">0</div>
                <div class="kpi-detail">บาท</div>
            </div>
        </div>
        
        <!-- Charts Row 1 -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-container">
                    <h3><i class="fas fa-chart-pie"></i> สถิติตามสถานะ</h3>
                    <div class="chart-wrapper">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h3><i class="fas fa-chart-bar"></i> สถิติตามแผนก</h3>
                    <div class="chart-wrapper">
                        <canvas id="departmentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row 2 -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-container">
                    <h3><i class="fas fa-chart-pie"></i> เปอร์เซ็นต์สถานะงานซ่อม</h3>
                    <div class="chart-wrapper">
                        <canvas id="statusPercentChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h3><i class="fas fa-chart-line"></i> แนวโน้มการแจ้งซ่อมรายวัน (30 วันล่าสุด)</h3>
                    <div class="chart-wrapper">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tables Row -->
        <div class="row">
            <div class="col-md-6">
                <div class="table-container">
                    <h3><i class="fas fa-exclamation-triangle"></i> เครื่องจักรที่มีปัญหาบ่อย (Top 10)</h3>
                    <div class="table-responsive">
                        <table class="table table-hover" id="frequentMachinesTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>รหัสเครื่องจักร</th>
                                    <th class="text-center">จำนวนครั้ง</th>
                                    <th class="text-center">เสร็จสิ้น</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="table-container">
                    <h3><i class="fas fa-user-tie"></i> ช่างที่ทำงานมากที่สุด (Top 10)</h3>
                    <div class="table-responsive">
                        <table class="table table-hover" id="technicianTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ชื่อช่าง</th>
                                    <th class="text-center">จำนวนงาน</th>
                                    <th class="text-center">ชั่วโมงรวม</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Expensive Machines & Branch Stats Row -->
        <div class="row">
            <div class="col-md-6">
                <div class="table-container">
                    <h3><i class="fas fa-money-bill-wave"></i> เครื่องจักรที่มีค่าใช้จ่ายสูงสุด (Top 10)</h3>
                    <div class="table-responsive">
                        <table class="table table-hover" id="expensiveMachinesTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>รหัสเครื่องจักร</th>
                                    <th>ชื่อเครื่องจักร</th>
                                    <th class="text-center">จำนวนครั้ง</th>
                                    <th class="text-right">ค่าใช้จ่ายรวม (บาท)</th>
                                    <th class="text-right">ค่าเฉลี่ย (บาท)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="table-container">
                    <h3><i class="fas fa-building"></i> สถิติตามสาขา</h3>
                    <div class="table-responsive">
                        <table class="table table-hover" id="branchTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>สาขา</th>
                                    <th class="text-center">จำนวนการแจ้งซ่อม</th>
                                    <th class="text-center">เสร็จสิ้น</th>
                                    <th class="text-center">% สำเร็จ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../assets/js/kpi.js"></script>
</body>
</html>
