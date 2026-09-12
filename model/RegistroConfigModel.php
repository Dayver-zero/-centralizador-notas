<?php
require_once __DIR__ . '/../config/conexion.php';

class RegistroConfigModel {
    private $conn;

    private static $CAMPOS = [
        'carrera', 'asignatura', 'codigo', 'anio', 'periodo',
        'docente', 'paralelo', 'observacion'
    ];

    public function __construct() {
        $this->conn = getConnection();
    }

    public function getConfig($cursoId, $materiaId, $gestion) {
        $stmt = $this->conn->prepare("SELECT * FROM registro_config WHERE curso_id = ? AND materia_id = ? AND gestion = ?");
        $stmt->bind_param("iii", $cursoId, $materiaId, $gestion);
        $stmt->execute();
        $result = $stmt->get_result();
        $cfg = $result->fetch_assoc();
        $stmt->close();
        return $cfg;
    }

    public function aplicarConfig($cursoId, $materiaId, $gestion, $campos, $logoBinario = null) {
        $limpios = [];
        foreach (self::$CAMPOS as $campo) {
            $limpios[$campo] = isset($campos[$campo]) ? trim((string) $campos[$campo]) : '';
        }

        $update = 'carrera = ?, asignatura = ?, codigo = ?, anio = ?, periodo = ?, docente = ?, paralelo = ?, observacion = ?';
        if ($logoBinario !== null) {
            $update .= ', logo = ?';
        }

        if ($logoBinario !== null) {
            $sql = "INSERT INTO registro_config
                    (curso_id, materia_id, gestion, carrera, asignatura, codigo, anio, periodo, docente, paralelo, observacion, logo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE $update, updated_at = CURRENT_TIMESTAMP";
        } else {
            $sql = "INSERT INTO registro_config
                    (curso_id, materia_id, gestion, carrera, asignatura, codigo, anio, periodo, docente, paralelo, observacion)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE $update, updated_at = CURRENT_TIMESTAMP";
        }

        $params = [$cursoId, $materiaId, $gestion];
        $types  = 'iii';
        foreach (self::$CAMPOS as $campo) {
            $params[] = $limpios[$campo];
            $types   .= 's';
        }
        if ($logoBinario !== null) {
            $params[] = $logoBinario;
            $types   .= 's';
        }
        foreach ($limpios as $valor) {
            $params[] = $valor;
            $types   .= 's';
        }
        if ($logoBinario !== null) {
            $params[] = $logoBinario;
            $types   .= 's';
        }

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return false;
        }
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}