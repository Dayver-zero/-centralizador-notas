<?php
require_once __DIR__ . '/../config/conexion.php';

class CarrerasModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function getAll() {
        $result = $this->conn->query("SELECT * FROM carreras ORDER BY nombre");
        $carreras = [];
        while ($row = $result->fetch_assoc()) {
            $carreras[] = $row;
        }
        return $carreras;
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM carreras WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $carrera = $result->fetch_assoc();
        $stmt->close();
        return $carrera;
    }

    public function crear($nombre, $duracion) {
        $stmt = $this->conn->prepare("INSERT INTO carreras (nombre, duracion) VALUES (?, ?)");
        $stmt->bind_param("ss", $nombre, $duracion);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function actualizar($id, $nombre, $duracion, $estado) {
        $stmt = $this->conn->prepare("UPDATE carreras SET nombre = ?, duracion = ?, estado = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nombre, $duracion, $estado, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function eliminar($id) {
        $stmt = $this->conn->prepare("DELETE FROM carreras WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
