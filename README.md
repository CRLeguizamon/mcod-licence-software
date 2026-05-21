# MCOD Software License Manager

[![WordPress Version](https://img.shields.io/badge/WordPress-5.0+-21759b.svg)](https://wordpress.org)
[![PHP Version](https://img.shields.io/badge/PHP-7.4+-777bb4.svg)](https://secure.php.net)
[![License](https://img.shields.io/badge/License-GPLv3-2b8c49.svg)](LICENSE)

Un fork moderno, optimizado y con un diseño premium del plugin **Software License Manager** de WordPress. Este sistema te permite actuar como un servidor de licencias centralizado para tus aplicaciones, temas o plugins de WordPress, gestionando activaciones de dominios y enlazándolos con **Proyectos** internos.

---

## 🚀 Características Clave (Novedades MCOD)

### 📂 1. Gestión Interna de Proyectos (CPT)
Se ha integrado un sistema nativo de gestión de **Proyectos** a través de un Custom Post Type (`mcrpd_project`):
* Submenú dedicado de fácil acceso dentro de la administración de WordPress.
* Posibilidad de crear, editar, organizar y estructurar tus productos como proyectos individuales (soporta descripción completa, imagen destacada y revisiones).

### 🔄 2. Vinculación Inteligente de Licencias a Proyectos
* En la pantalla de creación/edición de licencias, se ha reemplazado el campo de texto simple por un **selector desplegable dinámico y elegante** que lista todos tus proyectos activos.
* Muestra de forma inteligente el enlace directo al proyecto seleccionado en la tabla principal de administración de licencias.

### 🍃 3. Reutilización Automática de Licencias (Reactivación Inteligente)
* **Desactivación Dinámica:** Cuando un dominio es eliminado de una licencia (ya sea manualmente desde el dashboard o remotamente a través de la API mediante la acción `slm_deactivate`), el sistema detecta de manera automatizada si la licencia se ha quedado con **0 dominios activos**.
* **Estado Pendiente:** Si los dominios caen a cero y la licencia no ha expirado, su estado cambia automáticamente a **Pendiente (`pending`)**. Esto permite que la misma licencia pueda ser reutilizada y activada en un nuevo dominio sin intervención manual del administrador.

### 🎨 4. Interfaz de Usuario (UI/UX) Premium y Minimalista
Hemos rediseñado por completo la interfaz del plugin bajo una línea de diseño sumamente cuidada y moderna:
* **Paleta de Colores Exclusiva:** Estructura con fondos blancos limpios, grises minimalistas y acentos de color principal en **Verde MCOD (`#2b8c49`)**.
* **Efectos y Animaciones:** Tarjetas flotantes sutiles con sombras tridimensionales (`box-shadow`), transiciones suaves y anillos de brillo verde (`glow rings`) interactivos en campos activos al hacer foco.
* **Componentes Optimizados:** Limpieza absoluta de etiquetas CSS/JS en línea, estructurando los archivos en activos estáticos cargados óptimamente mediante la API oficial de WordPress (`admin_enqueue_scripts`).

### 📚 5. Documentación Interactiva de la API Integrada
* La página de ayuda para integración incluye un sistema interactivo de **acordeones HTML5 (`<details>`)** con un diseño impecable en tonos verdes.
* Incluye ejemplos en formato `cURL` listos para copiar y pegar para cada una de las acciones de la API (`slm_create_new`, `slm_activate`, `slm_deactivate`, `slm_check`).
* Todos los textos y mensajes son 100% compatibles con herramientas de traducción e internacionalización (`__()`, `_e()`).

---

## 🛠️ Requisitos
* **WordPress:** 5.0 o superior
* **PHP:** 7.4 o superior
* **Licencia:** GPLv3

---

## 💻 Integración con la API (Ejemplos Rápidos)

### A. Crear Nueva Licencia (`slm_create_new`)
Genera una licencia automáticamente en tu servidor de licencias MCOD:
```bash
curl -X POST https://tu-sitio.com/ \
     -d "slm_action=slm_create_new" \
     -d "secret_key=TU_CREATION_SECRET_KEY" \
     -d "first_name=John" \
     -d "last_name=Doe" \
     -d "email=john@example.com" \
     -d "max_allowed_domains=1"
```

### B. Activar una Licencia (`slm_activate`)
Activa una licencia y asóciala a un dominio registrado:
```bash
curl -X POST https://tu-sitio.com/ \
     -d "slm_action=slm_activate" \
     -d "secret_key=TU_VERIFICATION_SECRET_KEY" \
     -d "license_key=LIC-XXXX-XXXX-XXXX" \
     -d "registered_domain=cliente-sitio.com" \
     -d "item_reference=PROJECT_POST_ID"
```

### C. Desactivar una Licencia (`slm_deactivate`)
Desactiva un dominio. Si la cantidad de dominios asociados baja a 0, la licencia vuelve automáticamente a estado `pending`:
```bash
curl -X POST https://tu-sitio.com/ \
     -d "slm_action=slm_deactivate" \
     -d "secret_key=TU_VERIFICATION_SECRET_KEY" \
     -d "license_key=LIC-XXXX-XXXX-XXXX" \
     -d "registered_domain=cliente-sitio.com"
```

---

## 🔒 Seguridad e Integridad del Código
Todo el desarrollo sigue rigurosamente los estándares de desarrollo de WordPress y las directrices internas de desarrollo MCOD:
1. **Sin acceso directo:** Todos los archivos PHP inician con una cláusula de protección `defined('ABSPATH') || exit;`.
2. **Sanitización y Validación:** Todos los inputs provenientes de peticiones externas o de superglobales son pasados previamente por `wp_unslash()` y sanitizados mediante funciones específicas (`sanitize_text_field`, `esc_url_raw`, `absint`).
3. **Escapado Seguro:** Se asegura la salida limpia de HTML mediante `esc_html`, `esc_attr`, `esc_url` y `wp_kses_post`.
4. **Protección CSRF:** Implementación estricta de WordPress Nonces en todos los formularios de configuración y administración.

---

## 👥 Contribuciones y Autor
* **Autor:** crleguizamon
* **Página Web:** [mcodform.com](https://mcodform.com/)
* **Licencia:** Distribuido bajo la licencia GPLv3.
