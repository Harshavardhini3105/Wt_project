<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Get parameters
// First check POST, then fallback to user_settings, then fallback to defaults
$target_hours = 4;
$max_subjects_per_day = 2;

$settings_res = mysqli_query($conn, "SELECT daily_limit_hours FROM user_settings WHERE user_id='$user_id'");
if ($settings_res && mysqli_num_rows($settings_res) > 0) {
    $row = mysqli_fetch_assoc($settings_res);
    if (!empty($row['daily_limit_hours'])) $target_hours = floatval($row['daily_limit_hours']);
}

if (isset($_POST['target_hours'])) $target_hours = floatval($_POST['target_hours']);
if (isset($_POST['max_subjects_per_day'])) $max_subjects_per_day = intval($_POST['max_subjects_per_day']);

// 1. Clear existing 'pending' tasks so we regenerate a fresh AI plan
mysqli_query($conn, "DELETE FROM tasks WHERE user_id='$user_id' AND status='pending'");

// Fetch user's pref time
$user_res = mysqli_query($conn, "SELECT pref_time FROM users WHERE id='$user_id'");
$user_data = mysqli_fetch_assoc($user_res);
$pref_time = strtolower($user_data['pref_time'] ?? 'morning');

// Determine start hour based on pref_time
$start_hour = 10; // Default Moring 10 AM
if ($pref_time == 'morning') $start_hour = 8;
else if ($pref_time == 'afternoon') $start_hour = 14;
else if ($pref_time == 'evening') $start_hour = 18;
else if ($pref_time == 'night') $start_hour = 20;

// 2. Fetch user's subjects
$subjects_query = "SELECT * FROM subjects WHERE user_id='$user_id'";
$subjects_result = mysqli_query($conn, $subjects_query);

$subjects = [];
while($row = mysqli_fetch_assoc($subjects_result)) {
    $subjects[] = $row;
}

// If no subjects, just return to schedule
if (count($subjects) == 0) {
    header("Location: schedule.php?msg=no_subjects");
    exit();
}

$start_date = new DateTime();
$start_date->modify('+1 day'); // Start scheduling from tomorrow

// 3. Fetch user's COMPLETED tasks to avoid rescheduling them
$completed_query = mysqli_query($conn, "SELECT DISTINCT subject_name FROM tasks WHERE user_id='$user_id' AND status='completed'");
$completed_topics = [];
while($row = mysqli_fetch_assoc($completed_query)) {
    $completed_topics[] = $row['subject_name'];
}

// We need to keep track of ALL uncompleted topics that need to be scheduled
$topics_to_schedule = [];
foreach ($subjects as $subj) {
    $max = max(1, $subj['topics_count']); // Ensure at least 1 task
    for ($i=1; $i<=$max; $i++) {
        $subject_topic_name = $subj['name'] . " - Topic " . $i;
        
        // Skip this topic if the user already completed it
        if (in_array($subject_topic_name, $completed_topics)) {
            continue;
        }
        
        $topics_to_schedule[] = [
            'subject_id' => $subj['id'],
            'subject_name' => $subj['name'],
            'topic_string' => $subject_topic_name,
            'duration' => $subj['daily_study_mins'] ?? 30, // Fallback to 30 mins
        ];
    }
}

// Interleave topics array to alternate subjects so it's a balanced schedule
// We use a simple shuffle for AI variety
shuffle($topics_to_schedule);

// Now pop elements and fit them into days
$current_date = clone $start_date;

$current_day_mins = 0;
$current_day_subjects = []; // To enforce max_subjects_per_day
$target_mins = $target_hours * 60;

foreach ($topics_to_schedule as $topic) {
    
    // Safety check: if a SINGLE topic is longer than the entire daily target hours,
    // we MUST schedule it anyway or it will cause an infinite loop of skipping days.
    $is_huge_topic = $topic['duration'] >= $target_mins;
    
    $would_exceed_time = !$is_huge_topic && ($current_day_mins + $topic['duration'] > $target_mins);
    $is_new_subj = !in_array($topic['subject_id'], $current_day_subjects);
    $would_exceed_subj = $is_new_subj && (count($current_day_subjects) >= $max_subjects_per_day);
    
    // If we exceed limits for today, roll over to the next day
    // (Unless it's the start of a fresh day and the topic is huge, then we just schedule it)
    if (($would_exceed_time || $would_exceed_subj) && $current_day_mins > 0) {
        $current_date->modify('+1 day');
        $current_day_mins = 0;
        $current_day_subjects = [];
    }
    
    // Actually set the task's deadline for display
    $task_time = clone $current_date;
    $task_time->setTime($start_hour, 0);
    $task_time->modify("+{$current_day_mins} minutes");
    
    $subject_name = mysqli_real_escape_string($conn, $topic['topic_string']);
    $duration = $topic['duration'];
    $deadline = $task_time->format('Y-m-d H:i:s');
    
    mysqli_query($conn, "INSERT INTO tasks (user_id, subject_name, duration_mins, deadline, status) 
                         VALUES ('$user_id', '$subject_name', '$duration', '$deadline', 'pending')");
                         
    $current_day_mins += $duration;
    if (!in_array($topic['subject_id'], $current_day_subjects)) {
        $current_day_subjects[] = $topic['subject_id'];
    }
    
    // If the massive topic filled the whole day, forcefully move to the next day
    if ($current_day_mins >= $target_mins) {
        $current_date->modify('+1 day');
        $current_day_mins = 0;
        $current_day_subjects = [];
    }
}

if (isset($_GET['auto']) && $_GET['auto'] == '1') {
    // If it was auto-triggered (e.g. from dashboard because of missed tasks)
    header("Location: dashboard.php?msg=auto_rescheduled");
} else {
    // If it was manually triggered
    header("Location: schedule.php?msg=success");
}
exit();
?>
