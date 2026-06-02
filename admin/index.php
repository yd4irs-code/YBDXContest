<?php
// admin/index.php
require_once 'header.php';
global $pdo;

// Fetch some stats
$stmt = $pdo->query("SELECT COUNT(*) as t FROM participants WHERE year = $current_contest_year");
$total_p = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as t FROM participants WHERE year = $current_contest_year AND disqualified = 1");
$total_dq = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as t FROM qsos JOIN participants p ON qsos.participant_id = p.id WHERE p.year = $current_contest_year");
$total_qso = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT status FROM cabrillo_logs JOIN participants p ON cabrillo_logs.participant_id = p.id WHERE p.year = $current_contest_year LIMIT 1");
$status = $stmt->fetchColumn();
$adj_status = ($status == 'adjudicated') ? 'Completed' : 'Pending';
?>

<div class="glass-container animate-fade-in">
    <h2>Dashboard Overview</h2>
    <p style="color: var(--text-secondary);">Welcome, <?php echo $_SESSION['admin_username']; ?>. Contest Year: <?php echo $current_contest_year; ?></p>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
        <div style="background: rgba(59, 130, 246, 0.2); padding: 1.5rem; border-radius: 8px;">
            <h3 style="color: var(--accent-hover); margin-bottom: 0.5rem;">Participants</h3>
            <span style="font-size: 2.5rem; font-weight: bold;"><?php echo $total_p; ?></span>
        </div>
        <div style="background: rgba(16, 185, 129, 0.2); padding: 1.5rem; border-radius: 8px;">
            <h3 style="color: #34d399; margin-bottom: 0.5rem;">Total QSOs</h3>
            <span style="font-size: 2.5rem; font-weight: bold;"><?php echo $total_qso; ?></span>
        </div>
        <div style="background: rgba(239, 68, 68, 0.2); padding: 1.5rem; border-radius: 8px;">
            <h3 style="color: #f87171; margin-bottom: 0.5rem;">Disqualified</h3>
            <span style="font-size: 2.5rem; font-weight: bold;"><?php echo $total_dq; ?></span>
        </div>
        <div style="background: rgba(245, 158, 11, 0.2); padding: 1.5rem; border-radius: 8px;">
            <h3 style="color: #fbbf24; margin-bottom: 0.5rem;">Adjudication</h3>
            <span style="font-size: 1.5rem; font-weight: bold;"><?php echo $adj_status; ?></span>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
