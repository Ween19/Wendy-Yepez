<?php
require_once "../libs/auth.php";
require_once "../config/conexion.php";

    $id_usuario = $_SESSION['usuario_id'];
    $success = "";
    $error = "";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $nombre = trim($_POST['nombre']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if (
            empty($nombre) ||
            empty($username) ||
            empty($email)
        ) {
            $error = "Todos los campos obligatorios deben completarse.";
        } else {
            $sqlCheck = "SELECT id_usuario 
                        FROM usuarios 
                        WHERE (email = :email OR username = :username)
                        AND id_usuario != :id_usuario
                        LIMIT 1";

            $stmtCheck = $pdo->prepare($sqlCheck);

            $stmtCheck->execute([
                ':email' => $email,
                ':username' => $username,
                ':id_usuario' => $id_usuario
            ]);
            if ($stmtCheck->fetch()) {
                $error = "El username o correo ya están registrados.";
            } else {

                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $error = "La contraseña debe tener mínimo 6 caracteres.";
                    } else {
                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                        $sqlUpdate = "UPDATE usuarios SET nombre = :nombre, username = :username, email = :email, password = :password WHERE id_usuario = :id_usuario";
                        $stmtUpdate = $pdo->prepare($sqlUpdate);

                        $stmtUpdate->execute([ ':nombre' => $nombre, ':username' => $username, ':email' => $email, ':password' => $passwordHash, ':id_usuario' => $id_usuario]);
                        $_SESSION['nombre'] = $nombre;
                        $_SESSION['username'] = $username;

                        $success = "Perfil actualizado correctamente.";
                    }
                } else {
                    $sqlUpdate = "UPDATE usuarios SET nombre = :nombre, username = :username, email = :email WHERE id_usuario = :id_usuario";
                    $stmtUpdate = $pdo->prepare($sqlUpdate);
                    $stmtUpdate->execute([ ':nombre' => $nombre, ':username' => $username, ':email' => $email, ':id_usuario' => $id_usuario]);
                    $_SESSION['nombre'] = $nombre;
                    $_SESSION['username'] = $username;
                    $success = "Perfil actualizado correctamente.";
                }
            }
        }
    }


    $sql = "SELECT * FROM usuarios WHERE id_usuario = :id_usuario LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_usuario', $id_usuario);
    $stmt->execute();

    $usuario = $stmt->fetch();
    if (!$usuario) {
        header("Location: ../logout.php");
        exit();
    }

    $rol = ($usuario['id_rol'] == 1)
        ? "Administrador"
        : "Usuario";

    $estado = ($usuario['estado'] == 1)
        ? "Activo"
        : "Inactivo";

    $estadoColor = ($usuario['estado'] == 1)
        ? "text-success"
        : "text-danger";

        
    $sqlMomentos = "SELECT fecha, hora, nota_emocional, intensidad 
                    FROM historial_habitos 
                    WHERE id_usuario = :uid AND (id_habito IS NULL OR id_habito = 0)
                    ORDER BY fecha DESC, hora DESC 
                    LIMIT 20";
    $stmtMomentos = $pdo->prepare($sqlMomentos);
    $stmtMomentos->execute([':uid' => $id_usuario]);
    $momentos = $stmtMomentos->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mi perfil | Habita</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/estilos.css">

</head>

<body class="dashboard-body">

    <?php include "../includes/header.php"; ?>
    <?php include "../includes/sidebar.php"; ?>

    <main class="content-wrapper">
        <div class="container-fluid">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"> <?= $success ?> </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"> <?= $error ?> </div>
            <?php endif; ?>

            <div class="page-header mb-4">
                <h1 class="page-title">Mi perfil</h1>
                <p class="page-subtitle"> Gestiona tu información personal y preferencias de cuenta. </p>
            </div>

            <div class="row g-4">
                <div class="col-lg-3">
                    <div class="profile-sidebar">
                        <div class="profile-avatar-wrapper">
                            <div class="profile-avatar"> <?= strtoupper(substr($usuario['nombre'],0,1)) ?> </div>
                        </div>

                        <h3 class="profile-name"> <?= htmlspecialchars($usuario['nombre']) ?> </h3>
                        <span class="profile-role"> <?= $rol ?> </span>
                        <p class="profile-description"> Usuario registrado en la plataforma Habita. </p>

                        <div class="profile-info-list">
                            <div class="profile-info-item">
                                <i class="bi bi-person"></i>
                                <div>
                                    <small>Usuario</small>
                                    <strong> <?= htmlspecialchars($usuario['username']) ?> </strong>
                                </div>

                            </div>

                            <div class="profile-info-item">
                                <i class="bi bi-envelope"></i>
                                <div>
                                    <small>Correo electrónico</small>
                                    <strong> <?= htmlspecialchars($usuario['email']) ?></strong>
                                </div>

                            </div>

                            <div class="profile-info-item">
                                <i class="bi bi-calendar-event"></i>
                                <div>
                                    <small>Fecha de registro</small>
                                    <strong> <?= date("d/m/Y", strtotime($usuario['fecha_registro'])) ?></strong>
                                </div>

                            </div>

                            <div class="profile-info-item">
                                <i class="bi bi-clock-history"></i>
                                <div>
                                    <small>Último acceso</small>
                                    <strong> <?= $usuario['ultimo_acceso'] ?? 'Sin registro' ?> </strong>
                                </div>

                            </div>

                            <div class="profile-info-item">
                                <i class="bi bi-shield-check"></i>
                                <div>
                                    <small>Estado</small>
                                    <strong class="<?= $estadoColor ?>"> <?= $estado ?> </strong>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <h3> <i class="bi bi-person"></i> Información personal </h3>
                            <button class="btn btn-edit-profile" data-bs-toggle="modal" data-bs-target="#editarPerfilModal"> <i class="bi bi-pencil"></i> Editar información </button>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <label class="form-label"> Nombre completo </label>
                                <input type="text" class="form-control profile-input" value="<?= htmlspecialchars($usuario['nombre']) ?>" readonly>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"> Username </label>
                                <input type="text" class="form-control profile-input" value="<?= htmlspecialchars($usuario['username']) ?>" readonly>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Correo electrónico </label>
                                <input type="email" class="form-control profile-input" value="<?= htmlspecialchars($usuario['email']) ?>" readonly>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"> Rol </label>
                                <input type="text" class="form-control profile-input" value="<?= $rol ?>" readonly>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"> Estado </label>
                                <input type="text" class="form-control profile-input" value="<?= $estado ?>" readonly>
                            </div>

                        </div>

                        <div class="profile-card mt-4">
                            <div class="profile-card-header">
                                <h3><i class="bi bi-journal-text"></i> Mis momentos</h3>
                            </div>
                            <div class="card-body">
                                <?php if (empty($momentos)): ?>
                                    <p class="text-muted">Aún no has registrado ningún momento. Usa el Magic Fill en el Dashboard.</p>
                                <?php else: ?>
                                    <div class="timeline-momentos">
                                        <?php foreach ($momentos as $m): ?>
                                            <div class="momento-item mb-3 pb-2 border-bottom">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="bi bi-clock"></i> <?= date('d/m/Y H:i', strtotime($m['fecha'] . ' ' . $m['hora'])) ?>
                                                    </small>
                                                    <span class="badge intensity-<?= $m['intensidad'] ?>">
                                                        <?php
                                                            $emoji = match($m['intensidad']) {
                                                                1 => '😢',
                                                                2 => '☹️',
                                                                3 => '🙂',
                                                                4 => '😊',
                                                                5 => '😍',
                                                                default => '😐'
                                                            };
                                                            echo $emoji . ' ' . $m['intensidad'] . '/5';
                                                        ?>
                                                    </span>
                                                </div>
                                                <p class="mb-0 mt-1"><?= htmlspecialchars($m['nota_emocional']) ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                </div>
        </div>
         
    </main>

    <div class="modal fade" id="editarPerfilModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content profile-modal">
                <div class="modal-header border-0">
                    <div>
                        <h2 class="modal-title"> Editar información </h2>
                        <p class="modal-subtitle"> Actualiza tu información personal. </p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"> </button>
                </div>

                <div class="modal-body">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"> Nombre completo </label>
                                <input type="text" class="form-control modal-input" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label"> Username </label>
                                <input type="text" class="form-control modal-input" name="username" value="<?= htmlspecialchars($usuario['username']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label"> Correo electrónico </label>
                                <input type="email" class="form-control modal-input" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label"> Nueva contraseña </label>
                                <div class="input-group">
                                    <input type="password" class="form-control modal-input" id="newPassword" name="password" placeholder="Opcional">
                                    <button class="btn btn-password" type="button" onclick="togglePassword()">
                                        <i class="bi bi-eye" id="iconPassword"></i>
                                    </button>

                                </div>

                            </div>
                            
                        </div>

                        <div class="modal-footer border-0 mt-4">
                            <button type="button" class="btn btn-cancel" data-bs-dismiss="modal"> Cancelar </button>
                            <button type="submit" class="btn btn-save"> Guardar cambios </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

    <?php include "../includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function togglePassword(){
            let input = document.getElementById("newPassword");
            let icon = document.getElementById("iconPassword");

            if(input.type === "password"){
                input.type = "text";
                icon.classList.remove("bi-eye");
                icon.classList.add("bi-eye-slash");

            }else{
                input.type = "password";
                icon.classList.remove("bi-eye-slash");
                icon.classList.add("bi-eye");
            }
        }
     </script>

</body>
</html>