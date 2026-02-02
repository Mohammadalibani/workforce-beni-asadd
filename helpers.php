<?php
/**
 * توابع کمکی سیستم مدیریت کارکرد پرسنل
 * شامل توابع عمومی، اعتبارسنجی، تبدیل تاریخ و امکانات کمکی
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// ==================== توابع تاریخ و زمان ====================

/**
 * کلاس تبدیل تاریخ شمسی به میلادی و بالعکس
 */
class WF_Date_Converter {
    
    /**
     * تبدیل تاریخ میلادی به شمسی
     */
    public static function gregorian_to_jalali($g_y, $g_m, $g_d, $mod = '') {
        $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
        
        $gy = $g_y - 1600;
        $gm = $g_m - 1;
        $gd = $g_d - 1;
        
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
        $j_day_no = $j_day_no % 12053;
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
        
        if ($mod == '') {
            return array($jy, $jm, $jd);
        }
        
        return self::format_jalali_date($jy, $jm, $jd, $mod);
    }
    
    /**
     * تبدیل تاریخ شمسی به میلادی
     */
    public static function jalali_to_gregorian($j_y, $j_m, $j_d) {
        $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
        
        $jy = $j_y - 979;
        $jm = $j_m - 1;
        $jd = $j_d - 1;
        
        $j_day_no = 365 * $jy + floor($jy / 33) * 8 + floor(($jy % 33 + 3) / 4);
        
        for ($i = 0; $i < $jm; ++$i) {
            $j_day_no += $j_days_in_month[$i];
        }
        
        $j_day_no += $jd;
        
        $g_day_no = $j_day_no + 79;
        $gy = 1600 + 400 * floor($g_day_no / 146097);
        $g_day_no = $g_day_no % 146097;
        
        $leap = true;
        if ($g_day_no >= 36525) {
            $g_day_no--;
            $gy += 100 * floor($g_day_no / 36524);
            $g_day_no = $g_day_no % 36524;
            
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
            $g_day_no = $g_day_no % 365;
        }
        
        for ($i = 0; $g_day_no >= $g_days_in_month[$i] + ($i == 1 && $leap ? 1 : 0); $i++) {
            $g_day_no -= $g_days_in_month[$i] + ($i == 1 && $leap ? 1 : 0);
        }
        
        $gm = $i + 1;
        $gd = $g_day_no + 1;
        
        return array($gy, $gm, $gd);
    }
    
    /**
     * فرمت کردن تاریخ شمسی
     */
    public static function format_jalali_date($year, $month, $day, $format = 'Y/m/d') {
        $persian_numbers = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
        $arabic_numbers = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');
        $english_numbers = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        
        $month_names = array(
            'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
            'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
        );
        
        $day_names = array(
            'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'
        );
        
        $formatted = $format;
        
        // جایگزینی سال
        $formatted = str_replace('Y', $year, $formatted);
        $formatted = str_replace('y', substr($year, -2), $formatted);
        
        // جایگزینی ماه
        $formatted = str_replace('m', str_pad($month, 2, '0', STR_PAD_LEFT), $formatted);
        $formatted = str_replace('n', $month, $formatted);
        $formatted = str_replace('F', $month_names[$month - 1], $formatted);
        $formatted = str_replace('M', mb_substr($month_names[$month - 1], 0, 3, 'UTF-8'), $formatted);
        
        // جایگزینی روز
        $formatted = str_replace('d', str_pad($day, 2, '0', STR_PAD_LEFT), $formatted);
        $formatted = str_replace('j', $day, $formatted);
        
        // تبدیل اعداد به فارسی
        $formatted = str_replace($english_numbers, $persian_numbers, $formatted);
        $formatted = str_replace($arabic_numbers, $persian_numbers, $formatted);
        
        return $formatted;
    }
    
    /**
     * دریافت نام ماه شمسی
     */
    public static function get_jalali_month_name($month_number) {
        $month_names = array(
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
            4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
            7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
            10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
        );
        
        return $month_names[$month_number] ?? 'نامشخص';
    }
    
    /**
     * دریافت نام روز هفته
     */
    public static function get_jalali_day_name($timestamp = null) {
        if ($timestamp === null) {
            $timestamp = current_time('timestamp');
        }
        
        $day_of_week = date('w', $timestamp);
        $day_names = array(
            'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'
        );
        
        return $day_names[$day_of_week];
    }
}

/**
 * تبدیل تاریخ میلادی به شمسی
 */
function wf_convert_to_jalali($gregorian_date, $format = 'Y/m/d') {
    if (empty($gregorian_date) || $gregorian_date == '0000-00-00') {
        return '';
    }
    
    // اگر تاریخ به صورت timestamp است
    if (is_numeric($gregorian_date)) {
        $gregorian_date = date('Y-m-d', $gregorian_date);
    }
    
    // اگر فرمت ISO نیست، سعی کن تبدیلش کن
    if (strpos($gregorian_date, '-') === false) {
        $timestamp = strtotime($gregorian_date);
        if ($timestamp === false) {
            return $gregorian_date;
        }
        $gregorian_date = date('Y-m-d', $timestamp);
    }
    
    list($year, $month, $day) = explode('-', $gregorian_date);
    
    if (count(explode(' ', $day)) > 1) {
        list($day, $time) = explode(' ', $day);
    }
    
    $jalali = WF_Date_Converter::gregorian_to_jalali($year, $month, $day, $format);
    
    // اگر زمان هم داشتیم، برگردون
    if (isset($time)) {
        $jalali .= ' ' . $time;
    }
    
    return $jalali;
}

/**
 * تبدیل تاریخ شمسی به میلادی
 */
function wf_convert_to_gregorian($jalali_date, $format = 'Y-m-d') {
    if (empty($jalali_date)) {
        return null;
    }
    
    // حذف اعداد فارسی و عربی
    $persian_numbers = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    $arabic_numbers = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');
    $english_numbers = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    
    $jalali_date = str_replace($persian_numbers, $english_numbers, $jalali_date);
    $jalali_date = str_replace($arabic_numbers, $english_numbers, $jalali_date);
    
    // جداسازی تاریخ و زمان
    $time_part = '';
    if (strpos($jalali_date, ' ') !== false) {
        list($jalali_date, $time_part) = explode(' ', $jalali_date, 2);
    }
    
    // جدا کردن اجزای تاریخ
    $separators = array('/', '-', '.');
    $used_separator = '';
    
    foreach ($separators as $sep) {
        if (strpos($jalali_date, $sep) !== false) {
            $used_separator = $sep;
            break;
        }
    }
    
    if (empty($used_separator)) {
        // اگر جداکننده نداشت، فرض کن فرمت YYYYMMDD است
        if (strlen($jalali_date) == 8) {
            $year = substr($jalali_date, 0, 4);
            $month = substr($jalali_date, 4, 2);
            $day = substr($jalali_date, 6, 2);
        } else {
            return null;
        }
    } else {
        list($year, $month, $day) = explode($used_separator, $jalali_date);
    }
    
    // تبدیل به عدد
    $year = (int)$year;
    $month = (int)$month;
    $day = (int)$day;
    
    // تبدیل
    list($g_year, $g_month, $g_day) = WF_Date_Converter::jalali_to_gregorian($year, $month, $day);
    
    $gregorian_date = sprintf('%04d-%02d-%02d', $g_year, $g_month, $g_day);
    
    // افزودن زمان اگر وجود داشت
    if (!empty($time_part)) {
        $gregorian_date .= ' ' . $time_part;
    }
    
    // فرمت کردن خروجی
    if ($format != 'Y-m-d') {
        $timestamp = strtotime($gregorian_date);
        $gregorian_date = date($format, $timestamp);
    }
    
    return $gregorian_date;
}

/**
 * اعتبارسنجی تاریخ شمسی
 */
function wf_validate_jalali_date($date) {
    if (empty($date)) {
        return false;
    }
    
    // تبدیل اعداد
    $persian_numbers = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    $arabic_numbers = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');
    $english_numbers = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    
    $date = str_replace($persian_numbers, $english_numbers, $date);
    $date = str_replace($arabic_numbers, $english_numbers, $date);
    
    // پیدا کردن جداکننده
    $separators = array('/', '-', '.');
    $used_separator = '';
    
    foreach ($separators as $sep) {
        if (strpos($date, $sep) !== false) {
            $used_separator = $sep;
            break;
        }
    }
    
    if (empty($used_separator)) {
        return false;
    }
    
    list($year, $month, $day) = explode($used_separator, $date);
    
    // بررسی عدد بودن
    if (!is_numeric($year) || !is_numeric($month) || !is_numeric($day)) {
        return false;
    }
    
    $year = (int)$year;
    $month = (int)$month;
    $day = (int)$day;
    
    // بررسی محدوده‌ها
    if ($year < 1300 || $year > 1500) {
        return false;
    }
    
    if ($month < 1 || $month > 12) {
        return false;
    }
    
    // روزهای هر ماه شمسی
    $days_in_month = array(
        1 => 31, 2 => 31, 3 => 31, 4 => 31, 5 => 31, 6 => 31,
        7 => 30, 8 => 30, 9 => 30, 10 => 30, 11 => 30, 12 => 29
    );
    
    // سال کبیسه
    if ($month == 12 && wf_is_jalali_leap_year($year)) {
        $days_in_month[12] = 30;
    }
    
    if ($day < 1 || $day > $days_in_month[$month]) {
        return false;
    }
    
    return true;
}

/**
 * بررسی سال کبیسه شمسی
 */
function wf_is_jalali_leap_year($year) {
    $year = (int)$year;
    $leap_years = array(1, 5, 9, 13, 17, 22, 26, 30);
    $remainder = $year % 33;
    return in_array($remainder, $leap_years);
}

/**
 * فرمت کردن تاریخ و زمان به فارسی
 */
function wf_format_datetime_fa($datetime, $format = 'Y/m/d H:i') {
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00') {
        return '';
    }
    
    $timestamp = strtotime($datetime);
    
    if ($timestamp === false) {
        return $datetime;
    }
    
    $jalali_date = wf_convert_to_jalali(date('Y-m-d', $timestamp), 'Y/m/d');
    $time = date('H:i', $timestamp);
    
    if (strpos($format, 'H:i') !== false) {
        return $jalali_date . ' ' . $time;
    }
    
    if (strpos($format, 'H:i:s') !== false) {
        $time = date('H:i:s', $timestamp);
        return $jalali_date . ' ' . $time;
    }
    
    return $jalali_date;
}

/**
 * دریافت تاریخ امروز به شمسی
 */
function wf_get_current_jalali_date($format = 'Y/m/d') {
    $current_time = current_time('mysql');
    list($date_part) = explode(' ', $current_time);
    return wf_convert_to_jalali($date_part, $format);
}

/**
 * محاسبه سن بر اساس تاریخ تولد
 */
function wf_calculate_age($birth_date) {
    if (empty($birth_date)) {
        return null;
    }
    
    // اگر تاریخ شمسی است، به میلادی تبدیل کن
    if (wf_validate_jalali_date($birth_date)) {
        $birth_date = wf_convert_to_gregorian($birth_date);
    }
    
    $birth_timestamp = strtotime($birth_date);
    
    if ($birth_timestamp === false) {
        return null;
    }
    
    $now = time();
    $age_seconds = $now - $birth_timestamp;
    
    $age_years = floor($age_seconds / (365 * 24 * 3600));
    
    return $age_years;
}

// ==================== توابع اعتبارسنجی ====================

/**
 * اعتبارسنجی کد ملی
 */
function wf_validate_national_code($code) {
    if (empty($code)) {
        return false;
    }
    
    // حذف فاصله و کاراکترهای غیرعددی
    $code = preg_replace('/[^0-9]/', '', $code);
    
    // بررسی طول کد
    if (strlen($code) != 10) {
        return false;
    }
    
    // بررسی اینکه همه ارقام یکسان نباشند
    if (preg_match('/^(\d)\1{9}$/', $code)) {
        return false;
    }
    
    // محاسبه مجموع کنترل
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += (int)$code[$i] * (10 - $i);
    }
    
    $remainder = $sum % 11;
    $control_digit = (int)$code[9];
    
    if ($remainder < 2) {
        return $control_digit == $remainder;
    } else {
        return $control_digit == (11 - $remainder);
    }
}

/**
 * اعتبارسنجی شماره موبایل
 */
function wf_validate_mobile($mobile) {
    if (empty($mobile)) {
        return false;
    }
    
    // حذف فاصله و کاراکترهای غیرعددی
    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    
    // بررسی طول شماره
    if (strlen($mobile) != 11) {
        return false;
    }
    
    // بررسی پیش‌شماره
    if (!preg_match('/^09[0-9]{9}$/', $mobile)) {
        return false;
    }
    
    return true;
}

/**
 * اعتبارسنجی ایمیل
 */
function wf_validate_email($email) {
    if (empty($email)) {
        return false;
    }
    
    // بررسی فرمت کلی
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // بررسی دامنه
    $domain = substr(strrchr($email, "@"), 1);
    if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
        return false;
    }
    
    return true;
}

/**
 * اعتبارسنجی شماره حساب بانکی
 */
function wf_validate_bank_account($account) {
    if (empty($account)) {
        return false;
    }
    
    // حذف فاصله و کاراکترهای غیرعددی
    $account = preg_replace('/[^0-9]/', '', $account);
    
    // بررسی طول شماره حساب
    if (strlen($account) < 10 || strlen($account) > 20) {
        return false;
    }
    
    // الگوریتم اعتبارسنجی ساده
    // در عمل نیاز به الگوریتم خاص بانک‌ها داریم
    return true;
}

/**
 * اعتبارسنجی شماره شبا
 */
function wf_validate_sheba($sheba) {
    if (empty($sheba)) {
        return false;
    }
    
    // حذف فاصله و کاراکترهای غیرعددی و حروف
    $sheba = preg_replace('/[^A-Z0-9]/', '', strtoupper($sheba));
    
    // بررسی طول شبا
    if (strlen($sheba) != 26) {
        return false;
    }
    
    // جابجایی ۴ کاراکتر اول به انتها
    $first_four = substr($sheba, 0, 4);
    $rest = substr($sheba, 4);
    $rearranged = $rest . $first_four;
    
    // تبدیل حروف به اعداد (A=10, B=11, ..., Z=35)
    $converted = '';
    for ($i = 0; $i < strlen($rearranged); $i++) {
        $char = $rearranged[$i];
        if (ctype_alpha($char)) {
            $converted .= (ord($char) - 55);
        } else {
            $converted .= $char;
        }
    }
    
    // محاسبه باقیمانده تقسیم بر ۹۷
    $remainder = bcmod($converted, '97');
    
    return $remainder == 1;
}

// ==================== توابع امنیتی ====================

/**
 * رمزنگاری داده‌های حساس
 */
function wf_encrypt_data($data, $key = null) {
    if (empty($data)) {
        return $data;
    }
    
    if ($key === null) {
        $key = AUTH_KEY;
    }
    
    $method = 'AES-256-CBC';
    $iv_length = openssl_cipher_iv_length($method);
    $iv = openssl_random_pseudo_bytes($iv_length);
    
    $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
    
    return base64_encode($iv . $encrypted);
}

/**
 * رمزگشایی داده‌های حساس
 */
function wf_decrypt_data($encrypted_data, $key = null) {
    if (empty($encrypted_data)) {
        return $encrypted_data;
    }
    
    if ($key === null) {
        $key = AUTH_KEY;
    }
    
    $data = base64_decode($encrypted_data);
    $method = 'AES-256-CBC';
    $iv_length = openssl_cipher_iv_length($method);
    
    $iv = substr($data, 0, $iv_length);
    $encrypted = substr($data, $iv_length);
    
    return openssl_decrypt($encrypted, $method, $key, 0, $iv);
}

/**
 * هش کردن رمز عبور
 */
function wf_hash_password($password) {
    return wp_hash_password($password);
}

/**
 * بررسی تطابق رمز عبور
 */
function wf_check_password($password, $hash) {
    return wp_check_password($password, $hash);
}

/**
 * ایجاد توکن امنیتی
 */
function wf_generate_security_token($length = 32) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $characters_length = strlen($characters);
    $token = '';
    
    for ($i = 0; $i < $length; $i++) {
        $token .= $characters[random_int(0, $characters_length - 1)];
    }
    
    return $token;
}

/**
 * جلوگیری از حملات XSS
 */
function wf_sanitize_input($input) {
    if (is_array($input)) {
        return array_map('wf_sanitize_input', $input);
    }
    
    // حذف تگ‌های HTML و PHP
    $input = strip_tags($input);
    
    // تبدیل کاراکترهای خاص
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    // حذف کاراکترهای کنترل
    $input = preg_replace('/[\x00-\x1F\x7F]/u', '', $input);
    
    // نرمال‌سازی خطوط جدید
    $input = preg_replace('/\r\n|\r|\n/', "\n", $input);
    
    return trim($input);
}

/**
 * اعتبارسنجی و پاکسازی آپلود فایل
 */
function wf_validate_uploaded_file($file, $allowed_types = array(), $max_size = 10485760) {
    $errors = array();
    
    // بررسی وجود فایل
    if (empty($file) || $file['error'] == UPLOAD_ERR_NO_FILE) {
        $errors[] = 'هیچ فایلی انتخاب نشده است';
        return array('success' => false, 'errors' => $errors);
    }
    
    // بررسی خطاهای آپلود
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = array(
            UPLOAD_ERR_INI_SIZE => 'حجم فایل بیشتر از حد مجاز است',
            UPLOAD_ERR_FORM_SIZE => 'حجم فایل بیشتر از حد مجاز فرم است',
            UPLOAD_ERR_PARTIAL => 'فایل به طور کامل آپلود نشد',
            UPLOAD_ERR_NO_TMP_DIR => 'پوشه موقت وجود ندارد',
            UPLOAD_ERR_CANT_WRITE => 'خطا در نوشتن فایل روی دیسک',
            UPLOAD_ERR_EXTENSION => 'آپلود فایل توسط یکی از افزونه‌ها متوقف شد'
        );
        
        $errors[] = $upload_errors[$file['error']] ?? 'خطای ناشناخته در آپلود فایل';
        return array('success' => false, 'errors' => $errors);
    }
    
    // بررسی حجم فایل
    if ($file['size'] > $max_size) {
        $errors[] = sprintf('حجم فایل باید کمتر از %s باشد', wf_format_bytes($max_size));
    }
    
    // بررسی نوع فایل
    if (!empty($allowed_types)) {
        $file_info = wp_check_filetype($file['name']);
        
        if (!$file_info['type'] || !in_array($file_info['type'], $allowed_types)) {
            $errors[] = sprintf('نوع فایل مجاز نیست. فقط انواع زیر مجاز هستند: %s', 
                implode(', ', $allowed_types));
        }
    }
    
    // بررسی محتوای فایل برای امنیت
    $file_content = file_get_contents($file['tmp_name']);
    if (preg_match('/<\s*script|<\?php|eval\s*\(|base64_decode/i', $file_content)) {
        $errors[] = 'فایل حاوی کد مخرب است';
    }
    
    if (empty($errors)) {
        return array(
            'success' => true,
            'filename' => sanitize_file_name($file['name']),
            'filepath' => $file['tmp_name'],
            'filesize' => $file['size'],
            'filetype' => $file['type']
        );
    }
    
    return array('success' => false, 'errors' => $errors);
}

/**
 * فرمت کردن حجم فایل
 */
function wf_format_bytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// ==================== توابع کاربردی ====================

/**
 * ایجاد GUID یکتا
 */
function wf_generate_guid() {
    if (function_exists('com_create_guid') === true) {
        return trim(com_create_guid(), '{}');
    }
    
    $data = random_bytes(16);
    
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * ایجاد کد تصادفی
 */
function wf_generate_random_code($length = 6, $type = 'numeric') {
    switch ($type) {
        case 'alphanumeric':
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
        case 'alpha':
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
        case 'numeric':
        default:
            $characters = '0123456789';
            break;
    }
    
    $code = '';
    $max = strlen($characters) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, $max)];
    }
    
    return $code;
}

/**
 * فرمت کردن شماره تلفن
 */
function wf_format_phone_number($phone) {
    if (empty($phone)) {
        return '';
    }
    
    // حذف همه کاراکترهای غیرعددی
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // اگر با ۰ شروع شده، حذف کن
    if (substr($phone, 0, 1) == '0') {
        $phone = substr($phone, 1);
    }
    
    // اگر با ۹۸ شروع شده، حذف کن
    if (substr($phone, 0, 2) == '98') {
        $phone = substr($phone, 2);
    }
    
    // فرمت کردن
    if (strlen($phone) == 10) {
        return sprintf('0%s-%s-%s',
            substr($phone, 0, 3),
            substr($phone, 3, 3),
            substr($phone, 6, 4)
        );
    }
    
    return $phone;
}

/**
 * ایجاد پیوند مناسب برای فارسی
 */
function wf_sanitize_permalink($string) {
    $string = trim($string);
    
    // حذف کاراکترهای خاص
    $string = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $string);
    
    // جایگزینی فاصله با خط تیره
    $string = preg_replace('/\s+/', '-', $string);
    
    // حذف خط تیره‌های تکراری
    $string = preg_replace('/-+/', '-', $string);
    
    // حذف خط تیره از ابتدا و انتها
    $string = trim($string, '-');
    
    return $string;
}

/**
 * کوتاه کردن متن با حفظ کلمات
 */
function wf_truncate_text($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text, 'UTF-8') <= $length) {
        return $text;
    }
    
    $truncated = mb_substr($text, 0, $length, 'UTF-8');
    $last_space = mb_strrpos($truncated, ' ', 0, 'UTF-8');
    
    if ($last_space !== false) {
        $truncated = mb_substr($truncated, 0, $last_space, 'UTF-8');
    }
    
    return $truncated . $suffix;
}

/**
 * استخراج کلمات کلیدی از متن
 */
function wf_extract_keywords($text, $max_keywords = 5) {
    $text = strip_tags($text);
    $text = mb_strtolower($text, 'UTF-8');
    
    // حذف کاراکترهای خاص
    $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
    
    // جدا کردن کلمات
    $words = preg_split('/\s+/', $text);
    
    // حذف کلمات تکی و کوتاه
    $words = array_filter($words, function($word) {
        return mb_strlen($word, 'UTF-8') > 2;
    });
    
    // شمارش تکرار کلمات
    $word_count = array_count_values($words);
    
    // مرتب‌سازی بر اساس تکرار
    arsort($word_count);
    
    // گرفتن کلمات برتر
    $keywords = array_slice(array_keys($word_count), 0, $max_keywords);
    
    return $keywords;
}

/**
 * بررسی اینکه آیا رشته حاوی حروف فارسی است
 */
function wf_contains_persian($string) {
    return preg_match('/[\x{0600}-\x{06FF}]/u', $string);
}

/**
 * نرمال‌سازی متن فارسی
 */
function wf_normalize_persian_text($text) {
    if (empty($text)) {
        return $text;
    }
    
    // جایگزینی حروف عربی با فارسی
    $arabic_chars = array(
        'ك' => 'ک', 'ي' => 'ی', 'ة' => 'ه', 'ؤ' => 'و',
        'إ' => 'ا', 'أ' => 'ا', 'ٱ' => 'ا', 'ٲ' => 'ا',
        'ڔ' => 'ر', 'ڕ' => 'ر', 'ڒ' => 'ر', 'ڑ' => 'ر',
        'ړ' => 'ر', 'ڙ' => 'ر', 'ژ' => 'ژ', 'ټ' => 'ت',
        'ٿ' => 'ت', 'ٽ' => 'ت', 'ٺ' => 'ت', 'ۃ' => 'ه'
    );
    
    $text = strtr($text, $arabic_chars);
    
    // نرمال‌سازی فاصله‌ها
    $text = preg_replace('/\s+/', ' ', $text);
    
    // حذف فاصله قبل و بعد از علائم نگارشی
    $text = preg_replace('/\s+([\.،:؛!؟])/', '$1', $text);
    $text = preg_replace('/([\.،:؛!؟])\s+/', '$1 ', $text);
    
    return trim($text);
}

// ==================== توابع مرتبط با فیلدها ====================

/**
 * محاسبه درصد تکمیل فیلدهای ضروری
 */
function wf_calculate_completion_percentage($data, $personnel_id = null) {
    $fields = wf_get_all_fields();
    
    $required_fields = array();
    $completed_fields = 0;
    $warnings = array();
    
    foreach ($fields as $field) {
        $field_key = $field['field_key'];
        $field_value = $data[$field_key] ?? null;
        
        // اگر فیلد ضروری است
        if ($field['is_required'] == 1) {
            $required_fields[] = $field_key;
            
            // بررسی پر بودن فیلد
            if (!empty($field_value) && trim($field_value) !== '') {
                $completed_fields++;
            } else {
                $warnings[] = sprintf('فیلد "%s" پر نشده است', $field['field_name']);
            }
        }
        
        // اعتبارسنجی فیلدها بر اساس نوع
        if (!empty($field_value) && $field['validation_rules']) {
            $validation_result = wf_validate_field_value($field_value, $field);
            if (!$validation_result['valid']) {
                $warnings[] = sprintf('فیلد "%s": %s', $field['field_name'], $validation_result['message']);
            }
        }
    }
    
    $total_required = count($required_fields);
    $percentage = $total_required > 0 ? round(($completed_fields / $total_required) * 100, 2) : 100;
    
    // بررسی تکراری بودن فیلدهای کلید
    if ($personnel_id) {
        $duplicate_check = wf_check_duplicate_key_fields($data, $personnel_id);
        if (!$duplicate_check['valid']) {
            $warnings[] = $duplicate_check['message'];
        }
    }
    
    return array(
        'total_fields' => count($fields),
        'required_fields' => $required_fields,
        'required_completed' => $completed_fields,
        'required_total' => $total_required,
        'percentage' => $percentage,
        'has_warnings' => !empty($warnings),
        'warnings' => $warnings
    );
}

/**
 * اعتبارسنجی مقدار فیلد
 */
function wf_validate_field_value($value, $field) {
    $type = $field['field_type'];
    $rules = $field['validation_rules'] ?? array();
    
    $errors = array();
    
    // بررسی خالی بودن برای فیلدهای ضروری
    if ($field['is_required'] == 1 && empty($value) && $value !== '0') {
        return array(
            'valid' => false,
            'message' => 'این فیلد ضروری است'
        );
    }
    
    // اعتبارسنجی بر اساس نوع فیلد
    switch ($type) {
        case 'number':
            if (!is_numeric($value) && !empty($value)) {
                $errors[] = 'مقدار باید عددی باشد';
            } else {
                $num_value = (float)$value;
                
                if (isset($rules['min']) && $num_value < $rules['min']) {
                    $errors[] = sprintf('مقدار نمی‌تواند کمتر از %s باشد', $rules['min']);
                }
                
                if (isset($rules['max']) && $num_value > $rules['max']) {
                    $errors[] = sprintf('مقدار نمی‌تواند بیشتر از %s باشد', $rules['max']);
                }
                
                if (isset($rules['integer']) && $rules['integer'] && floor($num_value) != $num_value) {
                    $errors[] = 'مقدار باید عدد صحیح باشد';
                }
            }
            break;
            
        case 'decimal':
            if (!is_numeric($value) && !empty($value)) {
                $errors[] = 'مقدار باید عددی باشد';
            } else {
                $num_value = (float)$value;
                
                if (isset($rules['min']) && $num_value < $rules['min']) {
                    $errors[] = sprintf('مقدار نمی‌تواند کمتر از %s باشد', $rules['min']);
                }
                
                if (isset($rules['max']) && $num_value > $rules['max']) {
                    $errors[] = sprintf('مقدار نمی‌تواند بیشتر از %s باشد', $rules['max']);
                }
                
                if (isset($rules['precision']) && strlen(substr(strrchr($value, "."), 1)) > $rules['precision']) {
                    $errors[] = sprintf('حداکثر %s رقم اعشار مجاز است', $rules['precision']);
                }
            }
            break;
            
        case 'date':
            if (!empty($value) && !wf_validate_jalali_date($value)) {
                $errors[] = 'تاریخ معتبر نیست';
            }
            break;
            
        case 'time':
            if (!empty($value) && !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $value)) {
                $errors[] = 'زمان معتبر نیست';
            }
            break;
            
        case 'text':
            if (isset($rules['min_length']) && mb_strlen($value, 'UTF-8') < $rules['min_length']) {
                $errors[] = sprintf('حداقل %s کاراکتر نیاز است', $rules['min_length']);
            }
            
            if (isset($rules['max_length']) && mb_strlen($value, 'UTF-8') > $rules['max_length']) {
                $errors[] = sprintf('حداکثر %s کاراکتر مجاز است', $rules['max_length']);
            }
            
            if (isset($rules['pattern']) && !preg_match($rules['pattern'], $value)) {
                $errors[] = 'فرمت وارد شده معتبر نیست';
            }
            break;
            
        case 'select':
            if (!empty($value) && isset($rules['options']) && !in_array($value, $rules['options'])) {
                $errors[] = 'مقدار انتخاب شده معتبر نیست';
            }
            break;
    }
    
    // اعتبارسنجی سفارشی
    if (isset($rules['custom_validation']) && is_callable($rules['custom_validation'])) {
        $custom_result = call_user_func($rules['custom_validation'], $value, $field);
        if (!$custom_result['valid']) {
            $errors[] = $custom_result['message'];
        }
    }
    
    if (empty($errors)) {
        return array('valid' => true, 'message' => '');
    }
    
    return array(
        'valid' => false,
        'message' => implode('، ', $errors)
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
            // حذف کاراکترهای غیرعددی به جز نقطه و منفی
            $value = preg_replace('/[^0-9\.\-]/', '', $value);
            $value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            return $value === false ? null : $value;
            
        case 'date':
            // حذف کاراکترهای غیرمجاز
            $value = preg_replace('/[^0-9\/\-]/', '', $value);
            return trim($value);
            
        case 'time':
            // حذف کاراکترهای غیرمجاز
            $value = preg_replace('/[^0-9:]/', '', $value);
            return trim($value);
            
        case 'datetime':
            // حذف کاراکترهای غیرمجاز
            $value = preg_replace('/[^0-9\/\-\s:]/', '', $value);
            return trim($value);
            
        case 'checkbox':
            return $value ? 1 : 0;
            
        case 'select':
            return sanitize_text_field($value);
            
        case 'text':
        default:
            return sanitize_textarea_field($value);
    }
}

/**
 * بررسی تکراری بودن فیلدهای کلید
 */
function wf_check_duplicate_key_fields($data, $exclude_personnel_id = null) {
    global $wpdb;
    
    $key_fields = wf_get_key_fields();
    
    foreach ($key_fields as $field) {
        $field_key = $field['field_key'];
        $field_value = $data[$field_key] ?? null;
        
        if (!empty($field_value)) {
            // جستجوی مقادیر تکراری
            $query = "SELECT COUNT(*) FROM {$wpdb->prefix}wf_personnel 
                     WHERE JSON_EXTRACT(data, '$.{$field_key}') = %s 
                     AND is_deleted = 0";
            
            $params = array($field_value);
            
            if ($exclude_personnel_id) {
                $query .= " AND id != %d";
                $params[] = $exclude_personnel_id;
            }
            
            $count = (int)$wpdb->get_var($wpdb->prepare($query, $params));
            
            if ($count > 0) {
                return array(
                    'valid' => false,
                    'message' => sprintf('مقدار "%s" برای فیلد "%s" تکراری است', $field_value, $field['field_name'])
                );
            }
        }
    }
    
    return array('valid' => true, 'message' => '');
}

/**
 * دریافت فیلدهای کلید
 */
function wf_get_key_fields() {
    global $wpdb;
    
    return $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}wf_fields WHERE is_key = 1 AND status = 'active'",
        ARRAY_A
    );
}

// ==================== توابع مرتبط با کاربران ====================

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
            'message' => 'دسترسی نامعتبر'
        );
    }
    
    // ادمین وردپرس می‌تواند مستقیماً ویرایش کند
    if (in_array('administrator', $user->roles)) {
        return array(
            'can_edit' => true,
            'direct_edit' => true,
            'message' => ''
        );
    }
    
    // مدیر سازمان می‌تواند مستقیماً ویرایش کند
    if (in_array('wf_org_manager', $user->roles)) {
        return array(
            'can_edit' => true,
            'direct_edit' => true,
            'message' => ''
        );
    }
    
    // مدیر اداره فقط می‌تواند پرسنل اداره خود را ویرایش کند
    if (in_array('wf_department_manager', $user->roles)) {
        $user_department_id = get_user_meta($user_id, 'wf_department_id', true);
        
        if ($user_department_id == $personnel['department_id']) {
            // بررسی فیلدهای قفل
            $has_locked_fields = wf_personnel_has_locked_fields($personnel_id);
            
            if ($has_locked_fields) {
                return array(
                    'can_edit' => true,
                    'direct_edit' => false,
                    'message' => 'برای ویرایش فیلدهای قفل نیاز به تایید مدیر سیستم دارید'
                );
            }
            
            return array(
                'can_edit' => true,
                'direct_edit' => true,
                'message' => ''
            );
        }
    }
    
    return array(
        'can_edit' => false,
        'direct_edit' => false,
        'message' => 'شما مجوز ویرایش این پرسنل را ندارید'
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
            'message' => 'دسترسی نامعتبر'
        );
    }
    
    // ادمین وردپرس می‌تواند مستقیماً حذف کند
    if (in_array('administrator', $user->roles)) {
        return array(
            'can_delete' => true,
            'direct_delete' => true,
            'message' => ''
        );
    }
    
    // مدیر سازمان می‌تواند مستقیماً حذف کند
    if (in_array('wf_org_manager', $user->roles)) {
        return array(
            'can_delete' => true,
            'direct_delete' => true,
            'message' => ''
        );
    }
    
    // مدیر اداره فقط می‌تواند پرسنل اداره خود را حذف کند
    if (in_array('wf_department_manager', $user->roles)) {
        $user_department_id = get_user_meta($user_id, 'wf_department_id', true);
        
        if ($user_department_id == $personnel['department_id']) {
            return array(
                'can_delete' => true,
                'direct_delete' => false, // نیاز به تایید مدیر سیستم
                'message' => 'برای حذف پرسنل نیاز به تایید مدیر سیستم دارید'
            );
        }
    }
    
    return array(
        'can_delete' => false,
        'direct_delete' => false,
        'message' => 'شما مجوز حذف این پرسنل را ندارید'
    );
}

/**
 * به‌روزرسانی نقش کاربر در اداره
 */
function wf_update_user_department_role($user_id, $department_id) {
    $user = get_user_by('id', $user_id);
    
    if (!$user) {
        return false;
    }
    
    // حذف نقش مدیر اداره از همه کاربران برای این اداره
    $args = array(
        'meta_key' => 'wf_department_id',
        'meta_value' => $department_id,
        'exclude' => array($user_id)
    );
    
    $old_managers = get_users($args);
    foreach ($old_managers as $old_manager) {
        $old_manager->remove_role('wf_department_manager');
        delete_user_meta($old_manager->ID, 'wf_department_id');
    }
    
    // افزودن نقش به مدیر جدید
    if ($department_id) {
        $user->add_role('wf_department_manager');
        update_user_meta($user_id, 'wf_department_id', $department_id);
    } else {
        $user->remove_role('wf_department_manager');
        delete_user_meta($user_id, 'wf_department_id');
    }
    
    return true;
}

/**
 * دریافت مدیران ادارات
 */
function wf_get_department_managers() {
    $args = array(
        'role__in' => array('wf_department_manager', 'wf_org_manager', 'administrator'),
        'fields' => array('ID', 'display_name', 'user_email')
    );
    
    $users = get_users($args);
    $managers = array();
    
    foreach ($users as $user) {
        $department_id = get_user_meta($user->ID, 'wf_department_id', true);
        $department = $department_id ? wf_get_department($department_id) : null;
        
        $managers[] = array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'department_id' => $department_id,
            'department_name' => $department ? $department['name'] : 'مدیر سازمان'
        );
    }
    
    return $managers;
}

// ==================== توابع مرتبط با ادارات ====================

/**
 * به‌روزرسانی تعداد پرسنل اداره
 */
function wf_update_department_personnel_count($department_id) {
    global $wpdb;
    
    $count = wf_count_department_personnel($department_id);
    
    $wpdb->update(
        $wpdb->prefix . 'wf_departments',
        array(
            'personnel_count' => $count,
            'updated_at' => current_time('mysql')
        ),
        array('id' => $department_id)
    );
    
    // محاسبه درصد تکمیل متوسط اداره
    wf_update_department_completion_percentage($department_id);
    
    return $count;
}

/**
 * محاسبه درصد تکمیل متوسط اداره
 */
function wf_update_department_completion_percentage($department_id) {
    global $wpdb;
    
    $avg_completion = $wpdb->get_var($wpdb->prepare(
        "SELECT AVG(completion_percentage) 
         FROM {$wpdb->prefix}wf_personnel 
         WHERE department_id = %d AND is_deleted = 0",
        $department_id
    ));
    
    $wpdb->update(
        $wpdb->prefix . 'wf_departments',
        array(
            'completion_percentage' => round($avg_completion, 2),
            'updated_at' => current_time('mysql')
        ),
        array('id' => $department_id)
    );
    
    return $avg_completion;
}

/**
 * بررسی چرخه در ساختار درختی ادارات
 */
function wf_check_department_cycle($department_id, $new_parent_id) {
    if (!$new_parent_id) {
        return false;
    }
    
    $current_parent = $new_parent_id;
    $visited = array($department_id);
    
    while ($current_parent) {
        if (in_array($current_parent, $visited)) {
            return true;
        }
        
        $visited[] = $current_parent;
        $department = wf_get_department($current_parent);
        $current_parent = $department ? $department['parent_id'] : null;
    }
    
    return false;
}

/**
 * شمارش زیرمجموعه‌های اداره
 */
function wf_count_department_children($department_id) {
    global $wpdb;
    
    return (int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wf_departments 
         WHERE parent_id = %d AND status != 'suspended'",
        $department_id
    ));
}

/**
 * غیرفعال کردن پرسنل یک اداره
 */
function wf_deactivate_department_personnel($department_id) {
    global $wpdb;
    
    return $wpdb->update(
        $wpdb->prefix . 'wf_personnel',
        array(
            'status' => 'inactive',
            'updated_at' => current_time('mysql')
        ),
        array(
            'department_id' => $department_id,
            'is_deleted' => 0
        )
    );
}

/**
 * دریافت ساختار سلسله مراتبی ادارات
 */
function wf_get_department_hierarchy($include_personnel = false) {
    $departments = wf_get_all_departments();
    $hierarchy = array();
    
    // ایجاد آرایه‌ای با کلید id
    $indexed = array();
    foreach ($departments as $dept) {
        $indexed[$dept['id']] = $dept;
        $indexed[$dept['id']]['children'] = array();
        
        if ($include_personnel) {
            $indexed[$dept['id']]['personnel'] = wf_get_department_personnel($dept['id']);
        }
    }
    
    // ساختن درخت
    foreach ($indexed as $id => &$dept) {
        if ($dept['parent_id'] && isset($indexed[$dept['parent_id']])) {
            $indexed[$dept['parent_id']]['children'][] = &$dept;
        } elseif ($dept['parent_id'] === null || $dept['parent_id'] == 0) {
            $hierarchy[] = &$dept;
        }
    }
    
    return $hierarchy;
}

// ==================== توابع مرتبط با دوره‌ها ====================

/**
 * بررسی تداخل دوره‌ها
 */
function wf_check_period_overlap($start_date, $end_date, $exclude_period_id = null) {
    global $wpdb;
    
    $query = "SELECT COUNT(*) FROM {$wpdb->prefix}wf_periods 
              WHERE status = 'active' 
              AND (
                  (start_date <= %s AND end_date >= %s) OR
                  (start_date <= %s AND end_date >= %s) OR
                  (start_date >= %s AND end_date <= %s)
              )";
    
    $params = array($end_date, $start_date, $start_date, $end_date, $start_date, $end_date);
    
    if ($exclude_period_id) {
        $query .= " AND id != %d";
        $params[] = $exclude_period_id;
    }
    
    $count = (int)$wpdb->get_var($wpdb->prepare($query, $params));
    
    return $count > 0;
}

/**
 * فعال‌سازی یک دوره و غیرفعال کردن بقیه
 */
function wf_activate_period($period_id) {
    global $wpdb;
    
    // غیرفعال کردن همه دوره‌ها
    $wpdb->update(
        $wpdb->prefix . 'wf_periods',
        array('is_active' => 0),
        array('is_active' => 1)
    );
    
    // فعال‌سازی دوره مورد نظر
    $result = $wpdb->update(
        $wpdb->prefix . 'wf_periods',
        array('is_active' => 1),
        array('id' => $period_id)
    );
    
    if ($result) {
        wf_log_user_action(
            'period_activated',
            'دوره فعال شد',
            array('period_id' => $period_id)
        );
    }
    
    return $result;
}

// ==================== توابع مرتبط با درخواست‌های تایید ====================

/**
 * اجرای درخواست تایید شده
 */
function wf_execute_approved_request($approval) {
    $request_type = $approval['request_type'];
    $request_data = json_decode($approval['request_data'], true);
    
    switch ($request_type) {
        case 'add_personnel':
            return wf_create_personnel($request_data['personnel_data']);
            
        case 'edit_personnel':
            return wf_update_personnel(
                $request_data['personnel_id'],
                $request_data['changes'],
                false // نیازی به تایید مجدد نیست
            );
            
        case 'delete_personnel':
            return wf_delete_personnel(
                $request_data['personnel_id'],
                false, // حذف نرم
                false // نیازی به تایید مجدد نیست
            );
            
        case 'edit_field':
            return wf_update_field(
                $request_data['field_id'],
                $request_data['changes']
            );
            
        case 'add_department':
            return wf_create_department($request_data['department_data']);
            
        default:
            return array(
                'success' => false,
                'message' => 'نوع درخواست پشتیبانی نمی‌شود'
            );
    }
}

/**
 * ارسال اعلان درخواست تایید
 */
function wf_send_approval_notification($approval_id) {
    global $wpdb;
    
    $approval = wf_get_approval_request($approval_id);
    
    if (!$approval) {
        return false;
    }
    
    // دریافت ادمین‌های سیستم
    $admins = get_users(array(
        'role' => 'administrator',
        'fields' => array('user_email', 'display_name')
    ));
    
    $requester = get_user_by('id', $approval['requester_id']);
    $department = $approval['department_id'] ? wf_get_department($approval['department_id']) : null;
    
    $subject = sprintf('درخواست تایید جدید - %s', 
        wf_get_approval_type_name($approval['request_type']));
    
    $message = sprintf('
        <div dir="rtl">
            <h3>درخواست تایید جدید</h3>
            <p>یک درخواست تایید جدید در سیستم ثبت شده است:</p>
            
            <table border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse;">
                <tr>
                    <td><strong>نوع درخواست:</strong></td>
                    <td>%s</td>
                </tr>
                <tr>
                    <td><strong>درخواست‌دهنده:</strong></td>
                    <td>%s</td>
                </tr>
                <tr>
                    <td><strong>اداره:</strong></td>
                    <td>%s</td>
                </tr>
                <tr>
                    <td><strong>اولویت:</strong></td>
                    <td>%s</td>
                </tr>
                <tr>
                    <td><strong>تاریخ درخواست:</strong></td>
                    <td>%s</td>
                </tr>
            </table>
            
            <p>لطفاً برای بررسی درخواست به پنل مدیریت مراجعه کنید.</p>
            
            <hr>
            <p style="color: #666; font-size: 12px;">
                این ایمیل به صورت خودکار ارسال شده است. لطفاً به آن پاسخ ندهید.
            </p>
        </div>
    ',
        wf_get_approval_type_name($approval['request_type']),
        $requester ? $requester->display_name : 'نامشخص',
        $department ? $department['name'] : 'نامشخص',
        wf_get_priority_name($approval['priority']),
        wf_format_datetime_fa($approval['created_at'])
    );
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    foreach ($admins as $admin) {
        wp_mail($admin->user_email, $subject, $message, $headers);
    }
    
    return true;
}

/**
 * ارسال پاسخ درخواست تایید
 */
function wf_send_approval_response_notification($approval_id, $action) {
    global $wpdb;
    
    $approval = wf_get_approval_request($approval_id);
    
    if (!$approval) {
        return false;
    }
    
    $requester = get_user_by('id', $approval['requester_id']);
    
    if (!$requester) {
        return false;
    }
    
    $action_names = array(
        'approved' => 'تایید',
        'rejected' => 'رد',
        'needs_revision' => 'نیاز به اصلاح'
    );
    
    $subject = sprintf('پاسخ درخواست تایید - %s', 
        $action_names[$action] ?? 'بررسی شده');
    
    $message = sprintf('
        <div dir="rtl">
            <h3>پاسخ درخواست تایید</h3>
            <p>درخواست تایید شما توسط مدیر سیستم بررسی شد:</p>
            
            <table border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse;">
                <tr>
                    <td><strong>نوع درخواست:</strong></td>
                    <td>%s</td>
                </tr>
                <tr>
                    <td><strong>نتیجه بررسی:</strong></td>
                    <td><strong style="color: %s">%s</strong></td>
                </tr>
                <tr>
                    <td><strong>تاریخ بررسی:</strong></td>
                    <td>%s</td>
                </tr>
            </table>
            
            <h4>توضیحات مدیر:</h4>
            <div style="background: #f5f5f5; padding: 15px; border-right: 4px solid #ccc;">
                %s
            </div>
            
            <hr>
            <p style="color: #666; font-size: 12px;">
                این ایمیل به صورت خودکار ارسال شده است. لطفاً به آن پاسخ ندهید.
            </p>
        </div>
    ',
        wf_get_approval_type_name($approval['request_type']),
        $action == 'approved' ? '#34a853' : ($action == 'rejected' ? '#ea4335' : '#f9ab00'),
        $action_names[$action] ?? 'بررسی شده',
        wf_format_datetime_fa($approval['reviewed_at']),
        nl2br(esc_html($approval['admin_notes']))
    );
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    return wp_mail($requester->user_email, $subject, $message, $headers);
}

/**
 * دریافت نام نوع درخواست
 */
function wf_get_approval_type_name($type) {
    $names = array(
        'add_personnel' => 'افزودن پرسنل جدید',
        'edit_personnel' => 'ویرایش اطلاعات پرسنل',
        'delete_personnel' => 'حذف پرسنل',
        'edit_field' => 'ویرایش فیلد',
        'add_department' => 'افزودن اداره جدید',
        'other' => 'سایر'
    );
    
    return $names[$type] ?? $type;
}

/**
 * دریافت نام اولویت
 */
function wf_get_priority_name($priority) {
    $names = array(
        'low' => 'کم',
        'normal' => 'معمولی',
        'high' => 'بالا',
        'urgent' => 'فوری'
    );
    
    return $names[$priority] ?? $priority;
}

// ==================== توابع گزارش‌گیری ====================

/**
 * تولید گزارش آماری
 */
function wf_generate_statistical_report($params) {
    $report = array(
        'summary' => array(),
        'departments' => array(),
        'trends' => array(),
        'charts' => array()
    );
    
    // محاسبه آمار کلی
    $report['summary'] = wf_calculate_system_stats();
    
    // آمار ادارات
    $departments = wf_get_all_departments();
    foreach ($departments as $dept) {
        $personnel_count = wf_count_department_personnel($dept['id']);
        $avg_completion = wf_update_department_completion_percentage($dept['id']);
        
        $report['departments'][] = array(
            'id' => $dept['id'],
            'name' => $dept['name'],
            'personnel_count' => $personnel_count,
            'avg_completion' => $avg_completion,
            'manager' => $dept['manager_id'] ? get_user_by('id', $dept['manager_id'])->display_name : 'ندارد'
        );
    }
    
    // روند ماهانه
    $report['trends'] = wf_generate_aggregate_report('monthly_trend');
    
    // داده‌های نمودار
    $report['charts'] = array(
        'completion_distribution' => wf_generate_aggregate_report('completion_stats'),
        'employment_type_distribution' => wf_generate_aggregate_report('employment_type_distribution')
    );
    
    return $report;
}

/**
 * فرمت کردن اعداد به فارسی
 */
function wf_format_number_fa($number, $decimals = 0) {
    $persian_numbers = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    $english_numbers = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    
    $formatted = number_format($number, $decimals);
    return str_replace($english_numbers, $persian_numbers, $formatted);
}

// ==================== توابع کمکی عمومی ====================

/**
 * بررسی وجود کد ملی
 */
function wf_is_national_code_exists($national_code, $exclude_personnel_id = null) {
    global $wpdb;
    
    $query = "SELECT COUNT(*) FROM {$wpdb->prefix}wf_personnel 
              WHERE national_code = %s AND is_deleted = 0";
    
    $params = array($national_code);
    
    if ($exclude_personnel_id) {
        $query .= " AND id != %d";
        $params[] = $exclude_personnel_id;
    }
    
    $count = (int)$wpdb->get_var($wpdb->prepare($query, $params));
    
    return $count > 0;
}

/**
 * بررسی استفاده از فیلد در داده‌ها
 */
function wf_is_field_used($field_id) {
    global $wpdb;
    
    $field = wf_get_field($field_id);
    
    if (!$field) {
        return false;
    }
    
    $count = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wf_personnel 
         WHERE JSON_EXTRACT(data, '$.%s') IS NOT NULL 
         AND JSON_EXTRACT(data, '$.%s') != '' 
         AND is_deleted = 0",
        $field['field_key'],
        $field['field_key']
    ));
    
    return $count > 0;
}

/**
 * بررسی فیلدهای قفل در پرسنل
 */
function wf_personnel_has_locked_fields($personnel_id) {
    global $wpdb;
    
    $locked_fields = $wpdb->get_results(
        "SELECT field_key FROM {$wpdb->prefix}wf_fields WHERE is_locked = 1 AND status = 'active'",
        ARRAY_A
    );
    
    if (empty($locked_fields)) {
        return false;
    }
    
    $personnel = wf_get_personnel($personnel_id);
    
    if (!$personnel || empty($personnel['data'])) {
        return false;
    }
    
    foreach ($locked_fields as $field) {
        $field_key = $field['field_key'];
        if (isset($personnel['data'][$field_key]) && !empty($personnel['data'][$field_key])) {
            return true;
        }
    }
    
    return false;
}

/**
 * ارسال ایمیل عمومی
 */
function wf_send_email($to, $subject, $message, $headers = array()) {
    $default_headers = array('Content-Type: text/html; charset=UTF-8');
    $headers = array_merge($default_headers, $headers);
    
    return wp_mail($to, $subject, $message, $headers);
}

/**
 * ثبت خطا
 */
function wf_log_error($message, $data = array()) {
    error_log('Workforce Error: ' . $message . ' - ' . json_encode($data));
    wf_log_system_action('error', $message, $data, 'error');
}

/**
 * دیباگ اطلاعات
 */
function wf_debug($data, $label = '') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        echo '<pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd; direction: ltr;">';
        if ($label) {
            echo '<strong>' . esc_html($label) . ':</strong><br>';
        }
        print_r($data);
        echo '</pre>';
    }
}

// ==================== توابع AJAX ====================

/**
 * پردازش AJAX عمومی
 */
function wf_process_ajax_request($action, $data) {
    check_ajax_referer('workforce_nonce', 'nonce');
    
    $response = array('success' => false, 'message' => 'عملیات ناموفق');
    
    switch ($action) {
        case 'save_personnel':
            $response = wf_save_personnel_ajax($data);
            break;
            
        case 'delete_personnel':
            $response = wf_delete_personnel_ajax($data);
            break;
            
        case 'filter_data':
            $response = wf_filter_data_ajax($data);
            break;
            
        case 'export_excel':
            $response = wf_export_excel_ajax($data);
            break;
            
        case 'get_stats':
            $response = wf_get_stats_ajax($data);
            break;
    }
    
    wp_send_json($response);
}

/**
 * ثبت خطای AJAX
 */
function wf_ajax_error($message, $code = 400) {
    status_header($code);
    wp_send_json_error(array('message' => $message));
}

/**
 * موفقیت AJAX
 */
function wf_ajax_success($data = array(), $message = 'عملیات موفقیت‌آمیز بود') {
    wp_send_json_success(array_merge(array('message' => $message), $data));
}

// ==================== فیلترها و اکشن‌های وردپرس ====================

/**
 * افزودن قابلیت‌ها به نقش‌های وردپرس
 */
add_action('init', 'wf_add_capabilities');

function wf_add_capabilities() {
    $roles = array('administrator', 'editor', 'author');
    
    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        
        if ($role) {
            $role->add_cap('wf_view_dashboard');
            $role->add_cap('wf_manage_personnel');
            $role->add_cap('wf_export_data');
        }
    }
}

/**
 * فیلتر کردن محتوا برای نمایش تاریخ شمسی
 */
add_filter('the_content', 'wf_filter_content_dates');

function wf_filter_content_dates($content) {
    // پیدا کردن تاریخ‌های میلادی و تبدیل به شمسی
    $pattern = '/\d{4}-\d{2}-\d{2}/';
    
    return preg_replace_callback($pattern, function($matches) {
        $jalali_date = wf_convert_to_jalali($matches[0], 'Y/m/d');
        return $jalali_date ?: $matches[0];
    }, $content);
}

/**
 * اضافه کردن استایل به head
 */
add_action('wp_head', 'wf_add_inline_styles');

function wf_add_inline_styles() {
    if (is_admin()) {
        return;
    }
    
    $css = '
        .workforce-persian-date {
            font-family: Tahoma, Arial, sans-serif;
            direction: rtl;
        }
        .workforce-required::after {
            content: " *";
            color: #ff0000;
        }
    ';
    
    echo '<style>' . $css . '</style>';
}

// ==================== توابع کمکی نهایی ====================

/**
 * بررسی نسخه PHP
 */
function wf_check_php_version($min_version = '7.4') {
    if (version_compare(PHP_VERSION, $min_version, '<')) {
        return sprintf('نیاز به PHP نسخه %s یا بالاتر دارید. نسخه فعلی: %s', 
            $min_version, PHP_VERSION);
    }
    
    return true;
}

/**
 * بررسی افزونه‌های مورد نیاز
 */
function wf_check_required_extensions() {
    $required = array(
        'json' => 'JSON',
        'mbstring' => 'Multibyte String',
        'openssl' => 'OpenSSL',
        'pdo_mysql' => 'PDO MySQL'
    );
    
    $missing = array();
    
    foreach ($required as $ext => $name) {
        if (!extension_loaded($ext)) {
            $missing[] = $name;
        }
    }
    
    if (!empty($missing)) {
        return sprintf('افزونه‌های زیر مورد نیاز هستند: %s', implode(', ', $missing));
    }
    
    return true;
}

/**
 * به‌روزرسانی خودکار
 */
add_filter('pre_set_site_transient_update_plugins', 'wf_check_for_updates');

function wf_check_for_updates($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }
    
    // در اینجا می‌توانید API خود را برای بررسی آپدیت فراخوانی کنید
    // این یک نمونه ساده است
    
    $plugin_slug = 'workforce-beni-asadd/workforce-beni-asadd.php';
    $current_version = WF_VERSION;
    
    // فرض کنید آخرین نسخه از API گرفته می‌شود
    $latest_version = '1.0.0'; // این مقدار باید از API گرفته شود
    
    if (version_compare($current_version, $latest_version, '<')) {
        $transient->response[$plugin_slug] = (object) array(
            'slug' => 'workforce-beni-asadd',
            'new_version' => $latest_version,
            'url' => 'https://beniasad.ir/workforce',
            'package' => 'https://beniasad.ir/download/workforce-beni-asadd-' . $latest_version . '.zip'
        );
    }
    
    return $transient;
}

/**
 * خلاصه توضیحات پلاگین
 */
function wf_get_plugin_summary() {
    return array(
        'name' => 'کارکرد پرسنل - بنی اسد',
        'version' => WF_VERSION,
        'description' => 'سیستم جامع مدیریت کارکرد پرسنل سازمانی',
        'author' => 'بنی اسد',
        'website' => 'https://beniasad.ir',
        'support' => 'https://support.beniasad.ir',
        'documentation' => 'https://docs.beniasad.ir/workforce'
    );
}

/**
 * گرفتن اطلاعات سرور
 */
function wf_get_server_info() {
    return array(
        'php_version' => PHP_VERSION,
        'mysql_version' => wf_get_mysql_version(),
        'wordpress_version' => get_bloginfo('version'),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'نامشخص',
        'max_upload_size' => ini_get('upload_max_filesize'),
        'max_execution_time' => ini_get('max_execution_time'),
        'memory_limit' => ini_get('memory_limit')
    );
}

/**
 * دریافت نسخه MySQL
 */
function wf_get_mysql_version() {
    global $wpdb;
    return $wpdb->db_version();
}

/**
 * خاتمه دادن به اسکریپت با پیام خطا
 */
function wf_die_with_error($message, $title = 'خطا') {
    wp_die(
        '<h1>' . esc_html($title) . '</h1>' .
        '<p>' . esc_html($message) . '</p>' .
        '<p><a href="' . admin_url() . '">بازگشت به پیشخوان</a></p>',
        $title,
        array('response' => 500)
    );
}

// ==================== رجیستر توابع ====================

/**
 * رجیستر کردن توابع در وردپرس
 */
add_action('init', 'wf_register_helper_functions');

function wf_register_helper_functions() {
    // این تابع برای رجیستر کردن هوک‌ها و فیلترهای اضافی استفاده می‌شود
}

/**
 * بررسی فعال بودن پلاگین
 */
function wf_is_plugin_active() {
    return defined('WF_VERSION');
}

/**
 * گرفتن مسیر پلاگین
 */
function wf_get_plugin_path($file = '') {
    return WF_PLUGIN_DIR . $file;
}

/**
 * گرفتن URL پلاگین
 */
function wf_get_plugin_url($file = '') {
    return WF_PLUGIN_URL . $file;
}

// ==================== خاتمه فایل ====================

/**
 * تابع خاتمه‌دهنده برای تمیز کردن منابع
 */
function wf_shutdown() {
    // آزاد کردن منابع اگر نیاز باشد
}

register_shutdown_function('wf_shutdown');

?>