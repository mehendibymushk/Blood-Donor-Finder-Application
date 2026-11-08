<?php
session_start();

require_once "db.php";

header("Content-Type: application/json; charset=UTF-8");

// Check login
if (!isset($_SESSION["donor_id"])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Please log in again before saving your location."
    ]);
    exit;
}

// Receive GPS coordinates
$data = json_decode(file_get_contents("php://input"), true);

$latitude = $data["latitude"] ?? null;
$longitude = $data["longitude"] ?? null;

// Validate coordinates
if (
    !is_numeric($latitude) ||
    !is_numeric($longitude) ||
    $latitude < -90 || $latitude > 90 ||
    $longitude < -180 || $longitude > 180
) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid GPS coordinates. Please try again."
    ]);
    exit;
}

$donorId = (int) $_SESSION["donor_id"];

$latitude = (float) $latitude;
$longitude = (float) $longitude;

// Update the logged-in donor's location
$stmt = $conn->prepare(
    "UPDATE donors SET latitude = ?, longitude = ? WHERE id = ?"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("ddi", $latitude, $longitude, $donorId);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Could not save location: " . $stmt->error
    ]);
    $stmt->close();
    exit;
}

$stmt->close();

// Verify that the donor record exists and the coordinates were saved
$check = $conn->prepare(
    "SELECT latitude, longitude FROM donors WHERE id = ?"
);

if (!$check) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Could not verify the saved location."
    ]);
    exit;
}

$check->bind_param("i", $donorId);
$check->execute();
$check->bind_result($savedLatitude, $savedLongitude);

if ($check->fetch()) {
    if ($savedLatitude !== null && $savedLongitude !== null) {
        echo json_encode([
            "success" => true,
            "message" => "Your location has been saved successfully!"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Your donor record was found, but the coordinates are still empty."
        ]);
    }
} else {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "message" => "Your donor account was not found. Please log in again."
    ]);
}

$check->close();
?>