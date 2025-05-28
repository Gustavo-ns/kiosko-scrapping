<?php
// add_record.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'grupo' => isset($_POST['grupo']) ? $_POST['grupo'] : '',
        'pais' => isset($_POST['pais']) ? $_POST['pais'] : '',
        'titulo' => isset($_POST['titulo']) ? $_POST['titulo'] : '',
        'dereach' => isset($_POST['dereach']) ? $_POST['dereach'] : 0,
        'source' => isset($_POST['source']) ? $_POST['source'] : '',
        'twitter_id' => isset($_POST['twitter_id']) ? $_POST['twitter_id'] : '',
    ];
    
    // Cargar configuración de la base de datos
    $cfg = require 'config.php';

    try {
        $pdo = new PDO(
            "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}",
            $cfg['db']['user'],
            $cfg['db']['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        $stmt = $pdo->prepare("
            INSERT INTO pk_meltwater_resumen 
            (grupo, pais, titulo, dereach, source, twitter_id, visualizar) 
            VALUES 
            (:grupo, :pais, :titulo, :dereach, :source, :twitter_id, 0)
        ");

        $stmt->execute($data);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
