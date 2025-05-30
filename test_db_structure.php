<?php
// Simple test to verify the database migration worked

require_once 'config.php';

$config = require 'config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
        $config['db']['user'],
        $config['db']['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "Testing database structure...\n";
    
    // Check if the new columns exist
    $stmt = $pdo->query("DESCRIBE covers");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Columns in covers table:\n";
    foreach ($columns as $column) {
        echo "- $column\n";
    }
    
    // Check if there are any existing covers
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM covers");
    $result = $stmt->fetch();
    echo "\nTotal covers in database: " . $result['count'] . "\n";
    
    // Check a sample record to see the structure
    $stmt = $pdo->query("SELECT * FROM covers LIMIT 1");
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sample) {
        echo "\nSample cover record:\n";
        foreach ($sample as $key => $value) {
            echo "- $key: " . (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) . "\n";
        }
    } else {
        echo "\nNo covers found in database.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
