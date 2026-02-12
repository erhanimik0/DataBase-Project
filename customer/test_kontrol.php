<?php
// Hataları açalım
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/db.php';

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) { die("Lütfen önce giriş yapın."); }

$userID = $_SESSION['user_id'];

echo "<div style='font-family:monospace; padding:20px; background:#f0f0f0;'>";
echo "<h2>🔍 DETAYLI HESAP KONTROLÜ</h2>";
echo "<hr>";

// 1. Müşteri ID'sini bulalım
$stmt = $pdo->prepare("SELECT * FROM Customers WHERE UserID = ?");
$stmt->execute([$userID]);
$cust = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cust) {
    die("<h3 style='color:red'>HATA: Customers tablosunda kaydınız bulunamadı!</h3>");
}

echo "<strong>Giriş Yapan UserID:</strong> " . $userID . "<br>";
echo "<strong>Eşleşen MüşteriID (CustomerID):</strong> " . $cust['CustomerID'] . "<br>";
echo "<strong>Ad Soyad:</strong> " . $cust['FirstName'] . " " . $cust['LastName'] . "<br><br>";

// 2. Bu müşterinin hesaplarını dökelim
echo "<h3>📋 SAHİP OLUNAN HESAPLAR (Accounts Tablosu)</h3>";
$stmtAcc = $pdo->prepare("
    SELECT a.AccountID, a.AccountNumber, a.Balance, a.TypeID, t.TypeName, t.Currency 
    FROM Accounts a
    LEFT JOIN AccountTypes t ON a.TypeID = t.TypeID
    WHERE a.CustomerID = ?
");
$stmtAcc->execute([$cust['CustomerID']]);
$accounts = $stmtAcc->fetchAll(PDO::FETCH_ASSOC);

if (count($accounts) == 0) {
    echo "<span style='color:red'>HİÇ HESAP BULUNAMADI!</span><br>";
} else {
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse; background:white; width:100%;'>";
    echo "<tr style='background:#ddd;'><th>Account ID</th><th>Hesap No</th><th>Bakiye</th><th>Type ID (Önemli)</th><th>Hesap Türü Adı (DB'den)</th><th>Para Birimi</th></tr>";
    
    foreach ($accounts as $acc) {
        // Vadeli kontrolü yapalım
        $isVadeli = (strpos($acc['TypeName'], 'Vadeli') !== false) ? "<span style='color:green; font-weight:bold'>EVET</span>" : "<span style='color:red'>HAYIR</span>";
        
        echo "<tr>";
        echo "<td>" . $acc['AccountID'] . "</td>";
        echo "<td>" . $acc['AccountNumber'] . "</td>";
        echo "<td>" . $acc['Balance'] . "</td>";
        echo "<td style='font-weight:bold; color:blue;'>" . $acc['TypeID'] . "</td>";
        echo "<td>" . $acc['TypeName'] . " <br><small>(Vadeli mi? : $isVadeli)</small></td>";
        echo "<td>" . $acc['Currency'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<br><hr>";
echo "<h3>🛠 MEVCUT HESAP TÜRLERİ (AccountTypes Tablosu)</h3>";
$stmtTypes = $pdo->query("SELECT * FROM AccountTypes");
$types = $stmtTypes->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5' style='border-collapse:collapse; background:white;'>";
echo "<tr style='background:#ddd;'><th>ID</th><th>Tür Adı</th><th>Açıklama</th></tr>";
foreach($types as $t){
    echo "<tr><td>".$t['TypeID']."</td><td>".$t['TypeName']."</td><td>".$t['Description']."</td></tr>";
}
echo "</table>";

echo "</div>";
?>