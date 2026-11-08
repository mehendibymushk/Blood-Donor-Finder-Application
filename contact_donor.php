<?php
session_start();

require_once "db.php";
require_once __DIR__ . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = require __DIR__ . "/mail-config.php";

$donor_id = filter_input(INPUT_GET, "donor_id", FILTER_VALIDATE_INT);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $donor_id = filter_input(INPUT_POST, "donor_id", FILTER_VALIDATE_INT);
}

if (!$donor_id || $donor_id < 1) {
    exit("Invalid donor. Please return to the search page.");
}

$stmt = $conn->prepare(
    "SELECT name, email FROM donors WHERE id = ?"
);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$donor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$donor || !filter_var($donor["email"], FILTER_VALIDATE_EMAIL)) {
    exit("This donor was not found or has no valid email address.");
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $sender_name = trim($_POST["sender_name"] ?? "");
    $sender_email = trim($_POST["sender_email"] ?? "");
    $message = trim($_POST["message"] ?? "");

    if (
        $sender_name === "" ||
        !filter_var($sender_email, FILTER_VALIDATE_EMAIL) ||
        $message === ""
    ) {
        $error = "Please enter your name, a valid email and your message.";
    } elseif (
        strlen($sender_name) > 100 ||
        strlen($sender_email) > 254 ||
        strlen($message) > 5000
    ) {
        $error = "Your name, email or message is too long.";
    } else {
        try {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = "smtp.gmail.com";
            $mail->SMTPAuth = true;
            $mail->Username = $config["email"];
            $mail->Password = $config["app_password"];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->CharSet = "UTF-8";
            $mail->setFrom($config["email"], "Blood Donor Finder");
            $mail->addAddress($donor["email"], $donor["name"]);
            $mail->addReplyTo($sender_email, $sender_name);

            $mail->isHTML(false);
            $mail->Subject = "Blood Donation Enquiry";
            $mail->Body =
                "Hello " . $donor["name"] . ",\n\n" .
                "You have received a message through Blood Donor Finder.\n\n" .
                "From: " . $sender_name . "\n" .
                "Reply email: " . $sender_email . "\n\n" .
                "Message:\n" . $message . "\n\n" .
                "Please reply only if you are comfortable being contacted.";

            $mail->send();
            $success = "Your email was sent successfully to the donor!";
        } catch (Exception $e) {
            error_log("Donor email failed: " . $mail->ErrorInfo);
            $error = "The email could not be sent. Please check your email configuration and try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Donor</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .email-form {
            max-width: 550px;
            margin: 35px auto;
            padding: 24px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px #ddd;
        }

        .email-form input,
        .email-form textarea {
            display: block;
            box-sizing: border-box;
            width: 100%;
            padding: 12px;
            margin: 10px 0 18px;
        }

        .email-form textarea {
            min-height: 140px;
            resize: vertical;
        }

        .email-form button {
            padding: 12px 20px;
            cursor: pointer;
        }

        .status {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 6px;
        }

        .success {
            background: #e4f7e8;
            color: #17652a;
        }

        .error {
            background: #ffe7e7;
            color: #a40000;
        }
    </style>
</head>

<body>

<header>
    <div class="logo">🩸 Blood Donor Finder</div>
    <nav>
        <a href="index.html">Home</a>
        <a href="search.php">Find Donors</a>
    </nav>
</header>

<div class="email-form">

    <h2>Email Donor</h2>

    <p>
        <strong>To:</strong>
        <?php echo htmlspecialchars($donor["name"], ENT_QUOTES, "UTF-8"); ?>
    </p>

    <?php if ($success !== ""): ?>

        <p class="status success">
            <?php echo htmlspecialchars($success, ENT_QUOTES, "UTF-8"); ?>
        </p>

        <a href="search.php">Back to Find Donors</a>

    <?php else: ?>

        <?php if ($error !== ""): ?>
            <p class="status error">
                <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="contact_donor.php">

            <input
                type="hidden"
                name="donor_id"
                value="<?php echo (int)$donor_id; ?>"
            >

            <label>Your Name</label>
            <input
                type="text"
                name="sender_name"
                maxlength="100"
                required
                value="<?php echo htmlspecialchars($_POST["sender_name"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
            >

            <label>Your Email</label>
            <input
                type="email"
                name="sender_email"
                maxlength="254"
                required
                value="<?php echo htmlspecialchars($_POST["sender_email"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
            >

            <label>Your Message</label>
            <textarea
                name="message"
                maxlength="5000"
                required
            ><?php echo htmlspecialchars($_POST["message"] ?? "", ENT_QUOTES, "UTF-8"); ?></textarea>

            <button type="submit">📧 Send Email</button>

        </form>

    <?php endif; ?>

</div>

</body>
</html>