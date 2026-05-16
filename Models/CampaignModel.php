<?php

namespace Models;

use PDO;

class CampaignModel extends Model
{
    protected $table = 'campaigns';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function getCampaigns()
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getCampaign($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function createCampaign($data)
    {
        $sql = "INSERT INTO {$this->table} (title, content, criteria, status) 
                VALUES (:title, :content, :criteria, :status)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':title' => $data['title'],
            ':content' => $data['content'],
            ':criteria' => json_encode($data['criteria']),
            ':status' => 'draft'
        ]);
        return $this->db->lastInsertId();
    }

    public function updateCampaign($id, $data)
    {
        $sql = "UPDATE {$this->table} 
                SET title = :title, content = :content, criteria = :criteria, status = :status 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':content' => $data['content'],
            ':criteria' => json_encode($data['criteria']),
            ':status' => $data['status'] ?? 'draft'
        ]);
    }

    public function deleteCampaign($id)
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM campaign_logs WHERE campaign_id = :id");
            $stmt->execute([':id' => $id]);

            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
            $stmt->execute([':id' => $id]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getFilteredUsers($criteria)
    {
        if (!empty($criteria['user_ids'])) {
            $ids = is_array($criteria['user_ids']) ? $criteria['user_ids'] : explode(',', $criteria['user_ids']);
            if (!empty($ids)) {
                $placeholders = rtrim(str_repeat('?, ', count($ids)), ', ');
                $stmt = $this->db->prepare("SELECT id, email, COALESCE(NULLIF(adi_soyadi, ''), kullanici_adi) as name FROM kullanicilar WHERE id IN ($placeholders) AND (silinme_tarihi IS NULL OR silinme_tarihi = '')");
                $stmt->execute($ids);
                return $stmt->fetchAll(PDO::FETCH_OBJ);
            }
        }

        $query = "SELECT k.id, k.email, COALESCE(NULLIF(k.adi_soyadi, ''), k.kullanici_adi) as name 
                  FROM kullanicilar k 
                  LEFT JOIN kullanici_abonelik a ON k.id = a.kullanici_id 
                  WHERE (k.silinme_tarihi IS NULL OR k.silinme_tarihi = '')";
        
        $params = [];
        if (!empty($criteria['status'])) {
            $query .= " AND k.durum = :status";
            $params[':status'] = $criteria['status'];
        }
        if (!empty($criteria['paket_id'])) {
            $query .= " AND a.paket_id = :paket_id";
            $params[':paket_id'] = $criteria['paket_id'];
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function clearLogs($campaignId)
    {
        // Bekleyen (pending) logları siliyoruz ki yeni seçim gelsin. 
        // Gönderilmiş olanlar (sent/failed) geçmişte kalmaya devam eder.
        $stmt = $this->db->prepare("DELETE FROM campaign_logs WHERE campaign_id = :id AND status = 'pending'");
        $stmt->execute([':id' => $campaignId]);
        
        $stmt = $this->db->prepare("UPDATE {$this->table} SET total_recipients = 0, sent_count = 0, failed_count = 0 WHERE id = :id");
        $stmt->execute([':id' => $campaignId]);
    }

    public function createLogs($campaignId, $users)
    {
        $sql = "INSERT INTO campaign_logs (campaign_id, user_id, name, email, status) VALUES (:campaign_id, :user_id, :name, :email, 'pending')";
        $stmt = $this->db->prepare($sql);
        foreach ($users as $user) {
            $stmt->execute([
                ':campaign_id' => $campaignId,
                ':user_id' => $user->id,
                ':name' => $user->name,
                ':email' => $user->email
            ]);
        }

        $stmt = $this->db->prepare("UPDATE {$this->table} SET total_recipients = :total WHERE id = :id");
        $stmt->execute([':total' => count($users), ':id' => $campaignId]);
    }

    public function getPendingLogs($campaignId, $limit = 50)
    {
        $stmt = $this->db->prepare("SELECT * FROM campaign_logs WHERE campaign_id = :campaign_id AND status = 'pending' LIMIT :limit");
        $stmt->bindValue(':campaign_id', $campaignId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function updateLogStatus($logId, $status, $error = null)
    {
        $sql = "UPDATE campaign_logs SET status = :status, error_message = :error, sent_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $logId,
            ':status' => $status,
            ':error' => $error
        ]);

        // Campaign stats update
        if ($status === 'sent') {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET sent_count = sent_count + 1 WHERE id = (SELECT campaign_id FROM campaign_logs WHERE id = :id)");
            $stmt->execute([':id' => $logId]);
        } else {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET failed_count = failed_count + 1 WHERE id = (SELECT campaign_id FROM campaign_logs WHERE id = :id)");
            $stmt->execute([':id' => $logId]);
        }
    }
    
    public function finishCampaign($campaignId) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = 'completed', completed_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $campaignId]);
    }
}
