<?php
// Hataları Göster
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/db.php';

// Güvenlik
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) { 
    die('<div style="color:red; padding:20px;">HATA: Yetkisiz Giriş. <a href="../auth/login.php">Giriş Yapın</a></div>');
}

try {
    // TABLO İSİMLERİ KÜÇÜK HARF YAPILDI (employees, users, branches)
    // SQL sorgusunda hata varsa die() ile ekrana basılacak.
    $sql = "SELECT e.*, u.Email, b.BranchName
            FROM employees e
            JOIN users u ON e.UserID = u.UserID
            LEFT JOIN branches b ON e.BranchID = b.BranchID
            WHERE e.Title != 'CEO'
            ORDER BY b.BranchID ASC";
            
    $stmt = $pdo->query($sql);

    // Eğer tablo ismi yanlışsa veya SQL hatası varsa burada yakalarız
    if (!$stmt) {
        $err = $pdo->errorInfo();
        die("<h3>Veritabanı Sorgu Hatası:</h3> " . $err[2]);
    }

    $employees = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Kritik Hata: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Personel Listesi - Yönetim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold"><i class="fa fa-user-tie text-warning"></i> Personel Kadrosu</h2>
            <a href="dashboard.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Panele Dön</a>
        </div>

        <div class="card shadow border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Ad Soyad</th>
                                <th>Ünvan</th>
                                <th>Görev Yeri</th>
                                <th>İletişim (Email)</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($employees)): ?>
                                <tr><td colspan="6" class="text-center p-4">Hiç personel bulunamadı.</td></tr>
                            <?php else: ?>
                                <?php foreach($employees as $e): ?>
                                <tr>
                                    <td>#<?= $e['EmployeeID'] ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($e['FirstName'] . ' ' . $e['LastName']) ?></td>
                                    <td><?= htmlspecialchars($e['Title']) ?></td>
                                    <td><?= htmlspecialchars($e['BranchName'] ?? 'Atanmamış') ?></td>
                                    <td class="font-monospace text-primary"><?= htmlspecialchars($e['Email']) ?></td>
                                    <td><span class="badge bg-success">Aktif</span></td>
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