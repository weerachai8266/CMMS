// ฟังก์ชันโหลด master data ใช้ซ้ำได้ทุกหน้า

function loadBranches(selectId, firstOptionText = "ทั้งหมด", callback) {
    console.log('loadBranches called for', selectId);
    var $select = $(selectId);
    if ($select.length === 0) {
        console.warn('Selector not found for loadBranches:', selectId);
        if (typeof callback === 'function') callback({ success: false, data: [] });
        return;
    }
    $.ajax({
        url: '../api/master_data.php',
        method: 'GET',
        data: { action: 'list', type: 'branch' },
        dataType: 'json',
        success: function(response) {
            console.log('loadBranches response:', response);
            $select.empty();
            $select.append('<option value="">' + firstOptionText + '</option>');
            if (response && response.success && Array.isArray(response.data)) {
                response.data.forEach(function(branch) {
                    if (branch.is_active == 1) {
                        // Use branch.name as the option value (user requested names)
                        var val = branch.name || branch.id;
                        $select.append('<option value="' + val + '">' + branch.name + '</option>');
                    }
                });
            } else {
                console.warn('No branch data or response.success is false', response);
            }
            if (typeof callback === 'function') {
                try { callback(response); } catch (err) { console.error('loadBranches callback error:', err); }
            }
        },
        error: function(xhr, status, err) {
            console.error('Error loading branches:', status, err, xhr && xhr.responseText);
            $select.empty();
            $select.append('<option value="">' + firstOptionText + '</option>');
            if (typeof callback === 'function') callback({ success: false, data: [] });
        }
    });
}

function loadDivisions(selectId, firstOptionText = "ทั้งหมด") {
    $.ajax({
        url: '../api/master_data.php',
        method: 'GET',
        data: { action: 'list', type: 'division' },
        success: function(response) {
            if (response.success) {
                var $select = $(selectId);
                $select.empty();
                $select.append('<option value="">' + firstOptionText + '</option>');
                response.data.forEach(function(item) {
                    if (item.is_active == 1) {
                        $select.append('<option value="' + item.name + '">' + item.name + '</option>');
                    }
                });
            }
        }
    });
}

function loadDepartments(selectId, firstOptionText = "ทั้งหมด") {
    $.ajax({
        url: '../api/master_data.php',
        method: 'GET',
        data: { action: 'list', type: 'department' },
        success: function(response) {
            if (response.success) {
                var $select = $(selectId);
                $select.empty();
                $select.append('<option value="">' + firstOptionText + '</option>');
                response.data.forEach(function(item) {
                    if (item.is_active == 1) {
                        $select.append('<option value="' + item.name + '">' + item.name + '</option>');
                    }
                });
            }
        }
    });
}

function loadIssues(selectId, firstOptionText = "ทั้งหมด") {
    $.ajax({
        url: '../api/master_data.php',
        method: 'GET',
        data: { action: 'list', type: 'issue' },
        success: function(response) {
            if (response.success) {
                var $select = $(selectId);
                $select.empty();
                $select.append('<option value="">' + firstOptionText + '</option>');
                response.data.forEach(function(item) {
                    if (item.is_active == 1) {
                        $select.append('<option value="' + item.name + '">' + item.name + '</option>');
                    }
                });
            }
        }
    });
}
