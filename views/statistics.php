<?php
// views/statistics.php
require_once __DIR__ . '/../config.php';
global $pdo;

// Fetch Year-by-Year Statistics
$stmt = $pdo->query("
    SELECT 
        p.year, 
        COUNT(DISTINCT p.id) as total_participants, 
        COALESCE(SUM(c.total_qso), 0) as total_qso
    FROM participants p
    LEFT JOIN cabrillo_logs c ON p.id = c.participant_id AND c.status = 'adjudicated'
    WHERE p.disqualified = 0
    GROUP BY p.year
    ORDER BY p.year DESC
");
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="wrapper animate-fade-in">
    <div class="glass-container">
        <h2 style="margin-bottom: 2rem;"><i class="fas fa-chart-bar"></i> <?php echo t('statistics'); ?></h2>
        
        <p style="margin-bottom: 2rem; color: var(--text-secondary);">Contest execution statistics from year to year, showing the growth of participants and total QSOs.</p>
        
        <?php if (count($stats) == 0): ?>
            <p style="text-align:center; color: var(--text-secondary);">No historical data available.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th style="text-align: right;">Total Logs Received</th>
                            <th style="text-align: right;">Total Valid QSOs</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($stats as $row): ?>
                        <tr>
                            <td style="font-weight: bold; color: var(--accent-hover);"><?php echo $row['year']; ?></td>
                            <td style="text-align: right;"><?php echo number_format($row['total_participants']); ?></td>
                            <td style="text-align: right;"><?php echo number_format($row['total_qso']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
