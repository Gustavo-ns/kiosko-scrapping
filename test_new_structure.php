<?php
// Test script for the new organized image structure

require_once 'config.php';
require_once 'download_image.php';

// Test the saveImageLocally function with a sample image
$testImageUrl = 'https://kiosko.net/cl/np/cl_tercera.html'; // This should have images
$country = 'Test';
$alt = 'Test Image';

echo "Testing new image structure...\n";

// Test if the functions are available
if (function_exists('saveImageLocally')) {
    echo "✅ saveImageLocally function found\n";
} else {
    echo "❌ saveImageLocally function not found\n";
}

if (function_exists('convertToWebP')) {
    echo "✅ convertToWebP function found\n";
} else {
    echo "❌ convertToWebP function not found\n";
}

// Check if WebP is supported
if (function_exists('imagewebp')) {
    echo "✅ WebP support available\n";
} else {
    echo "❌ WebP support not available\n";
}

// Test directory creation
$upload_dir = __DIR__ . '/images/covers';
$thumb_dir = $upload_dir . '/thumbnails';

echo "Creating directories...\n";
foreach ([$upload_dir, $thumb_dir] as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Created directory: $dir\n";
        } else {
            echo "❌ Failed to create directory: $dir\n";
        }
    } else {
        echo "✅ Directory already exists: $dir\n";
    }
}

// Test with a simple image URL (placeholder)
$testImage = 'https://via.placeholder.com/800x1200/0066cc/ffffff?text=Test+Cover';
echo "\nTesting image processing with placeholder image...\n";
echo "URL: $testImage\n";

$result = saveImageLocally($testImage, $country, $alt);

if ($result) {
    if (is_array($result)) {
        echo "✅ Image processing successful!\n";
        echo "Thumbnail: " . $result['thumbnail'] . "\n";
        echo "Original: " . $result['original'] . "\n";
        
        // Check if files actually exist
        $thumbnail_file = __DIR__ . '/' . $result['thumbnail'];
        $original_file = __DIR__ . '/' . $result['original'];
        
        if (file_exists($thumbnail_file)) {
            echo "✅ Thumbnail file exists: " . filesize($thumbnail_file) . " bytes\n";
        } else {
            echo "❌ Thumbnail file not found: $thumbnail_file\n";
        }
        
        if (file_exists($original_file)) {
            echo "✅ Original file exists: " . filesize($original_file) . " bytes\n";
        } else {
            echo "❌ Original file not found: $original_file\n";
        }
    } else {
        echo "⚠️ Function returned string instead of array: $result\n";
    }
} else {
    echo "❌ Image processing failed\n";
}

echo "\nTest completed.\n";
?>
