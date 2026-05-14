<?php
// Kwa XAMPP, host ni "localhost"
$host = "localhost"; 

// Kwa XAMPP, user ni "root" na password huwa ni wazi (empty string)
$user = "root";
$pass = ""; 

$dbname = "bodaboda_system";

// Jaribu kuunganisha
$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
} else {
    echo "Umeunganishwa kikamilifu!";
}
?>