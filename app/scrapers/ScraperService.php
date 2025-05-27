<?php

use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;

class ScraperService {
    private $pdo;
    private $config;
    private $client;
    private $guzzle;
    private $imagesDir;

    public function __construct($pdo, $config) {
        $this->pdo = $pdo;
        $this->config = $config;
        $this->imagesDir = ROOT_PATH . '/storage/images/';
        
        $this->client = new Client();
        $this->guzzle = new GuzzleClient([
            'headers' => ['User-Agent' => 'Mozilla/5.0'],
            'timeout' => 10,
            'connect_timeout' => 5
        ]);
    }

    public function execute() {
        // Obtener la última fecha de scraping
        $stmt = $this->pdo->prepare("SELECT value FROM configs WHERE name = 'last_scrape_date'");
        $stmt->execute();
        $lastScrapeDate = $stmt->fetchColumn() ?: '2000-01-01';

        $hoy = date('Y-m-d');
        if ($lastScrapeDate !== $hoy) {
            $this->cleanupOldData($lastScrapeDate);
        }

        $results = [];
        foreach ($this->config['sites'] as $country => $sites) {
            $results[$country] = $this->scrapeSites($sites, $country);
        }

        return $results;
    }

    private function cleanupOldData($lastScrapeDate) {
        echo "Limpiando imágenes y reiniciando base de datos (última fecha: $lastScrapeDate)...\n";

        if (is_dir($this->imagesDir)) {
            $files = glob($this->imagesDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) unlink($file);
            }
        } else {
            mkdir($this->imagesDir, 0777, true);
        }

        $this->pdo->exec('TRUNCATE TABLE covers');

        $update = $this->pdo->prepare("REPLACE INTO configs (name, value) VALUES ('last_scrape_date', :fecha)");
        $update->execute([':fecha' => date('Y-m-d')]);
    }

    private function scrapeSites($sites, $country) {
        $results = [];
        foreach ($sites as $site) {
            try {
                $result = null;
                if (isset($site['custom_extractor'])) {
                    $method = $site['custom_extractor'];
                    $result = $this->$method($site['url'], $country);
                } elseif (isset($site['use_xpath'])) {
                    $result = $this->scrapeWithXPath($site, $country);
                } else {
                    $result = $this->scrapeNormal($site, $country);
                }
                
                // Solo agregar al resultado si se encontró una imagen
                if ($result && !isset($result['error'])) {
                    $results[] = $result;
                }
            } catch (Exception $e) {
                error_log("Error scraping {$site['url']}: " . $e->getMessage());
                // No agregamos errores al resultado
            }
        }
        return $results;
    }

    private function makeAbsoluteUrl($baseUrl, $relativeUrl) {
        if (strpos($relativeUrl, '//') === 0) return 'https:' . $relativeUrl;
        if (strpos($relativeUrl, 'http') === 0) return $relativeUrl;
        return rtrim($baseUrl, '/') . '/' . ltrim($relativeUrl, '/');
    }

    private function getFullSizeImageUrl($url) {
        return preg_replace('/-\d+x\d+(?=\.\w+$)/', '', $url);
    }

    private function saveImageLocally($url, $country, $title) {
        if (empty($url)) {
            error_log("URL vacía en saveImageLocally para $title ($country)");
            return false;
        }

        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($title));
        $filename = "{$country}_{$slug}_" . uniqid() . ".webp";
        $path = $this->imagesDir . $filename;

        try {
            $response = $this->guzzle->get($url);
            $imageData = $response->getBody()->getContents();

            $imagick = new Imagick();
            $imagick->readImageBlob($imageData);

            if ($imagick->getImageColorspace() === Imagick::COLORSPACE_CMYK) {
                $imagick->transformImageColorspace(Imagick::COLORSPACE_RGB);
            }

            $imagick->resizeImage(300, 0, Imagick::FILTER_LANCZOS, 1);
            $imagick->setImageFormat('webp');
            $imagick->setImageCompressionQuality(80);

            try {
                $imagick->writeImage($path);
            } finally {
                $imagick->clear();
                $imagick->destroy();
            }

            return 'storage/images/' . $filename;
        } catch (Exception $e) {
            error_log("No se pudo guardar la imagen: $url - " . $e->getMessage());
            return false;
        }
    }

    private function storeCover($country, $alt, $urlImg, $sourceLink) {
        try {
            $stmt = $this->pdo->prepare('SELECT 1 FROM covers WHERE country=:c AND original_link=:u');
            $stmt->execute([':c' => $country, ':u' => $urlImg]);
            if (!$stmt->fetchColumn()) {
                $local = $this->saveImageLocally($urlImg, $country, $alt);
                if ($local) {
                    $ins = $this->pdo->prepare("INSERT INTO covers(country,title,image_url,source,original_link) VALUES(:c,:t,:i,:s,:l)");
                    $ins->execute([':c' => $country, ':t' => $alt, ':i' => $local, ':s' => $sourceLink, ':l' => $urlImg]);
                    return true;
                }
            }
        } catch (Exception $e) {
            error_log("Error guardando cover para {$urlImg}: " . $e->getMessage());
        }
        return false;
    }

    private function extractHiresFromFusionScript($url, $country) {
        $crawler = $this->client->request('GET', $url);
        $script = $crawler->filter('script')->reduce(function ($node) {
            return strpos($node->text(), 'Fusion.globalContent') !== false;
        });

        if ($script->count() === 0) return null;

        $content = $script->text();
        if (preg_match('/Fusion\\.globalContent\\s*=\\s*(\{.*?\});/s', $content, $m)) {
            $json = json_decode($m[1], true);
            $imageUrl = isset($json['data']['hires']) ? $json['data']['hires'] : null;
            if ($imageUrl) {
                $this->storeCover($country, 'ABC Digital', $imageUrl, $url);
                return ['url' => $url, 'image' => $imageUrl];
            }
        }

        return null; // Retornamos null en lugar de un error
    }

    private function scrapeWithXPath($site, $country) {
        $url = $site['url'];
        $xpath = $site['xpath'];
        $attribute = isset($site['attribute']) ? $site['attribute'] : 'href';

        // Agregar delay aleatorio para evitar bloqueos
        usleep(rand(500000, 2000000)); // delay entre 0.5 y 2 segundos

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => $this->getRandomUserAgent(),
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_ENCODING => 'gzip, deflate',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'DNT: 1'
            ],
            CURLOPT_COOKIEJAR => __DIR__ . '/../../storage/cookies.txt',
            CURLOPT_COOKIEFILE => __DIR__ . '/../../storage/cookies.txt'
        ]);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$html || strlen($html) < 100) {
            error_log("Error obteniendo página $url: HTTP $httpCode");
            return null;
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        if (!$dom->loadHTML($html)) {
            error_log("Error parseando HTML de $url");
            return null;
        }

        $xpathObj = new DOMXPath($dom);
        $nodes = $xpathObj->query($xpath);
        if ($nodes && $nodes->length > 0) {
            $imageUrl = $nodes->item(0)->getAttribute($attribute);
            if ($imageUrl) {
                $imageUrl = $this->makeAbsoluteUrl($url, $imageUrl);
                if ($this->storeCover($country, basename($url), $imageUrl, $url)) {
                    return ['url' => $url, 'image' => $imageUrl];
                }
            }
        }

        return null;
    }

    private function getRandomUserAgent() {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Edge/91.0.864.59 Safari/537.36'
        ];
        return $userAgents[array_rand($userAgents)];
    }

    private function scrapeNormal($site, $country) {
        $crawler = $this->client->request('GET', $site['url']);
        $results = [];

        try {
            if ($site['multiple']) {
                $crawler->filter($site['selector'])->each(function ($node) use ($site, $country, &$results) {
                    $result = $this->processNode($node, $site, $country);
                    if ($result && isset($result['image'])) {
                        $results[] = $result;
                    }
                });
                return ['url' => $site['url'], 'results' => array_filter($results)];
            } else {
                $node = $crawler->filter($site['selector']);
                if ($node->count() > 0) {
                    $result = $this->processNode($node, $site, $country);
                    if ($result && isset($result['image'])) {
                        return $result;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error en scrapeNormal para {$site['url']}: " . $e->getMessage());
        }

        return null;
    }

    private function processNode($node, $site, $country) {
        try {
            $baseUrl = $site['url'];
            $imageUrl = null;
            
            if (isset($site['followLinks'])) {
                try {
                    $link = $node->filter($site['followLinks']['linkSelector'])->link();
                    $crawler = $this->client->click($link);
                    
                    if (isset($site['followLinks']['linkImgSelector'])) {
                        $imgLink = $crawler->filter($site['followLinks']['linkImgSelector'])->link();
                        $crawler = $this->client->click($imgLink);
                    }
                    
                    $imageUrl = $crawler->filter($site['followLinks']['imageSelector'])->attr('src');
                } catch (Exception $e) {
                    error_log("Error siguiendo enlaces en {$baseUrl}: " . $e->getMessage());
                    return null;
                }
            } else {
                $attribute = isset($site['attribute']) ? $site['attribute'] : 'src';
                $imageUrl = $node->attr($attribute);
            }

            if (empty($imageUrl)) {
                return null;
            }

            $imageUrl = $this->makeAbsoluteUrl($baseUrl, $imageUrl);
            if (isset($site['transformImageUrl']) && is_callable($site['transformImageUrl'])) {
                $imageUrl = $site['transformImageUrl']($imageUrl);
            }

            $title = $node->attr('alt');
            if (empty($title)) {
                $title = basename($imageUrl);
            }

            if ($this->storeCover($country, $title, $imageUrl, $baseUrl)) {
                return [
                    'title' => $title,
                    'image' => $imageUrl
                ];
            }
        } catch (Exception $e) {
            error_log("Error procesando nodo para {$baseUrl}: " . $e->getMessage());
        }

        return null;
    }
} 