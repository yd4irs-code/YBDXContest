<?php
// views/received_logs.php
require_once __DIR__ . '/../config.php';
global $pdo;

// Fetch all received logs for current year
$stmt = $pdo->prepare("
    SELECT p.*, c.total_qso, c.submitted_at 
    FROM participants p
    JOIN cabrillo_logs c ON p.id = c.participant_id
    WHERE p.year = ? AND p.disqualified = 0
    ORDER BY p.category_op, p.category_band, p.category_power, p.continent, p.country, p.callsign
");
$stmt->execute([$current_contest_year]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Grouping logic: Operator -> Band -> Power -> Continent -> Country
$grouped = [];
foreach ($logs as $log) {
    $op = $log['category_op'] ?: 'UNKNOWN-OP';
    $band = $log['category_band'] ?: 'UNKNOWN-BAND';
    $power = $log['category_power'] ?: 'UNKNOWN-POWER';
    $cont = $log['continent'] ?: 'UNKNOWN-CONT';
    $country = $log['country'] ?: 'UNKNOWN-COUNTRY';
    
    if(!isset($grouped[$op])) $grouped[$op] = [];
    if(!isset($grouped[$op][$band])) $grouped[$op][$band] = [];
    if(!isset($grouped[$op][$band][$power])) $grouped[$op][$band][$power] = [];
    if(!isset($grouped[$op][$band][$power][$cont])) $grouped[$op][$band][$power][$cont] = [];
    if(!isset($grouped[$op][$band][$power][$cont][$country])) $grouped[$op][$band][$power][$cont][$country] = [];
    
    $grouped[$op][$band][$power][$cont][$country][] = $log;
}

$total_received = count($logs);
?>

<div class="wrapper animate-fade-in">
    <div class="glass-container">
        <h2 style="margin-bottom: 0.5rem;"><?php echo t('received_logs'); ?> (<?php echo $current_contest_year; ?>)</h2>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">Total Logs Received: <strong><?php echo $total_received; ?></strong></p>
        
        <?php if ($total_received == 0): ?>
            <p style="text-align:center;">No logs have been submitted yet.</p>
        <?php else: ?>
        
            <?php foreach($grouped as $op => $bands): ?>
                <h3 style="background: rgba(59, 130, 246, 0.3); padding: 0.5rem 1rem; border-radius: 8px; margin-top: 2rem; border-left: 5px solid var(--accent-color);">
                    Operator: <?php echo htmlspecialchars($op); ?>
                </h3>
                
                <?php foreach($bands as $band => $powers): ?>
                    <h4 style="color: var(--accent-hover); margin-left: 1rem; margin-top: 1.5rem; border-bottom: 1px solid var(--glass-border);">
                        Band: <?php echo htmlspecialchars($band); ?>
                    </h4>
                    
                    <?php foreach($powers as $power => $conts): ?>
                        <div style="margin-left: 2rem; margin-top: 1rem;">
                            <span class="badge badge-warning" style="margin-bottom: 1rem; display: inline-block;">
                                Power: <?php echo htmlspecialchars($power); ?>
                            </span>
                            
                            <?php foreach($conts as $cont => $countries): ?>
                                <h5 style="margin-top: 1rem; color: #fff;">Continent: <?php echo htmlspecialchars($cont); ?></h5>
                                
                                <?php foreach($countries as $country => $participants): ?>
                                    <h6 style="color: var(--text-secondary); margin-top: 0.5rem;"><?php echo htmlspecialchars($country); ?></h6>
                                    
                                    <div class="table-responsive">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Callsign</th>
                                                    <th>Total QSO (Raw)</th>
                                                    <th>Date Submitted</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($participants as $p): ?>
                                                <tr>
                                                    <td style="font-weight: 600; color: var(--accent-hover);"><?php echo htmlspecialchars($p['callsign']); ?></td>
                                                    <td><?php echo $p['total_qso']; ?></td>
                                                    <td><?php echo date('Y-m-d H:i', strtotime($p['submitted_at'])); ?> UTC</td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
            
        <?php endif; ?>
    </div>
</div>
