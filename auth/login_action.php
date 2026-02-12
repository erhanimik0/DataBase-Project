<?php
session_start();
require_once '../includes/db.php';

// Hata raporlamayı açalım ki bir sorun varsa görelim
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        header("Location: login.php?error=empty");
        exit;
    }

    try {
        // Stored Procedure yerine doğrudan güvenli SQL sorgusu kullanıyoruz.
        // Bu sayede 'banka_db' isim hatasına takılmayacaksın.
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE Email = ? AND Password = MD5(?)");
        $stmt->execute([$email, $password]);
        $user = $stmt->fetch();

        if ($user) {
            // Giriş Başarılı - Session'ları Oluştur
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['role'] = $user['RoleID'];
            $_SESSION['email'] = $user['Email'];

            // İsim bilgisini bulmaya çalış (Opsiyonel)
            $fullname = "Kullanıcı";
            if ($user['RoleID'] == 3) { // Müşteri
                $stmtCust = $pdo->prepare("SELECT FirstName, LastName FROM Customers WHERE UserID = ?");
                $stmtCust->execute([$user['UserID']]);
                $cust = $stmtCust->fetch();
                if ($cust) $fullname = $cust['FirstName'] . ' ' . $cust['LastName'];
            } elseif ($user['RoleID'] == 2) { // Personel
                $stmtEmp = $pdo->prepare("SELECT FirstName, LastName FROM Employees WHERE UserID = ?");
                $stmtEmp->execute([$user['UserID']]);
                $emp = $stmtEmp->fetch();
                if ($emp) $fullname = $emp['FirstName'] . ' ' . $emp['LastName'];
            }
            $_SESSION['fullname'] = $fullname;

            // Role Göre Yönlendirme
            if ($user['RoleID'] == 1) {
                header("Location: ../admin/dashboard.php");
            } elseif ($user['RoleID'] == 2) {
                header("Location: ../staff/dashboard.php");
            } else {
                header("Location: ../index.php");
            }
            exit;

        } else {
            // Hatalı Şifre
            header("Location: login.php?error=invalid");
            exit;
        }

    } catch (PDOException $e) {
        die("Sorgu Hatası: " . $e->getMessage());
    }
} else {
    // POST değilse login sayfasına at
    header("Location: login.php");
    exit;
}
?>