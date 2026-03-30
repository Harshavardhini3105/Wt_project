<?php
$host = "localhost";
$user = "root"; 
$pass = "root"; 
$dbname = "study_planner";

// Procedural mysqli approach
$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}
?>
