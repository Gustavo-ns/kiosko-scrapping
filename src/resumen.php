<?php

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Cargar configuración de la base de datos
$cfg = require '../config.php';

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

// Cambiar visibilidad si se recibe el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['visualizar'])) {
  $id = (int)$_POST['id'];
  $newvisualizar = $_POST['visualizar'] == 1 ? 0 : 1;
  $stmt = $pdo->prepare("UPDATE pk_meltwater_resumen SET visualizar = :visualizar WHERE id = :id");
  $stmt->execute(['visualizar' => $newvisualizar, 'id' => $id]);
  // Redirigir para evitar reenvío de formulario
  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
}

// Obtener los datos
$stmt = $pdo->query("SELECT * FROM pk_meltwater_resumen ORDER BY published_date DESC");
$registros = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Resumen Meltwater</title>
  <style>
    body {
      font-family: 'Arial', sans-serif;
      background: #f4f4f4;
      margin: 0;
      padding: 2rem;
    }

    .actions-bar {
      margin-bottom: 1rem;
      display: flex;
      justify-content: flex-end;
      gap: 1rem;
    }

    .btn {
      padding: 0.5rem 1rem;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-weight: bold;
      transition: background-color 0.3s;
    }

    .btn-danger {
      background-color: #dc3545;
      color: white;
    }

    .btn-danger:hover {
      background-color: #c82333;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    th,
    td {
      padding: 1rem;
      text-align: left;
      border-bottom: 1px solid #ccc;
    }

    th {
      background: #222;
      color: #fff;
    }

    tr:hover {
      background: #f1f1f1;
    }

    .visualizar-link {
      color: #2196F3;
      text-decoration: none;
    }

    .visibility-form {
      display: inline;
    }

    .btn-toggle {
      background-color: #eee;
      border: 1px solid #ccc;
      padding: 0.5em 1em;
      cursor: pointer;
    }

    .editable {
      position: relative;
      cursor: pointer;
      padding: 5px;
      border: 1px solid transparent;
      transition: all 0.3s;
    }

    .editable:hover {
      background-color: #f8f9fa;
      border-color: #ddd;
    }

    .editable.editing {
      background-color: #fff;
      border-color: #007bff;
      box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }

    .editable input, .editable select {
      width: 100%;
      padding: 5px;
      border: none;
      background: transparent;
      font-family: inherit;
      font-size: inherit;
    }

    .editable input:focus, .editable select:focus {
      outline: none;
    }

    .save-indicator {
      position: absolute;
      top: 50%;
      right: 5px;
      transform: translateY(-50%);
      color: #28a745;
      display: none;
    }

    .save-indicator.show {
      display: block;
      animation: fadeOut 2s forwards;
    }

    @keyframes fadeOut {
      0% { opacity: 1; }
      70% { opacity: 1; }
      100% { opacity: 0; }
    }

    .error-message {
      color: #dc3545;
      font-size: 0.875rem;
      margin-top: 0.25rem;
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
  </style>
</head>

<body>

  <h1>Resumen Meltwater</h1>
  <div class="actions-bar">
    <a href="/importar" class="btn btn-primary">📥 Importar Enlaces</a>
    <button id="clearBtn" class="btn btn-danger">🗑️ Limpiar registros antiguos</button>
  </div>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Grupo</th>
        <th>País</th>
        <th>Título</th>
        <th>Reach</th>
        <th>Imagen URL</th>
        <th>Twitter ID</th>
        <th>Visualizar</th>
        <th>Fecha de Publicación</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($registros as $fila): ?>
        <tr data-id="<?= $fila['id'] ?>">
          <td><?= htmlspecialchars($fila['id']) ?></td>
          <td>
            <div class="editable" data-field="grupo">
              <span class="display-value"><?= htmlspecialchars($fila['grupo']) ?></span>
              <div class="autocomplete-wrapper">
                <input type="text" class="edit-input" style="display: none;" 
                       value="<?= htmlspecialchars($fila['grupo']) ?>"
                       autocomplete="off">
                <div class="autocomplete-results"></div>
              </div>
              <span class="save-indicator">✓</span>
            </div>
          </td>
          <td>
            <div class="editable" data-field="pais">
              <span class="display-value"><?= htmlspecialchars($fila['pais']) ?></span>
              <select class="edit-input" style="display: none;">
                <option value="">Seleccione un país</option>
                <option value="Argentina">Argentina</option>
                <option value="Bolivia">Bolivia</option>
                <option value="Brasil">Brasil</option>
                <option value="Chile">Chile</option>
                <option value="España">España</option>
                <option value="Paraguay">Paraguay</option>
                <option value="Uruguay">Uruguay</option>
              </select>
              <span class="save-indicator">✓</span>
            </div>
          </td>
          <td>
            <div class="editable" data-field="titulo">
              <span class="display-value"><?= htmlspecialchars($fila['titulo']) ?></span>
              <input type="text" class="edit-input" style="display: none;" value="<?= htmlspecialchars($fila['titulo']) ?>">
              <span class="save-indicator">✓</span>
            </div>
          </td>
          <td>
            <div class="editable" data-field="dereach">
              <span class="display-value"><?= htmlspecialchars($fila['dereach']) ?></span>
              <input type="number" class="edit-input" style="display: none;" value="<?= htmlspecialchars($fila['dereach']) ?>">
              <span class="save-indicator">✓</span>
            </div>
          </td>
          <td>
            <div class="editable" data-field="source">
              <span class="display-value">
                <?php if (!empty($fila['source'])): ?>
                  <img src="<?= htmlspecialchars($fila['source']) ?>" alt="Imagen" style="max-width:100px;max-height:60px;">
                <?php else: ?>
                  —
                <?php endif; ?>
              </span>
              <input type="text" class="edit-input" style="display: none;" value="<?= htmlspecialchars($fila['source']) ?>">
              <span class="save-indicator">✓</span>
            </div>
          </td>
          <td>
            <div class="editable" data-field="twitter_id">
              <span class="display-value"><?= htmlspecialchars($fila['twitter_id']) ?></span>
              <input type="text" class="edit-input" style="display: none;" value="<?= htmlspecialchars($fila['twitter_id']) ?>">
              <span class="save-indicator">✓</span>
            </div>
          </td>
          <td>
            <form method="POST" class="visibility-form">
              <input type="hidden" name="id" value="<?= $fila['id'] ?>">
              <input type="hidden" name="visualizar" value="<?= $fila['visualizar'] ?>">
              <input type="checkbox" class="visibility-toggle" data-id="<?= $fila['id'] ?>" <?= $fila['visualizar'] ? 'checked' : '' ?>>
            </form>
          </td>
          <td><?= htmlspecialchars($fila['published_date']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <script>
    document.querySelectorAll('.visibility-toggle').forEach(checkbox => {
      checkbox.addEventListener('change', function() {
        const id = this.dataset.id;
        const visualizar = this.checked ? 1 : 0;

        fetch('/api/update-visibility', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `id=${id}&visualizar=${visualizar}`
          })
          .then(response => response.json())
          .then(data => {
            if (!data.success) {
              alert('Error al actualizar: ' + (data.error || 'Desconocido'));
            }
          })
          .catch(err => {
            console.error('Error de red', err);
          });
      });
    });

    // Funcionalidad de edición inline
    document.querySelectorAll('.editable').forEach(editable => {
      const displayValue = editable.querySelector('.display-value');
      const editInput = editable.querySelector('.edit-input');
      const saveIndicator = editable.querySelector('.save-indicator');

      // Activar edición al hacer clic
      displayValue.addEventListener('click', () => {
        editable.classList.add('editing');
        displayValue.style.display = 'none';
        editInput.style.display = 'block';
        editInput.focus();

        // Si es un select, establecer el valor actual
        if (editInput.tagName === 'SELECT') {
          editInput.value = displayValue.textContent.trim();
        }
      });

      // Guardar al perder el foco o presionar Enter
      editInput.addEventListener('blur', saveChanges);
      editInput.addEventListener('keyup', e => {
        if (e.key === 'Enter') {
          editInput.blur();
        } else if (e.key === 'Escape') {
          cancelEdit();
        }
      });

      function cancelEdit() {
        editable.classList.remove('editing');
        displayValue.style.display = 'block';
        editInput.style.display = 'none';
      }

      function saveChanges() {
        const newValue = editInput.value.trim();
        const field = editable.dataset.field;
        const row = editable.closest('tr');
        const id = row.dataset.id;

        // Si el valor no ha cambiado, solo cancelar la edición
        if (newValue === displayValue.textContent.trim()) {
          cancelEdit();
          return;
        }

        fetch('/api/update-record', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `id=${id}&${field}=${encodeURIComponent(newValue)}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            if (field === 'source' && newValue) {
              displayValue.innerHTML = `<img src="${newValue}" alt="Imagen" style="max-width:100px;max-height:60px;">`;
            } else {
              displayValue.textContent = newValue;
            }
            
            saveIndicator.classList.add('show');
            setTimeout(() => {
              saveIndicator.classList.remove('show');
            }, 2000);
          } else {
            alert('Error al guardar: ' + (data.error || 'Error desconocido'));
          }
        })
        .catch(error => {
          alert('Error de red: ' + error);
        })
        .finally(() => {
          cancelEdit();
        });
      }
    });

    // Funcionalidad de autocompletado para grupos
    let gruposCache = null;

    async function getGrupos() {
      if (gruposCache) return gruposCache;

      try {
        const response = await fetch('/api/grupos');
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

    document.querySelectorAll('.editable[data-field="grupo"]').forEach(grupoEditable => {
      const input = grupoEditable.querySelector('.edit-input');
      const resultsContainer = grupoEditable.querySelector('.autocomplete-results');
      let selectedIndex = -1;

      input.addEventListener('input', async () => {
        const grupos = await getGrupos();
        const value = input.value.toLowerCase();
        
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
      input.addEventListener('keydown', (e) => {
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
              input.value = items[selectedIndex].textContent;
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
          input.value = item.textContent;
          resultsContainer.classList.remove('show');
          input.focus();
        }
      });

      // Cerrar sugerencias al hacer clic fuera
      document.addEventListener('click', (e) => {
        if (!grupoEditable.contains(e.target)) {
          resultsContainer.classList.remove('show');
        }
      });

      // Limpiar resultados al finalizar edición
      input.addEventListener('blur', () => {
        // Pequeño delay para permitir el clic en las sugerencias
        setTimeout(() => {
          resultsContainer.classList.remove('show');
        }, 200);
      });
    });
  </script>

  <script>
    document.getElementById('addForm').addEventListener('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(this);

      fetch('/api/add-record', {
          method: 'POST',
          body: new URLSearchParams(formData)
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            alert('Registro agregado correctamente');
            location.reload(); // Opcional: recargar para ver el nuevo registro
          } else {
            alert('Error al agregar: ' + (data.error || 'desconocido'));
          }
        })
        .catch(err => alert('Error de red: ' + err));
    });
  </script>

  <script>
    // Agregar funcionalidad al botón de limpieza
    document.getElementById('clearBtn').addEventListener('click', function() {
      if (confirm('¿Estás seguro de que deseas eliminar los registros antiguos (más de 24 horas) que no están marcados para visualizar?')) {
        fetch('/api/clear-records', {
          method: 'POST'
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            alert(data.message);
            location.reload();
          } else {
            alert('Error al limpiar registros: ' + (data.error || 'desconocido'));
          }
        })
        .catch(err => {
          console.error('Error:', err);
          alert('Error al limpiar registros');
        });
      }
    });
  </script>

</body>

</html>