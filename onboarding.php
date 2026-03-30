<?php
session_start();
include 'db.php';

// If user isn't logged in, send them back to signup
if (!isset($_SESSION['user_id'])) {
    header("Location: signup.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $goal = mysqli_real_escape_string($conn, $_POST['goal']);
    $hours = mysqli_real_escape_string($conn, $_POST['hours']);
    $time = mysqli_real_escape_string($conn, $_POST['time']);

    // Update the user record with onboarding info
    $sql = "UPDATE users SET goal='$goal', study_hours='$hours', pref_time='$time' WHERE id='$user_id'";

    if (mysqli_query($conn, $sql)) {
        header("Location: dashboard.php");
        exit();
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onboarding | AI Study Planner</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #f4f7f8; --card-bg: #ffffff; --text-main: #1e293b; --text-muted: #64748b;
            --primary-green: #15803d; --primary-green-hover: #14532d; --primary-green-light: #dcfce7; 
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        .auth-body { background-color: var(--bg-color); display: flex; align-items: center; justify-content: center; min-height: 100vh; color: var(--text-main); padding: 20px; }
        .auth-card { background: var(--card-bg); padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); width: 100%; max-width: 450px; }
        .progress-bar-container { width: 100%; height: 8px; background-color: #e2e8f0; border-radius: 10px; overflow: hidden; margin-bottom: 10px; }
        .progress-bar { height: 100%; background-color: var(--primary-green); border-radius: 10px; transition: width 0.8s ease-in-out; }
        .step-text { font-size: 0.85rem; color: var(--primary-green); font-weight: 600; text-align: right; margin-bottom: 25px; }
        .auth-card h2 { margin-bottom: 8px; font-size: 1.6rem; color: var(--text-main); }
        .auth-subtitle { color: var(--text-muted); margin-bottom: 30px; font-size: 0.95rem; line-height: 1.5; }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; }
        .input-group input, .input-group select { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 0.95rem; color: var(--text-main); transition: border-color 0.2s; background-color: #ffffff; }
        .input-group input:focus, .input-group select:focus { border-color: var(--primary-green); box-shadow: 0 0 0 3px var(--primary-green-light); }
        .btn-primary.auth-btn { width: 100%; background-color: var(--primary-green); color: white; padding: 14px; border: none; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: background-color 0.2s; margin-top: 15px; display: inline-block; }
        .btn-primary.auth-btn:hover { background-color: var(--primary-green-hover); }
    </style>
</head>
<body class="auth-body">
    <div class="auth-card">
        <div class="progress-bar-container">
            <div class="progress-bar" style="width: 25%;"></div>
        </div>
    
        <h2>Let's set up your AI.</h2>
        <p class="auth-subtitle">Tell us about your study habits so we can build your perfect schedule.</p>

        <form action="onboarding.php" method="POST">
    
    <div class="input-group">
        <label>What are you preparing for?</label>
        <input type="text" name="goal" placeholder="e.g., Final Exams, SAT" required>
    </div>

    <div class="input-group">
        <label>How many hours can you study per day?</label>
        <input type="number" name="hours" placeholder="e.g., 4" required min="1" max="16">
    </div>

    <div class="input-group">
        <label>When do you focus best?</label>
        <select name="time" required>
            <option value="" disabled selected>Choose your peak time...</option>
            <option value="morning">Morning (6 AM - 12 PM)</option>
            <option value="afternoon">Afternoon (12 PM - 5 PM)</option>
            <option value="evening">Evening (5 PM - 10 PM)</option>
        </select>
    </div>

    <button type="submit" class="btn-primary auth-btn">Generate My Smart Schedule ✨</button>
</form>
            

</body>
</html>
