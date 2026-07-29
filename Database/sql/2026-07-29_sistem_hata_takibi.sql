-- 2026-07-29 Sistem hata takibi
-- 1) Otomatik/manuel onay denemelerinde SGK'dan donen hata metnini kalici olarak sakla
-- 2) Cron calismalarini kayit altina alarak "cron calisti mi" sorusunu cevaplanabilir yap

ALTER TABLE `onaylanan_raporlar`
    ADD COLUMN `hata_mesaji` TEXT NULL DEFAULT NULL AFTER `onay_durumu`;

CREATE TABLE IF NOT EXISTS `cron_calismalari` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `cron_adi` VARCHAR(64) NOT NULL,
    `baslangic` DATETIME NOT NULL,
    `bitis` DATETIME NULL DEFAULT NULL,
    `durum` ENUM('calisiyor','tamamlandi','hata') NOT NULL DEFAULT 'calisiyor',
    `isyeri_sayisi` INT(11) NOT NULL DEFAULT 0,
    `onaylanan` INT(11) NOT NULL DEFAULT 0,
    `basarisiz` INT(11) NOT NULL DEFAULT 0,
    `atlanan` INT(11) NOT NULL DEFAULT 0,
    `hata_mesaji` TEXT NULL DEFAULT NULL,
    `ozet` MEDIUMTEXT NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_cron_adi_baslangic` (`cron_adi`, `baslangic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
