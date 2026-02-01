<?php
/**
 * خروجی اکسل پیشرفته
 */

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class WorkforceExcelExporter {
    
    public static function export($data, $filename = 'کارکرد_پرسنل.xlsx') {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // تنظیمات صفحه
        $sheet->setRightToLeft(true);
        $sheet->getPageSetup()
              ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
              ->setPaperSize(PageSetup::PAPERSIZE_A4);
        
        // ایجاد استایل‌ها
        $styles = self::get_styles();
        
        // نوشتن هدر
        self::write_header($sheet, $data['headers'], $styles);
        
        // نوشتن داده‌ها
        self::write_data($sheet, $data['rows'], $styles);
        
        // فرمت‌بندی ستون‌ها
        self::format_columns($sheet, $data['columns_format']);
        
        // ایجاد فایل
        $writer = new Xlsx($spreadsheet);
        
        // ذخیره در حافظه
        ob_start();
        $writer->save('php://output');
        $excelData = ob_get_clean();
        
        return [
            'data' => $excelData,
            'filename' => $filename,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
    }
    
    private static function get_styles() {
        return [
            'header' => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                    'name' => 'B Nazanin'
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2C3E50']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '34495E']
                    ]
                ]
            ],
            'data' => [
                'font' => [
                    'size' => 10,
                    'name' => 'B Nazanin'
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'BDC3C7']
                    ]
                ]
            ],
            'total' => [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'color' => ['rgb' => 'FFFFFF'],
                    'name' => 'B Nazanin'
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '27AE60']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '2ECC71']
                    ]
                ]
            ]
        ];
    }
    
    private static function write_header($sheet, $headers, $styles) {
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, 1, $header);
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            $col++;
        }
        
        $lastCol = $col - 1;
        $sheet->getStyle("A1:" . $sheet->getColumnDimensionByColumn($lastCol)->getColumnIndex() . "1")
              ->applyFromArray($styles['header']);
        
        // ارتفاع ردیف هدر
        $sheet->getRowDimension(1)->setRowHeight(35);
    }
    
    private static function write_data($sheet, $rows, $styles) {
        $row = 2;
        foreach ($rows as $rowData) {
            $col = 1;
            foreach ($rowData as $cellData) {
                $sheet->setCellValueByColumnAndRow($col, $row, $cellData);
                $col++;
            }
            
            // ارتفاع ردیف
            $sheet->getRowDimension($row)->setRowHeight(25);
            $row++;
        }
        
        // اعمال استایل به داده‌ها
        $lastRow = $row - 1;
        $lastCol = count($rowData);
        $sheet->getStyle("A2:" . $sheet->getColumnDimensionByColumn($lastCol)->getColumnIndex() . $lastRow)
              ->applyFromArray($styles['data']);
    }
    
    private static function format_columns($sheet, $formats) {
        foreach ($formats as $colIndex => $format) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            
            switch ($format) {
                case 'number':
                    $sheet->getStyle($colLetter . ':' . $colLetter)
                          ->getNumberFormat()
                          ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    break;
                    
                case 'decimal':
                    $sheet->getStyle($colLetter . ':' . $colLetter)
                          ->getNumberFormat()
                          ->setFormatCode('#,##0.00');
                    break;
                    
                case 'date':
                    $sheet->getStyle($colLetter . ':' . $colLetter)
                          ->getNumberFormat()
                          ->setFormatCode('yyyy/mm/dd');
                    break;
            }
        }
    }
}