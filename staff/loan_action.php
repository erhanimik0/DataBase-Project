<?php
session_start();
require_once '../includes/db.php';

// Güvenlik: Sadece Personel (Rol 2)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 2) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $loanID = $_POST['loan_id'];
    $action = $_POST['action'];

    try {
        if ($action == 'approve') {
            // 1. Kredi Bilgilerini Çek
            $stmt = $pdo->prepare("SELECT * FROM LoanRequests WHERE LoanID = ?");
            $stmt->execute([$loanID]);
            $loan = $stmt->fetch();

            // Sadece 'Beklemede' (Pending) olanları işle
            if ($loan && $loan['Status'] == 'Pending') {
                
                // İŞLEM BAŞLAT (Transaction) - Ya hepsi olur ya hiçbiri
                $pdo->beginTransaction();

                try {
                    // A. Kredi Statüsünü 'Approved' Yap
                    // (HATA ÇIKARAN 'ProcessedBy' SÜTUNUNU SİLDİM)
                    $update = $pdo->prepare("UPDATE LoanRequests SET Status = 'Approved' WHERE LoanID = ?");
                    $update->execute([$loanID]);

                    // B. Müşterinin TL Hesabını Bul
                    // Parayı yatırmak için müşterinin ilk TL hesabını buluyoruz.
                    $stmtAcc = $pdo->prepare("
                        SELECT AccountID 
                        FROM Accounts a 
                        JOIN AccountTypes t ON a.TypeID = t.TypeID 
                        WHERE a.CustomerID = ? AND t.Currency = 'TL' 
                        LIMIT 1
                    ");
                    $stmtAcc->execute([$loan['CustomerID']]);
                    $account = $stmtAcc->fetch();

                    if ($account) {
                        // C. Parayı Hesaba Ekle
                        $pdo->prepare("UPDATE Accounts SET Balance = Balance + ? WHERE AccountID = ?")
                            ->execute([$loan['Amount'], $account['AccountID']]);
                        
                        // D. İşlem Kaydı (Dekont) Oluştur
                        $desc = "Kredi Kullanımı: " . number_format($loan['Amount'], 2) . " TL onaylandı.";
                        $pdo->prepare("INSERT INTO Transactions (AccountID, TransactionType, Amount, Description, TransactionDate) VALUES (?, 'Kredi', ?, ?, NOW())")
                            ->execute([$account['AccountID'], $loan['Amount'], $desc]);
                    }

                    // Her şey yolundaysa kaydet
                    $pdo->commit();

                } catch (Exception $e) {
                    $pdo->rollBack(); // Hata varsa işlemi geri al
                    die("İşlem sırasında hata oluştu: " . $e->getMessage());
                }
            }

        } elseif ($action == 'reject') {
            // Reddetme İşlemi (ProcessedBy SİLİNDİ)
            $pdo->prepare("UPDATE LoanRequests SET Status = 'Rejected' WHERE LoanID = ?")
                ->execute([$loanID]);
        }

    } catch (PDOException $e) {
        die("Veritabanı Hatası: " . $e->getMessage());
    }
}

// İşlem bitince Personel Paneline geri dön
header("Location: dashboard.php");
exit;
?>