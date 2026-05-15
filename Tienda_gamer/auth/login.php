<?php
// ============================================================
// TIENDA GAMER - Login con verificación 2 pasos
// INTEGRANTE 1: Subir carpeta auth/ completa
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/mailer.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Si hay verificación pendiente, redirigir
if (!empty($_SESSION['2fa_pendiente'])) {
    header('Location: ' . BASE_URL . '/auth/verificar.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo    = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if (empty($correo) || empty($contrasena)) {
        $error = 'Por favor completa todos los campos.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
            // Credenciales correctas → generar código 2FA
            $codigo = generarCodigo2FA();
            guardarCodigo2FA($codigo);

            // Guardar datos del usuario pendiente (no logueado aún)
            $_SESSION['2fa_pendiente'] = [
                'id_usuario' => $usuario['id_usuario'],
                'nombre'     => $usuario['nombre'],
                'correo'     => $usuario['correo'],
                'rol'        => $usuario['rol'],
            ];

            // Enviar código por correo
            enviarCodigo2FA($usuario['correo'], $usuario['nombre'], $codigo);

            header('Location: ' . BASE_URL . '/auth/verificar.php');
            exit;
        } else {
            $error = 'Correo o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | GamerZone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-body d-flex align-items-center min-vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="auth-card shadow-lg p-5">
                <div class="text-center mb-4">
                    <a href="<?= BASE_URL ?>/index.php" class="text-decoration-none">
                        <i class="bi bi-controller-fill text-cyan" style="font-size:2.8rem"></i>
                        <h3 class="fw-bold mt-2 auth-title">Gamer<span class="text-cyan">Zone</span></h3>
                    </a>
                    <p class="auth-subtitle">Inicia sesión en tu cuenta</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show small">
                        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" id="formLogin" novalidate>
                    <div class="mb-3">
                        <label class="form-label auth-label">Correo electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="correo" class="form-control"
                                   value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>"
                                   placeholder="correo@ejemplo.com" required>
                            <div class="invalid-feedback">Ingresa un correo válido.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label auth-label">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="contrasena" id="contrasena" class="form-control"
                                   placeholder="Tu contraseña" required minlength="6">
                            <button type="button" class="btn btn-outline-secondary" id="togglePass">
                                <i class="bi bi-eye"></i>
                            </button>
                            <div class="invalid-feedback">La contraseña es requerida.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-cyan w-100 py-2">
                        <i class="bi bi-shield-lock me-2"></i>Iniciar Sesión
                    </button>
                </form>

                <hr class="auth-divider my-4">
                <p class="text-center mb-0 auth-subtitle small">
                    ¿No tienes cuenta?
                    <a href="<?= BASE_URL ?>/auth/registro.php" class="text-cyan fw-semibold">Regístrate aquí</a>
                </p>
            </div>

            <p class="text-center mt-3 small" style="color:#4a6280">
                <i class="bi bi-shield-check me-1 text-cyan"></i>
                Después del login recibirás un código de verificación en tu correo.
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('togglePass').addEventListener('click', function () {
    const input = document.getElementById('contrasena');
    const icon  = this.querySelector('i');
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('bi-eye');
    icon.classList.toggle('bi-eye-slash');
});
document.getElementById('formLogin').addEventListener('submit', function (e) {
    if (!this.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
    this.classList.add('was-validated');
});
</script>
</body>
</html>
