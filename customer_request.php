<?php
include('db.php');
session_start();

// 1. Usalama na Logout Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];

if(isset($_POST['submit'])){
    $p = mysqli_real_escape_string($conn, $_POST['pickup']);
    $d = mysqli_real_escape_string($conn, $_POST['destination']);
    $a = mysqli_real_escape_string($conn, $_POST['amount']);
    
    $sql = "INSERT INTO trips (customer_id, pickup_location, destination, amount, status) 
            VALUES ('$customer_id', '$p', '$d', '$a', 'pending')";
            
    if(mysqli_query($conn, $sql)){
        $success_msg = "Ombi limetumwa! Subiri dereva akubali.";
    } else {
        $error_msg = "Tatizo: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BodaApp | Dashibodi ya Mteja</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root { --main-color: #ff9800; --dark-blue: #2c3e50; --bg: #f8f9fa; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--bg); margin: 0; padding: 0; }
        
        /* Navbar */
        .navbar { background: var(--dark-blue); padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; color: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar a { color: white; text-decoration: none; font-weight: bold; margin-left: 20px; }
        .logout-btn { background: #e74c3c; padding: 8px 15px; border-radius: 5px; }

        .main-container { max-width: 1100px; margin: 30px auto; display: grid; grid-template-columns: 1fr 1.5fr; gap: 25px; padding: 0 20px; }
        
        /* Form Styling */
        .request-form { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .request-form h2 { margin-bottom: 25px; color: var(--dark-blue); font-size: 24px; }
        
        .input-group { margin-bottom: 20px; position: relative; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        input { width: 100%; padding: 14px; border: 2px solid #edf2f7; border-radius: 12px; box-sizing: border-box; font-size: 15px; transition: 0.3s; }
        input:focus { border-color: var(--main-color); outline: none; box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.1); }
        
        .price-card { background: #fff9f0; padding: 20px; border-radius: 15px; border: 1px dashed var(--main-color); margin-top: 20px; }
        .price-card h3 { margin: 0; color: var(--main-color); font-size: 22px; }
        
        .btn-submit { width: 100%; padding: 16px; background: var(--main-color); color: white; border: none; border-radius: 12px; font-weight: bold; font-size: 17px; cursor: pointer; margin-top: 20px; transition: 0.3s; box-shadow: 0 5px 15px rgba(255, 152, 0, 0.3); }
        .btn-submit:hover { transform: translateY(-2px); background: #f57c00; }

        /* Map Styling */
        .map-wrapper { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 8px solid white; position: relative; }
        #map { height: 550px; width: 100%; }
        
        @media (max-width: 850px) { .main-container { grid-template-columns: 1fr; } .map-wrapper { order: -1; } }
    </style>
</head>
<body>

<div class="navbar">
    <div style="font-size: 22px; font-weight: bold;">🏍️ BodaApp</div>
    <div>
        <span>Karibu, Mteja</span>
        <a href="dashboard.php">Nyumbani</a>
        <a href="logout.php" class="logout-btn">Ondoka</a>
    </div>
</div>

<div class="main-container">
    <div class="request-form">
        <h2>Agiza Safari 📍</h2>
        
        <?php if(isset($success_msg)) echo "<div style='background:#d4edda; color:#155724; padding:15px; border-radius:10px; margin-bottom:20px;'>$success_msg</div>"; ?>

        <form method="POST" id="rideForm">
            <div class="input-group">
                <label>Unatokea wapi? (Pickup)</label>
                <input type="text" name="pickup" id="pickup_input" placeholder="Andika eneo (Mf: Kariakoo)" required onblur="geocodeAddress('pickup')">
            </div>
            
            <div class="input-group">
                <label>Unakwenda wapi? (Destination)</label>
                <input type="text" name="destination" id="dest_input" placeholder="Andika unapoenda (Mf: Posta)" required onblur="geocodeAddress('dest')">
            </div>
            
            <div class="price-card">
                <label>Makadirio ya Bei</label>
                <h3 id="display_price">TSH 0</h3>
                <input type="hidden" name="amount" id="amount_hidden">
                <small id="km_info" style="color: #888;">Ingiza maeneo kupata bei...</small>
            </div>
            
            <button type="submit" name="submit" class="btn-submit">AGIZA BODA SASA</button>
        </form>
    </div>

    <div class="map-wrapper">
        <div id="map"></div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    // Inizialize Map
    var map = L.map('map').setView([-6.7924, 39.2083], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    var pickupCoords = null;
    var destCoords = null;
    var pickupMarker, destMarker;
    var ratePerKm = 50;

    // Search function kwa kutumia Nominatim (BURE)
    async function geocodeAddress(type) {
        let input = document.getElementById(type + '_input').value;
        if(input.length < 3) return;

        try {
            let response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(input)}`);
            let data = await response.json();

            if (data.length > 0) {
                let lat = parseFloat(data[0].lat);
                let lon = parseFloat(data[0].lon);
                let coords = L.latLng(lat, lon);

                if (type === 'pickup') {
                    pickupCoords = coords;
                    if(pickupMarker) map.removeLayer(pickupMarker);
                    pickupMarker = L.marker(coords).addTo(map).bindPopup("Mwanzo").openPopup();
                } else {
                    destCoords = coords;
                    if(destMarker) map.removeLayer(destMarker);
                    destMarker = L.marker(coords).addTo(map).bindPopup("Mwisho").openPopup();
                }

                map.panTo(coords);
                calculateLogic();
            }
        } catch (error) {
            console.error("Error fetching location");
        }
    }

    function calculateLogic() {
        if (pickupCoords && destCoords) {
            let distanceMetres = pickupCoords.distanceTo(destCoords);
            let distanceKm = (distanceMetres / 1000).toFixed(2);
            
            let price = Math.round(distanceKm * ratePerKm);
            if(price < 1500) price = 1500; // Bei ya chini kabisa

            document.getElementById('display_price').innerText = "TSH " + price.toLocaleString();
            document.getElementById('amount_hidden').value = price;
            document.getElementById('km_info').innerText = "Umbali: " + distanceKm + " Km";
            
            // Zoom map kuona zote mbili
            var group = new L.featureGroup([pickupMarker, destMarker]);
            map.fitBounds(group.getBounds().pad(0.5));
        }
    }
</script>

</body>
</html>