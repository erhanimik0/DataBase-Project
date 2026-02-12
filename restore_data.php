<?php
// Dosya: restore_data.php
require_once 'includes/db.php';

$defaultPass = '1234'; 

try {
    echo "<h1>🛠️ Özel Müşteri Listesi Yükleniyor...</h1>";

    // 1. Önce Mevcut Müşterileri Temizle (Personel ve Boss kalsın)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    // Rolü 3 (Müşteri) olanları siliyoruz
    $pdo->exec("DELETE FROM Users WHERE RoleID = 3"); 
    $pdo->exec("TRUNCATE TABLE Customers");
    $pdo->exec("TRUNCATE TABLE Accounts"); // Hesapları da sıfırlayalım temiz olsun
    $pdo->exec("TRUNCATE TABLE Transactions");
    $pdo->exec("TRUNCATE TABLE LoanRequests");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<p>🗑️ Eski rastgele müşteriler temizlendi.</p>";

    // 2. SENİN ÖZEL LİSTEN
    $ozelListe = [
        ['Ahmet', 'Demir', 'ahmet@banka.com'],
        ['Mert', 'Gün', 'mert@banka.com'],
        ['Ensar', 'Sal', 'ensar@banka.com'],
        ['Testo', 'Taylan', 'testo@banka.com'],
        ['Mustafa', 'Sarıgül', 'mustafa@banka.com'],
        ['Sadettin', 'Saran', 'sadettin@banka.com'],
        ['Kontra', 'Volta', 'kontra@banka.com'],
        ['Ege', 'Fitness', 'ege@banka.com'],
        ['Kadir', 'Hoca', 'kadir@banka.com'],
        ['Muharrem', 'İmik', 'muharrem@banka.com']
    ];

    // 3. LİSTEYİ VERİTABANINA EKLE
    foreach ($ozelListe as $kisi) {
        $ad = $kisi[0];
        $soyad = $kisi[1];
        $email = $kisi[2];
        
        // Rastgele Şube Ata (1, 2 veya 3)
        $branchID = rand(1, 3); 
        $tckn = rand(10000000000, 99999999999);

        // A. Kullanıcı Oluştur
        $stmt = $pdo->prepare("INSERT INTO Users (RoleID, Email, Password) VALUES (3, ?, ?)");
        $stmt->execute([$email, $defaultPass]);
        $userID = $pdo->lastInsertId();

        // B. Müşteri Detayı Oluştur
        $stmt = $pdo->prepare("INSERT INTO Customers (UserID, BranchID, FirstName, LastName, TCKN, Phone) VALUES (?, ?, ?, ?, ?, '5551234567')");
        $stmt->execute([$userID, $branchID, $ad, $soyad, $tckn]);
        $custID = $pdo->lastInsertId();

        // C. Hesaplarını Aç (1 TL Hesabı, 1 Dolar Hesabı)
        
        // Vadesiz TL
        $ibanTL = "TR" . rand(1000,9999) . "0000" . rand(10000000, 99999999);
        $bakiyeTL = rand(5000, 100000);
        $pdo->prepare("INSERT INTO Accounts (CustomerID, TypeID, AccountNumber, Balance, Currency, BranchID) VALUES (?, 1, ?, ?, 'TL', ?)")->execute([$custID, $ibanTL, $bakiyeTL, $branchID]);

        // Vadesiz Dolar
        $ibanUSD = "US" . rand(1000,9999) . "0000" . rand(10000000, 99999999);
        $bakiyeUSD = rand(100, 5000);
        $pdo->prepare("INSERT INTO Accounts (CustomerID, TypeID, AccountNumber, Balance, Currency, BranchID) VALUES (?, 2, ?, ?, 'USD', ?)")->execute([$custID, $ibanUSD, $bakiyeUSD, $branchID]);

        echo "✅ $ad $soyad eklendi (Şube: $branchID) -> $email<br>";
    }

    echo "<h1>🎉 İŞLEM TAMAM! LİSTEN GERİ GELDİ.</h1>";
    echo "<p>Şifreleri: 1234</p>";

} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>