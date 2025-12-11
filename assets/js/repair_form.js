/**
 * ระบบแจ้งซ่อมเครื่องจักร - Main JavaScript
 * Version: 2.0
 */

$(document).ready(function() {
    
    // โหลดรายการเครื่องจักร
    let machinesData = []; // เก็บข้อมูลเครื่องจักรทั้งหมด
    loadMachineList();
    
    // เมื่อพิมพ์หรือเลือกเครื่องจักร ให้แสดงชื่อเครื่องจักร
    $('#machine_number').on('input change', function() {
        const machineCode = $(this).val().trim().toUpperCase();
        
        // หาชื่อเครื่องจักรจาก machinesData
        const machine = machinesData.find(m => m.machine_code === machineCode);
        
        if (machine) {
            $('#machine_name').val(machine.machine_name);
        } else {
            $('#machine_name').val('');
        }
    });
    
    // แปลงเป็นตัวพิมพ์ใหญ่อัตโนมัติ
    $('#machine_number').on('blur', function() {
        $(this).val($(this).val().trim().toUpperCase());
    });
    
    /**
     * Load machine list for datalist
     */
    function loadMachineList() {
        $.ajax({
            url: '../api/machines.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    machinesData = response.data;
                    
                    // เรียงตามประเภทและรหัส
                    machinesData.sort((a, b) => {
                        if (a.machine_type !== b.machine_type) {
                            return a.machine_type.localeCompare(b.machine_type);
                        }
                        return a.machine_code.localeCompare(b.machine_code);
                    });
                    
                    // สร้าง datalist options
                    let options = '';
                    machinesData.forEach(machine => {
                        options += `<option value="${machine.machine_code}" label="${machine.machine_name}">`;
                    });
                    
                    $('#machine_list').html(options);
                }
            },
            error: function() {
                console.error('ไม่สามารถโหลดรายการเครื่องจักรได้');
            }
        });
    }
    
    /**
     * Load repair data from server
     */
    function loadRepairData() {
        $('#repair-list').html(`
            <div class="loading">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">กำลังโหลดข้อมูล...</p>
            </div>
        `);
        
        // Get filter values
        const filterData = {
            department: $('#filter_department').val() || '',
            machine: $('#filter_machine').val() || '',
            reported_by: $('#filter_reported_by').val() || '',
            status: $('#filter_status').val() || ''
        };
        
        $.ajax({
            url: '../api/display.php',
            type: 'GET',
            data: filterData,
            success: function(data) {
                $('#repair-list').html(data);
            },
            error: function(xhr, status, error) {
                $('#repair-list').html(`
                    <div class="alert alert-danger">
                        <strong>เกิดข้อผิดพลาด!</strong> ไม่สามารถโหลดข้อมูลได้
                    </div>
                `);
                console.error('Error loading data:', error);
            }
        });
    }

    /**
     * Show success message
     */
    function showMessage(message, type = 'success') {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show success-message" role="alert">
                <strong>${type === 'success' ? '✓' : '✗'}</strong> ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;
        
        $('body').append(alertHtml);
        
        // Auto hide after 3 seconds
        setTimeout(function() {
            $('.success-message').fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

    /**
     * Image preview handler
     */
    $('#image').on('change', function(e) {
        const file = e.target.files[0];
        const $label = $('.custom-file-label');
        
        if (file) {
            // Update label with filename
            $label.text(file.name);
            
            // Check file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                showMessage('ขนาดไฟล์ใหญ่เกิน 5MB', 'error');
                $(this).val('');
                $label.text('เลือกรูปภาพ...');
                $('#image-preview').hide();
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#preview-img').attr('src', e.target.result);
                $('#image-preview').show();
            };
            reader.readAsDataURL(file);
        } else {
            $label.text('เลือกรูปภาพ...');
            $('#image-preview').hide();
        }
    });

    /**
     * Form submission handler
     */
    $('#repairForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }
        
        // Disable submit button
        const $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> กำลังบันทึก...');
        
        // Create FormData for file upload
        const formData = new FormData(this);
        
        $.ajax({
            url: '../api/save_repair.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showMessage(response.message, 'success');
                    $('#repairForm')[0].reset();
                    $('#repairForm').removeClass('was-validated');
                    $('.custom-file-label').text('เลือกรูปภาพ...');
                    $('#image-preview').hide();
                    loadRepairData();
                } else {
                    showMessage(response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                showMessage('เกิดข้อผิดพลาดในการบันทึกข้อมูล', 'error');
                console.error('Save error:', error);
            },
            complete: function() {
                // Re-enable submit button
                $submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> บันทึก');
            }
        });
    });

    /**
     * Image after preview handler
     */
    $('#image_after').on('change', function(e) {
        const file = e.target.files[0];
        const $label = $('#image_after').siblings('.custom-file-label');
        
        if (file) {
            $label.text(file.name);
            
            if (file.size > 5 * 1024 * 1024) {
                showMessage('ขนาดไฟล์ใหญ่เกิน 5MB', 'error');
                $(this).val('');
                $label.text('เลือกรูปภาพ...');
                $('#image-after-preview').hide();
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#preview-after-img').attr('src', e.target.result);
                $('#image-after-preview').show();
            };
            reader.readAsDataURL(file);
        } else {
            $label.text('เลือกรูปภาพ...');
            $('#image-after-preview').hide();
        }
    });

    /**
     * Update status button handler
     */
    $(document).on('click', '.btn-update-status', function() {
        const $btn = $(this);
        const id = $btn.data('id');
        const newStatus = $btn.data('status');
        
        let actionText = '';
        
        switch(parseInt(newStatus)) {
            case 10:
                actionText = 'กลับเป็น "รออนุมัติ"';
                break;
            case 20:
                actionText = 'กลับเป็น "รอดำเนินการ"';
                break;
            case 30:
                actionText = 'เปลี่ยนเป็น "รออะไหล่"';
                break;
            case 40:
                // เปิด Modal สำหรับกรอกผู้ดำเนินการและอัพโหลดรูป
                $('#complete_id').val(id);
                $('#handled_by_input').val('');
                $('#job_status').prop('checked', true);
                $('#job_other_text').val('').prop('disabled', true);
                $('#receiver_name').val('');
                $('#image_after').val('');
                $('#image_after').siblings('.custom-file-label').text('เลือกรูปภาพ...');
                $('#image-after-preview').hide();
                $('#completeModal').modal('show');
                return;
            default:
                actionText = 'เปลี่ยนสถานะ';
        }
        
        // Confirm action (สำหรับสถานะอื่นที่ไม่ใช่เสร็จสิ้น)
        if (!confirm('คุณต้องการ' + actionText + ' ใช่หรือไม่?')) {
            return;
        }
        
        // Disable button
        $btn.prop('disabled', true);
        
        const postData = { 
            id: id, 
            status: newStatus
        };
        
        $.ajax({
            url: '../api/update_status.php',
            type: 'POST',
            data: postData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showMessage(response.message, 'success');
                    loadRepairData();
                } else {
                    showMessage(response.message, 'error');
                    $btn.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                showMessage('เกิดข้อผิดพลาดในการอัพเดทสถานะ', 'error');
                console.error('Update error:', error);
                $btn.prop('disabled', false);
            }
        });
    });

    /**
     * Complete form submission handler
     */
    $('#completeForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }
        
        const $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> กำลังบันทึก...');
        
        const formData = new FormData(this);
        formData.append('status', '40'); // เสร็จสิ้น (STATUS_COMPLETED)
        
        // Debug: Log form data
        console.log('Submitting complete form with data:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        $.ajax({
            url: '../api/update_status.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                console.log('Server response:', response);
                if (response.success) {
                    showMessage(response.message, 'success');
                    $('#completeModal').modal('hide');
                    $('#completeForm')[0].reset();
                    $('#completeForm').removeClass('was-validated');
                    loadRepairData();
                } else {
                    showMessage(response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error details:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });
                
                // Try to parse error response
                let errorMsg = 'เกิดข้อผิดพลาดในการอัพเดทสถานะ';
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.message) {
                        errorMsg = errorResponse.message;
                    }
                } catch(e) {
                    // Response is not JSON
                }
                
                showMessage(errorMsg, 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html('<i class="fas fa-check"></i> ยืนยันเสร็จสิ้น');
            }
        });
    });

    /**
     * Filter change handlers
     */
    $('#filter_department, #filter_machine, #filter_reported_by, #filter_status').on('change keyup', function() {
        loadRepairData();
    });
    
    /**
     * Clear filter button
     */
    $('#btn_clear_filter').on('click', function() {
        $('#filter_department').val('');
        $('#filter_machine').val('');
        $('#filter_reported_by').val('');
        $('#filter_status').val('');
        loadRepairData();
    });

    /**
     * Auto refresh data every 30 seconds
     */
    setInterval(function() {
        loadRepairData();
    }, 30000);

    // Initial load
    loadRepairData();
});
