<?php

namespace Models;



use PDO;
use Models\UserModel;

class KullaniciAbonelikModel extends Model
{
    protected $table = 'kullanici_abonelikleri';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Kullanicının Aktif abonelik paketini alır.
     * @param int $kullaniciId
     * @return object
     */
    public function getSubscriptionByUserId($kullaniciId)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} 
                                        WHERE kullanici_id = :kullanici_id 
                                        AND baslangic_tarihi <= :bugun 
                                        AND bitis_tarihi >= :bugun
                                        AND durum = 'aktif'
                                        ORDER BY id DESC");
        $stmt->bindParam(':kullanici_id', $kullaniciId);
        $bugun = date('Y-m-d');
        $stmt->bindParam(':bugun', $bugun);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }



    /** Kulanıcının firma hakkını getirir
        * @param int $kullaniciId
        * @return int
        */
    public function getUserFirmLimit($kullaniciId)
    {
        $abonelik = $this->getSubscriptionByUserId($kullaniciId);
        if($abonelik){
            
            return $abonelik->firma_hakki ?? 0; //abonelik paketi bulunamazsa 0 döner
        }
         
        return 0; //abonelik yoksa 0 döner
    }


    /**
     * Kullanıcının aktif aboneliği var mı 
     * Başlangıç ve bitiş tarihleri arasında abonelik durumu aktif olanları kontrol eder.
     * @param int $kullaniciId
     * @return bool
     */
    public function hasActiveSubscription($kullaniciId)
    {
        //kullanıcı türü user ise abonelik kontrolü yapma
        $KullaniciModel = new UserModel();
        $kullanici = $KullaniciModel->findById($kullaniciId);
        if($kullanici->role == "user"){
            return true;
        }

        $abonelik = $this->getSubscriptionByUserId($kullaniciId);
        return $abonelik !== false;
    }


    /**
     * Kullanıcıların aboneliklerini kullanıcı adı ve paket adı ile beraber getirir
     * Bu fonksiyon admin tarafında paketleri onaylamak için kullanılabilir.
     * @return array
     */
    public function getAllSubscriptionsWithUsernames()
    {
        $stmt = $this->db->prepare("SELECT 
                                                k.adi_soyadi,
                                                ap.ad as paket_adi,
                                                ka.baslangic_tarihi,
                                                ka.bitis_tarihi,
                                                ka.durum
                                            FROM 
                                                $this->table ka

                                            LEFT JOIN 
                                                kullanicilar k ON k.id = ka.kullanici_id
                                            LEFT JOIN
                                                abonelik_paketleri ap ON ap.id = ka.paket_id
                                            ORDER BY ka.id desc
                                                ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }



    
}
