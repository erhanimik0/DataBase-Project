<?php
require_once 'includes/db.php';

try {
    // DÜZELTME: b.BranchName'i en başa aldık. 
    // Böylece FETCH_GROUP bunu anahtar olarak kullanacak ve EmployeeID'yi silmeyecek.
    $sql = "SELECT b.BranchName, e.*, u.Email 
            FROM Employees e 
            JOIN Branches b ON e.BranchID = b.BranchID 
            JOIN Users u ON e.UserID = u.UserID 
            ORDER BY b.BranchID ASC, e.EmployeeID ASC";
            
    $stmt = $pdo->query($sql);
    $staff = $stmt->fetchAll(PDO::FETCH_GROUP);

} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Personel Listesi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .branch-header { background: #0d6efd; color: white; padding: 15px; border-radius: 10px 10px 0 0; }
        .table-responsive { background: white; border-radius: 0 0 10px 10px; }
    </style>
</head>
<body class="p-5">

<div class="container">
    <h2 class="fw-bold mb-4 text-center"><i class="fa fa-users"></i> BANK of İSTÜN - Şube Personel Listesi</h2>
    <p class="text-center text-muted mb-5">Tüm personellerin varsayılan şifresi: <b>1234</b></p>

    <?php if (empty($staff)): ?>
        <div class="alert alert-warning text-center">Hiç çalışan kaydı bulunamadı.</div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($staff as $branchName => $employees): ?>
                <div class="col-md-12 mb-5">
                    <div class="card shadow-sm border-0">
                        <div class="branch-header d-flex justify-content-between align-items-center">
                            <h4 class="m-0 fw-bold"><i class="fa fa-building me-2"></i> <?= htmlspecialchars($branchName) ?></h4>
                            <span class="badge bg-white text-primary"><?= count($employees) ?> Çalışan</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">ID</th>
                                            <th>Ad Soyad</th>
                                            <th>Ünvan</th>
                                            <th>E-Posta (Giriş)</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($employees as $emp): ?>
                                            <tr>
                                                <td class="ps-4 text-muted small">#<?= $emp['EmployeeID'] ?></td>
                                                <td class="fw-bold"><?= htmlspecialchars($emp['FirstName'] . ' ' . $emp['LastName']) ?></td>
                                                <td><span class="badge bg-secondary"><?= htmlspecialchars($emp['Title']) ?></span></td>
                                                <td class="font-monospace text-primary"><?= htmlspecialchars($emp['Email']) ?></td>
                                                <td><span class="badge bg-success">Aktif</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="text-center mt-4">
        <a href="index.php" class="btn btn-dark btn-lg px-5 rounded-pill shadow">Ana Sayfaya Dön</a>
    </div>
</div>

</body>
</html>