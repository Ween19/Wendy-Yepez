<?php

require_once '../../libs/admin.php';
require_once '../../config/conexion.php';


    if (!isset($_GET['id'])) {
        header("Location: usuarios.php");
        exit();
    }

    $id = intval($_GET['id']);
    $sql = "SELECT * FROM usuarios WHERE id_usuario = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $usuario = $stmt->fetch();

    if (!$usuario) {
        header("Location: usuarios.php");
        exit();
    }

    if ($usuario['id_usuario'] == $_SESSION['usuario_id']) {
        header("Location: listar.php");
        exit();
    }


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sqlUpdate = "UPDATE usuarios SET estado = 0 WHERE id_usuario = :id";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmtUpdate->execute()) {
            header("Location: listar.php");
            exit();
        }
    }

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Eliminar usuario | Habita</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../css/estilos.css">

</head>

<body class="modal-page">
    <div class="delete-modal-container">
        <a href="listar.php" class="close-modal">
            <i class="bi bi-x-lg"></i>
        </a>

        <div class="delete-icon">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>

        <h2 class="delete-title"> ¿Eliminar usuario? </h2>

        <p class="delete-text">
            Estás a punto de eliminar al usuario
            <span> "<?= htmlspecialchars($usuario['nombre']); ?>" </span>
            <br>
            Esta acción no se puede deshacer.
        </p>

        <div class="delete-buttons">
            <a href="listar.php" class="btn-cancel-delete"> Cancelar </a>

            <form method="POST">
                <button type="submit" class="btn-confirm-delete">
                    <i class="bi bi-trash"></i> Sí, eliminar
                </button>
            </form>

        </div>

    </div>

</body>
</html>