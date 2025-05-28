// Función para mostrar alertas
function showAlert(message, type = 'success') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    const container = document.querySelector('.container');
    container.insertBefore(alert, container.firstChild);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// Función para formatear fechas
function formatDate(date) {
    return new Date(date).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Función para validar formularios
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('error');
            
            const errorMessage = document.createElement('small');
            errorMessage.className = 'error-message';
            errorMessage.textContent = 'Este campo es requerido';
            
            if (!field.nextElementSibling?.classList.contains('error-message')) {
                field.parentNode.insertBefore(errorMessage, field.nextSibling);
            }
        } else {
            field.classList.remove('error');
            const errorMessage = field.nextElementSibling;
            if (errorMessage?.classList.contains('error-message')) {
                errorMessage.remove();
            }
        }
    });
    
    return isValid;
}

// Función para manejar errores de fetch
function handleFetchError(error) {
    console.error('Error:', error);
    showAlert(error.message || 'Ha ocurrido un error', 'error');
}

// Función para serializar formularios
function serializeForm(form) {
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    return data;
}

// Función para hacer peticiones AJAX
async function fetchData(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Ha ocurrido un error');
        }
        
        return data;
    } catch (error) {
        handleFetchError(error);
        throw error;
    }
}

// Función para manejar modales
function initModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return null;
    
    const close = modal.querySelector('.close');
    
    function openModal() {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
    
    if (close) {
        close.addEventListener('click', closeModal);
    }
    
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    return {
        open: openModal,
        close: closeModal,
        modal
    };
}

// Función para manejar la paginación
function initPagination(container, itemsPerPage = 10) {
    const items = container.children;
    const totalPages = Math.ceil(items.length / itemsPerPage);
    let currentPage = 1;
    
    function showPage(page) {
        const start = (page - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        
        Array.from(items).forEach((item, index) => {
            item.style.display = index >= start && index < end ? '' : 'none';
        });
    }
    
    function createPagination() {
        if (totalPages <= 1) return;
        
        const pagination = document.createElement('div');
        pagination.className = 'pagination';
        
        for (let i = 1; i <= totalPages; i++) {
            const button = document.createElement('button');
            button.textContent = i;
            button.className = i === currentPage ? 'active' : '';
            button.addEventListener('click', () => {
                currentPage = i;
                showPage(i);
                updateButtons();
            });
            pagination.appendChild(button);
        }
        
        container.parentNode.insertBefore(pagination, container.nextSibling);
    }
    
    function updateButtons() {
        const buttons = document.querySelectorAll('.pagination button');
        buttons.forEach((button, index) => {
            button.className = index + 1 === currentPage ? 'active' : '';
        });
    }
    
    showPage(1);
    createPagination();
}

// Función para manejar filtros
function initFilters(container, filters) {
    const items = container.children;
    
    function applyFilters() {
        Array.from(items).forEach(item => {
            let visible = true;
            
            for (const [key, value] of Object.entries(filters)) {
                const filterValue = value();
                if (filterValue) {
                    const itemValue = item.dataset[key];
                    if (itemValue !== filterValue) {
                        visible = false;
                        break;
                    }
                }
            }
            
            item.style.display = visible ? '' : 'none';
        });
    }
    
    // Agregar event listeners a los filtros
    Object.keys(filters).forEach(key => {
        const element = document.querySelector(`[data-filter="${key}"]`);
        if (element) {
            element.addEventListener('change', applyFilters);
        }
    });
    
    return {
        apply: applyFilters,
        reset: () => {
            Object.keys(filters).forEach(key => {
                const element = document.querySelector(`[data-filter="${key}"]`);
                if (element) {
                    element.value = '';
                }
            });
            applyFilters();
        }
    };
}

// Función para manejar el scroll infinito
function initInfiniteScroll(container, loadMore, options = {}) {
    const {
        threshold = 100,
        debounce = 200
    } = options;
    
    let loading = false;
    let timer;
    
    async function checkScroll() {
        if (loading) return;
        
        const containerHeight = container.offsetHeight;
        const scrollPosition = window.innerHeight + window.pageYOffset;
        const scrollHeight = document.documentElement.scrollHeight;
        
        if (scrollHeight - scrollPosition <= threshold) {
            loading = true;
            
            try {
                await loadMore();
            } catch (error) {
                console.error('Error loading more items:', error);
            }
            
            loading = false;
        }
    }
    
    function handleScroll() {
        clearTimeout(timer);
        timer = setTimeout(checkScroll, debounce);
    }
    
    window.addEventListener('scroll', handleScroll);
    
    return {
        destroy: () => window.removeEventListener('scroll', handleScroll)
    };
}

// Exportar funciones
window.app = {
    showAlert,
    formatDate,
    validateForm,
    handleFetchError,
    serializeForm,
    fetchData,
    initModal,
    initPagination,
    initFilters,
    initInfiniteScroll
}; 