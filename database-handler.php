<?php
/**
 * هندلر پایگاه داده - سیستم مدیریت کارکرد پرسنل
 * مدیریت تمام جداول و عملیات دیتابیس
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
    
    $charset_collate = $wpdb->get_charset_collate();
    $table_prefix = $wpdb->prefix . 'wf_';
    
    // جدول فیلدها
    $sql1 = "CREATE TABLE IF NOT EXISTS {$table_prefix}fields (
        id INT(11) NOT NULL AUTO_INCREMENT,
        field_name VARCHAR(255) NOT NULL,
        field_key VARCHAR(100) NOT NULL UNIQUE,
        field_type ENUM('text', 'number', 'decimal', 'date', 'time', 'datetime', 'select', 'checkbox') DEFAULT 'text',
        field_options TEXT,
        is_required TINYINT(1) DEFAULT 0,
        is_locked TINYINT(1) DEFAULT 0,
        is_monitoring TINYINT(1) DEFAULT 0,
        is_key TINYINT(1) DEFAULT 0,
        display_order INT(5) DEFAULT 0,
        validation_rules TEXT,
        description TEXT,
        status ENUM('active', 'inactive', 'deleted') DEFAULT 'active',
        created_by INT(11),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_field_key (field_key),
        INDEX idx_status (status),
        INDEX idx_display_order (display_order)
    ) $charset_collate;";
    
    // جدول ادارات
    $sql2 = "CREATE TABLE IF NOT EXISTS {$table_prefix}departments (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        code VARCHAR(50) UNIQUE,
        manager_id INT(11),
        parent_id INT(11) DEFAULT NULL,
        color VARCHAR(7) DEFAULT '#1a73e8',
        description TEXT,
        settings TEXT,
        personnel_count INT(11) DEFAULT 0,
        completion_percentage DECIMAL(5,2) DEFAULT 0.00,
        last_activity DATETIME,
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        created_by INT(11),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_manager (manager_id),
        INDEX idx_parent (parent_id),
        INDEX idx_status (status),
        INDEX idx_code (code),
        FOREIGN KEY (manager_id) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL,
        FOREIGN KEY (parent_id) REFERENCES {$table_prefix}departments(id) ON DELETE CASCADE
    ) $charset_collate;";
    
    // جدول پرسنل
    $sql3 = "CREATE TABLE IF NOT EXISTS {$table_prefix}personnel (
        id INT(11) NOT NULL AUTO_INCREMENT,
        national_code VARCHAR(10) NOT NULL UNIQUE,
        department_id INT(11) NOT NULL,
        employment_date DATE,
        employment_type ENUM('permanent', 'contractual', 'temporary', 'project') DEFAULT 'permanent',
        position VARCHAR(255),
        level VARCHAR(100),
        status ENUM('active', 'inactive', 'suspended', 'retired', 'resigned') DEFAULT 'active',
        profile_image VARCHAR(500),
        data JSON,
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
        INDEX idx_employment_date (employment_date),
        INDEX idx_completion (completion_percentage),
        INDEX idx_has_warnings (has_warnings),
        INDEX idx_is_deleted (is_deleted),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (department_id) REFERENCES {$table_prefix}departments(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL,
        FOREIGN KEY (last_modified_by) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL
    ) $charset_collate;";
    
    // جدول دوره‌های کاری
    $sql4 = "CREATE TABLE IF NOT EXISTS {$table_prefix}periods (
        id INT(11) NOT NULL AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        period_code VARCHAR(50) UNIQUE,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        description TEXT,
        settings TEXT,
        is_active TINYINT(1) DEFAULT 0,
        status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
        created_by INT(11),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_period_code (period_code),
        INDEX idx_dates (start_date, end_date),
        INDEX idx_is_active (is_active),
        INDEX idx_status (status)
    ) $charset_collate;";
    
    // جدول درخواست‌های تایید
    $sql5 = "CREATE TABLE IF NOT EXISTS {$table_prefix}approvals (
        id INT(11) NOT NULL AUTO_INCREMENT,
        request_type ENUM('add_personnel', 'edit_personnel', 'delete_personnel', 'edit_field', 'add_department', 'other') NOT NULL,
        request_data JSON NOT NULL,
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
        response_data JSON,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_request_type (request_type),
        INDEX idx_requester (requester_id),
        INDEX idx_department (department_id),
        INDEX idx_personnel (personnel_id),
        INDEX idx_status (status),
        INDEX idx_priority (priority),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (requester_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
        FOREIGN KEY (department_id) REFERENCES {$table_prefix}departments(id) ON DELETE CASCADE,
        FOREIGN KEY (personnel_id) REFERENCES {$table_prefix}personnel(id) ON DELETE CASCADE,
        FOREIGN KEY (field_id) REFERENCES {$table_prefix}fields(id) ON DELETE CASCADE,
        FOREIGN KEY (reviewed_by) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL
    ) $charset_collate;";
    
    // جدول قالب‌های اکسل
    $sql6 = "CREATE TABLE IF NOT EXISTS {$table_prefix}templates (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        template_type ENUM('excel', 'report', 'form') DEFAULT 'excel',
        settings JSON NOT NULL,
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
        FOREIGN KEY (created_by) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL
    ) $charset_collate;";
    
    // جدول لاگ‌های سیستم
    $sql7 = "CREATE TABLE IF NOT EXISTS {$table_prefix}logs (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        log_type ENUM('system', 'user', 'error', 'security', 'export', 'import') DEFAULT 'system',
        user_id INT(11),
        action VARCHAR(100) NOT NULL,
        target_type VARCHAR(50),
        target_id INT(11),
        details JSON,
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
        INDEX idx_created_at (created_at),
        FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL
    ) $charset_collate;";
    
    // جدول تنظیمات
    $sql8 = "CREATE TABLE IF NOT EXISTS {$table_prefix}settings (
        id INT(11) NOT NULL AUTO_INCREMENT,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value LONGTEXT,
        setting_type ENUM('string', 'number', 'boolean', 'array', 'object') DEFAULT 'string',
        category VARCHAR(50),
        description TEXT,
        is_public TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_setting_key (setting_key),
        INDEX idx_category (category),
        INDEX idx_is_public (is_public)
    ) $charset_collate;";
    
    // جدول backup‌ها
    $sql9 = "CREATE TABLE IF NOT EXISTS {$table_prefix}backups (
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
        FOREIGN KEY (created_by) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL
    ) $charset_collate;";
    
    // جدول کارت‌های مانیتورینگ کاربر
    $sql10 = "CREATE TABLE IF NOT EXISTS {$table_prefix}user_cards (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        card_name VARCHAR(255),
        field_id INT(11),
        card_type ENUM('sum', 'avg', 'count', 'min', 'max', 'custom') DEFAULT 'count',
        card_color VARCHAR(7) DEFAULT '#1a73e8',
        card_icon VARCHAR(100),
        card_order INT(5) DEFAULT 0,
        filter_conditions JSON,
        refresh_interval INT(11) DEFAULT 300,
        last_refresh DATETIME,
        is_active TINYINT(1) DEFAULT 1,
        settings JSON,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_user_card (user_id, field_id, card_type),
        INDEX idx_user_id (user_id),
        INDEX idx_field_id (field_id),
        INDEX idx_is_active (is_active),
        INDEX idx_card_order (card_order),
        FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
        FOREIGN KEY (field_id) REFERENCES {$table_prefix}fields(id) ON DELETE CASCADE
    ) $charset_collate;";
    
    // جدول فیلترهای ذخیره شده
    $sql11 = "CREATE TABLE IF NOT EXISTS {$table_prefix}saved_filters (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        filter_name VARCHAR(255),
        filter_type ENUM('global', 'department', 'personnel') DEFAULT 'global',
        filter_conditions JSON NOT NULL,
        is_public TINYINT(1) DEFAULT 0,
        usage_count INT(11) DEFAULT 0,
        last_used DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_user_id (user_id),
        INDEX idx_filter_type (filter_type),
        INDEX idx_is_public (is_public),
        FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    $queries = array($sql1, $sql2, $sql3, $sql4, $sql5, $sql6, $sql7, $sql8, $sql9, $sql10, $sql11);
    
    foreach ($queries as $sql) {
        dbDelta($sql);
    }
    
    // ایجاد ایندکس‌های اضافی برای بهینه‌سازی
    wf_create_additional_indexes();
    
    // افزودن داده‌های پیش‌فرض
    wf_add_default_data();
    
    // ثبت لاگ
    wf_log_system_action('database_tables_created', 'جدول‌های دیتابیس ایجاد شدند');
}

/**
 * ایجاد ایندکس‌های اضافی
 */
function wf_create_additional_indexes() {
    global $wpdb;
    
    $indexes = array(
        "CREATE INDEX idx_personnel_dept_status ON {$wpdb->prefix}wf_personnel (department_id, status, is_deleted)",
        "CREATE INDEX idx_approvals_status_date ON {$wpdb->prefix}wf_approvals (status, created_at)",
        "CREATE INDEX idx_logs_type_date ON {$wpdb->prefix}wf_logs (log_type, created_at)",
        "CREATE INDEX idx_departments_manager_status ON {$wpdb->prefix}wf_departments (manager_id, status)",
        "CREATE INDEX idx_personnel_completion_warnings ON {$wpdb->prefix}wf_personnel (completion_percentage, has_warnings)",
    );
    
    foreach ($indexes as $index_sql) {
        $wpdb->query($index_sql);
    }
}

/**
 * افزودن داده‌های پیش‌فرض
 */
function wf_add_default_data() {
    global $wpdb;
    
    // بررسی وجود داده‌های پیش‌فرض
    $has_data = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wf_settings");
    
    if ($has_data == 0) {
        // تنظیمات پیش‌فرض
        $default_settings = array(
            array(
                'setting_key' => 'system_version',
                'setting_value' => '1.0.0',
                'setting_type' => 'string',
                'category' => 'system'
            ),
            array(
                'setting_key' => 'max_export_records',
                'setting_value' => '10000',
                'setting_type' => 'number',
                'category' => 'export'
            ),
            array(
                'setting_key' => 'backup_enabled',
                'setting_value' => '1',
                'setting_type' => 'boolean',
                'category' => 'backup'
            ),
            array(
                'setting_key' => 'default_date_format',
                'setting_value' => 'Y/m/d',
                'setting_type' => 'string',
                'category' => 'display'
            ),
            array(
                'setting_key' => 'items_per_page',
                'setting_value' => '100',
                'setting_type' => 'number',
                'category' => 'display'
            ),
            array(
                'setting_key' => 'auto_refresh_interval',
                'setting_value' => '300',
                'setting_type' => 'number',
                'category' => 'performance'
            ),
            array(
                'setting_key' => 'max_upload_size',
                'setting_value' => '10485760',
                'setting_type' => 'number',
                'category' => 'import'
            ),
            array(
                'setting_key' => 'excel_template_default',
                'setting_value' => '1',
                'setting_type' => 'number',
                'category' => 'export'
            ),
            array(
                'setting_key' => 'email_notifications',
                'setting_value' => '1',
                'setting_type' => 'boolean',
                'category' => 'notifications'
            ),
            array(
                'setting_key' => 'session_timeout',
                'setting_value' => '3600',
                'setting_type' => 'number',
                'category' => 'security'
            ),
            array(
                'setting_key' => 'require_strong_password',
                'setting_value' => '1',
                'setting_type' => 'boolean',
                'category' => 'security'
            ),
            array(
                'setting_key' => 'two_factor_auth',
                'setting_value' => '0',
                'setting_type' => 'boolean',
                'category' => 'security'
            ),
            array(
                'setting_key' => 'maintenance_mode',
                'setting_value' => '0',
                'setting_type' => 'boolean',
                'category' => 'system'
            ),
            array(
                'setting_key' => 'debug_mode',
                'setting_value' => '0',
                'setting_type' => 'boolean',
                'category' => 'system'
            ),
            array(
                'setting_key' => 'log_retention_days',
                'setting_value' => '90',
                'setting_type' => 'number',
                'category' => 'logs'
            ),
            array(
                'setting_key' => 'backup_retention_days',
                'setting_value' => '365',
                'setting_type' => 'number',
                'category' => 'backup'
            ),
            array(
                'setting_key' => 'max_login_attempts',
                'setting_value' => '5',
                'setting_type' => 'number',
                'category' => 'security'
            ),
            array(
                'setting_key' => 'password_expiry_days',
                'setting_value' => '90',
                'setting_type' => 'number',
                'category' => 'security'
            ),
            array(
                'setting_key' => 'default_language',
                'setting_value' => 'fa_IR',
                'setting_type' => 'string',
                'category' => 'display'
            ),
            array(
                'setting_key' => 'timezone',
                'setting_value' => 'Asia/Tehran',
                'setting_type' => 'string',
                'category' => 'system'
            ),
            array(
                'setting_key' => 'currency',
                'setting_value' => 'IRR',
                'setting_type' => 'string',
                'category' => 'display'
            ),
            array(
                'setting_key' => 'decimal_separator',
                'setting_value' => '.',
                'setting_type' => 'string',
                'category' => 'display'
            ),
            array(
                'setting_key' => 'thousands_separator',
                'setting_value' => ',',
                'setting_type' => 'string',
                'category' => 'display'
            ),
            array(
                'setting_key' => 'date_format',
                'setting_value' => 'Y/m/d',
                'setting_type' => 'string',
                'category' => 'display'
            ),
            array(
                'setting_key' => 'time_format',
                'setting_value' => 'H:i',
                'setting_type' => 'string',
                'category' => 'display'
            ),
            array(
                'setting_key' => 'week_start_day',
                'setting_value' => '6',
                'setting_type' => 'number',
                'category' => 'display'
            ),
            array(
                'setting_key' => 'first_month_of_year',
                'setting_value' => '1',
                'setting_type' => 'number',
                'category' => 'display'
            )
        );
        
        foreach ($default_settings as $setting) {
            $wpdb->insert("{$wpdb->prefix}wf_settings", $setting);
        }
        
        // قالب پیش‌فرض اکسل
        $default_template = array(
            'name' => 'قالب پیش‌فرض گزارش',
            'template_type' => 'excel',
            'settings' => json_encode(array(
                'header' => array(
                    'bg_color' => '#1a73e8',
                    'font_color' => '#ffffff',
                    'font_size' => 12,
                    'font_bold' => true,
                    'alignment' => 'center'
                ),
                'data' => array(
                    'even_row_color' => '#f8f9fa',
                    'odd_row_color' => '#ffffff',
                    'font_color' => '#202124',
                    'font_size' => 10,
                    'alignment' => 'right'
                ),
                'borders' => array(
                    'style' => 'thin',
                    'color' => '#dadce0'
                ),
                'columns' => array(
                    'auto_width' => true,
                    'wrap_text' => true
                )
            )),
            'is_default' => 1,
            'description' => 'قالب پیش‌فرض برای خروجی اکسل',
            'created_by' => 1,
            'status' => 'active'
        );
        
        $wpdb->insert("{$wpdb->prefix}wf_templates", $default_template);
        
        // دوره پیش‌فرض
        $current_month = date('Y-m');
        $default_period = array(
            'title' => 'دوره ' . wf_get_jalali_month_name(date('n')) . ' ' . date('Y'),
            'period_code' => 'PERIOD_' . date('Ym'),
            'start_date' => date('Y-m-01'),
            'end_date' => date('Y-m-t'),
            'description' => 'دوره کاری پیش‌فرض',
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
    $validation = wf_validate_field_data($data);
    if (!$validation['valid']) {
        return array(
            'success' => false,
            'message' => $validation['message'],
            'errors' => $validation['errors']
        );
    }
    
    // آماده‌سازی داده‌ها
    $field_data = array(
        'field_name' => sanitize_text_field($data['field_name']),
        'field_key' => sanitize_key($data['field_key']),
        'field_type' => sanitize_text_field($data['field_type']),
        'field_options' => isset($data['field_options']) ? wp_json_encode($data['field_options']) : null,
        'is_required' => isset($data['is_required']) ? (int)$data['is_required'] : 0,
        'is_locked' => isset($data['is_locked']) ? (int)$data['is_locked'] : 0,
        'is_monitoring' => isset($data['is_monitoring']) ? (int)$data['is_monitoring'] : 0,
        'is_key' => isset($data['is_key']) ? (int)$data['is_key'] : 0,
        'display_order' => isset($data['display_order']) ? (int)$data['display_order'] : 0,
        'validation_rules' => isset($data['validation_rules']) ? wp_json_encode($data['validation_rules']) : null,
        'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : null,
        'created_by' => get_current_user_id(),
        'status' => 'active'
    );
    
    // بررسی تکراری نبودن field_key
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE field_key = %s AND status != 'deleted'",
        $field_data['field_key']
    ));
    
    if ($exists > 0) {
        return array(
            'success' => false,
            'message' => 'کلید فیلد تکراری است'
        );
    }
    
    // درج در دیتابیس
    $result = $wpdb->insert($table_name, $field_data);
    
    if ($result) {
        $field_id = $wpdb->insert_id;
        
        // اگر فیلد کلید باشد، بررسی تکراری بودن در پرسنل
        if ($field_data['is_key'] == 1) {
            wf_check_duplicate_key_values($field_id, $field_data['field_key']);
        }
        
        // ثبت لاگ
        wf_log_user_action(
            'field_created',
            'فیلد جدید ایجاد شد',
            array(
                'field_id' => $field_id,
                'field_name' => $field_data['field_name'],
                'field_key' => $field_data['field_key']
            )
        );
        
        return array(
            'success' => true,
            'message' => 'فیلد با موفقیت ایجاد شد',
            'field_id' => $field_id
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
    $field = wf_get_field($field_id);
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
    
    // آماده‌سازی داده‌ها
    $update_data = array();
    
    if (isset($data['field_name'])) {
        $update_data['field_name'] = sanitize_text_field($data['field_name']);
    }
    
    if (isset($data['field_type'])) {
        $update_data['field_type'] = sanitize_text_field($data['field_type']);
    }
    
    if (isset($data['field_options'])) {
        $update_data['field_options'] = wp_json_encode($data['field_options']);
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
    
    if (isset($data['validation_rules'])) {
        $update_data['validation_rules'] = wp_json_encode($data['validation_rules']);
    }
    
    if (isset($data['description'])) {
        $update_data['description'] = sanitize_textarea_field($data['description']);
    }
    
    if (isset($data['status'])) {
        $update_data['status'] = sanitize_text_field($data['status']);
    }
    
    $update_data['updated_at'] = current_time('mysql');
    
    // به‌روزرسانی در دیتابیس
    $result = $wpdb->update(
        $table_name,
        $update_data,
        array('id' => $field_id),
        array('%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s'),
        array('%d')
    );
    
    if ($result !== false) {
        // ثبت لاگ
        wf_log_user_action(
            'field_updated',
            'فیلد به‌روزرسانی شد',
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
 * حذف فیلد
 */
function wf_delete_field($field_id, $permanent = false) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_fields';
    
    // بررسی وجود فیلد
    $field = wf_get_field($field_id);
    if (!$field) {
        return array(
            'success' => false,
            'message' => 'فیلد مورد نظر یافت نشد'
        );
    }
    
    // بررسی استفاده از فیلد در داده‌ها
    if (wf_is_field_used($field_id)) {
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
            array('status' => 'deleted', 'updated_at' => current_time('mysql')),
            array('id' => $field_id)
        );
    }
    
    if ($result) {
        // ثبت لاگ
        wf_log_user_action(
            'field_deleted',
            $permanent ? 'فیلد حذف شد' : 'فیلد به سطل زباله منتقل شد',
            array(
                'field_id' => $field_id,
                'field_name' => $field['field_name'],
                'permanent' => $permanent
            )
        );
        
        return array(
            'success' => true,
            'message' => $permanent ? 'فیلد با موفقیت حذف شد' : 'فیلد به سطل زباله منتقل شد'
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در حذف فیلد: ' . $wpdb->last_error
    );
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
    
    if ($field && isset($field['field_options'])) {
        $field['field_options'] = json_decode($field['field_options'], true);
    }
    
    if ($field && isset($field['validation_rules'])) {
        $field['validation_rules'] = json_decode($field['validation_rules'], true);
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
    
    if (!empty($filters['field_type'])) {
        $where[] = "field_type = %s";
        $params[] = $filters['field_type'];
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
    
    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    if (!empty($params)) {
        $sql = $wpdb->prepare(
            "SELECT * FROM $table_name $where_clause ORDER BY display_order ASC, id ASC",
            $params
        );
    } else {
        $sql = "SELECT * FROM $table_name $where_clause ORDER BY display_order ASC, id ASC";
    }
    
    $fields = $wpdb->get_results($sql, ARRAY_A);
    
    // پردازش فیلدهای JSON
    foreach ($fields as &$field) {
        if (isset($field['field_options'])) {
            $field['field_options'] = json_decode($field['field_options'], true);
        }
        if (isset($field['validation_rules'])) {
            $field['validation_rules'] = json_decode($field['validation_rules'], true);
        }
    }
    
    return $fields;
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
    
    if (!empty($params)) {
        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name $where_clause",
            $params
        );
    } else {
        $sql = "SELECT COUNT(*) FROM $table_name $where_clause";
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
    $validation = wf_validate_department_data($data);
    if (!$validation['valid']) {
        return array(
            'success' => false,
            'message' => $validation['message'],
            'errors' => $validation['errors']
        );
    }
    
    // آماده‌سازی داده‌ها
    $department_data = array(
        'name' => sanitize_text_field($data['name']),
        'code' => isset($data['code']) ? sanitize_text_field($data['code']) : null,
        'manager_id' => isset($data['manager_id']) ? (int)$data['manager_id'] : null,
        'parent_id' => isset($data['parent_id']) ? (int)$data['parent_id'] : null,
        'color' => isset($data['color']) ? sanitize_hex_color($data['color']) : '#1a73e8',
        'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : null,
        'settings' => isset($data['settings']) ? wp_json_encode($data['settings']) : null,
        'created_by' => get_current_user_id(),
        'status' => 'active'
    );
    
    // بررسی تکراری نبودن کد
    if (!empty($department_data['code'])) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE code = %s AND status != 'suspended'",
            $department_data['code']
        ));
        
        if ($exists > 0) {
            return array(
                'success' => false,
                'message' => 'کد اداره تکراری است'
            );
        }
    }
    
    // درج در دیتابیس
    $result = $wpdb->insert($table_name, $department_data);
    
    if ($result) {
        $department_id = $wpdb->insert_id;
        
        // به‌روزرسانی نقش کاربر اگر مدیر انتخاب شده
        if ($department_data['manager_id']) {
            $user = get_user_by('id', $department_data['manager_id']);
            if ($user) {
                $user->add_role('wf_department_manager');
                update_user_meta($department_data['manager_id'], 'wf_department_id', $department_id);
            }
        }
        
        // ثبت لاگ
        wf_log_user_action(
            'department_created',
            'اداره جدید ایجاد شد',
            array(
                'department_id' => $department_id,
                'department_name' => $department_data['name'],
                'manager_id' => $department_data['manager_id']
            )
        );
        
        return array(
            'success' => true,
            'message' => 'اداره با موفقیت ایجاد شد',
            'department_id' => $department_id
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در ایجاد اداره: ' . $wpdb->last_error
    );
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
    
    // آماده‌سازی داده‌ها
    $update_data = array();
    
    if (isset($data['name'])) {
        $update_data['name'] = sanitize_text_field($data['name']);
    }
    
    if (isset($data['code'])) {
        $update_data['code'] = sanitize_text_field($data['code']);
    }
    
    if (isset($data['manager_id'])) {
        $old_manager_id = $department['manager_id'];
        $update_data['manager_id'] = (int)$data['manager_id'];
        
        // به‌روزرسانی نقش مدیر قبلی
        if ($old_manager_id && $old_manager_id != $update_data['manager_id']) {
            wf_update_user_department_role($old_manager_id, null);
        }
        
        // به‌روزرسانی نقش مدیر جدید
        if ($update_data['manager_id']) {
            wf_update_user_department_role($update_data['manager_id'], $department_id);
        }
    }
    
    if (isset($data['parent_id'])) {
        $update_data['parent_id'] = (int)$data['parent_id'];
        
        // بررسی چرخه در ساختار درختی
        if (wf_check_department_cycle($department_id, $update_data['parent_id'])) {
            return array(
                'success' => false,
                'message' => 'امکان ایجاد چرخه در ساختار سازمانی وجود ندارد'
            );
        }
    }
    
    if (isset($data['color'])) {
        $update_data['color'] = sanitize_hex_color($data['color']);
    }
    
    if (isset($data['description'])) {
        $update_data['description'] = sanitize_textarea_field($data['description']);
    }
    
    if (isset($data['settings'])) {
        $update_data['settings'] = wp_json_encode($data['settings']);
    }
    
    if (isset($data['status'])) {
        $update_data['status'] = sanitize_text_field($data['status']);
        
        // اگر اداره غیرفعال شود، پرسنل آن نیز غیرفعال می‌شوند
        if ($update_data['status'] == 'inactive') {
            wf_deactivate_department_personnel($department_id);
        }
    }
    
    $update_data['updated_at'] = current_time('mysql');
    
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
            'اداره به‌روزرسانی شد',
            array(
                'department_id' => $department_id,
                'changes' => array_keys($update_data)
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
 * حذف اداره
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
    
    // بررسی داشتن پرسنل
    $has_personnel = wf_count_department_personnel($department_id);
    if ($has_personnel > 0) {
        return array(
            'success' => false,
            'message' => 'این اداره دارای پرسنل است و نمی‌توان آن را حذف کرد'
        );
    }
    
    // بررسی داشتن زیرمجموعه
    $has_children = wf_count_department_children($department_id);
    if ($has_children > 0) {
        return array(
            'success' => false,
            'message' => 'این اداره دارای زیرمجموعه است و نمی‌توان آن را حذف کرد'
        );
    }
    
    if ($permanent) {
        // حذف فیزیکی
        $result = $wpdb->delete($table_name, array('id' => $department_id));
    } else {
        // حذف نرم (تغییر وضعیت)
        $result = $wpdb->update(
            $table_name,
            array('status' => 'suspended', 'updated_at' => current_time('mysql')),
            array('id' => $department_id)
        );
    }
    
    if ($result) {
        // حذف نقش مدیر
        if ($department['manager_id']) {
            wf_update_user_department_role($department['manager_id'], null);
        }
        
        // ثبت لاگ
        wf_log_user_action(
            'department_deleted',
            $permanent ? 'اداره حذف شد' : 'اداره تعلیق شد',
            array(
                'department_id' => $department_id,
                'department_name' => $department['name'],
                'permanent' => $permanent
            )
        );
        
        return array(
            'success' => true,
            'message' => $permanent ? 'اداره با موفقیت حذف شد' : 'اداره تعلیق شد'
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
        "SELECT * FROM $table_name WHERE id = %d AND status != 'suspended'",
        $department_id
    ), ARRAY_A);
    
    if ($department && isset($department['settings'])) {
        $department['settings'] = json_decode($department['settings'], true);
    }
    
    return $department;
}

/**
 * دریافت همه ادارات
 */
function wf_get_all_departments($filters = array()) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_departments';
    
    $where = array("status != 'suspended'");
    $params = array();
    
    if (!empty($filters['status'])) {
        $where[] = "status = %s";
        $params[] = $filters['status'];
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
    
    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    if (!empty($params)) {
        $sql = $wpdb->prepare(
            "SELECT * FROM $table_name $where_clause ORDER BY parent_id ASC, name ASC",
            $params
        );
    } else {
        $sql = "SELECT * FROM $table_name $where_clause ORDER BY parent_id ASC, name ASC";
    }
    
    $departments = $wpdb->get_results($sql, ARRAY_A);
    
    // پردازش تنظیمات JSON
    foreach ($departments as &$department) {
        if (isset($department['settings'])) {
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

// ==================== توابع CRUD برای پرسنل ====================

/**
 * ایجاد پرسنل جدید
 */
function wf_create_personnel($data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_personnel';
    
    // اعتبارسنجی داده‌ها
    $validation = wf_validate_personnel_data($data);
    if (!$validation['valid']) {
        return array(
            'success' => false,
            'message' => $validation['message'],
            'errors' => $validation['errors']
        );
    }
    
    // بررسی تکراری نبودن کد ملی
    if (wf_is_national_code_exists($data['national_code'])) {
        return array(
            'success' => false,
            'message' => 'کد ملی تکراری است'
        );
    }
    
    // آماده‌سازی داده‌های JSON
    $json_data = array();
    $fields = wf_get_all_fields();
    
    foreach ($fields as $field) {
        $field_key = $field['field_key'];
        if (isset($data[$field_key])) {
            $json_data[$field_key] = wf_sanitize_field_value($data[$field_key], $field['field_type']);
        }
    }
    
    // محاسبه درصد تکمیل
    $completion_stats = wf_calculate_completion_percentage($json_data);
    
    // آماده‌سازی داده‌های اصلی
    $personnel_data = array(
        'national_code' => sanitize_text_field($data['national_code']),
        'department_id' => (int)$data['department_id'],
        'employment_date' => isset($data['employment_date']) ? wf_convert_to_gregorian($data['employment_date']) : null,
        'employment_type' => isset($data['employment_type']) ? sanitize_text_field($data['employment_type']) : 'permanent',
        'position' => isset($data['position']) ? sanitize_text_field($data['position']) : null,
        'level' => isset($data['level']) ? sanitize_text_field($data['level']) : null,
        'profile_image' => isset($data['profile_image']) ? esc_url_raw($data['profile_image']) : null,
        'data' => wp_json_encode($json_data),
        'required_fields_completed' => $completion_stats['required_completed'],
        'total_fields' => $completion_stats['total_fields'],
        'completion_percentage' => $completion_stats['percentage'],
        'has_warnings' => $completion_stats['has_warnings'] ? 1 : 0,
        'warnings' => $completion_stats['has_warnings'] ? wp_json_encode($completion_stats['warnings']) : null,
        'created_by' => get_current_user_id(),
        'last_modified_by' => get_current_user_id(),
        'status' => 'active'
    );
    
    // درج در دیتابیس
    $result = $wpdb->insert($table_name, $personnel_data);
    
    if ($result) {
        $personnel_id = $wpdb->insert_id;
        
        // به‌روزرسانی تعداد پرسنل اداره
        wf_update_department_personnel_count($personnel_data['department_id']);
        
        // ثبت لاگ
        wf_log_user_action(
            'personnel_created',
            'پرسنل جدید اضافه شد',
            array(
                'personnel_id' => $personnel_id,
                'national_code' => $personnel_data['national_code'],
                'department_id' => $personnel_data['department_id']
            )
        );
        
        return array(
            'success' => true,
            'message' => 'پرسنل با موفقیت اضافه شد',
            'personnel_id' => $personnel_id,
            'completion_percentage' => $completion_stats['percentage']
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در اضافه کردن پرسنل: ' . $wpdb->last_error
    );
}

/**
 * به‌روزرسانی پرسنل
 */
function wf_update_personnel($personnel_id, $data, $request_approval = false) {
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
    
    if (!$user_can_edit['can_edit'] && !$request_approval) {
        return array(
            'success' => false,
            'message' => $user_can_edit['message']
        );
    }
    
    // اگر نیاز به تایید باشد، درخواست ایجاد کن
    if ($request_approval || !$user_can_edit['direct_edit']) {
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
    
    // آماده‌سازی داده‌های JSON
    $current_json_data = json_decode($personnel['data'], true);
    $new_json_data = $current_json_data;
    
    $fields = wf_get_all_fields();
    $updated_fields = array();
    
    foreach ($fields as $field) {
        $field_key = $field['field_key'];
        
        // بررسی آیا فیلد در داده‌های ارسالی وجود دارد
        if (array_key_exists($field_key, $data)) {
            // بررسی قفل بودن فیلد
            if ($field['is_locked'] == 1 && !current_user_can('manage_options')) {
                continue; // مدیران اداره نمی‌توانند فیلدهای قفل را ویرایش کنند
            }
            
            $old_value = isset($current_json_data[$field_key]) ? $current_json_data[$field_key] : null;
            $new_value = wf_sanitize_field_value($data[$field_key], $field['field_type']);
            
            // اگر مقدار تغییر کرده باشد
            if ($old_value != $new_value) {
                $new_json_data[$field_key] = $new_value;
                $updated_fields[$field_key] = array(
                    'old' => $old_value,
                    'new' => $new_value
                );
            }
        }
    }
    
    // محاسبه درصد تکمیل جدید
    $completion_stats = wf_calculate_completion_percentage($new_json_data);
    
    // آماده‌سازی داده‌های به‌روزرسانی
    $update_data = array(
        'data' => wp_json_encode($new_json_data),
        'required_fields_completed' => $completion_stats['required_completed'],
        'total_fields' => $completion_stats['total_fields'],
        'completion_percentage' => $completion_stats['percentage'],
        'has_warnings' => $completion_stats['has_warnings'] ? 1 : 0,
        'warnings' => $completion_stats['has_warnings'] ? wp_json_encode($completion_stats['warnings']) : null,
        'last_modified_by' => $current_user_id,
        'updated_at' => current_time('mysql')
    );
    
    // به‌روزرسانی فیلدهای اصلی اگر ارسال شده باشند
    if (isset($data['department_id'])) {
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
            'اطلاعات پرسنل به‌روزرسانی شد',
            array(
                'personnel_id' => $personnel_id,
                'national_code' => $personnel['national_code'],
                'updated_fields' => array_keys($updated_fields),
                'completion_percentage' => $completion_stats['percentage']
            )
        );
        
        return array(
            'success' => true,
            'message' => 'اطلاعات پرسنل با موفقیت به‌روزرسانی شد',
            'updated_fields' => $updated_fields,
            'completion_percentage' => $completion_stats['percentage']
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در به‌روزرسانی اطلاعات پرسنل: ' . $wpdb->last_error
    );
}

/**
 * حذف پرسنل (حذف نرم)
 */
function wf_delete_personnel($personnel_id, $permanent = false, $request_approval = false) {
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
    
    if (!$user_can_delete['can_delete'] && !$request_approval) {
        return array(
            'success' => false,
            'message' => $user_can_delete['message']
        );
    }
    
    // اگر نیاز به تایید باشد، درخواست ایجاد کن
    if ($request_approval || !$user_can_delete['direct_delete']) {
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
                'status' => 'inactive'
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
            $permanent ? 'پرسنل به طور دائم حذف شد' : 'پرسنل حذف شد',
            array(
                'personnel_id' => $personnel_id,
                'national_code' => $personnel['national_code'],
                'department_id' => $personnel['department_id'],
                'permanent' => $permanent
            )
        );
        
        return array(
            'success' => true,
            'message' => $permanent ? 'پرسنل به طور دائم حذف شد' : 'پرسنل حذف شد'
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در حذف پرسنل: ' . $wpdb->last_error
    );
}

/**
 * بازیابی پرسنل حذف شده
 */
function wf_restore_personnel($personnel_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_personnel';
    
    // بررسی وجود پرسنل
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
            'پرسنل بازیابی شد',
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
        if (isset($personnel['data'])) {
            $personnel['data'] = json_decode($personnel['data'], true);
        }
        
        if (isset($personnel['warnings'])) {
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
        $national_code
    ), ARRAY_A);
    
    if ($personnel && isset($personnel['data'])) {
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
        $where[] = "(national_code LIKE %s OR data LIKE %s OR position LIKE %s)";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = implode(' AND ', $where);
    
    // ساخت کوئری اصلی
    $sql = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE $where_clause",
        $params
    );
    
    // مرتب‌سازی
    $order_by = 'id DESC';
    if (!empty($filters['order_by'])) {
        $order_by = sanitize_sql_orderby($filters['order_by']);
    }
    $sql .= " ORDER BY $order_by";
    
    // صفحه‌بندی
    if (!empty($pagination['per_page']) && !empty($pagination['page'])) {
        $offset = ($pagination['page'] - 1) * $pagination['per_page'];
        $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $pagination['per_page'], $offset);
    }
    
    $personnel = $wpdb->get_results($sql, ARRAY_A);
    
    // پردازش داده‌های JSON
    foreach ($personnel as &$person) {
        if (isset($person['data'])) {
            $person['data'] = json_decode($person['data'], true);
        }
    }
    
    return $personnel;
}

/**
 * شمارش پرسنل یک اداره
 */
function wf_count_department_personnel($department_id, $filters = array()) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_personnel';
    
    $where = array("department_id = %d", "is_deleted = 0");
    $params = array($department_id);
    
    if (!empty($filters['status'])) {
        $where[] = "status = %s";
        $params[] = $filters['status'];
    }
    
    if (isset($filters['has_warnings'])) {
        $where[] = "has_warnings = %d";
        $params[] = (int)$filters['has_warnings'];
    }
    
    $where_clause = implode(' AND ', $where);
    
    $sql = $wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE $where_clause",
        $params
    );
    
    return (int)$wpdb->get_var($sql);
}

// ==================== توابع CRUD برای دوره‌ها ====================

/**
 * ایجاد دوره جدید
 */
function wf_create_period($data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_periods';
    
    // اعتبارسنجی داده‌ها
    $validation = wf_validate_period_data($data);
    if (!$validation['valid']) {
        return array(
            'success' => false,
            'message' => $validation['message'],
            'errors' => $validation['errors']
        );
    }
    
    // تبدیل تاریخ‌های شمسی به میلادی
    $start_date = wf_convert_to_gregorian($data['start_date']);
    $end_date = wf_convert_to_gregorian($data['end_date']);
    
    // بررسی منطقی بودن بازه زمانی
    if (strtotime($start_date) > strtotime($end_date)) {
        return array(
            'success' => false,
            'message' => 'تاریخ شروع نمی‌تواند بعد از تاریخ پایان باشد'
        );
    }
    
    // بررسی تداخل با دوره‌های دیگر
    $overlapping = wf_check_period_overlap($start_date, $end_date);
    if ($overlapping) {
        return array(
            'success' => false,
            'message' => 'این دوره با دوره‌های دیگر تداخل دارد'
        );
    }
    
    // آماده‌سازی داده‌ها
    $period_data = array(
        'title' => sanitize_text_field($data['title']),
        'period_code' => isset($data['period_code']) ? sanitize_text_field($data['period_code']) : null,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : null,
        'settings' => isset($data['settings']) ? wp_json_encode($data['settings']) : null,
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
            'دوره جدید ایجاد شد',
            array(
                'period_id' => $period_id,
                'period_title' => $period_data['title'],
                'start_date' => $period_data['start_date'],
                'end_date' => $period_data['end_date']
            )
        );
        
        return array(
            'success' => true,
            'message' => 'دوره با موفقیت ایجاد شد',
            'period_id' => $period_id
        );
    }
    
    return array(
        'success' => false,
        'message' => 'خطا در ایجاد دوره: ' . $wpdb->last_error
    );
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
    
    if (isset($data['title'])) {
        $update_data['title'] = sanitize_text_field($data['title']);
    }
    
    if (isset($data['period_code'])) {
        $update_data['period_code'] = sanitize_text_field($data['period_code']);
    }
    
    if (isset($data['start_date'])) {
        $update_data['start_date'] = wf_convert_to_gregorian($data['start_date']);
    }
    
    if (isset($data['end_date'])) {
        $update_data['end_date'] = wf_convert_to_gregorian($data['end_date']);
    }
    
    // بررسی منطقی بودن بازه زمانی
    if (isset($update_data['start_date']) && isset($update_data['end_date'])) {
        if (strtotime($update_data['start_date']) > strtotime($update_data['end_date'])) {
            return array(
                'success' => false,
                'message' => 'تاریخ شروع نمی‌تواند بعد از تاریخ پایان باشد'
            );
        }
    }
    
    if (isset($data['description'])) {
        $update_data['description'] = sanitize_textarea_field($data['description']);
    }
    
    if (isset($data['settings'])) {
        $update_data['settings'] = wp_json_encode($data['settings']);
    }
    
    if (isset($data['is_active'])) {
        $update_data['is_active'] = (int)$data['is_active'];
        
        // اگر این دوره فعال شود، سایر دوره‌ها غیرفعال شوند
        if ($update_data['is_active'] == 1) {
            $wpdb->update(
                $table_name,
                array('is_active' => 0),
                array('is_active' => 1)
            );
        }
    }
    
    if (isset($data['status'])) {
        $update_data['status'] = sanitize_text_field($data['status']);
    }
    
    $update_data['updated_at'] = current_time('mysql');
    
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
            'دوره به‌روزرسانی شد',
            array(
                'period_id' => $period_id,
                'period_title' => $period['title'],
                'changes' => array_keys($update_data)
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
 * دریافت دوره فعال
 */
function wf_get_active_period() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_periods';
    
    $period = $wpdb->get_row(
        "SELECT * FROM $table_name WHERE is_active = 1 AND status = 'active' LIMIT 1",
        ARRAY_A
    );
    
    if ($period && isset($period['settings'])) {
        $period['settings'] = json_decode($period['settings'], true);
    }
    
    return $period;
}

// ==================== توابع CRUD برای درخواست‌های تایید ====================

/**
 * ایجاد درخواست تایید
 */
function wf_create_approval_request($request_type, $request_data, $requester_id, $department_id = null, $personnel_id = null, $field_id = null, $priority = 'normal') {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_approvals';
    
    $approval_data = array(
        'request_type' => $request_type,
        'request_data' => wp_json_encode($request_data),
        'requester_id' => $requester_id,
        'department_id' => $department_id,
        'personnel_id' => $personnel_id,
        'field_id' => $field_id,
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
            'درخواست تایید جدید ایجاد شد',
            array(
                'approval_id' => $approval_id,
                'request_type' => $request_type,
                'requester_id' => $requester_id,
                'department_id' => $department_id
            )
        );
        
        // ارسال ایمیل به ادمین‌ها
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
    
    // آماده‌سازی داده‌های به‌روزرسانی
    $update_data = array(
        'status' => $action,
        'admin_notes' => sanitize_textarea_field($admin_notes),
        'response_data' => wp_json_encode($response_data),
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
        // اجرای اقدام بر اساس نوع درخواست
        if ($action == 'approved') {
            wf_execute_approved_request($approval);
        }
        
        // ارسال ایمیل به درخواست‌دهنده
        wf_send_approval_response_notification($approval_id, $action);
        
        // ثبت لاگ
        wf_log_user_action(
            'approval_reviewed',
            'درخواست تایید بررسی شد',
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

// ==================== توابع کمکی پیشرفته ====================

/**
 * محاسبه آمار کامل سیستم
 */
function wf_calculate_system_stats() {
    global $wpdb;
    
    $stats = array(
        'total_departments' => 0,
        'total_personnel' => 0,
        'active_personnel' => 0,
        'inactive_personnel' => 0,
        'departments_without_manager' => 0,
        'pending_approvals' => 0,
        'average_completion' => 0,
        'personnel_with_warnings' => 0,
        'recent_activity' => array()
    );
    
    // شمارش ادارات
    $stats['total_departments'] = (int)$wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wf_departments WHERE status = 'active'"
    );
    
    // شمارش پرسنل
    $stats['total_personnel'] = (int)$wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wf_personnel WHERE is_deleted = 0"
    );
    
    $stats['active_personnel'] = (int)$wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wf_personnel WHERE status = 'active' AND is_deleted = 0"
    );
    
    $stats['inactive_personnel'] = (int)$wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wf_personnel WHERE status != 'active' AND is_deleted = 0"
    );
    
    // ادارات بدون مدیر
    $stats['departments_without_manager'] = (int)$wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wf_departments WHERE manager_id IS NULL AND status = 'active'"
    );
    
    // درخواست‌های در انتظار
    $stats['pending_approvals'] = (int)$wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wf_approvals WHERE status = 'pending'"
    );
    
    // میانگین درصد تکمیل
    $stats['average_completion'] = (float)$wpdb->get_var(
        "SELECT AVG(completion_percentage) FROM {$wpdb->prefix}wf_personnel WHERE is_deleted = 0"
    );
    
    // پرسنل با هشدار
    $stats['personnel_with_warnings'] = (int)$wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wf_personnel WHERE has_warnings = 1 AND is_deleted = 0"
    );
    
    // فعالیت‌های اخیر
    $stats['recent_activity'] = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}wf_logs 
         ORDER BY created_at DESC 
         LIMIT 10",
        ARRAY_A
    );
    
    return $stats;
}

/**
 * جستجوی پیشرفته در پرسنل
 */
function wf_advanced_personnel_search($search_params, $pagination = array()) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_personnel';
    
    $where = array("p.is_deleted = 0");
    $params = array();
    $joins = array();
    
    // جستجو در فیلدهای اصلی
    if (!empty($search_params['national_code'])) {
        $where[] = "p.national_code LIKE %s";
        $params[] = '%' . $wpdb->esc_like($search_params['national_code']) . '%';
    }
    
    if (!empty($search_params['department_id'])) {
        $where[] = "p.department_id = %d";
        $params[] = (int)$search_params['department_id'];
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
    
    // جستجو در فیلدهای داینامیک (داده‌های JSON)
    if (!empty($search_params['field_search'])) {
        foreach ($search_params['field_search'] as $field_key => $value) {
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
    
    // بازه تاریخ استخدام
    if (!empty($search_params['employment_date_from'])) {
        $where[] = "p.employment_date >= %s";
        $params[] = wf_convert_to_gregorian($search_params['employment_date_from']);
    }
    
    if (!empty($search_params['employment_date_to'])) {
        $where[] = "p.employment_date <= %s";
        $params[] = wf_convert_to_gregorian($search_params['employment_date_to']);
    }
    
    // JOIN با جدول ادارات
    $joins[] = "LEFT JOIN {$wpdb->prefix}wf_departments d ON p.department_id = d.id";
    
    // جستجو در نام اداره
    if (!empty($search_params['department_name'])) {
        $where[] = "d.name LIKE %s";
        $params[] = '%' . $wpdb->esc_like($search_params['department_name']) . '%';
    }
    
    // ساخت کوئری
    $join_clause = implode(' ', $joins);
    $where_clause = implode(' AND ', $where);
    
    // کوئری شمارش برای صفحه‌بندی
    $count_sql = "SELECT COUNT(*) FROM $table_name p $join_clause WHERE $where_clause";
    if (!empty($params)) {
        $count_sql = $wpdb->prepare($count_sql, $params);
    }
    
    $total_count = (int)$wpdb->get_var($count_sql);
    
    // کوئری اصلی
    $sql = "SELECT p.*, d.name as department_name, d.color as department_color 
            FROM $table_name p 
            $join_clause 
            WHERE $where_clause";
    
    // مرتب‌سازی
    $order_by = 'p.id DESC';
    if (!empty($search_params['order_by'])) {
        $order_by = sanitize_sql_orderby($search_params['order_by']);
    }
    $sql .= " ORDER BY $order_by";
    
    // صفحه‌بندی
    if (!empty($pagination['per_page']) && !empty($pagination['page'])) {
        $offset = ($pagination['page'] - 1) * $pagination['per_page'];
        $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $pagination['per_page'], $offset);
    }
    
    if (!empty($params)) {
        $sql = $wpdb->prepare($sql, $params);
    }
    
    $results = $wpdb->get_results($sql, ARRAY_A);
    
    // پردازش داده‌های JSON
    foreach ($results as &$result) {
        if (isset($result['data'])) {
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
 * ایجاد گزارش تجمیعی
 */
function wf_generate_aggregate_report($report_type, $params = array()) {
    global $wpdb;
    
    $reports = array();
    
    switch ($report_type) {
        case 'department_summary':
            // گزارش خلاصه ادارات
            $reports = $wpdb->get_results("
                SELECT 
                    d.id,
                    d.name,
                    d.color,
                    d.manager_id,
                    u.display_name as manager_name,
                    COUNT(p.id) as personnel_count,
                    AVG(p.completion_percentage) as avg_completion,
                    SUM(CASE WHEN p.has_warnings = 1 THEN 1 ELSE 0 END) as warning_count,
                    MAX(p.updated_at) as last_activity
                FROM {$wpdb->prefix}wf_departments d
                LEFT JOIN {$wpdb->prefix}wf_personnel p ON d.id = p.department_id AND p.is_deleted = 0
                LEFT JOIN {$wpdb->users} u ON d.manager_id = u.ID
                WHERE d.status = 'active'
                GROUP BY d.id
                ORDER BY d.name
            ", ARRAY_A);
            break;
            
        case 'completion_stats':
            // آمار درصد تکمیل
            $reports = $wpdb->get_results("
                SELECT 
                    CASE 
                        WHEN completion_percentage = 100 THEN '100%'
                        WHEN completion_percentage >= 90 THEN '90-99%'
                        WHEN completion_percentage >= 75 THEN '75-89%'
                        WHEN completion_percentage >= 50 THEN '50-74%'
                        ELSE 'کمتر از 50%'
                    END as completion_range,
                    COUNT(*) as count,
                    AVG(completion_percentage) as avg_percentage
                FROM {$wpdb->prefix}wf_personnel 
                WHERE is_deleted = 0
                GROUP BY completion_range
                ORDER BY completion_percentage DESC
            ", ARRAY_A);
            break;
            
        case 'employment_type_distribution':
            // توزیع نوع استخدام
            $reports = $wpdb->get_results("
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
            $reports = $wpdb->get_results("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
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
    }
    
    return $reports;
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
    
    // پاکسازی داده‌های قدیمی
    wf_cleanup_old_data();
    
    // آنالیز جداول
    foreach ($tables as $table) {
        $wpdb->query("ANALYZE TABLE {$wpdb->prefix}{$table}");
    }
    
    return true;
}

/**
 * ایجاد ایندکس‌های پویا بر اساس الگوی استفاده
 */
function wf_create_dynamic_indexes() {
    global $wpdb;
    
    // بررسی الگوی جستجوهای پرکاربرد
    $common_searches = $wpdb->get_results("
        SELECT 
            SUBSTRING_INDEX(target_type, '_', 1) as entity_type,
            COUNT(*) as search_count
        FROM {$wpdb->prefix}wf_logs 
        WHERE action LIKE '%search%' 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY entity_type
        ORDER BY search_count DESC
    ", ARRAY_A);
    
    foreach ($common_searches as $search) {
        switch ($search['entity_type']) {
            case 'personnel':
                // ایجاد ایندکس برای جستجوهای پرکاربرد پرسنل
                $index_name = 'idx_personnel_search_common';
                $wpdb->query("
                    CREATE INDEX IF NOT EXISTS $index_name 
                    ON {$wpdb->prefix}wf_personnel (status, department_id, has_warnings)
                ");
                break;
        }
    }
}

// ==================== توابع backup و restore ====================

/**
 * ایجاد backup از داده‌ها
 */
function wf_create_backup($backup_type = 'auto') {
    global $wpdb;
    
    $backup_data = array(
        'timestamp' => current_time('mysql'),
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
    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.json';
    $filepath = WP_CONTENT_DIR . '/uploads/workforce_backups/' . $filename;
    
    // ایجاد دایرکتوری اگر وجود ندارد
    wp_mkdir_p(dirname($filepath));
    
    // ذخیره فایل
    $result = file_put_contents($filepath, json_encode($backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    if ($result !== false) {
        // ذخیره اطلاعات backup در دیتابیس
        $wpdb->insert($wpdb->prefix . 'wf_backups', array(
            'backup_type' => $backup_type,
            'filename' => $filename,
            'filepath' => $filepath,
            'filesize' => filesize($filepath),
            'record_count' => count($backup_data['tables'], COUNT_RECURSIVE),
            'created_by' => get_current_user_id(),
            'status' => 'success',
            'notes' => 'Backup created successfully'
        ));
        
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
                    unlink($backups[$i]['filepath']);
                }
                $wpdb->delete($wpdb->prefix . 'wf_backups', array('id' => $backups[$i]['id']));
            }
        }
        
        return array(
            'success' => true,
            'message' => 'Backup created successfully',
            'filename' => $filename,
            'filepath' => $filepath
        );
    }
    
    return array(
        'success' => false,
        'message' => 'Failed to create backup'
    );
}

/**
 * بازیابی از backup
 */
function wf_restore_from_backup($backup_id) {
    global $wpdb;
    
    // دریافت اطلاعات backup
    $backup = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wf_backups WHERE id = %d",
        $backup_id
    ), ARRAY_A);
    
    if (!$backup || !file_exists($backup['filepath'])) {
        return array(
            'success' => false,
            'message' => 'Backup file not found'
        );
    }
    
    // خواندن فایل backup
    $backup_content = file_get_contents($backup['filepath']);
    $backup_data = json_decode($backup_content, true);
    
    if (!$backup_data) {
        return array(
            'success' => false,
            'message' => 'Invalid backup file format'
        );
    }
    
    // شروع تراکنش
    $wpdb->query('START TRANSACTION');
    
    try {
        // پاکسازی جداول فعلی
        $tables = array_keys($backup_data['tables']);
        foreach ($tables as $table) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}{$table}");
        }
        
        // بازگردانی داده‌ها
        foreach ($backup_data['tables'] as $table => $data) {
            if (!empty($data)) {
                foreach ($data as $row) {
                    $wpdb->insert($wpdb->prefix . $table, $row);
                }
            }
        }
        
        // ثبت لاگ
        wf_log_user_action(
            'system_restored',
            'سیستم از backup بازیابی شد',
            array(
                'backup_id' => $backup_id,
                'backup_filename' => $backup['filename'],
                'tables_restored' => $tables
            )
        );
        
        // تأیید تراکنش
        $wpdb->query('COMMIT');
        
        return array(
            'success' => true,
            'message' => 'System restored successfully from backup'
        );
        
    } catch (Exception $e) {
        // بازگشت از تراکنش
        $wpdb->query('ROLLBACK');
        
        return array(
            'success' => false,
            'message' => 'Restore failed: ' . $e->getMessage()
        );
    }
}

// ==================== توابع امنیتی ====================

/**
 * بررسی دسترسی کاربر
 */
function wf_check_user_access($user_id, $department_id = null, $permission = 'view') {
    $user = get_user_by('id', $user_id);
    
    if (!$user) {
        return false;
    }
    
    // ادمین وردپرس دسترسی کامل دارد
    if (in_array('administrator', $user->roles)) {
        return true;
    }
    
    // مدیر سازمان دسترسی به همه ادارات دارد
    if (in_array('wf_org_manager', $user->roles)) {
        return true;
    }
    
    // مدیر اداره فقط به اداره خود دسترسی دارد
    if (in_array('wf_department_manager', $user->roles)) {
        if ($department_id) {
            $user_department_id = get_user_meta($user_id, 'wf_department_id', true);
            return $user_department_id == $department_id;
        }
        return true; // اگر department_id مشخص نشده، اجازه دسترسی بده
    }
    
    return false;
}

/**
 * بررسی تزریق SQL
 */
function wf_sanitize_sql($sql) {
    global $wpdb;
    
    // حذف کلمات کلیدی خطرناک
    $dangerous_keywords = array(
        'DROP', 'DELETE', 'TRUNCATE', 'UPDATE', 'INSERT', 
        'CREATE', 'ALTER', 'EXEC', 'EXECUTE', 'MERGE'
    );
    
    foreach ($dangerous_keywords as $keyword) {
        $pattern = '/\b' . $keyword . '\b/i';
        if (preg_match($pattern, $sql)) {
            return false;
        }
    }
    
    return $sql;
}

/**
 * لاگ‌گیری فعالیت‌های حساس
 */
function wf_log_sensitive_action($action, $details = array(), $severity = 'warning') {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    return wf_log_system_action(
        'security_' . $action,
        'فعالیت حساس شناسایی شد',
        array_merge($details, array(
            'ip' => $ip_address,
            'user_agent' => $user_agent
        )),
        $severity
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
        'details' => wp_json_encode($details),
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
        'details' => wp_json_encode(array_merge(
            array('message' => $message),
            $details
        )),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'severity' => 'info',
        'created_at' => current_time('mysql')
    );
    
    return $wpdb->insert($wpdb->prefix . 'wf_logs', $log_data);
}

/**
 * پاکسازی دوره‌ای لاگ‌ها
 */
add_action('wf_daily_maintenance', 'wf_cleanup_logs');

function wf_cleanup_logs() {
    global $wpdb;
    
    $retention_days = get_option('wf_log_retention_days', 90);
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-$retention_days days"));
    
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}wf_logs WHERE created_at < %s",
        $cutoff_date
    ));
}

// ==================== توابع اعتبارسنجی ====================

/**
 * اعتبارسنجی داده‌های فیلد
 */
function wf_validate_field_data($data) {
    $errors = array();
    
    if (empty($data['field_name'])) {
        $errors['field_name'] = 'عنوان فیلد الزامی است';
    }
    
    if (empty($data['field_key'])) {
        $errors['field_key'] = 'کلید فیلد الزامی است';
    } elseif (!preg_match('/^[a-z][a-z0-9_]*$/', $data['field_key'])) {
        $errors['field_key'] = 'کلید فیلد باید با حروف انگلیسی شروع شود و فقط شامل حروف کوچک، اعداد و زیرخط باشد';
    }
    
    $valid_types = array('text', 'number', 'decimal', 'date', 'time', 'datetime', 'select', 'checkbox');
    if (empty($data['field_type']) || !in_array($data['field_type'], $valid_types)) {
        $errors['field_type'] = 'نوع فیلد معتبر نیست';
    }
    
    return array(
        'valid' => empty($errors),
        'message' => empty($errors) ? 'داده‌ها معتبر هستند' : 'خطاهای اعتبارسنجی',
        'errors' => $errors
    );
}

/**
 * اعتبارسنجی داده‌های اداره
 */
function wf_validate_department_data($data) {
    $errors = array();
    
    if (empty($data['name'])) {
        $errors['name'] = 'نام اداره الزامی است';
    }
    
    if (!empty($data['manager_id']) && !get_user_by('id', $data['manager_id'])) {
        $errors['manager_id'] = 'مدیر انتخاب شده معتبر نیست';
    }
    
    if (!empty($data['color']) && !preg_match('/^#[0-9a-f]{6}$/i', $data['color'])) {
        $errors['color'] = 'فرمت رنگ معتبر نیست';
    }
    
    return array(
        'valid' => empty($errors),
        'message' => empty($errors) ? 'داده‌ها معتبر هستند' : 'خطاهای اعتبارسنجی',
        'errors' => $errors
    );
}

/**
 * اعتبارسنجی داده‌های پرسنل
 */
function wf_validate_personnel_data($data) {
    $errors = array();
    
    if (empty($data['national_code'])) {
        $errors['national_code'] = 'کد ملی الزامی است';
    } elseif (!preg_match('/^\d{10}$/', $data['national_code'])) {
        $errors['national_code'] = 'کد ملی باید 10 رقم باشد';
    }
    
    if (empty($data['department_id'])) {
        $errors['department_id'] = 'انتخاب اداره الزامی است';
    } elseif (!wf_get_department($data['department_id'])) {
        $errors['department_id'] = 'اداره انتخاب شده معتبر نیست';
    }
    
    if (!empty($data['employment_date']) && !wf_validate_jalali_date($data['employment_date'])) {
        $errors['employment_date'] = 'تاریخ استخدام معتبر نیست';
    }
    
    return array(
        'valid' => empty($errors),
        'message' => empty($errors) ? 'داده‌ها معتبر هستند' : 'خطاهای اعتبارسنجی',
        'errors' => $errors
    );
}

// ==================== init ====================

/**
 * مقداردهی اولیه هندلر دیتابیس
 */
add_action('plugins_loaded', 'wf_database_handler_init');

function wf_database_handler_init() {
    // بررسی و به‌روزرسانی جداول در صورت نیاز
    $current_version = get_option('wf_database_version', '0');
    if (version_compare($current_version, WF_VERSION, '<')) {
        wf_create_database_tables();
        update_option('wf_database_version', WF_VERSION);
    }
}

// فعال‌سازی maintenance hook
if (!wp_next_scheduled('wf_daily_maintenance')) {
    wp_schedule_event(time(), 'daily', 'wf_daily_maintenance');
}

// فعال‌سازی optimization hook
if (!wp_next_scheduled('wf_weekly_optimization')) {
    wp_schedule_event(time(), 'weekly', 'wf_weekly_optimization');
}

add_action('wf_weekly_optimization', 'wf_optimize_tables');

?>