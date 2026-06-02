<?php
// views/final_scores.php
require_once __DIR__ . '/../config.php';
global $pdo;

// Fetch ADJUDICATED logs
$stmt = $pdo->prepare("
    SELECT p.*, c.raw_score, c.total_qso, c.total_points, c.total_multiplier 
    FROM participants p
    JOIN cabrillo_logs c ON p.id = c.participant_id
    WHERE p.year = ? AND p.disqualified = 0 AND c.status = 'adjudicated'
    ORDER BY p.category_op, p.category_band, p.category_power, p.continent, p.country, c.raw_score DESC, c.total_points DESC, c.total_qso DESC, c.total_multiplier DESC
");
$stmt->execute([$current_contest_year]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Disqualified
$stmt_dq = $pdo->prepare("SELECT callsign, category_op FROM participants WHERE year = ? AND disqualified = 1");
$stmt_dq->execute([$current_contest_year]);
$dq_logs = $stmt_dq->fetchAll(PDO::FETCH_ASSOC);

// Grouping logic for Leaderboard
$grouped = [];
foreach ($logs as $log) {
    $op = $log['category_op'] ?: 'UNKNOWN-OP';
    $band = $log['category_band'] ?: 'UNKNOWN-BAND';
    $power = $log['category_power'] ?: 'UNKNOWN-POWER';
    $cont = $log['continent'] ?: 'UNKNOWN-CONT';
    $country = $log['country'] ?: 'UNKNOWN-COUNTRY';
    
    $grouped[$op][$band][$power][$cont][$country][] = $log;
}
?>

<div class="wrapper animate-fade-in">
    <div class="glass-container">
        <h2 style="margin-bottom: 2rem;"><?php echo t('final_scores'); ?> (<?php echo $current_contest_year; ?>)</h2>
        
        <?php if (count($logs) == 0): ?>
            <p style="text-align:center; color: var(--text-secondary);">Adjudication process is not complete yet. No final scores to display.</p>
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
                                                    <th style="width: 50px;">Rank</th>
                                                    <th>Callsign</th>
                                                    <th style="text-align: right;">Valid QSO</th>
                                                    <th style="text-align: right;">Points</th>
                                                    <th style="text-align: right;">Mult</th>
                                                    <th style="text-align: right;">Final Score</th>
                                                    <th style="text-align: center;">UBN Report</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $rank = 1;
                                                foreach($participants as $p): ?>
                                                <tr>
                                                    <td>
                                                        <?php if($rank == 1) echo '<span style="color: gold;"><i class="fas fa-trophy"></i> 1</span>';
                                                              elseif($rank == 2) echo '<span style="color: silver;">2</span>';
                                                              elseif($rank == 3) echo '<span style="color: #cd7f32;">3</span>';
                                                              else echo $rank; 
                                                        ?>
                                                    </td>
                                                    <td style="font-weight: 600; color: var(--accent-hover);"><?php echo htmlspecialchars($p['callsign']); ?></td>
                                                    <td style="text-align: right;"><?php echo $p['total_qso']; ?></td>
                                                    <td style="text-align: right;"><?php echo $p['total_points']; ?></td>
                                                    <td style="text-align: right;"><?php echo $p['total_multiplier']; ?></td>
                                                    <td style="text-align: right; font-weight: bold;"><?php echo number_format($p['raw_score']); ?></td>
                                                    <td style="text-align: center;">
                                                        <a href="ValidQSO/<?php echo $current_contest_year; ?>/UBN/<?php echo urlencode($p['callsign']); ?>.txt" target="_blank" class="btn" style="padding: 0.3rem 0.8rem; font-size: 0.8rem;">View UBN</a>
                                                    </td>
                                                </tr>
                                                <?php 
                                                $rank++;
                                                endforeach; 
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
            
            <?php if(count($dq_logs) > 0): ?>
            <div style="margin-top: 3rem; background: rgba(239, 68, 68, 0.1); padding: 1.5rem; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3);">
                <h3 style="color: var(--danger);"><i class="fas fa-ban"></i> Disqualified Participants</h3>
                <p style="margin-bottom: 1rem; color: var(--text-secondary);">The following callsigns have been disqualified for rule violations and are not eligible for any awards or certificates.</p>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Callsign</th>
                                <th>Category</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($dq_logs as $dq): ?>
                            <tr>
                                <td style="font-weight: bold; color: var(--danger);"><?php echo htmlspecialchars($dq['callsign']); ?></td>
                                <td><?php echo htmlspecialchars($dq['category_op']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
</div>
