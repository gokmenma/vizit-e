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

}


?>