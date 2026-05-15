<?php
// ============================================================
// TIENDA GAMER - Registro de usuarios
// INTEGRANTE 1: Subir carpeta auth/ completa
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $correo    = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $confirmar  = $_POST['confirmar']  ?? '';

    if (empty($nombre) || empty($correo) || empty($contrasena) || empty($confirmar)) {
        $error = 'Por favor completa todos los campos.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } elseif (strlen($contrasena) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($contrasena !== $confirmar) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        if ($stmt->fetch()) {
            $error = 'Ya existe una cuenta con ese correo electrónico.';
        } else {
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO usuarios (nombre, correo, contrasena, rol) VALUES (?, ?, ?, 'cliente')")
                ->execute([$nombre, $correo, $hash]);

            $_SESSION['msg']      = '¡Cuenta creada! Ya puedes iniciar sesión.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . BASE_URL . '/auth/login.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta | GamerZone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-body d-flex align-items-center min-vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="auth-card shadow-lg p-5">
                <div class="text-center mb-4">
                    <a href="<?= BASE_URL ?>/index.php" class="text-decoration-none">
                        <i class="bi bi-controller-fill text-cyan" style="font-size:2.8rem"></i>
                        <h3 class="fw-bold mt-2 auth-title">Gamer<span class="text-cyan">Zone</span></h3>
                    </a>
                    <p class="auth-subtitle small">Crea tu cuenta gratis</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show small">
                        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" id="formRegistro" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Nombre completo</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="nombre" class="form-control"
                                   value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                                   placeholder="Tu nombre" required minlength="3">
                            <div class="invalid-feedback">Ingresa tu nombre (mínimo 3 caracteres).</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Correo electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="correo" class="form-control"
                                   value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>"
                                   placeholder="correo@ejemplo.com" required>
                            <div class="invalid-feedback">Ingresa un correo válido.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="contrasena" id="contrasena" class="form-control"
                                   placeholder="Mínimo 6 caracteres" required minlength="6">
                            <button type="button" class="btn btn-outline-secondary" id="tp1"><i class="bi bi-eye"></i></button>
                            <div class="invalid-feedback">Mínimo 6 caracteres.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Confirmar contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="confirmar" id="confirmar" class="form-control"
                                   placeholder="Repite tu contraseña" required minlength="6">
                            <button type="button" class="btn btn-outline-secondary" id="tp2"><i class="bi bi-eye"></i></button>
                            <div class="invalid-feedback">Las contraseñas no coinciden.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-cyan w-100 py-2">
                        <i class="bi bi-person-plus me-2"></i>Crear Cuenta
                    </button>
                </form>

                <hr class="border-secondary my-4">
                <p class="text-center mb-0 text-muted small">
                    ¿Ya tienes cuenta?
                    <a href="<?= BASE_URL ?>/auth/login.php" class="text-cyan fw-semibold">Inicia sesión</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleVis(btnId, inputId) {
    document.getElementById(btnId).addEventListener('click', function () {
        const input = document.getElementById(inputId);
        const icon  = this.querySelector('i');
        input.type  = input.type === 'password' ? 'text' : 'password';
        icon.classList.toggle('bi-eye');
        icon.classList.toggle('bi-eye-slash');
    });
}
toggleVis('tp1', 'contrasena');
toggleVis('tp2', 'confirmar');

document.getElementById('formRegistro').addEventListener('submit', function (e) {
    const pass = document.getElementById('contrasena').value;
    const conf = document.getElementById('confirmar').value;
    document.getElementById('confirmar').setCustomValidity(pass !== conf ? 'Las contraseñas no coinciden.' : '');
    if (!this.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
    this.classList.add('was-validated');
});
</script>
</body>
</html>
