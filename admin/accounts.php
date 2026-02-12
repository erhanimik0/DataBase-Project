<?php
session_start();
require_once '../includes/db.php';

// Güvenlik: Sadece Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Hesapları ve Sahiplerini Çek (JOIN işlemi ile isimleri ve para birimlerini getiriyoruz)
try {
    $sql = "SELECT A.*, C.FirstName, C.LastName, T.TypeName, Cur.Code 
            FROM Accounts A
            JOIN Customers C ON A.CustomerID = C.CustomerID
            JOIN AccountTypes T ON A.TypeID = T.TypeID
            JOIN Currencies Cur ON A.CurrencyID = Cur.CurrencyID
            ORDER BY A.Balance DESC"; 
    $accounts = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    // Tablo hatası varsa boş dizi döndür
    $accounts = [];
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Açılan Hesaplar - Yönetim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Montserrat', sans-serif; background-color: #f8f9fa; }</style>
</head>
<body>

    <nav class="navbar navbar-dark bg-dark px-4 py-3">
        <a class="navbar-brand fw-bold" href="../index.php"><i class="fa fa-arrow-left me-2"></i> Yönetim Paneli</a>
        <span class="text-white">Hesap Yönetimi</span>
    </nav>

    <div class="container mt-5">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-success text-white p-4 rounded-top-4 d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-bold"><i class="fa fa-university me-2"></i> Aktif Hesaplar</h4>
                <span class="badge bg-white text-success fs-6"><?= count($accounts) ?> Hesap</span>
            </div>
            <div class="card-body p-0">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger m-3"><?= $error ?></div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="p-3">Hesap ID</th>
                                <th>Hesap Sahibi</th>
                                <th>Hesap Türü</th>
                                <th>IBAN</th>
                                <th class="text-end">Bakiye</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($accounts)): ?>
                                <tr>
                                    <td colspan="5" class="text-center p-4 text-muted">Henüz açılmış bir hesap bulunmuyor.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($accounts as $a): ?>
                                <tr>
                                    <td class="p-3 fw-bold text-muted">#<?= $a['AccountID'] ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($a['FirstName'] . ' ' . $a['LastName']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= $a['TypeName'] ?></span></td>
                                    <td class="font-monospace small"><?= $a['IBAN'] ?></td>
                                    <td class="text-end fw-bold text-success">
                                        <?= number_format($a['Balance'], 2) ?> <?= $a['Code'] ?>
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