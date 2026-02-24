/**
 * repair_form.js  (v3 - ID-based, fully normalized)
 * ระบบแจ้งซ่อม — Cascading dropdowns, form submit, repair list
 */

const API = '../api/form_data.php';
const SAVE_API = '../api/save_repair.php';
const LIST_API = '../api/get_all_repairs.php';
const STATUS_API = '../api/update_status.php';

/* ================================================================
   INIT
================================================================ */
$(function () {
    loadBranches();
    loadDivisions();
    loadIssues();
    loadActionTypes();
    loadReporters();
    loadRepairList();

    // Cascade: ฝ่าย → หน่วยงาน
    $('#division_id').on('change', function () {
        const divId = $(this).val();
        loadDepartments(divId);
    });

    // โหลดเครื่องจักรตามสาขา + หน่วยงาน
    $('#btnLoadMachines, #branch_id, #department_id').on('change', function () {
        triggerLoadMachines();
    });
    $('#btnLoadMachines').on('click', function () {
        triggerLoadMachines();
    });

    // เลือกเครื่องจักร → แสดง info box
    $('#machine_id').on('change', function () {
        const mid = $(this).val();
        if (mid) loadMachineDetail(mid);
        else hideMachineInfo();
    });

    // Image preview (before)
    $('#image').on('change', function () {
        previewImage(this, '#preview-img', '#image-preview');
        const name = this.files[0]?.name || 'เลือกรูปภาพ...';
        $(this).next('.custom-file-label').text(name);
    });

    // Image preview (after)
    $('#image_after').on('change', function () {
        previewImage(this, '#preview-after-img', '#after-preview');
        const name = this.files[0]?.name || 'เลือกรูปภาพ...';
        $(this).next('.custom-file-label').text(name);
    });

    // Form submit
    $('#repairForm').on('submit', handleFormSubmit);

    // Complete form submit
    $('#completeForm').on('submit', handleCompleteSubmit);

    // job_status radio
    $('body').on('change', 'input[name="job_status"]', function () {
        const isOther = $(this).val() === 'other';
        $('#job_other_text').prop('disabled', !isOther);
        if (!isOther) $('#job_other_text').val('');
    });

    // Filter listeners
    $('#f_machine, #f_dept, #f_status, #f_priority').on('input change', debounce(loadRepairList, 350));
    $('#btnClearFilter').on('click', clearFilter);
    $('#btnRefreshList').on('click', loadRepairList);

    // Reset form → clear cascaded fields
    $('#repairForm').on('reset', function () {
        setTimeout(() => {
            $('#department_id').html('<option value="">-- เลือกหน่วยงาน --</option>');
            $('#machine_id').html('<option value="">-- เลือกเครื่องจักร --</option>');
            hideMachineInfo();
            $('#action_detail').hide().val('');
            $('#image-preview').hide();
        }, 50);
    });
});

/* ================================================================
   LOAD MASTER DATA
================================================================ */
function loadBranches() {
    $.getJSON(API, { action: 'branches' }, function (res) {
        if (!res.success) return;
        let html = '<option value="">-- เลือกสาขา --</option>';
        res.data.forEach(b => {
            html += `<option value="${b.id}">${b.code} - ${b.name}</option>`;
        });
        $('#branch_id').html(html);
    });
}

function loadDivisions() {
    $.getJSON(API, { action: 'divisions' }, function (res) {
        if (!res.success) return;
        let html = '<option value="">-- เลือกฝ่าย --</option>';
        res.data.forEach(d => {
            html += `<option value="${d.id}">${d.name}</option>`;
        });
        $('#division_id').html(html);
    });
}

function loadDepartments(divisionId) {
    const params = { action: 'departments' };
    if (divisionId) params.division_id = divisionId;

    $.getJSON(API, params, function (res) {
        if (!res.success) return;
        let html = '<option value="">-- เลือกหน่วยงาน --</option>';
        res.data.forEach(d => {
            html += `<option value="${d.id}">${d.name}</option>`;
        });
        $('#department_id').html(html);
        // After department changes, clear machines
        $('#machine_id').html('<option value="">-- เลือกเครื่องจักร --</option>');
        hideMachineInfo();
    });
}

function triggerLoadMachines() {
    const branchId = $('#branch_id').val();
    const deptId   = $('#department_id').val();
    if (!branchId && !deptId) return;

    const params = { action: 'machines' };
    if (branchId) params.branch_id = branchId;
    if (deptId)   params.department_id = deptId;

    $.getJSON(API, params, function (res) {
        if (!res.success) {
            showAlert('danger', 'ไม่สามารถโหลดเครื่องจักรได้');
            return;
        }
        let html = '<option value="">-- เลือกเครื่องจักร --</option>';
        if (res.data.length === 0) {
            html = '<option value="">ไม่พบเครื่องจักรในสาขา/หน่วยงานนี้</option>';
        } else {
            res.data.forEach(m => {
                const statusBadge = m.status === 'under_repair' ? ' (กำลังซ่อม)' : '';
                html += `<option value="${m.id}">${m.machine_code} — ${m.machine_name}${statusBadge}</option>`;
            });
        }
        $('#machine_id').html(html);
        hideMachineInfo();
    });
}

function loadMachineDetail(machineId) {
    $.getJSON(API, { action: 'machine_detail', machine_id: machineId }, function (res) {
        if (res.success) {
            const m = res.data;
            const statusLabel = {
                active: '<span class="badge badge-success">พร้อมใช้</span>',
                under_repair: '<span class="badge badge-warning">กำลังซ่อม</span>',
                inactive: '<span class="badge badge-secondary">ปิดใช้งาน</span>',
            }[m.status] || m.status;
            $('#machineInfoText').html(
                `<i class="fas fa-cog text-primary mr-1"></i>
                 <strong>${m.machine_code}</strong> — ${m.machine_name}
                 &nbsp;|&nbsp; ยี่ห้อ: ${m.brand || '-'} รุ่น: ${m.model || '-'}
                 &nbsp;|&nbsp; ตำแหน่ง: ${m.location || '-'}
                 &nbsp;|&nbsp; สถานะ: ${statusLabel}`
            );
            $('#machineInfoBox').show();
        } else {
            hideMachineInfo();
        }
    });
}

function hideMachineInfo() {
    $('#machineInfoBox').hide();
    $('#machineInfoText').html('');
}

function loadIssues() {
    $.getJSON(API, { action: 'issues' }, function (res) {
        if (!res.success) return;
        let html = '<option value="">-- เลือกอาการเสีย --</option>';
        // จัดกลุ่มตาม category
        const groups = {};
        res.data.forEach(i => {
            const cat = i.category_name || 'อื่นๆ';
            if (!groups[cat]) groups[cat] = [];
            groups[cat].push(i);
        });
        Object.keys(groups).forEach(cat => {
            html += `<optgroup label="${cat}">`;
            groups[cat].forEach(i => {
                html += `<option value="${i.id}">${i.name}</option>`;
            });
            html += '</optgroup>';
        });
        $('#issue_id').html(html);
    });
}

function loadActionTypes() {
    $.getJSON(API, { action: 'action_types' }, function (res) {
        if (!res.success) return;
        let html = '';
        res.data.forEach(a => {
            html += `
                <div class="custom-control custom-radio custom-control-inline mr-3 mb-1">
                    <input type="radio" class="custom-control-input action-radio"
                           id="at_${a.id}" name="_action_type_radio" value="${a.id}"
                           data-is-other="${a.is_other}" ${html === '' ? 'checked' : ''}>
                    <label class="custom-control-label" for="at_${a.id}">${a.name}</label>
                </div>`;
        });
        $('#actionTypeContainer').html(html);

        // Set default
        const first = res.data[0];
        if (first) $('#action_type_id').val(first.id);

        // Radio change listener
        $(document).on('change', '.action-radio', function () {
            const isOther = $(this).data('is-other') == 1;
            $('#action_type_id').val($(this).val());
            if (isOther) {
                $('#action_detail').show().focus();
            } else {
                $('#action_detail').hide().val('');
            }
        });
    });
}

function loadReporters() {
    $.getJSON(API, { action: 'reporters' }, function (res) {
        if (!res.success) return;
        let html = '<option value="">-- เลือกผู้แจ้ง --</option>';
        res.data.forEach(u => {
            html += `<option value="${u.id}">${u.full_name}</option>`;
        });
        $('#reported_by_id').html(html);
    });
}

function loadTechnicians() {
    $.getJSON(API, { action: 'technicians' }, function (res) {
        if (!res.success) return;
        let html = '<option value="">-- เลือกช่าง --</option>';
        res.data.forEach(u => {
            html += `<option value="${u.id}">${u.full_name}</option>`;
        });
        $('#handled_by_id').html(html);
    });
}

/* ================================================================
   FORM SUBMIT
================================================================ */
function handleFormSubmit(e) {
    e.preventDefault();
    const $btn = $(this).find('[type=submit]');

    // Basic validation
    const branchId  = $('#branch_id').val();
    const machineId = $('#machine_id').val();
    const issueId   = $('#issue_id').val();
    const issueDetail = $('#issue_detail').val().trim();
    const repById   = $('#reported_by_id').val();
    const repByName = $('#reported_by_name').val().trim();

    if (!branchId)  return showAlert('warning', 'กรุณาเลือกสาขา');
    if (!machineId) return showAlert('warning', 'กรุณาเลือกเครื่องจักร');
    if (!issueId && !issueDetail) return showAlert('warning', 'กรุณาเลือกหรือระบุอาการเสีย');
    if (!repById && !repByName)   return showAlert('warning', 'กรุณาระบุผู้แจ้ง');

    const fd = new FormData(this);
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...');

    $.ajax({
        url: SAVE_API,
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function (res) {
            if (res.success) {
                showAlert('success', `✅ บันทึกสำเร็จ เลขที่: <strong>${res.data.document_no}</strong>`);
                $('#repairForm')[0].reset();
                $('#department_id').html('<option value="">-- เลือกหน่วยงาน --</option>');
                $('#machine_id').html('<option value="">-- เลือกเครื่องจักร --</option>');
                hideMachineInfo();
                $('#image-preview').hide();
                loadRepairList();
            } else {
                showAlert('danger', '❌ ' + res.message);
            }
        },
        error: function () {
            showAlert('danger', '❌ เกิดข้อผิดพลาดในการเชื่อมต่อ');
        },
        complete: function () {
            $btn.prop('disabled', false).html('<i class="fas fa-save"></i> บันทึกใบแจ้งซ่อม');
        }
    });
}

/* ================================================================
   REPAIR LIST
================================================================ */
function loadRepairList() {
    const params = {
        machine:  $('#f_machine').val(),
        dept:     $('#f_dept').val(),
        status:   $('#f_status').val(),
        priority: $('#f_priority').val(),
    };

    $('#repair-list').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>');

    $.getJSON(LIST_API, params, function (res) {
        if (!res.success || res.data.length === 0) {
            $('#repair-list').html('<div class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x"></i><p class="mt-2">ไม่มีรายการ</p></div>');
            return;
        }
        renderRepairList(res.data);
    }).fail(function () {
        $('#repair-list').html('<div class="alert alert-danger">โหลดรายการไม่สำเร็จ</div>');
    });
}

function renderRepairList(data) {
    const statusMap = {
        10: { label: 'รออนุมัติ',    cls: 'secondary' },
        11: { label: 'ไม่อนุมัติ',   cls: 'danger'    },
        20: { label: 'รอดำเนินการ', cls: 'warning'   },
        30: { label: 'รออะไหล่',    cls: 'info'      },
        40: { label: 'เสร็จสิ้น',   cls: 'success'   },
        50: { label: 'ยกเลิก',      cls: 'dark'      },
    };

    let html = '<div class="table-responsive"><table class="table table-sm table-hover table-bordered mb-0">';
    html += `<thead class="thead-dark">
        <tr>
            <th style="width:130px">เลขที่</th>
            <th>เครื่องจักร</th>
            <th>หน่วยงาน</th>
            <th>อาการเสีย</th>
            <th style="width:90px">ความเร่งด่วน</th>
            <th style="width:110px">สถานะ</th>
            <th style="width:130px">วันที่แจ้ง</th>
            <th style="width:120px">จัดการ</th>
        </tr>
    </thead><tbody>`;

    data.forEach(r => {
        const st = statusMap[r.status] || { label: r.status, cls: 'secondary' };
        const prioLabel = r.priority === 'urgent'
            ? '<span class="badge badge-danger">ด่วน</span>'
            : '<span class="badge badge-success">ปกติ</span>';
        const machineTxt = `${r.machine_code || ''} ${r.machine_name || ''}`.trim() || '-';
        const issueTxt   = r.issue_name || r.issue_detail || '-';
        const deptTxt    = r.department_name || '-';
        const dateStr    = r.start_job ? r.start_job.substring(0, 16).replace('T', ' ') : '-';

        html += `<tr>
            <td><small class="font-weight-bold">${r.document_no}</small></td>
            <td>${machineTxt}</td>
            <td>${deptTxt}</td>
            <td>${issueTxt}</td>
            <td class="text-center">${prioLabel}</td>
            <td class="text-center"><span class="badge badge-${st.cls}">${st.label}</span></td>
            <td><small>${dateStr}</small></td>
            <td class="text-center">
                ${r.status == 20 ? `<button class="btn btn-xs btn-success btn-complete" data-id="${r.id}"><i class="fas fa-check"></i> เสร็จสิ้น</button>` : ''}
            </td>
        </tr>`;
    });

    html += '</tbody></table></div>';
    $('#repair-list').html(html);

    // Complete button
    $('.btn-complete').off('click').on('click', function () {
        const repairId = $(this).data('id');
        $('#complete_id').val(repairId);
        loadTechnicians();
        $('#mt_report').val('');
        $('#receiver_name').val('');
        $('#job_complete').prop('checked', true);
        $('#job_other_text').prop('disabled', true).val('');
        $('#after-preview').hide();
        $('#completeModal').modal('show');
    });
}

function clearFilter() {
    $('#f_machine, #f_dept').val('');
    $('#f_status, #f_priority').val('');
    loadRepairList();
}

/* ================================================================
   COMPLETE MODAL SUBMIT
================================================================ */
function handleCompleteSubmit(e) {
    e.preventDefault();
    const $btn = $(this).find('[type=submit]');
    const fd   = new FormData(this);

    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...');

    $.ajax({
        url: STATUS_API,
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function (res) {
            if (res.success) {
                $('#completeModal').modal('hide');
                showAlert('success', '✅ บันทึกการซ่อมเสร็จสิ้นแล้ว');
                loadRepairList();
            } else {
                showAlert('danger', '❌ ' + res.message);
            }
        },
        error: function () {
            showAlert('danger', '❌ เกิดข้อผิดพลาดในการเชื่อมต่อ');
        },
        complete: function () {
            $btn.prop('disabled', false).html('<i class="fas fa-check"></i> ยืนยันเสร็จสิ้น');
        }
    });
}

/* ================================================================
   HELPERS
================================================================ */
function previewImage(input, imgSelector, containerSelector) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            $(imgSelector).attr('src', e.target.result);
            $(containerSelector).show();
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function showAlert(type, message) {
    const $alert = $(`
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>`);
    // Insert after navbar
    $('nav.navbar').after($alert);
    if (type === 'success') {
        setTimeout(() => $alert.alert('close'), 5000);
    }
    $('html, body').animate({ scrollTop: 0 }, 300);
}

function debounce(fn, delay) {
    let timer;
    return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
    };
}
