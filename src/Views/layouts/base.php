<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Kiosko Scraping' ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>    <nav class="main-nav">
        <div class="nav-container">
            <a href="/home" class="nav-logo">Kiosko Scraping</a>
            <div class="nav-links">
                <a href="/resumen">Resumen</a>
                <a href="/importar">Importar Enlaces</a>
                <a href="/scrape">Ejecutar Scraping</a>
            </div>
        </div>
    </nav>

    <main class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </main>

    <footer class="main-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Kiosko Scraping. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="/assets/js/scripts.js"></script>
</body>
</html> 