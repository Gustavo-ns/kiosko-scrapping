<?php
$title = 'Resumen de Noticias';
ob_start();
?>

<div class="resumen-page">
    <h1>Resumen de Noticias</h1>
    
    <div class="filters">
        <div class="filter-group">
            <label for="grupo">Grupo:</label>
            <select id="grupo" name="grupo">
                <option value="">Todos los grupos</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="pais">País:</label>
            <select id="pais" name="pais">
                <option value="">Todos los países</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="visualizar">Visualizar:</label>
            <select id="visualizar" name="visualizar">
                <option value="">Todos</option>
                <option value="1">Visible</option>
                <option value="0">No visible</option>
            </select>
        </div>
    </div>

    <div class="registros-grid">
        <?php foreach ($registros as $registro): ?>
            <div class="registro-card" data-id="<?= htmlspecialchars($registro['id']) ?>">
                <div class="registro-image">
                    <img src="<?= htmlspecialchars($registro['image_url']) ?>" 
                         alt="<?= htmlspecialchars($registro['title']) ?>"
                         loading="lazy">
                </div>
                <div class="registro-info">
                    <h3><?= htmlspecialchars($registro['title']) ?></h3>
                    <p class="grupo"><?= htmlspecialchars($registro['grupo']) ?></p>
                    <p class="pais"><?= htmlspecialchars($registro['pais']) ?></p>
                    <p class="fecha"><?= date('d/m/Y H:i', strtotime($registro['published_date'])) ?></p>
                    <div class="registro-actions">
                        <button class="btn-edit" onclick="editarRegistro(<?= $registro['id'] ?>)">
                            Editar
                        </button>
                        <label class="switch">
                            <input type="checkbox" 
                                   <?= $registro['visualizar'] ? 'checked' : '' ?>
                                   onchange="toggleVisibilidad(<?= $registro['id'] ?>, this.checked)">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="modal-editar" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Editar Registro</h2>
        <form id="form-editar">
            <input type="hidden" name="id" id="edit-id">
            <div class="form-group">
                <label for="edit-grupo">Grupo:</label>
                <input type="text" id="edit-grupo" name="grupo" required>
            </div>
            <div class="form-group">
                <label for="edit-pais">País:</label>
                <input type="text" id="edit-pais" name="pais" required>
            </div>
            <div class="form-group">
                <label for="edit-titulo">Título:</label>
                <input type="text" id="edit-titulo" name="titulo" required>
            </div>
            <div class="form-group">
                <label for="edit-source">Fuente:</label>
                <input type="text" id="edit-source" name="source" required>
            </div>
            <div class="form-group">
                <label for="edit-twitter">ID de Twitter:</label>
                <input type="text" id="edit-twitter" name="twitter_id">
            </div>
            <div class="form-group">
                <label for="edit-dereach">Dereach:</label>
                <input type="text" id="edit-dereach" name="dereach">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">Guardar</button>
                <button type="button" class="btn-secondary" onclick="cerrarModal()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    cargarGrupos();
    initializeFilters();
});

function cargarGrupos() {
    fetch('?route=resumen/grupos')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('grupo');
                data.grupos.forEach(grupo => {
                    const option = document.createElement('option');
                    option.value = grupo;
                    option.textContent = grupo;
                    select.appendChild(option);
                });
            }
        });
}

function initializeFilters() {
    const filters = document.querySelectorAll('.filters select');
    filters.forEach(filter => {
        filter.addEventListener('change', aplicarFiltros);
    });
}

function aplicarFiltros() {
    const grupo = document.getElementById('grupo').value;
    const pais = document.getElementById('pais').value;
    const visualizar = document.getElementById('visualizar').value;
    
    document.querySelectorAll('.registro-card').forEach(card => {
        let visible = true;
        
        if (grupo && card.querySelector('.grupo').textContent !== grupo) {
            visible = false;
        }
        if (pais && card.querySelector('.pais').textContent !== pais) {
            visible = false;
        }
        if (visualizar !== '') {
            const isVisible = card.querySelector('input[type="checkbox"]').checked;
            if (visualizar === '1' && !isVisible || visualizar === '0' && isVisible) {
                visible = false;
            }
        }
        
        card.style.display = visible ? 'block' : 'none';
    });
}

function editarRegistro(id) {
    const card = document.querySelector(`.registro-card[data-id="${id}"]`);
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-grupo').value = card.querySelector('.grupo').textContent;
    document.getElementById('edit-pais').value = card.querySelector('.pais').textContent;
    document.getElementById('edit-titulo').value = card.querySelector('h3').textContent;
    
    document.getElementById('modal-editar').style.display = 'block';
}

function cerrarModal() {
    document.getElementById('modal-editar').style.display = 'none';
}

document.getElementById('form-editar').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('?route=resumen/update', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error al actualizar: ' + data.error);
        }
    });
});

function toggleVisibilidad(id, estado) {
    fetch('?route=resumen/visibility', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id}&visualizar=${estado ? 1 : 0}`
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Error al actualizar la visibilidad: ' + data.error);
            // Revertir el cambio en el checkbox
            const checkbox = document.querySelector(`.registro-card[data-id="${id}"] input[type="checkbox"]`);
            checkbox.checked = !estado;
        }
    });
}

// Cerrar modal al hacer clic en la X o fuera del contenido
document.querySelector('.close').addEventListener('click', cerrarModal);
window.addEventListener('click', function(e) {
    if (e.target === document.getElementById('modal-editar')) {
        cerrarModal();
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?> 