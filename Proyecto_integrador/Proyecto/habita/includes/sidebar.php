<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';

?>

<aside id="sidebar" class="sidebar">
    <div class="sidebar-logo">
        <img src="/HABITA/assets/isologo.png" alt="Habita">
        <div>
            <h3>Habita</h3>
            <p>Pequeños hábitos,<br>grandes cambios</p>
        </div>
    </div>

    <small class="menu-title">MENÚ</small>
    <ul class="sidebar-menu">
        <li>
            <a href="/HABITA/dashboard.php" class="active">
                <i class="bi bi-house-fill"></i> Dashboard
            </a>
        </li>

        <li>
            <a href="/HABITA/sistema/habitos/listar_habitos.php">
                <i class="bi bi-calendar-check"></i> Mis hábitos
            </a>
        </li>

        <li>
            <a href="/HABITA/sistema/reportes/graficas.php">
                <i class="bi bi-bar-chart"></i> Reportes
            </a>
        </li>

        <li>
            <a href="/HABITA/sistema/perfil.php">
                <i class="bi bi-person"></i> Perfil
            </a>
        </li>

        <?php if($_SESSION['id_rol']==1): ?>

        <li>
            <a href="/HABITA/sistema/admin/listar.php">
                <i class="bi bi-people"></i> Usuarios
            </a>
        </li>

        <?php endif; ?>

    </ul>

    <div class="sidebar-user">
        <div class="user-avatar">
            <?= strtoupper(substr($_SESSION['nombre'],0,1)) ?>
        </div>

        <div>
            <strong><?= $_SESSION['nombre'] ?></strong>
            <small>
                <?= $_SESSION['id_rol']==1 ? "Administrador" : "Usuario" ?>
            </small>
        </div>

    </div>

    <a href="/HABITA/logout.php" class="logout-btn">
        <i class="bi bi-box-arrow-right"></i> Cerrar sesión
    </a>

</aside>

<div id="sidebar-overlay"></div>