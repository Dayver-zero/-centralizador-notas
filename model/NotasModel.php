<?php
require_once __DIR__ . '/../config/conexion.php';

class NotasModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function getNotasEstudiante($estudianteId, $cursoId, $materiaId) {
        $sql = "SELECT * FROM notas WHERE estudiante_id = ? AND curso_id = ? AND materia_id = ? ORDER BY tipo, nombre_actividad";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $estudianteId, $cursoId, $materiaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $notas = [];
        while ($row = $result->fetch_assoc()) {
            $notas[] = $row;
        }
        $stmt->close();
        return $notas;
    }

    public function getResumenNotas($estudianteId, $gestion = null) {
        if ($gestion !== null && $gestion !== '') {
            $sql = "SELECT n.*, m.nombre AS materia, m.codigo, c.nombre AS curso, c.paralelo, c.gestion, c.semestre AS semestre, ca.nombre AS carrera_nombre
                    FROM notas n
                    JOIN materias m ON n.materia_id = m.id
                    JOIN cursos c ON n.curso_id = c.id
                    JOIN carreras ca ON c.carrera_id = ca.id
                    WHERE n.estudiante_id = ? AND n.gestion = ?
                    ORDER BY m.nombre, n.tipo";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $estudianteId, $gestion);
        } else {
            $sql = "SELECT n.*, m.nombre AS materia, m.codigo, c.nombre AS curso, c.paralelo, c.gestion, c.semestre AS semestre, ca.nombre AS carrera_nombre
                    FROM notas n
                    JOIN materias m ON n.materia_id = m.id
                    JOIN cursos c ON n.curso_id = c.id
                    JOIN carreras ca ON c.carrera_id = ca.id
                    WHERE n.estudiante_id = ?
                    ORDER BY m.nombre, n.tipo";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $estudianteId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $notas = [];
        while ($row = $result->fetch_assoc()) {
            $notas[] = $row;
        }
        $stmt->close();
        return $notas;
    }

    public function getNotasPorCurso($cursoId, $materiaId) {
        $sql = "SELECT n.*, e.nombre_completo FROM notas n
                JOIN estudiantes e ON n.estudiante_id = e.id
                WHERE n.curso_id = ? AND n.materia_id = ?
                ORDER BY e.nombre_completo, n.tipo";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $cursoId, $materiaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $notas = [];
        while ($row = $result->fetch_assoc()) {
            $notas[] = $row;
        }
        $stmt->close();
        return $notas;
    }

    public function guardarNota($estudianteId, $cursoId, $materiaId, $tipo, $nombreActividad, $nota, $gestion = null, $origen = 'manual') {
        if ($gestion === null || $gestion === '') {
            $gestion = (int) date('Y');
        }
        $stmt = $this->conn->prepare(
            "INSERT INTO notas (estudiante_id, curso_id, materia_id, tipo, nombre_actividad, nota, gestion, origen)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE nota = VALUES(nota), gestion = VALUES(gestion),
                 origen = IF(origen = 'manual', 'manual', VALUES(origen))"
        );
        $stmt->bind_param("iiissdis", $estudianteId, $cursoId, $materiaId, $tipo, $nombreActividad, $nota, $gestion, $origen);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getParcialFilaDe($notas, $nombreActividad) {
        foreach ($notas as $n) {
            if (($n['tipo'] ?? '') === 'parcial' && ($n['nombre_actividad'] ?? '') === $nombreActividad) {
                return $n;
            }
        }
        return null;
    }

    public function eliminarNota($estudianteId, $cursoId, $materiaId, $tipo, $nombreActividad) {
        $stmt = $this->conn->prepare(
            "DELETE FROM notas WHERE estudiante_id = ? AND curso_id = ? AND materia_id = ? AND tipo = ? AND nombre_actividad = ?"
        );
        $stmt->bind_param("iiiss", $estudianteId, $cursoId, $materiaId, $tipo, $nombreActividad);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function calcularPromedio($estudianteId, $cursoId, $materiaId, $tipo) {
        $sql = "SELECT AVG(nota) AS promedio, SUM(nota) AS total FROM notas WHERE estudiante_id = ? AND curso_id = ? AND materia_id = ? AND tipo = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiis", $estudianteId, $cursoId, $materiaId, $tipo);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row;
    }

    public function calcularParcial($estudianteId, $cursoId, $materiaId) {
        $conocer = $this->calcularPromedio($estudianteId, $cursoId, $materiaId, 'conocer');
        $hacer = $this->calcularPromedio($estudianteId, $cursoId, $materiaId, 'hacer');
        $ser = $this->calcularPromedio($estudianteId, $cursoId, $materiaId, 'ser');

        $teoria = ($conocer['promedio'] ?? 0) * 0.3;
        $practica = ($hacer['promedio'] ?? 0) * 0.7;
        $total = $teoria + $practica + ($ser['promedio'] ?? 0) * 0.1;

        return [
            'conocer' => $conocer['promedio'] ?? 0,
            'hacer' => $hacer['promedio'] ?? 0,
            'ser' => $ser['promedio'] ?? 0,
            'teoria' => round($teoria, 2),
            'practica' => round($practica, 2),
            'total' => round($total, 2)
        ];
    }

    public function getParcialesPorCurso($cursoId, $materiaId) {
        $sql = "SELECT e.id AS estudiante_id, e.nombre_completo, e.matricula, e.ci,
                       n.tipo, n.nombre_actividad, n.nota, n.gestion
                FROM estudiantes e
                JOIN estudiantes_cursos ec ON ec.estudiante_id = e.id
                JOIN cursos c ON c.id = ec.curso_id
                LEFT JOIN notas n ON n.estudiante_id = e.id
                    AND n.curso_id = ec.curso_id
                    AND n.materia_id = ?
                    AND n.tipo = 'parcial'
                WHERE ec.curso_id = ?
                ORDER BY e.nombre_completo, n.nombre_actividad";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $materiaId, $cursoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $filas = [];
        while ($row = $result->fetch_assoc()) {
            $filas[] = $row;
        }
        $stmt->close();
        return $filas;
    }

    public function getNotaParcialDe($notas, $nombreActividad) {
        foreach ($notas as $n) {
            if (($n['tipo'] ?? '') === 'parcial' && ($n['nombre_actividad'] ?? '') === $nombreActividad) {
                return (float) $n['nota'];
            }
        }
        return null;
    }
}
