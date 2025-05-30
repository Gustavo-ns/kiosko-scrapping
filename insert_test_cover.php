<?php
// Insert test data to verify the new cover structure works

require_once 'config.php';

$config = require 'config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
        $config['db']['user'],
        $config['db']['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "Inserting test cover data...\n";
    
    // Create placeholder images in the covers directory
    $covers_dir = __DIR__ . '/images/covers';
    $thumbs_dir = $covers_dir . '/thumbnails';
    
    // Ensure directories exist
    if (!file_exists($covers_dir)) mkdir($covers_dir, 0755, true);
    if (!file_exists($thumbs_dir)) mkdir($thumbs_dir, 0755, true);
    
    // Create simple test images (1x1 pixel WebP)
    $webp_data = base64_decode('UklGRkYAAABXRUJQVlA4WAoAAAAQAAAAAAAAAAAAQUxQSAIAAAABBVBUOCAAEAAA8AcQAP4AAAD+AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
    
    $test_original = $covers_dir . '/test_original.webp';
    $test_thumb = $thumbs_dir . '/test_thumb.webp';
    
    file_put_contents($test_original, $webp_data);
    file_put_contents($test_thumb, $webp_data);
    
    echo "Created test images:\n";
    echo "- Original: $test_original\n";
    echo "- Thumbnail: $test_thumb\n";
    
    // Insert test data
    $stmt = $pdo->prepare("INSERT INTO covers (country, title, image_url, source, original_link, thumbnail_url, original_url, scraped_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE image_url = VALUES(image_url), thumbnail_url = VALUES(thumbnail_url), original_url = VALUES(original_url)");
    
    $stmt->execute([
        'Test Country',
        'Test Cover - New Structure',
        'images/covers/thumbnails/test_thumb.webp', // Main image (thumbnail)
        'http://example.com/test',
        'http://example.com/test-original.jpg',
        'images/covers/thumbnails/test_thumb.webp', // Thumbnail
        'images/covers/test_original.webp' // Original
    ]);
    
    echo "✅ Test cover data inserted successfully!\n";
    echo "You can now check the frontend to see the new structure in action.\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
