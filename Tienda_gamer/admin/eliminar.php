<?php
// ============================================================
// TIENDA GAMER - Eliminar producto (Admin)
// INTEGRANTE 1: Subir carpeta admin/ completa
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
requireAdmin();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . '/admin/productos.php');
    exit;
}

// Eliminar imágenes físicas
$imgs = $pdo->prepare("SELECT imagen FROM producto_imagenes WHERE id_producto = ?");
$imgs->execute([$id]);
foreach ($imgs->fetchAll(PDO::FETCH_COLUMN) as $img) {
    if ($img && file_exists(UPLOADS_DIR . $img)) unlink(UPLOADS_DIR . $img);
}

// Imagen legacy
$row = $pdo->prepare("SELECT imagen FROM productos WHERE id = ?");
$row->execute([$id]);
$legacyImg = $row->fetchColumn();
if ($legacyImg && file_exists(UPLOADS_DIR . $legacyImg)) unlink(UPLOADS_DIR . $legacyImg);

$pdo->prepare("DELETE FROM productos WHERE id = ?")->execute([$id]);

$_SESSION['msg']      = 'Producto eliminado correctamente.';
$_SESSION['msg_type'] = 'success';
header('Location: ' . BASE_URL . '/admin/productos.php');
exit;
