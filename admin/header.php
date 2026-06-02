<?php
// admin/header.php
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager Dashboard - YB DX Contest</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: rgba(0, 0, 0, 0.4);
            border-right: 1px solid var(--glass-border);
            padding: 2rem 1rem;
            backdrop-filter: blur(10px);
        }
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }
        .sidebar a {
            display: block;
            padding: 1rem;
            color: var(--text-primary);
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(59, 130, 246, 0.2);
            color: var(--accent-hover);
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <div class="sidebar">
            <h3 style="text-align: center; margin-bottom: 2rem; color: var(--accent-hover);">Manager<br>Dashboard</h3>
            <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Dashboard</a>
            <a href="participants.php" class="<?php echo $current_page == 'participants.php' ? 'active' : ''; ?>">Participants & DQ</a>
            <a href="committee.php" class="<?php echo $current_page == 'committee.php' ? 'active' : ''; ?>">Committee</a>
            <a href="plaques.php" class="<?php echo $current_page == 'plaques.php' ? 'active' : ''; ?>">Plaques</a>
            <a href="certificates.php" class="<?php echo $current_page == 'certificates.php' ? 'active' : ''; ?>">Certificates BG</a>
            <a href="settings_smtp.php" class="<?php echo $current_page == 'settings_smtp.php' ? 'active' : ''; ?>">SMTP Config</a>
            <a href="adjudicate.php" class="<?php echo $current_page == 'adjudicate.php' ? 'active' : ''; ?>" style="border: 1px solid var(--accent-color);">Run Adjudication</a>
            <a href="logout.php" style="color: var(--danger); margin-top: 2rem;">Logout</a>
            <a href="../index.php" style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 1rem;">&larr; View Public Site</a>
        </div>
        <div class="main-content">
