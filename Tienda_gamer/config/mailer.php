<?php
// ============================================================
// TIENDA GAMER - Envío de correos (2FA)
// INTEGRANTE 1: Subir junto con carpeta config/
//
// CONFIGURACIÓN XAMPP:
// En C:\xampp\php\php.ini busca [mail function] y configura:
//   SMTP = smtp.gmail.com
//   smtp_port = 587
//   sendmail_from = tucorreo@gmail.com
// O usa un servicio como Mailtrap para pruebas.
//
// Para pruebas locales: MAIL_DEBUG = true en config.php
// muestra el código directamente en pantalla.
// ============================================================

function generarCodigo2FA(): string {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function guardarCodigo2FA(string $codigo): void {
    $_SESSION['2fa_codigo']   = password_hash($codigo, PASSWORD_DEFAULT);
    $_SESSION['2fa_expira']   = time() + 600; // 10 minutos
    $_SESSION['2fa_intentos'] = 0;
}

function verificarCodigo2FA(string $codigoIngresado): bool {
    if (!isset($_SESSION['2fa_codigo'], $_SESSION['2fa_expira'])) return false;
    if (time() > $_SESSION['2fa_expira'])                         return false;
    if (($_SESSION['2fa_intentos'] ?? 0) >= 5)                    return false;

    $_SESSION['2fa_intentos']++;
    return password_verify($codigoIngresado, $_SESSION['2fa_codigo']);
}

function limpiarCodigo2FA(): void {
    unset($_SESSION['2fa_codigo'], $_SESSION['2fa_expira'], $_SESSION['2fa_intentos'], $_SESSION['2fa_pendiente']);
}

function enviarCodigo2FA(string $correo, string $nombre, string $codigo): bool {
    $asunto = 'GamerZone — Tu código de verificación';

    $cuerpo = "
    <!DOCTYPE html>
    <html lang='es'>
    <head><meta charset='UTF-8'></head>
    <body style='margin:0;padding:0;background:#0D1A2F;font-family:Segoe UI,sans-serif'>
      <div style='max-width:480px;margin:40px auto;background:#17364F;border-radius:16px;overflow:hidden;border:1px solid rgba(9,216,199,.3)'>
        <div style='background:linear-gradient(135deg,#17364F,#0D1A2F);padding:32px 24px;text-align:center;border-bottom:2px solid #09D8C7'>
          <p style='font-size:2rem;margin:0'>🎮</p>
          <h1 style='color:#e2e8f0;margin:8px 0 0;font-size:1.4rem'>Gamer<span style='color:#09D8C7'>Zone</span></h1>
        </div>
        <div style='padding:32px 24px;text-align:center'>
          <p style='color:#8ba3b8;margin:0 0 8px'>Hola, <strong style='color:#e2e8f0'>$nombre</strong></p>
          <p style='color:#8ba3b8;margin:0 0 24px;font-size:.95rem'>Tu código de verificación de 2 pasos es:</p>
          <div style='background:#0D1A2F;border:2px solid #09D8C7;border-radius:12px;padding:20px;letter-spacing:12px;font-size:2.4rem;font-weight:700;color:#09D8C7;margin:0 auto 24px'>$codigo</div>
          <p style='color:#8ba3b8;font-size:.85rem;margin:0'>Este código expira en <strong style='color:#e2e8f0'>10 minutos</strong>.<br>Si no iniciaste sesión, ignora este correo.</p>
        </div>
        <div style='background:#0D1A2F;padding:16px;text-align:center'>
          <p style='color:#4a6280;font-size:.75rem;margin:0'>&copy; " . date('Y') . " GamerZone &mdash; Todos los derechos reservados.</p>
        </div>
      </div>
    </body>
    </html>";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: GamerZone <noreply@tiendagamer.com>\r\n";
    $headers .= "X-Mailer: PHP/" . PHP_VERSION;

    $enviado = @mail($correo, $asunto, $cuerpo, $headers);

    // Modo debug: guarda el código en sesión para poder verlo en pantalla
    if (defined('MAIL_DEBUG') && MAIL_DEBUG) {
        $_SESSION['2fa_debug_codigo'] = $codigo;
    }

    return $enviado;
}
