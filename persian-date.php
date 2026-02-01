<?php
/**
 * کلاس تاریخ شمسی فارسی
 */

class PersianDate {
    
    public static function gregorian_to_jalali($g_y, $g_m = null, $g_d = null) {
        if ($g_m === null) {
            // اگر تاریخ کامل داده شده
            $date = explode('-', $g_y);
            $g_y = intval($date[0]);
            $g_m = intval($date[1]);
            $g_d = intval($date[2]);
        }
        
        $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
        
        $gy = $g_y - 1600;
        $gm = $g_m - 1;
        $gd = $g_d - 1;
        
        $g_day_no = 365 * $gy + floor(($gy + 3) / 4) - floor(($gy + 99) / 100) + floor(($gy + 399) / 400);
        
        for ($i = 0; $i < $gm; ++$i)
            $g_day_no += $g_days_in_month[$i];
        if ($gm > 1 && (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0)))
            $g_day_no++;
        $g_day_no += $gd;
        
        $j_day_no = $g_day_no - 79;
        
        $j_np = floor($j_day_no / 12053);
        $j_day_no %= 12053;
        
        $jy = 979 + 33 * $j_np + 4 * floor($j_day_no / 1461);
        $j_day_no %= 1461;
        
        if ($j_day_no >= 366) {
            $jy += floor(($j_day_no - 1) / 365);
            $j_day_no = ($j_day_no - 1) % 365;
        }
        
        for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i)
            $j_day_no -= $j_days_in_month[$i];
        $jm = $i + 1;
        $jd = $j_day_no + 1;
        
        return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
    }
    
    public static function jalali_to_gregorian($j_y, $j_m = null, $j_d = null) {
        if ($j_m === null) {
            $date = explode('/', $j_y);
            $j_y = intval($date[0]);
            $j_m = intval($date[1]);
            $j_d = intval($date[2]);
        }
        
        $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
        
        $jy = $j_y - 979;
        $jm = $j_m - 1;
        $jd = $j_d - 1;
        
        $j_day_no = 365 * $jy + floor($jy / 33) * 8 + floor(($jy % 33 + 3) / 4);
        for ($i = 0; $i < $jm; ++$i)
            $j_day_no += $j_days_in_month[$i];
        $j_day_no += $jd;
        
        $g_day_no = $j_day_no + 79;
        
        $gy = 1600 + 400 * floor($g_day_no / 146097);
        $g_day_no %= 146097;
        
        $leap = true;
        if ($g_day_no >= 36525) {
            $g_day_no--;
            $gy += 100 * floor($g_day_no / 36524);
            $g_day_no %= 36524;
            if ($g_day_no >= 365)
                $g_day_no++;
            else
                $leap = false;
        }
        
        $gy += 4 * floor($g_day_no / 1461);
        $g_day_no %= 1461;
        
        if ($g_day_no >= 366) {
            $leap = false;
            $g_day_no--;
            $gy += floor($g_day_no / 365);
            $g_day_no %= 365;
        }
        
        for ($i = 0; $g_day_no >= $g_days_in_month[$i] + ($i == 1 && $leap); $i++)
            $g_day_no -= $g_days_in_month[$i] + ($i == 1 && $leap);
        $gm = $i + 1;
        $gd = $g_day_no + 1;
        
        return array($gy, $gm, $gd);
    }
    
    public static function now($format = 'Y/m/d H:i:s') {
        $gregorian = date('Y-m-d H:i:s');
        $date_parts = explode(' ', $gregorian);
        
        list($j_y, $j_m, $j_d) = explode('/', self::gregorian_to_jalali($date_parts[0]));
        $time = $date_parts[1];
        
        $formatted = str_replace(
            ['Y', 'm', 'd', 'H', 'i', 's'],
            [$j_y, $j_m, $j_d, date('H'), date('i'), date('s')],
            $format
        );
        
        return $formatted;
    }
    
    public static function get_jalali_months() {
        return [
            'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
            'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
        ];
    }
}