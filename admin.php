<?php
include('db.php');
session_start();

// 1. USALAMA
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// 2. API: Inatoa Location ya Rider (kutoka trips au users table kulingana na muundo wako)
if (isset($_GET['get_location'])) {
    header('Content-Type: application/json');
    $rider_id = mysqli_real_escape_string($conn, $_GET['get_location']);
    
    // Inatafuta location ya mwisho inayojulikana ya huyu rider
    $query = "SELECT trips.current_lat as lat, trips.current_lng as lng, users.name 
              FROM users 
              LEFT JOIN trips ON users.id = trips.rider_id 
              WHERE users.id = '$rider_id' 
              ORDER BY trips.id DESC LIMIT 1";
    
    $res = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($res);
    
    echo json_encode($data ?: ['error' => 'Location not found']);
    exit();
}

$page = $_GET['page'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <title>BodaApp Admin | Track Riders</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root { --sidebar-bg: #1a1c23; --primary: #ff9800; --bg: #f4f5f7; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; display: flex; }
        .sidebar { width: 260px; background: var(--sidebar-bg); color: white; height: 100vh; position: fixed; padding: 20px; }
        .sidebar h2 { color: var(--primary); text-align: center; }
        .sidebar a { display: block; padding: 12px; color: #adb5bd; text-decoration: none; border-radius: 8px; margin-bottom: 5px; }
        .sidebar a.active { background: rgba(255,152,0,0.1); color: var(--primary); }
        .main-content { margin-left: 260px; padding: 30px; width: 100%; }
        .table-wrapper { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .btn-view { background: #2ecc71; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 11px; }
        .btn-track { background: var(--primary); color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 11px; }

        /* MODAL */
        #mapModal { display: none; position: fixed; z-index: 9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.8); }
        .modal-content { background: white; width: 85%; height: 85%; margin: 3% auto; border-radius: 15px; position: relative; padding: 10px; }
        #trackMap { height: 100%; width: 100%; border-radius: 10px; }
        .close-btn { position: absolute; top: -10px; right: -10px; background: #e74c3c; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-weight: bold; border: 2px solid white; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>BodaApp</h2>
    <a href="?page=dashboard" class="<?= ($page == 'dashboard') ? 'active' : '' ?>">📊 Dashboard</a>
    <a href="?page=riders" class="<?= ($page == 'riders') ? 'active' : '' ?>">🏍️ Manage Riders</a>
    <a href="?page=customers" class="<?= ($page == 'customers') ? 'active' : '' ?>">👥 Customers</a>
    <a href="logout.php" style="color:#e74c3c; margin-top:20px;">🚪 Logout</a>
</div>

<div class="main-content">
    
    <?php if ($page == 'riders'): ?>
        <h1>Orodha ya Madereva</h1>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Jina</th><th>Simu</th><th>Hali ya Sasa</th><th>Kitendo</th></tr>
                </thead>
                <tbody>
                    <?php
                    $riders = mysqli_query($conn, "SELECT * FROM users WHERE role='rider'");
                    while($r = mysqli_fetch_assoc($riders)) {
                        echo "<tr>
                            <td><b>{$r['name']}</b></td>
                            <td>{$r['phone']}</td>
                            <td><span style='color:green'>● Online</span></td>
                            <td>
                                <button class='btn-view' onclick='openTracker({$r['id']}, \"{$r['name']}\")'>📍 View Location</button>
                            </td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <h1>Dashboard Overview</h1>
        <div class="table-wrapper">
            <h3>Safari Zinazoendelea (Live)</h3>
            <table>
                <thead>
                    <tr><th>Dereva</th><th>Njia</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php
                    $active = mysqli_query($conn, "SELECT trips.*, users.name FROM trips JOIN users ON trips.rider_id = users.id WHERE status='active'");
                    while($row = mysqli_fetch_assoc($active)) {
                        echo "<tr>
                            <td>{$row['name']}</td>
                            <td>{$row['pickup_location']} ➔ {$row['destination']}</td>
                            <td><button class='btn-track' onclick='openTracker({$row['rider_id']}, \"{$row['name']}\")'>Live Track</button></td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="mapModal">
    <div class="modal-content">
        <div class="close-btn" onclick="closeTracker()">X</div>
        <h3 id="trackTitle" style="margin-top:0;">Inatafuta Location...</h3>
        <div id="trackMap"></div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    let map, marker, interval;

    function openTracker(rid, rname) {
        document.getElementById('mapModal').style.display = 'block';
        document.getElementById('trackTitle').innerText = "Location ya: " + rname;
        
        if (!map) {
            map = L.map('trackMap').setView([-6.7924, 39.2083], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
            marker = L.marker([-6.7924, 39.2083]).addTo(map);
        }

        const fetchLoc = () => {
            fetch(`admin.php?get_location=${rid}`)
            .then(res => res.json())
            .then(data => {
                if(data.lat && data.lng) {
                    let pos = [parseFloat(data.lat), parseFloat(data.lng)];
                    marker.setLatLng(pos);
                    map.panTo(pos);
                    marker.bindPopup("<b>" + data.name + "</b> yuko hapa").openPopup();
                } else {
                    alert("Samahani, location ya huyu dereva haijapatikana.");
                    closeTracker();
                }
            });
        };

        fetchLoc();
        // Tunavuta data kila sekunde 7 ili usizidie server nguvu
        interval = setInterval(fetchLoc, 7000);
    }

    function closeTracker() {
        document.getElementById('mapModal').style.display = 'none';
        clearInterval(interval);
    }
</script>
</body>
</html>