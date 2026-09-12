# Centralizador de Notas — Instituto Tecnológico "PACCIOLI"

Sistema web de gestión académica para el registro pedagógico y administración del Instituto Tecnológico [PACCIOLI]. Permite llevar el registro oficial de **notas, asistencia y parciales** por materia y curso, replicando la hoja de calificaciones (Registro Pedagógico) usada por la institución.

## Características

- **Registro Pedagógico por materia/curso** con hoja imprimible (formato A4 horizontal) que replica el registro institucional oficial.
- Notas por bloques: **CONOCER (30%)**, **HACER (60%)**, **SER (10%)** y **Parcial** con cálculo automático de teoría, práctica y parcial.
- Asistencia de estudiantes (presente / ausente / justificado) por fecha, con porcentaje automático.
- Gestión completa: **carreras, cursos, materias, docentes, estudiantes e inscripciones**.
- Asignación de **docentes a materias-cursos**.
- **Roles**: admin, docente y estudiante, con paneles y menús propios.
- **Mensajería interna** (bandeja de entrada / enviar mensaje) entre usuarios.
- Edición en línea de la tarjeta del registro (carrera, asignatura, docente, logo, etc.) con botón *Aplicar cambios*.
- Añadir/quitar **columnas de evaluación** y **fechas de asistencia** sobre la hoja.
- Añadir alumnos y desinscribir estudiantes sin notas ni asistencia (botones *Añadir alumno* / *Quitar vacíos*).
- Modo oscuro para la interfaz; la hoja del registro se imprime siempre como papel.

## Tecnologías

- **PHP** 8+ (sin frameworks)
- **MySQL / MariaDB** (XAMPP)
- **HTML5 + CSS3 + JavaScript** (JS vanilla, CSS nativo)
- **XAMPP** como entorno de desarrollo

## Estructura del proyecto

```
centralizador_notas/
├── config/             # Configuración (conexión a BD) — no se incluye en el repo
├── controller/         # Lógica de control (login, registro AJAX)
├── css/                # Estilos
├── includes/           # Menús, CSRF, cabeceras
├── js/                 # Scripts del cliente
├── model/              # Capa de datos (modelos)
├── sql/                # Esquema de la base de datos
├── view/               # Vistas (admin, docente, estudiante)
├── index.php           # Redirección según rol
├── login.php           # Inicio de sesión
└── logout.php          # Cerrar sesión
```

## Instalación (XAMPP)

1. **Requisitos**: XAMPP con PHP 8+ y MySQL/MariaDB activos.

2. **Copiar el proyecto** a `C:\xampp\htdocs\centralizador_notas`.

3. **Crear la base de datos** e importar el esquema:

   ```sql
   CREATE DATABASE centralizador_notas CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
   ```

   Importa luego el archivo `sql/centralizador_notas_esquema.sql` (solo estructura).
   *Nota:* el repositorio también conserva `sql/centralizador_notas.sql` con datos de prueba para desarrollo.

4. **Crear `config/conexion.php`** (no se incluye en el repositorio por contener credenciales):

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

   Ajusta `DB_HOST`/`DB_USER`/`DB_PASS` según tu instalación (el puerto por defecto de XAMPP para MySQL es **3307**).

5. **Crear usuarios iniciales** (admin, docentes, estudiantes) desde la base de datos. Ejemplo de estructura de la tabla `usuarios`:

   ```sql
   INSERT INTO usuarios (username, password, rol, referer_id)
   VALUES ('admin', 'HASH_DE_CONTRASEÑA', 'admin', 1);
   ```

6. **Acceder** desde el navegador:

   ```
   http://localhost/centralizador_notas/login.php
   ```

## Uso por rol

| Rol       | Acciones principales                                                        |
|-----------|-----------------------------------------------------------------------------|
| Admin     | Gestionar carreras, cursos, materias, docentes, estudiantes, inscripciones, asignaciones, notas e historial académico |
| Docente   | Ver materias asignadas, llenar el **Registro Pedagógico** (notas, asistencia, parciales), editar tarjeta y logo, añadir/quitar columnas y alumnos |
| Estudiante| Ver sus materias, notas y asistencia |

## Licencia

Proyecto académico del Instituto Tecnológico "PACCIOLI". Uso educativo.