<?php
// Önbelleği Engelle (Eski kodun çalışmadığından emin olmak için)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Hataları Göster
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$userID = $_SESSION['user_id'];

// --- BAŞLANGIÇ VERİSİ (Varsayılan: Yok) ---
// Test ekranındaki ID'ler: 5=TL, 6=USD, 7=EUR
$hesapDurumu = [
    'TL'  => ['var_mi' => false, 'bakiye' => 0],
    'USD' => ['var_mi' => false, 'bakiye' => 0],
    'EUR' => ['var_mi' => false, 'bakiye' => 0]
];

try {
    // 1. Müşteri ID'sini al
    $stmtCust = $pdo->prepare("SELECT CustomerID FROM Customers WHERE UserID = ?");
    $stmtCust->execute([$userID]);
    $cust = $stmtCust->fetch();

    if ($cust) {
        $customerID = $cust['CustomerID'];

        // 2. Müşterinin hesaplarını çek
        $stmtAcc = $pdo->prepare("SELECT TypeID, Balance FROM Accounts WHERE CustomerID = ?");
        $stmtAcc->execute([$customerID]);
        $hesaplar = $stmtAcc->fetchAll(PDO::FETCH_ASSOC);

        // 3. ID'ye göre eşleştir (Test ekranındaki ID'ler: 5, 6, 7)
        foreach ($hesaplar as $h) {
            $id = intval($h['TypeID']); // Sayıya çevir ki hata olmasın
            $bakiye = $h['Balance'];

            if ($id === 5) {
                $hesapDurumu['TL']['var_mi'] = true;
                $hesapDurumu['TL']['bakiye'] = $bakiye;
            } 
            elseif ($id === 6) {
                $hesapDurumu['USD']['var_mi'] = true;
                $hesapDurumu['USD']['bakiye'] = $bakiye;
            }
            elseif ($id === 7) {
                $hesapDurumu['EUR']['var_mi'] = true;
                $hesapDurumu['EUR']['bakiye'] = $bakiye;
            }
        }
    }
} catch (Exception $e) {
    die("Veritabanı Hatası: " . $e->getMessage());
}

// PHP verisini JS'e aktar
$jsonVeri = json_encode($hesapDurumu);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>E-Mevduat - BANK of İSTÜN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Montserrat', sans-serif; }
        .hero-section {
            background: linear-gradient(135deg, #0d6efd, #0043a8);
            color: white; padding: 60px 20px;
            border-radius: 0 0 50px 50px; text-align: center; margin-bottom: -100px;
        }
        .calc-card {
            border: none; border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1); overflow: hidden; background: white;
        }
        .result-box {
            background-color: #f0fdf4; border: 2px dashed #22c55e;
            border-radius: 15px; padding: 20px; text-align: center; margin-top: 20px;
        }
        .form-label { font-weight: 700; font-size: 0.8rem; letter-spacing: 0.5px; color: #555; }
        .form-control, .form-select { padding: 15px; font-weight: 600; border-radius: 10px; }
        .input-group-text { border-radius: 10px; }
        .btn-action { padding: 15px; font-weight: 800; border-radius: 12px; letter-spacing: 1px; }
    </style>
</head>
<body>

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

<div class="hero-section">
    <i class="fa fa-coins fa-3x mb-3 text-warning"></i>
    <h2 class="fw-bold">E-Mevduat Getirisi Hesapla</h2>
    <p class="opacity-75">Paranız durduğu yerde değer kaybetmesin.</p>
</div>

<div class="container pb-5" style="margin-top: 120px;">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card calc-card p-4">
                <form id="calcForm">
                    <div class="row g-3">
                        
                        <div class="col-md-6">
                            <label class="form-label">PARA BİRİMİ</label>
                            <select class="form-select bg-light border-0" id="currency" onchange="hesaplaVeKontrolEt()">
                                <option value="TL" selected>Türk Lirası (TL)</option>
                                <option value="USD">Amerikan Doları (USD)</option>
                                <option value="EUR">Euro (EUR)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">VADE SÜRESİ</label>
                            <select class="form-select bg-light border-0" id="days" onchange="hesaplaVeKontrolEt()">
                                <option value="32" selected>32 Gün (Kırık Vade)</option>
                                <option value="92">92 Gün (3 Ay)</option>
                                <option value="181">181 Gün (6 Ay)</option>
                                <option value="365">365 Gün (1 Yıl)</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">YATIRMAK İSTEDİĞİNİZ TUTAR</label>
                            <div class="input-group">
                                <input type="number" class="form-control border-primary" id="amount" value="50000" min="1000" step="1000" oninput="hesaplaVeKontrolEt()">
                                <span class="input-group-text bg-primary text-white fw-bold" id="currencySymbol">TL</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="result-box">
                                <div class="row text-center">
                                    <div class="col-4 border-end border-success">
                                        <small class="text-success fw-bold d-block">FAİZ ORANI</small>
                                        <h3 class="fw-bold m-0" id="rateDisplay">%45.00</h3>
                                    </div>
                                    <div class="col-4 border-end border-success">
                                        <small class="text-success fw-bold d-block">NET KAZANÇ</small>
                                        <h5 class="fw-bold m-0 text-dark" id="earningDisplay">...</h5>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-success fw-bold d-block">TOPLAM</small>
                                        <h5 class="fw-bold m-0 text-dark" id="totalDisplay">...</h5>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-top border-success border-opacity-25" id="dynamicArea">
                                    </div>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // PHP'den gelen garanti veriler
    const bankaVerisi = <?= $jsonVeri ?>;
    
    // Faiz Oranları
    const oranlar = { 'TL': 45.00, 'USD': 3.50, 'EUR': 2.75 };

    function hesaplaVeKontrolEt() {
        const secilenPara = document.getElementById('currency').value; // TL, USD, EUR
        const gun = parseInt(document.getElementById('days').value);
        const tutar = parseFloat(document.getElementById('amount').value) || 0;
        
        // 1. Faiz Hesapla
        const oran = oranlar[secilenPara];
        const brutGetiri = (tutar * oran * gun) / 36500;
        const netGetiri = brutGetiri * 0.95; // Stopaj düş
        
        // Ekrana Yaz
        document.getElementById('currencySymbol').innerText = secilenPara;
        document.getElementById('rateDisplay').innerText = '%' + oran.toFixed(2);
        
        const formatter = new Intl.NumberFormat('tr-TR', { style: 'currency', currency: secilenPara });
        document.getElementById('earningDisplay').innerText = formatter.format(netGetiri);
        document.getElementById('totalDisplay').innerText = formatter.format(tutar + netGetiri);

        // 2. BUTON KONTROLÜ (KRİTİK BÖLÜM)
        const hesapBilgisi = bankaVerisi[secilenPara];
        const alan = document.getElementById('dynamicArea');
        
        if (hesapBilgisi.var_mi === true) {
            // HESAP VARSA -> ONAY BUTONU GÖSTER
            let bakiyeStr = new Intl.NumberFormat('tr-TR', { style: 'currency', currency: secilenPara }).format(hesapBilgisi.bakiye);
            
            alan.innerHTML = `
                <div class="alert alert-info border-0 py-2 mb-3 small">
                    <i class="fa fa-check-circle me-1"></i> <b>Vadeli ${secilenPara}</b> hesabınız mevcut.
                    <br>Güncel Bakiye: <b>${bakiyeStr}</b>
                </div>
                <button type="button" class="btn btn-primary w-100 btn-action shadow" onclick="alert('Talimat alındı. Mevduat başlatılıyor...')">
                    <i class="fa fa-check me-2"></i> MEVDUATI BAŞLAT (ONAYLA)
                </button>
            `;
        } else {
            // HESAP YOKSA -> AÇ BUTONU GÖSTER
            alan.innerHTML = `
                <div class="text-warning small mb-2">
                    <i class="fa fa-exclamation-circle"></i> Vadeli ${secilenPara} hesabınız bulunmuyor.
                </div>
                <a href="open_account_request.php" class="btn btn-warning w-100 btn-action shadow text-white">
                    <i class="fa fa-plus me-2"></i> HEMEN VADELİ HESAP AÇ
                </a>
            `;
        }
    }

    // Sayfa açılınca çalıştır
    window.onload = hesaplaVeKontrolEt;
</script>

</body>
</html>