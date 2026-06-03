<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/ScoringHelper.php';
global $pdo;

$stmt = $pdo->query("SELECT c.id, p.id as participant_id, p.country, p.continent, p.category_band FROM cabrillo_logs c JOIN participants p ON c.participant_id = p.id WHERE c.total_points = 0 AND c.raw_score > 0");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count = 0;
foreach ($logs as $log) {
    $q_stmt = $pdo->prepare("SELECT * FROM qsos WHERE participant_id = ?");
    $q_stmt->execute([$log['participant_id']]);
    $qsos = $q_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($qsos as &$q) {
        $q['is_xqso'] = ($q['status'] === 'xqso');
    }
    
    $score_data = calculateScore($qsos, $log['country'], $log['continent'], $log['category_band']);
    
    $upd = $pdo->prepare("UPDATE cabrillo_logs SET total_points = ?, total_multiplier = ? WHERE id = ?");
    $upd->execute([$score_data['points'], $score_data['multiplier'], $log['id']]);
    $count++;
}
echo "Update selesai! Berhasil memperbaiki $count data log.";
