<?php
session_start();
require_once '../includes/db.php';

// Para birimi fonksiyonu kontrolü
if (file_exists('../includes/currency_helper.php')) {
    require_once '../includes/currency_helper.php';
}

// Güvenlik
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// 1. Kullanıcı Bilgilerini Çek
$stmt = $pdo->prepare("
    SELECT c.*, u.Email 
    FROM Customers c 
    JOIN Users u ON c.UserID = u.UserID 
    WHERE c.UserID = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// 2. KULLANICININ MEVCUT HESAPLARINI BUL (Engelleme için gerekli)
$stmtExisting = $pdo->prepare("SELECT TypeID FROM Accounts WHERE CustomerID = ?");
$stmtExisting->execute([$user['CustomerID']]); 
$existingTypes = $stmtExisting->fetchAll(PDO::FETCH_COLUMN);

// 3. TÜM HESAP TÜRLERİNİ ÇEK
// Vadeli/Vadesiz hepsi burada gelecek.
$stmtTypes = $pdo->query("SELECT * FROM AccountTypes ORDER BY TypeName ASC");
$allTypes = $stmtTypes->fetchAll();

// 4. Kur Bilgileri
$kurlar = [];
try {
    if (function_exists('getLiveCurrencies')) { $kurlar = getLiveCurrencies(); }
    else {
        $kurlar = [
            'USD' => ['name' => 'DOLAR', 'alis' => 36.40, 'satis' => 37.10],
            'EUR' => ['name' => 'EURO', 'alis' => 39.50, 'satis' => 40.20],
            'GA'  => ['name' => 'GRAM ALTIN', 'alis' => 3250, 'satis' => 3300],
            'CA'  => ['name' => 'ÇEYREK ALTIN', 'alis' => 5350, 'satis' => 5500]
        ];
    }
} catch (Exception $e) { $kurlar = []; }

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $age = $_POST['age'];
    $address = $_POST['address'];
    $purpose = $_POST['purpose'];
    
    // TELEFON
    $countryCode = $_POST['country_code'];
    $rawPhone = $_POST['phone_number'];
    $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone); 
    $phone = $countryCode . $cleanPhone;

    $requestedTypeID = $_POST['account_type']; 

    if (empty($requestedTypeID)) {
        $msg = "error: Lütfen bir hesap türü seçin.";
    } elseif (in_array($requestedTypeID, $existingTypes)) {
        // Ekstra güvenlik: Frontend'i aşarsa Backend'de yakala
        $msg = "error: Bu hesap türüne zaten sahipsiniz.";
    } elseif (strlen($cleanPhone) < 7) { 
        $msg = "error: Lütfen geçerli bir telefon numarası giriniz.";
    } else {
        try {
            // HESABI OLUŞTUR
            $accountNumber = "TR" . rand(10000000, 99999999) . rand(10000000, 99999999) . rand(10000000, 99999999);
            
            $sql = "INSERT INTO Accounts (CustomerID, TypeID, Balance, Currency, AccountNumber, OpenDate) 
                    VALUES (?, ?, 0.00, (SELECT Currency FROM AccountTypes WHERE TypeID = ?), ?, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user['CustomerID'], $requestedTypeID, $requestedTypeID, $accountNumber]);
            
            // Bilgileri Güncelle
            $pdo->prepare("UPDATE Customers SET Phone = ?, Address = ? WHERE CustomerID = ?")
                ->execute([$phone, $address, $user['CustomerID']]);

            $msg = "success";
            
            // Başarılı işlemden sonra mevcut hesaplar listesini güncelle ki dropdown anında kilitlensin
            $stmtExisting->execute([$user['CustomerID']]); 
            $existingTypes = $stmtExisting->fetchAll(PDO::FETCH_COLUMN);

        } catch (PDOException $e) {
            $msg = "error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>BANK of İSTÜN - Yeni Hesap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Montserrat', sans-serif; }
        .marquee-container { background: #000; color: #fff; padding: 10px 0; overflow: hidden; white-space: nowrap; font-size: 0.85rem; letter-spacing: 1px; }
        .marquee-content { display: inline-block; animation: marquee 30s linear infinite; }
        @keyframes marquee { 0% { transform: translateX(100%); } 100% { transform: translateX(-100%); } }
        .currency-item { display: inline-block; margin: 0 25px; }

        .bg-login-image {
            background-image: url('bank_woman.jpg'); 
            background-size: cover; background-position: center top;
            min-height: 900px; display: flex; flex-direction: column; 
            justify-content: flex-end; align-items: center; text-align: center; position: relative;
        }
        
        .bg-login-image::before {
            content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0) 0%, rgba(0,0,0,0.8) 100%);
        }
        .image-caption { position: relative; z-index: 2; color: white; padding: 60px 40px; width: 100%; }
        
        /* Footer */
        footer { background-color: #fff; border-top: 1px solid #eee; padding-top: 60px; padding-bottom: 40px; font-size: 0.8rem; letter-spacing: 0.5px; }
        footer h6 { font-weight: 700; letter-spacing: 1px; margin-bottom: 20px; font-size: 0.85rem; color: #000; }
        footer ul { padding-left: 0; list-style: none; }
        footer ul li { margin-bottom: 10px; }
        footer ul li a { color: #666; text-decoration: none; transition: color 0.3s; }
        footer ul li a:hover { color: #000; text-decoration: underline; }
        .newsletter-input { border: none; border-bottom: 1px solid #000; border-radius: 0; padding: 10px 0; width: 100%; font-size: 0.9rem; }
        .newsletter-input:focus { outline: none; border-bottom: 2px solid #000; }

        .phone-group { display: flex; width: 100%; }
        .phone-group .form-select { border-right: none; background-color: #f8f9fa; flex: 0 0 110px; width: 110px; padding-right: 25px; }
        .phone-group .form-control { border-left: 1px solid #ced4da; flex: 1; min-width: 0; }
    </style>
</head>
<body class="bg-light">

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
                <span class="currency-item">BANK of İSTÜN PİYASALARI: CANLI VERİ AKIŞI BEKLENİYOR...</span>
            <?php endif; ?>
        </div>
    </div>

    <nav class="navbar navbar-light bg-white px-5 py-3 border-bottom sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold text-dark fs-4 d-flex align-items-center" href="../index.php">
                <img src="../logo.png" alt="BANK of İSTÜN" height="50" class="me-3" style="object-fit: contain;">
                <div>
                    <span class="d-block" style="line-height:1; font-weight:800;">BANK of İSTÜN</span>
                    <small class="text-muted" style="font-size: 0.6rem; letter-spacing: 2px;">PREMIUM BANKING</small>
                </div>
            </a>
            <a href="../index.php" class="btn btn-dark rounded-0 px-4">
                <i class="fa fa-arrow-left me-2"></i> GERİ DÖN
            </a>
        </div>
    </nav>

    <div class="container py-5 my-3">
        <div class="card shadow-lg border-0" style="border-radius: 0; overflow: hidden;">
            <div class="row g-0">
                
                <div class="col-lg-5 d-none d-lg-flex bg-login-image">
                    <div class="image-caption text-start">
                        <h2 class="fw-bold mb-3 display-6" style="text-shadow: 2px 2px 10px rgba(0,0,0,0.5);">Size Özel<br>Bankacılık</h2>
                        <p class="fs-6 opacity-90 mb-4" style="text-shadow: 1px 1px 5px rgba(0,0,0,0.5);">
                            BANK of İSTÜN ayrıcalıklarını hemen yaşamaya başlayın.
                        </p>
                        <div class="d-flex gap-3">
                            <div class="badge bg-white text-dark p-2 rounded-0"><i class="fa fa-gem me-1"></i> Premium</div>
                            <div class="badge bg-white text-dark p-2 rounded-0"><i class="fa fa-globe me-1"></i> Global</div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7 bg-white">
                    <div class="card-body p-5">
                        <div class="mb-4">
                            <h3 class="fw-bold text-dark">Hesap Açılış Başvurusu</h3>
                            <p class="text-muted small">Aşağıdaki bilgileri doldurarak işleminizi tamamlayın.</p>
                        </div>

                        <?php if($msg == "success"): ?>
                            <div class="alert alert-success text-center py-5 rounded-0 bg-success text-white border-0">
                                <i class="fa fa-check-circle fa-5x mb-3"></i>
                                <h4 class="fw-bold">Tebrikler, Hesabınız Açıldı!</h4>
                                <p>Yeni hesabınız kullanıma hazırdır. Varlıklarım sayfasından kontrol edebilirsiniz.</p>
                                <a href="../index.php" class="btn btn-light text-success fw-bold mt-3 px-4 rounded-pill">Varlıklarıma Git</a>
                            </div>
                        <?php else: ?>
                            
                            <?php if(strpos($msg, 'error') !== false): ?>
                                <div class="alert alert-danger rounded-0"><?= str_replace("error: ", "", $msg) ?></div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="small text-muted fw-bold mb-1">AD SOYAD</label>
                                        <input type="text" class="form-control bg-light border-0 rounded-0 py-2" value="<?= htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small text-muted fw-bold mb-1">E-POSTA</label>
                                        <input type="email" class="form-control bg-light border-0 rounded-0 py-2" value="<?= htmlspecialchars($user['Email']) ?>" readonly>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="small text-muted fw-bold mb-1">TELEFON</label>
                                        <div class="input-group phone-group">
                                            <select name="country_code" class="form-select bg-light fw-bold rounded-0 border-0">
                                                <optgroup label="Sık Kullanılanlar">
                                                    <option value="+90" selected>🇹🇷 +90</option>
                                                    <option value="+994">🇦🇿 +994</option>
                                                    <option value="+1">🇺🇸 +1</option>
                                                    <option value="+49">🇩🇪 +49</option>
                                                </optgroup>
                                            </select>
                                            
                                            <input type="tel" name="phone_number" id="phoneInput" 
                                                   class="form-control rounded-0" 
                                                   placeholder="(5XX) XXX XX XX" 
                                                   maxlength="15" 
                                                   value="<?= htmlspecialchars($user['Phone'] ?? '') ?>" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="small text-muted fw-bold mb-1">YAŞ</label>
                                        <input type="number" name="age" class="form-control rounded-0" placeholder="25" min="18" required>
                                    </div>

                                    <div class="col-12">
                                        <label class="small text-muted fw-bold mb-1 text-primary">AÇILACAK HESAP TÜRÜ</label>
                                        <select name="account_type" class="form-select rounded-0 border-primary shadow-sm" required style="font-weight: 600;">
                                            <option value="" selected>Bir Hesap Türü Seçiniz...</option>
                                            <?php foreach ($allTypes as $type): ?>
                                                <?php 
                                                    // Kullanıcı bu hesaba zaten sahip mi?
                                                    $isOwned = in_array($type['TypeID'], $existingTypes);
                                                    $disabledAttr = $isOwned ? 'disabled' : '';
                                                    $extraText = $isOwned ? ' (Zaten Mevcut)' : '';
                                                    $style = $isOwned ? 'color: #999; background-color: #eee;' : '';
                                                ?>
                                                <option value="<?= $type['TypeID'] ?>" <?= $disabledAttr ?> style="<?= $style ?>">
                                                    <?= $type['TypeName'] ?> (<?= $type['Currency'] ?>) <?= $extraText ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Mevcut hesap türlerinden tekrar açılamaz.</div>
                                    </div>

                                    <div class="col-12">
                                        <label class="small text-muted fw-bold mb-1">İKAMET ADRESİ</label>
                                        <textarea name="address" class="form-control rounded-0" style="height: 80px" required><?= htmlspecialchars($user['Address'] ?? '') ?></textarea>
                                    </div>

                                    <div class="col-12">
                                        <label class="small text-muted fw-bold mb-1">KULLANIM AMACI</label>
                                        <select name="purpose" class="form-select rounded-0" required>
                                            <option value="">Seçiniz...</option>
                                            <option value="Maas">Maaş / Gelir</option>
                                            <option value="Yatirim">Yatırım / Birikim</option>
                                            <option value="Ticari">Ticari İşlemler</option>
                                            <option value="Gunluk">Günlük Harcamalar</option>
                                        </select>
                                    </div>

                                    <div class="col-12 mt-4">
                                        <button class="btn btn-primary w-100 py-3 rounded-0 fw-bold shadow-sm text-uppercase" type="submit" style="background-color: #0d6efd; border:none; letter-spacing: 1px;">
                                            BAŞVURUYU GÖNDER
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="row mb-5">
                <div class="col-md-6">
                    <h5 class="mb-3 text-uppercase" style="letter-spacing: 2px;">Bültenimize Abone Olun</h5>
                    <form action="#"><input type="email" class="newsletter-input" placeholder="E-POSTA ADRESİNİZİ BURAYA GİRİN"></form>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 col-6"><h6>YARDIM</h6><ul><li><a href="#">HESABIM</a></li><li><a href="#">ŞİFRE</a></li><li><a href="#">KARTLAR</a></li></ul></div>
                <div class="col-md-3 col-6"><h6>BİZİ TAKİP EDİN</h6><ul><li><a href="#">INSTAGRAM</a></li><li><a href="#">TWITTER</a></li><li><a href="#">LINKEDIN</a></li></ul></div>
                <div class="col-md-3 col-6"><h6>ŞİRKET</h6><ul><li><a href="#">HAKKIMIZDA</a></li><li><a href="#">KARİYER</a></li><li><a href="#">BASIN</a></li></ul></div>
                <div class="col-md-3 col-6"><h6>POLİTİKALAR</h6><ul><li><a href="#">GİZLİLİK</a></li><li><a href="#">ÇEREZLER</a></li><li><a href="#">KVKK</a></li></ul></div>
            </div>
            <div class="row mt-5 pt-4 border-top">
                <div class="col-md-6 text-muted small">İSTANBUL / TÜRKİYE</div>
                <div class="col-md-6 text-end text-muted small">&copy; 2026 BANK of İSTÜN A.Ş.</div>
            </div>
        </div>
    </footer>

    <script>
        const phoneInput = document.getElementById('phoneInput');
        phoneInput.addEventListener('input', function (e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
            e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? ' ' + x[3] : '') + (x[4] ? ' ' + x[4] : '');
        });
    </script>

</body>
</html>