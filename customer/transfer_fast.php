<?php
// Hataları Göster
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/db.php';

// Güvenlik
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$userID = $_SESSION['user_id'];
$message = "";
$messageType = "";

// --- 1. KAYAN BAR VE ÇEVİRİ İÇİN KURLAR ---
$kurlar = [
    'USD' => ['name' => 'USD', 'alis' => 34.00, 'satis' => 34.50, 'rate' => 34.20],
    'EUR' => ['name' => 'EUR', 'alis' => 37.00, 'satis' => 37.50, 'rate' => 37.50],
    'GA'  => ['name' => 'Gram Altın', 'alis' => 2900, 'satis' => 2950, 'rate' => 2900],
    'TL'  => ['name' => 'TL', 'alis' => 1.00,  'satis' => 1.00,  'rate' => 1.00]
];

function getExchangeRate($currency, $kurlar) {
    if ($currency == 'GR') $currency = 'GA';
    return $kurlar[$currency]['rate'] ?? 1.00;
}

try {
    // 2. GÖNDEREN HESAPLARI
    $stmt = $pdo->prepare("
        SELECT a.AccountID, a.AccountNumber, a.Balance, t.TypeName, t.Currency
        FROM Accounts a
        JOIN AccountTypes t ON a.TypeID = t.TypeID
        JOIN Customers c ON a.CustomerID = c.CustomerID
        WHERE c.UserID = ?
    ");
    $stmt->execute([$userID]);
    $myAccounts = $stmt->fetchAll();

    // 3. ALICI HESAPLARI
    $stmtTargets = $pdo->prepare("
        SELECT a.AccountNumber, c.FirstName, c.LastName, t.TypeName, t.Currency
        FROM Accounts a
        JOIN Customers c ON a.CustomerID = c.CustomerID
        JOIN AccountTypes t ON a.TypeID = t.TypeID
        WHERE c.UserID != ? 
        ORDER BY c.FirstName ASC
    ");
    $stmtTargets->execute([$userID]);
    $allOtherAccounts = $stmtTargets->fetchAll();

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

// 4. TRANSFER İŞLEMİ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fromAccountID = $_POST['from_account_id'];
    $targetAccountNo = $_POST['target_iban'];
    $amount = (float) $_POST['amount'];
    $description = $_POST['description'];

    if ($amount <= 0) {
        $message = "Lütfen geçerli bir tutar girin.";
        $messageType = "danger";
    } elseif (empty($targetAccountNo)) {
        $message = "Lütfen bir alıcı seçin.";
        $messageType = "danger";
    } else {
        $pdo->beginTransaction();
        try {
            // A. Gönderen Hesabı Çek
            $stmtSrc = $pdo->prepare("SELECT a.Balance, a.AccountNumber, t.Currency FROM Accounts a JOIN AccountTypes t ON a.TypeID = t.TypeID WHERE a.AccountID = ?");
            $stmtSrc->execute([$fromAccountID]);
            $sourceAcc = $stmtSrc->fetch();

            if ($sourceAcc['Balance'] < $amount) {
                throw new Exception("Yetersiz bakiye! Mevcut: " . number_format($sourceAcc['Balance'], 2));
            }

            // B. Alıcı Hesabı Çek
            $stmtDest = $pdo->prepare("SELECT a.AccountID, c.FirstName, c.LastName, t.Currency FROM Accounts a JOIN Customers c ON a.CustomerID = c.CustomerID JOIN AccountTypes t ON a.TypeID = t.TypeID WHERE a.AccountNumber = ?");
            $stmtDest->execute([$targetAccountNo]);
            $destAcc = $stmtDest->fetch();

            if (!$destAcc) throw new Exception("Seçilen hesap bulunamadı.");

            // C. Kur Çevirimi
            $srcCurrency = $sourceAcc['Currency'];
            $destCurrency = $destAcc['Currency'];
            
            $rateSrc = getExchangeRate($srcCurrency, $kurlar);
            $amountInTL = $amount * $rateSrc;
            
            $rateDest = getExchangeRate($destCurrency, $kurlar);
            $finalAmount = $amountInTL / $rateDest;

            // D. Transferi Uygula
            $pdo->prepare("UPDATE Accounts SET Balance = Balance - ? WHERE AccountID = ?")->execute([$amount, $fromAccountID]);
            $pdo->prepare("UPDATE Accounts SET Balance = Balance + ? WHERE AccountID = ?")->execute([$finalAmount, $destAcc['AccountID']]);

            // E. İşlem Kayıtları (Log)
            $conversionNote = ($srcCurrency != $destCurrency) ? " (Kur: $amount $srcCurrency -> " . number_format($finalAmount, 2) . " $destCurrency)" : "";

            // 1. Gönderen Log (DEKONT İÇİN BUNUN ID'Sİ LAZIM)
            $descSender = "Giden Transfer: " . $destAcc['FirstName'] . " " . $destAcc['LastName'] . " $conversionNote ($description)";
            $stmtLog = $pdo->prepare("INSERT INTO Transactions (AccountID, TransactionType, Amount, Description, TransactionDate) VALUES (?, 'Giden Transfer', ?, ?, NOW())");
            $stmtLog->execute([$fromAccountID, -$amount, $descSender]);
            
            // --- ID YAKALA ---
            $transactionID = $pdo->lastInsertId();

            // 2. Alıcı Log
            $descReceiver = "Gelen Transfer: " . $_SESSION['fullname'] . " $conversionNote ($description)";
            $pdo->prepare("INSERT INTO Transactions (AccountID, TransactionType, Amount, Description, TransactionDate) VALUES (?, 'Gelen Transfer', ?, ?, NOW())")
                ->execute([$destAcc['AccountID'], $finalAmount, $descReceiver]);

            $pdo->commit();
            
            // F. Başarı Mesajı ve DEKONT BUTONU
            $successMsg = "Transfer Başarılı!<br>";
            $successMsg .= "Hesabınızdan Çıkan: <b>" . number_format($amount, 2) . " $srcCurrency</b><br>";
            if ($srcCurrency != $destCurrency) {
                $successMsg .= "Alıcıya Geçen: <b>" . number_format($finalAmount, 2) . " $destCurrency</b>";
            }
            
            // BUTON BURADA:
            $successMsg .= "<div class='mt-3'><a href='receipt.php?tid=$transactionID' target='_blank' class='btn btn-light btn-sm fw-bold border shadow-sm text-dark'><i class='fa fa-file-invoice me-2'></i>Dekont Görüntüle</a></div>";

            $message = $successMsg;
            $messageType = "success";

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "İşlem Başarısız: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Para Transferi - BANK of İSTÜN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Montserrat', sans-serif; }
        .marquee-container { background: #000; color: #fff; padding: 10px 0; overflow: hidden; white-space: nowrap; font-size: 0.85rem; letter-spacing: 1px; border-bottom: 1px solid #333; }
        .marquee-content { display: inline-block; animation: marquee 30s linear infinite; }
        @keyframes marquee { 0% { transform: translateX(100%); } 100% { transform: translateX(-100%); } }
        .currency-item { display: inline-block; margin: 0 25px; }
        .form-label { font-weight: 600; font-size: 0.9rem; color: #555; }
        .card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<div class="marquee-container">
    <div class="marquee-content">
        <?php foreach($kurlar as $key => $val): ?>
            <?php if($key != 'TL'): ?>
            <span class="currency-item">
                <strong><?= $val['name'] ?></strong> 
                <span style="color:#4ade80">Alış: <?= $val['alis'] ?></span> | 
                <span style="color:#f87171">Satış: <?= $val['satis'] ?></span>
            </span>
            <?php endif; ?>
        <?php endforeach; ?>
        <span class="currency-item text-warning">HOŞ GELDİNİZ DEĞERLİ MÜŞTERİMİZ...</span>
    </div>
</div>

<nav class="navbar bg-white border-bottom px-4 py-3 shadow-sm">
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
    <div class="row justify-content-center">
        <div class="col-lg-6">
            
            <?php if($message): ?>
                <div class="alert alert-<?= $messageType ?> shadow-sm border-0 rounded-3 mb-4">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header bg-primary text-white py-3 rounded-top-4">
                    <h5 class="m-0 fw-bold"><i class="fa fa-paper-plane me-2"></i> Hızlı Para Transferi</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label">GÖNDEREN HESAP (SİZ)</label>
                            <select name="from_account_id" class="form-select" required>
                                <?php foreach($myAccounts as $acc): ?>
                                    <option value="<?= $acc['AccountID'] ?>">
                                        <?= $acc['TypeName'] ?> - <?= number_format($acc['Balance'], 2) ?> <?= $acc['Currency'] ?> 
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">ALICI HESAP SEÇİN</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fa fa-user"></i></span>
                                <select name="target_iban" class="form-select" required>
                                    <option value="">Lütfen bir alıcı seçin...</option>
                                    <?php if(empty($allOtherAccounts)): ?>
                                        <option value="" disabled>Başka müşteri hesabı bulunamadı.</option>
                                    <?php else: ?>
                                        <?php foreach($allOtherAccounts as $target): ?>
                                            <option value="<?= $target['AccountNumber'] ?>">
                                                <?= $target['FirstName'] . ' ' . $target['LastName'] ?> | 
                                                <?= $target['TypeName'] ?> (<?= $target['Currency'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="form-text">Farklı para birimlerine yapılan transferlerde otomatik kur çevirimi uygulanır.</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">GÖNDERİLECEK TUTAR</label>
                            <div class="input-group">
                                <input type="number" name="amount" class="form-control" placeholder="0.00" step="0.01" min="1" required>
                            </div>
                            <small class="text-muted">Tutar, gönderen hesabın para biriminden düşülecektir.</small>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">AÇIKLAMA</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Örn: Borç ödemesi"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow">TRANSFERİ ONAYLA</button>
                    </form>
                </div>
            </div>
            
            <div class="mt-3 text-center text-muted small">
                <i class="fa fa-info-circle me-1"></i> <b>Güncel Çeviri Kurları:</b><br>
                1 USD = <?= $kurlar['USD']['rate'] ?> TL | 
                1 EUR = <?= $kurlar['EUR']['rate'] ?> TL | 
                1 Gr Altın = <?= number_format($kurlar['GA']['rate'], 0) ?> TL
            </div>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>