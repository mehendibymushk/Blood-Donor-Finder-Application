<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPS Location | Blood Donor Finder</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #ff416c, #b25945, #5f72bd);
            color: #333;
        }

        header {
            background: white;
            padding: 20px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .logo {
            font-size: 22px;
            font-weight: bold;
        }

        nav {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
        }

        nav a {
            color: #713b70;
            text-decoration: none;
            font-weight: bold;
        }

        .container {
            width: 90%;
            max-width: 700px;
            margin: 60px auto;
            background: white;
            padding: 35px 25px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px #0002;
        }

        h1 {
            color: #d81b60;
            margin-bottom: 15px;
        }

        p {
            line-height: 1.7;
            margin-bottom: 20px;
        }

        button {
            background: #e91e63;
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 30px;
            font-size: 16px;
            cursor: pointer;
        }

        #result {
            margin-top: 25px;
            padding: 15px;
            background: #fff0f5;
            border-radius: 12px;
            line-height: 1.8;
            overflow-wrap: anywhere;
        }

        #result a {
            color: #d81b60;
            font-weight: bold;
        }
    </style>
</head>

<body>

<header>
    <div class="logo">🩸 Blood Donor Finder</div>

    <nav>
        <a href="index.html">Home</a>
        <a href="search.php">Find Donor</a>
        <a href="register.php">Register</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <h1>📍 Save Your GPS Location</h1>

    <p>
        Log in to your donor account before sharing your location.
        Your coordinates will be saved to your donor profile.
    </p>

    <button onclick="getLocation()">📍 Detect and Save My Location</button>

    <div id="result" aria-live="polite">
        Click the button to detect and save your location.
    </div>
</div>

<script>
async function getLocation() {
    const result = document.getElementById("result");

    if (!navigator.geolocation) {
        result.textContent = "Your browser does not support GPS.";
        return;
    }

    result.textContent = "📍 Detecting your location...";

    navigator.geolocation.getCurrentPosition(
        async function(position) {
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;

            result.textContent = "Location detected! Saving to database...";

            try {
                const response = await fetch("save_location.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        latitude: latitude,
                        longitude: longitude
                    })
                });

                const data = await response.json();

                if (!response.ok || data.success !== true) {
                    result.textContent =
                        "❌ " + (data.message || "Unable to save location.");
                    return;
                }

                result.innerHTML =
                    "✅ <strong>Location saved successfully!</strong><br><br>" +
                    "Latitude: " + latitude + "<br>" +
                    "Longitude: " + longitude + "<br><br>" +
                    '<a href="https://www.openstreetmap.org/?mlat=' +
                    latitude + '&mlon=' + longitude +
                    '#map=15/' + latitude + '/' + longitude +
                    '" target="_blank" rel="noopener">View My Location on Map</a>';

            } catch (error) {
                result.textContent =
                    "❌ Could not contact the server. Check save_location.php.";
            }
        },

        function(error) {
            if (error.code === 1) {
                result.textContent =
                    "Location permission denied. Allow location access in your browser.";
            } else if (error.code === 2) {
                result.textContent =
                    "Location unavailable. Check your device location settings.";
            } else {
                result.textContent =
                    "Location request timed out. Please try again.";
            }
        },

        {
            enableHighAccuracy: true,
            timeout: 20000,
            maximumAge: 0
        }
    );
}
</script>

</body>
</html>