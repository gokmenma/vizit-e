<?php

namespace Admin\Models;

use PDO;

class PackageModel extends Model {

    public function __construct() {
        parent::__construct('abonelik_paketleri');
    }

    /**
     * Get all active subscription packages sorted by price
     * @return array
     */
    public function all() {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE (silinme_tarihi IS NULL OR silinme_tarihi = '') ORDER BY fiyat ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Soft delete a package by setting its silinme_tarihi
     * @param int $id
     * @return bool
     */
    public function softDelete($id) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET silinme_tarihi = ? WHERE {$this->primaryKey} = ?");
        return $stmt->execute([date('Y-m-d H:i:s'), $id]);
    }
}

