<?php

namespace Models;

use PDO;


class KullaniciAyarModel extends Model
{
    protected $table = 'kullanici_ayarlari';

    public function __construct()
    {
        parent::__construct($this->table);
    }


    /** Belli bir parametreye göre kullanıcı ayarını getirir 
     * @param string $param
     * @return object
     */
    public function getSetting($param, $kullanici_id= null)
    {
        // Eğer kullanıcı ID'si verilmemişse, oturumdan al
        if ($kullanici_id === null) {
            if (!isset($_SESSION["kullanici_id"])) {
                throw new \Exception("Kullanıcı ID'si oturumda bulunamadı.");
            }
            $kullanici_id = $_SESSION["kullanici_id"];
        }

        $sql = $this->db->prepare("SELECT deger FROM $this->table 
                                           WHERE kullanici_id = ? 
                                           AND anahtar  = ? ");

        $sql->execute([$kullanici_id, $param]);
        $row = $sql->fetchObject();
        return $row ? ($row->deger ?? 0) : 0;
    }

    
    /** Kullanıcıya ait ayarları günceller
     * @param array $data ["anahtar_adi" => "deger"]
     * @return bool
     */
    public function updateSettings($kullanici_id , $data)
    {

        //Eğer bu ayar tabloda kayıtlı değilse ekle
        foreach ($data as $anahtar => $deger) {
            $exists = $this->db->prepare("SELECT COUNT(*) as count FROM $this->table 
                                                WHERE kullanici_id = ? 
                                                AND anahtar = ?");
            $exists->execute([$kullanici_id, $anahtar]);
            $count = $exists->fetch(PDO::FETCH_OBJ)->count ?? 0;

            if ($count == 0) {
                $insert = $this->db->prepare("INSERT INTO $this->table (kullanici_id, anahtar, deger) 
                                                    VALUES (?, ?, ?)");
                $insert->execute([$kullanici_id, $anahtar, $deger]);
            }
        }


        $sql = $this->db->prepare("UPDATE $this->table 
                                        SET deger = ? 
                                        WHERE kullanici_id = ? 
                                        AND anahtar = ?");

        foreach ($data as $anahtar => $deger) {
            $sql->execute([$deger, $kullanici_id, $anahtar]);
        }

        return true;
    }

  
    /** Kullanıcı ayarlarını sil
     * @param int $kullaniciId
     * @return bool
     */
    public function softDeleteByUserId($kullaniciId)
    {
        $sql = "UPDATE $this->table 
                SET silinme_tarihi = :silinme_tarihi 
                WHERE kullanici_id = :kullanici_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':kullanici_id', $kullaniciId);
        $silinmeTarihi = date('Y-m-d H:i:s');
        $stmt->bindParam(':silinme_tarihi', $silinmeTarihi);
        return $stmt->execute();
    }


    /**Otomatik onay ayarını getirir
     * @param int $kullaniciId
     * @return object
     */
    public function findActiveForAutoOnay()
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table 
                                           WHERE anahtar = ? 
                                           AND deger = ?
                                           LIMIT 1");
        $sql->execute([ 'rapor_otomatik_onay', '1']);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

}
