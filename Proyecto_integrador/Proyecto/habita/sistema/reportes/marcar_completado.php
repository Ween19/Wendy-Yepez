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

    if (!$id_habito) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }

    $check = $pdo->prepare("SELECT id_habito FROM habitos WHERE id_habito = :id AND id_usuario = :uid AND estado = 1");
    $check->execute([':id' => $id_habito, ':uid' => $id_usuario]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Hábito no encontrado']);
        exit;
    }

    $checkReg = $pdo->prepare("SELECT id_registro FROM historial_habitos WHERE id_habito = :id AND id_usuario = :uid AND fecha = :fecha");
    $checkReg->execute([':id' => $id_habito, ':uid' => $id_usuario, ':fecha' => $hoy]);

    if ($checkReg->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ya registrado hoy']);
        exit;
    }

    $sql = "INSERT INTO historial_habitos (id_habito, id_usuario, fecha, estado) VALUES (:id, :uid, :fecha, 1)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id_habito, ':uid' => $id_usuario, ':fecha' => $hoy]);

    echo json_encode(['success' => true]);
