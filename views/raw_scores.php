<?php
// views/raw_scores.php
require_once __DIR__ . '/../config.php';
global $pdo;

$pin_error = '';
$participant_detail = null;
$qso_details = [];

// Check global adjudication status
$stmt_status = $pdo->query("SELECT status FROM cabrillo_logs JOIN participants p ON cabrillo_logs.participant_id = p.id WHERE p.year = $current_contest_year LIMIT 1");
$global_status = $stmt_status->fetchColumn();
$is_adjudicated = ($global_status === 'adjudicated');

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_pin'])) {
    $callsign = strtoupper(trim($_POST['callsign']));
    $pin = isset($_POST['pin']) ? trim($_POST['pin']) : '';
    
    if ($is_adjudicated) {
        $stmt = $pdo->prepare("SELECT p.*, c.raw_score, c.total_qso, c.status as adjudication_status FROM participants p JOIN cabrillo_logs c ON p.id = c.participant_id WHERE p.callsign = ? AND p.year = ?");
        $stmt->execute([$callsign, $current_contest_year]);
    } else {
        $stmt = $pdo->prepare("SELECT p.*, c.raw_score, c.total_qso, c.status as adjudication_status FROM participants p JOIN cabrillo_logs c ON p.id = c.participant_id WHERE p.callsign = ? AND p.access_code = ? AND p.year = ?");
        $stmt->execute([$callsign, $pin, $current_contest_year]);
    }
    
    $participant_detail = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$participant_detail) {
        $pin_error = $is_adjudicated ? "Callsign not found." : "Invalid Callsign or PIN.";
    } else {
        // Fetch QSOs
        $stmt = $pdo->prepare("SELECT * FROM qsos WHERE participant_id = ?");
        $stmt->execute([$participant_detail['id']]);
        $qso_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Fetch Public Leaderboard if not viewing details
$grouped = [];
if (!$participant_detail) {
    $stmt = $pdo->prepare("
        SELECT p.*, c.raw_score, c.total_qso 
        FROM participants p
        JOIN cabrillo_logs c ON p.id = c.participant_id
        WHERE p.year = ? AND p.disqualified = 0
        ORDER BY p.category_op, p.category_band, p.category_power, p.continent, p.country, c.raw_score DESC
    ");
    $stmt->execute([$current_contest_year]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($logs as $log) {
        $op = $log['category_op'] ?: 'UNKNOWN';
        $band = $log['category_band'] ?: 'UNKNOWN';
        $power = $log['category_power'] ?: 'UNKNOWN';
        $cont = $log['continent'] ?: 'UNKNOWN';
        $country = $log['country'] ?: 'UNKNOWN';
        
        $grouped[$op][$band][$power][$cont][$country][] = $log;
    }
}
?>

<div class="wrapper animate-fade-in">

    <?php if ($participant_detail): ?>
        <!-- PRIVATE DETAIL VIEW -->
        <div class="glass-container">
            <h2>Detailed Score for <?php echo htmlspecialchars($participant_detail['callsign']); ?></h2>
            <p><strong>Category:</strong> <?php echo $participant_detail['category_op']; ?> / <?php echo $participant_detail['category_band']; ?> / <?php echo $participant_detail['category_power']; ?></p>
            <p><strong>Current Raw Score:</strong> <?php echo number_format($participant_detail['raw_score']); ?></p>
            <p><strong>Status:</strong> <?php echo strtoupper($participant_detail['adjudication_status']); ?></p>
            
            <h3 style="margin-top: 2rem; margin-bottom: 1rem;">QSO Details</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>Freq</th>
                            <th>Mode</th>
                            <th>Sent</th>
                            <th>Rcvd</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($qso_details as $q): ?>
                        <tr>
                            <td><?php echo $q['qso_date'] . ' ' . $q['qso_time']; ?></td>
                            <td><?php echo htmlspecialchars($q['freq']); ?></td>
                            <td><?php echo htmlspecialchars($q['mode']); ?></td>
                            <td><?php echo htmlspecialchars($q['sent_call'] . ' ' . $q['sent_rst'] . ' ' . $q['sent_exch']); ?></td>
                            <td><?php echo htmlspecialchars($q['rcvd_call'] . ' ' . $q['rcvd_rst'] . ' ' . $q['rcvd_exch']); ?></td>
                            <td>
                                <?php 
                                    if ($q['status'] === 'xqso') echo '<span class="badge badge-danger">X-QSO</span>';
                                    elseif ($q['status'] === 'valid') echo '<span class="badge badge-success">VALID</span>';
                                    else echo '<span class="badge badge-warning">'.strtoupper($q['status']).'</span>';
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 2rem;">
                <a href="index.php?page=raw_scores" class="btn">Back to Public Scores</a>
            </div>
        </div>

    <?php else: ?>
        <!-- PUBLIC VIEW -->
        <div class="glass-container">
            <h2><?php echo t('raw_scores'); ?></h2>
            <p style="color: var(--text-secondary);">This is the raw score calculated before the adjudication/cross-check process.</p>
            
            <div style="margin-top: 2rem; padding: 1.5rem; background: rgba(0,0,0,0.2); border-radius: 8px; border: 1px solid var(--glass-border);">
                <h3>View Personal Detailed Score</h3>
                <?php if($pin_error): ?>
                    <p style="color: var(--danger);"><?php echo $pin_error; ?></p>
                <?php endif; ?>
                <form method="post" style="display: flex; gap: 1rem; align-items: flex-end; margin-top: 1rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <label>Callsign</label>
                        <input type="text" name="callsign" required placeholder="e.g. YB1XYZ">
                    </div>
                    <?php if (!$is_adjudicated): ?>
                    <div style="flex: 1; min-width: 200px;">
                        <label>6-Digit Access Code (PIN)</label>
                        <input type="password" name="pin" required placeholder="XXXXXX">
                    </div>
                    <?php endif; ?>
                    <div>
                        <button type="submit" name="submit_pin" class="btn">View My Details</button>
                    </div>
                </form>
            </div>
            
            <?php if (count($grouped) == 0): ?>
                <p style="text-align:center; margin-top: 2rem;">No scores available.</p>
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
                                                        <th style="text-align: right;">QSO</th>
                                                        <th style="text-align: right;">Raw Score</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $rank = 1;
                                                    foreach($participants as $p): ?>
                                                    <tr>
                                                        <td><?php echo $rank++; ?></td>
                                                        <td style="font-weight: 600; color: var(--accent-hover);"><?php echo htmlspecialchars($p['callsign']); ?></td>
                                                        <td style="text-align: right;"><?php echo $p['total_qso']; ?></td>
                                                        <td style="text-align: right; font-weight: bold;"><?php echo number_format($p['raw_score']); ?></td>
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
    <?php endif; ?>
</div>
