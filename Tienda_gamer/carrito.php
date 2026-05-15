<?php
// ============================================================
// TIENDA GAMER - Carrito de compras
// INTEGRANTE 2: Subir este archivo (carrito.php)
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
requireLogin();

if (isAdmin()) {
    $_SESSION['msg']      = 'Los administradores no pueden realizar compras.';
    $_SESSION['msg_type'] = 'warning';
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

// Agregar producto
if ($accion === 'agregar' && isset($_POST['id_producto'])) {
    $id  = intval($_POST['id_producto']);
    $qty = max(1, intval($_POST['cantidad'] ?? 1));

    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ? AND stock > 0");
    $stmt->execute([$id]);
    $prod = $stmt->fetch();

    if ($prod) {
        if (isset($_SESSION['carrito'][$id])) {
            $nueva = $_SESSION['carrito'][$id]['cantidad'] + $qty;
            $_SESSION['carrito'][$id]['cantidad'] = min($nueva, $prod['stock']);
        } else {
            $_SESSION['carrito'][$id] = [
                'id'       => $prod['id'],
                'nombre'   => $prod['marca'] . ' ' . $prod['nombre'],
                'precio'   => $prod['precio'],
                'imagen'   => $prod['imagen'] ?? '',
                'cantidad' => min($qty, $prod['stock']),
            ];
        }
        $_SESSION['msg']      = 'Producto agregado al carrito.';
        $_SESSION['msg_type'] = 'success';
    }
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Quitar un producto
if ($accion === 'quitar' && isset($_GET['id'])) {
    unset($_SESSION['carrito'][intval($_GET['id'])]);
    header('Location: ' . BASE_URL . '/carrito.php');
    exit;
}

// Vaciar carrito
if ($accion === 'vaciar') {
    $_SESSION['carrito'] = [];
    header('Location: ' . BASE_URL . '/carrito.php');
    exit;
}

// Actualizar cantidades
if ($accion === 'actualizar' && isset($_POST['cantidades'])) {
    foreach ($_POST['cantidades'] as $id => $qty) {
        $id  = intval($id);
        $qty = intval($qty);
        if (isset($_SESSION['carrito'][$id])) {
            if ($qty <= 0) unset($_SESSION['carrito'][$id]);
            else $_SESSION['carrito'][$id]['cantidad'] = $qty;
        }
    }
    header('Location: ' . BASE_URL . '/carrito.php');
    exit;
}

// Ir al pago
if ($accion === 'ir_pago' && !empty($_SESSION['carrito'])) {
    header('Location: ' . BASE_URL . '/pago.php');
    exit;
}

$carrito = $_SESSION['carrito'];
$total   = array_sum(array_map(fn($i) => $i['precio'] * $i['cantidad'], $carrito));

$pageTitle = 'Carrito de Compras';
include __DIR__ . '/includes/header.php';
?>

<h4 class="fw-bold mb-4"><i class="bi bi-cart3 me-2 text-cyan"></i>Mi Carrito</h4>

<?php if (empty($carrito)): ?>
    <div class="text-center py-5">
        <i class="bi bi-cart-x text-muted" style="font-size:4rem"></i>
        <p class="mt-3 fs-5 text-muted">Tu carrito está vacío.</p>
        <a href="<?= BASE_URL ?>/index.php" class="btn btn-cyan mt-2">
            <i class="bi bi-shop me-2"></i>Ir a la tienda
        </a>
    </div>
<?php else: ?>
    <div class="row g-4">
        <!-- Tabla de productos -->
        <div class="col-lg-8">
            <div class="gamer-form-card">
                <form method="POST" id="formCarrito">
                    <input type="hidden" name="accion" value="actualizar">
                    <div class="table-responsive">
                        <table class="table gamer-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th style="width:120px">Cantidad</th>
                                    <th>Precio</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($carrito as $id => $item): ?>
                                <?php
                                $imgSrc = '';
                                if (!empty($item['imagen']) && file_exists(UPLOADS_DIR . $item['imagen'])) {
                                    $imgSrc = UPLOADS_URL . htmlspecialchars($item['imagen']);
                                }
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <?php if ($imgSrc): ?>
                                                <img src="<?= $imgSrc ?>" class="cart-item-img" alt="">
                                            <?php else: ?>
                                                <div class="cart-item-img bg-navy d-flex align-items-center justify-content-center">
                                                    <i class="bi bi-controller text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <span class="fw-semibold"><?= htmlspecialchars($item['nombre']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" name="cantidades[<?= $id ?>]"
                                               class="form-control gamer-input form-control-sm"
                                               value="<?= $item['cantidad'] ?>" min="1" max="99">
                                    </td>
                                    <td class="text-muted">$ <?= number_format($item['precio'], 2) ?></td>
                                    <td class="price-tag fw-bold">$ <?= number_format($item['precio'] * $item['cantidad'], 2) ?></td>
                                    <td>
                                        <a href="carrito.php?accion=quitar&id=<?= $id ?>"
                                           class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 d-flex gap-2">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
                        </button>
                        <a href="carrito.php?accion=vaciar" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash me-1"></i>Vaciar carrito
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resumen -->
        <div class="col-lg-4">
            <div class="gamer-form-card">
                <div class="card-header px-3 py-2 mb-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-receipt me-2"></i>Resumen del pedido</h6>
                </div>
                <div class="px-3 pb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal</span>
                        <span>$ <?= number_format($total, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Envío</span>
                        <span class="stock-ok">Gratis</span>
                    </div>
                    <hr style="border-color:rgba(9,216,199,.2)">
                    <div class="d-flex justify-content-between fw-bold mb-4" style="font-size:1.15rem">
                        <span>Total</span>
                        <span class="price-tag">$ <?= number_format($total, 2) ?></span>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="accion" value="ir_pago">
                        <button type="submit" class="btn btn-cyan w-100 py-2 fw-semibold">
                            <i class="bi bi-credit-card me-2"></i>Proceder al Pago
                        </button>
                    </form>
                    <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-secondary w-100 mt-2">
                        <i class="bi bi-arrow-left me-1"></i>Seguir comprando
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
