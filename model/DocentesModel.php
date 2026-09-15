<?php
require_once __DIR__ . '/../config/conexion.php';

class DocentesModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function getAll() {
        $result = $this->conn->query("SELECT * FROM docentes ORDER BY nombre_completo");
        $docentes = [];
        while ($row = $result->fetch_assoc()) {
            $docentes[] = $row;
        }
        return $docentes;
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM docentes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $docente = $result->fetch_assoc();
        $stmt->close();
        return $docente;
    }

    public function crear($ci, $nombre, $email, $telefono, $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO docentes (ci, nombre_completo, email, telefono, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $ci, $nombre, $email, $telefono, $hash);
        $result = $stmt->execute();
        $id = $this->conn->insert_id;
        $stmt->close();

        if ($result) {
            $username = $this->generarUsername($nombre);
            $this->crearUsuario($username, $password, $id);
        }
        return $result;
    }

    private function generarUsername($nombre) {
        $partes = preg_split('/\s+/', trim($nombre));
        $partes = array_values(array_filter($partes, function ($p) {
            return !preg_match('/^(ing|lic|dra|dr|mgr|mba)\.?$/i', $p);
        }));
        $base = strtolower($partes[0] ?? 'usuario');
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

    public function actualizar($id, $ci, $nombre, $email, $telefono, $estado) {
        $stmt = $this->conn->prepare("UPDATE docentes SET ci = ?, nombre_completo = ?, email = ?, telefono = ?, estado = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $ci, $nombre, $email, $telefono, $estado, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function eliminar($id) {
        $stmt = $this->conn->prepare("DELETE FROM docentes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            $stmt = $this->conn->prepare("DELETE FROM usuarios WHERE rol = 'docente' AND referer_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
        return $result;
    }

    public function getMaterias($docenteId) {
        $sql = "SELECT m.nombre AS materia, m.codigo, c.nombre AS curso, c.anio, c.paralelo, c.gestion, c.semestre, c.id AS curso_id, m.id AS materia_id,
                       ca.nombre AS carrera_nombre, ca.tipo AS carrera_tipo, ca.duracion AS carrera_duracion
                FROM docente_materia_curso dmc
                JOIN materias m ON dmc.materia_id = m.id
                JOIN cursos c ON dmc.curso_id = c.id
                JOIN carreras ca ON ca.id = c.carrera_id
                WHERE dmc.docente_id = ?
                ORDER BY c.anio, c.paralelo, m.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $docenteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $materias = [];
        while ($row = $result->fetch_assoc()) {
            $materias[] = $row;
        }
        $stmt->close();
        return $materias;
    }

    private function crearUsuario($username, $password, $docenteId) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO usuarios (username, password, rol, referer_id) VALUES (?, ?, 'docente', ?)");
        $stmt->bind_param("ssi", $username, $hash, $docenteId);
        $stmt->execute();
        $stmt->close();
    }
}
