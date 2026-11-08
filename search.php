<?php
include "db.php";

$blood_group = isset($_GET["blood_group"])
    ? trim($_GET["blood_group"]) : "";

$city = isset($_GET["city"])
    ? trim($_GET["city"]) : "";

$donors = [];
$search_done = isset($_GET["blood_group"]) || isset($_GET["city"]);

$groups = [
    "A+", "A-", "B+", "B-",
    "AB+", "AB-", "O+", "O-"
];

$error_message = "";

if ($search_done) {

    $sql = "SELECT id, name, email, phone, blood_group, city, address
            FROM donors
            WHERE availability = 'Available'";

    $params = [];
    $types = "";

    if ($blood_group !== "") {
        $sql .= " AND blood_group = ?";
        $params[] = $blood_group;
        $types .= "s";
    }

    if ($city !== "") {
        $sql .= " AND city LIKE ?";
        $params[] = "%" . $city . "%";
        $types .= "s";
    }

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if ($stmt->execute()) {
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $donors[] = $row;
            }
        } else {
            $error_message = "Unable to complete your search. Please try again.";
        }

        $stmt->close();

    } else {
        $error_message = "Something went wrong. Please try again.";
    }
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Find Blood Donor | Blood Donor Finder</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background: #100707;
    color: #ffffff;
    min-height: 100vh;
}

/* NAVBAR */

header {
    width: 100%;
    min-height: 75px;
    padding: 15px 7%;
    background: #1c1415;
    border-bottom: 1px solid #392023;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.logo {
    font-size: 21px;
    font-weight: 700;
    color: #ffffff;
}

.logo span {
    color: #e00032;
}

nav {
    display: flex;
    align-items: center;
    gap: 25px;
    flex-wrap: wrap;
}

nav a {
    text-decoration: none;
    color: #dddddd;
    font-size: 13px;
    transition: 0.3s;
}

nav a:hover {
    color: #ed1745;
}

/* MAIN SECTION */

.main-container {
    width: 90%;
    max-width: 1050px;
    margin: 65px auto;
}

.page-heading {
    text-align: center;
    margin-bottom: 30px;
}

.page-heading h1 {
    font-size: 30px;
    margin-bottom: 10px;
}

.page-heading h1 span {
    color: #e00032;
}

.page-heading p {
    color: #aaa;
    font-size: 14px;
}

/* SEARCH BOX */

.search-box {
    max-width: 750px;
    margin: auto;
    padding: 35px;
    background: #1c1415;
    border: 1px solid #392023;
    border-radius: 15px;
    box-shadow: 0 10px 35px rgba(0,0,0,0.25);
}

.search-box h2 {
    font-size: 19px;
    margin-bottom: 25px;
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    font-size: 13px;
    color: #eeeeee;
    margin-bottom: 9px;
}

input, select {
    width: 100%;
    padding: 14px;
    background: #100707;
    color: #ffffff;
    border: 1px solid #493034;
    border-radius: 7px;
    outline: none;
    font-family: inherit;
    font-size: 13px;
}

input:focus, select:focus {
    border-color: #e00032;
    box-shadow: 0 0 0 2px rgba(224,0,50,0.12);
}

select option {
    background: #1c1415;
    color: white;
}

input::placeholder {
    color: #888;
}

.search-btn {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 7px;
    background: #d00028;
    color: white;
    font-size: 14px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: 0.3s;
}

.search-btn:hover {
    background: #a90020;
    transform: translateY(-2px);
}

/* RESULTS */

.results-section {
    margin-top: 50px;
}

.results-heading {
    margin-bottom: 20px;
}

.results-heading h2 {
    font-size: 22px;
}

.results-heading p {
    color: #999;
    font-size: 13px;
    margin-top: 5px;
}

.donor-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
}

.donor-card {
    background: #1c1415;
    border: 1px solid #392023;
    border-radius: 12px;
    padding: 23px;
    transition: 0.3s;
}

.donor-card:hover {
    border-color: #d00028;
    transform: translateY(-4px);
}

.donor-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 18px;
}

.donor-card h3 {
    font-size: 17px;
    font-weight: 600;
}

.blood-badge {
    background: rgba(208,0,40,0.15);
    border: 1px solid #d00028;
    color: #ff4567;
    padding: 7px 11px;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 600;
}

.donor-info {
    color: #cccccc;
    font-size: 13px;
    line-height: 2.1;
    overflow-wrap: anywhere;
}

.donor-info strong {
    color: #ffffff;
}

.call-btn {
    display: block;
    text-align: center;
    margin-top: 20px;
    padding: 11px;
    background: #d00028;
    color: white;
    border-radius: 7px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    transition: 0.3s;
}

.call-btn:hover {
    background: #a90020;
}

/* MESSAGES */

.message {
    text-align: center;
    margin-top: 30px;
    padding: 18px;
    background: #1c1415;
    border: 1px solid #392023;
    border-radius: 9px;
    color: #cccccc;
    font-size: 14px;
}

.message.error {
    color: #ff7188;
    border-color: #7a2639;
}

.empty-icon {
    font-size: 30px;
    margin-bottom: 10px;
}

/* RESPONSIVE */

@media (max-width: 600px) {

    header {
        justify-content: center;
        text-align: center;
    }

    nav {
        justify-content: center;
        gap: 15px;
    }

    .main-container {
        margin: 40px auto;
    }

    .page-heading h1 {
        font-size: 24px;
    }

    .search-box {
        padding: 22px;
    }
}

</style>
</head>

<body>

<header>

    <div class="logo">
        🩸 Blood Donor <span>Finder</span>
    </div>

    <nav>
        <a href="index.html">Home</a>
        <a href="register.php">Become a Donor</a>
        <a href="login.php">Login</a>
    </nav>

</header>

<main class="main-container">

    <div class="page-heading">
        <h1>Find a <span>Blood Donor</span></h1>
        <p>Every drop counts. Find a donor and help save a life.</p>
    </div>

    <div class="search-box">

        <h2>Search Available Donors</h2>

        <form method="GET" action="search.php">

            <div class="form-group">

                <label>Blood Group</label>

                <select name="blood_group">

                    <option value="">Any Blood Group</option>

                    <?php foreach ($groups as $group): ?>

                        <option value="<?php echo e($group); ?>"
                            <?php echo ($blood_group === $group) ? "selected" : ""; ?>>
                            <?php echo e($group); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="form-group">

                <label>City</label>

                <input
                    type="text"
                    name="city"
                    placeholder="Enter your city"
                    value="<?php echo e($city); ?>"
                >

            </div>

            <button type="submit" class="search-btn">
                Search Donors
            </button>

        </form>

    </div>

    <?php if ($error_message !== ""): ?>

        <div class="message error">
            <?php echo e($error_message); ?>
        </div>

    <?php elseif ($search_done && count($donors) > 0): ?>

        <section class="results-section">

            <div class="results-heading">
                <h2>Available Donors</h2>
                <p><?php echo count($donors); ?> donor(s) found for your search.</p>
            </div>

            <div class="donor-list">

                <?php foreach ($donors as $donor): ?>

                    <div class="donor-card">

                        <div class="donor-top">

                            <h3><?php echo e($donor["name"]); ?></h3>

                            <span class="blood-badge">
                                <?php echo e($donor["blood_group"]); ?>
                            </span>

                        </div>

                        <div class="donor-info">

                            <p>
                                <strong>City:</strong>
                                <?php echo e($donor["city"]); ?>
                            </p>

                            <p>
                                <strong>Phone:</strong>
                                <?php echo e($donor["phone"]); ?>
                            </p>

                            <p>
                                <strong>Address:</strong>
                                <?php echo e($donor["address"]); ?>
                            </p>

                        </div>

                        <a
                            href="contact_donor.php?donor_id=<?php echo (int)$donor["id"]; ?>"
                            class="call-btn"
                        >
                            ✉ Email Donor
                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

        </section>

    <?php elseif ($search_done): ?>

        <div class="message error">
            <div class="empty-icon">🩸</div>
            No available donors found for your search.
            <br>
            Try another blood group or city.
        </div>

    <?php endif; ?>

</main>

</body>
</html>