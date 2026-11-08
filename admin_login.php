<?php
session_start();
require_once "db.php";

if (isset($_SESSION["admin_id"])) {
    header("Location: admin_dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Please enter username and password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT id, username, password FROM admins WHERE username = ? LIMIT 1"
        );

        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();
            $stmt->close();

            if ($admin && password_verify($password, $admin["password"])) {
                session_regenerate_id(true);
                $_SESSION["admin_id"] = $admin["id"];
                $_SESSION["admin_name"] = $admin["username"];

                header("Location: admin_dashboard.php");
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Unable to check admin details. Check the database.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Login | Blood Donor Finder</title>

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
    color: #fff;
}

/* LOGIN CARD */
.login-card {
    width: 100%;
    max-width: 410px;
    padding: 38px 35px;
    background: #1c1415;
    border: 1px solid #392023;
    border-radius: 15px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.35);
}

/* LOGO */
.logo {
    text-align: center;
    font-size: 42px;
    margin-bottom: 8px;
}

/* HEADING */
h1 {
    text-align: center;
    font-size: 27px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 7px;
}

h1 span {
    color: #e0002b;
}

.subtitle {
    text-align: center;
    color: #aaa0a0;
    font-size: 13px;
    margin-bottom: 28px;
}

/* FORM */
label {
    display: block;
    margin: 17px 0 8px;
    font-size: 13px;
    font-weight: 500;
    color: #eee;
}

input {
    width: 100%;
    padding: 13px 14px;
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
    right: 5px;
    top: 0;
    height: 100%;
    width: 40px;
    background: transparent;
    border: none;
    color: #bbb;
    cursor: pointer;
    font-size: 19px;
}

/* LOGIN BUTTON */
.login-button {
    width: 100%;
    margin-top: 25px;
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

.login-button:hover {
    background: #a90020;
}

/* ERROR */
.error {
    margin-bottom: 15px;
    padding: 11px;
    border-radius: 7px;
    background: #35151b;
    border: 1px solid #8e2335;
    color: #ff9aaa;
    font-size: 13px;
}

/* BACK LINK */
.back-link {
    display: block;
    margin-top: 22px;
    text-align: center;
    color: #ddd;
    font-size: 13px;
    text-decoration: none;
}

.back-link:hover {
    color: #ff334f;
}

/* FOOTER */
.footer {
    margin-top: 25px;
    padding-top: 17px;
    border-top: 1px solid #392023;
    text-align: center;
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

    <h1>Admin <span>Login</span></h1>

    <p class="subtitle">
        Blood Donor Finder Management Panel
    </p>

    <?php if ($error !== ""): ?>
        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="admin_login.php">

        <label for="username">Admin Username</label>

        <input
            type="text"
            id="username"
            name="username"
            placeholder="Enter admin username"
            autocomplete="username"
            required
        >

        <label for="password">Admin Password</label>

        <div class="password-box">

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter admin password"
                autocomplete="current-password"
                required
            >

            <button
                type="button"
                id="togglePassword"
                class="eye-button"
                aria-label="Show password"
            >&#128065;</button>

        </div>

        <button type="submit" class="login-button">
            Login
        </button>

    </form>

    <a href="index.html" class="back-link">
        ← Back to Home
    </a>

    <div class="footer">
        Blood Donor Finder Application
    </div>

</div>

<script>
const passwordInput = document.getElementById("password");
const toggleButton = document.getElementById("togglePassword");

toggleButton.addEventListener("click", function () {

    const show = passwordInput.type === "password";

    passwordInput.type = show ? "text" : "password";

    toggleButton.innerHTML = show ? "&#128584;" : "&#128065;";

    toggleButton.setAttribute(
        "aria-label",
        show ? "Hide password" : "Show password"
    );

});
</script>

</body>
</html>