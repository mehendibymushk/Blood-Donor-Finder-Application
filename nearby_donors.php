<?php
session_start();
require_once "db.php";

header("Content-Type: application/json; charset=utf-8");

// Donor login required for the existing donor-side map
if (!isset($_SESSION["donor_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Please log in first."]);
    exit;
}

// Get current location
$lat = $_GET["lat"] ?? null;
$lng = $_GET["lng"] ?? null;

if (
    !is_numeric($lat) ||
    !is_numeric($lng) ||
    $lat < -90 || $lat > 90 ||
    $lng < -180 || $lng > 180
) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid location."]);
    exit;
}

$myLat = (float)$lat;
$myLng = (float)$lng;
$myId = (int)$_SESSION["donor_id"];

// Get only available donors who enabled location sharing
$sql = "SELECT id, name, blood_group, city,
               latitude, longitude, availability
        FROM donors
        WHERE id != ?
        AND share_location = 1
        AND availability = 'Available'
        AND latitude IS NOT NULL
        AND longitude IS NOT NULL";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Database query failed."]);
    exit;
}

$stmt->bind_param("i", $myId);
$stmt->execute();

$result = $stmt->get_result();
$donors = [];

$earthRadius = 6371;

while ($row = $result->fetch_assoc()) {

    $donorLat = (float)$row["latitude"];
    $donorLng = (float)$row["longitude"];

    // Validate saved GPS coordinates
    if (
        !is_finite($donorLat) ||
        !is_finite($donorLng) ||
        $donorLat < -90 || $donorLat > 90 ||
        $donorLng < -180 || $donorLng > 180
    ) {
        continue;
    }

    // Haversine distance calculation
    $dLat = deg2rad($donorLat - $myLat);
    $dLng = deg2rad($donorLng - $myLng);

    $a = sin($dLat / 2) ** 2 +
         cos(deg2rad($myLat)) *
         cos(deg2rad($donorLat)) *
         sin($dLng / 2) ** 2;

    // Keep the value within the valid range
    $a = max(0, min(1, $a));

    $distance = 2 * $earthRadius *
                atan2(sqrt($a), sqrt(1 - $a));

    // Show only donors within 50 KM
    if ($distance > 50) {
        continue;
    }

    $donors[] = [
        "name" => $row["name"],
        "blood_group" => $row["blood_group"],
        "city" => $row["city"],
        "availability" => $row["availability"],

        // Keep accurate distance for sorting
        "distance" => $distance,

        // Approximate coordinates for privacy
        "latitude" => round($donorLat, 2),
        "longitude" => round($donorLng, 2)
    ];
}

$stmt->close();
$conn->close();

// Sort nearest donors first
usort($donors, function($a, $b) {
    return $a["distance"] <=> $b["distance"];
});

// Display distance up to two decimal places
foreach ($donors as &$donor) {
    $donor["distance"] = round($donor["distance"], 2);
}
unset($donor);

echo json_encode(
    $donors,
    JSON_INVALID_UTF8_SUBSTITUTE
);
?>