<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$blood_group = trim($_GET["group"] ?? "");

$valid_groups = [
    "A+", "A-", "B+", "B-",
    "AB+", "AB-", "O+", "O-"
];

if (!in_array($blood_group, $valid_groups, true)) {
    exit("Invalid blood group.");
}

$stmt = $conn->prepare(
    "SELECT id, name, phone, email, city, address
     FROM donors
     WHERE blood_group = ?
     ORDER BY id ASC"
);

$stmt->bind_param("s", $blood_group);
$stmt->execute();
$donors = $stmt->get_result();
$total = $donors->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?php echo e($blood_group); ?> Donors</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f5f8;
    color: #222;
}

header {
    background: linear-gradient(90deg, #a90025, #c8102e);
    color: white;
    padding: 20px;
    text-align: center;
}

.container {
    max-width: 1100px;
    margin: 30px auto;
    padding: 20px;
}

.card {
    background: white;
    padding: 25px;
    border-radius: 13px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.10);
}

h1 {
    color: #a90025;
    margin-top: 0;
}

.count {
    color: #a90025;
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 700px;
    border-collapse: collapse;
}

th {
    background: #ffe0e6;
    color: #970020;
}

th, td {
    padding: 13px;
    border: 1px solid #ddd;
    text-align: left;
}

tbody tr:hover {
    background: #fff8fa;
}

.back {
    display: inline-block;
    margin-top: 20px;
    background: #a90025;
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
}

.empty {
    padding: 20px;
    text-align: center;
    color: #777;
}

@media(max-width: 600px) {
    .container {
        padding: 12px;
    }

    .card {
        padding: 15px;
    }
}
</style>
</head>

<body>

<header>
    <h2>🩸 Blood Donor Finder</h2>
    <p>Blood Group Donor Details</p>
</header>

<div class="container">

<div class="card">

    <h1>🩸 <?php echo e($blood_group); ?> Blood Group Donors</h1>

    <div class="count">
        Total Donors: <?php echo $total; ?>
    </div>

    <div class="table-wrap">

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>City</th>
                    <th>Address</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($total > 0): ?>

                <?php while ($donor = $donors->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo (int)$donor["id"]; ?></td>
                        <td><?php echo e($donor["name"]); ?></td>
                        <td><?php echo e($donor["phone"]); ?></td>
                        <td><?php echo e($donor["email"]); ?></td>
                        <td><?php echo e($donor["city"]); ?></td>
                        <td><?php echo e($donor["address"]); ?></td>
                    </tr>
                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="6" class="empty">
                        No donors registered with this blood group.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>
        </table>

    </div>

    <a href="admin_dashboard.php" class="back">
        ← Back to Admin Dashboard
    </a>

</div>
</div>

</body>
</html>