window.addEventListener('DOMContentLoaded', () => {
  const loadBtn = document.getElementById('loadBtn');
  const countrySelect = document.getElementById('countrySelect');
  const gallery = document.getElementById('gallery');

  function cargarPortadas() {
    const country = countrySelect.value;
    const url = country ? `api.php?country=${encodeURIComponent(country)}` : 'api.php';

    fetch(url)
      .then(response => response.json())
      .then(data => {
        gallery.innerHTML = '';
        data.forEach(item => {
          const card = document.createElement('div');
          card.className = 'card';
          card.innerHTML = `
            <a href="${item.original_link || '#'}" target="_blank">
              <img src="${item.image_url}" alt="${item.title}">
            </a>
            <div class="info">
              <h3>${item.title}</h3>
              <small>${item.country} — ${item.source}</small>
            </div>
          `;
          gallery.appendChild(card);
        });
      })
      .catch(err => {
        console.error('Error al cargar las portadas:', err);
        gallery.innerHTML = '<p>Error al cargar las portadas. Revisa la consola para más detalles.</p>';
      });
  }

  loadBtn.addEventListener('click', cargarPortadas);
  countrySelect.addEventListener('change', cargarPortadas); // ← Nuevo listener
});
