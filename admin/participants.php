<?php
// admin/participants.php
require_once 'header.php';
global $pdo;

if (isset($_GET['dq']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $dq = (int)$_GET['dq'];
    $pdo->prepare("UPDATE participants SET disqualified = ? WHERE id = ?")->execute([$dq, $id]);
    header("Location: participants.php");
    exit;
}

if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM participants WHERE id = ?")->execute([$id]);
    header("Location: participants.php");
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT * FROM participants ";
$params = [];
if ($search !== '') {
    $sql .= "WHERE callsign LIKE ? ";
    $params[] = '%' . $search . '%';
}
$sql .= "ORDER BY year DESC, callsign ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="glass-container animate-fade-in">
    <h2>Manage Participants</h2>
    <p style="color: var(--text-secondary); margin-bottom: 2rem;">Disqualify or remove participants from the contest.</p>
    
    <div style="margin-bottom: 1.5rem;">
        <form method="get" action="participants.php" style="display: flex; gap: 0.5rem; max-width: 400px;">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search callsign..." style="flex: 1; background: rgba(0,0,0,0.2); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 4px; padding: 0.5rem 1rem;">
            <button type="submit" class="btn" style="padding: 0.5rem 1rem;">Search</button>
            <?php if ($search !== ''): ?>
                <a href="participants.php" class="btn btn-danger" style="padding: 0.5rem 1rem;">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Callsign</th>
                    <th>PIN</th>
                    <th>Category</th>
                    <th>Email</th>
                    <th>Year</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($participants as $p): ?>
                <tr style="<?php echo $p['disqualified'] ? 'background: rgba(239, 68, 68, 0.1);' : ''; ?>">
                    <td style="font-weight: bold; color: var(--accent-hover);"><?php echo htmlspecialchars($p['callsign']); ?></td>
                    <td style="font-family: monospace; letter-spacing: 2px; color: #fbbf24; font-weight: bold;"><?php echo htmlspecialchars($p['access_code']); ?></td>
                    <td><?php echo htmlspecialchars($p['category_op']); ?> / <?php echo htmlspecialchars($p['category_band']); ?></td>
                    <td><?php echo htmlspecialchars($p['email']); ?></td>
                    <td><?php echo $p['year']; ?></td>
                    <td>
                        <?php if ($p['disqualified']): ?>
                            <span class="badge badge-danger">DQ</span>
                        <?php else: ?>
                            <span class="badge badge-success">Active</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($p['disqualified']): ?>
                            <a href="participants.php?dq=0&id=<?php echo $p['id']; ?>" class="btn" style="padding: 0.3rem 0.8rem; font-size: 0.8rem; background: var(--success);">Restore</a>
                        <?php else: ?>
                            <a href="participants.php?dq=1&id=<?php echo $p['id']; ?>" class="btn btn-danger" style="padding: 0.3rem 0.8rem; font-size: 0.8rem;" onclick="return confirm('Disqualify <?php echo $p['callsign']; ?>?');">DQ</a>
                        <?php endif; ?>
                        
                        <a href="participants.php?delete=1&id=<?php echo $p['id']; ?>" class="btn" style="padding: 0.3rem 0.8rem; font-size: 0.8rem; background: #6b7280; margin-left: 0.5rem;" onclick="return confirm('Permantly delete <?php echo $p['callsign']; ?> logs?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
