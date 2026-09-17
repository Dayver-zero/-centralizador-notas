<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/CursosModel.php';
require_once __DIR__ . '/EstudiantesModel.php';
require_once __DIR__ . '/NotasModel.php';
require_once __DIR__ . '/ParcialPeriodoModel.php';

class PlanillasModel {
    private $conn;
    private $cursosModel;
    private $estudiantesModel;
    private $notasModel;

    public function __construct() {
        $this->conn = getConnection();
        $this->cursosModel = new CursosModel();
        $this->estudiantesModel = new EstudiantesModel();
        $this->notasModel = new NotasModel();
    }

    public function promedioArray(array $vals) {
        $sum = 0; $n = 0;
        foreach ($vals as $v) {
            if ($v !== null) { $sum += $v; $n++; }
        }
        return $n > 0 ? round($sum / $n, 1) : null;
    }

    // ===== ENTREGA DE CALIFICACIONES (curso/materia) =====
    public function datosEntrega($cursoId, $materiaId) {
        $curso = $this->cursosModel->getById((int) $cursoId);
        if (!$curso) {
            return null;
        }
        $materia = null;
        foreach ($this->cursosModel->getMateriasPorCurso((int) $cursoId) as $m) {
            if ((int) $m['id'] === (int) $materiaId) { $materia = $m; break; }
        }
        if (!$materia) {
            return null;
        }

        $stmt = $this->conn->prepare("SELECT d.nombre_completo FROM docente_materia_curso dmc
                                      JOIN docentes d ON d.id = dmc.docente_id
                                      WHERE dmc.curso_id = ? AND dmc.materia_id = ? LIMIT 1");
        $stmt->bind_param("ii", $cursoId, $materiaId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $curso['docente_nombre'] = $row['nombre_completo'] ?? 'Asignado';

        $carreraTipo = $curso['carrera_tipo'] ?? 'anual';
        $ciclo = ParcialPeriodoModel::ciclo($carreraTipo);

        $calendario = [];
        foreach ($this->notasModel->getParcialesPorCurso((int) $cursoId, (int) $materiaId) as $f) {
            $id = (int) $f['estudiante_id'];
            if (!isset($calendario[$id])) {
                $calendario[$id] = [
                    'nombre' => $f['nombre_completo'],
                    'matricula' => $f['matricula'],
                    'ci' => $f['ci'] ?? '',
                    'notas' => [],
                    'parcialUnico' => null,
                    'final' => null,
                ];
            }
            if ($f['nota'] === null) continue;
            if ($f['nombre_actividad'] === 'Parcial') {
                $calendario[$id]['parcialUnico'] = (float) $f['nota'];
            } elseif (in_array($f['nombre_actividad'], $ciclo, true)) {
                $calendario[$id]['notas'][$f['nombre_actividad']] = (float) $f['nota'];
            }
        }
        foreach ($calendario as &$d) {
            $d['final'] = !empty($d['notas']) ? $this->promedioArray($d['notas']) : $d['parcialUnico'];
        }
        unset($d);
        ksort($calendario);

        return [
            'curso' => $curso,
            'materia' => $materia,
            'ciclo' => $ciclo,
            'carreraTipo' => $carreraTipo,
            'calendario' => array_values($calendario),
            'notaAprobacion' => 61,
            'turno' => 'MAÑANA',
            'gestion' => (int) ($curso['gestion'] ?? date('Y')),
        ];
    }

    // ===== CENTRALIZADOR DE CALIFICACIONES (curso) =====
    public function datosCentralizador($cursoId) {
        $curso = $this->cursosModel->getById((int) $cursoId);
        if (!$curso) {
            return null;
        }
        $materias = $this->cursosModel->getMateriasPorCurso((int) $cursoId);
        $asignaciones = $this->cursosModel->getDocenteMaterias((int) $cursoId);

        $central = [];
        foreach ($materias as $mat) {
            $mid = (int) $mat['id'];
            foreach ($this->notasModel->getParcialesPorCurso((int) $cursoId, $mid) as $fila) {
                $sid = (int) $fila['estudiante_id'];
                if (!isset($central[$sid])) {
                    $central[$sid] = [
                        'nombre' => $fila['nombre_completo'],
                        'ci' => $fila['ci'] ?? '',
                        'matricula' => $fila['matricula'] ?? '',
                        'materias' => [],
                    ];
                }
                if ($fila['nota'] !== null) {
                    // Se conserva la ultima nota parcial de la materia (mismo
                    // comportamiento que la vista previa del centralizador)
                    $central[$sid]['materias'][$mid] = (float) $fila['nota'];
                }
            }
        }
        // Normaliza: las materias sin nota quedan con null
        $centralFila = [];
        foreach ($central as $sid => $est) {
            $proms = [];
            foreach ($materias as $mat) {
                $proms[(int) $mat['id']] = $est['materias'][(int) $mat['id']] ?? null;
            }
            $est['materias'] = $proms;
            $est['estado'] = $this->notaEstado($proms);
            $centralFila[] = $est;
        }
        usort($centralFila, fn($a, $b) => strcmp($a['nombre'], $b['nombre']));

        $inscritos = count($centralFila);
        $aprobados = 0; $reprobados = 0; $abandonos = 0;
        foreach ($centralFila as $est) {
            $tiene = array_filter($est['materias'], fn($v) => $v !== null && $v !== '');
            if (!$tiene) { $abandonos++; continue; }
            if (min(array_map('floatval', $tiene)) >= 61) $aprobados++;
            else $reprobados++;
        }

        return [
            'curso' => $curso,
            'materias' => $materias,
            'asignaciones' => $asignaciones,
            'central' => $centralFila,
            'inscritos' => $inscritos,
            'aprobados' => $aprobados,
            'reprobados' => $reprobados,
            'abandonos' => $abandonos,
            'codigoRegistro' => '80850061',
            'gestion' => (int) ($curso['gestion'] ?? date('Y')),
        ];
    }

    public function notaEstado(array $materias) {
        $proms = [];
        foreach ($materias as $prom) {
            if ($prom !== null && $prom !== '') $proms[] = (float) $prom;
        }
        if (!$proms) return '';
        return min($proms) >= 61 ? 'APROBADO' : 'REPROBADO';
    }

    public function pct($cant, $total) {
        return $total ? number_format($cant * 100 / $total, 1) : '0';
    }

    // ===== HISTORIAL ACADÉMICO / KARDEX (estudiante) =====
    public function datosHistorial($estudianteId) {
        $est = $this->estudiantesModel->getById((int) $estudianteId);
        if (!$est) {
            return null;
        }
        $notas = $this->notasModel->getResumenNotas((int) $estudianteId);
        $kardex = ['estudiante' => $est, 'filas' => []];

        $grupos = [];
        foreach ($notas as $n) {
            if (($n['tipo'] ?? '') !== 'parcial') continue;
            $act = $n['nombre_actividad'];
            if (!in_array($act, ['1er Parcial', '2do Parcial', '3er Parcial', '4to Parcial', 'Parcial'], true)) continue;
            $clave = (int) $n['materia_id'] . '|' . (int) $n['gestion'] . '|' . (int) $n['curso_id'];
            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [
                    'gestion' => (int) $n['gestion'],
                    'semestre' => (int) ($n['semestre'] ?? 0),
                    'codigo' => $n['codigo'],
                    'materia' => $n['materia'],
                    'curso' => $n['curso'],
                    'notas' => [],
                    'nota' => null,
                ];
            }
            if ($act === 'Parcial') {
                $grupos[$clave]['nota'] = (float) $n['nota'];
            } else {
                $grupos[$clave]['notas'][$act] = (float) $n['nota'];
            }
        }
        foreach ($grupos as $g) {
            if ($g['nota'] === null && count(array_filter($g['notas'], fn($v) => $v !== null)) > 0) {
                $g['nota'] = $this->promedioArray($g['notas']);
            }
            $kardex['filas'][] = $g;
        }
        usort($kardex['filas'], function ($a, $b) {
            if ($a['gestion'] !== $b['gestion']) return $a['gestion'] - $b['gestion'];
            return strcmp($a['materia'], $b['materia']);
        });

        $acum = 0; $n = 0; $aprobadas = 0;
        foreach ($kardex['filas'] as $f) {
            if ($f['nota'] !== null) {
                $acum += $f['nota'];
                $n++;
                if ($f['nota'] >= 61) $aprobadas++;
            }
        }
        $kardex['promedio'] = $n > 0 ? round($acum / $n, 1) : 0;
        $kardex['aprobadas'] = $aprobadas;
        $kardex['carrera'] = $est && $notas ? ($notas[0]['carrera_nombre'] ?? '') : '';

        return $kardex;
    }

    public function semestrePalabra($n) {
        $map = [1 => 'PRIMERO', 2 => 'SEGUNDO', 3 => 'TERCERO',
                4 => 'CUARTO', 5 => 'QUINTO', 6 => 'SEXTO'];
        return $map[(int) $n] ?? 'PRIMERO';
    }

    public function fechaAdmision($anio) {
        return '01/02/' . (int) $anio;
    }

    public function fechaConclusion($anio) {
        return '01/02/' . ((int) $anio + 3);
    }

    public function fechaBoletin($ts) {
        $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        return date('j', $ts) . ' de ' . $meses[(int) date('n', $ts) - 1] . ' de ' . date('Y', $ts);
    }
}