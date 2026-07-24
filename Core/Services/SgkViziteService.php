<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Helper\Security;
use Models\ArsivRaporModel;
use Models\RaporModel;

class SgkViziteService
{
    /**
     * ARSIV=1 raporların raporAramaTarihile sonuçlarından kaybolup kaybolmadığını
     * gözlemlemek için otomatik raporOkunduKapat işlemi geçici olarak kapalıdır.
     */
    private const ARSIV_RAPORLARINI_OTOMATIK_KAPAT = false;

    private const TOKEN_TTL_SECONDS = 1740;

    private string $serviceUrl = 'https://uyg.sgk.gov.tr/Ws_Vizite/services/ViziteGonder';
    private $kullaniciAdi;
    private $isyeriKodu;
    private $wsSifre;
    private $wsToken;
    private $tokenExpiresAt;
    private $activeUserKey;

    /**
     * Sınıfı başlatır.
     * Parametreler verilirse onları kullanır, verilmezse session'dan okumaya çalışır.
     *
     * @param string|null $kullaniciAdi Opsiyonel. Verilmezse session'dan alınır.
     * @param string|null $isyeriKodu   Opsiyonel. Verilmezse session'dan alınır.
     * @param string|null $wsSifre      Opsiyonel. Verilmezse session'dan alınır (şifrelenmemiş hali).
     * @throws Exception Gerekli bilgiler bulunamazsa hata fırlatır.
     */
    public function __construct(string $kullaniciAdi = null, string $isyeriKodu = null, string $wsSifre = null)
    {
        if (php_sapi_name() !== 'cli') { // 'cli' = Command Line Interface (Komut Satırı)
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
        }

        //Eğer dışarıdan parametre gelirse onları kullan değilse session'dan al
        
       if($kullaniciAdi === null) {
            $kullaniciAdi = $_SESSION['kullaniciAdi'] ?? null;
        }

        if($isyeriKodu === null) {
            $isyeriKodu = $_SESSION['isyeriKodu'] ?? null;
        }

        if($wsSifre === null) {
            $wsSifre = Security::decrypt($_SESSION['wsSifre'] ?? null);
        }

 
        // 1. Parametreleri ata (boşlukları temizle ve null koruması):
        $this->kullaniciAdi         = trim($kullaniciAdi ?? '');
        $this->isyeriKodu           = trim($isyeriKodu ?? '');
        $this->wsSifre              = trim($wsSifre ?? '');

        $this->wsToken          = null;
        $this->tokenExpiresAt   = null;
        $this->activeUserKey    = null;

        // Son kontrol: Tüm kontrollerden sonra bile bilgiler eksikse hata ver.
        if (!$this->kullaniciAdi || !$this->isyeriKodu || !$this->wsSifre) {
            throw new Exception("SGK Servisi için gerekli kimlik bilgileri eksik veya bulunamadı.");
        }

        $currentUserKey = $this->currentUserKey();
        $this->migrateLegacyTokenCache($currentUserKey);
        $this->loadTokenFromCache($currentUserKey);
    }

    /**
     * Tüm SOAP isteklerini yapan merkezi fonksiyon.
     * file_get_contents + stream_context kullanır.
     * SGK sunucusu büyük cevaplarda bağlantıyı protokole uygun kapatmadığı için
     * cURL hata 56 verir. file_get_contents buna tolerans gösterir.
     */
    private function sendRequest(string $methodName, array $params, bool $tokenRetryAllowed = true): object
    {
        if (!preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $methodName)) {
            throw new InvalidArgumentException('Geçersiz SGK metot adı.');
        }

        $paramXml = '';
        foreach ($params as $key => $value) {
            if (!preg_match('/^[A-Za-z][A-Za-z0-9]*$/', (string)$key)) {
                throw new InvalidArgumentException('Geçersiz SGK parametre adı.');
            }
            $paramXml .= "<{$key}>" . htmlspecialchars((string)$value, ENT_XML1, 'UTF-8') . "</{$key}>";
        }

        $xml_request = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://service.com">
<SOAP-ENV:Body>
    <ns1:{$methodName}>
        {$paramXml}
    </ns1:{$methodName}>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;

        $streamContext = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: text/xml; charset=utf-8\r\n" .
                            "SOAPAction: \"\"\r\n" .
                            "Connection: close\r\n",
                'content' => $xml_request,
                'timeout' => 180,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
            ]
        ]);

        $responseXml = @file_get_contents($this->serviceUrl, false, $streamContext);
        $httpStatus = $this->extractHttpStatus($http_response_header ?? []);

        if ($responseXml === false) {
            $lastErr = error_get_last();
            $errMsg = $lastErr['message'] ?? 'Bilinmeyen bağlantı hatası';
            throw new Exception("Bağlantı Hatası: {$errMsg} (Method: {$methodName})");
        }

        if (empty($responseXml) || strpos(ltrim($responseXml), '<') !== 0) {
            throw new Exception("SGK sunucusundan geçerli bir XML cevabı alınamadı (Method: {$methodName}).");
        }

        $previousLibxmlSetting = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($responseXml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);

        if ($xml === false) {
            libxml_clear_errors();
            libxml_use_internal_errors($previousLibxmlSetting);
            throw new Exception("Sunucudan gelen XML parse edilemedi.");
        }

        $faultNodes = $xml->xpath('//*[local-name()="Fault"]');
        if (!empty($faultNodes)) {
            $faultString = $faultNodes[0]->xpath('./*[local-name()="faultstring"]');
            $message = !empty($faultString) ? trim((string)$faultString[0]) : 'Bilinmeyen SOAP hatası';
            libxml_clear_errors();
            libxml_use_internal_errors($previousLibxmlSetting);
            throw new Exception("SGK SOAP hatası ({$methodName}): {$message}");
        }

        if ($httpStatus !== null && ($httpStatus < 200 || $httpStatus >= 300)) {
            libxml_clear_errors();
            libxml_use_internal_errors($previousLibxmlSetting);
            throw new Exception("SGK HTTP hatası {$httpStatus} (Method: {$methodName}).");
        }

        $returnName = $methodName . 'Return';
        $returnNodes = $xml->xpath('//*[local-name()="' . $returnName . '"]');
        if (empty($returnNodes)) {
            libxml_clear_errors();
            libxml_use_internal_errors($previousLibxmlSetting);
            throw new Exception("SGK cevabında {$returnName} alanı bulunamadı.");
        }

        $returnNode = $returnNodes[0];
        $resultCodeNodes = $returnNode->xpath('./*[local-name()="sonucKod"]');
        $resultCode = !empty($resultCodeNodes) ? trim((string)$resultCodeNodes[0]) : null;

        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlSetting);

        if (
            $tokenRetryAllowed
            && $methodName !== 'wsLogin'
            && array_key_exists('wsToken', $params)
            && in_array($resultCode, ['104', '106'], true)
        ) {
            $this->invalidateTokenCache();
            $params['wsToken'] = $this->getValidToken();
            return $this->sendRequest($methodName, $params, false);
        }

        $response = new stdClass();
        $response->{$returnName} = $returnNode;
        return $response;
    }

    private function getValidToken(): string
    {
        $currentUserKey = $this->currentUserKey();

        if ($this->activeUserKey !== $currentUserKey) {
            $this->wsToken = null;
            $this->tokenExpiresAt = 0;
        }

        if ($this->hasValidToken()) {
            return $this->wsToken;
        }

        $cacheFile = $this->tokenCacheFile($currentUserKey);
        $lockHandle = @fopen($cacheFile . '.lock', 'c');
        if ($lockHandle === false) {
            throw new Exception('SGK token kilit dosyası oluşturulamadı.');
        }
        @chmod($cacheFile . '.lock', 0600);

        try {
            if (!flock($lockHandle, LOCK_EX)) {
                throw new Exception('SGK token kilidi alınamadı.');
            }

            // Başka bir süreç kilidi beklerken token almış olabilir.
            $this->loadTokenFromCache($currentUserKey);
            if ($this->hasValidToken()) {
                return $this->wsToken;
            }

            $params = [
                'kullaniciAdi' => $this->kullaniciAdi,
                'isyeriKodu' => $this->isyeriKodu,
                'isyeriSifresi' => $this->wsSifre,
            ];

            $response = $this->sendRequest('wsLogin', $params, false);
            if (isset($response->wsLoginReturn->sonucKod) && (string)$response->wsLoginReturn->sonucKod === '0') {
                $this->wsToken = (string)$response->wsLoginReturn->wsToken;
                $this->tokenExpiresAt = time() + self::TOKEN_TTL_SECONDS;
                $this->activeUserKey = $currentUserKey;
                $this->writeTokenCache($cacheFile, [
                    'wsToken' => $this->wsToken,
                    'tokenExpiresAt' => $this->tokenExpiresAt,
                ]);
            } else {
                $hataMesaji = isset($response->wsLoginReturn->sonucAciklama)
                    ? (string)$response->wsLoginReturn->sonucAciklama
                    : 'Bilinmeyen Hata';

                if (mb_strpos($hataMesaji, 'FARKLI IP DEN ALINMIŞ GEÇERLİ GUID MEVCUT') !== false) {
                    $hataMesaji = "Oturum Hatası (SGK): Bu işveren için başka bir IP/cihaz üzerinden alınmış aktif bir seans mevcuttur. \n\nÇözüm: Lütfen 10-15 dakika bekleyerek SGK sunucusundaki eski oturumun otomatik sonlanmasını sağlayın veya internet bağlantınızı kesip tekrar bağlanarak (modem kapat-aç vb.) yeni bir IP almayı deneyin.";
                }
                throw new Exception("Login başarısız: " . $hataMesaji);
            }
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }

        return $this->wsToken;
    }

    private function currentUserKey(): string
    {
        return $this->kullaniciAdi . '|' . $this->isyeriKodu;
    }

    private function hasValidToken(): bool
    {
        return is_string($this->wsToken)
            && $this->wsToken !== ''
            && is_int($this->tokenExpiresAt)
            && time() < $this->tokenExpiresAt;
    }

    private function tokenCacheDirectory(): string
    {
        $configuredDirectory = getenv('SGK_TOKEN_CACHE_DIR');
        $directory = $configuredDirectory !== false && trim($configuredDirectory) !== ''
            ? rtrim($configuredDirectory, DIRECTORY_SEPARATOR)
            : rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . 'sgk-vizite-tokens-'
                . substr(hash('sha256', dirname(__DIR__, 2)), 0, 12);

        // mkdir uyarısını doğrudan HTML çıktısına sızdırma; aşağıda kontrollü
        // bir exception ile hem oluşturma hem de yazılabilirlik durumunu bildir.
        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new Exception('Güvenli SGK token dizini oluşturulamadı.');
        }
        @chmod($directory, 0700);

        if (!is_dir($directory) || !is_writable($directory)) {
            throw new Exception(
                'SGK token dizinine yazılamıyor. SGK_TOKEN_CACHE_DIR ayarını PHP kullanıcısının yazabildiği güvenli bir dizine yönlendirin.'
            );
        }

        return $directory;
    }

    private function tokenCacheFile(string $currentUserKey): string
    {
        return $this->tokenCacheDirectory()
            . DIRECTORY_SEPARATOR
            . 'sgk_token_' . hash('sha256', $currentUserKey) . '.json';
    }

    private function loadTokenFromCache(string $currentUserKey): void
    {
        $cacheFile = $this->tokenCacheFile($currentUserKey);
        if (!is_file($cacheFile) || !is_readable($cacheFile)) {
            return;
        }

        $cacheContents = @file_get_contents($cacheFile);
        if ($cacheContents === false) {
            return;
        }

        $cacheData = json_decode($cacheContents, true);
        if (
            is_array($cacheData)
            && isset($cacheData['wsToken'], $cacheData['tokenExpiresAt'])
            && is_string($cacheData['wsToken'])
            && $cacheData['wsToken'] !== ''
            && is_numeric($cacheData['tokenExpiresAt'])
            && time() < (int)$cacheData['tokenExpiresAt']
        ) {
            $this->wsToken = $cacheData['wsToken'];
            $this->tokenExpiresAt = (int)$cacheData['tokenExpiresAt'];
            $this->activeUserKey = $currentUserKey;
        }
    }

    private function writeTokenCache(string $cacheFile, array $cacheData): void
    {
        $json = json_encode($cacheData, JSON_THROW_ON_ERROR);
        $temporaryFile = $cacheFile . '.tmp.' . bin2hex(random_bytes(6));
        if (@file_put_contents($temporaryFile, $json, LOCK_EX) === false) {
            throw new Exception('SGK token önbelleği yazılamadı.');
        }
        @chmod($temporaryFile, 0600);

        if (!@rename($temporaryFile, $cacheFile)) {
            @unlink($temporaryFile);
            throw new Exception('SGK token önbelleği güvenli biçimde taşınamadı.');
        }
        @chmod($cacheFile, 0600);
    }

    private function invalidateTokenCache(): void
    {
        $cacheFile = $this->tokenCacheFile($this->currentUserKey());
        if (is_file($cacheFile)) {
            @unlink($cacheFile);
        }
        $this->wsToken = null;
        $this->tokenExpiresAt = 0;
        $this->activeUserKey = null;
    }

    private function migrateLegacyTokenCache(string $currentUserKey): void
    {
        $secureCacheFile = $this->tokenCacheFile($currentUserKey);
        if (is_file($secureCacheFile)) {
            return;
        }

        $legacyCacheFile = __DIR__ . '/../../cache/sgk_token_' . md5($currentUserKey) . '.json';
        if (!is_file($legacyCacheFile) || !is_readable($legacyCacheFile)) {
            return;
        }

        $cacheData = json_decode((string)file_get_contents($legacyCacheFile), true);
        if (
            is_array($cacheData)
            && isset($cacheData['wsToken'], $cacheData['tokenExpiresAt'])
            && time() < (int)$cacheData['tokenExpiresAt']
        ) {
            $this->writeTokenCache($secureCacheFile, $cacheData);
        }
    }

    private function extractHttpStatus(array $headers): ?int
    {
        foreach (array_reverse($headers) as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/i', $header, $matches)) {
                return (int)$matches[1];
            }
        }
        return null;
    }

    /**
     * Verilen SGK bilgilerinin geçerli olup olmadığını wsLogin ile test eder.
     * Sadece doğrulama amaçlıdır, token'ı saklamaz.
     * @return object Dönen cevap objesi (sonucKod ve sonucAciklama içerir).
     * @throws Exception
     */
    public function bilgileriDogrula(
        string $kullaniciAdi,
        string $isyeriKodu,
        string $isyeriSifresi
    ) {

        //Dışarıdan parametre gelirse onları kullan
        $this->kullaniciAdi = $kullaniciAdi;
        $this->isyeriKodu = $isyeriKodu;
        $this->wsSifre = $isyeriSifresi;

        $params = [
            'kullaniciAdi' => $this->kullaniciAdi,
            'isyeriKodu' => $this->isyeriKodu,
            'isyeriSifresi' => $this->wsSifre, // WSDL'ye göre doğru parametre adı
        ];

        $response = $this->sendRequest('wsLogin', $params);

        // wsLoginReturn objesini doğrudan döndür
        return $response->wsLoginReturn;
    }

    /**
     * METOT 24: OnayliRaporlarTarihile (MANUEL DÖNÜŞÜM VERSİYONU)
     * Belirtilen tarih aralığında onaylanmış raporları getirir.
     * @param DateTime $tarih1 Başlangıç tarihi
     * @param DateTime $tarih2 Bitiş tarihi
     * @return array Her zaman onaylanmış raporların bulunduğu saf bir PHP dizisi döndürür.
     * @throws Exception
     */
    public function onayliRaporlariGetir(DateTime $tarih1, DateTime $tarih2)
    {
        $params = [
            'kullaniciAdi' => $this->kullaniciAdi,
            'isyeriKodu' => $this->isyeriKodu,
            'wsToken' => $this->getValidToken(),
            'tarih1' => $tarih1->format('d.m.Y'),
            'tarih2' => $tarih2->format('d.m.Y'),
        ];

        $response = $this->sendRequest('onayliRaporlarTarihile', $params);
        $returnNode = $response->onayliRaporlarTarihileReturn;
        $sonucDizisi = []; // Döndüreceğimiz temiz dizi

        if (isset($returnNode->sonucKod) && $returnNode->sonucKod == '0') {

            // Onaylı raporların bulunduğu ana objeye erişiyoruz.
            if (isset($returnNode->onayliRaporlarTarihleBeanArray->OnayliRaporlarTarihleBean)) {

                $raporlarObject = $returnNode->onayliRaporlarTarihleBeanArray->OnayliRaporlarTarihleBean;

                // =============================================================
                // ===             KESİN ÇÖZÜM: MANUEL DÖNGÜ                 ===
                // =============================================================
                // Gelen SimpleXMLElement'in üzerinde doğrudan foreach ile dönüyoruz.
                // Eğer tek bir rapor varsa, döngü bir kez çalışır.
                // Eğer birden çok rapor varsa, döngü her rapor için çalışır.
                foreach ($raporlarObject as $rapor) {

                    // 1. ADIM: Her bir rapor objesini standart bir PHP dizisine çevirip
                    // önce bir değişkene atıyoruz.
                    $yeniRaporDizisi = [
                        'TCKIMLIKNO' => (string)$rapor->TCKIMLIKNO,
                        'AD' => (string)$rapor->AD,
                        'SOYAD' => (string)$rapor->SOYAD,
                        'SIGORTALIADSOYAD' => (string)$rapor->AD . ' ' . (string)$rapor->SOYAD,
                        'VAKAADI' => (string)$rapor->VAKAADI,
                        'POLIKLINIKTAR' => (string)$rapor->POLIKLINIKTAR,
                        'ISBASKONTTAR' => (string)$rapor->ISBASKONTTAR,
                        // Diğer tüm gerekli alanları buraya ekleyin...
                        'MEDULARAPORID' => (string)$rapor->MEDULARAPORID,
                        'RAPORTAKIPNO' => (string)$rapor->RAPORTAKIPNO,
                        'RAPORSIRANO' => (string)$rapor->RAPORSIRANO,
                        'TESISKODU' => (string)$rapor->TESISKODU,
                        'BRANSKODU' => (string)$rapor->BRANSKODU,
                        'RAPORDURUMADI' => (string)$rapor->RAPORDURUMADI,
                        'YATRAPBASTAR' => (string)$rapor->YATRAPBASTAR,
                        'YATRAPBITTAR' => (string)$rapor->YATRAPBITTAR,
                        'ABASTAR' => (string)$rapor->ABASTAR,
                        'ABITTAR' => (string)$rapor->ABITTAR,
                        'VAKA' => (string)$rapor->VAKA,
                    ];

                    $sonucDizisi[] = $yeniRaporDizisi;
                }
                // =============================================================
            }
        } else if (isset($returnNode->sonucKod) && $returnNode->sonucKod == '503') {
            // Rapor bulunamadıysa boş dizi döner, bu bir hata değildir.
        } else {
            $hataMesaji = isset($returnNode->sonucAciklama)
                ? (string)$returnNode->sonucAciklama
                : 'Bilinmeyen bir hata oluştu.';
            throw new Exception("Onaylı raporlar getirilemedi: " . $hataMesaji);
        }

        return $sonucDizisi; // Her zaman doğru yapıda ve sayıda eleman içeren PHP dizisini döndür
    }

    // 2. Detayları almak için bu fonksiyon (EKRANTARIHI'ni getirecek olan)
    public function onayliRaporDetayGetir(string $medulaRaporId)
    {
        $params = [
            'kullaniciAdi' => $this->kullaniciAdi,
            'isyeriKodu' => $this->isyeriKodu,
            'wsToken' => $this->getValidToken(),
            'medulaRaporId' => $medulaRaporId,
        ];

        $response = $this->sendRequest('onayliRaporlarDetay', $params);
        $returnNode = $response->onayliRaporlarDetayReturn;

        if (isset($returnNode->sonucKod) && $returnNode->sonucKod == '0') {
            // Çıktınıza göre, detay verisi 'tarihSorguBean' içinde geliyor.
            if (isset($returnNode->tarihSorguBean->TarihSorguBean)) {
                $detayObject = $returnNode->tarihSorguBean->TarihSorguBean;
                // Gelen tek bir nesneyi standart PHP dizisine çeviriyoruz
                return json_decode(json_encode($detayObject), true);
            }
            return null; // Başarılı ama detay verisi yok
        } else {
            throw new Exception("Rapor detayı getirilemedi (ID: {$medulaRaporId})");
        }
    }

    /**
     * Geriye dönük uyumluluk için detay metodu takma adı.
     * @deprecated onayliRaporDetayGetir() kullanın.
     */
    public function raporDetayGetir(string $medulaRaporId)
    {
        return $this->onayliRaporDetayGetir($medulaRaporId);
    }

    /**
     * METOT 2: RaporAramaTarihile (Sunucu Filtresiz Versiyon)
     * Belirtilen tarihten önceki TÜM vaka türlerindeki 100 raporu getirir.
     * @param DateTime $tarih Sorgulanacak tarih (bu tarihten öncekiler listelenir).
     * @return array Her zaman raporların bulunduğu bir dizi döndürür.
     * @throws Exception
     */
    public function raporlariGetir(DateTime $tarih, $arsiv = true)

    {
        $params = [
            'kullaniciAdi' => $this->kullaniciAdi,
            'isyeriKodu' => $this->isyeriKodu,
            'wsToken' => $this->getValidToken(),
            'tarih' => $tarih->format('d.m.Y'),
            // Vaka parametresini buradan kaldırdık!
        ];

        $response = $this->sendRequest('raporAramaTarihile', $params);
        $returnNode = $response->raporAramaTarihileReturn;
        $sonucDizisi = [];

        if (isset($returnNode->sonucKod) && $returnNode->sonucKod == '0') {
            if (isset($returnNode->raporAramaTarihleBeanArray->RaporAramaTarihleBean)) {
                $raporlarObject = $returnNode->raporAramaTarihleBeanArray->RaporAramaTarihleBean;

                foreach ($raporlarObject as $rapor) {
                    if ($arsiv == false && (string)$rapor->ARSIV == '1') {
                        continue;
                    }
                    // Cevapta gelen 'VAKA' alanı vaka kodunu (1, 2, 3, 4) içerir.
                    $sonucDizisi[] = [
                        'TCKIMLIKNO' => (string)$rapor->TCKIMLIKNO,
                        'AD' => (string)$rapor->AD,
                        'SOYAD' => (string)$rapor->SOYAD,
                        'VAKA' => (string)$rapor->VAKA, // Vaka Kodunu alıyoruz
                        'VAKAADI' => (string)$rapor->VAKAADI, // Vaka Adını alıyoruz
                        'POLIKLINIKTAR' => (string)$rapor->POLIKLINIKTAR,
                        'ISBASKONTTAR' => (string)$rapor->ISBASKONTTAR,
                        'ABITTAR' => (string)$rapor->ABITTAR,
                        'MEDULARAPORID' => (string)$rapor->MEDULARAPORID,
                        'RAPORTAKIPNO' => (string)$rapor->RAPORTAKIPNO,
                        'RAPORSIRANO' => (string)$rapor->RAPORSIRANO,
                        'ARSIV' => (string)$rapor->ARSIV, // <-- BU SATIRIN OLDUĞUNDAN EMİN OLUN
                        'SIGORTALIADSOYAD' => (string)$rapor->AD . ' ' . (string)$rapor->SOYAD,
                        'RAPORDURUMU' => (string)$rapor->RAPORDURUMU,
                        'RAPORDURUMADI' => (string)$rapor->RAPORDURUMADI,
                        'ONAYLI' => (string)$rapor->ONAYLI,
                        'ONAYDURUMU' => (string)$rapor->ONAYDURUMU,
                        'ONAY_TARIHI' => (string)$rapor->ONAY_TARIHI,
                        'EKRANTARIHI' => (string)$rapor->EKRANTARIHI,
                        'TESISKODU' => (string)$rapor->TESISKODU,
                        'BRANSKODU' => (string)$rapor->BRANSKODU,
                        'TESISADI' => (string)$rapor->TESISADI,
                        'BRANSADI' => (string)$rapor->BRANSADI,
                    ];
                }
            };
        } else if (isset($returnNode->sonucKod) && $returnNode->sonucKod == '503') {
            // Rapor bulunamadı.
        } else {
            $hataMesaji = isset($returnNode->sonucAciklama) ? (string)$returnNode->sonucAciklama : 'Bilinmeyen Hata';
            throw new Exception("Raporlar getirilemedi: " . $hataMesaji);
        }

        return $sonucDizisi;
    }


    /**
     * SADECE RaporAramaTarihleBean Verisini Döndürür.
     * Bu metot, ham SimpleXMLElement nesnesini veya dizisini döndürür.
     * Veriyi işlemek ve diziye çevirmek, bu metodu çağıran koda aittir.
     * 
     * @param DateTime $tarih Sorgulanacak tarih (bu tarihten öncekiler listelenir).
     * @return SimpleXMLElement|array|null Raporları içeren nesne/dizi veya kayıt yoksa null.
     * @throws Exception
     */
    public function raporAramaTarihleGetir(DateTime $tarih)
    {
        $params = [
            'kullaniciAdi' => $this->kullaniciAdi,
            'isyeriKodu' => $this->isyeriKodu,
            'wsToken' => $this->getValidToken(),
            'tarih' => $tarih->format('d.m.Y'),
        ];

        $response = $this->sendRequest('raporAramaTarihile', $params);
        $returnNode = $response->raporAramaTarihileReturn;

        if (isset($returnNode->sonucKod) && $returnNode->sonucKod == '0') {

            // Raporların bulunduğu yola erişip, ham nesneyi doğrudan döndürüyoruz.
            if (isset($returnNode->raporAramaTarihleBeanArray->RaporAramaTarihleBean)) {
                return $returnNode->raporAramaTarihleBeanArray->RaporAramaTarihleBean;
            } else {
                // Sonuç başarılı ama içinde rapor yoksa null döndür.
                return null;
            }
        } else if (isset($returnNode->sonucKod) && $returnNode->sonucKod == '503') { // 503 = Kayıt yok
            return null; // Rapor bulunamadıysa null döndür.
        } else {
            $hataMesaji = isset($returnNode->sonucAciklama)
                ? (string)$returnNode->sonucAciklama
                : 'Bilinmeyen bir hata oluştu.';
            throw new Exception("Raporlar getirilemedi: " . $hataMesaji);
        }
    }



    /**
     * METOT 25: RaporOkunduKapat
     * Görülen bir raporu "okundu" olarak işaretler ve bir sonraki sorguda gelmesini engeller.
     * @param string $medulaRaporId Kapatılacak raporun ID'si
     * @return object Dönen cevap objesi
     * @throws Exception
     */
    public function raporuKapat($medulaRaporId)
    {
        $params = [
            'kullaniciAdi' => $this->kullaniciAdi,
            'isyeriKodu' => $this->isyeriKodu,
            'wsToken' => $this->getValidToken(),
            'medulaRaporId' => $medulaRaporId,
        ];

        $response = $this->sendRequest('raporOkunduKapat', $params);

        // Cevabı doğrudan döndürelim, işleme mantığı ana betikte olsun.
        return $response->raporOkunduKapatReturn;
    }

    /**
     * raporlariGetir() ile gelen ham rapor dizisindeki ARSIV=1 (sonuçlanmış/arşivlenmiş)
     * raporları SGK üzerinde raporuKapat() ile kapatır. Bu sayede raporAramaTarihile'in
     * sabit 100 kayıtlık penceresi eski kapanmış raporlarla dolu kalmaz ve gerçek
     * onay bekleyen raporlar sorgularda görünür olur.
     *
     * @param array $raporlar raporlariGetir()'dan dönen dizi
     * @param int $limit Tek seferde en fazla kaç rapor kapatılacağı (sunucuyu yormamak için)
     * @return array ['kapatilan' => int, 'hatalar' => array]
     */
    public function arsivlenmisRaporlariKapat(array $raporlar, int $limit = 20): array
    {
        $kapatilan = 0;
        $hatalar = [];
        $arsivRaporModel = new ArsivRaporModel();

        foreach ($raporlar as $rapor) {
            if ($kapatilan + count($hatalar) >= $limit) {
                break;
            }
            if (($rapor['ARSIV'] ?? '0') != '1') {
                continue;
            }

            try {
                // SGK kuyruğundan kaldırmadan önce kalıcı arşive al. Yerel kayıt
                // başarısız olursa veri kaybını önlemek için SGK kaydını kapatma.
                $arsivRaporModel->arsivle(
                    $rapor,
                    $this->isyeriKodu,
                    isset($_SESSION['isyeri_id']) ? (int)$_SESSION['isyeri_id'] : null,
                    isset($_SESSION['kullanici_id']) ? (int)$_SESSION['kullanici_id'] : null
                );

                $sonuc = $this->raporuKapat($rapor['MEDULARAPORID']);
                $kod = (string)($sonuc->sonucKod ?? '');
                if ($kod === '0' || $kod === '600') {
                    $kapatilan++;
                    $arsivRaporModel->kapatildiOlarakIsaretle(
                        $this->isyeriKodu,
                        (string)$rapor['MEDULARAPORID']
                    );
                } else {
                    $hatalar[] = [
                        'medulaRaporId' => $rapor['MEDULARAPORID'],
                        'raporTakipNo' => $rapor['RAPORTAKIPNO'] ?? null,
                        'mesaj' => (string)($sonuc->sonucAciklama ?? 'Bilinmeyen hata'),
                    ];
                }
            } catch (Exception $e) {
                $hatalar[] = [
                    'medulaRaporId' => $rapor['MEDULARAPORID'],
                    'raporTakipNo' => $rapor['RAPORTAKIPNO'] ?? null,
                    'mesaj' => $e->getMessage(),
                ];
            }
        }

        return ['kapatilan' => $kapatilan, 'hatalar' => $hatalar];
    }

    /**
     * Onay Bekleyen Raporlar panel sayfaları ve otomatik onay cron'unun ortak kullandığı
     * merkezi metot: SGK'dan bekleyen raporları çeker ve sadece gerçekten işlem
     * gerektiren raporları döndürür. ARSIV=1 kayıtların SGK'da otomatik kapatılması,
     * arşiv görünürlüğünü test etmek amacıyla özellik bayrağı üzerinden geçici olarak
     * devre dışıdır.
     *
     * Bu filtreleme mantığı tek bir yerde tutulur; ileride bir kural değişirse
     * (ör. yeni bir "zaten onaylı" göstergesi eklenirse) sadece burası güncellenir.
     *
     * @param DateTime $tarih
     * @param RaporModel $raporModel
     * @return array ['raporlar' => array, 'arsiv_kapatma' => ['kapatilan' => int, 'hatalar' => array]]
     */
    public function bekleyenRaporlariGetir(DateTime $tarih, RaporModel $raporModel): array
    {
        $tumRaporlar = $this->raporlariGetir($tarih);
        $arsivKapatSonucu = [
            'kapatilan' => 0,
            'hatalar' => [],
            'devre_disi' => !self::ARSIV_RAPORLARINI_OTOMATIK_KAPAT,
        ];

        if (self::ARSIV_RAPORLARINI_OTOMATIK_KAPAT) {
            $arsivKapatSonucu = $this->arsivlenmisRaporlariKapat($tumRaporlar);
            $arsivKapatSonucu['devre_disi'] = false;
        }

        $bekleyenRaporlar = [];
        $mevcutMedulaRaporIds = array_fill_keys(
            $raporModel->findExistingMedulaRaporIds(array_column($tumRaporlar, 'MEDULARAPORID')),
            true
        );

        foreach ($tumRaporlar as $rapor) {
            // Arşivlenmiş (kısa süreli, SGK tarafından otomatik sonuçlandırılmış) raporları atla
            if (($rapor['ARSIV'] ?? '0') == 1) {
                continue;
            }

            // "ONAY BEKLİYOR" gibi durumları yanlışlıkla elememek için metinde ONAY
            // aramak yerine yalnızca kesin onay durumlarını kabul et.
            $raporDurumAdi = mb_strtoupper(trim((string)($rapor['RAPORDURUMADI'] ?? '')), 'UTF-8');
            $onayli = strtoupper(trim((string)($rapor['ONAYLI'] ?? '')));
            $onayDurumu = strtoupper(trim((string)($rapor['ONAYDURUMU'] ?? '')));
            if (
                in_array($raporDurumAdi, ['ONAYLI', 'ONAYLANDI'], true)
                || in_array($onayli, ['1', 'E'], true)
                || in_array($onayDurumu, ['1', 'E'], true)
            ) {
                continue;
            }

            // Takip numarasi birden fazla rapor sirasinda ortak olabilir. Yalnizca SGK'nin
            // tekil Medula rapor kimligi daha once kaydedildiyse bu satiri atla.
            $medulaRaporId = trim((string)($rapor['MEDULARAPORID'] ?? ''));
            if ($medulaRaporId !== '' && isset($mevcutMedulaRaporIds[$medulaRaporId])) {
                continue;
            }

            $bekleyenRaporlar[] = $rapor;
        }

        return [
            'raporlar' => $bekleyenRaporlar,
            'arsiv_kapatma' => $arsivKapatSonucu,
        ];
    }

    /**
     * METOT 10: OnaylIptal
     * Daha önce onaylanmış bir raporun onayını (çalışmazlık bildirimini) iptal eder.
     * @param string $medulaRaporId Onayı iptal edilecek raporun Medula Rapor ID'si.
     * @param string|null $bildirimId Onaylama işleminden dönen Bildirim ID'si (genellikle opsiyonel).
     * @return object Dönen cevap objesi.
     * @throws Exception
     */
    public function raporOnayIptalEt(string $medulaRaporId, ?string $bildirimId = null)
    {
        $params = [
            'kullaniciAdi' => $this->kullaniciAdi,
            'isyeriKodu' => $this->isyeriKodu,
            'wsToken' => $this->getValidToken(),
            'medulaRaporId' => $medulaRaporId,
            'bildirimId' => $bildirimId ?? '' // Eğer null ise boş string gönder
        ];

        $response = $this->sendRequest('onaylIptal', $params);

        return $response->onaylIptalReturn;
    }

    public function iletisimBilgileriniGetir()
    {

        $params = [
            'kullaniciAdi' => $this->kullaniciAdi,
            'isyeriKodu' => $this->isyeriKodu,
            'wsToken' => $this->getValidToken(),
        ];

        $response = $this->sendRequest('isverenIletisimBilgileriGoruntu', $params);

        $returnNode = $response->isverenIletisimBilgileriGoruntuReturn;

        if (isset($returnNode->sonucKod) && $returnNode->sonucKod == '0') {
            //echo "İletişim bilgileri başarıyla alındı.\n";

            // =============================================================
            // ===                NİHAİ DÜZELTME BURADA                  ===
            // =============================================================
            // Gelen cevaptaki iki katmanlı yola erişiyoruz.
            // Önce 'isverenIletisimBilgiBean' (küçük i) var mı diye bakıyoruz.
            if (isset($returnNode->isverenIletisimBilgiBean)) {
                // Sonra onun içinde 'IsverenIletisimBilgiBean' (büyük I) var mı diye bakıyoruz.
                $bilgiObjesi = $returnNode->isverenIletisimBilgiBean->IsverenIletisimBilgiBean;

                // Bu objenin tek bir kayıt mı yoksa dizi mi olduğunu kontrol edelim.
                // Genellikle tek kayıt için dizi yapısı [0] kullanılır.
                if (isset($bilgiObjesi[0])) {
                    return $bilgiObjesi[0];
                } else {
                    return $bilgiObjesi;
                }
            } else {
                // Sonuç başarılı ama içinde bilgi yoksa null döndür.
                return null;
            }
            // =============================================================

        } else if (isset($returnNode->sonucKod) && $returnNode->sonucKod == '501') {
            return null;
        } else {
            $hataMesaji = isset($returnNode->sonucAciklama)
                ? (string)$returnNode->sonucAciklama
                : 'Bilinmeyen bir hata oluştu.';
            throw new Exception("İletişim bilgileri alınamadı: " . $hataMesaji);
        }
    }

    /**
     * METOT 6: IsverenIletisimBilgileri
     * İşverenin e-posta ve telefon bilgilerini günceller.
     * @param string $eposta Yeni e-posta adresi
     * @param string $cepTel Yeni cep telefonu numarası (10 haneli, başında 0 olmadan)
     * @return object Dönen cevap objesi
     * @throws Exception
     */
    public function iletisimBilgileriniGuncelle(string $eposta, string $cepTel)
    {
        $params = [
            'kullaniciAdi' => $this->kullaniciAdi,
            'isyeriKodu' => $this->isyeriKodu,
            'wsToken' => $this->getValidToken(),
            'eposta' => $eposta,
            'cepTel' => $cepTel
        ];

        $response = $this->sendRequest('isverenIletisimBilgileri', $params);

        return $response->isverenIletisimBilgileriReturn;
    }


    /**
     * METOT 21: PersonelimDegildir
     * Seçilen raporun mevcut işverenle ilişkili olmadığını bildirir.
     * @param string $medulaRaporId
     * @param string $tcKimlikNo
     * @param string $vakaKodu
     * @return object Dönen cevap objesi
     * @throws Exception
     */
    public function personelimDegilBildir(string $medulaRaporId, string $tcKimlikNo, string $vakaKodu)
    {
        $params = [
            'kullaniciAdi' => $this->kullaniciAdi,
            'isyeriKodu' => $this->isyeriKodu,
            'wsToken' => $this->getValidToken(),
            'tckNo' => $tcKimlikNo,
            'vaka' => $vakaKodu,
            'medulaRaporId' => $medulaRaporId,
        ];

        $response = $this->sendRequest('personelimDegildir', $params);

        // Gelen cevabın kök objesini döndür
        return $response->personelimDegildirReturn;
    }


    /** METOT 17: RaporOnay
     * Bir rapora "Çalıştı" veya "Çalışmadı" bildirimini yapar.
     * @param string $medulaRaporId
     * @param string $tcKimlikNo
     * @param string $vakaKodu
     * @param string $nitelikDurumu "0" (Çalışmamıştır) veya "1" (Çalışmıştır)
     * @param DateTime $tarih İş başı tarihi
     * @return object Dönen cevap objesi
     * @throws Exception
     */
    public function raporuOnayla(
        string $medulaRaporId,
        string $tcKimlikNo,
        string $vakaKodu,
        string $nitelikDurumu,
        DateTime $tarih
    ) {
        $params = [
            'kullaniciAdi' => $this->kullaniciAdi,
            'isyeriKodu' => $this->isyeriKodu,
            'wsToken' => $this->getValidToken(),
            'tckNo' => $tcKimlikNo,
            'vaka' => $vakaKodu,
            'medulaRaporId' => $medulaRaporId,
            'nitelikDurumu' => $nitelikDurumu,
            'tarih' => $tarih->format('d.m.Y'),
        ];

        $response = $this->sendRequest('raporOnay', $params);

        return $response->raporOnayReturn;
    }

    /**
     * METOT 17: MahsuplastirmaOnayListesiSorguTarihle (NİHAİ VE DOĞRU VERSİYON)
     * Belirtilen tarih aralığında mahsuplaştırılacak raporları listeler.
     * @param DateTime $tarih1 Başlangıç tarihi
     * @param DateTime $tarih2 Bitiş tarihi
     * @return array Mahsuplaştırılacak raporların bulunduğu saf bir PHP dizisi.
     * @throws Exception
     */
    public function mahsuplastirilacakRaporlariGetir(DateTime $tarih1, DateTime $tarih2)
    {
        $params = [
            'kullaniciAdi' => $this->kullaniciAdi,
            'isyeriKodu' => $this->isyeriKodu,
            'wsToken' => $this->getValidToken(),
            'tarih' => $tarih1->format('d.m.Y'),
            'tarih2' => $tarih2->format('d.m.Y'),
        ];

        $response = $this->sendRequest('mahsuplastirmaOnayListesiSorguTarihle', $params);
        $returnNode = $response->mahsuplastirmaOnayListesiSorguTarihleReturn;
        $sonucDizisi = [];

        if (isset($returnNode->sonucKod) && $returnNode->sonucKod == '0') {

            // =============================================================
            // ===                DOĞRU YOL BURADA                       ===
            // =============================================================
            // Raporların bulunduğu doğru yola (makbuzRaporBean -> MakbuzRaporBean) erişiyoruz.
            if (isset($returnNode->makbuzRaporBean->MakbuzRaporBean)) {

                $raporlarObject = $returnNode->makbuzRaporBean->MakbuzRaporBean;

                // Manuel döngü ile en sağlam dönüşümü yapıyoruz
                // Gelen verinin tekil mi çoğul mu olduğunu bu şekilde anlarız.
                foreach ($raporlarObject as $rapor) {
                    $sonucDizisi[] = [
                        'id' => (string)$rapor->id,
                        'tcKimlikNo' => (string)$rapor->tcKimlikNo,
                        'adiSoyadi' => (string)$rapor->adiSoyadi,
                        'odenenTutar' => (string)$rapor->odenenTutar,
                        'odemeBasTar' => (string)$rapor->odemeBasTar,
                        'odemeBitTar' => (string)$rapor->odemeBitTar,
                        'vakaAdi' => (string)$rapor->vakaAdi
                    ];
                }
            }
        } else if (isset($returnNode->sonucKod) && $returnNode->sonucKod == '503') {
            // Rapor bulunamadı.
        } else {
            $hataMesaji = isset($returnNode->sonucAciklama)
                ? (string)$returnNode->sonucAciklama
                : 'Bilinmeyen bir hata oluştu.';
            throw new Exception("Mahsuplaştırılacak raporlar getirilemedi: " . $hataMesaji);
        }

        return $sonucDizisi;
    }

    /**
     * METOT 19: MahsuplastirmaOnay
     * Seçilen bir rapor için mahsuplaşma onayını SGK'ya bildirir.
     * @param array $rapor Raporun tüm bilgilerini içeren dizi
     * @return object Dönen cevap objesi
     * @throws Exception
     */
    public function mahsuplasmayiOnayla(array $rapor)
    {
        $params = [
            'kullaniciAdi' => $this->kullaniciAdi,
            'isyeriKodu' => $this->isyeriKodu,
            'wsToken' => $this->getValidToken(),
            'tckNo' => $rapor['tcKimlikNo'],
            'vakaAdi' => $rapor['vakaAdi'],
            'id' => $rapor['id'],
            'odemeBasTar' => $rapor['odemeBasTar'],
            'odemeBitTar' => $rapor['odemeBitTar'],
            'odenenTutar' => $rapor['odenenTutar']
        ];

        $response = $this->sendRequest('mahsuplastirmaOnay', $params);

        return $response->mahsuplastirmaOnayReturn;
    }


    /**
     * METOT 20: MahsuplastirmaOnaylananOdemeListesiSorguTarihle
     * Belirtilen tarih aralığında mahsuplaşması onaylanmış ödemeleri listeler.
     * @param DateTime $tarih1 Başlangıç tarihi
     * @param DateTime $tarih2 Bitiş tarihi
     * @return array Onaylanmış mahsuplaşma kayıtlarının bulunduğu saf bir PHP dizisi.
     * @throws Exception
     */
    public function mahsuplasmisRaporlariGetir(DateTime $tarih1, DateTime $tarih2)
    {
        $params = [
            'kullaniciAdi' => $this->kullaniciAdi,
            'isyeriKodu' => $this->isyeriKodu,
            'wsToken' => $this->getValidToken(),
            'tarih' => $tarih1->format('d.m.Y'),
            'tarih2' => $tarih2->format('d.m.Y'),
        ];

        $response = $this->sendRequest('mahsuplastirmaOnaylananOdemeListesiSorguTarihle', $params);

        $returnNode = $response->mahsuplastirmaOnaylananOdemeListesiSorguTarihleReturn;
        $sonucDizisi = [];

        if (isset($returnNode->sonucKod) && $returnNode->sonucKod == '0') {

            // Dökümandaki dönüş objesi `mansuplastirmaOdemeTakipBean` olabilir. Bu tahminidir.
            if (isset($returnNode->mansuplastirmaOdemeTakipBean->MansuplastirmaOdemeTakipBean)) {

                $raporlarObject = $returnNode->mansuplastirmaOdemeTakipBean->MansuplastirmaOdemeTakipBean;

                // Manuel döngü ile en sağlam dönüşümü yapıyoruz
                foreach ($raporlarObject as $rapor) {
                    $sonucDizisi[] = [
                        'tcKimlikNo' => (string)$rapor->tcKimlikNo,
                        'adiSoyadi' => (string)$rapor->adiSoyadi,
                        'vakaAdi' => (string)$rapor->vakaAdi,
                        'odemeBasTar' => (string)$rapor->odemeBasTar,
                        'odemeBitTar' => (string)$rapor->odemeBitTar,
                        'odenenTutar' => (string)$rapor->odenenTutar,
                        'mahsuplasmaTar' => (string)$rapor->mahsuplasmaTar,
                        'durumStr' => (string)$rapor->durumStr // Makbuz Durumu
                    ];
                }
            }
        } else if (isset($returnNode->sonucKod) && $returnNode->sonucKod == '503') {
            // Rapor bulunamadı.
        } else {
            $hataMesaji = isset($returnNode->sonucAciklama)
                ? (string)$returnNode->sonucAciklama
                : 'Bilinmeyen bir hata oluştu.';
            throw new Exception("Mahsuplaşması onaylanmış raporlar getirilemedi: " . $hataMesaji);
        }

        return $sonucDizisi;
    }

    /**
     * METOT 22: IsverenPrimBorcunaMahsupEdilenOdemeListesiSorguTarihle
     * Kurum tarafından işverenin prim borcuna mahsup edilen ödemeleri listeler.
     * @param DateTime $tarih1 Başlangıç tarihi
     * @param DateTime $tarih2 Bitiş tarihi
     * @return array Mahsup edilmiş ödemelerin bulunduğu saf bir PHP dizisi.
     * @throws Exception
     */
    public function primBorcunaMahsupEdilenleriGetir(DateTime $tarih1, DateTime $tarih2)
    {
        $params = [
            'kullaniciAdi' => $this->kullaniciAdi,
            'isyeriKodu' => $this->isyeriKodu,
            'wsToken' => $this->getValidToken(),
            'tarih' => $tarih1->format('d.m.Y'),
            'tarih2' => $tarih2->format('d.m.Y'),
        ];

        $response = $this->sendRequest('isverenPrimBorcunaMahsupEdilenOdemeListesiSorguTarihle', $params);

        $returnNode = $response->isverenPrimBorcunaMahsupEdilenOdemeListesiSorguTarihleReturn;
        $sonucDizisi = [];

        if (isset($returnNode->sonucKod) && $returnNode->sonucKod == '0') {

            // Dökümandaki dönüş objesi `isverenPrimBorcunaMahsupEdilenOdemeBean` olabilir.
            if (isset($returnNode->isverenPrimBorcunaMahsupEdilenOdemeBean->IsverenPrimBorcunaMahsupEdilenOdemeBean)) {

                $kayitlarObject = $returnNode->isverenPrimBorcunaMahsupEdilenOdemeBean->IsverenPrimBorcunaMahsupEdilenOdemeBean;

                // Manuel döngü ile en sağlam dönüşümü yapıyoruz
                foreach ($kayitlarObject as $kayit) {
                    $sonucDizisi[] = [
                        'tcKimlikNo' => (string)$kayit->tcKimlikNo,
                        'adiSoyadi' => (string)$kayit->adiSoyadi,
                        'vakaAdi' => (string)$kayit->vakaAdi,
                        'odemeBasTar' => (string)$kayit->odemeBasTar,
                        'odemeBitTar' => (string)$kayit->odemeBitTar,
                        'odenenTutar' => (string)$kayit->odenenTutar,
                        'mahsuplasmaTar' => (string)$kayit->mahsuplasmaTar,
                        'tahsilat_tutar' => (string)$kayit->tahsilat_tutar,

                        'primTahsilatDonem' => (string)$kayit->primTahsilatDonem
                    ];
                }
            }
        } else if (isset($returnNode->sonucKod) && $returnNode->sonucKod == '503') {
            // Kayıt yok.
        } else {
            $hataMesaji = isset($returnNode->sonucAciklama)
                ? (string)$returnNode->sonucAciklama
                : 'Bilinmeyen bir hata oluştu.';
            throw new Exception("Prim borcuna mahsup edilen ödemeler getirilemedi: " . $hataMesaji);
        }

        return $sonucDizisi;
    }

    /**
     * METOT 11: ManuelCalismazlikBildirimiGiris
     * Manuel olarak çalışılmadığına dair bildirim yapar.
     * @param string $tcKimlikNo
     * @param DateTime $raporBaslangicTarihi
     * @param DateTime $iseBaslamaTarihi
     * @param string $nitelikDurumu "E" (Çalıştı) veya "H" (Çalışmadı)
     * @return object Dönen cevap objesi
     * @throws Exception
     */
    public function manuelBildirimGir(string $tcKimlikNo, DateTime $raporBaslangicTarihi, DateTime $iseBaslamaTarihi, string $nitelikDurumu)
    {
        $params = [
            'kullaniciAdi' => $this->kullaniciAdi,
            'isyeriKodu' => $this->isyeriKodu,
            'wsToken' => $this->getValidToken(),
            'tckNo' => $tcKimlikNo,
            'raporBaslangicTarihi' => $raporBaslangicTarihi->format('d.m.Y'),
            'iseBaslamaTarihi' => $iseBaslamaTarihi->format('d.m.Y'),
            'nitelikDurumu' => $nitelikDurumu, // E veya H
        ];

        $response = $this->sendRequest('manuelCalismazlikBildirimiGiris', $params);

        return $response->manuelCalismazlikBildirimiGirisReturn;
    }

    /**
     * METOT 12: ManuelCalismazlikBildirimiGoruntu
     * Belirli bir kişi için daha önce yapılmış manuel bildirimleri listeler.
     * @param string $tcKimlikNo
     * @return array Manuel bildirimlerin bulunduğu saf bir PHP dizisi.
     * @throws Exception
     */
    public function manuelBildirimleriGetir(string $tcKimlikNo)
    {
        $params = [
            'kullaniciAdi' => $this->kullaniciAdi,
            'isyeriKodu' => $this->isyeriKodu,
            'wsToken' => $this->getValidToken(),
            'tckNo' => $tcKimlikNo,
        ];

        $response = $this->sendRequest('manuelCalismazlikBildirimiGoruntu', $params);
        $returnNode = $response->manuelCalismazlikBildirimiGoruntuReturn;
        $sonucDizisi = [];

        if (isset($returnNode->sonucKod) && $returnNode->sonucKod == '0') {
            if (isset($returnNode->manuelCalismazlikBean->ManuelCalismazlikBean)) {
                $bildirimlerObject = $returnNode->manuelCalismazlikBean->ManuelCalismazlikBean;

                // Manuel döngü ile en sağlam dönüşümü yapıyoruz
                foreach ($bildirimlerObject as $bildirim) {
                    $sonucDizisi[] = [
                        'ID' => (string)$bildirim->ID,
                        'tcKimlikNo' => (string)$bildirim->tcKimlikNo,
                        'adi' => (string)$bildirim->adi,
                        'soyadi' => (string)$bildirim->soyadi,
                        'istenAyrTarih' => (string)$bildirim->istenAyrTarih,
                        'iseDonusTarih' => (string)$bildirim->iseDonusTarih,
                        'nitelikDurumu' => (string)$bildirim->nitelikDurumu, // E veya H
                        'islemTar' => (string)$bildirim->islemTar
                    ];
                }
            }
        } else if (isset($returnNode->sonucKod) && $returnNode->sonucKod == '501') { // Kayıt Bulunamadı
            // Kayıt yoksa boş dizi döner, bu bir hata değildir.
        } else {
            $hataMesaji = isset($returnNode->sonucAciklama) ? (string)$returnNode->sonucAciklama : 'Bilinmeyen bir hata oluştu.';
            throw new Exception("Manuel bildirimler getirilemedi: " . $hataMesaji);
        }

        return $sonucDizisi;
    }

    /**
     * METOT 13: ManuelCalismazlikBildirimiSil
     * Daha önce yapılmış bir manuel bildirimi siler.
     * @param string $bildirimId Silinecek bildirimin ID'si
     * @param string $tcKimlikNo Bildirimin ait olduğu kişinin TC Kimlik Numarası
     * @return object Dönen cevap objesi
     * @throws Exception
     */
    public function manuelBildirimSil(string $bildirimId, string $tcKimlikNo)
    {
        $params = [
            'kullaniciAdi' => $this->kullaniciAdi,
            'isyeriKodu' => $this->isyeriKodu,
            'wsToken' => $this->getValidToken(),
            'id' => $bildirimId,
            'tckNo' => $tcKimlikNo,
        ];

        $response = $this->sendRequest('manuelCalismazlikBildirimiSil', $params);

        return $response->manuelCalismazlikBildirimiSilReturn;
    }

    /**
     * METOT 14: HasIsKazSorguTCile
     * TC Kimlik No ile iş kazası hastane provizyonlarını sorgular.
     * @param string $tcKimlikNo
     * @return array İş kazası kayıtlarının bulunduğu saf bir PHP dizisi.
     * @throws Exception
     */
    public function isKazasiGetirTcIle(string $tcKimlikNo)
    {
        $params = [
            'kullaniciAdi' => $this->kullaniciAdi,
            'isyeriKodu' => $this->isyeriKodu,
            'wsToken' => $this->getValidToken(),
            'tckNo' => $tcKimlikNo
        ];


        $response = $this->sendRequest('hasIsKazSorguTCile', $params);
        $returnNode = $response->hasIsKazSorguTCileReturn;

        return $this->isKazasiCevabiniDonustur($returnNode);
    }

    /**
     * METOT 15: HasIsKazSorguTarihle
     * Tarih aralığı ile iş kazası hastane provizyonlarını sorgular.
     * @param DateTime $tarih1
     * @param DateTime $tarih2
     * @return array İş kazası kayıtlarının bulunduğu saf bir PHP dizisi.
     * @throws Exception
     */
    public function isKazasiGetirTarihIle(DateTime $tarih1, DateTime $tarih2)
    {
        $params = ['kullaniciAdi' => $this->kullaniciAdi, 'isyeriKodu' => $this->isyeriKodu, 'wsToken' => $this->getValidToken(), 'tarih' => $tarih1->format('d.m.Y'), 'tarih2' => $tarih2->format('d.m.Y')];
        $response = $this->sendRequest('hasIsKazSorguTarihle', $params);
        $returnNode = $response->hasIsKazSorguTarihleReturn;
        return $this->isKazasiCevabiniDonustur($returnNode);
    }

    /**
     * METOT 16: HasIsKazSorguKapat
     * Bir iş kazası provizyonunu "okundu" olarak işaretler.
     * @param string $bildirimId Kapatılacak bildirimin ID'si
     * @return object Dönen cevap objesi
     * @throws Exception
     */
    public function isKazasiKapat(string $bildirimId)
    {
        $params = ['kullaniciAdi' => $this->kullaniciAdi, 'isyeriKodu' => $this->isyeriKodu, 'wsToken' => $this->getValidToken(), 'bildirimId' => $bildirimId];
        $response = $this->sendRequest('hasIsKazSorguKapat', $params);
        return $response->hasIsKazSorguKapatReturn;
    }

    /**
     * Yardımcı Fonksiyon: İş kazası metotlarından gelen cevabı standart bir diziye çevirir.
     * @param object $returnNode
     * @return array
     * @throws Exception
     */
    private function isKazasiCevabiniDonustur(object $returnNode): array
    {
        $sonucDizisi = [];
        if (isset($returnNode->sonucKod) && $returnNode->sonucKod == '0') {
            if (isset($returnNode->isKazasiHastaneBilgiBeanArray->IsKazasiHastaneBilgiBean)) {
                $kayitlarObject = $returnNode->isKazasiHastaneBilgiBeanArray->IsKazasiHastaneBilgiBean;
                foreach ($kayitlarObject as $kayit) {
                    $sonucDizisi[] = [
                        'BILDIRIMID' => (string)$kayit->BILDIRIMID,
                        'TCKIMLIKNO' => (string)$kayit->TCKIMLIKNO,
                        'CINSIYET' => (string)$kayit->CINSIYET,
                        'PROVIZYONTARIHI' => (string)$kayit->PROVIZYONTARIHI,
                        'TESISADI' => (string)$kayit->TESISADI,
                        'UNVANI' => (string)$kayit->UNVANI,
                        'ISLEMTUR' => (string)$kayit->ISLEMTUR,
                        'GIBSUBENO' => (string)$kayit->GIBSUBENO,
                        'ISKAZASITARIHI' => (string)$kayit->ISKAZASITARIHI,
                    ];
                }
            }
        } else if (isset($returnNode->sonucKod) && $returnNode->sonucKod != '503') { // 503 Kayıt yok hatası değilse
            throw new Exception($returnNode->sonucAciklama ?? 'İş kazası sorgulama başarısız.');
        }
        return $sonucDizisi;
    }

    /**
     * Arşivlenmiş (3 günden kısa süreli) raporları listeler.
     * Arka planda raporAramaTarihile'i (onay bekleyenler) çağırır ve sonuçları filtreler.
     *
     * @param DateTime $tarih1 Başlangıç tarihi
     * @param DateTime $tarih2 Bitiş tarihi
     * @return array Arşivlenmiş raporların bulunduğu saf bir PHP dizisi.
     * @throws Exception
     */
    public function arsivlenmisRaporlariGetir(
        DateTime $tarih1,
        DateTime $tarih2,
        ?string &$sgkHatasi = null
    )
    {
        $sgkHatasi = null;
        $arsivRaporModel = new ArsivRaporModel();
        $arsivlenmisRaporlar = $arsivRaporModel->tarihAraligindaGetir(
            $this->isyeriKodu,
            $tarih1,
            $tarih2
        );

        // raporAramaTarihile verilen tarihten küçük kayıtları döndürür. Seçilen bitiş
        // gününü de dahil etmek için servise bir sonraki günü gönder.
        $sgkSorguTarihi = (clone $tarih2)->modify('+1 day');
        try {
            $tumBekleyenRaporlar = $this->raporlariGetir($sgkSorguTarihi);
        } catch (Exception $e) {
            // SGK oturumu geçici olarak kullanılamasa bile daha önce kalıcı arşive
            // alınan kayıtları göstermeye devam et.
            $tumBekleyenRaporlar = [];
            $sgkHatasi = $e->getMessage();
        }

        $tekilRaporlar = [];
        foreach ($arsivlenmisRaporlar as $rapor) {
            if (!$this->arsivRaporuFiltreyeUyuyor($rapor, $tarih1, $tarih2)) {
                continue;
            }

            $anahtar = (string)($rapor['MEDULARAPORID'] ?? '');
            if ($anahtar !== '') {
                $tekilRaporlar[$anahtar] = $rapor;
            }
        }

        foreach ($tumBekleyenRaporlar as $rapor) {
            if (!$this->arsivRaporuFiltreyeUyuyor($rapor, $tarih1, $tarih2)) {
                continue;
            }

            // Henüz SGK kuyruğunda duran arşivleri de hemen yerel arşive
            // kaydet; sonraki bekleyen rapor sorgusu kapatsa bile kaybolmasın.
            $arsivRaporModel->arsivle(
                $rapor,
                $this->isyeriKodu,
                isset($_SESSION['isyeri_id']) ? (int)$_SESSION['isyeri_id'] : null,
                isset($_SESSION['kullanici_id']) ? (int)$_SESSION['kullanici_id'] : null
            );

            $anahtar = (string)($rapor['MEDULARAPORID'] ?? '');
            if ($anahtar !== '') {
                $tekilRaporlar[$anahtar] = $rapor;
            }
        }

        $arsivlenmisRaporlar = array_values($tekilRaporlar);
        usort($arsivlenmisRaporlar, static function (array $a, array $b): int {
            return strcmp((string)($b['POLIKLINIKTAR'] ?? ''), (string)($a['POLIKLINIKTAR'] ?? ''));
        });

        return $arsivlenmisRaporlar;
    }

    private function arsivRaporuFiltreyeUyuyor(array $rapor, DateTime $tarih1, DateTime $tarih2): bool
    {
        if ((string)($rapor['ARSIV'] ?? '0') !== '1') {
            return false;
        }

        $baslangic = $this->parseSgkDate($rapor['POLIKLINIKTAR'] ?? null);
        $iseBasi = $this->parseSgkDate($rapor['ISBASKONTTAR'] ?? null);
        if ($baslangic === null || $iseBasi === null || $iseBasi < $baslangic) {
            return false;
        }

        if ($baslangic->diff($iseBasi)->days >= 3) {
            return false;
        }

        $aralikBaslangici = DateTimeImmutable::createFromMutable($tarih1)->setTime(0, 0);
        $aralikBitisi = DateTimeImmutable::createFromMutable($tarih2)->setTime(23, 59, 59);
        return $baslangic >= $aralikBaslangici && $baslangic <= $aralikBitisi;
    }

    private function parseSgkDate($value): ?DateTimeImmutable
    {
        $value = trim((string)$value);
        if ($value === '' || in_array($value, ['0000-00-00', '0001-01-01'], true)) {
            return null;
        }

        foreach (['Y-m-d', 'd.m.Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
            $errors = DateTimeImmutable::getLastErrors();
            $hasErrors = is_array($errors)
                && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);
            if ($date !== false && !$hasErrors && $date->format($format) === $value) {
                return $date;
            }
        }

        return null;
    }
}
