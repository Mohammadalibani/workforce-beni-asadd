<?php
/**
 * Plugin Name: کارکرد پرسنل - بنی اسد
 * Plugin URI: https://your-domain.com/
 * Description: پیشرفته‌ترین سیستم مدیریت اطلاعات پرسنلی سازمانی با رابط کاربری افسانه‌ای
 * Version: 1.0.0
 * Author: بنی اسد
 * License: GPL v3
 * Text Domain: workforce-beni-asadd
 */

// امنیت کامل
defined('ABSPATH') or die('دسترسی غیرمجاز!');

// جلوگیری از دسترسی مستقیم
if (!defined('WPINC')) {
    die;
}

/**
 * کلاس اصلی پلاگین
 */
class WorkforceBeniAsad {

    private static $instance = null;
    public $version = '1.0.0';
    public $plugin_path;
    public $plugin_url;
    public $capability = 'manage_options';

    /**
     * Singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);
        
        $this->define_constants();
        $this->init_hooks();
    }

    /**
     * تعریف ثابت‌ها
     */
    private function define_constants() {
        define('WORKFORCE_VERSION', $this->version);
        define('WORKFORCE_PLUGIN_DIR', $this->plugin_path);
        define('WORKFORCE_PLUGIN_URL', $this->plugin_url);
        define('WORKFORCE_UPLOAD_DIR', wp_upload_dir()['basedir'] . '/workforce/');
        
        // ایجاد دایرکتوری آپلود
        if (!file_exists(WORKFORCE_UPLOAD_DIR)) {
            wp_mkdir_p(WORKFORCE_UPLOAD_DIR);
        }
    }

    /**
     * تنظیم هوک‌ها
     */
    private function init_hooks() {
        // فعال‌سازی و غیرفعال‌سازی
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // بارگذاری فایل‌های ضروری
        add_action('plugins_loaded', array($this, 'load_files'));
        
        // منوی ادمین
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // استایل و اسکریپت
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // شرط کد
        add_shortcode('workforce_dashboard', array($this, 'render_dashboard_shortcode'));
        
        // نقش‌های کاربری
        add_action('init', array($this, 'add_user_roles'));
        
        // ریدایرکت پس از لاگین
        add_filter('login_redirect', array($this, 'login_redirect'), 10, 3);
        
        // AJAX endpoints
        add_action('wp_ajax_workforce_ajax', array($this, 'handle_ajax'));
        add_action('wp_ajax_nopriv_workforce_ajax', array($this, 'handle_ajax_nopriv'));
    }

    /**
     * فعال‌سازی پلاگین
     */
    public function activate() {
        // بارگذاری فایل دیتابیس
        require_once $this->plugin_path . '4-database-core.php';
        WorkforceDatabase::create_tables();
        
        // ایجاد نقش‌ها
        $this->add_user_roles();
        
        // تنظیم صفحه پنل مدیران
        $this->create_dashboard_page();
        
        // ذخیره نسخه
        update_option('workforce_version', $this->version);
        
        // لاگ فعال‌سازی
        error_log('پلاگین کارکرد پرسنل بنی اسد فعال شد - ' . date('Y-m-d H:i:s'));
    }

    /**
     * غیرفعال‌سازی
     */
    public function deactivate() {
        // حذف cron jobs
        $timestamp = wp_next_scheduled('workforce_daily_backup');
        wp_unschedule_event($timestamp, 'workforce_daily_backup');
        
        // لاگ
        error_log('پلاگین کارکرد پرسنل بنی اسد غیرفعال شد');
    }

    /**
     * بارگذاری فایل‌ها
     */
    public function load_files() {
        $files = array(
            '4-database-core.php',
            '2-admin-panel.php',
            '3-manager-dashboard.php'
        );
        
        foreach ($files as $file) {
            $file_path = $this->plugin_path . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }

    /**
     * ایجاد نقش‌های کاربری
     */
    public function add_user_roles() {
        // مدیر سازمان
        add_role('workforce_org_manager', 'مدیر سازمان', array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'workforce_access' => true,
            'workforce_org_level' => true
        ));

        // مدیر اداره
        add_role('workforce_dept_manager', 'مدیر اداره', array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'workforce_access' => true,
            'workforce_dept_level' => true
        ));

        // اضافه کردن قابلیت‌ها به ادمین
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('workforce_access');
            $admin_role->add_cap('workforce_org_level');
            $admin_role->add_cap('workforce_admin_panel');
        }
    }

    /**
     * ایجاد صفحه پنل مدیران
     */
    private function create_dashboard_page() {
        $page_title = 'پنل مدیریت کارکرد پرسنل';
        $page_content = '[workforce_dashboard]';
        
        $page_check = get_page_by_title($page_title);
        
        if (!$page_check) {
            $page_data = array(
                'post_title'    => $page_title,
                'post_content'  => $page_content,
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_name'     => 'workforce-dashboard'
            );
            
            $page_id = wp_insert_post($page_data);
            update_option('workforce_dashboard_page_id', $page_id);
        }
    }

    /**
     * منوی ادمین
     */
    public function add_admin_menu() {
        // منوی اصلی
        add_menu_page(
            'کارکرد پرسنل',
            'کارکرد پرسنل',
            'workforce_admin_panel',
            'workforce-admin',
            array($this, 'render_admin_panel'),
            'data:image/svg+xml;base64,' . base64_encode($this->get_menu_icon()),
            30
        );

        // زیرمنوها
        add_submenu_page(
            'workforce-admin',
            'تنظیمات فیلدها',
            'فیلدها',
            'workforce_admin_panel',
            'workforce-fields',
            array($this, 'render_fields_panel')
        );

        add_submenu_page(
            'workforce-admin',
            'مدیریت ادارات',
            'ادارات',
            'workforce_admin_panel',
            'workforce-departments',
            array($this, 'render_departments_panel')
        );

        add_submenu_page(
            'workforce-admin',
            'گزارشات',
            'گزارشات',
            'workforce_admin_panel',
            'workforce-reports',
            array($this, 'render_reports_panel')
        );

        add_submenu_page(
            'workforce-admin',
            'تنظیمات',
            'تنظیمات',
            'workforce_admin_panel',
            'workforce-settings',
            array($this, 'render_settings_panel')
        );
    }

    /**
     * SVG آیکون منو
     */
    private function get_menu_icon() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            <line x1="19" y1="18" x2="19" y2="22"/>
            <line x1="23" y1="20" x2="15" y2="20"/>
        </svg>';
    }

    /**
     * رندر پنل ادمین
     */
    public function render_admin_panel() {
        if (file_exists($this->plugin_path . '2-admin-panel.php')) {
            include $this->plugin_path . '2-admin-panel.php';
        }
    }

    public function render_fields_panel() {
        echo '<div class="wrap"><h1>مدیریت فیلدها</h1>';
        echo '<div id="workforce-fields-manager"></div></div>';
    }

    public function render_departments_panel() {
        echo '<div class="wrap"><h1>مدیریت ادارات</h1>';
        echo '<div id="workforce-departments-manager"></div></div>';
    }

    public function render_reports_panel() {
        echo '<div class="wrap"><h1>گزارشات</h1>';
        echo '<div id="workforce-reports-manager"></div></div>';
    }

    public function render_settings_panel() {
        echo '<div class="wrap"><h1>تنظیمات سیستم</h1>';
        echo '<div id="workforce-settings-manager"></div></div>';
    }

    /**
     * شرط کد پنل مدیران
     */
    public function render_dashboard_shortcode($atts) {
        // فقط کاربران وارد شده
        if (!is_user_logged_in()) {
            return $this->render_login_form();
        }

        // بررسی دسترسی
        $user = wp_get_current_user();
        $allowed_roles = array('administrator', 'workforce_org_manager', 'workforce_dept_manager');
        
        if (!array_intersect($allowed_roles, $user->roles)) {
            return '<div class="workforce-error">شما دسترسی لازم به این بخش را ندارید.</div>';
        }

        // رندر پنل
        ob_start();
        include $this->plugin_path . '3-manager-dashboard.php';
        return ob_get_clean();
    }

    /**
     * فرم لاگین سفارشی
     */
    private function render_login_form() {
        $redirect = get_permalink(get_option('workforce_dashboard_page_id'));
        
        return '
        <div class="workforce-login-container">
            <div class="workforce-login-card">
                <div class="workforce-login-header">
                    <svg class="workforce-login-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                    </svg>
                    <h2>ورود به سامانه کارکرد پرسنل</h2>
                    <p>لطفاً برای ادامه وارد شوید</p>
                </div>
                ' . wp_login_form(array(
                    'echo' => false,
                    'redirect' => $redirect,
                    'form_id' => 'workforce_loginform',
                    'label_username' => 'نام کاربری',
                    'label_password' => 'رمز عبور',
                    'label_remember' => 'مرا به خاطر بسپار',
                    'label_log_in' => 'ورود به سامانه',
                    'remember' => true
                )) . '
                <div class="workforce-login-footer">
                    <a href="' . wp_lostpassword_url() . '">رمز عبور را فراموش کرده‌اید؟</a>
                </div>
            </div>
        </div>';
    }

    /**
     * ریدایرکت پس از لاگین
     */
    public function login_redirect($redirect_to, $request, $user) {
        if (isset($user->roles) && is_array($user->roles)) {
            $workforce_roles = array('workforce_org_manager', 'workforce_dept_manager');
            
            if (array_intersect($workforce_roles, $user->roles)) {
                $dashboard_page = get_option('workforce_dashboard_page_id');
                if ($dashboard_page) {
                    return get_permalink($dashboard_page);
                }
            }
        }
        return $redirect_to;
    }

    /**
     * بارگذاری استایل و اسکریپت ادمین
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'workforce-') === false) {
            return;
        }

        wp_enqueue_style(
            'workforce-admin-style',
            $this->plugin_url . '5-workforce-styles.css',
            array(),
            $this->version
        );

        wp_enqueue_script(
            'workforce-admin-script',
            $this->plugin_url . '6-workforce-scripts.js',
            array('jquery', 'wp-i18n'),
            $this->version,
            true
        );

        // داده‌های محلی
        wp_localize_script('workforce-admin-script', 'workforceData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('workforce_nonce'),
            'user_id' => get_current_user_id(),
            'user_role' => $this->get_user_role(),
            'rtl' => is_rtl(),
            'strings' => array(
                'confirm_delete' => 'آیا مطمئن هستید؟',
                'saving' => 'در حال ذخیره...',
                'saved' => 'ذخیره شد!',
                'error' => 'خطا! لطفاً مجدد تلاش کنید.'
            )
        ));

        // فارسی‌سازی
        wp_set_script_translations('workforce-admin-script', 'workforce-beni-asadd');
    }

    /**
     * بارگذاری استایل و اسکریپت فرانت‌اند
     */
    public function enqueue_frontend_assets() {
        global $post;
        
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'workforce_dashboard')) {
            return;
        }

        wp_enqueue_style(
            'workforce-frontend-style',
            $this->plugin_url . '5-workforce-styles.css',
            array(),
            $this->version
        );

        wp_enqueue_script(
            'workforce-frontend-script',
            $this->plugin_url . '6-workforce-scripts.js',
            array('jquery', 'wp-i18n'),
            $this->version,
            true
        );

        // داده برای فرانت‌اند
        wp_localize_script('workforce-frontend-script', 'workforceFrontend', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('workforce_frontend_nonce'),
            'user' => array(
                'id' => get_current_user_id(),
                'name' => wp_get_current_user()->display_name,
                'role' => $this->get_user_role()
            ),
            'dashboard_url' => get_permalink(get_option('workforce_dashboard_page_id')),
            'current_period' => $this->get_current_period(),
            'strings' => $this->get_frontend_strings()
        ));
    }

    /**
     * دریافت نقش کاربر
     */
    private function get_user_role() {
        $user = wp_get_current_user();
        $roles = $user->roles;
        
        if (in_array('administrator', $roles)) return 'admin';
        if (in_array('workforce_org_manager', $roles)) return 'org_manager';
        if (in_array('workforce_dept_manager', $roles)) return 'dept_manager';
        
        return 'none';
    }

    /**
     * دریافت دوره جاری
     */
    private function get_current_period() {
        $current_month = jdate('n', current_time('timestamp'), '', 'Asia/Tehran', 'en');
        $current_year = jdate('Y', current_time('timestamp'), '', 'Asia/Tehran', 'en');
        
        return array(
            'month' => $current_month,
            'year' => $current_year,
            'name' => $this->get_month_name($current_month) . ' ' . $current_year
        );
    }

    /**
     * نام ماه شمسی
     */
    private function get_month_name($month) {
        $months = array(
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
            4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
            7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
            10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
        );
        
        return isset($months[$month]) ? $months[$month] : 'نامشخص';
    }

    /**
     * رشته‌های فرانت‌اند
     */
    private function get_frontend_strings() {
        return array(
            'add_personnel' => 'افزودن پرسنل',
            'edit' => 'ویرایش',
            'delete' => 'حذف',
            'save' => 'ذخیره',
            'cancel' => 'انصراف',
            'next' => 'بعدی',
            'previous' => 'قبلی',
            'confirm' => 'تأیید',
            'search' => 'جستجو...',
            'filter' => 'فیلتر',
            'clear_filter' => 'پاک کردن فیلتر',
            'select_all' => 'انتخاب همه',
            'deselect_all' => 'لغو انتخاب همه',
            'loading' => 'در حال بارگذاری...',
            'no_data' => 'داده‌ای برای نمایش وجود ندارد',
            'total' => 'مجموع',
            'average' => 'میانگین',
            'export_excel' => 'خروجی اکسل',
            'period' => 'دوره',
            'department' => 'اداره',
            'required_field' => 'این فیلد الزامی است',
            'unique_error' => 'این مقدار باید منحصربه‌فرد باشد',
            'invalid_format' => 'فرمت نامعتبر'
        );
    }

    /**
     * هندلر AJAX
     */
    public function handle_ajax() {
        // بررسی nonce
        check_ajax_referer('workforce_nonce', 'nonce');
        
        // بررسی دسترسی
        if (!current_user_can('workforce_access')) {
            wp_die('دسترسی غیرمجاز', 403);
        }
        
        $action = sanitize_text_field($_POST['action_type']);
        $data = $_POST['data'] ?? array();
        
        // هندلرهای مختلف
        switch ($action) {
            case 'save_field':
                $this->ajax_save_field($data);
                break;
            case 'get_personnel':
                $this->ajax_get_personnel($data);
                break;
            case 'save_personnel':
                $this->ajax_save_personnel($data);
                break;
            case 'delete_personnel':
                $this->ajax_delete_personnel($data);
                break;
            case 'export_excel':
                $this->ajax_export_excel($data);
                break;
            default:
                wp_send_json_error('Action not found');
        }
    }

    /**
     * AJAX برای کاربران غیروارد
     */
    public function handle_ajax_nopriv() {
        wp_send_json_error('نیاز به ورود به سیستم');
    }

    /**
     * ذخیره فیلد
     */
    private function ajax_save_field($data) {
        // پیاده‌سازی در فایل دیتابیس
        wp_send_json_success('فیلد ذخیره شد');
    }

    /**
     * دریافت پرسنل
     */
    private function ajax_get_personnel($data) {
        // پیاده‌سازی در فایل دیتابیس
        wp_send_json_success(array('data' => array()));
    }

    /**
     * ذخیره پرسنل
     */
    private function ajax_save_personnel($data) {
        // پیاده‌سازی در فایل دیتابیس
        wp_send_json_success('اطلاعات ذخیره شد');
    }

    /**
     * حذف پرسنل
     */
    private function ajax_delete_personnel($data) {
        // پیاده‌سازی در فایل دیتابیس
        wp_send_json_success('حذف شد');
    }

    /**
     * خروجی اکسل
     */
    private function ajax_export_excel($data) {
        // پیاده‌سازی در فایل جدا
        wp_send_json_success('فایل آماده دانلود است');
    }
}

/**
 * راه‌اندازی پلاگین
 */
function workforce_beni_asad_init() {
    return WorkforceBeniAsad::get_instance();
}

// شروع اجرا
add_action('plugins_loaded', 'workforce_beni_asad_init');

// تابع کمکی برای استفاده در فایل‌های دیگر
function workforce() {
    return WorkforceBeniAsad::get_instance();
}