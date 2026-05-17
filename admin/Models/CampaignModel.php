<?php

namespace Admin\Models;

use PDO;

class CampaignModel extends Model {

    public function __construct() {
        parent::__construct('campaigns');
    }

    /**
     * Get email logs for a specific campaign
     * @param int $id
     * @return array
     */
    public function getCampaignLogs($id) {
        $stmt = $this->db->prepare("SELECT * FROM campaign_logs WHERE campaign_id = :id ORDER BY sent_at DESC");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
