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

// === Analytics Calculations ===

// 1. Total Study Time
$stats_query = mysqli_query($conn, "SELECT 
    SUM(duration_mins) as total_mins,
    SUM(CASE WHEN status='completed' THEN duration_mins ELSE 0 END) as completed_mins,
    COUNT(*) as total_tasks,
    SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed_tasks
    FROM tasks WHERE user_id='$user_id'");

$stats = mysqli_fetch_assoc($stats_query);
$total_mins = $stats['total_mins'] ?? 0;
$completed_mins = $stats['completed_mins'] ?? 0;

$total_hours = round($total_mins / 60, 1);
$completed_hours = round($completed_mins / 60, 1);

$total_tasks = $stats['total_tasks'] ?? 0;
$completed_tasks = $stats['completed_tasks'] ?? 0;

$focus_score = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;
$focus_label = "needs work";
if ($focus_score >= 80) $focus_label = "excellent";
else if ($focus_score >= 50) $focus_label = "good";

// 2. Best Subject (Based on most completed minutes)
$best_query = mysqli_query($conn, "SELECT subject_name, SUM(duration_mins) as total FROM tasks WHERE user_id='$user_id' AND status='completed' GROUP BY subject_name ORDER BY total DESC LIMIT 1");
$best_subject_row = mysqli_fetch_assoc($best_query);
$best_subject = $best_subject_row ? $best_subject_row['subject_name'] : "None yet";
// Clean up subject name "Name - Topic 1" -> "Name"
$best_subject_clean = explode(' - ', $best_subject)[0];

// 3. Subject Breakdown Progress Bars (Based on completed time)
$subject_data_query = mysqli_query($conn, "SELECT subject_name, SUM(duration_mins) as total FROM tasks WHERE user_id='$user_id' AND status='completed' GROUP BY subject_name ORDER BY total DESC LIMIT 4");
$subjectBreakdown = [];
$max_subj_mins = 1; // Default to avoid division by zero
$first_run = true;
while ($row = mysqli_fetch_assoc($subject_data_query)) {
    if ($first_run) { $max_subj_mins = max(1, $row['total']); $first_run = false; }
    $percent = round(($row['total'] / max($max_subj_mins, 1)) * 100);
    $hours_str = round($row['total'] / 60, 1) . "h";
    $clean_name = explode(' - ', $row['subject_name'])[0];
    
    $subjectBreakdown[] = [
        'name' => $clean_name,
        'hours' => $hours_str,
        'percent' => $percent > 0 ? $percent : 5
    ];
}

// 4. Weekly Chart Data (Completed study time per day)
// MySQL DAYOFWEEK() returns 1=Sun, 2=Mon... 7=Sat
$week_query = mysqli_query($conn, "SELECT DAYOFWEEK(deadline) as dw, SUM(duration_mins) as total FROM tasks WHERE user_id='$user_id' AND status='completed' GROUP BY dw");
$days_map = [2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 1 => 0]; // Mon, Tue... Sun
$max_day_mins = 1;
while ($row = mysqli_fetch_assoc($week_query)) {
    $days_map[$row['dw']] = $row['total'];
    if ($row['total'] > $max_day_mins) $max_day_mins = $row['total'];
}

$weeklyData = [
    ['day' => 'Mon', 'hours' => round($days_map[2]/60, 1), 'percent' => round(($days_map[2]/$max_day_mins)*100), 'isWeekend' => false],
    ['day' => 'Tue', 'hours' => round($days_map[3]/60, 1), 'percent' => round(($days_map[3]/$max_day_mins)*100), 'isWeekend' => false],
    ['day' => 'Wed', 'hours' => round($days_map[4]/60, 1), 'percent' => round(($days_map[4]/$max_day_mins)*100), 'isWeekend' => false],
    ['day' => 'Thu', 'hours' => round($days_map[5]/60, 1), 'percent' => round(($days_map[5]/$max_day_mins)*100), 'isWeekend' => false],
    ['day' => 'Fri', 'hours' => round($days_map[6]/60, 1), 'percent' => round(($days_map[6]/$max_day_mins)*100), 'isWeekend' => false],
    ['day' => 'Sat', 'hours' => round($days_map[7]/60, 1), 'percent' => round(($days_map[7]/$max_day_mins)*100), 'isWeekend' => true],
    ['day' => 'Sun', 'hours' => round($days_map[1]/60, 1), 'percent' => round(($days_map[1]/$max_day_mins)*100), 'isWeekend' => true]
];

// Ensure valid percentages
foreach ($weeklyData as &$wd) {
    if ($wd['percent'] == 0) $wd['percent'] = 5; // Minimum visible bar
}

$weekly_json = json_encode($weeklyData);
$subject_json = json_encode($subjectBreakdown);

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
    <title>Analytics | AI Study Planner</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="dashboard.css"> 
</head>
<body <?php echo $body_class; ?>>

    <div class="dashboard-layout">
        
        <aside class="sidebar">
            <div class="sidebar-logo">StudyPlaner</div>
            <nav class="sidebar-nav">
                <a href="dashboard.php"><span>📊</span> Dashboard</a>
                <a href="schedule.php"><span>📅</span> My Schedule</a>
                <a href="subjects.php"><span>📚</span> Subjects</a>
                <a href="analytics.php" class="active"><span>📈</span> Analytics</a>
                <a href="settings.php"><span>⚙️</span> Settings</a>
            </nav>
            <div class="sidebar-bottom">
                <a href="logout.php" class="logout-btn"><span>🚪</span> Logout</a>
            </div>
        </aside>

        <main class="main-content">
            
            <header class="topbar">
                <div class="greeting">
                    <h1>Your Analytics 📈</h1>
                    <p>Track your study trends and performance over time.</p>
                </div>
                <div class="user-profile">
                    <button class="btn-gradient">Generate Report</button>
                    <div class="avatar"><?php echo htmlspecialchars($avatar_letter); ?></div>
                </div>
            </header>

            <section class="widgets-grid">
                <div class="widget-card">
                    <div class="widget-icon bg-green-light text-green">⏳</div>
                    <div class="widget-info">
                        <p>Total Study Time</p>
                        <h3><?php echo $total_mins == 0 ? 'New' : htmlspecialchars($completed_hours) . 'h <span>completed</span>'; ?></h3>
                    </div>
                </div>
                <div class="widget-card">
                    <div class="widget-icon bg-green-light text-green">🎯</div>
                    <div class="widget-info">
                        <p>Avg. Focus Score</p>
                        <h3><?php echo $total_mins == 0 ? 'New' : htmlspecialchars($focus_score) . '% <span>' . htmlspecialchars($focus_label) . '</span>'; ?></h3>
                    </div>
                </div>
                <div class="widget-card">
                    <div class="widget-icon bg-green-light text-green">🏆</div>
                    <div class="widget-info">
                        <p>Best Subject</p>
                        <h3><?php echo $total_mins == 0 ? 'New' : htmlspecialchars($best_subject_clean) . ' <span>top tier</span>'; ?></h3>
                    </div>
                </div>
            </section>

            <div class="analytics-grid">
                
                <section class="card chart-section">
                    <div class="section-header">
                        <h2>📊 Study Hours (This Week)</h2>
                    </div>
                    <div class="bar-chart-container" id="weeklyChart">
                        </div>
                </section>

                <section class="card subject-section">
                    <div class="section-header">
                        <h2>📚 Time by Subject</h2>
                    </div>
                    <div class="subject-list" id="subjectBreakdown">
                        </div>
                </section>

            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {

        // --- 1. Render the Weekly Bar Chart (Dynamic from PHP) ---
        const weeklyData = <?php echo $weekly_json; ?>;
        const chartContainer = document.getElementById('weeklyChart');

        if (chartContainer) {
            weeklyData.forEach(data => {
                const col = document.createElement('div');
                col.className = 'bar-column';
                const weekendClass = data.isWeekend ? 'weekend' : '';
                col.innerHTML = `
                    <span style="font-size: 0.75rem; color: var(--primary-green); font-weight: 600; margin-bottom: 5px;">${data.hours}h</span>
                    <div class="bar-fill ${weekendClass}" style="height: 0%" data-target="${data.percent}%"></div>
                    <div class="bar-label">${data.day}</div>
                `;
                chartContainer.appendChild(col);
            });

            setTimeout(() => {
                document.querySelectorAll('.bar-fill').forEach(bar => {
                    bar.style.height = bar.getAttribute('data-target');
                });
            }, 100);
        }

        // --- 2. Render the Subject Breakdown Progress Bars (Dynamic from PHP) ---
        const subjectData = <?php echo $subject_json; ?>;
        const subjectContainer = document.getElementById('subjectBreakdown');

        if (subjectContainer) {
            if (subjectData.length === 0) {
                subjectContainer.innerHTML = "<p style='color:#64748b;font-size:0.9rem;'>No tasks scheduled yet. Add subjects and generate a plan!</p>";
            } else {
                subjectData.forEach(sub => {
                    const item = document.createElement('div');
                    item.className = 'subject-item';
                    item.innerHTML = `
                        <div class="subject-header">
                            <span>${sub.name}</span>
                            <span class="subject-hours">${sub.hours}</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" style="width: 0%" data-target="${sub.percent}%"></div>
                        </div>
                    `;
                    subjectContainer.appendChild(item);
                });

                setTimeout(() => {
                    document.querySelectorAll('.progress-fill').forEach(bar => {
                        bar.style.width = bar.getAttribute('data-target');
                    });
                }, 100);
            }
        }
    });
    </script>
</body>
</html>
