<?php
require_once __DIR__ . '/../config/conexion.php';

class MateriasModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function getAll() {
        $sql = "SELECT m.*, c.nombre AS carrera_nombre FROM materias m
                JOIN carreras c ON m.carrera_id = c.id
                ORDER BY c.nombre, m.nombre";
        $result = $this->conn->query($sql);
        $materias = [];
        while ($row = $result->fetch_assoc()) {
            $materias[] = $row;
        }
        return $materias;
    }

    public function getByCarrera($carreraId) {
        $stmt = $this->conn->prepare("SELECT * FROM materias WHERE carrera_id = ? ORDER BY nombre");
        $stmt->bind_param("i", $carreraId);
        $stmt->execute();
        $result = $stmt->get_result();
        $materias = [];
        while ($row = $result->fetch_assoc()) {
            $materias[] = $row;
        }
        $stmt->close();
        return $materias;
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM materias WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $materia = $result->fetch_assoc();
        $stmt->close();
        return $materia;
    }

    public function crear($nombre, $codigo, $carreraId) {
        $stmt = $this->conn->prepare("INSERT INTO materias (nombre, codigo, carrera_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $nombre, $codigo, $carreraId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function actualizar($id, $nombre, $codigo, $carreraId) {
        $stmt = $this->conn->prepare("UPDATE materias SET nombre = ?, codigo = ?, carrera_id = ? WHERE id = ?");
        $stmt->bind_param("ssii", $nombre, $codigo, $carreraId, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function eliminar($id) {
        $stmt = $this->conn->prepare("DELETE FROM materias WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
