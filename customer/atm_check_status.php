<?php
// 1. ÇIKTI TAMPONUNU BAŞLAT (Her şeyi hafızada tut, ekrana basma)
ob_start();

ini_set('display_errors', 0); // Hataları ekrana basmayı kapat
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

$response = [];

try {
    // Veritabanı dosyasını kontrol et
    if (!file_exists('../includes/db.php')) {
        throw new Exception("db.php dosyası bulunamadı!");
    }
    require_once '../includes/db.php';

    // PDO bağlandı mı kontrol et
    if (!isset($pdo)) {
        throw new Exception("Veritabanı bağlantısı kurulamadı.");
    }

    $token = $_POST['token'] ?? '';
    
    if (empty($token)) {
        $response['status'] = 'NoToken';
    } else {
        $stmt = $pdo->prepare("SELECT Status FROM AtmSessions WHERE Token = ?");
        $stmt->execute([$token]);
        $status = $stmt->fetchColumn();

        $response['status'] = $status ? $status : 'NotFound';
    }

} catch (Exception $e) {
    $response['status'] = 'Error';
    $response['message'] = $e->getMessage();
}

// 2. TAMPONU TEMİZLE VE KAPAT (Aradaki tüm HTML/Hata çöplerini sil)
ob_end_clean();

// 3. SADECE TEMİZ JSON VERİSİNİ BAS
echo json_encode($response);
exit;
?>