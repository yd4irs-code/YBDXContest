<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/ScoringHelper.php';
global $pdo;

// Ambil semua partisipan untuk diverifikasi ulang DXCC-nya
$stmt = $pdo->query("SELECT * FROM participants");
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;
foreach ($participants as $p) {
    $dxcc = getDxccFromCallsign($p['callsign']);
    // Jika negara yang tersimpan beda sama hasil terbaru (setelah dxcc.json diperbaiki)
    if ($dxcc['country'] !== $p['country']) {
        $pdo->prepare("UPDATE participants SET country = ?, continent = ? WHERE id = ?")->execute([$dxcc['country'], $dxcc['continent'], $p['id']]);
        echo "Updated {$p['callsign']} from {$p['country']} to {$dxcc['country']} ({$dxcc['continent']})<br>\n";
        
        // Recalculate score
        $q_stmt = $pdo->prepare("SELECT * FROM qsos WHERE participant_id = ?");
        $q_stmt->execute([$p['id']]);
        $qsos = $q_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($qsos as &$q) {
            $q['is_xqso'] = ($q['status'] === 'xqso');
        }
        
        $score = calculateScore($qsos, $dxcc['country'], $dxcc['continent'], $p['category_band']);
        
        $pdo->prepare("UPDATE cabrillo_logs SET total_points = ?, total_multiplier = ?, raw_score = ? WHERE participant_id = ?")
            ->execute([$score['points'], $score['multiplier'], $score['raw_score'], $p['id']]);
        $updated++;
    }
}

if ($updated === 0) {
    echo "Semua data DXCC peserta sudah akurat. Tidak ada yang perlu diubah.<br>\n";
} else {
    echo "Selesai. $updated peserta berhasil diperbaiki nilainya.\n";
}
?>
