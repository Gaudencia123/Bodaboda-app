<?php 
include('db.php');
session_start();

// 1. USALAMA: Hakikisha aliyelogin ni Rider
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'rider') {
    header("Location: login.php");
    exit();
}

$rider_id = $_SESSION['user_id'];

// 2. LOGIC YA DATABASE (Hapa ndipo update_status na update_location zinapokaa)

// A. Kukubali au Kukamilisha Safari (Action handler)
if (isset($_GET['action']) && isset($_GET['trip_id'])) {
    $tid = mysqli_real_escape_string($conn, $_GET['trip_id']);
    $act = $_GET['action'];

    if ($act == 'accept') {
        mysqli_query($conn, "UPDATE trips SET status = 'active', rider_id = '$rider_id' WHERE id = '$tid' AND status = 'pending'");
    } elseif ($act == 'complete') {
        mysqli_query($conn, "UPDATE trips SET status = 'completed' WHERE id = '$tid' AND rider_id = '$rider_id'");
    }
    header("Location: rider_dashboard.php"); // Refresh ukurasa
    exit();
}

// B. Kusasisha Mahali (Location update handler - inaitwa na JS fetch)
if (isset($_GET['update_lat']) && isset($_GET['update_lng'])) {
    $lat = $_GET['update_lat'];
    $lng = $_GET['update_lng'];
    mysqli_query($conn, "UPDATE trips SET current_lat = '$lat', current_lng = '$lng' WHERE rider_id = '$rider_id' AND status = 'active'");
    exit(); // Inazuia kurefresh ukurasa mzima kwa JS fetch
}

// 3. SET LUGHA
$lang = $_SESSION['lang'] ?? 'sw';
if (isset($_GET['lang'])) { $lang = $_GET['lang']; $_SESSION['lang'] = $lang; }

$texts = [
    'en' => ['dash'=>'Rider Panel', 'rides'=>'Total Rides', 'cash'=>'Earnings', 'active'=>'Active Trip', 'new'=>'New Requests', 'btn_acc'=>'ACCEPT', 'btn_comp'=>'COMPLETE'],
    'sw' => ['dash'=>'Dashibodi ya Dereva', 'rides'=>'Safari Zote', 'cash'=>'Mapato', 'active'=>'Safari ya Sasa', 'new'=>'Maombi Mapya', 'btn_acc'=>'KUBALI', 'btn_comp'=>'KAMILISHA']
];
$t = $texts[$lang];

// 4. DATA ZA MUONEKANO (Queries)
$stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total, SUM(amount) as cash FROM trips WHERE rider_id = '$rider_id' AND status = 'completed'"));
$new_trips = mysqli_query($conn, "SELECT trips.*, users.name as cname FROM trips JOIN users ON trips.customer_id = users.id WHERE trips.status = 'pending'");
$active_trip = mysqli_fetch_assoc(mysqli_query($conn, "SELECT trips.*, users.name as cname FROM trips JOIN users ON trips.customer_id = users.id WHERE trips.rider_id = '$rider_id' AND trips.status = 'active' LIMIT 1"));
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['dash']; ?></title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root { --primary: #2c3e50; --accent: #ff9800; --success: #27ae60; }
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; margin: 0; padding: 15px; }
        
        .nav { background: var(--primary); color: white; padding: 15px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .nav a { color: white; text-decoration: none; font-size: 13px; margin-left: 10px; }

        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px; }
        .card { background: white; padding: 15px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-align: center; }
        .card h2 { margin: 5px 0; color: var(--primary); }

        #map { height: 250px; width: 100%; border-radius: 10px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }

        .section { background: white; padding: 15px; border-radius: 10px; margin-bottom: 15px; border-left: 5px solid var(--primary); }
        .section h3 { margin-top: 0; font-size: 16px; color: #555; }
        
        table { width: 100%; border-collapse: collapse; }
        td { padding: 12px 5px; border-bottom: 1px solid #eee; font-size: 14px; }
        
        .btn { padding: 8px 12px; border-radius: 5px; text-decoration: none; color: white; font-weight: bold; font-size: 11px; }
        .btn-green { background: var(--success); }
        .btn-orange { background: var(--accent); }
    </style>
</head>
<body>

<div class="nav">
    <span><b>BodaApp</b></span>
    <div>
        <a href="?lang=sw">SW</a> | <a href="?lang=en">EN</a>
        <a href="logout.php" style="background:rgba(255,0,0,0.2); padding:5px; border-radius:5px;">Logout</a>
    </div>
</div>

<div class="grid">
    <div class="card"><small><?php echo $t['rides']; ?></small><h2><?php echo $stats['total'] ?? 0; ?></h2></div>
    <div class="card"><small><?php echo $t['cash']; ?></small><h2 style="color:var(--success)">Tsh <?php echo number_format($stats['cash'] ?? 0); ?></h2></div>
</div>

<div id="map"></div>

<div class="section" style="border-color: var(--accent);">
    <h3>🏍️ <?php echo $t['active']; ?></h3>
    <?php if($active_trip): ?>
        <table>
            <tr>
                <td><b><?php echo $active_trip['cname']; ?></b><br><small><?php echo $active_trip['pickup_location']; ?> ➔ <?php echo $active_trip['destination']; ?></small></td>
                <td align="right"><a href="?action=complete&trip_id=<?php echo $active_trip['id']; ?>" class="btn btn-orange"><?php echo $t['btn_comp']; ?></a></td>
            </tr>
        </table>
    <?php else: ?>
        <p style="color:#999; font-size:13px; text-align:center;">Huna safari inayoendelea sasa hivi.</p>
    <?php endif; ?>
</div>

<div class="section" style="border-color: var(--success);">
    <h3>🔔 <?php echo $t['new']; ?></h3>
    <table>
        <?php if(mysqli_num_rows($new_trips) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($new_trips)): ?>
                <tr>
                    <td><b><?php echo $row['cname']; ?></b><br><small><?php echo $row['pickup_location']; ?> ➔ <?php echo $row['destination']; ?></small><br><b>Tsh <?php echo number_format($row['amount']); ?></b></td>
                    <td align="right"><a href="?action=accept&trip_id=<?php echo $row['id']; ?>" class="btn btn-green"><?php echo $t['btn_acc']; ?></a></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="2" align="center" style="color:#999;">Hakuna maombi mapya kwa sasa.</td></tr>
        <?php endif; ?>
    </table>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Map setup
    var map = L.map('map').setView([-6.7924, 39.2083], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    var marker = L.marker([-6.7924, 39.2083]).addTo(map);

    function syncLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((pos) => {
                var lat = pos.coords.latitude;
                var lng = pos.coords.longitude;
                marker.setLatLng([lat, lng]);
                map.setView([lat, lng]);

                // Tuma coordinates kwa file hili hili (Logic B)
                fetch(`rider_dashboard.php?update_lat=${lat}&update_lng=${lng}`)
                    .then(r => console.log("Location Synced"));
            });
        }
    }
    setInterval(syncLocation, 10000); // Kila sekunde 10
    syncLocation();
</script>

</body>
</html>