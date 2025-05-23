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
Pass para VPS => w1F#riF>Tjw1F#riF>Tj
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
               'url' => 'https://es.kiosko.net/py/np/py_5dias.html',
               'selector'     => '.frontPageImage img',  // apunta directo al <img>
               'multiple'     => false,
           ],
            [
               'url' => 'https://www.adndigital.com.py/',
               'selector'     => '#text-html-widget-3 img',  // apunta directo al <img>
               'multiple'     => false,
           ],
            [
               'url' => 'https://www.cronica.com.py/',
               'selector'     => '#su_slider_682c0088f14d9 img',  // apunta directo al <img>
               'multiple'     => false,
           ],
            [
               'url' => 'https://independiente.com.py/19-05-25/',
               'selector'     => '#attachment_144509 a',
               'multiple'     => false,
                'followLinks' => [
                    'linkSelector' => null, // porque estás usando directamente el nodo 'a'
                    'attribute' => 'href',  // 👈 aquí extraés el enlace de la imagen grande
                ],
           ],           
            [
                'url' => 'https://independiente.com.py',
                'selector'     => '.viral_news_category_block-6',
                'multiple'     => true,
                'followLinks'  => [
                    'linkSelector'  => '#attachment_144509 a',
                    'attribute' => 'href',
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
            [
                'url' => 'https://www.extra.com.py/',
                'selector' => 'button[data-fancybox="fancybox-ver-tapa-impresa"]',
                'multiple' => false,
                'attribute' => 'data-src', // <-- atributo que quieres extraer
            ],
            [
                'url' => 'https://www.ip.gov.py/ip/',
                'use_xpath' => true,
                'xpath' => '//a[contains(@class,"tdm-image-box")]/@href',
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
            [
               'url' => 'https://www.elobservador.com.uy/',
               'selector'     => 'h3 + .news-article__figure.figure img.img-fluid',  // apunta directo al <img>
               'multiple'     => false,
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
            [
                'url' => 'https://eldeber.com.bo/',
                'selector' => '#bloque_213048 .field__item img',
                'multiple' => false,
            ],
            [
                'url' => 'https://larazon.bo/',
                'selector' => '#bloque5 .vc_single_image-img.entered',
                'transformImageUrl' => true,
            ],
            [
                'url' => 'https://www.lostiempos.com/',
                'selector' => '.pane-portada-impresa .views-field-field-portada-imagen a',
                'multiple' => false,
                'attribute' => 'href', // Obtiene la URL de alta resolución
                'imgSelectorInsideLink' => 'img', // Selector para extraer el src dentro del <a>
                'transformImageUrl' => function ($url) {
                    // Quitar el parámetro ?itok=... si existe
                    return preg_replace('/\?itok=.*$/', '', $url);
                }
            ],
            [
                'url' => 'https://www.eldiario.net/portal/',
                'selector' => '.tdm-inline-image-wrap a.td-modal-image',
                'multiple' => false,
                'attribute' => 'href', // El enlace apunta directamente a la imagen en alta resolución
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
