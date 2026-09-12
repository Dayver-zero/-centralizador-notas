<?php
require_once __DIR__ . '/../config/conexion.php';

class EstudiantesModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function getAll() {
        $result = $this->conn->query("SELECT * FROM estudiantes ORDER BY nombre_completo");
        $estudiantes = [];
        while ($row = $result->fetch_assoc()) {
            $estudiantes[] = $row;
        }
        return $estudiantes;
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM estudiantes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $estudiante = $result->fetch_assoc();
        $stmt->close();
        return $estudiante;
    }

    public function getByCurso($cursoId) {
        $sql = "SELECT e.* FROM estudiantes e
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

    public function getMaterias($estudianteId) {
        $sql = "SELECT m.nombre AS materia, m.codigo, c.nombre AS curso, c.anio, c.paralelo,
                       ca.nombre AS carrera, d.nombre_completo AS docente, c.gestion, c.semestre,
                       c.id AS curso_id, m.id AS materia_id
                FROM estudiantes_cursos ec
                JOIN cursos c ON ec.curso_id = c.id
                JOIN carreras ca ON c.carrera_id = ca.id
                JOIN docente_materia_curso dmc ON dmc.curso_id = c.id
                JOIN materias m ON m.id = dmc.materia_id
                JOIN docentes d ON d.id = dmc.docente_id
                WHERE ec.estudiante_id = ?
                ORDER BY ca.nombre, c.anio, m.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $estudianteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $materias = [];
        while ($row = $result->fetch_assoc()) {
            $materias[] = $row;
        }
        $stmt->close();
        return $materias;
    }

    public function crear($ci, $nombre, $matricula, $anioIngreso, $email, $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO estudiantes (ci, nombre_completo, matricula, anio_ingreso, email, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $ci, $nombre, $matricula, $anioIngreso, $email, $hash);
        $result = $stmt->execute();
        $id = $this->conn->insert_id;
        $stmt->close();

        if ($result) {
            $username = $this->generarUsername($nombre);
            $this->crearUsuario($username, $password, $id);
        }
        return $result;
    }

    public function estaInscrito($estudianteId, $cursoId) {
        $stmt = $this->conn->prepare("SELECT 1 FROM estudiantes_cursos WHERE estudiante_id = ? AND curso_id = ? LIMIT 1");
        $stmt->bind_param("ii", $estudianteId, $cursoId);
        $stmt->execute();
        $stmt->store_result();
        $existe = $stmt->num_rows > 0;
        $stmt->close();
        return $existe;
    }

    private function generarUsername($nombre) {
        $partes = preg_split('/\s+/', trim($nombre));
        $base = strtolower($partes[0] ?? 'estudiante');
        if (isset($partes[1])) {
            $base .= '.' . strtolower($partes[1]);
        }
        $username = $base;
        $i = 1;
        while ($this->existeUsername($username)) {
            $i++;
            $username = $base . $i;
        }
        return $username;
    }

    private function existeUsername($username) {
        $stmt = $this->conn->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $existe = $stmt->num_rows > 0;
        $stmt->close();
        return $existe;
    }

    public function actualizar($id, $ci, $nombre, $matricula, $anioIngreso, $email, $estado) {
        $stmt = $this->conn->prepare("UPDATE estudiantes SET ci = ?, nombre_completo = ?, matricula = ?, anio_ingreso = ?, email = ?, estado = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $ci, $nombre, $matricula, $anioIngreso, $email, $estado, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function inscribirCurso($estudianteId, $cursoId, $semestre = 1) {
        $stmt = $this->conn->prepare("INSERT IGNORE INTO estudiantes_cursos (estudiante_id, curso_id, semestre) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $estudianteId, $cursoId, $semestre);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function buscarPorCi($ci) {
        $stmt = $this->conn->prepare("SELECT id, nombre_completo, estado FROM estudiantes WHERE ci = ?");
        $stmt->bind_param("s", $ci);
        $stmt->execute();
        $result = $stmt->get_result();
        $estudiante = $result->fetch_assoc();
        $stmt->close();
        return $estudiante;
    }

    public function idsVacios($cursoId, $materiaId) {
        $sql = "SELECT ec.estudiante_id AS id
                FROM estudiantes_cursos ec
                JOIN estudiantes e ON e.id = ec.estudiante_id
                WHERE ec.curso_id = ?
                  AND NOT EXISTS (
                      SELECT 1 FROM notas n
                      WHERE n.estudiante_id = ec.estudiante_id
                        AND n.curso_id = ? AND n.materia_id = ?)
                  AND NOT EXISTS (
                      SELECT 1 FROM asistencia a
                      WHERE a.estudiante_id = ec.estudiante_id
                        AND a.curso_id = ? AND a.materia_id = ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiiii", $cursoId, $cursoId, $materiaId, $cursoId, $materiaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = (int) $row['id'];
        }
        $stmt->close();
        return $ids;
    }

    public function quitarVacios($cursoId, $materiaId) {
        $ids = $this->idsVacios($cursoId, $materiaId);
        if (empty($ids)) {
            return 0;
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM estudiantes_cursos WHERE curso_id = ? AND estudiante_id IN ($in)";
        $stmt = $this->conn->prepare($sql);
        $types = str_repeat('i', count($ids) + 1);
        $params = array_merge([$cursoId], $ids);
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();
        return $result ? count($ids) : 0;
    }

    public function eliminar($id) {
        $stmt = $this->conn->prepare("DELETE FROM estudiantes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            $stmt = $this->conn->prepare("DELETE FROM usuarios WHERE rol = 'estudiante' AND referer_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
        return $result;
    }

    private function crearUsuario($username, $password, $estudianteId) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO usuarios (username, password, rol, referer_id) VALUES (?, ?, 'estudiante', ?)");
        $stmt->bind_param("ssi", $username, $hash, $estudianteId);
        $stmt->execute();
        $stmt->close();
    }
}
