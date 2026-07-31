ALTER TABLE kullanici_isyerleri
    ADD COLUMN mahsuplasma_protokolu TINYINT(1) NOT NULL DEFAULT 0
    AFTER otomatik_rapor_onay;
