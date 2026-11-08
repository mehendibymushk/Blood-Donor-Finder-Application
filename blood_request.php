<?php
session_start();

require_once "db.php";
require_once __DIR__ . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = require __DIR__ . "/mail-config.php";

$success = "";
$error = "";

if (empty($_SESSION["request_csrf"])) {
    $_SESSION["request_csrf"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["request_csrf"], $_POST["csrf_token"])
    ) {
        $error = "Invalid request. Please refresh the page.";
    } else {

        $patient_name = trim($_POST["patient_name"] ?? "");
        $blood_group = trim($_POST["blood_group"] ?? "");
        $units = (int)($_POST["units"] ?? 0);
        $hospital_name = trim($_POST["hospital_name"] ?? "");
        $city = trim($_POST["city"] ?? "");
        $contact_name = trim($_POST["contact_name"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $message = trim($_POST["message"] ?? "");

        $valid_groups = [
            "A+", "A-", "B+", "B-",
            "AB+", "AB-", "O+", "O-"
        ];

        if (
            $patient_name === "" ||
            !in_array($blood_group, $valid_groups, true) ||
            $units < 1 || $units > 20 ||
            $hospital_name === "" ||
            $city === "" ||
            $contact_name === "" ||
            !preg_match('/^[0-9+\-\s]{7,15}$/', $phone) ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            $error = "Please enter valid details in all required fields.";

        } else {

            $sql = "INSERT INTO blood_requests
                    (patient_name, blood_group, units,
                     hospital_name, city, contact_name,
                     phone, email, message)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                $error = "Database error. Please try again.";

            } else {

                $stmt->bind_param(
                    "ssissssss",
                    $patient_name,
                    $blood_group,
                    $units,
                    $hospital_name,
                    $city,
                    $contact_name,
                    $phone,
                    $email,
                    $message
                );

                if ($stmt->execute()) {

                    $request_id = $stmt->insert_id;
                    $stmt->close();

                    try {

                        $mail = new PHPMailer(true);

                        $mail->isSMTP();
                        $mail->Host = "smtp.gmail.com";
                        $mail->SMTPAuth = true;
                        $mail->Username = $config["email"];
                        $mail->Password = $config["app_password"];
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        $mail->setFrom(
                            $config["email"],
                            "Blood Donor Finder"
                        );

                        $mail->addAddress($config["email"]);
                        $mail->addReplyTo($email, $contact_name);

                        $mail->isHTML(false);
                        $mail->Subject = "New Blood Request - Request #" . $request_id;

                        $mail->Body =
                            "NEW BLOOD REQUEST RECEIVED\n\n" .
                            "Request ID: #" . $request_id . "\n\n" .
                            "PATIENT DETAILS\n" .
                            "Patient Name: " . $patient_name . "\n" .
                            "Blood Group: " . $blood_group . "\n" .
                            "Units Required: " . $units . "\n\n" .
                            "HOSPITAL DETAILS\n" .
                            "Hospital: " . $hospital_name . "\n" .
                            "City: " . $city . "\n\n" .
                            "CONTACT DETAILS\n" .
                            "Contact Person: " . $contact_name . "\n" .
                            "Phone: " . $phone . "\n" .
                            "Email: " . $email . "\n\n" .
                            "Additional Message:\n" .
                            ($message !== "" ? $message : "No additional message") .
                            "\n\nPlease review this request in the admin panel.";

                        $mail->send();

                        $success = "Your blood request has been submitted successfully! The application owner has been notified by email. Request ID: #" . $request_id;

                    } catch (Exception $e) {

                        $success = "Your blood request was saved successfully! However, the email notification could not be sent. Please contact the application owner. Request ID: #" . $request_id;
                    }

                    $_SESSION["request_csrf"] = bin2hex(random_bytes(32));

                } else {
                    $error = "Unable to submit your request. Please try again.";
                    $stmt->close();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Blood Request | Blood Donor Finder</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #100b0d;
    color: #242424;
    min-height: 100vh;
}

/* HEADER */
header {
    background: linear-gradient(135deg, #16090d, #8f1028, #c51f3a);
    color: white;
    padding: 25px 15px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35);
    border-bottom: 3px solid #e33b50;
}

header h2 {
    font-size: 27px;
    font-weight: 700;
    margin-bottom: 6px;
    letter-spacing: 0.5px;
}

header p {
    color: #f5dfe3;
    font-size: 14px;
    letter-spacing: 1px;
}

/* MAIN FORM CARD */
.container {
    max-width: 780px;
    margin: 45px auto;
    background: #ffffff;
    padding: 38px;
    border-radius: 16px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.35);
    border-top: 5px solid #b51230;
}

.container h2 {
    text-align: center;
    color: #a70f2c;
    font-size: 27px;
    margin-bottom: 10px;
    font-weight: 700;
}

.description {
    text-align: center;
    color: #777;
    font-size: 14px;
    margin-bottom: 30px;
    line-height: 1.6;
}

/* FORM GRID */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 22px;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 9px;
}

.full {
    grid-column: 1 / -1;
}

label {
    font-weight: 600;
    font-size: 14px;
    color: #292126;
}

/* INPUT FIELDS */
input, select, textarea {
    width: 100%;
    padding: 13px 14px;
    border: 1px solid #ded5d8;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    color: #292126;
    background: #fffafa;
    outline: none;
    transition: 0.25s ease;
}

input:focus,
select:focus,
textarea:focus {
    border-color: #b51230;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(181, 18, 48, 0.10);
}

input::placeholder,
textarea::placeholder {
    color: #aaa;
}

textarea {
    resize: vertical;
    min-height: 110px;
}

/* SUBMIT BUTTON */
button {
    width: 100%;
    padding: 15px;
    margin-top: 28px;
    background: linear-gradient(135deg, #8f1028, #cf203e);
    color: white;
    border: none;
    border-radius: 9px;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 0.3px;
    cursor: pointer;
    transition: 0.3s ease;
    box-shadow: 0 5px 14px rgba(181, 18, 48, 0.22);
}

button:hover {
    background: linear-gradient(135deg, #710b20, #a91430);
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(181, 18, 48, 0.3);
}

/* SUCCESS AND ERROR */
.success, .error {
    padding: 15px;
    border-radius: 9px;
    margin-bottom: 22px;
    line-height: 1.6;
    font-size: 14px;
}

.success {
    background: #eaf7ed;
    color: #216b32;
    border-left: 4px solid #299447;
}

.error {
    background: #fff0f1;
    color: #a7192d;
    border-left: 4px solid #c62828;
}

/* BACK TO HOME */
.back {
    display: block;
    text-align: center;
    margin-top: 25px;
    color: #a70f2c;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: 0.2s;
}

.back:hover {
    color: #65091c;
    text-decoration: underline;
}

/* MOBILE RESPONSIVE */
@media (max-width: 600px) {
    .form-grid {
        grid-template-columns: 1fr;
    }

    .full {
        grid-column: auto;
    }

    .container {
        margin: 22px 14px;
        padding: 23px 18px;
    }

    header h2 {
        font-size: 23px;
    }

    .container h2 {
        font-size: 23px;
    }
}
</style>
</head>

<body>

<header>
    <h2>🩸 Blood Donor Finder</h2>
    <p>Every Drop Matters</p>
</header>

<div class="container">

    <h2>Blood Request Form</h2>

    <p class="description">
        Fill in the details below to request blood.
        No registration or login required.
    </p>

    <?php if ($success): ?>
        <div class="success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">

        <input type="hidden" name="csrf_token"
        value="<?php echo htmlspecialchars($_SESSION["request_csrf"]); ?>">

        <div class="form-grid">

            <div class="field">
                <label>Patient Name *</label>
                <input type="text" name="patient_name"
                maxlength="100" required>
            </div>

            <div class="field">
                <label>Blood Group *</label>
                <select name="blood_group" required>
                    <option value="">Select Blood Group</option>
                    <option>A+</option>
                    <option>A-</option>
                    <option>B+</option>
                    <option>B-</option>
                    <option>AB+</option>
                    <option>AB-</option>
                    <option>O+</option>
                    <option>O-</option>
                </select>
            </div>

            <div class="field">
                <label>Units Required *</label>
                <input type="number" name="units"
                min="1" max="20" required>
            </div>

            <div class="field">
                <label>Hospital Name *</label>
                <input type="text" name="hospital_name"
                maxlength="150" required>
            </div>

            <div class="field">
                <label>City *</label>
                <input type="text" name="city"
                maxlength="100" required>
            </div>

            <div class="field">
                <label>Contact Person *</label>
                <input type="text" name="contact_name"
                maxlength="100" required>
            </div>

            <div class="field">
                <label>Phone Number *</label>
                <input type="tel" name="phone"
                maxlength="15" required>
            </div>

            <div class="field">
                <label>Email Address *</label>
                <input type="email" name="email"
                maxlength="150" required>
            </div>

            <div class="field full">
                <label>Additional Message</label>
                <textarea name="message" maxlength="2000"
                placeholder="Enter additional information..."></textarea>
            </div>

        </div>

        <button type="submit">🩸 Submit Blood Request</button>

    </form>

    <a href="index.html" class="back">← Back to Home</a>

</div>

</body>
</html>