<?php
$title = 'Importar Enlaces';
ob_start();
?>

<div class="import-page">
    <h1>Importar Enlaces</h1>
    
    <div class="import-form">
        <form id="form-import" action="?route=importar/process" method="POST">
            <div class="form-group">
                <label for="grupo">Grupo:</label>
                <input type="text" id="grupo" name="grupo" required
                       placeholder="Nombre del grupo">
            </div>
            
            <div class="form-group">
                <label for="pais">País:</label>
                <input type="text" id="pais" name="pais" required
                       placeholder="País de origen">
            </div>
            
            <div class="form-group">
                <label for="links">Enlaces:</label>
                <textarea id="links" name="links" required rows="10"
                          placeholder="Pega aquí los enlaces, uno por línea"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Importar Enlaces</button>
                <button type="reset" class="btn-secondary">Limpiar</button>
            </div>
        </form>
    </div>

    <div id="resultado" class="resultado" style="display: none;">
        <h2>Resultado de la Importación</h2>
        <div class="resultado-content">
            <p class="mensaje"></p>
            <div class="errores" style="display: none;">
                <h3>Errores encontrados:</h3>
                <ul class="lista-errores"></ul>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('form-import').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const resultado = document.getElementById('resultado');
        const mensaje = resultado.querySelector('.mensaje');
        const errores = resultado.querySelector('.errores');
        const listaErrores = resultado.querySelector('.lista-errores');
        
        resultado.style.display = 'block';
        mensaje.textContent = data.message;
        
        if (data.errors && data.errors.length > 0) {
            errores.style.display = 'block';
            listaErrores.innerHTML = '';
            data.errors.forEach(error => {
                const li = document.createElement('li');
                li.textContent = error;
                listaErrores.appendChild(li);
            });
        } else {
            errores.style.display = 'none';
        }
        
        if (data.success) {
            this.reset();
        }
    })
    .catch(error => {
        alert('Error al procesar la solicitud: ' + error.message);
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?> 