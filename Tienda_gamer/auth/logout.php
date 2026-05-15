<?php
// ============================================================
// TIENDA GAMER - Cerrar sesión
// INTEGRANTE 1: Subir carpeta auth/ completa
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

// Limpiar sesión completa incluyendo datos 2FA pendientes
session_unset();
session_destroy();
header('Location: ' . BASE_URL . '/index.php');
exit;
