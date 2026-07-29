<?php

namespace Models;

use PDO;

class CronCalismaModel extends Model
{
    protected $table = 'cron_calismalari';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function baslat(string $cronAdi): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (cron_adi, baslangic, durum)
             VALUES (:cron_adi, :baslangic, 'calisiyor')"
        );
        $stmt->execute([
            ':cron_adi' => $cronAdi,
            ':baslangic' => date('Y-m-d H:i:s'),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function tamamla(int $id, array $sayaclar = [], ?string $ozet = null): void
    {
        if ($id <= 0) {
            return;
        }

        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET bitis = :bitis,
                 durum = 'tamamlandi',
                 isyeri_sayisi = :isyeri_sayisi,
                 onaylanan = :onaylanan,
                 basarisiz = :basarisiz,
                 atlanan = :atlanan,
                 ozet = :ozet
             WHERE id = :id"
        );
        $stmt->execute([
            ':bitis' => date('Y-m-d H:i:s'),
            ':isyeri_sayisi' => (int)($sayaclar['isyeri_sayisi'] ?? 0),
            ':onaylanan' => (int)($sayaclar['onaylanan'] ?? 0),
            ':basarisiz' => (int)($sayaclar['basarisiz'] ?? 0),
            ':atlanan' => (int)($sayaclar['atlanan'] ?? 0),
            ':ozet' => $ozet,
            ':id' => $id,
        ]);
    }

    public function hataylaBitir(int $id, string $hataMesaji, array $sayaclar = [], ?string $ozet = null): void
    {
        if ($id <= 0) {
            return;
        }

        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET bitis = :bitis,
                 durum = 'hata',
                 isyeri_sayisi = :isyeri_sayisi,
                 onaylanan = :onaylanan,
                 basarisiz = :basarisiz,
                 atlanan = :atlanan,
                 hata_mesaji = :hata_mesaji,
                 ozet = :ozet
             WHERE id = :id"
        );
        $stmt->execute([
            ':bitis' => date('Y-m-d H:i:s'),
            ':isyeri_sayisi' => (int)($sayaclar['isyeri_sayisi'] ?? 0),
            ':onaylanan' => (int)($sayaclar['onaylanan'] ?? 0),
            ':basarisiz' => (int)($sayaclar['basarisiz'] ?? 0),
            ':atlanan' => (int)($sayaclar['atlanan'] ?? 0),
            ':hata_mesaji' => mb_substr($hataMesaji, 0, 5000),
            ':ozet' => $ozet,
            ':id' => $id,
        ]);
    }

    public function sonCalismalar(int $limit = 50): array
    {
        $limit = max(1, min(500, $limit));
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} ORDER BY baslangic DESC LIMIT {$limit}"
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
