<?php

namespace Models;

use Override;
use PDO;


class UserModel extends Model
{
    protected $table = 'kullanicilar';

    public function __construct()
    {
        parent::__construct($this->table);
    }
    /**
     * Kullanıcıyı kullanıcı adına ve şifreye göre login .
     *
     * @param string $kullanici_adi
     * @param string $password
     * @return object|false
     */
    public function login($kullanici_adi)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} 
                                            WHERE kullanici_adi = :kullanici_adi
                                            AND 
                                            (silinme_tarihi IS NULL OR silinme_tarihi = '' )");

        $stmt->bindParam(':kullanici_adi', $kullanici_adi);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Admin login
     * @param string $kullanici_adi
     * @return object|false
     */
    public function adminLogin($kullanici_adi)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} 
                                            WHERE kullanici_adi = :kullanici_adi
                                            AND 
                                            (silinme_tarihi IS NULL OR silinme_tarihi = '' )");

        $stmt->bindParam(':kullanici_adi', $kullanici_adi);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Kullanıcıyı kullanıcı adına göre bulur.
     *
     * @param string $kullanici_adi
     * @return object|false
     */
    public function findByUserName($kullanici_adi)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} 
                                            WHERE kullanici_adi = :kullanici_adi
                                            AND (silinme_tarihi IS NULL OR silinme_tarihi = '' )");
        $stmt->bindParam(':kullanici_adi', $kullanici_adi);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Kullanıcıyı ID'ye göre bulur.
     *
     * @param int $id
     * @return object|false
     */
    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} 
                                            WHERE id = :id
                                            ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /* * Kullanıcıyı email adresine göre bulur.
     *
     * @param string $email
     * @return object|false
     */
    public function findByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} 
                                            WHERE email = :email
                                            AND (silinme_tarihi IS NULL OR silinme_tarihi = '' )");

        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Kullanıcının email adresinden başka aynı email adresi olan bir kullanıcı olup olmadığını kontrol eder.
     */
    public function checkEmailExists($email, $userId = 0)
    {
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} 
                                            WHERE email = :email
                                            AND id != :userId
                                            AND (silinme_tarihi IS NULL OR silinme_tarihi = '' )");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        return $stmt->fetchColumn();
    }


    /**
     * Kullanıcıyı siler.
     * @param int $id
     * @return bool
     */

    public function softDeleteUser($id)
    {
        $silinmeTarihi = date('Y-m-d H:i:s');
        
        try {
            $this->db->beginTransaction();

            // 1. Ana kullanıcıyı sil
            $sql = "UPDATE $this->table 
                    SET silinme_tarihi = :silinme_tarihi 
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':silinme_tarihi' => $silinmeTarihi
            ]);

            // 2. Alt kullanıcıları sil
            $sqlAlt = "UPDATE $this->table 
                       SET silinme_tarihi = :silinme_tarihi 
                       WHERE admin_id = :id";
            $stmtAlt = $this->db->prepare($sqlAlt);
            $stmtAlt->execute([
                ':id' => $id,
                ':silinme_tarihi' => $silinmeTarihi
            ]);

            // 3. İşyerlerini sil
            $sqlIsyeri = "UPDATE kullanici_isyerleri 
                          SET silinme_tarihi = :silinme_tarihi 
                          WHERE kullanici_id = :id";
            $stmtIsyeri = $this->db->prepare($sqlIsyeri);
            $stmtIsyeri->execute([
                ':id' => $id,
                ':silinme_tarihi' => $silinmeTarihi
            ]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**Kullanıcının giriş kayıtlarını getirir.
     * @param int $userId
     * @return array
     */
    public function getLoginRecords($userId, $limit)

    {
        $stmt = $this->db->prepare("SELECT * FROM logs 
                                            WHERE user_id = :userId 
                                            AND channel = :channel 
                                            ORDER BY created_at DESC
                                            LIMIT :limit");
        $stmt->bindParam(':userId', $userId);
        $stmt->bindValue(':channel', 'sign-in');
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }


    /** Aktif kullanıcıları getirir
     * @return array
     */
    public function AktifKullanicilar()
    {
        $stmt = $this->db->prepare("SELECT 
                                        k.*, 
                                        ap.ad as paket_adi,
                                        ka.paket_id as current_paket_id,
                                        ka.baslangic_tarihi,
                                        ka.bitis_tarihi,
                                        (SELECT COUNT(*) FROM kullanicilar k2 WHERE k2.admin_id = k.id AND (k2.silinme_tarihi IS NULL OR k2.silinme_tarihi = '')) as alt_kullanici_sayisi
                                    FROM {$this->table} k 
                                    LEFT JOIN (
                                        SELECT ka1.* FROM kullanici_abonelikleri ka1
                                        INNER JOIN (
                                            SELECT kullanici_id, MAX(id) as max_id 
                                            FROM kullanici_abonelikleri 
                                            WHERE durum = 'aktif' 
                                            GROUP BY kullanici_id
                                        ) ka2 ON ka1.id = ka2.max_id
                                    ) ka ON ka.kullanici_id = k.id
                                    LEFT JOIN abonelik_paketleri ap ON ap.id = ka.paket_id
                                    WHERE k.admin_id = ?  
                                    AND (k.silinme_tarihi IS NULL OR k.silinme_tarihi = '' )");
        $stmt->execute([0]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }


    /**Aktif kullanıcılar (Alt Kullanıcılar dahil) */
    public function AktifKullanicilarAltKullanici()
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} 
                                            WHERE (silinme_tarihi IS NULL OR silinme_tarihi = '' )");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }



/** Alt kullanıcıları getirir
 * @param int $adminId
 * @return array
 */
public function AltKullanicilar($adminId)
{
    $stmt = $this->db->prepare("SELECT k.*, 
                                       GROUP_CONCAT(ki.firma_adi SEPARATOR ', ') as firma_adi
                                FROM {$this->table} k
                                LEFT JOIN kullanici_isyerleri ki ON FIND_IN_SET(ki.id, k.yetkili_oldugu_isyeri_ids) > 0
                                                                  AND ki.kullanici_id = :admin_id
                                                                  AND (ki.silinme_tarihi IS NULL OR ki.silinme_tarihi = '')
                                WHERE (k.silinme_tarihi IS NULL OR k.silinme_tarihi = '')
                                      AND k.admin_id = :admin_id
                                GROUP BY k.id");
    $stmt->bindParam(':admin_id', $adminId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}


    /** Alt kullanıcı sayısını getirir
     * @param int $adminId
     * @return int
     */
    public function AltKullaniciSayisi($adminId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->table} 
                                            WHERE (silinme_tarihi IS NULL OR silinme_tarihi = '' )
                                            AND admin_id = :admin_id");
        $stmt->bindParam(':admin_id', $adminId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result ? (int)$result->count : 0;
    }



    /**
     * Verilen token hash'ine göre kullanıcıyı bulur.
     * @param string $tokenHash
     * @return object|false
     */
    public function findByResetToken($tokenHash)
    {
        $sql = "SELECT * FROM $this->table WHERE reset_token = :token_hash";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':token_hash' => $tokenHash]);
        return $stmt->fetch(PDO::FETCH_OBJ); // fetch() PDO::FETCH_OBJ varsayılanıyla nesne döner
    }

    /**
     * Kullanıcının şifresini günceller ve sıfırlama token'larını temizler.
     * @param int $userId
     * @param string $newPasswordHash
     * @return bool
     */
    public function updatePasswordAndClearToken($userId, $newPasswordHash)
    {
        $sql = "UPDATE $this->table SET 
                sifre = :new_password_hash, 
                reset_token = NULL, 
                token_expiry = NULL 
            WHERE id = :user_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':new_password_hash' => $newPasswordHash,
            ':user_id' => $userId
        ]);
        return $stmt->rowCount() > 0;
    }



    /**
     * Referral koduna göre kullanıcıyı bulur.
     * @param string $referralCode
     * @return object|false
     */
    public function getUserByReferralCode($referralCode)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} 
                                            WHERE referral_code = :referral_code
                                            AND (silinme_tarihi IS NULL OR silinme_tarihi = '' )");
        $stmt->bindParam(':referral_code', $referralCode);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Admin paneli dashboard istatistiklerini getirir
     * @return array
     */
    public function getAdminDashboardStats()
    {
        // 1. Toplam Gelir (Aktif aboneliklerin toplam paket fiyatı)
        $stmt = $this->db->prepare("SELECT SUM(ap.fiyat) as total_revenue 
                                    FROM kullanici_abonelikleri ka
                                    JOIN abonelik_paketleri ap ON ka.paket_id = ap.id
                                    WHERE ka.durum = 'aktif'");
        $stmt->execute();
        $revenue = $stmt->fetch(PDO::FETCH_OBJ)->total_revenue ?? 0;

        // 2. Yeni Kullanıcılar (Son 30 gün - Sadece ana kullanıcılar)
        $stmt = $this->db->prepare("SELECT COUNT(*) as new_users 
                                    FROM {$this->table} 
                                    WHERE admin_id = 0 
                                    AND kayit_tarihi >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                                    AND (silinme_tarihi IS NULL OR silinme_tarihi = '')");
        $stmt->execute();
        $newUsers = $stmt->fetch(PDO::FETCH_OBJ)->new_users ?? 0;

        // 3. Aktif Kullanıcılar (Toplam - Sadece ana kullanıcılar)
        $stmt = $this->db->prepare("SELECT COUNT(*) as active_users 
                                    FROM {$this->table} 
                                    WHERE admin_id = 0 
                                    AND (silinme_tarihi IS NULL OR silinme_tarihi = '')");
        $stmt->execute();
        $activeUsers = $stmt->fetch(PDO::FETCH_OBJ)->active_users ?? 0;

        // 4. Geçen ayki yeni kullanıcılar (Büyüme oranı hesaplaması için)
        $stmt = $this->db->prepare("SELECT COUNT(*) as last_month_users 
                                    FROM {$this->table} 
                                    WHERE admin_id = 0 
                                    AND kayit_tarihi < DATE_SUB(NOW(), INTERVAL 30 DAY)
                                    AND kayit_tarihi >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                                    AND (silinme_tarihi IS NULL OR silinme_tarihi = '')");
        $stmt->execute();
        $lastMonthUsers = $stmt->fetch(PDO::FETCH_OBJ)->last_month_users ?? 0;

        $growthRate = 0;
        if ($lastMonthUsers > 0) {
            $growthRate = (($newUsers - $lastMonthUsers) / $lastMonthUsers) * 100;
        }

        // 5. Son Kayıt Olan 5 Kullanıcı
        $stmt = $this->db->prepare("SELECT k.*, ap.ad as paket_adi 
                                    FROM {$this->table} k
                                    LEFT JOIN (
                                        SELECT ka1.* FROM kullanici_abonelikleri ka1
                                        INNER JOIN (
                                            SELECT kullanici_id, MAX(id) as max_id 
                                            FROM kullanici_abonelikleri 
                                            WHERE durum = 'aktif' 
                                            GROUP BY kullanici_id
                                        ) ka2 ON ka1.id = ka2.max_id
                                    ) ka ON ka.kullanici_id = k.id
                                    LEFT JOIN abonelik_paketleri ap ON ap.id = ka.paket_id
                                    WHERE k.admin_id = 0
                                    AND (k.silinme_tarihi IS NULL OR k.silinme_tarihi = '')
                                    ORDER BY k.id DESC
                                    LIMIT 5");
        $stmt->execute();
        $recentUsers = $stmt->fetchAll(PDO::FETCH_OBJ);

        // 6. Son Aktiviteler (Kritik işlemler)
        $recentActivities = $this->getRecentActivities(5);

        return [
            'total_revenue' => $revenue,
            'new_users' => $newUsers,
            'active_users' => $activeUsers,
            'growth_rate' => round($growthRate, 1),
            'recent_users' => $recentUsers,
            'recent_activities' => $recentActivities
        ];
    }

    /**
     * Kritik sistem aktivitelerini getirir
     * @param int $limit
     * @return array
     */
    public function getRecentActivities($limit = 10)
    {
        $stmt = $this->db->prepare("SELECT l.*, k.adi_soyadi, k.kullanici_adi 
                                    FROM logs l
                                    LEFT JOIN kullanicilar k ON l.user_id = k.id
                                    WHERE l.channel IN ('auth', 'sign-in', 'admin-auth', 'user-management', 'workplace-management', 'subscription')
                                    ORDER BY l.created_at DESC
                                    LIMIT :limit");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Kullanıcının alt kullanıcı ekleme limitini getirir.
     * Öncelik: Son satın alma (aktif abonelik) -> Paket varsayılanı
     * @param int $userId
     * @return int
     */
    public function getAltKullaniciLimiti($userId)
    {
        $stmt = $this->db->prepare("SELECT ka.alt_kullanici_hakki, ap.alt_kullanici_hakki as paket_limiti
                                    FROM kullanici_abonelikleri ka
                                    LEFT JOIN abonelik_paketleri ap ON ka.paket_id = ap.id
                                    WHERE ka.kullanici_id = :userId AND ka.durum = 'aktif'
                                    ORDER BY ka.id DESC LIMIT 1");
        $stmt->execute([':userId' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        
        if ($result) {
            // Eğer satın alma işleminde özel bir hak tanımlanmışsa (0'dan büyükse) onu al, 
            // yoksa bağlı olduğu paketin varsayılan hakkını al
            if (isset($result->alt_kullanici_hakki) && $result->alt_kullanici_hakki > 0) {
                return (int)$result->alt_kullanici_hakki;
            }
            return (int)($result->paket_limiti ?? 3);
        }
        
        return 3; // Varsayılan fallback
    }
}
