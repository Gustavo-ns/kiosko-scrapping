<?php

namespace App\Controllers;

use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use Imagick;

class ScrapeController extends BaseController
{
    private $client;
    private $guzzle;

    public function __construct()
    {
        parent::__construct();
        
        $this->client = new Client();
        $this->guzzle = new GuzzleClient([
            'headers' => ['User-Agent' => 'Mozilla/5.0'],
            'timeout' => $this->config['scraping']['timeout'],
            'connect_timeout' => 5
        ]);
    }

    public function index()
    {
        // Obtener la última fecha de scraping
        $stmt = $this->pdo->prepare("SELECT value FROM configs WHERE name = 'last_scrape_date'");
        $stmt->execute();
        $lastScrapeDate = $stmt->fetchColumn() ?: '2000-01-01';

        $hoy = date('Y-m-d');
        if ($lastScrapeDate !== $hoy) {
            $this->cleanupImages();
            $this->resetDatabase();
            $this->updateLastScrapeDate($hoy);
        }

        $total = $this->countSitesToScrape();
        $processed = $this->processSites();

        echo $this->view('scrape/index', [
            'total' => $total,
            'processed' => $processed,
            'config' => $this->config
        ]);
    }

    private function cleanupImages()
    {
        $imagesDir = $this->config['images']['storage_path'];
        if (is_dir($imagesDir)) {
            $files = glob($imagesDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) unlink($file);
            }
        } else {
            mkdir($imagesDir, 0777, true);
        }
    }

    private function resetDatabase()
    {
        $this->pdo->exec('TRUNCATE TABLE covers');
    }

    private function updateLastScrapeDate($date)
    {
        $update = $this->pdo->prepare("REPLACE INTO configs (name, value) VALUES ('last_scrape_date', :fecha)");
        $update->execute([':fecha' => $date]);
    }

    private function countSitesToScrape()
    {
        $total = 0;
        foreach ($this->config['sites'] as $country => $confs) {
            $total += count($confs);
        }
        return $total;
    }

    private function processSites()
    {
        $processed = 0;
        foreach ($this->config['sites'] as $country => $configs) {
            foreach ($configs as $conf) {
                try {
                    $this->processSite($country, $conf);
                    $processed++;
                } catch (\Exception $e) {
                    error_log("Error en {$conf['url']}: " . $e->getMessage());
                }
            }
        }
        return $processed;
    }

    private function processSite($country, $conf)
    {
        $crawler = $this->client->request('GET', $conf['url']);
        $urlImg = '';
        $alt = 'Portada';
        $fullUri = $conf['url'];

        if (!empty($conf['custom_extractor'])) {
            $this->processCustomExtractor($conf, $country, $alt, $fullUri);
            return;
        }

        if (!empty($conf['attribute']) && !empty($conf['selector'])) {
            $this->processAttributeSelector($conf, $crawler, $country, $alt, $fullUri);
            return;
        }

        if (!empty($conf['selector']) && empty($conf['multiple'])) {
            $this->processSingleSelector($conf, $crawler, $country, $alt, $fullUri);
            return;
        }

        if (!empty($conf['multiple'])) {
            $this->processMultipleSelector($conf, $crawler, $country);
            return;
        }
    }

    private function processCustomExtractor($conf, $country, $alt, $fullUri)
    {        switch ($conf['custom_extractor']) {
            case 'extractWithXPath':
                $xpath = isset($conf['xpath']) ? $conf['xpath'] : '//img';
                $attr = isset($conf['attribute']) ? $conf['attribute'] : 'src';
                $urlImg = $this->extractWithXPath($conf['url'], $xpath, $attr);
                if ($urlImg) {
                    $urlImg = $this->makeAbsoluteUrl($conf['url'], $urlImg);
                    $this->storeCover($country, $alt, $urlImg, $fullUri);
                }
                break;

            case 'extractHiresFromFusionScript':
                $hiresUrl = $this->extractHiresFromFusionScript($crawler);
                if ($hiresUrl) {
                    $urlImg = $this->makeAbsoluteUrl($conf['url'], $hiresUrl);
                    $this->storeCover($country, $alt, $urlImg, $fullUri);
                }
                break;
        }
    }

    private function processAttributeSelector($conf, $crawler, $country, $alt, $fullUri)
    {
        $node = $crawler->filter($conf['selector']);
        if ($node->count()) {
            $urlImg = $this->makeAbsoluteUrl($conf['url'], $node->attr($conf['attribute']));
            $this->storeCover($country, $alt, $urlImg, $fullUri);
        }
    }

    private function processSingleSelector($conf, $crawler, $country, $alt, $fullUri)
    {
        $node = $crawler->filter($conf['selector']);
        if ($node->count()) {
            $img = $node->attr('src') ?: '';
            $alt = $node->attr('alt') ?: $alt;
            $urlImg = $this->makeAbsoluteUrl($conf['url'], $img);
            $this->storeCover($country, $alt, $urlImg, $fullUri);
        }
    }

    private function processMultipleSelector($conf, $crawler, $country)
    {
        $crawler->filter($conf['selector'])->each(function ($node) use ($conf, $country) {
            $urlImg = '';
            $linkPage = $conf['url'];
            $alt = 'Portada';

            if (!empty($conf['followLinks'])) {
                $this->processFollowLinks($conf, $node, $country, $linkPage);
            } else {
                $this->processDirectImage($conf, $node, $country, $linkPage);
            }
        });
    }

    private function processFollowLinks($conf, $node, $country, $linkPage)
    {
        $linkNode = $node->filter($conf['followLinks']['linkSelector']);
        if ($linkNode->count()) {
            $link = $linkNode->attr('href') ?: '';
            $full = $this->makeAbsoluteUrl($conf['url'], $link);
            $detail = $this->client->request('GET', $full);
            $imgNode = $detail->filter($conf['followLinks']['imageSelector']);
            if ($imgNode->count()) {
                $img = $imgNode->attr('src') ?: '';
                $alt = $node->filter('img')->attr('alt') ?: 'Portada';
                $urlImg = $this->makeAbsoluteUrl($full, $img);
                $this->storeCover($country, $alt, $urlImg, $full);
            }
        }
    }

    private function processDirectImage($conf, $node, $country, $linkPage)
    {
        $imgNode = $node->filter('img');
        if ($imgNode->count()) {
            $img = $imgNode->attr('src') ?: '';
            $alt = $imgNode->attr('alt') ?: 'Portada';
            $urlImg = $this->makeAbsoluteUrl($conf['url'], $img);
            $this->storeCover($country, $alt, $urlImg, $linkPage);
        }
    }

    private function makeAbsoluteUrl($baseUrl, $relativeUrl)
    {
        if (strpos($relativeUrl, '//') === 0) return 'https:' . $relativeUrl;
        if (strpos($relativeUrl, 'http') === 0) return $relativeUrl;
        return rtrim($baseUrl, '/') . '/' . ltrim($relativeUrl, '/');
    }

    private function extractWithXPath($url, $xpath, $attribute = 'href')
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => $this->config['scraping']['timeout'],
            CURLOPT_TIMEOUT => $this->config['scraping']['timeout'] + 2,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; scraper-bot)',
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_RANGE => '0-65535',
            CURLOPT_HTTPHEADER => ['Accept: text/html'],
        ]);

        $html = curl_exec($ch);
        curl_close($ch);

        if (!$html || strlen($html) < 100) return null;

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        if (!$dom->loadHTML($html)) return null;

        $xpathObj = new \DOMXPath($dom);
        $nodes = $xpathObj->query($xpath);
        if ($nodes && $nodes->length > 0) {
            return $nodes->item(0)->getAttribute($attribute);
        }

        return null;
    }

    private function extractHiresFromFusionScript($crawler)
    {
        $script = $crawler->filter('script')->reduce(function ($node) {
            return strpos($node->text(), 'Fusion.globalContent') !== false;
        });

        if ($script->count() === 0) return null;

        $content = $script->text();
        if (preg_match('/Fusion\\.globalContent\\s*=\\s*(\{.*?\});/s', $content, $m)) {
            $json = json_decode($m[1], true);
            return isset($json['data']['hires']) ? $json['data']['hires'] : null;
        }

        return null;
    }

    private function storeCover($country, $alt, $urlImg, $sourceLink)
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM covers WHERE country=:c AND original_link=:u');
        $stmt->execute([':c' => $country, ':u' => $urlImg]);
        if (!$stmt->fetchColumn()) {
            $local = $this->saveImageLocally($urlImg, $country, $alt);
            if ($local) {
                $ins = $this->pdo->prepare("INSERT INTO covers(country,title,image_url,source,original_link) VALUES(:c,:t,:i,:s,:l)");
                $ins->execute([
                    ':c' => $country, 
                    ':t' => $alt, 
                    ':i' => $local,
                    ':s' => $sourceLink, 
                    ':l' => $urlImg
                ]);
            }
        }
    }

    private function saveImageLocally($imageUrl, $country, $alt)
    {
        $filename = preg_replace('/[^a-z0-9_\-]/i', '_', $alt) . '_' . uniqid();
        $savePath = $this->config['images']['storage_path'] . '/' . $filename;

        try {
            $response = $this->guzzle->get($imageUrl);
            $imageData = $response->getBody()->getContents();
        } catch (\Exception $e) {
            error_log("No se pudo descargar la imagen: $imageUrl - " . $e->getMessage());
            return false;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'img_');
        file_put_contents($tempFile, $imageData);

        try {
            $info = @getimagesize($tempFile);
            if ($info === false) {
                throw new \Exception("Archivo no válido como imagen");
            }

            $mime = $info['mime'];
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($mime, $allowedMimes)) {
                throw new \Exception("Formato de imagen no soportado ($mime)");
            }

            $imagick = new Imagick();
            $imagick->readImage($tempFile);
            
            if ($imagick->getImageColorspace() === Imagick::COLORSPACE_CMYK) {
                $imagick->transformImageColorspace(Imagick::COLORSPACE_SRGB);
            }

            $imagick->stripImage();
            
            $imagick->setImageFormat($this->config['images']['format']);
            $imagick->setImageCompressionQuality($this->config['images']['quality']);
            
            $width = $imagick->getImageWidth();
            $height = $imagick->getImageHeight();
            
            if ($width > $this->config['images']['max_width'] || $height > $this->config['images']['max_height']) {
                $imagick->resizeImage(
                    $this->config['images']['max_width'],
                    $this->config['images']['max_height'],
                    Imagick::FILTER_LANCZOS,
                    1,
                    true
                );
            }
            
            $finalPath = $savePath . '.' . $this->config['images']['format'];
            $imagick->writeImage($finalPath);
            
            $imagick->clear();
            $imagick->destroy();
            unlink($tempFile);
            
            return "assets/images/" . basename($finalPath);
            
        } catch (\Exception $e) {
            error_log("Error procesando la imagen: $imageUrl - " . $e->getMessage());
            if (file_exists($tempFile)) unlink($tempFile);
            if (isset($finalPath) && file_exists($finalPath)) unlink($finalPath);
            return false;
        }
    }

    // Async scraping methods for API endpoints
    public function start()
    {
        header('Content-Type: application/json');
        
        // Check if a process is already running
        $processFile = sys_get_temp_dir() . '/scrape_process.pid';
        if (file_exists($processFile)) {
            $pid = file_get_contents($processFile);
            if ($this->isProcessRunning($pid)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Ya hay un proceso de scraping en ejecución'
                ]);
                return;
            }
        }

        // Generate a unique process ID
        $processId = uniqid('scrape_');
        
        try {
            // Start the scraping process in background
            $command = "php " . realpath(__DIR__ . '/../../scrape.php') . " > /dev/null 2>&1 & echo $!";
            $pid = shell_exec($command);
            
            if ($pid) {
                // Store process information
                file_put_contents($processFile, trim($pid));
                file_put_contents(sys_get_temp_dir() . "/scrape_{$processId}.json", json_encode([
                    'processId' => $processId,
                    'pid' => trim($pid),
                    'started' => time(),
                    'total' => $this->countSitesToScrape(),
                    'processed' => 0,
                    'status' => 'running',
                    'log' => []
                ]));
                
                echo json_encode([
                    'success' => true,
                    'processId' => $processId,
                    'message' => 'Proceso de scraping iniciado'
                ]);
            } else {
                throw new \Exception('No se pudo iniciar el proceso');
            }
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error al iniciar el scraping: ' . $e->getMessage()
            ]);
        }
    }

    public function stop()
    {
        header('Content-Type: application/json');
        
        $processId = isset($_GET['processId']) ? $_GET['processId'] : (isset($_POST['processId']) ? $_POST['processId'] : null);
        if (!$processId) {
            echo json_encode([
                'success' => false,
                'error' => 'ID de proceso requerido'
            ]);
            return;
        }

        try {
            $processFile = sys_get_temp_dir() . "/scrape_{$processId}.json";            if (file_exists($processFile)) {
                $processInfo = json_decode(file_get_contents($processFile), true);
                $pid = isset($processInfo['pid']) ? $processInfo['pid'] : null;
                
                if ($pid && $this->isProcessRunning($pid)) {
                    // Try to kill the process gracefully first
                    exec("kill {$pid}", $output, $return);
                    sleep(2);
                    
                    // If still running, force kill
                    if ($this->isProcessRunning($pid)) {
                        exec("kill -9 {$pid}");
                    }
                }
                
                // Update process status
                $processInfo['status'] = 'stopped';
                $processInfo['stopped'] = time();
                file_put_contents($processFile, json_encode($processInfo));
                
                // Clean up main process file
                $mainProcessFile = sys_get_temp_dir() . '/scrape_process.pid';
                if (file_exists($mainProcessFile)) {
                    unlink($mainProcessFile);
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Proceso detenido'
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error al detener el proceso: ' . $e->getMessage()
            ]);
        }
    }

    public function status()
    {
        header('Content-Type: application/json');
        
        $processId = isset($_GET['processId']) ? $_GET['processId'] : null;
        if (!$processId) {
            echo json_encode([
                'success' => false,
                'error' => 'ID de proceso requerido'
            ]);
            return;
        }

        try {
            $processFile = sys_get_temp_dir() . "/scrape_{$processId}.json";
            if (!file_exists($processFile)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Proceso no encontrado'
                ]);
                return;
            }            $processInfo = json_decode(file_get_contents($processFile), true);
            $pid = isset($processInfo['pid']) ? $processInfo['pid'] : null;
            
            // Check if process is still running
            $isRunning = $pid ? $this->isProcessRunning($pid) : false;
            
            if (!$isRunning && $processInfo['status'] === 'running') {
                $processInfo['status'] = 'completed';
                $processInfo['completed'] = time();
                file_put_contents($processFile, json_encode($processInfo));
            }

            // Get current processed count from database
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM covers");
            $stmt->execute();
            $currentProcessed = $stmt->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'processId' => $processId,
                'status' => $processInfo['status'],
                'total' => $processInfo['total'],
                'processed' => $currentProcessed,
                'completed' => ($processInfo['status'] === 'completed' || $processInfo['status'] === 'stopped'),
                'log' => $this->getRecentLogs(),
                'isRunning' => $isRunning
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error al obtener el estado: ' . $e->getMessage()
            ]);
        }
    }

    private function isProcessRunning($pid)
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec("tasklist /FI \"PID eq {$pid}\" 2>NUL");
            return strpos($output, (string)$pid) !== false;
        } else {
            $output = shell_exec("ps -p {$pid} 2>/dev/null");
            return !empty($output) && strpos($output, (string)$pid) !== false;
        }
    }

    private function getRecentLogs()
    {
        $logFile = __DIR__ . '/../../scrape_errors.log';
        if (!file_exists($logFile)) {
            return [];
        }

        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice($lines, -10); // Return last 10 log entries
    }
}