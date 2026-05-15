<?php
// ============================================================
// TIENDA GAMER - Dashboard Admin
// INTEGRANTE 1: Subir carpeta admin/ completa
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
requireAdmin();

$totalProductos = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
$totalUsuarios  = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$totalVentas    = $pdo->query("SELECT COUNT(*) FROM ventas")->fetchColumn();
$totalIngresos  = $pdo->query("SELECT COALESCE(SUM(total),0) FROM ventas")->fetchColumn();
$sinStock       = $pdo->query("SELECT COUNT(*) FROM productos WHERE stock = 0")->fetchColumn();
$ultVentas      = $pdo->query("
    SELECT v.*, u.nombre FROM ventas v
    JOIN usuarios u ON v.id_usuario = u.id_usuario
    ORDER BY v.fecha DESC LIMIT 5
")->fetchAll();
$porCategoria = $pdo->query("
    SELECT categoria, COUNT(*) as total FROM productos GROUP BY categoria ORDER BY total DESC
")->fetchAll();

$pageTitle = 'Dashboard Admin';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">
            <i class="bi bi-speedometer2 me-2 text-cyan"></i>Panel de Administración
        </h4>
        <small class="text-muted">Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?></small>
    </div>
    <a href="<?= BASE_URL ?>/admin/crear.php" class="btn btn-cyan">
        <i class="bi bi-plus-lg me-1"></i>Nuevo Producto
    </a>
</div>

<!-- Estadísticas -->
<div class="row g-3 mb-4">
    <?php
    $stats = [
        ['Productos',  $totalProductos,         'bi-controller',  'rgba(9,216,199,.2)',  'var(--cyan)'],
        ['Usuarios',   $totalUsuarios,           'bi-people',      'rgba(189,9,39,.2)',   'var(--red)'],
        ['Ventas',     $totalVentas,             'bi-receipt',     'rgba(72,187,120,.2)', '#48bb78'],
        ['Ingresos',   '$ '.number_format($totalIngresos,2), 'bi-cash-stack', 'rgba(246,173,85,.2)', '#f6ad55'],
    ];
    foreach ($stats as [$label, $val, $ico, $bg, $color]): ?>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card p-3 h-100 d-flex align-items-center gap-3">
            <div class="stat-icon" style="background:<?= $bg ?>">
                <i class="bi <?= $ico ?> fs-4" style="color:<?= $color ?>"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0" style="color:<?= $color ?>"><?= $val ?></h4>
                <small class="text-muted"><?= $label ?></small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($sinStock > 0): ?>
<div class="alert alert-warning d-flex align-items-center mb-4">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <div>
        <strong><?= $sinStock ?> producto(s) sin stock.</strong>
        <a href="<?= BASE_URL ?>/admin/productos.php?filtro=sin_stock" class="ms-2" style="color:var(--red)">Ver detalles</a>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Últimas ventas -->
    <div class="col-lg-7">
        <div class="gamer-form-card">
            <div class="card-header d-flex justify-content-between align-items-center px-3 py-2">
                <span class="fw-bold"><i class="bi bi-clock-history me-2"></i>Últimas Ventas</span>
                <a href="<?= BASE_URL ?>/admin/ventas.php" class="btn btn-sm btn-outline-cyan">Ver todas</a>
            </div>
            <div class="table-responsive">
                <?php if (empty($ultVentas)): ?>
                    <p class="text-muted text-center py-4">No hay ventas registradas.</p>
                <?php else: ?>
                    <table class="table gamer-table mb-0">
                        <thead><tr><th>#</th><th>Cliente</th><th>Total</th><th>Fecha</th></tr></thead>
                        <tbody>
                        <?php foreach ($ultVentas as $v): ?>
                            <tr>
                                <td><span class="badge" style="background:rgba(9,216,199,.2);color:var(--cyan)">#<?= $v['id_venta'] ?></span></td>
                                <td><?= htmlspecialchars($v['nombre']) ?></td>
                                <td class="price-tag fw-bold">$ <?= number_format($v['total'], 2) ?></td>
                                <td><small class="text-muted"><?= date('d/m/Y', strtotime($v['fecha'])) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Acciones rápidas + categorías -->
    <div class="col-lg-5">
        <div class="gamer-form-card mb-4">
            <div class="card-header px-3 py-2">
                <span class="fw-bold"><i class="bi bi-lightning me-2"></i>Acciones Rápidas</span>
            </div>
            <div class="p-3 d-grid gap-2">
                <a href="<?= BASE_URL ?>/admin/crear.php"    class="btn btn-cyan"><i class="bi bi-plus-lg me-2"></i>Agregar producto</a>
                <a href="<?= BASE_URL ?>/admin/productos.php" class="btn btn-outline-cyan"><i class="bi bi-list-ul me-2"></i>Ver todos los productos</a>
                <a href="<?= BASE_URL ?>/admin/ventas.php"    class="btn btn-outline-secondary"><i class="bi bi-receipt me-2"></i>Gestionar ventas</a>
                <a href="<?= BASE_URL ?>/index.php"           class="btn btn-outline-secondary"><i class="bi bi-shop me-2"></i>Ver tienda pública</a>
            </div>
        </div>

        <div class="gamer-form-card">
            <div class="card-header px-3 py-2">
                <span class="fw-bold"><i class="bi bi-pie-chart me-2"></i>Productos por Categoría</span>
            </div>
            <ul class="list-unstyled p-3 mb-0">
                <?php foreach ($porCategoria as $cat): ?>
                <li class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted"><?= htmlspecialchars($cat['categoria']) ?></span>
                    <span class="badge-categoria"><?= $cat['total'] ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
