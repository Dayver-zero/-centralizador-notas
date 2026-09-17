# Centralizador de Notas — Instituto Tecnológico "PACCIOLI"

Sistema web de gestión académica para el registro pedagógico y la administración del Instituto Tecnológico "PACCIOLI". Permite llevar el registro oficial de **notas, asistencia y parciales** por materia y curso, y genera las **planillas institucionales** (Entrega de Calificaciones, Centralizador e Historial Académico) a partir de los datos registrados por los docentes.

## Características

- **Registro Pedagógico por materia/curso** con hoja imprimible (A4 horizontal) que replica el registro institucional oficial.
- Notas por bloques: **CONOCER (30%)**, **HACER (60%)**, **SER (10%)** y **Parcial**, con cálculo automático de teoría, práctica y parcial.
- **Flujo de parciales**: el docente captura y al pulsar *Enviar parcial* se genera automáticamente la nota del parcial (`origen = auto`); si la escribe a mano, se respeta (`origen = manual`). Al reenviar, las automáticas se recalculan con los componentes.
- Asistencia de estudiantes (presente / ausente / justificado) por fecha, con porcentaje automático.
- **Planillas del administrador** (formato institucional, conectadas a la base de datos):
  - **Entrega de Calificaciones** por curso/materia (con columnas dinámicas según el ciclo: 1er/2do o 1er–4to parcial).
  - **Centralizador de Calificaciones** del curso.
  - **Historial Académico / Kardex** por estudiante.
  - **Exportación a Excel (.xlsx)** sin dependencias externas e **impresión** directa (A4 horizontal / vertical según la planilla).
- Gestión completa: **carreras, cursos, materias, docentes, estudiantes e inscripciones** y **asignación de docentes a materias-cursos**.
- **Roles**: admin, docente y estudiante, con paneles y menús propios.
- **Mensajería interna** entre usuarios.
- **Edición directa por celdas** y navegación tipo Excel (flechas, Enter, Tab, Escape) en las hojas.
- Modo oscuro; las hojas siempre se imprimen como papel.

## Tecnologías

- **PHP** 8+ (sin frameworks)
- **MySQL / MariaDB** (XAMPP)
- **HTML5 + CSS3 + JavaScript** (JS vanilla, CSS nativo)
- **XAMPP** como entorno de desarrollo
- **Sin Composer** y **sin extensiones adicionales**: la exportación a `.xlsx` se genera con PHP nativo (no requiere la extensión `zip`).

## Estructura del proyecto

```
centralizador_notas/
├── config/             # Configuración (conexión a BD) — no se incluye en el repo
├── controller/         # Controladores (login, registro AJAX, exportación de planillas)
├── css/                # Estilos
├── includes/           # Menús, CSRF, generador XLSX
├── js/                 # Scripts del cliente
├── model/              # Capa de datos (modelos)
├── sql/                # Scripts de instalación de la base de datos
├── view/               # Vistas (admin, docente, estudiante)
├── index.php           # Redirección según rol
├── login.php           # Inicio de sesión
└── logout.php          # Cerrar sesión
```

## Requisitos

- **XAMPP** con **PHP 8+** y **MariaDB/MySQL** activos (Apache + MySQL encendidos).
- Navegador web moderno.
- No se necesita Composer, Node ni ninguna extensión de PHP extra.

## Instalación

### 1. Copiar el proyecto

Copia la carpeta del proyecto en `C:\xampp\htdocs\centralizador_notas`.

> **Importante:** las rutas del sistema son absolutas (`/centralizador_notas/...`), así que la carpeta debe llamarse exactamente **`centralizador_notas`**. Si le cambias el nombre, dejarán de cargar estilos, scripts y el login.

### 2. Crear la base de datos e importar el esquema

Hay **dos esquemas** disponibles en `sql/`. Elige uno según lo que necesites:

| Archivo | Contenido | Cuándo usarlo |
|---|---|---|
| **`sql/centralizador_notas.sql`** | Estructura completa (14 tablas con claves foráneas) **+ usuario `admin`**. Sin datos ficticios. | **Instalación limpia**: entras como `admin` y creas carreras, cursos, materias, etc. desde la web. |
| **`sql/setup_mysql_completo.sql`** | Estructura completa **+ usuario `admin` + datos de prueba ficticios** (carreras, cursos, materias, docentes, estudiantes, inscripciones, asignaciones, notas y asistencia de ejemplo). | **Prueba/demo**: ya tienes datos cargados para explorar todas las pantallas. |

Ambos scripts hacen `DROP DATABASE IF EXISTS centralizador_notas` y la vuelven a crear, así que **eligen el estado final de la base**.

**Opción A — Instalación limpia (recomendada para producción):**

```bat
C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\centralizador_notas\sql\centralizador_notas.sql
```

**Opción B — Con datos de prueba (recomendada para probar):**

```bat
C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\centralizador_notas\sql\setup_mysql_completo.sql
```

> Si tu MySQL no está en el puerto por defecto, agrega `-h 127.0.0.1 -P TU_PUERTO` (por ejemplo `-P 3307`).

También puedes importarlos desde **phpMyAdmin** (`http://localhost/phpmyadmin`): pestaña *Importar* → selecciona el archivo `.sql` → *Continuar*.

> El antiguo archivo `centralizador_notas_esquema.sql` fue **eliminado**: estaba desactualizado (no incluía la columna `notas.origen` ni el usuario admin) y podía romper el guardado de notas.

### 3. Crear `config/conexion.php`

El archivo `config/conexion.php` **no está en el repositorio** (contiene credenciales). Créalo con este contenido y ajusta `DB_HOST`/`DB_USER`/`DB_PASS` según tu instalación:

```php
<?php
define('DB_HOST', 'localhost:3307');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'centralizador_notas');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Error de conexion: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

function getConnection() {
    global $conn;
    return $conn;
}
```

> **Puerto:** en este ejemplo se usa `3307`. Verifica el tuyo en el panel de XAMPP (MySQL suele ser **3306** por defecto). Si tu MySQL usa otro puerto, cámbialo en `DB_HOST`.

### 4. Acceder

Abre en el navegador:

```
http://localhost/centralizador_notas/login.php
```

## Credenciales

**Opción A — instalación limpia** (`centralizador_notas.sql`):

| Rol | Usuario | Contraseña |
|---|---|---|
| Admin | `admin` | `admin123` |

Con ese admin creas desde la web los docentes/estudiantes (sus usuarios se generan solos).

**Opción B — con datos de prueba** (`setup_mysql_completo.sql`):

| Rol | Usuario | Contraseña |
|---|---|---|
| Admin | `admin` | `admin123` |
| Docente | `carlos.mendoza` | `123456` |
| Docente | `maria.fernandez` | `123456` |
| Docente | `juan.perez` | `123456` |
| Estudiante | `ana.lopez` | `123456` |
| Estudiante | `pedro.ramirez` | `123456` |
| Estudiante | `laura.martinez` | `123456` |

## Uso por rol

| Rol | Acciones principales |
|-----|----------------------|
| Admin | Gestionar carreras, cursos, materias, docentes, estudiantes, inscripciones y asignaciones; ver y exportar **Entrega de Calificaciones**, **Centralizador** e **Historial Académico** |
| Docente | Ver materias asignadas, llenar el **Registro Pedagógico** (notas, asistencia, parciales), editar la tarjeta del registro y enviar parciales |
| Estudiante | Ver sus materias, notas y asistencia |

## Solución de problemas

- **No cargan estilos, scripts ni el login** → la carpeta no se llama `centralizador_notas` o no está en `htdocs`. Las rutas son absolutas.
- **`Error de conexion`** → revisa `config/conexion.php`: usuario/contraseña y, sobre todo, el **puerto** de MySQL.
- **No aparece ninguna materia al seleccionar curso** → la materia debe estar **asignada a un docente** en ese curso (menú *Cursos → Asignar Docentes*).
- **Error al exportar a Excel** → este proyecto **no** usa la extensión `zip` ni Composer; si aparece un error, verifica que estés usando el `PHP 8+` de XAMPP y recarga la página (caché).

## Licencia

Proyecto académico del Instituto Tecnológico "PACCIOLI". Uso educativo.
