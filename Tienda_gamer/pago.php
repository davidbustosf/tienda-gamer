<?php
// ============================================================
// TIENDA GAMER - Página de pago
// INTEGRANTE 2: Subir este archivo (pago.php)
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
requireLogin();

if (isAdmin() || empty($_SESSION['carrito'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$carrito = $_SESSION['carrito'];
$total   = array_sum(array_map(fn($i) => $i['precio'] * $i['cantidad'], $carrito));
$ref     = 'GZ-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
$error_pago = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metodo = $_POST['metodo_pago'] ?? '';
    if (!in_array($metodo, ['qr', 'tarjeta', 'efectivo'])) {
        $error_pago = 'Selecciona un método de pago válido.';
    } else {
        if ($metodo === 'tarjeta') {
            $numero = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
            $nombre = trim($_POST['card_name'] ?? '');
            $expiry = trim($_POST['card_expiry'] ?? '');
            $cvv    = trim($_POST['card_cvv'] ?? '');
            if (strlen($numero) < 16 || empty($nombre) || empty($expiry) || strlen($cvv) < 3) {
                $error_pago = 'Completa correctamente los datos de la tarjeta.';
            }
        }

        if (!$error_pago) {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO ventas (id_usuario, total, metodo_pago) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['usuario_id'], $total, $metodo]);
                $idVenta = $pdo->lastInsertId();

                foreach ($carrito as $id => $item) {
                    $pdo->prepare("INSERT INTO detalle_venta (id_venta, id_producto, nombre_producto, precio, cantidad) VALUES (?,?,?,?,?)")
                        ->execute([$idVenta, $id, $item['nombre'], $item['precio'], $item['cantidad']]);
                    $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?")
                        ->execute([$item['cantidad'], $id]);
                }

                $pdo->commit();
                $_SESSION['carrito']      = [];
                $_SESSION['pago_exitoso'] = ['total' => $total, 'metodo' => $metodo, 'ref' => $ref];
                header('Location: ' . BASE_URL . '/pago_exitoso.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_pago = 'Error al procesar el pago. Inténtalo de nuevo.';
            }
        }
    }
}

$qr_data = urlencode("GamerZone | Ref: $ref | Total: $$total");
$qr_url  = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=$qr_data&color=09D8C7&bgcolor=0D1A2F&margin=10";

$pageTitle = 'Método de Pago';
include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-10">

    <?php if ($error_pago): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error_pago) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Panel de pago -->
        <div class="col-lg-7">
            <div class="gamer-form-card">
                <div class="card-header px-4 py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-wallet2 me-2"></i>Método de Pago</h5>
                </div>
                <div class="p-4">
                    <ul class="nav nav-pills mb-4 gap-2" id="payTabs">
                        <li class="nav-item">
                            <button class="nav-link active px-4" data-bs-toggle="pill" data-bs-target="#pane-qr">
                                <i class="bi bi-qr-code me-2"></i>QR
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link px-4" data-bs-toggle="pill" data-bs-target="#pane-tarjeta">
                                <i class="bi bi-credit-card me-2"></i>Tarjeta
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link px-4" data-bs-toggle="pill" data-bs-target="#pane-efectivo">
                                <i class="bi bi-cash me-2"></i>Efectivo
                            </button>
                        </li>
                    </ul>

                    <form method="POST" id="formPago" novalidate>
                        <input type="hidden" name="metodo_pago" id="metodo_pago" value="qr">

                        <div class="tab-content">
                            <!-- QR -->
                            <div class="tab-pane fade show active" id="pane-qr">
                                <div class="text-center py-2">
                                    <div class="d-flex justify-content-center gap-3 mb-4">
                                        <span class="badge bg-success fs-6 px-3 py-2"><i class="bi bi-phone me-1"></i>Yape</span>
                                        <span class="badge bg-primary fs-6 px-3 py-2"><i class="bi bi-phone me-1"></i>Plin</span>
                                    </div>
                                    <img src="<?= $qr_url ?>" alt="QR de pago" class="rounded mb-3"
                                         style="width:200px;height:200px;border:2px solid var(--cyan);padding:8px;background:var(--navy)">
                                    <p class="fw-bold price-tag fs-4 mb-1">$ <?= number_format($total, 2) ?></p>
                                    <p class="text-muted small">Referencia: <strong><?= $ref ?></strong></p>
                                    <div class="alert alert-info text-start small mt-3">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Escanea el QR con Yape o Plin y luego confirma el pago.
                                    </div>
                                </div>
                            </div>

                            <!-- Tarjeta -->
                            <div class="tab-pane fade" id="pane-tarjeta">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Número de tarjeta</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-credit-card"></i></span>
                                            <input type="text" id="card_number" name="card_number" class="form-control"
                                                   placeholder="1234 5678 9012 3456" maxlength="19" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Nombre en la tarjeta</label>
                                        <input type="text" name="card_name" class="form-control"
                                               placeholder="Como aparece en tu tarjeta" autocomplete="off">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Vencimiento</label>
                                        <input type="text" name="card_expiry" id="card_expiry" class="form-control"
                                               placeholder="MM/AA" maxlength="5" autocomplete="off">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">CVV</label>
                                        <input type="password" name="card_cvv" id="card_cvv" class="form-control"
                                               placeholder="***" maxlength="4" autocomplete="off">
                                    </div>
                                    <div class="col-12">
                                        <small class="text-muted">
                                            <i class="bi bi-shield-lock me-1 stock-ok"></i>Pago seguro simulado
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Efectivo -->
                            <div class="tab-pane fade" id="pane-efectivo">
                                <div class="text-center py-3">
                                    <i class="bi bi-cash-stack stock-ok" style="font-size:4rem"></i>
                                    <h5 class="mt-3 fw-bold">Pago contra entrega</h5>
                                    <p class="text-muted">Paga al recibir tu pedido.</p>
                                    <div class="alert alert-warning text-start small">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Ten listo el monto exacto: <strong>$ <?= number_format($total, 2) ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-cyan btn-lg py-3 fw-semibold">
                                <i class="bi bi-bag-check me-2"></i>Confirmar Pago — $ <?= number_format($total, 2) ?>
                            </button>
                        </div>
                    </form>

                    <a href="<?= BASE_URL ?>/carrito.php" class="btn btn-outline-secondary w-100 mt-2">
                        <i class="bi bi-arrow-left me-1"></i>Volver al carrito
                    </a>
                </div>
            </div>
        </div>

        <!-- Resumen del pedido -->
        <div class="col-lg-5">
            <div class="gamer-form-card">
                <div class="card-header px-3 py-2 mb-2">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-receipt me-2"></i>Tu pedido</h6>
                </div>
                <ul class="list-unstyled px-3 pb-0">
                    <?php foreach ($carrito as $item): ?>
                        <?php
                        $imgSrc = '';
                        if (!empty($item['imagen']) && file_exists(UPLOADS_DIR . $item['imagen'])) {
                            $imgSrc = UPLOADS_URL . htmlspecialchars($item['imagen']);
                        }
                        ?>
                        <li class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid rgba(9,216,199,.1)">
                            <?php if ($imgSrc): ?>
                                <img src="<?= $imgSrc ?>" class="rounded" style="width:45px;height:45px;object-fit:cover">
                            <?php else: ?>
                                <div class="bg-navy rounded d-flex align-items-center justify-content-center" style="width:45px;height:45px">
                                    <i class="bi bi-controller text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <p class="mb-0 small fw-semibold"><?= htmlspecialchars($item['nombre']) ?></p>
                                <small class="text-muted">x<?= $item['cantidad'] ?></small>
                            </div>
                            <span class="price-tag small">$ <?= number_format($item['precio'] * $item['cantidad'], 2) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="px-3 py-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Envío</span>
                        <span class="stock-ok">Gratis</span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold" style="font-size:1.15rem">
                        <span>Total</span>
                        <span class="price-tag">$ <?= number_format($total, 2) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
document.querySelectorAll('#payTabs button').forEach(function (btn) {
    btn.addEventListener('shown.bs.tab', function (e) {
        document.getElementById('metodo_pago').value = e.target.getAttribute('data-bs-target').replace('#pane-', '');
    });
});
const cardNum = document.getElementById('card_number');
if (cardNum) {
    cardNum.addEventListener('input', function () {
        let v = this.value.replace(/\D/g, '').substring(0, 16);
        this.value = v.match(/.{1,4}/g)?.join(' ') || v;
    });
}
const cardExp = document.getElementById('card_expiry');
if (cardExp) {
    cardExp.addEventListener('input', function () {
        let v = this.value.replace(/\D/g, '').substring(0, 4);
        if (v.length >= 3) v = v.substring(0, 2) + '/' + v.substring(2);
        this.value = v;
    });
}
const cardCvv = document.getElementById('card_cvv');
if (cardCvv) {
    cardCvv.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').substring(0, 4);
    });
}
document.getElementById('formPago').addEventListener('submit', function (e) {
    if (document.getElementById('metodo_pago').value === 'tarjeta') {
        const num  = (cardNum.value || '').replace(/\s/g, '');
        const name = document.querySelector('[name=card_name]')?.value.trim() || '';
        const exp  = cardExp?.value.trim() || '';
        const cvv  = cardCvv?.value.trim() || '';
        if (num.length < 16 || !name || exp.length < 5 || cvv.length < 3) {
            e.preventDefault();
            alert('Completa correctamente los datos de la tarjeta.');
        }
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
