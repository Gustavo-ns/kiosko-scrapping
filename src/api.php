<?php
// api.php
header('Content-Type: application/json');
require 'config.php';
$cfg = require 'config.php';

$pdo = new PDO(
    "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}",
    $cfg['db']['user'],
    $cfg['db']['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$country = $_GET['country'] ?? null;
if ($country) {
    $stmt = $pdo->prepare("SELECT * FROM covers WHERE country = :c ORDER BY scraped_at DESC");
    $stmt->execute([':c' => $country]);
} else {
    $stmt = $pdo->query("SELECT * FROM covers ORDER BY scraped_at DESC");
}
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data);
