<?php

$router->get('/', 'HomeController@index');
$router->get('/scrape', 'ScraperController@scrape');
$router->get('/update', 'MeltwaterController@update'); 