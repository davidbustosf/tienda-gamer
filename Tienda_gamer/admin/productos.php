<?php
// ============================================================
// TIENDA GAMER - Listado de productos (Admin)
// INTEGRANTE 1: Subir carpeta admin/ completa
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
requireAdmin();

$busqueda  = trim($_GET['buscar']    ?? '');
$categoria = trim($_GET['categoria'] ?? '');
$filtro    = trim($_GET['filtro']    ?? '');

$where  = "WHERE 1=1";
$params = [];

if ($busqueda !== '') {
    $where   .= " AND (marca LIKE ? OR nombre LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}
if ($categoria !== '') {
    $where   .= " AND categoria = ?";
    $params[] = $categoria;
}
if ($filtro === 'sin_stock') {
    $where .= " AND stock = 0";
}

$categorias = $pdo->query("SELECT DISTINCT categoria FROM productos ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);
$stmt       = $pdo->prepare("SELECT * FROM productos $where ORDER BY id DESC");
$stmt->execute($params);
$productos  = $stmt->fetchAll();

$pageTitle = 'Gestión de Productos';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-controller me-2 text-cyan"></i>Productos</h4>
        <small class="text-muted"><?= count($productos) ?> productos encontrados</small>
    </div>
    <a href="<?= BASE_URL ?>/admin/crear.php" class="btn btn-cyan">
        <i class="bi bi-plus-lg me-1"></i>Nuevo Producto
    </a>
</div>

<!-- Filtros -->
<form method="GET" class="row g-2 mb-4">
    <div class="col-md-5">
        <div class="input-group">
            <span class="input-group-text gamer-ig"><i class="bi bi-search"></i></span>
            <input type="text" name="buscar" class="form-control gamer-input"
                   placeholder="Buscar por marca o nombre..."
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
        <button type="submit" class="btn btn-cyan w-100"><i class="bi bi-funnel me-1"></i>Filtrar</button>
    </div>
    <?php if ($busqueda || $categoria || $filtro): ?>
    <div class="col-md-2">
        <a href="productos.php" class="btn btn-outline-secondary w-100">
            <i class="bi bi-x-lg me-1"></i>Limpiar
        </a>
    </div>
    <?php endif; ?>
</form>

<!-- Tabla -->
<div class="gamer-form-card">
    <div class="table-responsive">
        <table class="table gamer-table align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Imagen</th>
                    <th>Categoría</th>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($productos)): ?>
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-search d-block fs-1 mb-2"></i>
                        No se encontraron productos.
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($productos as $prod): ?>
                <?php
                $imgQ = $pdo->prepare("SELECT imagen FROM producto_imagenes WHERE id_producto=? AND principal=1 LIMIT 1");
                $imgQ->execute([$prod['id']]);
                $imgFile = $imgQ->fetchColumn();
                $imgSrc  = '';
                if ($imgFile) $imgSrc = UPLOADS_URL . htmlspecialchars($imgFile);
                elseif (!empty($prod['imagen'])) $imgSrc = UPLOADS_URL . htmlspecialchars($prod['imagen']);
                ?>
                <tr>
                    <td><small class="text-muted">#<?= $prod['id'] ?></small></td>
                    <td>
                        <?php if ($imgSrc): ?>
                            <img src="<?= $imgSrc ?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px;border:1px solid rgba(9,216,199,.2)">
                        <?php else: ?>
                            <div style="width:48px;height:48px;border-radius:6px;background:var(--navy);display:flex;align-items:center;justify-content:center">
                                <i class="bi bi-controller text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge-categoria"><?= htmlspecialchars($prod['categoria']) ?></span></td>
                    <td>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($prod['marca']) ?> <?= htmlspecialchars($prod['nombre']) ?></p>
                    </td>
                    <td class="price-tag">$ <?= number_format($prod['precio'], 2) ?></td>
                    <td>
                        <span class="<?= $prod['stock'] > 5 ? 'stock-ok' : ($prod['stock'] > 0 ? 'stock-warn' : 'stock-out') ?> fw-semibold">
                            <?= $prod['stock'] ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="<?= BASE_URL ?>/admin/editar.php?id=<?= $prod['id'] ?>"
                           class="btn btn-sm btn-outline-cyan me-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/admin/eliminar.php?id=<?= $prod['id'] ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('¿Eliminar este producto?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
