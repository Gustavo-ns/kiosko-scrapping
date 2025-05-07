document.addEventListener('DOMContentLoaded', () => {
  const countrySelect = document.getElementById('countrySelect');
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
  
  function loadCovers() {
    const country = countrySelect.value;
    const url = country ? `api.php?country=${encodeURIComponent(country)}` : 'api.php';

    fetch(url)
      .then(response => response.json())
      .then(data => {
        gallery.innerHTML = '';

        data.forEach(item => {
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
          img.onload = () => {
            img.classList.add('loaded');
            skeleton.remove();
          };

          const skeleton = document.createElement('div');
          skeleton.className = 'skeleton';

          // Agregar lupa
          const zoomIcon = document.createElement('div');
          zoomIcon.className = 'zoom-icon';
          zoomIcon.innerHTML = '🔍';
          zoomIcon.title = 'Ver imagen original';
          zoomIcon.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            showModal(item.original_link || item.image_url);
          });

          wrapper.appendChild(img);
          wrapper.appendChild(skeleton);
          wrapper.appendChild(zoomIcon);
          link.appendChild(wrapper);
          card.appendChild(link);

          const info = document.createElement('div');
          info.className = 'info';

          const h3 = document.createElement('h3');
          h3.textContent = item.title;

          //const small = document.createElement('small');
          //small.textContent = `${item.country} — ${item.source}`;
          
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

  loadCovers();
  countrySelect.addEventListener('change', loadCovers);
});
