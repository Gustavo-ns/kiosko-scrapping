document.addEventListener('DOMContentLoaded', () => {
  const countrySelect = document.getElementById('countrySelect');
  const refreshBtn    = document.getElementById('refreshBtn');
  const gallery = document.getElementById('gallery');

  // Modal
  const imageModal = document.getElementById('imageModal');
  const modalImage = document.getElementById('modalImage');
  const closeModal = document.querySelector('#imageModal .close');

  if (imageModal && modalImage && closeModal) {
    closeModal.addEventListener('click', () => {
      imageModal.style.display = 'none';
      modalImage.src = '';
    });
  
    window.addEventListener('click', e => {
      if (e.target === imageModal) {
        imageModal.style.display = 'none';
        modalImage.src = '';
      }
    });
  
    function showModal(imageUrl) {
      const loader = document.getElementById('modalLoader');
      modalImage.style.display = 'none';
      loader.style.display = 'block';
      imageModal.style.display = 'flex';
    
      modalImage.onload = () => {
        loader.style.display = 'none';
        modalImage.style.display = 'block';
      };
    
      modalImage.src = imageUrl;
    }    
  }
  
  // Leer parámetro al cargar
  const params = new URLSearchParams(window.location.search);
  const initialCountry = params.get('country') || '';
  countrySelect.value = initialCountry;

  function updateURL(country) {
    const url = new URL(window.location);
    if (country) url.searchParams.set('country', country);
    else url.searchParams.delete('country');
    history.replaceState(null, '', url);
  }

  function loadCovers() {
    const country = countrySelect.value;
    const apiUrl = country ? `/api/covers?country=${encodeURIComponent(country)}` : '/api/covers';
    
    gallery.innerHTML = '<p>Cargando portadas...</p>';
    
    fetch(apiUrl)
      .then(response => response.json())
      .then(data => {
        gallery.innerHTML = '';
        data.forEach((item, index) => {
          const card = document.createElement('div');
          card.className = 'card';
          
          const img = document.createElement('img');
          img.src = item.image_url;
          img.alt = item.title;
          img.loading = index < 6 ? 'eager' : 'lazy';
          
          const a = document.createElement('a');
          a.href = item.original_link;
          a.target = '_blank';
          a.appendChild(img);
          card.appendChild(a);

          const info = document.createElement('div');
          info.className = 'info';

          const h3 = document.createElement('h3');
          h3.textContent = item.title;
          
          const small = document.createElement('small');

          // Extraer el dominio de la URL
          let displaySource = item.source;
          try {
            const urlOriginal = new URL(item.source.startsWith('http') ? item.source : `https://${item.source}`);
            displaySource = `${urlOriginal.hostname}${urlOriginal.pathname.split('/').slice(0, 2).join('/')}`;
          } catch (e) {
            console.warn('URL inválida:', item.source);
          }
          
          small.textContent = `${item.country} — ${displaySource}`;
          
          info.appendChild(h3);
          info.appendChild(small);
          card.appendChild(info);

          gallery.appendChild(card);
        });
      })
      .catch(err => {
        console.error('Error al cargar las portadas:', err);
        gallery.innerHTML = '<p>Error al cargar las portadas.</p>';
      });
  }

  countrySelect.addEventListener('change', loadCovers);
  refreshBtn.addEventListener('click', () => {
    gallery.innerHTML = '<p>Actualizando portadas...</p>';
    // Llamar al endpoint de actualización
    fetch('/update')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          loadCovers(); // Recargar las portadas después de actualizar
        } else {
          gallery.innerHTML = '<p>Error al actualizar: ' + (data.message || 'Error desconocido') + '</p>';
        }
      })
      .catch(err => {
        console.error('Error al actualizar:', err);
        gallery.innerHTML = '<p>Error al actualizar las portadas.</p>';
      });
  });

  // Carga inicial
  loadCovers();
});
