<?php
/**
 * سیستم خروجی اکسل پلاگین مدیریت کارکرد پرسنل
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * کلاس مدیریت خروجی اکسل
 */
class Workforce_Excel_Export {
    
    private $template;
    
    public function __construct() {
        // گرفتن قالب پیش‌فرض
        $this->template = $this->get_excel_template();
    }
    
    /**
     * خروجی اکسل برای مدیر اداره
     */
    public function export_excel() {
        // بررسی دسترسی
        if (!is_user_logged_in()) {
            wp_die('لطفا ابتدا وارد شوید.');
        }
        
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        
        // فقط مدیران اداره و سازمان می‌توانند خروجی بگیرند
        if (!in_array('workforce_dept_manager', $current_user->roles) && 
            !in_array('workforce_org_manager', $current_user->roles)) {
            wp_die('شما دسترسی لازم را ندارید.');
        }
        
        $department_id = intval($_GET['department_id'] ?? 0);
        $period_id = isset($_GET['period_id']) ? intval($_GET['period_id']) : null;
        $filters = isset($_GET['filters']) ? json_decode(stripslashes($_GET['filters']), true) : [];
        $search = sanitize_text_field($_GET['search'] ?? '');
        
        // اعتبارسنجی
        if (in_array('workforce_dept_manager', $current_user->roles)) {
            // مدیر اداره فقط می‌تواند از اداره خودش خروجی بگیرد
            $user_departments = workforce_get_user_departments($user_id);
            if (empty($user_departments)) {
                wp_die('شما به هیچ اداره‌ای دسترسی ندارید.');
            }
            
            $user_dept_ids = array_map(function($dept) {
                return $dept->id;
            }, $user_departments);
            
            if ($department_id > 0 && !in_array($department_id, $user_dept_ids)) {
                wp_die('شما دسترسی به این اداره را ندارید.');
            }
            
            // اگر department_id مشخص نشده، از اولین اداره کاربر استفاده کن
            if ($department_id == 0 && !empty($user_departments)) {
                $department_id = $user_departments[0]->id;
            }
        }
        
        // بررسی وجود کتابخانه PHPExcel
        if (!class_exists('PHPExcel')) {
            // استفاده از کتابخانه داخلی یا جایگزین
            $this->export_csv_fallback($department_id, $period_id, $filters, $search);
            return;
        }
        
        // ایجاد شیء PHPExcel
        $objPHPExcel = new PHPExcel();
        
        // تنظیم خصوصیات سند
        $objPHPExcel->getProperties()
            ->setCreator("پلاگین مدیریت کارکرد پرسنل - بنی اسد")
            ->setLastModifiedBy("سیستم مدیریت پرسنل")
            ->setTitle("گزارش پرسنل")
            ->setSubject("گزارش پرسنل")
            ->setDescription("گزارش خروجی پرسنل")
            ->setKeywords("پرسنل گزارش اکسل")
            ->setCategory("گزارش");
        
        // تنظیم شیت فعال
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('گزارش پرسنل');
        
        // تنظیم جهت راست به چپ
        $sheet->setRightToLeft(true);
        
        // گرفتن داده‌ها
        $data = $this->get_export_data($department_id, $period_id, $filters, $search);
        
        // ایجاد هدر
        $headers = ['ردیف', 'کدملی', 'نام', 'نام خانوادگی', 'نام اداره', 'تاریخ استخدام', 'نوع استخدام', 'وضعیت'];
        
        // اضافه کردن فیلدهای متا به هدر
        $fields = workforce_get_all_fields();
        foreach ($fields as $field) {
            if (!in_array($field->field_name, ['national_code', 'first_name', 'last_name', 'employment_date'])) {
                $headers[] = $field->field_label;
            }
        }
        
        // نوشتن هدر
        $col = 0;
        foreach ($headers as $header) {
            $cell = PHPExcel_Cell::stringFromColumnIndex($col) . '1';
            $sheet->setCellValue($cell, $header);
            
            // اعمال استایل به هدر
            $this->apply_header_style($sheet, $cell);
            $col++;
        }
        
        // نوشتن داده‌ها
        $row = 2;
        foreach ($data as $index => $item) {
            $col = 0;
            
            // ردیف
            $sheet->setCellValueByColumnAndIndex($col++, $row, $index + 1);
            
            // اطلاعات پایه
            $sheet->setCellValueByColumnAndIndex($col++, $row, $item['national_code']);
            $sheet->setCellValueByColumnAndIndex($col++, $row, $item['first_name']);
            $sheet->setCellValueByColumnAndIndex($col++, $row, $item['last_name']);
            $sheet->setCellValueByColumnAndIndex($col++, $row, $item['department_name']);
            $sheet->setCellValueByColumnAndIndex($col++, $row, $item['employment_date']);
            $sheet->setCellValueByColumnAndIndex($col++, $row, $item['employment_type']);
            $sheet->setCellValueByColumnAndIndex($col++, $row, $item['status']);
            
            // فیلدهای متا
            foreach ($fields as $field) {
                if (!in_array($field->field_name, ['national_code', 'first_name', 'last_name', 'employment_date'])) {
                    $value = $item['meta'][$field->id] ?? $item['meta'][$field->field_name] ?? '';
                    $sheet->setCellValueByColumnAndIndex($col++, $row, $value);
                }
            }
            
            // اعمال استایل به سطر
            $this->apply_data_row_style($sheet, $row, $col, $index + 1);
            $row++;
        }
        
        // تنظیم عرض ستون‌ها به صورت خودکار
        foreach (range('A', PHPExcel_Cell::stringFromColumnIndex(count($headers) - 1)) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        
        // اعمال border به همه سلول‌ها
        $lastColumn = PHPExcel_Cell::stringFromColumnIndex(count($headers) - 1);
        $lastRow = $row - 1;
        
        $styleArray = [
            'borders' => [
                'allborders' => [
                    'style' => $this->get_border_style($this->template->border_style),
                    'color' => ['rgb' => ltrim($this->template->border_color, '#')]
                ]
            ]
        ];
        
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray($styleArray);
        
        // تنظیم ارتفاع سطر هدر
        $sheet->getRowDimension(1)->setRowHeight(30);
        
        // فریز کردن هدر
        $sheet->freezePane('A2');
        
        // هدر برای دانلود
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="گزارش_پرسنل_' . date('Y-m-d_H-i') . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        // ایجاد Writer و خروجی
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        
        exit;
    }
    
    /**
     * خروجی اکسل برای مدیر سازمان
     */
    public function export_org_excel() {
        // بررسی دسترسی
        if (!is_user_logged_in()) {
            wp_die('لطفا ابتدا وارد شوید.');
        }
        
        $current_user = wp_get_current_user();
        
        // فقط مدیر سازمان می‌تواند خروجی سازمانی بگیرد
        if (!in_array('workforce_org_manager', $current_user->roles)) {
            wp_die('شما دسترسی لازم را ندارید.');
        }
        
        $department_id = $_GET['department_id'] ? intval($_GET['department_id']) : null;
        $status = sanitize_text_field($_GET['status'] ?? '');
        $search = sanitize_text_field($_GET['search'] ?? '');
        
        // بررسی وجود کتابخانه PHPExcel
        if (!class_exists('PHPExcel')) {
            // استفاده از CSV به عنوان جایگزین
            $this->export_org_csv_fallback($department_id, $status, $search);
            return;
        }
        
        // ایجاد شیء PHPExcel
        $objPHPExcel = new PHPExcel();
        
        // تنظیم خصوصیات سند
        $objPHPExcel->getProperties()
            ->setCreator("پلاگین مدیریت کارکرد پرسنل - بنی اسد")
            ->setLastModifiedBy("سیستم مدیریت پرسنل")
            ->setTitle("گزارش سازمانی پرسنل")
            ->setSubject("گزارش سازمانی")
            ->setDescription("گزارش خروجی سازمانی پرسنل")
            ->setKeywords("پرسنل گزارش سازمانی اکسل")
            ->setCategory("گزارش");
        
        // ایجاد چندین شیت
        $objPHPExcel->createSheet();
        $objPHPExcel->createSheet();
        
        // شیت ۱: گزارش تجمیعی
        $objPHPExcel->setActiveSheetIndex(0);
        $summary_sheet = $objPHPExcel->getActiveSheet();
        $summary_sheet->setTitle('گزارش تجمیعی');
        $summary_sheet->setRightToLeft(true);
        
        // شیت ۲: جزئیات پرسنل
        $objPHPExcel->setActiveSheetIndex(1);
        $details_sheet = $objPHPExcel->getActiveSheet();
        $details_sheet->setTitle('جزئیات پرسنل');
        $details_sheet->setRightToLeft(true);
        
        // شیت ۳: آمار ادارات
        $objPHPExcel->setActiveSheetIndex(2);
        $stats_sheet = $objPHPExcel->getActiveSheet();
        $stats_sheet->setTitle('آمار ادارات');
        $stats_sheet->setRightToLeft(true);
        
        // ========== شیت گزارش تجمیعی ==========
        $summary_data = $this->get_org_summary_data($department_id, $status, $search);
        
        $summary_headers = ['ردیف', 'نام اداره', 'مدیر اداره', 'تعداد پرسنل', 'پرسنل فعال', 'پرسنل غیرفعال', 
                           'درصد تکمیل اطلاعات', 'میانگین سابقه کار', 'آخرین به‌روزرسانی'];
        
        // نوشتن هدر شیت تجمیعی
        $col = 0;
        foreach ($summary_headers as $header) {
            $cell = PHPExcel_Cell::stringFromColumnIndex($col) . '1';
            $summary_sheet->setCellValue($cell, $header);
            $this->apply_header_style($summary_sheet, $cell);
            $col++;
        }
        
        // نوشتن داده‌های تجمیعی
        $row = 2;
        foreach ($summary_data as $index => $dept) {
            $col = 0;
            
            $summary_sheet->setCellValueByColumnAndIndex($col++, $row, $index + 1);
            $summary_sheet->setCellValueByColumnAndIndex($col++, $row, $dept['name']);
            $summary_sheet->setCellValueByColumnAndIndex($col++, $row, $dept['manager']);
            $summary_sheet->setCellValueByColumnAndIndex($col++, $row, $dept['total_personnel']);
            $summary_sheet->setCellValueByColumnAndIndex($col++, $row, $dept['active_personnel']);
            $summary_sheet->setCellValueByColumnAndIndex($col++, $row, $dept['inactive_personnel']);
            $summary_sheet->setCellValueByColumnAndIndex($col++, $row, $dept['completion_rate'] . '%');
            $summary_sheet->setCellValueByColumnAndIndex($col++, $row, $dept['avg_experience']);
            $summary_sheet->setCellValueByColumnAndIndex($col++, $row, $dept['last_update']);
            
            $this->apply_data_row_style($summary_sheet, $row, $col, $index + 1);
            $row++;
        }
        
        // ========== شیت جزئیات پرسنل ==========
        $details_data = $this->get_org_details_data($department_id, $status, $search);
        
        $details_headers = ['ردیف', 'نام اداره', 'کدملی', 'نام', 'نام خانوادگی', 'تاریخ استخدام', 
                           'نوع استخدام', 'وضعیت', 'سن', 'سابقه کار', 'آخرین ویرایش'];
        
        // اضافه کردن فیلدهای مهم
        $important_fields = $this->get_important_fields();
        foreach ($important_fields as $field) {
            $details_headers[] = $field->field_label;
        }
        
        // نوشتن هدر شیت جزئیات
        $col = 0;
        foreach ($details_headers as $header) {
            $cell = PHPExcel_Cell::stringFromColumnIndex($col) . '1';
            $details_sheet->setCellValue($cell, $header);
            $this->apply_header_style($details_sheet, $cell);
            $col++;
        }
        
        // نوشتن داده‌های جزئیات
        $row = 2;
        foreach ($details_data as $index => $person) {
            $col = 0;
            
            $details_sheet->setCellValueByColumnAndIndex($col++, $row, $index + 1);
            $details_sheet->setCellValueByColumnAndIndex($col++, $row, $person['department_name']);
            $details_sheet->setCellValueByColumnAndIndex($col++, $row, $person['national_code']);
            $details_sheet->setCellValueByColumnAndIndex($col++, $row, $person['first_name']);
            $details_sheet->setCellValueByColumnAndIndex($col++, $row, $person['last_name']);
            $details_sheet->setCellValueByColumnAndIndex($col++, $row, $person['employment_date']);
            $details_sheet->setCellValueByColumnAndIndex($col++, $row, $person['employment_type']);
            $details_sheet->setCellValueByColumnAndIndex($col++, $row, $person['status']);
            $details_sheet->setCellValueByColumnAndIndex($col++, $row, $person['age']);
            $details_sheet->setCellValueByColumnAndIndex($col++, $row, $person['experience']);
            $details_sheet->setCellValueByColumnAndIndex($col++, $row, $person['last_edit']);
            
            // فیلدهای مهم
            foreach ($important_fields as $field) {
                $value = $person['meta'][$field->id] ?? $person['meta'][$field->field_name] ?? '';
                $details_sheet->setCellValueByColumnAndIndex($col++, $row, $value);
            }
            
            $this->apply_data_row_style($details_sheet, $row, $col, $index + 1);
            $row++;
        }
        
        // ========== شیت آمار ادارات ==========
        $stats_data = $this->get_department_stats_data();
        
        $stats_headers = ['ردیف', 'نام اداره', 'رنگ', 'تعداد پرسنل', 'توزیع وضعیت', 'توزیع نوع استخدام', 
                         'میانگین سن', 'میانگین سابقه', 'فیلدهای تکمیل شده', 'فیلدهای ناقص'];
        
        // نوشتن هدر شیت آمار
        $col = 0;
        foreach ($stats_headers as $header) {
            $cell = PHPExcel_Cell::stringFromColumnIndex($col) . '1';
            $stats_sheet->setCellValue($cell, $header);
            $this->apply_header_style($stats_sheet, $cell);
            $col++;
        }
        
        // نوشتن داده‌های آمار
        $row = 2;
        foreach ($stats_data as $index => $stat) {
            $col = 0;
            
            $stats_sheet->setCellValueByColumnAndIndex($col++, $row, $index + 1);
            $stats_sheet->setCellValueByColumnAndIndex($col++, $row, $stat['name']);
            
            // سلول رنگ
            $color_cell = PHPExcel_Cell::stringFromColumnIndex($col) . $row;
            $stats_sheet->setCellValueByColumnAndIndex($col++, $row, '');
            $color_style = $stats_sheet->getStyle($color_cell);
            $color_style->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            $color_style->getFill()->getStartColor()->setRGB(ltrim($stat['color'], '#'));
            
            $stats_sheet->setCellValueByColumnAndIndex($col++, $row, $stat['total_personnel']);
            $stats_sheet->setCellValueByColumnAndIndex($col++, $row, $stat['status_distribution']);
            $stats_sheet->setCellValueByColumnAndIndex($col++, $row, $stat['employment_distribution']);
            $stats_sheet->setCellValueByColumnAndIndex($col++, $row, $stat['avg_age']);
            $stats_sheet->setCellValueByColumnAndIndex($col++, $row, $stat['avg_experience']);
            $stats_sheet->setCellValueByColumnAndIndex($col++, $row, $stat['completed_fields']);
            $stats_sheet->setCellValueByColumnAndIndex($col++, $row, $stat['incomplete_fields']);
            
            $this->apply_data_row_style($stats_sheet, $row, $col, $index + 1);
            $row++;
        }
        
        // تنظیم عرض ستون‌ها در همه شیت‌ها
        foreach ($objPHPExcel->getAllSheets() as $sheet) {
            foreach (range('A', $sheet->getHighestDataColumn()) as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }
            $sheet->freezePane('A2');
        }
        
        // بازگشت به شیت اول
        $objPHPExcel->setActiveSheetIndex(0);
        
        // هدر برای دانلود
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="گزارش_سازمانی_' . date('Y-m-d_H-i') . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        // ایجاد Writer و خروجی
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        
        exit;
    }
    
    /**
     * گرفتن قالب اکسل
     */
    private function get_excel_template() {
        global $wpdb;
        $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'excel_templates';
        
        $template = $wpdb->get_row("SELECT * FROM $table_name WHERE is_default = 1 LIMIT 1");
        
        if (!$template) {
            // ایجاد یک قالب پیش‌فرض
            $template = (object) [
                'header_color' => '#2c3e50',
                'text_color' => '#333333',
                'even_row_color' => '#f8f9fa',
                'odd_row_color' => '#ffffff',
                'border_style' => 'thin',
                'border_color' => '#dddddd',
                'header_font_size' => 12,
                'data_font_size' => 11
            ];
        }
        
        return $template;
    }
    
    /**
     * اعمال استایل به هدر
     */
    private function apply_header_style($sheet, $cell) {
        $style = $sheet->getStyle($cell);
        $style->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $style->getFill()->getStartColor()->setRGB(ltrim($this->template->header_color, '#'));
        $style->getFont()->setBold(true);
        $style->getFont()->setSize($this->template->header_font_size);
        $style->getFont()->getColor()->setRGB('FFFFFF');
        $style->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $style->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        
        // تنظیم ارتفاع سطر
        $row = PHPExcel_Cell::coordinateFromString($cell)[1];
        $sheet->getRowDimension($row)->setRowHeight(30);
    }
    
    /**
     * اعمال استایل به سطر داده
     */
    private function apply_data_row_style($sheet, $row, $col_count, $row_index) {
        $start_col = 'A';
        $end_col = PHPExcel_Cell::stringFromColumnIndex($col_count - 1);
        
        $style = $sheet->getStyle("{$start_col}{$row}:{$end_col}{$row}");
        
        // رنگ‌بندی ردیف‌های زوج و فرد
        $row_color = $row_index % 2 == 0 ? $this->template->even_row_color : $this->template->odd_row_color;
        $style->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $style->getFill()->getStartColor()->setRGB(ltrim($row_color, '#'));
        
        // فونت
        $style->getFont()->setSize($this->template->data_font_size);
        $style->getFont()->getColor()->setRGB(ltrim($this->template->text_color, '#'));
        
        // تراز
        $style->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $style->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        
        // border
        $border_style = $this->get_border_style($this->template->border_style);
        $style->getBorders()->getAllBorders()->setBorderStyle($border_style);
        $style->getBorders()->getAllBorders()->getColor()->setRGB(ltrim($this->template->border_color, '#'));
    }
    
    /**
     * تبدیل استایل border
     */
    private function get_border_style($style_name) {
        $styles = [
            'thin' => PHPExcel_Style_Border::BORDER_THIN,
            'medium' => PHPExcel_Style_Border::BORDER_MEDIUM,
            'thick' => PHPExcel_Style_Border::BORDER_THICK,
            'dotted' => PHPExcel_Style_Border::BORDER_DOTTED,
            'dashed' => PHPExcel_Style_Border::BORDER_DASHED
        ];
        
        return $styles[$style_name] ?? PHPExcel_Style_Border::BORDER_THIN;
    }
    
    /**
     * گرفتن داده‌های خروجی برای مدیر اداره
     */
    private function get_export_data($department_id, $period_id = null, $filters = [], $search = '') {
        global $wpdb;
        
        $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
        $departments_table = $wpdb->prefix . WF_TABLE_PREFIX . 'departments';
        $meta_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel_meta';
        
        // ساختن کوئری
        $query = "SELECT p.*, d.name as department_name 
                  FROM $personnel_table p 
                  INNER JOIN $departments_table d ON p.department_id = d.id 
                  WHERE p.department_id = %d AND p.is_deleted = 0";
        
        $params = [$department_id];
        
        // اعمال فیلترها
        if (!empty($filters)) {
            foreach ($filters as $field_id => $values) {
                if ($field_id === 'status') {
                    $query .= " AND p.status = %s";
                    $params[] = $values;
                } elseif ($field_id === 'employment_type') {
                    $query .= " AND p.employment_type = %s";
                    $params[] = $values;
                }
            }
        }
        
        // اعمال جستجو
        if (!empty($search)) {
            $query .= " AND (p.first_name LIKE %s OR p.last_name LIKE %s OR p.national_code LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        $query .= " ORDER BY p.last_name ASC, p.first_name ASC";
        
        $personnel = $wpdb->get_results($wpdb->prepare($query, $params));
        
        // اضافه کردن داده‌های متا
        $fields = workforce_get_all_fields();
        $result = [];
        
        foreach ($personnel as $person) {
            $item = [
                'national_code' => $person->national_code,
                'first_name' => $person->first_name,
                'last_name' => $person->last_name,
                'department_name' => $person->department_name,
                'employment_date' => $person->employment_date,
                'employment_type' => $this->get_employment_type_label($person->employment_type),
                'status' => $this->get_status_label($person->status),
                'meta' => []
            ];
            
            // گرفتن داده‌های متا
            foreach ($fields as $field) {
                if (!in_array($field->field_name, ['national_code', 'first_name', 'last_name', 'employment_date'])) {
                    $value = workforce_get_personnel_field_value($person->id, $field->field_name, $period_id);
                    $item['meta'][$field->id] = $value;
                    $item['meta'][$field->field_name] = $value;
                }
            }
            
            $result[] = $item;
        }
        
        return $result;
    }
    
    /**
     * برچسب نوع استخدام
     */
    private function get_employment_type_label($type) {
        $labels = [
            'permanent' => 'دائمی',
            'contract' => 'پیمانی',
            'temporary' => 'موقت',
            'project' => 'پروژه‌ای'
        ];
        
        return $labels[$type] ?? $type;
    }
    
    /**
     * برچسب وضعیت
     */
    private function get_status_label($status) {
        $labels = [
            'active' => 'فعال',
            'inactive' => 'غیرفعال',
            'suspended' => 'تعلیق',
            'retired' => 'بازنشسته'
        ];
        
        return $labels[$status] ?? $status;
    }
    
    /**
     * گرفتن داده‌های تجمیعی برای مدیر سازمان
     */
    private function get_org_summary_data($department_id = null, $status = '', $search = '') {
        global $wpdb;
        
        $departments_table = $wpdb->prefix . WF_TABLE_PREFIX . 'departments';
        $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
        $users_table = $wpdb->users;
        
        $query = "SELECT d.id, d.name, d.color, d.manager_id, u.display_name as manager_name,
                         COUNT(p.id) as total_personnel,
                         SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END) as active_personnel,
                         SUM(CASE WHEN p.status != 'active' THEN 1 ELSE 0 END) as inactive_personnel,
                         MAX(p.updated_at) as last_update
                  FROM $departments_table d
                  LEFT JOIN $personnel_table p ON d.id = p.department_id AND p.is_deleted = 0
                  LEFT JOIN $users_table u ON d.manager_id = u.ID
                  WHERE d.is_active = 1";
        
        $params = [];
        
        if ($department_id) {
            $query .= " AND d.id = %d";
            $params[] = $department_id;
        }
        
        $query .= " GROUP BY d.id, d.name, d.color, d.manager_id, u.display_name
                    ORDER BY d.name ASC";
        
        $departments = $wpdb->get_results($wpdb->prepare($query, $params));
        
        $result = [];
        foreach ($departments as $dept) {
            // محاسبه درصد تکمیل اطلاعات
            $completion_rate = $this->calculate_completion_rate($dept->id);
            
            // محاسبه میانگین سابقه کار
            $avg_experience = $this->calculate_avg_experience($dept->id);
            
            $result[] = [
                'id' => $dept->id,
                'name' => $dept->name,
                'color' => $dept->color,
                'manager' => $dept->manager_name ?: 'تعیین نشده',
                'total_personnel' => $dept->total_personnel,
                'active_personnel' => $dept->active_personnel,
                'inactive_personnel' => $dept->inactive_personnel,
                'completion_rate' => $completion_rate,
                'avg_experience' => $avg_experience,
                'last_update' => $dept->last_update ? wp_date('Y/m/d H:i', strtotime($dept->last_update)) : 'ندارد'
            ];
        }
        
        return $result;
    }
    
    /**
     * محاسبه درصد تکمیل اطلاعات اداره
     */
    private function calculate_completion_rate($department_id) {
        global $wpdb;
        
        $fields_table = $wpdb->prefix . WF_TABLE_PREFIX . 'fields';
        $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
        $meta_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel_meta';
        
        // تعداد فیلدهای ضروری
        $required_fields = $wpdb->get_results(
            "SELECT id, field_name FROM $fields_table WHERE is_required = 1"
        );
        
        if (empty($required_fields)) {
            return 100;
        }
        
        // تعداد پرسنل
        $total_personnel = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $personnel_table WHERE department_id = %d AND is_deleted = 0",
            $department_id
        ));
        
        if ($total_personnel == 0) {
            return 100;
        }
        
        $total_required = count($required_fields) * $total_personnel;
        $completed_count = 0;
        
        foreach ($required_fields as $field) {
            $completed = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT pm.personnel_id) 
                 FROM $meta_table pm 
                 INNER JOIN $personnel_table p ON pm.personnel_id = p.id 
                 WHERE p.department_id = %d AND p.is_deleted = 0 
                 AND pm.meta_key = %s AND pm.meta_value != ''",
                $department_id, $field->field_name
            ));
            $completed_count += $completed;
        }
        
        return $total_required > 0 ? round(($completed_count / $total_required) * 100, 2) : 100;
    }
    
    /**
     * محاسبه میانگین سابقه کار اداره
     */
    private function calculate_avg_experience($department_id) {
        global $wpdb;
        
        $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
        
        $employment_dates = $wpdb->get_col($wpdb->prepare(
            "SELECT employment_date FROM $personnel_table 
             WHERE department_id = %d AND is_deleted = 0 AND employment_date IS NOT NULL AND employment_date != ''",
            $department_id
        ));
        
        if (empty($employment_dates)) {
            return 'ندارد';
        }
        
        $total_experience = 0;
        $count = 0;
        
        foreach ($employment_dates as $date) {
            if (preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', $date, $matches)) {
                list($gy, $gm, $gd) = workforce_jalali_to_gregorian(
                    (int) $matches[1],
                    (int) $matches[2],
                    (int) $matches[3]
                );
                
                $employment_timestamp = mktime(0, 0, 0, $gm, $gd, $gy);
                $current_timestamp = current_time('timestamp');
                
                $experience = date('Y', $current_timestamp) - date('Y', $employment_timestamp);
                
                if (date('md', $current_timestamp) < date('md', $employment_timestamp)) {
                    $experience--;
                }
                
                $total_experience += max(0, $experience);
                $count++;
            }
        }
        
        if ($count == 0) {
            return 'ندارد';
        }
        
        $avg_experience = round($total_experience / $count, 1);
        return $avg_experience . ' سال';
    }
    
    /**
     * گرفتن داده‌های جزئیات برای مدیر سازمان
     */
    private function get_org_details_data($department_id = null, $status = '', $search = '') {
        global $wpdb;
        
        $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
        $departments_table = $wpdb->prefix . WF_TABLE_PREFIX . 'departments';
        $meta_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel_meta';
        
        $query = "SELECT p.*, d.name as department_name, d.color as department_color 
                  FROM $personnel_table p 
                  INNER JOIN $departments_table d ON p.department_id = d.id 
                  WHERE p.is_deleted = 0";
        
        $params = [];
        
        if ($department_id) {
            $query .= " AND p.department_id = %d";
            $params[] = $department_id;
        }
        
        if ($status) {
            $query .= " AND p.status = %s";
            $params[] = $status;
        }
        
        if ($search) {
            $query .= " AND (p.first_name LIKE %s OR p.last_name LIKE %s OR p.national_code LIKE %s OR d.name LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        $query .= " ORDER BY d.name ASC, p.last_name ASC, p.first_name ASC";
        
        $personnel = $wpdb->get_results($wpdb->prepare($query, $params));
        
        $important_fields = $this->get_important_fields();
        $result = [];
        
        foreach ($personnel as $person) {
            // محاسبه سن
            $age = $this->calculate_age($person->birth_date);
            
            // محاسبه سابقه کار
            $experience = $this->calculate_experience($person->employment_date);
            
            $item = [
                'id' => $person->id,
                'department_name' => $person->department_name,
                'national_code' => $person->national_code,
                'first_name' => $person->first_name,
                'last_name' => $person->last_name,
                'employment_date' => $person->employment_date,
                'employment_type' => $this->get_employment_type_label($person->employment_type),
                'status' => $this->get_status_label($person->status),
                'age' => $age ? $age . ' سال' : 'نامشخص',
                'experience' => $experience ? $experience . ' سال' : 'نامشخص',
                'last_edit' => $person->updated_at ? wp_date('Y/m/d H:i', strtotime($person->updated_at)) : 'ندارد',
                'meta' => []
            ];
            
            // گرفتن فیلدهای مهم
            foreach ($important_fields as $field) {
                $value = workforce_get_personnel_field_value($person->id, $field->field_name);
                $item['meta'][$field->id] = $value;
                $item['meta'][$field->field_name] = $value;
            }
            
            $result[] = $item;
        }
        
        return $result;
    }
    
    /**
     * محاسبه سن از تاریخ تولد
     */
    private function calculate_age($birth_date) {
        if (!$birth_date) {
            return null;
        }
        
        if (!preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', $birth_date, $matches)) {
            return null;
        }
        
        list($gy, $gm, $gd) = workforce_jalali_to_gregorian(
            (int) $matches[1],
            (int) $matches[2],
            (int) $matches[3]
        );
        
        $birth_timestamp = mktime(0, 0, 0, $gm, $gd, $gy);
        $current_timestamp = current_time('timestamp');
        
        $age = date('Y', $current_timestamp) - date('Y', $birth_timestamp);
        
        if (date('md', $current_timestamp) < date('md', $birth_timestamp)) {
            $age--;
        }
        
        return $age;
    }
    
    /**
     * محاسبه سابقه کار از تاریخ استخدام
     */
    private function calculate_experience($employment_date) {
        if (!$employment_date) {
            return null;
        }
        
        if (!preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', $employment_date, $matches)) {
            return null;
        }
        
        list($gy, $gm, $gd) = workforce_jalali_to_gregorian(
            (int) $matches[1],
            (int) $matches[2],
            (int) $matches[3]
        );
        
        $employment_timestamp = mktime(0, 0, 0, $gm, $gd, $gy);
        $current_timestamp = current_time('timestamp');
        
        $experience = date('Y', $current_timestamp) - date('Y', $employment_timestamp);
        
        if (date('md', $current_timestamp) < date('md', $employment_timestamp)) {
            $experience--;
        }
        
        return max(0, $experience);
    }
    
    /**
     * گرفتن فیلدهای مهم برای گزارش
     */
    private function get_important_fields() {
        $all_fields = workforce_get_all_fields();
        $important_fields = [];
        
        // فیلدهای مانیتورینگ و ضروری در اولویت هستند
        foreach ($all_fields as $field) {
            if ($field->is_monitoring || $field->is_required) {
                if (!in_array($field->field_name, ['national_code', 'first_name', 'last_name', 'employment_date'])) {
                    $important_fields[] = $field;
                }
            }
        }
        
        // اگر کمتر از ۵ فیلد مهم داریم، بقیه فیلدها را اضافه کن
        if (count($important_fields) < 5) {
            foreach ($all_fields as $field) {
                if (!in_array($field->field_name, ['national_code', 'first_name', 'last_name', 'employment_date'])) {
                    $already_exists = false;
                    foreach ($important_fields as $imp_field) {
                        if ($imp_field->id == $field->id) {
                            $already_exists = true;
                            break;
                        }
                    }
                    
                    if (!$already_exists && count($important_fields) < 10) {
                        $important_fields[] = $field;
                    }
                }
            }
        }
        
        return $important_fields;
    }
    
    /**
     * گرفتن داده‌های آمار ادارات
     */
    private function get_department_stats_data() {
        global $wpdb;
        
        $departments_table = $wpdb->prefix . WF_TABLE_PREFIX . 'departments';
        $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
        
        $query = "SELECT d.id, d.name, d.color,
                         COUNT(p.id) as total_personnel,
                         GROUP_CONCAT(DISTINCT p.status) as statuses,
                         GROUP_CONCAT(DISTINCT p.employment_type) as employment_types
                  FROM $departments_table d
                  LEFT JOIN $personnel_table p ON d.id = p.department_id AND p.is_deleted = 0
                  WHERE d.is_active = 1
                  GROUP BY d.id, d.name, d.color
                  ORDER BY d.name ASC";
        
        $departments = $wpdb->get_results($query);
        
        $result = [];
        foreach ($departments as $dept) {
            // توزیع وضعیت
            $status_distribution = $this->get_status_distribution($dept->id);
            
            // توزیع نوع استخدام
            $employment_distribution = $this->get_employment_distribution($dept->id);
            
            // میانگین سن
            $avg_age = $this->calculate_dept_avg_age($dept->id);
            
            // میانگین سابقه کار
            $avg_experience = $this->calculate_dept_avg_experience($dept->id);
            
            // فیلدهای تکمیل شده و ناقص
            $field_stats = $this->get_dept_field_stats($dept->id);
            
            $result[] = [
                'id' => $dept->id,
                'name' => $dept->name,
                'color' => $dept->color,
                'total_personnel' => $dept->total_personnel,
                'status_distribution' => $status_distribution,
                'employment_distribution' => $employment_distribution,
                'avg_age' => $avg_age,
                'avg_experience' => $avg_experience,
                'completed_fields' => $field_stats['completed'],
                'incomplete_fields' => $field_stats['incomplete']
            ];
        }
        
        return $result;
    }
    
    /**
     * گرفتن توزیع وضعیت پرسنل اداره
     */
    private function get_status_distribution($department_id) {
        global $wpdb;
        
        $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
        
        $statuses = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) as count 
             FROM $personnel_table 
             WHERE department_id = %d AND is_deleted = 0 
             GROUP BY status 
             ORDER BY count DESC",
            $department_id
        ));
        
        $distribution = [];
        foreach ($statuses as $status) {
            $label = $this->get_status_label($status->status);
            $distribution[] = $label . ': ' . $status->count;
        }
        
        return implode(' | ', $distribution);
    }
    
    /**
     * گرفتن توزیع نوع استخدام اداره
     */
    private function get_employment_distribution($department_id) {
        global $wpdb;
        
        $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
        
        $types = $wpdb->get_results($wpdb->prepare(
            "SELECT employment_type, COUNT(*) as count 
             FROM $personnel_table 
             WHERE department_id = %d AND is_deleted = 0 
             GROUP BY employment_type 
             ORDER BY count DESC",
            $department_id
        ));
        
        $distribution = [];
        foreach ($types as $type) {
            $label = $this->get_employment_type_label($type->employment_type);
            $distribution[] = $label . ': ' . $type->count;
        }
        
        return implode(' | ', $distribution);
    }
    
    /**
     * محاسبه میانگین سن اداره
     */
    private function calculate_dept_avg_age($department_id) {
        global $wpdb;
        
        $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
        $meta_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel_meta';
        
        // گرفتن تاریخ تولد همه پرسنل
        $birth_dates = $wpdb->get_col($wpdb->prepare(
            "SELECT pm.meta_value 
             FROM $meta_table pm 
             INNER JOIN $personnel_table p ON pm.personnel_id = p.id 
             WHERE p.department_id = %d AND p.is_deleted = 0 
             AND pm.meta_key = 'birth_date' AND pm.meta_value != ''",
            $department_id
        ));
        
        if (empty($birth_dates)) {
            return 'ندارد';
        }
        
        $total_age = 0;
        $count = 0;
        
        foreach ($birth_dates as $birth_date) {
            $age = $this->calculate_age($birth_date);
            if ($age !== null) {
                $total_age += $age;
                $count++;
            }
        }
        
        if ($count == 0) {
            return 'ندارد';
        }
        
        $avg_age = round($total_age / $count, 1);
        return $avg_age . ' سال';
    }
    
    /**
     * محاسبه میانگین سابقه کار اداره
     */
    private function calculate_dept_avg_experience($department_id) {
        global $wpdb;
        
        $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
        
        $employment_dates = $wpdb->get_col($wpdb->prepare(
            "SELECT employment_date 
             FROM $personnel_table 
             WHERE department_id = %d AND is_deleted = 0 
             AND employment_date IS NOT NULL AND employment_date != ''",
            $department_id
        ));
        
        if (empty($employment_dates)) {
            return 'ندارد';
        }
        
        $total_experience = 0;
        $count = 0;
        
        foreach ($employment_dates as $date) {
            $experience = $this->calculate_experience($date);
            if ($experience !== null) {
                $total_experience += $experience;
                $count++;
            }
        }
        
        if ($count == 0) {
            return 'ندارد';
        }
        
        $avg_experience = round($total_experience / $count, 1);
        return $avg_experience . ' سال';
    }
    
    /**
     * گرفتن آمار فیلدهای اداره
     */
    private function get_dept_field_stats($department_id) {
        global $wpdb;
        
        $fields_table = $wpdb->prefix . WF_TABLE_PREFIX . 'fields';
        $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
        $meta_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel_meta';
        
        // تعداد فیلدها
        $total_fields = $wpdb->get_var("SELECT COUNT(*) FROM $fields_table");
        
        // تعداد پرسنل
        $total_personnel = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $personnel_table WHERE department_id = %d AND is_deleted = 0",
            $department_id
        ));
        
        $total_possible = $total_fields * $total_personnel;
        
        // تعداد فیلدهای پر شده
        $completed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
             FROM $meta_table pm 
             INNER JOIN $personnel_table p ON pm.personnel_id = p.id 
             WHERE p.department_id = %d AND p.is_deleted = 0 
             AND pm.meta_value != ''",
            $department_id
        ));
        
        $incomplete = $total_possible - $completed;
        
        return [
            'completed' => $completed,
            'incomplete' => $incomplete
        ];
    }
    
    /**
     * جایگزین CSV برای وقتی که PHPExcel وجود ندارد
     */
    private function export_csv_fallback($department_id, $period_id, $filters, $search) {
        $data = $this->get_export_data($department_id, $period_id, $filters, $search);
        
        // هدر برای دانلود CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="گزارش_پرسنل_' . date('Y-m-d_H-i') . '.csv"');
        header('Cache-Control: max-age=0');
        
        // ایجاد خروجی
        $output = fopen('php://output', 'w');
        
        // نوشتن BOM برای UTF-8
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
        
        // نوشتن هدر
        $headers = ['ردیف', 'کدملی', 'نام', 'نام خانوادگی', 'نام اداره', 'تاریخ استخدام', 'نوع استخدام', 'وضعیت'];
        
        $fields = workforce_get_all_fields();
        foreach ($fields as $field) {
            if (!in_array($field->field_name, ['national_code', 'first_name', 'last_name', 'employment_date'])) {
                $headers[] = $field->field_label;
            }
        }
        
        fputcsv($output, $headers);
        
        // نوشتن داده‌ها
        foreach ($data as $index => $item) {
            $row = [
                $index + 1,
                $item['national_code'],
                $item['first_name'],
                $item['last_name'],
                $item['department_name'],
                $item['employment_date'],
                $item['employment_type'],
                $item['status']
            ];
            
            foreach ($fields as $field) {
                if (!in_array($field->field_name, ['national_code', 'first_name', 'last_name', 'employment_date'])) {
                    $value = $item['meta'][$field->id] ?? $item['meta'][$field->field_name] ?? '';
                    $row[] = $value;
                }
            }
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * جایگزین CSV برای گزارش سازمانی
     */
    private function export_org_csv_fallback($department_id, $status, $search) {
        $data = $this->get_org_details_data($department_id, $status, $search);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="گزارش_سازمانی_' . date('Y-m-d_H-i') . '.csv"');
        header('Cache-Control: max-age=0');
        
        $output = fopen('php://output', 'w');
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
        
        // هدر
        $headers = ['ردیف', 'نام اداره', 'کدملی', 'نام', 'نام خانوادگی', 'تاریخ استخدام', 
                   'نوع استخدام', 'وضعیت', 'سن', 'سابقه کار', 'آخرین ویرایش'];
        
        $important_fields = $this->get_important_fields();
        foreach ($important_fields as $field) {
            $headers[] = $field->field_label;
        }
        
        fputcsv($output, $headers);
        
        // داده‌ها
        foreach ($data as $index => $person) {
            $row = [
                $index + 1,
                $person['department_name'],
                $person['national_code'],
                $person['first_name'],
                $person['last_name'],
                $person['employment_date'],
                $person['employment_type'],
                $person['status'],
                $person['age'],
                $person['experience'],
                $person['last_edit']
            ];
            
            foreach ($important_fields as $field) {
                $value = $person['meta'][$field->id] ?? $person['meta'][$field->field_name] ?? '';
                $row[] = $value;
            }
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}

/**
 * توابع عمومی برای دسترسی به کلاس
 */
function workforce_export_excel_handler() {
    $exporter = new Workforce_Excel_Export();
    $exporter->export_excel();
}
add_action('wp_ajax_workforce_export_excel', 'workforce_export_excel_handler');
add_action('wp_ajax_nopriv_workforce_export_excel', 'workforce_export_excel_handler');

function workforce_export_org_excel_handler() {
    $exporter = new Workforce_Excel_Export();
    $exporter->export_org_excel();
}
add_action('wp_ajax_workforce_export_org_excel', 'workforce_export_org_excel_handler');
add_action('wp_ajax_nopriv_workforce_export_org_excel', 'workforce_export_org_excel_handler');
