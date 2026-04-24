<?php
include('db.php');
session_start();

if(isset($_GET['lat']) && isset($_GET['lng']) && isset($_SESSION['user_id'])){
    $lat = $_GET['lat'];
    $lng = $_GET['lng'];
    $rider_id = $_SESSION['user_id'];

    // Tunatafuta safari ambayo Rider yuko nayo kwa sasa (active)
    $sql = "UPDATE trips SET current_lat = '$lat', current_lng = '$lng' 
            WHERE rider_id = '$rider_id' AND status = 'active'";
    
    mysqli_query($conn, $sql);
}
?>