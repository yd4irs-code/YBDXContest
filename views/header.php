<?php
// views/header.php
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YB DX Contest Robot</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="wrapper animate-fade-in">
    <header>
        <!-- Global Banner Header -->
        <img src="assets/banner-YBDXContest.jpg" alt="YB DX Contest Banner" class="banner-img" onerror="this.style.display='none'">
        
        <!-- Navigation -->
        <nav>
            <a href="index.php?page=home"><?php echo t('home'); ?></a>
            <a href="index.php?page=submit_log"><?php echo t('submit_log'); ?></a>
            <a href="index.php?page=received_logs"><?php echo t('received_logs'); ?></a>
            <a href="index.php?page=raw_scores"><?php echo t('raw_scores'); ?></a>
            <a href="index.php?page=final_scores"><?php echo t('final_scores'); ?></a>
            <a href="index.php?page=club_scores"><?php echo t('club_scores'); ?></a>
            <a href="index.php?page=soapbox"><?php echo t('soapbox'); ?></a>
            <a href="index.php?page=statistics"><?php echo t('statistics'); ?></a>
            <a href="index.php?page=committee"><?php echo t('committee'); ?></a>
            <a href="index.php?page=certificate_download" style="color: gold;"><i class="fas fa-award"></i> Certificate</a>
            
            <div style="flex-grow: 1;"></div>
            
            <a href="admin/index.php" class="btn" style="padding: 0.5rem 1rem; border-radius: 30px; font-size: 0.85rem;"><?php echo t('admin_login'); ?></a>
            
            <!-- Language Switcher -->
            <a href="?<?php echo http_build_query(array_merge($_GET, ['lang' => 'id'])); ?>" <?php if($lang == 'id') echo 'class="active"'; ?>>ID</a>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['lang' => 'en'])); ?>" <?php if($lang == 'en') echo 'class="active"'; ?>>EN</a>
        </nav>
    </header>
    
    <main>
