<?php
require_once '../includes/db.php';

try {
    echo "<h1>Veritabanı Onarımı Başlatılıyor...</h1>";

    // 1. Sütun Yoksa Ekle (Hata almamak için kontrol ediyoruz)
    try {
        $pdo->exec("ALTER TABLE AccountTypes ADD COLUMN Currency VARCHAR(5) DEFAULT 'TL'");
        echo "<p>✅ Currency sütunu eklendi.</p>";
    } catch (Exception $e) {
        echo "<p>ℹ️ Sütun zaten var, devam ediliyor.</p>";
    }

    // 2. Verileri Güncelle
    $updates = [
        "UPDATE AccountTypes SET Currency = 'USD' WHERE TypeName LIKE '%Dolar%'",
        "UPDATE AccountTypes SET Currency = 'EUR' WHERE TypeName LIKE '%Euro%'",
        "UPDATE AccountTypes SET Currency = 'GR'  WHERE TypeName LIKE '%Altın%'",
        "UPDATE AccountTypes SET Currency = 'TL'  WHERE TypeName LIKE '%TL%' OR TypeName LIKE '%Vadesiz Hesap%'"
    ];

    foreach ($updates as $sql) {
        $stmt = $pdo->exec($sql);
        echo "<p>👉 Güncelleme yapıldı: <code>$sql</code> (Etkilenen: $stmt)</p>";
    }

    echo "<h2 style='color:green'>İŞLEM TAMAMLANDI! Bu sayfayı kapatabilirsin.</h2>";

} catch (PDOException $e) {
    echo "<h2 style='color:red'>HATA: " . $e->getMessage() . "</h2>";
}
?>