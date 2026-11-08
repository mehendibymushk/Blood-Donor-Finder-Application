<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/mail-config.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $subject = trim($_POST["subject"] ?? "");
    $details = trim($_POST["details"] ?? "");

    if (
        $name === "" ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $subject === "" ||
        $details === ""
    ) {
        $message = "Please fill all fields correctly.";
    } else {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $config['email'];
            $mail->Password = $config['app_password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom($config['email'], 'Blood Donor Finder');
            $mail->addAddress($config['email']);
            $mail->addReplyTo($email, $name);

            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body =
                "Name: $name\n" .
                "Email: $email\n\n" .
                "Message:\n$details";

            $mail->send();
            $message = "Thank you! Your message was sent successfully.";
        } catch (Exception $e) {
            error_log("Contact email error: " . $mail->ErrorInfo);
            $message = "Sorry, the email could not be sent. Please check your email settings.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Blood Donor Finder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fff5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            margin: 35px auto;
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 12px #ddd;
        }
        h2 {
            color: #b71c1c;
            text-align: center;
        }
        label {
            display: block;
            margin-top: 14px;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        textarea {
            min-height: 110px;
        }
        button {
            width: 100%;
            margin-top: 20px;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background: #c62828;
            color: white;
            cursor: pointer;
        }
        .message {
            background: #ffebee;
            padding: 12px;
            overflow-wrap: anywhere;
        }
        a {
            color: #b71c1c;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>📧 Contact Us</h2>

    <?php if ($message !== ""): ?>
        <p class="message">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="contact.php">
        <label>Your Name</label>
        <input type="text" name="name" maxlength="100" required>

        <label>Your Email</label>
        <input type="email" name="email" maxlength="150" required>

        <label>Subject</label>
        <input type="text" name="subject" maxlength="150" required>

        <label>Your Message</label>
        <textarea name="details" maxlength="2000" required></textarea>

        <button type="submit">Send Message</button>
    </form>

    <p><a href="index.html">← Back to Home</a></p>
</div>
</body>
</html>