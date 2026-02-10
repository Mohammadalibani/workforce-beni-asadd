<?php
/**
 * پنل ادمین در پیشخوان وردپرس
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}



/**
 * مدیریت مدیران سازمان
 */
function workforce_admin_org_managers() {
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
// پردازش فرم ذخیره مدیران
if (isset($_POST['submit_org_managers'])) {
    $nonce = $_POST['_wpnonce'] ?? '';
    
    if (wp_verify_nonce($nonce, 'workforce_save_org_managers')) {
        $manager_ids = isset($_POST['manager_ids']) ? array_map('intval', $_POST['manager_ids']) : [];
        
        // دیباگ: چک کنید آیا داده می‌رسد
        error_log('مدیران انتخاب شده: ' . print_r($manager_ids, true));
        
        // ذخیره مدیران سازمان
        global $wpdb;
        $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'organization_managers';
        
        // حذف مدیران قبلی
        $delete_result = $wpdb->query("DELETE FROM $table_name");
        error_log('حذف مدیران قبلی: ' . ($delete_result ? 'موفق' : 'ناموفق'));
        
        // اضافه کردن مدیران جدید
        $is_primary = true;
        foreach ($manager_ids as $user_id) {
            $insert_result = $wpdb->insert($table_name, [
                'user_id' => $user_id,
                'is_primary' => $is_primary ? 1 : 0,
                'created_at' => current_time('mysql')
            ]);
            
            error_log('درج مدیر ID ' . $user_id . ': ' . ($insert_result ? 'موفق' : 'ناموفق'));
            $is_primary = false;
        }
        
        echo '<div class="updated"><p>مدیران سازمان با موفقیت ذخیره شدند.</p></div>';
    }
}
    
    // گرفتن مدیران فعلی
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'organization_managers';
    $current_managers = $wpdb->get_results(
        "SELECT * FROM $table_name ORDER BY is_primary DESC, created_at ASC"
    );
    $current_manager_ids = array_column($current_managers, 'user_id');
    ?>
    
    <div class="wrap workforce-admin-org-managers">
        <h1 class="wp-heading-inline">مدیریت مدیران سازمان</h1>
        <hr class="wp-header-end">
        
        <div class="card" style="max-width: 800px; margin: 20px 0;">
            <h2>تنظیم مدیران سازمان</h2>
            <p>مدیران سازمان به همه ادارات دسترسی کامل دارند و می‌توانند گزارشات کلان را مشاهده کنند.</p>
            
            <form method="post">
                <?php wp_nonce_field('workforce_save_org_managers'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="org_manager_ids">انتخاب مدیران</label></th>
                        <td>
                            <select name="manager_ids[]" id="org_manager_ids" multiple="multiple" style="width: 100%; min-height: 200px;">
                                <?php 
                                // گرفتن همه کاربران سایت
                                $all_users = get_users([
                                    'orderby' => 'display_name',
                                    'order' => 'ASC'
                                ]);
                                
                                foreach ($all_users as $user): 
                                    // نمایش نقش‌های کاربر
                                    $role_names = [];
                                    foreach ($user->roles as $role) {
                                        $role_obj = get_role($role);
                                        if ($role_obj) {
                                            $role_names[] = $role_obj->name;
                                        }
                                    }
                                ?>
                                    <option value="<?php echo esc_attr($user->ID); ?>" 
                                        <?php echo in_array($user->ID, $current_manager_ids) ? 'selected' : ''; ?>>
                                        <?php echo esc_html($user->display_name . ' (' . implode(', ', $role_names) . ') - ' . $user->user_email); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                برای انتخاب چند مدیر: در ویندوز کلید Ctrl را نگه دارید و کلیک کنید. در مک کلید Command را نگه دارید.
                                <br>مدیر اول به عنوان مدیر اصلی در نظر گرفته می‌شود.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" name="submit_org_managers" class="button button-primary">
                        <span class="dashicons dashicons-admin-users"></span>
                        ذخیره مدیران سازمان
                    </button>
                </p>
            </form>
        </div>
        
<div class="card" style="max-width: 800px;">
    <h2>مدیران فعلی سازمان</h2>
    
    <?php 
    // ایجاد nonce یک بار برای کل صفحه
    $remove_nonce = wp_create_nonce('workforce_remove_org_manager');
    ?>
    
    <?php if (empty($current_managers)): ?>
        <div class="notice notice-warning">
            <p>هنوز مدیری برای سازمان تعریف نشده است.</p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="50">ردیف</th>
                    <th>نام</th>
                    <th>ایمیل</th>
                    <th>نقش‌ها</th>
                    <th>نوع</th>
                    <th width="120">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($current_managers as $index => $manager): ?>
                    <?php $user = get_userdata($manager->user_id); ?>
                    <?php if ($user): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <strong><?php echo esc_html($user->display_name); ?></strong>
                                <?php if ($manager->is_primary): ?>
                                    <span class="dashicons dashicons-star-filled" style="color: #f1c40f; margin-right: 5px;" title="مدیر اصلی"></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td>
                                <?php 
                                $translated_roles = [];
                                foreach ($user->roles as $role) {
                                    $role_obj = get_role($role);
                                    if ($role_obj) {
                                        $translated_roles[] = translate_user_role($role_obj->name);
                                    }
                                }
                                echo implode('، ', $translated_roles);
                                ?>
                            </td>
                            <td>
                                <?php echo $manager->is_primary ? 'مدیر اصلی' : 'مدیر عادی'; ?>
                            </td>
                            <td>
                                <button type="button" class="button button-small button-link-delete workforce-remove-manager" 
                                        data-manager-id="<?php echo $manager->id; ?>"
                                        data-user-name="<?php echo esc_attr($user->display_name); ?>"
                                        style="color: #dc3232;">
                                    <span class="dashicons dashicons-trash"></span> حذف
                                </button>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // استفاده از event delegation
            $(document).on('click', '.workforce-remove-manager', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var managerId = $button.data('manager-id');
                var userName = $button.data('user-name');
                
                if (!confirm('آیا از حذف مدیر "' + userName + '" اطمینان دارید؟')) {
                    return;
                }
                
                // غیرفعال کردن دکمه
                $button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> در حال حذف...');
                
                $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>',
                    type: 'POST',
                    data: {
                        action: 'workforce_remove_org_manager',
                        manager_id: managerId,
                        _ajax_nonce: '<?php echo $remove_nonce; ?>'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert('خطا: ' + response.data.message);
                            $button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> حذف');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('خطا در ارتباط با سرور: ' + error);
                        console.log('AJAX Error:', xhr.responseText);
                        $button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> حذف');
                    }
                });
            });
        });
        </script>
    <?php endif; ?>
</div>
        
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>راهنمای مدیران سازمان</h2>
            <ul style="list-style-type: disc; margin-right: 20px;">
                <li>مدیران سازمان می‌توانند به <strong>همه ادارات</strong> دسترسی داشته باشند.</li>
                <li>مدیران سازمان می‌توانند <strong>گزارشات کلان</strong> سازمان را مشاهده کنند.</li>
                <li>مدیران سازمان می‌توانند <strong>مقایسه بین ادارات</strong> انجام دهند.</li>
                <li>مدیر اصلی (اولین مدیر در لیست) برای موارد رسمی استفاده می‌شود.</li>
                <li>توصیه می‌شود حداقل ۲ مدیر سازمان تعریف شود.</li>
            </ul>
        </div>
    </div>
    <?php
}

// این کد را در admin-panel.php اضافه کنید (در ابتدای فایل، بعد از تابع workforce_admin_org_managers)
add_action('wp_ajax_workforce_remove_org_manager', 'workforce_ajax_remove_org_manager_handler');

function workforce_ajax_remove_org_manager_handler() {
    // بررسی nonce - مهم!
    if (!check_ajax_referer('workforce_remove_org_manager', '_ajax_nonce', false)) {
        wp_send_json_error(['message' => 'توکن امنیتی نامعتبر است.']);
        wp_die();
    }
    
    // بررسی دسترسی
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'شما دسترسی لازم را ندارید.']);
        wp_die();
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'organization_managers';
    
    $manager_id = isset($_POST['manager_id']) ? intval($_POST['manager_id']) : 0;
    
    if ($manager_id <= 0) {
        wp_send_json_error(['message' => 'شناسه مدیر نامعتبر است.']);
        wp_die();
    }
    
    // گرفتن اطلاعات مدیر قبل از حذف
    $manager = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $manager_id
    ));
    
    if (!$manager) {
        wp_send_json_error(['message' => 'مدیر یافت نشد.']);
        wp_die();
    }
    
    // حذف نقش مدیر سازمان از کاربر
    $user = get_userdata($manager->user_id);
    if ($user) {
        $user->remove_role('workforce_org_manager');
        
        // اگر کاربر دیگر هیچ نقشی ندارد، نقش مشترک را اضافه کن
        if (empty($user->roles)) {
            $user->add_role('subscriber');
        }
    }
    
    // حذف از دیتابیس
    $result = $wpdb->delete($table_name, ['id' => $manager_id], ['%d']);
    
    if ($result) {
        wp_send_json_success(['message' => 'مدیر با موفقیت حذف شد.']);
    } else {
        wp_send_json_error(['message' => 'خطا در حذف مدیر از دیتابیس.']);
    }
    
    wp_die(); // همیشه wp_die() را فراخوانی کنید
}

/**
 * داشبورد ادمین
 */
function workforce_admin_dashboard() {
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
    $stats = workforce_get_overall_stats();
    ?>
    <div class="wrap workforce-admin-dashboard">
        <h1 class="wp-heading-inline">مدیریت کارکرد پرسنل - بنی اسد</h1>
        <hr class="wp-header-end">
        
        <div class="workforce-stats-grid">
            <div class="workforce-stat-card">
                <div class="stat-icon">🏢</div>
                <div class="stat-content">
                    <h3>تعداد ادارات</h3>
                    <p class="stat-number"><?php echo esc_html($stats['departments']); ?></p>
                </div>
            </div>
            
            <div class="workforce-stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <h3>تعداد پرسنل</h3>
                    <p class="stat-number"><?php echo esc_html($stats['personnel']); ?></p>
                    <p class="stat-sub">
                        فعال: <?php echo esc_html($stats['active_personnel']); ?> |
                        غیرفعال: <?php echo esc_html($stats['inactive_personnel']); ?>
                    </p>
                </div>
            </div>
            
            <div class="workforce-stat-card">
                <div class="stat-icon">⚙️</div>
                <div class="stat-content">
                    <h3>فیلدهای تعریف شده</h3>
                    <p class="stat-number"><?php echo esc_html($stats['fields']); ?></p>
                </div>
            </div>
            
            <div class="workforce-stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <h3>درخواست‌های در انتظار</h3>
                    <p class="stat-number"><?php echo esc_html($stats['pending_approvals']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="workforce-dashboard-content">
            <div class="workforce-dashboard-column">
                <h2>هشدارها</h2>
                <div class="workforce-alerts">
                    <?php
                    $alerts = workforce_get_admin_alerts();
                    if (empty($alerts)) {
                        echo '<p class="workforce-no-alert">هیچ هشداری وجود ندارد.</p>';
                    } else {
                        foreach ($alerts as $alert) {
                            echo '<div class="workforce-alert workforce-alert-' . esc_attr($alert['type']) . '">';
                            echo '<span class="alert-icon">' . esc_html($alert['icon']) . '</span>';
                            echo '<span class="alert-text">' . esc_html($alert['text']) . '</span>';
                            if (!empty($alert['action'])) {
                                echo '<a href="' . esc_url($alert['action']['url']) . '" class="alert-action">' . esc_html($alert['action']['text']) . '</a>';
                            }
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
                
                <h2>فعالیت‌های اخیر</h2>
                <div class="workforce-recent-activities">
                    <?php
                    $activities = workforce_get_recent_activities(10);
                    if (empty($activities)) {
                        echo '<p>هیچ فعالیتی ثبت نشده است.</p>';
                    } else {
                        echo '<table class="wp-list-table widefat fixed striped">';
                        echo '<thead><tr><th>کاربر</th><th>عمل</th><th>جزئیات</th><th>زمان</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($activities as $activity) {
                            $user = get_userdata($activity->user_id);
                            echo '<tr>';
                            echo '<td>' . esc_html($user ? $user->display_name : 'نامشخص') . '</td>';
                            echo '<td>' . esc_html($activity->action) . '</td>';
                            echo '<td>' . esc_html($activity->details) . '</td>';
                            echo '<td>' . esc_html(wp_date('Y/m/d H:i', strtotime($activity->created_at))) . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="workforce-dashboard-column">
                <h2>ادارات و مدیران</h2>
                <div class="workforce-departments-list">
                    <?php
                    $departments = workforce_get_all_departments();
                    if (empty($departments)) {
                        echo '<p>هیچ اداره‌ای ایجاد نشده است.</p>';
                    } else {
                        foreach ($departments as $dept) {
                            $manager = $dept->manager_id ? get_userdata($dept->manager_id) : null;
                            $personnel_count = workforce_get_department_personnel_count($dept->id);
                            
                            echo '<div class="workforce-dept-item" style="border-left-color: ' . esc_attr($dept->color) . '">';
                            echo '<h3>' . esc_html($dept->name) . '</h3>';
                            echo '<div class="dept-details">';
                            // گرفتن مدیران از جدول department_managers
// گرفتن مدیران
$dept_managers = workforce_get_department_managers($dept->id);
if (!empty($dept_managers)) {
    $manager_count = count($dept_managers);
    $primary_manager_name = 'تعیین نشده';
    
    foreach ($dept_managers as $dept_manager) {
        if ($dept_manager->is_primary) {
            $mgr_user = get_userdata($dept_manager->user_id);
            if ($mgr_user) {
                $primary_manager_name = $mgr_user->display_name;
            }
            break;
        }
    }
    
    echo '<span class="dept-manager" title="' . esc_attr($manager_count . ' مدیر') . '">👤 ' . 
          esc_html($primary_manager_name) . 
          ($manager_count > 1 ? ' +' . ($manager_count - 1) : '') . 
          '</span>';
} else {
// گرفتن همه مدیران
$dept_managers = workforce_get_department_managers($dept->id);
if (!empty($dept_managers)) {
    $all_manager_names = [];
    foreach ($dept_managers as $dept_manager) {
        $mgr_user = get_userdata($dept_manager->user_id);
        if ($mgr_user) {
            $all_manager_names[] = $mgr_user->display_name;
        }
    }
    // نمایش همه مدیران
    echo '<span class="dept-manager">👤 مدیران: ' . esc_html(implode('، ', $all_manager_names)) . '</span>';
} else {
    echo '<span class="dept-manager">👤 مدیر: تعیین نشده</span>';
}
}
                            echo '<span class="dept-personnel">👥 پرسنل: ' . esc_html($personnel_count) . ' نفر</span>';
                            echo '</div>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
                
                <h2>پیوندهای سریع</h2>
                <div class="workforce-quick-links">
                    <a href="<?php echo admin_url('admin.php?page=workforce-fields'); ?>" class="button button-primary">مدیریت فیلدها</a>
                    <a href="<?php echo admin_url('admin.php?page=workforce-departments'); ?>" class="button">مدیریت ادارات</a>
                    <a href="<?php echo admin_url('admin.php?page=workforce-personnel'); ?>" class="button">مدیریت پرسنل</a>
                    <a href="<?php echo admin_url('admin.php?page=workforce-approvals'); ?>" class="button">تایید درخواست‌ها</a>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * مدیریت فیلدها
 */
function workforce_admin_fields() {
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
    // پردازش فرم افزودن/ویرایش فیلد
    if (isset($_POST['submit_field'])) {
        $nonce = $_POST['_wpnonce'] ?? '';
        
        if (wp_verify_nonce($nonce, 'workforce_save_field')) {
            $field_data = [
                'field_label' => sanitize_text_field($_POST['field_label']),
                'field_type' => sanitize_text_field($_POST['field_type']),
                'is_required' => isset($_POST['is_required']),
                'is_locked' => isset($_POST['is_locked']),
                'is_monitoring' => isset($_POST['is_monitoring']),
                'is_key' => isset($_POST['is_key']),
                'display_order' => intval($_POST['display_order']),
            ];
            
            // پردازش آپشن‌ها برای فیلدهای select
            if ($_POST['field_type'] === 'select' && !empty($_POST['options'])) {
                $options = explode("\n", sanitize_textarea_field($_POST['options']));
                $options = array_map('trim', $options);
                $options = array_filter($options);
                $field_data['options'] = $options;
            }
            
            if (isset($_POST['field_id']) && !empty($_POST['field_id'])) {
                // ویرایش فیلد موجود
                workforce_update_field(intval($_POST['field_id']), $field_data);
                echo '<div class="updated"><p>فیلد با موفقیت ویرایش شد.</p></div>';
            } else {
                // افزودن فیلد جدید
                workforce_add_field($field_data);
                echo '<div class="updated"><p>فیلد جدید با موفقیت افزوده شد.</p></div>';
            }
        }
    }
    
    // پردازش حذف فیلد
    if (isset($_GET['delete_field'])) {
        $nonce = $_GET['_wpnonce'] ?? '';
        
        if (wp_verify_nonce($nonce, 'delete_field_' . $_GET['delete_field'])) {
            workforce_delete_field(intval($_GET['delete_field']));
            echo '<div class="updated"><p>فیلد با موفقیت حذف شد.</p></div>';
        }
    }
    
    $fields = workforce_get_all_fields();
    ?>
    
    <div class="wrap workforce-admin-fields">
        <h1 class="wp-heading-inline">مدیریت فیلدها</h1>
        <button type="button" class="page-title-action" onclick="showAddFieldModal()">افزودن فیلد جدید</button>
        <hr class="wp-header-end">
        
        <div class="workforce-fields-list">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ترتیب</th>
                        <th>عنوان فارسی</th>
                        <th>نوع</th>
                        <th>ویژگی‌ها</th>
                        <th>تاریخ ایجاد</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fields)): ?>
                        <tr><td colspan="6">هیچ فیلدی ایجاد نشده است.</td></tr>
                    <?php else: ?>
                        <?php foreach ($fields as $field): ?>
                            <tr>
                                <td><?php echo esc_html($field->display_order); ?></td>
                                <td>
                                    <strong><?php echo esc_html($field->field_label); ?></strong>
                                    <?php if ($field->is_key): ?>
                                        <span class="field-badge field-key" title="کلید (مقدار یکتا)">🔑</span>
                                    <?php endif; ?>
                                    <?php if ($field->is_required): ?>
                                        <span class="field-badge field-required" title="ضروری">⚠️</span>
                                    <?php endif; ?>
                                    <?php if ($field->is_locked): ?>
                                        <span class="field-badge field-locked" title="قفل شده">🔒</span>
                                    <?php endif; ?>
                                    <?php if ($field->is_monitoring): ?>
                                        <span class="field-badge field-monitoring" title="مانیتورینگ">📊</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($field->field_type); ?></td>
                                <td>
                                    <small>
                                        <?php if ($field->is_key): ?>کلید، <?php endif; ?>
                                        <?php if ($field->is_required): ?>ضروری، <?php endif; ?>
                                        <?php if ($field->is_locked): ?>قفل، <?php endif; ?>
                                        <?php if ($field->is_monitoring): ?>مانیتورینگ<?php endif; ?>
                                    </small>
                                </td>
                                <td><?php echo esc_html(wp_date('Y/m/d', strtotime($field->created_at))); ?></td>
                                <td>
                                    <button type="button" class="button button-small" onclick="editField(<?php echo $field->id; ?>)">ویرایش</button>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=workforce-fields&delete_field=' . $field->id), 'delete_field_' . $field->id, '_wpnonce'); ?>" class="button button-small button-link-delete" onclick="return confirm('آیا از حذف این فیلد اطمینان دارید؟')">حذف</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- مودال افزودن/ویرایش فیلد -->
        <div id="fieldModal" class="workforce-modal" style="display: none;">
            <div class="workforce-modal-content">
                <div class="workforce-modal-header">
                    <h2 id="modalTitle">افزودن فیلد جدید</h2>
                    <span class="workforce-modal-close" onclick="hideFieldModal()">&times;</span>
                </div>
                <div class="workforce-modal-body">
                    <form method="post" id="fieldForm">
                        <?php wp_nonce_field('workforce_save_field'); ?>
                        <input type="hidden" name="field_id" id="field_id" value="">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="field_label">عنوان فارسی فیلد</label></th>
                                <td>
                                    <input type="text" name="field_label" id="field_label" class="regular-text" required>
                                    <p class="description">عنوان فیلد به زبان فارسی که در جدول نمایش داده می‌شود</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="field_type">نوع فیلد</label></th>
                                <td>
                                    <select name="field_type" id="field_type" class="regular-text" onchange="toggleOptionsField()" required>
                                        <option value="text">متن</option>
                                        <option value="number">عدد</option>
                                        <option value="decimal">اعشار</option>
                                        <option value="date">تاریخ</option>
                                        <option value="time">زمان</option>
                                        <option value="select">لیست انتخابی</option>
                                        <option value="checkbox">چک‌باکس</option>
                                    </select>
                                </td>
                            </tr>
                            <tr id="optionsRow" style="display: none;">
                                <th scope="row"><label for="options">گزینه‌ها</label></th>
                                <td>
                                    <textarea name="options" id="options" class="large-text" rows="5" placeholder="هر گزینه در یک خط"></textarea>
                                    <p class="description">گزینه‌های لیست انتخابی (هر گزینه در یک خط جداگانه)</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">ویژگی‌ها</th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="is_required" id="is_required" value="1">
                                            <span>ضروری (هایلایت در پنل)</span>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="is_locked" id="is_locked" value="1">
                                            <span>قفل (غیرقابل ویرایش توسط مدیران)</span>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="is_monitoring" id="is_monitoring" value="1">
                                            <span>مانیتورینگ (ساخت کارت خودکار)</span>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="is_key" id="is_key" value="1">
                                            <span>کلید (کدملی - بررسی تکراری)</span>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="display_order">ترتیب نمایش</label></th>
                                <td>
                                    <input type="number" name="display_order" id="display_order" class="small-text" value="999" min="1">
                                    <p class="description">اعداد کمتر اولویت بیشتری دارند</p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
    <button type="submit" name="submit_field" class="button button-primary">ثبت فیلد</button>
    <button type="button" class="button" onclick="hideFieldModal()">انصراف</button>
</p>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        function showAddFieldModal() {
            document.getElementById('modalTitle').textContent = 'افزودن فیلد جدید';
            document.getElementById('fieldForm').reset();
            document.getElementById('field_id').value = '';
            document.getElementById('fieldModal').style.display = 'block';
            toggleOptionsField();
        }
        
        function hideFieldModal() {
            document.getElementById('fieldModal').style.display = 'none';
        }
        
        function editField(fieldId) {
            // بارگذاری داده‌های فیلد از طریق AJAX
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'workforce_get_field_data',
                    field_id: fieldId,
                    nonce: '<?php echo wp_create_nonce('workforce_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        var field = response.data;
                        document.getElementById('modalTitle').textContent = 'ویرایش فیلد';
                        document.getElementById('field_id').value = field.id;
                        document.getElementById('field_label').value = field.field_label;
                        document.getElementById('field_type').value = field.field_type;
                        document.getElementById('display_order').value = field.display_order;
                        document.getElementById('is_required').checked = field.is_required == 1;
                        document.getElementById('is_locked').checked = field.is_locked == 1;
                        document.getElementById('is_monitoring').checked = field.is_monitoring == 1;
                        document.getElementById('is_key').checked = field.is_key == 1;
                        
                        if (field.field_type === 'select' && field.options) {
                            document.getElementById('options').value = field.options.join('\n');
                        } else {
                            document.getElementById('options').value = '';
                        }
                        
                        document.getElementById('fieldModal').style.display = 'block';
                        toggleOptionsField();
                    }
                }
            });
        }
        
        function toggleOptionsField() {
            var fieldType = document.getElementById('field_type').value;
            var optionsRow = document.getElementById('optionsRow');
            
            if (fieldType === 'select') {
                optionsRow.style.display = 'table-row';
            } else {
                optionsRow.style.display = 'none';
            }
        }
        </script>
    </div>
    <?php
}

/**
 * مدیریت ادارات
 */
function workforce_admin_departments() {
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
// پردازش فرم افزودن/ویرایش اداره
// پردازش فرم افزودن/ویرایش اداره
if (isset($_POST['submit_department'])) {
    $nonce = $_POST['_wpnonce'] ?? '';
    
    if (wp_verify_nonce($nonce, 'workforce_save_department')) {
        $department_data = [
            'name' => sanitize_text_field($_POST['name']),
            'color' => sanitize_hex_color($_POST['color']),
            'parent_id' => !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : 0,
        ];
        
        // گرفتن مدیران (اگر ارسال شده باشد)
        $manager_ids = isset($_POST['manager_ids']) ? array_map('intval', (array)$_POST['manager_ids']) : [];
        
        if (isset($_POST['department_id']) && !empty($_POST['department_id'])) {
            // ویرایش اداره موجود
            $department_id = intval($_POST['department_id']);
            
            // اول اداره را آپدیت کن (بدون manager_id)
            workforce_update_department($department_id, $department_data);
            
            // سپس مدیران را تنظیم کن
            if (!empty($manager_ids)) {
                workforce_set_department_managers($department_id, $manager_ids);
            } else {
                // اگر هیچ مدیری انتخاب نشده، مدیر را حذف کن
                global $wpdb;
                $departments_table = $wpdb->prefix . WF_TABLE_PREFIX . 'departments';
                $wpdb->update(
                    $departments_table,
                    ['manager_id' => null],
                    ['id' => $department_id]
                );
                
                // مدیران قبلی را حذف کن
                $managers_table = $wpdb->prefix . WF_TABLE_PREFIX . 'department_managers';
                $wpdb->delete($managers_table, ['department_id' => $department_id]);
            }
            
            echo '<div class="updated"><p>اداره با موفقیت ویرایش شد.</p></div>';
        } else {
            // افزودن اداره جدید
            $department_id = workforce_add_department($department_data);
            
            if ($department_id && !empty($manager_ids)) {
                // مدیران را تنظیم کن
                workforce_set_department_managers($department_id, $manager_ids);
            }
            
            echo '<div class="updated"><p>اداره جدید با موفقیت افزوده شد.</p></div>';
        }
    }
}
    
    // پردازش حذف اداره
    if (isset($_GET['delete_department'])) {
        $nonce = $_GET['_wpnonce'] ?? '';
        
        if (wp_verify_nonce($nonce, 'delete_department_' . $_GET['delete_department'])) {
            $result = workforce_delete_department(intval($_GET['delete_department']));
            if ($result) {
                echo '<div class="updated"><p>اداره با موفقیت حذف شد.</p></div>';
            } else {
                echo '<div class="error"><p>این اداره دارای پرسنل است و نمی‌توان آن را حذف کرد.</p></div>';
            }
        }
    }
    
    $departments = workforce_get_all_departments(true);
    $users = get_users(['role__in' => ['workforce_org_manager', 'workforce_dept_manager']]);
    ?>
    
    <div class="wrap workforce-admin-departments">
        <h1 class="wp-heading-inline">مدیریت ادارات</h1>
        <button type="button" class="page-title-action" onclick="showAddDepartmentModal()">افزودن اداره جدید</button>
        <hr class="wp-header-end">
        
        <div class="workforce-departments-tree">
            <?php
            function render_department_tree($departments, $parent_id = 0, $level = 0) {
                $children = array_filter($departments, function($dept) use ($parent_id) {
                    return $dept->parent_id == $parent_id;
                });
                
                if (empty($children)) {
                    return;
                }
                
                echo '<ul class="workforce-tree-list">';
                foreach ($children as $dept) {
                    $manager = $dept->manager_id ? get_userdata($dept->manager_id) : null;
                    $personnel_count = workforce_get_department_personnel_count($dept->id);
                    
                    echo '<li class="workforce-tree-item" data-level="' . $level . '">';
                    echo '<div class="tree-item-header" style="border-color: ' . esc_attr($dept->color) . '">';
                    echo '<span class="tree-toggle" onclick="toggleTreeItem(this)">▶</span>';
                    echo '<span class="tree-name">' . esc_html($dept->name) . '</span>';
                    echo '<span class="tree-badge" style="background-color: ' . esc_attr($dept->color) . '"></span>';
                    echo '<span class="tree-details">';
                    // گرفتن مدیران از جدول department_managers
$dept_managers = workforce_get_department_managers($dept->id);
// جایگزین کردن این بخش:
if (!empty($dept_managers)) {
    $manager_names = [];
    foreach ($dept_managers as $dept_manager) {
        $mgr_user = get_userdata($dept_manager->user_id);
        if ($mgr_user) {
            $prefix = $dept_manager->is_primary ? '⭐ ' : '';
            $manager_names[] = $prefix . $mgr_user->display_name;
        }
    }
    echo '<span class="tree-manager" title="' . esc_attr(implode('، ', $manager_names)) . '">👤 ' . 
         esc_html(implode('، ', array_slice($manager_names, 0, 2))) . 
         (count($manager_names) > 2 ? ' و ' . (count($manager_names) - 2) . ' نفر دیگر' : '') . 
         '</span>';
}

// با این کد جدید:
if (!empty($dept_managers)) {
    $manager_names = [];
    foreach ($dept_managers as $dept_manager) {
        $mgr_user = get_userdata($dept_manager->user_id);
        if ($mgr_user) {
            $prefix = $dept_manager->is_primary ? '⭐ ' : '';
            $manager_names[] = $prefix . $mgr_user->display_name;
        }
    }
    echo '<span class="dept-manager" title="' . esc_attr('مدیران: ' . implode('، ', $manager_names)) . '">👤 مدیران: ' . 
         esc_html(implode('، ', $manager_names)) .  // نمایش همه مدیران
         '</span>';
}
                    echo '<span class="tree-personnel">👥 ' . esc_html($personnel_count) . ' نفر</span>';
                    echo '</span>';
                    echo '<span class="tree-actions">';
                    echo '<button type="button" class="button button-small" onclick="editDepartment(' . $dept->id . ')">ویرایش</button>';
                    echo '<a href="' . wp_nonce_url(admin_url('admin.php?page=workforce-departments&delete_department=' . $dept->id), 'delete_department_' . $dept->id, '_wpnonce') . '" class="button button-small button-link-delete" onclick="return confirm(\'آیا از حذف این اداره اطمینان دارید؟\')">حذف</a>';
                    echo '</span>';
                    echo '</div>';
                    
                    // بازگشتی برای زیرشاخه‌ها
                    echo '<div class="tree-item-children" style="display: none;">';
                    render_department_tree($departments, $dept->id, $level + 1);
                    echo '</div>';
                    
                    echo '</li>';
                }
                echo '</ul>';
            }
            
            if (empty($departments)) {
                echo '<p>هیچ اداره‌ای ایجاد نشده است.</p>';
            } else {
                render_department_tree($departments);
            }
            ?>
        </div>
        
        <!-- مودال افزودن/ویرایش اداره -->
        <div id="departmentModal" class="workforce-modal" style="display: none;">
            <div class="workforce-modal-content">
                <div class="workforce-modal-header">
                    <h2 id="departmentModalTitle">افزودن اداره جدید</h2>
                    <span class="workforce-modal-close" onclick="hideDepartmentModal()">&times;</span>
                </div>
                <div class="workforce-modal-body">
                    <form method="post" id="departmentForm">
                        <?php wp_nonce_field('workforce_save_department'); ?>
                        <input type="hidden" name="department_id" id="department_id" value="">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="name">نام فارسی اداره</label></th>
                                <td>
                                    <input type="text" name="name" id="name" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
    <th scope="row"><label for="parent_id">اداره مافوق</label></th>
    <td>
        <select name="parent_id" id="parent_id" class="regular-text">
            <option value="0">بدون مافوق (سطح اول)</option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?php echo esc_attr($dept->id); ?>">
                    <?php echo esc_html($dept->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </td>
</tr>
<tr>
    <th scope="row"><label for="manager_ids">مدیران اداره</label></th>
    <td>
        <select name="manager_ids[]" id="manager_ids" class="regular-text" multiple="multiple" style="height: 150px;">
            <?php 
            $users = get_users([
                'orderby' => 'display_name',
                'order' => 'ASC'
            ]);
            
            foreach ($users as $user): 
                $role_names = [];
                foreach ($user->roles as $role) {
                    $role_obj = get_role($role);
                    if ($role_obj) {
                        $role_names[] = translate_user_role($role_obj->name);
                    }
                }
            ?>
                <option value="<?php echo esc_attr($user->ID); ?>">
                    <?php echo esc_html($user->display_name . ' (' . implode(', ', $role_names) . ') - ' . $user->user_email); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            برای انتخاب چند مدیر: در ویندوز کلید Ctrl را نگه دارید و کلیک کنید. در مک کلید Command را نگه دارید.
            <br>مدیر اول به عنوان مدیر اصلی در نظر گرفته می‌شود.
        </p>
    </td>
</tr>
                            <tr>
                                <th scope="row"><label for="color">رنگ مشخصه</label></th>
                                <td>
                                    <input type="color" name="color" id="color" value="#3498db" style="width: 50px; height: 30px; vertical-align: middle;">
                                    <span style="margin-right: 10px;">یا کد HEX:</span>
                                    <input type="text" name="color_text" id="color_text" value="#3498db" class="small-text" pattern="^#[0-9A-Fa-f]{6}$" maxlength="7">
                                    <button type="button" class="button button-small" onclick="document.getElementById('color').value = getRandomColor(); document.getElementById('color_text').value = document.getElementById('color').value;">رنگ تصادفی</button>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" name="submit_department" class="button button-primary">ثبت اداره</button>
                            <button type="button" class="button" onclick="hideDepartmentModal()">انصراف</button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        function showAddDepartmentModal() {
            document.getElementById('departmentModalTitle').textContent = 'افزودن اداره جدید';
            document.getElementById('departmentForm').reset();
            document.getElementById('department_id').value = '';
            document.getElementById('color').value = '#3498db';
            document.getElementById('color_text').value = '#3498db';
            document.getElementById('departmentModal').style.display = 'block';
        }
        
        function hideDepartmentModal() {
            document.getElementById('departmentModal').style.display = 'none';
        }
        
function editDepartment(deptId) {
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'workforce_get_department_managers',
            department_id: deptId,
            nonce: '<?php echo wp_create_nonce('workforce_nonce'); ?>'
        },
        success: function(response) {
            if (response.success) {
                var dept = response.data.department;
                var managers = response.data.managers;
                
                document.getElementById('departmentModalTitle').textContent = 'ویرایش اداره';
                document.getElementById('department_id').value = dept.id;
                document.getElementById('name').value = dept.name;
                document.getElementById('parent_id').value = dept.parent_id || 0;
                document.getElementById('color').value = dept.color;
                document.getElementById('color_text').value = dept.color;
                
                // انتخاب مدیران در select
                var managerSelect = document.getElementById('manager_ids');
                if (managerSelect) {
                    // ابتدا همه را از انتخاب خارج کن
                    for (var i = 0; i < managerSelect.options.length; i++) {
                        managerSelect.options[i].selected = false;
                    }
                    
                    // مدیران را انتخاب کن
                    managers.forEach(function(manager) {
                        for (var i = 0; i < managerSelect.options.length; i++) {
                            if (managerSelect.options[i].value == manager.user_id) {
                                managerSelect.options[i].selected = true;
                                break;
                            }
                        }
                    });
                }
                
                document.getElementById('departmentModal').style.display = 'block';
            } else {
                alert('خطا در دریافت اطلاعات اداره: ' + response.data.message);
            }
        },
        error: function(xhr, status, error) {
            console.log('AJAX Error:', xhr.responseText);
            alert('خطا در ارتباط با سرور');
        }
    });
}
        
        function toggleTreeItem(element) {
            var parent = element.closest('.workforce-tree-item');
            var children = parent.querySelector('.tree-item-children');
            
            if (children.style.display === 'none') {
                children.style.display = 'block';
                element.textContent = '▼';
            } else {
                children.style.display = 'none';
                element.textContent = '▶';
            }
        }
        
        function getRandomColor() {
            var colors = ['#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#c0392b', '#16a085', '#8e44ad'];
            return colors[Math.floor(Math.random() * colors.length)];
        }
        
        // هماهنگی رنگ‌ها
        document.getElementById('color').addEventListener('input', function() {
            document.getElementById('color_text').value = this.value;
        });
        
        document.getElementById('color_text').addEventListener('input', function() {
            if (this.value.match(/^#[0-9A-Fa-f]{6}$/)) {
                document.getElementById('color').value = this.value;
            }
        });
        </script>
    </div>
    <?php
}

// دیباگ AJAX
add_action('wp_ajax_workforce_debug_test', 'workforce_debug_test');
add_action('wp_ajax_nopriv_workforce_debug_test', 'workforce_debug_test');

function workforce_debug_test() {
    error_log('AJAX Test - شروع');
    error_log('POST Data: ' . print_r($_POST, true));
    error_log('Nonce: ' . ($_POST['nonce'] ?? 'ندارد'));
    error_log('User ID: ' . get_current_user_id());
    error_log('User Cap: ' . (current_user_can('manage_options') ? 'دارد' : 'ندارد'));
    
    wp_send_json_success(['message' => 'AJAX تست موفق', 'data' => $_POST]);
}
function workforce_admin_personnel() {
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
    $current_tab = $_GET['tab'] ?? 'list';
    $department_id = $_GET['department_id'] ?? 0;
    $page = $_GET['paged'] ?? 1;
    $limit = 25;
    $offset = ($page - 1) * $limit;
    
    $departments = workforce_get_all_departments();
    $fields = workforce_get_all_fields();
    
    // پردازش افزودن پرسنل جدید
    if ($current_tab === 'add' && isset($_POST['add_personnel'])) {
        $nonce = $_POST['_wpnonce'] ?? '';
        
        if (wp_verify_nonce($nonce, 'workforce_add_personnel')) {
            $personnel_data = [
                'department_id' => intval($_POST['department_id']),
                'national_code' => sanitize_text_field($_POST['national_code']),
                'first_name' => sanitize_text_field($_POST['first_name']),
                'last_name' => sanitize_text_field($_POST['last_name']),
                'employment_date' => sanitize_text_field($_POST['employment_date']),
                'employment_type' => sanitize_text_field($_POST['employment_type']),
                'status' => sanitize_text_field($_POST['status']),
            ];
            
            // جمع‌آوری فیلدهای متا
            $meta_data = [];
            foreach ($fields as $field) {
                if (!in_array($field->field_name, ['national_code', 'first_name', 'last_name', 'employment_date'])) {
                    $field_name = 'field_' . $field->id;
                    if (isset($_POST[$field_name])) {
                        $meta_data[$field->id] = sanitize_text_field($_POST[$field_name]);
                    }
                }
            }
            
            // ذخیره پرسنل
            $personnel_id = workforce_add_personnel($personnel_data, $meta_data);
            
            if ($personnel_id) {
                echo '<div class="updated"><p>پرسنل جدید با موفقیت افزوده شد.</p></div>';
                // ریدایرکت به لیست
                echo '<script>window.location.href = "' . admin_url('admin.php?page=workforce-personnel&tab=list') . '";</script>';
                return;
            } else {
                echo '<div class="error"><p>خطا در افزودن پرسنل جدید.</p></div>';
            }
        }
    }
    ?>
    
    <div class="wrap workforce-admin-personnel">
        <h1 class="wp-heading-inline">مدیریت پرسنل</h1>
        <a href="<?php echo admin_url('admin.php?page=workforce-personnel&tab=add'); ?>" class="page-title-action">افزودن دستی</a>
        <a href="<?php echo admin_url('admin.php?page=workforce-personnel&tab=import'); ?>" class="page-title-action">آپلود اکسل</a>
        <hr class="wp-header-end">
        
        <h2 class="nav-tab-wrapper">
            <a href="<?php echo admin_url('admin.php?page=workforce-personnel&tab=list'); ?>" class="nav-tab <?php echo $current_tab === 'list' ? 'nav-tab-active' : ''; ?>">لیست پرسنل</a>
            <a href="<?php echo admin_url('admin.php?page=workforce-personnel&tab=add'); ?>" class="nav-tab <?php echo $current_tab === 'add' ? 'nav-tab-active' : ''; ?>">افزودن دستی</a>
            <a href="<?php echo admin_url('admin.php?page=workforce-personnel&tab=import'); ?>" class="nav-tab <?php echo $current_tab === 'import' ? 'nav-tab-active' : ''; ?>">آپلود اکسل</a>
        </h2>
        
        <div class="workforce-personnel-content">
            <?php if ($current_tab === 'list'): ?>
                <div class="workforce-personnel-filter">
                    <form method="get">
                        <input type="hidden" name="page" value="workforce-personnel">
                        <input type="hidden" name="tab" value="list">
                        
                        <label for="filter_department">اداره:</label>
                        <select name="department_id" id="filter_department" onchange="this.form.submit()">
                            <option value="0">همه ادارات</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo esc_attr($dept->id); ?>" <?php selected($department_id, $dept->id); ?>>
                                    <?php echo esc_html($dept->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <label for="filter_status">وضعیت:</label>
                        <select name="status" id="filter_status" onchange="this.form.submit()">
                            <option value="">همه</option>
                            <option value="active" <?php selected($_GET['status'] ?? '', 'active'); ?>>فعال</option>
                            <option value="inactive" <?php selected($_GET['status'] ?? '', 'inactive'); ?>>غیرفعال</option>
                            <option value="suspended" <?php selected($_GET['status'] ?? '', 'suspended'); ?>>تعلیق</option>
                            <option value="retired" <?php selected($_GET['status'] ?? '', 'retired'); ?>>بازنشسته</option>
                        </select>
                        
                        <label for="filter_search">جستجو:</label>
                        <input type="text" name="search" id="filter_search" value="<?php echo esc_attr($_GET['search'] ?? ''); ?>" placeholder="نام، نام خانوادگی، کدملی">
                        <button type="submit" class="button">فیلتر</button>
                        <a href="<?php echo admin_url('admin.php?page=workforce-personnel&tab=list'); ?>" class="button">پاک کردن فیلترها</a>
                    </form>
                </div>
                
                <!-- بخش اصلاح شده نمایش لیست پرسنل -->
                <?php
                global $wpdb;
                $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
                $departments_table = $wpdb->prefix . WF_TABLE_PREFIX . 'departments';
                
                // ساختن کوئری داینامیک
                $sql = "SELECT p.*, d.name as department_name, d.color as department_color 
                        FROM $personnel_table p 
                        LEFT JOIN $departments_table d ON p.department_id = d.id 
                        WHERE p.is_deleted = 0";
                
                $where_clauses = [];
                $query_params = [];
                
                // اعمال فیلترها
                if ($department_id > 0) {
                    $where_clauses[] = "p.department_id = %d";
                    $query_params[] = $department_id;
                }
                
                if (!empty($_GET['status'])) {
                    $where_clauses[] = "p.status = %s";
                    $query_params[] = sanitize_text_field($_GET['status']);
                }
                
                if (!empty($_GET['search'])) {
                    $search_term = '%' . $wpdb->esc_like(sanitize_text_field($_GET['search'])) . '%';
                    $where_clauses[] = "(p.first_name LIKE %s OR p.last_name LIKE %s OR p.national_code LIKE %s)";
                    $query_params[] = $search_term;
                    $query_params[] = $search_term;
                    $query_params[] = $search_term;
                }
                
                if (!empty($where_clauses)) {
                    $sql .= " AND " . implode(" AND ", $where_clauses);
                }
                
                // تعداد کل برای صفحه‌بندی
                $count_sql = "SELECT COUNT(*) FROM $personnel_table p WHERE p.is_deleted = 0";
                if (!empty($where_clauses)) {
                    $count_sql .= " AND " . implode(" AND ", $where_clauses);
                }
                
                if (!empty($query_params)) {
                    $total_count = $wpdb->get_var($wpdb->prepare($count_sql, $query_params));
                } else {
                    $total_count = $wpdb->get_var($count_sql);
                }
                
                $total_pages = ceil($total_count / $limit);
                
                // کوئری نهایی با صفحه‌بندی
                $sql .= " ORDER BY p.last_name ASC, p.first_name ASC LIMIT %d OFFSET %d";
                $query_params[] = $limit;
                $query_params[] = $offset;
                
                if (!empty($query_params)) {
                    $personnel = $wpdb->get_results($wpdb->prepare($sql, $query_params));
                } else {
                    $personnel = $wpdb->get_results($sql);
                }
                ?>
                
                <div class="workforce-personnel-list">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ردیف</th>
                                <th>کدملی</th>
                                <th>نام و نام خانوادگی</th>
                                <th>اداره</th>
                                <th>تاریخ استخدام</th>
                                <th>وضعیت</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($personnel)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 30px;">
                                        <div class="notice notice-warning">
                                            <h3>هیچ پرسنلی یافت نشد</h3>
                                            <p>در حال حاضر هیچ پرسنلی در سیستم ثبت نشده است.</p>
                                            <p>تعداد کل رکوردها در دیتابیس: <?php echo esc_html($total_count); ?></p>
                                            <p>
                                                <a href="<?php echo admin_url('admin.php?page=workforce-personnel&tab=add'); ?>" class="button button-primary">
                                                    افزودن پرسنل جدید
                                                </a>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($personnel as $index => $person): ?>
                                    <tr>
                                        <td><?php echo esc_html(($page - 1) * $limit + $index + 1); ?></td>
                                        <td><?php echo esc_html($person->national_code ?: '---'); ?></td>
                                        <td>
                                            <strong><?php echo esc_html($person->first_name . ' ' . $person->last_name); ?></strong>
                                            <br>
                                            <small style="color: #666;">ID: <?php echo esc_html($person->id); ?></small>
                                        </td>
                                        <td>
                                            <?php if (!empty($person->department_name)): ?>
                                                <span class="dept-badge" style="background-color: <?php echo esc_attr($person->department_color ?: '#3498db'); ?>;">
                                                    <?php echo esc_html($person->department_name); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="dept-badge" style="background-color: #95a5a6;">بدون اداره</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html($person->employment_date ?: '---'); ?></td>
                                        <td>
                                            <?php
                                            $status_labels = [
                                                'active' => '<span class="status-badge status-active">فعال</span>',
                                                'inactive' => '<span class="status-badge status-inactive">غیرفعال</span>',
                                                'suspended' => '<span class="status-badge status-suspended">تعلیق</span>',
                                                'retired' => '<span class="status-badge status-retired">بازنشسته</span>',
                                            ];
                                            echo $status_labels[$person->status] ?? '<span class="status-badge">' . esc_html($person->status) . '</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <div class="row-actions">
                                                <span class="edit">
                                                    <button type="button" class="button-link edit-personnel" 
                                                            onclick="editPersonnel(<?php echo $person->id; ?>)">
                                                        ویرایش
                                                    </button>
                                                </span>
                                                |
                                                <span class="view">
                                                    <button type="button" class="button-link view-personnel" 
                                                            onclick="viewPersonnel(<?php echo $person->id; ?>)">
                                                        مشاهده
                                                    </button>
                                                </span>
                                                |
                                                <span class="delete">
                                                    <button type="button" class="button-link delete-personnel" 
                                                            onclick="deletePersonnel(<?php echo $person->id; ?>)" 
                                                            style="color: #dc3232;">
                                                        حذف
                                                    </button>
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($total_pages > 1): ?>
                        <div class="tablenav">
                            <div class="tablenav-pages">
                                <span class="displaying-num">
                                    نمایش 
                                    <?php echo esc_html(($page - 1) * $limit + 1); ?>-<?php echo esc_html(min($page * $limit, $total_count)); ?> 
                                    از <?php echo esc_html($total_count); ?> رکورد
                                </span>
                                
                                <span class="pagination-links">
                                    <?php
                                    // دکمه اول
                                    if ($page > 1) {
                                        echo '<a class="first-page button" href="' . add_query_arg('paged', 1) . '">اولین</a>';
                                    } else {
                                        echo '<span class="first-page button disabled">اولین</span>';
                                    }
                                    
                                    // دکمه قبلی
                                    if ($page > 1) {
                                        echo '<a class="prev-page button" href="' . add_query_arg('paged', $page - 1) . '">قبلی</a>';
                                    } else {
                                        echo '<span class="prev-page button disabled">قبلی</span>';
                                    }
                                    
                                    // نمایش شماره صفحه
                                    echo '<span class="paging-input">
                                            <span class="screen-reader-text">صفحه فعلی</span>
                                            <input class="current-page" type="text" name="paged" value="' . $page . '" size="1" aria-describedby="table-paging">
                                            <span class="tablenav-paging-text"> از <span class="total-pages">' . $total_pages . '</span></span>
                                          </span>';
                                    
                                    // دکمه بعدی
                                    if ($page < $total_pages) {
                                        echo '<a class="next-page button" href="' . add_query_arg('paged', $page + 1) . '">بعدی</a>';
                                    } else {
                                        echo '<span class="next-page button disabled">بعدی</span>';
                                    }
                                    
                                    // دکمه آخر
                                    if ($page < $total_pages) {
                                        echo '<a class="last-page button" href="' . add_query_arg('paged', $total_pages) . '">آخرین</a>';
                                    } else {
                                        echo '<span class="last-page button disabled">آخرین</span>';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($current_tab === 'add'): ?>
                <!-- بقیه کد بدون تغییر -->
                <!-- ... -->
                <div class="workforce-add-personnel">
<form method="post" action="<?php echo admin_url('admin.php?page=workforce-personnel&tab=add'); ?>">
    <?php wp_nonce_field('workforce_add_personnel', '_wpnonce'); ?>
    
    <!-- این دو خط را حتماً اضافه کن: -->
    <input type="hidden" name="action" value="add_personnel">
    
    <div class="workforce-form-section">
        <h3>اطلاعات پایه</h3>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="add_department_id">اداره</label></th>
                <td>
                    <select name="department_id" id="add_department_id" class="regular-text" required>
                        <option value="">انتخاب کنید</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo esc_attr($dept->id); ?>"><?php echo esc_html($dept->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="add_national_code">کدملی</label></th>
                <td>
                    <input type="text" name="national_code" id="add_national_code" class="regular-text" required pattern="[0-9]{10}">
                    <p class="description">۱۰ رقم عددی</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="add_first_name">نام</label></th>
                <td>
                    <input type="text" name="first_name" id="add_first_name" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="add_last_name">نام خانوادگی</label></th>
                <td>
                    <input type="text" name="last_name" id="add_last_name" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="add_employment_date">تاریخ استخدام</label></th>
                <td>
                    <input type="text" name="employment_date" id="add_employment_date" 
                           class="regular-text" required 
                           pattern="^[۰-۹]{4}/[۰-۹]{2}/[۰-۹]{2}$"
                           placeholder="۱۴۰۳/۰۱/۰۱">
                    <p class="description">فرمت: ۱۴۰۳/۰۱/۰۱ (سال/ماه/روز)</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="add_employment_type">نوع استخدام</label></th>
                <td>
                    <select name="employment_type" id="add_employment_type" class="regular-text">
                        <option value="permanent">دائمی</option>
                        <option value="contract">پیمانی</option>
                        <option value="temporary">موقت</option>
                        <option value="project">پروژه‌ای</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="add_status">وضعیت</label></th>
                <td>
                    <select name="status" id="add_status" class="regular-text">
                        <option value="active">فعال</option>
                        <option value="inactive">غیرفعال</option>
                        <option value="suspended">تعلیق</option>
                        <option value="retired">بازنشسته</option>
                    </select>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="workforce-form-section">
        <h3>اطلاعات تکمیلی</h3>
        <table class="form-table">
            <?php foreach ($fields as $field): ?>
                <?php if (!in_array($field->field_name, ['national_code', 'first_name', 'last_name', 'employment_date'])): ?>
                    <tr>
                        <th scope="row">
                            <label for="field_<?php echo esc_attr($field->id); ?>">
                                <?php echo esc_html($field->field_label); ?>
                                <?php if ($field->is_required): ?><span class="required">*</span><?php endif; ?>
                            </label>
                        </th>
                        <td>
                            <?php workforce_render_field_input($field, 'field_' . $field->id, ''); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </table>
    </div>
    
    <p class="submit">
        <button type="submit" name="add_personnel" class="button button-primary">ثبت پرسنل</button>
        <button type="reset" class="button">بازنشانی</button>
    </p>
</form>
                </div>
                
            <?php elseif ($current_tab === 'import'): ?>
                <div class="workforce-import-personnel">
                    <div class="workforce-import-steps">
                        <div class="step active">
                            <span class="step-number">۱</span>
                            <span class="step-title">آپلود فایل</span>
                        </div>
                        <div class="step">
                            <span class="step-number">۲</span>
                            <span class="step-title">تطبیق ستون‌ها</span>
                        </div>
                        <div class="step">
                            <span class="step-number">۳</span>
                            <span class="step-title">بررسی و ثبت</span>
                        </div>
                    </div>
                    
                    <div class="workforce-import-content">
                        <form id="importForm" enctype="multipart/form-data">
                            <?php wp_nonce_field('workforce_import_excel'); ?>
                            
                            <div class="form-group">
                                <label for="import_file">فایل اکسل (xlsx, xls, csv)</label>
                                <input type="file" name="import_file" id="import_file" accept=".xlsx,.xls,.csv" required>
                                <p class="description">حداکثر حجم: ۱۰ مگابایت</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="import_department_id">اداره مقصد</label>
                                <select name="department_id" id="import_department_id" class="regular-text" required>
                                    <option value="">انتخاب کنید</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo esc_attr($dept->id); ?>"><?php echo esc_html($dept->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="overwrite" id="overwrite" value="1">
                                    رکوردهای تکراری را بازنویسی کن
                                </label>
                                <p class="description">اگر کدملی تکراری وجود داشته باشد، اطلاعات قبلی بازنویسی می‌شود</p>
                            </div>
                            
                            <p class="submit">
                                <button type="button" class="button button-primary" onclick="uploadExcelFile()">بارگذاری و ادامه</button>
                            </p>
                        </form>
                        
                        <div id="importPreview" style="display: none;">
                            <h3>پیش‌نمایش داده‌ها</h3>
                            <div id="previewTable"></div>
                            <div id="columnMapping"></div>
                            <p class="submit">
                                <button type="button" class="button button-primary" onclick="confirmImport()">تایید و ثبت اطلاعات</button>
                                <button type="button" class="button" onclick="cancelImport()">انصراف</button>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- مودال مشاهده/ویرایش پرسنل -->
    <div id="personnelModal" class="workforce-modal" style="display: none;">
        <div class="workforce-modal-content wide-modal">
            <div class="workforce-modal-header">
                <h2 id="personnelModalTitle">مشاهده پرسنل</h2>
                <span class="workforce-modal-close" onclick="hidePersonnelModal()">&times;</span>
            </div>
            <div class="workforce-modal-body" id="personnelModalBody">
                <!-- محتوای داینامیک -->
            </div>
        </div>
    </div>
    
    <script>
    function editPersonnel(personnelId) {
        loadPersonnelData(personnelId, 'edit');
    }
    
    function viewPersonnel(personnelId) {
        loadPersonnelData(personnelId, 'view');
    }
    
    function loadPersonnelData(personnelId, mode) {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'workforce_get_personnel_data',
                personnel_id: personnelId,
                mode: mode,
                nonce: '<?php echo wp_create_nonce('workforce_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    document.getElementById('personnelModalTitle').textContent = mode === 'edit' ? 'ویرایش پرسنل' : 'مشاهده پرسنل';
                    document.getElementById('personnelModalBody').innerHTML = response.data.html;
                    document.getElementById('personnelModal').style.display = 'block';
                    
                    if (mode === 'edit') {
                        // فعال‌سازی datepicker
                        jQuery('.jdatepicker').persianDatepicker({
                            format: 'YYYY/MM/DD',
                            observer: true,
                            persianDigit: false
                        });
                    }
                }
            }
        });
    }
    
    function hidePersonnelModal() {
        document.getElementById('personnelModal').style.display = 'none';
    }
    
function deletePersonnel(personnelId) {
    if (confirm('⚠️ آیا از حذف این پرسنل اطمینان دارید؟\nاین عمل غیرقابل بازگشت است.')) {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'workforce_delete_personnel_admin',
                personnel_id: personnelId,
                nonce: '<?php echo wp_create_nonce("workforce_delete"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('پرسنل با موفقیت حذف شد.');
                    location.reload();
                } else {
                    alert('خطا: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                alert('خطا در ارتباط با سرور: ' + error);
            }
        });
    }
}
// ... توابع قبلی ...

// تابع ذخیره تغییرات پرسنل در مودال ویرایش
function savePersonnelChanges() {
    var form = document.getElementById('personnelForm');
    var formData = new FormData(form);
    
    // اضافه کردن action و nonce
    formData.append('action', 'workforce_update_personnel');
    formData.append('nonce', '<?php echo wp_create_nonce("workforce_update"); ?>');
    
    // نمایش لودینگ
    var submitBtn = form.querySelector('button[type="button"]');
    var originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner is-active"></span> در حال ذخیره...';
    submitBtn.disabled = true;
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('تغییرات با موفقیت ذخیره شد.');
                location.reload();
            } else {
                alert('خطا: ' + response.data.message);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        },
        error: function(xhr, status, error) {
            alert('خطا در ارتباط با سرور: ' + error);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
}

// تابع حذف پرسنل
function deletePersonnel(personnelId) {
    if (confirm('⚠️ آیا از حذف این پرسنل اطمینان دارید؟\nاین عمل غیرقابل بازگشت است.')) {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'workforce_delete_personnel_admin',
                personnel_id: personnelId,
                nonce: '<?php echo wp_create_nonce("workforce_delete"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('پرسنل با موفقیت حذف شد.');
                    location.reload();
                } else {
                    alert('خطا: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                alert('خطا در ارتباط با سرور: ' + error);
            }
        });
    }
}

// تابع مشاهده پرسنل
function viewPersonnel(personnelId) {
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'workforce_view_personnel',
            personnel_id: personnelId,
            nonce: '<?php echo wp_create_nonce("workforce_view"); ?>'
        },
        success: function(response) {
            if (response.success) {
                alert('اطلاعات پرسنل:\n\n' + response.data);
            } else {
                alert('خطا: ' + response.data.message);
            }
        },
        error: function(xhr, status, error) {
            alert('خطا در ارتباط با سرور: ' + error);
        }
    });
}

// تابع ویرایش پرسنل
function editPersonnel(personnelId) {
    loadPersonnelData(personnelId, 'edit');
}

// تابع مشاهده پرسنل
function viewPersonnel(personnelId) {
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'workforce_view_personnel',
            personnel_id: personnelId,
            nonce: '<?php echo wp_create_nonce("workforce_view"); ?>'
        },
        success: function(response) {
            if (response.success) {
                alert('اطلاعات پرسنل:\n\n' + response.data);
            } else {
                alert('خطا: ' + response.data.message);
            }
        },
        error: function(xhr, status, error) {
            alert('خطا در ارتباط با سرور: ' + error);
        }
    });
}

// تابع ویرایش پرسنل (باید از قبل موجود باشد)
function editPersonnel(personnelId) {
    loadPersonnelData(personnelId, 'edit');
}
    
    function uploadExcelFile() {
        var formData = new FormData(document.getElementById('importForm'));
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    document.getElementById('importPreview').style.display = 'block';
                    document.getElementById('previewTable').innerHTML = response.data.preview;
                    document.getElementById('columnMapping').innerHTML = response.data.mapping;
                    document.getElementById('importForm').style.display = 'none';
                } else {
                    alert('خطا: ' + response.data.message);
                }
            }
        });
    }
    
    function confirmImport() {
        var mappings = {};
        jQuery('.column-mapping').each(function() {
            var excelCol = jQuery(this).data('excel');
            var fieldId = jQuery(this).val();
            if (fieldId) {
                mappings[excelCol] = fieldId;
            }
        });
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'workforce_confirm_import',
                file_id: jQuery('#import_file').data('file_id'),
                department_id: jQuery('#import_department_id').val(),
                mappings: mappings,
                overwrite: jQuery('#overwrite').is(':checked') ? 1 : 0,
                nonce: '<?php echo wp_create_nonce('workforce_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('اطلاعات با موفقیت وارد شد. تعداد رکوردهای وارد شده: ' + response.data.inserted);
                    location.reload();
                } else {
                    alert('خطا: ' + response.data.message);
                }
            }
        });
    }
    
    function cancelImport() {
        document.getElementById('importPreview').style.display = 'none';
        document.getElementById('importForm').style.display = 'block';
        document.getElementById('importForm').reset();
    }
    
    // تاریخ‌نگار فارسی
    jQuery(document).ready(function($) {
        $('.jdatepicker').persianDatepicker({
            format: 'YYYY/MM/DD',
            observer: true,
            persianDigit: false
        });
    });
    </script>
    <?php
}

/**
 * تنظیمات قالب اکسل
 */
function workforce_admin_excel_template() {
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
    $templates = workforce_get_all_excel_templates();
    $default_template = workforce_get_excel_template();
    ?>
    
    <div class="wrap workforce-admin-excel-template">
        <h1 class="wp-heading-inline">تنظیمات قالب گزارش اکسل</h1>
        <button type="button" class="page-title-action" onclick="showAddTemplateModal()">قالب جدید</button>
        <hr class="wp-header-end">
        
        <div class="workforce-template-editor">
            <div class="workforce-template-list">
                <h3>قالب‌های ذخیره شده</h3>
                <div class="template-items">
                    <?php foreach ($templates as $template): ?>
                        <div class="template-item <?php echo $template->is_default ? 'default-template' : ''; ?>" data-template-id="<?php echo esc_attr($template->id); ?>">
                            <h4><?php echo esc_html($template->name); ?></h4>
                            <?php if ($template->is_default): ?>
                                <span class="template-badge">پیش‌فرض</span>
                            <?php endif; ?>
                            <div class="template-actions">
                                <button type="button" class="button button-small" onclick="loadTemplate(<?php echo $template->id; ?>)">بارگذاری</button>
                                <button type="button" class="button button-small" onclick="editTemplate(<?php echo $template->id; ?>)">ویرایش</button>
                                <button type="button" class="button button-small button-link-delete" onclick="deleteTemplate(<?php echo $template->id; ?>)">حذف</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="workforce-template-preview">
                <h3>پیش‌نمایش قالب</h3>
                <div id="templatePreview" class="excel-preview">
                    <table>
                        <thead>
                            <tr>
                                <th>ستون ۱</th>
                                <th>ستون ۲</th>
                                <th>ستون ۳</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>داده نمونه ۱</td>
                                <td>داده نمونه ۲</td>
                                <td>داده نمونه ۳</td>
                            </tr>
                            <tr>
                                <td>داده نمونه ۴</td>
                                <td>داده نمونه ۵</td>
                                <td>داده نمونه ۶</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="workforce-template-settings">
            <h3>تنظیمات قالب</h3>
            <form id="templateForm" method="post">
                <?php wp_nonce_field('workforce_save_excel_template'); ?>
                <input type="hidden" name="template_id" id="template_id" value="">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="template_name">نام قالب</label></th>
                        <td>
                            <input type="text" name="template_name" id="template_name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="header_color">رنگ هدر</label></th>
                        <td>
                            <input type="color" name="header_color" id="header_color" value="#2c3e50">
                            <input type="text" name="header_color_text" id="header_color_text" value="#2c3e50" class="small-text" maxlength="7">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="text_color">رنگ متن</label></th>
                        <td>
                            <input type="color" name="text_color" id="text_color" value="#333333">
                            <input type="text" name="text_color_text" id="text_color_text" value="#333333" class="small-text" maxlength="7">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="even_row_color">رنگ ردیف زوج</label></th>
                        <td>
                            <input type="color" name="even_row_color" id="even_row_color" value="#f8f9fa">
                            <input type="text" name="even_row_color_text" id="even_row_color_text" value="#f8f9fa" class="small-text" maxlength="7">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="odd_row_color">رنگ ردیف فرد</label></th>
                        <td>
                            <input type="color" name="odd_row_color" id="odd_row_color" value="#ffffff">
                            <input type="text" name="odd_row_color_text" id="odd_row_color_text" value="#ffffff" class="small-text" maxlength="7">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="border_style">نوع خطوط</label></th>
                        <td>
                            <select name="border_style" id="border_style" class="regular-text">
                                <option value="thin">نازک</option>
                                <option value="medium">متوسط</option>
                                <option value="thick">ضخیم</option>
                                <option value="dotted">نقطه‌چین</option>
                                <option value="dashed">چین</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="border_color">رنگ خطوط</label></th>
                        <td>
                            <input type="color" name="border_color" id="border_color" value="#dddddd">
                            <input type="text" name="border_color_text" id="border_color_text" value="#dddddd" class="small-text" maxlength="7">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="header_font_size">سایز فونت هدر</label></th>
                        <td>
                            <input type="number" name="header_font_size" id="header_font_size" value="12" min="8" max="24" class="small-text">
                            <span>پیکسل</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="data_font_size">سایز فونت داده‌ها</label></th>
                        <td>
                            <input type="number" name="data_font_size" id="data_font_size" value="11" min="8" max="24" class="small-text">
                            <span>پیکسل</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">تنظیمات پیشرفته</th>
                        <td>
                            <label>
                                <input type="checkbox" name="is_default" id="is_default" value="1">
                                تنظیم به عنوان قالب پیش‌فرض
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="button" class="button button-primary" onclick="saveTemplate()">ذخیره قالب</button>
                    <button type="button" class="button" onclick="previewTemplate()">پیش‌نمایش</button>
                    <button type="button" class="button" onclick="resetTemplate()">بازنشانی</button>
                </p>
            </form>
        </div>
    </div>
    
    <!-- مودال افزودن قالب -->
    <div id="templateModal" class="workforce-modal" style="display: none;">
        <div class="workforce-modal-content">
            <div class="workforce-modal-header">
                <h2>قالب جدید</h2>
                <span class="workforce-modal-close" onclick="hideTemplateModal()">&times;</span>
            </div>
            <div class="workforce-modal-body">
                <form id="newTemplateForm">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="new_template_name">نام قالب</label></th>
                            <td>
                                <input type="text" name="new_template_name" id="new_template_name" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">گزینه‌ها</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="new_is_default" id="new_is_default" value="1">
                                    تنظیم به عنوان قالب پیش‌فرض
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" class="button button-primary" onclick="createNewTemplate()">ایجاد</button>
                        <button type="button" class="button" onclick="hideTemplateModal()">انصراف</button>
                    </p>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    function showAddTemplateModal() {
        document.getElementById('new_template_name').value = '';
        document.getElementById('new_is_default').checked = false;
        document.getElementById('templateModal').style.display = 'block';
    }
    
    function hideTemplateModal() {
        document.getElementById('templateModal').style.display = 'none';
    }
    
    function createNewTemplate() {
        var templateName = document.getElementById('new_template_name').value;
        var isDefault = document.getElementById('new_is_default').checked ? 1 : 0;
        
        if (!templateName.trim()) {
            alert('لطفا نام قالب را وارد کنید.');
            return;
        }
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'workforce_create_template',
                name: templateName,
                is_default: isDefault,
                nonce: '<?php echo wp_create_nonce('workforce_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('خطا: ' + response.data.message);
                }
            }
        });
    }
    
    function loadTemplate(templateId) {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'workforce_load_template',
                template_id: templateId,
                nonce: '<?php echo wp_create_nonce('workforce_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var template = response.data;
                    document.getElementById('template_id').value = template.id;
                    document.getElementById('template_name').value = template.name;
                    document.getElementById('header_color').value = template.header_color;
                    document.getElementById('header_color_text').value = template.header_color;
                    document.getElementById('text_color').value = template.text_color;
                    document.getElementById('text_color_text').value = template.text_color;
                    document.getElementById('even_row_color').value = template.even_row_color;
                    document.getElementById('even_row_color_text').value = template.even_row_color;
                    document.getElementById('odd_row_color').value = template.odd_row_color;
                    document.getElementById('odd_row_color_text').value = template.odd_row_color;
                    document.getElementById('border_style').value = template.border_style;
                    document.getElementById('border_color').value = template.border_color;
                    document.getElementById('border_color_text').value = template.border_color;
                    document.getElementById('header_font_size').value = template.header_font_size;
                    document.getElementById('data_font_size').value = template.data_font_size;
                    document.getElementById('is_default').checked = template.is_default == 1;
                    
                    previewTemplate();
                }
            }
        });
    }
    
    function editTemplate(templateId) {
        loadTemplate(templateId);
    }
    
    function deleteTemplate(templateId) {
        if (confirm('آیا از حذف این قالب اطمینان دارید؟')) {
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'workforce_delete_template',
                    template_id: templateId,
                    nonce: '<?php echo wp_create_nonce('workforce_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('خطا: ' + response.data.message);
                    }
                }
            });
        }
    }
    
    function saveTemplate() {
        var formData = new FormData(document.getElementById('templateForm'));
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'workforce_save_template',
                template_id: document.getElementById('template_id').value,
                name: document.getElementById('template_name').value,
                header_color: document.getElementById('header_color').value,
                text_color: document.getElementById('text_color').value,
                even_row_color: document.getElementById('even_row_color').value,
                odd_row_color: document.getElementById('odd_row_color').value,
                border_style: document.getElementById('border_style').value,
                border_color: document.getElementById('border_color').value,
                header_font_size: document.getElementById('header_font_size').value,
                data_font_size: document.getElementById('data_font_size').value,
                is_default: document.getElementById('is_default').checked ? 1 : 0,
                nonce: '<?php echo wp_create_nonce('workforce_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('قالب با موفقیت ذخیره شد.');
                    location.reload();
                } else {
                    alert('خطا: ' + response.data.message);
                }
            }
        });
    }
    
    function previewTemplate() {
        var preview = document.getElementById('templatePreview');
        var table = preview.querySelector('table');
        
        // اعمال استایل‌ها
        table.style.borderCollapse = 'collapse';
        table.style.width = '100%';
        
        // اعمال استایل به هدر
        var headerCells = table.querySelectorAll('thead th');
        for (var i = 0; i < headerCells.length; i++) {
            headerCells[i].style.backgroundColor = document.getElementById('header_color').value;
            headerCells[i].style.color = '#ffffff';
            headerCells[i].style.fontSize = document.getElementById('header_font_size').value + 'px';
            headerCells[i].style.padding = '8px';
            headerCells[i].style.border = '1px solid ' + document.getElementById('border_color').value;
            headerCells[i].style.textAlign = 'center';
        }
        
        // اعمال استایل به سلول‌ها
        var rows = table.querySelectorAll('tbody tr');
        for (var i = 0; i < rows.length; i++) {
            var cells = rows[i].querySelectorAll('td');
            var rowColor = (i % 2 === 0) ? document.getElementById('even_row_color').value : document.getElementById('odd_row_color').value;
            
            for (var j = 0; j < cells.length; j++) {
                cells[j].style.backgroundColor = rowColor;
                cells[j].style.color = document.getElementById('text_color').value;
                cells[j].style.fontSize = document.getElementById('data_font_size').value + 'px';
                cells[j].style.padding = '6px';
                cells[j].style.border = '1px solid ' + document.getElementById('border_color').value;
                
                // اعمال نوع خطوط
                var borderStyle = document.getElementById('border_style').value;
                if (borderStyle === 'dotted') {
                    cells[j].style.borderStyle = 'dotted';
                } else if (borderStyle === 'dashed') {
                    cells[j].style.borderStyle = 'dashed';
                } else {
                    cells[j].style.borderWidth = borderStyle === 'thin' ? '1px' : borderStyle === 'medium' ? '2px' : '3px';
                }
            }
        }
    }
    
    function resetTemplate() {
        document.getElementById('templateForm').reset();
        loadTemplate(<?php echo $default_template ? $default_template->id : 'null'; ?>);
    }
    
    // هماهنگی رنگ‌ها
    jQuery(document).ready(function($) {
        $('#header_color, #text_color, #even_row_color, #odd_row_color, #border_color').on('input', function() {
            var textId = this.id + '_text';
            $('#' + textId).val(this.value);
        });
        
        $('#header_color_text, #text_color_text, #even_row_color_text, #odd_row_color_text, #border_color_text').on('input', function() {
            var colorId = this.id.replace('_text', '');
            if (this.value.match(/^#[0-9A-Fa-f]{6}$/)) {
                $('#' + colorId).val(this.value);
            }
        });
        
        // بارگذاری قالب پیش‌فرض
        <?php if ($default_template): ?>
            loadTemplate(<?php echo $default_template->id; ?>);
        <?php endif; ?>
    });
    </script>
    <?php
}

/**
 * تایید درخواست‌ها
 */
function workforce_admin_approvals() {
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
    $current_status = $_GET['status'] ?? 'pending';
    $page = $_GET['paged'] ?? 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'approvals';
    
    // پردازش اقدامات
    if (isset($_POST['process_approval'])) {
        $nonce = $_POST['_wpnonce'] ?? '';
        $approval_id = intval($_POST['approval_id']);
        $action = sanitize_text_field($_POST['action_type']);
        $notes = sanitize_textarea_field($_POST['admin_notes'] ?? '');
        
        if (wp_verify_nonce($nonce, 'process_approval_' . $approval_id)) {
            $approval_data = [
                'status' => $action,
                'admin_notes' => $notes,
                'reviewer_id' => get_current_user_id(),
                'reviewed_at' => current_time('mysql'),
            ];
            
            workforce_update_approval_request($approval_id, $approval_data);
            
            // اگر تایید شد، تغییرات را اعمال کن
            if ($action === 'approved') {
                workforce_process_approved_request($approval_id);
            }
            
            echo '<div class="updated"><p>درخواست با موفقیت پردازش شد.</p></div>';
        }
    }
    
    // گرفتن درخواست‌ها
    $query = "SELECT * FROM $table_name WHERE status = %s ORDER BY created_at DESC LIMIT %d OFFSET %d";
    $approvals = $wpdb->get_results($wpdb->prepare($query, $current_status, $limit, $offset));
    
    $count_query = "SELECT COUNT(*) FROM $table_name WHERE status = %s";
    $total_count = $wpdb->get_var($wpdb->prepare($count_query, $current_status));
    $total_pages = ceil($total_count / $limit);
    ?>
    
    <div class="wrap workforce-admin-approvals">
        <h1 class="wp-heading-inline">تایید درخواست‌ها</h1>
        <hr class="wp-header-end">
        
        <h2 class="nav-tab-wrapper">
            <a href="<?php echo admin_url('admin.php?page=workforce-approvals&status=pending'); ?>" class="nav-tab <?php echo $current_status === 'pending' ? 'nav-tab-active' : ''; ?>">
                در انتظار تایید <span class="count">(<?php echo workforce_get_approval_count('pending'); ?>)</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=workforce-approvals&status=approved'); ?>" class="nav-tab <?php echo $current_status === 'approved' ? 'nav-tab-active' : ''; ?>">
                تایید شده
            </a>
            <a href="<?php echo admin_url('admin.php?page=workforce-approvals&status=rejected'); ?>" class="nav-tab <?php echo $current_status === 'rejected' ? 'nav-tab-active' : ''; ?>">
                رد شده
            </a>
            <a href="<?php echo admin_url('admin.php?page=workforce-approvals&status=needs_correction'); ?>" class="nav-tab <?php echo $current_status === 'needs_correction' ? 'nav-tab-active' : ''; ?>">
                نیاز به اصلاح
            </a>
        </h2>
        
        <div class="workforce-approvals-list">
            <?php if (empty($approvals)): ?>
                <p>هیچ درخواستی یافت نشد.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ردیف</th>
                            <th>نوع درخواست</th>
                            <th>درخواست کننده</th>
                            <th>جزئیات</th>
                            <th>تاریخ درخواست</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approvals as $index => $approval): ?>
                            <?php
                            $requester = get_userdata($approval->requester_id);
                            $reviewer = $approval->reviewer_id ? get_userdata($approval->reviewer_id) : null;
                            
                            $request_types = [
                                'add_personnel' => 'افزودن پرسنل',
                                'edit_personnel' => 'ویرایش پرسنل',
                                'delete_personnel' => 'حذف پرسنل',
                                'edit_field' => 'ویرایش فیلد',
                            ];
                            
                            $status_labels = [
                                'pending' => '<span class="status-badge status-pending">در انتظار</span>',
                                'approved' => '<span class="status-badge status-approved">تایید شده</span>',
                                'rejected' => '<span class="status-badge status-rejected">رد شده</span>',
                                'needs_correction' => '<span class="status-badge status-correction">نیاز به اصلاح</span>',
                                'suspended' => '<span class="status-badge status-suspended">تعلیق</span>',
                            ];
                            ?>
                            
                            <tr>
                                <td><?php echo esc_html(($page - 1) * $limit + $index + 1); ?></td>
                                <td><?php echo esc_html($request_types[$approval->request_type] ?? $approval->request_type); ?></td>
                                <td><?php echo esc_html($requester ? $requester->display_name : 'نامشخص'); ?></td>
                                <td>
                                    <?php
                                    if ($approval->request_type === 'add_personnel') {
                                        $data = unserialize($approval->data_after);
                                        echo 'افزودن: ' . ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '');
                                    } elseif ($approval->request_type === 'edit_personnel') {
                                        echo 'ویرایش پرسنل ID: ' . $approval->target_id;
                                    } elseif ($approval->request_type === 'delete_personnel') {
                                        echo 'حذف پرسنل ID: ' . $approval->target_id;
                                    } else {
                                        echo 'درخواست ' . $approval->request_type;
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html(wp_date('Y/m/d H:i', strtotime($approval->created_at))); ?></td>
                                <td><?php echo $status_labels[$approval->status]; ?></td>
                                <td>
                                    <?php if ($approval->status === 'pending'): ?>
                                        <button type="button" class="button button-small" onclick="showProcessModal(<?php echo $approval->id; ?>)">بررسی</button>
                                    <?php endif; ?>
                                    <button type="button" class="button button-small" onclick="viewApprovalDetails(<?php echo $approval->id; ?>)">مشاهده</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($total_pages > 1): ?>
                    <div class="tablenav">
                        <div class="tablenav-pages">
                            <span class="displaying-num">نمایش <?php echo esc_html(($page - 1) * $limit + 1); ?>-<?php echo esc_html(min($page * $limit, $total_count)); ?> از <?php echo esc_html($total_count); ?></span>
                            
                            <?php
                            echo paginate_links([
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => '&laquo; قبلی',
                                'next_text' => 'بعدی &raquo;',
                                'total' => $total_pages,
                                'current' => $page,
                            ]);
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- مودال بررسی درخواست -->
    <div id="processModal" class="workforce-modal" style="display: none;">
        <div class="workforce-modal-content">
            <div class="workforce-modal-header">
                <h2>بررسی درخواست</h2>
                <span class="workforce-modal-close" onclick="hideProcessModal()">&times;</span>
            </div>
            <div class="workforce-modal-body" id="processModalBody">
                <!-- محتوای داینامیک -->
            </div>
        </div>
    </div>
    
    <!-- مودال مشاهده جزئیات -->
    <div id="detailsModal" class="workforce-modal" style="display: none;">
        <div class="workforce-modal-content">
            <div class="workforce-modal-header">
                <h2>مشاهده جزئیات</h2>
                <span class="workforce-modal-close" onclick="hideDetailsModal()">&times;</span>
            </div>
            <div class="workforce-modal-body" id="detailsModalBody">
                <!-- محتوای داینامیک -->
            </div>
        </div>
    </div>
    
    <script>
    function showProcessModal(approvalId) {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'workforce_get_approval_details',
                approval_id: approvalId,
                nonce: '<?php echo wp_create_nonce('workforce_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    document.getElementById('processModalBody').innerHTML = response.data.html;
                    document.getElementById('processModal').style.display = 'block';
                }
            }
        });
    }
    
    function hideProcessModal() {
        document.getElementById('processModal').style.display = 'none';
    }
    
    function viewApprovalDetails(approvalId) {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'workforce_view_approval_details',
                approval_id: approvalId,
                nonce: '<?php echo wp_create_nonce('workforce_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    document.getElementById('detailsModalBody').innerHTML = response.data.html;
                    document.getElementById('detailsModal').style.display = 'block';
                }
            }
        });
    }
    
    function hideDetailsModal() {
        document.getElementById('detailsModal').style.display = 'none';
    }
    
    function processApproval(action) {
        var form = document.getElementById('processApprovalForm');
        var actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action_type';
        actionInput.value = action;
        form.appendChild(actionInput);
        
        form.submit();
    }
    </script>
    <?php
}

/**
 * مدیریت دوره‌ها
 */
function workforce_admin_periods() {
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
    // پردازش فرم
    if (isset($_POST['submit_period'])) {
        $nonce = $_POST['_wpnonce'] ?? '';
        
        if (wp_verify_nonce($nonce, 'workforce_save_period')) {
            $period_data = [
                'name' => sanitize_text_field($_POST['name']),
                'start_date' => sanitize_text_field($_POST['start_date']),
                'end_date' => sanitize_text_field($_POST['end_date']),
                'is_active' => isset($_POST['is_active']),
            ];
            
            if (isset($_POST['period_id']) && !empty($_POST['period_id'])) {
                workforce_update_period(intval($_POST['period_id']), $period_data);
                echo '<div class="updated"><p>دوره با موفقیت ویرایش شد.</p></div>';
            } else {
                workforce_add_period($period_data);
                echo '<div class="updated"><p>دوره جدید با موفقیت افزوده شد.</p></div>';
            }
        }
    }
    
    // حذف دوره
    if (isset($_GET['delete_period'])) {
        $nonce = $_GET['_wpnonce'] ?? '';
        
        if (wp_verify_nonce($nonce, 'delete_period_' . $_GET['delete_period'])) {
            $result = workforce_delete_period(intval($_GET['delete_period']));
            if ($result) {
                echo '<div class="updated"><p>دوره با موفقیت حذف شد.</p></div>';
            } else {
                echo '<div class="error"><p>این دوره دارای داده است و نمی‌توان آن را حذف کرد.</p></div>';
            }
        }
    }
    
    $periods = workforce_get_all_periods();
    $active_period = workforce_get_active_period();
    ?>
    
    <div class="wrap workforce-admin-periods">
        <h1 class="wp-heading-inline">مدیریت دوره‌های کارکرد</h1>
        <button type="button" class="page-title-action" onclick="showAddPeriodModal()">افزودن دوره جدید</button>
        <hr class="wp-header-end">
        
        <div class="workforce-periods-list">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>نام دوره</th>
                        <th>تاریخ شروع</th>
                        <th>تاریخ پایان</th>
                        <th>وضعیت</th>
                        <th>تاریخ ایجاد</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($periods)): ?>
                        <tr><td colspan="6">هیچ دوره‌ای ایجاد نشده است.</td></tr>
                    <?php else: ?>
                        <?php foreach ($periods as $period): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($period->name); ?></strong>
                                    <?php if ($period->is_active): ?>
                                        <span class="period-badge active">فعال</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($period->start_date); ?></td>
                                <td><?php echo esc_html($period->end_date); ?></td>
                                <td>
                                    <?php if ($period->is_active): ?>
                                        <span class="status-badge status-active">فعال</span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">غیرفعال</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(wp_date('Y/m/d', strtotime($period->created_at))); ?></td>
                                <td>
                                    <button type="button" class="button button-small" onclick="editPeriod(<?php echo $period->id; ?>)">ویرایش</button>
                                    <?php if (!$period->is_active): ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=workforce-periods&delete_period=' . $period->id), 'delete_period_' . $period->id, '_wpnonce'); ?>" class="button button-small button-link-delete" onclick="return confirm('آیا از حذف این دوره اطمینان دارید؟')">حذف</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- مودال افزودن/ویرایش دوره -->
    <div id="periodModal" class="workforce-modal" style="display: none;">
        <div class="workforce-modal-content">
            <div class="workforce-modal-header">
                <h2 id="periodModalTitle">افزودن دوره جدید</h2>
                <span class="workforce-modal-close" onclick="hidePeriodModal()">&times;</span>
            </div>
            <div class="workforce-modal-body">
                <form method="post" id="periodForm">
                    <?php wp_nonce_field('workforce_save_period'); ?>
                    <input type="hidden" name="period_id" id="period_id" value="">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="period_name">عنوان دوره</label></th>
                            <td>
                                <input type="text" name="name" id="period_name" class="regular-text" required placeholder="مثال: بهمن ۱۴۰۳">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="start_date">تاریخ شروع</label></th>
                            <td>
                                <input type="text" name="start_date" id="start_date" class="regular-text jdatepicker" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="end_date">تاریخ پایان</label></th>
                            <td>
                                <input type="text" name="end_date" id="end_date" class="regular-text jdatepicker" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">وضعیت دوره</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="is_active" id="is_active" value="1" <?php echo !$active_period ? 'checked' : ''; ?>>
                                    فعال (فقط یک دوره می‌تواند فعال باشد)
                                </label>
                                <?php if ($active_period): ?>
                                    <p class="description">دوره فعال فعلی: <?php echo esc_html($active_period->name); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" name="submit_period" class="button button-primary">ذخیره دوره</button>
                        <button type="button" class="button" onclick="hidePeriodModal()">انصراف</button>
                    </p>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    function showAddPeriodModal() {
        document.getElementById('periodModalTitle').textContent = 'افزودن دوره جدید';
        document.getElementById('periodForm').reset();
        document.getElementById('period_id').value = '';
        document.getElementById('is_active').checked = <?php echo $active_period ? 'false' : 'true'; ?>;
        document.getElementById('periodModal').style.display = 'block';
        
        jQuery('.jdatepicker').persianDatepicker({
            format: 'YYYY/MM/DD',
            observer: true,
            persianDigit: false
        });
    }
    
    function hidePeriodModal() {
        document.getElementById('periodModal').style.display = 'none';
    }
    
    function editPeriod(periodId) {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'workforce_get_period_data',
                period_id: periodId,
                nonce: '<?php echo wp_create_nonce('workforce_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var period = response.data;
                    document.getElementById('periodModalTitle').textContent = 'ویرایش دوره';
                    document.getElementById('period_id').value = period.id;
                    document.getElementById('period_name').value = period.name;
                    document.getElementById('start_date').value = period.start_date;
                    document.getElementById('end_date').value = period.end_date;
                    document.getElementById('is_active').checked = period.is_active == 1;
                    document.getElementById('periodModal').style.display = 'block';
                    
                    jQuery('.jdatepicker').persianDatepicker({
                        format: 'YYYY/MM/DD',
                        observer: true,
                        persianDigit: false
                    });
                }
            }
        });
    }
    
    jQuery(document).ready(function($) {
        $('.jdatepicker').persianDatepicker({
            format: 'YYYY/MM/DD',
            observer: true,
            persianDigit: false
        });
    });
    </script>
    <?php
}

/**
 * تنظیمات
 */
function workforce_admin_settings() {
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
    $settings = get_option('workforce_settings', []);
    
    if (isset($_POST['submit_settings'])) {
        $nonce = $_POST['_wpnonce'] ?? '';
        
        if (wp_verify_nonce($nonce, 'workforce_save_settings')) {
            $new_settings = [
                'company_name' => sanitize_text_field($_POST['company_name']),
                'primary_color' => sanitize_hex_color($_POST['primary_color']),
                'secondary_color' => sanitize_hex_color($_POST['secondary_color']),
                'login_page_id' => intval($_POST['login_page_id']),
                'manager_page_id' => intval($_POST['manager_page_id']),
                'org_manager_page_id' => intval($_POST['org_manager_page_id']),
                'items_per_page' => intval($_POST['items_per_page']),
                'auto_backup' => isset($_POST['auto_backup']),
                'backup_days' => intval($_POST['backup_days']),
                'enable_logging' => isset($_POST['enable_logging']),
                'log_days' => intval($_POST['log_days']),
            ];
            
            update_option('workforce_settings', $new_settings);
            echo '<div class="updated"><p>تنظیمات با موفقیت ذخیره شد.</p></div>';
            
            // بهینه‌سازی جداول
            if (isset($_POST['optimize_tables'])) {
                workforce_optimize_tables();
                echo '<div class="updated"><p>جداول پایگاه داده بهینه‌سازی شدند.</p></div>';
            }
            
            // پاکسازی لاگ‌ها
            if (isset($_POST['cleanup_logs'])) {
                workforce_cleanup_old_data(intval($_POST['log_days']));
                echo '<div class="updated"><p>لاگ‌های قدیمی پاکسازی شدند.</p></div>';
            }
        }
    }
    
    // گرفتن لیست صفحات
    $pages = get_pages();
    ?>
    
    <div class="wrap workforce-admin-settings">
        <h1 class="wp-heading-inline">تنظیمات پلاگین</h1>
        <hr class="wp-header-end">
        
<form method="post">

    <?php wp_nonce_field('wf_save_settings', 'wf_settings_nonce'); ?>
    <input type="hidden" name="wf_action" value="save_settings">
            
            <h2>تنظیمات عمومی</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="company_name">نام سازمان</label></th>
                    <td>
                        <input type="text" name="company_name" id="company_name" class="regular-text" value="<?php echo esc_attr($settings['company_name'] ?? 'سازمان شما'); ?>" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="primary_color">رنگ اصلی</label></th>
                    <td>
                        <input type="color" name="primary_color" id="primary_color" value="<?php echo esc_attr($settings['primary_color'] ?? '#2c3e50'); ?>">
                        <input type="text" name="primary_color_text" id="primary_color_text" value="<?php echo esc_attr($settings['primary_color'] ?? '#2c3e50'); ?>" class="small-text" maxlength="7">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="secondary_color">رنگ ثانویه</label></th>
                    <td>
                        <input type="color" name="secondary_color" id="secondary_color" value="<?php echo esc_attr($settings['secondary_color'] ?? '#3498db'); ?>">
                        <input type="text" name="secondary_color_text" id="secondary_color_text" value="<?php echo esc_attr($settings['secondary_color'] ?? '#3498db'); ?>" class="small-text" maxlength="7">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="items_per_page">تعداد رکورد در صفحه</label></th>
                    <td>
                        <select name="items_per_page" id="items_per_page" class="regular-text">
                            <option value="10" <?php selected($settings['items_per_page'] ?? 25, 10); ?>>۱۰</option>
                            <option value="25" <?php selected($settings['items_per_page'] ?? 25, 25); ?>>۲۵</option>
                            <option value="50" <?php selected($settings['items_per_page'] ?? 25, 50); ?>>۵۰</option>
                            <option value="100" <?php selected($settings['items_per_page'] ?? 25, 100); ?>>۱۰۰</option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <h2>تنظیمات صفحات</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="login_page_id">صفحه لاگین</label></th>
                    <td>
                        <select name="login_page_id" id="login_page_id" class="regular-text">
                            <option value="">انتخاب نشده</option>
                            <?php foreach ($pages as $page): ?>
                                <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($settings['login_page_id'] ?? '', $page->ID); ?>>
                                    <?php echo esc_html($page->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">صفحه‌ای که شرط‌کد [workforce_manager_panel] در آن قرار دارد</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="manager_page_id">صفحه مدیران ادارات</label></th>
                    <td>
                        <select name="manager_page_id" id="manager_page_id" class="regular-text">
                            <option value="">انتخاب نشده</option>
                            <?php foreach ($pages as $page): ?>
                                <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($settings['manager_page_id'] ?? '', $page->ID); ?>>
                                    <?php echo esc_html($page->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="org_manager_page_id">صفحه مدیر سازمان</label></th>
                    <td>
                        <select name="org_manager_page_id" id="org_manager_page_id" class="regular-text">
                            <option value="">انتخاب نشده</option>
                            <?php foreach ($pages as $page): ?>
                                <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($settings['org_manager_page_id'] ?? '', $page->ID); ?>>
                                    <?php echo esc_html($page->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <h2>تنظیمات پشتیبان‌گیری</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">پشتیبان‌گیری خودکار</th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_backup" id="auto_backup" value="1" <?php checked($settings['auto_backup'] ?? false); ?>>
                            فعال‌سازی پشتیبان‌گیری خودکار
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="backup_days">دفعات پشتیبان‌گیری</label></th>
                    <td>
                        <select name="backup_days" id="backup_days" class="regular-text">
                            <option value="1" <?php selected($settings['backup_days'] ?? 7, 1); ?>>روزانه</option>
                            <option value="7" <?php selected($settings['backup_days'] ?? 7, 7); ?>>هفتگی</option>
                            <option value="30" <?php selected($settings['backup_days'] ?? 7, 30); ?>>ماهانه</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">بهینه‌سازی</th>
                    <td>
                        <label>
                            <input type="checkbox" name="optimize_tables" id="optimize_tables" value="1">
                            بهینه‌سازی جداول پایگاه داده
                        </label>
                        <p class="description">با هر بار ذخیره تنظیمات انجام می‌شود</p>
                    </td>
                </tr>
            </table>
            
            <h2>تنظیمات لاگ‌گیری</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">ثبت لاگ فعالیت‌ها</th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_logging" id="enable_logging" value="1" <?php checked($settings['enable_logging'] ?? true); ?>>
                            فعال‌سازی ثبت لاگ
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="log_days">نگهداری لاگ‌ها</label></th>
                    <td>
                        <input type="number" name="log_days" id="log_days" value="<?php echo esc_attr($settings['log_days'] ?? 90); ?>" min="1" max="365" class="small-text">
                        <span>روز</span>
                        <p class="description">لاگ‌های قدیمی‌تر از این تعداد روز پاک می‌شوند</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">پاکسازی لاگ‌ها</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cleanup_logs" id="cleanup_logs" value="1">
                            پاکسازی لاگ‌های قدیمی
                        </label>
                        <p class="description">با هر بار ذخیره تنظیمات انجام می‌شود</p>
                    </td>
                </tr>
            </table>
            
            <h2>اطلاعات پلاگین</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">ورژن پلاگین</th>
                    <td><?php echo esc_html(WF_PLUGIN_VERSION); ?></td>
                </tr>
                <tr>
                    <th scope="row">تعداد جداول</th>
                    <td>۸ جدول</td>
                </tr>
                <tr>
                    <th scope="row">آمار کلی</th>
                    <td>
                        <?php
                        $stats = workforce_get_overall_stats();
                        echo 'ادارات: ' . esc_html($stats['departments']) . ' | ';
                        echo 'پرسنل: ' . esc_html($stats['personnel']) . ' | ';
                        echo 'فیلدها: ' . esc_html($stats['fields']);
                        ?>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
<button type="submit" name="wf_save_settings_btn" class="button button-primary">
    ذخیره تنظیمات
</button>
            </p>
        </form>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // هماهنگی رنگ‌ها
        $('#primary_color, #secondary_color').on('input', function() {
            var textId = this.id + '_text';
            $('#' + textId).val(this.value);
        });
        
        $('#primary_color_text, #secondary_color_text').on('input', function() {
            var colorId = this.id.replace('_text', '');
            if (this.value.match(/^#[0-9A-Fa-f]{6}$/)) {
                $('#' + colorId).val(this.value);
            }
        });
    });
    </script>
    <?php
}

/**
 * توابع کمکی ادمین
 */
function workforce_get_admin_alerts() {
    global $wpdb;
    $alerts = [];
    
// ادارات بدون مدیر
$departments_table = $wpdb->prefix . WF_TABLE_PREFIX . 'departments';
$managers_table = $wpdb->prefix . WF_TABLE_PREFIX . 'department_managers';

$departments_without_manager = $wpdb->get_var(
    "SELECT COUNT(DISTINCT d.id) 
     FROM $departments_table d 
     LEFT JOIN $managers_table dm ON d.id = dm.department_id 
     WHERE d.is_active = 1 AND dm.id IS NULL"
);
    
    if ($departments_without_manager > 0) {
        $alerts[] = [
            'type' => 'warning',
            'icon' => '⚠️',
            'text' => "$departments_without_manager اداره بدون مدیر وجود دارد.",
            'action' => [
                'text' => 'مشاهده',
                'url' => admin_url('admin.php?page=workforce-departments'),
            ],
        ];
    }
    
    // پرسنل با اطلاعات ناقص
    $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
    $fields_table = $wpdb->prefix . WF_TABLE_PREFIX . 'fields';
    
    $required_fields = $wpdb->get_results(
        "SELECT * FROM $fields_table WHERE is_required = 1"
    );
    
    if (!empty($required_fields)) {
        $incomplete_count = 0;
        foreach ($required_fields as $field) {
            // این بخش نیاز به پیاده‌سازی دقیق‌تر دارد
        }
        
        if ($incomplete_count > 0) {
            $alerts[] = [
                'type' => 'error',
                'icon' => '❌',
                'text' => "$incomplete_count پرسنل با اطلاعات ناقص وجود دارد.",
            ];
        }
    }
    
    // کدملی‌های تکراری
    $duplicate_national_codes = $wpdb->get_var(
        "SELECT COUNT(*) FROM (
            SELECT national_code, COUNT(*) as cnt 
            FROM $personnel_table 
            WHERE national_code IS NOT NULL AND national_code != '' AND is_deleted = 0
            GROUP BY national_code 
            HAVING cnt > 1
        ) as duplicates"
    );
    
    if ($duplicate_national_codes > 0) {
        $alerts[] = [
            'type' => 'error',
            'icon' => '🔍',
            'text' => "$duplicate_national_codes کدملی تکراری وجود دارد.",
        ];
    }
    
    return $alerts;
}

function workforce_get_recent_activities($limit = 10) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'activity_logs';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d",
        $limit
    ));
}

function workforce_render_field_input($field, $name, $value = '') {
    $required = $field->is_required ? ' required' : '';
    $disabled = $field->is_locked ? ' disabled' : '';
    
    switch ($field->field_type) {
        case 'text':
            echo '<input type="text" name="' . esc_attr($name) . '" id="' . esc_attr($name) . '" class="regular-text" value="' . esc_attr($value) . '"' . $required . $disabled . '>';
            break;
            
        case 'number':
            echo '<input type="number" name="' . esc_attr($name) . '" id="' . esc_attr($name) . '" class="regular-text" value="' . esc_attr($value) . '"' . $required . $disabled . '>';
            break;
            
        case 'decimal':
            echo '<input type="number" step="0.01" name="' . esc_attr($name) . '" id="' . esc_attr($name) . '" class="regular-text" value="' . esc_attr($value) . '"' . $required . $disabled . '>';
            break;
            
        case 'date':
            echo '<input type="text" name="' . esc_attr($name) . '" id="' . esc_attr($name) . '" class="regular-text jdatepicker" value="' . esc_attr($value) . '"' . $required . $disabled . '>';
            break;
            
        case 'time':
            echo '<input type="time" name="' . esc_attr($name) . '" id="' . esc_attr($name) . '" class="regular-text" value="' . esc_attr($value) . '"' . $required . $disabled . '>';
            break;
            
        case 'select':
            echo '<select name="' . esc_attr($name) . '" id="' . esc_attr($name) . '" class="regular-text"' . $required . $disabled . '>';
            echo '<option value="">انتخاب کنید</option>';
            
            if ($field->options && is_array($field->options)) {
                foreach ($field->options as $option) {
                    $selected = $option == $value ? ' selected' : '';
                    echo '<option value="' . esc_attr($option) . '"' . $selected . '>' . esc_html($option) . '</option>';
                }
            }
            
            echo '</select>';
            break;
            
        case 'checkbox':
            $checked = $value ? ' checked' : '';
            echo '<input type="checkbox" name="' . esc_attr($name) . '" id="' . esc_attr($name) . '" value="1"' . $checked . $disabled . '>';
            break;
            
        default:
            echo '<input type="text" name="' . esc_attr($name) . '" id="' . esc_attr($name) . '" class="regular-text" value="' . esc_attr($value) . '"' . $required . $disabled . '>';
    }
}

/**
 * پردازش درخواست تایید شده
 */
function workforce_process_approved_request($approval_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'approvals';
    
    $approval = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $approval_id
    ));
    
    if (!$approval) {
        return false;
    }
    
    switch ($approval->request_type) {
        case 'add_personnel':
            $data = unserialize($approval->data_after);
            if ($data) {
                workforce_add_personnel($data);
            }
            break;
            
        case 'edit_personnel':
            $data_before = unserialize($approval->data_before);
            $data_after = unserialize($approval->data_after);
            
            if ($data_after && $approval->target_id) {
                workforce_update_personnel($approval->target_id, $data_after);
            }
            break;
            
        case 'delete_personnel':
            if ($approval->target_id) {
                workforce_delete_personnel($approval->target_id);
            }
            break;
    }
    
    return true;
}

/**
 * هندلرهای AJAX برای ادمین
 */
function workforce_ajax_get_field_data() {
    check_ajax_referer('workforce_nonce', 'nonce');
    
    $field_id = intval($_POST['field_id']);
    $field = workforce_get_field($field_id);
    
    if ($field) {
        wp_send_json_success($field);
    } else {
        wp_send_json_error(['message' => 'فیلد یافت نشد.']);
    }
}
add_action('wp_ajax_workforce_get_field_data', 'workforce_ajax_get_field_data');
function workforce_ajax_get_department_managers() {
    check_ajax_referer('workforce_nonce', 'nonce');
    
    $department_id = intval($_POST['department_id']);
    $department = workforce_get_department($department_id);
    
    // گرفتن مدیران از جدول department_managers
    global $wpdb;
    $managers_table = $wpdb->prefix . WF_TABLE_PREFIX . 'department_managers';
    $managers = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $managers_table WHERE department_id = %d ORDER BY is_primary DESC, created_at ASC",
        $department_id
    ));
    
    if ($department) {
        wp_send_json_success([
            'department' => $department,
            'managers' => $managers
        ]);
    } else {
        wp_send_json_error(['message' => 'اداره یافت نشد.']);
    }
}
add_action('wp_ajax_workforce_get_department_managers', 'workforce_ajax_get_department_managers');
function workforce_ajax_get_department_data() {
    check_ajax_referer('workforce_nonce', 'nonce');
    
    $department_id = intval($_POST['department_id']);
    $department = workforce_get_department($department_id);
    
    if ($department) {
        wp_send_json_success($department);
    } else {
        wp_send_json_error(['message' => 'اداره یافت نشد.']);
    }
}
add_action('wp_ajax_workforce_get_department_data', 'workforce_ajax_get_department_data');

function workforce_ajax_get_personnel_data() {
    check_ajax_referer('workforce_nonce', 'nonce');
    
    $personnel_id = intval($_POST['personnel_id']);
    $mode = $_POST['mode'] ?? 'view';
    $personnel = workforce_get_personnel($personnel_id);
    
    if (!$personnel) {
        wp_send_json_error(['message' => 'پرسنل یافت نشد.']);
    }
    
    $department = workforce_get_department($personnel->department_id);
    $fields = workforce_get_all_fields();
    $meta = workforce_get_personnel_meta($personnel_id);
    
    ob_start();
    ?>
    <form id="personnelForm" method="post">
        <input type="hidden" name="personnel_id" value="<?php echo esc_attr($personnel->id); ?>">
        
        <div class="workforce-form-section">
            <h3>اطلاعات پایه</h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="edit_department_id">اداره</label></th>
                    <td>
                        <select name="department_id" id="edit_department_id" class="regular-text" <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                            <?php
                            $departments = workforce_get_all_departments();
                            foreach ($departments as $dept) {
                                $selected = $dept->id == $personnel->department_id ? ' selected' : '';
                                echo '<option value="' . esc_attr($dept->id) . '"' . $selected . '>' . esc_html($dept->name) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="edit_national_code">کدملی</label></th>
                    <td>
                        <input type="text" name="national_code" id="edit_national_code" class="regular-text" value="<?php echo esc_attr($personnel->national_code); ?>" <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="edit_first_name">نام</label></th>
                    <td>
                        <input type="text" name="first_name" id="edit_first_name" class="regular-text" value="<?php echo esc_attr($personnel->first_name); ?>" <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="edit_last_name">نام خانوادگی</label></th>
                    <td>
                        <input type="text" name="last_name" id="edit_last_name" class="regular-text" value="<?php echo esc_attr($personnel->last_name); ?>" <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="edit_employment_date">تاریخ استخدام</label></th>
                    <td>
                        <input type="text" name="employment_date" id="edit_employment_date" class="regular-text jdatepicker" value="<?php echo esc_attr($personnel->employment_date); ?>" <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="edit_employment_type">نوع استخدام</label></th>
                    <td>
                        <select name="employment_type" id="edit_employment_type" class="regular-text" <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                            <option value="permanent" <?php selected($personnel->employment_type, 'permanent'); ?>>دائمی</option>
                            <option value="contract" <?php selected($personnel->employment_type, 'contract'); ?>>پیمانی</option>
                            <option value="temporary" <?php selected($personnel->employment_type, 'temporary'); ?>>موقت</option>
                            <option value="project" <?php selected($personnel->employment_type, 'project'); ?>>پروژه‌ای</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="edit_status">وضعیت</label></th>
                    <td>
                        <select name="status" id="edit_status" class="regular-text" <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                            <option value="active" <?php selected($personnel->status, 'active'); ?>>فعال</option>
                            <option value="inactive" <?php selected($personnel->status, 'inactive'); ?>>غیرفعال</option>
                            <option value="suspended" <?php selected($personnel->status, 'suspended'); ?>>تعلیق</option>
                            <option value="retired" <?php selected($personnel->status, 'retired'); ?>>بازنشسته</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="workforce-form-section">
            <h3>اطلاعات تکمیلی</h3>
            <table class="form-table">
                <?php foreach ($fields as $field): ?>
                    <?php if (!in_array($field->field_name, ['national_code', 'first_name', 'last_name', 'employment_date'])): ?>
                        <tr>
                            <th scope="row">
                                <label for="edit_field_<?php echo esc_attr($field->id); ?>">
                                    <?php echo esc_html($field->field_label); ?>
                                    <?php if ($field->is_required): ?><span class="required">*</span><?php endif; ?>
                                    <?php if ($field->is_locked): ?><span title="قفل شده">🔒</span><?php endif; ?>
                                </label>
                            </th>
                            <td>
                                <?php
                                $value = $meta[$field->id] ?? $meta[$field->field_name] ?? '';
                                workforce_render_field_input($field, 'field_' . $field->id, $value);
                                ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </table>
        </div>
        
        <?php if ($mode === 'edit'): ?>
            <p class="submit">
                <?php wp_nonce_field('workforce_update_personnel', '_wpnonce'); ?>
                <button type="button" class="button button-primary" onclick="savePersonnelChanges()">ذخیره تغییرات</button>
                <button type="button" class="button" onclick="hidePersonnelModal()">انصراف</button>
            </p>
        <?php endif; ?>
    </form>
    <?php
    
    $html = ob_get_clean();
    
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_workforce_get_personnel_data', 'workforce_ajax_get_personnel_data');

function workforce_ajax_delete_personnel() {
    check_ajax_referer('workforce_nonce', 'nonce');
    
    $personnel_id = intval($_POST['personnel_id']);
    $result = workforce_delete_personnel($personnel_id, true);
    
    if ($result) {
        wp_send_json_success(['message' => 'پرسنل با موفقیت حذف شد.']);
    } else {
        wp_send_json_error(['message' => 'خطا در حذف پرسنل.']);
    }
}
// اضافه کردن AJAX handlers جدید در انتهای فایل (قبل از بسته شدن PHP)
add_action('wp_ajax_workforce_view_personnel', 'workforce_ajax_view_personnel');
add_action('wp_ajax_workforce_delete_personnel_admin', 'workforce_ajax_delete_personnel_admin');

function workforce_ajax_view_personnel() {
    check_ajax_referer('workforce_view', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'شما دسترسی لازم را ندارید.']);
    }
    
    $personnel_id = intval($_POST['personnel_id']);
    $personnel = workforce_get_personnel($personnel_id);
    
    if (!$personnel) {
        wp_send_json_error(['message' => 'پرسنل یافت نشد.']);
    }
    
    $info = "👤 نام: {$personnel->first_name} {$personnel->last_name}\n";
    $info .= "🔢 کدملی: {$personnel->national_code}\n";
    $info .= "🏢 وضعیت: {$personnel->status}\n";
    $info .= "📅 تاریخ استخدام: {$personnel->employment_date}\n";
    $info .= "📋 نوع استخدام: {$personnel->employment_type}";
    
    wp_send_json_success(['data' => $info]);
}

function workforce_ajax_delete_personnel_admin() {
    check_ajax_referer('workforce_delete', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'شما دسترسی لازم را ندارید.']);
    }
    
    $personnel_id = intval($_POST['personnel_id']);
    $result = workforce_delete_personnel($personnel_id, true);
    
    if ($result) {
        wp_send_json_success(['message' => 'پرسنل با موفقیت حذف شد.']);
    } else {
        wp_send_json_error(['message' => 'خطا در حذف پرسنل.']);
    }
}
add_action('wp_ajax_workforce_delete_personnel', 'workforce_ajax_delete_personnel');
