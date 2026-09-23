# 🚀 PROMPT TÉCNICO MASTER - WEBTING (UPGRADED ARCHITECTURE SPECIFICATION)

Este documento contiene el **Prompt Técnico de Alto Nivel** diseñado bajo estándares de un Senior Front-End Developer y UI/UX Specialist para la plataforma de juegos educativos **Webting**.

---

## 📋 PROMPT MAESTRO DE DESARROLLO (COPIAR / REUTILIZAR)

```markdown
Actúa como un Desarrollador Web Senior Front-End y Arquitecto UI/UX especializado en plataformas de juegos educativos interactivos (EdTech & Gaming). Diseña y desarrolla la arquitectura completa de una aplicación web modular llamada **Webting** para la gestión, emulación y reproducción de juegos educativos, cumpliendo estrictamente los siguientes parámetros técnicos y estéticos:

### 1. SISTEMA DE DISEÑO & TOKENS CROMÁTICOS (UI/UX)
- Paleta Primaria Dual: Naranja Vívido (HSL: 25°, 100%, 50% / #FF6B00, HSL: 32°, 100%, 50% / #FF8800) y Azul Deep Sea (HSL: 210°, 73%, 15% / #0A2540, HSL: 216°, 100%, 50% / #0066FF, HSL: 185°, 100%, 50% / #00E5FF).
- Estilo Visual: Dark Mode Neón / Glassmorphism con bordes translúcidos (`border: 1px solid rgba(0, 229, 255, 0.15)`), sombras luminosas (`box-shadow: 0 0 20px rgba(0, 102, 255, 0.3)`), y desenfoques de fondo (`backdrop-filter: blur(16px)`).
- Tipografía: Google Fonts 'Outfit' para encabezados y 'Plus Jakarta Sans' para cuerpo de texto.
- Micro-interacciones & Hover Cards: Cada tarjeta de juego debe ocultar su breve descripción y solo revelarla mediante una capa de superposición animada (overlay) cuando el usuario pasa el mouse sobre la tarjeta (:hover) o interactúa de forma táctil.

### 2. FILTRADO MULTI-VARIABLE & EMULACIÓN
- Buscador Dinámico Combinado: Filtrado en tiempo real sin recarga de página que combina:
  1. Input de búsqueda por texto.
  2. Filtro por asignatura (Matemáticas, Ciencias, Lenguaje, Geografía).
  3. Filtro por dificultad (Fácil, Medio, Difícil).
  4. Filtro por edad recomendada (6-8 años, 9-12 años, 12+ años).
- Zona de Emulación de Juego 3-Columnas:
  1. Columna Izquierda: Tabla de posiciones / puntajes altos (Leaderboard TOP 10) con medallas de rango (Oro, Plata, Bronce) + Formulario interactivo para registrar puntajes en caliente.
  2. Columna Central: Contenedor responsivo con etiqueta <iframe> para la emulación del juego HTML5/WebGL en vivo con barra de herramientas (Pantalla Completa, Reinicio y Estado).
  3. Columna Derecha: Panel interactivo de guía de controles (mapeo de teclas como WASD, Flechas, Mouse, Espacio).

### 3. PANEL DE ADMINISTRACIÓN AVANZADO (ADMIN MENU OPTIONS UPGRADED)
- Sistema de autenticación de administradores protegido por credenciales (Usuario/Contraseña) Y Verificación de Código de Seguridad PIN de 6 dígitos (889900).
- Herramientas CRUD completas para juegos:
  - Definición de metadatos expandidos: Asignatura, Dificultad, Rango de Edad, Competencia Clave.
  - Editor interactivo de controles dinámicos (añadir/remover filas).
  - Editor interactivo de clasificaciones (Leaderboard Editor): Permite a los administradores limpiar, corregir o agregar récords de puntuaciones manualmente.
- Confirmación de Seguridad PIN obligatoria en el modal antes de guardar o eliminar cualquier registro.

### 4. ARQUITECTURA DE CÓDIGO Y ESTRUCTURA DE ARCHIVOS SEPARADA
El proyecto debe estructurarse obligatoriamente manteniendo estricta separación de responsabilidades:
- Estilos CSS separados por módulos: `/css/main.css`, `/css/game.css`, `/css/admin.css`
- Lógica JS modular sin dependencias pesadas: `/js/app.js`, `/js/auth.js`, `/js/gameEngine.js`, `/js/adminEngine.js`
- Capa Backend PHP limpia y documentada: `/php/config.php`, `/php/auth.php`, `/php/games.php`, `/php/scores.php`
- Almacenamiento Persistente JSON/Database: `/data/games.json`

Todo el código debe incluir comentarios técnicos detallados en español, manejo limpio de errores y fallbacks locales (LocalStorage).
```

---

## 🛠️ ESPECIFICACIÓN TÉCNICA DE LA PLATAFORMA IMPLEMENTADA

### 1. Control del Código PIN de Seguridad
El PIN de confirmación por defecto es `889900`. 
Cualquier acción de guardado, actualización o borrado en el panel de administrador valida que el valor ingresado en el campo `#adminFormPinCode` coincida con este PIN máster, evitando alteraciones maliciosas del catálogo.

### 2. Persistencia Híbrida (JS LocalStorage & PHP API)
- El frontend realiza consultas asíncronas (`fetch`) a los endpoints PHP de la carpeta `/php/`.
- En caso de cargarse en un entorno estático (sin servidor PHP activo, ej. haciendo doble clic en `index.html`), la aplicación detecta el fallo y conmuta automáticamente a usar `localStorage` para emular el CRUD, el inicio de sesión y la persistencia de clasificaciones, garantizando un funcionamiento 100% interactivo en cualquier ambiente.
