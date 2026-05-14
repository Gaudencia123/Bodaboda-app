<?php
include('db.php');
session_start();

/** @var mysqli $conn */ 
// Mstari wa juu unaiambia VS Code kuwa $conn inatoka kwenye db.php ili isionyeshe error myekundu

if(isset($_GET['lat']) && isset($_GET['lng']) && isset($_SESSION['user_id'])){
    // Kusafisha data ili kuzuia SQL Injection
    $lat = mysqli_real_escape_string($conn, $_GET['lat']);
    $lng = mysqli_real_escape_string($conn, $_GET['lng']);
    $rider_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);

    // Tunatafuta safari ambayo Rider yuko nayo kwa sasa (active)
    $sql = "UPDATE trips SET current_lat = '$lat', current_lng = '$lng' 
            WHERE rider_id = '$rider_id' AND status = 'active'";
    
    if(mysqli_query($conn, $sql)) {
        echo json_encode(["status" => "success", "message" => "Location updated"]);
    } else {
        echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
    }
}
?>