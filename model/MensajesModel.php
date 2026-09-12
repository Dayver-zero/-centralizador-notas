<?php
require_once __DIR__ . '/../config/conexion.php';

class MensajesModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function getMensajesRecibidos($usuarioId, $rol) {
        $sql = "SELECT m.*, 
                CASE m.emisor_rol
                    WHEN 'admin' THEN 'Administrador'
                    WHEN 'docente' THEN (SELECT nombre_completo FROM docentes WHERE id = m.emisor_id)
                    WHEN 'estudiante' THEN (SELECT nombre_completo FROM estudiantes WHERE id = m.emisor_id)
                END AS emisor_nombre
                FROM mensajes m
                WHERE m.receptor_id = ? AND m.receptor_rol = ?
                ORDER BY m.fecha_envio DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $usuarioId, $rol);
        $stmt->execute();
        $result = $stmt->get_result();
        $mensajes = [];
        while ($row = $result->fetch_assoc()) {
            $mensajes[] = $row;
        }
        $stmt->close();
        return $mensajes;
    }

    public function getMensajesEnviados($usuarioId, $rol) {
        $sql = "SELECT m.*,
                CASE m.receptor_rol
                    WHEN 'admin' THEN 'Administrador'
                    WHEN 'docente' THEN (SELECT nombre_completo FROM docentes WHERE id = m.receptor_id)
                    WHEN 'estudiante' THEN (SELECT nombre_completo FROM estudiantes WHERE id = m.receptor_id)
                END AS receptor_nombre
                FROM mensajes m
                WHERE m.emisor_id = ? AND m.emisor_rol = ?
                ORDER BY m.fecha_envio DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $usuarioId, $rol);
        $stmt->execute();
        $result = $stmt->get_result();
        $mensajes = [];
        while ($row = $result->fetch_assoc()) {
            $mensajes[] = $row;
        }
        $stmt->close();
        return $mensajes;
    }

    public function obtenerMensaje($id) {
        $stmt = $this->conn->prepare("SELECT * FROM mensajes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $mensaje = $result->fetch_assoc();
        $stmt->close();

        if ($mensaje && !$mensaje['leido']) {
            $this->marcarLeido($id);
        }

        return $mensaje;
    }

    public function obtenerRespuestas($mensajeId) {
        $sql = "SELECT r.*,
                CASE r.emisor_rol
                    WHEN 'admin' THEN 'Administrador'
                    WHEN 'docente' THEN (SELECT nombre_completo FROM docentes WHERE id = r.emisor_id)
                    WHEN 'estudiante' THEN (SELECT nombre_completo FROM estudiantes WHERE id = r.emisor_id)
                END AS emisor
                FROM respuestas r
                WHERE r.mensaje_id = ?
                ORDER BY r.fecha_respuesta ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $mensajeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $respuestas = [];
        while ($row = $result->fetch_assoc()) {
            $respuestas[] = $row;
        }
        $stmt->close();
        return $respuestas;
    }

    public function enviarMensaje($emisorId, $emisorRol, $receptorId, $receptorRol, $asunto, $mensaje) {
        $stmt = $this->conn->prepare("INSERT INTO mensajes (emisor_id, emisor_rol, receptor_id, receptor_rol, asunto, mensaje) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissss", $emisorId, $emisorRol, $receptorId, $receptorRol, $asunto, $mensaje);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function responder($mensajeId, $emisorId, $emisorRol, $respuesta) {
        $stmt = $this->conn->prepare("INSERT INTO respuestas (mensaje_id, emisor_id, emisor_rol, respuesta) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $mensajeId, $emisorId, $emisorRol, $respuesta);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function marcarLeido($id) {
        $stmt = $this->conn->prepare("UPDATE mensajes SET leido = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    public function marcarTodosLeidos($usuarioId, $rol) {
        $stmt = $this->conn->prepare("UPDATE mensajes SET leido = 1 WHERE receptor_id = ? AND receptor_rol = ? AND leido = 0");
        $stmt->bind_param("is", $usuarioId, $rol);
        $stmt->execute();
        $stmt->close();
    }

    public function contarNoLeidos($usuarioId, $rol) {
        $sql = "SELECT COUNT(*) AS total FROM mensajes WHERE receptor_id = ? AND receptor_rol = ? AND leido = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $usuarioId, $rol);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['total'];
    }
}
