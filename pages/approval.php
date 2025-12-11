<?php
session_start();
require_once '../config/db.php';
require_once '../config/config.php';

// ตรวจสอบว่า login แล้วหรือไม่ (ถ้ามีระบบ login)
// if (!isset($_SESSION['user_id'])) {
//     header('Location: ../login.php');
//     exit;
// }

// รับค่าจาก filter
$filter_department = isset($_GET['department']) ? trim($_GET['department']) : '';

// ดึงข้อมูลใบแจ้งซ่อมที่รออนุมัติ (สถานะ 10)
try {
    $sql = "SELECT * FROM mt_repair WHERE status = :status";
    
    // เพิ่มเงื่อนไขแผนก
    if (!empty($filter_department)) {
        $sql .= " AND department LIKE :department";
    }
    
    $sql .= " ORDER BY start_job DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':status', STATUS_PENDING_APPROVAL, PDO::PARAM_INT);
    
    if (!empty($filter_department)) {
        $stmt->bindValue(':department', '%' . $filter_department . '%');
    }
    
    $stmt->execute();
    $repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ดึงรายการแผนกทั้งหมดสำหรับ dropdown
    $deptSql = "SELECT DISTINCT department FROM mt_repair WHERE department IS NOT NULL AND department != '' AND status = :status ORDER BY department";
    $deptStmt = $conn->prepare($deptSql);
    $deptStmt->bindValue(':status', STATUS_PENDING_APPROVAL, PDO::PARAM_INT);
    $deptStmt->execute();
    $departments = $deptStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die('เกิดข้อผิดพลาด: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อนุมัติใบแจ้งซ่อม - ระบบ MT</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Sarabun', sans-serif;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .approval-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .page-header {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .page-header h2 {
            margin: 0;
            color: #667eea;
            font-weight: 700;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #667eea;
        }
        .repair-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .repair-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .repair-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .repair-id {
            font-size: 1.8rem;
            font-weight: 700;
            color: #667eea;
        }
        .repair-time {
            color: #999;
            font-size: 0.9rem;
        }
        .repair-info {
            margin-bottom: 15px;
        }
        .info-label {
            font-weight: 600;
            color: #666;
            margin-right: 10px;
        }
        .info-value {
            color: #333;
        }
        .issue-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .btn-approve {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
            color: white;
        }
        .btn-reject {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
            color: white;
        }
        .btn-view {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
            color: white;
        }
        .priority-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .priority-urgent {
            background: #dc3545;
            color: white;
        }
        .priority-normal {
            background: #28a745;
            color: white;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .empty-state i {
            font-size: 5rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .approver-input {
            font-size: 1.1rem;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
        }
        .approver-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
    <div class="approval-container">
        <!-- Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="fas fa-clipboard-check"></i> อนุมัติใบแจ้งซ่อม</h2>
                    <p class="mb-0 text-muted">รายการรออนุมัติจากหัวหน้าแผนก</p>
                </div>
                <div class="col-md-4 text-right">
                    <a href="../index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> กลับหน้าหลัก
                    </a>
                </div>
            </div>
        </div>

        <!-- Filter Box -->
        <div class="page-header">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-4">
                        <label for="department"><i class="fas fa-building"></i> แผนก:</label>
                        <select name="department" id="department" class="form-control">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($filter_department === $dept) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-filter"></i> กรอง
                        </button>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <a href="approval.php" class="btn btn-secondary btn-block">
                            <i class="fas fa-redo"></i> ล้าง
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Stats -->
        <div class="row">
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <div class="stat-number"><?php echo count($repairs); ?></div>
                    <div class="text-muted">รายการรออนุมัติ</div>
                </div>
            </div>
        </div>

        <!-- Repair Cards -->
        <?php if (count($repairs) > 0): ?>
            <?php foreach ($repairs as $repair): 
                $time_ago = '';
                if ($repair['start_job']) {
                    $datetime = new DateTime($repair['start_job']);
                    $now = new DateTime();
                    $interval = $now->diff($datetime);
                    if ($interval->d > 0) {
                        $time_ago = $interval->d . ' วันที่แล้ว';
                    } elseif ($interval->h > 0) {
                        $time_ago = $interval->h . ' ชั่วโมงที่แล้ว';
                    } else {
                        $time_ago = $interval->i . ' นาทีที่แล้ว';
                    }
                }
            ?>
            <div class="repair-card">
                <div class="repair-header">
                    <div>
                        <div class="repair-id">#<?php echo htmlspecialchars($repair['document_no'] ?: $repair['id']); ?></div>
                        <div class="repair-time">
                            <i class="fas fa-clock"></i> แจ้งเมื่อ: <?php echo $time_ago; ?>
                            (<?php echo date('d/m/Y H:i', strtotime($repair['start_job'])); ?> น.)
                        </div>
                    </div>
                    <div>
                        <?php if ($repair['priority'] === 'urgent'): ?>
                            <span class="priority-badge priority-urgent">
                                <i class="fas fa-exclamation-circle"></i> ด่วน
                            </span>
                        <?php else: ?>
                            <span class="priority-badge priority-normal">
                                <i class="fas fa-check-circle"></i> ปกติ
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="repair-info">
                            <span class="info-label"><i class="fas fa-building"></i> หน่วยงาน:</span>
                            <span class="info-value"><?php echo htmlspecialchars($repair['department']); ?></span>
                        </div>
                        <div class="repair-info">
                            <span class="info-label"><i class="fas fa-sitemap"></i> ฝ่าย:</span>
                            <span class="info-value"><?php echo htmlspecialchars($repair['division']); ?></span>
                        </div>
                        <div class="repair-info">
                            <span class="info-label"><i class="fas fa-user"></i> ผู้แจ้ง:</span>
                            <span class="info-value"><?php echo htmlspecialchars($repair['reported_by']); ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="repair-info">
                            <span class="info-label"><i class="fas fa-cog"></i> เครื่องจักร:</span>
                            <span class="info-value"><?php echo htmlspecialchars($repair['machine_number']); ?></span>
                        </div>
                        <div class="repair-info">
                            <span class="info-label"><i class="fas fa-map-marker-alt"></i> สาขา:</span>
                            <span class="info-value"><?php echo htmlspecialchars($repair['branch']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="issue-box">
                    <strong><i class="fas fa-exclamation-triangle"></i> ปัญหา/อาการ:</strong><br>
                    <?php echo nl2br(htmlspecialchars($repair['issue'])); ?>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-approve" onclick="approveRepair(<?php echo $repair['id']; ?>, '<?php echo htmlspecialchars($repair['document_no'] ?: $repair['id']); ?>')">
                        <i class="fas fa-check"></i> อนุมัติ
                    </button>
                    <button class="btn btn-reject" onclick="rejectRepair(<?php echo $repair['id']; ?>, '<?php echo htmlspecialchars($repair['document_no'] ?: $repair['id']); ?>')">
                        <i class="fas fa-times"></i> ไม่อนุมัติ
                    </button>
                    <a href="print_form.php?id=<?php echo $repair['id']; ?>" class="btn btn-view" target="_blank">
                        <i class="fas fa-eye"></i> ดูรายละเอียด
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-check-double"></i>
                <h3>ไม่มีรายการรออนุมัติ</h3>
                <p class="text-muted">รายการทั้งหมดได้รับการอนุมัติแล้ว</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal: Approve -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-check-circle"></i> อนุมัติใบแจ้งซ่อม</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>คุณต้องการอนุมัติใบแจ้งซ่อม <strong id="approve_doc_no"></strong> ใช่หรือไม่?</p>
                    <div class="form-group">
                        <label for="approver_name">ชื่อผู้อนุมัติ: <span class="text-danger">*</span></label>
                        <input type="text" class="form-control approver-input" id="approver_name" placeholder="กรอกชื่อของคุณ" required>
                    </div>
                    <input type="hidden" id="approve_repair_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-success" onclick="confirmApprove()">
                        <i class="fas fa-check"></i> ยืนยันการอนุมัติ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Reject -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title"><i class="fas fa-times-circle"></i> ไม่อนุมัติใบแจ้งซ่อม</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>คุณต้องการปฏิเสธใบแจ้งซ่อม <strong id="reject_doc_no"></strong> ใช่หรือไม่?</p>
                    <div class="form-group">
                        <label for="reject_approver_name">ชื่อผู้พิจารณา: <span class="text-danger">*</span></label>
                        <input type="text" class="form-control approver-input" id="reject_approver_name" placeholder="กรอกชื่อของคุณ" required>
                    </div>
                    <div class="form-group">
                        <label for="reject_reason">เหตุผลที่ไม่อนุมัติ: <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject_reason" rows="4" placeholder="กรอกเหตุผล..." required></textarea>
                    </div>
                    <input type="hidden" id="reject_repair_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-danger" onclick="confirmReject()">
                        <i class="fas fa-times"></i> ยืนยันการปฏิเสธ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function approveRepair(id, docNo) {
            $('#approve_repair_id').val(id);
            $('#approve_doc_no').text(docNo);
            $('#approver_name').val('');
            $('#approveModal').modal('show');
        }

        function rejectRepair(id, docNo) {
            $('#reject_repair_id').val(id);
            $('#reject_doc_no').text(docNo);
            $('#reject_approver_name').val('');
            $('#reject_reason').val('');
            $('#rejectModal').modal('show');
        }

        function confirmApprove() {
            const id = $('#approve_repair_id').val();
            const approver = $('#approver_name').val().trim();

            if (!approver) {
                alert('กรุณากรอกชื่อผู้อนุมัติ');
                return;
            }

            // Send AJAX request
            $.ajax({
                url: '../api/approve_repair.php',
                method: 'POST',
                data: {
                    id: id,
                    approver: approver
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('✓ อนุมัติใบแจ้งซ่อมเรียบร้อยแล้ว');
                        $('#approveModal').modal('hide');
                        location.reload();
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + response.message);
                    }
                },
                error: function() {
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
                }
            });
        }

        function confirmReject() {
            const id = $('#reject_repair_id').val();
            const approver = $('#reject_approver_name').val().trim();
            const reason = $('#reject_reason').val().trim();

            if (!approver) {
                alert('กรุณากรอกชื่อผู้พิจารณา');
                return;
            }

            if (!reason) {
                alert('กรุณากรอกเหตุผลในการปฏิเสธ');
                return;
            }

            // Send AJAX request
            $.ajax({
                url: '../api/reject_repair.php',
                method: 'POST',
                data: {
                    id: id,
                    approver: approver,
                    reason: reason
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('✓ ปฏิเสธใบแจ้งซ่อมเรียบร้อยแล้ว');
                        $('#rejectModal').modal('hide');
                        location.reload();
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + response.message);
                    }
                },
                error: function() {
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
                }
            });
        }
    </script>
</body>
</html>
