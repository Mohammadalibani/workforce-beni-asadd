<?php
/**
 * پنل مدیریت ادمین - پلاگین مدیریت کارکرد پرسنل بنی اسد
 * منوها و صفحات مدیریت در پیشخوان وردپرس
 * 
 * @package Workforce_Beni_Asad
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * افزودن منوهای مدیریت به پیشخوان وردپرس
 */
add_action('admin_menu', 'wf_admin_menu');

function wf_admin_menu() {
    // منوی اصلی
    add_menu_page(
        'مدیریت پرسنل بنی اسد',
        'کارکرد پرسنل',
        'manage_options',
        'workforce-dashboard',
        'wf_admin_dashboard',
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
        'wf_admin_dashboard'
    );
    
    add_submenu_page(
        'workforce-dashboard',
        'مدیریت فیلدها',
        'فیلدها',
        'manage_options',
        'workforce-fields',
        'wf_admin_fields'
    );
    
    add_submenu_page(
        'workforce-dashboard',
        'مدیریت ادارات',
        'ادارات',
        'manage_options',
        'workforce-departments',
        'wf_admin_departments'
    );
    
    add_submenu_page(
        'workforce-dashboard',
        'مدیریت پرسنل',
        'پرسنل',
        'manage_options',
        'workforce-personnel',
        'wf_admin_personnel'
    );
    
    add_submenu_page(
        'workforce-dashboard',
        'قالب گزارش اکسل',
        'قالب اکسل',
        'manage_options',
        'workforce-excel-templates',
        'wf_admin_excel_templates'
    );
    
    add_submenu_page(
        'workforce-dashboard',
        'تایید درخواست‌ها',
        'تایید درخواست‌ها',
        'manage_options',
        'workforce-approvals',
        'wf_admin_approvals'
    );
    
    add_submenu_page(
        'workforce-dashboard',
        'مدیریت دوره‌ها',
        'دوره‌ها',
        'manage_options',
        'workforce-periods',
        'wf_admin_periods'
    );
    
    add_submenu_page(
        'workforce-dashboard',
        'گزارش‌ها',
        'گزارش‌ها',
        'manage_options',
        'workforce-reports',
        'wf_admin_reports'
    );
    
    add_submenu_page(
        'workforce-dashboard',
        'تنظیمات',
        'تنظیمات',
        'manage_options',
        'workforce-settings',
        'wf_admin_settings'
    );
    
    // منوی مخفی برای ابزارها
    add_submenu_page(
        null,
        'ابزارهای سیستم',
        'ابزارها',
        'manage_options',
        'workforce-tools',
        'wf_admin_tools'
    );
}

/**
 * ثبت استایل‌ها و اسکریپت‌های ادمین
 */
add_action('admin_enqueue_scripts', 'wf_admin_enqueue_scripts');

function wf_admin_enqueue_scripts($hook) {
    // فقط در صفحات پلاگین بارگذاری شود
    if (strpos($hook, 'workforce-') === false) {
        return;
    }
    
    // استایل‌ها
    wp_enqueue_style(
        'wf-admin-style',
        WF_PLUGIN_URL . 'assets/css/admin-style.css',
        array(),
        '1.0.0'
    );
    
    // اسکریپت‌ها
    wp_enqueue_script(
        'wf-admin-script',
        WF_PLUGIN_URL . 'assets/js/admin-script.js',
        array('jquery', 'jquery-ui-sortable', 'wp-color-picker'),
        '1.0.0',
        true
    );
    
    // Localize script for translations and AJAX
    wp_localize_script('wf-admin-script', 'wf_admin_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wf_admin_nonce'),
        'confirm_delete' => 'آیا از حذف این آیتم اطمینان دارید؟',
        'confirm_bulk_delete' => 'آیا از حذف آیتم‌های انتخاب شده اطمینان دارید؟',
        'loading' => 'در حال بارگذاری...',
        'saving' => 'در حال ذخیره...',
        'success' => 'عملیات با موفقیت انجام شد',
        'error' => 'خطا در انجام عملیات'
    ));
    
    // Color picker
    wp_enqueue_style('wp-color-picker');
}

/**
 * ============================================
 * صفحه داشبورد ادمین
 * ============================================
 */

function wf_admin_dashboard() {
    // بررسی دسترسی
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
    // دریافت آمار سیستم
    $stats = wf_get_system_stats();
    
    ?>
    <div class="wrap wf-admin-wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-dashboard"></span>
            داشبورد مدیریت پرسنل
        </h1>
        
        <div class="wf-dashboard-container">
            <!-- کارت‌های آمار -->
            <div class="wf-stats-grid">
                <!-- کارت ادارات -->
                <div class="wf-stat-card wf-stat-card-primary">
                    <div class="wf-stat-icon">
                        <span class="dashicons dashicons-building"></span>
                    </div>
                    <div class="wf-stat-content">
                        <h3><?php echo esc_html($stats['total_departments']); ?></h3>
                        <p>تعداد ادارات</p>
                    </div>
                    <div class="wf-stat-footer">
                        <a href="<?php echo admin_url('admin.php?page=workforce-departments'); ?>">
                            مشاهده همه →
                        </a>
                    </div>
                </div>
                
                <!-- کارت پرسنل -->
                <div class="wf-stat-card wf-stat-card-success">
                    <div class="wf-stat-icon">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <div class="wf-stat-content">
                        <h3><?php echo esc_html($stats['total_personnel']); ?></h3>
                        <p>تعداد پرسنل</p>
                    </div>
                    <div class="wf-stat-footer">
                        <a href="<?php echo admin_url('admin.php?page=workforce-personnel'); ?>">
                            مشاهده همه →
                        </a>
                    </div>
                </div>
                
                <!-- کارت فیلدها -->
                <div class="wf-stat-card wf-stat-card-info">
                    <div class="wf-stat-icon">
                        <span class="dashicons dashicons-list-view"></span>
                    </div>
                    <div class="wf-stat-content">
                        <h3><?php echo esc_html($stats['total_fields']); ?></h3>
                        <p>تعداد فیلدها</p>
                    </div>
                    <div class="wf-stat-footer">
                        <a href="<?php echo admin_url('admin.php?page=workforce-fields'); ?>">
                            مشاهده همه →
                        </a>
                    </div>
                </div>
                
                <!-- کارت درخواست‌های در انتظار -->
                <div class="wf-stat-card wf-stat-card-warning">
                    <div class="wf-stat-icon">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <div class="wf-stat-content">
                        <h3><?php echo esc_html($stats['pending_approvals']); ?></h3>
                        <p>درخواست در انتظار</p>
                    </div>
                    <div class="wf-stat-footer">
                        <a href="<?php echo admin_url('admin.php?page=workforce-approvals'); ?>">
                            بررسی درخواست‌ها →
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- بخش‌های اصلی -->
            <div class="wf-dashboard-sections">
                <!-- بخش ادارات و مدیران -->
                <div class="wf-dashboard-section">
                    <div class="wf-section-header">
                        <h2>
                            <span class="dashicons dashicons-building"></span>
                            ادارات و مدیران
                        </h2>
                        <a href="<?php echo admin_url('admin.php?page=workforce-departments&action=add'); ?>" 
                           class="button button-primary">
                            <span class="dashicons dashicons-plus"></span>
                            افزودن اداره جدید
                        </a>
                    </div>
                    
                    <div class="wf-section-content">
                        <?php
                        $departments = wf_get_departments(array(
                            'limit' => 5,
                            'with_manager' => true
                        ));
                        
                        if (empty($departments)) {
                            echo '<p class="wf-no-data">هیچ اداره‌ای ثبت نشده است.</p>';
                        } else {
                            echo '<table class="wp-list-table widefat fixed striped">';
                            echo '<thead>
                                <tr>
                                    <th>نام اداره</th>
                                    <th>مدیر</th>
                                    <th>تعداد پرسنل</th>
                                    <th>وضعیت</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>';
                            echo '<tbody>';
                            
                            foreach ($departments as $dept) {
                                $status_badge = wf_get_status_badge(
                                    $dept['status'],
                                    $dept['status'] == 'active' ? 'فعال' : 'غیرفعال'
                                );
                                
                                echo '<tr>';
                                echo '<td>
                                    <strong>' . esc_html($dept['name']) . '</strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="' . admin_url('admin.php?page=workforce-departments&action=edit&id=' . $dept['id']) . '">ویرایش</a>
                                        </span>
                                    </div>
                                </td>';
                                echo '<td>' . ($dept['manager_name'] ? esc_html($dept['manager_name']) : '---') . '</td>';
                                echo '<td>' . esc_html($dept['personnel_count']) . '</td>';
                                echo '<td>' . $status_badge . '</td>';
                                echo '<td>
                                    <div class="wf-action-buttons">
                                        <a href="' . admin_url('admin.php?page=workforce-personnel&department=' . $dept['id']) . '" 
                                           class="button button-small">
                                            <span class="dashicons dashicons-groups"></span>
                                            پرسنل
                                        </a>
                                    </div>
                                </td>';
                                echo '</tr>';
                            }
                            
                            echo '</tbody>';
                            echo '</table>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- بخش فعالیت‌های اخیر -->
                <div class="wf-dashboard-section">
                    <div class="wf-section-header">
                        <h2>
                            <span class="dashicons dashicons-update"></span>
                            فعالیت‌های اخیر
                        </h2>
                    </div>
                    
                    <div class="wf-section-content">
                        <?php
                        if (empty($stats['recent_activities'])) {
                            echo '<p class="wf-no-data">هیچ فعالیتی ثبت نشده است.</p>';
                        } else {
                            echo '<div class="wf-activities-list">';
                            
                            foreach ($stats['recent_activities'] as $activity) {
                                $time_diff = wf_relative_time($activity['created_at']);
                                $user_name = $activity['display_name'] ?: 'سیستم';
                                
                                echo '<div class="wf-activity-item">';
                                echo '<div class="wf-activity-icon">';
                                echo '<span class="dashicons dashicons-' . wf_get_activity_icon($activity['activity_type']) . '"></span>';
                                echo '</div>';
                                echo '<div class="wf-activity-content">';
                                echo '<p class="wf-activity-desc">' . esc_html($activity['description']) . '</p>';
                                echo '<div class="wf-activity-meta">';
                                echo '<span class="wf-activity-user">👤 ' . esc_html($user_name) . '</span>';
                                echo '<span class="wf-activity-time">🕒 ' . esc_html($time_diff) . '</span>';
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                            }
                            
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- بخش هشدارها -->
                <div class="wf-dashboard-section wf-alerts-section">
                    <div class="wf-section-header">
                        <h2>
                            <span class="dashicons dashicons-warning"></span>
                            هشدارها و اعلان‌ها
                        </h2>
                    </div>
                    
                    <div class="wf-section-content">
                        <?php
                        $alerts = wf_get_system_alerts();
                        
                        if (empty($alerts)) {
                            echo '<div class="wf-alert wf-alert-success">';
                            echo '<p>✅ همه چیز به خوبی کار می‌کند. هیچ هشداری وجود ندارد.</p>';
                            echo '</div>';
                        } else {
                            foreach ($alerts as $alert) {
                                $alert_class = 'wf-alert-' . $alert['type'];
                                echo '<div class="wf-alert ' . $alert_class . '">';
                                echo '<p>' . esc_html($alert['message']) . '</p>';
                                if (!empty($alert['action'])) {
                                    echo '<a href="' . esc_url($alert['action']['url']) . '" class="button button-small">';
                                    echo esc_html($alert['action']['text']);
                                    echo '</a>';
                                }
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
                
                <!-- بخش لینک‌های سریع -->
                <div class="wf-dashboard-section">
                    <div class="wf-section-header">
                        <h2>
                            <span class="dashicons dashicons-admin-links"></span>
                            لینک‌های سریع
                        </h2>
                    </div>
                    
                    <div class="wf-section-content">
                        <div class="wf-quick-links">
                            <a href="<?php echo admin_url('admin.php?page=workforce-fields&action=add'); ?>" 
                               class="wf-quick-link">
                                <span class="dashicons dashicons-plus"></span>
                                افزودن فیلد جدید
                            </a>
                            
                            <a href="<?php echo admin_url('admin.php?page=workforce-personnel&action=add'); ?>" 
                               class="wf-quick-link">
                                <span class="dashicons dashicons-plus"></span>
                                افزودن پرسنل جدید
                            </a>
                            
                            <a href="<?php echo admin_url('admin.php?page=workforce-periods&action=add'); ?>" 
                               class="wf-quick-link">
                                <span class="dashicons dashicons-plus"></span>
                                ایجاد دوره جدید
                            </a>
                            
                            <a href="<?php echo admin_url('admin.php?page=workforce-reports'); ?>" 
                               class="wf-quick-link">
                                <span class="dashicons dashicons-chart-bar"></span>
                                گزارش‌گیری
                            </a>
                            
                            <a href="<?php echo admin_url('admin.php?page=workforce-tools'); ?>" 
                               class="wf-quick-link">
                                <span class="dashicons dashicons-admin-tools"></span>
                                ابزارهای سیستم
                            </a>
                            
                            <a href="<?php echo admin_url('admin.php?page=workforce-settings'); ?>" 
                               class="wf-quick-link">
                                <span class="dashicons dashicons-admin-generic"></span>
                                تنظیمات سیستم
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .wf-admin-wrap {
        padding: 20px;
    }
    
    .wf-dashboard-container {
        margin-top: 20px;
    }
    
    .wf-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .wf-stat-card {
        background: white;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        transition: transform 0.3s ease;
    }
    
    .wf-stat-card:hover {
        transform: translateY(-5px);
    }
    
    .wf-stat-card-primary {
        border-right: 4px solid #3b82f6;
    }
    
    .wf-stat-card-success {
        border-right: 4px solid #10b981;
    }
    
    .wf-stat-card-info {
        border-right: 4px solid #0ea5e9;
    }
    
    .wf-stat-card-warning {
        border-right: 4px solid #f59e0b;
    }
    
    .wf-stat-icon {
        margin-left: 20px;
    }
    
    .wf-stat-icon .dashicons {
        font-size: 40px;
        width: 40px;
        height: 40px;
    }
    
    .wf-stat-card-primary .wf-stat-icon .dashicons {
        color: #3b82f6;
    }
    
    .wf-stat-card-success .wf-stat-icon .dashicons {
        color: #10b981;
    }
    
    .wf-stat-card-info .wf-stat-icon .dashicons {
        color: #0ea5e9;
    }
    
    .wf-stat-card-warning .wf-stat-icon .dashicons {
        color: #f59e0b;
    }
    
    .wf-stat-content h3 {
        font-size: 28px;
        margin: 0 0 5px 0;
        color: #1f2937;
    }
    
    .wf-stat-content p {
        margin: 0;
        color: #6b7280;
        font-size: 14px;
    }
    
    .wf-stat-footer {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #e5e7eb;
    }
    
    .wf-stat-footer a {
        color: #6b7280;
        text-decoration: none;
        font-size: 13px;
    }
    
    .wf-stat-footer a:hover {
        color: #3b82f6;
    }
    
    .wf-dashboard-sections {
        display: grid;
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    @media (min-width: 1200px) {
        .wf-dashboard-sections {
            grid-template-columns: 2fr 1fr;
        }
    }
    
    .wf-dashboard-section {
        background: white;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .wf-section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .wf-section-header h2 {
        margin: 0;
        font-size: 18px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .wf-no-data {
        text-align: center;
        padding: 40px 20px;
        color: #6b7280;
    }
    
    .wf-activities-list {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .wf-activity-item {
        display: flex;
        gap: 15px;
        padding: 15px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .wf-activity-item:last-child {
        border-bottom: none;
    }
    
    .wf-activity-icon .dashicons {
        font-size: 20px;
        color: #9ca3af;
    }
    
    .wf-activity-content {
        flex: 1;
    }
    
    .wf-activity-desc {
        margin: 0 0 8px 0;
        font-size: 14px;
        line-height: 1.5;
    }
    
    .wf-activity-meta {
        display: flex;
        gap: 15px;
        font-size: 12px;
        color: #6b7280;
    }
    
    .wf-alerts-section .wf-alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        border-right: 4px solid;
    }
    
    .wf-alert-success {
        background: #d1fae5;
        border-color: #10b981;
    }
    
    .wf-alert-warning {
        background: #fef3c7;
        border-color: #f59e0b;
    }
    
    .wf-alert-error {
        background: #fee2e2;
        border-color: #ef4444;
    }
    
    .wf-alert-info {
        background: #dbeafe;
        border-color: #3b82f6;
    }
    
    .wf-alert p {
        margin: 0 0 10px 0;
    }
    
    .wf-quick-links {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .wf-quick-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: #f8fafc;
        border-radius: 8px;
        text-decoration: none;
        color: #374151;
        text-align: center;
        transition: all 0.3s ease;
        border: 1px solid #e5e7eb;
    }
    
    .wf-quick-link:hover {
        background: #3b82f6;
        color: white;
        transform: translateY(-3px);
        border-color: #3b82f6;
    }
    
    .wf-quick-link .dashicons {
        font-size: 24px;
        margin-bottom: 10px;
    }
    
    .wf-action-buttons {
        display: flex;
        gap: 5px;
    }
    </style>
    <?php
}

/**
 * ============================================
 * صفحه مدیریت فیلدها
 * ============================================
 */

function wf_admin_fields() {
    // بررسی دسترسی
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
    // دریافت action
    $action = $_GET['action'] ?? 'list';
    $field_id = $_GET['id'] ?? 0;
    
    switch ($action) {
        case 'add':
        case 'edit':
            wf_admin_field_form($field_id, $action);
            break;
        case 'delete':
            wf_admin_delete_field($field_id);
            break;
        default:
            wf_admin_fields_list();
    }
}

function wf_admin_fields_list() {
    // دریافت فیلدها
    $fields = wf_get_fields();
    
    // دریافت پیام‌های عملیات
    $message = '';
    if (isset($_GET['message'])) {
        switch ($_GET['message']) {
            case 'created':
                $message = '<div class="notice notice-success"><p>فیلد جدید با موفقیت ایجاد شد.</p></div>';
                break;
            case 'updated':
                $message = '<div class="notice notice-success"><p>فیلد با موفقیت به‌روزرسانی شد.</p></div>';
                break;
            case 'deleted':
                $message = '<div class="notice notice-success"><p>فیلد با موفقیت حذف شد.</p></div>';
                break;
            case 'error':
                $message = '<div class="notice notice-error"><p>خطا در انجام عملیات.</p></div>';
                break;
        }
    }
    
    ?>
    <div class="wrap wf-admin-wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-list-view"></span>
            مدیریت فیلدها
        </h1>
        
        <a href="<?php echo admin_url('admin.php?page=workforce-fields&action=add'); ?>" 
           class="page-title-action">
            <span class="dashicons dashicons-plus"></span>
            افزودن فیلد جدید
        </a>
        
        <hr class="wp-header-end">
        
        <?php echo $message; ?>
        
        <div class="wf-admin-container">
            <div class="wf-filters">
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <select name="field_type_filter" id="field_type_filter">
                            <option value="">همه نوع‌ها</option>
                            <option value="text">متن</option>
                            <option value="number">عدد</option>
                            <option value="decimal">اعشار</option>
                            <option value="date">تاریخ</option>
                            <option value="time">زمان</option>
                            <option value="datetime">تاریخ و زمان</option>
                            <option value="select">انتخابی</option>
                            <option value="checkbox">چک‌باکس</option>
                        </select>
                        
                        <select name="field_status_filter" id="field_status_filter">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="active">فعال</option>
                            <option value="inactive">غیرفعال</option>
                        </select>
                        
                        <button type="button" class="button" id="apply_filters">اعمال فیلتر</button>
                        <button type="button" class="button" id="reset_filters">بازنشانی</button>
                    </div>
                    
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo count($fields); ?> فیلد</span>
                    </div>
                </div>
            </div>
            
            <form method="post" action="<?php echo admin_url('admin.php?page=workforce-fields'); ?>">
                <?php wp_nonce_field('wf_bulk_action_fields', 'wf_fields_nonce'); ?>
                
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="action" id="bulk-action-selector-top">
                            <option value="-1">عملیات دسته‌ای</option>
                            <option value="activate">فعال‌سازی</option>
                            <option value="deactivate">غیرفعال‌سازی</option>
                            <option value="delete">حذف</option>
                        </select>
                        <button type="submit" class="button action" id="doaction">اعمال</button>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column">
                                <input type="checkbox" id="cb-select-all-1">
                            </td>
                            <th scope="col" width="50">ترتیب</th>
                            <th scope="col">عنوان فارسی</th>
                            <th scope="col">نام فیلد</th>
                            <th scope="col">نوع</th>
                            <th scope="col">ویژگی‌ها</th>
                            <th scope="col">وضعیت</th>
                            <th scope="col">عملیات</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <?php if (empty($fields)): ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    <p class="wf-no-data">هیچ فیلدی تعریف نشده است.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fields as $field): ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="field_ids[]" value="<?php echo $field['id']; ?>">
                                </th>
                                <td>
                                    <input type="number" 
                                           name="order[<?php echo $field['id']; ?>]" 
                                           value="<?php echo $field['field_order']; ?>" 
                                           class="small-text wf-order-input"
                                           data-id="<?php echo $field['id']; ?>">
                                </td>
                                <td>
                                    <strong><?php echo esc_html($field['title']); ?></strong>
                                    <?php if ($field['is_required']): ?>
                                        <span class="wf-badge wf-badge-required" title="ضروری">*</span>
                                    <?php endif; ?>
                                    <?php if ($field['is_key']): ?>
                                        <span class="wf-badge wf-badge-key" title="کلید">🔑</span>
                                    <?php endif; ?>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo admin_url('admin.php?page=workforce-fields&action=edit&id=' . $field['id']); ?>">
                                                ویرایش
                                            </a>
                                        </span>
                                        |
                                        <span class="duplicate">
                                            <a href="#" class="wf-duplicate-field" data-id="<?php echo $field['id']; ?>">
                                                تکثیر
                                            </a>
                                        </span>
                                        |
                                        <span class="delete">
                                            <a href="<?php echo admin_url('admin.php?page=workforce-fields&action=delete&id=' . $field['id']); ?>" 
                                               class="submitdelete" 
                                               onclick="return confirm('آیا از حذف این فیلد اطمینان دارید؟')">
                                                حذف
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <code><?php echo esc_html($field['name']); ?></code>
                                </td>
                                <td>
                                    <?php echo wf_get_field_type_label($field['type']); ?>
                                </td>
                                <td>
                                    <div class="wf-field-features">
                                        <?php if ($field['is_required']): ?>
                                            <span class="wf-feature-badge" title="ضروری">
                                                <span class="dashicons dashicons-yes"></span>
                                                ضروری
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($field['is_locked']): ?>
                                            <span class="wf-feature-badge" title="قفل شده">
                                                <span class="dashicons dashicons-lock"></span>
                                                قفل
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($field['is_monitoring']): ?>
                                            <span class="wf-feature-badge" title="مانیتورینگ">
                                                <span class="dashicons dashicons-chart-area"></span>
                                                مانیتورینگ
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($field['is_key']): ?>
                                            <span class="wf-feature-badge" title="کلید">
                                                <span class="dashicons dashicons-admin-network"></span>
                                                کلید
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo wf_get_status_badge(
                                        $field['status'],
                                        $field['status'] == 'active' ? 'فعال' : 'غیرفعال'
                                    ); ?>
                                </td>
                                <td>
                                    <div class="wf-action-buttons">
                                        <a href="<?php echo admin_url('admin.php?page=workforce-fields&action=edit&id=' . $field['id']); ?>" 
                                           class="button button-small">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                        
                                        <a href="<?php echo admin_url('admin.php?page=workforce-fields&action=delete&id=' . $field['id']); ?>" 
                                           class="button button-small button-danger"
                                           onclick="return confirm('آیا از حذف این فیلد اطمینان دارید؟')">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
            
            <div class="wf-info-box">
                <h3>
                    <span class="dashicons dashicons-info"></span>
                    راهنمای فیلدها
                </h3>
                <ul>
                    <li><strong>فیلد ضروری (*):</strong> کاربر باید حتماً آن را پر کند</li>
                    <li><strong>فیلد قفل (🔒):</strong> فقط ادمین می‌تواند ویرایش کند</li>
                    <li><strong>فیلد مانیتورینگ (📊):</strong> در کارت‌های مانیتورینگ نمایش داده می‌شود</li>
                    <li><strong>فیلد کلید (🔑):</strong> مقدار یکتا و منحصربه‌فرد (مثل کدملی)</li>
                </ul>
            </div>
        </div>
    </div>
    
    <style>
    .wf-field-features {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }
    
    .wf-feature-badge {
        background: #f3f4f6;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 11px;
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }
    
    .wf-badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: bold;
        margin-right: 5px;
    }
    
    .wf-badge-required {
        background: #fef3c7;
        color: #92400e;
    }
    
    .wf-badge-key {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .wf-order-input {
        width: 60px !important;
        text-align: center;
    }
    
    .wf-info-box {
        background: #f0f9ff;
        border: 1px solid #0ea5e9;
        border-radius: 8px;
        padding: 20px;
        margin-top: 30px;
    }
    
    .wf-info-box h3 {
        margin-top: 0;
        color: #0369a1;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .wf-info-box ul {
        margin: 15px 0 0 20px;
    }
    
    .wf-info-box li {
        margin-bottom: 8px;
        line-height: 1.5;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // ذخیره ترتیب فیلدها
        $('.wf-order-input').on('change', function() {
            var field_id = $(this).data('id');
            var new_order = $(this).val();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wf_update_field_order',
                    field_id: field_id,
                    order: new_order,
                    nonce: wf_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // نمایش پیام موفقیت
                        var notice = $('<div class="notice notice-success is-dismissible"><p>' + wf_admin_ajax.success + '</p></div>');
                        $('.wf-admin-wrap').prepend(notice);
                        
                        // حذف پیام بعد از 3 ثانیه
                        setTimeout(function() {
                            notice.fadeOut(300, function() {
                                $(this).remove();
                            });
                        }, 3000);
                    }
                }
            });
        });
        
        // تکثیر فیلد
        $('.wf-duplicate-field').on('click', function(e) {
            e.preventDefault();
            var field_id = $(this).data('id');
            
            if (confirm('آیا از تکثیر این فیلد اطمینان دارید؟')) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wf_duplicate_field',
                        field_id: field_id,
                        nonce: wf_admin_ajax.nonce
                    },
                    beforeSend: function() {
                        $(this).text(wf_admin_ajax.loading);
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message || wf_admin_ajax.error);
                        }
                    }
                });
            }
        });
    });
    </script>
    <?php
}

function wf_admin_field_form($field_id = 0, $action = 'add') {
    $field = $field_id ? wf_get_field($field_id) : array();
    $is_edit = ($action == 'edit' && !empty($field));
    
    // تنظیم مقادیر پیش‌فرض
    $defaults = array(
        'name' => '',
        'title' => '',
        'type' => 'text',
        'default' => '',
        'is_required' => 0,
        'is_locked' => 0,
        'is_monitoring' => 0,
        'is_key' => 0,
        'field_order' => 0,
        'validation_rules' => array(),
        'help_text' => '',
        'options' => array(),
        'status' => 'active'
    );
    
    $field_data = wp_parse_args($field ?: array(), $defaults);
    
    ?>
    <div class="wrap wf-admin-wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-list-view"></span>
            <?php echo $is_edit ? 'ویرایش فیلد' : 'افزودن فیلد جدید'; ?>
        </h1>
        
        <a href="<?php echo admin_url('admin.php?page=workforce-fields'); ?>" 
           class="page-title-action">
            <span class="dashicons dashicons-arrow-right-alt"></span>
            بازگشت به لیست فیلدها
        </a>
        
        <hr class="wp-header-end">
        
        <div class="wf-admin-container">
            <form method="post" action="<?php echo admin_url('admin.php?page=workforce-fields'); ?>" 
                  id="wf-field-form">
                <?php wp_nonce_field('wf_save_field', 'wf_field_nonce'); ?>
                
                <?php if ($is_edit): ?>
                    <input type="hidden" name="field_id" value="<?php echo $field_id; ?>">
                <?php endif; ?>
                
                <input type="hidden" name="action" value="<?php echo $is_edit ? 'edit_field' : 'add_field'; ?>">
                
                <div class="wf-form-sections">
                    <!-- بخش اطلاعات اصلی -->
                    <div class="wf-form-section">
                        <h2>
                            <span class="dashicons dashicons-info"></span>
                            اطلاعات اصلی فیلد
                        </h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="field_title">عنوان فارسی <span class="required">*</span></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="field_title" 
                                           name="field_title" 
                                           value="<?php echo esc_attr($field_data['title']); ?>" 
                                           class="regular-text" 
                                           required>
                                    <p class="description">عنوان فارسی فیلد که در جدول نمایش داده می‌شود</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="field_name">نام فیلد <span class="required">*</span></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="field_name" 
                                           name="field_name" 
                                           value="<?php echo esc_attr($field_data['name']); ?>" 
                                           class="regular-text" 
                                           pattern="[a-z][a-z0-9_]*" 
                                           <?php echo $is_edit ? 'readonly' : ''; ?> 
                                           required>
                                    <p class="description">نام انگلیسی فیلد (فقط حروف کوچک، اعداد و زیرخط) - بعد از ذخیره قابل تغییر نیست</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="field_type">نوع فیلد <span class="required">*</span></label>
                                </th>
                                <td>
                                    <select id="field_type" name="field_type" class="regular-text">
                                        <option value="text" <?php selected($field_data['type'], 'text'); ?>>متن</option>
                                        <option value="number" <?php selected($field_data['type'], 'number'); ?>>عدد</option>
                                        <option value="decimal" <?php selected($field_data['type'], 'decimal'); ?>>اعشار</option>
                                        <option value="date" <?php selected($field_data['type'], 'date'); ?>>تاریخ</option>
                                        <option value="time" <?php selected($field_data['type'], 'time'); ?>>زمان</option>
                                        <option value="datetime" <?php selected($field_data['type'], 'datetime'); ?>>تاریخ و زمان</option>
                                        <option value="select" <?php selected($field_data['type'], 'select'); ?>>انتخابی</option>
                                        <option value="checkbox" <?php selected($field_data['type'], 'checkbox'); ?>>چک‌باکس</option>
                                    </select>
                                    <p class="description">نوع داده فیلد را انتخاب کنید</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="field_default">مقدار پیش‌فرض</label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="field_default" 
                                           name="field_default" 
                                           value="<?php echo esc_attr($field_data['default']); ?>" 
                                           class="regular-text">
                                    <p class="description">مقدار پیش‌فرض فیلد (اختیاری)</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="field_order">ترتیب نمایش</label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="field_order" 
                                           name="field_order" 
                                           value="<?php echo esc_attr($field_data['field_order']); ?>" 
                                           class="small-text" 
                                           min="0">
                                    <p class="description">ترتیب نمایش فیلد در جدول (اعداد کمتر اول نمایش داده می‌شوند)</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="field_help_text">متن راهنما</label>
                                </th>
                                <td>
                                    <textarea id="field_help_text" 
                                              name="field_help_text" 
                                              class="large-text" 
                                              rows="3"><?php echo esc_textarea($field_data['help_text']); ?></textarea>
                                    <p class="description">متن راهنمای فیلد که در فرم‌ها نمایش داده می‌شود</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- بخش تنظیمات فیلد -->
                    <div class="wf-form-section">
                        <h2>
                            <span class="dashicons dashicons-admin-generic"></span>
                            تنظیمات فیلد
                        </h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">ویژگی‌ها</th>
                                <td>
                                    <fieldset>
                                        <label for="field_required">
                                            <input type="checkbox" 
                                                   id="field_required" 
                                                   name="field_required" 
                                                   value="1" 
                                                   <?php checked($field_data['is_required'], 1); ?>>
                                            <span class="wf-checkbox-label">
                                                <strong>ضروری</strong>
                                                <span class="description">کاربر باید حتماً این فیلد را پر کند</span>
                                            </span>
                                        </label>
                                        <br>
                                        
                                        <label for="field_locked">
                                            <input type="checkbox" 
                                                   id="field_locked" 
                                                   name="field_locked" 
                                                   value="1" 
                                                   <?php checked($field_data['is_locked'], 1); ?>>
                                            <span class="wf-checkbox-label">
                                                <strong>قفل شده</strong>
                                                <span class="description">فقط ادمین می‌تواند این فیلد را ویرایش کند</span>
                                            </span>
                                        </label>
                                        <br>
                                        
                                        <label for="field_monitoring">
                                            <input type="checkbox" 
                                                   id="field_monitoring" 
                                                   name="field_monitoring" 
                                                   value="1" 
                                                   <?php checked($field_data['is_monitoring'], 1); ?>>
                                            <span class="wf-checkbox-label">
                                                <strong>مانیتورینگ</strong>
                                                <span class="description">در کارت‌های مانیتورینگ نمایش داده شود</span>
                                            </span>
                                        </label>
                                        <br>
                                        
                                        <label for="field_key">
                                            <input type="checkbox" 
                                                   id="field_key" 
                                                   name="field_key" 
                                                   value="1" 
                                                   <?php checked($field_data['is_key'], 1); ?>>
                                            <span class="wf-checkbox-label">
                                                <strong>کلید (یکتا)</strong>
                                                <span class="description">مقدار این فیلد باید در کل سیستم یکتا باشد (مثل کدملی)</span>
                                            </span>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="field_status">وضعیت</label>
                                </th>
                                <td>
                                    <select id="field_status" name="field_status" class="regular-text">
                                        <option value="active" <?php selected($field_data['status'], 'active'); ?>>فعال</option>
                                        <option value="inactive" <?php selected($field_data['status'], 'inactive'); ?>>غیرفعال</option>
                                    </select>
                                    <p class="description">فیلدهای غیرفعال نمایش داده نمی‌شوند</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- بخش اعتبارسنجی -->
                    <div class="wf-form-section">
                        <h2>
                            <span class="dashicons dashicons-shield"></span>
                            تنظیمات اعتبارسنجی
                        </h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="validation_min_length">حداقل طول</label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="validation_min_length" 
                                           name="validation[min_length]" 
                                           value="<?php echo esc_attr($field_data['validation_rules']['min_length'] ?? ''); ?>" 
                                           class="small-text" 
                                           min="0">
                                    <p class="description">حداقل تعداد کاراکترهای مجاز</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="validation_max_length">حداکثر طول</label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="validation_max_length" 
                                           name="validation[max_length]" 
                                           value="<?php echo esc_attr($field_data['validation_rules']['max_length'] ?? ''); ?>" 
                                           class="small-text" 
                                           min="1">
                                    <p class="description">حداکثر تعداد کاراکترهای مجاز</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="validation_pattern">الگوی regex</label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="validation_pattern" 
                                           name="validation[pattern]" 
                                           value="<?php echo esc_attr($field_data['validation_rules']['pattern'] ?? ''); ?>" 
                                           class="regular-text">
                                    <p class="description">الگوی regex برای اعتبارسنجی (اختیاری)</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="validation_min">حداقل مقدار</label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="validation_min" 
                                           name="validation[min]" 
                                           value="<?php echo esc_attr($field_data['validation_rules']['min'] ?? ''); ?>" 
                                           class="small-text">
                                    <p class="description">حداقل مقدار مجاز برای فیلدهای عددی</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="validation_max">حداکثر مقدار</label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="validation_max" 
                                           name="validation[max]" 
                                           value="<?php echo esc_attr($field_data['validation_rules']['max'] ?? ''); ?>" 
                                           class="small-text">
                                    <p class="description">حداکثر مقدار مجاز برای فیلدهای عددی</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- بخش گزینه‌های انتخابی (برای فیلدهای select) -->
                    <div class="wf-form-section wf-options-section" style="<?php echo $field_data['type'] != 'select' ? 'display: none;' : ''; ?>">
                        <h2>
                            <span class="dashicons dashicons-list-view"></span>
                            گزینه‌های انتخابی
                        </h2>
                        
                        <div id="wf-options-container">
                            <?php if (!empty($field_data['options'])): ?>
                                <?php foreach ($field_data['options'] as $index => $option): ?>
                                <div class="wf-option-row" data-index="<?php echo $index; ?>">
                                    <input type="text" 
                                           name="options[<?php echo $index; ?>][label]" 
                                           value="<?php echo esc_attr($option['label']); ?>" 
                                           placeholder="عنوان گزینه" 
                                           class="regular-text">
                                    <input type="text" 
                                           name="options[<?php echo $index; ?>][value]" 
                                           value="<?php echo esc_attr($option['value']); ?>" 
                                           placeholder="مقدار گزینه" 
                                           class="regular-text">
                                    <button type="button" class="button button-small wf-remove-option">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="wf-option-row" data-index="0">
                                    <input type="text" 
                                           name="options[0][label]" 
                                           placeholder="عنوان گزینه" 
                                           class="regular-text">
                                    <input type="text" 
                                           name="options[0][value]" 
                                           placeholder="مقدار گزینه" 
                                           class="regular-text">
                                    <button type="button" class="button button-small wf-remove-option">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="button" id="wf-add-option" class="button button-secondary">
                            <span class="dashicons dashicons-plus"></span>
                            افزودن گزینه جدید
                        </button>
                    </div>
                </div>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-large">
                        <span class="dashicons dashicons-yes"></span>
                        <?php echo $is_edit ? 'ذخیره تغییرات' : 'ایجاد فیلد'; ?>
                    </button>
                    
                    <a href="<?php echo admin_url('admin.php?page=workforce-fields'); ?>" class="button button-large">
                        <span class="dashicons dashicons-no"></span>
                        انصراف
                    </a>
                </p>
            </form>
        </div>
    </div>
    
    <style>
    .wf-form-sections {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .wf-form-section {
        margin-bottom: 40px;
        padding-bottom: 30px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .wf-form-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .wf-form-section h2 {
        color: #374151;
        font-size: 18px;
        margin-top: 0;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .wf-checkbox-label {
        display: inline-block;
        margin-right: 10px;
    }
    
    .wf-checkbox-label .description {
        display: block;
        color: #6b7280;
        font-weight: normal;
        font-size: 13px;
        margin-top: 3px;
    }
    
    .wf-option-row {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        align-items: center;
    }
    
    .wf-option-row input {
        flex: 1;
    }
    
    #wf-add-option {
        margin-top: 15px;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // نمایش/پنهان کردن بخش گزینه‌ها بر اساس نوع فیلد
        $('#field_type').on('change', function() {
            var type = $(this).val();
            if (type === 'select') {
                $('.wf-options-section').show();
            } else {
                $('.wf-options-section').hide();
            }
        });
        
        // اضافه کردن گزینه جدید
        var optionIndex = <?php echo !empty($field_data['options']) ? count($field_data['options']) : 1; ?>;
        
        $('#wf-add-option').on('click', function() {
            var html = '<div class="wf-option-row" data-index="' + optionIndex + '">' +
                '<input type="text" name="options[' + optionIndex + '][label]" placeholder="عنوان گزینه" class="regular-text">' +
                '<input type="text" name="options[' + optionIndex + '][value]" placeholder="مقدار گزینه" class="regular-text">' +
                '<button type="button" class="button button-small wf-remove-option">' +
                '<span class="dashicons dashicons-trash"></span>' +
                '</button>' +
                '</div>';
            
            $('#wf-options-container').append(html);
            optionIndex++;
        });
        
        // حذف گزینه
        $(document).on('click', '.wf-remove-option', function() {
            if ($('.wf-option-row').length > 1) {
                $(this).closest('.wf-option-row').remove();
            } else {
                alert('حداقل یک گزینه باید وجود داشته باشد');
            }
        });
        
        // اعتبارسنجی فرم
        $('#wf-field-form').on('submit', function(e) {
            var title = $('#field_title').val().trim();
            var name = $('#field_name').val().trim();
            
            if (!title) {
                alert('لطفا عنوان فارسی فیلد را وارد کنید');
                $('#field_title').focus();
                e.preventDefault();
                return false;
            }
            
            if (!name) {
                alert('لطفا نام فیلد را وارد کنید');
                $('#field_name').focus();
                e.preventDefault();
                return false;
            }
            
            if (!/^[a-z][a-z0-9_]*$/.test(name)) {
                alert('نام فیلد باید با حرف کوچک انگلیسی شروع شود و فقط شامل حروف کوچک، اعداد و زیرخط باشد');
                $('#field_name').focus();
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    });
    </script>
    <?php
}

function wf_admin_delete_field($field_id) {
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'delete_field_' . $field_id)) {
        wp_die('توکن امنیتی نامعتبر است.');
    }
    
    $result = wf_delete_field($field_id);
    
    if (is_wp_error($result)) {
        wp_redirect(admin_url('admin.php?page=workforce-fields&message=error&error=' . urlencode($result->get_error_message())));
    } else {
        wp_redirect(admin_url('admin.php?page=workforce-fields&message=deleted'));
    }
    
    exit;
}

/**
 * ============================================
 * صفحه مدیریت ادارات
 * ============================================
 */

function wf_admin_departments() {
    // بررسی دسترسی
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
    // دریافت action
    $action = $_GET['action'] ?? 'list';
    $department_id = $_GET['id'] ?? 0;
    
    switch ($action) {
        case 'add':
        case 'edit':
            wf_admin_department_form($department_id, $action);
            break;
        case 'delete':
            wf_admin_delete_department($department_id);
            break;
        default:
            wf_admin_departments_list();
    }
}

function wf_admin_departments_list() {
    // دریافت ادارات
    $departments = wf_get_departments(array(
        'with_manager' => true
    ));
    
    // دریافت پیام‌های عملیات
    $message = '';
    if (isset($_GET['message'])) {
        switch ($_GET['message']) {
            case 'created':
                $message = '<div class="notice notice-success"><p>اداره جدید با موفقیت ایجاد شد.</p></div>';
                break;
            case 'updated':
                $message = '<div class="notice notice-success"><p>اداره با موفقیت به‌روزرسانی شد.</p></div>';
                break;
            case 'deleted':
                $message = '<div class="notice notice-success"><p>اداره با موفقیت حذف شد.</p></div>';
                break;
            case 'error':
                $message = '<div class="notice notice-error"><p>خطا در انجام عملیات.</p></div>';
                break;
        }
    }
    
    ?>
    <div class="wrap wf-admin-wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-building"></span>
            مدیریت ادارات
        </h1>
        
        <a href="<?php echo admin_url('admin.php?page=workforce-departments&action=add'); ?>" 
           class="page-title-action">
            <span class="dashicons dashicons-plus"></span>
            افزودن اداره جدید
        </a>
        
        <hr class="wp-header-end">
        
        <?php echo $message; ?>
        
        <div class="wf-admin-container">
            <form method="post" action="<?php echo admin_url('admin.php?page=workforce-departments'); ?>">
                <?php wp_nonce_field('wf_bulk_action_departments', 'wf_departments_nonce'); ?>
                
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="action" id="bulk-action-selector-top">
                            <option value="-1">عملیات دسته‌ای</option>
                            <option value="activate">فعال‌سازی</option>
                            <option value="archive">آرشیو</option>
                        </select>
                        <button type="submit" class="button action" id="doaction">اعمال</button>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column">
                                <input type="checkbox" id="cb-select-all-1">
                            </td>
                            <th scope="col">نام اداره</th>
                            <th scope="col">کد</th>
                            <th scope="col">مدیر</th>
                            <th scope="col">پرسنل</th>
                            <th scope="col">درصد تکمیل</th>
                            <th scope="col">وضعیت</th>
                            <th scope="col">عملیات</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <?php if (empty($departments)): ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    <p class="wf-no-data">هیچ اداره‌ای ثبت نشده است.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($departments as $dept): ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="department_ids[]" value="<?php echo $dept['id']; ?>">
                                </th>
                                <td>
                                    <strong style="color: <?php echo esc_attr($dept['color']); ?>">■</strong>
                                    <strong><?php echo esc_html($dept['name']); ?></strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo admin_url('admin.php?page=workforce-departments&action=edit&id=' . $dept['id']); ?>">
                                                ویرایش
                                            </a>
                                        </span>
                                        |
                                        <span class="personnel">
                                            <a href="<?php echo admin_url('admin.php?page=workforce-personnel&department=' . $dept['id']); ?>">
                                                مشاهده پرسنل
                                            </a>
                                        </span>
                                        |
                                        <span class="delete">
                                            <a href="<?php echo admin_url('admin.php?page=workforce-departments&action=delete&id=' . $dept['id']); ?>" 
                                               class="submitdelete" 
                                               onclick="return confirm('آیا از حذف این اداره اطمینان دارید؟')">
                                                حذف
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?php echo $dept['code'] ? '<code>' . esc_html($dept['code']) . '</code>' : '---'; ?>
                                </td>
                                <td>
                                    <?php echo $dept['manager_name'] ? esc_html($dept['manager_name']) : '---'; ?>
                                </td>
                                <td>
                                    <?php echo esc_html($dept['personnel_count']); ?>
                                </td>
                                <td>
                                    <div class="wf-completion-bar">
                                        <div class="wf-completion-fill" style="width: <?php echo esc_attr($dept['completion_rate']); ?>%"></div>
                                        <span class="wf-completion-text"><?php echo esc_html($dept['completion_rate']); ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <?php echo wf_get_status_badge(
                                        $dept['status'],
                                        $dept['status'] == 'active' ? 'فعال' : 'غیرفعال'
                                    ); ?>
                                </td>
                                <td>
                                    <div class="wf-action-buttons">
                                        <a href="<?php echo admin_url('admin.php?page=workforce-departments&action=edit&id=' . $dept['id']); ?>" 
                                           class="button button-small">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                        
                                        <a href="<?php echo admin_url('admin.php?page=workforce-personnel&department=' . $dept['id']); ?>" 
                                           class="button button-small">
                                            <span class="dashicons dashicons-groups"></span>
                                        </a>
                                        
                                        <a href="<?php echo admin_url('admin.php?page=workforce-departments&action=delete&id=' . $dept['id']); ?>" 
                                           class="button button-small button-danger"
                                           onclick="return confirm('آیا از حذف این اداره اطمینان دارید؟')">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
    
    <style>
    .wf-completion-bar {
        width: 100px;
        height: 20px;
        background: #e5e7eb;
        border-radius: 10px;
        position: relative;
        overflow: hidden;
    }
    
    .wf-completion-fill {
        height: 100%;
        background: linear-gradient(90deg, #10b981, #34d399);
        border-radius: 10px;
        transition: width 0.3s ease;
    }
    
    .wf-completion-text {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: bold;
        color: #1f2937;
    }
    </style>
    <?php
}

function wf_admin_department_form($department_id = 0, $action = 'add') {
    $department = $department_id ? wf_get_department($department_id) : array();
    $is_edit = ($action == 'edit' && !empty($department));
    
    // دریافت لیست مدیران
    $managers = get_users(array(
        'role__in' => array('administrator', 'editor', 'author'),
        'orderby' => 'display_name',
        'order' => 'ASC'
    ));
    
    // تنظیم مقادیر پیش‌فرض
    $defaults = array(
        'name' => '',
        'code' => '',
        'description' => '',
        'manager_id' => 0,
        'color' => '#3b82f6',
        'parent_id' => 0,
        'phone' => '',
        'email' => '',
        'address' => '',
        'status' => 'active'
    );
    
    $dept_data = wp_parse_args($department ?: array(), $defaults);
    
    ?>
    <div class="wrap wf-admin-wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-building"></span>
            <?php echo $is_edit ? 'ویرایش اداره' : 'افزودن اداره جدید'; ?>
        </h1>
        
        <a href="<?php echo admin_url('admin.php?page=workforce-departments'); ?>" 
           class="page-title-action">
            <span class="dashicons dashicons-arrow-right-alt"></span>
            بازگشت به لیست ادارات
        </a>
        
        <hr class="wp-header-end">
        
        <div class="wf-admin-container">
            <form method="post" action="<?php echo admin_url('admin.php?page=workforce-departments'); ?>" 
                  id="wf-department-form">
                <?php wp_nonce_field('wf_save_department', 'wf_department_nonce'); ?>
                
                <?php if ($is_edit): ?>
                    <input type="hidden" name="department_id" value="<?php echo $department_id; ?>">
                <?php endif; ?>
                
                <input type="hidden" name="action" value="<?php echo $is_edit ? 'edit_department' : 'add_department'; ?>">
                
                <div class="wf-form-sections">
                    <!-- بخش اطلاعات اصلی -->
                    <div class="wf-form-section">
                        <h2>
                            <span class="dashicons dashicons-info"></span>
                            اطلاعات اصلی اداره
                        </h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="department_name">نام اداره <span class="required">*</span></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="department_name" 
                                           name="department_name" 
                                           value="<?php echo esc_attr($dept_data['name']); ?>" 
                                           class="regular-text" 
                                           required>
                                    <p class="description">نام کامل اداره به فارسی</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="department_code">کد اداره</label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="department_code" 
                                           name="department_code" 
                                           value="<?php echo esc_attr($dept_data['code']); ?>" 
                                           class="regular-text">
                                    <p class="description">کد اختصاصی اداره (اختیاری)</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="department_color">رنگ اداره</label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="department_color" 
                                           name="department_color" 
                                           value="<?php echo esc_attr($dept_data['color']); ?>" 
                                           class="color-picker" 
                                           data-default-color="#3b82f6">
                                    <p class="description">رنگ نمایش اداره در نمودارها و جداول</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="department_manager">مدیر اداره</label>
                                </th>
                                <td>
                                    <select id="department_manager" name="department_manager" class="regular-text">
                                        <option value="0">--- انتخاب مدیر ---</option>
                                        <?php foreach ($managers as $manager): ?>
                                            <option value="<?php echo $manager->ID; ?>" 
                                                    <?php selected($dept_data['manager_id'], $manager->ID); ?>>
                                                <?php echo esc_html($manager->display_name); ?> 
                                                (<?php echo esc_html($manager->user_email); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">مدیر این اداره را انتخاب کنید</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="department_parent">اداره مافوق</label>
                                </th>
                                <td>
                                    <select id="department_parent" name="department_parent" class="regular-text">
                                        <option value="0">--- بدون مافوق ---</option>
                                        <?php 
                                        $all_departments = wf_get_departments();
                                        foreach ($all_departments as $dept_item):
                                            if ($is_edit && $dept_item['id'] == $department_id) continue;
                                        ?>
                                            <option value="<?php echo $dept_item['id']; ?>" 
                                                    <?php selected($dept_data['parent_id'], $dept_item['id']); ?>>
                                                <?php echo esc_html($dept_item['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">در صورت وجود ساختار سلسله مراتبی</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="department_status">وضعیت</label>
                                </th>
                                <td>
                                    <select id="department_status" name="department_status" class="regular-text">
                                        <option value="active" <?php selected($dept_data['status'], 'active'); ?>>فعال</option>
                                        <option value="inactive" <?php selected($dept_data['status'], 'inactive'); ?>>غیرفعال</option>
                                    </select>
                                    <p class="description">ادارات غیرفعال در لیست مدیران نمایش داده نمی‌شوند</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="department_description">توضیحات</label>
                                </th>
                                <td>
                                    <textarea id="department_description" 
                                              name="department_description" 
                                              class="large-text" 
                                              rows="4"><?php echo esc_textarea($dept_data['description']); ?></textarea>
                                    <p class="description">توضیحات اضافی درباره اداره</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- بخش اطلاعات تماس -->
                    <div class="wf-form-section">
                        <h2>
                            <span class="dashicons dashicons-phone"></span>
                            اطلاعات تماس
                        </h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="department_phone">تلفن</label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="department_phone" 
                                           name="department_phone" 
                                           value="<?php echo esc_attr($dept_data['phone']); ?>" 
                                           class="regular-text">
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="department_email">ایمیل</label>
                                </th>
                                <td>
                                    <input type="email" 
                                           id="department_email" 
                                           name="department_email" 
                                           value="<?php echo esc_attr($dept_data['email']); ?>" 
                                           class="regular-text">
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="department_address">آدرس</label>
                                </th>
                                <td>
                                    <textarea id="department_address" 
                                              name="department_address" 
                                              class="large-text" 
                                              rows="3"><?php echo esc_textarea($dept_data['address']); ?></textarea>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <?php if ($is_edit): ?>
                    <!-- بخش آمار و اطلاعات -->
                    <div class="wf-form-section">
                        <h2>
                            <span class="dashicons dashicons-chart-bar"></span>
                            آمار و اطلاعات
                        </h2>
                        
                        <div class="wf-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                            <div class="wf-stat-card">
                                <div class="wf-stat-icon">
                                    <span class="dashicons dashicons-groups"></span>
                                </div>
                                <div class="wf-stat-content">
                                    <h3><?php echo esc_html($dept_data['personnel_count'] ?? 0); ?></h3>
                                    <p>تعداد پرسنل</p>
                                </div>
                            </div>
                            
                            <div class="wf-stat-card">
                                <div class="wf-stat-icon">
                                    <span class="dashicons dashicons-yes"></span>
                                </div>
                                <div class="wf-stat-content">
                                    <h3><?php echo esc_html($dept_data['completion_rate'] ?? 0); ?>%</h3>
                                    <p>درصد تکمیل</p>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <a href="<?php echo admin_url('admin.php?page=workforce-personnel&department=' . $department_id); ?>" 
                               class="button button-primary">
                                <span class="dashicons dashicons-groups"></span>
                                مشاهده پرسنل این اداره
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-large">
                        <span class="dashicons dashicons-yes"></span>
                        <?php echo $is_edit ? 'ذخیره تغییرات' : 'ایجاد اداره'; ?>
                    </button>
                    
                    <a href="<?php echo admin_url('admin.php?page=workforce-departments'); ?>" class="button button-large">
                        <span class="dashicons dashicons-no"></span>
                        انصراف
                    </a>
                </p>
            </form>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // فعال کردن color picker
        $('.color-picker').wpColorPicker();
        
        // اعتبارسنجی فرم
        $('#wf-department-form').on('submit', function(e) {
            var name = $('#department_name').val().trim();
            
            if (!name) {
                alert('لطفا نام اداره را وارد کنید');
                $('#department_name').focus();
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    });
    </script>
    <?php
}

function wf_admin_delete_department($department_id) {
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'delete_department_' . $department_id)) {
        wp_die('توکن امنیتی نامعتبر است.');
    }
    
    $result = wf_delete_department($department_id);
    
    if (is_wp_error($result)) {
        wp_redirect(admin_url('admin.php?page=workforce-departments&message=error&error=' . urlencode($result->get_error_message())));
    } else {
        wp_redirect(admin_url('admin.php?page=workforce-departments&message=deleted'));
    }
    
    exit;
}

/**
 * ============================================
 * صفحه مدیریت پرسنل
 * ============================================
 */

function wf_admin_personnel() {
    // بررسی دسترسی
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
    // دریافت action
    $action = $_GET['action'] ?? 'list';
    $personnel_id = $_GET['id'] ?? 0;
    
    switch ($action) {
        case 'add':
        case 'edit':
            wf_admin_personnel_form($personnel_id, $action);
            break;
        case 'view':
            wf_admin_personnel_view($personnel_id);
            break;
        case 'delete':
            wf_admin_delete_personnel($personnel_id);
            break;
        case 'import':
            wf_admin_personnel_import();
            break;
        default:
            wf_admin_personnel_list();
    }
}

function wf_admin_personnel_list() {
    // دریافت پارامترهای فیلتر
    $department_id = $_GET['department'] ?? 0;
    $status = $_GET['status'] ?? 'active';
    $search = $_GET['s'] ?? '';
    $paged = $_GET['paged'] ?? 1;
    
    // دریافت پرسنل
    $params = array(
        'department_id' => $department_id,
        'status' => $status,
        'search' => $search,
        'limit' => 20,
        'offset' => ($paged - 1) * 20,
        'with_department' => true
    );
    
    $personnel = wf_get_all_personnel($params);
    $total_personnel = wf_get_total_personnel_count($params);
    $total_pages = ceil($total_personnel / 20);
    
    // دریافت ادارات برای فیلتر
    $departments = wf_get_departments();
    
    // دریافت پیام‌های عملیات
    $message = '';
    if (isset($_GET['message'])) {
        switch ($_GET['message']) {
            case 'created':
                $message = '<div class="notice notice-success"><p>پرسنل جدید با موفقیت ایجاد شد.</p></div>';
                break;
            case 'updated':
                $message = '<div class="notice notice-success"><p>اطلاعات پرسنل با موفقیت به‌روزرسانی شد.</p></div>';
                break;
            case 'deleted':
                $message = '<div class="notice notice-success"><p>پرسنل با موفقیت حذف شد.</p></div>';
                break;
            case 'imported':
                $message = '<div class="notice notice-success"><p>اطلاعات پرسنل با موفقیت وارد شد.</p></div>';
                break;
            case 'error':
                $message = '<div class="notice notice-error"><p>خطا در انجام عملیات.</p></div>';
                break;
        }
    }
    
    ?>
    <div class="wrap wf-admin-wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-groups"></span>
            مدیریت پرسنل
        </h1>
        
        <a href="<?php echo admin_url('admin.php?page=workforce-personnel&action=add'); ?>" 
           class="page-title-action">
            <span class="dashicons dashicons-plus"></span>
            افزودن پرسنل جدید
        </a>
        
        <a href="<?php echo admin_url('admin.php?page=workforce-personnel&action=import'); ?>" 
           class="page-title-action">
            <span class="dashicons dashicons-upload"></span>
            وارد کردن از Excel
        </a>
        
        <hr class="wp-header-end">
        
        <?php echo $message; ?>
        
        <div class="wf-admin-container">
            <!-- فیلترها -->
            <div class="wf-filters">
                <form method="get" action="<?php echo admin_url('admin.php'); ?>">
                    <input type="hidden" name="page" value="workforce-personnel">
                    
                    <div class="tablenav top">
                        <div class="alignleft actions">
                            <!-- جستجو -->
                            <input type="search" 
                                   name="s" 
                                   value="<?php echo esc_attr($search); ?>" 
                                   placeholder="جستجوی نام، نام خانوادگی، کدملی..."
                                   style="width: 250px;">
                            
                            <!-- فیلتر اداره -->
                            <select name="department">
                                <option value="0">همه ادارات</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" 
                                            <?php selected($department_id, $dept['id']); ?>>
                                        <?php echo esc_html($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <!-- فیلتر وضعیت -->
                            <select name="status">
                                <option value="all" <?php selected($status, 'all'); ?>>همه وضعیت‌ها</option>
                                <option value="active" <?php selected($status, 'active'); ?>>فعال</option>
                                <option value="inactive" <?php selected($status, 'inactive'); ?>>غیرفعال</option>
                                <option value="pending" <?php selected($status, 'pending'); ?>>در انتظار تایید</option>
                                <option value="suspended" <?php selected($status, 'suspended'); ?>>معلق</option>
                            </select>
                            
                            <button type="submit" class="button">اعمال فیلتر</button>
                            
                            <?php if ($search || $department_id || $status != 'all'): ?>
                                <a href="<?php echo admin_url('admin.php?page=workforce-personnel'); ?>" 
                                   class="button">حذف فیلترها</a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="tablenav-pages">
                            <span class="displaying-num"><?php echo $total_personnel; ?> پرسنل</span>
                            
                            <?php if ($total_pages > 1): ?>
                                <span class="pagination-links">
                                    <?php if ($paged > 1): ?>
                                        <a class="first-page button" 
                                           href="<?php echo add_query_arg('paged', 1); ?>">
                                            <span class="screen-reader-text">صفحه اول</span>
                                            <span aria-hidden="true">«</span>
                                        </a>
                                        <a class="prev-page button" 
                                           href="<?php echo add_query_arg('paged', $paged - 1); ?>">
                                            <span class="screen-reader-text">صفحه قبل</span>
                                            <span aria-hidden="true">‹</span>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <span class="screen-reader-text">صفحه فعلی</span>
                                    <span id="table-paging" class="paging-input">
                                        <span class="tablenav-paging-text">
                                            <?php echo $paged; ?> از 
                                            <span class="total-pages"><?php echo $total_pages; ?></span>
                                        </span>
                                    </span>
                                    
                                    <?php if ($paged < $total_pages): ?>
                                        <a class="next-page button" 
                                           href="<?php echo add_query_arg('paged', $paged + 1); ?>">
                                            <span class="screen-reader-text">صفحه بعد</span>
                                            <span aria-hidden="true">›</span>
                                        </a>
                                        <a class="last-page button" 
                                           href="<?php echo add_query_arg('paged', $total_pages); ?>">
                                            <span class="screen-reader-text">صفحه آخر</span>
                                            <span aria-hidden="true">»</span>
                                        </a>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
            
            <form method="post" action="<?php echo admin_url('admin.php?page=workforce-personnel'); ?>">
                <?php wp_nonce_field('wf_bulk_action_personnel', 'wf_personnel_nonce'); ?>
                
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="action" id="bulk-action-selector-top">
                            <option value="-1">عملیات دسته‌ای</option>
                            <option value="activate">فعال‌سازی</option>
                            <option value="deactivate">غیرفعال‌سازی</option>
                            <option value="suspend">معلق کردن</option>
                            <option value="delete">حذف</option>
                        </select>
                        <button type="submit" class="button action" id="doaction">اعمال</button>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column">
                                <input type="checkbox" id="cb-select-all-1">
                            </td>
                            <th scope="col">نام و نام خانوادگی</th>
                            <th scope="col">کدملی</th>
                            <th scope="col">کد پرسنلی</th>
                            <th scope="col">اداره</th>
                            <th scope="col">سمت</th>
                            <th scope="col">حقوق</th>
                            <th scope="col">وضعیت</th>
                            <th scope="col">عملیات</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <?php if (empty($personnel)): ?>
                            <tr>
                                <td colspan="9" class="text-center">
                                    <p class="wf-no-data">هیچ پرسنلی یافت نشد.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($personnel as $person): ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="personnel_ids[]" value="<?php echo $person['id']; ?>">
                                </th>
                                <td>
                                    <strong>
                                        <a href="<?php echo admin_url('admin.php?page=workforce-personnel&action=view&id=' . $person['id']); ?>">
                                            <?php echo esc_html($person['first_name'] . ' ' . $person['last_name']); ?>
                                        </a>
                                    </strong>
                                    <div class="row-actions">
                                        <span class="view">
                                            <a href="<?php echo admin_url('admin.php?page=workforce-personnel&action=view&id=' . $person['id']); ?>">
                                                مشاهده
                                            </a>
                                        </span>
                                        |
                                        <span class="edit">
                                            <a href="<?php echo admin_url('admin.php?page=workforce-personnel&action=edit&id=' . $person['id']); ?>">
                                                ویرایش
                                            </a>
                                        </span>
                                        |
                                        <span class="delete">
                                            <a href="<?php echo admin_url('admin.php?page=workforce-personnel&action=delete&id=' . $person['id']); ?>" 
                                               class="submitdelete" 
                                               onclick="return confirm('آیا از حذف این پرسنل اطمینان دارید؟')">
                                                حذف
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <code><?php echo esc_html($person['national_id']); ?></code>
                                </td>
                                <td>
                                    <?php echo $person['personnel_code'] ? '<code>' . esc_html($person['personnel_code']) . '</code>' : '---'; ?>
                                </td>
                                <td>
                                    <?php if ($person['department_name']): ?>
                                        <span style="color: <?php echo esc_attr($person['department_color']); ?>">■</span>
                                        <?php echo esc_html($person['department_name']); ?>
                                    <?php else: ?>
                                        ---
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo esc_html($person['position'] ?: '---'); ?>
                                </td>
                                <td>
                                    <?php echo $person['salary'] ? wf_format_currency($person['salary']) : '---'; ?>
                                </td>
                                <td>
                                    <?php 
                                    $status_labels = array(
                                        'active' => 'فعال',
                                        'inactive' => 'غیرفعال',
                                        'pending' => 'در انتظار',
                                        'suspended' => 'معلق',
                                        'deleted' => 'حذف شده'
                                    );
                                    echo wf_get_status_badge(
                                        $person['status'],
                                        $status_labels[$person['status']] ?? $person['status']
                                    ); 
                                    ?>
                                </td>
                                <td>
                                    <div class="wf-action-buttons">
                                        <a href="<?php echo admin_url('admin.php?page=workforce-personnel&action=view&id=' . $person['id']); ?>" 
                                           class="button button-small">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </a>
                                        
                                        <a href="<?php echo admin_url('admin.php?page=workforce-personnel&action=edit&id=' . $person['id']); ?>" 
                                           class="button button-small">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                        
                                        <a href="<?php echo admin_url('admin.php?page=workforce-personnel&action=delete&id=' . $person['id']); ?>" 
                                           class="button button-small button-danger"
                                           onclick="return confirm('آیا از حذف این پرسنل اطمینان دارید؟')">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="tablenav bottom">
                    <div class="alignleft actions bulkactions">
                        <select name="action2" id="bulk-action-selector-bottom">
                            <option value="-1">عملیات دسته‌ای</option>
                            <option value="activate">فعال‌سازی</option>
                            <option value="deactivate">غیرفعال‌سازی</option>
                            <option value="suspend">معلق کردن</option>
                            <option value="delete">حذف</option>
                        </select>
                        <button type="submit" class="button action" id="doaction2">اعمال</button>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                        <div class="tablenav-pages">
                            <span class="displaying-num"><?php echo $total_personnel; ?> پرسنل</span>
                            
                            <span class="pagination-links">
                                <?php if ($paged > 1): ?>
                                    <a class="first-page button" 
                                       href="<?php echo add_query_arg('paged', 1); ?>">
                                        <span class="screen-reader-text">صفحه اول</span>
                                        <span aria-hidden="true">«</span>
                                    </a>
                                    <a class="prev-page button" 
                                       href="<?php echo add_query_arg('paged', $paged - 1); ?>">
                                        <span class="screen-reader-text">صفحه قبل</span>
                                        <span aria-hidden="true">‹</span>
                                    </a>
                                <?php endif; ?>
                                
                                <span class="screen-reader-text">صفحه فعلی</span>
                                <span id="table-paging" class="paging-input">
                                    <span class="tablenav-paging-text">
                                        <?php echo $paged; ?> از 
                                        <span class="total-pages"><?php echo $total_pages; ?></span>
                                    </span>
                                </span>
                                
                                <?php if ($paged < $total_pages): ?>
                                    <a class="next-page button" 
                                       href="<?php echo add_query_arg('paged', $paged + 1); ?>">
                                        <span class="screen-reader-text">صفحه بعد</span>
                                        <span aria-hidden="true">›</span>
                                    </a>
                                    <a class="last-page button" 
                                       href="<?php echo add_query_arg('paged', $total_pages); ?>">
                                        <span class="screen-reader-text">صفحه آخر</span>
                                        <span aria-hidden="true">»</span>
                                    </a>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <?php
}

function wf_admin_personnel_form($personnel_id = 0, $action = 'add') {
    $person = $personnel_id ? wf_get_personnel($personnel_id) : array();
    $is_edit = ($action == 'edit' && !empty($person));
    
    // دریافت ادارات
    $departments = wf_get_departments();
    
    // دریافت فیلدهای تعریف شده
    $fields = wf_get_fields();
    
    // تنظیم مقادیر پیش‌فرض
    $defaults = array(
        'national_id' => '',
        'personnel_code' => '',
        'first_name' => '',
        'last_name' => '',
        'father_name' => '',
        'birth_date' => '',
        'birth_city' => '',
        'gender' => 'male',
        'marital_status' => '',
        'education' => '',
        'field_of_study' => '',
        'mobile' => '',
        'phone' => '',
        'email' => '',
        'address' => '',
        'postal_code' => '',
        'department_id' => 0,
        'position' => '',
        'employment_type' => '',
        'employment_date' => '',
        'insurance_no' => '',
        'tax_no' => '',
        'bank_name' => '',
        'bank_account' => '',
        'card_number' => '',
        'salary' => '',
        'benefits' => '',
        'deductions' => '',
        'status' => 'active',
        'notes' => '',
        'custom_fields' => array()
    );
    
    $person_data = wp_parse_args($person ?: array(), $defaults);
    
    ?>
    <div class="wrap wf-admin-wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-groups"></span>
            <?php echo $is_edit ? 'ویرایش اطلاعات پرسنل' : 'افزودن پرسنل جدید'; ?>
        </h1>
        
        <a href="<?php echo admin_url('admin.php?page=workforce-personnel'); ?>" 
           class="page-title-action">
            <span class="dashicons dashicons-arrow-right-alt"></span>
            بازگشت به لیست پرسنل
        </a>
        
        <hr class="wp-header-end">
        
        <div class="wf-admin-container">
            <form method="post" action="<?php echo admin_url('admin.php?page=workforce-personnel'); ?>" 
                  id="wf-personnel-form">
                <?php wp_nonce_field('wf_save_personnel', 'wf_personnel_nonce'); ?>
                
                <?php if ($is_edit): ?>
                    <input type="hidden" name="personnel_id" value="<?php echo $personnel_id; ?>">
                <?php endif; ?>
                
                <input type="hidden" name="action" value="<?php echo $is_edit ? 'edit_personnel' : 'add_personnel'; ?>">
                
                <div class="wf-form-tabs">
                    <ul class="wf-tab-nav">
                        <li class="active"><a href="#tab-basic">اطلاعات پایه</a></li>
                        <li><a href="#tab-contact">اطلاعات تماس</a></li>
                        <li><a href="#tab-employment">اطلاعات استخدام</a></li>
                        <li><a href="#tab-financial">اطلاعات مالی</a></li>
                        <li><a href="#tab-custom">فیلدهای سفارشی</a></li>
                    </ul>
                    
                    <div class="wf-tab-content">
                        <!-- تب اطلاعات پایه -->
                        <div id="tab-basic" class="wf-tab-pane active">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="national_id">کدملی <span class="required">*</span></label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="national_id" 
                                               name="national_id" 
                                               value="<?php echo esc_attr($person_data['national_id']); ?>" 
                                               class="regular-text" 
                                               required 
                                               pattern="\d{10}" 
                                               maxlength="10">
                                        <p class="description">کدملی ۱۰ رقمی</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="personnel_code">کد پرسنلی</label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="personnel_code" 
                                               name="personnel_code" 
                                               value="<?php echo esc_attr($person_data['personnel_code']); ?>" 
                                               class="regular-text">
                                        <p class="description">کد اختصاصی پرسنل در سازمان</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="first_name">نام <span class="required">*</span></label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="first_name" 
                                               name="first_name" 
                                               value="<?php echo esc_attr($person_data['first_name']); ?>" 
                                               class="regular-text" 
                                               required>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="last_name">نام خانوادگی <span class="required">*</span></label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="last_name" 
                                               name="last_name" 
                                               value="<?php echo esc_attr($person_data['last_name']); ?>" 
                                               class="regular-text" 
                                               required>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="father_name">نام پدر</label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="father_name" 
                                               name="father_name" 
                                               value="<?php echo esc_attr($person_data['father_name']); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="birth_date">تاریخ تولد</label>
                                    </th>
                                    <td>
                                        <input type="date" 
                                               id="birth_date" 
                                               name="birth_date" 
                                               value="<?php echo esc_attr($person_data['birth_date']); ?>" 
                                               class="regular-text">
                                        <p class="description">فرت: YYYY-MM-DD</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="birth_city">محل تولد</label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="birth_city" 
                                               name="birth_city" 
                                               value="<?php echo esc_attr($person_data['birth_city']); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label>جنسیت</label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="radio" 
                                                   name="gender" 
                                                   value="male" 
                                                   <?php checked($person_data['gender'], 'male'); ?>>
                                            مرد
                                        </label>
                                        <label style="margin-right: 20px;">
                                            <input type="radio" 
                                                   name="gender" 
                                                   value="female" 
                                                   <?php checked($person_data['gender'], 'female'); ?>>
                                            زن
                                        </label>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="marital_status">وضعیت تأهل</label>
                                    </th>
                                    <td>
                                        <select id="marital_status" name="marital_status" class="regular-text">
                                            <option value="">--- انتخاب کنید ---</option>
                                            <option value="single" <?php selected($person_data['marital_status'], 'single'); ?>>مجرد</option>
                                            <option value="married" <?php selected($person_data['marital_status'], 'married'); ?>>متأهل</option>
                                            <option value="divorced" <?php selected($person_data['marital_status'], 'divorced'); ?>>مطلقه</option>
                                            <option value="widowed" <?php selected($person_data['marital_status'], 'widowed'); ?>>همسر فوت شده</option>
                                        </select>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="education">تحصیلات</label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="education" 
                                               name="education" 
                                               value="<?php echo esc_attr($person_data['education']); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="field_of_study">رشته تحصیلی</label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="field_of_study" 
                                               name="field_of_study" 
                                               value="<?php echo esc_attr($person_data['field_of_study']); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- تب اطلاعات تماس -->
                        <div id="tab-contact" class="wf-tab-pane">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="mobile">تلفن همراه</label>
                                    </th>
                                    <td>
                                        <input type="tel" 
                                               id="mobile" 
                                               name="mobile" 
                                               value="<?php echo esc_attr($person_data['mobile']); ?>" 
                                               class="regular-text" 
                                               pattern="09[0-9]{9}" 
                                               maxlength="11">
                                        <p class="description">شماره موبایل ۱۱ رقمی (با ۰۹ شروع شود)</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="phone">تلفن ثابت</label>
                                    </th>
                                    <td>
                                        <input type="tel" 
                                               id="phone" 
                                               name="phone" 
                                               value="<?php echo esc_attr($person_data['phone']); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="email">ایمیل</label>
                                    </th>
                                    <td>
                                        <input type="email" 
                                               id="email" 
                                               name="email" 
                                               value="<?php echo esc_attr($person_data['email']); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="address">آدرس</label>
                                    </th>
                                    <td>
                                        <textarea id="address" 
                                                  name="address" 
                                                  class="large-text" 
                                                  rows="3"><?php echo esc_textarea($person_data['address']); ?></textarea>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="postal_code">کد پستی</label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="postal_code" 
                                               name="postal_code" 
                                               value="<?php echo esc_attr($person_data['postal_code']); ?>" 
                                               class="regular-text" 
                                               pattern="\d{10}" 
                                               maxlength="10">
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- تب اطلاعات استخدام -->
                        <div id="tab-employment" class="wf-tab-pane">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="department_id">اداره <span class="required">*</span></label>
                                    </th>
                                    <td>
                                        <select id="department_id" name="department_id" class="regular-text" required>
                                            <option value="">--- انتخاب اداره ---</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?php echo $dept['id']; ?>" 
                                                        <?php selected($person_data['department_id'], $dept['id']); ?>>
                                                    <?php echo esc_html($dept['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="position">سمت</label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="position" 
                                               name="position" 
                                               value="<?php echo esc_attr($person_data['position']); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="employment_type">نوع استخدام</label>
                                    </th>
                                    <td>
                                        <select id="employment_type" name="employment_type" class="regular-text">
                                            <option value="">--- انتخاب کنید ---</option>
                                            <option value="permanent" <?php selected($person_data['employment_type'], 'permanent'); ?>>دائم</option>
                                            <option value="contractual" <?php selected($person_data['employment_type'], 'contractual'); ?>>قراردادی</option>
                                            <option value="temporary" <?php selected($person_data['employment_type'], 'temporary'); ?>>موقت</option>
                                            <option value="project" <?php selected($person_data['employment_type'], 'project'); ?>>پروژه‌ای</option>
                                        </select>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="employment_date">تاریخ استخدام</label>
                                    </th>
                                    <td>
                                        <input type="date" 
                                               id="employment_date" 
                                               name="employment_date" 
                                               value="<?php echo esc_attr($person_data['employment_date']); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="insurance_no">شماره بیمه</label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="insurance_no" 
                                               name="insurance_no" 
                                               value="<?php echo esc_attr($person_data['insurance_no']); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="tax_no">شماره مالیاتی</label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="tax_no" 
                                               name="tax_no" 
                                               value="<?php echo esc_attr($person_data['tax_no']); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="status">وضعیت پرسنل</label>
                                    </th>
                                    <td>
                                        <select id="status" name="status" class="regular-text">
                                            <option value="active" <?php selected($person_data['status'], 'active'); ?>>فعال</option>
                                            <option value="inactive" <?php selected($person_data['status'], 'inactive'); ?>>غیرفعال</option>
                                            <option value="pending" <?php selected($person_data['status'], 'pending'); ?>>در انتظار تایید</option>
                                            <option value="suspended" <?php selected($person_data['status'], 'suspended'); ?>>معلق</option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- تب اطلاعات مالی -->
                        <div id="tab-financial" class="wf-tab-pane">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="bank_name">نام بانک</label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="bank_name" 
                                               name="bank_name" 
                                               value="<?php echo esc_attr($person_data['bank_name']); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="bank_account">شماره حساب</label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="bank_account" 
                                               name="bank_account" 
                                               value="<?php echo esc_attr($person_data['bank_account']); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="card_number">شماره کارت</label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="card_number" 
                                               name="card_number" 
                                               value="<?php echo esc_attr($person_data['card_number']); ?>" 
                                               class="regular-text" 
                                               pattern="\d{16}" 
                                               maxlength="16">
                                        <p class="description">۱۶ رقم شماره کارت بانکی</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="salary">حقوق پایه</label>
                                    </th>
                                    <td>
                                        <input type="number" 
                                               id="salary" 
                                               name="salary" 
                                               value="<?php echo esc_attr($person_data['salary']); ?>" 
                                               class="regular-text" 
                                               min="0" 
                                               step="1000">
                                        <p class="description">ریال</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="benefits">مزایا</label>
                                    </th>
                                    <td>
                                        <input type="number" 
                                               id="benefits" 
                                               name="benefits" 
                                               value="<?php echo esc_attr($person_data['benefits']); ?>" 
                                               class="regular-text" 
                                               min="0" 
                                               step="1000">
                                        <p class="description">ریال</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="deductions">کسورات</label>
                                    </th>
                                    <td>
                                        <input type="number" 
                                               id="deductions" 
                                               name="deductions" 
                                               value="<?php echo esc_attr($person_data['deductions']); ?>" 
                                               class="regular-text" 
                                               min="0" 
                                               step="1000">
                                        <p class="description">ریال</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label>حقوق خالص</label>
                                    </th>
                                    <td>
                                        <strong id="net-salary-display">
                                            <?php 
                                            $net_salary = ($person_data['salary'] ?: 0) + 
                                                         ($person_data['benefits'] ?: 0) - 
                                                         ($person_data['deductions'] ?: 0);
                                            echo wf_format_currency($net_salary);
                                            ?>
                                        </strong>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- تب فیلدهای سفارشی -->
                        <div id="tab-custom" class="wf-tab-pane">
                            <table class="form-table">
                                <?php 
                                $custom_fields = $person_data['custom_fields'] ?: array();
                                
                                foreach ($fields as $field):
                                    if (in_array($field['type'], array('text', 'number', 'decimal', 'date', 'select', 'checkbox'))):
                                        $field_value = $custom_fields[$field['name']] ?? '';
                                ?>
                                <tr>
                                    <th scope="row">
                                        <label for="custom_<?php echo esc_attr($field['name']); ?>">
                                            <?php echo esc_html($field['title']); ?>
                                            <?php if ($field['is_required']): ?>
                                                <span class="required">*</span>
                                            <?php endif; ?>
                                        </label>
                                    </th>
                                    <td>
                                        <?php if ($field['type'] == 'select'): ?>
                                            <select id="custom_<?php echo esc_attr($field['name']); ?>" 
                                                    name="custom_fields[<?php echo esc_attr($field['name']); ?>]" 
                                                    class="regular-text"
                                                    <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                                <option value="">--- انتخاب کنید ---</option>
                                                <?php 
                                                $options = $field['options'] ?: array();
                                                foreach ($options as $option):
                                                    $opt_value = $option['value'] ?? $option['label'] ?? '';
                                                    $opt_label = $option['label'] ?? $opt_value;
                                                ?>
                                                    <option value="<?php echo esc_attr($opt_value); ?>" 
                                                            <?php selected($field_value, $opt_value); ?>>
                                                        <?php echo esc_html($opt_label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            
                                        <?php elseif ($field['type'] == 'checkbox'): ?>
                                            <label>
                                                <input type="checkbox" 
                                                       id="custom_<?php echo esc_attr($field['name']); ?>" 
                                                       name="custom_fields[<?php echo esc_attr($field['name']); ?>]" 
                                                       value="1" 
                                                       <?php checked($field_value, '1'); ?>>
                                                <?php echo esc_html($field['title']); ?>
                                            </label>
                                            
                                        <?php elseif ($field['type'] == 'date'): ?>
                                            <input type="date" 
                                                   id="custom_<?php echo esc_attr($field['name']); ?>" 
                                                   name="custom_fields[<?php echo esc_attr($field['name']); ?>]" 
                                                   value="<?php echo esc_attr($field_value); ?>" 
                                                   class="regular-text"
                                                   <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                                   
                                        <?php elseif (in_array($field['type'], array('number', 'decimal'))): ?>
                                            <input type="number" 
                                                   id="custom_<?php echo esc_attr($field['name']); ?>" 
                                                   name="custom_fields[<?php echo esc_attr($field['name']); ?>]" 
                                                   value="<?php echo esc_attr($field_value); ?>" 
                                                   class="regular-text"
                                                   <?php echo $field['is_required'] ? 'required' : ''; ?>
                                                   step="<?php echo $field['type'] == 'decimal' ? '0.01' : '1'; ?>">
                                                   
                                        <?php else: // text ?>
                                            <input type="text" 
                                                   id="custom_<?php echo esc_attr($field['name']); ?>" 
                                                   name="custom_fields[<?php echo esc_attr($field['name']); ?>]" 
                                                   value="<?php echo esc_attr($field_value); ?>" 
                                                   class="regular-text"
                                                   <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                        <?php endif; ?>
                                        
                                        <?php if ($field['help_text']): ?>
                                            <p class="description"><?php echo esc_html($field['help_text']); ?></p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- بخش یادداشت‌ها -->
                <div class="wf-form-section">
                    <h2>
                        <span class="dashicons dashicons-edit"></span>
                        یادداشت‌ها
                    </h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="notes">یادداشت‌های اضافی</label>
                            </th>
                            <td>
                                <textarea id="notes" 
                                          name="notes" 
                                          class="large-text" 
                                          rows="5"><?php echo esc_textarea($person_data['notes']); ?></textarea>
                                <p class="description">یادداشت‌های اضافی درباره این پرسنل</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-large">
                        <span class="dashicons dashicons-yes"></span>
                        <?php echo $is_edit ? 'ذخیره تغییرات' : 'ایجاد پرسنل'; ?>
                    </button>
                    
                    <a href="<?php echo admin_url('admin.php?page=workforce-personnel'); ?>" class="button button-large">
                        <span class="dashicons dashicons-no"></span>
                        انصراف
                    </a>
                </p>
            </form>
        </div>
    </div>
    
    <style>
    .wf-form-tabs {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .wf-tab-nav {
        display: flex;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    
    .wf-tab-nav li {
        margin: 0;
    }
    
    .wf-tab-nav li a {
        display: block;
        padding: 15px 20px;
        text-decoration: none;
        color: #6b7280;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
    }
    
    .wf-tab-nav li.active a {
        color: #3b82f6;
        border-bottom-color: #3b82f6;
        background: white;
    }
    
    .wf-tab-nav li a:hover {
        color: #1d4ed8;
        background: #f1f5f9;
    }
    
    .wf-tab-content {
        padding: 20px;
    }
    
    .wf-tab-pane {
        display: none;
    }
    
    .wf-tab-pane.active {
        display: block;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // مدیریت تب‌ها
        $('.wf-tab-nav a').on('click', function(e) {
            e.preventDefault();
            
            var tabId = $(this).attr('href');
            
            // غیرفعال کردن همه تب‌ها
            $('.wf-tab-nav li').removeClass('active');
            $('.wf-tab-pane').removeClass('active');
            
            // فعال کردن تب انتخاب شده
            $(this).parent().addClass('active');
            $(tabId).addClass('active');
        });
        
        // محاسبه حقوق خالص
        function calculateNetSalary() {
            var salary = parseFloat($('#salary').val()) || 0;
            var benefits = parseFloat($('#benefits').val()) || 0;
            var deductions = parseFloat($('#deductions').val()) || 0;
            
            var netSalary = salary + benefits - deductions;
            
            $('#net-salary-display').text(
                netSalary.toLocaleString('fa-IR') + ' ریال'
            );
        }
        
        $('#salary, #benefits, #deductions').on('input', calculateNetSalary);
        
        // اعتبارسنجی فرم
        $('#wf-personnel-form').on('submit', function(e) {
            var nationalId = $('#national_id').val().trim();
            var firstName = $('#first_name').val().trim();
            var lastName = $('#last_name').val().trim();
            var departmentId = $('#department_id').val();
            
            // اعتبارسنجی کدملی
            if (!nationalId || !/^\d{10}$/.test(nationalId)) {
                alert('لطفا کدملی ۱۰ رقمی معتبر وارد کنید');
                $('#national_id').focus();
                e.preventDefault();
                return false;
            }
            
            // اعتبارسنجی نام
            if (!firstName) {
                alert('لطفا نام را وارد کنید');
                $('#first_name').focus();
                e.preventDefault();
                return false;
            }
            
            if (!lastName) {
                alert('لطفا نام خانوادگی را وارد کنید');
                $('#last_name').focus();
                e.preventDefault();
                return false;
            }
            
            // اعتبارسنجی اداره
            if (!departmentId) {
                alert('لطفا اداره را انتخاب کنید');
                $('#department_id').focus();
                e.preventDefault();
                return false;
            }
            
            // اعتبارسنجی موبایل (اگر وارد شده)
            var mobile = $('#mobile').val().trim();
            if (mobile && !/^09\d{9}$/.test(mobile)) {
                alert('لطفا شماره موبایل معتبر وارد کنید');
                $('#mobile').focus();
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    });
    </script>
    <?php
}

function wf_admin_personnel_view($personnel_id) {
    $person = wf_get_personnel($personnel_id);
    
    if (!$person) {
        wp_die('پرسنل مورد نظر یافت نشد.');
    }
    
    ?>
    <div class="wrap wf-admin-wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-visibility"></span>
            مشاهده اطلاعات پرسنل
        </h1>
        
        <a href="<?php echo admin_url('admin.php?page=workforce-personnel'); ?>" 
           class="page-title-action">
            <span class="dashicons dashicons-arrow-right-alt"></span>
            بازگشت به لیست پرسنل
        </a>
        
        <a href="<?php echo admin_url('admin.php?page=workforce-personnel&action=edit&id=' . $personnel_id); ?>" 
           class="page-title-action">
            <span class="dashicons dashicons-edit"></span>
            ویرایش
        </a>
        
        <hr class="wp-header-end">
        
        <div class="wf-admin-container">
            <div class="wf-personnel-profile">
                <!-- هدر پروفایل -->
                <div class="wf-profile-header">
                    <div class="wf-profile-avatar">
                        <span class="dashicons dashicons-admin-users"></span>
                    </div>
                    
                    <div class="wf-profile-info">
                        <h2><?php echo esc_html($person['first_name'] . ' ' . $person['last_name']); ?></h2>
                        <p class="wf-profile-meta">
                            <span>کدملی: <code><?php echo esc_html($person['national_id']); ?></code></span>
                            <span>کد پرسنلی: <?php echo $person['personnel_code'] ? '<code>' . esc_html($person['personnel_code']) . '</code>' : '---'; ?></span>
                            <span>اداره: 
                                <span style="color: <?php echo esc_attr($person['department_color']); ?>">■</span>
                                <?php echo esc_html($person['department_name']); ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="wf-profile-status">
                        <?php 
                        $status_labels = array(
                            'active' => 'فعال',
                            'inactive' => 'غیرفعال',
                            'pending' => 'در انتظار',
                            'suspended' => 'معلق',
                            'deleted' => 'حذف شده'
                        );
                        echo wf_get_status_badge(
                            $person['status'],
                            $status_labels[$person['status']] ?? $person['status']
                        ); 
                        ?>
                    </div>
                </div>
                
                <!-- اطلاعات پرسنل -->
                <div class="wf-profile-sections">
                    <!-- اطلاعات شخصی -->
                    <div class="wf-profile-section">
                        <h3>
                            <span class="dashicons dashicons-id"></span>
                            اطلاعات شخصی
                        </h3>
                        
                        <div class="wf-info-grid">
                            <div class="wf-info-item">
                                <span class="wf-info-label">نام پدر:</span>
                                <span class="wf-info-value"><?php echo esc_html($person['father_name'] ?: '---'); ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">تاریخ تولد:</span>
                                <span class="wf-info-value"><?php echo $person['birth_date'] ? wf_gregorian_to_persian($person['birth_date']) . ' (' . $person['age'] . ' سال)' : '---'; ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">محل تولد:</span>
                                <span class="wf-info-value"><?php echo esc_html($person['birth_city'] ?: '---'); ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">جنسیت:</span>
                                <span class="wf-info-value"><?php echo $person['gender'] == 'male' ? 'مرد' : 'زن'; ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">وضعیت تأهل:</span>
                                <span class="wf-info-value">
                                    <?php 
                                    $marital_statuses = array(
                                        'single' => 'مجرد',
                                        'married' => 'متأهل',
                                        'divorced' => 'مطلقه',
                                        'widowed' => 'همسر فوت شده'
                                    );
                                    echo $marital_statuses[$person['marital_status']] ?? '---';
                                    ?>
                                </span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">تحصیلات:</span>
                                <span class="wf-info-value"><?php echo esc_html($person['education'] ?: '---'); ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">رشته تحصیلی:</span>
                                <span class="wf-info-value"><?php echo esc_html($person['field_of_study'] ?: '---'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- اطلاعات تماس -->
                    <div class="wf-profile-section">
                        <h3>
                            <span class="dashicons dashicons-phone"></span>
                            اطلاعات تماس
                        </h3>
                        
                        <div class="wf-info-grid">
                            <div class="wf-info-item">
                                <span class="wf-info-label">تلفن همراه:</span>
                                <span class="wf-info-value"><?php echo esc_html($person['mobile'] ?: '---'); ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">تلفن ثابت:</span>
                                <span class="wf-info-value"><?php echo esc_html($person['phone'] ?: '---'); ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">ایمیل:</span>
                                <span class="wf-info-value"><?php echo esc_html($person['email'] ?: '---'); ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">آدرس:</span>
                                <span class="wf-info-value"><?php echo esc_html($person['address'] ?: '---'); ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">کد پستی:</span>
                                <span class="wf-info-value"><?php echo esc_html($person['postal_code'] ?: '---'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- اطلاعات استخدام -->
                    <div class="wf-profile-section">
                        <h3>
                            <span class="dashicons dashicons-businessperson"></span>
                            اطلاعات استخدام
                        </h3>
                        
                        <div class="wf-info-grid">
                            <div class="wf-info-item">
                                <span class="wf-info-label">سمت:</span>
                                <span class="wf-info-value"><?php echo esc_html($person['position'] ?: '---'); ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">نوع استخدام:</span>
                                <span class="wf-info-value">
                                    <?php 
                                    $employment_types = array(
                                        'permanent' => 'دائم',
                                        'contractual' => 'قراردادی',
                                        'temporary' => 'موقت',
                                        'project' => 'پروژه‌ای'
                                    );
                                    echo $employment_types[$person['employment_type']] ?? '---';
                                    ?>
                                </span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">تاریخ استخدام:</span>
                                <span class="wf-info-value">
                                    <?php 
                                    if ($person['employment_date']) {
                                        echo wf_gregorian_to_persian($person['employment_date']) . ' (' . $person['employment_years'] . ')';
                                    } else {
                                        echo '---';
                                    }
                                    ?>
                                </span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">سابقه کار:</span>
                                <span class="wf-info-value"><?php echo esc_html($person['employment_years'] ?: '---'); ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">شماره بیمه:</span>
                                <span class="wf-info-value"><?php echo esc_html($person['insurance_no'] ?: '---'); ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">شماره مالیاتی:</span>
                                <span class="wf-info-value"><?php echo esc_html($person['tax_no'] ?: '---'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- اطلاعات مالی -->
                    <div class="wf-profile-section">
                        <h3>
                            <span class="dashicons dashicons-money"></span>
                            اطلاعات مالی
                        </h3>
                        
                        <div class="wf-info-grid">
                            <div class="wf-info-item">
                                <span class="wf-info-label">نام بانک:</span>
                                <span class="wf-info-value"><?php echo esc_html($person['bank_name'] ?: '---'); ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">شماره حساب:</span>
                                <span class="wf-info-value"><?php echo esc_html($person['bank_account'] ?: '---'); ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">شماره کارت:</span>
                                <span class="wf-info-value"><?php echo esc_html($person['card_number'] ?: '---'); ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">حقوق پایه:</span>
                                <span class="wf-info-value"><?php echo $person['salary'] ? wf_format_currency($person['salary']) : '---'; ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">مزایا:</span>
                                <span class="wf-info-value"><?php echo $person['benefits'] ? wf_format_currency($person['benefits']) : '---'; ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">کسورات:</span>
                                <span class="wf-info-value"><?php echo $person['deductions'] ? wf_format_currency($person['deductions']) : '---'; ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">حقوق خالص:</span>
                                <span class="wf-info-value">
                                    <strong><?php echo wf_format_currency($person['net_salary'] ?: 0); ?></strong>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- فیلدهای سفارشی -->
                    <?php if (!empty($person['custom_fields'])): ?>
                    <div class="wf-profile-section">
                        <h3>
                            <span class="dashicons dashicons-list-view"></span>
                            فیلدهای سفارشی
                        </h3>
                        
                        <div class="wf-info-grid">
                            <?php 
                            $fields = wf_get_fields();
                            foreach ($fields as $field):
                                $value = $person['custom_fields'][$field['name']] ?? '';
                                if (!empty($value)):
                            ?>
                            <div class="wf-info-item">
                                <span class="wf-info-label"><?php echo esc_html($field['title']); ?>:</span>
                                <span class="wf-info-value">
                                    <?php 
                                    if ($field['type'] == 'checkbox') {
                                        echo $value ? '✅' : '❌';
                                    } else {
                                        echo esc_html($value);
                                    }
                                    ?>
                                </span>
                            </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- یادداشت‌ها -->
                    <?php if (!empty($person['notes'])): ?>
                    <div class="wf-profile-section">
                        <h3>
                            <span class="dashicons dashicons-edit"></span>
                            یادداشت‌ها
                        </h3>
                        
                        <div class="wf-notes-box">
                            <?php echo nl2br(esc_html($person['notes'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- اطلاعات سیستمی -->
                    <div class="wf-profile-section">
                        <h3>
                            <span class="dashicons dashicons-info"></span>
                            اطلاعات سیستمی
                        </h3>
                        
                        <div class="wf-info-grid">
                            <div class="wf-info-item">
                                <span class="wf-info-label">ایجاد شده توسط:</span>
                                <span class="wf-info-value"><?php echo esc_html($person['creator_name'] ?: 'سیستم'); ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">تاریخ ایجاد:</span>
                                <span class="wf-info-value"><?php echo wf_format_persian_datetime($person['created_at']); ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">آخرین ویرایش:</span>
                                <span class="wf-info-value"><?php echo wf_format_persian_datetime($person['updated_at']); ?></span>
                            </div>
                            
                            <?php if ($person['verified_by']): ?>
                            <div class="wf-info-item">
                                <span class="wf-info-label">تایید شده توسط:</span>
                                <span class="wf-info-value"><?php echo esc_html($person['verifier_name']); ?></span>
                            </div>
                            
                            <div class="wf-info-item">
                                <span class="wf-info-label">تاریخ تایید:</span>
                                <span class="wf-info-value"><?php echo wf_format_persian_datetime($person['verified_at']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .wf-personnel-profile {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .wf-profile-header {
        background: linear-gradient(90deg, #3b82f6, #1d4ed8);
        color: white;
        padding: 30px;
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .wf-profile-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .wf-profile-avatar .dashicons {
        font-size: 40px;
        width: 40px;
        height: 40px;
    }
    
    .wf-profile-info h2 {
        margin: 0 0 10px 0;
        font-size: 24px;
    }
    
    .wf-profile-meta {
        margin: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        opacity: 0.9;
    }
    
    .wf-profile-status {
        margin-right: auto;
    }
    
    .wf-profile-sections {
        padding: 30px;
    }
    
    .wf-profile-section {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .wf-profile-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .wf-profile-section h3 {
        color: #374151;
        font-size: 18px;
        margin-top: 0;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .wf-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 15px;
    }
    
    .wf-info-item {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .wf-info-item:last-child {
        border-bottom: none;
    }
    
    .wf-info-label {
        color: #6b7280;
        font-weight: 500;
    }
    
    .wf-info-value {
        color: #1f2937;
        text-align: left;
    }
    
    .wf-notes-box {
        background: #f8fafc;
        border-radius: 8px;
        padding: 20px;
        line-height: 1.6;
    }
    </style>
    <?php
}

function wf_admin_personnel_import() {
    ?>
    <div class="wrap wf-admin-wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-upload"></span>
            وارد کردن اطلاعات پرسنل از Excel
        </h1>
        
        <a href="<?php echo admin_url('admin.php?page=workforce-personnel'); ?>" 
           class="page-title-action">
            <span class="dashicons dashicons-arrow-right-alt"></span>
            بازگشت به لیست پرسنل
        </a>
        
        <hr class="wp-header-end">
        
        <div class="wf-admin-container">
            <div class="card" style="max-width: 800px;">
                <h2>مراحل وارد کردن اطلاعات</h2>
                
                <div class="wf-import-steps">
                    <div class="wf-import-step active">
                        <div class="wf-step-number">۱</div>
                        <div class="wf-step-content">
                            <h3>آماده‌سازی فایل Excel</h3>
                            <p>فایل Excel خود را مطابق با قالب زیر آماده کنید:</p>
                            <ul>
                                <li>ستون اول باید <strong>کدملی</strong> باشد</li>
                                <li>ستون دوم باید <strong>نام</strong> باشد</li>
                                <li>ستون سوم باید <strong>نام خانوادگی</strong> باشد</li>
                                <li>ستون چهارم باید <strong>کد اداره</strong> باشد</li>
                                <li>می‌توانید سایر فیلدها را نیز اضافه کنید</li>
                            </ul>
                            <p>
                                <a href="<?php echo WF_PLUGIN_URL . 'templates/personnel-import-template.xlsx'; ?>" 
                                   class="button button-primary">
                                    <span class="dashicons dashicons-download"></span>
                                    دانلود قالب Excel
                                </a>
                            </p>
                        </div>
                    </div>
                    
                    <div class="wf-import-step">
                        <div class="wf-step-number">۲</div>
                        <div class="wf-step-content">
                            <h3>آپلود فایل</h3>
                            <p>فایل Excel آماده شده را آپلود کنید:</p>
                            
                            <form method="post" enctype="multipart/form-data" 
                                  action="<?php echo admin_url('admin.php?page=workforce-personnel'); ?>">
                                <?php wp_nonce_field('wf_import_personnel', 'wf_import_nonce'); ?>
                                <input type="hidden" name="action" value="import_personnel">
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="excel_file">فایل Excel</label>
                                        </th>
                                        <td>
                                            <input type="file" 
                                                   id="excel_file" 
                                                   name="excel_file" 
                                                   accept=".xlsx,.xls" 
                                                   required>
                                            <p class="description">فقط فایل‌های Excel با فرمت .xlsx یا .xls قابل قبول است</p>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row">
                                            <label for="import_mode">حالت وارد کردن</label>
                                        </th>
                                        <td>
                                            <select id="import_mode" name="import_mode" class="regular-text">
                                                <option value="add_only">فقط اضافه کردن رکوردهای جدید</option>
                                                <option value="update_existing">به‌روزرسانی رکوردهای موجود</option>
                                                <option value="replace_all">حذف همه و وارد کردن جدید</option>
                                            </select>
                                            <p class="description">نحوه برخورد با اطلاعات موجود را انتخاب کنید</p>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row">
                                            <label for="send_notifications">ارسال اعلان</label>
                                        </th>
                                        <td>
                                            <label>
                                                <input type="checkbox" 
                                                       id="send_notifications" 
                                                       name="send_notifications" 
                                                       value="1">
                                                ارسال اعلان به مدیران ادارات
                                            </label>
                                            <p class="description">در صورت انتخاب، پس از وارد کردن اطلاعات، اعلان‌هایی برای مدیران ارسال می‌شود</p>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p class="submit">
                                    <button type="submit" class="button button-primary button-large">
                                        <span class="dashicons dashicons-upload"></span>
                                        شروع وارد کردن اطلاعات
                                    </button>
                                </p>
                            </form>
                        </div>
                    </div>
                    
                    <div class="wf-import-step">
                        <div class="wf-step-number">۳</div>
                        <div class="wf-step-content">
                            <h3>تطبیق ستون‌ها</h3>
                            <p>پس از آپلود فایل، ستون‌های فایل Excel را با فیلدهای سیستم تطبیق دهید.</p>
                            <p>سیستم به طور خودکار ستون‌ها را تشخیص می‌دهد، اما می‌توانید آنها را اصلاح کنید.</p>
                        </div>
                    </div>
                    
                    <div class="wf-import-step">
                        <div class="wf-step-number">۴</div>
                        <div class="wf-step-content">
                            <h3>تأیید و وارد کردن</h3>
                            <p>اطلاعات را بررسی و تأیید کنید، سپس عملیات وارد کردن را آغاز کنید.</p>
                            <p>پس از اتمام عملیات، گزارش کامل را مشاهده خواهید کرد.</p>
                        </div>
                    </div>
                </div>
                
                <div class="wf-import-notice">
                    <h3>
                        <span class="dashicons dashicons-info"></span>
                        نکات مهم
                    </h3>
                    <ul>
                        <li>حداکثر حجم فایل: 10 مگابایت</li>
                        <li>حداکثر تعداد رکورد در هر بار وارد کردن: 1000 رکورد</li>
                        <li>اطلاعات وارد شده بلافاصله در سیستم ثبت می‌شوند</li>
                        <li>قبل از وارد کردن اطلاعات، از داده‌های فعلی پشتیبان بگیرید</li>
                        <li>اطلاعات با کدملی تکراری به‌روزرسانی می‌شوند (در صورتی که حالت به‌روزرسانی انتخاب شده باشد)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .wf-import-steps {
        margin: 30px 0;
    }
    
    .wf-import-step {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
        padding-bottom: 30px;
        border-bottom: 1px dashed #e5e7eb;
    }
    
    .wf-import-step:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .wf-step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e5e7eb;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 18px;
        flex-shrink: 0;
    }
    
    .wf-import-step.active .wf-step-number {
        background: #3b82f6;
        color: white;
    }
    
    .wf-step-content {
        flex: 1;
    }
    
    .wf-step-content h3 {
        margin-top: 0;
        color: #374151;
    }
    
    .wf-import-notice {
        background: #f0f9ff;
        border: 1px solid #0ea5e9;
        border-radius: 8px;
        padding: 20px;
        margin-top: 30px;
    }
    
    .wf-import-notice h3 {
        color: #0369a1;
        margin-top: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .wf-import-notice ul {
        margin: 15px 0 0 20px;
    }
    
    .wf-import-notice li {
        margin-bottom: 8px;
    }
    </style>
    <?php
}

/**
 * ============================================
 * سایر صفحات مدیریت
 * ============================================
 */

// توابع دیگر صفحات (excel-templates, approvals, periods, reports, settings, tools)
// به دلیل محدودیت طول پاسخ، این توابع در ادامه پیاده‌سازی می‌شوند

/**
 * صفحه قالب‌های اکسل
 */
function wf_admin_excel_templates() {
    // پیاده‌سازی صفحه قالب‌های اکسل
    echo '<div class="wrap"><h1>قالب گزارش اکسل</h1><p>این صفحه به زودی پیاده‌سازی می‌شود.</p></div>';
}

/**
 * صفحه تایید درخواست‌ها
 */
function wf_admin_approvals() {
    // پیاده‌سازی صفحه تایید درخواست‌ها
    echo '<div class="wrap"><h1>تایید درخواست‌ها</h1><p>این صفحه به زودی پیاده‌سازی می‌شود.</p></div>';
}

/**
 * صفحه مدیریت دوره‌ها
 */
function wf_admin_periods() {
    // پیاده‌سازی صفحه مدیریت دوره‌ها
    echo '<div class="wrap"><h1>مدیریت دوره‌ها</h1><p>این صفحه به زودی پیاده‌سازی می‌شود.</p></div>';
}

/**
 * صفحه گزارش‌ها
 */
function wf_admin_reports() {
    // پیاده‌سازی صفحه گزارش‌ها
    echo '<div class="wrap"><h1>گزارش‌ها</h1><p>این صفحه به زودی پیاده‌سازی می‌شود.</p></div>';
}

/**
 * صفحه تنظیمات
 */
function wf_admin_settings() {
    // پیاده‌سازی صفحه تنظیمات
    echo '<div class="wrap"><h1>تنظیمات سیستم</h1><p>این صفحه به زودی پیاده‌سازی می‌شود.</p></div>';
}

/**
 * صفحه ابزارهای سیستم
 */
function wf_admin_tools() {
    // پیاده‌سازی صفحه ابزارها
    echo '<div class="wrap"><h1>ابزارهای سیستم</h1><p>این صفحه به زودی پیاده‌سازی می‌شود.</p></div>';
}

/**
 * ============================================
 * توابع کمکی
 * ============================================
 */

/**
 * دریافت تعداد کل پرسنل با فیلتر
 */
function wf_get_total_personnel_count($params = array()) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'wf_personnel';
    
    $where = array("status != 'deleted'");
    $prepare_args = array();
    
    if (!empty($params['department_id'])) {
        $where[] = "department_id = %d";
        $prepare_args[] = $params['department_id'];
    }
    
    if (!empty($params['status']) && $params['status'] != 'all') {
        $where[] = "status = %s";
        $prepare_args[] = $params['status'];
    }
    
    if (!empty($params['search'])) {
        $where[] = "(first_name LIKE %s OR last_name LIKE %s OR national_id LIKE %s OR personnel_code LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($params['search']) . '%';
        $prepare_args[] = $search_term;
        $prepare_args[] = $search_term;
        $prepare_args[] = $search_term;
        $prepare_args[] = $search_term;
    }
    
    $where_sql = implode(' AND ', $where);
    
    $query = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
    
    if (!empty($prepare_args)) {
        $query = $wpdb->prepare($query, $prepare_args);
    }
    
    return (int) $wpdb->get_var($query);
}

/**
 * دریافت برچسب نوع فیلد
 */
function wf_get_field_type_label($type) {
    $labels = array(
        'text' => 'متن',
        'number' => 'عدد',
        'decimal' => 'اعشار',
        'date' => 'تاریخ',
        'time' => 'زمان',
        'datetime' => 'تاریخ و زمان',
        'select' => 'انتخابی',
        'checkbox' => 'چک‌باکس'
    );
    
    return $labels[$type] ?? $type;
}

/**
 * دریافت آیکن فعالیت
 */
function wf_get_activity_icon($activity_type) {
    $icons = array(
        'field_created' => 'plus',
        'field_updated' => 'edit',
        'field_deleted' => 'trash',
        'department_created' => 'building',
        'department_updated' => 'edit',
        'department_deleted' => 'trash',
        'personnel_created' => 'admin-users',
        'personnel_updated' => 'edit',
        'personnel_deleted' => 'trash',
        'period_created' => 'calendar',
        'period_closed' => 'lock',
        'approval_created' => 'warning',
        'approval_approved' => 'yes',
        'approval_rejected' => 'no',
        'tables_created' => 'database',
        'tables_optimized' => 'database',
        'backup_created' => 'backup',
        'system_initialized' => 'admin-site',
        'default_admin_created' => 'admin-users',
        'update_performed' => 'update'
    );
    
    return $icons[$activity_type] ?? 'info';
}

/**
 * دریافت هشدارهای سیستم
 */
function wf_get_system_alerts() {
    $alerts = array();
    
    global $wpdb;
    
    // بررسی ادارات بدون مدیر
    $departments_without_manager = $wpdb->get_results(
        "SELECT id, name FROM {$wpdb->prefix}wf_departments 
         WHERE manager_id = 0 AND status = 'active'",
        ARRAY_A
    );
    
    if (!empty($departments_without_manager)) {
        $alerts[] = array(
            'type' => 'warning',
            'message' => sprintf('%d اداره بدون مدیر وجود دارد.', count($departments_without_manager)),
            'action' => array(
                'text' => 'مشاهده ادارات',
                'url' => admin_url('admin.php?page=workforce-departments')
            )
        );
    }
    
    // بررسی پرسنل با اطلاعات ناقص
    $incomplete_personnel = wf_get_incomplete_personnel();
    $incomplete_count = count($incomplete_personnel);
    
    if ($incomplete_count > 0) {
        $alerts[] = array(
            'type' => 'error',
            'message' => sprintf('%d پرسنل با اطلاعات ناقص وجود دارد.', $incomplete_count),
            'action' => array(
                'text' => 'مشاهده پرسنل',
                'url' => admin_url('admin.php?page=workforce-personnel&status=incomplete')
            )
        );
    }
    
    // بررسی دوره جاری
    $current_period = wf_get_current_period();
    if (!$current_period) {
        $alerts[] = array(
            'type' => 'error',
            'message' => 'هیچ دوره فعالی وجود ندارد.',
            'action' => array(
                'text' => 'ایجاد دوره',
                'url' => admin_url('admin.php?page=workforce-periods&action=add')
            )
        );
    }
    
    return $alerts;
}

// پایان فایل
