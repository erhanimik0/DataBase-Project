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

// --- CANLI KURLAR (Kayan Bar İçin) ---
$kurlar = [];
try {
    // Eğer currency helper varsa çek, yoksa manuel tanımla
    if (file_exists('../includes/currency_helper.php')) {
        require_once '../includes/currency_helper.php';
    }
    
    if (function_exists('getLiveCurrencies')) {
        $kurlar = getLiveCurrencies();
    } else {
        // Fallback (Yedek) veriler
        $kurlar = [
            'USD' => ['alis' => 34.00, 'satis' => 34.50],
            'EUR' => ['alis' => 37.00, 'satis' => 37.50],
            'GA'  => ['name' => 'Gram Altın', 'alis' => 2900, 'satis' => 2950]
        ];
    }
} catch (Exception $e) { $kurlar = []; }

// 1. Müşteri Bilgilerini Çek
try {
    $stmt = $pdo->prepare("SELECT * FROM Customers WHERE UserID = ?");
    $stmt->execute([$userID]);
    $customer = $stmt->fetch();

    if (!$customer) {
        die("Hata: Müşteri profili bulunamadı.");
    }
} catch (PDOException $e) {
    die("Veritabanı Hatası: " . $e->getMessage());
}

// 2. Form Gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = $_POST['amount'];
    $income = $_POST['income'];
    $reason = $_POST['reason'];
    $maturity = $_POST['maturity'];

    if ($amount < 1000) {
        $message = "Minimum kredi tutarı 1.000 TL olmalıdır.";
        $messageType = "danger";
    } else {
        try {
            $stmtInsert = $pdo->prepare("INSERT INTO LoanRequests (CustomerID, Amount, Message, Status, RequestDate) VALUES (?, ?, ?, 'Pending', NOW())");
            $fullMessage = "Kredi Türü: $reason | Aylık Gelir: $income TL | Vade: $maturity Ay";
            
            $stmtInsert->execute([$customer['CustomerID'], $amount, $fullMessage]);
            
            $message = "Başvurunuz başarıyla alındı! Kredi onay ekibimiz en kısa sürede değerlendirecektir.";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Hata oluştu: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kredi Başvurusu - BANK of İSTÜN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f8f9fa; font-family: 'Montserrat', sans-serif; }
        
        /* KAYAN BAR STİLLERİ */
        .marquee-container { background: #000; color: #fff; padding: 10px 0; overflow: hidden; white-space: nowrap; font-size: 0.85rem; letter-spacing: 1px; border-bottom: 1px solid #333; }
        .marquee-content { display: inline-block; animation: marquee 30s linear infinite; }
        @keyframes marquee { 0% { transform: translateX(100%); } 100% { transform: translateX(-100%); } }
        .currency-item { display: inline-block; margin: 0 25px; }

        .main-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            min-height: 600px;
        }

        /* SOL PANEL */
        .left-panel {
            background: linear-gradient(rgba(0, 50, 150, 0.7), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1565514020176-db79238b6d37?q=80&w=1000&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 40px;
        }

        .left-panel h2 { font-weight: 800; font-size: 2.5rem; margin-bottom: 20px; }
        .left-panel p { font-size: 1.1rem; opacity: 0.9; line-height: 1.6; }
        .check-list { text-align: left; margin-top: 30px; list-style: none; padding: 0; }
        .check-list li { margin-bottom: 10px; font-size: 0.95rem; }
        .check-list i { color: #4ade80; margin-right: 10px; }

        .form-label { font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; color: #555; }
        .form-control, .form-select { padding: 12px; border-radius: 8px; border: 1px solid #ddd; }
        .form-control:focus, .form-select:focus { border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1); }
        .info-box { background: #f8f9fa; border-radius: 10px; padding: 15px; border-left: 5px solid #0d6efd; margin-top: 20px; }
    </style>
</head>
<body>

<div class="marquee-container">
    <div class="marquee-content">
        <?php if(!empty($kurlar)): ?>
            <?php foreach($kurlar as $key => $val): ?>
                <span class="currency-item">
                    <strong><?= $val['name'] ?? $key ?></strong> 
                    <span style="color:#4ade80">Alış: <?= $val['alis'] ?></span> | 
                    <span style="color:#f87171">Satış: <?= $val['satis'] ?></span>
                </span>
            <?php endforeach; ?>
        <?php else: ?>
            <span class="currency-item">BANK of İSTÜN PİYASALARI: VERİ AKIŞI BEKLENİYOR...</span>
        <?php endif; ?>
    </div>
</div>

<nav class="navbar bg-white border-bottom px-4 py-3 sticky-top shadow-sm">
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
        <div class="col-lg-10">
            
            <?php if($message): ?>
                <div class="alert alert-<?= $messageType ?> shadow-sm mb-4 rounded-3 border-0">
                    <i class="fa fa-info-circle me-2"></i> <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="card main-card">
                <div class="row g-0 h-100">
                    
                    <div class="col-md-5 left-panel">
                        <i class="fa fa-rocket fa-4x mb-4 text-warning"></i>
                        <h2>Hayallerini<br>Erteleme</h2>
                        <p>Ev, araba ya da nakit ihtiyaçların için en uygun faiz oranları ve esnek ödeme seçenekleri.</p>
                        
                        <ul class="check-list">
                            <li><i class="fa fa-check-circle"></i> 36 Aya Varan Vadeler</li>
                            <li><i class="fa fa-check-circle"></i> Anında Ön Onay</li>
                            <li><i class="fa fa-check-circle"></i> Dosya Masrafsız Seçenekler</li>
                            <li><i class="fa fa-check-circle"></i> %1.99'dan Başlayan Oranlar</li>
                        </ul>
                    </div>

                    <div class="col-md-7 bg-white p-5">
                        <h3 class="fw-bold mb-1 text-dark">Kredi Başvuru Formu</h3>
                        <p class="text-muted small mb-4">Finansal durumunuza en uygun krediyi hesaplayalım.</p>

                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">AD SOYAD</label>
                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($customer['FirstName'] . ' ' . $customer['LastName']) ?>" readonly>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">TC KİMLİK NO</label>
                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($customer['TCKN']) ?>" readonly>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">AYLIK GELİR (TL)</label>
                                    <input type="number" name="income" class="form-control" placeholder="Örn: 25000" required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">KREDİ TÜRÜ</label>
                                    <select name="reason" class="form-select">
                                        <option value="İhtiyaç Kredisi">İhtiyaç Kredisi (%3.50)</option>
                                        <option value="Taşıt Kredisi">Taşıt Kredisi (%2.99)</option>
                                        <option value="Konut Kredisi">Konut Kredisi (%2.49)</option>
                                    </select>
                                </div>

                                <div class="col-md-8">
                                    <label class="form-label">TALEP EDİLEN TUTAR (TL)</label>
                                    <input type="number" name="amount" id="amount" class="form-control" placeholder="0" min="1000" required>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setAmount(10000)">10 Bin</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setAmount(50000)">50 Bin</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setAmount(100000)">100 Bin</button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">VADE</label>
                                    <select name="maturity" class="form-select">
                                        <option value="12">12 Ay</option>
                                        <option value="24">24 Ay</option>
                                        <option value="36">36 Ay</option>
                                        <option value="48">48 Ay</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <div class="info-box">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted small">Faiz Oranı:</span>
                                            <span class="fw-bold text-dark">%3.50</span>
                                        </div>
                                        <div class="d-flex justify-content-between border-top pt-2 mt-2">
                                            <span class="fw-bold text-dark">TAHMİNİ GERİ ÖDEME:</span>
                                            <span class="fw-bold text-success" id="totalPay">-- TL</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" required>
                                        <label class="form-check-label small text-muted">
                                            Kredi sözleşmesini okudum, onaylıyorum.
                                        </label>
                                    </div>
                                </div>

                                <div class="col-12 mt-3">
                                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow-sm">
                                        BAŞVURUYU TAMAMLA
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function setAmount(val) {
        document.getElementById('amount').value = val;
        calcTotal();
    }
    document.getElementById('amount').addEventListener('input', calcTotal);
    function calcTotal() {
        let amt = document.getElementById('amount').value;
        if(amt) {
            let total = amt * 1.42; 
            document.getElementById('totalPay').innerText = new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(total);
        } else {
            document.getElementById('totalPay').innerText = "-- TL";
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>