<?php
// Hataları Göster
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/db.php';

// Güvenlik: Giriş yapılmış mı?
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$userID = $_SESSION['user_id'];
$accountID = $_GET['id'] ?? 0;

try {
    // 1. HESAP SAHİPLİĞİNİ DOĞRULA VE HESAP BİLGİLERİNİ ÇEK
    // Bu sorgu çok önemlidir: Hesabın (AccountID) sahibi olan Müşterinin (CustomerID),
    // şu an giriş yapan Kullanıcı (UserID) olup olmadığına bakar.
    $stmt = $pdo->prepare("
        SELECT a.*, t.TypeName, t.Currency, c.FirstName, c.LastName
        FROM Accounts a
        JOIN Customers c ON a.CustomerID = c.CustomerID
        JOIN AccountTypes t ON a.TypeID = t.TypeID
        WHERE a.AccountID = ? AND c.UserID = ?
    ");
    $stmt->execute([$accountID, $userID]);
    $account = $stmt->fetch();

    if (!$account) {
        // Eğer hesap bulunamazsa veya bu kullanıcıya ait değilse:
        die('<div style="text-align:center; margin-top:50px; font-family:sans-serif;">
                <h2 style="color:red;">Hata: Hesap Bulunamadı</h2>
                <p>Bu hesabı görüntüleme yetkiniz yok veya hesap mevcut değil.</p>
                <a href="../index.php" style="background:#333; color:#fff; padding:10px 20px; text-decoration:none; border-radius:5px;">Ana Sayfaya Dön</a>
             </div>');
    }

    // 2. HESAP HAREKETLERİNİ (TRANSACTIONS) ÇEK
    $stmtTrans = $pdo->prepare("
        SELECT * FROM Transactions 
        WHERE AccountID = ? 
        ORDER BY TransactionDate DESC
    ");
    $stmtTrans->execute([$accountID]);
    $transactions = $stmtTrans->fetchAll();

} catch (PDOException $e) {
    die("Veritabanı Hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Hesap Hareketleri - BANK of İSTÜN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Montserrat', sans-serif; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .table-header { background: #343a40; color: white; }
    </style>
</head>
<body>

<nav class="navbar bg-white border-bottom px-4 py-3 shadow-sm sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="../index.php">
            <img src="../logo.png" height="30" class="me-2" onerror="this.style.display='none'"> BANK of İSTÜN
        </a>
        <a href="../index.php" class="btn btn-dark btn-sm rounded-pill px-4 fw-bold">
            <i class="fa fa-arrow-left me-2"></i> GERİ DÖN
        </a>
    </div>
</nav>

<div class="container py-5">
    
    <div class="card mb-4 bg-primary text-white shadow">
        <div class="card-body p-4 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="text-uppercase text-white-50 fw-bold mb-1">HESAP DETAYI</h6>
                <h2 class="fw-bold m-0"><?= htmlspecialchars($account['TypeName']) ?></h2>
                <div class="mt-2 font-monospace bg-white text-primary px-2 py-1 rounded d-inline-block">
                    <?= $account['AccountNumber'] ?>
                </div>
            </div>
            <div class="text-end">
                <small class="d-block text-white-50">GÜNCEL BAKİYE</small>
                <h1 class="display-5 fw-bold mb-0">
                    <?= number_format($account['Balance'], 2) ?> 
                    <span class="fs-4"><?= $account['Currency'] ?></span>
                </h1>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="m-0 fw-bold text-dark"><i class="fa fa-list-ul me-2"></i> Hesap Hareketleri</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Tarih</th>
                            <th>İşlem Türü</th>
                            <th>Açıklama</th>
                            <th class="text-end">Tutar</th>
                            <th class="text-center">Dekont</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fa fa-history fa-2x mb-3 d-block opacity-25"></i>
                                    Bu hesapta henüz hiçbir işlem hareketi yok.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td class="ps-4 text-muted small">
                                        <?= date("d.m.Y H:i", strtotime($t['TransactionDate'])) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary fw-normal"><?= htmlspecialchars($t['TransactionType']) ?></span>
                                    </td>
                                    <td class="small text-dark">
                                        <?= htmlspecialchars($t['Description']) ?>
                                    </td>
                                    <td class="text-end fw-bold">
                                        <?php if ($t['Amount'] > 0): ?>
                                            <span class="text-success">+<?= number_format($t['Amount'], 2) ?> <?= $account['Currency'] ?></span>
                                        <?php else: ?>
                                            <span class="text-danger"><?= number_format($t['Amount'], 2) ?> <?= $account['Currency'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="receipt.php?tid=<?= $t['TransactionID'] ?>" target="_blank" class="btn btn-sm btn-light border shadow-sm text-secondary">
                                            <i class="fa fa-file-invoice"></i>
                                        </a>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>