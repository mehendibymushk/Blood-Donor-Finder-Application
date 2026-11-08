<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit("Invalid donor ID.");
}

$stmt = $conn->prepare(
    "SELECT id, name, email, phone, blood_group, city, address
     FROM donors WHERE id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$donor = $result->fetch_assoc();
$stmt->close();

if (!$donor) {
    http_response_code(404);
    exit("Donor not found.");
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Donor Details</title>
<style>
body {
    margin: 0;
    padding: 20px;
    font-family: Arial, sans-serif;
    background: #fff0f0;
}
.card {
    max-width: 600px;
    margin: 40px auto;
    padding: 25px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 5px 20px #0001;
}
h1 { color: #a00020; }
.detail {
    padding: 12px 0;
    border-bottom: 1px solid #eee;
    overflow-wrap: anywhere;
}
.label { font-weight: bold; }
a {
    display: inline-block;
    margin-top: 20px;
    padding: 11px 16px;
    background: #a00020;
    color: white;
    text-decoration: none;
    border-radius: 6px;
}
</style>
</head>
<body>
<div class="card">
    <h1>Donor Details</h1>

    <div class="detail"><span class="label">ID:</span>
        <?= (int)$donor["id"] ?></div>
    <div class="detail"><span class="label">Name:</span>
        <?= e($donor["name"]) ?></div>
    <div class="detail"><span class="label">Blood Group:</span>
        <?= e($donor["blood_group"]) ?></div>
    <div class="detail"><span class="label">City:</span>
        <?= e($donor["city"]) ?></div>
    <div class="detail"><span class="label">Phone:</span>
        <?= e($donor["phone"]) ?></div>
    <div class="detail"><span class="label">Email:</span>
        <?= e($donor["email"]) ?></div>
    <div class="detail"><span class="label">Address:</span>
        <?= e($donor["address"]) ?></div>

    <a href="admin_dashboard.php">Back to Dashboard</a>
</div>
</body>
</html>