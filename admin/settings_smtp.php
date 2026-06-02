<?php
// admin/settings_smtp.php
require_once 'header.php';
global $pdo;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'];
    $port = $_POST['port'];
    $user = $_POST['username'];
    $pass = empty($_POST['password']) ? null : $_POST['password']; // keep old if empty
    $enc = $_POST['encryption'];
    $femail = $_POST['from_email'];
    $fname = $_POST['from_name'];
    
    if ($pass) {
        $stmt = $pdo->prepare("UPDATE smtp_config SET host=?, port=?, username=?, password=?, encryption=?, from_email=?, from_name=? WHERE id=1");
        $stmt->execute([$host, $port, $user, $pass, $enc, $femail, $fname]);
    } else {
        $stmt = $pdo->prepare("UPDATE smtp_config SET host=?, port=?, username=?, encryption=?, from_email=?, from_name=? WHERE id=1");
        $stmt->execute([$host, $port, $user, $enc, $femail, $fname]);
    }
    
    $message = "SMTP Configuration updated successfully.";
}

$stmt = $pdo->query("SELECT * FROM smtp_config LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<div class="glass-container animate-fade-in">
    <h2>SMTP Configuration</h2>
    <p style="color: var(--text-secondary); margin-bottom: 2rem;">Setup email server for sending PINs to participants.</p>
    
    <?php if($message): ?>
        <div class="badge badge-success" style="display:block; padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <form method="post" style="max-width: 600px;">
        <div class="form-group">
            <label>SMTP Host</label>
            <input type="text" name="host" value="<?php echo htmlspecialchars($config['host']); ?>" required>
        </div>
        <div style="display:flex; gap: 1rem;">
            <div class="form-group" style="flex: 1;">
                <label>SMTP Port</label>
                <input type="number" name="port" value="<?php echo $config['port']; ?>" required>
            </div>
            <div class="form-group" style="flex: 1;">
                <label>Encryption</label>
                <select name="encryption">
                    <option value="tls" <?php echo $config['encryption'] == 'tls' ? 'selected' : ''; ?>>TLS</option>
                    <option value="ssl" <?php echo $config['encryption'] == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                    <option value="" <?php echo $config['encryption'] == '' ? 'selected' : ''; ?>>None</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>SMTP Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($config['username']); ?>" required>
        </div>
        <div class="form-group">
            <label>SMTP Password (leave blank to keep current)</label>
            <input type="password" name="password">
        </div>
        <div style="display:flex; gap: 1rem;">
            <div class="form-group" style="flex: 1;">
                <label>From Email</label>
                <input type="email" name="from_email" value="<?php echo htmlspecialchars($config['from_email']); ?>" required>
            </div>
            <div class="form-group" style="flex: 1;">
                <label>From Name</label>
                <input type="text" name="from_name" value="<?php echo htmlspecialchars($config['from_name']); ?>" required>
            </div>
        </div>
        <button type="submit" class="btn">Save Configuration</button>
    </form>
</div>

<?php require_once 'footer.php'; ?>
