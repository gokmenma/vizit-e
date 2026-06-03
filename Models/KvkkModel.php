<?php

namespace Models;

use PDO;

class KvkkModel extends Model
{
    // Raporlarınızın bulunduğu tablo adını buraya yazın
    protected $table = 'kvkk_bilgileri'; // Örnek tablo adı, sizinki farklı olabilir

    public function __construct()
    {
        parent::__construct($this->table);
    }

    

    /** Metin türüne göre KVKK metnini getirir
     * @param string $metin_turu 'kisisel_veri' veya 'cerez_politikasi'
     *
     */
    public function getKvkkMetniByType(string $metin_turu)
    {
        $stmt = $this->db->prepare("SELECT * FROM $this->table 
                                           WHERE metin_turu = :metin_turu 
                                           ORDER BY id DESC LIMIT 1");
        $stmt->bindParam(':metin_turu', $metin_turu, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
}