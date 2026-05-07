<?php


class Authorize 
{

     /**
     * Aktif PDO veritabanı bağlantısını tutar.
     * @var PDO
     */
    private PDO $db;

   
    protected $table = "userauths";
    public function getAuth($authname = null)
    {
        $role_id = $_SESSION["user_role"];
        if ($authname != null) {
            $query = $this->db->prepare("SELECT * FROM authority WHERE authName = ?");
            $query->execute([$authname]);
            $auth_id = $query->fetch(PDO::FETCH_OBJ);
            if ($auth_id == null) {
                echo "<script type='text/javascript'>window.location.href = 'index.php?p=authorize';</script>";
                exit;
            }
        }
        $query = $this->db->prepare("SELECT * FROM $this->table WHERE roleID = ? AND authID = ?");
        $query->execute([$role_id, $auth_id->id]);
        $auths = $query->fetch(PDO::FETCH_OBJ);
        if ($auths == null) {
            echo "<script type='text/javascript'>window.location.href = 'index.php?p=authorize';</script>";
            exit;
        }
        return;
    }
}
