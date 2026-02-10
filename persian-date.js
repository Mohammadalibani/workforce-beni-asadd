/**
 * Persian Date - نسخه ساده و اصلاح شده برای پلاگین مدیریت پرسنل
 * Version: 1.0.0
 * کاملاً محلی و بدون نیاز به اینترنت
 */

(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined'
        ? factory(exports)
        : typeof define === 'function' && define.amd
          ? define(['exports'], factory)
          : factory((global.persianDate = {}));
})(this, function (exports) {
    'use strict';

    // تابع اصلی PersianDate
    var PersianDate = function (input) {
        if (!(this instanceof PersianDate)) {
            return new PersianDate(input);
        }

        this._d = input ? new Date(input) : new Date();
    };

    // تبدیل میلادی به شمسی
    PersianDate.prototype.toPersian = function () {
        var gd = this._d.getDate();
        var gm = this._d.getMonth() + 1;
        var gy = this._d.getFullYear();

        // آرایه روزهای گذشته در هر ماه
        var g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

        // بررسی سال کبیسه
        var gy2 = gm > 2 ? gy + 1 : gy;

        // محاسبه روزهای گذشته از شروع تقویم میلادی
        var days =
            365 * gy +
            Math.floor((gy2 + 3) / 4) -
            Math.floor((gy2 + 99) / 100) +
            Math.floor((gy2 + 399) / 400) -
            80 +
            gd +
            g_d_m[gm - 1];

        var jy = 979;
        days =
            days -
            (1600 * 365 +
                Math.floor((1600 + 3) / 4) -
                Math.floor((1600 + 99) / 100) +
                Math.floor((1600 + 399) / 400) -
                80);

        jy = jy + 33 * Math.floor(days / 12053);
        days = days % 12053;
        jy = jy + 4 * Math.floor(days / 1461);
        days = days % 1461;

        if (days > 365) {
            jy = jy + Math.floor((days - 1) / 365);
            days = (days - 1) % 365;
        }

        var jm = days < 186 ? 1 + Math.floor(days / 31) : 7 + Math.floor((days - 186) / 30);
        var jd = 1 + (days < 186 ? days % 31 : (days - 186) % 30);

        return {
            year: jy,
            month: jm,
            day: jd,
        };
    };

    // فرمت کردن تاریخ
    PersianDate.prototype.format = function (format) {
        var persian = this.toPersian();
        var result = format;

        var replacements = {
            YYYY: persian.year,
            YY: String(persian.year).slice(-2),
            MM: persian.month < 10 ? '0' + persian.month : persian.month,
            M: persian.month,
            DD: persian.day < 10 ? '0' + persian.day : persian.day,
            D: persian.day,
            yyyy: persian.year,
            yy: String(persian.year).slice(-2),
            mm: persian.month < 10 ? '0' + persian.month : persian.month,
            m: persian.month,
            dd: persian.day < 10 ? '0' + persian.day : persian.day,
            d: persian.day,
        };

        for (var key in replacements) {
            if (replacements.hasOwnProperty(key)) {
                var regex = new RegExp(key, 'g');
                result = result.replace(regex, replacements[key]);
            }
        }

        return result;
    };

    // تبدیل شمسی به میلادی
    PersianDate.prototype.fromPersian = function (jy, jm, jd) {
        if (jy > 979) {
            var gy = 1600;
            jy = jy - 979;
        } else {
            var gy = 621;
        }

        var days =
            365 * jy +
            Math.floor(jy / 33) * 8 +
            Math.floor(((jy % 33) + 3) / 4) +
            78 +
            jd +
            (jm < 7 ? (jm - 1) * 31 : (jm - 7) * 30 + 186);

        gy = gy + 400 * Math.floor(days / 146097);
        days = days % 146097;

        if (days > 36524) {
            gy = gy + 100 * Math.floor(--days / 36524);
            days = days % 36524;
            if (days >= 365) days++;
        }

        gy = gy + 4 * Math.floor(days / 1461);
        days = days % 1461;

        if (days > 365) {
            gy = gy + Math.floor((days - 1) / 365);
            days = (days - 1) % 365;
        }

        var gd = days + 1;
        var gm = 0;

        var monthDays = [
            31,
            (gy % 4 === 0 && gy % 100 !== 0) || gy % 400 === 0 ? 29 : 28,
            31,
            30,
            31,
            30,
            31,
            31,
            30,
            31,
            30,
            31,
        ];

        for (var i = 0; i < monthDays.length; i++) {
            if (gd <= monthDays[i]) break;
            gd = gd - monthDays[i];
            gm++;
        }

        this._d = new Date(gy, gm, gd);
        return this;
    };

    // متدهای کمکی
    PersianDate.prototype.toString = function () {
        return this.format('YYYY/MM/DD');
    };

    PersianDate.prototype.valueOf = function () {
        return this._d.valueOf();
    };

    PersianDate.prototype.getTime = function () {
        return this._d.getTime();
    };

    // متدهای استاتیک
    PersianDate.now = function () {
        return new PersianDate();
    };

    PersianDate.today = function () {
        return new PersianDate();
    };

    // اکسپورت
    exports.PersianDate = PersianDate;

    Object.defineProperty(exports, '__esModule', { value: true });
});
