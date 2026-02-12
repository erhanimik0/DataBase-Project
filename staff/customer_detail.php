<?php
// Hataları Göster
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/db.php';

// Güvenlik: Sadece Personel (2) girebilir (Admin 1 de girebilir istenirse)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], [1, 2])) {
    header("Location: ../auth/login.php");
    exit;
}

// ID Kontrolü: Linkten ID gelmiş mi?
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: customers.php");
    exit;
}

$customerID = $_GET['id'];

// 1. Müşteri Kişisel Bilgilerini Çek (Users tablosundan E-postayı da alalım)
$stmt = $pdo->prepare("
    SELECT c.*, u.Email 
    FROM Customers c
    LEFT JOIN Users u ON c.UserID = u.UserID
    WHERE c.CustomerID = ?
");
$stmt->execute([$customerID]);
$cust = $stmt->fetch();

if (!$cust) {
    die("<div class='container mt-5 alert alert-danger'>Müşteri bulunamadı.</div>");
}

// 2. Müşterinin Hesaplarını Çek
$stmtAccounts = $pdo->prepare("
    SELECT a.*, t.TypeName, t.Currency 
    FROM Accounts a
    JOIN AccountTypes t ON a.TypeID = t.TypeID
    WHERE a.CustomerID = ?
    ORDER BY a.Balance DESC
");
$stmtAccounts->execute([$customerID]);
$accounts = $stmtAccounts->fetchAll();

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Müşteri Detayı - <?= htmlspecialchars($cust['FirstName']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif; background-color: #f0f2f5; }
        .avatar-circle {
            width: 100px; height: 100px; background-color: #e9ecef; color: #495057;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem; font-weight: bold; margin: 0 auto 15px;
        }
        .card { border:none; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 py-3">
        <div class="container">
            <span class="navbar-brand fw-bold">BANK of İSTÜN <span class="fw-normal fs-6">| Personel Paneli</span></span>
            <a href="customers.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                <i class="fa fa-arrow-left me-2"></i> Müşteri Listesine Dön
            </a>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row">
            
            <div class="col-md-4 mb-4">
                <div class="card text-center p-4 h-100">
                    <div class="avatar-circle bg-primary text-white">
                        <?= strtoupper(substr($cust['FirstName'], 0, 1) . substr($cust['LastName'], 0, 1)) ?>
                    </div>
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($cust['FirstName'] . ' ' . $cust['LastName']) ?></h4>
                    <p class="text-muted small mb-4">Müşteri No: #<?= $cust['CustomerID'] ?></p>
                    
                    <ul class="list-group list-group-flush text-start">
                        <li class="list-group-item px-0">
                            <small class="text-muted fw-bold d-block">E-POSTA</small>
                            <?= htmlspecialchars($cust['Email'] ?? 'E-posta yok') ?>
                        </li>
                        <li class="list-group-item px-0">
                            <small class="text-muted fw-bold d-block">TELEFON</small>
                            <?= htmlspecialchars($cust['Phone'] ?? '-') ?>
                        </li>
                        <li class="list-group-item px-0">
                            <small class="text-muted fw-bold d-block">TC KİMLİK NO</small>
                            <?= htmlspecialchars($cust['TCKN'] ?? '-') ?>
                        </li>
                        <li class="list-group-item px-0">
                            <small class="text-muted fw-bold d-block">ADRES</small>
                            <span class="small"><?= htmlspecialchars($cust['Address'] ?? 'Adres girilmemiş.') ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                        <h5 class="fw-bold mb-0"><i class="fa fa-wallet me-2 text-success"></i> Varlıklar & Hesaplar</h5>
                    </div>

                    <?php if(empty($accounts)): ?>
                        <div class="alert alert-warning text-center py-4">
                            <i class="fa fa-info-circle fa-2x mb-3"></i><br>
                            Bu müşteriye ait açık bir hesap bulunmamaktadır.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Hesap Türü</th>
                                        <th>IBAN / Hesap No</th>
                                        <th class="text-end">Bakiye</th>
                                        <th class="text-center">Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($accounts as $acc): ?>
                                    <?php 
                                        // Para Birimi Sembolü
                                        $symbol = '₺';
                                        if($acc['Currency'] == 'USD') $symbol = '$';
                                        elseif($acc['Currency'] == 'EUR') $symbol = '€';
                                        elseif($acc['Currency'] == 'GA' || $acc['Currency'] == 'GR') $symbol = 'gr';
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold d-block text-primary"><?= htmlspecialchars($acc['TypeName']) ?></span>
                                            <span class="badge bg-light text-dark border"><?= $acc['Currency'] ?></span>
                                        </td>
                                        <td class="font-monospace small text-muted">
                                            <?= htmlspecialchars($acc['IBAN'] ?? $acc['AccountNumber']) ?>
                                        </td>
                                        <td class="text-end fw-bold fs-5">
                                            <?= number_format($acc['Balance'], 2) ?> <small><?= $symbol ?></small>
                                        </td>
                                        <td class="text-center">
                                            <?php if(isset($acc['IsActive']) && $acc['IsActive'] == 0): ?>
                                                <span class="badge bg-danger">Pasif</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</body>
</html>