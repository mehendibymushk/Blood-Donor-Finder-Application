
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["donor_id"])) {
    header("Location: login.php");
    exit();
}

$donor_id = (int)$_SESSION["donor_id"];

if (empty($_SESSION["dashboard_csrf"])) {
    $_SESSION["dashboard_csrf"] = bin2hex(random_bytes(32));
}

$error = "";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// UPDATE PROFILE
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {

    if (!hash_equals($_SESSION["dashboard_csrf"], $_POST["csrf_token"] ?? "")) {
        $error = "Invalid request. Please try again.";
    } else {
        $name = trim($_POST["name"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $blood_group = trim($_POST["blood_group"] ?? "");
        $city = trim($_POST["city"] ?? "");
        $address = trim($_POST["address"] ?? "");

        $valid_groups = ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"];

        if ($name === "" || $phone === "" || $blood_group === "" || $city === "") {
            $error = "Please fill all required fields.";
        } elseif (!in_array($blood_group, $valid_groups, true)) {
            $error = "Please select a valid blood group.";
        } else {
            $stmt = $conn->prepare(
                "UPDATE donors SET name=?, phone=?, blood_group=?, city=?, address=? WHERE id=?"
            );

            $stmt->bind_param(
                "sssssi",
                $name,
                $phone,
                $blood_group,
                $city,
                $address,
                $donor_id
            );

            if ($stmt->execute()) {
                $_SESSION["donor_name"] = $name;
                header("Location: dashboard.php?profile_updated=1");
                exit();
            } else {
                $error = "Unable to update profile.";
            }

            $stmt->close();
        }
    }
}

// UPDATE LOCATION SHARING
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_sharing"])) {

    if (!hash_equals($_SESSION["dashboard_csrf"], $_POST["csrf_token"] ?? "")) {
        $error = "Invalid request. Please try again.";
    } else {
        $share_location = $_POST["share_location"] ?? "";

        if ($share_location !== "0" && $share_location !== "1") {
            $error = "Please select a valid location sharing option.";
        } else {
            $share_location = (int)$share_location;

            $stmt = $conn->prepare(
                "UPDATE donors SET share_location=? WHERE id=?"
            );

            $stmt->bind_param("ii", $share_location, $donor_id);

            if ($stmt->execute()) {
                header("Location: dashboard.php?sharing_updated=1");
                exit();
            } else {
                $error = "Unable to update location sharing.";
            }

            $stmt->close();
        }
    }
}

// GET DONOR DETAILS
$stmt = $conn->prepare(
    "SELECT name, email, phone, blood_group, city, address,
            availability, share_location
     FROM donors WHERE id=?"
);

$stmt->bind_param("i", $donor_id);
$stmt->execute();

$result = $stmt->get_result();
$donor = $result->fetch_assoc();
$stmt->close();

if (!$donor) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$current_availability = $donor["availability"] ?? "Available";
$sharing_enabled = (int)($donor["share_location"] ?? 0);
$initial = strtoupper(substr($donor["name"], 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Donor Dashboard | Blood Donor Finder</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    font-family: Arial, sans-serif;
    background: #f3f4f6;
    color: #222;
    font-size: 13px;
}

/* SIDEBAR */

.sidebar {
    width: 185px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background: #171717;
    color: white;
    padding: 18px 12px;
}

.logo {
    text-align: center;
    padding-bottom: 17px;
    border-bottom: 1px solid #393939;
}

.logo-icon {
    width: 37px;
    height: 37px;
    background: #d71920;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin: 0 auto 9px;
}

.logo h2 {
    font-size: 15px;
}

.logo p {
    color: #aaa;
    font-size: 10px;
    margin-top: 5px;
}

.nav-menu {
    margin-top: 19px;
}

.nav-menu a {
    display: flex;
    align-items: center;
    gap: 9px;
    color: #ddd;
    text-decoration: none;
    padding: 9px 10px;
    border-radius: 7px;
    margin-bottom: 5px;
    font-size: 11px;
    transition: 0.2s;
}

.nav-menu a:hover,
.nav-menu a.active {
    background: #d71920;
    color: white;
}

.nav-menu .logout {
    margin-top: 20px;
    border: 1px solid #555;
}

/* MAIN */

.main {
    margin-left: 185px;
    padding: 17px 22px;
    max-width: 1450px;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
}

.topbar h1 {
    font-size: 21px;
    color: #171717;
}

.topbar p {
    font-size: 10px;
    color: #888;
    margin-top: 4px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 8px;
    background: white;
    padding: 6px 10px;
    border-radius: 8px;
}

.user-avatar {
    width: 29px;
    height: 29px;
    background: #d71920;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.user-info strong {
    font-size: 11px;
}

/* WELCOME */

.welcome-banner {
    background: linear-gradient(110deg, #b5121b, #e12b32);
    color: white;
    padding: 16px 19px;
    border-radius: 9px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.welcome-banner h2 {
    font-size: 17px;
    margin-bottom: 5px;
}

.welcome-banner p {
    font-size: 10px;
}

.welcome-icon {
    font-size: 32px;
}

/* OVERVIEW */

.overview-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 11px;
    margin-bottom: 17px;
}

.overview-card {
    background: white;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 9px;
}

.overview-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: #fff0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.overview-card p {
    font-size: 10px;
    color: #888;
    margin-bottom: 4px;
}

.overview-card h3 {
    font-size: 14px;
    overflow-wrap: anywhere;
}

/* HEADINGS */

.section-heading {
    margin: 17px 0 9px;
}

.section-heading h2 {
    font-size: 16px;
}

.section-heading p {
    font-size: 10px;
    color: #888;
    margin-top: 4px;
}

/* SECTION CARDS */

.section-card {
    background: white;
    padding: 15px;
    border-radius: 9px;
    border: 1px solid #eee;
    margin-bottom: 13px;
}

.section-card h3 {
    font-size: 13px;
    margin-bottom: 11px;
}

/* PROFILE */

.profile-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px 12px;
}

.form-group label {
    display: block;
    font-size: 10px;
    font-weight: bold;
    margin-bottom: 5px;
    color: #555;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 8px 9px;
    border: 1px solid #ddd;
    border-radius: 6px;
    outline: none;
    font-size: 11px;
    background: white;
    color: #222;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #d71920;
}

.form-group textarea {
    min-height: 48px;
    resize: vertical;
}

.full-width {
    grid-column: 1 / -1;
}

/* BUTTONS */

.save-button {
    background: #d71920;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 8px 13px;
    font-size: 11px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 10px;
}

.save-button:hover {
    background: #aa1018;
}

/* AVAILABILITY */

.status-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

.status-row p {
    font-size: 10px;
    color: #777;
    margin-bottom: 7px;
}

.status-row select {
    padding: 7px 9px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 11px;
}

.status-pill {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: bold;
}

.available {
    color: #16834b;
    background: #e7f7ee;
}

.not-available {
    color: #bd2229;
    background: #ffe9e9;
}

/* LOCATION SHARING */

.sharing-options {
    display: flex;
    gap: 16px;
    margin: 10px 0;
}

.sharing-options label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    cursor: pointer;
}

.sharing-note {
    background: #fff5f5;
    border-left: 3px solid #d71920;
    padding: 8px;
    font-size: 10px;
    color: #666;
    border-radius: 5px;
    line-height: 1.5;
}

/* QUICK ACTIONS */

.quick-links {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 11px;
    margin-bottom: 15px;
}

.quick-card {
    background: white;
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 13px;
    text-decoration: none;
    color: #222;
    transition: 0.2s;
}

.quick-card:hover {
    border-color: #d71920;
    transform: translateY(-2px);
}

.quick-icon {
    font-size: 21px;
    margin-bottom: 6px;
}

.quick-card h3 {
    font-size: 12px;
    margin-bottom: 5px;
}

.quick-card p {
    color: #888;
    font-size: 10px;
}

/* ALERTS */

.alert {
    padding: 9px 12px;
    border-radius: 6px;
    margin-bottom: 12px;
    font-size: 11px;
}

.success {
    background: #e8f7ee;
    color: #167744;
    border: 1px solid #c8ead5;
}

.danger {
    background: #fff0f0;
    color: #b5121b;
    border: 1px solid #f3cccc;
}

/* FOOTER */

footer {
    text-align: center;
    color: #999;
    font-size: 10px;
    padding: 10px 0;
}

/* SMALL LAPTOP */

@media (min-width: 651px) and (max-width: 1100px) {
    .sidebar {
        width: 165px;
    }

    .main {
        margin-left: 165px;
        padding: 15px;
    }

    .overview-grid {
        gap: 7px;
    }

    .overview-card {
        padding: 9px;
    }
}

/* MOBILE */

@media (max-width: 650px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        padding: 15px;
    }

    .nav-menu {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 5px;
        margin-top: 15px;
    }

    .nav-menu a {
        margin: 0;
    }

    .main {
        margin-left: 0;
        padding: 13px;
    }

    .topbar {
        align-items: flex-start;
        flex-direction: column;
        gap: 10px;
    }

    .overview-grid,
    .profile-grid,
    .quick-links {
        grid-template-columns: 1fr;
    }

    .welcome-banner {
        padding: 15px;
    }
}
</style>
</head>

<body>

<!-- SIDEBAR -->

<aside class="sidebar">

    <div class="logo">
        <div class="logo-icon">🩸</div>
        <h2>Blood Donor Finder</h2>
        <p>Every Drop Counts</p>
    </div>

    <nav class="nav-menu">
        <a href="#dashboard" class="active">⌂ Dashboard</a>
        <a href="#profile">👤 My Profile</a>
        <a href="#availability">♥ Donor Availability</a>
        <a href="#location">📍 Location Sharing</a>
        <a href="search.php">🔎 Find Donor</a>
        <a href="nearby_map.php">🌍 Nearby Donors</a>
        <a href="logout.php" class="logout">↪ Logout</a>
    </nav>

</aside>

<!-- MAIN CONTENT -->

<main class="main" id="dashboard">

    <div class="topbar">
        <div>
            <h1>Donor Dashboard</h1>
            <p>Manage your donor profile and preferences.</p>
        </div>

        <div class="user-info">
            <div class="user-avatar"><?php echo e($initial); ?></div>
            <strong><?php echo e($donor["name"]); ?></strong>
        </div>
    </div>

    <!-- WELCOME BANNER -->

    <section class="welcome-banner">
        <div>
            <h2>Welcome back, <?php echo e($donor["name"]); ?>!</h2>
            <p>Your willingness to donate blood can help save someone's life.</p>
        </div>
        <div class="welcome-icon">❤️</div>
    </section>

    <!-- OVERVIEW -->

    <section class="overview-grid">

        <div class="overview-card">
            <div class="overview-icon">🩸</div>
            <div>
                <p>Your Blood Group</p>
                <h3><?php echo e($donor["blood_group"]); ?></h3>
            </div>
        </div>

        <div class="overview-card">
            <div class="overview-icon">📍</div>
            <div>
                <p>Your City</p>
                <h3><?php echo e($donor["city"]); ?></h3>
            </div>
        </div>

        <div class="overview-card">
            <div class="overview-icon">♥</div>
            <div>
                <p>Your Availability</p>
                <h3><?php echo e($current_availability); ?></h3>
            </div>
        </div>

    </section>

    <!-- NOTIFICATIONS -->

    <?php if (isset($_GET["profile_updated"])): ?>
        <div class="alert success">Your profile has been updated successfully!</div>
    <?php endif; ?>

    <?php if (isset($_GET["sharing_updated"])): ?>
        <div class="alert success">Your location sharing preference has been updated!</div>
    <?php endif; ?>

    <?php if (isset($_GET["updated"])): ?>
        <div class="alert success">Your availability has been updated successfully!</div>
    <?php endif; ?>

    <?php if (isset($_GET["error"])): ?>
        <div class="alert danger">Unable to update availability. Please try again.</div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="alert danger"><?php echo e($error); ?></div>
    <?php endif; ?>

    <!-- MY PROFILE -->

    <div class="section-heading" id="profile">
        <h2>My Profile</h2>
        <p>View and update your personal information.</p>
    </div>

    <section class="section-card">

        <h3>Personal Information</h3>

        <form action="dashboard.php" method="POST">

            <input type="hidden" name="csrf_token"
                   value="<?php echo e($_SESSION["dashboard_csrf"]); ?>">

            <div class="profile-grid">

                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name"
                           value="<?php echo e($donor["name"]); ?>" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email"
                           value="<?php echo e($donor["email"]); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="text" name="phone"
                           value="<?php echo e($donor["phone"]); ?>" required>
                </div>

                <div class="form-group">
                    <label>Blood Group *</label>
                    <select name="blood_group" required>
                        <?php
                        $groups = ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"];
                        foreach ($groups as $group):
                        ?>
                            <option value="<?php echo e($group); ?>"
                                <?php echo ($donor["blood_group"] === $group) ? "selected" : ""; ?>>
                                <?php echo e($group); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>City *</label>
                    <input type="text" name="city"
                           value="<?php echo e($donor["city"]); ?>" required>
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address"
                           value="<?php echo e($donor["address"]); ?>">
                </div>

            </div>

            <button type="submit" name="update_profile"
                    value="1" class="save-button">
                Save Profile Changes
            </button>

        </form>

    </section>

    <!-- DONOR AVAILABILITY -->

    <div class="section-heading" id="availability">
        <h2>Donor Availability</h2>
        <p>Choose whether you are currently available to donate blood.</p>
    </div>

    <section class="section-card">

        <h3>Availability Status</h3>

        <div class="status-row">

            <div>
                <p>Current Status</p>

                <span class="status-pill <?php echo ($current_availability === "Available") ? "available" : "not-available"; ?>">
                    <?php echo e($current_availability); ?>
                </span>
            </div>

            <form action="update_availability.php" method="POST">

                <select name="availability" required>
                    <option value="Available"
                        <?php echo ($current_availability === "Available") ? "selected" : ""; ?>>
                        Available
                    </option>

                    <option value="Not Available"
                        <?php echo ($current_availability === "Not Available") ? "selected" : ""; ?>>
                        Not Available
                    </option>
                </select>

                <button type="submit" class="save-button">
                    Save Availability
                </button>

            </form>

        </div>

    </section>

    <!-- LOCATION SHARING -->

    <div class="section-heading" id="location">
        <h2>Location Sharing</h2>
        <p>Manage your location sharing preference.</p>
    </div>

    <section class="section-card">

        <h3>Location Sharing Settings</h3>

        <div class="status-row">
            <div>
                <p>Current Location Sharing Status</p>

                <span class="status-pill <?php echo $sharing_enabled ? "available" : "not-available"; ?>">
                    <?php echo $sharing_enabled ? "ON" : "OFF"; ?>
                </span>
            </div>
        </div>

        <form action="dashboard.php" method="POST">

            <input type="hidden" name="csrf_token"
                   value="<?php echo e($_SESSION["dashboard_csrf"]); ?>">

            <div class="sharing-options">

                <label>
                    <input type="radio" name="share_location"
                           value="1" <?php echo $sharing_enabled ? "checked" : ""; ?>>
                    ON
                </label>

                <label>
                    <input type="radio" name="share_location"
                           value="0" <?php echo !$sharing_enabled ? "checked" : ""; ?>>
                    OFF
                </label>

            </div>

            <div class="sharing-note">
                Your location sharing preference controls whether your
                location can be shared through the application's location
                feature. Only turn it ON if you want to share your location.
            </div>

            <button type="submit" name="update_sharing"
                    value="1" class="save-button">
                Save Location Sharing
            </button>

        </form>

    </section>

    <!-- QUICK ACTIONS -->

    <div class="section-heading">
        <h2>Quick Actions</h2>
        <p>Explore other features of Blood Donor Finder.</p>
    </div>

    <section class="quick-links">

        <a href="search.php" class="quick-card">
            <div class="quick-icon">🩸</div>
            <h3>Find a Blood Donor</h3>
            <p>Search for donors by blood group and city.</p>
        </a>

        <a href="nearby_map.php" class="quick-card">
            <div class="quick-icon">📍</div>
            <h3>Nearby Donors</h3>
            <p>Explore nearby donors on the map.</p>
        </a>

    </section>

    <footer>
        © Blood Donor Finder Application | Every Drop Counts
    </footer>

</main>

</body>
</html>