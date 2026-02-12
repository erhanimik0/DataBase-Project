<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/currency_helper.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }

$userId = $_SESSION['user_id'];

// Görsel amaçlı rastgele bir token
$dummyToken = bin2hex(random_bytes(16));
$dummyLink = "http://bankofistun.com/mobile-approve?token=$dummyToken";

// --- YENİ KOD BAŞLANGICI ---
try {
    // Hesapları Çek
    $sql = "SELECT a.*, t.TypeName 
            FROM Accounts a 
            JOIN AccountTypes t ON a.TypeID = t.TypeID 
            WHERE a.CustomerID = ? 
            ORDER BY a.Balance DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $accounts = $stmt->fetchAll();

    // HATA VARSA EKRANA BAS
    if (count($accounts) == 0) {
        echo "<div style='background:red; color:white; padding:20px; font-size:18px; z-index:9999; position:absolute;'>";
        echo "<b>HATA: Hesap bulunamadı!</b><br>";
        echo "Aranan User ID: " . $userId . "<br>";
        echo "Lütfen veritabanında 'Accounts' tablosunu kontrol et.<br>";
        echo "Sütun adı 'CustomerID' mi yoksa 'user_id' mi?";
        echo "</div>";
    }
} catch (Exception $e) {
    die("SQL Hatası: " . $e->getMessage());
}
// --- YENİ KOD BİTİŞİ ---

// HESAP VAR MI KONTROLÜ
$hesapVarMi = count($accounts) > 0;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>QR ATM - Simülasyon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: 'Montserrat', sans-serif; background: #212529; color: white; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .atm-card { width: 100%; max-width: 500px; background: #343a40; border-radius: 20px; box-shadow: 0 0 50px rgba(0,0,0,0.5); overflow: hidden; position: relative; }
        .qr-section { padding: 40px; text-align: center; }
        .transaction-section { padding: 40px; display: none; background: #fff; color: #000; animation: fadeIn 0.5s; }
        .qr-img { background: white; padding: 15px; border-radius: 10px; width: 180px; height: 180px; margin: 0 auto; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        button:disabled { cursor: not-allowed; opacity: 0.6; }
        
        /* Dropdown ve Input Stilleri */
        select.form-select option { font-size: 0.9rem; padding: 5px; }
        
        /* Tutar Inputu için özel stil */
        #displayAmount {
            font-family: 'Montserrat', sans-serif;
            letter-spacing: 1px;
            color: #198754; /* Yeşilimsi para rengi */
        }
    </style>
</head>
<body>

    <div class="atm-card">
        
        <div class="qr-section" id="qrPanel">
            <a href="../index.php" class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-3 rounded-circle" title="Çıkış">
                <i class="fa fa-times"></i>
            </a>

            <h4 class="fw-bold mb-3">Lütfen QR Kodu Okutun</h4>
            <p class="text-white-50 small mb-4">Kartsız işlem yapmak için mobil uygulamayı kullanın.</p>
            
            <div class="qr-img mb-4">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($dummyLink) ?>" alt="QR">
            </div>

            <button onclick="simulateScan()" class="btn btn-warning w-100 py-3 fw-bold shadow mb-3">
                <i class="fa fa-mobile-alt me-2"></i> TELEFONDA OKUTTUM (SİMÜLE ET)
            </button>
            
            <a href="../index.php" class="btn btn-outline-secondary w-100 py-2 border-0 text-white-50">
                <i class="fa fa-arrow-left me-2"></i> İPTAL ET VE ÇIK
            </a>
        </div>

        <div class="transaction-section" id="transPanel">
            
            <div class="text-center mb-4">
                <div class="rounded-circle bg-success d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="fa fa-check text-white fa-2x"></i>
                </div>
                <h4 class="fw-bold">Giriş Onaylandı</h4>
            </div>
            
            <?php if(!$hesapVarMi): ?>
                <div class="alert alert-danger text-center small">
                    <i class="fa fa-exclamation-circle"></i> İşlem yapılacak hesabınız bulunmuyor.<br>Lütfen önce hesap açınız.
                </div>
            <?php endif; ?>

            <form method="POST" action="atm_process.php" onsubmit="return prepareSubmission()">
                
                <div class="mb-3">
                    <label class="fw-bold small text-muted">HESAP SEÇİN</label>
                    <select id="accountSelect" name="account_id" class="form-select py-3 fw-bold bg-light border-0" <?= !$hesapVarMi ? 'disabled' : '' ?> onchange="updateCurrencySymbol()">
                        <?php if($hesapVarMi): ?>
                            <?php foreach($accounts as $acc): ?>
                                <?php 
                                    // Para birimi sembolünü belirle
                                    $currencyCode = "TL";
                                    $symbol = "₺";
                                    
                                    if (strpos($acc['TypeName'], 'Dolar') !== false) { $currencyCode = "USD"; $symbol = "$"; }
                                    else if (strpos($acc['TypeName'], 'Euro') !== false) { $currencyCode = "EUR"; $symbol = "€"; }
                                    else if (strpos($acc['TypeName'], 'Altın') !== false) { $currencyCode = "GR"; $symbol = "gr"; }
                                ?>
                                <option value="<?= $acc['AccountID'] ?>" data-symbol="<?= $symbol ?>">
                                    <?= strtoupper($acc['TypeName']) ?> &nbsp;—&nbsp; <?= number_format($acc['Balance'], 2) ?> <?= $symbol ?> (<?= $acc['IBAN'] ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option>Hesap Bulunamadı</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="fw-bold small text-muted">TUTAR GİRİN</label>
                    
                    <input type="text" 
                           id="displayAmount" 
                           class="form-control py-3 fs-3 fw-bold text-center border-dark" 
                           placeholder="0,00" 
                           autocomplete="off"
                           required 
                           <?= !$hesapVarMi ? 'disabled' : '' ?>>
                           
                    <input type="hidden" name="amount" id="realAmount">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <button type="submit" name="action" value="withdraw" class="btn btn-outline-dark w-100 py-3 fw-bold" <?= !$hesapVarMi ? 'disabled' : '' ?>>
                            PARA ÇEK
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="submit" name="action" value="deposit" class="btn btn-dark w-100 py-3 fw-bold" <?= !$hesapVarMi ? 'disabled' : '' ?>>
                            PARA YATIR
                        </button>
                    </div>
                </div>

                <a href="../index.php" class="btn btn-link text-muted w-100 text-decoration-none small">
                    <i class="fa fa-times-circle me-1"></i> Vazgeç ve Kartı İade Al
                </a>

            </form>
        </div>

    </div>

    <script>
        function simulateScan() {
            $('#qrPanel').fadeOut(300, function(){
                $('#transPanel').fadeIn(300);
            });
        }

        // DOM Elemanları
        const displayInput = document.getElementById('displayAmount');
        const realInput = document.getElementById('realAmount');
        const accountSelect = document.getElementById('accountSelect');

        // Mevcut Sembolü Al (₺, $, €)
        function getSymbol() {
            const selectedOption = accountSelect.options[accountSelect.selectedIndex];
            return selectedOption ? (selectedOption.getAttribute('data-symbol') || '') : '';
        }

        // Para Birimi Değişince Inputu Güncelle
        function updateCurrencySymbol() {
            if(displayInput.value) {
                formatOnBlur(); // Mevcut değeri yeni sembolle tekrar formatla
            }
        }

        // ODAKLANINCA (FOCUS): Temiz sayı göster (Düzenleme modu)
        displayInput.addEventListener('focus', function() {
            const val = realInput.value;
            if(val) {
                // Sadece ham sayıyı göster: 3546
                displayInput.value = val; 
            } else {
                displayInput.value = '';
            }
        });

        // ODAKTAN ÇIKINCA (BLUR): Süslü formatla (3.546,00 $)
        displayInput.addEventListener('blur', formatOnBlur);

        function formatOnBlur() {
            let val = displayInput.value;
            
            // Sadece sayıları ve virgülü/noktayı al
            // Virgülü noktaya çevir ki JS float olarak anlasın (TR klavye uyumu)
            val = val.replace(',', '.'); 
            
            // Harfleri temizle
            val = val.replace(/[^0-9.]/g, '');

            if(val === '' || isNaN(val)) return;

            // Gerçek değeri sakla (Veritabanı için)
            realInput.value = val;

            // Ekrana basılacak formatı oluştur
            const numberVal = parseFloat(val);
            const symbol = getSymbol();

            // TR Formatı: 3.546,00
            const formatted = numberVal.toLocaleString('tr-TR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            displayInput.value = formatted + ' ' + symbol;
        }

        // YAZARKEN (INPUT): Gerçek değeri anlık güncelle
        displayInput.addEventListener('input', function() {
            // Kullanıcı yazarken harfleri engelle (Sadece rakam ve virgül/nokta)
            let val = this.value.replace(/[^0-9.,]/g, '');
            // Henüz görseli bozma, sadece realInput'u güncellemeye çalış
            // (Tam güncelleme Blur'da olacak)
        });

        // Form Gönderilmeden Önce Son Kontrol
        function prepareSubmission() {
            // Eğer kullanıcı bir şey yazıp direkt butona bastıysa (Blur tetiklenmediyse)
            // Manuel olarak realInput'u doldurmamız lazım.
            if(!realInput.value && displayInput.value) {
                let val = displayInput.value.replace(',', '.').replace(/[^0-9.]/g, '');
                realInput.value = val;
            }
            return true;
        }

        // Sayfa açıldığında sembolü kontrol et
        updateCurrencySymbol();
    </script>

</body>
</html>