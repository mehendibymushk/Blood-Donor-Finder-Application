<?php

include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);

    $stmt = $conn->prepare(
        "SELECT id FROM donors WHERE email = ?"
    );

    $stmt->bind_param("s", $email);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $message =
        "If this email is registered, password reset instructions can be sent.";

    } else {

        $message =
        "If this email is registered, password reset instructions can be sent.";

    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Forgot Password</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<header>

    <div class="logo">
        🩸 Blood Donor Finder
    </div>

</header>


<div class="form-container">

    <h2>Forgot Password</h2>

    <?php if ($message != ""): ?>

        <p class="message">
            <?php echo htmlspecialchars($message); ?>
        </p>

    <?php endif; ?>


    <form method="POST">

        <label>Enter your registered email</label>

        <input
            type="email"
            name="email"
            required
        >

        <button type="submit">
            Submit
        </button>

    </form>


    <p>
        <a href="login.php">
            Back to Login
        </a>
    </p>

</div>

</body>

</html>