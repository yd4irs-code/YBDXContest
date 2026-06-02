<?php
// admin/login.php
require_once __DIR__ . '/../config.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_role'] = $user['role'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager Login - YB DX Contest</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-box { width: 100%; max-width: 400px; }
    </style>
</head>
<body>
    <div class="glass-container login-box animate-fade-in">
        <h2 style="text-align: center; margin-bottom: 2rem;">YB DX Manager</h2>
        <?php if($error): ?>
            <div class="badge badge-danger" style="display:block; padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <form method="post" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn" style="width: 100%;">Login to Dashboard</button>
        </form>
        <div style="text-align: center; margin-top: 1rem;">
            <a href="../index.php" style="font-size: 0.9rem; color: var(--text-secondary);">Back to Public Site</a>
        </div>
    </div>
</body>
</html>
