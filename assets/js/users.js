// User Management JavaScript

$(document).ready(function() {
    loadUsers();
});

function loadUsers() {
    $.ajax({
        url: '../api/users.php',
        method: 'GET',
        data: { action: 'list' },
        success: function(response) {
            if (response.success) {
                displayUsers(response.data);
            } else {
                $('#usersTableBody').html('<tr><td colspan="12" class="text-center text-danger">' + response.message + '</td></tr>');
            }
        },
        error: function() {
            $('#usersTableBody').html('<tr><td colspan="12" class="text-center text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>');
        }
    });
}

function displayUsers(users) {
    const tbody = $('#usersTableBody');
    let html = '';
    
    if (users.length === 0) {
        tbody.html('<tr><td colspan="12" class="text-center">ไม่มีข้อมูล</td></tr>');
        return;
    }
    
    users.forEach(function(user) {
        const statusBadge = user.is_active == 1 
            ? '<span class="badge badge-success">ใช้งาน</span>' 
            : '<span class="badge badge-secondary">ปิดใช้งาน</span>';
        
        const roleBadge = {
            'admin': '<span class="badge badge-danger">ผู้ดูแลระบบ</span>',
            'manager': '<span class="badge badge-warning">ผู้จัดการ</span>',
            'supervisor': '<span class="badge badge-info">หัวหน้างาน</span>',
            'leader': '<span class="badge badge-info">ผู้นำ</span>',
            'engineer': '<span class="badge badge-success">วิศวกร</span>',
            'maintenance': '<span class="badge badge-primary">ช่างซ่อม</span>',
            'technician': '<span class="badge badge-primary">ช่างเทคนิค</span>',
            'staff': '<span class="badge badge-secondary">เจ้าหน้าที่</span>',
            'viewer': '<span class="badge badge-light text-dark border">ผู้ดู</span>'
        }[user.role] || '<span class="badge badge-secondary">' + user.role + '</span>';
        
        const lockedBadge = user.locked_until && new Date(user.locked_until) > new Date()
            ? '<br><span class="badge badge-danger"><i class="fas fa-lock"></i> ล็อค</span>'
            : '';
        
        const lastLogin = user.last_login ? new Date(user.last_login).toLocaleString('th-TH') : '-';
        
        html += '<tr>';
        html += '<td class="text-center">' + user.id + '</td>';
        html += '<td><strong>' + (user.username || '-') + '</strong>' + lockedBadge + '</td>';
        html += '<td>' + (user.full_name || '-') + '</td>';
        html += '<td><small>' + (user.email || '-') + '</small></td>';
        html += '<td><small>' + (user.phone || '-') + '</small></td>';
        html += '<td class="text-center">' + roleBadge + '</td>';
        html += '<td><small>' + (user.department || '-') + '</small></td>';
        html += '<td><small>' + (user.branch || '-') + '</small></td>';
        html += '<td><small>' + (user.position || '-') + '</small></td>';
        html += '<td class="text-center">' + statusBadge + '</td>';
        html += '<td class="text-center"><small>' + lastLogin + '</small></td>';
        html += '<td class="text-center">';
        // ตรวจสอบสิทธิ์: admin จัดการได้ทุกคน, manager จัดการได้ยกเว้น admin
        if (user.role === 'admin' && typeof currentUserRole !== 'undefined' && currentUserRole !== 'admin') {
            // manager ไม่สามารถจัดการ user ที่เป็น admin
            html += '<span class="text-muted"><i class="fas fa-shield-alt"></i> ป้องกัน</span>';
        } else {
            html += '<button class="btn btn-sm btn-primary" onclick="editUser(' + user.id + ')" title="แก้ไข"><i class="fas fa-edit"></i></button> ';
            html += '<button class="btn btn-sm btn-warning" onclick="showResetPassword(' + user.id + ', \'' + user.username + '\')" title="รีเซ็ตรหัสผ่าน"><i class="fas fa-key"></i></button> ';
            html += '<button class="btn btn-sm btn-info" onclick="toggleUserStatus(' + user.id + ')" title="เปลี่ยนสถานะ"><i class="fas fa-power-off"></i></button> ';
            html += '<button class="btn btn-sm btn-danger" onclick="deleteUser(' + user.id + ')" title="ลบ"><i class="fas fa-trash"></i></button>';
        }
        html += '</td>';
        html += '</tr>';
    });
    
    tbody.html(html);
}

function openUserModal() {
    $('#user_id').val('');
    $('#userForm')[0].reset();
    $('#userModalTitle').html('<i class="fas fa-user-plus"></i> เพิ่มผู้ใช้ใหม่');
    $('#password').prop('required', true);
    $('#password_required').show();
    $('#userModal').modal('show');
}

function editUser(id) {
    $.ajax({
        url: '../api/users.php',
        method: 'GET',
        data: { action: 'get', id: id },
        success: function(response) {
            if (response.success) {
                const user = response.data;
                $('#user_id').val(user.id);
                $('#username').val(user.username);
                $('#full_name').val(user.full_name);
                $('#email').val(user.email);
                $('#phone').val(user.phone);
                $('#role').val(user.role);
                $('#employee_id').val(user.employee_id);
                $('#department').val(user.department);
                $('#branch').val(user.branch);
                $('#position').val(user.position);
                
                $('#password').prop('required', false);
                $('#password_required').hide();
                $('#userModalTitle').html('<i class="fas fa-edit"></i> แก้ไขผู้ใช้: ' + user.username);
                $('#userModal').modal('show');
            } else {
                alert('เกิดข้อผิดพลาด: ' + response.message);
            }
        }
    });
}
                


$('#userForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'save');
    
    $.ajax({
        url: '../api/users.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $('#userModal').modal('hide');
                loadUsers();
                alert(response.message);
            } else {
                alert('เกิดข้อผิดพลาด: ' + response.message);
            }
        },
        error: function() {
            alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
        }
    });
});

function deleteUser(id) {
    if (!confirm('คุณต้องการลบผู้ใช้นี้หรือไม่?')) return;
    
    $.ajax({
        url: '../api/users.php',
        method: 'POST',
        data: { action: 'delete', id: id },
        success: function(response) {
            if (response.success) {
                loadUsers();
                alert(response.message);
            } else {
                alert('เกิดข้อผิดพลาด: ' + response.message);
            }
        }
    });
}

function toggleUserStatus(id) {
    $.ajax({
        url: '../api/users.php',
        method: 'POST',
        data: { action: 'toggle_status', id: id },
        success: function(response) {
            if (response.success) {
                loadUsers();
            } else {
                alert('เกิดข้อผิดพลาด: ' + response.message);
            }
        }
    });
}

function showResetPassword(id, username) {
    $('#reset_user_id').val(id);
    $('#reset_username').text(username);
    $('#resetPasswordModal').modal('show');
}

function confirmResetPassword() {
    const id = $('#reset_user_id').val();
    
    $.ajax({
        url: '../api/users.php',
        method: 'POST',
        data: { action: 'reset_password', id: id },
        success: function(response) {
            $('#resetPasswordModal').modal('hide');
            alert(response.message);
            if (response.success) {
                loadUsers();
            }
        },
        error: function() {
            alert('เกิดข้อผิดพลาดในการรีเซ็ตรหัสผ่าน');
        }
    });
}

function openChangePasswordModal() {
    $('#changePasswordForm')[0].reset();
    $('#changePasswordModal').modal('show');
}

$('#changePasswordForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'change_password');
    
    $.ajax({
        url: '../api/users.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $('#changePasswordModal').modal('hide');
                alert(response.message);
            } else {
                alert('เกิดข้อผิดพลาด: ' + response.message);
            }
        }
    });
});

function formatDateTime(datetime) {
    if (!datetime) return '-';
    const d = new Date(datetime);
    return d.toLocaleDateString('th-TH') + ' ' + d.toLocaleTimeString('th-TH');
}
