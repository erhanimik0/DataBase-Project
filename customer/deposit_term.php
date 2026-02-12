<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }

$userId = $_SESSION['user_id'];
$amount = $_GET['amount'] ?? 0;
$currencyCode = $_GET['currency'] ?? 'TL';
$days = $_GET['days'] ?? 32;

$msg = "";
$alertType = "danger";
$isSuccess = false; // YENİ: İşlem başarılı mı kontrolü

// İşlem Formu Gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sourceAccID = $_POST['source_account_id'];
    $targetAccID = $_POST['target_account_id'];
    $finalAmount = $_POST['amount'];

    // 1. Kaynak Hesap Bakiyesi Yeterli mi?
    $stmtBal = $pdo->prepare("SELECT Balance FROM Accounts WHERE AccountID = ? AND CustomerID = ?");
    $stmtBal->execute([$sourceAccID, $userId]);
    $balance = $stmtBal->fetchColumn();

    if ($balance < $finalAmount) {
        $msg = "HATA: Seçilen hesapta yeterli bakiye yok! (Mevcut: " . number_format($balance, 2) . ")";
    } else {
        // 2. TRANSFER İŞLEMİ (Vadesiz -> Vadeli)
        $pdo->beginTransaction();
        try {
            // Parayı Çek
            $pdo->prepare("UPDATE Accounts SET Balance = Balance - ? WHERE AccountID = ?")->execute([$finalAmount, $sourceAccID]);
            // Parayı Yatır
            $pdo->prepare("UPDATE Accounts SET Balance = Balance + ? WHERE AccountID = ?")->execute([$finalAmount, $targetAccID]);

            // Loglar
            $desc = "Vadeli Mevduat Açılışı ($days Gün)";
            $pdo->prepare("INSERT INTO Transactions (AccountID, TransactionType, Amount, Description, TransactionDate) VALUES (?, 'Transfer', ?, ?, NOW())")->execute([$sourceAccID, -$finalAmount, "Giden: $desc"]);
            $pdo->prepare("INSERT INTO Transactions (AccountID, TransactionType, Amount, Description, TransactionDate) VALUES (?, 'Deposit', ?, ?, NOW())")->execute([$targetAccID, $finalAmount, "Gelen: $desc"]);

            $pdo->commit();
            
            $msg = "Tebrikler! Vadeli hesabınıza para yatırıldı ve faiz işlemeye başladı.";
            $alertType = "success";
            $isSuccess = true; // BAŞARILI OLDUĞUNU İŞARETLE
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "Sistem hatası oluştu: " . $e->getMessage();
        }
    }
}

// --- VERİ HAZIRLIĞI ---

// 1. Kullanıcının Uygun "VADESİZ" Hesaplarını Bul (Kaynak)
$sourceTypeID = 1;
if ($currencyCode == 'USD') $sourceTypeID = 3;
if ($currencyCode == 'EUR') $sourceTypeID = 5;

$stmtSource = $pdo->prepare("SELECT * FROM Accounts WHERE CustomerID = ? AND TypeID = ? AND Balance > 0");
$stmtSource->execute([$userId, $sourceTypeID]);
$sourceAccounts = $stmtSource->fetchAll();

// 2. Kullanıcının Uygun "VADELİ" Hesabını Bul (Hedef)
$targetTypeID = 2;
if ($currencyCode == 'USD') $targetTypeID = 4;
if ($currencyCode == 'EUR') $targetTypeID = 6;

$stmtTarget = $pdo->prepare("SELECT * FROM Accounts WHERE CustomerID = ? AND TypeID = ? LIMIT 1");
$stmtTarget->execute([$userId, $targetTypeID]);
$targetAccount = $stmtTarget->fetch();

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Vadeli Hesap Yatırımı</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Montserrat', sans-serif; background-color: #f8f9fa; }</style>
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            
            <div class="card shadow border-0 rounded-4">
                <div class="card-header bg-success text-white py-3 text-center rounded-top-4">
                    <h5 class="mb-0 fw-bold">Vadeli Hesaba Para Yatır</h5>
                </div>
                <div class="card-body p-4">

                    <?php if ($msg): ?>
                        <div class="alert alert-<?= $alertType ?> text-center mb-4">
                            <?= $msg ?>
                            <?php if($alertType == 'success'): ?>
                                <br><a href="../index.php" class="btn btn-sm btn-success mt-2">Ana Sayfaya Dön</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!$isSuccess): ?>

                        <?php if (!$targetAccount): ?>
                            <div class="alert alert-warning">
                                Hata: Vadeli hesabınız bulunamadı. Lütfen önce hesap açın.
                            </div>
                        <?php elseif (empty($sourceAccounts)): ?>
                            <div class="alert alert-danger text-center">
                                <i class="fa fa-times-circle fa-3x mb-3"></i><br>
                                <b>Yetersiz Bakiye veya Hesap Yok!</b><br>
                                Bu işlem için bakiyesi olan bir <b>Vadesiz <?= $currencyCode ?></b> hesabınızın olması gerekir.
                                <br><br>
                                <a href="interest_calculator.php" class="btn btn-outline-dark btn-sm">Geri Dön</a>
                            </div>
                        <?php else: ?>

                            <form method="POST">
                                <input type="hidden" name="target_account_id" value="<?= $targetAccount['AccountID'] ?>">
                                
                                <div class="mb-4">
                                    <label class="text-muted small fw-bold">YATIRILACAK TUTAR (<?= $currencyCode ?>)</label>
                                    <input type="number" name="amount" class="form-control form-control-lg fw-bold text-success" value="<?= $amount ?>" readonly>
                                </div>

                                <div class="mb-4">
                                    <label class="text-muted small fw-bold">KAYNAK HESAP (Paranın Çekileceği)</label>
                                    <select name="source_account_id" class="form-select form-select-lg">
                                        <?php foreach ($sourceAccounts as $acc): ?>
                                            <option value="<?= $acc['AccountID'] ?>">
                                                <?= $acc['IBAN'] ?> - (Bakiye: <?= number_format($acc['Balance'], 2) ?> <?= $currencyCode ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="bg-light p-3 rounded mb-4 border">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Vade Süresi:</span>
                                        <span class="fw-bold"><?= $days ?> Gün</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Hedef Hesap:</span>
                                        <span class="fw-bold">Vadeli <?= $currencyCode ?> Hesabım</span>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-success w-100 py-3 fw-bold fs-5 rounded-pill shadow-sm">
                                    ONAYLA VE YATIR
                                </button>
                                
                                <div class="text-center mt-3">
                                    <a href="interest_calculator.php" class="text-decoration-none text-muted small">İptal Et</a>
                                </div>

                            </form>

                        <?php endif; ?>

                    <?php endif; // isSuccess bitişi ?>

                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>