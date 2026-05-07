<?php

namespace App\Helper;

use Model\DefinesModel;

class Helper
{
    const MONEY_UNIT = [
        '1' => 'TL',
        '2' => 'USD',
        '3' => 'EUR',
    ];
    
    public static function short($value, $lenght = 21)
    {
        if (empty($value)) return;
        return strlen($value) > $lenght ? substr($value, 0, $lenght) . '...' : $value;
    }

    public static function formattedMoney($value, $currency = 1)
    {
        return number_format($value, 2, ',', '.') . ' ' . self::MONEY_UNIT[$currency];
    }

    // 109.852,25 şeklinde gelen değeri 109852.25 olarak döndürür
    public static function formattedMoneyToNumber($value)
    {
        //içinde ₺ olabilir, onu kaldırır
        $value = str_replace('₺', '', $value);
        $value = str_replace(' ', '', $value); // Boşlukları kaldırır
        $value = str_replace('TL', '', $value); // TL'yi kaldırır
        return str_replace(['.', ','], ['', '.'], $value);
    }

    // Veritabanından gelen sayıdaki "." yı virgüle çevirir
    public static function moneyToNumber($value)
    {
        return str_replace('.', ',', $value);
    }

    // Para birim formatında TRY olmadan
    public static function formattedMoneyWithoutCurrency($value)
    {
        return number_format($value, 2, ',', '.');
    }

 

    // dd fonksiyonu
    public static function dd($data)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        die();
    }

    /*     
    * Gelen kelime adından baş harfleri alır
     * @param string $name
     * @param int $count Baş harf sayısı
     * @return string Baş harfler
     */
    public static function getInitials($name, $count = 2)
    {
        if (empty($name) || $name == null) {
            return '';
        }
        $name = explode(' ', $name);
        $initials = '';
        $counter = 0;
        foreach ($name as $n) {
            if (!empty($n) && $counter < $count) {  // Boş olup olmadığını ve counter'ı kontrol et
                $initials .= $n[0];
                $counter++;
            }
        }
        return strtoupper($initials);
    }

    // authorize sayfasını include eder
    public static function authorizePage()
    {
        echo '<div class="empty">
                <div class="empty-img">
                    <img src="static/unauthorize-red.svg" alt="" style="width:200px;height:200px">


                </div>
                <p class="empty-title">Yetkiniz Yok!!!</p>
                <p class="empty-subtitle text-secondary">
                    Bu alanı görüntüleme yetkiniz bulunmamaktadır!
                </p>
            
            </div>';
    }




}
