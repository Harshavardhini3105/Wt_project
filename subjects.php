<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Handle new subject submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_subject') {
    $name = mysqli_real_escape_string($conn, $_POST['subjectName']);
    $icon = mysqli_real_escape_string($conn, $_POST['subjectIcon']);
    $topics = (int)$_POST['subjectTopics'];
    $daily_mins = (int)$_POST['dailyStudyMins'];
    
    // Extract emoji from string if needed, or store string
    $icon = mb_substr($icon, 0, 2); // Taking the first char (emoji)
    
    $query = "INSERT INTO subjects (user_id, name, icon, topics_count, daily_study_mins, mastery_percent) VALUES ('$user_id', '$name', '$icon', '$topics', '$daily_mins', 0)";
    mysqli_query($conn, $query);
    
    header("Location: subjects.php");
    exit();
}

// Handle subject deletion
if (isset($_GET['delete'])) {
    $subject_id = (int)$_GET['delete'];
    
    // First verify it's the user's subject and get its name
    $subject_check = mysqli_query($conn, "SELECT name FROM subjects WHERE id='$subject_id' AND user_id='$user_id'");
    
    if ($subject_check && mysqli_num_rows($subject_check) > 0) {
        $subject_row = mysqli_fetch_assoc($subject_check);
        $subject_name_escaped = mysqli_real_escape_string($conn, $subject_row['name']);
        
        // Delete all tasks under this subject name for this user
        mysqli_query($conn, "DELETE FROM tasks WHERE subject_name='$subject_name_escaped' AND user_id='$user_id'");
        
        // Delete the subject
        mysqli_query($conn, "DELETE FROM subjects WHERE id='$subject_id' AND user_id='$user_id'");
    }
    
    header("Location: subjects.php");
    exit();
}

$user_result = mysqli_query($conn, "SELECT fullname FROM users WHERE id='$user_id'");
$user = mysqli_fetch_assoc($user_result);
$name_parts = explode(" ", $user['fullname']);
$first_name = $name_parts[0];
$avatar_letter = strtoupper(substr($first_name, 0, 1));

// Fetch user subjects
$subjects_query = "SELECT * FROM subjects WHERE user_id='$user_id' ORDER BY created_at DESC";
$subjects_result = mysqli_query($conn, $subjects_query);

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
    <title>My Subjects | AI Study Planner</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="dashboard.css">
    
    <style>
        /* --- SUBJECT SPECIFIC STYLES --- */
        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .subject-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            position: relative;
            transition: 0.3s;
        }

        .subject-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        .subject-icon {
            width: 50px;
            height: 50px;
            background: #dcfce7;
            color: #15803d;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        .subject-info h3 { font-size: 1.25rem; margin-bottom: 5px; }
        .subject-info p { color: #64748b; font-size: 0.9rem; margin-bottom: 25px; }

        /* --- PROGRESS BAR --- */
        .progress-container { margin-top: 15px; }
        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #f1f5f9;
            border-radius: 10px;
            overflow: hidden;
        }
        .progress-fill { height: 100%; border-radius: 10px; transition: width 1s ease-in-out; }

        /* --- ADD NEW SUBJECT CARD --- */
        .add-subject-card {
            border: 2px dashed #cbd5e1;
            background: transparent;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #94a3b8;
            min-height: 240px;
            transition: 0.2s;
        }
        .add-subject-card:hover { 
            border-color: #15803d; 
            color: #15803d; 
            background: #dcfce7; 
        }
        .add-icon { font-size: 3rem; margin-bottom: 10px; }
    </style>
</head>
<body <?php echo $body_class; ?>>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo">StudyPlaner</div>
            <nav class="sidebar-nav">
                <a href="dashboard.php"><span>📊</span> Dashboard</a>
                <a href="schedule.php"><span>📅</span> My Schedule</a>
                <a href="subjects.php" class="active"><span>📚</span> Subjects</a>
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
                    <h1>My Subjects 📚</h1>
                    <p>Track your curriculum and mastery levels.</p>
                </div>
                <div class="user-profile">
                    <button class="btn-primary" id="topAddSubjectBtn">+ New Subject</button>
                    <div class="avatar"><?php echo htmlspecialchars($avatar_letter); ?></div>
                </div>
            </header>

            <div class="subjects-grid">
                <?php while ($subject = mysqli_fetch_assoc($subjects_result)): ?>
                <div class="subject-card">
                    <a href="subjects.php?delete=<?php echo $subject['id']; ?>" style="position: absolute; top: 5px; right: 5px; z-index: 10; font-size: 1.5rem; cursor: pointer; color: #ef4444; opacity: 0.7; transition: 0.2s; text-decoration: none; padding: 10px;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'" title="Remove Subject">🗑️</a>
                    <div class="subject-icon"><?php echo htmlspecialchars($subject['icon']); ?></div>
                    <div class="subject-info">
                        <h3><?php echo htmlspecialchars($subject['name']); ?></h3>
                        <p><?php echo htmlspecialchars($subject['topics_count']); ?> Topics | <?php echo htmlspecialchars($subject['daily_study_mins'] ?? 30); ?>m daily</p>
                    </div>
                    <div class="progress-container">
                        <div class="progress-text">
                            <span>Mastery</span>
                            <span><?php echo htmlspecialchars($subject['mastery_percent']); ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo htmlspecialchars($subject['mastery_percent']); ?>%; background: #15803d;"></div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>

                <div class="subject-card add-subject-card" id="openSubjectModalBtn">
                    <span class="add-icon">+</span>
                    <span style="font-weight: 600;">Add Subject</span>
                </div>
            </div>
        </main>
    </div>

    <div id="subjectModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeSubjectModalBtn">&times;</span>
            <h2>Add New Subject</h2>
            <form id="newSubjectForm" action="subjects.php" method="POST">
                <input type="hidden" name="action" value="add_subject">
                <div class="input-group">
                    <label>Subject Name</label>
                    <input type="text" id="subjectName" name="subjectName" required placeholder="e.g., Organic Chemistry">
                </div>
                <div class="input-group">
                    <label>Select Icon</label>
                    <select id="subjectIcon" name="subjectIcon" required>
                        <option value="🧬">🧬 Science</option>
                        <option value="💻">💻 Tech / Coding</option>
                        <option value="📐">📐 Math</option>
                        <option value="🌍">🌍 History / Geography</option>
                        <option value="📖">📖 Literature</option>
                        <option value="🗣️">🗣️ Language</option>
                        <option value="🎨">🎨 Art / Design</option>
                        <option value="🎵">🎵 Music</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>Total Chapters / Topics</label>
                    <input type="number" id="subjectTopics" name="subjectTopics" required placeholder="e.g., 10">
                </div>
                <div class="input-group">
                    <label>Daily Study Time (Minutes)</label>
                    <input type="number" id="dailyStudyMins" name="dailyStudyMins" required placeholder="e.g., 30" value="30">
                </div>
                <button type="submit" class="btn-primary full-width">Save Subject</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById("subjectModal");
            const cardBtn = document.getElementById("openSubjectModalBtn");
            const topBtn = document.getElementById("topAddSubjectBtn");
            const closeBtn = document.getElementById("closeSubjectModalBtn");

            // Open modal when either the card or the top button is clicked
            if (cardBtn) cardBtn.addEventListener("click", () => modal.style.display = "flex");
            if (topBtn) topBtn.addEventListener("click", () => modal.style.display = "flex");

            // Close modal when X is clicked
            if (closeBtn) closeBtn.addEventListener("click", () => modal.style.display = "none");

            // Close modal when clicking outside the box
            window.addEventListener("click", (e) => { 
                if (e.target === modal) modal.style.display = "none"; 
            });

            // Let the form submit natively
            document.getElementById('newSubjectForm').addEventListener('submit', function() {
                const btn = this.querySelector('button[type="submit"]');
                btn.innerText = "⏳ Saving...";
                btn.style.backgroundColor = "var(--primary-green-hover)";
            });
        });
    </script>
</body>
</html>
