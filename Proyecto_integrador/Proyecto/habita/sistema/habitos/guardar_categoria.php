<?php
require_once "../../libs/auth.php";
require_once "../../config/conexion.php";

    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['nombre']) && !empty($data['nombre'])) {
        $nombre = trim($data['nombre']);
        $icono = isset($data['icono']) ? trim($data['icono']) : null;
        $id_usuario = $_SESSION['usuario_id'];
        
        $sql = "INSERT INTO categorias (nombre_cat, icono, id_usuario) VALUES (:nombre, :icono, :id_usuario)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':icono' => $icono,
            ':id_usuario' => $id_usuario
        ]);
        
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['success' => false]);
    }
