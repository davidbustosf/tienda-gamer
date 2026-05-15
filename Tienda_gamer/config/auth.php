<?php
// ============================================================
// TIENDA GAMER - Funciones de autenticación y sesión
// INTEGRANTE 1: Subir carpeta config/ completa
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['rol'] !== 'admin') {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function isLoggedIn() {
    return isset($_SESSION['usuario_id']);
}

function isAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}
