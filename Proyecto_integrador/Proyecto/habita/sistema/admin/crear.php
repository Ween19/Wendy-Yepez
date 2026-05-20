<?php

require_once '../../libs/admin.php';
require_once '../../config/conexion.php';

    $error = "";
    $success = "";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $nombre             = trim($_POST['nombre']);
        $username           = trim($_POST['username']);
        $email              = trim($_POST['email']);
        $password           = trim($_POST['password']);
        $confirm_password   = trim($_POST['confirm_password']);
        $id_rol             = intval($_POST['id_rol']);
        $estado             = intval($_POST['estado']);

        if (
            empty($nombre) ||
            empty($username) ||
            empty($email) ||
            empty($password) ||
            empty($confirm_password)
        ) {
            $error = "Todos los campos son obligatorios.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Correo electrónico inválido.";

        } elseif (strlen($password) < 6) {
            $error = "La contraseña debe tener mínimo 6 caracteres.";

        } elseif ($password !== $confirm_password) {
            $error = "Las contraseñas no coinciden.";

        } else {

            $sqlValidate = "SELECT id_usuario FROM usuarios WHERE email = :email OR username = :username LIMIT 1";
            $stmtValidate = $pdo->prepare($sqlValidate);
            $stmtValidate->bindParam(':email', $email);
            $stmtValidate->bindParam(':username', $username);

            $stmtValidate->execute();

            if ($stmtValidate->rowCount() > 0) {
                $error = "El username o correo ya existe.";

            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $sqlInsert = "INSERT INTO usuarios (nombre, username, email, password, id_rol, estado) VALUES ( :nombre, :username, :email, :password, :id_rol, :estado )";
                $stmtInsert = $pdo->prepare($sqlInsert);

                $stmtInsert->bindParam(':nombre', $nombre);
                $stmtInsert->bindParam(':username', $username);
                $stmtInsert->bindParam(':email', $email);
                $stmtInsert->bindParam(':password', $passwordHash);
                $stmtInsert->bindParam(':id_rol', $id_rol);
                $stmtInsert->bindParam(':estado', $estado);

                if ($stmtInsert->execute()) {

                    header("Location: listar.php");
                    exit();
                } else {
                    $error = "Error al crear usuario.";
                }
            }
        }
    }

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nuevo usuario | Habita</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../css/estilos.css">

</head>

<body class="modal-page">
    <div class="edit-modal-container">
        <div class="edit-modal-header">
            <h2>Nuevo usuario</h2>
            <a href="listar.php" class="close-modal"> <i class="bi bi-x-lg"></i></a>
        </div>

        <?php if(!empty($error)): ?>
            <div class="alert alert-danger">
                <?= $error; ?>
            </div>

        <?php endif; ?>

        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Nombre completo
                    </label>
                    <input type="text" name="nombre" class="form-control custom-input" placeholder="Ej. Juan Pérez" required >
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Username
                    </label>
                    <input type="text" name="username" class="form-control custom-input" placeholder="Ej. juanperez"required >
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Correo electrónico
                    </label>
                    <input type="email" name="email" class="form-control custom-input" placeholder="Ej. correo@gmail.com" required >
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Rol de usuario
                    </label>
                    <select name="id_rol" class="form-select custom-input" required >

                        <option value=""> Seleccionar rol </option>
                        <option value="1"> Administrador</option>
                        <option value="2">  Usuario </option>

                    </select>

                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Contraseña
                    </label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control custom-input" placeholder="Mínimo 6 caracteres" required >

                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password','icon1')" >
                            <i class="bi bi-eye" id="icon1"></i>
                        </button>
                    </div>

                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Confirmar contraseña
                    </label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control custom-input" placeholder="Repite la contraseña" required >

                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password','icon2')" >
                            <i class="bi bi-eye" id="icon2"></i>
                        </button>

                    </div>

                </div>

                <div class="col-md-6 mb-4">
                    <label class="form-label">
                        Estado
                    </label>
                    <select name="estado" class="form-select custom-input" required >
                       
                        <option value=""> Seleccionar estado</option>
                        <option value="1"> Activo </option>
                        <option value="0"> Inactivo </option>

                    </select>

                </div>

            </div>

            <hr class="border-secondary">

            <div class="edit-buttons"> <a href="listar.php" class="btn-cancel"> Cancelar </a>
                <button type="submit" class="btn-save">  Crear usuario </button>
            </div>

        </form>

    </div>

<script>

    function togglePassword(inputId, iconId){

        let input = document.getElementById(inputId);
        let icon = document.getElementById(iconId);

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