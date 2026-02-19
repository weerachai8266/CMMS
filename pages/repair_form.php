<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="ระบบแจ้งซ่อมเครื่องจักร - Maintenance Request System">
    <meta name="author" content="MT Department">
    <title>ระบบแจ้งซ่อมเครื่องจักร | Maintenance Request System</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
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
                <li class="nav-item active">
                    <a class="nav-link" href="repair_form.php"><i class="fas fa-clipboard-list"></i> แจ้งซ่อม</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="approval.php"><i class="fas fa-clipboard-check"></i> อนุมัติใบแจ้งซ่อม</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="machines.php"><i class="fas fa-user-cog"></i> เจ้าหน้าที่ MT</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="monitor.php"><i class="fas fa-tv"></i> Monitor</a>
                </li>
                <li class="nav-item">
                        <a class="nav-link" href="kpi.php"><i class="fas fa-chart-line"></i> KPI</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2><i class="fas fa-clipboard-list"></i> ระบบแจ้งซ่อมเครื่องจักร</h2>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-plus-circle"></i> แจ้งซ่อมใหม่</h4>
            <!-- <a href="monitor.php" class="btn btn-light" target="_blank">
                <i class="fas fa-tv"></i> เปิด Monitor
            </a> -->
        </div>
        <div class="card-body">
            <form id="repairForm" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="division">ฝ่าย <span class="text-danger">*</span></label>
                        <select class="form-control" id="division" name="division" required>
                            <option value="">-- เลือกฝ่าย --</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="department">หน่วยงาน <span class="text-danger">*</span></label>
                        <select class="form-control" id="department" name="department" required>
                            <option value="">-- เลือกหน่วยงาน --</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="branch">สาขา <span class="text-danger">*</span></label>
                        <select class="form-control" id="branch" name="branch" required>
                            <option value="">-- เลือกสาขา --</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="machine_number">หมายเลขเครื่องจักร <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="machine_number" name="machine_number" list="machine_list" autocomplete="off" required placeholder="พิมพ์หรือเลือกเครื่องจักร...">
                        <datalist id="machine_list">
                        </datalist>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="machine_name">ชื่อเครื่องจักร</label>
                        <input type="text" class="form-control" id="machine_name" name="machine_name" readonly style="background-color: #e9ecef;">
                    </div>
                </div>

                <!-- โปรดดำเนินการ -->
                <div class="form-group">
                    <label>โปรดดำเนินการ <span class="text-danger">*</span></label>
                    <div class="form-row">
                        <div class="col-md-2">
                            <div class="custom-control custom-radio">
                                <input type="radio" class="custom-control-input" id="action_check" name="action_type" value="check">
                                <label class="custom-control-label" for="action_check">ตรวจสอบ</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="custom-control custom-radio">
                                <input type="radio" class="custom-control-input" id="action_fix" name="action_type" value="fix">
                                <label class="custom-control-label" for="action_fix">แก้ไขปัญหา</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="custom-control custom-radio">
                                <input type="radio" class="custom-control-input" id="action_repair" name="action_type" value="repair" checked>
                                <label class="custom-control-label" for="action_repair">ซ่อม</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="custom-control custom-radio">
                                <input type="radio" class="custom-control-input" id="action_other" name="action_type" value="other">
                                <label class="custom-control-label" for="action_other">อื่นๆ</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm" id="action_other_text" name="action_other_text" placeholder="ระบุ..." disabled>
                        </div>
                    </div>
                </div>

                <!-- ความเร่งด่วนของงาน -->
                <div class="form-group">
                    <label>ความเร่งด่วนของงาน <span class="text-danger">*</span></label>
                    <div class="form-row">
                        <div class="col-md-2">
                            <div class="custom-control custom-radio">
                                <input type="radio" class="custom-control-input" id="priority_urgent" name="priority" value="urgent" checked>
                                <label class="custom-control-label" for="priority_urgent">ด่วน</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="custom-control custom-radio">
                                <input type="radio" class="custom-control-input" id="priority_normal" name="priority" value="normal">
                                <label class="custom-control-label" for="priority_normal">ปกติ</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="issue">อาการเสีย <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="issue" name="issue" list="issue_list" autocomplete="off" required placeholder="พิมพ์หรือเลือกอาการเสีย...">
                    <datalist id="issue_list">
                        <!-- โหลดจาก database -->
                    </datalist>
                    <!-- <small class="form-text text-muted">💡 เลือกจากรายการหรือพิมพ์อาการเสียอื่น ๆ ได้</small> -->
                </div>
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="reported_by">ผู้แจ้ง <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="reported_by" name="reported_by" required>
                    </div>
                    <div class="form-group col-md-9">
                        <label for="image">แนบรูปก่อนซ่อม (ถ้ามี)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="image" name="image" accept="image/*">
                            <label class="custom-file-label" for="image">เลือกรูปภาพ...</label>
                        </div>
                        <small class="form-text text-muted">รองรับไฟล์: JPG, PNG, GIF (ขนาดไม่เกิน 5MB)</small>
                        <div id="image-preview" class="mt-2" style="display: none;">
                            <img id="preview-img" src="" alt="Preview" style="max-width: 300px; max-height: 300px; border: 2px solid #ddd; border-radius: 5px; padding: 5px;">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> บันทึก
                </button>
            </form>
        </div>
    </div>
    
    <!-- รายการแจ้งซ่อม -->
    <div class="card mt-4">
        <div class="card-header bg-dark text-white">
            <h4 class="mb-0"><i class="fas fa-list"></i> รายการแจ้งซ่อมทั้งหมด</h4>
        </div>
        <div class="card-body">
            <div class="form-row mb-3">
                <div class="col-md-3">
                    <label for="filter_department">กรองตามแผนก</label>
                    <input type="text" class="form-control" id="filter_department" placeholder="ค้นหาแผนก...">
                </div>
                <div class="col-md-3">
                    <label for="filter_machine">หมายเลขเครื่องจักร</label>
                    <input type="text" class="form-control" id="filter_machine" placeholder="ค้นหาเครื่องจักร...">
                </div>
                <div class="col-md-2">
                    <label for="filter_reported_by">ผู้แจ้ง</label>
                    <input type="text" class="form-control" id="filter_reported_by" placeholder="ค้นหาผู้แจ้ง...">
                </div>
                <div class="col-md-2">
                    <label for="filter_status">สถานะ</label>
                    <select class="form-control" id="filter_status">
                        <option value="">ทั้งหมด</option>
                        <option value="10">📋 รออนุมัติ</option>
                        <option value="20">⏳ รอดำเนินการ</option>
                        <option value="30">⚙️ รออะไหล่</option>
                        <option value="40">✓ ซ่อมเสร็จสิ้น</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-secondary btn-block" id="btn_clear_filter">
                        <i class="fas fa-redo"></i> ล้างตัวกรอง
                    </button>
                </div>
            </div>
            
            <div id="repair-list"></div>
        </div>
    </div>
</div>

<!-- Modal สำหรับกดเสร็จสิ้น -->
<div class="modal fade" id="completeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-check-circle"></i> ซ่อมเสร็จแล้ว</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="completeForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="complete_id" name="id">
                    <!-- <div>
                        <h3>3 : บันทึกการดำเนินการซ่อม / สร้าง</h3>
                    </div>
                     -->                   

                    <div class="form-group">
                        <label for="handled_by">ช่างผู้ดำเนินการ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="handled_by_input" name="handled_by" required>
                    </div>

                    <hr>
                    <div>
                        <h3>4 : บันทึกการรับงาน</h3>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-radio">
                            <input type="radio" class="custom-control-input" id="job_complete" name="job_status" value="complete" checked>
                            <label class="custom-control-label" for="job_complete">งานเสร็จสมบูรณ์ตามใบแจ้งซ่อมนี้แล้ว</label>
                        </div>
                        <div class="custom-control custom-radio mt-2">
                            <input type="radio" class="custom-control-input" id="job_other" name="job_status" value="other">
                            <label class="custom-control-label" for="job_other">อื่นๆ</label>
                        </div>
                        <input type="text" class="form-control form-control-sm mt-2" id="job_other_text" name="job_other_text" placeholder="ระบุ..." disabled>
                    </div>

                    <div class="form-group">
                        <label for="receiver_name">ลงชื่อ ( ผู้รับงาน )</label>
                        <input type="text" class="form-control" id="receiver_name" name="receiver_name">
                    </div>

                    <div class="form-group">
                        <label for="image_after">แนบรูปหลังซ่อม (ถ้ามี)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="image_after" name="image_after" accept="image/*">
                            <label class="custom-file-label" for="image_after">เลือกรูปภาพ...</label>
                        </div>
                        <small class="form-text text-muted">รองรับไฟล์: JPG, PNG, GIF (ขนาดไม่เกิน 5MB)</small>
                        <div id="image-after-preview" class="mt-2" style="display: none;">
                            <img id="preview-after-img" src="" alt="Preview" style="max-width: 100%; max-height: 200px; border: 2px solid #ddd; border-radius: 5px; padding: 5px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> ยืนยันเสร็จสิ้น
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

<!-- Bootstrap JS Bundle (includes Popper.js) -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="../assets/js/repair_form.js"></script>
<script src="../assets/js/master_data.js"></script>

<script>
    // Load master data dropdowns
    $(document).ready(function() {
        loadDivisions('#division', '-- เลือกฝ่าย --');
        loadDepartments('#department', '-- เลือกหน่วยงาน --');
        loadBranches('#branch', '-- เลือกสาขา --');
        loadIssues();
    });

    function loadIssues() {
        $.ajax({
            url: '../api/master_data.php',
            method: 'GET',
            data: { action: 'list', type: 'issue' },
            success: function(response) {
                if (response.success) {
                    let html = '';
                    response.data.forEach(function(item) {
                        if (item.is_active == 1) {
                            html += '<option value="' + item.name + '">';
                        }
                    });
                    $('#issue_list').html(html);
                }
            }
        });
    }

    // Show/hide "อื่นๆ" text input for action_type
    $('input[name="action_type"]').on('change', function() {
        if ($('#action_other').is(':checked')) {
            $('#action_other_text').prop('disabled', false).focus();
        } else {
            $('#action_other_text').prop('disabled', true).val('');
        }
    });

    // Show/hide "อื่นๆ" text input for operation_type (checkbox)
    $('#operation_other').on('change', function() {
        if ($(this).is(':checked')) {
            $('#operation_other_text').prop('disabled', false).focus();
        } else {
            $('#operation_other_text').prop('disabled', true).val('');
        }
    });

    // Show/hide "อื่นๆ" text input for job_status
    $('input[name="job_status"]').on('change', function() {
        if ($('#job_other').is(':checked')) {
            $('#job_other_text').prop('disabled', false).focus();
        } else {
            $('#job_other_text').prop('disabled', true).val('');
        }
    });
</script>

</body>
</html>
