<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/vendor/autoload.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $blood_group = trim($_POST["blood_group"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $password = $_POST["password"] ?? "";

    if (
        $name === "" ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $phone === "" ||
        $city === "" ||
        $password === "" ||
        !in_array($blood_group, [
            "A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"
        ], true)
    ) {
        $message = "Please fill all required fields correctly.";

    } else {

        $check = $conn->prepare(
            "SELECT id FROM donors WHERE email = ?"
        );

        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $message = "Email already registered.";

        } else {

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO donors
                (name, email, phone, blood_group, city, address, password)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "sssssss",
                $name,
                $email,
                $phone,
                $blood_group,
                $city,
                $address,
                $hashed_password
            );

            if ($stmt->execute()) {

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

                    $mail->setFrom(
                        $config["email"],
                        "Blood Donor Finder"
                    );

                    $mail->addAddress($email, $name);

                    $mail->isHTML(false);
                    $mail->Subject =
                        "Blood Donor Registration Successful";

                    $mail->Body =
                        "Hello " . $name . ",\n\n" .
                        "Your registration with Blood Donor Finder " .
                        "was successful.\n\n" .
                        "Blood Group: " . $blood_group . "\n" .
                        "City: " . $city . "\n\n" .
                        "Thank you for joining our donor community.\n" .
                        "You can now log in to your account.\n\n" .
                        "Blood Donor Finder";

                    $mail->send();

                    $message =
                        "Registration successful! " .
                        "A confirmation email has been sent. You can now login.";

                } catch (\Throwable $e) {

                    error_log(
                        "Registration email error: " . $e->getMessage()
                    );

                    $message =
                        "Registration successful, but the confirmation " .
                        "email could not be sent. You can now login.";
                }

            } else {
                $message = "Registration failed. Please try again.";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Donor Registration | Blood Donor Finder</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    min-height: 100vh;
    background: #100707;
    color: white;
}

/* NAVBAR */

header {
    width: 100%;
    padding: 16px 6%;
    background: #1c1415;
    border-bottom: 1px solid #392023;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 21px;
    font-weight: 700;
    color: white;
}

.logo span {
    color: #e0002b;
}

nav {
    display: flex;
    align-items: center;
    gap: 25px;
}

nav a {
    text-decoration: none;
    color: #ddd;
    font-size: 13px;
    font-weight: 500;
    transition: 0.3s;
}

nav a:hover {
    color: #ff405c;
}

/* MAIN CONTAINER */

.page-container {
    min-height: calc(100vh - 65px);
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px 18px;
}

/* REGISTRATION CARD */

.form-container {
    width: 100%;
    max-width: 520px;
    padding: 35px;
    background: #1c1415;
    border: 1px solid #392023;
    border-radius: 15px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.35);
}

/* HEADING */

.card-logo {
    text-align: center;
    font-size: 38px;
    margin-bottom: 8px;
}

h2 {
    text-align: center;
    font-size: 26px;
    color: white;
    margin-bottom: 7px;
}

h2 span {
    color: #e0002b;
}

.subtitle {
    text-align: center;
    color: #aaa0a0;
    font-size: 13px;
    margin-bottom: 25px;
}

/* FORM */

.form-group {
    margin-bottom: 16px;
}

label {
    display: block;
    margin-bottom: 7px;
    color: #eee;
    font-size: 13px;
    font-weight: 500;
}

input,
select,
textarea {
    width: 100%;
    padding: 12px 14px;
    background: #100b0c;
    border: 1px solid #493438;
    border-radius: 8px;
    color: white;
    font-size: 14px;
    outline: none;
}

input,
select {
    height: 46px;
}

input::placeholder,
textarea::placeholder {
    color: #807575;
}

input:focus,
select:focus,
textarea:focus {
    border-color: #e0002b;
}

select {
    cursor: pointer;
}

select option {
    background: #1c1415;
    color: white;
}

textarea {
    min-height: 85px;
    resize: vertical;
}

/* PASSWORD */

.password-box {
    position: relative;
}

.password-box input {
    padding-right: 48px;
}

.eye-button {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #bbb;
    cursor: pointer;
    font-size: 18px;
}

/* REGISTER BUTTON */

button[type="submit"] {
    width: 100%;
    margin-top: 10px;
    padding: 13px;
    border: none;
    border-radius: 8px;
    background: #d00028;
    color: white;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
}

button[type="submit"]:hover {
    background: #a90020;
}

/* MESSAGE */

.message {
    padding: 12px;
    margin-bottom: 18px;
    border-radius: 7px;
    background: #35151b;
    border: 1px solid #8e2335;
    color: #ff9aaa;
    font-size: 13px;
    line-height: 1.6;
}

/* FOOTER LINKS */

.login-link {
    text-align: center;
    margin-top: 20px;
    color: #aaa0a0;
    font-size: 13px;
}

.login-link a {
    color: #ff405c;
    text-decoration: none;
    font-weight: 600;
}

.login-link a:hover {
    color: white;
}

/* FOOTER */

.footer {
    margin-top: 23px;
    padding-top: 16px;
    border-top: 1px solid #392023;
    text-align: center;
    color: #817575;
    font-size: 11px;
}

/* MOBILE RESPONSIVE */

@media(max-width: 600px) {

    header {
        flex-direction: column;
        gap: 15px;
    }

    nav {
        gap: 18px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .form-container {
        padding: 27px 20px;
    }

    h2 {
        font-size: 23px;
    }
}

</style>
</head>

<body>

<!-- NAVBAR -->

<header>

    <div class="logo">
        🩸 Blood Donor <span>Finder</span>
    </div>

    <nav>
        <a href="index.html">Home</a>
        <a href="search.php">Find Donor</a>
        <a href="login.php">Login</a>
    </nav>

</header>

<!-- REGISTRATION PAGE -->

<div class="page-container">

    <div class="form-container">

        <div class="card-logo">🩸</div>

        <h2>Donor <span>Registration</span></h2>

        <p class="subtitle">
            Join our community and help save lives
        </p>

        <?php if ($message !== ""): ?>
            <p class="message">
                <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="register.php">

            <div class="form-group">
                <label>Full Name</label>
                <input
                    type="text"
                    name="name"
                    maxlength="100"
                    placeholder="Enter your full name"
                    required
                >
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input
                    type="email"
                    name="email"
                    maxlength="150"
                    placeholder="Enter your email"
                    required
                >
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input
                    type="text"
                    name="phone"
                    maxlength="15"
                    placeholder="Enter your phone number"
                    required
                >
            </div>

            <div class="form-group">
                <label>Blood Group</label>

                <select name="blood_group" required>
                    <option value="">Select Blood Group</option>
                    <option value="A+">A+</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B-">B-</option>
                    <option value="AB+">AB+</option>
                    <option value="AB-">AB-</option>
                    <option value="O+">O+</option>
                    <option value="O-">O-</option>
                </select>
            </div>

            <div class="form-group">
                <label>City</label>
                <input
                    type="text"
                    name="city"
                    maxlength="100"
                    placeholder="Enter your city"
                    required
                >
            </div>

            <div class="form-group">
                <label>Address</label>
                <textarea
                    name="address"
                    maxlength="500"
                    placeholder="Enter your address"
                ></textarea>
            </div>

            <div class="form-group">
                <label>Password</label>

                <div class="password-box">
                    <input
                        type="password"
                        name="password"
                        id="password"
                        minlength="8"
                        placeholder="Create a password"
                        required
                    >

                    <button
                        type="button"
                        class="eye-button"
                        id="eyeButton"
                        aria-label="Show password"
                    >&#128065;</button>
                </div>
            </div>

            <button type="submit">
                Register
            </button>

        </form>

        <div class="login-link">
            Already have an account?
            <a href="login.php">Login</a>
        </div>

        <div class="footer">
            Blood Donor Finder Application
        </div>

    </div>

</div>

<script>

const passwordInput = document.getElementById("password");
const eyeButton = document.getElementById("eyeButton");

eyeButton.addEventListener("click", function () {

    const showPassword = passwordInput.type === "password";

    passwordInput.type = showPassword ? "text" : "password";

    eyeButton.innerHTML = showPassword
        ? "&#128584;"
        : "&#128065;";

    eyeButton.setAttribute(
        "aria-label",
        showPassword ? "Hide password" : "Show password"
    );

});

</script>

</body>
</html>