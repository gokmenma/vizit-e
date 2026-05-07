<?php

namespace App\Helper;

use DateTime;
use PhpOffice\PhpSpreadsheet\Shared\Date as PhpSpreadsheetDate;
 


class Date
{
    public static function dmY($date = null, $format = 'd.m.Y')
    {
        if ($date == null) {
            $date = date('Y-m-d');
        }
        return date($format, strtotime($date));
    }

    public static function Ymd($date, $format = 'Ymd')
    {
        if ($date == null) {
            return null;
        }
        return date($format, strtotime($date));
    }

    public static function YmdHIS($date, $format = 'YmdHis')
    {
        if ($date == null) {
            return '';
        }

        return self::normalizeDate( $date);
    }

    public static function normalizeDate($date, $outputFormat = 'Y-m-d H:i:s') 
    {
        if (empty($date)) {
            return '';
        }
    
        // Önceden tanımlanmış yaygın formatlar
        $commonFormats = [
            'd/m/Y-H:i:s',    // 26/05/2025-14:02:40
            'd.m.Y H:i:s',     // 26.05.2025 14:02:40
            'Y-m-d H:i:s',     // 2025-05-26 14:02:40
            'd/m/Y H:i:s',     // 26/05/2025 14:02:40
            'm/d/Y H:i:s',     // 05/26/2025 14:02:40 (US format)
            'Ymd His',         // 20250526 140240
            'D M d Y H:i:s',   // Tue May 26 2025 14:02:40
            DateTime::ATOM,     // 2025-05-26T14:02:40+00:00
            DateTime::RFC2822,  // Tue, 26 May 2025 14:02:40 +0000
        ];
    
        // Önce DateTime objesi oluşturmayı dene
        $datetime = date_create($date);
        
        // Başarısız olursa, bilinen formatları dene
        if ($datetime === false) {
            foreach ($commonFormats as $format) {
                $datetime = DateTime::createFromFormat($format, $date);
                if ($datetime !== false) {
                    break;
                }
            }
        }
    
        // Hala geçerli bir tarih yoksa, şimdiki zamanı dön
        if ($datetime === false) {
            return date($outputFormat);
        }
    
        return $datetime->format($outputFormat);
    }



    public static function firstDay($month, $year)
    {
        return sprintf('%d%02d%02d', $year, $month, 1);
    }

    public static function lastDay($month, $year)
    {
        return sprintf(
            '%d%02d%02d',
            $year,
            $month,
            self::daysInMonth($month, $year),
        );
    }

    // Yarının tarihini d.m.Y formatında döndürür
    public static function getTomorrowDate($format = 'Ymd')
    {
        return date($format, strtotime('+1 day'));
    }

    public static function getDay($date = null, $leadingZero = true)
    {
        $format = $leadingZero ? 'd' : 'j';
        return $date ? date($format, strtotime($date)) : date($format);
    }

    public static function getMonth($date = null, $leadingZero = true)
    {
        $format = $leadingZero ? 'm' : 'n';
        return $date ? date($format, strtotime($date)) : date($format);
    }

    public static function getYear($date = null)
    {
        return $date ? date('Y', strtotime($date)) : date('Y');
    }

    public static function daysInMonth($month, $year)
    {
        return cal_days_in_month(CAL_GREGORIAN, $month, $year);
    }

    public static function generateDates($year, $month, $days)
    {
        $dateList = [];
        for ($day = 1; $day <= $days; $day++) {
            // Tarih formatını ayarlama (d.m.Y)
            $formattedDate = sprintf('%2d%02d%02d', $year, $month, $day);
            $dateList[] = $formattedDate;
        }
        return $dateList;
    }

    public static function isWeekend($date)
    {
        $dateTime = new \DateTime($date);
        $dayOfWeek = $dateTime->format('N');
        return ($dayOfWeek == 7);
    }

    public static function isDate($date)
    {
        return strtotime($date);
    }

    public static function isBetween($date, $startDate, $endDate)
    {
        $date = strtotime($date);
        $startDate = strtotime($startDate);
        $endDate = strtotime($endDate);
        return ($date >= $startDate && $date <= $endDate);
    }

    public static function isBefore($date, $compareDate)
    {
        $date = self::Ymd($date);
        $compareDate = self::Ymd($compareDate);
        return ($date < $compareDate);
    }

    public static function gunAdi($gun)
    {
        $gun = date('D', strtotime($gun));
        $gunler = array(
            'Mon' => 'Pzt',
            'Tue' => 'Sal',
            'Wed' => 'Çar',
            'Thu' => 'Per',
            'Fri' => 'Cum',
            'Sat' => 'Cmt',
            'Sun' => 'Paz'
        );
        return $gunler[$gun];
    }

    const MONTHS = [
        1 => 'Ocak',
        2 => 'Şubat',
        3 => 'Mart',
        4 => 'Nisan',
        5 => 'Mayıs',
        6 => 'Haziran',
        7 => 'Temmuz',
        8 => 'Ağustos',
        9 => 'Eylül',
        10 => 'Ekim',
        11 => 'Kasım',
        12 => 'Aralık'
    ];

    public static function monthName($month)
    {
        // 09 şeklinde gelen ayları 9 şekline çevir
        $month = ltrim($month, '0');
        return self::MONTHS[$month];
    }

    public static function getMonthsSelect(
        $name = 'months',
        $month = null,
    ) {
        if ($month == null) {
            $month = date('m');
        }
        $select = '<select name="' . $name . '" class="form-select select2" id="' . $name . '" style="width:100%">';
        $select .= '<option value="">Ay Seçiniz</option>';
        foreach (self::MONTHS as $key => $value) {
            $selected = $month == $key ? ' selected' : '';
            $select .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
        }
        $select .= '</select>';
        return $select;
    }

    public static function getYearsSelect(
        $name = 'years',
        $year = null,
    ) {
        if ($year == null) {
            $year = date('Y');
        }
        $select = '<select name="' . $name . '" class="form-select select2" id="' . $name . '" style="width:100%">';
        $select .= '<option value="">Yıl Seçiniz</option>';
        for ($i = 2021; $i <= 2030; $i++) {
            $selected = $year == $i ? ' selected' : '';
            $select .= '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
        }
        $select .= '</select>';
        return $select;
    }

    /**
     * İki tarih arasındaki gün farkını hesaplar.
     *
     * @param string $date1 İlk tarih (Y-m-d H:i:s formatında)
     * @param string $date2 İkinci tarih (Y-m-d H:i:s formatında - boş ise bugünün tarihi alınır)
     * @return int İki tarih arasındaki gün farkı
     */
    public static function getDateDiff($date1, $date2 = '')
    {
        // date2 boş ise bugünün tarihi alınır
        if ($date2 == '') {
            $date2 = date('Y-m-d H:i:s');
        }
        $datetime1 = new \DateTime($date1);
        $datetime2 = new \DateTime($date2);
        $interval = $datetime1->diff($datetime2);
        return (int) $interval->format('%a');
    }

    // Kalan günü hesaplar
    public static function getRemainingDays($date)
    {
        if ($date == null) {
            return '';
        }

        $today = date('Y-m-d');
        $date = date('Y-m-d', strtotime($date));
        $diff = strtotime($date) - strtotime($today);
        return floor($diff / (60 * 60 * 24));
    }


    

}
