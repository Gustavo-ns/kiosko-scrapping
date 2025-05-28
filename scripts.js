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
    updateURL(country);
    const apiUrl = country
      ? `api.php?country=${encodeURIComponent(country)}`
      : 'api.php';
      
    gallery.innerHTML = '<div class="loader" id="cargandoLoader"></div>';
    
    fetch(apiUrl)
      .then(res => res.json())
      .then(data => {
        gallery.innerHTML = '';
        data.forEach((item, index) => {
          const card = document.createElement('div');
          card.className = 'card';

          const link = document.createElement('a');
          link.href = item.source || '#';
          link.target = '_blank';

          const wrapper = document.createElement('div');
          wrapper.style.position = 'relative';

          const img = document.createElement('img');
          img.src = item.image_url;
          img.alt = item.title;
          img.width = 325;
          img.height = 500;
          
          // ⚡️ Establecer fetchpriority=high solo para la primera imagen
          if (index === 0) {
            img.setAttribute('fetchpriority', 'high');
          }

          img.onload = () => {
            img.classList.add('loaded');
            skeleton.remove();
          };

          const skeleton = document.createElement('div');
          skeleton.className = 'skeleton';

          wrapper.appendChild(img);
          wrapper.appendChild(skeleton);
          link.appendChild(wrapper);
          card.appendChild(link);

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
    // Opcional: llamar al scraper en el servidor
    fetch('scrape.php').then(() => loadCovers());
  });

  // Carga inicial
  loadCovers();
});
