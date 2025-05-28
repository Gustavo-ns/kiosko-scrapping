<?php

return [
    'name' => getenv('APP_NAME', 'KioskoScraping'),
    'env' => getenv('APP_ENV', 'production'),
    'debug' => getenv('APP_DEBUG', false),
    
    'database' => [
        'host' => getenv('DB_HOST', 'localhost'),
        'name' => getenv('DB_NAME', 'kiosko_db'),
        'user' => getenv('DB_USER', 'root'),
        'pass' => getenv('DB_PASS', ''),
        'charset' => getenv('DB_CHARSET', 'utf8mb4'),
    ],
    
    'scraping' => [
        'interval' => getenv('SCRAPE_INTERVAL', 3600),
        'max_retries' => getenv('MAX_RETRIES', 3),
        'timeout' => getenv('TIMEOUT', 10),
    ],
    
    'images' => [
        'quality' => getenv('IMAGE_QUALITY', 85),
        'max_width' => getenv('MAX_IMAGE_WIDTH', 325),
        'max_height' => getenv('MAX_IMAGE_HEIGHT', 500),
        'format' => getenv('IMAGE_FORMAT', 'jpeg'),
        'storage_path' => __DIR__ . '/../public/assets/images',
    ],
    
    'paths' => [
        'base' => __DIR__ . '/..',
        'public' => __DIR__ . '/../public',
        'logs' => __DIR__ . '/../logs',
        'views' => __DIR__ . '/../src/Views',
        'cache' => __DIR__ . '/../storage/cache',
    ],
]; 