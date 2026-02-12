<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) { header("Location: ../auth/login.php"); exit; }

// Şubeleri ve Her Şubenin Toplam Mevduatını Çek
$sql = "SELECT b.*, 
        (SELECT COUNT(*) FROM Employees e WHERE e.BranchID = b.BranchID) as StaffCount,
        (SELECT COUNT(*) FROM Customers c WHERE c.BranchID = b.BranchID) as CustCount,
        (SELECT SUM(a.Balance) FROM Accounts a WHERE a.BranchID = b.BranchID AND a.Currency = 'TL') as TotalDeposit
        FROM Branches b
        ORDER BY b.BranchID ASC";
$branches = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Şube Raporları - Yönetim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold"><i class="fa fa-building text-info"></i> Şube Performans Raporu</h2>
            <a href="dashboard.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Panele Dön</a>
        </div>

        <div class="row">
            <?php foreach($branches as $b): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between">
                            <span><?= $b['BranchName'] ?></span>
                            <span class="badge bg-primary rounded-pill">ID: <?= $b['BranchID'] ?></span>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 text-center py-3 bg-light rounded">
                                <small class="text-muted d-block">TOPLAM MEVDUAT</small>
                                <h3 class="fw-bold text-success mb-0"><?= number_format($b['TotalDeposit'], 2) ?> ₺</h3>
                            </div>
                            
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fa fa-map-marker-alt text-danger me-2"></i> Şehir</span>
                                    <span class="fw-bold"><?= $b['City'] ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fa fa-users text-primary me-2"></i> Müşteri Sayısı</span>
                                    <span class="badge bg-secondary"><?= $b['CustCount'] ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fa fa-user-tie text-warning me-2"></i> Personel</span>
                                    <span class="badge bg-secondary"><?= $b['StaffCount'] ?></span>
                                </li>
                                <li class="list-group-item">
                                    <small class="text-muted"><i class="fa fa-phone me-1"></i> <?= $b['Phone'] ?></small>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>