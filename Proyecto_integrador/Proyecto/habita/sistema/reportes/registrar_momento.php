<?php
require_once "../../libs/auth.php";
require_once "../../config/conexion.php";

    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $id_usuario = $_SESSION['usuario_id'];
    $nota = trim($_POST['nota'] ?? '');
    $intensidad = intval($_POST['intensidad'] ?? 3);
    $hoy = date('Y-m-d');
    $hora = date('H:i:s');

    if (empty($nota)) {
        echo json_encode(['success' => false, 'message' => 'Escribe algo que hayas hecho']);
        exit;
    }

    $sql = "INSERT INTO historial_habitos (id_usuario, fecha, hora, nota_emocional, intensidad, estado) 
            VALUES (:uid, :fecha, :hora, :nota, :intensidad, 1)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':uid' => $id_usuario,
        ':fecha' => $hoy,
        ':hora' => $hora,
        ':nota' => $nota,
        ':intensidad' => $intensidad
    ]);

    echo json_encode(['success' => true]);
