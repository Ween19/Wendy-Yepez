<?php
require_once "../../libs/auth.php";
require_once "../../config/conexion.php";

    $id_usuario = $_SESSION['usuario_id'];

    $inicioSemana = date('Y-m-d', strtotime('monday this week'));
    $finSemana    = date('Y-m-d', strtotime('sunday this week'));

    $diasSemana = [];
    for($i=0; $i<7; $i++){
        $diasSemana[] = date('Y-m-d', strtotime("$inicioSemana +$i day"));
    }

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
    LEFT JOIN metas_config mc
        ON mc.id_habito = h.id_habito
    WHERE h.id_usuario = :uid
    AND h.estado = 1
    ORDER BY h.nombre ASC
    ";

    $stmt = $pdo->prepare($sqlHabitos);
    $stmt->execute([':uid' => $id_usuario]);
    $habitos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $metasDias = [];

    $sqlDias = "
    SELECT id_meta, dia
    FROM metas_dias
    ";

    $stmtDias = $pdo->query($sqlDias);

    while($d = $stmtDias->fetch(PDO::FETCH_ASSOC)){
        $metasDias[$d['id_meta']][] = $d['dia'];
    }

    $sqlHistorial = "
    SELECT
        id_habito,
        fecha,
        estado,
        valor_real
    FROM historial_habitos
    WHERE id_usuario = :uid
    AND fecha BETWEEN :ini AND :fin
    ";

    $stmtHist = $pdo->prepare($sqlHistorial);

    $stmtHist->execute([
        ':uid' => $id_usuario,
        ':ini' => $inicioSemana,
        ':fin' => $finSemana
    ]);

    $historial = [];

    while($r = $stmtHist->fetch(PDO::FETCH_ASSOC)){
        $historial[$r['id_habito']][$r['fecha']] = $r;
    }

    $resumen = [];

    foreach($habitos as $h){

        $esperados = 0;
        $completados = 0;

        foreach($diasSemana as $fecha){

            $diaLetra = strtoupper(substr(date('D', strtotime($fecha)), 0, 1));

            $debe = false;

            if(
                $h['direccion_habito'] === 'construir'
                &&
                $h['clase_habito'] === 'consciente'
            ){

                if($h['frecuencia'] === 'diaria'){
                    $debe = true;
                }

                elseif(
                    $h['frecuencia'] === 'semanal'
                    &&
                    isset($metasDias[$h['id_meta']])
                ){
                    $debe = in_array($diaLetra, $metasDias[$h['id_meta']]);
                }
            }

            elseif($h['direccion_habito'] === 'romper'){
                $debe = true;
            }

            elseif($h['clase_habito'] === 'inconsciente'){
                $debe = true;
            }

            if($debe){

                $esperados++;

                if(
                    isset($historial[$h['id_habito']][$fecha])
                    &&
                    $historial[$h['id_habito']][$fecha]['estado'] == 1
                ){
                    $completados++;
                }
            }
        }

        $porcentaje = $esperados > 0
            ? round(($completados / $esperados) * 100)
            : 0;

        $resumen[] = [
            'habito' => $h,
            'esperados' => $esperados,
            'completados' => $completados,
            'porcentaje' => $porcentaje
        ];
    }


    $statsDiarios = [];

    foreach($diasSemana as $fecha){
        $total = 0;
        $done  = 0;
        
        foreach($habitos as $h){
            $diaLetra = strtoupper(substr(date('D', strtotime($fecha)), 0, 1));
            
            $debe = false;
            
            if($h['direccion_habito'] === 'construir' && $h['clase_habito'] === 'consciente'){
                if($h['frecuencia'] === 'diaria'){
                    $debe = true;
                } elseif($h['frecuencia'] === 'semanal' && isset($metasDias[$h['id_meta']])){
                    $debe = in_array($diaLetra, $metasDias[$h['id_meta']]);
                }
            } elseif($h['direccion_habito'] === 'romper' || $h['clase_habito'] === 'inconsciente'){
                $debe = true;
            }
            
            if($debe){
                $total++;
                
                if(isset($historial[$h['id_habito']][$fecha]) && $historial[$h['id_habito']][$fecha]['estado'] == 1){
                    $done++;
                }
            }
        }
        
        $statsDiarios[$fecha] = [
            'total' => $total,
            'done'  => $done,
            'pct'   => $total > 0 ? round(($done / $total) * 100) : 0
        ];
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reportes | Habita</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../css/estilos.css">

</head>

<body class="dashboard-body">

    <?php include "../../includes/header.php"; ?>
    <?php include "../../includes/sidebar.php"; ?>

    <main class="content-wrapper">

        <div class="gr-header">
            <h1>Reportes</h1>
            <p>Analiza tu progreso semanal.</p>
        </div>

        <section class="gr-section">

            <div class="gr-section-title">
                Cumplimiento semanal
            </div>

            <div class="gr-week-grid">

                <?php foreach($statsDiarios as $fecha => $s): ?>

                <div class="gr-day-card">

                    <span class="gr-day">
                        <?= ucfirst(strftime('%a', strtotime($fecha))) ?>
                    </span>

                    <strong>
                        <?= $s['pct'] ?>%
                    </strong>

                    <small>
                        <?= $s['done'] ?>/<?= $s['total'] ?>
                    </small>

                </div>

                <?php endforeach; ?>

            </div>
        </section>

        <section class="gr-section">

            <div class="gr-section-title">
                Resumen de hábitos
            </div>

            <div class="gr-summary-list">

                <?php foreach($resumen as $r): ?>

                <div class="gr-summary-item">

                    <div>
                        <h4><?= htmlspecialchars($r['habito']['nombre']) ?></h4>

                        <small>
                            <?= $r['completados'] ?>/<?= $r['esperados'] ?> días
                        </small>
                    </div>

                    <div class="gr-badge">
                        <?= $r['porcentaje'] ?>%
                    </div>

                </div>

                <?php endforeach; ?>

            </div>

        </section>

        <section class="gr-section">

            <div class="gr-section-title">
                Evolución semanal
            </div>

            <div class="table-responsive">

                <table class="table gr-table">

                    <thead>

                        <tr>
                            <th>Hábito</th>

                            <?php foreach($diasSemana as $fecha): ?>

                            <th>
                                <?= date('d/m', strtotime($fecha)) ?>
                            </th>

                            <?php endforeach; ?>

                            <th>%</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach($resumen as $r): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($r['habito']['nombre']) ?>
                            </td>

                            <?php foreach($diasSemana as $fecha): ?>

                            <td>

                                <?php
                                $ok =
                                    isset($historial[$r['habito']['id_habito']][$fecha])
                                    &&
                                    $historial[$r['habito']['id_habito']][$fecha]['estado'] == 1;
                                ?>

                                <?= $ok ? '✅' : '❌' ?>

                            </td>

                            <?php endforeach; ?>

                            <td>
                                <?= $r['porcentaje'] ?>%
                            </td>

                        </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

    <?php include "../../includes/footer.php"; ?>

</body>
</html>