<?php
// Hataları Göster
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/db.php';

// Güvenlik: Personel (Rol 2) değilse at
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 2) {
    header("Location: ../auth/login.php");
    exit;
}

$userID = $_SESSION['user_id'];

// 1. Personelin Şubesini Bul
// DÜZELTME: 'BranchID' yerine 'e.BranchID' yazıldı (Ambiguous hatası çözümü)
$stmt = $pdo->prepare("SELECT e.BranchID, b.BranchName 
                       FROM Employees e 
                       JOIN Branches b ON e.BranchID = b.BranchID 
                       WHERE e.UserID = ?");
$stmt->execute([$userID]);
$personel = $stmt->fetch();

if (!$personel) {
    die("Hata: Personel kaydı veya şube bilgisi bulunamadı.");
}

$branchID = $personel['BranchID'];

// 2. Şubedeki TÜM Müşterileri Çek
$sql = "SELECT c.*, u.Email, 
        IFNULL((SELECT SUM(Balance) FROM Accounts a WHERE a.CustomerID = c.CustomerID AND a.Currency='TL'), 0) as TotalBal
        FROM Customers c
        JOIN Users u ON c.UserID = u.UserID
        WHERE c.BranchID = ?
        ORDER BY c.FirstName ASC";
        
$stmt = $pdo->prepare($sql);
$stmt->execute([$branchID]);
$customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Şube Müşterileri - <?= htmlspecialchars($personel['BranchName']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; font-family: 'Montserrat', sans-serif; }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fa fa-users text-primary"></i> Şube Müşterileri</h2>
        <a href="dashboard.php" class="btn btn-secondary rounded-pill px-4"><i class="fa fa-arrow-left me-2"></i> Panele Dön</a>
    </div>

    <div class="card shadow border-0 rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Müşteri Adı</th>
                            <th>TCKN</th>
                            <th>İletişim</th>
                            <th>Telefon</th>
                            <th>Toplam Varlık (TL)</th>
                            <th class="text-end pe-4">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($customers)): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">Kayıtlı müşteri bulunamadı.</td></tr>
                        <?php else: ?>
                            <?php foreach($customers as $c): ?>
                            <tr>
                                <td class="ps-4 fw-bold">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-3" style="width:40px; height:40px; font-size:1.2rem;">
                                            <?= strtoupper(substr($c['FirstName'], 0, 1)) ?>
                                        </div>
                                        <?= htmlspecialchars($c['FirstName'] . ' ' . $c['LastName']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($c['TCKN']) ?></td>
                                <td class="text-muted small"><?= htmlspecialchars($c['Email']) ?></td>
                                <td><?= htmlspecialchars($c['Phone']) ?></td>
                                <td class="fw-bold text-success"><?= number_format($c['TotalBal'], 2) ?> ₺</td>
                                <td class="text-end pe-4">
    <a href="customer_detail.php?id=<?= $c['CustomerID'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">Detay</a>
</td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>