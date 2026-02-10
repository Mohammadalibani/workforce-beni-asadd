/**
 * اسکریپت‌های اصلی پلاگین مدیریت کارکرد پرسنل - بنی اسد
 * نسخه: 1.0.0
 */

jQuery(document).ready(function ($) {
    // تنظیمات عمومی
    const workforce = {
        // ثابت‌ها
        CONSTANTS: {
            ENTER_KEY: 13,
            ESCAPE_KEY: 27,
            ARROW_LEFT: 37,
            ARROW_RIGHT: 39,
            CTRL_KEY: 17,
            CMD_KEY: 91,
        },

        // وضعیت‌ها
        state: {
            isDragging: false,
            dragStartX: 0,
            dragStartY: 0,
            selectedColumn: null,
            keyboardShortcuts: {},
            activeModals: [],
            notifications: [],
        },

        // داده‌های کش شده
        cache: {
            tableData: null,
            filters: {},
            searchTerm: '',
            selectedRows: new Set(),
        },

        // تنظیمات
        config: {
            apiUrl: workforce_ajax.ajax_url,
            nonce: workforce_ajax.nonce,
            userId: workforce_ajax.current_user_id,
            pluginUrl: workforce_ajax.plugin_url,
            recordsPerPage: 25,
            currentPage: 1,
            totalPages: 1,
            totalRecords: 0,
        },

        // مدیریت وضعیت
        status: {
            loading: false,
            saving: false,
            exporting: false,
            filtering: false,
        },
    };

    /**
     * مقداردهی اولیه
     */
    function init() {
        setupEventListeners();
        setupKeyboardShortcuts();
        setupDragAndDrop();
        setupDatePickers();
        setupTooltips();

        // بارگذاری اولیه داده‌ها اگر در پنل مدیر هستیم
        if ($('.workforce-manager-panel').length || $('.workforce-org-manager-panel').length) {
            loadInitialData();
        }

        // تنظیمات ریسپانسیو
        setupResponsive();

        console.log('پلاگین مدیریت کارکرد پرسنل بارگذاری شد.');
    }

    /**
     * تنظیم event listeners
     */
    function setupEventListeners() {
        // کلیک خارج از مودال‌ها
        $(document).on('click', function (e) {
            if ($(e.target).hasClass('workforce-modal')) {
                hideModal($(e.target).attr('id'));
            }
        });

        // کلید Escape برای بستن مودال‌ها
        $(document).on('keydown', function (e) {
            if (e.keyCode === workforce.CONSTANTS.ESCAPE_KEY) {
                if (workforce.state.activeModals.length > 0) {
                    hideModal(
                        workforce.state.activeModals[workforce.state.activeModals.length - 1]
                    );
                }
            }
        });

        // تغییر اندازه پنجره
        $(window).on('resize', debounce(handleResize, 250));

        // پیشگیری از ارسال فرم با Enter
        $('form').on('keydown', function (e) {
            if (
                e.keyCode === workforce.CONSTANTS.ENTER_KEY &&
                $(e.target).is('input:not([type="submit"])')
            ) {
                e.preventDefault();
            }
        });

        // کلیک روی دکمه‌های اکشن
        $(document).on('click', '.action-btn', function (e) {
            e.stopPropagation();
        });

        // انتخاب ردیف با کلیک
        $(document).on('click', '.workforce-data-table tbody tr', function (e) {
            if (!$(e.target).is('input, button, .action-btn, .action-btn *')) {
                const rowId = $(this).data('personnel-id');
                if (rowId) {
                    toggleRowSelection(rowId, this);
                }
            }
        });

        // دابل کلیک برای ویرایش
        $(document).on('dblclick', '.workforce-data-table tbody tr', function (e) {
            if (!$(e.target).is('input, button, .action-btn, .action-btn *')) {
                const rowId = $(this).data('personnel-id');
                if (rowId) {
                    editPersonnel(rowId);
                }
            }
        });
    }

    /**
     * تنظیم کلیدهای میانبر
     */
    function setupKeyboardShortcuts() {
        let ctrlPressed = false;

        $(document).on('keydown', function (e) {
            // تشخیص Ctrl/Cmd
            if (
                e.keyCode === workforce.CONSTANTS.CTRL_KEY ||
                e.keyCode === workforce.CONSTANTS.CMD_KEY
            ) {
                ctrlPressed = true;
            }

            // Ctrl + F: جستجو
            if (ctrlPressed && e.keyCode === 70) {
                // F
                e.preventDefault();
                $('#globalSearch, #orgGlobalSearch').first().focus();
            }

            // Ctrl + S: ذخیره
            if (ctrlPressed && e.keyCode === 83) {
                // S
                e.preventDefault();
                if ($('#sideForm').hasClass('active')) {
                    savePersonnelForm();
                }
            }

            // Ctrl + A: انتخاب همه
            if (ctrlPressed && e.keyCode === 65) {
                // A
                e.preventDefault();
                const $selectAll = $('#selectAll');
                $selectAll.prop('checked', !$selectAll.prop('checked'));
                $selectAll.trigger('change');
            }

            // Ctrl + E: خروجی اکسل
            if (ctrlPressed && e.keyCode === 69) {
                // E
                e.preventDefault();
                if ($('.workforce-org-manager-panel').length) {
                    exportOrgToExcel();
                } else {
                    exportToExcel();
                }
            }

            // Ctrl + R: رفرش
            if (ctrlPressed && e.keyCode === 82) {
                // R
                e.preventDefault();
                refreshData();
            }

            // فلش‌های چپ و راست برای ناوبری
            if ($('#sideForm').hasClass('active')) {
                if (e.keyCode === workforce.CONSTANTS.ARROW_LEFT) {
                    e.preventDefault();
                    navigatePersonnel('prev');
                } else if (e.keyCode === workforce.CONSTANTS.ARROW_RIGHT) {
                    e.preventDefault();
                    navigatePersonnel('next');
                }
            }
        });

        $(document).on('keyup', function (e) {
            if (
                e.keyCode === workforce.CONSTANTS.CTRL_KEY ||
                e.keyCode === workforce.CONSTANTS.CMD_KEY
            ) {
                ctrlPressed = false;
            }
        });
    }

    /**
     * تنظیم درگ و دراپ
     */
    function setupDragAndDrop() {
        const $table = $('#personnelTable');
        if (!$table.length) return;

        // ستون‌های قابل درگ
        $table.find('thead th').each(function () {
            const $th = $(this);
            if (
                !$th.hasClass('checkbox-col') &&
                !$th.hasClass('row-number') &&
                !$th.hasClass('actions-col')
            ) {
                $th.attr('draggable', 'true');

                $th.on('dragstart', function (e) {
                    workforce.state.isDragging = true;
                    workforce.state.dragStartX = e.clientX;
                    workforce.state.dragStartY = e.clientY;
                    workforce.state.selectedColumn = $th.index();

                    $th.addClass('dragging');
                    e.originalEvent.dataTransfer.setData('text/plain', $th.index());
                    e.originalEvent.dataTransfer.effectAllowed = 'move';
                });

                $th.on('dragend', function () {
                    workforce.state.isDragging = false;
                    $th.removeClass('dragging');
                    $table.find('th, td').removeClass('drop-zone');
                });
            }
        });

        // درگ اوور
        $table.on('dragover', 'th, td', function (e) {
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'move';

            if (!workforce.state.isDragging) return;

            const $target = $(this).closest('th');
            if ($target.length && !$target.hasClass('dragging')) {
                $table.find('th, td').removeClass('drop-zone');
                $target.addClass('drop-zone');
            }
        });

        // دراپ
        $table.on('drop', 'th, td', function (e) {
            e.preventDefault();

            if (!workforce.state.isDragging) return;

            const fromIndex = parseInt(e.originalEvent.dataTransfer.getData('text/plain'));
            const $target = $(this).closest('th');
            const toIndex = $target.index();

            if (fromIndex !== toIndex && toIndex >= 2) {
                // از ستون‌های ۲ به بعد (بعد از checkbox و row-number)
                reorderColumns(fromIndex, toIndex);
            }

            $table.find('th, td').removeClass('drop-zone');
        });
    }

    /**
     * تغییر ترتیب ستون‌ها
     */
    function reorderColumns(fromIndex, toIndex) {
        const $table = $('#personnelTable');
        const $tbody = $table.find('tbody');
        const $headerRow = $table.find('thead tr');

        // ذخیره ترتیب فعلی
        const columnOrder = [];
        $headerRow.find('th').each(function (index) {
            columnOrder.push({
                element: $(this),
                index: index,
            });
        });

        // جابجایی در آرایه
        const movedColumn = columnOrder.splice(fromIndex, 1)[0];
        columnOrder.splice(toIndex, 0, movedColumn);

        // به‌روزرسانی هدر
        $headerRow.empty();
        columnOrder.forEach((col) => {
            $headerRow.append(col.element);
        });

        // به‌روزرسانی داده‌ها
        $tbody.find('tr').each(function () {
            const $row = $(this);
            const cells = [];

            $row.find('td').each(function (index) {
                cells.push({
                    element: $(this),
                    index: index,
                });
            });

            // جابجایی مشابه در سلول‌ها
            const movedCell = cells.splice(fromIndex - 2, 1)[0]; // منهای ۲ برای checkbox و row-number
            cells.splice(toIndex - 2, 0, movedCell);

            $row.empty();
            cells.forEach((cell) => {
                $row.append(cell.element);
            });
        });

        // ذخیره ترتیب جدید در localStorage
        saveColumnOrder(columnOrder);

        showNotification('ترتیب ستون‌ها تغییر کرد', 'success');
    }

    /**
     * ذخیره ترتیب ستون‌ها
     */
    function saveColumnOrder(columnOrder) {
        try {
            const order = columnOrder.map(
                (col) => col.element.data('field-id') || col.element.data('field-name')
            );
            localStorage.setItem('workforce_column_order', JSON.stringify(order));
        } catch (e) {
            console.error('خطا در ذخیره ترتیب ستون‌ها:', e);
        }
    }

    /**
     * بارگذاری ترتیب ستون‌ها
     */
    function loadColumnOrder() {
        try {
            const savedOrder = localStorage.getItem('workforce_column_order');
            if (savedOrder) {
                return JSON.parse(savedOrder);
            }
        } catch (e) {
            console.error('خطا در بارگذاری ترتیب ستون‌ها:', e);
        }
        return null;
    }

    /**
     * تنظیم datepicker فارسی
     */
/**
 * تنظیم datepicker فارسی
 */
/**
 * تنظیم datepicker فارسی
 */
/**
 * تنظیم datepicker فارسی (محلی)
 */
/**
 * تنظیم datepicker فارسی
 */
function setupDatePickers() {
    // بررسی اینکه آیا Persian Datepicker بارگذاری شده است
    setTimeout(function () {
        if (
            typeof $.fn.persianDatepicker !== 'undefined' &&
            workforce_ajax.has_datepicker === 'yes'
        ) {
            console.log('Persian Datepicker بارگذاری شد.');

            $('.jdatepicker').persianDatepicker({
                format: 'YYYY/MM/DD',
                observer: true,
                persianDigit: false,
                autoClose: true,
                initialValue: false,
            });
        } else {
            console.warn('Persian Datepicker پیدا نشد! از جایگزین استفاده می‌شود.');

            // جایگزین ساده
            $('.jdatepicker').each(function () {
                const $input = $(this);

                // اضافه کردن placeholder و pattern
                $input.attr({
                    placeholder: '۱۴۰۳/۰۱/۰۱',
                    pattern: '^[۰-۹]{4}/[۰-۹]{2}/[۰-۹]{2}$',
                    title: 'فرمت: ۱۴۰۳/۰۱/۰۱',
                    autocomplete: 'off',
                });

                // گروه input
                const $group = $('<div class="date-input-group"></div>');
                $input.wrap($group);

                // hint
                $input.after('<div class="date-hint">مثال: ۱۴۰۳/۰۱/۰۱</div>');

                // اعتبارسنجی
                $input.on('blur', function () {
                    validateJalaliDate(this);
                });

                // auto-format
                $input.on('input', function (e) {
                    autoFormatJalaliDate(this, e);
                });
            });
        }
    }, 100); // تاخیر برای اطمینان از بارگذاری کامل
}

/**
 * auto-format تاریخ شمسی هنگام تایپ
 */
function autoFormatJalaliDate(input, e) {
    let value = $(input).val();

    // حذف همه غیراعداد
    value = value.replace(/[^۰-۹]/g, '');

    // اضافه کردن اسلش
    if (value.length > 4) {
        value = value.substring(0, 4) + '/' + value.substring(4);
    }
    if (value.length > 7) {
        value = value.substring(0, 7) + '/' + value.substring(7);
    }

    // محدود کردن طول
    if (value.length > 10) {
        value = value.substring(0, 10);
    }

    $(input).val(value);
}

// در document ready اصلی
jQuery(document).ready(function ($) {
    console.log('Persian Date Status:', {
        hasPersianDate: workforce_ajax.has_persian_date,
        hasDatepicker: workforce_ajax.has_datepicker,
    });

    // ... بقیه کدها ...
    setupDatePickers();
});

/**
 * اعتبارسنجی تاریخ شمسی
 */
function validateJalaliDate(input) {
    const $input = $(input);
    const value = $input.val();
    const regex = /^[۰-۹]{4}\/[۰-۹]{2}\/[۰-۹]{2}$/;
    
    // حذف پیام خطای قبلی
    $input.next('.date-error-message').remove();
    
    if (value && !regex.test(value)) {
        $input.addClass('date-error');
        $input.after('<span class="date-error-message" style="color: #e74c3c; font-size: 0.85em; display: block; margin-top: 5px;">فرمت تاریخ صحیح نیست (مثال: ۱۴۰۳/۰۱/۰۱)</span>');
        return false;
    }
    
    // اعتبارسنجی ماه و روز
    if (regex.test(value)) {
        const parts = value.split('/');
        const year = parseInt(parts[0]);
        const month = parseInt(parts[1]);
        const day = parseInt(parts[2]);
        
        if (month < 1 || month > 12 || day < 1 || day > 31) {
            $input.addClass('date-error');
            $input.after('<span class="date-error-message" style="color: #e74c3c; font-size: 0.85em; display: block; margin-top: 5px;">ماه باید ۱-۱۲ و روز باید ۱-۳۱ باشد</span>');
            return false;
        }
        
        // ماه‌های ۳۱ روزه
        if (month <= 6 && day > 31) {
            $input.addClass('date-error');
            $input.after('<span class="date-error-message" style="color: #e74c3c; font-size: 0.85em; display: block; margin-top: 5px;">روز برای این ماه معتبر نیست</span>');
            return false;
        }
        
        // ماه‌های ۳۰ روزه
        if (month >= 7 && month <= 11 && day > 30) {
            $input.addClass('date-error');
            $input.after('<span class="date-error-message" style="color: #e74c3c; font-size: 0.85em; display: block; margin-top: 5px;">روز برای این ماه معتبر نیست</span>');
            return false;
        }
        
        // اسفند
        if (month === 12) {
            if (!isLeapYear(year) && day > 29) {
                $input.addClass('date-error');
                $input.after('<span class="date-error-message" style="color: #e74c3c; font-size: 0.85em; display: block; margin-top: 5px;">سال کبیسه نیست، روز باید ۱-۲۹ باشد</span>');
                return false;
            }
            if (isLeapYear(year) && day > 30) {
                $input.addClass('date-error');
                $input.after('<span class="date-error-message" style="color: #e74c3c; font-size: 0.85em; display: block; margin-top: 5px;">روز برای این ماه معتبر نیست</span>');
                return false;
            }
        }
    }
    
    $input.removeClass('date-error');
    return true;
}

/**
 * بررسی سال کبیسه شمسی
 */
function isLeapYear(year) {
    const a = year % 33;
    return [1, 5, 9, 13, 17, 22, 26, 30].includes(a);
}

/**
 * اعتبارسنجی تاریخ شمسی
 */
function validateJalaliDate(input) {
    const value = $(input).val();
    const regex = /^[۰-۹]{4}\/[۰-۹]{2}\/[۰-۹]{2}$/;
    
    if (value && !regex.test(value)) {
        $(input).addClass('date-error');
        $(input).after('<span class="error-message">فرمت تاریخ صحیح نیست (مثال: ۱۴۰۳/۰۱/۰۱)</span>');
        return false;
    }
    
    $(input).removeClass('date-error');
    $(input).next('.error-message').remove();
    return true;
}

// در document ready اصلی
jQuery(document).ready(function($) {
    // ... کدهای دیگر ...
    
    setupDatePickers();
    
    // ... بقیه کدها ...
});

    /**
     * تنظیم tooltip‌ها
     */
    function setupTooltips() {
        // استفاده از title attribute برای tooltip
        $(document).on('mouseenter', '[title]', function () {
            const $el = $(this);
            const title = $el.attr('title');

            if (title && title.trim()) {
                $el.attr('data-original-title', title).removeAttr('title');

                const tooltip = $('<div class="workforce-tooltip-content"></div>')
                    .text(title)
                    .css({
                        position: 'absolute',
                        background: 'rgba(0, 0, 0, 0.8)',
                        color: 'white',
                        padding: '8px 12px',
                        borderRadius: '6px',
                        fontSize: '0.9em',
                        zIndex: '10000',
                        pointerEvents: 'none',
                        maxWidth: '300px',
                        whiteSpace: 'normal',
                        wordWrap: 'break-word',
                    })
                    .appendTo('body');

                const pos = $el.offset();
                tooltip.css({
                    top: pos.top - tooltip.outerHeight() - 10,
                    left: pos.left + ($el.outerWidth() - tooltip.outerWidth()) / 2,
                });

                $el.data('tooltip', tooltip);
            }
        });

        $(document).on('mouseleave', '[data-original-title]', function () {
            const $el = $(this);
            const tooltip = $el.data('tooltip');

            if (tooltip) {
                tooltip.remove();
                $el.removeData('tooltip');
                $el.attr('title', $el.attr('data-original-title')).removeAttr(
                    'data-original-title'
                );
            }
        });
    }

    /**
     * بارگذاری اولیه داده‌ها
     */
    function loadInitialData() {
        if (workforce.status.loading) return;

        workforce.status.loading = true;
        showLoading();

        const panelType = $('.workforce-org-manager-panel').length ? 'org' : 'dept';
        const endpoint =
            panelType === 'org' ? 'workforce_get_org_table_data' : 'workforce_get_table_data';

        const params = {
            action: endpoint,
            page: workforce.config.currentPage,
            per_page: workforce.config.recordsPerPage,
            nonce: workforce.config.nonce,
        };

        // اضافه کردن پارامترهای خاص
        if (panelType === 'dept') {
            const deptId = $('.workforce-manager-panel').data('dept-id');
            const periodId = $('.workforce-manager-panel').data('period-id');

            if (deptId) params.department_id = deptId;
            if (periodId) params.period_id = periodId;

            // اضافه کردن فیلترها
            if (Object.keys(workforce.cache.filters).length > 0) {
                params.filters = workforce.cache.filters;
            }

            // اضافه کردن جستجو
            if (workforce.cache.searchTerm) {
                params.search = workforce.cache.searchTerm;
            }
        }

        $.ajax({
            url: workforce.config.apiUrl,
            type: 'POST',
            data: params,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    if (panelType === 'org') {
                        renderOrgTable(response.data);
                    } else {
                        renderTable(response.data);
                        updateMonitoringCards();
                    }

                    updatePagination(response.data.pagination);
                    updateRecordCounter(response.data.pagination);

                    // ذخیره در کش
                    workforce.cache.tableData = response.data;
                } else {
                    showNotification(
                        'خطا در بارگذاری داده‌ها: ' + (response.data?.message || 'خطای ناشناخته'),
                        'error'
                    );
                }
            },
            error: function (xhr, status, error) {
                showNotification('خطا در ارتباط با سرور', 'error');
                console.error('خطای AJAX:', error);
            },
            complete: function () {
                workforce.status.loading = false;
                hideLoading();
            },
        });
    }

    /**
     * رندر جدول
     */
    function renderTable(data) {
        const $tbody = $('#tableBody');
        $tbody.empty();

        if (!data.rows || data.rows.length === 0) {
            $tbody.html('<tr><td colspan="100" class="no-data">داده‌ای یافت نشد.</td></tr>');
            return;
        }

        // گرفتن فیلدها از عناصر th
        const fields = [];
        $('#personnelTable thead th[data-field-id]').each(function () {
            fields.push({
                id: $(this).data('field-id'),
                name: $(this).data('field-name'),
                isRequired: $(this).hasClass('required-col'),
                isLocked: $(this).hasClass('locked-col'),
            });
        });

        // ایجاد ردیف‌ها
        data.rows.forEach((row, index) => {
            const $tr = $('<tr>').attr('data-personnel-id', row.id);

            if (row.is_deleted) {
                $tr.addClass('deleted-row');
            }

            // ستون انتخاب
            const $tdCheckbox = $('<td>')
                .addClass('checkbox-col')
                .html(`<input type="checkbox" class="row-checkbox" data-row-id="${row.id}">`);

            // ستون شماره ردیف
            const rowNumber =
                (workforce.config.currentPage - 1) * workforce.config.recordsPerPage + index + 1;
            const $tdNumber = $('<td>').addClass('row-number').text(rowNumber);

            $tr.append($tdCheckbox, $tdNumber);

            // ستون‌های داده
            fields.forEach((field) => {
                const value = row.meta?.[field.id] || row.meta?.[field.name] || '';
                const $td = $('<td>').text(value);

                if (field.isLocked) {
                    $td.addClass('locked-cell');
                }

                if (field.isRequired && !value) {
                    $td.addClass('required-empty');
                }

                $tr.append($td);
            });

            // ستون عملیات
            const $tdActions = $('<td>').addClass('actions-col').html(`
                <button type="button" class="action-btn edit-btn" title="ویرایش">
                    ✏️
                </button>
                <button type="button" class="action-btn view-btn" title="مشاهده">
                    👁️
                </button>
                <button type="button" class="action-btn delete-btn" title="حذف">
                    🗑️
                </button>
            `);

            $tr.append($tdActions);
            $tbody.append($tr);

            // افزودن event handlers برای دکمه‌های عملیات
            $tr.find('.edit-btn').on('click', function (e) {
                e.stopPropagation();
                editPersonnel(row.id);
            });

            $tr.find('.view-btn').on('click', function (e) {
                e.stopPropagation();
                viewPersonnel(row.id);
            });

            $tr.find('.delete-btn').on('click', function (e) {
                e.stopPropagation();
                requestDeletePersonnel(row.id);
            });
        });

        // به‌روزرسانی انتخاب‌ها
        updateRowSelections();
    }

    /**
     * رندر جدول سازمانی
     */
    function renderOrgTable(data) {
        const $tbody = $('#orgTableBody');
        $tbody.empty();

        if (!data.rows || data.rows.length === 0) {
            $tbody.html('<tr><td colspan="100" class="no-data">داده‌ای یافت نشد.</td></tr>');
            return;
        }

        data.rows.forEach((row, index) => {
            const rowNumber =
                (workforce.config.currentPage - 1) * workforce.config.recordsPerPage + index + 1;

            const $tr = $('<tr>');

            // شماره ردیف
            $tr.append($('<td>').addClass('row-number').text(rowNumber));

            // نام اداره
            $tr.append(
                $('<td>').addClass('dept-col').html(`
                <span class="dept-badge" style="background-color: ${row.department_color || '#3498db'}">
                    ${row.department_name}
                </span>
            `)
            );

            // اطلاعات پایه
            $tr.append($('<td>').text(row.national_code || ''));
            $tr.append($('<td>').html(`<strong>${row.first_name} ${row.last_name}</strong>`));
            $tr.append($('<td>').text(row.employment_date || ''));
            $tr.append($('<td>').text(getEmploymentTypeLabel(row.employment_type)));
            $tr.append($('<td>').html(getStatusBadge(row.status)));

            // عملیات
            const $tdActions = $('<td>').addClass('actions-col').html(`
                <button type="button" class="action-btn view-btn" title="مشاهده">
                    👁️
                </button>
                <button type="button" class="action-btn chart-btn" title="نمودار">
                    📈
                </button>
            `);

            $tr.append($tdActions);
            $tbody.append($tr);

            // event handlers
            $tr.find('.view-btn').on('click', function () {
                viewOrgPersonnel(row.id);
            });

            $tr.find('.chart-btn').on('click', function () {
                showPersonnelChart(row.id);
            });
        });
    }
/**
 * نمایش جزئیات اداره
 */
function showDeptDetails(deptId) {
    $.ajax({
        url: workforce.config.apiUrl,
        type: 'POST',
        data: {
            action: 'workforce_get_dept_details',
            department_id: deptId,
            nonce: workforce.config.nonce
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // نمایش در مودال
                alert('جزئیات اداره - این بخش نیاز به پیاده‌سازی دارد.');
            }
        }
    });
}
    /**
     * به‌روزرسانی کارت‌های مانیتورینگ
     */
    function updateMonitoringCards() {
        const deptId = $('.workforce-manager-panel').data('dept-id');
        if (!deptId) return;

        // آمار کلی
        $.ajax({
            url: workforce.config.apiUrl,
            type: 'POST',
            data: {
                action: 'workforce_get_department_stats',
                department_id: deptId,
                nonce: workforce.config.nonce,
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    const stats = response.data;

                    $('#personnelCount').text(stats.total_personnel);
                    $('#requiredFieldsPercent').text(stats.completion_rate + '%');
                    $('#warningCount').text(stats.incomplete_count);

                    // نوار پیشرفت
                    const $progressBar = $('#requiredFieldsProgress');
                    $progressBar.css('width', stats.completion_rate + '%');

                    // رنگ بر اساس درصد
                    let color = '#e74c3c'; // قرمز برای کمتر از ۵۰٪
                    if (stats.completion_rate >= 80) {
                        color = '#2ecc71'; // سبز برای ۸۰٪ به بالا
                    } else if (stats.completion_rate >= 50) {
                        color = '#f39c12'; // نارنجی برای ۵۰-۷۹٪
                    }

                    $progressBar.css('background-color', color);
                }
            },
        });

        // به‌روزرسانی کارت‌های داینامیک
        $('.monitoring-card.card-dynamic').each(function () {
            const $card = $(this);
            const fieldId = $card.attr('id').replace('monitoringCard_', '');

            if (fieldId && !isNaN(fieldId)) {
                updateMonitoringCardValue(parseInt(fieldId));
            }
        });
    }

    /**
     * به‌روزرسانی مقدار یک کارت مانیتورینگ
     */
    function updateMonitoringCardValue(fieldId) {
        const deptId = $('.workforce-manager-panel').data('dept-id');
        const periodId = $('.workforce-manager-panel').data('period-id');

        if (!deptId) return;

        $.ajax({
            url: workforce.config.apiUrl,
            type: 'POST',
            data: {
                action: 'workforce_get_field_stats',
                field_id: fieldId,
                department_id: deptId,
                period_id: periodId,
                nonce: workforce.config.nonce,
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    const $valueElement = $('#cardValue_' + fieldId);
                    if ($valueElement.length) {
                        $valueElement.text(response.data.total);
                    }
                }
            },
        });
    }

    /**
     * ایجاد کارت مانیتورینگ جدید
     */
    function createMonitoringCard(fieldId, fieldLabel) {
        // بررسی محدودیت
        const existingCards = $('.monitoring-card.card-dynamic').length;
        if (existingCards >= 6) {
            showNotification('حداکثر ۶ کارت مانیتورینگ می‌توانید داشته باشید', 'warning');
            return;
        }

        // بررسی تکراری نبودن
        if ($('#monitoringCard_' + fieldId).length) {
            showNotification('کارت مانیتورینگ برای این فیلد قبلا ایجاد شده است', 'warning');
            return;
        }

        const $cardsContainer = $('#monitoringCards');
        const $card = $(`
            <div class="monitoring-card card-dynamic" id="monitoringCard_${fieldId}">
                <div class="card-icon">📊</div>
                <div class="card-content">
                    <h3>${fieldLabel}</h3>
                    <p class="card-number" id="cardValue_${fieldId}">0</p>
                    <p class="card-sub">مجموع</p>
                </div>
                <button type="button" class="card-close">✕</button>
            </div>
        `);

        $cardsContainer.append($card);

        // event handler برای بستن
        $card.find('.card-close').on('click', function () {
            removeMonitoringCard(fieldId);
        });

        // به‌روزرسانی مقدار
        updateMonitoringCardValue(fieldId);

        showNotification('کارت مانیتورینگ ایجاد شد', 'success');
    }

    /**
     * حذف کارت مانیتورینگ
     */
    function removeMonitoringCard(fieldId) {
        $('#monitoringCard_' + fieldId).remove();
        showNotification('کارت مانیتورینگ حذف شد', 'info');
    }

    /**
     * به‌روزرسانی صفحه‌بندی
     */
    function updatePagination(pagination) {
        workforce.config.totalRecords = pagination.total_records;
        workforce.config.totalPages = pagination.total_pages;

        // به‌روزرسانی دکمه‌ها
        const $firstBtn = $('#firstPage');
        const $prevBtn = $('#prevPage');
        const $nextBtn = $('#nextPage');
        const $lastBtn = $('#lastPage');

        $firstBtn.prop('disabled', workforce.config.currentPage === 1);
        $prevBtn.prop('disabled', workforce.config.currentPage === 1);
        $nextBtn.prop('disabled', workforce.config.currentPage === workforce.config.totalPages);
        $lastBtn.prop('disabled', workforce.config.currentPage === workforce.config.totalPages);

        // ایجاد شماره صفحات
        const $pageNumbers = $('#pageNumbers');
        $pageNumbers.empty();

        let startPage = Math.max(1, workforce.config.currentPage - 2);
        let endPage = Math.min(workforce.config.totalPages, startPage + 4);

        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }

        for (let i = startPage; i <= endPage; i++) {
            const $btn = $(`<button type="button" class="page-number-btn">${i}</button>`);

            if (i === workforce.config.currentPage) {
                $btn.addClass('active');
            }

            $btn.on('click', function () {
                goToPage(i);
            });

            $pageNumbers.append($btn);
        }
    }

    /**
     * به‌روزرسانی شمارنده رکوردها
     */
    function updateRecordCounter(pagination) {
        const start = (workforce.config.currentPage - 1) * workforce.config.recordsPerPage + 1;
        const end = Math.min(
            workforce.config.currentPage * workforce.config.recordsPerPage,
            pagination.total_records
        );

        $('#recordCounter').text(`نمایش ${start}-${end} از ${pagination.total_records} رکورد`);
    }

    /**
     * رفتن به صفحه خاص
     */
    function goToPage(page) {
        if (page >= 1 && page <= workforce.config.totalPages) {
            workforce.config.currentPage = page;
            loadInitialData();
            scrollToTableTop();
        }
    }

    /**
     * صفحه قبلی
     */
    function goToPreviousPage() {
        if (workforce.config.currentPage > 1) {
            goToPage(workforce.config.currentPage - 1);
        }
    }

    /**
     * صفحه بعدی
     */
    function goToNextPage() {
        if (workforce.config.currentPage < workforce.config.totalPages) {
            goToPage(workforce.config.currentPage + 1);
        }
    }

    /**
     * اولین صفحه
     */
    function goToFirstPage() {
        goToPage(1);
    }

    /**
     * آخرین صفحه
     */
    function goToLastPage() {
        goToPage(workforce.config.totalPages);
    }

    /**
     * تغییر تعداد رکورد در صفحه
     */
    function changeRecordsPerPage(value) {
        if (value === 'all') {
            workforce.config.recordsPerPage = 999999;
        } else {
            workforce.config.recordsPerPage = parseInt(value);
        }

        workforce.config.currentPage = 1;
        loadInitialData();
    }

    /**
     * جستجوی سراسری
     */
    function performGlobalSearch(query) {
        workforce.cache.searchTerm = query;
        workforce.config.currentPage = 1;
        loadInitialData();
    }

    /**
     * پاک کردن همه فیلترها
     */
    function clearAllFilters() {
        workforce.cache.filters = {};
        workforce.cache.searchTerm = '';
        workforce.config.currentPage = 1;

        $('#globalSearch').val('');
        loadInitialData();

        showNotification('همه فیلترها پاک شدند', 'success');
    }

    /**
     * نمایش فیلتر ستونی
     */
    function showColumnFilter(fieldId) {
        const $th = $(`th[data-field-id="${fieldId}"]`);
        const fieldLabel = $th.find('.column-title').text();

        $('#filterModalTitle').text(`فیلتر: ${fieldLabel}`);

        const deptId = $('.workforce-manager-panel').data('dept-id');
        const periodId = $('.workforce-manager-panel').data('period-id');

        if (!deptId) return;

        $.ajax({
            url: workforce.config.apiUrl,
            type: 'POST',
            data: {
                action: 'workforce_get_unique_values',
                field_id: fieldId,
                department_id: deptId,
                period_id: periodId,
                nonce: workforce.config.nonce,
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    const values = response.data.values;
                    const currentFilter = workforce.cache.filters[fieldId] || [];

                    let html = '<div class="filter-content">';
                    html += '<div class="filter-values">';

                    values.forEach((value) => {
                        const checked = currentFilter.includes(value) ? ' checked' : '';
                        html += `
                            <label class="filter-checkbox">
                                <input type="checkbox" value="${value}"${checked}>
                                <span>${value || '(خالی)'}</span>
                            </label>
                        `;
                    });

                    html += '</div>';
                    html += '<div class="filter-actions">';
                    html +=
                        '<button type="button" class="button button-primary" onclick="applyColumnFilter(' +
                        fieldId +
                        ')">اعمال فیلتر</button>';
                    html +=
                        '<button type="button" class="button" onclick="clearColumnFilter(' +
                        fieldId +
                        ')">پاک کردن</button>';
                    html += '</div>';
                    html += '</div>';

                    $('#filterContent').html(html);

                    // event handler برای چک‌باکس‌ها
                    $('#filterContent input[type="checkbox"]').on('change', function () {
                        updateColumnFilter(fieldId, this);
                    });

                    showModal('columnFilterModal');
                }
            },
        });
    }

    /**
     * به‌روزرسانی فیلتر ستونی
     */
    function updateColumnFilter(fieldId, checkbox) {
        if (!workforce.cache.filters[fieldId]) {
            workforce.cache.filters[fieldId] = [];
        }

        const value = $(checkbox).val();
        const index = workforce.cache.filters[fieldId].indexOf(value);

        if ($(checkbox).is(':checked') && index === -1) {
            workforce.cache.filters[fieldId].push(value);
        } else if (!$(checkbox).is(':checked') && index > -1) {
            workforce.cache.filters[fieldId].splice(index, 1);
        }
    }

    /**
     * اعمال فیلتر ستونی
     */
    function applyColumnFilter(fieldId) {
        workforce.config.currentPage = 1;
        loadInitialData();
        hideModal('columnFilterModal');
    }

    /**
     * پاک کردن فیلتر ستونی
     */
    function clearColumnFilter(fieldId) {
        delete workforce.cache.filters[fieldId];
        workforce.config.currentPage = 1;
        loadInitialData();
        hideModal('columnFilterModal');
    }

    /**
     * انتخاب/عدم انتخاب همه ردیف‌ها
     */
    function toggleSelectAll(checkbox) {
        const isChecked = $(checkbox).is(':checked');
        $('.row-checkbox').prop('checked', isChecked).trigger('change');
    }

    /**
     * انتخاب/عدم انتخاب ردیف
     */
    function toggleRowSelection(rowId, element) {
        const $checkbox = $(element).is('input') ? $(element) : $(element).find('.row-checkbox');
        const isChecked = $checkbox.is(':checked');

        if (isChecked) {
            workforce.cache.selectedRows.add(rowId);
            $checkbox.closest('tr').addClass('selected');
        } else {
            workforce.cache.selectedRows.delete(rowId);
            $checkbox.closest('tr').removeClass('selected');
        }
    }

    /**
     * به‌روزرسانی انتخاب ردیف‌ها
     */
    function updateRowSelections() {
        $('.row-checkbox').each(function () {
            const rowId = $(this).data('row-id');
            if (workforce.cache.selectedRows.has(rowId)) {
                $(this).prop('checked', true).closest('tr').addClass('selected');
            }
        });
    }

    /**
     * ویرایش پرسنل
     */
    function editPersonnel(personnelId) {
        showSideForm();

        $.ajax({
            url: workforce.config.apiUrl,
            type: 'POST',
            data: {
                action: 'workforce_get_personnel_form',
                personnel_id: personnelId,
                mode: 'edit',
                nonce: workforce.config.nonce,
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $('#formTitle').text('ویرایش پرسنل');
                    $('#sideFormBody').html(response.data.html);

                    // تنظیم تاریخ‌نگار
                    setupDatePickers();

                    // بررسی قابلیت ناوبری
                    checkNavigationButtons();

                    // ذخیره ID فعلی
                    $('#sideForm').data('current-personnel-id', personnelId);
                }
            },
        });
    }

    /**
     * مشاهده پرسنل
     */
    function viewPersonnel(personnelId) {
        showSideForm();

        $.ajax({
            url: workforce.config.apiUrl,
            type: 'POST',
            data: {
                action: 'workforce_get_personnel_form',
                personnel_id: personnelId,
                mode: 'view',
                nonce: workforce.config.nonce,
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $('#formTitle').text('مشاهده پرسنل');
                    $('#sideFormBody').html(response.data.html);
                    checkNavigationButtons();

                    $('#sideForm').data('current-personnel-id', personnelId);
                }
            },
        });
    }

    /**
     * نمایش فرم سمت راست
     */
    function showSideForm() {
        $('#sideForm').addClass('active');
        $('body').css('overflow', 'hidden');
    }

    /**
     * پنهان کردن فرم سمت راست
     */
    function hideSideForm() {
        $('#sideForm').removeClass('active');
        $('body').css('overflow', '');
        $('#sideForm').removeData('current-personnel-id');
    }

    /**
     * بررسی دکمه‌های ناوبری
     */
    function checkNavigationButtons() {
        const currentId = parseInt($('#sideForm').data('current-personnel-id'));
        if (!currentId) return;

        const $rows = $('#tableBody tr[data-personnel-id]');
        let currentIndex = -1;

        $rows.each(function (index) {
            if (parseInt($(this).data('personnel-id')) === currentId) {
                currentIndex = index;
                return false;
            }
        });

        $('#prevBtn').prop('disabled', currentIndex <= 0);
        $('#nextBtn').prop('disabled', currentIndex >= $rows.length - 1);
    }

    /**
     * ناوبری بین رکوردها
     */
    function navigatePersonnel(direction) {
        const currentId = parseInt($('#sideForm').data('current-personnel-id'));
        if (!currentId) return;

        const $rows = $('#tableBody tr[data-personnel-id]');
        let currentIndex = -1;

        $rows.each(function (index) {
            if (parseInt($(this).data('personnel-id')) === currentId) {
                currentIndex = index;
                return false;
            }
        });

        if (direction === 'prev' && currentIndex > 0) {
            const prevId = parseInt($rows.eq(currentIndex - 1).data('personnel-id'));
            editPersonnel(prevId);
        } else if (direction === 'next' && currentIndex < $rows.length - 1) {
            const nextId = parseInt($rows.eq(currentIndex + 1).data('personnel-id'));
            editPersonnel(nextId);
        }
    }

    /**
     * ذخیره فرم ویرایش
     */
    function savePersonnelForm() {
        if (workforce.status.saving) return;

        const personnelId = $('#sideForm').data('current-personnel-id');
        if (!personnelId) return;

        const $form = $('#sideFormBody').find('form');
        if (!$form.length) return;

        workforce.status.saving = true;
        showLoading();

        const formData = new FormData($form[0]);
        formData.append('action', 'workforce_save_personnel');
        formData.append('personnel_id', personnelId);
        formData.append('nonce', workforce.config.nonce);

        $.ajax({
            url: workforce.config.apiUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showNotification('تغییرات با موفقیت ذخیره شد و برای تایید ارسال شد', 'success');
                    hideSideForm();
                    loadInitialData();
                    updateMonitoringCards();
                } else {
                    showNotification(
                        'خطا: ' + (response.data?.message || 'خطای ناشناخته'),
                        'error'
                    );
                }
            },
            error: function (xhr, status, error) {
                showNotification('خطا در ارتباط با سرور', 'error');
                console.error('خطای AJAX:', error);
            },
            complete: function () {
                workforce.status.saving = false;
                hideLoading();
            },
        });
    }

    /**
     * درخواست حذف پرسنل
     */
    function requestDeletePersonnel(personnelId) {
        if (
            !confirm('آیا از حذف این پرسنل اطمینان دارید؟ این عمل نیاز به تایید مدیر سیستم دارد.')
        ) {
            return;
        }

        $.ajax({
            url: workforce.config.apiUrl,
            type: 'POST',
            data: {
                action: 'workforce_request_delete_personnel',
                personnel_id: personnelId,
                nonce: workforce.config.nonce,
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showNotification(
                        'درخواست حذف با موفقیت ارسال شد و در انتظار تایید است',
                        'success'
                    );
                    loadInitialData();
                } else {
                    showNotification(
                        'خطا: ' + (response.data?.message || 'خطای ناشناخته'),
                        'error'
                    );
                }
            },
        });
    }

    /**
     * حذف چندین ردیف انتخاب شده
     */
    function deleteSelectedRows() {
        const selectedRows = Array.from(workforce.cache.selectedRows);

        if (selectedRows.length === 0) {
            showNotification('لطفا ابتدا ردیف‌هایی را برای حذف انتخاب کنید', 'warning');
            return;
        }

        if (!confirm(`آیا از حذف ${selectedRows.length} ردیف انتخاب شده اطمینان دارید؟`)) {
            return;
        }

        $.ajax({
            url: workforce.config.apiUrl,
            type: 'POST',
            data: {
                action: 'workforce_request_bulk_delete',
                personnel_ids: selectedRows,
                nonce: workforce.config.nonce,
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showNotification(
                        `درخواست حذف ${selectedRows.length} ردیف با موفقیت ارسال شد`,
                        'success'
                    );
                    workforce.cache.selectedRows.clear();
                    $('#selectAll').prop('checked', false);
                    loadInitialData();
                } else {
                    showNotification(
                        'خطا: ' + (response.data?.message || 'خطای ناشناخته'),
                        'error'
                    );
                }
            },
        });
    }

    /**
     * نمایش مودال افزودن پرسنل
     */
    function showAddPersonnelModal() {
        $.ajax({
            url: workforce.config.apiUrl,
            type: 'POST',
            data: {
                action: 'workforce_get_additional_fields',
                nonce: workforce.config.nonce,
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $('#additionalFields').html(response.data.html);
                    showModal('addPersonnelModal');
                    setupDatePickers();
                }
            },
        });
    }

    /**
     * ثبت فرم افزودن پرسنل
     */
    function submitAddPersonnelForm() {
        const $form = $('#addPersonnelForm');

        // اعتبارسنجی فرم
        if (!$form[0].checkValidity()) {
            showNotification('لطفا فیلدهای ضروری را پر کنید', 'warning');
            $form.find(':invalid').first().focus();
            return;
        }

        // اعتبارسنجی کدملی
        const nationalCode = $('#new_national_code').val();
        const deptId = $('.workforce-manager-panel').data('dept-id');

        $.ajax({
            url: workforce.config.apiUrl,
            type: 'POST',
            data: {
                action: 'workforce_validate_national_code',
                national_code: nationalCode,
                department_id: deptId,
                nonce: workforce.config.nonce,
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    // ارسال فرم
                    const formData = new FormData($form[0]);
                    formData.append('action', 'workforce_request_add_personnel');
                    formData.append('department_id', deptId);
                    formData.append('nonce', workforce.config.nonce);

                    $.ajax({
                        url: workforce.config.apiUrl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function (response) {
                            if (response.success) {
                                showNotification(
                                    'درخواست افزودن پرسنل با موفقیت ارسال شد',
                                    'success'
                                );
                                hideModal('addPersonnelModal');
                                $form[0].reset();
                                loadInitialData();
                                updateMonitoringCards();
                            } else {
                                showNotification(
                                    'خطا: ' + (response.data?.message || 'خطای ناشناخته'),
                                    'error'
                                );
                            }
                        },
                    });
                } else {
                    $('#nationalCodeValidation')
                        .text(response.data.message)
                        .css('color', '#e74c3c');
                }
            },
        });
    }

    /**
     * خروجی اکسل
     */
    function exportToExcel() {
        if (workforce.status.exporting) return;

        workforce.status.exporting = true;
        showLoading('در حال آماده‌سازی فایل اکسل...');

        const deptId = $('.workforce-manager-panel').data('dept-id');
        const periodId = $('.workforce-manager-panel').data('period-id');

        const params = {
            action: 'workforce_export_excel',
            department_id: deptId,
            period_id: periodId,
            filters: JSON.stringify(workforce.cache.filters),
            search: workforce.cache.searchTerm,
            nonce: workforce.config.nonce,
        };

        const url = workforce.config.apiUrl + '?' + $.param(params);

        // ایجاد لینک مخفی برای دانلود
        const $link = $('<a>', {
            href: url,
            target: '_blank',
            style: 'display: none;',
        }).appendTo('body');

        $link[0].click();
        $link.remove();

        setTimeout(() => {
            workforce.status.exporting = false;
            hideLoading();
            showNotification('فایل اکسل با موفقیت ایجاد شد', 'success');
        }, 1000);
    }

    /**
     * خروجی اکسل سازمانی
     */
    function exportOrgToExcel() {
        const deptFilter = $('#orgDeptFilter').val();
        const statusFilter = $('#orgStatusFilter').val();
        const search = $('#orgGlobalSearch').val();

        const params = {
            action: 'workforce_export_org_excel',
            department_id: deptFilter === 'all' ? '' : deptFilter,
            status: statusFilter === 'all' ? '' : statusFilter,
            search: search,
            nonce: workforce.config.nonce,
        };

        const url = workforce.config.apiUrl + '?' + $.param(params);
        window.open(url, '_blank');
    }

    /**
     * به‌روزرسانی داده‌ها
     */
    function refreshData() {
        loadInitialData();
        showNotification('داده‌ها با موفقیت به‌روزرسانی شدند', 'success');
    }

    /**
     * نمایش مودال
     */
    function showModal(modalId) {
        const $modal = $('#' + modalId);
        $modal.fadeIn(300);
        $('body').css('overflow', 'hidden');

        if (workforce.state.activeModals.indexOf(modalId) === -1) {
            workforce.state.activeModals.push(modalId);
        }
    }

    /**
     * پنهان کردن مودال
     */
    function hideModal(modalId) {
        const $modal = $('#' + modalId);
        $modal.fadeOut(300);

        const index = workforce.state.activeModals.indexOf(modalId);
        if (index > -1) {
            workforce.state.activeModals.splice(index, 1);
        }

        if (workforce.state.activeModals.length === 0) {
            $('body').css('overflow', '');
        }
    }

    /**
     * نمایش ناتفیکیشن
     */
    function showNotification(message, type = 'info') {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️',
        };

        const $notification = $(`
            <div class="workforce-notification notification-${type}">
                <div class="notification-icon">${icons[type] || icons.info}</div>
                <div class="notification-content">
                    <div class="notification-message">${message}</div>
                </div>
                <button type="button" class="notification-close">×</button>
            </div>
        `);

        $('body').append($notification);

        // event handler برای بستن
        $notification.find('.notification-close').on('click', function () {
            hideNotification($notification);
        });

        // بستن خودکار بعد از ۵ ثانیه
        setTimeout(() => {
            hideNotification($notification);
        }, 5000);

        workforce.state.notifications.push($notification);
    }

    /**
     * پنهان کردن ناتفیکیشن
     */
    function hideNotification($notification) {
        $notification.fadeOut(300, function () {
            $(this).remove();

            const index = workforce.state.notifications.indexOf($notification);
            if (index > -1) {
                workforce.state.notifications.splice(index, 1);
            }
        });
    }

    /**
     * نمایش بارگذاری
     */
    function showLoading(message = 'در حال بارگذاری...') {
        if ($('#workforceLoading').length) return;

        const $loading = $(`
            <div id="workforceLoading" class="loading-overlay">
                <div class="loading-content">
                    <div class="loading-spinner"></div>
                    <div class="loading-text">${message}</div>
                </div>
            </div>
        `);

        $('body').append($loading);
    }

    /**
     * پنهان کردن بارگذاری
     */
    function hideLoading() {
        $('#workforceLoading').fadeOut(300, function () {
            $(this).remove();
        });
    }

    /**
     * اسکرول به بالای جدول
     */
    function scrollToTableTop() {
        const $table = $('.workforce-main-table');
        if ($table.length) {
            $('html, body').animate(
                {
                    scrollTop: $table.offset().top - 100,
                },
                500
            );
        }
    }

    /**
     * تنظیمات ریسپانسیو
     */
    function setupResponsive() {
        handleResize();
    }

    /**
     * مدیریت تغییر اندازه پنجره
     */
    function handleResize() {
        const width = $(window).width();

        // تنظیمات برای موبایل
        if (width < 768) {
            $('.column-actions').css('opacity', '1');
            $('.welcome-details').css('flex-direction', 'column');
        } else {
            $('.column-actions').css('opacity', '');
            $('.welcome-details').css('flex-direction', '');
        }

        // تنظیم عرض فرم سمت راست
        if (width < 480) {
            $('#sideForm').css('width', '100%');
        } else {
            $('#sideForm').css('width', '400px');
        }
    }

    /**
     * تابع کمکی برای debounce
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
     * تابع کمکی برای throttle
     */
    function throttle(func, limit) {
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
    }

    /**
     * تابع کمکی برای فرمت اعداد
     */
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * تابع کمکی برای برچسب نوع استخدام
     */
    function getEmploymentTypeLabel(type) {
        const labels = {
            permanent: 'دائمی',
            contract: 'پیمانی',
            temporary: 'موقت',
            project: 'پروژه‌ای',
        };
        return labels[type] || type;
    }

    /**
     * تابع کمکی برای نشان وضعیت
     */
    function getStatusBadge(status) {
        const badges = {
            active: '<span class="status-badge status-active">فعال</span>',
            inactive: '<span class="status-badge status-inactive">غیرفعال</span>',
            suspended: '<span class="status-badge status-suspended">تعلیق</span>',
            retired: '<span class="status-badge status-retired">بازنشسته</span>',
        };
        return badges[status] || status;
    }

    /**
     * کپی اطلاعات پرسنل
     */
    function copyPersonnelData(personnelId) {
        $.ajax({
            url: workforce.config.apiUrl,
            type: 'POST',
            data: {
                action: 'workforce_get_personnel_data_text',
                personnel_id: personnelId,
                nonce: workforce.config.nonce,
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    navigator.clipboard
                        .writeText(response.data.text)
                        .then(() => {
                            showNotification('اطلاعات با موفقیت کپی شد', 'success');
                        })
                        .catch(() => {
                            showNotification('خطا در کپی اطلاعات', 'error');
                        });
                }
            },
        });
    }

    /**
     * نمایش منوی زمینه
     */
    $(document).on('contextmenu', '.workforce-data-table tbody tr', function (e) {
        e.preventDefault();

        const personnelId = $(this).data('personnel-id');
        if (!personnelId) return;

        // حذف منوی قبلی
        $('.context-menu').remove();

        const $menu = $(`
            <div class="context-menu" style="top: ${e.pageY}px; left: ${e.pageX}px">
                <div class="menu-item" data-action="edit">
                    <span class="menu-icon">✏️</span>
                    ویرایش
                </div>
                <div class="menu-item" data-action="view">
                    <span class="menu-icon">👁️</span>
                    مشاهده
                </div>
                <div class="menu-item" data-action="copy">
                    <span class="menu-icon">📋</span>
                    کپی اطلاعات
                </div>
                <div class="menu-separator"></div>
                <div class="menu-item" data-action="delete">
                    <span class="menu-icon">🗑️</span>
                    حذف
                </div>
            </div>
        `);

        $('body').append($menu);

        // event handlers
        $menu.find('.menu-item').on('click', function () {
            const action = $(this).data('action');

            switch (action) {
                case 'edit':
                    editPersonnel(personnelId);
                    break;
                case 'view':
                    viewPersonnel(personnelId);
                    break;
                case 'copy':
                    copyPersonnelData(personnelId);
                    break;
                case 'delete':
                    requestDeletePersonnel(personnelId);
                    break;
            }

            $menu.remove();
        });

        // بستن منو با کلیک خارج
        $(document).one('click', function () {
            $menu.remove();
        });
    });

    /**
     * تنظیم event handlers برای پنل سازمان
     */
    if ($('.workforce-org-manager-panel').length) {
        // فیلتر جدول سازمانی
        $('#orgDeptFilter, #orgStatusFilter').on('change', function () {
            workforce.config.currentPage = 1;
            loadInitialData();
        });

        // جستجوی جدول سازمانی
        let orgSearchTimer;
        $('#orgGlobalSearch').on('keyup', function () {
            clearTimeout(orgSearchTimer);
            orgSearchTimer = setTimeout(() => {
                workforce.config.currentPage = 1;
                loadInitialData();
            }, 500);
        });

        // صفحه‌بندی جدول سازمانی
        $(document).on('click', '#orgFirstPage', goToOrgFirstPage);
        $(document).on('click', '#orgPrevPage', goToOrgPreviousPage);
        $(document).on('click', '#orgNextPage', goToOrgNextPage);
        $(document).on('click', '#orgLastPage', goToOrgLastPage);
    }

    /**
     * توابع صفحه‌بندی جدول سازمانی
     */
    function goToOrgPage(page) {
        if (page >= 1 && page <= workforce.config.totalPages) {
            workforce.config.currentPage = page;
            loadInitialData();
        }
    }

    function goToOrgPreviousPage() {
        if (workforce.config.currentPage > 1) {
            goToOrgPage(workforce.config.currentPage - 1);
        }
    }

    function goToOrgNextPage() {
        if (workforce.config.currentPage < workforce.config.totalPages) {
            goToOrgPage(workforce.config.currentPage + 1);
        }
    }

    function goToOrgFirstPage() {
        goToOrgPage(1);
    }

    function goToOrgLastPage() {
        goToOrgPage(workforce.config.totalPages);
    }

    /**
     * مشاهده پرسنل در سطح سازمان
     */
    function viewOrgPersonnel(personnelId) {
        // در این نسخه ساده، از همان تابع viewPersonnel استفاده می‌کنیم
        viewPersonnel(personnelId);
    }

    /**
     * نمایش نمودار پرسنل
     */
    function showPersonnelChart(personnelId) {
        showNotification('این قابلیت در نسخه فعلی موجود نیست', 'info');
    }

    /**
     * نمایش گزارشات سازمان
     */
    function showOrgReports() {
        showModal('orgReportsModal');
    }

    /**
     * شروع برنامه
     */
    init();

    /**
     * توابع عمومی برای استفاده در HTML
     */
    window.workforceFunctions = {
        // جدول
        loadTableData: loadInitialData,
        goToPage: goToPage,
        goToPreviousPage: goToPreviousPage,
        goToNextPage: goToNextPage,
        goToFirstPage: goToFirstPage,
        goToLastPage: goToLastPage,
        changeRecordsPerPage: changeRecordsPerPage,
        performGlobalSearch: performGlobalSearch,
        clearAllFilters: clearAllFilters,

        // کارت‌ها
        createMonitoringCard: createMonitoringCard,
        removeMonitoringCard: removeMonitoringCard,

        // فیلترها
        showColumnFilter: showColumnFilter,
        applyColumnFilter: applyColumnFilter,
        clearColumnFilter: clearColumnFilter,
        updateColumnFilter: updateColumnFilter,

        // انتخاب
        toggleSelectAll: toggleSelectAll,
        toggleRowSelection: toggleRowSelection,
        deleteSelectedRows: deleteSelectedRows,

        // فرم‌ها
        editPersonnel: editPersonnel,
        viewPersonnel: viewPersonnel,
        savePersonnelForm: savePersonnelForm,
        requestDeletePersonnel: requestDeletePersonnel,
        navigatePersonnel: navigatePersonnel,
        showAddPersonnelModal: showAddPersonnelModal,
        submitAddPersonnelForm: submitAddPersonnelForm,
        hideSideForm: hideSideForm,

        // خروجی
        exportToExcel: exportToExcel,
        exportOrgToExcel: exportOrgToExcel,
        refreshData: refreshData,

        // مودال‌ها
        showModal: showModal,
        hideModal: hideModal,

        // پنل سازمان
        showOrgReports: showOrgReports,
        showDeptDetails: showDeptDetails,
        viewOrgPersonnel: viewOrgPersonnel,
        showPersonnelChart: showPersonnelChart,

        // ابزارها
        showNotification: showNotification,
        formatNumber: formatNumber,
        getEmploymentTypeLabel: getEmploymentTypeLabel,
        getStatusBadge: getStatusBadge,
    };
});
