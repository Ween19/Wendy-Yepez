<?php
require_once "../../libs/auth.php";
require_once "../../config/conexion.php";

    $id_usuario = $_SESSION['usuario_id'];

    $sql = "SELECT * FROM categorias WHERE es_popular = 1 OR id_usuario = :id_usuario ORDER BY nombre_cat ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_usuario',$id_usuario);
    $stmt->execute();

    $categorias = $stmt->fetchAll();

    $error = "";
    $success = "";

    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $nombre = trim($_POST['nombre']);
        $categoria = $_POST['categoria'];
        $clase_habito = $_POST['clase_habito'];
        $direccion = $_POST['direccion'];
        $dificultad = $_POST['dificultad'] ?? null;

        try{

            $sqlInsert = "INSERT INTO habitos(id_usuario, id_categoria, nombre, clase_habito, direccion_habito, dificultad) VALUES(:usuario, :categoria, :nombre, :clase, :direccion, :dificultad)";
            $stmtInsert = $pdo->prepare($sqlInsert);
            $stmtInsert->execute([
                ':usuario'=>$id_usuario,
                ':categoria'=>$categoria,
                ':nombre'=>$nombre,
                ':clase'=>$clase_habito,
                ':direccion'=>$direccion,
                ':dificultad'=>$dificultad
            ]);
            $id_habito = $pdo->lastInsertId();

            if($direccion === "construir" && $clase_habito === "consciente"){
                $meta = $_POST['meta_cantidad'];
                $unidad = $_POST['unidad'];
                $frecuencia = $_POST['frecuencia'];
                $fecha_inicio = $_POST['fecha_inicio'];
                $fecha_fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;

                $sqlMeta = "INSERT INTO metas_config(id_habito, frecuencia, cantidad, unidad, fecha_inicio, fecha_fin) VALUES(:habito, :frecuencia, :cantidad, :unidad, :inicio, :fin)";
                $stmtMeta = $pdo->prepare($sqlMeta);
                $stmtMeta->execute([
                    ':habito'=>$id_habito,
                    ':frecuencia'=>$frecuencia,
                    ':cantidad'=>$meta,
                    ':unidad'=>$unidad,
                    ':inicio'=>$fecha_inicio,
                    ':fin'=>$fecha_fin
                ]);
                $id_meta = $pdo->lastInsertId();

                if(isset($_POST['dias']) && is_array($_POST['dias'])){
                    foreach($_POST['dias'] as $dia){
                        $sqlDia = "INSERT INTO metas_dias(id_meta, dia) VALUES(:meta, :dia)";
                        $stmtDia = $pdo->prepare($sqlDia);
                        $stmtDia->execute([':meta'=>$id_meta, ':dia'=>$dia]);
                    }
                }
            }

            if($direccion === "romper" && !empty($_POST['ultima_recaida'])){
                $sqlUpdate = "UPDATE habitos SET fecha_ultima_recaida = :fecha WHERE id_habito = :id";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute([':fecha'=>$_POST['ultima_recaida'], ':id'=>$id_habito]);
            }

            header("Location: listar_habitos.php");
            exit();

        }catch(PDOException $e){
            $error = $e->getMessage();
        }
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Crear hábito | Habita</title>
    
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
        <div class="container" style="max-width: 900px; margin: 0 auto;">
            <div style="background: #071e30; border-radius: 20px; border: 1px solid rgba(255,255,255,.08); padding: 28px 32px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px;">
                    <div>
                        <h2 style="font-family: 'Syne', sans-serif; color: white; margin: 0;">Crear nuevo hábito</h2>
                        <p style="color: #7ea8c4; margin: 5px 0 0;">Define un hábito positivo o rompe uno negativo.</p>
                    </div>
                    <a href="listar_habitos.php" style="color: #7ea8c4; text-decoration: none; font-size: 1.2rem;">✕</a>
                </div>

                <?php if($error): ?>
                <div style="background: rgba(255,77,109,.1); border: 1px solid rgba(255,77,109,.3); border-radius: 12px; padding: 12px 16px; color: #ffa0b3; margin-bottom: 24px;">
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="hb-label">Nombre del hábito</label>
                            <div class="hb-input-wrap">
                                <i class="bi bi-pencil-square hb-input-icon"></i>
                                <input type="text" name="nombre" class="hb-input" placeholder="Ej. Tomar 2 litros de agua" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="hb-label">Categoría</label>
                            <div class="hb-input-wrap">
                                <i class="bi bi-tag hb-input-icon"></i>
                                <select name="categoria" class="hb-input" required>
                                    <option value="">Selecciona una categoría</option>
                                    <?php foreach($categorias as $cat): ?>
                                    <option value="<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre_cat']) ?></option>
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
                                    <input type="radio" name="clase_habito" value="consciente" checked>
                                    <span class="hb-toggle-btn"><i class="bi bi-brain"></i> Consciente</span>
                                </label>
                                <label class="hb-toggle-label clase">
                                    <input type="radio" name="clase_habito" value="inconsciente" id="inconscienteRadio">
                                    <span class="hb-toggle-btn">Inconsciente</span>
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="hb-label">Dirección</label>
                            <div class="hb-toggle-group">
                                <label class="hb-toggle-label build">
                                    <input type="radio" name="direccion" value="construir" checked id="construir">
                                    <span class="hb-toggle-btn"><i class="bi bi-graph-up-arrow"></i> Construir</span>
                                </label>
                                <label class="hb-toggle-label break">
                                    <input type="radio" name="direccion" value="romper" id="romper">
                                    <span class="hb-toggle-btn"><i class="bi bi-slash-circle"></i> Romper</span>
                                </label>
                            </div>
                        </div>

                    </div>

                    <div class="hb-divider-label mt-4"><span>Configuración</span></div>

                    <div id="buildCard" style="margin-top: 20px;">
                        <div class="hb-section-card build-card">
                            <div class="hb-card-header">
                                <div class="hb-card-icon green"><i class="bi bi-tree"></i></div>
                                <div><p class="hb-card-title">Construir un hábito</p><p class="hb-card-subtitle">Crea y fortalece una acción positiva.</p></div>
                            </div>

                            <div class="hb-card-body">
                                <div id="inconscienteMsg" style="display:none;">
                                    <div class="hb-alert-info"><i class="bi bi-info-circle-fill"></i><span>Este hábito no requiere meta cuantitativa; solo registra cuándo ocurre.</span></div>
                                </div>

                                <div id="buildContent">
                                    <div class="row g-3">
                                        <div class="col-6"><label class="hb-label">Meta</label><input type="number" step="0.1" name="meta_cantidad" class="hb-input" placeholder="Ej. 2"></div>
                                        <div class="col-6"><label class="hb-label">Unidad</label><div class="hb-input-wrap"><i class="bi bi-rulers hb-input-icon"></i><input type="text" name="unidad" class="hb-input" placeholder="Ej. litros"></div></div>
                                        <div class="col-12"><label class="hb-label">Frecuencia</label>
                                            <div class="hb-freq-group">
                                                <label class="hb-freq-label"><input type="radio" name="frecuencia" value="diaria" id="freqDiaria" checked><span class="hb-freq-btn">Diaria</span></label>
                                                <label class="hb-freq-label"><input type="radio" name="frecuencia" value="semanal" id="freqSemanal"><span class="hb-freq-btn">Semanal</span></label>
                                                <label class="hb-freq-label"><input type="radio" name="frecuencia" value="mensual" id="freqPersonalizada"><span class="hb-freq-btn">Mensual</span></label>
                                            </div>
                                        </div>

                                        <div class="col-12" id="diasContainer"></div>
                                        <div class="col-12"><label class="hb-label">Dificultad</label>
                                            <div class="hb-diff-group">
                                                <label class="hb-diff-label low"><input type="radio" name="dificultad" value="baja"><span class="hb-diff-btn"><span class="hb-diff-dot"></span> Baja</span></label>
                                                <label class="hb-diff-label medium"><input type="radio" name="dificultad" value="media"><span class="hb-diff-btn"><span class="hb-diff-dot"></span> Media</span></label>
                                                <label class="hb-diff-label high"><input type="radio" name="dificultad" value="alta"><span class="hb-diff-btn"><span class="hb-diff-dot"></span> Alta</span></label>
                                            </div>
                                        </div>

                                        <div class="col-md-6"><label class="hb-label">Fecha inicio</label><input type="date" name="fecha_inicio" class="hb-input" value="<?= date('Y-m-d') ?>"></div>
                                        <div class="col-md-6"><label class="hb-label">Fecha fin <span style="color:var(--text-muted);font-size:.7rem">(opcional)</span></label><input type="date" name="fecha_fin" class="hb-input"></div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div id="breakCard" style="display:none; margin-top: 20px;">
                        <div class="hb-section-card break-card">
                            <div class="hb-card-header"><div class="hb-card-icon red"><i class="bi bi-slash-circle"></i></div><div><p class="hb-card-title">Romper un hábito</p><p class="hb-card-subtitle">Identifica y elimina lo que te detiene.</p></div></div>
                            <div class="hb-card-body">
                                <div class="row g-3">
                                    <div class="col-md-6"><label class="hb-label">Última vez que ocurrió</label><div class="hb-input-wrap"><i class="bi bi-calendar-x hb-input-icon"></i><input type="date" name="ultima_recaida" class="hb-input" id="ultimaRecaida"></div></div>
                                    <div class="col-md-6"><label class="hb-label">Días sin recaída <i class="bi bi-info-circle" style="font-size:.75rem"></i></label><div class="hb-days-badge"><i class="bi bi-fire"></i><span id="diasSinRecaida">— días</span></div><p style="font-size:.72rem; color:#7ea8c4; margin:6px 0 0">Se actualiza automáticamente</p></div>
                                    <div class="col-12"><label class="hb-label">Dificultad</label>
                                        <div class="hb-diff-group">
                                            <label class="hb-diff-label low"><input type="radio" name="dificultad" value="baja"><span class="hb-diff-btn"><span class="hb-diff-dot"></span> Baja</span></label>
                                            <label class="hb-diff-label medium"><input type="radio" name="dificultad" value="media"><span class="hb-diff-btn"><span class="hb-diff-dot"></span> Media</span></label>
                                            <label class="hb-diff-label high"><input type="radio" name="dificultad" value="alta"><span class="hb-diff-btn"><span class="hb-diff-dot"></span> Alta</span></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 30px;">
                        <a href="listar_habitos.php" class="hb-btn-cancel">Cancelar</a>
                        <button type="submit" class="hb-btn-submit"><i class="bi bi-check-circle-fill"></i> Crear hábito</button>
                    </div>
                </form>

            </div>
        </div>
    </main>

  
    <div class="modal fade modal-categoria" id="categoriaModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-folder-plus me-2" style="color:var(--cyan)"></i> Nueva categoría</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                    <button type="button" class="hb-btn-submit" id="guardarCategoriaBtn"><i class="bi bi-check-circle-fill"></i> Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <?php include "../../includes/footer.php"; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


    <script>
        const construirRadio = document.getElementById("construir");
        const romperRadio = document.getElementById("romper");
        const buildCard = document.getElementById("buildCard");
        const breakCard = document.getElementById("breakCard");
        function toggleHabitCards(){
            if(construirRadio.checked){
                buildCard.style.display = "block";
                breakCard.style.display = "none";
            } else {
                buildCard.style.display = "none";
                breakCard.style.display = "block";
            }
        }
        construirRadio.addEventListener("change", toggleHabitCards);
        romperRadio.addEventListener("change", toggleHabitCards);
        toggleHabitCards();

        const inconscienteRadio = document.getElementById("inconscienteRadio");
        const buildContent = document.getElementById("buildContent");
        const inconscienteMsg = document.getElementById("inconscienteMsg");
        document.querySelectorAll('input[name="clase_habito"]').forEach(radio => {
            radio.addEventListener("change", () => {
                if(inconscienteRadio.checked){
                    buildContent.style.display = "none";
                    inconscienteMsg.style.display = "block";
                } else {
                    buildContent.style.display = "block";
                    inconscienteMsg.style.display = "none";
                }
            });
        });

        const diasContainer = document.getElementById("diasContainer");
        const DAYS = [{val:"L",label:"L"},{val:"M",label:"M"},{val:"X",label:"X"},{val:"J",label:"J"},{val:"V",label:"V"},{val:"S",label:"S"},{val:"D",label:"D"}];
        function buildDayPills(checked){
            const grid = document.createElement("div");
            grid.className = "hb-days-grid";
            DAYS.forEach(d => {
                const lbl = document.createElement("label");
                lbl.className = "hb-day-label";
                const inp = document.createElement("input");
                inp.type = "checkbox";
                inp.name = "dias[]";
                inp.value = d.val;
                if(checked) inp.checked = true;
                const pill = document.createElement("span");
                pill.className = "hb-day-pill";
                pill.textContent = d.label;
                lbl.appendChild(inp);
                lbl.appendChild(pill);
                grid.appendChild(lbl);
            });
            return grid;
        }
        function renderFrecuencia(){
            const val = document.querySelector('input[name="frecuencia"]:checked')?.value;
            diasContainer.innerHTML = "";
            if(val === "diaria"){
                const lbl = document.createElement("label");
                lbl.className = "hb-label";
                lbl.textContent = "Días";
                diasContainer.appendChild(lbl);
                diasContainer.appendChild(buildDayPills(true));
            } else if(val === "semanal"){
                const lbl = document.createElement("label");
                lbl.className = "hb-label";
                lbl.textContent = "Días de la semana";
                diasContainer.appendChild(lbl);
                diasContainer.appendChild(buildDayPills(false));
            }
        }
        document.querySelectorAll('input[name="frecuencia"]').forEach(r => r.addEventListener("change", renderFrecuencia));
        renderFrecuencia();

        const ultimaRecaida = document.getElementById("ultimaRecaida");
        const diasSinRecaida = document.getElementById("diasSinRecaida");
        ultimaRecaida.addEventListener("change", () => {
            if(ultimaRecaida.value){
                const fecha = new Date(ultimaRecaida.value);
                const hoy = new Date();
                const diferencia = Math.floor((hoy - fecha) / (1000 * 60 * 60 * 24));
                diasSinRecaida.textContent = diferencia + " días";
            }
        });

        document.getElementById("guardarCategoriaBtn").addEventListener("click", () => {
            const nombre = document.getElementById("nuevaCategoriaNombre").value.trim();
            if(!nombre) return;
            fetch("guardar_categoria.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ nombre })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){
                    const select = document.querySelector('select[name="categoria"]');
                    const opt = document.createElement("option");
                    opt.value = data.id;
                    opt.text = nombre;
                    opt.selected = true;
                    select.appendChild(opt);
                    document.getElementById("nuevaCategoriaNombre").value = "";
                    bootstrap.Modal.getInstance(document.getElementById("categoriaModal")).hide();
                } else {
                    alert("Error al guardar categoría");
                }
            });
        });
    </script>

</body>
</html>