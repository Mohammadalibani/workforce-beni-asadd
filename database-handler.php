<?php
/**
 * مدیریت پایگاه داده پلاگین مدیریت کارکرد پرسنل
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ایجاد جداول دیتابیس
 */
function workforce_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    $table_prefix = $wpdb->prefix . WF_TABLE_PREFIX;
    
// در تابع workforce_create_tables() در database-handler.php باید این جدول اضافه شود:
$dept_managers_table = $table_prefix . 'department_managers';
$sql9 = "CREATE TABLE IF NOT EXISTS $dept_managers_table (
    id INT(11) NOT NULL AUTO_INCREMENT,
    department_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY dept_user (department_id, user_id),
    KEY department_id (department_id),
    KEY user_id (user_id)
) $charset_collate;";

$org_managers_table = $table_prefix . 'organization_managers';
$sql10 = "CREATE TABLE IF NOT EXISTS $org_managers_table (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY user_id (user_id),
    KEY is_primary (is_primary)
) $charset_collate;";

// جدول فیلدها
$fields_table = $table_prefix . 'fields';
$sql1 = "CREATE TABLE IF NOT EXISTS $fields_table (
    id INT(11) NOT NULL AUTO_INCREMENT,
    field_name VARCHAR(100) NOT NULL,
    field_label VARCHAR(200) NOT NULL,
    field_type ENUM('text', 'number', 'decimal', 'date', 'time', 'select', 'checkbox') DEFAULT 'text',
    is_required TINYINT(1) DEFAULT 0,
    is_locked TINYINT(1) DEFAULT 0,
    is_monitoring TINYINT(1) DEFAULT 0,
    is_key TINYINT(1) DEFAULT 0,
    options TEXT,
    display_order INT(11) DEFAULT 999,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY field_name (field_name)
) $charset_collate;";
    
// جدول ادارات
$departments_table = $table_prefix . 'departments';
$sql2 = "CREATE TABLE IF NOT EXISTS $departments_table (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    manager_id INT(11) DEFAULT NULL,
    color VARCHAR(7) DEFAULT '#3498db',
    parent_id INT(11) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY manager_id (manager_id),
    KEY parent_id (parent_id)
) $charset_collate;";
    
    // جدول پرسنل
    $personnel_table = $table_prefix . 'personnel';
    $sql3 = "CREATE TABLE IF NOT EXISTS $personnel_table (
        id INT(11) NOT NULL AUTO_INCREMENT,
        department_id INT(11) NOT NULL,
        national_code VARCHAR(10) DEFAULT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        employment_date DATE DEFAULT NULL,
        employment_type ENUM('permanent', 'contract', 'temporary', 'project') DEFAULT 'permanent',
        status ENUM('active', 'inactive', 'suspended', 'retired') DEFAULT 'active',
        is_deleted TINYINT(1) DEFAULT 0,
        created_by INT(11) DEFAULT NULL,
        updated_by INT(11) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY national_code (national_code),
        KEY department_id (department_id),
        KEY status (status),
        KEY is_deleted (is_deleted)
    ) $charset_collate;";
    
    // جدول داده‌های پرسنل
    $personnel_meta_table = $table_prefix . 'personnel_meta';
    $sql4 = "CREATE TABLE IF NOT EXISTS $personnel_meta_table (
        id INT(11) NOT NULL AUTO_INCREMENT,
        personnel_id INT(11) NOT NULL,
        field_id INT(11) NOT NULL,
        meta_key VARCHAR(100) NOT NULL,
        meta_value TEXT,
        period_id INT(11) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY personnel_field_period (personnel_id, field_id, period_id),
        KEY personnel_id (personnel_id),
        KEY field_id (field_id),
        KEY meta_key (meta_key),
        KEY period_id (period_id)
    ) $charset_collate;";
    
    // جدول دوره‌های کاری
    $periods_table = $table_prefix . 'periods';
    $sql5 = "CREATE TABLE IF NOT EXISTS $periods_table (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        is_active TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY name (name),
        KEY is_active (is_active)
    ) $charset_collate;";
    
    // جدول درخواست‌های تایید
    $approvals_table = $table_prefix . 'approvals';
    $sql6 = "CREATE TABLE IF NOT EXISTS $approvals_table (
        id INT(11) NOT NULL AUTO_INCREMENT,
        request_type ENUM('add_personnel', 'edit_personnel', 'delete_personnel', 'edit_field') NOT NULL,
        requester_id INT(11) NOT NULL,
        target_id INT(11) DEFAULT NULL,
        target_type VARCHAR(50) DEFAULT NULL,
        data_before TEXT,
        data_after TEXT,
        status ENUM('pending', 'approved', 'rejected', 'needs_correction', 'suspended') DEFAULT 'pending',
        admin_notes TEXT,
        reviewer_id INT(11) DEFAULT NULL,
        reviewed_at DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY requester_id (requester_id),
        KEY status (status),
        KEY request_type (request_type)
    ) $charset_collate;";
    
    // جدول لاگ فعالیت‌ها
    $activity_logs_table = $table_prefix . 'activity_logs';
    $sql7 = "CREATE TABLE IF NOT EXISTS $activity_logs_table (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        action VARCHAR(100) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY action (action)
    ) $charset_collate;";
    
    // جدول قالب اکسل
    $excel_templates_table = $table_prefix . 'excel_templates';
    $sql8 = "CREATE TABLE IF NOT EXISTS $excel_templates_table (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        header_color VARCHAR(7) DEFAULT '#2c3e50',
        text_color VARCHAR(7) DEFAULT '#333333',
        even_row_color VARCHAR(7) DEFAULT '#f8f9fa',
        odd_row_color VARCHAR(7) DEFAULT '#ffffff',
        border_style VARCHAR(20) DEFAULT 'thin',
        border_color VARCHAR(7) DEFAULT '#dddddd',
        header_font_size INT(11) DEFAULT 12,
        data_font_size INT(11) DEFAULT 11,
        is_default TINYINT(1) DEFAULT 0,
        created_by INT(11) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY is_default (is_default)
    ) $charset_collate;";
    
        $org_managers_table = $table_prefix . 'organization_managers';
    $sql_org_managers = "CREATE TABLE IF NOT EXISTS $org_managers_table (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        is_primary TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_id (user_id),
        KEY is_primary (is_primary)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    
    dbDelta($sql1); // فیلدها
    dbDelta($sql10);
    dbDelta($sql9);
    dbDelta($sql2); // ادارات
    dbDelta($sql3); // پرسنل
    dbDelta($sql4); // personnel_meta
    dbDelta($sql5); // دوره‌ها
    dbDelta($sql6); // تاییدها
    dbDelta($sql7); // لاگ
    dbDelta($sql8); // قالب اکسل
    dbDelta($sql_org_managers); // مدیران سازمان - این خط مهم است!

    // ایجاد فیلدهای پیش‌فرض
    workforce_create_default_fields();
    
    // ایجاد قالب پیش‌فرض اکسل
    workforce_create_default_excel_template();
        // تابع کمکی برای لاگ

}

/**
 * ایجاد فیلدهای پیش‌فرض
 */

function workforce_create_default_fields() {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'fields';
    
    // فقط بررسی کن که آیا فیلدهای پیش‌فرض وجود دارند یا نه
    $existing_defaults = $wpdb->get_results(
        "SELECT * FROM $table_name WHERE field_name IN ('national_code', 'first_name', 'last_name')"
    );
    
    if (count($existing_defaults) >= 3) {
        return; // فیلدهای پیش‌فرض قبلاً ایجاد شده‌اند
    }
    
    // اگر تعداد فیلدهای موجود صفر است یا فیلدهای پیش‌فرض کامل نیستند
    $default_fields = [
        [
            'field_name' => 'national_code',
            'field_label' => 'کد ملی',
            'field_type' => 'text',
            'is_required' => 1,
            'is_locked' => 1,
            'is_monitoring' => 1,
            'is_key' => 1,
            'display_order' => 1,
        ],
        [
            'field_name' => 'first_name',
            'field_label' => 'نام',
            'field_type' => 'text',
            'is_required' => 1,
            'is_locked' => 0,
            'is_monitoring' => 1,
            'is_key' => 0,
            'display_order' => 2,
        ],
        [
            'field_name' => 'last_name',
            'field_label' => 'نام خانوادگی',
            'field_type' => 'text',
            'is_required' => 1,
            'is_locked' => 0,
            'is_monitoring' => 1,
            'is_key' => 0,
            'display_order' => 3,
        ],
        [
            'field_name' => 'father_name',
            'field_label' => 'نام پدر',
            'field_type' => 'text',
            'is_required' => 1,
            'is_locked' => 0,
            'is_monitoring' => 0,
            'is_key' => 0,
            'display_order' => 4,
        ],
        [
            'field_name' => 'birth_date',
            'field_label' => 'تاریخ تولد',
            'field_type' => 'date',
            'is_required' => 1,
            'is_locked' => 1,
            'is_monitoring' => 0,
            'is_key' => 0,
            'display_order' => 5,
        ],
        [
            'field_name' => 'employment_date',
            'field_label' => 'تاریخ استخدام',
            'field_type' => 'date',
            'is_required' => 1,
            'is_locked' => 1,
            'is_monitoring' => 1,
            'is_key' => 0,
            'display_order' => 6,
        ],
        [
            'field_name' => 'mobile',
            'field_label' => 'موبایل',
            'field_type' => 'text',
            'is_required' => 1,
            'is_locked' => 0,
            'is_monitoring' => 0,
            'is_key' => 0,
            'display_order' => 7,
        ],
        [
            'field_name' => 'email',
            'field_label' => 'ایمیل',
            'field_type' => 'text',
            'is_required' => 0,
            'is_locked' => 0,
            'is_monitoring' => 0,
            'is_key' => 0,
            'display_order' => 8,
        ],
    ];
    
    foreach ($default_fields as $field) {
        // بررسی کن که آیا این فیلد قبلاً وجود دارد یا نه
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE field_name = %s",
            $field['field_name']
        ));
        
        if (!$exists) {
            $wpdb->insert($table_name, $field);
        }
    }
}

/**
 * ایجاد قالب پیش‌فرض اکسل
 */
function workforce_create_default_excel_template() {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'excel_templates';
    
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE is_default = 1");
    
    if ($count == 0) {
        $wpdb->insert(
            $table_name,
            [
                'name' => 'قالب پیش‌فرض',
                'header_color' => '#2c3e50',
                'text_color' => '#333333',
                'even_row_color' => '#f8f9fa',
                'odd_row_color' => '#ffffff',
                'border_style' => 'thin',
                'border_color' => '#dddddd',
                'header_font_size' => 12,
                'data_font_size' => 11,
                'is_default' => 1,
            ]
        );
    }
}

/**
 * CRUD فیلدها
 */
function workforce_add_field($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'fields';
    
    // تولید نام فیلد از روی عنوان فارسی
    $field_name = 'field_' . time() . '_' . rand(100, 999);
    
    $result = $wpdb->insert(
        $table_name,
        [
            'field_name' => $field_name,
            'field_label' => $data['field_label'],
            'field_type' => $data['field_type'],
            'is_required' => isset($data['is_required']) ? 1 : 0,
            'is_locked' => isset($data['is_locked']) ? 1 : 0,
            'is_monitoring' => isset($data['is_monitoring']) ? 1 : 0,
            'is_key' => isset($data['is_key']) ? 1 : 0,
            'options' => isset($data['options']) ? serialize($data['options']) : null,
            'display_order' => $data['display_order'] ?? 999,
        ]
    );
    
    return $result ? $wpdb->insert_id : false;
}

function workforce_update_field($id, $data) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'fields';
    
    $update_data = [
        'field_label' => $data['field_label'],
        'field_type' => $data['field_type'],
        'is_required' => isset($data['is_required']) ? 1 : 0,
        'is_locked' => isset($data['is_locked']) ? 1 : 0,
        'is_monitoring' => isset($data['is_monitoring']) ? 1 : 0,
        'is_key' => isset($data['is_key']) ? 1 : 0,
        'options' => isset($data['options']) ? serialize($data['options']) : null,
        'display_order' => $data['display_order'] ?? 999,
        'updated_at' => current_time('mysql'),
    ];
    
    return $wpdb->update($table_name, $update_data, ['id' => $id]);
}

function workforce_delete_field($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'fields';
    
    // حذف داده‌های مرتبط از جدول personnel_meta
    $meta_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel_meta';
    $wpdb->delete($meta_table, ['field_id' => $id]);
    
    return $wpdb->delete($table_name, ['id' => $id]);
}

function workforce_get_field($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'fields';
    
    $field = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    
    if ($field && $field->options) {
        $field->options = unserialize($field->options);
    }
    
    return $field;
}

function workforce_get_all_fields($order_by = 'display_order') {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'fields';
    
    $fields = $wpdb->get_results("SELECT * FROM $table_name ORDER BY $order_by ASC");
    
    foreach ($fields as $field) {
        if ($field->options) {
            $field->options = unserialize($field->options);
        }
    }
    
    return $fields;
}

/**
 * CRUD ادارات
 */
function workforce_add_department($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'departments';
    
    $result = $wpdb->insert(
        $table_name,
        [
            'name' => $data['name'],
            'manager_id' => $data['manager_id'] ?? null,
            'color' => $data['color'] ?? workforce_generate_random_color(),
            'parent_id' => $data['parent_id'] ?? 0,
        ]
    );
    
    return $result ? $wpdb->insert_id : false;
}

function workforce_update_department($id, $data) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'departments';
    
    $update_data = [
        'name' => $data['name'] ?? null,
        'color' => $data['color'] ?? null,
        'parent_id' => $data['parent_id'] ?? 0,
        'updated_at' => current_time('mysql'),
    ];
    
    // اگر manager_id در data وجود دارد (برای سازگاری با کد قدیمی)
    if (isset($data['manager_id'])) {
        $update_data['manager_id'] = $data['manager_id'];
    }
    
    // حذف فیلدهای null
    $update_data = array_filter($update_data, function($value) {
        return !is_null($value);
    });
    
    $result = $wpdb->update($table_name, $update_data, ['id' => $id]);
    
    return $result;
}

function workforce_delete_department($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'departments';
    
    // بررسی وجود پرسنل در این اداره
    $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
    $personnel_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $personnel_table WHERE department_id = %d AND is_deleted = 0",
        $id
    ));
    
    if ($personnel_count > 0) {
        return false; // نمی‌توان اداره‌ای که پرسنل دارد را حذف کرد
    }
    
    return $wpdb->delete($table_name, ['id' => $id]);
}

function workforce_get_department($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'departments';
    
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
}

function workforce_get_all_departments($include_inactive = false) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'departments';
    
    $where = $include_inactive ? '' : 'WHERE is_active = 1';
    return $wpdb->get_results("SELECT * FROM $table_name $where ORDER BY parent_id ASC, name ASC");
}
/**
 * گرفتن مدیران سازمان
 */
function workforce_get_org_managers() {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'organization_managers';
    
    return $wpdb->get_results(
        "SELECT * FROM $table_name ORDER BY is_primary DESC, created_at ASC"
    );
}

/**
 * تنظیم مدیران سازمان
 */
function workforce_set_org_managers($user_ids) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'organization_managers';
    
    // حذف مدیران قبلی
    $wpdb->query("DELETE FROM $table_name");
    
    // اضافه کردن مدیران جدید
    $is_primary = true;
    foreach ($user_ids as $user_id) {
        $wpdb->insert($table_name, [
            'user_id' => $user_id,
            'is_primary' => $is_primary ? 1 : 0
        ]);
        $is_primary = false;
    }
    
    return true;
}

/**
 * بررسی آیا کاربر مدیر سازمان است
 */
/**
 * بررسی آیا کاربر مدیر سازمان است
 */
function workforce_is_org_manager($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'organization_managers';
    
    $current_user = get_userdata($user_id);
    
    // ادمین هم مدیر سازمان محسوب می‌شود
    if ($current_user && in_array('administrator', $current_user->roles)) {
        return true;
    }
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
        $user_id
    ));
    
    return $count > 0;
}
function workforce_get_user_departments($user_id) {
    global $wpdb;
    $departments_table = $wpdb->prefix . WF_TABLE_PREFIX . 'departments';
    $managers_table = $wpdb->prefix . WF_TABLE_PREFIX . 'department_managers';
    
    $current_user = wp_get_current_user();
    
    // 1. ادمین همه ادارات را می‌بیند
    if (in_array('administrator', $current_user->roles)) {
        $departments = workforce_get_all_departments();
        // اضافه کردن اطلاعات مدیران
        foreach ($departments as $dept) {
            $dept->managers = workforce_get_department_managers($dept->id);
        }
        return $departments;
    }
    
    // 2. مدیر سازمان همه ادارات را می‌بیند
    if (workforce_is_org_manager($user_id)) {
        $departments = workforce_get_all_departments();
        // اضافه کردن اطلاعات مدیران
        foreach ($departments as $dept) {
            $dept->managers = workforce_get_department_managers($dept->id);
        }
        return $departments;
    }
    
    // 3. مدیر اداره ادارات خودش را می‌بیند
    $departments = $wpdb->get_results($wpdb->prepare(
        "SELECT d.*, dm.is_primary 
         FROM $departments_table d 
         INNER JOIN $managers_table dm ON d.id = dm.department_id 
         WHERE dm.user_id = %d AND d.is_active = 1 
         ORDER BY dm.is_primary DESC, d.name ASC",
        $user_id
    ));
    
    // اضافه کردن اطلاعات مدیران دیگر
    foreach ($departments as $dept) {
        $dept->managers = workforce_get_department_managers($dept->id);
    }
    
    return $departments;
}

function workforce_get_department_personnel_count($department_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
    
    return $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE department_id = %d AND is_deleted = 0",
        $department_id
    ));
}

/**
 * CRUD پرسنل
 */
function workforce_add_personnel($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
    
    $result = $wpdb->insert(
        $table_name,
        [
            'department_id' => $data['department_id'],
            'national_code' => $data['national_code'] ?? null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'employment_date' => $data['employment_date'] ?? null,
            'employment_type' => $data['employment_type'] ?? 'permanent',
            'status' => $data['status'] ?? 'active',
            'created_by' => get_current_user_id(),
        ]
    );
    
    if (!$result) {
        return false;
    }
    
    $personnel_id = $wpdb->insert_id;
    
    // ذخیره فیلدهای متا
    if (isset($data['meta']) && is_array($data['meta'])) {
        foreach ($data['meta'] as $field_id => $value) {
            if (!empty($value)) {
                $field = workforce_get_field($field_id);
                if ($field) {
                    workforce_add_personnel_meta($personnel_id, $field_id, $field->field_name, $value);
                }
            }
        }
    }
    
    // لاگ فعالیت
    workforce_log_activity(
        get_current_user_id(),
        'add_personnel',
        "افزودن پرسنل: {$data['first_name']} {$data['last_name']} (ID: $personnel_id)"
    );
    
    return $personnel_id;
}

function workforce_update_personnel($id, $data) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
    
    $update_data = [
        'department_id' => $data['department_id'] ?? null,
        'national_code' => $data['national_code'] ?? null,
        'first_name' => $data['first_name'] ?? null,
        'last_name' => $data['last_name'] ?? null,
        'employment_date' => $data['employment_date'] ?? null,
        'employment_type' => $data['employment_type'] ?? null,
        'status' => $data['status'] ?? null,
        'updated_by' => get_current_user_id(),
        'updated_at' => current_time('mysql'),
    ];
    
    // حذف فیلدهای null
    $update_data = array_filter($update_data, function($value) {
        return !is_null($value);
    });
    
    $result = $wpdb->update($table_name, $update_data, ['id' => $id]);
    
    // به‌روزرسانی فیلدهای متا
    if (isset($data['meta']) && is_array($data['meta'])) {
        foreach ($data['meta'] as $field_id => $value) {
            $field = workforce_get_field($field_id);
            if ($field) {
                workforce_update_personnel_meta($id, $field_id, $field->field_name, $value);
            }
        }
    }
    
    // لاگ فعالیت
    workforce_log_activity(
        get_current_user_id(),
        'update_personnel',
        "ویرایش پرسنل ID: $id"
    );
    
    return $result;
}

function workforce_delete_personnel($id, $soft_delete = true) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
    
    if ($soft_delete) {
        $result = $wpdb->update(
            $table_name,
            ['is_deleted' => 1, 'updated_at' => current_time('mysql')],
            ['id' => $id]
        );
    } else {
        // حذف فیزیکی
        $result = $wpdb->delete($table_name, ['id' => $id]);
        
        // حذف داده‌های متا
        $meta_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel_meta';
        $wpdb->delete($meta_table, ['personnel_id' => $id]);
    }
    
    // لاگ فعالیت
    workforce_log_activity(
        get_current_user_id(),
        'delete_personnel',
        "حذف پرسنل ID: $id" . ($soft_delete ? ' (موقت)' : ' (دائمی)')
    );
    
    return $result;
}

function workforce_get_personnel($id, $include_deleted = false) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
    
    $where = $include_deleted ? 'id = %d' : 'id = %d AND is_deleted = 0';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE $where", $id));
}

function workforce_get_personnel_by_department($department_id, $filters = [], $limit = 25, $offset = 0) {
    global $wpdb;
    $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
    $meta_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel_meta';
    
    // ساختن کوئری پایه
    $query = "SELECT p.* FROM $personnel_table p WHERE p.department_id = %d AND p.is_deleted = 0";
    $params = [$department_id];
    
    // اعمال فیلترها
    if (!empty($filters)) {
        foreach ($filters as $field_name => $value) {
            if ($field_name === 'status') {
                $query .= " AND p.status = %s";
                $params[] = $value;
            } elseif ($field_name === 'search') {
                $query .= " AND (p.first_name LIKE %s OR p.last_name LIKE %s OR p.national_code LIKE %s)";
                $search_term = '%' . $wpdb->esc_like($value) . '%';
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
            } elseif ($field_name === 'employment_type') {
                $query .= " AND p.employment_type = %s";
                $params[] = $value;
            }
        }
    }
    
    // مرتب‌سازی و محدودیت
    $query .= " ORDER BY p.last_name ASC, p.first_name ASC LIMIT %d OFFSET %d";
    $params[] = $limit;
    $params[] = $offset;
    
    $personnel = $wpdb->get_results($wpdb->prepare($query, $params));
    
    // اضافه کردن داده‌های متا
    foreach ($personnel as &$person) {
        $person->meta = workforce_get_personnel_meta($person->id);
    }
    
    return $personnel;
}

function workforce_get_personnel_count($department_id = null, $filters = []) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
    
    $query = "SELECT COUNT(*) FROM $table_name WHERE is_deleted = 0";
    $params = [];
    
    if ($department_id) {
        $query .= " AND department_id = %d";
        $params[] = $department_id;
    }
    
    if (!empty($filters)) {
        foreach ($filters as $field_name => $value) {
            if ($field_name === 'status') {
                $query .= " AND status = %s";
                $params[] = $value;
            } elseif ($field_name === 'employment_type') {
                $query .= " AND employment_type = %s";
                $params[] = $value;
            }
        }
    }
    
    if (!empty($params)) {
        return $wpdb->get_var($wpdb->prepare($query, $params));
    }
    
    return $wpdb->get_var($query);
}

/**
 * مدیریت داده‌های متا پرسنل
 */
function workforce_add_personnel_meta($personnel_id, $field_id, $meta_key, $meta_value, $period_id = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel_meta';
    
    return $wpdb->insert(
        $table_name,
        [
            'personnel_id' => $personnel_id,
            'field_id' => $field_id,
            'meta_key' => $meta_key,
            'meta_value' => $meta_value,
            'period_id' => $period_id,
        ]
    );
}

function workforce_update_personnel_meta($personnel_id, $field_id, $meta_key, $meta_value, $period_id = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel_meta';
    
    // بررسی وجود رکورد
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $table_name WHERE personnel_id = %d AND field_id = %d AND period_id " . ($period_id ? "= %d" : "IS NULL"),
        $period_id ? [$personnel_id, $field_id, $period_id] : [$personnel_id, $field_id]
    ));
    
    if ($existing) {
        return $wpdb->update(
            $table_name,
            ['meta_value' => $meta_value, 'updated_at' => current_time('mysql')],
            ['id' => $existing->id]
        );
    } else {
        return workforce_add_personnel_meta($personnel_id, $field_id, $meta_key, $meta_value, $period_id);
    }
}

function workforce_get_personnel_meta($personnel_id, $period_id = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel_meta';
    
    $where = $period_id ? "personnel_id = %d AND period_id = %d" : "personnel_id = %d AND period_id IS NULL";
    $params = $period_id ? [$personnel_id, $period_id] : [$personnel_id];
    
    $meta = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE $where",
        $params
    ));
    
    $result = [];
    foreach ($meta as $item) {
        $result[$item->field_id] = $item->meta_value;
        $result[$item->meta_key] = $item->meta_value;
    }
    
    return $result;
}

function workforce_get_personnel_field_value($personnel_id, $field_name, $period_id = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel_meta';
    
    $where = $period_id ? "personnel_id = %d AND meta_key = %s AND period_id = %d" : "personnel_id = %d AND meta_key = %s AND period_id IS NULL";
    $params = $period_id ? [$personnel_id, $field_name, $period_id] : [$personnel_id, $field_name];
    
    return $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM $table_name WHERE $where",
        $params
    ));
}

/**
 * مدیریت دوره‌های کاری
 */
function workforce_add_period($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'periods';
    
    // غیرفعال کردن سایر دوره‌ها اگر این دوره فعال است
    if (isset($data['is_active']) && $data['is_active']) {
        $wpdb->update($table_name, ['is_active' => 0], ['is_active' => 1]);
    }
    
    $result = $wpdb->insert(
        $table_name,
        [
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'is_active' => isset($data['is_active']) ? 1 : 0,
        ]
    );
    
    return $result ? $wpdb->insert_id : false;
}

function workforce_update_period($id, $data) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'periods';
    
    // غیرفعال کردن سایر دوره‌ها اگر این دوره فعال است
    if (isset($data['is_active']) && $data['is_active']) {
        $wpdb->update($table_name, ['is_active' => 0], ['is_active' => 1]);
    }
    
    $update_data = [
        'name' => $data['name'] ?? null,
        'start_date' => $data['start_date'] ?? null,
        'end_date' => $data['end_date'] ?? null,
        'is_active' => isset($data['is_active']) ? 1 : 0,
        'updated_at' => current_time('mysql'),
    ];
    
    $update_data = array_filter($update_data, function($value) {
        return !is_null($value);
    });
    
    return $wpdb->update($table_name, $update_data, ['id' => $id]);
}

function workforce_delete_period($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'periods';
    
    // بررسی وجود داده در این دوره
    $meta_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel_meta';
    $data_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $meta_table WHERE period_id = %d",
        $id
    ));
    
    if ($data_count > 0) {
        return false; // نمی‌توان دوره‌ای که داده دارد را حذف کرد
    }
    
    return $wpdb->delete($table_name, ['id' => $id]);
}

function workforce_get_active_period() {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'periods';
    
    return $wpdb->get_row("SELECT * FROM $table_name WHERE is_active = 1 LIMIT 1");
}

function workforce_get_all_periods() {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'periods';
    
    return $wpdb->get_results("SELECT * FROM $table_name ORDER BY start_date DESC");
}

/**
 * مدیریت درخواست‌های تایید
 */
function workforce_add_approval_request($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'approvals';
    
    $result = $wpdb->insert(
        $table_name,
        [
            'request_type' => $data['request_type'],
            'requester_id' => $data['requester_id'],
            'target_id' => $data['target_id'] ?? null,
            'target_type' => $data['target_type'] ?? null,
            'data_before' => isset($data['data_before']) ? serialize($data['data_before']) : null,
            'data_after' => isset($data['data_after']) ? serialize($data['data_after']) : null,
            'status' => 'pending',
        ]
    );
    
    return $result ? $wpdb->insert_id : false;
}

function workforce_update_approval_request($id, $data) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'approvals';
    
    $update_data = [
        'status' => $data['status'] ?? null,
        'admin_notes' => $data['admin_notes'] ?? null,
        'reviewer_id' => $data['reviewer_id'] ?? null,
        'reviewed_at' => isset($data['status']) ? current_time('mysql') : null,
        'updated_at' => current_time('mysql'),
    ];
    
    $update_data = array_filter($update_data, function($value) {
        return !is_null($value);
    });
    
    return $wpdb->update($table_name, $update_data, ['id' => $id]);
}

function workforce_get_pending_approvals($limit = 50, $offset = 0) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'approvals';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE status = 'pending' ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $limit, $offset
    ));
}

function workforce_get_approval_count($status = 'pending') {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'approvals';
    
    return $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE status = %s",
        $status
    ));
}

/**
 * مدیریت قالب‌های اکسل
 */
function workforce_save_excel_template($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'excel_templates';
    
    // اگر قالب پیش‌فرض است، سایر قالب‌ها را غیرپیش‌فرض کنیم
    if (isset($data['is_default']) && $data['is_default']) {
        $wpdb->update($table_name, ['is_default' => 0], ['is_default' => 1]);
    }
    
    if (isset($data['id'])) {
        // ویرایش قالب موجود
        $result = $wpdb->update(
            $table_name,
            [
                'name' => $data['name'],
                'header_color' => $data['header_color'],
                'text_color' => $data['text_color'],
                'even_row_color' => $data['even_row_color'],
                'odd_row_color' => $data['odd_row_color'],
                'border_style' => $data['border_style'],
                'border_color' => $data['border_color'],
                'header_font_size' => $data['header_font_size'],
                'data_font_size' => $data['data_font_size'],
                'is_default' => isset($data['is_default']) ? 1 : 0,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $data['id']]
        );
        
        return $result ? $data['id'] : false;
    } else {
        // ایجاد قالب جدید
        $result = $wpdb->insert(
            $table_name,
            [
                'name' => $data['name'],
                'header_color' => $data['header_color'],
                'text_color' => $data['text_color'],
                'even_row_color' => $data['even_row_color'],
                'odd_row_color' => $data['odd_row_color'],
                'border_style' => $data['border_style'],
                'border_color' => $data['border_color'],
                'header_font_size' => $data['header_font_size'],
                'data_font_size' => $data['data_font_size'],
                'is_default' => isset($data['is_default']) ? 1 : 0,
                'created_by' => get_current_user_id(),
            ]
        );
        
        return $result ? $wpdb->insert_id : false;
    }
}

function workforce_get_excel_template($id = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'excel_templates';
    
    if ($id) {
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    } else {
        // قالب پیش‌فرض
        return $wpdb->get_row("SELECT * FROM $table_name WHERE is_default = 1 LIMIT 1");
    }
}

function workforce_get_all_excel_templates() {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'excel_templates';
    
    return $wpdb->get_results("SELECT * FROM $table_name ORDER BY is_default DESC, name ASC");
}

function workforce_delete_excel_template($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'excel_templates';
    
    // بررسی پیش‌فرض نبودن
    $template = workforce_get_excel_template($id);
    if ($template && $template->is_default) {
        return false; // نمی‌توان قالب پیش‌فرض را حذف کرد
    }
    
    return $wpdb->delete($table_name, ['id' => $id]);
}

/**
 * گرفتن آمار کلی
 */
function workforce_get_overall_stats() {
    global $wpdb;
    
    $departments_table = $wpdb->prefix . WF_TABLE_PREFIX . 'departments';
    $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
    $fields_table = $wpdb->prefix . WF_TABLE_PREFIX . 'fields';
    $approvals_table = $wpdb->prefix . WF_TABLE_PREFIX . 'approvals';
    
    $stats = [
        'departments' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $departments_table WHERE is_active = 1"),
        'personnel' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $personnel_table WHERE is_deleted = 0"),
        'fields' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $fields_table"),
        'pending_approvals' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $approvals_table WHERE status = 'pending'"),
        'active_personnel' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $personnel_table WHERE status = 'active' AND is_deleted = 0"),
        'inactive_personnel' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $personnel_table WHERE status != 'active' AND is_deleted = 0"),
    ];
    
    return $stats;
}


/**
 * گرفتن گزارشات مدیر سازمان
 */
function workforce_get_org_manager_stats() {
    global $wpdb;
    
    $departments_table = $wpdb->prefix . WF_TABLE_PREFIX . 'departments';
    $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
    
    $stats = [];
    
    // آمار هر اداره
    $departments = $wpdb->get_results("
        SELECT d.*, 
               COUNT(p.id) as personnel_count,
               SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END) as active_count,
               SUM(CASE WHEN p.status != 'active' THEN 1 ELSE 0 END) as inactive_count
        FROM $departments_table d
        LEFT JOIN $personnel_table p ON d.id = p.department_id AND p.is_deleted = 0
        GROUP BY d.id
        ORDER BY d.parent_id ASC, d.name ASC
    ");
    
    // رفع خطا: بررسی خالی بودن نتیجه
    if (empty($departments)) {
        $departments = [];
    }
    
    $stats['departments'] = [];
    
    foreach ($departments as $dept) {
        $stats['departments'][] = [
            'id' => $dept->id,
            'name' => $dept->name,
            'manager' => $dept->manager_id ? get_userdata($dept->manager_id)->display_name : 'تعیین نشده',
            'color' => $dept->color,
            'personnel_count' => (int) $dept->personnel_count,
            'active_count' => (int) $dept->active_count,
            'inactive_count' => (int) $dept->inactive_count,
            'completion_rate' => $dept->personnel_count > 0 ? round(($dept->active_count / $dept->personnel_count) * 100, 2) : 0,
        ];
    }
    
    // آمار کلی
    $stats['overall'] = [
        'total_departments' => count($stats['departments']),
        'total_personnel' => array_sum(array_column($stats['departments'], 'personnel_count')),
        'total_active' => array_sum(array_column($stats['departments'], 'active_count')),
        'avg_completion_rate' => count($stats['departments']) > 0 ? 
            round(array_sum(array_column($stats['departments'], 'completion_rate')) / count($stats['departments']), 2) : 0,
    ];
    
    return $stats;
}

/**
 * بهینه‌سازی پایگاه داده
 */
function workforce_optimize_tables() {
    global $wpdb;
    $tables = [
        $wpdb->prefix . WF_TABLE_PREFIX . 'fields',
        $wpdb->prefix . WF_TABLE_PREFIX . 'departments',
        $wpdb->prefix . WF_TABLE_PREFIX . 'personnel',
        $wpdb->prefix . WF_TABLE_PREFIX . 'personnel_meta',
        $wpdb->prefix . WF_TABLE_PREFIX . 'periods',
        $wpdb->prefix . WF_TABLE_PREFIX . 'approvals',
        $wpdb->prefix . WF_TABLE_PREFIX . 'activity_logs',
        $wpdb->prefix . WF_TABLE_PREFIX . 'excel_templates',
    ];
    
    foreach ($tables as $table) {
        $wpdb->query("OPTIMIZE TABLE $table");
    }
    
    return true;
}

/**
 * پاکسازی داده‌های قدیمی
 */
function workforce_cleanup_old_data($days_old = 90) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'activity_logs';
    
    $date = date('Y-m-d H:i:s', strtotime("-$days_old days"));
    
    return $wpdb->query($wpdb->prepare(
        "DELETE FROM $table_name WHERE created_at < %s",
        $date
    ));
}
