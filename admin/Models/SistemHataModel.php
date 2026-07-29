<?php

namespace Admin\Models;

use PDO;
use Throwable;

class SistemHataModel extends Model
{
    private $projeKok;

    public function __construct()
    {
        parent::__construct('logs');
        $this->projeKok = dirname(__DIR__, 2);
    }

    public function veritabaniHatalari(int $limit = 200, array $seviyeler = ['ERROR', 'WARNING']): array
    {
        if ($seviyeler === []) {
            return [];
        }

        $limit = max(1, min(1000, $limit));
        $placeholders = implode(', ', array_fill(0, count($seviyeler), '?'));

        try {
            $stmt = $this->db->prepare(
                "SELECT l.id, l.level, l.message, l.context, l.channel, l.created_at,
                        l.ip_address, l.user_id, k.adi_soyadi
                 FROM logs l
                 LEFT JOIN kullanicilar k ON k.id = l.user_id
                 WHERE l.level IN ({$placeholders})
                 ORDER BY l.created_at DESC
                 LIMIT {$limit}"
            );
            $stmt->execute(array_values($seviyeler));

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {
            error_log('SistemHataModel::veritabaniHatalari: ' . $e->getMessage());
            return [];
        }
    }

    public function cronCalismalari(int $limit = 50): array
    {
        $limit = max(1, min(500, $limit));

        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM cron_calismalari ORDER BY baslangic DESC LIMIT {$limit}"
            );
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {
            error_log('SistemHataModel::cronCalismalari: ' . $e->getMessage());
            return [];
        }
    }

    public function cronOzetleri(): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT c.*
                 FROM cron_calismalari c
                 INNER JOIN (
                     SELECT cron_adi, MAX(baslangic) AS son_baslangic
                     FROM cron_calismalari
                     GROUP BY cron_adi
                 ) s ON s.cron_adi = c.cron_adi AND s.son_baslangic = c.baslangic
                 ORDER BY c.cron_adi ASC"
            );
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {
            error_log('SistemHataModel::cronOzetleri: ' . $e->getMessage());
            return [];
        }
    }

    public function basarisizOnaylar(int $limit = 200): array
    {
        $limit = max(1, min(1000, $limit));

        try {
            $stmt = $this->db->prepare(
                "SELECT r.id, r.MEDULARAPORID, r.TCKIMLIKNO, r.SIGORTALIADSOYAD,
                        r.POLIKLINIKTAR, r.ISBASKONTTAR, r.onay_turu, r.onay_durumu,
                        r.onay_tarihi, r.created_at, r.hata_mesaji,
                        i.firma_adi
                 FROM onaylanan_raporlar r
                 LEFT JOIN kullanici_isyerleri i ON i.id = r.isyeri_id
                 WHERE r.onay_durumu = 'basarisiz'
                 ORDER BY r.created_at DESC
                 LIMIT {$limit}"
            );
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {
            error_log('SistemHataModel::basarisizOnaylar: ' . $e->getMessage());
            return [];
        }
    }

    public function sonBasariliOtomatikOnay()
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT MAX(created_at) AS son
                 FROM onaylanan_raporlar
                 WHERE onay_turu = 'Otomatik Onay' AND onay_durumu = 'basarili'"
            );
            $stmt->execute();
            $satir = $stmt->fetch(PDO::FETCH_OBJ);

            return $satir->son ?? null;
        } catch (Throwable $e) {
            error_log('SistemHataModel::sonBasariliOtomatikOnay: ' . $e->getMessage());
            return null;
        }
    }

    public function dosyaHatalari(int $satirLimit = 300): array
    {
        $kaynaklar = [];

        foreach ($this->hataDosyalari() as $etiket => $yol) {
            if (!is_file($yol) || !is_readable($yol)) {
                continue;
            }

            $satirlar = $this->sonSatirlar($yol, $satirLimit);
            if ($satirlar === []) {
                continue;
            }

            $kaynaklar[] = (object)[
                'etiket' => $etiket,
                'yol' => str_replace($this->projeKok . DIRECTORY_SEPARATOR, '', $yol),
                'boyut' => filesize($yol),
                'degisme' => filemtime($yol),
                'satirlar' => $satirlar,
            ];
        }

        usort($kaynaklar, static fn($a, $b) => $b->degisme <=> $a->degisme);

        return $kaynaklar;
    }

    private function hataDosyalari(): array
    {
        $dosyalar = [];

        $sabitler = [
            'PHP Hata Günlüğü' => $this->projeKok . '/logs/php_error.log',
            'Otomatik Onay Cron (PHP Hata)' => $this->projeKok . '/Core/Crons/cron_otomatik_onay_error.log',
            'Bakım Modu Hataları' => $this->projeKok . '/logs/maintenance_error.log',
        ];

        foreach ($sabitler as $etiket => $yol) {
            if (is_file($yol)) {
                $dosyalar[$etiket] = $yol;
            }
        }

        $cronKok = $this->projeKok . '/logs/crons';
        if (is_dir($cronKok)) {
            foreach (glob($cronKok . '/*', GLOB_ONLYDIR) ?: [] as $kanalDizini) {
                $gunlukler = glob($kanalDizini . '/*.log') ?: [];
                if ($gunlukler === []) {
                    continue;
                }

                usort($gunlukler, static fn($a, $b) => filemtime($b) <=> filemtime($a));
                $etiket = 'Cron: ' . str_replace('_', ' ', basename($kanalDizini));
                $dosyalar[$etiket] = $gunlukler[0];
            }
        }

        return $dosyalar;
    }

    private function sonSatirlar(string $yol, int $limit): array
    {
        $handle = @fopen($yol, 'rb');
        if ($handle === false) {
            return [];
        }

        $tampon = [];
        while (($satir = fgets($handle)) !== false) {
            $satir = rtrim($satir, "\r\n");
            if (trim($satir) === '') {
                continue;
            }

            $tampon[] = $satir;
            if (count($tampon) > $limit) {
                array_shift($tampon);
            }
        }
        fclose($handle);

        $sonuc = [];
        foreach (array_reverse($tampon) as $satir) {
            $sonuc[] = (object)[
                'seviye' => $this->satirSeviyesi($satir),
                'metin' => mb_convert_encoding($satir, 'UTF-8', 'UTF-8'),
            ];
        }

        return $sonuc;
    }

    private function satirSeviyesi(string $satir): string
    {
        $buyuk = mb_strtoupper($satir, 'UTF-8');

        if (str_contains($buyuk, '[ERROR]') || str_contains($buyuk, 'FATAL') || str_contains($buyuk, 'PHP FATAL')
            || str_contains($buyuk, 'HATA:') || str_contains($buyuk, 'BAŞARISIZ') || str_contains($buyuk, 'KRİTİK')) {
            return 'ERROR';
        }

        if (str_contains($buyuk, '[WARNING]') || str_contains($buyuk, 'PHP WARNING') || str_contains($buyuk, 'DEPRECATED')
            || str_contains($buyuk, 'ATLANDI')) {
            return 'WARNING';
        }

        return 'INFO';
    }
}
