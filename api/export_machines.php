<?php
require_once '../config/db.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

try {
    // ดึงข้อมูลเครื่องจักร
    $sql = "SELECT * FROM mt_machines ORDER BY machine_type ASC, machine_code ASC";
    $stmt = $conn->query($sql);
    $machines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // สร้าง Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Machine Registry');
    $sheet->getParent()->getDefaultStyle()->getFont()->setName('Aptos Narrow')->setSize(11);

    // กำหนด Header ตามลำดับ Modal
    $headers = [
        'ประเภท',
        'รหัสเครื่องจักร',
        'ชื่อเครื่องจักร',
        'หมายเลขเครื่อง',
        'ยี่ห้อ',
        'รุ่น',
        'กำลังแรงม้า',
        'น้ำหนัก',
        'จำนวน',
        'บริษัทผู้ผลิต',
        'ผู้แทนจำหน่าย',
        'หน่วยงานรับผิดชอบ',
        'พื้นที่ใช้งาน',
        'ราคาซื้อ (บาท)',
        'เบอร์โทรติดต่อ',
        'วันที่ซื้อ',
        'วันที่เริ่มใช้งาน',
        'วันที่ขึ้นทะเบียน',
        'สถานะ',
        'หน่วย',
        'หมายเหตุ'
    ];

    // เขียน Header
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $col++;
    }

    // จัดรูปแบบ Header
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '343A40']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    $sheet->getStyle('A1:U1')->applyFromArray($headerStyle);

    // เขียนข้อมูล
    $row = 2;
    foreach ($machines as $machine) {
        // แปลงสถานะ
        $status = '';
        switch ($machine['machine_status']) {
            case 'active': $status = 'ใช้งาน'; break;
            case 'maintenance': $status = 'ซ่อมบำรุง'; break;
            case 'broken': $status = 'ชำรุด'; break;
            case 'retired': $status = 'เลิกใช้งาน'; break;
            default: $status = $machine['machine_status'] ?? '';
        }

        $sheet->setCellValue('A' . $row, $machine['machine_type'] ?? '');
        $sheet->setCellValue('B' . $row, $machine['machine_code'] ?? '');
        $sheet->setCellValue('C' . $row, $machine['machine_name'] ?? '');
        $sheet->setCellValue('D' . $row, $machine['machine_number'] ?? '');
        $sheet->setCellValue('E' . $row, $machine['brand'] ?? '');
        $sheet->setCellValue('F' . $row, $machine['model'] ?? '');
        $sheet->setCellValue('G' . $row, $machine['horsepower'] ?? '');
        $sheet->setCellValue('H' . $row, $machine['weight'] ?? '');
        $sheet->setCellValue('I' . $row, $machine['quantity'] ?? '');
        $sheet->setCellValue('J' . $row, $machine['manufacturer'] ?? '');
        $sheet->setCellValue('K' . $row, $machine['supplier'] ?? '');
        $sheet->setCellValue('L' . $row, $machine['responsible_dept'] ?? '');
        $sheet->setCellValue('M' . $row, $machine['work_area'] ?? '');
        $sheet->setCellValue('N' . $row, $machine['purchase_price'] ?? '');
        $sheet->setCellValue('O' . $row, $machine['contact_phone'] ?? '');
        $sheet->setCellValue('P' . $row, $machine['purchase_date'] ?? '');
        $sheet->setCellValue('Q' . $row, $machine['start_date'] ?? '');
        $sheet->setCellValue('R' . $row, $machine['register_date'] ?? '');
        $sheet->setCellValue('S' . $row, $status);
        $sheet->setCellValue('T' . $row, $machine['unit'] ?? '');
        $sheet->setCellValue('U' . $row, $machine['note'] ?? '');

        $row++;
    }

    // จัดรูปแบบข้อมูล
    $dataStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    $lastRow = $row - 1;
    $sheet->getStyle('A1:U' . $lastRow)->applyFromArray($dataStyle);

    // ปรับความกว้างคอลัมน์อัตโนมัติ
    foreach (range('A', 'U') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // จัดตำแหน่งข้อมูล
    $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('G2:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('N2:N' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('P2:R' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('S2:T' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // จัดรูปแบบตัวเลขราคา
    $sheet->getStyle('N2:N' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');

    // ส่งออกไฟล์
    $filename = 'Machine_Registry_' . date('Y-m-d') . '.xlsx';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
    exit;

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
