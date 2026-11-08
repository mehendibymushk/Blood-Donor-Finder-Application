<?php
session_start();
require_once "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare(
        "SELECT id, name, password FROM donors WHERE email = ?"
    );

    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {

            $donor = $result->fetch_assoc();

            if (password_verify($password, $donor["password"])) {

                session_regenerate_id(true);

                $_SESSION["donor_id"] = $donor["id"];
                $_SESSION["donor_name"] = $donor["name"];

                header("Location: dashboard.php");
                exit();

            } else {
                $message = "Incorrect password!";
            }

        } else {
            $message = "Account not found!";
        }

        $stmt->close();

    } else {
        $message = "Unable to process login. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Donor Login | Blood Donor Finder</title>

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
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    background: #100707;
    color: white;
}

/* LOGIN CARD */
.login-card {
    width: 100%;
    max-width: 430px;
    padding: 38px 35px;
    background: #1c1415;
    border: 1px solid #392023;
    border-radius: 15px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.35);
    text-align: center;
}

/* LOGO */
.logo {
    font-size: 42px;
    margin-bottom: 8px;
}

/* HEADING */
h1 {
    color: white;
    font-size: 27px;
    font-weight: 700;
    margin-bottom: 7px;
}

h1 span {
    color: #e0002b;
}

.subtitle {
    color: #aaa0a0;
    font-size: 13px;
    margin-bottom: 28px;
}

/* FORM */
.form-group {
    text-align: left;
    margin-bottom: 19px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #eee;
}

input {
    width: 100%;
    height: 49px;
    padding: 12px 14px;
    background: #100b0c;
    border: 1px solid #493438;
    border-radius: 8px;
    color: white;
    font-size: 14px;
    outline: none;
}

input::placeholder {
    color: #807575;
}

input:focus {
    border-color: #e0002b;
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
    cursor: pointer;
    padding: 5px;
    color: #bbb;
}

.eye-button svg {
    width: 22px;
    height: 22px;
}

/* LOGIN BUTTON */
.login-btn {
    width: 100%;
    height: 49px;
    margin-top: 5px;
    background: #d00028;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
}

.login-btn:hover {
    background: #a90020;
}

/* ERROR MESSAGE */
.message {
    background: #35151b;
    color: #ff9aaa;
    border: 1px solid #8e2335;
    padding: 11px;
    border-radius: 7px;
    margin-bottom: 18px;
    font-size: 13px;
}

/* LINKS */
.links {
    margin-top: 22px;
    line-height: 2.2;
    color: #aaa0a0;
    font-size: 13px;
}

.links a,
.back-home {
    color: #ff405c;
    text-decoration: none;
    font-weight: 500;
}

.links a:hover,
.back-home:hover {
    color: white;
}

/* BACK HOME */
.back-home {
    display: inline-block;
    margin-top: 15px;
    font-size: 13px;
}

/* FOOTER */
.footer {
    margin-top: 25px;
    padding-top: 17px;
    border-top: 1px solid #392023;
    color: #817575;
    font-size: 11px;
}

/* MOBILE */
@media(max-width: 480px) {
    .login-card {
        padding: 30px 23px;
    }

    h1 {
        font-size: 24px;
    }
}
</style>
</head>

<body>

<div class="login-card">

    <div class="logo">🩸</div>

    <h1>Donor <span>Login</span></h1>

    <p class="subtitle">
        Blood Donor Finder Application
    </p>

    <?php if ($message !== ""): ?>
        <div class="message">
            <?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">

        <div class="form-group">

            <label for="email">Email Address</label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your email"
                autocomplete="email"
                required
            >

        </div>

        <div class="form-group">

            <label for="password">Password</label>

            <div class="password-box">

                <input
                    type="password"
                    name="password"
                    id="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >

                <button
                    type="button"
                    class="eye-button"
                    id="eyeButton"
                    onclick="togglePassword()"
                    aria-label="Show password"
                >

                    <svg id="eyeIcon"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2"
                         stroke-linecap="round"
                         stroke-linejoin="round">

                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                        <circle cx="12" cy="12" r="3"/>

                    </svg>

                </button>

            </div>

        </div>

        <button type="submit" class="login-btn">
            Login
        </button>

    </form>

    <div class="links">

        <a href="forgot_password.php">Forgot Password?</a>

        <br>

        Don't have an account?
        <a href="register.php">Register</a>

    </div>

    <a href="index.html" class="back-home">
        ← Back to Home
    </a>

    <div class="footer">
        Blood Donor Finder Application
    </div>

</div>

<script>
function togglePassword() {

    const password = document.getElementById("password");
    const icon = document.getElementById("eyeIcon");
    const button = document.getElementById("eyeButton");

    if (password.type === "password") {

        password.type = "text";

        button.setAttribute("aria-label", "Hide password");

        icon.innerHTML = `
            <path d="M3 3l18 18"/>
            <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
            <path d="M9.9 5.2A10.5 10.5 0 0 1 12 5c6.5 0 10 7 10 7a16 16 0 0 1-3.1 3.9"/>
            <path d="M6.2 6.2C3.5 8 2 12 2 12s3.5 7 10 7c1.2 0 2.3-.2 3.3-.6"/>
        `;

    } else {

        password.type = "password";

        button.setAttribute("aria-label", "Show password");

        icon.innerHTML = `
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
            <circle cx="12" cy="12" r="3"/>
        `;
    }
}
</script>

</body>
</html>