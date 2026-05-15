<?php
// ============================================================
// TIENDA GAMER - Header / Navbar
// INTEGRANTE 1: Subir carpeta includes/ completa
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : '' ?>GamerZone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark gamer-navbar shadow">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= BASE_URL ?>/index.php">
            <i class="bi bi-controller-fill text-cyan"></i>
            <span>Gamer<span class="text-cyan">Zone</span></span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/index.php">
                        <i class="bi bi-shop me-1"></i>Tienda
                    </a>
                </li>
                <?php if (isAdmin()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-speedometer2 me-1"></i>Administración
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark gamer-dropdown">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/index.php">
                            <i class="bi bi-grid me-2 text-cyan"></i>Dashboard
                        </a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/productos.php">
                            <i class="bi bi-controller me-2 text-cyan"></i>Productos
                        </a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/ventas.php">
                            <i class="bi bi-receipt me-2 text-cyan"></i>Ventas
                        </a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if (isLoggedIn()): ?>
                    <?php if (!isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?= BASE_URL ?>/carrito.php">
                            <i class="bi bi-cart3 fs-5"></i>
                            <?php
                            $cartCount = isset($_SESSION['carrito']) ? array_sum(array_column($_SESSION['carrito'], 'cantidad')) : 0;
                            if ($cartCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-red">
                                    <?= $cartCount ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                            <span class="avatar-sm"><i class="bi bi-person-circle"></i></span>
                            <?= htmlspecialchars($_SESSION['nombre']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end gamer-dropdown">
                            <?php if (isAdmin()): ?>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/index.php">
                                <i class="bi bi-speedometer2 me-2 text-cyan"></i>Panel Admin
                            </a></li>
                            <li><hr class="dropdown-divider border-secondary"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/auth/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/auth/login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Iniciar sesión
                        </a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-cyan btn-sm px-3 fw-semibold" href="<?= BASE_URL ?>/auth/registro.php">
                            Registrarse
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
<?php
function showAlert($msg, $type = 'success') {
    if (!empty($msg)) {
        echo "<div class='alert alert-{$type} alert-dismissible fade show gamer-alert' role='alert'>
                {$msg}
                <button type='button' class='btn-close btn-close-white' data-bs-dismiss='alert'></button>
              </div>";
    }
}
if (isset($_SESSION['msg'])) {
    showAlert($_SESSION['msg'], $_SESSION['msg_type'] ?? 'success');
    unset($_SESSION['msg'], $_SESSION['msg_type']);
}
?>
