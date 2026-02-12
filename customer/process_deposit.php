<?php
// 1. Çıktı Tamponlamayı Başlat (Tüm çıktıları hafızada tut, ekrana basma)
ob_start();

// 2. Hata Gösterimini Kapat (JSON bozulmasın diye)
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once '../includes/db.php';

// JSON başlığı
header('Content-Type: application/json; charset=utf-8');

$response = [];

try {
    if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Yetkisiz erişim veya hatalı istek.');
    }

    $userId = $_SESSION['user_id'];
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $currency = isset($_POST['currency']) ? $_POST['currency'] : '';

    if ($amount <= 0) {
        throw new Exception('Lütfen geçerli bir tutar giriniz.');
    }

    $pdo->beginTransaction();

    // A. Müşteri ID Bul
    $stmtCust = $pdo->prepare("SELECT CustomerID FROM Customers WHERE UserID = ?");
    $stmtCust->execute([$userId]);
    $cust = $stmtCust->fetch();
    if (!$cust) throw new Exception("Müşteri profili bulunamadı.");
    
    $customerID = $cust['CustomerID'];

    // B. Hesap Türü ID'lerini Belirle (Vadesiz -> Vadeli)
    // Veritabanındaki ID'ler:
    // Vadesiz: TL=1, USD=2, EUR=3
    // Vadeli:  TL=5, USD=6, EUR=7
    $sourceTypeID = 0;
    $targetTypeID = 0;

    if ($currency == 'TL') { $sourceTypeID = 1; $targetTypeID = 5; }
    elseif ($currency == 'USD') { $sourceTypeID = 2; $targetTypeID = 6; }
    elseif ($currency == 'EUR') { $sourceTypeID = 3; $targetTypeID = 7; }
    else { throw new Exception("Geçersiz para birimi."); }

    // C. Kaynak Hesabı (Vadesiz) Kontrol Et
    $stmtSource = $pdo->prepare("SELECT AccountID, Balance FROM Accounts WHERE CustomerID = ? AND TypeID = ?");
    $stmtSource->execute([$customerID, $sourceTypeID]);
    $sourceAcc = $stmtSource->fetch();

    if (!$sourceAcc) {
        throw new Exception("Vadesiz $currency hesabınız bulunamadı.");
    }
    if ($sourceAcc['Balance'] < $amount) {
        throw new Exception("Yetersiz bakiye! Vadesiz hesabınızda bu kadar para yok.");
    }

    // D. Hedef Hesabı (Vadeli) Kontrol Et
    $stmtTarget = $pdo->prepare("SELECT AccountID, Balance FROM Accounts WHERE CustomerID = ? AND TypeID = ?");
    $stmtTarget->execute([$customerID, $targetTypeID]);
    $targetAcc = $stmtTarget->fetch();

    if (!$targetAcc) {
        throw new Exception("Vadeli $currency hesabınız aktif görünmüyor.");
    }

    // --- TRANSFER İŞLEMİ ---

    // 1. Vadesizden Çek
    $newSourceBalance = $sourceAcc['Balance'] - $amount;
    $updateSource = $pdo->prepare("UPDATE Accounts SET Balance = ? WHERE AccountID = ?");
    $updateSource->execute([$newSourceBalance, $sourceAcc['AccountID']]);

    // 2. Vadeliye Yatır
    $newTargetBalance = $targetAcc['Balance'] + $amount;
    $updateTarget = $pdo->prepare("UPDATE Accounts SET Balance = ? WHERE AccountID = ?");
    $updateTarget->execute([$newTargetBalance, $targetAcc['AccountID']]);

    // 3. Dekontları (Logları) Yaz
    // Vadesiz Hesabın Hareketine İşle
    $logOut = $pdo->prepare("INSERT INTO Transactions (AccountID, TransactionType, Amount, TransactionDate, Description) VALUES (?, 'Vadeli Açılış', ?, NOW(), ?)");
    $logOut->execute([$sourceAcc['AccountID'], -$amount, "Vadeli Hesaba Aktarım ($amount $currency)"]);

    // Vadeli Hesabın Hareketine İşle
    $logIn = $pdo->prepare("INSERT INTO Transactions (AccountID, TransactionType, Amount, TransactionDate, Description) VALUES (?, 'Mevduat', ?, NOW(), ?)");
    $logIn->execute([$targetAcc['AccountID'], $amount, "Vadesiz Hesaptan Gelen ($amount $currency)"]);

    $pdo->commit();
    $response = ['status' => 'success', 'message' => 'İşlem başarıyla tamamlandı.'];

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

// 3. Çıktı Tamponunu Temizle (Burası Çok Önemli: Önceden basılan her şeyi siler)
ob_end_clean();

// 4. Sadece temiz JSON bas
echo json_encode($response);
exit;
?>