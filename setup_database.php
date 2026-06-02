<?php
// setup_database.php

$host = '127.0.0.1';
$user = 'root';
$pass = ''; // bawaan XAMPP emang polosan tanpa password bro

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Bikin DB baru kalo misalnya belum ada, sikat!
    $pdo->exec("CREATE DATABASE IF NOT EXISTS ybdxcontest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE ybdxcontest");
    
    echo "Mantap! Database 'ybdxcontest' udah kebuat/kepilih ya.\n";

    // 1. Tabel users (Buat login Admin/Manajer nih)
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('manager', 'admin') DEFAULT 'manager',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Masukin admin bawaan (default) biar ga kosong banget
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT IGNORE INTO users (username, password, role) VALUES ('admin', '$hash', 'admin')");

    // 2. Tabel smtp_config (Buat nyeting email)
    $pdo->exec("CREATE TABLE IF NOT EXISTS smtp_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        host VARCHAR(255),
        port INT,
        username VARCHAR(255),
        password VARCHAR(255),
        encryption VARCHAR(50),
        from_email VARCHAR(255),
        from_name VARCHAR(255),
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Masukin data dummy aja dulu kalo tabelnya masih kosong melompong
    $pdo->exec("INSERT IGNORE INTO smtp_config (id, host, port, username, password, encryption, from_email, from_name) VALUES (1, 'smtp.example.com', 587, 'user@example.com', 'secret', 'tls', 'contest@example.com', 'YB DX Contest Robot')");

    // 3. Tabel participants (Daftar peserta yang ikutan)
    $pdo->exec("CREATE TABLE IF NOT EXISTS participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        callsign VARCHAR(50) NOT NULL,
        email VARCHAR(255) NOT NULL,
        access_code VARCHAR(10) NOT NULL,
        category_op VARCHAR(50),
        category_band VARCHAR(50),
        category_power VARCHAR(50),
        category_mode VARCHAR(50) DEFAULT 'SSB',
        category_overlay VARCHAR(50),
        club_name VARCHAR(255),
        continent VARCHAR(50),
        country VARCHAR(100),
        year INT NOT NULL,
        disqualified TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 4. Tabel cabrillo_logs (Buat nampung file log cabrillo)
    $pdo->exec("CREATE TABLE IF NOT EXISTS cabrillo_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        participant_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        raw_score INT DEFAULT 0,
        total_qso INT DEFAULT 0,
        total_dupes INT DEFAULT 0,
        total_points INT DEFAULT 0,
        total_multiplier INT DEFAULT 0,
        status ENUM('pending', 'adjudicated') DEFAULT 'pending',
        soapbox TEXT,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE
    )");

    // 5. Tabel qsos (Daftar komunikasi yang sukses)
    $pdo->exec("CREATE TABLE IF NOT EXISTS qsos (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        participant_id INT NOT NULL,
        freq VARCHAR(20),
        mode VARCHAR(10),
        qso_date DATE,
        qso_time TIME,
        sent_call VARCHAR(50),
        sent_rst VARCHAR(10),
        sent_exch VARCHAR(50),
        rcvd_call VARCHAR(50),
        rcvd_rst VARCHAR(10),
        rcvd_exch VARCHAR(50),
        status ENUM('valid', 'busted', 'dupe', 'unique', 'nil', 'xqso') DEFAULT 'valid',
        points INT DEFAULT 0,
        is_multiplier TINYINT(1) DEFAULT 0,
        FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE
    )");

    // 6. Tabel plaques (Plakat/Piala nih)
    $pdo->exec("CREATE TABLE IF NOT EXISTS plaques (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(100),
        sponsor VARCHAR(255),
        year INT NOT NULL
    )");

    // 7. Tabel plaque_winners (Siapa aja yang menang piala)
    $pdo->exec("CREATE TABLE IF NOT EXISTS plaque_winners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plaque_id INT NOT NULL,
        participant_id INT,
        year INT NOT NULL,
        FOREIGN KEY (plaque_id) REFERENCES plaques(id) ON DELETE CASCADE,
        FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE SET NULL
    )");

    // 8. Tabel certificates_bg (Background sertifikat)
    $pdo->exec("CREATE TABLE IF NOT EXISTS certificates_bg (
        id INT AUTO_INCREMENT PRIMARY KEY,
        year INT NOT NULL UNIQUE,
        image_path VARCHAR(255) NOT NULL
    )");

    // 9. Tabel committee (Panitia/Pengurus kontes)
    $pdo->exec("CREATE TABLE IF NOT EXISTS committee (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        callsign VARCHAR(50),
        position VARCHAR(100) NOT NULL,
        year INT NOT NULL
    )");

    echo "Sip lah! Semua tabel udah beres dibikin!\n";

} catch (PDOException $e) {
    die("Waduh, setting database-nya gagal bro: " . $e->getMessage() . "\n");
}
