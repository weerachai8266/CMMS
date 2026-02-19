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
    
    <!-- jsPDF for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <!-- SheetJS for Excel export -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
    
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
            height: 300px;
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
        
        /* Print styles */
        @media print {
            .btn, .filter-section, .dashboard-header .btn-back {
                display: none !important;
            }
            
            body {
                background: white;
            }
            
            .chart-container, .table-container, .kpi-card {
                page-break-inside: avoid;
                box-shadow: none !important;
                border: 1px solid #ddd;
            }
        }
        
        /* Alert indicator */
        .alert-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 12px;
            height: 12px;
            background: #dc3545;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.1); }
        }
        
        .trend-badge {
            font-size: 0.8rem;
            margin-left: 5px;
        }
        
        .trend-up { color: #28a745; }
        .trend-down { color: #dc3545; }
        
        /* Table hover effect with cursor pointer */
        #frequentMachinesTable tbody tr,
        #mtbfTable tbody tr {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        #frequentMachinesTable tbody tr:hover,
        #mtbfTable tbody tr:hover {
            background-color: #e3f2fd !important;
            transform: scale(1.01);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        /* Modal styling */
        .modal-xl {
            max-width: 90%;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .modal-header .close {
            color: white;
            opacity: 1;
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
            
            .dashboard-header .d-flex {
                flex-direction: column;
                gap: 10px;
            }
            
            .dashboard-header .btn {
                width: 100%;
                margin-bottom: 5px;
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

    <!-- Threshold Settings Modal -->
    <div class="modal fade" id="thresholdSettingsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-cog"></i> ตั้งค่าการแจ้งเตือน (Alert Thresholds)</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> ระบบจะแจ้งเตือนอัตโนมัติเมื่อค่า KPI ต่ำกว่าหรือสูงกว่าเกณฑ์ที่กำหนด
                    </div>
                    
                    <form id="thresholdSettingsForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-danger text-white">
                                        <strong><i class="fas fa-exclamation-triangle"></i> การแจ้งเตือนวิกฤต (Critical)</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="mtbfThreshold">
                                                <i class="fas fa-chart-line"></i> MTBF (Mean Time Between Failure)
                                                <small class="text-muted">- วัน</small>
                                            </label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="mtbfThreshold" 
                                                       placeholder="7" min="1" max="365" step="1">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">วัน</span>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">แจ้งเตือนเมื่อ MTBF ต่ำกว่า (ค่าเริ่มต้น: 7 วัน)</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="mttrThreshold">
                                                <i class="fas fa-stopwatch"></i> MTTR (Mean Time To Repair)
                                                <small class="text-muted">- ชั่วโมง</small>
                                            </label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="mttrThreshold" 
                                                       placeholder="24" min="1" max="720" step="1">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">ชั่วโมง</span>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">แจ้งเตือนเมื่อ MTTR สูงกว่า (ค่าเริ่มต้น: 24 ชั่วโมง)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-warning text-dark">
                                        <strong><i class="fas fa-exclamation-circle"></i> การแจ้งเตือนทั่วไป (Warning)</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="successRateThreshold">
                                                <i class="fas fa-percentage"></i> อัตราความสำเร็จ (Success Rate)
                                                <small class="text-muted">- %</small>
                                            </label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="successRateThreshold" 
                                                       placeholder="70" min="0" max="100" step="1">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">แจ้งเตือนเมื่อต่ำกว่า (ค่าเริ่มต้น: 70%)</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="pendingThreshold">
                                                <i class="fas fa-clock"></i> รอการอนุมัติ (Pending Repairs)
                                                <small class="text-muted">- รายการ</small>
                                            </label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="pendingThreshold" 
                                                       placeholder="10" min="1" max="1000" step="1">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">รายการ</span>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">แจ้งเตือนเมื่อมากกว่า (ค่าเริ่มต้น: 10 รายการ)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <strong><i class="fas fa-chart-bar"></i> เกณฑ์เพิ่มเติม</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="oeeThreshold">
                                                        <i class="fas fa-tachometer-alt"></i> OEE
                                                        <small class="text-muted">- %</small>
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="number" class="form-control" id="oeeThreshold" 
                                                               placeholder="60" min="0" max="100" step="1">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text">%</span>
                                                        </div>
                                                    </div>
                                                    <small class="form-text text-muted">แจ้งเตือนเมื่อต่ำกว่า (ค่าเริ่มต้น: 60%)</small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="responseTimeThreshold">
                                                        <i class="fas fa-bolt"></i> Response Time
                                                        <small class="text-muted">- นาที</small>
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="number" class="form-control" id="responseTimeThreshold" 
                                                               placeholder="60" min="1" max="1440" step="1">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text">นาที</span>
                                                        </div>
                                                    </div>
                                                    <small class="form-text text-muted">แจ้งเตือนเมื่อสูงกว่า (ค่าเริ่มต้น: 60 นาที)</small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="downtimeThreshold">
                                                        <i class="fas fa-exclamation-circle"></i> Downtime
                                                        <small class="text-muted">- ชั่วโมง</small>
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="number" class="form-control" id="downtimeThreshold" 
                                                               placeholder="100" min="1" max="1000" step="1">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text">ชม.</span>
                                                        </div>
                                                    </div>
                                                    <small class="form-text text-muted">แจ้งเตือนเมื่อสูงกว่า (ค่าเริ่มต้น: 100 ชม.)</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> ยกเลิก
                    </button>
                    <button type="button" class="btn btn-warning" onclick="resetThresholds()">
                        <i class="fas fa-undo"></i> รีเซ็ตค่าเริ่มต้น
                    </button>
                    <button type="button" class="btn btn-success" onclick="saveThresholds()">
                        <i class="fas fa-save"></i> บันทึก
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1><i class="fas fa-chart-line"></i> KPI Dashboard</h1>
                    <p>สรุปสถิติและรายงานผลการทำงาน</p>
                </div>
                <div class="d-flex" style="gap: 15px;">
                    <!-- Column 1 -->
                    <div class="d-flex flex-column" style="gap: 10px;">
                        <a href="../index.php" class="btn btn-back">
                            <i class="fas fa-home"></i> กลับหน้าหลัก
                        </a>
                        <button class="btn btn-back" onclick="showThresholdSettings()">
                            <i class="fas fa-cog"></i> ตั้งค่าการแจ้งเตือน
                        </button>
                    </div>
                    <!-- Column 2 -->
                    <div class="d-flex flex-column" style="gap: 10px;">
                        <button class="btn btn-back" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <button class="btn btn-back" onclick="exportToPDF()">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                        <!-- <button class="btn btn-back" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button> -->
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Key Metrics Section -->
        <!-- <div class="row mb-4">
            <div class="col-md-3">
                <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-clipboard-list" style="font-size: 3rem; opacity: 0.9; margin-bottom: 15px;"></i>
                        <h6 style="color: rgba(255,255,255,0.9); font-weight: 600; margin-bottom: 10px;">ใบแจ้งซ่อมทั้งหมด</h6>
                        <h2 style="font-size: 3.5rem; font-weight: 800; margin-bottom: 5px;" id="keyTotalRepairs">0</h2>
                        <p style="color: rgba(255,255,255,0.8); margin-bottom: 0; font-size: 0.9rem;">รายการ</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-check-circle" style="font-size: 3rem; opacity: 0.9; margin-bottom: 15px;"></i>
                        <h6 style="color: rgba(255,255,255,0.9); font-weight: 600; margin-bottom: 10px;">อัตราความสำเร็จ</h6>
                        <h2 style="font-size: 3.5rem; font-weight: 800; margin-bottom: 5px;" id="keySuccessRate">0</h2>
                        <p style="color: rgba(255,255,255,0.8); margin-bottom: 0; font-size: 0.9rem;">%</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%); color: white; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-chart-line" style="font-size: 3rem; opacity: 0.9; margin-bottom: 15px;"></i>
                        <h6 style="color: rgba(255,255,255,0.9); font-weight: 600; margin-bottom: 10px;">MTBF</h6>
                        <h2 style="font-size: 3.5rem; font-weight: 800; margin-bottom: 5px;" id="keyMtbfDays">0</h2>
                        <p style="color: rgba(255,255,255,0.8); margin-bottom: 0; font-size: 0.9rem;">วัน</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-dollar-sign" style="font-size: 3rem; opacity: 0.9; margin-bottom: 15px;"></i>
                        <h6 style="color: rgba(255,255,255,0.9); font-weight: 600; margin-bottom: 10px;">ค่าใช้จ่ายรวม</h6>
                        <h2 style="font-size: 2.8rem; font-weight: 800; margin-bottom: 5px;" id="keyTotalCost">0</h2>
                        <p style="color: rgba(255,255,255,0.8); margin-bottom: 0; font-size: 0.9rem;">บาท</p>
                    </div>
                </div>
            </div>
        </div> -->

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row align-items-end">
                <div class="col-md-2">
                    <label for="dateFrom">จากวันที่</label>
                    <input type="date" class="form-control" id="dateFrom">
                </div>
                <div class="col-md-2">
                    <label for="dateTo">ถึงวันที่</label>
                    <input type="date" class="form-control" id="dateTo">
                </div>
                <div class="col-md-2">
                    <label for="filterDepartment">แผนก</label>
                    <select class="form-control" id="filterDepartment">
                        <option value="">ทั้งหมด</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filterBranch">สาขา</label>
                    <select class="form-control" id="filterBranch">
                        <option value="">ทั้งหมด</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filterStatus">สถานะ</label>
                    <select class="form-control" id="filterStatus">
                        <option value="">ทั้งหมด</option>
                        <option value="10">รออนุมัติ</option>
                        <option value="11">ไม่อนุมัติ</option>
                        <option value="20">กำลังซ่อม</option>
                        <option value="30">รออะไหล่</option>
                        <option value="40">เสร็จสิ้น</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-refresh btn-block" onclick="loadKPIData()">
                        <i class="fas fa-sync-alt"></i> โหลดข้อมูล
                    </button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary btn-block btn-sm" onclick="setDateRange('today')">วันนี้</button>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary btn-block btn-sm" onclick="setDateRange('week')">สัปดาห์นี้</button>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary btn-block btn-sm" onclick="setDateRange('month')">เดือนนี้</button>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary btn-block btn-sm" onclick="setDateRange('lastMonth')">เดือนที่แล้ว</button>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary btn-block btn-sm" onclick="setDateRange('year')">ปีนี้</button>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-warning btn-block btn-sm" onclick="clearFilters()"><i class="fas fa-eraser"></i> ล้างตัวกรอง</button>
                </div>
            </div>
        </div>
        
        <!-- KPI Cards Row 1 -->
        <!-- <div class="kpi-grid"> -->
        <div class="row mb-4"> 
            <div class="col-md-2">
                <div class="kpi-card primary">
                    <i class="fas fa-clipboard-list kpi-icon"></i>
                    <div class="kpi-label">ใบแจ้งซ่อมทั้งหมด</div>
                    <div class="kpi-value" id="totalRepairs">0</div>
                    <div class="kpi-detail">รายการทั้งหมด</div>
                </div>
            </div>
                
            <div class="col-md-2">
                <div class="kpi-card warning">
                    <i class="fas fa-clock kpi-icon"></i>
                    <div class="kpi-label">รออนุมัติ</div>
                    <div class="kpi-value" id="pendingRepairs">0</div>
                    <div class="kpi-detail">รายการ</div>
                </div>
            </div>
                
            <div class="col-md-2">
                <div class="kpi-card info">
                    <i class="fas fa-tools kpi-icon"></i>
                    <div class="kpi-label">กำลังซ่อม</div>
                    <div class="kpi-value" id="inProgressRepairs">0</div>
                    <div class="kpi-detail">รายการ</div>
                </div>
            </div>
                
            <div class="col-md-2">
                <div class="kpi-card orange">
                    <i class="fas fa-box-open kpi-icon"></i>
                    <div class="kpi-label">รออะไหล่</div>
                    <div class="kpi-value" id="waitingPartsRepairs">0</div>
                    <div class="kpi-detail">รายการ</div>
                </div>
            </div>
                
            <div class="col-md-2">
                <div class="kpi-card success">
                    <i class="fas fa-check-circle kpi-icon"></i>
                    <div class="kpi-label">เสร็จสิ้น</div>
                    <div class="kpi-value" id="completedRepairs">0</div>
                    <div class="kpi-detail">รายการ</div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="kpi-card danger">
                    <i class="fas fa-dollar-sign kpi-icon"></i>
                    <div class="kpi-label">ค่าใช้จ่ายรวม</div>
                    <div class="kpi-value" id="totalCost">0</div>
                    <div class="kpi-detail">บาท</div>
                </div>
            </div>
        </div>
        
        <!-- KPI Cards Row 2 - Summary Stats -->
        <div class="row mb-4">
            <!-- OEE card: commented out pending formula discussion
            <div class="col-md-2">
                <div class="kpi-card warning">
                    <i class="fas fa-chart-pie kpi-icon"></i>
                    <div class="kpi-label">OEE</div>
                    <div class="kpi-value" id="oeePercent">0</div>
                    <div class="kpi-detail">%</div>
                </div>
            </div>
            -->

            <div class="col-md-2">
                <div class="kpi-card purple">
                    <i class="fas fa-clock kpi-icon"></i>
                    <div class="kpi-label">เวลาทำงานรวม</div>
                    <div class="kpi-value" id="totalWorkHours">0</div>
                    <div class="kpi-detail">ชั่วโมง</div>
                </div>
            </div>
            
            <div class="col-md-2">
                <div class="kpi-card orange">
                    <i class="fas fa-exclamation-circle kpi-icon"></i>
                    <div class="kpi-label">เวลาหยุดเครื่องรวม</div>
                    <div class="kpi-value" id="totalDowntimeHours">0</div>
                    <div class="kpi-detail">ชั่วโมง</div>
                </div>
            </div>
            
            <div class="col-md-2">
                <div class="kpi-card info">
                    <i class="fas fa-percentage kpi-icon"></i>
                    <div class="kpi-label">อัตราความสำเร็จ</div>
                    <div class="kpi-value" id="successRate">0</div>
                    <div class="kpi-detail">%</div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="kpi-card primary">
                    <i class="fas fa-stopwatch kpi-icon"></i>
                    <div class="kpi-label">MTTR</div>
                    <div class="kpi-value" id="mttrHours">0</div>
                    <div class="kpi-detail">ชั่วโมง</div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="kpi-card success">
                    <i class="fas fa-bolt kpi-icon"></i>
                    <div class="kpi-label">เวลาตอบสนอง</div>
                    <div class="kpi-value" id="responseTime">0</div>
                    <div class="kpi-detail">นาที</div>
                </div>
            </div>
        </div>
        
        <!-- KPI Cards Row 3 - Advanced Metrics -->
        <!-- <div class="row mb-4">   
            <div class="col-md-2">
                <div class="kpi-card purple">
                    <i class="fas fa-check-double kpi-icon"></i>
                    <div class="kpi-label">First Time Fix Rate</div>
                    <div class="kpi-value" id="firstTimeFixRate">0</div>
                    <div class="kpi-detail">% (ซ่อมสำเร็จครั้งแรก)</div>
                </div>
            </div>
        </div> -->
        
        <!-- Charts Row 1 -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-container">
                    <h3><i class="fas fa-chart-pie" style="color: #007bff;"></i> สถิติตามสถานะ</h3>
                    <div class="chart-wrapper">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h3><i class="fas fa-chart-bar" style="color: #28a745;"></i> สถิติตามแผนก</h3>
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
                    <h3><i class="fas fa-chart-pie" style="color: #17a2b8;"></i> เปอร์เซ็นต์สถานะงานซ่อม</h3>
                    <div class="chart-wrapper">
                        <canvas id="statusPercentChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h3><i class="fas fa-chart-line" style="color: #6f42c1;"></i> แนวโน้มการแจ้งซ่อมรายวัน (30 วันล่าสุด)</h3>
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
                    <h3><i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i> เครื่องจักรที่มีปัญหาบ่อย (Top 10)</h3>
                    <!-- <p class="text-muted small"><i class="fas fa-info-circle"></i> คลิกที่แถวเพื่อดูรายละเอียดเพิ่มเติม</p> -->
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
                    <h3><i class="fas fa-user-tie" style="color: #007bff;"></i> ช่างที่ทำงานมากที่สุด (Top 10)</h3>
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
                    <h3><i class="fas fa-money-bill-wave" style="color: #dc3545;"></i> เครื่องจักรที่มีค่าใช้จ่ายสูงสุด (Top 10)</h3>
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
                    <h3><i class="fas fa-building" style="color: #28a745;"></i> สถิติตามสาขา</h3>
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
        
        <!-- Advanced Analytics Section -->
        <!-- MTBF Card -->
        <!-- <div class="row">
            <div class="col-md-12">
                <div class="chart-container" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h3 style="color: white;"><i class="fas fa-calculator"></i> MTBF - Mean Time Between Failure (ค่าเฉลี่ยเวลาระหว่างความเสียหาย)</h3>
                    <div class="row">
                        <div class="col-md-3 text-center py-3">
                            <h5 style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">จำนวนครั้งที่เสียทั้งหมด</h5>
                            <h2 style="font-size: 3rem; font-weight: 800;" id="totalFailures">0</h2>
                            <p style="color: rgba(255,255,255,0.8);">ครั้ง</p>
                        </div>
                        <div class="col-md-3 text-center py-3">
                            <h5 style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">ช่วงเวลาทั้งหมด</h5>
                            <h2 style="font-size: 3rem; font-weight: 800;" id="totalPeriodDays">0</h2>
                            <p style="color: rgba(255,255,255,0.8);">วัน</p>
                        </div>
                        <div class="col-md-3 text-center py-3">
                            <h5 style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">MTBF (ชั่วโมง)</h5>
                            <h2 style="font-size: 3rem; font-weight: 800;" id="mtbfHours">0</h2>
                            <p style="color: rgba(255,255,255,0.8);">ชั่วโมง</p>
                        </div>
                        <div class="col-md-3 text-center py-3">
                            <h5 style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">MTBF (วัน)</h5>
                            <h2 style="font-size: 3rem; font-weight: 800; color: #ffd700;" id="mtbfDays">0</h2>
                            <p style="color: rgba(255,255,255,0.8);">วัน</p>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->
        
        <!-- Monthly Performance & Pareto Chart Row -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-container">
                    <h3><i class="fas fa-chart-area" style="color: #007bff;"></i> ประสิทธิภาพรายเดือน (12 เดือนล่าสุด)</h3>
                    <div class="chart-wrapper">
                        <canvas id="monthlyPerformanceChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h3><i class="fas fa-chart-bar" style="color: #dc3545;"></i> Pareto Chart - สาเหตุหลักของการเสีย</h3>
                    <div class="chart-wrapper">
                        <canvas id="paretoChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- MTBF by Machine Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="table-container">
                    <h3><i class="fas fa-wrench" style="color: #6f42c1;"></i> MTBF ตามเครื่องจักร (Top 20)</h3>
                    <p class="text-muted small"><i class="fas fa-info-circle"></i> คลิกที่แถวเพื่อดูประวัติการซ่อม | สีแดง: < 7 วัน, สีเหลือง: < 30 วัน, สีเขียว: >= 30 วัน</p>
                    <div class="table-responsive">
                        <table class="table table-hover" id="mtbfTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>รหัสเครื่องจักร</th>
                                    <th class="text-center">จำนวนครั้งที่เสีย</th>
                                    <th class="text-center">MTBF (ชั่วโมง)</th>
                                    <th class="text-center">MTBF (วัน)</th>
                                    <th class="text-center">ความเสียหายล่าสุด</th>
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
