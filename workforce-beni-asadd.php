<?php
/**
 * Plugin Name: پلاگین جامع مدیریت کارکرد پرسنل - بنی اسد
 * Plugin URI: https://your-site.com/
 * Description: سیستم مدیریت پرسنل سازمانی تمام‌فارسی با رابط کاربری شبه‌اکسل پویا
 * Version: 1.0.0
 * Author: بنی اسد
 * Author URI: https://your-site.com/
 * Text Domain: workforce-beni-asadd
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// تعریف ثابت‌های اصلی
define('WF_PLUGIN_VERSION', '1.0.0');
define('WF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WF_TABLE_PREFIX', 'wf_');

// تابع فعال‌سازی پلاگین
register_activation_hook(__FILE__, 'workforce_plugin_activation');
function workforce_plugin_activation() {
    require_once WF_PLUGIN_DIR . 'database-handler.php';
    workforce_create_tables();
    
    // ایجاد نقش‌های کاربری
    add_role('workforce_org_manager', 'مدیر سازمان', [
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
    ]);
    
    add_role('workforce_dept_manager', 'مدیر اداره', [
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
    ]);
    
    // تنظیمات پیش‌فرض
    $default_settings = [
        'company_name' => 'سازمان شما',
        'primary_color' => '#2c3e50',
        'secondary_color' => '#3498db',
        'excel_template' => [],
    ];
    
    add_option('workforce_settings', $default_settings);
}

// تابع غیرفعال‌سازی
register_deactivation_hook(__FILE__, 'workforce_plugin_deactivation');
function workforce_plugin_deactivation() {
    // پاک کردن داده‌ها هنگام غیرفعال‌سازی (اختیاری)
    // delete_option('workforce_settings');
}

// بارگذاری فایل‌های وابسته
function workforce_load_dependencies() {
    $files = [
        'helpers.php',
        'database-handler.php',
        'admin-panel.php',
        'manager-panel.php',
        'excel-export.php', // این فایل اصلاح شده
    ];
    
    foreach ($files as $file) {
        if (file_exists(WF_PLUGIN_DIR . $file)) {
            require_once WF_PLUGIN_DIR . $file;
        }
    }
}
add_action('plugins_loaded', 'workforce_load_dependencies');

// تعریف شرط‌کدها
function workforce_manager_panel_shortcode($atts) {
    if (!is_user_logged_in()) {
        return workforce_login_form();
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // بررسی سطح دسترسی
    // ادمین سایت و مدیر سازمان می‌توانند پنل مدیر سازمان را ببینند
    if (in_array('administrator', $current_user->roles) || 
        in_array('workforce_org_manager', $current_user->roles)) {
        return workforce_org_manager_panel($user_id);
    } elseif (in_array('workforce_dept_manager', $current_user->roles)) {
        return workforce_dept_manager_panel($user_id);
    } else {
        return '<div class="workforce-error">شما دسترسی لازم را ندارید. لطفا با مدیر سیستم تماس بگیرید.</div>';
    }
}
add_shortcode('workforce_manager_panel', 'workforce_manager_panel_shortcode');

function workforce_org_manager_panel_shortcode($atts) {
    if (!is_user_logged_in()) {
        return workforce_login_form();
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // فقط مدیر سازمان می‌تواند این پنل را ببیند
    // ادمین سایت هم می‌تواند ببیند
    if (in_array('administrator', $current_user->roles) || 
        in_array('workforce_org_manager', $current_user->roles)) {
        return workforce_org_manager_panel($user_id);
    } else {
        return '<div class="workforce-error">شما دسترسی لازم را ندارید. لطفا با مدیر سیستم تماس بگیرید.</div>';
    }
}
add_shortcode('workforce_org_manager_panel', 'workforce_org_manager_panel_shortcode');

function workforce_enqueue_assets() {
    // فقط در صفحات مربوط به پلاگین ما بارگذاری شود
    $current_page = isset($_GET['page']) ? $_GET['page'] : '';
    $is_workforce_page = strpos($current_page, 'workforce-') === 0;
    
    if (!is_admin() && !$is_workforce_page) {
        return;
    }
    
    // استایل اصلی پلاگین
    wp_enqueue_style(
        'workforce-main-style',
        WF_PLUGIN_URL . 'assets/style.css',
        [],
        WF_PLUGIN_VERSION
    );
    
    // استفاده از jQuery وردپرس
    wp_enqueue_script('jquery');
    
    // Persian Date Library (محلی) - با fallback
    $persian_date_path = WF_PLUGIN_DIR . 'assets/js/persian-datepicker/persian-date.js';
    if (file_exists($persian_date_path)) {
        // بررسی syntax فایل
        $content = file_get_contents($persian_date_path);
        if (strpos($content, 'unexpected token') === false) {
            wp_enqueue_script(
                'persian-date',
                WF_PLUGIN_URL . 'assets/js/persian-datepicker/persian-date.js',
                [],
                '1.1.0',
                true
            );
        }
    }
    
    // Persian Datepicker (محلی) - با fallback
    $datepicker_path = WF_PLUGIN_DIR . 'assets/js/persian-datepicker/persianDatepicker.min.js';
    if (file_exists($datepicker_path)) {
        wp_enqueue_script(
            'persian-datepicker',
            WF_PLUGIN_URL . 'assets/js/persian-datepicker/persianDatepicker.min.js',
            ['jquery'],
            '1.2.0',
            true
        );
    }
    
    // اسکریپت اصلی پلاگین (بعد از datepicker)
    wp_enqueue_script(
        'workforce-main-script',
        WF_PLUGIN_URL . 'assets/script.js',
        ['jquery'],
        WF_PLUGIN_VERSION,
        true
    );
    
    // انتقال داده‌ها به جاوااسکریپت
    wp_localize_script('workforce-main-script', 'workforce_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('workforce_nonce'),
        'current_user_id' => get_current_user_id(),
        'plugin_url' => WF_PLUGIN_URL
    ]);
}
add_action('wp_enqueue_scripts', 'workforce_enqueue_assets');
add_action('admin_enqueue_scripts', 'workforce_enqueue_assets');

// تعریف منوهای ادمین
function workforce_admin_menu() {
    add_menu_page(
        'مدیریت کارکرد پرسنل',
        'کارکرد پرسنل',
        'manage_options',
        'workforce-admin',
        'workforce_admin_dashboard',
        'dashicons-groups',
        30
    );
    // در تابع workforce_admin_menu() این را اضافه کنید:
add_submenu_page(
    'workforce-admin',
    'مدیران سازمان',
    'مدیران سازمان',
    'manage_options',
    'workforce-org-managers',
    'workforce_admin_org_managers'
);
    add_submenu_page(
        'workforce-admin',
        'مدیریت فیلدها',
        'فیلدها',
        'manage_options',
        'workforce-fields',
        'workforce_admin_fields'
    );
    
    add_submenu_page(
        'workforce-admin',
        'مدیریت ادارات',
        'ادارات',
        'manage_options',
        'workforce-departments',
        'workforce_admin_departments'
    );
    
    add_submenu_page(
        'workforce-admin',
        'مدیریت پرسنل',
        'پرسنل',
        'manage_options',
        'workforce-personnel',
        'workforce_admin_personnel'
    );
    
    add_submenu_page(
        'workforce-admin',
        'تنظیمات قالب اکسل',
        'قالب گزارش',
        'manage_options',
        'workforce-excel-template',
        'workforce_admin_excel_template'
    );
    
    add_submenu_page(
        'workforce-admin',
        'تایید درخواست‌ها',
        'درخواست‌ها',
        'manage_options',
        'workforce-approvals',
        'workforce_admin_approvals'
    );
    
    add_submenu_page(
        'workforce-admin',
        'دوره‌های کارکرد',
        'دوره‌ها',
        'manage_options',
        'workforce-periods',
        'workforce_admin_periods'
    );
    
    add_submenu_page(
        'workforce-admin',
        'تنظیمات',
        'تنظیمات',
        'manage_options',
        'workforce-settings',
        'workforce_admin_settings'
    );
}
add_action('admin_menu', 'workforce_admin_menu');

// افزودن دسترسی‌ها به مدیران
function workforce_add_capabilities() {
    $roles = ['workforce_org_manager', 'workforce_dept_manager'];
    
    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            $role->add_cap('read');
            $role->add_cap('upload_files');
        }
    }
}
add_action('init', 'workforce_add_capabilities');

// تابع فرم لاگین
function workforce_login_form() {
    ob_start(); ?>
    <div class="workforce-login-container">
        <div class="workforce-login-box">
            <h2>ورود به پنل مدیریت</h2>
            <?php
            $args = [
                'echo' => false,
                'redirect' => get_permalink(),
                'form_id' => 'workforce-login-form',
                'label_username' => 'نام کاربری',
                'label_password' => 'رمز عبور',
                'label_remember' => 'مرا به خاطر بسپار',
                'label_log_in' => 'ورود',
                'remember' => true,
            ];
            
            echo wp_login_form($args);
            
            if (isset($_GET['login']) && $_GET['login'] == 'failed') {
                echo '<p class="workforce-login-error">نام کاربری یا رمز عبور اشتباه است.</p>';
            }
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// هندلرهای AJAX
function workforce_ajax_handler() {
    check_ajax_referer('workforce_nonce', 'nonce');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_field':
            workforce_ajax_save_field();
            break;
        case 'delete_field':
            workforce_ajax_delete_field();
            break;
        case 'save_personnel':
            workforce_ajax_save_personnel();
            break;
        case 'delete_personnel':
            workforce_ajax_delete_personnel();
            break;
        case 'get_personnel_data':
            workforce_ajax_get_personnel_data();
            break;
        case 'save_excel_template':
            workforce_ajax_save_excel_template();
            break;
        case 'export_excel':
            workforce_ajax_export_excel();
            break;
        case 'create_monitoring_card':
            workforce_ajax_create_monitoring_card();
            break;
        case 'filter_data':
            workforce_ajax_filter_data();
            break;
    }
    
    wp_die();
}
add_action('wp_ajax_workforce_action', 'workforce_ajax_handler');
add_action('wp_ajax_nopriv_workforce_action', 'workforce_ajax_handler');

// افزودن لینک تنظیمات در صفحه پلاگین‌ها
function workforce_plugin_action_links($links) {
    $settings_link = '<a href="admin.php?page=workforce-settings">تنظیمات</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'workforce_plugin_action_links');
