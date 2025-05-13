<?php
// config.php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

/* PRODUCTION */
/* 
        'host'    => 'localhost',
        'name'    => 'u735862410_kiosko',
        'user'    => 'u735862410_kiosko',
        'pass'    => 'w1F#riF>Tj',
        'charset' => 'utf8mb4',
*/

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
                    'linkImgSelector' => '.frontPageImage a',
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
                    'linkImgSelector' => '.frontPageImage a',
                    'imageSelector' => '#portada',
                ],
            ],
            [
                'url' => 'https://www.popular.com.py/',
                'selector' => '.portada a',
                'multiple' => false,
                'followLinks' => [
                    'linkSelector' => null, // porque estás usando directamente el nodo 'a'
                    'attribute' => 'href',  // 👈 aquí extraés el enlace de la imagen grande
                ],
            ],
            [
                'url' => 'https://www.ultimahora.com/',
                'selector' => 'bsp-page-promo-modal button[data-fancybox="fancybox-tapa"]',
                'multiple' => false,
                'followLinks' => [
                    'linkSelector' => null,
                    'imageSelector' => null,
                    'attribute' => 'data-src',
                ],
            ],
            [
                'url' => 'https://www.abc.com.py/edicion-impresa/',
                'custom_extractor' => 'extractHiresFromFusionScript',
            ],            
        ],
        'brasil' => [
            [
                'url' => 'https://es.kiosko.net/br/',
                'selector'     => '.thcover',
                'multiple'     => true,
                'followLinks'  => [
                    'linkSelector'  => 'a',
                    'linkImgSelector' => '.frontPageImage a',
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
                    'linkImgSelector' => '.frontPageImage a',
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
                    'linkImgSelector' => '.frontPageImage a',
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
                    'linkImgSelector' => '.frontPageImage a',
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
                    'linkImgSelector' => '.frontPageImage a',
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
                    'linkImgSelector' => '.frontPageImage a',
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
                    'linkImgSelector' => '.frontPageImage a',
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
                    'linkImgSelector' => '.frontPageImage a',
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
                    'linkImgSelector' => '.frontPageImage a',
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
                    'linkImgSelector' => '.frontPageImage a',
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
                    'linkImgSelector' => '.frontPageImage a',
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
                    'linkImgSelector' => '.frontPageImage a',
                    'imageSelector' => '#portada',
                ],
            ],
        ],
    ],
];
