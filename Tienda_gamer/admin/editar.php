<?php
// ============================================================
// TIENDA GAMER - Editar producto (Admin)
// INTEGRANTE 1: Subir carpeta admin/ completa
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
requireAdmin();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . '/admin/productos.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->execute([$id]);
$prod = $stmt->fetch();
if (!$prod) { header('Location: ' . BASE_URL . '/admin/productos.php'); exit; }

$imgStmt = $pdo->prepare("SELECT * FROM producto_imagenes WHERE id_producto = ? ORDER BY orden ASC");
$imgStmt->execute([$id]);
$imgExistentes = $imgStmt->fetchAll();

$categorias_disponibles = ['Teclado','Mouse','Headset','Monitor','Silla','Control','GPU','RAM','Audífonos','Otro'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoria      = trim($_POST['categoria']      ?? '');
    $marca          = trim($_POST['marca']          ?? '');
    $nombre         = trim($_POST['nombre']         ?? '');
    $precio         = trim($_POST['precio']         ?? '');
    $stock          = trim($_POST['stock']          ?? '');
    $descripcion    = trim($_POST['descripcion']    ?? '');
    $especificaciones = trim($_POST['especificaciones'] ?? '');

    if (empty($categoria) || empty($marca) || empty($nombre) || empty($precio) || $stock === '') {
        $error = 'Completa todos los campos obligatorios.';
    } elseif (!is_numeric($precio) || $precio <= 0) {
        $error = 'El precio debe ser un número mayor a 0.';
    } elseif (!is_numeric($stock) || $stock < 0) {
        $error = 'El stock debe ser un entero no negativo.';
    } else {
        $pdo->prepare("UPDATE productos SET categoria=?,marca=?,nombre=?,precio=?,stock=?,descripcion=?,especificaciones=? WHERE id=?")
            ->execute([$categoria, $marca, $nombre, $precio, $stock, $descripcion, $especificaciones, $id]);

        // Eliminar imágenes marcadas
        if (!empty($_POST['eliminar_imagenes'])) {
            foreach ($_POST['eliminar_imagenes'] as $imgId) {
                $imgId = intval($imgId);
                $row   = $pdo->prepare("SELECT imagen FROM producto_imagenes WHERE id=? AND id_producto=?");
                $row->execute([$imgId, $id]);
                $file  = $row->fetchColumn();
                if ($file && file_exists(UPLOADS_DIR . $file)) unlink(UPLOADS_DIR . $file);
                $pdo->prepare("DELETE FROM producto_imagenes WHERE id=?")->execute([$imgId]);
            }
        }

        // Agregar nuevas imágenes
        if (!empty($_FILES['imagenes']['name'][0])) {
            $allowed = ['jpg','jpeg','png','webp'];
            $files   = $_FILES['imagenes'];
            $maxOrden = $pdo->prepare("SELECT COALESCE(MAX(orden),0) FROM producto_imagenes WHERE id_producto=?");
            $maxOrden->execute([$id]);
            $orden = $maxOrden->fetchColumn() + 1;
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed) || $files['size'][$i] > 3*1024*1024) continue;
                $nombre_img = uniqid('gamer_') . '.' . $ext;
                if (move_uploaded_file($files['tmp_name'][$i], UPLOADS_DIR . $nombre_img)) {
                    $pdo->prepare("INSERT INTO producto_imagenes (id_producto, imagen, principal, orden) VALUES (?,?,0,?)")
                        ->execute([$id, $nombre_img, $orden++]);
                }
            }
        }

        // Actualizar imagen principal legacy
        $princ = $pdo->prepare("SELECT imagen FROM producto_imagenes WHERE id_producto=? AND principal=1 LIMIT 1");
        $princ->execute([$id]);
        $pFile = $princ->fetchColumn();
        if (!$pFile) {
            $first = $pdo->prepare("SELECT imagen FROM producto_imagenes WHERE id_producto=? ORDER BY orden ASC LIMIT 1");
            $first->execute([$id]);
            $pFile = $first->fetchColumn();
            if ($pFile) $pdo->prepare("UPDATE producto_imagenes SET principal=1 WHERE id_producto=? AND imagen=? LIMIT 1")->execute([$id, $pFile]);
        }
        if ($pFile) $pdo->prepare("UPDATE productos SET imagen=? WHERE id=?")->execute([$pFile, $id]);

        $_SESSION['msg']      = "Producto actualizado correctamente.";
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . BASE_URL . '/admin/productos.php');
        exit;
    }
    // Reload images after possible deletion
    $imgStmt->execute([$id]);
    $imgExistentes = $imgStmt->fetchAll();
    $prod = array_merge($prod, compact('categoria','marca','nombre','precio','stock','descripcion','especificaciones'));
}

// Parsear especificaciones para el editor
$specs_parsed = [];
if (!empty($prod['especificaciones'])) {
    foreach (explode("\n", $prod['especificaciones']) as $line) {
        $parts = explode(':', $line, 2);
        if (count($parts) === 2) $specs_parsed[] = [trim($parts[0]), trim($parts[1])];
    }
}

$pageTitle = 'Editar: ' . $prod['nombre'];
include __DIR__ . '/../includes/header.php';
?>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-pencil-square me-2 text-cyan"></i>Editar Producto</h4>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/detalle.php?id=<?= $id ?>" class="btn btn-outline-cyan btn-sm" target="_blank">
            <i class="bi bi-eye me-1"></i>Ver
        </a>
        <a href="<?= BASE_URL ?>/admin/productos.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<form method="POST" enctype="multipart/form-data" id="formProducto" novalidate>
<div class="row g-4">

    <div class="col-lg-7">
        <div class="gamer-form-card mb-4">
            <div class="card-header px-4 py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-controller me-2"></i>Información del Producto</h5>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Categoría <span class="text-red">*</span></label>
                        <select name="categoria" class="form-select" required>
                            <?php foreach ($categorias_disponibles as $cat): ?>
                                <option value="<?= $cat ?>" <?= $prod['categoria'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Marca <span class="text-red">*</span></label>
                        <input type="text" name="marca" class="form-control"
                               value="<?= htmlspecialchars($prod['marca']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nombre <span class="text-red">*</span></label>
                        <input type="text" name="nombre" class="form-control"
                               value="<?= htmlspecialchars($prod['nombre']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Precio ($) <span class="text-red">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="precio" class="form-control"
                                   value="<?= htmlspecialchars($prod['precio']) ?>"
                                   step="0.01" min="0.01" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Stock <span class="text-red">*</span></label>
                        <input type="number" name="stock" class="form-control"
                               value="<?= htmlspecialchars($prod['stock']) ?>" min="0" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($prod['descripcion']) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="gamer-form-card">
            <div class="card-header px-4 py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-cpu me-2"></i>Especificaciones</h5>
            </div>
            <div class="p-4">
                <div id="specsContainer"></div>
                <button type="button" class="btn btn-outline-cyan btn-sm mt-2" id="btnAddSpec">
                    <i class="bi bi-plus-lg me-1"></i>Agregar
                </button>
                <input type="hidden" name="especificaciones" id="especificaciones">
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="gamer-form-card mb-4">
            <div class="card-header px-4 py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-images me-2"></i>Imágenes actuales</h5>
            </div>
            <div class="p-4">
                <?php if (!empty($imgExistentes)): ?>
                    <div class="row g-2 mb-3">
                        <?php foreach ($imgExistentes as $img): ?>
                            <div class="col-4">
                                <div class="position-relative">
                                    <img src="<?= UPLOADS_URL . htmlspecialchars($img['imagen']) ?>"
                                         class="w-100 rounded" style="height:90px;object-fit:cover;border:1px solid rgba(9,216,199,.2)">
                                    <?php if ($img['principal']): ?>
                                        <span class="badge-principal position-absolute" style="top:4px;left:4px">Principal</span>
                                    <?php endif; ?>
                                    <div class="form-check position-absolute" style="bottom:4px;right:4px">
                                        <input class="form-check-input" type="checkbox"
                                               name="eliminar_imagenes[]" value="<?= $img['id'] ?>"
                                               id="del<?= $img['id'] ?>">
                                        <label class="form-check-label text-danger small" for="del<?= $img['id'] ?>">
                                            Eliminar
                                        </label>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-3">Sin imágenes aún.</p>
                <?php endif; ?>

                <div id="dropZone" class="drop-zone">
                    <i class="bi bi-cloud-upload fs-2 text-cyan"></i>
                    <p class="small text-muted mt-2 mb-2">Agregar más imágenes</p>
                    <label class="btn btn-outline-cyan btn-sm">
                        <i class="bi bi-folder2-open me-1"></i>Seleccionar
                        <input type="file" name="imagenes[]" id="inputImagenes"
                               accept=".jpg,.jpeg,.png,.webp" multiple style="display:none">
                    </label>
                </div>
                <div id="previewGrid" class="preview-grid mt-3"></div>
            </div>
        </div>
    </div>

</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-cyan fw-semibold px-4">
        <i class="bi bi-save me-2"></i>Guardar cambios
    </button>
    <a href="<?= BASE_URL ?>/admin/productos.php" class="btn btn-outline-secondary">
        <i class="bi bi-x-lg me-1"></i>Cancelar
    </a>
</div>
</form>

<script>
// Cargar specs existentes
const specsContainer = document.getElementById('specsContainer');
const inputEspec     = document.getElementById('especificaciones');
const specsData      = <?= json_encode($specs_parsed) ?>;

function addSpecRow(key = '', val = '') {
    const row = document.createElement('div');
    row.className = 'spec-row';
    row.innerHTML = `
        <input type="text" class="form-control form-control-sm spec-key" placeholder="Clave" value="${key}">
        <input type="text" class="form-control form-control-sm spec-val" placeholder="Valor" value="${val}">
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.parentElement.remove();syncSpecs()">
            <i class="bi bi-trash"></i>
        </button>`;
    row.querySelectorAll('input').forEach(i => i.addEventListener('input', syncSpecs));
    specsContainer.appendChild(row);
}

function syncSpecs() {
    inputEspec.value = [...specsContainer.querySelectorAll('.spec-row')].map(r => {
        const k = r.querySelector('.spec-key').value.trim();
        const v = r.querySelector('.spec-val').value.trim();
        return (k || v) ? k + ': ' + v : null;
    }).filter(Boolean).join('\n');
}

specsData.forEach(([k, v]) => addSpecRow(k, v));
syncSpecs();
document.getElementById('btnAddSpec').addEventListener('click', () => addSpecRow());

// Imágenes nuevas
const dropZone    = document.getElementById('dropZone');
const inputImg    = document.getElementById('inputImagenes');
const previewGrid = document.getElementById('previewGrid');
let fileList = [];

function renderPreviews() {
    previewGrid.innerHTML = '';
    fileList.forEach((file, idx) => {
        const reader = new FileReader();
        reader.onload = e => {
            const item = document.createElement('div');
            item.className = 'preview-item';
            item.innerHTML = `<img src="${e.target.result}" alt="">
                <button class="btn-remove" onclick="fileList.splice(${idx},1);renderPreviews()">✕</button>`;
            previewGrid.appendChild(item);
        };
        reader.readAsDataURL(file);
    });
    const dt = new DataTransfer();
    fileList.forEach(f => dt.items.add(f));
    inputImg.files = dt.files;
}

function addFiles(files) {
    [...files].forEach(f => {
        if (!['image/jpeg','image/png','image/webp'].includes(f.type)) return;
        if (f.size > 3*1024*1024) { alert(`"${f.name}" supera 3MB.`); return; }
        fileList.push(f);
    });
    renderPreviews();
}
dropZone.addEventListener('click',    () => inputImg.click());
dropZone.addEventListener('dragover', e  => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave',() => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop',     e  => { e.preventDefault(); dropZone.classList.remove('dragover'); addFiles(e.dataTransfer.files); });
inputImg.addEventListener('change',   () => addFiles(inputImg.files));

document.getElementById('formProducto').addEventListener('submit', function(e) {
    syncSpecs();
    if (!this.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
    this.classList.add('was-validated');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
