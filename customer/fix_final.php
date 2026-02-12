<?php
// Dosya: customer/fix_final.php
require_once '../includes/db.php';

try {
    echo "<h3>Para Birimi Onarımı Başlatılıyor...</h3>";

    // 1. Önce Currency sütunu yoksa ekle
    try {
        $pdo->exec("ALTER TABLE AccountTypes ADD COLUMN Currency VARCHAR(5) DEFAULT 'TL'");
    } catch (Exception $e) { /* Sütun varsa devam et */ }

    // 2. Sert Güncelleme Komutları
    $sql1 = "UPDATE AccountTypes SET Currency = 'USD' WHERE TypeName LIKE '%Dolar%' OR TypeName LIKE '%USD%'";
    $sql2 = "UPDATE AccountTypes SET Currency = 'EUR' WHERE TypeName LIKE '%Euro%' OR TypeName LIKE '%EUR%'";
    $sql3 = "UPDATE AccountTypes SET Currency = 'GR'  WHERE TypeName LIKE '%Altın%' OR TypeName LIKE '%Gold%'";
    $sql4 = "UPDATE AccountTypes SET Currency = 'TL'  WHERE TypeName LIKE '%TL%' OR TypeName LIKE '%Lira%' OR TypeName LIKE '%Vadesiz Hesap%'";

    $pdo->exec($sql1); echo "Checking USD... OK<br>";
    $pdo->exec($sql2); echo "Checking EUR... OK<br>";
    $pdo->exec($sql3); echo "Checking Gold... OK<br>";
    $pdo->exec($sql4); echo "Checking TL... OK<br>";

    echo "<h2 style='color:green'>✅ DÜZELTME TAMAMLANDI! Artık bu sayfayı kapatabilirsin.</h2>";

} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>