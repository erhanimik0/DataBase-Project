<?php
function getLiveCurrencies() {
    // Varsayılan (Yedek) Kurlar - Bağlantı koparsa bunlar görünür
    $currencies = [
        'USD' => [
            'name' => 'DOLAR',
            'alis' => '36.4000',
            'satis' => '36.8500'
        ],
        'EUR' => [
            'name' => 'EURO',
            'alis' => '39.1000',
            'satis' => '39.6000'
        ],
        'GA' => [ // Gram Altın (TCMB'de doğrudan yok, simüle ediyoruz)
            'name' => 'GRAM ALTIN',
            'alis' => '3.150.00',
            'satis' => '3.250.00'
        ],
        'CA' => [ // Çeyrek Altın
            'name' => 'ÇEYREK',
            'alis' => '5.100.00',
            'satis' => '5.300.00'
        ]
    ];

    try {
        // SSL Hatalarını Yoksaymak için Context Oluştur (Localhost sorunu için)
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );  

        // TCMB'den XML çekmeyi dene (Hata bastırma operatörü @ ile)
        $tcmb_url = "https://www.tcmb.gov.tr/kurlar/today.xml";
        $xml_content = @file_get_contents($tcmb_url, false, stream_context_create($arrContextOptions));

        if ($xml_content) {
            $xml = simplexml_load_string($xml_content);
            
            if ($xml) {
                // USD BUL
                $usd = $xml->xpath("Currency[@Kod='USD']");
                if (!empty($usd)) {
                    $currencies['USD']['alis'] = (string)$usd[0]->BanknoteBuying;
                    $currencies['USD']['satis'] = (string)$usd[0]->BanknoteSelling;
                }

                // EUR BUL
                $eur = $xml->xpath("Currency[@Kod='EUR']");
                if (!empty($eur)) {
                    $currencies['EUR']['alis'] = (string)$eur[0]->BanknoteBuying;
                    $currencies['EUR']['satis'] = (string)$eur[0]->BanknoteSelling;
                }
                
                // TCMB'de Altın kuru olmadığı için onu sabit bırakıyoruz veya ayrı API gerekir.
            }
        }
    } catch (Exception $e) {
        // Hata olursa hiçbir şey yapma, yukarıdaki varsayılan $currencies dizisini döndür.
    }

    return $currencies;
}
?>