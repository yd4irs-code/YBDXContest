<?php
// index.php
require_once 'config.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$allowed_pages = [
    'home', 
    'submit_log', 
    'received_logs', 
    'raw_scores', 
    'final_scores', 
    'club_scores', 
    'soapbox', 
    'statistics', 
    'committee',
    'certificate_download'
];

if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}

require_once 'views/header.php';

$view_path = "views/{$page}.php";
if (file_exists($view_path)) {
    require_once $view_path;
} else {
    echo "<div class='glass-container text-center'>";
    echo "<h2>Page Not Found / Under Construction</h2>";
    echo "</div>";
}

require_once 'views/footer.php';
?>
