<?php
// config.php
session_start();

// Nyambungin ke Database nih bosku
$host = '127.0.0.1';
$dbname = 'c2contest';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Waduh, koneksi database jebol: " . $e->getMessage());
}

// ---------------------------------------------------------
// Ngitung Jadwal Kontes
// "Tiap hari Sabtu di minggu kedua bulan Januari yak"
// ---------------------------------------------------------
function getContestDate($year) {
    // Cari tanggal 1 Januari dulu
    $firstDay = new DateTime("$year-01-01 00:00:00", new DateTimeZone('UTC'));
    
    // Cari hari Sabtu pertama, gas!
    $firstSaturday = clone $firstDay;
    if ($firstSaturday->format('w') != 6) {
        $firstSaturday->modify('next Saturday');
    }
    
    // Nah, Sabtu kedua tuh pas banget 7 hari abis Sabtu pertama
    $secondSaturday = clone $firstSaturday;
    $secondSaturday->modify('+7 days');
    
    $start_time = $secondSaturday->format('Y-m-d 00:00:00');
    $end_time = $secondSaturday->format('Y-m-d 23:59:59');
    
    return [
        'start' => $start_time,
        'end' => $end_time
    ];
}

// Variabel global buat nentuin tahun kontes sekarang (bisa diutak-atik sih)
$current_contest_year = date('Y');
$contest_schedule = getContestDate($current_contest_year);

// ---------------------------------------------------------
// Seting Bahasa (ID / EN) biar keren
// ---------------------------------------------------------
if (isset($_GET['lang'])) {
    if (in_array($_GET['lang'], ['id', 'en'])) {
        $_SESSION['lang'] = $_GET['lang'];
    }
}
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'id';

function t($key) {
    global $lang;
    $translations = [
        'id' => [
            'home' => 'Beranda',
            'submit_log' => 'Unggah Log Cabrillo',
            'received_logs' => 'Log Diterima',
            'raw_scores' => 'Skor Sementara',
            'final_scores' => 'Hasil Akhir',
            'club_scores' => 'Klasemen Klub',
            'soapbox' => 'Komentar Peserta',
            'statistics' => 'Statistik',
            'all_time_high' => 'Rekor Sepanjang Masa',
            'committee' => 'Pengurus',
            'admin_login' => 'Masuk Manajer',
            'language' => 'Bahasa'
        ],
        'en' => [
            'home' => 'Home',
            'submit_log' => 'Submit Cabrillo Log',
            'received_logs' => 'Received Logs',
            'raw_scores' => 'Raw Scores',
            'final_scores' => 'Final Results',
            'club_scores' => 'Club Competition',
            'soapbox' => 'Soapbox',
            'statistics' => 'Statistics',
            'all_time_high' => 'All Time High',
            'committee' => 'Committee',
            'admin_login' => 'Manager Login',
            'language' => 'Language'
        ]
    ];
    
    return isset($translations[$lang][$key]) ? $translations[$lang][$key] : $key;
}
?>
