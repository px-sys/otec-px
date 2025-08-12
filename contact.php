<?php
// contact.php
// Requiere PHPMailer. Si usas Composer: `composer require phpmailer/phpmailer`
// O bien sube la carpeta PHPMailer y ajusta los require de abajo.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php'; // <- si usas Composer
// Si NO usas Composer, comenta la línea anterior y descomenta estas 3:
// require __DIR__ . '/PHPMailer/src/Exception.php';
// require __DIR__ . '/PHPMailer/src/PHPMailer.php';
// require __DIR__ . '/PHPMailer/src/SMTP.php';

header('Content-Type: text/html; charset=utf-8');

// --------- Ajusta estos valores ----------
$toName  = 'OTEC Px';
$toEmail = 'contacto@otecpx.cl';      // destino real
$bcc     = 'mcifuentes@hcuch.cl';     // BCC opcional
$host    = 'mail.tu-dominio.cl';      // SMTP de tu cPanel (p.ej. mail.otecpx.cl)
$username= 'contacto@otecpx.cl';      // cuenta SMTP
$password= 'TU_PASSWORD_SMTP';        // clave SMTP
$port    = 465;                        // 465 (SSL) o 587 (TLS)
$secure  = 'ssl';                      // 'ssl' o 'tls'
// -----------------------------------------

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Método no permitido');
}

// Honeypot
if (!empty($_POST['website'])) {
  http_response_code(200);
  exit('OK'); // Silencio a bots
}

// Sanitizar entradas
$nombre  = trim(filter_input(INPUT_POST, 'nombre',  FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$empresa = trim(filter_input(INPUT_POST, 'empresa', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$email   = trim(filter_input(INPUT_POST, 'email',   FILTER_SANITIZE_EMAIL));
$interes = trim(filter_input(INPUT_POST, 'interes', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$mensaje = trim(filter_input(INPUT_POST, 'mensaje', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

// Validaciones mínimas
$errors = [];
if ($nombre === '')  { $errors[] = 'El nombre es obligatorio.'; }
if ($empresa === '') { $errors[] = 'La empresa es obligatoria.'; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'El correo no es válido.'; }
if ($interes === '') { $errors[] = 'Selecciona un interés.'; }

if ($errors) {
  http_response_code(422);
  echo implode('<br>', $errors);
  exit;
}

// Armar contenido
$subject = 'Nueva consulta desde el sitio OTEC Px';
$bodyHtml = "
  <h2>Consulta de contacto</h2>
  <p><strong>Nombre y cargo:</strong> {$nombre}</p>
  <p><strong>Empresa:</strong> {$empresa}</p>
  <p><strong>Correo:</strong> {$email}</p>
  <p><strong>Interés:</strong> {$interes}</p>
  <p><strong>Mensaje:</strong><br>" . nl2br($mensaje) . "</p>
  <hr>
  <small>IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . " • Fecha: " . date('Y-m-d H:i:s') . "</small>
";
$bodyTxt = "Consulta de contacto\n\n".
           "Nombre y cargo: {$nombre}\n".
           "Empresa: {$empresa}\n".
           "Correo: {$email}\n".
           "Interés: {$interes}\n".
           "Mensaje:\n{$mensaje}\n\n".
           "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . " • Fecha: " . date('Y-m-d H:i:s');

// Enviar con PHPMailer
$mail = new PHPMailer(true);
try {
  $mail->CharSet   = 'UTF-8';
  $mail->isSMTP();
  $mail->Host       = $host;
  $mail->SMTPAuth   = true;
  $mail->Username   = $username;
  $mail->Password   = $password;
  $mail->SMTPSecure = $secure;
  $mail->Port       = $port;

  $mail->setFrom($username, 'Sitio OTEC Px');
  $mail->addAddress($toEmail, $toName);
  if (!empty($bcc)) { $mail->addBCC($bcc); }

  // Responder a quien envió
  $mail->addReplyTo($email, $nombre);

  $mail->isHTML(true);
  $mail->Subject = $subject;
  $mail->Body    = $bodyHtml;
  $mail->AltBody = $bodyTxt;

  $mail->send();

  // Redirigir a gracias (ancla) o devolver OK
  if (!empty($_POST['_redirect'])) {
    header('Location: ' . $_POST['_redirect']);
  } else {
    echo 'OK';
  }
} catch (Exception $e) {
  http_response_code(500);
  echo 'No se pudo enviar el correo. Error: ' . htmlspecialchars($mail->ErrorInfo, ENT_QUOTES, 'UTF-8');
}
