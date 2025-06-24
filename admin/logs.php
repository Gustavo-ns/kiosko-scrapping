<?php
// Archivos de log disponibles
$log_files = [
    'process_all.log',
    'cron_process.log',
    'error_log',
    'process_portadas_error.log',
    'scrape_errors.log',
];

// Tipos de log para filtrar
$log_types = [
    'all' => 'Todos',
    'info' => 'INFO',
    'warning' => 'WARNING',
    'error' => 'ERROR',
    'fatal' => 'FATAL',
    'notice' => 'NOTICE',
];

// Directorio base (raíz del proyecto)
$base_dir = realpath(__DIR__ . '/../');

// Obtener log seleccionado
$selected_log = isset($_GET['log']) && in_array($_GET['log'], $log_files) ? $_GET['log'] : $log_files[0];
$log_path = $base_dir . DIRECTORY_SEPARATOR . $selected_log;

// Obtener tipo seleccionado
$selected_type = isset($_GET['type']) && array_key_exists($_GET['type'], $log_types) ? $_GET['type'] : 'all';

// Mensaje de estado
$status_msg = '';

// Limpiar log si se solicita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_log']) && in_array($_POST['log'], $log_files)) {
    $log_to_clear = $base_dir . DIRECTORY_SEPARATOR . $_POST['log'];
    if (file_exists($log_to_clear) && is_writable($log_to_clear)) {
        file_put_contents($log_to_clear, '');
        $status_msg = 'Log limpiado correctamente.';
        // Si se limpió otro log, actualizar el seleccionado
        $selected_log = $_POST['log'];
        $log_path = $log_to_clear;
    } else {
        $status_msg = 'No se pudo limpiar el log (no existe o no tiene permisos).';
    }
}

// Leer contenido del log
$log_content = '';
if (file_exists($log_path)) {
    $log_content = file_get_contents($log_path);
} else {
    $log_content = "Archivo de log no encontrado.";
}

// Filtrar por tipo
function filter_log_by_type($content, $type) {
    if ($type === 'all') return $content;
    $lines = explode("\n", $content);
    $filtered = array_filter($lines, function($line) use ($type) {
        return stripos($line, $type) !== false;
    });
    return implode("\n", $filtered);
}

// Resaltado simple: errores y advertencias
function highlight_log($text) {
    $text = preg_replace('/^(.*(error|fatal).*)$/im', '<span style="color: #fff; background: #d9534f; padding:2px 4px;">$1</span>', $text);
    $text = preg_replace('/^(.*warning.*)$/im', '<span style="color: #856404; background: #fff3cd; padding:2px 4px;">$1</span>', $text);
    $text = preg_replace('/^(.*notice.*)$/im', '<span style="color: #0c5460; background: #d1ecf1; padding:2px 4px;">$1</span>', $text);
    $text = preg_replace('/^(.*info.*)$/im', '<span style="color: #155724; background: #d4edda; padding:2px 4px;">$1</span>', $text);
    return $text;
}

// Aplicar filtro
$filtered_content = filter_log_by_type($log_content, $selected_type);

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Visor de Logs</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; margin: 0; padding: 0; }
        .container { max-width: 900px; margin: 30px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px #0001; padding: 24px; }
        h1 { font-size: 2em; margin-bottom: 0.5em; }
        select, button { font-size: 1em; padding: 4px 8px; }
        pre { background: #222; color: #eee; padding: 16px; border-radius: 6px; overflow-x: auto; font-size: 0.98em; line-height: 1.5; }
        .log-selector { margin-bottom: 18px; display: flex; gap: 10px; align-items: center; }
        .status-msg { margin-bottom: 12px; color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 8px 12px; border-radius: 4px; }
        .status-msg.error { color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
<div class="container">
    <h1>Visor de Logs</h1>
    <?php if ($status_msg): ?>
        <div class="status-msg<?= strpos($status_msg, 'no pudo') !== false ? ' error' : '' ?>"><?= htmlspecialchars($status_msg) ?></div>
    <?php endif; ?>
    <form method="get" class="log-selector" style="display:inline-block;">
        <label for="log">Selecciona un log:</label>
        <select name="log" id="log" onchange="this.form.submit()">
            <?php foreach ($log_files as $file): ?>
                <option value="<?= htmlspecialchars($file) ?>" <?= $file === $selected_log ? 'selected' : '' ?>><?= htmlspecialchars($file) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="type">Tipo:</label>
        <select name="type" id="type" onchange="this.form.submit()">
            <?php foreach ($log_types as $key => $label): ?>
                <option value="<?= htmlspecialchars($key) ?>" <?= $key === $selected_type ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Ver</button>
    </form>
    <form method="post" style="display:inline-block; margin-left:10px;">
        <input type="hidden" name="log" value="<?= htmlspecialchars($selected_log) ?>">
        <button type="submit" name="clear_log" onclick="return confirm('¿Seguro que quieres limpiar este log?')">Limpiar log</button>
    </form>
    <pre><?= $filtered_content ? highlight_log(htmlspecialchars($filtered_content)) : 'Sin contenido.' ?></pre>
</div>
</body>
</html> 