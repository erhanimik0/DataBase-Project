<?php
session_start();
require_once '../includes/db.php';

// Sadece Personel Girebilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Personel') {
    header("Location: ../auth/login.php");
    exit;
}

// Müşterileri ve E-posta adreslerini çekmek için JOIN işlemi
// Bu sorgu Customers ve Users tablolarını birleştirir.
// Users tablosu yok, her şey Customers tablosunda
$sql = "SELECT CustomerID, FirstName, LastName, IdentityNumber AS TCKN, PhoneNumber AS Phone, Email, BranchID 
        FROM Customers 
        ORDER BY FirstName ASC";

$musteriler = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Müşteri Listesi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fa fa-users text-primary"></i> Müşteri Listesi</h2>
            <div>
                <a href="add_customer.php" class="btn btn-success"><i class="fa fa-plus"></i> Yeni Müşteri Ekle</a>
                <a href="../index.php" class="btn btn-secondary">Ana Sayfa</a>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-body">
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Ad Soyad</th>
                            <th>TC Kimlik No</th>
                            <th>E-Posta</th>
                            <th>Telefon</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($musteriler as $m): ?>
                            <tr>
                                <td><?= $m['CustomerID'] ?></td>
                                <td class="fw-bold"><?= $m['FirstName'] ?> <?= $m['LastName'] ?></td>
                                <td><?= $m['TCKN'] ?></td>
                                <td><?= $m['Email'] ?></td>
                                <td><?= $m['Phone'] ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info text-white">Detay</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (empty($musteriler)): ?>
                    <p class="text-center mt-3 text-muted">Kayıtlı müşteri bulunamadı.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>