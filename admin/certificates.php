<?php
// admin/certificates.php
require_once 'header.php';
global $pdo;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    $year = $_POST['year'];
    
    if (isset($_FILES['bg_image']) && $_FILES['bg_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['bg_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $upload_dir = __DIR__ . '/../assets/images/certs/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $filename = 'cert_bg_' . $year . '.' . $ext;
            $filepath = 'assets/images/certs/' . $filename;
            $full_path = $upload_dir . $filename;
            
            move_uploaded_file($_FILES['bg_image']['tmp_name'], $full_path);
            
            // Upsert DB
            $stmt = $pdo->prepare("INSERT INTO certificates_bg (year, image_path) VALUES (?, ?) ON DUPLICATE KEY UPDATE image_path = ?");
            $stmt->execute([$year, $filepath, $filepath]);
            
            $message = "Certificate background for $year uploaded successfully.";
        } else {
            $message = "Invalid file type. Only JPG and PNG are allowed.";
        }
    }
}

$stmt = $pdo->query("SELECT * FROM certificates_bg ORDER BY year DESC");
$bgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="glass-container animate-fade-in">
    <h2>Certificate Backgrounds</h2>
    <p style="color: var(--text-secondary); margin-bottom: 2rem;">Upload the background image used for rendering PDF certificates.</p>
    
    <?php if($message): ?>
        <div class="badge badge-success" style="display:block; padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div style="margin-bottom: 2rem; background: rgba(0,0,0,0.2); padding: 1.5rem; border-radius: 8px; max-width: 500px;">
        <h3>Upload New Background</h3>
        <form method="post" enctype="multipart/form-data" style="margin-top: 1rem;">
            <div class="form-group">
                <label>Year</label>
                <input type="number" name="year" value="<?php echo $current_contest_year; ?>" required>
            </div>
            <div class="form-group">
                <label>Background Image (A4 Landscape, JPG/PNG)</label>
                <input type="file" name="bg_image" accept=".jpg,.jpeg,.png" required>
            </div>
            <button type="submit" name="upload" class="btn">Upload Background</button>
        </form>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Image Path</th>
                    <th>Preview</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bgs as $bg): ?>
                <tr>
                    <td style="font-weight: bold; font-size: 1.2rem;"><?php echo $bg['year']; ?></td>
                    <td><?php echo htmlspecialchars($bg['image_path']); ?></td>
                    <td>
                        <img src="../<?php echo htmlspecialchars($bg['image_path']); ?>" alt="Preview" style="height: 60px; border-radius: 4px; border: 1px solid var(--glass-border);">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
