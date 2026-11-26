-- Kullanici isyerleri tablosundaki aktif_mi sutununu BIT(1) tipinden TINYINT(1) tipine cevirme
-- PDO parametre baglama (prepared statement) sirasinda olusan 'String data, right truncated' (1406) hatasini onler.

ALTER TABLE `kullanici_isyerleri`
    MODIFY COLUMN `aktif_mi` TINYINT(1) NOT NULL DEFAULT 1;
