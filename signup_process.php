<?php
// Enable error reporting so we can see what's wrong
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root"; 
$pass = ""; 
$dbname = "study_planner";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("❌ Database Connection Failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect data
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $study_level = $_POST['study_level'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Prepare statement
    $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, study_level) VALUES (?, ?, ?, ?)");
    
    if($stmt === false) {
        die("❌ SQL Error: " . $conn->error);
    }

    $stmt->bind_param("ssss", $fullname, $email, $password, $study_level);

    if ($stmt->execute()) {
        // Success!
        echo "✅ Data saved! Redirecting...";
        header("Location: onboarding.html");
        exit();
    } else {
        echo "❌ Execution Error: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>
