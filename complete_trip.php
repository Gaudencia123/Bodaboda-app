<?php
include('db.php');
session_start();

if(isset($_GET['id']) && $_SESSION['role'] == 'rider'){
    $trip_id = $_GET['id'];

    $sql = "UPDATE trips SET status = 'completed' WHERE id = '$trip_id'";
    
    if(mysqli_query($conn, $sql)){
        header("Location: rider_dashboard.php?msg=Safari Imekamilika");
    }
}
// Ndani ya while loop ya admin.php
echo "<td>";
if($t['status'] == 'active'){
    // Inatengeneza link ya Google Maps kwa kutumia Lat na Lng zilizopo kwenye DB
    echo "<a href='https://www.google.com/maps?q={$t['current_lat']},{$t['current_lng']}' target='_blank' style='color:red;'>Track Live Movement</a>";
} else {
    echo "No active movement";
}
echo "</td>";
?>