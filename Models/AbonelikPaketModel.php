<?php 
namespace Models;

use PDO;


class AbonelikPaketModel extends Model
{
    protected $table = 'abonelik_paketleri';

    public function __construct()
    {
        parent::__construct($this->table);

    }

    public function all()
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE (silinme_tarihi IS NULL OR silinme_tarihi = '') AND aktif_mi = 1 ORDER BY fiyat ASC");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }
}


?>