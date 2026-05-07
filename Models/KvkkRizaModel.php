<?php

namespace Models;

use PDO;

class KvkkRizaModel extends Model

{
    // Raporlarınızın bulunduğu tablo adını buraya yazın
    protected $table = 'kvkk_rizalar'; // Örnek tablo adı, sizinki farklı olabilir

    public function __construct()
    {
        parent::__construct($this->table);
    }


    /**
     * Kullanıcının kvkk bilgilerini getirir    
     * @param int $kullanici_id
     * @return object

     */
    public function getKvkkRizaByUserId(int $kullanici_id,string $metin_turu )
    {
        $stmt = $this->db->prepare("SELECT 
                                                kr.id,
                                                kr.kvkk_type,
                                                kb.metin_icerik as icerik
                                            FROM $this->table kr
                                            LEFT JOIN kvkk_bilgileri kb ON kb.id = kr.kvkk_bilgi_id
                                           WHERE kullanici_id = :kullanici_id
                                           AND kr.kvkk_type = :metin_turu
                                           
                                           ");
        $stmt->bindParam(':kullanici_id', $kullanici_id, PDO::PARAM_INT);
        $stmt->bindParam(':metin_turu', $metin_turu, PDO::PARAM_STR);
        $stmt->execute();
        $result= $stmt->fetch(PDO::FETCH_OBJ);
        return $result ?? null;

    }
}
