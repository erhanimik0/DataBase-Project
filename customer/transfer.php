<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }

$userId = $_SESSION['user_id'];
$sourceId = $_GET['source_id'] ?? 0;

// 1. Gönderen Hesabı Seç (URL'den geldiyse onu seç, yoksa ilk hesabını)
$myAccounts = $pdo->prepare("SELECT * FROM Accounts WHERE CustomerID = ?");
$myAccounts->execute([$userId]);
$myAccList = $myAccounts->fetchAll();

// 2. ALICI LİSTESİ (Sistemdeki diğer herkesi çek)
// Gerçek bankada bu olmaz ama proje olduğu için listeliyoruz.
$sqlUsers = "SELECT a.AccountID, a.IBAN, c.FirstName, c.LastName, t.TypeName, t.Currency
             FROM Accounts a
             JOIN Customers c ON a.CustomerID = c.CustomerID
             JOIN AccountTypes t ON a.TypeID = t.TypeID
             WHERE a.CustomerID != ? 
             ORDER BY c.FirstName ASC";
$stmtUsers = $pdo->prepare($sqlUsers);
$stmtUsers->execute([$userId]);
$otherUsers = $stmtUsers->fetchAll();

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fromAccID = $_POST['from_account'];
    $toAccID = $_POST['to_account'];
    $amount = (float)$_POST['amount'];
    $desc = $_POST['description'];

    // Bakiye Kontrolü
    $stmtBal = $pdo->prepare("SELECT Balance FROM Accounts WHERE AccountID = ? AND CustomerID = ?");
    $stmtBal->execute([$fromAccID, $userId]);
    $balance = $stmtBal->fetchColumn();

    if ($balance < $amount) {
        $msg = "Yetersiz Bakiye!";
    } elseif ($amount <= 0) {
        $msg = "Geçersiz Tutar!";
    } else {
        // --- TRANSFER BAŞLIYOR ---
        $pdo->beginTransaction();
        try {
            // 1. Gönderenden Düş
            $pdo->prepare("UPDATE Accounts SET Balance = Balance - ? WHERE AccountID = ?")->execute([$amount, $fromAccID]);
            
            // 2. Alıcıya Ekle
            $pdo->prepare("UPDATE Accounts SET Balance = Balance + ? WHERE AccountID = ?")->execute([$amount, $toAccID]);

            // 3. Log Kayıtları (İki tarafa da işlenir)
            // Giden Logu
            $sqlLog1 = "INSERT INTO Transactions (AccountID, TransactionType, Amount, Description, TransactionDate) VALUES (?, 'Transfer', ?, ?, NOW())";
            $pdo->prepare($sqlLog1)->execute([$fromAccID, -$amount, "Transfer Gönderimi: $desc"]);
            
            // Gelen Logu
            $sqlLog2 = "INSERT INTO Transactions (AccountID, TransactionType, Amount, Description, TransactionDate) VALUES (?, 'Deposit', ?, ?, NOW())";
            $pdo->prepare($sqlLog2)->execute([$toAccID, $amount, "Gelen Transfer: $desc"]);

            // İşlem ID'sini al (Dekont için lazım)
            $transID = $pdo->lastInsertId();

            $pdo->commit();

            // DEKONT SAYFASINA YÖNLENDİR
            header("Location: receipt.php?tid=$transID&from=$fromAccID&to=$toAccID&amt=$amount");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "Hata: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Para Transferi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'Montserrat',sans-serif; background:#f8f9fa;}</style>
</head>
<body>

<nav class="navbar navbar-light bg-white px-5 py-3 border-bottom mb-5">
    <a class="navbar-brand fw-bold" href="index.php">
        <img src="../logo.png" height="40" class="me-2"> BANK of İSTÜN
    </a>
    <a href="index.php" class="btn btn-dark rounded-0">Geri Dön</a>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0 rounded-0">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0 fw-bold">Para Transferi / Havale</h5>
                </div>
                <div class="card-body p-5">
                    
                    <?php if($msg): ?>
                        <div class="alert alert-danger rounded-0"><?= $msg ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        
                        <div class="mb-4">
                            <label class="fw-bold small text-muted mb-1">GÖNDEREN HESABINIZ</label>
                            <select name="from_account" class="form-select py-3 fw-bold bg-light">
                                <?php foreach($myAccList as $acc): ?>
                                    <option value="<?= $acc['AccountID'] ?>" <?= ($sourceId == $acc['AccountID']) ? 'selected' : '' ?>>
                                        <?= $acc['IBAN'] ?> — (Bakiye: <?= number_format($acc['Balance'], 2) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="fw-bold small text-muted mb-1">ALICI SEÇİN</label>
                            <select name="to_account" class="form-select py-3 border-primary" required>
                                <option value="">Bir Alıcı Seçiniz...</option>
                                <?php foreach($otherUsers as $user): ?>
                                    <option value="<?= $user['AccountID'] ?>">
                                        👤 <?= $user['FirstName'] ?> <?= $user['LastName'] ?> — <?= $user['TypeName'] ?> (<?= $user['IBAN'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Listeden istediğiniz müşteriyi seçerek para gönderebilirsiniz.</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="fw-bold small text-muted mb-1">TUTAR</label>
                                <input type="number" name="amount" class="form-control py-3 fw-bold" placeholder="0.00" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="fw-bold small text-muted mb-1">AÇIKLAMA</label>
                                <input type="text" name="description" class="form-control py-3" placeholder="Örn: Kira ödemesi">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-3 fw-bold fs-5 rounded-0">
                            TRANSFERİ TAMAMLA
                        </button>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>