<?php
// config.php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

return [
    'db' => [
        'host'    => 'localhost',
        'name'    => 'kiosko_scraper',
        'user'    => 'root',
        'pass'    => 'root',
        'charset' => 'utf8mb4',
    ],
    'sites' => [
        'argentina' => [
            [
                'url' => 'https://es.kiosko.net/ar/',
                'selector'     => '.thcover',
                'multiple'     => true,
                'followLinks'  => [
                    'linkSelector'  => 'a',
                    'imageSelector' => '#portada',
                ],
            ],
        ],
        'paraguay' => [
            [
                'url' => 'https://es.kiosko.net/py/',
                'selector'     => '.thcover',
                'multiple'     => true,
                'followLinks'  => [
                    'linkSelector'  => 'a',
                    'imageSelector' => '#portada',
                ],
            ],
            [
                'url'      => 'https://www.popular.com.py/',
                'selector' => '.portada img',
                'multiple' => false,
            ],
        ],
        'brasil' => [
            [
                'url' => 'https://es.kiosko.net/br/',
                'selector'     => '.thcover',
                'multiple'     => true,
                'followLinks'  => [
                    'linkSelector'  => 'a',
                    'imageSelector' => '#portada',
                ],
            ],
        ],
        'usa' => [
            [
                'url' => 'https://es.kiosko.net/us/',
                'selector'     => '.thcover',
                'multiple'     => true,
                'followLinks'  => [
                    'linkSelector'  => 'a',
                    'imageSelector' => '#portada',
                ],
            ],
        ],
        'uruguay' => [
            [
                'url' => 'https://es.kiosko.net/uy/',
                'selector'     => '.thcover',
                'multiple'     => true,
                'followLinks'  => [
                    'linkSelector'  => 'a',
                    'imageSelector' => '#portada',
                ],
            ],
        ],
        'chile' => [
            [
                'url' => 'https://es.kiosko.net/cl/',
                'selector'     => '.thcover',
                'multiple'     => true,
                'followLinks'  => [
                    'linkSelector'  => 'a',
                    'imageSelector' => '#portada',
                ],
            ],
        ],
        'colombia' => [
            [
                'url' => 'https://es.kiosko.net/co/',
                'selector'     => '.thcover',
                'multiple'     => true,
                'followLinks'  => [
                    'linkSelector'  => 'a',
                    'imageSelector' => '#portada',
                ],
            ],
        ],
        'ecuador' => [
            [
                'url' => 'https://es.kiosko.net/ec/',
                'selector'     => '.thcover',
                'multiple'     => true,
                'followLinks'  => [
                    'linkSelector'  => 'a',
                    'imageSelector' => '#portada',
                ],
            ],
        ],
        'peru' => [
            [
                'url' => 'https://es.kiosko.net/pe/',
                'selector'     => '.thcover',
                'multiple'     => true,
                'followLinks'  => [
                    'linkSelector'  => 'a',
                    'imageSelector' => '#portada',
                ],
            ],
        ],
        'venezuela' => [
            [
                'url' => 'https://es.kiosko.net/ve/',
                'selector'     => '.thcover',
                'multiple'     => true,
                'followLinks'  => [
                    'linkSelector'  => 'a',
                    'imageSelector' => '#portada',
                ],
            ],
        ],
        'bolivia' => [
            [
                'url' => 'https://es.kiosko.net/bo/',
                'selector'     => '.thcover',
                'multiple'     => true,
                'followLinks'  => [
                    'linkSelector'  => 'a',
                    'imageSelector' => '#portada',
                ],
            ],
        ],
        'mexico' => [
            [
                'url' => 'https://es.kiosko.net/mx/',
                'selector'     => '.thcover',
                'multiple'     => true,
                'followLinks'  => [
                    'linkSelector'  => 'a',
                    'imageSelector' => '#portada',
                ],
            ],
        ],
        'panama' => [
            [
                'url' => 'https://es.kiosko.net/pa/',
                'selector'     => '.thcover',
                'multiple'     => true,
                'followLinks'  => [
                    'linkSelector'  => 'a',
                    'imageSelector' => '#portada',
                ],
            ],
        ],
        'dominicanRepublic' => [
            [
                'url' => 'https://es.kiosko.net/do/',
                'selector'     => '.thcover',
                'multiple'     => true,
                'followLinks'  => [
                    'linkSelector'  => 'a',
                    'imageSelector' => '#portada',
                ],
            ],
        ],
    ],
];
