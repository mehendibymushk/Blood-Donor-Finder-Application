<?php
session_start();

$isLoggedIn = isset($_SESSION["donor_id"]);
$donorName = $_SESSION["donor_name"] ?? "Donor";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Donor Map | Blood Donor Finder</title>

<link
 rel="stylesheet"
 href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
>

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, sans-serif;
    background: #fff5f5;
    color: #333;
}

header {
    background: #c62828;
    color: white;
    padding: 18px 5%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

header h2 {
    font-size: 22px;
}

nav {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

nav a {
    color: white;
    text-decoration: none;
    font-weight: bold;
}

.hero {
    text-align: center;
    padding: 25px 15px;
}

.hero h1 {
    color: #b71c1c;
    margin-bottom: 10px;
}

.hero p {
    color: #666;
    line-height: 1.6;
}

.actions {
    text-align: center;
    padding: 0 15px 20px;
}

button {
    background: #d32f2f;
    color: white;
    border: none;
    border-radius: 25px;
    padding: 13px 22px;
    margin: 5px;
    cursor: pointer;
    font-weight: bold;
}

button:hover {
    background: #a91515;
}

#status {
    padding: 12px;
    text-align: center;
    color: #8b1b1b;
}

.map-container {
    padding: 0 15px;
}

#map {
    width: 100%;
    height: 65vh;
    min-height: 400px;
    border-radius: 18px;
    border: 2px solid #f0caca;
    z-index: 1;
}

.donor-pin {
    background: #d71938;
    color: white;
    width: 42px;
    height: 42px;
    border-radius: 50% 50% 50% 0;
    transform: rotate(-45deg);
    border: 3px solid white;
    box-shadow: 0 3px 10px #777;
    display: flex;
    align-items: center;
    justify-content: center;
}

.donor-pin span {
    transform: rotate(45deg);
    font-size: 21px;
}

.info {
    background: white;
    padding: 20px;
    margin: 20px 15px;
    border-radius: 15px;
    line-height: 1.7;
}

.info h3 {
    color: #c62828;
    margin-bottom: 8px;
}

footer {
    text-align: center;
    padding: 20px;
    color: #777;
}

@media(max-width:600px) {
    header {
        justify-content: center;
        text-align: center;
    }

    .hero h1 {
        font-size: 25px;
    }

    #map {
        height: 60vh;
        min-height: 350px;
    }
}
</style>
</head>

<body>

<header>
    <h2>🩸 Blood Donor Finder</h2>

    <nav>
        <a href="index.html">Home</a>
        <a href="search.php">Find Donor</a>
        <a href="gps.php">My Location</a>

        <?php if ($isLoggedIn): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </nav>
</header>

<section class="hero">
    <h1>🗺️ Find Blood Donors on the Map</h1>
    <p>
        Explore donor locations around the map.
        Only donors who have shared their GPS location will appear.
    </p>
</section>

<div class="actions">
    <button onclick="findMyLocation()">
        📍 Show My Location
    </button>

    <button onclick="loadDonors()">
        🔄 Refresh Donors
    </button>
</div>

<div id="status">Loading the donor map...</div>

<div class="map-container">
    <div id="map"></div>
</div>

<div class="info">
    <h3>❤️ About this map</h3>
    <p>
        Red markers represent registered donors who have saved their GPS
        coordinates. Select a marker to see the donor's name, blood group,
        city and approximate distance from your current location.
    </p>
    <p>
        Donors must voluntarily share their location before they appear.
        The map does not reveal anyone's exact home address.
    </p>
</div>

<footer>Made with ❤️ | Blood Donor Finder</footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
const statusBox = document.getElementById("status");

// Initial map position: Dabaspet, Karnataka.
// The map will move to your GPS position when you share it.
const map = L.map("map").setView([13.22, 77.23], 11);

L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    attribution: "&copy; OpenStreetMap contributors"
}).addTo(map);

const donorLayer = L.layerGroup().addTo(map);

let myMarker = null;
let myCircle = null;
let myPosition = null;

const donorIcon = L.divIcon({
    className: "",
    html: '<div class="donor-pin"><span>🩸</span></div>',
    iconSize: [42, 42],
    iconAnchor: [21, 42],
    popupAnchor: [0, -40]
});

function escapeHTML(value) {
    const div = document.createElement("div");
    div.textContent = value ?? "";
    return div.innerHTML;
}

function distanceKm(lat1, lon1, lat2, lon2) {
    const radians = degrees => degrees * Math.PI / 180;

    const dLat = radians(lat2 - lat1);
    const dLon = radians(lon2 - lon1);

    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(radians(lat1)) *
        Math.cos(radians(lat2)) *
        Math.sin(dLon / 2) ** 2;

    return 6371 * 2 * Math.atan2(
        Math.sqrt(a),
        Math.sqrt(1 - a)
    );
}

async function loadDonors() {
    statusBox.textContent = "Loading donors who shared their locations...";

    donorLayer.clearLayers();

    try {
        const response = await fetch("nearby_donors.php", {
            cache: "no-store"
        });

        if (!response.ok) {
            throw new Error("Unable to load donors.");
        }

        const donors = await response.json();

        if (!Array.isArray(donors)) {
            throw new Error("Invalid donor data received.");
        }

        let shown = 0;

        donors.forEach(donor => {
            const lat = Number(donor.latitude);
            const lon = Number(donor.longitude);

            if (
                !Number.isFinite(lat) ||
                !Number.isFinite(lon) ||
                lat < -90 || lat > 90 ||
                lon < -180 || lon > 180
            ) {
                return;
            }

            const name = escapeHTML(donor.name);
            const group = escapeHTML(donor.blood_group);
            const city = escapeHTML(donor.city);

            let distanceText =
                "Share your location to calculate distance.";

            if (myPosition) {
                const distance = distanceKm(
                    myPosition.lat,
                    myPosition.lon,
                    lat,
                    lon
                );

                distanceText =
                    "Approx. distance: " + distance.toFixed(1) + " km";
            }

            const popup =
                "<strong>🩸 " + name + "</strong><br>" +
                "Blood group: " + group + "<br>" +
                "City: " + city + "<br>" +
                distanceText + "<br>" +
                "<small>Approximate location</small>";

            L.marker([lat, lon], {
                icon: donorIcon
            })
            .bindPopup(popup)
            .addTo(donorLayer);

            shown++;
        });

        if (shown === 0) {
            statusBox.textContent =
                "No donor locations saved yet. Donors must log in and " +
                "voluntarily save their GPS coordinates before appearing.";
        } else {
            statusBox.textContent =
                "❤️ Showing " + shown +
                " donor location(s). Click a blood-drop marker for details.";
        }

    } catch (error) {
        statusBox.textContent =
            "Could not load donors. Check nearby_donors.php and your database.";
    }
}

function findMyLocation() {
    if (!navigator.geolocation) {
        statusBox.textContent = "Your browser does not support GPS.";
        return;
    }

    statusBox.textContent = "📍 Detecting your current location...";

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;

            myPosition = {lat, lon};

            if (myMarker) {
                map.removeLayer(myMarker);
            }

            if (myCircle) {
                map.removeLayer(myCircle);
            }

            myMarker = L.marker([lat, lon])
                .addTo(map)
                .bindPopup("<strong>📍 Your current location</strong>")
                .openPopup();

            myCircle = L.circle([lat, lon], {
                radius: 150,
                color: "#1976d2",
                fillColor: "#64b5f6",
                fillOpacity: 0.2
            }).addTo(map);

            map.setView([lat, lon], 13);

            statusBox.textContent =
                "Your location is shown. Loading donor markers...";

            loadDonors();
        },

        function(error) {
            statusBox.textContent =
                "Could not detect your location. Allow browser GPS permission and try again.";
        },

        {
            enableHighAccuracy: true,
            timeout: 20000,
            maximumAge: 0
        }
    );
}

loadDonors();
</script>

</body>
</html>