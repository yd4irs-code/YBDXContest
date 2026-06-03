<?php
// views/received_logs.php
require_once __DIR__ . '/../config.php';
global $pdo;

// Fetch all received logs for current year
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "
    SELECT p.*, c.total_qso, c.submitted_at 
    FROM participants p
    JOIN cabrillo_logs c ON p.id = c.participant_id
    WHERE p.year = :year AND p.disqualified = 0
";

if (!empty($search_query)) {
    $sql .= " AND p.callsign LIKE :search";
}

$sql .= " ORDER BY p.category_op, p.category_band, p.category_power, p.continent, p.country, p.callsign";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':year', $current_contest_year);
if (!empty($search_query)) {
    $stmt->bindValue(':search', '%' . $search_query . '%');
}
$stmt->execute();
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
        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">Total Logs Received: <strong><?php echo count($logs); ?></strong></p>
        
        <form method="GET" action="index.php" style="margin-bottom: 2rem; display: flex; gap: 0.5rem; max-width: 400px;">
            <input type="hidden" name="page" value="received_logs">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search by callsign..." style="flex: 1; text-transform: uppercase;" autocomplete="off">
            <button type="submit" class="btn">Search</button>
            <?php if(!empty($search_query)): ?>
                <a href="index.php?page=received_logs" class="btn" style="background: var(--danger);">Clear</a>
            <?php endif; ?>
        </form>
        
        <?php if ($total_received == 0): ?>
            <p style="text-align:center;">No logs have been submitted yet.</p>
        <?php else: ?>
        
            <?php foreach($grouped as $op => $bands): ?>
                <h3 style="background: rgba(59, 130, 246, 0.3); padding: 0.5rem 1rem; border-radius: 8px; margin-top: 2rem; border-left: 5px solid var(--accent-color);">
                    Operator: <?php echo htmlspecialchars($op); ?>
                </h3>
                
                <?php foreach($bands as $band => $powers): ?>
                    <?php foreach($powers as $power => $conts): ?>
                        <?php foreach($conts as $cont => $countries): ?>
                            <?php
                            $cont_names = [
                                'AS' => 'ASIA (AS)',
                                'EU' => 'EUROPE (EU)',
                                'NA' => 'NORTH AMERICA (NA)',
                                'SA' => 'SOUTH AMERICA (SA)',
                                'AF' => 'AFRICA (AF)',
                                'OC' => 'OCEANIA (OC)',
                                'AN' => 'ANTARCTICA (AN)'
                            ];
                            $cont_display = isset($cont_names[$cont]) ? $cont_names[$cont] : $cont;
                            ?>
                            <div style="margin-left: 1rem; margin-top: 1.5rem;">
                                <h4 style="color: var(--accent-hover); border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem; display: flex; align-items: center; flex-wrap: wrap; gap: 1rem;">
                                    <span>Band: <?php echo htmlspecialchars($band); ?></span>
                                    <span class="badge badge-warning" style="font-size: 0.85rem; padding: 0.2rem 0.5rem;">Power: <?php echo htmlspecialchars($power); ?></span>
                                    <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: var(--success); font-size: 0.85rem; padding: 0.2rem 0.5rem;">Continent: <?php echo htmlspecialchars($cont_display); ?></span>
                                </h4>
                                
                                <div style="margin-left: 1rem; margin-top: 1rem;">
                                
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
                                </div> <!-- End inner div -->
                            </div> <!-- End outer div -->
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
            
        <?php endif; ?>
    </div>
</div>
