<?php
require_once dirname(dirname(__DIR__)) . '/app/config/DatabaseConnection.php';

try {
    $db = DatabaseConnection::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portadas de Periódicos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .filters {
            margin-bottom: 20px;
            padding: 15px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .covers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .cover-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .cover-card img {
            width: 100%;
            height: 350px;
            object-fit: cover;
        }
        .cover-info {
            padding: 15px;
        }
        .cover-info h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .cover-info p {
            margin: 5px 0;
            color: #666;
        }
        .menu {
            margin-bottom: 20px;
        }
        .menu a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #0066cc;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
        }
        .menu a:hover {
            background-color: #0052a3;
        }
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        .pagination a {
            display: inline-block;
            padding: 5px 10px;
            margin: 0 5px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-decoration: none;
            color: #333;
        }
        .pagination a.active {
            background-color: #0066cc;
            color: white;
            border-color: #0066cc;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="menu">
            <a href="/kiosko-scrapping/public/">Volver a Portadas</a>
        </div>

        <h1>Todas las Portadas</h1>

        <div class="filters">
            <form method="GET">
                <select name="country">
                    <option value="">Todos los países</option>
                    <?php
                    $stmt = $pdo->query("SELECT DISTINCT country FROM covers ORDER BY country");
                    while ($row = $stmt->fetch()) {
                        $selected = isset($_GET['country']) && $_GET['country'] === $row['country'] ? 'selected' : '';
                        echo "<option value='{$row['country']}' {$selected}>{$row['country']}</option>";
                    }
                    ?>
                </select>
                <button type="submit">Filtrar</button>
            </form>
        </div>

        <div class="covers-grid">
            <?php
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = 12;
            $offset = ($page - 1) * $perPage;

            $where = '';
            $params = [];
            if (!empty($_GET['country'])) {
                $where = 'WHERE country = :country';
                $params[':country'] = $_GET['country'];
            }

            // Obtener total de registros
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM covers $where");
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();
            $totalPages = ceil($total / $perPage);

            // Obtener portadas
            $stmt = $pdo->prepare("
                SELECT * FROM covers 
                $where 
                ORDER BY scraped_at DESC 
                LIMIT :offset, :perPage
            ");
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            while ($cover = $stmt->fetch()) {
                echo "<div class='cover-card'>";
                if (!empty($cover['local_path']) && file_exists($cover['local_path'])) {
                    echo "<img test src='./storage/images/{$cover['local_path']}' alt='{$cover['title']}'>";
                } else {
                    echo "<img src='{$cover['image_url']}' alt='{$cover['title']}'>";
                }
                echo "<div class='cover-info'>";
                echo "<h3>{$cover['title']}</h3>";
                echo "<p>País: {$cover['country']}</p>";
                echo "<p>Fecha: " . date('d/m/Y H:i', strtotime($cover['scraped_at'])) . "</p>";
                echo "</div></div>";
            }
            ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $queryParams = $_GET;
            for ($i = 1; $i <= $totalPages; $i++) {
                $queryParams['page'] = $i;
                $queryString = http_build_query($queryParams);
                $activeClass = $page === $i ? 'active' : '';
                echo "<a href='?{$queryString}' class='{$activeClass}'>{$i}</a>";
            }
            ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html> 