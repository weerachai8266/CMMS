<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

// ดึงพารามิเตอร์ - รองรับทั้งเครื่องเดียวและหลายเครื่อง
$machineCodes = [];
if (isset($_GET['machine_code']) && !empty($_GET['machine_code'])) {
    if (is_array($_GET['machine_code'])) {
        $machineCodes = $_GET['machine_code'];
    } else {
        $machineCodes = [$_GET['machine_code']];
    }
}

if (empty($machineCodes)) {
    die('กรุณาระบุรหัสเครื่องจักร');
}

// สร้าง Spreadsheet
$spreadsheet = new Spreadsheet();
$spreadsheet->removeSheetByIndex(0); // ลบ sheet เริ่มต้น

// วนลูปสร้าง sheet สำหรับแต่ละเครื่อง
foreach ($machineCodes as $index => $machineCode) {
    // ดึงข้อมูลเครื่องจักร
    $sqlMachine = "SELECT * FROM mt_machines WHERE machine_code = :machine_code LIMIT 1";
    $stmtMachine = $conn->prepare($sqlMachine);
    $stmtMachine->bindParam(':machine_code', $machineCode);
    $stmtMachine->execute();
    $machine = $stmtMachine->fetch(PDO::FETCH_ASSOC);

    if (!$machine) {
        continue; // ข้ามถ้าไม่พบข้อมูล
    }

    // ดึงประวัติการซ่อม
    $sqlHistory = "SELECT * FROM mt_machine_history 
                   WHERE machine_code = :machine_code 
                   ORDER BY work_date ASC, created_at DESC";
    $stmtHistory = $conn->prepare($sqlHistory);
    $stmtHistory->bindParam(':machine_code', $machineCode);
    $stmtHistory->execute();
    $historyData = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

    // สร้าง Sheet ใหม่
    $sheet = new PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $machineCode);
    $spreadsheet->addSheet($sheet, $index);
    
    // ตั้งค่ากระดาษ A4 แนวนอน ไม่มีขอบ
    $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
    $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
    $sheet->getPageMargins()->setTop(0);
    $sheet->getPageMargins()->setRight(0);
    $sheet->getPageMargins()->setLeft(0);
    $sheet->getPageMargins()->setBottom(0);
    $sheet->getPageMargins()->setHeader(0);
    $sheet->getPageMargins()->setFooter(0);
    
    // ตั้งค่า default font
    // Aptos Narrow
    // Angsana New
    $sheet->getParent()->getDefaultStyle()->getFont()->setName('Angsana New')->setSize(10);
    // จัดแนวข้อความให้อยู่ตรงกลางระหว่างบน ล่าง
    $sheet->getStyle('A:L')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    

    // Header - Title
    $sheet->mergeCells('A1:L1');
    $sheet->setCellValue('A1', 'ทะเบียนประวัติเครื่องจักร / ระบบอาคารสถูปโลก / เครื่องมือ');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->mergeCells('A2:L2');
    $sheet->setCellValue('A2', '( Machine record / Facilities / Tooling )');
    $sheet->getStyle('A2')->getFont()->setSize(12);
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // ข้อมูลเครื่องจักร - แถวที่ 4 เป็นต้นไป
    $row = 4;

    // แถวที่ 1:4 
    $sheet->mergeCells('B4:C4');
    $sheet->mergeCells('E4:G4');
    $sheet->mergeCells('H4:I4');
    $sheet->mergeCells('J4:L4');

    $sheet->setCellValue('A' . $row, 'ชื่อ');
    $sheet->setCellValue('B' . $row, $machine['machine_name'] ?? '');
    $sheet->setCellValue('D' . $row, 'บริษัทผู้ผลิต');
    $sheet->setCellValue('E' . $row, $machine['manufacturer'] ?? '');
    $sheet->setCellValue('H' . $row, 'ราคาซื้อ');
    $sheet->setCellValue('J' . $row, $machine['purchase_price'] ?? ' ');
    $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('#,##0.00" ฿"');
    
    $sheet->getStyle('A' . $row . ':A' . $row)->getFont()->setBold(true);
    $sheet->getStyle('D' . $row . ':D' . $row)->getFont()->setBold(true);
    $sheet->getStyle('J' . $row . ':J' . $row)->getFont()->setBold(true);
    $row++;

    // แถวที่ 2:5 
    $sheet->mergeCells('B5:C5');
    $sheet->mergeCells('E5:G5');
    $sheet->mergeCells('H5:I5');
    $sheet->mergeCells('J5:L5');
    
    $sheet->setCellValue('A' . $row, 'รหัสเครื่อง');
    $sheet->setCellValue('B' . $row, $machine['machine_code'] ?? '');
    $sheet->setCellValue('D' . $row, 'ผู้แทนจำหน่าย');
    $sheet->setCellValue('E' . $row, $machine['supplier'] ?? '');
    $sheet->setCellValue('H' . $row, 'วันที่ซื้อ');
    $sheet->setCellValue('J' . $row, $machine['purchase_date'] ? date('d/m/Y', strtotime($machine['purchase_date'])) : '');
    
    $sheet->getStyle('A' . $row . ':A' . $row)->getFont()->setBold(true);
    $sheet->getStyle('D' . $row . ':D' . $row)->getFont()->setBold(true);
    $sheet->getStyle('H' . $row . ':H' . $row)->getFont()->setBold(true);
    $row++;

    // แถวที่ 3:6 
    $sheet->mergeCells('B6:C6');
    $sheet->mergeCells('E6:G6');
    $sheet->mergeCells('H6:I6');
    $sheet->mergeCells('J6:L6');

    $sheet->setCellValue('A' . $row, 'หน่วยงาน');
    $sheet->setCellValue('B' . $row, $machine['responsible_dept'] ?? '');
    $sheet->setCellValue('D' . $row, 'แบบหรือรุ่น');
    $sheet->setCellValue('E' . $row, $machine['model'] ?? '');
    $sheet->setCellValue('H' . $row, 'วันที่เริ่มใช้งาน');
    $sheet->setCellValue('J' . $row, $machine['start_date'] ? date('d/m/Y', strtotime($machine['start_date'])) : '');  
   
    $sheet->getStyle('A' . $row . ':A' . $row)->getFont()->setBold(true);
    $sheet->getStyle('D' . $row . ':D' . $row)->getFont()->setBold(true);
    $sheet->getStyle('H' . $row . ':H' . $row)->getFont()->setBold(true);
    $row++;

    // แถวที่ 4:7 
    $sheet->mergeCells('B7:C7');
    $sheet->mergeCells('E7:G7');
    $sheet->mergeCells('H7:I7');
    $sheet->mergeCells('J7:L7');

    $sheet->setCellValue('A' . $row, 'พื้นที่ใช้งาน');
    $sheet->setCellValue('B' . $row, $machine['work_area'] ?? '');
    $sheet->setCellValue('D' . $row, 'ขนาด');
    $sheet->setCellValue('E' . $row, $machine['horsepower'] ?? '');
    $sheet->setCellValue('H' . $row, 'วันที่ขึ้นทะเบียน');
    $sheet->setCellValue('J' . $row, $machine['register_date'] ? date('d/m/Y', strtotime($machine['register_date'])) : '');

    $sheet->getStyle('A' . $row . ':A' . $row)->getFont()->setBold(true);
    $sheet->getStyle('D' . $row . ':D' . $row)->getFont()->setBold(true);
    $sheet->getStyle('H' . $row . ':H' . $row)->getFont()->setBold(true);
    $row++;

    // แถวที่ 5:8 
    $sheet->mergeCells('B8:C8');
    $sheet->mergeCells('H8:I8');
    $sheet->mergeCells('J8:L8');

    $sheet->setCellValue('A' . $row, 'ประเภทเครื่องจักร');
    $sheet->setCellValue('B' . $row, $machine['machine_type'] ?? '');
    $sheet->setCellValue('D' . $row, 'น้ำหนัก');
    $sheet->setCellValue('E' . $row, $machine['weight'] ?? '');
    $sheet->setCellValue('F' . $row, 'หมายเลขเครื่อง');
    $sheet->setCellValue('G' . $row, $machine['machine_number'] ?? '');
    $sheet->setCellValueExplicit('G' . $row, $machine['machine_number'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue('H' . $row, 'เบอร์โทรติดต่อ');
    $sheet->setCellValue('J' . $row, $machine['contact_phone'] ?? '');

    $sheet->getStyle('A' . $row . ':A' . $row)->getFont()->setBold(true);
    $sheet->getStyle('D' . $row . ':D' . $row)->getFont()->setBold(true);
    $sheet->getStyle('F' . $row . ':F' . $row)->getFont()->setBold(true);
    $sheet->getStyle('H' . $row . ':H' . $row)->getFont()->setBold(true);
    $row++;

    // ใส่เส้นขอบให้ข้อมูลเครื่องจักร
    $sheet->getStyle('A4:L' . ($row - 1))->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
            'horizontal' => [
                'borderStyle' => Border::BORDER_HAIR,
            ],
            'vertical' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
    ]);

    // $row++; // เว้นบรรทัด

    // ตารางประวัติการซ่อม
    $headerRow = $row;
    // Title ของตาราง
    $sheet->mergeCells('A' . $row . ':L' . $row);
    $sheet->getStyle('A' . $row . ':L' . $row)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => '00000000'],
            ],
        ],
    ]);
    $sheet->setCellValue('A' . $row, 'ประวัติการซ่อม (Repair record)');
    $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row++;

    // Header ของตาราง - 2 แถว (merge cells)
    $sheet->mergeCells('A' . $row . ':A' . ($row + 1)); // วันที่เข้าซ่อม
    $sheet->mergeCells('B' . $row . ':B' . ($row + 1)); // เลขที่เอกสาร
    $sheet->mergeCells('C' . $row . ':C' . ($row + 1)); // อาการเสีย
    $sheet->mergeCells('D' . $row . ':D' . ($row + 1)); // รายละเอียดในการซ่อม
    $sheet->mergeCells('E' . $row . ':E' . ($row + 1)); // รายการเปลี่ยนอะไหล่
    $sheet->mergeCells('F' . $row . ':G' . $row); // เวลา
    $sheet->mergeCells('I' . $row . ':J' . $row); // วันที่
    $sheet->mergeCells('L' . $row . ':L' . ($row + 1)); // หมายเหตุ    

    // แถวบนของ header
    $sheet->setCellValue('A' . $row, 'วันที่แจ้งซ่อม');
    $sheet->setCellValue('B' . $row, 'เลขที่เอกสาร');
    $sheet->setCellValue('C' . $row, 'อาการเสีย / ปัญหา');
    $sheet->setCellValue('D' . $row, 'รายละเอียดในการซ่อม');
    $sheet->setCellValue('E' . $row, 'รายการเปลี่ยนอะไหล่');
    $sheet->setCellValue('F' . $row, 'เวลา');
    $sheet->setCellValue('H' . $row, 'ค่าใช้จ่าย (฿)');
    $sheet->setCellValue('I' . $row, 'วันที่');
    $sheet->setCellValue('K' . $row, 'ผู้รับผิดชอบ');
    $sheet->setCellValue('L' . $row, 'หมายเหตุ');

    $row++; // ไปแถวถัดไป

    // แถวล่างของ header (sub-header)
    $sheet->setCellValue('F' . $row, 'ปฏิบัติงาน');
    $sheet->setCellValue('G' . $row, 'ผลิตที่เสียไป');
    $sheet->setCellValue('H' . $row, 'ในการซ่อม');
    $sheet->setCellValue('I' . $row, 'เริ่มซ่อม');
    $sheet->setCellValue('J' . $row, 'ซ่อมเสร็จ');
    $sheet->setCellValue('K' . $row, 'ในการซ่อม');

    // Style header ทั้ง 2 แถว
    $headerStartRow = $row - 1;
    $sheet->getStyle('A' . $headerStartRow . ':L' . $row)->applyFromArray([
        'font' => ['bold' => true],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFE0E0E0'],
        ],
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
        ],
    ]);

    $row++;
    $dataStartRow = $row;

    // ใส่ข้อมูลประวัติ
    if (count($historyData) > 0) {
        foreach ($historyData as $history) {

            // $sheet->mergeCells('E' . $row . ':G' . $row); // รายการเปลี่ยนอะไหล่
            
            $sheet->setCellValue('A' . $row, $history['work_date'] ? date('d-m-Y', strtotime($history['work_date'])) : '-');
            $sheet->setCellValue('B' . $row, $history['document_no'] ?? '-');                   // เลขที่เอกสาร
            $sheet->setCellValue('C' . $row, $history['issue_description'] ?? '-');             // อาการเสีย
            $sheet->setCellValue('D' . $row, $history['solution_description'] ?? '-');          // รายละเอียดในการซ่อม
            $sheet->setCellValue('E' . $row, $history['parts_used'] ?? '-');                    // รายการเปลี่ยนอะไหล่
            $sheet->setCellValue('F' . $row, ($history['work_hours'] ?? '0') . ' ชม.');         // เวลา ปฏิบัติงาน
            $sheet->setCellValue('G' . $row, ($history['downtime_hours'] ?? '0') . ' ชม.');     // เวลา ผลิตที่เสียไป
            $sheet->setCellValue('H' . $row, number_format($history['total_cost'] ?? 0, 2));    // ค่าใช้จ่าย (฿)
            $sheet->setCellValue('I' . $row, $history['start_date'] ? date('d-m-Y', strtotime($history['start_date'])) : '-');
            $sheet->setCellValue('J' . $row, $history['completed_date'] ? date('d-m-Y', strtotime($history['completed_date'])) : '-');
            $sheet->setCellValue('K' . $row, $history['handled_by'] ?? '-');                    // ผู้รับผิดชอบ
            $sheet->setCellValue('L' . $row, $history['note'] ?? '');                           // หมายเหตุ
            
            // จัด alignment
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            // $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('F' . $row . ':K' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Word wrap สำหรับคอลัมน์ที่มีข้อความยาว
            $sheet->getStyle('C' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('D' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('E' . $row)->getAlignment()->setWrapText(true); // Merged cell E:G
            $sheet->getStyle('L' . $row)->getAlignment()->setWrapText(true);
            
            // Auto-resize ความสูงของแถว
            // $sheet->getRowDimension($row)->setRowHeight(-1);

            $row++;
        }
    } else {
        $sheet->mergeCells('A' . $row . ':L' . $row);
        $sheet->setCellValue('A' . $row, 'ไม่มีประวัติการซ่อม');
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;
    }

    // ใส่เส้นขอบให้ตารางประวัติ

    // รูปแบบเส้นทั้งหมด:
    // BORDER_THIN - เส้นบาง (━━━)
    // BORDER_DOTTED - เส้นประ (⋯⋯⋯)
    // BORDER_DASHED - เส้นประจุด (╍╍╍)
    // BORDER_DASHDOT - เส้นประจุดผสม (╌·╌·╌)
    // BORDER_DOUBLE - เส้นคู่ (═══)
    // BORDER_THICK - เส้นหนา (━━━)
    // BORDER_MEDIUM - เส้นกลาง
    // BORDER_HAIR - เส้นบางมาก
    // ขอบนอก (outline) = BORDER_THIN
    // เส้นแนวนอนภายใน (horizontal) = BORDER_DOTTED
    // เส้นแนวตั้งภายใน (vertical) = BORDER_THIN
    $sheet->getStyle('A' . ($dataStartRow - 1) . ':L' . ($row - 1))->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
            'horizontal' => [
                'borderStyle' => Border::BORDER_HAIR,
            ],
            'vertical' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
    ]);

    // $row++;
    // ใส่เส้นขอบด้านบนแถวสุดท้าย
    $sheet->getStyle('A' . $row . ':L' . $row)->applyFromArray([
        'borders' => [
            'top' => [
            'borderStyle' => Border::BORDER_THIN,
            ],
        ],
        ]);

    $sheet->getStyle('A12:L12' . $row)->applyFromArray([
        'borders' => [
            'top' => [
            'borderStyle' => Border::BORDER_THIN,
            ],
        ],
        ]);

    // Footer
    $sheet->setCellValue('L' . $row, 'QWF-MT-02-0-01/01/2566');
    $sheet->getStyle('L' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('L' . $row)->getFont()->setSize(10);

    // ปรับความกว้างคอลัมน์
    $sheet->getColumnDimension('A')->setWidth(19);
    $sheet->getColumnDimension('B')->setWidth(13);
    $sheet->getColumnDimension('C')->setWidth(34);
    $sheet->getColumnDimension('D')->setWidth(31);
    $sheet->getColumnDimension('E')->setWidth(54);
    $sheet->getColumnDimension('F')->setWidth(15);
    $sheet->getColumnDimension('G')->setWidth(15);
    $sheet->getColumnDimension('H')->setWidth(12);
    $sheet->getColumnDimension('I')->setWidth(12);
    $sheet->getColumnDimension('J')->setWidth(12);
    $sheet->getColumnDimension('K')->setWidth(14);
    $sheet->getColumnDimension('L')->setWidth(18);


    // Word wrap สำหรับคอลัมน์ที่มีข้อความยาว
    // $sheet->getStyle('C' . $dataStartRow . ':G' . ($row - 1))->getAlignment()->setWrapText(true);
    // $sheet->getStyle('N' . $dataStartRow . ':N' . ($row - 1))->getAlignment()->setWrapText(true);
}

// ตั้ง sheet แรกเป็น active
if ($spreadsheet->getSheetCount() > 0) {
    $spreadsheet->setActiveSheetIndex(0);
}

// ส่งออกไฟล์
$filename = count($machineCodes) > 1 
    ? 'Machine_History_Multiple_' . date('Ymd') . '.xlsx'
    : 'Machine_History_' . $machineCodes[0] . '_' . date('Ymd') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;