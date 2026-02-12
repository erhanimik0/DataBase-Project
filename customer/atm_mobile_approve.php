<?php
// 1. TÜM HATALARI AÇ (Ekrana bassın)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h3>1. İşlem Başladı...</h3>";

session_start();
echo "<h3>2. Oturum Başlatıldı.</h3>";

// Veritabanı bağlantısını Try-Catch içine alalım
try {
    // Dosya yolunu kontrol et, bazen ../ çalışmaz, tam yol gerekebilir ama şimdilik böyle deneyelim.
    if (!file_exists('../includes/db.php')) {
        die("<h1 style='color:red'>HATA: db.php dosyası bulunamadı!</h1>");
    }
    require_once '../includes/db.php';
    echo "<h3>3. Veritabanı Bağlantısı Başarılı.</h3>";
} catch (Exception $e) {
    die("<h1 style='color:red'>Veritabanı Hatası: " . $e->getMessage() . "</h1>");
}

// Token kontrolü
if (!isset($_GET['token'])) { 
    die("<h1 style='color:red'>HATA: Linkte Token (Şifre) yok!</h1>"); 
}

$token = $_GET['token'];
echo "<h3>4. Token Alındı: " . htmlspecialchars($token) . "</h3>";

try {
    // Veritabanında bu token var mı?
    $stmtCheck = $pdo->prepare("SELECT * FROM AtmSessions WHERE Token = ?");
    $stmtCheck->execute([$token]);
    $session = $stmtCheck->fetch();

    if (!$session) {
        die("<h1 style='color:red'>HATA: Bu kod geçersiz veya veritabanında yok!</h1>");
    }
    echo "<h3>5. Token Veritabanında Bulundu. Durum: " . $session['Status'] . "</h3>";

    // Durumu 'Approved' yap
    $stmt = $pdo->prepare("UPDATE AtmSessions SET Status = 'Approved' WHERE Token = ?");
    $stmt->execute([$token]);
    echo "<h3>6. Güncelleme Komutu Gönderildi.</h3>";
    
    $success = true;

} catch (Exception $e) {
    die("<h1 style='color:red'>Sorgu Hatası: " . $e->getMessage() . "</h1>");
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobil Onay</title>
    <style>
        body { background-color: #fff; color: #000; font-family: sans-serif; text-align: center; padding: 20px; }
        .box { border: 2px solid #333; padding: 20px; margin-top: 20px; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="box">
        <?php if(isset($success) && $success): ?>
            <h1 style="color: green; font-size: 50px;">✔</h1>
            <h2 style="color: green;">ONAYLANDI!</h2>
            <p>Bilgisayar ekranına bakabilirsin.</p>
        <?php else: ?>
            <h1 style="color: red;">X</h1>
            <h2 style="color: red;">BİR SORUN VAR</h2>
        <?php endif; ?>
    </div>
</body>
</html>