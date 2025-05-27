const puppeteer = require('puppeteer');

(async () => {
  const url = 'https://www.eldiario.net/portal/';
  const browser = await puppeteer.launch({ headless: true });
  const page = await browser.newPage();

  await page.goto(url, { waitUntil: 'networkidle2' });

  // Extraer la URL de la imagen de la portada
  const imageUrl = await page.evaluate(() => {
    const img = document.querySelector('.tdm-inline-image-wrap img');
    return img ? img.src : null;
  });

  if (imageUrl) {
    console.log('✅ Imagen encontrada:', imageUrl);
  } else {
    console.log('❌ No se encontró la imagen.');
  }

  await browser.close();
})();

console.log(JSON.stringify({ img }));
