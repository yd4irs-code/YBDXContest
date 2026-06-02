<?php
// admin/plaques.php
require_once 'header.php';
global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $name = $_POST['name'];
        $category = $_POST['category'];
        $sponsor = $_POST['sponsor'];
        $year = $_POST['year'];
        $stmt = $pdo->prepare("INSERT INTO plaques (name, category, sponsor, year) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $category, $sponsor, $year]);
    } elseif (isset($_POST['delete'])) {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM plaques WHERE id = ?")->execute([$id]);
    }
    header("Location: plaques.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM plaques ORDER BY year DESC, id ASC");
$plaques = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="glass-container animate-fade-in">
    <h2>Manage Plaques & Awards</h2>
    
    <div style="margin-bottom: 2rem; background: rgba(0,0,0,0.2); padding: 1.5rem; border-radius: 8px;">
        <h3>Add New Plaque</h3>
        <form method="post" style="display: flex; gap: 1rem; align-items: flex-end; margin-top: 1rem; flex-wrap: wrap;">
            <div style="flex: 2; min-width: 200px;">
                <label>Plaque Name</label>
                <input type="text" name="name" required placeholder="e.g. World High Score">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label>Category</label>
                <input type="text" name="category" required placeholder="e.g. SINGLE-OP ALL HIGH">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label>Sponsor</label>
                <input type="text" name="sponsor" required>
            </div>
            <div style="width: 100px;">
                <label>Year</label>
                <input type="number" name="year" value="<?php echo $current_contest_year; ?>" required>
            </div>
            <div>
                <button type="submit" name="add" class="btn">Add</button>
            </div>
        </form>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Plaque Name</th>
                    <th>Category</th>
                    <th>Sponsor</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plaques as $p): ?>
                <tr>
                    <td><?php echo $p['year']; ?></td>
                    <td style="font-weight: bold; color: gold;"><?php echo htmlspecialchars($p['name']); ?></td>
                    <td><?php echo htmlspecialchars($p['category']); ?></td>
                    <td><?php echo htmlspecialchars($p['sponsor']); ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                            <button type="submit" name="delete" class="btn btn-danger" style="padding: 0.2rem 0.5rem; font-size: 0.8rem;" onclick="return confirm('Delete this plaque?');">Del</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
