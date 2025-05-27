<?php
require_once __DIR__ . '/../config/DatabaseConnection.php';

class MeltwaterModel {
    private $db;

    public function __construct() {
        $this->db = DatabaseConnection::getInstance();
    }

    private function execute($callback) {
        try {
            $pdo = $this->db->getConnection();
            $result = $callback($pdo);
            return $result;
        } finally {
            $this->db->closeConnection();
        }
    }

    public function getConnection() {
        return $this->db->getConnection();
    }

    public function getContentHash() {
        return $this->execute(function($pdo) {
            $stmt = $pdo->query("SELECT MAX(published_date) as last_update FROM pk_melwater");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return md5($result['last_update'] . ASSETS_VERSION);
        });
    }

    public function getMeltwaterDocs() {
        return $this->execute(function($pdo) {
            $stmt = $pdo->query("
                SELECT 
                    med.*,
                    pk.*,
                    med.twitter_screen_name,
                    med.dereach,
                    med.grupo,
                    med.pais as pais_medio,
                    'meltwater' as source_type
                FROM pk_melwater pk
                LEFT JOIN medios med ON pk.external_id = med.twitter_id
                ORDER BY med.grupo, med.pais, med.dereach DESC
            ");
            return $stmt->fetchAll();
        });
    }

    public function getCovers() {
        return $this->execute(function($pdo) {
            $stmt = $pdo->query("
                SELECT 
                    c.*,
                    'cover' as source_type
                FROM covers c 
                ORDER BY c.scraped_at DESC
            ");
            return $stmt->fetchAll();
        });
    }

    public function getGrupos() {
        return $this->execute(function($pdo) {
            return $pdo->query("
                SELECT DISTINCT med.grupo 
                FROM pk_melwater pk 
                LEFT JOIN medios med ON pk.external_id = med.twitter_id 
                WHERE med.grupo IS NOT NULL 
                ORDER BY med.grupo
            ")->fetchAll(PDO::FETCH_COLUMN);
        });
    }

    public function getPaises() {
        return $this->execute(function($pdo) {
            return $pdo->query("
                SELECT DISTINCT country 
                FROM covers 
                ORDER BY country
            ")->fetchAll(PDO::FETCH_COLUMN);
        });
    }

    public function updateData() {
        return $this->execute(function($pdo) {
            // Aquí iría la lógica de actualización de datos
            return ['success' => true];
        });
    }

    public function updateMeltwaterDocuments($documents) {
        return $this->execute(function($pdo) use ($documents) {
            // Preparar la consulta de inserción
            $stmt = $pdo->prepare("INSERT INTO pk_melwater (
                external_id, published_date, source_id, social_network, 
                country_code, country_name, author_name, content_image, 
                content_text, url_destino, input_names
            ) VALUES (
                :external_id, :published_date, :source_id, :social_network,
                :country_code, :country_name, :author_name, :content_image,
                :content_text, :url_destino, :input_names
            ) ON DUPLICATE KEY UPDATE
                published_date = VALUES(published_date),
                source_id = VALUES(source_id),
                social_network = VALUES(social_network),
                country_code = VALUES(country_code),
                country_name = VALUES(country_name),
                author_name = VALUES(author_name),
                content_image = VALUES(content_image),
                content_text = VALUES(content_text),
                url_destino = VALUES(url_destino),
                input_names = VALUES(input_names)");

            $country_names = [
                'ar' => 'Argentina',
                'bo' => 'Bolivia',
                'br' => 'Brasil',
                'cl' => 'Chile',
                'co' => 'Colombia',
                'ec' => 'Ecuador',
                'us' => 'Estados Unidos',
                'mx' => 'México',
                'pa' => 'Panamá',
                'py' => 'Paraguay',
                'pe' => 'Perú',
                'do' => 'República Dominicana',
                'uy' => 'Uruguay',
                've' => 'Venezuela',
                'es' => 'España',
                'gb' => 'Reino Unido',
                'zz' => 'Desconocido'
            ];

            $updatedCount = 0;
            foreach ($documents as $doc) {
                $author_name = isset($doc['author']['name']) ? $doc['author']['name'] : 'N/A';
                $content_image = isset($doc['content']['image']) ? $doc['content']['image'] : null;
                
                // Skip documents without image
                if (!$content_image) {
                    continue;
                }
                
                $content_text = isset($doc['content']['opening_text']) ? $doc['content']['opening_text'] : '';
                $country_code = strtolower(isset($doc['location']['country_code']) ? $doc['location']['country_code'] : 'zz');
                $country_name = isset($country_names[$country_code]) ? $country_names[$country_code] : ucfirst($country_code);
                $url_destino = isset($doc['url']) ? $doc['url'] : '#';
                $external_id = isset($doc['author']['external_id']) ? $doc['author']['external_id'] : '';
                $published_date = isset($doc['published_date']) ? $doc['published_date'] : '';
                $source_id = isset($doc['source']['id']) ? $doc['source']['id'] : '';
                
                // Extraer red social del source_id
                $social_network = '';
                if (!empty($source_id) && strpos($source_id, 'social:') === 0) {
                    $parts = explode(':', $source_id);
                    $social_network = isset($parts[1]) ? ucfirst($parts[1]) : '';
                }

                // Obtener los inputs names
                $input_names = [];
                if (isset($doc['matched']['inputs']) && is_array($doc['matched']['inputs'])) {
                    foreach ($doc['matched']['inputs'] as $input) {
                        if (isset($input['name'])) {
                            $input_names[] = $input['name'];
                        }
                    }
                }
                $input_names_str = implode(', ', $input_names);

                try {
                    $stmt->execute([
                        ':external_id' => $external_id,
                        ':published_date' => $published_date,
                        ':source_id' => $source_id,
                        ':social_network' => $social_network,
                        ':country_code' => $country_code,
                        ':country_name' => $country_name,
                        ':author_name' => $author_name,
                        ':content_image' => $content_image,
                        ':content_text' => $content_text,
                        ':url_destino' => $url_destino,
                        ':input_names' => $input_names_str
                    ]);
                    $updatedCount++;
                } catch (PDOException $e) {
                    error_log("Error al actualizar registro {$external_id}: " . $e->getMessage());
                    continue;
                }
            }

            return $updatedCount;
        });
    }
} 