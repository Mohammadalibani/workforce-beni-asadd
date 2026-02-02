<?php
/**
 * هندلر پایگاه داده - سیستم مدیریت کارکرد پرسنل
 * مدیریت تمام جداول و عملیات دیتابیس
 * نسخه: 1.0.0
 * تاریخ: بهمن ۱۴۰۳
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// ==================== ایجاد جداول دیتابیس ====================

/**
 * ایجاد جداول هنگام فعال‌سازی پلاگین
 */
function wf_create_database_tables() {
    global $wpdb;
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // ==================== مرحله ۱: ایجاد جدول تنظیمات ====================
    
    $sql1 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wf_settings (
        id INT(11) NOT NULL AUTO_INCREMENT,
        setting_key VARCHAR(100) NOT NULL,
        setting_value LONGTEXT,
        setting_type ENUM('string', 'number', 'boolean', 'json', 'array') DEFAULT 'string',
        category VARCHAR(50),
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_setting_key (setting_key),
        INDEX idx_category (category)
    ) $charset_collate;";
    
    dbDelta($sql1);
    
    // ==================== مرحله ۲: ایجاد جداول اصلی ====================
    
    // ۱. جدول فیلدها
    $sql2 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wf_fields (
        id INT(11) NOT NULL AUTO_INCREMENT,
        field_key VARCHAR(100) NOT NULL,
        field_name VARCHAR(255) NOT NULL,
        field_type ENUM('text', 'number', 'decimal', 'date', 'datetime', 'checkbox', 'select', 'textarea', 'file', 'time') NOT NULL DEFAULT 'text',
        field_options LONGTEXT,
        validation_rules LONGTEXT,
        is_required TINYINT(1) DEFAULT 0,
        is_locked TINYINT(1) DEFAULT 0,
        is_monitoring TINYINT(1) DEFAULT 0,
        is_key TINYINT(1) DEFAULT 0,
        display_order INT(5) DEFAULT 0,
        description TEXT,
        status ENUM('active', 'inactive', 'deleted') DEFAULT 'active',
        created_by INT(11),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_field_key (field_key),
        INDEX idx_field_type (field_type),
        INDEX idx_status (status),
        INDEX idx_is_required (is_required),
        INDEX idx_is_locked (is_locked),
        INDEX idx_is_monitoring (is_monitoring),
        INDEX idx_display_order (display_order)
    ) $charset_collate;";
    
    dbDelta($sql2);
    
    // ۲. جدول ادارات
    $sql3 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wf_departments (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        code VARCHAR(50),
        description TEXT,
        manager_id INT(11),
        color VARCHAR(7) DEFAULT '#2E86C1',
        parent_id INT(11),
        display_order INT(5) DEFAULT 0,
        settings LONGTEXT,
        is_active TINYINT(1) DEFAULT 1,
        created_by INT(11),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_department_code (code),
        INDEX idx_manager (manager_id),
        INDEX idx_parent (parent_id),
        INDEX idx_is_active (is_active),
        INDEX idx_display_order (display_order)
    ) $charset_collate;";
    
    dbDelta($sql3);
    
    // ۳. جدول دوره‌ها
    $sql4 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wf_periods (
        id INT(11) NOT NULL AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        period_code VARCHAR(100),
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        description TEXT,
        settings LONGTEXT,
        is_active TINYINT(1) DEFAULT 0,
        status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
        created_by INT(11),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_period_code (period_code),
        INDEX idx_status (status),
        INDEX idx_is_active (is_active),
        INDEX idx_dates (start_date, end_date)
    ) $charset_collate;";
    
    dbDelta($sql4);
    
    // ==================== مرحله ۳: ایجاد جداول وابسته ====================
    
    // ۴. جدول پرسنل
    $sql5 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wf_personnel (
        id INT(11) NOT NULL AUTO_INCREMENT,
        national_code VARCHAR(10) NOT NULL,
        department_id INT(11) NOT NULL,
        employment_date DATE,
        employment_type ENUM('permanent', 'contractual', 'temporary', 'project') DEFAULT 'permanent',
        position VARCHAR(255),
        level VARCHAR(100),
        status ENUM('active', 'inactive', 'suspended', 'retired', 'resigned') DEFAULT 'active',
        profile_image VARCHAR(500),
        data LONGTEXT,
        required_fields_completed INT(5) DEFAULT 0,
        total_fields INT(5) DEFAULT 0,
        completion_percentage DECIMAL(5,2) DEFAULT 0.00,
        has_warnings TINYINT(1) DEFAULT 0,
        warnings TEXT,
        last_modified_by INT(11),
        is_deleted TINYINT(1) DEFAULT 0,
        deleted_at DATETIME,
        deleted_by INT(11),
        created_by INT(11),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_national_code (national_code),
        INDEX idx_department (department_id),
        INDEX idx_status (status),
        INDEX idx_employment_type (employment_type),
        INDEX idx_employment_date (employment_date),
        INDEX idx_completion (completion_percentage),
        INDEX idx_has_warnings (has_warnings),
        INDEX idx_is_deleted (is_deleted),
        INDEX idx_created_at (created_at),
        INDEX idx_created_by (created_by),
        INDEX idx_last_modified_by (last_modified_by)
    ) $charset_collate;";
    
    dbDelta($sql5);
    
    // ۵. جدول تأییدیه‌ها
    $sql6 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wf_approvals (
        id INT(11) NOT NULL AUTO_INCREMENT,
        request_type ENUM('add_personnel', 'edit_personnel', 'delete_personnel', 'edit_field', 'add_department', 'other') NOT NULL,
        request_data LONGTEXT NOT NULL,
        requester_id INT(11) NOT NULL,
        department_id INT(11),
        personnel_id INT(11),
        field_id INT(11),
        priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
        status ENUM('pending', 'approved', 'rejected', 'needs_revision', 'suspended') DEFAULT 'pending',
        admin_notes TEXT,
        requester_notes TEXT,
        reviewed_by INT(11),
        reviewed_at DATETIME,
        response_data LONGTEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_request_type (request_type),
        INDEX idx_requester (requester_id),
        INDEX idx_department (department_id),
        INDEX idx_personnel (personnel_id),
        INDEX idx_field (field_id),
        INDEX idx_status (status),
        INDEX idx_priority (priority),
        INDEX idx_reviewed_by (reviewed_by),
        INDEX idx_created_at (created_at)
    ) $charset_collate;";
    
    dbDelta($sql6);
    
    // ۶. جدول قالب‌ها
    $sql7 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wf_templates (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        template_type ENUM('excel', 'report', 'form') DEFAULT 'excel',
        settings LONGTEXT NOT NULL,
        is_default TINYINT(1) DEFAULT 0,
        description TEXT,
        created_by INT(11),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_template_type (template_type),
        INDEX idx_is_default (is_default),
        INDEX idx_status (status),
        INDEX idx_created_by (created_by)
    ) $charset_collate;";
    
    dbDelta($sql7);
    
    // ۷. جدول لاگ‌ها
    $sql8 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wf_logs (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        log_type ENUM('system', 'user', 'error', 'security', 'export', 'import', 'backup') DEFAULT 'system',
        user_id INT(11),
        action VARCHAR(100) NOT NULL,
        target_type VARCHAR(50),
        target_id INT(11),
        details LONGTEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_log_type (log_type),
        INDEX idx_user_id (user_id),
        INDEX idx_action (action),
        INDEX idx_target (target_type, target_id),
        INDEX idx_severity (severity),
        INDEX idx_created_at (created_at)
    ) $charset_collate;";
    
    dbDelta($sql8);
    
    // ۸. جدول backup
    $sql9 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wf_backups (
        id INT(11) NOT NULL AUTO_INCREMENT,
        backup_type ENUM('full', 'partial', 'auto') DEFAULT 'auto',
        filename VARCHAR(255),
        filepath VARCHAR(500),
        filesize BIGINT(20),
        record_count INT(11),
        backup_data LONGTEXT,
        created_by INT(11),
        status ENUM('success', 'failed', 'partial') DEFAULT 'success',
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_backup_type (backup_type),
        INDEX idx_created_at (created_at),
        INDEX idx_status (status),
        INDEX idx_created_by (created_by)
    ) $charset_collate;";
    
    dbDelta($sql9);
    
    // ۹. جدول user_cards
    $sql10 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wf_user_cards (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        card_name VARCHAR(255),
        field_id INT(11),
        card_type ENUM('sum', 'avg', 'count', 'min', 'max', 'custom') DEFAULT 'count',
        card_color VARCHAR(7) DEFAULT '#1a73e8',
        card_icon VARCHAR(100),
        card_order INT(5) DEFAULT 0,
        filter_conditions LONGTEXT,
        refresh_interval INT(11) DEFAULT 300,
        last_refresh DATETIME,
        is_active TINYINT(1) DEFAULT 1,
        settings LONGTEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_user_card (user_id, field_id, card_type),
        INDEX idx_user_id (user_id),
        INDEX idx_field_id (field_id),
        INDEX idx_is_active (is_active),
        INDEX idx_card_order (card_order)
    ) $charset_collate;";
    
    dbDelta($sql10);
    
    // ۱۰. جدول saved_filters
    $sql11 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wf_saved_filters (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        filter_name VARCHAR(255),
        filter_type ENUM('global', 'department', 'personnel') DEFAULT 'global',
        filter_conditions LONGTEXT NOT NULL,
        is_public TINYINT(1) DEFAULT 0,
        usage_count INT(11) DEFAULT 0,
        last_used DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_user_id (user_id),
        INDEX idx_filter_type (filter_type),
        INDEX idx_is_public (is_public),
        INDEX idx_usage_count (usage_count)
    ) $charset_collate;";
    
    dbDelta($sql11);
    
    // ==================== مرحله ۴: اضافه کردن ایندکس‌های ترکیبی ====================
    
    wf_create_combined_indexes();
    
    // ==================== مرحله ۵: افزودن داده‌های پیش‌فرض ====================
    
    wf_add_default_data();
    
    // ==================== مرحله ۶: ذخیره نسخه دیتابیس ====================
    
    update_option('wf_db_version', '1.0.0');
    update_option('wf_plugin_installed', current_time('mysql'));
    
    // ثبت لاگ نصب
    wf_log_system_action(
        'plugin_installed',
        'پلاگین کارکرد پرسنل نصب شد',
        array('version' => '1.0.0'),
        'info'
    );
}

/**
 * ایجاد ایندکس‌های ترکیبی برای عملکرد بهتر
 */
function wf_create_combined_indexes() {
    global $wpdb;
    
    // بررسی وجود جداول قبل از ایجاد ایندکس
    $tables = array(
        'personnel' => $wpdb->prefix . 'wf_personnel',
        'approvals' => $wpdb->prefix . 'wf_approvals',
        'logs' => $wpdb->prefix . 'wf_logs',
        'departments' => $wpdb->prefix . 'wf_departments'
    );
    
    foreach ($tables as $key => $table) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s", 
            $table
        ));
        
        if ($exists) {
            switch ($key) {
                case 'personnel':
                    // ایندکس برای جستجوی ترکیبی پرسنل
                    $wpdb->query("CREATE INDEX IF NOT EXISTS idx_personnel_dept_status ON {$table} (department_id, status, is_deleted)");
                    $wpdb->query("CREATE INDEX IF NOT EXISTS idx_personnel_completion_warnings ON {$table} (completion_percentage, has_warnings)");
                    $wpdb->query("CREATE INDEX IF NOT EXISTS idx_personnel_dept_completion ON {$table} (department_id, completion_percentage)");
                    break;
                    
                case 'approvals':
                    // ایندکس برای درخواست‌های تایید
                    $wpdb->query("CREATE INDEX IF NOT EXISTS idx_approvals_status_date ON {$table} (status, created_at)");
                    $wpdb->query("CREATE INDEX IF NOT EXISTS idx_approvals_requester_status ON {$table} (requester_id, status)");
                    break;
                    
                case 'logs':
                    // ایندکس برای لاگ‌ها
                    $wpdb->query("CREATE INDEX IF NOT EXISTS idx_logs_type_date ON {$table} (log_type, created_at)");
                    $wpdb->query("CREATE INDEX IF NOT EXISTS idx_logs_user_date ON {$table} (user_id, created_at)");
                    break;
                    
                case 'departments':
                    // ایندکس برای ادارات
                    $wpdb->query("CREATE INDEX IF NOT EXISTS idx_departments_active_manager ON {$table} (is_active, manager_id)");
                    break;
            }
        }
    }
}

/**
 * افزودن داده‌های پیش‌فرض
 */
function wf_add_default_data() {
    global $wpdb;
    
    // ==================== ۱. تنظیمات سیستم ====================
    
    $has_settings = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wf_settings");
    
    if ($has_settings == 0) {
        $default_settings = array(
            array(
                'setting_key' => 'system_version',
                'setting_value' => '1.0.0',
                'setting_type' => 'string',
                'category' => 'system',
                'description' => 'نسخه سیستم مدیریت کارکرد پرسنل'
            ),
            array(
                'setting_key' => 'company_name',
                'setting_value' => 'سازمان بنی اسد',
                'setting_type' => 'string',
                'category' => 'general',
                'description' => 'نام سازمان'
            ),
            array(
                'setting_key' => 'max_export_records',
                'setting_value' => '10000',
                'setting_type' => 'number',
                'category' => 'export',
                'description' => 'حداکثر تعداد رکورد برای خروجی اکسل'
            ),
            array(
                'setting_key' => 'default_items_per_page',
                'setting_value' => '50',
                'setting_type' => 'number',
                'category' => 'display',
                'description' => 'تعداد آیتم در هر صفحه پیش‌فرض'
            ),
            array(
                'setting_key' => 'auto_refresh_interval',
                'setting_value' => '300',
                'setting_type' => 'number',
                'category' => 'performance',
                'description' => 'فاصله به‌روزرسانی خودکار (ثانیه)'
            ),
            array(
                'setting_key' => 'date_format',
                'setting_value' => 'Y/m/d',
                'setting_type' => 'string',
                'category' => 'display',
                'description' => 'فرمت تاریخ پیش‌فرض'
            ),
            array(
                'setting_key' => 'time_format',
                'setting_value' => 'H:i',
                'setting_type' => 'string',
                'category' => 'display',
                'description' => 'فرمت زمان پیش‌فرض'
            ),
            array(
                'setting_key' => 'timezone',
                'setting_value' => 'Asia/Tehran',
                'setting_type' => 'string',
                'category' => 'system',
                'description' => 'منطقه زمانی'
            ),
            array(
                'setting_key' => 'default_language',
                'setting_value' => 'fa_IR',
                'setting_type' => 'string',
                'category' => 'display',
                'description' => 'زبان پیش‌فرض'
            ),
            array(
                'setting_key' => 'enable_backup',
                'setting_value' => '1',
                'setting_type' => 'boolean',
                'category' => 'backup',
                'description' => 'فعال‌سازی پشتیبان‌گیری خودکار'
            ),
            array(
                'setting_key' => 'backup_interval_days',
                'setting_value' => '7',
                'setting_type' => 'number',
                'category' => 'backup',
                'description' => 'فاصله پشتیبان‌گیری (روز)'
            ),
            array(
                'setting_key' => 'log_retention_days',
                'setting_value' => '90',
                'setting_type' => 'number',
                'category' => 'logs',
                'description' => 'مدت نگهداری لاگ‌ها (روز)'
            ),
            array(
                'setting_key' => 'email_notifications',
                'setting_value' => '1',
                'setting_type' => 'boolean',
                'category' => 'notifications',
                'description' => 'فعال‌سازی اعلان‌های ایمیلی'
            ),
            array(
                'setting_key' => 'session_timeout',
                'setting_value' => '3600',
                'setting_type' => 'number',
                'category' => 'security',
                'description' => 'زمان انقضای نشست (ثانیه)'
            ),
            array(
                'setting_key' => 'max_login_attempts',
                'setting_value' => '5',
                'setting_type' => 'number',
                'category' => 'security',
                'description' => 'حداکثر تلاش برای ورود'
            ),
            array(
                'setting_key' => 'password_min_length',
                'setting_value' => '8',
                'setting_type' => 'number',
                'category' => 'security',
                'description' => 'حداقل طول رمز عبور'
            ),
            array(
                'setting_key' => 'maintenance_mode',
                'setting_value' => '0',
                'setting_type' => 'boolean',
                'category' => 'system',
                'description' => 'حالت تعمیرات'
            ),
            array(
                'setting_key' => 'debug_mode',
                'setting_value' => '0',
                'setting_type' => 'boolean',
                'category' => 'system',
                'description' => 'حالت دیباگ'
            )
        );
        
        foreach ($default_settings as $setting) {
            $wpdb->insert("{$wpdb->prefix}wf_settings", $setting);
        }
    }
    
    // ==================== ۲. فیلدهای پیش‌فرض ====================
    
    $has_fields = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wf_fields");
    
    if ($has_fields == 0) {
        $default_fields = array(
            array(
                'field_key' => 'first_name',
                'field_name' => 'نام',
                'field_type' => 'text',
                'is_required' => 1,
                'is_key' => 0,
                'display_order' => 1,
                'description' => 'نام پرسنل',
                'status' => 'active'
            ),
            array(
                'field_key' => 'last_name',
                'field_name' => 'نام خانوادگی',
                'field_type' => 'text',
                'is_required' => 1,
                'is_key' => 0,
                'display_order' => 2,
                'description' => 'نام خانوادگی پرسنل',
                'status' => 'active'
            ),
            array(
                'field_key' => 'father_name',
                'field_name' => 'نام پدر',
                'field_type' => 'text',
                'is_required' => 1,
                'is_key' => 0,
                'display_order' => 3,
                'description' => 'نام پدر پرسنل',
                'status' => 'active'
            ),
            array(
                'field_key' => 'birth_date',
                'field_name' => 'تاریخ تولد',
                'field_type' => 'date',
                'is_required' => 1,
                'is_key' => 0,
                'display_order' => 4,
                'description' => 'تاریخ تولد پرسنل',
                'status' => 'active'
            ),
            array(
                'field_key' => 'birth_certificate_number',
                'field_name' => 'شماره شناسنامه',
                'field_type' => 'text',
                'is_required' => 1,
                'is_key' => 0,
                'display_order' => 5,
                'description' => 'شماره شناسنامه پرسنل',
                'status' => 'active'
            ),
            array(
                'field_key' => 'mobile',
                'field_name' => 'شماره موبایل',
                'field_type' => 'text',
                'is_required' => 1,
                'is_key' => 0,
                'display_order' => 6,
                'description' => 'شماره موبایل پرسنل',
                'status' => 'active'
            ),
            array(
                'field_key' => 'phone',
                'field_name' => 'تلفن ثابت',
                'field_type' => 'text',
                'is_required' => 0,
                'is_key' => 0,
                'display_order' => 7,
                'description' => 'تلفن ثابت پرسنل',
                'status' => 'active'
            ),
            array(
                'field_key' => 'address',
                'field_name' => 'آدرس',
                'field_type' => 'textarea',
                'is_required' => 0,
                'is_key' => 0,
                'display_order' => 8,
                'description' => 'آدرس محل سکونت',
                'status' => 'active'
            ),
            array(
                'field_key' => 'education_level',
                'field_name' => 'مدرک تحصیلی',
                'field_type' => 'select',
                'field_options' => json_encode(array(
                    'دیپلم',
                    'فوق دیپلم',
                    'لیسانس',
                    'فوق لیسانس',
                    'دکترا',
                    'فوق دکترا'
                )),
                'is_required' => 1,
                'is_key' => 0,
                'display_order' => 9,
                'description' => 'آخرین مدرک تحصیلی',
                'status' => 'active'
            ),
            array(
                'field_key' => 'marital_status',
                'field_name' => 'وضعیت تاهل',
                'field_type' => 'select',
                'field_options' => json_encode(array(
                    'مجرد',
                    'متاهل',
                    'مطلقه',
                    'همسر فوت شده'
                )),
                'is_required' => 1,
                'is_key' => 0,
                'display_order' => 10,
                'description' => 'وضعیت تاهل پرسنل',
                'status' => 'active'
            )
        );
        
        foreach ($default_fields as $field) {
            $wpdb->insert("{$wpdb->prefix}wf_fields", $field);
        }
    }
    
    // ==================== ۳. قالب اکسل پیش‌فرض ====================
    
    $has_templates = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wf_templates");
    
    if ($has_templates == 0) {
        $default_template = array(
            'name' => 'قالب پیش‌فرض گزارش پرسنل',
            'template_type' => 'excel',
            'settings' => json_encode(array(
                'header' => array(
                    'bg_color' => '#1a73e8',
                    'font_color' => '#ffffff',
                    'font_size' => 12,
                    'font_bold' => true,
                    'alignment' => 'center',
                    'height' => 30
                ),
                'data' => array(
                    'even_row_color' => '#f8f9fa',
                    'odd_row_color' => '#ffffff',
                    'font_color' => '#202124',
                    'font_size' => 10,
                    'alignment' => 'right',
                    'wrap_text' => true
                ),
                'borders' => array(
                    'style' => 'thin',
                    'color' => '#dadce0',
                    'all_borders' => true
                ),
                'columns' => array(
                    'auto_width' => true,
                    'width_adjustment' => 5
                ),
                'print' => array(
                    'orientation' => 'landscape',
                    'fit_to_page' => true,
                    'scale' => 90
                )
            ), JSON_UNESCAPED_UNICODE),
            'is_default' => 1,
            'description' => 'قالب پیش‌فرض برای خروجی اکسل گزارش‌های پرسنلی',
            'created_by' => 1,
            'status' => 'active'
        );
        
        $wpdb->insert("{$wpdb->prefix}wf_templates", $default_template);
    }
    
    // ==================== ۴. دوره پیش‌فرض ====================
    
    $has_periods = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wf_periods");
    
    if ($has_periods == 0) {
        // استفاده از تابع کمکی برای نام ماه
        $current_month = date('n');
        $month_names = array(
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
            4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
            7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
            10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
        );
        
        $month_name = isset($month_names[$current_month]) ? $month_names[$current_month] : 'نامشخص';
        
        $default_period = array(
            'title' => 'دوره ' . $month_name . ' ' . date('Y'),
            'period_code' => 'PERIOD_' . date('Ym'),
            'start_date' => date('Y-m-01'),
            'end_date' => date('Y-m-t'),
            'description' => 'دوره کاری پیش‌فرض سیستم',
            'is_active' => 1,
            'status' => 'active',
            'created_by' => 1
        );
        
        $wpdb->insert("{$wpdb->prefix}wf_periods", $default_period);
    }
}

// ==================== توابع CRUD برای فیلدها ====================

/**
 * ایجاد فیلد جدید
 */
function wf_create_field($data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_fields';
    
    // اعتبارسنجی داده‌ها
    if (empty($data['field_name'])) {
        return array(
            'success' => false,
            'message' => 'عنوان فیلد الزامی است'
        );
    }
    
    if (empty($data['field_key'])) {
        return array(
            'success' => false,
            'message' => 'کلید فیلد الزامی است'
        );
    }
    
    // بررسی فرمت کلید فیلد
    if (!preg_match('/^[a-z][a-z0-9_]*$/', $data['field_key'])) {
        return array(
            'success' => false,
            'message' => 'کلید فیلد باید با حروف انگلیسی شروع شود و فقط شامل حروف کوچک، اعداد و زیرخط باشد'
        );
    }
    
    // بررسی تکراری نبودن کلید
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE field_key = %s AND status != 'deleted'",
        sanitize_key($data['field_key'])
    ));
    
    if ($exists > 0) {
        return array(
            'success' => false,
            'message' => 'کلید فیلد تکراری است'
        );
    }
    
    // آماده‌سازی داده‌ها
    $field_data = array(
        'field_key' => sanitize_key($data['field_key']),
        'field_name' => sanitize_text_field($data['field_name']),
        'field_type' => isset($data['field_type']) ? sanitize_text_field($data['field_type']) : 'text',
        'field_options' => isset($data['field_options']) ? json_encode($data['field_options'], JSON_UNESCAPED_UNICODE) : null,
        'validation_rules' => isset($data['validation_rules']) ? json_encode($data['validation_rules'], JSON_UNESCAPED_UNICODE) : null,
        'is_required' => isset($data['is_required']) ? (int)$data['is_required'] : 0,
        'is_locked' => isset($data['is_locked']) ? (int)$data['is_locked'] : 0,
        'is_monitoring' => isset($data['is_monitoring']) ? (int)$data['is_monitoring'] : 0,
        'is_key' => isset($data['is_key']) ? (int)$data['is_key'] : 0,
        'display_order' => isset($data['display_order']) ? (int)$data['display_order'] : 0,
        'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : null,
        'created_by' => get_current_user_id(),
        'status' => 'active'
    );
    
    // درج در دیتابیس
    $result = $wpdb->insert($table_name, $field_data);
    
    if ($result) {
        $field_id = $wpdb->insert_id;
        
        // ثبت لاگ
        wf_log_user_action(
            'field_created',
            'فیلد جدید ایجاد شد: ' . $field_data['field_name'],
            array(
                'field_id' => $field_id,
                'field_key' => $field_data['field_key'],
                'field_type' => $field_data['field_type']
            )
        );
        
        return array(
            'success' => true,
            'message' => 'فیلد با موفقیت ایجاد شد',
            'field_id' => $field_id,
            'data' => $field_data
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در ایجاد فیلد: ' . $wpdb->last_error
    );
}

/**
 * به‌روزرسانی فیلد
 */
function wf_update_field($field_id, $data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_fields';
    
    // بررسی وجود فیلد
    $field = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $field_id
    ), ARRAY_A);
    
    if (!$field) {
        return array(
            'success' => false,
            'message' => 'فیلد مورد نظر یافت نشد'
        );
    }
    
    // اگر فیلد قفل است، فقط ادمین می‌تواند ویرایش کند
    if ($field['is_locked'] == 1 && !current_user_can('manage_options')) {
        return array(
            'success' => false,
            'message' => 'فیلد قفل شده است و فقط مدیر سیستم می‌تواند آن را ویرایش کند'
        );
    }
    
    // آماده‌سازی داده‌های به‌روزرسانی
    $update_data = array();
    
    if (isset($data['field_name'])) {
        $update_data['field_name'] = sanitize_text_field($data['field_name']);
    }
    
    if (isset($data['field_type'])) {
        $update_data['field_type'] = sanitize_text_field($data['field_type']);
    }
    
    if (isset($data['field_options'])) {
        $update_data['field_options'] = json_encode($data['field_options'], JSON_UNESCAPED_UNICODE);
    }
    
    if (isset($data['validation_rules'])) {
        $update_data['validation_rules'] = json_encode($data['validation_rules'], JSON_UNESCAPED_UNICODE);
    }
    
    if (isset($data['is_required'])) {
        $update_data['is_required'] = (int)$data['is_required'];
    }
    
    if (isset($data['is_locked'])) {
        $update_data['is_locked'] = (int)$data['is_locked'];
    }
    
    if (isset($data['is_monitoring'])) {
        $update_data['is_monitoring'] = (int)$data['is_monitoring'];
    }
    
    if (isset($data['display_order'])) {
        $update_data['display_order'] = (int)$data['display_order'];
    }
    
    if (isset($data['description'])) {
        $update_data['description'] = sanitize_textarea_field($data['description']);
    }
    
    if (isset($data['status'])) {
        $update_data['status'] = sanitize_text_field($data['status']);
    }
    
    $update_data['updated_at'] = current_time('mysql');
    
    if (empty($update_data)) {
        return array(
            'success' => false,
            'message' => 'هیچ داده‌ای برای به‌روزرسانی ارسال نشده است'
        );
    }
    
    // به‌روزرسانی در دیتابیس
    $result = $wpdb->update(
        $table_name,
        $update_data,
        array('id' => $field_id)
    );
    
    if ($result !== false) {
        // ثبت لاگ
        wf_log_user_action(
            'field_updated',
            'فیلد به‌روزرسانی شد: ' . ($update_data['field_name'] ?? $field['field_name']),
            array(
                'field_id' => $field_id,
                'changes' => array_keys($update_data)
            )
        );
        
        return array(
            'success' => true,
            'message' => 'فیلد با موفقیت به‌روزرسانی شد'
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در به‌روزرسانی فیلد: ' . $wpdb->last_error
    );
}

/**
 * حذف فیلد (حذف نرم)
 */
function wf_delete_field($field_id, $permanent = false) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_fields';
    
    // بررسی وجود فیلد
    $field = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $field_id
    ), ARRAY_A);
    
    if (!$field) {
        return array(
            'success' => false,
            'message' => 'فیلد مورد نظر یافت نشد'
        );
    }
    
    // بررسی استفاده از فیلد در داده‌های پرسنل
    $is_used = wf_is_field_used_in_personnel($field['field_key']);
    
    if ($is_used) {
        return array(
            'success' => false,
            'message' => 'این فیلد در داده‌های پرسنل استفاده شده و نمی‌توان آن را حذف کرد'
        );
    }
    
    if ($permanent) {
        // حذف فیزیکی
        $result = $wpdb->delete($table_name, array('id' => $field_id));
    } else {
        // حذف نرم (تغییر وضعیت)
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => 'deleted',
                'updated_at' => current_time('mysql')
            ),
            array('id' => $field_id)
        );
    }
    
    if ($result) {
        // ثبت لاگ
        wf_log_user_action(
            $permanent ? 'field_permanently_deleted' : 'field_deleted',
            ($permanent ? 'فیلد حذف شد: ' : 'فیلد غیرفعال شد: ') . $field['field_name'],
            array(
                'field_id' => $field_id,
                'field_key' => $field['field_key'],
                'permanent' => $permanent
            )
        );
        
        return array(
            'success' => true,
            'message' => $permanent ? 'فیلد با موفقیت حذف شد' : 'فیلد با موفقیت غیرفعال شد'
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در حذف فیلد: ' . $wpdb->last_error
    );
}

/**
 * بررسی استفاده از فیلد در داده‌های پرسنل
 */
function wf_is_field_used_in_personnel($field_key) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_personnel';
    
    // بررسی در JSON داده‌ها
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name 
         WHERE is_deleted = 0 
         AND JSON_EXTRACT(data, '$.%s') IS NOT NULL",
        $field_key
    ));
    
    return $count > 0;
}

/**
 * دریافت فیلد بر اساس ID
 */
function wf_get_field($field_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_fields';
    
    $field = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d AND status != 'deleted'",
        $field_id
    ), ARRAY_A);
    
    if ($field) {
        // تبدیل JSON
        if (!empty($field['field_options'])) {
            $field['field_options'] = json_decode($field['field_options'], true);
        }
        
        if (!empty($field['validation_rules'])) {
            $field['validation_rules'] = json_decode($field['validation_rules'], true);
        }
    }
    
    return $field;
}

/**
 * دریافت فیلد بر اساس کلید
 */
function wf_get_field_by_key($field_key) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_fields';
    
    $field = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE field_key = %s AND status != 'deleted'",
        sanitize_key($field_key)
    ), ARRAY_A);
    
    if ($field && !empty($field['field_options'])) {
        $field['field_options'] = json_decode($field['field_options'], true);
    }
    
    return $field;
}

/**
 * دریافت همه فیلدها
 */
function wf_get_all_fields($filters = array()) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_fields';
    
    $where = array("status != 'deleted'");
    $params = array();
    
    if (!empty($filters['status'])) {
        $where[] = "status = %s";
        $params[] = $filters['status'];
    }
    
    if (isset($filters['is_required'])) {
        $where[] = "is_required = %d";
        $params[] = (int)$filters['is_required'];
    }
    
    if (isset($filters['is_locked'])) {
        $where[] = "is_locked = %d";
        $params[] = (int)$filters['is_locked'];
    }
    
    if (isset($filters['is_monitoring'])) {
        $where[] = "is_monitoring = %d";
        $params[] = (int)$filters['is_monitoring'];
    }
    
    if (!empty($filters['field_type'])) {
        $where[] = "field_type = %s";
        $params[] = $filters['field_type'];
    }
    
    if (!empty($filters['search'])) {
        $where[] = "(field_name LIKE %s OR field_key LIKE %s OR description LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // مرتب‌سازی
    $order_by = 'display_order ASC, field_name ASC';
    if (!empty($filters['order_by'])) {
        $order_by = sanitize_sql_orderby($filters['order_by']);
    }
    
    $sql = "SELECT * FROM $table_name $where_clause ORDER BY $order_by";
    
    if (!empty($params)) {
        $sql = $wpdb->prepare($sql, $params);
    }
    
    $fields = $wpdb->get_results($sql, ARRAY_A);
    
    // پردازش فیلدهای JSON
    foreach ($fields as &$field) {
        if (!empty($field['field_options'])) {
            $field['field_options'] = json_decode($field['field_options'], true);
        }
        
        if (!empty($field['validation_rules'])) {
            $field['validation_rules'] = json_decode($field['validation_rules'], true);
        }
    }
    
    return $fields;
}

/**
 * دریافت فیلدهای ضروری
 */
function wf_get_required_fields() {
    return wf_get_all_fields(array('is_required' => 1));
}

/**
 * دریافت فیلدهای مانیتورینگ
 */
function wf_get_monitoring_fields() {
    return wf_get_all_fields(array('is_monitoring' => 1));
}

/**
 * شمارش فیلدها
 */
function wf_count_fields($filters = array()) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_fields';
    
    $where = array("status != 'deleted'");
    $params = array();
    
    if (!empty($filters['status'])) {
        $where[] = "status = %s";
        $params[] = $filters['status'];
    }
    
    if (isset($filters['is_required'])) {
        $where[] = "is_required = %d";
        $params[] = (int)$filters['is_required'];
    }
    
    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $sql = "SELECT COUNT(*) FROM $table_name $where_clause";
    
    if (!empty($params)) {
        $sql = $wpdb->prepare($sql, $params);
    }
    
    return (int)$wpdb->get_var($sql);
}

// ==================== توابع CRUD برای ادارات ====================

/**
 * ایجاد اداره جدید
 */
function wf_create_department($data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_departments';
    
    // اعتبارسنجی داده‌ها
    if (empty($data['name'])) {
        return array(
            'success' => false,
            'message' => 'نام اداره الزامی است'
        );
    }
    
    // بررسی تکراری نبودن کد اداره
    if (!empty($data['code'])) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE code = %s AND is_active = 1",
            sanitize_text_field($data['code'])
        ));
        
        if ($exists > 0) {
            return array(
                'success' => false,
                'message' => 'کد اداره تکراری است'
            );
        }
    }
    
    // بررسی مدیر انتخابی
    if (!empty($data['manager_id'])) {
        $user = get_user_by('id', $data['manager_id']);
        if (!$user) {
            return array(
                'success' => false,
                'message' => 'کاربر انتخاب شده به عنوان مدیر معتبر نیست'
            );
        }
    }
    
    // آماده‌سازی داده‌ها
    $department_data = array(
        'name' => sanitize_text_field($data['name']),
        'code' => !empty($data['code']) ? sanitize_text_field($data['code']) : null,
        'description' => !empty($data['description']) ? sanitize_textarea_field($data['description']) : null,
        'manager_id' => !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
        'parent_id' => !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
        'color' => !empty($data['color']) ? sanitize_hex_color($data['color']) : '#2E86C1',
        'settings' => !empty($data['settings']) ? json_encode($data['settings'], JSON_UNESCAPED_UNICODE) : null,
        'display_order' => !empty($data['display_order']) ? (int)$data['display_order'] : 0,
        'created_by' => get_current_user_id(),
        'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
    );
    
    // درج در دیتابیس
    $result = $wpdb->insert($table_name, $department_data);
    
    if ($result) {
        $department_id = $wpdb->insert_id;
        
        // به‌روزرسانی نقش کاربر اگر مدیر انتخاب شده
        if ($department_data['manager_id']) {
            wf_assign_department_manager($department_data['manager_id'], $department_id);
        }
        
        // ثبت لاگ
        wf_log_user_action(
            'department_created',
            'اداره جدید ایجاد شد: ' . $department_data['name'],
            array(
                'department_id' => $department_id,
                'manager_id' => $department_data['manager_id'],
                'parent_id' => $department_data['parent_id']
            )
        );
        
        return array(
            'success' => true,
            'message' => 'اداره با موفقیت ایجاد شد',
            'department_id' => $department_id,
            'data' => $department_data
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در ایجاد اداره: ' . $wpdb->last_error
    );
}

/**
 * اختصاص مدیر به اداره
 */
function wf_assign_department_manager($user_id, $department_id) {
    // اضافه کردن نقش مدیر اداره
    $user = get_user_by('id', $user_id);
    if ($user) {
        $user->add_role('wf_department_manager');
        update_user_meta($user_id, 'wf_assigned_department', $department_id);
        update_user_meta($user_id, 'wf_is_department_manager', 1);
        
        // ثبت لاگ
        wf_log_user_action(
            'manager_assigned',
            'مدیر به اداره اختصاص داده شد',
            array(
                'user_id' => $user_id,
                'department_id' => $department_id
            )
        );
    }
}

/**
 * به‌روزرسانی اداره
 */
function wf_update_department($department_id, $data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_departments';
    
    // بررسی وجود اداره
    $department = wf_get_department($department_id);
    if (!$department) {
        return array(
            'success' => false,
            'message' => 'اداره مورد نظر یافت نشد'
        );
    }
    
    // آماده‌سازی داده‌های به‌روزرسانی
    $update_data = array();
    $changes = array();
    
    if (isset($data['name'])) {
        $update_data['name'] = sanitize_text_field($data['name']);
        $changes[] = 'name';
    }
    
    if (isset($data['code'])) {
        $update_data['code'] = sanitize_text_field($data['code']);
        $changes[] = 'code';
    }
    
    if (isset($data['description'])) {
        $update_data['description'] = sanitize_textarea_field($data['description']);
        $changes[] = 'description';
    }
    
    if (isset($data['color'])) {
        $update_data['color'] = sanitize_hex_color($data['color']);
        $changes[] = 'color';
    }
    
    if (isset($data['parent_id'])) {
        $update_data['parent_id'] = (int)$data['parent_id'];
        $changes[] = 'parent_id';
        
        // بررسی عدم ایجاد حلقه در ساختار درختی
        if ($update_data['parent_id'] == $department_id) {
            return array(
                'success' => false,
                'message' => 'اداره نمی‌تواند والد خود باشد'
            );
        }
    }
    
    if (isset($data['display_order'])) {
        $update_data['display_order'] = (int)$data['display_order'];
        $changes[] = 'display_order';
    }
    
    if (isset($data['settings'])) {
        $update_data['settings'] = json_encode($data['settings'], JSON_UNESCAPED_UNICODE);
        $changes[] = 'settings';
    }
    
    if (isset($data['is_active'])) {
        $update_data['is_active'] = (int)$data['is_active'];
        $changes[] = 'is_active';
        
        // اگر اداره غیرفعال شود، پرسنل آن نیز غیرفعال می‌شوند
        if ($update_data['is_active'] == 0) {
            wf_deactivate_department_personnel($department_id);
        }
    }
    
    // مدیریت تغییر مدیر
    if (isset($data['manager_id'])) {
        $new_manager_id = (int)$data['manager_id'];
        
        if ($new_manager_id != $department['manager_id']) {
            // حذف نقش مدیر قبلی
            if ($department['manager_id']) {
                wf_remove_department_manager_role($department['manager_id']);
            }
            
            // اختصاص نقش مدیر جدید
            if ($new_manager_id > 0) {
                wf_assign_department_manager($new_manager_id, $department_id);
            }
            
            $update_data['manager_id'] = $new_manager_id;
            $changes[] = 'manager_id';
        }
    }
    
    $update_data['updated_at'] = current_time('mysql');
    
    if (empty($update_data)) {
        return array(
            'success' => false,
            'message' => 'هیچ داده‌ای برای به‌روزرسانی ارسال نشده است'
        );
    }
    
    // به‌روزرسانی در دیتابیس
    $result = $wpdb->update(
        $table_name,
        $update_data,
        array('id' => $department_id)
    );
    
    if ($result !== false) {
        // ثبت لاگ
        wf_log_user_action(
            'department_updated',
            'اداره به‌روزرسانی شد: ' . ($update_data['name'] ?? $department['name']),
            array(
                'department_id' => $department_id,
                'changes' => $changes
            )
        );
        
        return array(
            'success' => true,
            'message' => 'اداره با موفقیت به‌روزرسانی شد'
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در به‌روزرسانی اداره: ' . $wpdb->last_error
    );
}

/**
 * حذف نقش مدیر اداره
 */
function wf_remove_department_manager_role($user_id) {
    $user = get_user_by('id', $user_id);
    if ($user) {
        $user->remove_role('wf_department_manager');
        delete_user_meta($user_id, 'wf_assigned_department');
        delete_user_meta($user_id, 'wf_is_department_manager');
    }
}

/**
 * غیرفعال کردن پرسنل یک اداره
 */
function wf_deactivate_department_personnel($department_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_personnel';
    
    $wpdb->update(
        $table_name,
        array('status' => 'inactive', 'updated_at' => current_time('mysql')),
        array('department_id' => $department_id, 'is_deleted' => 0)
    );
}

/**
 * حذف اداره (حذف نرم)
 */
function wf_delete_department($department_id, $permanent = false) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_departments';
    
    // بررسی وجود اداره
    $department = wf_get_department($department_id);
    if (!$department) {
        return array(
            'success' => false,
            'message' => 'اداره مورد نظر یافت نشد'
        );
    }
    
    // بررسی داشتن پرسنل فعال
    $has_personnel = wf_count_department_personnel($department_id);
    if ($has_personnel > 0) {
        return array(
            'success' => false,
            'message' => 'این اداره دارای پرسنل فعال است و نمی‌توان آن را حذف کرد'
        );
    }
    
    // بررسی داشتن زیرمجموعه فعال
    $has_children = wf_count_department_children($department_id);
    if ($has_children > 0) {
        return array(
            'success' => false,
            'message' => 'این اداره دارای زیرمجموعه فعال است و نمی‌توان آن را حذف کرد'
        );
    }
    
    if ($permanent) {
        // حذف فیزیکی
        $result = $wpdb->delete($table_name, array('id' => $department_id));
    } else {
        // حذف نرم (غیرفعال کردن)
        $result = $wpdb->update(
            $table_name,
            array(
                'is_active' => 0,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $department_id)
        );
    }
    
    if ($result) {
        // حذف نقش مدیر
        if ($department['manager_id']) {
            wf_remove_department_manager_role($department['manager_id']);
        }
        
        // ثبت لاگ
        wf_log_user_action(
            $permanent ? 'department_permanently_deleted' : 'department_deactivated',
            ($permanent ? 'اداره حذف شد: ' : 'اداره غیرفعال شد: ') . $department['name'],
            array(
                'department_id' => $department_id,
                'permanent' => $permanent
            )
        );
        
        return array(
            'success' => true,
            'message' => $permanent ? 'اداره با موفقیت حذف شد' : 'اداره با موفقیت غیرفعال شد'
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در حذف اداره: ' . $wpdb->last_error
    );
}

/**
 * دریافت اداره بر اساس ID
 */
function wf_get_department($department_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_departments';
    
    $department = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $department_id
    ), ARRAY_A);
    
    if ($department && !empty($department['settings'])) {
        $department['settings'] = json_decode($department['settings'], true);
    }
    
    return $department;
}

/**
 * دریافت اداره بر اساس کد
 */
function wf_get_department_by_code($code) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_departments';
    
    $department = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE code = %s AND is_active = 1",
        sanitize_text_field($code)
    ), ARRAY_A);
    
    if ($department && !empty($department['settings'])) {
        $department['settings'] = json_decode($department['settings'], true);
    }
    
    return $department;
}

/**
 * دریافت اداره بر اساس مدیر
 */
function wf_get_department_by_manager($manager_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_departments';
    
    $department = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE manager_id = %d AND is_active = 1",
        (int)$manager_id
    ), ARRAY_A);
    
    return $department;
}

/**
 * دریافت همه ادارات
 */
function wf_get_all_departments($filters = array()) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_departments';
    
    $where = array();
    $params = array();
    
    if (!isset($filters['include_inactive']) || !$filters['include_inactive']) {
        $where[] = "is_active = 1";
    }
    
    if (!empty($filters['manager_id'])) {
        $where[] = "manager_id = %d";
        $params[] = (int)$filters['manager_id'];
    }
    
    if (!empty($filters['parent_id'])) {
        if ($filters['parent_id'] === 'null') {
            $where[] = "parent_id IS NULL";
        } else {
            $where[] = "parent_id = %d";
            $params[] = (int)$filters['parent_id'];
        }
    }
    
    if (!empty($filters['search'])) {
        $where[] = "(name LIKE %s OR code LIKE %s OR description LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // مرتب‌سازی
    $order_by = 'display_order ASC, name ASC';
    if (!empty($filters['order_by'])) {
        $order_by = sanitize_sql_orderby($filters['order_by']);
    }
    
    $sql = "SELECT * FROM $table_name $where_clause ORDER BY $order_by";
    
    if (!empty($params)) {
        $sql = $wpdb->prepare($sql, $params);
    }
    
    $departments = $wpdb->get_results($sql, ARRAY_A);
    
    // پردازش تنظیمات JSON
    foreach ($departments as &$department) {
        if (!empty($department['settings'])) {
            $department['settings'] = json_decode($department['settings'], true);
        }
    }
    
    return $departments;
}

/**
 * دریافت ساختار درختی ادارات
 */
function wf_get_department_tree($parent_id = null) {
    $departments = wf_get_all_departments();
    $tree = array();
    
    if (empty($departments)) {
        return $tree;
    }
    
    // ایجاد آرایه‌ای با کلید id
    $indexed = array();
    foreach ($departments as $department) {
        $indexed[$department['id']] = $department;
        $indexed[$department['id']]['children'] = array();
    }
    
    // ساختن درخت
    foreach ($indexed as $id => &$department) {
        if ($department['parent_id'] && isset($indexed[$department['parent_id']])) {
            $indexed[$department['parent_id']]['children'][] = &$department;
        } elseif ($department['parent_id'] === null || $department['parent_id'] == 0) {
            $tree[] = &$department;
        }
    }
    
    // اگر parent_id مشخص شده، فقط زیرمجموعه‌های آن را برگردان
    if ($parent_id !== null) {
        if (isset($indexed[$parent_id])) {
            return $indexed[$parent_id]['children'];
        }
        return array();
    }
    
    return $tree;
}

/**
 * شمارش پرسنل یک اداره
 */
function wf_count_department_personnel($department_id, $include_inactive = false) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_personnel';
    
    $where = array("department_id = %d", "is_deleted = 0");
    $params = array($department_id);
    
    if (!$include_inactive) {
        $where[] = "status = 'active'";
    }
    
    $where_clause = implode(' AND ', $where);
    
    $sql = $wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE $where_clause",
        $params
    );
    
    return (int)$wpdb->get_var($sql);
}

/**
 * شمارش زیرمجموعه‌های یک اداره
 */
function wf_count_department_children($department_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_departments';
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE parent_id = %d AND is_active = 1",
        $department_id
    ));
    
    return (int)$count;
}

/**
 * به‌روزرسانی تعداد پرسنل اداره
 */
function wf_update_department_personnel_count($department_id) {
    global $wpdb;
    
    $personnel_count = wf_count_department_personnel($department_id);
    
    // ذخیره در meta یا ستون جداگانه اگر نیاز باشد
    // فعلاً فقط return می‌کنیم
    return $personnel_count;
}

// ==================== توابع CRUD برای پرسنل ====================

/**
 * ایجاد پرسنل جدید
 */
function wf_create_personnel($data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_personnel';
    
    // اعتبارسنجی داده‌های الزامی
    if (empty($data['national_code'])) {
        return array(
            'success' => false,
            'message' => 'کد ملی الزامی است'
        );
    }
    
    if (empty($data['department_id'])) {
        return array(
            'success' => false,
            'message' => 'انتخاب اداره الزامی است'
        );
    }
    
    // بررسی تکراری نبودن کد ملی
    if (wf_is_national_code_exists($data['national_code'])) {
        return array(
            'success' => false,
            'message' => 'کد ملی تکراری است'
        );
    }
    
    // بررسی وجود اداره
    $department = wf_get_department($data['department_id']);
    if (!$department) {
        return array(
            'success' => false,
            'message' => 'اداره انتخاب شده معتبر نیست'
        );
    }
    
    // آماده‌سازی داده‌های داینامیک
    $fields = wf_get_all_fields();
    $dynamic_data = array();
    $required_fields_missing = array();
    
    foreach ($fields as $field) {
        $field_key = $field['field_key'];
        
        if (isset($data[$field_key])) {
            // پاکسازی مقدار بر اساس نوع فیلد
            $value = wf_sanitize_field_value($data[$field_key], $field['field_type']);
            $dynamic_data[$field_key] = $value;
        } elseif ($field['is_required'] == 1) {
            // اگر فیلد ضروری باشد و مقدار نداشته باشد
            $required_fields_missing[] = $field['field_name'];
        }
    }
    
    // بررسی فیلدهای ضروری خالی
    if (!empty($required_fields_missing)) {
        return array(
            'success' => false,
            'message' => 'فیلدهای ضروری زیر پر نشده‌اند: ' . implode(', ', $required_fields_missing),
            'missing_fields' => $required_fields_missing
        );
    }
    
    // محاسبه درصد تکمیل
    $completion_stats = wf_calculate_completion_percentage($dynamic_data);
    
    // آماده‌سازی داده‌های اصلی
    $personnel_data = array(
        'national_code' => sanitize_text_field($data['national_code']),
        'department_id' => (int)$data['department_id'],
        'employment_date' => !empty($data['employment_date']) ? wf_convert_to_gregorian($data['employment_date']) : null,
        'employment_type' => !empty($data['employment_type']) ? sanitize_text_field($data['employment_type']) : 'permanent',
        'position' => !empty($data['position']) ? sanitize_text_field($data['position']) : null,
        'level' => !empty($data['level']) ? sanitize_text_field($data['level']) : null,
        'profile_image' => !empty($data['profile_image']) ? esc_url_raw($data['profile_image']) : null,
        'data' => json_encode($dynamic_data, JSON_UNESCAPED_UNICODE),
        'required_fields_completed' => $completion_stats['required_completed'],
        'total_fields' => $completion_stats['total_fields'],
        'completion_percentage' => $completion_stats['percentage'],
        'has_warnings' => $completion_stats['has_warnings'] ? 1 : 0,
        'warnings' => !empty($completion_stats['warnings']) ? json_encode($completion_stats['warnings'], JSON_UNESCAPED_UNICODE) : null,
        'created_by' => get_current_user_id(),
        'last_modified_by' => get_current_user_id(),
        'status' => !empty($data['status']) ? sanitize_text_field($data['status']) : 'active'
    );
    
    // درج در دیتابیس
    $result = $wpdb->insert($table_name, $personnel_data);
    
    if ($result) {
        $personnel_id = $wpdb->insert_id;
        
        // ثبت لاگ
        wf_log_user_action(
            'personnel_created',
            'پرسنل جدید اضافه شد: ' . $personnel_data['national_code'],
            array(
                'personnel_id' => $personnel_id,
                'national_code' => $personnel_data['national_code'],
                'department_id' => $personnel_data['department_id'],
                'completion_percentage' => $completion_stats['percentage']
            )
        );
        
        return array(
            'success' => true,
            'message' => 'پرسنل با موفقیت اضافه شد',
            'personnel_id' => $personnel_id,
            'completion_percentage' => $completion_stats['percentage'],
            'has_warnings' => $completion_stats['has_warnings']
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در اضافه کردن پرسنل: ' . $wpdb->last_error
    );
}

/**
 * بررسی وجود کد ملی
 */
function wf_is_national_code_exists($national_code, $exclude_id = null) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_personnel';
    
    $where = array("national_code = %s", "is_deleted = 0");
    $params = array(sanitize_text_field($national_code));
    
    if ($exclude_id) {
        $where[] = "id != %d";
        $params[] = (int)$exclude_id;
    }
    
    $where_clause = implode(' AND ', $where);
    
    $sql = $wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE $where_clause",
        $params
    );
    
    $count = (int)$wpdb->get_var($sql);
    
    return $count > 0;
}

/**
 * محاسبه درصد تکمیل اطلاعات
 */
function wf_calculate_completion_percentage($data) {
    $fields = wf_get_all_fields();
    
    $total_required = 0;
    $completed_required = 0;
    $total_fields = count($fields);
    $completed_fields = 0;
    $warnings = array();
    
    foreach ($fields as $field) {
        $field_key = $field['field_key'];
        $has_value = isset($data[$field_key]) && !empty($data[$field_key]);
        
        if ($field['is_required'] == 1) {
            $total_required++;
            if ($has_value) {
                $completed_required++;
            } else {
                $warnings[] = 'فیلد ضروری "' . $field['field_name'] . '" پر نشده است';
            }
        }
        
        if ($has_value) {
            $completed_fields++;
        }
    }
    
    // محاسبه درصد
    $required_percentage = $total_required > 0 ? ($completed_required / $total_required) * 100 : 100;
    $total_percentage = $total_fields > 0 ? ($completed_fields / $total_fields) * 100 : 100;
    
    // میانگین وزنی (وزن بیشتر برای فیلدهای ضروری)
    $completion_percentage = ($required_percentage * 0.7) + ($total_percentage * 0.3);
    
    return array(
        'required_completed' => $completed_required,
        'total_required' => $total_required,
        'fields_completed' => $completed_fields,
        'total_fields' => $total_fields,
        'percentage' => round($completion_percentage, 2),
        'has_warnings' => !empty($warnings),
        'warnings' => $warnings
    );
}

/**
 * پاکسازی مقدار فیلد بر اساس نوع
 */
function wf_sanitize_field_value($value, $field_type) {
    if (is_null($value) || $value === '') {
        return null;
    }
    
    switch ($field_type) {
        case 'number':
        case 'decimal':
            return is_numeric($value) ? $value : 0;
            
        case 'date':
        case 'datetime':
        case 'time':
            return sanitize_text_field($value);
            
        case 'textarea':
            return sanitize_textarea_field($value);
            
        case 'checkbox':
            return $value ? 1 : 0;
            
        case 'select':
            return is_array($value) ? array_map('sanitize_text_field', $value) : sanitize_text_field($value);
            
        case 'file':
            return esc_url_raw($value);
            
        case 'text':
        default:
            return sanitize_text_field($value);
    }
}

/**
 * به‌روزرسانی اطلاعات پرسنل
 */
function wf_update_personnel($personnel_id, $data, $require_approval = false) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_personnel';
    
    // بررسی وجود پرسنل
    $personnel = wf_get_personnel($personnel_id);
    if (!$personnel) {
        return array(
            'success' => false,
            'message' => 'پرسنل مورد نظر یافت نشد'
        );
    }
    
    // بررسی دسترسی کاربر
    $current_user_id = get_current_user_id();
    $user_can_edit = wf_can_user_edit_personnel($current_user_id, $personnel_id);
    
    // اگر نیاز به تایید باشد یا کاربر مستقیم نمی‌تواند ویرایش کند
    if ($require_approval || !$user_can_edit['direct_edit']) {
        return wf_create_approval_request(
            'edit_personnel',
            array(
                'personnel_id' => $personnel_id,
                'changes' => $data,
                'current_data' => $personnel
            ),
            $current_user_id,
            $personnel['department_id'],
            $personnel_id
        );
    }
    
    // پردازش داده‌های داینامیک
    $current_dynamic_data = json_decode($personnel['data'], true);
    $new_dynamic_data = $current_dynamic_data;
    $updated_fields = array();
    
    $fields = wf_get_all_fields();
    
    foreach ($fields as $field) {
        $field_key = $field['field_key'];
        
        // بررسی آیا فیلد در داده‌های ارسالی وجود دارد
        if (array_key_exists($field_key, $data)) {
            // بررسی قفل بودن فیلد
            if ($field['is_locked'] == 1 && !current_user_can('manage_options')) {
                continue; // فقط ادمین می‌تواند فیلدهای قفل را ویرایش کند
            }
            
            $old_value = isset($current_dynamic_data[$field_key]) ? $current_dynamic_data[$field_key] : null;
            $new_value = wf_sanitize_field_value($data[$field_key], $field['field_type']);
            
            // اگر مقدار تغییر کرده باشد
            if ($old_value != $new_value) {
                $new_dynamic_data[$field_key] = $new_value;
                $updated_fields[$field_key] = array(
                    'old' => $old_value,
                    'new' => $new_value,
                    'field_name' => $field['field_name']
                );
            }
        }
    }
    
    // محاسبه درصد تکمیل جدید
    $completion_stats = wf_calculate_completion_percentage($new_dynamic_data);
    
    // آماده‌سازی داده‌های به‌روزرسانی
    $update_data = array(
        'data' => json_encode($new_dynamic_data, JSON_UNESCAPED_UNICODE),
        'required_fields_completed' => $completion_stats['required_completed'],
        'total_fields' => $completion_stats['total_fields'],
        'completion_percentage' => $completion_stats['percentage'],
        'has_warnings' => $completion_stats['has_warnings'] ? 1 : 0,
        'warnings' => !empty($completion_stats['warnings']) ? json_encode($completion_stats['warnings'], JSON_UNESCAPED_UNICODE) : null,
        'last_modified_by' => $current_user_id,
        'updated_at' => current_time('mysql')
    );
    
    // به‌روزرسانی فیلدهای اصلی اگر ارسال شده باشند
    if (isset($data['department_id']) && $data['department_id'] != $personnel['department_id']) {
        $update_data['department_id'] = (int)$data['department_id'];
    }
    
    if (isset($data['employment_date'])) {
        $update_data['employment_date'] = wf_convert_to_gregorian($data['employment_date']);
    }
    
    if (isset($data['employment_type'])) {
        $update_data['employment_type'] = sanitize_text_field($data['employment_type']);
    }
    
    if (isset($data['position'])) {
        $update_data['position'] = sanitize_text_field($data['position']);
    }
    
    if (isset($data['level'])) {
        $update_data['level'] = sanitize_text_field($data['level']);
    }
    
    if (isset($data['status'])) {
        $update_data['status'] = sanitize_text_field($data['status']);
    }
    
    if (isset($data['profile_image'])) {
        $update_data['profile_image'] = esc_url_raw($data['profile_image']);
    }
    
    // به‌روزرسانی در دیتابیس
    $result = $wpdb->update(
        $table_name,
        $update_data,
        array('id' => $personnel_id)
    );
    
    if ($result !== false) {
        // اگر اداره تغییر کرده باشد، تعداد پرسنل را به‌روزرسانی کن
        if (isset($update_data['department_id']) && $update_data['department_id'] != $personnel['department_id']) {
            wf_update_department_personnel_count($personnel['department_id']);
            wf_update_department_personnel_count($update_data['department_id']);
        }
        
        // ثبت لاگ
        wf_log_user_action(
            'personnel_updated',
            'اطلاعات پرسنل به‌روزرسانی شد: ' . $personnel['national_code'],
            array(
                'personnel_id' => $personnel_id,
                'national_code' => $personnel['national_code'],
                'updated_fields' => array_keys($updated_fields),
                'completion_percentage' => $completion_stats['percentage'],
                'old_department' => $personnel['department_id'],
                'new_department' => $update_data['department_id'] ?? $personnel['department_id']
            )
        );
        
        return array(
            'success' => true,
            'message' => 'اطلاعات پرسنل با موفقیت به‌روزرسانی شد',
            'updated_fields' => $updated_fields,
            'completion_percentage' => $completion_stats['percentage'],
            'has_warnings' => $completion_stats['has_warnings']
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در به‌روزرسانی اطلاعات پرسنل: ' . $wpdb->last_error
    );
}

/**
 * بررسی دسترسی کاربر برای ویرایش پرسنل
 */
function wf_can_user_edit_personnel($user_id, $personnel_id) {
    $user = get_user_by('id', $user_id);
    $personnel = wf_get_personnel($personnel_id);
    
    if (!$user || !$personnel) {
        return array(
            'can_edit' => false,
            'direct_edit' => false,
            'message' => 'دسترسی غیرمجاز'
        );
    }
    
    // ادمین وردپرس
    if (in_array('administrator', $user->roles)) {
        return array(
            'can_edit' => true,
            'direct_edit' => true,
            'message' => ''
        );
    }
    
    // مدیر سازمان
    if (in_array('wf_org_manager', $user->roles)) {
        return array(
            'can_edit' => true,
            'direct_edit' => true,
            'message' => ''
        );
    }
    
    // مدیر اداره
    if (in_array('wf_department_manager', $user->roles)) {
        $user_department_id = get_user_meta($user_id, 'wf_assigned_department', true);
        
        if ($user_department_id == $personnel['department_id']) {
            // مدیر اداره می‌تواند ویرایش کند، اما برای فیلدهای خاص نیاز به تایید دارد
            return array(
                'can_edit' => true,
                'direct_edit' => true,
                'message' => '',
                'requires_approval_for' => array('department_id', 'employment_type', 'status') // فیلدهایی که نیاز به تایید دارند
            );
        }
    }
    
    return array(
        'can_edit' => false,
        'direct_edit' => false,
        'message' => 'شما دسترسی ویرایش این پرسنل را ندارید'
    );
}

/**
 * حذف پرسنل (حذف نرم)
 */
function wf_delete_personnel($personnel_id, $permanent = false, $require_approval = false) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_personnel';
    
    // بررسی وجود پرسنل
    $personnel = wf_get_personnel($personnel_id);
    if (!$personnel) {
        return array(
            'success' => false,
            'message' => 'پرسنل مورد نظر یافت نشد'
        );
    }
    
    // بررسی دسترسی کاربر
    $current_user_id = get_current_user_id();
    $user_can_delete = wf_can_user_delete_personnel($current_user_id, $personnel_id);
    
    // اگر نیاز به تایید باشد یا کاربر مستقیم نمی‌تواند حذف کند
    if ($require_approval || !$user_can_delete['direct_delete']) {
        return wf_create_approval_request(
            'delete_personnel',
            array(
                'personnel_id' => $personnel_id,
                'personnel_data' => $personnel
            ),
            $current_user_id,
            $personnel['department_id'],
            $personnel_id
        );
    }
    
    if ($permanent) {
        // حذف فیزیکی
        $result = $wpdb->delete($table_name, array('id' => $personnel_id));
    } else {
        // حذف نرم
        $result = $wpdb->update(
            $table_name,
            array(
                'is_deleted' => 1,
                'deleted_at' => current_time('mysql'),
                'deleted_by' => $current_user_id,
                'status' => 'inactive',
                'updated_at' => current_time('mysql')
            ),
            array('id' => $personnel_id)
        );
    }
    
    if ($result) {
        // به‌روزرسانی تعداد پرسنل اداره
        wf_update_department_personnel_count($personnel['department_id']);
        
        // ثبت لاگ
        wf_log_user_action(
            $permanent ? 'personnel_permanently_deleted' : 'personnel_deleted',
            ($permanent ? 'پرسنل حذف شد: ' : 'پرسنل غیرفعال شد: ') . $personnel['national_code'],
            array(
                'personnel_id' => $personnel_id,
                'national_code' => $personnel['national_code'],
                'department_id' => $personnel['department_id'],
                'permanent' => $permanent
            )
        );
        
        return array(
            'success' => true,
            'message' => $permanent ? 'پرسنل با موفقیت حذف شد' : 'پرسنل با موفقیت غیرفعال شد'
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در حذف پرسنل: ' . $wpdb->last_error
    );
}

/**
 * بررسی دسترسی کاربر برای حذف پرسنل
 */
function wf_can_user_delete_personnel($user_id, $personnel_id) {
    $user = get_user_by('id', $user_id);
    $personnel = wf_get_personnel($personnel_id);
    
    if (!$user || !$personnel) {
        return array(
            'can_delete' => false,
            'direct_delete' => false,
            'message' => 'دسترسی غیرمجاز'
        );
    }
    
    // ادمین وردپرس
    if (in_array('administrator', $user->roles)) {
        return array(
            'can_delete' => true,
            'direct_delete' => true,
            'message' => ''
        );
    }
    
    // مدیر سازمان
    if (in_array('wf_org_manager', $user->roles)) {
        return array(
            'can_delete' => true,
            'direct_delete' => true,
            'message' => ''
        );
    }
    
    // مدیر اداره
    if (in_array('wf_department_manager', $user->roles)) {
        $user_department_id = get_user_meta($user_id, 'wf_assigned_department', true);
        
        if ($user_department_id == $personnel['department_id']) {
            // مدیر اداره می‌تواند حذف کند اما نیاز به تایید دارد
            return array(
                'can_delete' => true,
                'direct_delete' => false,
                'message' => 'حذف پرسنل نیاز به تایید مدیر سازمان دارد'
            );
        }
    }
    
    return array(
        'can_delete' => false,
        'direct_delete' => false,
        'message' => 'شما دسترسی حذف این پرسنل را ندارید'
    );
}

/**
 * بازیابی پرسنل حذف شده
 */
function wf_restore_personnel($personnel_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_personnel';
    
    // بررسی وجود پرسنل حذف شده
    $personnel = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d AND is_deleted = 1",
        $personnel_id
    ), ARRAY_A);
    
    if (!$personnel) {
        return array(
            'success' => false,
            'message' => 'پرسنل حذف شده یافت نشد'
        );
    }
    
    // بازیابی
    $result = $wpdb->update(
        $table_name,
        array(
            'is_deleted' => 0,
            'deleted_at' => null,
            'deleted_by' => null,
            'status' => 'active',
            'updated_at' => current_time('mysql')
        ),
        array('id' => $personnel_id)
    );
    
    if ($result) {
        // به‌روزرسانی تعداد پرسنل اداره
        wf_update_department_personnel_count($personnel['department_id']);
        
        // ثبت لاگ
        wf_log_user_action(
            'personnel_restored',
            'پرسنل بازیابی شد: ' . $personnel['national_code'],
            array(
                'personnel_id' => $personnel_id,
                'national_code' => $personnel['national_code'],
                'department_id' => $personnel['department_id']
            )
        );
        
        return array(
            'success' => true,
            'message' => 'پرسنل با موفقیت بازیابی شد'
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در بازیابی پرسنل: ' . $wpdb->last_error
    );
}

/**
 * دریافت اطلاعات پرسنل
 */
function wf_get_personnel($personnel_id, $include_deleted = false) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_personnel';
    
    $where = $include_deleted ? "id = %d" : "id = %d AND is_deleted = 0";
    
    $personnel = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE $where",
        $personnel_id
    ), ARRAY_A);
    
    if ($personnel) {
        // تبدیل JSON
        if (!empty($personnel['data'])) {
            $personnel['data'] = json_decode($personnel['data'], true);
        }
        
        if (!empty($personnel['warnings'])) {
            $personnel['warnings'] = json_decode($personnel['warnings'], true);
        }
        
        // تبدیل تاریخ استخدام به شمسی
        if ($personnel['employment_date']) {
            $personnel['employment_date_jalali'] = wf_convert_to_jalali($personnel['employment_date']);
        }
    }
    
    return $personnel;
}

/**
 * دریافت پرسنل بر اساس کد ملی
 */
function wf_get_personnel_by_national_code($national_code) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_personnel';
    
    $personnel = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE national_code = %s AND is_deleted = 0",
        sanitize_text_field($national_code)
    ), ARRAY_A);
    
    if ($personnel && !empty($personnel['data'])) {
        $personnel['data'] = json_decode($personnel['data'], true);
    }
    
    return $personnel;
}

/**
 * دریافت پرسنل یک اداره
 */
function wf_get_department_personnel($department_id, $filters = array(), $pagination = array()) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_personnel';
    
    $where = array("department_id = %d", "is_deleted = 0");
    $params = array($department_id);
    
    // اعمال فیلترها
    if (!empty($filters['status'])) {
        $where[] = "status = %s";
        $params[] = $filters['status'];
    }
    
    if (isset($filters['has_warnings'])) {
        $where[] = "has_warnings = %d";
        $params[] = (int)$filters['has_warnings'];
    }
    
    if (!empty($filters['employment_type'])) {
        $where[] = "employment_type = %s";
        $params[] = $filters['employment_type'];
    }
    
    if (!empty($filters['search'])) {
        $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
        $where[] = "(national_code LIKE %s OR position LIKE %s)";
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if (!empty($filters['completion_min'])) {
        $where[] = "completion_percentage >= %f";
        $params[] = (float)$filters['completion_min'];
    }
    
    if (!empty($filters['completion_max'])) {
        $where[] = "completion_percentage <= %f";
        $params[] = (float)$filters['completion_max'];
    }
    
    $where_clause = implode(' AND ', $where);
    
    // ساخت کوئری اصلی
    $sql = "SELECT * FROM $table_name WHERE $where_clause";
    
    // مرتب‌سازی
    $order_by = 'id DESC';
    if (!empty($filters['order_by'])) {
        $order_by = sanitize_sql_orderby($filters['order_by']);
    }
    $sql .= " ORDER BY $order_by";
    
    // صفحه‌بندی
    if (!empty($pagination['per_page'])) {
        $page = !empty($pagination['page']) ? max(1, (int)$pagination['page']) : 1;
        $offset = ($page - 1) * $pagination['per_page'];
        $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $pagination['per_page'], $offset);
    }
    
    if (!empty($params)) {
        $sql = $wpdb->prepare($sql, $params);
    }
    
    $personnel = $wpdb->get_results($sql, ARRAY_A);
    
    // پردازش داده‌های JSON
    foreach ($personnel as &$person) {
        if (!empty($person['data'])) {
            $person['data'] = json_decode($person['data'], true);
        }
    }
    
    return $personnel;
}

/**
 * جستجوی پیشرفته در پرسنل
 */
function wf_search_personnel($search_params, $pagination = array()) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_personnel';
    $departments_table = $wpdb->prefix . 'wf_departments';
    
    $select = "SELECT p.*, d.name as department_name, d.color as department_color";
    $from = " FROM $table_name p";
    $join = " LEFT JOIN $departments_table d ON p.department_id = d.id";
    $where = array("p.is_deleted = 0");
    $params = array();
    
    // جستجو در فیلدهای اصلی
    if (!empty($search_params['national_code'])) {
        $where[] = "p.national_code LIKE %s";
        $params[] = '%' . $wpdb->esc_like($search_params['national_code']) . '%';
    }
    
    if (!empty($search_params['department_id'])) {
        $where[] = "p.department_id = %d";
        $params[] = (int)$search_params['department_id'];
    }
    
    if (!empty($search_params['department_name'])) {
        $where[] = "d.name LIKE %s";
        $params[] = '%' . $wpdb->esc_like($search_params['department_name']) . '%';
    }
    
    if (!empty($search_params['employment_type'])) {
        $where[] = "p.employment_type = %s";
        $params[] = $search_params['employment_type'];
    }
    
    if (!empty($search_params['status'])) {
        $where[] = "p.status = %s";
        $params[] = $search_params['status'];
    }
    
    if (isset($search_params['has_warnings'])) {
        $where[] = "p.has_warnings = %d";
        $params[] = (int)$search_params['has_warnings'];
    }
    
    // جستجو در فیلدهای داینامیک
    if (!empty($search_params['field_values'])) {
        foreach ($search_params['field_values'] as $field_key => $value) {
            if (!empty($value)) {
                $where[] = "JSON_EXTRACT(p.data, '$.$field_key') LIKE %s";
                $params[] = '%' . $wpdb->esc_like($value) . '%';
            }
        }
    }
    
    // بازه درصد تکمیل
    if (!empty($search_params['completion_min'])) {
        $where[] = "p.completion_percentage >= %f";
        $params[] = (float)$search_params['completion_min'];
    }
    
    if (!empty($search_params['completion_max'])) {
        $where[] = "p.completion_percentage <= %f";
        $params[] = (float)$search_params['completion_max'];
    }
    
    // جستجوی عمومی
    if (!empty($search_params['general_search'])) {
        $search_term = '%' . $wpdb->esc_like($search_params['general_search']) . '%';
        $where[] = "(p.national_code LIKE %s OR p.position LIKE %s OR d.name LIKE %s)";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = implode(' AND ', $where);
    
    // کوئری شمارش برای صفحه‌بندی
    $count_sql = "SELECT COUNT(*) $from $join WHERE $where_clause";
    if (!empty($params)) {
        $count_sql = $wpdb->prepare($count_sql, $params);
    }
    
    $total_count = (int)$wpdb->get_var($count_sql);
    
    // کوئری اصلی
    $sql = "$select $from $join WHERE $where_clause";
    
    // مرتب‌سازی
    $order_by = 'p.id DESC';
    if (!empty($search_params['order_by'])) {
        $order_by = sanitize_sql_orderby($search_params['order_by']);
    }
    $sql .= " ORDER BY $order_by";
    
    // صفحه‌بندی
    if (!empty($pagination['per_page'])) {
        $page = !empty($pagination['page']) ? max(1, (int)$pagination['page']) : 1;
        $offset = ($page - 1) * $pagination['per_page'];
        $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $pagination['per_page'], $offset);
    }
    
    if (!empty($params)) {
        $sql = $wpdb->prepare($sql, $params);
    }
    
    $results = $wpdb->get_results($sql, ARRAY_A);
    
    // پردازش داده‌های JSON
    foreach ($results as &$result) {
        if (!empty($result['data'])) {
            $result['data'] = json_decode($result['data'], true);
        }
    }
    
    return array(
        'results' => $results,
        'total_count' => $total_count,
        'current_page' => $pagination['page'] ?? 1,
        'per_page' => $pagination['per_page'] ?? 50,
        'total_pages' => ceil($total_count / ($pagination['per_page'] ?? 50))
    );
}

/**
 * دریافت آمار پرسنل
 */
function wf_get_personnel_stats($department_id = null) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_personnel';
    
    $where = array("is_deleted = 0");
    $params = array();
    
    if ($department_id) {
        $where[] = "department_id = %d";
        $params[] = (int)$department_id;
    }
    
    $where_clause = implode(' AND ', $where);
    
    $sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
        SUM(CASE WHEN has_warnings = 1 THEN 1 ELSE 0 END) as with_warnings,
        AVG(completion_percentage) as avg_completion,
        MIN(completion_percentage) as min_completion,
        MAX(completion_percentage) as max_completion,
        COUNT(CASE WHEN completion_percentage = 100 THEN 1 END) as fully_completed
    FROM $table_name 
    WHERE $where_clause";
    
    if (!empty($params)) {
        $sql = $wpdb->prepare($sql, $params);
    }
    
    $stats = $wpdb->get_row($sql, ARRAY_A);
    
    // تبدیل به اعداد
    foreach ($stats as $key => $value) {
        if (is_numeric($value)) {
            $stats[$key] = round((float)$value, 2);
        }
    }
    
    // آمار بر اساس نوع استخدام
    $employment_stats_sql = "SELECT 
        employment_type,
        COUNT(*) as count,
        ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage
    FROM $table_name 
    WHERE $where_clause
    GROUP BY employment_type
    ORDER BY count DESC";
    
    if (!empty($params)) {
        $employment_stats_sql = $wpdb->prepare($employment_stats_sql, $params);
    }
    
    $employment_stats = $wpdb->get_results($employment_stats_sql, ARRAY_A);
    
    $stats['employment_types'] = $employment_stats;
    
    return $stats;
}

// ==================== توابع CRUD برای دوره‌ها ====================

/**
 * ایجاد دوره جدید
 */
function wf_create_period($data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_periods';
    
    // اعتبارسنجی داده‌ها
    if (empty($data['title'])) {
        return array(
            'success' => false,
            'message' => 'عنوان دوره الزامی است'
        );
    }
    
    if (empty($data['start_date']) || empty($data['end_date'])) {
        return array(
            'success' => false,
            'message' => 'تاریخ شروع و پایان دوره الزامی است'
        );
    }
    
    // تبدیل تاریخ‌ها به میلادی
    $start_date = wf_convert_to_gregorian($data['start_date']);
    $end_date = wf_convert_to_gregorian($data['end_date']);
    
    if (!$start_date || !$end_date) {
        return array(
            'success' => false,
            'message' => 'فرمت تاریخ‌ها معتبر نیست'
        );
    }
    
    // بررسی منطقی بودن بازه زمانی
    if (strtotime($start_date) > strtotime($end_date)) {
        return array(
            'success' => false,
            'message' => 'تاریخ شروع نمی‌تواند بعد از تاریخ پایان باشد'
        );
    }
    
    // بررسی تداخل با دوره‌های دیگر
    $overlap = wf_check_period_overlap($start_date, $end_date);
    if ($overlap) {
        return array(
            'success' => false,
            'message' => 'این دوره با دوره‌های دیگر تداخل دارد'
        );
    }
    
    // آماده‌سازی داده‌ها
    $period_data = array(
        'title' => sanitize_text_field($data['title']),
        'period_code' => !empty($data['period_code']) ? sanitize_text_field($data['period_code']) : null,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'description' => !empty($data['description']) ? sanitize_textarea_field($data['description']) : null,
        'settings' => !empty($data['settings']) ? json_encode($data['settings'], JSON_UNESCAPED_UNICODE) : null,
        'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 0,
        'created_by' => get_current_user_id(),
        'status' => 'active'
    );
    
    // اگر این دوره فعال شود، سایر دوره‌ها غیرفعال شوند
    if ($period_data['is_active'] == 1) {
        $wpdb->update(
            $table_name,
            array('is_active' => 0),
            array('is_active' => 1)
        );
    }
    
    // درج در دیتابیس
    $result = $wpdb->insert($table_name, $period_data);
    
    if ($result) {
        $period_id = $wpdb->insert_id;
        
        // ثبت لاگ
        wf_log_user_action(
            'period_created',
            'دوره جدید ایجاد شد: ' . $period_data['title'],
            array(
                'period_id' => $period_id,
                'start_date' => $period_data['start_date'],
                'end_date' => $period_data['end_date']
            )
        );
        
        return array(
            'success' => true,
            'message' => 'دوره با موفقیت ایجاد شد',
            'period_id' => $period_id,
            'data' => $period_data
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در ایجاد دوره: ' . $wpdb->last_error
    );
}

/**
 * بررسی تداخل دوره‌ها
 */
function wf_check_period_overlap($start_date, $end_date, $exclude_id = null) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_periods';
    
    $where = array(
        "status = 'active'",
        "((start_date <= %s AND end_date >= %s) OR 
          (start_date <= %s AND end_date >= %s) OR
          (start_date >= %s AND end_date <= %s))"
    );
    
    $params = array($end_date, $start_date, $start_date, $end_date, $start_date, $end_date);
    
    if ($exclude_id) {
        $where[] = "id != %d";
        $params[] = (int)$exclude_id;
    }
    
    $where_clause = implode(' AND ', $where);
    
    $sql = $wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE $where_clause",
        $params
    );
    
    $count = (int)$wpdb->get_var($sql);
    
    return $count > 0;
}

/**
 * به‌روزرسانی دوره
 */
function wf_update_period($period_id, $data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_periods';
    
    // بررسی وجود دوره
    $period = wf_get_period($period_id);
    if (!$period) {
        return array(
            'success' => false,
            'message' => 'دوره مورد نظر یافت نشد'
        );
    }
    
    // آماده‌سازی داده‌های به‌روزرسانی
    $update_data = array();
    $changes = array();
    
    if (isset($data['title'])) {
        $update_data['title'] = sanitize_text_field($data['title']);
        $changes[] = 'title';
    }
    
    if (isset($data['period_code'])) {
        $update_data['period_code'] = sanitize_text_field($data['period_code']);
        $changes[] = 'period_code';
    }
    
    if (isset($data['description'])) {
        $update_data['description'] = sanitize_textarea_field($data['description']);
        $changes[] = 'description';
    }
    
    if (isset($data['settings'])) {
        $update_data['settings'] = json_encode($data['settings'], JSON_UNESCAPED_UNICODE);
        $changes[] = 'settings';
    }
    
    if (isset($data['status'])) {
        $update_data['status'] = sanitize_text_field($data['status']);
        $changes[] = 'status';
    }
    
    // مدیریت تاریخ‌ها
    $start_date_changed = false;
    $end_date_changed = false;
    
    if (isset($data['start_date'])) {
        $start_date = wf_convert_to_gregorian($data['start_date']);
        if ($start_date) {
            $update_data['start_date'] = $start_date;
            $start_date_changed = true;
            $changes[] = 'start_date';
        }
    }
    
    if (isset($data['end_date'])) {
        $end_date = wf_convert_to_gregorian($data['end_date']);
        if ($end_date) {
            $update_data['end_date'] = $end_date;
            $end_date_changed = true;
            $changes[] = 'end_date';
        }
    }
    
    // بررسی منطقی بودن بازه زمانی
    if ($start_date_changed || $end_date_changed) {
        $check_start = $start_date_changed ? $update_data['start_date'] : $period['start_date'];
        $check_end = $end_date_changed ? $update_data['end_date'] : $period['end_date'];
        
        if (strtotime($check_start) > strtotime($check_end)) {
            return array(
                'success' => false,
                'message' => 'تاریخ شروع نمی‌تواند بعد از تاریخ پایان باشد'
            );
        }
        
        // بررسی تداخل (به جز خود دوره)
        if (wf_check_period_overlap($check_start, $check_end, $period_id)) {
            return array(
                'success' => false,
                'message' => 'این دوره با دوره‌های دیگر تداخل دارد'
            );
        }
    }
    
    // مدیریت فعال‌سازی دوره
    if (isset($data['is_active'])) {
        $is_active = (int)$data['is_active'];
        
        if ($is_active == 1 && $period['is_active'] == 0) {
            // غیرفعال کردن سایر دوره‌ها
            $wpdb->update(
                $table_name,
                array('is_active' => 0),
                array('is_active' => 1)
            );
        }
        
        $update_data['is_active'] = $is_active;
        $changes[] = 'is_active';
    }
    
    $update_data['updated_at'] = current_time('mysql');
    
    if (empty($update_data)) {
        return array(
            'success' => false,
            'message' => 'هیچ داده‌ای برای به‌روزرسانی ارسال نشده است'
        );
    }
    
    // به‌روزرسانی در دیتابیس
    $result = $wpdb->update(
        $table_name,
        $update_data,
        array('id' => $period_id)
    );
    
    if ($result !== false) {
        // ثبت لاگ
        wf_log_user_action(
            'period_updated',
            'دوره به‌روزرسانی شد: ' . ($update_data['title'] ?? $period['title']),
            array(
                'period_id' => $period_id,
                'changes' => $changes
            )
        );
        
        return array(
            'success' => true,
            'message' => 'دوره با موفقیت به‌روزرسانی شد'
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در به‌روزرسانی دوره: ' . $wpdb->last_error
    );
}

/**
 * حذف دوره
 */
function wf_delete_period($period_id, $permanent = false) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_periods';
    
    // بررسی وجود دوره
    $period = wf_get_period($period_id);
    if (!$period) {
        return array(
            'success' => false,
            'message' => 'دوره مورد نظر یافت نشد'
        );
    }
    
    // بررسی فعال بودن دوره
    if ($period['is_active'] == 1) {
        return array(
            'success' => false,
            'message' => 'دوره فعال قابل حذف نیست. ابتدا دوره دیگری را فعال کنید'
        );
    }
    
    if ($permanent) {
        // حذف فیزیکی
        $result = $wpdb->delete($table_name, array('id' => $period_id));
    } else {
        // حذف نرم (تغییر وضعیت)
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => 'inactive',
                'updated_at' => current_time('mysql')
            ),
            array('id' => $period_id)
        );
    }
    
    if ($result) {
        // ثبت لاگ
        wf_log_user_action(
            $permanent ? 'period_permanently_deleted' : 'period_deactivated',
            ($permanent ? 'دوره حذف شد: ' : 'دوره غیرفعال شد: ') . $period['title'],
            array(
                'period_id' => $period_id,
                'permanent' => $permanent
            )
        );
        
        return array(
            'success' => true,
            'message' => $permanent ? 'دوره با موفقیت حذف شد' : 'دوره با موفقیت غیرفعال شد'
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در حذف دوره: ' . $wpdb->last_error
    );
}

/**
 * دریافت دوره بر اساس ID
 */
function wf_get_period($period_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_periods';
    
    $period = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d AND status = 'active'",
        $period_id
    ), ARRAY_A);
    
    if ($period && !empty($period['settings'])) {
        $period['settings'] = json_decode($period['settings'], true);
    }
    
    if ($period && $period['start_date']) {
        $period['start_date_jalali'] = wf_convert_to_jalali($period['start_date']);
    }
    
    if ($period && $period['end_date']) {
        $period['end_date_jalali'] = wf_convert_to_jalali($period['end_date']);
    }
    
    return $period;
}

/**
 * دریافت دوره فعال
 */
function wf_get_active_period() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_periods';
    
    $period = $wpdb->get_row(
        "SELECT * FROM $table_name WHERE is_active = 1 AND status = 'active' LIMIT 1",
        ARRAY_A
    );
    
    if ($period && !empty($period['settings'])) {
        $period['settings'] = json_decode($period['settings'], true);
    }
    
    if ($period) {
        $period['start_date_jalali'] = wf_convert_to_jalali($period['start_date']);
        $period['end_date_jalali'] = wf_convert_to_jalali($period['end_date']);
    }
    
    return $period;
}

/**
 * دریافت همه دوره‌ها
 */
function wf_get_all_periods($filters = array()) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_periods';
    
    $where = array("status = 'active'");
    $params = array();
    
    if (isset($filters['is_active'])) {
        $where[] = "is_active = %d";
        $params[] = (int)$filters['is_active'];
    }
    
    if (!empty($filters['year'])) {
        $where[] = "YEAR(start_date) = %d";
        $params[] = (int)$filters['year'];
    }
    
    if (!empty($filters['search'])) {
        $where[] = "(title LIKE %s OR period_code LIKE %s OR description LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = implode(' AND ', $where);
    
    // مرتب‌سازی
    $order_by = 'start_date DESC';
    if (!empty($filters['order_by'])) {
        $order_by = sanitize_sql_orderby($filters['order_by']);
    }
    
    $sql = "SELECT * FROM $table_name WHERE $where_clause ORDER BY $order_by";
    
    if (!empty($params)) {
        $sql = $wpdb->prepare($sql, $params);
    }
    
    $periods = $wpdb->get_results($sql, ARRAY_A);
    
    // پردازش داده‌ها
    foreach ($periods as &$period) {
        if (!empty($period['settings'])) {
            $period['settings'] = json_decode($period['settings'], true);
        }
        
        $period['start_date_jalali'] = wf_convert_to_jalali($period['start_date']);
        $period['end_date_jalali'] = wf_convert_to_jalali($period['end_date']);
    }
    
    return $periods;
}

// ==================== توابع CRUD برای درخواست‌های تایید ====================

/**
 * ایجاد درخواست تایید
 */
function wf_create_approval_request($request_type, $request_data, $requester_id, $department_id = null, $target_id = null, $priority = 'normal') {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_approvals';
    
    // اعتبارسنجی
    $valid_types = array('add_personnel', 'edit_personnel', 'delete_personnel', 'edit_field', 'add_department', 'other');
    if (!in_array($request_type, $valid_types)) {
        return array(
            'success' => false,
            'message' => 'نوع درخواست نامعتبر است'
        );
    }
    
    // آماده‌سازی داده‌ها
    $approval_data = array(
        'request_type' => $request_type,
        'request_data' => json_encode($request_data, JSON_UNESCAPED_UNICODE),
        'requester_id' => $requester_id,
        'department_id' => $department_id,
        'personnel_id' => $request_type == 'edit_personnel' || $request_type == 'delete_personnel' ? $target_id : null,
        'field_id' => $request_type == 'edit_field' ? $target_id : null,
        'priority' => $priority,
        'status' => 'pending',
        'created_at' => current_time('mysql')
    );
    
    // درج در دیتابیس
    $result = $wpdb->insert($table_name, $approval_data);
    
    if ($result) {
        $approval_id = $wpdb->insert_id;
        
        // ثبت لاگ
        wf_log_user_action(
            'approval_request_created',
            'درخواست تایید جدید ایجاد شد: ' . $request_type,
            array(
                'approval_id' => $approval_id,
                'request_type' => $request_type,
                'requester_id' => $requester_id,
                'department_id' => $department_id
            )
        );
        
        // ارسال اعلان به ادمین‌ها
        wf_send_approval_notification($approval_id);
        
        return array(
            'success' => true,
            'message' => 'درخواست شما با موفقیت ثبت شد و در انتظار تایید است',
            'approval_id' => $approval_id
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در ثبت درخواست: ' . $wpdb->last_error
    );
}

/**
 * ارسال اعلان برای درخواست تایید
 */
function wf_send_approval_notification($approval_id) {
    $approval = wf_get_approval_request($approval_id);
    
    if (!$approval) {
        return false;
    }
    
    // دریافت ادمین‌ها
    $admin_users = get_users(array(
        'role' => 'administrator',
        'fields' => array('ID', 'user_email', 'display_name')
    ));
    
    if (empty($admin_users)) {
        return false;
    }
    
    $requester = get_user_by('id', $approval['requester_id']);
    $requester_name = $requester ? $requester->display_name : 'کاربر ناشناس';
    
    $request_types = array(
        'add_personnel' => 'افزودن پرسنل جدید',
        'edit_personnel' => 'ویرایش اطلاعات پرسنل',
        'delete_personnel' => 'حذف پرسنل',
        'edit_field' => 'ویرایش فیلد',
        'add_department' => 'افزودن اداره جدید',
        'other' => 'درخواست دیگر'
    );
    
    $request_type_name = isset($request_types[$approval['request_type']]) ? $request_types[$approval['request_type']] : 'نامشخص';
    
    $subject = 'درخواست تایید جدید - سیستم مدیریت کارکرد پرسنل';
    $message = "
        <div style='font-family: Tahoma, sans-serif; direction: rtl; text-align: right;'>
            <h2 style='color: #1a73e8;'>درخواست تایید جدید</h2>
            <p>یک درخواست تایید جدید در سیستم ثبت شده است:</p>
            
            <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9; width: 150px;'><strong>نوع درخواست:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>{$request_type_name}</td>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9;'><strong>درخواست‌کننده:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>{$requester_name}</td>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9;'><strong>تاریخ درخواست:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>" . wf_convert_to_jalali($approval['created_at']) . "</td>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9;'><strong>اولویت:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>
                        <span style='padding: 5px 10px; border-radius: 3px; color: white; background-color: " . 
                        ($approval['priority'] == 'urgent' ? '#f44336' : 
                         ($approval['priority'] == 'high' ? '#ff9800' : 
                          ($approval['priority'] == 'normal' ? '#4caf50' : '#9e9e9e'))) . 
                        ";'>
                            {$approval['priority']}
                        </span>
                    </td>
                </tr>
            </table>
            
            <p>لطفاً برای بررسی و اقدام به پنل مدیریت مراجعه کنید.</p>
            
            <div style='margin-top: 30px; padding: 15px; background-color: #f5f5f5; border-radius: 5px;'>
                <small>این یک ایمیل خودکار از سیستم مدیریت کارکرد پرسنل است. لطفاً به آن پاسخ ندهید.</small>
            </div>
        </div>
    ";
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    // ارسال به همه ادمین‌ها
    foreach ($admin_users as $admin) {
        wp_mail($admin->user_email, $subject, $message, $headers);
    }
    
    return true;
}

/**
 * بررسی درخواست توسط ادمین
 */
function wf_review_approval_request($approval_id, $action, $admin_notes = '', $response_data = array()) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_approvals';
    
    // بررسی وجود درخواست
    $approval = wf_get_approval_request($approval_id);
    if (!$approval) {
        return array(
            'success' => false,
            'message' => 'درخواست مورد نظر یافت نشد'
        );
    }
    
    // بررسی وضعیت درخواست
    if ($approval['status'] != 'pending') {
        return array(
            'success' => false,
            'message' => 'این درخواست قبلاً بررسی شده است'
        );
    }
    
    // اعتبارسنجی action
    $valid_actions = array('approved', 'rejected', 'needs_revision', 'suspended');
    if (!in_array($action, $valid_actions)) {
        return array(
            'success' => false,
            'message' => 'عملیات نامعتبر است'
        );
    }
    
    // آماده‌سازی داده‌های به‌روزرسانی
    $update_data = array(
        'status' => $action,
        'admin_notes' => sanitize_textarea_field($admin_notes),
        'response_data' => !empty($response_data) ? json_encode($response_data, JSON_UNESCAPED_UNICODE) : null,
        'reviewed_by' => get_current_user_id(),
        'reviewed_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    );
    
    // به‌روزرسانی در دیتابیس
    $result = $wpdb->update(
        $table_name,
        $update_data,
        array('id' => $approval_id)
    );
    
    if ($result) {
        // اجرای اقدام در صورت تأیید
        if ($action == 'approved') {
            wf_execute_approved_request($approval);
        }
        
        // ارسال اعلان به درخواست‌دهنده
        wf_send_approval_response_notification($approval_id, $action);
        
        // ثبت لاگ
        wf_log_user_action(
            'approval_reviewed',
            'درخواست تایید بررسی شد: ' . $approval['request_type'] . ' -> ' . $action,
            array(
                'approval_id' => $approval_id,
                'request_type' => $approval['request_type'],
                'action' => $action,
                'reviewed_by' => get_current_user_id()
            )
        );
        
        return array(
            'success' => true,
            'message' => 'درخواست با موفقیت بررسی شد'
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در بررسی درخواست: ' . $wpdb->last_error
    );
}

/**
 * اجرای درخواست تأیید شده
 */
function wf_execute_approved_request($approval) {
    $request_data = json_decode($approval['request_data'], true);
    
    switch ($approval['request_type']) {
        case 'add_personnel':
            // ایجاد پرسنل جدید
            if (isset($request_data['personnel_data'])) {
                wf_create_personnel($request_data['personnel_data']);
            }
            break;
            
        case 'edit_personnel':
            // ویرایش پرسنل
            if (isset($request_data['personnel_id']) && isset($request_data['changes'])) {
                wf_update_personnel(
                    $request_data['personnel_id'],
                    $request_data['changes'],
                    false // نیاز به تایید ندارد چون قبلاً تأیید شده
                );
            }
            break;
            
        case 'delete_personnel':
            // حذف پرسنل
            if (isset($request_data['personnel_id'])) {
                wf_delete_personnel(
                    $request_data['personnel_id'],
                    false, // حذف نرم
                    false // نیاز به تایید ندارد
                );
            }
            break;
            
        case 'edit_field':
            // ویرایش فیلد
            if (isset($request_data['field_id']) && isset($request_data['changes'])) {
                wf_update_field($request_data['field_id'], $request_data['changes']);
            }
            break;
            
        case 'add_department':
            // ایجاد اداره جدید
            if (isset($request_data['department_data'])) {
                wf_create_department($request_data['department_data']);
            }
            break;
    }
}

/**
 * ارسال اعلان پاسخ به درخواست‌دهنده
 */
function wf_send_approval_response_notification($approval_id, $action) {
    $approval = wf_get_approval_request($approval_id);
    
    if (!$approval) {
        return false;
    }
    
    $requester = get_user_by('id', $approval['requester_id']);
    if (!$requester) {
        return false;
    }
    
    $reviewer = get_user_by('id', $approval['reviewed_by']);
    $reviewer_name = $reviewer ? $reviewer->display_name : 'مدیر سیستم';
    
    $action_names = array(
        'approved' => 'تأیید شد',
        'rejected' => 'رد شد',
        'needs_revision' => 'نیاز به اصلاح دارد',
        'suspended' => 'تعلیق شد'
    );
    
    $action_name = isset($action_names[$action]) ? $action_names[$action] : 'بررسی شد';
    
    $subject = 'نتیجه بررسی درخواست تایید - سیستم مدیریت کارکرد پرسنل';
    $message = "
        <div style='font-family: Tahoma, sans-serif; direction: rtl; text-align: right;'>
            <h2 style='color: " . ($action == 'approved' ? '#4caf50' : ($action == 'rejected' ? '#f44336' : '#ff9800')) . ";'>نتیجه بررسی درخواست تایید</h2>
            <p>درخواست تایید شما توسط مدیر سیستم بررسی شده است:</p>
            
            <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9; width: 150px;'><strong>نتیجه بررسی:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>
                        <span style='padding: 5px 10px; border-radius: 3px; color: white; background-color: " . 
                        ($action == 'approved' ? '#4caf50' : 
                         ($action == 'rejected' ? '#f44336' : 
                          ($action == 'needs_revision' ? '#ff9800' : '#9e9e9e'))) . 
                        ";'>
                            {$action_name}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9;'><strong>تاریخ بررسی:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>" . wf_convert_to_jalali($approval['reviewed_at']) . "</td>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9;'><strong>بررسی‌کننده:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>{$reviewer_name}</td>
                </tr>
            </table>
            
            " . (!empty($approval['admin_notes']) ? "
            <div style='margin: 20px 0; padding: 15px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;'>
                <h4 style='margin-top: 0; color: #856404;'>یادداشت مدیر:</h4>
                <p>{$approval['admin_notes']}</p>
            </div>
            " : "") . "
            
            <div style='margin-top: 30px; padding: 15px; background-color: #f5f5f5; border-radius: 5px;'>
                <small>این یک ایمیل خودکار از سیستم مدیریت کارکرد پرسنل است. لطفاً به آن پاسخ ندهید.</small>
            </div>
        </div>
    ";
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    wp_mail($requester->user_email, $subject, $message, $headers);
    
    return true;
}

/**
 * دریافت درخواست تایید
 */
function wf_get_approval_request($approval_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_approvals';
    
    $approval = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $approval_id
    ), ARRAY_A);
    
    if ($approval) {
        // تبدیل JSON
        if (!empty($approval['request_data'])) {
            $approval['request_data'] = json_decode($approval['request_data'], true);
        }
        
        if (!empty($approval['response_data'])) {
            $approval['response_data'] = json_decode($approval['response_data'], true);
        }
    }
    
    return $approval;
}

/**
 * دریافت درخواست‌های تایید
 */
function wf_get_approval_requests($filters = array()) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_approvals';
    $users_table = $wpdb->users;
    
    $select = "SELECT a.*, u.display_name as requester_name, u.user_email as requester_email";
    $from = " FROM $table_name a";
    $join = " LEFT JOIN $users_table u ON a.requester_id = u.ID";
    $where = array("1=1");
    $params = array();
    
    // اعمال فیلترها
    if (!empty($filters['status'])) {
        $where[] = "a.status = %s";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['request_type'])) {
        $where[] = "a.request_type = %s";
        $params[] = $filters['request_type'];
    }
    
    if (!empty($filters['requester_id'])) {
        $where[] = "a.requester_id = %d";
        $params[] = (int)$filters['requester_id'];
    }
    
    if (!empty($filters['department_id'])) {
        $where[] = "a.department_id = %d";
        $params[] = (int)$filters['department_id'];
    }
    
    if (!empty($filters['priority'])) {
        $where[] = "a.priority = %s";
        $params[] = $filters['priority'];
    }
    
    if (!empty($filters['search'])) {
        $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
        $where[] = "(u.display_name LIKE %s OR u.user_email LIKE %s)";
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if (!empty($filters['date_from'])) {
        $where[] = "a.created_at >= %s";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $where[] = "a.created_at <= %s";
        $params[] = $filters['date_to'];
    }
    
    $where_clause = implode(' AND ', $where);
    
    // مرتب‌سازی
    $order_by = 'a.created_at DESC';
    if (!empty($filters['order_by'])) {
        $order_by = sanitize_sql_orderby($filters['order_by']);
    }
    
    $sql = "$select $from $join WHERE $where_clause ORDER BY $order_by";
    
    // صفحه‌بندی
    if (!empty($filters['per_page'])) {
        $page = !empty($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $offset = ($page - 1) * $filters['per_page'];
        $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $filters['per_page'], $offset);
    }
    
    if (!empty($params)) {
        $sql = $wpdb->prepare($sql, $params);
    }
    
    $approvals = $wpdb->get_results($sql, ARRAY_A);
    
    // پردازش داده‌های JSON
    foreach ($approvals as &$approval) {
        if (!empty($approval['request_data'])) {
            $approval['request_data'] = json_decode($approval['request_data'], true);
        }
        
        if (!empty($approval['response_data'])) {
            $approval['response_data'] = json_decode($approval['response_data'], true);
        }
    }
    
    return $approvals;
}

/**
 * شمارش درخواست‌های تایید
 */
function wf_count_approval_requests($filters = array()) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_approvals';
    
    $where = array("1=1");
    $params = array();
    
    if (!empty($filters['status'])) {
        $where[] = "status = %s";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['request_type'])) {
        $where[] = "request_type = %s";
        $params[] = $filters['request_type'];
    }
    
    if (!empty($filters['requester_id'])) {
        $where[] = "requester_id = %d";
        $params[] = (int)$filters['requester_id'];
    }
    
    $where_clause = implode(' AND ', $where);
    
    $sql = "SELECT COUNT(*) FROM $table_name WHERE $where_clause";
    
    if (!empty($params)) {
        $sql = $wpdb->prepare($sql, $params);
    }
    
    return (int)$wpdb->get_var($sql);
}

// ==================== توابع CRUD برای قالب‌ها ====================

/**
 * ایجاد قالب جدید
 */
function wf_create_template($data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_templates';
    
    // اعتبارسنجی
    if (empty($data['name'])) {
        return array(
            'success' => false,
            'message' => 'نام قالب الزامی است'
        );
    }
    
    if (empty($data['settings'])) {
        return array(
            'success' => false,
            'message' => 'تنظیمات قالب الزامی است'
        );
    }
    
    // آماده‌سازی داده‌ها
    $template_data = array(
        'name' => sanitize_text_field($data['name']),
        'template_type' => !empty($data['template_type']) ? sanitize_text_field($data['template_type']) : 'excel',
        'settings' => json_encode($data['settings'], JSON_UNESCAPED_UNICODE),
        'is_default' => isset($data['is_default']) ? (int)$data['is_default'] : 0,
        'description' => !empty($data['description']) ? sanitize_textarea_field($data['description']) : null,
        'created_by' => get_current_user_id(),
        'status' => !empty($data['status']) ? sanitize_text_field($data['status']) : 'active'
    );
    
    // اگر این قالب به عنوان پیش‌فرض انتخاب شده، سایر قالب‌های پیش‌فرض را غیرفعال کن
    if ($template_data['is_default'] == 1) {
        $wpdb->update(
            $table_name,
            array('is_default' => 0),
            array('is_default' => 1, 'template_type' => $template_data['template_type'])
        );
    }
    
    // درج در دیتابیس
    $result = $wpdb->insert($table_name, $template_data);
    
    if ($result) {
        $template_id = $wpdb->insert_id;
        
        // ثبت لاگ
        wf_log_user_action(
            'template_created',
            'قالب جدید ایجاد شد: ' . $template_data['name'],
            array(
                'template_id' => $template_id,
                'template_type' => $template_data['template_type']
            )
        );
        
        return array(
            'success' => true,
            'message' => 'قالب با موفقیت ایجاد شد',
            'template_id' => $template_id,
            'data' => $template_data
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در ایجاد قالب: ' . $wpdb->last_error
    );
}

/**
 * دریافت قالب پیش‌فرض
 */
function wf_get_default_template($template_type = 'excel') {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_templates';
    
    $template = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE is_default = 1 AND template_type = %s AND status = 'active'",
        $template_type
    ), ARRAY_A);
    
    if ($template && !empty($template['settings'])) {
        $template['settings'] = json_decode($template['settings'], true);
    }
    
    return $template;
}

// ==================== توابع سیستم گزارش‌گیری ====================

/**
 * ایجاد گزارش تجمیعی
 */
function wf_generate_aggregate_report($report_type, $params = array()) {
    global $wpdb;
    
    $report = array(
        'type' => $report_type,
        'generated_at' => current_time('mysql'),
        'data' => array(),
        'stats' => array()
    );
    
    switch ($report_type) {
        case 'department_summary':
            // گزارش خلاصه ادارات
            $report['data'] = $wpdb->get_results("
                SELECT 
                    d.id,
                    d.name,
                    d.code,
                    d.color,
                    d.manager_id,
                    u.display_name as manager_name,
                    COUNT(p.id) as personnel_count,
                    AVG(p.completion_percentage) as avg_completion,
                    SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END) as active_count,
                    SUM(CASE WHEN p.has_warnings = 1 THEN 1 ELSE 0 END) as warning_count,
                    MAX(p.updated_at) as last_activity
                FROM {$wpdb->prefix}wf_departments d
                LEFT JOIN {$wpdb->prefix}wf_personnel p ON d.id = p.department_id AND p.is_deleted = 0
                LEFT JOIN {$wpdb->users} u ON d.manager_id = u.ID
                WHERE d.is_active = 1
                GROUP BY d.id
                ORDER BY d.name
            ", ARRAY_A);
            
            // آمار کلی
            if (!empty($report['data'])) {
                $report['stats']['total_departments'] = count($report['data']);
                $report['stats']['total_personnel'] = array_sum(array_column($report['data'], 'personnel_count'));
                $report['stats']['avg_completion_all'] = round(array_sum(array_column($report['data'], 'avg_completion')) / count($report['data']), 2);
            }
            break;
            
        case 'completion_analysis':
            // تحلیل درصد تکمیل
            $report['data'] = $wpdb->get_results("
                SELECT 
                    CASE 
                        WHEN completion_percentage = 100 THEN 'کامل (100%)'
                        WHEN completion_percentage >= 90 THEN 'عالی (90-99%)'
                        WHEN completion_percentage >= 75 THEN 'خوب (75-89%)'
                        WHEN completion_percentage >= 50 THEN 'متوسط (50-74%)'
                        WHEN completion_percentage > 0 THEN 'ضعیف (1-49%)'
                        ELSE 'بدون اطلاعات (0%)'
                    END as completion_range,
                    COUNT(*) as count,
                    ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage,
                    AVG(completion_percentage) as avg_in_range
                FROM {$wpdb->prefix}wf_personnel 
                WHERE is_deleted = 0
                GROUP BY completion_range
                ORDER BY completion_percentage DESC
            ", ARRAY_A);
            break;
            
        case 'employment_type_distribution':
            // توزیع نوع استخدام
            $report['data'] = $wpdb->get_results("
                SELECT 
                    employment_type,
                    COUNT(*) as count,
                    ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage
                FROM {$wpdb->prefix}wf_personnel 
                WHERE is_deleted = 0
                GROUP BY employment_type
                ORDER BY count DESC
            ", ARRAY_A);
            break;
            
        case 'monthly_trend':
            // روند ماهانه
            $report['data'] = $wpdb->get_results("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    DATE_FORMAT(created_at, '%Y/%m') as month_fa,
                    COUNT(*) as new_personnel,
                    SUM(CASE WHEN has_warnings = 1 THEN 1 ELSE 0 END) as warnings,
                    AVG(completion_percentage) as avg_completion
                FROM {$wpdb->prefix}wf_personnel 
                WHERE is_deleted = 0
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month DESC
                LIMIT 12
            ", ARRAY_A);
            break;
            
        case 'department_comparison':
            // مقایسه ادارات
            $report['data'] = $wpdb->get_results("
                SELECT 
                    d.name as department_name,
                    d.color,
                    COUNT(p.id) as total_personnel,
                    SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END) as active_personnel,
                    AVG(p.completion_percentage) as avg_completion,
                    SUM(CASE WHEN p.has_warnings = 1 THEN 1 ELSE 0 END) as warning_count,
                    ROUND(SUM(CASE WHEN p.has_warnings = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(p.id), 2) as warning_percentage
                FROM {$wpdb->prefix}wf_departments d
                LEFT JOIN {$wpdb->prefix}wf_personnel p ON d.id = p.department_id AND p.is_deleted = 0
                WHERE d.is_active = 1
                GROUP BY d.id
                ORDER BY avg_completion DESC
            ", ARRAY_A);
            break;
    }
    
    return $report;
}

// ==================== توابع سیستم backup ====================

/**
 * ایجاد backup از داده‌ها
 */
function wf_create_backup($backup_type = 'auto') {
    global $wpdb;
    
    $backup_data = array(
        'timestamp' => current_time('mysql'),
        'version' => '1.0.0',
        'tables' => array()
    );
    
    $tables = array(
        'wf_fields',
        'wf_departments',
        'wf_personnel',
        'wf_periods',
        'wf_approvals',
        'wf_templates',
        'wf_settings'
    );
    
    foreach ($tables as $table) {
        $table_name = $wpdb->prefix . $table;
        $data = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
        $backup_data['tables'][$table] = $data;
    }
    
    // ایجاد نام فایل
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "backup_{$timestamp}_{$backup_type}.json";
    
    // مسیر ذخیره‌سازی
    $upload_dir = wp_upload_dir();
    $backup_dir = $upload_dir['basedir'] . '/workforce_backups/';
    
    // ایجاد دایرکتوری اگر وجود ندارد
    if (!file_exists($backup_dir)) {
        wp_mkdir_p($backup_dir);
    }
    
    $filepath = $backup_dir . $filename;
    
    // ذخیره فایل
    $json_data = json_encode($backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $result = file_put_contents($filepath, $json_data);
    
    if ($result !== false) {
        // ذخیره اطلاعات backup در دیتابیس
        $wpdb->insert($wpdb->prefix . 'wf_backups', array(
            'backup_type' => $backup_type,
            'filename' => $filename,
            'filepath' => $filepath,
            'filesize' => filesize($filepath),
            'record_count' => array_sum(array_map('count', $backup_data['tables'])),
            'created_by' => get_current_user_id(),
            'status' => 'success',
            'notes' => 'Backup created successfully'
        ));
        
        $backup_id = $wpdb->insert_id;
        
        // حذف backup های قدیمی (نگهداری 10 backup آخر)
        $backups = $wpdb->get_results("
            SELECT id, filepath 
            FROM {$wpdb->prefix}wf_backups 
            WHERE status = 'success' 
            ORDER BY created_at DESC
        ", ARRAY_A);
        
        if (count($backups) > 10) {
            for ($i = 10; $i < count($backups); $i++) {
                if (file_exists($backups[$i]['filepath'])) {
                    @unlink($backups[$i]['filepath']);
                }
                $wpdb->delete($wpdb->prefix . 'wf_backups', array('id' => $backups[$i]['id']));
            }
        }
        
        // ثبت لاگ
        wf_log_user_action(
            'backup_created',
            'Backup created successfully: ' . $filename,
            array(
                'backup_id' => $backup_id,
                'filename' => $filename,
                'filesize' => filesize($filepath),
                'backup_type' => $backup_type
            )
        );
        
        return array(
            'success' => true,
            'message' => 'Backup created successfully',
            'backup_id' => $backup_id,
            'filename' => $filename,
            'filepath' => $filepath,
            'filesize' => filesize($filepath)
        );
    }
    
    return array(
        'success' => false,
        'message' => 'Failed to create backup file'
    );
}

// ==================== توابع utility ====================

/**
 * ثبت لاگ سیستم
 */
function wf_log_system_action($action, $message, $details = array(), $severity = 'info') {
    global $wpdb;
    
    $log_data = array(
        'log_type' => 'system',
        'user_id' => get_current_user_id(),
        'action' => $action,
        'target_type' => '',
        'target_id' => 0,
        'details' => json_encode(array_merge(
            array('message' => $message),
            $details
        ), JSON_UNESCAPED_UNICODE),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'severity' => $severity,
        'created_at' => current_time('mysql')
    );
    
    return $wpdb->insert($wpdb->prefix . 'wf_logs', $log_data);
}

/**
 * ثبت لاگ کاربر
 */
function wf_log_user_action($action, $message, $details = array()) {
    global $wpdb;
    
    $log_data = array(
        'log_type' => 'user',
        'user_id' => get_current_user_id(),
        'action' => $action,
        'target_type' => '',
        'target_id' => 0,
        'details' => json_encode(array_merge(
            array('message' => $message),
            $details
        ), JSON_UNESCAPED_UNICODE),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'severity' => 'info',
        'created_at' => current_time('mysql')
    );
    
    return $wpdb->insert($wpdb->prefix . 'wf_logs', $log_data);
}

/**
 * دریافت لاگ‌های سیستم
 */
function wf_get_system_logs($filters = array(), $limit = 100) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_logs';
    $users_table = $wpdb->users;
    
    $select = "SELECT l.*, u.display_name as user_name, u.user_email as user_email";
    $from = " FROM $table_name l";
    $join = " LEFT JOIN $users_table u ON l.user_id = u.ID";
    $where = array("1=1");
    $params = array();
    
    if (!empty($filters['log_type'])) {
        $where[] = "l.log_type = %s";
        $params[] = $filters['log_type'];
    }
    
    if (!empty($filters['severity'])) {
        $where[] = "l.severity = %s";
        $params[] = $filters['severity'];
    }
    
    if (!empty($filters['user_id'])) {
        $where[] = "l.user_id = %d";
        $params[] = (int)$filters['user_id'];
    }
    
    if (!empty($filters['action'])) {
        $where[] = "l.action LIKE %s";
        $params[] = '%' . $wpdb->esc_like($filters['action']) . '%';
    }
    
    if (!empty($filters['date_from'])) {
        $where[] = "l.created_at >= %s";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $where[] = "l.created_at <= %s";
        $params[] = $filters['date_to'];
    }
    
    if (!empty($filters['search'])) {
        $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
        $where[] = "(l.details LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s)";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = implode(' AND ', $where);
    
    $sql = "$select $from $join WHERE $where_clause ORDER BY l.created_at DESC LIMIT %d";
    $params[] = $limit;
    
    if (!empty($params)) {
        $sql = $wpdb->prepare($sql, $params);
    }
    
    $logs = $wpdb->get_results($sql, ARRAY_A);
    
    // پردازش داده‌های JSON
    foreach ($logs as &$log) {
        if (!empty($log['details'])) {
            $log['details'] = json_decode($log['details'], true);
        }
    }
    
    return $logs;
}

/**
 * محاسبه آمار کامل سیستم
 */
function wf_calculate_system_stats() {
    global $wpdb;
    
    $stats = array(
        'summary' => array(),
        'departments' => array(),
        'personnel' => array(),
        'periods' => array(),
        'recent_activity' => array()
    );
    
    // آمار کلی
    $stats['summary'] = array(
        'total_departments' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wf_departments WHERE is_active = 1"),
        'total_personnel' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wf_personnel WHERE is_deleted = 0"),
        'active_personnel' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wf_personnel WHERE status = 'active' AND is_deleted = 0"),
        'inactive_personnel' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wf_personnel WHERE status != 'active' AND is_deleted = 0"),
        'departments_without_manager' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wf_departments WHERE manager_id IS NULL AND is_active = 1"),
        'pending_approvals' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wf_approvals WHERE status = 'pending'"),
        'average_completion' => (float)$wpdb->get_var("SELECT AVG(completion_percentage) FROM {$wpdb->prefix}wf_personnel WHERE is_deleted = 0"),
        'personnel_with_warnings' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wf_personnel WHERE has_warnings = 1 AND is_deleted = 0")
    );
    
    // آمار ادارات
    $stats['departments'] = $wpdb->get_results("
        SELECT 
            d.id,
            d.name,
            d.color,
            COUNT(p.id) as personnel_count,
            AVG(p.completion_percentage) as avg_completion
        FROM {$wpdb->prefix}wf_departments d
        LEFT JOIN {$wpdb->prefix}wf_personnel p ON d.id = p.department_id AND p.is_deleted = 0
        WHERE d.is_active = 1
        GROUP BY d.id
        ORDER BY personnel_count DESC
        LIMIT 10
    ", ARRAY_A);
    
    // آمار پرسنل (توزیع درصد تکمیل)
    $stats['personnel']['completion_distribution'] = $wpdb->get_results("
        SELECT 
            CASE 
                WHEN completion_percentage = 100 THEN 'کامل'
                WHEN completion_percentage >= 90 THEN 'عالی'
                WHEN completion_percentage >= 75 THEN 'خوب'
                WHEN completion_percentage >= 50 THEN 'متوسط'
                ELSE 'ضعیف'
            END as level,
            COUNT(*) as count
        FROM {$wpdb->prefix}wf_personnel 
        WHERE is_deleted = 0
        GROUP BY level
        ORDER BY count DESC
    ", ARRAY_A);
    
    // آمار دوره‌ها
    $stats['periods'] = $wpdb->get_results("
        SELECT 
            id,
            title,
            start_date,
            end_date,
            is_active
        FROM {$wpdb->prefix}wf_periods 
        WHERE status = 'active'
        ORDER BY start_date DESC
        LIMIT 5
    ", ARRAY_A);
    
    // فعالیت‌های اخیر
    $stats['recent_activity'] = wf_get_system_logs(array(), 10);
    
    return $stats;
}

// ==================== توابع بهینه‌سازی ====================

/**
 * بهینه‌سازی جداول
 */
function wf_optimize_tables() {
    global $wpdb;
    
    $tables = array(
        'wf_fields',
        'wf_departments',
        'wf_personnel',
        'wf_periods',
        'wf_approvals',
        'wf_templates',
        'wf_logs',
        'wf_settings',
        'wf_backups',
        'wf_user_cards',
        'wf_saved_filters'
    );
    
    foreach ($tables as $table) {
        $wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}{$table}");
    }
    
    // پاکسازی لاگ‌های قدیمی
    $retention_days = 90;
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-$retention_days days"));
    
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}wf_logs WHERE created_at < %s",
        $cutoff_date
    ));
    
    // آنالیز جداول
    foreach ($tables as $table) {
        $wpdb->query("ANALYZE TABLE {$wpdb->prefix}{$table}");
    }
    
    return true;
}

// ==================== init و hooks ====================

/**
 * مقداردهی اولیه هندلر دیتابیس
 */
function wf_database_handler_init() {
    // بررسی و به‌روزرسانی جداول در صورت نیاز
    $current_version = get_option('wf_database_version', '0');
    $plugin_version = defined('WF_VERSION') ? WF_VERSION : '1.0.0';
    
    if (version_compare($current_version, $plugin_version, '<')) {
        wf_create_database_tables();
        update_option('wf_database_version', $plugin_version);
    }
}

// ثبت hook برای فعال‌سازی
register_activation_hook(WF_PLUGIN_FILE, 'wf_create_database_tables');

// ثبت hook‌های زمان‌بندی شده
add_action('wf_daily_maintenance', 'wf_daily_maintenance_tasks');
add_action('wf_weekly_optimization', 'wf_optimize_tables');

/**
 * وظایف روزانه
 */
function wf_daily_maintenance_tasks() {
    // پاکسازی لاگ‌های قدیمی
    $retention_days = get_option('wf_log_retention_days', 90);
    if ($retention_days > 0) {
        global $wpdb;
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-$retention_days days"));
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}wf_logs WHERE created_at < %s",
            $cutoff_date
        ));
    }
    
    // ایجاد backup خودکار
    $auto_backup = get_option('wf_auto_backup_enabled', 1);
    if ($auto_backup) {
        wf_create_backup('auto');
    }
    
    // بررسی دوره‌های منقضی شده
    wf_check_expired_periods();
}

/**
 * بررسی دوره‌های منقضی شده
 */
function wf_check_expired_periods() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_periods';
    
    // یافتن دوره‌های فعالی که تاریخ پایان آنها گذشته
    $expired_periods = $wpdb->get_results("
        SELECT id, title, end_date 
        FROM $table_name 
        WHERE is_active = 1 
        AND status = 'active' 
        AND end_date < CURDATE()
    ", ARRAY_A);
    
    foreach ($expired_periods as $period) {
        // غیرفعال کردن دوره
        $wpdb->update(
            $table_name,
            array('is_active' => 0, 'status' => 'completed', 'updated_at' => current_time('mysql')),
            array('id' => $period['id'])
        );
        
        // ثبت لاگ
        wf_log_system_action(
            'period_expired',
            'دوره منقضی شد: ' . $period['title'],
            array(
                'period_id' => $period['id'],
                'end_date' => $period['end_date']
            ),
            'info'
        );
    }
}

// برنامه‌ریزی cron jobs اگر وجود ندارند
function wf_schedule_cron_jobs() {
    if (!wp_next_scheduled('wf_daily_maintenance')) {
        wp_schedule_event(time(), 'daily', 'wf_daily_maintenance');
    }
    
    if (!wp_next_scheduled('wf_weekly_optimization')) {
        wp_schedule_event(time(), 'weekly', 'wf_weekly_optimization');
    }
}

add_action('init', 'wf_schedule_cron_jobs');

// ==================== فیلترهای وردپرس ====================

/**
 * افزودن نقش‌های سفارشی
 */
function wf_add_custom_roles() {
    // نقش مدیر سازمان
    add_role('wf_org_manager', 'مدیر سازمان', array(
        'read' => true,
        'wf_manage_organization' => true,
        'wf_view_all_departments' => true,
        'wf_manage_all_personnel' => true,
        'wf_generate_reports' => true,
        'wf_export_data' => true
    ));
    
    // نقش مدیر اداره
    add_role('wf_department_manager', 'مدیر اداره', array(
        'read' => true,
        'wf_manage_department' => true,
        'wf_view_department_personnel' => true,
        'wf_edit_department_personnel' => true,
        'wf_request_changes' => true,
        'wf_view_reports' => true
    ));
}

register_activation_hook(WF_PLUGIN_FILE, 'wf_add_custom_roles');

/**
 * حذف نقش‌های سفارشی هنگام غیرفعال‌سازی
 */
function wf_remove_custom_roles() {
    remove_role('wf_org_manager');
    remove_role('wf_department_manager');
}

register_deactivation_hook(WF_PLUGIN_FILE, 'wf_remove_custom_roles');

// ==================== API Endpoints ====================

/**
 * ثبت REST API endpoints
 */
function wf_register_rest_routes() {
    // لیست پرسنل
    register_rest_route('workforce/v1', '/personnel', array(
        'methods' => 'GET',
        'callback' => 'wf_rest_get_personnel',
        'permission_callback' => function () {
            return current_user_can('read');
        }
    ));
    
    // آمار سیستم
    register_rest_route('workforce/v1', '/stats', array(
        'methods' => 'GET',
        'callback' => 'wf_rest_get_stats',
        'permission_callback' => function () {
            return current_user_can('read');
        }
    ));
    
    // گزارش‌ها
    register_rest_route('workforce/v1', '/reports/(?P<type>[a-z_]+)', array(
        'methods' => 'GET',
        'callback' => 'wf_rest_get_report',
        'permission_callback' => function () {
            return current_user_can('wf_generate_reports');
        }
    ));
}

add_action('rest_api_init', 'wf_register_rest_routes');

/**
 * REST API: دریافت پرسنل
 */
function wf_rest_get_personnel(WP_REST_Request $request) {
    $params = $request->get_params();
    $filters = array();
    
    if (!empty($params['department_id'])) {
        $filters['department_id'] = (int)$params['department_id'];
    }
    
    if (!empty($params['search'])) {
        $filters['search'] = sanitize_text_field($params['search']);
    }
    
    $pagination = array(
        'per_page' => !empty($params['per_page']) ? (int)$params['per_page'] : 50,
        'page' => !empty($params['page']) ? (int)$params['page'] : 1
    );
    
    $result = wf_search_personnel($filters, $pagination);
    
    return new WP_REST_Response($result, 200);
}

/**
 * REST API: دریافت آمار
 */
function wf_rest_get_stats(WP_REST_Request $request) {
    $params = $request->get_params();
    $department_id = !empty($params['department_id']) ? (int)$params['department_id'] : null;
    
    $stats = wf_get_personnel_stats($department_id);
    
    return new WP_REST_Response(array(
        'success' => true,
        'data' => $stats
    ), 200);
}

/**
 * REST API: دریافت گزارش
 */
function wf_rest_get_report(WP_REST_Request $request) {
    $type = $request->get_param('type');
    $params = $request->get_params();
    
    $valid_types = array(
        'department_summary',
        'completion_analysis',
        'employment_type_distribution',
        'monthly_trend',
        'department_comparison'
    );
    
    if (!in_array($type, $valid_types)) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'نوع گزارش نامعتبر است'
        ), 400);
    }
    
    $report = wf_generate_aggregate_report($type, $params);
    
    return new WP_REST_Response(array(
        'success' => true,
        'data' => $report
    ), 200);
}

// ==================== توابع انتقال داده ====================

/**
 * وارد کردن داده از اکسل
 */
function wf_import_from_excel($file_path, $options = array()) {
    if (!file_exists($file_path)) {
        return array(
            'success' => false,
            'message' => 'فایل پیدا نشد'
        );
    }
    
    require_once ABSPATH . 'wp-admin/includes/file.php';
    
    // خواندن فایل اکسل (فرض بر استفاده از PHPExcel یا PhpSpreadsheet)
    // این بخش نیاز به نصب کتابخانه دارد
    
    $result = array(
        'success' => false,
        'message' => 'وارد کردن از اکسل هنوز پیاده‌سازی نشده است',
        'stats' => array(
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => array()
        )
    );
    
    return $result;
}

/**
 * انتقال داده از سیستم قدیمی
 */
function wf_migrate_from_old_system($old_data) {
    global $wpdb;
    
    $results = array(
        'departments' => array('success' => 0, 'failed' => 0),
        'personnel' => array('success' => 0, 'failed' => 0),
        'errors' => array()
    );
    
    // انتقال ادارات
    if (!empty($old_data['departments'])) {
        foreach ($old_data['departments'] as $old_dept) {
            try {
                $dept_data = array(
                    'name' => $old_dept['name'],
                    'code' => !empty($old_dept['code']) ? $old_dept['code'] : null,
                    'description' => !empty($old_dept['description']) ? $old_dept['description'] : null,
                    'created_by' => get_current_user_id()
                );
                
                $result = wf_create_department($dept_data);
                
                if ($result['success']) {
                    $results['departments']['success']++;
                    $old_dept['new_id'] = $result['department_id'];
                } else {
                    $results['departments']['failed']++;
                    $results['errors'][] = 'خطا در ایجاد اداره ' . $old_dept['name'] . ': ' . $result['message'];
                }
            } catch (Exception $e) {
                $results['departments']['failed']++;
                $results['errors'][] = 'خطا در ایجاد اداره ' . $old_dept['name'] . ': ' . $e->getMessage();
            }
        }
    }
    
    // انتقال پرسنل
    if (!empty($old_data['personnel'])) {
        foreach ($old_data['personnel'] as $old_person) {
            try {
                $person_data = array(
                    'national_code' => $old_person['national_code'],
                    'department_id' => $old_person['department_id'],
                    'employment_date' => !empty($old_person['employment_date']) ? $old_person['employment_date'] : null,
                    'employment_type' => !empty($old_person['employment_type']) ? $old_person['employment_type'] : 'permanent',
                    'position' => !empty($old_person['position']) ? $old_person['position'] : null,
                    'status' => !empty($old_person['status']) ? $old_person['status'] : 'active',
                    'created_by' => get_current_user_id()
                );
                
                // اضافه کردن فیلدهای داینامیک
                if (!empty($old_person['fields'])) {
                    foreach ($old_person['fields'] as $field_key => $field_value) {
                        $person_data[$field_key] = $field_value;
                    }
                }
                
                $result = wf_create_personnel($person_data);
                
                if ($result['success']) {
                    $results['personnel']['success']++;
                } else {
                    $results['personnel']['failed']++;
                    $results['errors'][] = 'خطا در ایجاد پرسنل ' . $old_person['national_code'] . ': ' . $result['message'];
                }
            } catch (Exception $e) {
                $results['personnel']['failed']++;
                $results['errors'][] = 'خطا در ایجاد پرسنل ' . $old_person['national_code'] . ': ' . $e->getMessage();
            }
        }
    }
    
    // ثبت لاگ انتقال
    wf_log_system_action(
        'data_migrated',
        'داده‌ها از سیستم قدیمی انتقال یافتند',
        $results,
        $results['errors'] ? 'warning' : 'info'
    );
    
    return array(
        'success' => true,
        'message' => 'انتقال داده‌ها کامل شد',
        'results' => $results
    );
}

// ==================== تابع main ====================

/**
 * تابع اصلی برای تست و دیباگ
 */
function wf_database_test() {
    global $wpdb;
    
    $tests = array();
    
    // تست اتصال به دیتابیس
    $tests['database_connection'] = $wpdb->check_connection();
    
    // تست وجود جداول
    $tables = array(
        'wf_fields',
        'wf_departments',
        'wf_personnel',
        'wf_periods',
        'wf_approvals',
        'wf_templates',
        'wf_logs',
        'wf_settings'
    );
    
    foreach ($tables as $table) {
        $table_name = $wpdb->prefix . $table;
        $tests['table_' . $table] = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    }
    
    // تست توابع CRUD
    try {
        // تست ایجاد فیلد
        $test_field = array(
            'field_key' => 'test_field_' . time(),
            'field_name' => 'فیلد تستی',
            'field_type' => 'text',
            'is_required' => 0
        );
        
        $create_result = wf_create_field($test_field);
        $tests['create_field'] = $create_result['success'];
        
        if ($create_result['success']) {
            $field_id = $create_result['field_id'];
            
            // تست خواندن فیلد
            $field = wf_get_field($field_id);
            $tests['read_field'] = !empty($field);
            
            // تست به‌روزرسانی فیلد
            $update_result = wf_update_field($field_id, array('field_name' => 'فیلد تستی ویرایش شده'));
            $tests['update_field'] = $update_result['success'];
            
            // تست حذف فیلد
            $delete_result = wf_delete_field($field_id);
            $tests['delete_field'] = $delete_result['success'];
        }
        
    } catch (Exception $e) {
        $tests['crud_test'] = false;
        $tests['crud_error'] = $e->getMessage();
    }
    
    // تست آمار سیستم
    $stats = wf_calculate_system_stats();
    $tests['system_stats'] = !empty($stats['summary']);
    
    return array(
        'success' => !in_array(false, $tests, true),
        'tests' => $tests,
        'stats' => $stats['summary'] ?? array()
    );
}

// ==================== پایان فایل ====================

/**
 * تابع کمکی برای دیباگ
 */
if (!function_exists('wf_debug_log')) {
    function wf_debug_log($message, $data = null) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WF Debug] ' . $message);
            if ($data) {
                error_log('[WF Data] ' . print_r($data, true));
            }
        }
    }
}

/**
 * بررسی سلامت سیستم
 */
function wf_check_system_health() {
    $health = array(
        'database' => array(),
        'files' => array(),
        'permissions' => array(),
        'plugins' => array(),
        'overall' => 'healthy'
    );
    
    // بررسی جداول دیتابیس
    global $wpdb;
    $required_tables = array(
        'wf_fields',
        'wf_departments', 
        'wf_personnel',
        'wf_periods',
        'wf_approvals'
    );
    
    foreach ($required_tables as $table) {
        $table_name = $wpdb->prefix . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        $health['database'][$table] = $exists ? 'ok' : 'missing';
    }
    
    // بررسی مجوزهای فایل
    $upload_dir = wp_upload_dir();
    $backup_dir = $upload_dir['basedir'] . '/workforce_backups/';
    
    $health['files']['upload_dir'] = is_writable($upload_dir['basedir']) ? 'writable' : 'not_writable';
    $health['files']['backup_dir'] = file_exists($backup_dir) ? (is_writable($backup_dir) ? 'writable' : 'not_writable') : 'not_exists';
    
    // بررسی وابستگی‌ها
    $health['plugins']['php_version'] = version_compare(PHP_VERSION, '7.4', '>=') ? 'ok' : 'outdated';
    $health['plugins']['wordpress_version'] = version_compare(get_bloginfo('version'), '5.6', '>=') ? 'ok' : 'outdated';
    
    // تعیین وضعیت کلی
    $all_ok = true;
    foreach ($health['database'] as $status) {
        if ($status != 'ok') $all_ok = false;
    }
    
    $health['overall'] = $all_ok ? 'healthy' : 'issues';
    
    return $health;
}

// اضافه کردن action برای بررسی سلامت
add_action('wp_ajax_wf_check_health', 'wf_ajax_check_health');

function wf_ajax_check_health() {
    if (!current_user_can('manage_options')) {
        wp_die('دسترسی غیرمجاز');
    }
    
    $health = wf_check_system_health();
    
    wp_send_json(array(
        'success' => true,
        'data' => $health
    ));
}

// ==================== پشتیبانی چندزبانه ====================

/**
 * ترجمه پیام‌های سیستم
 */
function wf_translate($text, $domain = 'workforce') {
    if (function_exists('translate')) {
        return translate($text, $domain);
    }
    return $text;
}

/**
 * بارگذاری فایل ترجمه
 */
function wf_load_textdomain() {
    load_plugin_textdomain(
        'workforce',
        false,
        dirname(plugin_basename(WF_PLUGIN_FILE)) . '/languages/'
    );
}

add_action('plugins_loaded', 'wf_load_textdomain');

?>
