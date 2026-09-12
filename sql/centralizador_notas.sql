-- ============================================
-- BASE DE DATOS: CENTRALIZADOR DE NOTAS
-- Instituto Tecnologico "PACCIOLI"
-- ============================================

CREATE DATABASE IF NOT EXISTS centralizador_notas
DEFAULT CHARACTER SET utf8mb4
DEFAULT COLLATE utf8mb4_general_ci;

USE centralizador_notas;

-- ============================================
-- TABLA: carreras
-- ============================================
CREATE TABLE carreras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    duracion VARCHAR(50) DEFAULT '4 anos',
    estado ENUM('activa', 'inactiva') DEFAULT 'activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABLA: materias
-- ============================================
CREATE TABLE materias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    codigo VARCHAR(20) UNIQUE,
    carrera_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (carrera_id) REFERENCES carreras(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- TABLA: docentes
-- ============================================
CREATE TABLE docentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ci VARCHAR(20) UNIQUE NOT NULL,
    nombre_completo VARCHAR(200) NOT NULL,
    email VARCHAR(150),
    telefono VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABLA: estudiantes
-- ============================================
CREATE TABLE estudiantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ci VARCHAR(20) UNIQUE NOT NULL,
    nombre_completo VARCHAR(200) NOT NULL,
    matricula VARCHAR(50) UNIQUE,
    anio_ingreso INT NOT NULL,
    email VARCHAR(150),
    password VARCHAR(255) NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABLA: cursos
-- ============================================
CREATE TABLE cursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    anio INT NOT NULL,
    paralelo VARCHAR(10) NOT NULL DEFAULT 'A',
    carrera_id INT NOT NULL,
    gestion INT NOT NULL,
    semestre INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (carrera_id) REFERENCES carreras(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- TABLA: estudiantes_cursos (inscripciones)
-- ============================================
CREATE TABLE estudiantes_cursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    curso_id INT NOT NULL,
    semestre INT DEFAULT 1,
    fecha_inscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_inscripcion (estudiante_id, curso_id)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: docente_materia_curso (asignaciones)
-- ============================================
CREATE TABLE docente_materia_curso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    docente_id INT NOT NULL,
    materia_id INT NOT NULL,
    curso_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (docente_id) REFERENCES docentes(id) ON DELETE CASCADE,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_asignacion (docente_id, materia_id, curso_id)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: notas
-- ============================================
CREATE TABLE notas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    curso_id INT NOT NULL,
    materia_id INT NOT NULL,
    tipo ENUM('conocer', 'hacer', 'ser', 'parcial', 'final') NOT NULL,
    nombre_actividad VARCHAR(200),
    nota DECIMAL(5,2) DEFAULT 0,
    gestion INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
    UNIQUE KEY unique_nota (estudiante_id, curso_id, materia_id, tipo, nombre_actividad)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: asistencia
-- ============================================
CREATE TABLE asistencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    curso_id INT NOT NULL,
    materia_id INT NOT NULL,
    fecha DATE NOT NULL,
    estado ENUM('presente', 'ausente', 'justificado') NOT NULL DEFAULT 'presente',
    gestion INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
    UNIQUE KEY unique_asistencia (estudiante_id, curso_id, materia_id, fecha)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: usuarios (login unificado)
-- ============================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'docente', 'estudiante') NOT NULL,
    referer_id INT NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABLA: mensajes
-- ============================================
CREATE TABLE mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    emisor_id INT NOT NULL,
    emisor_rol ENUM('admin', 'docente', 'estudiante') NOT NULL,
    receptor_id INT NOT NULL,
    receptor_rol ENUM('admin', 'docente', 'estudiante') NOT NULL,
    asunto VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    leido TINYINT(1) DEFAULT 0,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABLA: respuestas
-- ============================================
CREATE TABLE respuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mensaje_id INT NOT NULL,
    emisor_id INT NOT NULL,
    emisor_rol ENUM('admin', 'docente', 'estudiante') NOT NULL,
    respuesta TEXT NOT NULL,
    fecha_respuesta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mensaje_id) REFERENCES mensajes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- TABLA: registro_config (textos/logo por hoja del registro)
-- ============================================
CREATE TABLE registro_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    materia_id INT NOT NULL,
    gestion INT NOT NULL,
    carrera TEXT,
    asignatura TEXT,
    codigo VARCHAR(50),
    anio VARCHAR(20),
    periodo VARCHAR(50),
    docente TEXT,
    paralelo VARCHAR(20),
    observacion TEXT,
    logo MEDIUMBLOB,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reg (curso_id, materia_id, gestion)
) ENGINE=InnoDB;

-- ============================================
-- DATOS DE PRUEBA
-- ============================================

-- Carreras
INSERT INTO carreras (nombre, duracion) VALUES
('Ingenieria en Sistemas Computacionales', '4 anos'),
('Ingenieria en Administracion de Empresas', '4 anos'),
('Contaduria Publica', '4 anos'),
('Ingenieria Industrial', '4 anos');

-- Materias
INSERT INTO materias (nombre, codigo, carrera_id) VALUES
('Programacion I', 'PROG101', 1),
('Programacion II', 'PROG102', 1),
('Base de Datos', 'BD101', 1),
('Redes de Computadoras', 'RED101', 1),
('Matematicas I', 'MAT101', 2),
('Administracion General', 'ADM101', 2),
('Contabilidad I', 'CONT101', 3),
('Costos', 'COST101', 3),
('Produccion', 'PROD101', 4),
('Investigacion de Operaciones', 'INV101', 4);

-- Docentes (contraseña: 123456)
INSERT INTO docentes (ci, nombre_completo, email, telefono, password) VALUES
('12345678', 'Ing. Carlos Mendoza', 'carlos.mendoza@pacciol.edu', '70123456', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC'),
('87654321', 'Ing. Maria Fernandez', 'maria.fernandez@pacciol.edu', '70987654', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC'),
('11223344', 'Lic. Juan Perez', 'juan.perez@pacciol.edu', '70112233', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC');

-- Estudiantes (contraseña: 123456)
INSERT INTO estudiantes (ci, nombre_completo, matricula, anio_ingreso, email, password) VALUES
('55667788', 'Ana Lopez Garcia', 'MAT2024001', 2024, 'ana.lopez@est.pacciol.edu', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC'),
('55667789', 'Pedro Ramirez Soliz', 'MAT2024002', 2024, 'pedro.ramirez@est.pacciol.edu', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC'),
('55667790', 'Laura Martinez Vargas', 'MAT2024003', 2024, 'laura.martinez@est.pacciol.edu', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC'),
('55667791', 'Carlos Garcia Mamani', 'MAT2024004', 2024, 'carlos.garcia@est.pacciol.edu', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC'),
('55667792', 'Sofia Torres Choque', 'MAT2024005', 2024, 'sofia.torres@est.pacciol.edu', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC');

-- Cursos
INSERT INTO cursos (nombre, anio, paralelo, carrera_id, gestion, semestre) VALUES
('1er Semestre Sistemas', 1, 'A', 1, 2025, 1),
('2do Semestre Sistemas', 2, 'A', 1, 2025, 1),
('1er Semestre Administracion', 1, 'A', 2, 2025, 1),
('1er Semestre Contaduria', 1, 'B', 3, 2025, 1);

-- Inscripciones de estudiantes
INSERT INTO estudiantes_cursos (estudiante_id, curso_id, semestre) VALUES
(1, 1, 1), (2, 1, 1), (3, 1, 1),
(4, 2, 2), (5, 2, 2),
(1, 3, 1), (3, 4, 1);

-- Asignaciones docente-materia-curso
INSERT INTO docente_materia_curso (docente_id, materia_id, curso_id) VALUES
(1, 1, 1), (1, 2, 2), (1, 3, 1), (1, 4, 2),
(2, 5, 3), (2, 6, 3),
(3, 7, 4), (3, 8, 4);

-- Usuarios para login
INSERT INTO usuarios (username, password, rol, referer_id) VALUES
('admin', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC', 'admin', 1),
('carlos.mendoza', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC', 'docente', 1),
('maria.fernandez', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC', 'docente', 2),
('juan.perez', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC', 'docente', 3),
('ana.lopez', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC', 'estudiante', 1),
('pedro.ramirez', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC', 'estudiante', 2),
('laura.martinez', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC', 'estudiante', 3),
('carlos.garcia', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC', 'estudiante', 4),
('sofia.torres', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC', 'estudiante', 5);

-- Notas de prueba
INSERT INTO notas (estudiante_id, curso_id, materia_id, tipo, nombre_actividad, nota, gestion) VALUES
(1, 1, 1, 'parcial', 'Evaluacion Teorica', 85, 2025),
(1, 1, 1, 'parcial', 'Investigacion', 90, 2025),
(1, 1, 1, 'parcial', 'Practica', 78, 2025),
(2, 1, 1, 'parcial', 'Evaluacion Teorica', 70, 2025),
(2, 1, 1, 'parcial', 'Investigacion', 80, 2025),
(3, 1, 1, 'parcial', 'Evaluacion Teorica', 95, 2025);

-- Asistencia de prueba
INSERT INTO asistencia (estudiante_id, curso_id, materia_id, fecha, estado, gestion) VALUES
(1, 1, 1, '2025-03-10', 'presente', 2025),
(1, 1, 1, '2025-03-17', 'presente', 2025),
(1, 1, 1, '2025-03-24', 'ausente', 2025),
(2, 1, 1, '2025-03-10', 'presente', 2025),
(2, 1, 1, '2025-03-17', 'justificado', 2025),
(3, 1, 1, '2025-03-10', 'presente', 2025);
