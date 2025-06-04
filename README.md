# Kiosko Digital - Portadas de Periódicos

## Descripción
Kiosko Digital es una plataforma que muestra las portadas más recientes de periódicos de América Latina y el Caribe. La aplicación recopila y muestra portadas de diferentes fuentes (Meltwater, Covers y Resúmenes) en un formato visual atractivo y optimizado.

## Características Principales

### 1. Optimización de Rendimiento
- **LCP (Largest Contentful Paint) Optimizado**
  - Carga prioritaria de las primeras 6 imágenes
  - Preload de imágenes críticas
  - Optimización de thumbnails

- **CLS (Cumulative Layout Shift) Minimizado**
  - Dimensiones fijas para contenedores
  - Content-visibility optimizado
  - Contenido reservado para evitar saltos

- **Carga Progresiva**
  - Lazy loading para imágenes no críticas
  - Carga progresiva de imágenes de alta calidad
  - Placeholders durante la carga

### 2. Fuentes de Datos
- **Meltwater**
  - Portadas de medios principales
  - Imágenes optimizadas en formato WebP
  - Previews generados automáticamente

- **Covers**
  - Portadas de periódicos internacionales
  - Thumbnails y versiones originales
  - Actualización automática

- **Resúmenes**
  - Contenido de redes sociales
  - Enlaces directos a publicaciones
  - Integración con Twitter

### 3. Funcionalidades
- **Filtrado por Grupo**
  - Selector de grupos de medios
  - Persistencia de selección en URL
  - Actualización dinámica

- **Visualización**
  - Grid responsive
  - Modal para imágenes completas
  - Animaciones suaves

- **Actualización**
  - Botón de recarga forzada
  - Actualización automática periódica
  - Limpieza de caché

### 4. Optimizaciones Técnicas
- **CSS**
  - Estilos críticos inline
  - Content-visibility para rendimiento
  - Animaciones optimizadas

- **JavaScript**
  - Carga progresiva de imágenes
  - Batch updates para DOM
  - Intersection Observer para lazy loading

- **Caché y Recursos**
  - Service Worker para offline
  - Preload de recursos críticos
  - Versionado de assets

## Requisitos Técnicos
- PHP 7.4+
- MySQL/MariaDB
- Servidor web con soporte para WebP
- Módulos PHP: GD o Imagick

## Estructura de Archivos
```
├── index.php              # Página principal
├── styles.css            # Estilos no críticos
├── process_portadas.php  # Procesamiento de portadas
├── update_melwater.php   # Actualización de Meltwater
├── config.php           # Configuración
├── images/
│   ├── melwater/       # Imágenes de Meltwater
│   └── covers/         # Imágenes de portadas
└── cache/              # Directorio de caché
```

## Optimizaciones de Rendimiento
1. **Primera Carga**
   - Preload de primeras 6 imágenes
   - CSS crítico inline
   - Lazy loading para resto de contenido

2. **Renderizado**
   - Content-visibility para elementos no visibles
   - Contain-intrinsic-size para evitar CLS
   - Batch updates para DOM

3. **Imágenes**
   - Formato WebP para mejor compresión
   - Thumbnails optimizados
   - Carga progresiva

## Mantenimiento
- Limpieza automática de imágenes temporales
- Actualización periódica de contenido
- Logging de errores y eventos

## Contribución
Para contribuir al proyecto:
1. Fork el repositorio
2. Crea una rama para tu feature
3. Commit tus cambios
4. Push a la rama
5. Crea un Pull Request

## Licencia
Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles. 