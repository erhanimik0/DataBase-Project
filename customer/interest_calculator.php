<?php
// Önbellek sorununu engelle
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();
require_once '../includes/db.php';
require_once '../includes/currency_helper.php';

// Güvenlik
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }

$userId = $_SESSION['user_id'];
$ownedAccountTypeIDs = []; 

try {
    $stmtCust = $pdo->prepare("SELECT CustomerID FROM Customers WHERE UserID = ?");
    $stmtCust->execute([$userId]);
    $cust = $stmtCust->fetch();

    if ($cust) {
        $customerID = $cust['CustomerID'];
        $stmtAcc = $pdo->prepare("SELECT DISTINCT TypeID FROM Accounts WHERE CustomerID = ?");
        $stmtAcc->execute([$customerID]);
        $ownedAccountTypeIDs = $stmtAcc->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (PDOException $e) { }

$myAccountsJson = json_encode($ownedAccountTypeIDs);

// Canlı Kurlar
$kurlar = [];
try { if (function_exists('getLiveCurrencies')) { $kurlar = getLiveCurrencies(); } } catch (Exception $e) { $kurlar = []; }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Akıllı Mevduat Hesaplama - BANK of İSTÜN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        body { font-family: 'Montserrat', sans-serif; background-color: #f8f9fa; }
        .marquee-container { background: #000; color: #fff; padding: 10px 0; overflow: hidden; white-space: nowrap; font-size: 0.85rem; letter-spacing: 1px; }
        .marquee-content { display: inline-block; animation: marquee 30s linear infinite; }
        @keyframes marquee { 0% { transform: translateX(100%); } 100% { transform: translateX(-100%); } }
        .currency-item { display: inline-block; margin: 0 25px; }
        .calc-card { border: none; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); background: white; overflow: hidden; margin-top: 30px; }
        .calc-header { background: linear-gradient(135deg, #0d6efd, #0a58ca); color: white; padding: 40px 30px; text-align: center; }
        .result-box { background-color: #f0fdf4; border: 2px dashed #198754; border-radius: 15px; padding: 25px; margin-top: 25px; transition: all 0.3s; }
        footer { background-color: #fff; border-top: 1px solid #eee; padding-top: 60px; padding-bottom: 40px; font-size: 0.8rem; letter-spacing: 0.5px; margin-top: 80px; }
        footer h6 { font-weight: 700; letter-spacing: 1px; margin-bottom: 20px; font-size: 0.85rem; color: #000; }
        footer ul { padding-left: 0; list-style: none; }
        footer ul li { margin-bottom: 10px; }
        footer ul li a { color: #666; text-decoration: none; transition: color 0.3s; }
        footer ul li a:hover { color: #000; text-decoration: underline; }
        .newsletter-input { border: none; border-bottom: 1px solid #000; border-radius: 0; padding: 10px 0; width: 100%; font-size: 0.9rem; }
        .newsletter-input:focus { outline: none; border-bottom: 2px solid #000; }
        .swal2-popup { font-family: 'Montserrat', sans-serif; border-radius: 20px; }
        .swal2-confirm { padding: 12px 30px !important; font-weight: 600; border-radius: 50px !important; }
        .swal2-cancel { padding: 12px 30px !important; font-weight: 600; border-radius: 50px !important; }
    </style>
</head>
<body class="bg-light">

    <div class="marquee-container">
        <div class="marquee-content">
            <?php if(!empty($kurlar)): ?>
                <?php foreach($kurlar as $key => $val): ?>
                    <span class="currency-item"><strong><?= $val['name'] ?? $key ?></strong> <span style="color:#4ade80">Alış: <?= $val['alis'] ?></span> | <span style="color:#f87171">Satış: <?= $val['satis'] ?></span></span>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="currency-item">BANK of İSTÜN FİNANSAL HİZMETLERİ...</span>
            <?php endif; ?>
        </div>
    </div>

    <nav class="navbar navbar-light bg-white px-5 py-3 border-bottom sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold text-dark fs-4 d-flex align-items-center" href="../index.php">
                <img src="../logo.png" alt="BANK of İSTÜN" height="50" class="me-3" style="object-fit: contain;">
                <div><span class="d-block" style="line-height:1; font-weight:800;">BANK of İSTÜN</span><small class="text-muted" style="font-size: 0.6rem; letter-spacing: 2px;">PREMIUM BANKING</small></div>
            </a>
            <a href="../index.php" class="btn btn-dark rounded-0 px-4"><i class="fa fa-arrow-left me-2"></i> GERİ DÖN</a>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="calc-card">
                    <div class="calc-header">
                        <i class="fa fa-coins fa-4x mb-3 text-warning"></i>
                        <h2 class="fw-bold">E-Mevduat Getirisi Hesapla</h2>
                        <p class="mb-0 opacity-75">Paranız durduğu yerde değer kaybetmesin. Hemen hesaplayın, kazanmaya başlayın.</p>
                    </div>
                    <div class="card-body p-5">
                        <form id="calcForm">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small">PARA BİRİMİ</label>
                                    <select id="currency" class="form-select py-3 fw-bold border-primary" onchange="calculate()">
                                        <option value="TL" selected>Türk Lirası (TL)</option>
                                        <option value="USD">Amerikan Doları ($)</option>
                                        <option value="EUR">Euro (€)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small">VADE SÜRESİ</label>
                                    <select id="days" class="form-select py-3 fw-bold" onchange="calculate()">
                                        <option value="32">32 Gün (Kırık Vade)</option>
                                        <option value="46">46 Gün</option>
                                        <option value="92">92 Gün (3 Ay)</option>
                                        <option value="181">181 Gün (6 Ay)</option>
                                        <option value="365">365 Gün (1 Yıl)</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold text-muted small">YATIRMAK İSTEDİĞİNİZ TUTAR</label>
                                    <div class="input-group">
                                        <input type="number" id="amount" class="form-control py-3 fw-bold fs-4 text-primary" placeholder="Örn: 100000" oninput="calculate()">
                                        <span class="input-group-text fw-bold" id="currencySymbol">TL</span>
                                    </div>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill me-1" onclick="setAmount(10000)">10 Bin</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill me-1" onclick="setAmount(50000)">50 Bin</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" onclick="setAmount(100000)">100 Bin</button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div id="resultArea" class="result-box text-center d-none">
                            <div class="row align-items-center mb-4">
                                <div class="col-md-4 border-end">
                                    <small class="text-muted d-block fw-bold mb-1">FAİZ ORANI</small>
                                    <h3 class="text-primary fw-bold mb-0" id="displayRate">%45.00</h3>
                                </div>
                                <div class="col-md-4 border-end">
                                    <small class="text-muted d-block fw-bold mb-1">VADE SONU NET KAZANÇ</small>
                                    <h3 class="text-success fw-bold mb-0" id="displayInterest">0.00 TL</h3>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block fw-bold mb-1">TOPLAM BAKİYE</small>
                                    <h3 class="text-dark fw-bold mb-0" id="displayTotal">0.00 TL</h3>
                                </div>
                            </div>
                            <div id="actionButtonArea"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="row mb-5"><div class="col-md-6"><h5 class="mb-3 text-uppercase">Bültenimize Abone Olun</h5><form action="#"><input type="email" class="newsletter-input" placeholder="E-POSTA ADRESİNİZİ BURAYA GİRİN"></form></div></div>
            <div class="row mt-5 pt-4 border-top"><div class="col-md-6 text-muted small">İSTANBUL / TÜRKİYE</div><div class="col-md-6 text-end text-muted small">&copy; 2026 BANK of İSTÜN A.Ş.</div></div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const ownedTypeIDs = <?= $myAccountsJson ?>; 

        function setAmount(val) { document.getElementById('amount').value = val; calculate(); }

        function calculate() {
            const amount = parseFloat(document.getElementById('amount').value);
            const currency = document.getElementById('currency').value;
            const days = parseInt(document.getElementById('days').value);
            const resultBox = document.getElementById('resultArea');
            const actionArea = document.getElementById('actionButtonArea');
            const currencySym = document.getElementById('currencySymbol');

            currencySym.innerText = currency;

            if (!amount || amount <= 0) { resultBox.classList.add('d-none'); return; }
            resultBox.classList.remove('d-none');

            let rate = 0;
            if (currency === 'TL') { rate = days >= 181 ? 48.50 : (days >= 92 ? 47.00 : 45.00); }
            else if (currency === 'USD') { rate = days >= 92 ? 4.00 : 3.50; }
            else if (currency === 'EUR') { rate = days >= 92 ? 3.10 : 2.75; }

            const rawInterest = (amount * rate * days) / 36500;
            const tax = rawInterest * 0.05; 
            const netInterest = rawInterest - tax;
            const total = amount + netInterest;

            document.getElementById('displayRate').innerText = '%' + rate.toFixed(2);
            document.getElementById('displayInterest').innerText = netInterest.toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ' + currency;
            document.getElementById('displayTotal').innerText = total.toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ' + currency;

            let targetTypeId = 0;
            let targetAccountName = "";

            if (currency === 'TL') { targetTypeId = 5; targetAccountName = "Vadeli TL"; }
            if (currency === 'USD') { targetTypeId = 6; targetAccountName = "Vadeli Dolar"; }
            if (currency === 'EUR') { targetTypeId = 7; targetAccountName = "Vadeli Euro"; }

            const hasAccount = ownedTypeIDs.map(Number).includes(targetTypeId);

            if (hasAccount) {
                actionArea.innerHTML = `
                    <div class="alert alert-success bg-white border-success mb-2 p-2 small">
                        <i class="fa fa-check-circle text-success me-2"></i> Mevcut <b>${targetAccountName}</b> hesabınız tespit edildi.
                    </div>
                    <button class="btn btn-success w-100 py-3 fw-bold shadow-sm rounded-pill" onclick="confirmDeposit(${amount}, '${currency}')">
                        <i class="fa fa-coins me-2"></i> ${amount.toLocaleString()} ${currency} YATIR VE KAZAN
                    </button>
                `;
            } else {
                actionArea.innerHTML = `
                    <div class="alert alert-warning bg-white border-warning mb-2 p-2 small">
                        <i class="fa fa-info-circle text-warning me-2"></i> Henüz bir <b>${targetAccountName}</b> hesabınız yok.
                    </div>
                    <a href="open_account_request.php?account_type=${targetTypeId}" class="btn btn-primary w-100 py-3 fw-bold shadow-sm rounded-pill">
                        <i class="fa fa-user-plus me-2"></i> HEMEN VADELİ HESAP AÇ
                    </a>
                `;
            }
        }

        // --- GERÇEK İŞLEM YAPAN FONKSİYON ---
        function confirmDeposit(amount, currency) {
            let formattedAmount = new Intl.NumberFormat('tr-TR').format(amount);

            Swal.fire({
                title: 'Onaylıyor musunuz?',
                html: `Vadesiz hesabınızdan <b>${formattedAmount} ${currency}</b> çekilerek Vadeli hesabınıza aktarılacaktır.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Evet, Onaylıyorum',
                cancelButtonText: 'Vazgeç',
                confirmButtonColor: '#198754',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    // AJAX ile arka plan dosyasına git
                    const formData = new FormData();
                    formData.append('amount', amount);
                    formData.append('currency', currency);

                    return fetch('process_deposit.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) { throw new Error(response.statusText) }
                        return response.json()
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`İşlem Hatası: ${error}`)
                    })
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    if (result.value.status === 'success') {
                        Swal.fire({
                            title: 'İşlem Başarılı!',
                            text: 'Paranız başarıyla vadeli hesaba aktarıldı.',
                            icon: 'success',
                            confirmButtonText: 'Ana Sayfaya Dön'
                        }).then(() => {
                            window.location.href = '../index.php';
                        });
                    } else {
                        Swal.fire('Hata!', result.value.message, 'error');
                    }
                }
            });
        }
    </script>

</body>
</html>