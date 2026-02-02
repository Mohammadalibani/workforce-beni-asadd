<?php
/**
 * پنل مدیریت ادمین - سیستم مدیریت کارکرد پرسنل
 * مدیریت کامل سیستم از طریق پیشخوان وردپرس
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// ==================== منوها و صفحات ادمین ====================

/**
 * ایجاد منوهای ادمین
 */
add_action('admin_menu', 'wf_admin_menus');

function wf_admin_menus() {
    // منوی اصلی
    add_menu_page(
        __('مدیریت کارکرد پرسنل', 'workforce-beni-asad'),
        __('کارکرد پرسنل', 'workforce-beni-asad'),
        'manage_options',
        'workforce-admin',
        'wf_admin_dashboard_page',
        'dashicons-groups',
        30
    );
    
    // زیرمنوها
    add_submenu_page(
        'workforce-admin',
        __('داشبورد', 'workforce-beni-asad'),
        __('داشبورد', 'workforce-beni-asad'),
        'manage_options',
        'workforce-admin',
        'wf_admin_dashboard_page'
    );
    
    add_submenu_page(
        'workforce-admin',
        __('مدیریت فیلدها', 'workforce-beni-asad'),
        __('فیلدها', 'workforce-beni-asad'),
        'manage_options',
        'workforce-fields',
        'wf_admin_fields_page'
    );
    
    add_submenu_page(
        'workforce-admin',
        __('مدیریت ادارات', 'workforce-beni-asad'),
        __('ادارات', 'workforce-beni-asad'),
        'manage_options',
        'workforce-departments',
        'wf_admin_departments_page'
    );
    
    add_submenu_page(
        'workforce-admin',
        __('مدیریت پرسنل', 'workforce-beni-asad'),
        __('پرسنل', 'workforce-beni-asad'),
        'manage_options',
        'workforce-personnel',
        'wf_admin_personnel_page'
    );
    
    add_submenu_page(
        'workforce-admin',
        __('دوره‌های کارکرد', 'workforce-beni-asad'),
        __('دوره‌ها', 'workforce-beni-asad'),
        'manage_options',
        'workforce-periods',
        'wf_admin_periods_page'
    );
    
    add_submenu_page(
        'workforce-admin',
        __('تایید درخواست‌ها', 'workforce-beni-asad'),
        __('درخواست‌ها', 'workforce-beni-asad'),
        'manage_options',
        'workforce-approvals',
        'wf_admin_approvals_page'
    );
    
    add_submenu_page(
        'workforce-admin',
        __('قالب گزارش اکسل', 'workforce-beni-asad'),
        __('قالب اکسل', 'workforce-beni-asad'),
        'manage_options',
        'workforce-excel-templates',
        'wf_admin_excel_templates_page'
    );
    
    add_submenu_page(
        'workforce-admin',
        __('تنظیمات سیستم', 'workforce-beni-asad'),
        __('تنظیمات', 'workforce-beni-asad'),
        'manage_options',
        'workforce-settings',
        'wf_admin_settings_page'
    );
    
    add_submenu_page(
        'workforce-admin',
        __('لاگ سیستم', 'workforce-beni-asad'),
        __('لاگ‌ها', 'workforce-beni-asad'),
        'manage_options',
        'workforce-logs',
        'wf_admin_logs_page'
    );
    
    add_submenu_page(
        'workforce-admin',
        __('پشتیبان‌گیری', 'workforce-beni-asad'),
        __('پشتیبان', 'workforce-beni-asad'),
        'manage_options',
        'workforce-backup',
        'wf_admin_backup_page'
    );
    
    // منوی مخفی برای ایمپورت
    add_submenu_page(
        null,
        __('ورود اطلاعات', 'workforce-beni-asad'),
        __('ورود اطلاعات', 'workforce-beni-asad'),
        'manage_options',
        'workforce-import',
        'wf_admin_import_page'
    );
    
    // منوی مخفی برای گزارش‌های پیشرفته
    add_submenu_page(
        null,
        __('گزارش‌های پیشرفته', 'workforce-beni-asad'),
        __('گزارش‌های پیشرفته', 'workforce-beni-asad'),
        'manage_options',
        'workforce-advanced-reports',
        'wf_admin_advanced_reports_page'
    );
}

/**
 * نمایش صفحه داشبورد ادمین
 */
function wf_admin_dashboard_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('شما مجوز دسترسی به این صفحه را ندارید.', 'workforce-beni-asad'));
    }
    
    // دریافت آمار سیستم
    $stats = wf_calculate_system_stats();
    
    // دریافت فعالیت‌های اخیر
    $recent_activity = wf_get_recent_activity(10);
    
    // دریافت هشدارها
    $alerts = wf_get_system_alerts();
    
    ?>
    <div class="wrap workforce-admin-wrap">
        <h1 class="wp-heading-inline">
            <i class="dashicons dashicons-dashboard"></i>
            <?php _e('داشبورد مدیریت کارکرد پرسنل', 'workforce-beni-asad'); ?>
        </h1>
        
        <div class="wf-admin-header">
            <div class="wf-welcome-panel">
                <div class="wf-welcome-content">
                    <h2><?php _e('خوش آمدید به سیستم مدیریت کارکرد پرسنل', 'workforce-beni-asad'); ?></h2>
                    <p><?php _e('از این پنل می‌توانید تمام بخش‌های سیستم را مدیریت کنید.', 'workforce-beni-asad'); ?></p>
                    <div class="wf-quick-links">
                        <a href="<?php echo admin_url('admin.php?page=workforce-personnel'); ?>" class="button button-primary">
                            <i class="dashicons dashicons-plus"></i>
                            <?php _e('افزودن پرسنل جدید', 'workforce-beni-asad'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=workforce-departments'); ?>" class="button">
                            <i class="dashicons dashicons-building"></i>
                            <?php _e('مدیریت ادارات', 'workforce-beni-asad'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=workforce-approvals'); ?>" class="button">
                            <i class="dashicons dashicons-yes"></i>
                            <?php _e('تایید درخواست‌ها', 'workforce-beni-asad'); ?>
                        </a>
                    </div>
                </div>
                <div class="wf-system-info">
                    <h3><?php _e('اطلاعات سیستم', 'workforce-beni-asad'); ?></h3>
                    <ul>
                        <li><strong><?php _e('نسخه سیستم:', 'workforce-beni-asad'); ?></strong> <?php echo WF_VERSION; ?></li>
                        <li><strong><?php _e('تعداد ادارات:', 'workforce-beni-asad'); ?></strong> <?php echo $stats['total_departments']; ?></li>
                        <li><strong><?php _e('تعداد پرسنل:', 'workforce-beni-asad'); ?></strong> <?php echo $stats['total_personnel']; ?></li>
                        <li><strong><?php _e('تاریخ امروز:', 'workforce-beni-asad'); ?></strong> <?php echo wf_get_current_jalali_date('Y/m/d'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- کارت‌های آمار -->
        <div class="wf-stats-cards">
            <div class="wf-stat-card wf-stat-primary">
                <div class="wf-stat-icon">
                    <i class="dashicons dashicons-building"></i>
                </div>
                <div class="wf-stat-content">
                    <h3><?php _e('ادارات فعال', 'workforce-beni-asad'); ?></h3>
                    <div class="wf-stat-number"><?php echo $stats['total_departments']; ?></div>
                    <div class="wf-stat-desc"><?php _e('اداره ثبت شده', 'workforce-beni-asad'); ?></div>
                </div>
            </div>
            
            <div class="wf-stat-card wf-stat-success">
                <div class="wf-stat-icon">
                    <i class="dashicons dashicons-groups"></i>
                </div>
                <div class="wf-stat-content">
                    <h3><?php _e('پرسنل فعال', 'workforce-beni-asad'); ?></h3>
                    <div class="wf-stat-number"><?php echo $stats['active_personnel']; ?></div>
                    <div class="wf-stat-desc"><?php _e('نفر در سیستم', 'workforce-beni-asad'); ?></div>
                </div>
            </div>
            
            <div class="wf-stat-card wf-stat-warning">
                <div class="wf-stat-icon">
                    <i class="dashicons dashicons-warning"></i>
                </div>
                <div class="wf-stat-content">
                    <h3><?php _e('درخواست‌های انتظار', 'workforce-beni-asad'); ?></h3>
                    <div class="wf-stat-number"><?php echo $stats['pending_approvals']; ?></div>
                    <div class="wf-stat-desc"><?php _e('درخواست بررسی نشده', 'workforce-beni-asad'); ?></div>
                </div>
            </div>
            
            <div class="wf-stat-card wf-stat-info">
                <div class="wf-stat-icon">
                    <i class="dashicons dashicons-chart-line"></i>
                </div>
                <div class="wf-stat-content">
                    <h3><?php _e('میانگین تکمیل', 'workforce-beni-asad'); ?></h3>
                    <div class="wf-stat-number"><?php echo round($stats['average_completion'], 1); ?>%</div>
                    <div class="wf-stat-desc"><?php _e('پرونده‌های تکمیل شده', 'workforce-beni-asad'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="wf-dashboard-content">
            <div class="wf-dashboard-column">
                <!-- هشدارهای سیستم -->
                <div class="wf-card">
                    <div class="wf-card-header">
                        <h3><i class="dashicons dashicons-warning"></i> <?php _e('هشدارهای سیستم', 'workforce-beni-asad'); ?></h3>
                    </div>
                    <div class="wf-card-body">
                        <?php if (empty($alerts)): ?>
                            <div class="wf-alert wf-alert-success">
                                <i class="dashicons dashicons-yes"></i>
                                <?php _e('هیچ هشداری وجود ندارد. سیستم به درستی کار می‌کند.', 'workforce-beni-asad'); ?>
                            </div>
                        <?php else: ?>
                            <?php foreach ($alerts as $alert): ?>
                                <div class="wf-alert wf-alert-<?php echo esc_attr($alert['type']); ?>">
                                    <i class="dashicons dashicons-<?php echo esc_attr($alert['icon']); ?>"></i>
                                    <?php echo esc_html($alert['message']); ?>
                                    <?php if (!empty($alert['action_url'])): ?>
                                        <a href="<?php echo esc_url($alert['action_url']); ?>" class="wf-alert-action">
                                            <?php echo esc_html($alert['action_text']); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- فعالیت‌های اخیر -->
                <div class="wf-card">
                    <div class="wf-card-header">
                        <h3><i class="dashicons dashicons-update"></i> <?php _e('فعالیت‌های اخیر', 'workforce-beni-asad'); ?></h3>
                        <a href="<?php echo admin_url('admin.php?page=workforce-logs'); ?>" class="button button-small">
                            <?php _e('مشاهده همه', 'workforce-beni-asad'); ?>
                        </a>
                    </div>
                    <div class="wf-card-body">
                        <div class="wf-activity-list">
                            <?php if (empty($recent_activity)): ?>
                                <p class="wf-no-activity"><?php _e('هیچ فعالیتی ثبت نشده است.', 'workforce-beni-asad'); ?></p>
                            <?php else: ?>
                                <?php foreach ($recent_activity as $activity): ?>
                                    <div class="wf-activity-item">
                                        <div class="wf-activity-icon">
                                            <i class="dashicons dashicons-<?php echo esc_attr($activity['icon']); ?>"></i>
                                        </div>
                                        <div class="wf-activity-content">
                                            <div class="wf-activity-message">
                                                <?php echo wp_kses_post($activity['message']); ?>
                                            </div>
                                            <div class="wf-activity-meta">
                                                <span class="wf-activity-user"><?php echo esc_html($activity['user']); ?></span>
                                                <span class="wf-activity-time"><?php echo esc_html($activity['time']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="wf-dashboard-column">
                <!-- گزارش سریع -->
                <div class="wf-card">
                    <div class="wf-card-header">
                        <h3><i class="dashicons dashicons-chart-bar"></i> <?php _e('گزارش سریع', 'workforce-beni-asad'); ?></h3>
                    </div>
                    <div class="wf-card-body">
                        <div class="wf-quick-reports">
                            <div class="wf-quick-report-item">
                                <h4><?php _e('ادارات بدون مدیر', 'workforce-beni-asad'); ?></h4>
                                <div class="wf-quick-report-value"><?php echo $stats['departments_without_manager']; ?></div>
                                <a href="<?php echo admin_url('admin.php?page=workforce-departments&filter=no_manager'); ?>" class="button button-small">
                                    <?php _e('مشاهده', 'workforce-beni-asad'); ?>
                                </a>
                            </div>
                            
                            <div class="wf-quick-report-item">
                                <h4><?php _e('پرسنل با اطلاعات ناقص', 'workforce-beni-asad'); ?></h4>
                                <div class="wf-quick-report-value"><?php echo $stats['personnel_with_warnings']; ?></div>
                                <a href="<?php echo admin_url('admin.php?page=workforce-personnel&filter=incomplete'); ?>" class="button button-small">
                                    <?php _e('مشاهده', 'workforce-beni-asad'); ?>
                                </a>
                            </div>
                            
                            <div class="wf-quick-report-item">
                                <h4><?php _e('میانگین درصد تکمیل', 'workforce-beni-asad'); ?></h4>
                                <div class="wf-quick-report-value"><?php echo round($stats['average_completion'], 1); ?>%</div>
                                <div class="wf-progress-bar">
                                    <div class="wf-progress-fill" style="width: <?php echo $stats['average_completion']; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- لینک‌های سریع -->
                <div class="wf-card">
                    <div class="wf-card-header">
                        <h3><i class="dashicons dashicons-admin-links"></i> <?php _e('لینک‌های سریع', 'workforce-beni-asad'); ?></h3>
                    </div>
                    <div class="wf-card-body">
                        <div class="wf-quick-links-grid">
                            <a href="<?php echo admin_url('admin.php?page=workforce-fields'); ?>" class="wf-quick-link">
                                <i class="dashicons dashicons-editor-table"></i>
                                <span><?php _e('مدیریت فیلدها', 'workforce-beni-asad'); ?></span>
                            </a>
                            
                            <a href="<?php echo admin_url('admin.php?page=workforce-excel-templates'); ?>" class="wf-quick-link">
                                <i class="dashicons dashicons-media-spreadsheet"></i>
                                <span><?php _e('قالب‌های اکسل', 'workforce-beni-asad'); ?></span>
                            </a>
                            
                            <a href="<?php echo admin_url('admin.php?page=workforce-settings'); ?>" class="wf-quick-link">
                                <i class="dashicons dashicons-admin-settings"></i>
                                <span><?php _e('تنظیمات سیستم', 'workforce-beni-asad'); ?></span>
                            </a>
                            
                            <a href="<?php echo admin_url('admin.php?page=workforce-backup'); ?>" class="wf-quick-link">
                                <i class="dashicons dashicons-backup"></i>
                                <span><?php _e('پشتیبان‌گیری', 'workforce-beni-asad'); ?></span>
                            </a>
                            
                            <a href="<?php echo admin_url('admin.php?page=workforce-import'); ?>" class="wf-quick-link">
                                <i class="dashicons dashicons-upload"></i>
                                <span><?php _e('ورود اطلاعات', 'workforce-beni-asad'); ?></span>
                            </a>
                            
                            <a href="<?php echo admin_url('admin.php?page=workforce-advanced-reports'); ?>" class="wf-quick-link">
                                <i class="dashicons dashicons-analytics"></i>
                                <span><?php _e('گزارش‌های پیشرفته', 'workforce-beni-asad'); ?></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- اطلاعات فنی -->
        <div class="wf-card">
            <div class="wf-card-header">
                <h3><i class="dashicons dashicons-info"></i> <?php _e('اطلاعات فنی سیستم', 'workforce-beni-asad'); ?></h3>
            </div>
            <div class="wf-card-body">
                <div class="wf-system-details">
                    <?php
                    $server_info = wf_get_server_info();
                    $plugin_info = wf_get_plugin_summary();
                    ?>
                    <div class="wf-system-detail">
                        <strong><?php _e('نسخه پلاگین:', 'workforce-beni-asad'); ?></strong>
                        <span><?php echo $plugin_info['version']; ?></span>
                    </div>
                    <div class="wf-system-detail">
                        <strong><?php _e('نسخه PHP:', 'workforce-beni-asad'); ?></strong>
                        <span><?php echo $server_info['php_version']; ?></span>
                    </div>
                    <div class="wf-system-detail">
                        <strong><?php _e('نسخه MySQL:', 'workforce-beni-asad'); ?></strong>
                        <span><?php echo $server_info['mysql_version']; ?></span>
                    </div>
                    <div class="wf-system-detail">
                        <strong><?php _e('حداکثر حجم آپلود:', 'workforce-beni-asad'); ?></strong>
                        <span><?php echo $server_info['max_upload_size']; ?></span>
                    </div>
                    <div class="wf-system-detail">
                        <strong><?php _e('تعداد جداول سیستم:', 'workforce-beni-asad'); ?></strong>
                        <span><?php echo wf_count_database_tables(); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .workforce-admin-wrap {
            margin: 20px 20px 0 0;
        }
        
        .wf-admin-header {
            margin-bottom: 20px;
        }
        
        .wf-welcome-panel {
            background: #fff;
            border: 1px solid #c3c4c7;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .wf-welcome-content {
            flex: 1;
        }
        
        .wf-welcome-content h2 {
            margin-top: 0;
            color: #1d2327;
        }
        
        .wf-system-info {
            background: #f6f7f7;
            border: 1px solid #dcdcde;
            padding: 15px;
            border-radius: 4px;
            min-width: 300px;
            margin-right: 20px;
        }
        
        .wf-system-info h3 {
            margin-top: 0;
            border-bottom: 1px solid #dcdcde;
            padding-bottom: 10px;
        }
        
        .wf-system-info ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .wf-system-info li {
            padding: 5px 0;
            display: flex;
            justify-content: space-between;
        }
        
        .wf-stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .wf-stat-card {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,.1);
        }
        
        .wf-stat-card.wf-stat-primary {
            border-left: 4px solid #2271b1;
        }
        
        .wf-stat-card.wf-stat-success {
            border-left: 4px solid #00a32a;
        }
        
        .wf-stat-card.wf-stat-warning {
            border-left: 4px solid #dba617;
        }
        
        .wf-stat-card.wf-stat-info {
            border-left: 4px solid #0a95d9;
        }
        
        .wf-stat-icon {
            font-size: 36px;
            color: #50575e;
            margin-left: 15px;
        }
        
        .wf-stat-content {
            flex: 1;
        }
        
        .wf-stat-content h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #646970;
            font-weight: normal;
        }
        
        .wf-stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #1d2327;
            line-height: 1;
        }
        
        .wf-stat-desc {
            font-size: 13px;
            color: #646970;
            margin-top: 5px;
        }
        
        .wf-dashboard-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .wf-card {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .wf-card-header {
            background: #f6f7f7;
            border-bottom: 1px solid #dcdcde;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .wf-card-header h3 {
            margin: 0;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .wf-card-body {
            padding: 20px;
        }
        
        .wf-alert {
            padding: 12px 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            border-right: 4px solid;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .wf-alert-success {
            background: #edfaef;
            border-color: #00a32a;
            color: #0c622d;
        }
        
        .wf-alert-warning {
            background: #fef8ee;
            border-color: #dba617;
            color: #6b4b00;
        }
        
        .wf-alert-error {
            background: #fcf0f1;
            border-color: #d63638;
            color: #8a2424;
        }
        
        .wf-alert-action {
            margin-right: auto;
            font-weight: bold;
        }
        
        .wf-activity-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .wf-activity-item {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .wf-activity-item:last-child {
            border-bottom: none;
        }
        
        .wf-activity-icon {
            font-size: 20px;
            color: #50575e;
            width: 40px;
            text-align: center;
        }
        
        .wf-activity-content {
            flex: 1;
        }
        
        .wf-activity-message {
            margin-bottom: 5px;
            line-height: 1.4;
        }
        
        .wf-activity-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #646970;
        }
        
        .wf-no-activity {
            text-align: center;
            color: #646970;
            padding: 20px;
        }
        
        .wf-quick-reports {
            display: grid;
            gap: 20px;
        }
        
        .wf-quick-report-item {
            padding: 15px;
            background: #f6f7f7;
            border-radius: 4px;
            border: 1px solid #dcdcde;
        }
        
        .wf-quick-report-item h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #1d2327;
        }
        
        .wf-quick-report-value {
            font-size: 28px;
            font-weight: bold;
            color: #2271b1;
            margin-bottom: 10px;
        }
        
        .wf-progress-bar {
            height: 6px;
            background: #f0f0f1;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .wf-progress-fill {
            height: 100%;
            background: #2271b1;
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        
        .wf-quick-links-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .wf-quick-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 15px;
            background: #f6f7f7;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            text-decoration: none;
            color: #2c3338;
            transition: all 0.2s ease;
        }
        
        .wf-quick-link:hover {
            background: #f0f0f1;
            border-color: #8c8f94;
            transform: translateY(-2px);
        }
        
        .wf-quick-link i {
            font-size: 24px;
            margin-bottom: 10px;
            color: #50575e;
        }
        
        .wf-quick-link span {
            font-size: 13px;
            text-align: center;
        }
        
        .wf-system-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .wf-system-detail {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background: #f6f7f7;
            border-radius: 4px;
        }
        
        @media (max-width: 1200px) {
            .wf-dashboard-content {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 782px) {
            .wf-welcome-panel {
                flex-direction: column;
            }
            
            .wf-system-info {
                width: 100%;
                margin-right: 0;
                margin-top: 20px;
            }
            
            .wf-stats-cards {
                grid-template-columns: 1fr;
            }
            
            .wf-quick-links-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // رفرش خودکار آمار هر 5 دقیقه
        setInterval(function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wf_refresh_dashboard_stats',
                    nonce: '<?php echo wp_create_nonce('wf_dashboard_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // به‌روزرسانی آمار
                        $('.wf-stat-number').eq(0).text(response.data.departments);
                        $('.wf-stat-number').eq(1).text(response.data.personnel);
                        $('.wf-stat-number').eq(2).text(response.data.pending_approvals);
                        $('.wf-stat-number').eq(3).text(response.data.avg_completion + '%');
                    }
                }
            });
        }, 300000); // 5 دقیقه
    });
    </script>
    <?php
}

/**
 * صفحه مدیریت فیلدها
 */
function wf_admin_fields_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('شما مجوز دسترسی به این صفحه را ندارید.', 'workforce-beni-asad'));
    }
    
    // دریافت پارامترهای صفحه
    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
    $field_id = isset($_GET['field_id']) ? intval($_GET['field_id']) : 0;
    
    // پردازش اقدامات
    if (isset($_POST['wf_field_action'])) {
        $action_result = wf_process_field_action($_POST);
        if ($action_result['success']) {
            add_settings_error('wf_fields', 'wf_field_success', $action_result['message'], 'success');
        } else {
            add_settings_error('wf_fields', 'wf_field_error', $action_result['message'], 'error');
        }
    }
    
    // حذف فیلد
    if (isset($_GET['delete_field']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_field_' . $_GET['delete_field'])) {
        $delete_result = wf_delete_field(intval($_GET['delete_field']), isset($_GET['permanent']));
        if ($delete_result['success']) {
            add_settings_error('wf_fields', 'wf_delete_success', $delete_result['message'], 'success');
        } else {
            add_settings_error('wf_fields', 'wf_delete_error', $delete_result['message'], 'error');
        }
    }
    
    settings_errors('wf_fields');
    
    ?>
    <div class="wrap workforce-admin-wrap">
        <h1 class="wp-heading-inline">
            <i class="dashicons dashicons-editor-table"></i>
            <?php _e('مدیریت فیلدها', 'workforce-beni-asad'); ?>
        </h1>
        
        <?php if ($action === 'edit' || $action === 'add'): ?>
            <?php wf_render_field_editor($field_id); ?>
        <?php else: ?>
            <?php wf_render_fields_list(); ?>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * رندر لیست فیلدها
 */
function wf_render_fields_list() {
    // دریافت پارامترهای فیلتر
    $filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'all';
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    
    // دریافت فیلدها
    $filters = array();
    if ($filter === 'required') {
        $filters['is_required'] = 1;
    } elseif ($filter === 'locked') {
        $filters['is_locked'] = 1;
    } elseif ($filter === 'monitoring') {
        $filters['is_monitoring'] = 1;
    } elseif ($filter === 'key') {
        $filters['is_key'] = 1;
    } elseif ($filter === 'inactive') {
        $filters['status'] = 'inactive';
    }
    
    $fields = wf_get_all_fields($filters);
    
    // اعمال جستجو
    if ($search) {
        $fields = array_filter($fields, function($field) use ($search) {
            return stripos($field['field_name'], $search) !== false || 
                   stripos($field['field_key'], $search) !== false ||
                   stripos($field['description'], $search) !== false;
        });
    }
    
    ?>
    <a href="<?php echo admin_url('admin.php?page=workforce-fields&action=add'); ?>" class="page-title-action">
        <i class="dashicons dashicons-plus"></i>
        <?php _e('افزودن فیلد جدید', 'workforce-beni-asad'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <div class="wf-admin-filters">
        <div class="wf-filter-tabs">
            <a href="<?php echo admin_url('admin.php?page=workforce-fields'); ?>" 
               class="wf-filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                <?php _e('همه فیلدها', 'workforce-beni-asad'); ?>
                <span class="count">(<?php echo wf_count_fields(); ?>)</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=workforce-fields&filter=required'); ?>" 
               class="wf-filter-tab <?php echo $filter === 'required' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-star-filled" style="color: #f0b849;"></span>
                <?php _e('ضروری', 'workforce-beni-asad'); ?>
                <span class="count">(<?php echo wf_count_fields(array('is_required' => 1)); ?>)</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=workforce-fields&filter=locked'); ?>" 
               class="wf-filter-tab <?php echo $filter === 'locked' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-lock"></span>
                <?php _e('قفل شده', 'workforce-beni-asad'); ?>
                <span class="count">(<?php echo wf_count_fields(array('is_locked' => 1)); ?>)</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=workforce-fields&filter=monitoring'); ?>" 
               class="wf-filter-tab <?php echo $filter === 'monitoring' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-chart-area"></span>
                <?php _e('مانیتورینگ', 'workforce-beni-asad'); ?>
                <span class="count">(<?php echo wf_count_fields(array('is_monitoring' => 1)); ?>)</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=workforce-fields&filter=key'); ?>" 
               class="wf-filter-tab <?php echo $filter === 'key' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-admin-network"></span>
                <?php _e('کلید', 'workforce-beni-asad'); ?>
                <span class="count">(<?php echo wf_count_fields(array('is_key' => 1)); ?>)</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=workforce-fields&filter=inactive'); ?>" 
               class="wf-filter-tab <?php echo $filter === 'inactive' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-hidden"></span>
                <?php _e('غیرفعال', 'workforce-beni-asad'); ?>
                <span class="count">(<?php echo wf_count_fields(array('status' => 'inactive')); ?>)</span>
            </a>
        </div>
        
        <form method="get" class="wf-search-form">
            <input type="hidden" name="page" value="workforce-fields">
            <input type="search" 
                   name="s" 
                   value="<?php echo esc_attr($search); ?>" 
                   placeholder="<?php esc_attr_e('جستجو در فیلدها...', 'workforce-beni-asad'); ?>"
                   class="wf-search-input">
            <button type="submit" class="button">
                <span class="dashicons dashicons-search"></span>
            </button>
            <?php if ($search): ?>
                <a href="<?php echo admin_url('admin.php?page=workforce-fields'); ?>" class="button">
                    <?php _e('پاک کردن', 'workforce-beni-asad'); ?>
                </a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="wf-fields-table-wrap">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="column-cb check-column">
                        <input type="checkbox" id="cb-select-all-1">
                    </th>
                    <th scope="col" class="column-title"><?php _e('فیلد', 'workforce-beni-asad'); ?></th>
                    <th scope="col" class="column-type"><?php _e('نوع', 'workforce-beni-asad'); ?></th>
                    <th scope="col" class="column-status"><?php _e('وضعیت', 'workforce-beni-asad'); ?></th>
                    <th scope="col" class="column-options"><?php _e('تنظیمات', 'workforce-beni-asad'); ?></th>
                    <th scope="col" class="column-order"><?php _e('ترتیب', 'workforce-beni-asad'); ?></th>
                    <th scope="col" class="column-actions"><?php _e('عملیات', 'workforce-beni-asad'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fields)): ?>
                    <tr>
                        <td colspan="7" class="no-items">
                            <?php _e('هیچ فیلدی یافت نشد.', 'workforce-beni-asad'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($fields as $field): ?>
                        <?php 
                        $field_type_names = array(
                            'text' => 'متن',
                            'number' => 'عدد',
                            'decimal' => 'اعشار',
                            'date' => 'تاریخ',
                            'time' => 'زمان',
                            'datetime' => 'تاریخ و زمان',
                            'select' => 'انتخابی',
                            'checkbox' => 'چک‌باکس'
                        );
                        ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="field_ids[]" value="<?php echo $field['id']; ?>">
                            </th>
                            <td class="column-title column-primary">
                                <strong class="row-title">
                                    <?php echo esc_html($field['field_name']); ?>
                                    <?php if ($field['is_required']): ?>
                                        <span class="wf-required-badge" title="<?php esc_attr_e('فیلد ضروری', 'workforce-beni-asad'); ?>">
                                            <span class="dashicons dashicons-star-filled"></span>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($field['is_locked']): ?>
                                        <span class="wf-locked-badge" title="<?php esc_attr_e('فیلد قفل شده', 'workforce-beni-asad'); ?>">
                                            <span class="dashicons dashicons-lock"></span>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($field['is_key']): ?>
                                        <span class="wf-key-badge" title="<?php esc_attr_e('فیلد کلید', 'workforce-beni-asad'); ?>">
                                            <span class="dashicons dashicons-admin-network"></span>
                                        </span>
                                    <?php endif; ?>
                                </strong>
                                <div class="row-actions">
                                    <code class="wf-field-key"><?php echo esc_html($field['field_key']); ?></code>
                                    <?php if ($field['description']): ?>
                                        <div class="wf-field-description">
                                            <?php echo esc_html($field['description']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="column-type">
                                <span class="wf-field-type wf-field-type-<?php echo esc_attr($field['field_type']); ?>">
                                    <?php echo $field_type_names[$field['field_type']] ?? $field['field_type']; ?>
                                </span>
                            </td>
                            <td class="column-status">
                                <?php if ($field['status'] === 'active'): ?>
                                    <span class="wf-status-active">
                                        <span class="dashicons dashicons-yes"></span>
                                        <?php _e('فعال', 'workforce-beni-asad'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="wf-status-inactive">
                                        <span class="dashicons dashicons-no"></span>
                                        <?php _e('غیرفعال', 'workforce-beni-asad'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="column-options">
                                <div class="wf-field-options">
                                    <?php if ($field['is_monitoring']): ?>
                                        <span class="dashicons dashicons-chart-area" title="<?php esc_attr_e('قابل مانیتورینگ', 'workforce-beni-asad'); ?>"></span>
                                    <?php endif; ?>
                                    
                                    <?php if ($field['field_options']): ?>
                                        <span class="dashicons dashicons-admin-generic" title="<?php esc_attr_e('دارای تنظیمات', 'workforce-beni-asad'); ?>"></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="column-order">
                                <?php echo $field['display_order']; ?>
                            </td>
                            <td class="column-actions">
                                <div class="wf-action-buttons">
                                    <a href="<?php echo admin_url('admin.php?page=workforce-fields&action=edit&field_id=' . $field['id']); ?>" 
                                       class="button button-small">
                                        <span class="dashicons dashicons-edit"></span>
                                        <?php _e('ویرایش', 'workforce-beni-asad'); ?>
                                    </a>
                                    
                                    <?php if ($field['status'] === 'active'): ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=workforce-fields&deactivate_field=' . $field['id']), 'deactivate_field_' . $field['id']); ?>" 
                                           class="button button-small">
                                            <span class="dashicons dashicons-hidden"></span>
                                            <?php _e('غیرفعال', 'workforce-beni-asad'); ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=workforce-fields&activate_field=' . $field['id']), 'activate_field_' . $field['id']); ?>" 
                                           class="button button-small">
                                            <span class="dashicons dashicons-visibility"></span>
                                            <?php _e('فعال', 'workforce-beni-asad'); ?>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=workforce-fields&delete_field=' . $field['id']), 'delete_field_' . $field['id']); ?>" 
                                       class="button button-small button-link-delete"
                                       onclick="return confirm('<?php esc_attr_e('آیا از حذف این فیلد اطمینان دارید؟', 'workforce-beni-asad'); ?>')">
                                        <span class="dashicons dashicons-trash"></span>
                                        <?php _e('حذف', 'workforce-beni-asad'); ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7">
                        <div class="wf-bulk-actions">
                            <select name="wf_bulk_action">
                                <option value=""><?php _e('عملیات گروهی', 'workforce-beni-asad'); ?></option>
                                <option value="activate"><?php _e('فعال کردن', 'workforce-beni-asad'); ?></option>
                                <option value="deactivate"><?php _e('غیرفعال کردن', 'workforce-beni-asad'); ?></option>
                                <option value="delete"><?php _e('حذف', 'workforce-beni-asad'); ?></option>
                            </select>
                            <button type="button" class="button" id="wf-apply-bulk-action">
                                <?php _e('اعمال', 'workforce-beni-asad'); ?>
                            </button>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <div class="wf-field-info">
        <h3><?php _e('راهنمای آیکن‌ها', 'workforce-beni-asad'); ?></h3>
        <div class="wf-info-grid">
            <div class="wf-info-item">
                <span class="dashicons dashicons-star-filled" style="color: #f0b849;"></span>
                <span><?php _e('فیلد ضروری', 'workforce-beni-asad'); ?></span>
            </div>
            <div class="wf-info-item">
                <span class="dashicons dashicons-lock"></span>
                <span><?php _e('فیلد قفل شده', 'workforce-beni-asad'); ?></span>
            </div>
            <div class="wf-info-item">
                <span class="dashicons dashicons-chart-area"></span>
                <span><?php _e('قابل مانیتورینگ', 'workforce-beni-asad'); ?></span>
            </div>
            <div class="wf-info-item">
                <span class="dashicons dashicons-admin-network"></span>
                <span><?php _e('فیلد کلید', 'workforce-beni-asad'); ?></span>
            </div>
            <div class="wf-info-item">
                <span class="dashicons dashicons-admin-generic"></span>
                <span><?php _e('دارای تنظیمات', 'workforce-beni-asad'); ?></span>
            </div>
        </div>
    </div>
    
    <style>
        .wf-admin-filters {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            padding: 15px;
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
        }
        
        .wf-filter-tabs {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .wf-filter-tab {
            padding: 8px 15px;
            background: #f6f7f7;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            text-decoration: none;
            color: #2c3338;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .wf-filter-tab:hover {
            background: #f0f0f1;
            border-color: #8c8f94;
        }
        
        .wf-filter-tab.active {
            background: #2271b1;
            border-color: #2271b1;
            color: #fff;
        }
        
        .wf-filter-tab .count {
            background: rgba(255,255,255,.3);
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 11px;
        }
        
        .wf-search-form {
            display: flex;
            gap: 5px;
        }
        
        .wf-search-input {
            width: 250px;
            padding: 5px 10px;
            border: 1px solid #dcdcde;
            border-radius: 4px;
        }
        
        .wf-fields-table-wrap {
            margin: 20px 0;
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .wf-required-badge,
        .wf-locked-badge,
        .wf-key-badge {
            display: inline-block;
            margin-right: 5px;
            vertical-align: middle;
        }
        
        .wf-field-key {
            background: #f6f7f7;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            color: #50575e;
        }
        
        .wf-field-description {
            font-size: 12px;
            color: #646970;
            margin-top: 5px;
            line-height: 1.4;
        }
        
        .wf-field-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            background: #f6f7f7;
            color: #50575e;
        }
        
        .wf-status-active {
            color: #00a32a;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .wf-status-inactive {
            color: #d63638;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .wf-field-options {
            display: flex;
            gap: 5px;
        }
        
        .wf-action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .wf-bulk-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .wf-field-info {
            margin-top: 20px;
            padding: 15px;
            background: #f6f7f7;
            border: 1px solid #dcdcde;
            border-radius: 4px;
        }
        
        .wf-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        
        .wf-info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }
        
        @media (max-width: 782px) {
            .wf-admin-filters {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            
            .wf-filter-tabs {
                flex-wrap: wrap;
            }
            
            .wf-search-form {
                width: 100%;
            }
            
            .wf-search-input {
                flex: 1;
            }
            
            .wf-action-buttons {
                flex-direction: column;
            }
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // عملیات گروهی
        $('#wf-apply-bulk-action').on('click', function() {
            var action = $('select[name="wf_bulk_action"]').val();
            var selected = $('input[name="field_ids[]"]:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (!action) {
                alert('لطفاً یک عملیات انتخاب کنید');
                return;
            }
            
            if (selected.length === 0) {
                alert('لطفاً حداقل یک فیلد را انتخاب کنید');
                return;
            }
            
            if (action === 'delete' && !confirm('آیا از حذف فیلدهای انتخاب شده اطمینان دارید؟')) {
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wf_bulk_fields_action',
                    bulk_action: action,
                    field_ids: selected,
                    nonce: '<?php echo wp_create_nonce('wf_bulk_fields_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'خطا در انجام عملیات');
                    }
                }
            });
        });
        
        // مرتب‌سازی فیلدها با درگ اند درراپ
        $('.wf-fields-table-wrap tbody').sortable({
            items: 'tr',
            cursor: 'move',
            axis: 'y',
            handle: '.row-title',
            scrollSensitivity: 40,
            helper: function(e, tr) {
                var $originals = tr.children();
                var $helper = tr.clone();
                $helper.children().each(function(index) {
                    $(this).width($originals.eq(index).width());
                });
                return $helper;
            },
            update: function(event, ui) {
                var field_ids = [];
                $(this).find('tr').each(function() {
                    var field_id = $(this).find('input[name="field_ids[]"]').val();
                    if (field_id) {
                        field_ids.push(field_id);
                    }
                });
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wf_update_fields_order',
                        field_ids: field_ids,
                        nonce: '<?php echo wp_create_nonce('wf_update_order_nonce'); ?>'
                    }
                });
            }
        });
    });
    </script>
    <?php
}

/**
 * رندر ویرایشگر فیلد
 */
function wf_render_field_editor($field_id = 0) {
    $field = $field_id ? wf_get_field($field_id) : array();
    $is_edit = !empty($field);
    
    // تنظیمات پیش‌فرض
    $defaults = array(
        'field_name' => '',
        'field_key' => '',
        'field_type' => 'text',
        'field_options' => '',
        'is_required' => 0,
        'is_locked' => 0,
        'is_monitoring' => 0,
        'is_key' => 0,
        'display_order' => 0,
        'validation_rules' => '',
        'description' => '',
        'status' => 'active'
    );
    
    $field = array_merge($defaults, $field);
    
    ?>
    <div class="wf-field-editor">
        <form method="post" action="" id="wf-field-form">
            <?php wp_nonce_field('wf_save_field', 'wf_field_nonce'); ?>
            <input type="hidden" name="wf_field_action" value="<?php echo $is_edit ? 'edit' : 'add'; ?>">
            <input type="hidden" name="field_id" value="<?php echo $field_id; ?>">
            
            <div class="wf-field-editor-header">
                <h2>
                    <?php if ($is_edit): ?>
                        <i class="dashicons dashicons-edit"></i>
                        <?php printf(__('ویرایش فیلد: %s', 'workforce-beni-asad'), esc_html($field['field_name'])); ?>
                    <?php else: ?>
                        <i class="dashicons dashicons-plus"></i>
                        <?php _e('افزودن فیلد جدید', 'workforce-beni-asad'); ?>
                    <?php endif; ?>
                </h2>
                <div class="wf-form-actions">
                    <a href="<?php echo admin_url('admin.php?page=workforce-fields'); ?>" class="button">
                        <?php _e('انصراف', 'workforce-beni-asad'); ?>
                    </a>
                    <button type="submit" class="button button-primary">
                        <i class="dashicons dashicons-yes"></i>
                        <?php echo $is_edit ? __('ذخیره تغییرات', 'workforce-beni-asad') : __('ایجاد فیلد', 'workforce-beni-asad'); ?>
                    </button>
                </div>
            </div>
            
            <div class="wf-field-editor-content">
                <div class="wf-field-basic-settings">
                    <h3><?php _e('تنظیمات اصلی', 'workforce-beni-asad'); ?></h3>
                    
                    <div class="wf-form-row">
                        <div class="wf-form-col">
                            <label for="field_name">
                                <strong><?php _e('عنوان فارسی فیلد', 'workforce-beni-asad'); ?> *</strong>
                                <span class="description"><?php _e('عنوان قابل نمایش برای کاربران', 'workforce-beni-asad'); ?></span>
                            </label>
                            <input type="text" 
                                   id="field_name" 
                                   name="field_name" 
                                   value="<?php echo esc_attr($field['field_name']); ?>" 
                                   class="regular-text" 
                                   required>
                        </div>
                        
                        <div class="wf-form-col">
                            <label for="field_key">
                                <strong><?php _e('کلید فیلد', 'workforce-beni-asad'); ?> *</strong>
                                <span class="description"><?php _e('نام یکتا برای استفاده در سیستم (انگلیسی)', 'workforce-beni-asad'); ?></span>
                            </label>
                            <input type="text" 
                                   id="field_key" 
                                   name="field_key" 
                                   value="<?php echo esc_attr($field['field_key']); ?>" 
                                   class="regular-text" 
                                   pattern="[a-z][a-z0-9_]*"
                                   <?php echo $is_edit ? 'readonly' : ''; ?>
                                   required>
                            <?php if ($is_edit): ?>
                                <p class="description">
                                    <i class="dashicons dashicons-info"></i>
                                    <?php _e('تغییر کلید فیلد پس از ایجاد امکان‌پذیر نیست.', 'workforce-beni-asad'); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="wf-form-row">
                        <div class="wf-form-col">
                            <label for="field_type">
                                <strong><?php _e('نوع فیلد', 'workforce-beni-asad'); ?> *</strong>
                            </label>
                            <select id="field_type" name="field_type" class="regular-text">
                                <option value="text" <?php selected($field['field_type'], 'text'); ?>><?php _e('متن', 'workforce-beni-asad'); ?></option>
                                <option value="number" <?php selected($field['field_type'], 'number'); ?>><?php _e('عدد', 'workforce-beni-asad'); ?></option>
                                <option value="decimal" <?php selected($field['field_type'], 'decimal'); ?>><?php _e('اعشار', 'workforce-beni-asad'); ?></option>
                                <option value="date" <?php selected($field['field_type'], 'date'); ?>><?php _e('تاریخ', 'workforce-beni-asad'); ?></option>
                                <option value="time" <?php selected($field['field_type'], 'time'); ?>><?php _e('زمان', 'workforce-beni-asad'); ?></option>
                                <option value="datetime" <?php selected($field['field_type'], 'datetime'); ?>><?php _e('تاریخ و زمان', 'workforce-beni-asad'); ?></option>
                                <option value="select" <?php selected($field['field_type'], 'select'); ?>><?php _e('انتخابی', 'workforce-beni-asad'); ?></option>
                                <option value="checkbox" <?php selected($field['field_type'], 'checkbox'); ?>><?php _e('چک‌باکس', 'workforce-beni-asad'); ?></option>
                            </select>
                        </div>
                        
                        <div class="wf-form-col">
                            <label for="display_order">
                                <strong><?php _e('ترتیب نمایش', 'workforce-beni-asad'); ?></strong>
                                <span class="description"><?php _e('عدد کوچکتر = نمایش زودتر', 'workforce-beni-asad'); ?></span>
                            </label>
                            <input type="number" 
                                   id="display_order" 
                                   name="display_order" 
                                   value="<?php echo esc_attr($field['display_order']); ?>" 
                                   class="small-text" 
                                   min="0" 
                                   step="1">
                        </div>
                    </div>
                    
                    <div class="wf-form-row">
                        <div class="wf-form-col">
                            <label for="description">
                                <strong><?php _e('توضیحات', 'workforce-beni-asad'); ?></strong>
                                <span class="description"><?php _e('راهنمای کاربر برای پر کردن فیلد', 'workforce-beni-asad'); ?></span>
                            </label>
                            <textarea id="description" 
                                      name="description" 
                                      rows="3" 
                                      class="large-text"><?php echo esc_textarea($field['description']); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="wf-field-advanced-settings">
                    <h3><?php _e('تنظیمات پیشرفته', 'workforce-beni-asad'); ?></h3>
                    
                    <div class="wf-form-row">
                        <div class="wf-form-col">
                            <label class="wf-checkbox-label">
                                <input type="checkbox" 
                                       name="is_required" 
                                       value="1" 
                                       <?php checked($field['is_required'], 1); ?>>
                                <span class="wf-checkbox-text">
                                    <strong><?php _e('ضروری', 'workforce-beni-asad'); ?></strong>
                                    <span class="description"><?php _e('پر کردن این فیلد اجباری است', 'workforce-beni-asad'); ?></span>
                                </span>
                            </label>
                            
                            <label class="wf-checkbox-label">
                                <input type="checkbox" 
                                       name="is_locked" 
                                       value="1" 
                                       <?php checked($field['is_locked'], 1); ?>>
                                <span class="wf-checkbox-text">
                                    <strong><?php _e('قفل', 'workforce-beni-asad'); ?></strong>
                                    <span class="description"><?php _e('غیرقابل ویرایش توسط مدیران ادارات', 'workforce-beni-asad'); ?></span>
                                </span>
                            </label>
                            
                            <label class="wf-checkbox-label">
                                <input type="checkbox" 
                                       name="is_monitoring" 
                                       value="1" 
                                       <?php checked($field['is_monitoring'], 1); ?>>
                                <span class="wf-checkbox-text">
                                    <strong><?php _e('مانیتورینگ', 'workforce-beni-asad'); ?></strong>
                                    <span class="description"><?php _e('ساخت کارت مانیتورینگ خودکار', 'workforce-beni-asad'); ?></span>
                                </span>
                            </label>
                            
                            <label class="wf-checkbox-label">
                                <input type="checkbox" 
                                       name="is_key" 
                                       value="1" 
                                       <?php checked($field['is_key'], 1); ?>>
                                <span class="wf-checkbox-text">
                                    <strong><?php _e('کلید', 'workforce-beni-asad'); ?></strong>
                                    <span class="description"><?php _e('بررسی تکراری نبودن مقادیر', 'workforce-beni-asad'); ?></span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="wf-field-options-section" id="field-options-section" style="<?php echo $field['field_type'] !== 'select' ? 'display: none;' : ''; ?>">
                    <h3><?php _e('گزینه‌های انتخابی', 'workforce-beni-asad'); ?></h3>
                    
                    <div class="wf-form-row">
                        <div class="wf-form-col">
                            <label for="field_options">
                                <strong><?php _e('لیست گزینه‌ها', 'workforce-beni-asad'); ?></strong>
                                <span class="description"><?php _e('هر گزینه در یک خط جداگانه وارد شود', 'workforce-beni-asad'); ?></span>
                            </label>
                            <textarea id="field_options" 
                                      name="field_options" 
                                      rows="6" 
                                      class="large-text"
                                      placeholder="گزینه ۱&#10;گزینه ۲&#10;گزینه ۳"><?php 
                            if (!empty($field['field_options']) && is_array($field['field_options'])) {
                                echo esc_textarea(implode("\n", $field['field_options']));
                            }
                            ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="wf-field-validation-section">
                    <h3><?php _e('اعتبارسنجی', 'workforce-beni-asad'); ?></h3>
                    
                    <div class="wf-form-row">
                        <div class="wf-form-col">
                            <div class="wf-validation-rule" data-field-type="text" style="<?php echo $field['field_type'] !== 'text' ? 'display: none;' : ''; ?>">
                                <label for="validation_min_length">
                                    <strong><?php _e('حداقل طول', 'workforce-beni-asad'); ?></strong>
                                </label>
                                <input type="number" 
                                       id="validation_min_length" 
                                       name="validation_rules[min_length]" 
                                       value="<?php echo esc_attr($field['validation_rules']['min_length'] ?? ''); ?>" 
                                       class="small-text" 
                                       min="1">
                                
                                <label for="validation_max_length">
                                    <strong><?php _e('حداکثر طول', 'workforce-beni-asad'); ?></strong>
                                </label>
                                <input type="number" 
                                       id="validation_max_length" 
                                       name="validation_rules[max_length]" 
                                       value="<?php echo esc_attr($field['validation_rules']['max_length'] ?? ''); ?>" 
                                       class="small-text" 
                                       min="1">
                            </div>
                            
                            <div class="wf-validation-rule" data-field-type="number,decimal" style="<?php echo !in_array($field['field_type'], array('number', 'decimal')) ? 'display: none;' : ''; ?>">
                                <label for="validation_min">
                                    <strong><?php _e('حداقل مقدار', 'workforce-beni-asad'); ?></strong>
                                </label>
                                <input type="number" 
                                       id="validation_min" 
                                       name="validation_rules[min]" 
                                       value="<?php echo esc_attr($field['validation_rules']['min'] ?? ''); ?>" 
                                       class="small-text" 
                                       step="any">
                                
                                <label for="validation_max">
                                    <strong><?php _e('حداکثر مقدار', 'workforce-beni-asad'); ?></strong>
                                </label>
                                <input type="number" 
                                       id="validation_max" 
                                       name="validation_rules[max]" 
                                       value="<?php echo esc_attr($field['validation_rules']['max'] ?? ''); ?>" 
                                       class="small-text" 
                                       step="any">
                                
                                <?php if ($field['field_type'] === 'number'): ?>
                                    <label class="wf-checkbox-label">
                                        <input type="checkbox" 
                                               name="validation_rules[integer]" 
                                               value="1" 
                                               <?php checked($field['validation_rules']['integer'] ?? 0, 1); ?>>
                                        <span class="wf-checkbox-text">
                                            <?php _e('عدد صحیح', 'workforce-beni-asad'); ?>
                                        </span>
                                    </label>
                                <?php endif; ?>
                                
                                <?php if ($field['field_type'] === 'decimal'): ?>
                                    <label for="validation_precision">
                                        <strong><?php _e('تعداد اعشار', 'workforce-beni-asad'); ?></strong>
                                    </label>
                                    <input type="number" 
                                           id="validation_precision" 
                                           name="validation_rules[precision]" 
                                           value="<?php echo esc_attr($field['validation_rules']['precision'] ?? ''); ?>" 
                                           class="small-text" 
                                           min="0" 
                                           max="10">
                                <?php endif; ?>
                            </div>
                            
                            <div class="wf-validation-rule" data-field-type="date">
                                <p><?php _e('تاریخ به صورت شمسی اعتبارسنجی می‌شود.', 'workforce-beni-asad'); ?></p>
                            </div>
                            
                            <div class="wf-validation-rule" data-field-type="select">
                                <p><?php _e('گزینه‌ها باید از لیست تعریف شده انتخاب شوند.', 'workforce-beni-asad'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($is_edit): ?>
                <div class="wf-field-stats">
                    <h3><?php _e('آمار استفاده', 'workforce-beni-asad'); ?></h3>
                    
                    <div class="wf-form-row">
                        <div class="wf-form-col">
                            <?php
                            $usage_stats = wf_get_field_usage_stats($field_id);
                            ?>
                            <div class="wf-stats-grid">
                                <div class="wf-stat-item">
                                    <div class="wf-stat-label"><?php _e('تعداد پرسنل دارای مقدار', 'workforce-beni-asad'); ?></div>
                                    <div class="wf-stat-value"><?php echo $usage_stats['filled_count']; ?></div>
                                </div>
                                
                                <div class="wf-stat-item">
                                    <div class="wf-stat-label"><?php _e('پرسنل فاقد مقدار', 'workforce-beni-asad'); ?></div>
                                    <div class="wf-stat-value"><?php echo $usage_stats['empty_count']; ?></div>
                                </div>
                                
                                <div class="wf-stat-item">
                                    <div class="wf-stat-label"><?php _e('درصد پر شدن', 'workforce-beni-asad'); ?></div>
                                    <div class="wf-stat-value"><?php echo $usage_stats['fill_percentage']; ?>%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <style>
        .wf-field-editor {
            max-width: 1000px;
            margin: 20px 0;
        }
        
        .wf-field-editor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px 4px 0 0;
            border-bottom: none;
        }
        
        .wf-field-editor-header h2 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .wf-form-actions {
            display: flex;
            gap: 10px;
        }
        
        .wf-field-editor-content {
            padding: 20px;
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 0 0 4px 4px;
        }
        
        .wf-field-editor-content h3 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #dcdcde;
            color: #1d2327;
        }
        
        .wf-field-basic-settings,
        .wf-field-advanced-settings,
        .wf-field-options-section,
        .wf-field-validation-section,
        .wf-field-stats {
            margin-bottom: 30px;
        }
        
        .wf-form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .wf-form-col {
            flex: 1;
            min-width: 0;
        }
        
        .wf-form-col label {
            display: block;
            margin-bottom: 5px;
        }
        
        .wf-form-col label strong {
            color: #1d2327;
            display: block;
        }
        
        .wf-form-col .description {
            font-size: 12px;
            color: #646970;
            display: block;
            margin-top: 3px;
        }
        
        .wf-checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 15px;
            cursor: pointer;
        }
        
        .wf-checkbox-label input[type="checkbox"] {
            margin-top: 2px;
        }
        
        .wf-checkbox-text {
            flex: 1;
        }
        
        .wf-checkbox-text .description {
            margin-top: 3px;
        }
        
        .wf-validation-rule {
            margin-bottom: 15px;
        }
        
        .wf-validation-rule label {
            display: inline-block;
            margin-right: 15px;
        }
        
        .wf-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .wf-stat-item {
            padding: 15px;
            background: #f6f7f7;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            text-align: center;
        }
        
        .wf-stat-label {
            font-size: 12px;
            color: #646970;
            margin-bottom: 5px;
        }
        
        .wf-stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #2271b1;
        }
        
        @media (max-width: 782px) {
            .wf-field-editor-header {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            
            .wf-form-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .wf-stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // نمایش/پنهان کردن بخش‌ها بر اساس نوع فیلد
        $('#field_type').on('change', function() {
            var fieldType = $(this).val();
            
            // گزینه‌های انتخابی
            if (fieldType === 'select') {
                $('#field-options-section').show();
            } else {
                $('#field-options-section').hide();
            }
            
            // اعتبارسنجی
            $('.wf-validation-rule').hide();
            $('.wf-validation-rule[data-field-type*="' + fieldType + '"]').show();
        });
        
        // تولید خودکار کلید فیلد از عنوان
        $('#field_name').on('blur', function() {
            if (!$('#field_key').val() || !$('#field_key').attr('readonly')) {
                var fieldName = $(this).val();
                var fieldKey = wf_generate_field_key(fieldName);
                $('#field_key').val(fieldKey);
            }
        });
        
        // اعتبارسنجی فرم
        $('#wf-field-form').on('submit', function(e) {
            var fieldKey = $('#field_key').val();
            var fieldName = $('#field_name').val();
            
            if (!fieldName.trim()) {
                alert('لطفاً عنوان فیلد را وارد کنید');
                e.preventDefault();
                return;
            }
            
            if (!fieldKey.trim()) {
                alert('لطفاً کلید فیلد را وارد کنید');
                e.preventDefault();
                return;
            }
            
            // بررسی فرمت کلید فیلد
            if (!/^[a-z][a-z0-9_]*$/.test(fieldKey)) {
                alert('کلید فیلد باید با حروف انگلیسی کوچک شروع شود و فقط شامل حروف کوچک، اعداد و زیرخط باشد');
                e.preventDefault();
                return;
            }
        });
        
        // تابع تولید کلید فیلد
        function wf_generate_field_key(text) {
            // تبدیل فارسی به انگلیسی (ساده‌سازی)
            var persianMap = {
                'ا': 'a', 'آ': 'a', 'ب': 'b', 'پ': 'p', 'ت': 't', 'ث': 's',
                'ج': 'j', 'چ': 'ch', 'ح': 'h', 'خ': 'kh', 'د': 'd', 'ذ': 'z',
                'ر': 'r', 'ز': 'z', 'ژ': 'zh', 'س': 's', 'ش': 'sh', 'ص': 's',
                'ض': 'z', 'ط': 't', 'ظ': 'z', 'ع': 'a', 'غ': 'gh', 'ف': 'f',
                'ق': 'gh', 'ک': 'k', 'گ': 'g', 'ل': 'l', 'م': 'm', 'ن': 'n',
                'و': 'v', 'ه': 'h', 'ی': 'y', ' ': '_'
            };
            
            var result = '';
            for (var i = 0; i < text.length; i++) {
                var char = text.charAt(i);
                result += persianMap[char] || char;
            }
            
            // حذف کاراکترهای غیرمجاز
            result = result.replace(/[^a-z0-9_]/g, '');
            
            // حذف زیرخط‌های تکراری
            result = result.replace(/_+/g, '_');
            
            // حذف زیرخط از ابتدا و انتها
            result = result.replace(/^_+|_+$/g, '');
            
            return result;
        }
    });
    </script>
    <?php
}

/**
 * پردازش اقدامات فیلد
 */
function wf_process_field_action($data) {
    if (!wp_verify_nonce($data['wf_field_nonce'], 'wf_save_field')) {
        return array(
            'success' => false,
            'message' => __('اعتبارسنجی نامعتبر است.', 'workforce-beni-asad')
        );
    }
    
    $action = $data['wf_field_action'];
    $field_id = isset($data['field_id']) ? intval($data['field_id']) : 0;
    
    // آماده‌سازی داده‌ها
    $field_data = array(
        'field_name' => sanitize_text_field($data['field_name']),
        'field_key' => sanitize_key($data['field_key']),
        'field_type' => sanitize_text_field($data['field_type']),
        'display_order' => intval($data['display_order']),
        'description' => sanitize_textarea_field($data['description']),
        'is_required' => isset($data['is_required']) ? 1 : 0,
        'is_locked' => isset($data['is_locked']) ? 1 : 0,
        'is_monitoring' => isset($data['is_monitoring']) ? 1 : 0,
        'is_key' => isset($data['is_key']) ? 1 : 0,
        'status' => 'active'
    );
    
    // گزینه‌های انتخابی
    if (!empty($data['field_options'])) {
        $options = array_map('trim', explode("\n", $data['field_options']));
        $options = array_filter($options);
        $field_data['field_options'] = $options;
    }
    
    // قوانین اعتبارسنجی
    if (!empty($data['validation_rules'])) {
        $validation_rules = array();
        
        foreach ($data['validation_rules'] as $key => $value) {
            if ($value !== '') {
                $validation_rules[$key] = $value;
            }
        }
        
        if (!empty($validation_rules)) {
            $field_data['validation_rules'] = $validation_rules;
        }
    }
    
    if ($action === 'add') {
        $result = wf_create_field($field_data);
    } else {
        $result = wf_update_field($field_id, $field_data);
    }
    
    return $result;
}

/**
 * صفحه مدیریت ادارات
 */
function wf_admin_departments_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('شما مجوز دسترسی به این صفحه را ندارید.', 'workforce-beni-asad'));
    }
    
    // دریافت پارامترهای صفحه
    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
    $department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
    
    // پردازش اقدامات
    if (isset($_POST['wf_department_action'])) {
        $action_result = wf_process_department_action($_POST);
        if ($action_result['success']) {
            add_settings_error('wf_departments', 'wf_department_success', $action_result['message'], 'success');
        } else {
            add_settings_error('wf_departments', 'wf_department_error', $action_result['message'], 'error');
        }
    }
    
    // حذف اداره
    if (isset($_GET['delete_department']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_department_' . $_GET['delete_department'])) {
        $delete_result = wf_delete_department(intval($_GET['delete_department']), isset($_GET['permanent']));
        if ($delete_result['success']) {
            add_settings_error('wf_departments', 'wf_delete_success', $delete_result['message'], 'success');
        } else {
            add_settings_error('wf_departments', 'wf_delete_error', $delete_result['message'], 'error');
        }
    }
    
    settings_errors('wf_departments');
    
    ?>
    <div class="wrap workforce-admin-wrap">
        <h1 class="wp-heading-inline">
            <i class="dashicons dashicons-building"></i>
            <?php _e('مدیریت ادارات', 'workforce-beni-asad'); ?>
        </h1>
        
        <?php if ($action === 'edit' || $action === 'add'): ?>
            <?php wf_render_department_editor($department_id); ?>
        <?php else: ?>
            <?php wf_render_departments_list(); ?>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * رندر لیست ادارات
 */
function wf_render_departments_list() {
    // دریافت پارامترهای فیلتر
    $filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'all';
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    
    // دریافت ادارات
    $departments = wf_get_all_departments();
    
    // اعمال فیلترها
    if ($filter === 'no_manager') {
        $departments = array_filter($departments, function($dept) {
            return empty($dept['manager_id']);
        });
    } elseif ($filter === 'inactive') {
        $departments = array_filter($departments, function($dept) {
            return $dept['status'] === 'inactive';
        });
    }
    
    // اعمال جستجو
    if ($search) {
        $departments = array_filter($departments, function($dept) use ($search) {
            return stripos($dept['name'], $search) !== false || 
                   stripos($dept['code'] ?? '', $search) !== false ||
                   stripos($dept['description'], $search) !== false;
        });
    }
    
    // ساختار درختی
    $departments_tree = wf_get_department_tree();
    
    ?>
    <a href="<?php echo admin_url('admin.php?page=workforce-departments&action=add'); ?>" class="page-title-action">
        <i class="dashicons dashicons-plus"></i>
        <?php _e('افزودن اداره جدید', 'workforce-beni-asad'); ?>
    </a>
    
    <a href="<?php echo admin_url('admin.php?page=workforce-departments&view=tree'); ?>" class="page-title-action">
        <i class="dashicons dashicons-networking"></i>
        <?php _e('نمایش درختی', 'workforce-beni-asad'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <?php if (isset($_GET['view']) && $_GET['view'] === 'tree'): ?>
        <?php wf_render_departments_tree($departments_tree); ?>
    <?php else: ?>
        <div class="wf-admin-filters">
            <div class="wf-filter-tabs">
                <a href="<?php echo admin_url('admin.php?page=workforce-departments'); ?>" 
                   class="wf-filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    <?php _e('همه ادارات', 'workforce-beni-asad'); ?>
                    <span class="count">(<?php echo count($departments); ?>)</span>
                </a>
                <a href="<?php echo admin_url('admin.php?page=workforce-departments&filter=no_manager'); ?>" 
                   class="wf-filter-tab <?php echo $filter === 'no_manager' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-admin-users" style="color: #d63638;"></span>
                    <?php _e('بدون مدیر', 'workforce-beni-asad'); ?>
                    <span class="count">(<?php echo count(array_filter($departments, function($dept) { return empty($dept['manager_id']); })); ?>)</span>
                </a>
                <a href="<?php echo admin_url('admin.php?page=workforce-departments&filter=inactive'); ?>" 
                   class="wf-filter-tab <?php echo $filter === 'inactive' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-hidden"></span>
                    <?php _e('غیرفعال', 'workforce-beni-asad'); ?>
                    <span class="count">(<?php echo count(array_filter($departments, function($dept) { return $dept['status'] === 'inactive'; })); ?>)</span>
                </a>
            </div>
            
            <form method="get" class="wf-search-form">
                <input type="hidden" name="page" value="workforce-departments">
                <input type="search" 
                       name="s" 
                       value="<?php echo esc_attr($search); ?>" 
                       placeholder="<?php esc_attr_e('جستجو در ادارات...', 'workforce-beni-asad'); ?>"
                       class="wf-search-input">
                <button type="submit" class="button">
                    <span class="dashicons dashicons-search"></span>
                </button>
                <?php if ($search): ?>
                    <a href="<?php echo admin_url('admin.php?page=workforce-departments'); ?>" class="button">
                        <?php _e('پاک کردن', 'workforce-beni-asad'); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="wf-departments-table-wrap">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="column-cb check-column">
                            <input type="checkbox" id="cb-select-all-2">
                        </th>
                        <th scope="col" class="column-name"><?php _e('اداره', 'workforce-beni-asad'); ?></th>
                        <th scope="col" class="column-manager"><?php _e('مدیر', 'workforce-beni-asad'); ?></th>
                        <th scope="col" class="column-stats"><?php _e('آمار', 'workforce-beni-asad'); ?></th>
                        <th scope="col" class="column-status"><?php _e('وضعیت', 'workforce-beni-asad'); ?></th>
                        <th scope="col" class="column-actions"><?php _e('عملیات', 'workforce-beni-asad'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($departments)): ?>
                        <tr>
                            <td colspan="6" class="no-items">
                                <?php _e('هیچ اداره‌ای یافت نشد.', 'workforce-beni-asad'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($departments as $dept): ?>
                            <?php 
                            $manager = $dept['manager_id'] ? get_user_by('id', $dept['manager_id']) : null;
                            $personnel_count = wf_count_department_personnel($dept['id']);
                            $children_count = wf_count_department_children($dept['id']);
                            ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="department_ids[]" value="<?php echo $dept['id']; ?>">
                                </th>
                                <td class="column-name column-primary">
                                    <strong class="row-title">
                                        <span class="wf-department-color" style="background-color: <?php echo esc_attr($dept['color']); ?>"></span>
                                        <?php echo esc_html($dept['name']); ?>
                                        <?php if ($dept['code']): ?>
                                            <code class="wf-department-code"><?php echo esc_html($dept['code']); ?></code>
                                        <?php endif; ?>
                                    </strong>
                                    <div class="row-actions">
                                        <?php if ($dept['description']): ?>
                                            <div class="wf-department-description">
                                                <?php echo esc_html($dept['description']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($dept['parent_id']): ?>
                                            <?php 
                                            $parent = wf_get_department($dept['parent_id']);
                                            if ($parent): ?>
                                                <div class="wf-department-parent">
                                                    <span class="dashicons dashicons-arrow-up-alt"></span>
                                                    <?php echo esc_html($parent['name']); ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="column-manager">
                                    <?php if ($manager): ?>
                                        <div class="wf-department-manager">
                                            <div class="wf-manager-name"><?php echo esc_html($manager->display_name); ?></div>
                                            <div class="wf-manager-email"><?php echo esc_html($manager->user_email); ?></div>
                                        </div>
                                    <?php else: ?>
                                        <span class="wf-no-manager">
                                            <span class="dashicons dashicons-warning"></span>
                                            <?php _e('بدون مدیر', 'workforce-beni-asad'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-stats">
                                    <div class="wf-department-stats">
                                        <div class="wf-stat-item">
                                            <span class="wf-stat-icon dashicons dashicons-groups"></span>
                                            <span class="wf-stat-value"><?php echo $personnel_count; ?></span>
                                            <span class="wf-stat-label"><?php _e('پرسنل', 'workforce-beni-asad'); ?></span>
                                        </div>
                                        <div class="wf-stat-item">
                                            <span class="wf-stat-icon dashicons dashicons-networking"></span>
                                            <span class="wf-stat-value"><?php echo $children_count; ?></span>
                                            <span class="wf-stat-label"><?php _e('زیرمجموعه', 'workforce-beni-asad'); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="column-status">
                                    <?php if ($dept['status'] === 'active'): ?>
                                        <span class="wf-status-active">
                                            <span class="dashicons dashicons-yes"></span>
                                            <?php _e('فعال', 'workforce-beni-asad'); ?>
                                        </span>
                                    <?php elseif ($dept['status'] === 'inactive'): ?>
                                        <span class="wf-status-inactive">
                                            <span class="dashicons dashicons-hidden"></span>
                                            <?php _e('غیرفعال', 'workforce-beni-asad'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="wf-status-suspended">
                                            <span class="dashicons dashicons-no"></span>
                                            <?php _e('تعلیق', 'workforce-beni-asad'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-actions">
                                    <div class="wf-action-buttons">
                                        <a href="<?php echo admin_url('admin.php?page=workforce-departments&action=edit&department_id=' . $dept['id']); ?>" 
                                           class="button button-small">
                                            <span class="dashicons dashicons-edit"></span>
                                            <?php _e('ویرایش', 'workforce-beni-asad'); ?>
                                        </a>
                                        
                                        <a href="<?php echo admin_url('admin.php?page=workforce-personnel&department=' . $dept['id']); ?>" 
                                           class="button button-small">
                                            <span class="dashicons dashicons-groups"></span>
                                            <?php _e('پرسنل', 'workforce-beni-asad'); ?>
                                        </a>
                                        
                                        <?php if ($dept['status'] === 'active'): ?>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=workforce-departments&deactivate_department=' . $dept['id']), 'deactivate_department_' . $dept['id']); ?>" 
                                               class="button button-small">
                                                <span class="dashicons dashicons-hidden"></span>
                                                <?php _e('غیرفعال', 'workforce-beni-asad'); ?>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=workforce-departments&activate_department=' . $dept['id']), 'activate_department_' . $dept['id']); ?>" 
                                               class="button button-small">
                                                <span class="dashicons dashicons-visibility"></span>
                                                <?php _e('فعال', 'workforce-beni-asad'); ?>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=workforce-departments&delete_department=' . $dept['id']), 'delete_department_' . $dept['id']); ?>" 
                                           class="button button-small button-link-delete"
                                           onclick="return confirm('<?php esc_attr_e('آیا از حذف این اداره اطمینان دارید؟', 'workforce-beni-asad'); ?>')">
                                            <span class="dashicons dashicons-trash"></span>
                                            <?php _e('حذف', 'workforce-beni-asad'); ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <style>
        .wf-departments-table-wrap {
            margin: 20px 0;
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .wf-department-color {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-left: 8px;
            vertical-align: middle;
        }
        
        .wf-department-code {
            background: #f6f7f7;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            color: #50575e;
            margin-right: 8px;
        }
        
        .wf-department-description {
            font-size: 12px;
            color: #646970;
            margin-top: 5px;
            line-height: 1.4;
        }
        
        .wf-department-parent {
            font-size: 11px;
            color: #8c8f94;
            margin-top: 3px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .wf-department-manager {
            line-height: 1.4;
        }
        
        .wf-manager-name {
            font-weight: 500;
            color: #2c3338;
        }
        
        .wf-manager-email {
            font-size: 11px;
            color: #646970;
        }
        
        .wf-no-manager {
            color: #d63638;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
        }
        
        .wf-department-stats {
            display: flex;
            gap: 15px;
        }
        
        .wf-stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 60px;
        }
        
        .wf-stat-icon {
            font-size: 16px;
            color: #50575e;
            margin-bottom: 3px;
        }
        
        .wf-stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #2271b1;
            line-height: 1;
        }
        
        .wf-stat-label {
            font-size: 11px;
            color: #646970;
            margin-top: 2px;
        }
        
        .wf-status-suspended {
            color: #dba617;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .wf-tree-view {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .wf-tree-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dcdcde;
        }
        
        .wf-tree-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .wf-tree-container {
            max-height: 600px;
            overflow-y: auto;
            padding: 10px;
        }
        
        .wf-tree-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .wf-tree-list ul {
            list-style: none;
            padding-right: 20px;
            margin: 5px 0;
        }
        
        .wf-tree-item {
            padding: 10px 15px;
            margin: 5px 0;
            background: #f6f7f7;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .wf-tree-item-content {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .wf-tree-item-color {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        
        .wf-tree-item-name {
            font-weight: 500;
            color: #2c3338;
        }
        
        .wf-tree-item-stats {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #646970;
        }
        
        .wf-tree-item-actions {
            display: flex;
            gap: 5px;
        }
        
        .wf-tree-toggle {
            cursor: pointer;
            color: #50575e;
            margin-left: 10px;
        }
        
        .wf-tree-toggle:hover {
            color: #2271b1;
        }
        
        .wf-tree-children {
            display: none;
        }
        
        .wf-tree-children.expanded {
            display: block;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // نمایش درختی ادارات
        $('.wf-tree-toggle').on('click', function() {
            var $children = $(this).closest('.wf-tree-item').next('.wf-tree-children');
            var $icon = $(this).find('.dashicons');
            
            if ($children.hasClass('expanded')) {
                $children.removeClass('expanded').slideUp();
                $icon.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-right');
            } else {
                $children.addClass('expanded').slideDown();
                $icon.removeClass('dashicons-arrow-right').addClass('dashicons-arrow-down');
            }
        });
        
        // عملیات گروهی ادارات
        $('#wf-apply-bulk-action').on('click', function() {
            var action = $('select[name="wf_bulk_action"]').val();
            var selected = $('input[name="department_ids[]"]:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (!action) {
                alert('لطفاً یک عملیات انتخاب کنید');
                return;
            }
            
            if (selected.length === 0) {
                alert('لطفاً حداقل یک اداره را انتخاب کنید');
                return;
            }
            
            if (action === 'delete' && !confirm('آیا از حذف ادارات انتخاب شده اطمینان دارید؟')) {
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wf_bulk_departments_action',
                    bulk_action: action,
                    department_ids: selected,
                    nonce: '<?php echo wp_create_nonce('wf_bulk_departments_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'خطا در انجام عملیات');
                    }
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * رندر ساختار درختی ادارات
 */
function wf_render_departments_tree($departments_tree, $level = 0) {
    ?>
    <div class="wf-tree-view">
        <div class="wf-tree-header">
            <h3>
                <i class="dashicons dashicons-networking"></i>
                <?php _e('ساختار سازمانی', 'workforce-beni-asad'); ?>
            </h3>
            <a href="<?php echo admin_url('admin.php?page=workforce-departments'); ?>" class="button">
                <i class="dashicons dashicons-list-view"></i>
                <?php _e('نمایش لیستی', 'workforce-beni-asad'); ?>
            </a>
        </div>
        
        <div class="wf-tree-container">
            <ul class="wf-tree-list">
                <?php if (empty($departments_tree)): ?>
                    <li class="wf-tree-item">
                        <div class="wf-tree-item-content">
                            <span class="wf-tree-item-name"><?php _e('هیچ اداره‌ای ثبت نشده است.', 'workforce-beni-asad'); ?></span>
                        </div>
                    </li>
                <?php else: ?>
                    <?php foreach ($departments_tree as $dept): ?>
                        <?php 
                        $manager = $dept['manager_id'] ? get_user_by('id', $dept['manager_id']) : null;
                        $personnel_count = wf_count_department_personnel($dept['id']);
                        $children_count = count($dept['children']);
                        ?>
                        <li>
                            <div class="wf-tree-item">
                                <div class="wf-tree-item-content">
                                    <span class="wf-tree-item-color" style="background-color: <?php echo esc_attr($dept['color']); ?>"></span>
                                    <span class="wf-tree-item-name"><?php echo esc_html($dept['name']); ?></span>
                                    <?php if ($dept['code']): ?>
                                        <code class="wf-department-code"><?php echo esc_html($dept['code']); ?></code>
                                    <?php endif; ?>
                                    
                                    <div class="wf-tree-item-stats">
                                        <span title="<?php esc_attr_e('تعداد پرسنل', 'workforce-beni-asad'); ?>">
                                            <i class="dashicons dashicons-groups"></i> <?php echo $personnel_count; ?>
                                        </span>
                                        <span title="<?php esc_attr_e('تعداد زیرمجموعه', 'workforce-beni-asad'); ?>">
                                            <i class="dashicons dashicons-networking"></i> <?php echo $children_count; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="wf-tree-item-actions">
                                    <?php if ($children_count > 0): ?>
                                        <span class="wf-tree-toggle">
                                            <i class="dashicons dashicons-arrow-right"></i>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <a href="<?php echo admin_url('admin.php?page=workforce-departments&action=edit&department_id=' . $dept['id']); ?>" 
                                       class="button button-small">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>
                                </div>
                            </div>
                            
                            <?php if ($children_count > 0): ?>
                                <div class="wf-tree-children">
                                    <?php wf_render_departments_tree($dept['children'], $level + 1); ?>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * رندر ویرایشگر اداره
 */
function wf_render_department_editor($department_id = 0) {
    $department = $department_id ? wf_get_department($department_id) : array();
    $is_edit = !empty($department);
    
    // تنظیمات پیش‌فرض
    $defaults = array(
        'name' => '',
        'code' => '',
        'manager_id' => '',
        'parent_id' => '',
        'color' => '#1a73e8',
        'description' => '',
        'status' => 'active'
    );
    
    $department = array_merge($defaults, $department);
    
    // دریافت لیست مدیران
    $managers = wf_get_department_managers();
    
    // دریافت لیست ادارات برای والد
    $departments = wf_get_all_departments(array('status' => 'active'));
    $parent_options = array('' => __('بدون والد (سطح اول)', 'workforce-beni-asad'));
    foreach ($departments as $dept) {
        if (!$is_edit || $dept['id'] != $department_id) {
            $parent_options[$dept['id']] = $dept['name'];
        }
    }
    
    ?>
    <div class="wf-field-editor">
        <form method="post" action="" id="wf-department-form">
            <?php wp_nonce_field('wf_save_department', 'wf_department_nonce'); ?>
            <input type="hidden" name="wf_department_action" value="<?php echo $is_edit ? 'edit' : 'add'; ?>">
            <input type="hidden" name="department_id" value="<?php echo $department_id; ?>">
            
            <div class="wf-field-editor-header">
                <h2>
                    <?php if ($is_edit): ?>
                        <i class="dashicons dashicons-edit"></i>
                        <?php printf(__('ویرایش اداره: %s', 'workforce-beni-asad'), esc_html($department['name'])); ?>
                    <?php else: ?>
                        <i class="dashicons dashicons-plus"></i>
                        <?php _e('افزودن اداره جدید', 'workforce-beni-asad'); ?>
                    <?php endif; ?>
                </h2>
                <div class="wf-form-actions">
                    <a href="<?php echo admin_url('admin.php?page=workforce-departments'); ?>" class="button">
                        <?php _e('انصراف', 'workforce-beni-asad'); ?>
                    </a>
                    <button type="submit" class="button button-primary">
                        <i class="dashicons dashicons-yes"></i>
                        <?php echo $is_edit ? __('ذخیره تغییرات', 'workforce-beni-asad') : __('ایجاد اداره', 'workforce-beni-asad'); ?>
                    </button>
                </div>
            </div>
            
            <div class="wf-field-editor-content">
                <div class="wf-field-basic-settings">
                    <h3><?php _e('اطلاعات اصلی', 'workforce-beni-asad'); ?></h3>
                    
                    <div class="wf-form-row">
                        <div class="wf-form-col">
                            <label for="department_name">
                                <strong><?php _e('نام اداره', 'workforce-beni-asad'); ?> *</strong>
                                <span class="description"><?php _e('نام فارسی کامل اداره', 'workforce-beni-asad'); ?></span>
                            </label>
                            <input type="text" 
                                   id="department_name" 
                                   name="name" 
                                   value="<?php echo esc_attr($department['name']); ?>" 
                                   class="regular-text" 
                                   required>
                        </div>
                        
                        <div class="wf-form-col">
                            <label for="department_code">
                                <strong><?php _e('کد اداره', 'workforce-beni-asad'); ?></strong>
                                <span class="description"><?php _e('کد یکتا برای شناسایی (اختیاری)', 'workforce-beni-asad'); ?></span>
                            </label>
                            <input type="text" 
                                   id="department_code" 
                                   name="code" 
                                   value="<?php echo esc_attr($department['code']); ?>" 
                                   class="regular-text">
                        </div>
                    </div>
                    
                    <div class="wf-form-row">
                        <div class="wf-form-col">
                            <label for="department_color">
                                <strong><?php _e('رنگ اداره', 'workforce-beni-asad'); ?></strong>
                                <span class="description"><?php _e('برای نمایش در نمودارها و کارت‌ها', 'workforce-beni-asad'); ?></span>
                            </label>
                            <input type="color" 
                                   id="department_color" 
                                   name="color" 
                                   value="<?php echo esc_attr($department['color']); ?>" 
                                   class="wf-color-picker">
                            <div class="wf-color-preview" style="background-color: <?php echo esc_attr($department['color']); ?>"></div>
                        </div>
                        
                        <div class="wf-form-col">
                            <label for="department_parent">
                                <strong><?php _e('اداره والد', 'workforce-beni-asad'); ?></strong>
                                <span class="description"><?php _e('در صورت وجود سلسله مراتب', 'workforce-beni-asad'); ?></span>
                            </label>
                            <select id="department_parent" name="parent_id" class="regular-text">
                                <?php foreach ($parent_options as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($department['parent_id'], $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="wf-form-row">
                        <div class="wf-form-col">
                            <label for="department_manager">
                                <strong><?php _e('مدیر اداره', 'workforce-beni-asad'); ?></strong>
                                <span class="description"><?php _e('انتخاب کاربر به عنوان مدیر این اداره', 'workforce-beni-asad'); ?></span>
                            </label>
                            <select id="department_manager" name="manager_id" class="regular-text">
                                <option value=""><?php _e('انتخاب مدیر...', 'workforce-beni-asad'); ?></option>
                                <?php foreach ($managers as $manager): ?>
                                    <option value="<?php echo esc_attr($manager['id']); ?>" 
                                            <?php selected($department['manager_id'], $manager['id']); ?>
                                            data-department="<?php echo esc_attr($manager['department_name']); ?>">
                                        <?php echo esc_html($manager['name']); ?> 
                                        (<?php echo esc_html($manager['email']); ?>)
                                        <?php if ($manager['department_name']): ?>
                                            - <?php echo esc_html($manager['department_name']); ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <i class="dashicons dashicons-info"></i>
                                <?php _e('انتخاب مدیر، نقش "مدیر اداره" را به کاربر می‌دهد.', 'workforce-beni-asad'); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="wf-form-row">
                        <div class="wf-form-col">
                            <label for="department_description">
                                <strong><?php _e('توضیحات', 'workforce-beni-asad'); ?></strong>
                                <span class="description"><?php _e('توضیحات اختیاری درباره اداره', 'workforce-beni-asad'); ?></span>
                            </label>
                            <textarea id="department_description" 
                                      name="description" 
                                      rows="4" 
                                      class="large-text"><?php echo esc_textarea($department['description']); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <?php if ($is_edit): ?>
                <div class="wf-field-stats">
                    <h3><?php _e('آمار اداره', 'workforce-beni-asad'); ?></h3>
                    
                    <div class="wf-form-row">
                        <div class="wf-form-col">
                            <?php
                            $personnel_count = wf_count_department_personnel($department_id);
                            $active_personnel = wf_count_department_personnel($department_id, array('status' => 'active'));
                            $completion_percentage = wf_update_department_completion_percentage($department_id);
                            $children_count = wf_count_department_children($department_id);
                            ?>
                            <div class="wf-stats-grid">
                                <div class="wf-stat-item">
                                    <div class="wf-stat-label"><?php _e('کل پرسنل', 'workforce-beni-asad'); ?></div>
                                    <div class="wf-stat-value"><?php echo $personnel_count; ?></div>
                                </div>
                                
                                <div class="wf-stat-item">
                                    <div class="wf-stat-label"><?php _e('پرسنل فعال', 'workforce-beni-asad'); ?></div>
                                    <div class="wf-stat-value"><?php echo $active_personnel; ?></div>
                                </div>
                                
                                <div class="wf-stat-item">
                                    <div class="wf-stat-label"><?php _e('میانگین تکمیل', 'workforce-beni-asad'); ?></div>
                                    <div class="wf-stat-value"><?php echo round($completion_percentage, 1); ?>%</div>
                                    <div class="wf-progress-bar">
                                        <div class="wf-progress-fill" style="width: <?php echo $completion_percentage; ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="wf-stat-item">
                                    <div class="wf-stat-label"><?php _e('زیرمجموعه', 'workforce-beni-asad'); ?></div>
                                    <div class="wf-stat-value"><?php echo $children_count; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <style>
        .wf-color-picker {
            width: 60px;
            height: 40px;
            padding: 3px;
            vertical-align: middle;
        }
        
        .wf-color-preview {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            vertical-align: middle;
            margin-right: 10px;
        }
        
        #department_manager option {
            padding: 5px;
        }
        
        #department_manager option[data-department]::after {
            content: attr(data-department);
            float: left;
            color: #646970;
            font-size: 11px;
            margin-right: 10px;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // به‌روزرسانی پیش‌نمایش رنگ
        $('#department_color').on('change', function() {
            $('.wf-color-preview').css('background-color', $(this).val());
        });
        
        // اعتبارسنجی فرم
        $('#wf-department-form').on('submit', function(e) {
            var departmentName = $('#department_name').val();
            
            if (!departmentName.trim()) {
                alert('لطفاً نام اداره را وارد کنید');
                e.preventDefault();
                return;
            }
        });
        
        // فیلتر کردن مدیران بر اساس اداره
        $('#department_manager').select2({
            placeholder: 'انتخاب مدیر...',
            allowClear: true,
            width: '100%',
            templateResult: function(state) {
                if (!state.id) {
                    return state.text;
                }
                
                var $state = $(
                    '<div>' + state.text + '</div>'
                );
                
                return $state;
            }
        });
    });
    </script>
    <?php
}

// ==================== ادامه فایل در پیام بعدی ====================

// اینجا فایل ادامه دارد اما به دلیل محدودیت طول، ادامه آن در پاسخ بعدی ارسال می‌شود.
// بخش‌های باقی‌مانده شامل:
// 1. صفحه مدیریت پرسنل
// 2. صفحه دوره‌های کارکرد  
// 3. صفحه تایید درخواست‌ها
// 4. صفحه قالب‌های اکسل
// 5. صفحه تنظیمات سیستم
// 6. صفحه لاگ‌ها
// 7. صفحه پشتیبان‌گیری
// 8. صفحه ورود اطلاعات
// 9. صفحه گزارش‌های پیشرفته
// 10. توابع AJAX و پردازش‌گرها

?>