<?php
require_once 'includes/db.php';

// Varsayılan Şifre
$defaultPass = '1234'; 

try {
    echo "<h1>🚀 Banka Tam Kurulum ve Hiyerarşi Başlatılıyor...</h1>";

    // 1. GÜVENLİK: Yabancı anahtar kontrolünü kapat ve ESKİ TABLOLARI SİL (Temiz Başlangıç)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $tables = ['Users', 'Roles', 'Branches', 'Employees', 'Customers', 'Accounts', 'Transactions', 'LoanRequests', 'AuditLogs', 'AccountTypes'];
    foreach($tables as $t) {
        $pdo->exec("DROP TABLE IF EXISTS $t"); // Varsa sil, yoksa devam et
    }
    echo "<p>🗑️ Eski tablolar ve veriler temizlendi.</p>";

    // 2. TABLOLARI SIFIRDAN OLUŞTUR
    
    // --- ROLES ---
    $pdo->exec("CREATE TABLE Roles (
        RoleID INT AUTO_INCREMENT PRIMARY KEY,
        RoleName VARCHAR(50) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // --- BRANCHES ---
    $pdo->exec("CREATE TABLE Branches (
        BranchID INT AUTO_INCREMENT PRIMARY KEY,
        BranchName VARCHAR(100) NOT NULL,
        City VARCHAR(50),
        Address TEXT,
        Phone VARCHAR(20),
        Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // --- USERS ---
    $pdo->exec("CREATE TABLE Users (
        UserID INT AUTO_INCREMENT PRIMARY KEY,
        RoleID INT,
        Email VARCHAR(100) UNIQUE NOT NULL,
        Password VARCHAR(255) NOT NULL,
        IsActive TINYINT(1) DEFAULT 1,
        Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (RoleID) REFERENCES Roles(RoleID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // --- CUSTOMERS ---
    $pdo->exec("CREATE TABLE Customers (
        CustomerID INT AUTO_INCREMENT PRIMARY KEY,
        UserID INT,
        BranchID INT,
        FirstName VARCHAR(50),
        LastName VARCHAR(50),
        TCKN VARCHAR(11),
        Phone VARCHAR(20),
        FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE,
        FOREIGN KEY (BranchID) REFERENCES Branches(BranchID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // --- EMPLOYEES ---
    $pdo->exec("CREATE TABLE Employees (
        EmployeeID INT AUTO_INCREMENT PRIMARY KEY,
        UserID INT,
        BranchID INT,
        FirstName VARCHAR(50),
        LastName VARCHAR(50),
        Title VARCHAR(50),
        FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE,
        FOREIGN KEY (BranchID) REFERENCES Branches(BranchID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // --- ACCOUNT TYPES ---
    $pdo->exec("CREATE TABLE AccountTypes (
        TypeID INT AUTO_INCREMENT PRIMARY KEY,
        TypeName VARCHAR(50),
        Currency VARCHAR(5) DEFAULT 'TL'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // --- ACCOUNTS ---
    $pdo->exec("CREATE TABLE Accounts (
        AccountID INT AUTO_INCREMENT PRIMARY KEY,
        CustomerID INT,
        TypeID INT,
        AccountNumber VARCHAR(50),
        Balance DECIMAL(15,2) DEFAULT 0.00,
        Currency VARCHAR(5) DEFAULT 'TL',
        BranchID INT,
        Opened_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        IsActive TINYINT(1) DEFAULT 1,
        FOREIGN KEY (CustomerID) REFERENCES Customers(CustomerID),
        FOREIGN KEY (TypeID) REFERENCES AccountTypes(TypeID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // --- TRANSACTIONS ---
    $pdo->exec("CREATE TABLE Transactions (
        TransactionID INT AUTO_INCREMENT PRIMARY KEY,
        AccountID INT,
        TransactionType VARCHAR(50), 
        Amount DECIMAL(15,2),
        Description TEXT,
        TransactionDate DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // --- LOAN REQUESTS ---
    $pdo->exec("CREATE TABLE LoanRequests (
        LoanID INT AUTO_INCREMENT PRIMARY KEY,
        CustomerID INT,
        Amount DECIMAL(15,2),
        Message TEXT,
        Status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
        RequestDate DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (CustomerID) REFERENCES Customers(CustomerID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // --- AUDIT LOGS ---
    $pdo->exec("CREATE TABLE AuditLogs (
        LogID INT AUTO_INCREMENT PRIMARY KEY,
        UserID INT,
        Action VARCHAR(255),
        IPAddress VARCHAR(50),
        LogDate DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    echo "<p>✅ Tüm tablolar başarıyla oluşturuldu.</p>";

    // 3. VERİLERİ DOLDURMA (DATA SEEDING)

    // Roller
    $pdo->exec("INSERT INTO Roles (RoleID, RoleName) VALUES (1, 'Boss'), (2, 'Personel'), (3, 'Musteri')");
    
    // Hesap Türleri
    $pdo->exec("INSERT INTO AccountTypes (TypeID, TypeName, Currency) VALUES 
                (1, 'Vadesiz TL', 'TL'), 
                (2, 'Vadesiz Dolar', 'USD'), 
                (3, 'Vadesiz Euro', 'EUR'),
                (4, 'Vadesiz Altın', 'GR')");

    // Şubeler
    $pdo->exec("INSERT INTO Branches (BranchID, BranchName, City, Address, Phone) VALUES 
                (1, 'Merkez Şube', 'İstanbul', 'Levent', '0212 111 11 11'),
                (2, 'Kadıköy Şubesi', 'İstanbul', 'Kadıköy', '0216 222 22 22'),
                (3, 'Ankara Şubesi', 'Ankara', 'Kızılay', '0312 333 33 33')");

    // --- BOSS ---
    $pdo->exec("INSERT INTO Users (RoleID, Email, Password) VALUES (1, 'boss@banka.com', '$defaultPass')");
    $bossID = $pdo->lastInsertId();
    $pdo->exec("INSERT INTO Employees (UserID, BranchID, FirstName, LastName, Title) VALUES ($bossID, 1, 'Büyük', 'Patron', 'CEO')");
    echo "<p>👑 <b>BOSS:</b> boss@banka.com / 1234</p>";

    // --- PERSONEL VE MÜŞTERİ DÖNGÜSÜ ---
    $branches = [1, 2, 3];
    foreach ($branches as $brID) {
        
        // 3 Personel Ekle
        for ($i = 1; $i <= 3; $i++) {
            $email = "personel{$brID}_{$i}@banka.com";
            $pdo->prepare("INSERT INTO Users (RoleID, Email, Password) VALUES (2, ?, ?)")->execute([$email, $defaultPass]);
            $uID = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO Employees (UserID, BranchID, FirstName, LastName, Title) VALUES (?, ?, 'Personel', ?, 'Gişe Memuru')")->execute([$uID, $brID, "$brID-$i"]);
        }

        // 8 Müşteri Ekle
        for ($k = 1; $k <= 8; $k++) {
            $email = "musteri{$brID}_{$k}@banka.com";
            $tckn = "1" . str_pad($brID, 2, "0", STR_PAD_LEFT) . str_pad($k, 8, "0", STR_PAD_LEFT);
            
            $pdo->prepare("INSERT INTO Users (RoleID, Email, Password) VALUES (3, ?, ?)")->execute([$email, $defaultPass]);
            $uID = $pdo->lastInsertId();
            
            $pdo->prepare("INSERT INTO Customers (UserID, BranchID, FirstName, LastName, TCKN, Phone) VALUES (?, ?, 'Musteri', ?, ?, '5550000000')")->execute([$uID, $brID, "$brID-$k", $tckn]);
            $custID = $pdo->lastInsertId();

            // Müşteriye TL Hesabı Aç
            $iban = "TR" . rand(10000000, 99999999) . rand(10000000, 99999999);
            $bal = rand(5000, 100000);
            $pdo->prepare("INSERT INTO Accounts (CustomerID, TypeID, AccountNumber, Balance, Currency, BranchID) VALUES (?, 1, ?, ?, 'TL', ?)")->execute([$custID, $iban, $bal, $brID]);
        }
        echo "✅ Şube $brID: 3 Personel ve 8 Müşteri eklendi.<br>";
    }

    // Yabancı anahtar kontrolünü geri aç
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "<h1>🎉 KURULUM BAŞARIYLA TAMAMLANDI!</h1>";

} catch (PDOException $e) {
    echo "<h1 style='color:red'>HATA: " . $e->getMessage() . "</h1>";
    // Hata durumunda da checks'i açalım ki sistem kilitli kalmasın
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
}
?>