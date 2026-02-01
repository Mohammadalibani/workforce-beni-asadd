<?php
/**
 * پنل مدیران و مدیران سازمان
 */

// بررسی دسترسی
if (!is_user_logged_in()) {
    echo '<div class="workforce-login-required">لطفاً ابتدا وارد شوید.</div>';
    return;
}

$user = wp_get_current_user();
$user_roles = $user->roles;
$user_id = $user->ID;

// تعیین نوع کاربر
$is_admin = in_array('administrator', $user_roles);
$is_org_manager = in_array('workforce_org_manager', $user_roles);
$is_dept_manager = in_array('workforce_dept_manager', $user_roles);

if (!$is_admin && !$is_org_manager && !$is_dept_manager) {
    echo '<div class="workforce-access-denied">شما دسترسی به این پنل را ندارید.</div>';
    return;
}

global $wpdb, $db;
$current_user = $user;

// دریافت ادارات تحت مدیریت کاربر
$managed_departments = array();
if ($is_admin || $is_org_manager) {
    // مدیر کل - همه ادارات
    $managed_departments = $db->get_departments(array('is_active' => 1));
} elseif ($is_dept_manager) {
    // مدیر اداره - فقط ادارات مربوطه
    $managed_departments = $db->get_departments(array(
        'manager_id' => $user_id,
        'is_active' => 1
    ));
}

// اگر مدیر اداره است و هیچ اداره‌ای ندارد
if ($is_dept_manager && empty($managed_departments)) {
    echo '<div class="workforce-no-department">شما به هیچ اداره‌ای دسترسی ندارید. لطفاً با مدیر سیستم تماس بگیرید.</div>';
    return;
}

// دریافت دوره‌های فعال
$active_periods = $wpdb->get_results("
    SELECT * FROM {$wpdb->prefix}workforce_periods 
    WHERE is_active = 1 
    ORDER BY period_year DESC, period_month DESC
", ARRAY_A);

// اگر دوره فعالی وجود ندارد
if (empty($active_periods)) {
    echo '<div class="workforce-no-period">هیچ دوره فعالی وجود ندارد. لطفاً با مدیر سیستم تماس بگیرید.</div>';
    return;
}

// دوره پیش‌فرض
$default_period = $active_periods[0];
$current_period_id = $_GET['period'] ?? $default_period['id'];
$current_period = null;

foreach ($active_periods as $period) {
    if ($period['id'] == $current_period_id) {
        $current_period = $period;
        break;
    }
}

if (!$current_period) {
    $current_period = $default_period;
}

// دریافت فیلدهای اصلی
$main_fields = $db->get_fields(array('is_main' => true));
$all_fields = $db->get_fields();

// AJAX endpoint
if (wp_doing_ajax()) {
    add_action('wp_ajax_workforce_manager_ajax', 'handle_manager_ajax');
    add_action('wp_ajax_nopriv_workforce_manager_ajax', 'handle_manager_ajax_nopriv');
}

// تنظیم متغیرهای جهانی برای جاوااسکریپت
add_action('wp_footer', function() use ($user_id, $managed_departments, $current_period, $all_fields) {
    ?>
    <script>
    window.workforceManagerData = {
        userId: <?php echo $user_id; ?>,
        userRole: '<?php echo $is_admin ? 'admin' : ($is_org_manager ? 'org_manager' : 'dept_manager'); ?>',
        managedDepartments: <?php echo json_encode(array_column($managed_departments, 'id')); ?>,
        currentPeriod: <?php echo json_encode($current_period); ?>,
        mainFields: <?php echo json_encode($all_fields); ?>,
        ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo wp_create_nonce('workforce_manager_nonce'); ?>',
        strings: {
            save: 'ذخیره',
            cancel: 'انصراف',
            delete: 'حذف',
            edit: 'ویرایش',
            add: 'افزودن',
            confirmDelete: 'آیا مطمئن هستید؟',
            loading: 'در حال بارگذاری...',
            saved: 'ذخیره شد!',
            error: 'خطا رخ داد!',
            next: 'بعدی',
            prev: 'قبلی',
            filter: 'فیلتر',
            clearFilter: 'پاک کردن فیلتر',
            selectAll: 'انتخاب همه',
            exportExcel: 'خروجی اکسل'
        }
    };
    </script>
    <?php
});

?>

<div class="workforce-manager-dashboard" id="workforceDashboard">
    <!-- هدر اصلی -->
    <header class="workforce-header">
        <div class="header-left">
            <div class="logo">
                <svg class="logo-icon" width="32" height="32" viewBox="0 0 24 24">
                    <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                </svg>
                <h1>کارکرد پرسنل بنی اسد</h1>
            </div>
            
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo get_avatar($user_id, 40); ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo $current_user->display_name; ?></div>
                    <div class="user-role">
                        <?php 
                        if ($is_admin) echo 'مدیر سیستم';
                        elseif ($is_org_manager) echo 'مدیر سازمان';
                        elseif ($is_dept_manager) echo 'مدیر اداره';
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="header-right">
            <div class="period-selector">
                <select id="periodSelect" class="period-select">
                    <?php foreach ($active_periods as $period): ?>
                    <option value="<?php echo $period['id']; ?>" 
                            <?php selected($current_period['id'], $period['id']); ?>>
                        <?php echo esc_html($period['period_name']); ?>
                        <?php if ($period['is_locked']): ?> 🔒 <?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="header-actions">
                <button class="btn-icon" title="بارگذاری مجدد" onclick="location.reload()">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
                    </svg>
                </button>
                
                <button class="btn-icon" title="خروجی Excel" onclick="exportToExcel()">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
                    </svg>
                </button>
                
                <div class="notification-bell">
                    <button class="btn-icon" title="اعلان‌ها" onclick="showNotifications()">
                        <svg width="20" height="20" viewBox="0 0 24 24">
                            <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                        </svg>
                        <span class="notification-count">3</span>
                    </button>
                </div>
                
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn-logout" title="خروج">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/>
                    </svg>
                </a>
            </div>
        </div>
    </header>
    
    <!-- کارت‌های مانیتورینگ -->
    <section class="monitoring-cards" id="monitoringCards">
        <div class="cards-grid">
            <!-- کارت خوش‌آمدگویی -->
            <div class="card welcome-card">
                <div class="card-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                    </svg>
                </div>
                <div class="card-content">
                    <h3>سلام <?php echo $current_user->first_name ?: $current_user->display_name; ?> عزیز</h3>
                    <p>به پنل مدیریت کارکرد پرسنل خوش آمدید</p>
                    <div class="card-meta">
                        <span class="meta-item">
                            <svg width="16" height="16" viewBox="0 0 24 24">
                                <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                            </svg>
                            <?php echo esc_html($current_period['period_name']); ?>
                        </span>
                        <span class="meta-item">
                            <svg width="16" height="16" viewBox="0 0 24 24">
                                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                            </svg>
                            <?php echo count($managed_departments); ?> اداره
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- کارت تعداد پرسنل -->
            <div class="card stat-card">
                <div class="card-header">
                    <h4>تعداد پرسنل</h4>
                    <div class="card-trend up">
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/>
                        </svg>
                        ۱۲٪+
                    </div>
                </div>
                <div class="card-value" id="totalPersonnel">0</div>
                <div class="card-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 75%"></div>
                    </div>
                    <span class="progress-text">۷۵٪ از هدف</span>
                </div>
            </div>
            
            <!-- کارت فیلدهای اصلی -->
            <div class="card stat-card">
                <div class="card-header">
                    <h4>فیلدهای اصلی</h4>
                    <div class="card-trend down">
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <path d="M16 18l2.29-2.29-4.88-4.88-4 4L2 7.41 3.41 6l6 6 4-4 6.3 6.29L22 12v6z"/>
                        </svg>
                        ۵٪-
                    </div>
                </div>
                <div class="card-value">
                    <span id="filledMainFields">0</span>/<span id="totalMainFields">0</span>
                </div>
                <div class="card-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" id="mainFieldsProgress" style="width: 0%"></div>
                    </div>
                    <span class="progress-text" id="mainFieldsPercent">۰٪ تکمیل</span>
                </div>
            </div>
            
            <!-- کارت وضعیت ادارات -->
            <div class="card status-card">
                <div class="card-header">
                    <h4>وضعیت ادارات</h4>
                    <button class="btn-refresh" onclick="refreshDepartmentStatus()">
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
                        </svg>
                    </button>
                </div>
                <div class="departments-status" id="departmentsStatus">
                    <!-- ادارات به صورت داینامیک لود می‌شوند -->
                    <div class="loading">در حال بارگذاری...</div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- کارت‌های ادارات (فقط برای مدیر سازمان) -->
    <?php if ($is_admin || $is_org_manager): ?>
    <section class="departments-cards" id="departmentsCards">
        <h3 class="section-title">
            <svg width="20" height="20" viewBox="0 0 24 24">
                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
            </svg>
            ادارات تحت مدیریت
        </h3>
        <div class="cards-grid" id="departmentCardsGrid">
            <!-- کارت‌های ادارات به صورت داینامیک لود می‌شوند -->
        </div>
    </section>
    <?php endif; ?>
    
    <!-- جدول اصلی -->
    <section class="main-table-section">
        <div class="table-header">
            <div class="header-left">
                <h3 class="section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                    </svg>
                    اطلاعات پرسنل
                </h3>
                <div class="table-stats" id="tableStats">
                    <span class="stat-item">۰ رکورد</span>
                    <span class="stat-item">صفحه ۱ از ۱</span>
                </div>
            </div>
            
            <div class="header-right">
                <div class="search-box">
                    <input type="text" id="tableSearch" placeholder="جستجوی پرسنل..." class="search-input">
                    <button class="search-button">
                        <svg width="18" height="18" viewBox="0 0 24 24">
                            <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                        </svg>
                    </button>
                </div>
                
                <button class="btn-primary" onclick="addNewPersonnel()">
                    <svg width="16" height="16" viewBox="0 0 24 24">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                    </svg>
                    افزودن پرسنل
                </button>
                
                <div class="table-actions">
                    <button class="btn-icon" title="فیلترها" onclick="toggleFilters()">
                        <svg width="18" height="18" viewBox="0 0 24 24">
                            <path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z"/>
                        </svg>
                    </button>
                    <button class="btn-icon" title="مرتب‌سازی" onclick="showSortOptions()">
                        <svg width="18" height="18" viewBox="0 0 24 24">
                            <path d="M3 18h6v-2H3v2zM3 6v2h18V6H3zm0 7h12v-2H3v2z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- فیلترها -->
        <div class="table-filters" id="tableFilters" style="display: none;">
            <div class="filters-grid">
                <div class="filter-group">
                    <label>اداره</label>
                    <select id="filterDepartment" class="filter-select" multiple>
                        <?php foreach ($managed_departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>"><?php echo esc_html($dept['department_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>وضعیت</label>
                    <select id="filterStatus" class="filter-select">
                        <option value="">همه</option>
                        <option value="active">فعال</option>
                        <option value="inactive">غیرفعال</option>
                        <option value="pending">در انتظار</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>تأیید</label>
                    <select id="filterVerified" class="filter-select">
                        <option value="">همه</option>
                        <option value="1">تأیید شده</option>
                        <option value="0">تأیید نشده</option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button class="btn-secondary" onclick="applyFilters()">اعمال فیلتر</button>
                    <button class="btn-link" onclick="clearFilters()">پاک کردن</button>
                </div>
            </div>
        </div>
        
        <!-- جدول -->
        <div class="table-container" id="tableContainer">
            <div class="table-wrapper">
                <table class="data-table" id="personnelTable">
                    <thead>
                        <tr id="tableHeader">
                            <!-- هدرها به صورت داینامیک ساخته می‌شوند -->
                            <th width="50">ردیف</th>
                            <th>کد ملی</th>
                            <th>نام و نام خانوادگی</th>
                            <th>اداره</th>
                            <th width="100">عملیات</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <!-- داده‌ها به صورت داینامیک لود می‌شوند -->
                        <tr>
                            <td colspan="5" class="loading-cell">
                                <div class="loading-spinner"></div>
                                در حال بارگذاری اطلاعات...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- صفحه‌بندی -->
            <div class="table-pagination" id="tablePagination">
                <div class="pagination-info">
                    نمایش <span id="startRow">۱</span> تا <span id="endRow">۵۰</span> از <span id="totalRows">۰</span> رکورد
                </div>
                <div class="pagination-controls">
                    <button class="page-btn" onclick="goToPage(1)" disabled>
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <path d="M18.41 16.59L13.82 12l4.59-4.59L17 6l-6 6 6 6zM6 6h2v12H6z"/>
                        </svg>
                        اولین
                    </button>
                    <button class="page-btn" onclick="prevPage()" disabled>
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <path d="M15.41 16.59L10.83 12l4.58-4.59L14 6l-6 6 6 6 1.41-1.41z"/>
                        </svg>
                        قبلی
                    </button>
                    <div class="page-numbers" id="pageNumbers"></div>
                    <button class="page-btn" onclick="nextPage()" disabled>
                        بعدی
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
                        </svg>
                    </button>
                    <button class="page-btn" onclick="goToLastPage()" disabled>
                        آخرین
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <path d="M5.59 7.41L10.18 12l-4.59 4.59L7 18l6-6-6-6-1.41 1.41zM16 6h2v12h-2z"/>
                        </svg>
                    </button>
                </div>
                <div class="page-size">
                    <select id="pageSize" onchange="changePageSize()">
                        <option value="10">۱۰ رکورد</option>
                        <option value="25">۲۵ رکورد</option>
                        <option value="50" selected>۵۰ رکورد</option>
                        <option value="100">۱۰۰ رکورد</option>
                    </select>
                </div>
            </div>
        </div>
    </section>
    
    <!-- پنل ویرایش -->
    <div class="edit-panel" id="editPanel">
        <div class="panel-header">
            <h3 id="panelTitle">ویرایش اطلاعات پرسنل</h3>
            <button class="panel-close" onclick="closeEditPanel()">
                <svg width="20" height="20" viewBox="0 0 24 24">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>
        </div>
        
        <div class="panel-content">
            <form id="personnelForm">
                <div class="form-grid" id="formFields">
                    <!-- فیلدها به صورت داینامیک ساخته می‌شوند -->
                </div>
                
                <input type="hidden" id="editPersonnelId" value="0">
                <input type="hidden" id="editPersonnelPeriod" value="<?php echo $current_period['id']; ?>">
            </form>
        </div>
        
        <div class="panel-footer">
            <div class="form-actions">
                <button class="btn-danger" onclick="deletePersonnel()" id="deleteBtn" style="display: none;">
                    <svg width="16" height="16" viewBox="0 0 24 24">
                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                    </svg>
                    حذف
                </button>
                
                <div class="nav-buttons">
                    <button class="btn-secondary" onclick="prevRecord()" id="prevBtn">
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <path d="M15.41 16.59L10.83 12l4.58-4.59L14 6l-6 6 6 6 1.41-1.41z"/>
                        </svg>
                        قبلی
                    </button>
                    <button class="btn-secondary" onclick="nextRecord()" id="nextBtn">
                        بعدی
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
                        </svg>
                    </button>
                </div>
                
                <div class="save-buttons">
                    <button class="btn-secondary" onclick="closeEditPanel()">انصراف</button>
                    <button class="btn-primary" onclick="savePersonnel()">
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
                        </svg>
                        ذخیره
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- مودال فیلتر ستونی -->
    <div class="column-filter-modal" id="columnFilterModal">
        <div class="filter-modal-content">
            <div class="filter-header">
                <h4 id="filterColumnTitle">فیلتر ستون</h4>
                <button class="filter-close" onclick="closeColumnFilter()">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </div>
            <div class="filter-body">
                <div class="filter-search">
                    <input type="text" placeholder="جستجو..." id="filterSearch">
                </div>
                <div class="filter-options" id="filterOptions">
                    <!-- گزینه‌ها به صورت داینامیک -->
                </div>
                <div class="filter-actions">
                    <label class="checkbox-label">
                        <input type="checkbox" id="selectAllOptions"> انتخاب همه
                    </label>
                    <div class="action-buttons">
                        <button class="btn-secondary" onclick="clearFilterOptions()">پاک کردن</button>
                        <button class="btn-primary" onclick="applyColumnFilter()">اعمال</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- مودال تأیید -->
    <div class="confirmation-modal" id="confirmationModal">
        <div class="modal-content">
            <div class="modal-icon" id="modalIcon"></div>
            <h3 id="modalMessage">آیا مطمئن هستید؟</h3>
            <div class="modal-actions">
                <button class="btn-secondary" onclick="closeModal()">انصراف</button>
                <button class="btn-primary" id="modalConfirmBtn">تأیید</button>
            </div>
        </div>
    </div>
    
    <!-- نوتیفیکیشن -->
    <div class="notifications-container" id="notificationsContainer">
        <div class="notifications-list" id="notificationsList">
            <!-- نوتیفیکیشن‌ها -->
        </div>
    </div>
</div>

<script>
// متغیرهای سراسری
let currentData = [];
let currentPage = 1;
let totalPages = 1;
let pageSize = 50;
let currentFilters = {};
let currentSort = {};
let currentPersonnelIndex = -1;
let currentColumnFilter = null;
let tableHeaders = [];

// بارگذاری اولیه
document.addEventListener('DOMContentLoaded', function() {
    initDashboard();
});

function initDashboard() {
    // بارگذاری آمار
    loadStatistics();
    
    // بارگذاری کارت‌های ادارات
    if (workforceManagerData.userRole === 'admin' || workforceManagerData.userRole === 'org_manager') {
        loadDepartmentCards();
    }
    
    // بارگذاری وضعیت ادارات
    loadDepartmentsStatus();
    
    // بارگذاری جدول
    loadTableData();
    
    // ساخت هدر جدول
    buildTableHeaders();
    
    // رویدادها
    setupEventListeners();
}

function loadStatistics() {
    fetch(workforceManagerData.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'workforce_manager_ajax',
            action_type: 'get_statistics',
            period_id: workforceManagerData.currentPeriod.id,
            nonce: workforceManagerData.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const stats = data.data;
            
            // به‌روزرسانی کارت‌ها
            document.getElementById('totalPersonnel').textContent = stats.total_personnel.toLocaleString();
            document.getElementById('filledMainFields').textContent = stats.filled_main_fields;
            document.getElementById('totalMainFields').textContent = stats.total_main_fields;
            
            const percent = stats.total_main_fields > 0 ? 
                Math.round((stats.filled_main_fields / stats.total_main_fields) * 100) : 0;
            
            document.getElementById('mainFieldsProgress').style.width = percent + '%';
            document.getElementById('mainFieldsPercent').textContent = percent + '% تکمیل';
        }
    })
    .catch(error => {
        console.error('Error loading statistics:', error);
    });
}

function loadDepartmentCards() {
    fetch(workforceManagerData.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'workforce_manager_ajax',
            action_type: 'get_department_cards',
            period_id: workforceManagerData.currentPeriod.id,
            nonce: workforceManagerData.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const container = document.getElementById('departmentCardsGrid');
            container.innerHTML = '';
            
            data.data.forEach(dept => {
                const card = createDepartmentCard(dept);
                container.appendChild(card);
            });
        }
    });
}

function createDepartmentCard(dept) {
    const card = document.createElement('div');
    card.className = 'card department-card';
    
    const percent = dept.total_personnel > 0 ? 
        Math.round((dept.filled_main_fields / (dept.total_main_fields * dept.total_personnel)) * 100) : 0;
    
    const statusClass = percent >= 90 ? 'status-good' : 
                       percent >= 70 ? 'status-warning' : 'status-bad';
    
    card.innerHTML = `
        <div class="card-header">
            <h4>${dept.department_name}</h4>
            <div class="card-status ${statusClass}">
                ${percent}%
            </div>
        </div>
        <div class="card-content">
            <div class="dept-stats">
                <div class="stat-item">
                    <span class="stat-label">پرسنل</span>
                    <span class="stat-value">${dept.total_personnel}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">تکمیل</span>
                    <span class="stat-value">${dept.filled_main_fields}/${dept.total_main_fields * dept.total_personnel}</span>
                </div>
            </div>
            <div class="card-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${percent}%"></div>
                </div>
            </div>
            <div class="card-meta">
                <span class="meta-item">
                    <svg width="14" height="14" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                    </svg>
                    ${dept.manager_name || 'تعیین نشده'}
                </span>
            </div>
        </div>
        <div class="card-actions">
            <button class="btn-small" onclick="viewDepartment(${dept.id})">مشاهده</button>
            <button class="btn-small btn-primary" onclick="editDepartmentPersonnel(${dept.id})">ویرایش</button>
        </div>
    `;
    
    return card;
}

function loadDepartmentsStatus() {
    fetch(workforceManagerData.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'workforce_manager_ajax',
            action_type: 'get_departments_status',
            period_id: workforceManagerData.currentPeriod.id,
            nonce: workforceManagerData.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const container = document.getElementById('departmentsStatus');
            container.innerHTML = '';
            
            data.data.forEach(dept => {
                const item = createDepartmentStatusItem(dept);
                container.appendChild(item);
            });
        }
    });
}

function createDepartmentStatusItem(dept) {
    const item = document.createElement('div');
    item.className = 'department-status-item';
    
    const percent = dept.total_personnel > 0 ? 
        Math.round((dept.filled_main_fields / (dept.total_main_fields * dept.total_personnel)) * 100) : 0;
    
    const statusIcon = percent >= 90 ? '✅' : percent >= 70 ? '⚠️' : '❌';
    
    item.innerHTML = `
        <div class="dept-status-name">
            <span class="status-icon">${statusIcon}</span>
            ${dept.department_name}
        </div>
        <div class="dept-status-progress">
            <div class="progress-bar small">
                <div class="progress-fill" style="width: ${percent}%"></div>
            </div>
            <span class="progress-text">${percent}%</span>
        </div>
    `;
    
    return item;
}

function buildTableHeaders() {
    const headerRow = document.getElementById('tableHeader');
    headerRow.innerHTML = '';
    
    // هدرهای ثابت
    const fixedHeaders = [
        { title: 'ردیف', width: '50', sortable: false },
        { title: 'کد ملی', width: '120', sortable: true, field: 'national_code' },
        { title: 'نام و نام خانوادگی', width: '200', sortable: true, field: 'full_name' },
        { title: 'اداره', width: '150', sortable: true, field: 'department_name' }
    ];
    
    // هدرهای فیلدهای اصلی
    workforceManagerData.mainFields.forEach(field => {
        if (field.is_main) {
            fixedHeaders.push({
                title: field.field_name,
                width: '150',
                sortable: true,
                field: field.field_key,
                is_main: true,
                field_type: field.field_type
            });
        }
    });
    
    // هدر عملیات
    fixedHeaders.push({ title: 'عملیات', width: '100', sortable: false });
    
    // ذخیره هدرها
    tableHeaders = fixedHeaders;
    
    // ساخت هدرهای HTML
    fixedHeaders.forEach((header, index) => {
        const th = document.createElement('th');
        th.style.width = header.width + 'px';
        
        let content = header.title;
        
        if (header.sortable) {
            content = `
                <div class="header-content">
                    <span>${header.title}</span>
                    <div class="header-actions">
                        <button class="header-btn" onclick="sortColumn('${header.field}')" title="مرتب‌سازی">
                            <svg width="14" height="14" viewBox="0 0 24 24">
                                <path d="M3 18h6v-2H3v2zM3 6v2h18V6H3zm0 7h12v-2H3v2z"/>
                            </svg>
                        </button>
                        <button class="header-btn" onclick="openColumnFilter(${index})" title="فیلتر">
                            <svg width="14" height="14" viewBox="0 0 24 24">
                                <path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z"/>
                            </svg>
                        </button>
                        ${header.is_main ? `
                        <button class="header-btn" onclick="toggleColumnSummary(${index})" title="خلاصه">
                            <svg width="14" height="14" viewBox="0 0 24 24">
                                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                            </svg>
                        </button>
                        ` : ''}
                    </div>
                </div>
            `;
        }
        
        th.innerHTML = content;
        headerRow.appendChild(th);
    });
}

function loadTableData() {
    const params = {
        page: currentPage,
        per_page: pageSize,
        period_id: workforceManagerData.currentPeriod.id,
        ...currentFilters,
        ...currentSort
    };
    
    // نشانگر لودینگ
    document.getElementById('tableBody').innerHTML = `
        <tr>
            <td colspan="${tableHeaders.length}" class="loading-cell">
                <div class="loading-spinner"></div>
                در حال بارگذاری اطلاعات...
            </td>
        </tr>
    `;
    
    fetch(workforceManagerData.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'workforce_manager_ajax',
            action_type: 'get_personnel',
            ...params,
            nonce: workforceManagerData.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentData = data.data.data;
            updateTable(data.data);
            updatePagination(data.data.pagination);
        } else {
            showError('خطا در بارگذاری اطلاعات');
        }
    })
    .catch(error => {
        console.error('Error loading table data:', error);
        showError('خطا در ارتباط با سرور');
    });
}

function updateTable(data) {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';
    
    if (data.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="${tableHeaders.length}" class="empty-cell">
                    <svg width="48" height="48" viewBox="0 0 24 24">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/>
                    </svg>
                    <p>داده‌ای برای نمایش وجود ندارد</p>
                </td>
            </tr>
        `;
        return;
    }
    
    data.forEach((row, index) => {
        const tr = document.createElement('tr');
        tr.dataset.id = row.id;
        
        let rowHtml = '';
        
        // ردیف
        rowHtml += `<td>${((currentPage - 1) * pageSize) + index + 1}</td>`;
        
        // کد ملی
        rowHtml += `<td><code>${row.national_code}</code></td>`;
        
        // نام کامل
        rowHtml += `<td>${row.first_name || ''} ${row.last_name || ''}</td>`;
        
        // اداره
        rowHtml += `<td>${row.department_name}</td>`;
        
        // فیلدهای اصلی
        workforceManagerData.mainFields.forEach(field => {
            if (field.is_main) {
                const value = row.data && row.data[field.field_key] ? 
                    formatFieldValue(row.data[field.field_key], field.field_type) : 
                    '<span class="empty-value">—</span>';
                rowHtml += `<td>${value}</td>`;
            }
        });
        
        // عملیات
        rowHtml += `
            <td>
                <div class="row-actions">
                    <button class="btn-action" onclick="editPersonnel(${row.id})" title="ویرایش">
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                        </svg>
                    </button>
                    <button class="btn-action" onclick="viewPersonnel(${row.id})" title="مشاهده">
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                    </button>
                    ${row.is_verified ? '' : `
                    <button class="btn-action btn-success" onclick="verifyPersonnel(${row.id})" title="تأیید">
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                        </svg>
                    </button>
                    `}
                </div>
            </td>
        `;
        
        tr.innerHTML = rowHtml;
        tbody.appendChild(tr);
        
        // رویداد کلیک روی سطر
        tr.addEventListener('click', (e) => {
            if (!e.target.closest('.row-actions')) {
                editPersonnel(row.id);
            }
        });
    });
}

function updatePagination(pagination) {
    const totalRows = pagination.total;
    totalPages = pagination.total_pages;
    currentPage = pagination.current_page;
    
    // به‌روزرسانی اطلاعات
    document.getElementById('startRow').textContent = ((currentPage - 1) * pageSize) + 1;
    document.getElementById('endRow').textContent = Math.min(currentPage * pageSize, totalRows);
    document.getElementById('totalRows').textContent = totalRows.toLocaleString();
    
    // به‌روزرساری دکمه‌ها
    document.querySelectorAll('.page-btn').forEach(btn => {
        btn.disabled = false;
    });
    
    if (currentPage === 1) {
        document.querySelector('.page-btn:nth-child(1)').disabled = true;
        document.querySelector('.page-btn:nth-child(2)').disabled = true;
    }
    
    if (currentPage === totalPages) {
        document.querySelector('.page-btn:nth-last-child(2)').disabled = true;
        document.querySelector('.page-btn:nth-last-child(1)').disabled = true;
    }
    
    // ساخت شماره صفحات
    const pageNumbers = document.getElementById('pageNumbers');
    pageNumbers.innerHTML = '';
    
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);
    
    if (endPage - startPage < 4) {
        startPage = Math.max(1, endPage - 4);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const btn = document.createElement('button');
        btn.className = 'page-number';
        if (i === currentPage) {
            btn.classList.add('active');
        }
        btn.textContent = i;
        btn.onclick = () => goToPage(i);
        pageNumbers.appendChild(btn);
    }
    
    // اگر صفحات بیشتری وجود دارد
    if (endPage < totalPages) {
        const dots = document.createElement('span');
        dots.className = 'page-dots';
        dots.textContent = '...';
        pageNumbers.appendChild(dots);
        
        const lastBtn = document.createElement('button');
        lastBtn.className = 'page-number';
        lastBtn.textContent = totalPages;
        lastBtn.onclick = () => goToPage(totalPages);
        pageNumbers.appendChild(lastBtn);
    }
}

// توابع ناوبری
function goToPage(page) {
    if (page >= 1 && page <= totalPages && page !== currentPage) {
        currentPage = page;
        loadTableData();
    }
}

function prevPage() {
    if (currentPage > 1) {
        goToPage(currentPage - 1);
    }
}

function nextPage() {
    if (currentPage < totalPages) {
        goToPage(currentPage + 1);
    }
}

function goToLastPage() {
    goToPage(totalPages);
}

function changePageSize() {
    pageSize = parseInt(document.getElementById('pageSize').value);
    currentPage = 1;
    loadTableData();
}

// توابع فیلتر
function toggleFilters() {
    const filters = document.getElementById('tableFilters');
    filters.style.display = filters.style.display === 'none' ? 'block' : 'none';
}

function applyFilters() {
    currentFilters = {
        department_id: Array.from(document.getElementById('filterDepartment').selectedOptions)
            .map(opt => opt.value)
            .filter(val => val),
        status: document.getElementById('filterStatus').value,
        is_verified: document.getElementById('filterVerified').value
    };
    
    // حذف فیلترهای خالی
    Object.keys(currentFilters).forEach(key => {
        if (!currentFilters[key] || 
            (Array.isArray(currentFilters[key]) && currentFilters[key].length === 0)) {
            delete currentFilters[key];
        }
    });
    
    currentPage = 1;
    loadTableData();
    
    // بستن فیلترها
    document.getElementById('tableFilters').style.display = 'none';
}

function clearFilters() {
    document.getElementById('filterDepartment').selectedIndex = -1;
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterVerified').value = '';
    currentFilters = {};
    currentPage = 1;
    loadTableData();
}

// توابع مرتب‌سازی
function sortColumn(field) {
    if (currentSort.field === field) {
        // تغییر جهت مرتب‌سازی
        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
        // مرتب‌سازی جدید
        currentSort = { field, direction: 'asc' };
    }
    
    loadTableData();
}

// توابع ویرایش
function addNewPersonnel() {
    openEditPanel(null);
}

function editPersonnel(id) {
    const index = currentData.findIndex(item => item.id == id);
    if (index !== -1) {
        currentPersonnelIndex = index;
        openEditPanel(currentData[index]);
    }
}

function viewPersonnel(id) {
    // باز کردن پنل ویرایش در حالت مشاهده
    const personnel = currentData.find(item => item.id == id);
    if (personnel) {
        openEditPanel(personnel, true);
    }
}

function openEditPanel(personnel = null, readOnly = false) {
    const panel = document.getElementById('editPanel');
    const title = document.getElementById('panelTitle');
    const form = document.getElementById('formFields');
    const deleteBtn = document.getElementById('deleteBtn');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    
    // تنظیم عنوان
    if (personnel) {
        title.textContent = readOnly ? 'مشاهده اطلاعات پرسنل' : 'ویرایش اطلاعات پرسنل';
    } else {
        title.textContent = 'افزودن پرسنل جدید';
    }
    
    // ساخت فرم
    form.innerHTML = '';
    
    // فیلدهای ثابت
    const fixedFields = [
        {
            key: 'national_code',
            name: 'کد ملی',
            type: 'text',
            required: true,
            pattern: '\\d{10}',
            maxlength: 10
        },
        {
            key: 'first_name',
            name: 'نام',
            type: 'text',
            required: true
        },
        {
            key: 'last_name',
            name: 'نام خانوادگی',
            type: 'text',
            required: true
        }
    ];
    
    // اضافه کردن فیلدهای ثابت
    fixedFields.forEach(field => {
        const value = personnel ? personnel[field.key] || '' : '';
        const fieldHtml = createFormField(field, value, readOnly);
        form.appendChild(fieldHtml);
    });
    
    // فیلدهای پویا
    workforceManagerData.mainFields.forEach(field => {
        const value = personnel && personnel.data ? personnel.data[field.field_key] || '' : '';
        const fieldData = {
            key: field.field_key,
            name: field.field_name,
            type: field.field_type,
            required: field.is_required,
            is_main: field.is_main,
            dropdown_values: field.dropdown_values
        };
        
        const fieldHtml = createFormField(fieldData, value, readOnly);
        form.appendChild(fieldHtml);
    });
    
    // ذخیره ID
    document.getElementById('editPersonnelId').value = personnel ? personnel.id : 0;
    
    // نمایش/عدم نمایش دکمه‌ها
    deleteBtn.style.display = personnel && !readOnly ? 'block' : 'none';
    prevBtn.style.display = personnel ? 'block' : 'none';
    nextBtn.style.display = personnel ? 'block' : 'none';
    
    // باز کردن پنل
    panel.classList.add('open');
}

function createFormField(field, value, readOnly) {
    const div = document.createElement('div');
    div.className = 'form-group';
    
    let inputHtml = '';
    const requiredAttr = field.required ? 'required' : '';
    const readonlyAttr = readOnly ? 'readonly' : '';
    
    switch (field.type) {
        case 'dropdown':
            inputHtml = `
                <select id="field_${field.key}" name="${field.key}" ${requiredAttr} ${readonlyAttr} class="form-control">
                    <option value="">انتخاب کنید</option>
                    ${field.dropdown_values ? field.dropdown_values.map(opt => `
                        <option value="${opt}" ${value == opt ? 'selected' : ''}>${opt}</option>
                    `).join('') : ''}
                </select>
            `;
            break;
            
        case 'textarea':
            inputHtml = `
                <textarea id="field_${field.key}" name="${field.key}" ${requiredAttr} ${readonlyAttr} 
                          class="form-control" rows="3">${value || ''}</textarea>
            `;
            break;
            
        default:
            inputHtml = `
                <input type="${field.type}" id="field_${field.key}" name="${field.key}" 
                       value="${value || ''}" ${requiredAttr} ${readonlyAttr} 
                       class="form-control" ${field.pattern ? `pattern="${field.pattern}"` : ''}
                       ${field.maxlength ? `maxlength="${field.maxlength}"` : ''}>
            `;
    }
    
    div.innerHTML = `
        <label for="field_${field.key}">
            ${field.name}
            ${field.required ? '<span class="required">*</span>' : ''}
            ${field.is_main ? '<span class="main-badge">اصلی</span>' : ''}
        </label>
        ${inputHtml}
    `;
    
    return div;
}

function closeEditPanel() {
    document.getElementById('editPanel').classList.remove('open');
    currentPersonnelIndex = -1;
}

function savePersonnel() {
    const formData = new FormData();
    const personnelId = document.getElementById('editPersonnelId').value;
    const periodId = document.getElementById('editPersonnelPeriod').value;
    
    // جمع‌آوری داده‌های فرم
    formData.append('national_code', document.getElementById('field_national_code').value);
    formData.append('first_name', document.getElementById('field_first_name').value);
    formData.append('last_name', document.getElementById('field_last_name').value);
    formData.append('period_id', periodId);
    
    // فیلدهای پویا
    const dynamicData = {};
    workforceManagerData.mainFields.forEach(field => {
        const input = document.getElementById(`field_${field.field_key}`);
        if (input) {
            dynamicData[field.field_key] = input.value;
        }
    });
    
    formData.append('data', JSON.stringify(dynamicData));
    
    // ارسال به سرور
    fetch(workforceManagerData.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'workforce_manager_ajax',
            action_type: personnelId > 0 ? 'update_personnel' : 'create_personnel',
            personnel_id: personnelId,
            ...Object.fromEntries(formData),
            nonce: workforceManagerData.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('اطلاعات با موفقیت ذخیره شد', 'success');
            closeEditPanel();
            loadTableData();
            loadStatistics();
            loadDepartmentsStatus();
            
            if (workforceManagerData.userRole === 'admin' || workforceManagerData.userRole === 'org_manager') {
                loadDepartmentCards();
            }
        } else {
            showNotification(data.message || 'خطا در ذخیره اطلاعات', 'error');
        }
    })
    .catch(error => {
        console.error('Error saving personnel:', error);
        showNotification('خطا در ارتباط با سرور', 'error');
    });
}

function deletePersonnel() {
    const personnelId = document.getElementById('editPersonnelId').value;
    
    showConfirmation('آیا از حذف این پرسنل اطمینان دارید؟', () => {
        fetch(workforceManagerData.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'workforce_manager_ajax',
                action_type: 'delete_personnel',
                personnel_id: personnelId,
                nonce: workforceManagerData.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('پرسنل با موفقیت حذف شد', 'success');
                closeEditPanel();
                loadTableData();
                loadStatistics();
            } else {
                showNotification(data.message || 'خطا در حذف', 'error');
            }
        })
        .catch(error => {
            console.error('Error deleting personnel:', error);
            showNotification('خطا در ارتباط با سرور', 'error');
        });
    });
}

function prevRecord() {
    if (currentPersonnelIndex > 0) {
        currentPersonnelIndex--;
        editPersonnel(currentData[currentPersonnelIndex].id);
    }
}

function nextRecord() {
    if (currentPersonnelIndex < currentData.length - 1) {
        currentPersonnelIndex++;
        editPersonnel(currentData[currentPersonnelIndex].id);
    }
}

function verifyPersonnel(id) {
    showConfirmation('آیا از تأیید این پرسنل اطمینان دارید؟', () => {
        fetch(workforceManagerData.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'workforce_manager_ajax',
                action_type: 'verify_personnel',
                personnel_id: id,
                nonce: workforceManagerData.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('پرسنل با موفقیت تأیید شد', 'success');
                loadTableData();
            } else {
                showNotification(data.message || 'خطا در تأیید', 'error');
            }
        })
        .catch(error => {
            console.error('Error verifying personnel:', error);
            showNotification('خطا در ارتباط با سرور', 'error');
        });
    });
}

// توابع فیلتر ستونی
function openColumnFilter(columnIndex) {
    currentColumnFilter = columnIndex;
    const header = tableHeaders[columnIndex];
    
    if (!header || !header.field) return;
    
    document.getElementById('filterColumnTitle').textContent = `فیلتر ${header.title}`;
    
    // جمع‌آوری مقادیر منحصربه‌فرد
    const values = [...new Set(currentData.map(row => {
        if (header.field === 'full_name') {
            return `${row.first_name || ''} ${row.last_name || ''}`.trim();
        } else if (header.field === 'national_code') {
            return row.national_code;
        } else if (header.field === 'department_name') {
            return row.department_name;
        } else {
            return row.data && row.data[header.field] ? row.data[header.field] : null;
        }
    }).filter(val => val !== null && val !== ''))];
    
    const optionsContainer = document.getElementById('filterOptions');
    optionsContainer.innerHTML = '';
    
    values.forEach(value => {
        const option = document.createElement('label');
        option.className = 'checkbox-option';
        option.innerHTML = `
            <input type="checkbox" value="${value}">
            <span>${formatFieldValue(value, header.field_type)}</span>
        `;
        optionsContainer.appendChild(option);
    });
    
    document.getElementById('columnFilterModal').style.display = 'flex';
}

function closeColumnFilter() {
    document.getElementById('columnFilterModal').style.display = 'none';
    currentColumnFilter = null;
}

function applyColumnFilter() {
    if (currentColumnFilter === null) return;
    
    const header = tableHeaders[currentColumnFilter];
    const checkedValues = Array.from(
        document.querySelectorAll('#filterOptions input:checked')
    ).map(input => input.value);
    
    if (checkedValues.length > 0) {
        currentFilters[header.field] = checkedValues;
        currentPage = 1;
        loadTableData();
    }
    
    closeColumnFilter();
}

function clearFilterOptions() {
    document.querySelectorAll('#filterOptions input').forEach(input => {
        input.checked = false;
    });
}

function toggleColumnSummary(columnIndex) {
    // نمایش خلاصه ستون
    const header = tableHeaders[columnIndex];
    const values = currentData.map(row => {
        if (header.field === 'full_name' || header.field === 'department_name') {
            return null;
        }
        
        let value = null;
        if (header.field === 'national_code') {
            value = row.national_code;
        } else {
            value = row.data && row.data[header.field] ? parseFloat(row.data[header.field]) : null;
        }
        
        return value;
    }).filter(val => val !== null);
    
    if (values.length === 0) return;
    
    const sum = values.reduce((a, b) => a + b, 0);
    const avg = sum / values.length;
    const max = Math.max(...values);
    const min = Math.min(...values);
    
    showNotification(`
        <strong>خلاصه ${header.title}:</strong><br>
        مجموع: ${sum.toLocaleString()}<br>
        میانگین: ${avg.toLocaleString()}<br>
        حداکثر: ${max.toLocaleString()}<br>
        حداقل: ${min.toLocaleString()}
    `, 'info', 5000);
}

// توابع کمکی
function formatFieldValue(value, type) {
    if (value === null || value === undefined || value === '') {
        return '<span class="empty-value">—</span>';
    }
    
    switch (type) {
        case 'number':
        case 'decimal':
            return parseFloat(value).toLocaleString();
        case 'date':
            // تبدیل تاریخ شمسی
            return value;
        default:
            return value;
    }
}

function exportToExcel() {
    const params = {
        period_id: workforceManagerData.currentPeriod.id,
        ...currentFilters,
        format: 'excel'
    };
    
    showNotification('در حال ایجاد فایل Excel...', 'info');
    
    fetch(workforceManagerData.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'workforce_manager_ajax',
            action_type: 'export_excel',
            ...params,
            nonce: workforceManagerData.nonce
        })
    })
    .then(response => response.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `کارکرد_پرسنل_${workforceManagerData.currentPeriod.period_name}.xlsx`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        showNotification('فایل Excel با موفقیت دانلود شد', 'success');
    })
    .catch(error => {
        console.error('Error exporting to Excel:', error);
        showNotification('خطا در ایجاد فایل Excel', 'error');
    });
}

function showNotifications() {
    // بارگذاری اعلان‌ها
    fetch(workforceManagerData.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'workforce_manager_ajax',
            action_type: 'get_notifications',
            nonce: workforceManagerData.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const container = document.getElementById('notificationsContainer');
            const list = document.getElementById('notificationsList');
            
            list.innerHTML = data.data.map(notif => `
                <div class="notification-item ${notif.read ? 'read' : 'unread'}">
                    <div class="notification-icon">
                        ${notif.type === 'success' ? '✅' : 
                          notif.type === 'warning' ? '⚠️' : 
                          notif.type === 'error' ? '❌' : 'ℹ️'}
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">${notif.title}</div>
                        <div class="notification-message">${notif.message}</div>
                        <div class="notification-time">${notif.time}</div>
                    </div>
                    ${!notif.read ? '<div class="notification-dot"></div>' : ''}
                </div>
            `).join('');
            
            container.classList.toggle('open');
        }
    });
}

function showNotification(message, type = 'info', duration = 3000) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">${message}</div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <svg width="16" height="16" viewBox="0 0 24 24">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    // نمایش انیمیشن
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // حذف خودکار
    if (duration > 0) {
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, duration);
    }
}

function showConfirmation(message, confirmCallback) {
    const modal = document.getElementById('confirmationModal');
    const messageEl = document.getElementById('modalMessage');
    const confirmBtn = document.getElementById('modalConfirmBtn');
    const icon = document.getElementById('modalIcon');
    
    messageEl.textContent = message;
    icon.innerHTML = '❓';
    
    // حذف رویدادهای قبلی
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    
    newConfirmBtn.onclick = function() {
        confirmCallback();
        closeModal();
    };
    
    modal.style.display = 'flex';
}

function closeModal() {
    document.getElementById('confirmationModal').style.display = 'none';
}

function showError(message) {
    showNotification(message, 'error');
}

function setupEventListeners() {
    // جستجو
    document.getElementById('tableSearch').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            currentFilters.search = this.value;
            currentPage = 1;
            loadTableData();
        }
    });
    
    // تغییر دوره
    document.getElementById('periodSelect').addEventListener('change', function() {
        workforceManagerData.currentPeriod = workforceManagerData.activePeriods
            .find(p => p.id == this.value);
        currentPage = 1;
        loadTableData();
        loadStatistics();
        loadDepartmentsStatus();
        
        if (workforceManagerData.userRole === 'admin' || workforceManagerData.userRole === 'org_manager') {
            loadDepartmentCards();
        }
    });
    
    // بستن مودال با کلیک خارج
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('confirmationModal');
        if (event.target === modal) {
            closeModal();
        }
        
        const columnModal = document.getElementById('columnFilterModal');
        if (event.target === columnModal) {
            closeColumnFilter();
        }
        
        const notifications = document.getElementById('notificationsContainer');
        if (!notifications.contains(event.target) && 
            !event.target.closest('.notification-bell')) {
            notifications.classList.remove('open');
        }
    });
    
    // کلیدهای میانبر
    document.addEventListener('keydown', function(e) {
        // Ctrl + S برای ذخیره
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            if (document.getElementById('editPanel').classList.contains('open')) {
                savePersonnel();
            }
        }
        
        // Ctrl + F برای جستجو
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            document.getElementById('tableSearch').focus();
        }
        
        // Ctrl + → و Ctrl + ← برای ناوبری
        if (e.ctrlKey && e.key === 'ArrowRight') {
            e.preventDefault();
            nextRecord();
        }
        
        if (e.ctrlKey && e.key === 'ArrowLeft') {
            e.preventDefault();
            prevRecord();
        }
        
        // Escape برای بستن
        if (e.key === 'Escape') {
            if (document.getElementById('editPanel').classList.contains('open')) {
                closeEditPanel();
            }
            closeModal();
            closeColumnFilter();
            document.getElementById('notificationsContainer').classList.remove('open');
        }
    });
}

// توابع استفاده نشده (برای تکمیل)
function viewDepartment(deptId) {
    // مشاهده اطلاعات اداره
    currentFilters.department_id = [deptId];
    currentPage = 1;
    loadTableData();
}

function editDepartmentPersonnel(deptId) {
    // ویرایش پرسنل اداره
    currentFilters.department_id = [deptId];
    currentPage = 1;
    loadTableData();
}

function refreshDepartmentStatus() {
    loadDepartmentsStatus();
}

function showSortOptions() {
    // نمایش گزینه‌های مرتب‌سازی
    alert('گزینه‌های مرتب‌سازی');
}

// بارگذاری اولیه
initDashboard();
</script>

<style>
/* استایل‌ها در فایل جداگانه CSS تعریف می‌شوند */
</style>