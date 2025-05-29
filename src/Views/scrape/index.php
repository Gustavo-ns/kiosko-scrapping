<?php
$title = 'Ejecutar Scraping';
ob_start();
?>

<div class="scrape-page">
    <h1>Ejecutar Scraping</h1>
    
    <div class="scrape-status">
        <div class="status-card">
            <h3>Estado del Proceso</h3>
            <p>Sitios procesados: <span class="processed"><?= $processed ?></span> de <span class="total"><?= $total ?></span></p>
            <div class="progress-bar">
                <div class="progress" style="width: <?= ($processed / $total) * 100 ?>%"></div>
            </div>
        </div>
        
        <div class="actions">
            <button id="btn-scrape" class="btn-primary">
                Iniciar Scraping
            </button>
            <button id="btn-stop" class="btn-secondary" style="display: none;">
                Detener Proceso
            </button>
        </div>
    </div>
    
    <div class="log-container">
        <h3>Registro de Actividad</h3>
        <div id="log-output" class="log-output"></div>
    </div>
</div>

<script>
let isRunning = false;
let processId = null;

document.getElementById('btn-scrape').addEventListener('click', function() {
    if (isRunning) return;
    
    isRunning = true;
    this.disabled = true;
    document.getElementById('btn-stop').style.display = 'inline-block';
    document.getElementById('log-output').innerHTML = '';
    
    iniciarScraping();
});

document.getElementById('btn-stop').addEventListener('click', function() {
    if (!isRunning) return;
    
    detenerScraping();
});

function iniciarScraping() {
    fetch('/api/scrape/start', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            processId = data.processId;
            monitorearProceso();
        } else {
            mostrarError('Error al iniciar el proceso: ' + data.error);
            resetearEstado();
        }
    })
    .catch(error => {
        mostrarError('Error de conexión: ' + error.message);
        resetearEstado();
    });
}

function monitorearProceso() {
    if (!isRunning) return;
    
    fetch(`/api/scrape/status?processId=${processId}`)
        .then(response => response.json())
        .then(data => {
            actualizarProgreso(data);
            
            if (data.completed) {
                mostrarMensaje('Proceso completado exitosamente');
                resetearEstado();
            } else if (isRunning) {
                setTimeout(monitorearProceso, 1000);
            }
        })
        .catch(error => {
            mostrarError('Error al monitorear el proceso: ' + error.message);
            resetearEstado();
        });
}

function detenerScraping() {
    fetch(`/api/scrape/stop?processId=${processId}`, {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarMensaje('Proceso detenido por el usuario');
        } else {
            mostrarError('Error al detener el proceso: ' + data.error);
        }
        resetearEstado();
    })
    .catch(error => {
        mostrarError('Error de conexión: ' + error.message);
        resetearEstado();
    });
}

function actualizarProgreso(data) {
    const processed = document.querySelector('.processed');
    const progressBar = document.querySelector('.progress');
    
    processed.textContent = data.processed;
    progressBar.style.width = `${(data.processed / data.total) * 100}%`;
    
    if (data.log) {
        const logOutput = document.getElementById('log-output');
        data.log.forEach(entry => {
            const p = document.createElement('p');
            p.textContent = entry;
            logOutput.appendChild(p);
            logOutput.scrollTop = logOutput.scrollHeight;
        });
    }
}

function mostrarMensaje(mensaje) {
    const logOutput = document.getElementById('log-output');
    const p = document.createElement('p');
    p.textContent = mensaje;
    p.className = 'success';
    logOutput.appendChild(p);
    logOutput.scrollTop = logOutput.scrollHeight;
}

function mostrarError(mensaje) {
    const logOutput = document.getElementById('log-output');
    const p = document.createElement('p');
    p.textContent = mensaje;
    p.className = 'error';
    logOutput.appendChild(p);
    logOutput.scrollTop = logOutput.scrollHeight;
}

function resetearEstado() {
    isRunning = false;
    processId = null;
    document.getElementById('btn-scrape').disabled = false;
    document.getElementById('btn-stop').style.display = 'none';
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?> 