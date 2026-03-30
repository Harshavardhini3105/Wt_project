<?php
session_start();
header('Content-Type: application/json');

include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    try {
        mysqli_begin_transaction($conn);
        
        // Update userfullname and new profile details
        $fullname = mysqli_real_escape_string($conn, $data['fullname']);
        $study_level = mysqli_real_escape_string($conn, $data['study_level']);
        $goal = mysqli_real_escape_string($conn, $data['goal']);
        $study_hours = intval($data['study_hours']);
        $pref_time = mysqli_real_escape_string($conn, $data['pref_time']);
        
        mysqli_query($conn, "UPDATE users SET 
            fullname='$fullname',
            study_level='$study_level',
            goal='$goal',
            study_hours=$study_hours,
            pref_time='$pref_time'
            WHERE id='$user_id'");
            
        $_SESSION['name'] = $data['fullname']; // Update session name immediately

        // Update user_settings
        $school = mysqli_real_escape_string($conn, $data['school']);
        $focus_session_mins = intval($data['focus_session_mins']);
        $short_break_mins = intval($data['short_break_mins']);
        $daily_limit_hours = intval($data['daily_limit_hours']);
        $email_notify = intval($data['email_notify']);
        $dark_mode = intval($data['dark_mode']);
        
        // Check if settings exist for the user
        $check = mysqli_query($conn, "SELECT 1 FROM user_settings WHERE user_id='$user_id'");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "UPDATE user_settings SET 
                school='$school', 
                focus_session_mins=$focus_session_mins, 
                short_break_mins=$short_break_mins, 
                daily_limit_hours=$daily_limit_hours, 
                email_notify=$email_notify, 
                dark_mode=$dark_mode 
                WHERE user_id='$user_id'");
        } else {
            mysqli_query($conn, "INSERT INTO user_settings 
                (user_id, school, focus_session_mins, short_break_mins, daily_limit_hours, email_notify, dark_mode) 
                VALUES 
                ('$user_id', '$school', $focus_session_mins, $short_break_mins, $daily_limit_hours, $email_notify, $dark_mode)");
        }
        
        mysqli_commit($conn);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
}
