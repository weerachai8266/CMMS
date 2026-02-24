<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบแจ้งซ่อม | CMMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .card-section { margin-bottom: 1.25rem; }
        .machine-info-box { background:#f8f9fa; border:1px solid #dee2e6; border-radius:6px; padding:10px 14px; font-size:.9rem; }
        .badge-urgent { background:#dc3545; color:#fff; }
        .badge-normal { background:#28a745; color:#fff; }
        .select2-container { width:100% !important; }
        label .text-danger { font-size:.8rem; }
    </style>
</head>
<body>

<?php require_once '../config/config.php'; ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="../index.php"><i class="fas fa-tools"></i> CMMS</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item"><a class="nav-link" href="../index.php"><i class="fas fa-home"></i> หน้าแรก</a></li>
                <li class="nav-item active"><a class="nav-link" href="repair_form.php"><i class="fas fa-clipboard-list"></i> แจ้งซ่อม</a></li>
                <li class="nav-item"><a class="nav-link" href="approval.php"><i class="fas fa-clipboard-check"></i> อนุมัติ</a></li>
                <li class="nav-item"><a class="nav-link" href="machines.php"><i class="fas fa-cog"></i> เครื่องจักร</a></li>
                <li class="nav-item"><a class="nav-link" href="monitor.php"><i class="fas fa-tv"></i> Monitor</a></li>
                <li class="nav-item"><a class="nav-link" href="kpi.php"><i class="fas fa-chart-line"></i> KPI</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid mt-4">

    <!-- ===== FORM ===== -->
    <div class="card card-section shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-plus-circle"></i> แจ้งซ่อมใหม่</h5>
        </div>
        <div class="card-body">
            <form id="repairForm" enctype="multipart/form-data" novalidate>

                <!-- ROW 1: สาขา / ฝ่าย / หน่วยงาน -->
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="branch_id">สาขา <span class="text-danger">*</span></label>
                        <select class="form-control" id="branch_id" name="branch_id" required>
                            <option value="">-- เลือกสาขา --</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="division_id">ฝ่าย</label>
                        <select class="form-control" id="division_id" name="division_id">
                            <option value="">-- เลือกฝ่าย --</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="department_id">หน่วยงาน</label>
                        <select class="form-control" id="department_id" name="department_id">
                            <option value="">-- เลือกหน่วยงาน --</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-outline-secondary btn-block" id="btnLoadMachines">
                            <i class="fas fa-search"></i> โหลดเครื่องจักร
                        </button>
                    </div>
                </div>

                <!-- ROW 2: เครื่องจักร -->
                <div class="form-row">
                    <div class="form-group col-md-5">
                        <label for="machine_id">เครื่องจักร <span class="text-danger">*</span></label>
                        <select class="form-control" id="machine_id" name="machine_id" required>
                            <option value="">-- เลือกเครื่องจักร --</option>
                        </select>
                    </div>
                    <div class="form-group col-md-7">
                        <label>&nbsp;</label>
                        <div class="machine-info-box" id="machineInfoBox" style="display:none;">
                            <span id="machineInfoText"></span>
                        </div>
                    </div>
                </div>

                <!-- ROW 3: อาการเสีย -->
                <div class="form-row">
                    <div class="form-group col-md-5">
                        <label for="issue_id">อาการเสีย</label>
                        <select class="form-control" id="issue_id" name="issue_id">
                            <option value="">-- เลือกอาการเสีย --</option>
                        </select>
                    </div>
                    <div class="form-group col-md-7">
                        <label for="issue_detail">รายละเอียดเพิ่มเติม / อาการเสียที่ไม่มีในรายการ</label>
                        <input type="text" class="form-control" id="issue_detail" name="issue_detail"
                               placeholder="อธิบายอาการเสียเพิ่มเติม...">
                    </div>
                </div>

                <!-- ROW 4: ประเภทการดำเนินการ + ความเร่งด่วน -->
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>โปรดดำเนินการ <span class="text-danger">*</span></label>
                        <div id="actionTypeContainer" class="d-flex flex-wrap gap-2 pt-1">
                            <!-- โหลดจาก DB -->
                        </div>
                        <input type="hidden" id="action_type_id" name="action_type_id">
                        <input type="text" class="form-control form-control-sm mt-2"
                               id="action_detail" name="action_detail"
                               placeholder="ระบุรายละเอียด..." style="display:none;">
                    </div>
                    <div class="form-group col-md-6">
                        <label>ความเร่งด่วน <span class="text-danger">*</span></label>
                        <div class="pt-1">
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" class="custom-control-input" id="prio_urgent"
                                       name="priority" value="urgent" checked>
                                <label class="custom-control-label text-danger font-weight-bold" for="prio_urgent">
                                    <i class="fas fa-exclamation-circle"></i> ด่วน
                                </label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" class="custom-control-input" id="prio_normal"
                                       name="priority" value="normal">
                                <label class="custom-control-label text-success" for="prio_normal">
                                    <i class="fas fa-check-circle"></i> ปกติ
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ROW 5: ผู้แจ้ง + รูปภาพ -->
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4">
                        <label for="reported_by_id">ผู้แจ้ง (เลือกจากระบบ)</label>
                        <select class="form-control" id="reported_by_id" name="reported_by_id">
                            <option value="">-- เลือกผู้แจ้ง --</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="reported_by_name">หรือพิมพ์ชื่อผู้แจ้ง <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="reported_by_name" name="reported_by_name"
                               placeholder="ชื่อ-สกุล ผู้แจ้ง">
                        <small class="form-text text-muted">กรอกอย่างใดอย่างหนึ่ง</small>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="image">แนบรูปก่อนซ่อม (ถ้ามี)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="image" name="image"
                                   accept="image/*">
                            <label class="custom-file-label" for="image">เลือกรูปภาพ...</label>
                        </div>
                        <small class="form-text text-muted">JPG, PNG, GIF, WEBP ไม่เกิน 5 MB</small>
                        <div id="image-preview" class="mt-2" style="display:none;">
                            <img id="preview-img" src="" alt="Preview"
                                 style="max-width:200px;max-height:150px;border:2px solid #ddd;border-radius:5px;padding:4px;">
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <button type="reset" class="btn btn-outline-secondary">
                        <i class="fas fa-undo"></i> ล้างฟอร์ม
                    </button>
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-save"></i> บันทึกใบแจ้งซ่อม
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- ===== LIST ===== -->
    <div class="card card-section shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list"></i> รายการแจ้งซ่อมทั้งหมด</h5>
            <button class="btn btn-sm btn-outline-light" id="btnRefreshList">
                <i class="fas fa-sync-alt"></i> รีเฟรช
            </button>
        </div>
        <div class="card-body">
            <!-- Filter bar -->
            <div class="form-row mb-3">
                <div class="col-md-3">
                    <input type="text" class="form-control form-control-sm" id="f_machine"
                           placeholder="ค้นหาเครื่องจักร...">
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control form-control-sm" id="f_dept"
                           placeholder="ค้นหาหน่วยงาน...">
                </div>
                <div class="col-md-2">
                    <select class="form-control form-control-sm" id="f_status">
                        <option value="">ทุกสถานะ</option>
                        <option value="10">📋 รออนุมัติ</option>
                        <option value="20">⏳ รอดำเนินการ</option>
                        <option value="30">⚙️ รออะไหล่</option>
                        <option value="40">✅ เสร็จสิ้น</option>
                        <option value="11">❌ ไม่อนุมัติ</option>
                        <option value="50">🚫 ยกเลิก</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-control form-control-sm" id="f_priority">
                        <option value="">ทุกความเร่งด่วน</option>
                        <option value="urgent">ด่วน</option>
                        <option value="normal">ปกติ</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-sm btn-secondary btn-block" id="btnClearFilter">
                        <i class="fas fa-redo"></i> ล้างตัวกรอง
                    </button>
                </div>
            </div>

            <div id="repair-list">
                <div class="text-center text-muted py-4">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">กำลังโหลด...</p>
                </div>
            </div>
        </div>
    </div>

</div><!-- /container -->

<!-- Modal: เสร็จสิ้น -->
<div class="modal fade" id="completeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-check-circle"></i> บันทึกการซ่อมเสร็จสิ้น</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="completeForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="complete_id" name="id">
                    <input type="hidden" name="status" value="40">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>ช่างผู้ดำเนินการ <span class="text-danger">*</span></label>
                            <select class="form-control" id="handled_by_id" name="handled_by_id">
                                <option value="">-- เลือกช่าง --</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>ผู้รับงาน (ลงชื่อ)</label>
                            <input type="text" class="form-control" id="receiver_name" name="receiver_name"
                                   placeholder="ชื่อผู้รับงาน">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>สรุปการซ่อม / บันทึก MT</label>
                        <textarea class="form-control" id="mt_report" name="mt_report" rows="3"
                                  placeholder="อธิบายวิธีแก้ไข วัสดุที่ใช้ ฯลฯ"></textarea>
                    </div>
                    <div class="form-group">
                        <label>ผลการซ่อม <span class="text-danger">*</span></label>
                        <div class="custom-control custom-radio">
                            <input type="radio" class="custom-control-input" id="job_complete"
                                   name="job_status" value="complete" checked>
                            <label class="custom-control-label" for="job_complete">งานเสร็จสมบูรณ์</label>
                        </div>
                        <div class="custom-control custom-radio mt-1">
                            <input type="radio" class="custom-control-input" id="job_other"
                                   name="job_status" value="other">
                            <label class="custom-control-label" for="job_other">อื่นๆ</label>
                        </div>
                        <input type="text" class="form-control form-control-sm mt-2"
                               id="job_other_text" name="job_status_note" placeholder="ระบุ..." disabled>
                    </div>
                    <div class="form-group">
                        <label>แนบรูปหลังซ่อม (ถ้ามี)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="image_after" name="image_after"
                                   accept="image/*">
                            <label class="custom-file-label" for="image_after">เลือกรูปภาพ...</label>
                        </div>
                        <div id="after-preview" class="mt-2" style="display:none;">
                            <img id="preview-after-img" src="" alt="Preview"
                                 style="max-width:100%;max-height:180px;border:2px solid #ddd;border-radius:5px;padding:4px;">
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

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/repair_form.js"></script>
</body>
</html>
