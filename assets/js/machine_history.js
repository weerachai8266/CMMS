/**
 * Machine History Management
 * จัดการประวัติการซ่อม/PM/Calibration ของเครื่องจักร
 */

// ==================== Utility Functions ====================
/**
 * แปลง newline (\n) เป็น <br> สำหรับแสดงผลใน HTML
 */
function nl2br(text) {
    if (!text) return '-';
    return text.replace(/\n/g, '<br>');
}

// ==================== โหลดประวัติเครื่องจักร ====================
function loadMachineHistoryByCode(machineCode) {
    console.log('Loading history for machine:', machineCode);
    $.ajax({
        url: '../api/machine_history.php?machine_code=' + encodeURIComponent(machineCode),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('History response:', response);
            if (response.success) {
                displayMachineHistory(response.data);
            } else {
                console.warn('No history data:', response.message);
                displayMachineHistory([]);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading machine history:', error);
            console.error('Response:', xhr.responseText);
            displayMachineHistory([]);
        }
    });
}

// ==================== แสดงประวัติในตาราง ====================
function displayMachineHistory(historyData) {
    const tbody = $('#repair_history_body');
    let html = '';
    
    if (!historyData || historyData.length === 0) {
        html = '<tr><td colspan="14" class="text-center">ไม่มีประวัติการซ่อม</td></tr>';
        tbody.html(html);
        $('#repair_history_card').show(); // แสดงการ์ดแม้ไม่มีข้อมูล
        return;
    }
    
    historyData.forEach(function(item, index) {
        html += '<tr>';
        html += '<td class="text-center">' + (index + 1) + '</td>';
        html += '<td class="text-center">' + formatDateDMY(item.work_date) + '</td>';
        html += '<td>' + (item.document_no || '-') + '</td>';
        html += '<td>' + nl2br(item.issue_description) + '</td>';
        html += '<td>' + nl2br(item.solution_description) + '</td>';
        html += '<td style="min-width: 300px;">' + nl2br(item.parts_used) + '</td>';
        html += '<td class="text-right">' + formatCurrency(item.total_cost) + '</td>';
        html += '<td class="text-center">' + (item.work_hours || '0') + '</td>';
        html += '<td class="text-center">' + (item.downtime_hours || '0') + '</td>';
        // html += '<td class="text-center">' + formatDateDMY(item.start_date) + '</td>';
        // html += '<td class="text-center">' + formatDateDMY(item.completed_date) + '</td>';
        html += '<td>' + (item.handled_by || '-') + '</td>';
        html += '<td class="text-center">';
        html += '<button class="btn btn-sm btn-primary" onclick="viewHistoryDetail(' + item.id + ')" title="ดู"><i class="fas fa-eye"></i></button> ';
        html += '<button class="btn btn-sm btn-warning" onclick="editHistory(' + item.id + ')" title="แก้ไข"><i class="fas fa-edit"></i></button> ';
        html += '<button class="btn btn-sm btn-danger" onclick="deleteHistory(' + item.id + ')" title="ลบ"><i class="fas fa-trash"></i></button>';
        html += '</td>';
        html += '</tr>';
    });
    
    tbody.html(html);
    $('#repair_history_card').show(); // แสดงการ์ดหลังจากโหลดข้อมูลเสร็จ
}

// ==================== เปิดโมดอลเพิ่มประวัติ ====================
function openAddHistoryModal(machineCode, machineName) {
    $('#historyForm')[0].reset();
    $('#history_id').val('');
    $('#history_machine_code').val(machineCode);
    $('#history_machine_name').val(machineName);
    $('#history_work_date').val(new Date().toISOString().split('T')[0]);
    $('#historyModalTitle').html('<i class="fas fa-plus"></i> เพิ่มประวัติการซ่อม');
    $('#historyModal').modal('show');
}

// ==================== เปิดโมดอลเพิ่มประวัติแบบรวดเร็ว (ไม่ต้องเลือกเครื่อง) ====================
function openQuickAddHistory() {
    $('#historyForm')[0].reset();
    $('#history_id').val('');
    $('#history_machine_code').prop('readonly', false); // ให้แก้ไขได้
    $('#history_machine_name').prop('readonly', true); // แต่ชื่อ auto-fill
    $('#history_work_date').val(new Date().toISOString().split('T')[0]);
    $('#historyModalTitle').html('<i class="fas fa-plus-circle"></i> เพิ่มประวัติ PM / Calibration');
    
    // โหลดรายการเครื่องจักรลง datalist
    loadMachineCodeDatalist();
    
    $('#historyModal').modal('show');
}

// ==================== โหลดรายการเครื่องจักรลง datalist ====================
function loadMachineCodeDatalist() {
    $.ajax({
        url: '../api/machines.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let options = '';
                response.data.forEach(function(machine) {
                    options += '<option value="' + machine.machine_code + '" data-name="' + machine.machine_name + '">';
                });
                $('#history_machine_code_datalist').html(options);
            }
        },
        error: function() {
            console.error('Error loading machine codes');
        }
    });
}

// ==================== แสดงชื่อเครื่องจักรอัตโนมัติ ====================
function fillMachineNameFromCode(machineCode) {
    if (!machineCode) {
        $('#history_machine_name').val('');
        return;
    }
    
    // ค้นหาจาก machines API
    $.ajax({
        url: '../api/machines.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const machine = response.data.find(m => m.machine_code === machineCode);
                if (machine) {
                    $('#history_machine_name').val(machine.machine_name);
                } else {
                    $('#history_machine_name').val('');
                }
            }
        },
        error: function() {
            console.error('Error finding machine name');
        }
    });
}

// ==================== คำนวณเวลาปฏิบัติงาน ====================
function calculateWorkDuration() {
    const startTime = $('#history_start_time').val();
    const endTime = $('#history_end_time').val();
    
    if (!startTime || !endTime) {
        $('#history_work_hours').val(0);
        return;
    }
    
    const start = new Date(startTime);
    const end = new Date(endTime);
    
    if (end <= start) {
        alert('เวลาเสร็จต้องมากกว่าเวลาเริ่ม');
        $('#history_work_hours').val(0);
        return;
    }
    
    // คำนวณชั่วโมง (ปัดเศษ 2 ตำแหน่ง)
    const diffMs = end - start;
    const diffHours = diffMs / (1000 * 60 * 60);
    $('#history_work_hours').val(diffHours.toFixed(2));
}

// ==================== ดูรายละเอียด ====================
function viewHistoryDetail(id) {
    $.ajax({
        url: '../api/machine_history.php?id=' + id,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showHistoryDetailModal(response.data);
            } else {
                alert('ไม่พบข้อมูล');
            }
        },
        error: function() {
            alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
        }
    });
}

function showHistoryDetailModal(data) {
    let html = '<div class="row">';
    html += '<div class="col-md-6"><strong>รหัสเครื่องจักร:</strong> ' + (data.machine_code || '-') + '</div>';
    html += '<div class="col-md-6"><strong>ชื่อเครื่องจักร:</strong> ' + (data.machine_name || '-') + '</div>';
    html += '</div><hr>';
    html += '<div class="row">';
    html += '<div class="col-md-6"><strong>เลขที่เอกสาร:</strong> ' + (data.document_no || '-') + '</div>';
    html += '<div class="col-md-6"><strong>วันที่ทำงาน:</strong> ' + formatDateDMY(data.work_date) + '</div>';
    html += '</div><hr>';
    html += '<div class="row">';
    html += '<div class="col-12"><strong>อาการเสีย/ปัญหา:</strong><br>' + nl2br(data.issue_description) + '</div>';
    html += '</div><hr>';
    html += '<div class="row">';
    html += '<div class="col-12"><strong>วิธีแก้ไข:</strong><br>' + nl2br(data.solution_description) + '</div>';
    html += '</div><hr>';
    html += '<div class="row">';
    html += '<div class="col-12"><strong>อะไหล่ที่ใช้:</strong><br>' + nl2br(data.parts_used) + '</div>';
    html += '</div><hr>';
    html += '<div class="row">';
    html += '<div class="col-md-3"><strong>เวลาทำงาน:</strong> ' + (data.work_hours || '0') + ' ชม.</div>';
    html += '<div class="col-md-3"><strong>เวลาหยุดเครื่อง:</strong> ' + (data.downtime_hours || '0') + ' ชม.</div>';
    html += '<div class="col-md-6"><strong>ค่าใช้จ่ายรวม:</strong> ' + formatCurrency(data.total_cost) + '</div>';
    html += '</div><hr>';
    html += '<div class="row">';
    html += '<div class="col-md-4"><strong>ผู้แจ้ง:</strong> ' + (data.reported_by || '-') + '</div>';
    html += '<div class="col-md-4"><strong>ผู้รับผิดชอบ:</strong> ' + (data.handled_by || '-') + '</div>';
    html += '<div class="col-md-4"><strong>สถานะ:</strong> ' + (data.status || '-') + '</div>';
    html += '</div>';
    if (data.note) {
        html += '<hr><div class="row"><div class="col-12"><strong>หมายเหตุ:</strong><br>' + nl2br(data.note) + '</div></div>';
    }
    
    $('#historyDetailContent').html(html);
    $('#historyDetailModal').modal('show');
}

// ==================== แก้ไขประวัติ ====================
function editHistory(id) {
    $.ajax({
        url: '../api/machine_history.php?id=' + id,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                fillHistoryForm(response.data);
            } else {
                alert('ไม่พบข้อมูล');
            }
        },
        error: function() {
            alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
        }
    });
}

function fillHistoryForm(data) {
    $('#history_id').val(data.id);
    $('#history_machine_code').val(data.machine_code);
    $('#history_machine_name').val(data.machine_name);
    $('#history_document_no').val(data.document_no);
    
    // ดึง work_type จาก document_no (ถ้ามี)
    if (data.document_no) {
        if (data.document_no.startsWith('PM')) $('#history_work_type').val('PM');
        else if (data.document_no.startsWith('CAL')) $('#history_work_type').val('CAL');
        else if (data.document_no.startsWith('OVH')) $('#history_work_type').val('OVH');
        else if (data.document_no.startsWith('INS')) $('#history_work_type').val('INS');
    }
    
    $('#history_work_date').val(data.work_date);
    $('#history_start_date').val(data.start_date);
    $('#history_completed_date').val(data.completed_date);
    $('#history_issue').val(data.issue_description);
    $('#history_solution').val(data.solution_description);
    $('#history_parts').val(data.parts_used);
    $('#history_work_hours').val(data.work_hours);
    $('#history_downtime_hours').val(data.downtime_hours);
    $('#history_labor_cost').val(data.labor_cost);
    $('#history_parts_cost').val(data.parts_cost);
    $('#history_other_cost').val(data.other_cost);
    $('#history_total_cost').val(data.total_cost);
    $('#history_reported_by').val(data.reported_by);
    $('#history_handled_by').val(data.handled_by);
    $('#history_status').val(data.status);
    $('#history_note').val(data.note);
    
    $('#historyModalTitle').html('<i class="fas fa-edit"></i> แก้ไขประวัติการซ่อม');
    $('#historyModal').modal('show');
}

// ==================== บันทึกประวัติ ====================
function saveHistory(event) {
    event.preventDefault();
    
    const id = $('#history_id').val();
    const data = {
        id: id || undefined,
        machine_code: $('#history_machine_code').val(),
        machine_name: $('#history_machine_name').val(),
        work_type: $('#history_work_type').val(), // ประเภทงาน (PM, CAL, REP, INS)
        work_date: $('#history_work_date').val(),
        start_date: $('#history_start_date').val() || null,
        completed_date: $('#history_completed_date').val() || null,
        issue_description: $('#history_issue').val(),
        solution_description: $('#history_solution').val(),
        parts_used: $('#history_parts').val(),
        work_hours: parseFloat($('#history_work_hours').val()) || 0,
        downtime_hours: parseFloat($('#history_downtime_hours').val()) || 0,
        labor_cost: parseFloat($('#history_labor_cost').val()) || 0,
        parts_cost: parseFloat($('#history_parts_cost').val()) || 0,
        other_cost: parseFloat($('#history_other_cost').val()) || 0,
        total_cost: parseFloat($('#history_total_cost').val()) || 0,
        reported_by: $('#history_reported_by').val(),
        handled_by: $('#history_handled_by').val(),
        status: $('#history_status').val(),
        note: $('#history_note').val()
    };
    
    const method = id ? 'PUT' : 'POST';
    
    $.ajax({
        url: '../api/machine_history.php',
        method: method,
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                let message = id ? 'อัปเดตข้อมูลสำเร็จ' : 'บันทึกข้อมูลสำเร็จ';
                // แสดงเลขที่เอกสารถ้าเป็นการสร้างใหม่
                if (!id && response.data && response.data.document_no) {
                    message += '\nเลขที่เอกสาร: ' + response.data.document_no;
                }
                alert(message);
                $('#historyModal').modal('hide');
                // โหลดประวัติใหม่
                loadMachineHistoryByCode(data.machine_code);
            } else {
                alert('เกิดข้อผิดพลาด: ' + response.message);
            }
        },
        error: function() {
            alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
        }
    });
}

// ==================== ลบประวัติ ====================
function deleteHistory(id) {
    if (!confirm('คุณต้องการลบประวัติการซ่อมนี้หรือไม่?')) {
        return;
    }
    
    $.ajax({
        url: '../api/machine_history.php',
        method: 'DELETE',
        contentType: 'application/json',
        data: JSON.stringify({ id: id }),
        success: function(response) {
            if (response.success) {
                alert('ลบข้อมูลสำเร็จ');
                // โหลดประวัติใหม่
                const machineCode = $('#history_machine_select_input').val();
                if (machineCode) {
                    loadMachineHistoryByCode(machineCode);
                }
            } else {
                alert('เกิดข้อผิดพลาด: ' + response.message);
            }
        },
        error: function() {
            alert('เกิดข้อผิดพลาดในการลบข้อมูล');
        }
    });
}

// ==================== คำนวณค่าใช้จ่ายรวม ====================
function calculateTotalCost() {
    const laborCost = parseFloat($('#history_labor_cost').val()) || 0;
    const partsCost = parseFloat($('#history_parts_cost').val()) || 0;
    const otherCost = parseFloat($('#history_other_cost').val()) || 0;
    const total = laborCost + partsCost + otherCost;
    $('#history_total_cost').val(total.toFixed(2));
}

// ==================== Format ตัวเลข ====================
function formatCurrency(amount) {
    if (!amount || amount == 0) return '-';
    return parseFloat(amount).toLocaleString('th-TH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + ' บาท';
}

// ==================== Export to Excel ====================
function exportMachineHistory() {
    const machineCode = $('#history_machine_select_input').val().trim();
    
    if (!machineCode) {
        alert('กรุณาเลือกเครื่องจักรก่อน');
        return;
    }
    
    // เปิดหน้าต่างใหม่เพื่อ download Excel
    window.open('../api/export_machine_history.php?machine_code=' + encodeURIComponent(machineCode), '_blank');
}
