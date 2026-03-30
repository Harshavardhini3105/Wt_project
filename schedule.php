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

// Fetch user tasks for the calendar
$tasks_query = "SELECT * FROM tasks WHERE user_id='$user_id'";
$tasks_result = mysqli_query($conn, $tasks_query);
$calendar_tasks = [];
while ($row = mysqli_fetch_assoc($tasks_result)) {
    // Basic conversion logic to format them for JS
    $calendar_tasks[] = [
        'subject' => $row['subject_name'],
        'duration' => $row['duration_mins'],
        'deadline' => $row['deadline'],
        'status' => $row['status']
    ];
}
$tasks_json = json_encode($calendar_tasks);

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
    <title>My Schedule | AI Study Planner</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="dashboard.css">
    
    <style>
        /* --- CALENDAR SPECIFIC STYLES --- */
        .calendar-container {
            background: var(--card-bg, #ffffff);
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-top: 10px;
        }

        .calendar-header {
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f1f5f9;
        }

        .calendar-header h2 {
            font-size: 1.25rem;
            color: var(--text-main);
        }

        .calendar-header-info {
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* 7 Columns for the 7 days of the week */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #e2e8f0; /* The "Border" color between days */
        }

        .day-label {
            background: #f8fafc;
            padding: 12px 10px;
            text-align: center;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .cal-day {
            background: white;
            min-height: 120px;
            padding: 12px;
            transition: 0.2s;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .cal-day:hover { background: #fcfdfd; }

        .date-num {
            font-size: 0.85rem;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 4px;
            display: block;
        }

        .inactive { background: #fdfdfd; opacity: 0.5; }
        
        /* Today Highlight using Thick Green Theme */
        .today { background: #dcfce7 !important; } /* Light green background */
        .today .date-num { color: #15803d; font-weight: 800; } /* Thick green text */

        /* Event Tags */
        .event {
            font-size: 10px;
            padding: 5px 8px;
            border-radius: 6px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
            text-align: left;
        }
        
        .event:hover { opacity: 0.8; }
        
        /* Event Colors */
        .blue { background: #3b82f6; } 
        .green { background: #15803d; } 
        .red { background: #ef4444; }
        .yellow { background: #f59e0b; color: #fff; text-align: center; font-weight: bold; }
        .purple { background: #9333ea; }
        .pink { background: #db2777; }
        .orange { background: #ea580c; }
        .teal { background: #0d9488; }
        .indigo { background: #4f46e5; }
    </style>
</head>
<body <?php echo $body_class; ?>>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo"> StudyPlaner</div>
            <nav class="sidebar-nav">
                <a href="dashboard.php"><span>📊</span> Dashboard</a>
                <a href="schedule.php" class="active"><span>📅</span> My Schedule</a>
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
                    <h1>My Schedule 📅</h1>
                    <p>Full month view for <?php echo date('F Y'); ?>.</p>
                </div>
                <div class="user-profile">
                    <button id="openRegenerateModalBtn" class="btn-primary" style="display: inline-block;">✨ Regenerate Plan</button>
                    <div class="avatar"><?php echo htmlspecialchars($avatar_letter); ?></div>
                </div>
            </header>

            <section class="calendar-container">
                <div class="calendar-header">
                    <h2 id="calendar-month-title">Current Month</h2>
                    <div class="calendar-header-info">Your Custom AI Plan</div>
                </div>
                
                <div class="calendar-grid" style="border-bottom: 1px solid #e2e8f0;">
                    <div class="day-label">Sun</div><div class="day-label">Mon</div>
                    <div class="day-label">Tue</div><div class="day-label">Wed</div>
                    <div class="day-label">Thu</div><div class="day-label">Fri</div>
                    <div class="day-label">Sat</div>
                </div>

                <div class="calendar-grid" id="full-month-grid">
                    <div style="padding: 20px; grid-column: 1 / -1; text-align: center; color: #64748b;">Loading your schedule...</div>
                </div>
            </section>
        </main>
    </div>

    <!-- REGENERATE SCHEDULE MODAL -->
    <div id="regenerateModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeRegenerateModalBtn">&times;</span>
            <h2>✨ Generate AI Study Plan</h2>
            <p style="color: #64748b; margin-bottom: 20px; font-size: 0.9rem;">Tell us how you want to study, and we'll automatically schedule all your subjects based on your preferred time of day.</p>
            <form action="generate_schedule.php" method="POST" id="regenerateForm">
                <div class="input-group">
                    <label>Target Study Time per Day (Hours)</label>
                    <input type="number" step="0.5" name="target_hours" required placeholder="e.g. 4" value="4">
                </div>
                <div class="input-group">
                    <label>Max Subjects per Day</label>
                    <input type="number" name="max_subjects_per_day" required placeholder="e.g. 2" value="2">
                </div>
                <button type="submit" class="btn-primary full-width" id="generateSubmitBtn">Generate Schedule</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Modal Logic
            const regenModal = document.getElementById("regenerateModal");
            const openRegenBtn = document.getElementById("openRegenerateModalBtn");
            const closeRegenBtn = document.getElementById("closeRegenerateModalBtn");

            if (openRegenBtn) openRegenBtn.addEventListener("click", () => regenModal.style.display = "flex");
            if (closeRegenBtn) closeRegenBtn.addEventListener("click", () => regenModal.style.display = "none");
            
            // Close modal when clicking outside the box
            window.addEventListener("click", (e) => { 
                if (e.target === regenModal) regenModal.style.display = "none"; 
            });

            const grid = document.getElementById('full-month-grid');
            const headerTitle = document.getElementById('calendar-month-title');
            const tasksData = <?php echo $tasks_json; ?>;
            
            let calendarHTML = ''; 
            
            const availableColors = ['blue', 'green', 'purple', 'pink', 'orange', 'teal', 'indigo'];
            const subjectColorMap = {};
            let colorIndex = 0;
            
            // Dynamic Date Logic
            const today = new Date();
            const currentMonth = today.getMonth();
            const currentYear = today.getFullYear();
            const currentDate = today.getDate();
            
            // Format naturally e.g. "March 2026"
            const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            if (headerTitle) headerTitle.innerText = `${monthNames[currentMonth]} ${currentYear}`;
            
            // First day index (0 = Sun, 1 = Mon)
            const firstDayIndex = new Date(currentYear, currentMonth, 1).getDay();
            // Total days in current month
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
            // Total days in previous month
            const daysInPrevMonth = new Date(currentYear, currentMonth, 0).getDate();
            
            // 1. Fill inactive prefix days from previous month
            for (let x = firstDayIndex; x > 0; x--) {
                let pDate = daysInPrevMonth - x + 1;
                calendarHTML += `<div class="cal-day inactive"><span class="date-num">${pDate}</span></div>`;
            }

            // 2. Fill active days
            for (let day = 1; day <= daysInMonth; day++) {
                let isToday = (day === currentDate) ? 'today' : '';
                let eventsHtml = '';
                
                tasksData.forEach(task => {
                    // Extract exact date match
                    let tDateObj = new Date(task.deadline);
                    if (tDateObj.getDate() === day && tDateObj.getMonth() === currentMonth && tDateObj.getFullYear() === currentYear) {
                        let baseSubject = task.subject.split(' - ')[0];
                        if (!subjectColorMap[baseSubject]) {
                            subjectColorMap[baseSubject] = availableColors[colorIndex % availableColors.length];
                            colorIndex++;
                        }
                        let colorClass = subjectColorMap[baseSubject];
                        if (task.status === 'missed') colorClass = 'red';
                        eventsHtml += `<div class="event ${colorClass}">${task.subject} (${task.duration}m)</div>`;
                    }
                });

                if (tasksData.length === 0 && day === currentDate) {
                    eventsHtml += `<div class="event blue">Welcome to your Calendar!</div>`;
                }

                calendarHTML += `
                    <div class="cal-day ${isToday}">
                        <span class="date-num">${day}</span>
                        ${eventsHtml}
                    </div>
                `;
            }
            
            // // Fill inactive suffix days to complete the calendar grid
            const totalCells = firstDayIndex + daysInMonth;
            let remainingCells = 7 - (totalCells % 7);
            if (remainingCells < 7) {
                for(let j = 1; j <= remainingCells; j++){
                    calendarHTML += `<div class="cal-day inactive"><span class="date-num">${j}</span></div>`;
                }
            }

            grid.innerHTML = calendarHTML;
        });
    </script>
</body>
</html>
