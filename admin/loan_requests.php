<?php
session_start();
require_once '../includes/db.php';

// Güvenlik: Sadece Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Bekleyen Kredileri Çek
$sql = "SELECT L.*, C.FirstName, C.LastName, C.IdentityNumber, C.PhoneNumber 
        FROM Loans L 
        JOIN Customers C ON L.CustomerID = C.CustomerID 
        WHERE L.Status = 'Pending' 
        ORDER BY L.ApplicationDate DESC";
$loans = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kredi Başvuruları</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif; background-color: #f8f9fa; }
        .card { border: none; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .info-label { font-size: 0.8rem; color: #6c757d; text-transform: uppercase; font-weight: 600; }
        .info-value { font-size: 1.1rem; font-weight: 600; color: #212529; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark bg-dark px-4 py-3">
        <a class="navbar-brand fw-bold" href="../index.php">
            <i class="fa fa-arrow-left me-2"></i> Yönetim Paneline Dön
        </a>
        <span class="text-white">Kredi Operasyonları</span>
    </nav>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="fa fa-hand-holding-usd text-success"></i> Bekleyen Kredi Başvuruları</h3>
            <span class="badge bg-warning text-dark fs-6"><?= count($loans) ?> Bekleyen</span>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($loans)): ?>
            <div class="card p-5 text-center">
                <div class="card-body">
                    <i class="fa fa-clipboard-check fa-5x text-secondary mb-3"></i>
                    <h4>Bekleyen başvuru yok.</h4>
                    <p class="text-muted">Tüm kredi talepleri değerlendirildi.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($loans as $loan): ?>
                    <div class="col-12 mb-4">
                        <div class="card border-start border-5 border-success">
                            <div class="card-body p-4">
                                <div class="row align-items-center">
                                    
                                    <div class="col-md-3 border-end">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-light rounded-circle p-3 me-3">
                                                <i class="fa fa-user fa-2x text-success"></i>
                                            </div>
                                            <div>
                                                <h5 class="fw-bold mb-0"><?= $loan['FirstName'] . ' ' . $loan['LastName'] ?></h5>
                                                <small class="text-muted"><?= $loan['IdentityNumber'] ?></small>
                                            </div>
                                        </div>
                                        <div class="small text-muted"><i class="fa fa-phone me-1"></i> <?= $loan['PhoneNumber'] ?></div>
                                        <div class="small text-muted"><i class="fa fa-calendar me-1"></i> <?= date("d.m.Y H:i", strtotime($loan['ApplicationDate'])) ?></div>
                                    </div>

                                    <div class="col-md-6 px-4">
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <div class="info-label">Talep Edilen Tutar</div>
                                                <div class="info-value text-success"><?= number_format($loan['Amount'], 2) ?> TL</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="info-label">Kredi Türü</div>
                                                <div class="info-value"><?= $loan['LoanType'] ?> Kredisi</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="info-label">Vade / Faiz</div>
                                                <div class="info-value"><?= $loan['TermMonths'] ?> Ay / %<?= $loan['InterestRate'] ?></div>
                                            </div>
                                            <div class="col-6">
                                                <div class="info-label">Geri Ödenecek</div>
                                                <div class="info-value text-danger"><?= number_format($loan['TotalPayment'], 2) ?> TL</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3 text-center border-start">
                                        <form action="process_loan.php" method="POST">
                                            <input type="hidden" name="loan_id" value="<?= $loan['LoanID'] ?>">
                                            <input type="hidden" name="customer_id" value="<?= $loan['CustomerID'] ?>">
                                            <input type="hidden" name="amount" value="<?= $loan['Amount'] ?>">
                                            
                                            <button type="submit" name="action" value="approve" class="btn btn-success w-100 mb-2 fw-bold shadow-sm">
                                                <i class="fa fa-check-circle me-1"></i> ONAYLA
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn btn-outline-danger w-100 fw-bold">
                                                <i class="fa fa-times-circle me-1"></i> REDDET
                                            </button>
                                        </form>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>