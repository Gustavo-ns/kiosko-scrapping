<?php
// test_lcp_optimization.php - Test LCP optimization for critical images

require_once 'config.php';

echo "<h1>🚀 LCP Optimization Test</h1>";

try {
    $cfg = require 'config.php';
    $pdo = new PDO(
        "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}",
        $cfg['db']['user'],
        $cfg['db']['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Get first 6 documents (critical images)
    $stmt = $pdo->query("
        SELECT 
            med.*,
            pk.*,
            'meltwater' as source_type
        FROM pk_melwater pk
        LEFT JOIN medios med ON pk.external_id = med.twitter_id
        WHERE med.visualizar = 1
        ORDER BY med.grupo, med.pais, med.dereach DESC
        LIMIT 6
    ");
    $critical_docs = $stmt->fetchAll();

    echo "<h2>Critical Images (First 6) - Should Load High Quality Directly</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Position</th><th>Title</th><th>Source Type</th><th>Image Strategy</th><th>Image URL</th>";
    echo "</tr>";

    $image_count = 0;
    foreach ($critical_docs as $doc) {
        $image_count++;
        $is_critical = $image_count <= 6;
        
        $title = htmlspecialchars($doc['title'] ?? 'No title');
        $source_type = $doc['source_type'];
        $content_image = $doc['content_image'] ?? '';
        $external_id = $doc['external_id'] ?? '';
        
        // Simulate the logic from index.php
        if ($content_image) {
            // Check if processed images exist
            $original_path = "images/melwater/{$external_id}_original.webp";
            $preview_path = "images/melwater/{$external_id}_preview.webp";
            
            if (file_exists($original_path)) {
                $final_image = $original_path;
                $preview_image = $preview_path;
            } else {
                $final_image = $content_image;
                $preview_image = $content_image;
            }
            
            if ($is_critical) {
                $img_src = $final_image; // Critical: High quality directly
                $strategy = "HIGH QUALITY (LCP optimized)";
                $bg_color = "#e8f5e8";
            } else {
                $img_src = $preview_image; // Non-critical: Preview first
                $strategy = "PROGRESSIVE (Preview → High Quality)";
                $bg_color = "#fff3cd";
            }
        } else {
            $img_src = "No image";
            $strategy = "No image available";
            $bg_color = "#f8d7da";
        }
        
        echo "<tr style='background: {$bg_color};'>";
        echo "<td><strong>{$image_count}</strong></td>";
        echo "<td>{$title}</td>";
        echo "<td>{$source_type}</td>";
        echo "<td><strong>{$strategy}</strong></td>";
        echo "<td style='font-family: monospace; font-size: 12px;'>" . htmlspecialchars($img_src) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";

    echo "<h2>Non-Critical Images (7+) - Should Use Progressive Loading</h2>";
    
    // Get documents 7-12 for testing
    $stmt = $pdo->query("
        SELECT 
            med.*,
            pk.*,
            'meltwater' as source_type
        FROM pk_melwater pk
        LEFT JOIN medios med ON pk.external_id = med.twitter_id
        WHERE med.visualizar = 1
        ORDER BY med.grupo, med.pais, med.dereach DESC
        LIMIT 6 OFFSET 6
    ");
    $non_critical_docs = $stmt->fetchAll();

    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Position</th><th>Title</th><th>Source Type</th><th>Image Strategy</th><th>Progressive Loading</th>";
    echo "</tr>";

    foreach ($non_critical_docs as $doc) {
        $image_count++;
        $is_critical = $image_count <= 6;
        
        $title = htmlspecialchars($doc['title'] ?? 'No title');
        $source_type = $doc['source_type'];
        $content_image = $doc['content_image'] ?? '';
        $external_id = $doc['external_id'] ?? '';
        
        if ($content_image) {
            $original_path = "images/melwater/{$external_id}_original.webp";
            $preview_path = "images/melwater/{$external_id}_preview.webp";
            
            if (file_exists($original_path)) {
                $final_image = $original_path;
                $preview_image = $preview_path;
                $has_progressive = true;
            } else {
                $final_image = $content_image;
                $preview_image = $content_image;
                $has_progressive = false;
            }
            
            $img_src = $preview_image; // Non-critical: Preview first
            $strategy = "PREVIEW FIRST";
            $progressive = $has_progressive ? "✅ Preview → High Quality" : "❌ No processed images";
            $bg_color = $has_progressive ? "#e8f4fd" : "#f8d7da";
        } else {
            $strategy = "No image available";
            $progressive = "❌ No image";
            $bg_color = "#f8d7da";
        }
        
        echo "<tr style='background: {$bg_color};'>";
        echo "<td><strong>{$image_count}</strong></td>";
        echo "<td>{$title}</td>";
        echo "<td>{$source_type}</td>";
        echo "<td><strong>{$strategy}</strong></td>";
        echo "<td>{$progressive}</td>";
        echo "</tr>";
    }
    
    echo "</table>";

    echo "<h2>🎯 Optimization Summary</h2>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>✅ What's Optimized:</h3>";
    echo "<ul>";
    echo "<li><strong>Critical Images (1-6):</strong> Load high-quality immediately for better LCP</li>";
    echo "<li><strong>Non-Critical Images (7+):</strong> Start with preview, upgrade to high-quality</li>";
    echo "<li><strong>fetchpriority='high':</strong> Applied only to critical images</li>";
    echo "<li><strong>Preload:</strong> First 3 images preloaded in head section</li>";
    echo "</ul>";
    echo "</div>";

    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>⚡ Expected Benefits:</h3>";
    echo "<ul>";
    echo "<li><strong>Faster LCP:</strong> Critical images load immediately without bandwidth competition</li>";
    echo "<li><strong>Better UX:</strong> Users see content quickly with progressive enhancement</li>";
    echo "<li><strong>Reduced CLS:</strong> Fixed aspect ratios prevent layout shifts</li>";
    echo "<li><strong>Bandwidth Efficiency:</strong> Non-critical images start small</li>";
    echo "</ul>";
    echo "</div>";

    echo "<h2>🧪 Live Test</h2>";
    echo "<p><a href='index.php' target='_blank' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Open Main Page</a></p>";
    echo "<p><small>Open browser DevTools → Network tab to see the loading strategy in action!</small></p>";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px;'>";
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
