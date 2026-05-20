<?php
require_once "libs/auth.php";
require_once "config/conexion.php";

    $id_usuario = $_SESSION['usuario_id'];

    $sqlHabitos = "
    SELECT
        h.id_habito,
        h.nombre,
        h.clase_habito,
        h.direccion_habito,
        h.fecha_ultima_recaida,
        mc.id_meta,
        mc.frecuencia,
        mc.cantidad,
        mc.unidad
    FROM habitos h
    LEFT JOIN metas_config mc ON mc.id_habito = h.id_habito
    WHERE h.id_usuario = :uid
    AND h.estado = 1
    ORDER BY h.direccion_habito DESC, h.nombre ASC
    ";

    $stmt = $pdo->prepare($sqlHabitos);
    $stmt->execute([':uid' => $id_usuario]);
    $todos_habitos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $metasDias = [];
    $sqlDias = "SELECT id_meta, dia FROM metas_dias";
    $stmtDias = $pdo->query($sqlDias);
    while ($d = $stmtDias->fetch(PDO::FETCH_ASSOC)) {
        $metasDias[$d['id_meta']][] = $d['dia'];
    }

    $hoy = date('Y-m-d');
    $diaLetra = strtoupper(substr(date('D'), 0, 1)); // L, M, X, J, V, S, D

    $habitos_hoy = [];
    $total_esperados = 0;
    $total_completados = 0;

    foreach ($todos_habitos as $h) {
        $debe = false;

        if ($h['direccion_habito'] === 'construir' && $h['clase_habito'] === 'consciente') {
            if ($h['frecuencia'] === 'diaria') {
                $debe = true;
            } elseif ($h['frecuencia'] === 'semanal' && isset($metasDias[$h['id_meta']])) {
                $debe = in_array($diaLetra, $metasDias[$h['id_meta']]);
            }
        }
        elseif ($h['direccion_habito'] === 'romper' || $h['clase_habito'] === 'inconsciente') {
            $debe = true;
        }

        if ($debe) {
            $total_esperados++;
            
            $sqlCheck = "SELECT estado, valor_real FROM historial_habitos 
                        WHERE id_habito = :id_habito AND id_usuario = :uid AND fecha = :fecha
                        LIMIT 1";
            $stmtCheck = $pdo->prepare($sqlCheck);
            $stmtCheck->execute([
                ':id_habito' => $h['id_habito'],
                ':uid' => $id_usuario,
                ':fecha' => $hoy
            ]);
            $registro_hoy = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            $completado_hoy = ($registro_hoy && $registro_hoy['estado'] == 1);
            
            if ($completado_hoy) {
                $total_completados++;
            }
            
            $habitos_hoy[] = [
                'id' => $h['id_habito'],
                'nombre' => $h['nombre'],
                'direccion' => $h['direccion_habito'],
                'clase' => $h['clase_habito'],
                'meta_cantidad' => $h['cantidad'] ?? null,
                'meta_unidad' => $h['unidad'] ?? null,
                'completado' => $completado_hoy,
                'valor_real' => $registro_hoy['valor_real'] ?? null,
                'ultima_recaida' => $h['fecha_ultima_recaida']
            ];
        }
    }

    $porcentaje_hoy = $total_esperados > 0 
        ? round(($total_completados / $total_esperados) * 100) 
        : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | Habita</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/estilos.css">
    
</head>

<body class="dashboard-body">
    <?php include "includes/header.php"; ?>
    <?php include "includes/sidebar.php"; ?>

    <div class="layout-container">
        <main class="content-wrapper">
            <div class="container-fluid">
                
                <div class="dashboard-container mb-5">
                    <h1 class="dashboard-title">Bienvenido, <?= htmlspecialchars($_SESSION['nombre']); ?></h1>
                    <p class="dashboard-subtitle">Hoy es <?= date('d/m/Y') ?></p>
                    
                    <div class="row justify-content-center mt-4">
                        <div class="col-md-6">
                            <div class="progress mb-2" style="height: 12px;">
                                <div class="progress-bar bg-success" style="width: <?= $porcentaje_hoy ?>%;"></div>
                            </div>
                            <p class="text-center">Progreso hoy: <strong><?= $porcentaje_hoy ?>%</strong> (<?= $total_completados ?>/<?= $total_esperados ?> hábitos)</p>
                        </div>
                    </div>
                </div>

                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"> ¿Qué acabas de hacer?</h5>
                        <form id="magicFillForm">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <input type="text" name="nota" class="form-control" placeholder="Ej. Me comí una galleta" required>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <select name="intensidad" class="form-select">
                                        <option value="1">😢 Muy mal</option>
                                        <option value="2">☹️ Mal</option>
                                        <option value="3" selected>🙂 Normal</option>
                                        <option value="4">😊 Bien</option>
                                        <option value="5">😍 Genial</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <button type="submit" class="btn btn-primary w-100">Registrar momento</button>
                                </div>
                            </div>
                        </form>
                        <div id="magicFillMsg" class="mt-2"></div>
                    </div>
                </div>

                <h3 class="mb-3"> Tus hábitos para hoy</h3>
                
                <?php if (empty($habitos_hoy)): ?>
                    <div class="alert alert-info">No tienes hábitos programados para hoy. ¡Disfruta tu día!</div>
                <?php else: ?>
                    <div class="row" id="habitosContainer">
                        <?php foreach ($habitos_hoy as $h): ?>
                            <div class="col-md-6 col-lg-4 mb-3" data-habito-id="<?= $h['id'] ?>">
                                <div class="card h-100 <?= $h['direccion'] == 'construir' ? 'border-success' : 'border-danger' ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($h['nombre']) ?></h5>
                                        
                                        <?php if ($h['direccion'] == 'construir' && $h['clase'] == 'consciente'): ?>
                                            <p class="card-text">
                                                Meta: <?= $h['meta_cantidad'] ?> <?= $h['meta_unidad'] ?>
                                            </p>
                                            <?php if ($h['completado']): ?>
                                                <div class="alert alert-success py-1">✅ Completado hoy</div>
                                            <?php else: ?>
                                                <button class="btn btn-success completar-btn" data-id="<?= $h['id'] ?>">
                                                    <i class="bi bi-check-circle"></i> Marcar como completado
                                                </button>
                                            <?php endif; ?>
                                            
                                        <?php elseif ($h['direccion'] == 'romper'): ?>
                                            <p class="card-text">
                                                Última recaída: <?= $h['ultima_recaida'] ? date('d/m/Y', strtotime($h['ultima_recaida'])) : 'Nunca' ?>
                                            </p>
                                            <?php if ($h['completado']): ?>
                                                <div class="alert alert-success py-1">✅ Día sin recaída registrado</div>
                                            <?php else: ?>
                                                <button class="btn btn-danger recaida-btn" data-id="<?= $h['id'] ?>">
                                                    <i class="bi bi-exclamation-triangle"></i> Registrar recaída
                                                </button>
                                            <?php endif; ?>
                                            
                                        <?php elseif ($h['clase'] == 'inconsciente'): ?>
                                            <p class="card-text text-muted">Hábito inconsciente (sin meta)</p>
                                            <?php if ($h['completado']): ?>
                                                <div class="alert alert-success py-1">✅ Registrado hoy</div>
                                            <?php else: ?>
                                                <button class="btn btn-info inconsciente-btn" data-id="<?= $h['id'] ?>">
                                                    <i class="bi bi-cloud"></i> Registrar ocurrencia
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
            </div>
        </main>
    </div>

    <?php include "includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>\

    <script>
        document.querySelectorAll('.completar-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.dataset.id;
                const formData = new FormData();
                formData.append('id_habito', id);
                
                const res = await fetch('sistema/reportes/marcar_completado.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error al marcar');
                }
            });
        });

        document.querySelectorAll('.recaida-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.dataset.id;
                const formData = new FormData();
                formData.append('id_habito', id);
                
                const res = await fetch('sistema/reportes/registrar_recaida.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error al registrar recaída');
                }
            });
        });

        document.querySelectorAll('.inconsciente-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.dataset.id;
                const formData = new FormData();
                formData.append('id_habito', id);
                
                const res = await fetch('sistema/reportes/registrar_inconsciente.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error al registrar');
                }
            });
        });

        document.getElementById('magicFillForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            const res = await fetch('sistema/reportes/registrar_momento.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            const msgDiv = document.getElementById('magicFillMsg');
            if (data.success) {
                msgDiv.innerHTML = '<div class="alert alert-success">✅ Momento registrado correctamente</div>';
                e.target.reset();
                setTimeout(() => msgDiv.innerHTML = '', 3000);
            } else {
                msgDiv.innerHTML = '<div class="alert alert-danger">❌ Error al registrar</div>';
            }
        });
    </script>

</body>
</html>