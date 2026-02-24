<?php
session_start();

// ตรวจสอบการ login
if (!isset($_SESSION['technician_logged_in']) || $_SESSION['technician_logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/db.php';
require_once '../config/config.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Import JS dependencies -->
    <title>จัดการระบบ MT - ระบบแจ้งซ่อม</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .tab-content {
            padding: 20px 0;
        }
        .nav-tabs .nav-link {
            color: #495057;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            color: #007bff;
            font-weight: 600;
        }
        .filter-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php"><i class="fas fa-tools"></i> ระบบแจ้งซ่อม</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php"><i class="fas fa-home"></i> หน้าแรก</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="repair_form.php"><i class="fas fa-clipboard-list"></i> แจ้งซ่อม</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="approval.php"><i class="fas fa-clipboard-check"></i> อนุมัติใบแจ้งซ่อม</a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="machines.php"><i class="fas fa-user-cog"></i> เจ้าหน้าที่ MT</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="monitor.php"><i class="fas fa-tv"></i> Monitor</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kpi.php"><i class="fas fa-chart-line"></i> KPI</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link text-light">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['technician_username']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row mb-3">
            <div class="col-md-12">
                <h2><i class="fas fa-user-cog"></i> จัดการระบบ MT (Maintenance)</h2>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs" id="mtTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="repairs-tab" data-toggle="tab" href="#repairs" role="tab">
                    <i class="fas fa-clipboard-list"></i> ลงประวัติเครื่องจักร
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="machines-tab" data-toggle="tab" href="#machines" role="tab">
                    <i class="fas fa-cogs"></i> ทะเบียนเครื่องจักร
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="reserve2-tab" data-toggle="tab" href="#reserve2" role="tab">
                    <i class="fas fa-history"></i> ประวัติเครื่องจักร
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="reserve1-tab" data-toggle="tab" href="#reserve1" role="tab">
                    <i class="fas fa-database"></i> จัดการข้อมูลพื้นฐาน
                </a>
            </li>
            <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'manager' || $_SESSION['user_role'] === 'staff'): ?>
            <li class="nav-item">
                <a class="nav-link" id="users-tab" data-toggle="tab" href="#users" role="tab">
                    <i class="fas fa-users"></i> จัดการผู้ใช้งานระบบ
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="mtTabsContent">
            <!-- Tab 1: จัดการใบแจ้งซ่อม -->
            <div class="tab-pane fade show active" id="repairs" role="tabpanel">
                <h4><i class="fas fa-clipboard-list"></i> รายการใบแจ้งซ่อมทั้งหมด</h4>
                
                <!-- Filter Box -->
                <div class="filter-box">
                    <div class="row">
                        <div class="col-md-3">
                            <label>วันที่แจ้งซ่อม:</label>
                            <input type="date" class="form-control" id="filter_repair_date">
                        </div>
                        <div class="col-md-2">
                            <label>เลขที่:</label>
                            <input type="text" class="form-control" id="filter_document_no" placeholder="ค้นหาเลขที่...">
                        </div>
                        <div class="col-md-2">
                            <label>สถานะ:</label>
                            <select class="form-control" id="filter_status">
                                <option value="">ทั้งหมด</option>
                                <option value="10">รออนุมัติ</option>
                                <option value="11">ไม่อนุมัติ</option>
                                <option value="20">รอดำเนินการ</option>
                                <option value="30">รออะไหล่</option>
                                <option value="40">ซ่อมเสร็จสิ้น</option>
                                <option value="50">ยกเลิก</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>ผู้ลงประวัติ:</label>
                            <select class="form-control" id="filter_registry_signer">
                                <option value="">ทั้งหมด</option>
                                <option value="not_empty">มีผู้ลงประวัติ</option>
                                <option value="empty">ยังไม่มีผู้ลงประวัติ</option>
                            </select>
                        </div>
                    
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <button class="btn btn-primary btn-block" onclick="filterRepairs()">
                                <i class="fas fa-search"></i> ค้นหา
                            </button>                            
                            <!-- <button class="btn btn-secondary btn-block" onclick="clearFilters()">
                                <i class="fas fa-redo"></i> ล้างตัวกรอง
                            </button> -->
                        </div>
                    </div>
                </div>

                <!-- Repairs Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>เลขที่</th>
                                <th>วันที่แจ้ง</th>
                                <th>เครื่องจักร</th>
                                <th>ผู้แจ้ง</th>
                                <th>ปัญหา</th>
                                <th>สถานะ</th>
                                <th>ผู้รับผิดชอบ</th>
                                <th>ผู้ลงประวัติ</th>
                                <th style="width: 120px;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="repairsTableBody">
                            <tr>
                                <td colspan="9" class="text-center">
                                    <i class="fas fa-spinner fa-spin"></i> กำลังโหลดข้อมูล...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 2: จัดการเครื่องจักร -->
            <div class="tab-pane fade" id="machines" role="tabpanel">
                <h4><i class="fas fa-cogs"></i> จัดการข้อมูลเครื่องจักร</h4>
                
                <!-- ปุ่มเพิ่มเครื่องจักร -->
                <div class="mb-3 d-flex justify-content-between">
                    <div>
                        <button class="btn btn-success" data-toggle="modal" data-target="#machineModal">
                            <i class="fas fa-plus"></i> เพิ่มเครื่องจักรใหม่
                        </button>
                        <button class="btn btn-info" id="btn_toggle_select" onclick="toggleSelectMode()">
                            <i class="fas fa-check-square"></i> เลือกหลายเครื่อง
                        </button>
                    </div>
                    <div>
                        <button class="btn btn-warning" id="btn_export_selected" onclick="exportSelectedMachines()" style="display: none;">
                            <i class="fas fa-file-excel"></i> Export ประวัติเครื่องจักรที่เลือก (<span id="selected_count">0</span>)
                        </button>
                        <button class="btn btn-primary" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Export to Excel
                        </button>
                    </div>
                </div>

                <!-- ตารางเครื่องจักร -->
                <div class="card">
                    <!-- รายการเครื่องจักร -->
                    <div class="card-header bg-info text-white">
                        <h5><i class="fas fa-list"></i> รายการเครื่องจักร</h5>
                    </div>
                    <div class="card-body">
                        <!-- ตัวกรอง -->
                        <div class="form-row">
                            <div class="col-md-2" style="display: none;">
                                <label for="filter_type">ประเภทเครื่องจักร</label>
                                <select class="form-control" id="filter_type">
                                    <option value="">ทั้งหมด</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="filter_branch">สาขา</label>
                                <select class="form-control" id="filter_branch">
                                    <option value="">ทั้งหมด</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="filter_code">รหัสเครื่องจักร</label>
                                <input type="text" class="form-control" id="filter_code" placeholder="ค้นหารหัส...">
                            </div>
                            <div class="col-md-3">
                                <label for="filter_brand">ยี่ห้อ</label>
                                <input type="text" class="form-control" id="filter_brand" placeholder="ค้นหายี่ห้อ...">
                            </div>
                            <div class="col-md-3">
                                <label for="filter_model">รุ่น</label>
                                <input type="text" class="form-control" id="filter_model" placeholder="ค้นหารุ่น...">
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-secondary btn-block" id="btn_clear_filter">
                                    <i class="fas fa-redo"></i> ล้างตัวกรอง
                                </button>
                            </div>
                        </div>
                        <br>
                        <!-- รายการเครื่องจักร -->
                        <div id="machineList">
                            <div class="text-center">
                                <i class="fas fa-spinner fa-spin fa-2x"></i>
                                <p>กำลังโหลดข้อมูล...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: จัดการข้อมูลพื้นฐาน -->
            <div class="tab-pane fade" id="reserve1" role="tabpanel">
                <h4><i class="fas fa-database"></i> จัดการข้อมูลพื้นฐาน (Master Data)</h4>
                
                <!-- Sub Navigation -->
                <ul class="nav nav-pills mb-3" id="masterTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="company-tab" data-toggle="pill" href="#company" role="tab">
                            <i class="fas fa-building"></i> บริษัท
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="branch-tab" data-toggle="pill" href="#branch" role="tab">
                            <i class="fas fa-map-marker-alt"></i> สาขา
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="division-tab" data-toggle="pill" href="#division" role="tab">
                            <i class="fas fa-sitemap"></i> ฝ่าย
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="department-tab" data-toggle="pill" href="#department" role="tab">
                            <i class="fas fa-users"></i> หน่วยงาน
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="issue-tab" data-toggle="pill" href="#issue" role="tab">
                            <i class="fas fa-exclamation-triangle"></i> อาการเสีย
                        </a>
                    </li>
                </ul>

                <div class="tab-content" id="masterTabsContent">
                    <!-- บริษัท -->
                    <div class="tab-pane fade show active" id="company" role="tabpanel">
                        <div class="mb-3">
                            <button class="btn btn-success" onclick="openMasterModal('company')">
                                <i class="fas fa-plus"></i> เพิ่มบริษัท
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th width="80">ID</th>
                                        <th>ชื่อบริษัท</th>
                                        <th width="120">สร้างโดย</th>
                                        <th width="120">แก้ไขโดย</th>
                                        <th width="100">สถานะ</th>
                                        <th width="150">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody id="companyTableBody">
                                    <tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> กำลังโหลด...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- สาขา -->
                    <div class="tab-pane fade" id="branch" role="tabpanel">
                        <div class="mb-3">
                            <button class="btn btn-success" onclick="openMasterModal('branch')">
                                <i class="fas fa-plus"></i> เพิ่มสาขา
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th width="80">ID</th>
                                        <th>ชื่อสาขา</th>
                                        <th width="120">สร้างโดย</th>
                                        <th width="120">แก้ไขโดย</th>
                                        <th width="100">สถานะ</th>
                                        <th width="150">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody id="branchTableBody">
                                    <tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> กำลังโหลด...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ฝ่าย -->
                    <div class="tab-pane fade" id="division" role="tabpanel">
                        <div class="mb-3">
                            <button class="btn btn-success" onclick="openMasterModal('division')">
                                <i class="fas fa-plus"></i> เพิ่มฝ่าย
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th width="80">ID</th>
                                        <th>ชื่อฝ่าย</th>
                                        <th width="120">สร้างโดย</th>
                                        <th width="120">แก้ไขโดย</th>
                                        <th width="100">สถานะ</th>
                                        <th width="150">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody id="divisionTableBody">
                                    <tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> กำลังโหลด...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- หน่วยงาน -->
                    <div class="tab-pane fade" id="department" role="tabpanel">
                        <div class="mb-3">
                            <button class="btn btn-success" onclick="openMasterModal('department')">
                                <i class="fas fa-plus"></i> เพิ่มหน่วยงาน
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th width="80">ID</th>
                                        <th>ชื่อหน่วยงาน</th>
                                        <th>กลุ่ม</th>
                                        <th width="120">สร้างโดย</th>
                                        <th width="120">แก้ไขโดย</th>
                                        <th width="100">สถานะ</th>
                                        <th width="150">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody id="departmentTableBody">
                                    <tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> กำลังโหลด...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- อาการเสีย -->
                    <div class="tab-pane fade" id="issue" role="tabpanel">
                        <div class="mb-3">
                            <button class="btn btn-success" onclick="openMasterModal('issue')">
                                <i class="fas fa-plus"></i> เพิ่มอาการเสีย
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th width="80">ID</th>
                                        <th>อาการเสีย</th>
                                        <th width="120">สร้างโดย</th>
                                        <th width="120">แก้ไขโดย</th>
                                        <th width="100">สถานะ</th>
                                        <th width="150">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody id="issueTableBody">
                                    <tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> กำลังโหลด...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>


                </div>
            </div>

            <!-- Tab 4: ประวัติเครื่องจักร -->
            <div class="tab-pane fade" id="reserve2" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0"><i class="fas fa-history"></i> ประวัติเครื่องจักร</h4>
                    <div>
                        <button class="btn btn-success" onclick="openQuickAddHistory()">
                            <i class="fas fa-plus-circle"></i> เพิ่มประวัติ PM/Calibration
                        </button>
                    </div>
                </div>

                <!-- คำแนะนำการใช้งาน -->
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h6 class="alert-heading"><i class="fas fa-info-circle"></i> วิธีการบันทึกประวัติ</h6>
                    <ul class="mb-0 pl-3">
                        <li><strong>การซ่อม (Repair):</strong> ใช้ฟอร์มแจ้งซ่อมปกติ → ระบบจะบันทึกประวัติอัตโนมัติเมื่อซ่อมเสร็จ</li>
                        <li><strong>PM / Calibration:</strong> กดปุ่ม <span class="badge badge-success">"เพิ่มประวัติ PM/Calibration"</span> เพื่อบันทึกตรงๆ</li>
                        <li><strong>ดูประวัติ:</strong> เลือกรหัสเครื่องจักรด้านล่าง → กด "แสดงประวัติ"</li>
                    </ul>
                </div>
                
                <!-- ค้นหาเครื่องจักร -->
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-search"></i> เลือกเครื่องจักร</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="col-md-8">
                                <label for="history_machine_select">รหัสเครื่องจักร <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="history_machine_select_input" list="history_machine_datalist" placeholder="พิมพ์หรือเลือกรหัสเครื่องจักร">
                                <datalist id="history_machine_datalist">
                                </datalist>
                            </div>
                            <div class="col-md-4">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-primary btn-block" id="btn_show_history">
                                    <i class="fas fa-search"></i> แสดงประวัติ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- แสดงข้อมูลเครื่องจักร -->
                <div class="card mb-3" id="machine_info_card" style="display: none;">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> ข้อมูลเครื่องจักร</h5>
                        <span id="machine_status_badge"></span>
                    </div>
                    <div class="card-body">
                        <div id="machine_current_info"></div>
                    </div>
                </div>

                <!-- ประวัติการซ่อม -->
                <div class="card" id="repair_history_card" style="display: none;">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-tools"></i> ประวัติการซ่อมและบำรุงรักษา</h5>
                        <div>
                            <button class="btn btn-primary btn-sm" id="btn_export_history" onclick="exportMachineHistory()">
                                <i class="fas fa-file-excel"></i> Export to Excel
                            </button>
                            <button class="btn btn-success btn-sm" id="btn_add_history" onclick="openAddHistoryModal($('#history_machine_select_input').val(), $('#history_machine_select_input').val())">
                                <i class="fas fa-plus"></i> เพิ่มประวัติ
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm">
                                <thead class="thead-dark">
                                    <tr>
                                        <th style="width: 40px;">ลำดับ</th>
                                        <th style="width: 100px;">วันที่</th>
                                        <th style="width: 120px;">เลขที่เอกสาร</th>
                                        <th style="width: 200px;">อาการเสีย/ปัญหา</th>
                                        <th style="width: 200px;">การแก้ไข</th>
                                        <th style="width: 150px;">อะไหล่</th>
                                        <th style="width: 100px;">ค่าใช้จ่าย</th>
                                        <th style="width: 100px;">เวลาปฏิบัติงาน (ชั่วโมง)</th>
                                        <th style="width: 100px;">เวลาหยุดเครื่อง (ชั่วโมง)</th>
                                        <!-- <th style="width: 120px;">เวลาเริ่มปฏิบัติงาน</th> -->
                                        <!-- <th style="width: 120px;">เวลาปฏิบัติงานเสร็จ</th> -->
                                        <th style="width: 100px;">ผู้รับผิดชอบ</th>
                                        <th style="width: 120px;">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody id="repair_history_body">
                                    <tr>
                                        <td colspan="13" class="text-center">
                                            ไม่มีข้อมูล
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 5: จัดการผู้ใช้งานระบบ -->
            <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'manager' || $_SESSION['user_role'] === 'staff'): ?>
            <div class="tab-pane fade" id="users" role="tabpanel">
                <h4><i class="fas fa-users"></i> จัดการผู้ใช้งานระบบ</h4>
                
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list"></i> รายการผู้ใช้</h5>
                        <button class="btn btn-light btn-sm" onclick="openUserModal()">
                            <i class="fas fa-plus"></i> เพิ่มผู้ใช้ใหม่
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th width="60">ID</th>
                                        <th>Username</th>
                                        <th>ชื่อ-นามสกุล</th>
                                        <th>Email</th>
                                        <th>เบอร์โทร</th>
                                        <th>สิทธิ์</th>
                                        <th>แผนก</th>
                                        <th>สาขา</th>
                                        <th>ตำแหน่ง</th>
                                        <th width="100">สถานะ</th>
                                        <th width="100">Login ล่าสุด</th>
                                        <th width="200">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    <tr>
                                        <td colspan="12" class="text-center">
                                            <i class="fas fa-spinner fa-spin"></i> กำลังโหลดข้อมูล...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal เพิ่ม/แก้ไขเครื่องจักร -->
    <div class="modal fade" id="machineModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-plus"></i> เพิ่มเครื่องจักรใหม่
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="machineForm">
                    <div class="modal-body">
                        <input type="hidden" id="machine_id" name="machine_id">
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>ประเภทเครื่องจักร <span class="text-danger">*</span></label>
                                    <select class="form-control text-primary" id="machine_type" name="machine_type" required>
                                        <option value="">-- เลือกประเภท --</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>สาขา <span class="text-danger">*</span></label>
                                    <select class="form-control text-primary" id="branch_select" name="branch" required>
                                        <option value="">-- เลือกสาขา --</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>หน่วยงานที่รับผิดชอบ</label>
                                    <input type="text" class="form-control text-primary" id="responsible_dept" name="responsible_dept">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>พื้นที่ใช้งาน</label>
                                    <input type="text" class="form-control text-primary" id="work_area" name="work_area">
                                </div>
                            </div>
                            
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>รหัสเครื่องจักร <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text-primary" id="machine_code" name="machine_code" required>
                                </div>
                            </div>                            
                            <div class="col-md-3">
                                <label>ชื่อเครื่องจักร <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-primary" id="machine_name" name="machine_name" required>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>หมายเลขเครื่อง</label>
                                    <input type="text" class="form-control text-primary" id="machine_number" name="machine_number" placeholder="Serial No.">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>ยี่ห้อ</label>
                                    <input type="text" class="form-control text-primary" id="brand" name="brand">
                                </div>
                            </div>                            
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>รุ่น</label>
                                    <input type="text" class="form-control text-primary" id="model" name="model">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>ขนาด</label>
                                    <input type="text" class="form-control text-primary" id="horsepower" name="horsepower" placeholder="ระบุหน่วย เช่น 5 HP, 10 kW">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>น้ำหนัก</label>
                                    <input type="text" class="form-control text-primary" id="weight" name="weight" placeholder="ระบุหน่วย เช่น 500 kg">
                                </div>
                            </div>
                            <!-- <div class="col-md-4">
                                <div class="form-group">
                                    <label>จำนวน</label>
                                    <input type="number" class="form-control text-primary" id="quantity" name="quantity" value="1" min="1">
                                </div>
                            </div> -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>หน่วย</label>
                                    <select class="form-control text-primary" id="unit" name="unit">
                                        <option value="เครื่อง">เครื่อง</option>
                                        <option value="คัน">คัน</option>
                                        <option value="ชุด">ชุด</option>
                                        <option value="ตัว">ตัว</option>
                                        <option value="อัน">อัน</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>บริษัทผู้ผลิต</label>
                                    <input type="text" class="form-control text-primary" id="manufacturer" name="manufacturer">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>ผู้แทนจำหน่าย</label>
                                    <input type="text" class="form-control text-primary" id="supplier" name="supplier">
                                </div>
                            </div>                        
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>ราคาซื้อ (บาท)</label>
                                    <input type="number" class="form-control text-primary" id="purchase_price" name="purchase_price" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>เบอร์โทรติดต่อ</label>
                                    <input type="text" class="form-control text-primary" id="contact_phone" name="contact_phone">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>วันที่ซื้อ</label>
                                    <input type="date" class="form-control text-primary" id="purchase_date" name="purchase_date">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>วันที่เริ่มใช้งาน</label>
                                    <input type="date" class="form-control text-primary" id="start_date" name="start_date">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>วันที่ขึ้นทะเบียน</label>
                                    <input type="date" class="form-control text-primary" id="register_date" name="register_date">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>สถานะ</label>
                                    <select class="form-control text-primary" id="machine_status" name="machine_status">
                                        <option value="active">ใช้งาน</option>
                                        <option value="maintenance">ซ่อมบำรุง</option>
                                        <option value="broken">ชำรุด</option>
                                        <option value="retired">เลิกใช้งาน</option>
                                    </select>
                                </div>
                            </div>                            
                        </div>


                        <div class="form-group">
                            <label>หมายเหตุ</label>
                            <textarea class="form-control text-primary" id="note" name="note" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> ยกเลิก
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับ Master Data -->
    <div class="modal fade" id="masterModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="masterModalTitle">
                        <i class="fas fa-plus"></i> เพิ่มข้อมูล
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="masterForm">
                    <div class="modal-body">
                        <input type="hidden" id="master_id" name="id">
                        <input type="hidden" id="master_type" name="type">
                        
                        <div class="form-group">
                            <label>ชื่อ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="master_name" name="name" required placeholder="กรอกชื่อ...">
                        </div>
                        <div id="group_name_field" style="display: none;">
                            <div class="form-row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>รหัสกลุ่ม</label>
                                        <input type="number" class="form-control" id="master_group_id" name="group_id" placeholder="เช่น 1, 2, 3..." min="1">
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>ชื่อกลุ่ม</label>
                                        <input type="text" class="form-control" id="master_group_name" name="group_name" placeholder="กรอกชื่อกลุ่ม...">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> ยกเลิก
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal บันทึก/แก้ไขประวัติการซ่อม -->
    <div class="modal fade" id="historyModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="historyModalTitle">
                        <i class="fas fa-plus"></i> เพิ่มประวัติการซ่อม
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="historyForm" onsubmit="saveHistory(event)">
                    <div class="modal-body">
                        <input type="hidden" id="history_id">
                        
                        <!-- ข้อมูลเครื่องจักร -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>รหัสเครื่องจักร <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="history_machine_code" required 
                                           list="history_machine_code_datalist"
                                           placeholder="พิมพ์หรือเลือกรหัสเครื่องจักร"
                                           onchange="fillMachineNameFromCode(this.value)">
                                    <datalist id="history_machine_code_datalist">
                                        <!-- จะโหลดจาก API -->
                                    </datalist>
                                    <small class="form-text text-muted">เลือกหรือพิมพ์รหัสเครื่องจักร</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>ชื่อเครื่องจักร</label>
                                    <input type="text" class="form-control" id="history_machine_name" readonly
                                           placeholder="แสดงอัตโนมัติจากรหัสเครื่อง">
                                    <small class="form-text text-muted">จะแสดงอัตโนมัติเมื่อเลือกรหัส</small>
                                </div>
                            </div>
                        </div>

                        <!-- ข้อมูลพื้นฐาน -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>ประเภทงาน <span class="text-danger">*</span></label>
                                    <select class="form-control" id="history_work_type" required onchange="updateDocumentNoPrefix()">
                                        <option value="PM">PM (Preventive Maintenance)</option>
                                        <option value="CAL">Calibration</option>
                                        <option value="OVH">Overhaul (ยกเครื่องใหม่)</option>
                                        <option value="INS">ตรวจสอบ (Inspection)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>เลขที่เอกสาร</label>
                                    <input type="text" class="form-control" id="history_document_no" placeholder="(สร้างอัตโนมัติ)" readonly>
                                    <small class="form-text text-muted">ระบบจะสร้างเลขที่เอกสารอัตโนมัติ</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>วันที่แจ้ง/ทำงาน <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="history_work_date" required>
                                </div>
                            </div>
                        </div>

                        <!-- รายละเอียดงาน -->
                        <div class="form-group">
                            <label>อาการเสีย/ปัญหา/รายละเอียด <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="history_issue" rows="3" required placeholder="อธิบายอาการเสียหรือปัญหาที่พบ"></textarea>
                        </div>

                        <div class="form-group">
                            <label>วิธีแก้ไข/การซ่อม</label>
                            <textarea class="form-control" id="history_solution" rows="3" placeholder="อธิบายวิธีการแก้ไขหรือการซ่อม"></textarea>
                        </div>

                        <div class="form-group">
                            <label>รายการอะไหล่ที่ใช้/รหัสอะไหล่</label>
                            <textarea class="form-control" id="history_parts" rows="2" placeholder="เช่น LE-135177 Grease Cartridge, SPU-10"></textarea>
                        </div>

                        <!-- เวลาและค่าใช้จ่าย -->
                        <h6 class="mt-3 mb-2"><i class="fas fa-clock"></i> เวลาและค่าใช้จ่าย</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>เวลาเริ่มปฏิบัติงาน</label>
                                    <input type="datetime-local" class="form-control" id="history_start_time" onchange="calculateWorkDuration()">
                                    <small class="form-text text-muted">วันเวลาที่เริ่มงาน</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>เวลาปฏิบัติงานเสร็จ</label>
                                    <input type="datetime-local" class="form-control" id="history_end_time" onchange="calculateWorkDuration()">
                                    <small class="form-text text-muted">วันเวลาที่เสร็จงาน</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>เวลาปฏิบัติงาน (ชั่วโมง)</label>
                                    <input type="number" class="form-control" id="history_work_hours" step="0.5" min="0" value="0" readonly>
                                    <small class="form-text text-muted">คำนวนอัตโนมัติ</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>เวลาหยุดเครื่อง (ชั่วโมง)</label>
                                    <input type="number" class="form-control" id="history_downtime_hours" step="0.5" min="0" value="0">
                                    <small class="form-text text-muted">ระบุด้วยตนเอง</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>ค่าแรง (บาท)</label>
                                    <input type="number" class="form-control" id="history_labor_cost" step="0.01" min="0" value="0" onchange="calculateTotalCost()">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>ค่าอะไหล่ (บาท)</label>
                                    <input type="number" class="form-control" id="history_parts_cost" step="0.01" min="0" value="0" onchange="calculateTotalCost()">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>ค่าใช้จ่ายอื่นๆ (บาท)</label>
                                    <input type="number" class="form-control" id="history_other_cost" step="0.01" min="0" value="0" onchange="calculateTotalCost()">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>รวมค่าใช้จ่าย (บาท)</label>
                                    <input type="number" class="form-control" id="history_total_cost" step="0.01" min="0" value="0" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- ผู้เกี่ยวข้อง -->
                        <h6 class="mt-3 mb-2"><i class="fas fa-users"></i> ผู้เกี่ยวข้อง</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>ผู้แจ้ง</label>
                                    <input type="text" class="form-control" id="history_reported_by">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>ผู้รับผิดชอบ/ช่าง</label>
                                    <input type="text" class="form-control" id="history_handled_by">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>สถานะ</label>
                                    <select class="form-control" id="history_status">
                                        <option value="pending">รอดำเนินการ</option>
                                        <option value="in-progress">กำลังดำเนินการ</option>
                                        <option value="completed" selected>เสร็จสิ้น</option>
                                        <option value="cancelled">ยกเลิก</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- หมายเหตุ -->
                        <div class="form-group">
                            <label>หมายเหตุ</label>
                            <textarea class="form-control" id="history_note" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> ยกเลิก
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal แสดงรายละเอียดประวัติ -->
    <div class="modal fade" id="historyDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle"></i> รายละเอียดประวัติการซ่อม
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="historyDetailContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> ปิด
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal เพิ่ม/แก้ไข User -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="userModalTitle">
                        <i class="fas fa-user-plus"></i> เพิ่มผู้ใช้ใหม่
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="userForm">
                    <div class="modal-body">
                        <input type="hidden" id="user_id" name="user_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>รหัสผ่าน <span class="text-danger" id="password_required">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password">
                                    <small class="form-text text-muted">ปล่อยว่างไว้ถ้าไม่ต้องการเปลี่ยน (เฉพาะแก้ไข)</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" class="form-control" id="email" name="email">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>เบอร์โทร</label>
                                    <input type="text" class="form-control" id="phone" name="phone">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>สิทธิ์การใช้งาน <span class="text-danger">*</span></label>
                                    <select class="form-control" id="role" name="role" required>
                                        <option value="viewer">ผู้ดู (Viewer)</option>
                                        <option value="staff">เจ้าหน้าที่ (Staff)</option>
                                        <option value="maintenance">ช่างซ่อม (Maintenance)</option>
                                        <option value="technician">ช่างเทคนิค (Technician)</option>
                                        <option value="engineer">วิศวกร (Engineer)</option>
                                        <option value="leader">ผู้นำ (Leader)</option>
                                        <option value="supervisor">หัวหน้างาน (Supervisor)</option>
                                        <option value="manager">ผู้จัดการ (Manager)</option>
                                        <!-- <option value="admin">ผู้ดูแลระบบ (Admin)</option> -->
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>รหัสพนักงาน</label>
                                    <input type="text" class="form-control" id="employee_id" name="employee_id">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>แผนก</label>
                                    <input type="text" class="form-control" id="department" name="department">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>สาขา</label>
                                    <input type="text" class="form-control" id="branch" name="branch">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>ตำแหน่ง</label>
                                    <input type="text" class="form-control" id="position" name="position">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> ยกเลิก
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Reset Password -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fas fa-key"></i> รีเซ็ตรหัสผ่าน
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        คุณต้องการรีเซ็ตรหัสผ่านของผู้ใช้ <strong id="reset_username"></strong> หรือไม่?
                    </div>
                    <p>รหัสผ่านใหม่จะถูกตั้งเป็น: <code class="text-danger">password123</code></p>
                    <p class="text-muted"><small>ผู้ใช้ควรเปลี่ยนรหัสผ่านทันทีหลังจาก login</small></p>
                    <input type="hidden" id="reset_user_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> ยกเลิก
                    </button>
                    <button type="button" class="btn btn-warning" onclick="confirmResetPassword()">
                        <i class="fas fa-key"></i> ยืนยันรีเซ็ต
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // ส่ง role ของ user ปัจจุบันให้ JavaScript
        var currentUserRole = '<?php echo $_SESSION['user_role'] ?? 'viewer'; ?>';
    </script>
    <script src="../assets/js/master_data.js"></script>
    <script src="../assets/js/machines.js"></script>
    <script src="../assets/js/machine_history.js"></script>
    <script src="../assets/js/users.js"></script>
    <script>
        // อัพเดทตัวอย่างเลขเอกสารตามประเภทที่เลือก
        function updateDocumentNoPrefix() {
            const workType = $('#history_work_type').val();
            const year = new Date().getFullYear() + 543; // แปลงเป็น พ.ศ.
            const year2digit = year.toString().slice(-2);
            
            const prefixExamples = {
                'PM': 'PM001/' + year2digit,
                'CAL': 'CAL001/' + year2digit,
                'OVH': 'OVH001/' + year2digit,
                'INS': 'INS001/' + year2digit
            };
            
            $('#history_document_no').attr('placeholder', prefixExamples[workType] || '(สร้างอัตโนมัติ)');
        }
        
        // Helper: format various date strings to DD-MM-YYYY
        function formatDateDMY(dateStr) {
            if (!dateStr) return '-';
            // If already in YYYY-MM-DD or starts with that, convert by splitting
            var m = dateStr.match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (m) {
                return m[3] + '-' + m[2] + '-' + m[1];
            }
            // Try parsing as Date and format (fallback)
            var d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr || '-';
            var dd = String(d.getDate()).padStart(2, '0');
            var mm = String(d.getMonth() + 1).padStart(2, '0');
            var yyyy = d.getFullYear();
            return dd + '-' + mm + '-' + yyyy;
        }
    </script>
    <script>
        // Load repairs data
        function loadRepairs(repairDate = '', documentNo = '', status = '', registrySigner = '') {
            $.ajax({
                url: '../api/get_all_repairs.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    repair_date: repairDate,
                    document_no: documentNo,
                    status: status,
                    registry_signer: registrySigner
                }),
                success: function(response) {
                    let html = '';
                    if (response.success && response.data.length > 0) {
                        response.data.forEach(function(repair) {
                            let statusBadge = '';
                            switch(parseInt(repair.status)) {
                                case 10:
                                    statusBadge = '<span class="badge badge-secondary">รออนุมัติ</span>';
                                    break;
                                case 11:
                                    statusBadge = '<span class="badge badge-danger">ไม่อนุมัติ</span>';
                                    break;
                                case 20:
                                    statusBadge = '<span class="badge badge-warning">รอดำเนินการ</span>';
                                    break;
                                case 30:
                                    statusBadge = '<span class="badge badge-info">รออะไหล่</span>';
                                    break;
                                case 40:
                                    statusBadge = '<span class="badge badge-success">ซ่อมเสร็จสิ้น</span>';
                                    break;
                                case 50:
                                    statusBadge = '<span class="badge badge-dark">ยกเลิก</span>';
                                    break;
                                default:
                                    statusBadge = '<span class="badge badge-secondary">' + repair.status + '</span>';
                            }
                            
                            html += '<tr>';
                            html += '<td><strong style="color: #007bff;">' + (repair.document_no || '-') + '</strong></td>';
                            html += '<td>' + formatDateDMY(repair.start_job) + '</td>';
                            html += '<td>' + (repair.machine_number || '-') + '</td>';
                            html += '<td>' + (repair.reported_by || '-') + '</td>';
                            html += '<td>' + (repair.issue ? (repair.issue.length > 50 ? repair.issue.substring(0, 50) + '...' : repair.issue) : '-') + '</td>';
                            html += '<td>' + statusBadge + '</td>';
                            html += '<td>' + (repair.handled_by || '-') + '</td>';
                            html += '<td>' + (repair.registry_signer || '-') + '</td>';
                            html += '<td class="text-center">';
                            html += '<a href="print_form.php?id=' + repair.id + '" class="btn btn-sm btn-primary" target="_blank" title="ดูรายละเอียด"><i class="fas fa-eye"></i></a> ';
                            html += '<button class="btn btn-sm btn-success" onclick="printRepair(' + repair.id + ')" title="พิมพ์ใบแจ้งซ่อม"><i class="fas fa-print"></i></button> ';
                            // html += '<button class="btn btn-sm btn-danger" onclick="deleteRepair(' + repair.id + ')" title="ลบ"><i class="fas fa-trash"></i></button>';
                            
                            html += '</td>';
                            html += '</tr>';
                        });
                    } else {
                        html = '<tr><td colspan="9" class="text-center">ไม่พบข้อมูล</td></tr>';
                    }
                    $('#repairsTableBody').html(html);
                },
                error: function() {
                    $('#repairsTableBody').html('<tr><td colspan="9" class="text-center text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>');
                }
            });
        }

        // Filter repairs
        function filterRepairs() {
            const repairDate = $('#filter_repair_date').val();
            const documentNo = $('#filter_document_no').val();
            const status = $('#filter_status').val();
            const registrySigner = $('#filter_registry_signer').val();
            loadRepairs(repairDate, documentNo, status, registrySigner);
        }

        function clearFilters() {
            $('#filter_repair_date').val('');
            $('#filter_document_no').val('');
            $('#filter_status').val('');
            $('#filter_registry_signer').val('');
            loadRepairs();
        }

        // Print repair form
        function printRepair(id) {
            const printWindow = window.open('print_form.php?id=' + id, '_blank');
            printWindow.onload = function() {
                printWindow.print();
            };
        }

        // Delete repair
        function deleteRepair(id) {
            if (confirm('คุณต้องการลบใบแจ้งซ่อมนี้หรือไม่?')) {
                $.ajax({
                    url: '../api/delete_repair.php',
                    method: 'POST',
                    data: JSON.stringify({id: id}),
                    contentType: 'application/json',
                    success: function(response) {
                        if (response.success) {
                            alert('ลบข้อมูลเรียบร้อย');
                            loadRepairs();
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + response.message);
                        }
                    }
                });
            }
        }

        // Load on page load
        $(document).ready(function() {
            loadRepairs();
            
            // Load master data when tab is shown
            $('#reserve1-tab').on('shown.bs.tab', function() {
                loadMasterData('company');
            });
            
            // Load data when switching master data tabs
            $('#company-tab').on('click', function() { loadMasterData('company'); });
            $('#branch-tab').on('click', function() { loadMasterData('branch'); });
            $('#division-tab').on('click', function() { loadMasterData('division'); });
            $('#department-tab').on('click', function() { loadMasterData('department'); });
            $('#issue-tab').on('click', function() { loadMasterData('issue'); });
            
            // Load users when users tab is shown
            $('#users-tab').on('shown.bs.tab', function() {
                if (typeof loadUsers === 'function') {
                    loadUsers();
                }
            });
        });

        // ========== Master Data Functions ==========
        let currentMasterType = '';

        function loadMasterData(type) {
            console.log('Loading master data for type:', type);
            currentMasterType = type;
            const tbody = $('#' + type + 'TableBody');
            tbody.html('<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> กำลังโหลด...</td></tr>');
            
            $.ajax({
                url: '../api/master_data.php',
                method: 'GET',
                data: { action: 'list', type: type },
                success: function(response) {
                    // console.log('Master data response for', type, ':', response);
                    if (response.success) {
                        displayMasterData(type, response.data);
                    } else {
                        tbody.html('<tr><td colspan="6" class="text-center text-danger">' + response.message + '</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading master data for', type, ':', error);
                    tbody.html('<tr><td colspan="6" class="text-center text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>');
                }
            });
        }

        function displayMasterData(type, data) {
            const tbody = $('#' + type + 'TableBody');
            let html = '';
            const colspan = (type === 'department') ? 7 : 6;
            
            if (data.length === 0) {
                tbody.html('<tr><td colspan="' + colspan + '" class="text-center">ไม่มีข้อมูล</td></tr>');
                return;
            }
            
            data.forEach(function(item) {
                const statusBadge = item.is_active == 1 
                    ? '<span class="badge badge-success">ใช้งาน</span>' 
                    : '<span class="badge badge-secondary">ไม่ใช้งาน</span>';
                
                html += '<tr>';
                html += '<td class="text-center">' + item.id + '</td>';
                html += '<td>' + (item.name || '-') + '</td>';
                if (type === 'department') {
                    html += '<td>' + (item.group_id ? item.group_id + (item.group_name ? ' - ' + item.group_name : '') : (item.group_name || '-')) + '</td>';
                }
                html += '<td class="text-center"><small>' + (item.created_by || '-') + '</small></td>';
                html += '<td class="text-center"><small>' + (item.updated_by || '-') + '</small></td>';
                html += '<td class="text-center">' + statusBadge + '</td>';
                html += '<td class="text-center">';
                html += '<button class="btn btn-sm btn-primary" onclick="editMasterItem(\'' + type + '\', ' + item.id + ')" title="แก้ไข"><i class="fas fa-edit"></i></button> ';
                html += '<button class="btn btn-sm btn-warning" onclick="toggleMasterStatus(\'' + type + '\', ' + item.id + ')" title="เปลี่ยนสถานะ"><i class="fas fa-power-off"></i></button> ';
                html += '<button class="btn btn-sm btn-danger" onclick="deleteMasterItem(\'' + type + '\', ' + item.id + ')" title="ลบ"><i class="fas fa-trash"></i></button>';
                html += '</td>';
                html += '</tr>';
            });
            
            tbody.html(html);
        }

        function openMasterModal(type) {
            currentMasterType = type;
            $('#master_type').val(type);
            $('#master_id').val('');
            $('#masterForm')[0].reset();
            
            if (type === 'department') {
                $('#group_name_field').show();
            } else {
                $('#group_name_field').hide();
                $('#master_group_id').val('');
                $('#master_group_name').val('');
            }
            
            let title = '';
            if (type === 'company') {
                title = 'เพิ่มบริษัท';
            } else if (type === 'branch') {
                title = 'เพิ่มสาขา';
            } else if (type === 'division') {
                title = 'เพิ่มฝ่าย';
            } else if (type === 'department') {
                title = 'เพิ่มหน่วยงาน';
            } else if (type === 'issue') {
                title = 'เพิ่มอาการเสีย';
            }
            
            $('#masterModalTitle').html('<i class="fas fa-plus"></i> ' + title);
            $('#masterModal').modal('show');
        }

        function editMasterItem(type, id) {
            currentMasterType = type;
            $.ajax({
                url: '../api/master_data.php',
                method: 'GET',
                data: { action: 'get', type: type, id: id },
                success: function(response) {
                    if (response.success) {
                        const item = response.data;
                        $('#master_type').val(type);
                        $('#master_id').val(item.id);
                        $('#master_name').val(item.name);
                        
                        if (type === 'department') {
                            $('#master_group_id').val(item.group_id || '');
                            $('#master_group_name').val(item.group_name || '');
                            $('#group_name_field').show();
                        } else {
                            $('#master_group_name').val('');
                            $('#group_name_field').hide();
                        }
                        
                        let title = '';
                        if (type === 'company') {
                            title = 'แก้ไขบริษัท';
                        } else if (type === 'branch') {
                            title = 'แก้ไขสาขา';
                        } else if (type === 'division') {
                            title = 'แก้ไขฝ่าย';
                        } else if (type === 'department') {
                            title = 'แก้ไขหน่วยงาน';
                        } else if (type === 'issue') {
                            title = 'แก้ไขอาการเสีย';
                        }
                        
                        $('#masterModalTitle').html('<i class="fas fa-edit"></i> ' + title);
                        $('#masterModal').modal('show');
                    }
                }
            });
        }

        function deleteMasterItem(type, id) {
            if (!confirm('คุณต้องการลบข้อมูลนี้หรือไม่?')) return;
            
            $.ajax({
                url: '../api/master_data.php',
                method: 'POST',
                data: { action: 'delete', type: type, id: id },
                success: function(response) {
                    if (response.success) {
                        alert('ลบข้อมูลเรียบร้อย');
                        loadMasterData(type);
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + response.message);
                    }
                }
            });
        }

        function toggleMasterStatus(type, id) {
            $.ajax({
                url: '../api/master_data.php',
                method: 'POST',
                data: { action: 'toggle_status', type: type, id: id },
                success: function(response) {
                    if (response.success) {
                        loadMasterData(type);
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + response.message);
                    }
                }
            });
        }

        // Master form submit
        $('#masterForm').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: '../api/master_data.php',
                method: 'POST',
                data: $(this).serialize() + '&action=save',
                success: function(response) {
                    if (response.success) {
                        alert('บันทึกข้อมูลเรียบร้อย');
                        $('#masterModal').modal('hide');
                        loadMasterData(currentMasterType);
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + response.message);
                    }
                },
                error: function() {
                    alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                }
            });
        });
        
        // ========== Machine History Functions ==========
        // Load machines into dropdown when tab is shown
        let machinesData = [];
        
        $('#reserve2-tab').on('shown.bs.tab', function() {
            loadMachinesDropdown();
        });
        
        function loadMachinesDropdown() {
            $.ajax({
                url: '../api/machines.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        machinesData = response.data;
                        let options = '';
                        response.data.forEach(function(machine) {
                            options += '<option value="' + machine.machine_code + '">' + 
                                       machine.machine_code + ' - ' + machine.machine_name + '</option>';
                        });
                        $('#history_machine_datalist').html(options);
                    }
                }
            });
        }
        
        $('#btn_show_history').on('click', function() {
            const machineCode = $('#history_machine_select_input').val().trim();
            
            if (!machineCode) {
                alert('กรุณาพิมพ์หรือเลือกรหัสเครื่องจักร');
                return;
            }
            
            // Find machine by code
            const machine = machinesData.find(m => m.machine_code === machineCode);
            
            if (!machine) {
                alert('ไม่พบรหัสเครื่องจักรนี้');
                return;
            }
            
            showMachineHistory(machine.id, machineCode);
        });
        
        function showMachineHistory(machineId, machineCode) {
            // Load machine info
            $.ajax({
                url: '../api/machines.php?id=' + machineId,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayMachineInfo(response.data);
                        // โหลดประวัติจากระบบใหม่
                        loadMachineHistoryByCode(machineCode);
                    }
                }
            });
        }
        
        function displayMachineInfo(machine) {
            // Update status badge in header
            let statusClass = '';
            switch(machine.machine_status) {
                case 'active': statusClass = 'badge-success'; break;
                case 'maintenance': statusClass = 'badge-warning'; break;
                case 'broken': statusClass = 'badge-danger'; break;
                case 'retired': statusClass = 'badge-secondary'; break;
                default: statusClass = 'badge-info';
            }
            $('#machine_status_badge').html('<strong>สถานะ: </strong><span class="badge ' + statusClass + ' badge-lg">' + getStatusText(machine.machine_status) + '</span>');
            
            // Format price
            let price = '-';
            if (machine.purchase_price) {
                price = parseFloat(machine.purchase_price).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' บาท';
            }
            
            // Dates: format to DD-MM-YYYY
            let purchaseDate = formatDateDMY(machine.purchase_date);
            let startDate = formatDateDMY(machine.start_date);
            let registerDate = formatDateDMY(machine.register_date);
            
            let infoHtml = `
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-5 text-right"><strong>ชื่อเครื่องจักร:</strong></div>
                            <div class="col-7">${machine.machine_name || '-'}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-5 text-right"><strong>บริษัทผู้ผลิต:</strong></div>
                            <div class="col-7">${machine.manufacturer || '-'}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-5 text-right"><strong>ราคาซื้อ:</strong></div>
                            <div class="col-7">${price}</div>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-5 text-right"><strong>รหัส:</strong></div>
                            <div class="col-7">${machine.machine_code || '-'}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-5 text-right"><strong>ผู้แทนจำหน่าย:</strong></div>
                            <div class="col-7">${machine.supplier || '-'}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-5 text-right"><strong>วันที่ซื้อ:</strong></div>
                            <div class="col-7">${purchaseDate}</div>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-5 text-right"><strong>หน่วยงานที่รับผิดชอบ:</strong></div>
                            <div class="col-7">${machine.responsible_dept || '-'}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-5 text-right"><strong>รุ่น:</strong></div>
                            <div class="col-7">${machine.model || '-'}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-5 text-right"><strong>วันที่เริ่มใช้งาน:</strong></div>
                            <div class="col-7">${startDate}</div>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-5 text-right"><strong>พื้นที่ใช้งาน:</strong></div>
                            <div class="col-7">${machine.work_area || '-'}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-5 text-right"><strong>ขนาด:</strong></div>
                            <div class="col-7">${machine.horsepower || '-'}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-5 text-right"><strong>วันที่ขึ้นทะเบียน:</strong></div>
                            <div class="col-7">${registerDate}</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-5 text-right"><strong>ประเภทเครื่องจักร:</strong></div>
                            <div class="col-7">${machine.machine_type || '-'}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-5 text-right"><strong>น้ำหนัก:</strong></div>
                            <div class="col-7">${machine.weight || '-'}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-5 text-right"><strong>เบอร์โทรติดต่อ:</strong></div>
                            <div class="col-7">${machine.contact_phone || '-'}</div>
                        </div>
                    </div>
                </div>
            `;
            $('#machine_current_info').html(infoHtml);
            $('#machine_info_card').show();
        }
        
        function getStatusText(status) {
            switch(status) {
                case 'active': return 'ใช้งาน';
                case 'maintenance': return 'ซ่อมบำรุง';
                case 'broken': return 'ชำรุด';
                case 'retired': return 'เลิกใช้งาน';
                default: return status || 'ไม่ระบุ';
            }
        }
        
        // ฟังก์ชัน loadRepairHistory ถูกย้ายไปใช้ loadMachineHistoryByCode แทน (ใน machine_history.js)
    </script>
</body>
</html>
