<?php
// ============================================================
// TIENDA GAMER - Detalle de producto
// INTEGRANTE 2: Subir este archivo (detalle.php)
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->execute([$id]);
$prod = $stmt->fetch();
if (!$prod) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Imágenes
$imgStmt = $pdo->prepare("SELECT imagen FROM producto_imagenes WHERE id_producto = ? ORDER BY orden ASC");
$imgStmt->execute([$id]);
$imagenes = $imgStmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($imagenes) && !empty($prod['imagen'])) {
    $imagenes = [$prod['imagen']];
}

// Especificaciones
$specs = [];
if (!empty($prod['especificaciones'])) {
    foreach (explode("\n", $prod['especificaciones']) as $line) {
        $parts = explode(':', $line, 2);
        if (count($parts) === 2) {
            $specs[trim($parts[0])] = trim($parts[1]);
        }
    }
}

// Productos relacionados (misma categoría)
$relStmt = $pdo->prepare("SELECT * FROM productos WHERE categoria = ? AND id != ? AND stock > 0 LIMIT 4");
$relStmt->execute([$prod['categoria'], $id]);
$relacionados = $relStmt->fetchAll();

$pageTitle = htmlspecialchars($prod['marca'] . ' ' . $prod['nombre']);

$iconosCat = [
    'Teclado'  => 'bi-keyboard',
    'Mouse'    => 'bi-mouse',
    'Headset'  => 'bi-headphones',
    'Monitor'  => 'bi-display',
    'Silla'    => 'bi-person-workspace',
    'Control'  => 'bi-controller',
    'GPU'      => 'bi-gpu-card',
    'RAM'      => 'bi-memory',
    'Audífonos'=> 'bi-headset',
];
$icono = $iconosCat[$prod['categoria']] ?? 'bi-controller';

include __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb" style="--bs-breadcrumb-divider-color: var(--text-muted-gamer)">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" class="text-cyan">Tienda</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php?categoria=<?= urlencode($prod['categoria']) ?>" class="text-cyan"><?= htmlspecialchars($prod['categoria']) ?></a></li>
        <li class="breadcrumb-item active text-muted"><?= htmlspecialchars($prod['nombre']) ?></li>
    </ol>
</nav>

<div class="row g-5 mb-5">
    <!-- Galería -->
    <div class="col-lg-5">
        <?php if (!empty($imagenes)): ?>
            <img id="mainImg"
                 src="<?= UPLOADS_URL . htmlspecialchars($imagenes[0]) ?>"
                 class="gallery-main w-100 mb-3" alt="<?= htmlspecialchars($prod['nombre']) ?>">
            <?php if (count($imagenes) > 1): ?>
                <div class="d-flex gap-2 flex-wrap">
                    <?php foreach ($imagenes as $idx => $img): ?>
                        <img src="<?= UPLOADS_URL . htmlspecialchars($img) ?>"
                             class="gallery-thumb <?= $idx === 0 ? 'active' : '' ?>"
                             onclick="cambiarImg(this, '<?= UPLOADS_URL . htmlspecialchars($img) ?>')"
                             alt="">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="gallery-main d-flex align-items-center justify-content-center text-muted" style="height:380px">
                <i class="bi <?= $icono ?>" style="font-size:6rem;opacity:.3"></i>
            </div>
        <?php endif; ?>
    </div>

    <!-- Info -->
    <div class="col-lg-7">
        <span class="badge-categoria mb-3 d-inline-block">
            <i class="bi <?= $icono ?> me-1"></i><?= htmlspecialchars($prod['categoria']) ?>
        </span>
        <h2 class="fw-bold mb-1"><?= htmlspecialchars($prod['marca']) ?> <?= htmlspecialchars($prod['nombre']) ?></h2>

        <div class="d-flex align-items-center gap-3 mb-3">
            <span class="price-tag" style="font-size:1.8rem">$ <?= number_format($prod['precio'], 2) ?></span>
            <span class="<?= $prod['stock'] > 5 ? 'stock-ok' : ($prod['stock'] > 0 ? 'stock-warn' : 'stock-out') ?> small">
                <i class="bi bi-box-seam me-1"></i>
                <?= $prod['stock'] > 0 ? $prod['stock'] . ' en stock' : 'Sin stock' ?>
            </span>
        </div>

        <?php if (!empty($prod['descripcion'])): ?>
            <p class="text-muted mb-4"><?= nl2br(htmlspecialchars($prod['descripcion'])) ?></p>
        <?php endif; ?>

        <!-- Acción -->
        <div class="mb-4">
            <?php if ($prod['stock'] <= 0): ?>
                <button class="btn btn-outline-secondary btn-lg" disabled>Sin stock</button>
            <?php elseif (isAdmin()): ?>
                <a href="<?= BASE_URL ?>/admin/editar.php?id=<?= $prod['id'] ?>" class="btn btn-outline-cyan btn-lg">
                    <i class="bi bi-pencil me-2"></i>Editar producto
                </a>
            <?php elseif (isLoggedIn()): ?>
                <form method="POST" action="<?= BASE_URL ?>/carrito.php" class="d-flex gap-2">
                    <input type="hidden" name="accion"      value="agregar">
                    <input type="hidden" name="id_producto" value="<?= $prod['id'] ?>">
                    <input type="number" name="cantidad" class="form-control gamer-input" style="max-width:90px"
                           value="1" min="1" max="<?= $prod['stock'] ?>">
                    <button type="submit" class="btn btn-cyan btn-lg px-4">
                        <i class="bi bi-cart-plus me-2"></i>Agregar al carrito
                    </button>
                </form>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-cyan btn-lg">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Inicia sesión para comprar
                </a>
            <?php endif; ?>
        </div>

        <!-- Especificaciones -->
        <?php if (!empty($specs)): ?>
        <div class="gamer-form-card p-3">
            <h6 class="text-cyan fw-bold mb-3"><i class="bi bi-cpu me-2"></i>Especificaciones técnicas</h6>
            <div class="row g-1">
                <?php foreach ($specs as $k => $v): ?>
                    <div class="col-sm-6">
                        <div class="d-flex justify-content-between p-2 rounded" style="background:rgba(9,216,199,.06)">
                            <small class="text-muted"><?= htmlspecialchars($k) ?></small>
                            <small class="fw-semibold"><?= htmlspecialchars($v) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Productos relacionados -->
<?php if (!empty($relacionados)): ?>
<section>
    <h5 class="fw-bold mb-3 text-cyan"><i class="bi bi-grid me-2"></i>También te puede interesar</h5>
    <div class="row g-3">
        <?php foreach ($relacionados as $rel): ?>
            <?php
            $relImg = '';
            $ri = $pdo->prepare("SELECT imagen FROM producto_imagenes WHERE id_producto=? AND principal=1 LIMIT 1");
            $ri->execute([$rel['id']]);
            $rf = $ri->fetchColumn();
            if ($rf) $relImg = UPLOADS_URL . htmlspecialchars($rf);
            elseif (!empty($rel['imagen'])) $relImg = UPLOADS_URL . htmlspecialchars($rel['imagen']);
            ?>
            <div class="col-sm-6 col-lg-3">
                <div class="gamer-card h-100">
                    <a href="<?= BASE_URL ?>/detalle.php?id=<?= $rel['id'] ?>" class="text-decoration-none">
                        <div class="product-img-wrap" style="height:140px">
                            <?php if ($relImg): ?>
                                <img src="<?= $relImg ?>" alt="<?= htmlspecialchars($rel['nombre']) ?>">
                            <?php else: ?>
                                <div class="no-img-placeholder" style="height:140px">
                                    <i class="bi <?= $iconosCat[$rel['categoria']] ?? 'bi-controller' ?>" style="font-size:2.5rem"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="p-3" style="background:transparent">
                        <p class="small fw-semibold mb-1" style="color:var(--text-light)">
                            <?= htmlspecialchars($rel['marca']) ?> <?= htmlspecialchars($rel['nombre']) ?>
                        </p>
                        <span class="price-tag">$ <?= number_format($rel['precio'], 2) ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<script>
function cambiarImg(thumb, src) {
    document.getElementById('mainImg').src = src;
    document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
