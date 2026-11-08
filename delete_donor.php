<?php
session_start();
require_once "db.php";

// Only logged-in admins can delete donors
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

// Deletion must use POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin_dashboard.php");
    exit;
}

// Verify request token
$token = $_POST["csrf_token"] ?? "";
$sessionToken = $_SESSION["admin_csrf"] ?? "";

if (
    !is_string($token) ||
    $sessionToken === "" ||
    !hash_equals($sessionToken, $token)
) {
    exit("Invalid request. Return to the dashboard and try again.");
}

$id = filter_input(INPUT_POST, "donor_id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit("Invalid donor ID.");
}

$stmt = $conn->prepare("DELETE FROM donors WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$deleted = $stmt->affected_rows;
$stmt->close();

header("Location: admin_dashboard.php?deleted=" . ($deleted > 0 ? "1" : "0"));
exit;
?>