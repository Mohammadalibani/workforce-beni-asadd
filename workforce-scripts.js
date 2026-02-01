/**
 * سامانه کارکرد پرسنل بنی اسد
 * اسکریپت‌های کامل - تعاملی حرفه‌ای
 * Version: 1.0.0
 */

(function ($) {
    'use strict';

    /**
     * شیء اصلی Workforce
     */
    const Workforce = {
        // تنظیمات
        config: {
            ajaxUrl: workforceData?.ajax_url || ajaxurl,
            nonce: workforceData?.nonce || '',
            userId: workforceData?.user_id || 0,
            userRole: workforceData?.user_role || 'none',
            rtl: workforceData?.rtl || true,
            strings: workforceData?.strings || {},
        },

        // حالت‌های برنامه
        state: {
            currentPage: 1,
            pageSize: 50,
            totalPages: 1,
            totalRecords: 0,
            filters: {},
            sort: {},
            selectedRows: [],
            editingId: 0,
            currentPeriod: null,
            departments: [],
            mainFields: [],
            allFields: [],
        },

        // کش محلی
        cache: {
            personnel: {},
            departments: {},
            statistics: {},
            periods: {},
        },

        // زمان‌بندها
        timers: {
            autoSave: null,
            searchDebounce: null,
            refreshInterval: null,
        },

        /**
         * مقداردهی اولیه
         */
        init: function () {
            this.setupEventListeners();
            this.loadInitialData();
            this.setupKeyboardShortcuts();
            this.setupAutoSave();
            this.setupPeriodicRefresh();

            // نمایش پیام خوش‌آمد
            this.showWelcomeMessage();

            // لاگ راه‌اندازی
            console.log('Workforce System Initialized');
        },

        /**
         * تنظیم گوش‌کننده‌های رویداد
         */
        setupEventListeners: function () {
            // عمومی
            $(document).on('click', '[data-action]', this.handleAction.bind(this));

            // فرم‌ها
            $(document).on('submit', '.workforce-form', this.handleFormSubmit.bind(this));
            $(document).on('change', '.form-control', this.handleFormChange.bind(this));

            // جستجو
            $('#tableSearch').on('input', this.debounce(this.handleSearch, 300));

            // فیلترها
            $('.filter-select').on('change', this.handleFilterChange.bind(this));

            // مرتب‌سازی
            $(document).on('click', '[data-sort]', this.handleSort.bind(this));

            // انتخاب ردیف
            $(document).on('click', '.data-table tbody tr', this.handleRowClick.bind(this));

            // مدال‌ها
            $(document).on(
                'click',
                '.modal-close, .filter-close, .panel-close',
                this.closeModals.bind(this)
            );
            $(document).on(
                'click',
                '.workforce-modal, .confirmation-modal',
                this.handleOutsideClick.bind(this)
            );

            // درگ و دراپ
            this.setupDragAndDrop();

            // تغییر سایز
            $(window).on('resize', this.debounce(this.handleResize, 200));

            // قبل از بسته شدن صفحه
            $(window).on('beforeunload', this.handleBeforeUnload.bind(this));
        },

        /**
         * بارگذاری داده‌های اولیه
         */
        loadInitialData: function () {
            this.showLoading();

            // بارگذاری همزمان داده‌ها
            Promise.all([
                this.loadPeriods(),
                this.loadDepartments(),
                this.loadFields(),
                this.loadStatistics(),
            ])
                .then(() => {
                    this.loadTableData();
                    this.updateDashboard();
                    this.hideLoading();
                })
                .catch((error) => {
                    console.error('Error loading initial data:', error);
                    this.showError('خطا در بارگذاری داده‌های اولیه');
                    this.hideLoading();
                });
        },

        /**
         * بارگذاری دوره‌ها
         */
        loadPeriods: function () {
            return new Promise((resolve, reject) => {
                if (this.cache.periods.loaded) {
                    resolve(this.cache.periods.data);
                    return;
                }

                $.ajax({
                    url: this.config.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'workforce_ajax',
                        action_type: 'get_periods',
                        nonce: this.config.nonce,
                    },
                    success: (response) => {
                        if (response.success) {
                            this.cache.periods = {
                                loaded: true,
                                data: response.data,
                                timestamp: Date.now(),
                            };
                            this.state.periods = response.data;
                            this.populatePeriodSelect(response.data);
                            resolve(response.data);
                        } else {
                            reject(response.data);
                        }
                    },
                    error: (xhr, status, error) => {
                        reject(error);
                    },
                });
            });
        },

        /**
         * بارگذاری ادارات
         */
        loadDepartments: function () {
            return new Promise((resolve, reject) => {
                if (this.cache.departments.loaded) {
                    resolve(this.cache.departments.data);
                    return;
                }

                $.ajax({
                    url: this.config.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'workforce_ajax',
                        action_type: 'get_departments',
                        nonce: this.config.nonce,
                    },
                    success: (response) => {
                        if (response.success) {
                            this.cache.departments = {
                                loaded: true,
                                data: response.data,
                                timestamp: Date.now(),
                            };
                            this.state.departments = response.data;
                            this.populateDepartmentFilters(response.data);
                            resolve(response.data);
                        } else {
                            reject(response.data);
                        }
                    },
                    error: (xhr, status, error) => {
                        reject(error);
                    },
                });
            });
        },

        /**
         * بارگذاری فیلدها
         */
        loadFields: function () {
            return new Promise((resolve, reject) => {
                if (this.cache.fields.loaded) {
                    resolve(this.cache.fields.data);
                    return;
                }

                $.ajax({
                    url: this.config.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'workforce_ajax',
                        action_type: 'get_fields',
                        nonce: this.config.nonce,
                    },
                    success: (response) => {
                        if (response.success) {
                            this.cache.fields = {
                                loaded: true,
                                data: response.data,
                                timestamp: Date.now(),
                            };
                            this.state.allFields = response.data;
                            this.state.mainFields = response.data.filter((f) => f.is_main);
                            this.buildTableHeaders();
                            resolve(response.data);
                        } else {
                            reject(response.data);
                        }
                    },
                    error: (xhr, status, error) => {
                        reject(error);
                    },
                });
            });
        },

        /**
         * بارگذاری آمار
         */
        loadStatistics: function () {
            return new Promise((resolve, reject) => {
                const periodId = this.getCurrentPeriodId();

                $.ajax({
                    url: this.config.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'workforce_ajax',
                        action_type: 'get_statistics',
                        period_id: periodId,
                        nonce: this.config.nonce,
                    },
                    success: (response) => {
                        if (response.success) {
                            this.cache.statistics = {
                                data: response.data,
                                timestamp: Date.now(),
                            };
                            this.updateStatisticsCards(response.data);
                            resolve(response.data);
                        } else {
                            reject(response.data);
                        }
                    },
                    error: (xhr, status, error) => {
                        reject(error);
                    },
                });
            });
        },

        /**
         * بارگذاری داده‌های جدول
         */
        loadTableData: function () {
            const params = this.buildQueryParams();

            this.showTableLoading();

            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: params,
                success: (response) => {
                    if (response.success) {
                        this.handleTableData(response.data);
                    } else {
                        this.showTableError(response.data);
                    }
                },
                error: (xhr, status, error) => {
                    this.showTableError('خطا در ارتباط با سرور');
                },
            });
        },

        /**
         * ساخت پارامترهای کوئری
         */
        buildQueryParams: function () {
            const params = {
                action: 'workforce_ajax',
                action_type: 'get_personnel',
                page: this.state.currentPage,
                per_page: this.state.pageSize,
                period_id: this.getCurrentPeriodId(),
                nonce: this.config.nonce,
            };

            // اضافه کردن فیلترها
            if (Object.keys(this.state.filters).length > 0) {
                Object.assign(params, this.state.filters);
            }

            // اضافه کردن مرتب‌سازی
            if (this.state.sort.field) {
                params.sort_by = this.state.sort.field;
                params.sort_order = this.state.sort.direction;
            }

            return params;
        },

        /**
         * پردازش داده‌های جدول
         */
        handleTableData: function (data) {
            this.state.totalRecords = data.pagination.total;
            this.state.totalPages = data.pagination.total_pages;

            // ذخیره در کش
            const cacheKey = this.getCacheKey();
            this.cache.personnel[cacheKey] = {
                data: data.data,
                timestamp: Date.now(),
            };

            // رندر جدول
            this.renderTable(data.data);

            // به‌روزرسانی صفحه‌بندی
            this.updatePagination(data.pagination);

            // به‌روزرسانی آمار
            this.updateTableStats(data.pagination);

            // پنهان کردن لودینگ
            this.hideTableLoading();
        },

        /**
         * رندر جدول
         */
        renderTable: function (data) {
            const $tbody = $('#tableBody');
            $tbody.empty();

            if (data.length === 0) {
                $tbody.html(this.getEmptyTableHTML());
                return;
            }

            data.forEach((row, index) => {
                const $row = this.createTableRow(row, index);
                $tbody.append($row);
            });
        },

        /**
         * ایجاد ردیف جدول
         */
        createTableRow: function (row, index) {
            const rowNum = (this.state.currentPage - 1) * this.state.pageSize + index + 1;
            const isSelected = this.state.selectedRows.includes(row.id);
            const rowClass = isSelected ? 'selected' : '';

            let html = `
                <tr data-id="${row.id}" class="${rowClass}">
                    <td>${rowNum}</td>
                    <td><code>${row.national_code || ''}</code></td>
                    <td>${row.first_name || ''} ${row.last_name || ''}</td>
                    <td>${row.department_name || ''}</td>
            `;

            // فیلدهای اصلی
            this.state.mainFields.forEach((field) => {
                const value =
                    row.data && row.data[field.field_key]
                        ? this.formatFieldValue(row.data[field.field_key], field.field_type)
                        : '<span class="empty-value">—</span>';
                html += `<td>${value}</td>`;
            });

            // عملیات
            html += `
                <td>
                    <div class="row-actions">
                        <button class="btn-action" data-action="edit" data-id="${row.id}" title="ویرایش">
                            <svg width="16" height="16" viewBox="0 0 24 24">
                                <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                            </svg>
                        </button>
                        <button class="btn-action" data-action="view" data-id="${row.id}" title="مشاهده">
                            <svg width="16" height="16" viewBox="0 0 24 24">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                            </svg>
                        </button>
            `;

            if (!row.is_verified) {
                html += `
                        <button class="btn-action btn-success" data-action="verify" data-id="${row.id}" title="تأیید">
                            <svg width="16" height="16" viewBox="0 0 24 24">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                            </svg>
                        </button>
                `;
            }

            html += `
                    </div>
                </td>
            </tr>
            `;

            return $(html);
        },

        /**
         * فرمت‌دهی مقدار فیلد
         */
        formatFieldValue: function (value, type) {
            if (value === null || value === undefined || value === '') {
                return '<span class="empty-value">—</span>';
            }

            switch (type) {
                case 'number':
                case 'decimal':
                    const num = parseFloat(value);
                    return !isNaN(num) ? num.toLocaleString() : value;
                case 'date':
                    // تبدیل تاریخ شمسی
                    return this.convertToJalali(value);
                default:
                    return this.escapeHtml(value);
            }
        },

        /**
         * تبدیل به تاریخ شمسی
         */
        convertToJalali: function (dateString) {
            // پیاده‌سازی ساده - در عمل از کتابخانه jdf استفاده شود
            if (!dateString) return '';

            try {
                const date = new Date(dateString);
                const gregorianYear = date.getFullYear();
                const gregorianMonth = date.getMonth() + 1;
                const gregorianDay = date.getDate();

                // الگوریتم ساده تبدیل میلادی به شمسی
                const jd = this.gregorianToJulian(gregorianYear, gregorianMonth, gregorianDay);
                const jalali = this.julianToJalali(jd);

                return `${jalali.year}/${jalali.month.toString().padStart(2, '0')}/${jalali.day.toString().padStart(2, '0')}`;
            } catch (error) {
                return dateString;
            }
        },

        /**
         * تبدیل میلادی به جولیان
         */
        gregorianToJulian: function (year, month, day) {
            if (month <= 2) {
                year -= 1;
                month += 12;
            }
            const a = Math.floor(year / 100);
            const b = 2 - a + Math.floor(a / 4);
            return (
                Math.floor(365.25 * (year + 4716)) +
                Math.floor(30.6001 * (month + 1)) +
                day +
                b -
                1524.5
            );
        },

        /**
         * تبدیل جولیان به شمسی
         */
        julianToJalali: function (jd) {
            jd = Math.floor(jd) + 0.5;
            const depoch = jd - this.jalaliToJulian(475, 1, 1);
            const cycle = Math.floor(depoch / 1029983);
            const cyear = depoch % 1029983;
            let ycycle;

            if (cyear === 1029982) {
                ycycle = 2820;
            } else {
                const aux1 = Math.floor(cyear / 366);
                const aux2 = cyear % 366;
                ycycle = Math.floor((2134 * aux1 + 2816 * aux2 + 2815) / 1028522) + aux1 + 1;
            }

            const year = ycycle + 2820 * cycle + 474;
            const yday = jd - this.jalaliToJulian(year, 1, 1) + 1;
            let month = yday <= 186 ? Math.ceil(yday / 31) : Math.ceil((yday - 6) / 30);
            const day = jd - this.jalaliToJulian(year, month, 1) + 1;

            return { year, month, day };
        },

        /**
         * تبدیل شمسی به جولیان
         */
        jalaliToJulian: function (year, month, day) {
            const epbase = year - (year >= 0 ? 474 : 473);
            const epyear = 474 + (epbase % 2820);

            return (
                day +
                (month <= 7 ? (month - 1) * 31 : (month - 1) * 30 + 6) +
                Math.floor((epyear * 682 - 110) / 2816) +
                (epyear - 1) * 365 +
                Math.floor(epbase / 2820) * 1029983 +
                1948320.5
            );
        },

        /**
         * امن‌سازی HTML
         */
        escapeHtml: function (text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * به‌روزرسانی صفحه‌بندی
         */
        updatePagination: function (pagination) {
            const $pagination = $('#tablePagination');
            if (!$pagination.length) return;

            // به‌روزرسانی اطلاعات
            $('#startRow').text(pagination.start);
            $('#endRow').text(pagination.end);
            $('#totalRows').text(pagination.total.toLocaleString());

            // به‌روزرساری دکمه‌ها
            $('.page-btn').prop('disabled', false);

            if (pagination.current_page === 1) {
                $('.page-btn:eq(0), .page-btn:eq(1)').prop('disabled', true);
            }

            if (pagination.current_page === pagination.total_pages) {
                $('.page-btn:eq(3), .page-btn:eq(4)').prop('disabled', true);
            }

            // ساخت شماره صفحات
            this.buildPageNumbers(pagination.current_page, pagination.total_pages);
        },

        /**
         * ساخت شماره صفحات
         */
        buildPageNumbers: function (currentPage, totalPages) {
            const $pageNumbers = $('#pageNumbers');
            $pageNumbers.empty();

            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, startPage + 4);

            if (endPage - startPage < 4) {
                startPage = Math.max(1, endPage - 4);
            }

            for (let i = startPage; i <= endPage; i++) {
                const $btn = $(
                    `<button class="page-number ${i === currentPage ? 'active' : ''}">${i}</button>`
                );
                $btn.on('click', () => this.goToPage(i));
                $pageNumbers.append($btn);
            }

            if (endPage < totalPages) {
                $pageNumbers.append('<span class="page-dots">...</span>');
                const $lastBtn = $(`<button class="page-number">${totalPages}</button>`);
                $lastBtn.on('click', () => this.goToPage(totalPages));
                $pageNumbers.append($lastBtn);
            }
        },

        /**
         * رفتن به صفحه خاص
         */
        goToPage: function (page) {
            if (page < 1 || page > this.state.totalPages || page === this.state.currentPage) {
                return;
            }

            this.state.currentPage = page;
            this.loadTableData();
            this.scrollToTable();
        },

        /**
         * صفحه قبلی
         */
        prevPage: function () {
            if (this.state.currentPage > 1) {
                this.goToPage(this.state.currentPage - 1);
            }
        },

        /**
         * صفحه بعدی
         */
        nextPage: function () {
            if (this.state.currentPage < this.state.totalPages) {
                this.goToPage(this.state.currentPage + 1);
            }
        },

        /**
         * تغییر سایز صفحه
         */
        changePageSize: function () {
            const newSize = parseInt($('#pageSize').val());
            if (newSize !== this.state.pageSize) {
                this.state.pageSize = newSize;
                this.state.currentPage = 1;
                this.loadTableData();
            }
        },

        /**
         * به‌روزرسانی آمار جدول
         */
        updateTableStats: function (pagination) {
            $('#tableStats').html(`
                <span class="stat-item">${pagination.total.toLocaleString()} رکورد</span>
                <span class="stat-item">صفحه ${pagination.current_page} از ${pagination.total_pages}</span>
            `);
        },

        /**
         * به‌روزرسانی داشبورد
         */
        updateDashboard: function () {
            this.updateStatisticsCards(this.cache.statistics.data);
            this.updateDepartmentCards();
            this.updateDepartmentsStatus();
        },

        /**
         * به‌روزرسانی کارت‌های آمار
         */
        updateStatisticsCards: function (stats) {
            if (!stats) return;

            // تعداد پرسنل
            $('#totalPersonnel').text(stats.total_personnel?.toLocaleString() || '0');

            // فیلدهای اصلی
            const filledMain = stats.filled_main_fields || 0;
            const totalMain = stats.total_main_fields || 0;
            const mainPercent = totalMain > 0 ? Math.round((filledMain / totalMain) * 100) : 0;

            $('#filledMainFields').text(filledMain);
            $('#totalMainFields').text(totalMain);
            $('#mainFieldsProgress').css('width', `${mainPercent}%`);
            $('#mainFieldsPercent').text(`${mainPercent}% تکمیل`);
        },

        /**
         * به‌روزرسانی کارت‌های ادارات
         */
        updateDepartmentCards: function () {
            if (this.config.userRole !== 'admin' && this.config.userRole !== 'org_manager') {
                return;
            }

            const periodId = this.getCurrentPeriodId();

            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'workforce_ajax',
                    action_type: 'get_department_cards',
                    period_id: periodId,
                    nonce: this.config.nonce,
                },
                success: (response) => {
                    if (response.success) {
                        this.renderDepartmentCards(response.data);
                    }
                },
            });
        },

        /**
         * رندر کارت‌های ادارات
         */
        renderDepartmentCards: function (departments) {
            const $grid = $('#departmentCardsGrid');
            if (!$grid.length) return;

            $grid.empty();

            departments.forEach((dept) => {
                const percent =
                    dept.total_personnel > 0
                        ? Math.round(
                              (dept.filled_main_fields /
                                  (dept.total_main_fields * dept.total_personnel)) *
                                  100
                          )
                        : 0;

                const statusClass =
                    percent >= 90 ? 'status-good' : percent >= 70 ? 'status-warning' : 'status-bad';

                const card = `
                    <div class="card department-card">
                        <div class="card-header">
                            <h4>${this.escapeHtml(dept.department_name)}</h4>
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
                            <button class="btn-small" onclick="Workforce.viewDepartment(${dept.id})">مشاهده</button>
                            <button class="btn-small btn-primary" onclick="Workforce.editDepartmentPersonnel(${dept.id})">ویرایش</button>
                        </div>
                    </div>
                `;

                $grid.append(card);
            });
        },

        /**
         * به‌روزرسانی وضعیت ادارات
         */
        updateDepartmentsStatus: function () {
            const periodId = this.getCurrentPeriodId();

            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'workforce_ajax',
                    action_type: 'get_departments_status',
                    period_id: periodId,
                    nonce: this.config.nonce,
                },
                success: (response) => {
                    if (response.success) {
                        this.renderDepartmentsStatus(response.data);
                    }
                },
            });
        },

        /**
         * رندر وضعیت ادارات
         */
        renderDepartmentsStatus: function (departments) {
            const $container = $('#departmentsStatus');
            if (!$container.length) return;

            $container.empty();

            departments.forEach((dept) => {
                const percent =
                    dept.total_personnel > 0
                        ? Math.round(
                              (dept.filled_main_fields /
                                  (dept.total_main_fields * dept.total_personnel)) *
                                  100
                          )
                        : 0;

                const statusIcon = percent >= 90 ? '✅' : percent >= 70 ? '⚠️' : '❌';

                const item = `
                    <div class="department-status-item">
                        <div class="dept-status-name">
                            <span class="status-icon">${statusIcon}</span>
                            ${this.escapeHtml(dept.department_name)}
                        </div>
                        <div class="dept-status-progress">
                            <div class="progress-bar small">
                                <div class="progress-fill" style="width: ${percent}%"></div>
                            </div>
                            <span class="progress-text">${percent}%</span>
                        </div>
                    </div>
                `;

                $container.append(item);
            });
        },

        /**
         * ساخت هدرهای جدول
         */
        buildTableHeaders: function () {
            const $header = $('#tableHeader');
            if (!$header.length) return;

            $header.empty();

            // هدرهای ثابت
            const headers = [
                { title: 'ردیف', width: '50', sortable: false },
                { title: 'کد ملی', width: '120', sortable: true, field: 'national_code' },
                { title: 'نام و نام خانوادگی', width: '200', sortable: true, field: 'full_name' },
                { title: 'اداره', width: '150', sortable: true, field: 'department_name' },
            ];

            // هدرهای فیلدهای اصلی
            this.state.mainFields.forEach((field) => {
                headers.push({
                    title: field.field_name,
                    width: '150',
                    sortable: true,
                    field: field.field_key,
                    is_main: true,
                    field_type: field.field_type,
                });
            });

            // هدر عملیات
            headers.push({ title: 'عملیات', width: '100', sortable: false });

            // ساخت هدرهای HTML
            headers.forEach((header, index) => {
                const $th = $(`<th style="width: ${header.width}px"></th>`);

                let content = header.title;

                if (header.sortable) {
                    const sortIcon =
                        this.state.sort.field === header.field
                            ? this.state.sort.direction === 'asc'
                                ? '↑'
                                : '↓'
                            : '';

                    content = `
                        <div class="header-content">
                            <span>${header.title} ${sortIcon}</span>
                            <div class="header-actions">
                                <button class="header-btn" data-sort="${header.field}" title="مرتب‌سازی">
                                    <svg width="14" height="14" viewBox="0 0 24 24">
                                        <path d="M3 18h6v-2H3v2zM3 6v2h18V6H3zm0 7h12v-2H3v2z"/>
                                    </svg>
                                </button>
                                <button class="header-btn" data-action="filter-column" data-index="${index}" title="فیلتر">
                                    <svg width="14" height="14" viewBox="0 0 24 24">
                                        <path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z"/>
                                    </svg>
                                </button>
                    `;

                    if (header.is_main) {
                        content += `
                                <button class="header-btn" data-action="column-summary" data-index="${index}" title="خلاصه">
                                    <svg width="14" height="14" viewBox="0 0 24 24">
                                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                                    </svg>
                                </button>
                        `;
                    }

                    content += `</div></div>`;
                }

                $th.html(content);
                $header.append($th);
            });
        },

        /**
         * پر کردن انتخاب دوره
         */
        populatePeriodSelect: function (periods) {
            const $select = $('#periodSelect');
            if (!$select.length) return;

            $select.empty();

            periods.forEach((period) => {
                const locked = period.is_locked ? ' 🔒' : '';
                const $option = $(
                    `<option value="${period.id}">${this.escapeHtml(period.period_name)}${locked}</option>`
                );

                if (period.id === this.getCurrentPeriodId()) {
                    $option.prop('selected', true);
                    this.state.currentPeriod = period;
                }

                $select.append($option);
            });

            $select.on('change', () => {
                const periodId = $select.val();
                this.state.currentPeriod = periods.find((p) => p.id == periodId);
                this.state.currentPage = 1;
                this.loadTableData();
                this.loadStatistics();
                this.updateDashboard();
            });
        },

        /**
         * پر کردن فیلترهای اداره
         */
        populateDepartmentFilters: function (departments) {
            const $filter = $('#filterDepartment');
            if (!$filter.length) return;

            $filter.empty();

            departments.forEach((dept) => {
                $filter.append(
                    `<option value="${dept.id}">${this.escapeHtml(dept.department_name)}</option>`
                );
            });
        },

        /**
         * دریافت ID دوره جاری
         */
        getCurrentPeriodId: function () {
            if (this.state.currentPeriod) {
                return this.state.currentPeriod.id;
            }

            const $select = $('#periodSelect');
            if ($select.length) {
                return $select.val();
            }

            return 0;
        },

        /**
         * ایجاد کلید کش
         */
        getCacheKey: function () {
            const params = {
                page: this.state.currentPage,
                pageSize: this.state.pageSize,
                periodId: this.getCurrentPeriodId(),
                filters: this.state.filters,
                sort: this.state.sort,
            };

            return JSON.stringify(params);
        },

        /**
         * هندلر اقدامات
         */
        handleAction: function (event) {
            event.preventDefault();
            event.stopPropagation();

            const $target = $(event.currentTarget);
            const action = $target.data('action');
            const id = $target.data('id') || 0;
            const index = $target.data('index') || 0;

            switch (action) {
                case 'edit':
                    this.editPersonnel(id);
                    break;

                case 'view':
                    this.viewPersonnel(id);
                    break;

                case 'delete':
                    this.deletePersonnel(id);
                    break;

                case 'verify':
                    this.verifyPersonnel(id);
                    break;

                case 'add':
                    this.addPersonnel();
                    break;

                case 'save':
                    this.savePersonnel();
                    break;

                case 'cancel':
                    this.closeEditPanel();
                    break;

                case 'refresh':
                    this.refreshData();
                    break;

                case 'export':
                    this.exportToExcel();
                    break;

                case 'filter':
                    this.toggleFilters();
                    break;

                case 'filter-column':
                    this.openColumnFilter(index);
                    break;

                case 'column-summary':
                    this.showColumnSummary(index);
                    break;

                case 'apply-filters':
                    this.applyFilters();
                    break;

                case 'clear-filters':
                    this.clearFilters();
                    break;

                case 'prev-record':
                    this.prevRecord();
                    break;

                case 'next-record':
                    this.nextRecord();
                    break;

                case 'sort':
                    this.handleSortAction($target.data('sort'));
                    break;

                default:
                    console.log('Unknown action:', action);
            }
        },

        /**
         * ویرایش پرسنل
         */
        editPersonnel: function (id) {
            this.showEditPanel(id, false);
        },

        /**
         * مشاهده پرسنل
         */
        viewPersonnel: function (id) {
            this.showEditPanel(id, true);
        },

        /**
         * نمایش پنل ویرایش
         */
        showEditPanel: function (id, readOnly = false) {
            if (id) {
                // بارگذاری اطلاعات پرسنل
                this.loadPersonnelData(id, readOnly);
            } else {
                // فرم خالی برای افزودن جدید
                this.openEditPanel(null, readOnly);
            }
        },

        /**
         * بارگذاری اطلاعات پرسنل
         */
        loadPersonnelData: function (id, readOnly) {
            this.showLoading();

            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'workforce_ajax',
                    action_type: 'get_personnel_details',
                    personnel_id: id,
                    nonce: this.config.nonce,
                },
                success: (response) => {
                    this.hideLoading();

                    if (response.success) {
                        this.openEditPanel(response.data, readOnly);
                    } else {
                        this.showError('خطا در بارگذاری اطلاعات');
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showError('خطا در ارتباط با سرور');
                },
            });
        },

        /**
         * باز کردن پنل ویرایش
         */
        openEditPanel: function (personnel, readOnly) {
            const $panel = $('#editPanel');
            const $title = $('#panelTitle');
            const $form = $('#formFields');
            const $deleteBtn = $('#deleteBtn');
            const $prevBtn = $('#prevBtn');
            const $nextBtn = $('#nextBtn');

            // تنظیم عنوان
            if (personnel) {
                $title.text(readOnly ? 'مشاهده اطلاعات پرسنل' : 'ویرایش اطلاعات پرسنل');
            } else {
                $title.text('افزودن پرسنل جدید');
            }

            // ساخت فرم
            $form.empty();

            // فیلدهای ثابت
            const fixedFields = [
                {
                    key: 'national_code',
                    name: 'کد ملی',
                    type: 'text',
                    required: true,
                    pattern: '\\d{10}',
                    maxlength: 10,
                },
                {
                    key: 'first_name',
                    name: 'نام',
                    type: 'text',
                    required: true,
                },
                {
                    key: 'last_name',
                    name: 'نام خانوادگی',
                    type: 'text',
                    required: true,
                },
            ];

            // اضافه کردن فیلدهای ثابت
            fixedFields.forEach((field) => {
                const value = personnel ? personnel[field.key] || '' : '';
                const $field = this.createFormField(field, value, readOnly);
                $form.append($field);
            });

            // فیلدهای پویا
            this.state.mainFields.forEach((field) => {
                const value =
                    personnel && personnel.data ? personnel.data[field.field_key] || '' : '';
                const fieldData = {
                    key: field.field_key,
                    name: field.field_name,
                    type: field.field_type,
                    required: field.is_required,
                    is_main: field.is_main,
                    dropdown_values: field.dropdown_values,
                };

                const $field = this.createFormField(fieldData, value, readOnly);
                $form.append($field);
            });

            // ذخیره ID
            $('#editPersonnelId').val(personnel ? personnel.id : 0);
            $('#editPersonnelPeriod').val(this.getCurrentPeriodId());

            // نمایش/عدم نمایش دکمه‌ها
            $deleteBtn.toggle(personnel && !readOnly);
            $prevBtn.toggle(!!personnel);
            $nextBtn.toggle(!!personnel);

            // ذخیره index جاری
            if (personnel) {
                const rows = $('#tableBody tr');
                this.state.editingId = personnel.id;

                // پیدا کردن index جاری
                let currentIndex = -1;
                rows.each(function (index) {
                    if ($(this).data('id') == personnel.id) {
                        currentIndex = index;
                        return false;
                    }
                });

                // فعال/غیرفعال کردن دکمه‌های ناوبری
                $prevBtn.prop('disabled', currentIndex <= 0);
                $nextBtn.prop('disabled', currentIndex >= rows.length - 1);
            }

            // باز کردن پنل
            $panel.addClass('open');
            this.scrollToTop();
        },

        /**
         * ایجاد فیلد فرم
         */
        createFormField: function (field, value, readOnly) {
            const $div = $('<div class="form-group"></div>');

            let inputHtml = '';
            const requiredAttr = field.required ? 'required' : '';
            const readonlyAttr = readOnly ? 'readonly' : '';
            const id = `field_${field.key}`;

            switch (field.type) {
                case 'dropdown':
                    inputHtml = `
                        <select id="${id}" name="${field.key}" ${requiredAttr} ${readonlyAttr} class="form-control">
                            <option value="">انتخاب کنید</option>
                    `;

                    if (field.dropdown_values && Array.isArray(field.dropdown_values)) {
                        field.dropdown_values.forEach((opt) => {
                            const selected = value == opt ? 'selected' : '';
                            inputHtml += `<option value="${this.escapeHtml(opt)}" ${selected}>${this.escapeHtml(opt)}</option>`;
                        });
                    }

                    inputHtml += `</select>`;
                    break;

                case 'textarea':
                    inputHtml = `
                        <textarea id="${id}" name="${field.key}" ${requiredAttr} ${readonlyAttr} 
                                  class="form-control" rows="3">${this.escapeHtml(value)}</textarea>
                    `;
                    break;

                default:
                    inputHtml = `
                        <input type="${field.type}" id="${id}" name="${field.key}" 
                               value="${this.escapeHtml(value)}" ${requiredAttr} ${readonlyAttr} 
                               class="form-control" ${field.pattern ? `pattern="${field.pattern}"` : ''}
                               ${field.maxlength ? `maxlength="${field.maxlength}"` : ''}>
                    `;
            }

            $div.html(`
                <label for="${id}">
                    ${field.name}
                    ${field.required ? '<span class="required">*</span>' : ''}
                    ${field.is_main ? '<span class="main-badge">اصلی</span>' : ''}
                </label>
                ${inputHtml}
            `);

            return $div;
        },

        /**
         * بستن پنل ویرایش
         */
        closeEditPanel: function () {
            $('#editPanel').removeClass('open');
            this.state.editingId = 0;
        },

        /**
         * ذخیره پرسنل
         */
        savePersonnel: function () {
            if (!this.validateForm()) {
                this.showError('لطفاً فیلدهای الزامی را پر کنید');
                return;
            }

            const personnelId = $('#editPersonnelId').val();
            const isNew = personnelId == '0';

            // جمع‌آوری داده‌ها
            const formData = this.collectFormData();

            this.showLoading('در حال ذخیره...');

            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'workforce_ajax',
                    action_type: isNew ? 'create_personnel' : 'update_personnel',
                    personnel_id: personnelId,
                    ...formData,
                    nonce: this.config.nonce,
                },
                success: (response) => {
                    this.hideLoading();

                    if (response.success) {
                        this.showSuccess('اطلاعات با موفقیت ذخیره شد');
                        this.closeEditPanel();
                        this.loadTableData();
                        this.loadStatistics();
                        this.updateDashboard();
                    } else {
                        this.showError(response.data || 'خطا در ذخیره اطلاعات');
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showError('خطا در ارتباط با سرور');
                },
            });
        },

        /**
         * اعتبارسنجی فرم
         */
        validateForm: function () {
            let isValid = true;

            $('#personnelForm .form-control[required]').each(function () {
                const $input = $(this);
                const value = $input.val().trim();

                if (!value) {
                    $input.addClass('error');
                    isValid = false;
                } else {
                    $input.removeClass('error');

                    // اعتبارسنجی الگو
                    const pattern = $input.attr('pattern');
                    if (pattern && !new RegExp(pattern).test(value)) {
                        $input.addClass('error');
                        isValid = false;
                    }
                }
            });

            return isValid;
        },

        /**
         * جمع‌آوری داده‌های فرم
         */
        collectFormData: function () {
            const data = {
                national_code: $('#field_national_code').val(),
                first_name: $('#field_first_name').val(),
                last_name: $('#field_last_name').val(),
                period_id: $('#editPersonnelPeriod').val(),
            };

            // فیلدهای پویا
            const dynamicData = {};
            this.state.mainFields.forEach((field) => {
                const value = $(`#field_${field.field_key}`).val();
                if (value !== undefined) {
                    dynamicData[field.field_key] = value;
                }
            });

            data.data = JSON.stringify(dynamicData);

            return data;
        },

        /**
         * حذف پرسنل
         */
        deletePersonnel: function () {
            const personnelId = $('#editPersonnelId').val();

            if (!personnelId || personnelId == '0') {
                return;
            }

            this.showConfirmation('آیا از حذف این پرسنل اطمینان دارید؟', () => {
                this.performDelete(personnelId);
            });
        },

        /**
         * اجرای حذف
         */
        performDelete: function (personnelId) {
            this.showLoading('در حال حذف...');

            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'workforce_ajax',
                    action_type: 'delete_personnel',
                    personnel_id: personnelId,
                    nonce: this.config.nonce,
                },
                success: (response) => {
                    this.hideLoading();

                    if (response.success) {
                        this.showSuccess('پرسنل با موفقیت حذف شد');
                        this.closeEditPanel();
                        this.loadTableData();
                        this.loadStatistics();
                    } else {
                        this.showError(response.data || 'خطا در حذف');
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showError('خطا در ارتباط با سرور');
                },
            });
        },

        /**
         * تأیید پرسنل
         */
        verifyPersonnel: function (id) {
            this.showConfirmation('آیا از تأیید این پرسنل اطمینان دارید؟', () => {
                this.performVerify(id);
            });
        },

        /**
         * اجرای تأیید
         */
        performVerify: function (id) {
            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'workforce_ajax',
                    action_type: 'verify_personnel',
                    personnel_id: id,
                    nonce: this.config.nonce,
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess('پرسنل با موفقیت تأیید شد');
                        this.loadTableData();
                    } else {
                        this.showError(response.data || 'خطا در تأیید');
                    }
                },
                error: () => {
                    this.showError('خطا در ارتباط با سرور');
                },
            });
        },

        /**
         * رکورد قبلی
         */
        prevRecord: function () {
            const $rows = $('#tableBody tr');
            let currentIndex = -1;

            $rows.each(function (index) {
                if ($(this).data('id') == this.state.editingId) {
                    currentIndex = index;
                    return false;
                }
            });

            if (currentIndex > 0) {
                const prevId = $rows.eq(currentIndex - 1).data('id');
                this.editPersonnel(prevId);
            }
        },

        /**
         * رکورد بعدی
         */
        nextRecord: function () {
            const $rows = $('#tableBody tr');
            let currentIndex = -1;

            $rows.each(function (index) {
                if ($(this).data('id') == this.state.editingId) {
                    currentIndex = index;
                    return false;
                }
            });

            if (currentIndex < $rows.length - 1) {
                const nextId = $rows.eq(currentIndex + 1).data('id');
                this.editPersonnel(nextId);
            }
        },

        /**
         * هندلر ارسال فرم
         */
        handleFormSubmit: function (event) {
            event.preventDefault();
            this.savePersonnel();
        },

        /**
         * هندلر تغییر فرم
         */
        handleFormChange: function (event) {
            $(event.currentTarget).removeClass('error');
        },

        /**
         * هندلر جستجو
         */
        handleSearch: function (event) {
            const searchTerm = $('#tableSearch').val().trim();

            if (searchTerm) {
                this.state.filters.search = searchTerm;
            } else {
                delete this.state.filters.search;
            }

            this.state.currentPage = 1;
            this.loadTableData();
        },

        /**
         * هندلر تغییر فیلتر
         */
        handleFilterChange: function (event) {
            // این متد توسط applyFilters فراخوانی می‌شود
        },

        /**
         * اعمال فیلترها
         */
        applyFilters: function () {
            const filters = {};

            // فیلتر اداره
            const deptValues = $('#filterDepartment').val();
            if (deptValues && deptValues.length > 0) {
                filters.department_id = deptValues;
            }

            // فیلتر وضعیت
            const statusValue = $('#filterStatus').val();
            if (statusValue) {
                filters.status = statusValue;
            }

            // فیلتر تأیید
            const verifiedValue = $('#filterVerified').val();
            if (verifiedValue !== '') {
                filters.is_verified = verifiedValue;
            }

            this.state.filters = filters;
            this.state.currentPage = 1;
            this.loadTableData();

            // بستن فیلترها
            this.toggleFilters();
        },

        /**
         * پاک کردن فیلترها
         */
        clearFilters: function () {
            $('#filterDepartment').val('');
            $('#filterStatus').val('');
            $('#filterVerified').val('');

            this.state.filters = {};
            this.state.currentPage = 1;
            this.loadTableData();

            this.toggleFilters();
        },

        /**
         * نمایش/پنهان فیلترها
         */
        toggleFilters: function () {
            $('#tableFilters').slideToggle();
        },

        /**
         * هندلر مرتب‌سازی
         */
        handleSort: function (event) {
            const $target = $(event.currentTarget);
            const field = $target.data('sort');

            if (!field) return;

            if (this.state.sort.field === field) {
                // تغییر جهت
                this.state.sort.direction = this.state.sort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                // مرتب‌سازی جدید
                this.state.sort = { field, direction: 'asc' };
            }

            this.state.currentPage = 1;
            this.loadTableData();
        },

        /**
         * هندلر کلیک روی ردیف
         */
        handleRowClick: function (event) {
            // جلوگیری از کلیک روی دکمه‌های عملیات
            if ($(event.target).closest('.row-actions').length) {
                return;
            }

            const $row = $(event.currentTarget);
            const id = $row.data('id');

            if (id) {
                this.editPersonnel(id);
            }
        },

        /**
         * بستن مدال‌ها
         */
        closeModals: function () {
            $('.workforce-modal, .confirmation-modal, .edit-panel').hide();
            $('.workforce-modal, .confirmation-modal').removeClass('show');
            $('.edit-panel').removeClass('open');
        },

        /**
         * هندلر کلیک خارج
         */
        handleOutsideClick: function (event) {
            if (event.target === event.currentTarget) {
                this.closeModals();
            }
        },

        /**
         * تنظیم درگ و دراپ
         */
        setupDragAndDrop: function () {
            // پیاده‌سازی درگ و دراپ برای آینده
        },

        /**
         * هندلر تغییر سایز
         */
        handleResize: function () {
            // به‌روزرسانی layout در صورت نیاز
        },

        /**
         * هندلر قبل از بسته شدن
         */
        handleBeforeUnload: function (event) {
            // بررسی ذخیره نشده‌ها
            if (this.hasUnsavedChanges()) {
                event.preventDefault();
                event.returnValue =
                    'تغییرات ذخیره نشده‌ای دارید. آیا مطمئن هستید که می‌خواهید صفحه را ترک کنید؟';
                return event.returnValue;
            }
        },

        /**
         * بررسی تغییرات ذخیره نشده
         */
        hasUnsavedChanges: function () {
            // پیاده‌سازی بررسی تغییرات
            return false;
        },

        /**
         * تنظیم میانبرهای صفحه‌کلید
         */
        setupKeyboardShortcuts: function () {
            $(document).on('keydown', this.handleKeyboardShortcut.bind(this));
        },

        /**
         * هندلر میانبرهای صفحه‌کلید
         */
        handleKeyboardShortcut: function (event) {
            // فقط وقتی که focus روی input نباشد
            if ($(event.target).is('input, textarea, select')) {
                return;
            }

            // Ctrl + S: ذخیره
            if (event.ctrlKey && event.key === 's') {
                event.preventDefault();
                if ($('#editPanel').hasClass('open')) {
                    this.savePersonnel();
                }
            }

            // Ctrl + F: جستجو
            if (event.ctrlKey && event.key === 'f') {
                event.preventDefault();
                $('#tableSearch').focus();
            }

            // Ctrl + → : بعدی
            if (event.ctrlKey && event.key === 'ArrowRight') {
                event.preventDefault();
                if ($('#editPanel').hasClass('open')) {
                    this.nextRecord();
                } else {
                    this.nextPage();
                }
            }

            // Ctrl + ← : قبلی
            if (event.ctrlKey && event.key === 'ArrowLeft') {
                event.preventDefault();
                if ($('#editPanel').hasClass('open')) {
                    this.prevRecord();
                } else {
                    this.prevPage();
                }
            }

            // Ctrl + N: جدید
            if (event.ctrlKey && event.key === 'n') {
                event.preventDefault();
                this.addPersonnel();
            }

            // Ctrl + E: خروجی
            if (event.ctrlKey && event.key === 'e') {
                event.preventDefault();
                this.exportToExcel();
            }

            // Esc: بستن
            if (event.key === 'Escape') {
                this.closeModals();
            }
        },

        /**
         * تنظیم ذخیره خودکار
         */
        setupAutoSave: function () {
            // ذخیره خودکار هر 30 ثانیه
            this.timers.autoSave = setInterval(() => {
                if (this.hasUnsavedChanges()) {
                    this.autoSave();
                }
            }, 30000);
        },

        /**
         * ذخیره خودکار
         */
        autoSave: function () {
            // پیاده‌سازی ذخیره خودکار
        },

        /**
         * تنظیم رفرش دوره‌ای
         */
        setupPeriodicRefresh: function () {
            // رفرش هر 5 دقیقه
            this.timers.refreshInterval = setInterval(() => {
                this.refreshData();
            }, 300000);
        },

        /**
         * رفرش داده‌ها
         */
        refreshData: function () {
            this.loadTableData();
            this.loadStatistics();
        },

        /**
         * افزودن پرسنل جدید
         */
        addPersonnel: function () {
            this.showEditPanel(null, false);
        },

        /**
         * مشاهده اداره
         */
        viewDepartment: function (deptId) {
            this.state.filters.department_id = [deptId];
            this.state.currentPage = 1;
            this.loadTableData();
        },

        /**
         * ویرایش پرسنل اداره
         */
        editDepartmentPersonnel: function (deptId) {
            this.state.filters.department_id = [deptId];
            this.state.currentPage = 1;
            this.loadTableData();
        },

        /**
         * باز کردن فیلتر ستونی
         */
        openColumnFilter: function (columnIndex) {
            // پیاده‌سازی فیلتر ستونی
        },

        /**
         * نمایش خلاصه ستون
         */
        showColumnSummary: function (columnIndex) {
            // پیاده‌سازی خلاصه ستون
        },

        /**
         * خروجی Excel
         */
        exportToExcel: function () {
            const params = this.buildQueryParams();
            params.action_type = 'export_excel';
            params.all_pages = true;

            this.showLoading('در حال ایجاد فایل Excel...');

            // ایجاد لینک دانلود
            const queryString = new URLSearchParams(params).toString();
            const url = `${this.config.ajaxUrl}?${queryString}`;

            // ایجاد لینک مخفی برای دانلود
            const $link = $('<a>', {
                href: url,
                download: `کارکرد_پرسنل_${this.state.currentPeriod?.period_name || 'گزارش'}.xlsx`,
                style: 'display: none;',
            });

            $('body').append($link);
            $link[0].click();
            $link.remove();

            this.hideLoading();
            this.showSuccess('فایل Excel در حال دانلود است');
        },

        /**
         * نمایش پیام خوش‌آمد
         */
        showWelcomeMessage: function () {
            const userName = workforceData?.user?.name || 'کاربر';
            const periodName = this.state.currentPeriod?.period_name || 'دوره جاری';

            this.showNotification(
                `
                <strong>سلام ${userName} عزیز!</strong><br>
                به سامانه کارکرد پرسنل بنی اسد خوش آمدید.<br>
                دوره فعال: <strong>${periodName}</strong>
            `,
                'info',
                5000
            );
        },

        /**
         * نمایش نوتیفیکیشن
         */
        showNotification: function (message, type = 'info', duration = 3000) {
            const $notification = $(`
                <div class="notification notification-${type}">
                    <div class="notification-content">${message}</div>
                    <button class="notification-close">
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                        </svg>
                    </button>
                </div>
            `);

            $('body').append($notification);

            // نمایش با انیمیشن
            setTimeout(() => {
                $notification.addClass('show');
            }, 10);

            // بستن با کلیک
            $notification.find('.notification-close').on('click', () => {
                $notification.removeClass('show');
                setTimeout(() => $notification.remove(), 300);
            });

            // حذف خودکار
            if (duration > 0) {
                setTimeout(() => {
                    $notification.removeClass('show');
                    setTimeout(() => $notification.remove(), 300);
                }, duration);
            }
        },

        /**
         * نمایش موفقیت
         */
        showSuccess: function (message) {
            this.showNotification(message, 'success');
        },

        /**
         * نمایش خطا
         */
        showError: function (message) {
            this.showNotification(message, 'error');
        },

        /**
         * نمایش هشدار
         */
        showWarning: function (message) {
            this.showNotification(message, 'warning');
        },

        /**
         * نمایش تایید
         */
        showConfirmation: function (message, confirmCallback) {
            const $modal = $('#confirmationModal');
            const $message = $('#modalMessage');
            const $confirmBtn = $('#modalConfirmBtn');
            const $icon = $('#modalIcon');

            $message.text(message);
            $icon.html('❓');

            // حذف رویدادهای قبلی
            $confirmBtn.off('click');

            // رویداد جدید
            $confirmBtn.on('click', () => {
                confirmCallback();
                $modal.hide();
            });

            $modal.show();
        },

        /**
         * نمایش لودینگ
         */
        showLoading: function (message = 'در حال بارگذاری...') {
            let $loading = $('#workforceLoading');

            if (!$loading.length) {
                $loading = $(`
                    <div id="workforceLoading" class="workforce-loading">
                        <div class="workforce-loading-spinner"></div>
                        <div class="workforce-loading-text">${message}</div>
                    </div>
                `);
                $('body').append($loading);
            } else {
                $loading.find('.workforce-loading-text').text(message);
            }
        },

        /**
         * پنهان کردن لودینگ
         */
        hideLoading: function () {
            $('#workforceLoading').remove();
        },

        /**
         * نمایش لودینگ جدول
         */
        showTableLoading: function () {
            const $tbody = $('#tableBody');
            $tbody.html(`
                <tr>
                    <td colspan="${this.state.mainFields.length + 5}" class="loading-cell">
                        <div class="loading-spinner"></div>
                        در حال بارگذاری اطلاعات...
                    </td>
                </tr>
            `);
        },

        /**
         * پنهان کردن لودینگ جدول
         */
        hideTableLoading: function () {
            // پنهان‌سازی خودکار انجام می‌شود
        },

        /**
         * نمایش خطای جدول
         */
        showTableError: function (message) {
            const $tbody = $('#tableBody');
            $tbody.html(`
                <tr>
                    <td colspan="${this.state.mainFields.length + 5}" class="empty-cell">
                        <svg width="48" height="48" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                        </svg>
                        <p>${message}</p>
                    </td>
                </tr>
            `);
        },

        /**
         * HTML جدول خالی
         */
        getEmptyTableHTML: function () {
            return `
                <tr>
                    <td colspan="${this.state.mainFields.length + 5}" class="empty-cell">
                        <svg width="48" height="48" viewBox="0 0 24 24">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/>
                        </svg>
                        <p>داده‌ای برای نمایش وجود ندارد</p>
                    </td>
                </tr>
            `;
        },

        /**
         * اسکرول به جدول
         */
        scrollToTable: function () {
            const $table = $('.main-table-section');
            if ($table.length) {
                $('html, body').animate(
                    {
                        scrollTop: $table.offset().top - 100,
                    },
                    300
                );
            }
        },

        /**
         * اسکرول به بالا
         */
        scrollToTop: function () {
            $('html, body').animate({ scrollTop: 0 }, 300);
        },

        /**
         * تابع debounce
         */
        debounce: function (func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * تابع throttle
         */
        throttle: function (func, limit) {
            let inThrottle;
            return function () {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => (inThrottle = false), limit);
                }
            };
        },

        /**
         * تنظیمات local storage
         */
        setLocalStorage: function (key, value) {
            try {
                localStorage.setItem(`workforce_${key}`, JSON.stringify(value));
            } catch (e) {
                console.warn('LocalStorage is not available:', e);
            }
        },

        /**
         * دریافت از local storage
         */
        getLocalStorage: function (key, defaultValue = null) {
            try {
                const value = localStorage.getItem(`workforce_${key}`);
                return value ? JSON.parse(value) : defaultValue;
            } catch (e) {
                console.warn('LocalStorage is not available:', e);
                return defaultValue;
            }
        },

        /**
         * پاک کردن local storage
         */
        removeLocalStorage: function (key) {
            try {
                localStorage.removeItem(`workforce_${key}`);
            } catch (e) {
                console.warn('LocalStorage is not available:', e);
            }
        },

        /**
         * مدیریت خطاها
         */
        handleError: function (error, context = '') {
            console.error(`Workforce Error [${context}]:`, error);

            let message = 'خطایی رخ داده است';

            if (error.responseJSON && error.responseJSON.data) {
                message = error.responseJSON.data;
            } else if (error.statusText) {
                message = `خطای شبکه: ${error.statusText}`;
            } else if (error.message) {
                message = error.message;
            }

            this.showError(message);

            // لاگ کردن به سرور
            this.logError(error, context);
        },

        /**
         * لاگ خطا به سرور
         */
        logError: function (error, context) {
            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'workforce_ajax',
                    action_type: 'log_error',
                    error: error.toString(),
                    context: context,
                    url: window.location.href,
                    user_id: this.config.userId,
                    nonce: this.config.nonce,
                },
            }).fail(() => {
                // اگر لاگینگ خطا هم خطا داد، کاری نکن
            });
        },

        /**
         * بررسی وضعیت آنلاین
         */
        checkOnlineStatus: function () {
            if (!navigator.onLine) {
                this.showWarning(
                    'اتصال اینترنت برقرار نیست. برخی ویژگی‌ها ممکن است در دسترس نباشند.'
                );
            }
        },

        /**
         * به‌روزرسانی زمان واقعی
         */
        updateRealtime: function () {
            // پیاده‌سازی به‌روزرسانی زمان واقعی با WebSocket یا Polling
        },

        /**
         * پشتیبانی از touch
         */
        setupTouchSupport: function () {
            if ('ontouchstart' in window) {
                // بهینه‌سازی‌های تاچ
                $('.btn-action, .btn-icon, .page-number').css('min-height', '44px');
                $('.form-control').css('font-size', '16px'); // جلوگیری از زوم در iOS
            }
        },

        /**
         * مدیریت کش مرورگر
         */
        setupCacheControl: function () {
            // اضافه کردن پارامتر version به URLها برای جلوگیری از کش قدیمی
            const version = '1.0.0';
            $.ajaxSetup({
                cache: false,
                data: {
                    _v: version,
                },
            });
        },

        /**
         * پرفورمنس مانیتورینگ
         */
        setupPerformanceMonitoring: function () {
            if ('performance' in window) {
                const perfEntries = performance.getEntriesByType('navigation');
                if (perfEntries.length > 0) {
                    const navTiming = perfEntries[0];

                    // لاگ زمان‌های لودینگ
                    const data = {
                        dns: navTiming.domainLookupEnd - navTiming.domainLookupStart,
                        tcp: navTiming.connectEnd - navTiming.connectStart,
                        request: navTiming.responseStart - navTiming.requestStart,
                        response: navTiming.responseEnd - navTiming.responseStart,
                        dom:
                            navTiming.domContentLoadedEventEnd -
                            navTiming.domContentLoadedEventStart,
                        load: navTiming.loadEventEnd - navTiming.loadEventStart,
                    };

                    // ارسال به سرور برای آنالیز
                    $.ajax({
                        url: this.config.ajaxUrl,
                        method: 'POST',
                        data: {
                            action: 'workforce_ajax',
                            action_type: 'log_performance',
                            data: data,
                            nonce: this.config.nonce,
                        },
                    });
                }
            }
        },

        /**
         * توسعه‌دهنده‌ها
         */
        setupDeveloperTools: function () {
            // کلیدهای میانبر توسعه
            $(document).on('keydown', (event) => {
                if (event.ctrlKey && event.shiftKey && event.key === 'D') {
                    event.preventDefault();
                    this.toggleDeveloperMode();
                }

                if (event.ctrlKey && event.shiftKey && event.key === 'L') {
                    event.preventDefault();
                    this.clearAllCache();
                }
            });
        },

        /**
         * تغییر حالت توسعه‌دهنده
         */
        toggleDeveloperMode: function () {
            const isDev = this.getLocalStorage('dev_mode', false);
            this.setLocalStorage('dev_mode', !isDev);

            if (!isDev) {
                console.log(
                    '%c🔧 حالت توسعه‌دهنده فعال شد',
                    'color: #3b82f6; font-size: 14px; font-weight: bold;'
                );
                console.log('Workforce State:', this.state);
                console.log('Workforce Config:', this.config);
                console.log('Workforce Cache:', this.cache);
            } else {
                console.log(
                    '%c🔧 حالت توسعه‌دهنده غیرفعال شد',
                    'color: #ef4444; font-size: 14px; font-weight: bold;'
                );
            }

            this.showNotification(
                `حالت توسعه‌دهنده ${!isDev ? 'فعال' : 'غیرفعال'} شد`,
                !isDev ? 'info' : 'warning'
            );
        },

        /**
         * پاک کردن تمام کش
         */
        clearAllCache: function () {
            this.cache = {
                personnel: {},
                departments: {},
                statistics: {},
                periods: {},
                fields: {},
            };

            try {
                for (let i = 0; i < localStorage.length; i++) {
                    const key = localStorage.key(i);
                    if (key.startsWith('workforce_')) {
                        localStorage.removeItem(key);
                    }
                }
            } catch (e) {
                console.warn('Cannot clear localStorage:', e);
            }

            this.showSuccess('کش سیستم پاک شد');
            this.loadInitialData();
        },

        /**
         * گزارش استفاده
         */
        trackUsage: function (action, data = {}) {
            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'workforce_ajax',
                    action_type: 'track_usage',
                    user_action: action,
                    user_data: data,
                    user_id: this.config.userId,
                    nonce: this.config.nonce,
                },
            });
        },

        /**
         * راهنمای سیستم
         */
        showHelp: function () {
            const helpContent = `
                <h3>📚 راهنمای استفاده از سامانه</h3>
                <div class="help-sections">
                    <div class="help-section">
                        <h4>🏠 داشبورد</h4>
                        <p>نمای کلی از آمار و وضعیت سیستم</p>
                    </div>
                    <div class="help-section">
                        <h4>📊 جدول اطلاعات</h4>
                        <p>مدیریت اطلاعات پرسنل با قابلیت‌های:</p>
                        <ul>
                            <li>🔍 جستجوی پیشرفته</li>
                            <li>🎯 فیلتر ستونی</li>
                            <li>📈 مرتب‌سازی</li>
                            <li>📥 خروجی Excel</li>
                        </ul>
                    </div>
                    <div class="help-section">
                        <h4>⌨️ میانبرهای صفحه‌کلید</h4>
                        <ul>
                            <li><kbd>Ctrl + S</kbd> ذخیره</li>
                            <li><kbd>Ctrl + F</kbd> جستجو</li>
                            <li><kbd>Ctrl + N</kbd> افزودن جدید</li>
                            <li><kbd>Ctrl + E</kbd> خروجی Excel</li>
                            <li><kbd>Ctrl + →</kbd> بعدی</li>
                            <li><kbd>Ctrl + ←</kbd> قبلی</li>
                            <li><kbd>Esc</kbd> بستن پنجره‌ها</li>
                        </ul>
                    </div>
                </div>
            `;

            this.showNotification(helpContent, 'info', 10000);
        },

        /**
         * درباره سیستم
         */
        showAbout: function () {
            const aboutContent = `
                <h3>🧩 سامانه کارکرد پرسنل بنی اسد</h3>
                <p><strong>نسخه:</strong> 1.0.0</p>
                <p><strong>توسعه‌دهنده:</strong> تیم فنی بنی اسد</p>
                <p><strong>پشتیبانی:</strong> support@beniasad.ir</p>
                <hr>
                <p>سیستم مدیریت اطلاعات پرسنلی سازمانی با آخرین تکنولوژی‌های وب</p>
            `;

            this.showNotification(aboutContent, 'info', 8000);
        },
    };

    /**
     * مقداردهی اولیه زمانی که DOM آماده است
     */
    $(document).ready(function () {
        // بررسی وجود المنت‌های ضروری
        if ($('#workforceDashboard').length || $('.workforce-admin-wrap').length) {
            Workforce.init();
        }
    });

    /**
     * در دسترس قرار دادن Workforce در محیط global
     */
    window.Workforce = Workforce;
})(jQuery);
