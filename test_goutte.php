<?php
require 'vendor/autoload.php';
use Goutte\Client;

$client = new Client();
$crawler = $client->request('GET', 'https://example.com');
echo $crawler->filter('h1')->text();
