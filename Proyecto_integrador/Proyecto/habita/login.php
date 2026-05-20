<?php
    session_start();
    require_once 'config/conexion.php';

    if (isset($_SESSION['usuario_id'])) {

        header("Location: dashboard.php");
        exit();
    }

    $error = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Todos los campos son obligatorios.";
    } else { 
        $sql = "SELECT * FROM usuarios WHERE (username = :username OR email = :username) AND estado = 1 LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $usuario = $stmt->fetch();

        if ($usuario) {
            if (password_verify($password, $usuario['password'])) {
                $sqlUpdate = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = :id_usuario";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->bindParam(
                    ':id_usuario',
                    $usuario['id_usuario']
                );

                $stmtUpdate->execute();

                $_SESSION['usuario_id'] = $usuario['id_usuario'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['username'] = $usuario['username'];
                $_SESSION['id_rol'] = $usuario['id_rol'];

                header("Location: dashboard.php");
                exit();

            } else {
                $error = "Contraseña incorrecta.";
            }

        } else {
            $error = "Usuario o email incorrecto.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0" >

    <title>Login | HABITA</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" >
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" >
    <link rel="stylesheet" href="css/estilos.css" >

</head>

<body>
<div class="container-fluid">
    <div class="row min-vh-100 justify-content-center align-items-center g-4">
        <div class="col-lg-4 col-md-6 col-11">
            <div class="auth-card">
                    
                <div class="mb-3">
                    <a href="index.php" class="back-btn"> <i class="bi bi-arrow-left"></i> Volver</a>
                </div>

                <h1 class="auth-title"> Sign in </h1>
                <p class="auth-subtitle"> Inicia sesión con tu usuario y contraseña.</p>

                <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($error)) : ?>
                    <div class="alert alert-danger">
                        <?= $error; ?>
                    </div>
                <?php endif; ?>


                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label"> Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person"></i>
                            </span>

                            <input type="text" name="username" class="form-control" placeholder="Ingrese su usuario" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label"> Contraseña </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock"></i> 
                            </span>

                            <input type="password" name="password" id="password" class="form-control" placeholder="Ingrese su contraseña" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="mostrarPassword()">
                                <i class="bi bi-eye" id="iconPassword"></i>
                            </button>
                        </div>

                    </div>

                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-habita" > Log in </button>
                    </div>
                </form>

                <div class="text-center">
                    <span class="text-muted"> ¿No tienes cuenta? </span>
                    <a href="sign_up.php" class="auth-link fw-bold"> Regístrate aquí </a>
                </div>
            </div>
        </div>


        <div class="col-lg-3 col-md-5 col-11">
            <div class="auth-card">
                <div class="text-center mb-4">
                    <h2 class="auth-title"> HABITA </h2>
                    <p class="text-muted"> Plataforma de seguimiento de hábitos y productividad personal.</p>
                </div>

                <div class="credentials-box mb-4">
                    <strong> Credenciales de prueba </strong>
                    <hr>

                    <p class="mb-3">
                        <strong>Administrador</strong>
                        <br>
                        Usuario: admin
                        <br>
                        Contraseña: 123456
                    </p>

                    <p class="mb-0">
                        <strong>Usuario</strong>
                        <br>
                        Usuario: User
                        <br>
                        Contraseña: 123456
                    </p>

                </div>
            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>

    function mostrarPassword() {
        let password = document.getElementById("password");
        let icon = document.getElementById("iconPassword");

        if (password.type === "password") {
            password.type = "text";
            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");
        } else {
            password.type = "password";
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
        }
    }

</script>

</body>
</html>