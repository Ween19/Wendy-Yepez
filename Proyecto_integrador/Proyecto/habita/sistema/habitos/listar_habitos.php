<?php
require_once "../../libs/auth.php";
require_once "../../config/conexion.php";

    $id_usuario = $_SESSION['usuario_id'];

    $sql = "
    SELECT
        h.id_habito,
        h.nombre,
        h.clase_habito,
        h.direccion_habito,
        h.dificultad,
        h.fecha_ultima_recaida,
        h.fecha_creacion,
        c.nombre_cat,
        mc.cantidad      AS meta_cantidad,
        mc.unidad        AS meta_unidad,
        mc.frecuencia    AS meta_frecuencia,
        COALESCE(
            (SELECT SUM(hh.valor_real)
            FROM historial_habitos hh
            WHERE hh.id_habito = h.id_habito
            AND hh.fecha = CURDATE()),
            0
        ) AS progreso_hoy,
        DATEDIFF(CURDATE(), h.fecha_ultima_recaida) AS dias_sin_recaida
    FROM habitos h
    LEFT JOIN categorias c      ON h.id_categoria  = c.id_categoria
    LEFT JOIN metas_config mc   ON mc.id_habito    = h.id_habito
    WHERE h.id_usuario = :uid
    AND h.estado = 1
    ORDER BY h.fecha_creacion DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $id_usuario]);
    $habitos = $stmt->fetchAll();

    $total  = count($habitos);
    $builds = count(array_filter($habitos, fn($h) => $h['direccion_habito'] === 'construir'));
    $breaks = count(array_filter($habitos, fn($h) => $h['direccion_habito'] === 'romper'));


    $msg_exito = $_GET['exito'] ?? '';

?>


<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mis hábitos | Habita</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/estilos.css">


</head>
<body class="dashboard-body">

    <?php include "../../includes/header.php"; ?>
    <?php include "../../includes/sidebar.php"; ?>

    <main class="content-wrapper">
        <div class="lh-page-header">
            <div class="lh-header-top">
                <h1 class="lh-page-title">Mis hábitos</h1>
                <p class="lh-page-sub">
                    Administra todos tus hábitos.
                </p>
            </div>

            <div class="lh-header-bottom">
                <div class="lh-stats">
                    <div class="lh-stat-card total">
                        <i class="bi bi-collection stat-icon"></i>
                        <span class="stat-num"><?= $total ?></span>
                        <span class="stat-label">Total</span>
                    </div>

                    <div class="lh-stat-card build">
                        <i class="bi bi-graph-up-arrow stat-icon"></i>
                        <span class="stat-num"><?= $builds ?></span>
                        <span class="stat-label">Construyendo</span>
                    </div>

                    <div class="lh-stat-card brk">
                        <i class="bi bi-slash-circle stat-icon"></i>
                        <span class="stat-num"><?= $breaks ?></span>
                        <span class="stat-label">Rompiendo</span>
                    </div>
                </div>

                <a href="crear_habitos.php" class="lh-btn-new">
                    <i class="bi bi-plus-lg"></i>
                    Nuevo hábito
                </a>

            </div>
        </div>

            <?php if($msg_exito): ?>

            <div class="lh-alert">
                <i class="bi bi-check-circle-fill"></i>
                <?php
                if($msg_exito === 'actualizado')  echo 'Hábito actualizado correctamente.';
                elseif($msg_exito === 'eliminado') echo 'Hábito eliminado correctamente.';
                ?>
            </div>

            <?php endif; ?>

            <?php if(empty($habitos)): ?>
            <div class="lh-empty">
                <div class="lh-empty-icon"><i class="bi bi-journal-x"></i></div>
                <h3>Aún no tienes hábitos</h3>
                <p>Crea tu primer hábito y comienza a crecer.</p>
            </div>
            <?php else: ?>

            <div class="lh-grid">
                <?php foreach($habitos as $h):
                    $isB = $h['direccion_habito'] === 'construir';
                    $pct = 0;
                    if($isB && $h['meta_cantidad'] > 0){
                    $pct = min(100, round(($h['progreso_hoy'] / $h['meta_cantidad']) * 100));
                    } ?>

                <div class="hb-card <?= $isB ? 'build' : 'break' ?>">
                    <div class="hb-card-top">
                        <div class="hb-dir-badge <?= $isB ? 'build' : 'break' ?>">
                            <i class="bi bi-<?= $isB ? 'graph-up-arrow' : 'slash-circle' ?>"></i>
                            <?= $isB ? 'Construir' : 'Romper' ?>
                        </div>

                        <h3 class="hb-card-name"><?= htmlspecialchars($h['nombre']) ?></h3>
                        <div class="hb-card-cat">
                            <i class="bi bi-tag"></i>
                            <?= htmlspecialchars($h['nombre_cat'] ?? '—') ?>
                        </div>

                        <div class="hb-card-meta">
                            <span class="hb-meta-chip">
                                <i class="bi bi-<?= $h['clase_habito'] === 'consciente' ? 'brain' : 'cloud' ?>"></i>
                                <?= ucfirst($h['clase_habito']) ?>
                            </span>
                            <?php if($h['dificultad']): ?>

                            <span class="hb-meta-chip">
                                <span class="hb-diff-dot <?= $h['dificultad'] ?>"></span>
                                <?= ucfirst($h['dificultad']) ?>
                            </span>
                            <?php endif; ?>

                            <?php if($h['meta_frecuencia']): ?>
                            <span class="hb-meta-chip accent">
                                <i class="bi bi-calendar3"></i>
                                <?= ucfirst($h['meta_frecuencia']) ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <?php if($isB && $h['clase_habito'] === 'consciente' && $h['meta_cantidad']): ?>
                        <div class="hb-progress-wrap">
                            <div class="hb-progress-label">
                                <span>Hoy: <?= $h['progreso_hoy'] ?> / <?= $h['meta_cantidad'] ?> <?= htmlspecialchars($h['meta_unidad'] ?? '') ?></span>
                                <span><?= $pct ?>%</span>
                            </div>
                            <div class="hb-progress-bar-bg">
                                <div class="hb-progress-bar-fill" style="width:<?= $pct ?>%"></div>
                            </div>
                        </div>

                        <?php elseif($isB && $h['clase_habito'] === 'inconsciente'): ?>
                        <p style="font-size:.74rem;color:var(--texto-secundario);margin:0">Sin meta cuantitativa</p>
                        <?php elseif(!$isB): ?>
                        <?php $streak = $h['dias_sin_recaida'] ?? 0; ?>
                        <div class="hb-streak">
                            <i class="bi bi-fire"></i>
                            <?= $streak ?> día<?= $streak !== 1 ? 's' : '' ?> sin recaída
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="hb-card-actions">
                        <a href="actualizar_habito.php?id=<?= $h['id_habito'] ?>" class="hb-action-btn edit">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                        <a href="eliminar_habito.php?id=<?= $h['id_habito'] ?>"
                            class="hb-action-btn del"
                            onclick="return confirm('¿Eliminar el hábito «<?= htmlspecialchars(addslashes($h['nombre'])) ?>»? Esta acción no se puede deshacer.')">
                            <i class="bi bi-trash3"></i> Eliminar
                        </a>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

    </main>

    <?php include "../../includes/footer.php"; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>
