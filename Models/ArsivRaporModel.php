<?php

namespace Models;

use DateTime;
use PDO;
use RuntimeException;

class ArsivRaporModel extends Model
{
    protected $table = 'arsivlenen_raporlar';

    public function __construct()
    {
        parent::__construct($this->table);
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS {$this->table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                isyeri_id INT NULL,
                kullanici_id INT NULL,
                isyeri_kodu VARCHAR(50) NOT NULL,
                medula_rapor_id VARCHAR(50) NOT NULL,
                rapor_takip_no VARCHAR(50) NULL,
                rapor_sira_no VARCHAR(20) NULL,
                poliklinik_tarihi DATE NULL,
                rapor_verisi LONGTEXT NOT NULL,
                sgk_kapatildi TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_isyeri_medula (isyeri_kodu, medula_rapor_id),
                KEY idx_isyeri_tarih (isyeri_kodu, poliklinik_tarihi)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function arsivle(
        array $rapor,
        string $isyeriKodu,
        ?int $isyeriId = null,
        ?int $kullaniciId = null
    ): void {
        $medulaRaporId = trim((string)($rapor['MEDULARAPORID'] ?? ''));
        if ($medulaRaporId === '' || $isyeriKodu === '') {
            throw new RuntimeException('Arşiv kaydı için işyeri veya Medula rapor kimliği eksik.');
        }

        $raporJson = json_encode($rapor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($raporJson === false) {
            throw new RuntimeException('Arşiv raporu JSON formatına dönüştürülemedi.');
        }

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table}
                (isyeri_id, kullanici_id, isyeri_kodu, medula_rapor_id, rapor_takip_no,
                 rapor_sira_no, poliklinik_tarihi, rapor_verisi)
             VALUES
                (:isyeri_id, :kullanici_id, :isyeri_kodu, :medula_rapor_id, :rapor_takip_no,
                 :rapor_sira_no, :poliklinik_tarihi, :rapor_verisi)
             ON DUPLICATE KEY UPDATE
                isyeri_id = VALUES(isyeri_id),
                kullanici_id = VALUES(kullanici_id),
                rapor_takip_no = VALUES(rapor_takip_no),
                rapor_sira_no = VALUES(rapor_sira_no),
                poliklinik_tarihi = VALUES(poliklinik_tarihi),
                rapor_verisi = VALUES(rapor_verisi)"
        );

        $stmt->execute([
            ':isyeri_id' => $isyeriId,
            ':kullanici_id' => $kullaniciId,
            ':isyeri_kodu' => $isyeriKodu,
            ':medula_rapor_id' => $medulaRaporId,
            ':rapor_takip_no' => $rapor['RAPORTAKIPNO'] ?? null,
            ':rapor_sira_no' => $rapor['RAPORSIRANO'] ?? null,
            ':poliklinik_tarihi' => $this->normalizeDate($rapor['POLIKLINIKTAR'] ?? null),
            ':rapor_verisi' => $raporJson,
        ]);
    }

    public function kapatildiOlarakIsaretle(string $isyeriKodu, string $medulaRaporId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET sgk_kapatildi = 1
             WHERE isyeri_kodu = :isyeri_kodu AND medula_rapor_id = :medula_rapor_id"
        );
        $stmt->execute([
            ':isyeri_kodu' => $isyeriKodu,
            ':medula_rapor_id' => $medulaRaporId,
        ]);
    }

    public function tarihAraligindaGetir(string $isyeriKodu, DateTime $tarih1, DateTime $tarih2): array
    {
        $stmt = $this->db->prepare(
            "SELECT rapor_verisi
             FROM {$this->table}
             WHERE isyeri_kodu = :isyeri_kodu
               AND poliklinik_tarihi BETWEEN :tarih1 AND :tarih2
             ORDER BY poliklinik_tarihi DESC, id DESC"
        );
        $stmt->execute([
            ':isyeri_kodu' => $isyeriKodu,
            ':tarih1' => $tarih1->format('Y-m-d'),
            ':tarih2' => $tarih2->format('Y-m-d'),
        ]);

        $raporlar = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $raporJson) {
            $rapor = json_decode($raporJson, true);
            if (is_array($rapor)) {
                $raporlar[] = $rapor;
            }
        }

        return $raporlar;
    }

    private function normalizeDate($value): ?string
    {
        $value = trim((string)$value);
        if ($value === '' || $value === '0001-01-01' || $value === '0000-00-00') {
            return null;
        }

        foreach (['Y-m-d', 'd.m.Y', 'd/m/Y'] as $format) {
            $date = DateTime::createFromFormat('!' . $format, $value);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }
}
