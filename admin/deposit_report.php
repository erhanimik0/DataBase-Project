<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') { header("Location: ../auth/login.php"); exit; }

// Para Birimine Göre Toplam Paralar
$sql = "SELECT Cur.Name, Cur.Code, SUM(A.Balance) as Total 
        FROM Accounts A
        JOIN Currencies Cur ON A.CurrencyID = Cur.CurrencyID
        GROUP BY Cur.Name, Cur.Code";
$totals = $pdo->query($sql)->fetchAll();

// En Yüksek Bakiyeli 5 Müşteri
$sqlTop = "SELECT C.FirstName, C.LastName, A.Balance, Cur.Code 
           FROM Accounts A 
           JOIN Customers C ON A.CustomerID = C.CustomerID 
           JOIN Currencies Cur ON A.CurrencyID = Cur.CurrencyID
           ORDER BY A.Balance DESC LIMIT 5";
$topUsers = $pdo->query($sqlTop)->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Mevduat Raporu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Montserrat', sans-serif; background-color: #f8f9fa; }</style>
</head>
<body>

    <nav class="navbar navbar-dark bg-dark px-4 py-3">
        <a class="navbar-brand fw-bold" href="../index.php"><i class="fa fa-arrow-left me-2"></i> Yönetim Paneli</a>
        <span class="text-white">Finansal Raporlar</span>
    </nav>

    <div class="container mt-5">
        <h3 class="fw-bold mb-4">Mevduat Durumu</h3>
        
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-warning text-dark fw-bold">Para Birimi Bazında Toplamlar</div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach($totals as $t): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fa fa-money-bill-wave text-muted me-2"></i> <?= $t['Name'] ?></span>
                                <span class="fw-bold fs-5"><?= number_format($t['Total'], 2) ?> <?= $t['Code'] ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-dark text-white fw-bold">En Yüksek Bakiyeli 5 Hesap</div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <tbody>
                                <?php foreach($topUsers as $i => $u): ?>
                                <tr>
                                    <td class="px-3 fw-bold text-secondary"><?= $i+1 ?>.</td>
                                    <td><?= $u['FirstName'] . ' ' . $u['LastName'] ?></td>
                                    <td class="text-end fw-bold text-success"><?= number_format($u['Balance'], 2) ?> <?= $u['Code'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>