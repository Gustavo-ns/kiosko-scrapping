# Kiosko Scraping

Sistema de scraping de noticias que permite recopilar y gestionar portadas de periódicos y noticias de diferentes fuentes.

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Extensión ImageMagick para PHP
- Composer

## Instalación

1. Clonar el repositorio:
```bash
git clone https://github.com/tu-usuario/kiosko-scrapping.git
cd kiosko-scrapping
```

2. Instalar dependencias:
```bash
composer install
```

3. Configurar el entorno:
```bash
cp .env.example .env
```
Editar el archivo `.env` con tus configuraciones.

4. Crear la base de datos:
```sql
CREATE DATABASE kiosko_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

5. Importar la estructura de la base de datos:
```bash
mysql -u root -p kiosko_db < database/schema.sql
```

6. Configurar los permisos:
```bash
chmod -R 777 public/assets/images
chmod -R 777 logs
```

## Estructura del Proyecto

```
kiosko-scrapping/
├── config/           # Archivos de configuración
├── database/         # Esquemas y migraciones
├── logs/            # Logs de la aplicación
├── public/          # Archivos públicos
├── scripts/         # Scripts de utilidad
├── src/             # Código fuente
│   ├── Controllers/ # Controladores
│   ├── Models/      # Modelos
│   ├── Views/       # Vistas
│   ├── Services/    # Servicios
│   └── Config/      # Configuraciones adicionales
├── tests/           # Tests
└── vendor/          # Dependencias
```

## Características

- Scraping de portadas de periódicos
- Gestión de múltiples fuentes de noticias
- Procesamiento y optimización de imágenes
- Interfaz de usuario intuitiva
- Sistema de filtrado y búsqueda
- Gestión de visibilidad de contenido
- Registro de actividad y errores

## Uso

1. **Importar Enlaces**
   - Acceder a la sección "Importar Enlaces"
   - Ingresar el grupo y país
   - Pegar los enlaces (uno por línea)
   - Hacer clic en "Importar"

2. **Ejecutar Scraping**
   - Ir a la sección "Ejecutar Scraping"
   - Hacer clic en "Iniciar Scraping"
   - Monitorear el progreso en tiempo real

3. **Gestionar Resumen**
   - Acceder a la sección "Resumen"
   - Filtrar por grupo, país o visibilidad
   - Editar registros según sea necesario
   - Activar/desactivar la visibilidad de los registros

## Configuración

### Variables de Entorno

- `APP_NAME`: Nombre de la aplicación
- `APP_ENV`: Entorno (development/production)
- `APP_DEBUG`: Modo debug (true/false)
- `DB_*`: Configuración de la base de datos
- `SCRAPE_*`: Configuración del scraping
- `IMAGE_*`: Configuración del procesamiento de imágenes

### Configuración de Scraping

El archivo `config/app.php` contiene la configuración de los sitios a scrapear. Cada sitio debe tener:

```php
'sites' => [
    'pais' => [
        [
            'url' => 'https://ejemplo.com',
            'selector' => '.portada-img',
            'attribute' => 'src',
            'multiple' => false
        ]
    ]
]
```

## Mantenimiento

### Logs

Los logs se almacenan en el directorio `logs/`:
- `error.log`: Errores de la aplicación
- `scraping.log`: Registro de actividad de scraping

### Limpieza

Para limpiar imágenes antiguas:
```bash
php scripts/cleanup.php --days=7
```

### Backup

Para respaldar la base de datos:
```bash
php scripts/backup.php
```

## Contribuir

1. Fork el repositorio
2. Crear una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir un Pull Request

## Licencia

Este proyecto está licenciado bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles. 