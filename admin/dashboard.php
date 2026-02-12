<?php
// Hataları Göster
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/db.php';

// Güvenlik: Sadece Boss (Rol 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: ../auth/login.php");
    exit;
}

// --- İSTATİSTİKLER ---

// 1. Toplam Mevduat (TL)
$stmtMoney = $pdo->query("SELECT SUM(Balance) FROM Accounts WHERE Currency = 'TL'");
$totalMoney = $stmtMoney->fetchColumn() ?: 0;

// 2. Aktif Müşteri
$stmtCust = $pdo->query("SELECT COUNT(*) FROM Customers");
$totalCust = $stmtCust->fetchColumn();

// 3. Personel
$stmtEmp = $pdo->query("SELECT COUNT(*) FROM Employees WHERE Title != 'CEO'");
$totalEmp = $stmtEmp->fetchColumn();

// 4. Şube Sayısı
$stmtBranch = $pdo->query("SELECT COUNT(*) FROM Branches");
$totalBranch = $stmtBranch->fetchColumn();

// --- TABLO VERİSİ (ŞUBELER DAHİL) ---
// Not: Transactions -> Accounts -> Customers -> Branches zinciriyle şubeyi buluyoruz.
$sqlLogs = "SELECT t.*, c.FirstName, c.LastName, t.TransactionType, b.BranchName
            FROM Transactions t
            JOIN Accounts a ON t.AccountID = a.AccountID
            JOIN Customers c ON a.CustomerID = c.CustomerID
            LEFT JOIN Branches b ON c.BranchID = b.BranchID
            ORDER BY t.TransactionDate DESC 
            LIMIT 10";
$stmtLogs = $pdo->query($sqlLogs);
$logs = $stmtLogs->fetchAll();

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yönetim Paneli - BANK of İSTÜN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { display: flex; min-height: 100vh; background-color: #f0f2f5; font-family: 'Montserrat', sans-serif; }
        
        /* SIDEBAR */
        .sidebar { width: 260px; background: #212529; color: #fff; flex-shrink: 0; padding-top: 20px; }
        .sidebar-brand { padding: 0 20px 20px; font-size: 1.2rem; font-weight: 700; border-bottom: 1px solid #343a40; margin-bottom: 20px; display:flex; align-items:center; }
        .sidebar-menu ul { list-style: none; padding: 0; }
        .sidebar-menu li a { display: block; padding: 12px 20px; color: #adb5bd; text-decoration: none; transition: 0.3s; font-size: 0.95rem; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: #0d6efd; color: #fff; }
        .sidebar-menu li a i { width: 25px; }

        /* MAIN */
        .main-content { flex-grow: 1; padding: 30px; overflow-y: auto; }
        
        /* CARDS */
        .stat-card { border: none; border-radius: 12px; color: #fff; position: relative; overflow: hidden; height: 140px; }
        .stat-card .card-body { position: relative; z-index: 2; }
        .stat-card .icon-bg { position: absolute; right: -10px; bottom: -10px; font-size: 6rem; opacity: 0.2; transform: rotate(-15deg); z-index: 1; }
        
        .bg-gradient-primary { background: linear-gradient(45deg, #0d6efd, #0a58ca); }
        .bg-gradient-success { background: linear-gradient(45deg, #198754, #146c43); }
        .bg-gradient-warning { background: linear-gradient(45deg, #ffc107, #ffca2c); color: #000; }
        .bg-gradient-info    { background: linear-gradient(45deg, #0dcaf0, #3dd5f3); color: #000; }

        /* TABLE */
        .table-card { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

    <div class="sidebar d-none d-lg-block">
        <div class="sidebar-brand">
            <img src="../logo.png" height="30" class="me-2" style="filter: brightness(0) invert(1);" onerror="this.style.display='none'">
            BANK of İSTÜN
        </div>
        <nav class="sidebar-menu">
            <ul>
                <li><a href="dashboard.php" class="active"><i class="fa fa-home"></i> Genel Bakış</a></li>
                <li><a href="customers.php"><i class="fa fa-users"></i> Müşteriler</a></li>
                <li><a href="employees.php"><i class="fa fa-id-badge"></i> Personeller</a></li>
                <li><a href="branches.php"><i class="fa fa-building"></i> Şubeler</a></li>
                <li><a href="#" class="text-muted"><i class="fa fa-file-alt"></i> İşlem Kayıtları</a></li>
                <li class="mt-5"><a href="../auth/logout.php" class="text-danger"><i class="fa fa-sign-out-alt"></i> Güvenli Çıkış</a></li>
            </ul>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h3 class="fw-bold text-dark m-0">Hoş Geldiniz, Büyük Patron</h3>
                <p class="text-muted small">Bankanızın güncel durum özeti aşağıdadır.</p>
            </div>
            <div class="d-flex align-items-center bg-white px-3 py-2 rounded shadow-sm">
                <i class="fa fa-calendar-alt text-primary me-2"></i>
                <span class="fw-bold small"><?= date("d.m.Y") ?></span>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card bg-gradient-success">
                    <div class="card-body p-4">
                        <h6 class="text-uppercase mb-2 opacity-75">TOPLAM MEVDUAT (TL)</h6>
                        <h2 class="fw-bold mb-0"><?= number_format($totalMoney, 2) ?> ₺</h2>
                        <i class="fa fa-coins icon-bg"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card bg-gradient-primary">
                    <div class="card-body p-4">
                        <h6 class="text-uppercase mb-2 opacity-75">AKTİF MÜŞTERİ</h6>
                        <h2 class="fw-bold mb-0"><?= $totalCust ?></h2>
                        <a href="customers.php" class="text-white small text-decoration-none mt-2 d-inline-block">Detayları Gör <i class="fa fa-arrow-right ms-1"></i></a>
                        <i class="fa fa-users icon-bg"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card bg-gradient-warning">
                    <div class="card-body p-4">
                        <h6 class="text-uppercase mb-2 opacity-75">PERSONEL SAYISI</h6>
                        <h2 class="fw-bold mb-0"><?= $totalEmp ?></h2>
                        <a href="employees.php" class="text-dark small text-decoration-none mt-2 d-inline-block">Kadro Listesi <i class="fa fa-arrow-right ms-1"></i></a>
                        <i class="fa fa-user-tie icon-bg"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card bg-gradient-info">
                    <div class="card-body p-4">
                        <h6 class="text-uppercase mb-2 opacity-75">ŞUBE SAYISI</h6>
                        <h2 class="fw-bold mb-0"><?= $totalBranch ?></h2>
                        <i class="fa fa-building icon-bg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card table-card">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="m-0 fw-bold text-dark"><i class="fa fa-history text-primary me-2"></i> Son Banka Hareketleri</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Tarih</th>
                                <th>Müşteri</th>
                                <th>Şube</th>
                                <th>İşlem Türü</th>
                                <th>Açıklama</th>
                                <th>Tutar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="fa fa-info-circle fa-2x text-muted mb-3 d-block"></i>
                                        Henüz kaydedilmiş bir işlem hareketi bulunmuyor.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($logs as $log): ?>
                                <tr>
                                    <td class="ps-4 fw-bold small text-muted">
                                        <?= date("d.m.Y H:i", strtotime($log['TransactionDate'])) ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2 text-primary fw-bold" style="width:35px; height:35px;">
                                                <?= substr($log['FirstName'],0,1) . substr($log['LastName'],0,1) ?>
                                            </div>
                                            <?= htmlspecialchars($log['FirstName'] . ' ' . $log['LastName']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            <?= htmlspecialchars($log['BranchName'] ?? 'Merkez') ?>
                                        </span>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($log['TransactionType']) ?></span></td>
                                    <td class="small text-muted"><?= htmlspecialchars(mb_strimwidth($log['Description'], 0, 40, "...")) ?></td>
                                    <td class="fw-bold <?= $log['Amount'] < 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= number_format(abs($log['Amount']), 2) ?> 
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