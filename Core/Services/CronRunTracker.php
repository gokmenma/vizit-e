<?php

namespace Core\Services;

use Models\CronCalismaModel;
use Throwable;

/**
 * Cron dosyalarinin calisip calismadigini merkezi tabloda izler.
 *
 * exit/die ve beklenmeyen fatal hatalarda da shutdown callback calistigi icin
 * cron_calismalari kaydi yarim kalmaz.
 */
class CronRunTracker
{
    private $model;
    private $id = 0;
    private $cronAdi;
    private $reporter;
    private $tamamlandi = false;
    private $sayaclar = [
        'isyeri_sayisi' => 0,
        'onaylanan' => 0,
        'basarisiz' => 0,
        'atlanan' => 0,
    ];

    public function __construct(string $cronAdi, ?CronReporter $reporter = null)
    {
        $this->cronAdi = $cronAdi;
        $this->reporter = $reporter;

        try {
            $this->model = new CronCalismaModel();
            $this->id = $this->model->baslat($cronAdi);
        } catch (Throwable $e) {
            error_log("{$cronAdi} cron çalışma kaydı oluşturulamadı: " . $e->getMessage());
        }

        register_shutdown_function([$this, 'shutdown']);
    }

    public function sayacEkle(string $alan, int $miktar = 1): void
    {
        if (array_key_exists($alan, $this->sayaclar)) {
            $this->sayaclar[$alan] += $miktar;
        }
    }

    public function hata(Throwable $e): void
    {
        if ($this->tamamlandi || !$this->model || $this->id <= 0) {
            return;
        }

        try {
            $this->model->hataylaBitir(
                $this->id,
                $e->getMessage(),
                $this->sayaclar,
                $this->ozet()
            );
            $this->tamamlandi = true;
        } catch (Throwable $kayitHatasi) {
            error_log("{$this->cronAdi} cron hata kaydı güncellenemedi: " . $kayitHatasi->getMessage());
        }
    }

    public function shutdown(): void
    {
        if ($this->tamamlandi || !$this->model || $this->id <= 0) {
            return;
        }

        $sonHata = error_get_last();
        $fatalTipler = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

        try {
            if ($sonHata && in_array($sonHata['type'], $fatalTipler, true)) {
                $mesaj = $sonHata['message'] . ' (' . $sonHata['file'] . ':' . $sonHata['line'] . ')';
                $this->model->hataylaBitir($this->id, $mesaj, $this->sayaclar, $this->ozet());
            } else {
                $this->model->tamamla($this->id, $this->sayaclar, $this->ozet());
            }
            $this->tamamlandi = true;
        } catch (Throwable $e) {
            error_log("{$this->cronAdi} cron çalışma kaydı tamamlanamadı: " . $e->getMessage());
        }
    }

    private function ozet(): ?string
    {
        if (!$this->reporter) {
            return null;
        }

        $ozet = trim($this->reporter->getOutput());
        return $ozet === '' ? null : $ozet;
    }
}
