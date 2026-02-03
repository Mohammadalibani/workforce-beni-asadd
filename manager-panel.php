<?php
/**
 * پنل اصلی مدیران - سیستم مدیریت کارکرد پرسنل بنی اسد
 * فایل اصلی رابط کاربری مدیران (اداره و سازمان)
 * 
 * @package Workforce_Beni_Asad
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// بررسی وجود توابع کمکی
if (!function_exists('wf_get_user_role')) {
    require_once WF_PLUGIN_DIR . 'helpers.php';
}

/**
 * نمایش پنل اصلی مدیران
 *
 * @param string $panel_type نوع پنل (department|organization)
 * @return string HTML خروجی پنل
 */
function wf_render_manager_panel($panel_type = 'department') {
    // بررسی ورود کاربر
    if (!is_user_logged_in()) {
        return wf_render_login_form();
    }
    
    // تشخیص سطح دسترسی کاربر
    $user_id = get_current_user_id();
    $user_role = wf_get_user_role($user_id);
    
    // بررسی مجوز دسترسی
    if (!wf_check_manager_access($user_id, $panel_type)) {
        return wf_render_access_denied();
    }
    
    // دریافت اطلاعات کاربر و اداره
    $user_info = wf_get_manager_info($user_id, $panel_type);
    $active_period = wf_get_active_period();
    
    // بارگذاری داده‌های پرسنل
    $personnel_data = wf_load_personnel_data($user_id, $panel_type, $active_period['id']);
    
    // بارگذاری فیلدهای تعریف شده
    $fields = wf_get_all_fields();
    
    // تولید خروجی HTML
    ob_start();
    ?>
    
    <!-- ==================== -->
    <!-- استایل‌های اختصاصی -->
    <!-- ==================== -->
    <style>
    .wf-panel-container {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
    }
    
    .wf-main-wrapper {
        max-width: 100%;
        margin: 0 auto;
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        overflow: hidden;
        position: relative;
    }
    
    /* هدر هوشمند */
    .wf-smart-header {
        background: linear-gradient(90deg, #1e3a8a 0%, #1e40af 100%);
        color: white;
        padding: 25px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        overflow: hidden;
    }
    
    .wf-smart-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 300px;
        height: 300px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
    }
    
    .wf-user-info h2 {
        margin: 0 0 10px 0;
        font-size: 24px;
        font-weight: 600;
    }
    
    .wf-user-info .wf-meta {
        display: flex;
        gap: 25px;
        font-size: 14px;
        opacity: 0.9;
    }
    
    .wf-meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .wf-meta-item i {
        font-size: 16px;
    }
    
    .wf-period-info {
        background: rgba(255,255,255,0.15);
        padding: 12px 20px;
        border-radius: 12px;
        backdrop-filter: blur(10px);
    }
    
    .wf-period-info .wf-date {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .wf-period-info .wf-status {
        font-size: 12px;
        opacity: 0.8;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    /* کارت‌های مانیتورینگ */
    .wf-monitoring-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        padding: 30px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .wf-card {
        background: white;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        border: 2px solid transparent;
        position: relative;
        overflow: hidden;
    }
    
    .wf-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    
    .wf-card.wf-card-essential {
        border-color: #f59e0b;
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    }
    
    .wf-card.wf-card-critical {
        border-color: #ef4444;
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    }
    
    .wf-card.wf-card-success {
        border-color: #10b981;
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    }
    
    .wf-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .wf-card-title {
        font-size: 14px;
        font-weight: 600;
        color: #4b5563;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .wf-card-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
    }
    
    .wf-card-value {
        font-size: 32px;
        font-weight: 700;
        color: #1f2937;
        margin: 10px 0;
    }
    
    .wf-card-progress {
        height: 6px;
        background: #e5e7eb;
        border-radius: 3px;
        overflow: hidden;
        margin: 15px 0;
    }
    
    .wf-card-progress-bar {
        height: 100%;
        border-radius: 3px;
        transition: width 0.5s ease;
    }
    
    /* نوار ابزار */
    .wf-toolbar {
        background: white;
        padding: 20px 30px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .wf-action-buttons {
        display: flex;
        gap: 12px;
    }
    
    .wf-btn {
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .wf-btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
    }
    
    .wf-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
    }
    
    .wf-btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }
    
    .wf-btn-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    /* جدول اصلی */
    .wf-table-container {
        position: relative;
        overflow: auto;
        max-height: 600px;
        padding: 0 30px 30px;
    }
    
    .wf-excel-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        min-width: 1200px;
    }
    
    .wf-table-header {
        position: sticky;
        top: 0;
        z-index: 50;
        background: white;
    }
    
    .wf-table-header th {
        padding: 18px 15px;
        text-align: right;
        font-weight: 600;
        font-size: 13px;
        color: #4b5563;
        border-bottom: 2px solid #e5e7eb;
        background: #f9fafb;
        white-space: nowrap;
        position: relative;
        user-select: none;
    }
    
    .wf-table-header th.wf-required {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    }
    
    .wf-table-header th.wf-locked {
        background: #1f2937;
        color: white;
    }
    
    .wf-header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    
    .wf-header-icons {
        display: flex;
        gap: 5px;
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    
    .wf-table-header th:hover .wf-header-icons {
        opacity: 1;
    }
    
    .wf-icon-btn {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border: 1px solid #e5e7eb;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 14px;
    }
    
    .wf-icon-btn:hover {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
        transform: scale(1.1);
    }
    
    .wf-table-body td {
        padding: 15px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 14px;
        transition: all 0.2s ease;
        position: relative;
    }
    
    .wf-table-body tr {
        transition: all 0.2s ease;
    }
    
    .wf-table-body tr:hover {
        background: #f8fafc;
    }
    
    .wf-table-body tr.wf-selected {
        background: #dbeafe;
    }
    
    .wf-table-body tr.wf-deleted {
        opacity: 0.5;
    }
    
    .wf-checkbox-cell {
        width: 50px;
        text-align: center;
    }
    
    .wf-checkbox {
        width: 18px;
        height: 18px;
        border-radius: 4px;
        border: 2px solid #d1d5db;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .wf-checkbox:checked {
        background: #3b82f6;
        border-color: #3b82f6;
    }
    
    /* فرم ویرایش سایدبار */
    .wf-edit-sidebar {
        position: fixed;
        top: 0;
        right: -450px;
        width: 450px;
        height: 100vh;
        background: white;
        box-shadow: -5px 0 30px rgba(0,0,0,0.15);
        z-index: 1000;
        transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
    }
    
    .wf-edit-sidebar.wf-active {
        right: 0;
    }
    
    .wf-sidebar-header {
        padding: 25px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
    }
    
    .wf-sidebar-title {
        font-size: 20px;
        font-weight: 600;
        color: #1f2937;
    }
    
    .wf-sidebar-close {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f3f4f6;
        border: none;
        cursor: pointer;
        font-size: 18px;
        transition: all 0.2s ease;
    }
    
    .wf-sidebar-close:hover {
        background: #ef4444;
        color: white;
    }
    
    .wf-sidebar-content {
        flex: 1;
        overflow-y: auto;
        padding: 25px;
    }
    
    .wf-form-group {
        margin-bottom: 25px;
    }
    
    .wf-form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #374151;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .wf-form-label i {
        color: #6b7280;
    }
    
    .wf-form-input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-size: 14px;
        transition: all 0.2s ease;
        background: white;
    }
    
    .wf-form-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .wf-form-input.wf-locked {
        background: #f9fafb;
        color: #6b7280;
        cursor: not-allowed;
    }
    
    .wf-sidebar-footer {
        padding: 20px 25px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 10px;
        background: #f8fafc;
    }
    
    .wf-nav-btn {
        width: 45px;
        height: 45px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border: 2px solid #e5e7eb;
        cursor: pointer;
        font-size: 18px;
        transition: all 0.2s ease;
    }
    
    .wf-nav-btn:hover {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }
    
    .wf-save-btn {
        flex: 1;
        height: 45px;
        border-radius: 10px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .wf-save-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
    }
    
    /* صفحه‌بندی */
    .wf-pagination {
        padding: 20px 30px;
        border-top: 1px solid #e5e8eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
    }
    
    .wf-page-info {
        font-size: 14px;
        color: #6b7280;
    }
    
    .wf-page-buttons {
        display: flex;
        gap: 8px;
    }
    
    .wf-page-btn {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border: 1px solid #e5e7eb;
        color: #4b5563;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 14px;
    }
    
    .wf-page-btn:hover {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }
    
    .wf-page-btn.wf-active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }
    
    /* مودال فیلتر */
    .wf-filter-modal {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.9);
        width: 500px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        z-index: 2000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .wf-filter-modal.wf-active {
        opacity: 1;
        visibility: visible;
        transform: translate(-50%, -50%) scale(1);
    }
    
    .wf-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    .wf-overlay.wf-active {
        opacity: 1;
        visibility: visible;
    }
    
    /* بارگذاری */
    .wf-loading {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(5px);
    }
    
    .wf-spinner {
        width: 60px;
        height: 60px;
        border: 4px solid #e5e7eb;
        border-top-color: #3b82f6;
        border-radius: 50%;
        animation: wf-spin 1s linear infinite;
    }
    
    @keyframes wf-spin {
        to { transform: rotate(360deg); }
    }
    
    /* ریسپانسیو */
    @media (max-width: 1024px) {
        .wf-monitoring-cards {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .wf-edit-sidebar {
            width: 100%;
            right: -100%;
        }
    }
    
    @media (max-width: 768px) {
        .wf-smart-header {
            flex-direction: column;
            gap: 20px;
            text-align: center;
        }
        
        .wf-user-info .wf-meta {
            flex-direction: column;
            gap: 10px;
        }
        
        .wf-monitoring-cards {
            grid-template-columns: 1fr;
        }
        
        .wf-toolbar {
            flex-direction: column;
            gap: 15px;
        }
        
        .wf-action-buttons {
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .wf-filter-modal {
            width: 90%;
        }
    }
    </style>
    
    <!-- ======================== -->
    <!-- HTML اصلی پنل -->
    <!-- ======================== -->
    <div class="wf-panel-container" id="wf-manager-panel">
        
        <!-- Overlay برای مودال‌ها -->
        <div class="wf-overlay" id="wf-overlay"></div>
        
        <!-- بارگذاری -->
        <div class="wf-loading" id="wf-loading">
            <div class="wf-spinner"></div>
        </div>
        
        <!-- فرم ویرایش سایدبار -->
        <div class="wf-edit-sidebar" id="wf-edit-sidebar">
            <div class="wf-sidebar-header">
                <h3 class="wf-sidebar-title" id="wf-edit-title">ویرایش پرسنل</h3>
                <button class="wf-sidebar-close" id="wf-close-edit">✕</button>
            </div>
            <div class="wf-sidebar-content" id="wf-edit-content">
                <!-- فرم به صورت پویا تولید می‌شود -->
            </div>
            <div class="wf-sidebar-footer">
                <button class="wf-nav-btn" id="wf-prev-record">⏮️</button>
                <button class="wf-save-btn" id="wf-save-record">💾 ذخیره</button>
                <button class="wf-nav-btn" id="wf-next-record">⏭️</button>
            </div>
        </div>
        
        <!-- مودال فیلتر -->
        <div class="wf-filter-modal" id="wf-filter-modal">
            <div class="wf-sidebar-header">
                <h3 class="wf-sidebar-title">فیلتر پیشرفته</h3>
                <button class="wf-sidebar-close" id="wf-close-filter">✕</button>
            </div>
            <div class="wf-sidebar-content" id="wf-filter-content">
                <!-- فیلترها به صورت پویا تولید می‌شوند -->
            </div>
            <div class="wf-sidebar-footer">
                <button class="wf-btn wf-btn-danger" id="wf-clear-filters">
                    🗑️ پاک کردن همه
                </button>
                <button class="wf-btn wf-btn-success" id="wf-apply-filters">
                    🔍 اعمال فیلتر
                </button>
            </div>
        </div>
        
        <!-- wrapper اصلی -->
        <div class="wf-main-wrapper">
            
            <!-- هدر هوشمند -->
            <div class="wf-smart-header">
                <div class="wf-user-info">
                    <h2>👋 خوش آمدید، <?php echo esc_html($user_info['name']); ?></h2>
                    <div class="wf-meta">
                        <div class="wf-meta-item">
                            <i>🏢</i>
                            <span><?php echo esc_html($user_info['department']); ?> / <?php echo esc_html($user_info['organization']); ?></span>
                        </div>
                        <div class="wf-meta-item">
                            <i>👑</i>
                            <span><?php echo esc_html($user_info['role_name']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="wf-period-info">
                    <div class="wf-date">
                        📅 دوره فعال: <?php echo esc_html($active_period['title']); ?>
                    </div>
                    <div class="wf-status">
                        🕒 امروز: <?php echo wf_get_persian_date(); ?>
                    </div>
                </div>
            </div>
            
            <!-- کارت‌های مانیتورینگ -->
            <div class="wf-monitoring-cards" id="wf-monitoring-cards">
                <!-- کارت‌های ثابت -->
                <div class="wf-card wf-card-essential">
                    <div class="wf-card-header">
                        <div class="wf-card-title">وضعیت پرسنل</div>
                        <div class="wf-card-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                            👥
                        </div>
                    </div>
                    <div class="wf-card-value" id="wf-total-personnel">۰</div>
                    <div class="wf-card-progress">
                        <div class="wf-card-progress-bar" id="wf-personnel-progress" style="width: 100%; background: #3b82f6;"></div>
                    </div>
                    <div class="wf-card-footer">
                        <small>کل پرسنل فعال</small>
                    </div>
                </div>
                
                <div class="wf-card wf-card-success">
                    <div class="wf-card-header">
                        <div class="wf-card-title">فیلدهای ضروری</div>
                        <div class="wf-card-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            ✅
                        </div>
                    </div>
                    <div class="wf-card-value" id="wf-required-percent">۰٪</div>
                    <div class="wf-card-progress">
                        <div class="wf-card-progress-bar" id="wf-required-progress" style="width: 0%; background: #10b981;"></div>
                    </div>
                    <div class="wf-card-footer">
                        <small>درصد تکمیل اطلاعات</small>
                    </div>
                </div>
                
                <div class="wf-card wf-card-critical">
                    <div class="wf-card-header">
                        <div class="wf-card-title">هشدار</div>
                        <div class="wf-card-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                            ⚠️
                        </div>
                    </div>
                    <div class="wf-card-value" id="wf-incomplete-count">۰</div>
                    <div class="wf-card-progress">
                        <div class="wf-card-progress-bar" id="wf-incomplete-progress" style="width: 0%; background: #ef4444;"></div>
                    </div>
                    <div class="wf-card-footer">
                        <small>پرسنل با اطلاعات ناقص</small>
                    </div>
                </div>
                
                <!-- کارت‌های داینامیک اینجا اضافه می‌شوند -->
                <div id="wf-dynamic-cards"></div>
            </div>
            
            <!-- نوار ابزار اقدامات -->
            <div class="wf-toolbar">
                <div class="wf-action-buttons">
                    <button class="wf-btn wf-btn-primary" id="wf-add-personnel">
                        ➕ افزودن پرسنل جدید
                    </button>
                    <button class="wf-btn wf-btn-danger" id="wf-delete-selected">
                        🗑️ حذف انتخاب شده‌ها
                    </button>
                    <button class="wf-btn wf-btn-success" id="wf-export-excel">
                        📤 خروجی Excel
                    </button>
                    <button class="wf-btn wf-btn-primary" id="wf-advanced-filter">
                        🔍 فیلتر پیشرفته
                    </button>
                </div>
                
                <div class="wf-display-options">
                    <select class="wf-form-input" id="wf-page-size" style="width: 120px;">
                        <option value="25">۲۵ رکورد در صفحه</option>
                        <option value="50">۵۰ رکورد در صفحه</option>
                        <option value="100">۱۰۰ رکورد در صفحه</option>
                    </select>
                </div>
            </div>
            
            <!-- جدول اصلی -->
            <div class="wf-table-container">
                <table class="wf-excel-table" id="wf-main-table">
                    <thead class="wf-table-header" id="wf-table-header">
                        <tr>
                            <th class="wf-checkbox-cell">
                                <input type="checkbox" class="wf-checkbox" id="wf-select-all">
                            </th>
                            <!-- سرستون‌ها به صورت پویا تولید می‌شوند -->
                            <?php foreach ($fields as $field): ?>
                            <?php
                            $field_class = '';
                            if ($field['required']) $field_class .= ' wf-required';
                            if ($field['locked']) $field_class .= ' wf-locked';
                            ?>
                            <th class="<?php echo esc_attr($field_class); ?>" data-field-id="<?php echo esc_attr($field['id']); ?>">
                                <div class="wf-header-content">
                                    <span><?php echo esc_html($field['title']); ?></span>
                                    <div class="wf-header-icons">
                                        <button class="wf-icon-btn wf-filter-btn" title="فیلتر">
                                            🔍
                                        </button>
                                        <button class="wf-icon-btn wf-card-btn" title="ساخت کارت">
                                            📊
                                        </button>
                                        <button class="wf-icon-btn wf-pin-btn" title="پین ستون">
                                            📌
                                        </button>
                                    </div>
                                </div>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="wf-table-body" id="wf-table-body">
                        <!-- داده‌ها به صورت پویا بارگذاری می‌شوند -->
                        <tr>
                            <td colspan="<?php echo count($fields) + 1; ?>" style="text-align: center; padding: 50px;">
                                در حال بارگذاری داده‌ها...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- صفحه‌بندی -->
            <div class="wf-pagination">
                <div class="wf-page-info">
                    نمایش <span id="wf-start-record">۰</span> - <span id="wf-end-record">۰</span> 
                    از <span id="wf-total-records">۰</span> رکورد
                </div>
                
                <div class="wf-page-buttons" id="wf-pagination-buttons">
                    <button class="wf-page-btn wf-page-prev">«</button>
                    <button class="wf-page-btn wf-active">۱</button>
                    <button class="wf-page-btn">۲</button>
                    <button class="wf-page-btn">۳</button>
                    <button class="wf-page-btn wf-page-next">»</button>
                </div>
                
                <div class="wf-page-size">
                    <select class="wf-form-input" id="wf-page-size-bottom" style="width: 140px;">
                        <option value="25">۲۵ رکورد در صفحه</option>
                        <option value="50">۵۰ رکورد در صفحه</option>
                        <option value="100">۱۰۰ رکورد در صفحه</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ======================== -->
    <!-- جاوااسکریپت -->
    <!-- ======================== -->
    <script>
    (function($) {
        'use strict';
        
        // داده‌های گلوبال
        window.wfData = {
            personnel: <?php echo json_encode($personnel_data, JSON_UNESCAPED_UNICODE); ?>,
            fields: <?php echo json_encode($fields, JSON_UNESCAPED_UNICODE); ?>,
            currentPage: 1,
            pageSize: 25,
            filters: {},
            selectedRows: [],
            currentEditIndex: -1,
            dynamicCards: []
        };
        
        // ثابت‌ها
        const WF_CONSTANTS = {
            MAX_DYNAMIC_CARDS: 6,
            DEBOUNCE_DELAY: 300,
            SAVE_TIMEOUT: 2000
        };
        
        /**
         * مقداردهی اولیه سیستم
         */
        function initWorkforcePanel() {
            // مخفی کردن لودینگ
            $('#wf-loading').fadeOut(300);
            
            // بارگذاری اولیه داده‌ها
            loadTableData();
            updateMonitoringCards();
            setupEventListeners();
            setupKeyboardShortcuts();
            
            // به‌روزرسانی کارت‌های ثابت
            updateStaticCards();
        }
        
        /**
         * بارگذاری داده‌های جدول
         */
        function loadTableData() {
            const startIndex = (wfData.currentPage - 1) * wfData.pageSize;
            const endIndex = Math.min(startIndex + wfData.pageSize, wfData.personnel.length);
            const pageData = wfData.personnel.slice(startIndex, endIndex);
            
            // پاک کردن محتوای قبلی
            $('#wf-table-body').empty();
            
            // تولید ردیف‌های جدید
            pageData.forEach((person, index) => {
                const rowIndex = startIndex + index;
                const rowClass = person.deleted ? 'wf-deleted' : '';
                const selectedClass = wfData.selectedRows.includes(rowIndex) ? 'wf-selected' : '';
                
                let rowHtml = `<tr class="${rowClass} ${selectedClass}" data-index="${rowIndex}">`;
                
                // سلول چک‌باکس
                const checked = wfData.selectedRows.includes(rowIndex) ? 'checked' : '';
                rowHtml += `
                    <td class="wf-checkbox-cell">
                        <input type="checkbox" class="wf-checkbox wf-row-checkbox" ${checked}>
                    </td>
                `;
                
                // سلول‌های داده
                wfData.fields.forEach(field => {
                    const value = person[field.name] || '';
                    const cellClass = field.locked ? 'wf-locked-cell' : '';
                    rowHtml += `
                        <td class="${cellClass}" data-field="${field.name}">
                            ${escapeHtml(value)}
                        </td>
                    `;
                });
                
                rowHtml += '</tr>';
                $('#wf-table-body').append(rowHtml);
            });
            
            // به‌روزرسانی اطلاعات صفحه
            updatePaginationInfo();
            
            // افزودن رویدادها به ردیف‌ها
            attachRowEvents();
        }
        
        /**
         * به‌روزرسانی کارت‌های مانیتورینگ
         */
        function updateMonitoringCards() {
            const total = wfData.personnel.length;
            const requiredFields = wfData.fields.filter(f => f.required).length;
            
            // محاسبه درصد تکمیل اطلاعات ضروری
            let completedCount = 0;
            wfData.personnel.forEach(person => {
                let personCompleted = true;
                wfData.fields.forEach(field => {
                    if (field.required && !person[field.name]) {
                        personCompleted = false;
                    }
                });
                if (personCompleted) completedCount++;
            });
            
            const completionPercent = total > 0 ? Math.round((completedCount / total) * 100) : 0;
            const incompleteCount = total - completedCount;
            
            // به‌روزرسانی کارت‌های ثابت
            $('#wf-total-personnel').text(total.toLocaleString('fa-IR'));
            $('#wf-required-percent').text(completionPercent + '%');
            $('#wf-required-progress').css('width', completionPercent + '%');
            $('#wf-incomplete-count').text(incompleteCount.toLocaleString('fa-IR'));
            $('#wf-incomplete-progress').css('width', ((incompleteCount / total) * 100) + '%');
            
            // به‌روزرسانی کارت‌های داینامیک
            updateDynamicCards();
        }
        
        /**
         * تنظیم رویدادها
         */
        function setupEventListeners() {
            // انتخاب همه
            $('#wf-select-all').on('change', function() {
                const isChecked = $(this).prop('checked');
                $('.wf-row-checkbox').prop('checked', isChecked).trigger('change');
            });
            
            // انتخاب ردیف
            $(document).on('change', '.wf-row-checkbox', function() {
                const row = $(this).closest('tr');
                const index = parseInt(row.data('index'));
                
                if ($(this).prop('checked')) {
                    if (!wfData.selectedRows.includes(index)) {
                        wfData.selectedRows.push(index);
                    }
                    row.addClass('wf-selected');
                } else {
                    wfData.selectedRows = wfData.selectedRows.filter(i => i !== index);
                    row.removeClass('wf-selected');
                }
                
                updateSelectionCount();
            });
            
            // دوبار کلیک برای ویرایش
            $(document).on('dblclick', '#wf-table-body td:not(.wf-checkbox-cell)', function() {
                const row = $(this).closest('tr');
                const index = parseInt(row.data('index'));
                openEditSidebar(index);
            });
            
            // افزودن پرسنل جدید
            $('#wf-add-personnel').on('click', function() {
                openAddPersonnelModal();
            });
            
            // حذف انتخاب شده‌ها
            $('#wf-delete-selected').on('click', function() {
                if (wfData.selectedRows.length === 0) {
                    showAlert('⚠️ لطفا حداقل یک ردیف را انتخاب کنید', 'warning');
                    return;
                }
                
                if (confirm(`آیا از حذف ${wfData.selectedRows.length} رکورد انتخاب شده اطمینان دارید؟`)) {
                    deleteSelectedRecords();
                }
            });
            
            // خروجی اکسل
            $('#wf-export-excel').on('click', function() {
                exportToExcel();
            });
            
            // فیلتر پیشرفته
            $('#wf-advanced-filter').on('click', function() {
                openFilterModal();
            });
            
            // تغییر اندازه صفحه
            $('#wf-page-size, #wf-page-size-bottom').on('change', function() {
                wfData.pageSize = parseInt($(this).val());
                wfData.currentPage = 1;
                loadTableData();
            });
            
            // پیمایش صفحه
            $(document).on('click', '.wf-page-btn:not(.wf-active)', function() {
                if ($(this).hasClass('wf-page-prev')) {
                    if (wfData.currentPage > 1) {
                        wfData.currentPage--;
                    }
                } else if ($(this).hasClass('wf-page-next')) {
                    const totalPages = Math.ceil(wfData.personnel.length / wfData.pageSize);
                    if (wfData.currentPage < totalPages) {
                        wfData.currentPage++;
                    }
                } else {
                    wfData.currentPage = parseInt($(this).text());
                }
                
                loadTableData();
                updatePaginationButtons();
            });
            
            // بستن فرم ویرایش
            $('#wf-close-edit').on('click', closeEditSidebar);
            
            // ذخیره تغییرات
            $('#wf-save-record').on('click', saveCurrentRecord);
            
            // پیمایش بین رکوردها
            $('#wf-prev-record').on('click', function() {
                navigateToRecord(-1);
            });
            
            $('#wf-next-record').on('click', function() {
                navigateToRecord(1);
            });
            
            // آیکن‌های سرستون
            $(document).on('click', '.wf-filter-btn', function() {
                const fieldId = $(this).closest('th').data('field-id');
                openColumnFilter(fieldId);
            });
            
            $(document).on('click', '.wf-card-btn', function() {
                const fieldId = $(this).closest('th').data('field-id');
                createDynamicCard(fieldId);
            });
            
            $(document).on('click', '.wf-pin-btn', function() {
                $(this).toggleClass('wf-active');
                const fieldId = $(this).closest('th').data('field-id');
                togglePinColumn(fieldId);
            });
            
            // بستن مودال‌ها با کلیک روی overlay
            $('#wf-overlay').on('click', function() {
                closeEditSidebar();
                closeFilterModal();
            });
            
            // پاک کردن فیلترها
            $('#wf-clear-filters').on('click', function() {
                clearAllFilters();
            });
            
            // اعمال فیلترها
            $('#wf-apply-filters').on('click', function() {
                applyFilters();
            });
            
            // بستن مودال فیلتر
            $('#wf-close-filter').on('click', closeFilterModal);
        }
        
        /**
         * تنظیم شورتکات‌های صفحه‌کلید
         */
        function setupKeyboardShortcuts() {
            $(document).on('keydown', function(e) {
                // جلوگیری از اجرا در inputها
                if ($(e.target).is('input, textarea, select')) {
                    return;
                }
                
                // Ctrl + S: ذخیره
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    if ($('#wf-edit-sidebar').hasClass('wf-active')) {
                        saveCurrentRecord();
                    }
                }
                
                // Ctrl + F: جستجو
                if (e.ctrlKey && e.key === 'f') {
                    e.preventDefault();
                    $('#wf-advanced-filter').click();
                }
                
                // Ctrl + E: خروجی اکسل
                if (e.ctrlKey && e.key === 'e') {
                    e.preventDefault();
                    exportToExcel();
                }
                
                // Escape: بستن مودال‌ها
                if (e.key === 'Escape') {
                    closeEditSidebar();
                    closeFilterModal();
                }
                
                // فلش‌ها برای پیمایش
                if ($('#wf-edit-sidebar').hasClass('wf-active')) {
                    if (e.key === 'ArrowLeft') {
                        navigateToRecord(-1);
                    } else if (e.key === 'ArrowRight') {
                        navigateToRecord(1);
                    }
                }
            });
        }
        
        /**
         * باز کردن فرم ویرایش
         */
        function openEditSidebar(index) {
            wfData.currentEditIndex = index;
            const person = wfData.personnel[index];
            
            // به‌روزرسانی عنوان
            $('#wf-edit-title').html(`ویرایش پرسنل: <strong>${escapeHtml(person.name || 'بدون نام')}</strong>`);
            
            // تولید فرم
            let formHtml = '';
            wfData.fields.forEach(field => {
                const value = person[field.name] || '';
                const required = field.required ? 'required' : '';
                const locked = field.locked ? 'readonly' : '';
                const inputClass = field.locked ? 'wf-locked' : '';
                
                formHtml += `
                    <div class="wf-form-group">
                        <label class="wf-form-label">
                            <i>📝</i>
                            ${escapeHtml(field.title)}
                            ${field.required ? '<span style="color: #ef4444;">*</span>' : ''}
                        </label>
                        <input type="text" 
                               class="wf-form-input ${inputClass}"
                               data-field="${field.name}"
                               value="${escapeHtml(value)}"
                               ${required}
                               ${locked}
                               placeholder="${field.required ? 'الزامی' : 'اختیاری'}">
                    </div>
                `;
            });
            
            $('#wf-edit-content').html(formHtml);
            
            // نمایش فرم
            $('#wf-edit-sidebar').addClass('wf-active');
            $('#wf-overlay').addClass('wf-active');
            
            // فوکوس روی اولین فیلد غیرقفل
            setTimeout(() => {
                $('#wf-edit-content .wf-form-input:not(.wf-locked)').first().focus();
            }, 300);
        }
        
        /**
         * بستن فرم ویرایش
         */
        function closeEditSidebar() {
            $('#wf-edit-sidebar').removeClass('wf-active');
            $('#wf-overlay').removeClass('wf-active');
            wfData.currentEditIndex = -1;
        }
        
        /**
         * ذخیره رکورد جاری
         */
        function saveCurrentRecord() {
            if (wfData.currentEditIndex === -1) return;
            
            const person = wfData.personnel[wfData.currentEditIndex];
            let hasError = false;
            
            // جمع‌آوری داده‌ها از فرم
            $('#wf-edit-content .wf-form-input').each(function() {
                const fieldName = $(this).data('field');
                const value = $(this).val().trim();
                const field = wfData.fields.find(f => f.name === fieldName);
                
                // اعتبارسنجی فیلدهای الزامی
                if (field && field.required && !value) {
                    $(this).addClass('wf-error');
                    showAlert(`فیلد "${field.title}" الزامی است`, 'error');
                    hasError = true;
                    return false;
                }
                
                $(this).removeClass('wf-error');
                person[fieldName] = value;
            });
            
            if (hasError) return;
            
            // نمایش موفقیت
            showAlert('✅ تغییرات با موفقیت ذخیره شد', 'success');
            
            // به‌روزرسانی جدول
            updateRowInTable(wfData.currentEditIndex, person);
            
            // بستن فرم بعد از 1 ثانیه
            setTimeout(closeEditSidebar, 1000);
        }
        
        /**
         * پیمایش بین رکوردها
         */
        function navigateToRecord(direction) {
            if (wfData.currentEditIndex === -1) return;
            
            let newIndex = wfData.currentEditIndex + direction;
            
            // محدودیت‌های ابتدا و انتها
            if (newIndex < 0) newIndex = wfData.personnel.length - 1;
            if (newIndex >= wfData.personnel.length) newIndex = 0;
            
            openEditSidebar(newIndex);
        }
        
        /**
         * ایجاد کارت داینامیک
         */
        function createDynamicCard(fieldId) {
            const field = wfData.fields.find(f => f.id == fieldId);
            if (!field) return;
            
            // بررسی حداکثر تعداد کارت
            if (wfData.dynamicCards.length >= WF_CONSTANTS.MAX_DYNAMIC_CARDS) {
                showAlert(`حداکثر ${WF_CONSTANTS.MAX_DYNAMIC_CARDS} کارت مجاز است`, 'warning');
                return;
            }
            
            // بررسی تکراری نبودن
            if (wfData.dynamicCards.some(card => card.fieldId == fieldId)) {
                showAlert('کارت برای این ستون قبلاً ایجاد شده است', 'warning');
                return;
            }
            
            // محاسبه آمار
            const values = wfData.personnel.map(p => p[field.name]).filter(v => v);
            const sum = values.reduce((a, b) => parseFloat(a) || 0 + parseFloat(b) || 0, 0);
            const avg = values.length > 0 ? sum / values.length : 0;
            const count = values.length;
            
            // ایجاد کارت
            const cardId = 'wf-card-' + Date.now();
            const cardHtml = `
                <div class="wf-card wf-dynamic-card" id="${cardId}">
                    <div class="wf-card-header">
                        <div class="wf-card-title">${escapeHtml(field.title)}</div>
                        <div class="wf-card-actions">
                            <button class="wf-icon-btn wf-close-card" style="width: 24px; height: 24px; font-size: 12px;">
                                ✕
                            </button>
                        </div>
                    </div>
                    <div class="wf-card-value">${formatNumber(sum)}</div>
                    <div class="wf-card-progress">
                        <div class="wf-card-progress-bar" style="width: 100%; background: #8b5cf6;"></div>
                    </div>
                    <div class="wf-card-footer">
                        <small>جمع: ${formatNumber(sum)} | میانگین: ${formatNumber(avg)}</small>
                    </div>
                </div>
            `;
            
            // افزودن به DOM
            $('#wf-dynamic-cards').append(cardHtml);
            
            // ذخیره در آرایه
            wfData.dynamicCards.push({
                id: cardId,
                fieldId: fieldId,
                fieldName: field.name,
                title: field.title
            });
            
            // رویداد بستن کارت
            $(`#${cardId} .wf-close-card`).on('click', function() {
                $(this).closest('.wf-dynamic-card').remove();
                wfData.dynamicCards = wfData.dynamicCards.filter(c => c.id !== cardId);
            });
        }
        
        /**
         * به‌روزرسانی کارت‌های داینامیک
         */
        function updateDynamicCards() {
            wfData.dynamicCards.forEach(card => {
                const field = wfData.fields.find(f => f.name === card.fieldName);
                if (!field) return;
                
                const values = wfData.personnel.map(p => p[field.name]).filter(v => v);
                const sum = values.reduce((a, b) => (parseFloat(a) || 0) + (parseFloat(b) || 0), 0);
                
                $(`#${card.id} .wf-card-value`).text(formatNumber(sum));
            });
        }
        
        /**
         * باز کردن مودال فیلتر
         */
        function openFilterModal() {
            let filterHtml = '';
            
            // فیلتر بر اساس اداره (فقط برای مدیر سازمان)
            if (wfData.userRole === 'organization_manager') {
                filterHtml += `
                    <div class="wf-form-group">
                        <label class="wf-form-label">🏢 فیلتر بر اساس اداره</label>
                        <div class="wf-checkbox-group">
                            <label><input type="checkbox" value="all" checked> همه ادارات</label>
                            <!-- ادارات به صورت پویا -->
                        </div>
                    </div>
                `;
            }
            
            // فیلتر بر اساس وضعیت تکمیل
            filterHtml += `
                <div class="wf-form-group">
                    <label class="wf-form-label">✅ وضعیت تکمیل اطلاعات</label>
                    <div class="wf-radio-group">
                        <label><input type="radio" name="completion" value="all" checked> همه</label>
                        <label><input type="radio" name="completion" value="complete"> تکمیل شده</label>
                        <label><input type="radio" name="completion" value="incomplete"> ناقص</label>
                    </div>
                </div>
            `;
            
            // فیلترهای ستونی
            wfData.fields.forEach(field => {
                if (field.filterable) {
                    const uniqueValues = [...new Set(wfData.personnel.map(p => p[field.name]).filter(v => v))];
                    if (uniqueValues.length > 1) {
                        filterHtml += `
                            <div class="wf-form-group">
                                <label class="wf-form-label">${escapeHtml(field.title)}</label>
                                <select class="wf-form-input" multiple data-field="${field.name}">
                                    ${uniqueValues.map(v => `<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`).join('')}
                                </select>
                            </div>
                        `;
                    }
                }
            });
            
            $('#wf-filter-content').html(filterHtml);
            
            // نمایش مودال
            $('#wf-filter-modal').addClass('wf-active');
            $('#wf-overlay').addClass('wf-active');
        }
        
        /**
         * بستن مودال فیلتر
         */
        function closeFilterModal() {
            $('#wf-filter-modal').removeClass('wf-active');
            $('#wf-overlay').removeClass('wf-active');
        }
        
        /**
         * اعمال فیلترها
         */
        function applyFilters() {
            // جمع‌آوری فیلترها
            wfData.filters = {};
            
            // فیلتر وضعیت تکمیل
            const completion = $('input[name="completion"]:checked').val();
            if (completion !== 'all') {
                wfData.filters.completion = completion;
            }
            
            // فیلترهای ستونی
            $('select[data-field]').each(function() {
                const fieldName = $(this).data('field');
                const selectedValues = $(this).val();
                if (selectedValues && selectedValues.length > 0) {
                    wfData.filters[fieldName] = selectedValues;
                }
            });
            
            // اعمال فیلترها
            applyFiltersToData();
            
            // بستن مودال
            closeFilterModal();
            
            // نمایش پیام
            showAlert('✅ فیلترها اعمال شدند', 'success');
        }
        
        /**
         * پاک کردن همه فیلترها
         */
        function clearAllFilters() {
            wfData.filters = {};
            loadTableData();
            updateMonitoringCards();
            closeFilterModal();
            showAlert('🗑️ همه فیلترها پاک شدند', 'info');
        }
        
        /**
         * اعمال فیلترها روی داده‌ها
         */
        function applyFiltersToData() {
            // این تابع در فایل کامل پیاده‌سازی می‌شود
            console.log('Applying filters:', wfData.filters);
        }
        
        /**
         * حذف رکوردهای انتخاب شده
         */
        function deleteSelectedRecords() {
            // این تابع در فایل کامل پیاده‌سازی می‌شود
            console.log('Deleting records:', wfData.selectedRows);
        }
        
        /**
         * خروجی اکسل
         */
        function exportToExcel() {
            // این تابع در فایل کامل پیاده‌سازی می‌شود
            console.log('Exporting to Excel');
        }
        
        /**
         * به‌روزرسانی اطلاعات صفحه‌بندی
         */
        function updatePaginationInfo() {
            const total = wfData.personnel.length;
            const start = (wfData.currentPage - 1) * wfData.pageSize + 1;
            const end = Math.min(start + wfData.pageSize - 1, total);
            
            $('#wf-start-record').text(start.toLocaleString('fa-IR'));
            $('#wf-end-record').text(end.toLocaleString('fa-IR'));
            $('#wf-total-records').text(total.toLocaleString('fa-IR'));
        }
        
        /**
         * به‌روزرساری دکمه‌های صفحه‌بندی
         */
        function updatePaginationButtons() {
            const totalPages = Math.ceil(wfData.personnel.length / wfData.pageSize);
            let buttonsHtml = '';
            
            // دکمه قبلی
            buttonsHtml += `<button class="wf-page-btn wf-page-prev" ${wfData.currentPage === 1 ? 'disabled' : ''}>«</button>`;
            
            // دکمه‌های صفحات
            for (let i = 1; i <= Math.min(totalPages, 5); i++) {
                const activeClass = i === wfData.currentPage ? 'wf-active' : '';
                buttonsHtml += `<button class="wf-page-btn ${activeClass}">${i}</button>`;
            }
            
            // دکمه بعدی
            buttonsHtml += `<button class="wf-page-btn wf-page-next" ${wfData.currentPage === totalPages ? 'disabled' : ''}>»</button>`;
            
            $('#wf-pagination-buttons').html(buttonsHtml);
        }
        
        /**
         * نمایش پیام
         */
        function showAlert(message, type = 'info') {
            // حذف آلرت قبلی
            $('.wf-alert').remove();
            
            const colors = {
                success: '#10b981',
                error: '#ef4444',
                warning: '#f59e0b',
                info: '#3b82f6'
            };
            
            const alertHtml = `
                <div class="wf-alert" style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${colors[type]};
                    color: white;
                    padding: 15px 25px;
                    border-radius: 10px;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                    z-index: 9999;
                    animation: wf-slideIn 0.3s ease;
                ">
                    ${message}
                </div>
            `;
            
            $('body').append(alertHtml);
            
            // حذف خودکار بعد از 3 ثانیه
            setTimeout(() => {
                $('.wf-alert').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
        
        /**
         * فرار از HTML
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        /**
         * فرمت اعداد
         */
        function formatNumber(num) {
            return new Intl.NumberFormat('fa-IR').format(num);
        }
        
        /**
         * به‌روزرسانی ردیف در جدول
         */
        function updateRowInTable(index, person) {
            const row = $(`tr[data-index="${index}"]`);
            wfData.fields.forEach(field => {
                const cell = row.find(`td[data-field="${field.name}"]`);
                cell.text(person[field.name] || '');
            });
        }
        
        /**
         * افزودن رویداد به ردیف‌ها
         */
        function attachRowEvents() {
            $('.wf-row-checkbox').on('change', function() {
                const row = $(this).closest('tr');
                const index = parseInt(row.data('index'));
                
                if ($(this).prop('checked')) {
                    if (!wfData.selectedRows.includes(index)) {
                        wfData.selectedRows.push(index);
                    }
                    row.addClass('wf-selected');
                } else {
                    wfData.selectedRows = wfData.selectedRows.filter(i => i !== index);
                    row.removeClass('wf-selected');
                }
                
                updateSelectionCount();
            });
        }
        
        /**
         * به‌روزرسانی تعداد انتخاب‌ها
         */
        function updateSelectionCount() {
            const count = wfData.selectedRows.length;
            if (count > 0) {
                $('#wf-delete-selected').html(`🗑️ حذف (${count})`);
            } else {
                $('#wf-delete-selected').html('🗑️ حذف انتخاب شده‌ها');
            }
        }
        
        /**
         * به‌روزرسانی کارت‌های ثابت
         */
        function updateStaticCards() {
            // محاسبات اضافی برای کارت‌های ثابت
            const numericFields = wfData.fields.filter(f => 
                f.type === 'number' || f.type === 'decimal'
            );
            
            if (numericFields.length > 0) {
                // ایجاد کارت برای اولین فیلد عددی
                setTimeout(() => {
                    createDynamicCard(numericFields[0].id);
                }, 1000);
            }
        }
        
        /**
         * باز کردن مودال افزودن پرسنل
         */
        function openAddPersonnelModal() {
            // این تابع در فایل کامل پیاده‌سازی می‌شود
            console.log('Opening add personnel modal');
        }
        
        /**
         * باز کردن فیلتر ستونی
         */
        function openColumnFilter(fieldId) {
            // این تابع در فایل کامل پیاده‌سازی می‌شود
            console.log('Opening column filter for:', fieldId);
        }
        
        /**
         * پین کردن ستون
         */
        function togglePinColumn(fieldId) {
            // این تابع در فایل کامل پیاده‌سازی می‌شود
            console.log('Toggling pin for column:', fieldId);
        }
        
        // راه‌اندازی سیستم
        $(document).ready(function() {
            initWorkforcePanel();
        });
        
    })(jQuery);
    </script>
    
    <?php
    return ob_get_clean();
}

/**
 * فرم لاگین
 */
function wf_render_login_form() {
    ob_start();
    ?>
    <div class="wf-login-container">
        <style>
        .wf-login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }
        
        .wf-login-title {
            text-align: center;
            color: #1e40af;
            margin-bottom: 30px;
            font-size: 24px;
        }
        
        .wf-login-form .wf-form-group {
            margin-bottom: 20px;
        }
        
        .wf-login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .wf-login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }
        </style>
        
        <h2 class="wf-login-title">🔐 ورود به پنل مدیران</h2>
        
        <form class="wf-login-form" method="post">
            <?php wp_nonce_field('wf_manager_login', 'wf_login_nonce'); ?>
            
            <div class="wf-form-group">
                <label>نام کاربری</label>
                <input type="text" name="wf_username" class="wf-form-input" required>
            </div>
            
            <div class="wf-form-group">
                <label>رمز عبور</label>
                <input type="password" name="wf_password" class="wf-form-input" required>
            </div>
            
            <button type="submit" class="wf-login-btn">ورود به پنل</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * بررسی دسترسی مدیر
 */
function wf_check_manager_access($user_id, $panel_type) {
    $user_role = wf_get_user_role($user_id);
    
    if ($panel_type === 'department' && $user_role === 'department_manager') {
        return true;
    }
    
    if ($panel_type === 'organization' && $user_role === 'organization_manager') {
        return true;
    }
    
    // ادمین می‌تواند به هر دو پنل دسترسی داشته باشد
    if (current_user_can('manage_options')) {
        return true;
    }
    
    return false;
}

/**
 * دریافت اطلاعات مدیر
 */
function wf_get_manager_info($user_id, $panel_type) {
    global $wpdb;
    
    $user = get_userdata($user_id);
    $info = array(
        'name' => $user->display_name,
        'role' => wf_get_user_role($user_id),
        'role_name' => '',
        'department' => '',
        'organization' => 'سازمان بنی اسد'
    );
    
    // تعیین نام نقش
    switch ($info['role']) {
        case 'department_manager':
            $info['role_name'] = 'مدیر اداره';
            break;
        case 'organization_manager':
            $info['role_name'] = 'مدیر سازمان';
            break;
        case 'admin':
            $info['role_name'] = 'مدیر کل سیستم';
            break;
    }
    
    // دریافت اطلاعات اداره برای مدیران اداره
    if ($info['role'] === 'department_manager') {
        $department = $wpdb->get_row($wpdb->prepare(
            "SELECT name, color FROM {$wpdb->prefix}wf_departments 
             WHERE manager_id = %d",
            $user_id
        ));
        
        if ($department) {
            $info['department'] = $department->name;
        }
    }
    
    return $info;
}

/**
 * بارگذاری داده‌های پرسنل
 */
function wf_load_personnel_data($user_id, $panel_type, $period_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wf_personnel';
    $departments_table = $wpdb->prefix . 'wf_departments';
    
    $query = "SELECT p.*, d.name as department_name, d.color as department_color 
              FROM {$table_name} p
              LEFT JOIN {$departments_table} d ON p.department_id = d.id
              WHERE p.period_id = %d";
    
    $params = array($period_id);
    
    // محدود کردن بر اساس اداره برای مدیران اداره
    if ($panel_type === 'department') {
        $department_id = wf_get_user_department($user_id);
        if ($department_id) {
            $query .= " AND p.department_id = %d";
            $params[] = $department_id;
        }
    }
    
    $query .= " AND p.status = 'active' 
                ORDER BY p.created_at DESC 
                LIMIT 1000";
    
    $results = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
    
    return $results ?: array();
}

/**
 * دریافت تمام فیلدها
 */
function wf_get_all_fields() {
    global $wpdb;
    
    $results = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}wf_fields 
         WHERE status = 'active' 
         ORDER BY field_order ASC",
        ARRAY_A
    );
    
    return $results ?: array(
        array(
            'id' => 1,
            'name' => 'national_id',
            'title' => 'کد ملی',
            'type' => 'text',
            'required' => true,
            'locked' => true,
            'filterable' => true
        ),
        array(
            'id' => 2,
            'name' => 'name',
            'title' => 'نام',
            'type' => 'text',
            'required' => true,
            'locked' => false,
            'filterable' => true
        ),
        array(
            'id' => 3,
            'name' => 'last_name',
            'title' => 'نام خانوادگی',
            'type' => 'text',
            'required' => true,
            'locked' => false,
            'filterable' => true
        ),
        array(
            'id' => 4,
            'name' => 'department',
            'title' => 'اداره',
            'type' => 'text',
            'required' => true,
            'locked' => true,
            'filterable' => true
        ),
        array(
            'id' => 5,
            'name' => 'salary',
            'title' => 'حقوق',
            'type' => 'decimal',
            'required' => false,
            'locked' => false,
            'filterable' => true
        )
    );
}

/**
 * دریافت دوره فعال
 */
function wf_get_active_period() {
    global $wpdb;
    
    $result = $wpdb->get_row(
        "SELECT * FROM {$wpdb->prefix}wf_periods 
         WHERE status = 'active' 
         ORDER BY start_date DESC 
         LIMIT 1",
        ARRAY_A
    );
    
    return $result ?: array(
        'id' => 1,
        'title' => 'بهمن ۱۴۰۳',
        'start_date' => '2025-01-21',
        'end_date' => '2025-02-19',
        'status' => 'active'
    );
}

/**
 * دریافت اداره کاربر
 */
function wf_get_user_department($user_id) {
    global $wpdb;
    
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}wf_departments 
         WHERE manager_id = %d",
        $user_id
    ));
    
    return $result;
}

/**
 * نمایش پیام عدم دسترسی
 */
function wf_render_access_denied() {
    ob_start();
    ?>
    <div class="wf-access-denied">
        <style>
        .wf-access-denied {
            text-align: center;
            padding: 100px 20px;
        }
        
        .wf-access-icon {
            font-size: 80px;
            margin-bottom: 20px;
            color: #ef4444;
        }
        
        .wf-access-title {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .wf-access-message {
            color: #6b7280;
            margin-bottom: 30px;
        }
        </style>
        
        <div class="wf-access-icon">🚫</div>
        <h2 class="wf-access-title">دسترسی غیرمجاز</h2>
        <p class="wf-access-message">
            شما مجوز دسترسی به این بخش را ندارید.
        </p>
        <a href="<?php echo home_url(); ?>" class="wf-btn wf-btn-primary">
            بازگشت به صفحه اصلی
        </a>
    </div>
    <?php
    return ob_get_clean();
}

// پایان فایل
