<?php
// ============================================================
// TIENDA GAMER - Crear nuevo producto (Admin)
// INTEGRANTE 1: Subir carpeta admin/ completa
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
requireAdmin();

$categorias_disponibles = ['Teclado','Mouse','Headset','Monitor','Silla','Control','GPU','RAM','Audífonos','Otro'];
$error = '';
$datos = ['categoria'=>'','marca'=>'','nombre'=>'','precio'=>'','stock'=>'','descripcion'=>'','especificaciones'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoria      = trim($_POST['categoria']      ?? '');
    $marca          = trim($_POST['marca']          ?? '');
    $nombre         = trim($_POST['nombre']         ?? '');
    $precio         = trim($_POST['precio']         ?? '');
    $stock          = trim($_POST['stock']          ?? '');
    $descripcion    = trim($_POST['descripcion']    ?? '');
    $especificaciones = trim($_POST['especificaciones'] ?? '');
    $datos = compact('categoria','marca','nombre','precio','stock','descripcion','especificaciones');

    if (empty($categoria) || empty($marca) || empty($nombre) || empty($precio) || $stock === '') {
        $error = 'Completa todos los campos obligatorios.';
    } elseif (!is_numeric($precio) || $precio <= 0) {
        $error = 'El precio debe ser un número mayor a 0.';
    } elseif (!is_numeric($stock) || $stock < 0) {
        $error = 'El stock debe ser un entero no negativo.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO productos (categoria, marca, nombre, precio, stock, descripcion, especificaciones, extension) VALUES (?,?,?,?,?,?,?,'')");
        $stmt->execute([$categoria, $marca, $nombre, $precio, $stock, $descripcion, $especificaciones]);
        $idProducto = $pdo->lastInsertId();

        // Procesar imágenes
        $imagenes = [];
        if (!empty($_FILES['imagenes']['name'][0])) {
            $allowed = ['jpg','jpeg','png','webp'];
            $files   = $_FILES['imagenes'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) continue;
                if ($files['size'][$i] > 3 * 1024 * 1024) continue;
                $nombre_img = uniqid('gamer_') . '.' . $ext;
                if (move_uploaded_file($files['tmp_name'][$i], UPLOADS_DIR . $nombre_img)) {
                    $imagenes[] = $nombre_img;
                }
            }
        }
        foreach ($imagenes as $idx => $img) {
            $pdo->prepare("INSERT INTO producto_imagenes (id_producto, imagen, principal, orden) VALUES (?,?,?,?)")
                ->execute([$idProducto, $img, $idx === 0 ? 1 : 0, $idx]);
        }
        if (!empty($imagenes)) {
            $pdo->prepare("UPDATE productos SET imagen=? WHERE id=?")->execute([$imagenes[0], $idProducto]);
        }

        $_SESSION['msg']      = "Producto <strong>$marca $nombre</strong> creado exitosamente.";
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . BASE_URL . '/admin/productos.php');
        exit;
    }
}

$pageTitle = 'Nuevo Producto';
include __DIR__ . '/../includes/header.php';
?>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-plus-circle me-2 text-cyan"></i>Nuevo Producto</h4>
    <a href="<?= BASE_URL ?>/admin/productos.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<form method="POST" enctype="multipart/form-data" id="formProducto" novalidate>
<div class="row g-4">

    <!-- Columna izquierda -->
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
                            <option value="">Selecciona una categoría</option>
                            <?php foreach ($categorias_disponibles as $cat): ?>
                                <option value="<?= $cat ?>" <?= $datos['categoria'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Marca <span class="text-red">*</span></label>
                        <input type="text" name="marca" class="form-control"
                               value="<?= htmlspecialchars($datos['marca']) ?>"
                               placeholder="Ej: Logitech, Razer, HyperX" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nombre del Producto <span class="text-red">*</span></label>
                        <input type="text" name="nombre" class="form-control"
                               value="<?= htmlspecialchars($datos['nombre']) ?>"
                               placeholder="Ej: G502 Hero, Cloud II, K552" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Precio ($) <span class="text-red">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="precio" class="form-control"
                                   value="<?= htmlspecialchars($datos['precio']) ?>"
                                   step="0.01" min="0.01" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Stock <span class="text-red">*</span></label>
                        <input type="number" name="stock" class="form-control"
                               value="<?= htmlspecialchars($datos['stock']) ?>"
                               min="0" placeholder="Unidades disponibles" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"
                                  placeholder="Breve descripción del producto..."><?= htmlspecialchars($datos['descripcion']) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Especificaciones -->
        <div class="gamer-form-card">
            <div class="card-header px-4 py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-cpu me-2"></i>Especificaciones Técnicas</h5>
            </div>
            <div class="p-4">
                <p class="text-muted small mb-3">Formato <code style="color:var(--cyan)">Clave: Valor</code>, una por línea.</p>
                <div id="specsContainer"></div>
                <button type="button" class="btn btn-outline-cyan btn-sm mt-2" id="btnAddSpec">
                    <i class="bi bi-plus-lg me-1"></i>Agregar especificación
                </button>
                <input type="hidden" name="especificaciones" id="especificaciones">

                <div class="mt-3">
                    <small class="text-muted fw-semibold">Agregar rápido:</small>
                    <div class="d-flex flex-wrap gap-1 mt-1">
                        <?php
                        $specs_rapidas = ['Conectividad','DPI','Switches','Frecuencia de refresco','Resolución','Panel','RAM','VRAM','Peso','Color','Compatibilidad','Retroiluminación'];
                        foreach ($specs_rapidas as $s): ?>
                            <button type="button" class="btn btn-sm spec-quick"
                                    style="background:rgba(9,216,199,.1);border:1px solid rgba(9,216,199,.25);color:var(--cyan);font-size:.75rem"
                                    data-key="<?= $s ?>">+ <?= $s ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna derecha: imágenes -->
    <div class="col-lg-5">
        <div class="gamer-form-card h-100">
            <div class="card-header px-4 py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-images me-2"></i>Imágenes</h5>
            </div>
            <div class="p-4 d-flex flex-column">
                <div id="dropZone" class="drop-zone mb-3">
                    <i class="bi bi-cloud-upload fs-2 text-cyan"></i>
                    <p class="mb-1 fw-semibold mt-2" style="color:var(--text-light)">Arrastra imágenes aquí</p>
                    <p class="text-muted small mb-3">o haz clic para seleccionar</p>
                    <label class="btn btn-outline-cyan btn-sm">
                        <i class="bi bi-folder2-open me-1"></i>Seleccionar archivos
                        <input type="file" name="imagenes[]" id="inputImagenes"
                               accept=".jpg,.jpeg,.png,.webp" multiple style="display:none">
                    </label>
                    <p class="text-muted small mt-2 mb-0">JPG, PNG, WEBP · Máx 3MB · Sin límite</p>
                </div>
                <div id="previewGrid" class="preview-grid"></div>
                <p class="text-muted small mt-2 mb-0">
                    <i class="bi bi-info-circle me-1 text-cyan"></i>La primera imagen será la principal.
                </p>
            </div>
        </div>
    </div>

</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-cyan fw-semibold px-4">
        <i class="bi bi-save me-2"></i>Guardar Producto
    </button>
    <a href="<?= BASE_URL ?>/admin/productos.php" class="btn btn-outline-secondary">
        <i class="bi bi-x-lg me-1"></i>Cancelar
    </a>
</div>
</form>

<script>
// ── Especificaciones ────────────────────────────────────────
const specsContainer = document.getElementById('specsContainer');
const inputEspec     = document.getElementById('especificaciones');

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

document.getElementById('btnAddSpec').addEventListener('click', () => addSpecRow());
document.querySelectorAll('.spec-quick').forEach(btn =>
    btn.addEventListener('click', () => addSpecRow(btn.dataset.key, ''))
);

// ── Imágenes ────────────────────────────────────────────────
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
            item.draggable = true;
            item.dataset.idx = idx;
            item.innerHTML = `<img src="${e.target.result}" alt="">
                ${idx === 0 ? '<span class="badge-principal">Principal</span>' : ''}
                <button class="btn-remove" onclick="removeFile(${idx})">✕</button>`;
            item.addEventListener('dragstart', () => item.classList.add('dragging'));
            item.addEventListener('dragend',   () => { item.classList.remove('dragging'); rebuildOrder(); });
            item.addEventListener('dragover',  e => { e.preventDefault(); swapWith(item); });
            previewGrid.appendChild(item);
        };
        reader.readAsDataURL(file);
    });
    syncInput();
}

function removeFile(idx) { fileList.splice(idx, 1); renderPreviews(); }

function swapWith(target) {
    const dragging = previewGrid.querySelector('.dragging');
    if (!dragging || dragging === target) return;
    const items = [...previewGrid.children];
    if (items.indexOf(dragging) < items.indexOf(target)) previewGrid.insertBefore(dragging, target.nextSibling);
    else previewGrid.insertBefore(dragging, target);
}

function rebuildOrder() {
    fileList = [...previewGrid.children].map(el => fileList[parseInt(el.dataset.idx)]);
    renderPreviews();
}

function syncInput() {
    const dt = new DataTransfer();
    fileList.forEach(f => dt.items.add(f));
    inputImg.files = dt.files;
}

function addFiles(newFiles) {
    const allowed = ['image/jpeg','image/png','image/webp'];
    [...newFiles].forEach(f => {
        if (!allowed.includes(f.type)) return;
        if (f.size > 3 * 1024 * 1024) { alert(`"${f.name}" supera 3MB.`); return; }
        fileList.push(f);
    });
    renderPreviews();
}

dropZone.addEventListener('click',     ()  => inputImg.click());
dropZone.addEventListener('dragover',  e   => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', ()  => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop',      e   => { e.preventDefault(); dropZone.classList.remove('dragover'); addFiles(e.dataTransfer.files); });
inputImg.addEventListener('change',    ()  => addFiles(inputImg.files));

document.getElementById('formProducto').addEventListener('submit', function(e) {
    syncSpecs();
    if (!this.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
    this.classList.add('was-validated');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
