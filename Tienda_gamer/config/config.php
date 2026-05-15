<?php
// ============================================================
// TIENDA GAMER - Configuración general
// INTEGRANTE 1: Subir carpeta config/ completa
// ============================================================

define('BASE_URL',    'http://localhost/Tienda_gamer');
define('UPLOADS_DIR', __DIR__ . '/../uploads/');
define('UPLOADS_URL', BASE_URL . '/uploads/');
define('SITE_NAME',   'GamerZone');

// MAIL_DEBUG = true → muestra el código 2FA en pantalla (solo para pruebas locales)
// Cambia a false cuando tengas SMTP configurado en producción
define('MAIL_DEBUG', true);
