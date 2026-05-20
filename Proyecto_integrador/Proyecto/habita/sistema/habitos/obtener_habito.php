<?php
require_once "../../libs/auth.php";
require_once "../../config/conexion.php";

    header('Content-Type: application/json');

    $id_usuario = $_SESSION['usuario_id'];
    $id_habito  = intval($_GET['id'] ?? 0);

    if(!$id_habito){
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }

    try {

        $sql = "
        SELECT
            h.id_habito,
            h.nombre,
            h.id_categoria,
            h.clase_habito,
            h.direccion_habito,
            h.dificultad,
            h.fecha_ultima_recaida,
            mc.id_meta,
            mc.cantidad      AS meta_cantidad,
            mc.unidad        AS meta_unidad,
            mc.frecuencia    AS meta_frecuencia,
            mc.fecha_inicio  AS meta_fecha_inicio,
            mc.fecha_fin     AS meta_fecha_fin
        FROM habitos h
        LEFT JOIN metas_config mc ON mc.id_habito = h.id_habito
        WHERE h.id_habito  = :id
        AND h.id_usuario = :uid
        AND h.estado     = 1
        LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id_habito, ':uid' => $id_usuario]);
        $habito = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$habito){
            echo json_encode(['success' => false, 'message' => 'Hábito no encontrado']);
            exit;
        }

        $dias = [];
        if($habito['id_meta']){
            $sqlDias = "SELECT dia FROM metas_dias WHERE id_meta = :id_meta";
            $stmtD   = $pdo->prepare($sqlDias);
            $stmtD->execute([':id_meta' => $habito['id_meta']]);
            $dias = array_column($stmtD->fetchAll(PDO::FETCH_ASSOC), 'dia');
        }

        echo json_encode([
            'success' => true,
            'habito'  => $habito,
            'dias'    => $dias
        ]);

    } catch(PDOException $e){
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
