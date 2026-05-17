<?php

namespace Admin\Models;

use PDO;

class PurchaseModel extends Model {

    public function __construct() {
        parent::__construct('kullanici_abonelikleri');
    }

    /**
     * Get all subscriber purchases & transactions across the system
     * @return array
     */
    public function getPurchases() {
        $stmt = $this->db->prepare("SELECT ka.*, k.adi_soyadi as ad_soyad, k.kullanici_adi, k.email, ka.paket_id as current_package_id, ap.ad as paket_adi, ap.fiyat 
                                    FROM {$this->table} ka 
                                    LEFT JOIN kullanicilar k ON ka.kullanici_id = k.id 
                                    LEFT JOIN abonelik_paketleri ap ON ka.paket_id = ap.id 
                                    ORDER BY ka.id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get details of a single purchase record
     * @param int $id
     * @return object|false
     */
    public function getPurchaseById($id) {
        $stmt = $this->db->prepare("SELECT ka.*, k.adi_soyadi FROM {$this->table} ka LEFT JOIN kullanicilar k ON ka.kullanici_id = k.id WHERE ka.id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
}
