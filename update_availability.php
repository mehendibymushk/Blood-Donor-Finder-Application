<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["donor_id"])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dashboard.php");
    exit();
}

$availability = $_POST["availability"] ?? "";

if (!in_array($availability, ["Available", "Not Available"], true)) {
    header("Location: dashboard.php?error=invalid");
    exit();
}

$donor_id = (int) $_SESSION["donor_id"];

$stmt = $conn->prepare(
    "UPDATE donors SET availability = ? WHERE id = ?"
);

$stmt->bind_param("si", $availability, $donor_id);

if ($stmt->execute() && $stmt->affected_rows >= 0) {
    header("Location: dashboard.php?updated=1");
    exit();
}

header("Location: dashboard.php?error=1");
exit();
?>