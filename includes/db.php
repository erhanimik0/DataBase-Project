<?php
// includes/db.php

$host = '127.0.0.1'; 

$dbname = 'banka_db_erhan';
$username = 'root';
$password = 'root';
$port = 8889;

try {
    // PDO bağlantısına port bilgisini (port=$port) ekledik
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Hata modunu aktif et
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Varsayılan fetch modunu ayarla
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // echo "Bağlantı Başarılı!"; // Bunu testi geçtikten sonra silebilirsin.
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>