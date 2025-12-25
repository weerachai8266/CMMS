<?php
require_once '../config/db.php';
require_once '../config/config.php';

// ตรวจสอบ ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('ไม่พบข้อมูลใบแจ้งซ่อม');
}

$id = intval($_GET['id']);

// ดึงข้อมูลจากฐานข้อมูล
try {
    $sql = "SELECT * FROM mt_repair WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        die('ไม่พบข้อมูลใบแจ้งซ่อม ID: ' . $id);
    }
    
    // ดึงชื่อเครื่องจักรจาก mt_machines
    $machine_name = '';
    if (!empty($data['machine_number'])) {
        $sqlMachine = "SELECT machine_name FROM mt_machines WHERE machine_code = :machine_code LIMIT 1";
        $stmtMachine = $conn->prepare($sqlMachine);
        $stmtMachine->bindParam(':machine_code', $data['machine_number']);
        $stmtMachine->execute();
        $machineData = $stmtMachine->fetch(PDO::FETCH_ASSOC);
        $machine_name = $machineData ? $machineData['machine_name'] : '';
    }
    
    // Format dates
    $start_date = $data['start_job'] ? date('d/m/Y', strtotime($data['start_job'])) : '';
    $start_time = $data['start_job'] ? date('H:i', strtotime($data['start_job'])) : '';
    $end_date = $data['end_job'] ? date('d/m/Y', strtotime($data['end_job'])) : '';
    
    // Use document_no if available, otherwise use ID
    $form_number = !empty($data['document_no']) ? $data['document_no'] : str_pad($id, 4, '0', STR_PAD_LEFT);
    
    // Get action_type and priority
    $action_type = $data['action_type'] ?? 'repair';
    $action_other_text = $data['action_other_text'] ?? '';
    $priority = $data['priority'] ?? 'urgent';
    
    // Get operation_type (comma-separated string) and convert to array
    $operation_type_str = $data['operation_type'] ?? '';
    $operation_types = !empty($operation_type_str) ? explode(',', $operation_type_str) : [];
    $operation_other_text = $data['operation_other_text'] ?? '';
    
    // Get job_status for Section 4
    $job_status = $data['job_status'] ?? 'complete';
    $job_other_text = $data['job_other_text'] ?? '';
    
    // Format Section 2 dates
    $receive_date_formatted = $data['receive_date'] ? date('Y-m-d', strtotime($data['receive_date'])) : '';
    $receive_time_formatted = $data['receive_time'] ? date('H:i', strtotime($data['receive_time'])) : '';
    $mtc_date_formatted = $data['mtc_date'] ? date('Y-m-d', strtotime($data['mtc_date'])) : '';
    
    // Format Section 3 dates
    // ใช้ approved_at สำหรับวันที่เริ่มปฏิบัติงาน ถ้าไม่มี start_date
    $section3_start_date = $data['start_date'] ? date('Y-m-d', strtotime($data['start_date'])) : 
                          ($data['approved_at'] ? date('Y-m-d', strtotime($data['approved_at'])) : '');
    $section3_start_time = $data['start_time'] ? date('H:i', strtotime($data['start_time'])) : 
                          ($data['approved_at'] ? date('H:i', strtotime($data['approved_at'])) : '');
    $section3_end_date = $data['end_date'] ? date('Y-m-d', strtotime($data['end_date'])) : 
                        ($data['end_job'] ? date('Y-m-d', strtotime($data['end_job'])) : '');
    $section3_end_time = $data['end_time'] ? date('H:i', strtotime($data['end_time'])) : 
                        ($data['end_job'] ? date('H:i', strtotime($data['end_job'])) : '');
    $registry_date_formatted = $data['registry_date'] ? date('Y-m-d', strtotime($data['registry_date'])) : '';
    
} catch (PDOException $e) {
    die('เกิดข้อผิดพลาด: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบแจ้งซ่อม - <?php echo $form_number; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/print.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- ปุ่มพิมพ์ (แสดงแค่บนหน้าจอ) -->
    <div class="no-print">
        <!-- <a href="../index.php" class="print-btn" style="display: inline-block; margin-right: 10px; background: #6c757d; text-decoration: none;">
            <i class="fas fa-home"></i> หน้าแรก
        </a> -->
        <button class="print-btn" id="save-btn" style="background: #28a745;">
            <i class="fas fa-save"></i> บันทึกข้อมูล
        </button>
        <button class="print-btn" onclick="window.print()">
            <i class="fas fa-print"></i> พิมพ์ใบแจ้งซ่อม
        </button>
        <button class="print-btn close-btn" onclick="window.close()">
            <i class="fas fa-times"></i> ปิดหน้าต่าง
        </button>
    </div>

    <!-- ฟอร์มใบแจ้งซ่อม -->
    <div class="print-container">
        <!-- Header -->
        <div class="form-section">
            <div class="form-header">
                <div class="company-logo">
                    <img src="../img/logo.JPG" alt="Logo" style="width: 60px; height: 60px; object-fit: contain;">
                </div>
                <h2>บริษัท ชัยวัฒนา แทนเนอรี่ กรุ๊ป จำกัด (มหาชน)</h2>
                <h3>ใบแจ้งซ่อมเครื่องจักร / ซ่อมสร้างทั่วไป</h3>
                <p class="small-text">( Machine repair requirement / Rebuilding )</p>
            </div>

            <!-- Top Info -->
            <div class="top-info">
                <div>เล่มที่ <span contenteditable="true" class="underline-field" style="min-width: 100px;"></span></div>
                <div>เลขที่ <span contenteditable="true" class="underline-field" style="min-width: 100px; "><?php echo htmlspecialchars($data['document_no']); ?></span></div>
            </div>
        

        <!-- Section 1: ผู้แจ้งซ่อม -->
        <!-- <div class="form-section"> -->
            <div class="two-columns">
                <!-- Left Column -->
                <div class="column-left">
                    <div class="section-header" style="border-bottom: 1px solid #000; border-top: 1px solid #000;">1 : ผู้แจ้งซ่อม ( Communicant )</div>
                    <div class="section-content">
                        <div class="form-field">
                            หน่วยงาน <span class="underline-field" style="min-width: 295px; cursor: default; "><?php echo htmlspecialchars($data['department']); ?></span>
                        </div>                        
                        <div class="form-field">
                            ฝ่าย <span class="underline-field" style="min-width: 325px; cursor: default; "><?php echo htmlspecialchars($data['division']); ?></span>
                        </div>
                        <div class="form-field">
                            บริษัท <span class="underline-field" style="min-width: 170px; cursor: default; ">ชัยวัฒนา แทนเนอรี่ กรุ๊ป</span> 
                            สาขา <span class="underline-field" style="min-width: 100px; cursor: default; "><?php echo htmlspecialchars($data['branch']); ?></span>
                        </div>
                        
                        <div class="form-field" style="margin-top: 12px;">
                            <strong>โปรดดำเนินการ</strong>
                        </div>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" <?php echo ($action_type === 'check') ? 'checked' : ''; ?> class="readonly-checkbox" onclick="return false;"> ตรวจสอบ
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" <?php echo ($action_type === 'fix') ? 'checked' : ''; ?> class="readonly-checkbox" onclick="return false;"> แก้ไขปัญหา
                            </div>
                        </div>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" <?php echo ($action_type === 'repair') ? 'checked' : ''; ?> class="readonly-checkbox" onclick="return false;"> ซ่อม
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" <?php echo ($action_type === 'other') ? 'checked' : ''; ?> class="readonly-checkbox" onclick="return false;"> อื่นๆ 
                                <span class="underline-field" style="min-width: 150px; <?php echo ($action_type === 'other') ? '' : ''; ?>"><?php echo ($action_type === 'other') ? htmlspecialchars($action_other_text) : ''; ?></span>
                            </div>
                        </div>
                        
                        <div class="form-field" style="margin-top: 12px;">
                            <strong>ความเร่งด่วนของงาน</strong>
                            <span class="checkbox-item" style="margin-left: 20px;">
                                <input type="checkbox" <?php echo ($priority === 'urgent') ? 'checked' : ''; ?> class="readonly-checkbox" onclick="return false;"> ด่วน
                            </span>
                            <span class="checkbox-item">
                                <input type="checkbox" <?php echo ($priority === 'normal') ? 'checked' : ''; ?> class="readonly-checkbox" onclick="return false;"> ปกติ
                            </span>
                        </div>
                        
                        <div class="form-field" style="margin-top: 12px;">
                            ชื่อเครื่องจักร <span class="underline-field" style="min-width: 285px; cursor: default; "><?php echo htmlspecialchars($machine_name); ?></span>
                        </div>
                        <div class="form-field" style="margin-top: 12px;">
                            รหัสเครื่องจักร <span class="underline-field" style="min-width: 280px; cursor: default; "><?php echo htmlspecialchars($data['machine_number']); ?></span>
                        </div>
                        <div class="form-field">
                            อาการที่เสีย / รายละเอียดที่จะให้สร้างหรือปรับปรุง
                        </div>
                        <div class="form-field">
                            ( ถ้าแจ้งสร้างให้แนบ Drawing ประกอบ )
                        </div>
                        <div style="margin-top: 8px;">
                            <textarea class="text-area" rows="8" readonly><?php echo htmlspecialchars($data['issue']); ?></textarea>
                        </div>
                        
                        <div class="form-field">
                            <div>ลงชื่อ <span class="underline-field" style="min-width: 255px; cursor: default; "><?php echo htmlspecialchars($data['reported_by']); ?></span> ( ผู้แจ้งซ่อม )</div>
                        </div>
                        <div class="form-field">
                            <div>วันที่ <span class="underline-field" style="min-width: 130px; cursor: default; "><?php echo htmlspecialchars($data['start_job'] ? date('d/m/Y', strtotime($data['start_job'])) : ''); ?></span> 
                            เวลา <span class="underline-field" style="min-width: 130px; cursor: default; "><?php echo htmlspecialchars($data['start_job'] ? date('H:i', strtotime($data['start_job'])) : ''); ?></span> น.</div>
                        </div>
                        <div class="form-field">
                            <div>ลงชื่อ <span class="underline-field" style="min-width: 265px; cursor: default; "><?php echo htmlspecialchars($data['approver'] ?? ''); ?></span> ( ผู้อนุมัติ )</div>
                        </div>
                        <div class="form-field">
                            <div>วันที่ <span class="underline-field" style="min-width: 130px; cursor: default; "><?php echo htmlspecialchars($data['approved_at'] ? date('d/m/Y', strtotime($data['approved_at'])) : ''); ?></span> 
                            เวลา <span class="underline-field" style="min-width: 130px; cursor: default; "><?php echo htmlspecialchars($data['approved_at'] ? date('H:i', strtotime($data['approved_at'])) : ''); ?></span> น.</div>
                        </div>                        
                        
                    </div>

                    <!-- 4 : บันทึกการรับงาน -->
                    <div class="section-header" style="border-bottom: 1px solid #000; border-top: 1px solid #000;">4 : บันทึกการรับงาน</div>
                    <div class="section-content">
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" <?php echo ($job_status === 'complete') ? 'checked' : ''; ?> class="readonly-checkbox" onclick="return false;"> งานเสร็จสมบูรณ์ตามใบแจ้งซ่อมนี้แล้ว
                            </div>
                        </div>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" <?php echo ($job_status === 'other') ? 'checked' : ''; ?> class="readonly-checkbox" onclick="return false;"> อื่นๆ
                            </div>
                            <textarea class="text-area" rows="1" readonly><?php echo ($job_status === 'other') ? htmlspecialchars($job_other_text) : ''; ?></textarea>
                        </div>
                        <div class="form-field">
                            <div>ลงชื่อ <span contenteditable="true" class="underline-field" style="min-width: 265px;"><?php echo htmlspecialchars($data['receiver_name']); ?></span> ( ผู้รับงาน )</div>
                        </div>
                        <div style="margin-top: 8px; display: flex; justify-content: space-between;">
                            <div>วันที่ <span class="underline-field" style="min-width: 130px; cursor: default; "><?php echo htmlspecialchars($data['end_job'] ? date('d/m/Y', strtotime($data['end_job'])) : ''); ?></span> 
                            เวลา <span class="underline-field" style="min-width: 130px; cursor: default; "><?php echo htmlspecialchars($data['end_job'] ? date('H:i', strtotime($data['end_job'])) : ''); ?></span> น.</div>
                        </div>

                    </div>
                </div>

                <!-- Right Column -->
                <div class="column-right">
                    <div class="section-header" style="border-bottom: 1px solid #000; border-top: 1px solid #000;">2 : ผู้รับแจ้งซ่อม (Maintenance)</div>
                    <div class="section-content">
                        <div class="form-field">
                            <div>วันที่รับแจ้งซ่อม <input type="date" id="receive_date" class="date-input" <?php if($receive_date_formatted) echo 'value="' . htmlspecialchars($receive_date_formatted) . '"'; ?>> 
                            เวลา <input type="time" id="receive_time" class="time-input" <?php if($receive_time_formatted) echo 'value="' . htmlspecialchars($receive_time_formatted) . '"'; ?>></div>
                        </div>
                        <div class="form-field">
                            ลงชื่อผู้รับแจ้ง <span contenteditable="true" class="underline-field" id="receiver_mt" style="min-width: 265px;"><?php echo htmlspecialchars($data['receiver_mt'] ?? ''); ?></span>
                        </div>
                        
                        <div class="form-field" style="margin-top: 15px;">
                            <strong>บันทึกความคิดเห็น MTC / MTM</strong>
                        </div>
                        <div>
                            <textarea class="text-area" id="mtc_comment" rows="3" ><?php echo htmlspecialchars($data['mtc_comment'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-field" style="margin-top: 15px; padding: 0px;">
                            <div>ลงชื่อ <span contenteditable="true" class="underline-field" id="mtc_signer" style="min-width: 125px;"><?php echo htmlspecialchars($data['mtc_signer'] ?? ''); ?></span> 
                            วันที่ <input type="date" id="mtc_date" class="date-input" <?php if($mtc_date_formatted) echo 'value="' . htmlspecialchars($mtc_date_formatted) . '"'; ?>></div>
                        </div>                        

                    </div>
                    <div class="section-header" style="border-bottom: 1px solid #000; border-top: 1px solid #000;">3 : บันทึกการดำเนินการซ่อม / สร้าง</div>
                    <div class="section-content">
                        <div class="form-field">
                            สาเหตุ
                        </div>
                        <div>
                            <textarea class="text-area" id="mt_report" rows="3"><?php echo htmlspecialchars($data['mt_report'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-field" style="margin-top: 10px;">
                        <strong>การดำเนินการ</strong>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="op_change_part" value="change_part" <?php echo in_array('change_part', $operation_types) ? 'checked' : ''; ?>> เปลี่ยนอะไหล่
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="op_external_repair" value="external_repair" <?php echo in_array('external_repair', $operation_types) ? 'checked' : ''; ?>> ส่งซ่อมข้างนอก
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="op_order_part" value="order_part" <?php echo in_array('order_part', $operation_types) ? 'checked' : ''; ?>> สั่งซื้ออะไหล่
                            </div>                        
                            <div class="checkbox-item">
                                <input type="checkbox" id="op_order_material" value="order_material" <?php echo in_array('order_material', $operation_types) ? 'checked' : ''; ?>> สั่งซื้อวัสดุ-อุปกรณ์ต่างๆ
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="op_other" value="other" <?php echo (count(array_filter($operation_types, function($v) { return strpos($v, 'other:') === 0; })) > 0) ? 'checked' : ''; ?>> อื่นๆ <span contenteditable="true" class="underline-field" id="operation_other_text" style="min-width: 100px;"><?php 
                                $other = array_filter($operation_types, function($v) { return strpos($v, 'other:') === 0; });
                                echo $other ? htmlspecialchars(str_replace('other:', '', reset($other))) : '';
                                ?></span>
                            </div>
                        </div>                
                        <div>
                            <textarea class="text-area" id="operation_detail" rows="3"><?php echo htmlspecialchars($data['operation_detail'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-field">
                            จำนวนผู้ปฏิบัติงาน <span contenteditable="true" class="underline-field" id="worker_count" style="min-width: 225px;"><?php echo htmlspecialchars($data['worker_count'] ?? ''); ?></span> คน
                        </div>
                        <div class="form-field">
                            จำนวนเวลาในการปฏิบัติงาน <span contenteditable="true" class="underline-field" id="work_hours" style="min-width: 175px;"><?php echo htmlspecialchars($data['work_hours'] ?? ''); ?></span> ช.ม.
                        </div>
                        <div class="form-field">
                            จำนวนเวลาที่เครื่องจักรทำการผลิตไม่ได้ <span contenteditable="true" class="underline-field" id="downtime_hours" style="min-width: 115px;"><?php echo htmlspecialchars($data['downtime_hours'] ?? ''); ?></span> ช.ม.
                        </div>
                        <div class="form-field">
                            <div>วันที่เริ่มปฏิบัติงาน <input type="date" id="start_date" class="date-input" <?php if($section3_start_date) echo 'value="' . htmlspecialchars($section3_start_date) . '"'; ?>> 
                            เวลา <input type="time" id="start_time" class="time-input" <?php if($section3_start_time) echo 'value="' . htmlspecialchars($section3_start_time) . '"'; ?>></div>
                        </div>
                        <div class="form-field">
                            <div>วันที่ทำงานเสร็จ <input type="date" id="end_date" class="date-input" <?php if($section3_end_date) echo 'value="' . htmlspecialchars($section3_end_date) . '"'; ?>> 
                            เวลา <input type="time" id="end_time" class="time-input" <?php if($section3_end_time) echo 'value="' . htmlspecialchars($section3_end_time) . '"'; ?>></div>
                        </div>                        
                        <div class="form-field">
                            ค่าใช้จ่ายทั้งหมด <span contenteditable="true" class="underline-field" id="total_cost" style="min-width: 235px;"><?php echo htmlspecialchars($data['total_cost'] ?? ''); ?></span> บาท
                        </div>
                        <div class="form-field">
                            ลงชื่อ <span contenteditable="true" class="underline-field" id="handled_by_sign" style="min-width: 220px;"><?php echo htmlspecialchars($data['handled_by']); ?></span> ( ช่างผู้รับผิดชอบ )
                        </div>
                        <div class="form-field">
                            ลงประวัติในทะเบียนเครื่องจักร  วันที่ <input type="date" id="registry_date" class="date-input" <?php if($registry_date_formatted) echo 'value="' . htmlspecialchars($registry_date_formatted) . '"'; ?>>
                        </div>
                        <div class="form-field">
                            ลงชื่อผู้ลงประวัติ  <span contenteditable="true" class="underline-field" id="registry_signer" style="min-width: 255px;"><?php echo htmlspecialchars($data['registry_signer'] ?? ''); ?></span>
                        </div>
                        <div class="form-field">
                            ลงชื่อ  <span contenteditable="true" class="underline-field" id="mtc_manager" style="min-width: 230px;"><?php echo htmlspecialchars($data['mtc_manager'] ?? ''); ?></span> ( MTC / MTM )
                        </div>
                    </div>
                </div>
            </div>
        </div>     
        

        <!-- Route / Flowchart Section -->
        <div style="text-align: center; border-top: 1px solid #000;">
            <!-- <strong style="font-size: 11pt;">Route :</strong><br> -->
            <img src="../img/route.png" alt="Route" style="max-width: 99%; height: auto; margin-top: 5px;">
        </div>
        
    </div>
    <!-- Form Footer -->
    <div style="text-align: right; margin-top: 1px; font-size: 10pt;">
        QWF-MT-01-0-01/01/2022
    </div>

    <script>
        // ซ่อน placeholder ของ date/time inputs โดยใช้ CSS injection
        document.addEventListener('DOMContentLoaded', function() {
            // สร้าง style element เพื่อซ่อน datetime placeholder
            const style = document.createElement('style');
            style.textContent = `
                .date-input::-webkit-datetime-edit-fields-wrapper {
                    opacity: 0;
                }
                .date-input:focus::-webkit-datetime-edit-fields-wrapper,
                .date-input.has-value::-webkit-datetime-edit-fields-wrapper {
                    opacity: 1;
                }
                .time-input::-webkit-datetime-edit-fields-wrapper {
                    opacity: 0;
                }
                .time-input:focus::-webkit-datetime-edit-fields-wrapper,
                .time-input.has-value::-webkit-datetime-edit-fields-wrapper {
                    opacity: 1;
                }
            `;
            document.head.appendChild(style);
            
            // ตรวจสอบและเพิ่ม class has-value เมื่อมีค่า
            function updateInputClass() {
                document.querySelectorAll('.date-input, .time-input').forEach(input => {
                    if (input.value) {
                        input.classList.add('has-value');
                    } else {
                        input.classList.remove('has-value');
                    }
                });
            }
            
            // เรียกใช้ครั้งแรก
            updateInputClass();
            
            // อัพเดทเมื่อมีการเปลี่ยนแปลง
            document.querySelectorAll('.date-input, .time-input').forEach(input => {
                input.addEventListener('change', updateInputClass);
                input.addEventListener('input', updateInputClass);
            });
            
            // คำนวณเวลาอัตโนมัติเมื่อโหลดหน้าเสร็จ (กรณีมีข้อมูลวันที่อยู่แล้ว)
            calculateWorkHours();
        });
        
        // Calculate work hours automatically
        function calculateWorkHours() {
            const startDate = document.getElementById('start_date').value;
            const startTime = document.getElementById('start_time').value;
            const endDate = document.getElementById('end_date').value;
            const endTime = document.getElementById('end_time').value;
            
            if (startDate && startTime && endDate && endTime) {
                const start = new Date(startDate + ' ' + startTime);
                const end = new Date(endDate + ' ' + endTime);
                
                const diffMs = end - start;
                if (diffMs > 0) {
                    const diffHours = (diffMs / (1000 * 60 * 60)).toFixed(2);
                    document.getElementById('work_hours').textContent = diffHours;
                } else {
                    document.getElementById('work_hours').textContent = '';
                }
            }
        }
        
        // Add event listeners
        document.getElementById('start_date').addEventListener('change', calculateWorkHours);
        document.getElementById('start_time').addEventListener('change', calculateWorkHours);
        document.getElementById('end_date').addEventListener('change', calculateWorkHours);
        document.getElementById('end_time').addEventListener('change', calculateWorkHours);
        
        // Save data function
        document.getElementById('save-btn').addEventListener('click', function() {
            // Get operation types (checkboxes)
            const operationTypes = [];
            if (document.getElementById('op_change_part').checked) operationTypes.push('change_part');
            if (document.getElementById('op_external_repair').checked) operationTypes.push('external_repair');
            if (document.getElementById('op_order_part').checked) operationTypes.push('order_part');
            if (document.getElementById('op_order_material').checked) operationTypes.push('order_material');
            if (document.getElementById('op_other').checked) {
                const otherText = document.getElementById('operation_other_text').textContent.trim();
                operationTypes.push('other:' + otherText);
            }
            
            // Collect data from editable fields
            const formData = {
                id: <?php echo $id; ?>,
                section: 0,
                // Section 2
                receive_date: document.getElementById('receive_date').value,
                receive_time: document.getElementById('receive_time').value,
                receiver_mt: document.getElementById('receiver_mt').textContent.trim(),
                mtc_comment: document.getElementById('mtc_comment').value.trim(),
                mtc_signer: document.getElementById('mtc_signer').textContent.trim(),
                mtc_date: document.getElementById('mtc_date').value,
                
                // Section 3
                mt_report: document.getElementById('mt_report').value.trim(),
                operation_type: operationTypes.join(','),
                operation_detail: document.getElementById('operation_detail').value.trim(),
                worker_count: document.getElementById('worker_count').textContent.trim(),
                work_hours: document.getElementById('work_hours').textContent.trim(),
                downtime_hours: document.getElementById('downtime_hours').textContent.trim(),
                start_date: document.getElementById('start_date').value,
                start_time: document.getElementById('start_time').value,
                end_date: document.getElementById('end_date').value,
                end_time: document.getElementById('end_time').value,
                total_cost: document.getElementById('total_cost').textContent.trim(),
                registry_date: document.getElementById('registry_date').value,
                registry_signer: document.getElementById('registry_signer').textContent.trim(),
                mtc_manager: document.getElementById('mtc_manager').textContent.trim()
            };
            
            // Save button
            const saveBtn = this;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...';
            
            // Send data via AJAX
            fetch('../api/update_print_form.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✓ บันทึกข้อมูลเรียบร้อยแล้ว');
                    saveBtn.innerHTML = '<i class="fas fa-check"></i> บันทึกสำเร็จ';
                    saveBtn.style.background = '#218838';
                    setTimeout(() => {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<i class="fas fa-save"></i> บันทึกข้อมูล';
                        saveBtn.style.background = '#28a745';
                    }, 2000);
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save"></i> บันทึกข้อมูล';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save"></i> บันทึกข้อมูล';
            });
        });
        
        // Auto print when page loads (optional)
        // window.onload = function() {
        //     window.print();
        // };
    </script>
</body>
</html>
