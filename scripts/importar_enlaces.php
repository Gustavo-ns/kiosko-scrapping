<?php
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
} catch (\PDOException $e) {
    exit('Error de conexión: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importar Enlaces - Resumen Meltwater</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 2rem;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        h1 {
            color: #333;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #555;
        }

        select, textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        textarea {
            min-height: 200px;
            font-family: monospace;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1rem;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
            margin-left: 1rem;
        }

        .btn-secondary:hover {
            background-color: #545b62;
        }

        .result {
            margin-top: 2rem;
            padding: 1rem;
            border-radius: 4px;
            display: none;
        }

        .result.success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .result.error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .nav-links {
            margin-bottom: 2rem;
        }

        .nav-links a {
            color: #007bff;
            text-decoration: none;
            margin-right: 1rem;
        }

        .nav-links a:hover {
            text-decoration: underline;
        }

        .autocomplete-wrapper {
            position: relative;
        }

        .autocomplete-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1000;
            display: none;
        }

        .autocomplete-results.show {
            display: block;
        }

        .autocomplete-item {
            padding: 8px 12px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .autocomplete-item:hover {
            background-color: #f8f9fa;
        }

        .autocomplete-item.selected {
            background-color: #e9ecef;
        }

        .grupo-input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-links">
            <a href="resumen.php">← Volver a Resumen</a>
        </div>

        <h1>Importar Enlaces</h1>
        
        <form id="importForm">
            <div class="form-group">
                <label for="grupo">Grupo:</label>
                <div class="autocomplete-wrapper">
                    <input type="text" name="grupo" id="grupo" class="grupo-input" required autocomplete="off">
                    <div class="autocomplete-results"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="pais">País:</label>
                <select name="pais" id="pais" required>
                    <option value="">Seleccione un país</option>
                    <option value="Argentina">Argentina</option>
                    <option value="Bolivia">Bolivia</option>
                    <option value="Brasil">Brasil</option>
                    <option value="Chile">Chile</option>
                    <option value="España">España</option>
                    <option value="Paraguay">Paraguay</option>
                    <option value="Uruguay">Uruguay</option>
                </select>
            </div>

            <div class="form-group">
                <label for="links">Enlaces (uno por línea):</label>
                <textarea name="links" id="links" required 
                          placeholder="https://ejemplo1.com&#10;https://ejemplo2.com&#10;https://ejemplo3.com"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Importar Enlaces</button>
            <button type="reset" class="btn btn-secondary">Limpiar</button>
        </form>

        <div id="result" class="result"></div>
    </div>

    <script>
        // Funcionalidad de autocompletado para grupos
        let gruposCache = null;

        async function getGrupos() {
            if (gruposCache) return gruposCache;

            try {
                const response = await fetch('get_grupos.php');
                const data = await response.json();
                if (data.success) {
                    gruposCache = data.grupos;
                    return gruposCache;
                }
                return [];
            } catch (error) {
                console.error('Error al obtener grupos:', error);
                return [];
            }
        }

        const grupoInput = document.getElementById('grupo');
        const resultsContainer = document.querySelector('.autocomplete-results');
        let selectedIndex = -1;

        grupoInput.addEventListener('input', async () => {
            const grupos = await getGrupos();
            const value = grupoInput.value.toLowerCase();
            
            // Filtrar grupos que coincidan con el input
            const matches = grupos.filter(grupo => 
                grupo.toLowerCase().includes(value)
            );

            // Mostrar resultados
            resultsContainer.innerHTML = matches
                .map(grupo => `<div class="autocomplete-item">${grupo}</div>`)
                .join('');

            if (matches.length > 0) {
                resultsContainer.classList.add('show');
            } else {
                resultsContainer.classList.remove('show');
            }

            selectedIndex = -1;
        });

        // Manejar navegación con teclado
        grupoInput.addEventListener('keydown', (e) => {
            const items = resultsContainer.querySelectorAll('.autocomplete-item');
            
            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                    updateSelection(items);
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    updateSelection(items);
                    break;
                case 'Enter':
                    if (selectedIndex >= 0 && items[selectedIndex]) {
                        e.preventDefault();
                        grupoInput.value = items[selectedIndex].textContent;
                        resultsContainer.classList.remove('show');
                    }
                    break;
                case 'Escape':
                    resultsContainer.classList.remove('show');
                    break;
            }
        });

        function updateSelection(items) {
            items.forEach((item, index) => {
                if (index === selectedIndex) {
                    item.classList.add('selected');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('selected');
                }
            });
        }

        // Manejar clic en sugerencias
        resultsContainer.addEventListener('click', (e) => {
            const item = e.target.closest('.autocomplete-item');
            if (item) {
                grupoInput.value = item.textContent;
                resultsContainer.classList.remove('show');
                grupoInput.focus();
            }
        });

        // Cerrar sugerencias al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!grupoInput.parentElement.contains(e.target)) {
                resultsContainer.classList.remove('show');
            }
        });

        // Limpiar resultados al finalizar edición
        grupoInput.addEventListener('blur', () => {
            // Pequeño delay para permitir el clic en las sugerencias
            setTimeout(() => {
                resultsContainer.classList.remove('show');
            }, 200);
        });

        document.getElementById('importForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const resultDiv = document.getElementById('result');

            // Validar que haya al menos un enlace
            const links = formData.get('links').trim();
            if (!links) {
                resultDiv.className = 'result error';
                resultDiv.style.display = 'block';
                resultDiv.textContent = 'Por favor, ingrese al menos un enlace';
                return;
            }

            fetch('process_bulk_links.php', {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(res => res.json())
            .then(data => {
                resultDiv.style.display = 'block';
                if (data.success) {
                    resultDiv.className = 'result success';
                    let message = data.message;
                    if (data.errors && data.errors.length > 0) {
                        message += '\n\nErrores encontrados:\n' + data.errors.join('\n');
                    }
                    resultDiv.textContent = message;
                    
                    // Limpiar el formulario si todo fue exitoso y no hubo errores
                    if (!data.errors || data.errors.length === 0) {
                        e.target.reset();
                    }
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.textContent = 'Error: ' + (data.error || 'Error desconocido');
                }
            })
            .catch(err => {
                resultDiv.className = 'result error';
                resultDiv.style.display = 'block';
                resultDiv.textContent = 'Error de red: ' + err.message;
            });
        });
    </script>
</body>
</html> 