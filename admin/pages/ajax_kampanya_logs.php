<?php
require_once __DIR__ . '/../../autoload.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
    exit();
}

$id = $_GET['id'] ?? 0;
if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz kampanya ID.']);
    exit();
}

$db = \Core\Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM campaign_logs WHERE campaign_id = :id ORDER BY sent_at DESC");
$stmt->execute([':id' => $id]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['status' => 'success', 'logs' => $logs]);
