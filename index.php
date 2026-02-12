<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/currency_helper.php';

// Güvenlik: Giriş yapılmış mı?
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$userRole = $_SESSION['role'];
$userId   = $_SESSION['user_id'];

// --- YÖNLENDİRME (ROUTING) MERKEZİ ---
// Eğer giren kişi MÜŞTERİ DEĞİLSE, kendi paneline postala.
if ($userRole == 1) { header("Location: admin/dashboard.php"); exit; }
if ($userRole == 2) { header("Location: staff/dashboard.php"); exit; }

// Canlı Kurlar
$kurlar = [];
try {
    if (function_exists('getLiveCurrencies')) {
        $kurlar = getLiveCurrencies();
    } else {
        $kurlar = [
            'USD' => ['alis' => 34.00, 'satis' => 34.50],
            'EUR' => ['alis' => 37.00, 'satis' => 37.50],
            'GA'  => ['name' => 'Gram Altın', 'alis' => 2900, 'satis' => 2950]
        ];
    }
} catch (Exception $e) { $kurlar = []; }

// Müşteri Verilerini Çek
$hesaplar = [];
$krediler = [];
$userBranchName = "";

try {
    // 1. Müşteri ID'sini ve Şube Bilgisini Bul
    // NOT: Veritabanında UserID bağlantısını düzelttiğimiz için artık doğru çalışır.
    $stmtC = $pdo->prepare("
        SELECT c.CustomerID, b.BranchName 
        FROM Customers c 
        LEFT JOIN Branches b ON c.BranchID = b.BranchID 
        WHERE c.UserID = ?
    ");
    $stmtC->execute([$userId]);
    $custData = $stmtC->fetch();

    if ($custData) {
        $custID = $custData['CustomerID'];
        $userBranchName = $custData['BranchName'] ?? 'Merkez Şube';

        // 2. Hesapları Getir
        $sql = "SELECT a.*, t.TypeName, t.Currency 
                FROM Accounts a 
                JOIN AccountTypes t ON a.TypeID = t.TypeID 
                WHERE a.CustomerID = ? 
                ORDER BY a.Balance DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$custID]);
        $hesaplar = $stmt->fetchAll();

        // 3. Kredileri Getir
        $stmtKredi = $pdo->prepare("SELECT * FROM LoanRequests WHERE CustomerID = ? ORDER BY RequestDate DESC");
        $stmtKredi->execute([$custID]);
        $krediler = $stmtKredi->fetchAll();
    }
} catch (PDOException $e) {
    // Hata oluşursa sessiz kal
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>BANK of İSTÜN - Ana Sayfa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Montserrat', sans-serif; }
        .card-hover:hover { transform: translateY(-3px); transition: 0.3s; box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
        .marquee-container { background: #000; color: #fff; padding: 10px 0; overflow: hidden; white-space: nowrap; font-size: 0.85rem; letter-spacing: 1px; border-bottom: 1px solid #333; }
        .marquee-content { display: inline-block; animation: marquee 30s linear infinite; }
        @keyframes marquee { 0% { transform: translateX(100%); } 100% { transform: translateX(-100%); } }
        .currency-item { display: inline-block; margin: 0 25px; }
        .empty-state-card { border: 2px dashed #0d6efd; background-color: #f0f8ff; transition: all 0.3s; }
        .empty-state-card:hover { background-color: #e0f0ff; border-color: #0b5ed7; }
        footer { background-color: #fff; border-top: 1px solid #eee; padding-top: 60px; padding-bottom: 40px; font-size: 0.8rem; letter-spacing: 0.5px; margin-top: 80px; }
        footer h6 { font-weight: 700; letter-spacing: 1px; margin-bottom: 20px; font-size: 0.85rem; color: #000; }
        footer ul { padding-left: 0; list-style: none; }
        footer ul li { margin-bottom: 10px; }
        footer ul li a { color: #666; text-decoration: none; transition: color 0.3s; }
        footer ul li a:hover { color: #000; text-decoration: underline; }
        .newsletter-input { border: none; border-bottom: 1px solid #000; border-radius: 0; padding: 10px 0; width: 100%; font-size: 0.9rem; }
        .newsletter-input:focus { outline: none; border-bottom: 2px solid #000; }
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
                <span class="currency-item">BANK of İSTÜN PİYASALARI: VERİ AKIŞI BEKLENİYOR...</span>
            <?php endif; ?>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-light bg-white px-4 shadow-sm sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="logo.png" alt="Logo" height="50" class="me-3" style="object-fit: contain;">
                <div style="line-height: 1.2;">
                    <span class="d-block fw-bold text-dark" style="font-size: 1.4rem; letter-spacing: -1px;">BANK of İSTÜN</span>
                    <span class="d-block text-muted" style="font-size: 0.7rem; letter-spacing: 2px;">PREMIUM BANKING</span>
                </div>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="ms-auto d-flex text-dark align-items-center">
                    <div class="me-4 text-end d-none d-lg-block">
                        <span class="d-block small text-muted">Hoş Geldiniz</span>
                        <span class="fw-bold"><i class="fa fa-user-circle text-primary"></i> <?= isset($_SESSION['fullname']) ? htmlspecialchars($_SESSION['fullname']) : 'Kullanıcı' ?></span>
                        
                        <?php if(!empty($userBranchName)): ?>
                            <span class="d-block small text-secondary fw-bold mt-1 text-end" style="font-size: 0.75rem; letter-spacing: 0.5px; opacity: 0.8;">
                                <i class="fa fa-building me-1"></i> <?= mb_strtoupper($userBranchName, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>

                    </div>
                    <a href="auth/logout.php" class="btn btn-outline-danger btn-sm px-4 rounded-0 ms-3">GÜVENLİ ÇIKIŞ</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-5" style="min-height: 600px;">
        
        <?php if(isset($_GET['error']) && $_GET['error'] == 'balance_not_zero'): ?>
            <div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa fa-exclamation-triangle me-2"></i> <strong>İşlem Başarısız:</strong> Hesabı kapatmak için bakiyenizin 0.00 olması gerekir.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif(isset($_GET['success']) && $_GET['success'] == 'account_closed'): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa fa-check-circle me-2"></i> Hesap başarıyla kapatıldı.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                    <h4 class="mb-0 fw-bold">Varlıklarım</h4>
                    <a href="customer/open_account_request.php" class="btn btn-sm btn-dark rounded-0 px-3">
                        <i class="fa fa-plus"></i> HESAP AÇ
                    </a>
                </div>
                
                <?php if (empty($hesaplar)): ?>
                    <div class="card empty-state-card shadow-sm text-center py-5">
                        <div class="card-body">
                            <i class="fa fa-university fa-5x text-primary mb-3"></i>
                            <h3 class="fw-bold text-dark">Henüz Bir Hesabınız Yok</h3>
                            <p class="text-muted fs-5 mb-4">Bankacılık işlemlerine başlamak için hemen ilk hesabınızı oluşturun.</p>
                            <a href="customer/open_account_request.php" class="btn btn-primary btn-lg px-5 py-3 shadow fw-bold fs-5 rounded-0">
                                <i class="fa fa-plus-circle me-2"></i> YENİ HESAP AÇ
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($hesaplar as $h): ?>
                            <?php 
                                // RENK ve SEMBOL AYARI
                                $symbol = "₺";
                                $borderColor = "#0d6efd"; // Mavi
                                
                                if ($h['Currency'] == 'USD') { 
                                    $symbol = "$"; 
                                    $borderColor = "#198754"; // Yeşil
                                } elseif ($h['Currency'] == 'EUR') { 
                                    $symbol = "€"; 
                                    $borderColor = "#0dcaf0"; // Açık Mavi
                                } elseif ($h['Currency'] == 'GR' || $h['Currency'] == 'GA') { 
                                    $symbol = "gr"; 
                                    $borderColor = "#ffc107"; // Sarı
                                }
                            ?>
                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm border-start border-4 h-100 card-hover bg-white" style="border-left: 5px solid <?= $borderColor ?> !important;">
                                    <div class="card-body">
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="text-uppercase mb-0 fw-bold" style="color: <?= $borderColor ?>"><?= $h['TypeName'] ?></h6>
                                            
                                            <button onclick="checkAccountStatus(<?= $h['AccountID'] ?>, '<?= $h['Balance'] ?>')" 
                                                    class="btn btn-sm text-secondary hover-danger p-0" 
                                                    title="Hesabı Kapat">
                                                <i class="fa fa-trash-alt"></i>
                                            </button>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center">
                                            <h3 class="fw-bold mb-0"><?= number_format($h['Balance'], 2) ?> <small class="fs-6 text-muted"><?= $symbol ?></small></h3>
                                            <i class="fa fa-wallet fa-2x text-black-50 opacity-25"></i>
                                        </div>

                                        <small class="text-muted d-block mt-3 mb-3 font-monospace">IBAN: <span class="text-dark fw-bold"><?= $h['AccountNumber'] ?? $h['IBAN'] ?></span></small>
                                        
                                        <div class="d-grid gap-2 d-md-flex">
                                            <a href="customer/account_history.php?id=<?= $h['AccountID'] ?>" class="btn btn-sm btn-outline-primary flex-grow-1 rounded-0 fw-bold">
                                                HAREKETLER
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <h4 class="mb-3 border-bottom pb-2 fw-bold">Hızlı İşlemler</h4>
                
                <div class="card mb-3 shadow-sm card-hover border-0">
                    <div class="card-body text-center">
                        <div class="mb-3"><i class="fa fa-paper-plane fa-3x text-primary"></i></div>
                        <h5 class="card-title fw-bold">Para Transferi</h5>
                        <p class="small text-muted mb-3">Havale veya EFT ile hızlıca para gönderin.</p>
                        <a href="customer/transfer_fast.php" class="btn btn-outline-primary w-100 rounded-0 stretched-link fw-bold">GÖNDER</a>
                    </div>
                </div>

                <div class="card mb-3 shadow-sm card-hover border-0">
                    <div class="card-body text-center">
                        <div class="mb-3"><i class="fa fa-hand-holding-usd fa-3x text-success"></i></div>
                        <h5 class="card-title fw-bold">Kredi Başvurusu</h5>
                        <p class="small text-muted mb-3">İhtiyaç, Araç veya Konut kredisi başvurusu yapın.</p>
                        <a href="customer/apply_loan.php" class="btn btn-outline-success w-100 rounded-0 stretched-link">BAŞVURU YAP</a>
                    </div>
                </div>
                <div class="card mb-3 shadow-sm card-hover border-0">
                    <div class="card-body text-center">
                        <div class="mb-3"><i class="fa fa-calculator fa-3x text-warning"></i></div>
                        <h5 class="card-title fw-bold">Vadeli Faiz Hesapla</h5>
                        <p class="small text-muted mb-3">Paranızın ne kadar değerleneceğini görün.</p>
                        <a href="customer/interest_calculator.php" class="btn btn-outline-warning w-100 rounded-0 stretched-link">HESAPLA</a>
                    </div>
                </div>
                <div class="card mb-3 shadow-sm border-danger card-hover">
                    <div class="card-body text-center">
                        <div class="mb-3"><i class="fa fa-qrcode fa-3x text-danger"></i></div>
                        <h5 class="card-title text-danger fw-bold">QR ATM</h5>
                        <p class="small text-muted mb-3">Kartsız para yatırma ve çekme işlemi.</p>
                        <a href="customer/atm.php" class="btn btn-danger w-100 rounded-0 stretched-link">ATM'YE GİT</a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if(!empty($krediler)): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm border-0 mb-5">
                    <div class="card-header bg-white fw-bold py-3">
                        <i class="fa fa-history text-primary"></i> Geçmiş Kredi Başvurularım
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kredi Türü</th>
                                        <th>Tutar</th>
                                        <th>Mesaj</th>
                                        <th>Durum</th>
                                        <th>Tarih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($krediler as $k): ?>
                                        <tr>
                                            <td>İhtiyaç Kredisi</td> <td class="fw-bold"><?= number_format($k['Amount'], 2) ?> TL</td>
                                            <td class="text-muted small"><?= htmlspecialchars($k['Message'] ?? '-') ?></td>
                                            <td>
                                                <?php if($k['Status'] == 'Approved'): ?>
                                                    <span class="badge bg-success rounded-0">Onaylandı</span>
                                                <?php elseif($k['Status'] == 'Rejected'): ?>
                                                    <span class="badge bg-danger rounded-0">Reddedildi</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary rounded-0">Değerlendiriliyor</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date("d.m.Y", strtotime($k['RequestDate'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

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
                <div class="col-md-3 col-6"><h6>BİZİ TAKİP EDİN</h6><ul><li><a href="#">INSTAGRAM</a></li><li><a href="#">TWITTER</a></li></ul></div>
                <div class="col-md-3 col-6"><h6>BANK of İSTÜN</h6><ul><li><a href="#">HAKKIMIZDA</a></li><li><a href="#">OFİSLERİMİZ</a></li></ul></div>
                <div class="col-md-3 col-6"><h6>POLİTİKALAR</h6><ul><li><a href="#">GİZLİLİK POLİTİKASI</a></li><li><a href="#">ÇEREZLER</a></li></ul></div>
            </div>
            <div class="row mt-5 pt-4 border-top">
                <div class="col-md-6 text-muted small">İSTANBUL / TÜRKİYE</div>
                <div class="col-md-6 text-end text-muted small">&copy; 2026 BANK of İSTÜN A.Ş.</div>
            </div>
        </div>
    </footer>

    <div class="modal fade" id="balanceWarningModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-warning border-0">
            <h5 class="modal-title fw-bold text-dark"><i class="fa fa-exclamation-triangle me-2"></i>İşlem Yapılamaz</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body text-center py-4">
            <i class="fa fa-wallet fa-3x text-warning mb-3"></i>
            <h4 class="fw-bold">Bakiye Sıfır Değil!</h4>
            <p class="text-muted">Bu hesabı kapatabilmek için içindeki parayı boşaltmanız gerekmektedir.</p>
            <div class="d-grid gap-2 col-10 mx-auto mt-4">
                <a href="customer/transfer_fast.php" class="btn btn-outline-dark fw-bold">PARA TRANSFERİ YAP</a>
                <a href="customer/atm.php" class="btn btn-outline-danger fw-bold">ATM'DEN PARA ÇEK</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white border-0">
            <h5 class="modal-title fw-bold"><i class="fa fa-trash-alt me-2"></i>Hesap Kapatma Onayı</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body text-center py-4">
            <i class="fa fa-question-circle fa-3x text-danger mb-3"></i>
            <p class="fs-5">Bu hesabı kalıcı olarak silmek istediğinize emin misiniz?</p>
            <p class="small text-muted">Bu işlem geri alınamaz ve işlem geçmişi silinir.</p>
          </div>
          <div class="modal-footer justify-content-center border-0">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
            
            <form action="customer/close_account.php" method="POST" id="deleteForm">
                <input type="hidden" name="account_id" id="deleteAccountID">
                <button type="submit" class="btn btn-danger fw-bold px-4">Evet, Hesabı Kapat</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function checkAccountStatus(id, balance) {
            // Gelen bakiye float'a çevrilir
            let bakiye = parseFloat(balance);

            if (bakiye > 0) {
                // Bakiye varsa UYARI popup'ını aç
                var myModal = new bootstrap.Modal(document.getElementById('balanceWarningModal'));
                myModal.show();
            } else {
                // Bakiye 0 ise ONAY popup'ını aç
                document.getElementById('deleteAccountID').value = id;
                var myModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
                myModal.show();
            }
        }
    </script>
</body>
</html>