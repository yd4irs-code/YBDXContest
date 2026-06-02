<?php
// admin/adjudicate.php
require_once 'header.php';
require_once __DIR__ . '/../includes/CrossChecker.php';

global $pdo;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_adj'])) {
    try {
        $checker = new CrossChecker($pdo, $current_contest_year);
        $checker->runAdjudication();
        
        // Reset/Clear all PINs after adjudication is complete
        $pdo->prepare("UPDATE participants SET access_code = NULL WHERE year = ?")->execute([$current_contest_year]);
        
        $message = "Adjudication process completed successfully! All PINs have been reset.";
    } catch (Exception $e) {
        $message = "Error during adjudication: " . $e->getMessage();
    }
}

// Check status
$stmt = $pdo->query("SELECT status FROM cabrillo_logs JOIN participants p ON cabrillo_logs.participant_id = p.id WHERE p.year = $current_contest_year LIMIT 1");
$status = $stmt->fetchColumn();

?>

<div class="glass-container animate-fade-in">
    <h2>Run Adjudication Engine</h2>
    <p style="color: var(--text-secondary); margin-bottom: 2rem;">Cross-check all submitted logs, assign Valid/Busted/NIL/Dupe statuses, generate UBN reports, and calculate final scores.</p>
    
    <?php if($message): ?>
        <div class="badge badge-success" style="display:block; padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div style="background: rgba(0,0,0,0.2); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--glass-border);">
        <p style="margin-bottom: 1.5rem;"><strong>Current Status:</strong> <?php echo strtoupper($status ?: 'PENDING'); ?></p>
        
        <form method="post">
            <button type="submit" name="run_adj" class="btn" style="padding: 1rem 2rem; font-size: 1.1rem; background: #8b5cf6;" onclick="return confirm('This will re-calculate scores for all participants. Are you sure?');">
                <i class="fas fa-play"></i> RUN ADJUDICATION NOW
            </button>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>
