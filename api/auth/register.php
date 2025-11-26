<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../database.php';

use PHPMailer\PHPMailer\PHPMailer;
use Ramsey\Uuid\Uuid;

function generateVerificationToken() {
    return Uuid::uuid4()->toString();
}

function generateVerificationCode() {
    return rand(1000, 9999);
}

function sendMail($toEmail, $toName, $subject, $body) {
    global $env;

    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = $env['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $env['SMTP_USER'];
        $mail->Password   = $env['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $env['SMTP_PORT'];

        //Recipients
        $mail->setFrom($env['SMTP_FROM_EMAIL'], $env['SMTP_FROM_NAME']);
        $mail->addAddress($toEmail, $toName);

        //Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function validateInput($username, $email, $password) {
    $errors = [];

    if (empty($username) || strlen($username) < 3 || strlen($username) > 20) {
        $errors[] = "Username harus antara 3 hingga 20 karakter.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password harus minimal 8 karakter.";
    }

    return null;
}

function register($username, $email, $password) {
    global $conn, $env;

    // Validasi input
    $validationError = validateInput($username, $email, $password);

    if ($validationError !== null) {
        return ["success" => false, "message" => $validationError];
    }

    // Cek apakah email sudah terdaftar
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        return ["success" => false, "message" => "Email sudah terdaftar."];
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $verificationToken = generateVerificationToken();
    $verificationCode = generateVerificationCode();

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, verification_token, verification_code) VALUES (?, ?, ?, ?, ?, ?)");
    $role = 'user';
    $stmt->bind_param("ssssss", $username, $email, $hashedPassword, $role, $verificationToken, $verificationCode);
    if ($stmt->execute()) {
        // Kirim email verifikasi
        $subject = "Verifikasi Akun Anda";
        $body = "
            <h1>Verifikasi Akun Anda</h1>
            <p>Terima kasih telah mendaftar, $username!</p>
            <p>Silakan gunakan kode berikut untuk memverifikasi akun Anda: <strong>$verificationCode</strong></p>
            <p>Verifikasi akun Anda dengan mengunjungi tautan berikut:</p>
            <a href='" . $env['BASE_URL'] . "/frontend/auth/verify.html?token=$verificationToken'>Verifikasi Akun</a>
        ";

        if (sendMail($email, $username, $subject, $body)) {
            return ["success" => true, "message" => "Registrasi berhasil. Silakan periksa email Anda untuk verifikasi."];
        } else {
            return ["success" => false, "message" => "Gagal mengirim email verifikasi."];
        }
    } else {
        return ["success" => false, "message" => "Gagal melakukan registrasi."];
    }
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');

        try {
            $result = register($username, $email, $password);
        } catch (Exception $e) {
            http_response_code(500);
            $result = ["success" => false, "message" => "Terjadi kesalahan pada server."];
        }

        header('Content-Type: application/json');
        http_response_code($result['success'] ? 200 : 400);
        echo json_encode($result);
        break;

    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
        break;
}