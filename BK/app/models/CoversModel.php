<?php
require_once __DIR__ . '/../config/DatabaseConnection.php';

class CoversModel {
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

    public function getCovers($country = null) {
        return $this->execute(function($pdo) use ($country) {
            if ($country) {
                $stmt = $pdo->prepare("SELECT * FROM covers WHERE country = :country ORDER BY scraped_at DESC");
                $stmt->execute([':country' => $country]);
            } else {
                $stmt = $pdo->query("SELECT * FROM covers ORDER BY scraped_at DESC");
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function getCountries() {
        return $this->execute(function($pdo) {
            $stmt = $pdo->query("SELECT DISTINCT country FROM covers ORDER BY country");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        });
    }

    public function addCover($data) {
        return $this->execute(function($pdo) use ($data) {
            $stmt = $pdo->prepare("
                INSERT INTO covers (
                    title, source, image_url, original_link, 
                    country, scraped_at
                ) VALUES (
                    :title, :source, :image_url, :original_link,
                    :country, :scraped_at
                )
            ");
            
            return $stmt->execute([
                ':title' => $data['title'],
                ':source' => $data['source'],
                ':image_url' => $data['image_url'],
                ':original_link' => $data['original_link'],
                ':country' => $data['country'],
                ':scraped_at' => isset($data['scraped_at']) ? $data['scraped_at'] : date('Y-m-d H:i:s')
            ]);
        });
    }

    public function updateCover($id, $data) {
        return $this->execute(function($pdo) use ($id, $data) {
            $updates = [];
            $params = [':id' => $id];
            
            foreach ($data as $key => $value) {
                if (in_array($key, ['title', 'source', 'image_url', 'original_link', 'country', 'scraped_at'])) {
                    $updates[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }
            
            if (empty($updates)) {
                return false;
            }
            
            $sql = "UPDATE covers SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute($params);
        });
    }

    public function deleteCover($id) {
        return $this->execute(function($pdo) use ($id) {
            $stmt = $pdo->prepare("DELETE FROM covers WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        });
    }
} 