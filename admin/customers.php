<?php
// Hataları Ekrana Bas (Beyaz ekranı önler)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/db.php';

// --- HATA AYIKLAMA MODU ---
// Eğer bu sayfadaysanız ve hala Login ekranına gidiyorsanız, bu dosya KAYDEDİLMEMİŞ demektir.
// Çünkü bu kodda "header(Location:...)" komutu YOKTUR.

// 1. Oturum Kontrolü
if (!isset($_SESSION['user_id'])) {
    die('<div class="alert alert-danger m-5"><h1>HATA: Oturum Başlatılamadı!</h1><p>Dosyanın en başında boşluk olabilir veya giriş yapılmamış. <br><a href="../auth/login.php">Tekrar Giriş Yap</a></p></div>');
}

// 2. Yetki Kontrolü
if ($_SESSION['role'] != 1) {
    die('<div class="alert alert-danger m-5"><h1>HATA: Yetkisiz Giriş!</h1><p>Rol ID: ' . $_SESSION['role'] . ' (Beklenen: 1 - Boss)</p></div>');
}

try {
    // 3. Verileri Çek
    // Tablo isimlerini 'setup_hierarchy.php' ile uyumlu (Users, Customers, Branches) yapıyoruz.
    $sql = "SELECT c.*, u.Email, b.BranchName,
            IFNULL((SELECT SUM(Balance) FROM Accounts a WHERE a.CustomerID = c.CustomerID AND a.Currency = 'TL'), 0) as TotalBalance
            FROM Customers c
            JOIN Users u ON c.UserID = u.UserID
            LEFT JOIN Branches b ON c.BranchID = b.BranchID
            ORDER BY c.CustomerID DESC";
            
    $stmt = $pdo->query($sql);
    
    if (!$stmt) {
        $err = $pdo->errorInfo();
        die("<h1>Veritabanı Sorgu Hatası:</h1>" . $err[2]);
    }

    $customers = $stmt->fetchAll();

} catch (PDOException $e) {
    die("<h1>Kritik Hata:</h1>" . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Müşteri Listesi - Yönetim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold"><i class="fa fa-users text-primary"></i> Müşteri Listesi</h2>
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
                                <th>TCKN</th>
                                <th>Şube</th>
                                <th>E-Posta</th>
                                <th>Varlık (TL)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($customers)): ?>
                                <tr><td colspan="6" class="text-center p-4">Kayıtlı müşteri bulunamadı.</td></tr>
                            <?php else: ?>
                                <?php foreach($customers as $c): ?>
                                <tr>
                                    <td>#<?= $c['CustomerID'] ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($c['FirstName'] . ' ' . $c['LastName']) ?></td>
                                    <td><?= htmlspecialchars($c['TCKN']) ?></td>
                                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($c['BranchName'] ?? 'Merkez') ?></span></td>
                                    <td><?= htmlspecialchars($c['Email']) ?></td>
                                    <td class="fw-bold text-success"><?= number_format($c['TotalBalance'], 2) ?> ₺</td>
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