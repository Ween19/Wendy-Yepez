<?php
    require_once '../../libs/admin.php';
    require_once '../../config/conexion.php';

    if (!isset($_GET['id'])) {
        header("Location: listar.php");
        exit();
    }

    $id = intval($_GET['id']);

    $sql = "SELECT * FROM usuarios WHERE id_usuario = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $usuario = $stmt->fetch();

    if (!$usuario) {
        header("Location: listar.php");
        exit();
    }

    $mensaje = "";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre   = trim($_POST['nombre']);
        $email    = trim($_POST['email']);
        $id_rol   = intval($_POST['id_rol']);
        $estado   = isset($_POST['estado']) ? 1 : 0;

        $sqlEmail = "SELECT id_usuario FROM usuarios WHERE email = :email AND id_usuario != :id";

        $stmtEmail = $pdo->prepare($sqlEmail);
        $stmtEmail->bindParam(':email', $email);
        $stmtEmail->bindParam(':id', $id);

        $stmtEmail->execute();

        if ($stmtEmail->rowCount() > 0) {
            $mensaje = "El correo ya está registrado.";

        } else {

            $sqlUpdate = "UPDATE usuarios SET nombre = :nombre, email = :email, id_rol = :id_rol, estado = :estado WHERE id_usuario = :id";
            $stmtUpdate = $pdo->prepare($sqlUpdate);

            $stmtUpdate->bindParam(':nombre', $nombre);
            $stmtUpdate->bindParam(':email', $email);
            $stmtUpdate->bindParam(':id_rol', $id_rol);
            $stmtUpdate->bindParam(':estado', $estado);
            $stmtUpdate->bindParam(':id', $id);

            if ($stmtUpdate->execute()) {

                header("Location: listar.php");
                exit();
            } else {
                $mensaje = "Error al actualizar.";
            }
        }
    }

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar usuario | Habita</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../css/estilos.css">

</head>

<body class="modal-page">
    <div class="edit-modal-container">
        <div class="edit-modal-header">
            <h2>Editar usuario</h2>
            <a href="listar.php" class="close-modal">
                <i class="bi bi-x-lg"></i>
            </a>
        </div>

        <?php if(!empty($mensaje)): ?>
            <div class="alert alert-danger">
                <?= $mensaje; ?>
            </div>

        <?php endif; ?>

        <form method="POST">
            <div class="row">
                <div class="col-lg-4 text-center">
                    <div class="user-photo-box">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($usuario['nombre']); ?>&background=80B4BF&color=fff&size=256">
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="mb-3">
                        <label class="form-label">
                            Nombre completo
                        </label>
                        <input type="text" name="nombre" class="form-control custom-input" value="<?= htmlspecialchars($usuario['nombre']); ?>" required >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Correo electrónico
                        </label>

                        <input type="email" name="email" class="form-control custom-input" value="<?= htmlspecialchars($usuario['email']); ?>" required >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Rol de usuario
                        </label>

                        <select name="id_rol" class="form-select custom-input">
                            <option value="1" <?= $usuario['id_rol'] == 1 ? 'selected' : ''; ?>> Administrador </option>
                            <option value="2" <?= $usuario['id_rol'] == 2 ? 'selected' : ''; ?>> Usuario </option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label d-block">
                            Estado
                        </label>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="estado" <?= $usuario['estado'] == 1 ? 'checked' : ''; ?> >

                            <label class="form-check-label text-light">
                                Usuario activo
                            </label>
                        </div>

                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Fecha de registro
                        </label>
                        <input type="text" class="form-control custom-input" value="<?= $usuario['fecha_registro']; ?>" disabled >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Último acceso
                        </label>
                        <input type="text" class="form-control custom-input" value="<?= $usuario['ultimo_acceso'] ?? 'Nunca'; ?>" disabled>
                    </div>
                </div>

            </div>

            <div class="edit-buttons">
                <a href="listar.php" class="btn-cancel">
                    Cancelar
                </a>
                <button type="submit" class="btn-save">
                    Guardar cambios
                </button>
            </div>
        </form>

    </div>

</body>
</html>