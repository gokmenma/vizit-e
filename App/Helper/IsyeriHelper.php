<?php 
namespace App\Helper;
use Models\KullaniciIsyeriModel;

class IsyeriHelper 
{


  

    /**
     * Kullanıcının işyerlerini select olarak getirir
     */
    public static function IsyeriSelect($name, $selected_id = null)
    {
        //name içindeki  [] işaretlerini kaldır
        $id = str_replace(['[',']'], '', $name);
        $KullaniciIsyeriModel = new KullaniciIsyeriModel();
        $kullanici_id = $_SESSION["kullanici_id"];
        $isyerleri = $KullaniciIsyeriModel->findByUserId($kullanici_id);

        $options = '';
        foreach ($isyerleri as $isyeri) {
            $selected = ($isyeri->id == $selected_id) ? 'selected' : '';
            $options .= "<option value='{$isyeri->id}' {$selected}>{$isyeri->firma_adi}</option>";
        }

        return "<select name='". $name."' id='". $id ."' class='form-control select2' multiple placeholder='İşyeri Seçiniz' data-placeholder='İşyeri Seçiniz' style='width:100%'>{$options}</select>";
    }
   

}
?>