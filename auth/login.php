<?php
session_start();
require_once '../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Lütfen tüm alanları doldurun.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Not: Eğer veritabanındaki şifre MD5 ise buradaki kontrol başarısız olabilir.
        // Düz metin (1234 gibi) tutuyorsan bu kod çalışır.
        if ($user && $user['Password'] == $password) { 
            
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['role'] = $user['RoleID'];
            $_SESSION['email'] = $user['Email'];

            // İsim Bulma
            $fullName = "Kullanıcı";
            if ($user['RoleID'] == 3) {
                $stmtC = $pdo->prepare("SELECT FirstName, LastName FROM Customers WHERE UserID = ?");
                $stmtC->execute([$user['UserID']]);
                $d = $stmtC->fetch();
            } else {
                $stmtE = $pdo->prepare("SELECT FirstName, LastName FROM Employees WHERE UserID = ?");
                $stmtE->execute([$user['UserID']]);
                $d = $stmtE->fetch();
            }
            if($d) $_SESSION['fullname'] = $d['FirstName'] . ' ' . $d['LastName'];

            // YÖNLENDİRME
            if ($user['RoleID'] == 1) header("Location: ../admin/dashboard.php");
            elseif ($user['RoleID'] == 2) header("Location: ../staff/dashboard.php");
            else header("Location: ../index.php");
            exit;
        } else {
            $error = "Hatalı E-posta veya Şifre!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap - BANK of İSTÜN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Montserrat', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .login-card {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .login-logo {
            width: 80px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .btn-custom {
            background-color: #1877f2;
            border: none;
            color: white;
            font-weight: 700;
            padding: 12px;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-custom:hover {
            background-color: #166fe5;
            color: white;
        }
        .form-control {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .error-msg {
            color: #dc3545;
            font-size: 0.9rem;
            margin-bottom: 15px;
            text-align: left;
        }
        .footer-link {
            font-size: 0.9rem;
            color: #1877f2;
            text-decoration: none;
        }
        .footer-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="login-card">
    <img src="../logo.png" alt="Logo" class="login-logo" onerror="this.src='https://via.placeholder.com/80?text=BANK'">
    
    <h3 class="fw-bold mb-4 text-dark">Giriş Yap</h3>

    <?php if($error): ?>
        <div class="alert alert-danger py-2 small text-start"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email" class="form-control" placeholder="E-Posta Adresi" required>
        <input type="password" name="password" class="form-control" placeholder="Şifre" required>
        
        <button type="submit" class="btn btn-custom w-100 mt-2 mb-3">GÜVENLİ GİRİŞ</button>
        
        </form>
</div>

</body>
</html>