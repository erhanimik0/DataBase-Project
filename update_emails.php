<?php
require_once 'includes/db.php';

// Türkçe karakter değiştirme fonksiyonu
function tr_to_eng($text) {
    $search  = ['ç', 'Ç', 'ğ', 'Ğ', 'ı', 'İ', 'ö', 'Ö', 'ş', 'Ş', 'ü', 'Ü', ' '];
    $replace = ['c', 'c', 'g', 'g', 'i', 'i', 'o', 'o', 's', 's', 'u', 'u', ''];
    return str_replace($search, $replace, $text);
}

try {
    // 1. Personelleri Çek
    $sql = "SELECT e.EmployeeID, e.UserID, e.FirstName, b.BranchName 
            FROM Employees e 
            JOIN Branches b ON e.BranchID = b.BranchID 
            WHERE e.Title != 'CEO'";
    $stmt = $pdo->query($sql);
    $employees = $stmt->fetchAll();

    echo "<h1>🔄 Personel E-Postaları Güncelleniyor...</h1>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; font-family: Arial;'>";
    echo "<tr style='background:#eee;'><th>İsim</th><th>Şube</th><th>Yeni E-Posta (Giriş)</th><th>Şifre</th></tr>";

    foreach ($employees as $emp) {
        // İsim ve Şube adını temizle
        $cleanName = strtolower(tr_to_eng($emp['FirstName']));
        
        $rawBranch = str_replace([' Şubesi', ' Şube', 'Subesi', 'Sube'], '', $emp['BranchName']);
        $cleanBranch = strtolower(tr_to_eng($rawBranch));

        // E-posta Çakışmasını Önlemek İçin SONUNA ID EKLİYORUZ
        // Örn: personel_merkez_4@banka.com
        $newEmail = "{$cleanName}_{$cleanBranch}_{$emp['EmployeeID']}@banka.com";
        
        // Veritabanını Güncelle
        // Hata olursa (örn: mail hala çakışırsa) script durmasın diye try-catch içinde yapıyoruz
        try {
            $update = $pdo->prepare("UPDATE Users SET Email = ? WHERE UserID = ?");
            $update->execute([$newEmail, $emp['UserID']]);
            
            echo "<tr>";
            echo "<td>" . $emp['FirstName'] . "</td>";
            echo "<td>" . $emp['BranchName'] . "</td>";
            echo "<td style='color:blue; font-weight:bold;'>" . $newEmail . "</td>";
            echo "<td>1234</td>";
            echo "</tr>";
        } catch (PDOException $ex) {
            echo "<tr><td colspan='4' style='color:red'>Hata (ID: {$emp['EmployeeID']}): " . $ex->getMessage() . "</td></tr>";
        }
    }
    echo "</table>";
    echo "<br><h2 style='color:green;'>✅ İşlem Tamamlandı!</h2>";
    echo "<a href='list_employees.php' style='font-size:20px; font-weight:bold;'>Listeyi Görmek İçin Tıkla</a>";

} catch (PDOException $e) {
    die("Genel Hata: " . $e->getMessage());
}
?>