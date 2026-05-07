<?php

namespace Models;

use PDO;

class RaporModel extends Model
{
    // Raporlarınızın bulunduğu tablo adını buraya yazın
    protected $table = 'onaylanan_raporlar'; // Örnek tablo adı, sizinki farklı olabilir


    public function __construct()
    {
        parent::__construct($this->table);
    }

/** Rapor takip numarasına göre onayli rapor bilgisini getir
 * @param string $rapor_takip_no
 * @return object
 */
    public function findReportByRaporTakipNo($rapor_takip_no)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE RAPORTAKIPNO = :rapor_takip_no LIMIT 1");
        $stmt->bindParam(':rapor_takip_no', $rapor_takip_no, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }



    /** Raporun onaylanma türünü döndürür
     * @param int $medulaRaporId
     * @return int
     */
    public function onaylanmaTuru($medulaRaporId)
    {
        $stmt = $this->db->prepare("SELECT onay_turu 
                                            FROM $this->table 
                                            WHERE MEDULARAPORID = :medulaRaporId");
        $stmt->bindParam(':medulaRaporId', $medulaRaporId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ)->onay_turu ?? "Sgk Vizite";
    }


    

 
}