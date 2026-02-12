<?php
session_start();
require_once '../includes/db.php';

// Güvenlik: Giriş yapılmamışsa dekont görünmez
if (!isset($_SESSION['user_id'])) {
    die("Erişim reddedildi. Lütfen giriş yapın.");
}

$transID = $_GET['tid'] ?? 0;

try {
    // İşlem detaylarını çek
    $sql = "SELECT t.*, a.AccountNumber, c.FirstName, c.LastName, typ.Currency 
            FROM Transactions t
            JOIN Accounts a ON t.AccountID = a.AccountID
            JOIN Customers c ON a.CustomerID = c.CustomerID
            JOIN AccountTypes typ ON a.TypeID = typ.TypeID
            WHERE t.TransactionID = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$transID]);
    $data = $stmt->fetch();

    if (!$data) {
        die("Dekont bulunamadı veya yetkisiz erişim.");
    }

} catch (PDOException $e) {
    die("Veritabanı Hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Dekont - İşlem #<?= $data['TransactionID'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #555; font-family: 'Courier New', Courier, monospace; }
        
        .receipt-container {
            max-width: 600px;
            background: #fff;
            margin: 50px auto;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
            position: relative;
            background-image: radial-gradient(#eee 1px, transparent 1px);
            background-size: 20px 20px;
        }
        
        .receipt-header { border-bottom: 2px dashed #000; padding-bottom: 20px; margin-bottom: 20px; text-align: center; }
        .logo { font-size: 24px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }
        .receipt-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 14px; }
        .receipt-label { font-weight: bold; color: #555; }
        .receipt-val { font-weight: bold; color: #000; text-align: right; }
        .amount-box { border: 2px solid #000; padding: 15px; text-align: center; margin: 30px 0; font-size: 24px; font-weight: bold; background: #f9f9f9; }
        .receipt-footer { border-top: 2px dashed #000; padding-top: 20px; text-align: center; font-size: 12px; color: #777; margin-top: 30px; }
        
        /* Butonların Konumlandırılması */
        .print-btn { position: fixed; bottom: 20px; right: 20px; z-index: 100; }
        .home-btn { position: fixed; bottom: 20px; left: 20px; z-index: 100; }

        /* Yazdırma modunda butonları gizle */
        @media print {
            .print-btn, .home-btn { display: none; }
            body { background-color: #fff; }
            .receipt-container { box-shadow: none; margin: 0; width: 100%; max-width: 100%; }
        }

        .receipt-container::before, .receipt-container::after {
            content: ""; position: absolute; left: 0; width: 100%; height: 10px;
            background: linear-gradient(45deg, transparent 33.333%, #fff 33.333%, #fff 66.667%, transparent 66.667%), 
                        linear-gradient(-45deg, transparent 33.333%, #fff 33.333%, #fff 66.667%, transparent 66.667%);
            background-size: 20px 40px;
        }
        .receipt-container::before { top: -10px; background-position: 0 -20px; transform: rotate(180deg); }
        .receipt-container::after { bottom: -10px; background-position: 0 0; }
    </style>
</head>
<body>

<div class="receipt-container">
    <div class="receipt-header">
        <div class="logo">BANK of İSTÜN</div>
        <div class="small mt-2">ELEKTRONİK İŞLEM DEKONTU</div>
        <div class="small text-muted"><?= date("d.m.Y H:i:s", strtotime($data['TransactionDate'])) ?></div>
    </div>

    <div class="receipt-row">
        <span class="receipt-label">İŞLEM NO:</span>
        <span class="receipt-val">#<?= str_pad($data['TransactionID'], 8, '0', STR_PAD_LEFT) ?></span>
    </div>
    
    <div class="receipt-row">
        <span class="receipt-label">MÜŞTERİ ADI:</span>
        <span class="receipt-val"><?= mb_strtoupper($data['FirstName'] . ' ' . $data['LastName']) ?></span>
    </div>

    <div class="receipt-row">
        <span class="receipt-label">HESAP NO:</span>
        <span class="receipt-val"><?= $data['AccountNumber'] ?></span>
    </div>

    <div class="receipt-row">
        <span class="receipt-label">İŞLEM TÜRÜ:</span>
        <span class="receipt-val text-uppercase"><?= $data['TransactionType'] ?></span>
    </div>

    <div class="receipt-row">
        <span class="receipt-label">AÇIKLAMA:</span>
        <span class="receipt-val" style="max-width: 60%; font-size:12px;"><?= $data['Description'] ?></span>
    </div>

    <div class="amount-box">
        <?php 
            $symbol = ($data['Amount'] < 0) ? '-' : '+';
            echo $symbol . ' ' . number_format(abs($data['Amount']), 2) . ' ' . $data['Currency']; 
        ?>
    </div>

    <div class="receipt-footer">
        <p>Bu belge elektronik ortamda üretilmiştir, ıslak imza gerektirmez.</p>
        <p>BANK of İSTÜN A.Ş. - Genel Müdürlük</p>
        <img src="../logo.png" style="height: 30px; opacity: 0.5; margin-top:10px;" onerror="this.style.display='none'">
    </div>
</div>

<a href="../index.php" class="btn btn-secondary home-btn fw-bold shadow">
    <i class="fa fa-arrow-left me-2"></i> ANA SAYFAYA DÖN
</a>

<button onclick="window.print()" class="btn btn-light print-btn fw-bold shadow">
    <i class="fa fa-print me-2"></i> YAZDIR / KAYDET
</button>

</body>
</html>