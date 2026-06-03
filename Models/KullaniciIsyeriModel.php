<?php

namespace Models;

use PDO;
use Models\KullaniciAbonelikModel;


class KullaniciIsyeriModel extends Model
{
    protected $table = 'kullanici_isyerleri';

    public function __construct()
    {
        parent::__construct($this->table);
    }



    /** Kullanıcının işyerlerini al
     * @param int $kullaniciId
     * @return array|object[]

     */
    public function findByUserId($kullaniciId)
    {
        $sql = "SELECT * FROM $this->table
                        WHERE kullanici_id = :kullanici_id
                        AND (silinme_tarihi IS NULL OR silinme_tarihi = '')";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':kullanici_id', $kullaniciId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }


    /**
     * Alt kullanıcının yetkili olduğu işyerlerini al
     * @param array $isyeri_ids
     * @return object
     */
    public function altKullanicininYetkiliOlduguIsyerleri($isyeri_ids)
    {
        if (empty($isyeri_ids)) {
            return [];
        }
        // SQL sorgusunu dinamik olarak oluştur
        $placeholders = implode(',', array_fill(0, count($isyeri_ids), '?'));
        $sql = "SELECT * FROM $this->table 
                        WHERE id IN ($placeholders)
                        AND (silinme_tarihi IS NULL OR silinme_tarihi = '')";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($isyeri_ids);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**Alt kullanıcının yetkili olduğu işyerlerini al
     * @param string $isyeri_ids
     * @return object
     */
    public function AltKullaniciİsyerleri($isyeri_ids)
    {
        $sql = "SELECT * FROM $this->table 
                        WHERE FIND_IN_SET(id, :isyeri_ids)
                        AND (silinme_tarihi IS NULL OR silinme_tarihi = '')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['isyeri_ids' => $isyeri_ids]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }


    /** 
     * Kullanicinin kayit ettiği isyeri sayısını al
     * @param int $kullaniciId
     * @return int
     */

    public function countFirmByUserId($kullaniciId)
    {
        $sql = "SELECT COUNT(*) as sayi FROM $this->table WHERE kullanici_id = :kullanici_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':kullanici_id', $kullaniciId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ)->sayi ?? 0;
    }

    /** Kullanıcının kalan firma hakkını döndürür
     * @param int $kullaniciId
     * @return int
     */
    public function kalanFirmaHakki($kullaniciId)
    {
        $KullaniciAbonelikModel = new KullaniciAbonelikModel();
        $firmaHakki = $KullaniciAbonelikModel->getUserFirmLimit($kullaniciId);
        $kullaniciIsyeriSayisi = $this->countFirmByUserId($kullaniciId);
        return $firmaHakki - $kullaniciIsyeriSayisi;
    }

    /**Kullanıcının işyerlerini sil
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

    /**Kullanıcının işyerlerinden otomatik_rapor_onayi aktif olanları getirir
     * @param int $kullaniciId
     * 
     */
    public function findActiveForAutoOnayByUserId($kullaniciId)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table 
                                           WHERE kullanici_id = ? 
                                           AND (otomatik_rapor_onay = ?)
                                           AND (silinme_tarihi IS NULL OR silinme_tarihi = '')");
        $sql->execute([$kullaniciId, 1]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    /** Kullanıcının işyerinin otomatik rapor onayı aktif mi değil mi kontrol eder
     * @param int $isyeriId
     * @return bool
     */
    public function isAutoOnayActive($isyeriId)
    {
        $sql = $this->db->prepare("SELECT COUNT(*) AS sayi FROM $this->table 
                                                WHERE id = ?
                                                AND otomatik_rapor_onay = ?
                                                AND (silinme_tarihi IS NULL OR silinme_tarihi = '')");

        $sql->execute([$isyeriId, 1]);
        $result = $sql->fetch(PDO::FETCH_OBJ);
        return $result->sayi > 0 ? true : false;
    }

    // KullaniciIsyeriModel.php içinde örnek metot
    public function findAllActiveForAutoOnay()
    {
        // Bu sorgu, aynı SGK bilgilerine sahip işyerlerinden sadece bir tanesini alır.
        $sql = "SELECT * FROM $this->table 
                     WHERE otomatik_rapor_onay = 1
                     GROUP BY kullanici_kodu, isyeri_kodu";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /* Otomaik onay sonucunu benzersiz email adreslerine göndermek için 
* @return array Tekrarlayan email adreslerini kaldırır
*/
    public function findUniqueEmailsForAutoOnay()
    {
        $sql = "SELECT DISTINCT otomatik_onay_eposta FROM $this->table 
            WHERE otomatik_rapor_onay = 1
            GROUP BY kullanici_kodu, isyeri_kodu , kullanici_id
            AND (silinme_tarihi IS NULL OR silinme_tarihi = '')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ); // Sadece email sütununu döndür
    }



    public function findUserIdsByIsyeriId($isyeriId)
    {
        // Önce referans işyerinin SGK bilgilerini al
        $refIsyeri = $this->find($isyeriId);
        if (!$refIsyeri) return [];

        // Bu SGK bilgileriyle eşleşen tüm kullanıcı ID'lerini bul
        $sql = "SELECT DISTINCT kullanici_id FROM $this->table 
                                         WHERE kullanici_kodu = ? 
                                         AND isyeri_kodu = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$refIsyeri->kullanici_kodu, $refIsyeri->isyeri_kodu]);

        // Sadece ID'leri içeren tek boyutlu bir dizi döndür
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}
