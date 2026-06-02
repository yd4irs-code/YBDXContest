<?php
// views/raw_scores.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/ScoringHelper.php';
global $pdo;

$pin_error = '';
$participant_detail = null;
$qso_details = [];

// Check global adjudication status
$stmt_status = $pdo->query("SELECT status FROM cabrillo_logs JOIN participants p ON cabrillo_logs.participant_id = p.id WHERE p.year = $current_contest_year LIMIT 1");
$global_status = $stmt_status->fetchColumn();
$is_adjudicated = ($global_status === 'adjudicated');

// Handle Form Submission or GET link
$callsign_to_view = '';

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
        $callsign_to_view = $callsign;
    } else {
        // Fetch QSOs
        $stmt = $pdo->prepare("SELECT * FROM qsos WHERE participant_id = ?");
        $stmt->execute([$participant_detail['id']]);
        $qso_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} elseif (isset($_GET['view_callsign'])) {
    $callsign_to_view = strtoupper(trim($_GET['view_callsign']));
    
    if ($is_adjudicated) {
        $stmt = $pdo->prepare("SELECT p.*, c.raw_score, c.total_qso, c.status as adjudication_status FROM participants p JOIN cabrillo_logs c ON p.id = c.participant_id WHERE p.callsign = ? AND p.year = ?");
        $stmt->execute([$callsign_to_view, $current_contest_year]);
        $participant_detail = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$participant_detail) {
            $pin_error = "Callsign not found.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM qsos WHERE participant_id = ?");
            $stmt->execute([$participant_detail['id']]);
            $qso_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
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
            
            <?php 
                $my_country = $participant_detail['country'] ?: 'UNKNOWN';
                $my_continent = $participant_detail['continent'] ?: 'UNKNOWN';
                $score_data = calculateScore($qso_details, $my_country, $my_continent); 
            ?>
            
            <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 1.5rem; margin-bottom: 2rem;">
                <div style="background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 8px; flex: 1; min-width: 120px;">
                    <div style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase;">Points</div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: #fff;"><?php echo number_format($score_data['points']); ?></div>
                </div>
                <div style="background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 8px; flex: 1; min-width: 120px;">
                    <div style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase;">Multipliers</div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: #fff;"><?php echo number_format($score_data['multiplier']); ?></div>
                </div>
                <div style="background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 8px; flex: 1; min-width: 120px;">
                    <div style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase;">Continents Worked</div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: #fff;"><?php echo number_format($score_data['continent_count']); ?></div>
                </div>
                <div style="background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 8px; flex: 1; min-width: 120px;">
                    <div style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase;">Countries / DXCC</div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: #fff;"><?php echo number_format($score_data['dxcc_count']); ?></div>
                </div>
                <div style="background: rgba(16, 185, 129, 0.1); padding: 1rem; border-radius: 8px; flex: 2; min-width: 200px; border: 1px solid rgba(16, 185, 129, 0.3);">
                    <div style="font-size: 0.9rem; color: var(--success); text-transform: uppercase; font-weight: 600;">Calculated Raw Score</div>
                    <div style="font-size: 2rem; font-weight: bold; color: var(--success);"><?php echo number_format($score_data['raw_score']); ?></div>
                </div>
            </div>
            
            <p style="margin-bottom: 2rem;"><strong>Adjudication Status:</strong> <?php echo strtoupper($participant_detail['adjudication_status']); ?></p>
            
            <h3 style="margin-top: 2rem; margin-bottom: 1rem;">QSO Details</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">#</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Freq</th>
                            <th>Mode</th>
                            <th>Sent Call</th>
                            <th>Sent NR</th>
                            <th>Rcvd Call</th>
                            <th>Rcvd NR</th>
                            <th>Status</th>
                            <th style="text-align: center;">Points</th>
                            <th style="text-align: center;">Mult</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $qso_idx = 1; foreach($qso_details as $q): ?>
                        <tr>
                            <td style="text-align: center; color: var(--text-secondary);"><?php echo $qso_idx++; ?></td>
                            <td><?php echo $q['qso_date']; ?></td>
                            <td><?php echo substr($q['qso_time'], 0, 5); ?></td>
                            <td><?php echo htmlspecialchars($q['freq']); ?></td>
                            <td><?php echo htmlspecialchars($q['mode']); ?></td>
                            <td><?php echo htmlspecialchars($q['sent_call']); ?></td>
                            <td><?php echo htmlspecialchars($q['sent_exch']); ?></td>
                            <td><?php echo htmlspecialchars($q['rcvd_call']); ?></td>
                            <td><?php echo htmlspecialchars($q['rcvd_exch']); ?></td>
                            <td>
                                <?php 
                                    if ($q['status'] === 'xqso') echo '<span class="badge badge-danger">X-QSO</span>';
                                    elseif ($q['status'] === 'valid') echo '<span class="badge badge-success">VALID</span>';
                                    else echo '<span class="badge badge-warning">'.strtoupper($q['status']).'</span>';
                                ?>
                            </td>
                            <td style="text-align: center; font-weight: bold;"><?php echo (isset($q['qso_points']) && $q['status'] !== 'xqso') ? $q['qso_points'] : 0; ?></td>
                            <td style="text-align: center; font-weight: bold; color: var(--accent-hover);"><?php echo (isset($q['is_mult']) && $q['is_mult'] && $q['status'] !== 'xqso') ? '1' : ''; ?></td>
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
                        <input type="text" name="callsign" id="callsign_input" required placeholder="e.g. YB1XYZ" value="<?php echo htmlspecialchars($callsign_to_view); ?>">
                    </div>
                    <?php if (!$is_adjudicated): ?>
                    <div style="flex: 1; min-width: 200px;">
                        <label>6-Digit Access Code (PIN)</label>
                        <input type="password" name="pin" id="pin_input" required placeholder="XXXXXX">
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
                    <details class="score-details level-1" open>
                        <summary>
                            <span class="summary-title">Operator: <?php echo htmlspecialchars($op); ?></span>
                            <i class="fas fa-chevron-down summary-icon"></i>
                        </summary>
                        <div class="details-content">
                            <?php foreach($bands as $band => $powers): ?>
                                <details class="score-details level-2">
                                    <summary>
                                        <span class="summary-title">Band: <?php echo htmlspecialchars($band); ?></span>
                                        <i class="fas fa-chevron-down summary-icon"></i>
                                    </summary>
                                    <div class="details-content">
                                        <?php foreach($powers as $power => $conts): ?>
                                            <details class="score-details level-3">
                                                <summary>
                                                    <span class="summary-title">Power: <span class="badge badge-warning" style="margin-left:0.5rem; margin-bottom:0; font-size:0.8rem;"><?php echo htmlspecialchars($power); ?></span></span>
                                                    <i class="fas fa-chevron-down summary-icon"></i>
                                                </summary>
                                                <div class="details-content">
                                                    <?php foreach($conts as $cont => $countries): ?>
                                                        <details class="score-details level-4">
                                                            <summary>
                                                                <span class="summary-title">Continent: <?php echo htmlspecialchars($cont); ?></span>
                                                                <i class="fas fa-chevron-down summary-icon"></i>
                                                            </summary>
                                                            <div class="details-content">
                                                                <?php foreach($countries as $country => $participants): ?>
                                                                    <h6 style="color: var(--text-secondary); margin-top: 1rem; margin-bottom: 0.5rem; font-size: 1.1rem; border-bottom: 1px dashed var(--glass-border); padding-bottom: 0.3rem;"><i class="fas fa-flag" style="margin-right: 0.5rem;"></i> <?php echo htmlspecialchars($country); ?></h6>
                                                                    
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
                                                                                    <td style="font-weight: 600;">
                                                                                        <a href="javascript:void(0)" onclick="viewCallsign('<?php echo htmlspecialchars(addslashes($p['callsign'])); ?>')" style="color: var(--accent-hover); text-decoration: none;">
                                                                                            <?php echo htmlspecialchars($p['callsign']); ?>
                                                                                        </a>
                                                                                    </td>
                                                                                    <td style="text-align: right;"><?php echo $p['total_qso']; ?></td>
                                                                                    <td style="text-align: right; font-weight: bold;"><?php echo number_format($p['raw_score']); ?></td>
                                                                                </tr>
                                                                                <?php endforeach; ?>
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </details>
                                                    <?php endforeach; ?>
                                                </div>
                                            </details>
                                        <?php endforeach; ?>
                                    </div>
                                </details>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
                
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function viewCallsign(callsign) {
    <?php if ($is_adjudicated): ?>
    window.location.href = 'index.php?page=raw_scores&view_callsign=' + encodeURIComponent(callsign);
    <?php else: ?>
    var callsignInput = document.getElementById('callsign_input');
    var pinInput = document.getElementById('pin_input');
    
    if(callsignInput) callsignInput.value = callsign;
    if(pinInput) pinInput.focus();
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
    <?php endif; ?>
}
</script>
