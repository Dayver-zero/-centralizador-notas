-- =============================================================
-- Centralizador de Notas - Instalación completa para MySQL
-- Proyecto: Instituto Tecnológico PACCIOLI
-- =============================================================

DROP DATABASE IF EXISTS centralizador_notas;
CREATE DATABASE centralizador_notas
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE centralizador_notas;

-- Seguridad y codificación
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================
-- TABLA: carreras
-- =============================================================
CREATE TABLE carreras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    duracion INT NOT NULL DEFAULT 3,
    tipo ENUM('anual', 'semestral') NOT NULL DEFAULT 'anual',
    estado ENUM('activa', 'inactiva') DEFAULT 'activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- TABLA: materias
-- =============================================================
CREATE TABLE materias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    codigo VARCHAR(20) UNIQUE,
    carrera_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_materias_carreras FOREIGN KEY (carrera_id) REFERENCES carreras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- TABLA: docentes
-- =============================================================
CREATE TABLE docentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ci VARCHAR(20) UNIQUE NOT NULL,
    nombre_completo VARCHAR(200) NOT NULL,
    email VARCHAR(150),
    telefono VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- TABLA: estudiantes
-- =============================================================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- TABLA: cursos
-- =============================================================
CREATE TABLE cursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    anio INT NOT NULL,
    paralelo VARCHAR(10) NOT NULL DEFAULT 'A',
    carrera_id INT NOT NULL,
    gestion INT NOT NULL,
    semestre INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cursos_carreras FOREIGN KEY (carrera_id) REFERENCES carreras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- TABLA: estudiantes_cursos
-- =============================================================
CREATE TABLE estudiantes_cursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    curso_id INT NOT NULL,
    semestre INT DEFAULT 1,
    fecha_inscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_inscripcion (estudiante_id, curso_id),
    CONSTRAINT fk_estudiantes_cursos_estudiantes FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    CONSTRAINT fk_estudiantes_cursos_cursos FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- TABLA: docente_materia_curso
-- =============================================================
CREATE TABLE docente_materia_curso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    docente_id INT NOT NULL,
    materia_id INT NOT NULL,
    curso_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_asignacion (docente_id, materia_id, curso_id),
    CONSTRAINT fk_dmc_docentes FOREIGN KEY (docente_id) REFERENCES docentes(id) ON DELETE CASCADE,
    CONSTRAINT fk_dmc_materias FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
    CONSTRAINT fk_dmc_cursos FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- TABLA: notas
-- =============================================================
CREATE TABLE notas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    curso_id INT NOT NULL,
    materia_id INT NOT NULL,
    tipo ENUM('conocer', 'hacer', 'ser', 'parcial', 'final') NOT NULL,
    nombre_actividad VARCHAR(200) DEFAULT NULL,
    nota DECIMAL(5,2) DEFAULT 0.00,
    gestion INT NOT NULL,
    origen ENUM('manual', 'auto') NOT NULL DEFAULT 'manual',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_nota (estudiante_id, curso_id, materia_id, tipo, nombre_actividad),
    CONSTRAINT fk_notas_estudiantes FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    CONSTRAINT fk_notas_cursos FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    CONSTRAINT fk_notas_materias FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- TABLA: asistencia
-- =============================================================
CREATE TABLE asistencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    curso_id INT NOT NULL,
    materia_id INT NOT NULL,
    fecha DATE NOT NULL,
    estado ENUM('presente', 'ausente', 'justificado') NOT NULL DEFAULT 'presente',
    gestion INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_asistencia (estudiante_id, curso_id, materia_id, fecha),
    CONSTRAINT fk_asistencia_estudiantes FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    CONSTRAINT fk_asistencia_cursos FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    CONSTRAINT fk_asistencia_materias FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- TABLA: usuarios (login unificado)
-- =============================================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'docente', 'estudiante') NOT NULL,
    referer_id INT NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- TABLA: mensajes
-- =============================================================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- TABLA: respuestas
-- =============================================================
CREATE TABLE respuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mensaje_id INT NOT NULL,
    emisor_id INT NOT NULL,
    emisor_rol ENUM('admin', 'docente', 'estudiante') NOT NULL,
    respuesta TEXT NOT NULL,
    fecha_respuesta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_respuestas_mensajes FOREIGN KEY (mensaje_id) REFERENCES mensajes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- TABLA: registro_config
-- =============================================================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- TABLA: parcial_periodo
-- =============================================================
CREATE TABLE parcial_periodo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    gestion INT NOT NULL,
    materia_id INT NOT NULL,
    parcial VARCHAR(30) NOT NULL,
    estado ENUM('abierto', 'enviado', 'cerrado') NOT NULL DEFAULT 'abierto',
    abierto_por INT NULL,
    enviado_por INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_curso_mat_parcial (curso_id, materia_id, gestion, parcial),
    CONSTRAINT fk_parcial_cursos FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    CONSTRAINT fk_parcial_materias FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- DATOS INICIALES
-- =============================================================

INSERT INTO carreras (nombre, duracion, tipo, estado) VALUES
('Ingenieria en Sistemas Computacionales', 3, 'anual', 'activa'),
('Ingenieria en Administracion de Empresas', 3, 'anual', 'activa'),
('Contaduria Publica', 3, 'anual', 'activa'),
('Ingenieria Industrial', 3, 'anual', 'activa');

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

INSERT INTO docentes (ci, nombre_completo, email, telefono, password) VALUES
('12345678', 'Ing. Carlos Mendoza', 'carlos.mendoza@pacciol.edu', '70123456', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC'),
('87654321', 'Ing. Maria Fernandez', 'maria.fernandez@pacciol.edu', '70987654', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC'),
('11223344', 'Lic. Juan Perez', 'juan.perez@pacciol.edu', '70112233', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC');

INSERT INTO estudiantes (ci, nombre_completo, matricula, anio_ingreso, email, password) VALUES
('55667788', 'Ana Lopez Garcia', 'MAT2024001', 2024, 'ana.lopez@est.pacciol.edu', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC'),
('55667789', 'Pedro Ramirez Soliz', 'MAT2024002', 2024, 'pedro.ramirez@est.pacciol.edu', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC'),
('55667790', 'Laura Martinez Vargas', 'MAT2024003', 2024, 'laura.martinez@est.pacciol.edu', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC');

INSERT INTO cursos (nombre, anio, paralelo, carrera_id, gestion, semestre) VALUES
('Curso 1', 2025, 'A', 1, 2025, 1),
('Curso 2', 2025, 'B', 1, 2025, 2),
('Curso 3', 2025, 'A', 2, 2025, 1),
('Curso 4', 2025, 'B', 3, 2025, 1);

INSERT INTO estudiantes_cursos (estudiante_id, curso_id, semestre) VALUES
(1, 1, 1),
(2, 1, 1),
(3, 1, 1),
(1, 3, 1),
(3, 4, 1);

INSERT INTO docente_materia_curso (docente_id, materia_id, curso_id) VALUES
(1, 1, 1),
(1, 2, 2),
(1, 3, 1),
(2, 5, 3),
(2, 6, 3),
(3, 7, 4),
(3, 8, 4);

INSERT INTO usuarios (username, password, rol, referer_id) VALUES
('admin', '$2y$12$NYzMD.i1rsKfll6e.unnyuvAQBOhUkzuAYnIMZ1avPg7cqEFWKOCG', 'admin', 1),
('carlos.mendoza', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC', 'docente', 1),
('maria.fernandez', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC', 'docente', 2),
('juan.perez', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC', 'docente', 3),
('ana.lopez', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC', 'estudiante', 1),
('pedro.ramirez', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC', 'estudiante', 2),
('laura.martinez', '$2y$12$At1jA8gSSh0sQ5T.14mK7.OgopzjhupZafPo/iYtD85ThJEaWdVlC', 'estudiante', 3);

INSERT INTO notas (estudiante_id, curso_id, materia_id, tipo, nombre_actividad, nota, gestion) VALUES
(1, 1, 1, 'parcial', 'Evaluacion Teorica', 85, 2025),
(1, 1, 1, 'parcial', 'Investigacion', 90, 2025),
(1, 1, 1, 'parcial', 'Practica', 78, 2025),
(2, 1, 1, 'parcial', 'Evaluacion Teorica', 70, 2025),
(2, 1, 1, 'parcial', 'Investigacion', 75, 2025),
(3, 1, 1, 'parcial', 'Practica', 82, 2025);

INSERT INTO asistencia (estudiante_id, curso_id, materia_id, fecha, estado, gestion) VALUES
(1, 1, 1, '2025-05-10', 'presente', 2025),
(2, 1, 1, '2025-05-10', 'ausente', 2025),
(3, 1, 1, '2025-05-10', 'justificado', 2025),
(1, 1, 1, '2025-05-12', 'presente', 2025),
(2, 1, 1, '2025-05-12', 'presente', 2025),
(3, 1, 1, '2025-05-12', 'presente', 2025);

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- FIN DEL SCRIPT
-- =============================================================
