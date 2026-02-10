<?php
/**
 * پنل اصلی مدیران (اداره و سازمان)
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * پنل مدیر اداره
 */
function workforce_dept_manager_panel($user_id) {
    $current_user = wp_get_current_user();
    $user_departments = workforce_get_user_departments($user_id);
    
    if (empty($user_departments)) {
        return '<div class="workforce-error">شما به هیچ اداره‌ای دسترسی ندارید. لطفا با مدیر سیستم تماس بگیرید.</div>';
    }
    
    // مدیر ممکن است چندین اداره داشته باشد
    $department = $user_departments[0];
    $department_id = $department->id;
    
    // گرفتن دوره فعال
    $active_period = workforce_get_active_period();
    $period_id = $active_period ? $active_period->id : null;
    
    // گرفتن فیلدها
    $fields = workforce_get_all_fields();
    
    ob_start();
    ?>
    <div class="workforce-manager-panel" data-dept-id="<?php echo esc_attr($department_id); ?>" data-period-id="<?php echo esc_attr($period_id); ?>">
        <!-- هدر هوشمند -->
        <div class="workforce-header">
            <div class="header-content">
                <div class="welcome-section">
                    <div class="welcome-icon">👋</div>
                    <div class="welcome-text">
                        <h2>خوش آمدید، <?php echo esc_html($current_user->display_name); ?></h2>
                        <div class="welcome-details">
<span class="detail-item">
    <span class="detail-icon">🏢</span>
    <span class="detail-text"><?php echo esc_html($department->name); ?>
        <?php
        // نمایش مدیران
        if (!empty($department->managers)) {
            $manager_names = [];
            foreach ($department->managers as $dept_manager) {
                $mgr_user = get_userdata($dept_manager->user_id);
                if ($mgr_user) {
                    $prefix = $dept_manager->is_primary ? '⭐ ' : '';
                    $manager_names[] = $prefix . $mgr_user->display_name;
                }
            }
            echo '<br><small>👤 ' . esc_html(implode('، ', array_slice($manager_names, 0, 2))) . 
                 (count($manager_names) > 2 ? ' و ' . (count($manager_names) - 2) . ' نفر دیگر' : '') . 
                 '</small>';
        } else {
            echo '<br><small>👤 تعیین نشده</small>';
        }
        ?>
    </span>
</span>
                            <span class="detail-item">
                                <span class="detail-icon">📅</span>
                                <span class="detail-text">دوره: <?php echo $active_period ? esc_html($active_period->name) : 'تعیین نشده'; ?></span>
                            </span>
                            <span class="detail-item">
                                <span class="detail-icon">🕒</span>
                                <span class="detail-text">امروز: <?php echo esc_html(workforce_today_jalali()); ?></span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="header-actions">
                    <button type="button" class="button button-primary" onclick="showAddPersonnelModal()">
                        <span class="action-icon">➕</span>
                        افزودن پرسنل
                    </button>
                    <button type="button" class="button button-secondary" onclick="exportToExcel()">
                        <span class="action-icon">📤</span>
                        خروجی اکسل
                    </button>
                    <button type="button" class="button" onclick="refreshData()">
                        <span class="action-icon">🔄</span>
                        به‌روزرسانی
                    </button>
                </div>
            </div>
        </div>
        
        <!-- کارت‌های مانیتورینگ -->
        <div class="workforce-monitoring-cards" id="monitoringCards">
            <!-- کارت‌های ثابت -->
            <div class="monitoring-card card-blue" id="cardPersonnelCount">
                <div class="card-icon">👥</div>
                <div class="card-content">
                    <h3>وضعیت پرسنل</h3>
                    <p class="card-number" id="personnelCount">0</p>
                    <p class="card-sub">نفر</p>
                </div>
            </div>
            
            <div class="monitoring-card card-dynamic" id="cardRequiredFields">
                <div class="card-icon">📊</div>
                <div class="card-content">
                    <h3>فیلدهای ضروری</h3>
                    <p class="card-number" id="requiredFieldsPercent">0%</p>
                    <p class="card-sub">پر شده</p>
                </div>
                <div class="card-progress">
                    <div class="progress-bar" id="requiredFieldsProgress"></div>
                </div>
            </div>
            
            <div class="monitoring-card card-red" id="cardWarnings">
                <div class="card-icon">⚠️</div>
                <div class="card-content">
                    <h3>هشدار</h3>
                    <p class="card-number" id="warningCount">0</p>
                    <p class="card-sub">اطلاعات ناقص</p>
                </div>
            </div>
            
            <!-- کارت‌های داینامیک اینجا اضافه می‌شوند -->
        </div>
        
        <!-- جدول اصلی -->
        <div class="workforce-main-table">
            <!-- نوار ابزار جدول -->
            <div class="table-toolbar">
                <div class="toolbar-left">
                    <div class="records-per-page">
                        <label>نمایش:</label>
                        <select id="recordsPerPage" onchange="changeRecordsPerPage(this.value)">
                            <option value="25">۲۵</option>
                            <option value="50">۵۰</option>
                            <option value="100">۱۰۰</option>
                            <option value="all">همه</option>
                        </select>
                    </div>
                    
                    <div class="record-counter" id="recordCounter">
                        نمایش ۰-۰ از ۰ رکورد
                    </div>
                </div>
                
                <div class="toolbar-right">
                    <div class="search-box">
                        <input type="text" id="globalSearch" placeholder="جستجو در همه فیلدها..." onkeyup="performGlobalSearch(this.value)">
                        <span class="search-icon">🔍</span>
                    </div>
                    
                    <button type="button" class="button button-small" onclick="clearAllFilters()">
                        <span class="button-icon">🗑️</span>
                        پاک کردن فیلترها
                    </button>
                </div>
            </div>
            
            <!-- جدول داده‌ها -->
            <div class="table-container">
                <table class="workforce-data-table" id="personnelTable">
                    <thead>
                        <tr>
                            <th class="checkbox-col">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                            </th>
                            <th class="row-number">ردیف</th>
                            
                            <?php foreach ($fields as $field): ?>
                                <?php
                                $col_class = '';
                                if ($field->is_required) $col_class .= ' required-col';
                                if ($field->is_locked) $col_class .= ' locked-col';
                                if ($field->is_monitoring) $col_class .= ' monitoring-col';
                                ?>
                                <th class="<?php echo esc_attr($col_class); ?>" data-field-id="<?php echo esc_attr($field->id); ?>" data-field-name="<?php echo esc_attr($field->field_name); ?>">
                                    <div class="column-header">
                                        <span class="column-title"><?php echo esc_html($field->field_label); ?></span>
                                        <div class="column-actions">
                                            <?php if ($field->is_monitoring): ?>
                                                <button type="button" class="column-action-btn" onclick="createMonitoringCard(<?php echo $field->id; ?>, '<?php echo esc_attr($field->field_label); ?>')" title="ساخت کارت مانیتورینگ">
                                                    📊
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="column-action-btn" onclick="showColumnFilter(<?php echo $field->id; ?>)" title="فیلتر ستونی">
                                                🔍
                                            </button>
                                            <button type="button" class="column-action-btn pin-btn" onclick="togglePinColumn(this)" title="ثابت کردن ستون">
                                                📌
                                            </button>
                                        </div>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                            
                            <th class="actions-col">عملیات</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <!-- داده‌ها از طریق AJAX بارگذاری می‌شوند -->
                    </tbody>
                </table>
            </div>
            
            <!-- صفحه‌بندی -->
            <div class="table-pagination">
                <div class="pagination-info" id="paginationInfo"></div>
                <div class="pagination-controls">
                    <button type="button" class="pagination-btn" onclick="goToPage(1)" disabled id="firstPage">اولین</button>
                    <button type="button" class="pagination-btn" onclick="goToPreviousPage()" disabled id="prevPage">قبلی</button>
                    
                    <div class="page-numbers" id="pageNumbers"></div>
                    
                    <button type="button" class="pagination-btn" onclick="goToNextPage()" disabled id="nextPage">بعدی</button>
                    <button type="button" class="pagination-btn" onclick="goToLastPage()" disabled id="lastPage">آخرین</button>
                </div>
            </div>
        </div>
        
        <!-- فرم سمت راست برای ویرایش -->
        <div class="workforce-side-form" id="sideForm">
            <div class="side-form-header">
                <h3 id="formTitle">ویرایش پرسنل</h3>
                <button type="button" class="side-form-close" onclick="hideSideForm()">&times;</button>
            </div>
            <div class="side-form-body" id="sideFormBody">
                <!-- محتوای فرم اینجا بارگذاری می‌شود -->
            </div>
            <div class="side-form-footer">
                <div class="form-navigation">
                    <button type="button" class="button button-small" onclick="navigatePersonnel('prev')" id="prevBtn">⏮️ قبلی</button>
                    <button type="button" class="button button-primary" onclick="savePersonnelForm()">ذخیره</button>
                    <button type="button" class="button button-small" onclick="navigatePersonnel('next')" id="nextBtn">بعدی ⏭️</button>
                </div>
                <button type="button" class="button button-link" onclick="hideSideForm()">انصراف</button>
            </div>
        </div>
    </div>
    
    <!-- مودال افزودن پرسنل -->
    <div id="addPersonnelModal" class="workforce-modal">
        <div class="workforce-modal-content wide-modal">
            <div class="workforce-modal-header">
                <h2>افزودن پرسنل جدید</h2>
                <span class="workforce-modal-close" onclick="hideAddPersonnelModal()">&times;</span>
            </div>
            <div class="workforce-modal-body">
                <form id="addPersonnelForm">
                    <div class="form-sections">
                        <div class="form-section">
                            <h3>اطلاعات پایه</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="new_national_code">کدملی <span class="required">*</span></label>
                                    <input type="text" id="new_national_code" name="national_code" required pattern="[0-9]{10}" maxlength="10">
                                    <div class="validation-message" id="nationalCodeValidation"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_first_name">نام <span class="required">*</span></label>
                                    <input type="text" id="new_first_name" name="first_name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_last_name">نام خانوادگی <span class="required">*</span></label>
                                    <input type="text" id="new_last_name" name="last_name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_employment_date">تاریخ استخدام <span class="required">*</span></label>
                                    <input type="text" id="new_employment_date" name="employment_date" class="jdatepicker" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_employment_type">نوع استخدام</label>
                                    <select id="new_employment_type" name="employment_type">
                                        <option value="permanent">دائمی</option>
                                        <option value="contract">پیمانی</option>
                                        <option value="temporary">موقت</option>
                                        <option value="project">پروژه‌ای</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_status">وضعیت</label>
                                    <select id="new_status" name="status">
                                        <option value="active">فعال</option>
                                        <option value="inactive">غیرفعال</option>
                                        <option value="suspended">تعلیق</option>
                                        <option value="retired">بازنشسته</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>اطلاعات تکمیلی</h3>
                            <div class="form-grid" id="additionalFields">
                                <!-- فیلدهای اضافی اینجا بارگذاری می‌شوند -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="button button-primary" onclick="submitAddPersonnelForm()">ثبت درخواست</button>
                        <button type="button" class="button" onclick="hideAddPersonnelModal()">انصراف</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- مودال فیلتر ستونی -->
    <div id="columnFilterModal" class="workforce-modal">
        <div class="workforce-modal-content">
            <div class="workforce-modal-header">
                <h2 id="filterModalTitle">فیلتر ستون</h2>
                <span class="workforce-modal-close" onclick="hideColumnFilterModal()">&times;</span>
            </div>
            <div class="workforce-modal-body">
                <div id="filterContent">
                    <!-- محتوای فیلتر اینجا بارگذاری می‌شود -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- اسکریپت‌ها -->
    <script>
    // داده‌های جهانی
    var workforceData = {
        currentPage: 1,
        recordsPerPage: 25,
        totalRecords: 0,
        totalPages: 0,
        currentFilters: {},
        currentSearch: '',
        selectedRows: [],
        currentPersonnelId: null,
        pinnedColumns: [],
        monitoringCards: [],
        departmentId: <?php echo esc_js($department_id); ?>,
        periodId: <?php echo esc_js($period_id); ?>,
        fields: <?php echo json_encode($fields); ?>
    };
    
    // بارگذاری اولیه داده‌ها
    document.addEventListener('DOMContentLoaded', function() {
        loadTableData();
        updateMonitoringCards();
        setupEventListeners();
        setupKeyboardShortcuts();
    });
    
    // بارگذاری داده‌های جدول
    function loadTableData() {
        var params = {
            action: 'workforce_get_table_data',
            department_id: workforceData.departmentId,
            period_id: workforceData.periodId,
            page: workforceData.currentPage,
            per_page: workforceData.recordsPerPage,
            filters: workforceData.currentFilters,
            search: workforceData.currentSearch,
            nonce: workforce_ajax.nonce
        };
        
        jQuery.ajax({
            url: workforce_ajax.ajax_url,
            type: 'POST',
            data: params,
            success: function(response) {
                if (response.success) {
                    renderTable(response.data);
                    updatePagination(response.data.pagination);
                    updateRecordCounter(response.data.pagination);
                }
            }
        });
    }
    
    // رندر جدول
    function renderTable(data) {
        var tbody = document.getElementById('tableBody');
        tbody.innerHTML = '';
        
        if (data.rows.length === 0) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td colspan="' + (workforceData.fields.length + 3) + '" class="no-data">داده‌ای یافت نشد.</td>';
            tbody.appendChild(tr);
            return;
        }
        
        data.rows.forEach(function(row, index) {
            var tr = document.createElement('tr');
            tr.dataset.personnelId = row.id;
            if (row.is_deleted) {
                tr.classList.add('deleted-row');
            }
            
            // ستون انتخاب
            var tdCheckbox = document.createElement('td');
            tdCheckbox.className = 'checkbox-col';
            tdCheckbox.innerHTML = '<input type="checkbox" class="row-checkbox" onchange="toggleRowSelection(' + row.id + ', this)">';
            tr.appendChild(tdCheckbox);
            
            // ستون شماره ردیف
            var tdNumber = document.createElement('td');
            tdNumber.className = 'row-number';
            tdNumber.textContent = ((workforceData.currentPage - 1) * workforceData.recordsPerPage) + index + 1;
            tr.appendChild(tdNumber);
            
            // ستون‌های داده
            workforceData.fields.forEach(function(field) {
                var td = document.createElement('td');
                var value = row.meta[field.id] || row.meta[field.field_name] || '';
                
                if (field.is_locked) {
                    td.classList.add('locked-cell');
                }
                if (field.is_required && !value) {
                    td.classList.add('required-empty');
                }
                
                td.textContent = value;
                td.title = value;
                tr.appendChild(td);
            });
            
            // ستون عملیات
            var tdActions = document.createElement('td');
            tdActions.className = 'actions-col';
            tdActions.innerHTML = `
                <button type="button" class="action-btn edit-btn" onclick="editPersonnel(${row.id})" title="ویرایش">
                    ✏️
                </button>
                <button type="button" class="action-btn view-btn" onclick="viewPersonnel(${row.id})" title="مشاهده">
                    👁️
                </button>
                <button type="button" class="action-btn delete-btn" onclick="requestDeletePersonnel(${row.id})" title="حذف">
                    🗑️
                </button>
            `;
            tr.appendChild(tdActions);
            
            // کلیک روی ردیف
            tr.addEventListener('click', function(e) {
                if (!e.target.matches('.row-checkbox, .action-btn, .action-btn *')) {
                    editPersonnel(row.id);
                }
            });
            
            tbody.appendChild(tr);
        });
    }
    
    // به‌روزرسانی صفحه‌بندی
    function updatePagination(pagination) {
        workforceData.totalRecords = pagination.total_records;
        workforceData.totalPages = pagination.total_pages;
        
        var info = document.getElementById('paginationInfo');
        var pageNumbers = document.getElementById('pageNumbers');
        var firstBtn = document.getElementById('firstPage');
        var prevBtn = document.getElementById('prevPage');
        var nextBtn = document.getElementById('nextPage');
        var lastBtn = document.getElementById('lastPage');
        
        // به‌روزرسانی دکمه‌ها
        firstBtn.disabled = workforceData.currentPage === 1;
        prevBtn.disabled = workforceData.currentPage === 1;
        nextBtn.disabled = workforceData.currentPage === workforceData.totalPages;
        lastBtn.disabled = workforceData.currentPage === workforceData.totalPages;
        
        // ایجاد شماره صفحات
        pageNumbers.innerHTML = '';
        var startPage = Math.max(1, workforceData.currentPage - 2);
        var endPage = Math.min(workforceData.totalPages, startPage + 4);
        
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }
        
        for (var i = startPage; i <= endPage; i++) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'page-number-btn';
            if (i === workforceData.currentPage) {
                btn.classList.add('active');
            }
            btn.textContent = i;
            btn.onclick = function() {
                goToPage(parseInt(this.textContent));
            };
            pageNumbers.appendChild(btn);
        }
    }
    
    // به‌روزرسانی شمارنده رکوردها
    function updateRecordCounter(pagination) {
        var start = ((workforceData.currentPage - 1) * workforceData.recordsPerPage) + 1;
        var end = Math.min(workforceData.currentPage * workforceData.recordsPerPage, pagination.total_records);
        var counter = document.getElementById('recordCounter');
        counter.textContent = 'نمایش ' + start + '-' + end + ' از ' + pagination.total_records + ' رکورد';
    }
    
    // تغییر تعداد رکورد در صفحه
    function changeRecordsPerPage(value) {
        if (value === 'all') {
            workforceData.recordsPerPage = 999999;
        } else {
            workforceData.recordsPerPage = parseInt(value);
        }
        workforceData.currentPage = 1;
        loadTableData();
    }
    
    // رفتن به صفحه مشخص
    function goToPage(page) {
        if (page >= 1 && page <= workforceData.totalPages) {
            workforceData.currentPage = page;
            loadTableData();
            scrollToTableTop();
        }
    }
    
    function goToPreviousPage() {
        if (workforceData.currentPage > 1) {
            goToPage(workforceData.currentPage - 1);
        }
    }
    
    function goToNextPage() {
        if (workforceData.currentPage < workforceData.totalPages) {
            goToPage(workforceData.currentPage + 1);
        }
    }
    
    function goToFirstPage() {
        goToPage(1);
    }
    
    function goToLastPage() {
        goToPage(workforceData.totalPages);
    }
    
    // جستجوی سراسری
    function performGlobalSearch(query) {
        workforceData.currentSearch = query;
        workforceData.currentPage = 1;
        loadTableData();
    }
    
    // پاک کردن همه فیلترها
    function clearAllFilters() {
        workforceData.currentFilters = {};
        workforceData.currentSearch = '';
        document.getElementById('globalSearch').value = '';
        loadTableData();
    }
    
    // ایجاد کارت مانیتورینگ
    function createMonitoringCard(fieldId, fieldLabel) {
        // بررسی محدودیت تعداد کارت‌ها
        if (workforceData.monitoringCards.length >= 6) {
            alert('حداکثر ۶ کارت مانیتورینگ فعال می‌توانید داشته باشید. لطفا ابتدا یکی از کارت‌ها را ببندید.');
            return;
        }
        
        // بررسی تکراری نبودن
        if (workforceData.monitoringCards.includes(fieldId)) {
            alert('کارت مانیتورینگ برای این فیلد قبلا ایجاد شده است.');
            return;
        }
        
        workforceData.monitoringCards.push(fieldId);
        
        // ایجاد عنصر کارت
        var cardsContainer = document.getElementById('monitoringCards');
        var card = document.createElement('div');
        card.className = 'monitoring-card card-dynamic';
        card.id = 'monitoringCard_' + fieldId;
        card.innerHTML = `
            <div class="card-icon">📊</div>
            <div class="card-content">
                <h3>${fieldLabel}</h3>
                <p class="card-number" id="cardValue_${fieldId}">0</p>
                <p class="card-sub">مجموع</p>
            </div>
            <button type="button" class="card-close" onclick="removeMonitoringCard(${fieldId})">✕</button>
        `;
        cardsContainer.appendChild(card);
        
        // به‌روزرسانی مقدار کارت
        updateMonitoringCardValue(fieldId);
    }
    
    // حذف کارت مانیتورینگ
    function removeMonitoringCard(fieldId) {
        var index = workforceData.monitoringCards.indexOf(fieldId);
        if (index > -1) {
            workforceData.monitoringCards.splice(index, 1);
        }
        
        var card = document.getElementById('monitoringCard_' + fieldId);
        if (card) {
            card.remove();
        }
    }
    
    // به‌روزرسانی کارت‌های مانیتورینگ
    function updateMonitoringCards() {
        // آمار کلی
        jQuery.ajax({
            url: workforce_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'workforce_get_department_stats',
                department_id: workforceData.departmentId,
                nonce: workforce_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var stats = response.data;
                    document.getElementById('personnelCount').textContent = stats.total_personnel;
                    document.getElementById('requiredFieldsPercent').textContent = stats.completion_rate + '%';
                    document.getElementById('warningCount').textContent = stats.incomplete_count;
                    
                    // نوار پیشرفت
                    var progressBar = document.getElementById('requiredFieldsProgress');
                    progressBar.style.width = stats.completion_rate + '%';
                    progressBar.style.backgroundColor = stats.completion_rate >= 80 ? '#2ecc71' : 
                                                      stats.completion_rate >= 50 ? '#f39c12' : '#e74c3c';
                }
            }
        });
        
        // به‌روزرسانی کارت‌های داینامیک
        workforceData.monitoringCards.forEach(function(fieldId) {
            updateMonitoringCardValue(fieldId);
        });
    }
    
    // به‌روزرسانی مقدار یک کارت مانیتورینگ
    function updateMonitoringCardValue(fieldId) {
        jQuery.ajax({
            url: workforce_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'workforce_get_field_stats',
                field_id: fieldId,
                department_id: workforceData.departmentId,
                period_id: workforceData.periodId,
                nonce: workforce_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var valueElement = document.getElementById('cardValue_' + fieldId);
                    if (valueElement) {
                        valueElement.textContent = response.data.total;
                    }
                }
            }
        });
    }
    
    // نشان دادن فیلتر ستونی
    function showColumnFilter(fieldId) {
        var field = workforceData.fields.find(function(f) {
            return f.id === fieldId;
        });
        
        if (!field) return;
        
        document.getElementById('filterModalTitle').textContent = 'فیلتر: ' + field.field_label;
        
        // گرفتن مقادیر یکتا
        jQuery.ajax({
            url: workforce_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'workforce_get_unique_values',
                field_id: fieldId,
                department_id: workforceData.departmentId,
                period_id: workforceData.periodId,
                nonce: workforce_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var values = response.data.values;
                    var currentFilter = workforceData.currentFilters[fieldId] || [];
                    
                    var html = '<div class="filter-content">';
                    html += '<div class="filter-values">';
                    
                    values.forEach(function(value) {
                        var checked = currentFilter.includes(value) ? ' checked' : '';
                        html += `
                            <label class="filter-checkbox">
                                <input type="checkbox" value="${value}"${checked} onchange="updateColumnFilter(${fieldId}, this)">
                                <span>${value || '(خالی)'}</span>
                            </label>
                        `;
                    });
                    
                    html += '</div>';
                    html += '<div class="filter-actions">';
                    html += '<button type="button" class="button button-primary" onclick="applyColumnFilter(' + fieldId + ')">اعمال فیلتر</button>';
                    html += '<button type="button" class="button" onclick="clearColumnFilter(' + fieldId + ')">پاک کردن</button>';
                    html += '</div>';
                    html += '</div>';
                    
                    document.getElementById('filterContent').innerHTML = html;
                    document.getElementById('columnFilterModal').style.display = 'block';
                }
            }
        });
    }
    
    // پنهان کردن مودال فیلتر
    function hideColumnFilterModal() {
        document.getElementById('columnFilterModal').style.display = 'none';
    }
    
    // به‌روزرسانی فیلتر ستونی
    function updateColumnFilter(fieldId, checkbox) {
        if (!workforceData.currentFilters[fieldId]) {
            workforceData.currentFilters[fieldId] = [];
        }
        
        var index = workforceData.currentFilters[fieldId].indexOf(checkbox.value);
        if (checkbox.checked && index === -1) {
            workforceData.currentFilters[fieldId].push(checkbox.value);
        } else if (!checkbox.checked && index > -1) {
            workforceData.currentFilters[fieldId].splice(index, 1);
        }
    }
    
    // اعمال فیلتر ستونی
    function applyColumnFilter(fieldId) {
        workforceData.currentPage = 1;
        loadTableData();
        hideColumnFilterModal();
    }
    
    // پاک کردن فیلتر ستونی
    function clearColumnFilter(fieldId) {
        delete workforceData.currentFilters[fieldId];
        workforceData.currentPage = 1;
        loadTableData();
        hideColumnFilterModal();
    }
    
    // ثابت کردن ستون
    function togglePinColumn(button) {
        var th = button.closest('th');
        var fieldId = th.dataset.fieldId;
        
        th.classList.toggle('pinned');
        button.classList.toggle('pinned');
        
        var index = workforceData.pinnedColumns.indexOf(fieldId);
        if (index === -1) {
            workforceData.pinnedColumns.push(fieldId);
        } else {
            workforceData.pinnedColumns.splice(index, 1);
        }
    }
    
    // انتخاب همه ردیف‌ها
    function toggleSelectAll(checkbox) {
        var checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(function(cb) {
            cb.checked = checkbox.checked;
            var rowId = parseInt(cb.closest('tr').dataset.personnelId);
            toggleRowSelection(rowId, cb);
        });
    }
    
    // انتخاب ردیف
    function toggleRowSelection(rowId, checkbox) {
        var index = workforceData.selectedRows.indexOf(rowId);
        if (checkbox.checked && index === -1) {
            workforceData.selectedRows.push(rowId);
        } else if (!checkbox.checked && index > -1) {
            workforceData.selectedRows.splice(index, 1);
        }
    }
    
    // ویرایش پرسنل
    function editPersonnel(personnelId) {
        workforceData.currentPersonnelId = personnelId;
        showSideForm();
        
        jQuery.ajax({
            url: workforce_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'workforce_get_personnel_form',
                personnel_id: personnelId,
                mode: 'edit',
                nonce: workforce_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    document.getElementById('formTitle').textContent = 'ویرایش پرسنل';
                    document.getElementById('sideFormBody').innerHTML = response.data.html;
                    
                    // فعال‌سازی datepicker
                    jQuery('.jdatepicker').persianDatepicker({
                        format: 'YYYY/MM/DD',
                        observer: true,
                        persianDigit: false
                    });
                    
                    // بررسی قابلیت ناوبری
                    checkNavigationButtons();
                }
            }
        });
    }
    
    // مشاهده پرسنل
    function viewPersonnel(personnelId) {
        workforceData.currentPersonnelId = personnelId;
        showSideForm();
        
        jQuery.ajax({
            url: workforce_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'workforce_get_personnel_form',
                personnel_id: personnelId,
                mode: 'view',
                nonce: workforce_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    document.getElementById('formTitle').textContent = 'مشاهده پرسنل';
                    document.getElementById('sideFormBody').innerHTML = response.data.html;
                    checkNavigationButtons();
                }
            }
        });
    }
    
    // نمایش فرم سمت راست
    function showSideForm() {
        document.getElementById('sideForm').classList.add('active');
    }
    
    // پنهان کردن فرم سمت راست
    function hideSideForm() {
        document.getElementById('sideForm').classList.remove('active');
        workforceData.currentPersonnelId = null;
    }
    
    // ناوبری بین رکوردها
    function navigatePersonnel(direction) {
        var rows = document.querySelectorAll('#tableBody tr[data-personnel-id]');
        var currentIndex = -1;
        
        for (var i = 0; i < rows.length; i++) {
            if (parseInt(rows[i].dataset.personnelId) === workforceData.currentPersonnelId) {
                currentIndex = i;
                break;
            }
        }
        
        if (direction === 'prev' && currentIndex > 0) {
            var prevId = parseInt(rows[currentIndex - 1].dataset.personnelId);
            editPersonnel(prevId);
        } else if (direction === 'next' && currentIndex < rows.length - 1) {
            var nextId = parseInt(rows[currentIndex + 1].dataset.personnelId);
            editPersonnel(nextId);
        }
    }
    
    // بررسی دکمه‌های ناوبری
    function checkNavigationButtons() {
        var rows = document.querySelectorAll('#tableBody tr[data-personnel-id]');
        var currentIndex = -1;
        
        for (var i = 0; i < rows.length; i++) {
            if (parseInt(rows[i].dataset.personnelId) === workforceData.currentPersonnelId) {
                currentIndex = i;
                break;
            }
        }
        
        document.getElementById('prevBtn').disabled = currentIndex <= 0;
        document.getElementById('nextBtn').disabled = currentIndex >= rows.length - 1;
    }
    
    // ذخیره فرم ویرایش
    function savePersonnelForm() {
        var form = document.getElementById('sideFormBody').querySelector('form');
        if (!form) return;
        
        var formData = new FormData(form);
        formData.append('action', 'workforce_save_personnel');
        formData.append('personnel_id', workforceData.currentPersonnelId);
        formData.append('nonce', workforce_ajax.nonce);
        
        jQuery.ajax({
            url: workforce_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('تغییرات با موفقیت ذخیره شد و برای تایید ارسال شد.');
                    hideSideForm();
                    loadTableData();
                    updateMonitoringCards();
                } else {
                    alert('خطا: ' + response.data.message);
                }
            }
        });
    }
    
    // درخواست حذف پرسنل
    function requestDeletePersonnel(personnelId) {
        if (!confirm('آیا از حذف این پرسنل اطمینان دارید؟ این عمل نیاز به تایید مدیر سیستم دارد.')) {
            return;
        }
        
        jQuery.ajax({
            url: workforce_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'workforce_request_delete_personnel',
                personnel_id: personnelId,
                nonce: workforce_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('درخواست حذف با موفقیت ارسال شد و در انتظار تایید است.');
                    loadTableData();
                } else {
                    alert('خطا: ' + response.data.message);
                }
            }
        });
    }
    
    // حذف چندین ردیف انتخاب شده
    function deleteSelectedRows() {
        if (workforceData.selectedRows.length === 0) {
            alert('لطفا ابتدا ردیف‌هایی را برای حذف انتخاب کنید.');
            return;
        }
        
        if (!confirm('آیا از حذف ' + workforceData.selectedRows.length + ' ردیف انتخاب شده اطمینان دارید؟')) {
            return;
        }
        
        jQuery.ajax({
            url: workforce_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'workforce_request_bulk_delete',
                personnel_ids: workforceData.selectedRows,
                nonce: workforce_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('درخواست حذف با موفقیت ارسال شد و در انتظار تایید است.');
                    workforceData.selectedRows = [];
                    document.getElementById('selectAll').checked = false;
                    loadTableData();
                } else {
                    alert('خطا: ' + response.data.message);
                }
            }
        });
    }
    
    // نمایش مودال افزودن پرسنل
    function showAddPersonnelModal() {
        // بارگذاری فیلدهای اضافی
        jQuery.ajax({
            url: workforce_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'workforce_get_additional_fields',
                nonce: workforce_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    document.getElementById('additionalFields').innerHTML = response.data.html;
                    document.getElementById('addPersonnelModal').style.display = 'block';
                    
                    // فعال‌سازی datepicker
                    jQuery('.jdatepicker').persianDatepicker({
                        format: 'YYYY/MM/DD',
                        observer: true,
                        persianDigit: false
                    });
                }
            }
        });
    }
    
    // پنهان کردن مودال افزودن پرسنل
    function hideAddPersonnelModal() {
        document.getElementById('addPersonnelModal').style.display = 'none';
        document.getElementById('addPersonnelForm').reset();
        document.getElementById('nationalCodeValidation').textContent = '';
    }
    
    // ثبت فرم افزودن پرسنل
    function submitAddPersonnelForm() {
        var form = document.getElementById('addPersonnelForm');
        if (!form.checkValidity()) {
            alert('لطفا فیلدهای ضروری را پر کنید.');
            return;
        }
        
        var formData = new FormData(form);
        formData.append('action', 'workforce_request_add_personnel');
        formData.append('department_id', workforceData.departmentId);
        formData.append('nonce', workforce_ajax.nonce);
        
        // اعتبارسنجی کدملی
        var nationalCode = document.getElementById('new_national_code').value;
        jQuery.ajax({
            url: workforce_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'workforce_validate_national_code',
                national_code: nationalCode,
                department_id: workforceData.departmentId,
                nonce: workforce_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // ارسال فرم
                    jQuery.ajax({
                        url: workforce_ajax.ajax_url,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(addResponse) {
                            if (addResponse.success) {
                                alert('درخواست افزودن پرسنل با موفقیت ارسال شد و در انتظار تایید است.');
                                hideAddPersonnelModal();
                                loadTableData();
                                updateMonitoringCards();
                            } else {
                                alert('خطا: ' + addResponse.data.message);
                            }
                        }
                    });
                } else {
                    document.getElementById('nationalCodeValidation').textContent = response.data.message;
                    document.getElementById('nationalCodeValidation').style.color = '#e74c3c';
                }
            }
        });
    }
    
    // خروجی اکسل
    function exportToExcel() {
        var params = {
            action: 'workforce_export_excel',
            department_id: workforceData.departmentId,
            period_id: workforceData.periodId,
            filters: workforceData.currentFilters,
            search: workforceData.currentSearch,
            nonce: workforce_ajax.nonce
        };
        
        // ایجاد URL برای دانلود
        var url = workforce_ajax.ajax_url + '?' + jQuery.param(params);
        window.open(url, '_blank');
    }
    
    // به‌روزرسانی داده‌ها
    function refreshData() {
        loadTableData();
        updateMonitoringCards();
        alert('داده‌ها با موفقیت به‌روزرسانی شدند.');
    }
    
    // اسکرول به بالای جدول
    function scrollToTableTop() {
        var table = document.querySelector('.workforce-main-table');
        if (table) {
            table.scrollIntoView({ behavior: 'smooth' });
        }
    }
    
    // تنظیم event listeners
    function setupEventListeners() {
        // درگ و دراپ برای تغییر ترتیب ستون‌ها
        var table = document.getElementById('personnelTable');
        var headerCells = table.querySelectorAll('thead th');
        
        headerCells.forEach(function(cell, index) {
            if (index < 2) return; // ستون‌های انتخاب و شماره ردیف
            
            cell.setAttribute('draggable', 'true');
            
            cell.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('text/plain', index);
                cell.classList.add('dragging');
            });
            
            cell.addEventListener('dragend', function() {
                cell.classList.remove('dragging');
            });
        });
        
        table.addEventListener('dragover', function(e) {
            e.preventDefault();
        });
        
        table.addEventListener('drop', function(e) {
            e.preventDefault();
            var fromIndex = e.dataTransfer.getData('text/plain');
            var toCell = e.target.closest('th');
            var toIndex = Array.from(headerCells).indexOf(toCell);
            
            if (fromIndex >= 2 && toIndex >= 2 && fromIndex !== toIndex) {
                // تغییر ترتیب در آرایه fields
                var field = workforceData.fields.splice(fromIndex - 2, 1)[0];
                workforceData.fields.splice(toIndex - 2, 0, field);
                
                // بارگذاری مجدد جدول
                loadTableData();
            }
        });
        
        // کلیک راست برای منو زمینه
        table.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            var row = e.target.closest('tr[data-personnel-id]');
            if (row) {
                showContextMenu(e, parseInt(row.dataset.personnelId));
            }
        });
        
        // بستن منو زمینه با کلیک
        document.addEventListener('click', function() {
            var contextMenu = document.getElementById('contextMenu');
            if (contextMenu) {
                contextMenu.remove();
            }
        });
    }
    
    // نمایش منو زمینه
    function showContextMenu(e, personnelId) {
        // حذف منوی قبلی
        var oldMenu = document.getElementById('contextMenu');
        if (oldMenu) oldMenu.remove();
        
        // ایجاد منوی جدید
        var menu = document.createElement('div');
        menu.id = 'contextMenu';
        menu.className = 'context-menu';
        menu.style.top = e.pageY + 'px';
        menu.style.left = e.pageX + 'px';
        
        menu.innerHTML = `
            <div class="menu-item" onclick="editPersonnel(${personnelId})">
                <span class="menu-icon">✏️</span>
                ویرایش
            </div>
            <div class="menu-item" onclick="viewPersonnel(${personnelId})">
                <span class="menu-icon">👁️</span>
                مشاهده
            </div>
            <div class="menu-item" onclick="requestDeletePersonnel(${personnelId})">
                <span class="menu-icon">🗑️</span>
                حذف
            </div>
            <div class="menu-separator"></div>
            <div class="menu-item" onclick="copyPersonnelData(${personnelId})">
                <span class="menu-icon">📋</span>
                کپی اطلاعات
            </div>
        `;
        
        document.body.appendChild(menu);
    }
    
    // کپی اطلاعات پرسنل
    function copyPersonnelData(personnelId) {
        jQuery.ajax({
            url: workforce_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'workforce_get_personnel_data_text',
                personnel_id: personnelId,
                nonce: workforce_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    navigator.clipboard.writeText(response.data.text)
                        .then(function() {
                            alert('اطلاعات با موفقیت کپی شد.');
                        })
                        .catch(function() {
                            alert('خطا در کپی اطلاعات.');
                        });
                }
            }
        });
    }
    
    // تنظیم کلیدهای میانبر
    function setupKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Ctrl + F: جستجو
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('globalSearch').focus();
            }
            
            // Ctrl + S: ذخیره (در فرم ویرایش)
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                if (workforceData.currentPersonnelId) {
                    savePersonnelForm();
                }
            }
            
            // Ctrl + A: انتخاب همه
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault();
                var checkbox = document.getElementById('selectAll');
                checkbox.checked = !checkbox.checked;
                toggleSelectAll(checkbox);
            }
            
            // Escape: بستن فرم
            if (e.key === 'Escape') {
                hideSideForm();
                hideAddPersonnelModal();
                hideColumnFilterModal();
            }
            
            // فلش‌های چپ و راست برای ناوبری
            if (workforceData.currentPersonnelId) {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    navigatePersonnel('prev');
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    navigatePersonnel('next');
                }
            }
        });
    }
    </script>
    <?php
    
    return ob_get_clean();
}

/**
 * پنل مدیر سازمان
 */
function workforce_org_manager_panel($user_id) {
    $current_user = wp_get_current_user();
    $departments = workforce_get_all_departments();
    $active_period = workforce_get_active_period();
    $period_id = $active_period ? $active_period->id : null;
    $fields = workforce_get_all_fields();
    
    ob_start();
    ?>
    <div class="workforce-org-manager-panel" data-period-id="<?php echo esc_attr($period_id); ?>">
        <!-- هدر هوشمند -->
        <div class="workforce-header">
            <div class="header-content">
                <div class="welcome-section">
                    <div class="welcome-icon">👑</div>
                    <div class="welcome-text">
                        <h2>خوش آمدید، <?php echo esc_html($current_user->display_name); ?></h2>
                        <div class="welcome-details">
                            <span class="detail-item">
                                <span class="detail-icon">🏢</span>
                                <span class="detail-text">مدیر سازمان</span>
                            </span>
                            <span class="detail-item">
                                <span class="detail-icon">📅</span>
                                <span class="detail-text">دوره: <?php echo $active_period ? esc_html($active_period->name) : 'تعیین نشده'; ?></span>
                            </span>
                            <span class="detail-item">
                                <span class="detail-icon">🕒</span>
                                <span class="detail-text">امروز: <?php echo esc_html(workforce_today_jalali()); ?></span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="header-actions">
                    <button type="button" class="button button-primary" onclick="showOrgReports()">
                        <span class="action-icon">📈</span>
                        گزارشات کلان
                    </button>
                    <button type="button" class="button button-secondary" onclick="exportOrgToExcel()">
                        <span class="action-icon">📤</span>
                        خروجی اکسل
                    </button>
                    <button type="button" class="button" onclick="refreshOrgData()">
                        <span class="action-icon">🔄</span>
                        به‌روزرسانی
                    </button>
                </div>
            </div>
        </div>
        
        <!-- آمار سازمانی -->
        <div class="workforce-org-stats">
            <?php
            $org_stats = workforce_get_org_manager_stats();
            ?>
            <div class="org-stat-card">
                <div class="stat-icon">🏢</div>
                <div class="stat-content">
                    <h3>تعداد ادارات</h3>
                    <p class="stat-number"><?php echo esc_html($org_stats['overall']['total_departments']); ?></p>
                </div>
            </div>
            
            <div class="org-stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <h3>کل پرسنل</h3>
                    <p class="stat-number"><?php echo esc_html($org_stats['overall']['total_personnel']); ?></p>
                    <p class="stat-sub"><?php echo esc_html($org_stats['overall']['total_active']); ?> نفر فعال</p>
                </div>
            </div>
            
            <div class="org-stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <h3>میانگین تکمیل</h3>
                    <p class="stat-number"><?php echo esc_html($org_stats['overall']['avg_completion_rate']); ?>%</p>
                    <p class="stat-sub">اطلاعات پرسنل</p>
                </div>
            </div>
        </div>
        
        <!-- کارت‌های ادارات -->
        <div class="workforce-dept-cards">
            <h3>وضعیت ادارات</h3>
            <div class="dept-cards-grid">
                <?php foreach ($org_stats['departments'] as $dept): ?>
                    <div class="dept-card" style="border-color: <?php echo esc_attr($dept['color']); ?>" onclick="showDeptDetails(<?php echo esc_attr($dept['id']); ?>)">
                        <div class="dept-card-header">
                            <div class="dept-color" style="background-color: <?php echo esc_attr($dept['color']); ?>"></div>
                            <h4><?php echo esc_html($dept['name']); ?></h4>
                        </div>
                        <div class="dept-card-content">
                            <div class="dept-stat">
                                <span class="stat-label">پرسنل:</span>
                                <span class="stat-value"><?php echo esc_html($dept['personnel_count']); ?></span>
                            </div>
                            <div class="dept-stat">
                                <span class="stat-label">فعال:</span>
                                <span class="stat-value"><?php echo esc_html($dept['active_count']); ?></span>
                            </div>
                            <div class="dept-stat">
                                <span class="stat-label">تکمیل:</span>
                                <span class="stat-value"><?php echo esc_html($dept['completion_rate']); ?>%</span>
                            </div>
                        </div>
<div class="dept-card-footer">
    <span class="dept-manager">
        <?php
        global $wpdb;
        $managers_table = $wpdb->prefix . WF_TABLE_PREFIX . 'department_managers';
        $users_table = $wpdb->users;
        
        $managers = $wpdb->get_results($wpdb->prepare(
            "SELECT dm.is_primary, u.display_name 
             FROM $managers_table dm 
             INNER JOIN $users_table u ON dm.user_id = u.ID 
             WHERE dm.department_id = %d 
             ORDER BY dm.is_primary DESC 
             LIMIT 1",
            $dept['id']
        ));
        
        if (!empty($managers)) {
            $total = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $managers_table WHERE department_id = %d",
                $dept['id']
            ));
            
            echo '👤 ' . esc_html($managers[0]->display_name) . 
                 ($total > 1 ? ' +' . ($total - 1) : '');
        } else {
            echo '👤 تعیین نشده';
        }
        ?>
    </span>
</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- جدول تجمیعی -->
        <div class="workforce-org-table">
            <h3>اطلاعات تجمیعی همه ادارات</h3>
            
            <div class="table-toolbar">
                <div class="toolbar-left">
                    <div class="filter-group">
                        <label>فیلتر اداره:</label>
                        <select id="orgDeptFilter" onchange="filterOrgTable()">
                            <option value="all">همه ادارات</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo esc_attr($dept->id); ?>"><?php echo esc_html($dept->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>فیلتر وضعیت:</label>
                        <select id="orgStatusFilter" onchange="filterOrgTable()">
                            <option value="all">همه</option>
                            <option value="active">فعال</option>
                            <option value="inactive">غیرفعال</option>
                            <option value="suspended">تعلیق</option>
                            <option value="retired">بازنشسته</option>
                        </select>
                    </div>
                </div>
                
                <div class="toolbar-right">
                    <div class="search-box">
                        <input type="text" id="orgGlobalSearch" placeholder="جستجو..." onkeyup="searchOrgTable()">
                        <span class="search-icon">🔍</span>
                    </div>
                </div>
            </div>
            
            <div class="table-container">
                <table class="workforce-data-table" id="orgPersonnelTable">
                    <thead>
                        <tr>
                            <th class="row-number">ردیف</th>
                            <th class="dept-col">نام اداره</th>
                            <th>کدملی</th>
                            <th>نام و نام خانوادگی</th>
                            <th>تاریخ استخدام</th>
                            <th>نوع استخدام</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody id="orgTableBody">
                        <!-- داده‌ها از طریق AJAX بارگذاری می‌شوند -->
                    </tbody>
                </table>
            </div>
            
            <div class="table-pagination">
                <div class="pagination-info" id="orgPaginationInfo"></div>
                <div class="pagination-controls">
                    <button type="button" class="pagination-btn" onclick="goToOrgPage(1)" disabled id="orgFirstPage">اولین</button>
                    <button type="button" class="pagination-btn" onclick="goToOrgPreviousPage()" disabled id="orgPrevPage">قبلی</button>
                    
                    <div class="page-numbers" id="orgPageNumbers"></div>
                    
                    <button type="button" class="pagination-btn" onclick="goToOrgNextPage()" disabled id="orgNextPage">بعدی</button>
                    <button type="button" class="pagination-btn" onclick="goToOrgLastPage()" disabled id="orgLastPage">آخرین</button>
                </div>
            </div>
        </div>
        
        <!-- مودال گزارشات -->
        <div id="orgReportsModal" class="workforce-modal">
            <div class="workforce-modal-content wide-modal">
                <div class="workforce-modal-header">
                    <h2>گزارشات کلان سازمان</h2>
                    <span class="workforce-modal-close" onclick="hideOrgReportsModal()">&times;</span>
                </div>
                <div class="workforce-modal-body">
                    <div class="report-tabs">
                        <button type="button" class="report-tab active" onclick="showReportTab('comparison')">مقایسه ادارات</button>
                        <button type="button" class="report-tab" onclick="showReportTab('monthly')">روند ماهانه</button>
                        <button type="button" class="report-tab" onclick="showReportTab('analysis')">تحلیل آماری</button>
                    </div>
                    
                    <div class="report-content">
                        <div id="comparisonReport" class="report-tab-content active">
                            <h3>مقایسه عملکرد ادارات</h3>
                            <div id="comparisonChart"></div>
                        </div>
                        
                        <div id="monthlyReport" class="report-tab-content">
                            <h3>روند تغییرات ماهانه</h3>
                            <div id="monthlyChart"></div>
                        </div>
                        
                        <div id="analysisReport" class="report-tab-content">
                            <h3>تحلیل آماری سازمان</h3>
                            <div id="analysisStats"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // داده‌های سازمانی
    var orgData = {
        currentPage: 1,
        recordsPerPage: 25,
        totalRecords: 0,
        totalPages: 0,
        currentDeptFilter: 'all',
        currentStatusFilter: 'all',
        currentSearch: '',
        departments: <?php echo json_encode($departments); ?>
    };
    
    // بارگذاری اولیه
    document.addEventListener('DOMContentLoaded', function() {
        loadOrgTableData();
    });
    
    // بارگذاری داده‌های جدول سازمانی
    function loadOrgTableData() {
        var params = {
            action: 'workforce_get_org_table_data',
            department_id: orgData.currentDeptFilter === 'all' ? '' : orgData.currentDeptFilter,
            status: orgData.currentStatusFilter === 'all' ? '' : orgData.currentStatusFilter,
            search: orgData.currentSearch,
            page: orgData.currentPage,
            per_page: orgData.recordsPerPage,
            nonce: workforce_ajax.nonce
        };
        
        jQuery.ajax({
            url: workforce_ajax.ajax_url,
            type: 'POST',
            data: params,
            success: function(response) {
                if (response.success) {
                    renderOrgTable(response.data);
                    updateOrgPagination(response.data.pagination);
                    updateOrgRecordCounter(response.data.pagination);
                }
            }
        });
    }
    
    // رندر جدول سازمانی
    function renderOrgTable(data) {
        var tbody = document.getElementById('orgTableBody');
        tbody.innerHTML = '';
        
        if (data.rows.length === 0) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td colspan="8" class="no-data">داده‌ای یافت نشد.</td>';
            tbody.appendChild(tr);
            return;
        }
        
        data.rows.forEach(function(row, index) {
            var tr = document.createElement('tr');
            
            // شماره ردیف
            var tdNumber = document.createElement('td');
            tdNumber.className = 'row-number';
            tdNumber.textContent = ((orgData.currentPage - 1) * orgData.recordsPerPage) + index + 1;
            tr.appendChild(tdNumber);
            
            // نام اداره
            var tdDept = document.createElement('td');
            tdDept.className = 'dept-col';
            tdDept.innerHTML = '<span class="dept-badge" style="background-color: ' + row.department_color + '">' + row.department_name + '</span>';
            tr.appendChild(tdDept);
            
            // کدملی
            var tdNationalCode = document.createElement('td');
            tdNationalCode.textContent = row.national_code;
            tr.appendChild(tdNationalCode);
            
            // نام و نام خانوادگی
            var tdName = document.createElement('td');
            tdName.innerHTML = '<strong>' + row.first_name + ' ' + row.last_name + '</strong>';
            tr.appendChild(tdName);
            
            // تاریخ استخدام
            var tdEmploymentDate = document.createElement('td');
            tdEmploymentDate.textContent = row.employment_date;
            tr.appendChild(tdEmploymentDate);
            
            // نوع استخدام
            var tdEmploymentType = document.createElement('td');
            tdEmploymentType.textContent = getEmploymentTypeLabel(row.employment_type);
            tr.appendChild(tdEmploymentType);
            
            // وضعیت
            var tdStatus = document.createElement('td');
            tdStatus.innerHTML = getStatusBadge(row.status);
            tr.appendChild(tdStatus);
            
            // عملیات
            var tdActions = document.createElement('td');
            tdActions.className = 'actions-col';
            tdActions.innerHTML = `
                <button type="button" class="action-btn view-btn" onclick="viewOrgPersonnel(${row.id})" title="مشاهده">
                    👁️
                </button>
                <button type="button" class="action-btn chart-btn" onclick="showPersonnelChart(${row.id})" title="نمودار">
                    📈
                </button>
            `;
            tr.appendChild(tdActions);
            
            tbody.appendChild(tr);
        });
    }
    
    // برچسب نوع استخدام
    function getEmploymentTypeLabel(type) {
        var labels = {
            'permanent': 'دائمی',
            'contract': 'پیمانی',
            'temporary': 'موقت',
            'project': 'پروژه‌ای'
        };
        return labels[type] || type;
    }
    
    // نشان وضعیت
    function getStatusBadge(status) {
        var badges = {
            'active': '<span class="status-badge status-active">فعال</span>',
            'inactive': '<span class="status-badge status-inactive">غیرفعال</span>',
            'suspended': '<span class="status-badge status-suspended">تعلیق</span>',
            'retired': '<span class="status-badge status-retired">بازنشسته</span>'
        };
        return badges[status] || status;
    }
    
    // فیلتر جدول سازمانی
    function filterOrgTable() {
        orgData.currentDeptFilter = document.getElementById('orgDeptFilter').value;
        orgData.currentStatusFilter = document.getElementById('orgStatusFilter').value;
        orgData.currentPage = 1;
        loadOrgTableData();
    }
    
    // جستجو در جدول سازمانی
    function searchOrgTable() {
        orgData.currentSearch = document.getElementById('orgGlobalSearch').value;
        orgData.currentPage = 1;
        loadOrgTableData();
    }
    
    // صفحه‌بندی جدول سازمانی
    function updateOrgPagination(pagination) {
        orgData.totalRecords = pagination.total_records;
        orgData.totalPages = pagination.total_pages;
        
        var firstBtn = document.getElementById('orgFirstPage');
        var prevBtn = document.getElementById('orgPrevPage');
        var nextBtn = document.getElementById('orgNextPage');
        var lastBtn = document.getElementById('orgLastPage');
        
        firstBtn.disabled = orgData.currentPage === 1;
        prevBtn.disabled = orgData.currentPage === 1;
        nextBtn.disabled = orgData.currentPage === orgData.totalPages;
        lastBtn.disabled = orgData.currentPage === orgData.totalPages;
        
        // شماره صفحات
        var pageNumbers = document.getElementById('orgPageNumbers');
        pageNumbers.innerHTML = '';
        
        var startPage = Math.max(1, orgData.currentPage - 2);
        var endPage = Math.min(orgData.totalPages, startPage + 4);
        
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }
        
        for (var i = startPage; i <= endPage; i++) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'page-number-btn';
            if (i === orgData.currentPage) {
                btn.classList.add('active');
            }
            btn.textContent = i;
            btn.onclick = function() {
                goToOrgPage(parseInt(this.textContent));
            };
            pageNumbers.appendChild(btn);
        }
    }
    
    function updateOrgRecordCounter(pagination) {
        var start = ((orgData.currentPage - 1) * orgData.recordsPerPage) + 1;
        var end = Math.min(orgData.currentPage * orgData.recordsPerPage, pagination.total_records);
        var counter = document.getElementById('orgPaginationInfo');
        counter.textContent = 'نمایش ' + start + '-' + end + ' از ' + pagination.total_records + ' رکورد';
    }
    
    function goToOrgPage(page) {
        if (page >= 1 && page <= orgData.totalPages) {
            orgData.currentPage = page;
            loadOrgTableData();
        }
    }
    
    function goToOrgPreviousPage() {
        if (orgData.currentPage > 1) {
            goToOrgPage(orgData.currentPage - 1);
        }
    }
    
    function goToOrgNextPage() {
        if (orgData.currentPage < orgData.totalPages) {
            goToOrgPage(orgData.currentPage + 1);
        }
    }
    
    function goToOrgFirstPage() {
        goToOrgPage(1);
    }
    
    function goToOrgLastPage() {
        goToOrgPage(orgData.totalPages);
    }
    
    // مشاهده پرسنل در سطح سازمان
    function viewOrgPersonnel(personnelId) {
        jQuery.ajax({
            url: workforce_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'workforce_view_org_personnel',
                personnel_id: personnelId,
                nonce: workforce_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // نمایش در مودال
                    alert('مشاهده پرسنل - این بخش نیاز به پیاده‌سازی دارد.');
                }
            }
        });
    }
    
    // نمایش نمودار پرسنل
    function showPersonnelChart(personnelId) {
        // پیاده‌سازی نمودار
        alert('نمودار پرسنل - این بخش نیاز به پیاده‌سازی دارد.');
    }
    
    // نمایش جزئیات اداره
    function showDeptDetails(deptId) {
        jQuery.ajax({
            url: workforce_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'workforce_get_dept_details',
                department_id: deptId,
                nonce: workforce_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // نمایش در مودال
                    alert('جزئیات اداره - این بخش نیاز به پیاده‌سازی دارد.');
                }
            }
        });
    }
    
    // نمایش گزارشات سازمان
    function showOrgReports() {
        document.getElementById('orgReportsModal').style.display = 'block';
        loadComparisonReport();
    }
    
    function hideOrgReportsModal() {
        document.getElementById('orgReportsModal').style.display = 'none';
    }
    
    function showReportTab(tabName) {
        // حذف کلاس active از همه تب‌ها
        document.querySelectorAll('.report-tab').forEach(function(tab) {
            tab.classList.remove('active');
        });
        
        document.querySelectorAll('.report-tab-content').forEach(function(content) {
            content.classList.remove('active');
        });
        
        // افزودن کلاس active به تب انتخاب شده
        event.target.classList.add('active');
        document.getElementById(tabName + 'Report').classList.add('active');
        
        // بارگذاری گزارش مربوطه
        if (tabName === 'comparison') {
            loadComparisonReport();
        } else if (tabName === 'monthly') {
            loadMonthlyReport();
        } else if (tabName === 'analysis') {
            loadAnalysisReport();
        }
    }
    
    function loadComparisonReport() {
        jQuery.ajax({
            url: workforce_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'workforce_get_comparison_report',
                nonce: workforce_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // ایجاد نمودار مقایسه
                    createComparisonChart(response.data);
                }
            }
        });
    }
    
    function loadMonthlyReport() {
        jQuery.ajax({
            url: workforce_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'workforce_get_monthly_report',
                nonce: workforce_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // ایجاد نمودار روند ماهانه
                    createMonthlyChart(response.data);
                }
            }
        });
    }
    
    function loadAnalysisReport() {
        jQuery.ajax({
            url: workforce_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'workforce_get_analysis_report',
                nonce: workforce_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // نمایش آمار تحلیلی
                    document.getElementById('analysisStats').innerHTML = response.data.html;
                }
            }
        });
    }
    
    function createComparisonChart(data) {
        // پیاده‌سازی نمودار با Chart.js یا کتابخانه دیگر
        var ctx = document.getElementById('comparisonChart').getContext('2d');
        // کد ایجاد نمودار
    }
    
    function createMonthlyChart(data) {
        // پیاده‌سازی نمودار با Chart.js یا کتابخانه دیگر
        var ctx = document.getElementById('monthlyChart').getContext('2d');
        // کد ایجاد نمودار
    }
    
    // خروجی اکسل سازمانی
    function exportOrgToExcel() {
        var params = {
            action: 'workforce_export_org_excel',
            department_id: orgData.currentDeptFilter === 'all' ? '' : orgData.currentDeptFilter,
            status: orgData.currentStatusFilter === 'all' ? '' : orgData.currentStatusFilter,
            search: orgData.currentSearch,
            nonce: workforce_ajax.nonce
        };
        
        var url = workforce_ajax.ajax_url + '?' + jQuery.param(params);
        window.open(url, '_blank');
    }
    
    // به‌روزرسانی داده‌های سازمانی
    function refreshOrgData() {
        loadOrgTableData();
        alert('داده‌های سازمانی با موفقیت به‌روزرسانی شدند.');
    }
    </script>
    <?php
    
    return ob_get_clean();
}

/**
 * هندلرهای AJAX برای پنل مدیران
 */
function workforce_ajax_get_table_data() {
    check_ajax_referer('workforce_nonce', 'nonce');
    
    $department_id = intval($_POST['department_id']);
    $period_id = isset($_POST['period_id']) ? intval($_POST['period_id']) : null;
    $page = intval($_POST['page']) ?: 1;
    $per_page = intval($_POST['per_page']) ?: 25;
    $filters = isset($_POST['filters']) ? (array) $_POST['filters'] : [];
    $search = sanitize_text_field($_POST['search'] ?? '');
    
    $offset = ($page - 1) * $per_page;
    
    global $wpdb;
    $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
    $meta_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel_meta';
    $fields_table = $wpdb->prefix . WF_TABLE_PREFIX . 'fields';
    
    // ساختن کوئری اصلی
    $query = "SELECT p.* FROM $personnel_table p WHERE p.department_id = %d AND p.is_deleted = 0";
    $params = [$department_id];
    
    // اعمال فیلترهای وضعیت و نوع استخدام
    if (!empty($filters)) {
        foreach ($filters as $field_id => $values) {
            if ($field_id === 'status') {
                $query .= " AND p.status = %s";
                $params[] = $values;
            } elseif ($field_id === 'employment_type') {
                $query .= " AND p.employment_type = %s";
                $params[] = $values;
            }
        }
    }
    
    // اعمال جستجوی سراسری
    if (!empty($search)) {
        $query .= " AND (p.first_name LIKE %s OR p.last_name LIKE %s OR p.national_code LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($search) . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // گرفتن تعداد کل
    $count_query = "SELECT COUNT(*) FROM ($query) as count_query";
    $total_records = $wpdb->get_var($wpdb->prepare($count_query, $params));
    
    // اعمال محدودیت و مرتب‌سازی
    $query .= " ORDER BY p.last_name ASC, p.first_name ASC LIMIT %d OFFSET %d";
    $params[] = $per_page;
    $params[] = $offset;
    
    $personnel = $wpdb->get_results($wpdb->prepare($query, $params));
    
    // اضافه کردن داده‌های متا
    $fields = workforce_get_all_fields();
    foreach ($personnel as &$person) {
        $person->meta = [];
        foreach ($fields as $field) {
            $value = workforce_get_personnel_field_value($person->id, $field->field_name, $period_id);
            $person->meta[$field->id] = $value;
            $person->meta[$field->field_name] = $value;
        }
    }
    
    // محاسبه صفحه‌بندی
    $total_pages = ceil($total_records / $per_page);
    
    $response = [
        'rows' => $personnel,
        'pagination' => [
            'total_records' => $total_records,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'per_page' => $per_page
        ]
    ];
    
    wp_send_json_success($response);
}
add_action('wp_ajax_workforce_get_table_data', 'workforce_ajax_get_table_data');

function workforce_ajax_get_department_stats() {
    check_ajax_referer('workforce_nonce', 'nonce');
    
    $department_id = intval($_POST['department_id']);
    
    global $wpdb;
    $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
    $meta_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel_meta';
    $fields_table = $wpdb->prefix . WF_TABLE_PREFIX . 'fields';
    
    // تعداد کل پرسنل
    $total_personnel = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $personnel_table WHERE department_id = %d AND is_deleted = 0",
        $department_id
    ));
    
    // تعداد پرسنل فعال
    $active_personnel = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $personnel_table WHERE department_id = %d AND status = 'active' AND is_deleted = 0",
        $department_id
    ));
    
    // درصد تکمیل فیلدهای ضروری
    $required_fields = $wpdb->get_results(
        "SELECT id, field_name FROM $fields_table WHERE is_required = 1"
    );
    
    $completed_count = 0;
    $total_required = count($required_fields) * $total_personnel;
    
    if ($total_required > 0) {
        foreach ($required_fields as $field) {
            $completed = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT pm.personnel_id) 
                 FROM $meta_table pm 
                 INNER JOIN $personnel_table p ON pm.personnel_id = p.id 
                 WHERE p.department_id = %d AND p.is_deleted = 0 
                 AND pm.meta_key = %s AND pm.meta_value != ''",
                $department_id, $field->field_name
            ));
            $completed_count += $completed;
        }
        
        $completion_rate = round(($completed_count / $total_required) * 100, 2);
    } else {
        $completion_rate = 0;
    }
    
    // تعداد اطلاعات ناقص
    $incomplete_count = $total_required - $completed_count;
    
    $response = [
        'total_personnel' => $total_personnel,
        'active_personnel' => $active_personnel,
        'completion_rate' => $completion_rate,
        'incomplete_count' => $incomplete_count
    ];
    
    wp_send_json_success($response);
}
add_action('wp_ajax_workforce_get_department_stats', 'workforce_ajax_get_department_stats');

function workforce_ajax_get_field_stats() {
    check_ajax_referer('workforce_nonce', 'nonce');
    
    $field_id = intval($_POST['field_id']);
    $department_id = intval($_POST['department_id']);
    $period_id = isset($_POST['period_id']) ? intval($_POST['period_id']) : null;
    
    $field = workforce_get_field($field_id);
    if (!$field) {
        wp_send_json_error(['message' => 'فیلد یافت نشد.']);
    }
    
    global $wpdb;
    $meta_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel_meta';
    $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
    
    // محاسبه مجموع یا تعداد بر اساس نوع فیلد
    if (in_array($field->field_type, ['number', 'decimal'])) {
        // برای فیلدهای عددی: مجموع
        $query = "SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2))) as total 
                  FROM $meta_table pm 
                  INNER JOIN $personnel_table p ON pm.personnel_id = p.id 
                  WHERE p.department_id = %d AND p.is_deleted = 0 
                  AND pm.meta_key = %s";
        $params = [$department_id, $field->field_name];
        
        if ($period_id) {
            $query .= " AND pm.period_id = %d";
            $params[] = $period_id;
        } else {
            $query .= " AND pm.period_id IS NULL";
        }
        
        $total = $wpdb->get_var($wpdb->prepare($query, $params)) ?: 0;
    } else {
        // برای سایر فیلدها: تعداد مقادیر غیرخالی
        $query = "SELECT COUNT(*) as total 
                  FROM $meta_table pm 
                  INNER JOIN $personnel_table p ON pm.personnel_id = p.id 
                  WHERE p.department_id = %d AND p.is_deleted = 0 
                  AND pm.meta_key = %s AND pm.meta_value != ''";
        $params = [$department_id, $field->field_name];
        
        if ($period_id) {
            $query .= " AND pm.period_id = %d";
            $params[] = $period_id;
        } else {
            $query .= " AND pm.period_id IS NULL";
        }
        
        $total = $wpdb->get_var($wpdb->prepare($query, $params)) ?: 0;
    }
    
    wp_send_json_success(['total' => $total]);
}
add_action('wp_ajax_workforce_get_field_stats', 'workforce_ajax_get_field_stats');

function workforce_ajax_get_unique_values() {
    check_ajax_referer('workforce_nonce', 'nonce');
    
    $field_id = intval($_POST['field_id']);
    $department_id = intval($_POST['department_id']);
    $period_id = isset($_POST['period_id']) ? intval($_POST['period_id']) : null;
    
    $field = workforce_get_field($field_id);
    if (!$field) {
        wp_send_json_error(['message' => 'فیلد یافت نشد.']);
    }
    
    global $wpdb;
    $meta_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel_meta';
    $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
    
    $query = "SELECT DISTINCT pm.meta_value 
              FROM $meta_table pm 
              INNER JOIN $personnel_table p ON pm.personnel_id = p.id 
              WHERE p.department_id = %d AND p.is_deleted = 0 
              AND pm.meta_key = %s";
    $params = [$department_id, $field->field_name];
    
    if ($period_id) {
        $query .= " AND pm.period_id = %d";
        $params[] = $period_id;
    } else {
        $query .= " AND pm.period_id IS NULL";
    }
    
    $query .= " ORDER BY pm.meta_value ASC";
    
    $results = $wpdb->get_col($wpdb->prepare($query, $params));
    
    wp_send_json_success(['values' => $results]);
}
add_action('wp_ajax_workforce_get_unique_values', 'workforce_ajax_get_unique_values');

function workforce_ajax_get_personnel_form() {
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
    $active_period = workforce_get_active_period();
    
    ob_start();
    ?>
    <form id="personnelEditForm">
        <input type="hidden" name="personnel_id" value="<?php echo esc_attr($personnel->id); ?>">
        
        <div class="form-sections">
            <div class="form-section">
                <h4>اطلاعات پایه</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_national_code">کدملی</label>
                        <input type="text" id="edit_national_code" name="national_code" 
                               value="<?php echo esc_attr($personnel->national_code); ?>"
                               <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_first_name">نام</label>
                        <input type="text" id="edit_first_name" name="first_name" 
                               value="<?php echo esc_attr($personnel->first_name); ?>"
                               <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_last_name">نام خانوادگی</label>
                        <input type="text" id="edit_last_name" name="last_name" 
                               value="<?php echo esc_attr($personnel->last_name); ?>"
                               <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_employment_date">تاریخ استخدام</label>
                        <input type="text" id="edit_employment_date" name="employment_date" 
                               class="jdatepicker" value="<?php echo esc_attr($personnel->employment_date); ?>"
                               <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_employment_type">نوع استخدام</label>
                        <select id="edit_employment_type" name="employment_type" 
                                <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                            <option value="permanent" <?php selected($personnel->employment_type, 'permanent'); ?>>دائمی</option>
                            <option value="contract" <?php selected($personnel->employment_type, 'contract'); ?>>پیمانی</option>
                            <option value="temporary" <?php selected($personnel->employment_type, 'temporary'); ?>>موقت</option>
                            <option value="project" <?php selected($personnel->employment_type, 'project'); ?>>پروژه‌ای</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_status">وضعیت</label>
                        <select id="edit_status" name="status" 
                                <?php echo $mode === 'view' ? 'disabled' : ''; ?>>
                            <option value="active" <?php selected($personnel->status, 'active'); ?>>فعال</option>
                            <option value="inactive" <?php selected($personnel->status, 'inactive'); ?>>غیرفعال</option>
                            <option value="suspended" <?php selected($personnel->status, 'suspended'); ?>>تعلیق</option>
                            <option value="retired" <?php selected($personnel->status, 'retired'); ?>>بازنشسته</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h4>اطلاعات تکمیلی - دوره: <?php echo $active_period ? esc_html($active_period->name) : 'بدون دوره'; ?></h4>
                <div class="form-grid">
                    <?php foreach ($fields as $field): ?>
                        <?php if (!in_array($field->field_name, ['national_code', 'first_name', 'last_name', 'employment_date'])): ?>
                            <?php
                            $value = $meta[$field->id] ?? $meta[$field->field_name] ?? '';
                            $required = $field->is_required ? ' required' : '';
                            $disabled = ($field->is_locked || $mode === 'view') ? ' disabled' : '';
                            ?>
                            <div class="form-group">
                                <label for="edit_field_<?php echo esc_attr($field->id); ?>">
                                    <?php echo esc_html($field->field_label); ?>
                                    <?php if ($field->is_required): ?><span class="required">*</span><?php endif; ?>
                                    <?php if ($field->is_locked): ?><span title="قفل شده">🔒</span><?php endif; ?>
                                </label>
                                
                                <?php if ($field->field_type === 'select' && $field->options): ?>
                                    <select id="edit_field_<?php echo esc_attr($field->id); ?>" 
                                            name="field_<?php echo esc_attr($field->id); ?>"
                                            class="<?php echo $required . $disabled; ?>">
                                        <option value="">انتخاب کنید</option>
                                        <?php foreach ($field->options as $option): ?>
                                            <option value="<?php echo esc_attr($option); ?>" 
                                                <?php selected($value, $option); ?>>
                                                <?php echo esc_html($option); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($field->field_type === 'date'): ?>
                                    <input type="text" id="edit_field_<?php echo esc_attr($field->id); ?>" 
                                           name="field_<?php echo esc_attr($field->id); ?>"
                                           class="jdatepicker<?php echo $required . $disabled; ?>"
                                           value="<?php echo esc_attr($value); ?>">
                                <?php elseif ($field->field_type === 'checkbox'): ?>
                                    <input type="checkbox" id="edit_field_<?php echo esc_attr($field->id); ?>" 
                                           name="field_<?php echo esc_attr($field->id); ?>"
                                           value="1" <?php checked($value, '1'); echo $disabled; ?>>
                                <?php else: ?>
                                    <input type="<?php echo $field->field_type === 'number' ? 'number' : 'text'; ?>" 
                                           id="edit_field_<?php echo esc_attr($field->id); ?>" 
                                           name="field_<?php echo esc_attr($field->id); ?>"
                                           class="<?php echo $required . $disabled; ?>"
                                           value="<?php echo esc_attr($value); ?>"
                                           <?php echo $field->field_type === 'number' ? 'step="0.01"' : ''; ?>>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </form>
    <?php
    
    $html = ob_get_clean();
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_workforce_get_personnel_form', 'workforce_ajax_get_personnel_form');

function workforce_ajax_update_personnel() {
    // دیباگ: لاگ همه داده‌ها
    error_log('=== AJAX UPDATE PERSONNEL CALLED ===');
    error_log('POST Data: ' . print_r($_POST, true));
    
    // بررسی nonce - از هر دو حالت
    $nonce = $_POST['nonce'] ?? $_POST['_wpnonce'] ?? '';
    error_log('Nonce received: ' . $nonce);
    
    if (!wp_verify_nonce($nonce, 'workforce_nonce')) {
        error_log('Nonce verification FAILED');
        wp_send_json_error(['message' => 'توکن امنیتی نامعتبر است.']);
    }
    
    error_log('Nonce verification SUCCESS');
    
    $personnel_id = intval($_POST['personnel_id'] ?? 0);
    error_log('Personnel ID: ' . $personnel_id);
    
    if (!$personnel_id) {
        wp_send_json_error(['message' => 'شناسه پرسنل نامعتبر است.']);
    }
    
    // گرفتن اطلاعات فعلی
    $personnel = workforce_get_personnel($personnel_id);
    if (!$personnel) {
        wp_send_json_error(['message' => 'پرسنل یافت نشد.']);
    }
    
    // آماده‌سازی داده‌های جدید
    $data_after = [];
    
    // فیلدهای اصلی
    $fields_to_update = ['national_code', 'first_name', 'last_name', 'employment_date', 'employment_type', 'status'];
    foreach ($fields_to_update as $field) {
        if (isset($_POST[$field])) {
            $value = sanitize_text_field($_POST[$field]);
            
            // اصلاح تاریخ نادرست
            if ($field === 'employment_date' && ($value === '0000-00-00' || empty($value))) {
                $value = $personnel->employment_date; // نگه داشتن تاریخ قبلی
            }
            
            $data_after[$field] = $value;
            error_log("Field $field: " . $value);
        }
    }
    
    // اضافه کردن فیلدهای متا
    $fields = workforce_get_all_fields();
    $meta_updates = [];
    
    foreach ($fields as $field) {
        if (!in_array($field->field_name, ['national_code', 'first_name', 'last_name', 'employment_date'])) {
            $field_name = 'field_' . $field->id;
            if (isset($_POST[$field_name])) {
                $value = $field->field_type === 'checkbox' ? 
                         (isset($_POST[$field_name]) ? '1' : '0') : 
                         sanitize_text_field($_POST[$field_name]);
                $meta_updates[$field->id] = $value;
                error_log("Meta field {$field->field_name}: " . $value);
            }
        }
    }
    
    // بررسی تغییرات
    $has_changes = false;
    
    // مقایسه فیلدهای اصلی
    foreach ($data_after as $key => $value) {
        $before_value = $personnel->$key ?? '';
        if ($before_value != $value) {
            $has_changes = true;
            error_log("Change detected in $key: $before_value -> $value");
            break;
        }
    }
    
    // اگر تغییر اصلی نیست، متا فیلدها رو چک کن
    if (!$has_changes && !empty($meta_updates)) {
        $current_meta = workforce_get_personnel_meta($personnel_id);
        foreach ($meta_updates as $field_id => $value) {
            $before_value = $current_meta[$field_id] ?? '';
            if ($before_value != $value) {
                $has_changes = true;
                error_log("Meta change detected in field $field_id");
                break;
            }
        }
    }
    
    if (!$has_changes) {
        wp_send_json_error(['message' => 'تغییری ایجاد نشده است.']);
    }
    
    // بررسی فیلدهای قفل‌شده
    foreach ($fields as $field) {
        if ($field->is_locked) {
            $field_name = 'field_' . $field->id;
            if (isset($_POST[$field_name])) {
                $current_meta = workforce_get_personnel_meta($personnel_id);
                $before_value = $current_meta[$field->id] ?? '';
                $after_value = sanitize_text_field($_POST[$field_name]);
                
                if ($before_value != $after_value) {
                    wp_send_json_error([
                        'message' => 'شما اجازه ویرایش فیلد قفل‌شده "' . $field->field_label . '" را ندارید.'
                    ]);
                }
            }
        }
    }
    
    // ایجاد درخواست تایید
    $data_before = [
        'national_code' => $personnel->national_code,
        'first_name' => $personnel->first_name,
        'last_name' => $personnel->last_name,
        'employment_date' => $personnel->employment_date,
        'employment_type' => $personnel->employment_type,
        'status' => $personnel->status,
        'meta' => workforce_get_personnel_meta($personnel_id)
    ];
    
    $approval_data = [
        'request_type' => 'edit_personnel',
        'requester_id' => get_current_user_id(),
        'target_id' => $personnel_id,
        'target_type' => 'personnel',
        'data_before' => $data_before,
        'data_after' => [
            'personnel' => $data_after,
            'meta' => $meta_updates
        ],
    ];
    
    error_log('Creating approval request...');
    $approval_id = workforce_add_approval_request($approval_data);
    
    if ($approval_id) {
        error_log('Approval created with ID: ' . $approval_id);
        
        // لاگ فعالیت
        workforce_log_activity(
            get_current_user_id(),
            'request_edit_personnel',
            "درخواست ویرایش پرسنل ID: $personnel_id"
        );
        
        wp_send_json_success([
            'message' => 'تغییرات با موفقیت ثبت شد و در انتظار تایید است.',
            'approval_id' => $approval_id,
            'debug' => [
                'personnel_id' => $personnel_id,
                'fields_updated' => array_keys($data_after),
                'meta_updated' => array_keys($meta_updates)
            ]
        ]);
    } else {
        error_log('Failed to create approval');
        wp_send_json_error(['message' => 'خطا در ثبت درخواست.']);
    }
}

// ثبت hook با نام جدید
add_action('wp_ajax_workforce_update_personnel', 'workforce_ajax_update_personnel');
remove_action('wp_ajax_workforce_save_personnel', 'workforce_ajax_save_personnel'); // اگر قبلاً ثبت شده

function workforce_ajax_update_personnel_nopriv() {
    wp_send_json_error(['message' => 'نیاز به لاگین دارد.']);
}

function workforce_ajax_request_delete_personnel() {
    check_ajax_referer('workforce_nonce', 'nonce');
    
    $personnel_id = intval($_POST['personnel_id']);
    $current_user_id = get_current_user_id();
    
    $personnel = workforce_get_personnel($personnel_id);
    if (!$personnel) {
        wp_send_json_error(['message' => 'پرسنل یافت نشد.']);
    }
    
    // ایجاد درخواست تایید
    $approval_data = [
        'request_type' => 'delete_personnel',
        'requester_id' => $current_user_id,
        'target_id' => $personnel_id,
        'target_type' => 'personnel',
        'data_before' => [
            'id' => $personnel->id,
            'name' => $personnel->first_name . ' ' . $personnel->last_name,
            'national_code' => $personnel->national_code,
        ],
    ];
    
    $approval_id = workforce_add_approval_request($approval_data);
    
    if ($approval_id) {
        workforce_log_activity(
            $current_user_id,
            'request_delete_personnel',
            "درخواست حذف پرسنل ID: $personnel_id - " . $personnel->first_name . ' ' . $personnel->last_name
        );
        
        wp_send_json_success(['message' => 'درخواست حذف با موفقیت ثبت شد و در انتظار تایید است.']);
    } else {
        wp_send_json_error(['message' => 'خطا در ثبت درخواست.']);
    }
}
add_action('wp_ajax_workforce_request_delete_personnel', 'workforce_ajax_request_delete_personnel');

function workforce_ajax_request_bulk_delete() {
    check_ajax_referer('workforce_nonce', 'nonce');
    
    $personnel_ids = $_POST['personnel_ids'] ?? [];
    $current_user_id = get_current_user_id();
    
    if (empty($personnel_ids)) {
        wp_send_json_error(['message' => 'هیچ ردیفی انتخاب نشده است.']);
    }
    
    $success_count = 0;
    foreach ($personnel_ids as $personnel_id) {
        $personnel_id = intval($personnel_id);
        $personnel = workforce_get_personnel($personnel_id);
        
        if ($personnel) {
            $approval_data = [
                'request_type' => 'delete_personnel',
                'requester_id' => $current_user_id,
                'target_id' => $personnel_id,
                'target_type' => 'personnel',
                'data_before' => [
                    'id' => $personnel->id,
                    'name' => $personnel->first_name . ' ' . $personnel->last_name,
                    'national_code' => $personnel->national_code,
                ],
            ];
            
            if (workforce_add_approval_request($approval_data)) {
                $success_count++;
            }
        }
    }
    
    if ($success_count > 0) {
        workforce_log_activity(
            $current_user_id,
            'request_bulk_delete',
            "درخواست حذف دسته‌جمعی " . count($personnel_ids) . " پرسنل"
        );
        
        wp_send_json_success([
            'message' => $success_count . ' درخواست حذف با موفقیت ثبت شد و در انتظار تایید است.'
        ]);
    } else {
        wp_send_json_error(['message' => 'خطا در ثبت درخواست‌ها.']);
    }
}
add_action('wp_ajax_workforce_request_bulk_delete', 'workforce_ajax_request_bulk_delete');

function workforce_ajax_get_additional_fields() {
    check_ajax_referer('workforce_nonce', 'nonce');
    
    $fields = workforce_get_all_fields();
    $active_period = workforce_get_active_period();
    
    ob_start();
    ?>
    <div class="form-grid">
        <?php foreach ($fields as $field): ?>
            <?php if (!in_array($field->field_name, ['national_code', 'first_name', 'last_name', 'employment_date'])): ?>
                <?php
                $required = $field->is_required ? ' required' : '';
                $disabled = $field->is_locked ? ' disabled' : '';
                ?>
                <div class="form-group">
                    <label for="new_field_<?php echo esc_attr($field->id); ?>">
                        <?php echo esc_html($field->field_label); ?>
                        <?php if ($field->is_required): ?><span class="required">*</span><?php endif; ?>
                        <?php if ($field->is_locked): ?><span title="قفل شده">🔒</span><?php endif; ?>
                    </label>
                    
                    <?php if ($field->field_type === 'select' && $field->options): ?>
                        <select id="new_field_<?php echo esc_attr($field->id); ?>" 
                                name="field_<?php echo esc_attr($field->id); ?>"
                                class="<?php echo $required . $disabled; ?>">
                            <option value="">انتخاب کنید</option>
                            <?php foreach ($field->options as $option): ?>
                                <option value="<?php echo esc_attr($option); ?>">
                                    <?php echo esc_html($option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($field->field_type === 'date'): ?>
                        <input type="text" id="new_field_<?php echo esc_attr($field->id); ?>" 
                               name="field_<?php echo esc_attr($field->id); ?>"
                               class="jdatepicker<?php echo $required . $disabled; ?>">
                    <?php elseif ($field->field_type === 'checkbox'): ?>
                        <input type="checkbox" id="new_field_<?php echo esc_attr($field->id); ?>" 
                               name="field_<?php echo esc_attr($field->id); ?>"
                               value="1"<?php echo $disabled; ?>>
                    <?php else: ?>
                        <input type="<?php echo $field->field_type === 'number' ? 'number' : 'text'; ?>" 
                               id="new_field_<?php echo esc_attr($field->id); ?>" 
                               name="field_<?php echo esc_attr($field->id); ?>"
                               class="<?php echo $required . $disabled; ?>"
                               <?php echo $field->field_type === 'number' ? 'step="0.01"' : ''; ?>>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php
    
    $html = ob_get_clean();
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_workforce_get_additional_fields', 'workforce_ajax_get_additional_fields');

function workforce_ajax_validate_national_code() {
    check_ajax_referer('workforce_nonce', 'nonce');
    
    $national_code = sanitize_text_field($_POST['national_code']);
    $department_id = intval($_POST['department_id'] ?? 0);
    
    // اعتبارسنجی فرمت
    if (!preg_match('/^[0-9]{10}$/', $national_code)) {
        wp_send_json_error(['message' => 'کدملی باید ۱۰ رقم عددی باشد.']);
    }
    
    // اعتبارسنجی الگوریتم کدملی
    if (!workforce_validate_national_code($national_code)) {
        wp_send_json_error(['message' => 'کدملی وارد شده معتبر نیست.']);
    }
    
    // بررسی تکراری نبودن در کل سیستم
    global $wpdb;
    $table_name = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
    
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE national_code = %s AND is_deleted = 0",
        $national_code
    ));
    
    if ($existing > 0) {
        wp_send_json_error(['message' => 'این کدملی قبلا در سیستم ثبت شده است.']);
    }
    
    wp_send_json_success(['message' => 'کدملی معتبر است.']);
}
add_action('wp_ajax_workforce_validate_national_code', 'workforce_ajax_validate_national_code');

function workforce_ajax_request_add_personnel() {
    check_ajax_referer('workforce_nonce', 'nonce');
    
    $current_user_id = get_current_user_id();
    $department_id = intval($_POST['department_id']);
    
    // آماده‌سازی داده‌ها
    $data = [
        'department_id' => $department_id,
        'national_code' => sanitize_text_field($_POST['national_code']),
        'first_name' => sanitize_text_field($_POST['first_name']),
        'last_name' => sanitize_text_field($_POST['last_name']),
        'employment_date' => sanitize_text_field($_POST['employment_date']),
        'employment_type' => sanitize_text_field($_POST['employment_type'] ?? 'permanent'),
        'status' => sanitize_text_field($_POST['status'] ?? 'active'),
    ];
    
    // اضافه کردن فیلدهای متا
    $fields = workforce_get_all_fields();
    $data['meta'] = [];
    foreach ($fields as $field) {
        if (!in_array($field->field_name, ['national_code', 'first_name', 'last_name', 'employment_date'])) {
            $field_name = 'field_' . $field->id;
            if (isset($_POST[$field_name])) {
                $value = $field->field_type === 'checkbox' ? 
                         (isset($_POST[$field_name]) ? '1' : '0') : 
                         sanitize_text_field($_POST[$field_name]);
                $data['meta'][$field->id] = $value;
            }
        }
    }
    
    // بررسی فیلدهای ضروری
    foreach ($fields as $field) {
        if ($field->is_required) {
            $field_name = 'field_' . $field->id;
            $value = $data['meta'][$field->id] ?? '';
            
            if (empty($value) && !in_array($field->field_name, ['national_code', 'first_name', 'last_name', 'employment_date'])) {
                wp_send_json_error(['message' => 'فیلد ضروری "' . $field->field_label . '" را پر کنید.']);
            }
        }
    }
    
    // ایجاد درخواست تایید
    $approval_data = [
        'request_type' => 'add_personnel',
        'requester_id' => $current_user_id,
        'data_after' => $data,
    ];
    
    $approval_id = workforce_add_approval_request($approval_data);
    
    if ($approval_id) {
        workforce_log_activity(
            $current_user_id,
            'request_add_personnel',
            "درخواست افزودن پرسنل جدید: " . $data['first_name'] . ' ' . $data['last_name']
        );
        
        wp_send_json_success(['message' => 'درخواست افزودن پرسنل با موفقیت ثبت شد و در انتظار تایید است.']);
    } else {
        wp_send_json_error(['message' => 'خطا در ثبت درخواست.']);
    }
}
add_action('wp_ajax_workforce_request_add_personnel', 'workforce_ajax_request_add_personnel');

function workforce_ajax_get_org_table_data() {
    check_ajax_referer('workforce_nonce', 'nonce');
    
    $department_id = $_POST['department_id'] ? intval($_POST['department_id']) : null;
    $status = sanitize_text_field($_POST['status'] ?? '');
    $search = sanitize_text_field($_POST['search'] ?? '');
    $page = intval($_POST['page']) ?: 1;
    $per_page = intval($_POST['per_page']) ?: 25;
    $offset = ($page - 1) * $per_page;
    
    global $wpdb;
    $personnel_table = $wpdb->prefix . WF_TABLE_PREFIX . 'personnel';
    $departments_table = $wpdb->prefix . WF_TABLE_PREFIX . 'departments';
    
    // ساختن کوئری
    $query = "SELECT p.*, d.name as department_name, d.color as department_color 
              FROM $personnel_table p 
              INNER JOIN $departments_table d ON p.department_id = d.id 
              WHERE p.is_deleted = 0";
    
    $params = [];
    
    if ($department_id) {
        $query .= " AND p.department_id = %d";
        $params[] = $department_id;
    }
    
    if ($status) {
        $query .= " AND p.status = %s";
        $params[] = $status;
    }
    
    if ($search) {
        $query .= " AND (p.first_name LIKE %s OR p.last_name LIKE %s OR p.national_code LIKE %s OR d.name LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($search) . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // گرفتن تعداد کل
    $count_query = "SELECT COUNT(*) FROM ($query) as count_query";
    $total_records = $wpdb->get_var($wpdb->prepare($count_query, $params));
    
    // اعمال محدودیت و مرتب‌سازی
    $query .= " ORDER BY d.name ASC, p.last_name ASC, p.first_name ASC LIMIT %d OFFSET %d";
    $params[] = $per_page;
    $params[] = $offset;
    
    $personnel = $wpdb->get_results($wpdb->prepare($query, $params));
    
    // محاسبه صفحه‌بندی
    $total_pages = ceil($total_records / $per_page);
    
    $response = [
        'rows' => $personnel,
        'pagination' => [
            'total_records' => $total_records,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'per_page' => $per_page
        ]
    ];
    
    wp_send_json_success($response);
}
add_action('wp_ajax_workforce_get_org_table_data', 'workforce_ajax_get_org_table_data');
