<?php
session_start();
require_once '../includes/db.php';

// Güvenlik: Giriş kontrolü
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }

$userId = $_SESSION['user_id'];
$pageTitle = "İşlem Sonucu";
$status = "error"; // Varsayılan durum
$message = "Geçersiz işlem isteği.";
$icon = "fa-times-circle";
$color = "danger";

// Sadece POST isteği geldiyse işlem yap
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accountId = $_POST['account_id'];

    // 1. Hesap Kontrolü (Sana mı ait?)
    $stmt = $pdo->prepare("
        SELECT a.Balance, a.AccountID, t.TypeName 
        FROM Accounts a
        JOIN Customers c ON a.CustomerID = c.CustomerID
        JOIN AccountTypes t ON a.TypeID = t.TypeID
        WHERE a.AccountID = ? AND c.UserID = ?
    ");
    $stmt->execute([$accountId, $userId]);
    $account = $stmt->fetch();

    if ($account) {
        $balance = (float)$account['Balance'];
        $accountName = $account['TypeName'];

        // 2. Bakiye Kontrolü
        if ($balance > 0) {
            $status = "warning";
            $message = "<b>İşlem Durduruldu:</b><br>Kapatmak istediğiniz hesapta hâlâ <b>" . number_format($balance, 2) . "</b> bakiye bulunmaktadır.<br>Lütfen önce parayı çekin veya transfer edin.";
            $icon = "fa-exclamation-triangle";
            $color = "warning";
        } else {
            // 3. Silme İşlemi
            try {
                $pdo->beginTransaction();

                // Hareketleri sil
                $pdo->prepare("DELETE FROM Transactions WHERE AccountID = ?")->execute([$accountId]);
                
                // Hesabı sil
                $pdo->prepare("DELETE FROM Accounts WHERE AccountID = ?")->execute([$accountId]);

                $pdo->commit();
                
                $status = "success";
                $message = "<b>" . strtoupper($accountName) . "</b><br>başarıyla kapatılmış ve portföyünüzden silinmiştir.";
                $icon = "fa-check-circle";
                $color = "success";

            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Veritabanı hatası oluştu: " . $e->getMessage();
            }
        }
    } else {
        $message = "Hesap bulunamadı veya bu işlem için yetkiniz yok.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?> - BANK of İSTÜN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif; background: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .result-card { max-width: 500px; width: 90%; border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); background: white; text-align: center; padding: 50px 30px; }
        .icon-box { font-size: 80px; margin-bottom: 30px; }
    </style>
</head>
<body>

    <div class="result-card animate__animated animate__fadeInUp">
        <div class="icon-box text-<?= $color ?>">
            <i class="fa <?= $icon ?>"></i>
        </div>
        
        <h2 class="fw-bold mb-3 text-<?= $color ?>">
            <?= $status == 'success' ? 'İşlem Başarılı' : ($status == 'warning' ? 'Dikkat' : 'Hata') ?>
        </h2>
        
        <p class="text-muted fs-5 mb-5">
            <?= $message ?>
        </p>

        <div class="d-grid gap-2">
            <a href="../index.php" class="btn btn-dark btn-lg fw-bold rounded-pill py-3">
                <i class="fa fa-home me-2"></i> ANA SAYFAYA DÖN
            </a>
            
            <?php if($status == 'warning'): ?>
                <a href="transfer_fast.php" class="btn btn-outline-dark btn-lg fw-bold rounded-pill py-3">
                    <i class="fa fa-paper-plane me-2"></i> PARAYI TRANSFER ET
                </a>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>