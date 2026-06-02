<?php
// config.php
session_start();

// Koneksi Database
$host = '127.0.0.1';
$dbname = 'ybdxcontest';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// ---------------------------------------------------------
// Kalkulasi Jadwal Kontes
// "Setiap hari Sabtu di minggu kedua bulan Januari"
// ---------------------------------------------------------
function getContestDate($year) {
    // Cari hari pertama bulan Januari
    $firstDay = new DateTime("$year-01-01 00:00:00", new DateTimeZone('UTC'));
    
    // Cari hari Sabtu pertama
    $firstSaturday = clone $firstDay;
    if ($firstSaturday->format('w') != 6) {
        $firstSaturday->modify('next Saturday');
    }
    
    // Sabtu kedua adalah tepat 7 hari setelah Sabtu pertama
    $secondSaturday = clone $firstSaturday;
    $secondSaturday->modify('+7 days');
    
    $start_time = $secondSaturday->format('Y-m-d 00:00:00');
    $end_time = $secondSaturday->format('Y-m-d 23:59:59');
    
    return [
        'start' => $start_time,
        'end' => $end_time
    ];
}

// Variabel global untuk tahun kontes berjalan (bisa dikonfigurasi)
$current_contest_year = date('Y');
$contest_schedule = getContestDate($current_contest_year);

// ---------------------------------------------------------
// Pengaturan Bilingual (ID / EN)
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
