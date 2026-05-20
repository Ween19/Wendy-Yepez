<?php
    session_start();
    require_once 'config/conexion.php';
    if(isset($_SESSION['usuario_id'])){

        header("Location: dashboard.php");
        exit();
    }

    $error="";
    $success="";

    if($_SERVER["REQUEST_METHOD"]=="POST"){
        $nombre=trim($_POST['nombre']);
        $username=trim($_POST['username']);
        $email=trim($_POST['email']);
        $password=trim($_POST['password']);
        $confirmar=trim($_POST['confirmar']);

        if(
            empty($nombre) ||
            empty($username) ||
            empty($email) ||
            empty($password) ||
            empty($confirmar)
        ){
            $error="Todos los campos son obligatorios.";
        }
        elseif(!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/",$nombre)){
            $error="El nombre solo admite letras.";
        }
        elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){
            $error="Correo inválido.";
        }
        elseif(strlen($password)<6){
            $error="La contraseña debe tener mínimo 6 caracteres.";
        }
        elseif($password!=$confirmar){
            $error="Las contraseñas no coinciden.";
        }
        else{
            $sql="SELECT id_usuario FROM usuarios WHERE email=:email OR username=:username";
            $stmt=$pdo->prepare($sql);
            $stmt->bindParam(":email",$email);
            $stmt->bindParam(":username",$username);

            $stmt->execute();

            if($stmt->rowCount()>0){
                $error="Usuario o correo ya existe.";
            }
            else{
                $hash=password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $sql="INSERT INTO usuarios (nombre, username, email, password, id_rol) VALUES ( :nombre, :username, :email, :password, 2)";
                $stmt=$pdo->prepare($sql);
                $stmt->bindParam(":nombre",$nombre);
                $stmt->bindParam(":username",$username);
                $stmt->bindParam(":email",$email);
                $stmt->bindParam(":password",$hash);

                if($stmt->execute()){
                    $idUsuario = $pdo->lastInsertId();

                    $_SESSION['usuario_id']=$idUsuario;
                    $_SESSION['nombre']=$nombre;
                    $_SESSION['username']=$username;
                    $_SESSION['id_rol']=2;
                    header("Location: dashboard.php");
                    exit();
                }else{
                    $error="Error al registrar.";
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

    <title>Crear cuenta | HABITA</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/estilos.css">

</head>

<body>
    <div class="container-fluid">
        <div class="row min-vh-100 justify-content-center align-items-center g-4">
            <div class="col-lg-4 col-md-6 col-11">
                <div class="auth-card">

                    <div class="mb-3">
                        <a href="index.php" class="back-btn">
                            <i class="bi bi-arrow-left"></i>
                            Volver
                        </a>
                    </div>

                    <h1 class="auth-title"> Create account  </h1>
                    <p class="auth-subtitle"> Comienza a construir hábitos positivos. </p>

                    <?php if($_SERVER["REQUEST_METHOD"]=="POST" && !empty($error)):?>

                    <div class="alert alert-danger">
                        <?= $error ?>
                    </div>
                    <?php endif;?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label"> Nombre </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-person"></i>
                                </span>

                                <input type="text" name="nombre" class="form-control" placeholder="Nombre completo" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"> Usuario </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-at"></i>
                                </span>

                                <input type="text" name="username" class="form-control" placeholder="Usuario" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"> Correo </label>

                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope"></i>
                                </span>

                                <input type="email" name="email" class="form-control" placeholder="Correo" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"> Contraseña </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>

                                <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
                            </div>
                        </div>

                        <div class="mb-4">

                            <label class="form-label"> Confirmar contraseña </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-shield-lock"></i>
                                </span>

                                <input type="password" name="confirmar" class="form-control" placeholder="Confirmar" required>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button class="btn btn-habita"> Crear cuenta </button>
                        </div>

                    </form>

                    <div class="text-center mt-4">
                        <span class="text-muted"> ¿Ya tienes cuenta? </span>
                        <a href="login.php" class="auth-link fw-bold"> Iniciar sesión </a>
                    </div>

                </div>

            </div>


            <div class="col-lg-3 col-md-4 col-11">
                <div class="auth-card">
                    <div class="text-center mb-4">
                        <h2 class="auth-title"> HABITA </h2>
                        <p class="text-muted"> Pequeños hábitos, grandes cambios </p>
                    </div>

                        <div class="credentials-box">
                            <h6> Beneficios</h6>
                            <hr>

                            <p> ✔ Seguimiento diario</p>
                            <p> ✔ Reportes y estadísticas </p>
                            <p> ✔ Control de hábitos </p>
                            <p> ✔ Rachas y productividad</p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>