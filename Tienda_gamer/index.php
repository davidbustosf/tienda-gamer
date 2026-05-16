<?php
// ============================================================
// TIENDA GAMER - Catálogo principal
// INTEGRANTE 2: Subir este archivo (index.php)
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

$pageTitle = 'Catálogo Gamer';

$busqueda  = trim($_GET['buscar']    ?? '');
$categoria = trim($_GET['categoria'] ?? '');

$where  = "WHERE stock > 0";
$params = [];

if ($busqueda !== '') {
    $where   .= " AND (marca LIKE ? OR nombre LIKE ? OR descripcion LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}
if ($categoria !== '') {
    $where   .= " AND categoria = ?";
    $params[] = $categoria;
}

$categorias = $pdo->query("SELECT DISTINCT categoria FROM productos ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("SELECT * FROM productos $where ORDER BY id DESC");
$stmt->execute($params);
$productos = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';

// Íconos por categoría
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
?>

<!-- Hero banner -->
<div class="gamer-hero mb-5 text-center">
    <h1 class="fw-bold mb-2">
        <i class="bi bi-controller-fill text-cyan me-2"></i>
        Bienvenido a <span class="text-cyan">Gamere</span>
    </h1>
    <p class="text-muted mb-0">Hardware y accesorios para llevar tu gaming al siguiente nivel</p>
</div>

<!-- Cabecera + contador -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="fw-bold mb-0"><i class="bi bi-grid me-2 text-cyan"></i>Catálogo</h5>
        <small class="text-muted"><?= count($productos) ?> productos disponibles</small>
    </div>
    <?php if (isAdmin()): ?>
        <a href="<?= BASE_URL ?>/admin/crear.php" class="btn btn-cyan btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Nuevo producto
        </a>
    <?php endif; ?>
</div>

<!-- Filtros -->
<form method="GET" class="row g-2 mb-4">
    <div class="col-md-6">
        <div class="input-group">
            <span class="input-group-text gamer-ig"><i class="bi bi-search"></i></span>
            <input type="text" name="buscar" class="form-control gamer-input"
                   placeholder="Buscar marca, nombre..."
                   value="<?= htmlspecialchars($busqueda) ?>">
        </div>
    </div>
    <div class="col-md-3">
        <select name="categoria" class="form-select gamer-select">
            <option value="">Todas las categorías</option>
            <?php foreach ($categorias as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>" <?= $categoria === $c ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-cyan w-100">
            <i class="bi bi-funnel me-1"></i>Filtrar
        </button>
    </div>
    <?php if ($busqueda || $categoria): ?>
    <div class="col-md-1">
        <a href="index.php" class="btn btn-outline-secondary w-100" title="Limpiar">
            <i class="bi bi-x-lg"></i>
        </a>
    </div>
    <?php endif; ?>
</form>

<!-- Resultado -->
<?php if (empty($productos)): ?>
    <div class="text-center py-5">
        <i class="bi bi-search fs-1 text-muted"></i>
        <p class="mt-3 text-muted fs-5">No se encontraron productos con esos criterios.</p>
        <a href="index.php" class="btn btn-outline-cyan">Ver todos</a>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($productos as $prod): ?>
            <?php
            $imgSrc = '';
            $imgQ   = $pdo->prepare("SELECT imagen FROM producto_imagenes WHERE id_producto=? AND principal=1 LIMIT 1");
            $imgQ->execute([$prod['id']]);
            $imgFile = $imgQ->fetchColumn();
            if ($imgFile) {
                $imgSrc = UPLOADS_URL . htmlspecialchars($imgFile);
            } elseif (!empty($prod['imagen']) && file_exists(UPLOADS_DIR . $prod['imagen'])) {
                $imgSrc = UPLOADS_URL . htmlspecialchars($prod['imagen']);
            }
            $icono = $iconosCat[$prod['categoria']] ?? 'bi-controller';
            ?>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="gamer-card h-100">
                    <a href="<?= BASE_URL ?>/detalle.php?id=<?= $prod['id'] ?>" class="text-decoration-none">
                        <div class="product-img-wrap">
                            <?php if ($imgSrc): ?>
                                <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($prod['nombre']) ?>">
                            <?php else: ?>
                                <div class="no-img-placeholder">
                                    <i class="bi <?= $icono ?>"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="card-body p-3 d-flex flex-column" style="background:transparent">
                        <span class="badge-categoria mb-2 d-inline-block">
                            <i class="bi <?= $icono ?> me-1"></i><?= htmlspecialchars($prod['categoria']) ?>
                        </span>
                        <a href="<?= BASE_URL ?>/detalle.php?id=<?= $prod['id'] ?>" class="text-decoration-none">
                            <h6 class="fw-bold mb-1" style="color:var(--text-light)">
                                <?= htmlspecialchars($prod['marca']) ?> <?= htmlspecialchars($prod['nombre']) ?>
                            </h6>
                        </a>
                        <?php if (!empty($prod['descripcion'])): ?>
                            <p class="text-muted small flex-grow-1 mb-2">
                                <?= htmlspecialchars(mb_strimwidth($prod['descripcion'], 0, 75, '...')) ?>
                            </p>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="price-tag">$ <?= number_format($prod['precio'], 2) ?></span>
                            <small class="<?= $prod['stock'] > 5 ? 'stock-ok' : ($prod['stock'] > 0 ? 'stock-warn' : 'stock-out') ?>">
                                <i class="bi bi-box-seam me-1"></i><?= $prod['stock'] ?> uds.
                            </small>
                        </div>
                    </div>
                    <div class="p-3 pt-0" style="background:transparent">
                        <?php if (isAdmin()): ?>
                            <a href="<?= BASE_URL ?>/admin/editar.php?id=<?= $prod['id'] ?>"
                               class="btn btn-outline-cyan btn-sm w-100">
                                <i class="bi bi-pencil me-1"></i>Editar
                            </a>
                        <?php elseif (isLoggedIn()): ?>
                            <form method="POST" action="<?= BASE_URL ?>/carrito.php">
                                <input type="hidden" name="accion"      value="agregar">
                                <input type="hidden" name="id_producto" value="<?= $prod['id'] ?>">
                                <button type="submit" class="btn btn-cyan btn-sm w-100">
                                    <i class="bi bi-cart-plus me-1"></i>Agregar al carrito
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-outline-cyan btn-sm w-100">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Inicia sesión para comprar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
