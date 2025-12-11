
$(document).ready(function() {
    loadMachines();
    $('#machineForm').on('submit', function(e) {
        e.preventDefault();
        saveMachine();
    });
    
    // ตั้งค่า event handlers สำหรับการกรอง (ใช้ on เพื่อไม่ให้ซ้ำซ้อน)
    $('#filter_type').off('change').on('change', filterMachines);
    $('#filter_code, #filter_brand, #filter_model').off('keyup').on('keyup', filterMachines);
    $('#filter_branch').off('change').on('change', filterMachines);
    
    $('#btn_clear_filter').on('click', function() {
        $('#filter_type, #filter_code, #filter_brand, #filter_model').val('');
        $('#filter_branch').val('');
        filterMachines();
    });
});

// Accessibility: ensure focus management for modal to avoid aria-hidden warnings
// Blur the element that triggered the modal so it doesn't retain focus while modal is hidden
$(document).on('show.bs.modal', '#machineModal', function(e) {
    try {
        if (e && e.relatedTarget) {
            $(e.relatedTarget).blur();
        }
        // set aria-hidden false when showing
        $(this).attr('aria-hidden', 'false');
        // If the modal was opened for "add" (not via an edit button), clear form and load branches
        try {
                if (e && e.relatedTarget && !$(e.relatedTarget).hasClass('edit-machine-btn')) {
                clearForm();
                loadBranches('#branch_select', '-- เลือกสาขา --');
            }
        } catch (innerErr) {
            console.warn('Could not auto-load branches for add-modal:', innerErr);
        }
    } catch (err) {
        console.error('Error in show.bs.modal handler:', err);
    }
});

// After modal is fully shown, move focus to the first focusable element inside
$(document).on('shown.bs.modal', '#machineModal', function() {
    var $modal = $(this);
    var $focusable = $modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')
        .filter(':visible').first();
    if ($focusable && $focusable.length) {
        $focusable.focus();
    }
});

// When modal is hidden, restore aria-hidden and try to return focus to a logical place
$(document).on('hidden.bs.modal', '#machineModal', function() {
    $(this).attr('aria-hidden', 'true');
    // optionally move focus back to the "Add" button
    var $trigger = $('button[data-target="#machineModal"]').first();
    if ($trigger && $trigger.length) $trigger.focus();
});

// โหลดรายการเครื่องจักร
function loadMachines() {
    // โหลดสาขาสำหรับ filter dropdown (เฉพาะครั้งแรก)
    if ($('#filter_branch option').length <= 1) {
        loadBranches('#filter_branch', 'ทั้งหมด');
    }
    
    $.ajax({
        url: '../api/machines.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // เรียงตามประเภทก่อน แล้วตามรหัส
                response.data.sort(function(a, b) {
                    if (a.machine_type !== b.machine_type) {
                        return a.machine_type.localeCompare(b.machine_type);
                    }
                    return a.machine_code.localeCompare(b.machine_code);
                });
                displayMachines(response.data);
            } else {
                $('#machineList').html('<div class="alert alert-warning">ไม่พบข้อมูล</div>');
            }
        },
        error: function() {
            $('#machineList').html('<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>');
        }
    });
}

// กรองข้อมูล
function filterMachines() {
    const filterType = $('#filter_type').val().toLowerCase();
    const filterCode = $('#filter_code').val().toLowerCase();
    const filterBrand = $('#filter_brand').val().toLowerCase();
    const filterModel = $('#filter_model').val().toLowerCase();
    const filterBranch = $('#filter_branch').val(); // name (string or empty)

    $('#machineList tbody tr').each(function() {
        const row = $(this);
        
        // ใช้ data attribute แทนการอ้างอิง td:eq() เพราะตำแหน่งอาจเปลี่ยนเมื่อมี checkbox
        const type = (row.data('type') || '').toString().toLowerCase();
        const code = (row.data('machine-code') || '').toString().toLowerCase();
        const number = (row.data('number') || '').toString().toLowerCase();
        const brand = (row.data('brand') || '').toString().toLowerCase();
        const model = (row.data('model') || '').toString().toLowerCase();
        const branchName = row.data('branch') || '';

        const matchType = !filterType || type.includes(filterType);
        const matchCode = !filterCode || code.includes(filterCode) || number.includes(filterCode);
        const matchBrand = !filterBrand || brand.includes(filterBrand);
        const matchModel = !filterModel || model.includes(filterModel);
        const matchBranch = !filterBranch || (branchName == filterBranch);

        if (matchType && matchCode && matchBrand && matchModel && matchBranch) {
            row.show();
        } else {
            row.hide();
        }
    });
}

// แสดงรายการเครื่องจักร
function displayMachines(machines) {
    if (machines.length === 0) {
        $('#machineList').html('<div class="alert alert-info">ยังไม่มีข้อมูลเครื่องจักร</div>');
        return;
    }

    let html = `
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th class="select-mode-only text-center" style="width: 40px; display: none;">
                            <input type="checkbox" id="select_all_machines" onchange="toggleSelectAll()">
                        </th>
                        <th class="text-center" style="width: 60px; display: none;">ประเภท</th>
                        <th class="text-center" style="width: 80px;">สาขา</th>
                        <th class="text-center" style="width: 100px;">รหัส</th>
                        <th>ชื่อเครื่องจักร</th>
                        <th class="text-center" style="width: 100px;">หมายเลข</th>
                        <th class="text-center" style="width: 100px;">ยี่ห้อ</th>
                        <th class="text-center" style="width: 100px;">รุ่น</th>
                        <th class="text-center" style="width: 120px;">ผู้ผลิต</th>
                        <th class="text-center" style="width: 120px;">ผู้จำหน่าย</th>
                        <th class="text-center" style="width: 100px;">หน่วยงาน</th>
                        <th class="text-center" style="width: 100px;">พื้นที่</th>
                        <th class="text-center" style="width: 120px;">เบอร์ติดต่อ</th>
                        <th class="text-center" style="width: 80px;">สถานะ</th>
                        <th class="text-center" style="width: 120px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
    `;

    machines.forEach((machine, index) => {
        let statusBadge = '';
        switch(machine.machine_status) {
            case 'active':
                statusBadge = '<span class="badge badge-success">ใช้งาน</span>';
                break;
            case 'maintenance':
                statusBadge = '<span class="badge badge-warning">ซ่อมบำรุง</span>';
                break;
            case 'broken':
                statusBadge = '<span class="badge badge-danger">ชำรุด</span>';
                break;
            case 'retired':
                statusBadge = '<span class="badge badge-secondary">เลิกใช้งาน</span>';
                break;
            default:
                statusBadge = '<span class="badge badge-info">ไม่ระบุ</span>';
        }
        html += `
            <tr data-branch="${machine.branch || ''}" 
                data-machine-code="${machine.machine_code}"
                data-type="${machine.machine_type || ''}"
                data-number="${machine.machine_number || ''}"
                data-brand="${machine.brand || ''}"
                data-model="${machine.model || ''}">
                <td class="text-center select-mode-only" style="display: none;">
                    <input type="checkbox" class="machine-checkbox" value="${machine.machine_code}">
                </td>
                <td class="text-center" style="display: none;">${machine.machine_type}</td>
                <td class="text-center">${machine.branch}</td>
                <td><strong>${machine.machine_code}</strong></td>
                <td>${machine.machine_name}</td>
                <td>${machine.machine_number || '-'}</td>
                <td>${machine.brand || '-'}</td>
                <td>${machine.model || '-'}</td>
                <td>${machine.manufacturer || '-'}</td>
                <td>${machine.supplier || '-'}</td>
                <td>${machine.responsible_dept || '-'}</td>
                <td>${machine.work_area || '-'}</td>
                <td>${machine.contact_phone || '-'}</td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-center" style="white-space: nowrap;">
                    <button class="btn btn-sm btn-primary" onclick="viewMachine(${machine.id})" title="ดูรายละเอียด">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning edit-machine-btn" data-toggle="modal" data-target="#machineModal" onclick="editMachine(${machine.id})" title="แก้ไข">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteMachine(${machine.id}, '${machine.machine_code}')" title="ลบ">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
        </div>
    `;

    $('#machineList').html(html);
}

// ล้างฟอร์ม
function clearForm() {
    $('#machineForm')[0].reset();
    $('#machine_id').val('');
    $('#modalTitle').html('<i class="fas fa-plus"></i> เพิ่มเครื่องจักรใหม่');
}

// บันทึกข้อมูล
function saveMachine() {
    // แปลงเป็นตัวพิมพ์ใหญ่ก่อนส่ง
    $('#machine_type').val($('#machine_type').val().toUpperCase());
    $('#machine_code').val($('#machine_code').val().toUpperCase());
    $('#machine_number').val($('#machine_number').val().toUpperCase());
    $('#machine_name').val($('#machine_name').val().toUpperCase());
    // branch is an ID (numeric); do not uppercase it
    $('#brand').val($('#brand').val());
    $('#model').val($('#model').val().toUpperCase());
    $('#horsepower').val($('#horsepower').val());
    $('#weight').val($('#weight').val().toUpperCase());
    $('#responsible_dept').val($('#responsible_dept').val().toUpperCase());
    $('#work_area').val($('#work_area').val());
    $('#manufacturer').val($('#manufacturer').val());
    $('#supplier').val($('#supplier').val());
    $('#note').val($('#note').val());
    
    const formData = $('#machineForm').serialize();
    const id = $('#machine_id').val();
    const method = id ? 'PUT' : 'POST';

    $.ajax({
        url: '../api/machines.php',
        method: method,
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(response.message);
                $('#machineModal').modal('hide');
                clearForm();
                loadMachines();
            } else {
                alert('ข้อผิดพลาด: ' + response.message);
            }
        },
        error: function() {
            alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
        }
    });
}

// ดูรายละเอียดเครื่องจักร
function viewMachine(id) {
    $.ajax({
        url: '../api/machines.php?id=' + id,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const m = response.data;
                let statusText = '';
                switch(m.machine_status) {
                    case 'active': statusText = 'ใช้งาน'; break;
                    case 'maintenance': statusText = 'ซ่อมบำรุง'; break;
                    case 'broken': statusText = 'ชำรุด'; break;
                    case 'retired': statusText = 'เลิกใช้งาน'; break;
                }
                
                const details = `
                    <table class="table table-bordered">
                        <tr><th width="200">ประเภท:</th><td>${m.machine_type}</td></tr>
                        <tr><th>สาขา:</th><td><strong>${m.branch}</strong></td></tr>
                        <tr><th>รหัส:</th><td><strong>${m.machine_code}</strong></td></tr>
                        <tr><th>หมายเลขเครื่อง:</th><td>${m.machine_number || '-'}</td></tr>
                        <tr><th>ชื่อเครื่องจักร:</th><td>${m.machine_name}</td></tr>
                        <tr><th>ยี่ห้อ:</th><td>${m.brand || '-'}</td></tr>
                        <tr><th>รุ่น:</th><td>${m.model || '-'}</td></tr>
                        <tr><th>ขนาด:</th><td>${m.horsepower || '-'}</td></tr>
                        <tr><th>น้ำหนัก:</th><td>${m.weight || '-'}</td></tr>
                        <tr><th>หน่วยงานที่รับผิดชอบ:</th><td>${m.responsible_dept || '-'}</td></tr>
                        <tr><th>พื้นที่ใช้งาน:</th><td>${m.work_area || '-'}</td></tr>
                        <tr><th>บริษัทผู้ผลิต:</th><td>${m.manufacturer || '-'}</td></tr>
                        <tr><th>ผู้แทนจำหน่าย:</th><td>${m.supplier || '-'}</td></tr>
                        <tr><th>ราคาซื้อ:</th><td>${m.purchase_price ? parseFloat(m.purchase_price).toLocaleString() + ' บาท' : '-'}</td></tr>
                        <tr><th>เบอร์โทรติดต่อ:</th><td>${m.contact_phone || '-'}</td></tr>
                        <tr><th>วันที่ซื้อ:</th><td>${m.purchase_date || '-'}</td></tr>
                        <tr><th>วันที่เริ่มใช้งาน:</th><td>${m.start_date || '-'}</td></tr>
                        <tr><th>วันที่ขึ้นทะเบียน:</th><td>${m.register_date || '-'}</td></tr>
                        <tr><th>สถานะ:</th><td>${statusText}</td></tr>
                        <tr><th>หมายเหตุ:</th><td>${m.note || '-'}</td></tr>
                    </table>
                `;
                
                const modal = `
                    <div class="modal fade" id="viewMachineModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-info text-white">
                                    <h5 class="modal-title"><i class="fas fa-info-circle"></i> รายละเอียดเครื่องจักร</h5>
                                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                                </div>
                                <div class="modal-body">${details}</div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">ปิด</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                $('#viewMachineModal').remove();
                $('body').append(modal);
                $('#viewMachineModal').modal('show');
            }
        }
    });
}

// แก้ไขเครื่องจักร
function editMachine(id) {
    // ต้อง clearForm ก่อน เพื่อ reset modal ทุกครั้ง
    // clearForm();
    $.ajax({
        url: '../api/machines.php?id=' + id,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const machine = response.data;
                // โหลดสาขาใหม่ก่อน set ค่า (callback หลังโหลดเสร็จ)
                loadBranches('#branch_select', '-- เลือกสาขา --', function() {
                    $('#machine_id').val(machine.id);
                    $('#machine_type').val(machine.machine_type);
                    $('#machine_code').val(machine.machine_code);
                    $('#machine_number').val(machine.machine_number);
                    $('#machine_name').val(machine.machine_name);
                    // set branch by name (user wants names)
                    $('#branch_select').val(machine.branch || '');
                    $('#brand').val(machine.brand);
                    $('#model').val(machine.model);
                    $('#horsepower').val(machine.horsepower);
                    $('#weight').val(machine.weight);
                    // $('#quantity').val(machine.quantity);
                    $('#responsible_dept').val(machine.responsible_dept);
                    $('#work_area').val(machine.work_area);
                    $('#manufacturer').val(machine.manufacturer);
                    $('#supplier').val(machine.supplier);
                    $('#purchase_price').val(machine.purchase_price);
                    $('#contact_phone').val(machine.contact_phone);
                    $('#purchase_date').val(machine.purchase_date);
                    $('#start_date').val(machine.start_date);
                    $('#register_date').val(machine.register_date);
                    $('#machine_status').val(machine.machine_status);
                    $('#unit').val(machine.unit);
                    $('#note').val(machine.note);
                    $('#modalTitle').html('<i class="fas fa-edit"></i> แก้ไขข้อมูลเครื่องจักร');
                    $('#machineModal').modal('show');
                });
            }
        },
        error: function() {
            alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
        }
    });
}

// ลบเครื่องจักร
function deleteMachine(id, code) {
    if (confirm(`ต้องการลบเครื่องจักร "${code}" ใช่หรือไม่?`)) {
        $.ajax({
            url: '../api/machines.php',
            method: 'DELETE',
            data: 'id=' + id,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    loadMachines();
                } else {
                    alert('ข้อผิดพลาด: ' + response.message);
                }
            },
            error: function() {
                alert('เกิดข้อผิดพลาดในการลบข้อมูล');
            }
        });
    }
}

// Toggle select mode
let selectMode = false;
function toggleSelectMode() {
    selectMode = !selectMode;
    
    if (selectMode) {
        $('.select-mode-only').show();
        $('#btn_toggle_select').html('<i class="fas fa-times"></i> ยกเลิกเลือก');
        $('#btn_toggle_select').removeClass('btn-info').addClass('btn-secondary');
        $('#btn_export_selected').show();
        updateSelectedCount();
    } else {
        $('.select-mode-only').hide();
        $('#btn_toggle_select').html('<i class="fas fa-check-square"></i> เลือกหลายเครื่อง');
        $('#btn_toggle_select').removeClass('btn-secondary').addClass('btn-info');
        $('#btn_export_selected').hide();
        $('.machine-checkbox').prop('checked', false);
        $('#select_all_machines').prop('checked', false);
    }
}

// Toggle select all visible machines
function toggleSelectAll() {
    const isChecked = $('#select_all_machines').is(':checked');
    $('#machineList tbody tr:visible .machine-checkbox').prop('checked', isChecked);
    updateSelectedCount();
}

// Update selected count
function updateSelectedCount() {
    const count = $('.machine-checkbox:checked').length;
    $('#selected_count').text(count);
}

// Listen to checkbox changes
$(document).on('change', '.machine-checkbox', function() {
    updateSelectedCount();
});

// Export selected machines
function exportSelectedMachines() {
    const selectedCodes = [];
    $('.machine-checkbox:checked').each(function() {
        selectedCodes.push($(this).val());
    });
    
    if (selectedCodes.length === 0) {
        alert('กรุณาเลือกเครื่องจักรอย่างน้อย 1 เครื่อง');
        return;
    }
    
    // สร้าง URL สำหรับ export
    const params = selectedCodes.map(code => 'machine_code[]=' + encodeURIComponent(code)).join('&');
    const url = '../api/export_machine_history.php?' + params;
    
    // เปิดในหน้าต่างใหม่เพื่อ download
    window.open(url, '_blank');
}

// Export to Excel
function exportToExcel() {
    window.open('../api/export_machines.php', '_blank');
}
