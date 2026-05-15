<?php
// ============================================================
// TIENDA GAMER - Confirmación de pago
// INTEGRANTE 2: Subir este archivo (pago_exitoso.php)
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';
requireLogin();

if (empty($_SESSION['pago_exitoso'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$info = $_SESSION['pago_exitoso'];
unset($_SESSION['pago_exitoso']);

$metodos = [
    'qr'       => ['QR / Yape / Plin', 'bi-qr-code',    'success'],
    'tarjeta'  => ['Tarjeta de crédito/débito', 'bi-credit-card', 'primary'],
    'efectivo' => ['Pago contra entrega', 'bi-cash-stack', 'warning'],
];
$m = $metodos[$info['metodo']] ?? ['Pago', 'bi-check', 'success'];

$pageTitle = '¡Pago Exitoso!';
include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center mt-3">
    <div class="col-md-6 col-lg-5 text-center">
        <div class="gamer-form-card p-5">
            <div class="mb-4">
                <i class="bi bi-check-circle-fill text-cyan" style="font-size:5rem"></i>
            </div>
            <h3 class="fw-bold mb-2">¡Pago Confirmado!</h3>
            <p class="text-muted mb-4">Tu pedido ha sido registrado exitosamente.</p>

            <div class="p-3 rounded mb-4" style="background:rgba(9,216,199,.08);border:1px solid rgba(9,216,199,.2)">
                <p class="text-muted small mb-1">Número de referencia</p>
                <p class="fw-bold fs-5 text-cyan mb-3"><?= htmlspecialchars($info['ref']) ?></p>

                <p class="text-muted small mb-1">Total pagado</p>
                <p class="fw-bold price-tag fs-4 mb-3">$ <?= number_format($info['total'], 2) ?></p>

                <p class="text-muted small mb-1">Método de pago</p>
                <span class="badge bg-<?= $m[2] ?> px-3 py-2">
                    <i class="bi <?= $m[1] ?> me-2"></i><?= $m[0] ?>
                </span>
            </div>

            <p class="text-muted small mb-4">
                <i class="bi bi-envelope me-1 text-cyan"></i>
                Recibirás tu pedido en 24-48 horas hábiles.
            </p>

            <a href="<?= BASE_URL ?>/index.php" class="btn btn-cyan w-100 py-2">
                <i class="bi bi-shop me-2"></i>Seguir comprando
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
