<?php
/**
 * پنل مدیران و مدیران سازمان - نسخه کامل
 * شامل تمام امکانات درخواستی
 */

// ==================== بخش ۱: امنیت و بررسی دسترسی ====================

if (!defined('ABSPATH')) {
    exit('دسترسی مستقیم ممنوع است');
}

// بررسی session
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true
    ]);
}

// Rate Limiting
function workforce_rate_limit($user_id) {
    $key = 'workforce_rate_' . $user_id;
    $current_time = time();
    $requests = $_SESSION[$key] ?? [];
    
    // حذف درخواست‌های قدیمی (آخرین 60 ثانیه)
    $requests = array_filter($requests, function($time) use ($current_time) {
        return $current_time - $time < 60;
    });
    
    // بررسی تعداد درخواست‌ها
    if (count($requests) >= 100) {
        wp_die('تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً 1 دقیقه صبر کنید.', 429);
    }
    
    $requests[] = $current_time;
    $_SESSION[$key] = $requests;
    
    return true;
}

// بررسی دسترسی
if (!is_user_logged_in()) {
    wp_die('<div class="workforce-error">لطفاً ابتدا وارد شوید.</div>');
}

$user = wp_get_current_user();
$user_id = $user->ID;

// Rate Limiting اعمال
workforce_rate_limit($user_id);

// بررسی نقش
$is_admin = current_user_can('administrator');
$is_org_manager = current_user_can('workforce_org_manager');
$is_dept_manager = current_user_can('workforce_dept_manager');

if (!$is_admin && !$is_org_manager && !$is_dept_manager) {
    wp_die('<div class="workforce-error">شما دسترسی به این پنل را ندارید.</div>');
}

// ==================== بخش ۲: AJAX Handlers کامل ====================

// Handler اصلی AJAX
function workforce_manager_ajax_handler() {
    // بررسی nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'workforce_manager_nonce')) {
        wp_send_json_error('توکن امنیتی نامعتبر است', 403);
    }
    
    // بررسی ورودی‌ها
    $action = sanitize_text_field($_POST['action_type'] ?? '');
    
    if (empty($action)) {
        wp_send_json_error('نوع عملیات مشخص نشده است');
    }
    
    // اعتبارسنجی داده‌ها
    $data = workforce_sanitize_input($_POST);
    
    try {
        switch ($action) {
            case 'get_statistics':
                $result = handle_get_statistics($data);
                break;
                
            case 'get_personnel':
                $result = handle_get_personnel($data);
                break;
                
            case 'create_personnel':
                $result = handle_create_personnel($data);
                break;
                
            case 'update_personnel':
                $result = handle_update_personnel($data);
                break;
                
            case 'delete_personnel':
                $result = handle_delete_personnel($data);
                break;
                
            case 'export_excel':
                $result = handle_export_excel($data);
                break;
                
            case 'get_department_status':
                $result = handle_get_department_status($data);
                break;
                
            case 'get_column_values':
                $result = handle_get_column_values($data);
                break;
                
            case 'save_preferences':
                $result = handle_save_preferences($data);
                break;
                
            case 'bulk_actions':
                $result = handle_bulk_actions($data);
                break;
                
            default:
                throw new Exception('عملیات نامعتبر');
        }
        
        wp_send_json_success($result);
        
    } catch (Exception $e) {
        error_log('Workforce Error: ' . $e->getMessage());
        wp_send_json_error($e->getMessage());
    }
}
add_action('wp_ajax_workforce_manager_ajax', 'workforce_manager_ajax_handler');

// اعتبارسنجی ورودی‌ها
function workforce_sanitize_input($input) {
    $sanitized = [];
    
    foreach ($input as $key => $value) {
        if (is_array($value)) {
            $sanitized[$key] = array_map('sanitize_text_field', $value);
        } else {
            // اعتبارسنجی ویژه برای هر فیلد
            switch ($key) {
                case 'national_code':
                    if (!preg_match('/^\d{10}$/', $value)) {
                        throw new Exception('کد ملی نامعتبر است');
                    }
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
                    
                case 'phone':
                    if (!preg_match('/^09\d{9}$/', $value)) {
                        throw new Exception('شماره تلفن نامعتبر است');
                    }
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
                    
                case 'email':
                    $sanitized[$key] = sanitize_email($value);
                    break;
                    
                case 'data':
                    // داده‌های JSON
                    $decoded = json_decode(stripslashes($value), true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception('داده‌های ارسالی نامعتبر است');
                    }
                    $sanitized[$key] = map_deep($decoded, 'sanitize_text_field');
                    break;
                    
                default:
                    $sanitized[$key] = sanitize_text_field($value);
            }
        }
    }
    
    return $sanitized;
}

// ==================== بخش ۳: توابع عملیاتی ====================

// دریافت آمار
function handle_get_statistics($data) {
    global $wpdb;
    
    $user_id = get_current_user_id();
    $period_id = intval($data['period_id'] ?? 0);
    
    // اعتبارسنجی دوره
    $period = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}workforce_periods WHERE id = %d AND is_active = 1",
        $period_id
    ));
    
    if (!$period) {
        throw new Exception('دوره انتخاب شده معتبر نیست');
    }
    
    // دریافت ادارات تحت مدیریت کاربر
    $departments = get_user_departments($user_id);
    
    if (empty($departments)) {
        return [
            'total_personnel' => 0,
            'filled_main_fields' => 0,
            'total_main_fields' => 0,
            'departments' => []
        ];
    }
    
    $dept_ids = array_column($departments, 'id');
    $dept_ids_placeholders = implode(',', array_fill(0, count($dept_ids), '%d'));
    
    // تعداد کل پرسنل
    $total_personnel = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}workforce_personnel 
         WHERE period_id = %d AND department_id IN ($dept_ids_placeholders) AND is_active = 1",
        array_merge([$period_id], $dept_ids)
    ));
    
    // فیلدهای اصلی
    $main_fields = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}workforce_fields WHERE is_main = 1 AND is_active = 1",
        ARRAY_A
    );
    
    $total_main_fields = count($main_fields);
    $filled_main_fields = 0;
    
    // محاسبه فیلدهای پر شده
    if ($total_personnel > 0 && $total_main_fields > 0) {
        foreach ($main_fields as $field) {
            $filled = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT personnel_id) 
                 FROM {$wpdb->prefix}workforce_personnel_data 
                 WHERE field_id = %d AND value IS NOT NULL AND value != '' 
                 AND personnel_id IN (
                     SELECT id FROM {$wpdb->prefix}workforce_personnel 
                     WHERE period_id = %d AND department_id IN ($dept_ids_placeholders) AND is_active = 1
                 )",
                array_merge([$field['id'], $period_id], $dept_ids)
            ));
            
            $filled_main_fields += $filled;
        }
    }
    
    // وضعیت ادارات
    $departments_status = [];
    foreach ($departments as $dept) {
        $dept_personnel = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}workforce_personnel 
             WHERE department_id = %d AND period_id = %d AND is_active = 1",
            $dept['id'], $period_id
        ));
        
        $dept_filled = 0;
        if ($dept_personnel > 0) {
            foreach ($main_fields as $field) {
                $filled = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT p.id) 
                     FROM {$wpdb->prefix}workforce_personnel p
                     JOIN {$wpdb->prefix}workforce_personnel_data d ON p.id = d.personnel_id
                     WHERE p.department_id = %d AND p.period_id = %d AND p.is_active = 1
                     AND d.field_id = %d AND d.value IS NOT NULL AND d.value != ''",
                    $dept['id'], $period_id, $field['id']
                ));
                $dept_filled += $filled;
            }
        }
        
        $total_needed = $dept_personnel * $total_main_fields;
        $completion_rate = $total_needed > 0 ? round(($dept_filled / $total_needed) * 100) : 0;
        
        $departments_status[] = [
            'id' => $dept['id'],
            'name' => $dept['department_name'],
            'total_personnel' => $dept_personnel,
            'filled_main_fields' => $dept_filled,
            'completion_rate' => $completion_rate,
            'status' => $completion_rate >= 90 ? 'success' : 
                       ($completion_rate >= 70 ? 'warning' : 'danger')
        ];
    }
    
    return [
        'total_personnel' => intval($total_personnel),
        'filled_main_fields' => intval($filled_main_fields),
        'total_main_fields' => intval($total_main_fields),
        'completion_percentage' => $total_personnel > 0 ? 
            round(($filled_main_fields / ($total_personnel * $total_main_fields)) * 100) : 0,
        'departments' => $departments_status,
        'period_name' => $period->period_name
    ];
}

// دریافت پرسنل با فیلتر و صفحه‌بندی
function handle_get_personnel($data) {
    global $wpdb;
    
    $user_id = get_current_user_id();
    $period_id = intval($data['period_id'] ?? 0);
    $page = intval($data['page'] ?? 1);
    $per_page = intval($data['per_page'] ?? 50);
    $offset = ($page - 1) * $per_page;
    
    // اعتبارسنجی
    if ($page < 1 || $per_page < 1 || $per_page > 200) {
        throw new Exception('پارامترهای صفحه‌بندی نامعتبر است');
    }
    
    // ادارات کاربر
    $departments = get_user_departments($user_id);
    if (empty($departments)) {
        return [
            'data' => [],
            'pagination' => [
                'total' => 0,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => 0
            ]
        ];
    }
    
    $dept_ids = array_column($departments, 'id');
    $dept_ids_placeholders = implode(',', array_fill(0, count($dept_ids), '%d'));
    
    // ساخت شرط‌های WHERE
    $where_conditions = ["p.period_id = %d", "p.department_id IN ($dept_ids_placeholders)", "p.is_active = 1"];
    $where_params = array_merge([$period_id], $dept_ids);
    
    // فیلترها
    $filters = json_decode(stripslashes($data['filters'] ?? '{}'), true) ?: [];
    
    if (!empty($filters)) {
        foreach ($filters as $field => $values) {
            if (!empty($values)) {
                if (is_array($values)) {
                    $placeholders = implode(',', array_fill(0, count($values), '%s'));
                    $where_conditions[] = "pd_$field.value IN ($placeholders)";
                    $where_params = array_merge($where_params, $values);
                } else {
                    $where_conditions[] = "pd_$field.value = %s";
                    $where_params[] = $values;
                }
                
                // JOIN برای فیلد
                $join_conditions["pd_$field"] = "LEFT JOIN {$wpdb->prefix}workforce_personnel_data pd_$field 
                    ON p.id = pd_$field.personnel_id AND pd_$field.field_key = %s";
                $where_params[] = $field;
            }
        }
    }
    
    // جستجو
    if (!empty($data['search'])) {
        $search = '%' . $wpdb->esc_like($data['search']) . '%';
        $where_conditions[] = "(p.first_name LIKE %s OR p.last_name LIKE %s OR p.national_code LIKE %s)";
        $where_params[] = $search;
        $where_params[] = $search;
        $where_params[] = $search;
    }
    
    // ساخت query
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    $join_clause = !empty($join_conditions) ? implode(' ', $join_conditions) : '';
    
    // تعداد کل رکوردها
    $count_query = "
        SELECT COUNT(DISTINCT p.id)
        FROM {$wpdb->prefix}workforce_personnel p
        $join_clause
        $where_clause
    ";
    
    $total = $wpdb->get_var($wpdb->prepare($count_query, $where_params));
    
    // دریافت داده‌ها
    $data_query = "
        SELECT p.*, 
               d.department_name,
               GROUP_CONCAT(CONCAT(pd.field_key, ':', pd.value) SEPARATOR '||') as field_data
        FROM {$wpdb->prefix}workforce_personnel p
        LEFT JOIN {$wpdb->prefix}workforce_departments d ON p.department_id = d.id
        LEFT JOIN {$wpdb->prefix}workforce_personnel_data pd ON p.id = pd.personnel_id
        $where_clause
        GROUP BY p.id
        ORDER BY p.created_at DESC
        LIMIT %d OFFSET %d
    ";
    
    $where_params[] = $per_page;
    $where_params[] = $offset;
    
    $results = $wpdb->get_results($wpdb->prepare($data_query, $where_params), ARRAY_A);
    
    // پردازش داده‌ها
    $processed_data = [];
    foreach ($results as $row) {
        $personnel = [
            'id' => $row['id'],
            'national_code' => $row['national_code'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'department_name' => $row['department_name'],
            'is_verified' => boolval($row['is_verified']),
            'created_at' => $row['created_at'],
            'data' => []
        ];
        
        // پردازش فیلدهای داینامیک
        if (!empty($row['field_data'])) {
            $fields = explode('||', $row['field_data']);
            foreach ($fields as $field) {
                if (!empty($field)) {
                    list($key, $value) = explode(':', $field, 2);
                    $personnel['data'][$key] = $value;
                }
            }
        }
        
        $processed_data[] = $personnel;
    }
    
    return [
        'data' => $processed_data,
        'pagination' => [
            'total' => intval($total),
            'per_page' => $per_page,
            'current_page' => $page,
            'total_pages' => ceil($total / $per_page)
        ]
    ];
}

// ایجاد پرسنل جدید
function handle_create_personnel($data) {
    global $wpdb;
    
    $user_id = get_current_user_id();
    
    // اعتبارسنجی داده‌های اجباری
    $required_fields = ['national_code', 'first_name', 'last_name', 'department_id', 'period_id'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("فیلد {$field} الزامی است");
        }
    }
    
    // بررسی تکراری نبودن کد ملی در این دوره
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}workforce_personnel 
         WHERE national_code = %s AND period_id = %d",
        $data['national_code'], $data['period_id']
    ));
    
    if ($exists > 0) {
        throw new Exception('این کد ملی در این دوره قبلاً ثبت شده است');
    }
    
    // بررسی دسترسی به اداره
    $user_depts = get_user_departments($user_id);
    $dept_ids = array_column($user_depts, 'id');
    
    if (!in_array($data['department_id'], $dept_ids)) {
        throw new Exception('دسترسی به این اداره ندارید');
    }
    
    // شروع تراکنش
    $wpdb->query('START TRANSACTION');
    
    try {
        // ذخیره اطلاعات اصلی
        $personnel_data = [
            'national_code' => $data['national_code'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'department_id' => $data['department_id'],
            'period_id' => $data['period_id'],
            'created_by' => $user_id,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'is_verified' => 0, // نیاز به تأیید ادمین
            'verification_status' => 'pending'
        ];
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'workforce_personnel',
            $personnel_data
        );
        
        if (!$result) {
            throw new Exception('خطا در ذخیره اطلاعات اصلی');
        }
        
        $personnel_id = $wpdb->insert_id;
        
        // ذخیره فیلدهای داینامیک
        if (!empty($data['data'])) {
            $field_data = json_decode(stripslashes($data['data']), true);
            
            foreach ($field_data as $field_key => $value) {
                if (!empty($value)) {
                    // دریافت ID فیلد
                    $field = $wpdb->get_row($wpdb->prepare(
                        "SELECT id FROM {$wpdb->prefix}workforce_fields WHERE field_key = %s",
                        $field_key
                    ));
                    
                    if ($field) {
                        $wpdb->insert(
                            $wpdb->prefix . 'workforce_personnel_data',
                            [
                                'personnel_id' => $personnel_id,
                                'field_id' => $field->id,
                                'field_key' => $field_key,
                                'value' => $value,
                                'created_at' => current_time('mysql')
                            ]
                        );
                    }
                }
            }
        }
        
        // ارسال نوتیفیکیشن به ادمین برای تأیید
        send_admin_notification(
            'درخواست تأیید پرسنل جدید',
            "پرسنل جدیدی با کد ملی {$data['national_code']} توسط کاربر ID {$user_id} اضافه شده است و نیاز به تأیید دارد.",
            'warning',
            $personnel_id
        );
        
        $wpdb->query('COMMIT');
        
        return [
            'id' => $personnel_id,
            'message' => 'اطلاعات با موفقیت ثبت شد. نیاز به تأیید مدیر سیستم دارد.'
        ];
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        throw $e;
    }
}

// خروجی اکسل کامل
function handle_export_excel($data) {
    global $wpdb;
    
    $user_id = get_current_user_id();
    $period_id = intval($data['period_id'] ?? 0);
    
    // بارگذاری کتابخانه PhpSpreadsheet
    require_once ABSPATH . 'wp-admin/includes/file.php';
    
    if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        // اگر PhpSpreadsheet نصب نیست، از نسخه ساده استفاده می‌کنیم
        return export_excel_simple($data);
    }
    
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    
    // ایجاد spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // تنظیمات اولیه
    $sheet->setRightToLeft(true);
    
    // دریافت تمام داده‌ها (بدون صفحه‌بندی)
    $data['per_page'] = -1; // دریافت همه رکوردها
    $personnel_data = handle_get_personnel($data);
    
    // دریافت فیلدها
    $fields = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}workforce_fields WHERE is_active = 1 ORDER BY display_order",
        ARRAY_A
    );
    
    // ساخت هدرها
    $headers = ['ردیف', 'کد ملی', 'نام', 'نام خانوادگی', 'اداره'];
    foreach ($fields as $field) {
        if ($field['is_main']) {
            $headers[] = $field['field_name'] . ($field['is_required'] ? ' *' : '');
        }
    }
    $headers[] = 'وضعیت تأیید';
    $headers[] = 'تاریخ ثبت';
    
    // نوشتن هدرها
    $column = 1;
    foreach ($headers as $header) {
        $sheet->setCellValueByColumnAndRow($column, 1, $header);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        $column++;
    }
    
    // استایل هدر
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 11
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '2C3E50']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '7F8C8D']
            ]
        ]
    ];
    
    $sheet->getStyle('A1:' . $sheet->getColumnDimensionByColumn(count($headers))->getColumnIndex() . '1')
          ->applyFromArray($headerStyle);
    
    // نوشتن داده‌ها
    $row = 2;
    foreach ($personnel_data['data'] as $index => $person) {
        $col = 1;
        
        // ردیف
        $sheet->setCellValueByColumnAndRow($col++, $row, $index + 1);
        
        // اطلاعات اصلی
        $sheet->setCellValueByColumnAndRow($col++, $row, $person['national_code']);
        $sheet->setCellValueByColumnAndRow($col++, $row, $person['first_name']);
        $sheet->setCellValueByColumnAndRow($col++, $row, $person['last_name']);
        $sheet->setCellValueByColumnAndRow($col++, $row, $person['department_name']);
        
        // فیلدهای داینامیک
        foreach ($fields as $field) {
            if ($field['is_main']) {
                $value = $person['data'][$field['field_key']] ?? '';
                $sheet->setCellValueByColumnAndRow($col++, $row, $value);
            }
        }
        
        // وضعیت
        $sheet->setCellValueByColumnAndRow($col++, $row, $person['is_verified'] ? 'تأیید شده' : 'در انتظار');
        
        // تاریخ
        $sheet->setCellValueByColumnAndRow($col++, $row, convert_to_persian_date($person['created_at']));
        
        $row++;
    }
    
    // استایل داده‌ها
    $dataStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'BDC3C7']
            ]
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ];
    
    $dataRange = 'A2:' . $sheet->getColumnDimensionByColumn(count($headers))->getColumnIndex() . ($row - 1);
    $sheet->getStyle($dataRange)->applyFromArray($dataStyle);
    
    // ستون‌های عددی
    foreach ($fields as $field) {
        if (in_array($field['field_type'], ['number', 'decimal'])) {
            $field_index = array_search($field['field_name'], $headers);
            if ($field_index !== false) {
                $column_letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($field_index + 1);
                $sheet->getStyle($column_letter . '2:' . $column_letter . ($row - 1))
                      ->getNumberFormat()
                      ->setFormatCode('#,##0');
            }
        }
    }
    
    // ذخیره فایل
    $filename = 'کارکرد_پرسنل_' . date('Y-m-d_H-i-s') . '.xlsx';
    $temp_file = wp_tempnam($filename);
    
    $writer = new Xlsx($spreadsheet);
    $writer->save($temp_file);
    
    // بازگرداندن فایل
    $file_content = file_get_contents($temp_file);
    unlink($temp_file);
    
    return [
        'filename' => $filename,
        'content' => base64_encode($file_content),
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
}

// تابع ساده برای خروجی اکسل (اگر PhpSpreadsheet نصب نیست)
function export_excel_simple($data) {
    $personnel_data = handle_get_personnel($data);
    
    $headers = ['ردیف', 'کد ملی', 'نام', 'نام خانوادگی', 'اداره', 'وضعیت تأیید', 'تاریخ ثبت'];
    
    $csv = implode(',', $headers) . "\n";
    
    foreach ($personnel_data['data'] as $index => $person) {
        $row = [
            $index + 1,
            '"' . $person['national_code'] . '"',
            '"' . $person['first_name'] . '"',
            '"' . $person['last_name'] . '"',
            '"' . $person['department_name'] . '"',
            '"' . ($person['is_verified'] ? 'تأیید شده' : 'در انتظار') . '"',
            '"' . convert_to_persian_date($person['created_at']) . '"'
        ];
        
        $csv .= implode(',', $row) . "\n";
    }
    
    $filename = 'کارکرد_پرسنل_' . date('Y-m-d_H-i-s') . '.csv';
    
    return [
        'filename' => $filename,
        'content' => base64_encode($csv),
        'mime_type' => 'text/csv; charset=utf-8'
    ];
}

// ==================== بخش ۴: توابع کمکی ====================

// دریافت ادارات کاربر
function get_user_departments($user_id) {
    global $wpdb;
    
    $user = get_userdata($user_id);
    $roles = $user->roles;
    
    if (in_array('administrator', $roles) || in_array('workforce_org_manager', $roles)) {
        // مدیر کل - همه ادارات
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}workforce_departments WHERE is_active = 1 ORDER BY department_name",
            ARRAY_A
        );
    } elseif (in_array('workforce_dept_manager', $roles)) {
        // مدیر اداره - فقط ادارات مرتبط
        return $wpdb->get_results($wpdb->prepare(
            "SELECT d.* FROM {$wpdb->prefix}workforce_departments d
             LEFT JOIN {$wpdb->prefix}workforce_department_managers dm ON d.id = dm.department_id
             WHERE dm.user_id = %d AND d.is_active = 1
             ORDER BY d.department_name",
            $user_id
        ), ARRAY_A);
    }
    
    return [];
}

// ارسال نوتیفیکیشن به ادمین
function send_admin_notification($title, $message, $type = 'info', $reference_id = null) {
    global $wpdb;
    
    $admins = get_users(['role' => 'administrator']);
    
    foreach ($admins as $admin) {
        $wpdb->insert(
            $wpdb->prefix . 'workforce_notifications',
            [
                'user_id' => $admin->ID,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'reference_id' => $reference_id,
                'is_read' => 0,
                'created_at' => current_time('mysql')
            ]
        );
    }
}

// تبدیل تاریخ به شمسی
function convert_to_persian_date($gregorian_date) {
    require_once dirname(__FILE__) . '/7-persian-date.php';
    return PersianDate::gregorian_to_jalali($gregorian_date);
}

// ==================== بخش ۵: HTML رابط کاربری ====================

// (HTML قبلی شما - با بهبود‌های جزئی)
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل مدیریت کارکرد پرسنل - بنی اسد</title>
    <link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__) . '5-workforce-styles.css'; ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
</head>
<body class="workforce-dashboard">
    <div class="dashboard-container" id="workforceDashboard">
        <!-- Loader اولیه -->
        <div class="page-loader" id="pageLoader">
            <div class="loader-spinner"></div>
            <p>در حال بارگذاری پنل مدیریت...</p>
        </div>
        
        <!-- Main Content (با JavaScript ساخته می‌شود) -->
        <div id="app" style="display: none;"></div>
        
        <!-- Error Boundary -->
        <div class="error-boundary" id="errorBoundary" style="display: none;">
            <div class="error-content">
                <div class="error-icon">⚠️</div>
                <h3>خطا در بارگذاری پنل</h3>
                <p id="errorMessage">یک خطای غیرمنتظره رخ داده است.</p>
                <button onclick="location.reload()" class="btn-primary">
                    <i class="mdi mdi-reload"></i> بارگذاری مجدد
                </button>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/vue@3.3.4/dist/vue.global.prod.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    
    <script src="<?php echo plugin_dir_url(__FILE__) . '6-workforce-scripts.js'; ?>"></script>
    
    <!-- انتقال داده‌های PHP به JavaScript -->
    <script>
    window.workforceConfig = {
        ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo wp_create_nonce('workforce_manager_nonce'); ?>',
        user: {
            id: <?php echo $user_id; ?>,
            name: '<?php echo esc_js($user->display_name); ?>',
            role: '<?php echo $is_admin ? 'admin' : ($is_org_manager ? 'org_manager' : 'dept_manager'); ?>',
            avatar: '<?php echo esc_url(get_avatar_url($user_id, ['size' => 64])); ?>'
        },
        periods: <?php echo json_encode(get_active_periods()); ?>,
        currentPeriod: <?php echo json_encode(get_current_period()); ?>,
        apiEndpoints: {
            statistics: 'get_statistics',
            personnel: 'get_personnel',
            createPersonnel: 'create_personnel',
            updatePersonnel: 'update_personnel',
            deletePersonnel: 'delete_personnel',
            exportExcel: 'export_excel',
            bulkActions: 'bulk_actions'
        },
        strings: {
            // تمام رشته‌های فارسی
            save: 'ذخیره',
            cancel: 'انصراف',
            delete: 'حذف',
            edit: 'ویرایش',
            add: 'افزودن',
            search: 'جستجو...',
            filter: 'فیلتر',
            export: 'خروجی Excel',
            loading: 'در حال بارگذاری...',
            noData: 'داده‌ای برای نمایش وجود ندارد',
            error: 'خطا',
            success: 'موفقیت‌آمیز',
            warning: 'هشدار',
            info: 'اطلاعات'
        }
    };
    </script>
    
    <!-- Initialization Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // مخفی کردن Loader بعد از 1 ثانیه (برای UX بهتر)
        setTimeout(() => {
            document.getElementById('pageLoader').style.display = 'none';
            document.getElementById('app').style.display = 'block';
            
            // راه‌اندازی برنامه Vue
            if (typeof window.initWorkforceApp === 'function') {
                try {
                    window.initWorkforceApp();
                } catch (error) {
                    console.error('خطا در راه‌اندازی برنامه:', error);
                    document.getElementById('errorBoundary').style.display = 'flex';
                    document.getElementById('errorMessage').textContent = error.message;
                }
            }
        }, 1000);
    });
    </script>
</body>
</html>
<?php

// ==================== بخش ۶: توابع کمکی پایانی ====================

function get_active_periods() {
    global $wpdb;
    return $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}workforce_periods 
         WHERE is_active = 1 
         ORDER BY period_year DESC, period_month DESC",
        ARRAY_A
    );
}

function get_current_period() {
    $periods = get_active_periods();
    return $periods[0] ?? null;
}

// پایان فایل
?>