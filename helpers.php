<?php
/**
 * توابع کمکی پلاگین مدیریت کارکرد پرسنل
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}
add_action('wp_ajax_workforce_debug_all', function() {
    header('Content-Type: application/json');
    
    $response = [
        'server' => [
            'php_version' => phpversion(),
            'wordpress_version' => get_bloginfo('version'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize')
        ],
        'request' => [
            'method' => $_SERVER['REQUEST_METHOD'],
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'N/A',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
            'post_data' => $_POST,
            'get_data' => $_GET
        ],
        'wordpress' => [
            'ajax_url' => admin_url('admin-ajax.php'),
            'site_url' => site_url(),
            'home_url' => home_url(),
            'is_admin' => is_admin(),
            'is_user_logged_in' => is_user_logged_in(),
            'current_user_id' => get_current_user_id()
        ],
        'plugin' => [
            'version' => WF_PLUGIN_VERSION,
            'tables_exist' => []
        ]
    ];
    
    // بررسی جداول
    global $wpdb;
    $tables = ['fields', 'departments', 'personnel', 'personnel_meta', 'periods', 'approvals'];
    
    foreach ($tables as $table) {
        $table_name = $wpdb->prefix . WF_TABLE_PREFIX . $table;
        $response['plugin']['tables_exist'][$table] = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    wp_die();
});
/**
 * اعتبارسنجی کدملی (شناسه ملی)
 */
function workforce_validate_national_code($code) {
    if (!preg_match('/^[0-9]{10}$/', $code)) {
        return false;
    }
    
    $check = (int) $code[9];
    $sum = 0;
    
    for ($i = 0; $i < 9; $i++) {
        $sum += (int) $code[$i] * (10 - $i);
    }
    
    $remain = $sum % 11;
    
    if ($remain < 2) {
        return $check == $remain;
    } else {
        return $check == 11 - $remain;
    }
}

/**
 * تبدیل تاریخ میلادی به شمسی
 */
function workforce_gregorian_to_jalali($gy, $gm, $gd) {
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    
    if ($gy > 1600) {
        $jy = 979;
        $gy -= 1600;
    } else {
        $jy = 0;
        $gy -= 621;
    }
    
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100)) + ((int)(($gy2 + 399) / 400)) - 80 + $gd + $g_d_m[$gm - 1];
    $jy += 33 * ((int)($days / 12053));
    $days %= 12053;
    $jy += 4 * ((int)($days / 1461));
    $days %= 1461;
    
    if ($days > 365) {
        $jy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    
    $jm = ($days < 186) ? 1 + (int)($days / 31) : 7 + (int)(($days - 186) / 30);
    $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));
    
    return [$jy, $jm, $jd];
}

/**
 * تبدیل تاریخ شمسی به میلادی
 */
function workforce_jalali_to_gregorian($jy, $jm, $jd) {
    if ($jy > 979) {
        $gy = 1600;
        $jy -= 979;
    } else {
        $gy = 621;
    }
    
    $days = (365 * $jy) + (((int)($jy / 33)) * 8) + ((int)((($jy % 33) + 3) / 4)) + 78 + $jd + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
    $gy += 400 * ((int)($days / 146097));
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
    $gm = 0;
    
    foreach ([31, ((($gy % 4 == 0) && ($gy % 100 != 0)) || ($gy % 400 == 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31] as $v) {
        if ($gd <= $v) break;
        $gd -= $v;
        $gm++;
    }
    
    return [$gy, $gm + 1, $gd];
}

/**
 * تاریخ امروز به شمسی
 */
function workforce_today_jalali() {
    $current_date = current_time('timestamp');
    $year = date('Y', $current_date);
    $month = date('m', $current_date);
    $day = date('d', $current_date);
    
    list($jy, $jm, $jd) = workforce_gregorian_to_jalali($year, $month, $day);
    
    return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
}

/**
 * اعتبارسنجی تاریخ شمسی
 */
function workforce_validate_jalali_date($date) {
    if (!preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', $date, $matches)) {
        return false;
    }
    
    $year = (int) $matches[1];
    $month = (int) $matches[2];
    $day = (int) $matches[3];
    
    if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
        return false;
    }
    
    if ($month > 6 && $day > 30) {
        return false;
    }
    
    if ($month == 12 && $day > 29) {
        if (!workforce_is_leap_year($year) || $day > 30) {
            return false;
        }
    }
    
    return true;
}

/**
 * بررسی سال کبیسه شمسی
 */
function workforce_is_leap_year($year) {
    $a = $year % 33;
    return in_array($a, [1, 5, 9, 13, 17, 22, 26, 30]);
}

/**
 * امن‌سازی ورودی‌ها
 */
function workforce_sanitize_input($input) {
    if (is_array($input)) {
        return array_map('workforce_sanitize_input', $input);
    }
    
    $input = wp_unslash($input);
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    return $input;
}

/**
 * تولید توکن امنیتی
 */
function workforce_generate_token($length = 32) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    
    return $randomString;
}

/**
 * لاگ‌گیری فعالیت‌ها
 */
function workforce_log_activity($user_id, $action, $details = '') {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'activity_logs';
    
    $wpdb->insert(
        $table_name,
        [
            'user_id' => $user_id,
            'action' => $action,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'created_at' => current_time('mysql'),
        ],
        ['%d', '%s', '%s', '%s', '%s']
    );
}

/**
 * دریافت اطلاعات کاربر
 */
function workforce_get_user_info($user_id) {
    $user = get_userdata($user_id);
    
    if (!$user) {
        return null;
    }
    
    return [
        'id' => $user->ID,
        'username' => $user->user_login,
        'display_name' => $user->display_name,
        'email' => $user->user_email,
        'roles' => $user->roles,
    ];
}

/**
 * بررسی دسترسی کاربر
 */
function workforce_check_access($user_id, $required_role) {
    $user = get_userdata($user_id);
    
    if (!$user) {
        return false;
    }
    
    // ادمین وردپرس دسترسی کامل دارد
    if (in_array('administrator', $user->roles)) {
        return true;
    }
    
    return in_array($required_role, $user->roles);
}

/**
 * نمایش اعلان
 */
function workforce_show_notice($message, $type = 'success') {
    $classes = [
        'success' => 'notice notice-success is-dismissible',
        'error' => 'notice notice-error is-dismissible',
        'warning' => 'notice notice-warning is-dismissible',
        'info' => 'notice notice-info is-dismissible',
    ];
    
    $class = $classes[$type] ?? $classes['info'];
    
    return sprintf('<div class="%s"><p>%s</p></div>', $class, $message);
}

/**
 * فرمت اعداد فارسی
 */
function workforce_format_number($number) {
    $persian_numbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english_numbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    
    return str_replace($english_numbers, $persian_numbers, $number);
}

/**
 * محدود کردن متن
 */
function workforce_truncate_text($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text, 'UTF-8') <= $length) {
        return $text;
    }
    
    $text = mb_substr($text, 0, $length, 'UTF-8');
    return $text . $suffix;
}

/**
 * بررسی وجود فیلد تکراری
 */
function workforce_check_duplicate_key($field_name, $value, $exclude_id = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
    
    $query = "SELECT COUNT(*) FROM $table_name WHERE meta_key = %s AND meta_value = %s";
    $params = [$field_name, $value];
    
    if ($exclude_id) {
        $query .= " AND id != %d";
        $params[] = $exclude_id;
    }
    
    $count = $wpdb->get_var($wpdb->prepare($query, $params));
    
    return $count > 0;
}

/**
 * تولید رنگ تصادفی
 */
function workforce_generate_random_color() {
    $colors = [
        '#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6',
        '#1abc9c', '#d35400', '#c0392b', '#16a085', '#8e44ad',
        '#27ae60', '#2980b9', '#f1c40f', '#e67e22', '#95a5a6',
    ];
    
    return $colors[array_rand($colors)];
}

/**
 * محاسبه سن از روی تاریخ تولد
 */
function workforce_calculate_age($birth_date) {
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
 * گرفتن تاریخ فارسی کامل
 */
function workforce_get_full_jalali_date($timestamp = null) {
    if (!$timestamp) {
        $timestamp = current_time('timestamp');
    }
    
    $year = date('Y', $timestamp);
    $month = date('m', $timestamp);
    $day = date('d', $timestamp);
    
    list($jy, $jm, $jd) = workforce_gregorian_to_jalali($year, $month, $day);
    
    $persian_months = [
        'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
    ];
    
    $persian_days = [
        'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه',
        'پنجشنبه', 'جمعه', 'شنبه'
    ];
    
    $day_of_week = date('w', $timestamp);
    $day_of_week = $day_of_week == 0 ? 6 : $day_of_week - 1;
    
    return sprintf(
        '%s %d %s %d',
        $persian_days[$day_of_week],
        $jd,
        $persian_months[$jm - 1],
        $jy
    );
}
/**
 * گرفتن مدیران یک اداره
 */
function workforce_get_department_managers($department_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'department_managers';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE department_id = %d ORDER BY is_primary DESC, created_at ASC",
        $department_id
    ));
}
/**
 * نمایش لیست مدیران یک اداره
 */
function workforce_display_managers($department_id, $max_display = 0) {
    $managers = workforce_get_department_managers($department_id);
    
    if (empty($managers)) {
        return 'تعیین نشده';
    }
    
    $manager_names = [];
    foreach ($managers as $manager) {
        $user = get_userdata($manager->user_id);
        if ($user) {
            $prefix = $manager->is_primary ? '⭐ ' : '';
            $manager_names[] = $prefix . $user->display_name;
        }
    }
    
    // اگر max_display مشخص شده و تعداد مدیران بیشتر از آن است
    if ($max_display > 0 && count($manager_names) > $max_display) {
        $display = array_slice($manager_names, 0, $max_display);
        return implode('، ', $display) . ' و ' . (count($manager_names) - $max_display) . ' نفر دیگر';
    }
    
    // نمایش همه مدیران
    return implode('، ', $manager_names);
}
/**
 * تنظیم مدیران یک اداره
 */
function workforce_set_department_managers($department_id, $user_ids) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'department_managers';
    
    // حذف مدیران قبلی
    $wpdb->delete($table_name, ['department_id' => $department_id]);
    
    // اضافه کردن مدیران جدید
    $is_primary = true;
    foreach ($user_ids as $user_id) {
        $wpdb->insert($table_name, [
            'department_id' => $department_id,
            'user_id' => $user_id,
            'is_primary' => $is_primary ? 1 : 0
        ]);
        $is_primary = false;
    }
    
    return true;
}


/**
 * بررسی آیا کاربر مدیر یک اداره است
 */
function workforce_is_department_manager($user_id, $department_id = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'department_managers';
    
    if ($department_id) {
        // بررسی برای اداره خاص
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE department_id = %d AND user_id = %d",
            $department_id, $user_id
        ));
        return $count > 0;
    } else {
        // بررسی برای هر اداره‌ای
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        return $count > 0;
    }
}
