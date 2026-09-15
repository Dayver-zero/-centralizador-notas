<?php
require_once __DIR__ . '/../config/conexion.php';

class ParcialPeriodoModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    // Ciclo de parciales segun el tipo de carrera
    public static function ciclo($tipo) {
        return $tipo === 'semestral'
            ? ['1er Parcial', '2do Parcial']
            : ['1er Parcial', '2do Parcial', '3er Parcial', '4to Parcial'];
    }

    // Todas las opciones (ciclo + Parcial Unico)
    public static function opciones($tipo) {
        return array_merge(self::ciclo($tipo), ['Parcial']);
    }

    public static function etiqueta($parcial) {
        $map = [
            '1er Parcial' => 'PRIMER PARCIAL',
            '2do Parcial' => 'SEGUNDO PARCIAL',
            '3er Parcial' => 'TERCER PARCIAL',
            '4to Parcial' => 'CUARTO PARCIAL',
            'Parcial'     => 'PARCIAL ÚNICO',
        ];
        return $map[$parcial] ?? strtoupper((string) $parcial);
    }

    public static function añoTexto($n) {
        $map = [1 => 'PRIMER AÑO', 2 => 'SEGUNDO AÑO', 3 => 'TERCER AÑO',
                4 => 'CUARTO AÑO', 5 => 'QUINTO AÑO', 6 => 'SEXTO AÑO'];
        return $map[(int) $n] ?? ('AÑO ' . (int) $n);
    }

    private function materiasDeCurso($cursoId) {
        $stmt = $this->conn->prepare("SELECT DISTINCT materia_id FROM docente_materia_curso WHERE curso_id = ?");
        $stmt->bind_param("i", $cursoId);
        $stmt->execute();
        $res = $stmt->get_result();
        $ids = [];
        while ($row = $res->fetch_assoc()) {
            $ids[] = (int) $row['materia_id'];
        }
        $stmt->close();
        return $ids;
    }

    // Crea las filas del curso si faltan (primer parcial abierto, resto cerrado)
    public function asegurarCurso($cursoId, $gestion, $tipo) {
        $materiaIds = $this->materiasDeCurso($cursoId);
        if (!$materiaIds) {
            return 0;
        }
        $ciclo = self::ciclo($tipo);
        $opciones = array_merge($ciclo, ['Parcial']);
        $stmt = $this->conn->prepare(
            "INSERT IGNORE INTO parcial_periodo (curso_id, gestion, materia_id, parcial, estado) VALUES (?, ?, ?, ?, ?)"
        );
        $affected = 0;
        foreach ($materiaIds as $matId) {
            foreach ($opciones as $i => $parcial) {
                $estado = ($i === 0) ? 'abierto' : 'cerrado';
                $stmt->bind_param("iiiss", $cursoId, $gestion, $matId, $parcial, $estado);
                $stmt->execute();
                $affected += $stmt->affected_rows;
            }
        }
        $stmt->close();
        return $affected;
    }

    public function getEstados($cursoId, $materiaId, $gestion, $tipo) {
        $this->asegurarCurso($cursoId, $gestion, $tipo);
        $map = [];
        foreach (self::opciones($tipo) as $p) {
            $map[$p] = 'cerrado';
        }
        $stmt = $this->conn->prepare(
            "SELECT parcial, estado FROM parcial_periodo WHERE curso_id = ? AND materia_id = ? AND gestion = ?"
        );
        $stmt->bind_param("iii", $cursoId, $materiaId, $gestion);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $map[$row['parcial']] = $row['estado'];
        }
        $stmt->close();
        return $map;
    }

    // Avanza automaticamente: la "campaña" activa es el primer parcial del ciclo
    // que aun no fue enviado por todas las materias. Si esa campaña no es la
    // primera, la abre (avance automatico al enviar el anterior).
    private function autoAvanzar($cursoId, $gestion, $tipo) {
        $materiaIds = $this->materiasDeCurso($cursoId);
        if (!$materiaIds) {
            return false;
        }
        $ciclo = self::ciclo($tipo);
        $stmtCount = $this->conn->prepare(
            "SELECT COUNT(DISTINCT materia_id) AS total, SUM(estado = 'enviado') AS enviados
             FROM parcial_periodo WHERE curso_id = ? AND gestion = ? AND parcial = ?"
        );
        $campaign = null;
        foreach ($ciclo as $parcial) {
            $stmtCount->bind_param("iis", $cursoId, $gestion, $parcial);
            $stmtCount->execute();
            $row = $stmtCount->get_result()->fetch_assoc();
            $total = (int) ($row['total'] ?? 0);
            $enviados = (int) ($row['enviados'] ?? 0);
            if ($total === 0 || $enviados !== $total) {
                $campaign = $parcial;
                break;
            }
        }
        $stmtCount->close();
        if ($campaign === null || $campaign === $ciclo[0]) {
            return true; // todo enviado, o la campaña ya era la primera (abierta por defecto)
        }
        $upd = $this->conn->prepare(
            "UPDATE parcial_periodo SET estado = 'abierto', abierto_por = NULL
             WHERE curso_id = ? AND gestion = ? AND parcial = ? AND estado = 'cerrado'"
        );
        $upd->bind_param("iis", $cursoId, $gestion, $campaign);
        $upd->execute();
        $upd->close();
        return true;
    }

    // Parcial activo (abierto) para la materia del docente; null si no hay ninguno
    public function parcialActivo($cursoId, $materiaId, $gestion, $tipo, $estados = null) {
        $this->autoAvanzar($cursoId, $gestion, $tipo);
        if ($estados === null) {
            $estados = $this->getEstados($cursoId, $materiaId, $gestion, $tipo);
        }
        foreach (self::ciclo($tipo) as $p) {
            if (($estados[$p] ?? 'cerrado') === 'abierto') {
                return $p;
            }
        }
        if (($estados['Parcial'] ?? 'cerrado') === 'abierto') {
            return 'Parcial';
        }
        return null;
    }

    public function esAbierto($cursoId, $materiaId, $gestion, $parcial) {
        $stmt = $this->conn->prepare(
            "SELECT estado FROM parcial_periodo WHERE curso_id = ? AND materia_id = ? AND gestion = ? AND parcial = ?"
        );
        $stmt->bind_param("iiis", $cursoId, $materiaId, $gestion, $parcial);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return ($row['estado'] ?? '') === 'abierto';
    }

    // Director/admin abre el parcial para todas las materias del curso
    public function abrir($cursoId, $gestion, $parcial, $adminId) {
        $this->asegurarCurso($cursoId, $gestion, $this->tipoDeCurso($cursoId) ?? 'anual');
        $stmt = $this->conn->prepare(
            "INSERT INTO parcial_periodo (curso_id, gestion, materia_id, parcial, estado, abierto_por)
             SELECT ? AS curso_id, ? AS gestion, dmc.materia_id AS materia_id, ? AS parcial, 'abierto' AS estado, ? AS abierto_por
             FROM docente_materia_curso dmc
             LEFT JOIN parcial_periodo pp ON pp.curso_id = dmc.curso_id AND pp.gestion = ? AND pp.materia_id = dmc.materia_id AND pp.parcial = ?
             WHERE dmc.curso_id = ?
             ON DUPLICATE KEY UPDATE estado = 'abierto', abierto_por = VALUES(abierto_por)"
        );
        $stmt->bind_param("iisiiii", $cursoId, $gestion, $parcial, $adminId, $gestion, $parcial, $cursoId);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    public function cerrar($cursoId, $gestion, $parcial) {
        $stmt = $this->conn->prepare(
            "UPDATE parcial_periodo SET estado = 'cerrado' WHERE curso_id = ? AND gestion = ? AND parcial = ?"
        );
        $stmt->bind_param("iis", $cursoId, $gestion, $parcial);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    // El docente envia sus notas de la materia; solo si el parcial esta abierto
    public function enviar($cursoId, $materiaId, $gestion, $parcial, $docenteId) {
        if (!$this->esAbierto($cursoId, $materiaId, $gestion, $parcial)) {
            return false;
        }
        $stmt = $this->conn->prepare(
            "UPDATE parcial_periodo SET estado = 'enviado', enviado_por = ? WHERE curso_id = ? AND materia_id = ? AND gestion = ? AND parcial = ? AND estado = 'abierto'"
        );
        $stmt->bind_param("iiiis", $docenteId, $cursoId, $materiaId, $gestion, $parcial);
        $stmt->execute();
        $afectadas = $stmt->affected_rows;
        $stmt->close();
        if ($afectadas > 0) {
            $this->autoAvanzar($cursoId, $gestion, $this->tipoDeCurso($cursoId) ?? 'anual');
        }
        return $afectadas > 0;
    }

    private function tipoDeCurso($cursoId) {
        $stmt = $this->conn->prepare(
            "SELECT ca.tipo AS tipo FROM cursos c JOIN carreras ca ON ca.id = c.carrera_id WHERE c.id = ?"
        );
        $stmt->bind_param("i", $cursoId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row['tipo'] ?? null;
    }

    // Resumen por curso para el panel admin (por cada parcial del ciclo)
    public function resumenCurso($cursoId, $gestion, $tipo) {
        $this->asegurarCurso($cursoId, $gestion, $tipo);
        $opciones = self::opciones($tipo);
        $resumen = [];
        foreach ($opciones as $p) {
            $resumen[$p] = ['total' => 0, 'abiertos' => 0, 'enviados' => 0, 'cerrados' => 0];
        }
        $stmt = $this->conn->prepare(
            "SELECT parcial, estado, COUNT(*) AS n FROM parcial_periodo
             WHERE curso_id = ? AND gestion = ? GROUP BY parcial, estado"
        );
        $stmt->bind_param("ii", $cursoId, $gestion);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            if (!isset($resumen[$row['parcial']])) {
                $resumen[$row['parcial']] = ['total' => 0, 'abiertos' => 0, 'enviados' => 0, 'cerrados' => 0];
            }
            $resumen[$row['parcial']]['total'] += (int) $row['n'];
            $resumen[$row['parcial']][$row['estado'] . 's'] += (int) $row['n'];
        }
        $stmt->close();
        return $resumen;
    }
}