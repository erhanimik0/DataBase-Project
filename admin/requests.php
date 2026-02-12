<?php
session_start();
require_once '../includes/db.php';

// Sadece Admin Girebilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Bekleyen (Pending) Başvuruları Çek
$sql = "SELECT * FROM AccountApplications WHERE Status = 'Pending' ORDER BY ApplicationDate DESC";
$requests = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Hesap Açma Talepleri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif; background-color: #f8f9fa; }
        .card { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .badge-purpose { font-size: 0.9rem; padding: 8px 12px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark bg-dark px-4 py-3">
        <a class="navbar-brand fw-bold" href="../index.php">
            <i class="fa fa-arrow-left me-2"></i> Yönetim Paneline Dön
        </a>
        <span class="text-white">Talep Yönetimi</span>
    </nav>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="fa fa-envelope-open-text text-primary"></i> Bekleyen Hesap Talepleri</h3>
            <span class="badge bg-danger fs-6"><?= count($requests) ?> Bekleyen</span>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($requests)): ?>
            <div class="card p-5 text-center">
                <div class="card-body">
                    <i class="fa fa-check-circle fa-5x text-success mb-3"></i>
                    <h4>Harika! Bekleyen talep yok.</h4>
                    <p class="text-muted">Tüm başvurular incelendi ve yanıtlandı.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($requests as $req): ?>
                    <div class="col-12 mb-4">
                        <div class="card h-100 border-start border-4 border-primary">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-3 text-center border-end">
                                        <div class="avatar-circle mb-2">
                                            <i class="fa fa-user-circle fa-4x text-secondary"></i>
                                        </div>
                                        <h5 class="fw-bold mb-1"><?= $req['FullName'] ?></h5>
                                        <span class="badge bg-secondary"><?= $req['Age'] ?> Yaşında</span>
                                        <div class="mt-2 text-muted small">
                                            <i class="fa fa-clock"></i> <?= date("d.m.Y H:i", strtotime($req['ApplicationDate'])) ?>
                                        </div>
                                    </div>

                                    <div class="col-md-6 px-4">
                                        <h6 class="text-uppercase text-muted small fw-bold mb-3">Başvuru Detayları</h6>
                                        
                                        <div class="mb-2">
                                            <i class="fa fa-phone text-primary me-2"></i> <strong>Telefon:</strong> <?= $req['Phone'] ?>
                                        </div>
                                        <div class="mb-2">
                                            <i class="fa fa-envelope text-primary me-2"></i> <strong>Email:</strong> <?= $req['Email'] ?>
                                        </div>
                                        <div class="mb-2">
                                            <i class="fa fa-map-marker-alt text-primary me-2"></i> <strong>Adres:</strong> 
                                            <span class="text-muted"><?= $req['Address'] ?></span>
                                        </div>
                                        <div class="mt-3">
                                            <strong>Hesap Açma Amacı:</strong><br>
                                            <?php 
                                                $badges = [
                                                    'Maas' => ['bg' => 'success', 'text' => 'Maaş Ödemesi'],
                                                    'Yatirim' => ['bg' => 'warning text-dark', 'text' => 'Yatırım / Birikim'],
                                                    'Ticari' => ['bg' => 'info text-dark', 'text' => 'Ticari Faaliyet'],
                                                    'Egitim' => ['bg' => 'primary', 'text' => 'Eğitim'],
                                                    'Gunluk' => ['bg' => 'secondary', 'text' => 'Günlük Kullanım']
                                                ];
                                                $p = $req['Purpose'];
                                                $badge = $badges[$p] ?? ['bg' => 'secondary', 'text' => $p];
                                            ?>
                                            <span class="badge bg-<?= $badge['bg'] ?> badge-purpose mt-1">
                                                <?= $badge['text'] ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-md-3 text-center border-start">
                                        <div class="d-grid gap-2">
                                            <form action="process_request.php" method="POST">
                                                <input type="hidden" name="id" value="<?= $req['ApplicationID'] ?>">
                                                <input type="hidden" name="customer_id" value="<?= $req['CustomerID'] ?>">
                                                
                                                <button type="submit" name="action" value="approve" class="btn btn-success w-100 mb-2 py-2 fw-bold">
                                                    <i class="fa fa-check"></i> ONAYLA
                                                </button>
                                                
                                                <button type="submit" name="action" value="reject" class="btn btn-outline-danger w-100 py-2 fw-bold">
                                                    <i class="fa fa-times"></i> REDDET
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>