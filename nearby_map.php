<?php
session_start();

if (!isset($_SESSION["donor_id"])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Nearby Donors | Blood Donor Finder</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: #100707;
    color: #ffffff;
    min-height: 100vh;
}

/* NAVBAR */
header {
    background: #1c1415;
    padding: 18px 6%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    border-bottom: 1px solid #392023;
}

.logo {
    color: #ffffff;
    font-size: 21px;
    font-weight: 700;
}

.logo span {
    color: #e00032;
}

nav {
    display: flex;
    gap: 22px;
    flex-wrap: wrap;
}

nav a {
    color: #eeeeee;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: .3s;
}

nav a:hover {
    color: #ed1745;
}

/* MAIN CONTAINER */
.container {
    width: 92%;
    max-width: 1100px;
    margin: 40px auto;
}

/* HEADING */
.heading {
    text-align: center;
    margin-bottom: 28px;
}

.heading h1 {
    color: #ffffff;
    font-size: 30px;
    font-weight: 700;
    margin-bottom: 10px;
}

.heading h1 span {
    color: #e00032;
}

.subtitle {
    color: #b9aaaa;
    line-height: 1.7;
    font-size: 14px;
}

/* LOCATION STATUS */
.status {
    background: #1c1415;
    border: 1px solid #392023;
    padding: 17px;
    border-radius: 10px;
    margin: 25px 0;
    text-align: center;
    color: #ff526e;
    font-size: 14px;
}

/* MAP */
#map {
    height: 430px;
    width: 100%;
    border-radius: 14px;
    border: 1px solid #493034;
    z-index: 1;
    overflow: hidden;
}

.blue-dot {
    width: 20px;
    height: 20px;
    background: #1685ff;
    border: 4px solid white;
    border-radius: 50%;
    box-shadow: 0 0 10px #1685ff;
}

/* PRIVACY */
.privacy {
    font-size: 12px;
    color: #999;
    text-align: center;
    margin-top: 14px;
    line-height: 1.7;
}

/* DONOR SECTION */
.section-title {
    color: #ff4567;
    margin-top: 35px;
    margin-bottom: 18px;
    font-size: 21px;
}

.donor-card {
    background: #1c1415;
    padding: 20px;
    margin: 15px 0;
    border-radius: 12px;
    border: 1px solid #392023;
    transition: .3s;
}

.donor-card:hover {
    border-color: #d00028;
    transform: translateY(-3px);
}

.donor-card h3 {
    margin: 0 0 12px;
    color: #ffffff;
    font-size: 17px;
}

.donor-card p {
    margin: 8px 0;
    color: #cccccc;
    font-size: 13px;
}

.blood {
    color: #ff4567;
    font-weight: 700;
}

.distance {
    color: #55d99b !important;
    font-weight: 600;
}

/* EMPTY MESSAGE */
.empty {
    background: #1c1415;
    padding: 22px;
    border: 1px solid #392023;
    border-radius: 10px;
    text-align: center;
    color: #cccccc;
    font-size: 14px;
}

/* MAP POPUP */
.leaflet-popup-content-wrapper,
.leaflet-popup-tip {
    background: #1c1415;
    color: #ffffff;
}

.leaflet-popup-content {
    line-height: 1.7;
}

/* MOBILE */
@media(max-width: 600px) {
    header {
        justify-content: center;
        text-align: center;
    }

    nav {
        justify-content: center;
        gap: 14px;
    }

    #map {
        height: 350px;
    }

    .heading h1 {
        font-size: 24px;
    }

    .container {
        margin: 25px auto;
    }
}
</style>
</head>

<body>

<header>
    <div class="logo">🩸 Blood Donor <span>Finder</span></div>

    <nav>
        <a href="index.html">Home</a>
        <a href="search.php">Find Donor</a>
        <a href="gps.php">Save GPS</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">

    <div class="heading">
        <h1>📍 Nearby <span>Blood Donors</span></h1>

        <p class="subtitle">
            Find available blood donors near your current location.
        </p>
    </div>

    <div class="status" id="status">
        Allow location permission to find nearby donors.
    </div>

    <div id="map"></div>

    <p class="privacy">
        Your location updates while this page is open.
        Donor map markers are approximate to protect privacy.
    </p>

    <h2 class="section-title">Available Donors Within 50 KM</h2>

    <div id="donorList">
        <div class="empty">Waiting for your location...</div>
    </div>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>

let map = null;
let myMarker = null;
let donorMarkers = [];
let firstLocation = true;
let watchId = null;
let lastRequest = 0;

const statusBox = document.getElementById("status");
const donorList = document.getElementById("donorList");

// Blue marker for your location
const blueIcon = L.divIcon({
    className: "",
    html: '<div class="blue-dot"></div>',
    iconSize: [20, 20],
    iconAnchor: [10, 10]
});

function escapeHTML(value) {
    return String(value ?? "").replace(/[&<>"']/g, function(char) {
        return {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        }[char];
    });
}

function initializeMap(latitude, longitude) {

    if (!map) {

        map = L.map("map").setView(
            [latitude, longitude], 13
        );

        L.tileLayer(
            "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
            {
                attribution: "&copy; OpenStreetMap contributors"
            }
        ).addTo(map);

        myMarker = L.marker(
            [latitude, longitude],
            {icon: blueIcon}
        ).addTo(map);

        myMarker.bindPopup("Your current location");

    } else {

        myMarker.setLatLng([latitude, longitude]);

        if (firstLocation) {
            map.setView([latitude, longitude], 13);
        }
    }

    firstLocation = false;
}

async function loadNearbyDonors(latitude, longitude) {

    try {

        const response = await fetch(
            "nearby_donors.php?lat=" +
            encodeURIComponent(latitude) +
            "&lng=" +
            encodeURIComponent(longitude),
            {cache: "no-store"}
        );

        const donors = await response.json();

        if (!response.ok) {
            throw new Error(donors.error || "Unable to load donors.");
        }

        // Remove old donor markers
        donorMarkers.forEach(marker => map.removeLayer(marker));
        donorMarkers = [];

        donorList.innerHTML = "";

        if (donors.length === 0) {
            donorList.innerHTML =
                '<div class="empty">No available donors found within 50 KM.</div>';
            return;
        }

        donors.forEach(function(donor) {

            // Approximate donor position
            const marker = L.circleMarker(
                [donor.latitude, donor.longitude],
                {
                    radius: 9,
                    color: "#ffffff",
                    weight: 2,
                    fillColor: "#d71920",
                    fillOpacity: 1
                }
            ).addTo(map);

            marker.bindPopup(
                "<b>" + escapeHTML(donor.name) + "</b><br>" +
                "Blood Group: " + escapeHTML(donor.blood_group) + "<br>" +
                "Distance: " + donor.distance + " KM"
            );

            donorMarkers.push(marker);

            const card = document.createElement("div");
            card.className = "donor-card";

            card.innerHTML =
                "<h3>" + escapeHTML(donor.name) + "</h3>" +
                "<p>Blood Group: <span class='blood'>" +
                escapeHTML(donor.blood_group) + "</span></p>" +
                "<p>City: " + escapeHTML(donor.city) + "</p>" +
                "<p class='distance'>📍 " +
                donor.distance + " KM away</p>";

            donorList.appendChild(card);
        });

    } catch (error) {

        donorList.innerHTML =
            '<div class="empty">' +
            escapeHTML(error.message) +
            '</div>';
    }
}

function locationSuccess(position) {

    const latitude = position.coords.latitude;
    const longitude = position.coords.longitude;

    statusBox.textContent = "✅ Your live location is updating.";

    initializeMap(latitude, longitude);

    // Avoid sending repeated requests too frequently
    const now = Date.now();

    if (now - lastRequest >= 10000) {
        lastRequest = now;
        loadNearbyDonors(latitude, longitude);
    }
}

function locationError(error) {

    if (error.code === 1) {
        statusBox.textContent =
            "Location permission denied. Please allow location access.";
    } else if (error.code === 2) {
        statusBox.textContent =
            "Location unavailable. Check your device location settings.";
    } else {
        statusBox.textContent =
            "Location request timed out. Please try again.";
    }
}

if ("geolocation" in navigator) {

    watchId = navigator.geolocation.watchPosition(
        locationSuccess,
        locationError,
        {
            enableHighAccuracy: true,
            maximumAge: 5000,
            timeout: 20000
        }
    );

} else {

    statusBox.textContent =
        "Your browser does not support GPS.";
}

</script>

</body>
</html>