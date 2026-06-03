<?php


namespace Models;

use App\Helper\Security;
use PDO;

use Database\Db;


class Model
{
    protected $table;
    protected $primaryKey = 'id';
    protected $attributes = [];
    protected $isNew = true;

    protected $db;


    public function __construct($table = null)
    {
        $this->table = $table ?: $this->getTableName();
        $config = require __DIR__ . "/../config.php";
        $this->db = Db::getInstance($config)->getConnection();
    }

    protected function getTableName()
    {
        $className = get_called_class();
        $parts = explode('\\', $className);
        $className = end($parts);
        return strtolower($className) . 's';
    }

    public function all()
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function find($id, $encrypt = false)
    {
        if ($encrypt) {
            $id = Security::decrypt($id);
        }

        if (!$id) {
            return false;
        }

        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE $this->primaryKey = ?");
        $sql->execute(array($id));
        return $sql->fetch(PDO::FETCH_OBJ) ?? false;
    }

    /** Finds records where a specific column matches a value.
     * @param string $column The column to search in.
     * @param mixed $value The value to match against the column.
     */
    public function where($column, $value, $sorting = "id asc")
    {
        if (empty($value)) {
            return [];
        }

        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE $column = ? ORDER BY $sorting");
        $sql->execute(array($value));
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }


    /**
     * Summary of whereRaw
     * @param string $condition
     * @param array $params
     * @param mixed $sorting
     * @return array
     */
    public function whereRaw(string $condition, array $params = [], $sorting = "id asc")
{
    if (empty($condition)) {
        return [];
    }

    $sql = $this->db->prepare("SELECT * FROM $this->table WHERE $condition ORDER BY $sorting");
    $sql->execute($params);

    return $sql->fetchAll(PDO::FETCH_OBJ);
}



    /** Finds records where a specific column matches a value.
     * @param string $column The column to search in.
     * @param mixed $value The value to match against the column.
     */
    public function findWhereIn($column, $values, $sorting = "column asc")
    {
        if (empty($values)) {
            return [];
        }

        $placeholders = rtrim(str_repeat('?, ', count($values)), ', ');
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE $column IN ($placeholders) ORDER BY $sorting");
        $sql->execute($values);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }




    public function save()
    {
        if ($this->isNew) {
            return $this->insert();
        } else {
            $this->update();
        }
    }

    public function saveWithAttr($data)
    {
        $this->attributes = $data;
        if (isset($data['id']) && $data['id'] > 0) {
            $this->update();
        } else {
            return $this->insert();
        }
    }

    protected function insert()
    {
        $columns = implode(', ', array_keys($this->attributes));
        $values = ':' . implode(', :', array_keys($this->attributes));
        $sql = $this->db->prepare("INSERT INTO $this->table ($columns) VALUES ($values)");

        foreach ($this->attributes as $key => $value) {
            $sql->bindValue(":$key", $value);
        }

        $sql->execute();

        $this->isNew = false;
        $this->attributes[$this->primaryKey] = $this->db->lastInsertId();

        return Security::encrypt($this->attributes[$this->primaryKey]);
    }

    protected function update(): void
    {
        $setClause = '';

        if ($this->find($this->attributes[$this->primaryKey]) === false) {
            throw new \Exception('Kayıt bulunamadı.' . $this->attributes[$this->primaryKey]);
        }

        foreach ($this->attributes as $key => $value) {
            $setClause .= "$key = :$key, ";
        }
        $setClause = rtrim($setClause, ', ');

        $sql = $this->db->prepare("UPDATE $this->table SET $setClause WHERE $this->primaryKey = :$this->primaryKey");

        $sql->bindParam(":$this->primaryKey", $this->attributes[$this->primaryKey], PDO::PARAM_INT);

        foreach ($this->attributes as $key => $value) {
            $sql->bindValue(":$key", $value);
        }

        $sql->execute();


        // if ($sql->rowCount() === 0) {
        //     throw new Exception("Kayıt güncellenemedi.");
        // }
    }

    /**
     * Bir kaydı ID'sine göre günceller.
     * Bu metod, üst sınıftaki (parent) `update` metodunu override eder.
     * Güncellemeden önce kaydın varlığını kontrol eder ve verileri hazırlar.
     *
     * @param int|string $id Güncellenecek kaydın şifrelenmiş veya normal ID'si
     * @param array $data Güncellenecek verileri içeren anahtar-değer dizisi
     * @return bool Güncelleme işleminin sonucu (genellikle true/false)
     * @throws \Exception Kayıt bulunamazsa veya güncelleme başarısız olursa
     */
    public function updateSingle($id, $data)
    {
        // 1. ID'yi deşifre et (Eğer şifreli geliyorsa)
        // $decryptedId = Security::decrypt($id);

        // 2. Güncelleme öncesi kaydın varlığını kontrol et
        // Bu, gereksiz veritabanı sorgularını önler ve hata yönetimini iyileştirir.
        // find() metodunun zaten modelin niteliklerini ($this->attributes) doldurduğunu varsayıyoruz.
        $record = $this->find($id);
        if ($record === false) {
            // Kayıt bulunamadıysa, bir istisna fırlatarak işlemi durdur.
            throw new \Exception("Güncellenmek istenen kayıt bulunamadı. ID: " . $id);
        }

        // 3. Modelin niteliklerini (attributes) yeni güncelleme verileriyle birleştir/ayarla
        // Gelen veriyi mevcut niteliklerin üzerine yazıyoruz.
        $this->attributes = array_merge($this->attributes, $data);

        // Birincil anahtarın doğru ayarlandığından emin olalım.
        // find() bunu zaten yapmış olmalı, ama bu bir güvencedir.
        $this->attributes[$this->primaryKey] = $id;

        // 4. Üst sınıfın orijinal update metodunu çağırarak asıl veritabanı işlemini gerçekleştir
        // DİKKAT: $this->update() yerine parent::update() kullanılmalıdır!
        // parent::update() metodu, $this->attributes dizisindeki verileri kullanarak
        // "UPDATE tablo SET ... WHERE id=..." sorgusunu çalıştıracaktır.
        $this->update();
        return true;
    }

    public function reload()
    {
        if (!$this->isNew) {
            $sql = $this->db->prepare("SELECT * FROM $this->table WHERE $this->primaryKey = ?");
            $sql->execute(array($this->attributes[$this->primaryKey]));
            $data = $sql->fetch(PDO::FETCH_OBJ);
        }
    }

    public function delete($id)
    {

        $id = Security::decrypt($id);
        $sql = $this->db->prepare("DELETE FROM $this->table WHERE $this->primaryKey = ?");
        $sql->execute(array($id));

        if ($sql->rowCount() === 0) {
            return new \Exception('Kayıt bulunamadı veya silinemedi.');
        }
        return true;
    }

    /**Kolona göre silme işlemi
     * @param string $column Silinecek kaydın hangi kolona göre silineceği
     * @param mixed $value Silinecek kaydın değeri
     * @return bool|Exception
     */
    public function deleteByColumn($column, $value)
    {
        $sql = $this->db->prepare("DELETE FROM $this->table WHERE $column = ?");
        $sql->execute(array($value));

        if ($sql->rowCount() === 0) {
            return new \Exception('Kayıt bulunamadı veya silinemedi.');
        }
        return true;
    }

    //Soft delete
    public function softDelete($id, $silen_kullanici_id = null)
    {
        //$id = Security::decrypt($id);
        $sql = $this->db->prepare("UPDATE $this->table SET silinme_tarihi = NOW() , silen_kullanici = ?  WHERE $this->primaryKey = ? ");
        $sql->execute(array( $silen_kullanici_id, $id));

        if ($sql->rowCount() === 0) {
            return new \Exception('Kayıt bulunamadı veya silinemedi.');
        }
        return true;
    }

    /**Kolona göre Soft delete işlemi
     * @param string $column Silinecek kaydın hangi kolona göre silineceği
     * @param mixed $value Silinecek kaydın değeri
     * @param int $silen_kullanici_id  Silen kullanıcının ID'si
     *  @return bool|Exception
     */
    public function softDeleteByColumn($column, $value, $silen_kullanici_id = null)
    {

        $sql = $this->db->prepare("UPDATE $this->table SET silinme_tarihi = NOW(), silen_kullanici = ? WHERE $column = ?");
        $sql->execute(array($silen_kullanici_id, $value));

        if ($sql->rowCount() === 0) {
            return new \Exception('Kayıt bulunamadı veya silinemedi.');
        }
        return true;
    }


    

    //     return true;
    // }
    public function backupDelete($id, $table, $primaryKey = 'id')
    {
        $id = Security::decrypt($id);
        $backupTable = 'silinen_' . $table;
        // 1. Kaydı al
        $stmt = $this->db->prepare("SELECT * FROM $table WHERE $primaryKey = ? LIMIT 1");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return new \Exception('Kayıt bulunamadı.');
        }
        // 2. aktif_mi ve kullanim_durumu varsa sıfırla
        if (array_key_exists('aktif_mi', $data)) {
            $data['aktif_mi'] = 0;
        }
        if (array_key_exists('kullanim_durumu', $data)) {
            $data['kullanim_durumu'] = 0;
        }
        // 3. silinme_tarihi yoksa ekle
        if (!array_key_exists('silinme_tarihi', $data)) {
            $data['silinme_tarihi'] = date('Y-m-d H:i:s');
        }
        // 4. Alanları ve değerleri hazırla
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ':' . $col, $columns);
        // 5. Sorguyu hazırla
        $sql = "INSERT INTO $backupTable (" . implode(', ', $columns) . ")
            VALUES (" . implode(', ', $placeholders) . ")";
        $insertStmt = $this->db->prepare($sql);

        // 6. Bind işlemi
        foreach ($data as $key => $value) {
            $insertStmt->bindValue(':' . $key, $value);
        }

        $insertStmt->execute();

        // 7. Orijinal kaydı sil
        $deleteStmt = $this->db->prepare("DELETE FROM $table WHERE $primaryKey = ?");
        $deleteStmt->execute([$id]);

        if ($deleteStmt->rowCount() === 0) {
            return new \Exception('Kayıt silinemedi.');
        }

        return true;
    }
}