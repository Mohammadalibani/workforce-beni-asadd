<?php
/**
 * سیستم مدیریت پایگاه داده - پلاگین مدیریت کارکرد پرسنل بنی اسد
 * ایجاد جداول و توابع CRUD برای سیستم
 * 
 * @package Workforce_Beni_Asad
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ایجاد جداول دیتابیس هنگام فعال‌سازی پلاگین
 * 
 * @hook register_activation_hook
 */
function wf_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // 1. جدول فیلدهای تعریف شده
    $table_fields = $wpdb->prefix . 'wf_fields';
    $sql_fields = "CREATE TABLE IF NOT EXISTS {$table_fields} (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        title VARCHAR(200) NOT NULL,
        type VARCHAR(50) NOT NULL COMMENT 'text, number, decimal, date, time, datetime',
        `default` VARCHAR(255) DEFAULT NULL,
        is_required TINYINT(1) DEFAULT 0 COMMENT 'فیلد الزامی',
        is_locked TINYINT(1) DEFAULT 0 COMMENT 'فیلد قفل شده',
        is_monitoring TINYINT(1) DEFAULT 0 COMMENT 'نمایش در کارت مانیتورینگ',
        is_key TINYINT(1) DEFAULT 0 COMMENT 'فیلد کلید (کدملی)',
        field_order INT(11) DEFAULT 0,
        validation_rules TEXT,
        help_text TEXT,
        options TEXT COMMENT 'برای فیلدهای انتخابی',
        status ENUM('active', 'inactive', 'deleted') DEFAULT 'active',
        created_by INT(11),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_name (name),
        KEY idx_status (status),
        KEY idx_type (type),
        KEY idx_order (field_order)
    ) {$charset_collate} ENGINE=InnoDB;";
    
    // 2. جدول ادارات
    $table_departments = $wpdb->prefix . 'wf_departments';
    $sql_departments = "CREATE TABLE IF NOT EXISTS {$table_departments} (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(200) NOT NULL,
        code VARCHAR(50) UNIQUE,
        description TEXT,
        manager_id INT(11),
        color VARCHAR(20) DEFAULT '#3b82f6',
        parent_id INT(11) DEFAULT 0 COMMENT 'برای ساختار سلسله مراتبی',
        organization_id INT(11) DEFAULT 1,
        phone VARCHAR(20),
        email VARCHAR(100),
        address TEXT,
        settings TEXT COMMENT 'تنظیمات اختصاصی اداره',
        sort_order INT(11) DEFAULT 0,
        status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
        created_by INT(11),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_code (code),
        KEY idx_manager (manager_id),
        KEY idx_parent (parent_id),
        KEY idx_status (status),
        KEY idx_organization (organization_id),
        FULLTEXT KEY ft_name (name)
    ) {$charset_collate} ENGINE=InnoDB;";
    
    // 3. جدول پرسنل
    $table_personnel = $wpdb->prefix . 'wf_personnel';
    $sql_personnel = "CREATE TABLE IF NOT EXISTS {$table_personnel} (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        national_id VARCHAR(20) UNIQUE COMMENT 'کدملی - کلید اصلی',
        personnel_code VARCHAR(50) UNIQUE COMMENT 'کد پرسنلی',
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        father_name VARCHAR(100),
        birth_date DATE,
        birth_city VARCHAR(100),
        gender ENUM('male', 'female') DEFAULT 'male',
        marital_status ENUM('single', 'married', 'divorced', 'widowed'),
        education VARCHAR(100),
        field_of_study VARCHAR(200),
        mobile VARCHAR(20),
        phone VARCHAR(20),
        email VARCHAR(100),
        address TEXT,
        postal_code VARCHAR(20),
        department_id INT(11) NOT NULL,
        position VARCHAR(200),
        employment_type ENUM('permanent', 'contractual', 'temporary', 'project'),
        employment_date DATE,
        insurance_no VARCHAR(50),
        tax_no VARCHAR(50),
        bank_name VARCHAR(100),
        bank_account VARCHAR(50),
        card_number VARCHAR(50),
        salary DECIMAL(15,2) DEFAULT 0.00,
        benefits DECIMAL(15,2) DEFAULT 0.00,
        deductions DECIMAL(15,2) DEFAULT 0.00,
        net_salary DECIMAL(15,2) DEFAULT 0.00,
        status ENUM('active', 'inactive', 'suspended', 'deleted', 'pending') DEFAULT 'active',
        period_id INT(11) NOT NULL COMMENT 'دوره کاری',
        custom_fields TEXT COMMENT 'فیلدهای سفارشی به صورت JSON',
        notes TEXT,
        attachments TEXT COMMENT 'فایل‌های پیوست',
        verified_by INT(11),
        verified_at DATETIME,
        created_by INT(11),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_national_id (national_id),
        UNIQUE KEY unique_personnel_code (personnel_code),
        KEY idx_department (department_id),
        KEY idx_status (status),
        KEY idx_period (period_id),
        KEY idx_employment_date (employment_date),
        KEY idx_created_by (created_by),
        KEY idx_full_name (first_name, last_name),
        KEY idx_mobile (mobile),
        FULLTEXT KEY ft_search (first_name, last_name, father_name, national_id, personnel_code)
    ) {$charset_collate} ENGINE=InnoDB;";
    
    // 4. جدول دوره‌های کاری
    $table_periods = $wpdb->prefix . 'wf_periods';
    $sql_periods = "CREATE TABLE IF NOT EXISTS {$table_periods} (
        id INT(11) NOT NULL AUTO_INCREMENT,
        title VARCHAR(200) NOT NULL,
        year SMALLINT(4) NOT NULL,
        month TINYINT(2) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        is_current TINYINT(1) DEFAULT 0 COMMENT 'دوره جاری',
        settings TEXT COMMENT 'تنظیمات دوره',
        status ENUM('active', 'inactive', 'closed', 'archived') DEFAULT 'active',
        closed_by INT(11),
        closed_at DATETIME,
        created_by INT(11),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_period (year, month),
        KEY idx_status (status),
        KEY idx_current (is_current),
        KEY idx_dates (start_date, end_date)
    ) {$charset_collate} ENGINE=InnoDB;";
    
    // 5. جدول درخواست‌های تایید
    $table_approvals = $wpdb->prefix . 'wf_approvals';
    $sql_approvals = "CREATE TABLE IF NOT EXISTS {$table_approvals} (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        request_type ENUM('add_personnel', 'edit_personnel', 'delete_personnel', 'edit_field', 'other') NOT NULL,
        personnel_id BIGINT(20) COMMENT 'برای درخواست‌های مربوط به پرسنل',
        department_id INT(11),
        requested_by INT(11) NOT NULL,
        request_data TEXT NOT NULL COMMENT 'داده‌های درخواست به صورت JSON',
        request_reason TEXT,
        status ENUM('pending', 'approved', 'rejected', 'returned', 'cancelled') DEFAULT 'pending',
        reviewed_by INT(11),
        review_notes TEXT,
        reviewed_at DATETIME,
        action_taken TEXT COMMENT 'اقدام انجام شده',
        due_date DATE,
        priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
        attachments TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_status (status),
        KEY idx_request_type (request_type),
        KEY idx_requested_by (requested_by),
        KEY idx_reviewed_by (reviewed_by),
        KEY idx_due_date (due_date),
        KEY idx_priority (priority),
        KEY idx_personnel (personnel_id),
        KEY idx_department (department_id),
        KEY idx_created_at (created_at)
    ) {$charset_collate} ENGINE=InnoDB;";
    
    // 6. جدول فعالیت‌های سیستم
    $table_activities = $wpdb->prefix . 'wf_activities';
    $sql_activities = "CREATE TABLE IF NOT EXISTS {$table_activities} (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        activity_type VARCHAR(100) NOT NULL,
        activity_module VARCHAR(100),
        record_id BIGINT(20),
        description TEXT NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        metadata TEXT COMMENT 'داده‌های اضافی به صورت JSON',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_user_id (user_id),
        KEY idx_activity_type (activity_type),
        KEY idx_module (activity_module),
        KEY idx_record_id (record_id),
        KEY idx_created_at (created_at)
    ) {$charset_collate} ENGINE=InnoDB;";
    
    // 7. جدول تنظیمات سیستم
    $table_settings = $wpdb->prefix . 'wf_settings';
    $sql_settings = "CREATE TABLE IF NOT EXISTS {$table_settings} (
        id INT(11) NOT NULL AUTO_INCREMENT,
        setting_key VARCHAR(100) NOT NULL,
        setting_value LONGTEXT,
        setting_group VARCHAR(50) DEFAULT 'general',
        data_type ENUM('string', 'number', 'boolean', 'array', 'object') DEFAULT 'string',
        is_public TINYINT(1) DEFAULT 0 COMMENT 'قابل نمایش برای مدیران',
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_key (setting_key),
        KEY idx_group (setting_group)
    ) {$charset_collate} ENGINE=InnoDB;";
    
    // 8. جدول گزارش‌های سیستمی
    $table_reports = $wpdb->prefix . 'wf_reports';
    $sql_reports = "CREATE TABLE IF NOT EXISTS {$table_reports} (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        report_type VARCHAR(100) NOT NULL,
        report_name VARCHAR(200) NOT NULL,
        report_filters TEXT COMMENT 'فیلترهای گزارش به صورت JSON',
        report_data LONGTEXT COMMENT 'داده‌های گزارش',
        file_path VARCHAR(500),
        file_size INT(11),
        mime_type VARCHAR(100),
        generated_by INT(11) NOT NULL,
        department_id INT(11),
        period_id INT(11),
        status ENUM('pending', 'generating', 'completed', 'failed', 'deleted') DEFAULT 'pending',
        generation_time INT(11) COMMENT 'زمان تولید به ثانیه',
        download_count INT(11) DEFAULT 0,
        expires_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_report_type (report_type),
        KEY idx_generated_by (generated_by),
        KEY idx_department (department_id),
        KEY idx_period (period_id),
        KEY idx_status (status),
        KEY idx_created_at (created_at)
    ) {$charset_collate} ENGINE=InnoDB;";
    
    // 9. جدول قالب‌های اکسل
    $table_excel_templates = $wpdb->prefix . 'wf_excel_templates';
    $sql_excel_templates = "CREATE TABLE IF NOT EXISTS {$table_excel_templates} (
        id INT(11) NOT NULL AUTO_INCREMENT,
        template_name VARCHAR(200) NOT NULL,
        template_type VARCHAR(50) DEFAULT 'personnel_report',
        settings TEXT NOT NULL COMMENT 'تنظیمات قالب به صورت JSON',
        is_default TINYINT(1) DEFAULT 0,
        created_by INT(11),
        department_id INT(11),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_template_type (template_type),
        KEY idx_created_by (created_by),
        KEY idx_department (department_id),
        KEY idx_status (status)
    ) {$charset_collate} ENGINE=InnoDB;";
    
    // 10. جدول کاربران سیستمی
    $table_system_users = $wpdb->prefix . 'wf_system_users';
    $sql_system_users = "CREATE TABLE IF NOT EXISTS {$table_system_users} (
        id INT(11) NOT NULL AUTO_INCREMENT,
        wp_user_id INT(11) NOT NULL,
        role ENUM('admin', 'organization_manager', 'department_manager', 'viewer', 'auditor') DEFAULT 'viewer',
        department_id INT(11),
        permissions TEXT COMMENT 'سطح دسترسی‌های اضافی',
        settings TEXT COMMENT 'تنظیمات کاربر',
        last_login DATETIME,
        login_count INT(11) DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_wp_user (wp_user_id),
        KEY idx_role (role),
        KEY idx_department (department_id),
        KEY idx_is_active (is_active),
        KEY idx_last_login (last_login)
    ) {$charset_collate} ENGINE=InnoDB;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // اجرای کوئری‌ها
    dbDelta($sql_fields);
    dbDelta($sql_departments);
    dbDelta($sql_personnel);
    dbDelta($sql_periods);
    dbDelta($sql_approvals);
    dbDelta($sql_activities);
    dbDelta($sql_settings);
    dbDelta($sql_reports);
    dbDelta($sql_excel_templates);
    dbDelta($sql_system_users);
    
    // ایجاد داده‌های اولیه
    wf_create_initial_data();
    
    // ثبت فعالیت
    wf_log_activity(0, 'system', 'tables_created', 'جداول دیتابیس ایجاد شدند');
}

/**
 * ایجاد داده‌های اولیه
 */
function wf_create_initial_data() {
    global $wpdb;
    
    // ایجاد دوره پیش‌فرض
    $periods_table = $wpdb->prefix . 'wf_periods';
    $current_period = $wpdb->get_var("SELECT COUNT(*) FROM {$periods_table}");
    
    if ($current_period == 0) {
        $current_year = date('Y');
        $current_month = date('n');
        $persian_date = wf_gregorian_to_persian(date('Y-m-d'));
        
        $wpdb->insert($periods_table, array(
            'title' => sprintf('ماه %s %s', $persian_date['month_name'], $persian_date['year']),
            'year' => $persian_date['year'],
            'month' => $persian_date['month'],
            'start_date' => date('Y-m-01'),
            'end_date' => date('Y-m-t'),
            'is_current' => 1,
            'status' => 'active',
            'created_by' => 1
        ));
    }
    
    // ایجاد فیلدهای پیش‌فرض
    $fields_table = $wpdb->prefix . 'wf_fields';
    $current_fields = $wpdb->get_var("SELECT COUNT(*) FROM {$fields_table}");
    
    if ($current_fields == 0) {
        $default_fields = array(
            array(
                'name' => 'national_id',
                'title' => 'کد ملی',
                'type' => 'text',
                'is_required' => 1,
                'is_locked' => 1,
                'is_key' => 1,
                'field_order' => 1,
                'validation_rules' => json_encode(array('length' => 10, 'numeric' => true)),
                'help_text' => 'کد ملی ۱۰ رقمی'
            ),
            array(
                'name' => 'first_name',
                'title' => 'نام',
                'type' => 'text',
                'is_required' => 1,
                'is_locked' => 0,
                'field_order' => 2,
                'validation_rules' => json_encode(array('min_length' => 2, 'max_length' => 50)),
                'help_text' => 'نام پرسنل'
            ),
            array(
                'name' => 'last_name',
                'title' => 'نام خانوادگی',
                'type' => 'text',
                'is_required' => 1,
                'is_locked' => 0,
                'field_order' => 3,
                'validation_rules' => json_encode(array('min_length' => 2, 'max_length' => 50)),
                'help_text' => 'نام خانوادگی پرسنل'
            ),
            array(
                'name' => 'mobile',
                'title' => 'تلفن همراه',
                'type' => 'text',
                'is_required' => 0,
                'is_locked' => 0,
                'field_order' => 4,
                'validation_rules' => json_encode(array('pattern' => '09[0-9]{9}')),
                'help_text' => 'شماره تلفن همراه ۱۱ رقمی'
            ),
            array(
                'name' => 'department_id',
                'title' => 'اداره',
                'type' => 'number',
                'is_required' => 1,
                'is_locked' => 1,
                'field_order' => 5,
                'help_text' => 'انتخاب اداره'
            ),
            array(
                'name' => 'salary',
                'title' => 'حقوق',
                'type' => 'decimal',
                'is_required' => 0,
                'is_locked' => 0,
                'is_monitoring' => 1,
                'field_order' => 6,
                'validation_rules' => json_encode(array('min' => 0, 'max' => 9999999999)),
                'help_text' => 'مبلغ حقوق به ریال'
            ),
            array(
                'name' => 'employment_date',
                'title' => 'تاریخ استخدام',
                'type' => 'date',
                'is_required' => 0,
                'is_locked' => 0,
                'field_order' => 7,
                'help_text' => 'تاریخ شروع به کار'
            ),
            array(
                'name' => 'position',
                'title' => 'سمت',
                'type' => 'text',
                'is_required' => 0,
                'is_locked' => 0,
                'field_order' => 8,
                'help_text' => 'سمت سازمانی'
            )
        );
        
        foreach ($default_fields as $field) {
            $wpdb->insert($fields_table, $field);
        }
    }
    
    // ایجاد تنظیمات پیش‌فرض
    $settings_table = $wpdb->prefix . 'wf_settings';
    $current_settings = $wpdb->get_var("SELECT COUNT(*) FROM {$settings_table}");
    
    if ($current_settings == 0) {
        $default_settings = array(
            array(
                'setting_key' => 'system_name',
                'setting_value' => 'سیستم مدیریت پرسنل بنی اسد',
                'setting_group' => 'general',
                'data_type' => 'string',
                'is_public' => 1,
                'description' => 'نام سیستم'
            ),
            array(
                'setting_key' => 'organization_name',
                'setting_value' => 'سازمان بنی اسد',
                'setting_group' => 'general',
                'data_type' => 'string',
                'is_public' => 1,
                'description' => 'نام سازمان'
            ),
            array(
                'setting_key' => 'excel_template_default',
                'setting_value' => json_encode(array(
                    'header_color' => '#1e40af',
                    'header_font_size' => 12,
                    'data_font_size' => 11,
                    'even_row_color' => '#f8fafc',
                    'odd_row_color' => '#ffffff',
                    'border_style' => 'thin',
                    'border_color' => '#d1d5db'
                )),
                'setting_group' => 'excel',
                'data_type' => 'object',
                'is_public' => 1,
                'description' => 'قالب پیش‌فرض گزارش اکسل'
            ),
            array(
                'setting_key' => 'max_dynamic_cards',
                'setting_value' => '6',
                'setting_group' => 'ui',
                'data_type' => 'number',
                'is_public' => 1,
                'description' => 'حداکثر تعداد کارت‌های داینامیک'
            ),
            array(
                'setting_key' => 'auto_logout_minutes',
                'setting_value' => '30',
                'setting_group' => 'security',
                'data_type' => 'number',
                'is_public' => 1,
                'description' => 'مدت زمان عدم فعالیت قبل از خروج خودکار (دقیقه)'
            ),
            array(
                'setting_key' => 'default_rows_per_page',
                'setting_value' => '25',
                'setting_group' => 'ui',
                'data_type' => 'number',
                'is_public' => 1,
                'description' => 'تعداد رکورد پیش‌فرض در هر صفحه'
            ),
            array(
                'setting_key' => 'enable_audit_log',
                'setting_value' => '1',
                'setting_group' => 'security',
                'data_type' => 'boolean',
                'is_public' => 0,
                'description' => 'فعال کردن ثبت لاگ فعالیت‌ها'
            )
        );
        
        foreach ($default_settings as $setting) {
            $wpdb->insert($settings_table, $setting);
        }
    }
}

/**
 * ============================================
 * توابع CRUD برای فیلدها
 * ============================================
 */

/**
 * ایجاد فیلد جدید
 * 
 * @param array $data داده‌های فیلد
 * @return int|WP_Error شناسه فیلد یا خطا
 */
function wf_create_field($data) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_fields';
    
    // اعتبارسنجی داده‌ها
    $errors = wf_validate_field_data($data);
    if (!empty($errors)) {
        return new WP_Error('validation_error', implode(', ', $errors));
    }
    
    // تنظیم مقادیر پیش‌فرض
    $defaults = array(
        'type' => 'text',
        'is_required' => 0,
        'is_locked' => 0,
        'is_monitoring' => 0,
        'is_key' => 0,
        'field_order' => 0,
        'status' => 'active',
        'created_by' => get_current_user_id(),
        'created_at' => current_time('mysql')
    );
    
    $data = wp_parse_args($data, $defaults);
    
    // کدگذاری JSON برای برخی فیلدها
    if (isset($data['validation_rules']) && is_array($data['validation_rules'])) {
        $data['validation_rules'] = json_encode($data['validation_rules']);
    }
    
    if (isset($data['options']) && is_array($data['options'])) {
        $data['options'] = json_encode($data['options']);
    }
    
    // بررسی تکراری نبودن نام فیلد
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE name = %s AND status != 'deleted'",
        $data['name']
    ));
    
    if ($exists > 0) {
        return new WP_Error('duplicate_field', 'فیلد با این نام قبلاً ثبت شده است');
    }
    
    // درج در دیتابیس
    $result = $wpdb->insert($table, $data);
    
    if ($result === false) {
        return new WP_Error('db_error', $wpdb->last_error);
    }
    
    $field_id = $wpdb->insert_id;
    
    // ثبت فعالیت
    wf_log_activity(get_current_user_id(), 'field', 'field_created', 
        sprintf('فیلد "%s" ایجاد شد', $data['title']), $field_id);
    
    return $field_id;
}

/**
 * دریافت فیلد بر اساس شناسه
 * 
 * @param int $field_id شناسه فیلد
 * @return array|false داده‌های فیلد یا false
 */
function wf_get_field($field_id) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_fields';
    
    $field = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d AND status != 'deleted'",
        $field_id
    ), ARRAY_A);
    
    if (!$field) {
        return false;
    }
    
    // دیکد کردن JSON فیلدها
    if (!empty($field['validation_rules'])) {
        $field['validation_rules'] = json_decode($field['validation_rules'], true);
    }
    
    if (!empty($field['options'])) {
        $field['options'] = json_decode($field['options'], true);
    }
    
    return $field;
}

/**
 * دریافت همه فیلدها
 * 
 * @param array $params پارامترهای فیلتر
 * @return array لیست فیلدها
 */
function wf_get_fields($params = array()) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_fields';
    
    $defaults = array(
        'status' => 'active',
        'type' => '',
        'orderby' => 'field_order',
        'order' => 'ASC',
        'limit' => 0,
        'offset' => 0
    );
    
    $params = wp_parse_args($params, $defaults);
    
    $where = array("status != 'deleted'");
    $prepare_args = array();
    
    if (!empty($params['status']) && $params['status'] != 'all') {
        $where[] = "status = %s";
        $prepare_args[] = $params['status'];
    }
    
    if (!empty($params['type'])) {
        $where[] = "type = %s";
        $prepare_args[] = $params['type'];
    }
    
    $where_sql = implode(' AND ', $where);
    $order_sql = "ORDER BY {$params['orderby']} {$params['order']}";
    
    $limit_sql = '';
    if ($params['limit'] > 0) {
        $limit_sql = $wpdb->prepare("LIMIT %d OFFSET %d", 
            $params['limit'], $params['offset']);
    }
    
    $query = "SELECT * FROM {$table} WHERE {$where_sql} {$order_sql} {$limit_sql}";
    
    if (!empty($prepare_args)) {
        $query = $wpdb->prepare($query, $prepare_args);
    }
    
    $fields = $wpdb->get_results($query, ARRAY_A);
    
    // دیکد کردن JSON فیلدها
    foreach ($fields as &$field) {
        if (!empty($field['validation_rules'])) {
            $field['validation_rules'] = json_decode($field['validation_rules'], true);
        }
        
        if (!empty($field['options'])) {
            $field['options'] = json_decode($field['options'], true);
        }
    }
    
    return $fields;
}

/**
 * به‌روزرسانی فیلد
 * 
 * @param int $field_id شناسه فیلد
 * @param array $data داده‌های جدید
 * @return bool|WP_Error موفقیت یا خطا
 */
function wf_update_field($field_id, $data) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_fields';
    
    // بررسی وجود فیلد
    $existing = wf_get_field($field_id);
    if (!$existing) {
        return new WP_Error('not_found', 'فیلد مورد نظر یافت نشد');
    }
    
    // کدگذاری JSON برای برخی فیلدها
    if (isset($data['validation_rules']) && is_array($data['validation_rules'])) {
        $data['validation_rules'] = json_encode($data['validation_rules']);
    }
    
    if (isset($data['options']) && is_array($data['options'])) {
        $data['options'] = json_encode($data['options']);
    }
    
    // افزودن زمان به‌روزرسانی
    $data['updated_at'] = current_time('mysql');
    
    // به‌روزرسانی در دیتابیس
    $result = $wpdb->update($table, $data, array('id' => $field_id));
    
    if ($result === false) {
        return new WP_Error('db_error', $wpdb->last_error);
    }
    
    // ثبت فعالیت
    wf_log_activity(get_current_user_id(), 'field', 'field_updated', 
        sprintf('فیلد "%s" به‌روزرسانی شد', $existing['title']), $field_id);
    
    return true;
}

/**
 * حذف منطقی فیلد
 * 
 * @param int $field_id شناسه فیلد
 * @return bool|WP_Error موفقیت یا خطا
 */
function wf_delete_field($field_id) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_fields';
    
    // بررسی وجود فیلد
    $field = wf_get_field($field_id);
    if (!$field) {
        return new WP_Error('not_found', 'فیلد مورد نظر یافت نشد');
    }
    
    // بررسی استفاده‌شده بودن فیلد
    $in_use = wf_check_field_in_use($field_id);
    if ($in_use) {
        return new WP_Error('field_in_use', 'این فیلد در داده‌های پرسنل استفاده شده و قابل حذف نیست');
    }
    
    // حذف منطقی
    $result = $wpdb->update($table, 
        array('status' => 'deleted', 'updated_at' => current_time('mysql')),
        array('id' => $field_id)
    );
    
    if ($result === false) {
        return new WP_Error('db_error', $wpdb->last_error);
    }
    
    // ثبت فعالیت
    wf_log_activity(get_current_user_id(), 'field', 'field_deleted', 
        sprintf('فیلد "%s" حذف شد', $field['title']), $field_id);
    
    return true;
}

/**
 * اعتبارسنجی داده‌های فیلد
 * 
 * @param array $data داده‌های فیلد
 * @return array لیست خطاها
 */
function wf_validate_field_data($data) {
    $errors = array();
    
    if (empty($data['name'])) {
        $errors[] = 'نام فیلد الزامی است';
    } elseif (!preg_match('/^[a-z][a-z0-9_]*$/', $data['name'])) {
        $errors[] = 'نام فیلد فقط می‌تواند شامل حروف کوچک انگلیسی، اعداد و زیرخط باشد';
    }
    
    if (empty($data['title'])) {
        $errors[] = 'عنوان فارسی فیلد الزامی است';
    }
    
    $valid_types = array('text', 'number', 'decimal', 'date', 'time', 'datetime', 'select', 'checkbox');
    if (empty($data['type']) || !in_array($data['type'], $valid_types)) {
        $errors[] = 'نوع فیلد معتبر نیست';
    }
    
    return $errors;
}

/**
 * بررسی استفاده‌شده بودن فیلد
 * 
 * @param int $field_id شناسه فیلد
 * @return bool
 */
function wf_check_field_in_use($field_id) {
    global $wpdb;
    
    $field = wf_get_field($field_id);
    if (!$field) {
        return false;
    }
    
    // بررسی وجود داده در جدول پرسنل برای این فیلد
    $personnel_table = $wpdb->prefix . 'wf_personnel';
    
    // برای فیلدهای استاندارد، بررسی می‌کنیم که آیا داده‌ای دارند یا خیر
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$personnel_table} 
         WHERE JSON_EXTRACT(custom_fields, '$.%s') IS NOT NULL 
         OR JSON_EXTRACT(custom_fields, '$.%s') != ''",
        $field['name'], $field['name']
    ));
    
    return $result > 0;
}

/**
 * ============================================
 * توابع CRUD برای ادارات
 * ============================================
 */

/**
 * ایجاد اداره جدید
 * 
 * @param array $data داده‌های اداره
 * @return int|WP_Error شناسه اداره یا خطا
 */
function wf_create_department($data) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_departments';
    
    // اعتبارسنجی
    if (empty($data['name'])) {
        return new WP_Error('validation_error', 'نام اداره الزامی است');
    }
    
    // تنظیم مقادیر پیش‌فرض
    $defaults = array(
        'code' => '',
        'description' => '',
        'manager_id' => 0,
        'color' => '#3b82f6',
        'parent_id' => 0,
        'organization_id' => 1,
        'sort_order' => 0,
        'status' => 'active',
        'created_by' => get_current_user_id(),
        'created_at' => current_time('mysql')
    );
    
    $data = wp_parse_args($data, $defaults);
    
    // کدگذاری JSON برای تنظیمات
    if (isset($data['settings']) && is_array($data['settings'])) {
        $data['settings'] = json_encode($data['settings']);
    }
    
    // بررسی تکراری نبودن کد
    if (!empty($data['code'])) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE code = %s AND status != 'archived'",
            $data['code']
        ));
        
        if ($exists > 0) {
            return new WP_Error('duplicate_code', 'اداره با این کد قبلاً ثبت شده است');
        }
    }
    
    // درج در دیتابیس
    $result = $wpdb->insert($table, $data);
    
    if ($result === false) {
        return new WP_Error('db_error', $wpdb->last_error);
    }
    
    $department_id = $wpdb->insert_id;
    
    // ثبت فعالیت
    wf_log_activity(get_current_user_id(), 'department', 'department_created', 
        sprintf('اداره "%s" ایجاد شد', $data['name']), $department_id);
    
    return $department_id;
}

/**
 * دریافت اداره بر اساس شناسه
 * 
 * @param int $department_id شناسه اداره
 * @return array|false داده‌های اداره یا false
 */
function wf_get_department($department_id) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_departments';
    
    $department = $wpdb->get_row($wpdb->prepare(
        "SELECT d.*, 
                u.display_name as manager_name,
                u.user_email as manager_email,
                p.name as parent_name
         FROM {$table} d
         LEFT JOIN {$wpdb->users} u ON d.manager_id = u.ID
         LEFT JOIN {$table} p ON d.parent_id = p.id
         WHERE d.id = %d AND d.status != 'archived'",
        $department_id
    ), ARRAY_A);
    
    if (!$department) {
        return false;
    }
    
    // دیکد کردن JSON تنظیمات
    if (!empty($department['settings'])) {
        $department['settings'] = json_decode($department['settings'], true);
    }
    
    return $department;
}

/**
 * دریافت همه ادارات
 * 
 * @param array $params پارامترهای فیلتر
 * @return array لیست ادارات
 */
function wf_get_departments($params = array()) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_departments';
    $users_table = $wpdb->users;
    
    $defaults = array(
        'status' => 'active',
        'parent_id' => null,
        'organization_id' => 1,
        'with_manager' => false,
        'orderby' => 'sort_order',
        'order' => 'ASC',
        'limit' => 0,
        'offset' => 0
    );
    
    $params = wp_parse_args($params, $defaults);
    
    $where = array("d.status != 'archived'");
    $prepare_args = array();
    
    if (!empty($params['status']) && $params['status'] != 'all') {
        $where[] = "d.status = %s";
        $prepare_args[] = $params['status'];
    }
    
    if (!empty($params['parent_id'])) {
        $where[] = "d.parent_id = %d";
        $prepare_args[] = $params['parent_id'];
    }
    
    if (!empty($params['organization_id'])) {
        $where[] = "d.organization_id = %d";
        $prepare_args[] = $params['organization_id'];
    }
    
    $where_sql = implode(' AND ', $where);
    
    // ساخت کوئری با یا بدون اطلاعات مدیر
    if ($params['with_manager']) {
        $select = "d.*, u.display_name as manager_name, u.user_email as manager_email";
        $join = "LEFT JOIN {$users_table} u ON d.manager_id = u.ID";
    } else {
        $select = "d.*";
        $join = "";
    }
    
    $order_sql = "ORDER BY d.{$params['orderby']} {$params['order']}";
    
    $limit_sql = '';
    if ($params['limit'] > 0) {
        $limit_sql = $wpdb->prepare("LIMIT %d OFFSET %d", 
            $params['limit'], $params['offset']);
    }
    
    $query = "SELECT {$select} FROM {$table} d {$join} WHERE {$where_sql} {$order_sql} {$limit_sql}";
    
    if (!empty($prepare_args)) {
        $query = $wpdb->prepare($query, $prepare_args);
    }
    
    $departments = $wpdb->get_results($query, ARRAY_A);
    
    // دیکد کردن JSON تنظیمات و اضافه کردن اطلاعات اضافی
    foreach ($departments as &$dept) {
        if (!empty($dept['settings'])) {
            $dept['settings'] = json_decode($dept['settings'], true);
        }
        
        // تعداد پرسنل
        $dept['personnel_count'] = wf_get_department_personnel_count($dept['id']);
        
        // وضعیت بر اساس درصد تکمیل
        $dept['completion_rate'] = wf_get_department_completion_rate($dept['id']);
    }
    
    return $departments;
}

/**
 * به‌روزرسانی اداره
 * 
 * @param int $department_id شناسه اداره
 * @param array $data داده‌های جدید
 * @return bool|WP_Error موفقیت یا خطا
 */
function wf_update_department($department_id, $data) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_departments';
    
    // بررسی وجود اداره
    $existing = wf_get_department($department_id);
    if (!$existing) {
        return new WP_Error('not_found', 'اداره مورد نظر یافت نشد');
    }
    
    // کدگذاری JSON برای تنظیمات
    if (isset($data['settings']) && is_array($data['settings'])) {
        $data['settings'] = json_encode($data['settings']);
    }
    
    // بررسی تکراری نبودن کد
    if (isset($data['code']) && $data['code'] !== $existing['code']) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE code = %s AND id != %d AND status != 'archived'",
            $data['code'], $department_id
        ));
        
        if ($exists > 0) {
            return new WP_Error('duplicate_code', 'کد اداره تکراری است');
        }
    }
    
    // افزودن زمان به‌روزرسانی
    $data['updated_at'] = current_time('mysql');
    
    // به‌روزرسانی در دیتابیس
    $result = $wpdb->update($table, $data, array('id' => $department_id));
    
    if ($result === false) {
        return new WP_Error('db_error', $wpdb->last_error);
    }
    
    // ثبت فعالیت
    wf_log_activity(get_current_user_id(), 'department', 'department_updated', 
        sprintf('اداره "%s" به‌روزرسانی شد', $existing['name']), $department_id);
    
    return true;
}

/**
 * حذف منطقی اداره
 * 
 * @param int $department_id شناسه اداره
 * @return bool|WP_Error موفقیت یا خطا
 */
function wf_delete_department($department_id) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_departments';
    
    // بررسی وجود اداره
    $department = wf_get_department($department_id);
    if (!$department) {
        return new WP_Error('not_found', 'اداره مورد نظر یافت نشد');
    }
    
    // بررسی داشتن پرسنل
    $personnel_count = wf_get_department_personnel_count($department_id);
    if ($personnel_count > 0) {
        return new WP_Error('has_personnel', 
            sprintf('این اداره دارای %d پرسنل است و نمی‌توان آن را حذف کرد', $personnel_count));
    }
    
    // بررسی داشتن زیرمجموعه
    $child_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE parent_id = %d AND status != 'archived'",
        $department_id
    ));
    
    if ($child_count > 0) {
        return new WP_Error('has_children', 'این اداره دارای زیرمجموعه است و نمی‌توان آن را حذف کرد');
    }
    
    // حذف منطقی
    $result = $wpdb->update($table, 
        array('status' => 'archived', 'updated_at' => current_time('mysql')),
        array('id' => $department_id)
    );
    
    if ($result === false) {
        return new WP_Error('db_error', $wpdb->last_error);
    }
    
    // ثبت فعالیت
    wf_log_activity(get_current_user_id(), 'department', 'department_deleted', 
        sprintf('اداره "%s" حذف شد', $department['name']), $department_id);
    
    return true;
}

/**
 * دریافت تعداد پرسنل یک اداره
 * 
 * @param int $department_id شناسه اداره
 * @return int تعداد پرسنل
 */
function wf_get_department_personnel_count($department_id) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_personnel';
    
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} 
         WHERE department_id = %d 
         AND status IN ('active', 'pending')",
        $department_id
    ));
}

/**
 * دریافت درصد تکمیل اطلاعات اداره
 * 
 * @param int $department_id شناسه اداره
 * @return float درصد تکمیل
 */
function wf_get_department_completion_rate($department_id) {
    global $wpdb;
    
    $personnel_table = $wpdb->prefix . 'wf_personnel';
    $fields_table = $wpdb->prefix . 'wf_fields';
    
    // دریافت فیلدهای الزامی
    $required_fields = $wpdb->get_results(
        "SELECT name FROM {$fields_table} WHERE is_required = 1 AND status = 'active'",
        ARRAY_A
    );
    
    if (empty($required_fields)) {
        return 100;
    }
    
    $required_field_names = array_column($required_fields, 'name');
    $total_personnel = wf_get_department_personnel_count($department_id);
    
    if ($total_personnel == 0) {
        return 0;
    }
    
    // شمارش پرسنل با اطلاعات کامل
    $completed_count = 0;
    
    foreach ($required_field_names as $field) {
        // این بخش نیاز به پیاده‌سازی دقیق‌تری دارد
        // بستگی به ساختار ذخیره‌سازی فیلدها دارد
    }
    
    return round(($completed_count / $total_personnel) * 100, 2);
}

/**
 * ============================================
 * توابع CRUD برای پرسنل
 * ============================================
 */

/**
 * ایجاد پرسنل جدید
 * 
 * @param array $data داده‌های پرسنل
 * @param bool $require_approval نیاز به تایید دارد؟
 * @return array|WP_Error نتیجه عملیات
 */
function wf_create_personnel($data, $require_approval = true) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_personnel';
    
    // اعتبارسنجی
    $errors = wf_validate_personnel_data($data);
    if (!empty($errors)) {
        return new WP_Error('validation_error', implode(', ', $errors));
    }
    
    // بررسی تکراری نبودن کدملی
    if (!empty($data['national_id'])) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE national_id = %s AND status != 'deleted'",
            $data['national_id']
        ));
        
        if ($exists > 0) {
            return new WP_Error('duplicate_national_id', 'کدملی تکراری است');
        }
    }
    
    // بررسی تکراری نبودن کد پرسنلی
    if (!empty($data['personnel_code'])) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE personnel_code = %s AND status != 'deleted'",
            $data['personnel_code']
        ));
        
        if ($exists > 0) {
            return new WP_Error('duplicate_personnel_code', 'کد پرسنلی تکراری است');
        }
    }
    
    // تنظیم مقادیر پیش‌فرض
    $defaults = array(
        'status' => $require_approval ? 'pending' : 'active',
        'period_id' => wf_get_current_period_id(),
        'created_by' => get_current_user_id(),
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    );
    
    $data = wp_parse_args($data, $defaults);
    
    // کدگذاری JSON برای فیلدهای سفارشی
    if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
        $data['custom_fields'] = json_encode($data['custom_fields']);
    }
    
    if (isset($data['attachments']) && is_array($data['attachments'])) {
        $data['attachments'] = json_encode($data['attachments']);
    }
    
    // محاسبه حقوق خالص
    if (isset($data['salary']) || isset($data['benefits']) || isset($data['deductions'])) {
        $salary = floatval($data['salary'] ?? 0);
        $benefits = floatval($data['benefits'] ?? 0);
        $deductions = floatval($data['deductions'] ?? 0);
        $data['net_salary'] = $salary + $benefits - $deductions;
    }
    
    // درج در دیتابیس
    $result = $wpdb->insert($table, $data);
    
    if ($result === false) {
        return new WP_Error('db_error', $wpdb->last_error);
    }
    
    $personnel_id = $wpdb->insert_id;
    
    // اگر نیاز به تایید دارد، ایجاد درخواست تایید
    if ($require_approval) {
        $approval_data = array(
            'request_type' => 'add_personnel',
            'personnel_id' => $personnel_id,
            'department_id' => $data['department_id'],
            'requested_by' => get_current_user_id(),
            'request_data' => json_encode($data),
            'status' => 'pending',
            'created_at' => current_time('mysql')
        );
        
        wf_create_approval_request($approval_data);
        
        $message = sprintf('پرسنل "%s %s" با موفقیت ایجاد شد (در انتظار تایید)', 
            $data['first_name'], $data['last_name']);
    } else {
        $message = sprintf('پرسنل "%s %s" با موفقیت ایجاد شد', 
            $data['first_name'], $data['last_name']);
    }
    
    // ثبت فعالیت
    wf_log_activity(get_current_user_id(), 'personnel', 'personnel_created', 
        $message, $personnel_id);
    
    return array(
        'success' => true,
        'personnel_id' => $personnel_id,
        'message' => $message,
        'requires_approval' => $require_approval
    );
}

/**
 * دریافت اطلاعات پرسنل بر اساس شناسه
 * 
 * @param int $personnel_id شناسه پرسنل
 * @return array|false داده‌های پرسنل یا false
 */
function wf_get_personnel($personnel_id) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_personnel';
    $departments_table = $wpdb->prefix . 'wf_departments';
    $periods_table = $wpdb->prefix . 'wf_periods';
    
    $personnel = $wpdb->get_row($wpdb->prepare(
        "SELECT p.*, 
                d.name as department_name,
                d.color as department_color,
                pr.title as period_title,
                uc.display_name as creator_name,
                uv.display_name as verifier_name
         FROM {$table} p
         LEFT JOIN {$departments_table} d ON p.department_id = d.id
         LEFT JOIN {$periods_table} pr ON p.period_id = pr.id
         LEFT JOIN {$wpdb->users} uc ON p.created_by = uc.ID
         LEFT JOIN {$wpdb->users} uv ON p.verified_by = uv.ID
         WHERE p.id = %d AND p.status != 'deleted'",
        $personnel_id
    ), ARRAY_A);
    
    if (!$personnel) {
        return false;
    }
    
    // دیکد کردن JSON فیلدها
    if (!empty($personnel['custom_fields'])) {
        $personnel['custom_fields'] = json_decode($personnel['custom_fields'], true);
    }
    
    if (!empty($personnel['attachments'])) {
        $personnel['attachments'] = json_decode($personnel['attachments'], true);
    }
    
    // اضافه کردن اطلاعات اضافی
    $personnel['age'] = wf_calculate_age($personnel['birth_date']);
    $personnel['employment_years'] = wf_calculate_employment_years($personnel['employment_date']);
    
    return $personnel;
}

/**
 * دریافت پرسنل بر اساس کدملی
 * 
 * @param string $national_id کدملی
 * @return array|false داده‌های پرسنل یا false
 */
function wf_get_personnel_by_national_id($national_id) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_personnel';
    
    $personnel = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE national_id = %s AND status != 'deleted'",
        $national_id
    ), ARRAY_A);
    
    if (!$personnel) {
        return false;
    }
    
    // دیکد کردن JSON فیلدها
    if (!empty($personnel['custom_fields'])) {
        $personnel['custom_fields'] = json_decode($personnel['custom_fields'], true);
    }
    
    return $personnel;
}

/**
 * دریافت همه پرسنل
 * 
 * @param array $params پارامترهای فیلتر
 * @return array لیست پرسنل
 */
function wf_get_all_personnel($params = array()) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_personnel';
    $departments_table = $wpdb->prefix . 'wf_departments';
    
    $defaults = array(
        'department_id' => 0,
        'status' => 'active',
        'period_id' => 0,
        'search' => '',
        'orderby' => 'created_at',
        'order' => 'DESC',
        'limit' => 0,
        'offset' => 0,
        'with_department' => true
    );
    
    $params = wp_parse_args($params, $defaults);
    
    $where = array("p.status != 'deleted'");
    $prepare_args = array();
    
    if (!empty($params['department_id'])) {
        $where[] = "p.department_id = %d";
        $prepare_args[] = $params['department_id'];
    }
    
    if (!empty($params['status']) && $params['status'] != 'all') {
        $where[] = "p.status = %s";
        $prepare_args[] = $params['status'];
    }
    
    if (!empty($params['period_id'])) {
        $where[] = "p.period_id = %d";
        $prepare_args[] = $params['period_id'];
    }
    
    if (!empty($params['search'])) {
        $where[] = "(p.first_name LIKE %s OR p.last_name LIKE %s OR p.national_id LIKE %s OR p.personnel_code LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($params['search']) . '%';
        $prepare_args[] = $search_term;
        $prepare_args[] = $search_term;
        $prepare_args[] = $search_term;
        $prepare_args[] = $search_term;
    }
    
    $where_sql = implode(' AND ', $where);
    
    // ساخت کوئری
    if ($params['with_department']) {
        $select = "p.*, d.name as department_name, d.color as department_color";
        $join = "LEFT JOIN {$departments_table} d ON p.department_id = d.id";
    } else {
        $select = "p.*";
        $join = "";
    }
    
    $order_sql = "ORDER BY p.{$params['orderby']} {$params['order']}";
    
    $limit_sql = '';
    if ($params['limit'] > 0) {
        $limit_sql = $wpdb->prepare("LIMIT %d OFFSET %d", 
            $params['limit'], $params['offset']);
    }
    
    $query = "SELECT {$select} FROM {$table} p {$join} WHERE {$where_sql} {$order_sql} {$limit_sql}";
    
    if (!empty($prepare_args)) {
        $query = $wpdb->prepare($query, $prepare_args);
    }
    
    $personnel = $wpdb->get_results($query, ARRAY_A);
    
    // دیکد کردن JSON فیلدها
    foreach ($personnel as &$person) {
        if (!empty($person['custom_fields'])) {
            $person['custom_fields'] = json_decode($person['custom_fields'], true);
        }
    }
    
    return $personnel;
}

/**
 * به‌روزرسانی اطلاعات پرسنل
 * 
 * @param int $personnel_id شناسه پرسنل
 * @param array $data داده‌های جدید
 * @param bool $require_approval نیاز به تایید دارد؟
 * @return array|WP_Error نتیجه عملیات
 */
function wf_update_personnel($personnel_id, $data, $require_approval = true) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_personnel';
    
    // بررسی وجود پرسنل
    $existing = wf_get_personnel($personnel_id);
    if (!$existing) {
        return new WP_Error('not_found', 'پرسنل مورد نظر یافت نشد');
    }
    
    // اعتبارسنجی
    $errors = wf_validate_personnel_data($data, $personnel_id);
    if (!empty($errors)) {
        return new WP_Error('validation_error', implode(', ', $errors));
    }
    
    // بررسی تکراری نبودن کدملی
    if (isset($data['national_id']) && $data['national_id'] !== $existing['national_id']) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE national_id = %s AND id != %d AND status != 'deleted'",
            $data['national_id'], $personnel_id
        ));
        
        if ($exists > 0) {
            return new WP_Error('duplicate_national_id', 'کدملی تکراری است');
        }
    }
    
    // بررسی تکراری نبودن کد پرسنلی
    if (isset($data['personnel_code']) && $data['personnel_code'] !== $existing['personnel_code']) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE personnel_code = %s AND id != %d AND status != 'deleted'",
            $data['personnel_code'], $personnel_id
        ));
        
        if ($exists > 0) {
            return new WP_Error('duplicate_personnel_code', 'کد پرسنلی تکراری است');
        }
    }
    
    // کدگذاری JSON برای فیلدهای سفارشی
    if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
        $data['custom_fields'] = json_encode($data['custom_fields']);
    }
    
    if (isset($data['attachments']) && is_array($data['attachments'])) {
        $data['attachments'] = json_encode($data['attachments']);
    }
    
    // محاسبه حقوق خالص
    if (isset($data['salary']) || isset($data['benefits']) || isset($data['deductions'])) {
        $salary = floatval($data['salary'] ?? $existing['salary']);
        $benefits = floatval($data['benefits'] ?? $existing['benefits']);
        $deductions = floatval($data['deductions'] ?? $existing['deductions']);
        $data['net_salary'] = $salary + $benefits - $deductions;
    }
    
    // افزودن زمان به‌روزرسانی
    $data['updated_at'] = current_time('mysql');
    
    // اگر نیاز به تایید دارد، ایجاد درخواست تایید
    if ($require_approval) {
        $approval_data = array(
            'request_type' => 'edit_personnel',
            'personnel_id' => $personnel_id,
            'department_id' => $existing['department_id'],
            'requested_by' => get_current_user_id(),
            'request_data' => json_encode(array_merge($existing, $data)),
            'status' => 'pending',
            'created_at' => current_time('mysql')
        );
        
        wf_create_approval_request($approval_data);
        
        $message = sprintf('تغییرات پرسنل "%s %s" در انتظار تایید است', 
            $existing['first_name'], $existing['last_name']);
        
        return array(
            'success' => true,
            'requires_approval' => true,
            'message' => $message
        );
    }
    
    // به‌روزرسانی در دیتابیس
    $result = $wpdb->update($table, $data, array('id' => $personnel_id));
    
    if ($result === false) {
        return new WP_Error('db_error', $wpdb->last_error);
    }
    
    // ثبت فعالیت
    wf_log_activity(get_current_user_id(), 'personnel', 'personnel_updated', 
        sprintf('پرسنل "%s %s" به‌روزرسانی شد', 
            $data['first_name'] ?? $existing['first_name'], 
            $data['last_name'] ?? $existing['last_name']), 
        $personnel_id);
    
    return array(
        'success' => true,
        'requires_approval' => false,
        'message' => 'تغییرات با موفقیت ذخیره شد'
    );
}

/**
 * حذف منطقی پرسنل
 * 
 * @param int $personnel_id شناسه پرسنل
 * @param bool $require_approval نیاز به تایید دارد؟
 * @return array|WP_Error نتیجه عملیات
 */
function wf_delete_personnel($personnel_id, $require_approval = true) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_personnel';
    
    // بررسی وجود پرسنل
    $personnel = wf_get_personnel($personnel_id);
    if (!$personnel) {
        return new WP_Error('not_found', 'پرسنل مورد نظر یافت نشد');
    }
    
    // اگر نیاز به تایید دارد، ایجاد درخواست تایید
    if ($require_approval) {
        $approval_data = array(
            'request_type' => 'delete_personnel',
            'personnel_id' => $personnel_id,
            'department_id' => $personnel['department_id'],
            'requested_by' => get_current_user_id(),
            'request_data' => json_encode($personnel),
            'status' => 'pending',
            'created_at' => current_time('mysql')
        );
        
        wf_create_approval_request($approval_data);
        
        return array(
            'success' => true,
            'requires_approval' => true,
            'message' => sprintf('درخواست حذف پرسنل "%s %s" در انتظار تایید است', 
                $personnel['first_name'], $personnel['last_name'])
        );
    }
    
    // حذف منطقی
    $result = $wpdb->update($table, 
        array('status' => 'deleted', 'updated_at' => current_time('mysql')),
        array('id' => $personnel_id)
    );
    
    if ($result === false) {
        return new WP_Error('db_error', $wpdb->last_error);
    }
    
    // ثبت فعالیت
    wf_log_activity(get_current_user_id(), 'personnel', 'personnel_deleted', 
        sprintf('پرسنل "%s %s" حذف شد', $personnel['first_name'], $personnel['last_name']), 
        $personnel_id);
    
    return array(
        'success' => true,
        'requires_approval' => false,
        'message' => 'پرسنل با موفقیت حذف شد'
    );
}

/**
 * اعتبارسنجی داده‌های پرسنل
 * 
 * @param array $data داده‌های پرسنل
 * @param int $personnel_id شناسه پرسنل (برای به‌روزرسانی)
 * @return array لیست خطاها
 */
function wf_validate_personnel_data($data, $personnel_id = 0) {
    $errors = array();
    
    // اعتبارسنجی فیلدهای الزامی
    $required_fields = wf_get_required_fields();
    
    foreach ($required_fields as $field) {
        if (empty($data[$field['name']])) {
            $errors[] = sprintf('فیلد "%s" الزامی است', $field['title']);
        }
    }
    
    // اعتبارسنجی کدملی
    if (!empty($data['national_id'])) {
        if (!preg_match('/^\d{10}$/', $data['national_id'])) {
            $errors[] = 'کدملی باید ۱۰ رقم باشد';
        } elseif (!wf_validate_national_id($data['national_id'])) {
            $errors[] = 'کدملی معتبر نیست';
        }
    }
    
    // اعتبارسنجی تاریخ‌ها
    if (!empty($data['birth_date'])) {
        if (!wf_validate_date($data['birth_date'])) {
            $errors[] = 'تاریخ تولد معتبر نیست';
        } elseif (strtotime($data['birth_date']) > strtotime('-18 years')) {
            $errors[] = 'سن باید حداقل ۱۸ سال باشد';
        }
    }
    
    if (!empty($data['employment_date'])) {
        if (!wf_validate_date($data['employment_date'])) {
            $errors[] = 'تاریخ استخدام معتبر نیست';
        }
    }
    
    // اعتبارسنجی ایمیل
    if (!empty($data['email']) && !is_email($data['email'])) {
        $errors[] = 'ایمیل معتبر نیست';
    }
    
    // اعتبارسنجی موبایل
    if (!empty($data['mobile']) && !preg_match('/^09[0-9]{9}$/', $data['mobile'])) {
        $errors[] = 'شماره موبایل معتبر نیست';
    }
    
    // اعتبارسنجی اعداد
    if (isset($data['salary']) && !is_numeric($data['salary'])) {
        $errors[] = 'حقوق باید عددی باشد';
    }
    
    return $errors;
}

/**
 * ============================================
 * توابع CRUD برای دوره‌های کاری
 * ============================================
 */

/**
 * ایجاد دوره جدید
 * 
 * @param array $data داده‌های دوره
 * @return int|WP_Error شناسه دوره یا خطا
 */
function wf_create_period($data) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_periods';
    
    // اعتبارسنجی
    if (empty($data['title'])) {
        return new WP_Error('validation_error', 'عنوان دوره الزامی است');
    }
    
    if (empty($data['year']) || empty($data['month'])) {
        return new WP_Error('validation_error', 'سال و ماه دوره الزامی است');
    }
    
    // بررسی تکراری نبودن دوره
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE year = %d AND month = %d AND status != 'archived'",
        $data['year'], $data['month']
    ));
    
    if ($exists > 0) {
        return new WP_Error('duplicate_period', 'دوره برای این ماه و سال قبلاً ثبت شده است');
    }
    
    // تنظیم مقادیر پیش‌فرض
    $defaults = array(
        'start_date' => date('Y-m-01'),
        'end_date' => date('Y-m-t'),
        'is_current' => 0,
        'status' => 'active',
        'created_by' => get_current_user_id(),
        'created_at' => current_time('mysql')
    );
    
    $data = wp_parse_args($data, $defaults);
    
    // کدگذاری JSON برای تنظیمات
    if (isset($data['settings']) && is_array($data['settings'])) {
        $data['settings'] = json_encode($data['settings']);
    }
    
    // اگر این دوره را به عنوان دوره جاری علامت زده‌اند، بقیه دوره‌ها را غیرجاری کنیم
    if ($data['is_current'] == 1) {
        $wpdb->update($table, 
            array('is_current' => 0),
            array('is_current' => 1)
        );
    }
    
    // درج در دیتابیس
    $result = $wpdb->insert($table, $data);
    
    if ($result === false) {
        return new WP_Error('db_error', $wpdb->last_error);
    }
    
    $period_id = $wpdb->insert_id;
    
    // ثبت فعالیت
    wf_log_activity(get_current_user_id(), 'period', 'period_created', 
        sprintf('دوره "%s" ایجاد شد', $data['title']), $period_id);
    
    return $period_id;
}

/**
 * دریافت دوره جاری
 * 
 * @return array|false داده‌های دوره یا false
 */
function wf_get_current_period() {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_periods';
    
    $period = $wpdb->get_row(
        "SELECT * FROM {$table} WHERE is_current = 1 AND status = 'active' LIMIT 1",
        ARRAY_A
    );
    
    if (!$period) {
        // اگر دوره جاری نداریم، آخرین دوره فعال را برمی‌گردانیم
        $period = $wpdb->get_row(
            "SELECT * FROM {$table} WHERE status = 'active' ORDER BY year DESC, month DESC LIMIT 1",
            ARRAY_A
        );
    }
    
    if (!$period) {
        return false;
    }
    
    // دیکد کردن JSON تنظیمات
    if (!empty($period['settings'])) {
        $period['settings'] = json_decode($period['settings'], true);
    }
    
    return $period;
}

/**
 * دریافت شناسه دوره جاری
 * 
 * @return int شناسه دوره
 */
function wf_get_current_period_id() {
    $period = wf_get_current_period();
    return $period ? $period['id'] : 0;
}

/**
 * دریافت همه دوره‌ها
 * 
 * @param array $params پارامترهای فیلتر
 * @return array لیست دوره‌ها
 */
function wf_get_periods($params = array()) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_periods';
    
    $defaults = array(
        'status' => 'active',
        'year' => 0,
        'orderby' => 'year DESC, month DESC',
        'order' => 'DESC',
        'limit' => 0,
        'offset' => 0
    );
    
    $params = wp_parse_args($params, $defaults);
    
    $where = array("status != 'archived'");
    $prepare_args = array();
    
    if (!empty($params['status']) && $params['status'] != 'all') {
        $where[] = "status = %s";
        $prepare_args[] = $params['status'];
    }
    
    if (!empty($params['year'])) {
        $where[] = "year = %d";
        $prepare_args[] = $params['year'];
    }
    
    $where_sql = implode(' AND ', $where);
    $order_sql = "ORDER BY {$params['orderby']} {$params['order']}";
    
    $limit_sql = '';
    if ($params['limit'] > 0) {
        $limit_sql = $wpdb->prepare("LIMIT %d OFFSET %d", 
            $params['limit'], $params['offset']);
    }
    
    $query = "SELECT * FROM {$table} WHERE {$where_sql} {$order_sql} {$limit_sql}";
    
    if (!empty($prepare_args)) {
        $query = $wpdb->prepare($query, $prepare_args);
    }
    
    $periods = $wpdb->get_results($query, ARRAY_A);
    
    // دیکد کردن JSON تنظیمات و اضافه کردن اطلاعات اضافی
    foreach ($periods as &$period) {
        if (!empty($period['settings'])) {
            $period['settings'] = json_decode($period['settings'], true);
        }
        
        // تعداد پرسنل در این دوره
        $period['personnel_count'] = wf_get_period_personnel_count($period['id']);
        
        // نام ماه شمسی
        $period['month_name'] = wf_get_persian_month_name($period['month']);
    }
    
    return $periods;
}

/**
 * بستن دوره
 * 
 * @param int $period_id شناسه دوره
 * @return bool|WP_Error موفقیت یا خطا
 */
function wf_close_period($period_id) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_periods';
    
    // بررسی وجود دوره
    $period = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d",
        $period_id
    ), ARRAY_A);
    
    if (!$period) {
        return new WP_Error('not_found', 'دوره مورد نظر یافت نشد');
    }
    
    // بررسی اینکه دوره قبلاً بسته نشده باشد
    if ($period['status'] == 'closed') {
        return new WP_Error('already_closed', 'این دوره قبلاً بسته شده است');
    }
    
    // بستن دوره
    $result = $wpdb->update($table, 
        array(
            'status' => 'closed',
            'closed_by' => get_current_user_id(),
            'closed_at' => current_time('mysql'),
            'is_current' => 0,
            'updated_at' => current_time('mysql')
        ),
        array('id' => $period_id)
    );
    
    if ($result === false) {
        return new WP_Error('db_error', $wpdb->last_error);
    }
    
    // ثبت فعالیت
    wf_log_activity(get_current_user_id(), 'period', 'period_closed', 
        sprintf('دوره "%s" بسته شد', $period['title']), $period_id);
    
    return true;
}

/**
 * ============================================
 * توابع CRUD برای درخواست‌های تایید
 * ============================================
 */

/**
 * ایجاد درخواست تایید
 * 
 * @param array $data داده‌های درخواست
 * @return int|WP_Error شناسه درخواست یا خطا
 */
function wf_create_approval_request($data) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_approvals';
    
    // اعتبارسنجی
    if (empty($data['request_type'])) {
        return new WP_Error('validation_error', 'نوع درخواست الزامی است');
    }
    
    if (empty($data['requested_by'])) {
        return new WP_Error('validation_error', 'درخواست‌دهنده الزامی است');
    }
    
    // تنظیم مقادیر پیش‌فرض
    $defaults = array(
        'status' => 'pending',
        'priority' => 'normal',
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    );
    
    $data = wp_parse_args($data, $defaults);
    
    // کدگذاری JSON برای داده‌های درخواست
    if (isset($data['request_data']) && is_array($data['request_data'])) {
        $data['request_data'] = json_encode($data['request_data']);
    }
    
    if (isset($data['attachments']) && is_array($data['attachments'])) {
        $data['attachments'] = json_encode($data['attachments']);
    }
    
    // درج در دیتابیس
    $result = $wpdb->insert($table, $data);
    
    if ($result === false) {
        return new WP_Error('db_error', $wpdb->last_error);
    }
    
    $approval_id = $wpdb->insert_id;
    
    // ثبت فعالیت
    $request_types = array(
        'add_personnel' => 'درخواست افزودن پرسنل',
        'edit_personnel' => 'درخواست ویرایش پرسنل',
        'delete_personnel' => 'درخواست حذف پرسنل',
        'edit_field' => 'درخواست ویرایش فیلد',
        'other' => 'درخواست دیگر'
    );
    
    $type_name = $request_types[$data['request_type']] ?? 'درخواست ناشناخته';
    
    wf_log_activity($data['requested_by'], 'approval', 'approval_created', 
        sprintf('%s ایجاد شد', $type_name), $approval_id);
    
    return $approval_id;
}

/**
 * دریافت درخواست‌های تایید
 * 
 * @param array $params پارامترهای فیلتر
 * @return array لیست درخواست‌ها
 */
function wf_get_approval_requests($params = array()) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_approvals';
    $users_table = $wpdb->users;
    $departments_table = $wpdb->prefix . 'wf_departments';
    $personnel_table = $wpdb->prefix . 'wf_personnel';
    
    $defaults = array(
        'status' => 'pending',
        'request_type' => '',
        'department_id' => 0,
        'requested_by' => 0,
        'priority' => '',
        'orderby' => 'created_at',
        'order' => 'DESC',
        'limit' => 0,
        'offset' => 0
    );
    
    $params = wp_parse_args($params, $defaults);
    
    $where = array("1=1");
    $prepare_args = array();
    
    if (!empty($params['status']) && $params['status'] != 'all') {
        $where[] = "a.status = %s";
        $prepare_args[] = $params['status'];
    }
    
    if (!empty($params['request_type'])) {
        $where[] = "a.request_type = %s";
        $prepare_args[] = $params['request_type'];
    }
    
    if (!empty($params['department_id'])) {
        $where[] = "a.department_id = %d";
        $prepare_args[] = $params['department_id'];
    }
    
    if (!empty($params['requested_by'])) {
        $where[] = "a.requested_by = %d";
        $prepare_args[] = $params['requested_by'];
    }
    
    if (!empty($params['priority'])) {
        $where[] = "a.priority = %s";
        $prepare_args[] = $params['priority'];
    }
    
    $where_sql = implode(' AND ', $where);
    
    $order_sql = "ORDER BY a.{$params['orderby']} {$params['order']}";
    
    $limit_sql = '';
    if ($params['limit'] > 0) {
        $limit_sql = $wpdb->prepare("LIMIT %d OFFSET %d", 
            $params['limit'], $params['offset']);
    }
    
    $query = "SELECT a.*, 
                     ru.display_name as requester_name,
                     ru.user_email as requester_email,
                     eu.display_name as reviewer_name,
                     d.name as department_name,
                     p.first_name as personnel_first_name,
                     p.last_name as personnel_last_name
              FROM {$table} a
              LEFT JOIN {$users_table} ru ON a.requested_by = ru.ID
              LEFT JOIN {$users_table} eu ON a.reviewed_by = eu.ID
              LEFT JOIN {$departments_table} d ON a.department_id = d.id
              LEFT JOIN {$personnel_table} p ON a.personnel_id = p.id
              WHERE {$where_sql} {$order_sql} {$limit_sql}";
    
    if (!empty($prepare_args)) {
        $query = $wpdb->prepare($query, $prepare_args);
    }
    
    $requests = $wpdb->get_results($query, ARRAY_A);
    
    // دیکد کردن JSON فیلدها
    foreach ($requests as &$request) {
        if (!empty($request['request_data'])) {
            $request['request_data'] = json_decode($request['request_data'], true);
        }
        
        if (!empty($request['attachments'])) {
            $request['attachments'] = json_decode($request['attachments'], true);
        }
        
        if (!empty($request['action_taken'])) {
            $request['action_taken'] = json_decode($request['action_taken'], true);
        }
    }
    
    return $requests;
}

/**
 * تایید درخواست
 * 
 * @param int $approval_id شناسه درخواست
 * @param string $notes یادداشت تایید
 * @return bool|WP_Error موفقیت یا خطا
 */
function wf_approve_request($approval_id, $notes = '') {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_approvals';
    
    // دریافت درخواست
    $request = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d",
        $approval_id
    ), ARRAY_A);
    
    if (!$request) {
        return new WP_Error('not_found', 'درخواست مورد نظر یافت نشد');
    }
    
    if ($request['status'] != 'pending') {
        return new WP_Error('not_pending', 'این درخواست قبلاً پردازش شده است');
    }
    
    // پردازش درخواست بر اساس نوع
    $result = wf_process_approval_request($request);
    
    if (is_wp_error($result)) {
        return $result;
    }
    
    // به‌روزرسانی وضعیت درخواست
    $update_data = array(
        'status' => 'approved',
        'reviewed_by' => get_current_user_id(),
        'review_notes' => $notes,
        'reviewed_at' => current_time('mysql'),
        'action_taken' => json_encode($result),
        'updated_at' => current_time('mysql')
    );
    
    $wpdb->update($table, $update_data, array('id' => $approval_id));
    
    // ثبت فعالیت
    wf_log_activity(get_current_user_id(), 'approval', 'approval_approved', 
        sprintf('درخواست #%d تایید شد', $approval_id), $approval_id);
    
    return true;
}

/**
 * رد درخواست
 * 
 * @param int $approval_id شناسه درخواست
 * @param string $notes یادداشت رد
 * @return bool|WP_Error موفقیت یا خطا
 */
function wf_reject_request($approval_id, $notes = '') {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_approvals';
    
    // دریافت درخواست
    $request = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d",
        $approval_id
    ), ARRAY_A);
    
    if (!$request) {
        return new WP_Error('not_found', 'درخواست مورد نظر یافت نشد');
    }
    
    if ($request['status'] != 'pending') {
        return new WP_Error('not_pending', 'این درخواست قبلاً پردازش شده است');
    }
    
    // به‌روزرسانی وضعیت درخواست
    $update_data = array(
        'status' => 'rejected',
        'reviewed_by' => get_current_user_id(),
        'review_notes' => $notes,
        'reviewed_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    );
    
    $wpdb->update($table, $update_data, array('id' => $approval_id));
    
    // ثبت فعالیت
    wf_log_activity(get_current_user_id(), 'approval', 'approval_rejected', 
        sprintf('درخواست #%d رد شد', $approval_id), $approval_id);
    
    return true;
}

/**
 * پردازش درخواست تایید
 * 
 * @param array $request داده‌های درخواست
 * @return array|WP_Error نتیجه پردازش
 */
function wf_process_approval_request($request) {
    $request_data = json_decode($request['request_data'], true);
    $result = array();
    
    switch ($request['request_type']) {
        case 'add_personnel':
            // تغییر وضعیت پرسنل به active
            global $wpdb;
            $table = $wpdb->prefix . 'wf_personnel';
            
            $wpdb->update($table, 
                array('status' => 'active', 'updated_at' => current_time('mysql')),
                array('id' => $request['personnel_id'])
            );
            
            $result = array('action' => 'personnel_activated', 'personnel_id' => $request['personnel_id']);
            break;
            
        case 'edit_personnel':
            // اعمال تغییرات
            if (isset($request_data['custom_fields'])) {
                $custom_fields = json_decode($request_data['custom_fields'], true);
                $result = wf_update_personnel($request['personnel_id'], $custom_fields, false);
            }
            break;
            
        case 'delete_personnel':
            // حذف پرسنل
            $result = wf_delete_personnel($request['personnel_id'], false);
            break;
            
        default:
            $result = array('action' => 'no_action', 'message' => 'نوع درخواست شناخته شده نیست');
    }
    
    return $result;
}

/**
 * ============================================
 * توابع کمکی و ابزاری
 * ============================================
 */

/**
 * ثبت فعالیت در سیستم
 * 
 * @param int $user_id شناسه کاربر
 * @param string $module ماژول
 * @param string $action عمل انجام شده
 * @param string $description توضیح فعالیت
 * @param int $record_id شناسه رکورد مرتبط
 * @return bool موفقیت
 */
function wf_log_activity($user_id, $module, $action, $description, $record_id = 0) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_activities';
    
    // بررسی فعال بودن ثبت لاگ
    $enable_log = wf_get_setting('enable_audit_log', '1');
    if ($enable_log != '1') {
        return true;
    }
    
    $data = array(
        'user_id' => $user_id,
        'activity_type' => $action,
        'activity_module' => $module,
        'record_id' => $record_id,
        'description' => $description,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'created_at' => current_time('mysql')
    );
    
    return $wpdb->insert($table, $data) !== false;
}

/**
 * دریافت تنظیمات سیستم
 * 
 * @param string $key کلید تنظیم
 * @param mixed $default مقدار پیش‌فرض
 * @return mixed مقدار تنظیم
 */
function wf_get_setting($key, $default = '') {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_settings';
    
    $value = $wpdb->get_var($wpdb->prepare(
        "SELECT setting_value FROM {$table} WHERE setting_key = %s",
        $key
    ));
    
    if ($value === null) {
        return $default;
    }
    
    // تشخیص نوع داده
    $data_type = $wpdb->get_var($wpdb->prepare(
        "SELECT data_type FROM {$table} WHERE setting_key = %s",
        $key
    ));
    
    switch ($data_type) {
        case 'number':
            return floatval($value);
        case 'boolean':
            return $value === '1' || $value === 'true';
        case 'array':
        case 'object':
            return json_decode($value, true);
        default:
            return $value;
    }
}

/**
 * ذخیره تنظیمات سیستم
 * 
 * @param string $key کلید تنظیم
 * @param mixed $value مقدار تنظیم
 * @param string $group گروه تنظیم
 * @param string $data_type نوع داده
 * @return bool موفقیت
 */
function wf_save_setting($key, $value, $group = 'general', $data_type = 'string') {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_settings';
    
    // تبدیل مقدار به رشته بر اساس نوع داده
    switch ($data_type) {
        case 'array':
        case 'object':
            $value = json_encode($value);
            break;
        case 'boolean':
            $value = $value ? '1' : '0';
            break;
        case 'number':
            $value = strval($value);
            break;
    }
    
    // بررسی وجود تنظیم
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE setting_key = %s",
        $key
    ));
    
    if ($exists > 0) {
        // به‌روزرسانی
        return $wpdb->update($table, 
            array(
                'setting_value' => $value,
                'data_type' => $data_type,
                'updated_at' => current_time('mysql')
            ),
            array('setting_key' => $key)
        ) !== false;
    } else {
        // ایجاد جدید
        return $wpdb->insert($table, 
            array(
                'setting_key' => $key,
                'setting_value' => $value,
                'setting_group' => $group,
                'data_type' => $data_type,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            )
        ) !== false;
    }
}

/**
 * دریافت آمار کلی سیستم
 * 
 * @return array آمار سیستم
 */
function wf_get_system_stats() {
    global $wpdb;
    
    $stats = array();
    
    // تعداد ادارات
    $stats['total_departments'] = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wf_departments WHERE status = 'active'"
    );
    
    // تعداد پرسنل
    $stats['total_personnel'] = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wf_personnel WHERE status = 'active'"
    );
    
    // تعداد فیلدها
    $stats['total_fields'] = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wf_fields WHERE status = 'active'"
    );
    
    // تعداد مدیران
    $stats['total_managers'] = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wf_system_users WHERE is_active = 1"
    );
    
    // تعداد درخواست‌های در انتظار
    $stats['pending_approvals'] = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wf_approvals WHERE status = 'pending'"
    );
    
    // آخرین فعالیت‌ها
    $stats['recent_activities'] = $wpdb->get_results(
        "SELECT a.*, u.display_name 
         FROM {$wpdb->prefix}wf_activities a
         LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
         ORDER BY created_at DESC LIMIT 10",
        ARRAY_A
    );
    
    return $stats;
}

/**
 * بهینه‌سازی جداول دیتابیس
 * 
 * @return array نتایج بهینه‌سازی
 */
function wf_optimize_tables() {
    global $wpdb;
    
    $tables = array(
        'wf_fields',
        'wf_departments',
        'wf_personnel',
        'wf_periods',
        'wf_approvals',
        'wf_activities',
        'wf_settings',
        'wf_reports',
        'wf_excel_templates',
        'wf_system_users'
    );
    
    $results = array();
    
    foreach ($tables as $table) {
        $full_table = $wpdb->prefix . $table;
        $wpdb->query("OPTIMIZE TABLE {$full_table}");
        $results[$table] = 'بهینه‌سازی شد';
    }
    
    wf_log_activity(get_current_user_id(), 'system', 'tables_optimized', 
        'جداول دیتابیس بهینه‌سازی شدند');
    
    return $results;
}

/**
 * پشتیبان‌گیری از دیتابیس
 * 
 * @param string $backup_type نوع پشتیبان
 * @return string|WP_Error مسیر فایل پشتیبان یا خطا
 */
function wf_backup_database($backup_type = 'full') {
    // این تابع نیاز به پیاده‌سازی پیشرفته‌تری دارد
    // فعلاً به صورت ساده پیاده‌سازی می‌شود
    
    $backup_dir = WP_CONTENT_DIR . '/uploads/wf-backups/';
    
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $backup_file = $backup_dir . 'backup-' . date('Y-m-d-H-i-s') . '.sql';
    
    // در یک سیستم واقعی، اینجا از mysqldump استفاده می‌شود
    // فعلاً یک فایل نمونه ایجاد می‌کنیم
    file_put_contents($backup_file, "-- Backup created at " . date('Y-m-d H:i:s'));
    
    wf_log_activity(get_current_user_id(), 'system', 'backup_created', 
        sprintf('پشتیبان %s ایجاد شد', $backup_type));
    
    return $backup_file;
}

/**
 * اعتبارسنجی کدملی ایرانی
 * 
 * @param string $national_id کدملی
 * @return bool معتبر بودن
 */
function wf_validate_national_id($national_id) {
    if (!preg_match('/^\d{10}$/', $national_id)) {
        return false;
    }
    
    // الگوریتم اعتبارسنجی کدملی
    $check = (int) substr($national_id, 9, 1);
    $sum = 0;
    
    for ($i = 0; $i < 9; $i++) {
        $sum += (int) substr($national_id, $i, 1) * (10 - $i);
    }
    
    $remainder = $sum % 11;
    
    if (($remainder < 2 && $check == $remainder) || ($remainder >= 2 && $check == (11 - $remainder))) {
        return true;
    }
    
    return false;
}

/**
 * محاسبه سن
 * 
 * @param string $birth_date تاریخ تولد
 * @return int سن
 */
function wf_calculate_age($birth_date) {
    if (empty($birth_date)) {
        return 0;
    }
    
    $birth = new DateTime($birth_date);
    $today = new DateTime();
    $age = $today->diff($birth);
    
    return $age->y;
}

/**
 * محاسبه سابقه کار
 * 
 * @param string $employment_date تاریخ استخدام
 * @return string سابقه کار
 */
function wf_calculate_employment_years($employment_date) {
    if (empty($employment_date)) {
        return '0 سال';
    }
    
    $start = new DateTime($employment_date);
    $today = new DateTime();
    $interval = $today->diff($start);
    
    $years = $interval->y;
    $months = $interval->m;
    
    $result = '';
    
    if ($years > 0) {
        $result .= $years . ' سال';
    }
    
    if ($months > 0) {
        if ($years > 0) {
            $result .= ' و ';
        }
        $result .= $months . ' ماه';
    }
    
    if (empty($result)) {
        $result = 'کمتر از ۱ ماه';
    }
    
    return $result;
}

/**
 * دریافت فیلدهای الزامی
 * 
 * @return array لیست فیلدهای الزامی
 */
function wf_get_required_fields() {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_fields';
    
    return $wpdb->get_results(
        "SELECT name, title FROM {$table} WHERE is_required = 1 AND status = 'active'",
        ARRAY_A
    );
}

/**
 * اعتبارسنجی تاریخ
 * 
 * @param string $date تاریخ
 * @return bool معتبر بودن
 */
function wf_validate_date($date) {
    if (empty($date)) {
        return false;
    }
    
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * دریافت نام ماه شمسی
 * 
 * @param int $month_number شماره ماه
 * @return string نام ماه
 */
function wf_get_persian_month_name($month_number) {
    $months = array(
        1 => 'فروردین',
        2 => 'اردیبهشت',
        3 => 'خرداد',
        4 => 'تیر',
        5 => 'مرداد',
        6 => 'شهریور',
        7 => 'مهر',
        8 => 'آبان',
        9 => 'آذر',
        10 => 'دی',
        11 => 'بهمن',
        12 => 'اسفند'
    );
    
    return $months[$month_number] ?? 'نامشخص';
}

/**
 * ============================================
 * توابع برای گزارش‌گیری
 * ============================================
 */

/**
 * دریافت آمار ادارات
 * 
 * @return array آمار ادارات
 */
function wf_get_departments_stats() {
    global $wpdb;
    
    $departments_table = $wpdb->prefix . 'wf_departments';
    $personnel_table = $wpdb->prefix . 'wf_personnel';
    
    $query = "
        SELECT d.id, d.name, d.color,
               COUNT(p.id) as personnel_count,
               AVG(p.salary) as avg_salary,
               SUM(p.salary) as total_salary
        FROM {$departments_table} d
        LEFT JOIN {$personnel_table} p ON d.id = p.department_id AND p.status = 'active'
        WHERE d.status = 'active'
        GROUP BY d.id
        ORDER BY d.sort_order ASC
    ";
    
    return $wpdb->get_results($query, ARRAY_A);
}

/**
 * دریافت روند ماهانه استخدام
 * 
 * @param int $year سال
 * @return array روند استخدام
 */
function wf_get_monthly_employment_trend($year = null) {
    global $wpdb;
    
    if (!$year) {
        $year = date('Y');
    }
    
    $table = $wpdb->prefix . 'wf_personnel';
    
    $query = $wpdb->prepare("
        SELECT MONTH(employment_date) as month,
               COUNT(*) as count,
               AVG(salary) as avg_salary
        FROM {$table}
        WHERE YEAR(employment_date) = %d 
          AND status = 'active'
        GROUP BY MONTH(employment_date)
        ORDER BY month ASC
    ", $year);
    
    return $wpdb->get_results($query, ARRAY_A);
}

/**
 * دریافت پرسنل با اطلاعات ناقص
 * 
 * @param int $department_id شناسه اداره (اختیاری)
 * @return array لیست پرسنل
 */
function wf_get_incomplete_personnel($department_id = 0) {
    global $wpdb;
    
    $personnel_table = $wpdb->prefix . 'wf_personnel';
    $fields_table = $wpdb->prefix . 'wf_fields';
    $departments_table = $wpdb->prefix . 'wf_departments';
    
    // دریافت فیلدهای الزامی
    $required_fields = wf_get_required_fields();
    
    if (empty($required_fields)) {
        return array();
    }
    
    $where_conditions = array();
    foreach ($required_fields as $field) {
        $where_conditions[] = "(p.{$field['name']} IS NULL OR p.{$field['name']} = '')";
    }
    
    $where_sql = implode(' OR ', $where_conditions);
    
    $query = "
        SELECT p.*, d.name as department_name
        FROM {$personnel_table} p
        LEFT JOIN {$departments_table} d ON p.department_id = d.id
        WHERE p.status = 'active' 
          AND ({$where_sql})
    ";
    
    if ($department_id > 0) {
        $query .= $wpdb->prepare(" AND p.department_id = %d", $department_id);
    }
    
    $query .= " ORDER BY p.created_at DESC LIMIT 100";
    
    return $wpdb->get_results($query, ARRAY_A);
}

/**
 * دریافت تعداد پرسنل در یک دوره
 * 
 * @param int $period_id شناسه دوره
 * @return int تعداد پرسنل
 */
function wf_get_period_personnel_count($period_id) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_personnel';
    
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE period_id = %d AND status IN ('active', 'pending')",
        $period_id
    ));
}

/**
 * ============================================
 * هوک‌های وردپرس
 * ============================================
 */

// ثبت هوک ایجاد جداول هنگام فعال‌سازی پلاگین
register_activation_hook(WF_PLUGIN_FILE, 'wf_create_tables');

// هوک حذف جداول هنگام غیرفعال‌سازی
register_uninstall_hook(WF_PLUGIN_FILE, 'wf_drop_tables');

/**
 * حذف جداول دیتابیس هنگام حذف پلاگین
 */
function wf_drop_tables() {
    global $wpdb;
    
    $tables = array(
        'wf_fields',
        'wf_departments',
        'wf_personnel',
        'wf_periods',
        'wf_approvals',
        'wf_activities',
        'wf_settings',
        'wf_reports',
        'wf_excel_templates',
        'wf_system_users'
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
    }
    
    // حذف فایل‌های پشتیبان قدیمی
    $backup_dir = WP_CONTENT_DIR . '/uploads/wf-backups/';
    if (file_exists($backup_dir)) {
        array_map('unlink', glob("{$backup_dir}/*.*"));
        rmdir($backup_dir);
    }
}

/**
 * ============================================
 * توابع برای دیباگ و توسعه
 * ============================================
 */

/**
 * بررسی سلامت دیتابیس
 * 
 * @return array وضعیت سلامت
 */
function wf_check_database_health() {
    global $wpdb;
    
    $health = array();
    
    // بررسی وجود جداول
    $tables = array(
        'wf_fields' => 'جدول فیلدها',
        'wf_departments' => 'جدول ادارات',
        'wf_personnel' => 'جدول پرسنل',
        'wf_periods' => 'جدول دوره‌ها',
        'wf_approvals' => 'جدول درخواست‌ها',
        'wf_activities' => 'جدول فعالیت‌ها',
        'wf_settings' => 'جدول تنظیمات'
    );
    
    foreach ($tables as $table => $name) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$table}'");
        $health[$table] = array(
            'name' => $name,
            'exists' => !empty($exists),
            'count' => $exists ? $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}{$table}") : 0
        );
    }
    
    // بررسی داده‌های اولیه
    $health['initial_data'] = array(
        'fields' => count(wf_get_fields()) > 0,
        'periods' => wf_get_current_period() !== false,
        'settings' => wf_get_setting('system_name') !== ''
    );
    
    return $health;
}

/**
 * بازنشانی داده‌های تست (فقط در حالت توسعه)
 * 
 * @return array نتیجه بازنشانی
 */
function wf_reset_test_data() {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return new WP_Error('not_allowed', 'این عمل فقط در حالت توسعه مجاز است');
    }
    
    global $wpdb;
    
    $tables = array(
        'wf_personnel',
        'wf_approvals',
        'wf_activities'
    );
    
    $results = array();
    
    foreach ($tables as $table) {
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}{$table}");
        $results[$table] = 'پاک شد';
    }
    
    // ایجاد داده‌های تست
    wf_create_test_data();
    
    return $results;
}

/**
 * ایجاد داده‌های تست
 */
function wf_create_test_data() {
    // این تابع فقط برای محیط توسعه استفاده می‌شود
    // داده‌های نمونه برای تست سیستم ایجاد می‌کند
}

// پایان فایل
