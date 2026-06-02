<?php
// views/certificate_download.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/PdfGenerator.php';
global $pdo;

$pin_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_cert'])) {
    $callsign = strtoupper(trim($_POST['callsign']));
    
    // Check participant
    $stmt = $pdo->prepare("
        SELECT p.*, c.raw_score, c.status as adjudication_status 
        FROM participants p 
        JOIN cabrillo_logs c ON p.id = c.participant_id 
        WHERE p.callsign = ? AND p.year = ? AND p.disqualified = 0
    ");
    $stmt->execute([$callsign, $current_contest_year]);
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$participant) {
        $pin_error = "Callsign not found, or you have been disqualified.";
    } elseif ($participant['adjudication_status'] !== 'adjudicated') {
        $pin_error = "Certificates are only available after the adjudication process is complete.";
    } else {
        // Fetch background image path
        $stmt_bg = $pdo->prepare("SELECT image_path FROM certificates_bg WHERE year = ?");
        $stmt_bg->execute([$current_contest_year]);
        $bg = $stmt_bg->fetch(PDO::FETCH_ASSOC);
        
        $bg_path = $bg ? __DIR__ . '/../' . $bg['image_path'] : '';
        
        // Output PDF and exit
        ob_end_clean(); // Clean any previous output buffer
        PdfGenerator::generateCertificate($participant, $bg_path);
        exit;
    }
}
?>

<div class="wrapper animate-fade-in">
    <div class="glass-container" style="max-width: 600px; margin: 0 auto;">
        <h2 style="text-align: center;"><i class="fas fa-award"></i> Download e-Certificate</h2>
        <p style="text-align: center; color: var(--text-secondary); margin-bottom: 2rem;">Please enter your Callsign to download your certificate.</p>
        
        <?php if($pin_error): ?>
            <div class="badge badge-danger" style="display:block; padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px;">
                <?php echo $pin_error; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div class="form-group">
                <label>Callsign</label>
                <input type="text" name="callsign" required placeholder="YB1XYZ">
            </div>
            
            <button type="submit" name="download_cert" class="btn" style="width: 100%; font-size: 1.1rem; padding: 1rem;">Download PDF</button>
        </form>
    </div>
</div>
