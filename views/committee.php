<?php
// views/committee.php
require_once __DIR__ . '/../config.php';
global $pdo;

$stmt = $pdo->query("SELECT * FROM committee ORDER BY year DESC, id ASC");
$committee = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by year
$grouped = [];
foreach ($committee as $c) {
    $grouped[$c['year']][] = $c;
}
?>

<div class="wrapper animate-fade-in">
    <div class="glass-container">
        <h2 style="margin-bottom: 2rem;"><?php echo t('committee'); ?></h2>
        
        <?php if (count($grouped) == 0): ?>
            <p style="text-align:center; color: var(--text-secondary);">No committee data available.</p>
        <?php else: ?>
            <?php foreach ($grouped as $year => $members): ?>
                <h3 style="color: var(--accent-color); margin-top: 1.5rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem;"><?php echo $year; ?></h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Callsign</th>
                                <th>Position</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $m): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($m['name']); ?></td>
                                <td style="font-weight: bold; color: var(--accent-hover);"><?php echo htmlspecialchars($m['callsign']); ?></td>
                                <td><?php echo htmlspecialchars($m['position']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
