<?php
require_once "../../libs/auth.php";
require_once "../../config/conexion.php";

    $id_usuario = $_SESSION['usuario_id'];
    $id_habito  = intval($_GET['id'] ?? 0);

    if(!$id_habito){
        header("Location: listar_habitos.php");
        exit;
    }

    try {
        $sql = "
        UPDATE habitos
        SET estado = 0, fecha_eliminacion = NOW()
        WHERE id_habito  = :id
        AND id_usuario = :uid
        AND estado     = 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id_habito, ':uid' => $id_usuario]);
    } catch(PDOException $e){

    }

    header("Location: listar_habitos.php?exito=eliminado");
    exit;
