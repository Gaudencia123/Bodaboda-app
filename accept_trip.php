<?php
include('db.php');
session_start();

if(isset($_GET['id']) && $_SESSION['role'] == 'rider'){
    $trip_id = $_GET['id'];
    $rider_id = $_SESSION['user_id'];

    $sql = "UPDATE trips SET rider_id = '$rider_id', status = 'active' WHERE id = '$trip_id'";
    
    if(mysqli_query($conn, $sql)){
        header("Location: rider_dashboard.php?msg=Safari Imeanza");
    }
}
?>