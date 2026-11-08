<?php
session_start();

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/vendor/autoload.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit("Invalid request.");
}

// Only logged-in users can send emails.
if (empty($_SESSION["user_id"])) {
    exit('Please <a href="login.php">log in</a> before contacting a donor.');
}

$donor_id = filter_input(INPUT_POST, "donor_id", FILTER_VALIDATE_INT);

if (!$donor_id) {
    exit("Invalid donor.");
}

// Get the selected donor's email from the database.
$stmt = $conn->prepare(
    "SELECT name, email FROM donors WHERE id = ?"
);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();
$donor = $result->fetch_assoc();
$stmt->close();

if (!$donor) {
    exit("Donor not found.");
}

try {
    $config = require __DIR__ . "/mail-config.php";

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;
    $mail->Username = $config["email"];
    $mail->Password = $config["app_password"];
    $mail->SMTPSecure =
        \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom($config["email"], "Blood Donor Finder");
    $mail->addAddress($donor["email"], $donor["name"]);

    $mail->isHTML(false);
    $mail->Subject = "Blood Donation Enquiry";
    $mail->Body =
        "Hello " . $donor["name"] . ",\n\n" .
        "I am contacting you through Blood Donor Finder regarding a blood donation enquiry.\n\n" .
        "Please reply to this email if you are available to help.\n\n" .
        "Thank you.";

    $mail->send();

    echo "Email sent successfully to the selected donor!<br>";
    echo '<a href="search.php">Back to Find Donor</a>';

} catch (\Throwable $e) {
    error_log("Donor email error: " . $e->getMessage());
    echo "Sorry, the email could not be sent. Please check your email settings.";
}
?>