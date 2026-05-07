<?php

namespace Models;

use PDO;


class RaporBildirimLogModel extends Model
{
    protected $table = 'rapor_bildirim_loglari';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    // RaporBildirimLogModel.php içinde örnek bir kontrol metodu
    public function isReportNotifiedToday($medulaRaporId, $kullaniciId)
    {
        $today = date('Y-m-d');
        $stmt = $this->db->prepare("SELECT id FROM rapor_bildirim_loglari WHERE medula_rapor_id = ? AND kullanici_id = ? AND bildirim_tarihi = ?");
        $stmt->execute([$medulaRaporId, $kullaniciId, $today]);
        return $stmt->fetch() !== false;
    }
}
