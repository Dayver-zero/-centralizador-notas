<?php
require_once __DIR__ . '/../config/conexion.php';

class CursosModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function getAll() {
        $sql = "SELECT c.*, ca.nombre AS carrera_nombre, ca.tipo AS carrera_tipo, ca.duracion AS carrera_duracion FROM cursos c
                JOIN carreras ca ON c.carrera_id = ca.id
                ORDER BY c.gestion DESC, c.nombre, c.paralelo";
        $result = $this->conn->query($sql);
        $cursos = [];
        while ($row = $result->fetch_assoc()) {
            $cursos[] = $row;
        }
        return $cursos;
    }

    public function getByCarrera($carreraId) {
        $sql = "SELECT * FROM cursos WHERE carrera_id = ? ORDER BY anio, paralelo";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $carreraId);
        $stmt->execute();
        $result = $stmt->get_result();
        $cursos = [];
        while ($row = $result->fetch_assoc()) {
            $cursos[] = $row;
        }
        $stmt->close();
        return $cursos;
    }

    public function getByGestion($gestion) {
        $sql = "SELECT c.*, ca.nombre AS carrera_nombre, ca.tipo AS carrera_tipo, ca.duracion AS carrera_duracion FROM cursos c
                JOIN carreras ca ON c.carrera_id = ca.id
                WHERE c.gestion = ?
                ORDER BY c.nombre, c.paralelo";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $gestion);
        $stmt->execute();
        $result = $stmt->get_result();
        $cursos = [];
        while ($row = $result->fetch_assoc()) {
            $cursos[] = $row;
        }
        $stmt->close();
        return $cursos;
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT c.*, ca.nombre AS carrera_nombre, ca.tipo AS carrera_tipo, ca.duracion AS carrera_duracion FROM cursos c JOIN carreras ca ON c.carrera_id = ca.id WHERE c.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $curso = $result->fetch_assoc();
        $stmt->close();
        return $curso;
    }

    public function crear($nombre, $anio, $paralelo, $carreraId, $gestion, $semestre) {
        $stmt = $this->conn->prepare("INSERT INTO cursos (nombre, anio, paralelo, carrera_id, gestion, semestre) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisiii", $nombre, $anio, $paralelo, $carreraId, $gestion, $semestre);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getEstudiantes($cursoId) {
        $sql = "SELECT e.*, ec.semestre FROM estudiantes e
                JOIN estudiantes_cursos ec ON ec.estudiante_id = e.id
                WHERE ec.curso_id = ?
                ORDER BY e.nombre_completo";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $cursoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $estudiantes = [];
        while ($row = $result->fetch_assoc()) {
            $estudiantes[] = $row;
        }
        $stmt->close();
        return $estudiantes;
    }

    public function getMateriasPorCurso($cursoId) {
        $sql = "SELECT DISTINCT m.id, m.nombre, m.codigo
                FROM docente_materia_curso dmc
                JOIN materias m ON dmc.materia_id = m.id
                WHERE dmc.curso_id = ?
                ORDER BY m.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $cursoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $materias = [];
        while ($row = $result->fetch_assoc()) {
            $materias[] = $row;
        }
        $stmt->close();
        return $materias;
    }

    public function getDocenteMaterias($cursoId) {
        $sql = "SELECT dmc.*, d.nombre_completo AS docente_nombre, m.nombre AS materia_nombre, m.codigo AS materia_codigo
                FROM docente_materia_curso dmc
                JOIN docentes d ON dmc.docente_id = d.id
                JOIN materias m ON dmc.materia_id = m.id
                WHERE dmc.curso_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $cursoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $asignaciones = [];
        while ($row = $result->fetch_assoc()) {
            $asignaciones[] = $row;
        }
        $stmt->close();
        return $asignaciones;
    }

    public function esDocenteAsignado($docenteId, $materiaId, $cursoId) {
        $stmt = $this->conn->prepare("SELECT id FROM docente_materia_curso WHERE docente_id = ? AND materia_id = ? AND curso_id = ?");
        $stmt->bind_param("iii", $docenteId, $materiaId, $cursoId);
        $stmt->execute();
        $stmt->store_result();
        $asignado = $stmt->num_rows > 0;
        $stmt->close();
        return $asignado;
    }

    public function asignarDocenteMateria($docenteId, $materiaId, $cursoId) {
        $stmt = $this->conn->prepare("INSERT IGNORE INTO docente_materia_curso (docente_id, materia_id, curso_id) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $docenteId, $materiaId, $cursoId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getAniosClase() {
        $result = $this->conn->query("SELECT DISTINCT gestion FROM cursos ORDER BY gestion DESC");
        $anios = [];
        while ($row = $result->fetch_assoc()) {
            $anios[] = $row['gestion'];
        }
        return $anios;
    }
}
