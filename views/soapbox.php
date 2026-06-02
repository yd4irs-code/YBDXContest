<?php
// views/soapbox.php
require_once __DIR__ . '/../config.php';
global $pdo;

$stmt = $pdo->query("
    SELECT p.callsign, c.soapbox, c.submitted_at 
    FROM cabrillo_logs c
    JOIN participants p ON c.participant_id = p.id
    WHERE c.soapbox IS NOT NULL AND TRIM(c.soapbox) != ''
    ORDER BY c.submitted_at DESC
");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="wrapper animate-fade-in">
    <div class="glass-container">
        <h2 style="margin-bottom: 2rem;"><i class="fas fa-comments"></i> <?php echo t('soapbox'); ?></h2>
        
        <?php if (count($messages) == 0): ?>
            <p style="text-align:center; color: var(--text-secondary);">No soapbox comments yet.</p>
        <?php else: ?>
            <div class="chat-container">
                <?php foreach ($messages as $msg): 
                    $callsign = htmlspecialchars($msg['callsign']);
                    $time = date('Y-m-d H:i', strtotime($msg['submitted_at']));
                    $text = nl2br(htmlspecialchars(trim($msg['soapbox'])));
                    $initial = substr($callsign, 0, 1);
                ?>
                <div class="chat-msg animate-fade-in">
                    <div class="chat-avatar"><?php echo $initial; ?></div>
                    <div class="chat-bubble">
                        <div class="chat-header">
                            <span class="chat-callsign"><?php echo $callsign; ?></span>
                            <span class="chat-time"><?php echo $time; ?></span>
                        </div>
                        <div class="chat-content">
                            <?php echo $text; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
