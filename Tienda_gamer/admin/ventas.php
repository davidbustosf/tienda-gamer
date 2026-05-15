<?php
// ============================================================
// TIENDA GAMER - Historial de ventas (Admin)
// INTEGRANTE 1: Subir carpeta admin/ completa
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
requireAdmin();

$buscarCliente = trim($_GET['cliente']     ?? '');
$filtMetodo    = trim($_GET['metodo']      ?? '');
$filtPeriodo   = trim($_GET['periodo']     ?? '');
$fechaDesde    = trim($_GET['fecha_desde'] ?? '');
$fechaHasta    = trim($_GET['fecha_hasta'] ?? '');

$where  = "WHERE 1=1";
$params = [];

if ($buscarCliente !== '') {
    $where   .= " AND (u.nombre LIKE ? OR u.correo LIKE ?)";
    $params[] = "%$buscarCliente%";
    $params[] = "%$buscarCliente%";
}
if ($filtMetodo !== '') {
    $where   .= " AND v.metodo_pago = ?";
    $params[] = $filtMetodo;
}

switch ($filtPeriodo) {
    case 'hoy':   $where .= " AND DATE(v.fecha) = CURDATE()"; break;
    case 'semana': $where .= " AND YEARWEEK(v.fecha,1) = YEARWEEK(CURDATE(),1)"; break;
    case 'mes':   $where .= " AND YEAR(v.fecha) = YEAR(CURDATE()) AND MONTH(v.fecha) = MONTH(CURDATE())"; break;
    case 'año':   $where .= " AND YEAR(v.fecha) = YEAR(CURDATE())"; break;
    case 'exacta':
        if ($fechaDesde) { $where .= " AND DATE(v.fecha) = ?"; $params[] = $fechaDesde; }
        break;
    case 'rango':
        if ($fechaDesde) { $where .= " AND DATE(v.fecha) >= ?"; $params[] = $fechaDesde; }
        if ($fechaHasta) { $where .= " AND DATE(v.fecha) <= ?"; $params[] = $fechaHasta; }
        break;
}

$sql    = "SELECT v.*, u.nombre, u.correo FROM ventas v JOIN usuarios u ON v.id_usuario=u.id_usuario $where ORDER BY v.fecha DESC";
$stmt   = $pdo->prepare($sql);
$stmt->execute($params);
$ventas = $stmt->fetchAll();

$sumStmt = $pdo->prepare("SELECT COALESCE(SUM(v.total),0), COUNT(*) FROM ventas v JOIN usuarios u ON v.id_usuario=u.id_usuario $where");
$sumStmt->execute($params);
[$totalMonto, $totalCount] = $sumStmt->fetch(PDO::FETCH_NUM);

$metodoTotales = [];
foreach (['qr','tarjeta','efectivo'] as $m) {
    $mStmt = $pdo->prepare("SELECT COALESCE(SUM(v.total),0), COUNT(*) FROM ventas v JOIN usuarios u ON v.id_usuario=u.id_usuario $where AND v.metodo_pago='$m'");
    $mStmt->execute($params);
    [$monto, $cnt] = $mStmt->fetch(PDO::FETCH_NUM);
    $metodoTotales[$m] = ['monto' => $monto, 'count' => $cnt];
}

$pageTitle = 'Historial de Ventas';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center mb-4 gap-3">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-receipt me-2 text-cyan"></i>Ventas
    </h4>
    <span class="badge-categoria"><?= $totalCount ?> registros</span>
</div>

<!-- Resumen por método -->
<div class="row g-3 mb-4">
    <?php
    $metStmt = [
        ['Total recaudado', '$ '.number_format($totalMonto,2), 'bi-cash-stack', 'var(--cyan)', 'rgba(9,216,199,.15)', ''],
        ['QR / Yape / Plin', '$ '.number_format($metodoTotales['qr']['monto'],2).'<br><small class="text-muted">'.$metodoTotales['qr']['count'].' ventas</small>', 'bi-qr-code', '#48bb78', 'rgba(72,187,120,.12)', ''],
        ['Tarjeta', '$ '.number_format($metodoTotales['tarjeta']['monto'],2).'<br><small class="text-muted">'.$metodoTotales['tarjeta']['count'].' ventas</small>', 'bi-credit-card', '#63b3ed', 'rgba(99,179,237,.12)', ''],
        ['Efectivo', '$ '.number_format($metodoTotales['efectivo']['monto'],2).'<br><small class="text-muted">'.$metodoTotales['efectivo']['count'].' ventas</small>', 'bi-cash', '#f6ad55', 'rgba(246,173,85,.12)', ''],
    ];
    foreach ($metStmt as [$label, $val, $ico, $color, $bg, $_]): ?>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card p-3 text-center" style="background:<?= $bg ?>">
            <i class="bi <?= $ico ?> fs-3 mb-2" style="color:<?= $color ?>"></i>
            <p class="text-muted small mb-1"><?= $label ?></p>
            <h5 class="fw-bold mb-0" style="color:<?= $color ?>"><?= $val ?></h5>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filtros -->
<div class="gamer-form-card mb-4">
    <div class="p-3">
        <form method="GET" id="formFiltros">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Cliente</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="cliente" class="form-control gamer-input"
                               placeholder="Nombre o correo..." value="<?= htmlspecialchars($buscarCliente) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Método</label>
                    <select name="metodo" class="form-select form-select-sm gamer-select">
                        <option value="">Todos</option>
                        <option value="qr"       <?= $filtMetodo==='qr'      ?'selected':'' ?>>QR / Yape / Plin</option>
                        <option value="tarjeta"  <?= $filtMetodo==='tarjeta' ?'selected':'' ?>>Tarjeta</option>
                        <option value="efectivo" <?= $filtMetodo==='efectivo'?'selected':'' ?>>Efectivo</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Período</label>
                    <select name="periodo" class="form-select form-select-sm gamer-select" id="selectPeriodo"
                            onchange="toggleFechas(this.value)">
                        <option value="">Todos</option>
                        <option value="hoy"    <?= $filtPeriodo==='hoy'   ?'selected':'' ?>>Hoy</option>
                        <option value="semana" <?= $filtPeriodo==='semana'?'selected':'' ?>>Esta semana</option>
                        <option value="mes"    <?= $filtPeriodo==='mes'   ?'selected':'' ?>>Este mes</option>
                        <option value="año"    <?= $filtPeriodo==='año'   ?'selected':'' ?>>Este año</option>
                        <option value="exacta" <?= $filtPeriodo==='exacta'?'selected':'' ?>>Fecha exacta</option>
                        <option value="rango"  <?= $filtPeriodo==='rango' ?'selected':'' ?>>Rango</option>
                    </select>
                </div>
                <div class="col-md-3" id="wrapFechas" style="<?= in_array($filtPeriodo,['exacta','rango'])?'':'display:none' ?>">
                    <label class="form-label small mb-1" id="lblFecha">Fecha</label>
                    <div id="inputFechaExacta" style="<?= $filtPeriodo==='rango'?'display:none':'' ?>">
                        <input type="date" name="fecha_desde" class="form-control form-control-sm gamer-input"
                               value="<?= htmlspecialchars($fechaDesde) ?>">
                    </div>
                    <div class="input-group input-group-sm" id="inputFechaRango" style="<?= $filtPeriodo==='rango'?'':'display:none' ?>">
                        <input type="date" name="fecha_desde" class="form-control gamer-input" value="<?= htmlspecialchars($fechaDesde) ?>">
                        <input type="date" name="fecha_hasta" class="form-control gamer-input" value="<?= htmlspecialchars($fechaHasta) ?>">
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-cyan btn-sm flex-grow-1">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <?php if ($buscarCliente || $filtMetodo || $filtPeriodo): ?>
                    <a href="ventas.php" class="btn btn-outline-secondary btn-sm" title="Limpiar">
                        <i class="bi bi-x-lg"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="gamer-form-card">
    <div class="table-responsive">
        <table class="table gamer-table align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Correo</th>
                    <th>Método</th>
                    <th>Total</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($ventas)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-receipt d-block fs-1 mb-2"></i>No hay ventas con estos filtros.
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($ventas as $v):
                $badges = [
                    'qr'       => ['stock-ok',  'bi-qr-code',    'QR'],
                    'tarjeta'  => ['text-info',  'bi-credit-card','Tarjeta'],
                    'efectivo' => ['stock-warn', 'bi-cash-stack', 'Efectivo'],
                ];
                $b = $badges[$v['metodo_pago']] ?? ['text-muted','bi-question',$v['metodo_pago']];
            ?>
                <tr>
                    <td><span class="badge-categoria">#<?= $v['id_venta'] ?></span></td>
                    <td class="fw-semibold"><?= htmlspecialchars($v['nombre']) ?></td>
                    <td><small class="text-muted"><?= htmlspecialchars($v['correo']) ?></small></td>
                    <td>
                        <span class="<?= $b[0] ?>">
                            <i class="bi <?= $b[1] ?> me-1"></i><?= $b[2] ?>
                        </span>
                    </td>
                    <td class="price-tag fw-bold">$ <?= number_format($v['total'], 2) ?></td>
                    <td>
                        <span><?= date('d/m/Y', strtotime($v['fecha'])) ?></span>
                        <small class="text-muted d-block"><?= date('H:i', strtotime($v['fecha'])) ?></small>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <?php if (!empty($ventas)): ?>
            <tfoot>
                <tr style="background:rgba(9,216,199,.05)">
                    <td colspan="4" class="text-end fw-bold text-muted">Total filtrado:</td>
                    <td class="price-tag fw-bold">$ <?= number_format($totalMonto, 2) ?></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<script>
function toggleFechas(val) {
    const wrap   = document.getElementById('wrapFechas');
    const exacta = document.getElementById('inputFechaExacta');
    const rango  = document.getElementById('inputFechaRango');
    const lbl    = document.getElementById('lblFecha');
    if (val === 'exacta') {
        wrap.style.display = '';
        exacta.style.display = '';
        rango.style.display  = 'none';
        lbl.textContent = 'Fecha exacta';
    } else if (val === 'rango') {
        wrap.style.display = '';
        exacta.style.display = 'none';
        rango.style.display  = '';
        lbl.textContent = 'Rango de fechas';
    } else {
        wrap.style.display = 'none';
    }
}
toggleFechas('<?= $filtPeriodo ?>');
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
