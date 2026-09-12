<?php
require_once __DIR__ . '/../config/conexion.php';

class UsuariosModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function login($username, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE username = ? AND estado = 'activo'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        $stmt->close();

        if ($usuario && password_verify($password, $usuario['password'])) {
            return $usuario;
        }
        return null;
    }

    public function getUsuarioById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        $stmt->close();
        return $usuario;
    }

    public function crearUsuario($username, $password, $rol, $referer_id) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO usuarios (username, password, rol, referer_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $username, $hash, $rol, $referer_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function cambiarPassword($id, $nuevaPassword) {
        $hash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
