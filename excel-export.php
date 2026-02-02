<?php
/**
 * سیستم خروجی اکسل - سیستم مدیریت کارکرد پرسنل
 * تولید فایل Excel با PHPExcel و قابلیت‌های پیشرفته
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// بررسی وجود کلاس PHPExcel
if (!class_exists('PHPExcel')) {
    require_once WF_PLUGIN_DIR . 'vendor/PHPExcel/PHPExcel.php';
}

// ==================== کلاس اصلی خروجی اکسل ====================

class WF_Excel_Exporter {
    
    private $excel;
    private $worksheet;
    private $current_row = 1;
    private $template_settings = array();
    private $fields = array();
    private $data = array();
    
    /**
     * سازنده کلاس
     */
    public function __construct($template_id = null) {
        $this->excel = new PHPExcel();
        $this->worksheet = $this->excel->getActiveSheet();
        
        // بارگذاری قالب
        $this->load_template($template_id);
        
        // تنظیمات پیش‌فرض
        $this->excel->getDefaultStyle()->getFont()->setName('Tahoma');
        $this->excel->getDefaultStyle()->getFont()->setSize(10);
        $this->worksheet->setRightToLeft(true);
    }
    
    /**
     * بارگذاری قالب
     */
    private function load_template($template_id = null) {
        if ($template_id) {
            $template = wf_get_excel_template($template_id);
            if ($template) {
                $this->template_settings = json_decode($template['settings'], true);
            }
        }
        
        // تنظیمات پیش‌فرض
        $default_settings = array(
            'header' => array(
                'bg_color' => '1a73e8',
                'font_color' => 'ffffff',
                'font_size' => 12,
                'font_bold' => true,
                'alignment' => 'center',
                'height' => 30
            ),
            'data' => array(
                'even_row_color' => 'f8f9fa',
                'odd_row_color' => 'ffffff',
                'font_color' => '202124',
                'font_size' => 10,
                'alignment' => 'right',
                'height' => 25
            ),
            'borders' => array(
                'style' => 'thin',
                'color' => 'dadce0'
            ),
            'columns' => array(
                'auto_width' => true,
                'wrap_text' => true
            ),
            'footer' => array(
                'include' => true,
                'text' => 'تولید شده توسط سیستم مدیریت کارکرد پرسنل',
                'font_size' => 9,
                'font_color' => '5f6368'
            )
        );
        
        $this->template_settings = array_merge($default_settings, $this->template_settings);
    }
    
    /**
     * تنظیم فیلدها
     */
    public function set_fields($fields) {
        $this->fields = $fields;
        return $this;
    }
    
    /**
     * تنظیم داده‌ها
     */
    public function set_data($data) {
        $this->data = $data;
        return $this;
    }
    
    /**
     * تولید فایل اکسل
     */
    public function generate($filename = 'report') {
        // ایجاد هدر
        $this->create_header();
        
        // ایجاد داده‌ها
        $this->create_data_rows();
        
        // ایجاد فوتر
        if ($this->template_settings['footer']['include']) {
            $this->create_footer();
        }
        
        // تنظیمات ستون‌ها
        $this->apply_column_settings();
        
        // تنظیمات صفحه
        $this->apply_page_settings();
        
        return $this->save($filename);
    }
    
    /**
     * ایجاد هدر
     */
    private function create_header() {
        $header_style = $this->template_settings['header'];
        
        // عنوان گزارش
        $this->worksheet->setCellValue('A' . $this->current_row, 'گزارش کارکرد پرسنل');
        $this->worksheet->mergeCells('A' . $this->current_row . ':' . $this->get_column_letter(count($this->fields) - 1) . $this->current_row);
        $this->apply_header_style('A' . $this->current_row, array(
            'font' => array(
                'bold' => true,
                'size' => 14,
                'color' => array('rgb' => $header_style['font_color'])
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => $header_style['bg_color'])
            )
        ));
        
        $this->current_row += 2;
        
        // اطلاعات گزارش
        $info_row = $this->current_row;
        $this->worksheet->setCellValue('A' . $info_row, 'تاریخ تولید:');
        $this->worksheet->setCellValue('B' . $info_row, wf_get_current_jalali_date('Y/m/d H:i'));
        
        $this->worksheet->setCellValue('D' . $info_row, 'تعداد رکورد:');
        $this->worksheet->setCellValue('E' . $info_row, count($this->data));
        
        $this->current_row += 2;
        
        // هدر ستون‌ها
        $header_row = $this->current_row;
        $col_index = 0;
        
        foreach ($this->fields as $field) {
            $col_letter = $this->get_column_letter($col_index);
            $this->worksheet->setCellValue($col_letter . $header_row, $field['field_name']);
            
            // اعمال استایل هدر
            $this->apply_header_style($col_letter . $header_row);
            
            $col_index++;
        }
        
        $this->current_row++;
    }
    
    /**
     * ایجاد ردیف‌های داده
     */
    private function create_data_rows() {
        $data_style = $this->template_settings['data'];
        $border_style = $this->template_settings['borders'];
        
        foreach ($this->data as $index => $row) {
            $col_index = 0;
            $row_style = array();
            
            // تعیین رنگ ردیف
            $fill_color = $index % 2 == 0 ? $data_style['even_row_color'] : $data_style['odd_row_color'];
            
            foreach ($this->fields as $field) {
                $col_letter = $this->get_column_letter($col_index);
                $cell_address = $col_letter . $this->current_row;
                
                // مقدار سلول
                $value = $this->get_cell_value($row, $field);
                $this->worksheet->setCellValue($cell_address, $value);
                
                // تنظیم فرمت سلول
                $this->apply_cell_format($cell_address, $field['field_type']);
                
                // استایل ردیف
                $row_style = array(
                    'fill' => array(
                        'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => array('rgb' => $fill_color)
                    ),
                    'font' => array(
                        'color' => array('rgb' => $data_style['font_color']),
                        'size' => $data_style['font_size']
                    ),
                    'alignment' => array(
                        'horizontal' => $data_style['alignment'] == 'right' ? 
                            PHPExcel_Style_Alignment::HORIZONTAL_RIGHT : 
                            PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                        'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                        'wrap' => $this->template_settings['columns']['wrap_text']
                    ),
                    'borders' => array(
                        'allborders' => array(
                            'style' => $border_style['style'],
                            'color' => array('rgb' => $border_style['color'])
                        )
                    )
                );
                
                $col_index++;
            }
            
            // اعمال استایل به کل ردیف
            $this->worksheet->getStyle('A' . $this->current_row . ':' . $this->get_column_letter(count($this->fields) - 1) . $this->current_row)
                ->applyFromArray($row_style);
            
            // تنظیم ارتفاع ردیف
            $this->worksheet->getRowDimension($this->current_row)->setRowHeight($data_style['height']);
            
            $this->current_row++;
        }
    }
    
    /**
     * ایجاد فوتر
     */
    private function create_footer() {
        $footer_style = $this->template_settings['footer'];
        
        $this->current_row++;
        
        $this->worksheet->setCellValue('A' . $this->current_row, $footer_style['text']);
        $this->worksheet->mergeCells('A' . $this->current_row . ':' . $this->get_column_letter(count($this->fields) - 1) . $this->current_row);
        
        $this->worksheet->getStyle('A' . $this->current_row)->applyFromArray(array(
            'font' => array(
                'size' => $footer_style['font_size'],
                'color' => array('rgb' => $footer_style['font_color']),
                'italic' => true
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
            )
        ));
    }
    
    /**
     * دریافت مقدار سلول
     */
    private function get_cell_value($row, $field) {
        $value = '';
        
        if (isset($row['data'][$field['field_key']])) {
            $value = $row['data'][$field['field_key']];
        } elseif (isset($row[$field['field_key']])) {
            $value = $row[$field['field_key']];
        }
        
        // فرمت‌دهی بر اساس نوع فیلد
        switch ($field['field_type']) {
            case 'date':
                if ($value) {
                    $value = wf_convert_to_jalali($value, 'Y/m/d');
                }
                break;
                
            case 'datetime':
                if ($value) {
                    $value = wf_convert_to_jalali($value, 'Y/m/d H:i');
                }
                break;
                
            case 'number':
            case 'decimal':
                if (is_numeric($value)) {
                    $value = number_format($value, $field['field_type'] == 'decimal' ? 2 : 0);
                }
                break;
                
            case 'checkbox':
                $value = $value ? '✓' : '✗';
                break;
        }
        
        return $value;
    }
    
    /**
     * اعمال فرمت سلول
     */
    private function apply_cell_format($cell_address, $field_type) {
        switch ($field_type) {
            case 'number':
                $this->worksheet->getStyle($cell_address)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');
                break;
                
            case 'decimal':
                $this->worksheet->getStyle($cell_address)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');
                break;
                
            case 'date':
                $this->worksheet->getStyle($cell_address)
                    ->getNumberFormat()
                    ->setFormatCode('yyyy/mm/dd');
                break;
                
            case 'datetime':
                $this->worksheet->getStyle($cell_address)
                    ->getNumberFormat()
                    ->setFormatCode('yyyy/mm/dd hh:mm');
                break;
        }
    }
    
    /**
     * اعمال استایل هدر
     */
    private function apply_header_style($cell_address, $custom_style = array()) {
        $header_style = $this->template_settings['header'];
        $border_style = $this->template_settings['borders'];
        
        $default_style = array(
            'font' => array(
                'bold' => $header_style['font_bold'],
                'color' => array('rgb' => $header_style['font_color']),
                'size' => $header_style['font_size']
            ),
            'alignment' => array(
                'horizontal' => $header_style['alignment'] == 'center' ? 
                    PHPExcel_Style_Alignment::HORIZONTAL_CENTER : 
                    PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                'wrap' => true
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => $header_style['bg_color'])
            ),
            'borders' => array(
                'allborders' => array(
                    'style' => $border_style['style'],
                    'color' => array('rgb' => $border_style['color'])
                )
            )
        );
        
        $style = array_merge($default_style, $custom_style);
        $this->worksheet->getStyle($cell_address)->applyFromArray($style);
    }
    
    /**
     * تنظیمات ستون‌ها
     */
    private function apply_column_settings() {
        if ($this->template_settings['columns']['auto_width']) {
            foreach (range(0, count($this->fields) - 1) as $col_index) {
                $col_letter = $this->get_column_letter($col_index);
                $this->worksheet->getColumnDimension($col_letter)->setAutoSize(true);
            }
        }
        
        // تنظیم عرض ستون‌های خاص
        foreach ($this->fields as $index => $field) {
            $col_letter = $this->get_column_letter($index);
            
            // تنظیم عرض بر اساس نوع فیلد
            switch ($field['field_type']) {
                case 'date':
                    $this->worksheet->getColumnDimension($col_letter)->setWidth(12);
                    break;
                    
                case 'datetime':
                    $this->worksheet->getColumnDimension($col_letter)->setWidth(16);
                    break;
                    
                case 'checkbox':
                    $this->worksheet->getColumnDimension($col_letter)->setWidth(8);
                    break;
                    
                case 'number':
                case 'decimal':
                    $this->worksheet->getColumnDimension($col_letter)->setWidth(15);
                    break;
            }
        }
    }
    
    /**
     * تنظیمات صفحه
     */
    private function apply_page_settings() {
        // جهت صفحه
        $this->worksheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
        
        // حاشیه‌ها
        $this->worksheet->getPageMargins()->setTop(0.5);
        $this->worksheet->getPageMargins()->setRight(0.5);
        $this->worksheet->getPageMargins()->setLeft(0.5);
        $this->worksheet->getPageMargins()->setBottom(0.5);
        
        // تکرار هدر در هر صفحه
        $this->worksheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(5, 5);
        
        // مرکز کردن افقی
        $this->worksheet->getPageSetup()->setHorizontalCentered(true);
        
        // تنظیمات پرینت
        $this->worksheet->getPageSetup()->setFitToWidth(1);
        $this->worksheet->getPageSetup()->setFitToHeight(0);
    }
    
    /**
     * ذخیره فایل
     */
    private function save($filename) {
        $writer = PHPExcel_IOFactory::createWriter($this->excel, 'Excel2007');
        
        // ایجاد نام فایل
        $filename = sanitize_file_name($filename . '_' . date('Y-m-d_H-i-s') . '.xlsx');
        $filepath = WP_CONTENT_DIR . '/uploads/workforce_exports/' . $filename;
        
        // ایجاد دایرکتوری
        wp_mkdir_p(dirname($filepath));
        
        // ذخیره فایل
        $writer->save($filepath);
        
        return array(
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => content_url('/uploads/workforce_exports/' . $filename)
        );
    }
    
    /**
     * تبدیل شماره ستون به حرف
     */
    private function get_column_letter($col_index) {
        return PHPExcel_Cell::stringFromColumnIndex($col_index);
    }
}

// ==================== توابع خروجی اکسل ====================

/**
 * ایجاد گزارش اکسل
 */
function wf_generate_excel_report($data, $fields, $template_id = null, $options = array()) {
    $exporter = new WF_Excel_Exporter($template_id);
    
    $exporter->set_fields($fields)
             ->set_data($data);
    
    $filename = isset($options['filename']) ? $options['filename'] : 'گزارش_پرسنل';
    
    return $exporter->generate($filename);
}

/**
 * ایجاد گزارش سازمانی
 */
function wf_generate_organization_report($period_id = null, $department_ids = array(), $template_id = null) {
    global $wpdb;
    
    // دریافت دوره
    if (!$period_id) {
        $period = wf_get_current_period();
        $period_id = $period['id'];
    }
    
    // دریافت ادارات
    if (empty($department_ids)) {
        $departments = wf_get_all_departments();
        $department_ids = array_column($departments, 'id');
    }
    
    // دریافت فیلدها
    $fields = wf_get_all_fields();
    
    // دریافت داده‌ها
    $placeholders = implode(',', array_fill(0, count($department_ids), '%d'));
    $query = $wpdb->prepare(
        "SELECT p.*, d.name as department_name 
         FROM {$wpdb->prefix}wf_personnel p
         LEFT JOIN {$wpdb->prefix}wf_departments d ON p.department_id = d.id
         WHERE p.department_id IN ($placeholders) AND p.is_deleted = 0
         ORDER BY d.name, p.last_name, p.first_name",
        $department_ids
    );
    
    $personnel = $wpdb->get_results($query, ARRAY_A);
    
    // پردازش داده‌ها
    $data = array();
    foreach ($personnel as $person) {
        if (isset($person['data'])) {
            $person['data'] = json_decode($person['data'], true);
        }
        $data[] = $person;
    }
    
    // ایجاد فایل اکسل
    return wf_generate_excel_report($data, $fields, $template_id, array(
        'filename' => 'گزارش_سازمانی_' . date('Y-m-d')
    ));
}

/**
 * ایجاد گزارش اداره
 */
function wf_generate_department_report($department_id, $period_id = null, $template_id = null) {
    global $wpdb;
    
    // دریافت دوره
    if (!$period_id) {
        $period = wf_get_current_period();
        $period_id = $period['id'];
    }
    
    // دریافت اداره
    $department = wf_get_department($department_id);
    if (!$department) {
        return new WP_Error('invalid_department', 'اداره معتبر نیست');
    }
    
    // دریافت فیلدها
    $fields = wf_get_all_fields();
    
    // دریافت داده‌ها
    $query = $wpdb->prepare(
        "SELECT p.*, d.name as department_name 
         FROM {$wpdb->prefix}wf_personnel p
         LEFT JOIN {$wpdb->prefix}wf_departments d ON p.department_id = d.id
         WHERE p.department_id = %d AND p.is_deleted = 0
         ORDER BY p.last_name, p.first_name",
        $department_id
    );
    
    $personnel = $wpdb->get_results($query, ARRAY_A);
    
    // پردازش داده‌ها
    $data = array();
    foreach ($personnel as $person) {
        if (isset($person['data'])) {
            $person['data'] = json_decode($person['data'], true);
        }
        $data[] = $person;
    }
    
    // ایجاد فایل اکسل
    return wf_generate_excel_report($data, $fields, $template_id, array(
        'filename' => 'گزارش_' . sanitize_file_name($department['name']) . '_' . date('Y-m-d')
    ));
}

/**
 * ایجاد گزارش آماری
 */
function wf_generate_statistical_report($report_type, $params = array()) {
    $exporter = new WF_Excel_Exporter();
    $worksheet = $exporter->getWorksheet();
    
    // بر اساس نوع گزارش
    switch ($report_type) {
        case 'department_summary':
            $data = wf_generate_aggregate_report('department_summary', $params);
            $this->create_department_summary_report($worksheet, $data);
            break;
            
        case 'completion_stats':
            $data = wf_generate_aggregate_report('completion_stats', $params);
            $this->create_completion_report($worksheet, $data);
            break;
            
        case 'monthly_trend':
            $data = wf_generate_aggregate_report('monthly_trend', $params);
            $this->create_trend_report($worksheet, $data);
            break;
    }
    
    return $exporter->generate('گزارش_آماری_' . date('Y-m-d'));
}

/**
 * ایجاد گزارش خلاصه ادارات
 */
private function create_department_summary_report($worksheet, $data) {
    $worksheet->setTitle('خلاصه ادارات');
    
    // هدر
    $headers = array('نام اداره', 'مدیر', 'تعداد پرسنل', 'میانگین تکمیل', 'هشدارها', 'آخرین فعالیت');
    $col = 0;
    
    foreach ($headers as $header) {
        $worksheet->setCellValueByColumnAndRow($col++, 1, $header);
    }
    
    // داده‌ها
    $row = 2;
    foreach ($data as $item) {
        $col = 0;
        $worksheet->setCellValueByColumnAndRow($col++, $row, $item['name']);
        $worksheet->setCellValueByColumnAndRow($col++, $row, $item['manager_name'] ?? 'ندارد');
        $worksheet->setCellValueByColumnAndRow($col++, $row, $item['personnel_count']);
        $worksheet->setCellValueByColumnAndRow($col++, $row, $item['avg_completion'] . '%');
        $worksheet->setCellValueByColumnAndRow($col++, $row, $item['warning_count']);
        $worksheet->setCellValueByColumnAndRow($col++, $row, $item['last_activity'] ? 
            wf_convert_to_jalali($item['last_activity'], 'Y/m/d H:i') : 'ندارد');
        $row++;
    }
    
    // فرمت‌دهی
    $worksheet->getStyle('A1:F1')->applyFromArray(array(
        'font' => array('bold' => true),
        'fill' => array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => array('rgb' => '1a73e8')
        ),
        'font' => array('color' => array('rgb' => 'ffffff'))
    ));
    
    // تنظیم عرض ستون‌ها
    foreach (range('A', 'F') as $column) {
        $worksheet->getColumnDimension($column)->setAutoSize(true);
    }
}

/**
 * ایجاد گزارش روند ماهانه
 */
private function create_trend_report($worksheet, $data) {
    $worksheet->setTitle('روند ماهانه');
    
    // هدر
    $headers = array('ماه', 'پرسنل جدید', 'هشدارها', 'میانگین تکمیل');
    $col = 0;
    
    foreach ($headers as $header) {
        $worksheet->setCellValueByColumnAndRow($col++, 1, $header);
    }
    
    // داده‌ها
    $row = 2;
    foreach ($data as $item) {
        $col = 0;
        $worksheet->setCellValueByColumnAndRow($col++, $row, $item['month']);
        $worksheet->setCellValueByColumnAndRow($col++, $row, $item['new_personnel']);
        $worksheet->setCellValueByColumnAndRow($col++, $row, $item['warnings']);
        $worksheet->setCellValueByColumnAndRow($col++, $row, round($item['avg_completion'], 1) . '%');
        $row++;
    }
    
    // ایجاد نمودار
    $chart = new PHPExcel_Chart(
        'chart1',
        new PHPExcel_Chart_Title('روند ماهانه'),
        new PHPExcel_Chart_Legend(PHPExcel_Chart_Legend::POSITION_RIGHT, null, false),
        new PHPExcel_Chart_PlotArea(null, array(
            new PHPExcel_Chart_Series(
                new PHPExcel_Chart_DataSeriesValues('String', 'Worksheet!$A$2:$A$' . ($row-1), null, count($data)),
                new PHPExcel_Chart_DataSeriesValues('Number', 'Worksheet!$B$2:$B$' . ($row-1), null, count($data))
            )
        )),
        true,
        new PHPExcel_Chart_Title('ماه'),
        new PHPExcel_Chart_Title('تعداد')
    );
    
    $chart->setTopLeftPosition('F2');
    $chart->setBottomRightPosition('P15');
    $worksheet->addChart($chart);
}

// ==================== AJAX Handlers ====================

/**
 * AJAX خروجی اکسل
 */
add_action('wp_ajax_wf_export_excel', 'wf_ajax_export_excel');

function wf_ajax_export_excel() {
    check_ajax_referer('workforce_manager_nonce', 'nonce');
    
    $export_type = sanitize_text_field($_POST['export_type']);
    $manager_id = intval($_POST['manager_id']);
    $filters = isset($_POST['filters']) ? json_decode(stripslashes($_POST['filters']), true) : array();
    $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : null;
    $include_selected = isset($_POST['include_selected']) ? (bool)$_POST['include_selected'] : false;
    $selected_ids = isset($_POST['selected_ids']) ? array_map('intval', $_POST['selected_ids']) : array();
    
    // بررسی دسترسی
    $user = get_user_by('id', $manager_id);
    if (!$user) {
        wp_send_json_error(array('message' => 'کاربر معتبر نیست'));
    }
    
    $roles = $user->roles;
    $is_admin = in_array('administrator', $roles) || in_array('wf_org_manager', $roles);
    $is_department_manager = in_array('wf_department_manager', $roles);
    
    if (!$is_admin && !$is_department_manager) {
        wp_send_json_error(array('message' => 'دسترسی غیرمجاز'));
    }
    
    try {
        if ($export_type === 'organization' && $is_admin) {
            // گزارش سازمانی
            $result = wf_generate_organization_report(null, array(), $template_id);
        } elseif ($export_type === 'department' && $is_department_manager) {
            // گزارش اداره
            $department_id = get_user_meta($manager_id, 'wf_department_id', true);
            if (!$department_id) {
                wp_send_json_error(array('message' => 'شما مدیر هیچ اداره‌ای نیستید'));
            }
            
            $result = wf_generate_department_report($department_id, null, $template_id);
        } elseif ($export_type === 'filtered') {
            // گزارش فیلتر شده
            $result = wf_generate_filtered_report($manager_id, $filters, $selected_ids, $include_selected, $template_id);
        } else {
            wp_send_json_error(array('message' => 'نوع خروجی معتبر نیست'));
        }
        
        wp_send_json_success(array(
            'file_url' => $result['url'],
            'file_name' => $result['filename'],
            'message' => 'فایل اکسل با موفقیت ایجاد شد'
        ));
        
    } catch (Exception $e) {
        wp_send_json_error(array('message' => 'خطا در ایجاد فایل: ' . $e->getMessage()));
    }
}

/**
 * ایجاد گزارش فیلتر شده
 */
function wf_generate_filtered_report($manager_id, $filters, $selected_ids, $include_selected, $template_id) {
    global $wpdb;
    
    // تعیین محدوده دسترسی
    $user = get_user_by('id', $manager_id);
    $roles = $user->roles;
    $is_admin = in_array('administrator', $roles) || in_array('wf_org_manager', $roles);
    
    $department_ids = array();
    
    if ($is_admin) {
        // مدیر سازمان به همه ادارات دسترسی دارد
        $departments = wf_get_all_departments();
        $department_ids = array_column($departments, 'id');
    } else {
        // مدیر اداره فقط به اداره خود دسترسی دارد
        $department_id = get_user_meta($manager_id, 'wf_department_id', true);
        if ($department_id) {
            $department_ids[] = $department_id;
        }
    }
    
    if (empty($department_ids)) {
        throw new Exception('دسترسی به داده‌ای وجود ندارد');
    }
    
    // دریافت فیلدها
    $fields = wf_get_all_fields();
    
    // ساختن شرط WHERE
    $where = array("p.is_deleted = 0");
    $params = array();
    
    // محدودیت اداره
    $placeholders = implode(',', array_fill(0, count($department_ids), '%d'));
    $where[] = "p.department_id IN ($placeholders)";
    $params = array_merge($params, $department_ids);
    
    // فیلترها
    if (!empty($filters)) {
        foreach ($filters as $field_id => $filter) {
            $field = wf_get_field($field_id);
            if ($field) {
                $field_key = $field['field_key'];
                
                switch ($filter['type']) {
                    case 'equals':
                        $where[] = "JSON_EXTRACT(p.data, '$.$field_key') = %s";
                        $params[] = $filter['value'];
                        break;
                    case 'contains':
                        $where[] = "JSON_EXTRACT(p.data, '$.$field_key') LIKE %s";
                        $params[] = '%' . $wpdb->esc_like($filter['value']) . '%';
                        break;
                    case 'greater':
                        $where[] = "CAST(JSON_EXTRACT(p.data, '$.$field_key') AS DECIMAL) > %f";
                        $params[] = floatval($filter['value']);
                        break;
                    case 'less':
                        $where[] = "CAST(JSON_EXTRACT(p.data, '$.$field_key') AS DECIMAL) < %f";
                        $params[] = floatval($filter['value']);
                        break;
                }
            }
        }
    }
    
    // انتخاب شده‌ها
    if ($include_selected && !empty($selected_ids)) {
        $selected_placeholders = implode(',', array_fill(0, count($selected_ids), '%d'));
        $where[] = "p.id IN ($selected_placeholders)";
        $params = array_merge($params, $selected_ids);
    }
    
    $where_clause = implode(' AND ', $where);
    
    // دریافت داده‌ها
    $query = "SELECT p.*, d.name as department_name 
              FROM {$wpdb->prefix}wf_personnel p
              LEFT JOIN {$wpdb->prefix}wf_departments d ON p.department_id = d.id
              WHERE $where_clause
              ORDER BY d.name, p.last_name, p.first_name";
    
    if (!empty($params)) {
        $query = $wpdb->prepare($query, $params);
    }
    
    $personnel = $wpdb->get_results($query, ARRAY_A);
    
    // پردازش داده‌ها
    $data = array();
    foreach ($personnel as $person) {
        if (isset($person['data'])) {
            $person['data'] = json_decode($person['data'], true);
        }
        $data[] = $person;
    }
    
    // ایجاد فایل اکسل
    return wf_generate_excel_report($data, $fields, $template_id, array(
        'filename' => 'گزارش_فیلتر شده_' . date('Y-m-d')
    ));
}

/**
 * دریافت قالب‌های اکسل
 */
add_action('wp_ajax_wf_get_excel_templates', 'wf_ajax_get_excel_templates');

function wf_ajax_get_excel_templates() {
    check_ajax_referer('workforce_manager_nonce', 'nonce');
    
    global $wpdb;
    
    $templates = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}wf_templates 
         WHERE template_type = 'excel' AND status = 'active'
         ORDER BY is_default DESC, name ASC",
        ARRAY_A
    );
    
    foreach ($templates as &$template) {
        $template['settings'] = json_decode($template['settings'], true);
    }
    
    wp_send_json_success($templates);
}

/**
 * ذخیره قالب اکسل
 */
add_action('wp_ajax_wf_save_excel_template', 'wf_ajax_save_excel_template');

function wf_ajax_save_excel_template() {
    check_ajax_referer('workforce_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'دسترسی غیرمجاز'));
    }
    
    $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
    $name = sanitize_text_field($_POST['name']);
    $settings = isset($_POST['settings']) ? json_decode(stripslashes($_POST['settings']), true) : array();
    $is_default = isset($_POST['is_default']) ? (bool)$_POST['is_default'] : false;
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
    
    global $wpdb;
    
    $template_data = array(
        'name' => $name,
        'template_type' => 'excel',
        'settings' => json_encode($settings),
        'description' => $description,
        'created_by' => get_current_user_id(),
        'status' => 'active'
    );
    
    if ($is_default) {
        $template_data['is_default'] = 1;
        // غیرفعال کردن پیش‌فرض دیگر
        $wpdb->update(
            $wpdb->prefix . 'wf_templates',
            array('is_default' => 0),
            array('is_default' => 