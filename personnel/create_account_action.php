<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] == 'Personel') {
    
    $customerID = $_POST['customer_id'];
    $typeID = $_POST['type_id'];
    $balance = $_POST['balance'];

    try {
        // Prosedürü Çağır
        $stmt = $pdo->prepare("CALL sp_CreateAccount(?, ?, ?)");
        $stmt->execute([$customerID, $typeID, $balance]);
        
        // Yeni oluşan hesap numarasını al
        $result = $stmt->fetch();
        $newAccountNo = $result['NewAccountNumber'];

        header("Location: create_account.php?success=1&account_no=" . $newAccountNo);
        exit;

    } catch (PDOException $e) {
        die("Hata oluştu: " . $e->getMessage());
    }

} else {
    header("Location: ../index.php");
}