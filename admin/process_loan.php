<?php
session_start();
require_once '../includes/db.php';

// Güvenlik: Sadece Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Değişkenleri Hazırla (HTML içinde kullanacağız)
$statusTitle = "";
$statusMsg = "";
$statusIcon = "";
$alertType = ""; // success, danger, warning

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $loanID = $_POST['loan_id'];
    $custID = $_POST['customer_id'];
    $amount = $_POST['amount'];
    $action = $_POST['action'];

    try {
        if ($action == 'approve') {
            // 1. Krediyi Onayla (Status = Approved)
            $stmt = $pdo->prepare("UPDATE Loans SET Status = 'Approved' WHERE LoanID = ?");
            $stmt->execute([$loanID]);

            // 2. PARAYI HESABA AKTAR (Müşterinin Vadesiz TL Hesabını Bul: TypeID=1)
            $stmtAcc = $pdo->prepare("SELECT AccountID FROM Accounts WHERE CustomerID = ? AND TypeID = 1 LIMIT 1");
            $stmtAcc->execute([$custID]);
            $accountID = $stmtAcc->fetchColumn();

            if ($accountID) {
                // Hesap varsa bakiyeyi artır
                $pdo->prepare("UPDATE Accounts SET Balance = Balance + ? WHERE AccountID = ?")->execute([$amount, $accountID]);

                // İşlem Kaydı (Log)
                $desc = "Kredi Kullanımı (Kredi #" . $loanID . ")";
                $sqlTrans = "INSERT INTO Transactions (AccountID, TransactionType, Amount, Description, TransactionDate) 
                             VALUES (?, 'Deposit', ?, ?, NOW())";
                $pdo->prepare($sqlTrans)->execute([$accountID, $amount, $desc]);

                // BAŞARILI MESAJI
                $statusTitle = "Kredi Onaylandı!";
                $statusMsg = "Kredi başvurusu onaylandı ve <b>" . number_format($amount, 2) . " TL</b> tutarındaki kredi müşterinin hesabına başarıyla yatırıldı.";
                $statusIcon = "fa-check-circle";
                $alertType = "success";
            } else {
                // Kredi onaylandı ama hesap yoksa
                $statusTitle = "Kısmi Onay (Dikkat)";
                $statusMsg = "Kredi statüsü 'Onaylandı' yapıldı ANCAK müşterinin Vadesiz TL hesabı bulunamadığı için para bakiyeye eklenemedi.";
                $statusIcon = "fa-exclamation-triangle";
                $alertType = "warning";
            }

        } elseif ($action == 'reject') {
            // REDDETME İŞLEMİ
            $stmt = $pdo->prepare("UPDATE Loans SET Status = 'Rejected' WHERE LoanID = ?");
            $stmt->execute([$loanID]);
            
            $statusTitle = "Başvuru Reddedildi";
            $statusMsg = "Kredi başvurusu reddedildi. Müşteriye herhangi bir ödeme yapılmadı.";
            $statusIcon = "fa-times-circle";
            $alertType = "danger";
        }

    } catch (PDOException $e) {
        // HATA DURUMU
        $statusTitle = "Sistem Hatası";
        $statusMsg = "Veritabanı işlemi sırasında bir hata oluştu: " . $e->getMessage();
        $statusIcon = "fa-bomb";
        $alertType = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İşlem Sonucu - BANK of İSTÜN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif; background-color: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .result-card { width: 100%; max-width: 500px; border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .icon-box { font-size: 80px; margin-bottom: 20px; }
    </style>
</head>
<body>

    <div class="result-card card text-center p-5">
        <div class="mb-4">
            <img src="../logo.png" alt="BANK of İSTÜN" height="60" style="object-fit: contain;">
            <h4 class="fw-bold mt-2 text-dark">BANK of İSTÜN</h4>
        </div>

        <div class="card-body">
            <div class="icon-box text-<?= $alertType ?>">
                <i class="fa <?= $statusIcon ?>"></i>
            </div>
            
            <h2 class="fw-bold text-<?= $alertType ?> mb-3"><?= $statusTitle ?></h2>
            <p class="text-muted fs-5 mb-4"><?= $statusMsg ?></p>

            <a href="loan_requests.php" class="btn btn-dark w-100 py-3 fw-bold rounded-pill mb-3">
                <i class="fa fa-arrow-left me-2"></i> Kredi Listesine Dön
            </a>
            
            <div>
                <a href="../index.php" class="text-muted small text-decoration-none">Yönetim Paneline Git</a>
            </div>
        </div>
    </div>

</body>
</html>