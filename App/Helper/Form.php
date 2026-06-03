<?php 

namespace App\Helper;

class Form
{
   
    /**
     * Gelen Değerlere göre Select2 elemanını oluşturur
     * * @param string $name Select elemanının adı
     * * @param array $options Select elemanının seçenekleri
     * * @param string $selected Seçili olan değer
     */
    public static function Select2($name, $id = null, $options = [], $selected = null, $class = 'form-select select2 w-100')
    {
        // ID değeri verilmemişse, name değerini kullan
        $id = $id ?? $name;

        // Select başlangıcı
        $select = '<select id="' . htmlspecialchars($id) . 
                      '" name="' . htmlspecialchars($name) . 
                     '" class="' . htmlspecialchars($class) . '">';

        // Seçenekleri döngüyle ekle
        foreach ($options as $key => $value) {
            $selectedAttr = ($selected !== null && $selected == $key) ? 'selected' : '';
            $select .= "<option value='" . htmlspecialchars($key) . 
                            "' $selectedAttr>" . htmlspecialchars($value) . "</option>";
        }

        // Select bitişi
        $select .= '</select>';

        return $select;
    }


/**
 * Gelen değerlere göre bir Select2 Multiple elemanı oluşturur.
 *
 * @param string $name Select elemanının adı. Dizi olarak veri almak için "isim[]" şeklinde olmalıdır.
 * @param array $options Seçenekler. ['value1' => 'Label 1', 'value2' => 'Label 2'] formatında olmalıdır.
 * @param array $selectedValues Seçili olan değerleri içeren bir DİZİ.
 * @param string $class Select elemanının CSS sınıfı.
 * @param string|null $id Select elemanına atanacak ID. Boş bırakılırsa, isimden türetilir.
 * @return string Oluşturulan HTML çıktısı.
 */
public static function Select2Multiple(
    string $name,
    array $options = [],
    array $selectedValues = [], // Artık bir dizi olarak bekleniyor
    string $class = 'form-select select2 w-100',
    ?string $id = null
): string 
{
    // ID değeri verilmemişse, name'deki "[]" karakterlerini temizleyerek bir ID türet.
    $elementId = $id ?? preg_replace('/\[\]$/', '', $name);

    // Select başlangıcı
    $select = '<select id="' . htmlspecialchars($elementId) . '" ' .
                    'name="' . htmlspecialchars($name) . '" ' .
                    'class="' . htmlspecialchars($class) . '" multiple="multiple">'; // multiple niteliği standartlara uygun hale getirildi.

    // Seçenekleri döngüyle ekle
    foreach ($options as $value => $label) {
        // Değerin seçili değerler dizisinde olup olmadığını kontrol et.
        // Tür duyarlılığı olmaması için == ile karşılaştırma yapılabilir veya türler aynı olmalı.
        $selectedAttr = in_array($value, $selectedValues) ? 'selected' : '';
        
        $select .= '<option value="' . htmlspecialchars($value) . '" ' . $selectedAttr . '>' . 
                        htmlspecialchars($label) . 
                   '</option>';
    }

    // Select bitişi
    $select .= '</select>';

    return $select;
}



}