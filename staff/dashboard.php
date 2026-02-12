<?php
// Hataları Göster
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/db.php';

// Güvenlik
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 2) {
    header("Location: ../auth/login.php");
    exit;
}

$userID = $_SESSION['user_id'];

// 1. Personel Bilgisi
$stmt = $pdo->prepare("SELECT e.*, b.BranchName 
                       FROM Employees e 
                       JOIN Branches b ON e.BranchID = b.BranchID 
                       WHERE e.UserID = ?");
$stmt->execute([$userID]);
$personel = $stmt->fetch();

if (!$personel) die("Personel kaydı bulunamadı.");
$myBranchID = $personel['BranchID'];

// --- İSTATİSTİKLER ---
// A. Müşteri Sayısı
$statCust = $pdo->prepare("SELECT COUNT(*) FROM Customers WHERE BranchID = ?");
$statCust->execute([$myBranchID]);
$totalBranchCustomers = $statCust->fetchColumn();

// B. Mevduat
$statMoney = $pdo->prepare("SELECT SUM(a.Balance) FROM Accounts a JOIN Customers c ON a.CustomerID = c.CustomerID WHERE c.BranchID = ? AND a.Currency = 'TL'");
$statMoney->execute([$myBranchID]);
$totalBranchMoney = $statMoney->fetchColumn() ?: 0;

// C. Bekleyen Kredi
$statLoans = $pdo->prepare("SELECT COUNT(*) FROM LoanRequests lr JOIN Customers c ON lr.CustomerID = c.CustomerID WHERE c.BranchID = ? AND lr.Status = 'Pending'");
$statLoans->execute([$myBranchID]);
$pendingLoanCount = $statLoans->fetchColumn();

// --- LİSTELER ---
// 1. Krediler
$stmtLoans = $pdo->prepare("SELECT lr.*, c.FirstName, c.LastName, c.TCKN FROM LoanRequests lr JOIN Customers c ON lr.CustomerID = c.CustomerID WHERE c.BranchID = ? AND lr.Status = 'Pending' ORDER BY lr.RequestDate ASC");
$stmtLoans->execute([$myBranchID]);
$loans = $stmtLoans->fetchAll();

// 2. İşlemler
$stmtTrans = $pdo->prepare("SELECT t.*, a.AccountNumber, c.FirstName, c.LastName FROM Transactions t JOIN Accounts a ON t.AccountID = a.AccountID JOIN Customers c ON a.CustomerID = c.CustomerID WHERE c.BranchID = ? ORDER BY t.TransactionDate DESC LIMIT 10");
$stmtTrans->execute([$myBranchID]);
$transactions = $stmtTrans->fetchAll();

// 3. Müşteri Özeti (Son 5)
$stmtCustList = $pdo->prepare("SELECT c.*, u.Email, IFNULL((SELECT SUM(Balance) FROM Accounts a WHERE a.CustomerID = c.CustomerID AND a.Currency='TL'), 0) as TotalBal FROM Customers c JOIN Users u ON c.UserID = u.UserID WHERE c.BranchID = ? ORDER BY c.CustomerID DESC LIMIT 5");
$stmtCustList->execute([$myBranchID]);
$branchCustomers = $stmtCustList->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Personel Paneli - <?= htmlspecialchars($personel['BranchName']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; font-family: 'Montserrat', sans-serif; }
        .navbar-brand { font-weight: 700; letter-spacing: 1px; }
        .stat-card { border: none; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); transition: transform 0.3s; overflow: hidden; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon { position: absolute; right: 20px; top: 20px; font-size: 3rem; opacity: 0.15; }
        .table-card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); overflow: hidden; }
        .table-header { background: #fff; padding: 20px; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary px-4 shadow py-3">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="fa fa-university me-2"></i> BANK of İSTÜN <span class="badge bg-white text-primary ms-2 small" style="font-size:0.7rem; vertical-align:middle;"><?= strtoupper($personel['BranchName']) ?></span></a>
        <div class="d-flex text-white align-items-center">
            <div class="me-3 text-end d-none d-md-block">
                <small class="d-block opacity-75">Personel</small>
                <span class="fw-bold"><?= htmlspecialchars($personel['FirstName'] . ' ' . $personel['LastName']) ?></span>
            </div>
            <a href="../auth/logout.php" class="btn btn-light text-primary fw-bold rounded-pill px-4 ms-3">Çıkış</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 py-5">
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card stat-card bg-white h-100 text-primary border-bottom border-4 border-primary">
                <div class="card-body p-4 position-relative">
                    <h6 class="text-uppercase text-muted fw-bold mb-2">Şube Müşterisi</h6>
                    <h2 class="display-5 fw-bold mb-0"><?= $totalBranchCustomers ?></h2>
                    <i class="fa fa-users stat-icon text-primary"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-white h-100 text-success border-bottom border-4 border-success">
                <div class="card-body p-4 position-relative">
                    <h6 class="text-uppercase text-muted fw-bold mb-2">Şube Mevduatı (TL)</h6>
                    <h2 class="display-5 fw-bold mb-0"><?= number_format($totalBranchMoney, 0, ',', '.') ?> ₺</h2>
                    <i class="fa fa-coins stat-icon text-success"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-white h-100 text-danger border-bottom border-4 border-danger">
                <div class="card-body p-4 position-relative">
                    <h6 class="text-uppercase text-muted fw-bold mb-2">Onay Bekleyen Kredi</h6>
                    <h2 class="display-5 fw-bold mb-0"><?= $pendingLoanCount ?></h2>
                    <i class="fa fa-clock stat-icon text-danger"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card table-card mb-4">
                <div class="table-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold m-0 text-dark"><i class="fa fa-hand-holding-usd text-warning me-2"></i> Kredi Onay Masası</h5>
                    <?php if($pendingLoanCount > 0): ?>
                        <span class="badge bg-danger rounded-pill px-3">Acil: <?= $pendingLoanCount ?></span>
                    <?php else: ?>
                        <span class="badge bg-success rounded-pill px-3">Bekleyen Yok</span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4">Müşteri</th>
                                    <th>TCKN</th>
                                    <th>Talep</th>
                                    <th>Sebep</th>
                                    <th class="text-end pe-4">Aksiyon</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($loans)): ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted">Şu an onay bekleyen kredi başvurusu yok.</td></tr>
                                <?php else: ?>
                                    <?php foreach($loans as $l): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold"><?= htmlspecialchars($l['FirstName'] . ' ' . $l['LastName']) ?></td>
                                        <td><?= htmlspecialchars($l['TCKN']) ?></td>
                                        <td class="fw-bold text-primary"><?= number_format($l['Amount'], 2) ?> ₺</td>
                                        <td class="small text-muted"><?= htmlspecialchars($l['Message']) ?></td>
                                        <td class="text-end pe-4">
                                            <form action="loan_action.php" method="POST" class="d-inline">
                                                <input type="hidden" name="loan_id" value="<?= $l['LoanID'] ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-success btn-sm rounded-pill px-3 fw-bold shadow-sm me-1"><i class="fa fa-check"></i></button>
                                                <button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-bold" onclick="return confirm('Reddetmek istediğine emin misin?')"><i class="fa fa-times"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card table-card mb-4">
                <div class="table-header">
                    <h5 class="fw-bold m-0 text-dark"><i class="fa fa-exchange-alt text-primary me-2"></i> Şube İşlem Hareketleri</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4">Tarih</th>
                                    <th>Müşteri</th>
                                    <th>Açıklama</th>
                                    <th>Tutar</th>
                                    <th class="text-end pe-4">Dekont</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($transactions)): ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted">Henüz bu şubede işlem yapılmadı.</td></tr>
                                <?php else: ?>
                                    <?php foreach($transactions as $t): ?>
                                    <tr>
                                        <td class="ps-4 text-muted small"><?= date("d.m H:i", strtotime($t['TransactionDate'])) ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($t['FirstName'] . ' ' . $t['LastName']) ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($t['Description']) ?></td>
                                        <td class="fw-bold <?php echo ($t['Amount'] < 0) ? 'text-danger' : 'text-success'; ?>"><?= number_format($t['Amount'], 2) ?></td>
                                        <td class="text-end pe-4">
                                            <a href="../customer/receipt.php?tid=<?= $t['TransactionID'] ?>&amt=<?= $t['Amount'] ?>" target="_blank" class="btn btn-sm btn-light border shadow-sm rounded-pill px-3"><i class="fa fa-file-invoice text-secondary"></i></a>
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

        <div class="col-lg-4">
            <div class="card table-card h-100">
                <div class="table-header bg-dark text-white">
                    <h5 class="fw-bold m-0"><i class="fa fa-address-book me-2"></i> Şube Müşterileri</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if(empty($branchCustomers)): ?>
                            <li class="list-group-item text-center p-4">Kayıtlı müşteri yok.</li>
                        <?php else: ?>
                            <?php foreach($branchCustomers as $bc): ?>
                            <li class="list-group-item p-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($bc['FirstName'] . ' ' . $bc['LastName']) ?></h6>
                                    <small class="text-muted"><?= $bc['Email'] ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="d-block fw-bold text-success small"><?= number_format($bc['TotalBal'], 2) ?> ₺</span>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                    <div class="p-3 text-center bg-light">
                        <a href="customers.php" class="btn btn-outline-primary btn-sm w-100 rounded-pill">Tüm Müşterileri Gör</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>