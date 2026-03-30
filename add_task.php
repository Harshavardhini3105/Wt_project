<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$subject = mysqli_real_escape_string($conn, $_POST['subject']);
$duration = intval($_POST['duration']);
$deadline = mysqli_real_escape_string($conn, $_POST['deadline']);

if ($subject && $duration > 0 && $deadline) {
    $sql = "INSERT INTO tasks (user_id, subject_name, duration_mins, deadline, status) 
            VALUES ('$user_id', '$subject', $duration, '$deadline', 'pending')";
    
    if (mysqli_query($conn, $sql)) {
        // After adding a single task, we regenerate the AI schedule 
        // mapping all pending topics to their proper study times.
        header("Location: generate_schedule.php?msg=added");
    } else {
        header("Location: dashboard.php?error=" . urlencode(mysqli_error($conn)));
    }
} else {
    header("Location: dashboard.php?error=Missing+Fields");
}
exit();
?>
