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

    /**
     * SGK'nin her rapor satiri icin verdigi tekil Medula rapor kimligine gore kaydi getirir.
     * Ayni RAPORTAKIPNO altinda birden fazla RAPORSIRANO bulunabildigi icin bekleyen rapor
     * kontrolünde takip numarasi tek basina kullanilmamalidir.
     */
    public function findReportByMedulaRaporId($medulaRaporId)
    {
        if ($medulaRaporId === null || $medulaRaporId === '') {
            return false;
        }

        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE MEDULARAPORID = :medula_rapor_id LIMIT 1");
        $stmt->bindValue(':medula_rapor_id', (string)$medulaRaporId, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Verilen Medula rapor kimliklerinden yerel veritabanında bulunanları topluca getirir.
     *
     * @return string[]
     */
    public function findExistingMedulaRaporIds(array $medulaRaporIds): array
    {
        $medulaRaporIds = array_values(array_unique(array_filter(
            array_map(static fn($id): string => trim((string)$id), $medulaRaporIds),
            static fn(string $id): bool => $id !== ''
        )));

        if ($medulaRaporIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($medulaRaporIds), '?'));
        $stmt = $this->db->prepare(
            "SELECT MEDULARAPORID FROM {$this->table} WHERE MEDULARAPORID IN ({$placeholders})"
        );
        $stmt->execute($medulaRaporIds);

        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
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
