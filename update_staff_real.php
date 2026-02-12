<?php
// Hataları Göster
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db.php';

// Türkçe karakter temizleme
function temizle($text) {
    $search  = ['ç', 'Ç', 'ğ', 'Ğ', 'ı', 'İ', 'ö', 'Ö', 'ş', 'Ş', 'ü', 'Ü', ' ', '.', ','];
    $replace = ['c', 'c', 'g', 'g', 'i', 'i', 'o', 'o', 's', 's', 'u', 'u', '', '', ''];
    return strtolower(str_replace($search, $replace, $text));
}

// GERÇEKÇİ İSİM HAVUZU
$erkek_isimler = ['Ahmet', 'Mehmet', 'Mustafa', 'Can', 'Burak', 'Emre', 'Murat', 'Hakan', 'Oğuz', 'Yusuf', 'Eren', 'Kerem', 'Barış', 'Serkan', 'Cem', 'Deniz', 'Umut', 'Volkan', 'Tolga', 'Onur'];
$kadin_isimler = ['Ayşe', 'Fatma', 'Zeynep', 'Elif', 'Gamze', 'Buse', 'Selin', 'Derya', 'Merve', 'Esra', 'Gizem', 'Damla', 'İrem', 'Ece', 'Nazlı', 'Bahar', 'Pelin', 'Seda', 'Yasemin', 'Sinem'];
$soyisimler    = ['Yılmaz', 'Kaya', 'Demir', 'Çelik', 'Şahin', 'Yıldız', 'Yıldırım', 'Öztürk', 'Aydın', 'Özdemir', 'Arslan', 'Doğan', 'Kılıç', 'Aslan', 'Çetin', 'Kara', 'Koç', 'Kurt', 'Özkan', 'Şimşek'];

try {
    // CEO hariç tüm personelleri çek
    $sql = "SELECT e.EmployeeID, e.UserID, b.BranchName 
            FROM Employees e 
            JOIN Branches b ON e.BranchID = b.BranchID 
            WHERE e.Title != 'CEO'";
    $stmt = $pdo->query($sql);
    $employees = $stmt->fetchAll();

    echo "<h1>🔄 Personel Kimlikleri Yenileniyor...</h1>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; font-family: sans-serif; width: 100%;'>";
    echo "<tr style='background:#333; color:white;'><th>Eski Durum</th><th>Yeni İsim</th><th>Yeni Şube E-Postası</th><th>Durum</th></tr>";

    $usedEmails = []; // Çakışmayı önlemek için takip listesi

    foreach ($employees as $emp) {
        
        // 1. RASTGELE CİNSİYET VE İSİM SEÇ
        if (rand(0, 1) == 0) {
            $ad = $erkek_isimler[array_rand($erkek_isimler)];
        } else {
            $ad = $kadin_isimler[array_rand($kadin_isimler)];
        }
        $soyad = $soyisimler[array_rand($soyisimler)];

        // 2. ŞUBE İSMİNİ FORMATLA (Kadıköy Şubesi -> kadikoy)
        $rawBranch = str_replace([' Şubesi', ' Şube'], '', $emp['BranchName']);
        $subeKodu = temizle($rawBranch);

        // 3. E-POSTA OLUŞTUR (isim@sube.com)
        $isimKodu = temizle($ad);
        $emailBase = $isimKodu . "@" . $subeKodu . ".com";
        $finalEmail = $emailBase;

        // Eğer bu mail daha önce üretildiyse veya veritabanında varsa sonuna sayı ekle
        // Örn: ahmet@kadikoy.com varsa ahmet2@kadikoy.com yap.
        $counter = 2;
        while (in_array($finalEmail, $usedEmails)) {
            $finalEmail = $isimKodu . $counter . "@" . $subeKodu . ".com";
            $counter++;
        }
        $usedEmails[] = $finalEmail;

        // 4. VERİTABANINI GÜNCELLE
        // A. İsim Soyisim Güncelle (Employees Tablosu)
        $updEmp = $pdo->prepare("UPDATE Employees SET FirstName = ?, LastName = ? WHERE EmployeeID = ?");
        $updEmp->execute([$ad, $soyad, $emp['EmployeeID']]);

        // B. E-Posta Güncelle (Users Tablosu)
        // Try-Catch kullanıyoruz ki veritabanında "Duplicate" hatası verirse script durmasın
        try {
            $updUser = $pdo->prepare("UPDATE Users SET Email = ? WHERE UserID = ?");
            $updUser->execute([$finalEmail, $emp['UserID']]);
            $status = "<span style='color:green; font-weight:bold;'>BAŞARILI</span>";
        } catch (PDOException $ex) {
            // Eğer veritabanı "Bu mail var" derse, soyadını ekleyip tekrar dene
            $finalEmail = $isimKodu . "." . temizle($soyad) . "@" . $subeKodu . ".com";
            $pdo->prepare("UPDATE Users SET Email = ? WHERE UserID = ?")->execute([$finalEmail, $emp['UserID']]);
            $status = "<span style='color:orange;'>Çakışma Giderildi</span>";
        }

        echo "<tr>";
        echo "<td style='color:#999;'>ID: {$emp['EmployeeID']}</td>";
        echo "<td style='font-weight:bold;'>$ad $soyad</td>";
        echo "<td style='color:blue;'>$finalEmail</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }

    echo "</table>";
    echo "<br><h3>✅ Tüm personeller gerçek isimlere ve şube e-postalarına kavuştu!</h3>";
    echo "<p>Şifreleri değişmedi: <b>1234</b></p>";
    echo "<a href='list_employees.php' style='background:blue; color:white; padding:10px; text-decoration:none; border-radius:5px;'>Yeni Listeyi Gör</a>";

} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}
?>