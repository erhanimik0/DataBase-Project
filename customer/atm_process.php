<?php
session_start();
require_once '../includes/db.php';

// Güvenlik: Giriş yapılmış mı?
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }

$userId = $_SESSION['user_id'];
$alertType = "danger";
$msg = "İşlem yapılamadı.";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accID = $_POST['account_id'];
    $amount = (float)$_POST['amount'];
    $action = $_POST['action'];

    if ($amount <= 0) {
        $msg = "Lütfen geçerli bir tutar giriniz.";
    } else {
        try {
            // Veritabanı hatası olursa yakalamak için try bloğu açıyoruz
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($action == 'withdraw') {
                // --- PARA ÇEKME ---
                
                // 1. Bakiye Kontrolü
                $stmt = $pdo->prepare("SELECT Balance FROM Accounts WHERE AccountID = ? AND CustomerID = ?");
                $stmt->execute([$accID, $userId]);
                $currentBalance = $stmt->fetchColumn();

                if ($currentBalance >= $amount) {
                    
                    // 2. Bakiyeyi Düş
                    $pdo->prepare("UPDATE Accounts SET Balance = Balance - ? WHERE AccountID = ?")->execute([$amount, $accID]);
                    
                    // 3. Log Kaydı (HATA BURADA ÇIKIYOR OLABİLİR)
                    // Not: TransactionType sütunu string mi yoksa ID mi bekliyor?
                    // Genelde bu tür tablolarda 'Withdraw' yazısı yerine ID (örn: 2) kullanılır.
                    // Şimdilik eski kodunu deniyoruz ama hata verirse göreceğiz.
                    $sql = "INSERT INTO Transactions (AccountID, TransactionType, Amount, Description, TransactionDate) 
                            VALUES (?, 'Withdraw', ?, 'ATM Para Çekme', NOW())";
                    
                    $pdo->prepare($sql)->execute([$accID, -$amount]);
                    
                    $msg = "Paranız hazırlanıyor...<br>Lütfen bölmeden alınız.";
                    $alertType = "success";
                } else {
                    $msg = "Yetersiz Bakiye! <br> Mevcut Bakiyeniz: " . number_format($currentBalance, 2);
                }

            } elseif ($action == 'deposit') {
                // --- PARA YATIRMA ---
                
                // 1. Bakiyeyi Artır
                $pdo->prepare("UPDATE Accounts SET Balance = Balance + ? WHERE AccountID = ?")->execute([$amount, $accID]);
                
                // 2. Log Kaydı
                $sql = "INSERT INTO Transactions (AccountID, TransactionType, Amount, Description, TransactionDate) 
                        VALUES (?, 'Deposit', ?, 'ATM Para Yatırma', NOW())";
                
                $pdo->prepare($sql)->execute([$accID, $amount]);
                
                $msg = "Paranız başarıyla hesabınıza yatırıldı.";
                $alertType = "success";
            }

        } catch (PDOException $e) {
            // HATA VARSA EKRANA BAS (Kullanıcıya göstermek için)
            $alertType = "danger";
            $msg = "<b>VERİTABANI HATASI:</b><br>" . $e->getMessage();
            
            // Eğer sütun hatası ise ipucu verelim
            if(strpos($e->getMessage(), 'Unknown column') !== false) {
                $msg .= "<br><br><b>İPUCU:</b> 'Transactions' tablosunda kodda yazdığımız sütunlardan biri yok. Lütfen sütun isimlerini kontrol et.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İşlem Sonucu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif; background: #212529; color: white; height: 100vh; display: flex; align-items: center; justify-content: center; text-align: center; }
        .result-card { background: #343a40; padding: 50px; border-radius: 20px; box-shadow: 0 0 50px rgba(0,0,0,0.5); max-width: 500px; width: 90%; }
    </style>
</head>
<body>

    <div class="result-card">
        <?php if($alertType == 'success'): ?>
            <div class="mb-4"><i class="fa fa-check-circle fa-5x text-success"></i></div>
            <h2 class="fw-bold text-success mb-3">İşlem Başarılı</h2>
        <?php else: ?>
            <div class="mb-4"><i class="fa fa-times-circle fa-5x text-danger"></i></div>
            <h2 class="fw-bold text-danger mb-3">İşlem Başarısız</h2>
        <?php endif; ?>
        
        <p class="fs-5 text-light opacity-75 mb-5"><?= $msg ?></p>
        
        <div class="d-grid gap-2">
            <a href="atm.php" class="btn btn-light fw-bold py-3 rounded-pill">Başka İşlem Yap</a>
            <a href="../index.php" class="btn btn-outline-light fw-bold py-3 rounded-pill">Kartı İade Al</a>
        </div>
    </div>

</body>
</html>