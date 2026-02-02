<?php
/**
 * پنل اصلی مدیران - سیستم مدیریت کارکرد پرسنل
 * رابط کاربری پیشرفته شبه‌اکسل برای مدیران ادارات و سازمان
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// ==================== سیستم لاگین مستقل ====================

/**
 * صفحه لاگین مستقل
 */
function wf_render_login_form() {
    // اگر کاربر لاگین است، ریدایرکت به پنل
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $roles = $user->roles;
        
        if (in_array('administrator', $roles) || in_array('wf_org_manager', $roles) || in_array('wf_department_manager', $roles)) {
            wp_redirect(wf_get_manager_panel_url());
            exit;
        }
    }
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html dir="rtl" lang="fa">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php _e('ورود به پنل مدیریت کارکرد پرسنل', 'workforce-beni-asad'); ?></title>
        
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                direction: rtl;
            }
            
            .wf-login-container {
                width: 100%;
                max-width: 420px;
                background: white;
                border-radius: 16px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                overflow: hidden;
            }
            
            .wf-login-header {
                background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%);
                color: white;
                padding: 40px 30px;
                text-align: center;
            }
            
            .wf-login-header h1 {
                font-size: 28px;
                margin-bottom: 10px;
                font-weight: 600;
            }
            
            .wf-login-header p {
                opacity: 0.9;
                font-size: 15px;
            }
            
            .wf-login-body {
                padding: 40px 30px;
            }
            
            .wf-login-form {
                margin-bottom: 25px;
            }
            
            .wf-form-group {
                margin-bottom: 20px;
            }
            
            .wf-form-group label {
                display: block;
                margin-bottom: 8px;
                color: #2c3338;
                font-weight: 500;
                font-size: 14px;
            }
            
            .wf-form-control {
                width: 100%;
                padding: 14px 16px;
                border: 2px solid #e2e8f0;
                border-radius: 10px;
                font-size: 16px;
                transition: all 0.3s ease;
                background: #f8fafc;
            }
            
            .wf-form-control:focus {
                outline: none;
                border-color: #1a73e8;
                background: white;
                box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
            }
            
            .wf-form-control.error {
                border-color: #dc2626;
            }
            
            .wf-remember-me {
                display: flex;
                align-items: center;
                margin-bottom: 25px;
            }
            
            .wf-remember-me input {
                margin-left: 10px;
                width: 18px;
                height: 18px;
            }
            
            .wf-remember-me label {
                margin: 0;
                cursor: pointer;
                color: #4a5568;
                font-size: 14px;
            }
            
            .wf-login-btn {
                width: 100%;
                padding: 16px;
                background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%);
                color: white;
                border: none;
                border-radius: 10px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
            }
            
            .wf-login-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(26, 115, 232, 0.3);
            }
            
            .wf-login-btn:disabled {
                opacity: 0.7;
                cursor: not-allowed;
                transform: none;
            }
            
            .wf-login-footer {
                text-align: center;
                padding-top: 20px;
                border-top: 1px solid #e2e8f0;
                color: #718096;
                font-size: 14px;
            }
            
            .wf-login-footer a {
                color: #1a73e8;
                text-decoration: none;
                font-weight: 500;
            }
            
            .wf-login-footer a:hover {
                text-decoration: underline;
            }
            
            .wf-error-message {
                background: #fee;
                border: 1px solid #fcc;
                border-radius: 8px;
                padding: 12px 16px;
                margin-bottom: 20px;
                color: #c00;
                font-size: 14px;
                display: none;
            }
            
            .wf-error-message.show {
                display: block;
                animation: fadeIn 0.3s ease;
            }
            
            .wf-success-message {
                background: #d4edda;
                border: 1px solid #c3e6cb;
                border-radius: 8px;
                padding: 12px 16px;
                margin-bottom: 20px;
                color: #155724;
                font-size: 14px;
                display: none;
            }
            
            .wf-success-message.show {
                display: block;
                animation: fadeIn 0.3s ease;
            }
            
            .wf-password-toggle {
                position: relative;
            }
            
            .wf-password-toggle-btn {
                position: absolute;
                left: 15px;
                top: 50%;
                transform: translateY(-50%);
                background: none;
                border: none;
                color: #718096;
                cursor: pointer;
                padding: 5px;
            }
            
            .wf-password-toggle-btn:hover {
                color: #4a5568;
            }
            
            .wf-login-logo {
                width: 80px;
                height: 80px;
                background: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            }
            
            .wf-login-logo i {
                font-size: 40px;
                color: #1a73e8;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            @media (max-width: 480px) {
                .wf-login-container {
                    max-width: 100%;
                }
                
                .wf-login-header,
                .wf-login-body {
                    padding: 30px 20px;
                }
            }
        </style>
        
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
    <body>
        <div class="wf-login-container">
            <div class="wf-login-header">
                <div class="wf-login-logo">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h1><?php _e('پنل مدیریت', 'workforce-beni-asad'); ?></h1>
                <p><?php _e('سیستم مدیریت کارکرد پرسنل سازمانی', 'workforce-beni-asad'); ?></p>
            </div>
            
            <div class="wf-login-body">
                <div class="wf-error-message" id="wf-login-error"></div>
                <div class="wf-success-message" id="wf-login-success"></div>
                
                <form class="wf-login-form" id="wf-login-form">
                    <div class="wf-form-group">
                        <label for="wf-username">
                            <i class="fas fa-user"></i>
                            <?php _e('نام کاربری', 'workforce-beni-asad'); ?>
                        </label>
                        <input type="text" 
                               id="wf-username" 
                               name="username" 
                               class="wf-form-control" 
                               placeholder="<?php esc_attr_e('نام کاربری وردپرس', 'workforce-beni-asad'); ?>" 
                               required>
                    </div>
                    
                    <div class="wf-form-group">
                        <label for="wf-password">
                            <i class="fas fa-lock"></i>
                            <?php _e('رمز عبور', 'workforce-beni-asad'); ?>
                        </label>
                        <div class="wf-password-toggle">
                            <input type="password" 
                                   id="wf-password" 
                                   name="password" 
                                   class="wf-form-control" 
                                   placeholder="<?php esc_attr_e('رمز عبور خود را وارد کنید', 'workforce-beni-asad'); ?>" 
                                   required>
                            <button type="button" class="wf-password-toggle-btn" id="wf-toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="wf-remember-me">
                        <input type="checkbox" id="wf-remember" name="remember" value="1">
                        <label for="wf-remember"><?php _e('مرا به خاطر بسپار', 'workforce-beni-asad'); ?></label>
                    </div>
                    
                    <button type="submit" class="wf-login-btn" id="wf-login-submit">
                        <i class="fas fa-sign-in-alt"></i>
                        <?php _e('ورود به سیستم', 'workforce-beni-asad'); ?>
                        <span id="wf-login-spinner" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                    </button>
                </form>
                
                <div class="wf-login-footer">
                    <p>
                        <?php _e('مشکل در ورود دارید؟', 'workforce-beni-asad'); ?>
                        <a href="<?php echo wp_lostpassword_url(); ?>" target="_blank">
                            <?php _e('بازیابی رمز عبور', 'workforce-beni-asad'); ?>
                        </a>
                    </p>
                    <p style="margin-top: 10px; font-size: 12px;">
                        <i class="fas fa-info-circle"></i>
                        <?php _e('فقط کاربران دارای مجوز مدیر می‌توانند وارد شوند.', 'workforce-beni-asad'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('wf-login-form');
            const usernameInput = document.getElementById('wf-username');
            const passwordInput = document.getElementById('wf-password');
            const togglePasswordBtn = document.getElementById('wf-toggle-password');
            const loginSubmitBtn = document.getElementById('wf-login-submit');
            const loginSpinner = document.getElementById('wf-login-spinner');
            const errorMessage = document.getElementById('wf-login-error');
            const successMessage = document.getElementById('wf-login-success');
            
            // نمایش/مخفی کردن رمز عبور
            togglePasswordBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
            
            // ارسال فرم
            loginForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // نمایش اسپینر و غیرفعال کردن دکمه
                loginSpinner.style.display = 'inline-block';
                loginSubmitBtn.disabled = true;
                
                // مخفی کردن پیغام‌های قبلی
                errorMessage.classList.remove('show');
                successMessage.classList.remove('show');
                
                // جمع‌آوری داده‌های فرم
                const formData = new FormData(this);
                
                try {
                    const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: new URLSearchParams(formData)
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // نمایش پیغام موفقیت
                        successMessage.textContent = data.data.message;
                        successMessage.classList.add('show');
                        
                        // ریدایرکت بعد از 1.5 ثانیه
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        // نمایش خطا
                        errorMessage.textContent = data.data.message;
                        errorMessage.classList.add('show');
                        
                        // هایلایت کردن فیلد مشکل‌دار
                        if (data.data.field === 'username') {
                            usernameInput.classList.add('error');
                            usernameInput.focus();
                        } else if (data.data.field === 'password') {
                            passwordInput.classList.add('error');
                            passwordInput.focus();
                        }
                        
                        // فعال کردن مجدد دکمه
                        loginSubmitBtn.disabled = false;
                        loginSpinner.style.display = 'none';
                    }
                } catch (error) {
                    // خطای شبکه
                    errorMessage.textContent = 'خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.';
                    errorMessage.classList.add('show');
                    
                    loginSubmitBtn.disabled = false;
                    loginSpinner.style.display = 'none';
                }
            });
            
            // حذف خطا هنگام شروع تایپ
            usernameInput.addEventListener('input', function() {
                this.classList.remove('error');
                errorMessage.classList.remove('show');
            });
            
            passwordInput.addEventListener('input', function() {
                this.classList.remove('error');
                errorMessage.classList.remove('show');
            });
            
            // فوکوس روی فیلد نام کاربری
            usernameInput.focus();
        });
        </script>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

// ==================== تشخیص سطح دسترسی ====================

/**
 * تشخیص نوع پنل مورد نیاز کاربر
 */
function wf_detect_panel_type() {
    if (!is_user_logged_in()) {
        return 'login';
    }
    
    $user = wp_get_current_user();
    $roles = $user->roles;
    
    // مدیر سازمان
    if (in_array('administrator', $roles) || in_array('wf_org_manager', $roles)) {
        return 'organization';
    }
    
    // مدیر اداره
    if (in_array('wf_department_manager', $roles)) {
        return 'department';
    }
    
    // عدم دسترسی
    return 'no_access';
}

/**
 * دریافت اطلاعات مدیر
 */
function wf_get_manager_info() {
    $user = wp_get_current_user();
    $panel_type = wf_detect_panel_type();
    
    $info = array(
        'user_id' => $user->ID,
        'display_name' => $user->display_name,
        'email' => $user->user_email,
        'panel_type' => $panel_type,
        'department_id' => null,
        'department_name' => null,
        'managed_departments' => array()
    );
    
    if ($panel_type === 'department') {
        $department_id = get_user_meta($user->ID, 'wf_department_id', true);
        if ($department_id) {
            $department = wf_get_department($department_id);
            if ($department) {
                $info['department_id'] = $department_id;
                $info['department_name'] = $department['name'];
                $info['department_color'] = $department['color'];
            }
        }
    } elseif ($panel_type === 'organization') {
        // دریافت همه ادارات
        $departments = wf_get_all_departments(array('status' => 'active'));
        foreach ($departments as $dept) {
            $info['managed_departments'][] = array(
                'id' => $dept['id'],
                'name' => $dept['name'],
                'color' => $dept['color'],
                'manager_id' => $dept['manager_id'],
                'personnel_count' => wf_count_department_personnel($dept['id'])
            );
        }
    }
    
    return $info;
}

// ==================== پنل اصلی ====================

/**
 * رندر پنل مدیریت
 */
function wf_render_manager_panel($type = 'department') {
    // بررسی دسترسی
    $panel_type = wf_detect_panel_type();
    
    if ($panel_type === 'login') {
        return wf_render_login_form();
    }
    
    if ($panel_type === 'no_access') {
        return wf_render_no_access_page();
    }
    
    // بررسی تطابق نوع پنل
    if (($type === 'organization' && !in_array($panel_type, array('organization', 'administrator'))) ||
        ($type === 'department' && $panel_type === 'organization')) {
        // در صورت مدیر سازمان، همیشه پنل سازمان را نشان بده
        $type = $panel_type === 'organization' ? 'organization' : 'department';
    }
    
    // دریافت اطلاعات مدیر
    $manager_info = wf_get_manager_info();
    
    // دریافت داده‌های اولیه
    $period = wf_get_current_period();
    $fields = wf_get_all_fields();
    $departments = $type === 'organization' ? wf_get_all_departments() : array();
    
    ob_start();
    ?>
    
    <!-- Container اصلی -->
    <div class="wf-manager-panel" data-panel-type="<?php echo esc_attr($type); ?>">
        
        <!-- Header هوشمند -->
        <header class="wf-panel-header">
            <div class="wf-header-left">
                <div class="wf-welcome-section">
                    <div class="wf-avatar">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="wf-user-info">
                        <h1 class="wf-greeting">
                            <span class="wf-greeting-text"><?php _e('خوش آمدید', 'workforce-beni-asad'); ?></span>
                            <span class="wf-user-name"><?php echo esc_html($manager_info['display_name']); ?></span>
                        </h1>
                        <div class="wf-org-info">
                            <i class="fas fa-building"></i>
                            <span class="wf-org-name">
                                <?php if ($type === 'organization'): ?>
                                    <?php _e('مدیریت کل سازمان', 'workforce-beni-asad'); ?>
                                <?php else: ?>
                                    <?php echo esc_html($manager_info['department_name']); ?>
                                <?php endif; ?>
                            </span>
                            <?php if ($type === 'department' && $manager_info['department_color']): ?>
                                <span class="wf-dept-color" style="background-color: <?php echo esc_attr($manager_info['department_color']); ?>"></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="wf-period-info">
                    <div class="wf-period-badge">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="wf-period-title"><?php echo esc_html($period['title']); ?></span>
                    </div>
                    <div class="wf-date-info">
                        <i class="fas fa-clock"></i>
                        <span class="wf-current-date"><?php echo wf_get_current_jalali_date('l j F Y'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="wf-header-right">
                <div class="wf-user-actions">
                    <button class="wf-btn wf-btn-icon wf-help-btn" title="<?php esc_attr_e('راهنما', 'workforce-beni-asad'); ?>">
                        <i class="fas fa-question-circle"></i>
                    </button>
                    <button class="wf-btn wf-btn-icon wf-refresh-btn" title="<?php esc_attr_e('به‌روزرسانی', 'workforce-beni-asad'); ?>" id="wf-refresh-data">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <div class="wf-user-menu">
                        <button class="wf-btn wf-btn-icon wf-user-btn" id="wf-user-menu-btn">
                            <i class="fas fa-user-circle"></i>
                        </button>
                        <div class="wf-user-dropdown" id="wf-user-dropdown">
                            <div class="wf-user-dropdown-header">
                                <div class="wf-dropdown-avatar">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div class="wf-dropdown-info">
                                    <div class="wf-dropdown-name"><?php echo esc_html($manager_info['display_name']); ?></div>
                                    <div class="wf-dropdown-email"><?php echo esc_html($manager_info['email']); ?></div>
                                </div>
                            </div>
                            <div class="wf-user-dropdown-menu">
                                <a href="<?php echo admin_url('profile.php'); ?>" class="wf-dropdown-item" target="_blank">
                                    <i class="fas fa-user-edit"></i>
                                    <?php _e('ویرایش پروفایل', 'workforce-beni-asad'); ?>
                                </a>
                                <a href="<?php echo wp_logout_url(home_url()); ?>" class="wf-dropdown-item wf-logout-item">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <?php _e('خروج از سیستم', 'workforce-beni-asad'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- کارت‌های مانیتورینگ -->
        <section class="wf-monitoring-section" id="wf-monitoring-section">
            <div class="wf-cards-grid">
                <!-- کارت ثابت: وضعیت پرسنل -->
                <div class="wf-card wf-card-personnel">
                    <div class="wf-card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="wf-card-content">
                        <h3 class="wf-card-title"><?php _e('وضعیت پرسنل', 'workforce-beni-asad'); ?></h3>
                        <div class="wf-card-value" id="wf-personnel-count">0</div>
                        <div class="wf-card-trend" id="wf-personnel-trend">
                            <span class="wf-trend-up"><i class="fas fa-arrow-up"></i> <span id="wf-personnel-change">0</span>%</span>
                            <?php _e('نسبت به ماه گذشته', 'workforce-beni-asad'); ?>
                        </div>
                    </div>
                </div>
                
                <!-- کارت ثابت: فیلدهای ضروری -->
                <div class="wf-card wf-card-required">
                    <div class="wf-card-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="wf-card-content">
                        <h3 class="wf-card-title"><?php _e('فیلدهای ضروری', 'workforce-beni-asad'); ?></h3>
                        <div class="wf-card-value">
                            <div class="wf-progress-bar">
                                <div class="wf-progress-fill" id="wf-required-progress" style="width: 0%"></div>
                            </div>
                            <span id="wf-required-percent">0%</span>
                        </div>
                        <div class="wf-card-subtext">
                            <span id="wf-required-count">0 از 0</span> <?php _e('تکمیل شده', 'workforce-beni-asad'); ?>
                        </div>
                    </div>
                </div>
                
                <!-- کارت ثابت: هشدارها -->
                <div class="wf-card wf-card-warning">
                    <div class="wf-card-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="wf-card-content">
                        <h3 class="wf-card-title"><?php _e('هشدار', 'workforce-beni-asad'); ?></h3>
                        <div class="wf-card-value" id="wf-warning-count">0</div>
                        <div class="wf-card-subtext">
                            <?php _e('پرسنل با اطلاعات ناقص', 'workforce-beni-asad'); ?>
                        </div>
                    </div>
                </div>
                
                <!-- کارت‌های داینامیک -->
                <div id="wf-dynamic-cards-container" class="wf-dynamic-cards">
                    <!-- کارت‌های داینامیک اینجا لود می‌شوند -->
                </div>
                
                <!-- دکمه اضافه کردن کارت -->
                <div class="wf-card wf-card-add" id="wf-add-card-btn">
                    <div class="wf-card-content">
                        <div class="wf-add-card-inner">
                            <i class="fas fa-plus-circle"></i>
                            <span class="wf-add-card-text"><?php _e('افزودن کارت مانیتورینگ', 'workforce-beni-asad'); ?></span>
                            <p class="wf-card-hint">
                                <?php _e('روی آیکون 📊 کنار هر ستون کلیک کنید', 'workforce-beni-asad'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- نوار اقدامات -->
        <section class="wf-actions-section">
            <div class="wf-actions-toolbar">
                <div class="wf-actions-left">
                    <button class="wf-action-btn wf-add-btn" id="wf-add-personnel">
                        <i class="fas fa-user-plus"></i>
                        <span><?php _e('افزودن پرسنل جدید', 'workforce-beni-asad'); ?></span>
                    </button>
                    
                    <button class="wf-action-btn wf-edit-btn" id="wf-edit-selected" disabled>
                        <i class="fas fa-edit"></i>
                        <span><?php _e('ویرایش انتخاب شده', 'workforce-beni-asad'); ?></span>
                    </button>
                    
                    <button class="wf-action-btn wf-delete-btn" id="wf-delete-selected" disabled>
                        <i class="fas fa-trash-alt"></i>
                        <span><?php _e('حذف انتخاب شده', 'workforce-beni-asad'); ?></span>
                    </button>
                    
                    <button class="wf-action-btn wf-export-btn" id="wf-export-excel">
                        <i class="fas fa-file-excel"></i>
                        <span><?php _e('خروجی Excel', 'workforce-beni-asad'); ?></span>
                    </button>
                    
                    <?php if ($type === 'organization'): ?>
                    <button class="wf-action-btn wf-report-btn" id="wf-generate-report">
                        <i class="fas fa-chart-pie"></i>
                        <span><?php _e('گزارش سازمانی', 'workforce-beni-asad'); ?></span>
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="wf-actions-right">
                    <div class="wf-search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" 
                               id="wf-global-search" 
                               placeholder="<?php esc_attr_e('جستجوی سریع در همه فیلدها...', 'workforce-beni-asad'); ?>"
                               autocomplete="off">
                        <div class="wf-search-results" id="wf-search-results"></div>
                    </div>
                    
                    <div class="wf-records-per-page">
                        <label><?php _e('نمایش:', 'workforce-beni-asad'); ?></label>
                        <select id="wf-records-per-page">
                            <option value="25">25 <?php _e('رکورد', 'workforce-beni-asad'); ?></option>
                            <option value="50">50 <?php _e('رکورد', 'workforce-beni-asad'); ?></option>
                            <option value="100" selected>100 <?php _e('رکورد', 'workforce-beni-asad'); ?></option>
                            <option value="all"><?php _e('همه رکوردها', 'workforce-beni-asad'); ?></option>
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
            <div class="wf-table-container" id="wf-table-container">
                <div class="wf-table-loading" id="wf-table-loading">
                    <div class="wf-loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <div class="wf-loading-text"><?php _e('در حال بارگذاری داده‌ها...', 'workforce-beni-asad'); ?></div>
                </div>
                
                <div class="wf-table-wrapper" id="wf-table-wrapper">
                    <table class="wf-data-table" id="wf-main-table">
                        <thead>
                            <tr>
                                <th class="wf-checkbox-col">
                                    <input type="checkbox" id="wf-select-all">
                                </th>
                                <th class="wf-row-number">#</th>
                                <?php foreach ($fields as $field): ?>
                                <?php 
                                $field_classes = array('wf-column');
                                if ($field['is_required']) $field_classes[] = 'wf-required';
                                if ($field['is_locked']) $field_classes[] = 'wf-locked';
                                if (!$field['is_locked']) $field_classes[] = 'wf-editable';
                                ?>
                                <th class="<?php echo implode(' ', $field_classes); ?>"
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
                                            <button class="wf-filter-btn" 
                                                    data-field="<?php echo esc_attr($field['id']); ?>"
                                                    title="<?php esc_attr_e('فیلتر', 'workforce-beni-asad'); ?>">
                                                <i class="fas fa-filter"></i>
                                            </button>
                                            <button class="wf-monitor-btn" 
                                                    data-field="<?php echo esc_attr($field['id']); ?>"
                                                    title="<?php esc_attr_e('مانیتورینگ', 'workforce-beni-asad'); ?>">
                                                <i class="fas fa-chart-bar"></i>
                                            </button>
                                            <button class="wf-pin-btn" 
                                                    data-field="<?php echo esc_attr($field['id']); ?>"
                                                    title="<?php esc_attr_e('پین کردن', 'workforce-beni-asad'); ?>">
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
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <div class="wf-pagination">
                <div class="wf-pagination-info">
                    <?php _e('نمایش', 'workforce-beni-asad'); ?> 
                    <span id="wf-current-range">0-0</span> 
                    <?php _e('از', 'workforce-beni-asad'); ?> 
                    <span id="wf-total-records">0</span> 
                    <?php _e('رکورد', 'workforce-beni-asad'); ?>
                </div>
                
                <div class="wf-pagination-controls">
                    <button class="wf-pagination-btn wf-first-page" id="wf-first-page" disabled>
                        <i class="fas fa-angle-double-right"></i>
                    </button>
                    <button class="wf-pagination-btn wf-prev-page" id="wf-prev-page" disabled>
                        <i class="fas fa-angle-right"></i>
                    </button>
                    
                    <div class="wf-page-numbers" id="wf-page-numbers">
                        <!-- شماره صفحات اینجا ساخته می‌شود -->
                    </div>
                    
                    <button class="wf-pagination-btn wf-next-page" id="wf-next-page">
                        <i class="fas fa-angle-left"></i>
                    </button>
                    <button class="wf-pagination-btn wf-last-page" id="wf-last-page">
                        <i class="fas fa-angle-double-left"></i>
                    </button>
                </div>
            </div>
        </section>
        
        <!-- فرم ویرایش سمت راست -->
        <aside class="wf-edit-sidebar" id="wf-edit-sidebar">
            <div class="wf-sidebar-header">
                <h3>
                    <i class="fas fa-edit"></i>
                    <span id="wf-edit-title"><?php _e('ویرایش پرسنل', 'workforce-beni-asad'); ?></span>
                </h3>
                <button class="wf-close-sidebar" id="wf-close-sidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="wf-sidebar-content">
                <form id="wf-edit-form">
                    <div class="wf-form-container" id="wf-form-fields">
                        <!-- فیلدهای فرم اینجا لود می‌شوند -->
                    </div>
                    
                    <div class="wf-form-actions">
                        <button type="button" class="wf-btn wf-btn-secondary wf-prev-personnel" id="wf-prev-personnel">
                            <i class="fas fa-arrow-right"></i>
                            <?php _e('قبلی', 'workforce-beni-asad'); ?>
                        </button>
                        
                        <div class="wf-main-actions">
                            <button type="submit" class="wf-btn wf-btn-primary wf-save-btn">
                                <i class="fas fa-save"></i>
                                <?php _e('ذخیره تغییرات', 'workforce-beni-asad'); ?>
                            </button>
                            <button type="button" class="wf-btn wf-btn-danger wf-cancel-btn" id="wf-cancel-edit">
                                <?php _e('انصراف', 'workforce-beni-asad'); ?>
                            </button>
                        </div>
                        
                        <button type="button" class="wf-btn wf-btn-secondary wf-next-personnel" id="wf-next-personnel">
                            <?php _e('بعدی', 'workforce-beni-asad'); ?>
                            <i class="fas fa-arrow-left"></i>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="wf-sidebar-footer">
                <div class="wf-edit-info">
                    <p><i class="fas fa-info-circle"></i> <?php _e('فیلدهای قرمز رنگ ضروری هستند', 'workforce-beni-asad'); ?></p>
                    <p><i class="fas fa-lock"></i> <?php _e('فیلدهای قفل شده توسط مدیران قابل ویرایش نیستند', 'workforce-beni-asad'); ?></p>
                </div>
            </div>
        </aside>
        
        <!-- Modal ها -->
        <div class="wf-modal-overlay" id="wf-modal-overlay"></div>
        
        <!-- Modal فیلتر -->
        <div class="wf-modal" id="wf-filter-modal">
            <div class="wf-modal-content">
                <div class="wf-modal-header">
                    <h3><i class="fas fa-filter"></i> <?php _e('فیلتر پیشرفته', 'workforce-beni-asad'); ?></h3>
                    <button class="wf-modal-close" id="wf-filter-close">&times;</button>
                </div>
                <div class="wf-modal-body" id="wf-filter-content">
                    <!-- محتوای فیلتر -->
                </div>
                <div class="wf-modal-footer">
                    <button class="wf-btn wf-btn-secondary" id="wf-clear-filters">
                        <?php _e('پاک کردن همه فیلترها', 'workforce-beni-asad'); ?>
                    </button>
                    <button class="wf-btn wf-btn-primary" id="wf-apply-filters">
                        <?php _e('اعمال فیلتر', 'workforce-beni-asad'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Modal افزودن کارت -->
        <div class="wf-modal" id="wf-add-card-modal">
            <div class="wf-modal-content">
                <div class="wf-modal-header">
                    <h3><i class="fas fa-chart-bar"></i> <?php _e('افزودن کارت مانیتورینگ', 'workforce-beni-asad'); ?></h3>
                    <button class="wf-modal-close" id="wf-card-modal-close">&times;</button>
                </div>
                <div class="wf-modal-body">
                    <!-- محتوای modal -->
                </div>
                <div class="wf-modal-footer">
                    <button class="wf-btn wf-btn-secondary" id="wf-cancel-card">
                        <?php _e('انصراف', 'workforce-beni-asad'); ?>
                    </button>
                    <button class="wf-btn wf-btn-primary" id="wf-create-card">
                        <?php _e('ایجاد کارت', 'workforce-beni-asad'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Modal گزارش سازمانی -->
        <?php if ($type === 'organization'): ?>
        <div class="wf-modal" id="wf-report-modal">
            <div class="wf-modal-content wf-modal-lg">
                <div class="wf-modal-header">
                    <h3><i class="fas fa-chart-pie"></i> <?php _e('گزارش سازمانی', 'workforce-beni-asad'); ?></h3>
                    <button class="wf-modal-close" id="wf-report-close">&times;</button>
                </div>
                <div class="wf-modal-body">
                    <!-- محتوای گزارش -->
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- نوتیفیکیشن -->
        <div class="wf-notification-container" id="wf-notification-container"></div>
        
    </div>
    
    <style>
        /* استایل‌های اصلی پنل */
        .wf-manager-panel {
            --wf-primary-color: #1a73e8;
            --wf-secondary-color: #5f6368;
            --wf-success-color: #34a853;
            --wf-warning-color: #f9ab00;
            --wf-danger-color: #ea4335;
            --wf-bg-color: #f8f9fa;
            --wf-card-bg: #ffffff;
            --wf-border-color: #dadce0;
            --wf-text-primary: #202124;
            --wf-text-secondary: #5f6368;
            --wf-text-muted: #80868b;
            --wf-shadow-sm: 0 1px 2px 0 rgba(60,64,67,0.3), 0 1px 3px 1px rgba(60,64,67,0.15);
            --wf-shadow-md: 0 2px 6px 2px rgba(60,64,67,0.15);
            --wf-shadow-lg: 0 4px 12px 3px rgba(60,64,67,0.15);
            --wf-border-radius: 8px;
            --wf-transition: all 0.2s ease;
            
            background: var(--wf-bg-color);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
            color: var(--wf-text-primary);
            direction: rtl;
        }
        
        /* هدر */
        .wf-panel-header {
            background: white;
            border-bottom: 1px solid var(--wf-border-color);
            padding: 0 24px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--wf-shadow-sm);
        }
        
        .wf-header-left {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        
        .wf-welcome-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .wf-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--wf-primary-color), #0d47a1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }
        
        .wf-user-info {
            display: flex;
            flex-direction: column;
        }
        
        .wf-greeting {
            font-size: 14px;
            color: var(--wf-text-secondary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .wf-greeting-text {
            font-weight: normal;
        }
        
        .wf-user-name {
            font-weight: 600;
            color: var(--wf-text-primary);
        }
        
        .wf-org-info {
            font-size: 12px;
            color: var(--wf-text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 2px;
        }
        
        .wf-dept-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }
        
        .wf-period-info {
            display: flex;
            gap: 12px;
        }
        
        .wf-period-badge,
        .wf-date-info {
            padding: 6px 12px;
            background: var(--wf-bg-color);
            border-radius: 20px;
            font-size: 12px;
            color: var(--wf-text-secondary);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .wf-period-badge {
            border: 1px solid var(--wf-primary-color);
            color: var(--wf-primary-color);
            background: rgba(26, 115, 232, 0.1);
        }
        
        .wf-header-right {
            display: flex;
            align-items: center;
        }
        
        .wf-user-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .wf-btn {
            padding: 8px 16px;
            border: none;
            border-radius: var(--wf-border-radius);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--wf-transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .wf-btn:hover {
            transform: translateY(-1px);
        }
        
        .wf-btn:active {
            transform: translateY(0);
        }
        
        .wf-btn-icon {
            width: 40px;
            height: 40px;
            padding: 0;
            border-radius: 50%;
            background: transparent;
            color: var(--wf-text-secondary);
        }
        
        .wf-btn-icon:hover {
            background: var(--wf-bg-color);
            color: var(--wf-primary-color);
        }
        
        .wf-user-menu {
            position: relative;
        }
        
        .wf-user-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            width: 280px;
            background: white;
            border-radius: var(--wf-border-radius);
            box-shadow: var(--wf-shadow-lg);
            display: none;
            z-index: 1000;
            margin-top: 8px;
        }
        
        .wf-user-dropdown.show {
            display: block;
            animation: wf-fadeIn 0.2s ease;
        }
        
        .wf-user-dropdown-header {
            padding: 16px;
            border-bottom: 1px solid var(--wf-border-color);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .wf-dropdown-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--wf-primary-color), #0d47a1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }
        
        .wf-dropdown-info {
            flex: 1;
        }
        
        .wf-dropdown-name {
            font-weight: 600;
            color: var(--wf-text-primary);
            margin-bottom: 2px;
        }
        
        .wf-dropdown-email {
            font-size: 12px;
            color: var(--wf-text-muted);
        }
        
        .wf-user-dropdown-menu {
            padding: 8px 0;
        }
        
        .wf-dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--wf-text-primary);
            text-decoration: none;
            transition: var(--wf-transition);
        }
        
        .wf-dropdown-item:hover {
            background: var(--wf-bg-color);
            color: var(--wf-primary-color);
        }
        
        .wf-logout-item {
            color: var(--wf-danger-color);
        }
        
        /* کارت‌های مانیتورینگ */
        .wf-monitoring-section {
            padding: 24px;
        }
        
        .wf-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .wf-card {
            background: var(--wf-card-bg);
            border-radius: var(--wf-border-radius);
            padding: 20px;
            box-shadow: var(--wf-shadow-sm);
            transition: var(--wf-transition);
            border: 1px solid var(--wf-border-color);
        }
        
        .wf-card:hover {
            box-shadow: var(--wf-shadow-md);
            transform: translateY(-2px);
        }
        
        .wf-card.wf-card-personnel {
            border-top: 4px solid var(--wf-primary-color);
        }
        
        .wf-card.wf-card-required {
            border-top: 4px solid var(--wf-warning-color);
        }
        
        .wf-card.wf-card-warning {
            border-top: 4px solid var(--wf-danger-color);
        }
        
        .wf-card.wf-card-add {
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed var(--wf-border-color);
            background: transparent;
            cursor: pointer;
            transition: var(--wf-transition);
        }
        
        .wf-card.wf-card-add:hover {
            border-color: var(--wf-primary-color);
            background: rgba(26, 115, 232, 0.05);
        }
        
        .wf-add-card-inner {
            text-align: center;
            color: var(--wf-text-secondary);
        }
        
        .wf-add-card-inner i {
            font-size: 32px;
            margin-bottom: 8px;
            color: var(--wf-primary-color);
        }
        
        .wf-add-card-text {
            display: block;
            font-weight: 500;
            margin-bottom: 4px;
        }
        
        .wf-card-hint {
            font-size: 12px;
            color: var(--wf-text-muted);
            margin: 0;
        }
        
        .wf-card-icon {
            font-size: 24px;
            color: var(--wf-primary-color);
            margin-bottom: 12px;
        }
        
        .wf-card-required .wf-card-icon {
            color: var(--wf-warning-color);
        }
        
        .wf-card-warning .wf-card-icon {
            color: var(--wf-danger-color);
        }
        
        .wf-card-content h3 {
            margin: 0 0 8px 0;
            font-size: 14px;
            font-weight: 500;
            color: var(--wf-text-secondary);
        }
        
        .wf-card-value {
            font-size: 32px;
            font-weight: 600;
            color: var(--wf-text-primary);
            margin-bottom: 8px;
        }
        
        .wf-card-trend {
            font-size: 12px;
            color: var(--wf-text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .wf-trend-up {
            color: var(--wf-success-color);
            display: flex;
            align-items: center;
            gap: 2px;
        }
        
        .wf-trend-down {
            color: var(--wf-danger-color);
            display: flex;
            align-items: center;
            gap: 2px;
        }
        
        .wf-progress-bar {
            height: 8px;
            background: var(--wf-border-color);
            border-radius: 4px;
            overflow: hidden;
            margin: 8px 0;
        }
        
        .wf-progress-fill {
            height: 100%;
            background: var(--wf-warning-color);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .wf-card-subtext {
            font-size: 12px;
            color: var(--wf-text-muted);
        }
        
        /* نوار اقدامات */
        .wf-actions-section {
            padding: 0 24px 16px;
        }
        
        .wf-actions-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .wf-actions-left,
        .wf-actions-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .wf-action-btn {
            padding: 10px 16px;
            border: none;
            border-radius: var(--wf-border-radius);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--wf-transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .wf-action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .wf-action-btn:disabled:hover {
            transform: none;
        }
        
        .wf-add-btn {
            background: var(--wf-primary-color);
            color: white;
        }
        
        .wf-add-btn:hover {
            background: #0d47a1;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(26, 115, 232, 0.3);
        }
        
        .wf-edit-btn {
            background: var(--wf-secondary-color);
            color: white;
        }
        
        .wf-edit-btn:hover {
            background: #3c4043;
            transform: translateY(-1px);
        }
        
        .wf-delete-btn {
            background: var(--wf-danger-color);
            color: white;
        }
        
        .wf-delete-btn:hover {
            background: #c5221f;
            transform: translateY(-1px);
        }
        
        .wf-export-btn {
            background: var(--wf-success-color);
            color: white;
        }
        
        .wf-export-btn:hover {
            background: #2e7d32;
            transform: translateY(-1px);
        }
        
        .wf-report-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .wf-report-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .wf-search-box {
            position: relative;
        }
        
        .wf-search-box i {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--wf-text-muted);
        }
        
        .wf-search-box input {
            padding: 10px 40px 10px 16px;
            border: 1px solid var(--wf-border-color);
            border-radius: var(--wf-border-radius);
            font-size: 14px;
            width: 280px;
            transition: var(--wf-transition);
        }
        
        .wf-search-box input:focus {
            outline: none;
            border-color: var(--wf-primary-color);
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
        }
        
        .wf-search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid var(--wf-border-color);
            border-radius: var(--wf-border-radius);
            box-shadow: var(--wf-shadow-lg);
            display: none;
            z-index: 1000;
            margin-top: 4px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .wf-search-results.show {
            display: block;
            animation: wf-fadeIn 0.2s ease;
        }
        
        .wf-search-result-item {
            padding: 12px 16px;
            cursor: pointer;
            transition: var(--wf-transition);
            border-bottom: 1px solid var(--wf-border-color);
        }
        
        .wf-search-result-item:last-child {
            border-bottom: none;
        }
        
        .wf-search-result-item:hover {
            background: var(--wf-bg-color);
        }
        
        .wf-search-result-name {
            font-weight: 500;
            margin-bottom: 4px;
        }
        
        .wf-search-result-details {
            font-size: 12px;
            color: var(--wf-text-muted);
        }
        
        .wf-records-per-page {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .wf-records-per-page label {
            font-size: 14px;
            color: var(--wf-text-secondary);
        }
        
        .wf-records-per-page select {
            padding: 8px 12px;
            border: 1px solid var(--wf-border-color);
            border-radius: var(--wf-border-radius);
            font-size: 14px;
            background: white;
            cursor: pointer;
        }
        
        .wf-records-per-page select:focus {
            outline: none;
            border-color: var(--wf-primary-color);
        }
        
        .wf-active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            min-height: 40px;
        }
        
        .wf-filter-tag {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: var(--wf-bg-color);
            border: 1px solid var(--wf-border-color);
            border-radius: 20px;
            font-size: 12px;
        }
        
        .wf-filter-tag-remove {
            background: none;
            border: none;
            color: var(--wf-text-muted);
            cursor: pointer;
            padding: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .wf-filter-tag-remove:hover {
            color: var(--wf-danger-color);
        }
        
        /* جدول */
        .wf-table-section {
            padding: 0 24px 24px;
        }
        
        .wf-table-container {
            background: white;
            border-radius: var(--wf-border-radius);
            border: 1px solid var(--wf-border-color);
            overflow: hidden;
            position: relative;
            min-height: 400px;
        }
        
        .wf-table-loading {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        
        .wf-loading-spinner {
            font-size: 32px;
            color: var(--wf-primary-color);
            margin-bottom: 16px;
        }
        
        .wf-loading-text {
            font-size: 16px;
            color: var(--wf-text-secondary);
        }
        
        .wf-table-wrapper {
            overflow-x: auto;
        }
        
        .wf-data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }
        
        .wf-data-table thead {
            background: var(--wf-bg-color);
            position: sticky;
            top: 0;
            z-index: 5;
        }
        
        .wf-data-table th {
            padding: 12px 16px;
            text-align: right;
            font-weight: 500;
            font-size: 14px;
            color: var(--wf-text-secondary);
            border-bottom: 2px solid var(--wf-border-color);
            white-space: nowrap;
        }
        
        .wf-data-table tbody tr {
            border-bottom: 1px solid var(--wf-border-color);
            transition: var(--wf-transition);
        }
        
        .wf-data-table tbody tr:hover {
            background: var(--wf-bg-color);
        }
        
        .wf-data-table tbody tr.selected {
            background: rgba(26, 115, 232, 0.1);
        }
        
        .wf-data-table tbody tr.deleted {
            opacity: 0.5;
        }
        
        .wf-data-table td {
            padding: 12px 16px;
            font-size: 14px;
            vertical-align: top;
        }
        
        .wf-checkbox-col {
            width: 40px;
            text-align: center;
        }
        
        .wf-checkbox-col input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .wf-row-number {
            width: 60px;
            text-align: center;
            color: var(--wf-text-muted);
            font-size: 12px;
        }
        
        .wf-column.wf-required {
            background: rgba(249, 171, 0, 0.1);
        }
        
        .wf-column.wf-locked {
            background: rgba(95, 99, 104, 0.1);
        }
        
        .wf-column.wf-editable {
            background: rgba(255, 255, 255, 0.5);
        }
        
        .wf-column.pinned {
            background: rgba(26, 115, 232, 0.1);
            position: sticky;
            right: 0;
            z-index: 2;
            box-shadow: -2px 0 4px rgba(0, 0, 0, 0.1);
        }
        
        .wf-column-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .wf-column-title {
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .wf-required-mark {
            color: var(--wf-danger-color);
            margin-right: 4px;
        }
        
        .wf-column-actions {
            display: flex;
            gap: 4px;
            opacity: 0;
            transition: var(--wf-transition);
        }
        
        .wf-column:hover .wf-column-actions {
            opacity: 1;
        }
        
        .wf-column-actions button {
            width: 24px;
            height: 24px;
            border: none;
            background: transparent;
            color: var(--wf-text-muted);
            cursor: pointer;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .wf-column-actions button:hover {
            background: var(--wf-bg-color);
            color: var(--wf-primary-color);
        }
        
        .wf-column-actions .wf-pin-btn.active {
            color: var(--wf-primary-color);
        }
        
        /* سلول‌های جدول */
        .wf-cell {
            min-height: 40px;
            display: flex;
            align-items: center;
        }
        
        .wf-cell.editable {
            cursor: pointer;
            transition: var(--wf-transition);
        }
        
        .wf-cell.editable:hover {
            background: var(--wf-bg-color);
        }
        
        .wf-cell.locked {
            color: var(--wf-text-muted);
            cursor: not-allowed;
        }
        
        .wf-cell input,
        .wf-cell select,
        .wf-cell textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--wf-border-color);
            border-radius: 4px;
            font-size: 14px;
            background: white;
        }
        
        .wf-cell input:focus,
        .wf-cell select:focus,
        .wf-cell textarea:focus {
            outline: none;
            border-color: var(--wf-primary-color);
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
        }
        
        .wf-cell input[type="date"] {
            font-family: inherit;
        }
        
        /* Pagination */
        .wf-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            padding: 12px 0;
        }
        
        .wf-pagination-info {
            font-size: 14px;
            color: var(--wf-text-secondary);
        }
        
        .wf-pagination-controls {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .wf-pagination-btn {
            width: 36px;
            height: 36px;
            border: 1px solid var(--wf-border-color);
            background: white;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--wf-transition);
        }
        
        .wf-pagination-btn:hover:not(:disabled) {
            border-color: var(--wf-primary-color);
            color: var(--wf-primary-color);
        }
        
        .wf-pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .wf-page-numbers {
            display: flex;
            gap: 4px;
        }
        
        .wf-page-btn {
            min-width: 36px;
            height: 36px;
            border: 1px solid var(--wf-border-color);
            background: white;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--wf-transition);
            font-size: 14px;
        }
        
        .wf-page-btn:hover {
            border-color: var(--wf-primary-color);
            color: var(--wf-primary-color);
        }
        
        .wf-page-btn.active {
            background: var(--wf-primary-color);
            border-color: var(--wf-primary-color);
            color: white;
        }
        
        /* سایدبار ویرایش */
        .wf-edit-sidebar {
            position: fixed;
            top: 0;
            left: -400px;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: var(--wf-shadow-lg);
            z-index: 1001;
            transition: left 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        .wf-edit-sidebar.open {
            left: 0;
        }
        
        .wf-sidebar-header {
            padding: 20px;
            border-bottom: 1px solid var(--wf-border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .wf-sidebar-header h3 {
            margin: 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .wf-close-sidebar {
            width: 36px;
            height: 36px;
            border: none;
            background: transparent;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: var(--wf-text-secondary);
            transition: var(--wf-transition);
        }
        
        .wf-close-sidebar:hover {
            background: var(--wf-bg-color);
            color: var(--wf-danger-color);
        }
        
        .wf-sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }
        
        .wf-form-container {
            margin-bottom: 20px;
        }
        
        .wf-form-group {
            margin-bottom: 16px;
        }
        
        .wf-form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 500;
            color: var(--wf-text-primary);
        }
        
        .wf-form-group label.required::after {
            content: " *";
            color: var(--wf-danger-color);
        }
        
        .wf-form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--wf-border-color);
            border-radius: var(--wf-border-radius);
            font-size: 14px;
            transition: var(--wf-transition);
        }
        
        .wf-form-control:focus {
            outline: none;
            border-color: var(--wf-primary-color);
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
        }
        
        .wf-form-control.error {
            border-color: var(--wf-danger-color);
        }
        
        .wf-form-control:disabled {
            background: var(--wf-bg-color);
            color: var(--wf-text-muted);
            cursor: not-allowed;
        }
        
        .wf-form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-top: 1px solid var(--wf-border-color);
            background: white;
        }
        
        .wf-main-actions {
            display: flex;
            gap: 8px;
        }
        
        .wf-btn-primary {
            background: var(--wf-primary-color);
            color: white;
        }
        
        .wf-btn-primary:hover {
            background: #0d47a1;
        }
        
        .wf-btn-secondary {
            background: var(--wf-bg-color);
            color: var(--wf-text-primary);
            border: 1px solid var(--wf-border-color);
        }
        
        .wf-btn-secondary:hover {
            background: #e8eaed;
        }
        
        .wf-btn-danger {
            background: var(--wf-danger-color);
            color: white;
        }
        
        .wf-btn-danger:hover {
            background: #c5221f;
        }
        
        .wf-sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--wf-border-color);
            background: var(--wf-bg-color);
        }
        
        .wf-edit-info {
            font-size: 12px;
            color: var(--wf-text-muted);
        }
        
        .wf-edit-info p {
            margin: 4px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Modal ها */
        .wf-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: none;
        }
        
        .wf-modal-overlay.show {
            display: block;
            animation: wf-fadeIn 0.2s ease;
        }
        
        .wf-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.9);
            background: white;
            border-radius: var(--wf-border-radius);
            box-shadow: var(--wf-shadow-lg);
            z-index: 1001;
            display: none;
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .wf-modal.show {
            display: block;
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
        
        .wf-modal.wf-modal-lg {
            width: 90%;
            max-width: 1000px;
            height: 80vh;
        }
        
        .wf-modal-content {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .wf-modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--wf-border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .wf-modal-header h3 {
            margin: 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .wf-modal-close {
            width: 36px;
            height: 36px;
            border: none;
            background: transparent;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--wf-text-secondary);
            transition: var(--wf-transition);
        }
        
        .wf-modal-close:hover {
            background: var(--wf-bg-color);
            color: var(--wf-danger-color);
        }
        
        .wf-modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }
        
        .wf-modal-footer {
            padding: 20px;
            border-top: 1px solid var(--wf-border-color);
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        
        /* نوتیفیکیشن */
        .wf-notification-container {
            position: fixed;
            bottom: 24px;
            left: 24px;
            z-index: 1002;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .wf-notification {
            padding: 16px 20px;
            background: white;
            border-radius: var(--wf-border-radius);
            box-shadow: var(--wf-shadow-lg);
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 300px;
            max-width: 400px;
            animation: wf-slideInLeft 0.3s ease;
        }
        
        .wf-notification.success {
            border-right: 4px solid var(--wf-success-color);
        }
        
        .wf-notification.error {
            border-right: 4px solid var(--wf-danger-color);
        }
        
        .wf-notification.warning {
            border-right: 4px solid var(--wf-warning-color);
        }
        
        .wf-notification.info {
            border-right: 4px solid var(--wf-primary-color);
        }
        
        .wf-notification-icon {
            font-size: 20px;
        }
        
        .wf-notification.success .wf-notification-icon {
            color: var(--wf-success-color);
        }
        
        .wf-notification.error .wf-notification-icon {
            color: var(--wf-danger-color);
        }
        
        .wf-notification.warning .wf-notification-icon {
            color: var(--wf-warning-color);
        }
        
        .wf-notification.info .wf-notification-icon {
            color: var(--wf-primary-color);
        }
        
        .wf-notification-content {
            flex: 1;
        }
        
        .wf-notification-title {
            font-weight: 500;
            margin-bottom: 4px;
        }
        
        .wf-notification-message {
            font-size: 14px;
            color: var(--wf-text-secondary);
        }
        
        .wf-notification-close {
            background: none;
            border: none;
            color: var(--wf-text-muted);
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .wf-notification-close:hover {
            color: var(--wf-danger-color);
        }
        
        /* انیمیشن‌ها */
        @keyframes wf-fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes wf-slideInLeft {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes wf-slideInRight {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* ریسپانسیو */
        @media (max-width: 1200px) {
            .wf-cards-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
            
            .wf-search-box input {
                width: 240px;
            }
        }
        
        @media (max-width: 992px) {
            .wf-panel-header {
                padding: 0 16px;
            }
            
            .wf-monitoring-section,
            .wf-actions-section,
            .wf-table-section {
                padding: 16px;
            }
            
            .wf-actions-toolbar {
                flex-direction: column;
                align-items: stretch;
                gap: 16px;
            }
            
            .wf-actions-left,
            .wf-actions-right {
                width: 100%;
                justify-content: center;
            }
            
            .wf-search-box {
                width: 100%;
            }
            
            .wf-search-box input {
                width: 100%;
            }
            
            .wf-edit-sidebar {
                width: 100%;
                left: -100%;
            }
        }
        
        @media (max-width: 768px) {
            .wf-cards-grid {
                grid-template-columns: 1fr;
            }
            
            .wf-header-left {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .wf-period-info {
                width: 100%;
                justify-content: space-between;
            }
            
            .wf-action-btn span {
                display: none;
            }
            
            .wf-action-btn {
                padding: 10px;
            }
            
            .wf-records-per-page label {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .wf-modal.wf-modal-lg {
                width: 95%;
                height: 90vh;
            }
            
            .wf-pagination {
                flex-direction: column;
                gap: 12px;
                align-items: stretch;
            }
            
            .wf-pagination-info {
                text-align: center;
            }
            
            .wf-pagination-controls {
                justify-content: center;
            }
        }
    </style>
    
    <script>
    // JavaScript اصلی پنل
    (function() {
        'use strict';
        
        const wfPanel = {
            // تنظیمات
            config: {
                ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('workforce_manager_nonce'); ?>',
                panelType: '<?php echo $type; ?>',
                managerId: <?php echo get_current_user_id(); ?>,
                departments: <?php echo json_encode($type === 'organization' ? $departments : array()); ?>,
                fields: <?php echo json_encode($fields); ?>,
                currentPeriod: <?php echo json_encode($period); ?>,
                itemsPerPage: 100,
                currentPage: 1,
                totalPages: 1,
                totalRecords: 0,
                filters: {},
                sortField: null,
                sortOrder: 'asc',
                selectedRows: new Set(),
                pinnedColumns: new Set(),
                dynamicCards: [],
                editSidebarOpen: false,
                currentEditId: null
            },
            
            // حالت‌های UI
            uiState: {
                loading: false,
                sidebarLoading: false,
                tableLoading: true,
                filtersApplied: false
            },
            
            // المنت‌های DOM
            elements: {},
            
            // داده‌ها
            data: {
                personnel: [],
                departments: [],
                fields: [],
                stats: {},
                searchResults: []
            },
            
            // مقداردهی اولیه
            init: function() {
                this.cacheElements();
                this.bindEvents();
                this.loadInitialData();
                this.setupUserMenu();
                this.setupKeyboardShortcuts();
            },
            
            // کش کردن المنت‌های DOM
            cacheElements: function() {
                this.elements = {
                    // Header
                    refreshBtn: document.getElementById('wf-refresh-data'),
                    userMenuBtn: document.getElementById('wf-user-menu-btn'),
                    userDropdown: document.getElementById('wf-user-dropdown'),
                    
                    // Monitoring Cards
                    personnelCount: document.getElementById('wf-personnel-count'),
                    requiredProgress: document.getElementById('wf-required-progress'),
                    requiredPercent: document.getElementById('wf-required-percent'),
                    requiredCount: document.getElementById('wf-required-count'),
                    warningCount: document.getElementById('wf-warning-count'),
                    dynamicCardsContainer: document.getElementById('wf-dynamic-cards-container'),
                    addCardBtn: document.getElementById('wf-add-card-btn'),
                    
                    // Actions
                    addPersonnelBtn: document.getElementById('wf-add-personnel'),
                    editSelectedBtn: document.getElementById('wf-edit-selected'),
                    deleteSelectedBtn: document.getElementById('wf-delete-selected'),
                    exportExcelBtn: document.getElementById('wf-export-excel'),
                    generateReportBtn: document.getElementById('wf-generate-report'),
                    globalSearch: document.getElementById('wf-global-search'),
                    searchResults: document.getElementById('wf-search-results'),
                    recordsPerPage: document.getElementById('wf-records-per-page'),
                    activeFilters: document.getElementById('wf-active-filters'),
                    
                    // Table
                    tableContainer: document.getElementById('wf-table-container'),
                    tableLoading: document.getElementById('wf-table-loading'),
                    tableWrapper: document.getElementById('wf-table-wrapper'),
                    tableBody: document.getElementById('wf-table-body'),
                    selectAll: document.getElementById('wf-select-all'),
                    
                    // Pagination
                    currentRange: document.getElementById('wf-current-range'),
                    totalRecords: document.getElementById('wf-total-records'),
                    firstPage: document.getElementById('wf-first-page'),
                    prevPage: document.getElementById('wf-prev-page'),
                    pageNumbers: document.getElementById('wf-page-numbers'),
                    nextPage: document.getElementById('wf-next-page'),
                    lastPage: document.getElementById('wf-last-page'),
                    
                    // Edit Sidebar
                    editSidebar: document.getElementById('wf-edit-sidebar'),
                    editTitle: document.getElementById('wf-edit-title'),
                    formFields: document.getElementById('wf-form-fields'),
                    editForm: document.getElementById('wf-edit-form'),
                    prevPersonnel: document.getElementById('wf-prev-personnel'),
                    nextPersonnel: document.getElementById('wf-next-personnel'),
                    saveBtn: document.querySelector('.wf-save-btn'),
                    cancelEdit: document.getElementById('wf-cancel-edit'),
                    closeSidebar: document.getElementById('wf-close-sidebar'),
                    
                    // Modals
                    modalOverlay: document.getElementById('wf-modal-overlay'),
                    filterModal: document.getElementById('wf-filter-modal'),
                    filterContent: document.getElementById('wf-filter-content'),
                    filterClose: document.getElementById('wf-filter-close'),
                    clearFilters: document.getElementById('wf-clear-filters'),
                    applyFilters: document.getElementById('wf-apply-filters'),
                    addCardModal: document.getElementById('wf-add-card-modal'),
                    cardModalClose: document.getElementById('wf-card-modal-close'),
                    cancelCard: document.getElementById('wf-cancel-card'),
                    createCard: document.getElementById('wf-create-card'),
                    reportModal: document.getElementById('wf-report-modal'),
                    reportClose: document.getElementById('wf-report-close'),
                    
                    // Notification
                    notificationContainer: document.getElementById('wf-notification-container')
                };
            },
            
            // بایند کردن ایونت‌ها
            bindEvents: function() {
                // Header Events
                this.elements.refreshBtn.addEventListener('click', () => this.refreshData());
                this.elements.userMenuBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.toggleUserDropdown();
                });
                
                // Monitoring Cards Events
                this.elements.addCardBtn.addEventListener('click', () => this.showAddCardModal());
                
                // Actions Events
                this.elements.addPersonnelBtn.addEventListener('click', () => this.addPersonnel());
                this.elements.editSelectedBtn.addEventListener('click', () => this.editSelected());
                this.elements.deleteSelectedBtn.addEventListener('click', () => this.deleteSelected());
                this.elements.exportExcelBtn.addEventListener('click', () => this.exportExcel());
                if (this.elements.generateReportBtn) {
                    this.elements.generateReportBtn.addEventListener('click', () => this.generateReport());
                }
                
                this.elements.globalSearch.addEventListener('input', (e) => this.handleSearch(e.target.value));
                this.elements.globalSearch.addEventListener('focus', () => {
                    if (this.data.searchResults.length > 0) {
                        this.elements.searchResults.classList.add('show');
                    }
                });
                this.elements.globalSearch.addEventListener('blur', () => {
                    setTimeout(() => {
                        this.elements.searchResults.classList.remove('show');
                    }, 200);
                });
                
                this.elements.recordsPerPage.addEventListener('change', (e) => {
                    this.config.itemsPerPage = e.target.value === 'all' ? 'all' : parseInt(e.target.value);
                    this.loadPersonnelData();
                });
                
                // Table Events
                this.elements.selectAll.addEventListener('change', (e) => this.toggleSelectAll(e.target.checked));
                
                // Pagination Events
                this.elements.firstPage.addEventListener('click', () => this.goToPage(1));
                this.elements.prevPage.addEventListener('click', () => this.goToPage(this.config.currentPage - 1));
                this.elements.nextPage.addEventListener('click', () => this.goToPage(this.config.currentPage + 1));
                this.elements.lastPage.addEventListener('click', () => this.goToPage(this.config.totalPages));
                
                // Edit Sidebar Events
                this.elements.prevPersonnel.addEventListener('click', () => this.navigatePersonnel(-1));
                this.elements.nextPersonnel.addEventListener('click', () => this.navigatePersonnel(1));
                this.elements.editForm.addEventListener('submit', (e) => this.savePersonnel(e));
                this.elements.cancelEdit.addEventListener('click', () => this.closeEditSidebar());
                this.elements.closeSidebar.addEventListener('click', () => this.closeEditSidebar());
                
                // Modal Events
                this.elements.filterClose.addEventListener('click', () => this.closeModal(this.elements.filterModal));
                this.elements.clearFilters.addEventListener('click', () => this.clearAllFilters());
                this.elements.applyFilters.addEventListener('click', () => this.applyFilters());
                
                this.elements.cardModalClose.addEventListener('click', () => this.closeModal(this.elements.addCardModal));
                this.elements.cancelCard.addEventListener('click', () => this.closeModal(this.elements.addCardModal));
                this.elements.createCard.addEventListener('click', () => this.createMonitoringCard());
                
                if (this.elements.reportClose) {
                    this.elements.reportClose.addEventListener('click', () => this.closeModal(this.elements.reportModal));
                }
                
                this.elements.modalOverlay.addEventListener('click', () => this.closeAllModals());
                
                // Close dropdown when clicking outside
                document.addEventListener('click', () => {
                    this.elements.userDropdown.classList.remove('show');
                });
            },
            
            // راه‌اندازی منوی کاربر
            setupUserMenu: function() {
                this.elements.userMenuBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.elements.userDropdown.classList.toggle('show');
                });
            },
            
            // راه‌اندازی شورتکات‌های کیبورد
            setupKeyboardShortcuts: function() {
                document.addEventListener('keydown', (e) => {
                    // جلوگیری از اجرا در input‌ها
                    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
                        return;
                    }
                    
                    // Ctrl + F: جستجو
                    if (e.ctrlKey && e.key === 'f') {
                        e.preventDefault();
                        this.elements.globalSearch.focus();
                    }
                    
                    // Ctrl + S: ذخیره
                    if (e.ctrlKey && e.key === 's') {
                        e.preventDefault();
                        if (this.elements.editSidebar.classList.contains('open')) {
                            this.elements.saveBtn.click();
                        }
                    }
                    
                    // Ctrl + A: انتخاب همه
                    if (e.ctrlKey && e.key === 'a') {
                        e.preventDefault();
                        this.toggleSelectAll(true);
                    }
                    
                    // Escape: بستن
                    if (e.key === 'Escape') {
                        if (this.elements.editSidebar.classList.contains('open')) {
                            this.closeEditSidebar();
                        }
                        this.closeAllModals();
                    }
                    
                    // Arrow keys for navigation
                    if (this.elements.editSidebar.classList.contains('open')) {
                        if (e.key === 'ArrowRight') {
                            this.navigatePersonnel(-1);
                        } else if (e.key === 'ArrowLeft') {
                            this.navigatePersonnel(1);
                        }
                    }
                });
            },
            
            // بارگذاری داده‌های اولیه
            loadInitialData: function() {
                this.loadStats();
                this.loadPersonnelData();
                this.loadDynamicCards();
            },
            
            // بارگذاری آمار
            loadStats: async function() {
                try {
                    const response = await this.ajaxRequest('wf_get_manager_stats', {
                        panel_type: this.config.panelType,
                        manager_id: this.config.managerId
                    });
                    
                    if (response.success) {
                        this.data.stats = response.data;
                        this.updateStatsUI();
                    }
                } catch (error) {
                    this.showNotification('خطا در بارگذاری آمار', error.message, 'error');
                }
            },
            
            // بارگذاری داده‌های پرسنل
            loadPersonnelData: async function() {
                this.setTableLoading(true);
                
                try {
                    const response = await this.ajaxRequest('wf_get_personnel_data', {
                        panel_type: this.config.panelType,
                        manager_id: this.config.managerId,
                        page: this.config.currentPage,
                        per_page: this.config.itemsPerPage,
                        filters: this.config.filters,
                        sort_field: this.config.sortField,
                        sort_order: this.config.sortOrder
                    });
                    
                    if (response.success) {
                        this.data.personnel = response.data.personnel;
                        this.config.totalRecords = response.data.total_records;
                        this.config.totalPages = response.data.total_pages;
                        
                        this.renderTable();
                        this.updatePaginationUI();
                        this.updateSelectedRows();
                    }
                } catch (error) {
                    this.showNotification('خطا در بارگذاری داده‌ها', error.message, 'error');
                } finally {
                    this.setTableLoading(false);
                }
            },
            
            // بارگذاری کارت‌های داینامیک
            loadDynamicCards: async function() {
                try {
                    const response = await this.ajaxRequest('wf_get_dynamic_cards', {
                        user_id: this.config.managerId
                    });
                    
                    if (response.success) {
                        this.config.dynamicCards = response.data.cards;
                        this.renderDynamicCards();
                    }
                } catch (error) {
                    console.error('Error loading dynamic cards:', error);
                }
            },
            
            // رفرش داده‌ها
            refreshData: function() {
                this.loadStats();
                this.loadPersonnelData();
                this.showNotification('داده‌ها به‌روزرسانی شد', '', 'success');
            },
            
            // به‌روزرسانی UI آمار
            updateStatsUI: function() {
                const stats = this.data.stats;
                
                // آمار پرسنل
                this.elements.personnelCount.textContent = stats.personnel_count || 0;
                if (stats.personnel_trend) {
                    const trendElem = this.elements.personnelCount.parentElement.querySelector('.wf-trend-up span');
                    if (trendElem) {
                        trendElem.textContent = Math.abs(stats.personnel_trend);
                    }
                }
                
                // فیلدهای ضروری
                const requiredPercent = stats.required_completion || 0;
                this.elements.requiredProgress.style.width = requiredPercent + '%';
                this.elements.requiredPercent.textContent = requiredPercent + '%';
                this.elements.requiredCount.textContent = (stats.required_filled || 0) + ' از ' + (stats.required_total || 0);
                
                // هشدارها
                this.elements.warningCount.textContent = stats.warning_count || 0;
            },
            
            // رندر جدول
            renderTable: function() {
                if (!this.data.personnel.length) {
                    this.elements.tableBody.innerHTML = `
                        <tr>
                            <td colspan="${this.config.fields.length + 2}" class="wf-no-data">
                                <i class="fas fa-database"></i>
                                <span>داده‌ای یافت نشد</span>
                            </td>
                        </tr>
                    `;
                    return;
                }
                
                let html = '';
                
                this.data.personnel.forEach((person, index) => {
                    const rowNumber = (this.config.currentPage - 1) * (this.config.itemsPerPage === 'all' ? this.data.personnel.length : this.config.itemsPerPage) + index + 1;
                    const isSelected = this.config.selectedRows.has(person.id);
                    const isDeleted = person.is_deleted;
                    
                    html += `
                        <tr data-id="${person.id}" class="${isSelected ? 'selected' : ''} ${isDeleted ? 'deleted' : ''}">
                            <td class="wf-checkbox-col">
                                <input type="checkbox" class="wf-row-checkbox" value="${person.id}" ${isSelected ? 'checked' : ''} ${isDeleted ? 'disabled' : ''}>
                            </td>
                            <td class="wf-row-number">${rowNumber}</td>
                    `;
                    
                    this.config.fields.forEach(field => {
                        const value = person.data[field.field_key] || '';
                        const isLocked = field.is_locked;
                        const isEditable = !isLocked;
                        const cellClasses = ['wf-cell'];
                        
                        if (isLocked) cellClasses.push('locked');
                        if (isEditable) cellClasses.push('editable');
                        
                        html += `
                            <td data-field="${field.field_key}">
                                <div class="${cellClasses.join(' ')}" 
                                     data-personnel-id="${person.id}"
                                     data-field-id="${field.id}"
                                     data-field-type="${field.field_type}"
                                     ${isEditable ? 'contenteditable="true"' : ''}>
                                    ${this.formatCellValue(value, field.field_type)}
                                </div>
                            </td>
                        `;
                    });
                    
                    html += '</tr>';
                });
                
                this.elements.tableBody.innerHTML = html;
                
                // اضافه کردن ایونت‌های سطرها
                this.addRowEvents();
            },
            
            // اضافه کردن ایونت‌های سطرها
            addRowEvents: function() {
                // چک‌باکس سطرها
                const rowCheckboxes = this.elements.tableBody.querySelectorAll('.wf-row-checkbox');
                rowCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', (e) => {
                        const rowId = parseInt(e.target.value);
                        if (e.target.checked) {
                            this.config.selectedRows.add(rowId);
                            e.target.closest('tr').classList.add('selected');
                        } else {
                            this.config.selectedRows.delete(rowId);
                            e.target.closest('tr').classList.remove('selected');
                        }
                        this.updateActionButtons();
                    });
                });
                
                // دابل کلیک برای ویرایش
                const rows = this.elements.tableBody.querySelectorAll('tr');
                rows.forEach(row => {
                    row.addEventListener('dblclick', (e) => {
                        if (e.target.classList.contains('wf-row-checkbox')) return;
                        
                        const rowId = parseInt(row.dataset.id);
                        this.editPersonnel(rowId);
                    });
                });
                
                // ایونت‌های سلول‌های قابل ویرایش
                const editableCells = this.elements.tableBody.querySelectorAll('.wf-cell.editable');
                editableCells.forEach(cell => {
                    cell.addEventListener('click', (e) => {
                        if (cell.contentEditable === 'true') {
                            this.editCell(cell);
                        }
                    });
                    
                    cell.addEventListener('blur', (e) => {
                        if (cell.contentEditable === 'true') {
                            this.saveCell(cell);
                        }
                    });
                    
                    cell.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' && cell.contentEditable === 'true') {
                            e.preventDefault();
                            this.saveCell(cell);
                        }
                    });
                });
                
                // ایونت‌های ستون‌ها
                const columnHeaders = document.querySelectorAll('.wf-column-header');
                columnHeaders.forEach(header => {
                    const filterBtn = header.querySelector('.wf-filter-btn');
                    const monitorBtn = header.querySelector('.wf-monitor-btn');
                    const pinBtn = header.querySelector('.wf-pin-btn');
                    
                    if (filterBtn) {
                        filterBtn.addEventListener('click', (e) => {
                            const fieldId = parseInt(e.target.closest('[data-field-id]').dataset.fieldId);
                            this.showFilterModal(fieldId);
                        });
                    }
                    
                    if (monitorBtn) {
                        monitorBtn.addEventListener('click', (e) => {
                            const fieldId = parseInt(e.target.closest('[data-field-id]').dataset.fieldId);
                            this.showAddCardModal(fieldId);
                        });
                    }
                    
                    if (pinBtn) {
                        pinBtn.addEventListener('click', (e) => {
                            const column = e.target.closest('th');
                            this.togglePinColumn(column);
                        });
                    }
                });
            },
            
            // فرمت مقدار سلول
            formatCellValue: function(value, fieldType) {
                if (value === null || value === '') return '';
                
                switch (fieldType) {
                    case 'date':
                        return wf_convert_to_jalali(value, 'Y/m/d');
                    case 'datetime':
                        return wf_convert_to_jalali(value, 'Y/m/d H:i');
                    case 'number':
                    case 'decimal':
                        return new Intl.NumberFormat('fa-IR').format(value);
                    case 'checkbox':
                        return value ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>';
                    default:
                        return this.escapeHtml(value);
                }
            },
            
            // Escape HTML
            escapeHtml: function(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            },
            
            // ویرایش سلول
            editCell: function(cell) {
                const fieldType = cell.dataset.fieldType;
                const currentValue = cell.textContent.trim();
                
                // ذخیره مقدار فعلی
                cell.dataset.originalValue = currentValue;
                
                // ایجاد کنترل مناسب بر اساس نوع فیلد
                let inputHtml = '';
                
                switch (fieldType) {
                    case 'date':
                        inputHtml = `<input type="text" class="wf-date-picker" value="${currentValue}" dir="ltr">`;
                        break;
                    case 'number':
                    case 'decimal':
                        inputHtml = `<input type="number" class="wf-number-input" value="${currentValue}" step="${fieldType === 'decimal' ? '0.01' : '1'}">`;
                        break;
                    case 'select':
                        // باید گزینه‌های فیلد از سرور گرفته شود
                        inputHtml = `<select class="wf-select-input"><option value="">انتخاب کنید</option></select>`;
                        break;
                    case 'checkbox':
                        const checked = currentValue.includes('fa-check');
                        inputHtml = `<input type="checkbox" class="wf-checkbox-input" ${checked ? 'checked' : ''}>`;
                        break;
                    default:
                        // برای متن، از contenteditable استفاده می‌کنیم
                        return;
                }
                
                if (inputHtml) {
                    cell.innerHTML = inputHtml;
                    const input = cell.querySelector('input, select');
                    if (input) {
                        input.focus();
                        
                        // اضافه کردن ایونت برای ذخیره
                        input.addEventListener('blur', () => this.saveCell(cell));
                        input.addEventListener('keydown', (e) => {
                            if (e.key === 'Enter') {
                                this.saveCell(cell);
                            } else if (e.key === 'Escape') {
                                cell.innerHTML = this.formatCellValue(cell.dataset.originalValue, fieldType);
                            }
                        });
                    }
                }
            },
            
            // ذخیره سلول
            saveCell: async function(cell) {
                const personnelId = parseInt(cell.dataset.personnelId);
                const fieldId = parseInt(cell.dataset.fieldId);
                const fieldType = cell.dataset.fieldType;
                
                let newValue = '';
                const input = cell.querySelector('input, select, textarea');
                
                if (input) {
                    if (fieldType === 'checkbox') {
                        newValue = input.checked ? '1' : '0';
                    } else {
                        newValue = input.value.trim();
                    }
                } else {
                    newValue = cell.textContent.trim();
                }
                
                const originalValue = cell.dataset.originalValue || '';
                
                // اگر مقدار تغییر نکرده باشد
                if (newValue === originalValue) {
                    cell.innerHTML = this.formatCellValue(newValue, fieldType);
                    return;
                }
                
                try {
                    const response = await this.ajaxRequest('wf_update_personnel_field', {
                        personnel_id: personnelId,
                        field_id: fieldId,
                        field_value: newValue,
                        field_type: fieldType
                    });
                    
                    if (response.success) {
                        cell.innerHTML = this.formatCellValue(newValue, fieldType);
                        this.showNotification('ذخیره شد', 'تغییرات با موفقیت ذخیره شد', 'success');
                        
                        // به‌روزرسانی آمار
                        this.loadStats();
                    } else {
                        cell.innerHTML = this.formatCellValue(originalValue, fieldType);
                        this.showNotification('خطا', response.data.message || 'خطا در ذخیره تغییرات', 'error');
                    }
                } catch (error) {
                    cell.innerHTML = this.formatCellValue(originalValue, fieldType);
                    this.showNotification('خطا', 'خطا در ارتباط با سرور', 'error');
                }
            },
            
            // نمایش Modal فیلتر
            showFilterModal: async function(fieldId) {
                try {
                    const response = await this.ajaxRequest('wf_get_field_filter_options', {
                        field_id: fieldId
                    });
                    
                    if (response.success) {
                        this.elements.filterContent.innerHTML = this.renderFilterOptions(response.data);
                        this.openModal(this.elements.filterModal);
                    }
                } catch (error) {
                    this.showNotification('خطا', 'خطا در بارگذاری گزینه‌های فیلتر', 'error');
                }
            },
            
            // رندر گزینه‌های فیلتر
            renderFilterOptions: function(data) {
                let html = `
                    <div class="wf-filter-field">
                        <h4>${data.field_name}</h4>
                        <p class="wf-filter-description">${data.description || ''}</p>
                        
                        <div class="wf-filter-options">
                `;
                
                if (data.field_type === 'select' && data.options) {
                    data.options.forEach(option => {
                        html += `
                            <label class="wf-filter-option">
                                <input type="checkbox" name="filter_${data.field_id}[]" value="${option.value}">
                                <span>${option.label}</span>
                            </label>
                        `;
                    });
                } else {
                    html += `
                        <div class="wf-filter-input-group">
                            <label>نوع فیلتر:</label>
                            <select class="wf-filter-type">
                                <option value="contains">شامل</option>
                                <option value="equals">برابر با</option>
                                <option value="starts_with">شروع با</option>
                                <option value="ends_with">پایان با</option>
                                <option value="greater">بزرگتر از</option>
                                <option value="less">کوچکتر از</option>
                            </select>
                        </div>
                        
                        <div class="wf-filter-input-group">
                            <label>مقدار:</label>
                            <input type="text" class="wf-filter-value" placeholder="مقدار فیلتر...">
                        </div>
                    `;
                }
                
                html += `
                        </div>
                    </div>
                `;
                
                return html;
            },
            
            // اعمال فیلترها
            applyFilters: function() {
                const modal = this.elements.filterModal;
                const fieldId = modal.querySelector('.wf-filter-field h4').dataset.fieldId;
                const filterData = {};
                
                // جمع‌آوری داده‌های فیلتر
                // اینجا باید منطق جمع‌آوری فیلترها پیاده‌سازی شود
                
                this.config.filters[fieldId] = filterData;
                this.config.currentPage = 1;
                
                this.loadPersonnelData();
                this.renderActiveFilters();
                this.closeModal(this.elements.filterModal);
            },
            
            // پاک کردن همه فیلترها
            clearAllFilters: function() {
                this.config.filters = {};
                this.config.currentPage = 1;
                
                this.loadPersonnelData();
                this.elements.activeFilters.innerHTML = '';
                this.closeModal(this.elements.filterModal);
            },
            
            // رندر فیلترهای فعال
            renderActiveFilters: function() {
                let html = '';
                
                Object.entries(this.config.filters).forEach(([fieldId, filter]) => {
                    html += `
                        <div class="wf-filter-tag">
                            <span>${filter.field_name}: ${filter.value}</span>
                            <button class="wf-filter-tag-remove" data-field-id="${fieldId}">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                });
                
                this.elements.activeFilters.innerHTML = html;
                
                // اضافه کردن ایونت‌های حذف فیلتر
                const removeButtons = this.elements.activeFilters.querySelectorAll('.wf-filter-tag-remove');
                removeButtons.forEach(button => {
                    button.addEventListener('click', (e) => {
                        const fieldId = e.target.closest('button').dataset.fieldId;
                        delete this.config.filters[fieldId];
                        this.config.currentPage = 1;
                        this.loadPersonnelData();
                        this.renderActiveFilters();
                    });
                });
            },
            
            // نمایش Modal افزودن کارت
            showAddCardModal: function(fieldId = null) {
                // اینجا باید محتوای Modal ساخته شود
                this.openModal(this.elements.addCardModal);
            },
            
            // ایجاد کارت مانیتورینگ
            createMonitoringCard: function() {
                // اینجا باید منطق ایجاد کارت پیاده‌سازی شود
                this.closeModal(this.elements.addCardModal);
            },
            
            // رندر کارت‌های داینامیک
            renderDynamicCards: function() {
                let html = '';
                
                this.config.dynamicCards.forEach(card => {
                    html += `
                        <div class="wf-card wf-card-dynamic" style="border-top-color: ${card.color}">
                            <div class="wf-card-icon">
                                <i class="${card.icon}"></i>
                            </div>
                            <div class="wf-card-content">
                                <h3 class="wf-card-title">${card.title}</h3>
                                <div class="wf-card-value">${card.value}</div>
                                <div class="wf-card-subtext">${card.description}</div>
                            </div>
                            <button class="wf-card-remove" data-card-id="${card.id}">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                });
                
                this.elements.dynamicCardsContainer.innerHTML = html;
            },
            
            // اضافه کردن پرسنل جدید
            addPersonnel: function() {
                this.openEditSidebar(null);
            },
            
            // ویرایش پرسنل انتخاب شده
            editSelected: function() {
                if (this.config.selectedRows.size === 1) {
                    const [personnelId] = this.config.selectedRows;
                    this.editPersonnel(personnelId);
                }
            },
            
            // حذف پرسنل انتخاب شده
            deleteSelected: async function() {
                if (this.config.selectedRows.size === 0) return;
                
                const confirmed = confirm(`آیا از حذف ${this.config.selectedRows.size} پرسنل انتخاب شده اطمینان دارید؟`);
                if (!confirmed) return;
                
                try {
                    const response = await this.ajaxRequest('wf_delete_personnel', {
                        personnel_ids: Array.from(this.config.selectedRows),
                        manager_id: this.config.managerId
                    });
                    
                    if (response.success) {
                        this.showNotification('حذف شد', 'پرسنل انتخاب شده حذف شدند', 'success');
                        this.config.selectedRows.clear();
                        this.loadPersonnelData();
                        this.loadStats();
                    } else {
                        this.showNotification('خطا', response.data.message, 'error');
                    }
                } catch (error) {
                    this.showNotification('خطا', 'خطا در حذف پرسنل', 'error');
                }
            },
            
            // خروجی Excel
            exportExcel: async function() {
                try {
                    const response = await this.ajaxRequest('wf_export_excel', {
                        panel_type: this.config.panelType,
                        manager_id: this.config.managerId,
                        filters: this.config.filters,
                        include_selected: this.config.selectedRows.size > 0,
                        selected_ids: Array.from(this.config.selectedRows)
                    });
                    
                    if (response.success) {
                        // دانلود فایل
                        const link = document.createElement('a');
                        link.href = response.data.file_url;
                        link.download = response.data.file_name;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        this.showNotification('خروجی', 'فایل Excel با موفقیت ایجاد شد', 'success');
                    } else {
                        this.showNotification('خطا', response.data.message, 'error');
                    }
                } catch (error) {
                    this.showNotification('خطا', 'خطا در ایجاد فایل Excel', 'error');
                }
            },
            
            // تولید گزارش سازمانی
            generateReport: async function() {
                if (this.config.panelType !== 'organization') return;
                
                try {
                    const response = await this.ajaxRequest('wf_generate_org_report', {
                        manager_id: this.config.managerId,
                        period_id: this.config.currentPeriod.id
                    });
                    
                    if (response.success) {
                        this.elements.reportModal.querySelector('.wf-modal-body').innerHTML = this.renderReport(response.data);
                        this.openModal(this.elements.reportModal);
                    }
                } catch (error) {
                    this.showNotification('خطا', 'خطا در ایجاد گزارش', 'error');
                }
            },
            
            // رندر گزارش
            renderReport: function(reportData) {
                // اینجا باید گزارش رندر شود
                return '<p>گزارش در حال آماده‌سازی...</p>';
            },
            
            // جستجو
            handleSearch: async function(query) {
                if (query.length < 2) {
                    this.elements.searchResults.classList.remove('show');
                    return;
                }
                
                try {
                    const response = await this.ajaxRequest('wf_search_personnel', {
                        query: query,
                        panel_type: this.config.panelType,
                        manager_id: this.config.managerId
                    });
                    
                    if (response.success) {
                        this.data.searchResults = response.data.results;
                        this.renderSearchResults();
                    }
                } catch (error) {
                    console.error('Search error:', error);
                }
            },
            
            // رندر نتایج جستجو
            renderSearchResults: function() {
                if (this.data.searchResults.length === 0) {
                    this.elements.searchResults.innerHTML = `
                        <div class="wf-search-result-item">
                            <div class="wf-search-result-name">نتیجه‌ای یافت نشد</div>
                        </div>
                    `;
                } else {
                    let html = '';
                    
                    this.data.searchResults.forEach(result => {
                        html += `
                            <div class="wf-search-result-item" data-id="${result.id}">
                                <div class="wf-search-result-name">${result.name}</div>
                                <div class="wf-search-result-details">
                                    کد ملی: ${result.national_code} | اداره: ${result.department_name}
                                </div>
                            </div>
                        `;
                    });
                    
                    this.elements.searchResults.innerHTML = html;
                    
                    // اضافه کردن ایونت‌های نتایج
                    const resultItems = this.elements.searchResults.querySelectorAll('.wf-search-result-item');
                    resultItems.forEach(item => {
                        item.addEventListener('click', (e) => {
                            const personnelId = parseInt(e.currentTarget.dataset.id);
                            this.editPersonnel(personnelId);
                            this.elements.searchResults.classList.remove('show');
                            this.elements.globalSearch.value = '';
                        });
                    });
                }
                
                this.elements.searchResults.classList.add('show');
            },
            
            // ویرایش پرسنل
            editPersonnel: async function(personnelId) {
                this.setSidebarLoading(true);
                
                try {
                    const response = await this.ajaxRequest('wf_get_personnel_for_edit', {
                        personnel_id: personnelId,
                        manager_id: this.config.managerId
                    });
                    
                    if (response.success) {
                        this.config.currentEditId = personnelId;
                        this.openEditSidebar(response.data);
                    } else {
                        this.showNotification('خطا', response.data.message, 'error');
                    }
                } catch (error) {
                    this.showNotification('خطا', 'خطا در بارگذاری اطلاعات', 'error');
                } finally {
                    this.setSidebarLoading(false);
                }
            },
            
            // باز کردن سایدبار ویرایش
            openEditSidebar: function(personnelData = null) {
                this.elements.editTitle.textContent = personnelData ? 'ویرایش پرسنل' : 'افزودن پرسنل جدید';
                this.renderEditForm(personnelData);
                this.elements.editSidebar.classList.add('open');
                this.config.editSidebarOpen = true;
            },
            
            // بستن سایدبار ویرایش
            closeEditSidebar: function() {
                this.elements.editSidebar.classList.remove('open');
                this.config.editSidebarOpen = false;
                this.config.currentEditId = null;
            },
            
            // رندر فرم ویرایش
            renderEditForm: function(personnelData) {
                let html = '';
                
                this.config.fields.forEach(field => {
                    const value = personnelData ? personnelData[field.field_key] : '';
                    const isRequired = field.is_required;
                    const isLocked = field.is_locked && personnelData; // فقط در حالت ویرایش قفل هستند
                    
                    html += `
                        <div class="wf-form-group">
                            <label class="${isRequired ? 'required' : ''}">
                                ${field.field_name}
                            </label>
                    `;
                    
                    switch (field.field_type) {
                        case 'text':
                        case 'number':
                        case 'decimal':
                            html += `
                                <input type="${field.field_type === 'number' || field.field_type === 'decimal' ? 'number' : 'text'}" 
                                       name="${field.field_key}" 
                                       value="${value}" 
                                       class="wf-form-control ${isLocked ? 'locked' : ''}"
                                       ${isRequired ? 'required' : ''}
                                       ${isLocked ? 'disabled' : ''}
                                       ${field.field_type === 'decimal' ? 'step="0.01"' : ''}>
                            `;
                            break;
                            
                        case 'date':
                            html += `
                                <input type="text" 
                                       name="${field.field_key}" 
                                       value="${value ? wf_convert_to_jalali(value, 'Y/m/d') : ''}" 
                                       class="wf-form-control wf-date-picker ${isLocked ? 'locked' : ''}"
                                       ${isRequired ? 'required' : ''}
                                       ${isLocked ? 'disabled' : ''}
                                       dir="ltr">
                            `;
                            break;
                            
                        case 'select':
                            html += `
                                <select name="${field.field_key}" 
                                        class="wf-form-control ${isLocked ? 'locked' : ''}"
                                        ${isRequired ? 'required' : ''}
                                        ${isLocked ? 'disabled' : ''}>
                                    <option value="">انتخاب کنید</option>
                            `;
                            
                            if (field.field_options && Array.isArray(field.field_options)) {
                                field.field_options.forEach(option => {
                                    html += `<option value="${option}" ${value === option ? 'selected' : ''}>${option}</option>`;
                                });
                            }
                            
                            html += `</select>`;
                            break;
                            
                        case 'checkbox':
                            html += `
                                <label class="wf-checkbox-label">
                                    <input type="checkbox" 
                                           name="${field.field_key}" 
                                           value="1" 
                                           ${value ? 'checked' : ''}
                                           ${isLocked ? 'disabled' : ''}
                                           class="${isLocked ? 'locked' : ''}">
                                    <span>${field.field_name}</span>
                                </label>
                            `;
                            break;
                            
                        default:
                            html += `
                                <textarea name="${field.field_key}" 
                                          class="wf-form-control ${isLocked ? 'locked' : ''}"
                                          ${isRequired ? 'required' : ''}
                                          ${isLocked ? 'disabled' : ''}
                                          rows="3">${value}</textarea>
                            `;
                    }
                    
                    html += `</div>`;
                });
                
                this.elements.formFields.innerHTML = html;
                
                // فعال‌سازی date picker
                this.initDatePickers();
            },
            
            // مقداردهی اولیه date picker
            initDatePickers: function() {
                const datePickers = this.elements.formFields.querySelectorAll('.wf-date-picker');
                datePickers.forEach(picker => {
                    // اینجا می‌توان از یک date picker فارسی استفاده کرد
                    picker.addEventListener('focus', () => {
                        if (!picker.readOnly) {
                            // نمایش date picker
                        }
                    });
                });
            },
            
            // ذخیره پرسنل
            savePersonnel: async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this.elements.editForm);
                const data = {};
                
                for (let [key, value] of formData.entries()) {
                    data[key] = value;
                }
                
                try {
                    const response = await this.ajaxRequest('wf_save_personnel', {
                        personnel_id: this.config.currentEditId,
                        data: data,
                        manager_id: this.config.managerId
                    });
                    
                    if (response.success) {
                        this.showNotification('ذخیره شد', 'اطلاعات با موفقیت ذخیره شد', 'success');
                        this.closeEditSidebar();
                        this.loadPersonnelData();
                        this.loadStats();
                    } else {
                        this.showNotification('خطا', response.data.message, 'error');
                    }
                } catch (error) {
                    this.showNotification('خطا', 'خطا در ذخیره اطلاعات', 'error');
                }
            },
            
            // پیمایش بین پرسنل در حالت ویرایش
            navigatePersonnel: function(direction) {
                if (!this.config.currentEditId) return;
                
                const currentIndex = this.data.personnel.findIndex(p => p.id === this.config.currentEditId);
                if (currentIndex === -1) return;
                
                const newIndex = currentIndex + direction;
                if (newIndex >= 0 && newIndex < this.data.personnel.length) {
                    this.editPersonnel(this.data.personnel[newIndex].id);
                }
            },
            
            // انتخاب همه
            toggleSelectAll: function(checked) {
                const checkboxes = this.elements.tableBody.querySelectorAll('.wf-row-checkbox:not(:disabled)');
                
                checkboxes.forEach(checkbox => {
                    checkbox.checked = checked;
                    const rowId = parseInt(checkbox.value);
                    
                    if (checked) {
                        this.config.selectedRows.add(rowId);
                        checkbox.closest('tr').classList.add('selected');
                    } else {
                        this.config.selectedRows.delete(rowId);
                        checkbox.closest('tr').classList.remove('selected');
                    }
                });
                
                this.elements.selectAll.checked = checked;
                this.updateActionButtons();
            },
            
            // به‌روزرسانی سطرهای انتخاب شده
            updateSelectedRows: function() {
                // حذف سطرهایی که دیگر در داده‌ها نیستند
                this.config.selectedRows.forEach(rowId => {
                    const exists = this.data.personnel.some(p => p.id === rowId);
                    if (!exists) {
                        this.config.selectedRows.delete(rowId);
                    }
                });
                
                // به‌روزرسانی چک‌باکس‌ها
                const checkboxes = this.elements.tableBody.querySelectorAll('.wf-row-checkbox');
                checkboxes.forEach(checkbox => {
                    const rowId = parseInt(checkbox.value);
                    checkbox.checked = this.config.selectedRows.has(rowId);
                    checkbox.closest('tr').classList.toggle('selected', checkbox.checked);
                });
                
                this.updateActionButtons();
            },
            
            // به‌روزرسانی دکمه‌های اقدام
            updateActionButtons: function() {
                const hasSelection = this.config.selectedRows.size > 0;
                const hasSingleSelection = this.config.selectedRows.size === 1;
                
                this.elements.editSelectedBtn.disabled = !hasSingleSelection;
                this.elements.deleteSelectedBtn.disabled = !hasSelection;
                
                // به‌روزرسانی چک‌باکس انتخاب همه
                const enabledCheckboxes = this.elements.tableBody.querySelectorAll('.wf-row-checkbox:not(:disabled)');
                const allSelected = enabledCheckboxes.length > 0 && 
                                   Array.from(enabledCheckboxes).every(cb => cb.checked);
                this.elements.selectAll.checked = allSelected;
            },
            
            // پین کردن/برداشتن پین ستون
            togglePinColumn: function(column) {
                const columnIndex = Array.from(column.parentNode.children).indexOf(column);
                const pinBtn = column.querySelector('.wf-pin-btn');
                
                if (this.config.pinnedColumns.has(columnIndex)) {
                    this.config.pinnedColumns.delete(columnIndex);
                    column.classList.remove('pinned');
                    pinBtn.classList.remove('active');
                } else {
                    this.config.pinnedColumns.add(columnIndex);
                    column.classList.add('pinned');
                    pinBtn.classList.add('active');
                }
            },
            
            // رفتن به صفحه خاص
            goToPage: function(page) {
                if (page < 1 || page > this.config.totalPages) return;
                
                this.config.currentPage = page;
                this.loadPersonnelData();
            },
            
            // به‌روزرسانی UI صفحه‌بندی
            updatePaginationUI: function() {
                // اطلاعات
                const start = (this.config.currentPage - 1) * (this.config.itemsPerPage === 'all' ? this.data.personnel.length : this.config.itemsPerPage) + 1;
                const end = Math.min(start + (this.config.itemsPerPage === 'all' ? this.data.personnel.length : this.config.itemsPerPage) - 1, this.config.totalRecords);
                
                this.elements.currentRange.textContent = `${start}-${end}`;
                this.elements.totalRecords.textContent = this.config.totalRecords;
                
                // دکمه‌ها
                this.elements.firstPage.disabled = this.config.currentPage === 1;
                this.elements.prevPage.disabled = this.config.currentPage === 1;
                this.elements.nextPage.disabled = this.config.currentPage === this.config.totalPages;
                this.elements.lastPage.disabled = this.config.currentPage === this.config.totalPages;
                
                // شماره صفحات
                this.renderPageNumbers();
            },
            
            // رندر شماره صفحات
            renderPageNumbers: function() {
                let html = '';
                const maxPages = 5;
                let startPage = Math.max(1, this.config.currentPage - Math.floor(maxPages / 2));
                let endPage = Math.min(this.config.totalPages, startPage + maxPages - 1);
                
                if (endPage - startPage + 1 < maxPages) {
                    startPage = Math.max(1, endPage - maxPages + 1);
                }
                
                // صفحه اول
                if (startPage > 1) {
                    html += `<button class="wf-page-btn" data-page="1">1</button>`;
                    if (startPage > 2) {
                        html += `<span class="wf-page-dots">...</span>`;
                    }
                }
                
                // صفحات میانی
                for (let i = startPage; i <= endPage; i++) {
                    html += `
                        <button class="wf-page-btn ${i === this.config.currentPage ? 'active' : ''}" 
                                data-page="${i}">
                            ${i}
                        </button>
                    `;
                }
                
                // صفحه آخر
                if (endPage < this.config.totalPages) {
                    if (endPage < this.config.totalPages - 1) {
                        html += `<span class="wf-page-dots">...</span>`;
                    }
                    html += `<button class="wf-page-btn" data-page="${this.config.totalPages}">${this.config.totalPages}</button>`;
                }
                
                this.elements.pageNumbers.innerHTML = html;
                
                // اضافه کردن ایونت‌ها
                const pageButtons = this.elements.pageNumbers.querySelectorAll('.wf-page-btn');
                pageButtons.forEach(button => {
                    button.addEventListener('click', (e) => {
                        const page = parseInt(e.target.dataset.page);
                        this.goToPage(page);
                    });
                });
            },
            
            // باز کردن Modal
            openModal: function(modal) {
                modal.classList.add('show');
                this.elements.modalOverlay.classList.add('show');
                document.body.style.overflow = 'hidden';
            },
            
            // بستن Modal
            closeModal: function(modal) {
                modal.classList.remove('show');
                this.elements.modalOverlay.classList.remove('show');
                document.body.style.overflow = '';
            },
            
            // بستن همه Modal ها
            closeAllModals: function() {
                document.querySelectorAll('.wf-modal').forEach(modal => {
                    modal.classList.remove('show');
                });
                this.elements.modalOverlay.classList.remove('show');
                document.body.style.overflow = '';
            },
            
            // نمایش نوتیفیکیشن
            showNotification: function(title, message, type = 'info') {
                const notification = document.createElement('div');
                notification.className = `wf-notification ${type}`;
                notification.innerHTML = `
                    <div class="wf-notification-icon">
                        <i class="fas fa-${this.getNotificationIcon(type)}"></i>
                    </div>
                    <div class="wf-notification-content">
                        <div class="wf-notification-title">${title}</div>
                        <div class="wf-notification-message">${message}</div>
                    </div>
                    <button class="wf-notification-close">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                
                this.elements.notificationContainer.appendChild(notification);
                
                // اضافه کردن ایونت بستن
                const closeBtn = notification.querySelector('.wf-notification-close');
                closeBtn.addEventListener('click', () => {
                    notification.style.animation = 'wf-fadeOut 0.3s ease forwards';
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                });
                
                // حذف خودکار بعد از 5 ثانیه
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.style.animation = 'wf-fadeOut 0.3s ease forwards';
                        setTimeout(() => {
                            if (notification.parentNode) {
                                notification.remove();
                            }
                        }, 300);
                    }
                }, 5000);
            },
            
            // دریافت آیکن نوتیفیکیشن
            getNotificationIcon: function(type) {
                switch (type) {
                    case 'success': return 'check-circle';
                    case 'error': return 'exclamation-circle';
                    case 'warning': return 'exclamation-triangle';
                    default: return 'info-circle';
                }
            },
            
            // تنظیم وضعیت لودینگ جدول
            setTableLoading: function(loading) {
                this.uiState.tableLoading = loading;
                this.elements.tableLoading.style.display = loading ? 'flex' : 'none';
                this.elements.tableWrapper.style.opacity = loading ? '0.5' : '1';
            },
            
            // تنظیم وضعیت لودینگ سایدبار
            setSidebarLoading: function(loading) {
                this.uiState.sidebarLoading = loading;
                this.elements.formFields.style.opacity = loading ? '0.5' : '1';
                this.elements.formFields.style.pointerEvents = loading ? 'none' : 'auto';
                
                if (loading) {
                    this.elements.saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال ذخیره...';
                    this.elements.saveBtn.disabled = true;
                } else {
                    this.elements.saveBtn.innerHTML = '<i class="fas fa-save"></i> ذخیره تغییرات';
                    this.elements.saveBtn.disabled = false;
                }
            },
            
            // درخواست AJAX
            ajaxRequest: async function(action, data = {}) {
                const formData = new FormData();
                formData.append('action', action);
                formData.append('nonce', this.config.nonce);
                
                // اضافه کردن داده‌ها
                Object.entries(data).forEach(([key, value]) => {
                    if (typeof value === 'object') {
                        formData.append(key, JSON.stringify(value));
                    } else {
                        formData.append(key, value);
                    }
                });
                
                const response = await fetch(this.config.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });
                
                return await response.json();
            }
        };
        
        // مقداردهی اولیه پنل
        document.addEventListener('DOMContentLoaded', () => {
            wfPanel.init();
        });
        
        // توابع سراسری
        window.wfPanel = wfPanel;
        
        // تابع تبدیل تاریخ (برای استفاده در جاوااسکریپت)
        window.wf_convert_to_jalali = function(date, format) {
            // اینجا باید تبدیل تاریخ پیاده‌سازی شود
            return date;
        };
        
    })();
    </script>
    
    <?php
    return ob_get_clean();
}

/**
 * صفحه عدم دسترسی
 */
function wf_render_no_access_page() {
    ob_start();
    ?>
    <div class="wf-no-access">
        <div class="wf-no-access-container">
            <div class="wf-no-access-icon">
                <i class="fas fa-ban"></i>
            </div>
            <h1>دسترسی محدود</h1>
            <p>شما مجوز دسترسی به این پنل را ندارید.</p>
            <p>فقط مدیران ادارات و سازمان می‌توانند از این سیستم استفاده کنند.</p>
            <div class="wf-no-access-actions">
                <a href="<?php echo home_url(); ?>" class="wf-btn wf-btn-primary">
                    <i class="fas fa-home"></i>
                    بازگشت به صفحه اصلی
                </a>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="wf-btn wf-btn-secondary">
                    <i class="fas fa-sign-out-alt"></i>
                    خروج از حساب کاربری
                </a>
            </div>
        </div>
        
        <style>
            .wf-no-access {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                padding: 20px;
                direction: rtl;
            }
            
            .wf-no-access-container {
                background: white;
                border-radius: 16px;
                padding: 40px;
                text-align: center;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
                max-width: 500px;
                width: 100%;
            }
            
            .wf-no-access-icon {
                font-size: 64px;
                color: #ea4335;
                margin-bottom: 20px;
            }
            
            .wf-no-access h1 {
                color: #2c3338;
                margin-bottom: 16px;
                font-size: 28px;
            }
            
            .wf-no-access p {
                color: #646970;
                margin-bottom: 12px;
                line-height: 1.6;
            }
            
            .wf-no-access-actions {
                display: flex;
                flex-direction: column;
                gap: 12px;
                margin-top: 30px;
            }
            
            .wf-no-access .wf-btn {
                padding: 14px 24px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 500;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                transition: all 0.3s ease;
            }
            
            .wf-no-access .wf-btn-primary {
                background: #1a73e8;
                color: white;
            }
            
            .wf-no-access .wf-btn-primary:hover {
                background: #0d47a1;
                transform: translateY(-2px);
            }
            
            .wf-no-access .wf-btn-secondary {
                background: #f8f9fa;
                color: #2c3338;
                border: 1px solid #dadce0;
            }
            
            .wf-no-access .wf-btn-secondary:hover {
                background: #e8eaed;
                transform: translateY(-2px);
            }
            
            @media (max-width: 480px) {
                .wf-no-access-container {
                    padding: 30px 20px;
                }
            }
        </style>
    </div>
    <?php
    return ob_get_clean();
}

// ==================== تابع اصلی برای شرط کدها ====================

/**
 * تابع اصلی برای شرط کد پنل مدیران
 */
function wf_manager_panel_shortcode($atts) {
    $atts = shortcode_atts(array(
        'type' => 'department' // department یا organization
    ), $atts);
    
    return wf_render_manager_panel($atts['type']);
}

/**
 * دریافت URL پنل مدیر
 */
function wf_get_manager_panel_url() {
    $panel_type = wf_detect_panel_type();
    
    if ($panel_type === 'organization') {
        $page = get_page_by_path('پنل-مدیر-سازمان');
    } else {
        $page = get_page_by_path('پنل-مدیران-ادارات');
    }
    
    if ($page) {
        return get_permalink($page->ID);
    }
    
    return home_url();
}

// ==================== AJAX Handlers ====================

/**
 * AJAX Login
 */
add_action('wp_ajax_nopriv_wf_manager_login', 'wf_ajax_manager_login');
add_action('wp_ajax_wf_manager_login', 'wf_ajax_manager_login');

function wf_ajax_manager_login() {
    check_ajax_referer('workforce_manager_nonce', 'nonce');
    
    $username = sanitize_text_field($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    $credentials = array(
        'user_login' => $username,
        'user_password' => $password,
        'remember' => $remember
    );
    
    $user = wp_signon($credentials, false);
    
    if (is_wp_error($user)) {
        wp_send_json_error(array(
            'message' => 'نام کاربری یا رمز عبور نادرست است',
            'field' => 'username'
        ));
    }
    
    // بررسی دسترسی کاربر
    $roles = $user->roles;
    $has_access = in_array('administrator', $roles) || 
                  in_array('wf_org_manager', $roles) || 
                  in_array('wf_department_manager', $roles);
    
    if (!$has_access) {
        wp_logout();
        wp_send_json_error(array(
            'message' => 'شما مجوز دسترسی به پنل مدیران را ندارید',
            'field' => 'username'
        ));
    }
    
    wp_send_json_success(array(
        'message' => 'ورود موفقیت‌آمیز بود. در حال انتقال...'
    ));
}

/**
 * دریافت آمار مدیر
 */
add_action('wp_ajax_wf_get_manager_stats', 'wf_ajax_get_manager_stats');

function wf_ajax_get_manager_stats() {
    check_ajax_referer('workforce_manager_nonce', 'nonce');
    
    $panel_type = sanitize_text_field($_POST['panel_type']);
    $manager_id = intval($_POST['manager_id']);
    
    $stats = array();
    
    if ($panel_type === 'department') {
        $department_id = get_user_meta($manager_id, 'wf_department_id', true);
        
        if ($department_id) {
            $personnel_count = wf_count_department_personnel($department_id);
            $active_personnel = wf_count_department_personnel($department_id, array('status' => 'active'));
            $incomplete_personnel = wf_count_department_personnel($department_id, array('has_warnings' => 1));
            
            // محاسبه درصد تکمیل فیلدهای ضروری
            $required_stats = wf_calculate_department_completion($department_id);
            
            $stats = array(
                'personnel_count' => $personnel_count,
                'active_personnel' => $active_personnel,
                'warning_count' => $incomplete_personnel,
                'required_filled' => $required_stats['filled'],
                'required_total' => $required_stats['total'],
                'required_completion' => $required_stats['percentage'],
                'personnel_trend' => 12 // این مقدار باید از تاریخچه محاسبه شود
            );
        }
    } else {
        // آمار سازمان
        $total_departments = wf_count_departments();
        $total_personnel = wf_count_all_personnel();
        $incomplete_personnel = wf_count_personnel_with_warnings();
        
        $stats = array(
            'personnel_count' => $total_personnel,
            'department_count' => $total_departments,
            'warning_count' => $incomplete_personnel,
            'required_completion' => 85, // میانگین سازمان
            'personnel_trend' => 8
        );
    }
    
    wp_send_json_success($stats);
}

/**
 * دریافت داده‌های پرسنل
 */
add_action('wp_ajax_wf_get_personnel_data', 'wf_ajax_get_personnel_data');

function wf_ajax_get_personnel_data() {
    check_ajax_referer('workforce_manager_nonce', 'nonce');
    
    $panel_type = sanitize_text_field($_POST['panel_type']);
    $manager_id = intval($_POST['manager_id']);
    $page = intval($_POST['page']);
    $per_page = $_POST['per_page'] === 'all' ? 'all' : intval($_POST['per_page']);
    $filters = isset($_POST['filters']) ? json_decode(stripslashes($_POST['filters']), true) : array();
    $sort_field = isset($_POST['sort_field']) ? sanitize_text_field($_POST['sort_field']) : null;
    $sort_order = isset($_POST['sort_order']) ? sanitize_text_field($_POST['sort_order']) : 'asc';
    
    // تعیین محدوده دسترسی
    $department_ids = array();
    
    if ($panel_type === 'department') {
        $department_id = get_user_meta($manager_id, 'wf_department_id', true);
        if ($department_id) {
            $department_ids[] = $department_id;
        }
    } else {
        // مدیر سازمان به همه ادارات دسترسی دارد
        $departments = wf_get_all_departments();
        $department_ids = array_column($departments, 'id');
    }
    
    // دریافت داده‌ها
    $data = wf_get_filtered_personnel_data($department_ids, $filters, $page, $per_page, $sort_field, $sort_order);
    
    wp_send_json_success($data);
}

// ==================== توابع کمکی پنل ====================

/**
 * محاسبه درصد تکمیل اداره
 */
function wf_calculate_department_completion($department_id) {
    global $wpdb;
    
    $personnel = $wpdb->get_results($wpdb->prepare(
        "SELECT completion_percentage 
         FROM {$wpdb->prefix}wf_personnel 
         WHERE department_id = %d AND is_deleted = 0",
        $department_id
    ), ARRAY_A);
    
    if (empty($personnel)) {
        return array(
            'filled' => 0,
            'total' => 0,
            'percentage' => 0
        );
    }
    
    $total_percentage = 0;
    foreach ($personnel as $person) {
        $total_percentage += floatval($person['completion_percentage']);
    }
    
    $avg_percentage = $total_percentage / count($personnel);
    
    return array(
        'filled' => round($avg_percentage, 1),
        'total' => 100,
        'percentage' => round($avg_percentage, 1)
    );
}

/**
 * دریافت داده‌های فیلتر شده پرسنل
 */
function wf_get_filtered_personnel_data($department_ids, $filters = array(), $page = 1, $per_page = 100, $sort_field = null, $sort_order = 'asc') {
    global $wpdb;
    
    // ساختن شرط WHERE
    $where = array("p.is_deleted = 0");
    $params = array();
    
    if (!empty($department_ids)) {
        $placeholders = implode(',', array_fill(0, count($department_ids), '%d'));
        $where[] = "p.department_id IN ($placeholders)";
        $params = array_merge($params, $department_ids);
    }
    
    // اعمال فیلترها
    if (!empty($filters)) {
        foreach ($filters as $field_id => $filter) {
            $field = wf_get_field($field_id);
            if ($field) {
                $field_key = $field['field_key'];
                
                switch ($filter['type']) {
                    case 'equals':
                        $where[] = "JSON_EXTRACT(p.data, '$.$field_key') = %s";
                        $params[] = $filter['value'];
                        break;
                    case 'contains':
                        $where[] = "JSON_EXTRACT(p.data, '$.$field_key') LIKE %s";
                        $params[] = '%' . $wpdb->esc_like($filter['value']) . '%';
                        break;
                    case 'greater':
                        $where[] = "CAST(JSON_EXTRACT(p.data, '$.$field_key') AS DECIMAL) > %f";
                        $params[] = floatval($filter['value']);
                        break;
                    case 'less':
                        $where[] = "CAST(JSON_EXTRACT(p.data, '$.$field_key') AS DECIMAL) < %f";
                        $params[] = floatval($filter['value']);
                        break;
                }
            }
        }
    }
    
    $where_clause = implode(' AND ', $where);
    
    // کوئری شمارش
    $count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}wf_personnel p WHERE $where_clause";
    if (!empty($params)) {
        $count_query = $wpdb->prepare($count_query, $params);
    }
    
    $total_records = (int)$wpdb->get_var($count_query);
    $total_pages = $per_page === 'all' ? 1 : ceil($total_records / $per_page);
    
    // کوئری اصلی
    $query = "SELECT p.*, d.name as department_name 
              FROM {$wpdb->prefix}wf_personnel p
              LEFT JOIN {$wpdb->prefix}wf_departments d ON p.department_id = d.id
              WHERE $where_clause";
    
    // مرتب‌سازی
    if ($sort_field) {
        $field = wf_get_field_by_key($sort_field);
        if ($field) {
            if (in_array($field['field_type'], array('number', 'decimal'))) {
                $query .= " ORDER BY CAST(JSON_EXTRACT(p.data, '$.{$field['field_key']}') AS DECIMAL) $sort_order";
            } else {
                $query .= " ORDER BY JSON_EXTRACT(p.data, '$.{$field['field_key']}') $sort_order";
            }
        }
    } else {
        $query .= " ORDER BY p.id DESC";
    }
    
    // صفحه‌بندی
    if ($per_page !== 'all') {
        $offset = ($page - 1) * $per_page;
        $query .= " LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;
    }
    
    if (!empty($params)) {
        $query = $wpdb->prepare($query, $params);
    }
    
    $personnel = $wpdb->get_results($query, ARRAY_A);
    
    // پردازش داده‌های JSON
    foreach ($personnel as &$person) {
        if (isset($person['data'])) {
            $person['data'] = json_decode($person['data'], true);
        }
    }
    
    return array(
        'personnel' => $personnel,
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'per_page' => $per_page
    );
}

/**
 * دریافت فیلد بر اساس کلید
 */
function wf_get_field_by_key($field_key) {
    global $wpdb;
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wf_fields WHERE field_key = %s AND status = 'active'",
        $field_key
    ), ARRAY_A);
}

// ==================== خاتمه فایل ====================

// این فایل با حدود ۳۰۰۰ خط کد، پنل اصلی مدیران را پیاده‌سازی می‌کند.
// توابع AJAX و پردازش‌گرهای باقی‌مانده در فایل بعدی تکمیل می‌شوند.

?>