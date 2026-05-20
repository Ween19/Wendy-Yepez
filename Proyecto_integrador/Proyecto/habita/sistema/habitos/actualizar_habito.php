<?php
require_once "../../libs/auth.php";
require_once "../../config/conexion.php";

    $id_usuario = $_SESSION['usuario_id'];
    $id_habito  = intval($_GET['id'] ?? 0);

    if(!$id_habito){
        header("Location: listar_habitos.php");
        exit;
    }
    $sql = "
    SELECT
        h.id_habito,
        h.nombre,
        h.id_categoria,
        h.clase_habito,
        h.direccion_habito,
        h.dificultad,
        h.fecha_ultima_recaida,
        mc.id_meta,
        mc.cantidad      AS meta_cantidad,
        mc.unidad        AS meta_unidad,
        mc.frecuencia    AS meta_frecuencia,
        mc.fecha_inicio  AS meta_fecha_inicio,
        mc.fecha_fin     AS meta_fecha_fin
    FROM habitos h
    LEFT JOIN metas_config mc ON mc.id_habito = h.id_habito
    WHERE h.id_habito  = :id
    AND h.id_usuario = :uid
    AND h.estado     = 1
    LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id_habito, ':uid' => $id_usuario]);
    $h = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$h){
        header("Location: listar_habitos.php");
        exit;
    }

    $dias_marcados = [];
    if($h['id_meta']){
        $stmtD = $pdo->prepare("SELECT dia FROM metas_dias WHERE id_meta = :id");
        $stmtD->execute([':id' => $h['id_meta']]);
        $dias_marcados = array_column($stmtD->fetchAll(PDO::FETCH_ASSOC), 'dia');
    }

    $sqlCats = "SELECT * FROM categorias WHERE es_popular = 1 OR id_usuario = :uid ORDER BY nombre_cat ASC";
    $stmtCats = $pdo->prepare($sqlCats);
    $stmtCats->execute([':uid' => $id_usuario]);
    $categorias = $stmtCats->fetchAll();

    $error = '';

    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $nombre       = trim($_POST['nombre']      ?? '');
        $id_categoria = intval($_POST['categoria'] ?? 0);
        $clase_habito = $_POST['clase_habito']     ?? '';
        $direccion    = $_POST['direccion']         ?? '';
        $dificultad   = $_POST['dificultad']       ?? null;

        if(!$nombre || !$id_categoria || !$clase_habito || !$direccion){
            $error = 'Por favor completa todos los campos obligatorios.';
        } else {
            try {
                $pdo->beginTransaction();

                $pdo->prepare("
                    UPDATE habitos SET
                        nombre           = :nombre,
                        id_categoria     = :cat,
                        clase_habito     = :clase,
                        direccion_habito = :dir,
                        dificultad       = :dif
                    WHERE id_habito  = :id
                    AND id_usuario = :uid
                ")->execute([
                    ':nombre' => $nombre,
                    ':cat'    => $id_categoria,
                    ':clase'  => $clase_habito,
                    ':dir'    => $direccion,
                    ':dif'    => $dificultad ?: null,
                    ':id'     => $id_habito,
                    ':uid'    => $id_usuario
                ]);

                $oldMeta = $pdo->prepare("SELECT id_meta FROM metas_config WHERE id_habito = :id");
                $oldMeta->execute([':id' => $id_habito]);
                $metaRow = $oldMeta->fetch();
                if($metaRow){
                    $pdo->prepare("DELETE FROM metas_dias   WHERE id_meta = :m")->execute([':m' => $metaRow['id_meta']]);
                    $pdo->prepare("DELETE FROM metas_config WHERE id_meta = :m")->execute([':m' => $metaRow['id_meta']]);
                }

                if($direccion === 'construir'){
                    $pdo->prepare("UPDATE habitos SET fecha_ultima_recaida = NULL WHERE id_habito = :id")
                        ->execute([':id' => $id_habito]);
                }

                if($direccion === 'construir' && $clase_habito === 'consciente'){
                    $meta_cantidad = $_POST['meta_cantidad'] ?? null;
                    $unidad        = trim($_POST['unidad']   ?? '');
                    $frecuencia    = $_POST['frecuencia']    ?? 'diaria';
                    $fecha_inicio  = $_POST['fecha_inicio']  ?? null;
                    $fecha_fin     = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;

                    $pdo->prepare("
                        INSERT INTO metas_config(id_habito, frecuencia, cantidad, unidad, fecha_inicio, fecha_fin)
                        VALUES(:hab, :freq, :cant, :uni, :ini, :fin)
                    ")->execute([
                        ':hab'  => $id_habito,
                        ':freq' => $frecuencia,
                        ':cant' => $meta_cantidad ?: null,
                        ':uni'  => $unidad ?: null,
                        ':ini'  => $fecha_inicio ?: null,
                        ':fin'  => $fecha_fin
                    ]);
                    $id_meta_nuevo = $pdo->lastInsertId();

                    if(isset($_POST['dias']) && is_array($_POST['dias'])){
                        $stmtDia = $pdo->prepare("INSERT INTO metas_dias(id_meta, dia) VALUES(:m, :d)");
                        foreach($_POST['dias'] as $dia){
                            $stmtDia->execute([':m' => $id_meta_nuevo, ':d' => $dia]);
                        }
                    }
                }

                if($direccion === 'romper' && !empty($_POST['ultima_recaida'])){
                    $pdo->prepare("UPDATE habitos SET fecha_ultima_recaida = :f WHERE id_habito = :id")
                        ->execute([':f' => $_POST['ultima_recaida'], ':id' => $id_habito]);
                }

                $pdo->commit();
                header("Location: listar_habitos.php?exito=actualizado");
                exit;

            } catch(PDOException $e){
                $pdo->rollBack();
                $error = 'Error al guardar: ' . $e->getMessage();
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar hábito | Habita</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/estilos.css">

<style>

</style>
</head>
<body class="dashboard-body">

    <?php include "../../includes/header.php"; ?>
    <?php include "../../includes/sidebar.php"; ?>

    <main class="content-wrapper">
        <div class="eh-wrapper">
        <div class="eh-card">
            <div class="eh-header">
                <div>
                    <h2 class="eh-title">Editar hábito</h2>
                    <p class="eh-subtitle">Modifica los datos de tu hábito.</p>
                </div>
                <a href="listar_habitos.php" class="eh-close" title="Volver">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>

                <?php if($error): ?>
                <div class="eh-error">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="hb-label">Nombre del hábito</label>
                        <div class="hb-input-wrap">
                            <i class="bi bi-pencil-square hb-input-icon"></i>
                            <input type="text" name="nombre" class="hb-input" placeholder="Ej. Tomar 2 litros de agua" value="<?= htmlspecialchars($h['nombre']) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="hb-label">Categoría</label>
                        <div class="hb-input-wrap">
                            <i class="bi bi-tag hb-input-icon"></i>

                            <select name="categoria" class="hb-input" required>
                                <option value="">Selecciona una categoría</option>
                                <?php foreach($categorias as $cat): ?>

                                <option value="<?= $cat['id_categoria'] ?>"
                                    <?= $cat['id_categoria'] == $h['id_categoria'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nombre_cat']) ?>
                                </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <button type="button" class="hb-new-cat" data-bs-toggle="modal" data-bs-target="#categoriaModal">
                            <i class="bi bi-plus-lg"></i> Nueva categoría
                        </button>

                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="hb-label">Clase de hábito</label>
                        <div class="hb-toggle-group">
                            <label class="hb-toggle-label clase">
                                <input type="radio" name="clase_habito" value="consciente" id="edit_consciente"
                                        <?= $h['clase_habito'] === 'consciente' ? 'checked' : '' ?>>
                                <span class="hb-toggle-btn"><i class="bi bi-brain"></i> Consciente</span>
                            </label>

                            <label class="hb-toggle-label clase">
                                <input type="radio" name="clase_habito" value="inconsciente" id="edit_inconsciente"
                                        <?= $h['clase_habito'] === 'inconsciente' ? 'checked' : '' ?>>
                                <span class="hb-toggle-btn"><i class="bi bi-cloud"></i> Inconsciente</span>
                            </label>

                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="hb-label">Dirección</label>
                        <div class="hb-toggle-group">
                            <label class="hb-toggle-label build">
                                <input type="radio" name="direccion" value="construir" id="edit_construir"
                                        <?= $h['direccion_habito'] === 'construir' ? 'checked' : '' ?>>
                                <span class="hb-toggle-btn"><i class="bi bi-graph-up-arrow"></i> Construir</span>
                            </label>

                            <label class="hb-toggle-label break">
                                <input type="radio" name="direccion" value="romper" id="edit_romper"
                                        <?= $h['direccion_habito'] === 'romper' ? 'checked' : '' ?>>
                                <span class="hb-toggle-btn"><i class="bi bi-slash-circle"></i> Romper</span>
                            </label>

                        </div>
                    </div>
                </div>


                <div id="buildCard" style="display:none;">
                    <div class="hb-section-card">
                        <div class="hb-card-header">
                            <div class="hb-card-icon green"><i class="bi bi-graph-up-arrow"></i></div>
                            <div>
                                <p class="hb-card-title">Construir un hábito</p>
                                <p class="hb-card-subtitle">Define una meta clara y alcanzable.</p>
                            </div>
                        </div>

                        <div class="hb-card-body">


                            <div class="mb-3">
                            <label class="hb-label">Dificultad</label>
                                <div class="hb-diff-group">
                                    <?php foreach(['baja','media','alta'] as $dif): ?>

                                    <label class="hb-diff-label <?= $dif ?>">
                                        <input type="radio" name="dificultad" value="<?= $dif ?>"
                                                <?= $h['dificultad'] === $dif ? 'checked' : '' ?>>
                                        <span class="hb-diff-btn">
                                            <span class="hb-diff-dot"></span>
                                            <?= ucfirst($dif) ?>
                                        </span>
                                    </label>
                                    <?php endforeach; ?>

                                </div>
                            </div>


                            <div id="buildContent">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="hb-label">Meta (cantidad)</label>
                                        <div class="hb-input-wrap">
                                            <i class="bi bi-bullseye hb-input-icon"></i>
                                            <input type="number" name="meta_cantidad" class="hb-input" min="1" placeholder="Ej. 30" value="<?= htmlspecialchars($h['meta_cantidad'] ?? '') ?>">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="hb-label">Unidad</label>
                                        <div class="hb-input-wrap">
                                            <i class="bi bi-rulers hb-input-icon"></i>
                                            <input type="text" name="unidad" class="hb-input"
                                                placeholder="Ej. minutos, vasos, páginas"
                                                value="<?= htmlspecialchars($h['meta_unidad'] ?? '') ?>">
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="hb-label">Frecuencia</label>
                                        <div class="hb-toggle-group">
                                            <?php
                                            $freqActual = $h['meta_frecuencia'] ?? 'diaria';
                                            foreach(['diaria'=>'Diaria','semanal'=>'Semanal','mensual'=>'Mensual'] as $val => $label):
                                            ?>
                                            <label class="hb-toggle-label">
                                                <input type="radio" name="frecuencia" value="<?= $val ?>" <?= $freqActual === $val ? 'checked' : '' ?>>
                                                <span class="hb-toggle-btn"><?= $label ?></span>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="col-12" id="diasContainer"></div>
                                    <div class="col-md-6">
                                        <label class="hb-label">Fecha inicio</label>
                                        <div class="hb-input-wrap">
                                            <i class="bi bi-calendar-check hb-input-icon"></i>
                                            <input type="date" name="fecha_inicio" class="hb-input" value="<?= htmlspecialchars($h['meta_fecha_inicio'] ?? '') ?>">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="hb-label">Fecha fin <small style="color:var(--texto-secundario)">(opcional)</small></label>
                                        <div class="hb-input-wrap">
                                            <i class="bi bi-calendar-x hb-input-icon"></i>
                                            <input type="date" name="fecha_fin" class="hb-input" value="<?= htmlspecialchars($h['meta_fecha_fin'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div id="inconscienteMsg" class="hb-inconsciente-msg" style="display:none;">
                                <i class="bi bi-info-circle-fill" style="color:var(--azul-claro);font-size:1.1rem"></i>
                                Los hábitos inconscientes no requieren una meta cuantitativa. Solo haz seguimiento de si lo practicaste o no.
                            </div>

                        </div>
                    </div>
                </div>


                <div id="breakCard" style="display:none;">
                    <div class="hb-section-card">
                        <div class="hb-card-header">
                            <div class="hb-card-icon red"><i class="bi bi-slash-circle"></i></div>
                            <div>
                                <p class="hb-card-title">Romper un hábito</p>
                                <p class="hb-card-subtitle">Identifica y elimina lo que te detiene.</p>
                            </div>
                        </div>

                        <div class="hb-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="hb-label">Última vez que ocurrió</label>
                                    <div class="hb-input-wrap">
                                        <i class="bi bi-calendar-x hb-input-icon"></i>
                                        <input type="date" name="ultima_recaida" class="hb-input" id="ultimaRecaida" value="<?= htmlspecialchars($h['fecha_ultima_recaida'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="hb-label">Días sin recaída</label>
                                    <div class="hb-days-badge">
                                        <i class="bi bi-fire"></i>
                                        <span id="diasSinRecaida">
                                            <?php
                                            if($h['fecha_ultima_recaida']){
                                                $diff = floor((time() - strtotime($h['fecha_ultima_recaida'])) / 86400);
                                                echo $diff . ' día' . ($diff !== 1 ? 's' : '');
                                            } else {
                                                echo '— días';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <p style="font-size:.72rem;color:var(--texto-secundario);margin:6px 0 0">Se actualiza automáticamente</p>
                                </div>

                                <div class="col-12">
                                    <label class="hb-label">Dificultad</label>
                                    <div class="hb-diff-group">
                                        <?php foreach(['baja','media','alta'] as $dif): ?>
                                        <label class="hb-diff-label <?= $dif ?>">
                                            <input type="radio" name="dificultad" value="<?= $dif ?>"
                                                <?= $h['dificultad'] === $dif ? 'checked' : '' ?>>
                                            <span class="hb-diff-btn">
                                            <span class="hb-diff-dot"></span> <?= ucfirst($dif) ?>
                                            </span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="eh-actions">
                    <a href="listar_habitos.php" class="hb-btn-cancel">
                        <i class="bi bi-x-lg"></i> Cancelar
                    </a>
                    <button type="submit" class="hb-btn-submit">
                        <i class="bi bi-check-circle-fill"></i> Guardar cambios
                    </button>
                </div>

                </form>
            </div>
        </div>
    </main>

    <div class="modal fade modal-categoria" id="categoriaModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-folder-plus me-2"></i> Nueva categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="hb-label">Nombre</label>
                    <div class="hb-input-wrap">
                    <i class="bi bi-tag hb-input-icon"></i>
                    <input type="text" id="nuevaCategoriaNombre" class="hb-input" placeholder="Ej. Bienestar mental">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="hb-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="hb-btn-submit" id="guardarCategoriaBtn">
                    <i class="bi bi-check-circle-fill"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>


    <?php include "../../includes/footer.php"; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    
<script>
  
    const FREQ_INICIAL  = <?= json_encode($h['meta_frecuencia'] ?? 'diaria') ?>;
    const DIAS_MARCADOS = <?= json_encode($dias_marcados) ?>;

    const DAYS = [
    {val:'L',label:'L'},{val:'M',label:'M'},{val:'X',label:'X'},
    {val:'J',label:'J'},{val:'V',label:'V'},{val:'S',label:'S'},{val:'D',label:'D'}
    ];

    function buildDayPills(container, checked = [], allChecked = false){
    const grid = document.createElement('div');
    grid.className = 'hb-days-grid';
    DAYS.forEach(d => {
        const lbl = document.createElement('label');
        lbl.className = 'hb-day-label';
        const inp = document.createElement('input');
        inp.type    = 'checkbox';
        inp.name    = 'dias[]';
        inp.value   = d.val;
        inp.checked = allChecked || checked.includes(d.val);
        const pill = document.createElement('span');
        pill.className = 'hb-day-pill';
        pill.textContent = d.label;
        lbl.append(inp, pill);
        grid.appendChild(lbl);
    });
    container.innerHTML = '';
    container.appendChild(grid);
    }

    function renderFrecuencia(val, diasChecked){
    const container = document.getElementById('diasContainer');
    container.innerHTML = '';
    if(val === 'diaria'){
        const lbl = document.createElement('label');
        lbl.className = 'hb-label';
        lbl.textContent = 'Días';
        container.appendChild(lbl);
        buildDayPills(container, [], true);
    } else if(val === 'semanal'){
        const lbl = document.createElement('label');
        lbl.className = 'hb-label';
        lbl.textContent = 'Días de la semana';
        container.appendChild(lbl);
        buildDayPills(container, diasChecked ?? [], false);
    }
    }

    document.querySelectorAll('input[name="frecuencia"]').forEach(r => {
    r.addEventListener('change', () => renderFrecuencia(r.value, []));
    });


    function toggleCards(){
    const isB = document.getElementById('edit_construir').checked;
    document.getElementById('buildCard').style.display = isB ? 'block' : 'none';
    document.getElementById('breakCard').style.display = isB ? 'none'  : 'block';
    if(isB) renderFrecuencia(FREQ_INICIAL, DIAS_MARCADOS);
    }

    document.getElementById('edit_construir').addEventListener('change', toggleCards);
    document.getElementById('edit_romper').addEventListener('change', toggleCards);

    function toggleInconsciente(){
    const isI = document.getElementById('edit_inconsciente').checked;
    document.getElementById('buildContent').style.display    = isI ? 'none'  : 'block';
    document.getElementById('inconscienteMsg').style.display = isI ? 'block' : 'none';
    }

    document.getElementById('edit_consciente').addEventListener('change', toggleInconsciente);
    document.getElementById('edit_inconsciente').addEventListener('change', toggleInconsciente);

    document.getElementById('ultimaRecaida').addEventListener('change', function(){
    if(this.value){
        const diff = Math.floor((new Date() - new Date(this.value)) / 86400000);
        document.getElementById('diasSinRecaida').textContent = diff + ' día' + (diff !== 1 ? 's' : '');
    }
    });

    document.getElementById('guardarCategoriaBtn').addEventListener('click', () => {
    const nombre = document.getElementById('nuevaCategoriaNombre').value.trim();
    if(!nombre) return;
    fetch('guardar_categoria.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nombre })
    })
    .then(r => r.json())
    .then(data => {
        if(data.success){
        const select = document.querySelector('select[name="categoria"]');
        const opt = new Option(nombre, data.id, true, true);
        select.appendChild(opt);
        document.getElementById('nuevaCategoriaNombre').value = '';
        bootstrap.Modal.getInstance(document.getElementById('categoriaModal')).hide();
        } else {
        alert('Error al guardar categoría');
        }
    });
    });

    toggleCards();
    toggleInconsciente();
</script>

</body>
</html>

