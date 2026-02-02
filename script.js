/**
 * اسکریپت کامل سیستم مدیریت کارکرد پرسنل بنی اسد
 * نسخه 1.0.0 - کاملاً ریسپانسیو و تعاملی
 */

(function ($) {
    'use strict';

    // ==================== GLOBAL VARIABLES ====================
    let Workforce = {
        config: {
            ajaxUrl: workforce_ajax.ajax_url,
            nonce: workforce_ajax.nonce,
            currentUserId: workforce_ajax.user_id,
            currentUserRole: workforce_ajax.user_role,
            currentPeriod: workforce_ajax.current_period,
            baseUrl: workforce_ajax.base_url,
            isRTL: true,
        },

        data: {
            fields: [],
            personnel: [],
            departments: [],
            filters: {},
            selectedRows: [],
            currentPage: 1,
            pageSize: 25,
            totalRecords: 0,
            sortColumn: null,
            sortDirection: 'asc',
        },

        elements: {},
        charts: {},
        cache: {},
    };

    // ==================== INITIALIZATION ====================
    $(document).ready(function () {
        initWorkforceSystem();
        bindEvents();
        loadInitialData();
    });

    /**
     * مقداردهی اولیه سیستم
     */
    function initWorkforceSystem() {
        console.log('🚀 Workforce System Initializing...');

        // ذخیره عناصر مهم
        Workforce.elements = {
            // ظروف اصلی
            container: $('.workforce-system'),
            mainContent: $('#wf-main-content'),

            // هدر
            header: $('.wf-header'),
            userInfo: $('.wf-user-info'),
            periodSelector: $('.wf-period-selector select'),

            // کارت‌ها
            cardsContainer: $('.wf-cards-grid'),
            cards: $('.wf-card'),

            // جدول
            tableContainer: $('.wf-table-container'),
            tableWrapper: $('.wf-table-wrapper'),
            table: $('.wf-table'),
            tableBody: $('.wf-table tbody'),
            tableHeader: $('.wf-table thead'),

            // صفحه‌بندی
            pagination: $('.wf-pagination'),
            pageSizeSelect: $('.wf-page-size select'),
            pageNumbers: $('.wf-page-numbers'),
            prevPageBtn: $('.wf-page-btn.prev'),
            nextPageBtn: $('.wf-page-btn.next'),

            // فیلترها
            filterOverlay: $('.wf-filter-overlay'),
            filterPanel: $('.wf-filter-panel'),
            filterCloseBtn: $('.wf-filter-close'),
            filterApplyBtn: $('.wf-apply-filters'),
            filterClearBtn: $('.wf-clear-filters'),

            // فرم ویرایش
            editOverlay: $('.wf-edit-overlay'),
            editPanel: $('.wf-edit-panel'),
            editCloseBtn: $('.wf-edit-close'),
            editForm: $('#wf-edit-form'),
            editPrevBtn: $('.wf-edit-prev'),
            editNextBtn: $('.wf-edit-next'),
            editSaveBtn: $('.wf-edit-save'),
            editCancelBtn: $('.wf-edit-cancel'),

            // دکمه‌های اکشن
            addPersonnelBtn: $('.wf-add-personnel'),
            deleteSelectedBtn: $('.wf-delete-selected'),
            exportExcelBtn: $('.wf-export-excel'),
            importExcelBtn: $('.wf-import-excel'),
            printBtn: $('.wf-print'),
            refreshBtn: $('.wf-refresh'),

            // حالت‌های مختلف
            loadingState: $('.wf-loading'),
            emptyState: $('.wf-empty-state'),
            errorState: $('.wf-error-state'),

            // هشدارها
            alertsContainer: $('.wf-alerts-container'),

            // جستجو
            searchInput: $('.wf-search-input'),
            searchBtn: $('.wf-search-btn'),

            // انتخاب ردیف
            selectAllCheckbox: $('.wf-select-all'),
            rowCheckboxes: $('.wf-row-checkbox'),
        };

        // فعال کردن ویژگی‌های پیشرفته اگر مرورگر پشتیبانی کند
        enableAdvancedFeatures();

        console.log('✅ Workforce System Initialized');
    }

    /**
     * فعال کردن ویژگی‌های پیشرفته
     */
    function enableAdvancedFeatures() {
        // تشخیص پشتیبانی از LocalStorage
        Workforce.supportsLocalStorage = typeof Storage !== 'undefined';

        // تشخیص پشتیبانی از Drag & Drop
        Workforce.supportsDragDrop = 'draggable' in document.createElement('div');

        // تشخیص پشتیبانی از Clipboard API
        Workforce.supportsClipboard = 'clipboard' in navigator;

        // تنظیم کلیدهای میانبر
        if (Workforce.supportsLocalStorage) {
            setupKeyboardShortcuts();
        }
    }

    // ==================== EVENT HANDLING ====================

    /**
     * اتصال کلیه رویدادها
     */
    function bindEvents() {
        bindTableEvents();
        bindCardEvents();
        bindFilterEvents();
        bindEditFormEvents();
        bindActionEvents();
        bindPaginationEvents();
        bindSearchEvents();
        bindKeyboardEvents();
        bindWindowEvents();
    }

    /**
     * رویدادهای جدول
     */
    function bindTableEvents() {
        // کلیک روی هدر ستون برای مرتب‌سازی
        $(document).on('click', '.wf-table th', function (e) {
            if ($(e.target).hasClass('wf-column-btn')) return;
            sortTable($(this).data('field'));
        });

        // کلیک روی ردیف برای انتخاب
        $(document).on('click', '.wf-table tbody tr', function (e) {
            if ($(e.target).is('input[type="checkbox"]') || $(e.target).hasClass('wf-column-btn'))
                return;

            const rowId = $(this).data('id');
            toggleRowSelection(rowId);
        });

        // دابل کلیک روی ردیف برای ویرایش
        $(document).on('dblclick', '.wf-table tbody tr', function () {
            const rowId = $(this).data('id');
            editPersonnel(rowId);
        });

        // کشیدن برای انتخاب چند ردیف
        if (Workforce.supportsDragDrop) {
            bindDragSelectionEvents();
        }

        // کلیک راست برای منو
        $(document).on('contextmenu', '.wf-table tbody tr', function (e) {
            e.preventDefault();
            showRowContextMenu(e, $(this).data('id'));
        });

        // آیکن‌های ستون
        $(document).on('click', '.wf-column-btn', function (e) {
            e.stopPropagation();
            const btnType = $(this).data('action');
            const fieldId = $(this).closest('th').data('field');

            switch (btnType) {
                case 'filter':
                    openFilterPanel(fieldId);
                    break;
                case 'pin':
                    toggleColumnPin(fieldId);
                    break;
                case 'chart':
                    createCardFromColumn(fieldId);
                    break;
                case 'sort':
                    sortTable(fieldId);
                    break;
            }
        });
    }

    /**
     * رویدادهای کارت‌ها
     */
    function bindCardEvents() {
        // کلیک روی کارت برای جزئیات
        $(document).on('click', '.wf-card', function () {
            const cardType = $(this).data('type');
            if (cardType) {
                showCardDetails(cardType);
            }
        });

        // دکمه بستن کارت داینامیک
        $(document).on('click', '.wf-card-close', function (e) {
            e.stopPropagation();
            const cardId = $(this).closest('.wf-card').data('card-id');
            removeDynamicCard(cardId);
        });

        // رفرش کارت
        $(document).on('click', '.wf-card-refresh', function (e) {
            e.stopPropagation();
            const cardId = $(this).closest('.wf-card').data('card-id');
            refreshCard(cardId);
        });
    }

    /**
     * رویدادهای فیلتر
     */
    function bindFilterEvents() {
        // باز کردن پنل فیلتر
        $(document).on('click', '.wf-open-filter', function () {
            const fieldId = $(this).data('field') || 'all';
            openFilterPanel(fieldId);
        });

        // بستن پنل فیلتر
        $(document).on('click', '.wf-filter-close', closeFilterPanel);
        $(document).on('click', '.wf-filter-overlay', function (e) {
            if ($(e.target).hasClass('wf-filter-overlay')) {
                closeFilterPanel();
            }
        });

        // اعمال فیلترها
        $(document).on('click', '.wf-apply-filters', applyFilters);

        // پاک کردن فیلترها
        $(document).on('click', '.wf-clear-filters', clearFilters);

        // تغییر در گزینه‌های فیلتر
        $(document).on('change', '.wf-filter-option input', updateFilterPreview);
    }

    /**
     * رویدادهای فرم ویرایش
     */
    function bindEditFormEvents() {
        // بستن فرم ویرایش
        $(document).on('click', '.wf-edit-close', closeEditForm);
        $(document).on('click', '.wf-edit-overlay', function (e) {
            if ($(e.target).hasClass('wf-edit-overlay')) {
                closeEditForm();
            }
        });

        // ناوبری بین رکوردها
        $(document).on('click', '.wf-edit-prev', showPrevRecord);
        $(document).on('click', '.wf-edit-next', showNextRecord);

        // ذخیره تغییرات
        $(document).on('click', '.wf-edit-save', saveEditForm);

        // لغو ویرایش
        $(document).on('click', '.wf-edit-cancel', closeEditForm);

        // تغییر در فیلدهای فرم
        $(document).on(
            'change keyup',
            '.wf-form-input, .wf-form-select, .wf-form-textarea',
            function () {
                validateField($(this));
            }
        );
    }

    /**
     * رویدادهای دکمه‌های اکشن
     */
    function bindActionEvents() {
        // افزودن پرسنل جدید
        $(document).on('click', '.wf-add-personnel', addNewPersonnel);

        // حذف انتخاب‌شده‌ها
        $(document).on('click', '.wf-delete-selected', deleteSelectedPersonnel);

        // خروجی اکسل
        $(document).on('click', '.wf-export-excel', exportToExcel);

        // ورود اکسل
        $(document).on('click', '.wf-import-excel', importFromExcel);

        // پرینت
        $(document).on('click', '.wf-print', printTable);

        // رفرش داده‌ها
        $(document).on('click', '.wf-refresh', refreshData);
    }

    /**
     * رویدادهای صفحه‌بندی
     */
    function bindPaginationEvents() {
        // تغییر سایز صفحه
        $(document).on('change', '.wf-page-size select', function () {
            Workforce.data.pageSize = parseInt($(this).val());
            Workforce.data.currentPage = 1;
            loadPersonnelData();
        });

        // تغییر صفحه
        $(document).on('click', '.wf-page-btn:not(.disabled)', function () {
            const pageAction = $(this).data('action');
            const pageNum = $(this).data('page');

            if (pageAction === 'prev' && Workforce.data.currentPage > 1) {
                Workforce.data.currentPage--;
            } else if (pageAction === 'next' && Workforce.data.currentPage < getTotalPages()) {
                Workforce.data.currentPage++;
            } else if (pageNum) {
                Workforce.data.currentPage = pageNum;
            }

            loadPersonnelData();
        });
    }

    /**
     * رویدادهای جستجو
     */
    function bindSearchEvents() {
        // جستجوی لحظه‌ای
        $(document).on('keyup', '.wf-search-input', function (e) {
            clearTimeout(Workforce.searchTimeout);
            Workforce.searchTimeout = setTimeout(() => {
                performSearch($(this).val());
            }, 300);
        });

        // دکمه جستجو
        $(document).on('click', '.wf-search-btn', function () {
            performSearch($('.wf-search-input').val());
        });

        // پاک کردن جستجو
        $(document).on('click', '.wf-search-clear', function () {
            $('.wf-search-input').val('');
            performSearch('');
        });
    }

    /**
     * رویدادهای کیبورد
     */
    function bindKeyboardEvents() {
        $(document).on('keydown', function (e) {
            // فقط اگر در حالت ویرایش یا فیلتر نباشیم
            if ($('.wf-edit-overlay.active').length || $('.wf-filter-overlay.active').length) {
                return;
            }

            // کلیدهای ترکیبی
            if (e.ctrlKey || e.metaKey) {
                switch (e.key.toLowerCase()) {
                    case 's': // ذخیره
                        e.preventDefault();
                        if (Workforce.currentEditId) {
                            saveEditForm();
                        }
                        break;
                    case 'f': // جستجو
                        e.preventDefault();
                        $('.wf-search-input').focus();
                        break;
                    case 'e': // خروجی
                        e.preventDefault();
                        exportToExcel();
                        break;
                    case 'n': // جدید
                        e.preventDefault();
                        addNewPersonnel();
                        break;
                    case 'd': // حذف
                        e.preventDefault();
                        deleteSelectedPersonnel();
                        break;
                    case 'r': // رفرش
                        e.preventDefault();
                        refreshData();
                        break;
                    case 'p': // پرینت
                        e.preventDefault();
                        printTable();
                        break;
                    case 'arrowleft': // قبلی
                        e.preventDefault();
                        if (Workforce.currentEditId) showPrevRecord();
                        break;
                    case 'arrowright': // بعدی
                        e.preventDefault();
                        if (Workforce.currentEditId) showNextRecord();
                        break;
                }
            }

            // کلیدهای تک
            switch (e.key) {
                case 'Escape': // بستن
                    if ($('.wf-edit-overlay.active').length) {
                        closeEditForm();
                    } else if ($('.wf-filter-overlay.active').length) {
                        closeFilterPanel();
                    }
                    break;
                case 'ArrowUp': // بالا
                    if (e.altKey && Workforce.currentEditId) {
                        e.preventDefault();
                        showPrevRecord();
                    }
                    break;
                case 'ArrowDown': // پایین
                    if (e.altKey && Workforce.currentEditId) {
                        e.preventDefault();
                        showNextRecord();
                    }
                    break;
                case 'Delete': // حذف
                    if (Workforce.data.selectedRows.length > 0) {
                        deleteSelectedPersonnel();
                    }
                    break;
                case 'Enter': // ویرایش
                    if (Workforce.data.selectedRows.length === 1) {
                        editPersonnel(Workforce.data.selectedRows[0]);
                    }
                    break;
            }
        });
    }

    /**
     * رویدادهای پنجره
     */
    function bindWindowEvents() {
        // تغییر سایز پنجره
        $(window).on(
            'resize',
            debounce(function () {
                adjustTableLayout();
                updateCardsLayout();
            }, 250)
        );

        // جلوگیری از بسته شدن صفحه در صورت ذخیره نشدن تغییرات
        $(window).on('beforeunload', function (e) {
            if (Workforce.unsavedChanges) {
                e.preventDefault();
                e.returnValue =
                    'تغییرات ذخیره نشده‌ای دارید. آیا مطمئنید که می‌خواهید صفحه را ترک کنید؟';
                return e.returnValue;
            }
        });

        // کلیک خارج از منوهای باز
        $(document).on('click', function (e) {
            // بستن منوهای زمینه
            if (!$(e.target).closest('.wf-context-menu').length) {
                $('.wf-context-menu').remove();
            }

            // بستن پاپ‌آپ‌ها
            if (
                !$(e.target).closest('.wf-popup').length &&
                !$(e.target).hasClass('wf-popup-trigger')
            ) {
                $('.wf-popup').remove();
            }
        });
    }

    // ==================== DATA LOADING ====================

    /**
     * بارگذاری داده‌های اولیه
     */
    function loadInitialData() {
        showLoading();

        // بارگذاری همزمان داده‌های مختلف
        Promise.all([loadFields(), loadDepartments(), loadPersonnelData(), loadDashboardStats()])
            .then(() => {
                hideLoading();
                renderTable();
                renderCards();
                setupCharts();
                showAlert('success', 'سیستم با موفقیت بارگذاری شد', 'خوش آمدید!');
            })
            .catch((error) => {
                hideLoading();
                showError('خطا در بارگذاری داده‌ها', error.message);
            });
    }

    /**
     * بارگذاری فیلدها
     */
    function loadFields() {
        return new Promise((resolve, reject) => {
            if (Workforce.cache.fields && Workforce.cache.fields.length > 0) {
                Workforce.data.fields = Workforce.cache.fields;
                resolve();
                return;
            }

            $.ajax({
                url: Workforce.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wf_get_fields',
                    nonce: Workforce.config.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        Workforce.data.fields = response.data;
                        Workforce.cache.fields = response.data;
                        resolve();
                    } else {
                        reject(new Error(response.data || 'خطا در دریافت فیلدها'));
                    }
                },
                error: function (xhr, status, error) {
                    reject(new Error('خطای شبکه در دریافت فیلدها'));
                },
            });
        });
    }

    /**
     * بارگذاری ادارات
     */
    function loadDepartments() {
        return new Promise((resolve, reject) => {
            if (Workforce.cache.departments && Workforce.cache.departments.length > 0) {
                Workforce.data.departments = Workforce.cache.departments;
                resolve();
                return;
            }

            $.ajax({
                url: Workforce.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wf_get_departments',
                    nonce: Workforce.config.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        Workforce.data.departments = response.data;
                        Workforce.cache.departments = response.data;
                        resolve();
                    } else {
                        reject(new Error(response.data || 'خطا در دریافت ادارات'));
                    }
                },
                error: function (xhr, status, error) {
                    reject(new Error('خطای شبکه در دریافت ادارات'));
                },
            });
        });
    }

    /**
     * بارگذاری داده‌های پرسنل
     */
    function loadPersonnelData() {
        return new Promise((resolve, reject) => {
            showTableLoading();

            const data = {
                action: 'wf_get_personnel',
                nonce: Workforce.config.nonce,
                page: Workforce.data.currentPage,
                page_size: Workforce.data.pageSize,
                filters: Workforce.data.filters,
            };

            // اضافه کردن مرتب‌سازی
            if (Workforce.data.sortColumn) {
                data.sort_by = Workforce.data.sortColumn;
                data.sort_dir = Workforce.data.sortDirection;
            }

            $.ajax({
                url: Workforce.config.ajaxUrl,
                type: 'POST',
                data: data,
                success: function (response) {
                    if (response.success) {
                        Workforce.data.personnel = response.data.personnel;
                        Workforce.data.totalRecords = response.data.total;

                        // ذخیره در کش
                        const cacheKey = `personnel_page_${Workforce.data.currentPage}_size_${Workforce.data.pageSize}`;
                        Workforce.cache[cacheKey] = {
                            data: response.data.personnel,
                            timestamp: Date.now(),
                            filters: JSON.stringify(Workforce.data.filters),
                        };

                        renderTable();
                        updatePagination();
                        resolve();
                    } else {
                        reject(new Error(response.data || 'خطا در دریافت داده‌های پرسنل'));
                    }
                },
                error: function (xhr, status, error) {
                    reject(new Error('خطای شبکه در دریافت داده‌های پرسنل'));
                },
                complete: function () {
                    hideTableLoading();
                },
            });
        });
    }

    /**
     * بارگذاری آمار داشبورد
     */
    function loadDashboardStats() {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: Workforce.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wf_get_dashboard_stats',
                    nonce: Workforce.config.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        Workforce.data.stats = response.data;
                        resolve();
                    } else {
                        // اگر خطا داد، با داده‌های پیش‌فرض ادامه بده
                        Workforce.data.stats = getDefaultStats();
                        resolve();
                    }
                },
                error: function () {
                    // در صورت خطا، با داده‌های پیش‌فرض ادامه بده
                    Workforce.data.stats = getDefaultStats();
                    resolve();
                },
            });
        });
    }

    // ==================== TABLE FUNCTIONS ====================

    /**
     * رندر جدول
     */
    function renderTable() {
        const $tbody = Workforce.elements.tableBody;
        $tbody.empty();

        if (Workforce.data.personnel.length === 0) {
            showEmptyTableState();
            return;
        }

        // رندر هر ردیف
        Workforce.data.personnel.forEach((person, index) => {
            const $row = createTableRow(person, index);
            $tbody.append($row);
        });

        // به‌روزرسانی وضعیت انتخاب
        updateSelectionState();

        // اعمال رنگ‌بندی شرطی
        applyRowStyling();

        // تنظیم layout
        adjustTableLayout();
    }

    /**
     * ایجاد ردیف جدول
     */
    function createTableRow(person, index) {
        const isSelected = Workforce.data.selectedRows.includes(person.id);
        const isDeleted = person.status === 'deleted' || person.is_deleted;
        const rowClass = isDeleted ? 'deleted' : '';
        const selectedClass = isSelected ? 'selected' : '';

        let rowHtml = `
            <tr data-id="${person.id}" 
                data-index="${index}"
                class="${rowClass} ${selectedClass}"
                data-status="${person.status || 'active'}">
                <td class="wf-row-selector">
                    <input type="checkbox" 
                           class="wf-row-checkbox" 
                           data-id="${person.id}"
                           ${isSelected ? 'checked' : ''}>
                </td>
        `;

        // ستون‌های داده
        Workforce.data.fields.forEach((field) => {
            if (!field.show_in_table) return;

            const value = getFieldValue(person, field);
            const cellClass = getCellClass(field, value);
            const cellStyle = getCellStyle(field, value);

            rowHtml += `
                <td class="${cellClass}"
                    data-field="${field.field_key}"
                    data-value="${value}"
                    ${cellStyle ? `style="${cellStyle}"` : ''}
                    title="${getFieldTitle(field, value)}">
                    ${formatCellValue(value, field.field_type)}
                </td>
            `;
        });

        rowHtml += '</tr>';
        return $(rowHtml);
    }

    /**
     * مرتب‌سازی جدول
     */
    function sortTable(fieldId) {
        if (!fieldId) return;

        // اگر ستون فعلی بود، جهت را تغییر بده
        if (Workforce.data.sortColumn === fieldId) {
            Workforce.data.sortDirection = Workforce.data.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            Workforce.data.sortColumn = fieldId;
            Workforce.data.sortDirection = 'asc';
        }

        // آپدیت آیکن‌های مرتب‌سازی
        updateSortIcons();

        // بارگذاری مجدد داده‌ها
        loadPersonnelData();
    }

    /**
     * جستجو در جدول
     */
    function performSearch(query) {
        if (!query || query.trim() === '') {
            // اگر جستجو خالی بود، فیلتر جستجو را حذف کن
            delete Workforce.data.filters._search;
        } else {
            // فیلتر جستجو را اضافه کن
            Workforce.data.filters._search = {
                query: query.trim(),
                fields: ['first_name', 'last_name', 'national_code', 'data'],
            };
        }

        Workforce.data.currentPage = 1;
        loadPersonnelData();
    }

    // ==================== FILTER FUNCTIONS ====================

    /**
     * باز کردن پنل فیلتر
     */
    function openFilterPanel(fieldId) {
        // بارگذاری گزینه‌های فیلتر
        loadFilterOptions(fieldId)
            .then((options) => {
                renderFilterPanel(fieldId, options);
                Workforce.elements.filterOverlay.addClass('active');

                // جلوگیری از اسکرول بدنه
                $('body').addClass('wf-no-scroll');
            })
            .catch((error) => {
                showAlert('error', 'خطا در بارگذاری فیلترها', error.message);
            });
    }

    /**
     * بستن پنل فیلتر
     */
    function closeFilterPanel() {
        Workforce.elements.filterOverlay.removeClass('active');
        $('body').removeClass('wf-no-scroll');
    }

    /**
     * اعمال فیلترها
     */
    function applyFilters() {
        const activeFilters = {};
        const $filterPanel = Workforce.elements.filterPanel;

        // جمع‌آوری فیلترهای فعال
        $filterPanel.find('.wf-filter-option input:checked').each(function () {
            const fieldId = $(this).data('field');
            const value = $(this).val();

            if (!activeFilters[fieldId]) {
                activeFilters[fieldId] = [];
            }
            activeFilters[fieldId].push(value);
        });

        // فیلترهای پیشرفته
        $filterPanel.find('.wf-advanced-filter').each(function () {
            const fieldId = $(this).data('field');
            const operator = $(this).data('operator');
            const value = $(this).val();

            if (value) {
                activeFilters[fieldId] = {
                    operator: operator,
                    value: value,
                };
            }
        });

        // ذخیره فیلترها
        Workforce.data.filters = activeFilters;
        Workforce.data.currentPage = 1;

        // بستن پنل و بارگذاری مجدد
        closeFilterPanel();
        loadPersonnelData();

        // نمایش تعداد فیلترهای فعال
        updateActiveFiltersBadge();
    }

    /**
     * پاک کردن فیلترها
     */
    function clearFilters() {
        Workforce.data.filters = {};
        Workforce.data.currentPage = 1;

        // ریست کردن چک‌باکس‌ها
        Workforce.elements.filterPanel.find('input[type="checkbox"]').prop('checked', false);
        Workforce.elements.filterPanel.find('.wf-advanced-filter').val('');

        // آپدیت نشانگر
        updateActiveFiltersBadge();

        // اگر پنل باز است، فقط پیش‌نمایش آپدیت شود
        if (Workforce.elements.filterOverlay.hasClass('active')) {
            updateFilterPreview();
        } else {
            loadPersonnelData();
        }
    }

    // ==================== EDIT FORM FUNCTIONS ====================

    /**
     * ویرایش پرسنل
     */
    function editPersonnel(personId) {
        if (!personId) {
            showAlert('error', 'خطا', 'شناسه پرسنل معتبر نیست');
            return;
        }

        // پیدا کردن اطلاعات پرسنل
        const person = Workforce.data.personnel.find((p) => p.id == personId);
        if (!person) {
            showAlert('error', 'خطا', 'اطلاعات پرسنل یافت نشد');
            return;
        }

        Workforce.currentEditId = personId;
        Workforce.unsavedChanges = false;

        // بارگذاری فرم ویرایش
        loadEditForm(person)
            .then((formHtml) => {
                Workforce.elements.editPanel.find('.wf-edit-content').html(formHtml);
                Workforce.elements.editOverlay.addClass('active');

                // جلوگیری از اسکرول بدنه
                $('body').addClass('wf-no-scroll');

                // فعال کردن اعتبارسنجی
                initFormValidation();

                // نمایش اطلاعات فعلی
                updateEditFormTitle(person);
                updateEditFormNavigation();

                // فوکوس روی اولین فیلد قابل ویرایش
                setTimeout(() => {
                    Workforce.elements.editPanel
                        .find('.wf-form-input:not(:disabled)')
                        .first()
                        .focus();
                }, 100);
            })
            .catch((error) => {
                showAlert('error', 'خطا در بارگذاری فرم', error.message);
            });
    }

    /**
     * بستن فرم ویرایش
     */
    function closeEditForm() {
        if (Workforce.unsavedChanges) {
            if (!confirm('تغییرات ذخیره نشده‌ای دارید. آیا مطمئنید که می‌خواهید ببندید؟')) {
                return;
            }
        }

        Workforce.currentEditId = null;
        Workforce.unsavedChanges = false;
        Workforce.elements.editOverlay.removeClass('active');
        $('body').removeClass('wf-no-scroll');
    }

    /**
     * ذخیره فرم ویرایش
     */
    function saveEditForm() {
        const $form = Workforce.elements.editForm;
        if (!$form.length) return;

        // اعتبارسنجی فرم
        if (!validateForm($form)) {
            showAlert('warning', 'خطا در اعتبارسنجی', 'لطفاً فیلدهای الزامی را پر کنید');
            return;
        }

        // جمع‌آوری داده‌ها
        const formData = new FormData($form[0]);
        const jsonData = {};

        formData.forEach((value, key) => {
            jsonData[key] = value;
        });

        // اضافه کردن شناسه
        jsonData.id = Workforce.currentEditId;

        // نمایش loading
        showFormLoading();

        // ارسال درخواست
        $.ajax({
            url: Workforce.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wf_save_personnel',
                nonce: Workforce.config.nonce,
                data: jsonData,
            },
            success: function (response) {
                hideFormLoading();

                if (response.success) {
                    showAlert('success', 'موفقیت', 'اطلاعات با موفقیت ذخیره شد');
                    Workforce.unsavedChanges = false;

                    // آپدیت داده‌های محلی
                    updateLocalPersonnelData(response.data);

                    // بستن فرم
                    setTimeout(() => {
                        closeEditForm();
                        loadPersonnelData(); // رفرش جدول
                    }, 1000);
                } else {
                    showAlert('error', 'خطا در ذخیره', response.data || 'خطای نامشخص');
                }
            },
            error: function (xhr, status, error) {
                hideFormLoading();
                showAlert('error', 'خطای شبکه', 'خطا در ارتباط با سرور');
            },
        });
    }

    // ==================== CARD & DASHBOARD FUNCTIONS ====================

    /**
     * رندر کارت‌ها
     */
    function renderCards() {
        const $cardsContainer = Workforce.elements.cardsContainer;
        if (!$cardsContainer.length) return;

        $cardsContainer.empty();

        // کارت‌های ثابت
        renderStaticCards($cardsContainer);

        // کارت‌های داینامیک
        renderDynamicCards($cardsContainer);
    }

    /**
     * ایجاد کارت جدید از ستون
     */
    function createCardFromColumn(fieldId) {
        const field = Workforce.data.fields.find((f) => f.field_key === fieldId);
        if (!field) return;

        // محاسبه آمار ستون
        const stats = calculateColumnStats(fieldId);

        // ایجاد کارت
        const cardId = 'card_' + fieldId + '_' + Date.now();
        const cardHtml = `
            <div class="wf-card info" data-card-id="${cardId}" data-field="${fieldId}">
                <div class="wf-card-header">
                    <div class="wf-card-icon">
                        <i class="wf-icon-chart"></i>
                    </div>
                    <div class="wf-card-actions">
                        <button class="wf-card-action-btn wf-card-refresh" title="بروزرسانی">
                            <i class="wf-icon-refresh"></i>
                        </button>
                        <button class="wf-card-action-btn wf-card-close" title="بستن">
                            <i class="wf-icon-close"></i>
                        </button>
                    </div>
                </div>
                <div class="wf-card-title">${field.field_name}</div>
                <div class="wf-card-value">${formatCardValue(stats.total, field.field_type)}</div>
                <div class="wf-card-details">
                    <div>میانگین: ${formatCardValue(stats.average, field.field_type)}</div>
                    <div>ماکزیمم: ${formatCardValue(stats.max, field.field_type)}</div>
                    <div>مینیمم: ${formatCardValue(stats.min, field.field_type)}</div>
                </div>
            </div>
        `;

        // اضافه کردن به داشبورد
        Workforce.elements.cardsContainer.append(cardHtml);

        // ذخیره در حافظه
        if (!Workforce.dynamicCards) {
            Workforce.dynamicCards = [];
        }
        Workforce.dynamicCards.push({
            id: cardId,
            fieldId: fieldId,
            type: 'column_stats',
        });

        // محدود کردن تعداد کارت‌ها
        limitDynamicCards();

        // نمایش پیام
        showAlert('success', 'کارت ایجاد شد', `کارت آماری برای "${field.field_name}" ایجاد شد`);
    }

    // ==================== EXPORT & IMPORT ====================

    /**
     * خروجی به اکسل
     */
    function exportToExcel() {
        showLoading('در حال ایجاد فایل اکسل...');

        const exportData = {
            action: 'wf_export_excel_simple',
            nonce: Workforce.config.nonce,
            manager_id: Workforce.config.currentUserId,
            export_type: 'filtered',
            filters: Workforce.data.filters,
            selected_ids: Workforce.data.selectedRows,
            include_selected: Workforce.data.selectedRows.length > 0,
            template_id: null,
        };

        $.ajax({
            url: Workforce.config.ajaxUrl,
            type: 'POST',
            data: exportData,
            xhrFields: {
                responseType: 'blob',
            },
            success: function (blob, status, xhr) {
                hideLoading();

                // گرفتن نام فایل از هدر
                const filename = getFilenameFromHeaders(xhr) || 'گزارش_پرسنل.xlsx';

                // ایجاد لینک دانلود
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();

                // پاکسازی
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                showAlert('success', 'موفقیت', 'فایل اکسل با موفقیت ایجاد و دانلود شد');
            },
            error: function (xhr, status, error) {
                hideLoading();

                // تلاش برای خواندن پاسخ JSON در صورت خطا
                try {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const response = JSON.parse(e.target.result);
                        showAlert('error', 'خطا در ایجاد فایل', response.message || 'خطای نامشخص');
                    };
                    reader.readAsText(xhr.response);
                } catch (e) {
                    showAlert('error', 'خطا', 'خطا در ایجاد فایل اکسل');
                }
            },
        });
    }

    /**
     * ورود از اکسل
     */
    function importFromExcel() {
        // ایجاد input فایل
        const $fileInput = $('<input type="file" accept=".xlsx,.xls,.csv" style="display: none;">');
        $('body').append($fileInput);

        $fileInput.on('change', function (e) {
            const file = this.files[0];
            if (!file) return;

            // نمایش dialog تأیید
            if (confirm(`آیا مایل به وارد کردن فایل "${file.name}" هستید؟`)) {
                uploadExcelFile(file);
            }

            // پاکسازی
            $fileInput.remove();
        });

        // کلیک روی input
        $fileInput.click();
    }

    // ==================== UTILITY FUNCTIONS ====================

    /**
     * نمایش loading
     */
    function showLoading(message = 'در حال بارگذاری...') {
        const loadingHtml = `
            <div class="wf-loading-overlay">
                <div class="wf-loading-content">
                    <div class="wf-loading-spinner"></div>
                    <div class="wf-loading-text">${message}</div>
                </div>
            </div>
        `;

        if ($('.wf-loading-overlay').length === 0) {
            $('body').append(loadingHtml);
        }
    }

    /**
     * پنهان کردن loading
     */
    function hideLoading() {
        $('.wf-loading-overlay').remove();
    }

    /**
     * نمایش alert
     */
    function showAlert(type, title, message, duration = 5000) {
        const alertId = 'alert_' + Date.now();
        const icon = getAlertIcon(type);

        const alertHtml = `
            <div class="wf-alert wf-alert-${type} wf-animate-slideDown" data-alert-id="${alertId}">
                <div class="wf-alert-icon">${icon}</div>
                <div class="wf-alert-content">
                    <div class="wf-alert-title">${title}</div>
                    <div class="wf-alert-message">${message}</div>
                </div>
                <button class="wf-alert-close" data-alert-id="${alertId}">
                    <i class="wf-icon-close"></i>
                </button>
            </div>
        `;

        // اضافه کردن به container
        const $container = Workforce.elements.alertsContainer;
        if ($container.length) {
            $container.prepend(alertHtml);
        } else {
            // اگر container وجود نداشت، ایجاد کن
            const $alertsContainer = $('<div class="wf-alerts-container"></div>');
            $('body').append($alertsContainer);
            $alertsContainer.prepend(alertHtml);
            Workforce.elements.alertsContainer = $alertsContainer;
        }

        // حذف خودکار بعد از مدت زمان مشخص
        setTimeout(() => {
            $(`[data-alert-id="${alertId}"]`).fadeOut(300, function () {
                $(this).remove();
            });
        }, duration);

        // رویداد بستن
        $(document).on('click', `[data-alert-id="${alertId}"] .wf-alert-close`, function () {
            $(this)
                .closest('.wf-alert')
                .fadeOut(300, function () {
                    $(this).remove();
                });
        });
    }

    /**
     * debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * گرفتن مقدار فیلد از داده‌های پرسنل
     */
    function getFieldValue(person, field) {
        if (person.data && person.data[field.field_key] !== undefined) {
            return person.data[field.field_key];
        }
        return person[field.field_key] || '';
    }

    /**
     * فرمت‌دهی مقدار سلول
     */
    function formatCellValue(value, fieldType) {
        if (value === null || value === undefined || value === '') {
            return '-';
        }

        switch (fieldType) {
            case 'date':
                return formatDate(value);
            case 'datetime':
                return formatDateTime(value);
            case 'number':
                return formatNumber(value);
            case 'decimal':
                return formatDecimal(value);
            case 'currency':
                return formatCurrency(value);
            case 'checkbox':
            case 'boolean':
                return value ? '✅' : '❌';
            default:
                return String(value);
        }
    }

    /**
     * فرمت‌دهی تاریخ
     */
    function formatDate(dateStr) {
        if (!dateStr) return '-';

        // اگر تاریخ شمسی است
        if (dateStr.includes('/')) {
            return dateStr;
        }

        // تبدیل میلادی به شمسی
        try {
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return dateStr;

            // تبدیل ساده (در نسخه واقعی از کتابخانه استفاده می‌شود)
            return date.toLocaleDateString('fa-IR');
        } catch (e) {
            return dateStr;
        }
    }

    // ==================== HELPER FUNCTIONS ====================

    /**
     * گرفتن آمار پیش‌فرض
     */
    function getDefaultStats() {
        return {
            total_personnel: 0,
            total_departments: 0,
            completion_rate: 0,
            warnings_count: 0,
            recent_activity: [],
        };
    }

    /**
     * گرفتن آیکن alert
     */
    function getAlertIcon(type) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️',
        };
        return icons[type] || 'ℹ️';
    }

    /**
     * گرفتن نام فایل از هدرهای پاسخ
     */
    function getFilenameFromHeaders(xhr) {
        const disposition = xhr.getResponseHeader('Content-Disposition');
        if (disposition && disposition.includes('filename=')) {
            const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
            const matches = filenameRegex.exec(disposition);
            if (matches != null && matches[1]) {
                return matches[1].replace(/['"]/g, '');
            }
        }
        return null;
    }

    /**
     * آپدیت نشانگر فیلترهای فعال
     */
    function updateActiveFiltersBadge() {
        const activeFiltersCount = Object.keys(Workforce.data.filters).length;
        const $badge = $('.wf-filter-badge');

        if (activeFiltersCount > 0) {
            if (!$badge.length) {
                $('.wf-open-filter').append('<span class="wf-filter-badge"></span>');
            }
            $('.wf-filter-badge').text(activeFiltersCount).show();
        } else {
            $('.wf-filter-badge').hide();
        }
    }

    // ==================== PUBLIC API ====================

    // در صورت نیاز می‌توان توابعی را عمومی کرد
    window.Workforce = {
        refresh: refreshData,
        exportExcel: exportToExcel,
        addPersonnel: addNewPersonnel,
        editPersonnel: editPersonnel,
        deletePersonnel: deleteSelectedPersonnel,
        getData: () => Workforce.data,
        getConfig: () => Workforce.config,
    };

    // ==================== FINAL INITIALIZATION ====================

    console.log('🎯 Workforce System Ready!');
})(jQuery);
