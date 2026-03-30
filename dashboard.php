<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

$result = mysqli_query($conn, "SELECT fullname FROM users WHERE id='$user_id'");
$user = mysqli_fetch_assoc($result);

$name_parts = explode(" ", $user['fullname']);
$first_name = $name_parts[0];
$avatar_letter = strtoupper(substr($first_name, 0, 1));

// --- Missed Tasks Detection & Auto-Reschedule ---
$check_missed = mysqli_query($conn, "SELECT COUNT(*) as c FROM tasks WHERE user_id='$user_id' AND status='pending' AND deadline < NOW()");
$missed_count = mysqli_fetch_assoc($check_missed)['c'] ?? 0;
if ($missed_count > 0) {
    mysqli_query($conn, "UPDATE tasks SET status='missed' WHERE user_id='$user_id' AND status='pending' AND deadline < NOW()");
    header("Location: generate_schedule.php?auto=1");
    exit();
}

// === Dashboard Metrics ===

// 1. Study Hours (Total Scheduled)
$hours_query = mysqli_query($conn, "SELECT SUM(duration_mins) as total FROM tasks WHERE user_id='$user_id'");
$total_hours = round((mysqli_fetch_assoc($hours_query)['total'] ?? 0) / 60, 1);

// 2. Completion Percentage
$task_stats = mysqli_query($conn, "SELECT 
    COUNT(*) as total_tasks, 
    SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed_tasks 
    FROM tasks WHERE user_id='$user_id'");
$stats = mysqli_fetch_assoc($task_stats);
$completion_pct = $stats['total_tasks'] > 0 ? round(($stats['completed_tasks'] / $stats['total_tasks']) * 100) : 0;

// 3. Next Deadline
$deadline_query = mysqli_query($conn, "SELECT subject_name, deadline FROM tasks WHERE user_id='$user_id' AND status='pending' AND deadline >= NOW() ORDER BY deadline ASC LIMIT 1");
$next_task = mysqli_fetch_assoc($deadline_query);
$next_subj = "None";
$next_time = "Rest up!";
if ($next_task) {
    $next_subj = explode(' - ', $next_task['subject_name'])[0];
    
    // Calculate days away
    $diff = date_diff(new DateTime(), new DateTime($next_task['deadline']));
    if ($diff->d == 0) $next_time = "Today";
    else if ($diff->d == 1) $next_time = "Tomorrow";
    else $next_time = "in " . $diff->d . " days";
}

// 4. Today's Plan (Fetching upcoming 3 tasks)
$todays_tasks_query = mysqli_query($conn, "SELECT id, subject_name, duration_mins, DATE_FORMAT(deadline, '%h:%i %p') as time_str FROM tasks WHERE user_id='$user_id' AND status='pending' ORDER BY deadline ASC LIMIT 4");

// Check Dark Mode
$dark_mode = false;
$dm_query = mysqli_query($conn, "SELECT dark_mode FROM user_settings WHERE user_id='$user_id'");
if ($dm_query && mysqli_num_rows($dm_query) > 0) {
    $dm_row = mysqli_fetch_assoc($dm_query);
    if ($dm_row['dark_mode'] == 1) $dark_mode = true;
}
$body_class = $dark_mode ? 'class="dark-mode"' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | AI Study Planner</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css"> 
</head>
<body <?php echo $body_class; ?>>

    <div class="dashboard-layout">
        
        <aside class="sidebar">
            <div class="sidebar-logo">StudyPlaner</div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="active"><span>📊</span> Dashboard</a>
                <a href="schedule.php"><span>📅</span> My Schedule</a>
                <a href="subjects.php"><span>📚</span> Subjects</a>
                <a href="analytics.php"><span>📈</span> Analytics</a>
                <a href="settings.php"><span>⚙️</span> Settings</a>
            </nav>
            <div class="sidebar-bottom">
                <a href="logout.php" class="logout-btn"><span>🚪</span> Logout</a>
            </div>
        </aside>

        <main class="main-content">
            
            <header class="topbar">
                <div class="greeting">
                    <h1>Welcome back, <?php echo htmlspecialchars($first_name); ?>! 👋</h1>
                    <p>Here is your AI-optimized plan for today.</p>
                </div>
                <div class="user-profile">
                    <button class="btn-primary" id="openModalBtn">➕ Add Task</button>
                   
                    <div class="avatar"><?php echo htmlspecialchars($avatar_letter); ?></div>
                </div>
            </header>

            <section class="widgets-grid">
                <div class="widget-card">
                    <div class="widget-icon bg-green-light text-green">⏱️</div>
                    <div class="widget-info">
                        <p>Study Hours</p>
                        <h3><?php echo $total_hours; ?>h <span>scheduled</span></h3>
                    </div>
                </div>
                <div class="widget-card">
                    <div class="widget-icon bg-green-light text-green">✅</div>
                    <div class="widget-info">
                        <p>Completion</p>
                        <h3><?php echo $completion_pct; ?>% <span>on track</span></h3>
                    </div>
                </div>
                <div class="widget-card">
                    <div class="widget-icon bg-red-light text-red">🚨</div>
                    <div class="widget-info">
                        <p>Next Deadline</p>
                        <h3><?php echo htmlspecialchars($next_subj); ?> <span><?php echo $next_time; ?></span></h3>
                    </div>
                </div>
            </section>

            <div class="content-grid">
                <section class="todays-plan card">
                    <div class="section-header">
                        <h2>📅 Today's Study Plan</h2>
                        <a href="schedule.php" class="link-green">View Calendar</a>
                    </div>
                    <div class="task-list" id="taskList">
                        <?php if (mysqli_num_rows($todays_tasks_query) > 0): ?>
                            <?php while($t = mysqli_fetch_assoc($todays_tasks_query)): ?>
                            <div class="task-item">
                                <div>
                                    <strong style="color: #1e293b;"><?php echo htmlspecialchars($t['subject_name']); ?></strong><br>
                                    <span style="color: #15803d; font-weight: 600; font-size: 0.9rem;">⏰ <?php echo $t['time_str']; ?> (<?php echo $t['duration_mins']; ?>m)</span>
                                </div>
                                <button class="btn-primary" style="padding: 6px 12px; font-size: 0.85rem;" onclick="completeTask(<?php echo $t['id']; ?>)">✓ Done</button>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="color: #64748b; padding: 10px;">No upcoming tasks. Check your Subjects to regenerate plan!</p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <div id="taskModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeModalBtn">&times;</span>
            <h2>Add New Study Task</h2>
            <form id="newTaskForm" action="add_task.php" method="POST">
                <div class="input-group">
                    <label>Subject / Topic</label>
                    <input type="text" name="subject" id="taskSubject" required placeholder="e.g., Chapter 4: Photosynthesis">
                </div>
                <div class="input-group">
                    <label>Duration (Minutes)</label>
                    <input type="number" name="duration" id="taskDuration" required placeholder="60">
                </div>
                <div class="input-group">
                    <label>Exam Deadline</label>
                    <input type="datetime-local" name="deadline" id="taskDeadline" required>
                </div>
                <button type="submit" class="btn-primary full-width">Save Task</button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById("taskModal");
        const openBtn = document.getElementById("openModalBtn");
        const closeBtn = document.getElementById("closeModalBtn");

        if (openBtn) openBtn.addEventListener("click", () => modal.style.display = "flex");
        if (closeBtn) closeBtn.addEventListener("click", () => modal.style.display = "none");
        window.addEventListener("click", (e) => { if (e.target === modal) modal.style.display = "none"; });
    });

    function completeTask(taskId) {
        if (!confirm('Mark this task as completed?')) return;
        
        // Simple AJAX POST to complete_task.php
        fetch('complete_task.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'task_id=' + taskId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload dashboard to apply changes
                window.location.reload();
            } else {
                alert('Error marking task as complete: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('Network error while completing task.');
        });
    }
    </script>
</body>
</html>
