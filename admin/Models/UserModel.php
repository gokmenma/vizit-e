<?php

namespace Admin\Models;

use PDO;

class UserModel extends Model {

    public function __construct() {
        parent::__construct('kullanicilar');
    }

    /**
     * Get all alt-users (sub-users) linked to their primary admin
     * @return array
     */
    public function getSubUsers() {
        $stmt = $this->db->prepare("SELECT u.*, 
                                           COALESCE(NULLIF(u.adi_soyadi, '0'), u.kullanici_adi) as display_name, 
                                           COALESCE(NULLIF(p.adi_soyadi, '0'), p.kullanici_adi) as admin_name,
                                           p.adi_soyadi as admin_ad,
                                           p.kullanici_adi as admin_username,
                                           COALESCE(NULLIF(e.adi_soyadi, '0'), e.kullanici_adi) as ekleyen_ad,
                                           e.kullanici_adi as ekleyen_username

                                    FROM {$this->table} u 
                                    LEFT JOIN {$this->table} p ON u.admin_id = p.id 
                                     LEFT JOIN {$this->table} e ON u.ekleyen_id = e.id 
                                    WHERE u.admin_id != 0 AND (u.silinme_tarihi IS NULL OR u.silinme_tarihi = '')
                                    ORDER BY u.id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get basic info for an admin
     * @param int $id
     * @return object|false
     */
    public function getAdminDetails($id) {
        $stmt = $this->db->prepare("SELECT id, adi_soyadi, kullanici_adi FROM {$this->table} WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Get all main users (admin_id = 0) for the modal dropdown
     * @return array
     */
    public function getMainUsers() {
        $stmt = $this->db->prepare("SELECT id, adi_soyadi, kullanici_adi, email 
                                    FROM {$this->table} 
                                    WHERE admin_id = 0 AND (silinme_tarihi IS NULL OR silinme_tarihi = '') 
                                    ORDER BY adi_soyadi ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Check if username or email already exists for another user
     * @param string $username
     * @param string $email
     * @param int $excludeId
     * @return object|false
     */
    public function checkUserExists($username, $email, $excludeId = 0) {
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE (kullanici_adi = :username OR email = :email) AND id != :excludeId AND (silinme_tarihi IS NULL OR silinme_tarihi = '')");
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':excludeId' => $excludeId
        ]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Save or update a sub-user (alt kullanıcı) account
     * @param array $data
     * @return mixed Last inserted ID or execute status
     */
    public function saveAltUser($data) {
        $id = $data['id'] ?? 0;
        $attributes = [
            'adi_soyadi' => $data['adi_soyadi'] ?? '',
            'kullanici_adi' => $data['kullanici_adi'] ?? '',
            'email' => $data['email'] ?? '',
            'admin_id' => $data['admin_id'] ?? 0,
            'yetkiler' => $data['yetkiler'] ?? '',
        ];

        if ($id > 0) {
            $attributes['id'] = $id;
            if (!empty($data['sifre'])) {
                $attributes['sifre'] = password_hash($data['sifre'], PASSWORD_DEFAULT);
            }
        } else {
            $attributes['sifre'] = password_hash($data['sifre'], PASSWORD_DEFAULT);
            $attributes['role'] = 'user';
            $attributes['ekleyen_id'] = $data['ekleyen_id'] ?? 0;
            $attributes['kayit_tarihi'] = date('Y-m-d H:i:s');
            $attributes['durum'] = 'Aktif';
        }

        return $this->saveWithAttr($attributes);
    }

    /**
     * Update an administrator's profile details
     * @param array $data
     * @return bool
     */
    public function updateProfile($data) {
        $userId = $data['user_id'] ?? 0;
        $attributes = [
            'id' => $userId,
            'adi_soyadi' => $data['adi_soyadi'] ?? '',
            'kullanici_adi' => $data['kullanici_adi'] ?? '',
            'email' => $data['email'] ?? '',
        ];

        if (!empty($data['new_password'])) {
            $attributes['sifre'] = password_hash($data['new_password'], PASSWORD_DEFAULT);
        }

        return $this->saveWithAttr($attributes);
    }
}
