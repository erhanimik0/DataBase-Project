<?php
session_start();
require_once '../includes/db.php';

// Güvenlik
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../auth/login.php");
    exit;
}

$statusTitle = "";
$statusMsg = "";
$statusIcon = "";
$alertType = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $appID = $_POST['id'];
    $custID = $_POST['customer_id'];
    $action = $_POST['action'];

    try {
        if ($action == 'approve') {
            
            // 1. İstenen Hesap Türünü Öğren
            $stmtApp = $pdo->prepare("SELECT RequestedTypeID FROM AccountApplications WHERE ApplicationID = ?");
            $stmtApp->execute([$appID]);
            $reqType = $stmtApp->fetchColumn() ?: 1; 

            // 2. Durumu Güncelle
            $stmt = $pdo->prepare("UPDATE AccountApplications SET Status = 'Approved' WHERE ApplicationID = ?");
            $stmt->execute([$appID]);

            // 3. Para Birimini (CurrencyID) Belirle
            // 1:TL, 2:USD, 3:EUR, 4:XAU(Altın)
            $currencyID = 1; // Varsayılan TRY
            
            if ($reqType == 3 || $reqType == 4) {
                $currencyID = 2; // USD
            } elseif ($reqType == 5 || $reqType == 6) {
                $currencyID = 3; // EUR
            } elseif ($reqType == 7 || $reqType == 8) {
                $currencyID = 4; // XAU (Hem Vadesiz Altın hem Vadesiz Gram -> Altın Kuru)
            }

            // 4. Hesabı Aç
            $newIBAN = 'TR' . rand(10, 99) . '00062' . rand(10000000000, 99999999999);
            
            $sqlAcc = "INSERT INTO Accounts (CustomerID, BranchID, CurrencyID, TypeID, Balance, IBAN) 
                       VALUES (?, 1, ?, ?, 0.00, ?)";
            $pdo->prepare($sqlAcc)->execute([$custID, $currencyID, $reqType, $newIBAN]);

            // Mesaj İçin Hesap Adını Çek
            $typeName = $pdo->query("SELECT TypeName FROM AccountTypes WHERE TypeID = $reqType")->fetchColumn();

            $statusTitle = "İşlem Başarılı!";
            $statusMsg = "Müşteriye <b>$typeName</b> başarıyla açıldı.";
            $statusIcon = "fa-check-circle";
            $alertType = "success";

        } elseif ($action == 'reject') {
            $pdo->prepare("UPDATE AccountApplications SET Status = 'Rejected' WHERE ApplicationID = ?")->execute([$appID]);
            
            $statusTitle = "Başvuru Reddedildi";
            $statusMsg = "Hesap açma talebi reddedildi.";
            $statusIcon = "fa-times-circle";
            $alertType = "danger";
        }

    } catch (PDOException $e) {
        $statusTitle = "Hata";
        $statusMsg = $e->getMessage();
        $statusIcon = "fa-exclamation-triangle";
        $alertType = "warning";
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
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .result-card { width: 100%; max-width: 500px; border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .icon-box { font-size: 80px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="result-card card text-center p-5">
        <div class="mb-4"><img src="../logo.png" height="60" style="object-fit: contain;"></div>
        <div class="card-body">
            <div class="icon-box text-<?= $alertType ?>"><i class="fa <?= $statusIcon ?>"></i></div>
            <h2 class="fw-bold text-<?= $alertType ?> mb-3"><?= $statusTitle ?></h2>
            <p class="text-muted fs-5 mb-4"><?= $statusMsg ?></p>
            <a href="requests.php" class="btn btn-dark w-100 py-3 fw-bold rounded-pill">Listeye Dön</a>
        </div>
    </div>
</body>
</html>