<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Monitor - ระบบแจ้งซ่อมเครื่องจักร</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        html {
            font-size: 150%;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        html::-webkit-scrollbar {
            display: none;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Sarabun', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        body::-webkit-scrollbar {
            display: none;
        }
        
        .monitor-header {
            background: rgba(0, 0, 0, 0.3);
            padding: 12px 0;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            position: relative;
        }
        
        .monitor-header .btn-home {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.5);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .monitor-header .btn-home:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: white;
            color: white;
            text-decoration: none;
            transform: translateY(-50%) scale(1.05);
        }
        
        .monitor-header h1 {
            color: white;
            font-size: 2rem;
            font-weight: 800;
            text-align: center;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            margin: 0;
        }
        
        .monitor-header .datetime {
            color: rgba(255, 255, 255, 0.9);
            text-align: center;
            font-size: 1rem;
            margin-top: 5px;
        }
        
        .repair-grid {
            padding: 0 20px 20px 20px;
        }
        
        .repair-table {
            width: 100%;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }
        
        .repair-table table {
            width: 100%;
            margin: 0;
        }
        
        .repair-table thead {
            background: linear-gradient(135deg, #2c3e50, #34495e);
        }
        
        .repair-table thead th {
            color: white;
            padding: 12px 10px;
            font-weight: 700;
            font-size: 0.95rem;
            text-align: center;
            border: none;
        }
        
        .repair-table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .repair-table tbody tr:hover {
            transform: scale(1.005);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .repair-table tbody td {
            padding: 12px 10px;
            vertical-align: middle;
            font-size: 0.9rem;
        }
        
        /* สีตามสถานะ */
        .row-pending {
            background: linear-gradient(90deg, #fff3cd 0%, #ffffff 100%);
            border-left: 5px solid #ffc107;
        }
        
        .row-waiting {
            background: linear-gradient(90deg, #f8d7da 0%, #ffffff 100%);
            border-left: 5px solid #dc3545;
        }
        
        .row-completed {
            background: linear-gradient(90deg, #d4edda 0%, #ffffff 100%);
            border-left: 5px solid #28a745;
        }
        
        .repair-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: none; /* ซ่อนเพราะไม่ใช้แล้ว */
        }
        
        .repair-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .repair-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }
        
        .repair-id {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #f0f0f0;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
            color: #666;
        }
        
        .repair-department {
            font-size: 1.4rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
            padding-right: 60px;
        }
        
        .repair-machine {
            font-size: 1.8rem;
            font-weight: 800;
            color: #667eea;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .repair-machine i {
            font-size: 1.5rem;
        }
        
        .repair-issue {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        
        .repair-issue-title {
            font-weight: 700;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .repair-issue-text {
            color: #333;
            font-size: 1.1rem;
            line-height: 1.5;
        }
        
        .repair-report {
            background: #fff3cd;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #ffc107;
            min-height: 60px;
        }
        
        .repair-report-title {
            font-weight: 700;
            color: #856404;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        
        .repair-report-text {
            color: #856404;
            font-size: 1rem;
        }
        
        .repair-time {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .repair-time strong {
            color: #333;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 15px;
            text-align: center;
            width: 100%;
        }
        
        .status-pending {
            background: linear-gradient(135deg, #ffc107, #ff9800);
            color: white;
        }
        
        .status-completed {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .status-waiting {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
            color: white;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .btn-action {
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-complete {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .btn-approve {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }
        
        .btn-waiting {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
            color: white;
        }
        
        .btn-pending {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }
        
        .loading {
            text-align: center;
            padding: 50px;
            color: white;
            font-size: 1.5rem;
        }
        
        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .stats-bar {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 15px;
            margin: 0 20px 15px 20px;
            border-radius: 10px;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .stat-item {
            text-align: center;
            color: white;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            display: block;
        }
        
        .stat-label {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        @media (max-width: 768px) {
            .repair-grid {
                grid-template-columns: 1fr;
            }
            
            .monitor-header h1 {
                font-size: 1.8rem;
            }
        }
        
        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .repair-card {
            animation: fadeInUp 0.5s ease;
        }

        /* New item notification banner */
        #new-item-banner {
            display: none;
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #ff6b35, #f7c59f);
            color: white;
            padding: 14px 30px;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: 700;
            box-shadow: 0 6px 24px rgba(255, 107, 53, 0.5);
            z-index: 9999;
            /* เวลาปรากฏของแบนเนอร์ */
            animation: bannerPop 0.1s ease;
            white-space: nowrap;
        }
        @keyframes bannerPop {
            from { opacity: 0; transform: translateX(-50%) scale(0.8); }
            to   { opacity: 1; transform: translateX(-50%) scale(1); }
        }
    </style>
</head>
<body>

    <div class="monitor-header">
        <!-- <a href="../index.php" class="btn-home">
            <i class="fas fa-home"></i> หน้าแรก
        </a> -->
        <h1><a href="../index.php" style="color: white; text-decoration: none;"><i class="fas fa-tools"></i> ระบบแจ้งซ่อมเครื่องจักร - MONITOR</a></h1>
        <div class="datetime" id="datetime"></div>
    </div>

    <!-- New item notification banner -->
    <div id="new-item-banner">
        <i class="fas fa-bell"></i> มีรายการแจ้งซ่อมใหม่!
    </div>
    
    <div class="stats-bar" id="stats-bar">
        <div class="stat-item">
            <span class="stat-value" id="stat-total">0</span>
            <span class="stat-label">ทั้งหมด</span>
        </div>
        <div class="stat-item">
            <span class="stat-value" id="stat-pending">0</span>
            <span class="stat-label">รอดำเนินการ</span>
        </div>
        <div class="stat-item">
            <span class="stat-value" id="stat-waiting">0</span>
            <span class="stat-label">รออะไหล่</span>
        </div>
        <div class="stat-item">
            <span class="stat-value" id="stat-completed">0</span>
            <span class="stat-label">ซ่อมเสร็จแล้ว</span>
        </div>
    </div>

    <div class="repair-grid" id="repair-grid">
        <div class="loading">
            <div class="spinner"></div>
            กำลังโหลดข้อมูล...
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            
            // Update date time
            function updateDateTime() {
                const now = new Date();
                const options = { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                };
                $('#datetime').text(now.toLocaleDateString('th-TH', options));
            }
            
            updateDateTime();
            setInterval(updateDateTime, 1000);
            
            // ---- Notification Sound System ----
            // Kiosk mode: ใช้ --autoplay-policy=no-user-gesture-required ใน Chromium
            let soundEnabled = true;           // เปิดเสียงไว้เป็นค่าเริ่มต้น
            let knownItemIds = new Set();      // เก็บ ID รายการที่รู้จักแล้ว
            let isFirstLoad = true;            // โหลดครั้งแรกยังไม่แจ้งเตือน

            let _soundPlaying = false;
            function playNotificationSound() {
                if (!soundEnabled || _soundPlaying) return;
                try {
                    let count = 0;
                    _soundPlaying = true;
                    const playBeep = () => {
                        if (count < 3) {
                            const audio = new Audio('../assets/sounds/tiengdong_com.mp3');
                            audio.volume = 1.0;
                            audio.onended = () => {
                                count++;
                                setTimeout(playBeep, 200); // หน่วงเล็กน้อยระหว่างเสียง
                            };
                            audio.play().catch(e => {
                                console.error('Audio play error:', e);
                                _soundPlaying = false;
                            });
                        } else {
                            _soundPlaying = false;
                        }
                    };
                    playBeep();
                } catch (e) {
                    console.error('Error playing sound:', e);
                    _soundPlaying = false;
                }
            }

            function showNewItemBanner(count) {
                const $banner = $('#new-item-banner');
                $banner.html('<i class="fas fa-bell"></i> มีรายการแจ้งซ่อมใหม่ ' + count + ' รายการ!');
                $banner.fadeIn(300);
                setTimeout(() => $banner.fadeOut(600), 4000);
            }
            
            // Load repair data
            function loadRepairData() {
                $.ajax({
                    url: '../api/monitor_data.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            if (isFirstLoad) {
                                // โหลดครั้งแรก: บันทึก ID ทั้งหมดโดยไม่แจ้งเตือน
                                response.data.forEach(function(item) {
                                    knownItemIds.add(String(item.id));
                                });
                                isFirstLoad = false;
                            } else {
                                // โหลดครั้งถัดไป: ตรวจหา ID ใหม่ที่ยังไม่รู้จัก
                                const newItems = response.data.filter(function(item) {
                                    return !knownItemIds.has(String(item.id));
                                });
                                if (newItems.length > 0) {
                                    console.log('New items detected:', newItems.length, newItems.map(i => i.id));
                                    newItems.forEach(function(item) {
                                        knownItemIds.add(String(item.id));
                                    });
                                    showNewItemBanner(newItems.length);
                                    playNotificationSound();
                                }
                            }

                            displayRepairCards(response.data);
                            updateStats(response.stats);
                        } else {
                            $('#repair-grid').html('<div class="loading">ไม่สามารถโหลดข้อมูลได้</div>');
                        }
                    },
                    error: function() {
                        $('#repair-grid').html('<div class="loading">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>');
                    }
                });
            }
            
            // Display repair cards
            function displayRepairCards(data) {
                if (data.length === 0) {
                    $('#repair-grid').html('<div class="loading">ไม่มีรายการแจ้งซ่อม</div>');
                    return;
                }
                
                let html = `
                    <div class="repair-table">
                        <table>
                            <thead>
                                <tr>
                                    <!--<th style="width: 50px;">ID</th>-->
                                    <th style="width: 140px;">เลขที่เอกสาร</th>
                                    <th style="width: 100px;">แผนก</th>
                                    <th style="width: 120px;">เครื่องจักร</th>
                                    <th style="width: 280px;">อาการเสีย</th>
                                    <th style="width: 110px;">ผู้แจ้ง</th>
                                    <!--<th style="width: 110px;">ผู้ดำเนินการ</th>-->
                                    <!--<th>รายงาน MT</th>-->
                                    <th style="width: 120px;">เวลาแจ้ง</th>
                                    <th style="width: 100px;">เวลาที่ผ่านไป</th>
                                    <th style="width: 130px;">สถานะ</th>
                                    <!--<th style="width: 200px;">จัดการ</th>-->
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                data.forEach(function(item) {
                    const statusInfo = getStatusInfo(item.status);
                    const rowClass = getRowClass(item.status);
                    const endJobDisplay = item.end_job && item.end_job !== '0000-00-00 00:00:00' ? item.end_job : '-';
                    
                    // ใช้ approved_at ถ้ามี ไม่งั้นใช้ start_job
                    const startTime = item.approved_at_formatted || item.start_job;
                    const startTimeRaw = item.approved_at_raw || item.start_job_raw;
                    
                    const elapsed = calculateElapsedTime(startTime, item.end_job, item.status);
                    
                    html += `
                        <tr class="${rowClass}">
                            <!--<td style="text-align: center; font-weight: 700; font-size: 1rem;">#${item.id}</td>-->
                            <td style="text-align: center; font-weight: 700; font-size: 0.95rem; color: #007bff;">
                                <i class="fas fa-file-alt"></i> ${escapeHtml(item.document_no || '-')}
                            </td>
                            <td style="font-weight: 600; font-size: 0.9rem;">${escapeHtml(item.department)}</td>
                            <td style="text-align: center;">
                                <strong style="font-size: 1.05rem; color: #667eea;">
                                    <i class="fas fa-cog"></i> ${escapeHtml(item.machine_number)}
                                </strong>
                            </td>
                            <td style="font-size: 0.9rem; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${escapeHtml(item.issue)}">${escapeHtml(item.issue)}</td>
                            <td style="text-align: center; font-weight: 600; font-size: 0.85rem;">
                                <i class="fas fa-user"></i> ${escapeHtml(item.reported_by || '-')}
                            </td>
                            <!--<td style="text-align: center; font-weight: 600; font-size: 0.85rem; color: #28a745;">
                                <i class="fas fa-user-cog"></i> ${escapeHtml(item.handled_by || '-')}
                            </td>-->
                            <!--<td style="color: #856404;">${item.mt_report ? escapeHtml(item.mt_report) : '-'}</td>-->
                            <td style="text-align: center; font-size: 0.85rem;">
                                <i class="fas fa-clock"></i> ${item.start_job}
                            </td>
                            <td style="text-align: center; font-weight: 700; font-size: 0.95rem;" class="elapsed-time" data-start="${startTimeRaw || ''}" data-end="${item.end_job_raw || ''}" data-status="${item.status}">
                                ${elapsed.display}
                            </td>
                            <td style="text-align: center;">
                                <span class="status-badge ${statusInfo.class}" style="display: inline-block; padding: 6px 12px; border-radius: 15px; font-weight: 700; font-size: 0.85rem;">
                                    <i class="${statusInfo.icon}"></i> ${statusInfo.text}
                                </span>
                            </td>
                            <!-- ปุ่มจัดการ
                            <td style="text-align: center;">
                                ${getActionButtons(item.id, item.status)}
                            </td>
                            -->
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
                
                $('#repair-grid').html(html);
            }
            
            // Get row class based on status
            function getRowClass(status) {
                switch(parseInt(status)) {
                    case 10:
                        return 'row-pending';  // รออนุมัติ
                    case 20:
                        return 'row-pending';  // รอดำเนินการ
                    case 30:
                        return 'row-waiting';  // รออะไหล่
                    case 40:
                        return 'row-completed';  // ซ่อมเสร็จสิ้น
                    default:
                        return '';
                }
            }
            
            // Calculate elapsed time
            function calculateElapsedTime(startJobFormatted, endJobFormatted, status) {
                try {
                    // ถ้าไม่มีเวลาเริ่มต้น ให้ return -
                    if (!startJobFormatted || startJobFormatted === '-') {
                        return {
                            display: '<span style="color: #999;">รออนุมัติ</span>',
                            totalMinutes: 0
                        };
                    }
                    
                    // Parse Thai formatted date "12/11/2025 10:24"
                    const startParts = startJobFormatted.split(' ');
                    const dateParts = startParts[0].split('/');
                    const timeParts = startParts[1].split(':');
                    
                    const startDate = new Date(
                        parseInt(dateParts[2]),  // year
                        parseInt(dateParts[1]) - 1,  // month (0-indexed)
                        parseInt(dateParts[0]),  // day
                        parseInt(timeParts[0]),  // hour
                        parseInt(timeParts[1])   // minute
                    );
                    
                    let endDate;
                    if (status == 40 && endJobFormatted && endJobFormatted !== '-') {
                        // If completed (status 40), use end_job
                        const endParts = endJobFormatted.split(' ');
                        const endDateParts = endParts[0].split('/');
                        const endTimeParts = endParts[1].split(':');
                        
                        endDate = new Date(
                            parseInt(endDateParts[2]),
                            parseInt(endDateParts[1]) - 1,
                            parseInt(endDateParts[0]),
                            parseInt(endTimeParts[0]),
                            parseInt(endTimeParts[1])
                        );
                    } else {
                        // Still pending or waiting, use current time
                        endDate = new Date();
                    }
                    
                    const diff = endDate - startDate;
                    
                    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    
                    let displayParts = [];
                    let colorClass = '';
                    
                    if (days > 0) {
                        displayParts.push(`${days}วัน`);
                        colorClass = 'color: #dc3545; font-weight: 800;'; // Red for > 1 day
                    }
                    if (hours > 0 || days > 0) {
                        displayParts.push(`${hours}ชม.`);
                        if (!colorClass && hours >= 8) {
                            colorClass = 'color: #ffc107; font-weight: 700;'; // Orange for >= 8 hours
                        }
                    }
                    displayParts.push(`${minutes}นาที`);
                    
                    if (!colorClass) {
                        colorClass = 'color: #28a745;'; // Green for < 8 hours
                    }
                    
                    return {
                        display: `<span style="${colorClass}"><i class="fas fa-hourglass-half"></i> ${displayParts.join(' ')}</span>`,
                        totalMinutes: Math.floor(diff / (1000 * 60))
                    };
                } catch (e) {
                    return {
                        display: '-',
                        totalMinutes: 0
                    };
                }
            }
            
            // Get status info
            function getStatusInfo(status) {
                switch(parseInt(status)) {
                    case 10:
                        return { 
                            class: 'status-pending', 
                            text: 'รออนุมัติ',
                            icon: 'fas fa-clipboard-check'
                        };
                    case 20:
                        return { 
                            class: 'status-pending', 
                            text: 'รอดำเนินการ',
                            icon: 'fas fa-clock'
                        };
                    case 30:
                        return { 
                            class: 'status-waiting', 
                            text: 'รออะไหล่',
                            icon: 'fas fa-hourglass-half'
                        };
                    case 40:
                        return { 
                            class: 'status-completed', 
                            text: 'ซ่อมเสร็จสิ้น',
                            icon: 'fas fa-check-circle'
                        };
                    default:
                        return { 
                            class: 'status-pending', 
                            text: 'ไม่ทราบสถานะ',
                            icon: 'fas fa-question-circle'
                        };
                }
            }
            
            // Get action buttons based on current status
            function getActionButtons(id, status) {
                status = parseInt(status);
                
                let buttons = '<div style="display: flex; gap: 5px; justify-content: center;">';
                
                if (status === 10) {
                    // รออนุมัติ -> อนุมัติไปรอดำเนินการ
                    buttons += `
                        <button class="btn-action btn-approve" data-id="${id}" data-status="20" style="padding: 8px 15px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-check"></i> อนุมัติ
                        </button>
                    `;
                } else if (status === 20) {
                    // รอดำเนินการ -> สามารถเปลี่ยนเป็น ซ่อมเสร็จ หรือ รออะไหล่
                    buttons += `
                        <button class="btn-action btn-complete" data-id="${id}" data-status="40" style="padding: 8px 15px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-check"></i> เสร็จ
                        </button>
                        <button class="btn-action btn-waiting" data-id="${id}" data-status="30" style="padding: 8px 15px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-hourglass-half"></i> รออะไหล่
                        </button>
                    `;
                } else if (status === 30) {
                    // รออะไหล่ -> สามารถเปลี่ยนเป็น ซ่อมเสร็จ หรือ กลับเป็นรอดำเนินการ
                    buttons += `
                        <button class="btn-action btn-complete" data-id="${id}" data-status="40" style="padding: 8px 15px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-check"></i> เสร็จ
                        </button>
                        <button class="btn-action btn-pending" data-id="${id}" data-status="20" style="padding: 8px 15px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-undo"></i> กลับ
                        </button>
                    `;
                } else if (status === 40) {
                    // ซ่อมเสร็จสิ้น -> สามารถยกเลิกกลับเป็น รอดำเนินการ
                    buttons += `
                        <button class="btn-action btn-pending" data-id="${id}" data-status="20" style="padding: 8px 15px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-undo"></i> ยกเลิก
                        </button>
                    `;
                }
                
                buttons += '</div>';
                return buttons;
            }
            
            // Update statistics
            function updateStats(stats) {
                $('#stat-total').text(stats.total);
                $('#stat-pending').text(stats.pending);
                $('#stat-waiting').text(stats.waiting);
                $('#stat-completed').text(stats.completed);
            }
            
            // Escape HTML to prevent XSS
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            // Handle status update
            $(document).on('click', '.btn-action', function() {
                const id = $(this).data('id');
                const newStatus = $(this).data('status');
                const statusText = getStatusInfo(newStatus).text;
                
                if (confirm(`คุณต้องการเปลี่ยนสถานะเป็น "${statusText}" ใช่หรือไม่?`)) {
                    $.ajax({
                        url: '../api/monitor_update.php',
                        type: 'POST',
                        data: { id: id, status: newStatus },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                loadRepairData();
                            } else {
                                alert('เกิดข้อผิดพลาด: ' + response.message);
                            }
                        },
                        error: function() {
                            alert('เกิดข้อผิดพลาดในการอัพเดทสถานะ');
                        }
                    });
                }
            });
            
            // Initial load
            loadRepairData();
            
            // Auto refresh every 10 seconds
            setInterval(loadRepairData, 10000);
            
            // Update elapsed time every minute
            setInterval(function() {
                $('.elapsed-time').each(function() {
                    const $cell = $(this);
                    const startJob = $cell.data('start');
                    const endJob = $cell.data('end');
                    const status = $cell.data('status');
                    
                    if (startJob) {
                        const elapsed = calculateElapsedTime(startJob, endJob, status);
                        $cell.html(elapsed.display);
                    }
                });
            }, 60000); // Update every 1 minute
        });
    </script>

</body>
</html>
