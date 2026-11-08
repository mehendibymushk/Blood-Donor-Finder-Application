<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

if (empty($_SESSION["admin_csrf"])) {
    $_SESSION["admin_csrf"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$search = trim($_GET["search"] ?? "");

// DASHBOARD STATISTICS
$totalResult = $conn->query("SELECT COUNT(*) AS total FROM donors");
$totalDonors = (int)$totalResult->fetch_assoc()["total"];

$availableResult = $conn->query(
    "SELECT COUNT(*) AS total FROM donors WHERE availability = 'Available'"
);
$availableDonors = (int)$availableResult->fetch_assoc()["total"];

$requestResult = $conn->query("SELECT COUNT(*) AS total FROM blood_requests");
$totalRequests = (int)$requestResult->fetch_assoc()["total"];

// BLOOD GROUP STATISTICS
$groupResult = $conn->query(
    "SELECT blood_group, COUNT(*) AS total
     FROM donors
     GROUP BY blood_group
     ORDER BY blood_group ASC"
);

$groups = [];

while ($group = $groupResult->fetch_assoc()) {
    $groups[] = $group;
}

$availableGroups = count($groups);

// DONOR SEARCH
if ($search !== "") {
    $stmt = $conn->prepare(
        "SELECT id, name, email, phone, blood_group, city
         FROM donors
         WHERE name LIKE ?
         OR email LIKE ?
         OR phone LIKE ?
         OR blood_group LIKE ?
         OR city LIKE ?
         ORDER BY id ASC"
    );

    $term = "%" . $search . "%";
    $stmt->bind_param("sssss", $term, $term, $term, $term, $term);
    $stmt->execute();
    $donors = $stmt->get_result();

} else {
    $donors = $conn->query(
        "SELECT id, name, email, phone, blood_group, city
         FROM donors
         ORDER BY id ASC"
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Blood Donor Finder | Admin Dashboard</title>

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
    background: #f5f5f7;
    color: #171717;
}

.layout {
    display: flex;
    min-height: 100vh;
}

/* SIDEBAR */
.sidebar {
    width: 215px;
    height: 100vh;
    position: sticky;
    top: 0;
    background: #171717;
    color: white;
    padding: 18px 13px;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    overflow-y: auto;
}

.brand {
    text-align: center;
    padding: 3px 5px 17px;
    border-bottom: 1px solid #444;
}

.brand-logo {
    width: 58px;
    height: 58px;
    background: #c62828;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 31px;
    margin: 0 auto 9px;
    box-shadow: 0 4px 15px rgba(198,40,40,.4);
}

.brand h2 {
    font-size: 19px;
    font-weight: 900;
    line-height: 1.25;
}

.brand p {
    color: #ff7777;
    font-size: 11px;
    margin-top: 6px;
}

.nav-heading {
    color: #888;
    font-size: 10px;
    font-weight: bold;
    letter-spacing: 1.4px;
    margin: 19px 9px 9px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 10px;
    color: #eee;
    text-decoration: none;
    border-radius: 8px;
    margin-bottom: 5px;
    font-size: 13px;
    transition: .2s;
}

.nav-link:hover,
.nav-link.active {
    background: #c62828;
    color: white;
}

.nav-link .icon {
    font-size: 18px;
    width: 23px;
    text-align: center;
}

.sidebar-bottom {
    margin-top: auto;
    border-top: 1px solid #444;
    padding-top: 14px;
}

.logout {
    display: block;
    text-align: center;
    background: #c62828;
    color: white;
    text-decoration: none;
    padding: 10px;
    border-radius: 7px;
    font-size: 13px;
    font-weight: bold;
}

.logout:hover {
    background: #a71919;
}

.sidebar-message {
    text-align: center;
    margin-top: 17px;
    color: #ffaaaa;
    font-size: 12px;
    line-height: 1.5;
}

/* MAIN CONTENT */
.main {
    flex: 1;
    min-width: 0;
    padding: 19px 22px;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 17px;
}

.topbar h1 {
    font-size: 27px;
    font-weight: 900;
}

.topbar h1 span {
    color: #c62828;
}

.topbar p {
    color: #666;
    margin-top: 5px;
    font-size: 12px;
}

.admin-tag {
    background: white;
    padding: 9px 15px;
    border: 1px solid #eee;
    border-radius: 8px;
    font-weight: bold;
    font-size: 12px;
    white-space: nowrap;
}

.admin-tag span {
    color: #c62828;
}

/* WELCOME BANNER */
.welcome {
    background: linear-gradient(110deg, #8e0000, #d71920, #a90000);
    color: white;
    border-radius: 12px;
    padding: 18px 23px;
    margin-bottom: 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    box-shadow: 0 4px 12px rgba(180,0,0,.17);
    overflow: hidden;
}

.welcome h2 {
    font-size: 21px;
    margin-bottom: 6px;
}

.welcome p {
    color: #ffe0e0;
    font-size: 12px;
}

.welcome-art {
    font-size: 47px;
}

/* SECTION TITLE */
.section-title {
    font-size: 17px;
    font-weight: 900;
    margin-bottom: 12px;
}

.section-title span {
    color: #c62828;
}

/* STATISTICS */
.stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 18px;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 14px 16px;
    border: 1px solid #eee;
    box-shadow: 0 3px 10px rgba(0,0,0,.045);
    border-bottom: 3px solid #c62828;
    transition: .2s;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-card.red {
    background: #c62828;
    color: white;
}

.stat-card.black {
    background: #171717;
    color: white;
}

.stat-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 9px;
}

.stat-icon {
    width: 35px;
    height: 35px;
    border-radius: 8px;
    background: #ffe5e5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
}

.stat-card.red .stat-icon,
.stat-card.black .stat-icon {
    background: rgba(255,255,255,.18);
}

.stat-number {
    font-size: 27px;
    font-weight: 900;
    margin-bottom: 3px;
}

.stat-name {
    font-size: 12px;
    font-weight: bold;
    opacity: .85;
}

/* PANELS */
.panel {
    background: white;
    border-radius: 11px;
    padding: 16px 18px;
    border: 1px solid #eee;
    box-shadow: 0 3px 10px rgba(0,0,0,.04);
    margin-bottom: 17px;
}

.panel .section-title {
    font-size: 17px;
    margin-bottom: 13px;
}

/* BLOOD GROUP CARDS */
.groups {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
}

.group-card {
    display: block;
    text-decoration: none;
    text-align: center;
    padding: 11px 8px;
    border: 1px solid #f1baba;
    background: #fff0f0;
    border-radius: 9px;
    transition: .2s;
}

.group-card strong {
    display: block;
    font-size: 21px;
    font-weight: 900;
    color: #c62828;
    margin-bottom: 3px;
}

.group-card span {
    color: #333;
    font-size: 12px;
}

.group-card:hover {
    background: #c62828;
    transform: translateY(-2px);
}

.group-card:hover strong,
.group-card:hover span {
    color: white;
}

/* DONOR SEARCH */
.search-form {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

.search-form input {
    flex: 1;
    min-width: 0;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 7px;
    outline: none;
    font-size: 12px;
}

.search-form input:focus {
    border-color: #c62828;
}

.search-btn {
    padding: 0 18px;
    background: #c62828;
    color: white;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    font-size: 12px;
    font-weight: bold;
}

.search-btn:hover {
    background: #a71919;
}

.clear-btn {
    display: flex;
    align-items: center;
    background: #171717;
    color: white;
    text-decoration: none;
    padding: 0 13px;
    border-radius: 7px;
    font-size: 12px;
}

/* DONOR TABLE */
.table-wrapper {
    overflow: auto;
    width: 100%;
    max-height: 370px;
    border-radius: 6px;
}

table {
    width: 100%;
    min-width: 760px;
    border-collapse: collapse;
}

thead {
    background: #171717;
    color: white;
    position: sticky;
    top: 0;
    z-index: 1;
}

th {
    text-align: left;
    padding: 10px 9px;
    font-size: 11px;
    text-transform: uppercase;
}

td {
    padding: 9px;
    border-bottom: 1px solid #eee;
    font-size: 11px;
}

tbody tr:hover {
    background: #fff7f7;
}

.blood-badge {
    display: inline-block;
    background: #ffe5e5;
    color: #b71c1c;
    padding: 5px 8px;
    border-radius: 5px;
    font-size: 11px;
    font-weight: bold;
}

.view-btn {
    display: inline-block;
    padding: 6px 9px;
    background: #171717;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-size: 11px;
    margin-right: 3px;
}

.delete-btn {
    padding: 6px 9px;
    background: #c62828;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 11px;
}

.view-btn:hover {
    background: #444;
}

.delete-btn:hover {
    background: #a71919;
}

.empty {
    text-align: center;
    padding: 20px;
    color: #777;
}

/* FOOTER */
.footer {
    text-align: center;
    color: #888;
    font-size: 11px;
    padding: 8px;
}

/* MEDIUM SCREENS */
@media(max-width: 1050px) {
    .sidebar {
        width: 190px;
    }

    .main {
        padding: 15px;
    }

    .stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* MOBILE */
@media(max-width: 750px) {
    .layout {
        display: block;
    }

    .sidebar {
        width: 100%;
        height: auto;
        min-height: auto;
        position: relative;
        padding: 14px;
    }

    .brand {
        display: flex;
        align-items: center;
        gap: 12px;
        text-align: left;
        padding-bottom: 12px;
    }

    .brand-logo {
        width: 45px;
        height: 45px;
        font-size: 24px;
        margin: 0;
        flex-shrink: 0;
    }

    .brand h2 {
        font-size: 16px;
    }

    .nav-heading {
        margin: 12px 5px 7px;
    }

    .nav-link {
        display: inline-flex;
        padding: 9px;
        margin: 3px;
    }

    .sidebar-bottom {
        margin-top: 8px;
    }

    .logout {
        max-width: 130px;
    }

    .sidebar-message {
        display: none;
    }

    .main {
        padding: 14px 11px;
    }

    .groups {
        grid-template-columns: repeat(2, 1fr);
    }

    .topbar h1 {
        font-size: 23px;
    }
}

@media(max-width: 430px) {
    .stats {
        gap: 8px;
    }

    .stat-card {
        padding: 11px;
    }

    .stat-number {
        font-size: 23px;
    }

    .stat-name {
        font-size: 10px;
    }

    .panel {
        padding: 13px;
    }

    .welcome {
        padding: 16px;
    }

    .welcome h2 {
        font-size: 18px;
    }

    .welcome-art {
        font-size: 35px;
    }

    .search-form {
        flex-wrap: wrap;
    }

    .search-form input {
        flex-basis: 100%;
        padding: 10px;
    }

    .search-btn,
    .clear-btn {
        padding: 9px 13px;
    }
}
</style>
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="brand">
            <div class="brand-logo">🩸</div>
            <div>
                <h2>Blood Donor<br>Finder</h2>
                <p>Every Drop Matters</p>
            </div>
        </div>

        <div class="nav-heading">MAIN MENU</div>

        <a href="admin_dashboard.php" class="nav-link active">
            <span class="icon">⌂</span> Dashboard
        </a>

        <a href="#groups" class="nav-link">
            <span class="icon">🩸</span> Blood Groups
        </a>

        <a href="#donors" class="nav-link">
            <span class="icon">♙</span> Donor Management
        </a>

        <div class="sidebar-bottom">

            <a href="admin_logout.php" class="logout"
               onclick="return confirm('Are you sure you want to logout?');">
                ⇥ &nbsp; Logout
            </a>

            <div class="sidebar-message">
                🩸<br>
                <strong>Donate Blood<br>Save Lives ❤️</strong>
            </div>

        </div>

    </aside>

    <!-- MAIN DASHBOARD -->
    <main class="main">

        <div class="topbar">
            <div>
                <h1>Admin <span>Dashboard</span></h1>
                <p>Manage Blood Donors & Requests Efficiently</p>
            </div>

            <div class="admin-tag">
                ● <span>ADMIN</span>
            </div>
        </div>

        <!-- WELCOME -->
        <div class="welcome">
            <div>
                <h2>Welcome Back, Admin!</h2>
                <p>Together we can save lives. Every drop matters.</p>
            </div>

            <div class="welcome-art">❤️</div>
        </div>

        <!-- STATISTICS -->
        <h2 class="section-title">DASHBOARD OVERVIEW</h2>

        <div class="stats">

            <div class="stat-card red">
                <div class="stat-top">
                    <div class="stat-icon">👥</div>
                    <span>🩸</span>
                </div>
                <div class="stat-number"><?php echo $totalDonors; ?></div>
                <div class="stat-name">Total Donors</div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon">✓</div>
                    <span>♥</span>
                </div>
                <div class="stat-number"><?php echo $availableDonors; ?></div>
                <div class="stat-name">Available Donors</div>
            </div>

            <div class="stat-card red">
                <div class="stat-top">
                    <div class="stat-icon">📋</div>
                    <span>+</span>
                </div>
                <div class="stat-number"><?php echo $totalRequests; ?></div>
                <div class="stat-name">Blood Requests</div>
            </div>

            <div class="stat-card black">
                <div class="stat-top">
                    <div class="stat-icon">💧</div>
                    <span>+</span>
                </div>
                <div class="stat-number"><?php echo $availableGroups; ?></div>
                <div class="stat-name">Blood Groups</div>
            </div>

        </div>

        <!-- BLOOD GROUP STATISTICS -->
        <section class="panel" id="groups">

            <h2 class="section-title">
                🩸 <span>Blood Group Statistics</span>
            </h2>

            <?php if (count($groups) > 0): ?>

                <div class="groups">

                    <?php foreach ($groups as $group): ?>

                        <a class="group-card"
                           href="blood_group_donors.php?group=<?php echo urlencode($group["blood_group"]); ?>">

                            <strong><?php echo e($group["blood_group"]); ?></strong>

                            <span>
                                <?php echo (int)$group["total"]; ?> Donors
                            </span>

                        </a>

                    <?php endforeach; ?>

                </div>

                <p style="margin-top:12px;color:#777;font-size:12px;">
                    Click any blood group to view its registered donors.
                </p>

            <?php else: ?>

                <p class="empty">No blood group information available.</p>

            <?php endif; ?>

        </section>

        <!-- DONOR MANAGEMENT -->
        <section class="panel" id="donors">

            <h2 class="section-title">
                👥 <span>Recent Donors / Donor Management</span>
            </h2>

            <form method="GET" class="search-form">

                <input
                    type="text"
                    name="search"
                    placeholder="Search donors by name, email, phone, blood group or city..."
                    value="<?php echo e($search); ?>"
                >

                <button type="submit" class="search-btn">
                    Search
                </button>

                <?php if ($search !== ""): ?>
                    <a href="admin_dashboard.php" class="clear-btn">Clear</a>
                <?php endif; ?>

            </form>

            <div class="table-wrapper">

                <table>

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Blood Group</th>
                            <th>City</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($donors && $donors->num_rows > 0): ?>

                        <?php while ($donor = $donors->fetch_assoc()): ?>

                            <tr>

                                <td><?php echo (int)$donor["id"]; ?></td>

                                <td>
                                    <strong><?php echo e($donor["name"]); ?></strong>
                                </td>

                                <td>
                                    <span class="blood-badge">
                                        <?php echo e($donor["blood_group"]); ?>
                                    </span>
                                </td>

                                <td><?php echo e($donor["city"]); ?></td>

                                <td><?php echo e($donor["phone"]); ?></td>

                                <td><?php echo e($donor["email"]); ?></td>

                                <td>

                                    <a class="view-btn"
                                       href="view_donor.php?id=<?php echo (int)$donor["id"]; ?>">
                                        View
                                    </a>

                                    <form method="POST"
                                          action="delete_donor.php"
                                          style="display:inline;"
                                          onsubmit="return confirm('Are you sure you want to delete this donor?');">

                                        <input type="hidden"
                                               name="donor_id"
                                               value="<?php echo (int)$donor["id"]; ?>">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?php echo e($_SESSION["admin_csrf"]); ?>">

                                        <button type="submit" class="delete-btn">
                                            Delete
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="empty">
                                No donors found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

        <div class="footer">
            © <?php echo date("Y"); ?> Blood Donor Finder. All Rights Reserved.
            <br><br>
            Designed with ❤️ to Save Lives
        </div>

    </main>

</div>

</body>
</html>