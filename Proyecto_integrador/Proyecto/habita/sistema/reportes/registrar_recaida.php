<?php
require_once "../../libs/auth.php";
require_once "../../config/conexion.php";

    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $id_habito = intval($_POST['id_habito'] ?? 0);
    $id_usuario = $_SESSION['usuario_id'];
    $hoy = date('Y-m-d');
    $hora = date('H:i:s');

    if (!$id_habito) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $update = $pdo->prepare("UPDATE habitos SET fecha_ultima_recaida = :fecha WHERE id_habito = :id AND id_usuario = :uid");
        $update->execute([':fecha' => $hoy, ':id' => $id_habito, ':uid' => $id_usuario]);

        $insert = $pdo->prepare("INSERT INTO historial_habitos (id_habito, id_usuario, fecha, hora, estado, nota_emocional) 
                                VALUES (:id, :uid, :fecha, :hora, 0, 'recaída')");
        $insert->execute([':id' => $id_habito, ':uid' => $id_usuario, ':fecha' => $hoy, ':hora' => $hora]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
