<?php
require_once "db.php";
require_once "vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {

        $stmt = $conn->prepare("SELECT id FROM donors WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $token = bin2hex(random_bytes(32));
            $token_hash = hash("sha256", $token);

            $delete = $conn->prepare(
                "DELETE FROM password_resets WHERE email = ?"
            );
            $delete->bind_param("s", $email);
            $delete->execute();
            $delete->close();

            $expires = date("Y-m-d H:i:s", time() + 1800);

            $insert = $conn->prepare(
                "INSERT INTO password_resets
                (email, token_hash, expires_at)
                VALUES (?, ?, ?)"
            );

            $insert->bind_param("sss", $email, $token_hash, $expires);

            if ($insert->execute()) {

                $resetLink =
                    "http://localhost/BDFA/reset_password.php?token=" .
                    urlencode($token);

                try {
                    $config = require "mail-config.php";

                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = "smtp.gmail.com";
                    $mail->SMTPAuth = true;
                    $mail->Username = $config["email"];
                    $mail->Password = $config["app_password"];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom($config["email"], "Blood Donor Finder");
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = "Reset Your Blood Donor Finder Password";

                    $mail->Body = "
                        <h2>Password Reset Request</h2>
                        <p>Click the link below to create a new password:</p>
                        <p><a href='$resetLink'>Reset My Password</a></p>
                        <p>This link expires in 30 minutes.</p>
                        <p>If you did not request this, please ignore this email.</p>
                    ";

                    $mail->send();
                    $message = "If this email belongs to an account, a reset link has been sent.";

                } catch (Exception $e) {
                    $message = "Email could not be sent. Please try again later.";
                }

            } else {
                $message = "Unable to create a reset request. Please try again.";
            }

            $insert->close();

        } else {
            $message = "If this email belongs to an account, a reset link has been sent.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fff0f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 5px 20px #0002;
        }

        h2 { color: #d81b60; }

        input {
            width: 100%;
            padding: 13px;
            margin: 15px 0;
            box-sizing: border-box;
        }

        button {
            background: #e91e63;
            color: white;
            border: none;
            padding: 13px;
            width: 100%;
            cursor: pointer;
            border-radius: 8px;
        }

        a { color: #d81b60; }
        .message { margin: 15px 0; }
    </style>
</head>
<body>

<div class="card">
    <h2>Forgot Password?</h2>
    <p>Enter your registered email address.</p>

    <form method="POST">
        <input type="email" name="email"
               placeholder="Enter your email" required>
        <button type="submit">Send Reset Link</button>
    </form>

    <div class="message">
        <?= htmlspecialchars($message) ?>
    </div>

    <a href="login.php">Back to Login</a>
</div>

</body>
</html>