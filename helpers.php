<?php
/**
 * توابع کمکی و ابزاری - پلاگین مدیریت کارکرد پرسنل بنی اسد
 * شامل توابع تاریخ، امنیتی، اعتبارسنجی و کاربردی
 * 
 * @package Workforce_Beni_Asad
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ============================================
 * توابع تاریخ و زمان شمسی
 * ============================================
 */

/**
 * تبدیل تاریخ میلادی به شمسی
 *
 * @param string $gregorian_date تاریخ میلادی (Y-m-d)
 * @param string $delimiter جداکننده
 * @param bool $show_day نمایش روز هفته
 * @return string تاریخ شمسی
 */
function wf_gregorian_to_persian($gregorian_date, $delimiter = '/', $show_day = false) {
    if (empty($gregorian_date) || $gregorian_date == '0000-00-00') {
        return '--';
    }
    
    $date_parts = explode('-', $gregorian_date);
    
    if (count($date_parts) != 3) {
        return 'تاریخ نامعتبر';
    }
    
    $year = (int) $date_parts[0];
    $month = (int) $date_parts[1];
    $day = (int) $date_parts[2];
    
    // استفاده از کتابخانه‌ی PHP اگر موجود باشد
    if (class_exists('jDateTime')) {
        $jdate = new jDateTime(true, true, 'Asia/Tehran');
        $persian_date = $jdate->date("Y{$delimiter}m{$delimiter}d", strtotime($gregorian_date));
        
        if ($show_day) {
            $day_name = $jdate->date('l', strtotime($gregorian_date));
            $persian_date .= ' - ' . wf_get_persian_day_name($day_name);
        }
        
        return $persian_date;
    }
    
    // الگوریتم تبدیل دستی (تقریبی)
    $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
    
    $gy = $year - 1600;
    $gm = $month - 1;
    $gd = $day - 1;
    
    $g_day_no = 365 * $gy + floor(($gy + 3) / 4) - floor(($gy + 99) / 100) + floor(($gy + 399) / 400);
    
    for ($i = 0; $i < $gm; ++$i) {
        $g_day_no += $g_days_in_month[$i];
    }
    
    if ($gm > 1 && (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0))) {
        $g_day_no++;
    }
    
    $g_day_no += $gd;
    
    $j_day_no = $g_day_no - 79;
    
    $j_np = floor($j_day_no / 12053);
    $j_day_no %= 12053;
    
    $jy = 979 + 33 * $j_np + 4 * floor($j_day_no / 1461);
    $j_day_no %= 1461;
    
    if ($j_day_no >= 366) {
        $jy += floor(($j_day_no - 1) / 365);
        $j_day_no = ($j_day_no - 1) % 365;
    }
    
    for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i) {
        $j_day_no -= $j_days_in_month[$i];
    }
    
    $jm = $i + 1;
    $jd = $j_day_no + 1;
    
    $persian_date = sprintf('%04d%s%02d%s%02d', $jy, $delimiter, $jm, $delimiter, $jd);
    
    if ($show_day) {
        $timestamp = strtotime($gregorian_date);
        $day_of_week = date('w', $timestamp);
        $day_name = wf_get_persian_day_name_by_number($day_of_week);
        $persian_date .= ' - ' . $day_name;
    }
    
    return $persian_date;
}

/**
 * تبدیل تاریخ شمسی به میلادی
 *
 * @param string $persian_date تاریخ شمسی (Y/m/d)
 * @param string $delimiter جداکننده
 * @return string تاریخ میلادی
 */
function wf_persian_to_gregorian($persian_date, $delimiter = '/') {
    if (empty($persian_date)) {
        return null;
    }
    
    $date_parts = explode($delimiter, $persian_date);
    
    if (count($date_parts) != 3) {
        return null;
    }
    
    $year = (int) $date_parts[0];
    $month = (int) $date_parts[1];
    $day = (int) $date_parts[2];
    
    // استفاده از کتابخانه‌ی PHP اگر موجود باشد
    if (class_exists('jDateTime')) {
        $jdate = new jDateTime(true, true, 'Asia/Tehran');
        $timestamp = $jdate->mktime(0, 0, 0, $month, $day, $year);
        return date('Y-m-d', $timestamp);
    }
    
    // الگوریتم تبدیل دستی (معکوس)
    $jy = $year - 979;
    $jm = $month - 1;
    $jd = $day - 1;
    
    $j_day_no = 365 * $jy + floor($jy / 33) * 8 + floor(($jy % 33 + 3) / 4);
    
    for ($i = 0; $i < $jm; ++$i) {
        $j_day_no += wf_get_jalali_days_in_month($i + 1, $jy);
    }
    
    $j_day_no += $jd;
    
    $g_day_no = $j_day_no + 79;
    
    $gy = 1600 + 400 * floor($g_day_no / 146097);
    $g_day_no %= 146097;
    
    $leap = true;
    
    if ($g_day_no >= 36525) {
        $g_day_no--;
        $gy += 100 * floor($g_day_no / 36524);
        $g_day_no %= 36524;
        
        if ($g_day_no >= 365) {
            $g_day_no++;
        } else {
            $leap = false;
        }
    }
    
    $gy += 4 * floor($g_day_no / 1461);
    $g_day_no %= 1461;
    
    if ($g_day_no >= 366) {
        $leap = false;
        $g_day_no--;
        $gy += floor($g_day_no / 365);
        $g_day_no %= 365;
    }
    
    for ($i = 0; $g_day_no >= (wf_is_gregorian_leap($gy) ? 366 : 365); ++$i) {
        $g_day_no -= wf_is_gregorian_leap($gy) ? 366 : 365;
        $gy++;
    }
    
    $sal_a = array(0, 31, (wf_is_gregorian_leap($gy) ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    
    $gm = 0;
    
    while ($g_day_no >= $sal_a[$gm]) {
        $g_day_no -= $sal_a[$gm];
        $gm++;
    }
    
    $gd = $g_day_no + 1;
    
    return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
}

/**
 * دریافت تاریخ امروز به شمسی
 *
 * @param string $format فرمت خروجی
 * @return string تاریخ شمسی
 */
function wf_get_persian_date($format = 'Y/m/d') {
    $today = date('Y-m-d');
    $persian_date = wf_gregorian_to_persian($today);
    
    $parts = explode('/', $persian_date);
    
    if (count($parts) != 3) {
        return $persian_date;
    }
    
    $year = $parts[0];
    $month = $parts[1];
    $day = $parts[2];
    
    $month_name = wf_get_persian_month_name($month);
    $day_name = wf_get_persian_day_name(date('l'));
    
    $replacements = array(
        'Y' => $year,
        'y' => substr($year, -2),
        'm' => $month,
        'n' => (int) $month,
        'M' => $month_name,
        'F' => $month_name,
        'd' => $day,
        'j' => (int) $day,
        'l' => $day_name,
        'D' => mb_substr($day_name, 0, 1, 'UTF-8')
    );
    
    $formatted_date = $format;
    
    foreach ($replacements as $key => $value) {
        $formatted_date = str_replace($key, $value, $formatted_date);
    }
    
    return $formatted_date;
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
 * دریافت نام روز شمسی
 *
 * @param string $english_day_name نام انگلیسی روز
 * @return string نام فارسی روز
 */
function wf_get_persian_day_name($english_day_name) {
    $days = array(
        'Saturday' => 'شنبه',
        'Sunday' => 'یکشنبه',
        'Monday' => 'دوشنبه',
        'Tuesday' => 'سه‌شنبه',
        'Wednesday' => 'چهارشنبه',
        'Thursday' => 'پنجشنبه',
        'Friday' => 'جمعه'
    );
    
    return $days[$english_day_name] ?? 'نامشخص';
}

/**
 * دریافت نام روز شمسی بر اساس شماره
 *
 * @param int $day_number شماره روز (0-6)
 * @return string نام فارسی روز
 */
function wf_get_persian_day_name_by_number($day_number) {
    $days = array(
        0 => 'یکشنبه',
        1 => 'دوشنبه',
        2 => 'سه‌شنبه',
        3 => 'چهارشنبه',
        4 => 'پنجشنبه',
        5 => 'جمعه',
        6 => 'شنبه'
    );
    
    return $days[$day_number] ?? 'نامشخص';
}

/**
 * بررسی سال کبیسه شمسی
 *
 * @param int $year سال شمسی
 * @return bool کبیسه بودن
 */
function wf_is_jalali_leap($year) {
    $mod = $year % 33;
    $leap_years = array(1, 5, 9, 13, 17, 22, 26, 30);
    return in_array($mod, $leap_years);
}

/**
 * بررسی سال کبیسه میلادی
 *
 * @param int $year سال میلادی
 * @return bool کبیسه بودن
 */
function wf_is_gregorian_leap($year) {
    return ($year % 4 == 0 && $year % 100 != 0) || ($year % 400 == 0);
}

/**
 * دریافت تعداد روزهای ماه شمسی
 *
 * @param int $month شماره ماه
 * @param int $year سال شمسی
 * @return int تعداد روزها
 */
function wf_get_jalali_days_in_month($month, $year) {
    if ($month <= 6) {
        return 31;
    } elseif ($month <= 11) {
        return 30;
    } else { // ماه 12 (اسفند)
        return wf_is_jalali_leap($year) ? 30 : 29;
    }
}

/**
 * فرمت تاریخ و زمان فارسی
 *
 * @param string $datetime تاریخ و زمان
 * @param bool $show_time نمایش زمان
 * @return string تاریخ فرمت شده
 */
function wf_format_persian_datetime($datetime, $show_time = true) {
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00') {
        return '--';
    }
    
    $date_part = substr($datetime, 0, 10);
    $time_part = substr($datetime, 11, 5);
    
    $persian_date = wf_gregorian_to_persian($date_part, '/', true);
    
    if ($show_time && $time_part != '00:00') {
        $persian_date .= ' ' . $time_part;
    }
    
    return $persian_date;
}

/**
 * محاسبه فاصله زمانی به فارسی
 *
 * @param string $from_date تاریخ شروع
 * @param string $to_date تاریخ پایان
 * @return string فاصله زمانی
 */
function wf_get_time_diff_persian($from_date, $to_date = null) {
    if (empty($from_date)) {
        return '--';
    }
    
    if ($to_date === null) {
        $to_date = current_time('mysql');
    }
    
    $from = new DateTime($from_date);
    $to = new DateTime($to_date);
    $diff = $to->diff($from);
    
    $years = $diff->y;
    $months = $diff->m;
    $days = $diff->d;
    $hours = $diff->h;
    $minutes = $diff->i;
    
    $parts = array();
    
    if ($years > 0) {
        $parts[] = $years . ' سال';
    }
    
    if ($months > 0) {
        $parts[] = $months . ' ماه';
    }
    
    if ($days > 0 && $years == 0) {
        $parts[] = $days . ' روز';
    }
    
    if ($hours > 0 && $years == 0 && $months == 0) {
        $parts[] = $hours . ' ساعت';
    }
    
    if ($minutes > 0 && $years == 0 && $months == 0 && $days == 0) {
        $parts[] = $minutes . ' دقیقه';
    }
    
    if (empty($parts)) {
        return 'کمتر از یک دقیقه';
    }
    
    return implode(' و ', $parts);
}

/**
 * ============================================
 * توابع امنیتی
 * ============================================
 */

/**
 * تولید توکن امنیتی (Nonce)
 *
 * @param string $action عمل
 * @return string توکن
 */
function wf_create_nonce($action = '') {
    if (empty($action)) {
        $action = 'wf_secure_action_' . time();
    }
    
    $nonce = wp_create_nonce('wf_' . $action);
    
    // اضافه کردن هش اضافی برای امنیت بیشتر
    $extra_salt = 'beni_asad_' . AUTH_SALT;
    $nonce = md5($nonce . $extra_salt);
    
    return substr($nonce, 0, 16);
}

/**
 * بررسی توکن امنیتی
 *
 * @param string $nonce توکن
 * @param string $action عمل
 * @return bool معتبر بودن
 */
function wf_verify_nonce($nonce, $action = '') {
    if (empty($nonce) || strlen($nonce) != 16) {
        return false;
    }
    
    // بازسازی توکن اصلی
    $extra_salt = 'beni_asad_' . AUTH_SALT;
    $possible_nonces = array();
    
    // تولید چندین توکن ممکن (برای جلوگیری از حملات timing attack)
    for ($i = -2; $i <= 2; $i++) {
        $time_adjusted_action = 'wf_' . $action . ($i * 3600);
        $wp_nonce = wp_create_nonce($time_adjusted_action);
        $possible_nonces[] = substr(md5($wp_nonce . $extra_salt), 0, 16);
    }
    
    return in_array($nonce, $possible_nonces, true);
}

/**
 * پاکسازی و اعتبارسنجی داده‌های ورودی
 *
 * @param mixed $data داده ورودی
 * @param string $type نوع داده
 * @param array $options گزینه‌های اضافی
 * @return mixed داده پاکسازی شده
 */
function wf_sanitize_input($data, $type = 'text', $options = array()) {
    if (is_null($data)) {
        return null;
    }
    
    switch ($type) {
        case 'text':
            $data = sanitize_text_field($data);
            $data = wp_strip_all_tags($data);
            $data = stripslashes($data);
            break;
            
        case 'textarea':
            $data = sanitize_textarea_field($data);
            $data = wp_strip_all_tags($data, '<br><p><strong><em><u><ol><ul><li>');
            $data = stripslashes($data);
            break;
            
        case 'email':
            $data = sanitize_email($data);
            break;
            
        case 'url':
            $data = esc_url_raw($data);
            break;
            
        case 'number':
            $data = (int) $data;
            if (isset($options['min'])) {
                $data = max($data, $options['min']);
            }
            if (isset($options['max'])) {
                $data = min($data, $options['max']);
            }
            break;
            
        case 'decimal':
            $data = (float) $data;
            if (isset($options['min'])) {
                $data = max($data, $options['min']);
            }
            if (isset($options['max'])) {
                $data = min($data, $options['max']);
            }
            if (isset($options['precision'])) {
                $data = round($data, $options['precision']);
            }
            break;
            
        case 'date':
            $data = sanitize_text_field($data);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
                $data = '';
            }
            break;
            
        case 'array':
            if (!is_array($data)) {
                $data = array();
            }
            foreach ($data as $key => $value) {
                $data[$key] = wf_sanitize_input($value, 'text');
            }
            break;
            
        case 'json':
            $data = json_decode(wp_unslash($data), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $data = array();
            }
            $data = wf_sanitize_input($data, 'array');
            break;
            
        default:
            $data = sanitize_text_field($data);
    }
    
    return $data;
}

/**
 * رمزنگاری داده‌ها
 *
 * @param string $data داده
 * @param string $key کلید رمزنگاری
 * @return string داده رمز شده
 */
function wf_encrypt($data, $key = '') {
    if (empty($key)) {
        $key = AUTH_SALT . LOGGED_IN_SALT;
    }
    
    $method = 'aes-256-cbc';
    $iv_length = openssl_cipher_iv_length($method);
    $iv = openssl_random_pseudo_bytes($iv_length);
    
    $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);
    
    if ($encrypted === false) {
        return false;
    }
    
    return base64_encode($iv . $encrypted);
}

/**
 * رمزگشایی داده‌ها
 *
 * @param string $encrypted_data داده رمز شده
 * @param string $key کلید رمزگشایی
 * @return string داده اصلی
 */
function wf_decrypt($encrypted_data, $key = '') {
    if (empty($key)) {
        $key = AUTH_SALT . LOGGED_IN_SALT;
    }
    
    $method = 'aes-256-cbc';
    $iv_length = openssl_cipher_iv_length($method);
    
    $data = base64_decode($encrypted_data);
    
    if (strlen($data) < $iv_length) {
        return false;
    }
    
    $iv = substr($data, 0, $iv_length);
    $encrypted = substr($data, $iv_length);
    
    $decrypted = openssl_decrypt($encrypted, $method, $key, OPENSSL_RAW_DATA, $iv);
    
    return $decrypted !== false ? $decrypted : false;
}

/**
 * هش کردن رمز عبور با الگوریتم ایمن
 *
 * @param string $password رمز عبور
 * @return string رمز هش شده
 */
function wf_hash_password($password) {
    return wp_hash_password($password);
}

/**
 * بررسی رمز عبور
 *
 * @param string $password رمز عبور
 * @param string $hash هش ذخیره شده
 * @return bool صحت رمز عبور
 */
function wf_check_password($password, $hash) {
    return wp_check_password($password, $hash);
}

/**
 * تولید رمز عبور تصادفی
 *
 * @param int $length طول رمز
 * @param bool $special_chars کاراکترهای ویژه
 * @return string رمز عبور
 */
function wf_generate_password($length = 12, $special_chars = true) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    
    if ($special_chars) {
        $chars .= '!@#$%^&*()_+-=[]{}|;:,.<>?';
    }
    
    $password = '';
    $chars_length = strlen($chars);
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $chars_length - 1)];
    }
    
    return $password;
}

/**
 * بررسی CSRF Token
 *
 * @return bool معتبر بودن
 */
function wf_check_csrf() {
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }
    
    $csrf_token = $_POST['_csrf_token'] ?? '';
    $session_token = $_SESSION['csrf_token'] ?? '';
    
    if (empty($csrf_token) || empty($session_token) || !hash_equals($session_token, $csrf_token)) {
        return false;
    }
    
    return true;
}

/**
 * تولید CSRF Token
 *
 * @return string توکن
 */
function wf_generate_csrf_token() {
    if (!session_id()) {
        session_start();
    }
    
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    
    return $token;
}

/**
 * محدود کردن درخواست‌های API
 *
 * @param string $ip آدرس IP
 * @param int $limit حداکثر درخواست
 * @param int $window پنجره زمانی (ثانیه)
 * @return bool اجازه دسترسی
 */
function wf_rate_limit($ip, $limit = 100, $window = 3600) {
    $key = 'wf_rate_limit_' . md5($ip);
    $requests = get_transient($key) ?: array();
    
    $now = time();
    
    // حذف درخواست‌های قدیمی
    $requests = array_filter($requests, function($timestamp) use ($now, $window) {
        return ($now - $timestamp) < $window;
    });
    
    if (count($requests) >= $limit) {
        return false;
    }
    
    $requests[] = $now;
    set_transient($key, $requests, $window);
    
    return true;
}

/**
 * ============================================
 * توابع اعتبارسنجی
 * ============================================
 */

/**
 * اعتبارسنجی کدملی ایرانی
 *
 * @param string $national_id کدملی
 * @return array نتیجه اعتبارسنجی
 */
function wf_validate_national_id($national_id) {
    $result = array(
        'valid' => false,
        'message' => '',
        'data' => array()
    );
    
    // بررسی طول
    if (strlen($national_id) != 10) {
        $result['message'] = 'کدملی باید ۱۰ رقم باشد';
        return $result;
    }
    
    // بررسی عددی بودن
    if (!ctype_digit($national_id)) {
        $result['message'] = 'کدملی باید فقط شامل اعداد باشد';
        return $result;
    }
    
    // اعتبارسنجی الگوریتمی
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += (int) $national_id[$i] * (10 - $i);
    }
    
    $remainder = $sum % 11;
    $check_digit = (int) $national_id[9];
    
    if (($remainder < 2 && $check_digit == $remainder) || ($remainder >= 2 && $check_digit == (11 - $remainder))) {
        $result['valid'] = true;
        $result['message'] = 'کدملی معتبر است';
        
        // استخراج اطلاعات اضافی
        $result['data'] = array(
            'province_code' => substr($national_id, 0, 3),
            'unique_code' => substr($national_id, 3, 6),
            'check_digit' => $check_digit
        );
    } else {
        $result['message'] = 'کدملی معتبر نیست';
    }
    
    return $result;
}

/**
 * اعتبارسنجی شماره موبایل ایرانی
 *
 * @param string $mobile شماره موبایل
 * @return array نتیجه اعتبارسنجی
 */
function wf_validate_mobile($mobile) {
    $result = array(
        'valid' => false,
        'message' => '',
        'data' => array()
    );
    
    // حذف فاصله و کاراکترهای غیرعددی
    $mobile = preg_replace('/\D/', '', $mobile);
    
    // بررسی طول
    if (strlen($mobile) != 11) {
        $result['message'] = 'شماره موبایل باید ۱۱ رقم باشد';
        return $result;
    }
    
    // بررسی پیش‌شماره
    if (!preg_match('/^09[0-9]{9}$/', $mobile)) {
        $result['message'] = 'شماره موبایل با ۰۹ شروع می‌شود';
        return $result;
    }
    
    $result['valid'] = true;
    $result['message'] = 'شماره موبایل معتبر است';
    $result['data'] = array(
        'operator_code' => substr($mobile, 0, 4),
        'number' => substr($mobile, 4)
    );
    
    return $result;
}

/**
 * اعتبارسنجی کد پستی ایرانی
 *
 * @param string $postal_code کد پستی
 * @return array نتیجه اعتبارسنجی
 */
function wf_validate_postal_code($postal_code) {
    $result = array(
        'valid' => false,
        'message' => '',
        'data' => array()
    );
    
    // حذف فاصله و کاراکترهای غیرعددی
    $postal_code = preg_replace('/\D/', '', $postal_code);
    
    // بررسی طول
    if (strlen($postal_code) != 10) {
        $result['message'] = 'کد پستی باید ۱۰ رقم باشد';
        return $result;
    }
    
    // بررسی الگوی کد پستی ایران
    if (!preg_match('/^\d{10}$/', $postal_code)) {
        $result['message'] = 'کد پستی نامعتبر است';
        return $result;
    }
    
    $result['valid'] = true;
    $result['message'] = 'کد پستی معتبر است';
    $result['data'] = array(
        'province_code' => substr($postal_code, 0, 2),
        'city_code' => substr($postal_code, 2, 3),
        'area_code' => substr($postal_code, 5, 2),
        'delivery_code' => substr($postal_code, 7, 3)
    );
    
    return $result;
}

/**
 * اعتبارسنجی شماره کارت بانکی
 *
 * @param string $card_number شماره کارت
 * @return array نتیجه اعتبارسنجی
 */
function wf_validate_card_number($card_number) {
    $result = array(
        'valid' => false,
        'message' => '',
        'data' => array()
    );
    
    // حذف فاصله و کاراکترهای غیرعددی
    $card_number = preg_replace('/\D/', '', $card_number);
    
    // بررسی طول
    if (strlen($card_number) != 16) {
        $result['message'] = 'شماره کارت باید ۱۶ رقم باشد';
        return $result;
    }
    
    // الگوریتم Luhn برای اعتبارسنجی کارت
    $sum = 0;
    $double = false;
    
    for ($i = strlen($card_number) - 1; $i >= 0; $i--) {
        $digit = (int) $card_number[$i];
        
        if ($double) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        
        $sum += $digit;
        $double = !$double;
    }
    
    if ($sum % 10 == 0) {
        $result['valid'] = true;
        $result['message'] = 'شماره کارت معتبر است';
        
        // تشخیص نوع کارت
        $first_digit = $card_number[0];
        $bank_info = wf_get_bank_info_from_card($card_number);
        
        $result['data'] = array(
            'bank' => $bank_info['bank'] ?? 'نامشخص',
            'type' => $bank_info['type'] ?? 'نامشخص',
            'first_digit' => $first_digit
        );
    } else {
        $result['message'] = 'شماره کارت معتبر نیست';
    }
    
    return $result;
}

/**
 * تشخیص بانک از شماره کارت
 *
 * @param string $card_number شماره کارت
 * @return array اطلاعات بانک
 */
function wf_get_bank_info_from_card($card_number) {
    $bank_codes = array(
        '603799' => array('bank' => 'ملی ایران', 'type' => 'بانک'),
        '589210' => array('bank' => 'سپه', 'type' => 'بانک'),
        '627648' => array('bank' => 'توسعه صادرات', 'type' => 'بانک'),
        '627961' => array('bank' => 'صنعت و معدن', 'type' => 'بانک'),
        '603770' => array('bank' => 'کشاورزی', 'type' => 'بانک'),
        '628023' => array('bank' => 'مسکن', 'type' => 'بانک'),
        '627760' => array('bank' => 'پست بانک', 'type' => 'بانک'),
        '502908' => array('bank' => 'توسعه تعاون', 'type' => 'بانک'),
        '627412' => array('bank' => 'اقتصاد نوین', 'type' => 'بانک'),
        '622106' => array('bank' => 'پارسیان', 'type' => 'بانک'),
        '502229' => array('bank' => 'پاسارگاد', 'type' => 'بانک'),
        '627488' => array('bank' => 'کارآفرین', 'type' => 'بانک'),
        '621986' => array('bank' => 'سامان', 'type' => 'بانک'),
        '639346' => array('bank' => 'سینا', 'type' => 'بانک'),
        '639607' => array('bank' => 'سرمایه', 'type' => 'بانک'),
        '636214' => array('bank' => 'آینده', 'type' => 'بانک'),
        '502806' => array('bank' => 'شهر', 'type' => 'بانک'),
        '502938' => array('bank' => 'دی', 'type' => 'بانک'),
        '603769' => array('bank' => 'صادرات', 'type' => 'بانک'),
        '610433' => array('bank' => 'ملت', 'type' => 'بانک'),
        '627353' => array('bank' => 'تجارت', 'type' => 'بانک'),
        '589463' => array('bank' => 'رفاه', 'type' => 'بانک'),
        '627381' => array('bank' => 'انصار', 'type' => 'بانک'),
        '639370' => array('bank' => 'مهر اقتصاد', 'type' => 'بانک')
    );
    
    $first_six = substr($card_number, 0, 6);
    
    return $bank_codes[$first_six] ?? array('bank' => 'نامشخص', 'type' => 'نامشخص');
}

/**
 * اعتبارسنجی تاریخ شمسی
 *
 * @param string $date تاریخ شمسی
 * @param string $format فرمت تاریخ
 * @return array نتیجه اعتبارسنجی
 */
function wf_validate_persian_date($date, $format = 'Y/m/d') {
    $result = array(
        'valid' => false,
        'message' => '',
        'data' => array()
    );
    
    $parts = explode('/', $date);
    
    if (count($parts) != 3) {
        $result['message'] = 'فرت تاریخ نامعتبر است';
        return $result;
    }
    
    $year = (int) $parts[0];
    $month = (int) $parts[1];
    $day = (int) $parts[2];
    
    // بررسی محدوده سال
    if ($year < 1300 || $year > 1500) {
        $result['message'] = 'سال باید بین ۱۳۰۰ تا ۱۵۰۰ باشد';
        return $result;
    }
    
    // بررسی محدوده ماه
    if ($month < 1 || $month > 12) {
        $result['message'] = 'ماه باید بین ۱ تا ۱۲ باشد';
        return $result;
    }
    
    // بررسی محدوده روز
    $days_in_month = wf_get_jalali_days_in_month($month, $year);
    if ($day < 1 || $day > $days_in_month) {
        $result['message'] = sprintf('روز باید بین ۱ تا %d باشد', $days_in_month);
        return $result;
    }
    
    $result['valid'] = true;
    $result['message'] = 'تاریخ معتبر است';
    $result['data'] = array(
        'year' => $year,
        'month' => $month,
        'day' => $day,
        'month_name' => wf_get_persian_month_name($month),
        'gregorian' => wf_persian_to_gregorian($date)
    );
    
    return $result;
}

/**
 * اعتبارسنجی ایمیل
 *
 * @param string $email آدرس ایمیل
 * @return array نتیجه اعتبارسنجی
 */
function wf_validate_email($email) {
    $result = array(
        'valid' => false,
        'message' => '',
        'data' => array()
    );
    
    if (empty($email)) {
        $result['message'] = 'ایمیل نمی‌تواند خالی باشد';
        return $result;
    }
    
    if (!is_email($email)) {
        $result['message'] = 'فرمت ایمیل نامعتبر است';
        return $result;
    }
    
    $result['valid'] = true;
    $result['message'] = 'ایمیل معتبر است';
    
    // استخراج اطلاعات از ایمیل
    $parts = explode('@', $email);
    $result['data'] = array(
        'username' => $parts[0],
        'domain' => $parts[1],
        'is_iranian' => strpos($parts[1], '.ir') !== false
    );
    
    return $result;
}

/**
 * اعتبارسنجی عدد
 *
 * @param mixed $value مقدار
 * @param array $options گزینه‌ها
 * @return array نتیجه اعتبارسنجی
 */
function wf_validate_number($value, $options = array()) {
    $result = array(
        'valid' => false,
        'message' => '',
        'data' => array()
    );
    
    if (!is_numeric($value)) {
        $result['message'] = 'مقدار باید عددی باشد';
        return $result;
    }
    
    $number = (float) $value;
    
    // بررسی حداقل
    if (isset($options['min']) && $number < $options['min']) {
        $result['message'] = sprintf('مقدار باید حداقل %s باشد', wf_format_number($options['min']));
        return $result;
    }
    
    // بررسی حداکثر
    if (isset($options['max']) && $number > $options['max']) {
        $result['message'] = sprintf('مقدار باید حداکثر %s باشد', wf_format_number($options['max']));
        return $result;
    }
    
    // بررسی عدد صحیح
    if (isset($options['integer']) && $options['integer'] && floor($number) != $number) {
        $result['message'] = 'مقدار باید عدد صحیح باشد';
        return $result;
    }
    
    // بررسی مثبت بودن
    if (isset($options['positive']) && $options['positive'] && $number <= 0) {
        $result['message'] = 'مقدار باید مثبت باشد';
        return $result;
    }
    
    $result['valid'] = true;
    $result['message'] = 'عدد معتبر است';
    $result['data'] = array(
        'value' => $number,
        'formatted' => wf_format_number($number)
    );
    
    return $result;
}

/**
 * ============================================
 * توابع فرمت‌بندی و نمایش
 * ============================================
 */

/**
 * فرمت‌بندی اعداد فارسی
 *
 * @param mixed $number عدد
 * @param int $decimals تعداد اعشار
 * @return string عدد فرمت شده
 */
function wf_format_number($number, $decimals = 0) {
    if (!is_numeric($number)) {
        return '۰';
    }
    
    $formatted = number_format((float) $number, $decimals, '.', ',');
    
    // تبدیل اعداد انگلیسی به فارسی
    $persian_numbers = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    $english_numbers = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    
    $formatted = str_replace($english_numbers, $persian_numbers, $formatted);
    
    return $formatted;
}

/**
 * فرمت‌بندی مبلغ پول
 *
 * @param float $amount مبلغ
 * @param string $currency واحد پول
 * @param bool $show_currency نمایش واحد پول
 * @return string مبلغ فرمت شده
 */
function wf_format_currency($amount, $currency = 'ریال', $show_currency = true) {
    $formatted = wf_format_number($amount, 0);
    
    if ($show_currency) {
        $formatted .= ' ' . $currency;
    }
    
    return $formatted;
}

/**
 * فرمت‌بندی شماره تلفن
 *
 * @param string $phone شماره تلفن
 * @return string شماره فرمت شده
 */
function wf_format_phone($phone) {
    if (empty($phone)) {
        return '--';
    }
    
    // حذف همه کاراکترهای غیرعددی
    $phone = preg_replace('/\D/', '', $phone);
    
    if (strlen($phone) == 11 && strpos($phone, '09') === 0) {
        // فرمت موبایل: ۰۹۱۲ ۳۴۵ ۶۷۸۹
        return '۰' . substr($phone, 1, 3) . ' ' . substr($phone, 4, 3) . ' ' . substr($phone, 7, 4);
    } elseif (strlen($phone) == 10 && strpos($phone, '0') === 0) {
        // فرمت تلفن ثابت: ۰۲۱ ۱۲۳۴۵۶۷۸
        return '۰' . substr($phone, 1, 3) . ' ' . substr($phone, 4, 7);
    } elseif (strlen($phone) == 8) {
        // فرمت بدون پیش‌شماره: ۱۲۳۴۵۶۷۸
        return $phone;
    }
    
    return $phone;
}

/**
 * کوتاه کردن متن با حفظ کلمات
 *
 * @param string $text متن
 * @param int $length طول مورد نظر
 * @param string $suffix پسوند
 * @return string متن کوتاه شده
 */
function wf_truncate_text($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text, 'UTF-8') <= $length) {
        return $text;
    }
    
    $short_text = mb_substr($text, 0, $length, 'UTF-8');
    
    // برش در آخرین فاصله
    $last_space = mb_strrpos($short_text, ' ', 0, 'UTF-8');
    
    if ($last_space !== false) {
        $short_text = mb_substr($short_text, 0, $last_space, 'UTF-8');
    }
    
    return $short_text . $suffix;
}

/**
 * ایجاد slug فارسی
 *
 * @param string $text متن
 * @return string slug
 */
function wf_create_slug($text) {
    // حذف تگ‌های HTML
    $text = strip_tags($text);
    
    // تبدیل کاراکترهای خاص
    $text = str_replace(
        array('آ', 'أ', 'إ', 'ئ', 'ء', 'ة'),
        array('ا', 'ا', 'ا', 'ی', '', 'ه'),
        $text
    );
    
    // حذف کاراکترهای غیرمجاز
    $text = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $text);
    
    // جایگزینی فاصله با خط تیره
    $text = preg_replace('/\s+/', '-', $text);
    
    // حذف خط تیره‌های تکراری
    $text = preg_replace('/\-+/', '-', $text);
    
    // حذف خط تیره از ابتدا و انتها
    $text = trim($text, '-');
    
    // تبدیل به حروف کوچک
    $text = mb_strtolower($text, 'UTF-8');
    
    return $text;
}

/**
 * نمایش زمان نسبی (مثلاً "۲ ساعت پیش")
 *
 * @param string $datetime تاریخ و زمان
 * @return string زمان نسبی
 */
function wf_relative_time($datetime) {
    $now = new DateTime();
    $then = new DateTime($datetime);
    $diff = $now->diff($then);
    
    if ($diff->y > 0) {
        return $diff->y . ' سال پیش';
    } elseif ($diff->m > 0) {
        return $diff->m . ' ماه پیش';
    } elseif ($diff->d > 0) {
        return $diff->d . ' روز پیش';
    } elseif ($diff->h > 0) {
        return $diff->h . ' ساعت پیش';
    } elseif ($diff->i > 0) {
        return $diff->i . ' دقیقه پیش';
    } else {
        return 'همین الان';
    }
}

/**
 * تبدیل بایت به واحد خوانا
 *
 * @param int $bytes تعداد بایت
 * @param int $precision دقت اعشار
 * @return string حجم خوانا
 */
function wf_format_bytes($bytes, $precision = 2) {
    $units = array('بایت', 'کیلوبایت', 'مگابایت', 'گیگابایت', 'ترابایت');
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * ============================================
 * توابع کاربردی و عمومی
 * ============================================
 */

/**
 * دریافت نقش کاربر
 *
 * @param int $user_id شناسه کاربر
 * @return string نقش کاربر
 */
function wf_get_user_role($user_id = 0) {
    if ($user_id == 0) {
        $user_id = get_current_user_id();
    }
    
    if ($user_id == 0) {
        return 'guest';
    }
    
    $user = get_userdata($user_id);
    
    if (!$user) {
        return 'guest';
    }
    
    // بررسی نقش‌های سفارشی سیستم
    global $wpdb;
    $table = $wpdb->prefix . 'wf_system_users';
    
    $system_role = $wpdb->get_var($wpdb->prepare(
        "SELECT role FROM {$table} WHERE wp_user_id = %d AND is_active = 1",
        $user_id
    ));
    
    if ($system_role) {
        return $system_role;
    }
    
    // نقش پیش‌فرض وردپرس
    $roles = $user->roles;
    
    if (in_array('administrator', $roles)) {
        return 'admin';
    } elseif (in_array('editor', $roles)) {
        return 'organization_manager';
    } elseif (in_array('author', $roles)) {
        return 'department_manager';
    }
    
    return 'viewer';
}

/**
 * بررسی دسترسی کاربر
 *
 * @param string $capability توانایی مورد نیاز
 * @param int $user_id شناسه کاربر
 * @return bool دسترسی دارد یا نه
 */
function wf_user_can($capability, $user_id = 0) {
    if ($user_id == 0) {
        $user_id = get_current_user_id();
    }
    
    $role = wf_get_user_role($user_id);
    
    $capabilities = array(
        'admin' => array(
            'manage_fields',
            'manage_departments',
            'manage_personnel',
            'manage_periods',
            'manage_approvals',
            'view_reports',
            'export_data',
            'manage_settings'
        ),
        'organization_manager' => array(
            'manage_departments',
            'manage_personnel',
            'view_reports',
            'export_data'
        ),
        'department_manager' => array(
            'manage_personnel',
            'view_reports'
        ),
        'viewer' => array(
            'view_reports'
        )
    );
    
    $role_caps = $capabilities[$role] ?? array();
    
    return in_array($capability, $role_caps);
}

/**
 * دریافت تنظیمات کاربر
 *
 * @param int $user_id شناسه کاربر
 * @param string $key کلید تنظیم
 * @param mixed $default مقدار پیش‌فرض
 * @return mixed مقدار تنظیم
 */
function wf_get_user_setting($user_id, $key, $default = '') {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_system_users';
    
    $settings = $wpdb->get_var($wpdb->prepare(
        "SELECT settings FROM {$table} WHERE wp_user_id = %d",
        $user_id
    ));
    
    if (!$settings) {
        return $default;
    }
    
    $settings = json_decode($settings, true);
    
    return $settings[$key] ?? $default;
}

/**
 * ذخیره تنظیمات کاربر
 *
 * @param int $user_id شناسه کاربر
 * @param string $key کلید تنظیم
 * @param mixed $value مقدار تنظیم
 * @return bool موفقیت
 */
function wf_set_user_setting($user_id, $key, $value) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_system_users';
    
    // دریافت تنظیمات فعلی
    $current_settings = wf_get_user_setting($user_id, 'all', array());
    
    if ($current_settings === '') {
        $current_settings = array();
    }
    
    // به‌روزرسانی تنظیمات
    $current_settings[$key] = $value;
    
    // ذخیره در دیتابیس
    $settings_json = json_encode($current_settings);
    
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE wp_user_id = %d",
        $user_id
    ));
    
    if ($exists > 0) {
        return $wpdb->update($table, 
            array('settings' => $settings_json),
            array('wp_user_id' => $user_id)
        ) !== false;
    } else {
        return $wpdb->insert($table, 
            array(
                'wp_user_id' => $user_id,
                'settings' => $settings_json,
                'created_at' => current_time('mysql')
            )
        ) !== false;
    }
}

/**
 * دریافت رنگ بر اساس وضعیت
 *
 * @param string $status وضعیت
 * @param string $type نوع رنگ
 * @return string کد رنگ
 */
function wf_get_status_color($status, $type = 'background') {
    $colors = array(
        'active' => array(
            'background' => '#10b981',
            'text' => '#ffffff',
            'light' => '#d1fae5'
        ),
        'inactive' => array(
            'background' => '#6b7280',
            'text' => '#ffffff',
            'light' => '#e5e7eb'
        ),
        'pending' => array(
            'background' => '#f59e0b',
            'text' => '#ffffff',
            'light' => '#fef3c7'
        ),
        'approved' => array(
            'background' => '#10b981',
            'text' => '#ffffff',
            'light' => '#d1fae5'
        ),
        'rejected' => array(
            'background' => '#ef4444',
            'text' => '#ffffff',
            'light' => '#fee2e2'
        ),
        'completed' => array(
            'background' => '#3b82f6',
            'text' => '#ffffff',
            'light' => '#dbeafe'
        ),
        'deleted' => array(
            'background' => '#6b7280',
            'text' => '#ffffff',
            'light' => '#e5e7eb'
        ),
        'warning' => array(
            'background' => '#f59e0b',
            'text' => '#ffffff',
            'light' => '#fef3c7'
        ),
        'error' => array(
            'background' => '#ef4444',
            'text' => '#ffffff',
            'light' => '#fee2e2'
        ),
        'success' => array(
            'background' => '#10b981',
            'text' => '#ffffff',
            'light' => '#d1fae5'
        ),
        'info' => array(
            'background' => '#3b82f6',
            'text' => '#ffffff',
            'light' => '#dbeafe'
        )
    );
    
    $status_colors = $colors[$status] ?? $colors['info'];
    
    return $status_colors[$type] ?? $status_colors['background'];
}

/**
 * ایجاد بادژ (badge) وضعیت
 *
 * @param string $status وضعیت
 * @param string $text متن
 * @param string $size اندازه
 * @return string HTML بادژ
 */
function wf_get_status_badge($status, $text = '', $size = 'medium') {
    if (empty($text)) {
        $text = $status;
    }
    
    $colors = wf_get_status_color($status, 'background');
    $text_color = wf_get_status_color($status, 'text');
    
    $sizes = array(
        'small' => 'px-2 py-1 text-xs',
        'medium' => 'px-3 py-1 text-sm',
        'large' => 'px-4 py-2 text-base'
    );
    
    $size_class = $sizes[$size] ?? $sizes['medium'];
    
    $badge = sprintf(
        '<span class="rounded-full font-medium %s" style="background-color: %s; color: %s;">%s</span>',
        $size_class,
        $colors,
        $text_color,
        esc_html($text)
    );
    
    return $badge;
}

/**
 * بررسی وجود فایل آپلود شده
 *
 * @param string $field_name نام فیلد
 * @param array $allowed_types انواع مجاز
 * @param int $max_size حداکثر حجم (بایت)
 * @return array|false اطلاعات فایل یا false
 */
function wf_validate_uploaded_file($field_name, $allowed_types = array(), $max_size = 5242880) {
    if (!isset($_FILES[$field_name]) || $_FILES[$field_name]['error'] != UPLOAD_ERR_OK) {
        return false;
    }
    
    $file = $_FILES[$field_name];
    
    // بررسی حجم فایل
    if ($file['size'] > $max_size) {
        return false;
    }
    
    // بررسی نوع فایل
    if (!empty($allowed_types)) {
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            return false;
        }
    }
    
    // بررسی MIME Type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    // لیست MIME types مجاز
    $allowed_mimes = array(
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    );
    
    if (!in_array($mime_type, $allowed_mimes)) {
        return false;
    }
    
    return array(
        'name' => sanitize_file_name($file['name']),
        'type' => $file['type'],
        'tmp_name' => $file['tmp_name'],
        'size' => $file['size'],
        'extension' => pathinfo($file['name'], PATHINFO_EXTENSION)
    );
}

/**
 * آپلود فایل امن
 *
 * @param array $file اطلاعات فایل
 * @param string $upload_dir دایرکتوری مقصد
 * @param string $filename نام فایل (اختیاری)
 * @return array|false نتیجه آپلود
 */
function wf_upload_file($file, $upload_dir = 'workforce', $filename = '') {
    if (!$file || !is_array($file)) {
        return false;
    }
    
    // ایجاد دایرکتوری آپلود
    $upload_path = WP_CONTENT_DIR . '/uploads/' . $upload_dir . '/' . date('Y/m');
    
    if (!file_exists($upload_path)) {
        mkdir($upload_path, 0755, true);
    }
    
    // نام فایل
    if (empty($filename)) {
        $filename = uniqid() . '_' . $file['name'];
    } else {
        $filename = sanitize_file_name($filename);
    }
    
    $filepath = $upload_path . '/' . $filename;
    
    // انتقال فایل
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // تغییر مجوز فایل
        chmod($filepath, 0644);
        
        return array(
            'success' => true,
            'path' => $filepath,
            'url' => content_url('/uploads/' . $upload_dir . '/' . date('Y/m') . '/' . $filename),
            'filename' => $filename,
            'size' => $file['size'],
            'type' => $file['type']
        );
    }
    
    return false;
}

/**
 * ایجاد QR Code
 *
 * @param string $data داده
 * @param int $size اندازه
 * @return string URL تصویر QR Code
 */
function wf_generate_qr_code($data, $size = 200) {
    $encoded_data = urlencode($data);
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encoded_data}&format=png";
    
    return $qr_url;
}

/**
 * ارسال نوتیفیکیشن
 *
 * @param int $user_id شناسه کاربر
 * @param string $title عنوان
 * @param string $message پیام
 * @param string $type نوع
 * @param array $data داده اضافی
 * @return bool موفقیت
 */
function wf_send_notification($user_id, $title, $message, $type = 'info', $data = array()) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_notifications';
    
    // اگر جدول نوتیفیکیشن وجود ندارد، ایجادش کن
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    
    if (!$table_exists) {
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(50) DEFAULT 'info',
            data TEXT,
            is_read TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_is_read (is_read),
            KEY idx_created_at (created_at)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    // ذخیره نوتیفیکیشن
    $notification_data = array(
        'user_id' => $user_id,
        'title' => $title,
        'message' => $message,
        'type' => $type,
        'data' => json_encode($data),
        'created_at' => current_time('mysql')
    );
    
    $result = $wpdb->insert($table, $notification_data);
    
    if (!$result) {
        return false;
    }
    
    // ارسال ایمیل (اختیاری)
    $user = get_userdata($user_id);
    if ($user && !empty($user->user_email)) {
        $email_subject = $title . ' - سیستم مدیریت پرسنل';
        $email_message = $message . "\n\n" . home_url('/wp-admin/admin.php?page=workforce-notifications');
        
        wp_mail($user->user_email, $email_subject, $email_message);
    }
    
    return true;
}

/**
 * دریافت نوتیفیکیشن‌های خوانده نشده
 *
 * @param int $user_id شناسه کاربر
 * @param int $limit تعداد
 * @return array لیست نوتیفیکیشن‌ها
 */
function wf_get_unread_notifications($user_id, $limit = 10) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_notifications';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    
    if (!$table_exists) {
        return array();
    }
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} WHERE user_id = %d AND is_read = 0 ORDER BY created_at DESC LIMIT %d",
        $user_id, $limit
    ), ARRAY_A);
}

/**
 ============================================
 * توابع دیباگ و لاگ
 * ============================================
 */

/**
 * ثبت خطا در فایل لاگ
 *
 * @param mixed $error خطا یا پیام
 * @param string $type نوع خطا
 * @param array $context اطلاعات اضافی
 */
function wf_log_error($error, $type = 'error', $context = array()) {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    $log_dir = WP_CONTENT_DIR . '/uploads/wf-logs/';
    
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . date('Y-m-d') . '.log';
    
    $message = '[' . date('Y-m-d H:i:s') . '] ';
    $message .= strtoupper($type) . ': ';
    
    if (is_wp_error($error)) {
        $message .= $error->get_error_message();
        $context['error_codes'] = $error->get_error_codes();
        $context['error_data'] = $error->get_error_data();
    } elseif (is_string($error)) {
        $message .= $error;
    } elseif (is_array($error) || is_object($error)) {
        $message .= print_r($error, true);
    }
    
    if (!empty($context)) {
        $message .= ' | Context: ' . json_encode($context);
    }
    
    $message .= "\n";
    
    file_put_contents($log_file, $message, FILE_APPEND | LOCK_EX);
}

/**
 * نمایش داده‌ها برای دیباگ
 *
 * @param mixed $data داده
 * @param bool $die توقف اجرا
 * @param bool $json نمایش به صورت JSON
 */
function wf_debug($data, $die = false, $json = false) {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    echo '<pre style="background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; border-radius: 5px; direction: ltr; text-align: left;">';
    
    if ($json) {
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        if (is_array($data) || is_object($data)) {
            print_r($data);
        } else {
            var_dump($data);
        }
    }
    
    echo '</pre>';
    
    if ($die) {
        die();
    }
}

/**
 * بررسی زمان اجرای اسکریپت
 *
 * @param string $point نقطه بررسی
 * @return array زمان‌ها
 */
function wf_benchmark($point = '') {
    static $start_time = null;
    static $last_time = null;
    static $points = array();
    
    $current_time = microtime(true);
    
    if ($start_time === null) {
        $start_time = $current_time;
        $last_time = $current_time;
        return array('started' => $start_time);
    }
    
    if (!empty($point)) {
        $points[$point] = array(
            'time' => $current_time,
            'total_elapsed' => $current_time - $start_time,
            'since_last' => $current_time - $last_time
        );
    }
    
    $last_time = $current_time;
    
    return $points;
}

/**
 * ============================================
 * توابع برای API و Ajax
 * ============================================
 */

/**
 * ارسال پاسخ JSON استاندارد
 *
 * @param bool $success موفقیت
 * @param string $message پیام
 * @param array $data داده
 * @param int $status_code کد وضعیت HTTP
 */
function wf_json_response($success = true, $message = '', $data = array(), $status_code = 200) {
    status_header($status_code);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = array(
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => time(),
        'version' => '1.0.0'
    );
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * بررسی درخواست Ajax
 *
 * @param string $action عمل
 * @return bool معتبر بودن
 */
function wf_verify_ajax_request($action = '') {
    // بررسی درخواست Ajax
    if (!defined('DOING_AJAX') || !DOING_AJAX) {
        wf_json_response(false, 'درخواست نامعتبر', array(), 400);
        return false;
    }
    
    // بررسی Nonce
    $nonce = $_POST['_ajax_nonce'] ?? $_GET['_ajax_nonce'] ?? '';
    
    if (empty($nonce) || !wf_verify_nonce($nonce, $action)) {
        wf_json_response(false, 'توکن امنیتی نامعتبر', array(), 403);
        return false;
    }
    
    // بررسی CSRF برای درخواست‌های POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !wf_check_csrf()) {
        wf_json_response(false, 'توکن CSRF نامعتبر', array(), 403);
        return false;
    }
    
    return true;
}

/**
 * دریافت داده‌های Ajax
 *
 * @param string $key کلید
 * @param mixed $default مقدار پیش‌فرض
 * @param string $type نوع داده
 * @return mixed داده
 */
function wf_get_ajax_data($key, $default = '', $type = 'text') {
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return wf_sanitize_input($value, $type);
}

/**
 * ============================================
 * توابع کمکی وردپرسی
 * ============================================
 */

/**
 * دریافت آدرس صفحه با پارامترها
 *
 * @param string $page_slug slug صفحه
 * @param array $params پارامترها
 * @return string آدرس کامل
 */
function wf_get_page_url($page_slug, $params = array()) {
    $page = get_page_by_path($page_slug);
    
    if (!$page) {
        return home_url();
    }
    
    $url = get_permalink($page->ID);
    
    if (!empty($params)) {
        $url = add_query_arg($params, $url);
    }
    
    return $url;
}

/**
 * ایجاد صفحه اگر وجود نداشته باشد
 *
 * @param string $title عنوان صفحه
 * @param string $slug slug صفحه
 * @param string $content محتوا
 * @param int $parent_id شناسه والد
 * @return int شناسه صفحه
 */
function wf_create_page_if_not_exists($title, $slug, $content = '', $parent_id = 0) {
    $page = get_page_by_path($slug);
    
    if ($page) {
        return $page->ID;
    }
    
    $page_data = array(
        'post_title' => $title,
        'post_name' => $slug,
        'post_content' => $content,
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_parent' => $parent_id,
        'post_author' => 1
    );
    
    $page_id = wp_insert_post($page_data);
    
    return $page_id;
}

/**
 * اضافه کردن roleهای سفارشی
 */
function wf_add_custom_roles() {
    // نقش مدیر سازمان
    add_role('organization_manager', 'مدیر سازمان', array(
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
        'publish_posts' => false,
        'upload_files' => true,
        'manage_categories' => false
    ));
    
    // نقش مدیر اداره
    add_role('department_manager', 'مدیر اداره', array(
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
        'publish_posts' => false,
        'upload_files' => true
    ));
    
    // نقش بازبین
    add_role('auditor', 'بازبین', array(
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
        'publish_posts' => false
    ));
}

/**
 * حذف roleهای سفارشی
 */
function wf_remove_custom_roles() {
    remove_role('organization_manager');
    remove_role('department_manager');
    remove_role('auditor');
}

/**
 * ============================================
 * توابع برای ماژول‌ها
 * ============================================
 */

/**
 * بارگذاری ماژول
 *
 * @param string $module_name نام ماژول
 * @return bool موفقیت
 */
function wf_load_module($module_name) {
    $module_path = WF_PLUGIN_DIR . 'modules/' . $module_name . '.php';
    
    if (file_exists($module_path)) {
        require_once $module_path;
        return true;
    }
    
    return false;
}

/**
 * دریافت لیست ماژول‌های فعال
 *
 * @return array ماژول‌های فعال
 */
function wf_get_active_modules() {
    $modules = array(
        'core' => array(
            'name' => 'هسته سیستم',
            'description' => 'ماژول اصلی سیستم مدیریت پرسنل',
            'version' => '1.0.0',
            'active' => true,
            'required' => true
        ),
        'reports' => array(
            'name' => 'گزارش‌گیری',
            'description' => 'سیستم پیشرفته گزارش‌گیری',
            'version' => '1.0.0',
            'active' => true,
            'required' => false
        ),
        'notifications' => array(
            'name' => 'اعلان‌ها',
            'description' => 'سیستم ارسال اعلان و نوتیفیکیشن',
            'version' => '1.0.0',
            'active' => true,
            'required' => false
        ),
        'backup' => array(
            'name' => 'پشتیبان‌گیری',
            'description' => 'سیستم پشتیبان‌گیری خودکار',
            'version' => '1.0.0',
            'active' => true,
            'required' => false
        ),
        'api' => array(
            'name' => 'API',
            'description' => 'API برای یکپارچه‌سازی با سیستم‌های دیگر',
            'version' => '1.0.0',
            'active' => false,
            'required' => false
        )
    );
    
    return $modules;
}

/**
 * فعال کردن ماژول
 *
 * @param string $module_name نام ماژول
 * @return bool موفقیت
 */
function wf_activate_module($module_name) {
    $modules = wf_get_active_modules();
    
    if (!isset($modules[$module_name])) {
        return false;
    }
    
    // ذخیره در تنظیمات
    $active_modules = wf_get_setting('active_modules', array());
    $active_modules[$module_name] = true;
    
    wf_save_setting('active_modules', $active_modules, 'modules', 'array');
    
    // بارگذاری ماژول
    return wf_load_module($module_name);
}

/**
 * غیرفعال کردن ماژول
 *
 * @param string $module_name نام ماژول
 * @return bool موفقیت
 */
function wf_deactivate_module($module_name) {
    $modules = wf_get_active_modules();
    
    if (!isset($modules[$module_name]) || $modules[$module_name]['required']) {
        return false;
    }
    
    // به‌روزرسانی تنظیمات
    $active_modules = wf_get_setting('active_modules', array());
    unset($active_modules[$module_name]);
    
    wf_save_setting('active_modules', $active_modules, 'modules', 'array');
    
    return true;
}

/**
 * ============================================
 * توابع پایانی
 * ============================================
 */

/**
 * بررسی نسخه پلاگین
 *
 * @return array اطلاعات نسخه
 */
function wf_get_version_info() {
    $plugin_data = get_file_data(WF_PLUGIN_FILE, array(
        'Version' => 'Version',
        'Name' => 'Plugin Name',
        'Description' => 'Description',
        'Author' => 'Author',
        'TextDomain' => 'Text Domain'
    ));
    
    return array(
        'version' => $plugin_data['Version'] ?? '1.0.0',
        'name' => $plugin_data['Name'] ?? 'کارکرد پرسنل - بنی اسد',
        'description' => $plugin_data['Description'] ?? '',
        'author' => $plugin_data['Author'] ?? 'بنی اسد',
        'text_domain' => $plugin_data['TextDomain'] ?? 'workforce-beni-asad',
        'php_version' => PHP_VERSION,
        'wp_version' => get_bloginfo('version'),
        'db_version' => get_option('workforce_db_version', '1.0.0')
    );
}

/**
 * بررسی به‌روزرسانی
 *
 * @return array|false اطلاعات به‌روزرسانی
 */
function wf_check_for_updates() {
    $current_version = wf_get_version_info()['version'];
    
    // در یک سیستم واقعی، اینجا از API سرور استفاده می‌شود
    $update_server = 'https://updates.beni-asad.ir/';
    
    // فعلاً مقدار ثابت برمی‌گردانیم
    return array(
        'has_update' => false,
        'current_version' => $current_version,
        'new_version' => $current_version,
        'package_url' => '',
        'changelog' => '',
        'requires_php' => '7.4.0',
        'requires_wp' => '5.8.0'
    );
}

/**
 * به‌روزرسانی پلاگین
 *
 * @return array نتیجه به‌روزرسانی
 */
function wf_perform_update() {
    $update_info = wf_check_for_updates();
    
    if (!$update_info['has_update']) {
        return array(
            'success' => false,
            'message' => 'نسخه‌ی جدیدی موجود نیست'
        );
    }
    
    // در یک سیستم واقعی، اینجا عملیات به‌روزرسانی انجام می‌شود
    // فعلاً فقط پیام برمی‌گردانیم
    
    wf_log_activity(get_current_user_id(), 'system', 'update_performed', 
        sprintf('به‌روزرسانی از نسخه %s به %s', 
            $update_info['current_version'], 
            $update_info['new_version']));
    
    return array(
        'success' => true,
        'message' => 'به‌روزرسانی با موفقیت انجام شد',
        'old_version' => $update_info['current_version'],
        'new_version' => $update_info['new_version']
    );
}

/**
 * راه‌اندازی اولیه سیستم
 */
function wf_initialize_system() {
    // ایجاد roleهای سفارشی
    wf_add_custom_roles();
    
    // ایجاد صفحات لازم
    wf_create_page_if_not_exists(
        'پنل مدیران ادارات',
        'manager-panel',
        '[workforce_manager_panel]'
    );
    
    wf_create_page_if_not_exists(
        'پنل مدیر سازمان',
        'org-manager-panel',
        '[workforce_org_manager_panel]'
    );
    
    // ایجاد کاربر ادمین اگر وجود ندارد
    wf_create_default_admin();
    
    wf_log_activity(1, 'system', 'system_initialized', 'سیستم راه‌اندازی شد');
}

/**
 * ایجاد کاربر ادمین پیش‌فرض
 */
function wf_create_default_admin() {
    $username = 'beni_asad_admin';
    
    if (!username_exists($username)) {
        $password = wp_generate_password(12, true, true);
        $user_id = wp_create_user($username, $password, 'admin@beni-asad.ir');
        
        if (!is_wp_error($user_id)) {
            $user = new WP_User($user_id);
            $user->set_role('administrator');
            
            // ذخیره در جدول کاربران سیستم
            global $wpdb;
            $table = $wpdb->prefix . 'wf_system_users';
            
            $wpdb->insert($table, array(
                'wp_user_id' => $user_id,
                'role' => 'admin',
                'is_active' => 1,
                'created_at' => current_time('mysql')
            ));
            
            wf_log_activity($user_id, 'system', 'default_admin_created', 
                'کاربر ادمین پیش‌فرض ایجاد شد');
        }
    }
}

/**
 * خاتمه فایل
 */

// افزودن فیلترهای لازم
add_filter('sanitize_file_name', 'wf_persian_sanitize_filename', 10, 1);

/**
 * پاکسازی نام فایل فارسی
 *
 * @param string $filename نام فایل
 * @return string نام فایل پاکسازی شده
 */
function wf_persian_sanitize_filename($filename) {
    $filename = mb_convert_encoding($filename, 'UTF-8', 'auto');
    $filename = str_replace(
        array('آ', 'أ', 'إ', 'ئ', 'ء', 'ة'),
        array('ا', 'ا', 'ا', 'ی', '', 'ه'),
        $filename
    );
    return $filename;
}

// پایان فایل
