<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $senderID = $_POST['sender_account_id'];
    $targetNo = $_POST['target_account_no'];
    $amount = (float)$_POST['amount'];
    $desc = $_POST['description'];
    
    // Sabit İşlem Ücreti
    $fee = 6.37;
    $totalDeduction = $amount + $fee;

    if ($amount <= 0) { header("Location: transfer.php?error=Geçersiz tutar!"); exit; }

    try {
        // 1. Gönderenin Bakiyesini Kontrol Et
        $stmt = $pdo->prepare("SELECT Balance FROM Accounts WHERE AccountID = ?");
        $stmt->execute([$senderID]);
        $balance = $stmt->fetchColumn();

        if ($balance < $totalDeduction) {
            header("Location: transfer.php?error=Yetersiz Bakiye! (İşlem ücreti: 6.37 TL)");
            exit;
        }

        // 2. Alıcıyı Bul
        $stmt2 = $pdo->prepare("SELECT AccountID FROM Accounts WHERE IBAN = ?");
        $stmt2->execute([$targetNo]);
        $targetID = $stmt2->fetchColumn();

        if (!$targetID) {
            header("Location: transfer.php?error=Alıcı hesap bulunamadı!");
            exit;
        }

        // 3. TRANSFERİ YAP (Transaction ile)
        $pdo->beginTransaction();

        // Gönderenden düş (Tutar + Masraf)
        $pdo->prepare("UPDATE Accounts SET Balance = Balance - ? WHERE AccountID = ?")->execute([$totalDeduction, $senderID]);
        
        // Alıcıya ekle (Sadece Tutar)
        $pdo->prepare("UPDATE Accounts SET Balance = Balance + ? WHERE AccountID = ?")->execute([$amount, $targetID]);

        // Log Kayıtları
        $sqlLog = "INSERT INTO Transactions (AccountID, TransactionType, Amount, Description, Fee, TransactionDate) VALUES (?, ?, ?, ?, ?, NOW())";
        
        // Gönderen Logu
        $pdo->prepare($sqlLog)->execute([$senderID, 'Transfer', -$amount, "Giden: $desc (Alıcı: $targetNo)", $fee]);
        $lastTransID = $pdo->lastInsertId(); // Dekont için ID'yi al

        // Alıcı Logu
        $pdo->prepare($sqlLog)->execute([$targetID, 'Transfer', $amount, "Gelen: $desc", 0]);

        $pdo->commit();

        // DEKONTA YÖNLENDİR
        header("Location: receipt.php?id=" . $lastTransID);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: transfer.php?error=İşlem başarısız: " . $e->getMessage());
    }
}
?>