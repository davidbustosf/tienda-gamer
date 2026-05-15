<?php
// ============================================================
// TIENDA GAMER - Verificación de código 2 pasos
// INTEGRANTE 1: Subir carpeta auth/ completa
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/mailer.php';

// Si ya está logueado o no hay verificación pendiente, redirigir
if (isLoggedIn() || empty($_SESSION['2fa_pendiente'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pendiente = $_SESSION['2fa_pendiente'];
$error     = '';
$expirado  = isset($_SESSION['2fa_expira']) && time() > $_SESSION['2fa_expira'];

// Reenviar código
if (isset($_GET['reenviar']) && !$expirado) {
    require_once __DIR__ . '/../config/db.php';
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$pendiente['id_usuario']]);
    $usuario = $stmt->fetch();
    if ($usuario) {
        $codigo = generarCodigo2FA();
        guardarCodigo2FA($codigo);
        enviarCodigo2FA($usuario['correo'], $usuario['nombre'], $codigo);
        $_SESSION['msg']      = 'Código reenviado a tu correo.';
        $_SESSION['msg_type'] = 'info';
    }
    header('Location: ' . BASE_URL . '/auth/verificar.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigoIngresado = trim(str_replace(' ', '', $_POST['codigo'] ?? ''));

    if ($expirado) {
        $error = 'El código ha expirado. Solicita uno nuevo.';
    } elseif (empty($codigoIngresado) || strlen($codigoIngresado) !== 6) {
        $error = 'Ingresa el código de 6 dígitos.';
    } elseif (!ctype_digit($codigoIngresado)) {
        $error = 'El código solo debe contener números.';
    } elseif (($_SESSION['2fa_intentos'] ?? 0) >= 5) {
        $error = 'Demasiados intentos fallidos. Inicia sesión de nuevo.';
        limpiarCodigo2FA();
        unset($_SESSION['2fa_pendiente']);
    } elseif (verificarCodigo2FA($codigoIngresado)) {
        // ✅ Código correcto → completar login
        $_SESSION['usuario_id'] = $pendiente['id_usuario'];
        $_SESSION['nombre']     = $pendiente['nombre'];
        $_SESSION['correo']     = $pendiente['correo'];
        $_SESSION['rol']        = $pendiente['rol'];

        limpiarCodigo2FA();
        unset($_SESSION['2fa_pendiente']);

        $_SESSION['msg']      = '¡Bienvenido, ' . htmlspecialchars($pendiente['nombre']) . '!';
        $_SESSION['msg_type'] = 'success';

        header('Location: ' . ($pendiente['rol'] === 'admin' ? BASE_URL . '/admin/index.php' : BASE_URL . '/index.php'));
        exit;
    } else {
        $intentosRestantes = 5 - ($_SESSION['2fa_intentos'] ?? 0);
        $error = "Código incorrecto. Te quedan $intentosRestantes intento(s).";
    }
}

// Calcular tiempo restante
$tiempoRestante = max(0, ($_SESSION['2fa_expira'] ?? 0) - time());
$minutos = floor($tiempoRestante / 60);
$segundos = $tiempoRestante % 60;

// Correo parcialmente oculto para privacidad
$correoMasked = '';
if (!empty($pendiente['correo'])) {
    $parts = explode('@', $pendiente['correo']);
    $local = $parts[0];
    $dom   = $parts[1] ?? '';
    $correoMasked = substr($local, 0, 2) . str_repeat('*', max(0, strlen($local) - 4)) . substr($local, -2) . '@' . $dom;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación 2 Pasos | GamerZone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-body d-flex align-items-center min-vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="auth-card shadow-lg p-5">

                <!-- Header -->
                <div class="text-center mb-4">
                    <div class="verify-icon-wrap mx-auto mb-3">
                        <i class="bi bi-shield-lock-fill text-cyan" style="font-size:2.5rem"></i>
                    </div>
                    <h4 class="fw-bold auth-title mb-1">Verificación 2 Pasos</h4>
                    <p class="auth-subtitle small mb-0">
                        Enviamos un código a<br>
                        <strong class="text-cyan"><?= htmlspecialchars($correoMasked) ?></strong>
                    </p>
                </div>

                <!-- Tiempo restante -->
                <?php if (!$expirado && $tiempoRestante > 0): ?>
                <div class="timer-bar mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="auth-subtitle">Tiempo restante</small>
                        <small class="text-cyan fw-bold" id="timerDisplay">
                            <?= sprintf('%02d:%02d', $minutos, $segundos) ?>
                        </small>
                    </div>
                    <div class="progress" style="height:4px;background:rgba(9,216,199,.15)">
                        <div class="progress-bar bg-cyan" id="timerBar"
                             style="width:<?= ($tiempoRestante / 600) * 100 ?>%;transition:width 1s linear"></div>
                    </div>
                </div>
                <?php elseif ($expirado): ?>
                <div class="alert alert-danger small mb-4">
                    <i class="bi bi-clock me-2"></i>
                    El código expiró.
                    <a href="<?= BASE_URL ?>/auth/verificar.php?reenviar=1" class="alert-link ms-1">Reenviar código</a>
                </div>
                <?php endif; ?>

                <!-- Errores / alertas -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show small">
                        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['msg'])): ?>
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle me-2"></i><?= htmlspecialchars($_SESSION['msg']) ?>
                    </div>
                    <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
                <?php endif; ?>

                <!-- MODO DEBUG: muestra el código en pantalla (solo local) -->
                <?php if (defined('MAIL_DEBUG') && MAIL_DEBUG && isset($_SESSION['2fa_debug_codigo'])): ?>
                <div class="alert small mb-4" style="background:rgba(246,173,85,.12);border:1px solid rgba(246,173,85,.35);color:#f6ad55">
                    <i class="bi bi-bug me-2"></i>
                    <strong>Modo desarrollo:</strong> Tu código es
                    <span class="fw-bold fs-5 ms-2" style="letter-spacing:4px;color:#09D8C7">
                        <?= htmlspecialchars($_SESSION['2fa_debug_codigo']) ?>
                    </span>
                    <br><small class="opacity-75">Elimina esto en producción configurando MAIL_DEBUG=false</small>
                </div>
                <?php endif; ?>

                <!-- Formulario -->
                <form method="POST" id="formVerificar" novalidate>
                    <div class="mb-4">
                        <label class="form-label auth-label text-center d-block mb-3">
                            Ingresa el código de 6 dígitos
                        </label>
                        <!-- Inputs individuales por dígito -->
                        <div class="d-flex gap-2 justify-content-center" id="codeInputs">
                            <?php for ($i = 0; $i < 6; $i++): ?>
                            <input type="text" maxlength="1" inputmode="numeric"
                                   class="code-digit form-control text-center fw-bold fs-4"
                                   style="width:46px;height:56px;padding:0"
                                   autocomplete="off">
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="codigo" id="codigoHidden">
                    </div>

                    <button type="submit" class="btn btn-cyan w-100 py-2 fw-semibold" id="btnVerificar" <?= $expirado ? 'disabled' : '' ?>>
                        <i class="bi bi-check-circle me-2"></i>Verificar Código
                    </button>
                </form>

                <div class="text-center mt-3">
                    <a href="<?= BASE_URL ?>/auth/verificar.php?reenviar=1"
                       class="small text-cyan text-decoration-none <?= $expirado ? '' : 'opacity-50 pe-none' ?>"
                       id="linkReenviar">
                        <i class="bi bi-arrow-clockwise me-1"></i>Reenviar código
                    </a>
                </div>

                <hr class="auth-divider my-3">
                <div class="text-center">
                    <a href="<?= BASE_URL ?>/auth/login.php"
                       class="small auth-subtitle text-decoration-none"
                       onclick="<?php session_destroy(); ?>">
                        <i class="bi bi-arrow-left me-1"></i>Volver al inicio de sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Inputs de dígitos individuales ──────────────────────────
const digits  = document.querySelectorAll('.code-digit');
const hidden  = document.getElementById('codigoHidden');
const btnVerif = document.getElementById('btnVerificar');

digits.forEach((input, idx) => {
    input.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(-1);
        syncHidden();
        if (this.value && idx < digits.length - 1) digits[idx + 1].focus();
    });
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Backspace' && !this.value && idx > 0) {
            digits[idx - 1].focus();
            digits[idx - 1].value = '';
            syncHidden();
        }
    });
    input.addEventListener('paste', function (e) {
        const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
        e.preventDefault();
        pasted.split('').forEach((ch, i) => { if (digits[i]) digits[i].value = ch; });
        syncHidden();
        const nextEmpty = [...digits].findIndex(d => !d.value);
        (digits[nextEmpty] || digits[digits.length - 1]).focus();
    });
});

function syncHidden() {
    hidden.value = [...digits].map(d => d.value).join('');
}

document.getElementById('formVerificar').addEventListener('submit', function (e) {
    syncHidden();
    if (hidden.value.length !== 6) {
        e.preventDefault();
        digits[0].focus();
    }
});

// Focus en el primer campo al cargar
digits[0]?.focus();

// ── Temporizador ─────────────────────────────────────────────
<?php if (!$expirado && $tiempoRestante > 0): ?>
let remaining = <?= $tiempoRestante ?>;
const timerDisplay = document.getElementById('timerDisplay');
const timerBar     = document.getElementById('timerBar');
const linkReenviar = document.getElementById('linkReenviar');

const tick = setInterval(() => {
    remaining--;
    if (remaining <= 0) {
        clearInterval(tick);
        timerDisplay.textContent = '00:00';
        timerDisplay.style.color = '#BD0927';
        timerBar.style.width = '0%';
        timerBar.classList.replace('bg-cyan', 'bg-danger');
        btnVerif.disabled = true;
        if (linkReenviar) {
            linkReenviar.classList.remove('opacity-50','pe-none');
        }
        return;
    }
    const m = String(Math.floor(remaining / 60)).padStart(2,'0');
    const s = String(remaining % 60).padStart(2,'0');
    timerDisplay.textContent = `${m}:${s}`;
    timerBar.style.width = `${(remaining / 600) * 100}%`;
    if (remaining <= 60) timerDisplay.style.color = '#BD0927';
}, 1000);
<?php endif; ?>
</script>
</body>
</html>
