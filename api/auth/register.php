<?php

require __DIR__ . '/../database.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../../vendor/autoload.php';

use Ramsey\Uuid\Uuid;
use PHPMailer\PHPMailer\PHPMailer;

function usernameValidation($username) {
    if (empty($username)) {
        return [
            'status' => false,
            'message' => 'Username is required'
        ];
    } else if (strlen($username) < 3 || strlen($username) > 20) {
        return [
            'status' => false,
            'message' => 'Username must be between 3 and 20 characters'
        ];
    } else {
        return [
            'status' => true,
            'message' => 'Valid username'
        ];
    }
}

function emailValidation($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'status' => false,
            'message' => 'Invalid email format'
        ];
    } else {
        return [
            'status' => true,
            'message' => 'Valid email'
        ];
    }
}

function passwordValidation($password) {
    if (strlen($password) < 6) {
        return [
            'status' => false,
            'message' => 'Password must be at least 6 characters long'
        ];
    } else {
        return [
            'status' => true,
            'message' => 'Valid password'
        ];
    }
}

function validate($username, $email, $password) {
    $usernameCheck = usernameValidation($username);
    if (!$usernameCheck['status']) {
        return $usernameCheck;
    }

    $emailCheck = emailValidation($email);
    if (!$emailCheck['status']) {
        return $emailCheck;
    }

    $passwordCheck = passwordValidation($password);
    if (!$passwordCheck['status']) {
        return $passwordCheck;
    }

    return [
        'status' => true,
        'message' => 'All inputs are valid'
    ];
}

function sendMail($to, $subject, $body) {
    global $env;

    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = $env['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $env['SMTP_USER'];
        $mail->Password   = $env['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $env['SMTP_PORT'];

        // Sender & Recipient
        $mail->setFrom($env['SMTP_FROM_EMAIL'], $env['SMTP_FROM_NAME']);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
    } catch (Exception $e) {
        throw new Exception('Failed to send email.');
    }
}

function generateVerificationCode() {
    return rand(1000, 9999);
}

function register($username, $email, $password) {
    global $conn;
    global $env;

    // Validate inputs
    $validationResult = validate($username, $email, $password);

    if (!$validationResult['status']) {
        http_response_code(400);
        echo json_encode(['message' => $validationResult['message']]);
        return;
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['message' => 'Email already exists']);
        return;
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $token = Uuid::uuid4()->toString(); // generate unique verification token

    // Insert user into database
    $stmt = $conn->prepare("INSERT INTO user (username, email, password, verify_token) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashedPassword, $token);

    if ($stmt->execute()) {
        $verificationCode = generateVerificationCode();

        try {
            sendMail($email, "Welcome to Car Rental", "
                <h1>Registration Successful</h1>
                <p>Thank you for registering, $username!</p>
                <p>Please verify your email address by entering the code in the link below: </p>
                <a href='" . $env['BASE_URL'] . "/frontend/auth/verify.html?verify_token=$token'>Verify Email</a>
                <p>Your verification code is:</p>
                <h2>$verificationCode<h2>
            ");
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to send verification email.']);
            return;
        }

        // Store verification code in database
        $stmt = $conn->prepare("UPDATE user SET verification_code = ? WHERE email = ?");
        $stmt->bind_param("ss", $verificationCode, $email);
        $stmt->execute();

        http_response_code(201);
        echo json_encode(['message' => 'User registered successfully. Please check your email for verification.']);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Registration failed']);
    }
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'];
        $email = $data['email'];
        $password = $data['password'];
        register($username, $email, $password);
        break;
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method Not Allowed']);
        break;
}