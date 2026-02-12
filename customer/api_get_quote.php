<?php
// PHP hatalarının JSON çıktısını bozmasını engelle
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json; charset=utf-8');

// Veritabanı bağlantısı
$dbPath = '../includes/db.php';
if (file_exists($dbPath)) {
    require_once $dbPath;
} else {
    echo json_encode(['success' => false, 'error' => 'Veritabanı dosyası bulunamadı.']);
    exit;
}

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Oturum süresi dolmuş.']);
    exit;
}

// Para birimi yardımcısı
$currencyPath = '../includes/currency_helper.php';
if (file_exists($currencyPath)) {
    require_once $currencyPath;
}

// Eğer getLiveCurrencies fonksiyonu yoksa (dosya eksikse) sahte bir fonksiyon oluştur
if (!function_exists('getLiveCurrencies')) {
    function getLiveCurrencies() {
        return [
            'USD' => ['alis' => 34.00, 'satis' => 34.50],
            'EUR' => ['alis' => 37.00, 'satis' => 37.50],
            'GR'  => ['alis' => 2900,  'satis' => 2950]
        ];
    }
}

// POST verilerini al
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Veri alınamadı.']);
    exit;
}

$fromCurr = $input['fromCurrency'] ?? 'TL';
$toCurr = $input['toCurrency'] ?? 'TL';
$amount = (float)($input['amount'] ?? 0);

// Kur verilerini çek
$rates = getLiveCurrencies();

// Yardımcı Fonksiyonlar
function getRateToTL($curr, $rates) {
    if ($curr == 'TL' || $curr == 'TRY') return 1;
    // Banka dövizi müşteriden ALIR (Alış Kuru)
    return $rates[$curr]['alis'] ?? 0;
}

function getRateFromTL($curr, $rates) {
    if ($curr == 'TL' || $curr == 'TRY') return 1;
    // Banka dövizi müşteriye SATAR (Satış Kuru)
    return $rates[$curr]['satis'] ?? 0;
}

$rate = 0;
$convertedAmount = 0;

// HESAPLAMA MOTORU
if ($fromCurr == $toCurr) {
    $rate = 1;
    $convertedAmount = $amount;
} else {
    // 1. Gönderilen parayı TL'ye çevir
    $rateToTL = getRateToTL($fromCurr, $rates);
    $amountInTL = $amount * $rateToTL;
    
    // 2. TL'yi hedef para birimine çevir
    $rateFromTL = getRateFromTL($toCurr, $rates);
    
    if ($rateFromTL > 0) {
        $convertedAmount = $amountInTL / $rateFromTL;
        
        // Gösterimlik Çapraz Kur (1 Birim From = Kaç Birim To?)
        $rate = ($amount > 0) ? ($convertedAmount / $amount) : 0;
    } else {
        echo json_encode(['success' => false, 'error' => 'Kur bilgisi bulunamadı (' . $toCurr . ').']);
        exit;
    }
}

// SONUCU KİLİTLE (SESSION)
$expireTime = time() + 60; // 60 saniye geçerli

$_SESSION['transfer_quote'] = [
    'from' => $fromCurr,
    'to' => $toCurr,
    'rate' => $rate,
    'amount_sent' => $amount,
    'amount_received' => $convertedAmount,
    'expires_at' => $expireTime
];

// Başarılı yanıt döndür
echo json_encode([
    'success' => true,
    'rate' => $rate,
    'received_amount' => number_format($convertedAmount, 2, '.', ','),
    'expires_at' => $expireTime
]);
?>