<?php
require_once '../../libs/admin.php';
require_once '../../config/conexion.php';

    $sql = "SELECT * FROM usuarios ORDER BY fecha_registro DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $usuarios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Usuarios | HABITA</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../css/estilos.css">
    
</head>

<body class="dashboard-body">
    <?php include "../../includes/header.php"; ?>
    <?php include "../../includes/sidebar.php"; ?>
    <main class="content-wrapper">
        <div class="container-fluid">
            <div class="users-header">
                <div>
                    <h1 class="users-title"> Gestión de usuarios</h1>
                    <p class="users-subtitle"> Administra los usuarios registrados de la plataforma.</p>
                </div>

                <a href="crear.php" class="btn btn-new-user">
                    <i class="bi bi-plus-lg"></i> Nuevo usuario
                </a>

            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stats-card">
                        <div class="stats-icon blue">
                            <i class="bi bi-people"></i>
                        </div>

                        <div>
                            <small>Usuarios registrados</small>
                            <h2><?= count($usuarios) ?></h2>
                        </div>

                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stats-card">
                        <div class="stats-icon green">
                            <i class="bi bi-person-check"></i>
                        </div>

                        <div>
                            <small>Usuarios activos</small>
                            <h2> <?= count(array_filter($usuarios, fn($u) => $u['estado'] == 1)) ?> </h2>
                        </div>

                    </div>

                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stats-card">
                        <div class="stats-icon purple">
                            <i class="bi bi-shield-check"></i>
                        </div>

                        <div>
                            <small>Administradores</small> <h2> <?= count(array_filter($usuarios, fn($u) => $u['id_rol'] == 1)) ?> </h2>
                        </div>

                    </div>

                </div>

            </div>

            <div class="table-container">
                <table class="table align-middle users-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Fecha registro</th>
                            <th>Último acceso</th>
                            <th>Acciones</th>
                        </tr>

                    </thead>

                    <tbody>
                    <?php foreach($usuarios as $usuario): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="table-avatar">
                                        <?= strtoupper(substr($usuario['nombre'],0,1)) ?>
                                    </div>

                                    <div>
                                        <strong> <?= htmlspecialchars($usuario['nombre']) ?> </strong>

                                        <small> @<?= htmlspecialchars($usuario['username']) ?> </small>
                                    </div>
                                </div>

                            </td>

                            <td>
                                <?= htmlspecialchars($usuario['email']) ?>
                            </td>

                            <td>
                                <?php if($usuario['id_rol'] == 1): ?>
                                    <span class="badge-role admin">
                                        Administrador
                                    </span>

                                <?php else: ?>
                                    <span class="badge-role user">
                                        Usuario
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?php if($usuario['estado'] == 1): ?>
                                    <span class="status active">
                                        Activo
                                    </span>

                                <?php else: ?>
                                    <span class="status inactive">
                                        Inactivo
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?= date("d/m/Y", strtotime($usuario['fecha_registro'])) ?>
                            </td>

                            <td>
                                <?php if($usuario['ultimo_acceso']): ?>
                                    <?= date("d/m/Y H:i", strtotime($usuario['ultimo_acceso'])) ?>

                                <?php else: ?> Nunca
                                <?php endif; ?>

                            </td>

                            <td>
                                <div class="action-buttons">
                                    <a href="editar.php?id=<?= $usuario['id_usuario']; ?>" class="btn-action edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <a href="eliminar.php?id=<?= $usuario['id_usuario'] ?>" class="btn-action delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

    <?php include "../../includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>