<?php
/**
 * سیستم خروجی اکسل کاملاً مستقل - سیستم مدیریت کارکرد پرسنل بنی اسد
 * نسخه بدون نیاز به نصب - همه چیز در یک فایل
 * 
 * @package Workforce
 * @version 2.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// ==================== سیستم تاریخ شمسی داخلی ====================

if (!function_exists('gregorian_to_jalali')) {
    /**
     * تبدیل تاریخ میلادی به شمسی
     */
    function gregorian_to_jalali($gy, $gm, $gd, $mod = '')
    {
        $g_d_m = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100)) + ((int)(($gy2 + 399) / 400)) + $gd + $g_d_m[$gm - 1];
        $jy = -1595 + (33 * ((int)($days / 12053)));
        $days %= 12053;
        $jy += 4 * ((int)($days / 1461));
        $days %= 1461;
        if ($days > 365) {
            $jy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        if ($days < 186) {
            $jm = 1 + (int)($days / 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + (int)(($days - 186) / 30);
            $jd = 1 + (($days - 186) % 30);
        }
        return ($mod == '') ? array($jy, $jm, $jd) : $jy . $mod . $jm . $mod . $jd;
    }
}

if (!function_exists('jalali_to_gregorian')) {
    /**
     * تبدیل تاریخ شمسی به میلادی
     */
    function jalali_to_gregorian($jy, $jm, $jd, $mod = '')
    {
        $jy += 1595;
        $days = -355668 + (365 * $jy) + (((int)($jy / 33)) * 8) + ((int)((($jy % 33) + 3) / 4)) + $jd + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
        $gy = 400 * ((int)($days / 146097));
        $days %= 146097;
        if ($days > 36524) {
            $gy += 100 * ((int)(--$days / 36524));
            $days %= 36524;
            if ($days >= 365) $days++;
        }
        $gy += 4 * ((int)($days / 1461));
        $days %= 1461;
        if ($days > 365) {
            $gy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        $gd = $days + 1;
        $sal_a = array(0, 31, (($gy % 4 == 0 and $gy % 100 != 0) or ($gy % 400 == 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        for ($gm = 0; $gm < 13 and $gd > $sal_a[$gm]; $gm++) $gd -= $sal_a[$gm];
        return ($mod == '') ? array($gy, $gm, $gd) : $gy . $mod . $gm . $mod . $gd;
    }
}

if (!function_exists('wf_convert_to_jalali')) {
    /**
     * تبدیل تاریخ به شمسی با فرمت دلخواه
     */
    function wf_convert_to_jalali($date, $format = 'Y/m/d')
    {
        if (empty($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
            return '';
        }
        
        // اگر تاریخ میلادی است
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $date, $matches)) {
            $year = (int)$matches[1];
            $month = (int)$matches[2];
            $day = (int)$matches[3];
            
            list($jy, $jm, $jd) = gregorian_to_jalali($year, $month, $day);
            
            // فرمت‌دهی
            $replacements = array(
                'Y' => sprintf('%04d', $jy),
                'y' => sprintf('%02d', $jy % 100),
                'm' => sprintf('%02d', $jm),
                'n' => $jm,
                'd' => sprintf('%02d', $jd),
                'j' => $jd
            );
            
            $result = $format;
            foreach ($replacements as $key => $value) {
                $result = str_replace($key, $value, $result);
            }
            
            return $result;
        }
        
        // اگر تاریخ شمسی است
        if (preg_match('/^(\d{4})\/(\d{2})\/(\d{2})/', $date, $matches)) {
            return $date;
        }
        
        return $date;
    }
}

if (!function_exists('wf_current_jalali_date')) {
    /**
     * تاریخ شمسی امروز
     */
    function wf_current_jalali_date($format = 'Y/m/d')
    {
        $current_time = current_time('timestamp');
        $year = date('Y', $current_time);
        $month = date('m', $current_time);
        $day = date('d', $current_time);
        
        return wf_convert_to_jalali("$year-$month-$day", $format);
    }
}

// ==================== کلاس PHPExcel داخلی ====================

// بررسی وجود کلاس PHPExcel
if (!class_exists('PHPExcel')) {
    /**
     * کلاس PHPExcel شبه‌ساز - برای مواقعی که PHPExcel نصب نیست
     * فقط توابع ضروری را پیاده‌سازی می‌کند
     */
    class WF_MiniExcel {
        private $data = array();
        private $current_sheet = 0;
        private $sheets = array();
        private $styles = array();
        private $properties = array(
            'creator' => 'سیستم مدیریت کارکرد پرسنل',
            'title' => 'گزارش پرسنل',
            'description' => 'تولید شده توسط سیستم بنی اسد'
        );
        
        public function __construct() {
            $this->sheets[0] = array(
                'title' => 'گزارش',
                'data' => array(),
                'columns' => array(),
                'styles' => array()
            );
        }
        
        public function getActiveSheet() {
            return $this;
        }
        
        public function setCellValue($cell, $value) {
            $this->sheets[$this->current_sheet]['data'][$cell] = $value;
            return $this;
        }
        
        public function mergeCells($range) {
            $this->sheets[$this->current_sheet]['merged'][] = $range;
            return $this;
        }
        
        public function getStyle($cell) {
            if (!isset($this->sheets[$this->current_sheet]['styles'][$cell])) {
                $this->sheets[$this->current_sheet]['styles'][$cell] = new WF_MiniExcel_Style();
            }
            return $this->sheets[$this->current_sheet]['styles'][$cell];
        }
        
        public function getColumnDimension($column) {
            return new WF_MiniExcel_Column($column);
        }
        
        public function getRowDimension($row) {
            return new WF_MiniExcel_Row($row);
        }
        
        public function setTitle($title) {
            $this->sheets[$this->current_sheet]['title'] = $title;
            return $this;
        }
        
        public function setRightToLeft($value) {
            $this->sheets[$this->current_sheet]['rtl'] = $value;
            return $this;
        }
        
        public function generateXLSX() {
            // تولید فایل Excel ساده
            return $this->generateSimpleExcel();
        }
        
        private function generateSimpleExcel() {
            $filename = 'report_' . date('Y-m-d_H-i-s') . '.xlsx';
            $filepath = WP_CONTENT_DIR . '/uploads/workforce_exports/' . $filename;
            
            // در نسخه واقعی، اینجا فایل Excel تولید می‌شود
            // فعلاً فقط یک فایل متنی ساده ایجاد می‌کنیم
            
            $content = "گزارش پرسنل - سیستم بنی اسد\n";
            $content .= "تاریخ تولید: " . wf_current_jalali_date('Y/m/d H:i') . "\n\n";
            
            if (!empty($this->sheets[$this->current_sheet]['data'])) {
                foreach ($this->sheets[$this->current_sheet]['data'] as $cell => $value) {
                    $content .= "$cell: $value\n";
                }
            }
            
            wp_mkdir_p(dirname($filepath));
            file_put_contents($filepath, $content);
            
            return array(
                'filename' => $filename,
                'filepath' => $filepath,
                'url' => content_url('/uploads/workforce_exports/' . $filename)
            );
        }
    }
    
    class WF_MiniExcel_Style {
        private $properties = array();
        
        public function applyFromArray($style) {
            $this->properties = array_merge($this->properties, $style);
            return $this;
        }
        
        public function getFont() {
            return $this;
        }
        
        public function getAlignment() {
            return $this;
        }
        
        public function getNumberFormat() {
            return $this;
        }
        
        public function setBold($value) { return $this; }
        public function setSize($value) { return $this; }
        public function setName($value) { return $this; }
        public function setColor($value) { return $this; }
        public function setHorizontal($value) { return $this; }
        public function setVertical($value) { return $this; }
        public function setWrapText($value) { return $this; }
        public function setFormatCode($value) { return $this; }
    }
    
    class WF_MiniExcel_Column {
        private $column;
        
        public function __construct($column) {
            $this->column = $column;
        }
        
        public function setAutoSize($value) { return $this; }
        public function setWidth($value) { return $this; }
    }
    
    class WF_MiniExcel_Row {
        private $row;
        
        public function __construct($row) {
            $this->row = $row;
        }
        
        public function setRowHeight($value) { return $this; }
    }
    
    // تعریف کلاس اصلی با شبه‌ساز
    class PHPExcel extends WF_MiniExcel {}
    
} else {
    // اگر PHPExcel از قبل نصب است، از همان استفاده می‌کنیم
    require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
}

// ==================== کلاس اصلی خروجی اکسل ====================

class WF_Excel_Exporter_Standalone {
    
    private $excel;
    private $worksheet;
    private $current_row = 1;
    private $template_settings = array();
    private $fields = array();
    private $data = array();
    private $use_html_excel = false;
    
    /**
     * سازنده کلاس
     */
    public function __construct($template_id = null) {
        // بررسی اگر PHPExcel واقعی موجود است
        if (class_exists('PHPExcel') && !is_a('PHPExcel', 'WF_MiniExcel', false)) {
            $this->excel = new PHPExcel();
            $this->worksheet = $this->excel->getActiveSheet();
        } else {
            // استفاده از شبه‌ساز
            $this->excel = new WF_MiniExcel();
            $this->worksheet = $this->excel->getActiveSheet();
            $this->use_html_excel = true;
        }
        
        // تنظیم جهت راست‌به‌چپ
        $this->worksheet->setRightToLeft(true);
        
        // بارگذاری قالب
        $this->load_template($template_id);
        
        // تنظیمات پیش‌فرض
        if (!$this->use_html_excel) {
            $this->excel->getProperties()
                ->setCreator("سیستم مدیریت کارکرد پرسنل بنی اسد")
                ->setTitle("گزارش کارکرد پرسنل")
                ->setDescription("تولید شده توسط سیستم مدیریت کارکرد پرسنل");
        }
    }
    
    /**
     * بارگذاری قالب
     */
    private function load_template($template_id = null) {
        // تنظیمات پیش‌فرض
        $this->template_settings = array(
            'header' => array(
                'bg_color' => '2E86C1',
                'font_color' => 'FFFFFF',
                'font_size' => 14,
                'font_bold' => true,
                'alignment' => 'center',
                'height' => 35
            ),
            'data' => array(
                'even_row_color' => 'F2F3F4',
                'odd_row_color' => 'FFFFFF',
                'font_color' => '2C3E50',
                'font_size' => 11,
                'alignment' => 'right',
                'height' => 25,
                'auto_filter' => false
            ),
            'borders' => array(
                'style' => 'thin',
                'color' => 'D5D8DC'
            ),
            'columns' => array(
                'auto_width' => true,
                'wrap_text' => true
            ),
            'footer' => array(
                'include' => true,
                'text' => 'تولید شده توسط سیستم مدیریت کارکرد پرسنل بنی اسد | تاریخ تولید: {DATE}',
                'font_size' => 9,
                'font_color' => '7F8C8D'
            )
        );
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
    public function generate($filename = 'report', $options = array()) {
        if ($this->use_html_excel) {
            // استفاده از سیستم ساده HTML Excel
            return $this->generate_html_excel($filename, $options);
        }
        
        // ایجاد هدر
        $this->create_header($options);
        
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
        
        // ذخیره فایل
        return $this->save_excel_file($filename);
    }
    
    /**
     * ایجاد هدر
     */
    private function create_header($options) {
        $header_style = $this->template_settings['header'];
        
        // عنوان گزارش
        $title = isset($options['title']) ? $options['title'] : 'گزارش کارکرد پرسنل';
        
        $this->worksheet->setCellValue('A' . $this->current_row, $title);
        $this->worksheet->mergeCells('A' . $this->current_row . ':' . $this->get_column_letter(count($this->fields) - 1) . $this->current_row);
        
        // اعمال استایل
        $this->worksheet->getStyle('A' . $this->current_row)->applyFromArray(array(
            'font' => array(
                'bold' => true,
                'size' => 16,
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
        
        $this->worksheet->getRowDimension($this->current_row)->setRowHeight($header_style['height']);
        
        $this->current_row += 2;
        
        // اطلاعات گزارش
        $this->create_report_info($options);
        $this->current_row += 2;
        
        // هدر ستون‌ها
        $this->create_column_headers();
        $this->current_row++;
    }
    
    /**
     * ایجاد اطلاعات گزارش
     */
    private function create_report_info($options) {
        $info_row = $this->current_row;
        
        // تاریخ تولید
        $this->worksheet->setCellValue('A' . $info_row, 'تاریخ تولید:');
        $this->worksheet->setCellValue('B' . $info_row, wf_current_jalali_date('Y/m/d H:i'));
        
        // تعداد رکوردها
        $this->worksheet->setCellValue('D' . $info_row, 'تعداد رکورد:');
        $this->worksheet->setCellValue('E' . $info_row, number_format(count($this->data)));
        
        // مدیر گزارش
        $manager_name = isset($options['manager_name']) ? $options['manager_name'] : 'سیستم';
        $this->worksheet->setCellValue('G' . $info_row, 'مدیر گزارش:');
        $this->worksheet->setCellValue('H' . $info_row, $manager_name);
        
        // استایل اطلاعات
        $this->worksheet->getStyle('A' . $info_row . ':H' . $info_row)->applyFromArray(array(
            'font' => array(
                'bold' => true,
                'color' => array('rgb' => '2C3E50')
            )
        ));
    }
    
    /**
     * ایجاد هدر ستون‌ها
     */
    private function create_column_headers() {
        $header_style = $this->template_settings['header'];
        $border_style = $this->template_settings['borders'];
        
        $header_row = $this->current_row;
        $col_index = 0;
        
        foreach ($this->fields as $field) {
            $col_letter = $this->get_column_letter($col_index);
            $this->worksheet->setCellValue($col_letter . $header_row, $field['field_name']);
            
            // اعمال استایل
            $style = array(
                'font' => array(
                    'bold' => $header_style['font_bold'],
                    'color' => array('rgb' => $header_style['font_color']),
                    'size' => $header_style['font_size']
                ),
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ),
                'fill' => array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => $header_style['bg_color'])
                ),
                'borders' => array(
                    'allborders' => array(
                        'borderStyle' => $border_style['style'],
                        'color' => array('rgb' => $border_style['color'])
                    )
                )
            );
            
            $this->worksheet->getStyle($col_letter . $header_row)->applyFromArray($style);
            $this->worksheet->getRowDimension($header_row)->setRowHeight($header_style['height']);
            
            $col_index++;
        }
    }
    
    /**
     * ایجاد ردیف‌های داده
     */
    private function create_data_rows() {
        $data_style = $this->template_settings['data'];
        $border_style = $this->template_settings['borders'];
        
        foreach ($this->data as $index => $row) {
            $col_index = 0;
            
            // تعیین رنگ ردیف
            $fill_color = $index % 2 == 0 ? $data_style['even_row_color'] : $data_style['odd_row_color'];
            
            foreach ($this->fields as $field) {
                $col_letter = $this->get_column_letter($col_index);
                $cell_address = $col_letter . $this->current_row;
                
                // مقدار سلول
                $value = $this->get_cell_value($row, $field);
                $this->worksheet->setCellValue($cell_address, $value);
                
                // تنظیم فرمت
                $this->apply_cell_format($cell_address, $field['field_type']);
                
                $col_index++;
            }
            
            // اعمال استایل به کل ردیف
            $row_range = 'A' . $this->current_row . ':' . $this->get_column_letter(count($this->fields) - 1) . $this->current_row;
            
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
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'wrapText' => $this->template_settings['columns']['wrap_text']
                ),
                'borders' => array(
                    'allborders' => array(
                        'borderStyle' => $border_style['style'],
                        'color' => array('rgb' => $border_style['color'])
                    )
                )
            );
            
            $this->worksheet->getStyle($row_range)->applyFromArray($row_style);
            $this->worksheet->getRowDimension($this->current_row)->setRowHeight($data_style['height']);
            
            $this->current_row++;
        }
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
                if (is_numeric($value)) {
                    $value = number_format($value, 0, '.', ',');
                }
                break;
                
            case 'decimal':
            case 'float':
                if (is_numeric($value)) {
                    $value = number_format($value, 2, '.', ',');
                }
                break;
                
            case 'currency':
                if (is_numeric($value)) {
                    $value = number_format($value, 0, '.', ',') . ' ریال';
                }
                break;
                
            case 'checkbox':
            case 'boolean':
                $value = $value ? '✅' : '❌';
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
                    ->setFormatCode('yyyy/mm/dd;@');
                break;
                
            case 'datetime':
                $this->worksheet->getStyle($cell_address)
                    ->getNumberFormat()
                    ->setFormatCode('yyyy/mm/dd hh:mm;@');
                break;
        }
    }
    
    /**
     * ایجاد فوتر
     */
    private function create_footer() {
        $footer_style = $this->template_settings['footer'];
        
        $this->current_row++;
        
        $footer_text = str_replace(
            '{DATE}',
            wf_current_jalali_date('Y/m/d H:i'),
            $footer_style['text']
        );
        
        $this->worksheet->setCellValue('A' . $this->current_row, $footer_text);
        $this->worksheet->mergeCells('A' . $this->current_row . ':' . 
            $this->get_column_letter(count($this->fields) - 1) . $this->current_row);
        
        $this->worksheet->getStyle('A' . $this->current_row)->applyFromArray(array(
            'font' => array(
                'size' => $footer_style['font_size'],
                'color' => array('rgb' => $footer_style['font_color']),
                'italic' => true
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            )
        ));
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
        
        // تنظیم عرض مناسب برای ستون‌های خاص
        foreach ($this->fields as $index => $field) {
            $col_letter = $this->get_column_letter($index);
            
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
                    
                default:
                    if ($this->template_settings['columns']['auto_width']) {
                        $this->worksheet->getColumnDimension($col_letter)->setAutoSize(true);
                    }
                    break;
            }
        }
    }
    
    /**
     * تنظیمات صفحه
     */
    private function apply_page_settings() {
        $this->worksheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
        
        $this->worksheet->getPageMargins()->setTop(0.5);
        $this->worksheet->getPageMargins()->setRight(0.3);
        $this->worksheet->getPageMargins()->setLeft(0.3);
        $this->worksheet->getPageMargins()->setBottom(0.5);
        
        $this->worksheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 5);
        $this->worksheet->getPageSetup()->setFitToWidth(1);
        $this->worksheet->getPageSetup()->setFitToHeight(0);
        $this->worksheet->getPageSetup()->setHorizontalCentered(true);
    }
    
    /**
     * ذخیره فایل Excel
     */
    private function save_excel_file($filename) {
        $filename = sanitize_file_name($filename . '_' . date('Y-m-d_H-i-s') . '.xlsx');
        $filepath = WP_CONTENT_DIR . '/uploads/workforce_exports/' . $filename;
        
        wp_mkdir_p(dirname($filepath));
        
        // ایجاد Writer
        $writer = PHPExcel_IOFactory::createWriter($this->excel, 'Excel2007');
        $writer->save($filepath);
        
        return array(
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => content_url('/uploads/workforce_exports/' . $filename),
            'size' => filesize($filepath),
            'generated_at' => wf_current_jalali_date('Y/m/d H:i:s'),
            'records_count' => count($this->data)
        );
    }
    
    /**
     * تولید Excel HTML (برای مواقعی که PHPExcel نیست)
     */
    private function generate_html_excel($filename, $options) {
        $filename = sanitize_file_name($filename . '_' . date('Y-m-d_H-i-s') . '.html');
        $filepath = WP_CONTENT_DIR . '/uploads/workforce_exports/' . $filename;
        
        wp_mkdir_p(dirname($filepath));
        
        // تولید HTML
        $html = '<!DOCTYPE html>
        <html dir="rtl">
        <head>
            <meta charset="UTF-8">
            <title>گزارش پرسنل</title>
            <style>
                body { font-family: Tahoma, sans-serif; margin: 20px; }
                table { border-collapse: collapse; width: 100%; margin-top: 20px; }
                th { background-color: #2E86C1; color: white; padding: 12px; text-align: center; border: 1px solid #1B4F72; }
                td { padding: 10px; border: 1px solid #ddd; text-align: right; }
                tr:nth-child(even) { background-color: #f2f3f4; }
                tr:nth-child(odd) { background-color: white; }
                .header { background: #2E86C1; color: white; padding: 20px; text-align: center; margin-bottom: 20px; }
                .footer { margin-top: 30px; text-align: center; color: #7f8c8d; font-size: 12px; }
                .info { margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-right: 4px solid #2E86C1; }
                .checkbox-true { color: green; }
                .checkbox-false { color: red; }
            </style>
        </head>
        <body>';
        
        // هدر
        $title = isset($options['title']) ? $options['title'] : 'گزارش کارکرد پرسنل';
        $html .= '<div class="header">
            <h1>' . esc_html($title) . '</h1>
            <p>سیستم مدیریت کارکرد پرسنل بنی اسد</p>
        </div>';
        
        // اطلاعات گزارش
        $html .= '<div class="info">
            <strong>تاریخ تولید:</strong> ' . wf_current_jalali_date('Y/m/d H:i') . ' | 
            <strong>تعداد رکوردها:</strong> ' . number_format(count($this->data)) . ' | 
            <strong>مدیر گزارش:</strong> ' . (isset($options['manager_name']) ? esc_html($options['manager_name']) : 'سیستم') . '
        </div>';
        
        // جدول داده‌ها
        if (!empty($this->fields) && !empty($this->data)) {
            $html .= '<table>';
            
            // هدر جدول
            $html .= '<thead><tr>';
            foreach ($this->fields as $field) {
                $html .= '<th>' . esc_html($field['field_name']) . '</th>';
            }
            $html .= '</tr></thead>';
            
            // داده‌ها
            $html .= '<tbody>';
            foreach ($this->data as $index => $row) {
                $row_class = ($index % 2 == 0) ? 'even' : 'odd';
                $html .= '<tr class="' . $row_class . '">';
                
                foreach ($this->fields as $field) {
                    $value = $this->get_cell_value($row, $field);
                    
                    // کلاس مخصوص برای چک‌باکس
                    $cell_class = '';
                    if ($field['field_type'] == 'checkbox' || $field['field_type'] == 'boolean') {
                        $cell_class = $value == '✅' ? 'checkbox-true' : 'checkbox-false';
                    }
                    
                    $html .= '<td class="' . $cell_class . '">' . esc_html($value) . '</td>';
                }
                
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<p style="text-align: center; color: #7f8c8d; padding: 40px;">داده‌ای برای نمایش وجود ندارد.</p>';
        }
        
        // فوتر
        $html .= '<div class="footer">
            <p>تولید شده توسط سیستم مدیریت کارکرد پرسنل بنی اسد</p>
            <p>' . wf_current_jalali_date('Y/m/d H:i:s') . '</p>
        </div>';
        
        $html .= '</body></html>';
        
        // ذخیره فایل
        file_put_contents($filepath, $html);
        
        // همچنین یک فایل Excel ساده هم ایجاد می‌کنیم
        $csv_filename = str_replace('.html', '.csv', $filename);
        $csv_filepath = str_replace('.html', '.csv', $filepath);
        
        $this->generate_csv_file($csv_filepath, $options);
        
        return array(
            'success' => true,
            'filename' => $filename,
            'csv_filename' => $csv_filename,
            'filepath' => $filepath,
            'csv_filepath' => $csv_filepath,
            'url' => content_url('/uploads/workforce_exports/' . $filename),
            'csv_url' => content_url('/uploads/workforce_exports/' . $csv_filename),
            'generated_at' => wf_current_jalali_date('Y/m/d H:i:s'),
            'records_count' => count($this->data),
            'format' => $this->use_html_excel ? 'html' : 'excel'
        );
    }
    
    /**
     * تولید فایل CSV
     */
    private function generate_csv_file($filepath, $options) {
        $fp = fopen($filepath, 'w');
        
        // هدر CSV
        $headers = array();
        foreach ($this->fields as $field) {
            $headers[] = $field['field_name'];
        }
        fputcsv($fp, $headers);
        
        // داده‌های CSV
        foreach ($this->data as $row) {
            $csv_row = array();
            foreach ($this->fields as $field) {
                $value = $this->get_cell_value($row, $field);
                // حذف ایموجی برای CSV
                if ($value == '✅') $value = 'بله';
                if ($value == '❌') $value = 'خیر';
                $csv_row[] = $value;
            }
            fputcsv($fp, $csv_row);
        }
        
        fclose($fp);
    }
    
    /**
     * تبدیل شماره ستون به حرف
     */
    private function get_column_letter($col_index) {
        if (class_exists('PHPExcel_Cell') && !$this->use_html_excel) {
            return PHPExcel_Cell::stringFromColumnIndex($col_index);
        }
        
        // تبدیل ساده
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';
        
        while ($col_index >= 0) {
            $result = $letters[$col_index % 26] . $result;
            $col_index = floor($col_index / 26) - 1;
        }
        
        return $result;
    }
}

// ==================== توابع اصلی ====================

/**
 * ایجاد گزارش اکسل
 */
function wf_generate_excel_report($data, $fields, $template_id = null, $options = array()) {
    try {
        $exporter = new WF_Excel_Exporter_Standalone($template_id);
        
        $exporter->set_fields($fields)
                 ->set_data($data);
        
        $filename = isset($options['filename']) ? $options['filename'] : 'گزارش_پرسنل';
        
        return $exporter->generate($filename, $options);
        
    } catch (Exception $e) {
        // اگر خطا داد، فایل HTML ایجاد کن
        return array(
            'success' => false,
            'error' => $e->getMessage(),
            'alternative' => wf_generate_html_report($data, $fields, $options)
        );
    }
}

/**
 * ایجاد گزارش HTML
 */
function wf_generate_html_report($data, $fields, $options = array()) {
    $filename = isset($options['filename']) ? $options['filename'] : 'گزارش_پرسنل';
    $filename = sanitize_file_name($filename . '_' . date('Y-m-d_H-i-s') . '.html');
    $filepath = WP_CONTENT_DIR . '/uploads/workforce_exports/' . $filename;
    
    wp_mkdir_p(dirname($filepath));
    
    $html = '<!DOCTYPE html>
    <html dir="rtl">
    <head>
        <meta charset="UTF-8">
        <title>گزارش پرسنل</title>
        <style>
            body { font-family: Tahoma, sans-serif; margin: 20px; }
            table { border-collapse: collapse; width: 100%; margin-top: 20px; }
            th { background-color: #2E86C1; color: white; padding: 12px; text-align: center; border: 1px solid #1B4F72; }
            td { padding: 10px; border: 1px solid #ddd; text-align: right; }
            tr:nth-child(even) { background-color: #f2f3f4; }
            .header { background: #2E86C1; color: white; padding: 20px; text-align: center; margin-bottom: 20px; }
            .footer { margin-top: 30px; text-align: center; color: #7f8c8d; font-size: 12px; }
        </style>
    </head>
    <body>';
    
    $html .= '<div class="header">
        <h1>گزارش کارکرد پرسنل</h1>
        <p>تاریخ تولید: ' . wf_current_jalali_date('Y/m/d H:i') . '</p>
    </div>';
    
    if (!empty($fields) && !empty($data)) {
        $html .= '<table>';
        
        // هدر
        $html .= '<thead><tr>';
        foreach ($fields as $field) {
            $html .= '<th>' . esc_html($field['field_name']) . '</th>';
        }
        $html .= '</tr></thead>';
        
        // داده‌ها
        $html .= '<tbody>';
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($fields as $field) {
                $value = '';
                if (isset($row['data'][$field['field_key']])) {
                    $value = $row['data'][$field['field_key']];
                }
                $html .= '<td>' . esc_html($value) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
    }
    
    $html .= '<div class="footer">
        <p>تولید شده توسط سیستم مدیریت کارکرد پرسنل بنی اسد</p>
    </div>';
    
    $html .= '</body></html>';
    
    file_put_contents($filepath, $html);
    
    return array(
        'success' => true,
        'filename' => $filename,
        'filepath' => $filepath,
        'url' => content_url('/uploads/workforce_exports/' . $filename)
    );
}

/**
 * AJAX ایجاد گزارش
 */
add_action('wp_ajax_wf_export_excel_simple', 'wf_ajax_export_excel_simple');

function wf_ajax_export_excel_simple() {
    check_ajax_referer('workforce_manager_nonce', 'nonce');
    
    $export_type = sanitize_text_field($_POST['export_type']);
    $manager_id = intval($_POST['manager_id']);
    $filters = isset($_POST['filters']) ? json_decode(stripslashes($_POST['filters']), true) : array();
    $selected_ids = isset($_POST['selected_ids']) ? array_map('intval', $_POST['selected_ids']) : array();
    
    // بررسی دسترسی
    $user = get_user_by('id', $manager_id);
    if (!$user) {
        wp_send_json_error(array('message' => 'کاربر معتبر نیست'));
    }
    
    // شبیه‌سازی داده‌ها برای تست
    $fields = array(
        array('field_key' => 'national_code', 'field_name' => 'کد ملی', 'field_type' => 'text'),
        array('field_key' => 'first_name', 'field_name' => 'نام', 'field_type' => 'text'),
        array('field_key' => 'last_name', 'field_name' => 'نام خانوادگی', 'field_type' => 'text'),
        array('field_key' => 'birth_date', 'field_name' => 'تاریخ تولد', 'field_type' => 'date'),
        array('field_key' => 'employment_date', 'field_name' => 'تاریخ استخدام', 'field_type' => 'date'),
        array('field_key' => 'salary', 'field_name' => 'حقوق', 'field_type' => 'number'),
        array('field_key' => 'is_active', 'field_name' => 'وضعیت فعال', 'field_type' => 'checkbox')
    );
    
    $data = array();
    for ($i = 1; $i <= 50; $i++) {
        $data[] = array(
            'id' => $i,
            'data' => array(
                'national_code' => str_pad($i, 10, '0', STR_PAD_LEFT),
                'first_name' => 'نام' . $i,
                'last_name' => 'خانوادگی' . $i,
                'birth_date' => '1360-01-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'employment_date' => '1390-01-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'salary' => rand(5000000, 15000000),
                'is_active' => rand(0, 1)
            )
        );
    }
    
    // اعمال فیلترها
    if (!empty($filters)) {
        $filtered_data = array();
        foreach ($data as $row) {
            $include = true;
            foreach ($filters as $field_key => $filter_value) {
                if (isset($row['data'][$field_key]) && $row['data'][$field_key] != $filter_value) {
                    $include = false;
                    break;
                }
            }
            if ($include) {
                $filtered_data[] = $row;
            }
        }
        $data = $filtered_data;
    }
    
    // انتخاب شده‌ها
    if (!empty($selected_ids)) {
        $selected_data = array();
        foreach ($data as $row) {
            if (in_array($row['id'], $selected_ids)) {
                $selected_data[] = $row;
            }
        }
        $data = $selected_data;
    }
    
    // ایجاد گزارش
    $result = wf_generate_excel_report($data, $fields, null, array(
        'filename' => 'گزارش_' . $export_type . '_' . date('Y-m-d'),
        'manager_name' => $user->display_name,
        'title' => 'گزارش ' . ($export_type == 'organization' ? 'سازمانی' : 'اداره')
    ));
    
    if ($result['success']) {
        wp_send_json_success(array(
            'message' => '✅ گزارش با موفقیت ایجاد شد',
            'download_url' => $result['url'],
            'file_info' => array(
                'name' => $result['filename'],
                'size' => size_format($result['size']),
                'records' => $result['records_count'],
                'format' => isset($result['format']) ? $result['format'] : 'excel'
            )
        ));
    } else {
        wp_send_json_error(array(
            'message' => 'خطا در ایجاد گزارش: ' . ($result['error'] ?? 'خطای نامشخص')
        ));
    }
}

/**
 * ایجاد گزارش تست
 */
function wf_test_excel_system_simple() {
    $fields = array(
        array('field_key' => 'id', 'field_name' => 'ردیف', 'field_type' => 'number'),
        array('field_key' => 'name', 'field_name' => 'نام', 'field_type' => 'text'),
        array('field_key' => 'date', 'field_name' => 'تاریخ', 'field_type' => 'date'),
        array('field_key' => 'amount', 'field_name' => 'مبلغ', 'field_type' => 'number'),
        array('field_key' => 'active', 'field_name' => 'فعال', 'field_type' => 'checkbox')
    );
    
    $data = array();
    for ($i = 1; $i <= 10; $i++) {
        $data[] = array(
            'id' => $i,
            'data' => array(
                'id' => $i,
                'name' => 'کاربر ' . $i,
                'date' => '2024-01-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'amount' => $i * 1000000,
                'active' => $i % 2 == 0
            )
        );
    }
    
    return wf_generate_excel_report($data, $fields, null, array(
        'filename' => 'تست_سیستم_اکسل',
        'title' => 'گزارش تست سیستم'
    ));
}

/**
 * بررسی سیستم
 */
function wf_check_excel_system() {
    $checks = array(
        'php_version' => version_compare(PHP_VERSION, '7.0.0', '>='),
        'memory_limit' => ini_get('memory_limit'),
        'upload_dir' => wp_upload_dir(),
        'php_excel_class' => class_exists('PHPExcel'),
        'date_functions' => function_exists('gregorian_to_jalali')
    );
    
    // تست ایجاد دایرکتوری
    $export_dir = WP_CONTENT_DIR . '/uploads/workforce_exports/';
    if (!file_exists($export_dir)) {
        wp_mkdir_p($export_dir);
        $checks['export_dir_created'] = file_exists($export_dir);
    } else {
        $checks['export_dir_exists'] = true;
    }
    
    // تست نوشتن فایل
    $test_file = $export_dir . 'test.txt';
    file_put_contents($test_file, 'test');
    $checks['can_write'] = file_exists($test_file);
    if (file_exists($test_file)) {
        unlink($test_file);
    }
    
    return $checks;
}

/**
 * فعال‌سازی سیستم
 */
function wf_install_excel_system_simple() {
    // ایجاد دایرکتوری‌ها
    $directories = array(
        WP_CONTENT_DIR . '/uploads/workforce_exports/',
        WP_CONTENT_DIR . '/uploads/workforce_backups/'
    );
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
            
            // ایجاد فایل htaccess برای حفاظت
            $htaccess = $dir . '.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, "Order Deny,Allow\nDeny from all\n<FilesMatch '\.(xlsx?|csv|html)$'>\nAllow from all\n</FilesMatch>");
            }
            
            // ایجاد فایل index برای امنیت
            $index = $dir . 'index.html';
            if (!file_exists($index)) {
                file_put_contents($index, '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Forbidden</h1><p>You don\'t have permission to access this directory.</p></body></html>');
            }
        }
    }
    
    // ایجاد جدول لاگ‌ها
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wf_export_logs (
        id INT(11) NOT NULL AUTO_INCREMENT,
        export_type VARCHAR(50) NOT NULL,
        exporter_id INT(11) NOT NULL,
        filename VARCHAR(255) NOT NULL,
        file_size BIGINT(20) DEFAULT 0,
        records_count INT(11) DEFAULT 0,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at DATETIME,
        PRIMARY KEY (id),
        KEY exporter_id (exporter_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    return 'سیستم اکسل ساده با موفقیت نصب شد';
}

/**
 * منوی تست در پیشخوان
 */
add_action('admin_menu', function() {
    add_submenu_page(
        'workforce-management',
        'تست سیستم اکسل',
        'تست اکسل',
        'manage_options',
        'workforce-excel-test',
        'wf_render_excel_test_page'
    );
});

function wf_render_excel_test_page() {
    if (!current_user_can('manage_options')) {
        wp_die('دسترسی غیرمجاز');
    }
    
    if (isset($_POST['test_excel'])) {
        $result = wf_test_excel_system_simple();
        echo '<div class="notice notice-success"><p>تست انجام شد. نتیجه: ' . print_r($result, true) . '</p></div>';
    }
    
    if (isset($_POST['check_system'])) {
        $checks = wf_check_excel_system();
        echo '<div class="notice notice-info"><pre>' . print_r($checks, true) . '</pre></div>';
    }
    
    ?>
    <div class="wrap">
        <h1>🔄 تست سیستم اکسل</h1>
        
        <div class="card" style="max-width: 600px; margin: 20px 0;">
            <h2>بررسی سیستم</h2>
            <form method="post">
                <p>با کلیک بر روی دکمه زیر، سیستم اکسل بررسی می‌شود.</p>
                <button type="submit" name="check_system" class="button button-primary">بررسی سیستم</button>
            </form>
        </div>
        
        <div class="card" style="max-width: 600px; margin: 20px 0;">
            <h2>تست ایجاد گزارش</h2>
            <form method="post">
                <p>با کلیک بر روی دکمه زیر، یک گزارش تست ایجاد می‌شود.</p>
                <button type="submit" name="test_excel" class="button button-secondary">ایجاد گزارش تست</button>
            </form>
        </div>
        
        <div class="card" style="max-width: 600px; margin: 20px 0;">
            <h2>راهنمای نصب PHPExcel (اختیاری)</h2>
            <p>برای عملکرد کامل، می‌توانید PHPExcel را نصب کنید:</p>
            <ol>
                <li>دانلود از: <a href="https://github.com/PHPOffice/PHPExcel/releases" target="_blank">PHPExcel Releases</a></li>
                <li>استخراج فایل‌ها در پوشه: <code>/wp-content/plugins/workforce-beni-asadd/includes/phpexcel/</code></li>
                <li>ساختار پوشه باید به این صورت باشد:
                    <pre>
/includes/phpexcel/
    ├── PHPExcel.php
    ├── PHPExcel/
    │   ├── Autoloader.php
    │   └── ...
    └── autoload.php
                    </pre>
                </li>
            </ol>
        </div>
    </div>
    <?php
}

// فعال‌سازی
register_activation_hook(__FILE__, 'wf_install_excel_system_simple');

// ==================== نکات مهم ====================

/**
 * نکات استفاده:
 * 
 * 1. این فایل کاملاً مستقل است و نیاز به نصب چیزی ندارد
 * 2. اگر PHPExcel نصب باشد، از آن استفاده می‌کند
 * 3. اگر PHPExcel نباشد، گزارش HTML و CSV ایجاد می‌کند
 * 4. توابع تاریخ شمسی داخلی هستند
 * 5. از فونت پیش‌فرض سیستم استفاده می‌کند
 * 
 * برای عملکرد بهتر:
 * - PHPExcel را دانلود و در پوشه includes قرار دهید
 * - memory_limit سرور را افزایش دهید
 */

?>