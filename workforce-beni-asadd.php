<?php
/**
 * Plugin Name: کارکرد پرسنل - بنی اسد
 * Plugin URI: https://beniasad.ir/
 * Description: سیستم جامع مدیریت کارکرد پرسنل سازمانی با رابط کاربری پیشرفته شبه‌اکسل
 * Version: 1.0.0
 * Author: بنی اسد
 * Author URI: https://beniasad.ir/
 * License: GPL v2 or later
 * Text Domain: workforce-beni-asad
 * Domain Path: /languages
 */

// ==================== امنیت و تعریف ثابت‌ها ====================

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// تعریف ثابت‌های پلاگین
define('WF_VERSION', '1.0.0');
define('WF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WF_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WF_TABLE_PREFIX', 'wf_');

// ==================== بررسی وابستگی‌ها ====================

/**
 * بررسی وجود وردپرس و PHP
 */
function wf_check_requirements() {
    $errors = array();
    
    // بررسی نسخه PHP
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $errors[] = 'نیاز به PHP نسخه 7.4 یا بالاتر دارید. نسخه فعلی: ' . PHP_VERSION;
    }
    
    // بررسی وجود وردپرس
    if (!function_exists('wp_get_current_user')) {
        $errors[] = 'وردپرس یافت نشد!';
    }
    
    // بررسی وجود توابع ضروری
    $required_functions = array('mysqli_connect', 'json_encode', 'date_default_timezone_set');
    foreach ($required_functions as $function) {
        if (!function_exists($function)) {
            $errors[] = "تابع $function در سرور شما فعال نیست.";
        }
    }
    
    return $errors;
}

// ==================== بارگذاری فایل‌های ضروری ====================

// توابع کمکی ابتدا باید بارگذاری شوند
require_once WF_PLUGIN_DIR . 'helpers.php';

// بررسی نیازمندی‌ها هنگام فعال‌سازی
register_activation_hook(__FILE__, 'wf_activate_plugin');

/**
 * فعال‌سازی پلاگین
 */
function wf_activate_plugin() {
    $errors = wf_check_requirements();
    
    if (!empty($errors)) {
        deactivate_plugins(WF_PLUGIN_BASENAME);
        wp_die(
            '<h1>خطا در فعال‌سازی پلاگین</h1>' .
            '<p>' . implode('<br>', $errors) . '</p>' .
            '<a href="' . admin_url('plugins.php') . '">بازگشت به صفحه افزونه‌ها</a>'
        );
    }
    
    // بارگذاری هندلر دیتابیس و ایجاد جداول
    require_once WF_PLUGIN_DIR . 'database-handler.php';
    wf_create_database_tables();
    
    // ایجاد نقش‌های کاربری
    wf_create_user_roles();
    
    // تنظیمات پیش‌فرض
    wf_set_default_settings();
    
    // ریدایرکت به صفحه تنظیمات پلاگین
    add_option('wf_plugin_activated', true);
}

/**
 * غیرفعال‌سازی پلاگین
 */
register_deactivation_hook(__FILE__, function() {
    // حذف cron jobs
    wp_clear_scheduled_hook('wf_daily_backup');
    wp_clear_scheduled_hook('wf_weekly_report');
    
    // حذف option ریدایرکت
    delete_option('wf_plugin_activated');
});

/**
 * حذف پلاگین
 */
register_uninstall_hook(__FILE__, 'wf_uninstall_plugin');

function wf_uninstall_plugin() {
    global $wpdb;
    
    // حذف جداول دیتابیس
    $tables = array(
        'wf_fields',
        'wf_departments', 
        'wf_personnel',
        'wf_periods',
        'wf_approvals',
        'wf_settings',
        'wf_logs',
        'wf_templates'
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
    }
    
    // حذف options
    $options = array(
        'wf_plugin_settings',
        'wf_version',
        'wf_installed_time',
        'wf_backup_schedule'
    );
    
    foreach ($options as $option) {
        delete_option($option);
    }
    
    // حذف role capabilities
    wf_remove_user_roles();
}

// ==================== ایجاد نقش‌های کاربری ====================

function wf_create_user_roles() {
    // نقش مدیر سازمان
    add_role('wf_org_manager', 'مدیر سازمان', array(
        'read' => true,
        'wf_view_all_departments' => true,
        'wf_export_reports' => true,
        'wf_view_statistics' => true,
        'wf_manage_department_admins' => false,
        'wf_edit_locked_fields' => false,
        'wf_approve_personnel' => false
    ));
    
    // نقش مدیر اداره
    add_role('wf_department_manager', 'مدیر اداره', array(
        'read' => true,
        'wf_view_own_department' => true,
        'wf_edit_personnel' => true,
        'wf_add_personnel' => true,
        'wf_export_department' => true,
        'wf_view_department_stats' => true,
        'wf_request_changes' => true,
        'wf_view_all_departments' => false,
        'wf_edit_locked_fields' => false
    ));
}

function wf_remove_user_roles() {
    remove_role('wf_org_manager');
    remove_role('wf_department_manager');
}

// ==================== تنظیمات پیش‌فرض ====================

function wf_set_default_settings() {
    $default_settings = array(
        'company_name' => 'سازمان شما',
        'default_period_days' => 30,
        'max_dynamic_cards' => 6,
        'records_per_page' => array(25, 50, 100),
        'excel_export_format' => 'xlsx',
        'backup_enabled' => true,
        'backup_frequency' => 'weekly',
        'default_date_format' => 'j F Y',
        'timezone' => 'Asia/Tehran',
        'required_field_color' => '#fff8e1',
        'locked_field_color' => '#f5f5f5',
        'editable_field_color' => '#ffffff',
        'deleted_row_opacity' => 0.5,
        'table_border_radius' => 8,
        'primary_color' => '#1a73e8',
        'secondary_color' => '#5f6368',
        'success_color' => '#34a853',
        'warning_color' => '#f9ab00',
        'danger_color' => '#ea4335'
    );
    
    update_option('wf_plugin_settings', $default_settings);
    
    // ایجاد فیلدهای پیش‌فرض
    $default_fields = array(
        array(
            'field_name' => 'کد ملی',
            'field_key' => 'national_code',
            'field_type' => 'text',
            'is_required' => 1,
            'is_locked' => 1,
            'is_monitoring' => 1,
            'is_key' => 1,
            'display_order' => 1,
            'created_at' => current_time('mysql')
        ),
        array(
            'field_name' => 'نام',
            'field_key' => 'first_name',
            'field_type' => 'text',
            'is_required' => 1,
            'is_locked' => 0,
            'is_monitoring' => 0,
            'is_key' => 0,
            'display_order' => 2,
            'created_at' => current_time('mysql')
        ),
        array(
            'field_name' => 'نام خانوادگی',
            'field_key' => 'last_name',
            'field_type' => 'text',
            'is_required' => 1,
            'is_locked' => 0,
            'is_monitoring' => 0,
            'is_key' => 0,
            'display_order' => 3,
            'created_at' => current_time('mysql')
        ),
        array(
            'field_name' => 'تاریخ استخدام',
            'field_key' => 'employment_date',
            'field_type' => 'date',
            'is_required' => 1,
            'is_locked' => 1,
            'is_monitoring' => 1,
            'is_key' => 0,
            'display_order' => 4,
            'created_at' => current_time('mysql')
        )
    );
    
    global $wpdb;
    foreach ($default_fields as $field) {
        $wpdb->insert($wpdb->prefix . 'wf_fields', $field);
    }
}

// ==================== بارگذاری فایل‌های پلاگین ====================

// بارگذاری فایل‌های پلاگین
add_action('plugins_loaded', 'wf_load_plugin_files');

function wf_load_plugin_files() {
    // فایل‌های اصلی
    $files = array(
        'database-handler.php',
        'helpers.php',
        'admin-panel.php',
        'manager-panel.php',
        'excel-export.php'
    );
    
    foreach ($files as $file) {
        $file_path = WF_PLUGIN_DIR . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        } else {
            error_log("فایل پلاگین یافت نشد: " . $file_path);
        }
    }
    
    // بارگذاری فایل‌های زبان
    load_plugin_textdomain('workforce-beni-asad', false, dirname(WF_PLUGIN_BASENAME) . '/languages');
}

// ==================== ثبت شرط کدها ====================

// شرط کد پنل مدیران اداره
add_shortcode('workforce_manager_panel', 'wf_manager_panel_shortcode');

function wf_manager_panel_shortcode($atts) {
    // بررسی لاگین بودن کاربر
    if (!is_user_logged_in()) {
        return wf_render_login_form();
    }
    
    // بررسی دسترسی کاربر
    $user = wp_get_current_user();
    $user_roles = $user->roles;
    
    // بررسی آیا کاربر مدیر اداره است یا مدیر سازمان
    if (in_array('wf_department_manager', $user_roles) || 
        in_array('wf_org_manager', $user_roles) || 
        in_array('administrator', $user_roles)) {
        return wf_render_manager_panel('department');
    }
    
    // اگر دسترسی ندارد
    return '<div class="wf-access-denied">
                <h3>⛔ دسترسی محدود</h3>
                <p>شما مجوز دسترسی به این پنل را ندارید.</p>
                <p>لطفاً با مدیر سیستم تماس بگیرید.</p>
            </div>';
}

// شرط کد پنل مدیر سازمان
add_shortcode('workforce_org_manager_panel', 'wf_org_manager_panel_shortcode');

function wf_org_manager_panel_shortcode($atts) {
    // بررسی لاگین بودن کاربر
    if (!is_user_logged_in()) {
        return wf_render_login_form();
    }
    
    // بررسی دسترسی کاربر
    $user = wp_get_current_user();
    $user_roles = $user->roles;
    
    // بررسی آیا کاربر مدیر سازمان است یا ادمین
    if (in_array('wf_org_manager', $user_roles) || in_array('administrator', $user_roles)) {
        return wf_render_manager_panel('organization');
    }
    
    // اگر دسترسی ندارد
    return '<div class="wf-access-denied">
                <h3>⛔ دسترسی محدود</h3>
                <p>فقط مدیران سازمان می‌توانند به این پنل دسترسی داشته باشند.</p>
            </div>';
}

// ==================== بارگذاری استایل و اسکریپت‌ها ====================

// بارگذاری برای فرانت‌اند
add_action('wp_enqueue_scripts', 'wf_enqueue_frontend_assets');

function wf_enqueue_frontend_assets() {
    // فقط در صفحاتی که شرط کد داریم بارگذاری شود
    global $post;
    if (is_a($post, 'WP_Post') && (
        has_shortcode($post->post_content, 'workforce_manager_panel') || 
        has_shortcode($post->post_content, 'workforce_org_manager_panel')
    )) {
        // استایل‌ها
        wp_enqueue_style(
            'workforce-main-style',
            WF_PLUGIN_URL . 'assets/style.css',
            array(),
            WF_VERSION,
            'all'
        );
        
        // اسکریپت‌ها
        wp_enqueue_script(
            'workforce-main-script',
            WF_PLUGIN_URL . 'assets/script.js',
            array('jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker'),
            WF_VERSION,
            true
        );
        
        // محلی‌سازی اسکریپت
        wp_localize_script('workforce-main-script', 'wf_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('workforce_nonce'),
            'plugin_url' => WF_PLUGIN_URL,
            'current_user' => get_current_user_id(),
            'strings' => array(
                'loading' => 'در حال بارگذاری...',
                'saving' => 'در حال ذخیره...',
                'saved' => 'ذخیره شد',
                'error' => 'خطا رخ داد',
                'confirm_delete' => 'آیا از حذف اطمینان دارید؟',
                'no_results' => 'نتیجه‌ای یافت نشد',
                'select_all' => 'انتخاب همه',
                'deselect_all' => 'عدم انتخاب'
            )
        ));
        
        // تاریخ شمسی
        if (function_exists('wp_enqueue_jquery_ui_datepicker_fa')) {
            wp_enqueue_jquery_ui_datepicker_fa();
        }
        
        // آیکون‌ها
        wp_enqueue_style(
            'workforce-icons',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            array(),
            '6.4.0'
        );
    }
}

// بارگذاری برای ادمین
add_action('admin_enqueue_scripts', 'wf_enqueue_admin_assets');

function wf_enqueue_admin_assets($hook) {
    // فقط در صفحات پلاگین ما
    if (strpos($hook, 'workforce') !== false) {
        // استایل ادمین
        wp_enqueue_style(
            'workforce-admin-style',
            WF_PLUGIN_URL . 'assets/admin-style.css',
            array('wp-color-picker'),
            WF_VERSION
        );
        
        // اسکریپت ادمین
        wp_enqueue_script(
            'workforce-admin-script',
            WF_PLUGIN_URL . 'assets/admin-script.js',
            array('jquery', 'wp-color-picker', 'jquery-ui-sortable', 'jquery-ui-dialog'),
            WF_VERSION,
            true
        );
        
        // محلی‌سازی
        wp_localize_script('workforce-admin-script', 'wf_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('workforce_admin_nonce'),
            'confirm_delete' => 'آیا از حذف این آیتم اطمینان دارید؟ این عمل غیرقابل بازگشت است.',
            'select_file' => 'انتخاب فایل',
            'upload' => 'آپلود',
            'saving' => 'در حال ذخیره...'
        ));
    }
}

// ==================== سیستم AJAX ====================

// رجیستر کردن AJAX action ها
add_action('wp_ajax_wf_save_personnel', 'wf_ajax_save_personnel');
add_action('wp_ajax_wf_delete_personnel', 'wf_ajax_delete_personnel');
add_action('wp_ajax_wf_filter_data', 'wf_ajax_filter_data');
add_action('wp_ajax_wf_get_chart_data', 'wf_ajax_get_chart_data');
add_action('wp_ajax_wf_export_excel', 'wf_ajax_export_excel');
add_action('wp_ajax_wf_save_template', 'wf_ajax_save_template');
add_action('wp_ajax_wf_load_more', 'wf_ajax_load_more');

// AJAX برای کاربران غیرلاگین
add_action('wp_ajax_nopriv_wf_login', 'wf_ajax_login');

// ==================== فیلترها و اکشن‌ها ====================

// اضافه کردن لینک تنظیمات در صفحه پلاگین‌ها
add_filter('plugin_action_links_' . WF_PLUGIN_BASENAME, 'wf_plugin_action_links');

function wf_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=workforce-settings') . '">تنظیمات</a>';
    $docs_link = '<a href="https://docs.beniasad.ir/workforce" target="_blank">مستندات</a>';
    array_unshift($links, $settings_link, $docs_link);
    return $links;
}

// اضافه کردن منو به پیشخوان
add_action('admin_menu', 'wf_admin_menu');

function wf_admin_menu() {
    // منوی اصلی
    add_menu_page(
        'مدیریت کارکرد پرسنل',
        'کارکرد پرسنل',
        'manage_options',
        'workforce-dashboard',
        'wf_admin_dashboard_page',
        'dashicons-groups',
        30
    );
    
    // زیرمنوها
    add_submenu_page(
        'workforce-dashboard',
        'داشبورد',
        'داشبورد',
        'manage_options',
        'workforce-dashboard',
        'wf_admin_dashboard_page'
    );
    
    add_submenu_page(
        'workforce-dashboard',
        'مدیریت فیلدها',
        'فیلدها',
        'manage_options',
        'workforce-fields',
        'wf_admin_fields_page'
    );
    
    add_submenu_page(
        'workforce-dashboard',
        'مدیریت ادارات',
        'ادارات',
        'manage_options',
        'workforce-departments',
        'wf_admin_departments_page'
    );
    
    add_submenu_page(
        'workforce-dashboard',
        'مدیریت پرسنل',
        'پرسنل',
        'manage_options',
        'workforce-personnel',
        'wf_admin_personnel_page'
    );
    
    add_submenu_page(
        'workforce-dashboard',
        'دوره‌های کارکرد',
        'دوره‌ها',
        'manage_options',
        'workforce-periods',
        'wf_admin_periods_page'
    );
    
    add_submenu_page(
        'workforce-dashboard',
        'تایید درخواست‌ها',
        'درخواست‌ها',
        'manage_options',
        'workforce-approvals',
        'wf_admin_approvals_page'
    );
    
    add_submenu_page(
        'workforce-dashboard',
        'قالب گزارش اکسل',
        'قالب اکسل',
        'manage_options',
        'workforce-excel-templates',
        'wf_admin_excel_templates_page'
    );
    
    add_submenu_page(
        'workforce-dashboard',
        'تنظیمات',
        'تنظیمات',
        'manage_options',
        'workforce-settings',
        'wf_admin_settings_page'
    );
    
    add_submenu_page(
        'workforce-dashboard',
        'لاگ سیستم',
        'لاگ‌ها',
        'manage_options',
        'workforce-logs',
        'wf_admin_logs_page'
    );
}

// ==================== cron jobs ====================

// پشتیبان‌گیری روزانه
add_action('wf_daily_backup', 'wf_create_daily_backup');

function wf_create_daily_backup() {
    global $wpdb;
    
    $backup_data = array(
        'timestamp' => current_time('mysql'),
        'personnel_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wf_personnel"),
        'departments_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wf_departments"),
        'pending_approvals' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wf_approvals WHERE status = 'pending'")
    );
    
    // ذخیره در option
    $backups = get_option('wf_backups', array());
    $backups[] = $backup_data;
    
    // نگه داشتن فقط 30 backup آخر
    if (count($backups) > 30) {
        array_shift($backups);
    }
    
    update_option('wf_backups', $backups);
}

// گزارش هفتگی
add_action('wf_weekly_report', 'wf_send_weekly_report');

function wf_send_weekly_report() {
    global $wpdb;
    
    $admin_email = get_option('admin_email');
    $report_data = array(
        'period' => date('Y-m-d', strtotime('-7 days')) . ' تا ' . date('Y-m-d'),
        'new_personnel' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wf_personnel WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
        'pending_approvals' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wf_approvals WHERE status = 'pending'"),
        'active_departments' => $wpdb->get_var("SELECT COUNT(DISTINCT department_id) FROM {$wpdb->prefix}wf_personnel")
    );
    
    $subject = 'گزارش هفتگی سیستم کارکرد پرسنل - ' . date('Y/m/d');
    $message = wf_generate_report_email($report_data);
    
    wp_mail($admin_email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
}

// زمان‌بندی cron jobs
add_action('init', 'wf_schedule_cron_jobs');

function wf_schedule_cron_jobs() {
    if (!wp_next_scheduled('wf_daily_backup')) {
        wp_schedule_event(time(), 'daily', 'wf_daily_backup');
    }
    
    if (!wp_next_scheduled('wf_weekly_report')) {
        wp_schedule_event(time(), 'weekly', 'wf_weekly_report');
    }
}

// ==================== توابع اصلی ====================

/**
 * رندر فرم لاگین
 */
function wf_render_login_form() {
    ob_start();
    ?>
    <div class="wf-login-container">
        <div class="wf-login-box">
            <div class="wf-login-header">
                <h2><i class="fas fa-user-shield"></i> ورود به پنل مدیریت</h2>
                <p>لطفاً برای ورود به سیستم احراز هویت کنید</p>
            </div>
            
            <form id="wf-login-form" method="post">
                <?php wp_nonce_field('wf_login_action', 'wf_login_nonce'); ?>
                
                <div class="wf-form-group">
                    <label for="wf-username">
                        <i class="fas fa-user"></i> نام کاربری
                    </label>
                    <input type="text" 
                           id="wf-username" 
                           name="username" 
                           required 
                           placeholder="نام کاربری وردپرس خود را وارد کنید">
                </div>
                
                <div class="wf-form-group">
                    <label for="wf-password">
                        <i class="fas fa-lock"></i> رمز عبور
                    </label>
                    <input type="password" 
                           id="wf-password" 
                           name="password" 
                           required 
                           placeholder="رمز عبور خود را وارد کنید">
                </div>
                
                <div class="wf-form-group wf-remember">
                    <label>
                        <input type="checkbox" name="remember" value="1">
                        مرا به خاطر بسپار
                    </label>
                </div>
                
                <div class="wf-form-group">
                    <button type="submit" class="wf-login-btn">
                        <i class="fas fa-sign-in-alt"></i> ورود به سیستم
                    </button>
                </div>
                
                <div class="wf-login-footer">
                    <p>مشکل در ورود دارید؟ با مدیر سیستم تماس بگیرید.</p>
                </div>
            </form>
            
            <div id="wf-login-message" class="wf-message"></div>
        </div>
        
        <div class="wf-login-info">
            <h3><i class="fas fa-info-circle"></i> راهنمای ورود</h3>
            <ul>
                <li>از نام کاربری و رمز عبور وردپرس خود استفاده کنید</li>
                <li>فقط کاربرانی که مجوز مدیر اداره یا سازمان دارند می‌توانند وارد شوند</li>
                <li>در صورت فراموشی رمز عبور، از بخش کاربران در پیشخوان وردپرس اقدام کنید</li>
                <li>سیستم در زمان عدم فعالیت پس از 60 دقیقه به طور خودکار خارج می‌شود</li>
            </ul>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#wf-login-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            
            $.ajax({
                url: wf_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wf_login',
                    data: formData,
                    nonce: wf_ajax.nonce
                },
                beforeSend: function() {
                    $('.wf-login-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> در حال بررسی...');
                },
                success: function(response) {
                    if (response.success) {
                        $('#wf-login-message').html('<div class="wf-success">' + response.data.message + '</div>');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        $('#wf-login-message').html('<div class="wf-error">' + response.data.message + '</div>');
                        $('.wf-login-btn').prop('disabled', false).html('<i class="fas fa-sign-in-alt"></i> ورود به سیستم');
                    }
                },
                error: function() {
                    $('#wf-login-message').html('<div class="wf-error">خطا در ارتباط با سرور</div>');
                    $('.wf-login-btn').prop('disabled', false).html('<i class="fas fa-sign-in-alt"></i> ورود به سیستم');
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

/**
 * AJAX login
 */
function wf_ajax_login() {
    check_ajax_referer('workforce_nonce', 'nonce');
    
    parse_str($_POST['data'], $data);
    
    $username = sanitize_text_field($data['username']);
    $password = $data['password'];
    $remember = isset($data['remember']) ? true : false;
    
    $credentials = array(
        'user_login' => $username,
        'user_password' => $password,
        'remember' => $remember
    );
    
    $user = wp_signon($credentials, false);
    
    if (is_wp_error($user)) {
        wp_send_json_error(array(
            'message' => 'نام کاربری یا رمز عبور نادرست است'
        ));
    } else {
        wp_send_json_success(array(
            'message' => 'ورود موفقیت‌آمیز بود. در حال انتقال...'
        ));
    }
}

/**
 * رندر پنل مدیریت
 */
function wf_render_manager_panel($type = 'department') {
    // بررسی مجدد دسترسی
    $user = wp_get_current_user();
    
    if ($type === 'organization' && !in_array('wf_org_manager', $user->roles) && !in_array('administrator', $user->roles)) {
        return '<div class="wf-error">دسترسی غیرمجاز</div>';
    }
    
    if ($type === 'department' && !in_array('wf_department_manager', $user->roles) && 
        !in_array('wf_org_manager', $user->roles) && !in_array('administrator', $user->roles)) {
        return '<div class="wf-error">دسترسی غیرمجاز</div>';
    }
    
    // دریافت اطلاعات اولیه
    $user_data = wf_get_user_manager_data($user->ID);
    $current_period = wf_get_current_period();
    $fields = wf_get_all_fields();
    
    ob_start();
    ?>
    
    <!-- Container اصلی -->
    <div class="wf-manager-panel" data-panel-type="<?php echo esc_attr($type); ?>" data-user-id="<?php echo esc_attr($user->ID); ?>">
        
        <!-- Header -->
        <header class="wf-panel-header">
            <div class="wf-header-left">
                <div class="wf-welcome">
                    <h1><i class="fas fa-user-tie"></i> خوش آمدید، <?php echo esc_html($user->display_name); ?></h1>
                    <p class="wf-org-info">
                        <i class="fas fa-building"></i> 
                        <?php 
                        if ($type === 'organization') {
                            echo 'مدیریت کل سازمان';
                        } else {
                            echo 'مدیریت اداره: ' . esc_html($user_data['department_name'] ?? 'نامشخص');
                        }
                        ?>
                    </p>
                </div>
                
                <div class="wf-period-info">
                    <span class="wf-period-badge">
                        <i class="fas fa-calendar-alt"></i>
                        دوره فعال: <?php echo esc_html($current_period['title'] ?? 'تعیین نشده'); ?>
                    </span>
                    <span class="wf-date-info">
                        <i class="fas fa-clock"></i>
                        <?php echo wf_get_jalali_date(date('Y-m-d')); ?>
                    </span>
                </div>
            </div>
            
            <div class="wf-header-right">
                <div class="wf-user-actions">
                    <button class="wf-btn wf-btn-secondary wf-help-btn">
                        <i class="fas fa-question-circle"></i> راهنما
                    </button>
                    <button class="wf-btn wf-btn-primary wf-refresh-btn">
                        <i class="fas fa-sync-alt"></i> به‌روزرسانی
                    </button>
                    <button class="wf-btn wf-btn-logout" onclick="window.location.href='<?php echo wp_logout_url(get_permalink()); ?>'">
                        <i class="fas fa-sign-out-alt"></i> خروج
                    </button>
                </div>
            </div>
        </header>
        
        <!-- Monitoring Cards -->
        <section class="wf-monitoring-section">
            <div class="wf-cards-grid">
                <!-- کارت ثابت: وضعیت پرسنل -->
                <div class="wf-card wf-card-personnel">
                    <div class="wf-card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="wf-card-content">
                        <h3>وضعیت پرسنل</h3>
                        <div class="wf-card-value" id="wf-personnel-count">0</div>
                        <div class="wf-card-trend">
                            <span class="wf-trend-up"><i class="fas fa-arrow-up"></i> 12%</span>
                            نسبت به ماه گذشته
                        </div>
                    </div>
                </div>
                
                <!-- کارت ثابت: فیلدهای ضروری -->
                <div class="wf-card wf-card-required">
                    <div class="wf-card-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="wf-card-content">
                        <h3>فیلدهای ضروری</h3>
                        <div class="wf-card-value">
                            <div class="wf-progress-bar">
                                <div class="wf-progress-fill" id="wf-required-progress" style="width: 0%"></div>
                            </div>
                            <span id="wf-required-percent">0%</span>
                        </div>
                        <div class="wf-card-subtext">
                            <span id="wf-required-count">0 از 0</span> تکمیل شده
                        </div>
                    </div>
                </div>
                
                <!-- کارت ثابت: هشدارها -->
                <div class="wf-card wf-card-warning">
                    <div class="wf-card-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="wf-card-content">
                        <h3>هشدار</h3>
                        <div class="wf-card-value" id="wf-warning-count">0</div>
                        <div class="wf-card-subtext">
                            پرسنل با اطلاعات ناقص
                        </div>
                    </div>
                </div>
                
                <!-- کارت‌های داینامیک -->
                <div id="wf-dynamic-cards"></div>
                
                <!-- دکمه اضافه کردن کارت -->
                <div class="wf-card wf-card-add">
                    <div class="wf-card-content">
                        <button class="wf-add-card-btn" id="wf-add-monitoring-card">
                            <i class="fas fa-plus-circle"></i>
                            <span>افزودن کارت مانیتورینگ</span>
                        </button>
                        <p class="wf-card-hint">
                            روی آیکون 📊 کنار هر ستون کلیک کنید
                        </p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Actions Toolbar -->
        <section class="wf-actions-section">
            <div class="wf-actions-toolbar">
                <div class="wf-actions-left">
                    <button class="wf-action-btn wf-add-btn" id="wf-add-personnel">
                        <i class="fas fa-user-plus"></i> افزودن پرسنل جدید
                    </button>
                    <button class="wf-action-btn wf-edit-btn" id="wf-edit-selected">
                        <i class="fas fa-edit"></i> ویرایش انتخاب شده
                    </button>
                    <button class="wf-action-btn wf-delete-btn" id="wf-delete-selected">
                        <i class="fas fa-trash-alt"></i> حذف انتخاب شده
                    </button>
                    <button class="wf-action-btn wf-export-btn" id="wf-export-excel">
                        <i class="fas fa-file-excel"></i> خروجی Excel
                    </button>
                </div>
                
                <div class="wf-actions-right">
                    <div class="wf-search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" 
                               id="wf-global-search" 
                               placeholder="جستجوی سریع در همه فیلدها...">
                    </div>
                    
                    <div class="wf-records-per-page">
                        <label>نمایش:</label>
                        <select id="wf-records-per-page">
                            <option value="25">25 رکورد</option>
                            <option value="50">50 رکورد</option>
                            <option value="100" selected>100 رکورد</option>
                            <option value="all">همه رکوردها</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- فیلترهای فعال -->
            <div class="wf-active-filters" id="wf-active-filters">
                <!-- فیلترهای فعال اینجا نمایش داده می‌شوند -->
            </div>
        </section>
        
        <!-- جدول اصلی -->
        <section class="wf-table-section">
            <div class="wf-table-container">
                <table class="wf-data-table" id="wf-main-table">
                    <thead>
                        <tr>
                            <th class="wf-checkbox-col">
                                <input type="checkbox" id="wf-select-all">
                            </th>
                            <th class="wf-row-number">#</th>
                            <?php foreach ($fields as $field): ?>
                            <th class="wf-column 
                                <?php echo $field['is_required'] ? 'wf-required' : ''; ?>
                                <?php echo $field['is_locked'] ? 'wf-locked' : 'wf-editable'; ?>"
                                data-field-id="<?php echo esc_attr($field['id']); ?>"
                                data-field-type="<?php echo esc_attr($field['field_type']); ?>"
                                data-field-key="<?php echo esc_attr($field['field_key']); ?>">
                                
                                <div class="wf-column-header">
                                    <span class="wf-column-title">
                                        <?php echo esc_html($field['field_name']); ?>
                                        <?php if ($field['is_required']): ?>
                                            <span class="wf-required-mark">*</span>
                                        <?php endif; ?>
                                    </span>
                                    
                                    <div class="wf-column-actions">
                                        <button class="wf-filter-btn" data-field="<?php echo esc_attr($field['id']); ?>">
                                            <i class="fas fa-filter"></i>
                                        </button>
                                        <button class="wf-monitor-btn" data-field="<?php echo esc_attr($field['id']); ?>">
                                            <i class="fas fa-chart-bar"></i>
                                        </button>
                                        <button class="wf-pin-btn" data-field="<?php echo esc_attr($field['id']); ?>">
                                            <i class="fas fa-thumbtack"></i>
                                        </button>
                                    </div>
                                </div>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody id="wf-table-body">
                        <!-- داده‌ها به صورت AJAX لود می‌شوند -->
                        <tr class="wf-loading-row">
                            <td colspan="<?php echo count($fields) + 2; ?>">
                                <div class="wf-loading">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    در حال بارگذاری داده‌ها...
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="wf-pagination">
                <div class="wf-pagination-info">
                    نمایش <span id="wf-current-range">0-0</span> از <span id="wf-total-records">0</span> رکورد
                </div>
                
                <div class="wf-pagination-controls">
                    <button class="wf-pagination-btn wf-first-page" disabled>
                        <i class="fas fa-angle-double-left"></i>
                    </button>
                    <button class="wf-pagination-btn wf-prev-page" disabled>
                        <i class="fas fa-angle-left"></i>
                    </button>
                    
                    <div class="wf-page-numbers" id="wf-page-numbers">
                        <button class="wf-page-btn active">1</button>
                    </div>
                    
                    <button class="wf-pagination-btn wf-next-page">
                        <i class="fas fa-angle-right"></i>
                    </button>
                    <button class="wf-pagination-btn wf-last-page">
                        <i class="fas fa-angle-double-right"></i>
                    </button>
                </div>
            </div>
        </section>
        
        <!-- فرم ویرایش سمت راست -->
        <aside class="wf-edit-sidebar" id="wf-edit-sidebar">
            <div class="wf-sidebar-header">
                <h3>
                    <i class="fas fa-edit"></i>
                    <span id="wf-edit-title">ویرایش پرسنل</span>
                </h3>
                <button class="wf-close-sidebar" id="wf-close-sidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="wf-sidebar-content">
                <form id="wf-edit-form">
                    <input type="hidden" id="wf-edit-id" name="id">
                    <input type="hidden" id="wf-edit-type" name="type">
                    
                    <div class="wf-form-container" id="wf-form-fields">
                        <!-- فیلدهای فرم اینجا لود می‌شوند -->
                    </div>
                    
                    <div class="wf-form-actions">
                        <button type="button" class="wf-btn wf-btn-secondary wf-prev-personnel">
                            <i class="fas fa-arrow-right"></i> قبلی
                        </button>
                        
                        <div class="wf-main-actions">
                            <button type="submit" class="wf-btn wf-btn-primary wf-save-btn">
                                <i class="fas fa-save"></i> ذخیره تغییرات
                            </button>
                            <button type="button" class="wf-btn wf-btn-danger wf-cancel-btn">
                                انصراف
                            </button>
                        </div>
                        
                        <button type="button" class="wf-btn wf-btn-secondary wf-next-personnel">
                            بعدی <i class="fas fa-arrow-left"></i>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="wf-sidebar-footer">
                <div class="wf-edit-info">
                    <p><i class="fas fa-info-circle"></i> فیلدهای قرمز رنگ ضروری هستند</p>
                    <p><i class="fas fa-lock"></i> فیلدهای قفل شده توسط مدیران قابل ویرایش نیستند</p>
                </div>
            </div>
        </aside>
        
        <!-- Modal فیلتر -->
        <div class="wf-modal" id="wf-filter-modal">
            <div class="wf-modal-content">
                <div class="wf-modal-header">
                    <h3><i class="fas fa-filter"></i> فیلتر پیشرفته</h3>
                    <button class="wf-modal-close">&times;</button>
                </div>
                <div class="wf-modal-body" id="wf-filter-content">
                    <!-- محتوای فیلتر -->
                </div>
                <div class="wf-modal-footer">
                    <button class="wf-btn wf-btn-secondary" id="wf-clear-filters">
                        پاک کردن همه فیلترها
                    </button>
                    <button class="wf-btn wf-btn-primary" id="wf-apply-filters">
                        اعمال فیلتر
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Modal افزودن کارت مانیتورینگ -->
        <div class="wf-modal" id="wf-add-card-modal">
            <div class="wf-modal-content">
                <div class="wf-modal-header">
                    <h3><i class="fas fa-chart-bar"></i> افزودن کارت مانیتورینگ</h3>
                    <button class="wf-modal-close">&times;</button>
                </div>
                <div class="wf-modal-body">
                    <div class="wf-card-selection">
                        <h4>انتخاب فیلد برای مانیتورینگ</h4>
                        <div class="wf-fields-list" id="wf-card-fields-list">
                            <!-- لیست فیلدها -->
                        </div>
                    </div>
                    <div class="wf-card-settings">
                        <h4>تنظیمات کارت</h4>
                        <div class="wf-form-group">
                            <label>نوع کارت:</label>
                            <select id="wf-card-type">
                                <option value="sum">جمع</option>
                                <option value="avg">میانگین</option>
                                <option value="count">تعداد</option>
                                <option value="min">کمینه</option>
                                <option value="max">بیشینه</option>
                            </select>
                        </div>
                        <div class="wf-form-group">
                            <label>رنگ کارت:</label>
                            <input type="color" id="wf-card-color" value="#1a73e8">
                        </div>
                        <div class="wf-form-group">
                            <label>آیکون:</label>
                            <select id="wf-card-icon">
                                <option value="fas fa-chart-line">نمودار</option>
                                <option value="fas fa-calculator">ماشین حساب</option>
                                <option value="fas fa-database">دیتابیس</option>
                                <option value="fas fa-money-bill">پول</option>
                                <option value="fas fa-calendar">تقویم</option>
                                <option value="fas fa-users">کاربران</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="wf-modal-footer">
                    <button class="wf-btn wf-btn-secondary wf-cancel-card">
                        انصراف
                    </button>
                    <button class="wf-btn wf-btn-primary" id="wf-create-card">
                        ایجاد کارت
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Notification Area -->
        <div class="wf-notification-area" id="wf-notifications">
            <!-- اعلان‌ها اینجا نمایش داده می‌شوند -->
        </div>
        
    </div>
    
    <?php
    return ob_get_clean();
}

// ==================== توابع کمکی ====================

/**
 * دریافت اطلاعات مدیر
 */
function wf_get_user_manager_data($user_id) {
    global $wpdb;
    
    $data = array(
        'department_id' => null,
        'department_name' => null,
        'is_org_manager' => false,
        'managed_departments' => array()
    );
    
    // بررسی مدیر سازمان
    $user = get_user_by('id', $user_id);
    if (in_array('wf_org_manager', $user->roles) || in_array('administrator', $user->roles)) {
        $data['is_org_manager'] = true;
        
        // دریافت همه ادارات
        $departments = $wpdb->get_results("
            SELECT id, name 
            FROM {$wpdb->prefix}wf_departments 
            WHERE status = 'active'
        ");
        
        foreach ($departments as $dept) {
            $data['managed_departments'][] = array(
                'id' => $dept->id,
                'name' => $dept->name
            );
        }
    } else {
        // دریافت اداره مدیر
        $department = $wpdb->get_row($wpdb->prepare("
            SELECT d.id, d.name 
            FROM {$wpdb->prefix}wf_departments d
            WHERE d.manager_id = %d AND d.status = 'active'
        ", $user_id));
        
        if ($department) {
            $data['department_id'] = $department->id;
            $data['department_name'] = $department->name;
        }
    }
    
    return $data;
}

/**
 * دریافت دوره فعال
 */
function wf_get_current_period() {
    global $wpdb;
    
    $period = $wpdb->get_row("
        SELECT * 
        FROM {$wpdb->prefix}wf_periods 
        WHERE status = 'active' 
        ORDER BY start_date DESC 
        LIMIT 1
    ");
    
    if ($period) {
        return (array) $period;
    }
    
    // ایجاد دوره پیش‌فرض اگر وجود نداشت
    return array(
        'id' => 0,
        'title' => 'دوره پیش‌فرض',
        'start_date' => date('Y-m-01'),
        'end_date' => date('Y-m-t'),
        'status' => 'active'
    );
}

/**
 * دریافت همه فیلدها
 */
function wf_get_all_fields() {
    global $wpdb;
    
    $fields = $wpdb->get_results("
        SELECT * 
        FROM {$wpdb->prefix}wf_fields 
        WHERE status = 'active' 
        ORDER BY display_order ASC
    ", ARRAY_A);
    
    return $fields ?: array();
}

/**
 * تولید ایمیل گزارش
 */
function wf_generate_report_email($data) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html dir="rtl">
    <head>
        <meta charset="UTF-8">
        <title>گزارش هفتگی</title>
        <style>
            body { font-family: Tahoma, sans-serif; direction: rtl; }
            .container { max-width: 600px; margin: 0 auto; }
            .header { background: #1a73e8; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f5f5f5; }
            .stats { background: white; border-radius: 8px; padding: 20px; margin: 20px 0; }
            .stat-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>گزارش هفتگی سیستم کارکرد پرسنل</h1>
                <p>دوره: <?php echo esc_html($data['period']); ?></p>
            </div>
            
            <div class="content">
                <div class="stats">
                    <h3>آمار عملکرد</h3>
                    
                    <div class="stat-item">
                        <span>پرسنل جدید اضافه شده:</span>
                        <strong><?php echo esc_html($data['new_personnel']); ?> نفر</strong>
                    </div>
                    
                    <div class="stat-item">
                        <span>درخواست‌های در انتظار تایید:</span>
                        <strong><?php echo esc_html($data['pending_approvals']); ?> مورد</strong>
                    </div>
                    
                    <div class="stat-item">
                        <span>ادارات فعال:</span>
                        <strong><?php echo esc_html($data['active_departments']); ?> اداره</strong>
                    </div>
                </div>
                
                <p>این گزارش به طور خودکار توسط سیستم مدیریت کارکرد پرسنل تولید شده است.</p>
            </div>
            
            <div class="footer">
                <p>© <?php echo date('Y'); ?> سیستم مدیریت کارکرد پرسنل - بنی اسد</p>
                <p>این ایمیل به صورت خودکار ارسال شده است. لطفاً به آن پاسخ ندهید.</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

// ==================== cleanup ====================

/**
 * پاکسازی داده‌های قدیمی
 */
function wf_cleanup_old_data() {
    global $wpdb;
    
    // حذف لاگ‌های قدیمی‌تر از 90 روز
    $ninety_days_ago = date('Y-m-d H:i:s', strtotime('-90 days'));
    $wpdb->query($wpdb->prepare("
        DELETE FROM {$wpdb->prefix}wf_logs 
        WHERE created_at < %s
    ", $ninety_days_ago));
    
    // حذف backup های قدیمی‌تر از 1 سال
    $one_year_ago = date('Y-m-d H:i:s', strtotime('-1 year'));
    $wpdb->query($wpdb->prepare("
        DELETE FROM {$wpdb->prefix}wf_backups 
        WHERE created_at < %s
    ", $one_year_ago));
}

// ثبت cleanup در cron
add_action('wf_monthly_cleanup', 'wf_cleanup_old_data');

if (!wp_next_scheduled('wf_monthly_cleanup')) {
    wp_schedule_event(time(), 'monthly', 'wf_monthly_cleanup');
}

?>