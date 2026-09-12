<?php
require_once __DIR__ . '/../config/conexion.php';

class AsistenciaModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function getAsistenciaEstudiante($estudianteId, $cursoId, $materiaId, $gestion = null) {
        if ($gestion !== null && $gestion !== '') {
            $sql = "SELECT * FROM asistencia WHERE estudiante_id = ? AND curso_id = ? AND materia_id = ? AND gestion = ? ORDER BY fecha";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iiii", $estudianteId, $cursoId, $materiaId, $gestion);
        } else {
            $sql = "SELECT * FROM asistencia WHERE estudiante_id = ? AND curso_id = ? AND materia_id = ? ORDER BY fecha";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iii", $estudianteId, $cursoId, $materiaId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $asistencia = [];
        while ($row = $result->fetch_assoc()) {
            $asistencia[] = $row;
        }
        $stmt->close();
        return $asistencia;
    }

    public function getResumenAsistencia($estudianteId, $cursoId, $materiaId, $gestion = null) {
        if ($gestion !== null && $gestion !== '') {
            $sql = "SELECT estado, COUNT(*) AS total FROM asistencia WHERE estudiante_id = ? AND curso_id = ? AND materia_id = ? AND gestion = ? GROUP BY estado";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iiii", $estudianteId, $cursoId, $materiaId, $gestion);
        } else {
            $sql = "SELECT estado, COUNT(*) AS total FROM asistencia WHERE estudiante_id = ? AND curso_id = ? AND materia_id = ? GROUP BY estado";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iii", $estudianteId, $cursoId, $materiaId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $resumen = ['presente' => 0, 'ausente' => 0, 'justificado' => 0, 'total' => 0];
        while ($row = $result->fetch_assoc()) {
            $resumen[$row['estado']] = $row['total'];
            $resumen['total'] += $row['total'];
        }
        $stmt->close();

        if ($resumen['total'] > 0) {
            $resumen['porcentaje'] = round($resumen['presente'] / $resumen['total'] * 100, 1);
        } else {
            $resumen['porcentaje'] = 0;
        }

        return $resumen;
    }

    public function registrarAsistencia($estudianteId, $cursoId, $materiaId, $fecha, $estado, $gestion = null) {
        if ($gestion === null || $gestion === '') {
            $gestion = (int) date('Y');
        }
        $stmt = $this->conn->prepare("INSERT INTO asistencia (estudiante_id, curso_id, materia_id, fecha, estado, gestion) VALUES (?, ?, ?, ?, ?, ?)
                                      ON DUPLICATE KEY UPDATE estado = VALUES(estado)");
        $stmt->bind_param("iiissi", $estudianteId, $cursoId, $materiaId, $fecha, $estado, $gestion);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getAsistenciaPorCurso($cursoId, $materiaId, $gestion = null) {
        if ($gestion !== null && $gestion !== '') {
            $sql = "SELECT a.*, e.nombre_completo FROM asistencia a
                    JOIN estudiantes e ON a.estudiante_id = e.id
                    WHERE a.curso_id = ? AND a.materia_id = ? AND a.gestion = ?
                    ORDER BY e.nombre_completo, a.fecha";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iii", $cursoId, $materiaId, $gestion);
        } else {
            $sql = "SELECT a.*, e.nombre_completo FROM asistencia a
                    JOIN estudiantes e ON a.estudiante_id = e.id
                    WHERE a.curso_id = ? AND a.materia_id = ?
                    ORDER BY e.nombre_completo, a.fecha";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $cursoId, $materiaId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $asistencia = [];
        while ($row = $result->fetch_assoc()) {
            $asistencia[] = $row;
        }
        $stmt->close();
        return $asistencia;
    }
}
