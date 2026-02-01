<?php
/**
 * پنل مدیریت ادمین سایت
 */

// بررسی دسترسی
if (!current_user_can('workforce_admin_panel')) {
    wp_die('دسترسی غیرمجاز!');
}

global $wpdb;
$db = WorkforceDatabase::get_instance();

// دریافت وضعیت فعلی
$action = $_GET['action'] ?? 'dashboard';
$tab = $_GET['tab'] ?? 'overview';
$item_id = intval($_GET['id'] ?? 0);

// پردازش فرم‌ها
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nonce = $_POST['_wpnonce'] ?? '';
    
    if (wp_verify_nonce($nonce, 'workforce_admin_action')) {
        $form_action = $_POST['form_action'] ?? '';
        
        switch ($form_action) {
            case 'save_field':
                $field_data = array(
                    'id' => intval($_POST['field_id'] ?? 0),
                    'field_key' => sanitize_key($_POST['field_key']),
                    'field_name' => sanitize_text_field($_POST['field_name']),
                    'field_type' => sanitize_text_field($_POST['field_type']),
                    'is_required' => isset($_POST['is_required']),
                    'is_main' => isset($_POST['is_main']),
                    'is_unique' => isset($_POST['is_unique']),
                    'is_editable' => isset($_POST['is_editable']),
                    'sort_order' => intval($_POST['sort_order'] ?? 0)
                );
                
                // مقادیر dropdown
                if ($field_data['field_type'] === 'dropdown' && !empty($_POST['dropdown_values'])) {
                    $values = array_map('trim', explode("\n", $_POST['dropdown_values']));
                    $values = array_filter($values);
                    $field_data['dropdown_values'] = $values;
                }
                
                $result = $db->save_field($field_data);
                
                if (!is_wp_error($result)) {
                    $message = 'فیلد با موفقیت ذخیره شد.';
                    $message_type = 'success';
                } else {
                    $message = 'خطا در ذخیره فیلد: ' . $result->get_error_message();
                    $message_type = 'error';
                }
                break;
                
            case 'save_department':
                $dept_data = array(
                    'id' => intval($_POST['department_id'] ?? 0),
                    'department_name' => sanitize_text_field($_POST['department_name']),
                    'department_code' => sanitize_text_field($_POST['department_code'] ?? ''),
                    'parent_id' => intval($_POST['parent_id'] ?? 0),
                    'manager_ids' => array_map('intval', $_POST['manager_ids'] ?? array()),
                    'is_active' => isset($_POST['is_active'])
                );
                
                $result = $db->save_department($dept_data);
                
                if (!is_wp_error($result)) {
                    $message = 'اداره با موفقیت ذخیره شد.';
                    $message_type = 'success';
                } else {
                    $message = 'خطا در ذخیره اداره: ' . $result->get_error_message();
                    $message_type = 'error';
                }
                break;
                
            case 'delete_field':
                $field_id = intval($_POST['field_id']);
                $wpdb->update(
                    $wpdb->prefix . 'workforce_fields',
                    array('is_active' => 0),
                    array('id' => $field_id)
                );
                $message = 'فیلد حذف شد.';
                $message_type = 'success';
                break;
                
            case 'delete_department':
                $dept_id = intval($_POST['department_id']);
                $wpdb->update(
                    $wpdb->prefix . 'workforce_departments',
                    array('is_active' => 0),
                    array('id' => $dept_id)
                );
                $message = 'اداره حذف شد.';
                $message_type = 'success';
                break;
        }
    }
}
?>

<div class="wrap workforce-admin-wrap">
    <!-- هدر -->
    <div class="workforce-admin-header">
        <div class="workforce-header-title">
            <svg class="workforce-logo-icon" width="40" height="40" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
            <h1>سامانه کارکرد پرسنل بنی اسد</h1>
        </div>
        
        <div class="workforce-header-stats">
            <div class="stat-card">
                <div class="stat-icon" style="background: #3b82f620; color: #3b82f6;">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <span class="stat-value" id="total-fields">0</span>
                    <span class="stat-label">فیلد تعریف شده</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #10b98120; color: #10b981;">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <span class="stat-value" id="total-departments">0</span>
                    <span class="stat-label">اداره فعال</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #8b5cf620; color: #8b5cf6;">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <span class="stat-value" id="active-periods">0</span>
                    <span class="stat-label">دوره فعال</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #f59e0b20; color: #f59e0b;">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <span class="stat-value" id="pending-approvals">0</span>
                    <span class="stat-label">در انتظار تأیید</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- تب‌های ناوبری -->
    <nav class="workforce-admin-tabs">
        <a href="?page=workforce-admin&action=dashboard" 
           class="nav-tab <?php echo $action === 'dashboard' ? 'nav-tab-active' : ''; ?>">
           <svg width="18" height="18" viewBox="0 0 24 24">
               <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
           </svg>
           داشبورد
        </a>
        
        <a href="?page=workforce-admin&action=fields" 
           class="nav-tab <?php echo $action === 'fields' ? 'nav-tab-active' : ''; ?>">
           <svg width="18" height="18" viewBox="0 0 24 24">
               <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
           </svg>
           مدیریت فیلدها
        </a>
        
        <a href="?page=workforce-admin&action=departments" 
           class="nav-tab <?php echo $action === 'departments' ? 'nav-tab-active' : ''; ?>">
           <svg width="18" height="18" viewBox="0 0 24 24">
               <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
           </svg>
           مدیریت ادارات
        </a>
        
        <a href="?page=workforce-admin&action=periods" 
           class="nav-tab <?php echo $action === 'periods' ? 'nav-tab-active' : ''; ?>">
           <svg width="18" height="18" viewBox="0 0 24 24">
               <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
           </svg>
           دوره‌های زمانی
        </a>
        
        <a href="?page=workforce-admin&action=reports" 
           class="nav-tab <?php echo $action === 'reports' ? 'nav-tab-active' : ''; ?>">
           <svg width="18" height="18" viewBox="0 0 24 24">
               <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
           </svg>
           گزارشات و خروجی
        </a>
        
        <a href="?page=workforce-admin&action=settings" 
           class="nav-tab <?php echo $action === 'settings' ? 'nav-tab-active' : ''; ?>">
           <svg width="18" height="18" viewBox="0 0 24 24">
               <path d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.23.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/>
           </svg>
           تنظیمات
        </a>
    </nav>
    
    <!-- پیام‌ها -->
    <?php if (!empty($message)): ?>
    <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php endif; ?>
    
    <!-- محتوای اصلی -->
    <div class="workforce-admin-content">
        <?php
        switch ($action) {
            case 'dashboard':
                $this->render_dashboard();
                break;
                
            case 'fields':
                if ($item_id > 0 && ($_GET['subaction'] ?? '') === 'edit') {
                    $this->render_field_editor($item_id);
                } else {
                    $this->render_fields_manager();
                }
                break;
                
            case 'departments':
                if ($item_id > 0 && ($_GET['subaction'] ?? '') === 'edit') {
                    $this->render_department_editor($item_id);
                } else {
                    $this->render_departments_manager();
                }
                break;
                
            case 'periods':
                $this->render_periods_manager();
                break;
                
            case 'reports':
                $this->render_reports_manager();
                break;
                
            case 'settings':
                $this->render_settings_manager();
                break;
                
            default:
                $this->render_dashboard();
        }
        ?>
    </div>
</div>

<?php
// توابع رندر
class WorkforceAdminRenderer {
    
    public static function render_dashboard() {
        global $wpdb, $db;
        ?>
        <div class="workforce-dashboard">
            <!-- کارت‌های اطلاعات کلی -->
            <div class="dashboard-grid">
                <div class="dashboard-card card-wide">
                    <div class="card-header">
                        <h3>
                            <svg width="20" height="20" viewBox="0 0 24 24">
                                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                            </svg>
                            آخرین فعالیت‌ها
                        </h3>
                        <a href="?page=workforce-admin&action=reports" class="card-link">
                            مشاهده همه
                            <svg width="16" height="16" viewBox="0 0 24 24">
                                <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
                            </svg>
                        </a>
                    </div>
                    <div class="card-content">
                        <div class="activity-list">
                            <?php
                            $activities = $wpdb->get_results("
                                SELECT al.*, u.display_name 
                                FROM {$wpdb->prefix}workforce_audit_log al
                                LEFT JOIN {$wpdb->prefix}users u ON al.user_id = u.ID
                                ORDER BY al.created_at DESC 
                                LIMIT 10
                            ", ARRAY_A);
                            
                            if ($activities):
                                foreach ($activities as $activity):
                                    $action_text = self::get_action_text($activity['action_type']);
                            ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <?php echo self::get_action_icon($activity['action_type']); ?>
                                </div>
                                <div class="activity-details">
                                    <div class="activity-title">
                                        <strong><?php echo esc_html($activity['display_name']); ?></strong>
                                        <?php echo $action_text; ?>
                                    </div>
                                    <div class="activity-meta">
                                        <span class="activity-time">
                                            <?php echo human_time_diff(strtotime($activity['created_at'])) . ' پیش'; ?>
                                        </span>
                                        <span class="activity-ip">
                                            <?php echo esc_html($activity['ip_address']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; else: ?>
                            <div class="no-data">هیچ فعالیتی ثبت نشده است</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>
                            <svg width="20" height="20" viewBox="0 0 24 24">
                                <path d="M9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4zm2.5 2.1h-15V5h15v14.1zm0-16.1h-15c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h15c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/>
                            </svg>
                            وضعیت ادارات
                        </h3>
                    </div>
                    <div class="card-content">
                        <?php
                        $departments = $db->get_departments(array('is_active' => 1));
                        
                        foreach ($departments as $dept):
                            $stats = $db->get_department_stats($dept['id']);
                            $completion = $stats['total'] > 0 ? round(($stats['verified_count'] / $stats['total']) * 100) : 0;
                        ?>
                        <div class="department-status">
                            <div class="dept-name">
                                <?php echo esc_html($dept['department_name']); ?>
                                <?php if ($dept['department_code']): ?>
                                <span class="dept-code">(<?php echo esc_html($dept['department_code']); ?>)</span>
                                <?php endif; ?>
                            </div>
                            <div class="dept-stats">
                                <span class="dept-count"><?php echo $stats['total']; ?> پرسنل</span>
                                <div class="dept-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $completion; ?>%"></div>
                                    </div>
                                    <span class="progress-text"><?php echo $completion; ?>%</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>
                            <svg width="20" height="20" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                            </svg>
                            در انتظار تأیید
                        </h3>
                    </div>
                    <div class="card-content">
                        <?php
                        $pending = $wpdb->get_results("
                            SELECT p.*, d.department_name 
                            FROM {$wpdb->prefix}workforce_personnel p
                            LEFT JOIN {$wpdb->prefix}workforce_departments d ON p.department_id = d.id
                            WHERE p.is_verified = 0 AND p.status = 'pending'
                            ORDER BY p.created_at DESC 
                            LIMIT 5
                        ", ARRAY_A);
                        
                        if ($pending):
                            foreach ($pending as $item):
                        ?>
                        <div class="pending-item">
                            <div class="pending-name">
                                <?php echo esc_html($item['first_name'] . ' ' . $item['last_name']); ?>
                                <span class="pending-dept"><?php echo esc_html($item['department_name']); ?></span>
                            </div>
                            <div class="pending-actions">
                                <a href="#" class="btn-approve" data-id="<?php echo $item['id']; ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24">
                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                    </svg>
                                </a>
                                <a href="#" class="btn-reject" data-id="<?php echo $item['id']; ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24">
                                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                        <div class="no-data">موردی برای تأیید وجود ندارد</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- نمودارها -->
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>توزیع پرسنل بر اساس اداره</h3>
                    </div>
                    <div class="card-content">
                        <canvas id="departmentChart" width="400" height="300"></canvas>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>وضعیت تکمیل اطلاعات</h3>
                    </div>
                    <div class="card-content">
                        <canvas id="completionChart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // آمار لحظه‌ای
            fetchStats();
            
            // نمودارها
            setTimeout(() => {
                renderCharts();
            }, 500);
        });
        
        function fetchStats() {
            jQuery.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'workforce_ajax',
                    action_type: 'get_stats',
                    nonce: workforceData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const stats = response.data;
                        document.getElementById('total-fields').textContent = stats.total_fields;
                        document.getElementById('total-departments').textContent = stats.total_departments;
                        document.getElementById('active-periods').textContent = stats.active_periods;
                        document.getElementById('pending-approvals').textContent = stats.pending_approvals;
                    }
                }
            });
        }
        
        function renderCharts() {
            // نمودار ادارات
            const deptCtx = document.getElementById('departmentChart').getContext('2d');
            new Chart(deptCtx, {
                type: 'doughnut',
                data: {
                    labels: ['اداره ۱', 'اداره ۲', 'اداره ۳'],
                    datasets: [{
                        data: [35, 28, 42],
                        backgroundColor: ['#3b82f6', '#10b981', '#8b5cf6']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            rtl: true
                        }
                    }
                }
            });
            
            // نمودار تکمیل
            const compCtx = document.getElementById('completionChart').getContext('2d');
            new Chart(compCtx, {
                type: 'bar',
                data: {
                    labels: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد'],
                    datasets: [{
                        label: 'درصد تکمیل',
                        data: [85, 79, 92, 88, 95],
                        backgroundColor: '#10b981'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }
        </script>
        <?php
    }
    
    public static function render_fields_manager() {
        global $db;
        $fields = $db->get_fields();
        ?>
        <div class="workforce-fields-manager">
            <div class="manager-header">
                <h2>مدیریت فیلدهای اطلاعاتی</h2>
                <a href="?page=workforce-admin&action=fields&subaction=edit" class="button button-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                    </svg>
                    افزودن فیلد جدید
                </a>
            </div>
            
            <div class="fields-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="50">ترتیب</th>
                            <th>عنوان فیلد</th>
                            <th>کلید</th>
                            <th>نوع</th>
                            <th width="100">الزامی</th>
                            <th width="100">اصلی</th>
                            <th width="100">یونیک</th>
                            <th width="100">قابل ویرایش</th>
                            <th width="150">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($fields): ?>
                            <?php foreach ($fields as $field): ?>
                            <tr>
                                <td><?php echo $field['sort_order']; ?></td>
                                <td>
                                    <strong><?php echo esc_html($field['field_name']); ?></strong>
                                    <?php if ($field['field_type'] === 'dropdown' && !empty($field['dropdown_values'])): ?>
                                    <br><small class="text-muted">
                                        <?php echo implode('، ', (array)$field['dropdown_values']); ?>
                                    </small>
                                    <?php endif; ?>
                                </td>
                                <td><code><?php echo esc_html($field['field_key']); ?></code></td>
                                <td>
                                    <span class="field-type-badge type-<?php echo $field['field_type']; ?>">
                                        <?php echo self::get_field_type_name($field['field_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($field['is_required']): ?>
                                    <span class="dashicons dashicons-yes" style="color: #10b981;"></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($field['is_main']): ?>
                                    <span class="dashicons dashicons-star-filled" style="color: #f59e0b;"></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($field['is_unique']): ?>
                                    <span class="dashicons dashicons-admin-network" style="color: #8b5cf6;"></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($field['is_editable']): ?>
                                    <span class="dashicons dashicons-edit" style="color: #3b82f6;"></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a href="?page=workforce-admin&action=fields&subaction=edit&id=<?php echo $field['id']; ?>" 
                                           class="button button-small">
                                            ویرایش
                                        </a>
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('workforce_admin_action'); ?>
                                            <input type="hidden" name="form_action" value="delete_field">
                                            <input type="hidden" name="field_id" value="<?php echo $field['id']; ?>">
                                            <button type="submit" class="button button-small button-link-delete" 
                                                    onclick="return confirm('آیا مطمئن هستید؟')">
                                                حذف
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">
                                    هنوز هیچ فیلدی ایجاد نشده است.
                                    <a href="?page=workforce-admin&action=fields&subaction=edit">اولین فیلد را ایجاد کنید</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="fields-info-box">
                <h3>
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                    </svg>
                    راهنمای فیلدها
                </h3>
                <ul>
                    <li><strong>فیلدهای اصلی:</strong> در کارت‌های مانیتورینگ نمایش داده می‌شوند و وضعیت پر شدن آن‌ها مهم است.</li>
                    <li><strong>فیلدهای یونیک:</strong> مقدار آن‌ها باید در هر دوره منحصربه‌فرد باشد (مانند کد ملی).</li>
                    <li><strong>فیلدهای dropdown:</strong> مقادیر آن‌ها در پنل مدیران به صورت لیست کشویی نمایش داده می‌شود.</li>
                    <li><strong>ترتیب فیلدها:</strong> بر اساس شماره ترتیب، در فرم‌ها و جدول‌ها نمایش داده می‌شوند.</li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    public static function render_field_editor($field_id = 0) {
        global $db;
        
        $field = $field_id > 0 ? $db->get_fields(array('id' => $field_id))[0] ?? null : null;
        $dropdown_values = '';
        
        if ($field && $field['field_type'] === 'dropdown' && !empty($field['dropdown_values'])) {
            $dropdown_values = implode("\n", (array)$field['dropdown_values']);
        }
        ?>
        <div class="workforce-field-editor">
            <div class="editor-header">
                <h2><?php echo $field ? 'ویرایش فیلد' : 'افزودن فیلد جدید'; ?></h2>
                <a href="?page=workforce-admin&action=fields" class="button">
                    بازگشت به لیست فیلدها
                </a>
            </div>
            
            <form method="post" class="workforce-form">
                <?php wp_nonce_field('workforce_admin_action'); ?>
                <input type="hidden" name="form_action" value="save_field">
                <input type="hidden" name="field_id" value="<?php echo $field_id; ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="field_name">عنوان فیلد *</label>
                        <input type="text" id="field_name" name="field_name" 
                               value="<?php echo $field ? esc_attr($field['field_name']) : ''; ?>" 
                               required class="regular-text">
                        <p class="description">عنوان فارسی فیلد که در فرم‌ها نمایش داده می‌شود.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="field_key">کلید فیلد *</label>
                        <input type="text" id="field_key" name="field_key" 
                               value="<?php echo $field ? esc_attr($field['field_key']) : ''; ?>" 
                               pattern="[a-z0-9_]+" required class="regular-text">
                        <p class="description">کلید انگلیسی فیلد (فقط حروف کوچک، اعداد و زیرخط)</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="field_type">نوع فیلد *</label>
                    <select id="field_type" name="field_type" required class="regular-text">
                        <option value="text" <?php selected($field['field_type'] ?? '', 'text'); ?>>متنی</option>
                        <option value="number" <?php selected($field['field_type'] ?? '', 'number'); ?>>عدد صحیح</option>
                        <option value="decimal" <?php selected($field['field_type'] ?? '', 'decimal'); ?>>عدد اعشاری</option>
                        <option value="date" <?php selected($field['field_type'] ?? '', 'date'); ?>>تاریخ</option>
                        <option value="dropdown" <?php selected($field['field_type'] ?? '', 'dropdown'); ?>>لیست کشویی</option>
                        <option value="textarea" <?php selected($field['field_type'] ?? '', 'textarea'); ?>>متن چندخطی</option>
                    </select>
                </div>
                
                <div class="form-group dropdown-values-container" id="dropdownValuesContainer" 
                     style="display: <?php echo ($field['field_type'] ?? '') === 'dropdown' ? 'block' : 'none'; ?>">
                    <label for="dropdown_values">مقادیر لیست کشویی</label>
                    <textarea id="dropdown_values" name="dropdown_values" rows="5" 
                              class="large-text"><?php echo esc_textarea($dropdown_values); ?></textarea>
                    <p class="description">هر مقدار را در یک خط جدید وارد کنید</p>
                </div>
                
                <div class="form-grid">
                    <div class="form-check">
                        <label>
                            <input type="checkbox" name="is_required" value="1" 
                                   <?php checked($field['is_required'] ?? 0, 1); ?>>
                            فیلد الزامی
                        </label>
                    </div>
                    
                    <div class="form-check">
                        <label>
                            <input type="checkbox" name="is_main" value="1" 
                                   <?php checked($field['is_main'] ?? 0, 1); ?>>
                            فیلد اصلی
                        </label>
                    </div>
                    
                    <div class="form-check">
                        <label>
                            <input type="checkbox" name="is_unique" value="1" 
                                   <?php checked($field['is_unique'] ?? 0, 1); ?>>
                            مقدار یونیک
                        </label>
                    </div>
                    
                    <div class="form-check">
                        <label>
                            <input type="checkbox" name="is_editable" value="1" 
                                   <?php checked($field['is_editable'] ?? 1, 1); ?>>
                            قابل ویرایش توسط مدیران
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="sort_order">ترتیب نمایش</label>
                    <input type="number" id="sort_order" name="sort_order" 
                           value="<?php echo $field ? esc_attr($field['sort_order']) : '0'; ?>" 
                           min="0" max="100" class="small-text">
                    <p class="description">فیلدها بر اساس این شماره مرتب می‌شوند (کمتر = اولویت بیشتر)</p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary button-large">
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
                        </svg>
                        ذخیره فیلد
                    </button>
                    <a href="?page=workforce-admin&action=fields" class="button button-large">
                        انصراف
                    </a>
                </div>
            </form>
            
            <script>
            document.getElementById('field_type').addEventListener('change', function() {
                const container = document.getElementById('dropdownValuesContainer');
                container.style.display = this.value === 'dropdown' ? 'block' : 'none';
            });
            </script>
        </div>
        <?php
    }
    
    public static function render_departments_manager() {
        global $db;
        $departments = $db->get_departments();
        ?>
        <div class="workforce-departments-manager">
            <div class="manager-header">
                <h2>مدیریت ادارات و واحدها</h2>
                <a href="?page=workforce-admin&action=departments&subaction=edit" class="button button-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                    </svg>
                    افزودن اداره جدید
                </a>
            </div>
            
            <div class="departments-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>نام اداره</th>
                            <th width="100">کد</th>
                            <th>والد</th>
                            <th>مدیران</th>
                            <th width="100">پرسنل</th>
                            <th width="100">وضعیت</th>
                            <th width="150">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($departments): ?>
                            <?php foreach ($departments as $dept): 
                                $stats = $db->get_department_stats($dept['id']);
                                $manager_names = self::get_manager_names($dept['manager_ids'] ?? array());
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($dept['department_name']); ?></strong>
                                    <?php if ($dept['parent_id'] > 0): ?>
                                    <br><small class="text-muted">زیرمجموعه</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($dept['department_code']); ?></td>
                                <td>
                                    <?php 
                                    if ($dept['parent_id'] > 0) {
                                        $parent = $db->get_departments(array('id' => $dept['parent_id']))[0] ?? null;
                                        echo $parent ? esc_html($parent['department_name']) : '—';
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty($manager_names)): ?>
                                        <?php echo esc_html(implode('، ', $manager_names)); ?>
                                    <?php else: ?>
                                        <span class="text-muted">تعیین نشده</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="personnel-count"><?php echo $stats['total']; ?></span>
                                    <?php if ($stats['pending_count'] > 0): ?>
                                    <span class="pending-badge"><?php echo $stats['pending_count']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($dept['is_active']): ?>
                                    <span class="status-badge status-active">فعال</span>
                                    <?php else: ?>
                                    <span class="status-badge status-inactive">غیرفعال</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a href="?page=workforce-admin&action=departments&subaction=edit&id=<?php echo $dept['id']; ?>" 
                                           class="button button-small">
                                            ویرایش
                                        </a>
                                        <a href="?page=workforce-admin&action=reports&dept=<?php echo $dept['id']; ?>" 
                                           class="button button-small">
                                            گزارشات
                                        </a>
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('workforce_admin_action'); ?>
                                            <input type="hidden" name="form_action" value="delete_department">
                                            <input type="hidden" name="department_id" value="<?php echo $dept['id']; ?>">
                                            <button type="submit" class="button button-small button-link-delete" 
                                                    onclick="return confirm('آیا مطمئن هستید؟')">
                                                حذف
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">
                                    هنوز هیچ اداره‌ای ایجاد نشده است.
                                    <a href="?page=workforce-admin&action=departments&subaction=edit">اولین اداره را ایجاد کنید</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    public static function render_department_editor($dept_id = 0) {
        global $db, $wpdb;
        
        $department = $dept_id > 0 ? $db->get_departments(array('id' => $dept_id))[0] ?? null : null;
        $all_departments = $db->get_departments();
        $all_users = $wpdb->get_results("SELECT ID, display_name FROM {$wpdb->prefix}users ORDER BY display_name ASC", ARRAY_A);
        $selected_managers = $department ? (array)$department['manager_ids'] : array();
        ?>
        <div class="workforce-department-editor">
            <div class="editor-header">
                <h2><?php echo $department ? 'ویرایش اداره' : 'افزودن اداره جدید'; ?></h2>
                <a href="?page=workforce-admin&action=departments" class="button">
                    بازگشت به لیست ادارات
                </a>
            </div>
            
            <form method="post" class="workforce-form">
                <?php wp_nonce_field('workforce_admin_action'); ?>
                <input type="hidden" name="form_action" value="save_department">
                <input type="hidden" name="department_id" value="<?php echo $dept_id; ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="department_name">نام اداره *</label>
                        <input type="text" id="department_name" name="department_name" 
                               value="<?php echo $department ? esc_attr($department['department_name']) : ''; ?>" 
                               required class="regular-text">
                    </div>
                    
                    <div class="form-group">
                        <label for="department_code">کد اداره</label>
                        <input type="text" id="department_code" name="department_code" 
                               value="<?php echo $department ? esc_attr($department['department_code']) : ''; ?>" 
                               class="regular-text">
                        <p class="description">کد اختصاصی اداره (اختیاری)</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="parent_id">اداره والد</label>
                    <select id="parent_id" name="parent_id" class="regular-text">
                        <option value="0">— بدون والد —</option>
                        <?php foreach ($all_departments as $dept): 
                            if ($dept['id'] == $dept_id) continue; // جلوگیری از انتخاب خودش به عنوان والد
                        ?>
                        <option value="<?php echo $dept['id']; ?>" 
                                <?php selected($department['parent_id'] ?? 0, $dept['id']); ?>>
                            <?php echo esc_html($dept['department_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">در صورت نیاز به ساختار سلسله‌مراتبی</p>
                </div>
                
                <div class="form-group">
                    <label>مدیران اداره</label>
                    <div class="user-selector">
                        <div class="selected-users" id="selectedManagers">
                            <?php foreach ($selected_managers as $user_id): 
                                $user = get_userdata($user_id);
                                if ($user):
                            ?>
                            <span class="selected-user" data-id="<?php echo $user_id; ?>">
                                <?php echo esc_html($user->display_name); ?>
                                <button type="button" class="remove-user">&times;</button>
                            </span>
                            <?php endif; endforeach; ?>
                        </div>
                        <input type="text" id="userSearch" placeholder="جستجوی کاربر..." class="regular-text">
                        <div class="user-results" id="userResults"></div>
                        <input type="hidden" name="manager_ids" id="managerIds" 
                               value="<?php echo implode(',', $selected_managers); ?>">
                    </div>
                    <p class="description">می‌توانید چند مدیر برای اداره تعیین کنید</p>
                </div>
                
                <div class="form-check">
                    <label>
                        <input type="checkbox" name="is_active" value="1" 
                               <?php checked($department['is_active'] ?? 1, 1); ?>>
                        اداره فعال است
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary button-large">
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
                        </svg>
                        ذخیره اداره
                    </button>
                    <a href="?page=workforce-admin&action=departments" class="button button-large">
                        انصراف
                    </a>
                </div>
            </form>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const userSearch = document.getElementById('userSearch');
                const userResults = document.getElementById('userResults');
                const selectedUsers = document.getElementById('selectedManagers');
                const managerIds = document.getElementById('managerIds');
                const selectedIds = managerIds.value ? managerIds.value.split(',').map(Number) : [];
                
                // جستجوی کاربران
                userSearch.addEventListener('input', function() {
                    const query = this.value.trim();
                    
                    if (query.length < 2) {
                        userResults.innerHTML = '';
                        return;
                    }
                    
                    fetch(ajaxurl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'workforce_ajax',
                            action_type: 'search_users',
                            query: query,
                            nonce: workforceData.nonce
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            userResults.innerHTML = data.data.map(user => `
                                <div class="user-result" data-id="${user.ID}">
                                    <strong>${user.display_name}</strong>
                                    <span class="user-email">${user.user_email}</span>
                                </div>
                            `).join('');
                        }
                    });
                });
                
                // انتخاب کاربر
                userResults.addEventListener('click', function(e) {
                    const userResult = e.target.closest('.user-result');
                    if (userResult) {
                        const userId = parseInt(userResult.dataset.id);
                        const userName = userResult.querySelector('strong').textContent;
                        
                        if (!selectedIds.includes(userId)) {
                            selectedIds.push(userId);
                            updateSelectedUsers();
                            userSearch.value = '';
                            userResults.innerHTML = '';
                        }
                    }
                });
                
                // حذف کاربر انتخاب شده
                selectedUsers.addEventListener('click', function(e) {
                    if (e.target.classList.contains('remove-user')) {
                        const userSpan = e.target.parentElement;
                        const userId = parseInt(userSpan.dataset.id);
                        
                        const index = selectedIds.indexOf(userId);
                        if (index > -1) {
                            selectedIds.splice(index, 1);
                            updateSelectedUsers();
                        }
                    }
                });
                
                function updateSelectedUsers() {
                    // به‌روزرسانی نمایش
                    selectedUsers.innerHTML = selectedIds.map(id => {
                        const user = <?php echo json_encode(array_column($all_users, null, 'ID')); ?>[id];
                        if (!user) return '';
                        return `
                            <span class="selected-user" data-id="${id}">
                                ${user.display_name}
                                <button type="button" class="remove-user">&times;</button>
                            </span>
                        `;
                    }).join('');
                    
                    // به‌روزرسانی مقدار مخفی
                    managerIds.value = selectedIds.join(',');
                }
            });
            </script>
        </div>
        <?php
    }
    
    public static function render_periods_manager() {
        global $wpdb;
        $periods = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}workforce_periods ORDER BY period_year DESC, period_month DESC", ARRAY_A);
        ?>
        <div class="workforce-periods-manager">
            <div class="manager-header">
                <h2>مدیریت دوره‌های زمانی</h2>
                <button type="button" class="button button-primary" onclick="openPeriodModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                    </svg>
                    افزودن دوره جدید
                </button>
            </div>
            
            <div class="periods-grid">
                <?php foreach ($periods as $period): 
                    $month_name = self::get_month_name($period['period_month']);
                ?>
                <div class="period-card <?php echo $period['is_active'] ? 'active' : ''; ?>">
                    <div class="period-header">
                        <h3><?php echo esc_html($period['period_name']); ?></h3>
                        <div class="period-status">
                            <?php if ($period['is_locked']): ?>
                            <span class="status-badge status-locked">قفل شده</span>
                            <?php elseif ($period['is_active']): ?>
                            <span class="status-badge status-active">فعال</span>
                            <?php else: ?>
                            <span class="status-badge status-inactive">بایگانی</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="period-details">
                        <div class="period-date">
                            <?php echo $month_name . ' ' . $period['period_year']; ?>
                        </div>
                        
                        <?php if ($period['start_date'] && $period['end_date']): ?>
                        <div class="period-range">
                            <?php echo jdate('Y/m/d', strtotime($period['start_date'])); ?> 
                            تا 
                            <?php echo jdate('Y/m/d', strtotime($period['end_date'])); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php
                        $count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}workforce_personnel 
                             WHERE period_id = %d AND deleted_at IS NULL",
                            $period['id']
                        ));
                        ?>
                        <div class="period-count">
                            <svg width="16" height="16" viewBox="0 0 24 24">
                                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                            </svg>
                            <?php echo $count; ?> پرسنل
                        </div>
                    </div>
                    
                    <div class="period-actions">
                        <button type="button" class="button button-small" 
                                onclick="editPeriod(<?php echo $period['id']; ?>)">
                            ویرایش
                        </button>
                        <?php if (!$period['is_locked']): ?>
                        <button type="button" class="button button-small <?php echo $period['is_active'] ? 'button-secondary' : 'button-primary'; ?>" 
                                onclick="togglePeriod(<?php echo $period['id']; ?>, <?php echo $period['is_active'] ? 0 : 1; ?>)">
                            <?php echo $period['is_active'] ? 'غیرفعال' : 'فعال'; ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- مودال ایجاد/ویرایش دوره -->
        <div id="periodModal" class="workforce-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="modalTitle">افزودن دوره جدید</h3>
                    <button type="button" class="modal-close" onclick="closePeriodModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="periodForm">
                        <div class="form-group">
                            <label for="modal_period_year">سال</label>
                            <select id="modal_period_year" class="regular-text">
                                <?php for ($year = 1400; $year <= 1410; $year++): ?>
                                <option value="<?php echo $year; ?>" 
                                        <?php selected(jdate('Y', current_time('timestamp'), '', 'Asia/Tehran', 'en'), $year); ?>>
                                    <?php echo $year; ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="modal_period_month">ماه</label>
                            <select id="modal_period_month" class="regular-text">
                                <?php
                                $months = array(
                                    1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
                                    4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
                                    7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
                                    10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
                                );
                                foreach ($months as $num => $name):
                                ?>
                                <option value="<?php echo $num; ?>" 
                                        <?php selected(jdate('n', current_time('timestamp'), '', 'Asia/Tehran', 'en'), $num); ?>>
                                    <?php echo $name; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="modal_start_date">تاریخ شروع</label>
                                <input type="text" id="modal_start_date" class="regular-text jdate-picker">
                            </div>
                            
                            <div class="form-group">
                                <label for="modal_end_date">تاریخ پایان</label>
                                <input type="text" id="modal_end_date" class="regular-text jdate-picker">
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <label>
                                <input type="checkbox" id="modal_is_active" value="1" checked>
                                دوره فعال باشد
                            </label>
                        </div>
                        
                        <div class="form-check">
                            <label>
                                <input type="checkbox" id="modal_is_locked" value="1">
                                دوره قفل شود
                            </label>
                            <p class="description">در صورت قفل شدن، امکان ویرایش اطلاعات دوره وجود ندارد</p>
                        </div>
                        
                        <input type="hidden" id="modal_period_id" value="0">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button" onclick="closePeriodModal()">انصراف</button>
                    <button type="button" class="button button-primary" onclick="savePeriod()">ذخیره دوره</button>
                </div>
            </div>
        </div>
        
        <script>
        let currentPeriodId = 0;
        
        function openPeriodModal(periodId = 0) {
            currentPeriodId = periodId;
            const modal = document.getElementById('periodModal');
            const title = document.getElementById('modalTitle');
            
            if (periodId > 0) {
                title.textContent = 'ویرایش دوره';
                // بارگذاری اطلاعات دوره
                loadPeriodData(periodId);
            } else {
                title.textContent = 'افزودن دوره جدید';
                resetPeriodForm();
            }
            
            modal.style.display = 'flex';
        }
        
        function closePeriodModal() {
            document.getElementById('periodModal').style.display = 'none';
        }
        
        function loadPeriodData(periodId) {
            // AJAX برای بارگذاری اطلاعات دوره
        }
        
        function resetPeriodForm() {
            document.getElementById('modal_period_id').value = '0';
            document.getElementById('modal_period_year').value = '<?php echo jdate('Y', current_time('timestamp'), '', 'Asia/Tehran', 'en'); ?>';
            document.getElementById('modal_period_month').value = '<?php echo jdate('n', current_time('timestamp'), '', 'Asia/Tehran', 'en'); ?>';
            document.getElementById('modal_start_date').value = '';
            document.getElementById('modal_end_date').value = '';
            document.getElementById('modal_is_active').checked = true;
            document.getElementById('modal_is_locked').checked = false;
        }
        
        function savePeriod() {
            const formData = {
                period_id: document.getElementById('modal_period_id').value,
                period_year: document.getElementById('modal_period_year').value,
                period_month: document.getElementById('modal_period_month').value,
                start_date: document.getElementById('modal_start_date').value,
                end_date: document.getElementById('modal_end_date').value,
                is_active: document.getElementById('modal_is_active').checked ? 1 : 0,
                is_locked: document.getElementById('modal_is_locked').checked ? 1 : 0
            };
            
            // AJAX برای ذخیره
            closePeriodModal();
            location.reload();
        }
        
        function editPeriod(periodId) {
            openPeriodModal(periodId);
        }
        
        function togglePeriod(periodId, newStatus) {
            if (confirm('آیا مطمئن هستید؟')) {
                // AJAX برای تغییر وضعیت
                location.reload();
            }
        }
        
        // بستن مودال با کلیک خارج
        window.onclick = function(event) {
            const modal = document.getElementById('periodModal');
            if (event.target == modal) {
                closePeriodModal();
            }
        }
        </script>
        <?php
    }
    
    public static function render_reports_manager() {
        ?>
        <div class="workforce-reports-manager">
            <div class="manager-header">
                <h2>گزارشات و خروجی اطلاعات</h2>
            </div>
            
            <div class="reports-grid">
                <div class="report-card">
                    <div class="report-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                        </svg>
                    </div>
                    <h3>گزارش جامع</h3>
                    <p>گزارش کامل اطلاعات همه ادارات در دوره‌های مختلف</p>
                    <button type="button" class="button button-primary" onclick="generateReport('full')">
                        ایجاد گزارش
                    </button>
                </div>
                
                <div class="report-card">
                    <div class="report-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24">
                            <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
                        </svg>
                    </div>
                    <h3>خروجی اکسل</h3>
                    <p>استخراج اطلاعات به صورت فایل Excel</p>
                    <button type="button" class="button button-primary" onclick="openExportModal()">
                        خروجی بگیرید
                    </button>
                </div>
                
                <div class="report-card">
                    <div class="report-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24">
                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                        </svg>
                    </div>
                    <h3>گزارش ادارات</h3>
                    <p>گزارش تفکیک شده هر اداره</p>
                    <button type="button" class="button button-primary" onclick="openDeptReportModal()">
                        انتخاب اداره
                    </button>
                </div>
                
                <div class="report-card">
                    <div class="report-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24">
                            <path d="M9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4zm2.5 2.1h-15V5h15v14.1zm0-16.1h-15c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h15c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/>
                        </svg>
                    </div>
                    <h3>آمار و نمودار</h3>
                    <p>آمارهای تحلیلی و نمودارهای تعاملی</p>
                    <button type="button" class="button button-primary" onclick="showAnalytics()">
                        مشاهده آمار
                    </button>
                </div>
            </div>
            
            <!-- بخش گزارشات اخیر -->
            <div class="recent-reports">
                <h3>گزارشات اخیر</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>نوع گزارش</th>
                            <th>تاریخ ایجاد</th>
                            <th>تعداد رکورد</th>
                            <th>ایجاد کننده</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="text-center">
                                گزارشی یافت نشد
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- مودال خروجی اکسل -->
        <div id="exportModal" class="workforce-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>خروجی Excel</h3>
                    <button type="button" class="modal-close" onclick="closeExportModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="exportForm">
                        <div class="form-group">
                            <label for="export_period">دوره زمانی</label>
                            <select id="export_period" class="regular-text" multiple style="height: 150px;">
                                <!-- Options will be loaded via AJAX -->
                            </select>
                            <p class="description">می‌توانید چند دوره را انتخاب کنید (Ctrl+Click)</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="export_department">اداره</label>
                            <select id="export_department" class="regular-text">
                                <option value="all">همه ادارات</option>
                                <!-- Options will be loaded via AJAX -->
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="export_fields">فیلدها</label>
                            <div class="fields-checklist" id="fieldsChecklist">
                                <!-- Checkboxes will be loaded via AJAX -->
                            </div>
                            <div class="checklist-actions">
                                <button type="button" class="button button-small" onclick="selectAllFields()">انتخاب همه</button>
                                <button type="button" class="button button-small" onclick="deselectAllFields()">لغو همه</button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="export_format">فرمت خروجی</label>
                            <select id="export_format" class="regular-text">
                                <option value="excel">Excel (.xlsx)</option>
                                <option value="csv">CSV (.csv)</option>
                                <option value="pdf">PDF (.pdf)</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button" onclick="closeExportModal()">انصراف</button>
                    <button type="button" class="button button-primary" onclick="generateExport()">
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
                        </svg>
                        ایجاد خروجی
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        function openExportModal() {
            document.getElementById('exportModal').style.display = 'flex';
            loadExportData();
        }
        
        function closeExportModal() {
            document.getElementById('exportModal').style.display = 'none';
        }
        
        function loadExportData() {
            // AJAX برای بارگذاری دوره‌ها، ادارات و فیلدها
        }
        
        function selectAllFields() {
            document.querySelectorAll('#fieldsChecklist input[type="checkbox"]').forEach(cb => cb.checked = true);
        }
        
        function deselectAllFields() {
            document.querySelectorAll('#fieldsChecklist input[type="checkbox"]').forEach(cb => cb.checked = false);
        }
        
        function generateExport() {
            const formData = {
                periods: Array.from(document.getElementById('export_period').selectedOptions).map(o => o.value),
                department: document.getElementById('export_department').value,
                fields: Array.from(document.querySelectorAll('#fieldsChecklist input:checked')).map(cb => cb.value),
                format: document.getElementById('export_format').value
            };
            
            // AJAX برای ایجاد خروجی
            alert('خروجی در حال ایجاد است...');
            closeExportModal();
        }
        
        function generateReport(type) {
            alert('گزارش ' + type + ' در حال ایجاد است...');
        }
        
        function openDeptReportModal() {
            alert('مودال گزارش اداره باز می‌شود...');
        }
        
        function showAnalytics() {
            alert('صفحه آمار نمایش داده می‌شود...');
        }
        
        // بستن مودال‌ها با کلیک خارج
        window.onclick = function(event) {
            const exportModal = document.getElementById('exportModal');
            if (event.target == exportModal) {
                closeExportModal();
            }
        }
        </script>
        <?php
    }
    
    public static function render_settings_manager() {
        ?>
        <div class="workforce-settings-manager">
            <div class="manager-header">
                <h2>تنظیمات سیستم</h2>
            </div>
            
            <form method="post" class="workforce-form">
                <?php wp_nonce_field('workforce_admin_action'); ?>
                <input type="hidden" name="form_action" value="save_settings">
                
                <div class="settings-tabs">
                    <div class="tab-buttons">
                        <button type="button" class="tab-button active" data-tab="general">عمومی</button>
                        <button type="button" class="tab-button" data-tab="fields">فیلدها</button>
                        <button type="button" class="tab-button" data-tab="export">خروجی</button>
                        <button type="button" class="tab-button" data-tab="security">امنیت</button>
                        <button type="button" class="tab-button" data-tab="backup">پشتیبان</button>
                    </div>
                    
                    <div class="tab-content">
                        <!-- تب عمومی -->
                        <div class="tab-pane active" id="tab-general">
                            <div class="form-group">
                                <label for="system_name">نام سیستم</label>
                                <input type="text" id="system_name" name="system_name" 
                                       value="سامانه کارکرد پرسنل بنی اسد" class="regular-text">
                            </div>
                            
                            <div class="form-group">
                                <label for="default_period">دوره پیش‌فرض</label>
                                <select id="default_period" name="default_period" class="regular-text">
                                    <option value="current">دوره جاری</option>
                                    <option value="last">آخرین دوره</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="items_per_page">تعداد آیتم در صفحه</label>
                                <input type="number" id="items_per_page" name="items_per_page" 
                                       value="50" min="10" max="100" class="small-text">
                            </div>
                            
                            <div class="form-check">
                                <label>
                                    <input type="checkbox" name="enable_notifications" value="1" checked>
                                    فعال‌سازی اعلان‌ها
                                </label>
                            </div>
                        </div>
                        
                        <!-- تب فیلدها -->
                        <div class="tab-pane" id="tab-fields">
                            <div class="form-group">
                                <label for="required_color">رنگ فیلدهای الزامی</label>
                                <input type="color" id="required_color" name="required_color" 
                                       value="#ef4444" class="color-picker">
                            </div>
                            
                            <div class="form-group">
                                <label for="main_color">رنگ فیلدهای اصلی</label>
                                <input type="color" id="main_color" name="main_color" 
                                       value="#10b981" class="color-picker">
                            </div>
                            
                            <div class="form-check">
                                <label>
                                    <input type="checkbox" name="auto_save" value="1" checked>
                                    ذخیره خودکار هر 30 ثانیه
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <label>
                                    <input type="checkbox" name="show_field_hints" value="1" checked>
                                    نمایش راهنمای فیلدها
                                </label>
                            </div>
                        </div>
                        
                        <!-- تب خروجی -->
                        <div class="tab-pane" id="tab-export">
                            <div class="form-group">
                                <label for="export_limit">حداکثر رکورد در خروجی</label>
                                <input type="number" id="export_limit" name="export_limit" 
                                       value="10000" min="100" max="100000" class="small-text">
                                <p class="description">برای جلوگیری از overload سیستم</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="excel_template">قالب Excel</label>
                                <select id="excel_template" name="excel_template" class="regular-text">
                                    <option value="simple">ساده</option>
                                    <option value="detailed">مفصل با فرمت‌بندی</option>
                                    <option value="custom">سفارشی</option>
                                </select>
                            </div>
                            
                            <div class="form-check">
                                <label>
                                    <input type="checkbox" name="include_summary" value="1" checked>
                                    شامل صفحه خلاصه در خروجی
                                </label>
                            </div>
                        </div>
                        
                        <!-- تب امنیت -->
                        <div class="tab-pane" id="tab-security">
                            <div class="form-check">
                                <label>
                                    <input type="checkbox" name="enable_audit_log" value="1" checked>
                                    فعال‌سازی لاگ تغییرات
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <label>
                                    <input type="checkbox" name="ip_restriction" value="1">
                                    محدودیت دسترسی بر اساس IP
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label for="session_timeout">مدت زمان session (دقیقه)</label>
                                <input type="number" id="session_timeout" name="session_timeout" 
                                       value="120" min="15" max="1440" class="small-text">
                            </div>
                            
                            <div class="form-group">
                                <label for="max_login_attempts">حداکثر تلاش ورود</label>
                                <input type="number" id="max_login_attempts" name="max_login_attempts" 
                                       value="5" min="3" max="10" class="small-text">
                            </div>
                        </div>
                        
                        <!-- تب پشتیبان -->
                        <div class="tab-pane" id="tab-backup">
                            <div class="form-group">
                                <label for="backup_schedule">برنامه پشتیبان‌گیری</label>
                                <select id="backup_schedule" name="backup_schedule" class="regular-text">
                                    <option value="daily">روزانه</option>
                                    <option value="weekly" selected>هفتگی</option>
                                    <option value="monthly">ماهانه</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="backup_retention">مدت نگهداری (روز)</label>
                                <input type="number" id="backup_retention" name="backup_retention" 
                                       value="30" min="7" max="365" class="small-text">
                            </div>
                            
                            <div class="backup-actions">
                                <button type="button" class="button" onclick="createBackup()">
                                    ایجاد پشتیبان الآن
                                </button>
                                <button type="button" class="button" onclick="restoreBackup()">
                                    بازیابی پشتیبان
                                </button>
                            </div>
                            
                            <div class="backup-list" id="backupList">
                                <!-- لیست پشتیبان‌ها -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary button-large">
                        ذخیره تنظیمات
                    </button>
                    <button type="button" class="button button-large" onclick="resetSettings()">
                        بازنشانی
                    </button>
                </div>
            </form>
        </div>
        
        <script>
        // مدیریت تب‌ها
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.dataset.tab;
                
                // غیرفعال کردن همه تب‌ها
                document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
                
                // فعال کردن تب انتخاب شده
                this.classList.add('active');
                document.getElementById('tab-' + tabId).classList.add('active');
            });
        });
        
        function createBackup() {
            if (confirm('آیا از ایجاد پشتیبان اطمینان دارید؟')) {
                // AJAX برای ایجاد پشتیبان
                alert('پشتیبان در حال ایجاد است...');
            }
        }
        
        function restoreBackup() {
            alert('صفحه بازیابی نمایش داده می‌شود...');
        }
        
        function resetSettings() {
            if (confirm('آیا مطمئن هستید؟ همه تنظیمات به حالت پیش‌فرض بازمی‌گردد.')) {
                // AJAX برای بازنشانی
                location.reload();
            }
        }
        </script>
        <?php
    }
    
    // توابع کمکی
    private static function get_action_text($action) {
        $actions = array(
            'create_field' => 'فیلد جدید ایجاد کرد',
            'update_field' => 'فیلد را ویرایش کرد',
            'create_department' => 'اداره جدید ایجاد کرد',
            'update_department' => 'اداره را ویرایش کرد',
            'create_personnel' => 'پرسنل جدید اضافه کرد',
            'update_personnel' => 'اطلاعات پرسنل را ویرایش کرد',
            'delete_personnel' => 'پرسنل را حذف کرد',
            'export_data' => 'گزارش گرفت',
            'login' => 'وارد سیستم شد',
            'logout' => 'از سیستم خارج شد'
        );
        
        return $actions[$action] ?? 'عملیات انجام داد';
    }
    
    private static function get_action_icon($action) {
        $icons = array(
            'create_field' => '<svg width="16" height="16" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>',
            'update_field' => '<svg width="16" height="16" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>',
            'create_personnel' => '<svg width="16" height="16" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>',
            'login' => '<svg width="16" height="16" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>',
            'export_data' => '<svg width="16" height="16" viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>'
        );
        
        return $icons[$action] ?? '<svg width="16" height="16" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>';
    }
    
    private static function get_field_type_name($type) {
        $names = array(
            'text' => 'متنی',
            'number' => 'عدد',
            'decimal' => 'اعشاری',
            'date' => 'تاریخ',
            'dropdown' => 'کشویی',
            'textarea' => 'متن بلند'
        );
        
        return $names[$type] ?? $type;
    }
    
    private static function get_manager_names($manager_ids) {
        if (empty($manager_ids)) return array();
        
        global $wpdb;
        $ids = implode(',', array_map('intval', $manager_ids));
        
        $results = $wpdb->get_results("
            SELECT display_name 
            FROM {$wpdb->prefix}users 
            WHERE ID IN ($ids) 
            ORDER BY display_name ASC
        ", ARRAY_A);
        
        return array_column($results, 'display_name');
    }
    
    private static function get_month_name($month) {
        $months = array(
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
            4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
            7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
            10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
        );
        
        return $months[$month] ?? 'نامشخص';
    }
}
?>

<!-- استفاده از رندرر -->
<?php
$renderer = new WorkforceAdminRenderer();
?>

<!-- فایل ۴ ادامه دارد... (پنل مدیران) -->